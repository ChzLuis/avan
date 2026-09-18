<x-portal-layout layout="panel" :project="$project" pageTitle="Dashboard Comercial">

@php
    $cur = '$';
    $fmt = fn($n) => number_format($n, 2);
@endphp

<div class="p-6 max-w-7xl mx-auto">

    <div class="mb-6 flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Panel Comercial</h1>
            <p class="text-sm text-gray-500">Controla tus ventas y actúa a tiempo · {{ $project->name }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ url('/orders') }}" class="text-xs px-3 py-2 border border-gray-200 rounded-lg text-gray-600 hover:border-indigo-400 hover:text-indigo-600 font-semibold bg-white">Centro de pedidos</a>
            <a href="{{ route('ventas.express') }}" class="text-xs px-4 py-2 rounded-lg bg-indigo-600 text-white font-bold hover:bg-indigo-700">⚡ Nueva venta</a>
        </div>
    </div>

    {{-- ═══ TARJETAS PRINCIPALES ═══ --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="text-xs font-semibold text-gray-400 uppercase">Ventas hoy</div>
            <div class="text-2xl font-black text-gray-800 mt-1">S/ {{ $fmt($ventasHoy['total']) }}</div>
            @php $dHoy = $ventasAyer > 0 ? round(($ventasHoy['total'] - $ventasAyer) / $ventasAyer * 100) : null; @endphp
            <div class="text-xs mt-1 {{ $dHoy === null ? 'text-gray-500' : ($dHoy >= 0 ? 'text-emerald-600' : 'text-red-500') }}">
                {{ $ventasHoy['cantidad'] }} pedidos @if($dHoy !== null)· {{ $dHoy >= 0 ? '▲' : '▼' }} {{ abs($dHoy) }}% vs ayer @endif
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="text-xs font-semibold text-gray-400 uppercase">Ventas del mes</div>
            <div class="text-2xl font-black text-indigo-600 mt-1">S/ {{ $fmt($ventasMes['total']) }}</div>
            @php $dMes = $mesAnterior > 0 ? round(($ventasMes['total'] - $mesAnterior) / $mesAnterior * 100) : null; @endphp
            <div class="text-xs mt-1 {{ $dMes === null ? 'text-gray-500' : ($dMes >= 0 ? 'text-emerald-600' : 'text-red-500') }}">
                {{ $ventasMes['cantidad'] }} pedidos @if($dMes !== null)· {{ $dMes >= 0 ? '▲' : '▼' }} {{ abs($dMes) }}% vs mes anterior @endif
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="text-xs font-semibold text-gray-400 uppercase">Leads calientes</div>
            <div class="text-2xl font-black text-red-500 mt-1">🔥 {{ $leads['caliente'] }}</div>
            <div class="text-xs text-gray-500 mt-1">de {{ $leads['total'] }} clientes</div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="text-xs font-semibold text-gray-400 uppercase">Conversión</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ $conversion }}%</div>
            <div class="text-xs text-gray-500 mt-1">{{ $embudo['ganado'] }} ganados</div>
        </div>
    </div>

    {{-- ═══ SEGUNDA FILA DE KPIs ═══ --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <a href="{{ url('/orders') }}" class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:border-amber-300 transition">
            <div class="text-xs font-semibold text-gray-400 uppercase">Por cobrar</div>
            <div class="text-2xl font-black text-amber-600 mt-1">S/ {{ $fmt($porCobrar) }}</div>
            <div class="text-xs text-gray-500 mt-1">Ver pagos pendientes →</div>
        </a>
        <a href="{{ url('/orders') }}" class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:border-indigo-300 transition">
            <div class="text-xs font-semibold text-gray-400 uppercase">Pedidos activos</div>
            <div class="text-2xl font-black text-blue-600 mt-1">{{ $pedidosActivos }}</div>
            <div class="text-xs {{ $sinActualizar > 0 ? 'text-red-500 font-semibold' : 'text-gray-500' }} mt-1">{{ $sinActualizar }} sin actualizar +48h</div>
        </a>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="text-xs font-semibold text-gray-400 uppercase">Ticket promedio</div>
            <div class="text-2xl font-black text-gray-800 mt-1">S/ {{ $fmt($ticketProm) }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ $clientesNuevosMes }} clientes nuevos este mes</div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="text-xs font-semibold text-gray-400 uppercase">Meta del mes</div>
            @if($meta > 0)
                <div class="text-2xl font-black {{ ($metaPct ?? 0) >= 100 ? 'text-emerald-600' : 'text-gray-800' }} mt-1">{{ $metaPct }}%</div>
                <div class="w-full h-1.5 bg-gray-100 rounded-full mt-2"><div class="h-1.5 rounded-full {{ ($metaPct ?? 0) >= 100 ? 'bg-emerald-500' : 'bg-indigo-500' }}" style="width:{{ min(100, $metaPct ?? 0) }}%"></div></div>
                <div class="text-xs text-gray-500 mt-1">de S/ {{ $fmt($meta) }}</div>
            @else
                <form method="POST" action="{{ route('dashboard.comercial.meta') }}" class="mt-1 flex gap-1.5">
                    @csrf
                    <input name="meta" type="number" min="0" step="100" placeholder="S/ meta mensual" class="w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5" required>
                    <button class="text-xs px-3 rounded-lg bg-indigo-600 text-white font-bold">OK</button>
                </form>
                <div class="text-[11px] text-gray-400 mt-1">Define tu meta para medir el avance</div>
            @endif
        </div>
    </div>

    {{-- ═══ RENDIMIENTO: ventas por día vs meta ═══ --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm mb-6">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
            <h2 class="font-bold text-gray-800">Rendimiento del mes</h2>
            <div class="text-xs text-gray-500">Acumulado: <strong class="text-gray-800">S/ {{ $fmt($ventasMes['total']) }}</strong>@if($meta > 0) · Ritmo esperado hoy: S/ {{ $fmt($meta / now()->daysInMonth * now()->day) }}@endif</div>
        </div>
        @php
            $maxDia = max(1, collect($grafico)->max('total'));
            $n = max(1, count($grafico));
            $bw = 100 / max(14, $n);
        @endphp
        <svg viewBox="0 0 100 34" preserveAspectRatio="none" class="w-full" style="height:150px">
            @foreach($grafico as $i => $g)
                <rect x="{{ $i * $bw + $bw * 0.15 }}" y="{{ 30 - ($g['total'] / $maxDia * 26) }}" width="{{ $bw * 0.7 }}" height="{{ $g['total'] / $maxDia * 26 }}" rx="0.6" fill="{{ $g['total'] > 0 ? '#6366f1' : '#e5e7eb' }}">
                    <title>Día {{ $g['dia'] }}: S/ {{ $fmt($g['total']) }}</title>
                </rect>
            @endforeach
            @if($meta > 0)
                @php $metaDia = $meta / now()->daysInMonth; $yMeta = 30 - min(26, $metaDia / $maxDia * 26); @endphp
                <line x1="0" y1="{{ $yMeta }}" x2="100" y2="{{ $yMeta }}" stroke="#f59e0b" stroke-width="0.35" stroke-dasharray="1.4,1"/>
            @endif
            <line x1="0" y1="30" x2="100" y2="30" stroke="#e5e7eb" stroke-width="0.3"/>
        </svg>
        <div class="flex justify-between text-[10px] text-gray-400 mt-1"><span>1 {{ now()->translatedFormat('M') }}</span>@if($meta > 0)<span class="text-amber-500 font-semibold">— — meta diaria (S/ {{ $fmt($meta / now()->daysInMonth) }})</span>@endif<span>{{ now()->format('j M') }}</span></div>
    </div>

    {{-- ═══ ALERTAS + RANKING ═══ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <h2 class="font-bold text-gray-800 mb-3">Alertas de hoy</h2>
            @forelse($alertas as $a)
                <a href="{{ $a['url'] }}" class="flex items-center gap-2.5 py-2 border-b border-gray-50 last:border-0 group">
                    <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $a['tipo'] === 'red' ? 'bg-red-500' : 'bg-amber-400' }}"></span>
                    <span class="text-sm text-gray-700 group-hover:text-indigo-600 flex-1">{{ $a['txt'] }}</span>
                    <span class="text-gray-300 group-hover:text-indigo-500">→</span>
                </a>
            @empty
                <div class="text-sm text-emerald-600 bg-emerald-50 rounded-xl px-4 py-3">✓ Todo al día: sin pendientes urgentes.</div>
            @endforelse
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <h2 class="font-bold text-gray-800 mb-3">Ranking del mes por vendedor</h2>
            @forelse($ranking as $i => $r)
                <div class="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0">
                    <span class="w-6 h-6 rounded-full bg-indigo-50 text-indigo-600 text-[11px] font-black flex items-center justify-center flex-shrink-0">{{ $i + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-gray-800 truncate">{{ $r['nombre'] }}</div>
                        {{-- Cotizadas y conversion: quien cotiza mucho y cierra
                             poco no se veia en un ranking de solo ventas. --}}
                        <div class="text-[11px] text-gray-400">
                            {{ $r['pedidos'] }} pedidos · {{ $r['cotizadas'] ?? 0 }} cotizadas
                            @if(($r['conversion'] ?? null) !== null)
                                · <span class="{{ $r['conversion'] >= 50 ? 'text-emerald-600' : ($r['conversion'] >= 20 ? 'text-amber-600' : 'text-gray-400') }}">{{ $r['conversion'] }}% cierre</span>
                            @endif
                            · ticket S/ {{ $fmt($r['ticket']) }}
                        </div>
                    </div>
                    <div class="text-sm font-black text-gray-900">S/ {{ $fmt($r['total']) }}</div>
                </div>
            @empty
                <div class="text-sm text-gray-400 bg-gray-50 rounded-xl px-4 py-3">Aún no hay ventas ni cotizaciones por vendedor este mes.</div>
            @endforelse
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ═══ EMBUDO DE VENTAS ═══ --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <h2 class="font-bold text-gray-800 mb-4">Embudo de ventas</h2>
            @php
                $etapasLabel = [
                    'prospecto'=>['Prospecto','#94a3b8'], 'contactado'=>['Contactado','#3b82f6'],
                    'propuesta'=>['Propuesta','#8b5cf6'], 'negociacion'=>['Negociación','#f59e0b'],
                    'ganado'=>['Ganado','#22c55e'], 'perdido'=>['Perdido','#ef4444'],
                ];
                $maxEmbudo = max(1, max($embudo));
            @endphp
            <div class="space-y-3">
                @foreach($etapasLabel as $key => $lbl)
                    <div class="flex items-center gap-3">
                        <div class="w-24 text-xs font-semibold text-gray-600 flex-shrink-0">{{ $lbl[0] }}</div>
                        <div class="flex-1 bg-gray-50 rounded-lg h-7 overflow-hidden">
                            <div class="h-full rounded-lg flex items-center px-2 text-xs font-bold text-white"
                                 style="width:{{ max(6, $embudo[$key]/$maxEmbudo*100) }}%; background:{{ $lbl[1] }}">
                                {{ $embudo[$key] }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Resumen leads por temperatura --}}
            <div class="grid grid-cols-3 gap-3 mt-6 pt-4 border-t border-gray-50">
                <div class="text-center"><div class="text-xl font-black text-red-500">{{ $leads['caliente'] }}</div><div class="text-xs text-gray-400">🔥 Calientes</div></div>
                <div class="text-center"><div class="text-xl font-black text-amber-500">{{ $leads['tibio'] }}</div><div class="text-xs text-gray-400">🟡 Tibios</div></div>
                <div class="text-center"><div class="text-xl font-black text-blue-500">{{ $leads['frio'] }}</div><div class="text-xs text-gray-400">🔵 Fríos</div></div>
            </div>
        </div>

        {{-- ═══ PANEL LATERAL: proyección + alertas ═══ --}}
        <div class="space-y-6">
            <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-5 text-white shadow-sm">
                <div class="text-xs font-semibold uppercase opacity-80">Proyección del mes</div>
                <div class="text-3xl font-black mt-1">S/ {{ $fmt($proyeccion) }}</div>
                <div class="text-xs opacity-70 mt-1">Estimado según ritmo actual</div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                <h3 class="font-bold text-gray-800 mb-3 text-sm">⚠️ Stock bajo</h3>
                @forelse($stockBajo->take(5) as $p)
                    <div class="flex justify-between items-center text-sm py-1.5 border-b border-gray-50 last:border-0">
                        <span class="text-gray-700 truncate">{{ $p['nombre'] }}</span>
                        <span class="font-bold text-red-500 flex-shrink-0 ml-2">{{ $p['stock'] }} u</span>
                    </div>
                @empty
                    <p class="text-xs text-gray-400">Sin productos con stock bajo. 👍</p>
                @endforelse
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                <h3 class="font-bold text-gray-800 mb-1 text-sm">Leads nuevos hoy</h3>
                <div class="text-3xl font-black text-indigo-600">{{ $leads['nuevos_hoy'] }}</div>
            </div>
        </div>
    </div>

    {{-- ═══ TOP PRODUCTOS ═══ --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm mt-6">
        <h2 class="font-bold text-gray-800 mb-4">Productos más vendidos</h2>
        @if($topProductos->count())
            <div class="space-y-2">
                @foreach($topProductos as $i => $p)
                    <div class="flex items-center gap-3">
                        <div class="w-6 h-6 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold flex-shrink-0">{{ $i+1 }}</div>
                        <div class="flex-1 text-sm text-gray-700 truncate">{{ $p->nombre }}</div>
                        <div class="text-sm font-bold text-gray-800">{{ (int)$p->unidades }} u</div>
                        <div class="text-sm text-emerald-600 font-semibold w-24 text-right">S/ {{ $fmt($p->total) }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400">Aún no hay ventas registradas para mostrar el ranking.</p>
        @endif
    </div>

</div>
</x-portal-layout>
