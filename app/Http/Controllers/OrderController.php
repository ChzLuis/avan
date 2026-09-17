<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Order;
use App\Models\Client;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $isSales = request()->routeIs('bixosales.*');
        /** @var \App\Models\Project $project */
        $project = $isSales
            ? Project::findOrFail(session('comercial_project_id'))
            : app('active_project');
        $orders            = $project->orders()->with('client','items','autor:id,name')->latest()->limit(500)->get();

        // ── KPIs: definicion UNICA ──
        // Antes se calculaban aqui cuatro y Alpine recalculaba otros cuatro
        // distintos, que ni siquiera cuadraban entre si. Ahora solo existen
        // estos, y las vistas rapidas de la barra aplican los mismos criterios.
        //
        // "por cobrar" pasa por OrderStatus::debe(), que reconoce el valor
        // heredado 'pagado' como cobrado. Con la comparacion anterior contra
        // ['paid','refunded'] se contaban S/ 1 670,12 ya cobrados como deuda.
        // Se cuentan por SQL sobre TODOS los pedidos, no sobre los 500 que trae la
        // lista: si no, "atrasados" ignora justo los pedidos viejos atascados, que
        // son la unica razon por la que ese KPI existe.
        $cerrados = \App\Support\OrderStatus::rawsCerrados();
        $pendientes = \App\Support\OrderStatus::rawsDe('pending');
        $abiertos = fn () => $project->orders()->where(
            fn ($w) => $w->whereNull('status')->orWhereNotIn('status', $cerrados)
        );

        $kpis = [
            // 'nuevos' sustituye a 'por_cobrar' como KPI: la cobranza es de
            // Cuentas por Cobrar; aqui el pago es solo contexto de fila.
            'nuevos'     => $project->orders()->where(
                                fn ($w) => $w->whereNull('status')->orWhereIn('status', $pendientes)
                            )->count(),
            'activos'    => $abiertos()->count(),
            'atrasados'  => $abiertos()->where('updated_at', '<', now()->subHours(48))->count(),
            // Sustituye a 'En preparacion' cuando el proyecto no tiene flujo
            // operativo: ese KPI seria siempre 0 y no dice nada.
            'completados'=> $project->orders()->whereIn('status', \App\Support\OrderStatus::rawsDe('done'))->count(),
        ];
        $paymentMethods    = $this->catValues($project, 'payment_method');
        $paymentConditions = $this->catValues($project, 'payment_condition');
        $salesChannels     = $this->catValues($project, 'sales_channel');
        $portalLayout = $isSales ? 'comercial' : 'panel';

        // Capacidades resueltas una vez y compartidas por Blade y Alpine, para
        // no repetir en la vista la equivalencia A/B de los middleware.
        $puede = \App\Support\OrderAbilities::para(auth()->user());

        // Capacidad operativa EXPLICITA: el modulo 'logistics' del proyecto,
        // no la categoria. supportsFlow() devuelve true para cualquier
        // categoria no vacia ("tiene rubro" no es "usa flujo operativo").
        // El toggle ya existe en Ajustes -> Modulos; cero migraciones.
        $capacidadOperativa = $project->hasModule('logistics');
        $flujoOperativo = $capacidadOperativa && \App\Support\OrderFlow::supportsFlow($project->category ?? '')
            ? \App\Support\OrderFlow::activeStates($project)
            : [];

        // Pedido preseleccionado al entrar por /bixosales/pedidos/{order}.
        $pedidoInicial = request()->route('order')?->id;

        return view('orders.index', compact(
            'project', 'orders', 'kpis', 'paymentMethods', 'paymentConditions',
            'salesChannels', 'portalLayout', 'puede', 'flujoOperativo', 'pedidoInicial'
        ));
    }

    private function catValues(Project $project, string $type): \Illuminate\Support\Collection
    {
        $list = $project->catalogLists()->where('type', $type)->first();
        return $list ? $list->values()->where('is_active', true)->orderBy('sort_order')->pluck('label') : collect();
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = $this->proyectoActivo();
        $data = $request->validate([
            'client_name'       => 'required|string|max:100',
            'client_phone'      => 'nullable|string|max:30',
            'client_id'         => 'nullable|integer',
            'notes'             => 'nullable|string',
            'payment_method'    => 'nullable|string|max:80',
            'payment_condition' => 'nullable|string|max:80',
            'sales_channel'     => 'nullable|string|max:80',
            'items'             => 'required|array|min:1|max:200',
            'items.*.name'     => 'required|string',
            'items.*.price'    => 'required|numeric|decimal:0,2|min:0|max:99999999.99',
            'items.*.discount' => 'nullable|numeric|decimal:0,2|min:0|max:100',
            'items.*.quantity' => 'required|integer|min:1|max:10000',
        ]);

        $totalCents = \App\Support\LineMath::sumCents($data['items']);
        // orders.total es decimal(10,2) (create_orders_table real —
        // correccion Codex: mi 12,2 venia del cast, no del esquema).
        abort_if($totalCents > 9_999_999_999, 422, 'El total excede el máximo permitido (99,999,999.99).');
        $total = \App\Support\LineMath::format($totalCents);

        // Lavandería: nº total de prendas = suma de cantidades de los items
        $piecesCount = (int) collect($data['items'])->sum('quantity');

        $order = $project->orders()->create([
            'client_id'         => $data['client_id'] ?? null,
            'client_name'       => $data['client_name'],
            'client_phone'      => $data['client_phone'] ?? null,
            'notes'             => $data['notes'] ?? null,
            'payment_method'    => $data['payment_method'] ?? null,
            'payment_condition' => $data['payment_condition'] ?? null,
            'sales_channel'     => $data['sales_channel'] ?? null,
            'total'             => $total,
            'status'            => 'pending',
            'pieces_count'      => $piecesCount,
            'laundry_status'    => 'recibido',
            'laundry_status_at' => now(),
        ]);

        // Código de etiqueta legible por bolsa: LV-000123 (id acolchado)
        $order->update(['tag_code' => 'LV-' . str_pad($order->id, 6, '0', STR_PAD_LEFT)]);

        foreach ($data['items'] as $item) {
            $order->items()->create($item);
        }

        return response()->json(['order' => $order->load('items')]);
    }

    public function show(Order $order)
    {
        $isSales = request()->routeIs('bixosales.*');
        /** @var \App\Models\Project $project */
        $project = $isSales
            ? \App\Models\Project::findOrFail(session('comercial_project_id'))
            : app('active_project');
        abort_unless($order->project_id === $project->id, 403);

        // Drawer enlazable: si se entra por navegador a /pedidos/{id} hay que
        // pintar el listado completo con ese pedido abierto, no un JSON suelto.
        // Las llamadas AJAX del propio drawer siguen recibiendo JSON.
        if (! request()->expectsJson()) {
            return $this->index();
        }

        $order->load('items');
        $data = $order->toArray();
        if ($order->payment_proof) {
            $data['payment_proof'] = str_replace('http://', 'https://', asset('storage/' . $order->payment_proof));
        }

        // Los cobros viajan al abrir el pedido, no solo tras registrar uno: si
        // no, el historial solo aparece para quien acaba de cobrar.
        return response()->json([
            'order'  => $data,
            'cobros' => $this->historialDeCobros($project->id, $order->id),
        ]);
    }

    // ── Lavandería: etiqueta imprimible de la bolsa (ticket con código + QR) ───
    /** La nota de pedido en A4, sobre la familia visual de documentos. */
    public function pdf(Order $order)
    {
        $isSales = request()->routeIs('bixosales.*');
        /** @var \App\Models\Project $project */
        $project = $isSales
            ? \App\Models\Project::findOrFail(session('comercial_project_id'))
            : app('active_project');
        abort_unless($order->project_id === $project->id, 403);

        $order->load('items');

        return view('orders.pdf', compact('order', 'project'));
    }

    public function tag(Order $order)
    {
        $isSales = request()->routeIs('bixosales.*');
        /** @var \App\Models\Project $project */
        $project = $isSales
            ? \App\Models\Project::findOrFail(session('comercial_project_id'))
            : app('active_project');
        abort_unless($order->project_id === $project->id, 403);

        // Generar tag_code si el pedido es antiguo y no lo tiene
        if (!$order->tag_code) {
            $order->update(['tag_code' => 'LV-' . str_pad($order->id, 6, '0', STR_PAD_LEFT)]);
        }

        $order->load('items');

        // QR con el código de etiqueta vía servicio externo (sin dependencia PHP).
        // Si no hay internet, la vista igual muestra el código grande legible.
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=0&data='
               . urlencode($order->tag_code);

        return view('orders.tag', compact('order', 'project', 'qrUrl'));
    }

    public function update(Request $request, Order $order)
    {
        /** @var \App\Models\Project $project */
        $project = $this->proyectoActivo();
        abort_unless($order->project_id === $project->id, 403);
        $data = $request->validate(['status' => 'required|in:pending,process,processing,done,completed,cancelled', 'notes' => 'nullable|string', 'payment_method' => 'nullable|string|max:80', 'payment_condition' => 'nullable|string|max:80', 'sales_channel' => 'nullable|string|max:80']);

        $seAnula = $data['status'] === 'cancelled' && $order->status !== 'cancelled';
        if ($seAnula) {
            abort_unless($request->user()?->can('orders.cancelar'), 403, 'No tienes permiso para anular pedidos.');
        }

        if (($data['status'] ?? $order->status) !== $order->status) {
            \App\Models\OrderEvent::log($project->id, 'status_changed', ['from' => $order->status, 'to' => $data['status']], $order->id);
        }
        $order->update($data);

        // Anular devuelve la mercadería al almacén. Sin esto la venta descontaba
        // y la anulación no reponía: cada pedido anulado era una fuga permanente.
        if ($seAnula) {
            $this->devolverStockAlInventario($order);
        }

        return response()->json(['order' => $order]);
    }

    public function destroy(Order $order)
    {
        /** @var \App\Models\Project $project */
        $project = $this->proyectoActivo();
        abort_unless($order->project_id === $project->id, 403);
        // Borrar un pedido también libera su mercadería. El helper es idempotente,
        // así que anular y luego borrar no repone dos veces.
        $this->devolverStockAlInventario($order);
        $order->delete();
        return response()->json(['ok' => true]);
    }

    /**
     * Repone en el almacén lo que este pedido descontó.
     *
     * Solo devuelve lo que realmente salió por el Kardex con este pedido: si un
     * pedido antiguo no dejó movimientos, no se inventa la reposición, porque
     * adivinar inflaría el stock y provocaría vender lo que no existe.
     */
    private function devolverStockAlInventario(Order $order): void
    {
        $yaDevuelto = \App\Models\InventoryMovement::where('reference_type', 'order_cancel')
            ->where('reference_id', $order->id)->exists();
        if ($yaDevuelto) {
            return;
        }

        $salidas = \App\Models\InventoryMovement::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->where('quantity', '<', 0)
            ->get();

        foreach ($salidas as $mov) {
            $producto = \App\Models\Product::find($mov->product_id);
            if (!$producto) {
                continue;
            }
            \App\Support\InventoryLedger::registrar(
                $producto, abs((int) $mov->quantity), 'anulacion',
                null, 'Devolución por anulación del pedido #' . $order->id,
                'order_cancel', $order->id
            );
        }
    }

    /** Registrar pago (total o adelanto) con auditoría. */
    /**
     * Resuelve el proyecto activo segun el portal desde el que se llama.
     * Mismo patron que ya usan index() y show(): en rutas bixosales.* manda
     * comercial_project_id, porque ese portal tiene su propia sesion.
     */
    private function proyectoActivo(): Project
    {
        return request()->routeIs('bixosales.*')
            ? Project::findOrFail(session('comercial_project_id'))
            : app('active_project');
    }

    public function pay(Request $request, Order $order)
    {
        $project = $this->proyectoActivo();
        abort_unless($order->project_id === $project->id, 403);
        $data = $request->validate([
            'status'    => 'required|in:paid,partial,pending,rejected,refunded',
            'method'    => 'nullable|string|max:80',
            // Un pago parcial SIN importe deja al pedido diciendo "cobre algo"
            // sin saber cuanto: asi nacieron los pedidos 34 y 35, que Cuentas
            // por Cobrar acaba contando por el total. Ahora es obligatorio.
            'amount'    => 'required_if:status,partial|nullable|numeric|min:0.01',
            'reference' => 'nullable|string|max:100',
            'motivo'    => 'nullable|string|max:200',
        ], [
            'amount.required_if' => 'Indica cuánto se cobró: un pago parcial sin importe deja el saldo sin saber.',
        ]);
        // F3a: el cobro entra al LIBRO. `payment_status` y `advance_amount` ya
        // no se escriben aqui: los deriva Ledger::proyectar desde los asientos.
        // Asi un cobro es enumerable, reversible y atribuible, en vez de una
        // mutacion que pisa la anterior.
        $order->fill(array_filter([
            'payment_method'    => $data['method'] ?? null,
            'payment_reference' => $data['reference'] ?? null,
        ], fn ($v) => $v !== null))->save();

        try {
            $this->aplicarCobro($project, $order, $data);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok'     => true,
            'order'  => $order->fresh(),
            'cobros' => $this->historialDeCobros($project->id, $order->id),
        ]);
    }

    /** Traduce la accion del drawer a movimientos del libro. */
    private function aplicarCobro(\App\Models\Project $project, Order $order, array $data): void
    {
        $tipo     = $data['status'];
        $cobrado  = \App\Support\Ledger::cobradoCents($project->id, 'order', $order->id);
        $totalC   = \App\Support\LineMath::toCents(\App\Support\LineMath::canon((string) $order->total));

        if ($tipo === 'partial' || $tipo === 'paid') {
            // Con importe se respeta lo que dijo el usuario; sin el, se pasa
            // null y el LIBRO calcula el resto dentro de su transaccion: aqui
            // fuera el saldo aun no incluye el adelanto heredado.
            $importeC = isset($data['amount'])
                ? \App\Support\LineMath::toCents(\App\Support\LineMath::canon(number_format((float) $data['amount'], 2, '.', '')))
                : null;

            \App\Support\Ledger::registrar($project, $order, $importeC,
                $data['method'] ?? $order->payment_method, $data['reference'] ?? null, 'panel');

            return;
        }

        // Volver a pendiente / rechazado / devuelto con cobros ya registrados
        // NO borra nada: se revierten los asientos vigentes, con motivo, para
        // que quede rastro de que hubo correccion.
        $motivo = $data['motivo'] ?: 'Marcado como ' . $tipo . ' desde el panel';
        foreach (\App\Models\Payment::where('project_id', $project->id)
                     ->where('payable_type', 'order')->where('payable_id', $order->id)
                     ->whereNull('reverses_id')->get() as $asiento) {
            if (! \App\Models\Payment::where('reverses_id', $asiento->id)->exists()) {
                \App\Support\Ledger::revertir($asiento, $motivo);
            }
        }
    }

    /** Los cobros del pedido, para que el drawer pueda mostrarlos uno a uno. */
    private function historialDeCobros(int $projectId, int $orderId): array
    {
        return \App\Models\Payment::where('project_id', $projectId)
            ->where('payable_type', 'order')->where('payable_id', $orderId)
            ->orderBy('id')->get()
            ->map(fn ($p) => [
                'id'        => $p->id,
                'importe'   => \App\Support\LineMath::present(\App\Support\LineMath::format($p->amount_cents)),
                'metodo'    => $p->method,
                'referencia' => $p->reference,
                'fecha'     => $p->received_at?->format('d/m/Y H:i'),
                'reversion' => $p->esReversion(),
                'motivo'    => $p->reversal_reason,
            ])->all();
    }

    /** Marca el comprobante como emitido (boleta/factura/ticket) — idempotente. */
    public function issueDocument(Request $request, Order $order)
    {
        $project = $this->proyectoActivo();
        abort_unless($order->project_id === $project->id, 403);
        if ($order->document_status === 'issued') {
            return response()->json(['ok' => true, 'already' => true, 'order' => $order]);
        }
        $data = $request->validate(['type' => 'required|in:boleta,factura,ticket', 'number' => 'nullable|string|max:30']);
        $order->update(['document_status' => 'issued', 'document_type' => $data['type'], 'document_number' => $data['number'] ?? null]);
        \App\Models\OrderEvent::log($project->id, 'document_issued', ['type' => $data['type'], 'number' => $data['number'] ?? null], $order->id);

        return response()->json(['ok' => true, 'order' => $order->fresh()]);
    }

    /** Historial de auditoría del pedido. */
    public function events(Order $order)
    {
        $project = $this->proyectoActivo();
        abort_unless($order->project_id === $project->id, 403);
        $events = \App\Models\OrderEvent::where('order_id', $order->id)->with('user:id,name')
            ->orderByDesc('created_at')->limit(60)->get()
            ->map(fn ($e) => ['label' => $e->label, 'user' => $e->user?->name ?? 'Sistema', 'at' => $e->created_at->format('d/m/Y H:i')]);

        return response()->json(['events' => $events]);
    }

    /** Registrar envío de WhatsApp (el envío real abre wa.me en el navegador). */
    public function waSent(Request $request, Order $order)
    {
        $project = $this->proyectoActivo();
        abort_unless($order->project_id === $project->id, 403);
        $data = $request->validate(['template' => 'required|string|max:60', 'to' => 'required|string|max:30']);
        \App\Models\OrderEvent::log($project->id, 'whatsapp_sent', $data, $order->id);

        return response()->json(['ok' => true]);
    }

    /** Exportar pedidos a CSV (Excel) con los datos reales. */
    public function exportCsv()
    {
        $project = $this->proyectoActivo();
        $orders = $project->orders()->with('items')->latest()->limit(2000)->get();
        $headers = ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="pedidos_'.$project->slug.'_'.now()->format('Ymd').'.csv"'];
        $cb = function () use ($orders) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($f, ['Numero', 'Fecha', 'Cliente', 'Telefono', 'Estado', 'Estado pago', 'Metodo pago', 'Canal', 'Items', 'Total S/']);
            foreach ($orders as $o) {
                fputcsv($f, ['#'.$o->id, $o->created_at->format('d/m/Y H:i'), $o->client_name, $o->client_phone, $o->status, $o->payment_status ?? 'pending', $o->payment_method, $o->sales_channel, $o->items->count(), number_format((float) $o->total, 2, '.', '')]);
            }
            fclose($f);
        };

        return response()->stream($cb, 200, $headers);
    }

    // ── Portal Facturación ────────────────────────────────────────────────────

    private function projectBySlug(string $slug): Project
    {
        return Project::where('slug', $slug)->firstOrFail();
    }

    public function indexPortal(string $slug)
    {
        $project           = $this->projectBySlug($slug);
        $orders            = $project->orders()->with('client')->latest()->get();
        $paymentMethods    = $this->catValues($project, 'payment_method');
        $paymentConditions = $this->catValues($project, 'payment_condition');
        $salesChannels     = $this->catValues($project, 'sales_channel');
        return view('facturacion.pedidos.index', compact('project', 'orders', 'paymentMethods', 'paymentConditions', 'salesChannels'));
    }

    public function showPortal(string $slug, Order $order)
    {
        $project = $this->projectBySlug($slug);
        abort_unless($order->project_id === $project->id, 403);
        $order->load('items');
        return response()->json($order);
    }

    public function storePortal(Request $request, string $slug)
    {
        $project = $this->projectBySlug($slug);
        app()->instance('active_project', $project);
        return $this->store($request);
    }

    public function updatePortal(Request $request, string $slug, Order $order)
    {
        $project = $this->projectBySlug($slug);
        app()->instance('active_project', $project);
        return $this->update($request, $order);
    }

    // Vista de cocina — muestra pedidos activos con estado de preparación
    public function kitchen()
    {
        $project = app('active_project')
            ?? \App\Models\Project::find(session('comercial_project_id'));
        abort_unless($project, 403);

        $orders = $project->orders()
            ->whereIn('status', ['pending', 'process'])
            ->whereIn('kitchen_status', ['pending', 'cooking', 'ready'])
            ->with('items')
            ->latest()
            ->get();

        $ordersJson = $orders->map(function ($o) {
            return [
                'id'             => $o->id,
                'client_name'    => $o->client_name,
                'table_number'   => $o->table_number,
                'order_type'     => $o->order_type,
                'kitchen_status' => $o->kitchen_status,
                'notes'          => $o->notes,
                'created_at'     => $o->created_at->toISOString(),
                'kitchen_at'     => $o->kitchen_at?->toISOString(),
                'ready_at'       => $o->ready_at?->toISOString(),
                'items'          => $o->items->map(function ($i) {
                    return ['name' => $i->name, 'quantity' => $i->quantity];
                })->values()->all(),
            ];
        })->values()->all();

        /* El tablero se refresca cada 30 s. Antes se descargaba la pagina
           entera solo para decidir recargarla —dos cargas completas, y se
           perdia el scroll en una pantalla que esta abierta todo el servicio.
           Devolviendo los pedidos en JSON, la lista se actualiza en su sitio. */
        if (request()->expectsJson()) {
            return response()->json(['orders' => $ordersJson]);
        }

        return view('orders.kitchen', compact('orders', 'ordersJson', 'project'));
    }

    // PATCH /bixosales/pedidos/{order}/kitchen
    public function updateKitchen(Request $request, Order $order)
    {
        $project = app('active_project')
            ?? \App\Models\Project::find(session('comercial_project_id'));
        abort_unless($project && $order->project_id === $project->id, 403);

        $status = $request->input('kitchen_status');
        abort_unless(in_array($status, ['pending','cooking','ready','served']), 422);

        $data = ['kitchen_status' => $status];
        if ($status === 'cooking' && !$order->kitchen_at) $data['kitchen_at'] = now();
        if ($status === 'ready'   && !$order->ready_at)   $data['ready_at']   = now();

        $order->update($data);

        return response()->json(['ok' => true, 'kitchen_status' => $order->kitchen_status]);
    }
}
