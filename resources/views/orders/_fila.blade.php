{{--
    Una fila de la tabla (desktop) y su equivalente en tarjeta (<768).

    No es una tabla comprimida en móvil: son dos marcados distintos que
    comparten estado. Comprimir 9 columnas en 390 px produce texto ilegible.

    Los tres estados van separados —comercial, pago y operación— porque antes
    un solo `filterStatus` mezclaba `status` con `laundry_status` y no había
    forma de ver "entregado pero sin cobrar".
--}}

{{-- ══ TABLA (≥768) ══ --}}
<div class="hidden md:block flex-1 overflow-y-auto">
    <table class="w-full text-xs" style="border-collapse:separate;border-spacing:0">
        <thead class="sticky top-0 z-10 bg-gray-50 text-gray-500">
            <tr class="text-left">
                <th class="ped-th">Pedido</th>
                <th class="ped-th">Cliente</th>
                <th class="ped-th hidden xl:table-cell">Fecha</th>
                <th class="ped-th text-right">Total</th>
                <th class="ped-th">Comercial</th>
                <th class="ped-th">Pago</th>
                @if (!empty($flujoOperativo))
                    <th class="ped-th hidden lg:table-cell">Operación</th>
                @endif
                <th class="ped-th hidden xl:table-cell">Responsable</th>
                <th class="ped-th text-right">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <template x-for="o in filtered" :key="o.id">
                <tr data-pedido :class="selected && selected.id === o.id ? 'is-selected' : 'hover:bg-gray-50/70'"
                    class="border-b border-gray-100 cursor-pointer transition"
                    @click="abrirPedido(o, $event.currentTarget)"
                    tabindex="0"
                    @keydown.enter="abrirPedido(o, $event.currentTarget)"
                    :aria-label="'Pedido ' + (o.tag_code || o.id) + ' de ' + o.client_name">

                    <td class="ped-td">
                        <span class="font-bold text-gray-900" x-text="'#' + (o.tag_code || o.id)"></span>
                        <span class="block text-[10px] text-gray-400">
                            <span x-text="(o.items_count || 0) + ' item' + ((o.items_count||0)===1?'':'s')"></span>
                            <span x-show="o.sales_channel" class="ml-1" x-text="chLabel(o.sales_channel)"></span>
                        </span>
                    </td>

                    <td class="ped-td">
                        <span class="font-semibold text-gray-800" x-text="o.client_name || '—'"></span>
                        <span class="block text-[10px] text-gray-400" x-text="o.client_phone || ''"></span>
                    </td>

                    <td class="ped-td hidden xl:table-cell text-gray-500"
                        :title="o.created_at_full || ''" x-text="timeAgo(o.created_ts)"></td>

                    <td class="ped-td text-right font-bold text-gray-900"
                        x-text="money(o.total)"></td>

                    <td class="ped-td">
                        <span class="status-pill" :class="pillComercial(o).cls" x-text="pillComercial(o).label"></span>
                    </td>

                    <td class="ped-td">
                        <span class="status-pill" :class="pillPago(o).cls" x-text="pillPago(o).label"></span>
                        <span x-show="o.payment_method" class="block text-[10px] text-gray-400" x-text="o.payment_method"></span>
                    </td>

                    @if (!empty($flujoOperativo))
                        <td class="ped-td hidden lg:table-cell">
                            <span x-show="estadoOperativo(o)" class="status-pill s-process" x-text="estadoOperativo(o)"></span>
                            <span x-show="!estadoOperativo(o)" class="text-gray-300">—</span>
                        </td>
                    @endif

                    <td class="ped-td hidden xl:table-cell text-gray-500" x-text="responsable(o)"></td>

                    <td class="ped-td text-right">
                        <span class="text-gray-300 group-hover:text-gray-500">&rsaquo;</span>
                    </td>
                </tr>
            </template>
        </tbody>
    </table>

    {{-- Estado vacio DISEÑADO (DoD): distingue "aun no hay" de "el filtro no
         devuelve nada", y siempre ofrece una salida. --}}
    <div x-show="filtered.length === 0" x-cloak class="py-16 px-6 flex flex-col items-center text-center gap-2">
        <svg class="w-12 h-12" style="color:var(--borde, #e5e7eb)" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
        </svg>
        <template x-if="orders.length === 0">
            <div>
                <p class="font-bold text-gray-700">Todavía no hay pedidos</p>
                <p class="text-sm text-gray-400 mt-0.5">Cuando entre el primero, aparecerá en esta lista.</p>
            </div>
        </template>
        <template x-if="orders.length > 0">
            <div>
                <p class="font-bold text-gray-700">Ningún pedido coincide con los filtros</p>
                <p class="text-sm text-gray-400 mt-0.5">Prueba a limpiarlos para ver todos.</p>
                <button type="button" @click="limpiarFiltros()"
                        class="mt-3 s-btn bg-indigo-50 text-indigo-700">Limpiar filtros</button>
            </div>
        </template>
    </div>
</div>

{{-- ══ TARJETAS (<768) ══ --}}
<div class="md:hidden flex-1 overflow-y-auto p-3 space-y-2">
    <template x-for="o in filtered" :key="'m'+o.id">
        <button data-pedido @click="abrirPedido(o, $event.currentTarget)"
                class="w-full text-left bg-white border border-gray-200 rounded-xl p-3 shadow-sm active:bg-gray-50 transition"
                :aria-label="'Pedido ' + (o.tag_code || o.id)">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <span class="font-bold text-gray-900 text-xs" x-text="'#' + (o.tag_code || o.id)"></span>
                    <span class="block font-semibold text-sm text-gray-800 truncate" x-text="o.client_name || '—'"></span>
                    <span class="block text-[10px] text-gray-400">
                        <span x-text="timeAgo(o.created_ts)"></span>
                        <span x-text="' · ' + (o.items_count || 0) + ' items'"></span>
                    </span>
                </div>
                <span class="font-black text-gray-900 text-sm whitespace-nowrap"
                      x-text="money(o.total)"></span>
            </div>
            <div class="flex flex-wrap gap-1 mt-2">
                <span class="status-pill" :class="pillComercial(o).cls" x-text="pillComercial(o).label"></span>
                <span class="status-pill" :class="pillPago(o).cls" x-text="pillPago(o).label"></span>
                <span x-show="estadoOperativo(o)" class="status-pill s-process" x-text="estadoOperativo(o)"></span>
            </div>
        </button>
    </template>

    <div x-show="filtered.length === 0" class="py-16 text-center text-gray-400 text-sm">
        Sin pedidos que coincidan
    </div>
</div>
