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
<div class="px-6 pt-4 grid grid-cols-4 gap-4 flex-shrink-0">
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-xs text-gray-500">Total pedidos</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($totales['count']) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <p class="text-xs text-gray-500">Ingresos</p>
        <p class="text-2xl font-bold text-green-600 mt-1">S/ {{ number_format($totales['ingresos'], 2) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 border-l-4 border-l-green-500">
        <p class="text-xs text-gray-500 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            WhatsApp
        </p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($totales['whatsapp']) }}</p>
        <p class="text-xs text-gray-400 mt-0.5">S/ {{ number_format($totales['ingresos_wa'], 2) }}</p>
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
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Productos</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Total</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($ordenes as $orden)
                @php
                    $colors = ['pending'=>'bg-yellow-100 text-yellow-700','process'=>'bg-blue-100 text-blue-700','done'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-600'];
                    $waStatusLabels = ['pending'=>'Pend. pago','pago_recibido'=>'Pago recibido','entregado'=>'Entregado','problema'=>'Problema'];
                    $isWa = $orden->sales_channel === 'whatsapp';
                @endphp
                <tr class="hover:bg-gray-50 {{ $isWa ? 'bg-green-50/30' : '' }}">
                    <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ $orden->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900 text-sm">{{ $orden->client_name }}</p>
                        @if($isWa && $orden->wa_number)
                        <p class="text-xs text-green-600 mt-0.5 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            {{ $orden->wa_number }}
                        </p>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($isWa)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            WhatsApp
                        </span>
                        @if($orden->wa_status)
                        <p class="text-xs text-gray-400 mt-0.5">{{ $waStatusLabels[$orden->wa_status] ?? $orden->wa_status }}</p>
                        @endif
                        @elseif($orden->sales_channel === 'pos')
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">POS</span>
                        @elseif($orden->sales_channel === 'web')
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Web</span>
                        @else
                        <span class="text-xs text-gray-400">{{ $orden->sales_channel ?? '-' }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">
                        @if($orden->items && $orden->items->count())
                        <ul class="space-y-0.5">
                            @foreach($orden->items->take(3) as $item)
                            <li>{{ $item->quantity }}× {{ Str::limit($item->name, 28) }}</li>
                            @endforeach
                            @if($orden->items->count() > 3)
                            <li class="text-gray-400">+{{ $orden->items->count() - 3 }} más</li>
                            @endif
                        </ul>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-900 whitespace-nowrap">S/ {{ number_format($orden->total, 2) }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$orden->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ['pending'=>'Pendiente','process'=>'En proceso','done'=>'Completado','cancelled'=>'Cancelado'][$orden->status] ?? ucfirst($orden->status) }}
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
