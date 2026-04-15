<x-app-layout>
<x-slot name="slot">

@php $s = request('s', 'basico'); @endphp

<div class="flex flex-col h-full w-full overflow-hidden">

{{-- TOP BAR --}}
<div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 bg-white flex-shrink-0">
    <div>
        <h1 class="text-lg font-semibold text-gray-800">SEO</h1>
        <p class="text-xs text-gray-400 mt-0.5">{{ $project->name }}</p>
    </div>
</div>

{{-- BODY --}}
<div class="flex flex-1 overflow-hidden" x-data="{ mv: '{{ request()->has('s') ? 'detail' : 'list' }}' }">

{{-- ─── RAIL IZQUIERDA ─────────────────────────────────────────────── --}}
<div class="w-14 border-r border-gray-200 bg-gray-50 hidden md:flex flex-col items-center py-3 gap-2 flex-shrink-0">

    @php
    $railSections = [
        ['key'=>'basico',     'icon'=>'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',                                                                                                                         'tip'=>'Básico'],
        ['key'=>'social',     'icon'=>'M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z', 'tip'=>'Social'],
        ['key'=>'analytics',  'icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'tip'=>'Analytics'],
        ['key'=>'indexacion', 'icon'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',                                             'tip'=>'Indexación'],
        ['key'=>'schema',     'icon'=>'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',                                                                                                                               'tip'=>'Schema'],
    ];
    @endphp

    @foreach($railSections as $r)
    <div class="relative group">
        <a href="{{ route('settings.seo') }}?s={{ $r['key'] }}"
           class="w-9 h-9 flex items-center justify-center rounded-lg transition
                  {{ $s === $r['key'] ? 'bg-indigo-100 text-indigo-700' : 'text-gray-400 hover:text-gray-600' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $r['icon'] }}"/>
            </svg>
        </a>
        <span class="absolute left-full ml-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-800 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-10">{{ $r['tip'] }}</span>
    </div>
    @endforeach

</div>

{{-- ─── LISTA CENTRAL ──────────────────────────────────────────────── --}}
<div class="border-r border-gray-200 bg-white flex-shrink-0"
     :class="mv === 'detail' ? 'hidden md:flex md:flex-col md:w-72' : 'flex flex-col w-full md:w-72'">

    <div class="px-4 py-3 border-b border-gray-200 flex-shrink-0">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Secciones SEO</p>
    </div>

    <div class="overflow-y-auto flex-1">
        @php
        $sections = [
            ['key'=>'basico',     'label'=>'Básico',       'desc'=>'Título, descripción, keywords',     'icon'=>'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
            ['key'=>'social',     'label'=>'Social',       'desc'=>'Open Graph para redes sociales',    'icon'=>'M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z'],
            ['key'=>'analytics',  'label'=>'Analytics',    'desc'=>'GA4, GTM, Meta Pixel, TikTok',     'icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
            ['key'=>'indexacion', 'label'=>'Indexación',   'desc'=>'Robots, sitemap, verificación',     'icon'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['key'=>'schema',     'label'=>'Schema',       'desc'=>'Datos estructurados y rich results','icon'=>'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
        ];
        @endphp

        @foreach($sections as $sec)
        @php
            $badge = match($sec['key']) {
                'analytics'  => ($project->setting('ga_id') || $project->setting('fb_pixel_id') || $project->setting('gtm_id') || $project->setting('tiktok_pixel_id')) ? 'Activo' : null,
                'indexacion' => $project->setting('google_site_verification') ? 'Verificado' : null,
                default      => null,
            };
        @endphp
        <a href="{{ route('settings.seo') }}?s={{ $sec['key'] }}"
           @click="mv = 'detail'"
           class="flex items-center gap-3 px-4 py-3 cursor-pointer transition border-b border-gray-100
                  {{ $s === $sec['key'] ? 'bg-indigo-50 border-l-2 border-l-indigo-600' : 'hover:bg-gray-50' }}">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0
                        {{ $s === $sec['key'] ? 'bg-indigo-600' : 'bg-gray-100' }}">
                <svg class="w-4 h-4 {{ $s === $sec['key'] ? 'text-white' : 'text-gray-500' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $sec['icon'] }}"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">{{ $sec['label'] }}</p>
                <p class="text-xs text-gray-400 truncate">{{ $sec['desc'] }}</p>
            </div>
            @if($badge)
            <span class="text-[10px] bg-green-100 text-green-700 border border-green-200 px-1.5 py-0.5 rounded-full font-semibold flex-shrink-0">{{ $badge }}</span>
            @endif
        </a>
        @endforeach
    </div>

</div>

{{-- ─── PANEL DETALLE ──────────────────────────────────────────────── --}}
<div class="overflow-hidden bg-gray-50"
     :class="mv === 'detail' ? 'flex flex-1 flex-col' : 'hidden md:flex md:flex-1 md:flex-col'">

    {{-- Botón volver (solo mobile) --}}
    <button @click="mv = 'list'" type="button"
            class="md:hidden flex items-center gap-2 px-4 py-3 text-sm text-indigo-600 border-b border-gray-100 w-full hover:bg-gray-50 flex-shrink-0 bg-white">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver a SEO
    </button>

    @if(session('success'))
    <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,3500)" x-cloak
         class="m-4 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-2.5 text-sm flex items-center gap-2 flex-shrink-0">
        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- ══ BÁSICO ══ --}}
    @if($s === 'basico')
    <div class="px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
        <h2 class="font-semibold text-gray-800">Básico</h2>
        <p class="text-xs text-gray-400 mt-0.5">Título, descripción y palabras clave para Google</p>
    </div>
    <div class="flex-1 overflow-y-auto p-6">
        <form method="POST" action="{{ route('settings.seo.update') }}" class="space-y-5 max-w-lg">
            @csrf
            <input type="hidden" name="_tab" value="basico">

            {{-- Preview Google --}}
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-3">Vista previa en Google</p>
                <p class="text-[13px] text-blue-700 font-medium leading-snug" id="prev-title">
                    {{ $project->setting('seo_title') ?: $project->name.' — Catálogo Online' }}
                </p>
                <p class="text-[11px] text-green-700 mt-0.5">{{ url('/'.$project->slug) }}</p>
                <p class="text-[12px] text-gray-600 leading-relaxed mt-1" id="prev-desc">
                    {{ $project->setting('seo_description') ?: 'Explora nuestros productos y haz tu pedido en línea.' }}
                </p>
            </div>

            <div>
                <label class="label">Título SEO</label>
                <input type="text" name="seo_title" class="input mt-1" maxlength="70"
                       placeholder="{{ $project->name }} — Catálogo Online"
                       value="{{ old('seo_title', $project->setting('seo_title')) }}"
                       oninput="document.getElementById('prev-title').textContent=this.value||'{{ addslashes($project->name) }} — Catálogo Online';document.getElementById('cnt-title').textContent=this.value.length">
                <div class="flex justify-between mt-1">
                    <p class="text-xs text-gray-400">Aparece en Google y en la pestaña del navegador</p>
                    <span class="text-xs text-gray-400"><span id="cnt-title">{{ strlen($project->setting('seo_title','')) }}</span>/70</span>
                </div>
            </div>

            <div>
                <label class="label">Meta description</label>
                <textarea name="seo_description" class="input mt-1" rows="3" maxlength="160"
                          placeholder="Explora el catálogo de {{ $project->name }}. Encuentra productos y haz tu pedido en línea."
                          oninput="document.getElementById('prev-desc').textContent=this.value||'Explora nuestros productos.';document.getElementById('cnt-desc').textContent=this.value.length">{{ old('seo_description', $project->setting('seo_description')) }}</textarea>
                <div class="flex justify-between mt-1">
                    <p class="text-xs text-gray-400">Resumen que Google muestra debajo del título</p>
                    <span class="text-xs text-gray-400"><span id="cnt-desc">{{ strlen($project->setting('seo_description','')) }}</span>/160</span>
                </div>
            </div>

            <div>
                <label class="label">Palabras clave</label>
                <input type="text" name="seo_keywords" class="input mt-1"
                       placeholder="tienda online, {{ $project->name }}, productos, Lima"
                       value="{{ old('seo_keywords', $project->setting('seo_keywords')) }}">
                <p class="text-xs text-gray-400 mt-1">Separadas por coma.</p>
            </div>

            <div>
                <label class="label">URL Canónica</label>
                <input type="url" name="seo_canonical" class="input mt-1"
                       placeholder="{{ url('/'.$project->slug) }}"
                       value="{{ old('seo_canonical', $project->setting('seo_canonical')) }}">
                <p class="text-xs text-gray-400 mt-1">Solo si tienes dominio personalizado. Evita contenido duplicado.</p>
            </div>

            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-xs text-indigo-700">
                <p class="font-semibold text-indigo-800 mb-2">Incluido automáticamente</p>
                <div class="grid grid-cols-2 gap-1">
                    <div class="flex items-center gap-1.5"><span class="text-green-500">✓</span> Alt text en imágenes</div>
                    <div class="flex items-center gap-1.5"><span class="text-green-500">✓</span> Schema.org Product</div>
                    <div class="flex items-center gap-1.5"><span class="text-green-500">✓</span> Open Graph automático</div>
                    <div class="flex items-center gap-1.5"><span class="text-green-500">✓</span> JSON-LD Rich Results</div>
                    <div class="flex items-center gap-1.5"><span class="text-green-500">✓</span> URLs limpias por slug</div>
                    <div class="flex items-center gap-1.5"><span class="text-green-500">✓</span> Meta viewport mobile</div>
                </div>
            </div>

            <button type="submit" class="btn-primary">Guardar básico</button>
        </form>
    </div>
    @endif

    {{-- ══ SOCIAL ══ --}}
    @if($s === 'social')
    <div class="px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
        <h2 class="font-semibold text-gray-800">Social / Open Graph</h2>
        <p class="text-xs text-gray-400 mt-0.5">Cómo aparece tu catálogo al compartirlo en redes</p>
    </div>
    <div class="flex-1 overflow-y-auto p-6">
        <form method="POST" action="{{ route('settings.seo.update') }}" class="space-y-5 max-w-lg">
            @csrf
            <input type="hidden" name="_tab" value="social">

            {{-- Preview tarjeta --}}
            <div class="border border-gray-200 rounded-xl overflow-hidden max-w-sm bg-white">
                <div class="h-36 bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
                    @if($project->setting('og_image'))
                        <img src="{{ $project->setting('og_image') }}" class="h-full w-full object-cover">
                    @else
                        <svg class="w-12 h-12 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    @endif
                </div>
                <div class="px-3 py-2.5 bg-gray-50 border-t border-gray-200">
                    <p class="text-[10px] text-gray-400 uppercase">{{ parse_url(url('/'), PHP_URL_HOST) }}</p>
                    <p class="text-xs font-semibold text-gray-800 mt-0.5 truncate">
                        {{ $project->setting('og_title') ?: ($project->setting('seo_title') ?: $project->name) }}
                    </p>
                    <p class="text-[11px] text-gray-500 mt-0.5 line-clamp-2">
                        {{ $project->setting('og_description') ?: ($project->setting('seo_description') ?: 'Explora nuestros productos y haz tu pedido.') }}
                    </p>
                </div>
            </div>

            <div>
                <label class="label">OG Título</label>
                <input type="text" name="og_title" class="input mt-1" maxlength="95"
                       placeholder="{{ $project->setting('seo_title') ?: $project->name }}"
                       value="{{ old('og_title', $project->setting('og_title')) }}">
                <p class="text-xs text-gray-400 mt-1">Vacío = usa el título SEO básico.</p>
            </div>

            <div>
                <label class="label">OG Descripción</label>
                <textarea name="og_description" class="input mt-1" rows="2" maxlength="200"
                          placeholder="{{ $project->setting('seo_description') ?: 'Explora nuestros productos.' }}">{{ old('og_description', $project->setting('og_description')) }}</textarea>
            </div>

            <div>
                <label class="label">OG Imagen (URL)</label>
                <input type="url" name="og_image" class="input mt-1"
                       placeholder="https://tudominio.com/imagen.jpg"
                       value="{{ old('og_image', $project->setting('og_image')) }}">
                <p class="text-xs text-gray-400 mt-1">Recomendado: 1200×630px.</p>
            </div>

            <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 text-xs text-gray-600">
                <p class="font-semibold text-gray-700 mb-2">Compatible con</p>
                <div class="grid grid-cols-3 gap-1.5">
                    @foreach(['WhatsApp'=>'#25d366','Facebook'=>'#1877F2','X/Twitter'=>'#000','LinkedIn'=>'#0A66C2','Instagram'=>'#E1306C','Telegram'=>'#2CA5E0'] as $red => $color)
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $color }}"></span>
                        {{ $red }}
                    </div>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="btn-primary">Guardar social</button>
        </form>
    </div>
    @endif

    {{-- ══ ANALYTICS ══ --}}
    @if($s === 'analytics')
    <div class="px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
        <h2 class="font-semibold text-gray-800">Analytics & Pixels</h2>
        <p class="text-xs text-gray-400 mt-0.5">Conecta tus herramientas de medición</p>
    </div>
    <div class="flex-1 overflow-y-auto p-6">
        <form method="POST" action="{{ route('settings.seo.update') }}" class="space-y-4 max-w-lg">
            @csrf
            <input type="hidden" name="_tab" value="analytics">

            @php
            $analyticsTools = [
                ['key'=>'ga_id',           'name'=>'Google Analytics 4',  'desc'=>'Medición de visitas y conversiones',         'ph'=>'G-XXXXXXXXXX',      'color'=>'#F9AB00', 'icon'=>'M12 0C5.372 0 0 5.372 0 12s5.372 12 12 12 12-5.372 12-12S18.628 0 12 0zm5.885 17.323l-2.88-4.988-2.88 4.988H9.643l4.243-7.35-3.914-6.78h2.48l2.548 4.416 2.548-4.416h2.48l-3.914 6.78 4.243 7.35h-2.482z'],
                ['key'=>'gtm_id',          'name'=>'Google Tag Manager',   'desc'=>'Gestión centralizada de tags',               'ph'=>'GTM-XXXXXXX',       'color'=>'#4285F4', 'icon'=>'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                ['key'=>'fb_pixel_id',     'name'=>'Meta Pixel',           'desc'=>'Remarketing Facebook e Instagram Ads',       'ph'=>'123456789012345',   'color'=>'#1877F2', 'icon'=>'M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z'],
                ['key'=>'tiktok_pixel_id', 'name'=>'TikTok Pixel',         'desc'=>'Seguimiento para TikTok Ads',                'ph'=>'XXXXXXXXXXXXXXXX',  'color'=>'#000000', 'icon'=>'M9 12a4 4 0 104 4V4a5 5 0 005 5'],
            ];
            @endphp

            @foreach($analyticsTools as $tool)
            <div class="border border-gray-200 rounded-xl p-4 space-y-3 bg-white">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                         style="background:{{ $tool['color'] }}18">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             style="color:{{ $tool['color'] }}">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $tool['icon'] }}"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-800">{{ $tool['name'] }}</p>
                        <p class="text-xs text-gray-400">{{ $tool['desc'] }}</p>
                    </div>
                    @if($project->setting($tool['key']))
                    <span class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-semibold">Activo</span>
                    @endif
                </div>
                <input type="text" name="{{ $tool['key'] }}" class="input font-mono"
                       placeholder="{{ $tool['ph'] }}"
                       value="{{ old($tool['key'], $project->setting($tool['key'])) }}">
            </div>
            @endforeach

            <button type="submit" class="btn-primary">Guardar analytics</button>
        </form>
    </div>
    @endif

    {{-- ══ INDEXACIÓN ══ --}}
    @if($s === 'indexacion')
    <div class="px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
        <h2 class="font-semibold text-gray-800">Indexación</h2>
        <p class="text-xs text-gray-400 mt-0.5">Controla cómo Google rastrea tu catálogo</p>
    </div>
    <div class="flex-1 overflow-y-auto p-6">
        <form method="POST" action="{{ route('settings.seo.update') }}" class="space-y-5 max-w-lg">
            @csrf
            <input type="hidden" name="_tab" value="indexacion">

            <div>
                <label class="label">Robots</label>
                <select name="robots" class="input mt-1">
                    @php $robots = $project->setting('robots','index, follow'); @endphp
                    <option value="index, follow"     {{ $robots==='index, follow'    ?'selected':'' }}>index, follow — Indexar y seguir links (recomendado)</option>
                    <option value="noindex, follow"   {{ $robots==='noindex, follow'  ?'selected':'' }}>noindex, follow — No indexar pero sí seguir links</option>
                    <option value="index, nofollow"   {{ $robots==='index, nofollow'  ?'selected':'' }}>index, nofollow — Indexar pero no seguir links</option>
                    <option value="noindex, nofollow" {{ $robots==='noindex, nofollow'?'selected':'' }}>noindex, nofollow — No indexar ni seguir links</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Usa "index, follow" para aparecer en Google.</p>
            </div>

            <div>
                <label class="label">Sitemap automático</label>
                <select name="sitemap_enabled" class="input mt-1">
                    <option value="1" {{ $project->setting('sitemap_enabled','1')==='1'?'selected':'' }}>Activado</option>
                    <option value="0" {{ $project->setting('sitemap_enabled','1')==='0'?'selected':'' }}>Desactivado</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">
                    URL: <span class="font-mono text-indigo-600 select-all">{{ url('/'.$project->slug.'/sitemap.xml') }}</span>
                </p>
            </div>

            <div class="pt-2 border-t border-gray-100 space-y-4">
                <p class="text-sm font-semibold text-gray-700">Verificación de propiedad</p>

                <div>
                    <label class="label flex items-center gap-2">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" style="color:#4285F4"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                        Google Search Console
                    </label>
                    <input type="text" name="google_site_verification" class="input mt-1 font-mono text-sm"
                           placeholder="Pega el valor del meta tag de verificación"
                           value="{{ old('google_site_verification', $project->setting('google_site_verification')) }}">
                    <p class="text-xs text-gray-400 mt-1">En Search Console → Verificar → Etiqueta HTML → copia solo el valor de content=""</p>
                </div>

                <div>
                    <label class="label">Bing Webmaster Tools</label>
                    <input type="text" name="bing_site_verification" class="input mt-1 font-mono text-sm"
                           placeholder="Valor del meta tag de Bing"
                           value="{{ old('bing_site_verification', $project->setting('bing_site_verification')) }}">
                </div>
            </div>

            <button type="submit" class="btn-primary">Guardar indexación</button>
        </form>
    </div>
    @endif

    {{-- ══ SCHEMA ══ --}}
    @if($s === 'schema')
    <div class="px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
        <h2 class="font-semibold text-gray-800">Schema / Datos estructurados</h2>
        <p class="text-xs text-gray-400 mt-0.5">Rich Results en Google: estrellas, precios, horarios</p>
    </div>
    <div class="flex-1 overflow-y-auto p-6">
        <form method="POST" action="{{ route('settings.seo.update') }}" class="space-y-5 max-w-lg">
            @csrf
            <input type="hidden" name="_tab" value="schema">

            <div class="bg-purple-50 border border-purple-200 rounded-xl p-4 text-xs text-purple-700">
                <p class="font-semibold mb-1">¿Qué son los datos estructurados?</p>
                <p class="leading-relaxed">Le dicen a Google exactamente qué tipo de negocio eres. Pueden aparecer como Rich Results con estrellas, precio, horario y mapa en los resultados de búsqueda.</p>
            </div>

            <div>
                <label class="label">Tipo de negocio</label>
                <select name="schema_type" class="input mt-1">
                    @php $schemaType = $project->setting('schema_type','LocalBusiness'); @endphp
                    @foreach([
                        'LocalBusiness'            => 'Local Business (genérico)',
                        'Store'                    => 'Store (tienda)',
                        'Restaurant'               => 'Restaurant (restaurante / food)',
                        'FoodEstablishment'        => 'Food Establishment (comida general)',
                        'ClothingStore'            => 'Clothing Store (ropa / moda)',
                        'ElectronicsStore'         => 'Electronics Store (electrónica)',
                        'BeautySalon'              => 'Beauty Salon (spa / estética)',
                        'HairSalon'                => 'Hair Salon (barbería / peluquería)',
                        'HealthAndBeautyBusiness'  => 'Health & Beauty',
                        'HomeAndConstructionBusiness'=>'Home & Construction',
                        'ProfessionalService'      => 'Professional Service',
                        'MedicalBusiness'          => 'Medical Business (salud)',
                        'EducationalOrganization'  => 'Educational Organization',
                        'SportsActivityLocation'   => 'Sports (gym / deporte)',
                        'TravelAgency'             => 'Travel Agency (turismo)',
                    ] as $val => $lbl)
                    <option value="{{ $val }}" {{ $schemaType===$val?'selected':'' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label">Rango de precios</label>
                <select name="schema_price_range" class="input mt-1">
                    @php $pr = $project->setting('schema_price_range','$$'); @endphp
                    <option value="$"    {{ $pr==='$'   ?'selected':'' }}>$ — Económico</option>
                    <option value="$$"   {{ $pr==='$$'  ?'selected':'' }}>$$ — Precio moderado</option>
                    <option value="$$$"  {{ $pr==='$$$' ?'selected':'' }}>$$$ — Premium</option>
                    <option value="$$$$" {{ $pr==='$$$$'?'selected':'' }}>$$$$ — Lujo</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Aparece en Google Maps y resultados de búsqueda.</p>
            </div>

            <div>
                <label class="label">Horario de atención</label>
                <input type="text" name="schema_opening_hours" class="input mt-1 font-mono"
                       placeholder="Mo-Fr 09:00-18:00, Sa 09:00-13:00"
                       value="{{ old('schema_opening_hours', $project->setting('schema_opening_hours')) }}">
                <p class="text-xs text-gray-400 mt-1">
                    Formato: <span class="font-mono">Mo-Fr 09:00-18:00</span> · Días: Mo Tu We Th Fr Sa Su
                </p>
            </div>

            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-xs text-gray-600">
                <p class="font-semibold text-gray-700 mb-2">Rich Results generados automáticamente</p>
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2"><span class="text-green-500 font-bold">✓</span> Product schema (nombre, precio, disponibilidad)</div>
                    <div class="flex items-center gap-2"><span class="text-green-500 font-bold">✓</span> BreadcrumbList para navegación</div>
                    <div class="flex items-center gap-2"><span class="text-green-500 font-bold">✓</span> LocalBusiness con dirección, teléfono y horario</div>
                    <div class="flex items-center gap-2"><span class="text-green-500 font-bold">✓</span> WebSite con SearchAction</div>
                </div>
            </div>

            <button type="submit" class="btn-primary">Guardar schema</button>
        </form>
    </div>
    @endif

</div>{{-- /detail --}}
</div>{{-- /body --}}
</div>{{-- /wrap --}}

</x-slot>
</x-app-layout>
