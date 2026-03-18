<x-app-layout>
<x-slot name="slot">
<div class="flex flex-1 overflow-hidden">

{{-- PANEL LISTA --}}
<div class="list-panel flex-shrink-0">
    <div class="px-4 py-3 border-b border-gray-200 bg-white flex-shrink-0">
        <h2 class="text-sm font-semibold text-gray-700">Diseño</h2>
        <p class="text-xs text-gray-400 mt-0.5">{{ $project->name }}</p>
    </div>
    <div class="overflow-y-auto flex-1">
        @php
            $sections = [
                ['key'=>'brand',   'label'=>'Identidad visual', 'desc'=>'Colores y logo',      'icon'=>'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'],
                ['key'=>'social',  'label'=>'Redes sociales',   'desc'=>'Facebook, Instagram',  'icon'=>'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1'],
                ['key'=>'tienda',  'label'=>'Tienda online',     'desc'=>'Hero, banners, textos','icon'=>'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
                ['key'=>'seo',     'label'=>'SEO',               'desc'=>'Google, búsquedas',    'icon'=>'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
                ['key'=>'payments','label'=>'Pagos en línea',     'desc'=>'Pasarelas y métodos',  'icon'=>'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                ['key'=>'public',  'label'=>'Página pública',    'desc'=>'URL y estadísticas',   'icon'=>'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['key'=>'login',   'label'=>'Pantalla de login',  'desc'=>'Fondo y mensaje de bienvenida','icon'=>'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1'],
            ];
            $current = request('s', 'brand');
        @endphp
        @foreach($sections as $s)
        <a href="{{ route('settings.design', ['project'=>$project->id]) }}?s={{ $s['key'] }}"
           class="list-item {{ $current === $s['key'] ? 'selected' : '' }}">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                        {{ $current === $s['key'] ? 'bg-indigo-600' : 'bg-gray-100' }}">
                <svg class="w-4 h-4 {{ $current === $s['key'] ? 'text-white' : 'text-gray-500' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $s['icon'] }}"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800">{{ $s['label'] }}</p>
                <p class="text-xs text-gray-400">{{ $s['desc'] }}</p>
            </div>
        </a>
        @endforeach
    </div>
</div>

{{-- PANEL DETALLE --}}
<div class="detail-panel">

    @if(session('success'))
    <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,3000)" x-cloak
         class="m-4 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-2.5 text-sm flex items-center gap-2">
        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    @php $s = request('s', 'brand'); @endphp

    {{-- ── Identidad visual ── --}}
    @if($s === 'brand')
    <div class="flex flex-col h-full">
        <div class="px-6 py-4 border-b border-gray-100 bg-white flex-shrink-0">
            <h2 class="font-semibold text-gray-800">Identidad visual</h2>
            <p class="text-xs text-gray-400 mt-0.5">Colores y apariencia del catálogo público</p>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
            <form method="POST" action="{{ route('settings.design.update', ['project'=>$project->id]) }}"
                  class="space-y-6 max-w-lg" x-data="{ color: '{{ $project->setting('primary_color', '#4f46e5') }}' }">
                @csrf

                <div>
                    <label class="label">Color principal</label>
                    <div class="flex items-center gap-4 mt-1">
                        <input type="color" name="primary_color" x-model="color"
                               class="w-14 h-12 rounded-xl cursor-pointer border-2 border-gray-200 p-0.5">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-5 h-5 rounded-md border border-gray-200 flex-shrink-0"
                                     :style="'background:' + color"></div>
                                <code class="text-sm text-gray-700 font-mono" x-text="color"></code>
                            </div>
                            <p class="text-xs text-gray-400">Usado en botones, badges y destacados de la página pública</p>
                        </div>
                    </div>
                    {{-- Preview --}}
                    <div class="mt-4 p-4 bg-gray-50 rounded-xl border border-gray-200">
                        <p class="text-xs text-gray-500 mb-2 font-medium">Vista previa</p>
                        <div class="flex items-center gap-2">
                            <button type="button" class="px-4 py-2 rounded-lg text-white text-sm font-medium shadow-sm"
                                    :style="'background:' + color">Agregar al carrito</button>
                            <span class="px-2 py-0.5 rounded-full text-white text-xs font-medium"
                                  :style="'background:' + color">Nuevo</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="label">Mensaje de WhatsApp</label>
                    <input type="text" name="whatsapp_msg" class="input"
                           placeholder="Hola, vi tu catálogo y me interesa..."
                           value="{{ $project->setting('whatsapp_msg') }}">
                    <p class="text-xs text-gray-400 mt-1">Se envía cuando el cliente hace clic en el botón de WhatsApp</p>
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Redes sociales ── --}}
    @if($s === 'social')
    <div class="flex flex-col h-full">
        <div class="px-6 py-4 border-b border-gray-100 bg-white flex-shrink-0">
            <h2 class="font-semibold text-gray-800">Redes sociales</h2>
            <p class="text-xs text-gray-400 mt-0.5">Links visibles en tu catálogo público</p>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
            <form method="POST" action="{{ route('settings.design.update', ['project'=>$project->id]) }}"
                  class="space-y-4 max-w-lg">
                @csrf
                @php
                    $socialFields = [
                        ['key'=>'facebook_url',  'label'=>'Facebook',  'placeholder'=>'https://facebook.com/tunegocio',  'color'=>'#1877F2'],
                        ['key'=>'instagram_url', 'label'=>'Instagram', 'placeholder'=>'https://instagram.com/tunegocio', 'color'=>'#E1306C'],
                        ['key'=>'tiktok_url',    'label'=>'TikTok',    'placeholder'=>'https://tiktok.com/@tunegocio',   'color'=>'#000000'],
                        ['key'=>'youtube_url',   'label'=>'YouTube',   'placeholder'=>'https://youtube.com/@tunegocio',  'color'=>'#FF0000'],
                        ['key'=>'twitter_url',   'label'=>'X / Twitter','placeholder'=>'https://x.com/tunegocio',        'color'=>'#000000'],
                        ['key'=>'linkedin_url',  'label'=>'LinkedIn',  'placeholder'=>'https://linkedin.com/company/...','color'=>'#0A66C2'],
                    ];
                @endphp
                @foreach($socialFields as $field)
                <div>
                    <label class="label flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $field['color'] }}"></span>
                        {{ $field['label'] }}
                    </label>
                    <input type="url" name="{{ $field['key'] }}" class="input"
                           placeholder="{{ $field['placeholder'] }}"
                           value="{{ $project->setting($field['key']) }}">
                </div>
                @endforeach
                <div class="pt-2">
                    <button type="submit" class="btn-primary">Guardar redes</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Tienda online ── --}}
    @if($s === 'tienda')
    <div class="flex flex-col h-full">
        <div class="px-6 py-4 border-b border-gray-100 bg-white flex-shrink-0">
            <h2 class="font-semibold text-gray-800">Tienda online</h2>
            <p class="text-xs text-gray-400 mt-0.5">Textos del hero, banners y mensaje del catálogo público</p>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
            <form method="POST" action="{{ route('settings.design.update', ['project'=>$project->id]) }}"
                  class="space-y-6 max-w-lg">
                @csrf
                {{-- Hero --}}
                <div class="bg-gray-50 rounded-xl p-4 space-y-4 border border-gray-200">
                    <p class="text-sm font-semibold text-gray-700">Banner principal (Hero)</p>
                    <div>
                        <label class="label">Título principal</label>
                        <input type="text" name="hero_title" class="input"
                               placeholder="Ej: Bienvenido a nuestra tienda"
                               value="{{ $project->setting('hero_title') }}">
                    </div>
                    <div>
                        <label class="label">Subtítulo</label>
                        <input type="text" name="hero_subtitle" class="input"
                               placeholder="Ej: Encuentra todo lo que necesitas al mejor precio"
                               value="{{ $project->setting('hero_subtitle') }}">
                    </div>
                    <div>
                        <label class="label">Badge / etiqueta</label>
                        <input type="text" name="hero_badge" class="input"
                               placeholder="Ej: ¡Nuevos productos!" value="{{ $project->setting('hero_badge') }}">
                        <p class="text-xs text-gray-400 mt-1">Pequeña etiqueta que aparece sobre el título</p>
                    </div>
                    <div x-data="{ color: '{{ $project->setting('hero_bg_color', '#1e1b4b') }}' }">
                        <label class="label">Color de fondo del hero</label>
                        <div class="flex items-center gap-3 mt-1">
                            <input type="color" name="hero_bg_color" x-model="color"
                                   class="w-14 h-12 rounded-xl cursor-pointer border-2 border-gray-200 p-0.5">
                            <code class="text-sm text-gray-600 font-mono" x-text="color"></code>
                        </div>
                    </div>
                </div>

                {{-- Banners --}}
                <div class="bg-gray-50 rounded-xl p-4 space-y-4 border border-gray-200">
                    <p class="text-sm font-semibold text-gray-700">Banner lateral 1</p>
                    <div>
                        <label class="label">Título</label>
                        <input type="text" name="banner1_title" class="input"
                               placeholder="Ej: Nuevos productos" value="{{ $project->setting('banner1_title') }}">
                    </div>
                    <div>
                        <label class="label">Descripción</label>
                        <input type="text" name="banner1_sub" class="input"
                               placeholder="Ej: Descubre lo último" value="{{ $project->setting('banner1_sub') }}">
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-4 space-y-4 border border-gray-200">
                    <p class="text-sm font-semibold text-gray-700">Banner lateral 2</p>
                    <div>
                        <label class="label">Título</label>
                        <input type="text" name="banner2_title" class="input"
                               placeholder="Ej: Ofertas especiales" value="{{ $project->setting('banner2_title') }}">
                    </div>
                    <div>
                        <label class="label">Descripción</label>
                        <input type="text" name="banner2_sub" class="input"
                               placeholder="Ej: Precios imperdibles" value="{{ $project->setting('banner2_sub') }}">
                    </div>
                </div>

                {{-- Modo de venta --}}
                <div class="bg-gray-50 rounded-xl p-4 space-y-4 border border-gray-200">
                    <div>
                        <p class="text-sm font-semibold text-gray-700">Modo de venta</p>
                        <p class="text-xs text-gray-400 mt-0.5">¿Qué pueden hacer los clientes en tu tienda?</p>
                    </div>
                    @php $storeMode = $project->setting('store_mode', 'direct'); @endphp
                    <label class="flex items-start gap-3 p-3 rounded-xl border-2 cursor-pointer transition
                                  {{ $storeMode === 'direct' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                        <input type="radio" name="store_mode" value="direct"
                               {{ $storeMode === 'direct' ? 'checked' : '' }}
                               class="mt-0.5 accent-indigo-600 flex-shrink-0">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Compra directa</p>
                            <p class="text-xs text-gray-500 mt-0.5">El cliente agrega productos al carrito y realiza un pedido. Se muestra el precio.</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-3 rounded-xl border-2 cursor-pointer transition
                                  {{ $storeMode === 'quote_only' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                        <input type="radio" name="store_mode" value="quote_only"
                               {{ $storeMode === 'quote_only' ? 'checked' : '' }}
                               class="mt-0.5 accent-indigo-600 flex-shrink-0">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Solo cotizaciones</p>
                            <p class="text-xs text-gray-500 mt-0.5">El cliente arma su lista y la envía para cotizar. El precio puede mostrarse como referencial u ocultarse.</p>
                        </div>
                    </label>
                    {{-- Sub-opción: mostrar precio referencial --}}
                    <div class="pl-7 space-y-2" id="quote-price-opts">
                        <p class="text-xs font-semibold text-gray-600">Precio en modo cotización:</p>
                        @php $quotePrice = $project->setting('quote_price_display', 'show'); @endphp
                        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                            <input type="radio" name="quote_price_display" value="show"
                                   {{ $quotePrice === 'show' ? 'checked' : '' }} class="accent-indigo-600">
                            Mostrar precio referencial
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                            <input type="radio" name="quote_price_display" value="hide"
                                   {{ $quotePrice === 'hide' ? 'checked' : '' }} class="accent-indigo-600">
                            Ocultar precio (solo nombre y cantidad)
                        </label>
                    </div>
                </div>

                {{-- WhatsApp para cotizaciones --}}
                <div class="bg-gray-50 rounded-xl p-4 space-y-4 border border-gray-200">
                    <div>
                        <p class="text-sm font-semibold text-gray-700">WhatsApp para cotizaciones</p>
                        <p class="text-xs text-gray-400 mt-0.5">Al confirmar una cotización, el cliente podrá enviar el detalle por WhatsApp</p>
                    </div>
                    @php
                        $savedWaFull    = preg_replace('/\D/', '', $project->setting('quote_whatsapp', preg_replace('/\D/', '', $project->whatsapp ?? '')));
                        $savedCountry   = $project->setting('quote_whatsapp_country', '51');
                        $localWaNumber  = str_starts_with($savedWaFull, $savedCountry) ? substr($savedWaFull, strlen($savedCountry)) : $savedWaFull;
                        $countries = [
                            ['code'=>'51',  'flag'=>'🇵🇪', 'name'=>'Perú'],
                            ['code'=>'1',   'flag'=>'🇺🇸', 'name'=>'USA / Canadá'],
                            ['code'=>'52',  'flag'=>'🇲🇽', 'name'=>'México'],
                            ['code'=>'57',  'flag'=>'🇨🇴', 'name'=>'Colombia'],
                            ['code'=>'56',  'flag'=>'🇨🇱', 'name'=>'Chile'],
                            ['code'=>'54',  'flag'=>'🇦🇷', 'name'=>'Argentina'],
                            ['code'=>'591', 'flag'=>'🇧🇴', 'name'=>'Bolivia'],
                            ['code'=>'593', 'flag'=>'🇪🇨', 'name'=>'Ecuador'],
                            ['code'=>'595', 'flag'=>'🇵🇾', 'name'=>'Paraguay'],
                            ['code'=>'598', 'flag'=>'🇺🇾', 'name'=>'Uruguay'],
                            ['code'=>'58',  'flag'=>'🇻🇪', 'name'=>'Venezuela'],
                            ['code'=>'34',  'flag'=>'🇪🇸', 'name'=>'España'],
                            ['code'=>'55',  'flag'=>'🇧🇷', 'name'=>'Brasil'],
                        ];
                    @endphp
                    <div x-data="{ country: '{{ $savedCountry }}', local: '{{ $localWaNumber }}' }">
                        <label class="label">Número de WhatsApp</label>
                        <div class="flex items-center gap-0 mt-1">
                            <select x-model="country" name="quote_whatsapp_country"
                                    class="border border-r-0 border-gray-300 rounded-l-xl px-2 py-2.5 text-sm bg-gray-50 outline-none focus:border-indigo-400 transition cursor-pointer flex-shrink-0">
                                @foreach($countries as $c)
                                <option value="{{ $c['code'] }}" {{ $savedCountry === $c['code'] ? 'selected' : '' }}>
                                    {{ $c['flag'] }} +{{ $c['code'] }} {{ $c['name'] }}
                                </option>
                                @endforeach
                            </select>
                            <input type="tel" x-model="local"
                                   placeholder="999 888 777"
                                   class="flex-1 border border-gray-300 rounded-r-xl px-3 py-2.5 text-sm outline-none focus:border-indigo-400 transition min-w-0">
                            <input type="hidden" name="quote_whatsapp" :value="country + local.replace(/\D/g,'')">
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Solo el número local, sin espacios ni guiones. Ej: <code class="bg-gray-100 px-1 rounded">955 354 646</code></p>
                    </div>
                    <div>
                        <label class="label">Mensaje plantilla</label>
                        <textarea name="quote_wa_msg" class="input" rows="3"
                                  placeholder="Ej: Hola, me interesa cotizar los siguientes productos:">{{ $project->setting('quote_wa_msg', 'Hola, me interesa cotizar los siguientes productos:') }}</textarea>
                        <p class="text-xs text-gray-400 mt-1">El sistema agregará automáticamente el detalle de productos al final del mensaje.</p>
                    </div>
                </div>

                {{-- Métodos de pago aceptados (solo venta directa) --}}
                <div class="bg-gray-50 rounded-xl p-4 space-y-4 border border-gray-200">
                    <div>
                        <p class="text-sm font-semibold text-gray-700">Métodos de pago aceptados</p>
                        <p class="text-xs text-gray-400 mt-0.5">Se muestran en la tienda cuando el modo es <strong>Compra directa</strong></p>
                    </div>
                    @php
                        $paymentOptions = [
                            ['key'=>'efectivo',      'label'=>'Efectivo',             'icon'=>'💵'],
                            ['key'=>'yape',          'label'=>'Yape',                 'icon'=>'🟣'],
                            ['key'=>'plin',          'label'=>'Plin',                 'icon'=>'🔵'],
                            ['key'=>'transferencia', 'label'=>'Transferencia bancaria','icon'=>'🏦'],
                            ['key'=>'tarjeta',       'label'=>'Tarjeta crédito/débito','icon'=>'💳'],
                            ['key'=>'qr',            'label'=>'Pago con QR',          'icon'=>'📲'],
                            ['key'=>'contra_entrega','label'=>'Contra entrega',        'icon'=>'🚚'],
                        ];
                        $savedPayments = json_decode($project->setting('accepted_payments', '[]'), true) ?? [];
                    @endphp
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($paymentOptions as $pm)
                        <label class="flex items-center gap-2.5 p-3 rounded-xl border-2 cursor-pointer transition
                                      {{ in_array($pm['key'], $savedPayments) ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                            <input type="checkbox" name="accepted_payments[]" value="{{ $pm['key'] }}"
                                   {{ in_array($pm['key'], $savedPayments) ? 'checked' : '' }}
                                   class="accent-indigo-600 w-4 h-4 flex-shrink-0">
                            <span class="text-lg leading-none">{{ $pm['icon'] }}</span>
                            <span class="text-sm text-gray-700 font-medium">{{ $pm['label'] }}</span>
                        </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400">Los íconos de estos métodos aparecerán en el carrito de compras de tus clientes.</p>
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn-primary">Guardar tienda</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── SEO ── --}}
    @if($s === 'seo')
    <div class="flex flex-col h-full">
        <div class="px-6 py-4 border-b border-gray-100 bg-white flex-shrink-0">
            <h2 class="font-semibold text-gray-800">SEO — Posicionamiento en Google</h2>
            <p class="text-xs text-gray-400 mt-0.5">Configura cómo aparece tu catálogo en buscadores como Google</p>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
            <form method="POST" action="{{ route('settings.design.update', ['project'=>$project->id]) }}"
                  class="space-y-5 max-w-lg">
                @csrf

                {{-- Info --}}
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
                    <p class="font-semibold mb-1">¿Por qué es importante el SEO?</p>
                    <p class="text-xs text-blue-600 leading-relaxed">
                        Cuando alguien busca en Google "<em>{{ $project->name }} productos</em>" o busca lo que vendes,
                        un buen SEO hace que tu catálogo aparezca en los primeros resultados. Además, las imágenes de tus
                        productos aparecerán en Google Imágenes con tu negocio como fuente.
                    </p>
                </div>

                <div>
                    <label class="label">Título para buscadores (SEO Title)</label>
                    <input type="text" name="seo_title" class="input"
                           placeholder="Ej: {{ $project->name }} — Catálogo Online"
                           value="{{ $project->setting('seo_title') }}"
                           maxlength="70">
                    <p class="text-xs text-gray-400 mt-1">Máximo 60–70 caracteres. Aparece en la pestaña del navegador y en Google.</p>
                </div>

                <div>
                    <label class="label">Descripción para buscadores (Meta description)</label>
                    <textarea name="seo_description" class="input" rows="3"
                              placeholder="Ej: Explora el catálogo de {{ $project->name }}. Encuentra productos de calidad y haz tu pedido en línea."
                              maxlength="160">{{ $project->setting('seo_description') }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">Máximo 155–160 caracteres. Es el texto que Google muestra bajo el título.</p>
                </div>

                <div>
                    <label class="label">Palabras clave (Keywords)</label>
                    <input type="text" name="seo_keywords" class="input"
                           placeholder="Ej: tienda online, {{ $project->name }}, productos, Lima"
                           value="{{ $project->setting('seo_keywords') }}">
                    <p class="text-xs text-gray-400 mt-1">Separadas por coma. Describe lo que vendes y dónde estás.</p>
                </div>

                {{-- SEO de imágenes --}}
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 space-y-2">
                    <p class="text-sm font-semibold text-green-800">✅ SEO de imágenes (automático)</p>
                    <p class="text-xs text-green-700 leading-relaxed">
                        Cada imagen que subas a tus productos recibe automáticamente:
                    </p>
                    <ul class="text-xs text-green-700 space-y-1 pl-4 list-disc">
                        <li><strong>Alt text SEO:</strong> "[Nombre producto] — [Categoría] en {{ $project->name }}"</li>
                        <li><strong>Title:</strong> nombre del producto</li>
                        <li><strong>Lazy loading:</strong> carga rápida para mejor ranking</li>
                        <li><strong>Dimensiones explícitas:</strong> evita layout shift (CLS)</li>
                        <li><strong>Schema.org Product:</strong> datos estructurados para Google Shopping</li>
                    </ul>
                </div>

                {{-- JSON-LD --}}
                <div class="bg-purple-50 border border-purple-200 rounded-xl p-4 space-y-2">
                    <p class="text-sm font-semibold text-purple-800">🔍 Rich Results (automático)</p>
                    <p class="text-xs text-purple-700 leading-relaxed">
                        Tu catálogo ya incluye datos estructurados JSON-LD que permiten a Google mostrar:
                    </p>
                    <ul class="text-xs text-purple-700 space-y-1 pl-4 list-disc">
                        <li>Precios y disponibilidad directamente en resultados de búsqueda</li>
                        <li>Información del negocio (nombre, dirección, teléfono, redes)</li>
                        <li>Lista de productos con imágenes en Google Shopping</li>
                        <li>Open Graph para compartir en WhatsApp, Facebook, Twitter</li>
                    </ul>
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn-primary">Guardar SEO</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Pagos en línea ── --}}
    @if($s === 'payments')
    <div class="flex flex-col h-full">
        <div class="px-6 py-4 border-b border-gray-100 bg-white flex-shrink-0">
            <h2 class="font-semibold text-gray-800">Pagos en línea</h2>
            <p class="text-xs text-gray-400 mt-0.5">Configura cómo tus clientes pagan desde el catálogo</p>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
        <form method="POST" action="{{ route('settings.design.update', ['project'=>$project->id]) }}"
              class="space-y-6 max-w-lg">
            @csrf

            {{-- INFO GENERAL --}}
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
                <p class="font-semibold mb-1">¿Cómo funciona?</p>
                <p class="text-xs text-blue-600 leading-relaxed">
                    Activa uno o ambos modos. El cliente verá las opciones disponibles después de confirmar su pedido.
                    Los pagos manuales requieren que <strong>verifiques el comprobante</strong>.
                    Con pasarela, el cobro es automático.
                </p>
            </div>

            {{-- ══ MODO MANUAL ══ --}}
            @php $manualEnabled = $project->setting('payment_manual_enabled', '0') === '1'; @endphp
            <div x-data="{ open: {{ $manualEnabled ? 'true' : 'false' }} }" class="border-2 rounded-2xl overflow-hidden
                          {{ $manualEnabled ? 'border-indigo-400' : 'border-gray-200' }}">
                <label class="flex items-center gap-3 px-5 py-4 cursor-pointer bg-white">
                    <input type="checkbox" name="payment_manual_enabled" value="1" @change="open=$el.checked"
                           {{ $manualEnabled ? 'checked' : '' }}
                           class="w-5 h-5 rounded accent-indigo-600 flex-shrink-0">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800">Pagos manuales</p>
                        <p class="text-xs text-gray-500">Yape, Plin, transferencia bancaria — el cliente sube su número de operación</p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                 {{ $manualEnabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $manualEnabled ? 'Activo' : 'Inactivo' }}
                    </span>
                </label>
                <div x-show="open" class="border-t border-gray-100 bg-gray-50 p-5 space-y-4">

                    {{-- Métodos activos --}}
                    @php
                        $manualMethods = [
                            ['key'=>'yape',          'label'=>'Yape',                  'color'=>'#7c3aed', 'emoji'=>'🟣'],
                            ['key'=>'plin',          'label'=>'Plin',                  'color'=>'#0284c7', 'emoji'=>'🔵'],
                            ['key'=>'transferencia', 'label'=>'Transferencia bancaria', 'color'=>'#0891b2', 'emoji'=>'🏦'],
                            ['key'=>'qr',            'label'=>'Pago con QR genérico',  'color'=>'#059669', 'emoji'=>'📲'],
                            ['key'=>'contra_entrega','label'=>'Contra entrega',         'color'=>'#b45309', 'emoji'=>'🚚'],
                        ];
                        $savedManual = json_decode($project->setting('payment_manual_methods', '["yape","plin"]'), true) ?? [];
                    @endphp
                    <div>
                        <p class="text-xs font-semibold text-gray-700 mb-2">Métodos disponibles</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($manualMethods as $mm)
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer text-sm
                                          {{ in_array($mm['key'], $savedManual) ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 bg-white' }}">
                                <input type="checkbox" name="payment_manual_methods[]" value="{{ $mm['key'] }}"
                                       {{ in_array($mm['key'], $savedManual) ? 'checked' : '' }}
                                       class="accent-indigo-600 flex-shrink-0">
                                <span>{{ $mm['emoji'] }}</span>
                                <span class="text-gray-700 font-medium">{{ $mm['label'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Datos de cuenta por método --}}
                    <div class="space-y-3">
                        <p class="text-xs font-semibold text-gray-700">Datos de tu cuenta (el cliente los verá al pagar)</p>

                        <div>
                            <label class="label text-xs">Yape — Número o nombre</label>
                            <input type="text" name="payment_yape_number" class="input text-sm"
                                   placeholder="Ej: 955 354 646 — Juan García"
                                   value="{{ $project->setting('payment_yape_number') }}">
                        </div>
                        <div>
                            <label class="label text-xs">Plin — Número o nombre</label>
                            <input type="text" name="payment_plin_number" class="input text-sm"
                                   placeholder="Ej: 955 354 646 — Juan García"
                                   value="{{ $project->setting('payment_plin_number') }}">
                        </div>
                        <div>
                            <label class="label text-xs">Transferencia bancaria — Datos de cuenta</label>
                            <textarea name="payment_bank_details" class="input text-sm" rows="3"
                                      placeholder="Ej: BCP — Cta Corriente: 123-456789-0-01&#10;CCI: 002-123-456789-01">{{ $project->setting('payment_bank_details') }}</textarea>
                        </div>
                        <div>
                            <label class="label text-xs">Instrucciones adicionales (opcional)</label>
                            <input type="text" name="payment_manual_instructions" class="input text-sm"
                                   placeholder="Ej: Envía tu comprobante al WhatsApp luego de pagar"
                                   value="{{ $project->setting('payment_manual_instructions') }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══ CULQI ══ --}}
            @php $culqiEnabled = $project->setting('culqi_enabled', '0') === '1'; @endphp
            <div x-data="{ open: {{ $culqiEnabled ? 'true' : 'false' }} }" class="border-2 rounded-2xl overflow-hidden
                          {{ $culqiEnabled ? 'border-indigo-400' : 'border-gray-200' }}">
                <label class="flex items-center gap-3 px-5 py-4 cursor-pointer bg-white">
                    <input type="checkbox" name="culqi_enabled" value="1" @change="open=$el.checked"
                           {{ $culqiEnabled ? 'checked' : '' }}
                           class="w-5 h-5 rounded accent-indigo-600 flex-shrink-0">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-800">Culqi</p>
                            <span class="text-[10px] bg-orange-100 text-orange-700 font-bold px-1.5 py-0.5 rounded-full">Recomendado Perú</span>
                        </div>
                        <p class="text-xs text-gray-500">Tarjetas Visa/Mastercard + Yape con cobro automático. ~3.5% por transacción</p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                 {{ $culqiEnabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $culqiEnabled ? 'Activo' : 'Inactivo' }}
                    </span>
                </label>
                <div x-show="open" class="border-t border-gray-100 bg-gray-50 p-5 space-y-4">
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-700">
                        <p class="font-semibold mb-1">¿Cómo obtener tus llaves?</p>
                        <ol class="list-decimal pl-4 space-y-1 leading-relaxed">
                            <li>Regístrate en <strong>culqi.com</strong> como comercio</li>
                            <li>En tu panel → Desarrollo → API Keys</li>
                            <li>Copia tu <strong>Llave pública</strong> (pk_...) y <strong>Llave secreta</strong> (sk_...)</li>
                            <li>Para pruebas usa las llaves de <strong>Sandbox</strong></li>
                        </ol>
                    </div>
                    <div>
                        <label class="label text-xs">Llave pública (pk_test_... o pk_live_...)</label>
                        <input type="text" name="culqi_public_key" class="input text-sm font-mono"
                               placeholder="pk_test_xxxxxxxxxxxxx"
                               value="{{ $project->setting('culqi_public_key') }}">
                    </div>
                    <div>
                        <label class="label text-xs">Llave secreta (sk_test_... o sk_live_...)</label>
                        <input type="password" name="culqi_secret_key" class="input text-sm font-mono"
                               placeholder="sk_test_xxxxxxxxxxxxx"
                               value="{{ $project->setting('culqi_secret_key') ? '••••••••••••••••' : '' }}">
                        <p class="text-xs text-gray-400 mt-1">Se guarda encriptada. Nunca se muestra completa.</p>
                    </div>
                    <div x-data="{ mode: '{{ $project->setting('culqi_mode', 'test') }}' }">
                        <label class="label text-xs">Modo</label>
                        <div class="flex gap-3 mt-1">
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="radio" name="culqi_mode" value="test" x-model="mode" class="accent-indigo-600">
                                <span class="text-yellow-700 font-medium">🧪 Pruebas (sandbox)</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="radio" name="culqi_mode" value="live" x-model="mode" class="accent-indigo-600">
                                <span class="text-green-700 font-medium">🚀 Producción</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══ MERCADO PAGO ══ --}}
            @php $mpEnabled = $project->setting('mp_enabled', '0') === '1'; @endphp
            <div x-data="{ open: {{ $mpEnabled ? 'true' : 'false' }} }" class="border-2 rounded-2xl overflow-hidden
                          {{ $mpEnabled ? 'border-indigo-400' : 'border-gray-200' }}">
                <label class="flex items-center gap-3 px-5 py-4 cursor-pointer bg-white">
                    <input type="checkbox" name="mp_enabled" value="1" @change="open=$el.checked"
                           {{ $mpEnabled ? 'checked' : '' }}
                           class="w-5 h-5 rounded accent-indigo-600 flex-shrink-0">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-800">Mercado Pago</p>
                            <span class="text-[10px] bg-cyan-100 text-cyan-700 font-bold px-1.5 py-0.5 rounded-full">Multi-país LATAM</span>
                        </div>
                        <p class="text-xs text-gray-500">Tarjetas, wallets, cuotas. Disponible en Perú, México, Colombia, Chile, Argentina y más</p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                 {{ $mpEnabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $mpEnabled ? 'Activo' : 'Inactivo' }}
                    </span>
                </label>
                <div x-show="open" class="border-t border-gray-100 bg-gray-50 p-5 space-y-4">
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-700">
                        <p class="font-semibold mb-1">¿Cómo obtener tu Access Token?</p>
                        <ol class="list-decimal pl-4 space-y-1 leading-relaxed">
                            <li>Entra a <strong>mercadopago.com.pe</strong> → Tu cuenta → Panel de desarrolladores</li>
                            <li>Crea una aplicación nueva</li>
                            <li>En Credenciales → copia el <strong>Access Token</strong> de producción o pruebas</li>
                        </ol>
                    </div>
                    <div>
                        <label class="label text-xs">Access Token (APP_USR-... o TEST-...)</label>
                        <input type="password" name="mp_access_token" class="input text-sm font-mono"
                               placeholder="APP_USR-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                               value="{{ $project->setting('mp_access_token') ? '••••••••••••••••' : '' }}">
                        <p class="text-xs text-gray-400 mt-1">Usa TEST-... para pruebas y APP_USR-... para producción.</p>
                    </div>
                </div>
            </div>

            {{-- Nota multi-país --}}
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-xs text-gray-500 flex gap-2">
                <span class="text-lg leading-none">🌎</span>
                <div>
                    <p class="font-medium text-gray-600 mb-1">Expansión internacional</p>
                    <p class="leading-relaxed">Mercado Pago está disponible en Perú, México, Colombia, Chile, Argentina, Brasil y más.
                    Para otros países puedes integrar pasarelas adicionales como <strong>Stripe</strong> (global) o
                    <strong>PayU</strong> (LATAM) contactando a soporte AVAN.</p>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-primary">Guardar configuración de pagos</button>
            </div>
        </form>
        </div>
    </div>
    @endif

    {{-- ── Página pública ── --}}
    @if($s === 'public')
    <div class="flex flex-col h-full">
        <div class="px-6 py-4 border-b border-gray-100 bg-white flex-shrink-0">
            <h2 class="font-semibold text-gray-800">Página pública</h2>
            <p class="text-xs text-gray-400 mt-0.5">Textos y configuración del catálogo online</p>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
            <div class="max-w-lg space-y-5">
                {{-- URL --}}
                <div class="bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-xl p-5">
                    <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wide mb-2">Tu URL pública</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 text-sm text-indigo-800 bg-white border border-indigo-200 rounded-lg px-3 py-2 truncate font-mono">
                            {{ url('/p/' . $project->slug) }}
                        </code>
                        <a href="{{ url('/p/' . $project->slug) }}" target="_blank"
                           class="btn-primary text-xs px-3 py-2 whitespace-nowrap">Abrir ↗</a>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white border border-gray-200 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-gray-800">{{ $project->products()->where('is_available', true)->count() }}</p>
                        <p class="text-xs text-gray-500 mt-1">Productos publicados</p>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-gray-800">{{ $project->services()->where('is_available', true)->count() }}</p>
                        <p class="text-xs text-gray-500 mt-1">Servicios publicados</p>
                    </div>
                </div>

                {{-- Capacidades --}}
                <div class="bg-white border border-gray-200 rounded-xl p-4 space-y-2">
                    <p class="text-sm font-semibold text-gray-700 mb-3">¿Qué pueden hacer tus clientes?</p>
                    @foreach(['Ver productos y servicios del catálogo','Hacer pedidos con carrito de compras','Reservar citas directamente','Contactar por WhatsApp'] as $cap)
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ $cap }}
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Pantalla de Login ── --}}
    @if($s === 'login')
    <div class="flex flex-col h-full">
        <div class="px-6 py-4 border-b border-gray-100 bg-white flex-shrink-0">
            <h2 class="font-semibold text-gray-800">Pantalla de login</h2>
            <p class="text-xs text-gray-400 mt-0.5">Personaliza el fondo y el mensaje de bienvenida</p>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
            <form method="POST" action="{{ route('settings.design.update', ['project'=>$project->id]) }}?s=login"
                  class="space-y-6 max-w-lg"
                  x-data="{
                      bgType: '{{ $project->setting('login_bg_type', 'gradient') }}',
                      color1: '{{ $project->setting('login_color1', '#4f46e5') }}',
                      color2: '{{ $project->setting('login_color2', '#7c3aed') }}'
                  }">
                @csrf

                {{-- Tipo de fondo --}}
                <div>
                    <label class="label">Tipo de fondo</label>
                    <div class="grid grid-cols-3 gap-3 mt-2">
                        @foreach([
                            ['val'=>'gradient','label'=>'Degradado',  'icon'=>'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                            ['val'=>'solid',   'label'=>'Color sólido','icon'=>'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'],
                            ['val'=>'image',   'label'=>'Imagen URL',  'icon'=>'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14'],
                        ] as $opt)
                        <label class="flex flex-col items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all"
                               :class="bgType==='{{ $opt['val'] }}' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" name="login_bg_type" value="{{ $opt['val'] }}"
                                   x-model="bgType" class="sr-only">
                            <svg class="w-6 h-6" :class="bgType==='{{ $opt['val'] }}' ? 'text-indigo-600' : 'text-gray-400'"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $opt['icon'] }}"/>
                            </svg>
                            <span class="text-xs font-medium" :class="bgType==='{{ $opt['val'] }}' ? 'text-indigo-700' : 'text-gray-500'">
                                {{ $opt['label'] }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Colores del degradado --}}
                <div x-show="bgType === 'gradient'" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Color inicial</label>
                            <div class="flex items-center gap-2 mt-1">
                                <input type="color" name="login_color1" x-model="color1"
                                       class="w-12 h-10 rounded-lg cursor-pointer border border-gray-200 p-0.5">
                                <code class="text-sm text-gray-600 font-mono" x-text="color1"></code>
                            </div>
                        </div>
                        <div>
                            <label class="label">Color final</label>
                            <div class="flex items-center gap-2 mt-1">
                                <input type="color" name="login_color2" x-model="color2"
                                       class="w-12 h-10 rounded-lg cursor-pointer border border-gray-200 p-0.5">
                                <code class="text-sm text-gray-600 font-mono" x-text="color2"></code>
                            </div>
                        </div>
                    </div>
                    {{-- Preview degradado --}}
                    <div class="h-16 rounded-xl border border-gray-200 transition-all"
                         :style="'background: linear-gradient(135deg, ' + color1 + ' 0%, ' + color2 + ' 100%)'"></div>
                </div>

                {{-- Color sólido --}}
                <div x-show="bgType === 'solid'" class="space-y-2">
                    <label class="label">Color de fondo</label>
                    <div class="flex items-center gap-3">
                        <input type="color" name="login_color1" x-model="color1"
                               class="w-14 h-12 rounded-xl cursor-pointer border-2 border-gray-200 p-0.5">
                        <div>
                            <code class="text-sm text-gray-700 font-mono" x-text="color1"></code>
                            <p class="text-xs text-gray-400 mt-0.5">Se usa como fondo sólido</p>
                        </div>
                    </div>
                    <div class="h-16 rounded-xl border border-gray-200 transition-all"
                         :style="'background:' + color1"></div>
                </div>

                {{-- URL de imagen --}}
                <div x-show="bgType === 'image'">
                    <label class="label">URL de la imagen de fondo</label>
                    <input type="url" name="login_bg_image" class="input mt-1"
                           placeholder="https://images.unsplash.com/photo-..."
                           value="{{ $project->setting('login_bg_image') }}">
                    <p class="text-xs text-gray-400 mt-1">Usa una imagen de alta resolución (mínimo 1920×1080). Puedes usar Unsplash.</p>
                </div>

                {{-- Textos --}}
                <div class="border-t border-gray-100 pt-5 space-y-4">
                    <p class="text-sm font-semibold text-gray-700">Texto del panel izquierdo</p>

                    <div>
                        <label class="label">Título de bienvenida</label>
                        <input type="text" name="login_heading" class="input mt-1"
                               placeholder="Bienvenido de vuelta"
                               value="{{ $project->setting('login_heading', 'Bienvenido de vuelta') }}">
                    </div>

                    <div>
                        <label class="label">Subtítulo</label>
                        <input type="text" name="login_subtitle" class="input mt-1"
                               placeholder="Ingresa a tu panel de gestión"
                               value="{{ $project->setting('login_subtitle', 'Ingresa a tu panel de gestión') }}">
                    </div>
                </div>

                <div class="pt-2 flex items-center gap-3">
                    <button type="submit" class="btn-primary">Guardar cambios</button>
                    <a href="{{ route('login') }}" target="_blank"
                       class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Ver login
                    </a>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
</div>
</x-slot>
</x-app-layout>
