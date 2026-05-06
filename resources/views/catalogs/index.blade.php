<x-app-layout>
<x-slot name="slot">

@php
    $csrf   = csrf_token();
    $pid    = $project->id;
    $base   = url("/bixoadmin/catalogs");

    $groups = $catalogs->groupBy('type')->map->count();
@endphp

<div class="flex flex-col h-full w-full overflow-hidden"
     x-data="{
         catalogs: {{ Illuminate\Support\Js::from($catalogs->map(fn($c) => [
             'id'          => $c->id,
             'name'        => $c->name,
             'type'        => $c->type ?? '',
             'description' => $c->description ?? '',
             'color'       => $c->color ?? '#6366f1',
             'is_active'   => $c->is_active,
             'is_system'   => $c->is_system,
             'sort_order'  => $c->sort_order ?? 0,
         ])) }},
         values: [],
         panel: 'list',
         filterGroup: '',
         search: '',
         selected: null,
         tab: 'info',
         form: {},
         valueForm: { label: '', code: '', is_active: true },
         editingValue: null,
         editValueForm: {},
         addingValue: false,
         saving: false,
         isNew: false,
         baseUrl: '{{ $base }}',

         get filtered() {
             return this.catalogs.filter(c => {
                 const matchSearch = !this.search || c.name.toLowerCase().includes(this.search.toLowerCase());
                 const matchGroup  = !this.filterGroup || c.type === this.filterGroup;
                 return matchSearch && matchGroup;
             });
         },

         get typeGroups() {
             const types = {};
             this.catalogs.forEach(c => {
                 if (c.type) types[c.type] = (types[c.type] || 0) + 1;
             });
             return types;
         },

         async select(c) {
             if (window.innerWidth < 768) this.panel = 'detail';
             this.selected = c;
             this.form = { name: c.name, type: c.type, description: c.description, color: c.color, is_active: c.is_active };
             this.tab = 'info';
             this.isNew = false;
             this.addingValue = false;
             this.editingValue = null;
             await this.loadValues(c.id);
         },

         openNew() {
             if (window.innerWidth < 768) this.panel = 'detail';
             this.selected = null;
             this.form = { name: '', type: '', description: '', color: '#6366f1', is_active: true };
             this.values = [];
             this.tab = 'info';
             this.isNew = true;
             this.addingValue = false;
             this.editingValue = null;
         },

         async loadValues(id) {
             const res = await fetch(this.baseUrl + '/' + id + '/values', {
                 headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ $csrf }}' }
             });
             if (res.ok) this.values = await res.json();
         },

         async save() {
             this.saving = true;
             const url    = this.isNew ? this.baseUrl : this.baseUrl + '/' + this.selected.id;
             const method = this.isNew ? 'POST' : 'PUT';
             const res = await fetch(url, {
                 method,
                 headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ $csrf }}', 'Accept': 'application/json' },
                 body: JSON.stringify(this.form)
             });
             if (res.ok) {
                 const data = await res.json();
                 if (this.isNew) {
                     this.catalogs.push(data.catalog);
                     await this.select(data.catalog);
                 } else {
                     Object.assign(this.selected, data.catalog);
                 }
             }
             this.saving = false;
         },

         async del() {
             if (!confirm('¿Eliminar este catálogo y todos sus valores?')) return;
             const res = await fetch(this.baseUrl + '/' + this.selected.id, {
                 method: 'DELETE',
                 headers: { 'X-CSRF-TOKEN': '{{ $csrf }}', 'Accept': 'application/json' }
             });
             if (res.ok) {
                 this.catalogs = this.catalogs.filter(c => c.id !== this.selected.id);
                 this.selected = null;
                 this.isNew = false;
                 if (window.innerWidth < 768) this.panel = 'list';
             }
         },

         async addValue() {
             if (!this.valueForm.label) return;
             const res = await fetch(this.baseUrl + '/' + this.selected.id + '/values', {
                 method: 'POST',
                 headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ $csrf }}', 'Accept': 'application/json' },
                 body: JSON.stringify(this.valueForm)
             });
             if (res.ok) {
                 const data = await res.json();
                 this.values.push(data.value);
                 this.valueForm = { label: '', code: '', is_active: true };
                 this.addingValue = false;
             }
         },

         startEditValue(v) {
             this.editingValue = v.id;
             this.editValueForm = { label: v.label, code: v.code, is_active: v.is_active };
         },

         async saveEditValue(v) {
             const res = await fetch(this.baseUrl + '/' + this.selected.id + '/values/' + v.id, {
                 method: 'PUT',
                 headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ $csrf }}', 'Accept': 'application/json' },
                 body: JSON.stringify(this.editValueForm)
             });
             if (res.ok) {
                 Object.assign(v, this.editValueForm);
                 this.editingValue = null;
             }
         },

         async toggleValue(v) {
             const res = await fetch(this.baseUrl + '/' + this.selected.id + '/values/' + v.id, {
                 method: 'PUT',
                 headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ $csrf }}', 'Accept': 'application/json' },
                 body: JSON.stringify({ label: v.label, code: v.code, is_active: !v.is_active })
             });
             if (res.ok) v.is_active = !v.is_active;
         },

         async deleteValue(v) {
             if (!confirm('¿Eliminar este valor?')) return;
             const res = await fetch(this.baseUrl + '/' + this.selected.id + '/values/' + v.id, {
                 method: 'DELETE',
                 headers: { 'X-CSRF-TOKEN': '{{ $csrf }}', 'Accept': 'application/json' }
             });
             if (res.ok) this.values = this.values.filter(x => x.id !== v.id);
         }
     }"
     x-init="if(catalogs.length) select(catalogs[0])">

{{-- ══ TOP BAR ══ --}}
<div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 bg-white flex-shrink-0">
    <div>
        <h1 class="text-lg font-semibold text-gray-800">Catálogos</h1>
        <p class="text-xs text-gray-400 mt-0.5">
            <span x-text="catalogs.length"></span> catálogos ·
            <span x-text="catalogs.reduce((s,c)=>s+(c.is_active?1:0),0)"></span> activos
        </p>
    </div>
    <div class="flex items-center gap-2">
        <button @click="openNew()"
                class="flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo catálogo
        </button>
    </div>
</div>

{{-- ══ BODY (3 paneles) ══ --}}
<div class="flex flex-1 overflow-hidden">

    {{-- ── Sidebar filtros (hidden mobile) ── --}}
    <div class="w-14 border-r border-gray-200 bg-gray-50 hidden md:flex flex-col items-center py-3 gap-1 flex-shrink-0">
        {{-- Todos --}}
        <button @click="filterGroup=''"
                :class="filterGroup==='' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-400 hover:bg-gray-100'"
                class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors"
                title="Todos">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
        </button>
        {{-- Comercial --}}
        <button @click="filterGroup='product_category'"
                :class="filterGroup==='product_category' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-400 hover:bg-gray-100'"
                class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors"
                title="Comercial">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
        </button>
        {{-- RRHH --}}
        <button @click="filterGroup='department'"
                :class="filterGroup==='department' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-400 hover:bg-gray-100'"
                class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors"
                title="RRHH">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </button>
        {{-- General --}}
        <button @click="filterGroup='general_status'"
                :class="filterGroup==='general_status' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-400 hover:bg-gray-100'"
                class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors"
                title="Estados">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </button>
    </div>

    {{-- ── Panel Lista ── --}}
    <div class="border-r border-gray-200 bg-white flex-col flex-shrink-0"
         :class="panel==='list' ? 'flex w-full md:w-72' : 'hidden md:flex md:w-72'">

        {{-- Búsqueda --}}
        <div class="px-3 py-2.5 border-b border-gray-100 flex-shrink-0">
            <div class="relative">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" x-model="search" placeholder="Buscar catálogo..."
                       class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-indigo-300 focus:outline-none transition">
            </div>
        </div>

        {{-- Stats rápidas --}}
        <div class="px-3 py-2 border-b border-gray-100 flex gap-2 flex-shrink-0">
            <div class="flex-1 text-center bg-indigo-50 rounded-lg py-1.5">
                <p class="text-xs font-semibold text-indigo-700" x-text="catalogs.length"></p>
                <p class="text-[10px] text-indigo-400">Total</p>
            </div>
            <div class="flex-1 text-center bg-green-50 rounded-lg py-1.5">
                <p class="text-xs font-semibold text-green-700" x-text="catalogs.reduce((s,c)=>s+(c.is_active?1:0),0)"></p>
                <p class="text-[10px] text-green-400">Activos</p>
            </div>
            <div class="flex-1 text-center bg-gray-50 rounded-lg py-1.5">
                <p class="text-xs font-semibold text-gray-600" x-text="Object.keys(typeGroups).length"></p>
                <p class="text-[10px] text-gray-400">Grupos</p>
            </div>
        </div>

        {{-- Lista --}}
        <div class="overflow-y-auto flex-1">
            <template x-for="c in filtered" :key="c.id">
                <div @click="select(c)"
                     :class="selected && selected.id === c.id
                         ? 'bg-indigo-50 border-l-2 border-indigo-500'
                         : 'border-l-2 border-transparent hover:bg-gray-50'"
                     class="flex items-center gap-3 px-3 py-3 cursor-pointer transition-colors">
                    {{-- Dot de color --}}
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                         :style="'background:' + c.color + '22'">
                        <div class="w-3.5 h-3.5 rounded-sm" :style="'background:' + c.color"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate" x-text="c.name"></p>
                        <p class="text-xs text-gray-400 truncate" x-text="c.type || '—'"></p>
                    </div>
                    <div class="flex flex-col items-end gap-1 flex-shrink-0">
                        <div :class="c.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400'"
                             class="text-[10px] px-1.5 py-0.5 rounded-full font-medium"
                             x-text="c.is_active ? 'Activo' : 'Inactivo'"></div>
                        <div x-show="c.is_system"
                             class="text-[10px] px-1.5 py-0.5 rounded-full font-medium bg-indigo-100 text-indigo-600">
                            Sistema
                        </div>
                    </div>
                </div>
            </template>
            <template x-if="filtered.length === 0">
                <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                    <svg class="w-10 h-10 mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    <p class="text-sm">Sin resultados</p>
                </div>
            </template>
        </div>
    </div>

    {{-- ── Panel Detalle ── --}}
    <div class="flex-col overflow-hidden bg-white"
         :class="panel==='detail' ? 'flex flex-1' : 'hidden md:flex md:flex-1'">

        {{-- Botón volver (solo mobile) --}}
        <button @click="panel='list'" type="button"
                class="md:hidden flex items-center gap-2 px-4 py-3 text-sm text-indigo-600 border-b border-gray-100 w-full hover:bg-gray-50 flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver a catálogos
        </button>

        <template x-if="selected || isNew">
            <div class="flex flex-col h-full overflow-hidden">

                {{-- Header detalle --}}
                <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                         :style="isNew ? 'background:#6366f122' : 'background:' + selected?.color + '22'">
                        <div class="w-4 h-4 rounded-sm"
                             :style="isNew ? 'background:#6366f1' : 'background:' + selected?.color"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h2 class="text-base font-semibold text-gray-800 truncate"
                            x-text="isNew ? 'Nuevo catálogo' : selected?.name"></h2>
                        <p class="text-xs text-gray-400" x-text="isNew ? 'Completa los datos' : (selected?.type || '—')"></p>
                    </div>
                    <template x-if="!isNew && selected">
                        <div :class="selected?.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                             class="text-xs px-2.5 py-1 rounded-full font-medium flex-shrink-0"
                             x-text="selected?.is_active ? 'Activo' : 'Inactivo'"></div>
                    </template>
                </div>

                {{-- Tabs --}}
                <div class="flex border-b border-gray-200 px-6 bg-white flex-shrink-0">
                    <button @click="tab='info'"
                            :class="tab==='info' ? 'detail-tab active' : 'detail-tab'">
                        Información
                    </button>
                    <button @click="tab='values'; if(selected && !isNew) loadValues(selected.id)"
                            :class="tab==='values' ? 'detail-tab active' : 'detail-tab'"
                            x-show="!isNew">
                        Valores
                        <span x-show="values.length > 0"
                              class="ml-1.5 bg-indigo-100 text-indigo-700 text-[10px] px-1.5 rounded-full font-semibold"
                              x-text="values.length"></span>
                    </button>
                </div>

                {{-- Contenido --}}
                <div class="flex-1 overflow-y-auto p-6">

                    {{-- Tab Info --}}
                    <div x-show="tab==='info'" class="space-y-5 max-w-lg">
                        <div class="flex items-start gap-4">
                            <div class="flex-1">
                                <label class="label">Nombre *</label>
                                <input type="text" x-model="form.name" class="input" placeholder="Ej: Áreas, Marcas...">
                            </div>
                            <div class="pt-5">
                                <label class="text-xs text-gray-500 block mb-1.5">Estado</label>
                                <div @click="form.is_active = !form.is_active"
                                     :class="form.is_active ? 'bg-indigo-600' : 'bg-gray-200'"
                                     class="w-11 h-6 rounded-full cursor-pointer transition-colors relative">
                                    <div :class="form.is_active ? 'translate-x-5' : 'translate-x-0.5'"
                                         class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"></div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="label flex items-center gap-2">
                                Tipo / Categoría
                                <span x-show="selected?.is_system"
                                      class="text-[10px] bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded-full font-medium">
                                    bloqueado
                                </span>
                            </label>
                            <input type="text" x-model="form.type"
                                   :readonly="selected?.is_system"
                                   :class="selected?.is_system ? 'input bg-gray-50 text-gray-400 cursor-not-allowed' : 'input'"
                                   placeholder="Ej: department, brand, unit...">
                            <p class="text-[11px] text-gray-400 mt-1"
                               x-text="selected?.is_system
                                   ? 'Catálogo del sistema — el tipo no se puede cambiar.'
                                   : 'Identificador técnico para usar el catálogo en otros módulos.'">
                            </p>
                        </div>

                        <div>
                            <label class="label">Descripción</label>
                            <textarea x-model="form.description" class="input resize-none" rows="3"
                                      placeholder="¿Para qué se usa este catálogo?"></textarea>
                        </div>

                        <div>
                            <label class="label">Color identificador</label>
                            <div class="flex items-center gap-3">
                                <input type="color" x-model="form.color"
                                       class="w-10 h-10 rounded-lg cursor-pointer border border-gray-200 p-0.5">
                                <div class="flex-1">
                                    <div class="h-10 rounded-lg border border-gray-200 flex items-center px-3 gap-2"
                                         :style="'background:' + form.color + '15; border-color:' + form.color + '44'">
                                        <div class="w-3 h-3 rounded-sm flex-shrink-0" :style="'background:' + form.color"></div>
                                        <span class="text-xs font-mono text-gray-600" x-text="form.color"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab Valores --}}
                    <div x-show="tab==='values'" x-cloak class="max-w-lg">

                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-sm font-semibold text-gray-700">Valores del catálogo</p>
                                <p class="text-xs text-gray-400" x-text="values.length + ' valores registrados'"></p>
                            </div>
                            <button @click="addingValue=true; editingValue=null"
                                    x-show="!addingValue"
                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Agregar valor
                            </button>
                        </div>

                        {{-- Form nuevo valor --}}
                        <div x-show="addingValue" x-cloak
                             class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 space-y-3 mb-4">
                            <p class="text-xs font-semibold text-indigo-700">Nuevo valor</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="label">Nombre *</label>
                                    <input type="text" x-model="valueForm.label" class="input text-sm"
                                           placeholder="Ej: Ventas" @keydown.enter="addValue()">
                                </div>
                                <div>
                                    <label class="label">Código</label>
                                    <input type="text" x-model="valueForm.code" class="input text-sm"
                                           placeholder="Ej: VNT" @keydown.enter="addValue()">
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button @click="addValue()" :disabled="!valueForm.label"
                                        class="btn-primary text-xs px-3 py-1.5">Agregar</button>
                                <button @click="addingValue=false; valueForm={label:'',code:'',is_active:true}"
                                        class="btn-secondary text-xs px-3 py-1.5">Cancelar</button>
                            </div>
                        </div>

                        {{-- Lista de valores --}}
                        <div class="space-y-2">
                            <template x-for="v in values" :key="v.id">
                                <div>
                                    {{-- Vista normal --}}
                                    <div x-show="editingValue !== v.id"
                                         class="flex items-center gap-3 px-4 py-3 bg-white border border-gray-200 rounded-xl hover:border-gray-300 hover:shadow-sm transition-all group">
                                        <div class="w-2 h-2 rounded-full flex-shrink-0"
                                             :style="'background:' + (selected?.color || '#6366f1')"></div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-800" x-text="v.label"></p>
                                            <p class="text-xs text-gray-400 font-mono" x-show="v.code" x-text="v.code"></p>
                                        </div>
                                        <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button @click="startEditValue(v)"
                                                    class="p-1 text-gray-400 hover:text-indigo-600 transition-colors" title="Editar">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                </svg>
                                            </button>
                                            <button @click="deleteValue(v)"
                                                    class="p-1 text-gray-400 hover:text-red-500 transition-colors" title="Eliminar">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <div @click="toggleValue(v)"
                                             :class="v.is_active ? 'bg-indigo-600' : 'bg-gray-200'"
                                             class="w-8 h-5 rounded-full cursor-pointer transition-colors relative flex-shrink-0">
                                            <div :class="v.is_active ? 'translate-x-3.5' : 'translate-x-0.5'"
                                                 class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform"></div>
                                        </div>
                                    </div>

                                    {{-- Modo edición inline --}}
                                    <div x-show="editingValue === v.id" x-cloak
                                         class="bg-amber-50 border border-amber-200 rounded-xl p-3 space-y-2">
                                        <div class="grid grid-cols-2 gap-2">
                                            <input type="text" x-model="editValueForm.label" class="input text-sm" placeholder="Nombre">
                                            <input type="text" x-model="editValueForm.code" class="input text-sm" placeholder="Código">
                                        </div>
                                        <div class="flex gap-2">
                                            <button @click="saveEditValue(v)" class="btn-primary text-xs px-3 py-1.5">Guardar</button>
                                            <button @click="editingValue=null" class="btn-secondary text-xs px-3 py-1.5">Cancelar</button>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="values.length === 0 && !addingValue">
                                <div class="flex flex-col items-center justify-center py-10 text-gray-400 border-2 border-dashed border-gray-200 rounded-xl">
                                    <svg class="w-8 h-8 mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <p class="text-sm">Sin valores aún</p>
                                    <button @click="addingValue=true" class="mt-2 text-xs text-indigo-500 hover:underline">Agregar el primero</button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex-shrink-0 px-6 py-4 border-t border-gray-200 bg-white flex items-center justify-between">
                    <template x-if="!isNew && selected">
                        <div>
                            <template x-if="!selected.is_system">
                                <button @click="del()" class="btn-danger text-sm">Eliminar catálogo</button>
                            </template>
                            <template x-if="selected.is_system">
                                <div class="flex items-center gap-1.5 text-xs text-indigo-500 bg-indigo-50 border border-indigo-200 px-3 py-2 rounded-lg">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    Catálogo del sistema — protegido
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="isNew">
                        <div></div>
                    </template>
                    <div class="flex items-center gap-2">
                        <button @click="selected=null; isNew=false; if(window.innerWidth<768) panel='list'"
                                class="btn-secondary">Cancelar</button>
                        <button @click="save()" :disabled="saving || !form.name" class="btn-primary"
                                x-text="saving ? 'Guardando...' : (isNew ? 'Crear catálogo' : 'Guardar cambios')"></button>
                    </div>
                </div>

            </div>
        </template>

        <template x-if="!selected && !isNew">
            <div class="flex flex-col items-center justify-center h-full text-gray-400 gap-3">
                <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </div>
                <p class="text-sm">Selecciona un catálogo</p>
                <button @click="openNew()" class="text-xs text-indigo-500 hover:underline">o crea uno nuevo</button>
            </div>
        </template>

    </div>{{-- /detalle --}}

</div>{{-- /body --}}
</div>{{-- /wrapper --}}
</x-slot>
</x-app-layout>
