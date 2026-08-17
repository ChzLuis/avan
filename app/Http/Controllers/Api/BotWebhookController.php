<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotFlow;
use App\Models\BotSession;
use App\Models\Project;
use App\Support\FlowEngine\FlowRunner;
use App\Support\LeadScoring;
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
        ]);
        $telefono = preg_replace('/[^\d]/', '', $data['telefono']);

        // 1) SIEMPRE guardar la conversación en el CRM (el bot alimenta el CRM 24/7,
        //    como los CRM profesionales: acumula historial hacia adelante).
        $this->guardarEnCrm($project, $telefono, $data['nombre'] ?? $telefono, $data['mensaje'], 'in');

        // 2) Ejecutar el bot activo (si hay).
        $flow = BotFlow::where('project_id', $project->id)->where('activo', true)->latest()->first();
        if (!$flow || empty($flow->definicion['bloques'])) {
            return response()->json(['respuestas' => [], 'sin_bot' => true]);
        }

        $session = BotSession::firstOrNew([
            'project_id' => $project->id,
            'telefono'   => $telefono,
        ]);
        $estado = $session->estado ?? ['bloque' => null, 'vars' => [], 'esperando' => false];
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
            $disparos = $definicion['disparos'] ?? [];
            if (!empty($disparos) && !$this->disparoCoincide($disparos, $data['mensaje'])) {
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
        foreach ($res['respuestas'] as $resp) {
            if (is_array($resp)) {
                // Lista, imagen o archivo: tomar el texto representativo para el historial.
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
    }

    /**
     * REGLAS CRM: devuelve el motivo por el que el bot NO debe responder, o null si puede.
     * Usa datos REALES del cliente (el diferenciador: MERKADO no conoce tu CRM).
     */
    private function reglaSilencia(Project $project, string $telefono, array $reglas): ?string
    {
        if (empty($reglas)) return null;

        $client = \App\Models\Client::allProjects()
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

    /** Aplica las acciones CRM (registrar etapa/etiqueta, agendar seguimiento) que pidió el flujo. */
    private function aplicarAcciones(Project $project, string $telefono, string $nombre, array $acciones): void
    {
        $client = \App\Models\Client::allProjects()
            ->where('project_id', $project->id)->where('phone', $telefono)->first();
        if (!$client) {
            $client = new \App\Models\Client([
                'project_id' => $project->id, 'name' => $nombre ?: $telefono,
                'phone' => $telefono, 'etapa' => 'prospecto',
            ]);
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

            $order = \App\Models\Order::create([
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
                \App\Models\OrderItem::create([
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
    private function guardarEnCrm(\App\Models\Project $project, string $telefono, string $nombre, string $texto, string $dir): void
    {
        // Canal "Bot" del proyecto.
        $canal = \App\Models\WaCanal::firstOrCreate(
            ['project_id' => $project->id, 'tipo' => 'bot'],
            ['nombre' => 'Bot WhatsApp', 'activo' => true, 'bot_type' => 'baileys', 'color' => '#22c55e']
        );

        $canalIds = \App\Models\WaCanal::where('project_id', $project->id)->pluck('id');
        $conv = \App\Models\WaConversacion::whereIn('wa_canal_id', $canalIds)
            ->where('cliente_telefono', $telefono)->first();

        if (!$conv) {
            $conv = \App\Models\WaConversacion::create([
                'wa_canal_id' => $canal->id,
                'cliente_nombre' => $nombre ?: $telefono,
                'cliente_telefono' => substr($telefono, 0, 20),
                'estado' => 'nuevo',
                'no_leidos' => $dir === 'in' ? 1 : 0,
                'ultimo_mensaje_at' => now(),
                'bot_activo' => true,
            ]);
        } else {
            $conv->ultimo_mensaje_at = now();
            if ($dir === 'in') $conv->no_leidos++;
            $conv->save();
        }

        \App\Models\WaMensaje::create([
            'wa_conversacion_id' => $conv->id,
            'direccion' => $dir,
            'tipo' => 'texto',
            'contenido' => $texto,
            'estado' => $dir === 'in' ? 'recibido' : 'enviado',
        ]);

        // Clasificar el lead con los mensajes entrantes acumulados.
        if ($dir === 'in') {
            $textos = \App\Models\WaMensaje::where('wa_conversacion_id', $conv->id)
                ->where('direccion', 'in')->orderBy('created_at')->pluck('contenido')->implode("\n");
            $clasif = LeadScoring::clasificar($textos);
            $client = \App\Models\Client::allProjects()
                ->where('project_id', $project->id)->where('phone', $telefono)->first();
            if (!$client) {
                $client = new \App\Models\Client([
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
