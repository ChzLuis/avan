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
            ->whereIn('payment_status', ['under_review', 'en_revision'])  // canonico + alias legacy
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

        // Aprobar un pago NO completa la venta: el estado comercial no se
        // toca. Antes se escribia status='pagado' —valor ajeno al vocabulario
        // comercial— y era el origen de los pedidos legacy 20-22.
        //
        // F3b: el cobro entra al LIBRO en vez de pisar `payment_status`. Asi la
        // aprobacion del bot es un asiento conciliable, con su origen, y no una
        // mutacion que borra la anterior. La respuesta JSON no cambia.
        $desde = $order->payment_status;
        $saldo = \App\Support\Ledger::saldoCents($project->id, $order);

        if ($saldo > 0) {
            // null = "salda lo que falte", calculado dentro de la transaccion.
            \App\Support\Ledger::registrar($project, $order, null,
                $order->payment_method, $order->payment_reference, 'bot');
        } else {
            // Ya estaba cobrado (reintento del bot): idempotente, no se duplica
            // el asiento ni se devuelve error — el bot reintenta y no debe ver
            // un fallo por algo que ya hizo bien.
            \App\Support\Ledger::proyectar($project, $order);
        }

        $order->refresh();
        $order->notes = trim(($order->notes ? $order->notes . "\n" : '')
            . '✅ Pago APROBADO el ' . now()->format('d/m/Y H:i')
            . (auth()->user() ? ' por ' . auth()->user()->name : ''));
        $order->save();

        \App\Models\OrderEvent::log($project->id, 'payment_status', [
            'from' => $desde, 'to' => 'paid', 'source' => 'aprobacion_bot',
            'user' => auth()->user()?->name,
        ], $order->id);

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
        $desde = $order->payment_status;

        // F3b: si habia cobros registrados se REVIERTEN con su motivo —queda el
        // rastro de que hubo correccion— en vez de pisar el estado. Si no habia
        // ninguno, la proyeccion devuelve el pedido a 'pending' igualmente.
        foreach (\App\Models\Payment::where('project_id', $project->id)
                     ->where('payable_type', 'order')->where('payable_id', $order->id)
                     ->whereNull('reverses_id')->get() as $asiento) {
            if (! \App\Models\Payment::where('reverses_id', $asiento->id)->exists()) {
                \App\Support\Ledger::revertir($asiento, $motivo);
            }
        }
        \App\Support\Ledger::proyectar($project, $order->refresh());

        $order->refresh();
        $order->notes = trim(($order->notes ? $order->notes . "\n" : '')
            . '❌ Pago RECHAZADO el ' . now()->format('d/m/Y H:i') . ' — ' . $motivo);
        $order->save();

        \App\Models\OrderEvent::log($project->id, 'payment_status', [
            'from' => $desde, 'to' => 'pending', 'source' => 'rechazo_bot',
            'motivo' => $motivo, 'user' => auth()->user()?->name,
        ], $order->id);

        return response()->json([
            'ok' => true,
            'pedido' => $order->id,
            'mensaje_cliente' => "⚠️ Sobre tu pedido #{$order->id}: $motivo.\n¿Puedes reenviarnos el comprobante o escribirnos para ayudarte?",
        ]);
    }
}
