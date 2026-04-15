@php
    $clientsApiBase = ($portalLayout ?? 'panel') === 'comercial'
        ? route('bixosales.clientes')
        : route('clients');
@endphp
<x-portal-layout :layout="$portalLayout ?? 'panel'" :project="$project" pageTitle="Clientes">

@php
    $csrf = csrf_token();
    $pid  = $project->id;
@endphp

<div class="flex flex-col h-full w-full overflow-hidden">

{{-- TOP BAR --}}
<div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 bg-white flex-shrink-0">
    <div>
        <h1 class="text-lg font-semibold text-gray-800">Clientes</h1>
        <p class="text-xs text-gray-400 mt-0.5" id="client-count-label">Cargando...</p>
    </div>
    <div class="flex items-center gap-2">
        <button @click="openNew()"
                class="flex items-center gap-1.5 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo cliente
        </button>
    </div>
</div>

{{-- BODY --}}
<div class="flex flex-1 overflow-hidden"
     x-data="{
        clients: {{ Illuminate\Support\Js::from($clients->map(fn($c) => [
            'id'                 => $c->id,
            'name'               => $c->name,
            'phone'              => $c->phone ?? '',
            'email'              => $c->email ?? '',
            'notes'              => $c->notes ?? '',
            'client_type'        => $c->client_type ?? '',
            'lead_source'        => $c->lead_source ?? '',
            'orders_count'       => $c->orders_count ?? 0,
            'appointments_count' => $c->appointments_count ?? 0,
        ])) }},
        clientTypes: {{ Illuminate\Support\Js::from($clientTypes) }},
        leadSources:  {{ Illuminate\Support\Js::from($leadSources) }},
        search: '',
        filterStatus: '',
        panel: 'list',

        selected: null,
        creating: false,
        tab: 'info',
        saving: false,
        deleting: false,
        form: {},
        csrf: '{{ $csrf }}',
        storeUrl: '{{ $clientsApiBase }}',
        baseUrl: '{{ $clientsApiBase }}',

        get filtered() {
            return this.clients.filter(c => {
                const q = this.search.toLowerCase();
                const matchQ = !q
                    || c.name.toLowerCase().includes(q)
                    || (c.phone || '').includes(q)
                    || (c.email || '').toLowerCase().includes(q);
                return matchQ;
            });
        },

        openNew() {
            this.selected  = null;
            this.creating  = true;
            this.tab       = 'info';
            this.form = { name: '', phone: '', email: '', notes: '' };
        },

        select(c) {
            this.selected  = c;
            this.creating  = false;
            this.tab       = 'info';
            this.form      = { ...c };
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
                        name:        this.form.name,
                        phone:       this.form.phone || null,
                        email:       this.form.email || null,
                        notes:       this.form.notes || null,
                        client_type: this.form.client_type || null,
                        lead_source: this.form.lead_source || null,
                    }),
                });
                const json = await res.json();
                if (res.ok) {
                    const c = json.client;
                    if (this.selected) {
                        const idx = this.clients.findIndex(x => x.id === this.selected.id);
                        if (idx !== -1) this.clients[idx] = { ...this.clients[idx], ...c };
                        this.selected = { ...this.clients[idx] ?? c };
                        this.form     = { ...this.selected };
                    } else {
                        this.clients.unshift(c);
                        this.selected = c;
                        this.creating = false;
                        this.form     = { ...c };
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
            if (!this.selected || !confirm('¿Eliminar este cliente?')) return;
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
                    this.clients  = this.clients.filter(c => c.id !== this.selected.id);
                    this.selected = null;
                    this.creating = false;
                    this.form     = {};
                }
            } catch(err) { alert('Error de red'); }
            this.deleting = false;
        },
     }"
     x-init="
        $nextTick(() => {
            const el = document.getElementById('client-count-label');
            if (el) el.textContent = clients.length + ' clientes';
        });
        $watch('filtered', v => {
            const el = document.getElementById('client-count-label');
            if (el) el.textContent = v.length + ' clientes';
        });
     ">

{{-- ─── PANEL FILTROS (w-14) ──────────────────────────────────────────────────── --}}
<div class="w-14 border-r border-gray-200 bg-gray-50 hidden md:flex flex-col items-center py-3 gap-2 flex-shrink-0">

    {{-- Todos --}}
    <div class="relative group">
        <button @click="filterStatus = filterStatus==='' ? 'with_orders' : ''"
                :class="filterStatus !== '' ? 'bg-sky-100 text-sky-700' : 'text-gray-400 hover:text-gray-600'"
                class="w-9 h-9 flex items-center justify-center rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </button>
        <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">
            <template x-if="filterStatus===''">Todos los clientes</template>
            <template x-if="filterStatus==='with_orders'">Con pedidos</template>
        </span>
    </div>

    {{-- Separador --}}
    <div class="w-6 border-t border-gray-200 mt-1"></div>

    {{-- Reset filtros --}}
    <div class="relative group">
        <button @click="filterStatus=''; search='';"
                :class="(filterStatus!=='' || search!=='') ? 'bg-amber-100 text-amber-600' : 'text-gray-300'"
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
            <input type="text" x-model="search" placeholder="Buscar cliente..."
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

        {{-- Nuevo cliente --}}
        <button @click="openNew(); panel='detail'"
                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-sky-50 transition text-left"
                :class="creating ? 'bg-sky-50 border-l-2 border-sky-500' : ''">
            <div class="w-8 h-8 rounded-lg bg-sky-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-sky-700">Nuevo cliente</p>
                <p class="text-xs text-gray-400">Registrar cliente</p>
            </div>
        </button>

        {{-- Filas de clientes --}}
        <template x-for="c in filtered" :key="c.id">
            <button @click="select(c); panel='detail'"
                    class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition text-left"
                    :class="selected?.id===c.id ? 'bg-sky-50 border-l-2 border-sky-500' : ''">
                {{-- Avatar con iniciales --}}
                <div class="w-8 h-8 rounded-full bg-sky-600 flex items-center justify-center flex-shrink-0">
                    <span class="text-xs font-bold text-white" x-text="c.name.substring(0,2).toUpperCase()"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate" x-text="c.name"></p>
                    <p class="text-xs text-gray-400 truncate" x-text="c.phone || c.email || 'Sin contacto'"></p>
                </div>
                <span x-show="c.orders_count > 0"
                      class="text-[10px] bg-sky-100 text-sky-700 font-semibold px-1.5 py-0.5 rounded-md flex-shrink-0"
                      x-text="c.orders_count + ' ped.'"></span>
            </button>
        </template>

        {{-- Estado vacío --}}
        <div x-show="filtered.length===0" class="px-6 py-12 text-center">
            <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-sm text-gray-400">Sin clientes</p>
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
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-lg font-semibold text-gray-400">Selecciona un cliente</p>
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
                        x-text="selected ? (form.name || 'Sin nombre') : 'Nuevo cliente'"></h2>
                    <p class="text-xs text-gray-400 mt-0.5"
                       x-text="selected
                           ? ('ID #' + selected.id + (selected.orders_count > 0 ? ' · ' + selected.orders_count + ' pedidos' : ''))
                           : 'Completa la información del cliente'"></p>
                </div>
                <div x-show="selected" class="flex items-center gap-3">
                    <div class="text-center">
                        <p class="text-xl font-bold text-sky-600" x-text="selected?.orders_count ?? 0"></p>
                        <p class="text-[10px] text-gray-400">pedidos</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-bold text-indigo-600" x-text="selected?.appointments_count ?? 0"></p>
                        <p class="text-[10px] text-gray-400">citas</p>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="flex border-b border-gray-200 px-6 flex-shrink-0 bg-white">
                <button @click="tab='info'"
                        :class="tab==='info' ? 'border-b-2 border-sky-600 text-sky-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-3 text-sm transition whitespace-nowrap">
                    Información
                </button>
                <button @click="tab='history'" x-show="selected"
                        :class="tab==='history' ? 'border-b-2 border-sky-600 text-sky-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-3 text-sm transition whitespace-nowrap">
                    Historial
                </button>
                <button @click="tab='notes'" x-show="selected"
                        :class="tab==='notes' ? 'border-b-2 border-sky-600 text-sky-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-3 text-sm transition whitespace-nowrap">
                    Notas
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
                                   placeholder="Ej: Juan Pérez García"
                                   class="input" required>
                        </div>

                        {{-- Teléfono --}}
                        <div>
                            <label class="label">Teléfono</label>
                            <input type="text" x-model="form.phone"
                                   placeholder="Ej: +51 999 888 777"
                                   class="input">
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="label">Correo electrónico</label>
                            <input type="email" x-model="form.email"
                                   placeholder="cliente@correo.com"
                                   class="input">
                        </div>

                        {{-- Tipo de cliente --}}
                        <div x-show="clientTypes.length > 0">
                            <label class="label">Tipo de cliente</label>
                            <select x-model="form.client_type" class="input">
                                <option value="">Sin clasificar</option>
                                <template x-for="t in clientTypes" :key="t">
                                    <option :value="t" x-text="t"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Origen del lead --}}
                        <div x-show="leadSources.length > 0">
                            <label class="label">¿Cómo nos conoció?</label>
                            <select x-model="form.lead_source" class="input">
                                <option value="">Sin registrar</option>
                                <template x-for="s in leadSources" :key="s">
                                    <option :value="s" x-text="s"></option>
                                </template>
                            </select>
                        </div>

                    </div>
                </div>

                {{-- ═══ TAB: HISTORIAL ═══ --}}
                <div x-show="tab==='history'" class="space-y-4 max-w-lg">

                    <template x-if="selected">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-sky-50 border border-sky-100 rounded-xl p-5 text-center">
                                <p class="text-3xl font-bold text-sky-600" x-text="selected.orders_count ?? 0"></p>
                                <p class="text-sm text-sky-500 mt-1">Pedidos realizados</p>
                            </div>
                            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-5 text-center">
                                <p class="text-3xl font-bold text-indigo-600" x-text="selected.appointments_count ?? 0"></p>
                                <p class="text-sm text-indigo-500 mt-1">Citas agendadas</p>
                            </div>
                        </div>
                    </template>

                    <div class="border-2 border-dashed border-gray-200 rounded-2xl p-10 text-center">
                        <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-gray-400">Próximamente: historial completo del cliente</p>
                        <p class="text-xs text-gray-300 mt-1">Pedidos, citas, cotizaciones y actividad</p>
                    </div>

                </div>

                {{-- ═══ TAB: NOTAS ═══ --}}
                <div x-show="tab==='notes'" class="space-y-4 max-w-2xl">
                    <div>
                        <label class="label">Notas internas</label>
                        <textarea x-model="form.notes" class="input resize-none" rows="8"
                                  placeholder="Observaciones, preferencias, información importante del cliente..."></textarea>
                        <p class="text-xs text-gray-400 mt-1">Solo visible para el equipo interno.</p>
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
                            class="flex items-center gap-2 px-5 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                        <svg x-show="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="saving ? 'Guardando...' : (selected ? 'Guardar cambios' : 'Crear cliente')"></span>
                    </button>
                </div>
            </div>

        </div>
    </template>

</div>

</div>
</div>

</x-portal-layout>
