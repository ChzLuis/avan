{{-- Layout de componente: layouts.app no tiene @yield('content'); con @extends salia 500 --}}
<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Combos</h2></x-slot>
<div class="p-6 max-w-5xl mx-auto" x-data="combosPage()" x-cloak>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Combos</h1>
            <p class="text-sm text-gray-500 mt-0.5">Agrupa productos con precio especial</p>
        </div>
        <button @click="openNew()" class="flex items-center gap-2 bg-violet-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-violet-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Nuevo combo
        </button>
    </div>

    {{-- Grid de combos --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" x-show="combos.length > 0">
        <template x-for="c in combos" :key="c.id">
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition">
                {{-- Badge disponible --}}
                <div class="flex items-center justify-between px-4 pt-3 pb-1">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                          :class="c.is_available ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                          x-text="c.is_available ? 'Disponible' : 'No disponible'"></span>
                    <div class="flex gap-1">
                        <button @click="openEdit(c)" class="p-1.5 text-gray-400 hover:text-violet-600 rounded">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button @click="toggleCombo(c)" class="p-1.5 text-gray-400 hover:text-amber-500 rounded">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                        <button @click="deleteCombo(c)" class="p-1.5 text-gray-400 hover:text-red-500 rounded">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
                <div class="px-4 pb-4">
                    <h3 class="font-bold text-gray-900 text-base" x-text="c.name"></h3>
                    <p class="text-xs text-gray-500 mt-0.5 mb-3" x-text="c.description || 'Sin descripción'"></p>
                    {{-- Items --}}
                    <div class="flex flex-wrap gap-1 mb-3">
                        <template x-for="item in c.items" :key="item.id">
                            <span class="text-xs bg-violet-50 text-violet-700 px-2 py-0.5 rounded-full font-medium"
                                  x-text="item.quantity + 'x ' + (item.product?.name || item.custom_name || '?')"></span>
                        </template>
                    </div>
                    {{-- Precio --}}
                    <div class="flex items-end gap-2">
                        <span class="text-lg font-black text-gray-900" x-text="'S/ ' + Number(c.price).toFixed(2)"></span>
                        <span class="text-sm line-through text-gray-400" x-show="c.compare_price" x-text="'S/ ' + Number(c.compare_price).toFixed(2)"></span>
                        <span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full"
                              x-show="c.compare_price && c.compare_price > c.price"
                              x-text="'-' + Math.round((1 - c.price/c.compare_price)*100) + '%'"></span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div x-show="combos.length === 0" class="text-center py-20 text-gray-400">
        <div class="text-5xl mb-3">🍱</div>
        <p class="font-semibold text-gray-600">Sin combos todavía</p>
        <p class="text-sm mt-1">Crea tu primer combo para ofrecerlo en tu menú</p>
    </div>

    {{-- MODAL --}}
    <div x-show="modal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="flex items-center justify-between p-5 border-b">
                <h2 class="font-bold text-gray-900" x-text="editing ? 'Editar combo' : 'Nuevo combo'"></h2>
                <button @click="modal=false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre del combo *</label>
                    <input type="text" x-model="form.name" placeholder="Ej: Combo Familiar"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Descripción</label>
                    <textarea x-model="form.description" rows="2" placeholder="Describe el combo..."
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 resize-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Precio combo *</label>
                        <input type="number" x-model="form.price" step="0.01" min="0" placeholder="0.00"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Precio normal</label>
                        <input type="number" x-model="form.compare_price" step="0.01" min="0" placeholder="0.00"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>

                {{-- Items --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-semibold text-gray-700">Productos del combo *</label>
                        <button @click="addItem()" type="button" class="text-xs text-violet-600 font-semibold hover:underline">+ Agregar</button>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(item, idx) in form.items" :key="idx">
                            <div class="flex gap-2 items-center">
                                <select x-model="item.product_id" @change="item.type='product'"
                                        class="flex-1 border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                                    <option value="">— producto —</option>
                                    @foreach($project->products()->orderBy('name')->get() as $prod)
                                    <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                                    @endforeach
                                </select>
                                <input type="number" x-model="item.quantity" min="1" placeholder="Cant"
                                       class="w-16 border border-gray-300 rounded-lg px-2 py-1.5 text-sm text-center focus:outline-none focus:ring-2 focus:ring-violet-500">
                                <button @click="form.items.splice(idx,1)" type="button" class="text-red-400 hover:text-red-600 flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <div x-show="error" class="text-red-500 text-sm" x-text="error"></div>
            </div>
            <div class="flex justify-end gap-2 p-5 border-t">
                <button @click="modal=false" class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">Cancelar</button>
                <button @click="save()" :disabled="saving"
                        class="px-5 py-2 text-sm bg-violet-600 text-white font-semibold rounded-lg hover:bg-violet-700 disabled:opacity-50 transition">
                    <span x-text="saving ? 'Guardando...' : (editing ? 'Actualizar' : 'Crear combo')"></span>
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function combosPage() {
    return {
        combos: @json($combosJson),
        modal: false,
        editing: null,
        saving: false,
        error: '',
        form: { name:'', description:'', price:'', compare_price:'', items:[{type:'product',product_id:'',quantity:1}] },

        openNew() {
            this.editing = null;
            this.form = { name:'', description:'', price:'', compare_price:'', items:[{type:'product',product_id:'',quantity:1}] };
            this.error = '';
            this.modal = true;
        },
        openEdit(c) {
            this.editing = c.id;
            this.form = {
                name: c.name, description: c.description||'',
                price: c.price, compare_price: c.compare_price||'',
                items: c.items.map(i => ({ type: i.item_type, product_id: i.product_id||'', quantity: i.quantity })),
            };
            this.error = '';
            this.modal = true;
        },
        addItem() { this.form.items.push({type:'product',product_id:'',quantity:1}); },

        async save() {
            this.error = '';
            if (!this.form.name) { this.error = 'El nombre es requerido'; return; }
            if (!this.form.price) { this.error = 'El precio es requerido'; return; }
            if (!this.form.items.length) { this.error = 'Agrega al menos un producto'; return; }

            const items = this.form.items.map(i => ({
                type: i.product_id ? 'product' : 'custom',
                product_id: i.product_id || null,
                name: i.custom_name || null,
                quantity: parseInt(i.quantity) || 1,
            }));

            this.saving = true;
            const url    = this.editing ? `/bixoadmin/combos/${this.editing}` : '/bixoadmin/combos';
            const method = this.editing ? 'PUT' : 'POST';
            try {
                const res  = await fetch(url, {
                    method,
                    headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json' },
                    body: JSON.stringify({ ...this.form, items }),
                });
                const data = await res.json();
                if (data.ok) {
                    if (this.editing) {
                        const idx = this.combos.findIndex(c => c.id === this.editing);
                        if (idx >= 0) this.combos[idx] = data.combo;
                    } else {
                        this.combos.push(data.combo);
                    }
                    this.modal = false;
                } else {
                    this.error = data.message || 'Error al guardar';
                }
            } catch(e) { this.error = 'Error de conexión'; }
            this.saving = false;
        },

        async toggleCombo(c) {
            const res  = await fetch(`/bixoadmin/combos/${c.id}/toggle`, { method:'PATCH', headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'} });
            const data = await res.json();
            if (data.ok) { const idx = this.combos.findIndex(x=>x.id===c.id); if(idx>=0) this.combos[idx].is_available = data.is_available; }
        },

        async deleteCombo(c) {
            if (! await bxConfirmar({ descripcion: `¿Eliminar el combo "${c.name}"?` })) return;
            const res = await fetch(`/bixoadmin/combos/${c.id}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'} });
            if ((await res.json()).ok) this.combos = this.combos.filter(x=>x.id!==c.id);
        },
    }
}
</script>
</x-app-layout>
