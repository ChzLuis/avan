<x-admin-layout title="Usuarios">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-bold text-white">Todos los usuarios</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $users->count() }} usuarios registrados</p>
        </div>
    </div>

    <div class="rounded-2xl border overflow-hidden" style="background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.08);">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b" style="border-color:rgba(255,255,255,0.08);">
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Usuario</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Email</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Proyectos</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Rol</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Registrado</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="divide-color:rgba(255,255,255,0.05);">
                @forelse($users as $user)
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 text-white"
                                 style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                            <p class="font-medium text-white">{{ $user->name }}</p>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-gray-400">{{ $user->email }}</td>
                    <td class="px-5 py-4 text-center text-gray-300">{{ $user->owned_projects }}</td>
                    <td class="px-5 py-4 text-center">
                        @if($user->is_superadmin)
                        <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-purple-500/15 text-purple-400">
                            Super Admin
                        </span>
                        @else
                        <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-gray-700/50 text-gray-400">
                            Usuario
                        </span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-center text-gray-500 text-xs">
                        {{ $user->created_at->format('d/m/Y') }}
                    </td>
                    <td class="px-5 py-4 text-center">
                        @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.users.toggle-admin', $user) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="text-xs px-3 py-1.5 rounded-lg border transition-colors
                                           {{ $user->is_superadmin
                                               ? 'text-red-400 border-red-500/30 hover:bg-red-500/10'
                                               : 'text-purple-400 border-purple-500/30 hover:bg-purple-500/10' }}">
                                {{ $user->is_superadmin ? 'Quitar admin' : 'Hacer admin' }}
                            </button>
                        </form>
                        @else
                        <span class="text-xs text-gray-600">Tú</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-gray-600">Sin usuarios registrados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-admin-layout>
