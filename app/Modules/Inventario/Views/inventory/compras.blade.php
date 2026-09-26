<x-app-layout>
<x-slot name="slot">

<div class="flex flex-col h-full w-full overflow-hidden" style="background:#F8FAFC" x-data="{ nueva: false }">

    <div class="flex-shrink-0 bg-white border-b border-gray-200 px-4 md:px-6 py-3">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-lg font-bold text-gray-900">Órdenes de compra</h1>
                <p class="text-xs text-gray-500 mt-0.5">Lo que le pediste a tus proveedores y qué falta por llegar.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('inventory.index') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-800 px-3 py-2">← Inventario</a>
                <button @click="nueva = true" class="text-xs px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold">Nueva orden</button>
            </div>
        </div>
    </div>

    <div class="flex-shrink-0 bg-white border-b border-gray-200 px-4 md:px-6 py-2.5">
        <div class="flex gap-5 text-xs flex-wrap" style="font-variant-numeric:tabular-nums">
            <span class="text-gray-500">Abiertas: <b class="text-amber-700">{{ $resumen['abiertas'] }}</b></span>
            <span class="text-gray-500">Borradores: <b class="text-gray-800">{{ $resumen['borradores'] }}</b></span>
            <span class="text-gray-500">Por recibir: <b class="text-gray-800">{{ $moneda }} {{ number_format($resumen['por_recibir'], 2) }}</b></span>
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
                <input name="q" value="{{ request('q') }}" placeholder="Buscar por número (OC-0001)"
                       class="flex-1 min-w-[170px] text-sm rounded-lg border-gray-300" style="font-size:16px">
                <select name="estado" class="text-sm rounded-lg border-gray-300" style="font-size:16px">
                    <option value="">Todos los estados</option>
                    @foreach($estados as $k => $v)
                    <option value="{{ $k }}" @selected(request('estado') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                <button class="px-4 py-2 text-sm font-semibold rounded-lg bg-gray-900 text-white">Filtrar</button>
            </form>

            @forelse($ordenes as $o)
            <a href="{{ route('inventory.compras.show', $o->id) }}"
               class="block bg-white rounded-xl border border-gray-200 hover:border-indigo-300 p-4 transition">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono font-bold text-gray-900">{{ $o->numero }}</span>
                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full {{ $o->colorEstado() }}">{{ $o->etiquetaEstado() }}</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            {{ $o->proveedor->name ?? 'Sin proveedor' }}
                            · {{ $o->items_count }} {{ $o->items_count === 1 ? 'producto' : 'productos' }}
                            @if($o->ubicacion) · llega a {{ $o->ubicacion->codigo }}@endif
                            @if($o->fecha_esperada) · esperada {{ $o->fecha_esperada->format('d/m/Y') }}@endif
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-gray-900" style="font-variant-numeric:tabular-nums">{{ $moneda }} {{ number_format($o->total, 2) }}</div>
                        <div class="text-[11px] text-gray-400">{{ $o->created_at->format('d/m/Y') }}</div>
                    </div>
                </div>
            </a>
            @empty
            <div class="bg-white rounded-xl border border-dashed border-gray-300 p-10 text-center">
                <p class="text-gray-500 text-sm">Todavía no has registrado órdenes de compra.</p>
                <p class="text-gray-400 text-xs mt-1">
                    Con una orden sabes qué pediste, a qué precio y qué falta por llegar. Al recibirla, el stock sube solo.
                </p>
                <button @click="nueva = true" class="mt-4 text-xs px-4 py-2 rounded-lg bg-indigo-600 text-white font-bold">Crear la primera</button>
            </div>
            @endforelse

        </div>
    </div>

    <div x-show="nueva" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(15,23,42,.55)" @click.self="nueva = false">
        <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden">
            <div class="px-5 py-3 bg-gray-900"><h3 class="text-white font-bold text-sm">Nueva orden de compra</h3></div>
            <form method="POST" action="{{ route('inventory.compras.store') }}" class="p-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Proveedor</label>
                    <select name="proveedor_id" class="w-full rounded-lg border-gray-300" style="font-size:16px">
                        <option value="">Sin proveedor</option>
                        @foreach($proveedores as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @if($proveedores->isEmpty())
                    <p class="text-[11px] text-amber-600 mt-1">
                        No tienes proveedores. <a href="{{ route('proveedores.index') }}" class="underline font-semibold">Créalos aquí</a>.
                    </p>
                    @endif
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">¿Dónde llega?</label>
                    <select name="warehouse_location_id" class="w-full rounded-lg border-gray-300" style="font-size:16px">
                        <option value="">Decidir al recibir</option>
                        @foreach($ubicaciones->groupBy(fn ($u) => $u->sede->name ?? 'Sin local') as $local => $grupo)
                        <optgroup label="{{ $local }}">
                            @foreach($grupo as $u)
                            <option value="{{ $u->id }}">{{ $u->codigo }} · {{ $u->nombre }}</option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">Al recibirla, la mercadería se coloca ahí automáticamente.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Fecha esperada</label>
                    <input type="date" name="fecha_esperada" class="w-full rounded-lg border-gray-300" style="font-size:16px">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Notas</label>
                    <textarea name="notas" rows="2" maxlength="1000" class="w-full rounded-lg border-gray-300" style="font-size:16px"></textarea>
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="button" @click="nueva = false" class="flex-1 py-2.5 text-sm font-semibold rounded-lg border border-gray-200 text-gray-600">Cancelar</button>
                    <button class="flex-1 py-2.5 text-sm font-bold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white">Crear borrador</button>
                </div>
            </form>
        </div>
    </div>
</div>

</x-slot>
</x-app-layout>
