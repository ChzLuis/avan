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

    // ✅ ELIMINAR o COMENTAR todo el bloque de envío de WhatsApp
    /*
    try {
        $projectId = $venta->project_id ?? app('active_project')?->id ?? session('comercial_project_id');
        $bot  = BotInstance::where('project_id', $projectId)->where('bot_type', 'rifa')->first();
        $port = $bot?->port ?? 3002;

        $waNum = preg_replace('/\D/', '', $venta->wa_number);
        if (strlen($waNum) > 13) $waNum = substr($waNum, -11);

        $mensaje = "🎉 *¡TU SUSCRIPCIÓN A LA WEB está confirmada!*\n\n"
                 . "🎟️ *Plan:* " . ($venta->rifa?->nombre ?? $venta->plan_nombre) . "\n"
                 . "🎫 *Tickets:* {$venta->tickets}\n"
                 . "🔢 *N° de Ticket de Membresia:* {$pedido}\n\n"
                 . "🎫 *YA PUEDES VER TUS TICKETS AQUÍ:* 👇\n"
                 . "🔗 https://pruebatusuerte.com.pe/consulta-ticket/";

        \Illuminate\Support\Facades\Http::timeout(5)->post("http://127.0.0.1:{$port}/action", [
            'token'     => 'wa-bot-secret-2024',
            'action'    => 'send_message',
            'wa_number' => $waNum,
            'message'   => $mensaje,
        ]);
    } catch (\Throwable $e) {
        // Silencioso
    }
    */

    // Notificar al bot para que continúe pidiendo datos al cliente
    try {
        $projectId = $venta->project_id;
        $bot  = \App\Models\BotInstance::where('project_id', $projectId)->where('bot_type', 'rifa')->first();
        $port = $bot?->port ?? 3002;
        $waNum = preg_replace('/\D/', '', $venta->wa_number);
        \Illuminate\Support\Facades\Http::timeout(5)->post("http://127.0.0.1:{$port}/action", [
            'token'     => 'wa-bot-secret-2024',
            'action'    => 'pago_aprobado',
            'wa_number' => $waNum,
        ]);
    } catch (\Throwable $e) {}

    return response()->json(['ok' => true, 'venta' => $venta]);
}

public function enviarConMembresia(Request $request, RifaVenta $venta)
{
    // Verificar si existe la venta
    if (!$venta) {
        return response()->json(['ok' => false, 'error' => 'Venta no encontrada'], 404);
    }
    
    // Solo permitir si está pagado o enviado
    if (!in_array($venta->status, ['pagado', 'enviado'])) {
        return response()->json(['ok' => false, 'error' => 'El pedido no está pagado. Estado actual: ' . $venta->status], 422);
    }
    
    $validated = $request->validate([
        'numero_membresia' => 'required|string|max:500'
    ]);

    $numeroMembresia = $validated['numero_membresia'];
    $ticketNumbers   = $request->input('ticket_numbers', []);

    $venta->update([
        'ticket_code'    => $numeroMembresia,
        'ticket_numbers' => !empty($ticketNumbers) ? $ticketNumbers : null,
        'status'         => 'enviado',
    ]);
    
    // ✅ ENVIAR MENSAJE DE WHATSAPP (ACTIVADO)
    try {
        $projectId = $venta->project_id ?? app('active_project')?->id ?? session('comercial_project_id');
        $bot = BotInstance::where('project_id', $projectId)->where('bot_type', 'rifa')->first();
        $port = $bot?->port ?? 3002;
        \Log::info('Bot port: ' . $port . ' project: ' . $projectId . ' bot_id: ' . ($bot?->id ?? 'null'));
        
        $waNum = preg_replace('/\D/', '', $venta->wa_number);
        if (strlen($waNum) > 13) $waNum = substr($waNum, -11);
        
        $planNombre  = $venta->rifa?->nombre ?? $venta->plan_nombre;
        $listaTickets = !empty($ticketNumbers)
            ? implode(', ', $ticketNumbers)
            : $numeroMembresia;
        $mensaje = "🎉 *¡Tu SUSCRIPCIÓN A LA WEB está confirmada!*\n\n"
                 . "🎟️ Plan: {$planNombre}\n"
                 . "🎫 Tickets: {$venta->tickets}\n"
                 . "🔢 Número de Ticket de membresía : {$listaTickets}\n\n"
                 . "🎫 YA PUEDES VER TUS TICKETS AQUÍ: 👇\n"
                 . "🔗 https://pruebatusuerte.com.pe/consulta-ticket/\n\n"
                 . "¡Mucha suerte! 🍀🍀🍀";
        
        // Intentar enviar por whatsapp-web.js (bot local)
        $response = \Illuminate\Support\Facades\Http::timeout(10)->post("http://127.0.0.1:{$port}/action", [
            'token'     => 'wa-bot-secret-2024',
            'action'    => 'send_message',
            'wa_number' => $waNum,
            'message'   => $mensaje,
        ]);
        \Log::info('WhatsApp enviado a: ' . $waNum . ' - Status: ' . $response->status());

        // También enviar por Meta Cloud API si el usuario tiene canal Meta
        $waNumFull = preg_replace('/\D/', '', $venta->wa_number);
        $canalRow = \Illuminate\Support\Facades\DB::table('wa_canales')
            ->where('bot_type', 'rifa')
            ->where('project_id', $venta->project_id)
            ->whereNotNull('phone_number_id')
            ->where('phone_number_id', '!=', '')
            ->first();
        if ($canalRow && $canalRow->access_token && $canalRow->phone_number_id) {
            $metaRes = \Illuminate\Support\Facades\Http::withToken($canalRow->access_token)
                ->post("https://graph.facebook.com/v19.0/{$canalRow->phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $waNumFull,
                    'type'              => 'text',
                    'text'              => ['body' => $mensaje, 'preview_url' => true],
                ]);
            \Log::info('Meta membresía: status=' . $metaRes->status() . ' to=' . $waNumFull);
        }
        
    } catch (\Throwable $e) {
        \Log::error('Error WhatsApp membresía: ' . $e->getMessage());
        // No fallamos la respuesta, solo registramos el error
    }
    
    return response()->json(['ok' => true, 'numero_membresia' => $numeroMembresia]);
}

    // ── Enviar ticket por WhatsApp ───────────────────────────
    public function enviarTicket(RifaVenta $venta)
    {
        abort_unless(in_array($venta->status, ['pagado', 'enviado']), 422);
        $venta->update(['status' => 'enviado']);

        // Notificar al bot para que envíe mensaje final al cliente
        try {
            $bot  = \App\Models\BotInstance::where('project_id', $venta->project_id)->where('bot_type', 'rifa')->first();
            $port = $bot?->port ?? 3002;
            $waNum = preg_replace('/\D/', '', $venta->wa_number);
            \Illuminate\Support\Facades\Http::timeout(5)->post("http://127.0.0.1:{$port}/action", [
                'token'     => 'wa-bot-secret-2024',
                'action'    => 'tickets_enviados',
                'wa_number' => $waNum,
            ]);
        } catch (\Throwable $e) {}

        return response()->json(['ok' => true]);
    }

    // ── Portal Comercial — lista de ventas ───────────────────────
    public function indexComercial()
    {
        $project = \App\Models\Project::findOrFail(session('comercial_project_id'));

        // Buscar ventas del proyecto, o del bot rifa si el project_id del seeder difiere
        $projectIds = collect([$project->id]);
        $botProject = \App\Models\BotInstance::where('bot_type', 'rifa')->value('project_id');
        if ($botProject && !$projectIds->contains($botProject)) {
            $projectIds->push($botProject);
        }

        $ventas = RifaVenta::with('rifa')
                    ->whereIn('project_id', $projectIds)
                    ->orderByDesc('created_at')
                    ->get();

        $ventasJson = $ventas->keyBy('id')->map(fn($v) => [
            'id'            => $v->id,
            'nombre'        => $v->nombre,
            'dni'           => $v->dni,
            'wa_number'     => $v->wa_number,
            'ciudad'        => $v->ciudad,
            'plan_nombre'   => $v->rifa?->nombre ?? $v->plan_nombre,
            'tickets'       => $v->tickets,
            'monto'         => $v->monto,
            'ticket_code'    => $v->ticket_code,
            'ticket_numbers' => $v->ticket_numbers ?? [],
            'payment_proof'  => $v->payment_proof ? asset($v->payment_proof) : null,
            'status'        => $v->status,
            'created_at'    => $v->created_at->timezone('America/Lima')->format('d/m/Y H:i'),
        ])->values()->keyBy('id');

        return view('comercial.rifas', compact('project', 'ventas', 'ventasJson'));
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
                        'created_at'    => $v->created_at->timezone('America/Lima')->format('d/m/Y H:i'),
                    ]);
        return response()->json(['ventas' => $ventas]);
    }

    public function cancelar(RifaVenta $venta)
    {
        $venta->update(['status' => 'cancelado']);
        return response()->json(['ok' => true]);
    }

    // ── Preview de diseño con datos ficticios ────────────────────
    public function ticketDesign(Request $request)
    {
        if (!auth()->check() && $request->get('token') !== 'wa-bot-secret-2024') abort(403);
        return view('rifas.ticket', [
            'negocio'      => 'Prueba tu Suerte',
            'negocio_logo' => null,
            'evento'       => 'Gran Rifa Aniversario 2026',
            'rifa_nombre'  => 'Gran Rifa Aniversario',
            'banner_url'   => null,
            'nombre'       => 'Juan Pérez García',
            'dni'          => '12345678',
            'celular'      => '+51 999 888 777',
            'ciudad'       => 'Lima',
            'ticket_code'  => 'TK-A8X2F1',
            'precio'       => '25.00',
        ]);
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
    /** GET /wa/rifas — lista de productos activos para el bot */
    public function botList(Request $request)
    {
        $token = $request->get('token');
        if ($token !== 'wa-bot-secret-2024') return response()->json(['ok'=>false], 401);

        $botType = $request->get('bot', 'rifa');
        $bot     = BotInstance::where('bot_type', $botType)->first();
        if (!$bot) return response()->json(['ok' => false, 'error' => 'Bot no encontrado'], 404);

        $products = \App\Models\Product::allProjects()->where('project_id', $bot->project_id)
                     ->where('is_available', true)
                     ->with('mainImage')
                     ->orderBy('price')
                     ->get()
                     ->map(fn($p) => [
                         'id'          => $p->id,
                         'nombre'      => $p->name,
                         'descripcion' => $p->description ?? '',
                         'precio'      => (float) $p->price,
                         'imagen_url'  => $p->mainImage?->url ? (str_starts_with($p->mainImage->url, 'http') ? $p->mainImage->url : asset('storage/' . $p->mainImage->url)) : null,
                         'texto'       => "*{$p->name}*\n💰 S/ " . number_format($p->price, 2) . ($p->description ? "\n_{$p->description}_" : ''),
                     ]);

        return response()->json(['ok' => true, 'rifas' => $products]);
    }

    /** POST /wa/rifa-order — crear pedido desde el bot */
    public function botCreateOrder(Request $request)
    {
        $token = $request->get('token') ?? $request->input('token');
        if ($token !== 'wa-bot-secret-2024') return response()->json(['ok'=>false], 401);

        $itemId   = $request->input('rifa_id');   // puede ser product_id
        $tickets  = (int) $request->input('tickets', 1);
        $waNumber = $request->input('wa_number');
        $nombre   = $request->input('nombre');
        $dni      = $request->input('dni');

        // Buscar primero en Product, fallback a Rifa
        $product = \App\Models\Product::find($itemId);

        if ($product) {
            // Usar monto enviado por el bot; fallback al precio del producto
            $monto = $request->input('monto') ? (float) $request->input('monto') : (float) $product->price;
            $ciudad = $request->input('ciudad');
            $venta = RifaVenta::create([
                'project_id'  => $product->project_id,
                'order_number'=> RifaVenta::generateOrderNumber(),
                'wa_number'   => $waNumber,
                'plan'        => 'bot',
                'plan_nombre' => $product->name,
                'tickets'     => $tickets,
                'monto'       => $monto,
                'nombre'      => $nombre,
                'dni'         => $dni,
                'ciudad'      => $ciudad,
                'status'      => 'pendiente',
            ]);

            return response()->json([
                'ok'           => true,
                'order_id'     => $venta->id,
                'order_number' => $venta->order_number,
                'monto'        => $monto,
                'tickets'      => $tickets,
                'rifa_nombre'  => $product->name,
            ]);
        }

        $rifa = Rifa::find($itemId);
        if (!$rifa) return response()->json(['ok' => false, 'error' => 'Producto no encontrado'], 404);

        $monto = $rifa->precio_ticket * $tickets;

        $venta = RifaVenta::create([
            'project_id'  => $rifa->project_id,
            'rifa_id'     => $rifa->id,
            'order_number'=> RifaVenta::generateOrderNumber(),
            'wa_number'   => $waNumber,
            'plan'        => 'bot',
            'plan_nombre' => $rifa->nombre,
            'tickets'     => $tickets,
            'monto'       => $monto,
            'nombre'      => $nombre,
            'dni'         => $dni,
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
            $venta->update([
                'payment_proof' => 'uploads/rifas/' . $name,
                'status'        => 'comprobante',
                'nombre'        => $request->input('nombre', $venta->nombre),
                'wa_number'     => $request->input('wa_number', $venta->wa_number),
            ]);
        }
        return response()->json(['ok' => true]);
    }

    public function botUpdateData(Request $request, RifaVenta $venta)
    {
        $venta->update($request->only(['nombre', 'dni', 'ciudad', 'email', 'telefono', 'direccion']));
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

    // ── Portal Comercial — eliminar pedido ──────────────────────
    public function eliminarComercial(RifaVenta $venta)
    {
        $project = \App\Models\Project::findOrFail(session('comercial_project_id'));
        $botProject = \App\Models\BotInstance::where('bot_type', 'rifa')->value('project_id');
        $allowed = array_filter([$project->id, $botProject]);
        abort_unless(in_array($venta->project_id, $allowed), 403);

        $venta->delete();
        return response()->json(['ok' => true]);
    }

    // ── Portal Comercial — editar datos del participante ────────
    public function editarComercial(Request $request, RifaVenta $venta)
    {
        $project = \App\Models\Project::findOrFail(sessionA('comercial_project_id'));

        $botProject = \App\Models\BotInstance::where('bot_type', 'rifa')->value('project_id');
        $allowed = [$project->id];
        if ($botProject) $allowed[] = $botProject;
        abort_unless(in_array($venta->project_id, $allowed), 403);

        $data = $request->validate([
            'nombre' => 'nullable|string|max:100',
            'dni'    => 'nullable|string|max:20',
            'ciudad' => 'nullable|string|max:100',
        ]);

        $venta->update(array_filter($data, fn($v) => $v !== null));
        return response()->json(['ok' => true]);
    }
}
