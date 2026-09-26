<?php

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\Inventario\Models\InventoryMovement;
use App\Modules\Catalogo\Models\Product;
use App\Modules\Inventario\Support\InventoryLedger;
use App\Modules\Inventario\Support\StockSituacion;
use Illuminate\Http\Request;

/**
 * Inventario y Kardex: existencias actuales, su valorización y el historial
 * de movimientos de cada producto.
 */
class InventoryController extends Controller
{
    /** Existencias actuales del proyecto (solo productos que llevan control de stock). */
    public function index(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $query = $project->products()->whereNotNull('stock')->with('category');

        if ($buscar = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$buscar}%")->orWhere('sku', 'like', "%{$buscar}%"));
        }

        $filtro = $request->query('estado');
        if ($filtro === 'agotado')  $query->where('stock', '<=', 0);
        if ($filtro === 'bajo')     $query->whereColumn('stock', '<=', 'stock_min')->where('stock', '>', 0);

        $productos = $query->orderBy('name')->get();

        // Valorización: se calcula sobre TODO el inventario, no sobre el filtro,
        // para que el total no cambie al buscar (sería confuso).
        // El `id` hace falta para cruzar con lo comprometido: sin el, el
        // resumen no encuentra ningun producto y sale todo a cero.
        $todos = $project->products()->whereNotNull('stock')->get(['id', 'stock', 'cost', 'price', 'stock_min']);
        $resumen = [
            'referencias' => $todos->count(),
            'unidades'    => (int) $todos->sum('stock'),
            'valor_costo' => (float) $todos->sum(fn ($p) => (int) $p->stock * (float) ($p->cost ?? 0)),
            'valor_venta' => (float) $todos->sum(fn ($p) => (int) $p->stock * (float) $p->price),
            'agotados'    => $todos->filter(fn ($p) => (int) $p->stock <= 0)->count(),
            'bajos'       => $todos->filter(fn ($p) => (int) $p->stock > 0 && (int) $p->stock <= (int) ($p->stock_min ?? 0))->count(),
            // Un producto sin costo no suma a la valorización; hay que decirlo o el
            // total se lee como si fuera el valor real del almacén.
            'sin_costo'   => $todos->filter(fn ($p) => $p->cost === null || (float) $p->cost <= 0)->count(),
        ];

        $ultimos = InventoryMovement::where('project_id', $project->id)
            ->with(['product:id,name', 'user:id,name'])
            ->orderByDesc('id')->limit(12)->get();

        $motivosEntrada = InventoryLedger::ENTRADAS;
        $motivosSalida  = InventoryLedger::SALIDAS;

        // Vendido y aun en el estante. Sin esto, la pantalla dice que hay 3
        // cuando en el almacen se ven 8, y nadie sabe cual de las dos miente.
        $comprometidos = StockSituacion::comprometidoPorProducto($project->id);
        $situacion = StockSituacion::resumen($project->id, $todos);

        return view('inventario::inventory.index', compact(
            'project', 'productos', 'resumen', 'ultimos', 'motivosEntrada', 'motivosSalida',
            'comprometidos', 'situacion'
        ));
    }

    /** Kardex de un producto: todos sus movimientos, del más reciente al más antiguo. */
    public function kardex(Product $product)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($product->project_id === $project->id, 403);

        $movimientos = InventoryMovement::where('product_id', $product->id)
            ->with('user:id,name')
            ->orderByDesc('id')->limit(300)->get();

        $entradas = $movimientos->where('quantity', '>', 0)->sum('quantity');
        $salidas  = abs($movimientos->where('quantity', '<', 0)->sum('quantity'));

        return view('inventario::inventory.kardex', compact('project', 'product', 'movimientos', 'entradas', 'salidas'));
    }

    /** Registra un movimiento manual (compra, merma, devolución, conteo…). */
    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $data = $request->validate([
            'product_id' => 'required|integer',
            'reason'     => 'required|string|in:' . implode(',', array_merge(InventoryLedger::ENTRADAS, InventoryLedger::SALIDAS, ['conteo'])),
            'quantity'   => 'required|integer|min:1',
            'unit_cost'  => 'nullable|numeric|min:0',
            'notes'      => 'nullable|string|max:300',
        ]);

        $product = $project->products()->findOrFail($data['product_id']);

        if ($product->stock === null) {
            return back()->with('error', 'Este producto no lleva control de stock. Actívalo en su ficha para poder registrar movimientos.');
        }

        // "conteo" fija la existencia; el resto suma o resta según el motivo.
        if ($data['reason'] === 'conteo') {
            InventoryLedger::ajustarA($product, (int) $data['quantity'], 'conteo', $data['notes'] ?? null);
        } else {
            $entra = in_array($data['reason'], InventoryLedger::ENTRADAS, true);
            $delta = $entra ? (int) $data['quantity'] : -(int) $data['quantity'];

            if (!$entra && (int) $product->stock < (int) $data['quantity']) {
                return back()->with('error', "No puedes retirar {$data['quantity']} unidades: solo hay {$product->stock} en stock.");
            }

            InventoryLedger::registrar($product, $delta, $data['reason'], $data['unit_cost'] ?? null, $data['notes'] ?? null);
        }

        return back()->with('success', 'Movimiento registrado en el Kardex.');
    }
}
