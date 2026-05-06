<x-portal-layout layout="comercial" :project="$project" pageTitle="Top Productos">
<div class="flex flex-col h-full overflow-auto">

<div class="px-6 py-4 border-b border-gray-200 bg-white flex items-center justify-between flex-shrink-0">
    <div>
        <h1 class="text-lg font-semibold text-gray-900">Top Productos Vendidos</h1>
        <p class="text-xs text-gray-500 mt-0.5">Ranking por ingresos en el período</p>
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

<div class="p-6">
    @if($top->isEmpty())
    <div class="text-center py-16 text-gray-400 text-sm">Sin datos en el período seleccionado.</div>
    @else
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">#</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Producto</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Unidades</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Ingresos</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">% del total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @php $totalIngresos = $top->sum('ingresos'); @endphp
                @foreach($top as $i => $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-400 font-mono">{{ $i + 1 }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $row->name }}</td>
                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($row->cantidad) }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-900">S/ {{ number_format($row->ingresos, 2) }}</td>
                    <td class="px-4 py-3 text-right">
                        @php $pct = $totalIngresos > 0 ? round($row->ingresos / $totalIngresos * 100, 1) : 0; @endphp
                        <div class="flex items-center justify-end gap-2">
                            <div class="w-20 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full" style="width:{{ $pct }}%"></div>
                            </div>
                            <span class="text-xs text-gray-500 w-10 text-right">{{ $pct }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 border-t border-gray-200">
                    <td colspan="2" class="px-4 py-3 text-xs font-semibold text-gray-600">TOTAL</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ number_format($top->sum('cantidad')) }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-900">S/ {{ number_format($totalIngresos, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>
</div>
</x-portal-layout>
