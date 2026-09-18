<x-facturacion-layout :project="$project" pageTitle="Clientes">

<div class="flex-1 overflow-y-auto p-6 space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Clientes</h1>
            <p class="text-sm text-gray-500 mt-0.5">Base de clientes del negocio</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/60">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Nombre</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Doc.</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Teléfono</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Email</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Pedidos</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($clients as $client)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $client->name }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">
                        @if($client->doc_type && $client->doc_number)
                            {{ $client->doc_type }}: {{ $client->doc_number }}
                        @else —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $client->phone ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs truncate max-w-[140px]">{{ $client->email ?? '—' }}</td>
                    <td class="px-4 py-3 text-center text-gray-700 font-semibold">{{ $client->orders_count }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-400">No hay clientes aún</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

</x-facturacion-layout>
