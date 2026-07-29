<?php

namespace App\Http\Controllers;

use App\Models\AbandonedCart;
use App\Models\Project;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Support\StorefrontNavigation;
use App\Support\StorefrontTheme;
use App\Models\StoreSection;
use App\Models\StorePopup;
use App\Models\ProjectTemplate;

class PublicController extends Controller
{
    /**
     * Plantillas completas que ya existen en producción.
     *
     * Estas vistas no deben sustituirse por el layout genérico de la
     * estructura V2: al seleccionarlas, se renderiza su diseño Blade real.
     */
    public const PRODUCTION_TEMPLATE_VIEWS = [
        'default'     => 'public.catalog',
        'direct'      => 'public.templates.direct',
        'ella'        => 'public.templates.ella',
        'editorial'   => 'public.templates.editorial',
        'nordic'      => 'public.templates.nordic',
        'luxe'        => 'public.templates.luxe',
        'flash'       => 'public.templates.flash',
        'bistro'      => 'public.templates.bistro',
        'urban'       => 'public.templates.urban',
        'boutique'    => 'public.templates.boutique',
        'fresh'       => 'public.templates.fresh',
        'porto'       => 'public.templates.porto',
        'licoreria'   => 'public.templates.licoreria',
        'farma'       => 'public.templates.farma',
        'lavanderia'  => 'public.templates.lavanderia',
        'ecommerce'   => 'public.templates.ecommerce',
        'tecnologia'  => 'public.templates.tecnologia',
        'computienda' => 'public.templates.computienda',
    ];

    private function project(string $slug): Project
    {
        return Project::where('slug', $slug)->where('is_active', true)->firstOrFail();
    }

    public function catalog(string $slug)
    {
        $project = $this->project($slug);

        // Las plantillas de producción conservan su estructura visual propia,
        // incluso cuando el proyecto utiliza el constructor V2.
        if ($this->productionTemplateView($project)) {
            [$view, $data] = $this->prepararCatalogo($project);
            return view($view, $data);
        }

        if ($project->setting('storefront_structure_v2', '0') === '1') {
            return $this->renderStorefrontHome($project);
        }
        [$view, $data] = $this->prepararCatalogo($project);
        return view($view, $data);
    }

    public function previewStorefront(Project $project)
    {
        if ($this->productionTemplateView($project)) {
            [$view, $data] = $this->prepararCatalogo($project, true);
            return view($view, $data);
        }

        return $this->renderStorefrontHome($project, true);
    }

    private function productionTemplateView(Project $project): ?string
    {
        $template = (string) $project->setting('catalog_template', 'default');
        $view = self::PRODUCTION_TEMPLATE_VIEWS[$template] ?? null;

        if ($view && !view()->exists($view)) {
            Log::warning('Storefront template view is unavailable; using the compatibility fallback.', [
                'project_id' => $project->id,
                'template' => $template,
                'view' => $view,
            ]);

            return null;
        }

        return $view;
    }

    public function shop(Request $request, string $slug)
    {
        $project = $this->project($slug);

        // Plantillas de producción (computienda, etc.): su /tienda usa la MISMA
        // plantilla que el inicio, pero en modo "tienda" (catálogo con filtros).
        if ($this->productionTemplateView($project)) {
            [$view, $data] = $this->prepararCatalogo($project, false, 'tienda');
            return view($view, $data);
        }

        abort_unless($project->setting('storefront_structure_v2', '0') === '1' || $request->attributes->get('storefront_preview') === true, 404);
        $data = $this->storefrontBaseData($project);
        $categories = $project->categories()->where('is_active', true)
            ->with(['children' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->whereNull('parent_id')->orderBy('sort_order')->orderBy('name')->get();

        $query = $project->products()->where('is_available', true)->with(['mainImage', 'category']);
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';
                $builder->where('name', 'like', $like)->orWhere('sku', 'like', $like)->orWhere('description', 'like', $like);
            });
        }

        $categoryId = $request->integer('category');
        $selectedCategory = null;
        if ($categoryId) {
            $selectedCategory = $project->categories()->where('is_active', true)->find($categoryId);
            if ($selectedCategory) {
                $ids = $selectedCategory->parent_id ? [$selectedCategory->id] : $selectedCategory->children()->where('is_active', true)->pluck('id')->prepend($selectedCategory->id)->all();
                $query->whereIn('category_id', $ids);
            }
        }
        if ($request->boolean('sale')) $query->whereNotNull('compare_price')->whereColumn('compare_price', '>', 'price');
        if (is_numeric($request->query('min_price'))) $query->where('price', '>=', max(0, (float) $request->query('min_price')));
        if (is_numeric($request->query('max_price'))) $query->where('price', '<=', max(0, (float) $request->query('max_price')));

        match ($request->query('sort', 'recommended')) {
            'price_asc' => $query->orderBy('price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price')->orderByDesc('id'),
            'name' => $query->orderBy('name'),
            'newest' => $query->latest(),
            default => $query->orderBy('sort_order')->orderByDesc('id'),
        };
        $perPage = in_array($request->integer('per_page'), [12, 24, 48], true) ? $request->integer('per_page') : 12;
        $products = $query->paginate($perPage)->withQueryString();

        return view('public.storefront.shop', $data + compact('categories', 'products', 'search', 'selectedCategory'));
    }

    private function renderStorefrontHome(Project $project, bool $preview = false)
    {
        $data = $this->storefrontBaseData($project, $preview);
        $view = match ($data['storefrontTheme']['key'] ?? 'default') {
            'computienda' => 'public.storefront.templates.computienda',
            'ecommerce' => 'public.storefront.templates.ecommerce',
            'direct' => 'public.storefront.templates.direct',
            'default' => 'public.storefront.templates.classic',
            default => 'public.storefront.home',
        };

        return view($view, $data);
    }

    public function storefrontBaseData(Project $project, bool $preview = false): array
    {
        $settings = $project->settings()->pluck('value', 'key')->all();
        $projectTemplate = ProjectTemplate::where('project_id', $project->id)->where('is_active', true)->first();
        if ($projectTemplate && is_array($projectTemplate->settings)) $settings = array_merge($projectTemplate->settings, $settings);

        $sectionsQuery = StoreSection::where('project_id', $project->id)->where('page', 'home');
        if (!$preview) {
            $sectionsQuery->where('is_enabled', true)
                ->where(fn ($query) => $query->whereNull('publish_from')->orWhere('publish_from', '<=', now()))
                ->where(fn ($query) => $query->whereNull('publish_until')->orWhere('publish_until', '>=', now()));
        }
        $sections = $sectionsQuery->orderBy('sort_order')->get()->groupBy('component')->map->first();
        if ($preview) {
            $sections = $sections->filter->enabledForPreview()->map(function ($section) {
                $section->content = $section->contentForPreview();
                $section->variant = $section->variantForPreview();
                $section->sort_order = $section->draft_sort_order ?? $section->sort_order;
                return $section;
            })->sortBy('sort_order')->values();
        } else $sections = $sections->sortBy('sort_order')->values();

        $popup = StorePopup::where('project_id', $project->id)->where('is_enabled', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->latest()->first();
        $storeMenu = StorefrontNavigation::menu($project) ?: StorefrontNavigation::ensure($project)->load(['rootItems.children']);
        $headerSettings = StorefrontNavigation::headerSettings($project);
        $storefrontTheme = StorefrontTheme::resolve($settings);

        $previewMode = $preview;
        return compact('project', 'settings', 'sections', 'popup', 'storeMenu', 'headerSettings', 'storefrontTheme', 'previewMode');
    }

    /**
     * Prepara TODOS los datos que necesitan las plantillas de tienda
     * (categorías con productos, secciones, settings de diseño) y decide
     * qué plantilla usar. Reutilizable: la tienda pública y el catálogo del
     * revendedor comparten exactamente la misma vista y datos.
     *
     * @return array{0:string,1:array}  [nombre de la vista, datos]
     */
    public function prepararCatalogo(\App\Models\Project $project, bool $preview = false, string $storeView = 'home'): array
    {
        // Cargar settings del proyecto como arreglo clave=>valor
        $settingsCollection = $project->settings()->pluck('value', 'key');
        $settings = $settingsCollection->toArray();

        $popup = \App\Models\StorePopup::where('project_id', $project->id)
            ->where('is_enabled', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->latest()->first();
        $sectionsQuery = \App\Models\StoreSection::where('project_id', $project->id)->where('page', 'home');
        if (!$preview) {
            $sectionsQuery->where('is_enabled', true)
                ->where(fn ($q) => $q->whereNull('publish_from')->orWhere('publish_from', '<=', now()))
                ->where(fn ($q) => $q->whereNull('publish_until')->orWhere('publish_until', '>=', now()));
        }
        $sections = $sectionsQuery->orderBy('sort_order')->get()->groupBy('component')->map->first();
        if ($preview) {
            $sections = $sections->filter->enabledForPreview()->map(function ($section) {
                $section->content = $section->contentForPreview();
                $section->variant = $section->variantForPreview();
                $section->sort_order = $section->draft_sort_order ?? $section->sort_order;
                return $section;
            })->sortBy('sort_order')->values();
        } else {
            $sections = $sections->sortBy('sort_order')->values();
        }
        $aboutPage = \App\Models\StorePage::where('project_id', $project->id)->where('key', 'nosotros')->where('is_enabled', true)->first();

        // Si el proyecto tiene una plantilla activa, fusionar defaults de la plantilla
        $projectTemplate = \App\Models\ProjectTemplate::where('project_id', $project->id)->where('is_active', true)->first();
        if ($projectTemplate && is_array($projectTemplate->settings)) {
            // Template settings actúan como valores por defecto; los settings del proyecto sobrescriben.
            $settings = array_merge($projectTemplate->settings, $settings);
        }

        // Cargar árbol: padres con hijos, cada nodo con sus productos y servicios
        $categories = $project->categories()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with([
                'products' => fn($q) => $q->where('is_available', true)->with('mainImage')->orderBy('sort_order'),
                'services' => fn($q) => $q->where('is_available', true)->orderBy('sort_order'),
                'children' => fn($q) => $q->where('is_active', true)->with([
                    'products' => fn($q2) => $q2->where('is_available', true)->with('mainImage')->orderBy('sort_order'),
                    'services' => fn($q2) => $q2->where('is_available', true)->orderBy('sort_order'),
                ])->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')->get();

        // Productos para secciones de la tienda
        $newArrivals = $project->products()->where('is_available', true)
            ->with(['mainImage','category'])->latest()->take(8)->get();
        $onSale = $project->products()->where('is_available', true)
            ->whereNotNull('compare_price')->whereColumn('compare_price', '>', 'price')
            ->with(['mainImage','category'])->take(8)->get();
        $featured = $project->products()->where('is_available', true)
            ->with(['mainImage','category'])->inRandomOrder()->take(8)->get();

        $productRatings = \App\Models\Review::where('project_id', $project->id)
            ->where('is_approved', true)
            ->select('product_id', \DB::raw('ROUND(AVG(rating),1) as avg_rating'), \DB::raw('COUNT(*) as rating_count'))
            ->groupBy('product_id')
            ->get()->keyBy('product_id');

        // Detectar plantilla activa y cargar vista correspondiente
        $template  = $settings['catalog_template'] ?? 'default';
        $tplViews = self::PRODUCTION_TEMPLATE_VIEWS;
        $view = isset($tplViews[$template]) && view()->exists($tplViews[$template])
            ? $tplViews[$template]
            : 'public.catalog';

        // Negocios de servicios (ej. lavandería): fusionar servicios dentro de
        // la relación 'products' para que las plantillas los muestren igual,
        // sin duplicar la lógica de la vista. Los servicios de subcategorías se
        // suben a la categoría padre (la vista solo recorre el primer nivel).
        $svcToProduct = function ($s) {
            $p = new \App\Models\Product([
                'name'         => $s->name,
                'description'  => $s->description,
                'price'        => $s->price,
                'category_id'  => $s->category_id,
                'is_available' => $s->is_available,
                'sort_order'   => $s->sort_order,
            ]);
            $p->id = 'svc-' . $s->id;   // id no numérico para distinguirlo
            $p->setRelation('mainImage', null);
            return $p;
        };

        foreach ($categories as $cat) {
            $svc = collect();
            if ($cat->relationLoaded('services')) {
                $svc = $svc->concat($cat->services);
            }
            if ($cat->relationLoaded('children')) {
                foreach ($cat->children as $child) {
                    if ($child->relationLoaded('services')) {
                        $svc = $svc->concat($child->services);
                    }
                }
            }
            if ($svc->isNotEmpty()) {
                $asProducts = $svc->map($svcToProduct);
                $cat->setRelation('products', $cat->products->concat($asProducts)->sortBy('sort_order')->values());
            }
        }

        // Secciones destacadas: si no hay productos, usar los servicios fusionados
        if ($featured->isEmpty()) {
            $featured = $categories->flatMap->products->take(8)->values();
        }

        // Menú del Constructor (para que las plantillas lo usen en su navegación)
        $storeMenu = \App\Support\StorefrontNavigation::menu($project)
            ?: \App\Support\StorefrontNavigation::ensure($project)->load(['rootItems.children']);

        return [$view, compact('project','categories','settings','newArrivals','onSale','featured','productRatings','popup','sections','aboutPage','storeMenu','storeView')];
    }

    public function validateCoupon(Request $request, string $slug)
    {
        $project = $this->project($slug);
        $code    = strtoupper(trim($request->input('code', '')));
        $subtotal = (float) $request->input('subtotal', 0);

        $shipping = (float) $request->input('shipping', 0);
        $phone    = $request->input('phone', '');

        $coupon = $project->coupons()->where('code', $code)->first();
        if (!$coupon || !$coupon->isValid($subtotal, $phone)) {
            $msg = 'Cupón inválido o expirado.';
            if ($coupon && ($coupon->type === 'first_purchase' || $coupon->first_purchase_only)) {
                $msg = 'Este cupón es solo para nuevos clientes.';
            }
            return response()->json(['ok' => false, 'message' => $msg]);
        }

        $discount = $coupon->calculateDiscount($subtotal, $shipping);
        return response()->json([
            'ok'        => true,
            'code'      => $coupon->code,
            'type'      => $coupon->type,
            'value'     => (float) $coupon->value,
            'min_order' => (float) $coupon->min_order,
            'discount'  => $discount,
            'label'     => \App\Models\Coupon::$types[$coupon->type] ?? $coupon->type,
        ]);
    }

    public function storeOrder(Request $request, string $slug)
    {
        $project = $this->project($slug);
        $data = $request->validate([
            'client_name'      => 'required|string|max:100',
            'client_phone'     => 'required|string|max:30',
            'client_email'     => 'nullable|email|max:150',
            'notes'            => 'nullable|string',
            'coupon_code'      => 'nullable|string|max:50',
            'delivery_address' => 'nullable|string|max:255',
            'shipping_cost'    => 'nullable|numeric|min:0',
            'items'            => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer',
            'items.*.name'       => 'nullable|string|max:255',
            'items.*.price'      => 'nullable|numeric|min:0',
            'items.*.quantity'   => 'required|integer|min:1',
            'payment_method'   => 'nullable|string|max:80',
            'payment_reference'=> 'nullable|string|max:100',
            'payment_proof'    => 'nullable|url|max:500',
        ]);

        return DB::transaction(function () use ($project, $data) {
            // Verificar y descontar stock con lock pesimista
            foreach ($data['items'] as $item) {
                if (empty($item['product_id'])) continue;

                $product = Product::allProjects()
                    ->where('id', $item['product_id'])
                    ->where('project_id', $project->id)
                    ->lockForUpdate()
                    ->first();

                if (!$product) continue;

                if ($product->stock !== null) {
                    $qty = (int) ($item['quantity'] ?? 1);
                    if ($product->stock < $qty) {
                        return response()->json([
                            'ok'      => false,
                            'message' => "Stock insuficiente para \"{$product->name}\" (disponible: {$product->stock}).",
                        ], 422);
                    }
                    $product->decrement('stock', $qty);
                }
            }

            $subtotal   = collect($data['items'])->sum(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 1));
            $shipping   = (float) ($data['shipping_cost'] ?? 0);
            $discount   = 0.0;
            $couponCode = null;

            if (!empty($data['coupon_code'])) {
                $code   = strtoupper(trim($data['coupon_code']));
                $coupon = $project->coupons()->where('code', $code)->first();
                if ($coupon && $coupon->isValid($subtotal, $data['client_phone'] ?? null)) {
                    $discount   = $coupon->calculateDiscount($subtotal, $shipping);
                    $couponCode = $coupon->code;
                    $coupon->increment('uses_count');
                }
            }

            $total = max(0, $subtotal - $discount + $shipping);
            $order = $project->orders()->create([
                'client_name'      => $data['client_name'],
                'client_phone'     => $data['client_phone'],
                'client_email'     => $data['client_email'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'coupon_code'      => $couponCode,
                'discount'         => $discount > 0 ? $discount : null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'shipping_cost'    => $shipping > 0 ? $shipping : null,
                'total'            => $total,
                'status'           => 'pending',
                'sales_channel'    => 'web',
                'payment_method'   => $data['payment_method'] ?? null,
                'payment_reference'=> $data['payment_reference'] ?? null,
                'payment_proof'    => $data['payment_proof'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $pid = $item['product_id'] ?? null;
                $name = $item['name'] ?? null;
                $price = $item['price'] ?? null;
                if ($pid && (!$name || !$price)) {
                    $prod = Product::allProjects()->where('id', $pid)->where('project_id', $project->id)->first();
                    $name  = $name  ?: ($prod->name  ?? 'Producto');
                    $price = $price ?: ($prod->price ?? 0);
                }
                $order->items()->create([
                    'product_id' => $pid,
                    'name'       => $name ?: 'Producto',
                    'price'      => $price ?: 0,
                    'quantity'   => $item['quantity'],
                ]);
            }

            // Marcar carrito abandonado como recuperado
            AbandonedCart::where('project_id', $project->id)
                ->where('client_phone', $data['client_phone'])
                ->whereNull('recovered_at')
                ->update(['recovered_at' => now()]);

            // Enviar comprobante al WhatsApp del negocio via bot Meta
            if (!empty($data['payment_proof'])) {
                $this->sendVoucherToOwner($project, $order, $data['payment_proof']);
            }

            return response()->json(['ok' => true, 'order_id' => $order->id, 'total' => (float) $order->total]);
        });
    }

    /** Guarda carrito para recuperación por abandono (llamado desde JS al llenar nombre+teléfono) */
    public function saveCart(Request $request, string $slug)
    {
        $project = $this->project($slug);
        $data = $request->validate([
            'client_name'  => 'required|string|max:100',
            'client_phone' => 'required|string|max:30',
            'client_email' => 'nullable|email|max:150',
            'items'        => 'required|array|min:1',
            'subtotal'     => 'required|numeric|min:0',
            'coupon_code'  => 'nullable|string|max:50',
        ]);

        // Upsert por proyecto + teléfono para no duplicar
        AbandonedCart::updateOrCreate(
            ['project_id' => $project->id, 'client_phone' => $data['client_phone']],
            [
                'client_name'   => $data['client_name'],
                'client_email'  => $data['client_email'] ?? null,
                'items'         => $data['items'],
                'subtotal'      => $data['subtotal'],
                'coupon_code'   => $data['coupon_code'] ?? null,
                'reminder_sent' => false,
                'recovered_at'  => null,
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function storeQuote(Request $request, string $slug)
    {
        $project = $this->project($slug);
        $data = $request->validate([
            'client_name'       => 'nullable|string|max:100',
            'client_phone'      => 'nullable|string|max:30',
            'client_doc_type'   => 'nullable|string|max:20',
            'client_doc_number' => 'nullable|string|max:20',
            'notes'             => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.price'       => 'required|numeric|min:0',
            'items.*.quantity'    => 'required|numeric|min:1',
        ]);

        $total = collect($data['items'])->sum(fn($i) => $i['price'] * $i['quantity']);

        $quote = $project->quotes()->create([
            'client_name'       => $data['client_name'] ?? 'Cliente web',
            'client_phone'      => $data['client_phone'] ?? null,
            'client_doc_type'   => $data['client_doc_type'] ?? null,
            'client_doc_number' => $data['client_doc_number'] ?? null,
            'notes'             => $data['notes'] ?? null,
            'total'             => $total,
            'status'            => 'sent',
        ]);

        foreach ($data['items'] as $item) {
            $quote->items()->create([
                'description' => $item['description'],
                'price'       => $item['price'],
                'quantity'    => $item['quantity'],
            ]);
        }

        return response()->json(['ok' => true, 'quote_id' => $quote->id]);
    }

    public function product(string $slug, int $id)
    {
        $project = $this->project($slug);
        $product = $project->products()
            ->where('is_available', true)
            ->with(['images', 'category', 'mainImage'])
            ->findOrFail($id);
        $settings   = $project->settings()->pluck('value', 'key');
        $related    = $project->products()
            ->where('is_available', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with('mainImage')
            ->take(6)->get();
        $categories = $project->categories()->where('is_active', true)
            ->with(['products' => fn($q) => $q->where('is_available', true)->with('mainImage')])
            ->orderBy('sort_order')->get();
        $reviews    = $product->approvedReviews()->get();
        $avgRating  = $reviews->count() ? round($reviews->avg('rating'), 1) : null;
        if ($project->setting('storefront_structure_v2', '0') === '1') {
            return view('public.storefront.product', $this->storefrontBaseData($project) + compact('product', 'related', 'reviews', 'avgRating'));
        }
        return view('public.product', compact('project', 'product', 'settings', 'related', 'categories', 'reviews', 'avgRating'));
    }

    public function storeReview(Request $request, string $slug, int $productId)
    {
        $project = $this->project($slug);
        $product = $project->products()->where('is_available', true)->findOrFail($productId);

        $data = $request->validate([
            'author_name'  => 'required|string|max:100',
            'author_email' => 'nullable|email|max:150',
            'rating'       => 'required|integer|min:1|max:5',
            'comment'      => 'nullable|string|max:1000',
        ]);

        // Simple rate limiting: 1 review per IP per product per hour
        $recent = Review::where('product_id', $product->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        if ($recent >= 3) {
            return response()->json(['ok' => false, 'message' => 'Demasiadas reseñas. Inténtalo más tarde.'], 429);
        }

        $review = Review::create([
            'project_id'   => $project->id,
            'product_id'   => $product->id,
            'author_name'  => $data['author_name'],
            'author_email' => $data['author_email'] ?? null,
            'rating'       => $data['rating'],
            'comment'      => $data['comment'] ?? null,
            'is_approved'  => false,
        ]);

        return response()->json(['ok' => true, 'message' => 'Reseña enviada. Aparecerá tras ser aprobada.']);
    }

    public function thankyou(string $slug, int $orderId)
    {
        $project  = $this->project($slug);
        $order    = $project->orders()->with('items')->findOrFail($orderId);
        $settings = $project->settings()->pluck('value', 'key');
        return view('public.thankyou', compact('project', 'order', 'settings'));
    }

    public function book(string $slug)
    {
        $project  = $this->project($slug);
        $services = $project->services()->where('is_available', true)->get();
        return view('public.book', compact('project', 'services'));
    }

    public function storeBook(Request $request, string $slug)
    {
        $project = $this->project($slug);
        $data = $request->validate([
            'service_id'   => 'required|integer',
            'client_name'  => 'required|string|max:100',
            'client_phone' => 'required|string|max:30',
            'date'         => 'required|date|after_or_equal:today',
            'start_time'   => 'required',
        ]);
        $service = $project->services()->findOrFail($data['service_id']);
        $endTs   = strtotime($data['start_time']) + ($service->duration_min * 60);
        $project->appointments()->create([
            'service_id'   => $service->id,
            'client_name'  => $data['client_name'],
            'client_phone' => $data['client_phone'],
            'date'         => $data['date'],
            'start_time'   => $data['start_time'],
            'end_time'     => date('H:i', $endTs),
            'status'       => 'pending',
        ]);
        return response()->json(['ok' => true]);
    }

    private function sendVoucherToOwner($project, $order, string $voucherUrl): void
    {
        try {
            $botPort    = env('VOUCHER_PORT', '3005');
            $botToken   = env('BOT_TOKEN', 'wa-bot-secret-2024');
            $ownerPhone = preg_replace('/\D/', '', $project->whatsapp ?? '');
            if (!$ownerPhone) return;

            // Armar items del pedido
            $itemLines = $order->items->map(fn($i) =>
                "  - {$i->name} x{$i->quantity} = S/ " . number_format($i->price * $i->quantity, 2)
            )->implode("\n");

            $sep = "--------------------";
            $caption  = "*NUEVO PEDIDO #{$order->id} - {$project->name}*\n";
            $caption .= "{$sep}\n";
            $caption .= "*PRODUCTOS:*\n{$itemLines}\n";
            $caption .= "{$sep}\n";
            $caption .= "*TOTAL: S/ " . number_format((float)$order->total, 2) . "*\n";
            $caption .= "{$sep}\n";
            if ($order->payment_method) $caption .= "*PAGO:* " . strtoupper($order->payment_method) . "\n";
            if ($order->payment_reference) $caption .= "Nro. operacion: {$order->payment_reference}\n";
            $caption .= "{$sep}\n";
            $caption .= "*CLIENTE:*\n";
            $caption .= "Nombre: {$order->client_name}\n";
            $caption .= "Celular: {$order->client_phone}\n";
            if ($order->client_email) $caption .= "Email: {$order->client_email}\n";
            if ($order->delivery_address) $caption .= "Direccion: {$order->delivery_address}\n";
            if ($order->notes) $caption .= "Notas: {$order->notes}\n";

            $payload = json_encode([
                'token'     => $botToken,
                'to'        => $ownerPhone,
                'image_url' => $voucherUrl,
                'caption'   => $caption,
            ]);

            $ctx = stream_context_create(['http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($payload) . "\r\n",
                'content' => $payload,
                'timeout' => 5,
                'ignore_errors' => true,
            ]]);
            @file_get_contents("http://127.0.0.1:{$botPort}/", false, $ctx);
        } catch (\Throwable $e) {
            // silencioso
        }
    }

    public function uploadVoucher(Request $request, string $slug)
    {
        $this->project($slug); // verifica que el proyecto existe y está activo

        $request->validate([
            'voucher' => 'required|file|image|max:5120', // 5MB max
        ]);

        $path = $request->file('voucher')->store('vouchers', 'public');
        $url  = url('storage/' . $path);

        return response()->json(['ok' => true, 'url' => $url]);
    }

    public function sitemap(string $slug)
    {
        $project  = $this->project($slug);
        $settings = $project->settings()->pluck('value', 'key')->toArray();
        $baseUrl  = ($settings['seo_canonical'] ?? null)
            ?: ($project->custom_domain ? 'https://'.$project->custom_domain : url('/'.$slug));

        $products = $project->products()
            ->where('is_available', true)
            ->with(['images' => fn($q) => $q->where('is_main', true)])
            ->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
        $xml .= '<url><loc>'.e($baseUrl).'</loc><changefreq>daily</changefreq><priority>1.0</priority></url>';

        foreach ($products as $p) {
            $xml .= '<url>';
            $xml .= '<loc>'.e($baseUrl.'/p/'.$p->id).'</loc>';
            $xml .= '<changefreq>weekly</changefreq><priority>0.8</priority>';
            $img = $p->images->first();
            if ($img) {
                $imgUrl = str_starts_with($img->url, 'http') ? $img->url : asset('storage/'.$img->url);
                $xml .= '<image:image><image:loc>'.e($imgUrl).'</image:loc><image:title>'.e($p->name).'</image:title></image:image>';
            }
            $xml .= '</url>';
        }

        $xml .= '</urlset>';
        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function robots(string $slug)
    {
        $project  = $this->project($slug);
        $settings = $project->settings()->pluck('value', 'key')->toArray();
        $baseUrl  = ($settings['seo_canonical'] ?? null)
            ?: ($project->custom_domain ? 'https://'.$project->custom_domain : url('/'.$slug));

        $txt = "User-agent: *\nAllow: /\nSitemap: {$baseUrl}/sitemap.xml\n";
        return response($txt, 200)->header('Content-Type', 'text/plain');
    }
}
