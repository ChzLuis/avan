<x-app-layout>
<x-slot name="slot">

@php
    $cur = $project->setting('currency_symbol') ?: 'S/';
@endphp

<div class="flex flex-col h-full w-full overflow-hidden" style="background:#F8FAFC" x-data="{ nuevo: false }">

    <div class="flex-shrink-0 bg-white border-b border-gray-200 px-4 md:px-6 py-3">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-lg font-bold text-gray-900">Activos fijos</h1>
                <p class="text-xs text-gray-500 mt-0.5">Lo que la empresa posee: equipos, muebles y herramientas. Quién los tiene y dónde están.</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('inventory.index') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-800 px-3 py-2">← Inventario</a>
                @if($activos->isNotEmpty())
                <a href="{{ route('inventory.activos.etiquetas') }}" target="_blank"
                   class="text-xs px-3 py-2 border border-gray-200 rounded-lg text-gray-600 hover:border-emerald-400 hover:text-emerald-700 font-semibold bg-white">Imprimir etiquetas</a>
                @endif
                <button @click="nuevo = true" class="text-xs px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold">Registrar activo</button>
            </div>
        </div>
    </div>

    <div class="flex-shrink-0 bg-white border-b border-gray-200 px-4 md:px-6 py-2.5">
        <div class="flex gap-5 text-xs flex-wrap">
            <span class="text-gray-500">Total: <b class="text-gray-800">{{ $resumen['total'] }}</b></span>
            <span class="text-gray-500">Operativos: <b class="text-emerald-700">{{ $resumen['operativos'] }}</b></span>
            <span class="text-gray-500">Prestados: <b class="text-blue-700">{{ $resumen['prestados'] }}</b></span>
            <span class="text-gray-500">En reparación: <b class="text-amber-700">{{ $resumen['reparacion'] }}</b></span>
            @if($resumen['extraviados'] > 0)
            <span class="text-gray-500">Extraviados: <b class="text-red-700">{{ $resumen['extraviados'] }}</b></span>
            @endif
            <span class="text-gray-500">Valor: <b class="text-gray-800">{{ $cur }} {{ number_format($resumen['valor'], 2) }}</b></span>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 md:p-6">
        <div class="max-w-5xl mx-auto space-y-3">

            @if(session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif
            @if(session('error'))
            <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            <form method="GET" class="bg-white rounded-xl border border-gray-200 p-3 flex gap-2 flex-wrap">
                <input name="q" value="{{ request('q') }}" placeholder="Nombre, código, serie o responsable"
                       class="flex-1 min-w-[180px] text-sm rounded-lg border-gray-300" style="font-size:16px">
                <select name="estado" class="text-sm rounded-lg border-gray-300" style="font-size:16px">
                    <option value="">Todos los estados</option>
                    @foreach($estados as $k => $v)
                    <option value="{{ $k }}" @selected(request('estado') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                <button class="px-4 py-2 text-sm font-semibold rounded-lg bg-gray-900 text-white">Buscar</button>
            </form>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse($activos as $a)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('inventory.activos.show', $a->id) }}" class="font-semibold text-gray-800 hover:text-indigo-600">{{ $a->nombre }}</a>
                                <div class="text-[11px] text-gray-400">
                                    <span class="font-mono">{{ $a->codigo }}</span>
                                    @if($a->marca) · {{ $a->marca }} @endif
                                    @if($a->serie) · serie {{ $a->serie }} @endif
                                </div>
                            </td>
                            <td class="px-3 py-3 text-xs text-gray-600">{{ $a->responsable ?: '—' }}</td>
                            <td class="px-3 py-3 text-xs text-gray-500">{{ $a->ubicacion?->codigo ?: '—' }}</td>
                            <td class="px-3 py-3 text-right">
                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full {{ $a->colorEstado() }}">{{ $a->etiquetaEstado() }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr><td class="px-4 py-10 text-center text-gray-400 text-sm">
                            No hay activos registrados. Empieza por lo más caro o lo que más se presta.
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <div x-show="nuevo" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto"
         style="background:rgba(15,23,42,.55)" @click.self="nuevo = false">
        <div class="bg-white rounded-2xl w-full max-w-lg overflow-hidden my-8">
            <div class="px-5 py-3 bg-gray-900"><h3 class="text-white font-bold text-sm">Registrar activo</h3></div>
            <form method="POST" action="{{ route('inventory.activos.store') }}" class="p-5 space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Código</label>
                        <input name="codigo" required maxlength="40" placeholder="ACT-001"
                               class="w-full rounded-lg border-gray-300 font-mono" style="font-size:16px">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Categoría</label>
                        <input name="categoria" maxlength="60" placeholder="Cómputo, mobiliario…"
                               class="w-full rounded-lg border-gray-300" style="font-size:16px">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre</label>
                    <input name="nombre" required maxlength="150" placeholder="Laptop Lenovo del área de ventas"
                           class="w-full rounded-lg border-gray-300" style="font-size:16px">
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Marca</label>
                        <input name="marca" maxlength="80" class="w-full rounded-lg border-gray-300" style="font-size:16px">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Modelo</label>
                        <input name="modelo" maxlength="80" class="w-full rounded-lg border-gray-300" style="font-size:16px">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Serie</label>
                        <input name="serie" maxlength="80" class="w-full rounded-lg border-gray-300" style="font-size:16px">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Responsable</label>
                        <input name="responsable" maxlength="120" placeholder="Quién responde por él"
                               class="w-full rounded-lg border-gray-300" style="font-size:16px">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Ubicación</label>
                        <select name="warehouse_location_id" class="w-full rounded-lg border-gray-300" style="font-size:16px">
                            <option value="">Sin ubicación</option>
                            @foreach($ubicaciones as $u)
                            <option value="{{ $u->id }}">{{ $u->titulo() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Fecha de compra</label>
                        <input type="date" name="fecha_compra" class="w-full rounded-lg border-gray-300" style="font-size:16px">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Valor de compra</label>
                        <input type="number" step="0.01" min="0" name="valor_compra" class="w-full rounded-lg border-gray-300" style="font-size:16px">
                    </div>
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="button" @click="nuevo = false" class="flex-1 py-2.5 text-sm font-semibold rounded-lg border border-gray-200 text-gray-600">Cancelar</button>
                    <button class="flex-1 py-2.5 text-sm font-bold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

</x-slot>
</x-app-layout>
