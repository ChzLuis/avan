<x-app-layout>
<x-slot name="slot">

@php
    $csrf        = csrf_token();
    $base        = url("/bixoadmin");
    $currentType = $type ?? 'product';

    $allCats = $categories->map(fn($c) => [
        'id'             => $c->id,
        'name'           => $c->name,
        'image_url'      => $c->image_url ?? '',
        'color'          => $c->color ?? '#f97316',
        'is_active'      => $c->is_active,
        'sort_order'     => $c->sort_order,
        'type'           => $c->type,
        'parent_id'      => $c->parent_id,
        'products_count' => $c->products_count ?? 0,
        'services_count' => $c->services_count ?? 0,
        'children'       => $c->children->map(fn($s) => [
            'id'             => $s->id,
            'name'           => $s->name,
            'image_url'      => $s->image_url ?? '',
            'color'          => $s->color ?? '#f97316',
            'is_active'      => $s->is_active,
            'sort_order'     => $s->sort_order,
            'type'           => $s->type,
            'parent_id'      => $s->parent_id,
            'products_count' => $s->products_count ?? 0,
            'services_count' => $s->services_count ?? 0,
            'children'       => [],
        ])->values()->all(),
    ]);
@endphp

<script>
window.__categoryPageData = {
    categories: {!! Illuminate\Support\Js::from($allCats) !!},
    activeType: '{{ $currentType }}',
    csrf:       '{{ $csrf }}',
    baseUrl:    '{{ $base }}',
    importUrl:  '{{ route('categories.import') }}',
};
document.addEventListener('alpine:init', () => {
    Alpine.data('categoryPage', () => ({
        categories: window.__categoryPageData.categories,
        activeType: window.__categoryPageData.activeType,
        csrf:       window.__categoryPageData.csrf,
        baseUrl:    window.__categoryPageData.baseUrl,
        importUrl:  window.__categoryPageData.importUrl,
        search: '',
        panel: 'detail',
        selected: null,
        saving: false,
        deleting: false,
        isNew: false,
        expandedIds: {},
        form: { name: '', image_url: '', color: '#f97316', is_active: true, type: window.__categoryPageData.activeType, parent_id: null },
        importLog: { show: false, created: 0, updated: 0, errors: [] },

        get filtered() {
            const q = this.search.toLowerCase();
            if (!q) return this.categories;
            return this.categories.filter(c => {
                if (c.name.toLowerCase().includes(q)) return true;
                return c.children.some(s => s.name.toLowerCase().includes(q));
            });
        },

        toggleExpand(id) { this.expandedIds[id] = !this.expandedIds[id]; },
        isExpanded(id)   { return !!this.expandedIds[id]; },
        expandAll()      { this.categories.forEach(c => { if (c.children.length) this.expandedIds[c.id] = true; }); },
        collapseAll()    { this.expandedIds = {}; },

        switchType(t) {
            this.activeType = t;
            window.location.href = this.baseUrl + '/categories?type=' + t;
        },

        openNew(parentId = null) {
            this.selected = null;
            this.isNew    = true;
            this.form     = { name: '', image_url: '', color: '#f97316', is_active: true, type: this.activeType, parent_id: parentId };
            this.panel    = 'detail';
        },

        openEdit(cat) {
            this.selected = JSON.parse(JSON.stringify(cat));
            this.isNew    = false;
            this.form     = { ...this.selected };
            this.panel    = 'detail';
            if (cat.parent_id) this.expandedIds[cat.parent_id] = true;
        },

        parentName(id) {
            const p = this.categories.find(c => c.id === id);
            return p ? p.name : '';
        },

        async save() {
            if (!this.form.name.trim()) return;
            this.saving = true;
            const url    = this.isNew ? this.baseUrl + '/categories' : this.baseUrl + '/categories/' + this.form.id;
            const method = this.isNew ? 'POST' : 'PUT';
            try {
                const res  = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                    body: JSON.stringify(this.form),
                });
                const data = await res.json();
                if (this.isNew) {
                    if (data.parent_id) {
                        const parent = this.categories.find(c => c.id === data.parent_id);
                        if (parent) { parent.children.push(data); this.expandedIds[parent.id] = true; }
                    } else {
                        data.children = [];
                        this.categories.push(data);
                    }
                    this.isNew    = false;
                    this.selected = data;
                    this.form     = { ...data };
                } else {
                    const ri = this.categories.findIndex(c => c.id === data.id);
                    if (ri > -1) {
                        data.children       = this.categories[ri].children;
                        this.categories[ri] = data;
                    } else {
                        this.categories.forEach(c => {
                            const ci = c.children.findIndex(s => s.id === data.id);
                            if (ci > -1) c.children[ci] = data;
                        });
                    }
                    this.selected = data;
                    this.form     = { ...data };
                }
            } catch(e) { alert('Error al guardar'); }
            this.saving = false;
        },

        async del() {
            if (!this.selected) return;
            const ok = await window.__confirm({
                title: 'Eliminar categoria',
                msg: 'Eliminar "' + this.selected.name + '"? Si tiene subcategorias tambien seran desvinculadas.',
                confirmLabel: 'Si, eliminar',
            });
            if (!ok) return;
            this.deleting = true;
            await fetch(this.baseUrl + '/categories/' + this.selected.id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
            });
            this.categories = this.categories.filter(c => c.id !== this.selected.id);
            this.categories.forEach(c => { c.children = c.children.filter(s => s.id !== this.selected.id); });
            this.selected = null;
            this.panel    = 'list';
            this.deleting = false;
        },

        totalCount() { return this.categories.reduce((n, c) => n + 1 + c.children.length, 0); },

        async importar(event) {
            const file = event.target.files[0]; if (!file) return;
            const fd = new FormData();
            fd.append('file', file); fd.append('_token', this.csrf);
            try {
                const res  = await fetch(this.importUrl, { method:'POST', headers:{'Accept':'application/json'}, body: fd });
                const data = await res.json();
                event.target.value = '';
                this.importLog = { show: true, created: data.created||0, updated: data.updated||0, errors: data.errors||[] };
                if ((data.created||0)+(data.updated||0) > 0) window.location.reload();
            } catch(e) {
                event.target.value = '';
                this.importLog = { show: true, created: 0, updated: 0, errors: ['Error de red al importar.'] };
            }
        },

        init() {
            if (this.categories.length > 0) {
                this.openEdit(this.categories[0]);
            }
        },
    }));
});
</script>
<div class="flex flex-col h-full w-full overflow-hidden bg-gray-50" x-data="categoryPage()">

{{-- ══ TOPBAR ══════════════════════════════════════════════════════════════ --}}
<div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 bg-white flex-shrink-0">
    <div class="flex items-center gap-3">
        <div>
            <h1 class="text-base font-bold text-gray-800">Categorías</h1>
            <p class="text-xs text-gray-400"><span x-text="totalCount()"></span> categorías registradas</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        {{-- Pestañas Productos / Servicios --}}
        <div class="flex bg-gray-100 rounded-xl p-0.5 mr-2">
            <button @click="switchType('product')"
                    :class="activeType==='product' ? 'bg-white shadow text-orange-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Productos
            </button>
            <button @click="switchType('service')"
                    :class="activeType==='service' ? 'bg-white shadow text-purple-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Servicios
            </button>
        </div>

        {{-- Carga masiva --}}
        <div x-data="{open:false}" class="relative">
            <button @click="open=!open" class="flex items-center gap-1.5 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Carga Masiva
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" @click.outside="open=false" x-cloak
                 class="absolute right-0 mt-1.5 w-52 bg-white rounded-xl shadow-lg border border-gray-200 py-1 z-50">
                <a href="{{ route('categories.template') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Descargar plantilla
                </a>
                <label class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Importar archivo
                    <input type="file" accept=".csv,.xls,.xlsx" class="hidden" @change="importar($event)">
                </label>
                <div class="border-t border-gray-100 my-1"></div>
                <a href="{{ route('categories.export') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Exportar Excel
                </a>
            </div>
        </div>

        {{-- Nueva categoría --}}
        <button @click="openNew(null)"
                class="flex items-center gap-1.5 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nueva categoría
        </button>
    </div>
</div>

{{-- ══ BODY ══════════════════════════════════════════════════════════════════ --}}
<div class="flex flex-1 overflow-hidden">

    {{-- ── PANEL ÁRBOL ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-col bg-white border-r border-gray-200 flex-shrink-0"
         :class="panel==='list' ? 'flex w-full md:w-96' : 'hidden md:flex md:w-96'">

        {{-- Búsqueda --}}
        <div class="px-4 py-3 border-b border-gray-100">
            <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" placeholder="Buscar categorías..."
                       class="bg-transparent text-sm outline-none flex-1 text-gray-700 placeholder-gray-400">
                <button x-show="search" @click="search=''" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Toolbar expandir/colapsar --}}
        <div class="flex items-center gap-3 px-4 py-2 border-b border-gray-100 bg-gray-50">
            <button @click="expandAll()"
                    class="flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-800 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                Expandir todos los nodos
            </button>
            <span class="text-gray-300">|</span>
            <button @click="collapseAll()"
                    class="flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-800 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"/></svg>
                Colapsar todos los nodos
            </button>
        </div>

        {{-- Árbol --}}
        <div class="flex-1 overflow-y-auto">

            <template x-if="filtered.length === 0">
                <div class="flex flex-col items-center justify-center py-16 text-center px-6">
                    <svg class="w-12 h-12 text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    <p class="text-sm font-semibold text-gray-400">Sin categorías aún</p>
                    <p class="text-xs text-gray-300 mt-1">Haz clic en "Nueva categoría" para empezar</p>
                </div>
            </template>

            <template x-for="cat in filtered" :key="cat.id">
                <div>
                    {{-- ── Fila padre ── --}}
                    <div class="flex items-center border-b border-gray-100 cursor-pointer select-none"
                         :class="selected?.id === cat.id ? 'bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-800'">

                        {{-- Botón expandir --}}
                        <button @click="toggleExpand(cat.id)"
                                class="w-9 h-9 flex items-center justify-center flex-shrink-0 transition"
                                :class="selected?.id === cat.id ? 'text-gray-300 hover:text-white' : 'text-gray-400 hover:text-gray-700'">
                            <template x-if="cat.children.length > 0">
                                <svg class="w-4 h-4 transition-transform duration-150"
                                     :class="isExpanded(cat.id) ? 'rotate-90' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                </svg>
                            </template>
                            <template x-if="cat.children.length === 0">
                                <span class="w-2 h-2 rounded-full bg-current opacity-30"></span>
                            </template>
                        </button>

                        {{-- Nombre padre --}}
                        <button @click="openEdit(cat)" class="flex-1 flex items-center gap-2 py-2.5 text-left min-w-0">
                            <span class="text-xs font-bold tracking-wider uppercase truncate" x-text="cat.name"></span>
                            <span x-show="cat.children.length > 0"
                                  class="text-[10px] px-1.5 py-0.5 rounded-full flex-shrink-0"
                                  :class="selected?.id === cat.id ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-500'"
                                  x-text="cat.children.length"></span>
                        </button>

                        {{-- Badge activa --}}
                        <span class="text-[10px] px-2 py-0.5 rounded-full mr-1 flex-shrink-0 font-medium"
                              :class="cat.is_active
                                  ? (selected?.id === cat.id ? 'bg-green-400/30 text-green-200' : 'bg-green-100 text-green-700')
                                  : (selected?.id === cat.id ? 'bg-white/20 text-gray-300' : 'bg-gray-200 text-gray-400')"
                              x-text="cat.is_active ? 'Activa' : 'Inactiva'"></span>

                        {{-- Botón + subcategoría --}}
                        <button @click.stop="openNew(cat.id)"
                                title="Agregar subcategoría"
                                class="w-8 h-9 flex items-center justify-center flex-shrink-0 transition"
                                :class="selected?.id === cat.id ? 'text-orange-300 hover:text-orange-100' : 'text-orange-400 hover:text-orange-600'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>

                    {{-- ── Hijos ── --}}
                    <div x-show="isExpanded(cat.id)" x-collapse>
                        <template x-for="sub in cat.children" :key="sub.id">
                            <div class="flex items-center border-b border-gray-100 pl-4 cursor-pointer"
                                 :class="selected?.id === sub.id ? 'bg-orange-50 border-l-4 border-l-orange-400' : 'bg-white hover:bg-gray-50'">

                                {{-- Línea guía --}}
                                <div class="flex items-center w-7 flex-shrink-0 self-stretch">
                                    <div class="flex flex-col items-center h-full w-3 mr-1">
                                        <div class="w-px bg-gray-200 flex-1"></div>
                                        <div class="w-3 h-px bg-gray-200"></div>
                                        <div class="w-px bg-gray-200 flex-1"></div>
                                    </div>
                                    <span class="text-gray-300 text-xs">—</span>
                                </div>

                                <button @click="openEdit(sub)" class="flex-1 flex items-center gap-2 py-2 text-left min-w-0">
                                    <span class="text-sm text-gray-700 truncate" x-text="sub.name"></span>
                                    <span x-show="sub.products_count > 0 || sub.services_count > 0"
                                          class="text-[10px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-full flex-shrink-0"
                                          x-text="(sub.products_count + sub.services_count) + ' items'"></span>
                                </button>

                                <span class="text-[10px] px-2 py-0.5 rounded-full mr-3 flex-shrink-0 font-medium"
                                      :class="sub.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400'"
                                      x-text="sub.is_active ? 'Activa' : 'Inactiva'"></span>
                            </div>
                        </template>

                        {{-- Agregar subcategoría al fondo --}}
                        <button @click="openNew(cat.id)"
                                class="w-full flex items-center gap-2 pl-14 pr-4 py-2 text-xs text-orange-500 hover:bg-orange-50 transition border-b border-dashed border-orange-100">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Agregar subcategoría en <span class="font-semibold ml-1" x-text="cat.name"></span>
                        </button>
                    </div>

                </div>
            </template>

        </div>
    </div>

    {{-- ── PANEL DETALLE / FORMULARIO ──────────────────────────────────────── --}}
    <div class="flex-col overflow-hidden bg-white"
         :class="panel==='detail' ? 'flex flex-1' : 'hidden md:flex md:flex-1'">

        {{-- Empty state --}}
        <template x-if="!selected && !isNew">
            <div class="flex-1 flex flex-col items-center justify-center text-center p-10">
                <div class="w-20 h-20 rounded-2xl bg-gray-100 flex items-center justify-center mb-4">
                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                </div>
                <p class="text-base font-semibold text-gray-400">Selecciona una categoría</p>
                <p class="text-sm text-gray-300 mt-1 mb-5">o crea una nueva para empezar</p>
                <button @click="openNew(null)"
                        class="flex items-center gap-2 px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Nueva categoría
                </button>
            </div>
        </template>

        <template x-if="selected || isNew">
            <div class="flex flex-col flex-1 overflow-hidden">

                {{-- Header --}}
                <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-white flex-shrink-0">
                    <button @click="panel='list'; selected=null; isNew=false" class="md:hidden text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white text-sm flex-shrink-0"
                         :style="'background:' + (form.color || '#f97316')">
                        <span x-text="form.name ? form.name.charAt(0).toUpperCase() : '+'"></span>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-800"
                            x-text="isNew ? (form.parent_id ? 'Nueva subcategoría' : 'Nueva categoría') : form.name"></h2>
                        <p class="text-xs text-gray-400"
                           x-text="isNew ? (form.parent_id ? 'Subcategoría de: ' + parentName(form.parent_id) : 'Categoría padre') : ((selected?.products_count || 0) + ' productos · ' + (selected?.services_count || 0) + ' servicios')"></p>
                    </div>
                </div>

                {{-- Formulario --}}
                <div class="flex-1 overflow-y-auto p-6 space-y-6">

                    {{-- Tipo (solo categoría padre nueva) --}}
                    <template x-if="isNew && !form.parent_id">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Tipo de categoría</label>
                            <div class="flex gap-3">
                                <button @click="form.type='product'"
                                        :class="form.type==='product' ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-gray-200 text-gray-500 hover:border-orange-200'"
                                        class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl border-2 text-sm font-semibold transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    Productos
                                </button>
                                <button @click="form.type='service'"
                                        :class="form.type==='service' ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-gray-200 text-gray-500 hover:border-purple-200'"
                                        class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl border-2 text-sm font-semibold transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    Servicios
                                </button>
                            </div>
                            <p class="text-xs text-gray-400 mt-1.5">Define si esta categoría agrupa productos o servicios del negocio.</p>
                        </div>
                    </template>

                    {{-- Categoría padre (si es subcategoría) --}}
                    <template x-if="form.parent_id">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Categoría padre</label>
                            <div class="flex items-center gap-3 bg-gray-50 border border-gray-200 rounded-xl px-4 py-3">
                                <svg class="w-4 h-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                <span class="text-sm font-semibold text-gray-700" x-text="parentName(form.parent_id)"></span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1.5">Esta subcategoría pertenece a la categoría padre indicada.</p>
                        </div>
                    </template>

                    {{-- Nombre --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                            Nombre <span class="text-red-400 normal-case font-normal">(obligatorio)</span>
                        </label>
                        <input type="text" x-model="form.name"
                               class="w-full text-sm border border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-orange-400 focus:border-transparent outline-none transition"
                               :placeholder="form.parent_id ? 'Ej: Arroz, Pollo, Vitaminas...' : 'Ej: ABARROTES, CARNES, CONSULTAS...'">
                        <p class="text-xs text-gray-400 mt-1.5"
                           x-text="form.parent_id ? 'Nombre de la subcategoría. Ej: Arroz, Lentejas, Antibióticos.' : 'Nombre principal en mayúsculas. Ej: ABARROTES, CARNES Y DERIVADOS.'"></p>
                    </div>

                    {{-- Estado --}}
                    <div x-show="!isNew">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Estado</label>
                        <button @click="form.is_active = !form.is_active" type="button"
                                class="flex items-center gap-3 w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 transition hover:bg-gray-100">
                            <div :class="form.is_active ? 'bg-green-500' : 'bg-gray-300'"
                                 class="relative w-11 h-6 rounded-full transition-colors duration-200 flex-shrink-0">
                                <span :class="form.is_active ? 'translate-x-5' : 'translate-x-0.5'"
                                      class="absolute top-0.5 left-0 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200 block"></span>
                            </div>
                            <div class="text-left">
                                <p class="text-sm font-semibold" :class="form.is_active ? 'text-green-700' : 'text-gray-500'"
                                   x-text="form.is_active ? 'Activa' : 'Inactiva'"></p>
                                <p class="text-xs text-gray-400"
                                   x-text="form.is_active ? 'Visible en tienda y catálogo' : 'Oculta en tienda y catálogo'"></p>
                            </div>
                        </button>
                    </div>

                    {{-- Info subcategorías (solo en edición de padre) --}}
                    <template x-if="!isNew && !selected?.parent_id && selected?.children?.length > 0">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Subcategorías</label>
                            <div class="bg-gray-50 border border-gray-200 rounded-xl overflow-hidden">
                                <template x-for="sub in (selected?.children || [])" :key="sub.id">
                                    <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 last:border-0">
                                        <div class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-orange-400 flex-shrink-0"></span>
                                            <span class="text-sm text-gray-700" x-text="sub.name"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-400" x-text="(sub.products_count + sub.services_count) + ' items'"></span>
                                            <button @click="openEdit(sub)"
                                                    class="text-xs text-blue-500 hover:text-blue-700 font-medium transition">Editar</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <button @click="openNew(selected.id)"
                                    class="mt-2 flex items-center gap-1.5 text-xs text-orange-500 hover:text-orange-700 font-medium transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Agregar otra subcategoría
                            </button>
                        </div>
                    </template>

                </div>

                {{-- Footer --}}
                <div class="flex-shrink-0 px-6 py-4 border-t border-gray-100 bg-white flex items-center justify-between">
                    <button x-show="!isNew && selected" @click="del()" :disabled="deleting"
                            class="flex items-center gap-1.5 px-4 py-2 text-sm text-red-600 border border-red-200 hover:bg-red-50 rounded-lg transition font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Eliminar
                    </button>
                    <div class="flex items-center gap-3">
                        <button @click="selected=null; isNew=false; panel='list'"
                                class="px-4 py-2 text-sm text-gray-600 border border-gray-200 hover:bg-gray-50 rounded-lg transition">
                            Cancelar
                        </button>
                        <button @click="save()" :disabled="saving || !form.name.trim()"
                                :class="!form.name.trim() ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-orange-500 hover:bg-orange-600 text-white'"
                                class="flex items-center gap-2 px-6 py-2 text-sm font-bold rounded-lg transition">
                            <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span x-text="saving ? 'Guardando...' : (isNew ? 'Crear' : 'Guardar cambios')"></span>
                        </button>
                    </div>
                </div>

            </div>
        </template>

    </div>

</div>

{{-- ══ Modal importación ════════════════════════════════════════════════════ --}}
<div x-show="importLog.show" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
     @keydown.escape.window="importLog.show=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Resultado de importación</h3>
            <button @click="importLog.show=false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-6 py-4 grid grid-cols-2 gap-3">
            <div class="rounded-xl bg-green-50 border border-green-200 px-3 py-3 text-center">
                <div class="text-2xl font-bold text-green-700" x-text="importLog.created"></div>
                <div class="text-xs text-green-600 mt-0.5">Creadas</div>
            </div>
            <div class="rounded-xl bg-blue-50 border border-blue-200 px-3 py-3 text-center">
                <div class="text-2xl font-bold text-blue-700" x-text="importLog.updated"></div>
                <div class="text-xs text-blue-600 mt-0.5">Actualizadas</div>
            </div>
        </div>
        <div x-show="importLog.errors.length > 0" class="px-6 pb-4">
            <p class="text-xs font-bold text-red-600 mb-2">Errores (<span x-text="importLog.errors.length"></span>):</p>
            <ul class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 space-y-1.5 max-h-48 overflow-y-auto">
                <template x-for="(err, i) in importLog.errors" :key="i">
                    <li class="text-xs text-red-700 flex gap-2">
                        <span class="text-red-400 flex-shrink-0">•</span>
                        <span x-text="err"></span>
                    </li>
                </template>
            </ul>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
            <button @click="importLog.show=false"
                    class="px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold rounded-lg transition">
                Entendido
            </button>
        </div>
    </div>
</div>

</div>
</x-slot>
</x-app-layout>
