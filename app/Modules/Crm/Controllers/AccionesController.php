<?php

namespace App\Modules\Crm\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Modules\Crm\Models\CrmAccion;
use App\Modules\Crm\Models\CrmTrato;
use App\Modules\Crm\Models\WaCanal;
use App\Modules\Crm\Models\WaConversacion;
use Illuminate\Http\Request;

/** Acciones (fase 3 del CRM): tareas con fecha por cliente, con recordatorio push al asesor. */
class AccionesController extends Controller
{
    private function project(): Project
    {
        return Project::findOrFail(session('comunicaciones_project_id'));
    }

    private function autorizar(CrmAccion $accion): void
    {
        abort_unless($accion->project_id === (int) session('comunicaciones_project_id'), 403);
    }

    /** Pagina de acciones o, con ?json=1 / Accept json, la lista (opcionalmente de una conversacion). */
    public function index(Request $request)
    {
        $project = $this->project();
        $q = CrmAccion::where('project_id', $project->id)->with('conversacion')->orderByRaw('hecho_at IS NOT NULL')->orderBy('vence_at');
        if ($request->conversacion_id) {
            $q->where('wa_conversacion_id', (int) $request->conversacion_id);
        }
        if ($request->trato_id) {
            $q->where('trato_id', (int) $request->trato_id);
        }
        if (! $request->boolean('con_hechas')) {
            $q->where(fn ($w) => $w->whereNull('hecho_at')->orWhere('hecho_at', '>=', now()->subDays(7)));
        }
        $acciones = $q->limit(300)->get()->map->toArrayCrm()->values();

        if ($request->wantsJson() || $request->boolean('json')) {
            return response()->json(['acciones' => $acciones]);
        }

        return view('crm::acciones.index', ['project' => $project, 'acciones' => $acciones, 'usuario' => $request->user()]);
    }

    public function store(Request $request)
    {
        $project = $this->project();
        $data = $request->validate([
            'titulo'             => 'required|string|max:160',
            'notas'              => 'nullable|string|max:2000',
            'tipo'               => 'nullable|in:tarea,llamada,whatsapp,reunion',
            'vence_at'           => 'nullable|date',
            'wa_conversacion_id' => 'nullable|integer',
            'trato_id'           => 'nullable|integer',
            'asignado_a'         => 'nullable|integer',
            // Aviso por WhatsApp antes de la hora (a quien coordina la accion).
            'avisar_whatsapp'    => 'nullable|string|max:20',
            'avisar_minutos'     => 'nullable|integer|min:0|max:1440',
        ]);
        if (! empty($data['avisar_whatsapp'])) {
            $data['avisar_whatsapp'] = preg_replace('/\D/', '', $data['avisar_whatsapp']);
        }
        if (! empty($data['wa_conversacion_id'])) {
            $canales = WaCanal::where('project_id', $project->id)->pluck('id');
            abort_unless(WaConversacion::whereIn('wa_canal_id', $canales)->where('id', $data['wa_conversacion_id'])->exists(), 403);
        }
        if (! empty($data['trato_id'])) {
            abort_unless(CrmTrato::where('project_id', $project->id)->where('id', $data['trato_id'])->exists(), 403);
        }
        $accion = CrmAccion::create($data + [
            'project_id' => $project->id,
            'tipo'       => $data['tipo'] ?? 'tarea',
            'asignado_a' => $data['asignado_a'] ?? $request->user()->id,
            'creado_por' => $request->user()->id,
        ]);

        return response()->json(['ok' => true, 'accion' => $accion->fresh('conversacion')->toArrayCrm()], 201);
    }

    public function update(Request $request, CrmAccion $accion)
    {
        $this->autorizar($accion);
        $data = $request->validate([
            'titulo'     => 'sometimes|string|max:160',
            'notas'      => 'nullable|string|max:2000',
            'tipo'       => 'nullable|in:tarea,llamada,whatsapp,reunion',
            'vence_at'   => 'nullable|date',
            'asignado_a' => 'nullable|integer',
            'hecha'      => 'nullable|boolean',
            'avisar_whatsapp' => 'nullable|string|max:20',
            'avisar_minutos'  => 'nullable|integer|min:0|max:1440',
        ]);
        if (! empty($data['avisar_whatsapp'])) {
            $data['avisar_whatsapp'] = preg_replace('/\D/', '', $data['avisar_whatsapp']);
        }
        if (array_key_exists('hecha', $data)) {
            $accion->hecho_at = $data['hecha'] ? now() : null;
            unset($data['hecha']);
        }
        // Si se mueve la fecha hacia adelante, el recordatorio vuelve a estar pendiente.
        if (isset($data['vence_at']) && $accion->vence_at?->toDateTimeString() !== \Carbon\Carbon::parse($data['vence_at'])->toDateTimeString()) {
            $accion->recordada_at = null;
            $accion->avisada_at = null;
        }
        $accion->fill($data)->save();

        return response()->json(['ok' => true, 'accion' => $accion->fresh('conversacion')->toArrayCrm()]);
    }

    public function destroy(CrmAccion $accion)
    {
        $this->autorizar($accion);
        $accion->delete();

        return response()->json(['ok' => true]);
    }
}
