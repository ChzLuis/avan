<x-app-layout>
<x-slot name="slot">

@php
    $csrf = csrf_token();
    $pid  = $project->id;
    $catalogsUrl = route('catalogs.index');
@endphp

<div class="flex flex-col h-full w-full overflow-hidden">

{{-- TOP BAR --}}
<div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 bg-white flex-shrink-0">
    <div>
        <h1 class="text-lg font-semibold text-gray-800">Equipo / Empleados</h1>
        <p class="text-xs text-gray-400 mt-0.5" id="employee-count-label">Cargando...</p>
    </div>
    <div class="flex items-center gap-2">
        <button @click="openNew()"
                class="flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo empleado
        </button>
    </div>
</div>

{{-- BODY --}}
<div class="flex flex-1 overflow-hidden"
     x-data="{
        employees: {{ Illuminate\Support\Js::from($employees->load('user')->map(fn($e) => [
            'id'           => $e->id,
            'name'         => $e->name,
            'role'         => $e->role ?? '',
            'area'         => $e->area ?? '',
            'phone'        => $e->phone ?? '',
            'email'        => $e->email ?? '',
            'hire_date'    => $e->hire_date?->format('Y-m-d') ?? '',
            'is_available' => $e->is_active,
            'has_user'     => (bool)$e->user_id,
            'username'     => $e->user?->username ?? '',
        ])) }},
        departments:   {{ Illuminate\Support\Js::from($departments) }},
        jobTitles:     {{ Illuminate\Support\Js::from($jobTitles) }},
        contractTypes: {{ Illuminate\Support\Js::from($contractTypes) }},
        search: '',
        filterArea: '',
        filterStatus: '',
        panel: 'list',

        selected: null,
        creating: false,
        tab: 'info',
        saving: false,
        deleting: false,
        form: {},
        passwordModal: false,
        generatedPassword: '',
        generatedEmail: '',
        csrf: '{{ $csrf }}',
        storeUrl: '{{ route('hr.employees.store') }}',
        baseUrl: '{{ route('hr.employees.store') }}',

        get filtered() {
            return this.employees.filter(e => {
                const q = this.search.toLowerCase();
                const matchQ = !q
                    || e.name.toLowerCase().includes(q)
                    || (e.role  || '').toLowerCase().includes(q)
                    || (e.area  || '').toLowerCase().includes(q);
                const matchA = !this.filterArea   || e.area === this.filterArea;
                const matchS = this.filterStatus === ''
                    ? true
                    : (this.filterStatus === 'active' ? e.is_available : !e.is_available);
                return matchQ && matchA && matchS;
            });
        },

        get uniqueAreas() {
            const areas = this.employees.map(e => e.area).filter(a => a && a.trim() !== '');
            return [...new Set(areas)].sort();
        },

        openNew() {
            this.selected  = null;
            this.creating  = true;
            this.tab       = 'info';
            this.form = {
                name: '', role: '', area: '', phone: '',
                email: '', hire_date: '', is_available: true,
                username: '', password: '', password_confirmation: '',
            };
        },

        select(e) {
            this.selected  = e;
            this.creating  = false;
            this.tab       = 'info';
            this.form      = { ...e, password: '', password_confirmation: '' };
        },

        async save() {
            this.saving = true;
            const url    = this.selected
                ? this.baseUrl + '/' + this.selected.id
                : this.storeUrl;
            const method = this.selected ? 'PUT' : 'POST';
            try {
                const payload = {
                    name:                  this.form.name,
                    role:                  this.form.role       || null,
                    area:                  this.form.area       || null,
                    phone:                 this.form.phone      || null,
                    email:                 this.form.email      || null,
                    hire_date:             this.form.hire_date  || null,
                    is_active:             this.form.is_available,
                    username:              this.form.username              || null,
                    password:              this.form.password              || null,
                    password_confirmation: this.form.password_confirmation || null,
                };
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':        'application/json',
                        'X-CSRF-TOKEN':  this.csrf,
                    },
                    body: JSON.stringify(payload),
                });
                const json = await res.json();
                if (res.ok) {
                    const normalized = {
                        ...json.employee,
                        is_available:          json.employee.is_active,
                        password:              '',
                        password_confirmation: '',
                    };
                    if (this.selected) {
                        const idx = this.employees.findIndex(e => e.id === this.selected.id);
                        if (idx !== -1) this.employees[idx] = normalized;
                        this.selected = normalized;
                        this.form     = { ...normalized };
                    } else {
                        this.employees.unshift(normalized);
                        this.selected = normalized;
                        this.creating = false;
                        this.form     = { ...normalized };
                    }
                    // Mostrar contraseña generada
                    if (json.employee.generated_password) {
                        this.generatedPassword = json.employee.generated_password;
                        this.generatedEmail    = json.employee.generated_username || json.employee.email;
                        this.passwordModal     = true;
                    }
                } else {
                    // Mostrar errores de validación
                    if (json.errors) {
                        const msgs = Object.values(json.errors).flat().join('\n');
                        alert(msgs);
                    } else {
                        alert(json.message || 'Error al guardar');
                    }
                }
            } catch(err) {
                alert('Error de red');
            }
            this.saving = false;
        },

        async del() {
            if (!this.selected || !confirm('¿Eliminar este empleado?')) return;
            this.deleting = true;
            try {
                const res = await fetch(this.baseUrl + '/' + this.selected.id, {
                    method: 'DELETE',
                    headers: {
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                });
                if (res.ok) {
                    this.employees = this.employees.filter(e => e.id !== this.selected.id);
                    this.selected  = null;
                    this.creating  = false;
                    this.form      = {};
                }
            } catch(err) { alert('Error de red'); }
            this.deleting = false;
        },
     }"
     x-init="
        $nextTick(() => {
            const el = document.getElementById('employee-count-label');
            if (el) el.textContent = employees.length + ' empleados';
        });
        $watch('filtered', v => {
            const el = document.getElementById('employee-count-label');
            if (el) el.textContent = v.length + ' empleados';
        });
     ">

{{-- ─── PANEL FILTROS (w-14) ──────────────────────────────────────────────────── --}}
<div class="w-14 border-r border-gray-200 bg-gray-50 hidden md:flex flex-col items-center py-3 gap-2 flex-shrink-0">

    {{-- Filtrar por estado activo/inactivo --}}
    <div class="relative group">
        <button @click="filterStatus = filterStatus==='' ? 'active' : (filterStatus==='active' ? 'inactive' : '')"
                :class="filterStatus !== '' ? 'bg-emerald-100 text-emerald-700' : 'text-gray-400 hover:text-gray-600'"
                class="w-9 h-9 flex items-center justify-center rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </button>
        <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">
            <template x-if="filterStatus===''">Estado: todos</template>
            <template x-if="filterStatus==='active'">Solo activos</template>
            <template x-if="filterStatus==='inactive'">Solo inactivos</template>
        </span>
    </div>

    {{-- Filtrar por área (dinámico, un botón por área única) --}}
    <template x-for="area in uniqueAreas" :key="area">
        <div class="relative group">
            <button @click="filterArea = filterArea===area ? '' : area"
                    :class="filterArea===area ? 'bg-amber-100 text-amber-700 font-bold' : 'text-gray-400 hover:text-gray-600'"
                    class="w-9 h-9 flex items-center justify-center rounded-lg text-xs font-black transition"
                    :title="area">
                <span x-text="area.substring(0,2).toUpperCase()"></span>
            </button>
            <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10"
                  x-text="area"></span>
        </div>
    </template>

    {{-- Separador --}}
    <div class="w-6 border-t border-gray-200 mt-1"></div>

    {{-- Reset filtros --}}
    <div class="relative group">
        <button @click="filterStatus=''; filterArea=''; search='';"
                :class="(filterStatus!=='' || filterArea!=='' || search!=='') ? 'bg-amber-100 text-amber-600' : 'text-gray-300'"
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

{{-- ─── LISTA CENTRAL (w-72) ──────────────────────────────────────────────────── --}}
<div class="w-72 border-r border-gray-200 flex-col bg-white flex-shrink-0"
     :class="panel==='list' ? 'flex w-full md:w-72' : 'hidden md:flex md:w-72'">

    {{-- Búsqueda --}}
    <div class="p-3 border-b border-gray-200 flex-shrink-0">
        <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2">
            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" x-model="search" placeholder="Buscar empleado..."
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

        {{-- Nuevo empleado --}}
        <button @click="openNew(); panel='detail'"
                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-emerald-50 transition text-left"
                :class="creating ? 'bg-emerald-50 border-l-2 border-emerald-500' : ''">
            <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-emerald-700">Nuevo empleado</p>
                <p class="text-xs text-gray-400">Agregar al equipo</p>
            </div>
        </button>

        {{-- Filas de empleados --}}
        <template x-for="e in filtered" :key="e.id">
            <button @click="select(e); panel='detail'"
                    class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition text-left"
                    :class="selected?.id===e.id ? 'bg-emerald-50 border-l-2 border-emerald-500' : ''">
                {{-- Avatar con iniciales --}}
                <div class="w-8 h-8 rounded-full bg-emerald-600 flex items-center justify-center flex-shrink-0">
                    <span class="text-xs font-bold text-white" x-text="e.name.substring(0,2).toUpperCase()"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate" x-text="e.name"></p>
                    <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                        <span x-show="e.role" class="text-xs text-gray-400 truncate" x-text="e.role"></span>
                        <span x-show="e.area"
                              class="text-[10px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-md font-medium"
                              x-text="e.area"></span>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1">
                    <span :class="e.is_available ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-400'"
                          class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md"
                          x-text="e.is_available ? 'Activo' : 'Inactivo'"></span>
                    <span x-show="e.has_user" class="text-[9px] bg-blue-100 text-blue-600 px-1.5 py-0.5 rounded-md font-semibold">Acceso</span>
                </div>
            </button>
        </template>

        {{-- Estado vacío --}}
        <div x-show="filtered.length===0" class="px-6 py-12 text-center">
            <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="text-sm text-gray-400">Sin empleados</p>
        </div>

    </div>
</div>

{{-- ─── PANEL DETALLE (flex-1) ────────────────────────────────────────────────── --}}
<div class="flex-col overflow-hidden bg-white"
     :class="panel==='detail' ? 'flex flex-1' : 'hidden md:flex md:flex-1'">

    {{-- Estado vacío --}}
    <template x-if="!selected && !creating">
        <div class="flex-1 flex flex-col items-center justify-center text-center p-10 text-gray-300">
            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="text-lg font-semibold text-gray-400">Selecciona un empleado</p>
            <p class="text-sm text-gray-300 mt-1">o crea uno nuevo</p>
        </div>
    </template>

    {{-- Formulario / Detalle --}}
    <template x-if="selected || creating">
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
                        x-text="selected ? (form.name || 'Sin nombre') : 'Nuevo empleado'"></h2>
                    <p class="text-xs text-gray-400 mt-0.5"
                       x-text="selected
                           ? ('ID #' + selected.id + (selected.area ? ' · ' + selected.area : ''))
                           : 'Completa la información del empleado'"></p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500">Activo</span>
                    <button @click="form.is_available = !form.is_available" type="button"
                            :class="form.is_available ? 'bg-emerald-500' : 'bg-gray-300'"
                            class="relative w-10 h-5 rounded-full transition-colors duration-200">
                        <span :class="form.is_available ? 'translate-x-5' : 'translate-x-0.5'"
                              class="absolute top-0.5 left-0 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200 block"></span>
                    </button>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="flex border-b border-gray-200 px-6 flex-shrink-0 bg-white">
                <button @click="tab='info'"
                        :class="tab==='info' ? 'border-b-2 border-emerald-600 text-emerald-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-3 text-sm transition whitespace-nowrap">
                    Información
                </button>
                <button @click="tab='access'"
                        :class="tab==='access' ? 'border-b-2 border-emerald-600 text-emerald-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-3 text-sm transition whitespace-nowrap">
                    Acceso
                </button>
                <button @click="tab='history'"
                        :class="tab==='history' ? 'border-b-2 border-emerald-600 text-emerald-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-3 text-sm transition whitespace-nowrap">
                    Historial
                </button>
            </div>

            {{-- Tab Content --}}
            <div class="flex-1 overflow-y-auto p-6">

                {{-- ═══ TAB: INFORMACIÓN ═══ --}}
                <div x-show="tab==='info'" class="space-y-4 max-w-2xl">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Nombre --}}
                        <div class="sm:col-span-2">
                            <label class="label">Nombre completo *</label>
                            <input type="text" x-model="form.name"
                                   placeholder="Ej: María García López"
                                   class="input" required>
                        </div>

                        {{-- Cargo / Rol --}}
                        <div>
                            <label class="label flex items-center justify-between">
                                Cargo / Rol
                                <a href="{{ $catalogsUrl }}" class="text-indigo-500 text-[10px] hover:underline font-normal">+ gestionar</a>
                            </label>
                            <template x-if="jobTitles.length > 0">
                                <select x-model="form.role" class="input">
                                    <option value="">Sin cargo</option>
                                    <template x-for="t in jobTitles" :key="t">
                                        <option :value="t" x-text="t"></option>
                                    </template>
                                </select>
                            </template>
                            <template x-if="jobTitles.length === 0">
                                <input type="text" x-model="form.role" placeholder="Ej: Vendedor, Contador" class="input">
                            </template>
                        </div>

                        {{-- Área --}}
                        <div>
                            <label class="label flex items-center justify-between">
                                Área / Departamento
                                <a href="{{ $catalogsUrl }}" class="text-indigo-500 text-[10px] hover:underline font-normal">+ gestionar</a>
                            </label>
                            <template x-if="departments.length > 0">
                                <select x-model="form.area" class="input">
                                    <option value="">Sin área</option>
                                    <template x-for="d in departments" :key="d">
                                        <option :value="d" x-text="d"></option>
                                    </template>
                                </select>
                            </template>
                            <template x-if="departments.length === 0">
                                <input type="text" x-model="form.area" placeholder="Ej: Tecnología, Ventas" class="input">
                            </template>
                        </div>

                        {{-- Teléfono --}}
                        <div>
                            <label class="label">Teléfono / Celular</label>
                            <input type="text" x-model="form.phone"
                                   placeholder="Ej: +51 999 888 777"
                                   class="input">
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="label">Correo electrónico</label>
                            <input type="email" x-model="form.email"
                                   placeholder="empleado@empresa.com"
                                   class="input">
                        </div>

                        {{-- Fecha de ingreso --}}
                        <div>
                            <label class="label">Fecha de ingreso</label>
                            <input type="date" x-model="form.hire_date"
                                   class="input">
                        </div>


                        {{-- Estado (referencia visual) --}}
                        <div class="flex items-end">
                            <div :class="form.is_available
                                    ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
                                    : 'bg-gray-50 border-gray-200 text-gray-500'"
                                 class="flex items-center gap-2 border rounded-xl px-4 py-2.5 text-sm w-full">
                                <div :class="form.is_available ? 'bg-emerald-500' : 'bg-gray-400'"
                                     class="w-2 h-2 rounded-full flex-shrink-0"></div>
                                <span x-text="form.is_available ? 'Empleado activo' : 'Empleado inactivo'"></span>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- ═══ TAB: ACCESO ═══ --}}
                <div x-show="tab==='access'" class="space-y-4 max-w-lg">

                    {{-- Usuario --}}
                    <div>
                        <label class="label">Usuario</label>
                        <input type="text" x-model="form.username"
                               placeholder="Ej: maria.garcia"
                               class="input">
                        <p class="text-xs text-gray-400 mt-1">Si lo dejas vacío al crear, se genera automáticamente del nombre.</p>
                    </div>

                    {{-- Correo --}}
                    <div>
                        <label class="label">Correo electrónico</label>
                        <input type="email" x-model="form.email"
                               placeholder="empleado@empresa.com"
                               class="input">
                    </div>

                    {{-- Contraseña --}}
                    <div>
                        <label class="label">
                            Contraseña
                            <span x-show="selected" class="text-gray-400 font-normal text-xs ml-1">(dejar vacío para no cambiar)</span>
                            <span x-show="creating" class="text-gray-400 font-normal text-xs ml-1">(dejar vacío para generar automático)</span>
                        </label>
                        <input type="password" x-model="form.password"
                               placeholder="••••••••"
                               class="input">
                    </div>

                    {{-- Confirmar contraseña --}}
                    <div>
                        <label class="label">Confirmar contraseña</label>
                        <input type="password" x-model="form.password_confirmation"
                               placeholder="••••••••"
                               class="input">
                    </div>

                    {{-- Estado acceso --}}
                    <div x-show="selected && selected.has_user"
                         class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 flex-shrink-0"></div>
                        <p class="text-xs text-emerald-700 font-medium">Este empleado tiene acceso al sistema</p>
                    </div>

                    <div x-show="!selected || !selected.has_user"
                         class="bg-yellow-50 border border-yellow-200 rounded-xl p-3">
                        <p class="text-xs text-yellow-800">Sin acceso aún. Agrega un correo y guarda para crear el usuario.</p>
                    </div>

                </div>

                {{-- ═══ TAB: HISTORIAL ═══ --}}
                <div x-show="tab==='history'" class="space-y-4 max-w-lg">

                    <div class="border-2 border-dashed border-gray-200 rounded-2xl p-10 text-center">
                        <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-gray-400">Próximamente: historial de actividad del empleado</p>
                        <p class="text-xs text-gray-300 mt-1">Registro de cambios, accesos y eventos asociados al empleado</p>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
                        <p class="font-semibold mb-1">Próximamente</p>
                        <p class="text-xs text-blue-600">Podrás ver el historial completo de actividad: cambios de rol, fechas de modificación, eventos de acceso y más.</p>
                    </div>

                </div>

            </div>

            {{-- FOOTER --}}
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between flex-shrink-0 bg-white">
                <div>
                    <button x-show="selected" @click="del()" :disabled="deleting"
                            class="flex items-center gap-1.5 px-4 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span x-text="deleting ? 'Eliminando...' : 'Eliminar'"></span>
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="selected=null; creating=false; form={}; panel='list';" type="button"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button @click="save()" :disabled="saving || !form.name"
                            class="flex items-center gap-2 px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                        <svg x-show="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="saving ? 'Guardando...' : (selected ? 'Guardar cambios' : 'Crear empleado')"></span>
                    </button>
                </div>
            </div>

        </div>
    </template>

</div>

</div>
</div>

{{-- ─── MODAL: Contraseña generada ─────────────────────────────────────────── --}}
<template x-teleport="body">
<div x-show="passwordModal"
     x-transition.opacity
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
     @keydown.escape.window="passwordModal=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" @click.stop>

        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 text-base">Acceso creado</h3>
                <p class="text-xs text-gray-500">Comparte estas credenciales con el empleado</p>
            </div>
        </div>

        <div class="bg-gray-50 rounded-xl p-4 space-y-3 mb-5">
            <div>
                <p class="text-xs text-gray-400 mb-1 font-medium uppercase tracking-wide">Usuario</p>
                <p class="text-sm font-medium text-gray-800 font-mono" x-text="generatedEmail"></p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1 font-medium uppercase tracking-wide">Contraseña temporal</p>
                <div class="flex items-center gap-2">
                    <p class="text-lg font-bold text-emerald-700 font-mono tracking-widest" x-text="generatedPassword"></p>
                    <button @click="navigator.clipboard.writeText(generatedPassword); $el.textContent='✓'"
                            class="text-xs bg-emerald-100 text-emerald-700 px-2 py-1 rounded-lg hover:bg-emerald-200 transition">
                        Copiar
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-3 mb-5">
            <p class="text-xs text-yellow-800">
                <strong>Importante:</strong> Esta contraseña solo se muestra una vez. El empleado puede cambiarla desde su perfil.
            </p>
        </div>

        <button @click="passwordModal=false"
                class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl transition">
            Entendido
        </button>
    </div>
</div>
</template>

</x-slot>
</x-app-layout>
