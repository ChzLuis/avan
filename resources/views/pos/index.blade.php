<x-app-layout>
<x-slot name="slot">
<div class="flex flex-1 overflow-hidden" x-data="posApp()" x-init="init()">

{{-- ══════════════════════════════════════════════════════
     PANEL IZQUIERDO — Catálogo de productos
══════════════════════════════════════════════════════ --}}
<div class="flex flex-col bg-white border-r border-gray-200" style="width:58%; min-width:0;">

    {{-- Header catálogo --}}
    <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
        <div class="flex-1 relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input x-model="search" type="text" placeholder="Buscar producto..." autocomplete="off"
                   class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none">
        </div>
        {{-- Filtro categoría --}}
        <div class="flex gap-1 overflow-x-auto pb-0.5" style="max-width:55%">
            <button @click="filterCat=null"
                    :class="filterCat===null ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition">
                Todo
            </button>
            <template x-for="cat in categories" :key="cat.id">
            <button @click="filterCat = cat.id"
                    :class="filterCat === cat.id ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition"
                    x-text="cat.name">
            </button>
        </template>
        </div>
    </div>

    {{-- Grid de productos --}}
    <div class="flex-1 overflow-y-auto p-3">
        <div class="grid grid-cols-3 gap-2" style="grid-template-columns: repeat(auto-fill, minmax(140px,1fr));">
            <template x-for="p in filteredProducts" :key="p.id">
                <button @click="addToCart(p)"
                        class="bg-white border border-gray-200 rounded-xl p-3 text-left hover:border-indigo-400 hover:shadow-md transition-all group active:scale-95">
                    {{-- Imagen o placeholder --}}
                    <div class="w-full aspect-square rounded-lg mb-2 overflow-hidden bg-gray-100 flex items-center justify-center">
                        <template x-if="p.image">
                            <img :src="p.image" :alt="p.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                        </template>
                        <template x-if="!p.image">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </template>
                    </div>
                    <p class="text-xs font-semibold text-gray-800 leading-tight line-clamp-2" x-text="p.name"></p>
                    <p class="text-sm font-bold text-indigo-600 mt-1" x-text="'S/ ' + p.price.toFixed(2)"></p>
                </button>
            </template>
            <template x-if="filteredProducts.length === 0">
                <div class="col-span-full text-center py-12 text-gray-400">
                    <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <p class="text-sm">Sin resultados</p>
                </div>
            </template>
        </div>
    </div>

    {{-- Footer: transacciones del día --}}
    <div class="border-t border-gray-100 px-4 py-2 flex items-center gap-3">
        <button @click="showTransactions = !showTransactions"
                class="text-xs text-gray-500 hover:text-indigo-600 flex items-center gap-1 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span x-text="`${transactions.length} transacciones hoy`"></span>
        </button>
        <div class="ml-auto text-xs text-gray-400">
            Total: <span class="font-semibold text-gray-700" x-text="'S/ ' + todayTotal.toFixed(2)"></span>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     PANEL DERECHO — Carrito / Cobro
══════════════════════════════════════════════════════ --}}
<div class="flex flex-col bg-gray-50" style="width:42%; min-width:280px;">

    {{-- Header carrito --}}
    <div class="flex items-center justify-between px-4 py-3 bg-white border-b border-gray-200">
        <h2 class="font-bold text-gray-800 flex items-center gap-2 text-sm">
            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Nueva venta
        </h2>
        <button @click="clearCart()" x-show="cart.length > 0"
                class="text-xs text-gray-400 hover:text-red-500 transition flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Limpiar
        </button>
    </div>

    {{-- Items del carrito --}}
    <div class="flex-1 overflow-y-auto px-3 py-2 space-y-2">
        <template x-if="cart.length === 0">
            <div class="text-center py-16 text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="text-sm">El carrito está vacío</p>
                <p class="text-xs mt-1">Selecciona productos del catálogo</p>
            </div>
        </template>

        <template x-for="(item, idx) in cart" :key="idx">
            <div class="bg-white rounded-xl border border-gray-200 px-3 py-2.5 flex items-center gap-2">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate" x-text="item.name"></p>
                    <p class="text-xs text-gray-500" x-text="'S/ ' + item.price.toFixed(2) + ' c/u'"></p>
                </div>
                {{-- Qty controls --}}
                <div class="flex items-center gap-1.5">
                    <button @click="decreaseQty(idx)"
                            class="w-6 h-6 rounded-md bg-gray-100 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                    </button>
                    <input type="number" x-model.number="item.qty" min="1" @change="if(item.qty<1)removeFromCart(idx)"
                           class="w-10 text-center text-sm font-semibold border border-gray-200 rounded-md py-0.5 focus:ring-1 focus:ring-indigo-500 outline-none">
                    <button @click="increaseQty(idx)"
                            class="w-6 h-6 rounded-md bg-gray-100 hover:bg-green-100 hover:text-green-600 flex items-center justify-center transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </button>
                </div>
                <div class="text-right" style="min-width:60px">
                    <p class="text-sm font-bold text-gray-800" x-text="'S/ ' + (item.price * item.qty).toFixed(2)"></p>
                    <button @click="removeFromCart(idx)" class="text-[10px] text-gray-400 hover:text-red-500 transition">quitar</button>
                </div>
            </div>
        </template>

        {{-- Producto personalizado --}}
        <div x-show="cart.length > 0 || showCustom" class="mt-2">
            <button @click="showCustom=!showCustom" x-show="!showCustom"
                    class="w-full py-2 border-2 border-dashed border-gray-200 rounded-xl text-xs text-gray-400 hover:border-indigo-300 hover:text-indigo-500 transition flex items-center justify-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Agregar ítem manual
            </button>
            <div x-show="showCustom" class="bg-white border-2 border-indigo-200 rounded-xl p-3 space-y-2">
                <input x-model="customItem.name" type="text" placeholder="Descripción del ítem"
                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-indigo-500 outline-none">
                <div class="flex gap-2">
                    <input x-model.number="customItem.price" type="number" placeholder="Precio" min="0" step="0.01"
                           class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-indigo-500 outline-none">
                    <input x-model.number="customItem.qty" type="number" placeholder="Cant." min="1" value="1"
                           class="w-20 text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="flex gap-2">
                    <button @click="addCustomItem()"
                            class="flex-1 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition">
                        Agregar
                    </button>
                    <button @click="showCustom=false; customItem={name:'',price:0,qty:1}"
                            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs rounded-lg transition">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer cobro --}}
    <div class="bg-white border-t border-gray-200 px-4 pt-3 pb-4 space-y-3">

        {{-- Totales --}}
        <div class="space-y-1 text-sm">
            <div class="flex justify-between text-gray-500">
                <span>Subtotal (<span x-text="cart.reduce((s,i)=>s+i.qty,0)"></span> items)</span>
                <span x-text="'S/ ' + cartTotal.toFixed(2)"></span>
            </div>
            <div class="flex justify-between font-black text-gray-900 text-lg pt-1 border-t border-gray-100">
                <span>TOTAL</span>
                <span x-text="'S/ ' + cartTotal.toFixed(2)"></span>
            </div>
        </div>

        {{-- Efectivo / vuelto --}}
        <div x-show="paymentForm.method === 'Efectivo'" class="bg-green-50 border border-green-200 rounded-xl p-3 space-y-2">
            <div class="flex gap-2 items-center">
                <label class="text-xs text-gray-600 whitespace-nowrap">Recibido:</label>
                <input x-model.number="paymentForm.received" type="number" min="0" step="0.1"
                       class="flex-1 text-sm border border-green-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-green-500 outline-none text-right font-semibold">
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Vuelto:</span>
                <span class="font-bold text-green-700" x-text="'S/ ' + Math.max(0, paymentForm.received - cartTotal).toFixed(2)"></span>
            </div>
        </div>

        {{-- Método de pago --}}
        <div class="grid grid-cols-3 gap-1.5">
            @foreach($paymentMethods as $pm)
            <button @click="paymentForm.method = '{{ $pm }}'"
                    :class="paymentForm.method === '{{ $pm }}' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-200 hover:border-indigo-400'"
                    class="py-2 px-2 border-2 rounded-xl text-xs font-semibold transition text-center">
                {{ $pm }}
            </button>
            @endforeach
            @if($paymentMethods->isEmpty())
            <button @click="paymentForm.method = 'Efectivo'"
                    :class="paymentForm.method === 'Efectivo' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-200 hover:border-indigo-400'"
                    class="py-2 px-2 border-2 rounded-xl text-xs font-semibold transition">Efectivo</button>
            <button @click="paymentForm.method = 'Tarjeta'"
                    :class="paymentForm.method === 'Tarjeta' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-200 hover:border-indigo-400'"
                    class="py-2 px-2 border-2 rounded-xl text-xs font-semibold transition">Tarjeta</button>
            <button @click="paymentForm.method = 'Yape/Plin'"
                    :class="paymentForm.method === 'Yape/Plin' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-200 hover:border-indigo-400'"
                    class="py-2 px-2 border-2 rounded-xl text-xs font-semibold transition">Yape/Plin</button>
            @endif
        </div>

        {{-- Cliente (opcional) --}}
        <div x-show="showClientFields" class="grid grid-cols-2 gap-2">
            <input x-model="paymentForm.client_name" type="text" placeholder="Nombre cliente (opcional)"
                   class="text-xs border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none col-span-2">
            <input x-model="paymentForm.client_phone" type="text" placeholder="Teléfono"
                   class="text-xs border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
            <input x-model="paymentForm.notes" type="text" placeholder="Notas"
                   class="text-xs border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
        </div>
        <button @click="showClientFields=!showClientFields"
                class="text-xs text-gray-400 hover:text-indigo-500 transition flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="showClientFields ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7'"/>
            </svg>
            <span x-text="showClientFields ? 'Ocultar datos cliente' : 'Agregar datos de cliente'"></span>
        </button>

        {{-- Botón cobrar --}}
        <button @click="charge()"
                :disabled="cart.length === 0 || !paymentForm.method || processing"
                :class="cart.length === 0 || !paymentForm.method ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-200 active:scale-95'"
                class="w-full py-3.5 rounded-2xl font-black text-base transition-all flex items-center justify-center gap-2">
            <template x-if="!processing">
                <span>
                    <svg class="w-5 h-5 inline mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL — Venta completada
══════════════════════════════════════════════════════ --}}
<div x-show="successModal" x-cloak
     class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
     @click.self="successModal = false">
    <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-8 text-center" @click.stop>
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h3 class="text-xl font-black text-gray-900 mb-1">¡Venta registrada!</h3>
        <p class="text-gray-500 text-sm mb-2" x-text="'S/ ' + lastTotal.toFixed(2)"></p>
        <p class="text-gray-400 text-xs mb-1" x-text="paymentForm.method"></p>
        <template x-if="lastChange > 0">
            <div class="bg-green-50 border border-green-200 rounded-xl p-3 my-4">
                <p class="text-sm text-gray-600">Vuelto</p>
                <p class="text-2xl font-black text-green-700" x-text="'S/ ' + lastChange.toFixed(2)"></p>
            </div>
        </template>
        <div class="flex gap-2 mt-6">
            <button @click="successModal = false; clearCart()"
                    class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl transition">
                Nueva venta
            </button>
            <button @click="printTicket()"
                    class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-2xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
            </button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL — Historial de transacciones
══════════════════════════════════════════════════════ --}}
<div x-show="showTransactions" x-cloak
     class="fixed inset-0 bg-black/50 flex items-end sm:items-center justify-center z-50 p-4"
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
                <div class="flex items-center gap-3 py-2 border-b border-gray-50">
                    <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800" x-text="t.client_name"></p>
                        <p class="text-xs text-gray-400" x-text="t.payment_method + ' · ' + t.created_at"></p>
                    </div>
                    <p class="text-sm font-bold text-gray-800" x-text="'S/ ' + parseFloat(t.total).toFixed(2)"></p>
                </div>
            </template>
        </div>
        {{-- Resumen por método --}}
        <div class="px-6 py-4 bg-gray-50 rounded-b-3xl">
            <p class="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Resumen por método de pago</p>
            <div class="grid grid-cols-2 gap-2">
                <template x-for="[method, amount] in paymentSummary" :key="method">
                    <div class="bg-white rounded-xl px-3 py-2 flex justify-between items-center border border-gray-100">
                        <span class="text-xs text-gray-600" x-text="method"></span>
                        <span class="text-sm font-bold text-gray-800" x-text="'S/ ' + amount.toFixed(2)"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

</div>

<script>
function posApp() {
    return {
        products: @json($productsJs),
        categories: @json($categoriesJs),
        transactions: @json($transactionsJs),

        search: '',
        filterCat: null,
        cart: [],
        processing: false,
        showCustom: false,
        showClientFields: false,
        showTransactions: false,
        successModal: false,
        lastTotal: 0,
        lastChange: 0,
        customItem: { name: '', price: 0, qty: 1 },
        paymentForm: {
            method: '',
            received: 0,
            client_name: '',
            client_phone: '',
            notes: '',
        },

        init() {
            if (this.transactions.length === 0) {
                // default payment
            }
            @if($paymentMethods->isNotEmpty())
            this.paymentForm.method = '{{ $paymentMethods->first() }}';
            @else
            this.paymentForm.method = 'Efectivo';
            @endif
        },

        get filteredProducts() {
            return this.products.filter(p => {
                const s = !this.search || p.name.toLowerCase().includes(this.search.toLowerCase());
                const c = this.filterCat === null || p.cat_id === this.filterCat;
                return s && c;
            });
        },

        get cartTotal() {
            return this.cart.reduce((s, i) => s + i.price * i.qty, 0);
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

        addToCart(product) {
            const existing = this.cart.find(i => i.product_id === product.id);
            if (existing) {
                existing.qty++;
            } else {
                this.cart.push({ product_id: product.id, name: product.name, price: product.price, qty: 1 });
            }
        },

        addCustomItem() {
            if (!this.customItem.name || this.customItem.price <= 0) return;
            this.cart.push({ product_id: null, name: this.customItem.name, price: this.customItem.price, qty: this.customItem.qty || 1 });
            this.customItem = { name: '', price: 0, qty: 1 };
            this.showCustom = false;
        },

        increaseQty(idx) { this.cart[idx].qty++; },
        decreaseQty(idx) {
            if (this.cart[idx].qty <= 1) this.removeFromCart(idx);
            else this.cart[idx].qty--;
        },
        removeFromCart(idx) { this.cart.splice(idx, 1); },
        clearCart() { this.cart = []; this.paymentForm.received = 0; },

        async charge() {
            if (this.cart.length === 0 || !this.paymentForm.method || this.processing) return;
            this.processing = true;
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
                        payment_method: this.paymentForm.method,
                        notes:          this.paymentForm.notes || null,
                        items: this.cart.map(i => ({
                            product_id: i.product_id,
                            name:       i.name,
                            price:      i.price,
                            quantity:   i.qty,
                        })),
                    }),
                });
                const data = await res.json();
                if (data.ok) {
                    this.lastTotal  = parseFloat(data.total);
                    this.lastChange = this.paymentForm.method === 'Efectivo'
                        ? Math.max(0, this.paymentForm.received - this.lastTotal) : 0;

                    // Add to today's transactions list
                    this.transactions.unshift({
                        id:             data.order.id,
                        client_name:    data.order.client_name,
                        payment_method: data.order.payment_method,
                        total:          data.order.total,
                        created_at:     new Date().toLocaleTimeString('es-PE', {hour:'2-digit',minute:'2-digit'}),
                    });

                    this.successModal = true;
                    this.paymentForm.client_name  = '';
                    this.paymentForm.client_phone = '';
                    this.paymentForm.notes        = '';
                    this.paymentForm.received     = 0;
                    this.showClientFields = false;
                } else {
                    alert('Error al registrar la venta.');
                }
            } catch (e) {
                alert('Error de conexión.');
            }
            this.processing = false;
        },

        printTicket() {
            // Simple print of last transaction info
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
</x-slot>
</x-app-layout>
