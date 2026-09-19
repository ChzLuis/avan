<?php

namespace App\Modules\Crm\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Modules\Crm\Models\CrmEstado;
use App\Modules\Crm\Models\WaCanal;
use App\Modules\Crm\Models\WaConversacion;
use Illuminate\Http\Request;

/** Estados de conversacion del negocio: crear, renombrar, recolorear, reordenar y borrar. */
class EstadosController extends Controller
{
    private function project(): Project
    {
        return Project::findOrFail(session('comunicaciones_project_id'));
    }

    public function index()
    {
        $project = $this->project();
        CrmEstado::asegurar($project);

        return response()->json(['estados' => CrmEstado::delProyecto($project->id)]);
    }

    /**
     * Guarda la lista completa (como las etapas de Tratos): lo que no venga se borra y
     * sus conversaciones pasan al estado inicial, para que ninguna quede con un estado fantasma.
     */
    public function guardar(Request $request)
    {
        $project = $this->project();
        $data = $request->validate([
            'estados'              => 'required|array|min:1|max:30',
            'estados.*.id'         => 'nullable|integer',
            'estados.*.nombre'     => 'required|string|max:60',
            'estados.*.color'      => 'nullable|string|max:9',
            'estados.*.es_inicial' => 'nullable|boolean',
            'estados.*.es_final'   => 'nullable|boolean',
        ]);

        $existentes = CrmEstado::where('project_id', $project->id)->get()->keyBy('id');
        $vivos = [];
        $hayInicial = false;
        foreach ($data['estados'] as $i => $e) {
            $modelo = ! empty($e['id']) ? $existentes->get($e['id']) : null;
            $inicial = ! $hayInicial && ! empty($e['es_inicial']);
            if ($inicial) {
                $hayInicial = true;
            }
            $campos = [
                'nombre'     => $e['nombre'],
                'color'      => $e['color'] ?? '#64748b',
                'orden'      => $i,
                'es_inicial' => $inicial,
                'es_final'   => (bool) ($e['es_final'] ?? false),
            ];
            if ($modelo) {
                $modelo->update($campos);
            } else {
                $modelo = CrmEstado::create($campos + [
                    'project_id' => $project->id,
                    'clave'      => CrmEstado::claveDesde($e['nombre'], $project->id),
                ]);
            }
            $vivos[] = $modelo->id;
        }
        // Si nadie quedo como inicial, el primero lo es.
        if (! $hayInicial) {
            CrmEstado::whereIn('id', $vivos)->limit(1)->update(['es_inicial' => true]);
        }

        $borrados = $existentes->keys()->diff($vivos);
        if ($borrados->isNotEmpty()) {
            $inicial = CrmEstado::where('project_id', $project->id)->where('es_inicial', true)->first()
                ?? CrmEstado::where('project_id', $project->id)->orderBy('orden')->first();
            $canales = WaCanal::where('project_id', $project->id)->pluck('id');
            $claves = $existentes->only($borrados->all())->pluck('clave');
            WaConversacion::whereIn('wa_canal_id', $canales)->whereIn('estado', $claves)
                ->update(['estado' => $inicial->clave]);
            CrmEstado::whereIn('id', $borrados)->delete();
        }

        return response()->json(['ok' => true, 'estados' => CrmEstado::delProyecto($project->id)]);
    }
}
