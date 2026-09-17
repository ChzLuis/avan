<x-app-layout>
<x-slot name="slot">

@php
    $cur = $project->setting('currency_symbol') ?: $project->setting('currency', 'S/');
    $fmt = fn ($n) => $cur . ' ' . number_format((float) $n, 2);
    $stock = (int) $product->stock;
    $min   = (int) ($product->stock_min ?? 0);
@endphp

<div class="flex flex-col h-full w-full overflow-hidden" style="background:#F8FAFC">

    {{-- ── Cabecera ── --}}
    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
        <a href="{{ route('inventory.index') }}" class="text-xs text-gray-400 hover:text-indigo-600 font-semibold flex items-center gap-1 mb-1.5">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
            Inventario
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-lg font-semibold text-gray-800 truncate">{{ $product->name }}</h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Kardex · @if($product->sku)<span class="font-mono">{{ $product->sku }}</span> · @endif{{ $product->category->name ?? 'Sin categoría' }}
                </p>
            </div>
            <a href="{{ route('products.index') }}?edit={{ $product->id }}"
               class="text-xs px-3 py-2 border border-gray-200 rounded-lg text-gray-600 hover:border-indigo-400 hover:text-indigo-600 font-semibold bg-white text-center flex-shrink-0">Editar producto</a>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5">

        {{-- ── Cifras ── --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white rounded-xl border p-4 {{ $stock <= 0 ? 'border-red-200' : ($min > 0 && $stock <= $min ? 'border-amber-200' : 'border-gray-100') }}">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Stock actual</div>
                <div class="text-xl font-black mt-1 {{ $stock <= 0 ? 'text-red-600' : ($min > 0 && $stock <= $min ? 'text-amber-600' : 'text-gray-900') }}">{{ $stock }}</div>
                <div class="text-[11px] text-gray-400 mt-0.5">
                    @if($stock <= 0) Agotado
                    @elseif($min > 0 && $stock <= $min) Bajo el mínimo ({{ $min }})
                    @elseif($min > 0) Mínimo {{ $min }}
                    @else Sin mínimo definido @endif
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Total entradas</div>
                <div class="text-xl font-black text-emerald-600 mt-1">+{{ (int) $entradas }}</div>
                <div class="text-[11px] text-gray-400 mt-0.5">unidades registradas</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Total salidas</div>
                <div class="text-xl font-black text-red-500 mt-1">−{{ (int) $salidas }}</div>
                <div class="text-[11px] text-gray-400 mt-0.5">unidades registradas</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Valorizado</div>
                <div class="text-xl font-black mt-1 {{ $product->cost !== null ? 'text-gray-900' : 'text-gray-300' }}">
                    {{ $product->cost !== null ? $fmt($stock * (float) $product->cost) : '—' }}
                </div>
                <div class="text-[11px] mt-0.5 {{ $product->cost !== null ? 'text-gray-400' : 'text-amber-600 font-semibold' }}">
                    {{ $product->cost !== null ? 'costo unit. ' . $fmt($product->cost) : 'falta cargar el costo' }}
                </div>
            </div>
        </div>

        {{-- ── Movimientos ── --}}
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-800">Movimientos</h2>
                <span class="text-[11px] text-gray-400">{{ $movimientos->count() }} registro(s), del más reciente al más antiguo</span>
            </div>

            {{-- Tabla (escritorio) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm min-w-[720px]">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-[10px] uppercase tracking-wide">
                            <th class="text-left  font-bold px-4 py-2.5">Fecha</th>
                            <th class="text-left  font-bold px-3 py-2.5">Motivo</th>
                            <th class="text-right font-bold px-3 py-2.5">Cantidad</th>
                            <th class="text-right font-bold px-3 py-2.5">Saldo</th>
                            <th class="text-right font-bold px-3 py-2.5">Costo unit.</th>
                            <th class="text-left  font-bold px-4 py-2.5">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movimientos as $m)
                        <tr class="border-t border-gray-50 hover:bg-gray-50/60">
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                <div class="text-gray-800">{{ $m->created_at->format('d/m/Y') }}</div>
                                <div class="text-[11px] text-gray-400">{{ $m->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-3 py-2.5">
                                <span class="inline-block px-2 py-0.5 rounded-md text-[11px] font-semibold
                                             {{ $m->quantity > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' }}">
                                    {{ $m->motivo }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-right font-bold {{ $m->quantity > 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                {{ $m->quantity > 0 ? '+' : '−' }}{{ abs($m->quantity) }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-semibold text-gray-800">{{ $m->balance_after !== null ? $m->balance_after : '—' }}</td>
                            <td class="px-3 py-2.5 text-right text-gray-600">{{ $m->unit_cost !== null ? $fmt($m->unit_cost) : '—' }}</td>
                            <td class="px-4 py-2.5">
                                <div class="text-gray-600 text-[13px]">{{ $m->notes ?: '—' }}</div>
                                <div class="text-[11px] text-gray-400">{{ $m->user->name ?? 'Sistema' }}</div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">
                            Este producto todavía no tiene movimientos.<br>
                            <span class="text-xs">Se registrarán solos con cada venta, o puedes anotar una compra desde Inventario.</span>
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Tarjetas (móvil) --}}
            <div class="md:hidden divide-y divide-gray-50">
                @forelse($movimientos as $m)
                <div class="px-4 py-3 flex items-start gap-3">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 text-sm font-black
                                 {{ $m->quantity > 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500' }}">
                        {{ $m->quantity > 0 ? '+' : '−' }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="text-sm font-semibold text-gray-800">{{ $m->motivo }}</span>
                            <span class="text-sm font-bold flex-shrink-0 {{ $m->quantity > 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                {{ $m->quantity > 0 ? '+' : '−' }}{{ abs($m->quantity) }}
                            </span>
                        </div>
                        <div class="text-[11px] text-gray-500 mt-0.5">
                            {{ $m->created_at->format('d/m/Y H:i') }}
                            @if($m->balance_after !== null) · saldo {{ $m->balance_after }} @endif
                            @if($m->unit_cost !== null) · {{ $fmt($m->unit_cost) }} @endif
                        </div>
                        @if($m->notes)
                        <div class="text-[12px] text-gray-600 mt-1">{{ $m->notes }}</div>
                        @endif
                        <div class="text-[10px] text-gray-300 mt-0.5">{{ $m->user->name ?? 'Sistema' }}</div>
                    </div>
                </div>
                @empty
                <div class="px-4 py-10 text-center text-gray-400 text-sm">
                    Este producto todavía no tiene movimientos.
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

</x-slot>
</x-app-layout>
