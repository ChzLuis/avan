<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo de {{ $vendedor }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>[x-cloak]{display:none}</style>
</head>
<body class="bg-gray-50">
<div x-data="catPublico()" x-cloak class="min-h-screen">

    {{-- Header --}}
    <header class="bg-white border-b border-gray-100 sticky top-0 z-20">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-400">Catálogo de</p>
                <h1 class="text-base font-black text-gray-900">{{ $vendedor }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500" x-show="cart.length">
                    <span x-text="cart.reduce((s,i)=>s+i.qty,0)"></span> ítems
                </span>
                <span class="text-sm font-black text-green-600" x-text="'S/ ' + total.toFixed(2)"></span>
            </div>
        </div>
    </header>

    {{-- Buscador --}}
    <div class="max-w-3xl mx-auto px-4 pt-3">
        <input x-model="search" type="text" placeholder="Buscar producto..."
               class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 outline-none">
    </div>

    {{-- Grid --}}
    <main class="max-w-3xl mx-auto px-4 py-4 pb-32">
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <template x-for="p in filtrados" :key="p.id">
                <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden flex flex-col">
                    <div class="aspect-square bg-gray-50 overflow-hidden">
                        <template x-if="p.image"><img :src="p.image" :alt="p.name" class="w-full h-full object-cover"></template>
                    </div>
                    <div class="p-2.5 flex-1 flex flex-col">
                        <p class="text-xs font-semibold text-gray-800 leading-tight line-clamp-2 min-h-[2rem]" x-text="p.name"></p>
                        <p class="text-[10px] text-gray-400" x-text="p.category"></p>
                        <p class="text-sm font-black text-green-600 mt-1" x-text="'S/ ' + p.price.toFixed(2)"></p>
                        <div class="mt-auto pt-2">
                            <template x-if="qty(p.id) === 0">
                                <button @click="add(p)" class="w-full py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded-lg transition">Agregar</button>
                            </template>
                            <template x-if="qty(p.id) > 0">
                                <div class="flex items-center justify-between gap-1">
                                    <button @click="dec(p)" class="w-8 h-8 rounded-lg bg-gray-100 font-black text-gray-600">−</button>
                                    <span class="font-black text-sm" x-text="qty(p.id)"></span>
                                    <button @click="add(p)" class="w-8 h-8 rounded-lg bg-green-600 text-white font-black">+</button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <template x-if="filtrados.length === 0">
            <p class="text-center text-gray-400 text-sm py-10">No hay productos</p>
        </template>
    </main>

    {{-- Barra pedir por WhatsApp --}}
    <div x-show="cart.length" class="fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 z-30">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center gap-3">
            <div class="flex-1">
                <p class="text-xs text-gray-400" x-text="cart.reduce((s,i)=>s+i.qty,0) + ' productos'"></p>
                <p class="text-lg font-black text-gray-900" x-text="'S/ ' + total.toFixed(2)"></p>
            </div>
            <a :href="waLink" target="_blank"
               class="flex-1 py-3 bg-green-500 hover:bg-green-600 text-white text-center font-black rounded-2xl transition flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.71.306 1.263.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884M20.885 3.488A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                Pedir
            </a>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function catPublico() {
    return {
        productos: @json($productos),
        telefono: @json($telefono),
        vendedor: @json($vendedor),
        search: '',
        cart: [],

        get filtrados() {
            if (!this.search) return this.productos;
            const q = this.search.toLowerCase();
            return this.productos.filter(p => p.name.toLowerCase().includes(q));
        },
        get total() { return this.cart.reduce((s, i) => s + i.price * i.qty, 0); },

        qty(id) { const c = this.cart.find(i => i.id === id); return c ? c.qty : 0; },
        add(p) {
            const c = this.cart.find(i => i.id === p.id);
            if (c) c.qty++; else this.cart.push({ id: p.id, name: p.name, price: p.price, qty: 1 });
        },
        dec(p) {
            const c = this.cart.find(i => i.id === p.id);
            if (!c) return;
            if (c.qty <= 1) this.cart = this.cart.filter(i => i.id !== p.id); else c.qty--;
        },

        get waLink() {
            let msg = `¡Hola ${this.vendedor}! Quiero pedir:\n`;
            this.cart.forEach(i => { msg += `• ${i.qty}x ${i.name} — S/ ${(i.price*i.qty).toFixed(2)}\n`; });
            msg += `\nTotal: S/ ${this.total.toFixed(2)}`;
            const tel = this.telefono ? this.telefono : '';
            return `https://wa.me/${tel}?text=` + encodeURIComponent(msg);
        },
    }
}
</script>
</body>
</html>
