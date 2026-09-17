{{-- SHELL COMPONIBLE DE ENCABEZADO — un solo archivo arma estructuras distintas
     según la composición declarada en HeaderPresets::PRESETS[key]['shell']:
       topbar (true|false|'b2b'), logo (left|center), search (wide|hero|icon|takeover),
       nav_row, nav_inline, nav_align, cat_trigger (mega|visual|none),
       universes_row (top|below), actions_cta, tall|low, search_panel.
     Reutiliza las mismas variables compartidas y el Alpine del body que los
     headers existentes (query/fetchSuggest/mobileNav/openCartPage/itemCount). --}}
@php
    $sh = $hpPreset['shell'] ?? [];
    $shLogo = $sh['logo'] ?? 'left';
    $shSearch = $sh['search'] ?? 'wide';
    $shNavRow = (bool) ($sh['nav_row'] ?? true);
    $shNavInline = (bool) ($sh['nav_inline'] ?? false);
    $shNavAlign = $sh['nav_align'] ?? 'left';
    // El desplegable de categorias venia fijado por el preset. Ahora es un ajuste
    // del constructor (hp_cat_trigger) y el preset solo aporta el valor por defecto,
    // de modo que una tienda con muchas subcategorias pueda activarlo sin cambiar
    // de preset ni tocar codigo. Sin ajuste, comportamiento identico al anterior.
    $shCatTrigger = in_array($hp('cat_trigger'), ['mega', 'editorial', 'visual', 'lista', 'none'], true)
        ? $hp('cat_trigger')
        : ($sh['cat_trigger'] ?? 'none');
    // Posicion del boton de categorias (constructor, comun a todos los
    // layouts). Oculto equivale a no tener desplegable: sin boton no hay
    // forma de abrirlo, y un panel inalcanzable solo confunde.
    $shCatsPos = in_array($hp('cats_pos'), ['izquierda', 'derecha', 'oculto'], true) ? $hp('cats_pos') : 'izquierda';
    if ($shCatsPos === 'oculto' && in_array($shCatTrigger, ['mega', 'lista'], true)) $shCatTrigger = 'none';
    $shUniRow = $sh['universes_row'] ?? null;
    $shWa = preg_replace('/\D/', '', (string) ($settings['quote_whatsapp'] ?? ''));
    if ($shWa && !str_starts_with($shWa, '51')) $shWa = '51' . $shWa;
    $shellHandledB2b = ($sh['topbar'] ?? null) === 'b2b';
    $shellHandledUniverses = $shUniRow !== null;
    // El filtro lateral envia category[]=ID (array); se toma el primero para
    // marcar el enlace activo sin convertir un array a texto.
    $currentCategory = \App\Support\StorefrontNavigation::currentCategoryId();
    $shUniProfiles = !empty($catalogProfiles) && $catalogProfiles->count();
    // Sin perfiles, las categorías raíz solo actúan de universos si el negocio lo permite
    // (no toda categoría técnica es semánticamente un "universo").
    $shUniUseCats = (string) $hp('multiverse_use_categories', '1') !== '0';
    // Alcance de los iconos del menú: en ningún sitio, solo móvil, solo
    // escritorio o ambos. Por defecto 'no', para que ninguna tienda cambie
    // sola: el que los quiera, los enciende.
    $shMenuIcons = in_array($hp('menu_icons', 'no'), ['no','movil','escritorio','ambos'], true)
        ? $hp('menu_icons', 'no') : 'no';
    // Disposición del panel de categorías: la rejilla de siempre, o la columna
    // lateral con las subcategorías abriéndose al lado.
    $shMegaLayout = in_array($hp('mega_layout', 'rejilla'), ['rejilla','lateral'], true)
        ? $hp('mega_layout', 'rejilla') : 'rejilla';
    $shUniItems = $shUniProfiles ? $catalogProfiles : ($shUniUseCats ? ($navCategories ?? collect())->take(6) : collect());
@endphp
@php
                // Iconos propios: $svgIcons se define mas abajo en la plantilla y aqui aun no existe.
                $shSocialPaths = [
                    'facebook'  => '<path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H8v3h2v7h3v-7h3l1-3h-4v-2c0-.6.4-1 1-1Z"/>',
                    'instagram' => '<path d="M12 8.6A3.4 3.4 0 1 0 12 15.4 3.4 3.4 0 0 0 12 8.6Zm0 5.6a2.2 2.2 0 1 1 0-4.4 2.2 2.2 0 0 1 0 4.4Z"/><path d="M17 2H7a5 5 0 0 0-5 5v10a5 5 0 0 0 5 5h10a5 5 0 0 0 5-5V7a5 5 0 0 0-5-5Zm3.6 15a3.6 3.6 0 0 1-3.6 3.6H7A3.6 3.6 0 0 1 3.4 17V7A3.6 3.6 0 0 1 7 3.4h10A3.6 3.6 0 0 1 20.6 7v10Z"/><circle cx="17.2" cy="6.8" r="1"/>',
                    'tiktok'    => '<path d="M16.5 2h-2.7v13.1a2.4 2.4 0 1 1-2-2.4V10a5.4 5.4 0 1 0 4.7 5.3V8.7c.9.7 2.1 1.1 3.3 1.1V7.1c-1.9 0-3.3-1.6-3.3-3.4V2Z"/>',
                    'youtube'   => '<path d="M22 12s0-3.2-.4-4.7a2.5 2.5 0 0 0-1.7-1.7C18.3 5.2 12 5.2 12 5.2s-6.3 0-7.9.4A2.5 2.5 0 0 0 2.4 7.3C2 8.8 2 12 2 12s0 3.2.4 4.7c.2.9.9 1.5 1.7 1.7 1.6.4 7.9.4 7.9.4s6.3 0 7.9-.4a2.5 2.5 0 0 0 1.7-1.7C22 15.2 22 12 22 12ZM10 15.1V8.9l5.2 3.1-5.2 3.1Z"/>',
                    'linkedin'  => '<path d="M6.9 8.5H4V20h2.9V8.5ZM5.4 4a1.7 1.7 0 1 0 0 3.4 1.7 1.7 0 0 0 0-3.4ZM20 13.2c0-3-1.6-4.4-3.7-4.4-1.7 0-2.5.9-2.9 1.6V8.5H10.5V20h2.9v-6.3c0-1.3.7-2.1 1.8-2.1s1.9.8 1.9 2.1V20H20v-6.8Z"/>',
                ];
                $shSocialSvg = static fn (string $k) => '<svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true">'.($shSocialPaths[$k] ?? $shSocialPaths['facebook']).'</svg>';
@endphp


{{-- FILA 0a: barra superior. Con hp_topbar_items configurado se renderiza la
     versión rica (hasta 3 mensajes con ícono + accesos a la derecha); si no,
     el anuncio simple de siempre. Cada ítem vacío desaparece sin dejar hueco. --}}
@php
    $shTopIcons = [
        'truck'  => '<path d="M1 4h14v12H1z"/><path d="M15 9h4l4 4v3h-8"/><circle cx="6" cy="18.5" r="1.8"/><circle cx="18.5" cy="18.5" r="1.8"/>',
        'shield' => '<path d="M12 2 4 5v6c0 5 3.4 9.4 8 11 4.6-1.6 8-6 8-11V5l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
        'card'   => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
        'support'=> '<path d="M4 13a8 8 0 0 1 16 0"/><rect x="2" y="13" width="4" height="7" rx="1.6"/><rect x="18" y="13" width="4" height="7" rx="1.6"/>',
        'user'   => '<circle cx="12" cy="8" r="3.6"/><path d="M4.5 20c1-3.6 3.9-5.6 7.5-5.6s6.5 2 7.5 5.6"/>',
        'help'   => '<circle cx="12" cy="12" r="9"/><path d="M9.6 9.2a2.5 2.5 0 1 1 3.2 2.6c-.6.2-.9.8-.9 1.5"/><circle cx="12" cy="17" r=".8" fill="currentColor"/>',
        'check'  => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.6 2.6L16.5 9"/>',
        'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
    ];
    $shTopSvg = fn ($k) => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($shTopIcons[$k] ?? $shTopIcons['check']) . '</svg>';
    $shTopItems = collect([1, 2, 3])->map(fn ($n) => [
        'text' => trim((string) $hp("topbar_text_{$n}", '')),
        'icon' => $hp("topbar_icon_{$n}", ['truck', 'shield', 'card'][$n - 1]),
    ])->filter(fn ($it) => $it['text'] !== '')->values();
    $shTopRich = ($sh['topbar'] ?? false) === true && $shTopItems->isNotEmpty();
    // "Ayuda": enlace configurado o la página de Contacto de la tienda.
    // $contactUrl solo existe más abajo (footer), así que se resuelve aquí.
    $shTopHelpUrl = trim((string) $hp('topbar_help_url', ''))
        ?: \App\Support\StorefrontNavigation::resolveUrl($project, new \App\Models\StoreMenuItem(['destination_type' => 'contact']));
@endphp
@if($shTopRich)
<div class="topbar hpx-topbar {{ $headerStickyMode === 'all' ? 'hpx-st-top' : '' }} {{ $hp('topbar_social_style', 'circulo') === 'plano' ? 'soc-plano' : '' }}" style="background:{{ $announcementBg }};color:{{ $announcementColor }};--soc-size:{{ max(14, min(40, (int) $hp('topbar_social_size', 26))) }}px;--soc-gap:{{ max(4, min(28, (int) $hp('topbar_social_gap', 10))) }}px">
    <div class="container hpx-topbar-inner">
        <div class="hpx-topbar-left">
            @foreach($shTopItems as $it)
            <span class="hpx-topbar-item">{!! $shTopSvg($it['icon']) !!}{{ $it['text'] }}</span>
            @endforeach
        </div>
        @if(filled($shTopHelpUrl))
        <div class="hpx-topbar-right">
            @if(!empty($social) && (string) $hp('topbar_social', '1') !== '0')
            {{-- Redes en la cabecera: usa los enlaces ya configurados; sin datos no se pinta --}}
            <span class="hpx-topbar-social">
                @foreach($social as $net => $url)
                <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $net }}" title="{{ $net }}">{!! $shSocialSvg(strtolower($net)) !!}</a>
                @endforeach
            </span>
            @endif
            <a href="{{ $shTopHelpUrl }}">{!! $shTopSvg('help') !!}{{ $hp('topbar_help_text', 'Ayuda') }}</a>
        </div>
        @endif
    </div>
</div>
@elseif(($sh['topbar'] ?? false) === true && $announcementShow)
<div class="topbar topbar--{{ $announcementAlign }} {{ $headerStickyMode === 'all' ? 'hpx-st-top' : '' }}" style="background:{{ $announcementBg }};color:{{ $announcementColor }};font-size:{{ $announcementFontSize }}px">
    <div class="{{ $announcementFull ? 'topbar-full' : 'container' }} topbar-inner">
        <p style="color:{{ $announcementColor }}">{{ $announcementText ?: 'Atención especializada para tu compra' }}</p>
        @if(!empty($social) && (string) $hp('topbar_social', '1') !== '0')
        {{-- Redes a la derecha de la barra superior (usa los enlaces configurados) --}}
        <span class="hpx-topbar-social hpx-topbar-social--simple" style="color:{{ $announcementColor }}">
            @foreach($social as $net => $url)
            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $net }}" title="{{ $net }}" style="color:{{ $announcementColor }}">{!! $shSocialSvg(strtolower($net)) !!}</a>
            @endforeach
        </span>
        @endif
        @if($announcementAlign !== 'center')
        <div class="contact-links">@if($phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" style="color:{{ $announcementColor }}">{{ $phone }}</a>@endif</div>
        @endif
    </div>
</div>
@endif

{{-- FILA 0b: barra corporativa B2B (Comercial) --}}
@php $shWholesaleOn = (string) ($settings['wholesale_enabled'] ?? '1') !== '0' && (string) $hp('commercial_show_wholesale', '1') !== '0'; @endphp
@if($shellHandledB2b)
<div class="hpx-corp {{ $headerStickyMode === 'all' ? 'hpx-st-top' : '' }}">
    <div class="container hpx-corp-inner">
        @if($shWholesaleOn)
        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7l-8-4-8 4v10l8 4 8-4V7Z"/><path d="M4 7l8 4 8-4M12 11v10"/></svg> {{ $hp('commercial_wholesale_text', 'Venta mayorista · precios especiales por volumen') }}</span>
        <span class="hpx-corp-sep">·</span>
        @endif
        <span>Asesoría comercial dedicada</span>
        @if($phone)<span class="hpx-corp-sep">·</span><a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a>@endif
        @if(!empty($social) && (string) $hp('topbar_social', '1') !== '0')
        {{-- Redes oficiales a la derecha de la barra corporativa --}}
        <span class="hpx-topbar-social hpx-corp-social">
            @foreach($social as $net => $url)
            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $net }}" title="{{ $net }}" style="color:#fff">{!! $shSocialSvg(strtolower($net)) !!}</a>
            @endforeach
        </span>
        @endif
    </div>
</div>
@endif

{{-- FILA 0c: selector de universo protagonista (Multiuniverso) --}}
@if($shUniRow === 'top' && $shUniItems->count())
<div class="hpx-uni-top">
    <div class="container hpx-uni-top-inner">
        {{-- El rotulo ocupaba una fila entera para no decir nada. La barra pasa a
             ser conmutador de mundo + un dato comercial configurable. --}}
        @php $uniNote = trim((string) ($settings['uni_top_note'] ?? '')); @endphp
        <a class="hpx-uni-chip {{ empty($activeProfile) && !$currentCategory ? 'is-active' : '' }}" href="{{ \App\Support\StorefrontNavigation::shopUrl($project) }}">General</a>
        @if($shUniProfiles)
            @foreach($catalogProfiles as $cp)
            <a class="hpx-uni-chip {{ (!empty($activeProfile) && $activeProfile->id === $cp->id) ? 'is-active' : '' }}"
               @if($cp->primary_color) style="--chip-color:{{ $cp->primary_color }}" @endif
               href="{{ \App\Support\StorefrontNavigation::profileUrl($project, $cp->slug) }}">{{ $cp->menu_label ?: $cp->name }}</a>
            @endforeach
        @else
            @foreach($shUniItems as $uc)
            <a class="hpx-uni-chip {{ (string) $currentCategory === (string) $uc->id ? 'is-active' : '' }}" href="{{ $shopBase }}?category={{ $uc->id }}">{{ $uc->name }}</a>
            @endforeach
        @endif
        @if($uniNote !== '')<span class="hpx-uni-note">{{ $uniNote }}</span>@endif
    </div>
</div>
@endif

<div class="header-zone hz-style-{{ $headerStyle }} hpx hpx-{{ str_replace('_','-',$hpKey) }} {{ !empty($sh['tall']) ? 'hpx-tall' : '' }} {{ !empty($sh['low']) ? 'hpx-low' : '' }} {{ $headerStickyMode === 'all' ? 'is-sticky hpx-st-all' : '' }} {{ $headerStickyMode === 'header' ? 'hpx-st-header' : '' }} {{ $headerStickyMode === 'menu' ? 'hpx-st-menu' : '' }}">
<header class="store-header">
    <div class="container hpx-main hpx-logo-{{ $shLogo }} hpx-search-{{ $shSearch }}">
        <button class="header-hamburger" type="button" @click="mobileNav=true" aria-label="Abrir menú">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>

        @if($shLogo === 'center')
        <div class="hpx-aux">
            @if($menuRoots->count())@foreach($menuRoots->take(2) as $auxItem)<a href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, $auxItem) }}">{{ $auxItem->label }}</a>@endforeach @endif
        </div>
        @endif

        <a class="brand {{ $logoUrl ? 'brand--logo-only' : 'brand--text' }}" href="{{ \App\Support\StorefrontNavigation::homeUrl($project) }}" aria-label="Inicio de {{ $storeName }}">
            @if($logoUrl)<img class="brand-logo" src="{{ $logoUrl }}" alt="Logo de {{ $storeName }}">@if(($settings['logo_wordmark'] ?? '0') === '1')<span class="brand-wordmark">{{ $settings['logo_wordmark_text'] ?? $storeName }}</span>@endif
            @else<span class="brand-mark">{{ mb_strtoupper(mb_substr($storeName, 0, 1)) }}</span><span class="brand-copy"><span class="brand-name">{{ $storeName }}</span></span>@endif
        </a>

        {{-- Navegación inline (Minimal: una sola fila) --}}
        @if($shNavInline && $menuRoots->count())
        <nav class="hpx-inline-nav" aria-label="Navegación">
            @foreach($menuRoots->take(5) as $mItem)
            <a href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, $mItem) }}">{{ $mItem->label }}</a>
            @endforeach
        </nav>
        @endif

        @if($shLogo !== 'center')
            @php /* El selector del buscador NO filtra la busqueda: solo enlaza a la
                   categoria, y duplica el boton "Categorias" de la barra inferior.
                   Por eso viene apagado; se puede reactivar con hp_search_cats=1. */
               $shSearchCats = (string) $hp('search_cats', '0') === '1'
                   && ($sh['search'] ?? '') !== 'icon' && ($navCategories ?? collect())->count() > 0; @endphp
            <div class="hpx-searchbox {{ $shSearchCats ? 'has-cats' : '' }}">
                @include('storefront.partials.nav.search-box')
                @if($shSearchCats)
                <div class="hpx-searchcats" x-data="{ o:false }" @click.outside="o=false">
                    <button type="button" @click="o=!o" :aria-expanded="o">
                        <span>{{ $hp('search_cats_text', 'Todas las categorías') }}</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :style="o && 'transform:rotate(180deg)'"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div class="hpx-searchcats-menu" x-show="o" x-cloak x-transition.opacity>
                        <a href="{{ $shopBase }}">Todas las categorías</a>
                        @foreach($navCategories->take(10) as $sc)
                        <a href="{{ $shopBase }}?category={{ $sc->id }}">{{ $sc->name }}</a>
                        @endforeach
                    </div>
                </div>
                @endif
                <button type="button" class="hpx-searchgo" @click="goSearch()" aria-label="Buscar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                </button>
            </div>
        @endif

        <div class="header-actions hpx-actions">
            @if($shLogo === 'center')
                @include('storefront.partials.nav.search-box')
            @endif
            <button class="header-search-toggle" type="button" @click="searchOpen=!searchOpen" aria-label="Buscar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg></button>
            @php
                // El preset define CAPACIDAD; Ventas y operación define QUÉ está activo.
                $shQuoteActive = in_array(($settings['store_mode'] ?? 'direct'), ['quote', 'quote_only'], true)
                    || in_array(($settings['product_button_mode'] ?? 'cart'), ['both', 'inquiry'], true);
            @endphp
            @if(!empty($sh['actions_cta']))
                @if($shWa && (string) $hp('header_wa', '0') === '1')<a class="hpx-cta hpx-cta-wa" href="https://wa.me/{{ $shWa }}?text={{ urlencode($settings['quote_wa_msg'] ?? 'Hola, quiero una cotización') }}" target="_blank" rel="noopener"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Z"/></svg>{{ $hp('commercial_wa_text', 'WhatsApp') }}</a>@endif
                @if($shQuoteActive && (string) $hp('header_quote_btn', '0') === '1')<a class="hpx-cta hpx-cta-quote" href="{{ $hp('commercial_cta_url') ?: ($shWa ? 'https://wa.me/'.$shWa : $shopBase) }}"
                   @if(filled($hp('quote_btn_bg')) || filled($hp('quote_btn_color')))style="@if(filled($hp('quote_btn_bg')))background:{{ $hp('quote_btn_bg') }};border-color:{{ $hp('quote_btn_bg') }};@endif @if(filled($hp('quote_btn_color')))color:{{ $hp('quote_btn_color') }};@endif"@endif>{{ $hp('quote_btn_text', 'Cotizar') }}</a>@endif
            @elseif($phone && in_array($shSearch, ['wide','hero'], true))
                {{-- Era un <span> con la etiqueta en gris claro: sobre un encabezado
                     de color no se leia y, siendo un telefono, no se podia pulsar.
                     Ahora es un boton tel: con la etiqueta heredando el color del
                     encabezado. --}}
                <a class="phone-copy" href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" style="--tel-bg:{{ $hp('phone_btn_bg', '#ffffff') }};--tel-txt:{{ $hp('phone_btn_color', '#0f172a') }}"><span class="phone-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.4-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2Z"/></svg></span><span class="phone-txt"><small>{{ $hp('header_phone_label', 'Atención comercial') }}</small><strong>{{ $phone }}</strong></span></a>
            @endif
            <span class="ck-safe" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><span>Compra segura</span></span>
            <span class="hpx-sep" aria-hidden="true"></span><button class="cart-trigger hpx-cart" type="button" @click="openCartPage()" aria-label="{{ ($settings['store_mode'] ?? 'direct') === 'quote' ? 'Ver mi cotización' : 'Abrir carrito' }}"><span class="hpx-cart-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 3h2l2.1 11.2a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 2-1.6L21 7H6"></path><circle cx="10" cy="20" r="1"></circle><circle cx="18" cy="20" r="1"></circle></svg><span class="cart-count" x-text="itemCount()" aria-live="polite">0</span></span>@if(($settings['store_mode'] ?? 'direct') === 'quote')<span class="hpx-cart-total hpx-cart-label">Mi cotización</span>@else<span class="hpx-cart-total" x-text="money(total())"></span>@endif</button>
        </div>
    </div>
</header>

{{-- FILA de navegación --}}
@if($shNavRow)
<nav class="category-nav hpx-nav hpx-nav-{{ $shNavAlign }}" aria-label="Navegación" x-data="{ mega:false, megaCat: {{ $navCategories->first()->id ?? 'null' }}, vsp:false }" @mouseleave="mega=false">
    <div class="container category-bar">
        {{-- Boton de categorias comun (posicion, estilo y texto desde el
             Constructor). En modo mega solo pinta el boton y abre el panel
             de este layout; en modo lista trae su propio desplegable. --}}
        @if(in_array($shCatTrigger, ['mega', 'lista'], true) && $navCategories->count())
            @include('storefront.partials.nav.cats-button', ['modo' => $shCatTrigger, 'pos' => $shCatsPos, 'var' => 'mega'])
        @endif
        @if($shCatTrigger === 'editorial' && $navCategories->count())
        <button type="button" class="hpx-ed-trigger" @click="vsp=!vsp" :aria-expanded="vsp">
            Descubrir
            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" :style="vsp && 'transform:rotate(180deg)'"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        @endif
        @if($shCatTrigger === 'visual' && $navCategories->count())
        <button type="button" class="hpx-visual-trigger" @click="vsp=!vsp">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/></svg>
            Comprar
        </button>
        @endif
        <div class="category-list">
            @if($menuRoots->count())
                @foreach($menuRoots as $mItem)
                    @php
                        $isShopItem = in_array($mItem->destination_type, ['shop','products'], true);
                        $isActive = $isShopItem ? ($activeDest === 'shop' && ! $currentCategory) : in_array($mItem->destination_type, [$activeDest], true);
                    @endphp
                    @if(in_array($mItem->destination_type, ['category','subcategory']) && $mItem->destination_id)
                        <a href="{{ $shopBase }}?category={{ (int) $mItem->destination_id }}" class="{{ (string) $currentCategory === (string) $mItem->destination_id ? 'is-active' : '' }}">{{ $mItem->label }}</a>
                    @else
                        <a href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, $mItem) }}" class="{{ $isActive ? 'is-active' : '' }}" @if($mItem->target === '_blank') target="_blank" rel="noopener" @endif>{{ $mItem->label }}</a>
                    @endif
                @endforeach
            @else
                <a href="{{ $shopBase }}">Todos los productos</a>
                @foreach($navCategories->take(6) as $category)<a href="{{ $shopBase }}?category={{ $category->id }}">{{ $category->name }}</a>@endforeach
            @endif
        </div>

        {{-- ═══ Accesos del menú (configurables desde el constructor) ═══
             Antes eran tres enlaces fijos escritos a mano (Ofertas, Novedades,
             WhatsApp): para cambiar el texto, el icono o el destino había que
             tocar código. Ahora son hasta seis ranuras que apuntan a una
             categoría, a un perfil de catálogo, a una acción (WhatsApp, llamar,
             carrito, ofertas, novedades) o a una dirección libre.

             Retrocompatible: si la tienda no tiene ninguna ranura configurada,
             se construyen los tres accesos de siempre con sus mismos ajustes,
             así que ninguna tienda cambia hasta que alguien los edite. --}}
        @php
            $shHasOffers = (string) $hp('nav_show_offers', '1') !== '0'
                && (($onSale ?? collect())->isNotEmpty() || (string) $hp('nav_force_offers', '0') === '1');
            $shHasNew = (string) $hp('nav_show_new', '1') !== '0'
                && (($newArrivals ?? collect())->isNotEmpty() || (string) $hp('nav_force_new', '0') === '1');
            $shHasWa = (string) $hp('nav_show_wa', '1') !== '0' && $shWa !== '';

            // Biblioteca de iconos. Trazo de 1.8 y caja de 24 para que todos
            // pesen igual: mezclar iconos rellenos y de contorno es lo que hacía
            // que la fila se viera desordenada.
            $shChipIcon = function (string $key) use ($shWa) {
                $p = [
                    'etiqueta'  => '<path d="M20.6 13.4 12 22l-9-9V4h9l8.6 8.6a1.4 1.4 0 0 1 0 2Z"/><circle cx="7.5" cy="7.5" r="1.4"/>',
                    'novedad'   => '<path d="M20.6 13.4 12.4 21.6a2 2 0 0 1-2.8 0l-7.2-7.2a2 2 0 0 1-.6-1.4V4a2 2 0 0 1 2-2h9a2 2 0 0 1 1.4.6l6.4 6.4a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1.3"/>',
                    'descuento' => '<path d="M19 5 5 19"/><circle cx="7.5" cy="7.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/>',
                    'estrella'  => '<path d="m12 3 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.8 6.2 20.9l1.1-6.5L2.6 9.8l6.5-.9Z"/>',
                    'corazon'   => '<path d="M20.8 5.6a5 5 0 0 0-7.1 0L12 7.3l-1.7-1.7a5 5 0 1 0-7.1 7.1l8.8 8.8 8.8-8.8a5 5 0 0 0 0-7.1Z"/>',
                    'regalo'    => '<rect x="3" y="9" width="18" height="12" rx="1.6"/><path d="M3 13h18M12 9v12"/><path d="M12 9S9.5 3.5 7 5.2 9.6 9 12 9Zm0 0s2.5-5.5 5-3.8S14.4 9 12 9Z"/>',
                    'camion'    => '<path d="M3 16V6h11v10M14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17.5" cy="18" r="2"/>',
                    'escudo'    => '<path d="M12 2.5 4 6v6c0 4.5 3.3 8.2 8 9.5 4.7-1.3 8-5 8-9.5V6Z"/><path d="m9 12 2.2 2.2L15.4 10"/>',
                    'tienda'    => '<path d="M3.5 9h17l-1 11.5h-15Z"/><path d="M3.5 9 5 3.5h14L20.5 9"/><path d="M9.5 20.5V14h5v6.5"/>',
                    'carrito'   => '<path d="M3 3h2l2.1 11.2a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 2-1.6L21 7H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/>',
                    'caja'      => '<path d="M21 8 12 3 3 8v8l9 5 9-5Z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
                    'telefono'  => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.4-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2Z"/>',
                    'soporte'   => '<path d="M4 13a8 8 0 0 1 16 0"/><rect x="2.5" y="13" width="4" height="6" rx="1.6"/><rect x="17.5" y="13" width="4" height="6" rx="1.6"/><path d="M20 19v.6a2.4 2.4 0 0 1-2.4 2.4H13"/>',
                    'reloj'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5.2l3.4 2"/>',
                    'ubicacion' => '<path d="M20 10.5c0 5.4-8 12-8 12s-8-6.6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10.3" r="2.8"/>',
                    'tarjeta'   => '<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M2.5 10h19"/>',
                    'usuario'   => '<circle cx="12" cy="8" r="3.6"/><path d="M4.5 21a7.5 7.5 0 0 1 15 0"/>',
                    'catalogo'  => '<path d="M4 4h7v16H4z"/><path d="M13 4h7v16h-7z"/><path d="M6.5 8h2M15.5 8h2"/>',
                ];
                // WhatsApp va relleno porque su glifo oficial no se lee en trazo.
                if ($key === 'whatsapp') {
                    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.86 9.86 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm0 18.15h-.01a8.2 8.2 0 0 1-4.18-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.19 8.19 0 0 1-1.26-4.38c0-4.54 3.7-8.23 8.24-8.23a8.18 8.18 0 0 1 5.82 2.41 8.18 8.18 0 0 1 2.41 5.83c0 4.54-3.7 8.23-8.23 8.23Zm4.52-6.16c-.25-.13-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.13-.16.24-.64.8-.78.97-.15.16-.29.18-.53.06-.25-.13-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.01-.38.11-.5.11-.11.25-.29.37-.44.13-.15.17-.25.25-.42.08-.16.04-.31-.02-.44-.06-.12-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.43h-.47c-.16 0-.42.06-.64.31-.22.25-.84.82-.84 2s.86 2.32.98 2.48c.12.16 1.7 2.59 4.11 3.63.58.25 1.02.4 1.37.51.58.18 1.1.16 1.51.1.46-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.14-1.18-.06-.11-.22-.17-.47-.29Z"/></svg>';
                }
                $d = $p[$key] ?? $p['etiqueta'];
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$d.'</svg>';
            };

            // Resuelve el destino de una ranura a una URL real.
            $shChipUrl = function (string $target) use ($project, $shopBase, $shWa, $settings) {
                if ($target === 'ofertas')  return $shopBase.'?filter=sale';
                if ($target === 'novedades') return $shopBase.'?filter=new';
                if ($target === 'catalogo') return $shopBase;
                if ($target === 'whatsapp') {
                    return $shWa
                        ? 'https://wa.me/'.$shWa.'?text='.urlencode($settings['quote_wa_msg'] ?? 'Hola, quiero cotizar')
                        : '';
                }
                if (str_starts_with($target, 'categoria:')) {
                    return $shopBase.'?category='.(int) substr($target, 10);
                }
                if (str_starts_with($target, 'perfil:')) {
                    return \App\Support\StorefrontNavigation::profileUrl($project, substr($target, 7));
                }
                if (str_starts_with($target, 'tel:')) return $target;
                return $target; // dirección libre
            };

            // ═══ La letra la decide el color de fondo, no una constante ═══
            // El estilo sólido pintaba SIEMPRE la letra en blanco. Con un color
            // claro —el rosa de Niña daba 1,99:1, el celeste de Niño 2,26— el
            // texto casi desaparecía. Aquí se mide la luminancia y se elige
            // blanco o tinta oscura, así ningún color futuro puede romperlo.
            $shTinta = static function (string $hex): string {
                $hex = ltrim(trim($hex), '#');
                if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
                if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) return '#ffffff';
                $lin = static fn ($c) => ($c /= 255) <= 0.03928 ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4);
                $l = 0.2126 * $lin(hexdec(substr($hex, 0, 2)))
                   + 0.7152 * $lin(hexdec(substr($hex, 2, 2)))
                   + 0.0722 * $lin(hexdec(substr($hex, 4, 2)));
                // Contraste contra blanco vs contra tinta oscura: gana el mayor.
                // Se prefiere blanco siempre que llegue a 4,5:1; solo se cae a tinta
                // oscura cuando el fondo es tan claro que el blanco sería ilegible.
                return (1.05 / ($l + 0.05)) >= 4.5 ? '#ffffff' : '#10231f';
            };

            $shChips = [];
            for ($i = 1; $i <= 6; $i++) {
                $t = trim((string) $hp("nav_chip_{$i}_target", ''));
                if ($t === '' || (string) $hp("nav_chip_{$i}_enabled", '1') === '0') continue;
                // Misma regla que los accesos históricos y el filtro lateral:
                // un chip de Ofertas sin ninguna oferta real lleva a una rejilla
                // vacía. Se oculta solo y reaparece cuando existan descuentos.
                if ($t === 'ofertas' && !$shHasOffers) continue;
                if ($t === 'novedades' && !$shHasNew) continue;
                $url = $shChipUrl($t);
                if ($url === '') continue;
                $shChips[] = [
                    'url'   => $url,
                    'texto' => trim((string) $hp("nav_chip_{$i}_text", '')) ?: 'Ver más',
                    'icono' => (string) $hp("nav_chip_{$i}_icon", 'etiqueta'),
                    'estilo'=> in_array($hp("nav_chip_{$i}_style", 'suave'), ['plano','suave','solido'], true)
                        ? $hp("nav_chip_{$i}_style", 'suave') : 'suave',
                    'color' => trim((string) $hp("nav_chip_{$i}_color", '')),
                    'nueva' => str_starts_with($url, 'http') && !str_contains($url, request()->getHost()),
                ];
            }

            // WhatsApp siempre presente si la tienda tiene número: es el canal
            // por el que estas tiendas venden de verdad. Antes, en cuanto se
            // configuraba la primera ranura los accesos históricos dejaban de
            // aplicarse y el WhatsApp desaparecía sin que nadie lo hubiera
            // quitado. Solo se añade si no está ya en alguna ranura, para no
            // duplicarlo cuando el cliente lo coloca donde quiere.
            if (!empty($shChips) && $shHasWa
                && !collect($shChips)->contains(fn ($c) => str_contains($c['url'], 'wa.me'))) {
                $shChips[] = ['url' => $shChipUrl('whatsapp'), 'texto' => $hp('nav_wa_text', 'Cotiza por WhatsApp'),
                              'icono' => 'whatsapp', 'estilo' => 'solido', 'color' => '#25D366', 'nueva' => true];
            }

            // Sin ranuras configuradas: los tres accesos históricos.
            if (empty($shChips)) {
                if ($shHasOffers) $shChips[] = ['url' => $shopBase.'?filter=sale', 'texto' => $hp('nav_offers_text', 'Ofertas'), 'icono' => 'etiqueta', 'estilo' => 'suave', 'color' => 'var(--sale,#e8590c)', 'nueva' => false];
                if ($shHasNew)    $shChips[] = ['url' => $shopBase.'?filter=new',  'texto' => $hp('nav_new_text', 'Novedades'), 'icono' => 'novedad', 'estilo' => 'suave', 'color' => 'var(--primary)', 'nueva' => false];
                if ($shHasWa)     $shChips[] = ['url' => $shChipUrl('whatsapp'),   'texto' => $hp('nav_wa_text', 'Cotiza por WhatsApp'), 'icono' => 'whatsapp', 'estilo' => 'solido', 'color' => '#25D366', 'nueva' => true];
            }
        @endphp
        @if(count($shChips))
        <div class="hpx-nav-actions">
            <button class="hpx-nav-mini" type="button" @click="searchOpen = !searchOpen" aria-label="Buscar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/></svg>
            </button>
            <button class="hpx-nav-mini hpx-nav-mini-cart" type="button" @click="openCartPage()" aria-label="{{ ($settings['store_mode'] ?? 'direct') === 'quote' ? 'Ver mi cotización' : 'Abrir carrito' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 3h2l2.1 11.2a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 2-1.6L21 7H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                <span class="cart-count" x-text="itemCount()">0</span>
            </button>
            @foreach($shChips as $chip)
            <a class="hpx-nav-chip hpx-nav-chip--{{ $chip['estilo'] }}"
               href="{{ $chip['url'] }}"
               @if($chip['nueva']) target="_blank" rel="noopener" @endif
               {{-- Excepción de marca: el botón de WhatsApp es blanco sobre verde
                    en la propia app y así lo reconoce la gente; la tinta por
                    luminancia le ponía letra oscura. --}}
               @if($chip['color'] !== '') style="--chip:{{ $chip['color'] }};--chip-txt:{{ str_contains($chip['url'], 'wa.me') ? '#ffffff' : $shTinta($chip['color']) }}" @endif>{!! $shChipIcon($chip['icono']) !!}{{ $chip['texto'] }}</a>
            @endforeach
        </div>
        @endif
    </div>

    @if($shCatTrigger === 'mega' && $navCategories->count())
    @php
        // Panel multicolumna adaptativo: cada categoría raíz es una columna con
        // sus subcategorías; con pocas categorías el grid se encoge (sin columnas
        // vacías) y con muchas envuelve a una segunda fila.
        $hpMegaColsCfg = max(2, min(5, (int) ($hp('mega_columns') ?: 4)));
        $hpMegaMaxSubs = max(3, min(12, (int) ($hp('mega_max_subs') ?: 6)));
        // ¿Hay columna extra (marcas/promoción)? Misma condición que mega-extras.
        $hpMegaBrandsOn = ($hpKey === 'mega_menu') && (string) $hp('mega_brands', '1') !== '0'
            && collect(($homeSectionByNativeKey ?? collect())->get('brands')?->content['items'] ?? [])
                ->filter(fn ($b) => is_array($b) && ($b['enabled'] ?? true) && filled($b['name'] ?? null))->isNotEmpty();
        $hpMegaPromoOn = ($hpKey === 'mega_menu') && trim((string) $hp('mega_promo_title', '')) !== '';
        $hpMegaHasExtra = $hpMegaBrandsOn || $hpMegaPromoOn;
        $hpMegaColsShown = min($hpMegaColsCfg, max(1, $navCategories->count()));
    @endphp
    {{-- Velo: al abrir el mega menu la tienda se atenua y el panel gana foco --}}
    {{-- El velo cubre toda la pantalla y cuelga del <nav>: mientras el cursor
         esta sobre el, el mouseleave del nav no se dispara y el menu quedaba
         abierto al bajar. Se cierra al entrar en el velo, al hacer clic o al
         desplazar la pagina. --}}
    <div class="hpx-mega-veil" x-show="mega" x-cloak x-transition.opacity
         @keydown.escape.window="mega=false" @scroll.window="mega=false"></div>
    <div class="mega-panel hpx-mega" data-cols="{{ $hpMegaColsShown }}" data-layout="{{ $shMegaLayout }}" x-show="mega" x-cloak x-transition.opacity @mouseenter="mega=true">
        @if($shMegaLayout === 'lateral')
        {{-- ═══ Panel lateral ═══
             Columna fija de categorías a la izquierda; al posar el cursor sobre
             una, sus subcategorías se abren a la derecha en varias columnas.
             Con muchas categorías de un solo nivel la rejilla obliga a leerlo
             todo de golpe; así se recorre una a una sin perder el sitio.
             `megaCat` ya existía en el estado de este nav, declarado y sin usar. --}}
        <div class="container hpx-mega-lat">
            <div class="hpx-mega-aside" role="list">
                @foreach($navCategories->take(12) as $cat)
                <a class="hpx-mega-cat" role="listitem"
                   href="{{ $shopBase }}?category={{ $cat->id }}"
                   @mouseenter="megaCat={{ $cat->id }}" @focus="megaCat={{ $cat->id }}"
                   :class="megaCat==={{ $cat->id }} && 'is-on'">@if($shMenuIcons !== 'no')<span class="hpx-cat-ico" aria-hidden="true">{!! \App\Support\CategoryIcons::svg($cat->name, $settings['caticon_'.$cat->id] ?? null) !!}</span>@endif<span class="hpx-mega-cat-txt">{{ $cat->name }}</span>@if($cat->children->count())<span class="hpx-mega-cat-arr" aria-hidden="true">›</span>@endif</a>
                @endforeach
            </div>
            <div class="hpx-mega-fly">
                @foreach($navCategories->take(12) as $cat)
                <div class="hpx-mega-fly-panel" x-show="megaCat==={{ $cat->id }}" x-cloak>
                    @if($cat->children->count())
                    <div class="hpx-mega-fly-cols">
                        @foreach($cat->children as $sub)
                        <a class="hpx-mega-fly-link" href="{{ $shopBase }}?category={{ $sub->id }}">{{ $sub->name }}</a>
                        @endforeach
                    </div>
                    @else
                    {{-- Sin subcategorías no se deja el panel vacío: se ofrece
                         entrar directamente a la categoría. --}}
                    <p class="hpx-mega-fly-vacio">Esta categoría no tiene subcategorías.</p>
                    @endif
                    <a class="hpx-mega-fly-todo" href="{{ $shopBase }}?category={{ $cat->id }}">Ver todo en {{ $cat->name }} →</a>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="container hpx-mega-grid" style="--hpx-mc:{{ $hpMegaColsShown }}">
            @foreach($navCategories->take(10) as $cat)
            <div class="hpx-mega-col">
                {{-- Icono de la categoría. Sale de la biblioteca del constructor
                     (`caticon_{id}`, el mismo que ya usa la portada), así que no
                     hay un segundo sistema de iconos que mantener. Dónde se ve lo
                     decide `hp_menu_icons`: en ningún sitio, solo en móvil, solo
                     en escritorio o en ambos. --}}
                <a class="hpx-mega-title" href="{{ $shopBase }}?category={{ $cat->id }}">@if($shMenuIcons !== 'no')<span class="hpx-cat-ico" aria-hidden="true">{!! \App\Support\CategoryIcons::svg($cat->name, $settings['caticon_'.$cat->id] ?? null) !!}</span>@endif{{ $cat->name }}</a>
                @if($cat->children->count())
                    @foreach($cat->children->take($hpMegaMaxSubs) as $sub)
                    <a class="hpx-mega-link" href="{{ $shopBase }}?category={{ $sub->id }}">{{ $sub->name }}</a>
                    @endforeach
                    @if($cat->children->count() > $hpMegaMaxSubs)
                    <a class="hpx-mega-more" href="{{ $shopBase }}?category={{ $cat->id }}">Ver todas ({{ $cat->children->count() }}) →</a>
                    @endif
                @else
                <a class="hpx-mega-link" href="{{ $shopBase }}?category={{ $cat->id }}">Ver productos →</a>
                @endif
            </div>
            @endforeach
            @includeIf('storefront.partials.nav.mega-extras')
        </div>
        @endif
        <a class="hpx-mega-all" href="{{ $shopBase }}"><span class="container">Ver todo el catálogo →</span></a>
    </div>
    @endif

    @if($shCatTrigger === 'editorial' && $navCategories->count())
    @php
        // Imagen editorial: configurada > primera colección con foto > primera categoría con foto.
        $edImg = $assetUrl($hp('boutique_promo_image'));
        if (!$edImg) {
            $edCol = collect(($homeSectionByNativeKey ?? collect())->get('collection_showcase')?->content['items'] ?? [])
                ->first(fn ($it) => is_array($it) && filled($it['image'] ?? null));
            $edImg = $edCol ? $assetUrl($edCol['image']) : null;
        }
        if (!$edImg) {
            $edCat = $navCategories->first(fn ($c) => filled($c->image_url ?? $c->image ?? null));
            $edImg = $edCat ? $assetUrl($edCat->image_url ?? $edCat->image) : null;
        }
        $edUniverses = (!empty($catalogProfiles) && $catalogProfiles->count()) ? $catalogProfiles : null;
    @endphp
    <div class="hpx-ed" x-show="vsp" x-cloak x-transition.opacity @click.outside="vsp=false">
        <div class="container hpx-ed-grid">
            <div>
                <h5>Novedades</h5>
                <a class="hpx-ed-link" href="{{ $shopBase }}">Lo nuevo</a>
                <a class="hpx-ed-link" href="{{ $shopBase }}">Destacados</a>
                <a class="hpx-ed-link" href="{{ $shopBase }}">Temporada</a>
            </div>
            <div>
                <h5>{{ $edUniverses ? 'Colecciones' : 'Explorar' }}</h5>
                @if($edUniverses)
                    @foreach($edUniverses->take(6) as $cp)
                    <a class="hpx-ed-link" href="{{ \App\Support\StorefrontNavigation::profileUrl($project, $cp->slug) }}">{{ $cp->menu_label ?: $cp->name }}</a>
                    @endforeach
                @else
                    @foreach($navCategories->take(6) as $cat)
                    <a class="hpx-ed-link" href="{{ $shopBase }}?category={{ $cat->id }}">{{ $cat->name }}</a>
                    @endforeach
                @endif
            </div>
            <div>
                <h5>Categorías</h5>
                @foreach($navCategories->take(6) as $cat)
                <a class="hpx-ed-link" href="{{ $shopBase }}?category={{ $cat->id }}">{{ $cat->name }}</a>
                @endforeach
                <a class="hpx-ed-link" href="{{ $shopBase }}" style="font-weight:800;color:var(--accent,var(--primary))">Ver todo →</a>
            </div>
            <a class="hpx-ed-media" href="{{ $hp('boutique_promo_url') ?: $shopBase }}">
                @if($edImg)<img src="{{ $edImg }}" alt="" loading="lazy">@endif
                <span class="hpx-ed-media-copy">
                    <strong>{{ $hp('boutique_promo_title', 'Nueva colección') }}</strong>
                    @if(filled($hp('boutique_promo_desc')))<small>{{ $hp('boutique_promo_desc') }}</small>@endif
                    <span>{{ $hp('boutique_promo_button', 'Descubrir') }} →</span>
                </span>
            </a>
        </div>
    </div>
    @endif

    @if($shCatTrigger === 'visual' && $navCategories->count())
    <div class="hp-vs-panel" x-show="vsp" x-cloak x-transition.opacity style="position:absolute;left:0;right:0;z-index:60;background:var(--surface);border-bottom:1px solid var(--border);box-shadow:var(--shadow-lg)">
        <div class="container hp-vs-grid">
            @foreach($navCategories->take(10) as $cat)
            @php
                $vsOwn = $cat->image_url ?? $cat->image ?? null;
                $vsImg = $vsOwn ? $assetUrl($vsOwn) : ($cat->products?->first(fn ($pp) => filled($pp->main_image_url))?->main_image_url);
            @endphp
            <a class="hp-vs-card" href="{{ $shopBase }}?category={{ $cat->id }}">
                @if($vsImg)<img src="{{ $vsImg }}" alt="{{ $cat->name }}" loading="lazy">@else<span class="hp-vs-initial" aria-hidden="true">{{ mb_strtoupper(mb_substr($cat->name, 0, 1)) }}</span>@endif
                <span class="hp-vs-copy"><strong>{{ $cat->name }}</strong>@if($cat->children->count())<small>{{ $cat->children->count() }} subcategorías</small>@endif</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</nav>
@endif

{{-- Universos debajo de la navegación (Boutique) --}}
@if($shUniRow === 'below' && $shUniItems->count())
<div class="hpx-uni-below">
    <div class="container hpx-uni-below-inner">
        <a class="hpx-uni-pill {{ empty($activeProfile) && !$currentCategory ? 'is-active' : '' }}" href="{{ \App\Support\StorefrontNavigation::shopUrl($project) }}">Todo</a>
        @if($shUniProfiles)
            @foreach($catalogProfiles as $cp)
            <a class="hpx-uni-pill {{ (!empty($activeProfile) && $activeProfile->id === $cp->id) ? 'is-active' : '' }}" @if($cp->primary_color) style="--chip-color:{{ $cp->primary_color }}" @endif href="{{ \App\Support\StorefrontNavigation::profileUrl($project, $cp->slug) }}">{{ $cp->menu_label ?: $cp->name }}</a>
            @endforeach
        @else
            @foreach($shUniItems as $uc)
            <a class="hpx-uni-pill {{ (string) $currentCategory === (string) $uc->id ? 'is-active' : '' }}" href="{{ $shopBase }}?category={{ $uc->id }}">{{ $uc->name }}</a>
            @endforeach
        @endif
    </div>
</div>
@endif
</div>

<style>
    /* ═══ Shell componible: estructura por preset ═══ */
    .hpx-main{display:flex;align-items:center;gap:20px;padding-top:14px;padding-bottom:14px}
    .hpx .header-search-toggle{display:none}
    /* Telefono del encabezado como boton. La regla base lo pintaba con la
       etiqueta en #64748b a 10px: sobre un encabezado de color (celeste, azul)
       no alcanzaba contraste y ademas no era pulsable. Hereda el color del
       encabezado, sube a 11/15px y gana area de pulsacion. */
    /* Fondo y letra propios, configurables. Antes heredaba el color del
       encabezado con una transparencia: sobre un encabezado celeste la etiqueta
       "Atención comercial" no llegaba a contraste y el bloque no se leía como
       algo pulsable. Ahora es una pastilla con su propio fondo. */
    .hpx-main a.phone-copy{flex-direction:row;align-items:center;gap:10px;padding:7px 15px 7px 12px;
        border:1px solid transparent;border-radius:999px;text-decoration:none;
        color:var(--tel-txt,#0f172a);background:var(--tel-bg,#fff);
        box-shadow:0 1px 3px rgba(15,23,42,.10);
        transition:filter .18s ease,transform .18s ease}
    .hpx-main a.phone-copy:hover{filter:brightness(.96);transform:translateY(-1px)}
    .hpx-main a.phone-copy .phone-ico{display:grid;place-items:center;flex:0 0 auto}
    .hpx-main a.phone-copy .phone-ico svg{width:18px;height:18px}
    .hpx-main a.phone-copy .phone-txt{display:flex;flex-direction:column;line-height:1.15}
    /* El prefijo .hpx sube la especificidad por encima de
       `body.section-preset-commerce .phone-copy strong`, que fijaba 14px y el
       color secundario. */
    .hpx .hpx-main a.phone-copy small{color:inherit;opacity:.72;font-size:11px;letter-spacing:.02em}
    .hpx .hpx-main a.phone-copy strong{color:inherit;margin-top:2px;font-size:15px;font-weight:700;letter-spacing:.01em}
    @media(max-width:760px){.hpx-actions .hpx-cta{display:none}.hpx-main{gap:10px}.hpx-main .phone-copy{display:none}}
    /* MOVIL: menu + logo + buscador + carrito no caben en una fila de 390px; el
       buscador quedaba en ~150px y cortaba el texto. Pasa a su propia fila a
       ancho completo (patron estandar de ecommerce movil). */
    @media(max-width:760px){
        /* Header movil compacto: una fila util (menu / logo / carrito) + buscador. */
        .hpx-main{flex-wrap:wrap;row-gap:8px;padding-top:10px;padding-bottom:10px;align-items:center}
        /* Fila 1: hamburguesa | logo centrado | carrito. El logo se llevaba toda
           la fila y empujaba el carrito a una linea propia. */
        .hpx-main .header-hamburger{order:1;flex:0 0 44px}
        .hpx-main .brand{order:2;flex:1 1 0%;min-width:0;display:flex!important;justify-content:center;align-items:center}
        .hpx-main .brand-logo{max-height:42px!important;height:42px!important;width:auto!important;max-width:170px!important}
        .hpx-main .header-actions{order:3;flex:0 0 auto;display:flex;align-items:center;gap:8px}
        .hpx-main .header-actions .phone-copy{display:none}
        .hpx-topbar-left{gap:14px}
        .hpx-topbar-item:nth-child(n+3){display:none}
        .hpx-topbar-inner{min-height:0;padding:6px 12px}
        .hpx-main .hpx-searchbox,.hpx-main .hpx-search{order:9;flex:1 1 100%;width:100%;max-width:none}
        /* el boton se colaba antes del input al reordenar la fila */
        /* Sin relleno: campo y boton son una sola pieza, a la misma altura. */
        /* overflow VISIBLE, no hidden. El recorte estaba para redondear las
           esquinas de input y boton dentro de la caja, pero eso ya lo hacen los
           radios de los hijos (reglas de :first-child/:last-child e input).
           Con hidden, el desplegable de la busqueda predictiva, que cuelga por
           debajo con position:absolute, se pintaba entero y quedaba recortado
           a cero: el negocio escribia tres letras y no veia nada. */
        .hpx-searchbox{display:flex;align-items:stretch;height:48px;padding:0;overflow:visible}
        .hpx-search,.hpx-search input{height:100%}
        .hpx-searchbox .hpx-search{order:1;flex:1 1 auto;min-width:0}
        .hpx-searchgo{order:2}
        .hpx-search input{width:100%;font-size:14.5px}
        .hpx-brand{flex:1 1 auto}
    }
    .hpx-tall .hpx-main{padding-top:22px;padding-bottom:22px}
    .hpx-low .hpx-main{padding-top:8px;padding-bottom:8px}
    .hpx-main .brand-logo{max-height:var(--logo-h,48px)}
    .hpx-search{position:relative;flex:1;max-width:640px}
    .hpx-search-takeover .hpx-search{max-width:none;flex:1}
    .hpx-search-takeover .hpx-search input{height:54px;font-size:15px;border-width:2px;border-color:var(--primary);border-radius:12px;box-shadow:0 8px 26px color-mix(in srgb,var(--primary) 16%,transparent)}
    .hpx-search-hero .hpx-search input{height:50px;font-size:15px}
    .hpx-search-icon .hpx-search{flex:0 0 44px;max-width:44px;overflow:hidden;transition:max-width .25s ease,flex-basis .25s ease}
    .hpx-search-icon .hpx-search:focus-within{flex:1;max-width:420px}
    .hpx-actions{display:flex;align-items:center;gap:12px;margin-left:auto}
    /* Logo centrado (Boutique) */
    .hpx-logo-center{display:grid;grid-template-columns:1fr auto 1fr;align-items:center}
    .hpx-logo-center .brand{justify-self:center}
    .hpx-logo-center .brand-logo{max-height:64px}
    .hpx-logo-center .hpx-aux{display:flex;gap:18px;font-size:12px;letter-spacing:.08em;text-transform:uppercase}
    .hpx-logo-center .hpx-aux a{color:var(--header-text);opacity:.75;text-decoration:none}
    .hpx-logo-center .hpx-aux a:hover{opacity:1}
    .hpx-logo-center .hpx-actions{margin-left:0;justify-self:end}
    .hpx-logo-center .hpx-actions .hpx-search{flex:0 0 44px;max-width:44px;overflow:hidden;transition:max-width .25s ease,flex-basis .25s ease}
    .hpx-logo-center .hpx-actions .hpx-search:focus-within{flex:0 0 300px;max-width:300px}
    .hpx-logo-center{row-gap:0}
    /* Nav centrada / izquierda */
    .hpx-nav-center .category-bar{justify-content:center}
    .hpx-nav-center .category-list{justify-content:center}
    .hpx-boutique .category-list a{font-family:var(--font-title);font-size:12.5px;letter-spacing:.14em;text-transform:uppercase;padding:14px 4px}
    .hpx-boutique .category-list{gap:30px}
    .hpx-boutique .category-nav{border-top:1px solid var(--border);border-bottom:1px solid var(--border);background:var(--surface)}
    /* Nav inline del Minimal */
    .hpx-inline-nav{display:flex;gap:22px;margin-left:26px}
    .hpx-inline-nav a{color:var(--header-text);font-size:13.5px;font-weight:600;text-decoration:none;opacity:.85}
    .hpx-inline-nav a:hover{opacity:1;color:var(--primary)}
    @media(max-width:900px){.hpx-inline-nav{display:none}}
    /* Mega */
    {{-- Tinta por luminancia: sobre acento claro (dorado) el blanco fijo era ilegible. --}}
    .hpx-mega-menu .mega-btn,.hpx-commercial .mega-btn{display:inline-flex;align-items:center;gap:9px;background:var(--accent,var(--primary));color:var(--accent-ink,#fff);border:0;padding:11px 20px;border-radius:9px;font-weight:800;cursor:pointer}
    {{-- El buscador quedaba pegado al logo y el hueco sobrante caía todo a la
         derecha: se leía descentrado. Tres zonas: marca y utilidades ocupan lo
         mismo (flex 1 1 0) y el buscador queda centrado DE VERDAD respecto a
         la página, no solo respecto a su hueco. --}}
    .hpx-main .hpx-searchbox{margin-inline:auto}
    .hpx-main>.brand{flex:1 1 0!important;min-width:max-content}
    .hpx-main>.header-actions{flex:1 1 0!important;min-width:max-content;display:flex;justify-content:flex-end}
    /* CTAs comerciales */
    .hpx-cta{display:inline-flex;align-items:center;gap:8px;padding:11px 18px;border-radius:9px;font-size:13px;font-weight:800;text-decoration:none;border:1px solid transparent;transition:background .18s ease,color .18s ease,border-color .18s ease}
    /* El boton de cotizar se perdia sobre fondos de color: contorno solido */
    .hpx-cta-quote{color:var(--header-ink,currentColor);background:transparent;border-color:color-mix(in srgb,currentColor 38%,transparent)}
    .hpx-cta-quote:hover{background:color-mix(in srgb,currentColor 12%,transparent);border-color:currentColor}
    .hpx-cta svg{width:16px;height:16px}
    .hpx-cta-wa{background:#22c55e;color:#fff}
    .hpx-cta-quote{background:var(--primary);color:#fff}
    /* Barra corporativa B2B */
    .hpx-corp{background:var(--secondary);color:#fff}
    .hpx-corp-inner{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:8px 12px;padding:8px 16px;font-size:12.5px}
    /* Encabezado movil en dos filas fijas: menu + logo + carrito arriba y el
       buscador debajo. Antes el logo empujaba el carrito a una tercera fila y
       la cabecera se comia 245px de una pantalla de 844. */
    @media(max-width:760px){
        /* El contenedor dejaba 48px de margen a cada lado en una pantalla de
           390: la cabecera no cabia y se salia por la derecha. */
        .hpx-main.container{width:calc(100% - 28px)!important;max-width:none!important;
            margin-inline:auto!important;
            display:grid!important;grid-template-columns:44px minmax(0,1fr) auto;
            grid-template-areas:'menu marca acciones' 'buscar buscar buscar';
            align-items:center;gap:10px;padding-block:8px}
        .hpx-main > .header-hamburger{grid-area:menu}
        .hpx-main > .brand{grid-area:marca;justify-self:center;min-width:0!important;max-width:100%}
        .hpx-main .brand img,.hpx-main .brand-logo{max-height:48px!important;width:auto;max-width:100%;object-fit:contain}
        .hpx-main > .header-actions,.hpx-main > .hpx-act{grid-area:acciones;justify-self:end}
        .hpx-main > .hpx-searchbox{grid-area:buscar;width:100%;min-width:0}
    }
    /* La barra superior envolvia sus mensajes en dos filas y el contenedor
       dejaba 48px de margen a cada lado. En movil: una linea desplazable. */
    @media(max-width:760px){
        .hpx-topbar-inner.container{width:calc(100% - 24px)!important;max-width:none!important;
            margin-inline:auto!important;padding-inline:0!important}
        .hpx-topbar-left,.hpx-topbar-right{flex-wrap:nowrap!important;white-space:nowrap}
        .hpx-topbar-left > *,.hpx-topbar-right > *{flex:0 0 auto}
        .hpx-topbar-inner{min-height:38px}
        .hpx-topbar-left{gap:14px}
    }
    /* Cualquier barra superior en movil: una linea desplazable, sin redes. */
    @media(max-width:760px){
        .hpx-topbar .container,.hpx-topbar > div{flex-wrap:nowrap!important;
            overflow-x:auto;white-space:nowrap;scrollbar-width:none}
        .hpx-topbar .container::-webkit-scrollbar,.hpx-topbar > div::-webkit-scrollbar{display:none}
    }
    /* En movil esta barra medía 139px: dos lineas de texto envueltas y tres
       iconos de 44px que ya estan en el pie. Queda en una linea desplazable. */
    @media(max-width:760px){
        .hpx-corp-inner{flex-wrap:nowrap;justify-content:flex-start;overflow-x:auto;
            white-space:nowrap;padding:6px 14px;font-size:11.5px;min-height:36px;scrollbar-width:none}
        .hpx-corp-inner::-webkit-scrollbar{display:none}
        .hpx-corp-inner .hpx-topbar-social,.hpx-corp .hpx-topbar-social{display:none}
        .hpx-corp-inner a{min-height:36px}
    }
    .hpx-corp a{color:#fff;font-weight:700;text-decoration:none}
    .hpx-corp svg{width:14px;height:14px;vertical-align:-2px}
    .hpx-corp-sep{opacity:.4}
    /* Universos arriba (Multiuniverso) */
    .hpx-uni-top{background:color-mix(in srgb,var(--primary) 8%,var(--surface));border-bottom:1px solid var(--border)}
    .hpx-uni-top-inner{display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-start;gap:10px;padding:6px 16px;min-height:46px}
    .hpx-uni-note{margin-left:auto;color:var(--muted);font-size:12.5px;font-weight:600}
    /* Un sello circular no se lee como logotipo de cabecera. Mientras no exista
       la version horizontal, el isotipo se acompana del nombre en tipografia
       de marca: se lee, y el sello sigue siendo el sello. */
    /* En tablet no caben logo + nombre + buscador de 560: el nombre se cortaba.
       El buscador cede primero, la marca nunca. */
    @media(min-width:761px) and (max-width:1100px){
        .hpx-main .hpx-searchbox,body .hpx-searchbox{flex:1 1 auto!important;max-width:none!important;min-width:180px}
        .brand-wordmark{font-size:19px;margin-left:9px}
        .brand{flex:0 0 auto}
    }
    /* En tablet la navegacion desaparecia entera (navbar 0x0) y solo quedaba el
       menu hamburguesa: en 768px hay sitio de sobra para Inicio/Tienda/Contacto. */
    @media(min-width:761px) and (max-width:960px){
        .category-nav{display:block!important}
        .category-nav .category-list{display:flex!important;flex-wrap:nowrap;overflow-x:auto;
            scrollbar-width:none;gap:2px}
        .category-nav .category-list::-webkit-scrollbar{display:none}
        .header-hamburger{display:none!important}
    }
    /* El carrito mostraba "0S/ 0.00": contador e importe sin aire entre ellos. */
    .hpx-cart{gap:10px}
    .hpx-cart .cart-count{margin-right:2px}
    /* El importe del carrito iba al mismo peso que el resto del encabezado y se
       perdía. Es el dato que el comprador vigila mientras compra: sube de
       tamaño, gana peso y usa cifras de ancho fijo para que no baile al cambiar. */
    /* El importe heredaba el color de texto del contenido, no el del encabezado:
       sobre un encabezado de color se perdía. Sigue a `--header-text`, que es
       quien manda ahí, y así vale para cualquier color de tienda o de perfil. */
    .hpx-cart-total{margin-left:2px;font-variant-numeric:tabular-nums;
        font-size:15.5px;font-weight:800;letter-spacing:-.01em;
        color:var(--header-text,inherit)!important}
    @media(max-width:900px){.hpx-cart-total{font-size:14px}}
    /* Buscador con la misma altura en los tres anchos (medía 62 en tablet). */
    .hpx-searchbox,.hpx-searchbox input{height:48px!important;min-height:48px!important}
    .hpx-searchgo{height:48px!important}
    @media(max-width:760px){.hpx-searchbox,.hpx-searchbox input,.hpx-searchgo{height:44px!important;min-height:44px!important}}
    /* El nombre de la tienda usaba `--secondary`, que es el color de los títulos
       del contenido, no del encabezado. Dentro de un perfil de color eso dejaba
       "Baby Toncito" en rosa oscuro sobre rosa: ilegible. Sigue al color de
       letra del encabezado, que es quien manda ahí. */
    .brand-wordmark{margin-left:12px;font-family:var(--font-title);font-size:23px;font-weight:800;
        letter-spacing:-.02em;color:var(--header-text,var(--secondary));white-space:nowrap;line-height:1}
    .brand{align-items:center;min-width:0}
    /* El nombre iba en una linea fija de 339px y se metia bajo el buscador
       (empezaba 15px antes de que el nombre terminara). Se recorta al espacio
       que de verdad tiene, sin empujar ni taparse con nada. */
    @media(min-width:761px){
        .hpx-main > .brand{flex:0 0 auto!important;width:max-content;padding-right:24px;box-sizing:content-box}
        .brand-wordmark{overflow:visible;max-width:none}
    }
    /* En movil el nombre iba a 18px y en una sola linea: "DISTRIBUIDORA
       MURUHUAY" medía 265px sobre 390 de pantalla y se metía debajo del
       carrito. Ahora la marca comparte el ancho disponible y el nombre se
       encoge y parte en dos lineas antes que invadir nada. */
    @media(max-width:760px){
        .brand-wordmark{font-size:clamp(11px,3.4vw,15px);margin-left:7px;white-space:normal;
            line-height:1.05;overflow-wrap:anywhere;max-height:2.2em;overflow:hidden}
        .brand{min-width:0;flex:1 1 auto;overflow:hidden}
        .brand-logo{flex:0 0 auto}
    }
    @media(max-width:400px){.brand-wordmark{font-size:clamp(10px,3vw,13px)}}
    /* El buscador ocupaba 900px de 1240 y su radio no pegaba con la marca. */
    .hpx-main .hpx-searchbox,body .hpx-searchbox{max-width:560px!important;flex:1 1 320px!important;min-width:200px;border-radius:12px!important}
    .hpx-searchbox input{border-radius:12px 0 0 12px}
    /* ═══ Buscador: campo y botón, una sola pieza ═══
       El arreglo anterior solo entraba en móvil. En escritorio el botón seguía
       con radio propio y montado sobre el campo, y asomaba un filo blanco a su
       derecha. Se resuelve en la caja, para todos los anchos: ella pone el
       redondeo y recorta, y los dos hijos van a tope sin redondeo propio. */
    /* Misma razon que arriba: visible, o la busqueda predictiva no se ve. */
    .hpx-main .hpx-searchbox,body .hpx-searchbox{display:flex;align-items:stretch;padding:0;overflow:visible}
    .hpx-searchbox .hpx-search{flex:1 1 auto;min-width:0;max-width:none}
    .hpx-searchbox .hpx-search input{width:100%;height:100%;border-radius:0!important;border:0}
    .hpx-searchbox .hpx-searchgo{flex:0 0 56px;margin:0!important;border-radius:0!important;align-self:stretch;
        display:grid!important;place-items:center!important;padding:0!important}
    /* La lupa iba a 19px con trazo 1.7: dentro de un cuadro de 56px se veía
       pequeña y desplazada. Centrada de verdad, más grande y con trazo más
       grueso para que se lea como botón y no como adorno. */
    .hpx-searchbox .hpx-searchgo svg{width:22px!important;height:22px!important;display:block;
        stroke-width:2.4!important;stroke-linecap:round}
    @media(max-width:760px){.hpx-searchbox{max-width:none}}
    @media(max-width:900px){.hpx-uni-note{display:none}}
    .hpx-uni-label{color:var(--muted);font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;margin-right:6px}
    .hpx-uni-chip{padding:9px 20px;color:var(--text-strong);background:var(--surface);border:1.5px solid var(--border);border-radius:999px;font-size:13.5px;font-weight:800;text-decoration:none;transition:.15s}
    .hpx-uni-chip:hover{border-color:var(--chip-color,var(--primary))}
    .hpx-uni-chip.is-active{color:#fff;background:var(--chip-color,var(--primary));border-color:var(--chip-color,var(--primary))}
    /* Universos debajo (Boutique) */
    .hpx-uni-below{background:var(--surface-soft);border-bottom:1px solid var(--border)}
    .hpx-uni-below-inner{display:flex;flex-wrap:wrap;justify-content:center;gap:8px;padding:8px 16px}
    .hpx-uni-pill{padding:6px 15px;color:var(--text);background:var(--surface);border:1px solid var(--border);border-radius:999px;font-size:12px;font-weight:700;letter-spacing:.05em;text-decoration:none}
    .hpx-uni-pill.is-active{color:#fff;background:var(--chip-color,var(--primary));border-color:var(--chip-color,var(--primary))}
    /* Trigger visual */
    .hpx-visual-trigger{display:inline-flex;align-items:center;gap:9px;padding:12px 4px;margin-right:18px;color:inherit;background:none;border:0;border-bottom:2px solid currentColor;font-size:13px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;opacity:.95}
    .hpx-visual-trigger:hover{opacity:1}
    .hpx-visual-trigger svg{width:16px;height:16px}
    /* Grid visual (Visual Collections): el shell renderiza su propio panel;
       estos estilos DEBEN vivir aquí (el partial nav/visual ya no se incluye). */
    .hp-vs-grid{display:grid;grid-template-columns:repeat({{ max(3, min(5, (int) ($hp('visual_columns') ?: 4))) }},minmax(0,1fr));gap:14px;padding:22px 0}
    .hp-vs-card{position:relative;display:grid;place-items:end start;aspect-ratio:4/3;border-radius:var(--radius-md);overflow:hidden;text-decoration:none;background:linear-gradient(135deg,color-mix(in srgb,var(--primary) 82%,#000),color-mix(in srgb,var(--primary) 45%,#000))}
    .hp-vs-card img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transition:transform .3s ease}
    .hp-vs-card:hover img{transform:scale(1.05)}
    .hp-vs-card:before{content:'';position:absolute;inset:0;z-index:1;background:linear-gradient(180deg,transparent 30%,rgba(8,12,20,.62))}
    .hp-vs-copy{position:relative;z-index:2;padding:14px 16px;color:#fff}
    .hp-vs-copy strong{display:block;font-family:var(--font-title);font-size:16px}
    .hp-vs-copy small{opacity:.85;font-size:11.5px}
    .hp-vs-initial{position:relative;z-index:0;place-self:center;color:rgba(255,255,255,.35);font-family:var(--font-title);font-size:64px;font-weight:800}
    @media(max-width:960px){.hp-vs-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:480px){.hp-vs-grid{grid-template-columns:1fr 1fr;gap:10px}.hp-vs-copy strong{font-size:13px}}
    /* Buscador con selector integrado y botón (spec §9-12) */
    /* El buscador tenia ancho fijo (760px) y dejaba ~290px de hueco muerto
       antes de las acciones: ahora ocupa el espacio disponible. */
    .hpx-searchbox{display:flex;align-items:stretch;flex:1 1 auto;min-width:0;max-width:900px;margin-inline:auto;border:1px solid #D8DEE8;border-radius:6px;background:#fff}
    .hpx-searchbox>:first-child{border-top-left-radius:5px;border-bottom-left-radius:5px}
    .hpx-searchbox>:last-child{border-top-right-radius:5px;border-bottom-right-radius:5px}
    .hpx-searchbox .hpx-search input{border-radius:5px 0 0 5px}
    .hpx-searchbox .search-suggest{border-radius:12px}
    .hpx-searchbox .hpx-search{flex:1;max-width:none}
    .hpx-searchbox .hpx-search input{height:46px;border:0;border-radius:0;padding-left:19px;font-size:14px;box-shadow:none}
    .hpx-searchbox .hpx-search input:focus{box-shadow:none}
    .hpx-searchbox .hpx-search>svg{display:none}
    .hpx-searchcats{position:relative;flex:0 0 auto;border-left:1px solid #E7EAF0}
    .hpx-searchcats>button{display:flex;align-items:center;gap:8px;height:100%;min-width:180px;padding:0 14px;background:transparent;border:0;color:var(--secondary);font-size:13px;font-weight:500;cursor:pointer;white-space:nowrap}
    .hpx-searchcats>button svg{width:13px;height:13px;transition:transform .18s ease}
    .hpx-searchcats-menu{position:absolute;top:calc(100% + 6px);right:0;z-index:70;min-width:230px;padding:6px 0;background:#fff;border:1px solid var(--border);border-radius:8px;box-shadow:var(--shadow-lg)}
    .hpx-searchcats-menu a{display:block;padding:9px 16px;color:var(--text);font-size:13px;text-decoration:none}
    .hpx-searchcats-menu a:hover{background:var(--surface-soft);color:var(--primary)}
    .hpx-searchgo{flex:0 0 60px;display:grid;place-items:center;background:var(--accent,var(--primary));border:0;color:#fff;cursor:pointer;transition:filter .18s ease,transform .18s ease}
    .hpx-searchgo:hover{filter:brightness(.93)}
    .hpx-searchgo:active{transform:scale(.98)}
    .hpx-searchgo svg{width:19px;height:19px}
    @media(max-width:900px){.hpx-searchcats{display:none}.hpx-searchbox{flex-basis:100%}}
    /* Carrito con total y separador (spec §14) */
    .hpx-sep{width:1px;align-self:stretch;margin:10px 4px;background:var(--border)}
    .hpx-cart{display:flex;align-items:center;gap:10px;min-width:0;padding:0 6px;background:transparent!important;border:0!important;color:var(--header-text)!important}
    .hpx-cart-ico{position:relative;display:grid;place-items:center}
    .hpx-cart-ico svg{width:22px;height:22px}
    .hpx-cart .cart-count{position:absolute;top:-6px;right:-8px;min-width:18px;height:18px;background:var(--accent,var(--primary));color:#fff;font-size:10px}
    .hpx-cart-total{font-size:13px;font-weight:700;color:var(--secondary);white-space:nowrap}
    @media(max-width:760px){.hpx-cart-total,.hpx-sep{display:none}}
    /* MOVIL: la franja de universos gastaba dos filas (el 3er chip saltaba de linea)
       y el boton de busqueda crecia tanto que recortaba el campo. */
    @media(max-width:760px){
        .hpx-uni-top-inner{flex-wrap:nowrap;justify-content:flex-start;gap:7px;padding:8px 12px;overflow-x:auto;scrollbar-width:none;-webkit-overflow-scrolling:touch}
        .hpx-uni-top-inner::-webkit-scrollbar{display:none}
        .hpx-uni-label{display:none}
        .hpx-uni-chip{flex:0 0 auto;padding:6px 15px;font-size:12.5px}
        /* El boton tenia radio y margen propios DENTRO de una caja con relleno:
           se leia como una pieza pegada encima del campo, no como parte de el.
           Ahora comparte altura con el campo y solo redondea su lado derecho. */
        .hpx-searchgo{width:48px!important;flex:0 0 48px!important;min-width:0!important;padding:0!important;
            margin:0!important;border-radius:0 var(--r-btn,10px) var(--r-btn,10px) 0!important}
        .hpx-searchgo svg{width:18px;height:18px}
        .hpx-searchcats{display:none}
        .hpx-search input,.search input{min-width:0}
    }
    /* Topbar rica (spec): 3 mensajes con ícono a la izquierda + accesos a la derecha */
    .hpx-topbar{font-size:12.5px}
    /* En movil la barra superior se comia 130px de la primera pantalla con dos
       lineas de texto y tres iconos de 44px que ya estan en el pie. Se deja una
       sola linea, desplazable, y se ocultan las redes. */
    @media(max-width:760px){
        .hpx-topbar{font-size:11.5px}
        .hpx-topbar .container{display:flex;align-items:center;gap:10px;min-height:34px;
            overflow-x:auto;white-space:nowrap;scrollbar-width:none}
        .hpx-topbar .container::-webkit-scrollbar{display:none}
        .hpx-topbar-social{display:none}
        .hpx-topbar a{min-height:34px}
    }
    .hpx-topbar-inner{display:flex;align-items:center;justify-content:space-between;gap:20px;min-height:40px}
    .hpx-topbar-left{display:flex;align-items:center;gap:30px;flex-wrap:wrap}
    .hpx-topbar-right{display:flex;align-items:center;gap:22px}
    .hpx-topbar-social{display:inline-flex;align-items:center;gap:12px}
    /* Pagina activa: subrayado de acento + texto reforzado, visible en cualquier
       plantilla (antes dependia del preset y no se notaba). */
    .hpx-nav .category-list a.is-active,
    .category-nav .category-list a.is-active{position:relative;color:var(--text-strong,#0f172a)!important;background:none!important;font-weight:700}
    .hpx-nav .category-list a.is-active::after,
    .category-nav .category-list a.is-active::after{content:'';position:absolute;left:13px;right:13px;bottom:2px;height:3px;border-radius:2px;background:var(--accent,var(--primary))}
    .hpx-corp-social{margin-left:auto}
    .hpx-corp-inner{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    /* Colores oficiales de cada red (el icono va en blanco sobre su color) */
    .hpx-topbar-social a{display:inline-grid;place-items:center;width:26px;height:26px;border-radius:50%;color:#fff;transition:transform .18s ease,filter .18s ease}
    .hpx-topbar-social a:hover{transform:translateY(-1px);filter:brightness(1.12)}
    .hpx-topbar-social a[aria-label="Facebook"]{background:#1877F2}
    .hpx-topbar-social a[aria-label="Instagram"]{background:radial-gradient(circle at 30% 107%,#fdf497 0%,#fd5949 45%,#d6249f 60%,#285AEB 90%)}
    .hpx-topbar-social a[aria-label="TikTok"]{background:#010101;box-shadow:0 0 0 1px rgba(255,255,255,.22)}
    .hpx-topbar-social a[aria-label="YouTube"]{background:#FF0000}
    .hpx-topbar-social a[aria-label="LinkedIn"]{background:#0A66C2}
    .hpx-topbar-social svg{width:15px;height:15px}
    .hpx-topbar-item,.hpx-topbar-right a{display:inline-flex;align-items:center;gap:8px;color:inherit;text-decoration:none;transition:color .18s ease}
    .hpx-topbar-right a:hover{color:var(--accent,#c9a24b)}
    .hpx-topbar svg{width:16px;height:16px;color:var(--accent,#c9a24b);flex:0 0 auto}
    @media(max-width:1100px){.hpx-topbar-left .hpx-topbar-item:nth-child(n+3){display:none}}
    @media(max-width:760px){.hpx-topbar-right{display:none}.hpx-topbar-left{gap:18px;overflow-x:auto;scrollbar-width:none}.hpx-topbar-left .hpx-topbar-item{white-space:nowrap}}
    /* Accesos comerciales del nav (Ofertas / Novedades / WhatsApp) */
    .hpx-nav-actions{display:flex;align-items:center;gap:30px;margin-left:auto}
    .hpx-nav-actions a{display:inline-flex;align-items:center;gap:8px;color:var(--header-text);font-size:13px;font-weight:600;text-decoration:none;transition:color .18s ease}
    .hpx-nav-actions a:hover{color:var(--accent,var(--primary))}
    .hpx-nav-actions svg{width:16px;height:16px}
    /* Los tres estilos de acceso. Cada ranura elige el suyo desde el
       constructor y su color con la variable --chip, para que la fila tenga
       jerarquia: uno solido manda, el resto acompana. */
    .hpx-nav-actions .hpx-nav-chip{gap:8px;border-radius:999px;transition:background .18s ease,color .18s ease,box-shadow .18s ease,transform .18s ease}
    .hpx-nav-actions .hpx-nav-chip svg{width:16px;height:16px;color:var(--chip,currentColor)}
    /* Plano: solo texto, para accesos secundarios. */
    .hpx-nav-actions .hpx-nav-chip--plano:hover{color:var(--chip,var(--accent,var(--primary)))}
    /* Suave: fondo teñido del color propio. El texto va en el color, no en
       negro, porque sobre un fondo al 10% el negro no se distingue del resto. */
    /* El tinte estaba al 12%: sobre blanco apenas se distinguía del fondo y los
       accesos parecían texto gris. Sube a 18% con borde del propio color, que
       es lo que hace que se lean como pastilla sin convertirlos en botón. */
    .hpx-nav-actions .hpx-nav-chip--suave{padding:8px 15px;font-weight:700;
        color:color-mix(in srgb,var(--chip,var(--primary)) 88%,#000);
        background:color-mix(in srgb,var(--chip,var(--primary)) 18%,#fff);
        border:1.5px solid color-mix(in srgb,var(--chip,var(--primary)) 38%,#fff)}
    .hpx-nav-actions .hpx-nav-chip--suave:hover{
        color:#fff;background:var(--chip,var(--primary));border-color:var(--chip,var(--primary))}
    .hpx-nav-actions .hpx-nav-chip--suave:hover svg{color:#fff}
    /* Solido: el que manda. Uno por fila; dos ya compiten entre si. */
    .hpx-nav-actions .hpx-nav-chip--solido{padding:9px 17px;color:var(--chip-txt,#fff);background:var(--chip,var(--primary));font-weight:700;box-shadow:0 2px 8px color-mix(in srgb,var(--chip,var(--primary)) 34%,transparent)}
    .hpx-nav-actions .hpx-nav-chip--solido:hover{color:var(--chip-txt,#fff);filter:brightness(.94);box-shadow:0 4px 14px color-mix(in srgb,var(--chip,var(--primary)) 44%,transparent);transform:translateY(-1px)}
    .hpx-nav-actions .hpx-nav-chip--solido svg{color:var(--chip-txt,#fff)}
    /* Con muchos accesos la fila se estrecha antes: el hueco baja de 30 a 14. */
    .hpx-nav-actions:has(.hpx-nav-chip:nth-child(5)){gap:14px}
    @media(max-width:1180px){.hpx-nav-actions{gap:20px}.hpx-nav-actions a span{display:none}}
    @media(max-width:960px){.hpx-nav-actions{display:none}}
    /* Mega panel multicolumna (Mega Menú Pro / Comercial) */
    .hpx-mega{position:absolute;left:0;right:0;z-index:61;background:var(--surface);border-bottom:1px solid var(--border);box-shadow:var(--shadow-lg)}
    /* El velo va POR DEBAJO del header y del panel: oscurece la tienda pero deja
       el encabezado accesible para volver. Cierra con clic o Escape. */
    .hpx-mega-veil{position:fixed;inset:0;z-index:30;background:rgba(8,17,33,.42);pointer-events:none}
        /* Columnas reales, no masonry. Con `columns` + `overflow-y` el navegador
       deja de balancear y apila los bloques en la primera columna: una tienda
       de dos categorias mostraba las dos una debajo de otra y medio panel en
       blanco. Rejilla de anchos acotados y alineada a la izquierda: con dos
       categorias se lee como un panel, no como una pagina vacia. */
    .hpx-mega-grid{display:grid;grid-template-columns:repeat(var(--hpx-mc,4),minmax(160px,232px));justify-content:start;gap:12px 40px;padding:30px 0 22px;max-height:calc(100vh - 160px);overflow-y:auto;overscroll-behavior:contain}
    /* ═══ Lista vertical (una sola columna) ═══
       Con `Columnas del panel = 1` el mega menú deja de ser una rejilla y pasa a
       ser la lista alta de toda la vida: una categoría por fila, con su icono a
       la izquierda y la flecha a la derecha si tiene subcategorías. Es el
       formato que pide un catálogo con muchas categorías de un solo nivel. */
    .hpx-mega-grid[style*="--hpx-mc:1"],
    .hpx-mega[data-cols="1"] .hpx-mega-grid{grid-template-columns:minmax(280px,340px);gap:0;padding:8px 0}
    .hpx-mega[data-cols="1"] .hpx-mega-col{padding:0}
    .hpx-mega[data-cols="1"] .hpx-mega-title{display:flex;align-items:center;gap:12px;min-height:46px;padding:0 16px;border-bottom:1px solid var(--border);font-weight:600}
    .hpx-mega[data-cols="1"] .hpx-mega-title::after{content:'›';margin-left:auto;color:var(--muted);font-size:19px;line-height:1}
    .hpx-mega[data-cols="1"] .hpx-mega-title:hover{background:color-mix(in srgb,var(--primary) 8%,transparent);color:var(--primary)}
    .hpx-mega[data-cols="1"] .hpx-mega-link,
    .hpx-mega[data-cols="1"] .hpx-mega-more{display:none}
    /* ═══ Iconos del menú ═══
       El icono sale de la biblioteca del constructor. El alcance decide dónde
       se ve: en móvil el icono ayuda a barrer la lista de un vistazo, pero en
       escritorio, junto a un texto corto, muchas veces sobra. Por eso son
       decisiones separadas y no un único interruptor. */
    /* El velo cubre la tienda para que el panel destaque, pero se comia tambien
       la propia barra de navegacion: el boton "Categorias" y los accesos se
       veian apagados justo cuando el cliente los estaba usando. La barra sube
       por encima del velo (z-index 30) y se queda a plena luz. */
    .hpx-nav{position:relative;z-index:62}
    /* El velo es HIJO del nav, así que tapaba la propia barra de categorías: el
       botón y los accesos se apagaban justo cuando el cliente los estaba
       usando. La barra sube por encima del velo dentro del mismo contexto. */
    .hpx-nav > .category-bar{position:relative;z-index:40}
    /* El velo cubría la ventana entera (`fixed; inset:0`) y, siendo hijo del
       nav, apagaba la propia barra de categorías y el encabezado — justo lo que
       el cliente está usando. Ahora arranca en el borde inferior del nav y baja:
       oscurece la tienda, que es lo que se pretendía, y deja la navegación a
       plena luz sin pelear con nadie por el orden de apilamiento. */
    .hpx-mega-veil{position:absolute!important;inset:100% 0 auto 0!important;height:100vh!important}
    /* Y el encabezado entero con ella: buscador, teléfono y carrito siguen
       siendo navegación, no fondo. Con el velo encima el celeste de marca se
       apagaba a gris verdoso justo al abrir el menú. */
    /* Tiene que ir POR ENCIMA del nav (62), no solo del velo: el velo es hijo
       del nav y, al darle al nav contexto de apilamiento propio, arrastra
       consigo todo lo que tenga menos que él. Son hermanos, así que basta con
       que el encabezado pese más. El `!important` es porque una regla anterior
       ya le fijaba 40. */
    header.store-header{position:relative;z-index:64!important}
    /* ═══ Panel lateral con despliegue al lado ═══ */
    .hpx-mega-lat{display:grid;grid-template-columns:minmax(240px,286px) minmax(0,1fr);align-items:stretch;
        max-height:calc(100vh - 150px);padding:0}
    .hpx-mega-aside{display:flex;flex-direction:column;padding:8px 0;overflow-y:auto;
        border-right:1px solid var(--border);background:var(--surface-soft,#f8fafc)}
    .hpx-mega-cat{display:flex;align-items:center;gap:0;min-height:44px;padding:0 16px;
        color:var(--text-strong);font-size:13.5px;font-weight:600;text-decoration:none;
        border-left:3px solid transparent;transition:background .14s ease,color .14s ease,border-color .14s ease}
    .hpx-mega-cat-txt{flex:1 1 auto;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .hpx-mega-cat-arr{flex:0 0 auto;margin-left:10px;color:var(--muted);font-size:19px;line-height:1}
    .hpx-mega-cat:hover,.hpx-mega-cat.is-on{color:var(--primary);background:#fff;border-left-color:var(--primary)}
    .hpx-mega-cat.is-on .hpx-mega-cat-arr{color:var(--primary)}
    .hpx-mega-fly{position:relative;min-width:0;padding:22px 26px;overflow-y:auto;background:#fff}
    /* Las subcategorías fluyen en columnas: se reparten solas según cuántas
       haya, sin dejar una columna larga y tres vacías. */
    .hpx-mega-fly-cols{columns:4 190px;column-gap:26px}
    .hpx-mega-fly-link{display:block;padding:6px 0;color:var(--text);font-size:13px;text-decoration:none;
        break-inside:avoid;transition:color .14s ease}
    .hpx-mega-fly-link:hover{color:var(--primary)}
    .hpx-mega-fly-vacio{margin:0;color:var(--muted);font-size:13px}
    .hpx-mega-fly-todo{display:inline-block;margin-top:18px;color:var(--primary);font-size:13px;font-weight:700;text-decoration:none}
    .hpx-mega-fly-todo:hover{text-decoration:underline}
    /* En pantallas estrechas la columna lateral se come el panel: por debajo de
       960px el menú ya es el desplegable móvil, así que aquí solo se protege el
       tramo intermedio reduciendo la columna. */
    @media(max-width:1100px){.hpx-mega-lat{grid-template-columns:minmax(200px,230px) minmax(0,1fr)}.hpx-mega-fly-cols{columns:3 170px}}
    .hpx-cat-ico{display:inline-grid;place-items:center;flex:0 0 20px;width:20px;height:20px;margin-right:10px;color:var(--accent,var(--primary))}
    .hpx-cat-ico svg{width:20px;height:20px;display:block}
    .hpx-mega-title{display:flex;align-items:center}
    @if($shMenuIcons === 'no')
    .hpx-cat-ico{display:none}
    @elseif($shMenuIcons === 'movil')
    @media(min-width:761px){.hpx-cat-ico{display:none}}
    @elseif($shMenuIcons === 'escritorio')
    @media(max-width:760px){.hpx-cat-ico{display:none}}
    @endif
    .hpx-mega-grid .hpx-mega-col{min-width:0}
    .hpx-mega-grid::-webkit-scrollbar{width:8px}
    .hpx-mega-grid::-webkit-scrollbar-thumb{background:color-mix(in srgb,var(--primary) 22%,transparent);border-radius:8px}
    /* El panel nunca debe tapar toda la pantalla: alto acotado y scroll propio */
    /* El tope era `100vh - 240px` con `overflow:hidden`: en una pantalla de
       portátil el panel se cortaba y las últimas categorías desaparecían sin
       aviso ni barra de desplazamiento. Ahora el recorte lo hace la rejilla de
       dentro, que SÍ tiene desplazamiento, y el panel se queda con el alto que
       necesite hasta el borde de la ventana. */
    .hpx-mega{max-height:calc(100vh - 120px);overflow:hidden;border-radius:0 0 10px 10px}
    .hpx-mega-grid .hp-mm-extra{grid-column:span 1;border-left:1px solid var(--border);padding-left:22px}
    .hpx-mega-col{min-width:0}
    {{-- Rejilla amigable: antes era una pared de enlaces de 20px de alto, todos
         iguales y sin pista de que se pueden tocar. Ahora cada fila es un
         objetivo real (32px), con hover de banda del color primario y flecha;
         el título lleva su icono. Todo con los tokens de la tienda. --}}
    .hpx-mega-title{display:flex;align-items:center;gap:9px;margin-bottom:10px;padding-bottom:9px;border-bottom:2px solid var(--accent,var(--primary));color:var(--text-strong);font-family:var(--font-title);font-size:14px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;text-decoration:none}
    .hpx-mega-title:hover{color:var(--primary)}
    .hpx-mega-title .hpx-cat-ico{display:inline-grid;place-items:center;width:30px;height:30px;flex:0 0 30px;border-radius:9px;color:var(--primary);background:color-mix(in srgb,var(--primary) 10%,transparent)}
    .hpx-mega-title .hpx-cat-ico svg{width:17px;height:17px}
    .hpx-mega-link{position:relative;display:flex;align-items:center;min-height:32px;padding:4px 22px 4px 10px;margin-left:-10px;border-radius:8px;color:var(--text);font-size:13px;line-height:1.4;text-decoration:none;transition:background .15s ease,color .15s ease}
    .hpx-mega-link::after{content:'›';position:absolute;right:9px;color:var(--primary);font-size:15px;opacity:0;transition:opacity .15s ease,translate .15s ease}
    .hpx-mega-link:hover{color:var(--primary);background:color-mix(in srgb,var(--primary) 8%,transparent);font-weight:700}
    .hpx-mega-link:hover::after{opacity:1;translate:2px 0}
    .hpx-mega-more{display:inline-flex;align-items:center;min-height:32px;margin-top:8px;padding:0 14px;border:1px solid color-mix(in srgb,var(--primary) 35%,transparent);border-radius:999px;color:var(--primary);font-size:12px;font-weight:800;text-decoration:none;transition:background .15s ease,color .15s ease}
    .hpx-mega-more:hover{color:#fff;background:var(--primary)}
    .hpx-mega-all{display:block;border-top:1px solid var(--border);background:var(--surface-soft);padding:13px 0;color:var(--primary);font-size:13px;font-weight:800;text-decoration:none;text-align:right;transition:background .15s ease}
    .hpx-mega-all:hover{background:color-mix(in srgb,var(--primary) 10%,var(--surface-soft))}
    @media(max-width:960px){.hpx-mega{display:none}}
    /* Dropdown editorial (Boutique) */
    .hpx-ed-trigger{display:inline-flex;align-items:center;gap:8px;padding:14px 4px;margin-right:24px;color:inherit;background:none;border:0;font-family:var(--font-title);font-size:12.5px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;cursor:pointer;opacity:.95}
    .hpx-ed{position:absolute;left:0;right:0;z-index:60;background:var(--surface);border-bottom:1px solid var(--border);box-shadow:0 18px 44px rgba(15,23,42,.08)}
    .hpx-ed-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr)) minmax(240px,340px);gap:34px;padding:34px 0}
    .hpx-ed h5{margin:0 0 14px;color:var(--muted);font-size:10.5px;font-weight:800;letter-spacing:.18em;text-transform:uppercase}
    .hpx-ed a.hpx-ed-link{display:block;padding:6px 0;color:var(--text);font-family:var(--font-title);font-size:14.5px;text-decoration:none}
    .hpx-ed a.hpx-ed-link:hover{color:var(--accent,var(--primary))}
    .hpx-ed-media{position:relative;display:block;border-radius:var(--radius-md);overflow:hidden;min-height:230px;background:linear-gradient(135deg,color-mix(in srgb,var(--accent,var(--primary)) 30%,#fff),color-mix(in srgb,var(--accent,var(--primary)) 8%,#fff));text-decoration:none}
    .hpx-ed-media img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
    .hpx-ed-media:before{content:'';position:absolute;inset:0;z-index:1;background:linear-gradient(180deg,transparent 40%,rgba(10,14,22,.55))}
    .hpx-ed-media-copy{position:absolute;left:0;right:0;bottom:0;z-index:2;padding:18px;color:#fff}
    .hpx-ed-media-copy strong{display:block;font-family:var(--font-title);font-size:19px}
    .hpx-ed-media-copy small{display:block;margin-top:4px;opacity:.85}
    .hpx-ed-media-copy span{display:inline-block;margin-top:9px;font-size:12px;font-weight:800;text-decoration:underline}
    @media(max-width:960px){.hpx-ed{display:none}}
    /* Search panel (Search First) */
    .hpx-search-panel{display:grid;grid-template-columns:minmax(0,1fr) 220px;gap:0}
    .hpx-sp-title{margin:0;padding:10px 14px 4px;color:var(--muted);font-size:10.5px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
    .hpx-sp-cats{border-left:1px solid var(--border);padding-bottom:8px}
    .hpx-sp-cats a{display:block;padding:8px 14px;color:var(--text);font-size:13px;text-decoration:none}
    .hpx-sp-cats a:hover{color:var(--primary);background:var(--surface-soft)}
    @media(max-width:700px){.hpx-search-panel{grid-template-columns:1fr}.hpx-sp-cats{border-left:0;border-top:1px solid var(--border)}}
    /* Móvil: diferenciación del drawer por preset (orden de bloques) */
    @media(max-width:960px){
        .hpx-logo-center{display:flex}
        .hpx-logo-center .hpx-aux{display:none}
        body.hp-multiverse .mobile-nav-profiles{order:-1}
        body.hp-search-first .mobile-nav-panel .mobile-nav-links{order:2}
        body.hp-commercial .mobile-nav-panel:after{content:'';display:block}
    }

    /* ---- Fijado del encabezado (header_sticky_mode) -------------------------
       all    = barra superior + encabezado + menu bajan juntos
       header = solo el encabezado; el menu se repliega al bajar
       menu   = solo la barra de menu; el encabezado se repliega al bajar
       El repliegue usa max-height para que la transicion sea suave y para no
       sacar los nodos del DOM (el carrito y el buscador siguen siendo Alpine). */
    .hpx-st-top{position:sticky;top:0;z-index:61}
    .header-zone.hpx-st-all{position:sticky;top:var(--hpx-tb-h,0px);z-index:60}
    /* "Solo el menu" ya no pliega nada.
       Antes: la zona entera quedaba fija y la fila del logo se replegaba con una
       animacion de max-height + opacity. Durante la animacion la fila seguia
       ocupando su sitio a media opacidad y se veia un encabezado doble y
       fantasma; ademas, al desaparecer, la pagina se acortaba de golpe, el scroll
       volvia a cruzar el umbral y el ciclo se repetia sin parar.
       Ahora la fila del logo simplemente se va con la pagina, como cualquier
       contenido, y solo la fila de navegacion se queda pegada arriba. Sin
       animacion, sin salto de altura y sin nada que oscilar. */
    /* La zona se pega al viewport pero desplazada hacia arriba justo lo que miden
       las filas que hay por encima del menu (--hpx-sobre-nav). Resultado: esas
       filas se van con la pagina y la de navegacion queda clavada arriba. Todo
       CSS, sin animacion y sin que cambie la altura del documento. */
    .header-zone.hpx-st-menu{position:sticky;top:calc(-1 * var(--hpx-sobre-nav,0px));z-index:60}
    .header-zone.hpx-st-menu .category-nav{background:var(--header-bg,#fff);
        box-shadow:0 2px 12px rgba(15,23,42,.06)}
    .header-zone.hpx-st-header{position:sticky;top:0;z-index:60;
        box-shadow:0 2px 12px rgba(15,23,42,.06)}
    .header-zone.hpx-st-header .category-nav{transition:none}
    .header-zone.hpx-st-header.is-shrunk .category-nav{display:none}
    /* .header-main/.hpx-main declaran min-height y padding propios: sin anularlos
       el bloque conserva su alto y el repliegue no se nota. */
    .header-zone.hpx-st-menu.is-shrunk .store-header{min-height:0}
    .header-zone.hpx-st-menu.is-shrunk .store-header .header-main,
    .header-zone.hpx-st-menu.is-shrunk .store-header .hpx-main{min-height:0;padding-top:0;padding-bottom:0}
    .header-zone.hpx-st-header.is-shrunk .category-nav>*{padding-top:0;padding-bottom:0}
    /* Acciones compactas: solo aparecen cuando el encabezado esta replegado. */
    /* Ofertas y Novedades son los accesos que mas convierten: se les da color de
       marca y un fondo suave para que destaquen sobre el resto del menu. */
    .hpx-nav-actions>a[href*="filter=sale"],
    .hpx-nav-actions>a[href*="filter=new"]{padding:7px 13px;border-radius:999px;font-weight:800}
    /* Rojo y verde fijos rompian la paleta de cada tienda: en una muebleria
       marino y dorado, un chip rojo abarata. Ofertas toma el color de oferta y
       Novedades el de marca, ambos configurables desde el constructor. */
    /* Esta regla es anterior al sistema de accesos configurables y selecciona
       por el `href`, lo que le da MÁS especificidad (0,2,1) que las clases de
       estilo (0,2,0): el acceso de ofertas se quedaba pálido por mucho que se
       eligiera "botón sólido" en el constructor. Se excluye de los chips. */
    .hpx-nav-actions>a[href*="filter=sale"]:not(.hpx-nav-chip){color:var(--sale,#dc2626);background:color-mix(in srgb,var(--sale,#dc2626) 10%,transparent)}
    .hpx-nav-actions>a[href*="filter=sale"]:not(.hpx-nav-chip):hover{background:color-mix(in srgb,var(--sale,#dc2626) 18%,transparent)}
    {{-- :not(.hpx-nav-chip): sin él, este estilo histórico pisaba la tinta de
         las ranuras configuradas y un chip sólido azul quedaba azul sobre azul
         (mismo bug que ya se corrigió con filter=sale). --}}
    .hpx-nav-actions>a[href*="filter=new"]:not(.hpx-nav-chip){color:var(--primary);background:color-mix(in srgb,var(--primary) 9%,transparent)}
    .hpx-nav-actions>a[href*="filter=new"]:not(.hpx-nav-chip):hover{background:color-mix(in srgb,var(--primary) 16%,transparent)}
    /* ── ZONAS TACTILES ─────────────────────────────────────────────────────
       Medido en produccion: WhatsApp del menu tenia 16px de alto, el telefono
       15px y los iconos de redes 26px. Por debajo de 44px el dedo falla y el
       cliente abandona. Se amplia el area de toque con padding, sin cambiar el
       tamano visual del texto ni del icono. */
    .hpx-topbar a,
    .hpx-nav-actions > a,
    .category-list a,
    .mega-btn,
    .hpx-searchgo,
    .hpx-cart{min-height:44px;display:inline-flex;align-items:center}
    /* Las redes quedaban FUERA de esta lista a proposito. El area de toque de
       44px se aplicaba al propio circulo y lo inflaba de 26 a 44px: tres botones
       enormes en una franja de 12,5px de texto. El area se gana con un
       pseudo-elemento invisible, que es lo que pide la pauta (objetivo de 44px)
       sin tocar el tamano del circulo. */
    /* Tamaño y separación de las redes, configurables. Antes eran fijos y en una
       franja fina de 12,5px se veían desproporcionadas. */
    .hpx-topbar-social{gap:var(--soc-gap,10px)}
    .hpx-topbar-social a{position:relative;width:var(--soc-size,26px);height:var(--soc-size,26px);min-width:0;min-height:0;justify-content:center}
    .hpx-topbar-social a svg{width:calc(var(--soc-size,26px) * .55);height:calc(var(--soc-size,26px) * .55)}
    /* Estilo "sin relleno": el icono en el color del texto, sin círculo. Encaja
       mejor cuando la franja ya tiene color propio y tres círculos de marca la
       ensucian. */
    .hpx-topbar.soc-plano .hpx-topbar-social a{background:none!important;box-shadow:none!important;color:inherit!important;width:auto;height:auto}
    .hpx-topbar.soc-plano .hpx-topbar-social a svg{width:var(--soc-size,18px);height:var(--soc-size,18px)}
    .hpx-topbar-social a::after{content:'';position:absolute;top:50%;left:50%;width:44px;height:44px;transform:translate(-50%,-50%)}
    .hpx-topbar-social a svg{width:14px;height:14px}
    .hpx-nav-actions > a{padding-top:0;padding-bottom:0}
    @media(max-width:960px){
        /* En movil manda el area de toque: los enlaces del menu desplegable
           tambien pasan a 44px. */
        .mobile-nav-panel a,
        .mobile-nav-links a,
        .hpx-searchcats-menu a{min-height:44px;display:flex;align-items:center}
    }
    .hpx-nav-mini{display:none;place-items:center;width:34px;height:34px;padding:0;position:relative;
        background:transparent;border:0;color:inherit;cursor:pointer}
    .hpx-nav-mini svg{width:19px;height:19px}
    .hpx-nav-mini .cart-count{position:absolute;top:-3px;right:-4px;display:grid;place-items:center;
        min-width:17px;height:17px;border-radius:9px;background:var(--accent,var(--primary));color:#fff;
        font-size:10px;font-weight:700}
    .header-zone.hpx-st-menu.is-shrunk .hpx-nav-mini{display:grid}
    @media(max-width:960px){.hpx-nav-mini{display:none!important}}
    /* El carrito se perdia sobre encabezados de color: va sobre disco blanco. */
    .hpx-cart-ico{width:42px;height:42px;border-radius:50%;background:#fff;
        color:var(--secondary,#0B2038);box-shadow:0 2px 10px rgba(15,23,42,.16);
        transition:transform .18s ease,box-shadow .18s ease}
    .hpx-cart:hover .hpx-cart-ico{transform:translateY(-1px);box-shadow:0 6px 16px rgba(15,23,42,.22)}
    .hpx-cart .cart-count{top:-4px;right:-4px;display:grid;place-items:center;border-radius:9px;
        border:2px solid #fff;font-weight:700}
</style>

<script>
/* Fijado del encabezado. La altura de la barra superior se mide en vivo porque
   cambia con el contenido y con el ancho; sin ella el encabezado se solaparia. */
(function () {
    var zone = document.querySelector('.header-zone.hpx-st-all,.header-zone.hpx-st-header,.header-zone.hpx-st-menu');
    if (!zone) return;
    var top = document.querySelector('.hpx-st-top');
    var nav = zone.querySelector('.category-nav');
    var setTop = function () {
        document.documentElement.style.setProperty('--hpx-tb-h', (top ? top.offsetHeight : 0) + 'px');
        // Alto de todo lo que hay por encima del menu. Se mide al cargar y al
        // redimensionar, nunca al desplazar: no hay trabajo por scroll ni nada
        // que pueda entrar en bucle.
        if (nav) {
            var sobre = Math.max(0, zone.offsetHeight - nav.offsetHeight);
            zone.style.setProperty('--hpx-sobre-nav', sobre + 'px');
        }
    };
    // "Solo el menu" ya no necesita JS: la fila de navegacion es sticky por CSS.
    var shrinkable = zone.classList.contains('hpx-st-header');
    // Un unico umbral (90px) hacia que el encabezado no parara de moverse: al
    // plegarse la pagina se acorta, el scroll vuelve a bajar del umbral, se
    // despliega, la pagina crece y vuelve a cruzarlo. Bucle. Con histeresis
    // (pliega a 160, despliega a 60) el rango muerto rompe la oscilacion.
    var plegado = false, pendiente = false;
    var evaluar = function () {
        pendiente = false;
        var y = window.scrollY || window.pageYOffset || 0;
        if (!plegado && y > 160) { plegado = true; zone.classList.add('is-shrunk'); }
        else if (plegado && y < 60) { plegado = false; zone.classList.remove('is-shrunk'); }
    };
    var onScroll = function () {
        if (!shrinkable || pendiente) return;
        pendiente = true;
        window.requestAnimationFrame(evaluar);
    };
    setTop(); onScroll();
    window.addEventListener('resize', setTop, { passive: true });
    window.addEventListener('scroll', onScroll, { passive: true });
})();
</script>
