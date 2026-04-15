<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Project;
use Illuminate\Http\Request;

/**
 * Maneja los pagos del catálogo público (Avan Store).
 * Soporta:
 *   - Manual (Yape/Plin/transferencia): el cliente reporta número de operación
 *   - Culqi (pasarela peruana): charge con token JS SDK
 *   - Mercado Pago: preference + webhook IPN
 *
 * Para agregar nuevas pasarelas en el futuro, añadir un método handle{Gateway}()
 * y registrar la ruta correspondiente.
 */
class PaymentController extends Controller
{
    // ─── Confirmar pago manual (número de operación ingresado por el cliente) ───
    public function confirmManual(Request $request, string $slug, Order $order)
    {
        $project = Project::where('slug', $slug)->where('is_active', true)->firstOrFail();
        abort_unless($order->project_id === $project->id, 404);

        $data = $request->validate([
            'reference' => 'required|string|max:100',
        ]);

        $order->update([
            'payment_status'    => 'paid',
            'payment_reference' => $data['reference'],
            'payment_gateway'   => 'manual',
            'status'            => 'pending', // queda en pending para que el negocio confirme
        ]);

        return response()->json(['ok' => true]);
    }

    // ─── Culqi: recibir token del SDK JS y hacer el cargo ───────────────────────
    public function chargeCulqi(Request $request, string $slug, Order $order)
    {
        $project = Project::where('slug', $slug)->where('is_active', true)->firstOrFail();
        abort_unless($order->project_id === $project->id, 404);

        $token      = $request->input('token');
        $privateKey = $project->setting('culqi_secret_key');

        if (!$token || !$privateKey) {
            return response()->json(['ok' => false, 'message' => 'Configuración de pasarela incompleta.'], 422);
        }

        // Llamada a la API de Culqi
        $amount = (int) round($order->total * 100); // en céntimos
        $ch = curl_init('https://api.culqi.com/v2/charges');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $privateKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'amount'        => $amount,
                'currency_code' => 'PEN',
                'email'         => $order->client_email ?: ($request->input('email') ?: 'cliente@ejemplo.com'),
                'source_id'     => $token,
                'description'   => 'Pedido #' . $order->id . ' — ' . $project->name,
                'metadata'      => ['order_id' => $order->id, 'project' => $project->slug],
            ]),
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 201 && isset($result['id'])) {
            $order->update([
                'payment_status'    => 'paid',
                'payment_reference' => $result['id'],
                'payment_gateway'   => 'culqi',
                'status'            => 'pending',
            ]);
            return response()->json(['ok' => true, 'charge_id' => $result['id']]);
        }

        $errorMsg = $result['user_message'] ?? $result['merchant_message'] ?? 'Error al procesar el pago.';
        return response()->json(['ok' => false, 'message' => $errorMsg], 422);
    }

    // ─── Mercado Pago: crear preferencia de pago ─────────────────────────────
    public function createMpPreference(Request $request, string $slug, Order $order)
    {
        $project = Project::where('slug', $slug)->where('is_active', true)->firstOrFail();
        abort_unless($order->project_id === $project->id, 404);

        $accessToken = $project->setting('mp_access_token');
        if (!$accessToken) {
            return response()->json(['ok' => false, 'message' => 'Pasarela no configurada.'], 422);
        }

        $items = $order->items->map(fn($i) => [
            'title'       => $i->name,
            'quantity'    => (int) $i->quantity,
            'unit_price'  => (float) $i->price,
            'currency_id' => 'PEN',
        ])->toArray();

        $baseUrl = url('/p/' . $slug);

        $ch = curl_init('https://api.mercadopago.com/checkout/preferences');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'items'               => $items,
                'payer'               => ['email' => $order->client_email ?: 'cliente@ejemplo.com'],
                'back_urls'           => [
                    'success' => $baseUrl . '?payment=success&order=' . $order->id,
                    'failure' => $baseUrl . '?payment=failure&order=' . $order->id,
                    'pending' => $baseUrl . '?payment=pending&order=' . $order->id,
                ],
                'notification_url'    => url('/p/' . $slug . '/mp-webhook'),
                'external_reference'  => (string) $order->id,
                'statement_descriptor' => $project->name,
            ]),
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 201 && isset($result['init_point'])) {
            $isSandbox = str_starts_with($accessToken, 'TEST-');
            $order->update([
                'payment_status'  => 'pending_payment',
                'payment_gateway' => 'mercadopago',
                'payment_reference' => $result['id'] ?? null,
            ]);
            return response()->json([
                'ok'                 => true,
                'init_point'         => $result['init_point'],
                'sandbox_init_point' => $isSandbox ? ($result['sandbox_init_point'] ?? $result['init_point']) : null,
                'preference_id'      => $result['id'] ?? null,
                'is_sandbox'         => $isSandbox,
            ]);
        }

        $errMsg = $result['message'] ?? $result['error'] ?? 'Error al crear preferencia de pago.';
        return response()->json(['ok' => false, 'message' => $errMsg], 422);
    }

    // ─── Mercado Pago: webhook IPN ────────────────────────────────────────────
    public function mpWebhook(Request $request, string $slug)
    {
        $project     = Project::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $accessToken = $project->setting('mp_access_token');
        $type        = $request->input('type');

        if ($type === 'payment' && $accessToken) {
            $paymentId = $request->input('data.id');
            // Consultar el pago en MP
            $ch = curl_init('https://api.mercadopago.com/v1/payments/' . $paymentId);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            $payment = json_decode($response, true);

            if (isset($payment['external_reference']) && isset($payment['status'])) {
                $order = Order::where('id', $payment['external_reference'])
                              ->where('project_id', $project->id)
                              ->first();
                if ($order) {
                    $order->update([
                        'payment_status'    => $payment['status'] === 'approved' ? 'paid' : $payment['status'],
                        'payment_reference' => (string) $paymentId,
                        'status'            => $payment['status'] === 'approved' ? 'pending' : $order->status,
                    ]);
                }
            }
        }

        return response()->json(['ok' => true]);
    }
}
