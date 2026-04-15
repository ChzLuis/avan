<?php

namespace App\Http\Controllers;

use App\Models\WaCanal;
use App\Models\WaChatbotFlow;
use App\Models\WaConversacion;
use App\Models\WaMensaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaWebhookController extends Controller
{
    // ── Verificación del webhook (GET) — Meta lo llama al configurar ─────────

    public function verify(Request $request, string $slug)
    {
        $canal = $this->canalPorSlug($slug);
        if (!$canal) return response('Not found', 404);

        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === $canal->verify_token) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    // ── Recepción de mensajes (POST) ─────────────────────────────────────────

    public function receive(Request $request, string $slug)
    {
        // Responder 200 inmediatamente (Meta requiere respuesta rápida)
        $canal = $this->canalPorSlug($slug);
        if (!$canal) return response('OK', 200);

        try {
            $body = $request->all();

            if (($body['object'] ?? '') !== 'whatsapp_business_account') {
                return response('OK', 200);
            }

            foreach ($body['entry'] ?? [] as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    $value = $change['value'] ?? [];

                    // Actualizar estado de mensajes enviados
                    foreach ($value['statuses'] ?? [] as $status) {
                        $this->procesarEstado($status);
                    }

                    // Procesar mensajes entrantes
                    foreach ($value['messages'] ?? [] as $waMsg) {
                        $contacto = collect($value['contacts'] ?? [])->firstWhere('wa_id', $waMsg['from']);
                        $this->procesarMensaje($canal, $waMsg, $contacto);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('WA Webhook error: ' . $e->getMessage(), ['exception' => $e]);
        }

        return response('OK', 200);
    }

    // ── Privado ──────────────────────────────────────────────────────────────

    private function canalPorSlug(string $slug): ?WaCanal
    {
        // Buscar por verify_token (la URL usa el token como slug por seguridad)
        return WaCanal::where('verify_token', $slug)->where('activo', true)->first();
    }

    private function procesarMensaje(WaCanal $canal, array $waMsg, ?array $contacto): void
    {
        $telefono = $waMsg['from'];
        $nombre   = $contacto['profile']['name'] ?? null;

        // Obtener o crear conversación
        $conversacion = WaConversacion::firstOrCreate(
            ['wa_canal_id' => $canal->id, 'cliente_telefono' => $telefono],
            [
                'cliente_nombre'  => $nombre,
                'origen_anuncio'  => WaConversacion::detectarOrigen(
                    $this->extraerContenido($waMsg), $canal->tipo
                ),
                'estado'          => 'nuevo',
                'ultimo_mensaje_at' => now(),
            ]
        );

        $esNueva = $conversacion->wasRecentlyCreated;

        // Actualizar nombre si cambió
        if ($nombre && !$conversacion->cliente_nombre) {
            $conversacion->update(['cliente_nombre' => $nombre]);
        }

        // Evitar duplicados
        $waMessageId = $waMsg['id'];
        if (WaMensaje::where('wa_message_id', $waMessageId)->exists()) return;

        $contenido = $this->extraerContenido($waMsg);
        $tipo      = $this->extraerTipo($waMsg);

        // Guardar mensaje
        WaMensaje::create([
            'wa_conversacion_id' => $conversacion->id,
            'wa_message_id'      => $waMessageId,
            'direccion'          => 'entrante',
            'tipo'               => $tipo,
            'contenido'          => $contenido,
            'media_url'          => $this->extraerMediaUrl($waMsg),
            'estado'             => 'recibido',
        ]);

        // Actualizar conversación
        $conversacion->increment('no_leidos');
        $conversacion->update(['ultimo_mensaje_at' => now()]);

        // ── Chatbot ──────────────────────────────────────────────────────────────
        $this->ejecutarChatbot($canal, $conversacion, $contenido, $esNueva);
    }

    private function ejecutarChatbot(WaCanal $canal, WaConversacion $conversacion, string $contenido, bool $esNueva): void
    {
        // No responder si el bot fue desactivado manualmente para esta conversación
        if (!($conversacion->bot_activo ?? true)) return;

        $flows = WaChatbotFlow::where('wa_canal_id', $canal->id)
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        if ($flows->isEmpty()) return;

        $respuesta = null;

        // 1. Si es conversación nueva → buscar flow de bienvenida
        if ($esNueva) {
            $flowBienvenida = $flows->firstWhere('es_bienvenida', true);
            if ($flowBienvenida) {
                $respuesta = $flowBienvenida->response_text;
            }
        }

        // 2. Si no hubo bienvenida (o no es nueva), buscar por keywords
        if (!$respuesta) {
            foreach ($flows->where('es_bienvenida', false) as $flow) {
                if ($flow->coincide($contenido)) {
                    $respuesta = $flow->response_text;
                    break;
                }
            }
        }

        if (!$respuesta) return;

        // Enviar el mensaje por la API de Meta
        $waMessageId = null;
        if ($canal->phone_number_id && $canal->access_token) {
            try {
                $res = Http::withToken($canal->access_token)
                    ->post("https://graph.facebook.com/v18.0/{$canal->phone_number_id}/messages", [
                        'messaging_product' => 'whatsapp',
                        'recipient_type'    => 'individual',
                        'to'                => $conversacion->cliente_telefono,
                        'type'              => 'text',
                        'text'              => ['body' => $respuesta],
                    ]);
                $waMessageId = $res->json('messages.0.id');
            } catch (\Throwable $e) {
                Log::error('Chatbot send error: ' . $e->getMessage());
            }
        }

        // Guardar el mensaje enviado por el bot
        WaMensaje::create([
            'wa_conversacion_id' => $conversacion->id,
            'wa_message_id'      => $waMessageId,
            'direccion'          => 'saliente',
            'tipo'               => 'texto',
            'contenido'          => $respuesta,
            'estado'             => $waMessageId ? 'enviado' : 'pendiente',
        ]);

        $conversacion->update(['ultimo_mensaje_at' => now()]);
    }

    private function procesarEstado(array $status): void
    {
        $waMessageId = $status['id'] ?? null;
        if (!$waMessageId) return;

        $estado = match($status['status'] ?? '') {
            'delivered' => 'entregado',
            'read'      => 'leido',
            'failed'    => 'fallido',
            default     => null,
        };

        if (!$estado) return;

        $update = ['estado' => $estado];
        if ($estado === 'entregado') $update['entregado_at'] = now();
        if ($estado === 'leido')     $update['leido_at']     = now();

        WaMensaje::where('wa_message_id', $waMessageId)->update($update);
    }

    private function extraerContenido(array $waMsg): string
    {
        return match($waMsg['type'] ?? 'text') {
            'text'     => $waMsg['text']['body'] ?? '',
            'image'    => '[Imagen]' . ($waMsg['image']['caption'] ?? ''),
            'audio'    => '[Audio]',
            'document' => '[Documento: ' . ($waMsg['document']['filename'] ?? '') . ']',
            'sticker'  => '[Sticker]',
            'location' => '[Ubicación: ' . ($waMsg['location']['name'] ?? '') . ']',
            default    => '[Mensaje]',
        };
    }

    private function extraerTipo(array $waMsg): string
    {
        return match($waMsg['type'] ?? 'text') {
            'text'     => 'texto',
            'image'    => 'imagen',
            'audio'    => 'audio',
            'document' => 'documento',
            default    => 'texto',
        };
    }

    private function extraerMediaUrl(array $waMsg): ?string
    {
        $tipo = $waMsg['type'] ?? 'text';
        return $waMsg[$tipo]['id'] ?? null; // ID de media de WhatsApp
    }
}
