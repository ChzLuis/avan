<x-app-layout>
<x-slot name="slot">

@php
    $editable = $orden->esEditable();
    $recibible = $orden->admiteRecepcion();
@endphp

<div class="flex flex-col h-full w-full overflow-hidden" style="background:#F8FAFC" x-data="compra()">

    <div class="flex-shrink-0 bg-white border-b border-gray-200 px-4 md:px-6 py-3">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-lg font-bold text-gray-900 font-mono">{{ $orden->numero }}</h1>
                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full {{ $orden->colorEstado() }}">{{ $orden->etiquetaEstado() }}</span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $orden->proveedor->name ?? 'Sin proveedor' }}
                    @if($orden->ubicacion) · llega a {{ $orden->ubicacion->tituloConSede() }}@endif
                    @if($orden->pendiente() > 0 && $orden->estado !== 'borrador')
                    · <b class="text-amber-700">faltan {{ $orden->pendiente() }} unidades</b>
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('inventory.compras') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-800 px-3 py-2">← Órdenes</a>
                @if($editable)
                <form method="POST" action="{{ route('inventory.compras.enviar', $orden->id) }}">
                    @csrf
                    <button class="text-xs px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold">Enviar al proveedor</button>
                </form>
                @endif
                @if($orden->estado !== 'anulada' && $orden->estado !== 'recibida')
                <form method="POST" action="{{ route('inventory.compras.anular', $orden->id) }}"
                      onsubmit="return confirm('¿Anular esta orden?')">
                    @csrf
                    <button class="text-xs px-3 py-2 text-gray-400 hover:text-red-600 font-semibold">Anular</button>
                </form>
                @endif
            </div>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 md:p-6">
        <div class="max-w-4xl mx-auto space-y-4">

            @if(session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif
            @if(session('error'))
            <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            @if($editable)
            {{-- Agregar productos. Solo mientras es borrador: una vez enviada,
                 cambiar lo pedido haría que la orden dejara de coincidir con lo
                 que el proveedor tiene en la mano. --}}
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <label class="block text-xs font-semibold text-gray-600 mb-2">Agregar producto</label>
                <div class="flex gap-2 flex-wrap">
                    <select x-model="productId" class="flex-1 min-w-[200px] rounded-lg border-gray-300" style="font-size:16px">
                        <option value="">Elige un producto</option>
                        @foreach($productos as $p)
                        <option value="{{ $p->id }}" data-costo="{{ $p->cost }}">{{ $p->name }}@if($p->sku) · {{ $p->sku }}@endif</option>
                        @endforeach
                    </select>
                    <input x-model.number="cantidad" type="number" min="1" placeholder="Cantidad"
                           class="w-28 rounded-lg border-gray-300" style="font-size:16px">
                    <input x-model.number="precio" type="number" step="0.01" min="0" placeholder="Precio"
                           class="w-32 rounded-lg border-gray-300" style="font-size:16px">
                    <button @click="agregar()" :disabled="!productId || !cantidad || ocupado"
                            class="px-5 py-2 text-sm font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-40">Agregar</button>
                </div>
                <p class="text-xs mt-2" :class="msgTipo === 'error' ? 'text-red-600' : 'text-emerald-700'" x-text="msg"></p>
                <p class="text-[11px] text-gray-400 mt-1">Si dejas el precio vacío se usa el último costo del producto.</p>
            </div>
            @endif

            {{-- ── Líneas ── --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                @if($recibible)
                <form method="POST" action="{{ route('inventory.compras.recibir', $orden->id) }}">
                @csrf
                @endif
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-[11px] uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold">Producto</th>
                                <th class="px-3 py-2 text-right font-semibold">Pedido</th>
                                <th class="px-3 py-2 text-right font-semibold">Recibido</th>
                                <th class="px-3 py-2 text-right font-semibold">Precio</th>
                                <th class="px-3 py-2 text-right font-semibold">Total</th>
                                @if($recibible)<th class="px-3 py-2 text-right font-semibold">Recibir ahora</th>@endif
                                @if($editable)<th class="px-3 py-2"></th>@endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100" style="font-variant-numeric:tabular-nums">
                            @forelse($orden->items as $item)
                            <tr class="{{ $item->pendiente() === 0 ? 'bg-emerald-50/40' : '' }}">
                                <td class="px-4 py-2.5">
                                    <div class="font-semibold text-gray-800">{{ $item->product->name ?? '—' }}</div>
                                    <div class="text-[11px] text-gray-400 font-mono">{{ $item->product->sku ?? '' }}</div>
                                </td>
                                <td class="px-3 py-2.5 text-right text-gray-700">{{ $item->cantidad }}</td>
                                <td class="px-3 py-2.5 text-right">
                                    <span class="{{ $item->pendiente() === 0 ? 'text-emerald-700 font-bold' : 'text-gray-600' }}">{{ $item->cantidad_recibida }}</span>
                                    @if($item->pendiente() > 0 && $orden->estado !== 'borrador')
                                    <div class="text-[10px] text-amber-600">faltan {{ $item->pendiente() }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-right text-gray-600">{{ $moneda }} {{ number_format($item->precio_unitario, 2) }}</td>
                                <td class="px-3 py-2.5 text-right font-semibold text-gray-800">{{ $moneda }} {{ number_format($item->totalLinea(), 2) }}</td>
                                @if($recibible)
                                <td class="px-3 py-2.5 text-right">
                                    @if($item->pendiente() > 0)
                                    <input type="number" name="recibido[{{ $item->id }}]" min="0" max="{{ $item->pendiente() }}"
                                           placeholder="0" class="w-20 text-right rounded border-gray-300 py-1" style="font-size:16px">
                                    @else
                                    <span class="text-xs text-emerald-600 font-semibold">completo</span>
                                    @endif
                                </td>
                                @endif
                                @if($editable)
                                <td class="px-3 py-2.5 text-right">
                                    <button type="button" @click="quitar({{ $item->id }})"
                                            class="text-xs text-gray-300 hover:text-red-500 px-1.5">✕</button>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400 text-sm">
                                Esta orden todavía no tiene productos.
                            </td></tr>
                            @endforelse
                        </tbody>
                        @if($orden->items->isNotEmpty())
                        <tfoot class="bg-gray-50 text-sm" style="font-variant-numeric:tabular-nums">
                            <tr>
                                <td colspan="4" class="px-4 py-1.5 text-right text-gray-500">Subtotal</td>
                                <td class="px-3 py-1.5 text-right text-gray-700">{{ $moneda }} {{ number_format($orden->subtotal, 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="px-4 py-1.5 text-right text-gray-500">IGV</td>
                                <td class="px-3 py-1.5 text-right text-gray-700">{{ $moneda }} {{ number_format($orden->igv, 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                            <tr class="border-t border-gray-200">
                                <td colspan="4" class="px-4 py-2 text-right font-bold text-gray-900">Total</td>
                                <td class="px-3 py-2 text-right font-bold text-gray-900">{{ $moneda }} {{ number_format($orden->total, 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>

                @if($recibible)
                <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between gap-3 flex-wrap">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="actualizar_costo" value="1" checked
                               class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        Actualizar el costo del producto con el precio de esta compra
                    </label>
                    <button class="px-5 py-2.5 text-sm font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                        Registrar recepción
                    </button>
                </div>
                <p class="px-4 pb-3 text-[11px] text-gray-400">
                    Lo que recibas entra al stock y queda en el kardex de cada producto como "Compra a proveedor".
                </p>
                @endif

                @if($recibible)</form>@endif
            </div>

            @if($orden->notas)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-600 mb-1">Notas</p>
                <p class="text-sm text-gray-700">{{ $orden->notas }}</p>
            </div>
            @endif

        </div>
    </div>

    {{-- Quitar línea: va por formulario aparte para no anidarlo dentro del de recepción. --}}
    <form method="POST" action="{{ route('inventory.compras.quitar', $orden->id) }}" x-ref="formQuitar" class="hidden">
        @csrf
        <input type="hidden" name="item_id" x-ref="itemQuitar">
    </form>
</div>

<script>
function compra() {
    return {
        productId: '', cantidad: '', precio: '', msg: '', msgTipo: '', ocupado: false,

        async agregar() {
            if (!this.productId || !this.cantidad) return;
            this.ocupado = true;
            try {
                const res = await fetch('{{ route('inventory.compras.linea', $orden->id) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        product_id: Number(this.productId),
                        cantidad: Number(this.cantidad),
                        precio_unitario: this.precio === '' ? null : Number(this.precio),
                    }),
                });
                const j = await res.json().catch(() => null);
                if (!res.ok || !j || !j.ok) {
                    this.msg = (j && j.error) ? j.error : 'No se pudo agregar (código ' + res.status + ').';
                    this.msgTipo = 'error';
                    return;
                }
                this.msg = 'Agregado. Total de la orden: {{ $moneda }} ' + j.total.toFixed(2);
                this.msgTipo = '';
                this.productId = ''; this.cantidad = ''; this.precio = '';
                setTimeout(() => window.location.reload(), 700);
            } catch (e) {
                this.msg = 'Sin conexión. Revisa la señal.';
                this.msgTipo = 'error';
            } finally { this.ocupado = false; }
        },

        quitar(id) {
            if (!confirm('¿Quitar este producto de la orden?')) return;
            this.$refs.itemQuitar.value = id;
            this.$refs.formQuitar.submit();
        },
    };
}
</script>

</x-slot>
</x-app-layout>
