<x-app-layout>
<x-slot name="slot">

<div class="flex flex-col h-full w-full overflow-hidden" style="background:#F8FAFC"
     x-data="{ nuevo: false }">

    <div class="flex-shrink-0 bg-white border-b border-gray-200 px-4 md:px-6 py-3">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-lg font-bold text-gray-900">Toma de inventario</h1>
                <p class="text-xs text-gray-500 mt-0.5">Cuenta lo que hay de verdad en el almacén y ajusta las diferencias.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('inventory.index') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-800 px-3 py-2">← Inventario</a>
                <button @click="nuevo = true" class="text-xs px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold">
                    Empezar un conteo
                </button>
            </div>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-4 md:p-6">
        <div class="max-w-5xl mx-auto space-y-3">

            @if(session('error'))
            <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif
            @if(session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif

            @forelse($conteos as $c)
            <a href="{{ route('inventory.tomas.show', $c->id) }}"
               class="block bg-white rounded-xl border border-gray-200 hover:border-indigo-300 p-4 transition">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-gray-900">{{ $c->nombre }}</span>
                            @if($c->estado === 'abierta')
                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">En curso</span>
                            @else
                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Cerrada</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            {{ $c->items_count }} productos
                            @if($c->category) · {{ $c->category->name }} @else · Todo el almacén @endif
                            @if($c->user) · {{ $c->user->name }} @endif
                            · {{ $c->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    <span class="text-xs font-semibold text-indigo-600">
                        {{ $c->estado === 'abierta' ? 'Seguir contando →' : 'Ver resultado →' }}
                    </span>
                </div>
            </a>
            @empty
            <div class="bg-white rounded-xl border border-dashed border-gray-300 p-10 text-center">
                <p class="text-gray-500 text-sm">Todavía no has hecho ningún conteo.</p>
                <p class="text-gray-400 text-xs mt-1">Imprime las etiquetas QR, recorre el almacén escaneando y el sistema te dice qué descuadra.</p>
                <button @click="nuevo = true" class="mt-4 text-xs px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold">
                    Empezar el primero
                </button>
            </div>
            @endforelse

        </div>
    </div>

    {{-- ── Nuevo conteo ── --}}
    <div x-show="nuevo" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(15,23,42,.55)" @click.self="nuevo = false">
        <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden">
            <div class="px-5 py-3 bg-gray-900">
                <h3 class="text-white font-bold text-sm">Empezar un conteo</h3>
            </div>
            <form method="POST" action="{{ route('inventory.tomas.store') }}" class="p-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre del conteo</label>
                    <input name="nombre" required maxlength="120" value="Conteo {{ now()->format('d/m/Y') }}"
                           class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Alcance</label>
                    <select name="category_id" class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todo el almacén</option>
                        @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}">Solo: {{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">Contar por categorías es más manejable que el almacén entero de golpe.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Notas <span class="font-normal text-gray-400">(opcional)</span></label>
                    <textarea name="notas" rows="2" maxlength="1000"
                              class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="button" @click="nuevo = false" class="flex-1 py-2.5 text-sm font-semibold rounded-lg border border-gray-200 text-gray-600">Cancelar</button>
                    <button class="flex-1 py-2.5 text-sm font-bold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white">Empezar</button>
                </div>
            </form>
        </div>
    </div>
</div>

</x-slot>
</x-app-layout>
