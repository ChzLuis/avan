<?php

namespace App\Http\Controllers;

use App\Models\WaCanal;
use App\Models\WaConversacion;
use App\Models\WaMensaje;
use App\Models\WaRespuestaRapida;
use Database\Seeders\WaRespuestasRapidasSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ComunicacionesController extends Controller
{
    // ── Bandeja principal ────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $project  = app('active_project');
        $canales  = WaCanal::where('project_id', $project->id)->where('activo', true)->get();

        $query = WaConversacion::whereIn('wa_canal_id', $canales->pluck('id'))
            ->where('archivado', false)
            ->with(['canal', 'ultimoMensaje'])
            ->orderByDesc('ultimo_mensaje_at');

        // Filtros
        if ($request->canal && $request->canal !== 'todos') {
            $canalId = $canales->where('tipo', $request->canal)->first()?->id;
            if ($canalId) $query->where('wa_canal_id', $canalId);
        }
        if ($request->estado) {
            $query->where('estado', $request->estado);
        }
        if ($request->q) {
            $query->where(function($q) use ($request) {
                $q->where('cliente_nombre', 'like', "%{$request->q}%")
                  ->orWhere('cliente_telefono', 'like', "%{$request->q}%");
            });
        }
        if ($request->filter === 'sin_leer') {
            $query->where('no_leidos', '>', 0);
        }

        $conversaciones = $query->get();

        // Métricas del día
        $hoy = today();
        $metricas = [
            'total_hoy'   => WaConversacion::whereIn('wa_canal_id', $canales->pluck('id'))->whereDate('created_at', $hoy)->count(),
            'sin_leer'    => WaConversacion::whereIn('wa_canal_id', $canales->pluck('id'))->where('no_leidos', '>', 0)->count(),
            'cerrados'    => WaConversacion::whereIn('wa_canal_id', $canales->pluck('id'))->where('estado', 'cerrado')->whereDate('updated_at', $hoy)->count(),
            'por_estado'  => WaConversacion::whereIn('wa_canal_id', $canales->pluck('id'))->where('archivado', false)
                ->selectRaw('estado, count(*) as total')->groupBy('estado')->pluck('total', 'estado'),
        ];

        $respuestasRapidas = WaRespuestaRapida::where('project_id', $project->id)->orderBy('orden')->get();

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
        ])->values();

        return view('comunicaciones.index', compact('project', 'canales', 'conversacionesJs', 'metricas', 'respuestasRapidas'));
    }

    // ── Mensajes de una conversación (polling) ───────────────────────────────

    public function mensajes(WaConversacion $conversacion)
    {
        $project = app('active_project');
        $this->autorizarConversacion($conversacion, $project);

        // Marcar como leídos
        $conversacion->update(['no_leidos' => 0]);

        $mensajes = $conversacion->mensajes()->latest()->limit(100)->get()->reverse()->values();

        return response()->json([
            'mensajes'      => $mensajes,
            'conversacion'  => $conversacion->load('canal', 'client'),
        ]);
    }

    // ── Polling — solo mensajes nuevos desde timestamp ───────────────────────

    public function poll(Request $request)
    {
        $project = app('active_project');
        $canales = WaCanal::where('project_id', $project->id)->pluck('id');

        $since = $request->since ? \Carbon\Carbon::createFromTimestamp($request->since) : now()->subSeconds(5);

        // Conversaciones con mensajes nuevos
        $actualizadas = WaConversacion::whereIn('wa_canal_id', $canales)
            ->where('archivado', false)
            ->where('ultimo_mensaje_at', '>=', $since)
            ->with(['canal', 'ultimoMensaje'])
            ->get()
            ->map(fn($c) => [
                'id'              => $c->id,
                'cliente_nombre'  => $c->cliente_nombre,
                'cliente_telefono'=> $c->cliente_telefono,
                'no_leidos'       => $c->no_leidos,
                'estado'          => $c->estado,
                'ultimo_mensaje'  => $c->ultimoMensaje?->contenido,
                'ultimo_mensaje_at'=> $c->ultimo_mensaje_at?->toISOString(),
                'canal_tipo'      => $c->canal->tipo,
                'canal_color'     => $c->canal->color,
                'canal_nombre'    => $c->canal->nombre,
            ]);

        // Mensajes nuevos si hay conversación activa
        $mensajesNuevos = [];
        if ($request->conversacion_id) {
            $conv = WaConversacion::find($request->conversacion_id);
            if ($conv && in_array($conv->wa_canal_id, $canales->toArray())) {
                $mensajesNuevos = $conv->mensajes()
                    ->where('created_at', '>=', $since)
                    ->get();
            }
        }

        return response()->json([
            'conversaciones_actualizadas' => $actualizadas,
            'mensajes_nuevos'             => $mensajesNuevos,
            'server_time'                 => now()->timestamp,
        ]);
    }

    // ── Enviar mensaje ───────────────────────────────────────────────────────

    public function enviar(Request $request, WaConversacion $conversacion)
    {
        $project = app('active_project');
        $this->autorizarConversacion($conversacion, $project);

        $data = $request->validate([
            'contenido' => 'required|string|max:4096',
            'tipo'      => 'in:texto,imagen,documento,audio',
        ]);

        $canal = $conversacion->canal;
        $waMessageId = null;

        // Enviar por WhatsApp API si está configurado
        if ($canal->phone_number_id && $canal->access_token) {
            $waMessageId = $this->enviarPorWhatsApp(
                $canal,
                $conversacion->cliente_telefono,
                $data['contenido']
            );
        }

        $mensaje = $conversacion->mensajes()->create([
            'wa_message_id' => $waMessageId,
            'direccion'     => 'saliente',
            'tipo'          => $data['tipo'] ?? 'texto',
            'contenido'     => $data['contenido'],
            'estado'        => $waMessageId ? 'enviado' : 'pendiente',
        ]);

        $conversacion->update([
            'ultimo_mensaje_at' => now(),
            'estado'            => $conversacion->estado === 'nuevo' ? 'contactado' : $conversacion->estado,
        ]);

        return response()->json(['ok' => true, 'mensaje' => $mensaje]);
    }

    // ── Actualizar conversación (estado, notas, asignación) ──────────────────

    public function actualizar(Request $request, WaConversacion $conversacion)
    {
        $project = app('active_project');
        $this->autorizarConversacion($conversacion, $project);

        $data = $request->validate([
            'estado'      => 'nullable|in:nuevo,contactado,demo_enviada,propuesta,cerrado,perdido,academia',
            'notas'       => 'nullable|string',
            'archivado'   => 'nullable|boolean',
            'asignado_a'  => 'nullable|string|max:80',
            'cliente_nombre'   => 'nullable|string|max:100',
            'cliente_sector'   => 'nullable|string|max:80',
            'cliente_distrito' => 'nullable|string|max:80',
        ]);

        $conversacion->update(array_filter($data, fn($v) => !is_null($v)));

        return response()->json(['ok' => true]);
    }

    // ── CRUD Respuestas rápidas ──────────────────────────────────────────────

    public function respuestas()
    {
        $project = app('active_project');
        $respuestas = WaRespuestaRapida::where('project_id', $project->id)->orderBy('orden')->get();
        return response()->json($respuestas);
    }

    public function guardarRespuesta(Request $request)
    {
        $project = app('active_project');
        $data = $request->validate([
            'id'     => 'nullable|integer',
            'canal'  => 'required|in:bixo,academy,todos',
            'nombre' => 'required|string|max:80',
            'texto'  => 'required|string',
            'orden'  => 'nullable|integer',
        ]);

        if (!empty($data['id'])) {
            $r = WaRespuestaRapida::where('project_id', $project->id)->findOrFail($data['id']);
            $r->update($data);
        } else {
            $r = WaRespuestaRapida::create(array_merge($data, ['project_id' => $project->id]));
        }

        return response()->json(['ok' => true, 'respuesta' => $r]);
    }

    public function eliminarRespuesta(WaRespuestaRapida $respuesta)
    {
        $project = app('active_project');
        abort_unless($respuesta->project_id === $project->id, 403);
        $respuesta->delete();
        return response()->json(['ok' => true]);
    }

    // ── Configuración de canales ─────────────────────────────────────────────

    public function configuracion()
    {
        $project = app('active_project');
        $canales = WaCanal::where('project_id', $project->id)->get();
        $canalesJs = $canales->map(fn($c) => [
            'id'              => $c->id,
            'nombre'          => $c->nombre,
            'tipo'            => $c->tipo,
            'telefono'        => $c->telefono,
            'phone_number_id' => $c->phone_number_id,
            'verify_token'    => $c->verify_token,
            'color'           => $c->color ?? '#25d366',
            'activo'          => $c->activo,
        ])->values();
        return view('comunicaciones.configuracion', compact('project', 'canales', 'canalesJs'));
    }

    public function guardarCanal(Request $request)
    {
        $project = app('active_project');
        $data = $request->validate([
            'id'               => 'nullable|integer',
            'nombre'           => 'required|string|max:80',
            'tipo'             => 'required|in:bixo,academy,partners',
            'telefono'         => 'nullable|string|max:30',
            'phone_number_id'  => 'nullable|string|max:80',
            'access_token'     => 'nullable|string',
            'verify_token'     => 'nullable|string|max:100',
            'color'            => 'nullable|string|max:20',
            'mensaje_bienvenida' => 'nullable|string',
            'mensaje_ausencia'   => 'nullable|string',
        ]);

        if (!empty($data['id'])) {
            $canal = WaCanal::where('project_id', $project->id)->findOrFail($data['id']);
            // No sobreescribir tokens si vienen vacíos
            if (empty($data['access_token'])) unset($data['access_token']);
            if (empty($data['verify_token']))  unset($data['verify_token']);
            $canal->update($data);
        } else {
            $canal = WaCanal::create(array_merge($data, ['project_id' => $project->id]));
            // Sembrar respuestas rápidas si es el primer canal
            if (WaCanal::where('project_id', $project->id)->count() === 1) {
                WaRespuestasRapidasSeeder::seedForProject($project->id);
            }
        }

        return response()->json(['ok' => true, 'canal' => $canal->makeVisible(['access_token', 'verify_token'])]);
    }

    public function eliminarCanal(WaCanal $canal)
    {
        $project = app('active_project');
        abort_unless($canal->project_id === $project->id, 403);
        $canal->delete();
        return response()->json(['ok' => true]);
    }

    // ── Privado ──────────────────────────────────────────────────────────────

    private function autorizarConversacion(WaConversacion $conv, $project): void
    {
        $canalIds = WaCanal::where('project_id', $project->id)->pluck('id');
        abort_unless($canalIds->contains($conv->wa_canal_id), 403);
    }

    private function enviarPorWhatsApp(WaCanal $canal, string $telefono, string $mensaje): ?string
    {
        try {
            $res = Http::withToken($canal->access_token)
                ->post("https://graph.facebook.com/v18.0/{$canal->phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $telefono,
                    'type'              => 'text',
                    'text'              => ['body' => $mensaje],
                ]);

            return $res->json('messages.0.id');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
