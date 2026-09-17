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
                        <div class="flex items-center justify-center gap-2">
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.toggle-admin', $user) }}"
                                  data-bx-titulo="{{ $user->is_superadmin ? 'Quitar el acceso de administrador' : 'Dar acceso de administrador' }}"
                                  data-bx-confirmar="{{ $user->is_superadmin
                                      ? $user->name.' dejará de ver y administrar todos los negocios de la plataforma.'
                                      : $user->name.' podrá ver y administrar TODOS los negocios de la plataforma, no solo los suyos.' }}"
                                  data-bx-boton="{{ $user->is_superadmin ? 'Quitar admin' : 'Hacer admin' }}">
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

                            {{-- Cambiar contraseña --}}
                            <div x-data="{ open: false }">
                                <button @click="open=true"
                                        class="text-xs px-3 py-1.5 rounded-lg border text-blue-400 border-blue-500/30 hover:bg-blue-500/10 transition-colors">
                                    Cambiar clave
                                </button>
                                <div x-show="open" x-cloak
                                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
                                     @click.self="open=false">
                                    <div class="bg-gray-900 border border-white/10 rounded-2xl p-6 w-full max-w-sm shadow-2xl">
                                        <h3 class="text-white font-semibold mb-1">Cambiar contraseña</h3>
                                        <p class="text-gray-400 text-xs mb-4">{{ $user->name }} ({{ $user->username ?? $user->email }})</p>
                                        @if(session('success'))
                                        <div class="mb-3 text-xs text-green-400">{{ session('success') }}</div>
                                        @endif
                                        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}">
                                            @csrf
                                            <div class="mb-3">
                                                <label class="block text-xs text-gray-400 mb-1">Nueva contraseña</label>
                                                <input type="password" name="password" required minlength="6"
                                                       class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                       placeholder="Mínimo 6 caracteres">
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-xs text-gray-400 mb-1">Confirmar contraseña</label>
                                                <input type="password" name="password_confirmation" required
                                                       class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                       placeholder="Repite la contraseña">
                                            </div>
                                            <div class="flex gap-2">
                                                <button type="button" @click="open=false"
                                                        class="flex-1 py-2 text-sm text-gray-400 border border-white/10 rounded-lg hover:bg-white/5 transition">
                                                    Cancelar
                                                </button>
                                                <button type="submit"
                                                        class="flex-1 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition font-medium">
                                                    Guardar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
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
