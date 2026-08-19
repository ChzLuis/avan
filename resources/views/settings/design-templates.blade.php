<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Mis plantillas — Diseños guardados</h2></x-slot>

<div class="py-6 max-w-6xl mx-auto px-4" x-data="{ applying: null, versionsOf: null, renaming: null, importing: false, saving: false }">
    @if(session('success'))<div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $errors->first() }}</div>@endif

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <p class="text-sm text-gray-500 max-w-xl">Guarda el diseño actual de <strong>{{ $project->name }}</strong> como plantilla reutilizable, o aplica una plantilla guardada (siempre al borrador: nada se publica sin tu confirmación).</p>
        <div class="flex gap-2">
            <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold" @click="saving=!saving">＋ Guardar diseño actual</button>
            <button class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-semibold" @click="importing=!importing">Importar</button>
        </div>
    </div>

    {{-- Guardar como plantilla --}}
    <form x-show="saving" x-cloak method="POST" action="{{ route('design-templates.store') }}" class="mb-6 bg-white rounded-xl border p-4 grid gap-3 md:grid-cols-4">
        @csrf
        <input name="name" required maxlength="120" placeholder="Nombre (ej. Tecnología moderna)" class="rounded-lg border-gray-300 text-sm">
        <input name="category" maxlength="60" placeholder="Rubro (ej. tecnología)" class="rounded-lg border-gray-300 text-sm">
        <input name="description" maxlength="500" placeholder="Descripción breve" class="rounded-lg border-gray-300 text-sm md:col-span-2">
        <div class="md:col-span-4 flex gap-2">
            <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold">Capturar y guardar (v1)</button>
            <span class="text-xs text-gray-400 self-center">Captura diseño, estructura y multimedia — nunca pagos, contactos ni datos del negocio.</span>
        </div>
    </form>

    {{-- Importar --}}
    <form x-show="importing" x-cloak method="POST" enctype="multipart/form-data" action="{{ route('design-templates.import') }}" class="mb-6 bg-white rounded-xl border p-4 flex flex-wrap gap-3 items-center">
        @csrf
        <input type="file" name="file" accept="application/json" required class="text-sm">
        <input name="name" maxlength="120" placeholder="Nombre (opcional)" class="rounded-lg border-gray-300 text-sm">
        <button class="px-4 py-2 rounded-lg bg-gray-800 text-white text-sm font-semibold">Validar e importar</button>
    </form>

    {{-- Listado --}}
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse($templates as $tpl)
        @php $latest = $tpl->versions->first(); @endphp
        <div class="bg-white rounded-xl border overflow-hidden flex flex-col">
            <div class="h-28 bg-gray-100 bg-cover bg-center" @if($tpl->thumbnail_path) style="background-image:url('{{ str_starts_with($tpl->thumbnail_path,'http') ? $tpl->thumbnail_path : asset('storage/'.ltrim($tpl->thumbnail_path,'/')) }}')" @endif></div>
            <div class="p-4 flex-1">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <strong class="text-gray-900">{{ $tpl->name }}</strong>
                        <div class="text-xs text-gray-400 mt-0.5">
                            {{ $tpl->category ?: 'Sin rubro' }} · v{{ $latest?->version ?? 0 }} · {{ $tpl->updated_at->format('d/m/Y') }}
                            @if($tpl->is_default)<span class="text-indigo-600 font-semibold">· Predeterminada</span>@endif
                        </div>
                    </div>
                    <form method="POST" action="{{ route('design-templates.toggle', $tpl->id) }}">@csrf<input type="hidden" name="field" value="is_favorite">
                        <button title="Favorita" class="text-lg">{{ $tpl->is_favorite ? '★' : '☆' }}</button>
                    </form>
                </div>
                @if($tpl->description)<p class="text-xs text-gray-500 mt-2">{{ $tpl->description }}</p>@endif
                @php $unclassified = $latest?->decodedPayload()['unclassified_keys'] ?? []; @endphp
                @if($unclassified)<p class="text-[11px] text-amber-600 mt-1" title="{{ implode(', ', array_slice($unclassified, 0, 20)) }}">⚠ {{ count($unclassified) }} ajuste(s) no clasificado(s) quedaron fuera de la captura (revisar).</p>@endif
            </div>
            <div class="px-4 pb-4 flex flex-wrap gap-1.5 text-xs">
                <button class="px-2.5 py-1.5 rounded-md bg-indigo-600 text-white font-semibold" @click="applying=applying==={{ $tpl->id }}?null:{{ $tpl->id }}">Aplicar</button>
                <button class="px-2.5 py-1.5 rounded-md border" @click="versionsOf=versionsOf==={{ $tpl->id }}?null:{{ $tpl->id }}">Versiones ({{ $tpl->versions->count() }})</button>
                <form method="POST" action="{{ route('design-templates.version', $tpl->id) }}">@csrf<button class="px-2.5 py-1.5 rounded-md border" title="Captura el diseño actual como versión nueva">＋ Versión</button></form>
                <form method="POST" action="{{ route('design-templates.duplicate', $tpl->id) }}">@csrf<button class="px-2.5 py-1.5 rounded-md border">Duplicar</button></form>
                @php $tplMedia = count($latest?->decodedPayload()['media'] ?? []); @endphp
                <a class="px-2.5 py-1.5 rounded-md border" href="{{ route('design-templates.export', $tpl->id) }}"
                   @if($tplMedia) title="⚠ Referencia {{ $tplMedia }} archivo(s) multimedia de ESTE servidor: al importarla en otro servidor esos archivos no existirán y se omitirán (sin imágenes rotas). Sube los archivos al destino o reemplázalos después de aplicar." @endif>
                   {{-- "Exportar@if(...)" no compilaba: Blade ignora una directiva
                        pegada a una palabra (la confunde con un correo), asi que
                        quedaba el @if literal y el @endif suelto rompia la vista. --}}
                   Exportar
                   @if($tplMedia)<span class="text-amber-500" aria-hidden="true"> ⚠</span>@endif</a>
                <button class="px-2.5 py-1.5 rounded-md border" @click="renaming=renaming==={{ $tpl->id }}?null:{{ $tpl->id }}">Renombrar</button>
                <form method="POST" action="{{ route('design-templates.toggle', $tpl->id) }}">@csrf<input type="hidden" name="field" value="is_default"><button class="px-2.5 py-1.5 rounded-md border">Predet.</button></form>
                <form method="POST" action="{{ route('design-templates.toggle', $tpl->id) }}" data-bx-confirmar="¿Archivar esta plantilla? Podrás recuperarla luego.">@csrf<input type="hidden" name="field" value="archived"><button class="px-2.5 py-1.5 rounded-md border text-red-500">Archivar</button></form>
            </div>

            {{-- Aplicar por partes --}}
            <form x-show="applying==={{ $tpl->id }}" x-cloak method="POST" action="{{ route('design-templates.apply', $tpl->id) }}"
                  data-bx-confirmar="Se aplicará al BORRADOR de {{ $project->name }}. Tu diseño publicado no cambia hasta que publiques. ¿Continuar?"
                  class="border-t bg-gray-50 p-4 text-xs space-y-2">
                @csrf
                <strong class="text-gray-700">Qué aplicar a {{ $project->name }} (borrador):</strong>
                <label class="flex gap-2"><input type="checkbox" name="parts[]" value="all" checked> Diseño completo</label>
                <div class="grid grid-cols-2 gap-1">
                    @foreach(['identity'=>'Identidad','theme'=>'Colores y estilo','typography'=>'Tipografías','header'=>'Cabecera','navigation'=>'Menú','sections'=>'Página de inicio','catalog'=>'Catálogo','product_cards'=>'Tarjetas','footer'=>'Footer','global'=>'Otros ajustes'] as $mk=>$ml)
                    <label class="flex gap-2"><input type="checkbox" name="parts[]" value="{{ $mk }}"> {{ $ml }}</label>
                    @endforeach
                </div>
                <button class="px-3 py-1.5 rounded-md bg-indigo-600 text-white font-semibold">Aplicar al borrador</button>
            </form>

            {{-- Versiones --}}
            <div x-show="versionsOf==={{ $tpl->id }}" x-cloak class="border-t bg-gray-50 p-4 text-xs space-y-1.5">
                @foreach($tpl->versions as $v)
                <div class="flex items-center justify-between gap-2">
                    <span><strong>v{{ $v->version }}</strong> · {{ $v->created_at?->format('d/m/Y H:i') }} @if($v->notes)· {{ $v->notes }}@endif</span>
                    @unless($loop->first)
                    <form method="POST" action="{{ route('design-templates.restore', [$tpl->id, $v->version]) }}">@csrf<button class="text-indigo-600 font-semibold">Restaurar</button></form>
                    @endunless
                </div>
                @endforeach
            </div>

            {{-- Renombrar --}}
            <form x-show="renaming==={{ $tpl->id }}" x-cloak method="POST" enctype="multipart/form-data" action="{{ route('design-templates.update', $tpl->id) }}" class="border-t bg-gray-50 p-4 grid gap-2 text-xs">
                @csrf @method('PUT')
                <input name="name" value="{{ $tpl->name }}" required maxlength="120" class="rounded border-gray-300 text-xs">
                <input name="category" value="{{ $tpl->category }}" maxlength="60" placeholder="Rubro" class="rounded border-gray-300 text-xs">
                <input name="description" value="{{ $tpl->description }}" maxlength="500" placeholder="Descripción" class="rounded border-gray-300 text-xs">
                <label class="text-gray-500">Miniatura (captura o imagen de portada)
                    <input type="file" name="thumbnail" accept="image/*" class="block mt-1 text-xs">
                </label>
                <button class="px-3 py-1.5 rounded-md bg-gray-800 text-white font-semibold justify-self-start">Guardar</button>
            </form>
        </div>
        @empty
        <div class="md:col-span-3 text-center text-gray-400 py-14 bg-white rounded-xl border">Aún no tienes plantillas guardadas. Diseña tu tienda en el Constructor y presiona <strong>"Guardar diseño actual"</strong>.</div>
        @endforelse
    </div>

    @if($archived->isNotEmpty())
    <details class="mt-8"><summary class="text-sm text-gray-500 cursor-pointer">Archivadas ({{ $archived->count() }})</summary>
        <div class="mt-3 space-y-2">
            @foreach($archived as $tpl)
            <div class="flex items-center justify-between bg-white rounded-lg border px-4 py-2 text-sm">
                <span>{{ $tpl->name }} <span class="text-xs text-gray-400">· archivada {{ $tpl->archived_at->format('d/m/Y') }}</span></span>
                <form method="POST" action="{{ route('design-templates.toggle', $tpl->id) }}">@csrf<input type="hidden" name="field" value="archived"><button class="text-indigo-600 text-xs font-semibold">Recuperar</button></form>
            </div>
            @endforeach
        </div>
    </details>
    @endif
</div>
</x-app-layout>
