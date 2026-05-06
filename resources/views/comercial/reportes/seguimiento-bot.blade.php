<x-portal-layout layout="comercial" :project="$project" pageTitle="Reporte · Seguimiento Bot">
<div class="flex flex-col h-full overflow-hidden">

{{-- Header --}}
<div class="px-6 py-4 border-b border-gray-200 bg-white flex items-center justify-between flex-shrink-0 flex-wrap gap-3">
    <div>
        <h1 class="text-base font-semibold text-gray-800">Seguimiento de pedidos Bot</h1>
        <p class="text-xs text-gray-400 mt-0.5">Estado individual de cada participante</p>
    </div>
    <form method="GET" class="flex items-center gap-2 flex-wrap">
        <input type="date" name="desde" value="{{ $desde }}"
               class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:border-blue-400">
        <span class="text-xs text-gray-400">→</span>
        <input type="date" name="hasta" value="{{ $hasta }}"
               class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:border-blue-400">
        <select name="status" class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:border-blue-400">
            <option value="">Todos</option>
            <option value="pendiente" @selected($status==='pendiente')>Pendiente</option>
            <option value="pagado"    @selected($status==='pagado')>Pagado</option>
            <option value="enviado"   @selected($status==='enviado')>Enviado</option>
            <option value="cancelado" @selected($status==='cancelado')>Cancelado</option>
        </select>
        <input type="text" name="buscar" value="{{ $buscar }}" placeholder="Nombre, DNI o celular"
               class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:border-blue-400 w-44">
        <button type="submit" class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 transition">Buscar</button>
    </form>
</div>

{{-- Stat rápida --}}
<div class="px-6 py-2 bg-white border-b border-gray-100 flex items-center gap-6 text-xs text-gray-500 flex-shrink-0">
    <span>{{ $ventas->count() }} resultado(s)</span>
    <span class="text-yellow-600">{{ $ventas->where('status','pendiente')->count() }} pendientes</span>
    <span class="text-blue-600">{{ $ventas->where('status','pagado')->count() }} pagados</span>
    <span class="text-green-600">{{ $ventas->where('status','enviado')->count() }} enviados</span>
    @if($tiempoPromedio)
    <span class="text-purple-600">⏱ Tiempo promedio validación: {{ round($tiempoPromedio, 1) }}h</span>
    @endif
    <a href="{{ route('bixosales.reportes.ventas') }}" class="ml-auto text-blue-500 hover:underline">← Volver al resumen</a>
</div>

{{-- Tabla --}}
<div class="flex-1 overflow-auto p-6">
    @if($ventas->isEmpty())
    <div class="text-center py-20 text-gray-400">
        <p class="text-4xl mb-3">📭</p>
        <p class="text-sm">No hay pedidos con esos filtros</p>
    </div>
    @else
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-xs">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="text-left px-4 py-2.5 text-gray-500 font-medium">Participante</th>
                    <th class="text-left px-4 py-2.5 text-gray-500 font-medium">Plan</th>
                    <th class="text-left px-4 py-2.5 text-gray-500 font-medium">Monto</th>
                    <th class="text-left px-4 py-2.5 text-gray-500 font-medium">Tickets</th>
                    <th class="text-left px-4 py-2.5 text-gray-500 font-medium">Fecha</th>
                    <th class="text-left px-4 py-2.5 text-gray-500 font-medium">Estado</th>
                    <th class="text-left px-4 py-2.5 text-gray-500 font-medium">Comprobante</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($ventas as $v)
                @php
                $sc = match($v->status) {
                    'pendiente' => 'bg-yellow-100 text-yellow-700',
                    'pagado'    => 'bg-blue-100 text-blue-700',
                    'enviado'   => 'bg-green-100 text-green-700',
                    'cancelado' => 'bg-red-100 text-red-700',
                    default     => 'bg-gray-100 text-gray-500',
                };
                $sl = match($v->status) {
                    'pendiente' => 'Pendiente', 'pagado' => 'Pagado',
                    'enviado'   => 'Enviado ✓', 'cancelado' => 'Cancelado', default => $v->status,
                };
                @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-800">{{ $v->nombre ?? '—' }}</p>
                        <p class="text-gray-400">DNI: {{ $v->dni ?? '—' }} · {{ $v->wa_number }}</p>
                        @if($v->ciudad)<p class="text-gray-400">📍 {{ $v->ciudad }}</p>@endif
                    </td>
                    <td class="px-4 py-3 text-purple-700 font-medium">{{ $v->plan_nombre }}</td>
                    <td class="px-4 py-3 font-bold text-gray-800">S/ {{ number_format($v->monto, 2) }}</td>
                    <td class="px-4 py-3 text-gray-600">
                        {{ $v->tickets }}
                        @if($v->ticket_numbers)
                        <br><span class="text-indigo-500">#{{ implode(', #', array_map(fn($n) => str_pad($n,5,'0',STR_PAD_LEFT), $v->ticket_numbers)) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-400">{{ $v->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full font-medium {{ $sc }}">{{ $sl }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($v->payment_proof)
                        <a href="{{ asset($v->payment_proof) }}" target="_blank"
                           class="text-indigo-500 hover:underline">🧾 Ver</a>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
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
