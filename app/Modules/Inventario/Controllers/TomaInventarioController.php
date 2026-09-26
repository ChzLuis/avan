<?php

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogo\Models\Product;
use App\Modules\Inventario\Models\InventoryCount;
use App\Modules\Inventario\Models\InventoryCountItem;
use App\Modules\Inventario\Support\InventoryLedger;
use App\Modules\Inventario\Support\StockSituacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Toma de inventario: contar lo que hay de verdad en el almacen.
 *
 * El flujo es el de siempre en una bodega: se abre un conteo, se recorre el
 * almacen escaneando y anotando cantidades, y al final se cierra. Recien al
 * cerrar se toca el stock, y se toca con `InventoryLedger`, que es el unico
 * escritor: asi cada ajuste queda en el kardex con su motivo y su autor, y se
 * puede responder "por que bajo el stock" meses despues.
 *
 * Mientras el conteo esta abierto no altera nada. Eso permite contar en
 * varios dias, o a dos personas a la vez, sin dejar el inventario a medias.
 */
class TomaInventarioController extends Controller
{
    /** Conteos del negocio, el abierto primero. */
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $conteos = InventoryCount::where('project_id', $project->id)
            ->with(['user:id,name', 'category:id,name'])
            ->withCount('items')
            ->orderByRaw("estado = 'abierta' DESC")
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $categorias = $project->categories()->orderBy('name')->get(['id', 'name']);

        return view('inventario::inventory.tomas', compact('project', 'conteos', 'categorias'));
    }

    /** Abre un conteo y congela el stock del sistema de cada producto. */
    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $datos = $request->validate([
            'nombre' => 'required|string|max:120',
            'category_id' => 'nullable|integer',
            'notas' => 'nullable|string|max:1000',
        ]);

        // Solo entran los productos que llevan control de stock: un servicio o
        // un producto bajo pedido no se puede contar en el estante.
        $query = $project->products()->whereNotNull('stock');
        if (! empty($datos['category_id'])) {
            $query->where('category_id', $datos['category_id']);
        }
        $productos = $query->get(['id', 'stock']);

        if ($productos->isEmpty()) {
            return back()->with('error', 'No hay productos con control de stock en ese alcance.');
        }

        $conteo = DB::transaction(function () use ($project, $datos, $productos) {
            $conteo = InventoryCount::create([
                'project_id' => $project->id,
                'user_id' => auth()->id(),
                'nombre' => $datos['nombre'],
                'category_id' => ($datos['category_id'] ?? null) ?: null,
                'notas' => $datos['notas'] ?? null,
                'estado' => 'abierta',
            ]);

            // El stock del sistema se congela AHORA. Si se leyera al cerrar,
            // una venta hecha mientras se contaba apareceria como un faltante
            // que nadie cometio.
            $filas = $productos->map(fn ($p) => [
                'inventory_count_id' => $conteo->id,
                'product_id' => $p->id,
                'stock_sistema' => (int) $p->stock,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            foreach (array_chunk($filas, 500) as $lote) {
                InventoryCountItem::insert($lote);
            }

            return $conteo;
        });

        return redirect()->route('inventory.tomas.show', $conteo->id);
    }

    /** Pantalla de conteo: escanear, anotar y ver diferencias. */
    public function show(int $id)
    {
        $conteo = $this->buscar($id);

        $items = $conteo->items()
            ->with('product:id,name,sku,barcode,unit')
            ->get()
            ->sortBy(fn ($i) => $i->product->name ?? '')
            ->values();

        // Lo vendido y no despachado sigue en el estante: quien cuenta lo va a
        // encontrar. Si no se avisa, el conteo lo marca como sobrante y el
        // encargado "corrige" un stock que estaba bien.
        $comprometidos = StockSituacion::comprometidoPorProducto($conteo->project_id);

        $resumen = $conteo->resumen();

        return view('inventario::inventory.toma-detalle', [
            'project' => app('active_project'),
            'conteo' => $conteo,
            'items' => $items,
            'resumen' => $resumen,
            'comprometidos' => $comprometidos,
        ]);
    }

    /**
     * Anota lo contado de un producto. Responde JSON porque la pantalla de
     * conteo va escaneando sin recargar.
     */
    public function contar(Request $request, int $id)
    {
        $conteo = $this->buscar($id);

        if (! $conteo->estaAbierta()) {
            return response()->json(['ok' => false, 'error' => 'Este conteo ya está cerrado.'], 422);
        }

        $datos = $request->validate([
            'codigo' => 'nullable|string|max:80',
            'item_id' => 'nullable|integer',
            'cantidad' => 'required|integer|min:0|max:1000000',
        ]);

        $item = null;

        if (! empty($datos['item_id'])) {
            $item = $conteo->items()->whereKey($datos['item_id'])->first();
        } elseif (! empty($datos['codigo'])) {
            // El codigo viene del QR o del lector: puede ser el SKU o el
            // codigo de barras, asi que se buscan los dos.
            $codigo = trim($datos['codigo']);
            $candidatos = Product::where('project_id', $conteo->project_id)
                ->where(fn ($q) => $q->where('sku', $codigo)->orWhere('barcode', $codigo))
                ->limit(5)
                ->get();

            if ($candidatos->isEmpty()) {
                return response()->json([
                    'ok' => false,
                    'error' => "El código {$codigo} no está en tu catálogo.",
                ], 404);
            }

            // Codigo repetido en varios productos: anotar la cuenta en el que
            // no era deja mal DOS lineas del conteo, la de este y la del otro.
            if ($candidatos->count() > 1) {
                return response()->json([
                    'ok' => false,
                    'error' => "El código {$codigo} lo tienen {$candidatos->count()} productos distintos. "
                        .'Busca el producto en la lista y anota la cantidad ahí.',
                ], 409);
            }

            $producto = $candidatos->first();

            $item = $conteo->items()->where('product_id', $producto->id)->first();

            if (! $item) {
                return response()->json([
                    'ok' => false,
                    'error' => "{$producto->name} no entra en este conteo (otra categoría o sin control de stock).",
                ], 404);
            }
        }

        if (! $item) {
            return response()->json(['ok' => false, 'error' => 'No se indicó qué producto contar.'], 422);
        }

        $item->update([
            'contado' => $datos['cantidad'],
            'contado_at' => now(),
        ]);

        $item->load('product:id,name,sku,unit');

        return response()->json([
            'ok' => true,
            'item' => [
                'id' => $item->id,
                'nombre' => $item->product->name ?? '',
                'sku' => $item->product->sku ?? '',
                'sistema' => $item->stock_sistema,
                'contado' => $item->contado,
                'diferencia' => $item->diferencia(),
            ],
            'resumen' => $conteo->fresh()->load('items')->resumen(),
        ]);
    }

    /**
     * Cierra el conteo y aplica las diferencias al stock.
     *
     * Las lineas sin contar se dejan como estan a proposito: que nadie haya
     * llegado a ese estante no significa que el producto no exista. Ponerlas
     * a cero borraria inventario real.
     */
    public function cerrar(int $id)
    {
        $conteo = $this->buscar($id);

        if (! $conteo->estaAbierta()) {
            return back()->with('error', 'Este conteo ya estaba cerrado.');
        }

        $ajustados = 0;

        DB::transaction(function () use ($conteo, &$ajustados) {
            $items = $conteo->items()->whereNotNull('contado')->with('product')->get();

            foreach ($items as $item) {
                if (! $item->product || $item->diferencia() === 0) {
                    continue;
                }

                $movimiento = InventoryLedger::ajustarA(
                    $item->product,
                    (int) $item->contado,
                    'conteo',
                    "Toma de inventario: {$conteo->nombre}",
                    $conteo->user_id,
                );

                if ($movimiento) {
                    $ajustados++;
                }
            }

            $conteo->update(['estado' => 'cerrada', 'cerrada_at' => now()]);
        });

        return redirect()->route('inventory.tomas.show', $conteo->id)
            ->with('success', $ajustados === 0
                ? 'Conteo cerrado. No había diferencias que ajustar.'
                : "Conteo cerrado. Se ajustaron {$ajustados} productos.");
    }

    /** Un conteo del negocio activo. El filtro por proyecto va en la consulta. */
    private function buscar(int $id): InventoryCount
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        return InventoryCount::where('project_id', $project->id)
            ->whereKey($id)
            ->firstOrFail();
    }
}
