<?php

namespace App\Http\Controllers;

use App\Models\RifaVenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RifaController extends Controller
{
    // ── Panel admin ──────────────────────────────────────────
    public function index()
    {
        $project = app('active_project');
        $ventas  = RifaVenta::where('project_id', $project->id)
                    ->orderByDesc('created_at')->get();
        return view('rifas.index', compact('project', 'ventas'));
    }

    public function validar(RifaVenta $venta)
    {
        abort_unless($venta->status === 'pendiente', 422);
        $venta->update([
            'status'      => 'pagado',
            'ticket_code' => strtoupper(Str::random(8)),
        ]);
        return response()->json(['ok' => true, 'venta' => $venta]);
    }

    public function enviarTicket(RifaVenta $venta)
    {
        abort_unless($venta->status === 'pagado', 422);

        $botUrl = rtrim(env('BOT_URL', 'http://localhost:3001'), '/');

        $mensaje = "🎟️ *¡Tu boleto de rifa está listo!*\n\n"
                 . "👤 *Nombre:* {$venta->nombre}\n"
                 . "🪪 *DNI:* {$venta->dni}\n"
                 . "📦 *Plan:* {$venta->plan_nombre}\n"
                 . "🎫 *Tickets:* {$venta->tickets}\n"
                 . "💰 *Monto pagado:* S/ {$venta->monto}\n"
                 . "🔑 *Código:* *{$venta->ticket_code}*\n\n"
                 . "¡Mucha suerte! 🍀";

        Http::post("{$botUrl}/send-message", [
            'to'      => $venta->wa_number . '@c.us',
            'message' => $mensaje,
        ]);

        $venta->update(['status' => 'enviado']);
        return response()->json(['ok' => true]);
    }

    public function cancelar(RifaVenta $venta)
    {
        $venta->update(['status' => 'cancelado']);
        return response()->json(['ok' => true]);
    }

    // ── Bot API ───────────────────────────────────────────────
    public function botSave(Request $request)
    {
        $planes = RifaVenta::planes();
        $plan   = (int) $request->plan;

        if (!isset($planes[$plan])) {
            return response()->json(['ok' => false, 'error' => 'Plan inválido'], 422);
        }

        $info  = $planes[$plan];
        $venta = RifaVenta::updateOrCreate(
            ['wa_number' => $request->wa_number, 'status' => 'pendiente', 'plan' => $plan],
            [
                'project_id' => $request->project_id,
                'plan_nombre'=> $info['nombre'],
                'tickets'    => $info['tickets'],
                'monto'      => $info['monto'],
            ]
        );

        return response()->json(['ok' => true, 'id' => $venta->id]);
    }

    public function botPaymentProof(Request $request, RifaVenta $venta)
    {
        if ($request->has('image_base64')) {
            $dir  = public_path('uploads/rifas');
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $name = 'proof_' . $venta->id . '_' . time() . '.jpg';
            file_put_contents($dir . '/' . $name, base64_decode($request->image_base64));
            $venta->update(['payment_proof' => 'uploads/rifas/' . $name]);
        }
        return response()->json(['ok' => true]);
    }

    public function botUpdateData(Request $request, RifaVenta $venta)
    {
        $venta->update($request->only(['nombre', 'dni']));
        return response()->json(['ok' => true]);
    }
}
