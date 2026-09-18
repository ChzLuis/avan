<x-facturacion-layout :project="$project" pageTitle="Dashboard">

<div class="flex-1 overflow-y-auto p-6 space-y-6">

    {{-- Bienvenida --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ now()->isoFormat('dddd, D [de] MMMM YYYY') }}</p>
        </div>
    </div>

    {{-- Métricas --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Ventas hoy</p>
                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">S/ {{ number_format($ventasHoy, 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $pedidosHoy }} pedido{{ $pedidosHoy !== 1 ? 's' : '' }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pedidos hoy</p>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $pedidosHoy }}</p>
            <p class="text-xs text-gray-400 mt-1">Del día de hoy</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Facturas del mes</p>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $facturasMes }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ now()->isoFormat('MMMM YYYY') }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total facturado</p>
                <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">S/ {{ number_format($totalFacturado, 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">Mes actual</p>
        </div>

    </div>

    {{-- Accesos rápidos --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <a href="{{ route('facturacion.pos', $project->slug) }}"
           class="bg-blue-600 hover:bg-blue-700 text-white rounded-xl p-4 flex flex-col items-center gap-2 transition-colors text-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1-4l-3 3m0 0l-3-3m3 3V4"/>
            </svg>
            <span class="text-sm font-semibold">Abrir POS</span>
        </a>
        <a href="{{ route('facturacion.facturas', $project->slug) }}"
           class="bg-white hover:bg-gray-50 border border-gray-200 rounded-xl p-4 flex flex-col items-center gap-2 transition-colors text-center">
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
            </svg>
            <span class="text-sm font-semibold text-gray-700">Nueva Factura</span>
        </a>
        <a href="{{ route('facturacion.pedidos', $project->slug) }}"
           class="bg-white hover:bg-gray-50 border border-gray-200 rounded-xl p-4 flex flex-col items-center gap-2 transition-colors text-center">
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span class="text-sm font-semibold text-gray-700">Ver Pedidos</span>
        </a>
        <a href="{{ route('facturacion.cotizaciones', $project->slug) }}"
           class="bg-white hover:bg-gray-50 border border-gray-200 rounded-xl p-4 flex flex-col items-center gap-2 transition-colors text-center">
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="text-sm font-semibold text-gray-700">Cotizaciones</span>
        </a>
    </div>

    {{-- Últimas facturas y pedidos --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Últimas facturas --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-800">Últimas facturas</h2>
                <a href="{{ route('facturacion.facturas', $project->slug) }}" class="text-xs text-blue-600 hover:underline">Ver todas</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($ultimasFacturas as $inv)
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="w-8 h-8 rounded-lg {{ $inv->type === 'factura' ? 'bg-blue-50' : 'bg-emerald-50' }} flex items-center justify-center flex-shrink-0">
                        <span class="text-[10px] font-bold {{ $inv->type === 'factura' ? 'text-blue-600' : 'text-emerald-600' }}">
                            {{ $inv->type === 'factura' ? 'F' : 'B' }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-800 truncate">{{ $inv->client_name }}</p>
                        <p class="text-[11px] text-gray-400">{{ $inv->numero }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-xs font-bold text-gray-800">S/ {{ number_format($inv->total, 2) }}</p>
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full
                            {{ $inv->status === 'issued' ? 'bg-blue-50 text-blue-600' :
                               ($inv->status === 'cancelled' ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-500') }}">
                            {{ $inv->getStatusLabel() }}
                        </span>
                    </div>
                </div>
                @empty
                <p class="px-5 py-6 text-sm text-gray-400 text-center">Sin facturas aún</p>
                @endforelse
            </div>
        </div>

        {{-- Últimos pedidos --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-800">Últimos pedidos</h2>
                <a href="{{ route('facturacion.pedidos', $project->slug) }}" class="text-xs text-blue-600 hover:underline">Ver todos</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($ultimosPedidos as $order)
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-800 truncate">{{ $order->client_name ?? 'Sin nombre' }}</p>
                        <p class="text-[11px] text-gray-400">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-xs font-bold text-gray-800">S/ {{ number_format($order->total, 2) }}</p>
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500">
                            {{ $order->status }}
                        </span>
                    </div>
                </div>
                @empty
                <p class="px-5 py-6 text-sm text-gray-400 text-center">Sin pedidos aún</p>
                @endforelse
            </div>
        </div>

    </div>

</div>

</x-facturacion-layout>
