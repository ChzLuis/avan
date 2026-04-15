<?php

namespace App\Http\Controllers\Comunicaciones;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ComunicacionesController;
use App\Models\Project;
use App\Models\WaCanal;
use App\Models\WaConversacion;
use App\Models\WaRespuestaRapida;
use Illuminate\Http\Request;

class BandejaController extends Controller
{
    private function project(): Project
    {
        return Project::findOrFail(session('comunicaciones_project_id'));
    }

    public function index(Request $request)
    {
        $project  = $this->project();
        $canales  = WaCanal::where('project_id', $project->id)->where('activo', true)->get();

        $query = WaConversacion::whereIn('wa_canal_id', $canales->pluck('id'))
            ->where('archivado', false)
            ->with(['canal', 'ultimoMensaje'])
            ->orderByDesc('ultimo_mensaje_at');

        if ($request->canal && $request->canal !== 'todos') {
            $canalId = $canales->where('tipo', $request->canal)->first()?->id;
            if ($canalId) $query->where('wa_canal_id', $canalId);
        }
        if ($request->estado) $query->where('estado', $request->estado);
        if ($request->q) {
            $query->where(fn($q) => $q
                ->where('cliente_nombre', 'like', "%{$request->q}%")
                ->orWhere('cliente_telefono', 'like', "%{$request->q}%")
            );
        }
        if ($request->filter === 'sin_leer') $query->where('no_leidos', '>', 0);

        $conversaciones = $query->get();

        $hoy = today();
        $metricas = [
            'total_hoy' => WaConversacion::whereIn('wa_canal_id', $canales->pluck('id'))->whereDate('created_at', $hoy)->count(),
            'sin_leer'  => WaConversacion::whereIn('wa_canal_id', $canales->pluck('id'))->where('no_leidos', '>', 0)->count(),
            'cerrados'  => WaConversacion::whereIn('wa_canal_id', $canales->pluck('id'))->where('estado', 'cerrado')->whereDate('updated_at', $hoy)->count(),
        ];

        $conversacionesJs = $conversaciones->map(fn($c) => [
            'id'               => $c->id,
            'cliente_nombre'   => $c->cliente_nombre,
            'cliente_telefono' => $c->cliente_telefono,
            'cliente_sector'   => $c->cliente_sector,
            'cliente_distrito' => $c->cliente_distrito,
            'estado'           => $c->estado,
            'no_leidos'        => $c->no_leidos,
            'origen_anuncio'   => $c->origen_anuncio,
            'notas'            => $c->notas,
            'ultimo_mensaje'   => $c->ultimoMensaje?->contenido,
            'ultimo_mensaje_at'=> $c->ultimo_mensaje_at?->toISOString(),
            'canal_tipo'       => $c->canal->tipo,
            'canal_color'      => $c->canal->color,
            'canal_nombre'     => $c->canal->nombre,
            'wa_canal_id'      => $c->wa_canal_id,
            'bot_activo'       => $c->bot_activo ?? true,
        ])->values();

        $respuestasRapidas = WaRespuestaRapida::where('project_id', $project->id)->orderBy('orden')->get();

        return view('comunicaciones.bandeja', compact('project', 'canales', 'conversacionesJs', 'metricas', 'respuestasRapidas'));
    }

    public function mensajes(WaConversacion $conversacion)
    {
        $this->autorizar($conversacion);
        $conversacion->update(['no_leidos' => 0]);
        $mensajes = $conversacion->mensajes()->latest()->limit(100)->get()->reverse()->values();
        return response()->json(['mensajes' => $mensajes, 'conversacion' => $conversacion->load('canal')]);
    }

    public function poll(Request $request)
    {
        $project = $this->project();
        $canales = WaCanal::where('project_id', $project->id)->pluck('id');
        $since   = $request->since ? \Carbon\Carbon::createFromTimestamp($request->since) : now()->subSeconds(5);

        $actualizadas = WaConversacion::whereIn('wa_canal_id', $canales)
            ->where('archivado', false)
            ->where('ultimo_mensaje_at', '>=', $since)
            ->with(['canal', 'ultimoMensaje'])
            ->get()->map(fn($c) => [
                'id'               => $c->id,
                'cliente_nombre'   => $c->cliente_nombre,
                'cliente_telefono' => $c->cliente_telefono,
                'no_leidos'        => $c->no_leidos,
                'estado'           => $c->estado,
                'ultimo_mensaje'   => $c->ultimoMensaje?->contenido,
                'ultimo_mensaje_at'=> $c->ultimo_mensaje_at?->toISOString(),
                'canal_tipo'       => $c->canal->tipo,
                'canal_color'      => $c->canal->color,
                'canal_nombre'     => $c->canal->nombre,
            ]);

        $mensajesNuevos = [];
        if ($request->conversacion_id) {
            $conv = WaConversacion::find($request->conversacion_id);
            if ($conv && $canales->contains($conv->wa_canal_id)) {
                $mensajesNuevos = $conv->mensajes()->where('created_at', '>=', $since)->get();
            }
        }

        return response()->json([
            'conversaciones_actualizadas' => $actualizadas,
            'mensajes_nuevos'             => $mensajesNuevos,
            'server_time'                 => now()->timestamp,
        ]);
    }

    public function enviar(Request $request, WaConversacion $conversacion)
    {
        $this->autorizar($conversacion);
        $data = $request->validate(['contenido' => 'required|string|max:4096']);

        $project = $this->project();
        $canal   = $conversacion->canal;
        $waMessageId = null;

        if ($canal->phone_number_id && $canal->access_token) {
            try {
                $res = \Illuminate\Support\Facades\Http::withToken($canal->access_token)
                    ->post("https://graph.facebook.com/v18.0/{$canal->phone_number_id}/messages", [
                        'messaging_product' => 'whatsapp',
                        'recipient_type'    => 'individual',
                        'to'                => $conversacion->cliente_telefono,
                        'type'              => 'text',
                        'text'              => ['body' => $data['contenido']],
                    ]);
                $waMessageId = $res->json('messages.0.id');
            } catch (\Throwable) {}
        }

        $mensaje = $conversacion->mensajes()->create([
            'wa_message_id' => $waMessageId,
            'direccion'     => 'saliente',
            'tipo'          => 'texto',
            'contenido'     => $data['contenido'],
            'estado'        => $waMessageId ? 'enviado' : 'pendiente',
        ]);

        $conversacion->update([
            'ultimo_mensaje_at' => now(),
            'estado' => $conversacion->estado === 'nuevo' ? 'contactado' : $conversacion->estado,
        ]);

        return response()->json(['ok' => true, 'mensaje' => $mensaje]);
    }

    public function actualizar(Request $request, WaConversacion $conversacion)
    {
        $this->autorizar($conversacion);
        $data = $request->validate([
            'estado'           => 'nullable|in:nuevo,contactado,demo_enviada,propuesta,cerrado,perdido,academia',
            'notas'            => 'nullable|string',
            'archivado'        => 'nullable|boolean',
            'cliente_nombre'   => 'nullable|string|max:100',
            'cliente_sector'   => 'nullable|string|max:80',
            'cliente_distrito' => 'nullable|string|max:80',
        ]);
        $conversacion->update(array_filter($data, fn($v) => !is_null($v)));
        return response()->json(['ok' => true]);
    }

    private function autorizar(WaConversacion $conv): void
    {
        $ids = WaCanal::where('project_id', session('comunicaciones_project_id'))->pluck('id');
        abort_unless($ids->contains($conv->wa_canal_id), 403);
    }
}
