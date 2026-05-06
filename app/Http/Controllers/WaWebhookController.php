<?php

namespace App\Http\Controllers;

use App\Models\BotFlow;
use App\Models\BotSession;
use App\Models\WaCanal;
use App\Models\WaChatbotFlow;
use App\Models\WaConversacion;
use App\Models\WaMensaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaWebhookController extends Controller
{
    // ── Verificación directa Meta (/whatsapp/webhook GET) ────────────────────
    public function verifyMeta(Request $request)
    {
        $mode      = $request->query('hub_mode') ?: $request->query('hub.mode');
        $token     = $request->query('hub_verify_token') ?: $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?: $request->query('hub.challenge');

        $verifyToken = config('services.meta.verify_token', env('META_VERIFY_TOKEN', 'meta-bot-suerte-2024'));

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Meta webhook verificado OK');
            return response($challenge, 200);
        }

        Log::warning('Meta webhook verificación fallida', ['token' => $token]);
        return response('Forbidden', 403);
    }

    // ── Recepción directa Meta (/whatsapp/webhook POST) ───────────────────────
    public function receiveMeta(Request $request)
    {
        try {
            $body = json_decode($request->getContent(), true) ?? [];

            Log::info('Meta webhook recibido', ['object' => $body['object'] ?? 'none', 'entries' => count($body['entry'] ?? [])]);

            if (($body['object'] ?? '') !== 'whatsapp_business_account') {
                return response('OK', 200);
            }

            foreach ($body['entry'] ?? [] as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    $value    = $change['value'] ?? [];
                    $statuses = $value['statuses'] ?? [];
                    $messages = $value['messages'] ?? [];

                    // Obtener phone_number_id del payload de Meta
                    $phoneNumberId = $value['metadata']['phone_number_id'] ?? '';
                    Log::info('Meta webhook change', ['phone_number_id' => $phoneNumberId, 'msgs' => count($messages)]);

                    // Buscar canal en BD usando el phone_number_id del payload
                    $canal = WaCanal::where('phone_number_id', $phoneNumberId)->first();
                    $accessToken = $canal ? $canal->makeVisible('access_token')->access_token : '';
                    $projectId   = $canal?->project_id;

                    foreach ($statuses as $status) {
                        $this->procesarEstado($status);
                    }

                    foreach ($messages as $waMsg) {
                        $contacto = collect($value['contacts'] ?? [])->firstWhere('wa_id', $waMsg['from']);
                        $this->procesarMensajeMeta($waMsg, $contacto, $phoneNumberId, $accessToken, $projectId, $canal);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('Meta webhook error: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
        }

        return response('OK', 200);
    }

    private function procesarMensajeMeta(array $waMsg, ?array $contacto, string $phoneNumberId, string $accessToken, ?int $projectId, ?WaCanal $canal = null): void
    {
        $telefono  = $waMsg['from'];
        $nombre    = $contacto['profile']['name'] ?? null;
        $contenido = $this->extraerContenido($waMsg);

        Log::info('Meta mensaje entrante', ['from' => $telefono, 'contenido' => $contenido]);

        // Evitar duplicados
        if (WaMensaje::where('wa_message_id', $waMsg['id'])->exists()) return;

        // Usar canal existente o crear uno nuevo
        if (!$canal) {
            $canal = WaCanal::firstOrCreate(
                ['phone_number_id' => $phoneNumberId],
                [
                    'project_id'   => $projectId ?? 1,
                    'nombre'       => 'Meta Bot',
                    'tipo'         => 'whatsapp_business',
                    'access_token' => $accessToken,
                    'verify_token' => env('META_VERIFY_TOKEN', 'meta-bot-suerte-2024'),
                    'bot_type'     => 'rifa',
                    'activo'       => true,
                ]
            );
        }

        // Buscar o crear conversación
        $conversacion = WaConversacion::firstOrCreate(
            ['wa_canal_id' => $canal->id, 'cliente_telefono' => $telefono],
            ['cliente_nombre' => $nombre, 'estado' => 'nuevo', 'ultimo_mensaje_at' => now()]
        );

        $esNueva = $conversacion->wasRecentlyCreated;
        if ($nombre && !$conversacion->cliente_nombre) {
            $conversacion->update(['cliente_nombre' => $nombre]);
        }

        // Guardar mensaje entrante
        WaMensaje::create([
            'wa_conversacion_id' => $conversacion->id,
            'wa_message_id'      => $waMsg['id'],
            'direccion'          => 'entrante',
            'tipo'               => $this->extraerTipo($waMsg),
            'contenido'          => $contenido,
            'estado'             => 'recibido',
        ]);

        $conversacion->increment('no_leidos');
        $conversacion->update(['ultimo_mensaje_at' => now()]);

        // Ejecutar flujo de estados (también imágenes, para capturar comprobantes de pago)
        $tipo = $waMsg['type'] ?? 'text';
        if ($contenido && (!str_starts_with($contenido, '[') || $tipo === 'image')) {
            $this->ejecutarChatbot($canal, $conversacion, $contenido, $esNueva, $waMsg);
        }
    }

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
        $canal = $this->canalPorSlug($slug);
        if (!$canal) return response('OK', 200);

        try {
            $body = json_decode($request->getContent(), true) ?? [];

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
        $this->ejecutarChatbot($canal, $conversacion, $contenido, $esNueva, $waMsg);
    }

    private function ejecutarChatbot(WaCanal $canal, WaConversacion $conversacion, string $contenido, bool $esNueva, array $waMsg = []): void
    {
        if (!($conversacion->bot_activo ?? true)) return;

        // ── Si el canal tiene bot_type → usar flujo de estados ───────────────
        $botType = $canal->bot_type ?? null;
        if ($botType) {
            $this->ejecutarFlujoEstados($canal, $conversacion, $contenido, $botType, $waMsg);
            return;
        }

        // ── Modo keywords (comportamiento anterior) ───────────────────────────
        $flows = WaChatbotFlow::where('wa_canal_id', $canal->id)
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        if ($flows->isEmpty()) return;

        $respuesta = null;

        if ($esNueva) {
            $flowBienvenida = $flows->firstWhere('es_bienvenida', true);
            if ($flowBienvenida) $respuesta = $flowBienvenida->response_text;
        }

        if (!$respuesta) {
            foreach ($flows->where('es_bienvenida', false) as $flow) {
                if ($flow->coincide($contenido)) {
                    $respuesta = $flow->response_text;
                    break;
                }
            }
        }

        if (!$respuesta) return;
        $this->enviarMeta($canal, $conversacion, $respuesta);
    }

    private function ejecutarFlujoEstados(WaCanal $canal, WaConversacion $conversacion, string $contenido, string $botType, array $waMsg = []): void
    {
        $waNumber = $conversacion->cliente_telefono;
        $lower    = mb_strtolower(trim($contenido));

        Log::info("Flujo estados [{$botType}]", ['canal_id' => $canal->id, 'numero' => $waNumber, 'msg' => $contenido]);

        // Cargar flujo desde caché
        $flow = Cache::remember("bot.flow.{$botType}", 300, function () use ($canal, $botType) {
            $botFlow = BotFlow::where('project_id', $canal->project_id)
                ->where('bot_type', $botType)
                ->with('states.transitions.toState')
                ->first();
            if (!$botFlow) return null;
            return $botFlow->states->sortBy('sort_order')->keyBy('key')->map(fn($s) => [
                'id'      => $s->id,
                'key'     => $s->key,
                'message' => $s->message,
                'images'  => is_array($s->images) ? $s->images : (json_decode($s->images ?? '[]', true) ?: []),
                'transitions' => $s->transitions->map(fn($t) => [
                    'trigger' => $t->trigger,
                    'action'  => $t->action,
                    'to'      => $t->toState?->key,
                ])->values()->toArray(),
            ])->toArray();
        });

        if (!$flow) {
            Log::warning("Flujo [{$botType}] no encontrado en BD para project_id={$canal->project_id}");
            return;
        }

        // Sesión del usuario
        $flowModel = BotFlow::where('project_id', $canal->project_id)->where('bot_type', $botType)->first();
        if (!$flowModel) return;

        $session  = BotSession::firstOrCreate(
            ['flow_id' => $flowModel->id, 'wa_number' => $waNumber],
            ['current_state' => 'inicio', 'data' => [], 'last_activity_at' => now()]
        );
        $esNuevaSesion = $session->wasRecentlyCreated;

        $currentState = $session->current_state;
        $data         = $session->data ?? [];

        // Verificar inactividad (30 minutos)
        $inactivo = $session->last_activity_at && now()->diffInMinutes($session->last_activity_at) >= 30;
        if ($inactivo && !$esNuevaSesion && $currentState !== 'inicio') {
            $session->update(['current_state' => 'inicio', 'data' => [], 'last_activity_at' => now()]);
            $this->enviarMeta($canal, $conversacion, "⏰ Tu sesión expiró por inactividad. ¡No te preocupes! Escribe *HOLA* cuando quieras continuar. 😊");
            return;
        }

        // Sesión nueva: mostrar bienvenida + menú con botones
        if ($esNuevaSesion) {
            $this->enviarEstadoConImagenes($canal, $conversacion, $flow['premios'] ?? null);
            sleep(2);
            $this->enviarMenuBotones($canal, $conversacion);
            return;
        }

        $enFlujo      = !in_array($currentState, ['inicio', 'esperando_asesor']);
        $cancelWords  = ['cancelar', 'salir', 'cancel', 'reset', 'reiniciar'];
        $saludoWords  = ['menu', 'inicio', 'hola', 'hi', 'buenas', 'buenos', 'hey', '¡hola! quiero probar mi suerte.', 'hola! quiero probar mi suerte.', 'quiero probar mi suerte'];
        $isCancel     = in_array($lower, $cancelWords);
        $isSaludo     = in_array($lower, $saludoWords);

        // Cancelar desde cualquier estado
        if ($isCancel) {
            $session->update(['current_state' => 'inicio', 'data' => [], 'last_activity_at' => now()]);
            $this->enviarMeta($canal, $conversacion, "❌ Registro cancelado. Cuando quieras continuar escribe *HOLA*. 😊");
            $this->enviarMenuBotones($canal, $conversacion);
            return;
        }

        // Saludo en medio del flujo → avisar sin resetear
        if ($isSaludo && $enFlujo) {
            $session->update(['last_activity_at' => now()]);
            $this->enviarMeta($canal, $conversacion, "⚠️ Tienes un registro en curso. Continúa respondiendo o escribe *CANCELAR* si deseas empezar de nuevo.");
            return;
        }

        // Saludo en estado inicio → bienvenida + menú
        if ($isSaludo && !$enFlujo) {
            $session->update(['current_state' => 'inicio', 'data' => [], 'last_activity_at' => now()]);
            $this->enviarEstadoConImagenes($canal, $conversacion, $flow['premios'] ?? null);
            sleep(2);
            $this->enviarMenuBotones($canal, $conversacion);
            return;
        }

        // Estado esperando asesor
        if ($currentState === 'esperando_asesor') {
            $this->enviarMeta($canal, $conversacion, "⏳ Tu mensaje fue recibido. Un asesor te responderá en breve.\n\nSi deseas volver al menú principal escribe *MENU*.");
            $session->update(['last_activity_at' => now()]);
            return;
        }

        // Mensajes de voz, sticker, documento, ubicación fuera del estado pago
        $msgType = $waMsg['type'] ?? 'text';
        $tiposNoPermitidos = ['audio', 'sticker', 'document', 'location', 'video'];
        if (in_array($msgType, $tiposNoPermitidos)) {
            $this->enviarMeta($canal, $conversacion, "📝 Por favor responde solo con texto en este paso.");
            return;
        }

        // Imagen fuera del estado pago
        if ($msgType === 'image' && $currentState !== 'pago') {
            $this->enviarMeta($canal, $conversacion, "📸 Solo puedo recibir imágenes cuando te pido el comprobante de pago.");
            return;
        }

        // Manejar botones especiales 5 y 6 desde cualquier estado
        if ($contenido === '5') {
            $session->update(['current_state' => 'esperando_asesor', 'last_activity_at' => now()]);
            $this->enviarMeta($canal, $conversacion, "5️⃣ *Problemas de registro en la web*\n\nPara asistirte de manera oportuna, comunícate por este WhatsApp detallando el inconveniente. De ser posible, adjunta una captura de pantalla del error.");
            return;
        }
        if ($contenido === '6') {
            $session->update(['current_state' => 'esperando_asesor', 'last_activity_at' => now()]);
            $this->enviarMeta($canal, $conversacion, "6️⃣ *Contacto con un asesor*\n\nPuedes contactar directamente con un asesor por este mismo canal. Uno de nuestros representantes te atenderá a la brevedad. 😊");
            return;
        }

        $stateConfig = $flow[$currentState] ?? $flow['inicio'] ?? null;
        if (!$stateConfig) return;

        $transitions = $stateConfig['transitions'] ?? [];

        // Buscar transición que coincida
        $matched = null;
        $catchAll = null;
        foreach ($transitions as $t) {
            $trigger = $t['trigger'] ?? '';
            if ($trigger === '' || $trigger === null) {
                $catchAll = $t; // guardar catch-all para usar si no hay otro match
                continue;
            }
            if (mb_strtolower($trigger) === $lower || $trigger === $contenido) {
                $matched = $t;
                break;
            }
        }
        if (!$matched) $matched = $catchAll;

        // Opción inválida en ver_lista (número fuera de rango)
        if ($currentState === 'ver_lista') {
            $rifas = $this->cargarRifas($canal->project_id);
            $idx = intval($contenido) - 1;
            if (!is_numeric($contenido) || $idx < 0 || !isset($rifas[$idx])) {
                $this->enviarMeta($canal, $conversacion, "⚠️ Opción no válida. Escribe el número de la membresía que te interesa (1 al " . count($rifas) . ").");
                return;
            }
        }

        if (!$matched) {
            // Texto fuera de contexto
            $this->enviarMeta($canal, $conversacion, "🤔 No entendí tu respuesta. Por favor sigue las instrucciones del paso actual.");
            return;
        }

        $action       = $matched['action'] ?? '';
        $nextStateKey = $matched['to'] ?? $currentState;

        // Guardar datos según acción con validaciones
        if ($action === 'save_solo_nombre') {
            if (strlen(trim($contenido)) < 3) {
                $this->enviarMeta($canal, $conversacion, "⚠️ Por favor escribe tu nombre completo.");
                return;
            }
            $data['nombre'] = trim($contenido);
            if (!empty($data['itemOrderId'])) {
                try { \App\Models\RifaVenta::find($data['itemOrderId'])?->update(['nombre' => $data['nombre']]); } catch (\Throwable $e) {}
            }
        }
        if ($action === 'save_solo_dni') {
            if (!preg_match('/^\d{8}$/', trim($contenido))) {
                $this->enviarMeta($canal, $conversacion, "⚠️ El DNI debe tener exactamente 8 dígitos.\n\nEjemplo: 12345678");
                return;
            }
            $data['dni'] = trim($contenido);
            if (!empty($data['itemOrderId'])) {
                try { \App\Models\RifaVenta::find($data['itemOrderId'])?->update(['dni' => $data['dni']]); } catch (\Throwable $e) {}
            }
        }
        if ($action === 'save_solo_celular') {
            if (!preg_match('/^\d{9}$/', trim($contenido))) {
                $this->enviarMeta($canal, $conversacion, "⚠️ El celular debe tener exactamente 9 dígitos.\n\nEjemplo: 987654321");
                return;
            }
            $data['celular'] = trim($contenido);
        }
        if ($action === 'save_solo_email') {
            if (!filter_var(trim($contenido), FILTER_VALIDATE_EMAIL)) {
                $this->enviarMeta($canal, $conversacion, "⚠️ El correo no es válido. Por favor escribe un correo correcto.\n\nEjemplo: tucorreo@gmail.com");
                return;
            }
            $data['email'] = trim($contenido);
        }
        if ($action === 'save_solo_ciudad') {
            $data['ciudad'] = trim($contenido);
            if (!empty($data['itemOrderId'])) {
                try { \App\Models\RifaVenta::find($data['itemOrderId'])?->update(['ciudad' => $data['ciudad']]); } catch (\Throwable $e) {}
            }
        }

        // select_item: solo guardar datos del plan en sesión, sin crear orden todavía
        if (in_array($action, ['select_item', 'show_lista', 'select_rifa'])) {
            $rifas = $this->cargarRifas($canal->project_id);
            $data['rifas'] = $rifas;
            $idx = intval($contenido) - 1;
            if (isset($rifas[$idx])) {
                $sel = $rifas[$idx];
                $data['itemId']       = $sel['id'];
                $data['itemNombre']   = $sel['nombre'];
                $data['itemPrecio']   = $sel['precio'];
                $data['itemTotal']    = $sel['precio'];
                $data['itemCantidad'] = intval($sel['precio'] / 10);
            }
        }

        // save_rifa_payment / save_payment: crear orden y guardar comprobante
        if (in_array($action, ['save_rifa_payment', 'save_payment'])) {
            $msgType = $waMsg['type'] ?? '';
            if ($msgType === 'image' && !empty($waMsg['image']['id'])) {
                $mediaId = $waMsg['image']['id'];
                // Crear orden recién aquí, cuando llega el comprobante
                if (empty($data['itemOrderId']) && !empty($data['itemNombre'])) {
                    try {
                        $tickets = intval(($data['itemPrecio'] ?? 0) / 10);
                        $venta = \App\Models\RifaVenta::create([
                            'project_id'  => $canal->project_id,
                            'order_number'=> \App\Models\RifaVenta::generateOrderNumber(),
                            'wa_number'   => $conversacion->cliente_telefono,
                            'plan'        => 'bot',
                            'plan_nombre' => $data['itemNombre'],
                            'tickets'     => $tickets,
                            'monto'       => $data['itemPrecio'] ?? 0,
                            'nombre'      => $data['nombre'] ?? null,
                            'dni'         => $data['dni'] ?? null,
                            'status'      => 'pendiente',
                        ]);
                        $data['itemOrderId'] = $venta->id;
                        $data['orderNumber'] = $venta->order_number;
                        Log::info('Orden rifa creada al enviar comprobante', ['order_id' => $venta->id, 'wa' => $conversacion->cliente_telefono]);
                    } catch (\Throwable $e) {
                        Log::error('Error crear orden rifa: ' . $e->getMessage());
                    }
                }
                $orderId = $data['itemOrderId'] ?? null;
                if ($orderId) {
                    $this->guardarComprobanteMeta($canal, $orderId, $mediaId);
                } else {
                    Log::warning('save_rifa_payment sin itemOrderId', ['wa' => $conversacion->cliente_telefono]);
                }
            } else {
                $this->enviarMeta($canal, $conversacion, "⚠️ Por favor envía la imagen del comprobante como *foto* (no como archivo).");
                return;
            }
        }

        // Actualizar sesión
        $session->update([
            'current_state'    => $nextStateKey,
            'data'             => $data,
            'last_activity_at' => now(),
        ]);

        // Enviar mensaje del siguiente estado
        $nextState = $flow[$nextStateKey] ?? null;

        // ver_lista → enviar catálogo con imágenes
        if ($nextStateKey === 'ver_lista') {
            $rifas = $this->cargarRifas($canal->project_id);
            $data['rifas'] = $rifas;
            $session->update(['data' => $data]);
            $this->enviarListaMeta($canal, $conversacion, $rifas);
            return;
        }

        // pedir_nombre → mostrar resumen del plan primero
        if ($nextStateKey === 'pedir_nombre' && !empty($data['itemNombre'])) {
            $resumen = "✅ *Plan seleccionado:*\n"
                . "📦 {$data['itemNombre']}\n"
                . "🎫 " . ($data['itemCantidad'] ?? 1) . " ticket(s)\n"
                . "💰 S/ {$data['itemTotal']}\n\n"
                . "Escribe *cambiar* si quieres elegir otro plan.";
            $this->enviarMeta($canal, $conversacion, $resumen);
        }

        if ($nextState && $nextState['message']) {
            $vars = [
                '{nombre}'        => $data['nombre'] ?? '',
                '{dni}'           => $data['dni'] ?? '',
                '{documento}'     => $data['dni'] ?? '',
                '{celular}'       => $data['celular'] ?? '',
                '{email}'         => $data['email'] ?? '',
                '{ciudad}'        => $data['ciudad'] ?? '',
                '{item_nombre}'   => $data['itemNombre'] ?? '',
                '{item_precio}'   => $data['itemPrecio'] ?? '',
                '{item_total}'    => $data['itemTotal'] ?? '',
                '{item_cantidad}' => $data['itemCantidad'] ?? '1',
            ];
            $msg = str_replace(array_keys($vars), array_values($vars), $nextState['message']);
            $msg = str_replace('\n', "\n", $msg);
            $this->enviarEstadoConImagenes($canal, $conversacion, array_merge($nextState, ['message' => $msg]));
        }

        // confirmacion → resetear sesión DESPUÉS de enviar el mensaje
        if ($nextStateKey === 'confirmacion') {
            $session->update(['current_state' => 'inicio', 'data' => [], 'last_activity_at' => now()]);
            $this->enviarMeta($canal, $conversacion, "✅ ¡Listo! En breve verificaremos tu pago y recibirás tus tickets por WhatsApp.\n\nSi tienes alguna consulta escribe *MENU*. 😊");
        }
    }

    private function guardarComprobanteMeta(WaCanal $canal, int $orderId, string $mediaId): void
    {
        try {
            $token = $canal->makeVisible('access_token')->access_token;

            // 1. Obtener URL de descarga del media
            $metaRes = Http::withToken($token)
                ->get("https://graph.facebook.com/v19.0/{$mediaId}");
            $mediaUrl = $metaRes->json('url');
            if (!$mediaUrl) {
                Log::warning('guardarComprobanteMeta: no se obtuvo URL de media', ['media_id' => $mediaId]);
                return;
            }

            // 2. Descargar imagen (requiere Authorization header)
            $imgRes = Http::withToken($token)->get($mediaUrl);
            if (!$imgRes->successful()) {
                Log::warning('guardarComprobanteMeta: fallo descarga imagen', ['status' => $imgRes->status()]);
                return;
            }

            $base64   = base64_encode($imgRes->body());
            $mimetype = $imgRes->header('Content-Type') ?: 'image/jpeg';

            // 3. Guardar en el pedido
            Http::post(url("wa/rifa/{$orderId}/payment-proof"), [
                'image_base64' => $base64,
                'mimetype'     => $mimetype,
            ]);

            Log::info('Comprobante guardado', ['order_id' => $orderId]);
        } catch (\Throwable $e) {
            Log::error('guardarComprobanteMeta error: ' . $e->getMessage());
        }
    }

    private function enviarMenuBotones(WaCanal $canal, WaConversacion $conversacion): void
    {
        try {
            $res = Http::withToken($canal->makeVisible('access_token')->access_token)
                ->post("https://graph.facebook.com/v19.0/{$canal->phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $conversacion->cliente_telefono,
                    'type'              => 'interactive',
                    'interactive'       => [
                        'type' => 'button',
                        'body' => ['text' => '📲 ¿Qué deseas hacer?'],
                        'action' => [
                            'buttons' => [
                                ['type' => 'reply', 'reply' => ['id' => '1', 'title' => '🎟️ Ver membresías']],
                                ['type' => 'reply', 'reply' => ['id' => '5', 'title' => '⚠️ Problemas web']],
                                ['type' => 'reply', 'reply' => ['id' => '6', 'title' => '👤 Contactar asesor']],
                            ],
                        ],
                    ],
                ]);
            $waMessageId = $res->json('messages.0.id');
            if (!$waMessageId) {
                Log::warning('enviarMenuBotones sin message_id', ['response' => $res->body()]);
                // fallback a texto
                $this->enviarMeta($canal, $conversacion, "📲 ¿Qué deseas hacer?\n\n1️⃣ Ver tipos membresía\n5️⃣ Problemas de registro\n6️⃣ Contactar asesor");
                return;
            }
            WaMensaje::create([
                'wa_conversacion_id' => $conversacion->id,
                'wa_message_id'      => $waMessageId,
                'direccion'          => 'saliente',
                'tipo'               => 'texto',
                'contenido'          => '📲 ¿Qué deseas hacer? [botones]',
                'estado'             => 'enviado',
            ]);
            $conversacion->update(['ultimo_mensaje_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('enviarMenuBotones error: ' . $e->getMessage());
            $this->enviarMeta($canal, $conversacion, "📲 ¿Qué deseas hacer?\n\n1️⃣ Ver tipos membresía\n5️⃣ Problemas de registro\n6️⃣ Contactar asesor");
        }
    }

    private function enviarEstadoConImagenes(WaCanal $canal, WaConversacion $conversacion, ?array $stateConfig): void
    {
        if (!$stateConfig) return;

        $imagenes = $stateConfig['images'] ?? [];
        $mensaje  = $stateConfig['message'] ?? null;

        if (!empty($imagenes)) {
            foreach ($imagenes as $i => $url) {
                if (!$url) continue;
                $fullUrl = str_starts_with($url, 'http') ? $url : asset('storage/' . ltrim($url, '/'));
                // Primera imagen lleva el texto como caption
                $caption = ($i === 0 && $mensaje) ? $mensaje : '';
                $this->enviarImagenMeta($canal, $conversacion, $fullUrl, $caption);
            }
        } elseif ($mensaje) {
            $this->enviarMeta($canal, $conversacion, $mensaje);
        }
    }

    private function cargarRifas(int $projectId): array
    {
        return Cache::remember("rifas.lista.{$projectId}", 300, function () use ($projectId) {
            $products = \App\Models\Product::where('project_id', $projectId)
                ->where('is_available', true)
                ->with('mainImage')
                ->orderBy('price')
                ->get();
            return $products->map(fn($p) => [
                'id'        => $p->id,
                'nombre'    => $p->name,
                'precio'    => (float) $p->price,
                'imagen_url'=> $p->mainImage?->url
                    ? (str_starts_with($p->mainImage->url, 'http') ? $p->mainImage->url : asset('storage/' . $p->mainImage->url))
                    : null,
                'texto'     => "*{$p->name}*\n💰 S/ " . number_format($p->price, 2) . ($p->description ? "\n_{$p->description}_" : ''),
            ])->values()->toArray();
        });
    }

    private function enviarListaMeta(WaCanal $canal, WaConversacion $conversacion, array $rifas): void
    {
        Log::info('enviarListaMeta', ['rifas' => count($rifas)]);

        foreach ($rifas as $i => $r) {
            $caption   = "*" . ($i + 1) . ".* " . ($r['texto'] ?? $r['nombre']);
            $imagenUrl = $r['imagen_url'] ?? null;

            if ($imagenUrl) {
                $this->enviarImagenMeta($canal, $conversacion, $imagenUrl, $caption);
            } else {
                $this->enviarMeta($canal, $conversacion, $caption);
            }
        }

        // Mensaje de cierre con instrucción
        $this->enviarMeta($canal, $conversacion, "👆 Escribe el *número* de la membresía que te interesa:");
    }

    private function enviarImagenMeta(WaCanal $canal, WaConversacion $conversacion, string $imageUrl, string $caption): void
    {
        $waMessageId = null;
        try {
            $res = Http::withToken($canal->makeVisible('access_token')->access_token)
                ->post("https://graph.facebook.com/v19.0/{$canal->phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $conversacion->cliente_telefono,
                    'type'              => 'image',
                    'image'             => ['link' => $imageUrl, 'caption' => $caption],
                ]);
            $waMessageId = $res->json('messages.0.id');
            Log::info('Meta imagen response', ['status' => $res->status(), 'body' => $res->body()]);
            if (!$waMessageId) {
                Log::warning('Meta imagen sin message_id, fallback a texto');
                $this->enviarMeta($canal, $conversacion, $caption);
                return;
            }
        } catch (\Throwable $e) {
            Log::error('Meta imagen error: ' . $e->getMessage());
            $this->enviarMeta($canal, $conversacion, $caption);
            return;
        }

        WaMensaje::create([
            'wa_conversacion_id' => $conversacion->id,
            'wa_message_id'      => $waMessageId,
            'direccion'          => 'saliente',
            'tipo'               => 'imagen',
            'contenido'          => $caption,
            'estado'             => 'enviado',
        ]);

        $conversacion->update(['ultimo_mensaje_at' => now()]);
    }

    private function enviarMeta(WaCanal $canal, WaConversacion $conversacion, string $respuesta): void
    {
        $waMessageId = null;
        if ($canal->phone_number_id && $canal->access_token) {
            try {
                $res = Http::withToken($canal->makeVisible('access_token')->access_token)
                    ->post("https://graph.facebook.com/v19.0/{$canal->phone_number_id}/messages", [
                        'messaging_product' => 'whatsapp',
                        'recipient_type'    => 'individual',
                        'to'                => $conversacion->cliente_telefono,
                        'type'              => 'text',
                        'text'              => ['body' => $respuesta, 'preview_url' => false],
                    ]);
                $waMessageId = $res->json('messages.0.id');
            } catch (\Throwable $e) {
                Log::error('Meta send error: ' . $e->getMessage());
            }
        }

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
            'text'        => $waMsg['text']['body'] ?? '',
            'interactive' => $waMsg['interactive']['button_reply']['id'] ?? $waMsg['interactive']['list_reply']['id'] ?? '',
            'image'       => '[Imagen]' . ($waMsg['image']['caption'] ?? ''),
            'audio'       => '[Audio]',
            'document'    => '[Documento: ' . ($waMsg['document']['filename'] ?? '') . ']',
            'sticker'     => '[Sticker]',
            'location'    => '[Ubicación: ' . ($waMsg['location']['name'] ?? '') . ']',
            default       => '[Mensaje]',
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
