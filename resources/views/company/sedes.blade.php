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
        <h1 class="text-lg font-semibold text-gray-800">Compañía / Sedes</h1>
        <p class="text-xs text-gray-400 mt-0.5" id="sede-count-label">Cargando...</p>
    </div>
    <div class="flex items-center gap-2">
        <button @click="openNew()"
                class="flex items-center gap-1.5 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva sede
        </button>
    </div>
</div>

{{-- BODY --}}
<div class="flex flex-1 overflow-hidden"
     x-data="{
        sedes: {{ Illuminate\Support\Js::from($sedes) }},
        search: '',
        filterStatus: '',
        panel: 'list',

        selected: null,
        tab: 'info',
        saving: false,
        deleting: false,
        form: {},
        csrf: '{{ $csrf }}',
        storeUrl: '{{ route('sedes.store') }}',
        baseUrl: '{{ url('/'.$pid.'/company/sedes') }}',

        get filtered() {
            return this.sedes.filter(s => {
                const q = this.search.toLowerCase();
                const matchQ = !q || s.name.toLowerCase().includes(q) || (s.address||'').toLowerCase().includes(q) || (s.manager||'').toLowerCase().includes(q);
                const matchS = this.filterStatus === '' ? true : (this.filterStatus === '1' ? s.is_active : !s.is_active);
                return matchQ && matchS;
            });
        },

        openNew() {
            this.selected = null;
            this.tab = 'info';
            this.form = {
                name: '', address: '', phone: '', email: '', manager: '', notes: '', is_active: true
            };
        },

        openEdit(s) {
            this.selected = s;
            this.tab = 'info';
            this.form = {
                name:    s.name,
                address: s.address  || '',
                phone:   s.phone    || '',
                email:   s.email    || '',
                manager: s.manager  || '',
                notes:   s.notes    || '',
                is_active: s.is_active,
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
                        const idx = this.sedes.findIndex(s => s.id === this.selected.id);
                        if (idx !== -1) this.sedes[idx] = json.sede;
                        this.selected = json.sede;
                    } else {
                        this.sedes.unshift(json.sede);
                        this.selected = json.sede;
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
            if (!this.selected || !confirm('¿Eliminar esta sede?')) return;
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
                    this.sedes = this.sedes.filter(s => s.id !== this.selected.id);
                    this.selected = null;
                    this.form = {};
                }
            } catch(e) { alert('Error de red'); }
            this.deleting = false;
        },
    }"
     x-init="
        $nextTick(() => {
            const el = document.getElementById('sede-count-label');
            if (el) el.textContent = sedes.length + (sedes.length === 1 ? ' sede' : ' sedes');
        });
        $watch('filtered', v => {
            const el = document.getElementById('sede-count-label');
            if (el) el.textContent = v.length + (v.length === 1 ? ' sede' : ' sedes');
        });
     ">

{{-- ─── PANEL FILTROS (60px) ─────────────────────────────────────────────────────── --}}
<div class="w-14 border-r border-gray-200 bg-gray-50 hidden md:flex flex-col items-center py-3 gap-2 flex-shrink-0">

    {{-- Por estado activo --}}
    <div class="relative group">
        <button @click="filterStatus = filterStatus==='' ? '1' : (filterStatus==='1' ? '0' : '')"
                :class="filterStatus!=='' ? 'bg-orange-100 text-orange-700' : 'text-gray-400 hover:text-gray-600'"
                class="w-9 h-9 flex items-center justify-center rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </button>
        <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">
            <template x-if="filterStatus===''">Estado: todos</template>
            <template x-if="filterStatus==='1'">Solo activas</template>
            <template x-if="filterStatus==='0'">Solo inactivas</template>
        </span>
    </div>

    {{-- Resetear filtros --}}
    <div class="relative group">
        <button @click="filterStatus = ''; search = ''"
                :class="filterStatus!=='' ? 'bg-red-100 text-red-500' : 'text-gray-300 hover:text-gray-500'"
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
            <input type="text" x-model="search" placeholder="Buscar sede..."
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
        {{-- Nueva sede --}}
        <button @click="openNew(); panel='detail'"
                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-orange-50 transition text-left"
                :class="!selected && Object.keys(form).length ? 'bg-orange-50 border-l-2 border-orange-500' : ''">
            <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-orange-700">Nueva sede</p>
                <p class="text-xs text-gray-400">Registrar nueva sucursal</p>
            </div>
        </button>

        <template x-for="s in filtered" :key="s.id">
            <button @click="openEdit(s); panel='detail'"
                    class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition text-left"
                    :class="selected?.id===s.id ? 'bg-orange-50 border-l-2 border-orange-500' : ''">
                <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate" x-text="s.name"></p>
                    <p class="text-xs text-gray-400 truncate mt-0.5" x-text="s.address || 'Sin dirección'"></p>
                </div>
                <span :class="s.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400'"
                      class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md flex-shrink-0"
                      x-text="s.is_active ? 'Activa' : 'Inactiva'"></span>
            </button>
        </template>

        <div x-show="filtered.length===0" class="px-6 py-12 text-center">
            <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <p class="text-sm text-gray-400">Sin sedes</p>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <p class="text-lg font-semibold text-gray-400">Selecciona una sede</p>
            <p class="text-sm text-gray-300 mt-1">o crea una nueva</p>
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
                        x-text="selected ? (form.name || 'Sin nombre') : 'Nueva sede'"></h2>
                    <p class="text-xs text-gray-400 mt-0.5"
                       x-text="selected ? ('ID #' + selected.id + (form.manager ? ' · ' + form.manager : '')) : 'Completa la información de la sede'"></p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500">Activa</span>
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
                    ['key'=>'info',    'label'=>'Información'],
                    ['key'=>'contact', 'label'=>'Contacto'],
                    ['key'=>'staff',   'label'=>'Empleados'],
                ] as $t)
                <button @click="tab='{{ $t['key'] }}'"
                        :class="tab==='{{ $t['key'] }}' ? 'border-b-2 border-orange-600 text-orange-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
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
                            <label class="label">Nombre de la sede *</label>
                            <input type="text" x-model="form.name" placeholder="Ej: Sede Central, Sucursal Norte, Oficina Lima..."
                                   class="input" required>
                        </div>

                        {{-- Dirección --}}
                        <div class="sm:col-span-2">
                            <label class="label">Dirección</label>
                            <input type="text" x-model="form.address" placeholder="Ej: Av. Javier Prado 1234, San Isidro"
                                   class="input">
                        </div>

                        {{-- Responsable --}}
                        <div class="sm:col-span-2">
                            <label class="label">Responsable de sede</label>
                            <input type="text" x-model="form.manager" placeholder="Nombre del encargado o jefe de sede"
                                   class="input">
                        </div>

                        {{-- Estado (referencia visual) --}}
                        <div class="sm:col-span-2">
                            <div :class="form.is_active ? 'bg-green-50 border-green-200 text-green-700' : 'bg-gray-50 border-gray-200 text-gray-500'"
                                 class="flex items-center gap-2 border rounded-xl px-4 py-2.5 text-sm w-full">
                                <div :class="form.is_active ? 'bg-green-500' : 'bg-gray-400'"
                                     class="w-2 h-2 rounded-full flex-shrink-0"></div>
                                <span x-text="form.is_active ? 'Sede activa y operativa' : 'Sede inactiva o cerrada'"></span>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- ═══ TAB: CONTACTO ═══ --}}
                <div x-show="tab==='contact'" class="space-y-4 max-w-2xl">

                    {{-- Teléfono --}}
                    <div>
                        <label class="label">Teléfono</label>
                        <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden focus-within:border-orange-400 transition">
                            <span class="px-3 py-2.5 bg-gray-50 border-r border-gray-200">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </span>
                            <input type="text" x-model="form.phone" placeholder="Ej: +51 1 234-5678"
                                   class="flex-1 px-3 py-2.5 text-sm outline-none">
                        </div>
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="label">Correo electrónico</label>
                        <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden focus-within:border-orange-400 transition">
                            <span class="px-3 py-2.5 bg-gray-50 border-r border-gray-200">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </span>
                            <input type="email" x-model="form.email" placeholder="Ej: sede.central@empresa.com"
                                   class="flex-1 px-3 py-2.5 text-sm outline-none">
                        </div>
                    </div>

                    {{-- Tarjeta de resumen de contacto --}}
                    <div x-show="form.phone || form.email" class="bg-orange-50 border border-orange-200 rounded-xl p-4">
                        <p class="text-xs font-semibold text-orange-700 mb-3 uppercase tracking-wide">Datos de contacto</p>
                        <div class="space-y-2">
                            <div x-show="form.phone" class="flex items-center gap-3 text-sm">
                                <svg class="w-4 h-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <span class="text-gray-700" x-text="form.phone"></span>
                            </div>
                            <div x-show="form.email" class="flex items-center gap-3 text-sm">
                                <svg class="w-4 h-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-gray-700" x-text="form.email"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Notas --}}
                    <div>
                        <label class="label">Notas internas</label>
                        <textarea x-model="form.notes" rows="3"
                                  placeholder="Horarios especiales, instrucciones de acceso, observaciones..."
                                  class="input resize-none text-xs"></textarea>
                        <p class="text-xs text-gray-400 mt-1">Solo visible en el panel de administración</p>
                    </div>

                </div>

                {{-- ═══ TAB: EMPLEADOS ═══ --}}
                <div x-show="tab==='staff'" class="space-y-4 max-w-2xl">
                    <div class="border-2 border-dashed border-gray-200 rounded-2xl p-10 text-center text-gray-300">
                        <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-gray-400">Próximamente</p>
                        <p class="text-xs text-gray-300 mt-1">Listar empleados asignados a esta sede</p>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
                        <p class="font-semibold mb-1">Próximamente: asignación de empleados</p>
                        <p class="text-xs text-blue-600">Podrás asignar empleados a sedes específicas para organizar el equipo por sucursal y filtrar la agenda por sede.</p>
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
                            class="flex items-center gap-2 px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                        <svg x-show="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="saving ? 'Guardando...' : (selected ? 'Guardar cambios' : 'Crear sede')"></span>
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
