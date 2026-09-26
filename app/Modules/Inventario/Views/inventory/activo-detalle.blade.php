<x-app-layout>
<x-slot name="slot">

@php
    $cur = $project->setting('currency_symbol') ?: 'S/';
@endphp

<div class="flex flex-col h-full w-full overflow-hidden" style="background:#F8FAFC">

    <div class="flex-shrink-0 bg-white border-b border-gray-200 px-4 md:px-6 py-3">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-lg font-bold text-gray-900">{{ $activo->nombre }}</h1>
                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full {{ $activo->colorEstado() }}">{{ $activo->etiquetaEstado() }}</span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5 font-mono">{{ $activo->codigo }}</p>
            </div>
            <a href="{{ route('inventory.activos') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-800 px-3 py-2">← Activos</a>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 md:p-6">
        <div class="max-w-4xl mx-auto space-y-4">

            @if(session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif

            <div class="grid md:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                    <div class="inline-block">{!! $qr !!}</div>
                    <p class="text-xs font-mono font-bold text-gray-700 mt-2">{{ $activo->codigo }}</p>
                    <p class="text-[11px] text-gray-400 mt-1">Pega este código en el equipo</p>
                </div>

                <div class="md:col-span-2 bg-white rounded-xl border border-gray-200 p-4">
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2.5 text-sm">
                        <div><dt class="text-xs text-gray-500">Marca y modelo</dt>
                             <dd class="font-medium text-gray-800">{{ trim($activo->marca.' '.$activo->modelo) ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">Número de serie</dt>
                             <dd class="font-medium text-gray-800 font-mono text-xs">{{ $activo->serie ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">Categoría</dt>
                             <dd class="font-medium text-gray-800">{{ $activo->categoria ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">Ubicación</dt>
                             <dd class="font-medium text-gray-800">{{ $activo->ubicacion?->titulo() ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">Comprado</dt>
                             <dd class="font-medium text-gray-800">{{ $activo->fecha_compra?->format('d/m/Y') ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-gray-500">Valor</dt>
                             <dd class="font-medium text-gray-800">{{ $activo->valor_compra ? $cur.' '.number_format($activo->valor_compra, 2) : '—' }}</dd></div>
                        <div class="col-span-2"><dt class="text-xs text-gray-500">Responsable actual</dt>
                             <dd class="font-bold text-gray-900">{{ $activo->responsable ?: 'Sin asignar' }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <h2 class="text-sm font-bold text-gray-800 mb-3">Asignar, mover o cambiar el estado</h2>
                <form method="POST" action="{{ route('inventory.activos.actualizar', $activo->id) }}" class="grid md:grid-cols-4 gap-3 items-end">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Responsable</label>
                        <input name="responsable" value="{{ $activo->responsable }}" maxlength="120"
                               placeholder="Vacío = devuelto"
                               class="w-full rounded-lg border-gray-300" style="font-size:16px">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Estado</label>
                        <select name="estado" class="w-full rounded-lg border-gray-300" style="font-size:16px">
                            @foreach($estados as $k => $v)
                            <option value="{{ $k }}" @selected($activo->estado === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Ubicación</label>
                        <select name="warehouse_location_id" class="w-full rounded-lg border-gray-300" style="font-size:16px">
                            <option value="">Sin ubicación</option>
                            @foreach($ubicaciones as $u)
                            <option value="{{ $u->id }}" @selected($activo->warehouse_location_id === $u->id)>{{ $u->titulo() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Nota</label>
                        <div class="flex gap-2">
                            <input name="nota" maxlength="500" placeholder="Por qué"
                                   class="flex-1 min-w-0 rounded-lg border-gray-300" style="font-size:16px">
                            <button class="px-4 py-2 text-sm font-bold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Guardar</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-2.5 border-b border-gray-100 text-xs font-bold uppercase text-gray-500">Historial</div>
                <div class="divide-y divide-gray-100">
                    @forelse($activo->eventos as $e)
                    <div class="px-4 py-3 flex gap-3 items-start">
                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 whitespace-nowrap mt-0.5">{{ $e->etiqueta() }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-800">
                                @if($e->desde && $e->hasta)
                                    {{ $e->desde }} → <b>{{ $e->hasta }}</b>
                                @elseif($e->hasta)
                                    <b>{{ $e->hasta }}</b>
                                @elseif($e->desde)
                                    Devuelto por {{ $e->desde }}
                                @else
                                    {{ $e->etiqueta() }}
                                @endif
                            </p>
                            @if($e->nota)<p class="text-xs text-gray-500 mt-0.5">{{ $e->nota }}</p>@endif
                            <p class="text-[11px] text-gray-400 mt-0.5">
                                {{ $e->created_at->format('d/m/Y H:i') }}@if($e->user) · {{ $e->user->name }}@endif
                            </p>
                        </div>
                    </div>
                    @empty
                    <div class="px-4 py-8 text-center text-gray-400 text-sm">Sin movimientos todavía.</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>

</x-slot>
</x-app-layout>
