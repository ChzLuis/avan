<x-portal-layout layout="comercial" :project="$project" pageTitle="Reporte de Ventas">
<div class="flex flex-col h-full overflow-auto">

<div class="px-6 py-4 border-b border-gray-200 bg-white flex items-center justify-between flex-shrink-0">
    <div>
        <h1 class="text-lg font-semibold text-gray-900">Reporte de Ventas</h1>
        <p class="text-xs text-gray-500 mt-0.5">Todos los pedidos con filtros por período, canal y vendedor</p>
    </div>
    <a href="?desde={{ $desde }}&hasta={{ $hasta }}&canal={{ $canal }}&vendedor={{ $vendedor }}&estado={{ $estado }}&export=csv"
       class="flex items-center gap-2 px-3 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Exportar CSV
    </a>
</div>

{{-- Filtros --}}
<form method="GET" class="px-6 py-3 bg-gray-50 border-b border-gray-200 flex flex-wrap items-center gap-3 flex-shrink-0">
    <div class="flex items-center gap-2">
        <label class="text-xs text-gray-500">Desde</label>
        <input type="date" name="desde" value="{{ $desde }}" class="text-sm border border-gray-200 rounded-lg px-2 py-1.5">
    </div>
    <div class="flex items-center gap-2">
        <label class="text-xs text-gray-500">Hasta</label>
        <input type="date" name="hasta" value="{{ $hasta }}" class="text-sm border border-gray-200 rounded-lg px-2 py-1.5">
    </div>
    <select name="canal" class="text-sm border border-gray-200 rounded-lg px-2 py-1.5">
        <option value="">Todos los canales</option>
        <option value="web" @selected($canal==='web')>Web</option>
        <option value="pos" @selected($canal==='pos')>POS</option>
        <option value="whatsapp" @selected($canal==='whatsapp')>WhatsApp</option>
    </select>
    <select name="estado" class="text-sm border border-gray-200 rounded-lg px-2 py-1.5">
        <option value="">Todos los estados</option>
        <option value="pending" @selected($estado==='pending')>Pendiente</option>
        <option value="process" @selected($estado==='process')>En proceso</option>
        <option value="done" @selected($estado==='done')>Completado</option>
        <option value="cancelled" @selected($estado==='cancelled')>Cancelado</option>
    </select>
    @if($empleados->count())
    <select name="vendedor" class="text-sm border border-gray-200 rounded-lg px-2 py-1.5">
        <option value="">Todos los vendedores</option>
        @foreach($empleados as $emp)
        <option value="{{ $emp->id }}" @selected((string)$vendedor===(string)$emp->id)>{{ $emp->name }}</option>
        @endforeach
    </select>
    @endif
    <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Filtrar</button>
</form>

{{-- KPIs --}}
<div class="px-6 pt-4 grid grid-cols-3 gap-4 flex-shrink-0">
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-xs text-gray-500">Total pedidos</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($totales['count']) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-xs text-gray-500">Ingresos</p>
        <p class="text-2xl font-bold text-green-600 mt-1">S/ {{ number_format($totales['ingresos'], 2) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-xs text-gray-500">Cancelados</p>
        <p class="text-2xl font-bold text-red-500 mt-1">{{ number_format($totales['cancelados']) }}</p>
    </div>
</div>

{{-- Tabla --}}
<div class="p-6">
    @if($ordenes->isEmpty())
    <div class="text-center py-16 text-gray-400 text-sm">Sin pedidos en el período seleccionado.</div>
    @else
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Fecha</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Cliente</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Canal</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Total</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($ordenes as $orden)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $orden->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $orden->client_name }}</td>
                    <td class="px-4 py-3 text-gray-500 capitalize">{{ $orden->sales_channel ?? '-' }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-900">S/ {{ number_format($orden->total, 2) }}</td>
                    <td class="px-4 py-3 text-center">
                        @php $colors = ['pending'=>'bg-yellow-100 text-yellow-700','process'=>'bg-blue-100 text-blue-700','done'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-600']; @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$orden->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($orden->status) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
</div>
</x-portal-layout>
