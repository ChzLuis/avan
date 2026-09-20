<?php

namespace App\Modules\Bots\Controllers;

use App\Modules\Bots\Support\FlowEngine\FlowRunner;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\WaCanal;
use App\Modules\Crm\Models\WaConversacion;
use App\Modules\Crm\Models\WaMensaje;
use App\Modules\Crm\Support\WhatsappCloud\ClienteCloud;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Webhook de la WhatsApp Cloud API (Meta) → motor de bots de BIXO.
 *
 * A diferencia de Baileys (que pregunta "que respondo" y envia el mismo), aqui
 * Meta solo AVISA que llego un mensaje: BIXO decide y envia por la Graph API.
 * El motor (FlowRunner) es el mismo, asi que los flujos del constructor sirven
 * en los dos canales sin cambios.
 *
 * Las credenciales salen de `WaCanal`, propietario unico de la linea de
 * WhatsApp del negocio (ver MODULE_OWNERSHIP): la bandeja de Comunicaciones
 * envia con las mismas, de modo que bot y asesor comparten numero e historial.
 *
 * SEGURIDAD: la URL del webhook es publica por diseño (Meta la llama sin
 * token). Lo que autentica cada llamada es la firma HMAC del cuerpo con el
 * App Secret. Sin esa comprobacion, cualquiera podria inyectar mensajes
 * falsos al bot y hacerlo responder, cotizar o registrar leads en nombre de
 * un cliente: por eso una firma invalida se descarta ANTES de tocar el motor.
 */
class WhatsappCloudWebhookController extends Controller
{
    /**
     * Handshake de verificacion. Meta llama una sola vez al dar de alta la URL
     * y espera que le devolvamos el hub.challenge EN TEXTO PLANO.
     */
    public function verificar(Request $r): Response
    {
        $modo      = (string) $r->query('hub_mode');
        $token     = (string) $r->query('hub_verify_token');
        $challenge = (string) $r->query('hub_challenge');

        if ($modo !== 'subscribe' || $token === '') {
            return response('Solicitud inválida.', 400);
        }

        // El verify_token se compara contra TODAS las lineas configuradas
        // (Meta no dice a que proyecto pertenece en el handshake). La
        // comparacion es de tiempo constante para no filtrar el token.
        $existe = WaCanal::query()
            ->whereNotNull('verify_token')
            ->get(['verify_token'])
            ->contains(fn ($c) => hash_equals((string) $c->verify_token, $token));

        if (! $existe) {
            Log::warning('wa_cloud.verificacion_rechazada', ['ip' => $r->ip()]);

            return response('Token de verificación inválido.', 403);
        }

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Recibe los eventos de mensajes. SIEMPRE responde 200: si devolvemos
     * error, Meta reintenta el mismo evento y el cliente recibe respuestas
     * duplicadas. Los problemas se registran, no se propagan.
     */
    public function recibir(Request $r): JsonResponse
    {
        $cuerpo = $r->getContent();

        // Se lee del cuerpo CRUDO, el mismo que se firma. Depender de
        // $r->input() ataba el webhook a que llegara el Content-Type correcto:
        // sin el, Laravel no parsea el JSON y el evento se perdia en silencio
        // (200 para Meta, cliente sin respuesta y nada en el log).
        $datos = json_decode($cuerpo, true);
        if (! is_array($datos)) {
            Log::warning('wa_cloud.cuerpo_ilegible', ['ip' => $r->ip(), 'bytes' => strlen($cuerpo)]);

            return response()->json(['recibido' => true]);
        }

        foreach (($datos['entry'] ?? []) as $entrada) {
            foreach (($entrada['changes'] ?? []) as $cambio) {
                $valor = $cambio['value'] ?? [];

                // La linea que recibio el mensaje identifica al negocio.
                $phoneNumberId = (string) data_get($valor, 'metadata.phone_number_id', '');
                $canal = $phoneNumberId === '' ? null : WaCanal::query()
                    ->where('phone_number_id', $phoneNumberId)
                    ->where('activo', true)
                    ->first();

                if (! $canal) {
                    // Linea desconocida o desactivada: nada que hacer.
                    continue;
                }

                if (! $this->firmaValida($r, $cuerpo, $canal)) {
                    Log::warning('wa_cloud.firma_invalida', [
                        'proyecto' => $canal->project_id,
                        'ip'       => $r->ip(),
                    ]);
                    continue;
                }

                // Acuses de entrega/lectura/fallo: actualizan los checks de la bandeja.
                if (! empty($valor['statuses'])) {
                    $this->procesarAcuses($canal, $valor['statuses']);
                    continue;
                }

                foreach (($valor['messages'] ?? []) as $mensaje) {
                    $this->procesarMensaje($canal, $valor, $mensaje);
                }
            }
        }

        return response()->json(['recibido' => true]);
    }

    /**
     * Comprueba la firma HMAC-SHA256 del cuerpo con el App Secret.
     * Sin app_secret configurado NO se acepta el evento: un webhook sin firmar
     * es una puerta abierta al bot, y fallar cerrado es lo correcto aqui.
     */
    private function firmaValida(Request $r, string $cuerpo, WaCanal $canal): bool
    {
        $secreto = (string) $canal->app_secret;
        if ($secreto === '') {
            return false;
        }

        $cabecera = (string) $r->header('X-Hub-Signature-256', '');
        if (! str_starts_with($cabecera, 'sha256=')) {
            return false;
        }

        $esperada = 'sha256=' . hash_hmac('sha256', $cuerpo, $secreto);

        return hash_equals($esperada, $cabecera);
    }

    /**
     * Normaliza un mensaje de Meta al vocabulario del motor y responde.
     *
     * Traduce los tipos de Meta a los que el BotWebhookController ya entiende
     * (imagen, audio, video, documento, ubicacion, contacto) para que el
     * manejo de adjuntos se comporte igual en los dos canales.
     */
    private function procesarMensaje(WaCanal $canal, array $valor, array $mensaje): void
    {
        $telefono = preg_replace('/\D/', '', (string) ($mensaje['from'] ?? ''));
        $waId     = (string) ($mensaje['id'] ?? '');
        if ($telefono === '') {
            return;
        }

        // IDEMPOTENCIA: Meta reentrega el mismo evento si tarda la respuesta.
        // Cache::add es atomico, asi que la copia no vuelve a contestar.
        if ($waId !== '') {
            $clave = "wa_cloud_in_{$canal->project_id}_{$waId}";
            if (! Cache::add($clave, 1, 300)) {
                return;
            }
        }

        $nombre = (string) data_get($valor, 'contacts.0.profile.name', $telefono);
        [$texto, $tipo] = $this->extraerTexto($mensaje);
        // Lo que el asesor ve en la bandeja: el titulo del boton tocado, no su id.
        $textoVisible = $this->textoVisible($mensaje, $texto);

        // ANUNCIOS click-to-WhatsApp: cuando el cliente abre el chat desde un
        // anuncio, Meta adjunta AQUI el `referral` con el anuncio que lo trajo.
        // Viaja solo en el primer mensaje de esa ventana, asi que si no se lee
        // ahora se pierde y la conversacion queda sin atribucion para siempre.
        $referral = is_array($mensaje['referral'] ?? null) ? $mensaje['referral'] : null;

        // Una reaccion (un 👍 sobre un mensaje anterior) no abre conversacion.
        // Un texto vacio tampoco es nada que responder.
        if ($tipo === 'ignorar' || ($texto === '' && $tipo === 'texto')) {
            return;
        }

        // Fotos, audios, videos y documentos del cliente: se descargan al disco
        // del negocio para que la bandeja los muestre (y el asesor los oiga).
        $mediaUrl = null;
        $claveMeta = ['imagen' => 'image', 'audio' => 'audio', 'video' => 'video', 'documento' => 'document'][$tipo] ?? null;
        if ($claveMeta !== null) {
            $nombreArchivo = (string) data_get($mensaje, 'document.filename', '');
            $mediaUrl = ClienteCloud::descargarMedio($canal, (string) data_get($mensaje, "{$claveMeta}.id", ''), $nombreArchivo ?: null);
            if ($texto === '') {
                $texto = match ($tipo) {
                    'imagen'    => '📷 Imagen',
                    'audio'     => '🎤 Audio',
                    'video'     => '🎬 Video',
                    default     => '📄 ' . ($nombreArchivo ?: 'Documento'),
                };
            }
        }

        // Ubicacion y contacto compartidos: quedan legibles y con enlace al mapa.
        if ($tipo === 'ubicacion') {
            $lat = data_get($mensaje, 'location.latitude');
            $lng = data_get($mensaje, 'location.longitude');
            $textoVisible = trim('📍 ' . (data_get($mensaje, 'location.name') ?: 'Ubicación compartida') . ' ' . (data_get($mensaje, 'location.address') ?: ''));
            if ($lat !== null && $lng !== null) {
                $mediaUrl = "https://www.google.com/maps?q={$lat},{$lng}";
            }
        } elseif ($tipo === 'contacto') {
            $partes = [];
            foreach ((array) ($mensaje['contacts'] ?? []) as $c) {
                $tel = data_get($c, 'phones.0.wa_id') ?: data_get($c, 'phones.0.phone', '');
                $partes[] = trim((string) data_get($c, 'name.formatted_name', '') . ($tel ? ' · +' . ltrim((string) $tel, '+') : ''));
            }
            $textoVisible = '👤 ' . (implode(', ', array_filter($partes)) ?: 'Contacto compartido');
        }

        try {
            $respuestas = $this->ejecutarMotor($canal, $telefono, $nombre, $texto, $tipo, $mediaUrl, $referral, $textoVisible);
        } catch (\Throwable $e) {
            Log::error('wa_cloud.motor_fallo', [
                'proyecto' => $canal->project_id,
                'error'    => class_basename($e),
                'detalle'  => mb_substr($e->getMessage(), 0, 200),
            ]);

            return;
        }

        // Ojo: el visto azul NO se manda aqui cuando el chat lo lleva una persona.
        // Marcarlo al recibir seria mentirle al cliente ("leido" sin que nadie lo
        // haya visto). Lo hace la bandeja al abrir la conversacion. El bot si lo
        // marca, porque va a contestar en el acto.
        if ($respuestas === []) {
            return;
        }

        $cliente = new ClienteCloud($canal);
        if ($waId !== '') {
            $cliente->marcarLeido($waId);
        }

        $resultados = $cliente->enviarRespuestas($telefono, $respuestas);
        $this->anotarEnvios($canal, $telefono, $resultados);
    }

    /**
     * Guarda en cada mensaje saliente del bot el id que le dio Meta (para que
     * los acuses lo encuentren) o el motivo si Meta lo rechazo.
     */
    private function anotarEnvios(WaCanal $canal, string $telefono, array $resultados): void
    {
        if ($resultados === []) {
            return;
        }
        $canales = WaCanal::where('project_id', $canal->project_id)->pluck('id');
        $conv = WaConversacion::whereIn('wa_canal_id', $canales)->where('cliente_telefono', $telefono)->first();
        if (! $conv) {
            return;
        }
        $salientes = WaMensaje::where('wa_conversacion_id', $conv->id)->whereIn('direccion', ['out', 'saliente'])
            ->whereNull('wa_message_id')->where('estado', 'enviado')
            ->orderByDesc('id')->limit(count($resultados))->get()->reverse()->values();
        foreach ($salientes as $i => $m) {
            $r = $resultados[$i] ?? null;
            if (! $r) {
                continue;
            }
            if (! empty($r['ok'])) {
                $idMeta = (string) ($r['id'] ?? '');
                // El id es unico en la tabla: si ya existe (reenvio o fake), se deja sin anotar.
                if ($idMeta === '' || WaMensaje::where('wa_message_id', $idMeta)->exists()) {
                    continue;
                }
                $m->forceFill(['wa_message_id' => $idMeta])->save();
            } else {
                $m->forceFill(['estado' => 'fallido', 'error' => mb_substr((string) ($r['error'] ?? 'Meta rechazó el envío'), 0, 255)])->save();
            }
        }
    }

    /** Acuses de Meta: sent -> delivered -> read, o failed con su motivo. */
    private function procesarAcuses(WaCanal $canal, array $acuses): void
    {
        $orden = ['enviado' => 1, 'entregado' => 2, 'leido' => 3];
        foreach ($acuses as $a) {
            $id = (string) ($a['id'] ?? '');
            $estado = ['sent' => 'enviado', 'delivered' => 'entregado', 'read' => 'leido', 'failed' => 'fallido'][$a['status'] ?? ''] ?? null;
            if ($id === '' || $estado === null) {
                continue;
            }
            $m = WaMensaje::where('wa_message_id', $id)->first();
            if (! $m) {
                continue;
            }
            $datos = [];
            if ($estado === 'fallido') {
                $codigo = (int) data_get($a, 'errors.0.code');
                $motivo = match ($codigo) {
                    131047  => 'Pasaron más de 24 h desde el último mensaje del cliente: solo se puede escribir con una plantilla aprobada',
                    131026  => 'El número no tiene WhatsApp o bloqueó la línea',
                    131049, 131048 => 'Meta limitó los envíos a este número por ahora',
                    default => (string) (data_get($a, 'errors.0.error_data.details') ?: data_get($a, 'errors.0.title') ?: 'Meta rechazó el envío'),
                };
                $datos = ['estado' => 'fallido', 'error' => mb_substr($motivo, 0, 255)];
                $canal->forceFill(['ultimo_error' => mb_substr($motivo, 0, 255)])->saveQuietly();
            } elseif (($orden[$estado] ?? 0) > ($orden[$m->estado] ?? 0)) {
                // Nunca retroceder: un "delivered" tardio no pisa un "read".
                $datos = ['estado' => $estado];
                if ($estado === 'entregado') $datos['entregado_at'] = now();
                if ($estado === 'leido') $datos['leido_at'] = now();
            }
            if ($datos !== []) {
                $m->forceFill($datos)->save();
            }
        }
    }

    /** Titulo del boton o fila elegida; para todo lo demas, el texto tal cual. */
    private function textoVisible(array $m, string $texto): string
    {
        if (($m['type'] ?? '') !== 'interactive') {
            return $texto;
        }
        $titulo = (string) (data_get($m, 'interactive.button_reply.title') ?? data_get($m, 'interactive.list_reply.title') ?? '');

        return $titulo !== '' ? '👆 ' . $titulo : $texto;
    }

    /**
     * Saca el texto y el tipo de un mensaje de Meta.
     *
     * Las respuestas a listas y botones llegan como 'interactive' y lo que
     * importa es el ID de la fila elegida: es el valor que el FlowRunner
     * compara para saber que opcion tomo el cliente. El titulo visible no
     * sirve (Meta lo recorta a 24 caracteres).
     */
    private function extraerTexto(array $m): array
    {
        $tipo = (string) ($m['type'] ?? '');

        return match ($tipo) {
            'text'        => [(string) data_get($m, 'text.body', ''), 'texto'],
            'interactive' => [
                (string) (data_get($m, 'interactive.list_reply.id')
                    ?? data_get($m, 'interactive.button_reply.id')
                    ?? data_get($m, 'interactive.list_reply.title')
                    ?? data_get($m, 'interactive.button_reply.title')
                    ?? ''),
                'texto',
            ],
            // Botones de plantilla: el texto del boton es la respuesta.
            'button'      => [(string) data_get($m, 'button.payload', data_get($m, 'button.text', '')), 'texto'],
            'image'       => [(string) data_get($m, 'image.caption', ''), 'imagen'],
            'audio'       => ['', 'audio'],
            'video'       => [(string) data_get($m, 'video.caption', ''), 'video'],
            'document'    => [(string) data_get($m, 'document.caption', ''), 'documento'],
            'location'    => ['', 'ubicacion'],
            'contacts'    => ['', 'contacto'],
            // Un sticker es un mensaje deliberado del cliente: el bot contesta
            // que no lo entiende en vez de quedarse mudo. La REACCION, en
            // cambio, es un gesto sobre un mensaje viejo y se ignora a
            // proposito (responderla seria ruido en la conversacion).
            'sticker'     => ['', 'otro'],
            'reaction'    => ['', 'ignorar'],
            default       => ['', 'otro'],
        };
    }

    /**
     * Ejecuta el motor de bots reutilizando el controlador de Baileys.
     *
     * Se le pasa una peticion interna con el token del proyecto: asi la logica
     * de CRM, reglas de silencio, disparos, fusion de identidad e idempotencia
     * vive en UN solo sitio y los dos canales se comportan igual.
     */
    private function ejecutarMotor(
        WaCanal $canal,
        string $telefono,
        string $nombre,
        string $texto,
        string $tipo,
        ?string $mediaUrl = null,
        ?array $referral = null,
        ?string $textoVisible = null,
    ): array {
        $proyecto = $canal->project;
        if (! $proyecto) {
            return [];
        }

        $peticion = Request::create('/api/bot/inbound', 'POST', [
            'telefono'      => $telefono,
            'mensaje'       => $texto !== '' ? $texto : '.',
            'nombre'        => $nombre,
            'tipo'          => $tipo,
            // El id ya se consumio en la idempotencia de este webhook; pasarlo
            // otra vez haria que el motor lo tomara por duplicado y callara.
            'wa_message_id' => null,
            // El canal por el que entro: el CRM debe archivar la conversacion
            // en la linea de Meta, no en el canal 'Bot WhatsApp' de Baileys,
            // o la bandeja no podra responder ('no esta conectado').
            'wa_canal_id'   => $canal->id,
            'media_url'     => $mediaUrl,
            // Lo que se guarda en la bandeja cuando difiere de lo que lee el motor.
            ...($textoVisible !== null && $textoVisible !== $texto ? ['texto_visible' => $textoVisible] : []),
            // Solo se manda cuando existe: una clave en null ensuciaria la
            // validacion y quedaria guardada como atribucion vacia.
            ...($referral !== null ? ['referral' => $referral] : []),
        ]);
        $peticion->headers->set('X-Copilot-Token', (string) $proyecto->copilot_token);

        $respuesta = app(BotWebhookController::class)->inbound($peticion);
        $datos = json_decode($respuesta->getContent(), true) ?: [];

        return $datos['respuestas'] ?? [];
    }
}
