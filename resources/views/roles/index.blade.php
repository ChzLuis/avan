<x-app-layout>
<x-slot name="slot">

@php $csrfToken = csrf_token(); @endphp

<div class="flex-1 overflow-y-auto p-6"
     x-data="{
         roles: {{ Illuminate\Support\Js::from($roles) }},
         showModal: false,
         editingRole: null,
         form: { name: '', description: '', permissions: [] },
         saving: false,
         baseUrl: '/avan/public/{{ $project->id }}/roles',

         openNew() {
             this.editingRole = null;
             this.form = { name: '', description: '', permissions: [] };
             this.showModal = true;
         },
         openEdit(role) {
             this.editingRole = role;
             this.form = { name: role.display, description: role.description, permissions: [...role.permissions] };
             this.showModal = true;
         },
         togglePerm(perm) {
             const idx = this.form.permissions.indexOf(perm);
             if (idx >= 0) this.form.permissions.splice(idx, 1);
             else this.form.permissions.push(perm);
         },
         hasPerm(perm) { return this.form.permissions.includes(perm); },
         async save() {
             this.saving = true;
             const url    = this.editingRole ? this.baseUrl + '/' + this.editingRole.id : this.baseUrl;
             const method = this.editingRole ? 'PUT' : 'POST';
             const res = await fetch(url, {
                 method,
                 headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ $csrfToken }}', 'Accept': 'application/json' },
                 body: JSON.stringify(this.form)
             });
             if (res.ok) {
                 const data = await res.json();
                 if (this.editingRole) {
                     Object.assign(this.editingRole, { permissions: this.form.permissions });
                 } else {
                     this.roles.push(data.role);
                 }
                 this.showModal = false;
             }
             this.saving = false;
         },
         async del(role) {
             if (!confirm('¿Eliminar el rol ' + role.display + '?')) return;
             const res = await fetch(this.baseUrl + '/' + role.id, {
                 method: 'DELETE',
                 headers: { 'X-CSRF-TOKEN': '{{ $csrfToken }}', 'Accept': 'application/json' }
             });
             if (res.ok) this.roles = this.roles.filter(r => r.id !== role.id);
             else alert('No se puede eliminar: tiene usuarios asignados.');
         }
     }">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">Roles y Permisos</h1>
            <p class="text-sm text-gray-400 mt-0.5">Gestiona los niveles de acceso del equipo</p>
        </div>
        <button @click="openNew()" class="btn-primary flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo rol
        </button>
    </div>

    {{-- Grid de roles --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <template x-for="role in roles" :key="role.id">
            <div class="bg-white border border-gray-200 rounded-xl p-5 hover:border-indigo-300 transition-colors relative group">
                {{-- Acciones --}}
                <div class="absolute top-4 right-4 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="openEdit(role)"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button @click="del(role)"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>

                <div class="pr-12">
                    <p class="font-semibold text-gray-800 text-sm" x-text="role.display"></p>
                    <p class="text-xs text-gray-400 mt-0.5 mb-3" x-text="role.description || 'Sin descripción'"></p>
                </div>

                <span class="badge-process text-xs mb-3 inline-block"
                      x-text="role.users_count + ' usuario' + (role.users_count !== 1 ? 's' : '')"></span>

                <div class="flex flex-wrap gap-1 mt-2">
                    <template x-if="role.permissions.length === 0">
                        <span class="text-xs text-gray-400 italic">Sin permisos</span>
                    </template>
                    <template x-for="perm in role.permissions.slice(0, 5)" :key="perm">
                        <span class="inline-flex px-2 py-0.5 text-xs bg-indigo-50 text-indigo-700 rounded-full"
                              x-text="perm"></span>
                    </template>
                    <template x-if="role.permissions.length > 5">
                        <span class="inline-flex px-2 py-0.5 text-xs bg-gray-100 text-gray-500 rounded-full"
                              x-text="'+' + (role.permissions.length - 5) + ' más'"></span>
                    </template>
                </div>
            </div>
        </template>

        <template x-if="roles.length === 0">
            <div class="col-span-3 text-center py-16 text-gray-400">
                <p class="text-sm">Sin roles definidos. Crea el primero.</p>
            </div>
        </template>
    </div>

    {{-- MODAL crear/editar --}}
    <div x-show="showModal" x-cloak
         class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
        <div @click.outside="showModal=false"
             class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-800" x-text="editingRole ? 'Editar rol' : 'Nuevo rol'"></h3>
                <button @click="showModal=false" class="text-gray-400 hover:text-gray-600 p-1 rounded">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                <div>
                    <label class="label">Nombre del rol *</label>
                    <input type="text" x-model="form.name" class="input" placeholder="Ej: Vendedor">
                </div>
                <div>
                    <label class="label">Descripción</label>
                    <input type="text" x-model="form.description" class="input" placeholder="Breve descripción del rol">
                </div>

                {{-- Permisos --}}
                <div>
                    <label class="label mb-2">Permisos</label>
                    @foreach($allPermissions as $group => $perms)
                    <div class="mb-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ ucfirst($group) }}</p>
                        <div class="grid grid-cols-2 gap-1.5">
                            @foreach($perms as $perm)
                            <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer hover:text-indigo-700 p-1 rounded">
                                <div @click="togglePerm('{{ $perm->name }}')"
                                     :class="hasPerm('{{ $perm->name }}') ? 'bg-indigo-600' : 'bg-gray-200 border border-gray-300'"
                                     class="w-4 h-4 rounded flex items-center justify-center transition-colors flex-shrink-0">
                                    <svg x-show="hasPerm('{{ $perm->name }}')" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                {{ $perm->name }}
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                    @if($allPermissions->isEmpty())
                    <p class="text-xs text-gray-400">No hay permisos definidos. Ejecuta el seeder de roles.</p>
                    @endif
                </div>
            </div>

            <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200">
                <button @click="showModal=false" class="btn-secondary">Cancelar</button>
                <button @click="save()" :disabled="saving || !form.name" class="btn-primary"
                        x-text="saving ? 'Guardando...' : 'Guardar'"></button>
            </div>
        </div>
    </div>

</div>
</x-slot>
</x-app-layout>
