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
        <h1 class="text-lg font-semibold text-gray-800">Proveedores</h1>
        <p class="text-xs text-gray-400 mt-0.5" id="prov-count-label">Cargando...</p>
    </div>
    <div class="flex items-center gap-2">
        <button @click="openNew()"
                class="flex items-center gap-1.5 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo proveedor
        </button>
    </div>
</div>

{{-- BODY --}}
<div class="flex flex-1 overflow-hidden"
     x-data="{
        proveedores: {{ Illuminate\Support\Js::from($proveedores->map(fn($p) => [
            'id'           => $p->id,
            'name'         => $p->name,
            'contact_name' => $p->contact_name ?? '',
            'phone'        => $p->phone ?? '',
            'email'        => $p->email ?? '',
            'address'      => $p->address ?? '',
            'category'     => $p->category ?? '',
            'notes'        => $p->notes ?? '',
            'is_active'    => $p->is_active,
        ])) }},
        search: '',
        filterStatus: '',
        filterCategory: '',
        panel: 'list',

        selected: null,
        creating: false,
        tab: 'info',
        saving: false,
        deleting: false,
        form: {},
        csrf: '{{ $csrf }}',
        storeUrl: '{{ route('proveedores.store', ['project' => $pid]) }}',
        baseUrl: '{{ url('/'.$pid.'/company/proveedores') }}',

        get filtered() {
            return this.proveedores.filter(p => {
                const q = this.search.toLowerCase();
                const matchQ = !q
                    || p.name.toLowerCase().includes(q)
                    || (p.contact_name || '').toLowerCase().includes(q)
                    || (p.category || '').toLowerCase().includes(q);
                const matchS = this.filterStatus === ''
                    ? true
                    : (this.filterStatus === 'active' ? p.is_active : !p.is_active);
                const matchC = !this.filterCategory || p.category === this.filterCategory;
                return matchQ && matchS && matchC;
            });
        },

        get uniqueCategories() {
            const cats = this.proveedores.map(p => p.category).filter(c => c && c.trim() !== '');
            return [...new Set(cats)].sort();
        },

        openNew() {
            this.selected  = null;
            this.creating  = true;
            this.tab       = 'info';
            this.form = {
                name: '', contact_name: '', phone: '', email: '',
                address: '', category: '', notes: '', is_active: true
            };
        },

        select(p) {
            this.selected  = p;
            this.creating  = false;
            this.tab       = 'info';
            this.form      = { ...p };
        },

        async save() {
            this.saving = true;
            const url    = this.selected
                ? this.baseUrl + '/' + this.selected.id
                : this.storeUrl;
            const method = this.selected ? 'PUT' : 'POST';
            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':        'application/json',
                        'X-CSRF-TOKEN':  this.csrf,
                    },
                    body: JSON.stringify({
                        name:         this.form.name,
                        contact_name: this.form.contact_name || null,
                        phone:        this.form.phone        || null,
                        email:        this.form.email        || null,
                        address:      this.form.address      || null,
                        category:     this.form.category     || null,
                        notes:        this.form.notes        || null,
                        is_active:    this.form.is_active,
                    }),
                });
                const json = await res.json();
                if (res.ok) {
                    const prov = json.proveedor;
                    if (this.selected) {
                        const idx = this.proveedores.findIndex(x => x.id === this.selected.id);
                        if (idx !== -1) this.proveedores[idx] = prov;
                        this.selected = prov;
                        this.form     = { ...prov };
                    } else {
                        this.proveedores.unshift(prov);
                        this.selected = prov;
                        this.creating = false;
                        this.form     = { ...prov };
                    }
                } else {
                    alert(json.message || 'Error al guardar');
                }
            } catch(err) {
                alert('Error de red');
            }
            this.saving = false;
        },

        async del() {
            if (!this.selected || !confirm('¿Eliminar este proveedor?')) return;
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
                    this.proveedores = this.proveedores.filter(p => p.id !== this.selected.id);
                    this.selected    = null;
                    this.creating    = false;
                    this.form        = {};
                }
            } catch(err) { alert('Error de red'); }
            this.deleting = false;
        },
     }"
     x-init="
        $nextTick(() => {
            const el = document.getElementById('prov-count-label');
            if (el) el.textContent = proveedores.length + ' proveedores';
        });
        $watch('filtered', v => {
            const el = document.getElementById('prov-count-label');
            if (el) el.textContent = v.length + ' proveedores';
        });
     ">

{{-- ─── PANEL FILTROS (w-14) ──────────────────────────────────────────────────── --}}
<div class="w-14 border-r border-gray-200 bg-gray-50 hidden md:flex flex-col items-center py-3 gap-2 flex-shrink-0">

    {{-- Filtrar por estado --}}
    <div class="relative group">
        <button @click="filterStatus = filterStatus==='' ? 'active' : (filterStatus==='active' ? 'inactive' : '')"
                :class="filterStatus !== '' ? 'bg-teal-100 text-teal-700' : 'text-gray-400 hover:text-gray-600'"
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

    {{-- Filtrar por categoría --}}
    <template x-for="cat in uniqueCategories" :key="cat">
        <div class="relative group">
            <button @click="filterCategory = filterCategory===cat ? '' : cat"
                    :class="filterCategory===cat ? 'bg-amber-100 text-amber-700 font-bold' : 'text-gray-400 hover:text-gray-600'"
                    class="w-9 h-9 flex items-center justify-center rounded-lg text-xs font-black transition"
                    :title="cat">
                <span x-text="cat.substring(0,2).toUpperCase()"></span>
            </button>
            <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10"
                  x-text="cat"></span>
        </div>
    </template>

    {{-- Separador --}}
    <div class="w-6 border-t border-gray-200 mt-1"></div>

    {{-- Reset filtros --}}
    <div class="relative group">
        <button @click="filterStatus=''; filterCategory=''; search='';"
                :class="(filterStatus!=='' || filterCategory!=='' || search!=='') ? 'bg-amber-100 text-amber-600' : 'text-gray-300'"
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
            <input type="text" x-model="search" placeholder="Buscar proveedor..."
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

        {{-- Nuevo proveedor --}}
        <button @click="openNew()"
                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-teal-50 transition text-left"
                :class="creating ? 'bg-teal-50 border-l-2 border-teal-500' : ''">
            <div class="w-8 h-8 rounded-lg bg-teal-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-teal-700">Nuevo proveedor</p>
                <p class="text-xs text-gray-400">Agregar proveedor</p>
            </div>
        </button>

        {{-- Filas --}}
        <template x-for="p in filtered" :key="p.id">
            <button @click="select(p)"
                    class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition text-left"
                    :class="selected?.id===p.id ? 'bg-teal-50 border-l-2 border-teal-500' : ''">
                <div class="w-8 h-8 rounded-full bg-teal-600 flex items-center justify-center flex-shrink-0">
                    <span class="text-xs font-bold text-white" x-text="p.name.substring(0,2).toUpperCase()"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate" x-text="p.name"></p>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span x-show="p.contact_name" class="text-xs text-gray-400 truncate" x-text="p.contact_name"></span>
                        <span x-show="p.category"
                              class="text-[10px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-md font-medium"
                              x-text="p.category"></span>
                    </div>
                </div>
                <span :class="p.is_active ? 'bg-teal-100 text-teal-700' : 'bg-gray-100 text-gray-400'"
                      class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md flex-shrink-0"
                      x-text="p.is_active ? 'Activo' : 'Inactivo'"></span>
            </button>
        </template>

        {{-- Estado vacío --}}
        <div x-show="filtered.length===0" class="px-6 py-12 text-center">
            <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <p class="text-sm text-gray-400">Sin proveedores</p>
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
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <p class="text-lg font-semibold text-gray-400">Selecciona un proveedor</p>
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
                        x-text="selected ? (form.name || 'Sin nombre') : 'Nuevo proveedor'"></h2>
                    <p class="text-xs text-gray-400 mt-0.5"
                       x-text="selected
                           ? ('ID #' + selected.id + (selected.category ? ' · ' + selected.category : ''))
                           : 'Completa la información del proveedor'"></p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500">Activo</span>
                    <button @click="form.is_active = !form.is_active" type="button"
                            :class="form.is_active ? 'bg-teal-500' : 'bg-gray-300'"
                            class="relative w-10 h-5 rounded-full transition-colors duration-200">
                        <span :class="form.is_active ? 'translate-x-5' : 'translate-x-0.5'"
                              class="absolute top-0.5 left-0 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200 block"></span>
                    </button>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="flex border-b border-gray-200 px-6 flex-shrink-0 bg-white">
                <button @click="tab='info'"
                        :class="tab==='info' ? 'border-b-2 border-teal-600 text-teal-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-3 text-sm transition whitespace-nowrap">
                    Información
                </button>
                <button @click="tab='contacto'"
                        :class="tab==='contacto' ? 'border-b-2 border-teal-600 text-teal-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-3 text-sm transition whitespace-nowrap">
                    Contacto
                </button>
                <button @click="tab='notas'"
                        :class="tab==='notas' ? 'border-b-2 border-teal-600 text-teal-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-3 text-sm transition whitespace-nowrap">
                    Notas
                </button>
            </div>

            {{-- Tab Content --}}
            <div class="flex-1 overflow-y-auto p-6">

                {{-- ═══ TAB: INFORMACIÓN ═══ --}}
                <div x-show="tab==='info'" class="space-y-4 max-w-2xl">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="sm:col-span-2">
                            <label class="label">Nombre del proveedor *</label>
                            <input type="text" x-model="form.name"
                                   placeholder="Ej: Distribuidora ABC S.A.C."
                                   class="input" required>
                        </div>

                        <div>
                            <label class="label">Categoría / Rubro</label>
                            <input type="text" x-model="form.category"
                                   placeholder="Ej: Materiales, Servicios, Tecnología"
                                   class="input">
                        </div>

                        <div>
                            <label class="label">Persona de contacto</label>
                            <input type="text" x-model="form.contact_name"
                                   placeholder="Ej: Carlos López"
                                   class="input">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="label">Dirección</label>
                            <input type="text" x-model="form.address"
                                   placeholder="Ej: Av. Industrial 123, Lima"
                                   class="input">
                        </div>

                        {{-- Estado visual --}}
                        <div class="flex items-end">
                            <div :class="form.is_active
                                    ? 'bg-teal-50 border-teal-200 text-teal-700'
                                    : 'bg-gray-50 border-gray-200 text-gray-500'"
                                 class="flex items-center gap-2 border rounded-xl px-4 py-2.5 text-sm w-full">
                                <div :class="form.is_active ? 'bg-teal-500' : 'bg-gray-400'"
                                     class="w-2 h-2 rounded-full flex-shrink-0"></div>
                                <span x-text="form.is_active ? 'Proveedor activo' : 'Proveedor inactivo'"></span>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- ═══ TAB: CONTACTO ═══ --}}
                <div x-show="tab==='contacto'" class="space-y-4 max-w-lg">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div>
                            <label class="label">Teléfono</label>
                            <input type="text" x-model="form.phone"
                                   placeholder="Ej: +51 999 888 777"
                                   class="input">
                        </div>

                        <div>
                            <label class="label">Correo electrónico</label>
                            <input type="email" x-model="form.email"
                                   placeholder="proveedor@empresa.com"
                                   class="input">
                        </div>

                    </div>

                    <template x-if="form.phone || form.email">
                        <div class="bg-teal-50 border border-teal-100 rounded-xl p-4 space-y-2">
                            <p class="text-xs font-semibold text-teal-700 uppercase tracking-wide">Accesos rápidos</p>
                            <a x-show="form.phone" :href="'tel:' + form.phone"
                               class="flex items-center gap-2 text-sm text-teal-700 hover:text-teal-900">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <span x-text="form.phone"></span>
                            </a>
                            <a x-show="form.email" :href="'mailto:' + form.email"
                               class="flex items-center gap-2 text-sm text-teal-700 hover:text-teal-900">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span x-text="form.email"></span>
                            </a>
                        </div>
                    </template>

                </div>

                {{-- ═══ TAB: NOTAS ═══ --}}
                <div x-show="tab==='notas'" class="space-y-4 max-w-2xl">
                    <div>
                        <label class="label">Notas internas</label>
                        <textarea x-model="form.notes" class="input resize-none" rows="8"
                                  placeholder="Condiciones de pago, plazos de entrega, observaciones..."></textarea>
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
                            class="flex items-center gap-2 px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                        <svg x-show="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="saving ? 'Guardando...' : (selected ? 'Guardar cambios' : 'Crear proveedor')"></span>
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
