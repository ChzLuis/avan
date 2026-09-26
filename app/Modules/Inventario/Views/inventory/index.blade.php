<x-app-layout>
<x-slot name="slot">

@php
    $cur = $project->setting('currency_symbol') ?: $project->setting('currency', 'S/');
    $fmt = fn ($n) => $cur . ' ' . number_format((float) $n, 2);
@endphp

<div class="flex flex-col h-full w-full overflow-hidden" style="background:#F8FAFC"
     x-data="{ movModal: false, mov: { product_id: '', reason: 'compra', quantity: 1, unit_cost: '', notes: '' }, buscarProd: '' }">

    {{-- ── Cabecera ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 sm:px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
        <div>
            <h1 class="text-lg font-semibold text-gray-800">Inventario</h1>
            <p class="text-xs text-gray-500 mt-0.5">Existencias, valorización y movimientos (Kardex)</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('products.index') }}" class="text-xs px-3 py-2 border border-gray-200 rounded-lg text-gray-600 hover:border-indigo-400 hover:text-indigo-600 font-semibold bg-white">Ir al catálogo</a>
            <a href="{{ route('inventory.etiquetas') }}" class="text-xs px-3 py-2 border border-gray-200 rounded-lg text-gray-600 hover:border-emerald-400 hover:text-emerald-700 font-semibold bg-white flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                Etiquetas QR
            </a>
            <a href="{{ route('inventory.tomas') }}" class="text-xs px-3 py-2 border border-gray-200 rounded-lg text-gray-600 hover:border-indigo-400 hover:text-indigo-600 font-semibold bg-white flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                Toma de inventario
            </a>
            <a href="{{ route('inventory.ubicaciones') }}" class="text-xs px-3 py-2 border border-gray-200 rounded-lg text-gray-600 hover:border-indigo-400 hover:text-indigo-600 font-semibold bg-white">Ubicaciones</a>
            <a href="{{ route('inventory.activos') }}" class="text-xs px-3 py-2 border border-gray-200 rounded-lg text-gray-600 hover:border-indigo-400 hover:text-indigo-600 font-semibold bg-white">Activos fijos</a>
            <a href="{{ route('inventory.bultos') }}" class="text-xs px-3 py-2 border border-gray-200 rounded-lg text-gray-600 hover:border-indigo-400 hover:text-indigo-600 font-semibold bg-white">Bultos</a>
            <a href="{{ route('inventory.traslados') }}" class="text-xs px-3 py-2 border border-gray-200 rounded-lg text-gray-600 hover:border-indigo-400 hover:text-indigo-600 font-semibold bg-white">Traslados</a>
            <button @click="movModal = true" class="text-xs px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Registrar movimiento
            </button>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5">

        @if(session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        {{-- ── Resumen ── --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Valor del inventario</div>
                <div class="text-xl font-black text-gray-900 mt-1">{{ $fmt($resumen['valor_costo']) }}</div>
                @if($resumen['sin_costo'] > 0)
                <div class="text-[11px] text-amber-600 font-semibold mt-0.5">
                    Parcial: {{ $resumen['sin_costo'] }} de {{ $resumen['referencias'] }} sin costo cargado
                </div>
                @else
                <div class="text-[11px] text-gray-400 mt-0.5">a precio de costo</div>
                @endif
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Valor de venta</div>
                <div class="text-xl font-black text-emerald-600 mt-1">{{ $fmt($resumen['valor_venta']) }}</div>
                <div class="text-[11px] text-gray-400 mt-0.5">si se vendiera todo</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Unidades</div>
                <div class="text-xl font-black text-gray-900 mt-1">{{ number_format($resumen['unidades']) }}</div>
                <div class="text-[11px] text-gray-400 mt-0.5">{{ $resumen['referencias'] }} producto(s) con stock</div>
            </div>
            <div class="bg-white rounded-xl border p-4 {{ ($resumen['agotados'] + $resumen['bajos']) > 0 ? 'border-amber-200 bg-amber-50/50' : 'border-gray-100' }}">
                <div class="text-[10px] font-bold uppercase tracking-wide {{ ($resumen['agotados'] + $resumen['bajos']) > 0 ? 'text-amber-700/70' : 'text-gray-400' }}">Requieren atención</div>
                <div class="text-xl font-black mt-1 {{ ($resumen['agotados'] + $resumen['bajos']) > 0 ? 'text-amber-700' : 'text-gray-900' }}">{{ $resumen['agotados'] + $resumen['bajos'] }}</div>
                <div class="text-[11px] mt-0.5 {{ ($resumen['agotados'] + $resumen['bajos']) > 0 ? 'text-amber-700/70' : 'text-gray-400' }}">{{ $resumen['agotados'] }} agotados · {{ $resumen['bajos'] }} bajo mínimo</div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

            {{-- ── Existencias ── --}}
            <div class="xl:col-span-2 bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-3 flex-wrap">
                    <h2 class="text-sm font-bold text-gray-800">Existencias</h2>
                    <form method="GET" class="flex items-center gap-2">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar producto o SKU..."
                               class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 outline-none focus:border-indigo-400" style="width:190px">
                        <select name="estado" onchange="this.form.submit()" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 outline-none">
                            <option value="">Todos</option>
                            <option value="bajo"    {{ request('estado') === 'bajo' ? 'selected' : '' }}>Bajo mínimo</option>
                            <option value="agotado" {{ request('estado') === 'agotado' ? 'selected' : '' }}>Agotados</option>
                        </select>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[620px]">
                        @if(($situacion['comprometido'] ?? 0) > 0 || ($situacion['sobreventa'] ?? 0) > 0)
                        <caption class="caption-top text-left px-4 py-2 text-xs text-gray-500 bg-blue-50/50 border-b border-blue-100">
                            @if($situacion['comprometido'] > 0)
                            <b class="text-blue-800">{{ $situacion['comprometido'] }}
                            {{ $situacion['comprometido'] === 1 ? 'unidad' : 'unidades' }}</b> en
                            {{ $situacion['referencias_comprometidas'] }}
                            {{ $situacion['referencias_comprometidas'] === 1 ? 'producto' : 'productos' }}
                            {{ $situacion['comprometido'] === 1 ? 'está vendida y aún en el almacén' : 'están vendidas y aún en el almacén' }}:
                            al contar el estante {{ $situacion['comprometido'] === 1 ? 'la vas' : 'las vas' }} a encontrar.
                            @endif
                            @if($situacion['sobreventa'] > 0)
                            <span class="text-red-700 font-semibold">{{ $situacion['sobreventa'] }} producto(s) con stock negativo: se vendió más de lo que había.</span>
                            @endif
                        </caption>
                        @endif
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-[10px] uppercase tracking-wide">
                                <th class="text-left font-bold px-4 py-2.5">Producto</th>
                                <th class="text-right font-bold px-3 py-2.5">Stock</th>
                                <th class="text-right font-bold px-3 py-2.5" title="Vendido y todavía en el almacén, esperando despacho">Comprometido</th>
                                <th class="text-right font-bold px-3 py-2.5" title="Lo que deberías encontrar al contar el estante">En estante</th>
                                <th class="text-right font-bold px-3 py-2.5">Mínimo</th>
                                <th class="text-right font-bold px-3 py-2.5">Costo</th>
                                <th class="text-right font-bold px-4 py-2.5">Valorizado</th>
                                <th class="px-3 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($productos as $p)
                            @php
                                $stock = (int) $p->stock;
                                $min   = (int) ($p->stock_min ?? 0);
                                $estado = $stock <= 0 ? 'agotado' : ($min > 0 && $stock <= $min ? 'bajo' : 'ok');
                                // La venta descuenta al crear el pedido, no al despacharlo:
                                // lo comprometido sigue fisicamente en el estante.
                                $comp = (int) ($comprometidos[$p->id] ?? 0);
                                $enEstante = $stock + $comp;
                            @endphp
                            <tr class="border-t border-gray-50 hover:bg-gray-50/60">
                                <td class="px-4 py-2.5">
                                    <div class="font-medium text-gray-800">{{ $p->name }}</div>
                                    <div class="text-[11px] text-gray-400">
                                        @if($p->sku)<span class="font-mono">{{ $p->sku }}</span> · @endif{{ $p->category->name ?? 'Sin categoría' }}
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 text-right">
                                    <span class="font-bold {{ $estado === 'agotado' ? 'text-red-600' : ($estado === 'bajo' ? 'text-amber-600' : 'text-gray-800') }}">{{ $stock }}</span>
                                    @if($estado === 'agotado')
                                        <div class="text-[10px] font-semibold text-red-500">Agotado</div>
                                    @elseif($estado === 'bajo')
                                        <div class="text-[10px] font-semibold text-amber-500">Bajo mínimo</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-right">
                                    @if($comp > 0)
                                    <span class="font-semibold text-blue-700" style="font-variant-numeric:tabular-nums">{{ $comp }}</span>
                                    <div class="text-[10px] text-blue-400">por despachar</div>
                                    @else
                                    <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-right">
                                    <span class="{{ $comp > 0 ? 'font-bold text-gray-800' : 'text-gray-400' }}"
                                          style="font-variant-numeric:tabular-nums">{{ $enEstante }}</span>
                                </td>
                                <td class="px-3 py-2.5 text-right text-gray-400">{{ $min ?: '—' }}</td>
                                <td class="px-3 py-2.5 text-right text-gray-600">{{ $p->cost !== null ? $fmt($p->cost) : '—' }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold {{ $p->cost !== null ? 'text-gray-800' : 'text-gray-300' }}">
                                    {{ $p->cost !== null ? $fmt($stock * (float) $p->cost) : '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-right whitespace-nowrap">
                                    <button type="button" @click="mov.product_id = '{{ $p->id }}'; mov.unit_cost = '{{ $p->cost }}'; movModal = true"
                                            class="text-[11px] font-semibold text-gray-500 hover:text-indigo-600 px-2">Mover</button>
                                    <a href="{{ route('inventory.kardex', $p->id) }}"
                                       class="text-[11px] font-semibold text-indigo-600 hover:underline px-2">Kardex</a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">
                                @if(request('q') || request('estado'))
                                    Ningún producto coincide con la búsqueda.
                                @else
                                    Ningún producto lleva control de stock todavía.<br>
                                    <span class="text-xs">Actívalo en la pestaña Inventario de cada producto.</span>
                                @endif
                            </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── Últimos movimientos ── --}}
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-800">Últimos movimientos</h2>
                </div>
                <div class="divide-y divide-gray-50 max-h-[520px] overflow-y-auto">
                    @forelse($ultimos as $m)
                    <div class="px-4 py-2.5 flex items-start gap-2.5">
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 text-xs font-black
                                     {{ $m->quantity > 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500' }}">
                            {{ $m->quantity > 0 ? '+' : '−' }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-semibold text-gray-800 truncate">{{ $m->product->name ?? 'Producto eliminado' }}</div>
                            <div class="text-[11px] text-gray-400">
                                {{ $m->motivo }} · {{ abs($m->quantity) }} u
                                @if($m->balance_after !== null) · queda {{ $m->balance_after }} @endif
                            </div>
                            <div class="text-[10px] text-gray-300">{{ $m->created_at->format('d/m/Y H:i') }} · {{ $m->user->name ?? 'Sistema' }}</div>
                        </div>
                    </div>
                    @empty
                    <div class="px-4 py-10 text-center text-xs text-gray-400">
                        Aún no hay movimientos.<br>Se registran solos con cada venta.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ── Modal: registrar movimiento ── --}}
    <div x-show="movModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(15,23,42,.5)" @click.self="movModal = false" @keydown.escape.window="movModal = false">
        <form method="POST" action="{{ route('inventory.store') }}" class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            @csrf
            <div class="px-5 py-4" style="background:linear-gradient(135deg,#4F46E5,#7C3AED)">
                <h3 class="text-white font-bold">Registrar movimiento</h3>
                <p class="text-xs mt-0.5" style="color:rgba(255,255,255,.82)">Queda asentado en el Kardex del producto con fecha, motivo y responsable.</p>
            </div>

            <div class="p-5 space-y-4 overflow-y-auto" style="max-height:64vh">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Producto</label>
                    <select name="product_id" x-model="mov.product_id" required
                            class="w-full border border-gray-200 rounded-lg px-3 h-10 text-sm outline-none focus:border-indigo-400">
                        <option value="">Selecciona un producto…</option>
                        @foreach($productos as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} — stock {{ (int) $p->stock }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">Solo aparecen los productos que llevan control de stock.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Motivo</label>
                    <select name="reason" x-model="mov.reason" required
                            class="w-full border border-gray-200 rounded-lg px-3 h-10 text-sm outline-none focus:border-indigo-400">
                        <optgroup label="Entra mercadería">
                            @foreach($motivosEntrada as $r)
                            <option value="{{ $r }}">{{ \App\Modules\Inventario\Support\InventoryLedger::etiqueta($r) }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Sale mercadería">
                            @foreach($motivosSalida as $r)
                            <option value="{{ $r }}">{{ \App\Modules\Inventario\Support\InventoryLedger::etiqueta($r) }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Corrección">
                            <option value="conteo">Conteo físico (fijar cantidad exacta)</option>
                        </optgroup>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1"
                               x-text="mov.reason === 'conteo' ? 'Cantidad contada' : 'Cantidad'"></label>
                        <input type="number" name="quantity" x-model="mov.quantity" min="{{ 0 }}" required
                               class="w-full border border-gray-200 rounded-lg px-3 h-10 text-sm outline-none focus:border-indigo-400">
                        <p class="text-[11px] text-gray-400 mt-1" x-show="mov.reason === 'conteo'" x-cloak>
                            El stock quedará exactamente en este número.
                        </p>
                    </div>
                    <div x-show="mov.reason === 'compra'" x-cloak>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Costo unitario</label>
                        <input type="number" name="unit_cost" x-model="mov.unit_cost" step="0.01" min="0"
                               class="w-full border border-gray-200 rounded-lg px-3 h-10 text-sm outline-none focus:border-indigo-400">
                        <p class="text-[11px] text-gray-400 mt-1">Para valorizar el inventario.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nota <span class="text-gray-400 font-normal">(opcional)</span></label>
                    <input type="text" name="notes" x-model="mov.notes" maxlength="300"
                           placeholder="Ej: factura 001-1234 del proveedor"
                           class="w-full border border-gray-200 rounded-lg px-3 h-10 text-sm outline-none focus:border-indigo-400">
                </div>
            </div>

            <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex gap-2">
                <button type="button" @click="movModal = false"
                        class="flex-1 h-10 rounded-lg border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit"
                        class="flex-1 h-10 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold">Registrar</button>
            </div>
        </form>
    </div>
</div>

</x-slot>
</x-app-layout>
