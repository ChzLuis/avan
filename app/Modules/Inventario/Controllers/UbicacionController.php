<?php

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Sede;
use App\Modules\Catalogo\Models\Product;
use App\Modules\Inventario\Models\LocationTransfer;
use App\Modules\Inventario\Models\WarehouseLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Ubicaciones del almacen y que hay guardado en cada una.
 *
 * Se escanea el QR del estante y sale su contenido. Es lo contrario del
 * conteo: alli se busca un producto, aqui se pregunta por el sitio.
 */
class UbicacionController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $query = WarehouseLocation::where('project_id', $project->id)
            ->with('sede:id,name,manager,address')
            ->withCount('productos');

        if ($buscar = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q->where('codigo', 'like', "%{$buscar}%")
                ->orWhere('nombre', 'like', "%{$buscar}%")
                ->orWhere('zona', 'like', "%{$buscar}%"));
        }

        $ubicaciones = $query->orderBy('sort_order')->orderBy('codigo')->get();
        $tipos = WarehouseLocation::TIPOS;

        // Los locales del negocio, para poder colgar de ellos cada ubicacion.
        $sedes = Sede::where('project_id', $project->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('inventario::inventory.ubicaciones', compact('project', 'ubicaciones', 'tipos', 'sedes'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $datos = $request->validate([
            'codigo' => 'required|string|max:40',
            'nombre' => 'required|string|max:120',
            'sede_id' => 'nullable|integer',
            'zona' => 'nullable|string|max:60',
            'responsable' => 'nullable|string|max:120',
            'tipo' => 'required|string|in:'.implode(',', array_keys(WarehouseLocation::TIPOS)),
            'notas' => 'nullable|string|max:1000',
        ]);

        // La sede tiene que ser del mismo negocio: si no, una ubicacion podria
        // acabar colgando del local de otro cliente.
        if (! empty($datos['sede_id'])) {
            $existeSede = Sede::where('project_id', $project->id)->whereKey($datos['sede_id'])->exists();
            if (! $existeSede) {
                return back()->with('error', 'Esa sede no es de este negocio.');
            }
        }

        $codigo = strtoupper(trim($datos['codigo']));

        // El codigo es lo que se imprime en el QR: si se repitiera, al
        // escanear habria dos estantes candidatos y ninguno seguro.
        $existe = WarehouseLocation::where('project_id', $project->id)->where('codigo', $codigo)->exists();
        if ($existe) {
            return back()->with('error', "Ya tienes una ubicación con el código {$codigo}.");
        }

        WarehouseLocation::create($datos + [
            'project_id' => $project->id,
            'codigo' => $codigo,
        ]);

        return back()->with('success', "Ubicación {$codigo} creada.");
    }

    /** Contenido de una ubicacion. Es la pantalla que se abre al escanear. */
    public function show(int $id)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $ubicacion = WarehouseLocation::where('project_id', $project->id)->whereKey($id)->firstOrFail();

        $productos = $ubicacion->productos()
            ->select('products.id', 'products.name', 'products.sku', 'products.stock', 'products.unit')
            ->orderBy('products.name')
            ->get();

        $activos = $ubicacion->activos()->orderBy('nombre')->get();

        return view('inventario::inventory.ubicacion-detalle', compact('project', 'ubicacion', 'productos', 'activos'));
    }

    /** Guarda un producto en la ubicacion (o actualiza su cantidad). */
    public function asignar(Request $request, int $id)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $ubicacion = WarehouseLocation::where('project_id', $project->id)->whereKey($id)->firstOrFail();

        $datos = $request->validate([
            'codigo' => 'required|string|max:80',
            'cantidad' => 'nullable|integer|min:0|max:1000000',
        ]);

        $codigo = trim($datos['codigo']);
        $producto = Product::where('project_id', $project->id)
            ->where(fn ($q) => $q->where('sku', $codigo)->orWhere('barcode', $codigo))
            ->first();

        if (! $producto) {
            return response()->json(['ok' => false, 'error' => "El código {$codigo} no está en tu catálogo."], 404);
        }

        DB::table('product_locations')->updateOrInsert(
            ['product_id' => $producto->id, 'warehouse_location_id' => $ubicacion->id],
            [
                'project_id' => $project->id,
                'cantidad' => $datos['cantidad'] ?? null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json([
            'ok' => true,
            'producto' => [
                'id' => $producto->id,
                'nombre' => $producto->name,
                'sku' => $producto->sku,
                'stock' => $producto->stock,
                'cantidad' => $datos['cantidad'] ?? null,
            ],
        ]);
    }

    /** Saca un producto de la ubicacion. No toca el stock: solo deja de decir donde esta. */
    public function quitar(Request $request, int $id)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $ubicacion = WarehouseLocation::where('project_id', $project->id)->whereKey($id)->firstOrFail();

        $datos = $request->validate(['product_id' => 'required|integer']);

        DB::table('product_locations')
            ->where('warehouse_location_id', $ubicacion->id)
            ->where('product_id', $datos['product_id'])
            ->delete();

        return back()->with('success', 'Producto quitado de la ubicación.');
    }

    /**
     * Mueve mercaderia de una ubicacion a otra.
     *
     * Un traslado NO cambia el stock del negocio: los sacos siguen siendo los
     * mismos, solo cambian de sitio. Por eso no pasa por `InventoryLedger` ni
     * ensucia el kardex; queda en su propio registro, que es donde se mira
     * cuando algo no esta donde deberia.
     */
    public function trasladar(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $datos = $request->validate([
            'codigo' => 'required_without:product_id|nullable|string|max:80',
            'product_id' => 'required_without:codigo|nullable|integer',
            'desde_id' => 'nullable|integer',
            'hasta_id' => 'required|integer',
            'cantidad' => 'required|integer|min:1|max:1000000',
            'nota' => 'nullable|string|max:300',
        ]);

        if (! empty($datos['desde_id']) && (int) $datos['desde_id'] === (int) $datos['hasta_id']) {
            return response()->json(['ok' => false, 'error' => 'El origen y el destino son el mismo sitio.'], 422);
        }

        // El producto llega por codigo cuando se escanea, que es el caso
        // normal en el almacen, o por id desde una pantalla que ya lo sabe.
        $candidatos = Product::where('project_id', $project->id)
            ->when(! empty($datos['product_id']), fn ($q) => $q->whereKey($datos['product_id']))
            ->when(empty($datos['product_id']), function ($q) use ($datos) {
                $codigo = trim((string) $datos['codigo']);
                $q->where(fn ($w) => $w->where('sku', $codigo)->orWhere('barcode', $codigo));
            })
            ->limit(5)
            ->get();

        if ($candidatos->isEmpty()) {
            $ref = $datos['codigo'] ?? $datos['product_id'];
            return response()->json(['ok' => false, 'error' => "No encontré el producto {$ref} en tu catálogo."], 404);
        }

        // Un mismo codigo en varios productos hace el escaneo ambiguo. Antes
        // que mover el equivocado y descuadrar dos ubicaciones, se avisa: el
        // sistema no puede adivinar cual de los cinco tenia en la mano.
        if ($candidatos->count() > 1) {
            return response()->json([
                'ok' => false,
                'error' => 'El código '.trim((string) $datos['codigo']).' lo tienen '.$candidatos->count()
                    .' productos distintos. Corrige los códigos repetidos en el catálogo o elige el producto a mano.',
                'ambiguo' => $candidatos->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->name])->all(),
            ], 409);
        }

        $producto = $candidatos->first();

        $destino = WarehouseLocation::where('project_id', $project->id)->find($datos['hasta_id']);
        if (! $destino) {
            return response()->json(['ok' => false, 'error' => 'La ubicación de destino no existe.'], 404);
        }

        $origen = null;
        if (! empty($datos['desde_id'])) {
            $origen = WarehouseLocation::where('project_id', $project->id)->find($datos['desde_id']);
            if (! $origen) {
                return response()->json(['ok' => false, 'error' => 'La ubicación de origen no existe.'], 404);
            }
        }

        $cantidad = (int) $datos['cantidad'];

        // Si el origen lleva cantidad anotada, no se puede sacar mas de lo que
        // hay: permitirlo dejaria el estante en negativo, que es una mentira
        // que luego nadie sabe como corregir.
        if ($origen) {
            $enOrigen = DB::table('product_locations')
                ->where('product_id', $producto->id)
                ->where('warehouse_location_id', $origen->id)
                ->value('cantidad');

            if ($enOrigen === null) {
                return response()->json([
                    'ok' => false,
                    'error' => "{$producto->name} no está registrado en {$origen->codigo}.",
                ], 422);
            }

            if ((int) $enOrigen < $cantidad) {
                return response()->json([
                    'ok' => false,
                    'error' => "En {$origen->codigo} solo hay {$enOrigen}. No puedes mover {$cantidad}.",
                ], 422);
            }
        }

        // Mover algo dentro del mismo local es acomodar; llevarlo a otro local
        // es un traslado entre establecimientos de la misma empresa, que en
        // Peru necesita guia de remision (motivo 04 de SUNAT). El sistema no
        // la emite solo, pero avisa: enterarse en el control de carretera es
        // caro.
        $cruzaSedes = $origen
            && $origen->sede_id
            && $destino->sede_id
            && (int) $origen->sede_id !== (int) $destino->sede_id;

        DB::transaction(function () use ($project, $producto, $origen, $destino, $cantidad, $datos, $cruzaSedes) {
            if ($origen) {
                $restante = DB::table('product_locations')
                    ->where('product_id', $producto->id)
                    ->where('warehouse_location_id', $origen->id)
                    ->value('cantidad') - $cantidad;

                if ($restante > 0) {
                    DB::table('product_locations')
                        ->where('product_id', $producto->id)
                        ->where('warehouse_location_id', $origen->id)
                        ->update(['cantidad' => $restante, 'updated_at' => now()]);
                } else {
                    // Se llevaron todo: la linea desaparece en vez de quedarse
                    // en cero, o la ubicacion mostraria productos que ya no tiene.
                    DB::table('product_locations')
                        ->where('product_id', $producto->id)
                        ->where('warehouse_location_id', $origen->id)
                        ->delete();
                }
            }

            $yaHabia = DB::table('product_locations')
                ->where('product_id', $producto->id)
                ->where('warehouse_location_id', $destino->id)
                ->value('cantidad');

            DB::table('product_locations')->updateOrInsert(
                ['product_id' => $producto->id, 'warehouse_location_id' => $destino->id],
                [
                    'project_id' => $project->id,
                    'cantidad' => (int) ($yaHabia ?? 0) + $cantidad,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            LocationTransfer::create([
                'project_id' => $project->id,
                'product_id' => $producto->id,
                'user_id' => auth()->id(),
                'desde_id' => $origen?->id,
                'hasta_id' => $destino->id,
                'cantidad' => $cantidad,
                'entre_sedes' => $cruzaSedes,
                'nota' => $datos['nota'] ?? null,
            ]);
        });

        return response()->json([
            'ok' => true,
            'mensaje' => $origen
                ? "{$cantidad} de {$producto->name}: {$origen->codigo} → {$destino->codigo}"
                : "{$cantidad} de {$producto->name} colocados en {$destino->codigo}",
            'entre_sedes' => $cruzaSedes,
            'aviso' => $cruzaSedes
                ? 'Sale de '.($origen->sede->name ?? 'un local').' hacia '.($destino->sede->name ?? 'otro local')
                    .'. Un traslado entre locales de la misma empresa necesita guía de remisión (motivo 04).'
                : null,
            // Las guias viven en el portal de ventas, no en este panel. Se
            // comprueba que la ruta exista: si el modulo fiscal no esta
            // instalado, el aviso sale igual pero sin enlace roto.
            'url_guia' => $cruzaSedes && \Illuminate\Support\Facades\Route::has('bixosales.guias.index')
                ? route('bixosales.guias.index')
                : null,
        ]);
    }

    /** Historial de traslados del negocio. */
    public function traslados()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $traslados = LocationTransfer::where('project_id', $project->id)
            ->with(['product:id,name,sku', 'desde:id,codigo', 'hasta:id,codigo', 'user:id,name'])
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $ubicaciones = WarehouseLocation::where('project_id', $project->id)
            ->where('is_active', true)
            ->with('sede:id,name,manager,address')
            ->orderBy('sede_id')->orderBy('codigo')
            ->get();

        return view('inventario::inventory.traslados', compact('project', 'traslados', 'ubicaciones'));
    }

    /** Hoja de etiquetas QR de las ubicaciones, para pegar en los estantes. */
    public function etiquetas(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $ubicaciones = WarehouseLocation::where('project_id', $project->id)
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('codigo')
            ->get();

        $etiquetas = $ubicaciones->map(fn ($u) => [
            'nombre' => $u->nombre,
            'codigo' => $u->codigo,
            'precio' => null,
            'unidad' => $u->zona,
            'qr' => \App\Support\Qr::svg($u->codigo, 120),
        ])->all();

        $formato = EtiquetaController::FORMATOS['a4-24'];

        return view('inventario::inventory.etiquetas-hoja', [
            'project' => $project,
            'etiquetas' => $etiquetas,
            'formato' => $formato,
            'mostrarPrecio' => false,
        ]);
    }
}
