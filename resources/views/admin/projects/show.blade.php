<x-admin-layout title="Proyecto: {{ $project->name }}">

    <div class="mb-6">
        <a href="{{ route('admin.projects') }}" class="text-sm text-gray-500 hover:text-white transition-colors flex items-center gap-1">
            ← Volver a proyectos
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Info del proyecto --}}
        <div class="rounded-2xl border p-6" style="background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.08);">
            <h3 class="text-sm font-semibold text-white mb-4">Información</h3>
            <div class="space-y-3 text-sm">
                <div>
                    <p class="text-gray-500 text-xs">Nombre</p>
                    <p class="text-white font-medium">{{ $project->name }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs">Slug</p>
                    <p class="text-gray-300">/p/{{ $project->slug }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs">Propietario</p>
                    <p class="text-gray-300">
                        {{ $project->owner->name ?? '—' }}
                        @if($project->owner?->is_superadmin)
                        <span class="ml-1 text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300">ESKALA</span>
                        @endif
                    </p>
                    <p class="text-gray-500 text-xs">{{ $project->owner->email ?? '' }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs">Estado</p>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                 {{ $project->is_active ? 'bg-green-500/15 text-green-400' : 'bg-red-500/15 text-red-400' }}">
                        {{ $project->is_active ? 'Activo' : 'Suspendido' }}
                    </span>
                </div>
                <div>
                    <p class="text-gray-500 text-xs">Miembros</p>
                    <p class="text-gray-300">{{ $project->members->count() }} usuario(s)</p>
                </div>
            </div>

            {{-- Traspaso de propiedad. El negocio se crea a nombre de quien lo
                 registra —normalmente Eskala—, así que hace falta poder ponerlo
                 a nombre de su dueño real. --}}
            <div class="mt-5 pt-5 border-t" style="border-color:rgba(255,255,255,0.08);">
                <p class="text-sm font-semibold text-white">Dueño del negocio</p>
                <p class="text-xs text-gray-500 mt-0.5 max-w-xl">
                    El dueño manda dentro de su negocio: tiene todos los permisos sin necesidad de
                    perfil. No obtiene ninguna capacidad sobre los demás negocios.
                    @if($project->owner?->is_superadmin)
                    <span class="block mt-1 text-amber-400">
                        Ahora mismo pertenece a Eskala. Traspásalo al cliente cuando se lo entregues.
                    </span>
                    @endif
                </p>

                <form method="POST" action="{{ route('admin.projects.owner', $project) }}"
                      class="flex flex-wrap items-end gap-3 mt-3"
                      onsubmit="return confirm('El negocio pasará a manos de la persona elegida, que tendrá control total sobre él. ¿Continuar?')">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 mb-1.5">Traspasar a</label>
                        <select name="owner_id" required
                                class="rounded-xl bg-gray-900 border border-gray-700 px-3 h-10 text-sm text-white outline-none focus:border-indigo-500"
                                style="min-width:16rem">
                            @forelse($candidatosDueno as $c)
                            <option value="{{ $c->id }}" {{ $c->id === $project->owner_id ? 'disabled' : '' }}>
                                {{ $c->name }}{{ $c->email ? ' — '.$c->email : '' }}{{ $c->id === $project->owner_id ? ' (dueño actual)' : '' }}
                            </option>
                            @empty
                            <option value="" disabled>Este negocio aún no tiene miembros con cuenta</option>
                            @endforelse
                        </select>
                    </div>
                    <button type="submit"
                            class="h-10 px-5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold">
                        Traspasar
                    </button>
                </form>

                @error('owner_id')
                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                @enderror
            </div>

            <form method="POST" action="{{ route('admin.projects.toggle', $project) }}" class="mt-5">
                @csrf @method('PATCH')
                <button type="submit"
                        class="w-full py-2 rounded-xl text-sm font-medium border transition-colors
                               {{ $project->is_active
                                   ? 'text-red-400 border-red-500/30 hover:bg-red-500/10'
                                   : 'text-green-400 border-green-500/30 hover:bg-green-500/10' }}">
                    {{ $project->is_active ? 'Suspender proyecto' : 'Activar proyecto' }}
                </button>
            </form>
        </div>

        {{-- Subdominio / dominio personalizado --}}
        <div class="lg:col-span-3 rounded-2xl border p-6" style="background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.08);">
            <h3 class="text-sm font-semibold text-white mb-1">Subdominio / Dominio personalizado</h3>
            <p class="text-xs text-gray-500 mb-4">Asigna un subdominio (ej: <span class="text-gray-400">mitienda.arindg.com</span>) o dominio propio (ej: <span class="text-gray-400">mitienda.com</span>). El catálogo público estará disponible en esa URL.</p>

            <form method="POST" action="{{ route('admin.projects.subdomain', $project) }}" class="flex items-end gap-3">
                @csrf @method('PATCH')
                <div class="flex-1">
                    <label class="text-xs text-gray-400 mb-1 block">Dominio</label>
                    <input type="text" name="custom_domain"
                           value="{{ $project->custom_domain ?? '' }}"
                           placeholder="mitienda.arindg.com"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl text-sm font-medium text-white transition-colors flex-shrink-0"
                        style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                    Guardar
                </button>
            </form>

            @if($project->custom_domain)
            <div class="mt-3 flex items-center gap-2">
                <span class="text-xs text-gray-500">URL del catálogo:</span>
                <a href="https://{{ $project->custom_domain }}/{{ $project->slug }}"
                   target="_blank"
                   class="text-xs text-indigo-400 hover:text-indigo-300 underline">
                    https://{{ $project->custom_domain }}/{{ $project->slug }}
                </a>
            </div>
            @endif
        </div>

        {{-- Módulos --}}
        <div class="lg:col-span-2 rounded-2xl border p-6" style="background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.08);">
            <h3 class="text-sm font-semibold text-white mb-4">Módulos activos</h3>

            <form method="POST" action="{{ route('admin.projects.modules', $project) }}">
                @csrf @method('PATCH')

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-5">
                    @foreach($allModules as $module)
                    @php $isActive = $activeModuleIds->contains($module->id); @endphp
                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all
                                  {{ $isActive ? 'border-indigo-500/50 bg-indigo-500/10' : 'border-gray-700 hover:border-gray-600' }}">
                        <input type="checkbox" name="module_ids[]" value="{{ $module->id }}"
                               {{ $isActive ? 'checked' : '' }}
                               class="w-4 h-4 rounded accent-indigo-500 flex-shrink-0">
                        <div>
                            <p class="text-sm font-medium {{ $isActive ? 'text-indigo-300' : 'text-gray-400' }}">
                                {{ $module->name }}
                            </p>
                            <p class="text-xs text-gray-600">{{ $module->key }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>

                <button type="submit"
                        class="px-5 py-2 rounded-xl text-sm font-medium text-white transition-colors"
                        style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                    Guardar módulos
                </button>
            </form>
        </div>
    </div>

    {{-- Acciones rápidas del proyecto --}}
    <div class="mt-6 rounded-2xl border p-5" style="background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.08);">
        <h3 class="text-sm font-semibold text-white mb-4">Gestión operativa</h3>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.turnos.index', $project) }}"
               class="flex items-center gap-2 px-4 py-2.5 rounded-xl border text-sm font-medium transition-all"
               style="background:rgba(99,102,241,.1);border-color:rgba(99,102,241,.3);color:#a5b4fc;"
               onmouseover="this.style.background='rgba(99,102,241,.2)'" onmouseout="this.style.background='rgba(99,102,241,.1)'">
                <svg style="width:15px;height:15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Gestión de Turnos
            </a>
        </div>
    </div>

</x-admin-layout>
