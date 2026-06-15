<x-portal-layout layout="comercial" :project="$project">
@php
$cat = $project->category ?? 'default';
$esRest = in_array($cat, ['restaurante','cafeteria']);

// KPI labels por rubro
$kpi = match(true) {
    in_array($cat, ['restaurante','cafeteria']) => ['v'=>'Ventas hoy','p'=>'Pedidos / Mesas','pend'=>'En cocina','acc'=>'Nueva orden','acc_route'=>'bixosales.pos'],
    in_array($cat, ['peluqueria','salon_belleza']) => ['v'=>'Facturación hoy','p'=>'Atenciones hoy','pend'=>'Por atender','acc'=>'Cobrar servicio','acc_route'=>'bixosales.pos'],
    $cat==='clinica'    => ['v'=>'Facturación hoy','p'=>'Atenciones hoy','pend'=>'Por atender','acc'=>'Cobrar atención','acc_route'=>'bixosales.pos'],
    $cat==='gimnasio'   => ['v'=>'Cobros hoy','p'=>'Membresías hoy','pend'=>'Por cobrar','acc'=>'Cobrar membresía','acc_route'=>'bixosales.pos'],
    $cat==='taller'     => ['v'=>'Facturación hoy','p'=>'Órdenes hoy','pend'=>'En taller','acc'=>'Nueva orden','acc_route'=>'bixosales.pos'],
    default             => ['v'=>'Ventas hoy','p'=>'Pedidos hoy','pend'=>'Por atender','acc'=>'Nueva venta','acc_route'=>'bixosales.pos'],
};

// Semáforo — lógica real simple
$semV = $varVentas === null ? 'gray' : ($varVentas >= 0 ? 'green' : ($varVentas >= -10 ? 'yellow' : 'red'));
$semP = $pendientes === 0 ? 'green' : ($pendientes <= 5 ? 'yellow' : 'red');
$semScore = 87; // TODO: calcular dinámico

// Score ring deg
$scoreDeg = round($semScore * 3.6);
$scoreColor = $semScore >= 80 ? '#10B981' : ($semScore >= 60 ? '#F59E0B' : '#EF4444');
@endphp

{{-- ══════════════════════════════════════════════════════
     CENTRO OPERATIVO
══════════════════════════════════════════════════════ --}}
<div class="co-wrap" id="centroOp" x-data="centroOp()" x-init="init()">

    {{-- ── SECCIÓN 1: AVAN SCORE + SEMÁFORO ── --}}
    <div class="co-header">

        {{-- AVAN Score --}}
        <div class="score-card">
            <div class="score-ring-lg" style="background: conic-gradient({{ $scoreColor }} {{ $scoreDeg }}deg, #E5E8EF 0);">
                <div class="score-inner-lg">
                    <span class="score-num">{{ $semScore }}</span>
                    <span class="score-den">/100</span>
                </div>
            </div>
            <div class="score-info">
                <p class="score-title">AVAN Score</p>
                <p class="score-sub">
                    @if($varVentas !== null)
                        <span style="color:{{ $varVentas >= 0 ? '#10B981' : '#EF4444' }}">
                            {{ $varVentas >= 0 ? '▲' : '▼' }} {{ abs($varVentas) }}% ventas vs ayer
                        </span>
                    @else
                        Estado general del negocio
                    @endif
                </p>
                <div class="score-bar-wrap">
                    <div class="score-bar-fill" style="width:{{ $semScore }}%; background:{{ $scoreColor }};"></div>
                </div>
            </div>
        </div>

        {{-- Semáforo empresarial --}}
        <div class="semaforo-card">
            <p class="section-label">SEMÁFORO EMPRESARIAL</p>
            <div class="sema-grid">
                @php
                $areas = [
                    ['Comercial',   $semV,                   'Tendencia de ventas'],
                    ['Operaciones', $pendientes<=3?'green':($pendientes<=8?'yellow':'red'), 'Pedidos activos: '.$pendientes],
                    ['Caja',        'green',                 'Sin alertas'],
                    ['Logística',   'yellow',                'Delivery en proceso'],
                    ['Stock',       'green',                 'Niveles normales'],
                ];
                @endphp
                @foreach($areas as [$alabel, $acolor, $ahint])
                <div class="sema-item" title="{{ $ahint }}">
                    <div class="sema-dot-lg sema-dot-{{ $acolor }}"></div>
                    <span class="sema-label">{{ $alabel }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- KPIs rápidos --}}
        <div class="kpi-strip">
            <div class="kpi-mini">
                <p class="kpi-mini-label">{{ $kpi['v'] }}</p>
                <p class="kpi-mini-val">S/ {{ number_format($ventasHoy, 0) }}</p>
                @if($varVentas !== null)
                <span class="kpi-mini-trend" style="color:{{ $varVentas>=0?'#059669':'#DC2626' }}">
                    {{ $varVentas>=0?'▲':'▼' }} {{ abs($varVentas) }}%
                </span>
                @endif
            </div>
            <div class="kpi-mini">
                <p class="kpi-mini-label">{{ $kpi['p'] }}</p>
                <p class="kpi-mini-val">{{ $pedidosHoy }}</p>
                @if($varPedidos !== null)
                <span class="kpi-mini-trend" style="color:{{ $varPedidos>=0?'#059669':'#DC2626' }}">
                    {{ $varPedidos>=0?'▲':'▼' }} {{ abs($varPedidos) }}%
                </span>
                @endif
            </div>
            <div class="kpi-mini">
                <p class="kpi-mini-label">{{ $kpi['pend'] }}</p>
                <p class="kpi-mini-val" style="color:{{ $pendientes>0?'#F59E0B':'#10B981' }}">{{ $pendientes }}</p>
                <span class="kpi-mini-trend" style="color:#9CA3AF">activos ahora</span>
            </div>
            <div class="kpi-mini">
                <p class="kpi-mini-label">WhatsApp</p>
                <p class="kpi-mini-val">{{ $waPendientes }}</p>
                <span class="kpi-mini-trend" style="color:#9CA3AF">en proceso</span>
            </div>
        </div>
    </div>

    {{-- ── SECCIÓN 2: MAPA OPERATIVO ── --}}
    <div class="co-section">
        <div class="co-section-header">
            <div>
                <p class="section-label">MAPA OPERATIVO</p>
                <h2 class="section-title">
                    @if($esRest) Mesas
                    @else Estado del negocio
                    @endif
                </h2>
            </div>
            <div style="display:flex; gap:8px; align-items:center;">
                {{-- Leyenda --}}
                <div class="mapa-leyenda">
                    <span class="ley-dot" style="background:#10B981;"></span><span>Libre</span>
                    <span class="ley-dot" style="background:#F59E0B;"></span><span>Atención</span>
                    <span class="ley-dot" style="background:#EF4444;"></span><span>Urgente</span>
                    <span class="ley-dot" style="background:#E5E8EF;"></span><span>Cerrada</span>
                </div>
                @if($esRest)
                <a href="{{ route('bixosales.mesas') }}"
                   style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px;
                          background:var(--blue); color:#fff; border-radius:8px;
                          font-size:12px; font-weight:600; text-decoration:none;
                          transition:background .1s;"
                   onmouseover="this.style.background='#1D4ED8'"
                   onmouseout="this.style.background='#2563EB'">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Abrir mapa completo
                </a>
                @endif
            </div>
        </div>

        {{-- Mapa de mesas (restaurante) --}}
        @if($esRest)
        <div class="mapa-grid" id="mapaMesas" x-ref="mapa">
            <template x-if="mesasLoading">
                <div style="grid-column:1/-1; text-align:center; padding:40px 0; color:var(--muted);">
                    <svg style="width:24px;height:24px;animation:spin 1s linear infinite;margin:0 auto 8px;"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <p style="font-size:13px;">Cargando mesas...</p>
                </div>
            </template>
            <template x-if="!mesasLoading && mesas.length === 0">
                <div style="grid-column:1/-1; text-align:center; padding:40px 0;">
                    <p style="font-size:13px; color:var(--muted);">No hay mesas configuradas.</p>
                    <a href="{{ route('bixosales.mesas') }}" style="font-size:12px; color:var(--blue);">
                        Ir al mapa de mesas →
                    </a>
                </div>
            </template>
            <template x-for="m in mesas" :key="m.number">
                <div class="mesa-cell"
                     :style="mesaStyle(m)"
                     @click="window.location='{{ route('bixosales.mesas') }}'">
                    <div class="mesa-num" x-text="'M' + m.number"></div>
                    <div class="mesa-timer" x-show="m.minutes !== null">
                        <svg style="width:10px;height:10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span x-text="m.minutes + 'm'"></span>
                    </div>
                    <div class="mesa-estado" x-text="mesaLabel(m)"></div>
                    <div class="mesa-monto" x-show="m.total > 0" x-text="'S/ ' + m.total.toLocaleString()"></div>
                </div>
            </template>
        </div>

        {{-- No restaurante: vista operativa genérica --}}
        @else
        <div class="ops-grid">
            {{-- Ventas semana visual --}}
            <div class="ops-card ops-card-wide">
                <p class="ops-card-title">Ventas últimos 7 días</p>
                <div style="height:130px; position:relative;">
                    <canvas id="chartVentas"></canvas>
                </div>
            </div>
            {{-- Estados --}}
            <div class="ops-card">
                <p class="ops-card-title">Pedidos por estado</p>
                <div style="height:100px; display:flex; align-items:center; justify-content:center;">
                    <canvas id="chartEstados"></canvas>
                </div>
                <div style="margin-top:8px; display:flex; flex-direction:column; gap:4px;">
                    @foreach(['Nuevos'=>'#F59E0B','En proceso'=>'#3B82F6','Completados'=>'#10B981','Cancelados'=>'#EF4444'] as $lbl=>$col)
                    <div style="display:flex; align-items:center; justify-content:space-between; font-size:11px;">
                        <div style="display:flex; align-items:center; gap:5px;">
                            <span style="width:8px;height:8px;border-radius:50%;background:{{ $col }};display:inline-block;"></span>
                            <span style="color:var(--muted);">{{ $lbl }}</span>
                        </div>
                        <span style="font-weight:600;color:var(--text);">{{ $donaData[array_search($lbl,array_keys(['Nuevos'=>'','En proceso'=>'','Completados'=>'','Cancelados'=>'']))] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- ── SECCIÓN 3: OBJETOS OPERATIVOS ── --}}
    <div class="co-section">
        <div class="co-section-header">
            <div>
                <p class="section-label">OBJETOS OPERATIVOS</p>
                <h2 class="section-title">Activos ahora</h2>
            </div>
            <a href="{{ route('bixosales.pedidos') }}"
               style="font-size:12px; color:var(--blue); text-decoration:none; font-weight:500;">
                Ver todos →
            </a>
        </div>

        <div class="objetos-scroll">

            {{-- Pedidos activos --}}
            @forelse($pedidosRecientes->whereIn('status',['pending','process'])->take(8) as $o)
            @php
                $mins = $o->created_at->diffInMinutes(now());
                $urgencia = $mins >= 30 ? 'red' : ($mins >= 15 ? 'yellow' : 'green');
                $statusLabel = match($o->status) {
                    'pending' => 'Nuevo',
                    'process' => match(true) {
                        $esRest => 'En cocina',
                        default => 'En proceso',
                    },
                    default => 'Activo',
                };
            @endphp
            <a href="{{ route('bixosales.pedidos') }}" class="obj-card obj-{{ $urgencia }}">
                <div class="obj-header">
                    <div class="obj-icon obj-icon-{{ $urgencia }}">
                        @if($o->sales_channel === 'whatsapp')
                        <svg style="width:16px;height:16px;" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                        </svg>
                        @elseif($o->order_type === 'delivery')
                        <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10h10zM13 8h4l3 3v5h-7V8z"/>
                        </svg>
                        @else
                        <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        @endif
                    </div>
                    <div class="obj-meta">
                        <p class="obj-name">Pedido #{{ $o->id }}</p>
                        <p class="obj-sub">{{ $o->client_name }}</p>
                    </div>
                    <div class="timer timer-{{ $urgencia }}">
                        ⏱ {{ $mins }}m
                    </div>
                </div>
                <div class="obj-body">
                    <div class="obj-row">
                        <span class="obj-row-label">Estado</span>
                        <span class="obj-status obj-status-{{ $urgencia }}">{{ $statusLabel }}</span>
                    </div>
                    <div class="obj-row">
                        <span class="obj-row-label">Monto</span>
                        <span class="obj-val">S/ {{ number_format($o->total, 2) }}</span>
                    </div>
                    @if($o->table_number)
                    <div class="obj-row">
                        <span class="obj-row-label">Mesa</span>
                        <span class="obj-val">{{ $o->table_number }}</span>
                    </div>
                    @endif
                </div>
            </a>
            @empty
            <div style="grid-column:1/-1; padding:32px; text-align:center; color:var(--muted); font-size:13px;">
                No hay pedidos activos en este momento.
            </div>
            @endforelse

            {{-- Acción rápida --}}
            <a href="{{ route($kpi['acc_route']) }}"
               style="display:flex; flex-direction:column; align-items:center; justify-content:center;
                      gap:10px; min-width:160px; padding:20px;
                      border:2px dashed var(--border); border-radius:12px;
                      text-decoration:none; cursor:pointer; transition:all .15s;
                      color:var(--muted);"
               onmouseover="this.style.borderColor='var(--blue)'; this.style.color='var(--blue)'; this.style.background='var(--blue-light)'"
               onmouseout="this.style.borderColor='var(--border)'; this.style.color='var(--muted)'; this.style.background='none'">
                <svg style="width:24px;height:24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4"/>
                </svg>
                <span style="font-size:12px; font-weight:600; text-align:center;">{{ $kpi['acc'] }}</span>
            </a>
        </div>
    </div>

    {{-- ── SECCIÓN 4: TOP PRODUCTOS + VENTAS ── --}}
    <div class="co-section co-bottom-grid">

        {{-- Top productos --}}
        <div class="co-card">
            <div class="co-card-header">
                <p class="section-label">TOP PRODUCTOS HOY</p>
            </div>
            @forelse($topProductos as $i => $p)
            <div class="top-item">
                <span class="top-rank">{{ $i+1 }}</span>
                <div class="top-info">
                    <p class="top-name">{{ $p->name }}</p>
                    <div class="top-bar-wrap">
                        <div class="top-bar-fill"
                             style="width:{{ $topProductos->first()->qty > 0 ? round(($p->qty/$topProductos->first()->qty)*100) : 0 }}%">
                        </div>
                    </div>
                </div>
                <div class="top-nums">
                    <p class="top-qty">{{ $p->qty }} uds</p>
                    <p class="top-total">S/ {{ number_format($p->total, 0) }}</p>
                </div>
            </div>
            @empty
            <p style="font-size:12px; color:var(--muted); text-align:center; padding:20px 0;">Sin ventas aún</p>
            @endforelse
        </div>

        {{-- Ventas 7 días (solo si no restaurante ya lo tiene arriba) --}}
        @if($esRest)
        <div class="co-card co-card-wide">
            <div class="co-card-header">
                <p class="section-label">VENTAS 7 DÍAS</p>
            </div>
            <div style="height:150px; position:relative;">
                <canvas id="chartVentas"></canvas>
            </div>
        </div>
        @endif

        {{-- Recientes --}}
        <div class="co-card {{ $esRest ? '' : 'co-card-wide' }}">
            <div class="co-card-header">
                <p class="section-label">ÚLTIMOS PEDIDOS</p>
                <a href="{{ route('bixosales.pedidos') }}"
                   style="font-size:11px; color:var(--blue); text-decoration:none;">Ver todos →</a>
            </div>
            @forelse($pedidosRecientes->take(8) as $o)
            @php
                $sc = ['pending'=>['#FEF9C3','#92400E'], 'process'=>['#DBEAFE','#1E40AF'], 'done'=>['#D1FAE5','#065F46'], 'cancelled'=>['#FEE2E2','#991B1B']];
                $sl = ['pending'=>'Nuevo','process'=>'En proceso','done'=>'Completado','cancelled'=>'Cancelado'];
                [$bg,$tc] = $sc[$o->status] ?? ['#F3F4F6','#6B7280'];
            @endphp
            <div style="display:flex; align-items:center; gap:10px;
                        padding:8px 0; border-bottom:1px solid var(--border);">
                <div style="flex:1; min-width:0;">
                    <p style="font-size:12px; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ $o->client_name }}
                    </p>
                    <p style="font-size:10px; color:var(--muted-light);">
                        {{ $o->created_at->diffForHumans() }}
                    </p>
                </div>
                <p style="font-size:12px; font-weight:700; color:var(--text); white-space:nowrap;">
                    S/ {{ number_format($o->total, 2) }}
                </p>
                <span style="font-size:10px; font-weight:600; padding:2px 8px; border-radius:99px;
                             background:{{ $bg }}; color:{{ $tc }}; white-space:nowrap;">
                    {{ $sl[$o->status] ?? $o->status }}
                </span>
            </div>
            @empty
            <p style="font-size:12px; color:var(--muted); text-align:center; padding:20px 0;">Sin pedidos aún</p>
            @endforelse
        </div>

    </div>

</div>

{{-- ══ ESTILOS DEL CENTRO OPERATIVO ══ --}}
<style>
/* Wrapper */
.co-wrap {
    padding: 20px;
    display: flex; flex-direction: column; gap: 20px;
    overflow-y: auto; height: 100%;
}

/* Header: Score + Semáforo + KPIs */
.co-header {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 16px;
    align-items: stretch;
}
@media (max-width: 1100px) {
    .co-header { grid-template-columns: 1fr 1fr; }
    .kpi-strip { grid-column: 1/-1; }
}
@media (max-width: 700px) {
    .co-header { grid-template-columns: 1fr; }
}

/* Score card */
.score-card {
    display: flex; align-items: center; gap: 16px;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; padding: 16px 20px;
    box-shadow: var(--shadow-sm);
}
.score-ring-lg {
    width: 72px; height: 72px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.score-inner-lg {
    width: 54px; height: 54px; border-radius: 50%;
    background: var(--surface);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.score-num { font-size: 18px; font-weight: 800; color: var(--blue); line-height: 1; }
.score-den { font-size: 9px; color: var(--muted); font-weight: 500; }
.score-info { display: flex; flex-direction: column; gap: 4px; }
.score-title { font-size: 13px; font-weight: 700; color: var(--text); }
.score-sub { font-size: 11px; color: var(--muted); }
.score-bar-wrap {
    width: 110px; height: 4px; background: var(--border); border-radius: 2px; margin-top: 4px;
}
.score-bar-fill { height: 100%; border-radius: 2px; transition: width .5s; }

/* Semáforo card */
.semaforo-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; padding: 16px 20px;
    box-shadow: var(--shadow-sm);
}
.sema-grid {
    display: flex; flex-wrap: wrap; gap: 10px 20px; margin-top: 10px;
}
.sema-item {
    display: flex; align-items: center; gap: 7px; cursor: pointer;
}
.sema-dot-lg {
    width: 11px; height: 11px; border-radius: 50%; flex-shrink: 0;
}
.sema-dot-green  { background: #10B981; box-shadow: 0 0 0 3px #D1FAE5; }
.sema-dot-yellow { background: #F59E0B; box-shadow: 0 0 0 3px #FDE68A; }
.sema-dot-red    { background: #EF4444; box-shadow: 0 0 0 3px #FECACA; animation: sema-pulse 1.8s infinite; }
.sema-dot-gray   { background: #D1D5DB; box-shadow: 0 0 0 3px #F3F4F6; }
.sema-label { font-size: 12px; font-weight: 500; color: var(--text); }

/* KPI strip */
.kpi-strip {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;
}
@media (max-width: 800px) {
    .kpi-strip { grid-template-columns: repeat(2, 1fr); }
}
.kpi-mini {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 12px 14px;
    box-shadow: var(--shadow-sm);
}
.kpi-mini-label { font-size: 11px; color: var(--muted); font-weight: 500; margin-bottom: 4px; }
.kpi-mini-val   { font-size: 22px; font-weight: 800; color: var(--text); line-height: 1.1; }
.kpi-mini-trend { font-size: 11px; font-weight: 500; }

/* Section headers */
.section-label {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: var(--muted); margin-bottom: 4px;
}
.section-title { font-size: 16px; font-weight: 700; color: var(--text); }
.co-section { display: flex; flex-direction: column; gap: 12px; }
.co-section-header {
    display: flex; align-items: flex-end; justify-content: space-between;
}

/* Leyenda mapa */
.mapa-leyenda {
    display: flex; align-items: center; gap: 10px;
    font-size: 11px; color: var(--muted); font-weight: 500;
}
.ley-dot {
    width: 9px; height: 9px; border-radius: 50%; display: inline-block;
}

/* Mapa de mesas */
.mapa-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 10px;
}
.mesa-cell {
    background: var(--surface); border: 2px solid var(--border);
    border-radius: 12px; padding: 12px 10px 10px;
    display: flex; flex-direction: column; align-items: center;
    cursor: pointer; transition: all .15s;
    min-height: 90px; position: relative;
    text-align: center;
}
.mesa-cell:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); }
.mesa-num  { font-size: 15px; font-weight: 800; margin-bottom: 4px; }
.mesa-timer { font-size: 10px; font-weight: 600; display: flex; align-items: center; gap: 3px; }
.mesa-estado { font-size: 10px; font-weight: 500; color: var(--muted); margin-top: 2px; }
.mesa-monto  { font-size: 11px; font-weight: 700; margin-top: 4px; }

/* Objetos operativos */
.objetos-scroll {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 10px;
}
.obj-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 14px;
    display: flex; flex-direction: column; gap: 10px;
    text-decoration: none; color: inherit;
    position: relative; overflow: hidden;
    transition: box-shadow .15s, border-color .15s;
    border-left: 3px solid transparent;
}
.obj-card:hover { box-shadow: var(--shadow-md); }
.obj-card.obj-green  { border-left-color: #10B981; }
.obj-card.obj-yellow { border-left-color: #F59E0B; }
.obj-card.obj-red    { border-left-color: #EF4444; }
.obj-header { display: flex; align-items: flex-start; gap: 8px; }
.obj-icon {
    width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.obj-icon-green  { background: #D1FAE5; color: #059669; }
.obj-icon-yellow { background: #FEF3C7; color: #D97706; }
.obj-icon-red    { background: #FEE2E2; color: #DC2626; }
.obj-meta { flex: 1; min-width: 0; }
.obj-name { font-size: 13px; font-weight: 700; color: var(--text); }
.obj-sub  { font-size: 11px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.obj-body { display: flex; flex-direction: column; gap: 4px; }
.obj-row  { display: flex; justify-content: space-between; align-items: center; font-size: 11px; }
.obj-row-label { color: var(--muted); }
.obj-val  { font-weight: 600; color: var(--text); }
.obj-status { font-size: 10px; font-weight: 600; padding: 1px 7px; border-radius: 99px; }
.obj-status-green  { background: #D1FAE5; color: #065F46; }
.obj-status-yellow { background: #FEF3C7; color: #92400E; }
.obj-status-red    { background: #FEE2E2; color: #991B1B; }

/* Cronómetros */
.timer { display: inline-flex; align-items: center; gap: 3px; padding: 2px 7px; border-radius: 99px; font-size: 11px; font-weight: 600; flex-shrink: 0; }
.timer-green  { background: #D1FAE5; color: #059669; }
.timer-yellow { background: #FEF3C7; color: #D97706; }
.timer-red    { background: #FEE2E2; color: #DC2626; animation: timer-pulse 2s infinite; }

/* Bottom grid */
.co-bottom-grid {
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 14px;
    flex-direction: unset !important;
}
@media (max-width: 900px) {
    .co-bottom-grid { grid-template-columns: 1fr; }
}
.co-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; padding: 16px;
    box-shadow: var(--shadow-sm);
}
.co-card-wide { grid-column: span 1; } /* ajustado por grid padre */
.co-card-header {
    display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;
}
.top-item { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.top-rank {
    width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0;
    background: var(--blue-light); color: var(--blue);
    font-size: 10px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}
.top-info { flex: 1; min-width: 0; }
.top-name { font-size: 12px; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.top-bar-wrap { height: 3px; background: var(--border); border-radius: 2px; margin-top: 4px; }
.top-bar-fill { height: 100%; background: var(--blue); border-radius: 2px; }
.top-nums { text-align: right; flex-shrink: 0; }
.top-qty   { font-size: 11px; font-weight: 700; color: var(--text); }
.top-total { font-size: 10px; color: var(--muted); }

/* Ops genéricas */
.ops-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 12px; }
@media (max-width: 800px) { .ops-grid { grid-template-columns: 1fr; } }
.ops-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 16px; box-shadow: var(--shadow-sm);
}
.ops-card-wide { grid-column: 1; }
.ops-card-title { font-size: 12px; font-weight: 600; color: var(--text); margin-bottom: 10px; }

@keyframes timer-pulse { 0%,100%{opacity:1} 50%{opacity:.65} }
@keyframes sema-pulse  { 0%,100%{box-shadow:0 0 0 3px #FECACA} 50%{box-shadow:0 0 0 6px #FEE2E2} }
@keyframes spin        { to{transform:rotate(360deg)} }
</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Chart ventas 7 días ──────────────────────────────────
const ctxV = document.getElementById('chartVentas');
if (ctxV) {
    new Chart(ctxV, {
        type: 'line',
        data: {
            labels: @json($labels7),
            datasets: [{
                data: @json($data7),
                borderColor: '#2563EB',
                backgroundColor: 'rgba(37,99,235,0.07)',
                borderWidth: 2,
                pointBackgroundColor: '#2563EB',
                pointRadius: 3,
                tension: 0.4, fill: true,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#F3F4F6' },
                     ticks: { font: { size: 10 }, callback: v => 'S/ '+v } },
                x: { grid: { display: false }, ticks: { font: { size: 10 } } }
            }
        }
    });
}

// ── Chart estados (dona) ─────────────────────────────────
const ctxE = document.getElementById('chartEstados');
if (ctxE) {
    new Chart(ctxE, {
        type: 'doughnut',
        data: {
            labels: @json($donaLabels),
            datasets: [{ data: @json($donaData),
                backgroundColor: ['#FCD34D','#60A5FA','#34D399','#F87171'],
                borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false,
                   plugins: { legend: { display: false } }, cutout: '65%' }
    });
}

// ── Alpine: Centro Operativo ─────────────────────────────
function centroOp() {
    return {
        mesas: [], mesasLoading: true,
        async init() {
            @if($esRest)
            try {
                const r = await fetch('{{ route("bixosales.mesas.data") }}');
                const d = await r.json();
                this.mesas = d.mesas || [];
            } catch(e) { this.mesas = []; }
            this.mesasLoading = false;
            // refresh cada 20s
            setInterval(async () => {
                try {
                    const r = await fetch('{{ route("bixosales.mesas.data") }}');
                    const d = await r.json();
                    this.mesas = d.mesas || [];
                } catch(e) {}
            }, 20000);
            @else
            this.mesasLoading = false;
            @endif
        },
        mesaStyle(m) {
            const colors = {
                libre:     { bg: '#F0FDF4', border: '#10B981', text: '#065F46' },
                pedido:    { bg: '#FFFBEB', border: '#F59E0B', text: '#92400E' },
                cooking:   { bg: '#FFF7ED', border: '#F97316', text: '#7C2D12' },
                ready:     { bg: '#EFF6FF', border: '#3B82F6', text: '#1E40AF' },
                cerrada:   { bg: '#F9FAFB', border: '#E5E7EB', text: '#6B7280' },
            };
            const c = colors[m.status] || colors.cerrada;
            return `background:${c.bg}; border-color:${c.border}; color:${c.text};`;
        },
        mesaLabel(m) {
            const labels = { libre:'Libre', pedido:'Con pedido', cooking:'En cocina', ready:'Listo', cerrada:'Cerrada' };
            return labels[m.status] || m.status;
        }
    };
}
</script>
@endpush
</x-portal-layout>
