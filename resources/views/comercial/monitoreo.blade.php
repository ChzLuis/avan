<x-portal-layout layout="comercial" :project="$project" pageTitle="Monitoreo Bot">

@php
    // Colores de estado del bot
    $statusMap = [
        'connected' => ['🟢', 'Conectado',   '#16A34A', '#DCFCE7'],
        'qr'        => ['🟡', 'Esperando QR', '#B45309', '#FEF3C7'],
        'offline'   => ['🔴', 'Desconectado', '#DC2626', '#FEE2E2'],
    ];
    $st = $statusMap[$botStatus] ?? ['⚪', ucfirst($botStatus), '#6B7280', '#F3F4F6'];

    // Máximo del embudo para calcular anchos de barra
    $maxEmbudo = collect($embudo)->max('n') ?: 1;
    // Máximo de tráfico por día
    $maxTrafico = $traficoDias->max('usuarios') ?: 1;
@endphp

<div style="padding:20px 24px;background:#F8F9FB;min-height:100%;font-family:'Inter',sans-serif;">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
        <div>
            <h1 style="font-size:22px;font-weight:800;color:#111827;margin:0;">📊 Monitoreo del Bot</h1>
            <p style="font-size:13px;color:#6B7280;margin:4px 0 0;">Prueba Tu Suerte · Tráfico y rendimiento en tiempo real</p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:{{ $st[2] }};background:{{ $st[3] }};padding:7px 14px;border-radius:99px;">
                {{ $st[0] }} {{ $st[1] }}
            </span>
            <button onclick="location.reload()" style="font-size:13px;background:#2563EB;color:#fff;padding:7px 14px;border-radius:8px;border:none;cursor:pointer;font-weight:600;">↻ Actualizar</button>
        </div>
    </div>

    {{-- Tarjetas de números clave --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:22px;">
        @php
        $cards = [
            ['👥', 'Usuarios únicos', number_format($usuariosUnicos), '#6D28D9', '#F5F3FF'],
            ['🛒', 'Pedidos totales', number_format($totalPedidos), '#2563EB', '#EFF6FF'],
            ['📈', 'Conversión', $conversion.'%', '#16A34A', '#DCFCE7'],
            ['🎟️', 'Tickets vendidos', number_format($ticketsVendidos), '#B45309', '#FEF3C7'],
            ['💰', 'Ventas cobradas', 'S/ '.number_format($ventasCobradas, 2), '#0F766E', '#CCFBF1'],
        ];
        @endphp
        @foreach($cards as $c)
        <div style="background:#fff;border-radius:14px;padding:16px 18px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #EEF0F4;">
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;background:{{ $c[4] }};border-radius:10px;font-size:18px;">{{ $c[0] }}</span>
                <div>
                    <p style="font-size:11px;color:#9CA3AF;margin:0;text-transform:uppercase;letter-spacing:.04em;font-weight:600;">{{ $c[1] }}</p>
                    <p style="font-size:22px;font-weight:800;color:{{ $c[3] }};margin:2px 0 0;">{{ $c[2] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">

        {{-- Embudo de conversión --}}
        <div style="background:#fff;border-radius:14px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #EEF0F4;">
            <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0 0 4px;">🎯 Embudo de conversión</h3>
            <p style="font-size:12px;color:#9CA3AF;margin:0 0 16px;">Dónde están los usuarios en el flujo</p>
            @foreach($embudo as $key => $e)
            <div style="margin-bottom:12px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                    <span style="color:#374151;font-weight:500;">{{ $e['label'] }}</span>
                    <span style="color:#111827;font-weight:700;">{{ number_format($e['n']) }}</span>
                </div>
                <div style="background:#F3F4F6;border-radius:6px;height:10px;overflow:hidden;">
                    <div style="background:linear-gradient(90deg,#7C3AED,#A78BFA);height:100%;width:{{ $maxEmbudo > 0 ? round(($e['n']/$maxEmbudo)*100) : 0 }}%;border-radius:6px;transition:width .3s;"></div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Ventas por estado --}}
        <div style="background:#fff;border-radius:14px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #EEF0F4;">
            <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0 0 4px;">💵 Pedidos por estado</h3>
            <p style="font-size:12px;color:#9CA3AF;margin:0 0 16px;">Distribución de las ventas</p>
            @php
            $estadoLabels = [
                'pendiente'   => ['⏳ Sin pago',       '#9CA3AF', '#F3F4F6'],
                'comprobante' => ['📸 Por validar',    '#B45309', '#FEF3C7'],
                'pagado'      => ['✅ Pagado',          '#16A34A', '#DCFCE7'],
                'enviado'     => ['🎫 Completado',      '#2563EB', '#EFF6FF'],
                'cancelado'   => ['❌ Cancelado',       '#DC2626', '#FEE2E2'],
            ];
            @endphp
            @foreach($estadoLabels as $sKey => $sInfo)
            @php $v = $ventas[$sKey] ?? null; @endphp
            <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 12px;border-radius:9px;background:{{ $sInfo[2] }};margin-bottom:8px;">
                <span style="font-size:13px;font-weight:600;color:{{ $sInfo[1] }};">{{ $sInfo[0] }}</span>
                <span style="font-size:13px;color:#374151;">
                    <strong>{{ $v->n ?? 0 }}</strong> pedidos
                    @if($v && $v->total > 0)<span style="color:#9CA3AF;">· S/ {{ number_format($v->total, 2) }}</span>@endif
                </span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Tráfico por día --}}
    <div style="background:#fff;border-radius:14px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #EEF0F4;margin-bottom:16px;">
        <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0 0 4px;">📅 Tráfico por día (últimos 14 días)</h3>
        <p style="font-size:12px;color:#9CA3AF;margin:0 0 18px;">Usuarios únicos que escribieron al bot</p>
        @if($traficoDias->isEmpty())
            <p style="text-align:center;color:#9CA3AF;font-size:13px;padding:20px;">Sin datos de tráfico aún.</p>
        @else
        <div style="display:flex;align-items:flex-end;gap:6px;height:160px;padding-top:10px;">
            @foreach($traficoDias as $d)
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;height:100%;justify-content:flex-end;">
                <span style="font-size:11px;font-weight:700;color:#6D28D9;margin-bottom:4px;">{{ $d->usuarios }}</span>
                <div style="width:100%;max-width:32px;background:linear-gradient(180deg,#7C3AED,#C4B5FD);border-radius:6px 6px 0 0;height:{{ max(6, round(($d->usuarios/$maxTrafico)*130)) }}px;transition:height .3s;"></div>
                <span style="font-size:10px;color:#9CA3AF;margin-top:6px;white-space:nowrap;">{{ \Carbon\Carbon::parse($d->dia)->format('d/m') }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Últimas conversaciones --}}
    <div style="background:#fff;border-radius:14px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #EEF0F4;">
        <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0 0 16px;">💬 Últimas conversaciones</h3>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="text-align:left;color:#9CA3AF;font-size:11px;text-transform:uppercase;letter-spacing:.04em;">
                        <th style="padding:8px 10px;border-bottom:1px solid #EEF0F4;">Número</th>
                        <th style="padding:8px 10px;border-bottom:1px solid #EEF0F4;">Estado actual</th>
                        <th style="padding:8px 10px;border-bottom:1px solid #EEF0F4;">Última actividad</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $estadoNombre = [
                        'inicio' => '👋 Inicio', 'menu_principal' => '📋 Menú', 'enviar_qr' => '💳 Pago QR',
                        'comprobante_recibido' => '📸 Comprobante', 'confirmacion_final' => '✅ Registrado',
                        'pedir_nombre' => '✍️ Datos', 'pedir_dni' => '✍️ Datos', 'pedir_email' => '✍️ Datos',
                        'pedir_telefono' => '✍️ Datos', 'pedir_direccion' => '✍️ Datos', 'confirmar_ticket' => '🎟️ Confirmando',
                    ];
                    @endphp
                    @forelse($ultimasSesiones as $s)
                    <tr>
                        <td style="padding:9px 10px;border-bottom:1px solid #F5F6F8;font-family:monospace;color:#374151;">{{ $s->wa_number }}</td>
                        <td style="padding:9px 10px;border-bottom:1px solid #F5F6F8;color:#111827;">{{ $estadoNombre[$s->current_state] ?? $s->current_state }}</td>
                        <td style="padding:9px 10px;border-bottom:1px solid #F5F6F8;color:#9CA3AF;">{{ \Carbon\Carbon::parse($s->updated_at)->timezone('America/Lima')->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" style="padding:20px;text-align:center;color:#9CA3AF;">Sin conversaciones aún.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
</x-portal-layout>
