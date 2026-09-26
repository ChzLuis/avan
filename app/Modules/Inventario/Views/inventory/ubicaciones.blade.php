<x-app-layout>
<x-slot name="slot">

<div class="flex flex-col h-full w-full overflow-hidden" style="background:#F8FAFC" x-data="{ nueva: false }">

    <div class="flex-shrink-0 bg-white border-b border-gray-200 px-4 md:px-6 py-3">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-lg font-bold text-gray-900">Ubicaciones del almacén</h1>
                <p class="text-xs text-gray-500 mt-0.5">Pega un QR en cada estante y sabrás al instante qué hay guardado ahí.</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('inventory.index') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-800 px-3 py-2">← Inventario</a>
                @if($ubicaciones->isNotEmpty())
                <a href="{{ route('inventory.ubicaciones.etiquetas') }}" target="_blank"
                   class="text-xs px-3 py-2 border border-gray-200 rounded-lg text-gray-600 hover:border-emerald-400 hover:text-emerald-700 font-semibold bg-white">
                    Imprimir etiquetas
                </a>
                @endif
                <a href="{{ route('inventory.traslados') }}"
                   class="text-xs px-3 py-2 border border-gray-200 rounded-lg text-gray-600 hover:border-indigo-400 hover:text-indigo-600 font-semibold bg-white">
                    Mover entre almacenes
                </a>
                <button @click="nueva = true" class="text-xs px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold">
                    Nueva ubicación
                </button>
            </div>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 md:p-6">
        <div class="max-w-5xl mx-auto space-y-3">

            {{-- Las clases van escritas enteras: Tailwind purga lo que no
                 encuentra literal en el codigo, asi que "bg-{$color}-50" se
                 quedaria sin estilo en produccion. --}}
            @if(session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif
            @if(session('error'))
            <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            <form method="GET" class="bg-white rounded-xl border border-gray-200 p-3 flex gap-2">
                <input name="q" value="{{ request('q') }}" placeholder="Buscar por código, nombre o zona"
                       class="flex-1 text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" style="font-size:16px">
                <button class="px-4 py-2 text-sm font-semibold rounded-lg bg-gray-900 text-white">Buscar</button>
            </form>

            @php
                // Agrupadas por local, y los sueltos al final: un negocio de
                // un solo punto no tiene por que crear sucursales para usar
                // ubicaciones, y sus estantes no deben desaparecer.
                $porSede = $ubicaciones->groupBy(fn ($u) => $u->sede->name ?? '__sin__');
                $sinSede = $porSede->pull('__sin__');
            @endphp

            @forelse($porSede as $nombreSede => $grupo)
            @php $sedeObj = $grupo->first()->sede; @endphp
            <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-3 flex-wrap">
                    <div class="min-w-0">
                        <h2 class="font-bold text-gray-900">{{ $nombreSede }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $grupo->count() }} {{ $grupo->count() === 1 ? 'ubicación' : 'ubicaciones' }}
                            · {{ $grupo->sum('productos_count') }} productos
                            @if($sedeObj?->manager) · responsable: <b class="text-gray-700">{{ $sedeObj->manager }}</b>@endif
                        </p>
                    </div>
                    @if($sedeObj?->address)
                    <span class="text-[11px] text-gray-400 text-right max-w-xs">{{ $sedeObj->address }}</span>
                    @endif
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($grupo as $u)
                    <a href="{{ route('inventory.ubicaciones.show', $u->id) }}"
                       class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-gray-50 transition">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono font-bold text-gray-900">{{ $u->codigo }}</span>
                                <span class="text-gray-700">{{ $u->nombre }}</span>
                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $u->etiquetaTipo() }}</span>
                                @unless($u->is_active)
                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-red-100 text-red-700">Inactiva</span>
                                @endunless
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                @if($u->zona){{ $u->zona }} · @endif
                                {{ $u->productos_count }} {{ $u->productos_count === 1 ? 'producto' : 'productos' }}
                                @if($u->responsable) · a cargo de {{ $u->responsable }}@endif
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-indigo-600 whitespace-nowrap">Ver contenido →</span>
                    </a>
                    @endforeach
                </div>
            </section>
            @empty
            @endforelse

            @if($sinSede && $sinSede->isNotEmpty())
            <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100">
                    <h2 class="font-bold text-gray-900">Sin local asignado</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Asígnales una sucursal para poder mover mercadería entre locales con su guía de remisión.
                    </p>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($sinSede as $u)
                    <a href="{{ route('inventory.ubicaciones.show', $u->id) }}"
                       class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-gray-50 transition">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono font-bold text-gray-900">{{ $u->codigo }}</span>
                                <span class="text-gray-700">{{ $u->nombre }}</span>
                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $u->etiquetaTipo() }}</span>
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                @if($u->zona){{ $u->zona }} · @endif
                                {{ $u->productos_count }} {{ $u->productos_count === 1 ? 'producto' : 'productos' }}
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-indigo-600 whitespace-nowrap">Ver contenido →</span>
                    </a>
                    @endforeach
                </div>
            </section>
            @endif

            @if($ubicaciones->isEmpty())
            <div class="bg-white rounded-xl border border-dashed border-gray-300 p-10 text-center">
                <p class="text-gray-500 text-sm">Todavía no has creado ubicaciones.</p>
                <p class="text-gray-400 text-xs mt-1">Empieza por las que ya usas de palabra: "Estante A", "Pasillo 2", "Depósito del fondo".</p>
                <button @click="nueva = true" class="mt-4 text-xs px-4 py-2 rounded-lg bg-indigo-600 text-white font-bold">Crear la primera</button>
            </div>
            @endif

            {{-- Las sucursales se administran en Mi Empresa. Desde aquí solo se
                 usan: duplicar esa pantalla dejaría dos sitios donde crearlas. --}}
            @if($sedes->isNotEmpty())
            <p class="text-xs text-gray-400 text-center pt-1">
                Los locales se administran en
                <a href="{{ route('sedes.index') }}" class="underline font-semibold text-gray-500 hover:text-gray-700">Mi Empresa › Sucursales</a>.
            </p>
            @endif

        </div>
    </div>

    <div x-show="nueva" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(15,23,42,.55)" @click.self="nueva = false">
        <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden">
            <div class="px-5 py-3 bg-gray-900"><h3 class="text-white font-bold text-sm">Nueva ubicación</h3></div>
            <form method="POST" action="{{ route('inventory.ubicaciones.store') }}" class="p-5 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Código</label>
                        <input name="codigo" required maxlength="40" placeholder="A-01"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 font-mono" style="font-size:16px">
                        <p class="text-[11px] text-gray-400 mt-1">Es lo que irá en el QR.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo</label>
                        <select name="tipo" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" style="font-size:16px">
                            @foreach($tipos as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre</label>
                    <input name="nombre" required maxlength="120" placeholder="Estante frente a caja"
                           class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" style="font-size:16px">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Local / sucursal</label>
                    <select name="sede_id" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" style="font-size:16px">
                        <option value="">Sin asignar</option>
                        @foreach($sedes as $sede)
                        <option value="{{ $sede->id }}">{{ $sede->name }}</option>
                        @endforeach
                    </select>
                    @if($sedes->isEmpty())
                    <p class="text-[11px] text-amber-600 mt-1">
                        No tienes locales creados. <a href="{{ route('sedes.index') }}" class="underline font-semibold">Créalos aquí</a>
                        para poder mover mercadería entre ellos.
                    </p>
                    @else
                    <p class="text-[11px] text-gray-400 mt-1">Mover entre locales distintos exige guía de remisión.</p>
                    @endif
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Zona <span class="font-normal text-gray-400">(opcional)</span></label>
                        <input name="zona" maxlength="60" placeholder="Pasillo norte"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" style="font-size:16px">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Responsable</label>
                        <input name="responsable" maxlength="120" placeholder="Quién lo tiene a cargo"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" style="font-size:16px">
                    </div>
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="button" @click="nueva = false" class="flex-1 py-2.5 text-sm font-semibold rounded-lg border border-gray-200 text-gray-600">Cancelar</button>
                    <button class="flex-1 py-2.5 text-sm font-bold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white">Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>

</x-slot>
</x-app-layout>
