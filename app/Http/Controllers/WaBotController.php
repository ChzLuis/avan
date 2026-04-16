<?php
namespace App\Http\Controllers;

use App\Models\BotFlow;
use App\Models\BotSession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WaBotController extends Controller
{
    private const BOT_URL  = 'http://127.0.0.1:3001';
    private const BOT_TOKEN = 'wa-bot-secret-2024'; // igual en el bot

    // ── Bot → Laravel: obtener config del proyecto por número de teléfono ──────
    public function getConfig(Request $request)
    {
        $data = $request->validate(['token' => 'required|string', 'phone' => 'required|string']);
        if ($data['token'] !== self::BOT_TOKEN) return response()->json(['error' => 'Unauthorized'], 401);

        $phone    = preg_replace('/\D/', '', $data['phone']);
        $phoneLast = substr($phone, -9); // últimos 9 dígitos para buscar con/sin código de país
        $project  = \App\Models\Project::where('is_active', true)
            ->where(function($q) use ($phone, $phoneLast) {
                $q->where('wa_phone', $phone)
                  ->orWhereRaw("RIGHT(REPLACE(wa_phone, ' ', ''), 9) = ?", [$phoneLast]);
            })->first();

        if (!$project) return response()->json(['error' => 'No project found for this number'], 404);

        return response()->json([
            'ok'           => true,
            'project_slug' => $project->slug,
            'negocio'      => $project->name,
            'phone'        => $project->phone ?? '',
            'whatsapp'     => $project->whatsapp ?? '',
        ]);
    }

    // ── Bot → Laravel: flujo completo (estados + transiciones) ──────────────
    public function getFlowConfig(Request $request)
    {
        $data = $request->validate(['token' => 'required|string', 'bot' => 'required|string']);
        if ($data['token'] !== self::BOT_TOKEN) return response()->json(['error' => 'Unauthorized'], 401);

        // Prioridad: BotInstance → teléfono → cualquiera
        $botInstance = \App\Models\BotInstance::where('bot_type', $data['bot'])->first();

        $query = BotFlow::with('states.transitions.toState')
            ->where('bot_type', $data['bot'])
            ->where('is_active', true);

        if ($botInstance) {
            $query->where('project_id', $botInstance->project_id);
        } else {
            $phone = preg_replace('/\D/', '', $request->get('phone', ''));
            if ($phone) {
                $phoneLast = substr($phone, -9);
                $project = Project::where('is_active', true)
                    ->where(function($q) use ($phone, $phoneLast) {
                        $q->where('wa_phone', $phone)
                          ->orWhereRaw("RIGHT(REPLACE(wa_phone,' ',''),9)=?", [$phoneLast]);
                    })->first();
                if ($project) $query->where('project_id', $project->id);
            }
        }

        $flow = $query->first();
        if (!$flow) return response()->json(['error' => 'No flow found'], 404);

        $states = $flow->states->map(fn($s) => [
            'key'         => $s->key,
            'label'       => $s->label,
            'message'     => $s->message,
            'input_type'  => $s->input_type,
            'transitions' => $s->transitions->map(fn($t) => [
                'trigger'      => $t->trigger,
                'to'           => $t->toState?->key,
                'action'       => $t->action,
                'action_param' => $t->action_param,
            ])->values(),
        ])->keyBy('key');

        $payload = [
            'ok'         => true,
            'flow_id'    => $flow->id,
            'bot_type'   => $flow->bot_type,
            'project_id' => $flow->project_id,
            'states'     => $states,
        ];

        // Guardar JSON de respaldo para que el bot lo use si Laravel cae
        static::exportFlowJson($flow->bot_type, $payload);

        return response()->json($payload);
    }

    // ── Bot → Laravel: estado de sesión del cliente ───────────────────────────
    public function getSession(Request $request)
    {
        $data = $request->validate(['token' => 'required|string', 'flow_id' => 'required|integer', 'wa_number' => 'required|string']);
        if ($data['token'] !== self::BOT_TOKEN) return response()->json(['error' => 'Unauthorized'], 401);

        $existed = BotSession::where('flow_id', $data['flow_id'])->where('wa_number', $data['wa_number'])->exists();
        $session = BotSession::forNumber($data['flow_id'], $data['wa_number']);
        return response()->json([
            'ok'      => true,
            'state'   => $existed ? $session->current_state : null,
            'is_new'  => !$existed,
            'data'    => $session->data ?? [],
        ]);
    }

    // ── Bot → Laravel: actualizar estado de sesión ────────────────────────────
    public function updateSession(Request $request)
    {
        $data = $request->validate([
            'token'     => 'required|string',
            'flow_id'   => 'required|integer',
            'wa_number' => 'required|string',
            'state'     => 'required|string',
            'data'      => 'nullable|array',
        ]);
        if ($data['token'] !== self::BOT_TOKEN) return response()->json(['error' => 'Unauthorized'], 401);

        $session = BotSession::forNumber($data['flow_id'], $data['wa_number']);
        $update  = ['current_state' => $data['state'], 'last_activity_at' => now()];
        if (isset($data['data'])) {
            $update['data'] = array_merge($session->data ?? [], $data['data']);
        }
        $session->update($update);

        return response()->json(['ok' => true]);
    }

    // ── Helper: exportar flujo a JSON local ───────────────────────────────────
    public static function exportFlowJson(string $botType, array $payload): void
    {
        try {
            $path = base_path("whatsbot/flow-{$botType}.json");
            file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } catch (\Throwable) {}
    }

    // ── Bot → Laravel: guardar pedido al recibirlo ────────────────────────────
    public function receiveOrder(Request $request)
    {
        $data = $request->validate([
            'token'        => 'required|string',
            'project_slug' => 'required|string',
            'wa_number'    => 'required|string',
            'client_name'  => 'nullable|string|max:100',
            'n_pedido'     => 'required|string',
            'items'        => 'required|array|min:1',
            'items.*.name'     => 'required|string',
            'items.*.qty'      => 'required|integer|min:1',
            'items.*.price'    => 'required|numeric|min:0',
            'subtotal'     => 'required|numeric',
            'notes'        => 'nullable|string',
        ]);

        if ($data['token'] !== self::BOT_TOKEN) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $project = Project::where('slug', $data['project_slug'])->first();
        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        $order = $project->orders()->create([
            'client_name'  => $data['client_name'] ?: 'Cliente WhatsApp',
            'client_phone' => $data['wa_number'],
            'notes'        => $data['n_pedido'] . (($data['notes'] ?? '') ? "\n" . $data['notes'] : ''),
            'total'        => $data['subtotal'],
            'status'       => 'pending',
            'sales_channel'=> 'whatsapp',
            'wa_number'    => $data['wa_number'],
            'wa_status'    => 'pending',
        ]);

        foreach ($data['items'] as $item) {
            $order->items()->create([
                'name'     => $item['name'],
                'quantity' => $item['qty'],
                'price'    => $item['price'],
            ]);
        }

        return response()->json(['ok' => true, 'order_id' => $order->id]);
    }

    // ── Bot → Laravel: cliente envió comprobante de pago ─────────────────────
    public function paymentReceived(Request $request, Order $order)
    {
        $data = $request->validate(['token' => 'required|string']);
        if ($data['token'] !== self::BOT_TOKEN) return response()->json(['error' => 'Unauthorized'], 401);

        $order->update(['wa_status' => 'pago_recibido']);
        return response()->json(['ok' => true]);
    }

    // ── Bot → Laravel: cliente confirmó entrega ───────────────────────────────
    public function clientConfirmed(Request $request, Order $order)
    {
        $data = $request->validate(['token' => 'required|string', 'confirmed' => 'required|boolean']);
        if ($data['token'] !== self::BOT_TOKEN) return response()->json(['error' => 'Unauthorized'], 401);

        $order->update([
            'wa_status' => $data['confirmed'] ? 'entregado' : 'problema',
            'status'    => $data['confirmed'] ? 'done'      : 'process',
        ]);
        return response()->json(['ok' => true]);
    }

    // ── Portal → Bot: admin toma acción sobre pedido WA ───────────────────────
    public function portalAction(Request $request, Order $order)
    {
        $projectId = request()->routeIs('bixosales.*')
            ? session('comercial_project_id')
            : app('active_project')?->id;
        abort_unless($order->project_id === $projectId, 403);

        $data = $request->validate([
            'action' => 'required|in:confirmar_pago,en_camino,entregado',
        ]);

        $actionMap = [
            'confirmar_pago' => ['wa_status' => 'pago_confirmado', 'status' => 'process'],
            'en_camino'      => ['wa_status' => 'en_camino',       'status' => 'process'],
            'entregado'      => ['wa_status' => 'entregado_espera','status' => 'process'],
        ];

        $order->update($actionMap[$data['action']]);

        // Llama al mini-servidor del bot para que envíe el mensaje
        try {
            Http::timeout(4)->post(self::BOT_URL . '/action', [
                'token'     => self::BOT_TOKEN,
                'wa_number' => $order->wa_number,
                'action'    => $data['action'],
                'order_id'  => $order->id,
            ]);
        } catch (\Throwable $e) {
            // El bot puede estar caído, pero el estado ya se guardó
        }

        return response()->json(['ok' => true, 'wa_status' => $order->wa_status]);
    }

    // ── Bot → Laravel: buscar orden activa por número WA ────────────────────
    public function findOrder(Request $request)
    {
        $data = $request->validate(['token' => 'required|string', 'wa_number' => 'required|string']);
        if ($data['token'] !== self::BOT_TOKEN) return response()->json(['error' => 'Unauthorized'], 401);

        $phone = preg_replace('/\D/', '', $data['wa_number']);
        $order = Order::where('wa_number', $phone)
            ->whereNotIn('wa_status', ['entregado', 'problema'])
            ->latest()
            ->first();

        if (!$order) return response()->json(['ok' => false]);
        return response()->json(['ok' => true, 'order_id' => $order->id]);
    }

    // ── Bot → Laravel: guardar comprobante de pago ───────────────────────────
    public function receivePaymentProof(Request $request, Order $order)
    {
        $data = $request->validate([
            'token'         => 'required|string',
            'image_base64'  => 'required|string',
            'mimetype'      => 'required|string',
        ]);
        if ($data['token'] !== self::BOT_TOKEN) return response()->json(['error' => 'Unauthorized'], 401);

        $ext      = str_contains($data['mimetype'], 'png') ? 'png' : 'jpg';
        $filename = 'payment_proof_' . $order->id . '_' . time() . '.' . $ext;
        $dir      = storage_path('app/public/payment-proofs');
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        file_put_contents($dir . '/' . $filename, base64_decode($data['image_base64']));

        $order->update([
            'payment_proof' => 'payment-proofs/' . $filename,
            'wa_status'     => 'pago_recibido',
        ]);

        return response()->json(['ok' => true]);
    }

    // ── Portal → Bot: actualizar delivery + costo (tras recibir ubicación) ────
    public function updateDelivery(Request $request, Order $order)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($order->project_id === $project->id, 403);

        $data = $request->validate([
            'delivery_address' => 'nullable|string',
            'shipping_cost'    => 'required|numeric|min:0',
        ]);

        $newTotal = $order->total + $data['shipping_cost'];
        $order->update([
            'delivery_address' => $data['delivery_address'] ?? null,
            'shipping_cost'    => $data['shipping_cost'],
            'total'            => $newTotal,
        ]);

        return response()->json(['ok' => true, 'total' => $newTotal]);
    }
}
