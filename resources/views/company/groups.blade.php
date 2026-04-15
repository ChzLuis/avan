<x-app-layout>
<x-slot name="slot">

@php
    $csrf = csrf_token();
    $pid  = $project->id;
@endphp

<div class="flex flex-col h-full w-full overflow-hidden">

{{-- TOP BAR --}}
<div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 bg-white flex-shrink-0">
    <div>
        <h1 class="text-lg font-semibold text-gray-800">{{ $type === 'employee' ? 'Grupos de Trabajadores' : 'Grupos de Clientes' }}</h1>
        <p class="text-xs text-gray-400 mt-0.5" id="group-count-label">Cargando...</p>
    </div>
    <div class="flex items-center gap-2">
        <button @click="openNew()"
                class="flex items-center gap-1.5 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo grupo
        </button>
    </div>
</div>

{{-- BODY --}}
<div class="flex flex-1 overflow-hidden"
     x-data="{
        groups: {{ Illuminate\Support\Js::from($groups) }},
        search: '',
        filterType: '',
        filterStatus: '',
        panel: 'list',

        selected: null,
        tab: 'config',
        saving: false,
        deleting: false,
        form: {},
        csrf: '{{ $csrf }}',
        storeUrl: '{{ route('groups.store') }}',
        baseUrl: '{{ url('/'.$pid.'/company/groups') }}',

        get filtered() {
            return this.groups.filter(g => {
                const q = this.search.toLowerCase();
                const matchQ = !q || g.name.toLowerCase().includes(q) || (g.description||'').toLowerCase().includes(q);
                const matchT = !this.filterType || g.type === this.filterType;
                const matchS = this.filterStatus === '' ? true : (this.filterStatus === '1' ? g.is_active : !g.is_active);
                return matchQ && matchT && matchS;
            });
        },

        typeLabel(type) {
            return type === 'client' ? 'Clientes' : 'Empleados';
        },

        openNew() {
            this.selected = null;
            this.tab = 'config';
            this.form = {
                name: '', type: '{{ $type }}', description: '', color: '#6366f1', is_active: true
            };
        },

        openEdit(g) {
            this.selected = g;
            this.tab = 'config';
            this.form = {
                name:        g.name,
                type:        g.type        || 'client',
                description: g.description || '',
                color:       g.color       || '#6366f1',
                is_active:   g.is_active,
            };
        },

        async save() {
            this.saving = true;
            const url = this.selected
                ? this.baseUrl + '/' + this.selected.id
                : this.storeUrl;
            const method = this.selected ? 'PUT' : 'POST';
            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({...this.form}),
                });
                const json = await res.json();
                if (res.ok) {
                    if (this.selected) {
                        const idx = this.groups.findIndex(g => g.id === this.selected.id);
                        if (idx !== -1) this.groups[idx] = json.group;
                        this.selected = json.group;
                    } else {
                        this.groups.unshift(json.group);
                        this.selected = json.group;
                    }
                } else {
                    alert(json.message || 'Error al guardar');
                }
            } catch(e) {
                alert('Error de red');
            }
            this.saving = false;
        },

        async destroy() {
            if (!this.selected || !confirm('¿Eliminar este grupo?')) return;
            this.deleting = true;
            try {
                const res = await fetch(this.baseUrl + '/' + this.selected.id, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                });
                if (res.ok) {
                    this.groups = this.groups.filter(g => g.id !== this.selected.id);
                    this.selected = null;
                    this.form = {};
                }
            } catch(e) { alert('Error de red'); }
            this.deleting = false;
        },
    }"
     x-init="
        $nextTick(() => {
            const el = document.getElementById('group-count-label');
            if (el) el.textContent = groups.length + (groups.length === 1 ? ' grupo' : ' grupos');
        });
        $watch('filtered', v => {
            const el = document.getElementById('group-count-label');
            if (el) el.textContent = v.length + (v.length === 1 ? ' grupo' : ' grupos');
        });
     ">

{{-- ─── PANEL FILTROS (60px) ─────────────────────────────────────────────────────── --}}
<div class="w-14 border-r border-gray-200 bg-gray-50 hidden md:flex flex-col items-center py-3 gap-2 flex-shrink-0">

    {{-- Todos los tipos --}}
    <div class="relative group">
        <button @click="filterType = ''"
                :class="filterType==='' ? 'bg-violet-100 text-violet-700' : 'text-gray-400 hover:text-gray-600'"
                class="w-9 h-9 flex items-center justify-center rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">
            Todos los grupos
        </span>
    </div>

    {{-- Solo clientes --}}
    <div class="relative group">
        <button @click="filterType = filterType==='client' ? '' : 'client'"
                :class="filterType==='client' ? 'bg-violet-100 text-violet-700' : 'text-gray-400 hover:text-gray-600'"
                class="w-9 h-9 flex items-center justify-center rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </button>
        <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">
            Solo clientes
        </span>
    </div>

    {{-- Solo empleados --}}
    <div class="relative group">
        <button @click="filterType = filterType==='employee' ? '' : 'employee'"
                :class="filterType==='employee' ? 'bg-violet-100 text-violet-700' : 'text-gray-400 hover:text-gray-600'"
                class="w-9 h-9 flex items-center justify-center rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </button>
        <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">
            Solo empleados
        </span>
    </div>

    <div class="w-6 border-t border-gray-200 my-1"></div>

    {{-- Por estado --}}
    <div class="relative group">
        <button @click="filterStatus = filterStatus==='' ? '1' : (filterStatus==='1' ? '0' : '')"
                :class="filterStatus!=='' ? 'bg-violet-100 text-violet-700' : 'text-gray-400 hover:text-gray-600'"
                class="w-9 h-9 flex items-center justify-center rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </button>
        <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">
            <template x-if="filterStatus===''">Estado: todos</template>
            <template x-if="filterStatus==='1'">Solo activos</template>
            <template x-if="filterStatus==='0'">Solo inactivos</template>
        </span>
    </div>

    {{-- Resetear filtros --}}
    <div class="relative group">
        <button @click="filterType = ''; filterStatus = ''; search = ''"
                :class="filterType!=='' || filterStatus!=='' ? 'bg-red-100 text-red-500' : 'text-gray-300 hover:text-gray-500'"
                class="w-9 h-9 flex items-center justify-center rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">
            Limpiar filtros
        </span>
    </div>

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
            <input type="text" x-model="search" placeholder="Buscar grupo..."
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
        {{-- Nuevo grupo --}}
        <button @click="openNew(); panel='detail'"
                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-violet-50 transition text-left"
                :class="!selected && Object.keys(form).length ? 'bg-violet-50 border-l-2 border-violet-500' : ''">
            <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-violet-700">Nuevo grupo</p>
                <p class="text-xs text-gray-400">Crear grupo de usuarios</p>
            </div>
        </button>

        <template x-for="g in filtered" :key="g.id">
            <button @click="openEdit(g); panel='detail'"
                    class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition text-left"
                    :class="selected?.id===g.id ? 'bg-violet-50 border-l-2 border-violet-500' : ''">
                {{-- Color dot --}}
                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                     :style="'background-color: ' + (g.color || '#6366f1') + '22'">
                    <div class="w-3 h-3 rounded-full" :style="'background-color: ' + (g.color || '#6366f1')"></div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate" x-text="g.name"></p>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span :class="g.type==='client' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'"
                              class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md"
                              x-text="g.type==='client' ? 'Clientes' : 'Empleados'"></span>
                    </div>
                </div>
                <span :class="g.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400'"
                      class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md flex-shrink-0"
                      x-text="g.is_active ? 'Activo' : 'Inactivo'"></span>
            </button>
        </template>

        <div x-show="filtered.length===0" class="px-6 py-12 text-center">
            <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="text-sm text-gray-400">Sin grupos</p>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="text-lg font-semibold text-gray-400">Selecciona un grupo</p>
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
                <div class="flex items-center gap-3">
                    {{-- Color preview dot in header --}}
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                         :style="'background-color: ' + (form.color || '#6366f1') + '22'">
                        <div class="w-3 h-3 rounded-full" :style="'background-color: ' + (form.color || '#6366f1')"></div>
                    </div>
                    <div>
                        <h2 class="font-semibold text-gray-800 text-base"
                            x-text="selected ? (form.name || 'Sin nombre') : 'Nuevo grupo'"></h2>
                        <p class="text-xs text-gray-400 mt-0.5"
                           x-text="selected ? ('ID #' + selected.id + ' · ' + typeLabel(form.type)) : 'Completa la información del grupo'"></p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500">Activo</span>
                    <button @click="form.is_active = !form.is_active" type="button"
                            :class="form.is_active ? 'bg-green-500' : 'bg-gray-300'"
                            class="relative w-10 h-5 rounded-full transition-colors duration-200">
                        <span :class="form.is_active ? 'translate-x-5' : 'translate-x-0.5'"
                              class="absolute top-0.5 left-0 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200 block"></span>
                    </button>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="flex border-b border-gray-200 px-6 flex-shrink-0 bg-white">
                @foreach([
                    ['key'=>'config',  'label'=>'Configuración'],
                    ['key'=>'members', 'label'=>'Miembros'],
                ] as $t)
                <button @click="tab='{{ $t['key'] }}'"
                        :class="tab==='{{ $t['key'] }}' ? 'border-b-2 border-violet-600 text-violet-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-3 text-sm transition whitespace-nowrap">
                    {{ $t['label'] }}
                </button>
                @endforeach
            </div>

            {{-- Tab Content --}}
            <div class="flex-1 overflow-y-auto p-6">

                {{-- ═══ TAB: CONFIGURACIÓN ═══ --}}
                <div x-show="tab==='config'" class="space-y-4 max-w-2xl">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Nombre --}}
                        <div class="sm:col-span-2">
                            <label class="label">Nombre del grupo *</label>
                            <input type="text" x-model="form.name" placeholder="Ej: Clientes VIP, Mayoristas, Personal de ventas..."
                                   class="input" required>
                        </div>

                        {{-- Tipo --}}
                        <div>
                            <label class="label">Tipo de grupo</label>
                            <select x-model="form.type" class="input">
                                <option value="client">Clientes</option>
                                <option value="employee">Empleados</option>
                            </select>
                        </div>

                        {{-- Color --}}
                        <div>
                            <label class="label">Color de identificación</label>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl border border-gray-200 overflow-hidden flex-shrink-0 cursor-pointer relative"
                                     :style="'background-color: ' + (form.color || '#6366f1')">
                                    <input type="color" x-model="form.color"
                                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                           title="Seleccionar color">
                                </div>
                                <div class="flex-1">
                                    <input type="text" x-model="form.color" placeholder="#6366f1"
                                           class="input font-mono text-sm"
                                           maxlength="7"
                                           @input="if(!/^#[0-9a-fA-F]{0,6}$/.test(form.color)) form.color = form.color.replace(/[^#0-9a-fA-F]/g,'')">
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Haz clic en el cuadro de color o escribe el código hexadecimal</p>
                        </div>

                        {{-- Estado visual --}}
                        <div class="sm:col-span-2">
                            <div :class="form.is_active ? 'bg-green-50 border-green-200 text-green-700' : 'bg-gray-50 border-gray-200 text-gray-500'"
                                 class="flex items-center gap-2 border rounded-xl px-4 py-2.5 text-sm w-full">
                                <div :class="form.is_active ? 'bg-green-500' : 'bg-gray-400'"
                                     class="w-2 h-2 rounded-full flex-shrink-0"></div>
                                <span x-text="form.is_active ? 'Grupo activo y disponible' : 'Grupo inactivo'"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Descripción --}}
                    <div>
                        <label class="label">Descripción</label>
                        <textarea x-model="form.description" rows="3"
                                  placeholder="Describe el propósito de este grupo: criterios de pertenencia, beneficios, restricciones..."
                                  class="input resize-none"></textarea>
                    </div>

                    {{-- Vista previa --}}
                    <div x-show="form.name" class="rounded-xl border border-gray-200 p-4">
                        <p class="text-xs font-semibold text-gray-500 mb-3 uppercase tracking-wide">Vista previa</p>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                                 :style="'background-color: ' + (form.color || '#6366f1') + '22'">
                                <div class="w-4 h-4 rounded-full" :style="'background-color: ' + (form.color || '#6366f1')"></div>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800" x-text="form.name"></p>
                                <span :class="form.type==='client' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'"
                                      class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md"
                                      x-text="form.type==='client' ? 'Clientes' : 'Empleados'"></span>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- ═══ TAB: MIEMBROS ═══ --}}
                <div x-show="tab==='members'" class="space-y-4 max-w-2xl">
                    <div class="border-2 border-dashed border-gray-200 rounded-2xl p-10 text-center text-gray-300">
                        <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-gray-400">Próximamente</p>
                        <p class="text-xs text-gray-300 mt-1">Asignar clientes o empleados a este grupo</p>
                    </div>
                    <div class="bg-violet-50 border border-violet-200 rounded-xl p-4 text-sm text-violet-800">
                        <p class="font-semibold mb-1">Próximamente: asignación de miembros</p>
                        <p class="text-xs text-violet-600">Podrás asignar clientes o empleados a este grupo para enviar comunicaciones segmentadas y aplicar descuentos o condiciones especiales por grupo.</p>
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
                            class="flex items-center gap-2 px-5 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                        <svg x-show="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="saving ? 'Guardando...' : (selected ? 'Guardar cambios' : 'Crear grupo')"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

</div>
</div>

</x-slot>
</x-app-layout>
