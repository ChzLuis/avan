<x-portal-layout layout="comercial" :project="$project" pageTitle="Dashboard">
<div class="flex-1 overflow-y-auto p-5 space-y-5">

    {{-- ── KPIs ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Ventas hoy --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs text-gray-500 font-medium">Ventas hoy</p>
                <span class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <p class="text-2xl font-bold text-gray-800">S/ {{ number_format($ventasHoy, 2) }}</p>
            @if($varVentas !== null)
                <p class="text-xs mt-1 {{ $varVentas >= 0 ? 'text-green-600' : 'text-red-500' }}">
                    {{ $varVentas >= 0 ? '▲' : '▼' }} {{ abs($varVentas) }}% vs ayer
                </p>
            @else
                <p class="text-xs mt-1 text-gray-400">S/ {{ number_format($ventasAyer, 2) }} ayer</p>
            @endif
        </div>

        {{-- Pedidos hoy --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs text-gray-500 font-medium">Pedidos hoy</p>
                <span class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </span>
            </div>
            <p class="text-2xl font-bold text-gray-800">{{ $pedidosHoy }}</p>
            @if($varPedidos !== null)
                <p class="text-xs mt-1 {{ $varPedidos >= 0 ? 'text-green-600' : 'text-red-500' }}">
                    {{ $varPedidos >= 0 ? '▲' : '▼' }} {{ abs($varPedidos) }}% vs ayer
                </p>
            @else
                <p class="text-xs mt-1 text-gray-400">{{ $pedidosAyer }} ayer</p>
            @endif
        </div>

        {{-- Pendientes --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs text-gray-500 font-medium">Por atender</p>
                <span class="w-8 h-8 rounded-lg bg-yellow-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <p class="text-2xl font-bold text-yellow-600">{{ $pendientes }}</p>
            <p class="text-xs mt-1 text-gray-400">pedidos sin procesar</p>
        </div>

        {{-- WhatsApp --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-medium" style="color:#25D366">WhatsApp activos</p>
                <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:#f0fdf4">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="#25D366">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                    </svg>
                </span>
            </div>
            <p class="text-2xl font-bold text-gray-800">{{ $waPendientes }}</p>
            <p class="text-xs mt-1 text-gray-400">en proceso de entrega</p>
        </div>

    </div>

    {{-- ── Gráficas ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Ventas 7 días --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700">Ventas últimos 7 días</h3>
                <span class="text-xs text-gray-400">S/ total por día</span>
            </div>
            <div class="h-48">
                <canvas id="chartVentas"></canvas>
            </div>
        </div>

        {{-- Dona estados --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Pedidos por estado</h3>
            <div class="h-36 flex items-center justify-center">
                <canvas id="chartEstados"></canvas>
            </div>
            <div class="mt-3 space-y-1.5">
                @php $colores = ['#facc15','#60a5fa','#4ade80','#f87171']; @endphp
                @foreach(['Nuevos','En proceso','Completados','Cancelados'] as $i => $label)
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:{{ $colores[$i] }}"></span>
                        <span class="text-gray-600">{{ $label }}</span>
                    </div>
                    <span class="font-semibold text-gray-800">{{ $donaData[$i] }}</span>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- ── Fila inferior ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Top productos --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">🏆 Top productos vendidos</h3>
            @forelse($topProductos as $i => $p)
            <div class="flex items-center gap-2 mb-2.5">
                <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-[10px] font-bold flex items-center justify-center flex-shrink-0">
                    {{ $i + 1 }}
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-gray-700 truncate">{{ $p->name }}</p>
                    <div class="w-full bg-gray-100 rounded-full h-1 mt-0.5">
                        <div class="bg-indigo-500 h-1 rounded-full"
                             style="width:{{ $topProductos->first()->qty > 0 ? round(($p->qty / $topProductos->first()->qty) * 100) : 0 }}%">
                        </div>
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-xs font-bold text-gray-800">{{ $p->qty }} uds</p>
                    <p class="text-[10px] text-gray-400">S/ {{ number_format($p->total, 0) }}</p>
                </div>
            </div>
            @empty
            <p class="text-xs text-gray-400 text-center py-4">Sin ventas aún</p>
            @endforelse
        </div>

        {{-- Pedidos recientes --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Pedidos recientes</h3>
                <a href="{{ route('bixosales.pedidos') }}" class="text-xs text-blue-600 hover:underline">Ver todos →</a>
            </div>
            <div class="divide-y divide-gray-50 overflow-y-auto max-h-72">
                @forelse($pedidosRecientes as $o)
                <div class="flex items-center gap-3 px-4 py-2.5">
                    @if($o->sales_channel === 'whatsapp')
                        <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0" style="background:#25D366">
                            <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            </svg>
                        </div>
                    @else
                        <div class="w-7 h-7 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $o->client_name }}</p>
                        <p class="text-xs text-gray-400">{{ $o->created_at->diffForHumans() }} · {{ $o->items->count() }} item(s)</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-semibold text-gray-800">S/ {{ number_format($o->total, 2) }}</p>
                        @php
                            $sc = ['pending'=>'bg-yellow-100 text-yellow-700','process'=>'bg-blue-100 text-blue-700','done'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-700'];
                            $sl = ['pending'=>'Nuevo','process'=>'En proceso','done'=>'Listo','cancelled'=>'Cancelado'];
                        @endphp
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full {{ $sc[$o->status] ?? 'bg-gray-100 text-gray-500' }}">
                            {{ $sl[$o->status] ?? $o->status }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="px-4 py-8 text-center text-gray-400 text-sm">No hay pedidos aún</div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ── Accesos rápidos ── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <a href="{{ route('bixosales.pos') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white rounded-xl p-4 flex flex-col items-center gap-2 transition-colors text-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1-4l-3 3m0 0l-3-3m3 3V4"/>
            </svg>
            <span class="text-sm font-semibold">Nueva venta</span>
        </a>
        <a href="{{ route('bixosales.pedidos') }}"
           class="bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 rounded-xl p-4 flex flex-col items-center gap-2 transition-colors text-center">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span class="text-sm font-semibold">Ver pedidos</span>
        </a>
        <a href="{{ route('bixosales.pedidos', ['canal' => 'whatsapp']) }}"
           class="bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 rounded-xl p-4 flex flex-col items-center gap-2 transition-colors text-center">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="#25D366">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.117.554 4.103 1.523 5.824L0 24l6.335-1.502A11.955 11.955 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.885 0-3.652-.49-5.187-1.346l-.372-.214-3.762.892.924-3.67-.234-.388A9.954 9.954 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
            </svg>
            <span class="text-sm font-semibold">Pedidos WA</span>
        </a>
        <a href="{{ route('bixosales.cotizaciones') }}"
           class="bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 rounded-xl p-4 flex flex-col items-center gap-2 transition-colors text-center">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="text-sm font-semibold">Cotizaciones</span>
        </a>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // ── Gráfica de ventas 7 días ──────────────────────────────
    new Chart(document.getElementById('chartVentas'), {
        type: 'line',
        data: {
            labels: @json($labels7),
            datasets: [{
                label: 'Ventas S/',
                data: @json($data7),
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99,102,241,0.08)',
                borderWidth: 2,
                pointBackgroundColor: '#6366f1',
                pointRadius: 4,
                tension: 0.4,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f3f4f6' },
                    ticks: { font: { size: 11 }, callback: v => 'S/ ' + v.toLocaleString() }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } }
                }
            }
        }
    });

    // ── Dona de estados ───────────────────────────────────────
    new Chart(document.getElementById('chartEstados'), {
        type: 'doughnut',
        data: {
            labels: @json($donaLabels),
            datasets: [{
                data: @json($donaData),
                backgroundColor: ['#facc15','#60a5fa','#4ade80','#f87171'],
                borderWidth: 0,
                hoverOffset: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.raw } }
            },
            cutout: '65%',
        }
    });
</script>
@endpush
</x-portal-layout>
