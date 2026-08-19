<x-portal-layout :layout="$portalLayout ?? 'panel'" :project="$project" pageTitle="Mis precios y catálogo">
<div x-data="resellerPrecios()" class="max-w-5xl mx-auto p-4 md:p-6">

    {{-- Accesos rápidos: ir a vender (POS) y ver el catálogo --}}
    <div class="grid grid-cols-2 gap-3 mb-4">
        <a href="{{ $rutaPos }}"
           class="flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-2xl py-3.5 transition shadow-lg shadow-indigo-200 active:scale-[0.98]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Ir a vender (POS)
        </a>
        <a href="{{ $enlace }}" target="_blank"
           class="flex items-center justify-center gap-2 bg-white border-2 border-gray-200 hover:border-green-400 text-gray-700 font-black rounded-2xl py-3.5 transition active:scale-[0.98]">
            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            Ver mi catálogo
        </a>
    </div>

    {{-- Encabezado + enlace del catálogo --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-4">
        <div class="flex items-center gap-2 mb-1">
            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <h1 class="text-lg font-black text-gray-900">Mis precios y catálogo</h1>
        </div>
        <p class="text-sm text-gray-500 mb-4">Pon tu precio a cada producto (sin bajar del mínimo) y elige cuáles mostrar en tu catálogo para compartir.</p>

        <div class="flex flex-col sm:flex-row gap-2 items-stretch">
            <div class="flex-1 flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2">
                <span class="text-xs text-gray-400 font-medium">Tu catálogo:</span>
                <input type="text" readonly :value="enlace" @focus="$event.target.select()"
                       class="flex-1 text-sm text-gray-700 bg-transparent outline-none truncate">
            </div>
            <button @click="copiar()"
                    :class="copiado ? 'bg-green-100 text-green-700' : 'bg-indigo-600 text-white hover:bg-indigo-700'"
                    class="px-4 py-2 rounded-xl text-sm font-bold transition" x-text="copiado ? '¡Copiado!' : 'Copiar enlace'"></button>
            <a :href="'https://wa.me/?text=' + encodeURIComponent('Mira mi catálogo: ' + enlace)" target="_blank"
               class="px-4 py-2 rounded-xl text-sm font-bold bg-green-500 text-white hover:bg-green-600 transition flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.71.306 1.263.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884M20.885 3.488A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                Compartir
            </a>
        </div>
    </div>

    {{-- Buscador --}}
    <div class="relative mb-3">
        <input x-model="search" type="text" placeholder="Buscar producto..."
               class="w-full pl-4 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
    </div>

    {{-- Tabla de productos (estilo hoja de cálculo) --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse min-w-[720px]">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-[11px] uppercase tracking-wide">
                        <th class="text-left font-bold px-3 py-2.5 w-14"></th>
                        <th class="text-left font-bold px-3 py-2.5">Producto</th>
                        <th class="text-right font-bold px-3 py-2.5 w-24">Catálogo</th>
                        <th class="text-center font-bold px-3 py-2.5 w-28">Mi precio</th>
                        <th class="text-right font-bold px-3 py-2.5 w-28">Mi ganancia</th>
                        <th class="text-center font-bold px-3 py-2.5 w-28">
                            <div class="flex flex-col items-center gap-1">
                                <span>En catálogo</span>
                                <label class="flex items-center gap-1 text-[10px] font-semibold text-indigo-600 cursor-pointer normal-case">
                                    <input type="checkbox" @change="toggleTodos($event.target.checked)"
                                           :checked="todosMarcados"
                                           class="w-4 h-4 rounded accent-green-600 cursor-pointer">
                                    Todos
                                </label>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="p in filtrados" :key="p.id">
                        <tr class="border-t border-gray-100 hover:bg-gray-50/60">
                            {{-- Imagen --}}
                            <td class="px-3 py-2">
                                <div class="w-10 h-10 rounded-lg bg-gray-50 overflow-hidden">
                                    <template x-if="p.image"><img :src="p.image" class="w-full h-full object-cover"></template>
                                </div>
                            </td>
                            {{-- Nombre --}}
                            <td class="px-3 py-2 font-semibold text-gray-800" x-text="p.name"></td>
                            {{-- Precio catálogo --}}
                            <td class="px-3 py-2 text-right text-gray-600 font-medium">S/ <span x-text="p.base.toFixed(2)"></span></td>
                            {{-- Mi precio (editable) --}}
                            <td class="px-3 py-2 text-center">
                                <input type="number" min="0" step="0.10" x-model.number="p.my_price"
                                       @change="guardar(p)" @focus="$event.target.select()"
                                       :class="p.error ? 'border-red-400 text-red-600 bg-red-50' : 'border-gray-200'"
                                       class="w-20 text-sm font-black text-center border rounded-lg px-1 py-1 focus:ring-1 focus:ring-indigo-400 outline-none">
                                <p x-show="p.error" class="text-[10px] text-red-600 font-semibold mt-0.5" x-text="p.error"></p>
                            </td>
                            {{-- Ganancia --}}
                            <td class="px-3 py-2 text-right">
                                <template x-if="p.cost != null">
                                    <span class="font-bold"
                                          :class="p.my_price < (p.min ?? -1) ? 'text-red-600' : ((p.my_price - p.cost) < p.cost*0.1 ? 'text-amber-600' : 'text-green-600')">
                                        <span x-text="p.my_price < (p.min ?? -1) ? '🔴' : '🟢'"></span>
                                        +S/ <span x-text="Math.max(0, p.my_price - p.cost).toFixed(2)"></span>
                                    </span>
                                </template>
                                <template x-if="p.cost == null"><span class="text-gray-300">—</span></template>
                            </td>
                            {{-- Check en catálogo --}}
                            <td class="px-3 py-2 text-center">
                                <input type="checkbox" :checked="p.in_catalog" @change="toggle(p)"
                                       class="w-5 h-5 rounded accent-green-600 cursor-pointer">
                            </td>
                        </tr>
                    </template>
                    <template x-if="filtrados.length === 0">
                        <tr><td colspan="6" class="text-center text-gray-400 py-10">Sin resultados</td></tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function resellerPrecios() {
    return {
        productos: @json($productos),
        enlace: @json($enlace),
        search: '',
        copiado: false,

        get filtrados() {
            if (!this.search) return this.productos;
            const q = this.search.toLowerCase();
            return this.productos.filter(p => p.name.toLowerCase().includes(q));
        },

        async copiar() {
            try { await navigator.clipboard.writeText(this.enlace); this.copiado = true; setTimeout(() => this.copiado = false, 1500); }
            catch (e) {
                await bxConfirmar({
                    titulo: 'Copia tu enlace', descripcion: 'Tu navegador no permitió copiarlo solo. Selecciónalo y cópialo.',
                    boton: 'Listo', tono: 'principal', cancelar: '', entrada: { etiqueta: 'Enlace', valor: this.enlace, soloLectura: true },
                });
            }
        },

        async guardar(p) {
            p.error = '';
            try {
                const res = await fetch('{{ $rutaGuardar }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ product_id: p.id, price: p.my_price }),
                });
                const d = await res.json();
                if (!d.ok) { p.error = d.error || 'No se pudo guardar'; }
            } catch (e) { p.error = 'Error de conexión'; }
        },

        async toggle(p) {
            await this.setCatalogo(p, !p.in_catalog);
        },

        // Guarda el estado 'en catálogo' de un producto
        async setCatalogo(p, valor) {
            const anterior = p.in_catalog;
            p.in_catalog = valor;
            try {
                await fetch('{{ $rutaToggle }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ product_id: p.id, in_catalog: p.in_catalog }),
                });
            } catch (e) { p.in_catalog = anterior; }
        },

        // ¿Están todos los productos VISIBLES marcados? (para el check maestro)
        get todosMarcados() {
            return this.filtrados.length > 0 && this.filtrados.every(p => p.in_catalog);
        },

        // Marcar / desmarcar TODOS los productos visibles a la vez
        async toggleTodos(valor) {
            for (const p of this.filtrados) {
                if (p.in_catalog !== valor) await this.setCatalogo(p, valor);
            }
        },
    }
}
</script>
</x-portal-layout>
