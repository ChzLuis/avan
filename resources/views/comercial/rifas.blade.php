<x-portal-layout layout="comercial" :project="$project" pageTitle="Pedidos Bot">
@php
$recsCounts = \DB::table('rifa_recordatorios')
    ->whereIn('rifa_venta_id', $ventas->pluck('id'))
    ->select('rifa_venta_id', \DB::raw('COUNT(*) as total'))
    ->groupBy('rifa_venta_id')
    ->pluck('total', 'rifa_venta_id');

$cnts = [
    'todos'        => $ventas->count(),
    'comprobante'  => $ventas->where('status','comprobante')->count(),
    'pendiente'    => $ventas->where('status','pendiente')->count(),
    'pagado'       => $ventas->where('status','pagado')->count(),
    'enviado'      => $ventas->where('status','enviado')->count(),
    'cancelado'    => $ventas->where('status','cancelado')->count(),
];
$montoCobrado      = $ventas->whereIn('status',['pagado','enviado'])->sum('monto');
$montoComprobante  = $ventas->where('status','comprobante')->sum('monto');
$montoPotencial    = $ventas->where('status','pendiente')->sum('monto');

$stLabels = [
    'pendiente'   => 'Sin pago',
    'comprobante' => 'Por validar',
    'pagado'      => 'Pago confirmado',
    'enviado'     => 'Completado',
    'cancelado'   => 'Cancelado',
];
$stStyle = [
    'pendiente'   => 'background:#F3F4F6;color:#6B7280;border-color:#E5E7EB;',
    'comprobante' => 'background:#FEF3C7;color:#B45309;border-color:#FDE68A;',
    'pagado'      => 'background:#DBEAFE;color:#1D4ED8;border-color:#BFDBFE;',
    'enviado'     => 'background:#DCFCE7;color:#15803D;border-color:#BBF7D0;',
    'cancelado'   => 'background:#FEE2E2;color:#DC2626;border-color:#FECACA;',
];
@endphp

<div style="display:flex;height:100%;overflow:hidden;">
<div style="display:flex;flex-direction:column;flex:1;min-width:0;overflow:hidden;">

{{-- ── Header ── --}}
<div style="padding:12px 20px;border-bottom:1px solid #E5E8EF;background:#fff;flex-shrink:0;">

    {{-- Fila 1: título + KPIs de dinero --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
        <div>
            <h1 style="font-size:15px;font-weight:700;color:#111827;margin:0;">Pedidos Bot</h1>
            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">{{ $project->name }}</p>
        </div>
        <div style="display:flex;align-items:center;gap:16px;">
            <div style="text-align:right;">
                <p style="font-size:10px;color:#9CA3AF;margin:0;text-transform:uppercase;letter-spacing:.04em;">Cobrado</p>
                <p style="font-size:16px;font-weight:800;color:#15803D;margin:0;">S/ {{ number_format($montoCobrado,2) }}</p>
            </div>
            <div style="width:1px;height:32px;background:#E5E8EF;"></div>
            <div style="text-align:right;">
                <p style="font-size:10px;color:#B45309;margin:0;text-transform:uppercase;letter-spacing:.04em;">Por confirmar</p>
                <p style="font-size:16px;font-weight:800;color:#D97706;margin:0;">S/ {{ number_format($montoComprobante,2) }}</p>
            </div>
            <div style="width:1px;height:32px;background:#E5E8EF;"></div>
            <div style="text-align:right;">
                <p style="font-size:10px;color:#9CA3AF;margin:0;text-transform:uppercase;letter-spacing:.04em;">Potencial</p>
                <p style="font-size:14px;font-weight:700;color:#9CA3AF;margin:0;">S/ {{ number_format($montoPotencial,2) }}</p>
            </div>
            <button onclick="abrirNuevoTicket()"
                    style="font-size:12px;font-weight:600;background:#7C3AED;color:#fff;padding:6px 14px;border-radius:8px;border:none;cursor:pointer;display:flex;align-items:center;gap:5px;">
                ➕ Nuevo ticket
            </button>
            <button onclick="location.reload()"
                    style="background:none;border:none;cursor:pointer;color:#9CA3AF;font-size:16px;line-height:1;padding:4px;"
                    title="Recargar"
                    onmouseover="this.style.color='#6B7280'" onmouseout="this.style.color='#9CA3AF'">↻</button>
        </div>
    </div>

    {{-- Fila 2: Tabs por estado --}}
    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:10px;" id="tabs-estado">
        @php
        $tabs = [
            ['key'=>'todos',       'label'=>'Todos',          'color'=>'#6B7280', 'bg'=>'#F3F4F6'],
            ['key'=>'comprobante', 'label'=>'Por validar',    'color'=>'#B45309', 'bg'=>'#FEF3C7'],
            ['key'=>'pendiente',   'label'=>'Sin pago',       'color'=>'#9CA3AF', 'bg'=>'#F9FAFB'],
            ['key'=>'pagado',      'label'=>'Pago confirmado','color'=>'#1D4ED8', 'bg'=>'#EFF6FF'],
            ['key'=>'enviado',     'label'=>'Completado',     'color'=>'#15803D', 'bg'=>'#F0FDF4'],
            ['key'=>'cancelado',   'label'=>'Cancelado',      'color'=>'#DC2626', 'bg'=>'#FFF1F2'],
        ];
        @endphp
        @foreach($tabs as $tab)
        <button onclick="filtrarTab('{{ $tab['key'] }}')" id="tab-{{ $tab['key'] }}"
                style="font-size:11px;font-weight:600;padding:5px 10px;border-radius:99px;border:1px solid #E5E8EF;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all .12s;background:#fff;color:#6B7280;"
                data-tab="{{ $tab['key'] }}">
            {{ $tab['label'] }}
            <span style="font-size:10px;font-weight:800;padding:1px 6px;border-radius:99px;background:{{ $tab['bg'] }};color:{{ $tab['color'] }};">
                {{ $cnts[$tab['key']] }}
            </span>
            @if($tab['key']==='comprobante' && $cnts['comprobante'] > 0)
            <span style="width:7px;height:7px;border-radius:50%;background:#EF4444;animation:pulse-dot 1.4s ease-in-out infinite;display:inline-block;"></span>
            @endif
        </button>
        @endforeach
    </div>

    {{-- Fila 3: Filtros fecha + búsqueda --}}
    @php
        $tz      = 'America/Lima';
        $fDesde  = request('desde', now($tz)->subDays(30)->format('Y-m-d'));
        $fHasta  = request('hasta', now($tz)->format('Y-m-d'));
        $fBuscar = request('buscar', '');
    @endphp
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <input type="date" id="f-desde" value="{{ $fDesde }}"
               style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:5px 10px;outline:none;font-family:inherit;color:#374151;">
        <span style="color:#9CA3AF;font-size:12px;">→</span>
        <input type="date" id="f-hasta" value="{{ $fHasta }}"
               style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:5px 10px;outline:none;font-family:inherit;color:#374151;">
        <input type="text" id="f-buscar" placeholder="Nombre, DNI o celular..." value="{{ $fBuscar }}"
               style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:5px 12px;outline:none;width:210px;font-family:inherit;color:#374151;">
        <button onclick="buscarServidor()"
                style="font-size:12px;background:#2563EB;color:#fff;padding:5px 14px;border-radius:8px;border:none;cursor:pointer;font-weight:600;">
            Buscar
        </button>
        <button onclick="exportarExcel()"
                style="font-size:12px;background:#16A34A;color:#fff;padding:5px 14px;border-radius:8px;border:none;cursor:pointer;font-weight:600;"
                title="Descargar pedidos completados en Excel">
            📊 Exportar Excel
        </button>
        <a href="{{ route('bixosales.rifas.monitoreo') }}"
                style="font-size:12px;background:#7C3AED;color:#fff;padding:5px 14px;border-radius:8px;border:none;cursor:pointer;font-weight:600;text-decoration:none;"
                title="Panel de monitoreo del bot">
            📈 Monitoreo
        </a>
        @if(request('desde') || request('hasta') || request('buscar'))
        <a href="{{ request()->url() }}" style="font-size:11px;color:#9CA3AF;text-decoration:none;" title="Limpiar filtros">✕ Limpiar</a>
        @endif
    </div>
</div>

{{-- ── Lista ── --}}
<div style="flex:1;overflow-y:auto;padding:12px;background:#F8F9FB;" id="lista-ventas">
    @if($ventas->isEmpty())
    <div style="text-align:center;padding:60px 20px;color:#9CA3AF;">
        <div style="font-size:40px;margin-bottom:10px;">🎟️</div>
        <p style="font-size:13px;">Aún no hay ventas registradas</p>
    </div>
    @else
    <div style="display:flex;flex-direction:column;gap:4px;" id="cards-container">
    @foreach($ventas as $v)
    @php
    $sty = $stStyle[$v->status] ?? 'background:#F3F4F6;color:#6B7280;border-color:#E5E7EB;';
    $lbl = $stLabels[$v->status] ?? $v->status;
    $esComprobante = $v->status === 'comprobante';
    $esPagado      = $v->status === 'pagado';
    $cardBorder    = $esComprobante ? 'border:1.5px solid #FDE68A;' : 'border:1px solid #E5E8EF;';
    $cardBg        = $esComprobante ? 'background:#FFFBEB;' : 'background:#fff;';
    @endphp

    <div style="{{ $cardBg }}{{ $cardBorder }}border-radius:10px;padding:7px 12px;display:flex;align-items:center;gap:8px;cursor:pointer;transition:border-color .12s,box-shadow .12s;position:relative;"
         onclick="abrirDetalle({{ $v->id }})" id="rv-{{ $v->id }}"
         data-status="{{ $v->status }}"
         data-nombre="{{ strtolower($v->nombre ?? '') }}"
         data-dni="{{ $v->dni ?? '' }}"
         data-wa="{{ $v->wa_number ?? '' }}"
         data-fecha="{{ $v->created_at?->timezone('America/Lima')->format('Y-m-d') ?? '' }}"
         onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,.07)';this.style.borderColor='{{ $esComprobante ? '#F59E0B' : '#2563EB' }}';"
         onmouseout="this.style.boxShadow='none';this.style.borderColor='{{ $esComprobante ? '#FDE68A' : '#E5E8EF' }}';">

        {{-- Dot --}}
        <div style="width:7px;height:7px;border-radius:50%;flex-shrink:0;
             background:{{ $v->status==='comprobante' ? '#F59E0B' : ($v->status==='enviado' ? '#22C55E' : ($v->status==='pagado' ? '#3B82F6' : ($v->status==='cancelado' ? '#EF4444' : '#D1D5DB'))) }};
             {{ $esComprobante ? 'animation:pulse-dot 1.4s ease-in-out infinite;' : '' }}"></div>

        {{-- Info --}}
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:baseline;gap:6px;flex-wrap:wrap;">
                <span style="font-size:12px;font-weight:700;color:#111827;">{{ $v->nombre ?? '—' }}</span>
                @if($v->dni)<span style="font-size:11px;color:#9CA3AF;">· DNI {{ $v->dni }}</span>@endif
                <span style="font-size:11px;color:#9CA3AF;">· {{ $v->wa_number }}</span>
            </div>
            <div style="display:flex;align-items:center;gap:6px;margin-top:1px;flex-wrap:wrap;">
                <span style="font-size:11px;color:#7C3AED;font-weight:600;">{{ $v->plan_nombre }}</span>
                <span style="font-size:11px;color:#6B7280;">{{ $v->tickets }}t</span>
                <span style="font-size:11px;font-weight:700;color:#111827;">S/ {{ number_format($v->monto, 2) }}</span>
                <span style="font-size:10px;color:#9CA3AF;">{{ $v->created_at?->timezone('America/Lima')->format('d/m H:i') }}</span>
                @if($v->status === 'enviado' && $v->ticket_code)
                <span style="font-size:10px;background:#DCFCE7;color:#15803D;padding:1px 6px;border-radius:99px;font-family:monospace;">🎫 {{ $v->ticket_code }}</span>
                @endif
            </div>
        </div>

        {{-- Derecha: badge + flecha --}}
        <div style="display:flex;align-items:center;gap:5px;flex-shrink:0;">
            @if($v->payment_proof && !$esComprobante)
            <span style="font-size:11px;color:#6366F1;" title="Tiene comprobante">🧾</span>
            @endif
            <span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:99px;border:1px solid;white-space:nowrap;{{ $sty }}">{{ $lbl }}</span>
            <span style="color:#D1D5DB;font-size:13px;line-height:1;">›</span>
        </div>

        {{-- Botón recordatorio: esquina superior derecha --}}
        @if($v->status === 'pendiente' && $v->nombre)
        @php $recs = $recsCounts[$v->id] ?? 0; @endphp
        <button id="btn-recordar-{{ $v->id }}"
                onclick="event.stopPropagation();rifaRecordar({{ $v->id }})"
                {{ $recs >= 2 ? 'disabled' : '' }}
                title="{{ $recs >= 2 ? 'Límite alcanzado (2/2)' : 'Enviar recordatorio WhatsApp' }}"
                style="position:absolute;top:6px;right:10px;font-size:11px;font-weight:600;padding:3px 10px;border-radius:6px;border:1px solid {{ $recs >= 2 ? '#E5E8EF' : '#BFDBFE' }};background:{{ $recs >= 2 ? '#F3F4F6' : '#EFF6FF' }};color:{{ $recs >= 2 ? '#9CA3AF' : '#2563EB' }};cursor:{{ $recs >= 2 ? 'not-allowed' : 'pointer' }};white-space:nowrap;">
            📩 Recordatorio{{ $recs > 0 ? ' ('.$recs.'/2)' : '' }}
        </button>
        @endif
    </div>
    @endforeach
    </div>
    <p id="lista-vacia" style="display:none;text-align:center;padding:40px;color:#9CA3AF;font-size:13px;">Sin resultados para este filtro</p>
    @endif
</div>

</div>

{{-- ── Panel Bot WA ── --}}
<div id="bot-panel" style="position:fixed;top:52px;right:0;bottom:0;z-index:40;transition:width .2s ease;width:220px;border-left:1px solid #E5E8EF;background:#fff;display:flex;">
    <button id="bot-panel-toggle" onclick="toggleBotPanel()"
            style="width:22px;background:#F8F9FB;border-right:1px solid #E5E8EF;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;border:none;">
        <span style="writing-mode:vertical-lr;font-size:10px;font-weight:700;text-transform:uppercase;color:#6B7280;transform:rotate(180deg);letter-spacing:.06em;">BOT</span>
        <svg id="bot-chevron" style="width:12px;height:12px;transform:rotate(180deg);transition:transform .2s;margin-top:6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </button>
    <div id="bot-panel-content" style="flex:1;display:flex;flex-direction:column;overflow:hidden;padding:12px;gap:10px;">
        <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0;">WhatsApp Bot</p>
        <div style="display:flex;align-items:center;gap:7px;">
            <span id="bot-dot" style="width:9px;height:9px;border-radius:50%;background:#D1D5DB;flex-shrink:0;display:inline-block;"></span>
            <span id="bot-status-text" style="font-size:11px;font-weight:600;color:#6B7280;">Verificando...</span>
        </div>
        <div id="bot-qr-wrap" style="display:flex;flex-direction:column;align-items:center;gap:8px;">
            <div id="bot-qr-spinner" style="display:flex;flex-direction:column;align-items:center;gap:5px;padding:8px 0;">
                <div style="width:22px;height:22px;border:2px solid #E5E8EF;border-top-color:#2563EB;border-radius:50%;animation:spin .8s linear infinite;"></div>
                <p style="font-size:11px;color:#9CA3AF;margin:0;">Cargando...</p>
            </div>
            <img id="bot-qr-img" src="" style="width:160px;height:160px;border-radius:12px;border:1px solid #E5E8EF;display:none;" alt="QR">
            <p id="bot-qr-hint" style="font-size:10px;color:#9CA3AF;text-align:center;line-height:1.4;display:none;">WhatsApp → Dispositivos vinculados → Vincular dispositivo</p>
            <p id="bot-connected-msg" style="font-size:11px;color:#16A34A;font-weight:600;text-align:center;display:none;">✓ WhatsApp conectado</p>
            <p id="bot-offline-msg" style="font-size:11px;color:#9CA3AF;text-align:center;display:none;">Bot no iniciado</p>
        </div>
    </div>
</div>

</div>

{{-- ── Modal Detalle ── --}}
<div id="modal-detalle" style="position:fixed;left:0;right:0;top:52px;bottom:0;z-index:50;display:none;align-items:flex-start;justify-content:center;background:rgba(0,0,0,.45);padding:16px;overflow-y:auto;">
    <div style="width:100%;max-width:640px;background:#fff;border-radius:16px;box-shadow:0 24px 64px rgba(0,0,0,.18);margin:0 auto;display:flex;flex-direction:column;max-height:calc(100vh - 84px);">
        <div style="padding:14px 20px;border-bottom:1px solid #E5E8EF;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;background:#fff;z-index:2;">
            <div>
                <span style="font-size:15px;font-weight:700;color:#111827;" id="md-titulo">Pedido</span>
                <span id="md-status-badge" style="font-size:11px;padding:2px 8px;border-radius:99px;margin-left:8px;border:1px solid;"></span>
            </div>
            <button onclick="cerrarDetalle()" style="background:none;border:none;cursor:pointer;color:#9CA3AF;font-size:18px;">✕</button>
        </div>
        {{-- Body: 2 columnas cuando hay comprobante --}}
        <div style="padding:16px 20px;display:flex;gap:16px;overflow-y:auto;flex:1;">

            {{-- Columna izquierda: datos --}}
            <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:10px;">

                <div id="md-vista">
                    <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 8px;">Datos del participante</p>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
                        <div style="background:#F8F9FB;border-radius:8px;padding:8px 10px;">
                            <p style="font-size:10px;color:#9CA3AF;margin:0 0 2px;">Nombre</p>
                            <p style="font-size:13px;font-weight:600;color:#111827;margin:0;user-select:all;" id="md-nombre">—</p>
                        </div>
                        <div style="background:#F8F9FB;border-radius:8px;padding:8px 10px;">
                            <p style="font-size:10px;color:#9CA3AF;margin:0 0 2px;">DNI</p>
                            <p style="font-size:13px;font-weight:600;color:#111827;margin:0;user-select:all;" id="md-dni">—</p>
                        </div>
                        <div style="background:#F8F9FB;border-radius:8px;padding:8px 10px;">
                            <p style="font-size:10px;color:#9CA3AF;margin:0 0 2px;">WhatsApp</p>
                            <p style="font-size:13px;font-weight:600;color:#111827;margin:0;user-select:all;" id="md-celular">—</p>
                        </div>
                        <div style="background:#F8F9FB;border-radius:8px;padding:8px 10px;">
                            <p style="font-size:10px;color:#9CA3AF;margin:0 0 2px;">Ciudad</p>
                            <p style="font-size:13px;font-weight:600;color:#111827;margin:0;user-select:all;" id="md-ciudad">—</p>
                        </div>
                        <div style="background:#F8F9FB;border-radius:8px;padding:8px 10px;" id="md-correo-row">
                            <p style="font-size:10px;color:#9CA3AF;margin:0 0 2px;">Correo</p>
                            <p style="font-size:12px;font-weight:600;color:#111827;margin:0;user-select:all;" id="md-correo">—</p>
                        </div>
                        <div style="background:#F8F9FB;border-radius:8px;padding:8px 10px;" id="md-pventa-row">
                            <p style="font-size:10px;color:#9CA3AF;margin:0 0 2px;">Punto de venta</p>
                            <p style="font-size:12px;font-weight:600;color:#111827;margin:0;user-select:all;" id="md-pventa">—</p>
                        </div>
                    </div>
                    <div style="background:#F5F3FF;border-radius:8px;padding:8px 10px;margin-top:6px;">
                        <p style="font-size:10px;color:#9CA3AF;margin:0 0 2px;">Plan</p>
                        <p style="font-size:13px;font-weight:600;color:#6D28D9;margin:0;" id="md-plan">—</p>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:6px;">
                        <div style="background:#F8F9FB;border-radius:8px;padding:8px 10px;">
                            <p style="font-size:10px;color:#9CA3AF;margin:0 0 2px;">Tickets</p>
                            <p style="font-size:13px;font-weight:600;color:#111827;margin:0;" id="md-tickets">—</p>
                        </div>
                        <div style="background:#F8F9FB;border-radius:8px;padding:8px 10px;">
                            <p style="font-size:10px;color:#9CA3AF;margin:0 0 2px;">Monto</p>
                            <p style="font-size:14px;font-weight:800;color:#111827;margin:0;" id="md-monto">—</p>
                        </div>
                    </div>
                    <div id="md-ticket-code-row" style="background:#DCFCE7;border-radius:8px;padding:8px 10px;margin-top:6px;display:none;">
                        <p style="font-size:10px;color:#9CA3AF;margin:0 0 2px;">N° de Membresía / Ticket</p>
                        <p style="font-size:14px;font-weight:800;color:#15803D;margin:0;font-family:monospace;user-select:all;" id="md-ticket-code">—</p>
                    </div>
                    <p style="font-size:10px;color:#9CA3AF;margin:6px 0 0;" id="md-fecha">—</p>
                </div>

                {{-- Form enviar tickets --}}
                <div id="md-validar-form" style="display:none;flex-direction:column;gap:8px;border-top:1px solid #E5E8EF;padding-top:12px;">
                    <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0;">N° de Tickets a enviar</p>
                    <div id="vf-tickets-campos" style="display:flex;flex-direction:column;gap:6px;"></div>
                    <p style="font-size:11px;color:#9CA3AF;margin:0;">Se enviarán al cliente por WhatsApp</p>
                    <div style="display:flex;gap:8px;">
                        <button onclick="rifaEnviarTickets()"
                                style="flex:1;font-size:13px;font-weight:600;background:#16A34A;color:#fff;padding:9px;border-radius:8px;border:none;cursor:pointer;">
                            🎟️ Enviar tickets
                        </button>
                        <button onclick="ocultarFormValidar()"
                                style="font-size:12px;color:#6B7280;background:none;border:none;cursor:pointer;padding:0 10px;">
                            Cancelar
                        </button>
                    </div>
                </div>

                {{-- Form editar --}}
                <div id="md-edit" style="display:none;flex-direction:column;gap:8px;">
                    <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0;">Editar datos</p>
                    <div>
                        <label style="display:block;font-size:11px;color:#6B7280;margin-bottom:3px;">Nombre completo</label>
                        <input type="text" id="ef-nombre"
                               style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box;">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
                        <div>
                            <label style="display:block;font-size:11px;color:#6B7280;margin-bottom:3px;">DNI</label>
                            <input type="text" id="ef-dni" maxlength="12"
                                   style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block;font-size:11px;color:#6B7280;margin-bottom:3px;">Teléfono</label>
                            <input type="text" id="ef-telefono" maxlength="15"
                                   style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block;font-size:11px;color:#6B7280;margin-bottom:3px;">Ciudad</label>
                            <input type="text" id="ef-ciudad"
                                   style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block;font-size:11px;color:#6B7280;margin-bottom:3px;">Correo</label>
                            <input type="email" id="ef-correo"
                                   style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box;">
                        </div>
                    </div>
                    {{-- Desplegable de plan según el monto pagado --}}
                    <div style="background:#F5F3FF;border-radius:8px;padding:10px;">
                        <label style="display:block;font-size:11px;font-weight:600;color:#6D28D9;margin-bottom:4px;">💰 Plan pagado (según comprobante)</label>
                        <select id="ef-plan" onchange="efPlanCambio()"
                                style="width:100%;border:1px solid #DDD6FE;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box;background:#fff;cursor:pointer;">
                            <option value="">— Selecciona el plan —</option>
                            <option value="Probar mi suerte|1|10.00">1 Ticket — S/ 10</option>
                            <option value="Duplica tu suerte|2|20.00">2 Tickets — S/ 20</option>
                            <option value="Quintuplica tu suerte|5|50.00">5 Tickets — S/ 50</option>
                            <option value="Asegura suertudazo|10|100.00">10 Tickets — S/ 100</option>
                            <option value="__custom__">✏️ Otro monto (personalizado)</option>
                        </select>
                        <div id="ef-custom-wrap" style="display:none;grid-template-columns:1fr 1fr;gap:6px;margin-top:6px;">
                            <div>
                                <label style="display:block;font-size:10px;color:#6B7280;margin-bottom:2px;">N° de tickets</label>
                                <input type="number" id="ef-tickets" min="1" placeholder="0"
                                       style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:7px 10px;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="display:block;font-size:10px;color:#6B7280;margin-bottom:2px;">Monto S/</label>
                                <input type="number" id="ef-monto" min="0" step="0.01" placeholder="0.00"
                                       style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:7px 10px;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box;">
                            </div>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
                        <div>
                            <label style="display:block;font-size:11px;color:#6B7280;margin-bottom:3px;">N° Ticket / Membresía</label>
                            <input type="text" id="ef-ticket-code" placeholder="TS-02180"
                                   style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block;font-size:11px;color:#6B7280;margin-bottom:3px;">Punto de venta</label>
                            <input type="text" id="ef-punto-venta" placeholder="Tienda, local..."
                                   style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box;">
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button onclick="guardarEdicion()"
                                style="flex:1;font-size:13px;font-weight:600;background:#2563EB;color:#fff;padding:9px;border-radius:8px;border:none;cursor:pointer;">
                            Guardar
                        </button>
                        <button onclick="cancelarEdicion()"
                                style="font-size:12px;color:#6B7280;background:none;border:none;cursor:pointer;padding:0 10px;">
                            Cancelar
                        </button>
                    </div>
                </div>

            </div>

            {{-- Columna derecha: comprobante --}}
            <div id="md-comprobante-wrap" style="display:none;width:220px;flex-shrink:0;">
                <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 8px;">Comprobante</p>
                <img id="md-comprobante-img" src="" alt="Comprobante"
                     style="width:100%;border-radius:10px;border:1px solid #E5E8EF;cursor:zoom-in;display:block;"
                     onclick="window.open(this.src,'_blank')">
                <p style="font-size:10px;color:#9CA3AF;margin:4px 0 0;text-align:center;">Click para ampliar</p>
            </div>

        </div>
        <div id="md-acciones" style="padding:12px 20px;border-top:1px solid #E5E8EF;display:flex;flex-wrap:wrap;gap:6px;"></div>
    </div>
</div>

{{-- Modal selector de plan (antes de validar) --}}
<div id="modal-selector-plan" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:10000;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:14px;max-width:380px;width:100%;padding:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <p style="font-size:15px;font-weight:700;color:#111827;margin:0 0 4px;">💰 ¿Cuántos tickets pagó?</p>
        <p style="font-size:12px;color:#6B7280;margin:0 0 14px;">Selecciona el plan según el monto del comprobante antes de confirmar la venta.</p>
        <select id="sp-plan" onchange="spPlanCambio()"
                style="width:100%;border:1px solid #DDD6FE;border-radius:8px;padding:10px 12px;font-size:14px;outline:none;font-family:inherit;box-sizing:border-box;background:#fff;cursor:pointer;margin-bottom:8px;">
            <option value="">— Selecciona el plan —</option>
            <option value="Probar mi suerte|1|10.00">1 Ticket — S/ 10</option>
            <option value="Duplica tu suerte|2|20.00">2 Tickets — S/ 20</option>
            <option value="Quintuplica tu suerte|5|50.00">5 Tickets — S/ 50</option>
            <option value="Asegura suertudazo|10|100.00">10 Tickets — S/ 100</option>
            <option value="__custom__">✏️ Otro monto (personalizado)</option>
        </select>
        <div id="sp-custom-wrap" style="display:none;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
            <div>
                <label style="display:block;font-size:11px;color:#6B7280;margin-bottom:3px;">N° de tickets</label>
                <input type="number" id="sp-tickets" min="1" placeholder="0"
                       style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:8px 10px;font-size:13px;outline:none;box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block;font-size:11px;color:#6B7280;margin-bottom:3px;">Monto S/</label>
                <input type="number" id="sp-monto" min="0" step="0.01" placeholder="0.00"
                       style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:8px 10px;font-size:13px;outline:none;box-sizing:border-box;">
            </div>
        </div>
        <div style="display:flex;gap:8px;margin-top:6px;">
            <button onclick="confirmarConPlan()"
                    style="flex:1;font-size:14px;font-weight:700;background:#16A34A;color:#fff;padding:11px;border-radius:8px;border:none;cursor:pointer;">
                ✓ Confirmar pago
            </button>
            <button onclick="cerrarSelectorPlan()"
                    style="font-size:13px;color:#6B7280;background:#F3F4F6;border:none;cursor:pointer;padding:0 16px;border-radius:8px;">
                Cancelar
            </button>
        </div>
    </div>
</div>

{{-- Toast --}}
<div id="toast" style="position:fixed;bottom:20px;right:20px;z-index:9999;display:none;">
    <div id="toast-inner" style="padding:10px 18px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.15);font-size:13px;font-weight:500;color:#fff;background:#374151;"></div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes pulse-dot {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:.5; transform:scale(1.4); }
}
</style>

<script>
const csrf   = '{{ csrf_token() }}';
const ventas = @json($ventasJson);
let activeId     = null;
let pendienteValidarId = null;
let tabActivo    = 'todos';

const stLabels = {
    pendiente:   'Sin pago',
    comprobante: 'Por validar',
    pagado:      'Pago confirmado',
    enviado:     'Completado',
    cancelado:   'Cancelado',
};
const stStyles = {
    pendiente:   'background:#F3F4F6;color:#6B7280;border-color:#E5E7EB;',
    comprobante: 'background:#FEF3C7;color:#B45309;border-color:#FDE68A;',
    pagado:      'background:#DBEAFE;color:#1D4ED8;border-color:#BFDBFE;',
    enviado:     'background:#DCFCE7;color:#15803D;border-color:#BBF7D0;',
    cancelado:   'background:#FEE2E2;color:#DC2626;border-color:#FECACA;',
};

// ── Tabs ──────────────────────────────────────────────────────────────────────
function filtrarTab(tab) {
    tabActivo = tab;
    document.querySelectorAll('[data-tab]').forEach(btn => {
        const active = btn.dataset.tab === tab;
        btn.style.background   = active ? '#1D4ED8' : '#fff';
        btn.style.color        = active ? '#fff'     : '#6B7280';
        btn.style.borderColor  = active ? '#1D4ED8'  : '#E5E8EF';
    });
    aplicarFiltros();
}

// ── Buscar en servidor (recarga con params) ───────────────────────────────────
function buscarServidor() {
    const desde  = document.getElementById('f-desde').value;
    const hasta  = document.getElementById('f-hasta').value;
    const buscar = document.getElementById('f-buscar').value.trim();
    const params = new URLSearchParams();
    if (desde)  params.set('desde',  desde);
    if (hasta)  params.set('hasta',  hasta);
    if (buscar) params.set('buscar', buscar);
    const estado = new URLSearchParams(window.location.search).get('estado');
    if (estado)  params.set('estado', estado);
    window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
}

// ── Exportar a Excel (CSV) — solo pedidos completados ─────────────────────────
function exportarExcel() {
    const desde  = document.getElementById('f-desde').value;
    const hasta  = document.getElementById('f-hasta').value;
    const params = new URLSearchParams();
    if (desde) params.set('desde', desde);
    if (hasta) params.set('hasta', hasta);
    params.set('estado', 'completados');
    window.location.href = '/bixosales/pedidos-bot/exportar?' + params.toString();
}

// ── Filtro client-side por tab (solo estado, sin tocar fechas) ────────────────
function aplicarFiltros() {
    let visible = 0;
    document.querySelectorAll('[id^="rv-"]').forEach(row => {
        const v = ventas[row.id.replace('rv-', '')];
        if (!v) { row.style.display = 'none'; return; }
        const show = tabActivo === 'todos' || v.status === tabActivo;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('lista-vacia').style.display = visible === 0 ? 'block' : 'none';
}

document.getElementById('f-buscar')?.addEventListener('keyup', e => { if (e.key === 'Enter') buscarServidor(); });

// ── Modal ─────────────────────────────────────────────────────────────────────
function abrirDetalle(id) {
    activeId = id;
    const v  = ventas[id];
    if (!v) return;

    document.getElementById('md-titulo').innerHTML = v.order_number ? '#' + v.order_number : 'Pedido #' + id;

    const badge = document.getElementById('md-status-badge');
    badge.innerHTML  = stLabels[v.status] || v.status;
    badge.style.cssText = 'font-size:11px;padding:2px 8px;border-radius:99px;margin-left:8px;border:1px solid;' + (stStyles[v.status] || '');

    document.getElementById('md-nombre').innerHTML  = v.nombre     || '—';
    document.getElementById('md-dni').innerHTML     = v.dni        || '—';
    document.getElementById('md-celular').innerHTML = v.wa_number  || '—';
    document.getElementById('md-ciudad').innerHTML  = v.ciudad     || '—';
    document.getElementById('md-correo').innerHTML  = v.correo     || '—';
    document.getElementById('md-pventa').innerHTML  = v.punto_venta || '—';
    document.getElementById('md-plan').innerHTML    = v.plan_nombre || '—';
    document.getElementById('md-tickets').innerHTML = (parseInt(v.tickets) > 0) ? (v.tickets + ' ticket(s)') : '—';
    document.getElementById('md-monto').innerHTML   = (parseFloat(v.monto) > 0) ? ('S/ ' + parseFloat(v.monto).toFixed(2)) : '—';
    document.getElementById('md-fecha').innerHTML   = v.created_at || '';

    const codeRow = document.getElementById('md-ticket-code-row');
    if (v.ticket_code && v.status === 'enviado') {
        document.getElementById('md-ticket-code').innerHTML = v.ticket_code;
        codeRow.style.display = 'block';
    } else { codeRow.style.display = 'none'; }

    const compWrap = document.getElementById('md-comprobante-wrap');
    if (v.payment_proof) {
        document.getElementById('md-comprobante-img').src = v.payment_proof;
        compWrap.style.display = 'block';
    } else { compWrap.style.display = 'none'; }

    document.getElementById('md-vista').style.display        = 'block';
    document.getElementById('md-edit').style.display         = 'none';
    document.getElementById('md-validar-form').style.display = 'none';

    const acc = document.getElementById('md-acciones');
    acc.innerHTML = '';
    acc.innerHTML += `<button onclick="activarEdicion()" style="font-size:11px;background:#F3F4F6;color:#374151;padding:7px 12px;border-radius:8px;border:none;cursor:pointer;">✏️ Editar</button>`;
    acc.innerHTML += `<button onclick="rifaEliminar(${id})" style="font-size:11px;background:#FEE2E2;color:#EF4444;padding:7px 12px;border-radius:8px;border:none;cursor:pointer;">🗑️ Eliminar</button>`;

    if (v.status === 'pendiente' || v.status === 'comprobante') {
        acc.innerHTML += `<button onclick="rifaValidarSinTicket(${id})" style="flex:1;min-width:120px;font-size:13px;font-weight:600;background:#16A34A;color:#fff;padding:8px 16px;border-radius:8px;border:none;cursor:pointer;">✓ Confirmar pago</button>`;
        acc.innerHTML += `<button onclick="rifaCancelar(${id})" style="font-size:11px;background:#FEE2E2;color:#EF4444;padding:7px 12px;border-radius:8px;border:none;cursor:pointer;">✕ Rechazar</button>`;
    }
    if (v.status === 'pendiente' && v.nombre) {
        const recs = v.recordatorios ?? 0;
        const bloq = recs >= 2;
        acc.innerHTML += `<button id="btn-recordar-${id}" onclick="rifaRecordar(${id})" ${bloq ? 'disabled' : ''}
            style="font-size:11px;background:${bloq ? '#F3F4F6' : '#EFF6FF'};color:${bloq ? '#9CA3AF' : '#2563EB'};padding:7px 12px;border-radius:8px;border:1px solid ${bloq ? '#E5E8EF' : '#BFDBFE'};cursor:${bloq ? 'not-allowed' : 'pointer'};"
            title="${bloq ? 'Límite de recordatorios alcanzado (máx. 2)' : 'Enviar recordatorio de pago por WhatsApp'}">
            📩 Recordatorio${recs > 0 ? ' ('+recs+'/2)' : ''}
        </button>`;
    }
    if (v.status === 'pagado') {
        acc.innerHTML += `<button onclick="mostrarFormValidar()" style="flex:1;min-width:120px;font-size:13px;font-weight:600;background:#2563EB;color:#fff;padding:8px 16px;border-radius:8px;border:none;cursor:pointer;">🎟️ Enviar tickets</button>`;
    }
    if (v.status === 'enviado') {
        acc.innerHTML += `<a href="/rifas/${id}/ticket-preview" target="_blank" style="font-size:11px;background:#F3F4F6;color:#374151;padding:7px 12px;border-radius:8px;text-decoration:none;">👁️ Ver boleto</a>`;
    }

    document.getElementById('modal-detalle').style.display = 'flex';
}

function cerrarDetalle() {
    document.getElementById('modal-detalle').style.display = 'none';
    document.getElementById('md-validar-form').style.display = 'none';
    document.getElementById('md-edit').style.display         = 'none';
    document.getElementById('md-acciones').style.display     = 'flex';
    activeId = null;
}
document.getElementById('modal-detalle')?.addEventListener('click', e => { if (e.target === e.currentTarget) cerrarDetalle(); });

// ── Acciones rápidas inline ───────────────────────────────────────────────────
async function confirmarPagoRapido(id) {
    const v = ventas[id];
    // Si no tiene plan/tickets definidos → abrir selector primero
    const sinDefinir = !parseInt(v?.tickets) || parseFloat(v?.monto) <= 0 || (v?.plan_nombre === 'Por validar');
    if (sinDefinir) {
        abrirSelectorPlan(id);
        return;
    }
    if (!confirm('¿Confirmar el pago de este pedido?')) return;
    showToast('Confirmando...', 'info');
    const r = await fetch(`/bixosales/pedidos-bot/${id}/validar`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf} });
    const d = await r.json();
    if (d.ok) { showToast('Pago confirmado ✓', 'success'); setTimeout(() => location.reload(), 900); }
    else showToast('Error al confirmar', 'error');
}

async function rifaCancelarRapido(id) {
    if (!confirm('¿Rechazar este comprobante?')) return;
    const r = await fetch(`/bixosales/pedidos-bot/${id}/cancelar`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf} });
    const d = await r.json();
    if (d.ok) { showToast('Rechazado', 'error'); setTimeout(() => location.reload(), 900); }
    else showToast('Error', 'error');
}

// ── Form tickets ──────────────────────────────────────────────────────────────
function mostrarFormValidar() {
    const v       = ventas[activeId];
    const cantidad = parseInt(v?.tickets) || 1;
    const prev     = v?.ticket_numbers || [];
    const campos   = document.getElementById('vf-tickets-campos');
    campos.innerHTML = '';
    for (let i = 0; i < cantidad; i++) {
        const valPrev = '';
        campos.innerHTML += `<div style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:11px;color:#9CA3AF;width:20px;text-align:right;">${i+1}.</span>
            <input type="text" id="vf-t-${i}" value="${valPrev}"
                   style="flex:1;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;"
                   placeholder="TS-02180" autocomplete="off">
        </div>`;
    }
    document.getElementById('md-validar-form').style.display = 'flex';
    document.getElementById('md-acciones').style.display = 'none';
    setTimeout(() => document.getElementById('vf-t-0')?.focus(), 100);
}

function ocultarFormValidar() {
    document.getElementById('md-validar-form').style.display = 'none';
    document.getElementById('md-acciones').style.display = 'flex';
}

async function rifaEnviarTickets() {
    const v       = ventas[activeId];
    const cantidad = parseInt(v?.tickets) || 1;
    const numeros  = [];
    for (let i = 0; i < cantidad; i++) {
        const val = document.getElementById('vf-t-'+i)?.value.trim();
        if (!val) { showToast('Completa el ticket N° '+(i+1), 'error'); return; }
        numeros.push(val);
    }
    const btn = document.querySelector('#md-validar-form button[onclick="rifaEnviarTickets()"]');
    if (btn) { btn.disabled = true; btn.textContent = '⏳ Enviando...'; }
    showToast('Enviando...', 'info');
    try {
        const r = await fetch(`/bixosales/pedidos-bot/${activeId}/enviar-membresia`, {
            method:'POST',
            headers:{'X-CSRF-TOKEN':csrf,'Content-Type':'application/json'},
            body: JSON.stringify({ numero_membresia: numeros.join(', '), ticket_numbers: numeros })
        });
        const d = await r.json();
        if (d.ok) { showToast('Tickets enviados ✓','success'); setTimeout(()=>{ cerrarDetalle(); location.reload(); },1200); }
        else { showToast('Error: '+(d.error||'desconocido'),'error'); if(btn){btn.disabled=false;btn.textContent='🎟️ Enviar tickets';} }
    } catch(e) {
        showToast('Error de conexión','error');
        if(btn){btn.disabled=false;btn.textContent='🎟️ Enviar tickets';}
    }
}

// ── Validar / cancelar / editar / eliminar ────────────────────────────────────
async function rifaValidarSinTicket(id) {
    const v = ventas[id];
    // Si el pedido no tiene plan/tickets definidos → pedir selección ANTES de confirmar
    const sinDefinir = !parseInt(v?.tickets) || parseFloat(v?.monto) <= 0 || (v?.plan_nombre === 'Por validar');
    if (sinDefinir) {
        abrirSelectorPlan(id);
        return;
    }
    if (!confirm('¿Confirmar este pago?')) return;
    const r = await fetch(`/bixosales/pedidos-bot/${id}/validar`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf} });
    const d = await r.json();
    if (d.ok) { showToast('Pago confirmado ✓','success'); setTimeout(()=>{ cerrarDetalle(); location.reload(); },1000); }
    else showToast('Error al validar','error');
}

// ── Selector de plan obligatorio antes de validar ──────────────────────────────
function abrirSelectorPlan(id) {
    pendienteValidarId = id;
    document.getElementById('sp-plan').value = '';
    document.getElementById('sp-custom-wrap').style.display = 'none';
    document.getElementById('sp-tickets').value = '';
    document.getElementById('sp-monto').value = '';
    document.getElementById('modal-selector-plan').style.display = 'flex';
}
function spPlanCambio() {
    const sel = document.getElementById('sp-plan');
    document.getElementById('sp-custom-wrap').style.display = (sel.value === '__custom__') ? 'grid' : 'none';
}
function cerrarSelectorPlan() {
    document.getElementById('modal-selector-plan').style.display = 'none';
    pendienteValidarId = null;
}
async function confirmarConPlan() {
    const id = pendienteValidarId;
    if (!id) return;
    const sel = document.getElementById('sp-plan');
    let plan_nombre = null, tickets = null, monto = null;
    if (sel.value && sel.value !== '__custom__') {
        const [pn, tk, mt] = sel.value.split('|');
        plan_nombre = pn; tickets = parseInt(tk); monto = parseFloat(mt);
    } else if (sel.value === '__custom__') {
        tickets = parseInt(document.getElementById('sp-tickets').value) || 0;
        monto   = parseFloat(document.getElementById('sp-monto').value) || 0;
        plan_nombre = 'Personalizado';
    }
    if (!tickets || tickets < 1) { showToast('Selecciona un plan o cantidad válida', 'error'); return; }

    // 1. Guardar plan/tickets/monto
    const rEdit = await fetch(`/bixosales/pedidos-bot/${id}/editar`, {
        method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Content-Type':'application/json'},
        body: JSON.stringify({ plan_nombre, tickets, monto })
    });
    const dEdit = await rEdit.json();
    if (!dEdit.ok) { showToast('Error al guardar el plan', 'error'); return; }

    // 2. Confirmar el pago
    const r = await fetch(`/bixosales/pedidos-bot/${id}/validar`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf} });
    const d = await r.json();
    if (d.ok) { showToast('Pago confirmado ✓','success'); setTimeout(()=>{ cerrarSelectorPlan(); cerrarDetalle(); location.reload(); },1000); }
    else showToast('Error al validar','error');
}

function activarEdicion() {
    const v = ventas[activeId];
    document.getElementById('ef-nombre').value      = v.nombre       || '';
    document.getElementById('ef-dni').value         = v.dni          || '';
    document.getElementById('ef-ciudad').value      = v.ciudad       || '';
    document.getElementById('ef-telefono').value    = v.telefono     || '';
    document.getElementById('ef-correo').value      = v.correo       || '';
    //document.getElementById('ef-ticket-code').value = (v.ticket_code || '').replace(/^TS-/i,'');
    document.getElementById('ef-ticket-code').value =
    v.ticket_code || '';
    document.getElementById('ef-punto-venta') && (document.getElementById('ef-punto-venta').value = v.punto_venta || '');
    // Precargar plan según tickets/monto actuales
    const sel = document.getElementById('ef-plan');
    if (sel) {
        const combo = `${v.plan_nombre || ''}|${v.tickets || ''}|${Number(v.monto || 0).toFixed(2)}`;
        const match = Array.from(sel.options).find(o => o.value === combo);
        if (match) { sel.value = combo; }
        else if (v.tickets || v.monto) { sel.value = '__custom__'; }
        else { sel.value = ''; }
        efPlanCambio();
        if (sel.value === '__custom__') {
            document.getElementById('ef-tickets').value = v.tickets || '';
            document.getElementById('ef-monto').value   = v.monto ? Number(v.monto).toFixed(2) : '';
        }
    }
    document.getElementById('md-vista').style.display = 'none';
    document.getElementById('md-edit').style.display  = 'flex';
}
function efPlanCambio() {
    const sel = document.getElementById('ef-plan');
    const wrap = document.getElementById('ef-custom-wrap');
    if (!sel || !wrap) return;
    wrap.style.display = (sel.value === '__custom__') ? 'grid' : 'none';
}
function cancelarEdicion() {
    document.getElementById('md-vista').style.display = 'block';
    document.getElementById('md-edit').style.display  = 'none';
}
async function guardarEdicion() {
    const nombre      = document.getElementById('ef-nombre').value;
    const dni         = document.getElementById('ef-dni').value;
    const ciudad      = document.getElementById('ef-ciudad').value;
    const telefono    = document.getElementById('ef-telefono').value;
    const correo      = document.getElementById('ef-correo').value;
    //const ticket_code = document.getElementById('ef-ticket-code').value ? 'TS-' + document.getElementById('ef-ticket-code').value.padStart(5,'0') : '';
    const ticket_code = document.getElementById('ef-ticket-code').value.trim();
    const punto_venta = document.getElementById('ef-punto-venta').value;

    // Plan / tickets / monto del desplegable
    let plan_nombre = null, tickets = null, monto = null;
    const sel = document.getElementById('ef-plan');
    if (sel && sel.value && sel.value !== '__custom__') {
        const [pn, tk, mt] = sel.value.split('|');
        plan_nombre = pn; tickets = parseInt(tk); monto = parseFloat(mt);
    } else if (sel && sel.value === '__custom__') {
        tickets = parseInt(document.getElementById('ef-tickets').value) || null;
        monto   = parseFloat(document.getElementById('ef-monto').value) || null;
        plan_nombre = 'Personalizado';
    }

    const r = await fetch(`/bixosales/pedidos-bot/${activeId}/editar`, {
        method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Content-Type':'application/json'},
        body: JSON.stringify({ nombre, dni, ciudad, telefono, email: correo, ticket_code, punto_venta, plan_nombre, tickets, monto })
    });
    const d = await r.json();
    if (d.ok) {
        ventas[activeId] = {...ventas[activeId], nombre, dni, ciudad, telefono, correo, ticket_code, punto_venta,
                            ...(plan_nombre!==null && {plan_nombre}), ...(tickets!==null && {tickets}), ...(monto!==null && {monto})};
        showToast('Datos guardados ✓','success');
        cancelarEdicion();
        document.getElementById('md-nombre').innerHTML  = nombre      || '—';
        document.getElementById('md-dni').innerHTML     = dni         || '—';
        document.getElementById('md-ciudad').innerHTML  = ciudad      || '—';
        document.getElementById('md-correo').innerHTML  = correo      || '—';
        document.getElementById('md-pventa').innerHTML  = punto_venta || '—';
        if (plan_nombre !== null) document.getElementById('md-plan').innerHTML    = plan_nombre || '—';
        if (tickets !== null)     document.getElementById('md-tickets').innerHTML = tickets ? (tickets + ' ticket(s)') : '—';
        if (monto !== null)       document.getElementById('md-monto').innerHTML   = monto ? ('S/ ' + Number(monto).toFixed(2)) : '—';
        if (ticket_code) {
            document.getElementById('md-ticket-code').innerHTML = ticket_code;
            document.getElementById('md-ticket-code-row').style.display = 'block';
        }
    } else showToast('Error al guardar','error');
}

async function rifaCancelar(id) {
    if (!confirm('¿Cancelar esta venta?')) return;
    const r = await fetch(`/bixosales/pedidos-bot/${id}/cancelar`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf} });
    const d = await r.json();
    if (d.ok) { showToast('Venta cancelada','error'); setTimeout(()=>{ cerrarDetalle(); location.reload(); },1000); }
    else showToast('Error al cancelar','error');
}

async function rifaEliminar(id) {
    if (!confirm('¿Eliminar este pedido permanentemente?')) return;
    const r = await fetch(`/bixosales/pedidos-bot/${id}/eliminar`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf} });
    const d = await r.json();
    if (d.ok) { showToast('Pedido eliminado','error'); cerrarDetalle(); setTimeout(()=>location.reload(),800); }
    else showToast('Error al eliminar','error');
}

async function rifaRecordar(id) {
    const btn = document.getElementById('btn-recordar-' + id);
    if (btn) { btn.disabled = true; btn.textContent = '📩 Enviando...'; }
    try {
        const r = await fetch(`/bixosales/pedidos-bot/${id}/recordar`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf} });
        const d = await r.json();
        if (r.ok && d.ok) {
            showToast(`Recordatorio enviado ✓ (${d.enviados}/2)`, 'success');
            if (btn) {
                btn.textContent = `📩 Recordatorio (${d.enviados}/2)`;
                if (d.enviados >= 2) { btn.disabled = true; btn.style.color = '#9CA3AF'; btn.style.background = '#F3F4F6'; }
                else btn.disabled = false;
            }
            if (ventas[id]) ventas[id].recordatorios = d.enviados;
        } else {
            showToast(d.message || 'Error al enviar', 'error');
            if (btn) { btn.disabled = false; btn.textContent = '📩 Recordatorio'; }
        }
    } catch(e) {
        showToast('Error de conexión', 'error');
        if (btn) { btn.disabled = false; btn.textContent = '📩 Recordatorio'; }
    }
}

// ── Bot panel ─────────────────────────────────────────────────────────────────
let botPanelOpen = true;
function setBotPanel(open) {
    botPanelOpen = open;
    const panel   = document.getElementById('bot-panel');
    const content = document.getElementById('bot-panel-content');
    const chevron = document.getElementById('bot-chevron');
    panel.style.width      = open ? '220px' : '22px';
    content.style.display  = open ? 'flex'  : 'none';
    chevron.style.transform = open ? 'rotate(180deg)' : 'rotate(0deg)';
}
function toggleBotPanel() { setBotPanel(!botPanelOpen); }

function checkBotStatus() {
    fetch('/bot-status/rifa')
        .then(r => r.json())
        .then(d => {
            const dot     = document.getElementById('bot-dot');
            const txt     = document.getElementById('bot-status-text');
            const spinner = document.getElementById('bot-qr-spinner');
            const qrImg   = document.getElementById('bot-qr-img');
            const qrHint  = document.getElementById('bot-qr-hint');
            const connMsg = document.getElementById('bot-connected-msg');
            const offMsg  = document.getElementById('bot-offline-msg');

            spinner.style.display = qrImg.style.display = qrHint.style.display =
            connMsg.style.display = offMsg.style.display = 'none';

            if (d.status === 'connected') {
                dot.style.background = '#22C55E';
                txt.innerHTML = 'Conectado ✓'; txt.style.color = '#16A34A';
                connMsg.style.display = 'block';
                setBotPanel(false);
            } else if (d.status === 'qr' && d.qr) {
                dot.style.background = '#F59E0B';
                txt.innerHTML = 'Escanear QR'; txt.style.color = '#D97706';
                qrImg.src = d.qr; qrImg.style.display = 'block';
                qrHint.style.display = 'block';
                setBotPanel(true);
            } else if (d.status === 'qr' || d.status === 'starting') {
                dot.style.background = '#3B82F6';
                txt.innerHTML = 'Iniciando...'; txt.style.color = '#2563EB';
                spinner.style.display = 'flex';
                setBotPanel(true);
            } else {
                dot.style.background = '#D1D5DB';
                txt.innerHTML = 'Offline'; txt.style.color = '#9CA3AF';
                offMsg.style.display = 'block';
                setBotPanel(true);
            }
        })
        .catch(() => {
            document.getElementById('bot-dot').style.background = '#D1D5DB';
            document.getElementById('bot-status-text').innerHTML = 'Sin conexión';
            setBotPanel(true);
        });
}
checkBotStatus();
setInterval(checkBotStatus, 8000);

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(msg, type='info') {
    const inner  = document.getElementById('toast-inner');
    const colors = { success:'#16A34A', error:'#EF4444', info:'#374151' };
    inner.style.background = colors[type] || colors.info;
    inner.innerHTML = msg;
    document.getElementById('toast').style.display = 'block';
    setTimeout(() => { document.getElementById('toast').style.display = 'none'; }, 3000);
}

// Tab inicial: desde query string o por defecto comprobante si hay pendientes
(function() {
    const params = new URLSearchParams(window.location.search);
    const tabUrl = params.get('estado');
    const tabsValidos = ['todos','comprobante','pendiente','pagado','enviado','cancelado'];
    if (tabUrl && tabsValidos.includes(tabUrl)) {
        filtrarTab(tabUrl);
    } else if ({{ $cnts['comprobante'] }} > 0) {
        filtrarTab('comprobante');
    } else {
        filtrarTab('todos');
    }
})();

// ── Nuevo Ticket Manual ───────────────────────────────────────────────────────
function abrirNuevoTicket() {
    document.getElementById('modal-nuevo-ticket').style.display = 'flex';
    document.getElementById('nt-dni').focus();
}
function cerrarNuevoTicket() {
    document.getElementById('modal-nuevo-ticket').style.display = 'none';
    document.getElementById('nt-form').reset();
    document.getElementById('nt-nombres').value = '';
    document.getElementById('nt-apellidos').value = '';
    document.getElementById('nt-reniec-hint').style.display = 'none';
}

async function consultarReniec() {
    const dni = document.getElementById('nt-dni').value.trim();
    if (dni.length !== 8) return;
    const hint = document.getElementById('nt-reniec-hint');
    hint.innerHTML = '<span class="bixo-spinner-purple"></span> Consultando...';
    hint.style.display = 'flex';
    try {
        const res  = await fetch('{{ url("/bixosales/consultar-dni") }}/' + dni);
        const data = await res.json();
        if (data.nombres) {
            document.getElementById('nt-nombres').value   = data.nombres || '';
            document.getElementById('nt-apellidos').value = (data.apellidoPaterno || '') + ' ' + (data.apellidoMaterno || '');
            hint.innerHTML = '✓ Datos cargados de RENIEC';
            hint.style.color = '#15803D';
        } else {
            hint.innerHTML = 'No encontrado en RENIEC';
            hint.style.color = '#9CA3AF';
        }
    } catch(e) {
        hint.innerHTML = 'Error al consultar RENIEC';
        hint.style.color = '#EF4444';
    }
}

async function guardarNuevoTicket() {
    const dni       = document.getElementById('nt-dni').value.trim();
    const nombres   = document.getElementById('nt-nombres').value.trim();
    const apellidos = document.getElementById('nt-apellidos').value.trim();
    const telefono  = document.getElementById('nt-telefono').value.trim();
    const correo    = document.getElementById('nt-correo').value.trim();
    const ciudad    = document.getElementById('nt-ciudad').value.trim();
    const pventa    = document.getElementById('nt-pventa').value.trim();
    const codigo    = document.getElementById('nt-codigo').value.trim();
    const plan      = document.getElementById('nt-plan').value;
    const monto     = document.getElementById('nt-monto').value;

    if (!dni || !nombres || !codigo) {
        showToast('DNI, nombre y código son obligatorios', 'error'); return;
    }

    const btn = document.getElementById('btn-guardar-nuevo');
    btn.disabled = true; btn.textContent = '⏳ Guardando...';

    const r = await fetch('{{ route("bixosales.rifas.nuevo-manual") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json' },
        body: JSON.stringify({ dni, nombres, apellidos, telefono, correo, ciudad, pventa, codigo, plan, monto })
    });
    const d = await r.json();
    if (d.ok) {
        showToast('Ticket registrado ✓', 'success');
        cerrarNuevoTicket();
        setTimeout(() => location.reload(), 1000);
    } else {
        showToast('Error: ' + (d.error || 'no se pudo guardar'), 'error');
        btn.disabled = false; btn.textContent = 'Guardar';
    }
}

document.getElementById('nt-dni')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); consultarReniec(); }
});
</script>

{{-- ── Modal Nuevo Ticket Manual ── --}}
<div id="modal-nuevo-ticket" style="position:fixed;left:0;right:0;top:52px;bottom:0;z-index:60;display:none;align-items:flex-start;justify-content:center;background:rgba(0,0,0,.45);padding:16px;overflow-y:auto;">
    <div style="width:100%;max-width:500px;background:#fff;border-radius:16px;box-shadow:0 24px 64px rgba(0,0,0,.18);margin:0 auto;">
        <div style="padding:14px 20px;border-bottom:1px solid #E5E8EF;display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:15px;font-weight:700;color:#111827;">➕ Nuevo ticket manual</span>
            <button onclick="cerrarNuevoTicket()" style="background:none;border:none;cursor:pointer;color:#9CA3AF;font-size:18px;">✕</button>
        </div>
        <form id="nt-form" style="padding:16px 20px;display:flex;flex-direction:column;gap:10px;" onsubmit="event.preventDefault();guardarNuevoTicket();">

            {{-- DNI --}}
            <div>
                <label style="font-size:11px;color:#6B7280;display:block;margin-bottom:3px;">DNI *</label>
                <div style="display:flex;gap:8px;">
                    <input type="text" id="nt-dni" maxlength="8" placeholder="12345678"
                           style="flex:1;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;">
                    <button type="button" onclick="consultarReniec()"
                            style="font-size:12px;font-weight:600;background:#2563EB;color:#fff;padding:8px 14px;border-radius:8px;border:none;cursor:pointer;white-space:nowrap;">
                        Consultar
                    </button>
                </div>
                <div id="nt-reniec-hint" style="display:none;align-items:center;gap:6px;font-size:11px;margin-top:4px;color:#6B7280;"></div>
            </div>

            {{-- Nombres / Apellidos --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <div>
                    <label style="font-size:11px;color:#6B7280;display:block;margin-bottom:3px;">Nombres *</label>
                    <input type="text" id="nt-nombres" placeholder="Nombres"
                           style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:11px;color:#6B7280;display:block;margin-bottom:3px;">Apellidos</label>
                    <input type="text" id="nt-apellidos" placeholder="Apellidos"
                           style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box;">
                </div>
            </div>

            {{-- Teléfono / Correo --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <div>
                    <label style="font-size:11px;color:#6B7280;display:block;margin-bottom:3px;">Teléfono</label>
                    <input type="text" id="nt-telefono" placeholder="9XXXXXXXX"
                           style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:11px;color:#6B7280;display:block;margin-bottom:3px;">Correo</label>
                    <input type="email" id="nt-correo" placeholder="correo@gmail.com"
                           style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box;">
                </div>
            </div>

            {{-- Ciudad / Punto de venta --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <div>
                    <label style="font-size:11px;color:#6B7280;display:block;margin-bottom:3px;">Ciudad</label>
                    <input type="text" id="nt-ciudad" placeholder="Lima"
                           style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:11px;color:#6B7280;display:block;margin-bottom:3px;">Punto de venta</label>
                    <input type="text" id="nt-pventa" placeholder="Tienda, local..."
                           style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box;">
                </div>
            </div>

            {{-- Plan / Monto --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <div>
                    <label style="font-size:11px;color:#6B7280;display:block;margin-bottom:3px;">Plan</label>
                    <select id="nt-plan" style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box;background:#fff;">
                        @foreach(\App\Models\Rifa::where('project_id', $project->id)->where('is_active', true)->orderBy('sort_order')->get() as $rifa)
                        <option value="{{ $rifa->nombre }}">{{ $rifa->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;color:#6B7280;display:block;margin-bottom:3px;">Monto (S/)</label>
                    <input type="number" id="nt-monto" placeholder="0.00" step="0.01" min="0"
                           style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;box-sizing:border-box;">
                </div>
            </div>

            {{-- Código ticket --}}
            <div>
                <label style="font-size:11px;color:#6B7280;display:block;margin-bottom:3px;">N° Ticket *</label>
                <input type="text" id="nt-codigo" placeholder="TS-00001"
                       style="width:100%;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:monospace;box-sizing:border-box;">
            </div>

            <button type="submit" id="btn-guardar-nuevo"
                    style="width:100%;font-size:13px;font-weight:600;background:#7C3AED;color:#fff;padding:10px;border-radius:8px;border:none;cursor:pointer;margin-top:4px;">
                Guardar
            </button>
        </form>
    </div>
</div>

</x-portal-layout>
