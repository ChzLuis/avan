<?php

namespace App\Modules\Bots\Controllers;

use App\Modules\Crm\Models\WaCanal;
use App\Modules\Crm\Models\WaConversacion;
use App\Modules\Crm\Models\WaMensaje;

use App\Http\Controllers\Controller;
use App\Modules\Bots\Models\BotFlow;
use App\Modules\Bots\Models\BotSession;
use App\Models\Project;
use App\Modules\Bots\Support\FlowEngine\FlowRunner;
use App\Modules\Bots\Support\LeadScoring;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Puente WhatsApp → motor de bots.
 *
 * Baileys (bot "tonto") entrega cada mensaje entrante aquí. Este controlador:
 *   1. Identifica el proyecto por token.
 *   2. Toma el bot activo del proyecto.
 *   3. Carga el estado de la conversación (en qué bloque quedó ese teléfono).
 *   4. Ejecuta el motor (FlowRunner) con el mensaje.
 *   5. Guarda el nuevo estado y devuelve las respuestas para que Baileys las envíe.
 *
 * Baileys nunca decide nada: solo pasa mensajes y envía lo que BIXO responde.
 */
class BotWebhookController extends Controller
{
    private function project(Request $r): Project
    {
        $token = $r->header('X-Copilot-Token') ?? $r->input('token');
        $project = $token ? Project::where('copilot_token', $token)->first() : null;
        abort_if(!$project, 401, 'Token inválido.');
        return $project;
    }

    /** Recibe un mensaje entrante de WhatsApp y devuelve las respuestas del bot. */
    public function inbound(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $data = $r->validate([
            'telefono' => 'required|string',
            'mensaje'  => 'required|string',
            'nombre'   => 'nullable|string',
            'tipo'     => 'nullable|string|max:20',   // imagen | audio | video | documento | ubicacion | contacto
            'wa_message_id' => 'nullable|string|max:80',
            'texto_visible' => 'nullable|string|max:2000', // lo que ve el asesor (titulo del boton tocado, ubicacion...)
            'lid'      => 'nullable|string|max:40',   // id tecnico @lid de WhatsApp, si lo hubo
            'wa_canal_id' => 'nullable|integer',      // canal por el que entro (Meta); si falta, el canal Bot
            'media_url'   => 'nullable|string|max:500', // foto/audio/documento ya descargado por el webhook
            'referral'    => 'nullable|array',        // anuncio click-to-WhatsApp que trajo al cliente (Meta)
        ]);
        $this->canalEntradaId = isset($data['wa_canal_id']) ? (int) $data['wa_canal_id'] : null;
        $this->medioEntrada = ['tipo' => $data['tipo'] ?? 'texto', 'url' => $data['media_url'] ?? null];
        $this->referralEntrada = is_array($data['referral'] ?? null) ? $data['referral'] : null;
        $telefono = preg_replace('/[^\d]/', '', $data['telefono']);

        // IDENTIDAD WHATSAPP: el conector manda `telefono` = numero real (PN)
        // cuando lo conoce, y `lid` = id tecnico si el mensaje llego como @lid.
        // Cuando conocemos AMBOS, la conversacion que se abrio bajo el lid se
        // FUSIONA con la del numero real: un solo cliente, un solo historial,
        // y los recordatorios pueden contactarlo. Nunca se inventa un numero:
        // si solo hay lid, esa es la identidad (limitacion documentada).
        $lid = preg_replace('/[^\d]/', '', (string) ($data['lid'] ?? ''));
        if ($lid !== '' && $telefono !== '' && $lid !== $telefono) {
            $this->fusionarIdentidadLid($project, $lid, $telefono);
        }

        // IDEMPOTENCIA: WhatsApp puede reentregar el MISMO mensaje (replay tras
        // reconexion, reintentos del conector). Cache::add es atomico: la
        // segunda entrega del mismo id no procesa, no responde y no duplica CRM.
        if (! empty($data['wa_message_id'])) {
            $clave = "bot_in_{$project->id}_{$telefono}_" . $data['wa_message_id'];
            if (! \Illuminate\Support\Facades\Cache::add($clave, 1, 300)) {
                return response()->json(['respuestas' => [], 'duplicado' => true]);
            }
        }

        // 1) SIEMPRE guardar la conversación en el CRM (el bot alimenta el CRM 24/7,
        //    como los CRM profesionales: acumula historial hacia adelante).
        $this->guardarEnCrm($project, $telefono, $data['nombre'] ?? $telefono, $data['texto_visible'] ?? $data['mensaje'], 'in');

        // 1.a) BOT EN PAUSA para este chat (lo apago el asesor, o el flujo al pasar con
        //      una persona): el mensaje queda en el CRM y el bot calla. Solo la primera
        //      vez en 12 h dice que un asesor lo atiende, para que el cliente no se
        //      quede sin saber por que nadie contesta.
        $conv = $this->conversacionDe($project, $telefono);
        if ($conv && ! $conv->bot_activo) {
            $respuestas = [];
            if (\Illuminate\Support\Facades\Cache::add("bot_pausa_aviso_{$conv->id}", 1, 12 * 3600)) {
                $respuestas[] = 'Gracias 🙌 Un asesor te responde por aquí en breve. Puedes dejar tus fotos o dudas y las verá.';
                $this->guardarEnCrm($project, $telefono, $data['nombre'] ?? $telefono, $respuestas[0], 'out');
            }

            return response()->json(['respuestas' => $respuestas, 'bot_pausado' => true]);
        }

        // 2) Ejecutar el bot activo (si hay).
        $flow = BotFlow::where('project_id', $project->id)->where('activo', true)->latest()->first();
        if (!$flow || empty($flow->definicion['bloques'])) {
            return response()->json(['respuestas' => [], 'sin_bot' => true]);
        }

        // 1.b) ADJUNTOS: el motor entiende texto. Un audio o una foto no se
        //      ignoran en silencio (el cliente quedaba sin respuesta) ni se
        //      "buscan" como producto: respuesta honesta + registro en el CRM
        //      para que el asesor los vea. El flujo del cliente queda intacto.
        $tipo = $data['tipo'] ?? 'texto';
        if (! in_array($tipo, ['texto', ''], true)) {
            if ($this->reglaSilencia($project, $telefono, $flow->definicion['reglas'] ?? [])) {
                return response()->json(['respuestas' => [], 'silenciado' => true]);
            }
            // Una rafaga de fotos (un cliente mando 12 seguidas) recibe UNA respuesta,
            // no una por foto: las demas quedan guardadas en el CRM sin contestar.
            if (! \Illuminate\Support\Facades\Cache::add("bot_adjunto_aviso_{$project->id}_{$telefono}", 1, 600)) {
                return response()->json(['respuestas' => [], 'adjunto' => $tipo, 'agrupado' => true]);
            }
            $texto = match ($tipo) {
                'imagen'    => "📷 ¡Recibí tu imagen! Un *asesor* la revisará. Puedes enviar todas las fotos que quieras; te contesto una sola vez para no llenarte de mensajes 🙂",
                'audio'     => "🎧 Recibí tu audio. Por ahora solo puedo leer *texto*: ¿me lo escribes? O escribe *asesor* y te atiende una persona.",
                'video'     => "🎬 ¡Recibí tu video! Un *asesor* lo revisará. Cuéntame en texto en qué te ayudo 🙂",
                'documento' => "📄 Recibí tu archivo, un *asesor* lo revisará. ¿En qué te puedo ayudar mientras tanto?",
                'ubicacion' => "📍 ¡Gracias por tu ubicación! Un *asesor* la tomará en cuenta. ¿En qué te ayudo?",
                default     => "Recibí tu mensaje 🙂. Por ahora entiendo mejor el *texto*: cuéntame qué necesitas o escribe *asesor*.",
            };
            $this->guardarEnCrm($project, $telefono, $data['nombre'] ?? $telefono, $texto, 'out');

            return response()->json(['respuestas' => [$texto], 'adjunto' => $tipo]);
        }

        try {
        $session = BotSession::firstOrNew([
            'project_id' => $project->id,
            'telefono'   => $telefono,
        ]);
        $estado = $session->estado ?? ['bloque' => null, 'vars' => [], 'esperando' => false];
        // Una conversacion abandonada horas atras no sigue donde quedo: un cliente que
        // vuelve a escribir al dia siguiente (o toca otra vez el anuncio) empieza de nuevo.
        // Sin esto, su "hola" se tomaba como respuesta a "¿que tipo de negocio tienes?".
        if ($session->exists && $session->updated_at && $session->updated_at->lt(now()->subHours(6))) {
            $estado = ['bloque' => null, 'vars' => [], 'esperando' => false];
        }
        // Frases de REINICIO del flujo (`reinicio` en la definicion): el texto que trae el
        // anuncio o un saludo a secas siempre arrancan de cero, aunque el bot estuviera
        // esperando otra cosa (un cliente volvio a tocar el anuncio y su "hola, quiero mi
        // tienda" se tomo como respuesta a "¿ya tienes fotos listas?").
        if (! empty($estado['esperando']) && $this->esReinicio($data['mensaje'], $flow->definicion['reinicio'] ?? [])) {
            $estado = ['bloque' => null, 'vars' => [], 'esperando' => false];
        }
        $definicion = $flow->definicion;

        // El asistente atiende 24/7: nunca dejamos a un cliente sin respuesta.
        // (El horario 8–21 solo limita los RECORDATORIOS automáticos, no la atención.)

        // 2.a) REGLAS CRM: decidir si el bot debe callarse (ej. cliente con vendedor humano).
        //      Si la conversación NO está a mitad de un flujo, las reglas pueden silenciar al bot.
        if (empty($estado['esperando'])) {
            $motivo = $this->reglaSilencia($project, $telefono, $definicion['reglas'] ?? []);
            if ($motivo) {
                return response()->json(['respuestas' => [], 'silenciado' => true, 'motivo' => $motivo]);
            }

            // 2.b) DISPAROS: si hay disparos definidos, solo arrancar el bot cuando alguno coincida.
            //      El filtro protege el PRIMER contacto (no meterse en chats privados).
            //      Un cliente que YA conversó con el bot no es un chat privado: si su
            //      flujo murió y escribe "3" (respondiendo a un menú viejo), el bot
            //      debe retomar, no quedarse mudo.
            $disparos = $definicion['disparos'] ?? [];
            if (!$session->exists && !empty($disparos) && !$this->disparoCoincide($disparos, $data['mensaje'])) {
                return response()->json(['respuestas' => [], 'sin_disparo' => true]);
            }
        }

        $runner = new FlowRunner($project, $definicion);
        $res = $runner->procesar($data['mensaje'], $estado, $telefono);

        // 2.c) Ejecutar acciones sobre el CRM que pidieron los bloques (registrar/agendar).
        if (!empty($res['acciones'])) {
            $this->aplicarAcciones($project, $telefono, $data['nombre'] ?? $telefono, $res['acciones']);
        }

        $session->bot_builder_flow_id = $flow->id;
        $session->estado = $res['fin'] ? null : $res['estado'];
        $session->ultima_at = now();
        $session->save();

        // 3) Guardar también lo que respondió el bot (mensajes salientes).
        // FRENO DE SEGURIDAD: pase lo que pase en el flujo, un cliente nunca recibe una
        // avalancha (ayer un bucle mando 24 "Claro" seguidos a un cliente real).
        $res['respuestas'] = $this->frenar($project, $telefono, $res['respuestas'] ?? []);

        // El conector Baileys no sabe de botones ni de cta_url: recibe el texto
        // de respaldo. Meta (canal de entrada conocido) recibe el formato nativo.
        if (! $this->canalEntradaId) {
            $res['respuestas'] = array_map(
                function ($r) {
                    if (! is_array($r) || ! in_array($r['tipo'] ?? '', ['botones', 'cta_url'], true)) {
                        return $r;
                    }
                    $texto = $r['fallback'] ?? $r['cuerpo'] ?? '';
                    // Botones con foto de cabecera: por Baileys va la foto con el texto de pie.
                    return ! empty($r['imagen']) ? ['tipo' => 'imagen', 'url' => $r['imagen'], 'caption' => $texto] : $texto;
                },
                $res['respuestas']
            );
        }
        foreach ($res['respuestas'] as $resp) {
            if (is_array($resp)) {
                $tipoResp = (string) ($resp['tipo'] ?? '');
                // Imagen, archivo o audio del bot: en la bandeja se ve el adjunto, no su URL.
                if (in_array($tipoResp, ['imagen', 'archivo', 'audio'], true) && ! empty($resp['url'])) {
                    $tipoCrm = $tipoResp === 'archivo' ? 'documento' : $tipoResp;
                    $texto = (string) ($resp['caption'] ?? '');
                    if ($texto === '') {
                        $texto = match ($tipoCrm) {
                            'imagen'    => '📷 Imagen',
                            'audio'     => '🎤 Audio',
                            default     => '📄 ' . ($resp['nombre'] ?? 'Documento'),
                        };
                    }
                    $this->guardarEnCrm($project, $telefono, $data['nombre'] ?? $telefono, $texto, 'out', $tipoCrm, (string) $resp['url']);
                    continue;
                }
                // Botones con foto de cabecera: en la bandeja se ve la foto con la pregunta.
                if (in_array($tipoResp, ['botones', 'cta_url'], true) && ! empty($resp['imagen'])) {
                    $this->guardarEnCrm($project, $telefono, $data['nombre'] ?? $telefono, (string) ($resp['fallback'] ?? $resp['cuerpo'] ?? ''), 'out', 'imagen', (string) $resp['imagen']);
                    continue;
                }
                // Lista, botones o enlace: el texto representativo para el historial.
                $texto = $resp['fallback'] ?? $resp['cuerpo'] ?? $resp['caption'] ?? $resp['url'] ?? '';
            } else {
                $texto = $resp;
            }
            if ($texto !== '') $this->guardarEnCrm($project, $telefono, $data['nombre'] ?? $telefono, $texto, 'out');
        }

        return response()->json([
            'respuestas' => $res['respuestas'],
            'fin' => $res['fin'],
        ]);
        } catch (\Throwable $e) {
            // Limite de manejo: aqui adentro ya hay proyecto y mensaje validos;
            // cualquier fallo posterior (BD caida, query rota, estado corrupto)
            // es NUESTRO, no del cliente. Al canal va una disculpa breve; el
            // detalle tecnico, SOLO al log.
            \Illuminate\Support\Facades\Log::error('bot_inbound.fallo', [
                'proyecto' => $project->id,
                'error'    => class_basename($e),
                'detalle'  => mb_substr($e->getMessage(), 0, 200),
            ]);

            return response()->json([
                'respuestas' => ['Ups, tuvimos un inconveniente técnico 😅. Inténtalo de nuevo en un momento o escribe *asesor* para que te atienda una persona.'],
                'error_interno' => true,
            ]);
        }
    }

    /**
     * Fusiona la identidad tecnica @lid con el numero real cuando WhatsApp
     * entrega ambos: la sesion del bot y la conversacion del CRM abiertas bajo
     * el lid pasan al numero, sin duplicar cliente ni perder el hilo.
     */
    private function fusionarIdentidadLid(Project $project, string $lid, string $telefono): void
    {
        try {
            // Sesion del bot: el estado del flujo sobrevive al cambio de identidad.
            $delLid = BotSession::where('project_id', $project->id)->where('telefono', $lid)->first();
            if ($delLid) {
                $delPn = BotSession::where('project_id', $project->id)->where('telefono', $telefono)->first();
                if ($delPn) {
                    // Ambas existen: gana la mas reciente; la otra se retira.
                    if (($delLid->ultima_at ?? $delLid->updated_at) > ($delPn->ultima_at ?? $delPn->updated_at)) {
                        $delPn->delete();
                        $delLid->update(['telefono' => $telefono]);
                    } else {
                        $delLid->delete();
                    }
                } else {
                    $delLid->update(['telefono' => $telefono]);
                }
            }

            // CRM: los mensajes registrados bajo el lid se mueven a la
            // conversacion del numero real (o esta se renombra si no existia).
            $canales = \App\Modules\Crm\Models\WaCanal::where('project_id', $project->id)->pluck('id');
            $convLid = \App\Modules\Crm\Models\WaConversacion::whereIn('wa_canal_id', $canales)
                ->where('cliente_telefono', $lid)->first();
            if ($convLid) {
                $convPn = \App\Modules\Crm\Models\WaConversacion::whereIn('wa_canal_id', $canales)
                    ->where('cliente_telefono', $telefono)->first();
                if ($convPn) {
                    $convLid->mensajes()->update(['wa_conversacion_id' => $convPn->id]);
                    $convLid->delete();
                } else {
                    $convLid->update(['cliente_telefono' => substr($telefono, 0, 20)]);
                }
            }
        } catch (\Throwable $e) {
            // La fusion es mantenimiento de identidad: si falla, se atiende
            // igual con la identidad entrante y se deja rastro para revisarlo.
            \Illuminate\Support\Facades\Log::warning('bot_inbound.fusion_lid_fallo', [
                'proyecto' => $project->id, 'error' => class_basename($e),
            ]);
        }
    }

    /**
     * REGLAS CRM: devuelve el motivo por el que el bot NO debe responder, o null si puede.
     * Usa datos REALES del cliente (el diferenciador: MERKADO no conoce tu CRM).
     */
    private function reglaSilencia(Project $project, string $telefono, array $reglas): ?string
    {
        if (empty($reglas)) return null;

        $client = \App\Modules\Crm\Models\Client::allProjects()
            ->where('project_id', $project->id)->where('phone', $telefono)->first();

        foreach ($reglas as $regla) {
            $tipo = $regla['tipo'] ?? '';
            switch ($tipo) {
                case 'tiene_vendedor':
                    // No responder si el cliente ya tiene un asesor humano asignado.
                    if ($client && !empty($client->responsable)) return 'cliente con vendedor asignado';
                    break;
                case 'etapa':
                    // No responder si el cliente está en cierta etapa del pipeline (ej. "ganado").
                    if ($client && !empty($regla['valor']) && $client->etapa === $regla['valor']) {
                        return "etapa: {$regla['valor']}";
                    }
                    break;
                case 'horario':
                    // Solo responder dentro del horario de negocio [desde, hasta] (hora 0-23).
                    $h = (int) now()->format('H');
                    $desde = (int) ($regla['desde'] ?? 0);
                    $hasta = (int) ($regla['hasta'] ?? 24);
                    if ($h < $desde || $h >= $hasta) return 'fuera de horario';
                    break;
            }
        }
        return null;
    }

    /**
     * DISPAROS: ¿el mensaje entrante activa el bot? Coincide por palabra clave real.
     * (ej. el cliente pregunta por un producto del catálogo o escribe "precio", "catálogo").
     */
    private function disparoCoincide(array $disparos, string $mensaje): bool
    {
        $msg = mb_strtolower($mensaje);
        foreach ($disparos as $d) {
            $palabras = is_array($d) ? ($d['palabras'] ?? []) : (array) $d;
            foreach ($palabras as $p) {
                $p = mb_strtolower(trim($p));
                if ($p !== '' && str_contains($msg, $p)) return true;
            }
        }
        return false;
    }

    /**
     * Abre un trato en el embudo (solo negocios con CRM) enlazado a la conversacion.
     * Si el cliente ya tiene un trato abierto, no se duplica: se anota en sus notas.
     */
    private function abrirTrato(Project $project, string $telefono, string $nombre, array $trato): void
    {
        if (! \App\Support\Productos::contratado($project, 'crm')) {
            return;
        }
        $canales = WaCanal::where('project_id', $project->id)->pluck('id');
        $conv = WaConversacion::whereIn('wa_canal_id', $canales)->where('cliente_telefono', $telefono)->first();
        \App\Modules\Crm\Models\CrmEtapa::asegurar($project);
        $etapas = \App\Modules\Crm\Models\CrmEtapa::where('project_id', $project->id)->orderBy('orden')->get();
        $etapa = ($trato['etapa'] !== '' ? $etapas->first(fn ($e) => mb_strtolower($e->nombre) === mb_strtolower($trato['etapa'])) : null)
            ?? $etapas->first(fn ($e) => ! $e->es_ganado && ! $e->es_perdido);
        if (! $etapa) {
            return;
        }
        $nota = 'Abierto por el bot (' . ($trato['bloque'] ?? 'flujo') . ') el ' . now()->format('d/m H:i');

        $abierto = \App\Modules\Crm\Models\CrmTrato::where('project_id', $project->id)
            ->where('contacto_telefono', $telefono)->whereNull('ganado_at')->whereNull('perdido_at')->first();
        if ($abierto) {
            $abierto->forceFill(['notas' => trim((string) $abierto->notas . "\n" . 'El cliente volvió a pedir asesor · ' . $nota)])->save();
            if (! $abierto->wa_conversacion_id && $conv) $abierto->forceFill(['wa_conversacion_id' => $conv->id])->save();

            return;
        }

        \App\Modules\Crm\Models\CrmTrato::create([
            'project_id'         => $project->id,
            'etapa_id'           => $etapa->id,
            'wa_conversacion_id' => $conv?->id,
            'titulo'             => mb_substr($trato['titulo'] !== '' ? $trato['titulo'] : ('Tienda virtual · ' . ($nombre ?: $telefono)), 0, 120),
            'valor'              => $trato['valor'] ?? 0,
            'contacto_nombre'    => $nombre ?: $telefono,
            'contacto_telefono'  => $telefono,
            'origen'             => 'bot',
            'notas'              => $nota,
            'etapa_desde'        => now(),
        ]);
    }

    /** ¿El mensaje es una frase de reinicio del flujo? Saludos exactos o frases contenidas (sin tildes ni mayusculas). */
    private function esReinicio(string $mensaje, array $frases): bool
    {
        $norm = fn (string $t) => trim(preg_replace('/[^a-z0-9 ]/u', ' ', mb_strtolower(\Illuminate\Support\Str::ascii($t))));
        $m = preg_replace('/\s+/', ' ', $norm($mensaje));
        foreach ($frases as $f) {
            $f = preg_replace('/\s+/', ' ', $norm((string) $f));
            if ($f === '') continue;
            // Frases largas (el texto del anuncio): basta con que esten contenidas. Cortas ("hola"): exactas.
            if (mb_strlen($f) >= 15 ? str_contains($m, $f) : $m === $f) return true;
        }

        return false;
    }

    public const MAX_POR_TURNO = 6;
    public const MAX_POR_VENTANA = 20;   // mensajes del bot a un mismo cliente...
    public const VENTANA_SEG = 120;      // ...en este lapso

    /**
     * Tope por turno (sin repetidos) y por ventana de tiempo. Al pasarse el tope de la
     * ventana se corta todo, queda en el log y el bot del chat se pausa para que lo
     * revise una persona: mejor un chat mudo que uno que dispara sin parar.
     */
    private function frenar(Project $project, string $telefono, array $respuestas): array
    {
        $vistos = [];
        $limpias = [];
        foreach ($respuestas as $r) {
            $clave = is_array($r) ? md5(json_encode($r)) : md5(trim((string) $r));
            if ((is_string($r) && trim($r) === '') || isset($vistos[$clave])) {
                continue;
            }
            $vistos[$clave] = true;
            $limpias[] = $r;
            if (count($limpias) >= self::MAX_POR_TURNO) {
                \Illuminate\Support\Facades\Log::warning('bot.tope_turno', ['proyecto' => $project->id, 'telefono' => $telefono, 'pedidos' => count($respuestas)]);
                break;
            }
        }
        if ($limpias === []) {
            return [];
        }
        $claveVentana = "bot_ritmo_{$project->id}_{$telefono}";
        $cache = \Illuminate\Support\Facades\Cache::store();
        $cache->add($claveVentana, 0, self::VENTANA_SEG);
        $acumulado = (int) $cache->increment($claveVentana, count($limpias));
        if ($acumulado > self::MAX_POR_VENTANA) {
            \Illuminate\Support\Facades\Log::error('bot.avalancha_cortada', ['proyecto' => $project->id, 'telefono' => $telefono, 'en_ventana' => $acumulado]);
            $this->conversacionDe($project, $telefono)?->update(['bot_activo' => false]);

            return [];
        }

        return $limpias;
    }

    /** Conversacion del CRM de este telefono en el negocio (cualquiera de sus lineas). */
    private function conversacionDe(Project $project, string $telefono): ?WaConversacion
    {
        $canales = WaCanal::where('project_id', $project->id)->pluck('id');

        return WaConversacion::whereIn('wa_canal_id', $canales)->where('cliente_telefono', $telefono)->first();
    }

    /** Aplica las acciones CRM (registrar etapa/etiqueta, agendar seguimiento) que pidió el flujo. */
    private function aplicarAcciones(Project $project, string $telefono, string $nombre, array $acciones): void
    {
        $client = \App\Modules\Crm\Models\Client::allProjects()
            ->where('project_id', $project->id)->where('phone', $telefono)->first();
        if (!$client) {
            $client = new \App\Modules\Crm\Models\Client([
                'project_id' => $project->id, 'name' => $nombre ?: $telefono,
                'phone' => $telefono, 'etapa' => 'prospecto',
            ]);
        }

        if (!empty($acciones['trato'])) {
            $this->abrirTrato($project, $telefono, $nombre, $acciones['trato']);
        }
        // El flujo entrego el chat a una persona: el bot se pausa (la bandeja lo puede volver a encender).
        if (!empty($acciones['pausar_bot'])) {
            if ($c = $this->conversacionDe($project, $telefono)) {
                $c->update(['bot_activo' => false]);
                // El flujo ya dijo "te paso con un asesor": a partir de aqui, silencio total
                // (el aviso generico de pausa es solo para cuando lo apaga el asesor a mano).
                \Illuminate\Support\Facades\Cache::put("bot_pausa_aviso_{$c->id}", 1, 12 * 3600);
            }
        }

        if (!empty($acciones['registrar'])) {
            $reg = $acciones['registrar'];
            if (!empty($reg['etapa'])) $client->etapa = $reg['etapa'];
            if (!empty($reg['etiqueta'])) {
                $tags = is_array($client->etiquetas) ? $client->etiquetas : [];
                if (!in_array($reg['etiqueta'], $tags)) $tags[] = $reg['etiqueta'];
                $client->etiquetas = $tags;
            }
        }
        // PEDIDO del bot → Order real (visible en CRM, extensión, POS y pedidos).
        if (!empty($acciones['pedido']['items'])) {
            $ped = $acciones['pedido'];
            $notas = [];
            if (!empty($ped['direccion'])) $notas[] = "Dirección: {$ped['direccion']}";
            if (!empty($ped['pago']))      $notas[] = "Pago: {$ped['pago']}";
            $notas[] = 'Pedido tomado por el bot de WhatsApp';

            // Pago digital (Yape/Plin) → queda EN REVISIÓN para que el vendedor lo apruebe.
            $esDigital = in_array($ped['pago'] ?? '', ['yape-plin', 'yape', 'plin'], true);
            $metodo = match (true) {
                ($ped['pago'] ?? '') === 'contra-entrega' => 'Contra entrega',
                $esDigital => 'Yape/Plin',
                default => $ped['pago'] ?? null,
            };
            if ($esDigital) $notas[] = '⏳ Pago reportado por el cliente — PENDIENTE DE APROBACIÓN';

            $order = \App\Modules\Ventas\Models\Order::create([
                'project_id'    => $project->id,
                'client_name'   => $client->name ?: $nombre,
                'client_phone'  => $telefono,
                // Vocabulario canonico: comercial en ingles (pending); el pago
                // digital queda under_review hasta su aprobacion.
                'status'        => 'pending',
                'payment_status'=> $esDigital ? 'under_review' : 'pending',
                'payment_method'=> $metodo,
                'sales_channel' => 'whatsapp',
                'notes'         => implode("\n", $notas),
                'total'         => $ped['total'] ?? 0,
            ]);
            foreach ($ped['items'] as $it) {
                \App\Modules\Ventas\Models\OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => is_numeric($it['id'] ?? null) ? $it['id'] : null,
                    'name'       => $it['nombre'] ?? 'Producto',
                    'price'      => $it['precio'] ?? 0,
                    'quantity'   => $it['cantidad'] ?? 1,
                ]);
            }
            // Dejar rastro en la ficha del cliente (lo ve el vendedor en la extensión).
            $linea = '[' . now()->format('d/m H:i') . '] 🛒 Pedido #' . $order->id
                . ' por WhatsApp — S/ ' . number_format($ped['total'] ?? 0, 2);
            $client->notes = trim(($client->notes ? $client->notes . "\n" : '') . $linea);
            if (!empty($ped['direccion']) && empty($client->direccion)) $client->direccion = $ped['direccion'];
        }

        // Agendar = registrar un seguimiento (nota + próximo seguimiento).
        if (!empty($acciones['agendar']['nota'])) {
            $linea = '[' . now()->format('d/m H:i') . '] 📅 ' . $acciones['agendar']['nota'];
            $client->notes = trim(($client->notes ? $client->notes . "\n" : '') . $linea);
            if (empty($client->proximo_seguimiento)) $client->proximo_seguimiento = now()->addDay();
        }

        $client->ultima_actividad = now();
        $client->save();
    }

    /**
     * Guarda un mensaje en el CRM: conversación (crea/actualiza) + mensaje + lead.
     * Es lo que hace que el bot 24/7 alimente el CRM automáticamente.
     */
    /** Canal por el que entro el mensaje en curso (lo fija inbound() cuando viene de Meta). */
    private ?int $canalEntradaId = null;
    /** Tipo y archivo del mensaje entrante en curso (imagen/audio/documento descargado). */
    private array $medioEntrada = ['tipo' => 'texto', 'url' => null];
    /** Anuncio que trajo al cliente, si el mensaje en curso vino de un click-to-WhatsApp. */
    private ?array $referralEntrada = null;

    /**
     * Campos de atribucion cuando el mensaje vino de un anuncio click-to-WhatsApp.
     *
     * Meta manda el `referral` SOLO en el mensaje con el que se abre el chat,
     * asi que este es el unico momento en que se puede saber que anuncio pago
     * esta conversacion. Devuelve vacio cuando no hubo anuncio, para no tocar
     * las conversaciones organicas.
     *
     * `origen_anuncio` es de PRIMER TOQUE: si la conversacion ya nacio con un
     * origen, un clic posterior en otro anuncio no le reescribe la historia.
     * `anuncio_referral` y `anuncio_id`, en cambio, guardan el ultimo clic,
     * que es el que explica por que el cliente esta escribiendo ahora.
     */
    private function atribucionAnuncio(?\App\Modules\Crm\Models\WaConversacion $conv): array
    {
        if ($this->referralEntrada === null) {
            return [];
        }

        $anuncioId = mb_substr((string) ($this->referralEntrada['source_id'] ?? ''), 0, 40);

        $campos = [
            'anuncio_referral' => $this->referralEntrada,
            'anuncio_id'       => $anuncioId !== '' ? $anuncioId : null,
        ];

        if ($conv === null || (string) ($conv->origen_anuncio ?? '') === '') {
            $campos['origen_anuncio'] = 'anuncio_meta';
        }

        return $campos;
    }

    private function guardarEnCrm(\App\Models\Project $project, string $telefono, string $nombre, string $texto, string $dir, ?string $tipoSalida = null, ?string $mediaSalida = null): void
    {
        // La linea por la que entro (Meta) o, si no, el canal "Bot" de Baileys.
        $canal = $this->canalEntradaId
            ? \App\Modules\Crm\Models\WaCanal::where('project_id', $project->id)->find($this->canalEntradaId)
            : null;
        $canal ??= \App\Modules\Crm\Models\WaCanal::firstOrCreate(
            ['project_id' => $project->id, 'tipo' => 'bot'],
            ['nombre' => 'Bot WhatsApp', 'activo' => true, 'bot_type' => 'baileys', 'color' => '#22c55e']
        );

        $canalIds = \App\Modules\Crm\Models\WaCanal::where('project_id', $project->id)->pluck('id');
        $conv = \App\Modules\Crm\Models\WaConversacion::whereIn('wa_canal_id', $canalIds)
            ->where('cliente_telefono', $telefono)->first();

        if (!$conv) {
            $conv = \App\Modules\Crm\Models\WaConversacion::create([
                'wa_canal_id' => $canal->id,
                'cliente_nombre' => $nombre ?: $telefono,
                'cliente_telefono' => substr($telefono, 0, 20),
                'estado' => 'nuevo',
                'no_leidos' => $dir === 'in' ? 1 : 0,
                'ultimo_mensaje_at' => now(),
                'bot_activo' => true,
            ] + ($dir === 'in' ? $this->atribucionAnuncio(null) : []));
        } else {
            $conv->ultimo_mensaje_at = now();
            if ($dir === 'in') $conv->no_leidos++;
            // Si el cliente ya existia en el canal Bot y ahora escribe por la
            // linea de Meta, la conversacion pasa a esa linea: es por donde se
            // le puede responder.
            if ($canal->conectadoAMeta() && $conv->wa_canal_id !== $canal->id) $conv->wa_canal_id = $canal->id;
            // Un cliente que ya escribio antes puede volver por un anuncio
            // nuevo: se registra ese clic sin perder de donde salio la primera
            // vez (eso lo cuida atribucionAnuncio).
            if ($dir === 'in') $conv->fill($this->atribucionAnuncio($conv));
            $conv->save();
        }

        \App\Modules\Crm\Models\WaMensaje::create([
            'wa_conversacion_id' => $conv->id,
            'direccion' => $dir,
            'tipo' => $dir === 'in'
                ? (! empty($this->medioEntrada['url']) ? $this->medioEntrada['tipo'] : 'texto')
                : ($tipoSalida ?: 'texto'),
            'media_url' => $dir === 'in' ? $this->medioEntrada['url'] : $mediaSalida,
            'contenido' => $texto,
            'estado' => $dir === 'in' ? 'recibido' : 'enviado',
        ]);

        // Aviso push a los dispositivos del negocio (tras responder el webhook).
        if ($dir === 'in') {
            $convId = $conv->id; $projectId = $project->id; $textoAviso = $texto;
            dispatch(function () use ($convId, $projectId, $textoAviso) {
                if ($c = WaConversacion::find($convId)) {
                    \App\Modules\Crm\Support\WebPush\AvisoPush::mensajeEntrante($projectId, $c, $textoAviso);
                }
            })->afterResponse();
        }

        // Clasificar el lead con los mensajes entrantes acumulados.
        if ($dir === 'in') {
            $textos = \App\Modules\Crm\Models\WaMensaje::where('wa_conversacion_id', $conv->id)
                ->where('direccion', 'in')->orderBy('created_at')->pluck('contenido')->implode("\n");
            $clasif = LeadScoring::clasificar($textos);
            $client = \App\Modules\Crm\Models\Client::allProjects()
                ->where('project_id', $project->id)->where('phone', $telefono)->first();
            if (!$client) {
                $client = new \App\Modules\Crm\Models\Client([
                    'project_id' => $project->id, 'name' => $nombre ?: $telefono,
                    'phone' => $telefono, 'etapa' => 'prospecto',
                ]);
            }
            $client->lead_score = $clasif['score'];
            $client->lead_temp = $clasif['temp'];
            $client->lead_source = $clasif['source'];
            $client->ultima_actividad = now();
            $client->save();
        }
    }
}
