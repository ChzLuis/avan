<?php

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\FixedAsset;
use App\Modules\Inventario\Models\WarehouseLocation;
use App\Support\Qr;
use Illuminate\Http\Request;

/**
 * Activos fijos: los equipos y muebles de la empresa.
 *
 * Todo cambio deja un apunte en el historial del activo. Sin eso, un
 * inventario de activos solo dice que existe una laptop, no quien responde
 * por ella, que es la pregunta que se hace cuando falta.
 */
class ActivoFijoController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $query = FixedAsset::where('project_id', $project->id)->with('ubicacion');

        if ($buscar = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('codigo', 'like', "%{$buscar}%")
                ->orWhere('serie', 'like', "%{$buscar}%")
                ->orWhere('responsable', 'like', "%{$buscar}%"));
        }

        if ($estado = $request->query('estado')) {
            $query->where('estado', $estado);
        }

        $activos = $query->orderBy('nombre')->get();

        $todos = FixedAsset::where('project_id', $project->id)->get(['estado', 'valor_compra']);
        $resumen = [
            'total' => $todos->count(),
            'operativos' => $todos->where('estado', 'operativo')->count(),
            'prestados' => $todos->where('estado', 'prestado')->count(),
            'reparacion' => $todos->where('estado', 'en_reparacion')->count(),
            'extraviados' => $todos->where('estado', 'extraviado')->count(),
            'valor' => (float) $todos->whereNotIn('estado', ['baja'])->sum('valor_compra'),
        ];

        $ubicaciones = WarehouseLocation::where('project_id', $project->id)->orderBy('codigo')->get();
        $estados = FixedAsset::ESTADOS;

        return view('inventario::inventory.activos', compact(
            'project', 'activos', 'resumen', 'ubicaciones', 'estados'
        ));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $datos = $request->validate([
            'codigo' => 'required|string|max:40',
            'nombre' => 'required|string|max:150',
            'categoria' => 'nullable|string|max:60',
            'marca' => 'nullable|string|max:80',
            'modelo' => 'nullable|string|max:80',
            'serie' => 'nullable|string|max:80',
            'responsable' => 'nullable|string|max:120',
            'warehouse_location_id' => 'nullable|integer',
            'fecha_compra' => 'nullable|date',
            'valor_compra' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:1000',
        ]);

        $codigo = strtoupper(trim($datos['codigo']));

        if (FixedAsset::where('project_id', $project->id)->where('codigo', $codigo)->exists()) {
            return back()->with('error', "Ya tienes un activo con el código {$codigo}.");
        }

        $activo = FixedAsset::create($datos + [
            'project_id' => $project->id,
            'codigo' => $codigo,
            'estado' => 'operativo',
        ]);

        $activo->anotar('alta', null, $datos['responsable'] ?? null, 'Alta en el inventario de activos');

        return back()->with('success', "Activo {$codigo} registrado.");
    }

    public function show(int $id)
    {
        $activo = $this->buscar($id);
        $activo->load(['ubicacion', 'eventos.user:id,name']);

        return view('inventario::inventory.activo-detalle', [
            'project' => app('active_project'),
            'activo' => $activo,
            'ubicaciones' => WarehouseLocation::where('project_id', $activo->project_id)->orderBy('codigo')->get(),
            'estados' => FixedAsset::ESTADOS,
            'qr' => Qr::svg($activo->codigo, 150),
        ]);
    }

    /**
     * Cambia responsable, estado o ubicacion, y lo anota.
     *
     * Se hace en un solo sitio a proposito: si cada cambio tuviera su ruta,
     * seria facil que alguna se olvidara de escribir el historial.
     */
    public function actualizar(Request $request, int $id)
    {
        $activo = $this->buscar($id);

        $datos = $request->validate([
            'responsable' => 'nullable|string|max:120',
            'estado' => 'nullable|string|in:'.implode(',', array_keys(FixedAsset::ESTADOS)),
            'warehouse_location_id' => 'nullable|integer',
            'nota' => 'nullable|string|max:500',
        ]);

        $nota = $datos['nota'] ?? null;

        if (array_key_exists('responsable', $datos) && $datos['responsable'] !== $activo->responsable) {
            $antes = $activo->responsable;
            $activo->anotar(
                filled($datos['responsable']) ? 'asignacion' : 'devolucion',
                $antes, $datos['responsable'], $nota
            );
            $activo->responsable = $datos['responsable'];
        }

        if (! empty($datos['estado']) && $datos['estado'] !== $activo->estado) {
            $activo->anotar('estado', FixedAsset::ESTADOS[$activo->estado] ?? $activo->estado,
                FixedAsset::ESTADOS[$datos['estado']], $nota);
            $activo->estado = $datos['estado'];
        }

        if (array_key_exists('warehouse_location_id', $datos)
            && (int) $datos['warehouse_location_id'] !== (int) $activo->warehouse_location_id) {
            $antes = $activo->ubicacion?->codigo;
            $nueva = $datos['warehouse_location_id']
                ? WarehouseLocation::where('project_id', $activo->project_id)->find($datos['warehouse_location_id'])
                : null;
            $activo->anotar('ubicacion', $antes, $nueva?->codigo, $nota);
            $activo->warehouse_location_id = $nueva?->id;
        }

        $activo->save();

        return back()->with('success', 'Activo actualizado.');
    }

    /** Busca un activo por el codigo del QR. Responde JSON para el escaner. */
    public function porCodigo(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $codigo = strtoupper(trim((string) $request->query('codigo')));

        $activo = FixedAsset::where('project_id', $project->id)->where('codigo', $codigo)->first();

        if (! $activo) {
            return response()->json(['ok' => false, 'error' => "No hay ningún activo con el código {$codigo}."], 404);
        }

        return response()->json(['ok' => true, 'url' => route('inventory.activos.show', $activo->id)]);
    }

    /** Hoja de etiquetas de los activos. */
    public function etiquetas()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $activos = FixedAsset::where('project_id', $project->id)
            ->whereNot('estado', 'baja')
            ->orderBy('nombre')->get();

        $etiquetas = $activos->map(fn ($a) => [
            'nombre' => $a->nombre,
            'codigo' => $a->codigo,
            'precio' => null,
            'unidad' => $a->responsable,
            'qr' => Qr::svg($a->codigo, 120),
        ])->all();

        return view('inventario::inventory.etiquetas-hoja', [
            'project' => $project,
            'etiquetas' => $etiquetas,
            'formato' => EtiquetaController::FORMATOS['a4-24'],
            'mostrarPrecio' => false,
        ]);
    }

    private function buscar(int $id): FixedAsset
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        return FixedAsset::where('project_id', $project->id)->whereKey($id)->firstOrFail();
    }
}
