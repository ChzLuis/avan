<x-portal-layout :layout="$portalLayout ?? 'panel'" :project="$project" pageTitle="POS — Caja">
<div class="flex flex-1 overflow-hidden pb-16 md:pb-0" x-data="Object.assign(posApp(), { posTab: 'catalog' })" x-init="init()" style="height:calc(100vh - 56px);">

{{-- ══════════════════════════════════════════════════════
     PANEL IZQUIERDO — Catálogo
══════════════════════════════════════════════════════ --}}
<div class="flex flex-col bg-white border-r border-gray-200 w-full md:w-[58%]"
     :class="{ 'hidden md:flex': posTab !== 'catalog' }">

    {{-- Header --}}
    <div class="flex flex-col border-b border-gray-100 flex-shrink-0">
        {{-- Tabs --}}
        <div class="flex border-b border-gray-100 px-4 pt-2 gap-4">
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
            <div class="flex gap-1.5 overflow-x-auto pb-0.5 scrollbar-none">
                <button @click="filterCat=null"
                        :class="filterCat===null ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition flex-shrink-0">Todos</button>
                <template x-for="cat in categories" :key="cat.id">
                    <button @click="filterCat = filterCat===cat.id ? null : cat.id"
                            :class="filterCat===cat.id ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition flex-shrink-0"
                            x-text="cat.name">
                    </button>
                </template>
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
            <div class="grid gap-2.5" style="grid-template-columns: repeat(auto-fill, minmax(120px,1fr));">
                <template x-for="p in filteredProducts" :key="p.id">
                    <button @click="addToCart(p)"
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
                        <p class="text-xs font-semibold text-gray-800 leading-tight line-clamp-2 min-h-[2.5rem]" x-text="p.name"></p>
                        <p class="text-sm font-black text-indigo-600 mt-1" x-text="'S/ ' + p.price.toFixed(2)"></p>
                        <template x-if="p.stock !== null && p.stock !== undefined">
                            <p class="text-[10px] mt-0.5 font-medium"
                               :class="p.stock <= 0 ? 'text-red-500' : p.stock <= 5 ? 'text-amber-500' : 'text-gray-400'"
                               x-text="p.stock <= 0 ? 'Sin stock' : 'Stock: ' + p.stock"></p>
                        </template>
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
            <div class="grid gap-2.5" style="grid-template-columns: repeat(auto-fill, minmax(120px,1fr));">
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
    <div class="flex items-center justify-between px-4 py-3 bg-white border-b border-gray-200 flex-shrink-0">
        <h2 class="font-bold text-gray-800 flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Nueva venta
            <span x-show="cart.length > 0"
                  class="text-xs bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded-full"
                  x-text="cart.reduce((s,i)=>s+i.qty,0) + ' ítem' + (cart.reduce((s,i)=>s+i.qty,0)!==1?'s':'')"></span>
        </h2>
        <button @click="clearCart()" x-show="cart.length > 0"
                class="text-xs text-gray-400 hover:text-red-500 transition flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-red-50">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Vaciar
        </button>
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
                    {{-- Nombre --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-800 leading-snug truncate" x-text="item.name"></p>
                        <p class="text-[11px] text-gray-400" x-text="'S/ ' + item.price.toFixed(2) + ' c/u'"></p>
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
                {{-- Fila descuento por ítem --}}
                <div class="flex items-center gap-1.5 pt-0.5 border-t border-gray-50">
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
                <span class="text-xs font-black whitespace-nowrap"
                      :class="paymentForm.received >= cartTotal ? 'text-green-700' : 'text-red-500'"
                      x-text="'V: S/ ' + Math.max(0, paymentForm.received - cartTotal).toFixed(2)"></span>
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

        {{-- Mesa (si viene ?mesa=N o se escribe) --}}
        <div x-show="paymentForm.table_number || showClientFields"
             class="flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M10 5v14M14 5v14"/>
            </svg>
            <label class="text-xs font-semibold text-amber-700 whitespace-nowrap">Mesa Nº</label>
            <input x-model="paymentForm.table_number" type="number" min="1" placeholder="—"
                   class="w-16 text-sm font-black text-center border border-amber-300 bg-white rounded-lg px-2 py-1 focus:ring-2 focus:ring-amber-400 outline-none">
            <span class="text-[11px] text-amber-600 ml-auto" x-show="paymentForm.table_number">
                Pedido irá a cocina
            </span>
        </div>

        {{-- Cliente (opcional) --}}
        <div x-show="showClientFields" class="space-y-1">
            <div class="flex gap-1.5">
                <input x-model="paymentForm.client_name" type="text" placeholder="Nombre cliente"
                       class="flex-1 text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-indigo-500 outline-none">
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

        {{-- Botón cobrar --}}
        <button @click="charge()"
                :disabled="cart.length === 0 || !paymentForm.method || processing || (splitPayment && Math.abs((paymentForm.amount1+paymentForm.amount2)-cartTotal)>=0.01)"
                :class="cart.length > 0 && paymentForm.method && !processing && !(splitPayment && Math.abs((paymentForm.amount1+paymentForm.amount2)-cartTotal)>=0.01)
                    ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-200 active:scale-[0.98]'
                    : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                class="w-full py-2.5 rounded-2xl font-black text-base transition-all flex items-center justify-center gap-2">
            <template x-if="!processing">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Cobrar S/ <span x-text="cartTotal.toFixed(2)"></span>
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
     @click.self="successModal = false">
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

<script>
function posApp() {
    return {
        products: @json($productsJs),
        services: @json($servicesJs),
        categories: @json($categoriesJs),
        transactions: @json($transactionsJs),

        search: '',
        filterCat: null,
        catalogTab: 'products',
        cart: [],
        processing: false,
        showCustom: false,
        showClientFields: false,
        showTransactions: false,
        successModal: false,
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

        get filteredProducts() {
            return this.products.filter(p => {
                const s = !this.search || p.name.toLowerCase().includes(this.search.toLowerCase());
                const c = this.filterCat === null || p.cat_id === this.filterCat;
                return s && c;
            });
        },

        get filteredServices() {
            return this.services.filter(s => {
                const q = !this.search || s.name.toLowerCase().includes(this.search.toLowerCase());
                const c = this.filterCat === null || s.cat_id === this.filterCat;
                return q && c;
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
                    price:        item.price,
                    qty:          1,
                    discount:     0,
                    discountType: 'pct',
                });
            }
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

        async charge() {
            if (this.cart.length === 0 || !this.paymentForm.method || this.processing) return;
            if (this.splitPayment && Math.abs((this.paymentForm.amount1 + this.paymentForm.amount2) - this.cartTotal) >= 0.01) return;
            this.processing = true;
            const payMethod = this.splitPayment
                ? `${this.paymentForm.method} (S/${this.paymentForm.amount1.toFixed(2)}) + ${this.paymentForm.method2} (S/${this.paymentForm.amount2.toFixed(2)})`
                : this.paymentForm.method;
            try {
                const res = await fetch('{{ route("pos.store", $project) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        client_name:    this.paymentForm.client_name || null,
                        client_phone:   this.paymentForm.client_phone || null,
                        payment_method: payMethod,
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
                    }),
                });
                const data = await res.json();
                if (data.ok) {
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
                    this.paymentForm.client_name   = '';
                    this.paymentForm.client_phone  = '';
                    this.paymentForm.notes         = '';
                    this.paymentForm.received      = 0;
                    this.paymentForm.amount1       = 0;
                    this.paymentForm.amount2       = 0;
                    this.paymentForm.table_number  = '';
                    this.showClientFields = false;
                    this.splitPayment = false;
                } else {
                    alert('Error al registrar la venta.');
                }
            } catch (e) {
                alert('Error de conexión.');
            }
            this.processing = false;
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
