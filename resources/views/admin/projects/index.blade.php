<x-admin-layout title="Proyectos">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-bold text-white">Todos los proyectos</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $projects->count() }} proyectos registrados</p>
        </div>
    </div>

    <div class="rounded-2xl border overflow-hidden" style="background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.08);">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b" style="border-color:rgba(255,255,255,0.08);">
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Proyecto</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Propietario</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Órdenes</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Clientes</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Facturas</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Estado</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="divide-color:rgba(255,255,255,0.05);">
                @forelse($projects as $project)
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <td class="px-5 py-4">
                        <p class="font-medium text-white">{{ $project->name }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">/p/{{ $project->slug }}</p>
                    </td>
                    <td class="px-5 py-4">
                        <p class="text-gray-300">{{ $project->owner->name ?? '—' }}</p>
                        <p class="text-xs text-gray-600">{{ $project->owner->email ?? '' }}</p>
                    </td>
                    <td class="px-5 py-4 text-center text-gray-300">{{ $project->orders_count }}</td>
                    <td class="px-5 py-4 text-center text-gray-300">{{ $project->clients_count }}</td>
                    <td class="px-5 py-4 text-center text-gray-300">{{ $project->invoices_count }}</td>
                    <td class="px-5 py-4 text-center">
                        <span class="text-xs px-2.5 py-1 rounded-full font-medium
                                     {{ $project->is_active ? 'bg-green-500/15 text-green-400' : 'bg-red-500/15 text-red-400' }}">
                            {{ $project->is_active ? 'Activo' : 'Suspendido' }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('admin.projects.show', $project) }}"
                               class="text-xs px-3 py-1.5 rounded-lg text-indigo-400 border border-indigo-500/30 hover:bg-indigo-500/10 transition-colors">
                                Ver / Módulos
                            </a>
                            <form method="POST" action="{{ route('admin.projects.toggle', $project) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="text-xs px-3 py-1.5 rounded-lg border transition-colors
                                               {{ $project->is_active
                                                   ? 'text-red-400 border-red-500/30 hover:bg-red-500/10'
                                                   : 'text-green-400 border-green-500/30 hover:bg-green-500/10' }}">
                                    {{ $project->is_active ? 'Suspender' : 'Activar' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-10 text-center text-gray-600">Sin proyectos registrados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-admin-layout>
