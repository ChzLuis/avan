<?php

namespace App\Http\Controllers\Comunicaciones;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\WaCanal;
use App\Models\WaChatbotFlow;
use Database\Seeders\WaRespuestasRapidasSeeder;
use Illuminate\Http\Request;

class CanalesController extends Controller
{
    private function project(): Project
    {
        return Project::findOrFail(session('comunicaciones_project_id'));
    }

    public function index()
    {
        $project = $this->project();
        $canales = WaCanal::where('project_id', $project->id)->get();
        $canalesJs = $canales->map(fn($c) => [
            'id'                 => $c->id,
            'nombre'             => $c->nombre,
            'tipo'               => $c->tipo,
            'telefono'           => $c->telefono,
            'phone_number_id'    => $c->phone_number_id,
            'verify_token'       => $c->verify_token,
            'color'              => $c->color,
            'activo'             => $c->activo,
            'mensaje_bienvenida' => $c->mensaje_bienvenida,
            'mensaje_ausencia'   => $c->mensaje_ausencia,
        ])->values();
        return view('comunicaciones.configuracion', compact('project', 'canales', 'canalesJs'));
    }

    public function guardar(Request $request)
    {
        $project = $this->project();
        $data = $request->validate([
            'id'                 => 'nullable|integer',
            'nombre'             => 'required|string|max:80',
            'tipo'               => 'required|in:bixo,academy,partners',
            'telefono'           => 'nullable|string|max:30',
            'phone_number_id'    => 'nullable|string|max:80',
            'access_token'       => 'nullable|string',
            'verify_token'       => 'nullable|string|max:100',
            'color'              => 'nullable|string|max:20',
            'mensaje_bienvenida' => 'nullable|string',
            'mensaje_ausencia'   => 'nullable|string',
        ]);

        if (!empty($data['id'])) {
            $canal = WaCanal::where('project_id', $project->id)->findOrFail($data['id']);
            if (empty($data['access_token'])) unset($data['access_token']);
            if (empty($data['verify_token']))  unset($data['verify_token']);
            $canal->update($data);
        } else {
            $canal = WaCanal::create(array_merge($data, ['project_id' => $project->id]));
            if (WaCanal::where('project_id', $project->id)->count() === 1) {
                WaRespuestasRapidasSeeder::seedForProject($project->id);
            }
        }

        return response()->json(['ok' => true, 'canal' => $canal->makeVisible(['access_token', 'verify_token'])]);
    }

    public function eliminar(WaCanal $canal)
    {
        abort_unless($canal->project_id === $this->project()->id, 403);
        $canal->delete();
        return response()->json(['ok' => true]);
    }

    // ── Chatbot ──────────────────────────────────────────────────────────────

    public function chatbot()
    {
        $project = $this->project();
        $canales = WaCanal::where('project_id', $project->id)->get();
        $flows   = WaChatbotFlow::whereIn('wa_canal_id', $canales->pluck('id'))
            ->orderBy('wa_canal_id')
            ->orderBy('orden')
            ->get();

        return view('comunicaciones.chatbot', compact('project', 'canales', 'flows'));
    }

    public function guardarFlow(Request $request)
    {
        $project = $this->project();
        $canales = WaCanal::where('project_id', $project->id)->pluck('id');

        $data = $request->validate([
            'id'               => 'nullable|integer',
            'wa_canal_id'      => 'required|integer',
            'nombre'           => 'required|string|max:100',
            'trigger_keywords' => 'nullable|string',
            'response_text'    => 'required|string|max:4096',
            'es_bienvenida'    => 'boolean',
            'activo'           => 'boolean',
            'orden'            => 'integer',
        ]);

        abort_unless($canales->contains($data['wa_canal_id']), 403);

        // Convertir keywords de string CSV a array
        $keywords = array_values(array_filter(
            array_map('trim', explode(',', $data['trigger_keywords'] ?? ''))
        ));
        $data['trigger_keywords'] = $keywords;

        if (!empty($data['id'])) {
            $flow = WaChatbotFlow::where('id', $data['id'])
                ->whereIn('wa_canal_id', $canales)
                ->firstOrFail();
            $flow->update($data);
        } else {
            $flow = WaChatbotFlow::create($data);
        }

        return response()->json(['ok' => true, 'flow' => $flow]);
    }

    public function eliminarFlow(WaChatbotFlow $flow)
    {
        $project = $this->project();
        $canales = WaCanal::where('project_id', $project->id)->pluck('id');
        abort_unless($canales->contains($flow->wa_canal_id), 403);
        $flow->delete();
        return response()->json(['ok' => true]);
    }

    public function toggleBot(Request $request, $conversacionId)
    {
        $project = $this->project();
        $canales = WaCanal::where('project_id', $project->id)->pluck('id');

        $conv = \App\Models\WaConversacion::whereIn('wa_canal_id', $canales)->findOrFail($conversacionId);
        $conv->update(['bot_activo' => !$conv->bot_activo]);

        return response()->json(['ok' => true, 'bot_activo' => $conv->bot_activo]);
    }
}
