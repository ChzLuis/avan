<x-facturacion-layout :project="$project" pageTitle="Pedidos">

<div class="flex-1 overflow-y-auto p-6 space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Pedidos</h1>
            <p class="text-sm text-gray-500 mt-0.5">Gestión de órdenes del negocio</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/60">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">#</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Cliente</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Canal</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($orders as $order)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-5 py-3 text-gray-500 text-xs font-mono">#{{ $order->id }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $order->client_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $order->sales_channel ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-800">S/ {{ number_format($order->total, 2) }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-medium
                            {{ $order->status === 'done' ? 'bg-emerald-50 text-emerald-600' :
                               ($order->status === 'cancelled' ? 'bg-red-50 text-red-600' :
                               ($order->status === 'process' ? 'bg-blue-50 text-blue-600' : 'bg-amber-50 text-amber-600')) }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-400">No hay pedidos aún</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

</x-facturacion-layout>
