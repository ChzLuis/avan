<?php

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogo\Models\Product;
use App\Modules\Inventario\Models\Proveedor;
use App\Modules\Inventario\Models\PurchaseOrder;
use App\Modules\Inventario\Models\PurchaseOrderItem;
use App\Modules\Inventario\Models\WarehouseLocation;
use App\Modules\Inventario\Support\InventoryLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Ordenes de compra y recepcion de mercaderia.
 *
 * Cierra el ciclo del almacen: hasta ahora el stock solo podia subir con un
 * ajuste manual, sin decir a quien se compro, a que precio, ni si llego todo.
 *
 * La entrada al stock se hace SIEMPRE con `InventoryLedger`, igual que
 * cualquier otro movimiento: asi la compra aparece en el kardex del producto
 * junto a las ventas y los conteos, y se puede responder "de donde salio este
 * stock" meses despues.
 */
class OrdenCompraController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $query = PurchaseOrder::where('project_id', $project->id)
            ->with(['proveedor:id,name', 'ubicacion:id,codigo,nombre'])
            ->withCount('items');

        if ($estado = $request->query('estado')) {
            $query->where('estado', $estado);
        }
        if ($buscar = trim((string) $request->query('q'))) {
            $query->where('numero', 'like', "%{$buscar}%");
        }

        $ordenes = $query->orderByDesc('id')->limit(100)->get();

        $todas = PurchaseOrder::where('project_id', $project->id)->get(['estado', 'total']);
        $resumen = [
            'abiertas' => $todas->whereIn('estado', ['enviada', 'parcial'])->count(),
            'borradores' => $todas->where('estado', 'borrador')->count(),
            'por_recibir' => (float) $todas->whereIn('estado', ['enviada', 'parcial'])->sum('total'),
        ];

        $proveedores = Proveedor::where('project_id', $project->id)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $ubicaciones = WarehouseLocation::where('project_id', $project->id)
            ->where('is_active', true)->with('sede:id,name')->orderBy('codigo')->get();

        $estados = PurchaseOrder::ESTADOS;
        $moneda = $project->setting('currency_symbol') ?: 'S/';

        return view('inventario::inventory.compras', compact(
            'project', 'ordenes', 'resumen', 'proveedores', 'ubicaciones', 'estados', 'moneda'
        ));
    }

    /** Abre una orden en borrador. Las lineas se agregan despues. */
    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $datos = $request->validate([
            'proveedor_id' => 'nullable|integer',
            'warehouse_location_id' => 'nullable|integer',
            'fecha_esperada' => 'nullable|date',
            'notas' => 'nullable|string|max:1000',
        ]);

        if (! empty($datos['proveedor_id'])) {
            $existe = Proveedor::where('project_id', $project->id)->whereKey($datos['proveedor_id'])->exists();
            if (! $existe) {
                return back()->with('error', 'Ese proveedor no es de este negocio.');
            }
        }

        $orden = PurchaseOrder::create($datos + [
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'numero' => PurchaseOrder::siguienteNumero($project->id),
            'estado' => 'borrador',
            'fecha_emision' => now()->toDateString(),
            'moneda' => $project->setting('currency') ?: 'PEN',
        ]);

        return redirect()->route('inventory.compras.show', $orden->id);
    }

    public function show(int $id)
    {
        $orden = $this->buscar($id);
        $orden->load(['items.product:id,name,sku,unit,cost', 'proveedor', 'ubicacion.sede', 'user:id,name']);

        /** @var \App\Models\Project $project */
        $project = app('active_project');

        // Solo se ofrecen productos que llevan control de stock: pedirle al
        // proveedor un servicio no tiene sentido en una orden de almacen.
        $productos = $project->products()->whereNotNull('stock')
            ->orderBy('name')->limit(500)->get(['id', 'name', 'sku', 'cost', 'unit']);

        return view('inventario::inventory.compra-detalle', [
            'project' => $project,
            'orden' => $orden,
            'productos' => $productos,
            'moneda' => $project->setting('currency_symbol') ?: 'S/',
            'ubicaciones' => WarehouseLocation::where('project_id', $project->id)
                ->where('is_active', true)->with('sede:id,name')->orderBy('codigo')->get(),
        ]);
    }

    /** Agrega o actualiza una linea. Solo en borrador. */
    public function agregarLinea(Request $request, int $id)
    {
        $orden = $this->buscar($id);

        if (! $orden->esEditable()) {
            return response()->json(['ok' => false, 'error' => 'La orden ya fue enviada: no se pueden cambiar sus líneas.'], 422);
        }

        $datos = $request->validate([
            'product_id' => 'required|integer',
            'cantidad' => 'required|integer|min:1|max:1000000',
            'precio_unitario' => 'nullable|numeric|min:0',
        ]);

        $producto = Product::where('project_id', $orden->project_id)->find($datos['product_id']);
        if (! $producto) {
            return response()->json(['ok' => false, 'error' => 'Ese producto no es de este negocio.'], 404);
        }

        // Si no se indica precio se usa el ultimo costo conocido: casi siempre
        // se compra al mismo precio, y escribirlo cada vez cansa.
        $precio = $datos['precio_unitario'] ?? $producto->cost ?? 0;

        PurchaseOrderItem::updateOrCreate(
            ['purchase_order_id' => $orden->id, 'product_id' => $producto->id],
            ['cantidad' => $datos['cantidad'], 'precio_unitario' => $precio]
        );

        $orden->load('items');
        $orden->recalcular();

        return response()->json([
            'ok' => true,
            'total' => (float) $orden->total,
            'lineas' => $orden->items->count(),
        ]);
    }

    public function quitarLinea(Request $request, int $id)
    {
        $orden = $this->buscar($id);

        if (! $orden->esEditable()) {
            return back()->with('error', 'La orden ya fue enviada: no se pueden quitar líneas.');
        }

        $datos = $request->validate(['item_id' => 'required|integer']);
        $orden->items()->whereKey($datos['item_id'])->delete();

        $orden->load('items');
        $orden->recalcular();

        return back()->with('success', 'Línea quitada.');
    }

    /** Cierra el borrador y la da por enviada al proveedor. */
    public function enviar(int $id)
    {
        $orden = $this->buscar($id);

        if (! $orden->esEditable()) {
            return back()->with('error', 'Esta orden ya no es un borrador.');
        }

        $orden->load('items');
        if ($orden->items->isEmpty()) {
            return back()->with('error', 'Agrega al menos un producto antes de enviarla.');
        }

        $orden->update(['estado' => 'enviada', 'fecha_emision' => now()->toDateString()]);

        return back()->with('success', "Orden {$orden->numero} enviada. Ya puedes registrar su recepción.");
    }

    /**
     * Recibe mercaderia y la mete al stock.
     *
     * Admite recepcion parcial porque es lo normal: llegan 8 de 10 y el resto
     * la semana que viene. Cada unidad entra por `InventoryLedger`, asi que
     * queda en el kardex con el proveedor y el numero de orden.
     */
    public function recibir(Request $request, int $id)
    {
        $orden = $this->buscar($id);

        if (! $orden->admiteRecepcion()) {
            return back()->with('error', 'Esta orden no admite recepciones: '.$orden->etiquetaEstado().'.');
        }

        $datos = $request->validate([
            'recibido' => 'required|array',
            'recibido.*' => 'nullable|integer|min:0|max:1000000',
            'actualizar_costo' => 'nullable|boolean',
        ]);

        $orden->load('items.product');
        $entradas = 0;
        $unidades = 0;
        $errores = [];

        DB::transaction(function () use ($orden, $datos, &$entradas, &$unidades, &$errores) {
            foreach ($orden->items as $item) {
                $ahora = (int) ($datos['recibido'][$item->id] ?? 0);
                if ($ahora <= 0) {
                    continue;
                }

                // No se puede recibir mas de lo pedido: si el proveedor manda
                // de mas, eso es otra compra, no esta orden.
                $falta = $item->pendiente();
                if ($ahora > $falta) {
                    $errores[] = ($item->product->name ?? 'Producto')." : pediste {$falta} y quieres recibir {$ahora}.";

                    continue;
                }

                if (! $item->product) {
                    continue;
                }

                InventoryLedger::registrar(
                    $item->product,
                    $ahora,
                    'compra',
                    (float) $item->precio_unitario,
                    "Orden de compra {$orden->numero}".
                        ($orden->proveedor ? ' · '.$orden->proveedor->name : ''),
                    'purchase_order',
                    $orden->id,
                    auth()->id()
                );

                $item->cantidad_recibida += $ahora;
                $item->save();

                // El costo del producto se actualiza al de esta compra si se
                // pidio: es lo que hace que la valorizacion del inventario no
                // se quede con precios de hace un ano.
                if (! empty($datos['actualizar_costo']) && (float) $item->precio_unitario > 0) {
                    $item->product->cost = $item->precio_unitario;
                    $item->product->save();
                }

                // Si la orden tiene ubicacion de destino, la mercaderia se
                // coloca alli; asi se sabe donde buscarla desde el primer dia.
                if ($orden->warehouse_location_id) {
                    $yaHabia = DB::table('product_locations')
                        ->where('product_id', $item->product_id)
                        ->where('warehouse_location_id', $orden->warehouse_location_id)
                        ->value('cantidad');

                    DB::table('product_locations')->updateOrInsert(
                        ['product_id' => $item->product_id, 'warehouse_location_id' => $orden->warehouse_location_id],
                        [
                            'project_id' => $orden->project_id,
                            'cantidad' => (int) ($yaHabia ?? 0) + $ahora,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }

                $entradas++;
                $unidades += $ahora;
            }

            $orden->actualizarEstadoPorRecepcion();
        });

        if ($entradas === 0) {
            return back()->with('error', $errores
                ? implode(' ', $errores)
                : 'No indicaste ninguna cantidad a recibir.');
        }

        $aviso = "Recibidas {$unidades} unidades en {$entradas} producto(s). Ya están en el stock.";
        if ($errores) {
            $aviso .= ' Con avisos: '.implode(' ', $errores);
        }

        return back()->with('success', $aviso);
    }

    public function anular(int $id)
    {
        $orden = $this->buscar($id);

        if ($orden->estado === 'recibida') {
            return back()->with('error', 'Una orden ya recibida no se anula: el stock entró. Registra una devolución al proveedor.');
        }

        $orden->update(['estado' => 'anulada']);

        return back()->with('success', "Orden {$orden->numero} anulada.");
    }

    private function buscar(int $id): PurchaseOrder
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        return PurchaseOrder::where('project_id', $project->id)->whereKey($id)->firstOrFail();
    }
}
