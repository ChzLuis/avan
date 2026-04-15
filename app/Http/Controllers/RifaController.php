<?php

namespace App\Http\Controllers;

use App\Models\Rifa;
use App\Models\RifaVenta;
use App\Models\BotInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class RifaController extends Controller
{
    // ── Panel admin — Ventas ─────────────────────────────────
    public function index()
    {
        $project = app('active_project');
        $ventas  = RifaVenta::with('rifa')
                    ->where('project_id', $project->id)
                    ->orderByDesc('created_at')->get();
        $rifas   = Rifa::where('project_id', $project->id)
                    ->where('is_active', true)->orderBy('sort_order')->get();
        return view('rifas.index', compact('project', 'ventas', 'rifas'));
    }

    // ── CRUD Rifas (catálogo) ────────────────────────────────
    public function rifaStore(Request $request)
    {
        $project = app('active_project');
        $data    = $request->validate([
            'nombre'        => 'required|string|max:100',
            'descripcion'   => 'nullable|string|max:300',
            'precio_ticket' => 'required|numeric|min:0.01',
            'min_tickets'   => 'required|integer|min:1',
            'max_tickets'   => 'nullable|integer|min:1',
            'imagen_url'    => 'nullable|url|max:500',
            'premio'        => 'nullable|string|max:200',
            'sort_order'    => 'integer',
        ]);
        $rifa = Rifa::create(array_merge($data, ['project_id' => $project->id]));
        return response()->json(['ok' => true, 'rifa' => $rifa]);
    }

    public function rifaUpdate(Request $request, Rifa $rifa)
    {
        $project = app('active_project');
        abort_unless($rifa->project_id === $project->id, 403);
        $data = $request->validate([
            'nombre'        => 'required|string|max:100',
            'descripcion'   => 'nullable|string|max:300',
            'precio_ticket' => 'required|numeric|min:0.01',
            'min_tickets'   => 'required|integer|min:1',
            'max_tickets'   => 'nullable|integer|min:1',
            'imagen_url'    => 'nullable|url|max:500',
            'premio'        => 'nullable|string|max:200',
            'sort_order'    => 'integer',
            'is_active'     => 'boolean',
        ]);
        $rifa->update($data);
        return response()->json(['ok' => true, 'rifa' => $rifa]);
    }

    public function rifaDestroy(Rifa $rifa)
    {
        $project = app('active_project');
        abort_unless($rifa->project_id === $project->id, 403);
        $rifa->delete();
        return response()->json(['ok' => true]);
    }

    // ── Confirmar pago ───────────────────────────────────────
    public function confirmarPago(RifaVenta $venta)
    {
        abort_unless(in_array($venta->status, ['pendiente','pagado']), 422);

        $numbers = $venta->assignTicketNumbers();
        $venta->update([
            'status'         => 'pagado',
            'ticket_code'    => $venta->ticket_code ?: strtoupper(Str::random(8)),
            'ticket_numbers' => $numbers,
        ]);

        return response()->json(['ok' => true, 'venta' => $venta]);
    }

    // ── Enviar ticket por WhatsApp ───────────────────────────
    public function enviarTicket(RifaVenta $venta)
    {
        abort_unless($venta->status === 'pagado', 422);
        $project = app('active_project');

        // Encontrar la instancia del bot rifa
        $bot    = BotInstance::where('project_id', $project->id)
                    ->where('bot_type', 'rifa')->first();
        $port   = $bot?->port ?? 3002;
        $botUrl = "http://127.0.0.1:{$port}";

        $numeros = $venta->ticket_numbers
            ? implode(', ', array_map(fn($n) => str_pad($n, 5, '0', STR_PAD_LEFT), $venta->ticket_numbers))
            : 'Por asignar';

        // Generar imagen del ticket
        $ticketBase64 = $this->generateTicketImage($venta);

        $mensaje = "🎉 *¡Tu participación está confirmada!*\n\n"
                 . "📋 *Pedido:* {$venta->order_number}\n"
                 . "🎟️ *Rifa:* " . ($venta->rifa?->nombre ?? $venta->plan_nombre) . "\n"
                 . "🎫 *Tickets:* {$venta->tickets}\n"
                 . "🔑 *Código:* *{$venta->ticket_code}*\n"
                 . "🔢 *Números:* {$numeros}\n\n"
                 . "¡Mucha suerte! 🍀🍀🍀";

        try {
            \Illuminate\Support\Facades\Http::timeout(15)->post("{$botUrl}/action", [
                'token'          => 'wa-bot-secret-2024',
                'action'         => 'send_ticket',
                'wa_number'      => $venta->wa_number,
                'message'        => $mensaje,
                'ticket_base64'  => $ticketBase64,
                'ticket_mime'    => 'image/png',
            ]);
        } catch (\Throwable $e) {
            // Bot no disponible
        }

        $venta->update(['status' => 'enviado']);
        return response()->json(['ok' => true]);
    }

    // ── JSON para panel de bots ──────────────────────────────────
    public function ventasJson()
    {
        $project = app('active_project');
        $ventas  = RifaVenta::with('rifa')
                    ->where('project_id', $project->id)
                    ->orderByDesc('created_at')
                    ->limit(100)
                    ->get()
                    ->map(fn($v) => [
                        'id'            => $v->id,
                        'nombre'        => $v->nombre,
                        'dni'           => $v->dni,
                        'ciudad'        => $v->ciudad,
                        'wa_number'     => $v->wa_number,
                        'plan_nombre'   => $v->plan_nombre ?? $v->rifa?->nombre,
                        'tickets'       => $v->tickets,
                        'monto'         => $v->monto,
                        'ticket_code'   => $v->ticket_code,
                        'ticket_numbers'=> $v->ticket_numbers,
                        'payment_proof' => $v->payment_proof,
                        'status'        => $v->status,
                        'created_at'    => $v->created_at->format('d/m/Y H:i'),
                    ]);
        return response()->json(['ventas' => $ventas]);
    }

    public function cancelar(RifaVenta $venta)
    {
        $venta->update(['status' => 'cancelado']);
        return response()->json(['ok' => true]);
    }

    // ── Vista previa del ticket (HTML para screenshot) ──────────
    public function ticketPreview(RifaVenta $venta, Request $request)
    {
        if ($request->get('token') !== 'wa-bot-secret-2024' && !auth()->check()) abort(403);

        $rifa    = $venta->rifa;
        $project = $venta->project;

        return view('rifas.ticket', [
            'negocio'      => $project?->name ?? 'Prueba tu Suerte',
            'negocio_logo' => null,
            'evento'       => $rifa?->descripcion ?? '',
            'rifa_nombre'  => $rifa?->nombre ?? $venta->plan_nombre,
            'banner_url'   => $rifa?->imagen_url,
            'nombre'       => $venta->nombre ?? 'Participante',
            'dni'          => $venta->dni ?? '—',
            'celular'      => $venta->wa_number,
            'ciudad'       => $venta->ciudad ?? '—',
            'ticket_code'  => $venta->ticket_code ?? '—',
            'precio'       => number_format($venta->monto / max($venta->tickets, 1), 2),
        ]);
    }

    /** Genera imagen PNG del ticket usando Puppeteer y la devuelve como base64 */
    private function generateTicketImage(RifaVenta $venta): ?string
    {
        $url      = route('rifas.ticket.preview', ['venta' => $venta->id, 'token' => 'wa-bot-secret-2024']);
        $outFile  = storage_path("app/public/tickets/ticket-{$venta->id}.png");
        $outDir   = dirname($outFile);
        if (!is_dir($outDir)) mkdir($outDir, 0755, true);

        $script   = base_path('whatsbot/ticket-generator.js');
        $node     = trim(shell_exec('which node') ?: '/usr/bin/node');

        $cmd = "{$node} {$script} --url=" . escapeshellarg($url)
             . " --output=" . escapeshellarg($outFile) . " 2>&1";
        shell_exec($cmd);

        if (file_exists($outFile)) {
            return base64_encode(file_get_contents($outFile));
        }
        return null;
    }

    // ── Bot API ───────────────────────────────────────────────
    /** GET /wa/rifas — lista de rifas activas para el bot */
    public function botList(Request $request)
    {
        $token = $request->get('token');
        if ($token !== 'wa-bot-secret-2024') return response()->json(['ok'=>false], 401);

        // Detectar proyecto por bot_type
        $botType = $request->get('bot', 'rifa');
        $bot     = BotInstance::where('bot_type', $botType)->first();
        if (!$bot) return response()->json(['ok' => false, 'error' => 'Bot no encontrado'], 404);

        $rifas = Rifa::where('project_id', $bot->project_id)
                     ->where('is_active', true)
                     ->orderBy('sort_order')
                     ->get()
                     ->map(fn($r) => [
                         'id'            => $r->id,
                         'nombre'        => $r->nombre,
                         'descripcion'   => $r->descripcion,
                         'precio_ticket' => (float) $r->precio_ticket,
                         'min_tickets'   => $r->min_tickets,
                         'max_tickets'   => $r->max_tickets,
                         'imagen_url'    => $r->imagen_url,
                         'premio'        => $r->premio,
                         'texto'         => $r->formatoParaBot(),
                     ]);

        return response()->json(['ok' => true, 'rifas' => $rifas]);
    }

    /** POST /wa/rifa-order — crear pedido desde el bot */
    public function botCreateOrder(Request $request)
    {
        $token = $request->get('token') ?? $request->input('token');
        if ($token !== 'wa-bot-secret-2024') return response()->json(['ok'=>false], 401);

        $rifaId   = $request->input('rifa_id');
        $tickets  = (int) $request->input('tickets', 1);
        $waNumber = $request->input('wa_number');

        $rifa = Rifa::find($rifaId);
        if (!$rifa) return response()->json(['ok' => false, 'error' => 'Rifa no encontrada'], 404);

        $monto = $rifa->precio_ticket * $tickets;

        $venta = RifaVenta::create([
            'project_id'  => $rifa->project_id,
            'rifa_id'     => $rifa->id,
            'order_number'=> RifaVenta::generateOrderNumber(),
            'wa_number'   => $waNumber,
            'plan_nombre' => $rifa->nombre,
            'tickets'     => $tickets,
            'monto'       => $monto,
            'status'      => 'pendiente',
        ]);

        return response()->json([
            'ok'           => true,
            'order_id'     => $venta->id,
            'order_number' => $venta->order_number,
            'monto'        => $monto,
            'tickets'      => $tickets,
            'rifa_nombre'  => $rifa->nombre,
        ]);
    }

    public function botPaymentProof(Request $request, RifaVenta $venta)
    {
        if ($request->has('image_base64')) {
            $dir = public_path('uploads/rifas');
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $name = 'proof_' . $venta->id . '_' . time() . '.jpg';
            file_put_contents($dir . '/' . $name, base64_decode($request->image_base64));
            $venta->update(['payment_proof' => 'uploads/rifas/' . $name]);
        }
        return response()->json(['ok' => true]);
    }

    public function botUpdateData(Request $request, RifaVenta $venta)
    {
        $venta->update($request->only(['nombre', 'dni', 'ciudad']));
        return response()->json(['ok' => true]);
    }

    // Mantener compatibilidad
    public function botSave(Request $request)
    {
        return $this->botCreateOrder($request);
    }

    public function validar(RifaVenta $venta)
    {
        return $this->confirmarPago($venta);
    }
}
