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
        $ventas  = RifaVenta::allProjects()->with('rifa')
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
    abort_unless(in_array($venta->status, ['pendiente','comprobante','pagado']), 422);

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
        $mensaje = "🎉 *¡Tu SUSCRIPCIÓN A LA WEB y participacion en el SORTEO POR EL DIA DEL PADRE está confirmada!*\n\n"
                 . "🎟️ Opción: {$planNombre}\n"
                 . "🎫 Tickets: {$venta->tickets}\n"
                 . "🔢 Número de Ticket de membresía : {$listaTickets}\n\n"
                 . "🎫 *YA PUEDES VER TUS TICKETS AQUÍ:* 👇\n"
                 . "🔗 https://pruebatusuerte.com.pe/consulta-ticket/\n\n"
                 . "¡Mucha suerte! 🍀🍀🍀\n\n"
                 . "📅 Fecha de sorteo: Domingo 21 de junio\n"
                 . "🕒 Hora: 03:00 pm\n"
                 . "📍 Lugar: Colegio Mariscal Caceres - Ayacucho\n\n"
                 . "📽️ *Transmisión en vivo:*\n"
                 . "▶️ Facebook: Prueba Tu Suerte Peru\n"
                 . "▶️ Tik Tok: Prueba.Tu.Suerte.Peru";
        
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
    
    // ── Registrar en WordPress (fire & forget via curl) ──
    $partes    = explode(' ', trim($venta->nombre ?? ''), 2);
    $wpNombres = $partes[0] ?? '';
    $wpApellidos = $partes[1] ?? '';
    foreach (!empty($ticketNumbers) ? $ticketNumbers : [$numeroMembresia] as $cod) {
        $payload = escapeshellarg(json_encode([
            'key'       => 'bixo-tickets-2024',
            'codigo'    => $cod,
            'dni'       => $venta->dni       ?? '',
            'nombres'   => $wpNombres,
            'apellidos' => $wpApellidos,
            'correo'    => $venta->email     ?? '',
            'telefono'  => preg_replace('/\D/', '', $venta->wa_number ?? ''),
            'ciudad'    => $venta->ciudad    ?? '',
            'origen'    => 'bixo',
            'estado'    => 'activo',
        ]));
        exec("curl -s -m 5 -X POST -H 'Content-Type: application/json' -d {$payload} https://pruebatusuerte.com.pe/wp-json/bixo/v1/registrar > /dev/null 2>&1 &");
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
    public function indexComercial(\Illuminate\Http\Request $request)
    {
        $project = \App\Models\Project::findOrFail(session('comercial_project_id'));

        // Buscar ventas del proyecto, o del bot rifa si el project_id del seeder difiere
        $projectIds = collect([$project->id]);
        $botProject = \App\Models\BotInstance::where('bot_type', 'rifa')->value('project_id');
        if ($botProject && !$projectIds->contains($botProject)) {
            $projectIds->push($botProject);
        }

        $tz    = 'America/Lima';
        $desde = $request->get('desde');
        $hasta = $request->get('hasta');
        $buscar = trim($request->get('buscar', ''));

        $query = RifaVenta::allProjects()->with('rifa')->whereIn('project_id', $projectIds);

        if ($desde) {
            $query->where('created_at', '>=', \Carbon\Carbon::createFromFormat('Y-m-d', $desde, $tz)->startOfDay()->utc());
        }
        if ($hasta) {
            $query->where('created_at', '<=', \Carbon\Carbon::createFromFormat('Y-m-d', $hasta, $tz)->endOfDay()->utc());
        }
        if ($buscar) {
            $query->where(function($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('dni', 'like', "%{$buscar}%")
                  ->orWhere('wa_number', 'like', "%{$buscar}%");
            });
        }

        $ventas = $query->orderByDesc('created_at')->get();

        $ventaIds = $ventas->pluck('id');
        $recsCounts = \DB::table('rifa_recordatorios')
            ->whereIn('rifa_venta_id', $ventaIds)
            ->select('rifa_venta_id', \DB::raw('COUNT(*) as total'))
            ->groupBy('rifa_venta_id')
            ->pluck('total', 'rifa_venta_id');

        $ventasJson = $ventas->keyBy('id')->map(fn($v) => [
            'id'             => $v->id,
            'nombre'         => $v->nombre,
            'dni'            => $v->dni,
            'wa_number'      => $v->wa_number,
            'ciudad'         => $v->ciudad,
            'telefono'       => $v->telefono,
            'correo'         => $v->email,
            'punto_venta'    => $v->punto_venta,
            'plan_nombre'    => $v->rifa?->nombre ?? $v->plan_nombre,
            'tickets'        => $v->tickets,
            'monto'          => $v->monto,
            'ticket_code'    => $v->ticket_code,
            'ticket_numbers' => $v->ticket_numbers ?? [],
            'payment_proof'  => $v->payment_proof ? asset($v->payment_proof) : null,
            'status'         => $v->status,
            'created_at'     => $v->created_at->timezone('America/Lima')->format('d/m/Y H:i'),
            'recordatorios'  => (int) ($recsCounts[$v->id] ?? 0),
        ])->values()->keyBy('id');

        return view('comercial.rifas', compact('project', 'ventas', 'ventasJson'));
    }

    // ── Panel de Monitoreo del Bot ───────────────────────────────
    public function monitoreo(\Illuminate\Http\Request $request)
    {
        $project = \App\Models\Project::findOrFail(session('comercial_project_id'));

        $projectIds = collect([$project->id]);
        $botProject = \App\Models\BotInstance::where('bot_type', 'rifa')->value('project_id');
        if ($botProject && !$projectIds->contains($botProject)) {
            $projectIds->push($botProject);
        }

        // flow_id del bot rifa
        $flowId = \DB::table('bot_flows')->where('bot_type', 'rifa')->value('id') ?? 1;

        // ── Tráfico general ──
        $totalSesiones = \DB::table('bot_sessions')->where('flow_id', $flowId)->count();
        $usuariosUnicos = \DB::table('bot_sessions')->where('flow_id', $flowId)->distinct('wa_number')->count('wa_number');

        // ── Embudo de conversión (estados) ──
        $estadosRaw = \DB::table('bot_sessions')
            ->where('flow_id', $flowId)
            ->select('current_state', \DB::raw('COUNT(*) as n'))
            ->groupBy('current_state')
            ->pluck('n', 'current_state');

        // Mapa de etapas del embudo (orden lógico)
        $embudo = [
            'inicio'              => ['label' => '👋 Escribieron al bot',   'n' => (int)($estadosRaw['inicio'] ?? 0)],
            'menu_principal'      => ['label' => '📋 Vieron el menú',        'n' => (int)($estadosRaw['menu_principal'] ?? 0)],
            'enviar_qr'           => ['label' => '💳 Recibieron QR de pago', 'n' => (int)($estadosRaw['enviar_qr'] ?? 0)],
            'comprobante_recibido'=> ['label' => '📸 Enviaron comprobante',  'n' => (int)($estadosRaw['comprobante_recibido'] ?? 0)],
            'confirmacion_final'  => ['label' => '✅ Completaron registro',  'n' => (int)($estadosRaw['confirmacion_final'] ?? 0)],
        ];

        // ── Ventas por estado ──
        $ventas = \DB::table('rifa_ventas')
            ->whereIn('project_id', $projectIds)
            ->select('status', \DB::raw('COUNT(*) as n'), \DB::raw('ROUND(SUM(monto),2) as total'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $totalPedidos   = \DB::table('rifa_ventas')->whereIn('project_id', $projectIds)->count();
        $ventasCobradas = (float) \DB::table('rifa_ventas')->whereIn('project_id', $projectIds)
                            ->whereIn('status', ['pagado', 'enviado'])->sum('monto');
        $ticketsVendidos = (int) \DB::table('rifa_ventas')->whereIn('project_id', $projectIds)
                            ->whereIn('status', ['pagado', 'enviado'])->sum('tickets');

        // Tasa de conversión: usuarios que compraron / usuarios que escribieron
        $compradores = \DB::table('rifa_ventas')->whereIn('project_id', $projectIds)
                        ->whereIn('status', ['pagado', 'enviado'])->distinct('wa_number')->count('wa_number');
        $conversion = $usuariosUnicos > 0 ? round(($compradores / $usuariosUnicos) * 100, 1) : 0;

        // ── Tráfico por día (últimos 14 días) ──
        $traficoDias = \DB::table('bot_sessions')
            ->where('flow_id', $flowId)
            ->where('updated_at', '>=', now()->subDays(14))
            ->select(\DB::raw('DATE(updated_at) as dia'), \DB::raw('COUNT(DISTINCT wa_number) as usuarios'))
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        // ── Últimas conversaciones activas ──
        $ultimasSesiones = \DB::table('bot_sessions')
            ->where('flow_id', $flowId)
            ->orderByDesc('updated_at')
            ->limit(15)
            ->get(['wa_number', 'current_state', 'updated_at']);

        // Estado del bot (conexión WhatsApp)
        $botStatus = 'desconocido';
        try {
            $statusFile = base_path('whatsbot/rifa-status.json');
            if (file_exists($statusFile)) {
                $botStatus = json_decode(file_get_contents($statusFile), true)['status'] ?? 'desconocido';
            }
        } catch (\Throwable) {}

        return view('comercial.monitoreo', compact(
            'project', 'totalSesiones', 'usuariosUnicos', 'embudo', 'ventas',
            'totalPedidos', 'ventasCobradas', 'ticketsVendidos', 'conversion',
            'traficoDias', 'ultimasSesiones', 'botStatus'
        ));
    }

    // ── Exportar a Excel (CSV) — solo pedidos completados ────────
    public function exportarComercial(\Illuminate\Http\Request $request)
    {
        $project = \App\Models\Project::findOrFail(session('comercial_project_id'));

        $projectIds = collect([$project->id]);
        $botProject = \App\Models\BotInstance::where('bot_type', 'rifa')->value('project_id');
        if ($botProject && !$projectIds->contains($botProject)) {
            $projectIds->push($botProject);
        }

        $tz     = 'America/Lima';
        $desde  = $request->get('desde');
        $hasta  = $request->get('hasta');
        $estado = $request->get('estado', 'completados'); // por defecto completados

        $query = RifaVenta::allProjects()->with('rifa')->whereIn('project_id', $projectIds);

        // Filtro de estado: "completados" = enviado/pagado (los que tienen ticket)
        if ($estado === 'completados') {
            $query->whereIn('status', ['enviado', 'pagado']);
        } elseif ($estado && $estado !== 'todos') {
            $query->where('status', $estado);
        }

        if ($desde) {
            $query->where('created_at', '>=', \Carbon\Carbon::createFromFormat('Y-m-d', $desde, $tz)->startOfDay()->utc());
        }
        if ($hasta) {
            $query->where('created_at', '<=', \Carbon\Carbon::createFromFormat('Y-m-d', $hasta, $tz)->endOfDay()->utc());
        }

        $ventas = $query->orderByDesc('created_at')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pedidos Completados');

        // ── Título del reporte ──
        $sheet->mergeCells('A1:O1');
        $sheet->setCellValue('A1', 'REPORTE DE PEDIDOS COMPLETADOS — ' . ($project->name ?? 'Rifa'));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '6D28D9']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // ── Subtítulo: fecha de generación y total ──
        $sheet->mergeCells('A2:O2');
        $sheet->setCellValue('A2', 'Generado: ' . now($tz)->format('d/m/Y H:i') . '   |   Total de registros: ' . $ventas->count());
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '6B7280']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        // ── Encabezados ──
        $cols = [
            'N°', 'N° Orden', 'Código / Membresía', 'Números de Ticket',
            'Nombres y Apellidos', 'DNI', 'WhatsApp', 'Teléfono',
            'Correo', 'Dirección', 'Plan', 'Tickets', 'Monto (S/)',
            'Punto de Venta', 'Fecha',
        ];
        $headerRow = 4;
        $sheet->fromArray($cols, null, "A{$headerRow}");
        $lastCol = 'O';
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'BFDBFE']]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(22);

        // ── Filas de datos ──
        $row = $headerRow + 1;
        $i = 1;
        foreach ($ventas as $v) {
            $numeros = [];
            if (!empty($v->ticket_numbers)) {
                $numeros = array_merge($numeros, (array) $v->ticket_numbers);
            }
            if ($v->ticket_code) {
                array_unshift($numeros, $v->ticket_code);
            }
            $numerosStr = implode(', ', array_unique(array_filter($numeros)));

            $sheet->fromArray([
                $i,
                $v->order_number,
                $v->ticket_code ?? '',
                $numerosStr,
                $v->nombre ?? '',
                $v->dni ? '="' . $v->dni . '"' : '',
                $v->wa_number ?? '',
                $v->telefono ?? '',
                $v->email ?? '',
                $v->ciudad ?? '',
                $v->rifa?->nombre ?? $v->plan_nombre,
                $v->tickets,
                number_format((float) $v->monto, 2),
                $v->punto_venta ?? '',
                optional($v->created_at)->timezone('America/Lima')->format('d/m/Y H:i'),
            ], null, "A{$row}");

            // Zebra (filas alternas)
            if ($i % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F3F4F6');
            }
            $row++;
            $i++;
        }

        // ── Bordes y alineación de toda la tabla de datos ──
        $dataRange = "A{$headerRow}:{$lastCol}" . ($row - 1);
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            'font'    => ['size' => 10],
        ]);
        // Centrar columnas N°, Tickets, Monto
        foreach (['A', 'L', 'M'] as $c) {
            $sheet->getStyle("{$c}5:{$c}" . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        }

        // ── Anchos de columna ──
        $widths = ['A' => 5, 'B' => 12, 'C' => 16, 'D' => 28, 'E' => 28, 'F' => 12, 'G' => 14, 'H' => 12, 'I' => 24, 'J' => 26, 'K' => 18, 'L' => 8, 'M' => 11, 'N' => 16, 'O' => 16];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // ── Congelar encabezado ──
        $sheet->freezePane("A" . ($headerRow + 1));

        $filename = 'pedidos_completados_' . now($tz)->format('Y-m-d_His') . '.xlsx';

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ── JSON para panel de bots ──────────────────────────────────
    public function ventasJson()
    {
        $project = app('active_project');
        $ventas  = RifaVenta::allProjects()->with('rifa')
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

        // Notificar al cliente por WhatsApp
        if ($venta->wa_number) {
            $mensaje = "Muchas gracias por su interés 🙏\n\n"
                . "En esta ocasión no pudimos procesar su solicitud porque el comprobante de pago no fue adjuntado o no se visualiza correctamente.\n\n"
                . "Si desea intentarlo nuevamente, escribe *hola* y con gusto le ayudamos 😊\n\n"
                . "_Quedamos atentos para cualquier consulta._";

            try {
                $project = app('active_project');
                $botPort = $project->setting('bot_port') ?? 3003;
                $response = \Illuminate\Support\Facades\Http::timeout(5)->post("http://127.0.0.1:{$botPort}/action", [
                    'token'     => 'wa-bot-secret-2024',
                    'action'    => 'send_message',
                    'wa_number' => $venta->wa_number,
                    'message'   => $mensaje,
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Bot notify cancelar failed: ' . $e->getMessage());
            }
        }

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
                     ->map(function($p) {
                         // Extraer número de tickets de la descripción si existe, si no calcular por precio
                         $tickets = 1;
                         if ($p->description && preg_match('/(\d+)\s*ticket/i', $p->description, $m)) {
                             $tickets = (int) $m[1];
                         } else {
                             $price = (float) $p->price;
                             if ($price >= 100) $tickets = 10;
                             elseif ($price >= 50) $tickets = 5;
                             elseif ($price >= 20) $tickets = 2;
                             else $tickets = 1;
                         }
                         return [
                             'id'          => $p->id,
                             'nombre'      => $p->name,
                             'descripcion' => $p->description ?? '',
                             'precio'      => (float) $p->price,
                             'tickets'     => $tickets,
                             'imagen_url'  => $p->mainImage?->url ? (str_starts_with($p->mainImage->url, 'http') ? $p->mainImage->url : asset('storage/' . $p->mainImage->url)) : null,
                             'texto'       => "*{$p->name}*\n💰 S/ " . number_format($p->price, 2) . ($p->description ? "\n_{$p->description}_" : ''),
                         ];
                     });

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

        // Pedido sin plan definido — el responsable pone tickets/monto al validar
        if ($request->boolean('sin_definir')) {
            $rifaRef = Rifa::find($itemId);
            $projectId = $rifaRef?->project_id
                ?? \App\Models\BotInstance::where('bot_type','rifa')->value('project_id');
            $venta = RifaVenta::create([
                'project_id'  => $projectId,
                'rifa_id'     => $rifaRef?->id,
                'order_number'=> RifaVenta::generateOrderNumber(),
                'wa_number'   => $waNumber,
                'plan'        => 'bot',
                'plan_nombre' => 'Por validar',
                'tickets'     => 0,
                'monto'       => 0,
                'status'      => 'pendiente',
            ]);
            return response()->json([
                'ok'           => true,
                'order_id'     => $venta->id,
                'order_number' => $venta->order_number,
                'monto'        => 0,
                'tickets'      => 0,
                'rifa_nombre'  => 'Por validar',
            ]);
        }

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

    // ── Consultar DNI RENIEC ─────────────────────────────────────
    public function consultarDni($dni)
    {
        try {
            $res = \Illuminate\Support\Facades\Http::timeout(5)
                ->get('https://api.apis.net.pe/v1/dni', ['numero' => $dni]);
            return response()->json($res->json());
        } catch (\Throwable $e) {
            return response()->json(['error' => 'No se pudo consultar'], 500);
        }
    }

    // ── Portal Comercial — nuevo ticket manual ───────────────────
    public function nuevoManual(Request $request)
    {
        $project    = \App\Models\Project::findOrFail(session('comercial_project_id'));
        $botProject = \App\Models\BotInstance::where('bot_type', 'rifa')->value('project_id');
        $projectId  = $botProject ?? $project->id;

        $data = $request->validate([
            'dni'       => 'required|string|max:20',
            'nombres'   => 'required|string|max:100',
            'apellidos' => 'nullable|string|max:100',
            'telefono'  => 'nullable|string|max:20',
            'correo'    => 'nullable|email|max:150',
            'ciudad'    => 'nullable|string|max:100',
            'pventa'    => 'nullable|string|max:150',
            'codigo'    => 'required|string|max:50',
            'plan'      => 'nullable|string|max:100',
            'monto'     => 'nullable|numeric|min:0',
        ]);

        $nombre = trim(($data['nombres'] ?? '') . ' ' . ($data['apellidos'] ?? ''));

        $venta = RifaVenta::create([
            'project_id'   => $projectId,
            'order_number' => RifaVenta::generateOrderNumber(),
            'wa_number'    => preg_replace('/\D/', '', $data['telefono'] ?? ''),
            'plan'         => 'manual',
            'plan_nombre'  => $data['plan'] ?? 'Manual',
            'tickets'      => 1,
            'monto'        => $data['monto'] ?? 0,
            'nombre'       => $nombre,
            'dni'          => $data['dni'],
            'ciudad'       => $data['ciudad'] ?? '',
            'telefono'     => $data['telefono'] ?? '',
            'email'        => $data['correo'] ?? '',
            'punto_venta'  => $data['pventa'] ?? '',
            'ticket_code'  => $data['codigo'],
            'status'       => 'enviado',
        ]);

        // Sincronizar con WordPress
        $partes    = explode(' ', trim($nombre), 2);
        $wpNombres = $partes[0] ?? '';
        $wpApellidos = $partes[1] ?? '';
        $payload = escapeshellarg(json_encode([
            'key'         => 'bixo-tickets-2024',
            'codigo'      => $data['codigo'],
            'dni'         => $data['dni'],
            'nombres'     => $wpNombres,
            'apellidos'   => $wpApellidos,
            'correo'      => $data['correo'] ?? '',
            'telefono'    => preg_replace('/\D/', '', $data['telefono'] ?? ''),
            'ciudad'      => $data['ciudad'] ?? '',
            'punto_venta' => $data['pventa'] ?? '',
            'origen'      => 'bixo',
            'estado'      => 'activo',
        ]));
        exec("curl -s -m 5 -X POST -H 'Content-Type: application/json' -d {$payload} https://pruebatusuerte.com.pe/wp-json/bixo/v1/registrar > /dev/null 2>&1 &");

        return response()->json(['ok' => true, 'id' => $venta->id]);
    }

    // ── Portal Comercial — eliminar pedido ──────────────────────
    public function eliminarComercial(RifaVenta $venta)
    {
        $project = \App\Models\Project::findOrFail(session('comercial_project_id'));
        $botProject = \App\Models\BotInstance::where('bot_type', 'rifa')->value('project_id');
        $allowed = array_filter([$project->id, $botProject]);
        abort_unless(in_array($venta->project_id, $allowed), 403);

        // ── Eliminar en WordPress (fire & forget via curl) ──
        $codigos = [];
        if ($venta->ticket_code) $codigos[] = $venta->ticket_code;
        if (!empty($venta->ticket_numbers)) {
            $codigos = array_merge($codigos, (array) $venta->ticket_numbers);
        }
        foreach (array_unique($codigos) as $cod) {
            $payload = escapeshellarg(json_encode(['key' => 'bixo-tickets-2024', 'codigo' => $cod]));
            exec("curl -s -m 5 -X POST -H 'Content-Type: application/json' -d {$payload} https://pruebatusuerte.com.pe/wp-json/bixo/v1/eliminar > /dev/null 2>&1 &");
        }

        $venta->delete();
        return response()->json(['ok' => true]);
    }

    // ── Portal Comercial — editar datos del participante ────────
    public function editarComercial(Request $request, RifaVenta $venta)
    {
        $project = \App\Models\Project::findOrFail(session('comercial_project_id'));

        $botProject = \App\Models\BotInstance::where('bot_type', 'rifa')->value('project_id');
        $allowed = [$project->id];
        if ($botProject) $allowed[] = $botProject;
        abort_unless(in_array($venta->project_id, $allowed), 403);

        $data = $request->validate([
            'nombre'       => 'nullable|string|max:100',
            'dni'          => 'nullable|string|max:20',
            'ciudad'       => 'nullable|string|max:100',
            'telefono'     => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:150',
            'ticket_code'  => 'nullable|string|max:50',
            'punto_venta'  => 'nullable|string|max:150',
            'plan_nombre'  => 'nullable|string|max:100',
            'tickets'      => 'nullable|integer|min:1',
            'monto'        => 'nullable|numeric|min:0',
        ]);

        $venta->update(array_filter($data, fn($v) => $v !== null));

        // ── Sincronizar edición en WordPress (fire & forget via curl) ──
        $venta->refresh();
        $codigos = [];
        if ($venta->ticket_code) $codigos[] = $venta->ticket_code;
        if (!empty($venta->ticket_numbers)) {
            $codigos = array_merge($codigos, (array) $venta->ticket_numbers);
        }
        $partes      = explode(' ', trim($venta->nombre ?? ''), 2);
        $wpNombres   = $partes[0] ?? '';
        $wpApellidos = $partes[1] ?? '';
        foreach (array_unique($codigos) as $cod) {
            $payload = escapeshellarg(json_encode([
                'key'         => 'bixo-tickets-2024',
                'codigo'      => $cod,
                'dni'         => $venta->dni         ?? '',
                'nombres'     => $wpNombres,
                'apellidos'   => $wpApellidos,
                'correo'      => $venta->email       ?? '',
                'telefono'    => preg_replace('/\D/', '', $venta->wa_number ?? ''),
                'ciudad'      => $venta->ciudad      ?? '',
                'punto_venta' => $venta->punto_venta ?? '',
                'estado'      => 'activo',
            ]));
            exec("curl -s -m 5 -X POST -H 'Content-Type: application/json' -d {$payload} https://pruebatusuerte.com.pe/wp-json/bixo/v1/editar > /dev/null 2>&1 &");
        }

        return response()->json(['ok' => true]);
    }

    // ── Portal Comercial — enviar recordatorio de pago ──────────
    public function recordar(RifaVenta $venta)
    {
        $project    = \App\Models\Project::findOrFail(session('comercial_project_id'));
        $botProject = \App\Models\BotInstance::where('bot_type', 'rifa')->value('project_id');
        $allowed    = array_filter([$project->id, $botProject]);

        if (!in_array($venta->project_id, $allowed))   return response()->json(['ok'=>false,'message'=>'Sin permiso'], 403);
        if ($venta->status !== 'pendiente')             return response()->json(['ok'=>false,'message'=>'Solo se puede recordar pedidos sin pago'], 422);
        if (empty($venta->nombre))                      return response()->json(['ok'=>false,'message'=>'El cliente no tiene nombre registrado'], 422);

        $tz  = 'America/Lima';
        $now = \Carbon\Carbon::now($tz);

        if ($now->hour < 8 || $now->hour >= 21)
            return response()->json(['ok'=>false,'message'=>'Solo se pueden enviar recordatorios entre 8am y 9pm'], 422);

        $total = \DB::table('rifa_recordatorios')->where('rifa_venta_id', $venta->id)->count();
        if ($total >= 2)
            return response()->json(['ok'=>false,'message'=>'Límite alcanzado: ya se enviaron 2 recordatorios a este cliente'], 422);

        $reciente = \DB::table('rifa_recordatorios')
            ->where('rifa_venta_id', $venta->id)
            ->where('enviado_at', '>=', $now->copy()->subHours(2))
            ->exists();
        if ($reciente)
            return response()->json(['ok'=>false,'message'=>'Ya se envió un recordatorio hace menos de 2 horas, espera un momento'], 422);

        // Elegir versión aleatoria (rotar entre las existentes)
        $usadas   = \DB::table('rifa_recordatorios')->where('rifa_venta_id', $venta->id)->pluck('version')->toArray();
        $versiones = [1, 2, 3, 4, 5];
        $disponibles = array_values(array_diff($versiones, $usadas));
        $version  = $disponibles[array_rand($disponibles)];

        $nombre = ucfirst(strtolower(explode(' ', trim($venta->nombre))[0]));
        $plan   = $venta->plan_nombre ?? 'nuestro plan';
        $monto  = 'S/ ' . number_format($venta->monto, 2);

        $mensajes = [
            1 => "Hola {$nombre} 👋 te escribimos de *Prueba tu Suerte* 🍀\nVimos que aún no confirmaste tu pago del plan *{$plan}* ({$monto}).\nCuando puedas mándanos tu voucher por aquí y listo 📸",
            2 => "{$nombre}, tu cupo en *{$plan}* sigue reservado 🎟️\nSolo falta confirmar tu pago de {$monto} para asegurarlo.\n¿Necesitas el número de cuenta? Aquí te ayudamos 😊",
            3 => "Hola {$nombre}! Aún tienes un pago pendiente con nosotros 💳\n*{$plan}* — {$monto}\nEnvíanos tu comprobante cuando lo tengas 🙌",
            4 => "👋 {$nombre}, por aquí *Prueba tu Suerte*.\nTu participación en *{$plan}* está pendiente de pago ({$monto}).\nSi ya pagaste mándanos la foto del voucher, si no ¿te ayudamos? 😊",
            5 => "{$nombre} 🍀 tu lugar en *{$plan}* está por liberarse.\nConfirma tu pago de {$monto} hoy para no perder el cupo.\nAquí recibimos tu comprobante 📲",
        ];

        $mensaje = $mensajes[$version];

        // Enviar via bot
        $bot  = \App\Models\BotInstance::where('bot_type', 'rifa')->first();
        $port = $bot?->port ?? 3002;
        $waNum = preg_replace('/\D/', '', $venta->wa_number);

        $res = \Illuminate\Support\Facades\Http::timeout(8)->post("http://127.0.0.1:{$port}/action", [
            'token'     => 'wa-bot-secret-2024',
            'action'    => 'send_message',
            'wa_number' => $waNum,
            'message'   => $mensaje,
        ]);

        abort_unless($res->successful(), 500, 'Error al enviar mensaje por el bot');

        \DB::table('rifa_recordatorios')->insert([
            'rifa_venta_id' => $venta->id,
            'wa_number'     => $venta->wa_number,
            'version'       => $version,
            'enviado_at'    => $now,
        ]);

        return response()->json(['ok' => true, 'version' => $version, 'enviados' => $total + 1]);
    }
}
