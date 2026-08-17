<?php

namespace App\Http\Controllers;

use App\Models\AbandonedCart;
use App\Models\Project;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    /** Plantillas cuya página de producto vive dentro de la misma plantilla
     *  (bloque storeView==='producto'). ecommerce NO va aquí: usa SPA interna. */
    public const PRODUCT_VIEW_TEMPLATES = ['computienda'];

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
        if ($this->productionTemplateView($project, true)) {
            [$view, $data] = $this->prepararCatalogo($project, true, 'home', $this->draftSettingsOverlay($project));
            return view($view, $data);
        }

        return $this->renderStorefrontHome($project, true);
    }

    /** Todos los settings en borrador del Constructor, como overlay para preview. */
    private function draftSettingsOverlay(Project $project): array
    {
        return DB::table('builder_drafts')
            ->where('project_id', $project->id)->where('resource_type', 'settings')
            ->pluck('payload', 'resource_key')
            ->map(fn ($payload) => json_decode($payload, true)['value'] ?? null)
            ->filter(fn ($v) => $v !== null)->all();
    }

    /**
     * $preview=true (solo desde la vista previa del Constructor) considera la
     * plantilla elegida en borrador aunque aún no se haya publicado. La tienda
     * pública real siempre llama esto sin el flag: solo ve lo publicado.
     */
    private function productionTemplateView(Project $project, bool $preview = false): ?string
    {
        $template = $preview
            ? (string) ($this->draftSettingsOverlay($project)['catalog_template'] ?? $project->setting('catalog_template', 'default'))
            : (string) $project->setting('catalog_template', 'default');
        $view = self::PRODUCTION_TEMPLATE_VIEWS[$template] ?? null;

        // Si la plantilla elegida no tiene vista, la tienda cae a la de por
        // defecto y responde 200 con OTRA plantilla, sin avisar a nadie: ni al
        // negocio, que la eligio, ni al operador. `editorial`, `luxe` y
        // `bistro` estan declaradas y son ofrecibles pero NO tienen Blade.
        // Hoy ningun proyecto las usa (solo `computienda` y `ecommerce`), asi
        // que esto es una trampa latente: se deja registrada para que se
        // detecte el dia que alguien las elija, en vez de fallar en silencio.
        if ($view && ! view()->exists($view)) {
            // Las claves del contexto son las que fija el contrato
            // (`StorefrontStructureV2Test`): `project_id` y `template`. Las
            // habia escrito en español y el propio contrato lo caza.
            \Illuminate\Support\Facades\Log::warning('compatibility fallback: plantilla sin vista', [
                'project_id' => $project->id,
                'template'   => $template,
                'view'       => $view,
            ]);
        }

        return $view && view()->exists($view) ? $view : null;
    }

    /**
     * ¿Están habilitados los perfiles de catálogo para esta tienda?
     * Desactivado por defecto: sin coste ni consultas de perfiles cuando no se usa.
     */
    private function catalogProfilesEnabled(Project $project): bool
    {
        return (string) $project->setting('catalog_profiles_enabled', '0') === '1';
    }

    /**
     * Resuelve el perfil activo desde el slug de la URL. La URL es la fuente de
     * verdad. Si la funcionalidad está desactivada, no hay slug, o el perfil no
     * existe/está deshabilitado, devuelve null (catálogo normal) — salvo que se
     * pida explícitamente un slug inexistente/deshabilitado, en cuyo caso 404.
     */
    private function resolveActiveProfile(Project $project, ?string $profileSlug): ?\App\Models\StoreCatalogProfile
    {
        if (! $this->catalogProfilesEnabled($project)) {
            // Funcionalidad off: un slug de perfil en la URL no debe "colgar" la ruta.
            abort_if($profileSlug !== null, 404);
            return null;
        }
        if ($profileSlug === null) {
            return null; // /tienda sin perfil → catálogo completo
        }
        $profile = $project->catalogProfiles()->where('slug', $profileSlug)->first();
        // Perfil inexistente o deshabilitado → respuesta segura (404), sin productos.
        abort_if($profile === null || ! $profile->is_enabled, 404);

        return $profile;
    }

    /** Perfiles habilitados y visibles en el menú (para el selector/nav). Vacío si off. */
    private function menuProfiles(Project $project): \Illuminate\Support\Collection
    {
        if (! $this->catalogProfilesEnabled($project)) {
            return collect();
        }

        return $project->catalogProfiles()->inMenu()->orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * Catálogo filtrado por categoría con URL legible: /tienda/c/computadoras.
     *
     * No duplica nada de `shop()`: resuelve el slug a id, lo inyecta en la
     * petición como si hubiera llegado por `?category=` y delega. Así el
     * catálogo, los filtros y la paginación siguen funcionando exactamente
     * igual, y la forma antigua sigue viva.
     *
     * Si el slug no existe, 404 — mejor que devolver el catálogo entero y hacer
     * creer al visitante que esa categoría está vacía.
     */
    public function shopPorCategoria(Request $request, string $slug, string $categoria)
    {
        $project = $this->project($slug);

        $cat = \App\Models\Category::where('project_id', $project->id)
            ->where('slug', $categoria)
            ->first();

        if (! $cat) {
            abort(404);
        }

        $request->merge(['category' => (string) $cat->id]);
        $request->attributes->set('url_ya_canonica', true);

        return $this->shop($request, $slug);
    }

    public function shop(Request $request, string $slug, ?string $profile = null)
    {
        $project = $this->project($slug);

        // ═══ 301 de la forma antigua a la legible ═══
        // /tienda?category=394 → /tienda/c/computadoras, para que los enlaces ya
        // compartidos no queden en una URL de segunda y el buscador indexe una
        // sola dirección por categoría.
        //
        // Con tres guardas, porque este método sirve tanto páginas como datos:
        //  1. Nunca en peticiones JSON: el catálogo pagina con `?format=json`
        //     y una redirección ahí rompería el scroll infinito.
        //  2. Solo si `category` es el ÚNICO parámetro: con filtros de precio,
        //     orden o página, redirigir perdería lo que el visitante eligió.
        //  3. Solo si la categoría existe y tiene slug.
        // `shopPorCategoria()` marca la petición: ya viene de la URL legible y
        // le inyectó `category`, así que redirigirla la mandaría a sí misma.
        if ($request->isMethod('GET')
            && $profile === null
            && ! $request->attributes->get('url_ya_canonica')
            && $request->query('format') !== 'json'
            && ! $request->expectsJson()
            && array_keys($request->query()) === ['category']
            && ctype_digit((string) $request->query('category'))
        ) {
            $cat = \App\Models\Category::where('project_id', $project->id)
                ->where('id', (int) $request->query('category'))
                ->first();

            if ($cat && filled($cat->slug)) {
                return redirect(\App\Support\StorefrontNavigation::categoryUrl($project, $cat), 301);
            }
        }

        // Perfil de catálogo activo (si la funcionalidad está habilitada y el slug
        // corresponde a un perfil habilitado de esta tienda). Es null en el flujo normal.
        // ═══ Un solo segmento para perfiles y categorías ═══
        // /tienda/nino (perfil) y /tienda/discos-y-memorias (categoría) comparten
        // sitio. Antes las categorías llevaban un prefijo `c/` para no chocar,
        // pero esa letra suelta no significa nada para quien lee la URL.
        // Se resuelve por orden: primero perfil, luego categoría. Si el nombre
        // coincidiera, manda el perfil — es la pieza con identidad propia.
        if ($profile !== null && ! $request->attributes->get('url_ya_canonica')) {
            $esPerfil = $this->catalogProfilesEnabled($project)
                && $project->catalogProfiles()->where('slug', $profile)->where('is_enabled', true)->exists();

            if (! $esPerfil) {
                $cat = \App\Models\Category::where('project_id', $project->id)
                    ->where('slug', $profile)->first();

                if ($cat) {
                    $request->merge(['category' => (string) $cat->id]);
                    $request->attributes->set('url_ya_canonica', true);
                    $profile = null;
                }
            }
        }

        $activeProfile = $this->resolveActiveProfile($project, $profile);

        // Plantillas de producción (computienda, etc.): su /tienda usa la MISMA
        // plantilla que el inicio, pero en modo "tienda" (catálogo con filtros).
        if ($this->productionTemplateView($project)) {
            // Fase 2A: catálogo paginado desde DB. Respuesta JSON para AJAX.
            $catalog = app(\App\Storefront\CatalogQueryService::class);
            if ($request->query('format') === 'json') {
                $page = $catalog->paginate($project, $request, $activeProfile);
                return response()->json([
                    'products' => collect($page->items())->map(fn ($p) => $catalog->toCard($p, $project->slug))->all(),
                    'current_page' => $page->currentPage(),
                    'last_page' => $page->lastPage(),
                    'total' => $page->total(),
                    'has_more' => $page->hasMorePages(),
                    'next_page_url' => $page->nextPageUrl(),
                    'profile' => $activeProfile?->slug,
                ]);
            }
            [$view, $data] = $this->prepararCatalogo($project, false, 'tienda');
            $catalogPage = $catalog->paginate($project, $request, $activeProfile);
            $data['catalogPage'] = $catalogPage;
            $data['catalogCards'] = collect($catalogPage->items())->map(fn ($p) => $catalog->toCard($p, $project->slug))->all();
            $data['catalogMaxPrice'] = (float) $project->products()->where('is_available', true)->max('price');
            $data['activeProfile'] = $activeProfile;
            $data['catalogProfiles'] = $this->menuProfiles($project);
            // La identidad efectiva del perfil (overrides sobre la identidad global)
            // la aplica la propia plantilla, que re-lee settings frescos del proyecto.
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

        // La vista previa del Constructor debe reflejar lo que el usuario ya
        // eligió/guardó (borrador), aunque todavía no haya publicado — igual
        // que el checklist. La tienda pública real ($preview=false) sigue
        // mostrando exclusivamente lo publicado.
        if ($preview) {
            $settings = array_merge($settings, $this->draftSettingsOverlay($project));
        }

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
        $hasSectionRegistry = $sections->isNotEmpty()
            || StoreSection::where('project_id', $project->id)->where('page', 'home')->exists();
        return compact('project', 'settings', 'sections', 'popup', 'storeMenu', 'headerSettings', 'storefrontTheme', 'previewMode', 'hasSectionRegistry');
    }

    /**
     * Prepara TODOS los datos que necesitan las plantillas de tienda
     * (categorías con productos, secciones, settings de diseño) y decide
     * qué plantilla usar. Reutilizable: la tienda pública y el catálogo del
     * revendedor comparten exactamente la misma vista y datos.
     *
     * @return array{0:string,1:array}  [nombre de la vista, datos]
     */
    public function prepararCatalogo(\App\Models\Project $project, bool $preview = false, string $storeView = 'home', array $settingsOverlay = []): array
    {
        // Cargar settings del proyecto como arreglo clave=>valor
        $settingsCollection = $project->settings()->pluck('value', 'key');
        $settings = $settingsCollection->toArray();
        // Constructor (B1): el preview del borrador superpone los settings en
        // borrador SIN tocar el camino público (overlay vacío = comportamiento actual).
        if ($settingsOverlay !== []) {
            $settings = array_merge($settings, $settingsOverlay);
        }

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
        // Con filas de secciones (aunque estén desactivadas) se respeta lo publicado;
        // el fallback "mostrar todo" queda solo para tiendas sin configurar.
        $hasSectionRegistry = $sections->isNotEmpty()
            || \App\Models\StoreSection::where('project_id', $project->id)->where('page', 'home')->exists();
        $aboutPage = \App\Models\StorePage::where('project_id', $project->id)->where('key', 'nosotros')->where('is_enabled', true)->first();

        // Si el proyecto tiene una plantilla activa, fusionar defaults de la plantilla
        $projectTemplate = \App\Models\ProjectTemplate::where('project_id', $project->id)->where('is_active', true)->first();
        if ($projectTemplate && is_array($projectTemplate->settings)) {
            // Template settings actúan como valores por defecto; los settings del proyecto sobrescriben.
            $settings = array_merge($projectTemplate->settings, $settings);
        }

        // Cargar árbol: padres con hijos, cada nodo con sus productos y servicios.
        // El filtro de precio replica la regla de CatalogQueryService: un producto
        // sin precio no se puede comprar y la rejilla ya lo excluía. Aquí no se
        // aplicaba, así que los contadores del filtro lateral y las secciones de
        // portada contaban productos que la tienda nunca llegaba a mostrar: se
        // leía "26 productos" junto a un filtro que decía "57".
        $vendible = fn($q) => $q->where('is_available', true)->where('price', '>', 0);
        $categories = $project->categories()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with([
                'products' => fn($q) => $vendible($q)->with('mainImage')->orderBy('sort_order'),
                'services' => fn($q) => $q->where('is_available', true)->orderBy('sort_order'),
                'children' => fn($q) => $q->where('is_active', true)->with([
                    'products' => fn($q2) => $vendible($q2)->with('mainImage')->orderBy('sort_order'),
                    'services' => fn($q2) => $q2->where('is_available', true)->orderBy('sort_order'),
                ])->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')->get();

        // Productos para secciones de la tienda. take(24) = tope del límite
        // configurable de la sección de productos del constructor.
        $newArrivals = $vendible($project->products())
            ->with(['mainImage','category'])->latest()->take(24)->get();
        $onSale = $vendible($project->products())
            ->whereNotNull('compare_price')->whereColumn('compare_price', '>', 'price')
            ->with(['mainImage','category'])->take(24)->get();
        $featured = $vendible($project->products())
            ->with(['mainImage','category'])->inRandomOrder()->take(24)->get();

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

        $data = compact('project','categories','settings','newArrivals','onSale','featured','productRatings','popup','sections','aboutPage','storeMenu','storeView','hasSectionRegistry');
        // Los perfiles de catálogo (Niño/Niña, etc.) viven en la navegación compartida:
        // deben verse también en el Inicio y demás vistas, no solo en la Tienda.
        $data['catalogProfiles'] = $this->menuProfiles($project);

        return [$view, $data];
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

    /** Comprobante de pago (voucher Yape/transferencia) subido por el cliente antes de enviar el pedido. */
    public function uploadOrderProof(Request $request, string $slug)
    {
        $project = $this->project($slug);
        $request->validate(['proof' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096']);
        $path = $request->file('proof')->store("order-proofs/{$project->id}", 'public');

        return response()->json(['ok' => true, 'url' => asset('storage/' . $path)]);
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
            // Se sigue ACEPTANDO para no romper a los clientes que ya lo
            // envian, pero es informativo: el precio real sale del catalogo.
            'items.*.price'      => 'nullable|numeric|min:0',
            'items.*.quantity'   => 'required|integer|min:1',
            'payment_method'   => 'nullable|string|max:80',
            'payment_reference'=> 'nullable|string|max:100',
            'payment_proof'    => 'nullable|url|max:500',
        ]);

        return DB::transaction(function () use ($project, $data) {
            // Verificar stock con lock pesimista. Se agrupa por producto ANTES de
            // validar: un mismo producto puede venir en varias líneas del carrito y
            // validar cada línea por separado dejaría pasar la suma (2 líneas de 4
            // contra un stock de 5 pasarían las dos y el stock quedaría en -3).
            $pedidoPorProducto = [];
            foreach ($data['items'] as $item) {
                if (empty($item['product_id'])) continue;
                $pid = (int) $item['product_id'];
                $pedidoPorProducto[$pid] = ($pedidoPorProducto[$pid] ?? 0) + (int) ($item['quantity'] ?? 1);
            }

            foreach ($pedidoPorProducto as $pid => $qtyTotal) {
                $product = Product::allProjects()
                    ->where('id', $pid)
                    ->where('project_id', $project->id)
                    ->lockForUpdate()
                    ->first();

                if (!$product) continue;

                // Solo se VALIDA aquí (con el lock ya tomado). El descuento se hace
                // después de crear el pedido, para que el Kardex pueda apuntar a él.
                if ($product->stock !== null && $product->stock < $qtyTotal) {
                    return response()->json([
                        'ok'      => false,
                        'message' => "Stock insuficiente para \"{$product->name}\" (disponible: {$product->stock}).",
                    ], 422);
                }
            }

            // ── El precio lo pone el CATALOGO, nunca el comprador ────────────
            // Esta ruta es publica y aceptaba `items.*.price` del cuerpo de la
            // peticion: bastaba con enviar {product_id:7, name:"Laptop",
            // price:0.01} para comprar a un centimo, porque solo se recurria a
            // la base `if ($pid && (!$name || !$price))`. El stock se descontaba
            // de verdad y el pedido entraba a Cuentas por Cobrar como bueno.
            //
            // Ahora cada linea se resuelve contra el catalogo del proyecto: por
            // id, y si no lo trae, por nombre exacto (hay pedidos reales asi).
            // Lo que no se puede verificar, no se vende.
            $resueltos = [];
            foreach ($data['items'] as $idx => $item) {
                $prod = null;
                if (! empty($item['product_id'])) {
                    $prod = Product::allProjects()->where('project_id', $project->id)
                        ->where('id', $item['product_id'])->first();
                }
                if (! $prod && ! empty($item['name'])) {
                    $prod = Product::allProjects()->where('project_id', $project->id)
                        ->where('name', $item['name'])->first();
                }
                if (! $prod) {
                    return response()->json([
                        'ok'      => false,
                        'message' => 'No pudimos verificar uno de los productos de tu pedido. '
                                   . 'Actualiza la página y vuelve a intentarlo.',
                    ], 422);
                }
                $resueltos[$idx] = $prod;
            }

            // Suma en CENTAVOS con el precio del catalogo (LineMath), no con
            // flotantes sobre datos del cliente.
            $subtotalCents = 0;
            foreach ($data['items'] as $idx => $item) {
                $subtotalCents += \App\Support\LineMath::lineCents(
                    \App\Support\LineMath::canon((string) $resueltos[$idx]->price),
                    (int) $item['quantity']
                );
            }
            $subtotal   = (float) \App\Support\LineMath::format($subtotalCents);
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

            foreach ($data['items'] as $idx => $item) {
                // Producto ya resuelto arriba contra el catalogo: nombre, precio
                // e id salen de la base, no de lo que mando el navegador. De
                // paso queda enlazado el `product_id` aunque el carrito no lo
                // trajera, que era como se perdia la trazabilidad al Kardex.
                $prod  = $resueltos[$idx];
                $pid   = $prod->id;
                $order->items()->create([
                    'product_id' => $pid,
                    'name'       => $prod->name,
                    'price'      => $prod->price,
                    'quantity'   => $item['quantity'],
                ]);

                // Descuento de stock vía Kardex, ya con el pedido creado para referenciarlo.
                if ($pid) {
                    $prodStock = Product::allProjects()->where('id', $pid)->where('project_id', $project->id)->first();
                    if ($prodStock) {
                        \App\Support\InventoryLedger::registrar(
                            $prodStock, -abs((int) $item['quantity']), 'venta',
                            null, 'Venta en tienda online', 'order', $order->id, null
                        );
                    }
                }
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
        // Relacionados en tres pasos para que la sección nunca quede vacía:
        // misma categoría → categorías hermanas (mismo padre) → resto de la
        // tienda. Antes, un producto único en su categoría no mostraba ninguno.
        $related = $project->products()
            ->where('is_available', true)
            ->where('price', '>', 0)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with('mainImage')
            ->take(6)->get();

        if ($related->count() < 4) {
            $padre = $product->category?->parent_id ?: $product->category_id;
            $hermanas = $project->categories()
                ->where(fn ($q) => $q->where('parent_id', $padre)->orWhere('id', $padre))
                ->pluck('id');

            $extra = $project->products()
                ->where('is_available', true)
                ->where('price', '>', 0)
                ->whereIn('category_id', $hermanas)
                ->whereNotIn('id', $related->pluck('id')->push($product->id))
                ->with('mainImage')
                ->take(6 - $related->count())->get();

            $related = $related->concat($extra);
        }

        if ($related->count() < 4) {
            $extra = $project->products()
                ->where('is_available', true)
                ->where('price', '>', 0)
                ->whereNotIn('id', $related->pluck('id')->push($product->id))
                ->with('mainImage')
                ->latest()
                ->take(6 - $related->count())->get();

            $related = $related->concat($extra);
        }
        $categories = $project->categories()->where('is_active', true)
            ->with(['products' => fn($q) => $q->where('is_available', true)->with('mainImage')])
            ->orderBy('sort_order')->get();
        $reviews    = $product->approvedReviews()->get();
        $avgRating  = $reviews->count() ? round($reviews->avg('rating'), 1) : null;

        // Plantillas que integran la página de producto dentro de su propia
        // plantilla (mismo header, menú, carrito y footer), en modo "producto".
        // OJO: sólo las que implementan el bloque storeView==='producto'.
        // ecommerce maneja el producto como SPA interna (page==='product') y NO
        // debe entrar aquí, o rompe su estado.
        $template = (string) $project->setting('catalog_template', 'default');
        if (in_array($template, self::PRODUCT_VIEW_TEMPLATES, true) && $this->productionTemplateView($project)) {
            [$tplView, $data] = $this->prepararCatalogo($project, false, 'producto');
            $data['storeProduct'] = $product;
            $data['relatedProducts'] = $related;
            $data['productReviews'] = $reviews;
            $data['productAvgRating'] = $avgRating;
            return view($tplView, $data);
        }

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
            $xml .= '<loc>'.e($baseUrl.'/producto/'.\App\Support\ImageVariants::claveProducto($p->id, $p->name)).'</loc>';
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
