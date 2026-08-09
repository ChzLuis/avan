<x-portal-layout layout="panel" :project="$project" pageTitle="Bots">

<div class="p-6">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-bold text-gray-800">Constructor de bots</h1>
            <p class="text-xs text-gray-500">Crea flujos que responden con la info real de tu negocio (catálogo, precios, IA).</p>
        </div>
        <a href="{{ route('bot-flows.editor.new') }}"
           class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">+ Nuevo bot</a>
    </div>

    @if($flows->isEmpty())
        <div class="text-center py-16 text-gray-400">
            <div class="text-4xl mb-2">🤖</div>
            <p class="text-sm">Aún no tienes bots. Crea el primero.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @foreach($flows as $f)
                <a href="{{ route('bot-flows.editor', $f) }}"
                   class="block bg-white border border-gray-200 rounded-xl p-4 hover:border-indigo-400 hover:shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-gray-800">{{ $f->nombre }}</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $f->activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $f->activo ? 'Activo' : 'Borrador' }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">{{ count($f->definicion['bloques'] ?? []) }} bloques · editado {{ $f->updated_at->diffForHumans() }}</p>
                </a>
            @endforeach
        </div>
    @endif
</div>
</x-portal-layout>
