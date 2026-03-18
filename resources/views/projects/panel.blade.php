<x-app-layout>
<x-slot name="slot">

@php
    $csrf    = csrf_token();
    $pid     = $activeProject->id;

    $rubros = ['Retail','Salud','Comercio','Restaurante','Servicios','Educación','Tecnología','Construcción','Logística','Otro'];

    // Módulos agrupados por categoría
    $moduleGroups = [
        'Comercial'    => ['catalog','orders','quotes','clients','agenda'],
        'Recursos Humanos' => ['hr','employees','payroll'],
        'Operaciones'  => ['logistics','inventory','projects'],
        'General'      => ['dashboard','settings','reports','analytics'],
    ];

    // Pasar módulos con estado por proyecto
    $allModulesData = $allModules->map(fn($m) => [
        'id'    => $m->id,
        'name'  => $m->name,
        'key'   => $m->key,
        'group' => collect($moduleGroups)->filter(fn($keys) => in_array($m->key, $keys))->keys()->first() ?? 'General',
    ]);
@endphp

<div class="flex flex-1 overflow-hidden"
     x-data="{
         projects: {{ Illuminate\Support\Js::from($allProjects->map(fn($p) => [
             'id'        => $p->id,
             'name'      => $p->name,
             'category'  => $p->category ?? '',
             'description'=> $p->description ?? '',
             'is_active' => $p->is_active,
             'is_current'=> $p->id === $activeProject->id,
             'slug'      => $p->slug,
             'phone'     => $p->phone ?? '',
             'address'   => $p->address ?? '',
             'ruc'       => $p->setting('ruc',''),
             'email'     => $p->setting('email',''),
             'country'   => $p->setting('country',''),
             'currency'  => $p->setting('currency',''),
             'modules'   => $p->modules->pluck('id')->values()->all(),
         ])) }},
         allModules: {{ Illuminate\Support\Js::from($allModulesData->values()) }},
         panel: 'list',

         selected: null,
         tab: 'general',
         form: {},
         modEnabled: {},
         saving: false,
         isNew: false,

         select(p) {
            if(window.innerWidth<768)this.panel='detail';
             this.selected = JSON.parse(JSON.stringify(p));
             this.form = {
                 name: p.name, category: p.category, description: p.description,
                 is_active: p.is_active, phone: p.phone, address: p.address,
                 ruc: p.ruc, email: p.email, country: p.country, currency: p.currency,
             };
             this.modEnabled = {};
             this.allModules.forEach(m => { this.modEnabled[m.id] = p.modules.includes(m.id); });
             this.tab = 'general';
             this.isNew = false;
         },
         openNew() {
             this.selected = null;
             this.form = { name:'', category:'', description:'', is_active:true, phone:'', address:'', ruc:'', email:'', country:'', currency:'' };
             this.modEnabled = {};
             this.allModules.forEach(m => { this.modEnabled[m.id] = ['dashboard','catalog','settings'].includes(m.key); });
             this.tab = 'general';
             this.isNew = true;
         },

         async save() {
             this.saving = true;
             const url    = this.isNew ? '/avan/public/projects' : '/avan/public/projects/' + this.selected.id;
             const method = this.isNew ? 'POST' : 'PUT';
             const res = await fetch(url, {
                 method,
                 headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ $csrf }}', 'Accept':'application/json' },
                 body: JSON.stringify(this.form)
             });
             if (res.ok) {
                 const data = await res.json();
                 if (this.isNew) {
                     const np = { ...data.project, is_current:false, modules:[], ruc:'', email:'', country:'', currency:'', phone:'', address:'' };
                     this.projects.push(np);
                     this.select(np);
                 } else {
                     Object.assign(this.selected, data.project);
                     const idx = this.projects.findIndex(p => p.id === this.selected.id);
                     if (idx >= 0) Object.assign(this.projects[idx], data.project);
                     this.form = { ...this.form, name: data.project.name, category: data.project.category ?? '', is_active: data.project.is_active };
                 }
                 this.isNew = false;
             }
             this.saving = false;
         },

         async saveModules() {
             this.saving = true;
             const enabledIds = Object.entries(this.modEnabled).filter(([,v]) => v).map(([k]) => parseInt(k));
             await fetch('/avan/public/{{ $pid }}/projects/' + this.selected.id + '/modules', {
                 method: 'POST',
                 headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ $csrf }}', 'Accept':'application/json' },
                 body: JSON.stringify({ modules: enabledIds })
             });
             const idx = this.projects.findIndex(p => p.id === this.selected.id);
             if (idx >= 0) this.projects[idx].modules = enabledIds;
             this.saving = false;
         },

         async saveSettings() {
             this.saving = true;
             await fetch('/avan/public/projects/' + this.selected.id, {
                 method: 'PUT',
                 headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ $csrf }}', 'Accept':'application/json' },
                 body: JSON.stringify({ name: this.form.name, ruc: this.form.ruc, email: this.form.email, country: this.form.country, currency: this.form.currency, phone: this.form.phone, address: this.form.address })
             });
             this.saving = false;
         },

         async del() {
             if (!confirm('¿Eliminar ' + this.selected.name + '? Esta acción no se puede deshacer.')) return;
             const res = await fetch('/avan/public/projects/' + this.selected.id, {
                 method: 'DELETE',
                 headers: { 'X-CSRF-TOKEN':'{{ $csrf }}', 'Accept':'application/json' }
             });
             if (res.ok) {
                 this.projects = this.projects.filter(p => p.id !== this.selected.id);
                 this.selected = null;
                 this.isNew = false;
             }
         },

         groupModules(group) {
             return this.allModules.filter(m => m.group === group);
         }
     }"
     x-init="if(projects.length) select(projects[0])">

{{-- ── PANEL LISTA ─────────────────────────────────────────────────────────── --}}
<div class="flex-shrink-0 border-r border-gray-200 bg-white flex-col" :class="panel==='list' ? 'flex w-full md:w-[340px]' : 'hidden md:flex md:w-[340px]'">
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-white flex-shrink-0">
        <div>
            <h2 class="text-sm font-semibold text-gray-700">Negocios</h2>
            <p class="text-xs text-gray-400" x-text="projects.length + ' negocio' + (projects.length !== 1 ? 's' : '')"></p>
        </div>
        <button @click="openNew()" class="btn-primary text-xs px-3 py-1.5 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo
        </button>
    </div>

    <div class="overflow-y-auto flex-1">
        <template x-for="p in projects" :key="p.id">
            <div @click="select(p)"
                 :class="selected && selected.id === p.id ? 'list-item selected' : 'list-item'">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                     :style="'background: linear-gradient(135deg, #6366f1, #8b5cf6)'"
                     x-text="p.name.substring(0,2).toUpperCase()"></div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5">
                        <p class="text-sm font-medium text-gray-800 truncate" x-text="p.name"></p>
                        <span x-show="p.is_current"
                              class="text-[9px] bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded-full font-semibold flex-shrink-0">activo</span>
                    </div>
                    <p class="text-xs text-gray-400 truncate" x-text="p.category || 'Sin rubro'"></p>
                </div>
                <div class="flex flex-col items-end gap-1">
                    <span :class="p.is_active ? 'badge-active' : 'badge-inactive'"
                          x-text="p.is_active ? 'On' : 'Off'"></span>
                    <span class="text-[9px] text-gray-400" x-text="(p.modules?.length ?? 0) + ' mód.'"></span>
                </div>
            </div>
        </template>
        <template x-if="projects.length === 0">
            <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                <svg class="w-10 h-10 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <p class="text-sm">Sin negocios</p>
            </div>
        </template>
    </div>
</div>

{{-- ── PANEL DETALLE ───────────────────────────────────────────────────────── --}}
<div class="flex-col overflow-hidden bg-white" :class="panel==='detail' ? 'flex flex-1' : 'hidden md:flex md:flex-1'">
    <button @click="panel='list'" type="button" class="md:hidden flex items-center gap-1 px-4 py-3 text-sm text-gray-500 border-b border-gray-100 w-full hover:bg-gray-50 flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Volver
    </button>

    {{-- Nuevo negocio o negocio seleccionado --}}
    <template x-if="selected || isNew">
        <div class="flex flex-col h-full">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-white flex-shrink-0">
                <div class="flex items-center gap-3">
                    <template x-if="!isNew && selected">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                             style="background: linear-gradient(135deg, #6366f1, #8b5cf6)"
                             x-text="selected.name.substring(0,2).toUpperCase()"></div>
                    </template>
                    <template x-if="isNew">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                    </template>
                    <div>
                        <h2 class="font-semibold text-gray-800 text-base" x-text="isNew ? 'Nuevo negocio' : selected?.name"></h2>
                        <p class="text-xs text-gray-400" x-text="isNew ? 'Completa los datos del negocio' : (selected?.category || 'Sin rubro')"></p>
                    </div>
                </div>
                <div class="flex items-center gap-2" x-show="!isNew && selected">
                    <span x-show="selected?.is_current"
                          class="text-xs bg-indigo-50 text-indigo-600 border border-indigo-200 px-2 py-0.5 rounded-full font-medium">
                        Negocio activo
                    </span>
                    <span :class="selected?.is_active ? 'badge-active' : 'badge-inactive'"
                          x-text="selected?.is_active ? 'Activo' : 'Inactivo'"></span>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="flex border-b border-gray-200 px-6 bg-white gap-1">
                <button @click="tab='general'"
                        :class="tab==='general' ? 'detail-tab active' : 'detail-tab'">
                    General
                </button>
                <button @click="tab='modulos'"
                        :class="tab==='modulos' ? 'detail-tab active' : 'detail-tab'"
                        x-show="!isNew">
                    Módulos
                </button>
                <button @click="tab='ajustes'"
                        :class="tab==='ajustes' ? 'detail-tab active' : 'detail-tab'"
                        x-show="!isNew">
                    Configuración
                </button>
                <button @click="tab='acceso'"
                        :class="tab==='acceso' ? 'detail-tab active' : 'detail-tab'"
                        x-show="!isNew">
                    Acceso
                </button>
            </div>

            {{-- Contenido --}}
            <div class="flex-1 overflow-y-auto">

                {{-- ── Tab: General ── --}}
                <div x-show="tab==='general'" class="p-6 space-y-5 max-w-xl">

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="label">Nombre del negocio *</label>
                            <input type="text" x-model="form.name" class="input" placeholder="Ej: Mi Empresa S.A.C.">
                        </div>
                        <div class="col-span-2">
                            <label class="label">Rubro</label>
                            <select x-model="form.category" class="input">
                                <option value="">— Selecciona un rubro —</option>
                                @foreach($rubros as $r)
                                <option value="{{ $r }}">{{ $r }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="label">Descripción</label>
                            <textarea x-model="form.description" class="input resize-none" rows="3"
                                      placeholder="Breve descripción del negocio..."></textarea>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <label class="label mb-2">Estado del negocio</label>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="form.is_active = !form.is_active"
                                    :class="form.is_active ? 'bg-indigo-600' : 'bg-gray-200'"
                                    class="relative w-11 h-6 rounded-full transition-colors duration-200 focus:outline-none">
                                <span :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"
                                      class="absolute top-1 left-0 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"></span>
                            </button>
                            <span class="text-sm text-gray-600" x-text="form.is_active ? 'Activo — visible y operativo' : 'Inactivo — oculto para usuarios'"></span>
                        </div>
                    </div>
                </div>

                {{-- ── Tab: Módulos ── --}}
                <div x-show="tab==='modulos'" x-cloak class="p-6">
                    <p class="text-xs text-gray-500 mb-5">Activa los módulos que necesita este negocio. Solo verán los módulos habilitados.</p>

                    @php
                        $moduleIconMap = [
                            'dashboard'  => ['icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'color' => '#6366f1'],
                            'catalog'    => ['icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16', 'color' => '#0ea5e9'],
                            'orders'     => ['icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'color' => '#f59e0b'],
                            'quotes'     => ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'color' => '#10b981'],
                            'clients'    => ['icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0', 'color' => '#8b5cf6'],
                            'agenda'     => ['icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'color' => '#ec4899'],
                            'hr'         => ['icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'color' => '#f97316'],
                            'settings'   => ['icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', 'color' => '#64748b'],
                        ];
                    @endphp

                    <div class="space-y-6">
                        @foreach($moduleGroups as $groupName => $groupKeys)
                        <div>
                            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">{{ $groupName }}</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <template x-for="m in groupModules('{{ $groupName }}')" :key="m.id">
                                    <div @click="modEnabled[m.id] = !modEnabled[m.id]"
                                         :class="modEnabled[m.id] ? 'border-indigo-300 bg-indigo-50' : 'border-gray-200 bg-white hover:border-gray-300'"
                                         class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                             :class="modEnabled[m.id] ? 'bg-indigo-600' : 'bg-gray-100'">
                                            <svg class="w-4 h-4" :class="modEnabled[m.id] ? 'text-white' : 'text-gray-400'"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium" :class="modEnabled[m.id] ? 'text-indigo-700' : 'text-gray-700'"
                                               x-text="m.name"></p>
                                            <p class="text-xs text-gray-400" x-text="m.key"></p>
                                        </div>
                                        <div :class="modEnabled[m.id] ? 'bg-indigo-600' : 'bg-gray-200'"
                                             class="w-9 h-5 rounded-full flex-shrink-0 relative transition-colors">
                                            <span :class="modEnabled[m.id] ? 'translate-x-4' : 'translate-x-0.5'"
                                                  class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform"></span>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="groupModules('{{ $groupName }}').length === 0">
                                    <p class="text-xs text-gray-400 col-span-2 italic">Sin módulos en esta categoría</p>
                                </template>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- ── Tab: Configuración ── --}}
                <div x-show="tab==='ajustes'" x-cloak class="p-6 space-y-6 max-w-xl">

                    <div>
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Datos legales</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="label">RUC / NIF / Tax ID</label>
                                <input type="text" x-model="form.ruc" class="input" placeholder="20123456789">
                            </div>
                            <div>
                                <label class="label">Email de contacto</label>
                                <input type="email" x-model="form.email" class="input" placeholder="contacto@miempresa.com">
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Contacto</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="label">Teléfono / WhatsApp</label>
                                <input type="text" x-model="form.phone" class="input" placeholder="+51 999 999 999">
                            </div>
                            <div>
                                <label class="label">Dirección</label>
                                <input type="text" x-model="form.address" class="input" placeholder="Av. Principal 123, Lima">
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Región</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label">País</label>
                                <select x-model="form.country" class="input">
                                    <option value="">— País —</option>
                                    <option value="PE">Perú</option>
                                    <option value="CO">Colombia</option>
                                    <option value="MX">México</option>
                                    <option value="AR">Argentina</option>
                                    <option value="CL">Chile</option>
                                    <option value="EC">Ecuador</option>
                                    <option value="BO">Bolivia</option>
                                    <option value="VE">Venezuela</option>
                                    <option value="PY">Paraguay</option>
                                    <option value="UY">Uruguay</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Moneda</label>
                                <select x-model="form.currency" class="input">
                                    <option value="">— Moneda —</option>
                                    <option value="PEN">PEN — Sol peruano</option>
                                    <option value="USD">USD — Dólar</option>
                                    <option value="COP">COP — Peso colombiano</option>
                                    <option value="MXN">MXN — Peso mexicano</option>
                                    <option value="ARS">ARS — Peso argentino</option>
                                    <option value="CLP">CLP — Peso chileno</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Tab: Acceso ── --}}
                <div x-show="tab==='acceso'" x-cloak class="p-6">
                    <template x-if="selected">
                        <div class="space-y-3 max-w-md">
                            <div class="bg-gray-50 rounded-xl divide-y divide-gray-100 overflow-hidden border border-gray-200">
                                <div class="flex items-center justify-between px-4 py-3">
                                    <span class="text-sm text-gray-500">Panel principal</span>
                                    <a :href="'/avan/public/' + selected.id + '/dashboard'"
                                       class="text-indigo-600 text-xs font-medium hover:underline flex items-center gap-1">
                                        Abrir panel
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                </div>
                                <div class="flex items-center justify-between px-4 py-3">
                                    <span class="text-sm text-gray-500">URL pública</span>
                                    <a :href="'/avan/public/p/' + selected.slug" target="_blank"
                                       class="text-indigo-600 text-xs font-mono hover:underline truncate max-w-[180px]"
                                       x-text="'/p/' + selected.slug"></a>
                                </div>
                                <div class="flex items-center justify-between px-4 py-3">
                                    <span class="text-sm text-gray-500">Configuración completa</span>
                                    <a :href="'/avan/public/' + selected.id + '/settings'"
                                       class="text-indigo-600 text-xs font-medium hover:underline flex items-center gap-1">
                                        Ir a ajustes
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

            </div>{{-- /overflow-y-auto --}}

            {{-- Footer --}}
            <div class="flex-shrink-0 px-6 py-4 border-t border-gray-100 bg-white flex items-center justify-between">
                <template x-if="!isNew && selected && !selected.is_current">
                    <button @click="del()" class="btn-danger text-sm">Eliminar negocio</button>
                </template>
                <template x-if="isNew || (selected && selected.is_current)">
                    <span></span>
                </template>

                <div class="flex items-center gap-3">
                    <button @click="selected=null; isNew=false" class="btn-secondary text-sm">Cancelar</button>
                    <template x-if="tab === 'modulos'">
                        <button @click="saveModules()" :disabled="saving" class="btn-primary text-sm"
                                x-text="saving ? 'Guardando...' : 'Guardar módulos'"></button>
                    </template>
                    <template x-if="tab !== 'modulos' && tab !== 'acceso'">
                        <button @click="tab === 'ajustes' ? saveSettings() : save()"
                                :disabled="saving || (!isNew && !form.name)"
                                class="btn-primary text-sm"
                                x-text="saving ? 'Guardando...' : 'Guardar'"></button>
                    </template>
                </div>
            </div>

        </div>
    </template>

    {{-- Estado vacío --}}
    <template x-if="!selected && !isNew">
        <div class="flex flex-col items-center justify-center h-full text-gray-400 gap-3">
            <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <p class="text-sm">Selecciona un negocio o crea uno nuevo</p>
        </div>
    </template>

</div>

</div>
</x-slot>
</x-app-layout>
