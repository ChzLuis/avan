<x-admin-layout title="Licencias y sesiones">

@php
    $panel  = 'background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.08);';
    $borde  = 'border-color:rgba(255,255,255,0.08);';
@endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-lg font-bold text-white">Licencias y sesiones</h2>
        <p class="text-sm text-gray-500 mt-0.5">
            Quién está dentro, con qué equipo, y cómo liberar un asiento ocupado.
        </p>
    </div>
    @if($resumen['sesiones_huerfanas'] > 0)
    <form method="POST" action="{{ route('admin.licenses.revoke-idle') }}"
          data-bx-confirmar="Se cerrarán {{ $resumen['sesiones_huerfanas'] }} sesión(es) sin actividad en los últimos {{ \App\Support\LicenseManager::MINUTOS_PARA_INACTIVO }} minutos. Quien esté trabajando ahora no se ve afectado. ¿Continuar?">
        @csrf
        <button class="px-4 py-2 rounded-xl text-sm font-semibold bg-amber-500/15 text-amber-300 border border-amber-500/30 hover:bg-amber-500/25">
            Liberar {{ $resumen['sesiones_huerfanas'] }} sesión(es) inactivas
        </button>
    </form>
    @endif
</div>

{{-- ── Resumen ──────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="rounded-2xl border p-4" style="{{ $panel }}">
        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Trabajando ahora</p>
        <p class="text-2xl font-black text-white mt-1">{{ $resumen['personas_activas'] }}</p>
        <p class="text-[11px] text-gray-500 mt-0.5">activos en los últimos {{ \App\Support\LicenseManager::MINUTOS_PARA_INACTIVO }} min</p>
    </div>
    <div class="rounded-2xl border p-4" style="{{ $panel }}">
        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Sesiones abiertas</p>
        <p class="text-2xl font-black text-white mt-1">{{ $resumen['sesiones_abiertas'] }}</p>
        <p class="text-[11px] text-gray-500 mt-0.5">{{ $resumen['personas_conectadas'] }} persona(s), varios equipos</p>
    </div>
    <div class="rounded-2xl border p-4" style="{{ $panel }}">
        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Licencias nombradas</p>
        <p class="text-2xl font-black text-indigo-300 mt-1">{{ $resumen['nombradas'] }}</p>
        <p class="text-[11px] text-gray-500 mt-0.5">asiento reservado, no gastan pool</p>
    </div>
    <div class="rounded-2xl border p-4 {{ $resumen['sobrepasado'] ? 'ring-1 ring-red-500/40' : '' }}" style="{{ $panel }}">
        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Concurrentes en uso</p>
        <p class="text-2xl font-black mt-1 {{ $resumen['sobrepasado'] ? 'text-red-400' : 'text-white' }}">
            {{ $resumen['concurrentes_en_uso'] }}@if($resumen['concurrentes_limite'] > 0)<span class="text-gray-600 text-lg"> / {{ $resumen['concurrentes_limite'] }}</span>@endif
        </p>
        <p class="text-[11px] mt-0.5 {{ $resumen['sobrepasado'] ? 'text-red-400' : 'text-gray-500' }}">
            @if($resumen['concurrentes_limite'] <= 0) sin límite configurado
            @elseif($resumen['sobrepasado']) por encima del límite contratado
            @else quedan {{ $resumen['concurrentes_libres'] }} libre(s) @endif
        </p>
    </div>
</div>

{{-- ── Configuración ────────────────────────────────────────────────── --}}
<div class="rounded-2xl border p-5 mb-6" style="{{ $panel }}">
    <form method="POST" action="{{ route('admin.licenses.settings') }}"
          class="flex flex-col lg:flex-row lg:items-end gap-5">
        @csrf
        <div>
            <label class="block text-xs font-semibold text-gray-400 mb-1.5">Licencias concurrentes contratadas</label>
            <input type="number" name="licencias_concurrentes_max" min="0" max="10000"
                   value="{{ $resumen['concurrentes_limite'] }}"
                   class="w-44 rounded-xl bg-gray-900 border border-gray-700 px-3 h-10 text-sm text-white outline-none focus:border-indigo-500">
            <p class="text-[11px] text-gray-500 mt-1">0 = sin límite. Las nombradas no consumen de aquí.</p>
        </div>

        <label class="flex items-start gap-3 cursor-pointer lg:pb-1">
            <input type="hidden" name="licencias_aplicar_limite" value="0">
            <input type="checkbox" name="licencias_aplicar_limite" value="1"
                   {{ $resumen['aplica_limite'] ? 'checked' : '' }}
                   class="mt-0.5 w-4 h-4 rounded border-gray-600 bg-gray-900 text-indigo-500">
            <span>
                <span class="block text-sm font-semibold text-white">Bloquear el acceso al superar el límite</span>
                <span class="block text-[11px] text-gray-500 mt-0.5 max-w-md">
                    Apagado, el panel solo informa y nadie queda fuera. Encendido, un usuario
                    con licencia concurrente no podrá entrar si el pool está lleno. Los
                    superadmin y las licencias nombradas nunca se bloquean.
                </span>
            </span>
        </label>

        <button class="px-5 h-10 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold lg:ml-auto">
            Guardar
        </button>
    </form>
</div>

{{-- ── Conectados ───────────────────────────────────────────────────── --}}
<div class="rounded-2xl border overflow-hidden mb-6" style="{{ $panel }}">
    <div class="px-5 py-3.5 border-b flex items-center justify-between" style="{{ $borde }}">
        <h3 class="text-sm font-bold text-white">Dentro del sistema</h3>
        <span class="text-[11px] text-gray-500">{{ $conectados->count() }} persona(s)</span>
    </div>

    @forelse($conectados as $c)
    <div class="border-b last:border-b-0" style="{{ $borde }}">
        <div class="px-5 py-3.5 flex flex-col lg:flex-row lg:items-center gap-3">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $c['activo'] ? 'bg-emerald-400' : 'bg-gray-600' }}"
                      title="{{ $c['activo'] ? 'Activo ahora' : 'Sesión abierta pero sin actividad' }}"></span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-white truncate">
                        {{ $c['nombre'] }}
                        @if($c['superadmin'])
                        <span class="ml-1.5 text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-500/20 text-purple-300">SUPERADMIN</span>
                        @endif
                    </p>
                    <p class="text-[11px] text-gray-500 truncate">
                        @if($c['email']){{ $c['email'] }} · @endif
                        {{ $c['activo'] ? 'activo ahora' : 'sin actividad ' . \App\Support\LicenseManager::desdeHace($c['ultimo_visto']) }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                {{-- Tipo de licencia --}}
                <form method="POST" action="{{ route('admin.licenses.update-user', $c['user_id']) }}">
                    @csrf @method('PATCH')
                    <select name="license_type" onchange="this.form.submit()"
                            class="rounded-lg bg-gray-900 border border-gray-700 px-2 h-8 text-xs text-white outline-none focus:border-indigo-500">
                        <option value="concurrente" {{ $c['licencia'] === 'concurrente' ? 'selected' : '' }}>Concurrente</option>
                        <option value="nombrada"    {{ $c['licencia'] === 'nombrada'    ? 'selected' : '' }}>Nombrada</option>
                    </select>
                </form>

                @if($c['user_id'] !== auth()->id())
                <form method="POST" action="{{ route('admin.licenses.revoke-user', $c['user_id']) }}"
                      data-bx-confirmar="Se cerrarán todas las sesiones de {{ $c['nombre'] }}. Si está trabajando, perderá lo que no haya guardado. ¿Continuar?">
                    @csrf
                    <button class="px-3 h-8 rounded-lg text-xs font-semibold bg-red-500/15 text-red-300 border border-red-500/30 hover:bg-red-500/25">
                        Cerrar todas
                    </button>
                </form>
                @else
                <span class="px-3 h-8 flex items-center rounded-lg text-xs text-gray-600 border border-gray-800">Eres tú</span>
                @endif
            </div>
        </div>

        {{-- Sesiones (equipos) de esta persona --}}
        <div class="px-5 pb-3.5 pl-10 space-y-1.5">
            @foreach($c['sesiones'] as $s)
            <div class="flex items-center gap-3 text-[11px] text-gray-500">
                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $s->activo ? 'bg-emerald-400/70' : 'bg-gray-700' }}"></span>
                <span class="text-gray-400">{{ $s->dispositivo }}</span>
                <span class="font-mono">{{ $s->ip_address ?: 'sin IP' }}</span>
                <span>{{ $s->visto->format('d/m H:i') }}</span>
                @if($s->id !== session()->getId())
                <form method="POST" action="{{ route('admin.licenses.revoke-session') }}" class="ml-auto">
                    @csrf
                    <input type="hidden" name="session_id" value="{{ $s->id }}">
                    <button class="text-gray-600 hover:text-red-400 font-semibold">Liberar</button>
                </form>
                @else
                <span class="ml-auto text-indigo-400 font-semibold">Esta sesión</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="px-5 py-10 text-center text-sm text-gray-600">Nadie tiene sesión abierta ahora mismo.</div>
    @endforelse
</div>

{{-- ── Desconectados ────────────────────────────────────────────────── --}}
<div class="rounded-2xl border overflow-hidden" style="{{ $panel }}">
    <div class="px-5 py-3.5 border-b flex items-center justify-between" style="{{ $borde }}">
        <h3 class="text-sm font-bold text-white">Fuera del sistema</h3>
        <span class="text-[11px] text-gray-500">{{ $desconectados->count() }} usuario(s) sin sesión abierta</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[560px]">
            <thead>
                <tr class="border-b" style="{{ $borde }}">
                    <th class="px-5 py-2.5 text-left  text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Usuario</th>
                    <th class="px-5 py-2.5 text-left  text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Email</th>
                    <th class="px-5 py-2.5 text-right text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Licencia</th>
                </tr>
            </thead>
            <tbody>
                @forelse($desconectados as $u)
                <tr class="border-b last:border-b-0" style="{{ $borde }}">
                    <td class="px-5 py-2.5">
                        <span class="text-gray-300">{{ $u->name }}</span>
                        @if($u->is_superadmin)
                        <span class="ml-1.5 text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-500/20 text-purple-300">SUPERADMIN</span>
                        @endif
                    </td>
                    <td class="px-5 py-2.5 text-gray-600">{{ $u->email }}</td>
                    <td class="px-5 py-2.5 text-right">
                        <form method="POST" action="{{ route('admin.licenses.update-user', $u->id) }}" class="inline">
                            @csrf @method('PATCH')
                            <select name="license_type" onchange="this.form.submit()"
                                    class="rounded-lg bg-gray-900 border border-gray-700 px-2 h-8 text-xs text-white outline-none focus:border-indigo-500">
                                <option value="concurrente" {{ ($u->license_type ?: 'concurrente') === 'concurrente' ? 'selected' : '' }}>Concurrente</option>
                                <option value="nombrada"    {{ $u->license_type === 'nombrada' ? 'selected' : '' }}>Nombrada</option>
                            </select>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-5 py-10 text-center text-sm text-gray-600">Todos los usuarios están conectados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($sesionesAnonimas > 0)
<p class="text-[11px] text-gray-600 mt-4">
    Hay además {{ $sesionesAnonimas }} sesión(es) sin usuario identificado: son visitantes de las
    tiendas públicas que aún no han iniciado sesión. No consumen licencia.
</p>
@endif

</x-admin-layout>
