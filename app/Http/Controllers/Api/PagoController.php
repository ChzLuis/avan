<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Aprobación de pagos digitales (Yape/Plin) reportados por el cliente en el bot.
 *
 * El bot deja el pedido con payment_status = 'en_revision'. Desde el panel Sales
 * o desde la extensión de WhatsApp, el vendedor aprueba o rechaza el pago.
 * Al aprobar se puede avisar al cliente por WhatsApp.
 */
class PagoController extends Controller
{
    /** Proyecto por token (extensión) o por sesión (panel). */
    private function project(Request $r): Project
    {
        $token = $r->header('X-Copilot-Token') ?? $r->input('token');
        if ($token) {
            $p = Project::where('copilot_token', $token)->first();
            abort_if(!$p, 401, 'Token inválido.');
            return $p;
        }
        $id = session('comunicaciones_project_id') ?? session('active_project_id');
        $p = $id ? Project::find($id) : null;
        abort_if(!$p, 401, 'Sin proyecto activo.');
        return $p;
    }

    /** Pedidos con pago pendiente de aprobación (para el panel y la extensión). */
    public function pendientes(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $q = Order::where('project_id', $project->id)
            ->where('payment_status', 'en_revision')
            ->latest();

        // La extensión puede pedir solo los de un teléfono (el chat abierto).
        if ($tel = $r->input('telefono')) {
            $q->where('client_phone', preg_replace('/\D/', '', $tel));
        }

        return response()->json(['pedidos' => $q->limit(30)->get()->map(fn ($o) => [
            'id'      => $o->id,
            'cliente' => $o->client_name,
            'telefono'=> $o->client_phone,
            'total'   => (float) $o->total,
            'metodo'  => $o->payment_method,
            'notas'   => $o->notes,
            'fecha'   => $o->created_at?->format('d/m H:i'),
        ])->values()]);
    }

    /** Aprobar el pago: marca pagado y avisa al cliente. */
    public function aprobar(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $data = $r->validate(['order_id' => 'required|integer']);
        $order = Order::where('project_id', $project->id)->findOrFail($data['order_id']);

        $order->payment_status = 'pagado';
        $order->status = 'pagado';
        $order->notes = trim(($order->notes ? $order->notes . "\n" : '')
            . '✅ Pago APROBADO el ' . now()->format('d/m/Y H:i')
            . (auth()->user() ? ' por ' . auth()->user()->name : ''));
        $order->save();

        return response()->json([
            'ok' => true,
            'pedido' => $order->id,
            'mensaje_cliente' => "✅ ¡Confirmamos tu pago del pedido #{$order->id}! 🎉\nYa estamos preparando tu entrega. ¡Gracias por tu compra!",
        ]);
    }

    /** Rechazar el pago: lo deja pendiente y explica al cliente. */
    public function rechazar(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $data = $r->validate(['order_id' => 'required|integer', 'motivo' => 'nullable|string|max:200']);
        $order = Order::where('project_id', $project->id)->findOrFail($data['order_id']);

        $motivo = $data['motivo'] ?? 'No pudimos validar el comprobante';
        $order->payment_status = 'pendiente';
        $order->notes = trim(($order->notes ? $order->notes . "\n" : '')
            . '❌ Pago RECHAZADO el ' . now()->format('d/m/Y H:i') . ' — ' . $motivo);
        $order->save();

        return response()->json([
            'ok' => true,
            'pedido' => $order->id,
            'mensaje_cliente' => "⚠️ Sobre tu pedido #{$order->id}: $motivo.\n¿Puedes reenviarnos el comprobante o escribirnos para ayudarte?",
        ]);
    }
}
