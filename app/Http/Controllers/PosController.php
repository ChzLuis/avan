<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $products = $project->products()
            ->with(['images' => fn($q) => $q->where('is_main', true)])
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
        $misPrecios = \App\Models\ResellerPrice::where('project_id', $project->id)
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

        $categoriesJs = $categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values();

        $transactionsJs = $transactions->map(fn($t) => [
            'id'             => $t->id,
            'client_name'    => $t->client_name,
            'payment_method' => $t->payment_method ?? '—',
            'total'          => $t->total,
            'created_at'     => $t->created_at->format('H:i'),
        ])->values();

        return view('pos.index', compact('project', 'paymentMethods', 'productsJs', 'servicesJs', 'categoriesJs', 'transactionsJs'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $data = $request->validate([
            'client_name'    => 'nullable|string|max:100',
            'client_phone'   => 'nullable|string|max:30',
            'payment_method' => 'required|string|max:80',
            'notes'          => 'nullable|string',
            'table_number'   => 'nullable|string|max:10',
            'order_type'     => 'nullable|string|max:20',
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer',
            'items.*.service_id' => 'nullable|integer',
            'items.*.name'       => 'required|string',
            'items.*.price'      => 'required|numeric|min:0',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        // Seguridad: ningún ítem puede venderse por debajo de su precio mínimo.
        foreach ($data['items'] as $it) {
            if (!empty($it['product_id'])) {
                $min = Product::where('project_id', $project->id)->where('id', $it['product_id'])->value('price_min');
                if ($min !== null && (float) $it['price'] < (float) $min - 0.001) {
                    return response()->json([
                        'ok' => false,
                        'error' => "El producto \"{$it['name']}\" no puede venderse por debajo de S/ " . number_format($min, 2) . '.',
                    ], 422);
                }
            }
        }

        $total       = collect($data['items'])->sum(fn($i) => $i['price'] * $i['quantity']);
        $hasMesa     = !empty($data['table_number']);

        $order = $project->orders()->create([
            'client_name'    => $data['client_name'] ?? ($hasMesa ? 'Mesa ' . $data['table_number'] : 'Cliente mostrador'),
            'client_phone'   => $data['client_phone'] ?? null,
            'payment_method' => $data['payment_method'],
            'sales_channel'  => 'pos',
            'status'         => $hasMesa ? 'process' : 'done',
            // Sin mesa no hay flujo de cocina → 'done' (la columna es NOT NULL en producción).
            'kitchen_status' => $hasMesa ? 'pending' : 'done',
            'table_number'   => $data['table_number'] ?? null,
            'order_type'     => $data['order_type'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'total'          => $total,
        ]);

        foreach ($data['items'] as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'service_id' => $item['service_id'] ?? null,
                'name'       => $item['name'],
                'price'      => $item['price'],
                'quantity'   => $item['quantity'],
            ]);

            if (!empty($item['product_id'])) {
                Product::where('id', $item['product_id'])
                    ->where('project_id', $project->id)
                    ->whereNotNull('stock')
                    ->decrement('stock', $item['quantity']);
            }
        }

        // Recalcular stock actualizado para devolver al frontend
        $updatedStock = collect($data['items'])
            ->filter(fn($i) => !empty($i['product_id']))
            ->map(fn($i) => [
                'product_id' => $i['product_id'],
                'stock'      => Product::where('id', $i['product_id'])->value('stock'),
            ])->values();

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
        $products       = $project->products()->with(['images' => fn($q) => $q->where('is_main', true)])->orderBy('name')->get();
        $services       = $project->services()->where('is_available', true)->orderBy('name')->get();
        $categories     = $project->categories()->where('is_active', true)->orderBy('sort_order')->get();
        $paymentMethods = $this->catValues($project, 'payment_method');
        $transactions   = $project->orders()->where('sales_channel', 'pos')->whereDate('created_at', today())->latest()->limit(50)->get();

        $productsJs     = $products->map(fn($p) => ['id' => $p->id, 'type' => 'product', 'name' => $p->name, 'price' => (float) $p->price, 'suggested' => (float) ($p->price_suggested ?? $p->price), 'min' => $p->price_min !== null ? (float) $p->price_min : null, 'max' => $p->price_max !== null ? (float) $p->price_max : null, 'cost' => $p->cost !== null ? (float) $p->cost : null, 'cat_id' => $p->category_id, 'stock' => $p->stock, 'image' => $p->images->first() ? $this->resolveImageUrl($p->images->first()->url) : null])->values();
        $servicesJs     = $services->map(fn($s) => ['id' => $s->id, 'type' => 'service', 'name' => $s->name, 'price' => (float) $s->price, 'cat_id' => $s->category_id, 'image' => null, 'duration_min' => $s->duration_min])->values();
        $categoriesJs   = $categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values();
        $transactionsJs = $transactions->map(fn($t) => ['id' => $t->id, 'client_name' => $t->client_name, 'payment_method' => $t->payment_method ?? '—', 'total' => $t->total, 'created_at' => $t->created_at->format('H:i')])->values();

        $posStoreRoute  = route('bixosales.pos.store');
        $portalLayout   = 'comercial';
        return view('pos.index', compact('project', 'paymentMethods', 'productsJs', 'servicesJs', 'categoriesJs', 'transactionsJs', 'posStoreRoute', 'portalLayout'));
    }

    // ── Portal Facturación ────────────────────────────────────────────────────

    public function indexPortal(string $slug)
    {
        $project        = Project::where('slug', $slug)->firstOrFail();
        $products       = $project->products()->with(['images' => fn($q) => $q->where('is_main', true)])->orderBy('name')->get();
        $services       = $project->services()->where('is_available', true)->orderBy('name')->get();
        $categories     = $project->categories()->where('is_active', true)->orderBy('sort_order')->get();
        $paymentMethods = $this->catValues($project, 'payment_method');
        $transactions   = $project->orders()->where('sales_channel', 'pos')->whereDate('created_at', today())->latest()->limit(50)->get();

        $productsJs     = $products->map(fn($p) => ['id' => $p->id, 'type' => 'product', 'name' => $p->name, 'price' => (float) $p->price, 'suggested' => (float) ($p->price_suggested ?? $p->price), 'min' => $p->price_min !== null ? (float) $p->price_min : null, 'max' => $p->price_max !== null ? (float) $p->price_max : null, 'cost' => $p->cost !== null ? (float) $p->cost : null, 'cat_id' => $p->category_id, 'stock' => $p->stock, 'image' => $p->images->first() ? $this->resolveImageUrl($p->images->first()->url) : null])->values();
        $servicesJs     = $services->map(fn($s) => ['id' => $s->id, 'type' => 'service', 'name' => $s->name, 'price' => (float) $s->price, 'cat_id' => $s->category_id, 'image' => null, 'duration_min' => $s->duration_min])->values();
        $categoriesJs   = $categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values();
        $transactionsJs = $transactions->map(fn($t) => ['id' => $t->id, 'client_name' => $t->client_name, 'payment_method' => $t->payment_method ?? '—', 'total' => $t->total, 'created_at' => $t->created_at->format('H:i')])->values();

        $posStoreRoute = route('facturacion.pos.store', $slug);
        return view('pos.index', compact('project', 'paymentMethods', 'productsJs', 'servicesJs', 'categoriesJs', 'transactionsJs', 'posStoreRoute'));
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
        $project = app('active_project');
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
            'client_name' => $data['client_name'] ?: 'Cliente mostrador',
            'client_phone'=> $data['client_phone'] ?? null,
            'notes'       => $data['notes'] ?? null,
            'valid_until' => now()->addDays(15),
            'total'       => $total,
            'status'      => 'sent',
            'token'       => \Illuminate\Support\Str::random(48),
            'sent_at'     => now(),
        ]);

        foreach ($data['items'] as $item) {
            $quote->items()->create([
                'description' => $item['name'],
                'price'       => $item['price'],
                'quantity'    => $item['quantity'],
            ]);
        }

        return response()->json([
            'ok'    => true,
            'quote_id' => $quote->id,
            'total' => $total,
            'url'   => url('/b/' . $project->slug . '/c/' . $quote->token),
        ]);
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
