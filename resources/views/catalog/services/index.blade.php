<x-app-layout>
<x-slot name="slot">

@php
    $csrf = csrf_token();
    $pid  = $project->id;
    $base = url("/bixoadmin");
    $currency = $project->setting('currency', 'S/');
@endphp

<div class="flex flex-col h-full w-full overflow-hidden">

{{-- TOP BAR --}}
<div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 bg-white flex-shrink-0">
    <div>
        <h1 class="text-lg font-semibold text-gray-800">Catálogo de Servicios</h1>
        <p class="text-xs text-gray-400 mt-0.5" id="service-count-label">Cargando...</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('catalog') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            Productos
        </a>
        <a href="{{ route('categories.index') }}"
           class="flex items-center gap-1.5 px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            Categorías
        </a>
        <div x-data="{open:false}" class="relative">
            <button @click="open=!open" class="flex items-center gap-1.5 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Carga Masiva
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" @click.outside="open=false" x-cloak class="absolute right-0 mt-1.5 w-52 bg-white rounded-xl shadow-lg border border-gray-200 py-1 z-50">
                <a href="{{ route('services.template') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Descargar plantilla
                </a>
                <label class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Importar archivo
                    <input type="file" accept=".csv,.xls,.xlsx" class="hidden" @change="window.dispatchEvent(new CustomEvent('do-import-svc', { detail: { file: $event.target.files[0] } })); $event.target.value=''">
                </label>
                <div class="border-t border-gray-100 my-1"></div>
                <a href="{{ route('services.export') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Exportar Excel
                </a>
            </div>
        </div>
        <button @click="openNew()"
                class="flex items-center gap-1.5 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo servicio
        </button>
    </div>
</div>

{{-- BODY --}}
<script>
window.__servicePageData = {
    services:   {!! Illuminate\Support\Js::from($services) !!},
    categories: {!! Illuminate\Support\Js::from($categories) !!},
    csrf:       '{{ $csrf }}',
    storeUrl:   '{{ route('services.store') }}',
    baseUrl:    '{{ url('/bixoadmin/services') }}',
    reorderUrl: '{{ route('services.reorder') }}',
    importUrl:  '{{ route('services.import') }}',
};
document.addEventListener('alpine:init', () => {
    Alpine.data('servicePage', () => ({
        services:   window.__servicePageData.services,
        categories: window.__servicePageData.categories,
        csrf:       window.__servicePageData.csrf,
        storeUrl:   window.__servicePageData.storeUrl,
        baseUrl:    window.__servicePageData.baseUrl,
        reorderUrl: window.__servicePageData.reorderUrl,
        importUrl:  window.__servicePageData.importUrl,
        search: '',
        filterCat: 0,
        filterAvail: '',
        panel: 'list',
        selected: null,
        tab: 'info',
        saving: false,
        deleting: false,
        form: {},
        importLog: { show: false, created: 0, updated: 0, errors: [] },

        get filtered() {
            return this.services.filter(s => {
                const q = this.search.toLowerCase();
                const matchQ = !q || s.name.toLowerCase().includes(q) || (s.description||'').toLowerCase().includes(q);
                const matchC = !this.filterCat || s.category_id == this.filterCat;
                const matchA = this.filterAvail === '' ? true : (this.filterAvail === '1' ? s.is_available : !s.is_available);
                return matchQ && matchC && matchA;
            });
        },

        get durationLabel() {
            if (!this.form.duration_min) return '';
            const m = parseInt(this.form.duration_min);
            if (m < 60) return m + ' min';
            const h = Math.floor(m/60), rm = m%60;
            return rm ? h+'h '+rm+'min' : h+'h';
        },

        openNew() {
            this.selected = null;
            this.tab = 'info';
            this.form = {
                name:'', description:'', notes:'', price:0,
                duration_min:'', modality:'presencial', category_id:'', is_available:true
            };
        },

        openEdit(s) {
            this.selected = s;
            this.tab = 'info';
            this.form = {
                name: s.name,
                description: s.description || '',
                notes: s.notes || '',
                price: s.price,
                duration_min: s.duration_min || '',
                modality: s.modality || 'presencial',
                category_id: s.category_id || '',
                is_available: s.is_available,
            };
        },

        async save() {
            this.saving = true;
            const url = this.selected ? this.baseUrl + '/' + this.selected.id : this.storeUrl;
            const method = this.selected ? 'PUT' : 'POST';
            try {
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({...this.form}),
                });
                const json = await res.json();
                if (res.ok) {
                    if (this.selected) {
                        const idx = this.services.findIndex(s => s.id === this.selected.id);
                        if (idx !== -1) this.services[idx] = json.service;
                        this.selected = json.service;
                    } else {
                        this.services.unshift(json.service);
                        this.selected = json.service;
                    }
                    window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg: 'Cambios guardados', type: 'success' } }));
                } else {
                    window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg: json.message || 'Error al guardar', type: 'error' } }));
                }
            } catch(e) {
                window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg: 'Error de red', type: 'error' } }));
            }
            this.saving = false;
        },

        async destroy() {
            if (!this.selected) return;
            const ok = await window.__confirm({
                title: 'Eliminar servicio',
                msg: 'Eliminar "' + this.selected.name + '"? Esta accion no se puede deshacer.',
                confirmLabel: 'Si, eliminar',
            });
            if (!ok) return;
            this.deleting = true;
            try {
                const res = await fetch(this.baseUrl + '/' + this.selected.id, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                });
                if (res.ok) {
                    window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg: 'Servicio eliminado', type: 'warning' } }));
                    this.services = this.services.filter(s => s.id !== this.selected.id);
                    this.selected = null;
                    this.form = {};
                }
            } catch(e) {
                window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg: 'Error de red', type: 'error' } }));
            }
            this.deleting = false;
        },

        dragId: null,
        dragStart(id) { this.dragId = id; },
        dragOver(id) {
            if (this.dragId === null || this.dragId === id) return;
            const from = this.services.findIndex(s => s.id === this.dragId);
            const to   = this.services.findIndex(s => s.id === id);
            if (from === -1 || to === -1) return;
            const arr = [...this.services];
            arr.splice(to, 0, arr.splice(from, 1)[0]);
            this.services = arr;
        },
        async dragEnd() {
            if (this.dragId === null) return;
            this.dragId = null;
            await fetch(this.reorderUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({ ids: this.services.map(s => s.id) }),
            });
        },

        async importarServicios(file) {
            if (!file) return;
            const fd = new FormData();
            fd.append('file', file);
            fd.append('_token', this.csrf);
            try {
                const res  = await fetch(this.importUrl, { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd });
                const data = await res.json();
                this.importLog = { show: true, created: data.created||0, updated: data.updated||0, errors: data.errors||[] };
                location.reload();
            } catch(e) {
                this.importLog = { show: true, created: 0, updated: 0, errors: ['Error de red al importar. Verifica que el archivo sea CSV o XLS valido.'] };
            }
        },

        init() {
            this.$nextTick(() => {
                const el = document.getElementById('service-count-label');
                if (el) el.textContent = this.services.length + ' servicios';
            });
            this.$watch('filtered', v => {
                const el = document.getElementById('service-count-label');
                if (el) el.textContent = v.length + ' servicios';
            });
            window.addEventListener('do-import-svc', (e) => { this.importarServicios(e.detail.file); });
        },
    }));
});
</script>
<div class="flex flex-1 overflow-hidden" x-data="servicePage()">

{{-- ─── PANEL FILTROS (60px) ─────────────────────────────────────────────────────── --}}
<div class="w-14 border-r border-gray-200 bg-gray-50 hidden md:flex flex-col items-center py-3 gap-2 flex-shrink-0">
    {{-- Por disponibilidad --}}
    <div class="relative group">
        <button @click="filterAvail = filterAvail==='' ? '1' : (filterAvail==='1' ? '0' : '')"
                :class="filterAvail!=='' ? 'bg-purple-100 text-purple-700' : 'text-gray-400 hover:text-gray-600'"
                class="w-9 h-9 flex items-center justify-center rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </button>
        <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">
            <template x-if="filterAvail===''">Disponibilidad: todos</template>
            <template x-if="filterAvail==='1'">Solo disponibles</template>
            <template x-if="filterAvail==='0'">Solo inactivos</template>
        </span>
    </div>

    {{-- Por categoría --}}
    <div class="relative group">
        <button @click="filterCat = 0"
                :class="filterCat===0 ? 'bg-purple-100 text-purple-700' : 'text-gray-400 hover:text-gray-600'"
                class="w-9 h-9 flex items-center justify-center rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </div>
    @foreach($categories as $cat)
    <div class="relative group">
        <button @click="filterCat = filterCat=={{ $cat->id }} ? 0 : {{ $cat->id }}"
                :class="filterCat==={{ $cat->id }} ? 'bg-purple-100 text-purple-700 font-bold' : 'text-gray-400 hover:text-gray-600'"
                class="w-9 h-9 flex items-center justify-center rounded-lg text-xs font-black transition"
                title="{{ $cat->name }}">
            {{ mb_substr($cat->name, 0, 2) }}
        </button>
        <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">
            {{ $cat->name }}
        </span>
    </div>
    @endforeach
</div>

{{-- ─── LISTA CENTRAL ─────────────────────────────────────────────────────────────── --}}
<div class="w-72 border-r border-gray-200 flex-col bg-white flex-shrink-0"
     :class="panel==='list' ? 'flex w-full md:w-72' : 'hidden md:flex md:w-72'">
    {{-- Búsqueda --}}
    <div class="p-3 border-b border-gray-200 flex-shrink-0">
        <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2">
            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" x-model="search" placeholder="Buscar servicio..."
                   class="bg-transparent text-sm outline-none flex-1 min-w-0 text-gray-700 placeholder-gray-400">
            <button x-show="search" @click="search=''" class="text-gray-400 hover:text-gray-600">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Lista --}}
    <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
        {{-- Nuevo --}}
        <button @click="openNew(); panel='detail'"
                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-purple-50 transition text-left"
                :class="!selected && Object.keys(form).length ? 'bg-purple-50 border-l-2 border-purple-500' : ''">
            <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-purple-700">Nuevo servicio</p>
                <p class="text-xs text-gray-400">Agregar al catálogo</p>
            </div>
        </button>

        <template x-for="s in filtered" :key="s.id">
            <button @click="openEdit(s); panel='detail'"
                    draggable="true"
                    @dragstart="dragStart(s.id)"
                    @dragover.prevent="dragOver(s.id)"
                    @dragend="dragEnd()"
                    class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition text-left cursor-grab active:cursor-grabbing"
                    :class="[selected?.id===s.id ? 'bg-indigo-50 border-l-2 border-indigo-500' : '', dragId===s.id ? 'opacity-40' : '']">
                <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate" x-text="s.name"></p>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-xs font-semibold text-purple-700" x-text="'{{ $currency }} ' + parseFloat(s.price).toFixed(2)"></span>
                        <span x-show="s.duration_min" class="text-[10px] text-gray-400"
                              x-text="s.duration_min < 60 ? s.duration_min+'min' : Math.floor(s.duration_min/60)+'h'+(s.duration_min%60?s.duration_min%60+'m':'')"></span>
                    </div>
                </div>
                <span :class="s.is_available ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400'"
                      class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md flex-shrink-0"
                      x-text="s.is_available ? 'Activo' : 'Inactivo'"></span>
            </button>
        </template>

        <div x-show="filtered.length === 0 && !selected" class="flex flex-col items-center justify-center py-12 px-4 text-center">
            <div class="w-14 h-14 rounded-2xl bg-purple-50 flex items-center justify-center mb-3">
                <svg class="w-7 h-7 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <template x-if="search || filterCat !== null || filterStatus !== ''">
                <div>
                    <p class="text-sm font-medium text-gray-700">Sin resultados</p>
                    <p class="text-xs text-gray-400 mt-1">Prueba con otros filtros</p>
                    <button @click="search=''; filterCat=null; filterStatus=''" class="mt-3 text-xs text-purple-600 font-medium hover:underline">Limpiar filtros</button>
                </div>
            </template>
            <template x-if="!search && filterCat === null && filterStatus === ''">
                <div>
                    <p class="text-sm font-medium text-gray-700">Sin servicios aún</p>
                    <p class="text-xs text-gray-400 mt-1 mb-3">Crea tu primer servicio o importa desde Excel</p>
                    <button @click="openNew(); panel='detail'"
                            class="text-xs bg-purple-600 hover:bg-purple-700 text-white font-medium px-3 py-1.5 rounded-lg transition">
                        + Crear servicio
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>

{{-- ─── PANEL DETALLE ─────────────────────────────────────────────────────────────── --}}
<div class="flex-col overflow-hidden bg-white"
     :class="panel==='detail' ? 'flex flex-1' : 'hidden md:flex md:flex-1'">

    {{-- Vacío --}}
    <template x-if="!selected && !Object.keys(form).length">
        <div class="flex-1 flex flex-col items-center justify-center text-center p-10 text-gray-300">
            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <p class="text-lg font-semibold text-gray-400">Selecciona un servicio</p>
            <p class="text-sm text-gray-300 mt-1">o crea uno nuevo</p>
        </div>
    </template>

    {{-- Formulario --}}
    <template x-if="selected || Object.keys(form).length">
        <div class="flex flex-col h-full">

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-200 flex items-center flex-shrink-0">
                <button @click="panel='list'" type="button"
                        class="md:hidden mr-3 flex-shrink-0 text-gray-400 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <div>
                    <h2 class="font-semibold text-gray-800 text-base"
                        x-text="selected ? (form.name || 'Sin nombre') : 'Nuevo servicio'"></h2>
                    <p class="text-xs text-gray-400 mt-0.5"
                       x-text="selected ? ('ID #'+selected.id+(selected.category_name ? ' · '+selected.category_name : '')) : 'Completa la información del servicio'"></p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500">Disponible</span>
                    <button @click="form.is_available = !form.is_available" type="button"
                            :class="form.is_available ? 'bg-green-500' : 'bg-gray-300'"
                            class="relative w-10 h-5 rounded-full transition-colors duration-200">
                        <span :class="form.is_available ? 'translate-x-5' : 'translate-x-0.5'"
                              class="absolute top-0.5 left-0 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200 block"></span>
                    </button>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="flex border-b border-gray-200 px-6 flex-shrink-0 bg-white">
                @foreach([
                    ['key'=>'info',  'label'=>'Información'],
                    ['key'=>'price', 'label'=>'Precio'],
                    ['key'=>'img',   'label'=>'Imagen'],
                ] as $t)
                <button @click="tab='{{ $t['key'] }}'"
                        :class="tab==='{{ $t['key'] }}' ? 'border-b-2 border-purple-600 text-purple-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-3 text-sm transition whitespace-nowrap">
                    {{ $t['label'] }}
                </button>
                @endforeach
            </div>

            {{-- Tab Content --}}
            <div class="flex-1 overflow-y-auto p-6">

                {{-- ═══ TAB: INFORMACIÓN ═══ --}}
                <div x-show="tab==='info'" class="space-y-4 max-w-2xl">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Nombre --}}
                        <div class="sm:col-span-2">
                            <label class="label">Nombre del servicio *</label>
                            <input type="text" x-model="form.name" placeholder="Ej: Corte de cabello, Consultoría, Diseño web..."
                                   class="input" required>
                        </div>

                        {{-- Categoría --}}
                        <div>
                            <label class="label">Categoría</label>
                            <select x-model.number="form.category_id" class="input">
                                <option value="">Sin categoría</option>
                                <template x-for="c in categories" :key="c.id">
                                    <template x-if="c.children && c.children.length > 0">
                                        <optgroup :label="c.name">
                                            <template x-for="s in c.children" :key="s.id">
                                                <option :value="s.id" x-text="'  └ ' + s.name"></option>
                                            </template>
                                        </optgroup>
                                    </template>
                                    <template x-if="!c.children || c.children.length === 0">
                                        <option :value="c.id" x-text="c.name"></option>
                                    </template>
                                </template>
                            </select>
                        </div>

                        {{-- Modalidad --}}
                        <div>
                            <label class="label">Modalidad</label>
                            <select x-model="form.modality" class="input">
                                <option value="presencial">Presencial</option>
                                <option value="online">Online / Remoto</option>
                                <option value="domicilio">A domicilio</option>
                                <option value="híbrido">Híbrido</option>
                            </select>
                        </div>

                        {{-- Duración --}}
                        <div>
                            <label class="label">Duración</label>
                            <div class="flex items-center gap-2">
                                <input type="number" x-model="form.duration_min" min="1" placeholder="Ej: 60"
                                       class="input flex-1">
                                <span class="text-xs text-gray-500 whitespace-nowrap">min</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1" x-text="durationLabel || 'Ingresa minutos (ej: 90 = 1h 30min)'"></p>
                        </div>

                        {{-- Estado (referencia visual) --}}
                        <div class="flex items-end">
                            <div :class="form.is_available ? 'bg-green-50 border-green-200 text-green-700' : 'bg-gray-50 border-gray-200 text-gray-500'"
                                 class="flex items-center gap-2 border rounded-xl px-4 py-2.5 text-sm w-full">
                                <div :class="form.is_available ? 'bg-green-500' : 'bg-gray-400'"
                                     class="w-2 h-2 rounded-full flex-shrink-0"></div>
                                <span x-text="form.is_available ? 'Visible en catálogo público' : 'Oculto del catálogo'"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Descripción --}}
                    <div>
                        <label class="label">Descripción</label>
                        <textarea x-model="form.description" rows="4"
                                  placeholder="Describe qué incluye el servicio, qué resultados esperar, a quién va dirigido..."
                                  class="input resize-none"></textarea>
                    </div>

                    {{-- Notas internas --}}
                    <div>
                        <label class="label">Notas internas</label>
                        <textarea x-model="form.notes" rows="2"
                                  placeholder="Notas privadas: materiales necesarios, instrucciones especiales..."
                                  class="input resize-none text-xs"></textarea>
                        <p class="text-xs text-gray-400 mt-1">Solo visible en el panel de administración</p>
                    </div>
                </div>

                {{-- ═══ TAB: PRECIO ═══ --}}
                <div x-show="tab==='price'" class="space-y-5 max-w-lg">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Precio principal --}}
                        <div class="sm:col-span-2">
                            <label class="label">Precio del servicio *</label>
                            <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden focus-within:border-purple-400 transition">
                                <span class="px-3 py-2.5 text-sm text-gray-500 bg-gray-50 border-r border-gray-200">{{ $currency }}</span>
                                <input type="number" x-model="form.price" min="0" step="0.01"
                                       class="flex-1 px-3 py-2.5 text-sm outline-none" placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    {{-- Box resumen --}}
                    <div class="bg-purple-50 border border-purple-200 rounded-xl p-4">
                        <p class="text-xs font-semibold text-purple-700 mb-3 uppercase tracking-wide">Resumen de precio</p>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Precio de venta</span>
                                <span class="font-semibold text-gray-800">
                                    {{ $currency }} <span x-text="parseFloat(form.price||0).toFixed(2)"></span>
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Duración</span>
                                <span class="text-gray-700" x-text="durationLabel || '—'"></span>
                            </div>
                            <div class="flex justify-between text-sm" x-show="form.duration_min && form.price">
                                <span class="text-gray-600">Precio / hora</span>
                                <span class="font-semibold text-purple-700">
                                    {{ $currency }} <span x-text="(parseFloat(form.price||0) / (parseFloat(form.duration_min||60)/60)).toFixed(2)"></span>
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Tarjeta info --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
                        <p class="font-semibold mb-1">Próximamente: precios por paquete</p>
                        <p class="text-xs text-blue-600">Podrás definir paquetes (1 sesión, 4 sesiones, 10 sesiones) con precios diferenciados y crear combos de servicios.</p>
                    </div>
                </div>

                {{-- ═══ TAB: IMAGEN ═══ --}}
                <div x-show="tab==='img'" class="space-y-4 max-w-lg">
                    <div class="border-2 border-dashed border-gray-200 rounded-2xl p-10 text-center text-gray-300">
                        <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm font-medium text-gray-400">Carga de imagen próximamente</p>
                        <p class="text-xs text-gray-300 mt-1">Arrastra o selecciona una imagen del servicio</p>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-800">
                        <p class="font-semibold mb-1">SEO automático para imágenes</p>
                        <p class="text-xs text-green-700">Al subir la imagen, el sistema generará automáticamente el texto alternativo con el nombre del servicio y el negocio para mejorar el posicionamiento en buscadores.</p>
                    </div>
                </div>

            </div>

            {{-- FOOTER --}}
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between flex-shrink-0 bg-white">
                <div>
                    <button x-show="selected" @click="destroy()" :disabled="deleting"
                            class="flex items-center gap-1.5 px-4 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span x-text="deleting ? 'Eliminando...' : 'Eliminar'"></span>
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="selected=null; form={}; panel='list';" type="button"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button @click="save()" :disabled="saving || !form.name"
                            class="flex items-center gap-2 px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                        <svg x-show="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="saving ? 'Guardando...' : (selected ? 'Guardar cambios' : 'Crear servicio')"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

{{-- ── Modal log de importación ────────────────────────────────────────────── --}}
<div x-show="importLog.show" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
     @keydown.escape.window="importLog.show=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <span class="text-xl">📋</span>
                <h3 class="font-semibold text-gray-800">Resultado de importación</h3>
            </div>
            <button @click="importLog.show=false" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-6 py-4 grid grid-cols-2 gap-3">
            <div class="rounded-xl bg-green-50 border border-green-200 px-3 py-3 text-center">
                <div class="text-2xl font-bold text-green-700" x-text="importLog.created"></div>
                <div class="text-xs text-green-600 mt-0.5">Creados</div>
            </div>
            <div class="rounded-xl bg-blue-50 border border-blue-200 px-3 py-3 text-center">
                <div class="text-2xl font-bold text-blue-700" x-text="importLog.updated"></div>
                <div class="text-xs text-blue-600 mt-0.5">Actualizados</div>
            </div>
        </div>
        <div x-show="importLog.created===0 && importLog.updated===0 && importLog.errors.length===0"
             class="px-6 pb-4 text-sm text-gray-500 text-center">
            No se encontraron filas para importar.
        </div>
        <div x-show="importLog.errors.length > 0" class="px-6 pb-4">
            <p class="text-xs font-semibold text-red-600 mb-2">Detalle de errores (<span x-text="importLog.errors.length"></span>):</p>
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
                    class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition">
                Entendido
            </button>
        </div>
    </div>
</div>

</div>
</div>
</x-slot>
</x-app-layout>
