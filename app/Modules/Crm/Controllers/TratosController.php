<?php

namespace App\Modules\Crm\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Modules\Crm\Models\CrmEtapa;
use App\Modules\Crm\Models\CrmTrato;
use App\Modules\Crm\Models\WaConversacion;
use App\Modules\Crm\Models\WaCanal;
use Illuminate\Http\Request;

/**
 * Tratos: el embudo de ventas del CRM (tablero kanban y lista).
 *
 * Lo propio de BIXO frente a un CRM generico: un trato nace de una
 * conversacion de WhatsApp con un clic y conserva el enlace al chat; cada
 * etapa lleva una probabilidad para la prevision ponderada; y se ve cuantos
 * dias lleva cada trato sin moverse.
 */
class TratosController extends Controller
{
    private function project(): Project
    {
        return Project::findOrFail(session('comunicaciones_project_id'));
    }

    public function index(Request $request)
    {
        $project = $this->project();
        CrmEtapa::asegurar($project);

        $etapas = CrmEtapa::where('project_id', $project->id)->orderBy('orden')->get();
        $tratos = CrmTrato::where('project_id', $project->id)
            ->with('asesor:id,name')
            ->orderBy('orden')->orderByDesc('id')->get();

        $asesores = User::whereIn('id', function ($q) use ($project) {
            $q->select('user_id')->from('project_members')->where('project_id', $project->id);
        })->orWhere('id', $project->owner_id)->orderBy('name')->get(['id', 'name']);

        $tratosJs = $tratos->map(fn (CrmTrato $t) => [
            'id'                 => $t->id,
            'etapa_id'           => $t->etapa_id,
            'titulo'             => $t->titulo,
            'contacto_nombre'    => $t->contacto_nombre,
            'contacto_telefono'  => $t->contacto_telefono,
            'valor'              => (float) $t->valor,
            'moneda'             => $t->moneda,
            'asesor_id'          => $t->asesor_id,
            'asesor'             => $t->asesor?->name,
            'cierre_estimado'    => $t->cierre_estimado?->toDateString(),
            'dias_en_etapa'      => $t->diasEnEtapa(),
            'wa_conversacion_id' => $t->wa_conversacion_id,
            'motivo_perdida'     => $t->motivo_perdida,
            'notas'              => $t->notas,
            'origen'             => $t->origen,
            'orden'              => $t->orden,
        ])->values();

        // Alta directa desde la ficha de una conversacion (?nuevo=1&conversacion=ID)
        $prellenado = null;
        if ($request->integer('conversacion')) {
            $conv = WaConversacion::find($request->integer('conversacion'));
            if ($conv && WaCanal::where('project_id', $project->id)->pluck('id')->contains($conv->wa_canal_id)) {
                $prellenado = [
                    'wa_conversacion_id' => $conv->id,
                    'contacto_nombre'    => $conv->cliente_nombre,
                    'contacto_telefono'  => $conv->cliente_telefono,
                    'titulo'             => ($conv->cliente_nombre ?: $conv->cliente_telefono) . ' — ' . $project->name,
                ];
            }
        }

        return view('crm::tratos.index', [
            'project'    => $project,
            'etapas'     => $etapas,
            'tratosJs'   => $tratosJs,
            'asesores'   => $asesores,
            'prellenado' => $prellenado,
            'vista'      => $request->get('vista', 'tablero'),
        ]);
    }

    private function reglas(): array
    {
        return [
            'titulo'             => 'required|string|max:120',
            'etapa_id'           => 'nullable|integer',
            'contacto_nombre'    => 'nullable|string|max:100',
            'contacto_telefono'  => 'nullable|string|max:30',
            'valor'              => 'nullable|numeric|min:0|max:99999999',
            'moneda'             => 'nullable|string|size:3',
            'asesor_id'          => 'nullable|integer',
            'cierre_estimado'    => 'nullable|date',
            'notas'              => 'nullable|string|max:5000',
            'wa_conversacion_id' => 'nullable|integer',
        ];
    }

    public function store(Request $request)
    {
        $project = $this->project();
        CrmEtapa::asegurar($project);
        $data = $request->validate($this->reglas());

        $etapa = $this->etapaDelNegocio($project, $data['etapa_id'] ?? null)
            ?? CrmEtapa::where('project_id', $project->id)->orderBy('orden')->firstOrFail();
        $this->validarConversacion($project, $data['wa_conversacion_id'] ?? null);

        $trato = CrmTrato::create($data + [
            'project_id'  => $project->id,
            'etapa_id'    => $etapa->id,
            'etapa_desde' => now(),
            'origen'      => ! empty($data['wa_conversacion_id']) ? 'whatsapp' : 'manual',
            'orden'       => (int) CrmTrato::where('etapa_id', $etapa->id)->max('orden') + 1,
        ]);

        return response()->json(['ok' => true, 'trato' => $this->serializar($trato)], 201);
    }

    public function update(Request $request, CrmTrato $trato)
    {
        $this->autorizar($trato);
        $data = $request->validate($this->reglas() + ['titulo' => 'sometimes|required|string|max:120']);
        unset($data['etapa_id'], $data['wa_conversacion_id']);
        $trato->update($data);

        return response()->json(['ok' => true, 'trato' => $this->serializar($trato->fresh())]);
    }

    /** Arrastrar a otra columna (o reordenar dentro de la misma). */
    public function mover(Request $request, CrmTrato $trato)
    {
        $this->autorizar($trato);
        $data = $request->validate([
            'etapa_id'       => 'required|integer',
            'orden'          => 'nullable|integer|min:0',
            'motivo_perdida' => 'nullable|string|max:160',
        ]);
        $etapa = $this->etapaDelNegocio($trato->project, $data['etapa_id']);
        abort_unless($etapa, 422, 'Esa etapa no es de este negocio.');

        if ($etapa->id !== $trato->etapa_id) {
            $trato->moverA($etapa, $data['motivo_perdida'] ?? null);
        }
        if (isset($data['orden'])) {
            $trato->update(['orden' => $data['orden']]);
        }

        return response()->json(['ok' => true, 'trato' => $this->serializar($trato->fresh())]);
    }

    public function destroy(CrmTrato $trato)
    {
        $this->autorizar($trato);
        $trato->delete();

        return response()->json(['ok' => true]);
    }

    // ── Etapas (configuracion del embudo) ───────────────────────────────────

    public function guardarEtapas(Request $request)
    {
        $project = $this->project();
        $data = $request->validate([
            'etapas'                 => 'required|array|min:2|max:12',
            'etapas.*.id'            => 'nullable|integer',
            'etapas.*.nombre'        => 'required|string|max:60',
            'etapas.*.color'         => 'nullable|string|max:20',
            'etapas.*.probabilidad'  => 'nullable|integer|min:0|max:100',
            'etapas.*.es_ganado'     => 'nullable|boolean',
            'etapas.*.es_perdido'    => 'nullable|boolean',
        ]);

        $vivas = [];
        foreach ($data['etapas'] as $i => $e) {
            $campos = [
                'nombre' => $e['nombre'], 'orden' => $i, 'color' => $e['color'] ?? '#6366f1',
                'probabilidad' => $e['probabilidad'] ?? 50,
                'es_ganado' => (bool) ($e['es_ganado'] ?? false), 'es_perdido' => (bool) ($e['es_perdido'] ?? false),
            ];
            $etapa = ! empty($e['id']) ? CrmEtapa::where('project_id', $project->id)->find($e['id']) : null;
            $etapa ? $etapa->update($campos) : $etapa = CrmEtapa::create($campos + ['project_id' => $project->id]);
            $vivas[] = $etapa->id;
        }
        // Las etapas que ya no estan: sus tratos pasan a la primera viva, nunca se pierden.
        $primera = CrmEtapa::whereIn('id', $vivas)->orderBy('orden')->first();
        foreach (CrmEtapa::where('project_id', $project->id)->whereNotIn('id', $vivas)->get() as $sobrante) {
            CrmTrato::where('etapa_id', $sobrante->id)->update(['etapa_id' => $primera->id, 'etapa_desde' => now()]);
            $sobrante->delete();
        }

        return response()->json(['ok' => true, 'etapas' => CrmEtapa::where('project_id', $project->id)->orderBy('orden')->get()]);
    }

    // ── util ─────────────────────────────────────────────────────────────────

    private function etapaDelNegocio(Project $project, ?int $id): ?CrmEtapa
    {
        return $id ? CrmEtapa::where('project_id', $project->id)->find($id) : null;
    }

    private function validarConversacion(Project $project, ?int $id): void
    {
        if (! $id) {
            return;
        }
        $conv = WaConversacion::find($id);
        abort_unless($conv && WaCanal::where('project_id', $project->id)->pluck('id')->contains($conv->wa_canal_id), 403);
    }

    private function autorizar(CrmTrato $trato): void
    {
        abort_unless($trato->project_id === (int) session('comunicaciones_project_id'), 403);
    }

    private function serializar(CrmTrato $t): array
    {
        $t->loadMissing('asesor:id,name');

        return [
            'id' => $t->id, 'etapa_id' => $t->etapa_id, 'titulo' => $t->titulo,
            'contacto_nombre' => $t->contacto_nombre, 'contacto_telefono' => $t->contacto_telefono,
            'valor' => (float) $t->valor, 'moneda' => $t->moneda, 'asesor_id' => $t->asesor_id, 'asesor' => $t->asesor?->name,
            'cierre_estimado' => $t->cierre_estimado?->toDateString(), 'dias_en_etapa' => $t->diasEnEtapa(),
            'wa_conversacion_id' => $t->wa_conversacion_id, 'motivo_perdida' => $t->motivo_perdida,
            'notas' => $t->notas, 'origen' => $t->origen, 'orden' => $t->orden,
        ];
    }
}
