<x-portal-layout layout="panel" :project="$project" pageTitle="Dashboard Comercial">

@php
    $cur = '$';
    $fmt = fn($n) => number_format($n, 2);
@endphp

<div class="p-6 max-w-7xl mx-auto">

    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-800">Dashboard Comercial</h1>
        <p class="text-sm text-gray-500">Indicadores de {{ $project->name }} en tiempo real.</p>
    </div>

    {{-- ═══ TARJETAS PRINCIPALES ═══ --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="text-xs font-semibold text-gray-400 uppercase">Ventas hoy</div>
            <div class="text-2xl font-black text-gray-800 mt-1">S/ {{ $fmt($ventasHoy['total']) }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ $ventasHoy['cantidad'] }} pedidos</div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="text-xs font-semibold text-gray-400 uppercase">Ventas del mes</div>
            <div class="text-2xl font-black text-indigo-600 mt-1">S/ {{ $fmt($ventasMes['total']) }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ $ventasMes['cantidad'] }} pedidos</div>
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
