<?php

namespace App\Modules\Crm\Controllers;

use App\Modules\Crm\Support\WhatsappCloud\ClienteCloud;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Controllers\ComunicacionesController;
use App\Models\Project;
use App\Modules\Crm\Models\WaCanal;
use App\Modules\Crm\Models\WaConversacion;
use App\Modules\Crm\Models\WaRespuestaRapida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        // Trae tambien las archivadas: la bandeja las muestra en su pestana.
        $query = WaConversacion::whereIn('wa_canal_id', $canales->pluck('id'))
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
            'ultimo_tipo'      => $c->ultimoMensaje?->tipo,
            'ultimo_direccion' => $c->ultimoMensaje?->direccion,
            'ultimo_mensaje_at'=> $c->ultimo_mensaje_at?->toISOString(),
            'fijada'           => (bool) $c->fijada,
            'asignado_a'       => $c->asignado_a,
            'archivado'        => (bool) $c->archivado,
            'canal_tipo'       => $c->canal->tipo,
            'canal_color'      => $c->canal->color,
            'canal_nombre'     => $c->canal->nombre,
            'wa_canal_id'      => $c->wa_canal_id,
            'bot_activo'       => $c->bot_activo ?? true,
        ])->values();

        $respuestasRapidas = WaRespuestaRapida::where('project_id', $project->id)->orderBy('orden')->get();

        return view('crm::comunicaciones.bandeja', compact('project', 'canales', 'conversacionesJs', 'metricas', 'respuestasRapidas'));
    }

    public function mensajes(WaConversacion $conversacion)
    {
        $this->autorizar($conversacion);
        $conversacion->update(['no_leidos' => 0]);
        // Varios mensajes en el mismo segundo (el bot manda tres seguidos): el id desempata, si no salian de cabeza.
        $mensajes = $conversacion->mensajes()->orderByDesc('created_at')->orderByDesc('id')->limit(100)->get()->reverse()->values();
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
                'ultimo_tipo'      => $c->ultimoMensaje?->tipo,
                'ultimo_direccion' => $c->ultimoMensaje?->direccion,
                'ultimo_mensaje_at'=> $c->ultimo_mensaje_at?->toISOString(),
                'fijada'           => (bool) $c->fijada,
                'asignado_a'       => $c->asignado_a,
                'archivado'        => (bool) $c->archivado,
                'canal_tipo'       => $c->canal->tipo,
                'canal_color'      => $c->canal->color,
                'canal_nombre'     => $c->canal->nombre,
            ]);

        $mensajesNuevos = [];
        $estados = [];
        if ($request->conversacion_id) {
            $conv = WaConversacion::find($request->conversacion_id);
            if ($conv && $canales->contains($conv->wa_canal_id)) {
                $mensajesNuevos = $conv->mensajes()->where('created_at', '>=', $since)->orderBy('id')->get();
                // Acuses de Meta sobre mensajes que ya estaban en pantalla (✓ -> ✓✓ -> azul, o fallo).
                $estados = $conv->mensajes()->whereIn('direccion', ['out', 'saliente'])->where('updated_at', '>=', $since)
                    ->get(['id', 'estado', 'error', 'leido_at', 'entregado_at']);
            }
        }

        return response()->json([
            'conversaciones_actualizadas' => $actualizadas,
            'mensajes_nuevos'             => $mensajesNuevos,
            'estados'                     => $estados,
            'server_time'                 => now()->timestamp,
        ]);
    }

    /**
     * Envia texto o un adjunto (imagen o PDF). El adjunto se guarda en el
     * disco publico del negocio y a Meta se le pasa la URL: la Graph API
     * descarga el archivo desde ahi, por eso tiene que ser publica.
     */
    public function enviar(Request $request, WaConversacion $conversacion)
    {
        $this->autorizar($conversacion);
        $data = $request->validate([
            'contenido' => 'nullable|string|max:4096',
            'archivo'   => 'nullable|file|max:20480|mimes:jpg,jpeg,png,webp,pdf,mp3,ogg,oga,opus,m4a,aac,amr,webm',
        ]);
        if (! $request->hasFile('archivo') && trim((string) ($data['contenido'] ?? '')) === '') {
            return response()->json(['ok' => false, 'error' => 'Escribe un mensaje o adjunta un archivo.'], 422);
        }

        $respuesta = (string) ($data['contenido'] ?? '');
        $tipo = 'texto'; $mediaUrl = null; $contenido = $respuesta;
        if ($request->hasFile('archivo')) {
            $archivo = $request->file('archivo');
            $mime = (string) $archivo->getMimeType();
            $esImagen = str_starts_with($mime, 'image/');
            $esAudio  = str_starts_with($mime, 'audio/') || str_starts_with($mime, 'video/webm');
            // Nota de voz grabada en el navegador: llega como webm/opus, que
            // WhatsApp no acepta. Se convierte a ogg/opus con ffmpeg si el
            // servidor lo tiene; si no, se avisa en vez de mandar algo que
            // Meta rechazaria.
            if ($esAudio && str_contains($mime, 'webm')) {
                $convertido = $this->convertirAOgg($archivo->getRealPath());
                if ($convertido === null) {
                    return response()->json(['ok' => false, 'error' => 'Este servidor no puede convertir la grabación (falta ffmpeg). Adjunta un mp3 u ogg.'], 422);
                }
                $ruta = 'wa/' . $conversacion->canal->project_id . '/nota-' . uniqid() . '.ogg';
                Storage::disk('public')->put($ruta, file_get_contents($convertido));
                @unlink($convertido);
            } else {
                $ruta = $archivo->store('wa/' . $conversacion->canal->project_id, 'public');
            }
            $mediaUrl = Storage::disk('public')->url($ruta);
            $tipo = $esImagen ? 'imagen' : ($esAudio ? 'audio' : 'documento');
            $nombre = $archivo->getClientOriginalName();
            $contenido = $respuesta !== '' ? $respuesta : ($esAudio ? '🎤 Nota de voz' : $nombre);
            $respuesta = match ($tipo) {
                'imagen' => ['tipo' => 'imagen', 'url' => $mediaUrl, 'caption' => $data['contenido'] ?? ''],
                'audio'  => ['tipo' => 'audio', 'url' => $mediaUrl],
                default  => ['tipo' => 'archivo', 'url' => $mediaUrl, 'caption' => $data['contenido'] ?? '', 'nombre' => $nombre],
            };
        }

        return $this->despachar($conversacion, $respuesta, $tipo, $contenido, $mediaUrl);
    }

    /** webm/opus -> ogg/opus con ffmpeg. Devuelve la ruta temporal o null si no hay ffmpeg o fallo. */
    private function convertirAOgg(string $origen): ?string
    {
        $ffmpeg = trim((string) shell_exec('command -v ffmpeg 2>/dev/null'));
        if ($ffmpeg === '') {
            return null;
        }
        $destino = sys_get_temp_dir() . '/bixo-nota-' . uniqid() . '.ogg';
        shell_exec(escapeshellcmd($ffmpeg) . ' -y -i ' . escapeshellarg($origen) . ' -vn -c:a libopus -b:a 32k ' . escapeshellarg($destino) . ' 2>/dev/null');

        return is_file($destino) && filesize($destino) > 0 ? $destino : null;
    }

    /** Reenvia un mensaje de esta conversacion (texto o adjunto) a otra del mismo negocio. */
    public function reenviar(Request $request, WaConversacion $conversacion)
    {
        $this->autorizar($conversacion);
        $data = $request->validate([
            'mensaje_id' => 'required|integer',
            'destino_id' => 'required|integer',
        ]);
        $mensaje = $conversacion->mensajes()->findOrFail($data['mensaje_id']);
        $destino = WaConversacion::findOrFail($data['destino_id']);
        $this->autorizar($destino);

        $respuesta = match ($mensaje->tipo) {
            'imagen'    => ['tipo' => 'imagen', 'url' => $mensaje->media_url, 'caption' => ''],
            'audio'     => ['tipo' => 'audio', 'url' => $mensaje->media_url],
            'documento' => ['tipo' => 'archivo', 'url' => $mensaje->media_url, 'caption' => '', 'nombre' => basename((string) $mensaje->media_url)],
            default     => (string) $mensaje->contenido,
        };
        if (in_array($mensaje->tipo, ['imagen', 'documento', 'audio'], true) && ! $mensaje->media_url) {
            return response()->json(['ok' => false, 'error' => 'Este adjunto no tiene archivo descargado para reenviar.'], 422);
        }

        return $this->despachar($destino, $respuesta, $mensaje->tipo, (string) $mensaje->contenido, $mensaje->media_url);
    }

    /**
     * Manda por Meta y deja constancia en el historial. El envio pasa por
     * ClienteCloud, el mismo que usa el bot: una sola implementacion de la
     * Graph API para el canal automatico y el humano. Un fallo NO puede pasar
     * desapercibido: se devuelve con 502 y el motivo de Meta, que suele ser
     * accionable (token vencido, ventana de 24 h cerrada).
     */
    private function despachar(WaConversacion $conversacion, string|array $respuesta, string $tipo, string $contenido, ?string $mediaUrl)
    {
        $canal = $conversacion->canal;
        $waMessageId = null;
        $fallo = null;

        if ($canal->conectadoAMeta()) {
            $res = (new \App\Modules\Crm\Support\WhatsappCloud\ClienteCloud($canal))
                ->enviarUna($conversacion->cliente_telefono, $respuesta);

            $waMessageId = $res['id'] ?? null;
            $fallo = ($res['ok'] ?? false) ? null : ($res['error'] ?? 'No se pudo enviar.');
        } else {
            $fallo = 'Este canal aún no está conectado con WhatsApp.';
        }

        $mensaje = $conversacion->mensajes()->create([
            'wa_message_id' => $waMessageId,
            'direccion'     => 'saliente',
            'tipo'          => $tipo,
            'contenido'     => $contenido,
            'media_url'     => $mediaUrl,
            'estado'        => $waMessageId ? 'enviado' : 'pendiente',
        ]);

        $conversacion->update([
            'ultimo_mensaje_at' => now(),
            'estado' => $conversacion->estado === 'nuevo' ? 'contactado' : $conversacion->estado,
        ]);

        return response()->json([
            'ok'      => $fallo === null,
            'mensaje' => $mensaje,
            'error'   => $fallo,
        ], $fallo === null ? 200 : 502);
    }

    public function actualizar(Request $request, WaConversacion $conversacion)
    {
        $this->autorizar($conversacion);
        $data = $request->validate([
            'estado'           => 'nullable|in:nuevo,contactado,demo_enviada,propuesta,cerrado,perdido,academia',
            'notas'            => 'nullable|string',
            'archivado'        => 'nullable|boolean',
            'fijada'           => 'nullable|boolean',
            'asignado_a'       => 'nullable|string|max:100',
            'no_leidos'        => 'nullable|integer|min:0|max:1', // 1 = marcar como no leida
            'cliente_nombre'   => 'nullable|string|max:100',
            'cliente_sector'   => 'nullable|string|max:80',
            'cliente_distrito' => 'nullable|string|max:80',
        ]);
        $cambios = array_filter($data, fn($v) => !is_null($v));
        if ($request->has('asignado_a')) $cambios['asignado_a'] = $data['asignado_a']; // null = sin asignar
        $conversacion->update($cambios);
        return response()->json(['ok' => true]);
    }

    /**
     * Eliminar chat: borra la conversacion, sus mensajes y los adjuntos que
     * BIXO guardo en disco. No toca el telefono del cliente (Meta no permite
     * borrar en el otro extremo); es "eliminar de mi bandeja".
     */
    public function eliminar(WaConversacion $conversacion)
    {
        $this->autorizar($conversacion);
        foreach ($conversacion->mensajes()->whereNotNull('media_url')->pluck('media_url') as $url) {
            $this->borrarAdjunto($url);
        }
        $conversacion->mensajes()->delete();
        $conversacion->delete();

        return response()->json(['ok' => true]);
    }

    /** Eliminar un mensaje del historial (solo de la bandeja). */
    public function eliminarMensaje(WaConversacion $conversacion, int $mensaje)
    {
        $this->autorizar($conversacion);
        $m = $conversacion->mensajes()->findOrFail($mensaje);
        if ($m->media_url) {
            $this->borrarAdjunto($m->media_url);
        }
        $m->delete();

        return response()->json(['ok' => true]);
    }

    /** Solo borra archivos del disco publico de ESTE negocio (wa/{project}/...). */
    private function borrarAdjunto(string $url): void
    {
        $prefijo = '/storage/wa/' . session('comunicaciones_project_id') . '/';
        $pos = strpos($url, $prefijo);
        if ($pos === false) {
            return;
        }
        Storage::disk('public')->delete('wa/' . session('comunicaciones_project_id') . '/' . substr($url, $pos + strlen($prefijo)));
    }

    private function autorizar(WaConversacion $conv): void
    {
        $ids = WaCanal::where('project_id', session('comunicaciones_project_id'))->pluck('id');
        abort_unless($ids->contains($conv->wa_canal_id), 403);
    }
}
