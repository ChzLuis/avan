<x-portal-layout :layout="$portalLayout ?? 'panel'" :project="$project" pageTitle="POS — Caja">
@php
    // Mismo criterio de liberacion que el menu del portal.
    $modRevendedor = \App\Support\ModulosPortal::liberados($project, auth()->id())['revendedor'] ?? false;
@endphp
<div class="flex flex-1 overflow-hidden pb-16 md:pb-0" x-data="Object.assign(posApp(), { posTab: 'catalog' })" x-init="init()" style="height:calc(100vh - 56px);">

{{-- ══════════════════════════════════════════════════════
     PANEL IZQUIERDO — Catálogo
══════════════════════════════════════════════════════ --}}
<div class="flex flex-col bg-white border-r border-gray-200 w-full md:w-[58%]"
     :class="{ 'hidden md:flex': posTab !== 'catalog' }">

    {{-- Header --}}
    <div class="flex flex-col border-b border-gray-100 flex-shrink-0">
        {{-- Tabs --}}
        <div class="flex items-center border-b border-gray-100 px-4 pt-2 gap-4">
            {{-- Rejilla o lista, en la misma fila que Productos/Servicios.
                 Abajo, al final de los filtros de categoria, se perdia: esa
                 fila desplaza en horizontal y con muchas categorias el
                 selector quedaba fuera de la vista.
                 Va primero en el HTML y con `ml-auto order-last` para que
                 empuje a la derecha tambien cuando "Vender fácil" no existe. --}}
            <div class="ml-auto order-last mb-1 flex flex-shrink-0 rounded-lg border border-gray-200 p-0.5"
                 role="group" aria-label="Formato del catálogo">
                <button type="button" @click="setVista('grid')" title="Ver en cuadrícula" aria-label="Ver en cuadrícula"
                        :aria-pressed="vista === 'grid' ? 'true' : 'false'"
                        :class="vista === 'grid' ? 'bg-gray-900 text-white' : 'text-gray-500 hover:bg-gray-50'"
                        class="p-1.5 rounded-md transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
                    </svg>
                </button>
                <button type="button" @click="setVista('lista')" title="Ver en lista" aria-label="Ver en lista"
                        :aria-pressed="vista === 'lista' ? 'true' : 'false'"
                        :class="vista === 'lista' ? 'bg-gray-900 text-white' : 'text-gray-500 hover:bg-gray-50'"
                        class="p-1.5 rounded-md transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                </button>
            </div>

            {{-- "Vender fácil" es el Modo Revendedor: cambia el precio que se
                 escribe, enseña margen y cobra en un paso. En un negocio que
                 no revende no significa nada y solo estorba en la barra, asi
                 que sigue el mismo criterio que el menu: se ofrece a quien lo
                 usa (tiene precios propios) o a quien lo libere con el ajuste
                 `modulo_revendedor`. --}}
            @if($modRevendedor)
            <button @click="resellerMode = !resellerMode"
                    :class="resellerMode ? 'bg-green-500 text-white border-green-500 shadow-sm' : 'bg-white text-gray-500 border-gray-200 hover:border-green-400'"
                    class="order-last mr-2 mb-1 flex items-center gap-1.5 px-3 py-1.5 border-2 rounded-full text-xs font-bold transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span x-text="resellerMode ? 'Vender fácil: ON' : 'Vender fácil'"></span>
            </button>
            @endif
            <button @click="catalogTab='products'"
                    :class="catalogTab==='products' ? 'border-b-2 border-indigo-600 text-indigo-700 font-semibold' : 'text-gray-400 hover:text-gray-600'"
                    class="pb-2 text-sm transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Productos
                <span class="text-xs bg-gray-100 text-gray-500 rounded-full px-1.5 py-0.5" x-text="products.length"></span>
            </button>
            <button @click="catalogTab='services'"
                    :class="catalogTab==='services' ? 'border-b-2 border-purple-600 text-purple-700 font-semibold' : 'text-gray-400 hover:text-gray-600'"
                    class="pb-2 text-sm transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Servicios
                <span class="text-xs bg-gray-100 text-gray-500 rounded-full px-1.5 py-0.5" x-text="services.length"></span>
            </button>
        </div>
        {{-- Buscador + categorías --}}
        <div class="px-3 py-2 space-y-2">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input x-model="search" type="text" placeholder="Buscar producto..." autocomplete="off"
                       class="w-full pl-9 pr-9 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none">
                <button x-show="search" @click="search=''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-400 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            {{-- Dos desplegables, no filas de chips: en caja hay que ver el
                 catalogo, no los filtros. La categoria lleva sus subcategorias
                 agrupadas dentro; la marca ofrece solo las presentes en lo ya
                 filtrado, con su conteo. --}}
            <div class="flex gap-2">
                <select :value="valorCatSel" @change="elegirDesdeSelect($event.target.value)" aria-label="Categoría"
                        class="flex-1 min-w-0 text-sm border border-gray-200 rounded-xl py-2 px-3 bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="">Todas las categorías</option>
                    <template x-for="cat in catRaicesVisibles" :key="cat.id">
                        <optgroup :label="cat.name + ' (' + contarCat(cat.id) + ')'">
                            <option :value="'c' + cat.id" x-text="'Todo en ' + cat.name"></option>
                            <template x-for="sub in categories.filter(c => c.parent_id === cat.id && (cuentaEnCat(c.id) > 0 || filterSub === c.id))" :key="'s' + sub.id">
                                <option :value="'s' + sub.id" x-text="'   ' + sub.name + ' (' + contarCat(sub.id) + ')'"></option>
                            </template>
                        </optgroup>
                    </template>
                </select>
                <select :value="filterBrand" @change="filterBrand = $event.target.value || null; ajustarCat()" aria-label="Marca" x-show="marcasDisponibles.length || filterBrand"
                        class="w-40 flex-shrink-0 text-sm border border-gray-200 rounded-xl py-2 px-3 bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="">Todas las marcas</option>
                    <template x-for="m in marcasDisponibles" :key="'m' + m.nombre">
                        <option :value="m.nombre" x-text="(m.etiqueta || m.nombre) + ' (' + m.n + ')'"></option>
                    </template>
                </select>
            </div>
            <div class="flex items-center justify-between text-[11px] text-gray-400" x-show="filterCat!==null || filterBrand!==null || search" x-cloak>
                <span><b class="text-gray-600" x-text="filteredProducts.length"></b> productos</span>
                <button type="button" class="font-semibold text-indigo-600 hover:underline" @click="limpiarFiltros()">Limpiar filtros</button>
            </div>
        </div>
    </div>

    {{-- Grid productos --}}
    <div class="flex-1 overflow-y-auto p-3">
        <div x-show="catalogTab==='products'">
            <template x-if="filteredProducts.length === 0">
                <div class="text-center py-16 text-gray-400">
                    <svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <p class="text-sm font-medium">Sin resultados</p>
                    <button x-show="search||filterCat" @click="search=''; filterCat=null" class="text-xs text-indigo-500 mt-1 hover:underline">Limpiar filtros</button>
                </div>
            </template>
            <div x-show="vista === 'grid'" class="grid gap-2.5" style="grid-template-columns: repeat(auto-fill, minmax(120px,1fr));">
                <template x-for="p in filteredProducts" :key="p.id">
                    <button @click="resellerMode ? openQuickSale(p) : addToCart(p)"
                            :disabled="p.stock !== null && p.stock !== undefined && p.stock <= 0"
                            :class="(p.stock !== null && p.stock !== undefined && p.stock <= 0) ? 'opacity-40 cursor-not-allowed' : 'hover:border-indigo-400 hover:shadow-lg active:scale-95 active:bg-indigo-50'"
                            class="relative bg-white border-2 border-gray-100 rounded-2xl p-2.5 text-left transition-all group">
                        {{-- Badge cantidad en carrito --}}
                        <template x-if="cartQty(p.id, 'product') > 0">
                            <div class="absolute -top-2 -right-2 w-6 h-6 bg-indigo-600 text-white text-xs font-black rounded-full flex items-center justify-center shadow-md z-10"
                                 x-text="cartQty(p.id, 'product')"></div>
                        </template>
                        <div class="w-full aspect-square rounded-xl mb-2 overflow-hidden bg-gray-50 flex items-center justify-center">
                            <template x-if="p.image">
                                <img :src="p.image" :alt="p.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                            </template>
                            <template x-if="!p.image">
                                <svg class="w-8 h-8 text-gray-200 group-hover:text-indigo-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </template>
                        </div>
                        <p class="pos-nombre font-semibold text-gray-800" x-text="p.name" :title="p.name"></p>
                        <p class="text-sm font-black text-indigo-600 mt-1" x-text="'S/ ' + p.price.toFixed(2)"></p>
                        <template x-if="p.stock !== null && p.stock !== undefined">
                            <p class="text-[10px] mt-0.5 font-medium"
                               :class="p.stock <= 0 ? 'text-red-500' : p.stock <= 5 ? 'text-amber-500' : 'text-gray-400'"
                               x-text="p.stock <= 0 ? 'Sin stock' : 'Stock: ' + p.stock"></p>
                        </template>
                    </button>
                </template>
            </div>

            {{-- Lista: una fila por producto, nombre completo y cifras
                 alineadas. Con 106 productos y nombres largos, la rejilla
                 obliga a leer en zigzag y corta los nombres a dos lineas. --}}
            <div x-show="vista === 'lista'" x-cloak class="divide-y divide-gray-100 rounded-xl border border-gray-100 overflow-hidden bg-white">
                <template x-for="p in filteredProducts" :key="'l'+p.id">
                    <button @click="resellerMode ? openQuickSale(p) : addToCart(p)"
                            :disabled="p.stock !== null && p.stock !== undefined && p.stock <= 0"
                            :class="(p.stock !== null && p.stock !== undefined && p.stock <= 0) ? 'opacity-40 cursor-not-allowed' : 'hover:bg-indigo-50/60 active:bg-indigo-100'"
                            class="w-full flex items-center gap-3 px-3 py-2 text-left transition">
                        <div class="w-10 h-10 flex-shrink-0 rounded-lg overflow-hidden bg-gray-50 flex items-center justify-center">
                            <template x-if="p.image">
                                <img :src="p.image" :alt="p.name" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!p.image">
                                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-800 leading-snug" x-text="p.name" :title="p.name"></p>
                            <template x-if="p.stock !== null && p.stock !== undefined">
                                <p class="text-[11px] font-medium"
                                   :class="p.stock <= 0 ? 'text-red-500' : p.stock <= 5 ? 'text-amber-500' : 'text-gray-400'"
                                   x-text="p.stock <= 0 ? 'Sin stock' : 'Stock: ' + p.stock"></p>
                            </template>
                        </div>
                        <p class="flex-shrink-0 text-sm font-bold text-indigo-600 tabular-nums" x-text="'S/ ' + p.price.toFixed(2)"></p>
                        <span class="flex-shrink-0 w-7 h-7 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center relative">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            <template x-if="cartQty(p.id, 'product') > 0">
                                <span class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 bg-indigo-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center"
                                      x-text="cartQty(p.id, 'product')"></span>
                            </template>
                        </span>
                    </button>
                </template>
            </div>
        </div>
        <div x-show="catalogTab==='services'">
            <template x-if="filteredServices.length === 0">
                <div class="text-center py-16 text-gray-400">
                    <p class="text-sm">Sin servicios</p>
                </div>
            </template>
            <div x-show="vista === 'grid'" class="grid gap-2.5" style="grid-template-columns: repeat(auto-fill, minmax(120px,1fr));">
                <template x-for="s in filteredServices" :key="s.id">
                    <button @click="addToCart(s)"
                            class="relative bg-white border-2 border-gray-100 rounded-2xl p-2.5 text-left hover:border-purple-400 hover:shadow-lg transition-all group active:scale-95 active:bg-purple-50">
                        <template x-if="cartQty(s.id, 'service') > 0">
                            <div class="absolute -top-2 -right-2 w-6 h-6 bg-purple-600 text-white text-xs font-black rounded-full flex items-center justify-center shadow-md z-10"
                                 x-text="cartQty(s.id, 'service')"></div>
                        </template>
                        <div class="w-full aspect-square rounded-xl mb-2 overflow-hidden bg-purple-50 flex items-center justify-center">
                            <svg class="w-8 h-8 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-xs font-semibold text-gray-800 leading-tight line-clamp-2 min-h-[2.5rem]" x-text="s.name"></p>
                        <p class="text-sm font-black text-purple-600 mt-1" x-text="'S/ ' + s.price.toFixed(2)"></p>
                        <p x-show="s.duration_min" class="text-[10px] text-gray-400 mt-0.5"
                           x-text="s.duration_min >= 60 ? Math.floor(s.duration_min/60)+'h'+(s.duration_min%60?s.duration_min%60+'m':'') : s.duration_min+'min'"></p>
                    </button>
                </template>
            </div>

            <div x-show="vista === 'lista'" x-cloak class="divide-y divide-gray-100 rounded-xl border border-gray-100 overflow-hidden bg-white">
                <template x-for="s in filteredServices" :key="'l'+s.id">
                    <button @click="addToCart(s)"
                            class="w-full flex items-center gap-3 px-3 py-2 text-left transition hover:bg-purple-50/60 active:bg-purple-100">
                        <div class="w-10 h-10 flex-shrink-0 rounded-lg bg-purple-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-800 truncate" x-text="s.name"></p>
                            <p x-show="s.duration_min" class="text-[11px] text-gray-400"
                               x-text="s.duration_min >= 60 ? Math.floor(s.duration_min/60)+'h'+(s.duration_min%60?s.duration_min%60+'m':'') : s.duration_min+'min'"></p>
                        </div>
                        <p class="flex-shrink-0 text-sm font-bold text-purple-600 tabular-nums" x-text="'S/ ' + s.price.toFixed(2)"></p>
                        <span class="flex-shrink-0 w-7 h-7 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center relative">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            <template x-if="cartQty(s.id, 'service') > 0">
                                <span class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 bg-purple-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center"
                                      x-text="cartQty(s.id, 'service')"></span>
                            </template>
                        </span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- Footer transacciones --}}
    <div class="border-t border-gray-100 px-4 py-2 flex items-center gap-3 bg-white">
        <button @click="showTransactions = !showTransactions"
                class="text-xs text-gray-500 hover:text-indigo-600 flex items-center gap-1.5 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span x-text="`${transactions.length} transacciones hoy`"></span>
        </button>
        <div class="ml-auto text-xs">
            Total día: <span class="font-bold text-gray-800" x-text="'S/ ' + todayTotal.toFixed(2)"></span>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     PANEL DERECHO — Carrito / Cobro
══════════════════════════════════════════════════════ --}}
<div class="flex flex-col bg-gray-50 w-full md:w-[42%] md:min-w-[300px]"
     :class="{ 'hidden md:flex': posTab !== 'cart' }">

    {{-- Header carrito --}}
    <div class="flex flex-col gap-2 px-4 py-3 bg-white border-b border-gray-200 flex-shrink-0">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-4 h-4" :class="mode==='quote' ? 'text-amber-500' : 'text-indigo-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span x-text="mode==='quote' ? 'Cotización' : 'Nueva venta'"></span>
                <span x-show="cart.length > 0"
                      class="text-xs bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded-full"
                      x-text="cart.reduce((s,i)=>s+i.qty,0) + ' ítem' + (cart.reduce((s,i)=>s+i.qty,0)!==1?'s':'')"></span>
            </h2>
            <button @click="vaciarCarrito()" x-show="cart.length > 0"
                    class="text-xs text-gray-400 hover:text-red-500 transition flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-red-50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Vaciar
            </button>
        </div>
        {{-- Toggle Venta directa / Cotización --}}
        <div class="grid grid-cols-2 gap-1 bg-gray-100 rounded-xl p-1">
            <button @click="mode='sale'"
                    :class="mode==='sale' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                    class="py-1.5 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Venta directa
            </button>
            <button @click="mode='quote'"
                    :class="mode==='quote' ? 'bg-white text-amber-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                    class="py-1.5 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Cotización
            </button>
        </div>
    </div>

    {{-- Items del carrito --}}
    <div class="flex-1 overflow-y-auto px-3 py-2 space-y-1.5">
        <template x-if="cart.length === 0">
            <div class="text-center py-16 text-gray-400">
                <svg class="w-14 h-14 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="text-sm font-medium text-gray-500">Carrito vacío</p>
                <p class="text-xs mt-1 text-gray-400">Toca un producto para agregarlo</p>
            </div>
        </template>

        <template x-for="(item, idx) in cart" :key="item._key">
            <div class="bg-white rounded-xl border border-gray-100 px-2.5 py-2 space-y-1.5"
                 :class="item.type==='service' ? 'border-l-4 border-l-purple-400' : 'border-l-4 border-l-indigo-400'">
                <div class="flex items-center gap-2">
                    {{-- Nombre + precio editable --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-800 leading-snug truncate" x-text="item.name"></p>
                        <div class="flex items-center gap-1 mt-0.5">
                            <span class="text-[11px] text-gray-400" x-text="resellerMode ? 'Mi precio S/' : 'S/'"></span>
                            <input type="number" min="0" step="0.10" x-model.number="item.price"
                                   @focus="$event.target.select()"
                                   :class="(item.min != null && item.price < item.min) ? 'text-red-600 border-red-300 bg-red-50'
                                           : (item.basePrice != null && item.price != item.basePrice ? 'text-indigo-600 border-indigo-300' : 'text-gray-600 border-gray-200')"
                                   class="w-16 text-[11px] font-semibold text-center border rounded-md px-1 py-0.5 focus:ring-1 focus:ring-indigo-400 outline-none">
                            <span class="text-[10px] text-gray-300">c/u</span>
                            {{-- Modo normal: marca "referencial"/"ref." --}}
                            <template x-if="!resellerMode">
                                <span>
                                    <span x-show="item.basePrice != null && item.price == item.basePrice"
                                          class="text-[9px] text-amber-500 bg-amber-50 border border-amber-100 rounded px-1 leading-tight whitespace-nowrap">referencial</span>
                                    <span x-show="item.basePrice != null && item.price != item.basePrice"
                                          class="text-[9px] text-indigo-400 whitespace-nowrap" x-text="'ref. S/ ' + item.basePrice.toFixed(2)"></span>
                                </span>
                            </template>
                        </div>
                        {{-- Ganancia (modo revendedor) o aviso de mínimo --}}
                        <template x-if="item.min != null && item.price < item.min">
                            <p class="text-[9px] text-red-600 font-bold mt-0.5">⚠️ Mín. S/ <span x-text="item.min.toFixed(2)"></span></p>
                        </template>
                        <template x-if="resellerMode && (item.min == null || item.price >= item.min) && profitUnit(item.price, item) !== null">
                            <p class="text-[10px] font-bold mt-0.5"
                               :class="priceTone(item.price,item)==='low' ? 'text-amber-600' : 'text-green-600'">
                                <span x-text="priceTone(item.price,item)==='low' ? '🟡' : '🟢'"></span>
                                Ganas +S/ <span x-text="(profitUnit(item.price,item)*item.qty).toFixed(2)"></span>
                            </p>
                        </template>
                    </div>
                    {{-- Controles cantidad compactos --}}
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <button @click="decreaseQty(idx)"
                                class="w-7 h-7 rounded-lg bg-gray-100 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition font-bold text-base leading-none">
                            −
                        </button>
                        <span class="w-10 text-center text-sm font-black" x-text="item.qty"></span>
                        <button @click="increaseQty(idx)"
                                class="w-7 h-7 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white flex items-center justify-center transition font-bold text-base leading-none">
                            +
                        </button>
                    </div>
                    {{-- Total + quitar --}}
                    <div class="text-right flex-shrink-0 min-w-[56px]">
                        <p class="text-sm font-black"
                           :class="(item.discount||0)>0 ? 'text-green-700' : 'text-gray-900'"
                           x-text="'S/ ' + itemTotal(item).toFixed(2)"></p>
                        <button @click="removeFromCart(idx)"
                                class="text-[10px] text-gray-300 hover:text-red-500 transition">×</button>
                    </div>
                </div>
                {{-- Fila descuento por ítem (oculta en Modo Revendedor: concepto técnico) --}}
                <div x-show="!resellerMode" class="flex items-center gap-1.5 pt-0.5 border-t border-gray-50">
                    <span class="text-[10px] text-gray-400 font-medium whitespace-nowrap">Desc. ítem:</span>
                    <div class="flex items-center gap-1 flex-1">
                        <input type="number" x-model.number="item.discount" min="0" placeholder="0"
                               @focus="$event.target.select()"
                               class="w-16 text-center text-xs border border-gray-200 rounded-lg px-1 py-0.5 focus:ring-1 focus:ring-green-400 focus:border-green-400 outline-none">
                        <div class="flex rounded-lg border border-gray-200 overflow-hidden text-[10px] font-bold">
                            <button @click="item.discountType='pct'"
                                    :class="item.discountType==='pct' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'"
                                    class="px-1.5 py-0.5 transition">%</button>
                            <button @click="item.discountType='fixed'"
                                    :class="item.discountType==='fixed' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'"
                                    class="px-1.5 py-0.5 transition border-l border-gray-200">S/</button>
                        </div>
                    </div>
                    <span x-show="(item.discount||0) > 0"
                          class="text-[10px] text-green-600 font-semibold whitespace-nowrap"
                          x-text="'-S/ ' + itemDiscount(item).toFixed(2)"></span>
                </div>
            </div>
        </template>

        {{-- Ítem manual --}}
        <div x-show="cart.length > 0 || showCustom" class="pt-1">
            <button @click="showCustom=!showCustom" x-show="!showCustom"
                    class="w-full py-2.5 border-2 border-dashed border-gray-200 rounded-2xl text-xs text-gray-400 hover:border-indigo-300 hover:text-indigo-500 transition flex items-center justify-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Ítem manual
            </button>
            <div x-show="showCustom" class="bg-white border-2 border-indigo-200 rounded-2xl p-3 space-y-2">
                <p class="text-xs font-semibold text-indigo-700">Ítem personalizado</p>
                <input x-model="customItem.name" type="text" placeholder="Descripción"
                       class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                <div class="flex gap-2">
                    <input x-model.number="customItem.price" type="number" placeholder="Precio" min="0" step="0.01"
                           class="flex-1 text-sm border border-gray-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                    <input x-model.number="customItem.qty" type="number" placeholder="Cant." min="1"
                           class="w-20 text-sm border border-gray-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none text-center font-bold">
                </div>
                <div class="flex gap-2">
                    <button @click="addCustomItem()"
                            class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition">
                        Agregar
                    </button>
                    <button @click="showCustom=false; customItem={name:'',price:0,qty:1}"
                            class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm rounded-xl transition">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer cobro --}}
    <div class="bg-white border-t border-gray-200 px-3 pt-2 pb-2 space-y-1.5 flex-shrink-0">

        {{-- Total --}}
        <div class="flex justify-between items-center">
            <div>
                <span class="text-xs text-gray-500">
                    Subtotal <span class="font-semibold" x-text="'(' + cart.reduce((s,i)=>s+i.qty,0) + ')'"></span>
                </span>
                <template x-if="totalDiscount > 0">
                    <span class="text-xs text-green-600 font-semibold ml-2" x-text="'−S/ ' + totalDiscount.toFixed(2) + ' desc.'"></span>
                </template>
            </div>
            <span class="text-lg font-black text-gray-900" x-text="'S/ ' + cartTotal.toFixed(2)"></span>
        </div>

        {{-- Ganancia total (Modo Revendedor) --}}
        <template x-if="resellerMode && cart.length > 0 && cartProfit > 0">
            <div class="flex justify-between items-center bg-green-50 rounded-xl px-3 py-1.5">
                <span class="text-xs font-bold text-green-700">🟢 Tu ganancia</span>
                <span class="text-base font-black text-green-700" x-text="'+S/ ' + cartProfit.toFixed(2)"></span>
            </div>
        </template>

        {{-- Aviso: hay ítems bajo el mínimo --}}
        <template x-if="hasBelowMin">
            <div class="flex items-center gap-2 bg-red-50 border border-red-200 rounded-xl px-3 py-2">
                <span class="text-base">⚠️</span>
                <p class="text-xs font-bold text-red-700">Hay productos por debajo del precio mínimo. Ajústalos para continuar.</p>
            </div>
        </template>

        {{-- Bloque de pago (solo en modo Venta directa) --}}
        <div x-show="mode==='sale'" class="space-y-1.5">

        {{-- Toggle pago dividido --}}
        <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500 font-medium">Método de pago</span>
            <button @click="splitPayment = !splitPayment; if(!splitPayment){ paymentForm.method2=''; paymentForm.amount1=0; paymentForm.amount2=0; }"
                    :class="splitPayment ? 'bg-indigo-100 text-indigo-700 border-indigo-300' : 'bg-gray-100 text-gray-500 border-gray-200'"
                    class="text-[10px] font-bold px-2 py-0.5 rounded-full border transition flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                Dividir pago
            </button>
        </div>

        {{-- Métodos de pago --}}
        <div x-show="!splitPayment" class="grid grid-cols-3 gap-1">
            @foreach($paymentMethods as $pm)
            <button @click="paymentForm.method = '{{ $pm }}'"
                    :class="paymentForm.method === '{{ $pm }}' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:border-indigo-400 hover:bg-indigo-50'"
                    class="py-1.5 px-1 border-2 rounded-xl text-[11px] font-bold transition text-center leading-tight">
                {{ $pm }}
            </button>
            @endforeach
            @if($paymentMethods->isEmpty())
            <button @click="paymentForm.method = 'Efectivo'"
                    :class="paymentForm.method === 'Efectivo' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-200 hover:border-indigo-400'"
                    class="py-1.5 border-2 rounded-xl text-[11px] font-bold transition">Efectivo</button>
            <button @click="paymentForm.method = 'Tarjeta'"
                    :class="paymentForm.method === 'Tarjeta' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-200 hover:border-indigo-400'"
                    class="py-1.5 border-2 rounded-xl text-[11px] font-bold transition">Tarjeta</button>
            <button @click="paymentForm.method = 'Yape/Plin'"
                    :class="paymentForm.method === 'Yape/Plin' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-200 hover:border-indigo-400'"
                    class="py-1.5 border-2 rounded-xl text-[11px] font-bold transition">Yape/Plin</button>
            @endif
        </div>

        {{-- Pago dividido --}}
        <div x-show="splitPayment" class="bg-indigo-50 border border-indigo-200 rounded-xl p-2.5 space-y-2">
            <p class="text-[10px] font-bold text-indigo-700 uppercase tracking-wide">División de pago</p>
            {{-- Método 1 --}}
            <div class="flex items-center gap-2">
                <select x-model="paymentForm.method"
                        class="flex-1 text-xs border border-indigo-200 rounded-lg px-2 py-1.5 outline-none bg-white focus:ring-1 focus:ring-indigo-400">
                    @foreach($paymentMethods as $pm)
                    <option value="{{ $pm }}">{{ $pm }}</option>
                    @endforeach
                    @if($paymentMethods->isEmpty())
                    <option value="Efectivo">Efectivo</option>
                    <option value="Tarjeta">Tarjeta</option>
                    <option value="Yape/Plin">Yape/Plin</option>
                    <option value="Transferencia">Transferencia</option>
                    @endif
                </select>
                <div class="flex items-center gap-1 flex-shrink-0">
                    <span class="text-xs text-gray-500">S/</span>
                    <input type="number" x-model.number="paymentForm.amount1" min="0" step="0.50"
                           @input="paymentForm.amount2 = Math.max(0, cartTotal - paymentForm.amount1)"
                           @focus="$event.target.select()"
                           class="w-20 text-sm text-right font-black border border-indigo-200 rounded-lg px-2 py-1 focus:ring-1 focus:ring-indigo-400 outline-none bg-white">
                </div>
            </div>
            {{-- Método 2 --}}
            <div class="flex items-center gap-2">
                <select x-model="paymentForm.method2"
                        class="flex-1 text-xs border border-indigo-200 rounded-lg px-2 py-1.5 outline-none bg-white focus:ring-1 focus:ring-indigo-400">
                    @foreach($paymentMethods as $pm)
                    <option value="{{ $pm }}">{{ $pm }}</option>
                    @endforeach
                    @if($paymentMethods->isEmpty())
                    <option value="Efectivo">Efectivo</option>
                    <option value="Tarjeta">Tarjeta</option>
                    <option value="Yape/Plin">Yape/Plin</option>
                    <option value="Transferencia">Transferencia</option>
                    @endif
                </select>
                <div class="flex items-center gap-1 flex-shrink-0">
                    <span class="text-xs text-gray-500">S/</span>
                    <input type="number" x-model.number="paymentForm.amount2" min="0" step="0.50"
                           @focus="$event.target.select()"
                           class="w-20 text-sm text-right font-black border border-indigo-200 rounded-lg px-2 py-1 focus:ring-1 focus:ring-indigo-400 outline-none bg-white">
                </div>
            </div>
            <div class="flex justify-between text-xs pt-1 border-t border-indigo-100">
                <span class="text-gray-500">Suma:</span>
                <span :class="Math.abs((paymentForm.amount1+paymentForm.amount2)-cartTotal)<0.01 ? 'text-green-600 font-black' : 'text-red-500 font-black'"
                      x-text="'S/ ' + (paymentForm.amount1+paymentForm.amount2).toFixed(2) + (Math.abs((paymentForm.amount1+paymentForm.amount2)-cartTotal)<0.01 ? ' ✓' : ' ≠ S/ '+cartTotal.toFixed(2))"></span>
            </div>
        </div>

        {{-- Efectivo / vuelto --}}
        <div x-show="!splitPayment && paymentForm.method === 'Efectivo'" class="bg-green-50 border border-green-200 rounded-xl px-2.5 py-1.5 space-y-1">
            <div class="flex gap-2 items-center">
                <label class="text-xs text-gray-600 font-medium whitespace-nowrap">Recibido S/</label>
                <input x-model.number="paymentForm.received" type="number" min="0" step="0.5"
                       @focus="$event.target.select()"
                       class="flex-1 text-sm border border-green-200 rounded-lg px-2 py-1 focus:ring-2 focus:ring-green-500 outline-none text-right font-black">
            </div>
            {{-- El vuelto es lo que el cajero canta en voz alta: va en grande.
                 Si el dinero no alcanza se dice CUANTO falta, en vez de
                 mostrar 0.00 como si estuviera bien. --}}
            <div x-show="paymentForm.received > 0" x-cloak
                 class="flex items-baseline justify-between rounded-lg px-2 py-1"
                 :class="paymentForm.received >= cartTotal ? 'bg-green-100' : 'bg-red-50'">
                <span class="text-[11px] font-bold uppercase tracking-wide"
                      :class="paymentForm.received >= cartTotal ? 'text-green-700' : 'text-red-600'"
                      x-text="paymentForm.received >= cartTotal ? 'Vuelto' : 'Falta'"></span>
                <span class="text-2xl font-black leading-none"
                      :class="paymentForm.received >= cartTotal ? 'text-green-700' : 'text-red-600'"
                      x-text="'S/ ' + Math.abs(paymentForm.received - cartTotal).toFixed(2)"></span>
            </div>
            <div class="flex gap-1 flex-wrap">
                <template x-for="amt in quickAmounts" :key="amt">
                    <button @click="paymentForm.received = amt"
                            :class="paymentForm.received === amt ? 'bg-green-600 text-white' : 'bg-white text-gray-700 border border-green-200 hover:bg-green-50'"
                            class="px-2 py-0.5 rounded-md text-[11px] font-bold transition"
                            x-text="'S/'+amt"></button>
                </template>
                <button @click="paymentForm.received = Math.ceil(cartTotal)"
                        class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-white text-gray-700 border border-green-200 hover:bg-green-50 transition">
                    Exacto
                </button>
            </div>
        </div>

        </div> {{-- /Bloque de pago --}}

        {{-- ═══ DATOS DEL CLIENTE ═══ --}}
        {{-- En COTIZACIÓN: siempre visibles y el nombre es obligatorio (para saber a quién se envía). --}}
        {{-- En VENTA: opcionales, se abren con el botón. --}}

        {{-- Bloque cliente en modo COTIZACIÓN (siempre visible) --}}
        <div x-show="mode==='quote'" class="bg-amber-50 border border-amber-200 rounded-xl px-3 py-2.5 space-y-2">
            <div class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <p class="text-xs font-bold text-amber-700">¿Para quién es la cotización?</p>
            </div>
            <input x-model="paymentForm.client_name" type="text" placeholder="Nombre del cliente *"
                   :class="quoteNameMissing ? 'border-red-400 ring-1 ring-red-300' : 'border-amber-200'"
                   class="w-full text-sm border rounded-lg px-3 py-2 bg-white focus:ring-2 focus:ring-amber-400 outline-none">
            <input x-model="paymentForm.client_phone" type="text" placeholder="Teléfono / WhatsApp (recomendado)"
                   class="w-full text-sm border border-amber-200 rounded-lg px-3 py-2 bg-white focus:ring-2 focus:ring-amber-400 outline-none">
            <input x-model="paymentForm.notes" type="text" placeholder="Notas (opcional)"
                   class="w-full text-xs border border-amber-200 rounded-lg px-3 py-1.5 bg-white focus:ring-2 focus:ring-amber-400 outline-none">
            <p x-show="quoteNameMissing" class="text-[11px] text-red-600 font-semibold">Escribe el nombre del cliente para generar la cotización.</p>
        </div>

        {{-- Bloque cliente/mesa en modo VENTA (opcional, colapsable) --}}
        <template x-if="mode==='sale'">
            <div class="space-y-1.5">
                {{-- Mesa --}}
                <div x-show="paymentForm.table_number || showClientFields"
                     class="flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M10 5v14M14 5v14"/>
                    </svg>
                    <label class="text-xs font-semibold text-amber-700 whitespace-nowrap">Mesa Nº</label>
                    <input x-model="paymentForm.table_number" type="number" min="1" placeholder="—"
                           class="w-16 text-sm font-black text-center border border-amber-300 bg-white rounded-lg px-2 py-1 focus:ring-2 focus:ring-amber-400 outline-none">
                    <span class="text-[11px] text-amber-600 ml-auto" x-show="paymentForm.table_number">Pedido irá a cocina</span>
                </div>
                {{-- Datos cliente --}}
                <div x-show="showClientFields" class="space-y-1">
                    <div class="flex gap-1.5 relative">
                        {{-- Se escribe el nombre O el documento: si el cliente
                             ya esta registrado se elige de la lista y la venta
                             queda a su nombre (historial y deuda), en vez de
                             crear un texto suelto cada vez. --}}
                        <input x-model="paymentForm.client_name" type="text" placeholder="Nombre o RUC/DNI del cliente"
                               @input.debounce.300ms="buscarCliente()" @focus="buscarCliente()"
                               @keydown.escape="clientesSug = []"
                               @click.outside="clientesSug = []"
                               autocomplete="off"
                               class="flex-1 text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-indigo-500 outline-none">
                        <div x-show="clientesSug.length" x-cloak
                             class="absolute z-40 left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                            <template x-for="c in clientesSug" :key="c.id">
                                <button type="button" @mousedown.prevent="elegirCliente(c)"
                                        class="w-full text-left px-2.5 py-1.5 text-xs hover:bg-indigo-50 border-b border-gray-50 last:border-0">
                                    <span class="font-semibold text-gray-900" x-text="c.nombre"></span>
                                    <span class="text-gray-400" x-text="[c.doc_numero, c.telefono].filter(Boolean).join(' · ')"></span>
                                </button>
                            </template>
                        </div>
                        <input x-model="paymentForm.client_phone" type="text" placeholder="Teléfono"
                               class="w-28 text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <input x-model="paymentForm.notes" type="text" placeholder="Notas"
                           class="w-full text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <button @click="showClientFields=!showClientFields"
                        class="text-[11px] text-gray-400 hover:text-indigo-500 transition flex items-center gap-1">
                    <svg class="w-3 h-3 transition-transform" :class="showClientFields?'rotate-180':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    <span x-text="showClientFields ? 'Ocultar datos cliente' : '+ Mesa / Datos de cliente'"></span>
                </button>
            </div>
        </template>

        {{-- Botón principal: Cobrar (venta) o Generar cotización --}}
        <button @click="mode==='quote' ? createQuote() : charge()"
                :disabled="cart.length === 0 || processing || hasBelowMin || quoteNameMissing || (mode==='sale' && (!paymentForm.method || (splitPayment && Math.abs((paymentForm.amount1+paymentForm.amount2)-cartTotal)>=0.01)))"
                :class="cart.length === 0 || processing || hasBelowMin || quoteNameMissing || (mode==='sale' && (!paymentForm.method || (splitPayment && Math.abs((paymentForm.amount1+paymentForm.amount2)-cartTotal)>=0.01)))
                    ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
                    : (mode==='quote'
                        ? 'bg-amber-500 hover:bg-amber-600 text-white shadow-lg shadow-amber-200 active:scale-[0.98]'
                        : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-200 active:scale-[0.98]')"
                class="w-full py-2.5 rounded-2xl font-black text-base transition-all flex items-center justify-center gap-2">
            <template x-if="!processing">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="mode==='quote' ? 'Generar cotización' : 'Cobrar'"></span> S/ <span x-text="cartTotal.toFixed(2)"></span>
                </span>
            </template>
            <template x-if="processing">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    Procesando...
                </span>
            </template>
        </button>
    </div>

    {{-- ─── Historial del día (panel colapsable) ─────────────────────────── --}}
    <div class="border-t border-gray-200 flex-shrink-0">
        <button @click="historialOpen = !historialOpen"
                class="w-full flex items-center justify-between px-4 py-2 bg-gray-50 hover:bg-gray-100 transition text-xs font-semibold text-gray-600">
            <span class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Historial del día
                <span class="bg-indigo-100 text-indigo-700 px-1.5 rounded-full font-black" x-text="transactions.length"></span>
                <span class="text-green-600 font-black" x-text="'S/ ' + todayTotal.toFixed(2)"></span>
            </span>
            <svg class="w-4 h-4 transition-transform" :class="historialOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="historialOpen" class="bg-white border-t border-gray-100 max-h-[280px] overflow-y-auto">
            <template x-if="transactions.length === 0">
                <p class="text-center text-gray-400 text-xs py-6">Sin ventas hoy</p>
            </template>
            {{-- Resumen por método --}}
            <template x-if="transactions.length > 0">
                <div class="px-3 py-2 flex flex-wrap gap-1.5 bg-gray-50 border-b border-gray-100">
                    <template x-for="[method, amount] in paymentSummary" :key="method">
                        <span class="text-[10px] bg-white border border-gray-200 rounded-full px-2 py-0.5 font-semibold text-gray-600"
                              x-text="method + ': S/ ' + amount.toFixed(2)"></span>
                    </template>
                </div>
            </template>
            <template x-for="t in transactions" :key="t.id">
                <div class="flex items-center gap-2 px-3 py-2 border-b border-gray-50 last:border-0 hover:bg-gray-50 transition">
                    <div class="w-7 h-7 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-800 truncate" x-text="t.client_name || 'Cliente'"></p>
                        <p class="text-[10px] text-gray-400" x-text="t.payment_method + ' · ' + t.created_at"></p>
                    </div>
                    <p class="text-xs font-black text-gray-900 flex-shrink-0" x-text="'S/ ' + parseFloat(t.total).toFixed(2)"></p>
                </div>
            </template>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL — Venta completada
══════════════════════════════════════════════════════ --}}
<div x-show="successModal" x-cloak
     class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
     {{-- Cerrar tocando fuera tambien limpia. Solo el boton "Nueva venta"
          limpiaba: al cerrar por aqui el carrito quedaba intacto y el
          siguiente cliente arrancaba con los productos del anterior, con
          riesgo de cobrarlos dos veces. --}}
     @click.self="successModal = false; clearCart()">
    <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-8 text-center" @click.stop>
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h3 class="text-2xl font-black text-gray-900 mb-1">¡Venta lista!</h3>
        <p class="text-3xl font-black text-indigo-600 my-2" x-text="'S/ ' + lastTotal.toFixed(2)"></p>
        <p class="text-gray-400 text-sm" x-text="paymentForm.method"></p>
        <template x-if="lastChange > 0">
            <div class="bg-green-50 border-2 border-green-200 rounded-2xl p-4 my-4">
                <p class="text-sm text-gray-500 mb-1">Vuelto a entregar</p>
                <p class="text-3xl font-black text-green-700" x-text="'S/ ' + lastChange.toFixed(2)"></p>
            </div>
        </template>
        <div class="flex gap-2 mt-6">
            <button @click="successModal = false; clearCart()"
                    class="flex-1 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-2xl transition text-base">
                Nueva venta
            </button>
            <button @click="printTicket()"
                    class="px-4 py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-2xl transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
            </button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     POPUP — Venta Rápida (Modo Revendedor)
     "¿Cuánto quiero cobrar y cuánto gano?"
══════════════════════════════════════════════════════ --}}
<div x-show="quickSale.open" x-cloak
     class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-end sm:items-center justify-center z-[60] p-0 sm:p-4"
     @click.self="quickSale.open = false">
    <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full max-w-sm p-5 sm:p-6" @click.stop x-show="quickSale.item">
        <template x-if="quickSale.item">
            <div>
                {{-- Nombre --}}
                <div class="flex items-start justify-between gap-2 mb-4">
                    <h3 class="text-xl font-black text-gray-900 leading-tight" x-text="quickSale.item.name"></h3>
                    <button @click="quickSale.open=false" class="text-gray-300 hover:text-gray-500 text-2xl leading-none flex-shrink-0">×</button>
                </div>

                {{-- Precio sugerido --}}
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-gray-400 font-medium">Precio sugerido</span>
                    <span class="text-gray-500 font-bold" x-text="'S/ ' + (quickSale.item.suggested ?? quickSale.item.price).toFixed(2)"></span>
                </div>

                {{-- MI PRECIO (protagonista) --}}
                <div class="rounded-2xl border-2 p-4 mb-3 transition-colors"
                     :class="{
                        'border-red-300 bg-red-50':    priceTone(quickSale.price, quickSale.item)==='blocked',
                        'border-amber-300 bg-amber-50': priceTone(quickSale.price, quickSale.item)==='low',
                        'border-yellow-200 bg-yellow-50':priceTone(quickSale.price, quickSale.item)==='mid',
                        'border-green-300 bg-green-50': priceTone(quickSale.price, quickSale.item)==='ok'
                     }">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Mi precio</p>
                    <div class="flex items-center justify-center gap-3">
                        <span class="text-2xl font-black text-gray-400">S/</span>
                        <input type="number" min="0" step="0.10" x-model.number="quickSale.price"
                               @focus="$event.target.select()"
                               class="w-32 text-center text-4xl font-black bg-transparent outline-none"
                               :class="qsBlocked ? 'text-red-600' : 'text-gray-900'">
                    </div>

                    {{-- Botones rápidos --}}
                    <div class="flex items-center justify-center gap-1.5 mt-3">
                        <button @click="qsAdjust(-5)" class="px-3 py-2 rounded-xl bg-white border border-gray-200 text-sm font-black text-gray-600 hover:bg-gray-50 active:scale-95 transition">−5</button>
                        <button @click="qsAdjust(-1)" class="px-3 py-2 rounded-xl bg-white border border-gray-200 text-sm font-black text-gray-600 hover:bg-gray-50 active:scale-95 transition">−1</button>
                        <button @click="qsUseSuggested()" class="px-3 py-2 rounded-xl bg-indigo-100 text-indigo-700 text-xs font-black hover:bg-indigo-200 active:scale-95 transition">Sugerido</button>
                        <button @click="qsAdjust(1)" class="px-3 py-2 rounded-xl bg-white border border-gray-200 text-sm font-black text-gray-600 hover:bg-gray-50 active:scale-95 transition">+1</button>
                        <button @click="qsAdjust(5)" class="px-3 py-2 rounded-xl bg-white border border-gray-200 text-sm font-black text-gray-600 hover:bg-gray-50 active:scale-95 transition">+5</button>
                    </div>

                    {{-- Slider (fácil para tercera edad) --}}
                    <template x-if="quickSale.item.min != null && quickSale.item.max != null">
                        <input type="range" :min="quickSale.item.min" :max="quickSale.item.max" step="0.5"
                               x-model.number="quickSale.price"
                               class="w-full mt-3 accent-green-600">
                    </template>
                </div>

                {{-- GANANCIA (el foco del revendedor) --}}
                <template x-if="profitUnit(quickSale.price, quickSale.item) !== null">
                    <div class="flex items-center justify-between rounded-2xl px-4 py-3 mb-3"
                         :class="qsBlocked ? 'bg-red-50' : (priceTone(quickSale.price,quickSale.item)==='low' ? 'bg-amber-50' : 'bg-green-50')">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl" x-text="qsBlocked ? '🔴' : (priceTone(quickSale.price,quickSale.item)==='low' ? '🟡' : '🟢')"></span>
                            <div>
                                <p class="text-xs text-gray-500 font-medium">Tu ganancia por unidad</p>
                                <p class="text-2xl font-black"
                                   :class="qsBlocked ? 'text-red-600' : 'text-green-700'"
                                   x-text="'+S/ ' + Math.max(0, profitUnit(quickSale.price, quickSale.item)).toFixed(2)"></p>
                            </div>
                        </div>
                        <span class="text-sm font-black px-2 py-1 rounded-lg"
                              :class="qsBlocked ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-700'"
                              x-text="(profitPct(quickSale.price, quickSale.item) ?? 0).toFixed(0) + '%'"></span>
                    </div>
                </template>

                {{-- Aviso de bloqueo por mínimo --}}
                <template x-if="qsBlocked">
                    <div class="flex items-center gap-2 bg-red-100 border border-red-200 rounded-xl px-3 py-2 mb-3">
                        <span class="text-lg">⚠️</span>
                        <p class="text-xs font-bold text-red-700 leading-snug">
                            No puedes vender por debajo de <span x-text="'S/ ' + quickSale.item.min.toFixed(2)"></span>
                        </p>
                    </div>
                </template>

                {{-- Cantidad --}}
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm font-bold text-gray-600">Cantidad</span>
                    <div class="flex items-center gap-3">
                        <button @click="quickSale.qty = Math.max(1, quickSale.qty-1)"
                                class="w-11 h-11 rounded-2xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-2xl font-black text-gray-600 active:scale-95 transition">−</button>
                        <span class="w-10 text-center text-2xl font-black" x-text="quickSale.qty"></span>
                        <button @click="quickSale.qty++"
                                class="w-11 h-11 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white flex items-center justify-center text-2xl font-black active:scale-95 transition">+</button>
                    </div>
                </div>

                {{-- Total + Agregar --}}
                <div class="flex items-center justify-between mb-3 px-1">
                    <span class="text-sm text-gray-500 font-medium">Total</span>
                    <span class="text-2xl font-black text-gray-900" x-text="'S/ ' + (quickSale.price * quickSale.qty).toFixed(2)"></span>
                </div>
                <button @click="confirmQuickSale()"
                        :disabled="qsBlocked"
                        :class="qsBlocked ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700 text-white shadow-lg shadow-green-200 active:scale-[0.98]'"
                        class="w-full py-4 rounded-2xl font-black text-lg transition-all flex items-center justify-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Agregar
                </button>
            </div>
        </template>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL — Cotización generada
══════════════════════════════════════════════════════ --}}
<div x-show="quoteModal" x-cloak
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center"
     role="dialog" aria-modal="true" aria-labelledby="cot-modal-titulo"
     @keydown.escape.window="cerrarCotizacion()">

    {{-- Fondo --}}
    <div x-show="quoteModal" @click="cerrarCotizacion()"
         x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150 motion-reduce:transition-none"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

    {{-- Panel. x-trap encierra el teclado dentro del dialogo y, al cerrarlo,
         devuelve el foco al boton que lo abrio: el plugin Focus ya estaba
         registrado en resources/js/app.js exactamente para esto. --}}
    <div x-trap.noscroll="quoteModal" x-ref="cotPanel"
         x-show="quoteModal"
         x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-150 motion-reduce:transition-none"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="relative w-full sm:max-w-md bg-white rounded-t-2xl sm:rounded-2xl shadow-xl max-h-[92vh] overflow-y-auto overscroll-contain">

        <button type="button" @click="cerrarCotizacion()"
                class="absolute top-2.5 right-2.5 w-11 h-11 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition focus:outline-none focus:ring-2 focus:ring-slate-400"
                aria-label="Cerrar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
        </button>

        <div class="px-6 pt-8 pb-6 sm:px-7">

            {{-- Confirmacion --}}
            <div class="flex flex-col items-center text-center">
                <div x-show="quoteModal"
                     x-transition:enter="transition ease-out duration-200 delay-75 motion-reduce:transition-none"
                     x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
                     class="w-14 h-14 rounded-full bg-amber-50 flex items-center justify-center">
                    <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                </div>

                {{-- El foco entra por el titulo, no por la X: asi un lector de
                     pantalla anuncia "Cotizacion creada" al abrirse el dialogo
                     en vez de "boton cerrar", y no se ve un anillo de foco
                     sobre el unico control destructivo del modal. --}}
                <h2 id="cot-modal-titulo" x-ref="cotTitulo" tabindex="-1" autofocus
                    class="mt-4 text-xl font-semibold text-slate-900 tracking-tight focus:outline-none">
                    Cotización creada
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    <span x-text="cot.number"></span>
                    <span x-show="cot.age"> · <span x-text="cot.age"></span></span>
                </p>

                <p class="mt-4 text-3xl font-bold text-amber-600 tabular-nums tracking-tight"
                   x-text="cot.currency + ' ' + lastTotal.toFixed(2)"></p>
            </div>

            {{-- Ficha del documento --}}
            <div class="mt-5 rounded-xl border border-slate-200 divide-y divide-slate-100">
                <div class="flex items-center gap-2.5 px-4 py-3">
                    <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0"/>
                    </svg>
                    <p class="text-sm text-slate-600 min-w-0 truncate">
                        <template x-if="cot.client_name">
                            <span>Cliente: <span class="font-medium text-slate-900" x-text="cot.client_name"></span></span>
                        </template>
                        <template x-if="!cot.client_name">
                            <span class="text-slate-400">Cliente no registrado</span>
                        </template>
                    </p>
                </div>

                <div class="flex items-center gap-2.5 px-4 py-3">
                    <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                    </svg>
                    <p class="text-sm text-slate-600">
                        <span x-text="cot.items_count + (cot.items_count === 1 ? ' producto' : ' productos')"></span>
                        <span x-show="cot.valid_until"> · Vence <span x-text="cot.valid_until"></span></span>
                    </p>
                </div>

                <div class="px-4 py-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                          :class="cot.shared ? 'border-slate-200 bg-slate-50 text-slate-600' : 'border-amber-200 bg-amber-50 text-amber-700'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        <span x-text="cot.shared ? 'Compartida por WhatsApp' : cot.status_label"></span>
                    </span>
                </div>
            </div>

            {{-- Accion principal --}}
            <a x-ref="cotWhatsapp"
               :href="'https://wa.me/' + waDigits(paymentForm.client_phone) + '?text=' + encodeURIComponent('Le comparto su cotización ' + cot.number + ': ' + lastQuoteUrl)"
               target="_blank" rel="noopener"
               @click="cot.shared = true"
               class="mt-5 w-full h-12 px-4 rounded-xl bg-[#25D366] hover:bg-[#1eb955] text-white font-semibold text-sm flex items-center justify-center gap-2 transition focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.71.306 1.263.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                Enviar por WhatsApp
            </a>

            {{-- Acciones secundarias. Solo se pintan si el usuario puede entrar
                 a Cotizaciones: un boton que garantiza 403 no es una accion,
                 es una trampa. El servidor devuelve esas URL en null si no. --}}
            <div class="mt-2.5 grid grid-cols-2 gap-2.5" x-show="cot.view_url || cot.pdf_url">
                <a x-show="cot.view_url" :href="cot.view_url" target="_blank" rel="noopener"
                   class="h-11 px-3 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-medium text-sm flex items-center justify-center gap-2 transition min-w-0 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                    <span class="truncate">Ver cotización</span>
                </a>
                <a x-show="cot.pdf_url" :href="cot.pdf_url" target="_blank" rel="noopener"
                   class="h-11 px-3 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-medium text-sm flex items-center justify-center gap-2 transition min-w-0 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="w-4 h-4 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75h6m-6 3h3M9.75 3.104A2.25 2.25 0 0 0 8.25 3H5.625c-.621 0-1.125.504-1.125 1.125v15.75c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V9.75a2.25 2.25 0 0 0-.659-1.591l-4.5-4.5A2.25 2.25 0 0 0 12.75 3H9.75Z"/>
                    </svg>
                    <span class="truncate">Descargar PDF</span>
                </a>
            </div>

            {{-- Enlace publico, en segundo plano --}}
            <div class="mt-4 flex items-center gap-2 rounded-lg bg-slate-50 border border-slate-200 pl-3 pr-1.5 py-1.5">
                <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/>
                </svg>
                <input type="text" readonly x-ref="cotEnlace" :value="lastQuoteUrl"
                       @focus="$event.target.select()" aria-label="Enlace público de la cotización"
                       class="flex-1 min-w-0 bg-transparent border-0 p-0 text-xs text-slate-500 outline-none focus:ring-0 truncate">
                <button type="button" @click="copyQuoteLink()"
                        class="flex-shrink-0 h-8 px-3 rounded-md text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        :class="copied ? 'bg-emerald-50 text-emerald-700' : 'bg-white border border-slate-300 text-indigo-600 hover:bg-indigo-50'"
                        x-text="copied ? 'Copiado' : 'Copiar'"></button>
            </div>
            <p x-show="copyManual" x-cloak class="mt-1.5 text-xs text-slate-500">
                Tu navegador no permitió copiar automáticamente. El enlace ya está seleccionado: pulsa Ctrl+C.
            </p>

            {{-- Continuar trabajando --}}
            <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                <button type="button" @click="nuevaCotizacion()"
                        class="text-sm font-medium text-slate-500 hover:text-slate-800 transition py-1 focus:outline-none focus:ring-2 focus:ring-slate-400 rounded">
                    Crear otra cotización
                </button>
                <a x-show="cot.list_url" :href="cot.list_url"
                   class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition py-1 inline-flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded">
                    Ir a cotizaciones
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL — Historial transacciones
══════════════════════════════════════════════════════ --}}
<div x-show="showTransactions" x-cloak
     class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-end sm:items-center justify-center z-50 p-4"
     @click.self="showTransactions = false">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[80vh] flex flex-col" @click.stop>
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div>
                <h3 class="font-black text-gray-900">Transacciones de hoy</h3>
                <p class="text-xs text-gray-400" x-text="`${transactions.length} ventas · Total: S/ ${todayTotal.toFixed(2)}`"></p>
            </div>
            <button @click="showTransactions = false" class="text-gray-400 hover:text-gray-600 p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-2">
            <template x-if="transactions.length === 0">
                <p class="text-center text-gray-400 text-sm py-8">Sin transacciones hoy</p>
            </template>
            <template x-for="t in transactions" :key="t.id">
                <div class="flex items-center gap-3 py-2.5 border-b border-gray-50">
                    <div class="w-9 h-9 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800" x-text="t.client_name || 'Cliente'"></p>
                        <p class="text-xs text-gray-400" x-text="t.payment_method + ' · ' + t.created_at"></p>
                    </div>
                    <p class="text-sm font-black text-gray-900" x-text="'S/ ' + parseFloat(t.total).toFixed(2)"></p>
                </div>
            </template>
        </div>
        <div class="px-6 py-4 bg-gray-50 rounded-b-3xl">
            <p class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide">Por método de pago</p>
            <div class="grid grid-cols-2 gap-2">
                <template x-for="[method, amount] in paymentSummary" :key="method">
                    <div class="bg-white rounded-xl px-3 py-2.5 flex justify-between items-center border border-gray-100">
                        <span class="text-xs text-gray-600" x-text="method"></span>
                        <span class="text-sm font-black text-gray-800" x-text="'S/ ' + amount.toFixed(2)"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

{{-- Tab bar móvil --}}
<div class="md:hidden fixed bottom-0 left-0 right-0 flex border-t border-gray-200 bg-white z-50" style="padding-bottom: env(safe-area-inset-bottom)">
    <button @click="posTab='catalog'" class="flex-1 py-3 text-xs font-semibold flex flex-col items-center gap-1 transition"
            :class="posTab==='catalog' ? 'text-indigo-600' : 'text-gray-400'">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
        Catálogo
    </button>
    <button @click="posTab='cart'" class="flex-1 py-3 text-xs font-semibold flex flex-col items-center gap-1 relative transition"
            :class="posTab==='cart' ? 'text-indigo-600' : 'text-gray-400'">
        <div class="relative">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span x-show="cart.length > 0"
                  class="absolute -top-2 -right-2 w-5 h-5 bg-indigo-600 text-white text-[10px] font-black rounded-full flex items-center justify-center"
                  x-text="cart.reduce((s,i)=>s+i.qty,0)"></span>
        </div>
        Carrito
        <span x-show="cartTotal > 0" class="text-[10px] font-black text-indigo-600" x-text="'S/ ' + cartTotal.toFixed(2)"></span>
    </button>
</div>

</div>

<style>
    /* Nombre del producto en la tarjeta: tres lineas a la vista y, al pasar
       el raton o tocar, se despliega entero encima de la tarjeta. Antes se
       cortaba a dos lineas y "DISCO DE CORTE METAL NORTON 7 x 1/8" y
       "... 1/2 x 1/16" se veian iguales. */
    .pos-nombre { font-size: 11.5px; line-height: 1.25; min-height: 3.75em; display: -webkit-box;
                  -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; word-break: break-word; }
    .group:hover .pos-nombre, .group:focus-within .pos-nombre { -webkit-line-clamp: unset; overflow: visible; }
</style>
<script>
/* Buscador de clientes: cada cara tiene su ruta (el panel y Ventas llevan
   sesiones distintas, y la del otro portal rebota al login). */
const RUTA_CLIENTES = @json(
    ($portalLayout ?? 'panel') === 'comercial'
        ? (\Illuminate\Support\Facades\Route::has('bixosales.facturas.clientes') ? route('bixosales.facturas.clientes') : '')
        : (\Illuminate\Support\Facades\Route::has('invoices.clientes') ? route('invoices.clientes') : '')
);
function posApp() {
    return {
        products: @json($productsJs),
        services: @json($servicesJs),
        categories: @json($categoriesJs),
        transactions: @json($transactionsJs),

        search: '',
        filterCat: null,
        filterSub: null,     // subcategoria dentro de filterCat
        filterBrand: null,   // marca (texto), se interseca con lo anterior
        catalogTab: 'products',
        cart: [],
        mode: 'sale',          // 'sale' = venta directa | 'quote' = cotización
        resellerMode: false,   // "Vender fácil" — experiencia enfocada en el revendedor
        vista: 'grid',         // 'grid' | 'lista' — formato del catalogo
        quickSale: { open: false, item: null, price: 0, qty: 1 },  // popup Venta Rápida
        processing: false,
        huellaVenta: '',     // idempotencia: una venta, un cobro
        clientesSug: [],     // clientes ya registrados que coinciden
        showCustom: false,
        showClientFields: false,
        showTransactions: false,
        successModal: false,
        quoteModal: false,
        lastQuoteUrl: '',
        copyManual: false,
        // Lo que el servidor confirmo haber guardado. La confirmacion se pinta
        // de aqui, nunca del carrito en memoria, para que no ensene un
        // documento distinto del que se acaba de emitir. `shared` es lo unico
        // que vive solo en el navegador: sabemos que se pulso el boton de
        // WhatsApp, no que el cliente lo haya recibido, asi que no se guarda
        // como estado del documento ni se llama "Enviada".
        cot: {
            number: '', client_name: '', items_count: 0, valid_until: '',
            status: '', status_label: 'Pendiente de envío', currency: 'S/',
            view_url: null, pdf_url: null, list_url: null,
            created_ts: 0, age: '', shared: false,
        },
        cotReloj: null,
        copied: false,
        lastTotal: 0,
        lastChange: 0,
        splitPayment: false,
        historialOpen: false,
        customItem: { name: '', price: 0, qty: 1 },
        quickAmounts: [10, 20, 50, 100, 200],
        paymentForm: {
            method: '',
            method2: '',
            amount1: 0,
            amount2: 0,
            received: 0,
            client_id: null,      // cliente ya registrado, si se eligio uno
            client_name: '',
            client_phone: '',
            notes: '',
            table_number: '',
        },

        init() {
            @if($paymentMethods->isNotEmpty())
            this.paymentForm.method = '{{ $paymentMethods->first() }}';
            @else
            this.paymentForm.method = 'Efectivo';
            @endif
            // Recordar preferencia de "Vender fácil"
            // Sin boton para apagarlo, un 'ON' viejo en localStorage dejaria
            // al cajero atrapado en un modo que ya no puede ver ni cambiar.
            this.resellerMode = @json($modRevendedor) && localStorage.getItem('pos_reseller_mode') === '1';
            this.vista = localStorage.getItem('pos_vista') === 'lista' ? 'lista' : 'grid';
            this.recuperarCarrito();
            // Cada cambio del carrito se respalda: una recarga, un toque atras
            // o que el navegador descarte la pestaña ya no borran la venta a
            // medio armar con el cliente delante.
            this.$watch('cart', () => this.guardarCarrito());
            this.$watch('resellerMode', v => localStorage.setItem('pos_reseller_mode', v ? '1' : '0'));
            if (this.products.length === 0 && this.services.length > 0) {
                this.catalogTab = 'services';
            }
            // Leer mesa desde URL ?mesa=N (viene del Mapa de Mesas)
            const urlMesa = new URLSearchParams(window.location.search).get('mesa');
            if (urlMesa) {
                this.paymentForm.table_number = urlMesa;
                this.showClientFields = true;
            }
        },

        /* ── Filtros: categoria -> subcategoria -> marca, intersecados ────
           Las categorias raiz van en chips; si la elegida tiene hijas, salen
           debajo. Las marcas que se ofrecen son SOLO las presentes en lo ya
           filtrado, con su conteo, para no ofrecer chips que dejan la lista
           vacia. La busqueda mira nombre, SKU y marca. */
        get catRaices() {
            return this.categories.filter(c => !c.parent_id);
        },
        get subCats() {
            return this.filterCat === null ? [] : this.categories.filter(c => c.parent_id === this.filterCat);
        },
        // Ids de categoria que cuentan: la sub elegida, o la raiz con sus hijas.
        catIdsActivos() {
            if (this.filterSub !== null) return [this.filterSub];
            if (this.filterCat === null) return null;
            return [this.filterCat, ...this.categories.filter(c => c.parent_id === this.filterCat).map(c => c.id)];
        },
        pasaTexto(p) {
            if (!this.search) return true;
            const q = this.search.toLowerCase();
            return [p.name, p.sku, p.brand].some(v => (v || '').toLowerCase().includes(q));
        },
        pasaCat(p) {
            const ids = this.catIdsActivos();
            return ids === null || ids.includes(p.cat_id);
        },
        contarCat(catId) {
            return this.cuentaEnCat(catId);
        },
        // Categorias que se ofrecen: las que tienen algo con la marca elegida.
        get catRaicesVisibles() {
            return this.catRaices.filter(c => this.cuentaEnCat(c.id) > 0 || this.filterCat === c.id);
        },
        /* Marcas que se ofrecen: las presentes en lo ya filtrado POR TEXTO Y
           CATEGORIA (no por marca, o siempre saldria una sola). Si todavia no
           hay categoria elegida, salen todas: se puede empezar por la marca. */
        get marcasDisponibles() {
            const n = {};
            let sinMarca = 0;
            this.products.filter(p => this.pasaTexto(p) && this.pasaCat(p))
                .forEach(p => { p.brand ? (n[p.brand] = (n[p.brand] || 0) + 1) : sinMarca++; });
            const lista = Object.keys(n).sort((a, b) => a.localeCompare(b, 'es')).map(nombre => ({ nombre, n: n[nombre] }));
            // Los productos sin marca cargada no pueden quedar inalcanzables.
            if (sinMarca) lista.push({ nombre: '__sin__', etiqueta: 'Sin marca', n: sinMarca });
            return lista;
        },
        /* Y al reves: las categorias se acotan a las de la marca elegida, para
           que "marca -> categoria" funcione igual de bien que "categoria ->
           marca". Sin marca elegida, salen todas. */
        cuentaEnCat(catId) {
            const ids = [catId, ...this.categories.filter(c => c.parent_id === catId).map(c => c.id)];
            return this.products.filter(p => ids.includes(p.cat_id) && this.pasaTexto(p) && this.pasaMarca(p)).length;
        },
        pasaMarca(p) {
            if (this.filterBrand === null) return true;
            return this.filterBrand === '__sin__' ? !p.brand : p.brand === this.filterBrand;
        },
        elegirCat(id) {
            this.filterCat = id; this.filterSub = null; this.ajustarMarca();
        },
        // El desplegable guarda 'c<id>' (toda la categoria) o 's<id>' (una sub).
        get valorCatSel() {
            if (this.filterSub !== null) return 's' + this.filterSub;
            return this.filterCat === null ? '' : 'c' + this.filterCat;
        },
        elegirDesdeSelect(v) {
            if (!v) return this.elegirCat(null);
            const id = Number(v.slice(1));
            if (v[0] === 'c') return this.elegirCat(id);
            const sub = this.categories.find(c => c.id === id);
            this.filterCat = sub ? sub.parent_id : null; this.filterSub = id; this.ajustarMarca();
        },
        // Si la marca elegida ya no existe en la nueva categoria, se suelta:
        // dejarla puesta daba una lista vacia sin decir por que.
        ajustarMarca() {
            if (this.filterBrand !== null && !this.marcasDisponibles.some(m => m.nombre === this.filterBrand)) this.filterBrand = null;
        },
        // Al elegir marca: si la categoria puesta no tiene nada de esa marca,
        // se suelta la categoria, no la marca. Manda lo ultimo que se toco.
        ajustarCat() {
            if (this.filterSub !== null && this.cuentaEnCat(this.filterSub) === 0) this.filterSub = null;
            if (this.filterCat !== null && this.cuentaEnCat(this.filterCat) === 0) { this.filterCat = null; this.filterSub = null; }
        },
        limpiarFiltros() {
            this.search = ''; this.filterCat = null; this.filterSub = null; this.filterBrand = null;
        },
        get filteredProducts() {
            return this.products.filter(p => this.pasaTexto(p) && this.pasaCat(p) && this.pasaMarca(p));
        },

        get filteredServices() {
            return this.services.filter(s => {
                const q = !this.search || s.name.toLowerCase().includes(this.search.toLowerCase());
                const ids = this.catIdsActivos();
                return q && (ids === null || ids.includes(s.cat_id));
            });
        },

        itemDiscount(item) {
            const d = item.discount || 0;
            if (item.discountType === 'fixed') return Math.min(d, item.price * item.qty);
            return (item.price * item.qty) * (d / 100);
        },

        itemTotal(item) {
            return Math.max(0, item.price * item.qty - this.itemDiscount(item));
        },

        get totalDiscount() {
            return this.cart.reduce((s, i) => s + this.itemDiscount(i), 0);
        },

        get cartTotal() {
            return this.cart.reduce((s, i) => s + this.itemTotal(i), 0);
        },

        // Ganancia total del carrito (suma de (precio−costo)×cantidad, solo ítems con costo).
        get cartProfit() {
            return this.cart.reduce((s, i) => {
                if (i.cost == null) return s;
                return s + (i.price - i.cost) * i.qty;
            }, 0);
        },
        // ¿Hay algún ítem por debajo de su precio mínimo? (bloquea la venta)
        get hasBelowMin() {
            return this.cart.some(i => i.min != null && i.price < i.min);
        },
        // En cotización el nombre del cliente es obligatorio (para saber a quién enviarla).
        get quoteNameMissing() {
            return this.mode === 'quote' && !(this.paymentForm.client_name || '').trim();
        },

        get todayTotal() {
            return this.transactions.reduce((s, t) => s + parseFloat(t.total), 0);
        },

        get paymentSummary() {
            const map = {};
            this.transactions.forEach(t => {
                const m = t.payment_method;
                map[m] = (map[m] || 0) + parseFloat(t.total);
            });
            return Object.entries(map);
        },

        cartQty(itemId, type) {
            const key = type + '_' + itemId;
            const item = this.cart.find(i => i._key === key);
            return item ? item.qty : 0;
        },

        addToCart(item) {
            const key = (item.type || 'product') + '_' + item.id;
            const existing = this.cart.find(i => i._key === key);
            if (existing) {
                existing.qty++;
            } else {
                this.cart.push({
                    _key: key,
                    product_id:   item.type === 'service' ? null : item.id,
                    service_id:   item.type === 'service' ? item.id : null,
                    type:         item.type || 'product',
                    name:         item.name,
                    price:        item.suggested ?? item.price,
                    basePrice:    item.suggested ?? item.price,   // precio sugerido del catálogo
                    cost:         item.cost ?? null,
                    min:          item.min ?? null,
                    max:          item.max ?? null,
                    qty:          1,
                    discount:     0,
                    discountType: 'pct',
                });
            }
        },

        // ── Ganancia / semáforo (para el revendedor) ──────────────────────
        // Ganancia por unidad = precio de venta − costo. Si no hay costo, null.
        profitUnit(price, item) {
            if (!item || item.cost == null) return null;
            return price - item.cost;
        },
        profitPct(price, item) {
            if (!item || item.cost == null || item.cost <= 0) return null;
            return ((price - item.cost) / item.cost) * 100;
        },
        // Semáforo: rojo bajo el mínimo, amarillo poca ganancia, verde buena.
        priceTone(price, item) {
            if (!item) return 'ok';
            if (item.min != null && price < item.min) return 'blocked';
            const pct = this.profitPct(price, item);
            if (pct == null) return 'ok';
            if (pct < 5)  return 'low';
            if (pct < 15) return 'mid';
            return 'ok';
        },

        // ── Popup Venta Rápida (Modo Revendedor) ──────────────────────────
        openQuickSale(item) {
            this.quickSale = {
                open: true,
                item: item,
                price: item.suggested ?? item.price,
                qty: 1,
            };
        },
        qsAdjust(delta) {
            const it = this.quickSale.item;
            let np = Math.round((this.quickSale.price + delta) * 100) / 100;
            if (it && it.min != null && np < it.min) np = it.min;   // no bajar del mínimo
            if (it && it.max != null && np > it.max) np = it.max;   // no pasar el máximo
            this.quickSale.price = Math.max(0, np);
        },
        qsUseSuggested() {
            const it = this.quickSale.item;
            this.quickSale.price = it.suggested ?? it.price;
        },
        get qsBlocked() {
            const it = this.quickSale.item;
            return it && it.min != null && this.quickSale.price < it.min;
        },
        confirmQuickSale() {
            if (this.qsBlocked) return;
            const it = this.quickSale.item;
            const key = (it.type || 'product') + '_' + it.id;
            const existing = this.cart.find(i => i._key === key);
            if (existing) {
                existing.price = this.quickSale.price;
                existing.qty  += this.quickSale.qty;
            } else {
                this.cart.push({
                    _key: key,
                    product_id: it.type === 'service' ? null : it.id,
                    service_id: it.type === 'service' ? it.id : null,
                    type: it.type || 'product',
                    name: it.name,
                    price: this.quickSale.price,
                    basePrice: it.suggested ?? it.price,
                    cost: it.cost ?? null,
                    min: it.min ?? null,
                    max: it.max ?? null,
                    qty: this.quickSale.qty,
                    discount: 0,
                    discountType: 'pct',
                });
            }
            this.quickSale.open = false;
            // En móvil, saltar al carrito para revisar/cobrar
        },

        addCustomItem() {
            if (!this.customItem.name || this.customItem.price <= 0) return;
            this.cart.push({ _key: 'custom_' + Date.now(), product_id: null, service_id: null, type: 'custom', name: this.customItem.name, price: this.customItem.price, qty: this.customItem.qty || 1, discount: 0, discountType: 'pct' });
            this.customItem = { name: '', price: 0, qty: 1 };
            this.showCustom = false;
        },

        increaseQty(idx) { this.cart[idx].qty++; },
        decreaseQty(idx) {
            if (this.cart[idx].qty <= 1) this.removeFromCart(idx);
            else this.cart[idx].qty--;
        },
        removeFromCart(idx) { this.cart.splice(idx, 1); },
        clearCart() { this.cart = []; this.paymentForm.received = 0; this.showCustom = false; },

        /* Vaciar a peticion del usuario SI pregunta: el boton vive junto al
           ticket armado, con el cliente delante, y un toque de mas perdia todo
           el trabajo. Venta Express ya lo hacia asi. `clearCart()` se sigue
           usando tal cual despues de cobrar, donde no hay nada que preguntar. */
        async vaciarCarrito() {
            if (!this.cart.length) return;
            if (typeof bxConfirmar === 'function') {
                const ok = await bxConfirmar({
                    titulo: 'Vaciar el carrito',
                    descripcion: 'Se quitarán todos los productos del ticket. ¿Continuar?',
                    boton: 'Vaciar',
                });
                if (! ok) return;
            }
            this.clearCart();
        },

        // Un solo canal para hablar con el servidor desde el mostrador.
        // Antes, cualquier fallo se resumia en "Error al registrar la venta" o
        // "No se pudo generar la cotizacion": el cajero no podia distinguir una
        // sesion caducada (419, se arregla recargando) de una falta de permiso
        // (403) o de un limite de stock (422). Ahora el motivo se dice.
        async postJson(url, payload, huella) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                    'Accept': 'application/json',
                    // Huella del intento: el servidor devuelve la MISMA venta
                    // si la peticion se repite, en vez de cobrar dos veces.
                    ...(huella ? { 'X-Idempotencia': huella } : {}),
                },
                body: JSON.stringify(payload),
            });
            let data = null;
            try { data = await res.json(); } catch (e) { data = null; }
            if (res.ok && data && data.ok) return data;

            if (res.status === 419 || res.status === 401) {
                throw new Error('Tu sesión expiró. Recarga la página (F5), vuelve a iniciar sesión y repite la operación. El carrito no se pierde si no cierras la pestaña.');
            }
            if (res.status === 403) {
                throw new Error((data && (data.error || data.message)) || 'No tienes permiso para esta acción.');
            }
            if (res.status === 422 && data && data.errors) {
                throw new Error(Object.values(data.errors).flat().join('\n'));
            }
            throw new Error((data && (data.error || data.message)) || ('El servidor respondió ' + res.status + '.'));
        },

        async charge() {
            if (this.cart.length === 0 || !this.paymentForm.method || this.processing) return;
            if (this.splitPayment && Math.abs((this.paymentForm.amount1 + this.paymentForm.amount2) - this.cartTotal) >= 0.01) return;

            /* En efectivo, el dinero tiene que alcanzar. La pantalla ya pintaba
               "Falta S/ X" en rojo, pero el boton cobraba igual: la venta se
               registraba como pagada con menos dinero del debido y la caja
               cuadraba mal al cierre. Venta Express ya lo bloqueaba. */
            if (!this.splitPayment && this.paymentForm.method === 'Efectivo'
                && (Number(this.paymentForm.received) || 0) + 0.005 < this.cartTotal) {
                const falta = (this.cartTotal - (Number(this.paymentForm.received) || 0)).toFixed(2);
                bxAviso('El efectivo recibido no cubre el total: faltan S/ ' + falta + '.', 'error');
                return;
            }
            // Una huella POR VENTA: se conserva mientras el cobro se reintenta
            // y se renueva al vaciar el carrito.
            if (!this.huellaVenta) this.huellaVenta = 'v' + Date.now() + Math.random().toString(36).slice(2, 8);
            this.processing = true;
            const payMethod = this.splitPayment
                ? `${this.paymentForm.method} (S/${this.paymentForm.amount1.toFixed(2)}) + ${this.paymentForm.method2} (S/${this.paymentForm.amount2.toFixed(2)})`
                : this.paymentForm.method;
            try {
                /* La ruta la decide el controlador segun la cara por la que se
                   entro (Ventas, panel o portal): escrita a mano aqui, el POS
                   de Ventas cobraba contra la ruta del panel, que resuelve el
                   negocio por otra clave de sesion. */
                const data = await this.postJson(@js($posStoreRoute ?? route('pos.store')), {
                        client_id:      this.paymentForm.client_id || null,
                        client_name:    this.paymentForm.client_name || null,
                        client_phone:   this.paymentForm.client_phone || null,
                        payment_method: payMethod,
                        /* COBRAR ES COBRAR. Sin este dato el servidor guardaba
                           TODA venta de mostrador como `pending`: aparecia en
                           Cuentas por Cobrar como deuda aunque el cliente
                           hubiera pagado en efectivo y se le hubiera dado
                           vuelto, y el asiento del libro —que solo se escribe
                           si viene `paid`— no llegaba a registrarse nunca.
                           Venta Express ya lo mandaba bien. */
                        paid:           true,
                        notes:          this.paymentForm.notes || null,
                        table_number:   this.paymentForm.table_number || null,
                        order_type:     this.paymentForm.table_number ? 'mesa' : null,
                        items: this.cart.map(i => ({
                            product_id: i.product_id || null,
                            service_id: i.service_id || null,
                            name:       i.name,
                            price:      this.itemTotal(i) / i.qty,
                            quantity:   i.qty,
                        })),
                }, this.huellaVenta);
                {
                    this.lastTotal  = parseFloat(data.total);
                    this.lastChange = (!this.splitPayment && this.paymentForm.method === 'Efectivo')
                        ? Math.max(0, this.paymentForm.received - this.lastTotal) : 0;

                    // Actualizar stock reactivamente en la grilla de productos
                    if (data.stock_update) {
                        data.stock_update.forEach(u => {
                            const prod = this.products.find(p => p.id === u.product_id);
                            if (prod) prod.stock = u.stock;
                        });
                    }

                    this.transactions.unshift({
                        id:             data.order.id,
                        client_name:    data.order.client_name || 'Cliente',
                        payment_method: data.order.payment_method,
                        total:          data.order.total,
                        created_at:     new Date().toLocaleTimeString('es-PE', {hour:'2-digit',minute:'2-digit'}),
                    });
                    this.successModal = true;
                    this.olvidarCarrito();
                    this.huellaVenta = '';   // la siguiente venta, huella nueva
                    this.paymentForm.client_id     = null;
                    this.paymentForm.client_name   = '';
                    this.paymentForm.client_phone  = '';
                    this.paymentForm.notes         = '';
                    this.paymentForm.received      = 0;
                    this.paymentForm.amount1       = 0;
                    this.paymentForm.amount2       = 0;
                    this.paymentForm.table_number  = '';
                    this.showClientFields = false;
                    this.splitPayment = false;
                }
            } catch (e) {
                bxAviso(e.message || 'Error de conexión.', 'error');
            }
            this.processing = false;
        },

        /* ── Cliente registrado ───────────────────────────────────────────
           La venta se ata al cliente (`client_id`), no a un texto suelto: asi
           entra en su historial y en su cuenta por cobrar. Si no existe, el
           nombre tecleado se guarda igual y no estorba. */
        async buscarCliente() {
            const q = (this.paymentForm.client_name || '').trim();
            this.paymentForm.client_id = null;   // al reescribir, deja de ser el elegido
            if (q.length < 2 || !RUTA_CLIENTES) { this.clientesSug = []; return; }
            try {
                const res = await fetch(RUTA_CLIENTES + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.clientesSug = (data.clientes || []).slice(0, 6);
            } catch (e) { this.clientesSug = []; }
        },

        elegirCliente(c) {
            this.paymentForm.client_id    = c.id;
            this.paymentForm.client_name  = c.nombre;
            this.paymentForm.client_phone = c.telefono || this.paymentForm.client_phone;
            this.clientesSug = [];
        },

        /* ── Respaldo del carrito ─────────────────────────────────────────
           No es un pedido guardado: es una red para que nada se pierda entre
           que se arma la venta y se cobra. Se limpia al cobrar y caduca a las
           12 horas (un carrito de ayer ya no sirve). */
        claveCarrito() { return 'pos_carrito_' + @json($project->id ?? 0); },

        guardarCarrito() {
            try {
                if (!this.cart.length) return localStorage.removeItem(this.claveCarrito());
                localStorage.setItem(this.claveCarrito(), JSON.stringify({ cart: this.cart, cuando: Date.now() }));
            } catch (e) { /* modo privado o sin espacio: se sigue sin red */ }
        },

        recuperarCarrito() {
            try {
                const crudo = localStorage.getItem(this.claveCarrito());
                if (!crudo) return;
                const g = JSON.parse(crudo);
                if (!g || !Array.isArray(g.cart) || !g.cart.length) return;
                if ((Date.now() - (g.cuando || 0)) / 3600000 > 12) return this.olvidarCarrito();
                this.cart = g.cart;
                bxAviso('Recuperamos la venta que estabas armando.', 'exito');
            } catch (e) { /* respaldo ilegible: se ignora */ }
        },

        olvidarCarrito() {
            try { localStorage.removeItem(this.claveCarrito()); } catch (e) {}
        },

        setVista(v) {
            this.vista = v;
            try { localStorage.setItem('pos_vista', v); } catch (e) {}
        },

        async createQuote() {
            if (this.cart.length === 0 || this.processing) return;
            this.processing = true;
            try {
                const data = await this.postJson(@js($posQuoteRoute ?? route('pos.quote')), {
                        client_name:  this.paymentForm.client_name || null,
                        client_phone: this.paymentForm.client_phone || null,
                        notes:        this.paymentForm.notes || null,
                        items: this.cart.map(i => ({
                            name:     i.name,
                            price:    this.itemTotal(i) / i.qty,
                            quantity: i.qty,
                        })),
                });
                this.lastTotal    = parseFloat(data.total);
                this.lastQuoteUrl = data.url;
                this.cot = {
                    number:       data.number || '',
                    client_name:  data.client_name || '',
                    items_count:  data.items_count || 0,
                    valid_until:  data.valid_until || '',
                    status:       data.status || '',
                    status_label: data.status_label || 'Pendiente de envío',
                    currency:     data.currency || 'S/',
                    view_url:     data.view_url || null,
                    pdf_url:      data.pdf_url || null,
                    list_url:     data.list_url || null,
                    created_ts:   data.created_ts || 0,
                    age:          'hace unos segundos',
                    shared:       false,
                };
                this.copied = false;
                this.copyManual = false;
                this.abrirCotizacion();
            } catch (e) {
                bxAviso(e.message || 'Error de conexión.', 'error');
            }
            this.processing = false;
        },

        // Si el cajero anoto el celular, WhatsApp abre la conversacion con ese
        // cliente en vez del selector de contactos. Nueve digitos = celular
        // peruano, que es lo que se teclea en el mostrador.
        waDigits(phone) {
            const d = (phone || '').replace(/\D/g, '');
            if (!d) return '';
            return d.length === 9 ? '51' + d : d;
        },

        abrirCotizacion() {
            this.quoteModal = true;
            this.$nextTick(() => this.$refs.cotTitulo?.focus());
            // "hace unos segundos" tiene que dejar de ser verdad cuando deja de
            // serlo: el reloj se para al cerrar para no dejar timers colgando.
            clearInterval(this.cotReloj);
            this.cotReloj = setInterval(() => { this.cot.age = this.edadCotizacion(); }, 20000);
        },

        edadCotizacion() {
            if (!this.cot.created_ts) return '';
            const s = Math.max(0, Math.floor(Date.now() / 1000) - this.cot.created_ts);
            if (s < 60)   return 'hace unos segundos';
            const m = Math.floor(s / 60);
            if (m < 60)   return m === 1 ? 'hace 1 minuto' : 'hace ' + m + ' minutos';
            const h = Math.floor(m / 60);
            return h === 1 ? 'hace 1 hora' : 'hace ' + h + ' horas';
        },

        cerrarCotizacion() {
            if (!this.quoteModal) return;
            this.quoteModal = false;
            clearInterval(this.cotReloj);
            this.cotReloj = null;
        },

        // "Crear otra": cierra la confirmacion y deja el mostrador listo para la
        // siguiente. NO reenvia nada ni toca la cotizacion ya creada, que queda
        // guardada; solo limpia el carrito y los datos del cliente anterior.
        nuevaCotizacion() {
            this.cerrarCotizacion();
            this.clearCart();
            this.paymentForm.client_name  = '';
            this.paymentForm.client_phone = '';
            this.paymentForm.notes        = '';
            this.mode = 'quote';
        },

        async copyQuoteLink() {
            this.copyManual = false;
            try {
                await navigator.clipboard.writeText(this.lastQuoteUrl);
                this.copied = true;
                setTimeout(() => this.copied = false, 1800);
            } catch (e) {
                // Sin portapapeles (http sin TLS, permiso denegado): se deja el
                // enlace seleccionado y se dice como copiarlo, en vez de un
                // prompt del navegador que interrumpe el flujo.
                this.copyManual = true;
                this.$refs.cotEnlace?.focus();
                this.$refs.cotEnlace?.select();
            }
        },

        printTicket() {
            const w = window.open('', '_blank', 'width=300,height=500');
            w.document.write(`
                <html><head><title>Ticket</title>
                <style>body{font-family:monospace;font-size:12px;padding:16px;} h2{text-align:center;} .line{border-top:1px dashed #000;margin:8px 0;} .right{text-align:right;} .big{font-size:16px;font-weight:bold;}</style>
                </head><body>
                <h2>{{ $project->name }}</h2>
                <p style="text-align:center;font-size:11px;">${new Date().toLocaleString('es-PE')}</p>
                <div class="line"></div>
                ${this.cart.map(i=>`<div style="display:flex;justify-content:space-between"><span>${i.qty}x ${i.name}</span><span>S/ ${(i.price*i.qty).toFixed(2)}</span></div>`).join('')}
                <div class="line"></div>
                <div style="display:flex;justify-content:space-between"><span>TOTAL</span><span class="big">S/ ${this.lastTotal.toFixed(2)}</span></div>
                <p style="text-align:center;margin-top:16px;font-size:10px;">Pago: ${this.paymentForm.method}</p>
                ${this.lastChange > 0 ? `<p style="text-align:center;font-size:11px;">Vuelto: S/ ${this.lastChange.toFixed(2)}</p>` : ''}
                <div class="line"></div>
                <p style="text-align:center;font-size:10px;">Gracias por su compra</p>
                </body></html>
            `);
            w.document.close();
            w.print();
        }
    }
}
</script>
</x-portal-layout>
