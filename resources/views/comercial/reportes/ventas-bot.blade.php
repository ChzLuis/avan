<x-portal-layout layout="comercial" :project="$project" pageTitle="Reporte · Ventas Bot">
<div class="flex flex-col h-full overflow-hidden">

{{-- Header --}}
<div class="px-6 py-4 border-b border-gray-200 bg-white flex items-center justify-between flex-shrink-0">
    <div>
        <h1 class="text-base font-semibold text-gray-800">Ventas por WhatsApp Bot</h1>
        <p class="text-xs text-gray-400 mt-0.5">Resumen de ingresos y pedidos del bot</p>
    </div>
    {{-- Filtro fechas --}}
    <form method="GET" class="flex items-center gap-2">
        <input type="date" name="desde" value="{{ $desde }}"
               class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:border-blue-400">
        <span class="text-xs text-gray-400">→</span>
        <input type="date" name="hasta" value="{{ $hasta }}"
               class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:border-blue-400">
        <button type="submit" class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 transition">Filtrar</button>
    </form>
</div>

<div class="flex-1 overflow-y-auto p-6 bg-gray-50/30 space-y-6">

    {{-- KPIs --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $totales->total ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">Total pedidos</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-green-600">S/ {{ number_format($totales->ingresos ?? 0, 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">Ingresos</p>
        </div>
        <div class="bg-white rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-center">
            <p class="text-2xl font-bold text-yellow-600">{{ $totales->pendientes ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">Pendientes</p>
        </div>
        <div class="bg-white rounded-xl border border-blue-200 bg-blue-50 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $totales->pagados ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">Pagados</p>
        </div>
        <div class="bg-white rounded-xl border border-green-200 bg-green-50 p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ $totales->enviados ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">Enviados</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Por plan --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700">Ventas por plan</h2>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($porPlan as $plan)
                @php
                $pct = $totales->total > 0 ? round($plan->total / $totales->total * 100) : 0;
                @endphp
                <div class="px-4 py-3">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-sm font-medium text-gray-800">{{ $plan->plan_nombre ?? '—' }}</span>
                        <span class="text-sm font-bold text-green-600">S/ {{ number_format($plan->ingresos, 2) }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 bg-gray-100 rounded-full h-1.5">
                            <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="text-xs text-gray-400 w-16 text-right">
                            {{ $plan->total }} pedidos · {{ $pct }}%
                        </span>
                    </div>
                    <div class="flex gap-3 mt-1.5 text-xs text-gray-400">
                        <span class="text-yellow-600">{{ $plan->pendientes }} pendientes</span>
                        <span class="text-blue-600">{{ $plan->pagados }} pagados</span>
                        <span class="text-green-600">{{ $plan->enviados }} enviados</span>
                    </div>
                </div>
                @empty
                <div class="px-4 py-8 text-center text-gray-400 text-sm">Sin datos en este período</div>
                @endforelse
            </div>
        </div>

        {{-- Por día --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700">Pedidos por día</h2>
            </div>
            @if($porDia->isNotEmpty())
            @php $maxDia = $porDia->max('total'); @endphp
            <div class="px-4 py-3 space-y-2">
                @foreach($porDia as $dia)
                @php $pctDia = $maxDia > 0 ? round($dia->total / $maxDia * 100) : 0; @endphp
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-500 w-20 flex-shrink-0">{{ \Carbon\Carbon::parse($dia->fecha)->format('d/m/Y') }}</span>
                    <div class="flex-1 bg-gray-100 rounded-full h-2">
                        <div class="bg-purple-500 h-2 rounded-full" style="width: {{ $pctDia }}%"></div>
                    </div>
                    <span class="text-xs font-medium text-gray-700 w-6 text-right">{{ $dia->total }}</span>
                    <span class="text-xs text-green-600 w-20 text-right">S/ {{ number_format($dia->ingresos, 2) }}</span>
                </div>
                @endforeach
            </div>
            @else
            <div class="px-4 py-8 text-center text-gray-400 text-sm">Sin datos en este período</div>
            @endif
        </div>

    </div>
</div>
</div>
</x-portal-layout>
