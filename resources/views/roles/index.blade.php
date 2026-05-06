<x-app-layout>
<x-slot name="slot">

@php
    $csrf = csrf_token();
    $rolesUrl = route('roles.index');
@endphp

<div class="flex flex-col h-full w-full overflow-hidden">

{{-- TOP BAR --}}
<div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 bg-white flex-shrink-0">
    <div>
        <h1 class="text-lg font-semibold text-gray-800">Roles y Permisos</h1>
        <p class="text-xs text-gray-400 mt-0.5">Define los niveles de acceso del equipo</p>
    </div>
    <button @click="openNew()"
            class="flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo rol
    </button>
</div>

{{-- BODY: lista + panel --}}
<div class="flex flex-1 overflow-hidden"
     x-data="{
        roles: {{ Illuminate\Support\Js::from($roles) }},
        allPerms: {{ Illuminate\Support\Js::from($allPermissions->map(fn($g,$k)=>$g->map(fn($p)=>['name'=>$p->name,'label'=>$p->name])->values())->toArray()) }},
        allGroups: {{ Illuminate\Support\Js::from($allPermissions->keys()->values()) }},

        selected: null,
        creating: false,
        saving: false,
        deleting: false,
        panel: 'list',
        form: { name: '', description: '', permissions: [] },
        search: '',
        csrf: '{{ $csrf }}',
        baseUrl: '{{ $rolesUrl }}',

        get filtered() {
            if (!this.search) return this.roles;
            const q = this.search.toLowerCase();
            return this.roles.filter(r => r.display.toLowerCase().includes(q));
        },

        openNew() {
            this.selected = null;
            this.creating = true;
            this.form = { name: '', description: '', permissions: [] };
            this.panel = 'detail';
        },

        select(role) {
            this.selected = role;
            this.creating = false;
            this.form = { name: role.display, description: role.description || '', permissions: [...role.permissions] };
            this.panel = 'detail';
        },

        togglePerm(perm) {
            const idx = this.form.permissions.indexOf(perm);
            if (idx >= 0) this.form.permissions.splice(idx, 1);
            else this.form.permissions.push(perm);
        },

        hasPerm(perm) { return this.form.permissions.includes(perm); },

        toggleGroup(group, perms) {
            const all = perms.every(p => this.form.permissions.includes(p));
            if (all) {
                this.form.permissions = this.form.permissions.filter(p => !perms.includes(p));
            } else {
                perms.forEach(p => { if (!this.form.permissions.includes(p)) this.form.permissions.push(p); });
            }
        },

        groupAllSelected(perms) {
            return perms.length > 0 && perms.every(p => this.form.permissions.includes(p));
        },

        groupSomeSelected(perms) {
            return perms.some(p => this.form.permissions.includes(p)) && !this.groupAllSelected(perms);
        },

        async save() {
            if (!this.form.name.trim()) return;
            this.saving = true;
            const url    = this.selected ? this.baseUrl + '/' + this.selected.id : this.baseUrl;
            const method = this.selected ? 'PUT' : 'POST';
            try {
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                if (res.ok) {
                    const data = await res.json();
                    if (this.selected) {
                        Object.assign(this.selected, {
                            display:     this.form.name,
                            description: this.form.description,
                            permissions: [...this.form.permissions],
                        });
                    } else {
                        this.roles.push(data.role);
                        this.selected = data.role;
                        this.creating = false;
                        this.form.name = data.role.display;
                    }
                }
            } finally {
                this.saving = false;
            }
        },

        async del() {
            if (!this.selected) return;
            if (!confirm('¿Eliminar el rol «' + this.selected.display + '»? Esta acción no se puede deshacer.')) return;
            this.deleting = true;
            try {
                const res = await fetch(this.baseUrl + '/' + this.selected.id, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' }
                });
                if (res.ok) {
                    this.roles = this.roles.filter(r => r.id !== this.selected.id);
                    this.selected = null;
                    this.panel = 'list';
                } else {
                    const d = await res.json();
                    alert(d.error || 'No se puede eliminar: tiene usuarios asignados.');
                }
            } finally {
                this.deleting = false;
            }
        },

        permLabel(name) {
            const parts = name.split('.');
            return parts.length > 1 ? parts[1] : name;
        },

        groupColor(group) {
            const colors = {
                catalog:    '#f97316', orders:  '#34d399', quotes:    '#fbbf24',
                invoices:   '#818cf8', clients: '#fb7185', agenda:    '#a78bfa',
                reports:    '#22d3ee', hr:      '#38bdf8', attendance:'#6ee7b7',
                inventory:  '#fb923c', proveedores:'#94a3b8', rifas:  '#25d366',
                settings:   '#64748b', roles:   '#fbbf24', pos:       '#10b981',
            };
            return colors[group] || '#6366f1';
        }
     }">

    {{-- ══ COLUMNA IZQUIERDA: lista de roles ══ --}}
    <div class="flex flex-col border-r border-gray-200 bg-white flex-shrink-0 w-72"
         :class="panel==='list' ? 'flex w-full md:w-72' : 'hidden md:flex md:w-72'">

        {{-- Buscador --}}
        <div class="px-3 py-3 border-b border-gray-100">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input x-model="search" type="text" placeholder="Buscar rol..."
                       class="w-full pl-8 pr-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
        </div>

        {{-- Lista --}}
        <div class="flex-1 overflow-y-auto py-1">
            <template x-if="filtered.length === 0">
                <div class="text-center py-12 text-gray-400">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    <p class="text-xs">Sin roles. Crea el primero.</p>
                </div>
            </template>
            <template x-for="role in filtered" :key="role.id">
                <button @click="select(role); panel='detail'"
                        class="w-full text-left px-4 py-3 hover:bg-gray-50 transition border-b border-gray-100 last:border-0"
                        :class="selected?.id===role.id ? 'bg-indigo-50 border-l-2 border-l-indigo-500' : 'border-l-2 border-l-transparent'">
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-800 truncate" x-text="role.display"></p>
                            <p class="text-xs text-gray-400 mt-0.5 truncate"
                               x-text="role.permissions.length + ' permiso' + (role.permissions.length!==1?'s':'')"></p>
                        </div>
                        <div class="flex flex-col items-end gap-1 flex-shrink-0">
                            <span class="text-[10px] bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded-md font-semibold"
                                  x-text="role.users_count + ' usuario' + (role.users_count!==1?'s':'')"></span>
                        </div>
                    </div>
                </button>
            </template>
        </div>
    </div>

    {{-- ══ COLUMNA DERECHA: detalle / permisos ══ --}}
    <div class="flex flex-col flex-1 overflow-hidden bg-gray-50"
         :class="panel==='detail' ? 'flex' : 'hidden md:flex'">

        <template x-if="!selected && !creating">
            <div class="flex-1 flex flex-col items-center justify-center text-gray-300 gap-3">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                          d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                <p class="text-sm font-medium">Selecciona un rol para ver sus permisos</p>
                <button @click="openNew()" class="text-sm text-indigo-500 hover:underline">o crea uno nuevo</button>
            </div>
        </template>

        <template x-if="selected || creating">
            <div class="flex flex-col h-full">

                {{-- Header panel --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
                    <button @click="panel='list'" class="md:hidden mr-3 text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                            </div>
                            <div>
                                <input x-model="form.name"
                                       class="text-base font-semibold text-gray-800 bg-transparent border-0 focus:outline-none focus:ring-0 p-0 w-full"
                                       placeholder="Nombre del rol..."
                                       :readonly="selected && !creating">
                                <input x-model="form.description"
                                       class="text-xs text-gray-400 bg-transparent border-0 focus:outline-none focus:ring-0 p-0 w-full mt-0.5"
                                       placeholder="Descripción opcional...">
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 ml-4 flex-shrink-0">
                        <span class="text-xs text-gray-400 hidden sm:block"
                              x-text="form.permissions.length + ' permiso' + (form.permissions.length!==1?'s':'') + ' seleccionado' + (form.permissions.length!==1?'s':'')"></span>
                    </div>
                </div>

                {{-- Tabla de permisos estilo Aranda --}}
                <div class="flex-1 overflow-y-auto p-4">
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

                        {{-- Cabecera tabla --}}
                        <div class="grid grid-cols-[1fr_auto_auto] items-center px-4 py-2.5 bg-gray-50 border-b border-gray-200">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Permiso</span>
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide w-20 text-center">Módulo</span>
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide w-16 text-center">Acceso</span>
                        </div>

                        @foreach($allPermissions as $group => $perms)
                        @php $permNames = $perms->pluck('name')->toArray(); @endphp
                        {{-- Cabecera de grupo --}}
                        <div class="grid grid-cols-[1fr_auto_auto] items-center px-4 py-2 bg-gray-50/60 border-b border-gray-100 sticky top-0 z-10">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full flex-shrink-0"
                                     style="background:{{ match($group) {
                                         'catalog'=>'#f97316','orders'=>'#34d399','quotes'=>'#fbbf24',
                                         'invoices'=>'#818cf8','clients'=>'#fb7185','agenda'=>'#a78bfa',
                                         'reports'=>'#22d3ee','hr'=>'#38bdf8','attendance'=>'#6ee7b7',
                                         'inventory'=>'#fb923c','proveedores'=>'#94a3b8','rifas'=>'#25d366',
                                         'settings'=>'#64748b','roles'=>'#fbbf24','pos'=>'#10b981',
                                         default=>'#6366f1'
                                     } }}"></div>
                                <span class="text-xs font-bold text-gray-600 uppercase tracking-wide">{{ ucfirst($group) }}</span>
                                <span class="text-[10px] text-gray-400">({{ count($permNames) }})</span>
                            </div>
                            <div class="w-20"></div>
                            {{-- Checkbox de grupo --}}
                            <div class="w-16 flex justify-center">
                                <button type="button"
                                        @click="toggleGroup('{{ $group }}', {{ json_encode($permNames) }})"
                                        class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                                        :class="groupAllSelected({{ json_encode($permNames) }})
                                            ? 'bg-indigo-600 border-indigo-600'
                                            : groupSomeSelected({{ json_encode($permNames) }})
                                                ? 'bg-indigo-200 border-indigo-400'
                                                : 'bg-white border-gray-300 hover:border-indigo-400'">
                                    <svg x-show="groupAllSelected({{ json_encode($permNames) }})"
                                         class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <div x-show="groupSomeSelected({{ json_encode($permNames) }}) && !groupAllSelected({{ json_encode($permNames) }})"
                                         class="w-2 h-0.5 bg-indigo-600 rounded"></div>
                                </button>
                            </div>
                        </div>

                        {{-- Filas de permisos --}}
                        @foreach($perms as $perm)
                        <div class="grid grid-cols-[1fr_auto_auto] items-center px-4 py-2.5 border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors group">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <svg class="w-3.5 h-3.5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <span class="text-sm text-gray-700 truncate">{{ $perm->name }}</span>
                            </div>
                            <div class="w-20 flex justify-center">
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium"
                                      style="background:{{ match($group) {
                                          'catalog'=>'rgba(249,115,22,.1)','orders'=>'rgba(52,211,153,.1)',
                                          'quotes'=>'rgba(251,191,36,.1)','invoices'=>'rgba(129,140,248,.1)',
                                          'clients'=>'rgba(251,113,133,.1)','agenda'=>'rgba(167,139,250,.1)',
                                          'reports'=>'rgba(34,211,238,.1)','hr'=>'rgba(56,189,248,.1)',
                                          'attendance'=>'rgba(110,231,183,.1)','inventory'=>'rgba(251,146,60,.1)',
                                          default=>'rgba(99,102,241,.1)'
                                      } }};color:{{ match($group) {
                                          'catalog'=>'#f97316','orders'=>'#059669','quotes'=>'#d97706',
                                          'invoices'=>'#6366f1','clients'=>'#f43f5e','agenda'=>'#7c3aed',
                                          'reports'=>'#0891b2','hr'=>'#0284c7','attendance'=>'#059669',
                                          'inventory'=>'#ea580c',default=>'#4f46e5'
                                      } }}">{{ ucfirst($group) }}</span>
                            </div>
                            <div class="w-16 flex justify-center">
                                <button type="button"
                                        @click="togglePerm('{{ $perm->name }}')"
                                        class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                                        :class="hasPerm('{{ $perm->name }}')
                                            ? 'bg-indigo-600 border-indigo-600'
                                            : 'bg-white border-gray-300 hover:border-indigo-400'">
                                    <svg x-show="hasPerm('{{ $perm->name }}')"
                                         class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        @endforeach
                        @endforeach

                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-200 bg-white flex items-center justify-between flex-shrink-0">
                    <div>
                        <button x-show="selected" @click="del()" :disabled="deleting"
                                class="flex items-center gap-1.5 px-4 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition disabled:opacity-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span x-text="deleting ? 'Eliminando...' : 'Eliminar rol'"></span>
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="selected=null; creating=false; form={}; panel='list';" type="button"
                                class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            Cancelar
                        </button>
                        <button @click="save()" :disabled="saving || !form.name.trim()"
                                class="flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                            <svg x-show="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <span x-text="saving ? 'Guardando...' : (creating ? 'Crear rol' : 'Guardar cambios')"></span>
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
