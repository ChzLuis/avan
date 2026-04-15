<x-app-layout>
<x-slot name="slot">

@php
    $csrf = csrf_token();
    $pid  = $project->id;
    $base = url("/{$pid}/catalog");
@endphp

<div class="flex flex-col h-full w-full overflow-hidden"
     x-data="{
         categories: {{ Illuminate\Support\Js::from($categories->map(fn($c) => [
             'id'             => $c->id,
             'name'           => $c->name,
             'image_url'      => $c->image_url ?? '',
             'color'          => $c->color ?? '#6366f1',
             'is_active'      => $c->is_active,
             'sort_order'     => $c->sort_order,
             'products_count' => $c->products_count ?? 0,
             'services_count' => $c->services_count ?? 0,
         ])) }},
         search: '',
         showModal: false,
         isNew: false,
         saving: false,
         deleting: null,
         form: { name: '', color: '#6366f1', image_url: '', is_active: true },

         get filtered() {
             if (!this.search) return this.categories;
             const q = this.search.toLowerCase();
             return this.categories.filter(c => c.name.toLowerCase().includes(q));
         },

         openNew() {
             this.isNew = true;
             this.form = { name: '', color: '#6366f1', image_url: '', is_active: true };
             this.showModal = true;
         },

         openEdit(cat) {
             this.isNew = false;
             this.form = { ...cat };
             this.showModal = true;
         },

         async save() {
             if (!this.form.name.trim()) return;
             this.saving = true;
             const url    = this.isNew ? '{{ $base }}/categories' : '{{ $base }}/categories/' + this.form.id;
             const method = this.isNew ? 'POST' : 'PUT';
             const res    = await fetch(url, {
                 method,
                 headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ $csrf }}', 'Accept': 'application/json' },
                 body: JSON.stringify(this.form)
             });
             const data = await res.json();
             if (this.isNew) {
                 this.categories.push(data);
             } else {
                 const idx = this.categories.findIndex(c => c.id === data.id);
                 if (idx > -1) this.categories[idx] = data;
             }
             this.saving = false;
             this.showModal = false;
         },

         async toggleActive(cat) {
             const res = await fetch('{{ $base }}/categories/' + cat.id, {
                 method: 'PUT',
                 headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ $csrf }}', 'Accept': 'application/json' },
                 body: JSON.stringify({ ...cat, is_active: !cat.is_active })
             });
             const data = await res.json();
             const idx = this.categories.findIndex(c => c.id === cat.id);
             if (idx > -1) this.categories[idx] = data;
         },

         async del(cat) {
             if (!confirm('¿Eliminar categoría «' + cat.name + '»?\nLos productos y servicios quedarán sin categoría.')) return;
             this.deleting = cat.id;
             await fetch('{{ $base }}/categories/' + cat.id, {
                 method: 'DELETE',
                 headers: { 'X-CSRF-TOKEN': '{{ $csrf }}', 'Accept': 'application/json' }
             });
             this.categories = this.categories.filter(c => c.id !== cat.id);
             this.deleting = null;
         }
     }">

{{-- TOP BAR --}}
<div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 bg-white flex-shrink-0">
    <div>
        <h1 class="text-lg font-semibold text-gray-800">Categorías</h1>
        <p class="text-xs text-gray-400 mt-0.5">
            <span x-text="categories.length"></span> categorías · organiza productos y servicios
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('catalog') }}"
           class="hidden sm:flex items-center gap-1.5 px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            Productos
        </a>
        <a href="{{ route('services.index') }}"
           class="hidden sm:flex items-center gap-1.5 px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Servicios
        </a>
        <button @click="openNew()"
                class="flex items-center gap-1.5 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva categoría
        </button>
    </div>
</div>

{{-- BUSCADOR --}}
<div class="px-6 py-3 border-b border-gray-100 bg-white flex-shrink-0">
    <div class="relative max-w-sm">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text" x-model="search" placeholder="Buscar categoría..."
               class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none">
    </div>
</div>

{{-- GRID CATEGORÍAS --}}
<div class="flex-1 overflow-y-auto p-6">

    {{-- Empty state --}}
    <template x-if="categories.length === 0">
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 rounded-2xl bg-orange-50 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
            <p class="text-gray-600 font-semibold text-lg">Aún no hay categorías</p>
            <p class="text-gray-400 text-sm mt-1 mb-4">Crea categorías para organizar tu catálogo</p>
            <button @click="openNew()"
                    class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition">
                Crear primera categoría
            </button>
        </div>
    </template>

    {{-- Grid --}}
    <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(240px,1fr));">
        <template x-for="cat in filtered" :key="cat.id">
            <div class="bg-white border rounded-2xl overflow-hidden hover:shadow-md transition-shadow group"
                 :class="cat.is_active ? 'border-gray-200' : 'border-dashed border-gray-200 opacity-60'">

                {{-- Color bar --}}
                <div class="h-2 w-full" :style="'background:' + (cat.color || '#6366f1')"></div>

                <div class="p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-3 min-w-0">
                            {{-- Avatar con inicial --}}
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white text-sm flex-shrink-0"
                                 :style="'background:' + (cat.color || '#6366f1')">
                                <span x-text="cat.name.charAt(0).toUpperCase()"></span>
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-800 truncate" x-text="cat.name"></p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    <span x-text="cat.products_count"></span> prod ·
                                    <span x-text="cat.services_count"></span> serv
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button @click="openEdit(cat)"
                                    class="w-7 h-7 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700 flex items-center justify-center transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button @click="del(cat)"
                                    :disabled="deleting===cat.id"
                                    class="w-7 h-7 rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 flex items-center justify-center transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between">
                        <span :class="cat.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'"
                              class="text-xs font-medium px-2 py-0.5 rounded-full"
                              x-text="cat.is_active ? 'Activa' : 'Inactiva'"></span>
                        <button @click="toggleActive(cat)"
                                class="text-xs text-gray-400 hover:text-gray-600 transition"
                                x-text="cat.is_active ? 'Desactivar' : 'Activar'"></button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

{{-- MODAL crear/editar --}}
<div x-show="showModal" x-cloak
     class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4"
     @click.self="showModal=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" @click.stop>

        <h3 class="font-bold text-gray-800 text-lg mb-4"
            x-text="isNew ? 'Nueva categoría' : 'Editar categoría'"></h3>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre *</label>
                <input type="text" x-model="form.name"
                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 outline-none"
                       placeholder="Ej: Ropa, Electrónica, Consultoría">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Color identificador</label>
                <div class="flex items-center gap-3">
                    <input type="color" x-model="form.color"
                           class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5">
                    <span class="text-sm text-gray-500 font-mono" x-text="form.color"></span>
                    <div class="flex gap-1.5 ml-auto">
                        @foreach(['#6366f1','#f97316','#10b981','#f59e0b','#ec4899','#3b82f6','#8b5cf6','#ef4444'] as $col)
                        <button type="button" @click="form.color='{{ $col }}'"
                                class="w-6 h-6 rounded-full border-2 transition"
                                :class="form.color==='{{ $col }}' ? 'border-gray-800 scale-110' : 'border-transparent'"
                                style="background:{{ $col }}"></button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div x-show="!isNew">
                <label class="flex items-center gap-2 cursor-pointer">
                    <button @click="form.is_active = !form.is_active" type="button"
                            :class="form.is_active ? 'bg-green-500' : 'bg-gray-300'"
                            class="relative w-10 h-5 rounded-full transition-colors duration-200 flex-shrink-0">
                        <span :class="form.is_active ? 'translate-x-5' : 'translate-x-0.5'"
                              class="absolute top-0.5 left-0 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200 block"></span>
                    </button>
                    <span class="text-sm text-gray-600" x-text="form.is_active ? 'Activa (visible en POS y tienda)' : 'Inactiva (oculta)'"></span>
                </label>
            </div>
        </div>

        <div class="flex gap-2 mt-6">
            <button @click="showModal=false"
                    class="flex-1 py-2.5 border border-gray-200 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition">
                Cancelar
            </button>
            <button @click="save()" :disabled="saving || !form.name.trim()"
                    :class="!form.name.trim() ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-orange-500 hover:bg-orange-600 text-white'"
                    class="flex-1 py-2.5 text-sm font-semibold rounded-xl transition flex items-center justify-center gap-2">
                <template x-if="saving">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                </template>
                <span x-text="isNew ? 'Crear categoría' : 'Guardar cambios'"></span>
            </button>
        </div>
    </div>
</div>

</div>
</x-slot>
</x-app-layout>
