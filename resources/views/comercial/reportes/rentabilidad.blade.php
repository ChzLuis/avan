<x-portal-layout layout="comercial" :project="$project" pageTitle="Rentabilidad">
<div class="flex flex-col h-full overflow-auto">

{{-- Header --}}
<div class="px-6 py-4 border-b border-gray-200 bg-white flex items-center justify-between flex-shrink-0">
    <div>
        <h1 class="text-lg font-semibold text-gray-900">Rentabilidad de Productos</h1>
        <p class="text-xs text-gray-500 mt-0.5">Ganancia neta, margen y costo por producto</p>
    </div>
    <a href="?desde={{ $desde }}&hasta={{ $hasta }}&export=csv"
       class="flex items-center gap-2 px-3 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Exportar CSV
    </a>
</div>

{{-- Filtros --}}
<form method="GET" class="px-6 py-3 bg-gray-50 border-b border-gray-200 flex items-center gap-3 flex-shrink-0">
    <div class="flex items-center gap-2">
        <label class="text-xs text-gray-500">Desde</label>
        <input type="date" name="desde" value="{{ $desde }}" class="text-sm border border-gray-200 rounded-lg px-2 py-1.5">
    </div>
    <div class="flex items-center gap-2">
        <label class="text-xs text-gray-500">Hasta</label>
        <input type="date" name="hasta" value="{{ $hasta }}" class="text-sm border border-gray-200 rounded-lg px-2 py-1.5">
    </div>
    <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Filtrar</button>
</form>

{{-- KPIs --}}
<div class="px-6 pt-4 grid grid-cols-2 lg:grid-cols-4 gap-4 flex-shrink-0">
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-xs text-gray-500">Ingresos brutos</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">S/ {{ number_format($totalIngresos, 2) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-xs text-gray-500">Costo total</p>
        <p class="text-2xl font-bold text-orange-500 mt-1">S/ {{ number_format($totalCosto, 2) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 border-l-4 border-l-green-500">
        <p class="text-xs text-gray-500">Ganancia neta</p>
        <p class="text-2xl font-bold {{ $totalGanancia >= 0 ? 'text-green-600' : 'text-red-500' }} mt-1">
            S/ {{ number_format($totalGanancia, 2) }}
        </p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-xs text-gray-500">Margen global</p>
        <p class="text-2xl font-bold {{ $margenGlobal >= 0 ? 'text-blue-600' : 'text-red-500' }} mt-1">
            {{ $margenGlobal }}%
        </p>
        @if($sinCosto > 0)
        <p class="text-xs text-amber-500 mt-1">{{ $sinCosto }} ítems sin costo cargado</p>
        @endif
    </div>
</div>

{{-- Gráfico por día --}}
@if($porDia->count() > 1)
<div class="px-6 pt-4 flex-shrink-0">
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-xs font-semibold text-gray-500 mb-3 uppercase tracking-wide">Ganancia neta por día</p>
        <div class="flex items-end gap-1" style="height:80px">
            @php
                $maxVal = max(1, $porDia->max(fn($d) => abs($d['ganancia'])));
            @endphp
            @foreach($porDia as $dia)
            @php
                $pct = round(abs($dia['ganancia']) / $maxVal * 100);
                $isNeg = $dia['ganancia'] < 0;
            @endphp
            <div class="flex flex-col items-center flex-1 min-w-0 group relative" title="{{ $dia['fecha'] }}: S/ {{ number_format($dia['ganancia'],2) }}">
                <div class="w-full rounded-t {{ $isNeg ? 'bg-red-400' : 'bg-green-400' }} transition-all"
                     style="height:{{ max(4,$pct) }}%"></div>
                <span class="text-[9px] text-gray-400 mt-0.5 truncate w-full text-center">{{ $dia['fecha'] }}</span>
                {{-- tooltip --}}
                <div class="absolute bottom-full mb-1 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-xs rounded px-2 py-1 whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none z-10">
                    S/ {{ number_format($dia['ganancia'],2) }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Tabla por producto --}}
<div class="p-6">
    @if($porProducto->isEmpty())
    <div class="text-center py-16 text-gray-400 text-sm">Sin datos en el período seleccionado. Asegúrate de que los productos tengan costo registrado.</div>
    @else

    @if($sinCosto > 0)
    <div class="mb-4 flex items-start gap-2 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-700">
        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <span>Algunos productos no tienen costo registrado — su ganancia aparece igual a los ingresos.
            <a href="{{ route('bixosales.catalog.products.index') }}" class="underline font-medium">Actualizar costos en Catálogo</a>
        </span>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Producto</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Vendidos</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Ingresos</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Costo unit.</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Costo total</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Ganancia neta</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Margen</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 w-32">Barra</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @php $maxGanancia = max(1, $porProducto->max(fn($p) => abs($p['ganancia']))); @endphp
                @foreach($porProducto as $prod)
                @php
                    $isNeg = $prod['ganancia'] < 0;
                    $barPct = round(abs($prod['ganancia']) / $maxGanancia * 100);
                    $sinCostoFlag = $prod['costo_unit'] == 0;
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-900">
                        {{ $prod['nombre'] }}
                        @if($sinCostoFlag)
                        <span class="ml-1 text-xs text-amber-500" title="Sin costo registrado">⚠</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ $prod['vendidos'] }}</td>
                    <td class="px-4 py-3 text-right text-gray-900 font-medium">S/ {{ number_format($prod['ingresos'], 2) }}</td>
                    <td class="px-4 py-3 text-right text-gray-500">
                        @if($sinCostoFlag)
                        <span class="text-amber-400 text-xs">—</span>
                        @else
                        S/ {{ number_format($prod['costo_unit'], 2) }}
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right text-orange-500">S/ {{ number_format($prod['costo_total'], 2) }}</td>
                    <td class="px-4 py-3 text-right font-bold {{ $isNeg ? 'text-red-500' : 'text-green-600' }}">
                        {{ $isNeg ? '-' : '+' }}S/ {{ number_format(abs($prod['ganancia']), 2) }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $isNeg ? 'bg-red-100 text-red-600' : ($prod['margen'] >= 30 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ $prod['margen'] }}%
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="h-2 rounded-full {{ $isNeg ? 'bg-red-400' : 'bg-green-400' }}"
                                 style="width:{{ $barPct }}%"></div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                <tr>
                    <td class="px-4 py-3 font-semibold text-gray-700 text-sm">TOTAL</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-700">{{ $porProducto->sum('vendidos') }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-900">S/ {{ number_format($totalIngresos, 2) }}</td>
                    <td class="px-4 py-3"></td>
                    <td class="px-4 py-3 text-right font-semibold text-orange-500">S/ {{ number_format($totalCosto, 2) }}</td>
                    <td class="px-4 py-3 text-right font-bold text-lg {{ $totalGanancia >= 0 ? 'text-green-600' : 'text-red-500' }}">
                        S/ {{ number_format($totalGanancia, 2) }}
                    </td>
                    <td class="px-4 py-3 text-right font-bold {{ $margenGlobal >= 0 ? 'text-blue-600' : 'text-red-500' }}">{{ $margenGlobal }}%</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>

</div>
</x-portal-layout>
