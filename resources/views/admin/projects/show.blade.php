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
                    <p class="text-gray-300">{{ $project->owner->name ?? '—' }}</p>
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

</x-admin-layout>
