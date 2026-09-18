<?php

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ventas\Models\Quote;

use App\Modules\Ventas\Models\Order;
use App\Modules\Catalogo\Models\Product;
use App\Models\Project;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = $this->proyectoActivo();
        $products = $project->products()
            ->with(['images' => fn($q) => $q->where('is_main', true), 'marca:id,label'])
            ->orderBy('name')
            ->get();

        $services = $project->services()
            ->where('is_available', true)
            ->orderBy('name')
            ->get();

        $categories = $project->categories()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $paymentMethods = $this->catValues($project, 'payment_method');

        // Precios propios del revendedor logueado (si los tiene): el POS arranca con SU precio.
        $misPrecios = \App\Modules\Ventas\Models\ResellerPrice::where('project_id', $project->id)
            ->where('user_id', auth()->id())
            ->pluck('price', 'product_id');

        // Transactions today
        $transactions = $project->orders()
            ->where('sales_channel', 'pos')
            ->whereDate('created_at', today())
            ->latest()
            ->limit(50)
            ->get();

        $productsJs = $products->map(fn($p) => [
            'id'        => $p->id,
            'type'      => 'product',
            'name'      => $p->name,
            'price'     => (float) $p->price,
            // Si el revendedor puso su precio, ese es el que ve; si no, el sugerido.
            'suggested' => (float) ($misPrecios[$p->id] ?? $p->price_suggested ?? $p->price),
            'min'       => $p->price_min !== null ? (float) $p->price_min : null,
            'max'       => $p->price_max !== null ? (float) $p->price_max : null,
            'cost'      => $p->cost !== null ? (float) $p->cost : null,
            'cat_id'    => $p->category_id,
            'brand'    => (string) ($p->marca?->label ?? ''),
            'sku'    => (string) ($p->sku ?? ''),
            'stock'     => $p->stock,
            'image'     => $p->images->first() ? $this->resolveImageUrl($p->images->first()->url) : null,
        ])->values();

        $servicesJs = $services->map(fn($s) => [
            'id'     => $s->id,
            'type'   => 'service',
            'name'   => $s->name,
            'price'  => (float) $s->price,
            'cat_id' => $s->category_id,
            'image'  => null,
            'duration_min' => $s->duration_min,
        ])->values();

        $categoriesJs = $categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'parent_id' => $c->parent_id])->values();

        $transactionsJs = $transactions->map(fn($t) => [
            'id'             => $t->id,
            'client_name'    => $t->client_name,
            'payment_method' => $t->payment_method ?? '—',
            'total'          => $t->total,
            'created_at'     => $t->created_at->format('H:i'),
        ])->values();

        /* La vista tiene que cobrar contra la ruta de SU cara. Escrita a mano
           dentro del Blade, el POS de Ventas posteaba a `/pos` (la del panel),
           y esa ruta resuelve el negocio por `active_project_id` en vez de por
           `comercial_project_id`: con Configuracion abierta en otro negocio la
           venta se registraba ALLI, y sin sesion de panel daba 500. */
        $posStoreRoute = route('pos.store');
        $posQuoteRoute = route('pos.quote');

        return view('ventas::pos.index', compact('project', 'paymentMethods', 'productsJs', 'servicesJs', 'categoriesJs', 'transactionsJs', 'posStoreRoute', 'posQuoteRoute'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = $this->proyectoActivo();

        /* DOBLE COBRO. Con el cliente delante y la red lenta, el cajero toca
           "Cobrar" otra vez, o recarga: salian DOS ventas y el stock se
           descontaba dos veces. La huella la manda el navegador; el segundo
           envio recibe la venta ya creada en vez de crear otra. */
        $huella = trim((string) $request->header('X-Idempotencia'));
        $candado = null;
        if ($huella !== '') {
            $candado = 'pos:'.$project->id.':'.substr(preg_replace('/[^A-Za-z0-9\-]/', '', $huella), 0, 64);

            if ($yaCobrada = \Illuminate\Support\Facades\Cache::get($candado)) {
                $previa = Order::where('project_id', $project->id)->with('items')->find($yaCobrada);
                if ($previa) {
                    return response()->json([
                        'ok' => true, 'repetida' => true,
                        'order' => $previa, 'total' => $previa->total, 'stock_update' => [],
                    ]);
                }
            }
            // `add` es atomico: si otra peticion ya lo puso, esta no entra.
            if (! \Illuminate\Support\Facades\Cache::add($candado.':curso', 1, 30)) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Esa venta ya se está cobrando. Espera un momento.',
                ], 409);
            }
            // Se suelta pase lo que pase: si el cobro falla, el cajero tiene
            // que poder reintentar en el acto, no esperar 30 segundos a ciegas.
            app()->terminating(fn () => \Illuminate\Support\Facades\Cache::forget($candado.':curso'));
        }

        $data = $request->validate([
            'client_name'    => 'nullable|string|max:100',
            'client_phone'   => 'nullable|string|max:30',
            'client_id'      => 'nullable|integer',
            'paid'           => 'nullable|boolean',
            'payment_method' => 'required|string|max:80',
            'notes'          => 'nullable|string',
            'table_number'   => 'nullable|string|max:10',
            'order_type'     => 'nullable|string|max:20',
            'delivery_type'    => 'nullable|in:recojo,delivery',
            'delivery_address' => 'nullable|string|max:255',
            'promised_at'      => 'nullable|date',
            'advance_amount'   => 'nullable|numeric|min:0',
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer',
            'items.*.service_id' => 'nullable|integer',
            'items.*.name'       => 'required|string',
            'items.*.price'      => 'required|numeric|min:0',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        // Seguridad: ningún ítem puede venderse por debajo de su precio mínimo,
        // y solo puede descontar del precio de catálogo quien tenga permiso
        // orders.descuento (regla "cambiar precio o aplicar descuento con permiso").
        // can() en vez de hasPermissionTo(): este ultimo lanza excepcion si el
        // permiso no esta sembrado y tumbaba la venta con un 500.
        $canDiscount = auth()->user()?->is_superadmin || $project->owner_id === auth()->id() || (bool) auth()->user()?->can('orders.descuento');

        // Precio con el que el POS carga cada producto: el propio del revendedor
        // si lo tiene, si no el sugerido. Comparar contra products.price daba 403
        // en ventas normales, porque el sugerido casi siempre es MENOR que el de
        // catalogo y el vendedor no habia tocado nada.
        $misPrecios = \App\Modules\Ventas\Models\ResellerPrice::where('project_id', $project->id)
            ->where('user_id', auth()->id())
            ->pluck('price', 'product_id');

        $faltantes = [];
        foreach ($data['items'] as $it) {
            if (!empty($it['product_id'])) {
                $prod = Product::where('project_id', $project->id)->where('id', $it['product_id'])
                    ->first(['id', 'name', 'price', 'price_min', 'price_suggested', 'stock']);
                if (!$prod) continue;

                if ($prod->price_min !== null && (float) $it['price'] < (float) $prod->price_min - 0.001) {
                    return response()->json([
                        'ok' => false,
                        'error' => "El producto \"{$it['name']}\" no puede venderse por debajo de S/ " . number_format($prod->price_min, 2) . '.',
                    ], 422);
                }

                $precioBase = (float) ($misPrecios[$prod->id] ?? $prod->price_suggested ?? $prod->price);
                if (!$canDiscount && (float) $it['price'] < $precioBase - 0.001) {
                    return response()->json([
                        'ok' => false,
                        'error' => "No tienes permiso para aplicar descuento en \"{$it['name']}\".",
                    ], 403);
                }

                // El mostrador no validaba stock: se podian vender 10 unidades
                // habiendo 2 y el saldo quedaba en negativo sin avisar a nadie.
                if ($prod->stock !== null) {
                    $pedido = ($faltantes[$prod->id]['pedido'] ?? 0) + (int) $it['quantity'];
                    $faltantes[$prod->id] = ['nombre' => $prod->name, 'stock' => (int) $prod->stock, 'pedido' => $pedido];
                }
            }
        }

        // Se valida el TOTAL por producto: el mismo articulo puede venir en varias
        // lineas del ticket y linea por linea la suma se colaria.
        foreach ($faltantes as $f) {
            if ($f['pedido'] > $f['stock']) {
                return response()->json([
                    'ok' => false,
                    'error' => "Stock insuficiente para \"{$f['nombre']}\": quedan {$f['stock']} y estás vendiendo {$f['pedido']}.",
                ], 422);
            }
        }

        $total       = collect($data['items'])->sum(fn($i) => $i['price'] * $i['quantity']);
        $hasMesa     = !empty($data['table_number']);

        $order = $project->orders()->create([
            'client_name'    => $data['client_name'] ?? ($hasMesa ? 'Mesa ' . $data['table_number'] : 'Cliente mostrador'),
            'client_phone'   => $data['client_phone'] ?? null,
            'client_id'      => $data['client_id'] ?? null,
            'created_by'     => auth()->id(),
            'payment_status' => !empty($data['paid']) ? 'paid' : 'pending',
            'payment_method' => $data['payment_method'],
            'sales_channel'  => 'pos',
            'status'         => $hasMesa ? 'process' : 'done',
            // Sin mesa no hay flujo de cocina → 'done' (la columna es NOT NULL en producción).
            'kitchen_status' => $hasMesa ? 'pending' : 'done',
            'table_number'   => $data['table_number'] ?? null,
            'order_type'     => $data['order_type'] ?? 'mostrador',
            'delivery_type'    => $data['delivery_type'] ?? null,
            'delivery_address' => $data['delivery_address'] ?? null,
            'promised_at'      => $data['promised_at'] ?? null,
            'advance_amount'   => $data['advance_amount'] ?? null,
            'document_status'  => 'pending',
            'notes'          => $data['notes'] ?? null,
            'total'          => $total,
        ]);

        // F3c: el cobro del mostrador entra al LIBRO. La venta ya nace con su
        // `payment_status`, pero sin asiento ese dinero no seria enumerable ni
        // conciliable: una venta pagada en efectivo tiene que dejar su rastro
        // igual que una aprobada por el bot.
        $cobradoCents = 0;
        if (! empty($data['paid'])) {
            $cobradoCents = \App\Support\LineMath::toCents(\App\Support\LineMath::canon((string) $total));
        } elseif (! empty($data['advance_amount']) && (float) $data['advance_amount'] > 0) {
            $cobradoCents = \App\Support\LineMath::toCents(
                \App\Support\LineMath::canon(number_format((float) $data['advance_amount'], 2, '.', ''))
            );
        }
        if ($cobradoCents > 0) {
            // La columna ya la escribio create(); se limpia para que el libro
            // sea la unica fuente y la proyeccion no sume dos veces lo mismo.
            $order->forceFill(['advance_amount' => null])->save();
            \App\Modules\Finanzas\Support\Ledger::registrar($project, $order, $cobradoCents,
                $data['payment_method'] ?? null, null, 'pos');
        }

        // CRM automático: venta pagada con cliente identificado → etapa "ganado".
        if (!empty($data['paid']) && !empty($data['client_id'])) {
            \App\Modules\Crm\Models\Client::allProjects()->where('project_id', $project->id)
                ->where('id', $data['client_id'])
                ->update(['etapa' => 'ganado', 'ultima_actividad' => now()]);
        }

        foreach ($data['items'] as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'service_id' => $item['service_id'] ?? null,
                'name'       => $item['name'],
                'price'      => $item['price'],
                'quantity'   => $item['quantity'],
            ]);

            // El descuento pasa por el Kardex: así la venta queda registrada en el
            // historial del producto y el saldo siempre cuadra con la existencia.
            if (!empty($item['product_id'])) {
                $prod = Product::where('id', $item['product_id'])->where('project_id', $project->id)->first();
                if ($prod) {
                    \App\Modules\Inventario\Support\InventoryLedger::registrar(
                        $prod, -abs((int) $item['quantity']), 'venta',
                        null, 'Venta en punto de venta', 'order', $order->id
                    );
                }
            }
        }

        // Recalcular stock actualizado para devolver al frontend
        $updatedStock = collect($data['items'])
            ->filter(fn($i) => !empty($i['product_id']))
            ->map(fn($i) => [
                'product_id' => $i['product_id'],
                'stock'      => Product::where('id', $i['product_id'])->value('stock'),
            ])->values();

        // Con la venta ya creada, el reintento devuelve esta misma.
        if ($candado) {
            \Illuminate\Support\Facades\Cache::put($candado, $order->id, 30);
            \Illuminate\Support\Facades\Cache::forget($candado.':curso');
        }

        return response()->json([
            'ok'           => true,
            'order'        => $order->load('items'),
            'total'        => $total,
            'stock_update' => $updatedStock,
        ]);
    }

    // ── Portal Comercial ─────────────────────────────────────────────────────

    public function indexComercial()
    {
        /** @var \App\Models\Project $project */
        $project        = app('active_project');
        $products       = $project->products()->with(['images' => fn($q) => $q->where('is_main', true), 'marca:id,label'])->orderBy('name')->get();
        $services       = $project->services()->where('is_available', true)->orderBy('name')->get();
        $categories     = $project->categories()->where('is_active', true)->orderBy('sort_order')->get();
        $paymentMethods = $this->catValues($project, 'payment_method');
        $transactions   = $project->orders()->where('sales_channel', 'pos')->whereDate('created_at', today())->latest()->limit(50)->get();

        $productsJs     = $products->map(fn($p) => ['id' => $p->id, 'type' => 'product', 'name' => $p->name, 'price' => (float) $p->price, 'suggested' => (float) ($p->price_suggested ?? $p->price), 'min' => $p->price_min !== null ? (float) $p->price_min : null, 'max' => $p->price_max !== null ? (float) $p->price_max : null, 'cost' => $p->cost !== null ? (float) $p->cost : null, 'cat_id' => $p->category_id,
            'brand' => (string) ($p->marca?->label ?? ''),
            'sku' => (string) ($p->sku ?? ''), 'stock' => $p->stock, 'image' => $p->images->first() ? $this->resolveImageUrl($p->images->first()->url) : null])->values();
        $servicesJs     = $services->map(fn($s) => ['id' => $s->id, 'type' => 'service', 'name' => $s->name, 'price' => (float) $s->price, 'cat_id' => $s->category_id, 'image' => null, 'duration_min' => $s->duration_min])->values();
        $categoriesJs   = $categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'parent_id' => $c->parent_id])->values();
        $transactionsJs = $transactions->map(fn($t) => ['id' => $t->id, 'client_name' => $t->client_name, 'payment_method' => $t->payment_method ?? '—', 'total' => $t->total, 'created_at' => $t->created_at->format('H:i')])->values();

        $posStoreRoute  = route('bixosales.pos.store');
        $posQuoteRoute  = route('bixosales.pos.quote');
        $portalLayout   = 'comercial';
        return view('ventas::pos.index', compact('project', 'paymentMethods', 'productsJs', 'servicesJs', 'categoriesJs', 'transactionsJs', 'posStoreRoute', 'posQuoteRoute', 'portalLayout'));
    }

    // ── Portal Facturación ────────────────────────────────────────────────────

    public function indexPortal(string $slug)
    {
        $project        = Project::where('slug', $slug)->firstOrFail();
        $products       = $project->products()->with(['images' => fn($q) => $q->where('is_main', true), 'marca:id,label'])->orderBy('name')->get();
        $services       = $project->services()->where('is_available', true)->orderBy('name')->get();
        $categories     = $project->categories()->where('is_active', true)->orderBy('sort_order')->get();
        $paymentMethods = $this->catValues($project, 'payment_method');
        $transactions   = $project->orders()->where('sales_channel', 'pos')->whereDate('created_at', today())->latest()->limit(50)->get();

        $productsJs     = $products->map(fn($p) => ['id' => $p->id, 'type' => 'product', 'name' => $p->name, 'price' => (float) $p->price, 'suggested' => (float) ($p->price_suggested ?? $p->price), 'min' => $p->price_min !== null ? (float) $p->price_min : null, 'max' => $p->price_max !== null ? (float) $p->price_max : null, 'cost' => $p->cost !== null ? (float) $p->cost : null, 'cat_id' => $p->category_id,
            'brand' => (string) ($p->marca?->label ?? ''),
            'sku' => (string) ($p->sku ?? ''), 'stock' => $p->stock, 'image' => $p->images->first() ? $this->resolveImageUrl($p->images->first()->url) : null])->values();
        $servicesJs     = $services->map(fn($s) => ['id' => $s->id, 'type' => 'service', 'name' => $s->name, 'price' => (float) $s->price, 'cat_id' => $s->category_id, 'image' => null, 'duration_min' => $s->duration_min])->values();
        $categoriesJs   = $categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'parent_id' => $c->parent_id])->values();
        $transactionsJs = $transactions->map(fn($t) => ['id' => $t->id, 'client_name' => $t->client_name, 'payment_method' => $t->payment_method ?? '—', 'total' => $t->total, 'created_at' => $t->created_at->format('H:i')])->values();

        $posStoreRoute = route('facturacion.pos.store', $slug);
        // Esta cara no expone `cotizar`: la vista oculta el boton si viene vacia.
        $posQuoteRoute = null;
        return view('ventas::pos.index', compact('project', 'paymentMethods', 'productsJs', 'servicesJs', 'categoriesJs', 'transactionsJs', 'posStoreRoute', 'posQuoteRoute'));
    }

    public function storePortal(\Illuminate\Http\Request $request, string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();
        app()->instance('active_project', $project);
        return $this->store($request);
    }

    /**
     * Cotización rápida desde el POS: crea la Quote con precios (editables) y
     * devuelve el enlace público listo para enviar al cliente. Sin cobro.
     */
    public function quote(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = $this->proyectoActivo();
        $data = $request->validate([
            'client_name'  => 'nullable|string|max:100',
            'client_phone' => 'nullable|string|max:30',
            'notes'        => 'nullable|string',
            'items'        => 'required|array|min:1',
            'items.*.name'     => 'required|string',
            'items.*.price'    => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $total = collect($data['items'])->sum(fn ($i) => $i['price'] * $i['quantity']);

        $quote = $project->quotes()->create([
            'client_id'   => $request->input('client_id'),
            'client_name' => $data['client_name'] ?: 'Cliente mostrador',
            'client_phone'=> $data['client_phone'] ?? null,
            'notes'       => $data['notes'] ?? null,
            'valid_until' => now()->addDays(15),
            'total'       => $total,
            'status'      => 'sent',
            'token'       => \Illuminate\Support\Str::random(48),
            'sent_at'     => now(),
        ]);

        // CRM automático: cotización creada con cliente → etapa "propuesta".
        if ($request->filled('client_id')) {
            \App\Modules\Crm\Models\Client::allProjects()->where('project_id', $project->id)
                ->where('id', $request->input('client_id'))
                ->update(['etapa' => 'propuesta', 'ultima_actividad' => now()]);
        }

        foreach ($data['items'] as $item) {
            $quote->items()->create([
                'description' => $item['name'],
                'price'       => $item['price'],
                'quantity'    => $item['quantity'],
            ]);
        }

        // La confirmacion del mostrador enseña lo que se acaba de crear, y eso
        // tiene que decirlo el servidor: si la pintara el navegador con lo que
        // cree recordar del carrito, el vendedor podria enviar al cliente un
        // documento distinto del guardado. Numero, cliente, vigencia, estado y
        // enlaces salen todos de la fila recien escrita.
        $comercial = $request->routeIs('bixosales.*');
        $usuario   = auth()->user();
        $puedeVer  = $project->hasModule('quotes') && (
            $usuario?->is_superadmin
            || $project->owner_id === $usuario?->id
            || $usuario?->can('quotes.ver')
            || $usuario?->can('view-quotes')
        );

        $estado = \App\Modules\Ventas\Support\QuoteStatus::comercialPresentacion($quote->status);

        return response()->json([
            'ok'            => true,
            'quote_id'      => $quote->id,
            'number'        => $quote->etiqueta,
            'client_name'   => $quote->client_name,
            'items_count'   => count($data['items']),
            'created_ts'    => $quote->created_at->timestamp,
            'issued_at'     => $quote->created_at->format('d/m/Y'),
            'valid_until'   => self::fechaCorta($quote->valid_until),
            'status'        => \App\Modules\Ventas\Support\QuoteStatus::comercial($quote->status),
            'status_label'  => $estado['label'],
            // Cotizaciones no tiene columna de moneda todavia; se declara aqui
            // para que la vista no la de por supuesta y el dia que exista se
            // cambie en un solo sitio.
            'currency'      => 'S/',
            'total'         => $total,
            'url'           => url('/b/' . $project->slug . '/c/' . $quote->token),
            'view_url'      => $puedeVer ? ($comercial ? route('bixosales.cotizaciones.show', $quote) : route('quotes.show', $quote)) : null,
            'pdf_url'       => $puedeVer ? ($comercial ? route('bixosales.cotizaciones.pdf', $quote) : route('quotes.pdf', $quote)) : null,
            'list_url'      => $puedeVer ? ($comercial ? route('bixosales.cotizaciones') : route('quotes')) : null,
        ]);
    }

    /** "23 ago. 2026" sin depender del locale instalado en el servidor. */
    private static function fechaCorta($fecha): ?string
    {
        if (!$fecha) {
            return null;
        }
        $meses = ['ene.', 'feb.', 'mar.', 'abr.', 'may.', 'jun.', 'jul.', 'ago.', 'sep.', 'oct.', 'nov.', 'dic.'];

        return $fecha->day . ' ' . $meses[$fecha->month - 1] . ' ' . $fecha->year;
    }

    /** BIXO Venta Express: productos primero, cliente opcional o precargado, cierre en un paso. */
    public function express(Request $request)
    {
        $isSales = request()->routeIs('bixosales.*');
        $project = $this->proyectoActivo();

        // "BIXO empieza con lo que ya conoce": si llegamos desde el CRM, un
        // pedido anterior, una cotización o una conversación de WhatsApp,
        // precargamos ese cliente para no volver a registrarlo.
        $preload = null;
        if ($request->filled('client_id')) {
            $c = \App\Modules\Crm\Models\Client::allProjects()->where('project_id', $project->id)->find($request->integer('client_id'));
            if ($c) $preload = ['id' => $c->id, 'name' => $c->name, 'phone' => (string) $c->phone];
        } elseif ($request->filled('from_order')) {
            $o = Order::allProjects()->where('project_id', $project->id)->find($request->integer('from_order'));
            if ($o) $preload = ['id' => $o->client_id, 'name' => $o->client_name, 'phone' => (string) $o->client_phone];
        } elseif ($request->filled('from_quote')) {
            $q = \App\Modules\Ventas\Models\Quote::allProjects()->where('project_id', $project->id)->find($request->integer('from_quote'));
            if ($q) $preload = ['id' => $q->client_id, 'name' => $q->client_name, 'phone' => (string) $q->client_phone];
        } elseif ($request->filled('phone')) {
            $normalized = preg_replace('/\D/', '', (string) $request->input('phone'));
            $c = \App\Modules\Crm\Models\Client::allProjects()->where('project_id', $project->id)
                ->where('phone', 'like', '%'.$normalized.'%')->first();
            $preload = $c ? ['id' => $c->id, 'name' => $c->name, 'phone' => (string) $c->phone] : ['id' => null, 'name' => null, 'phone' => $normalized];
        }

        $clientsLite = \App\Modules\Crm\Models\Client::allProjects()->where('project_id', $project->id)
            ->orderByDesc('updated_at')->limit(300)
            ->get(['id', 'name', 'phone', 'email'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'phone' => (string) $c->phone])->values();

        $productsLite = Product::allProjects()->where('project_id', $project->id)
            ->where('is_available', true)->with('mainImage')
            ->orderBy('name')->limit(400)->get()
            ->map(fn ($pr) => [
                'id' => $pr->id, 'name' => $pr->name, 'price' => (float) $pr->price,
                'sku' => (string) ($pr->sku ?? ''), 'barcode' => (string) ($pr->barcode ?? ''), 'stock' => $pr->stock,
                'image' => $pr->mainImage?->url ? $this->resolveImageUrl($pr->mainImage->url) : null,
            ])->values();

        // Frecuentes: los más vendidos (últimos 90 días)
        $frequentIds = \DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.project_id', $project->id)
            ->where('orders.created_at', '>=', now()->subDays(90))
            ->whereNotNull('order_items.product_id')
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as n')
            ->groupBy('order_items.product_id')->orderByDesc('n')->limit(8)->pluck('product_id');

        $paymentMethods = $this->catValues($project, 'payment_method');
        $yape = [
            'number' => preg_replace('/\D/', '', (string) $project->setting('payment_yape_number', '')),
            'name'   => (string) $project->setting('payment_yape_name', $project->name),
            'qr'     => ($q = (string) $project->setting('payment_yape_qr', '')) ? (str_starts_with($q, 'http') ? $q : asset('storage/'.$q)) : null,
        ];

        return view('ventas::ventas.express', [
            'project' => $project,
            'portalLayout' => $isSales ? 'comercial' : 'panel',
            'clientsLite' => $clientsLite,
            'productsLite' => $productsLite,
            'frequentIds' => $frequentIds,
            'paymentMethods' => $paymentMethods,
            'yape' => $yape,
            'storeUrl' => $isSales ? route('bixosales.pos.store') : route('pos.store'),
            'quoteUrl' => $isSales ? route('bixosales.pos.quote') : route('pos.quote'),
            'preload' => $preload,
            'canDiscount' => auth()->user()?->is_superadmin || $project->owner_id === auth()->id() || (bool) auth()->user()?->can('orders.descuento'),
        ]);
    }

    /**
     * Resuelve el proyecto segun el portal desde el que se llama.
     *
     * El portal comercial tiene su propia sesion (comercial_project_id). Venta
     * Express se pintaba con ese proyecto pero posteaba a un store() que leia
     * active_project: con las dos sesiones abiertas el pedido caia en el proyecto
     * equivocado, y sin sesion de panel reventaba en 500.
     */
    private function proyectoActivo(): Project
    {
        return request()->routeIs('bixosales.*')
            ? Project::findOrFail(session('comercial_project_id'))
            : app('active_project');
    }

    private function catValues(Project $project, string $type): \Illuminate\Support\Collection
    {
        $list = $project->catalogLists()->where('type', $type)->first();
        return $list ? $list->values()->where('is_active', true)->orderBy('sort_order')->pluck('label') : collect();
    }

    private function resolveImageUrl(string $url): string
    {
        return str_starts_with($url, 'http') ? $url : asset('storage/' . $url);
    }
}
