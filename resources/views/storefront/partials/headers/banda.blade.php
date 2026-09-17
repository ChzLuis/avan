{{-- ═══════════════════════════════════════════════════════
     CABECERA "BANDA CORPORATIVA" (header_layout = banda)
     Tres franjas de poco alto, hermana del pie "Una banda":
       1. Cinta fina: lema a la izquierda y sellos de confianza a la derecha.
       2. Fila principal: logo, buscador ancho al centro, cotización/carrito
          y el botón de WhatsApp en verde.
       3. Fila de enlaces centrada, con subrayado bajo el activo.
     Reusa el estado Alpine del padre (query/suggest/cart/mobileNav) y las
     variables compartidas ($menuRoots, $navCategories, $activeDest…).
     ═══════════════════════════════════════════════════════ --}}
@php
    $hbLema = trim((string) ($settings['header_tagline'] ?? $settings['announcement_text'] ?? ''))
        ?: trim((string) ($tagline ?? ''));
    // Sellos de la derecha: se escriben separados por "|" y si no hay, no se
    // inventa nada (una cinta con texto de relleno delata la plantilla).
    $hbSellos = array_values(array_filter(array_map('trim',
        explode('|', (string) ($settings['header_badges'] ?? '')))));
    $hbWa = preg_replace('/\D/', '', (string) ($settings['quote_whatsapp'] ?? $phone ?? ''));
    if ($hbWa && ! str_starts_with($hbWa, '51')) { $hbWa = '51'.$hbWa; }
    $hbCotiza = ($settings['store_mode'] ?? 'direct') === 'quote';
@endphp
<style>
    .hba{position:relative;z-index:50;background:var(--header-bg);color:var(--header-text)}
    /* 1. Cinta superior */
    .hba-cinta{background:color-mix(in srgb,var(--header-bg) 82%,#000);color:color-mix(in srgb,var(--header-text) 88%,transparent);font-size:12px}
    .hba-cinta-in{width:min(1400px,calc(100% - 48px));margin:0 auto;min-height:32px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
    .hba-sellos{display:flex;align-items:center;gap:0}
    .hba-sellos span{display:inline-flex;align-items:center;gap:6px;padding:0 14px;border-left:1px solid color-mix(in srgb,currentColor 26%,transparent)}
    .hba-sellos span:first-child{border-left:0}
    .hba-sellos svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2}

    /* 2. Fila principal */
    .hba-main{width:min(1400px,calc(100% - 48px));margin:0 auto;min-height:66px;display:flex;align-items:center;gap:26px}
    .hba-brand{display:flex;align-items:center;gap:10px;color:inherit;text-decoration:none;font-weight:800;letter-spacing:-.02em;flex:0 0 auto}
    .hba-brand img{height:auto!important;max-height:44px!important;width:auto!important;max-width:170px!important;object-fit:contain}
    .hba-search{position:relative;flex:1 1 auto;max-width:560px;margin:0 auto}
    .hba-search input{width:100%;height:40px;padding:0 44px 0 16px;border:1px solid color-mix(in srgb,var(--header-text) 26%,transparent);border-radius:8px;background:color-mix(in srgb,var(--header-text) 7%,transparent);color:inherit;font-size:13.5px;outline:none}
    .hba-search input::placeholder{color:color-mix(in srgb,currentColor 62%,transparent)}
    .hba-search input:focus{border-color:var(--primary);background:color-mix(in srgb,var(--header-text) 12%,transparent)}
    .hba-search-btn{position:absolute;right:6px;top:6px;width:28px;height:28px;border:0;border-radius:6px;background:transparent;color:inherit;display:grid;place-items:center;cursor:pointer}
    .hba-search-btn svg{width:17px;height:17px;stroke:currentColor;fill:none;stroke-width:2}
    .hba-acciones{display:flex;align-items:center;gap:12px;flex:0 0 auto}
    .hba-btn{display:inline-flex;align-items:center;gap:8px;height:38px;padding:0 15px;border:1px solid color-mix(in srgb,var(--header-text) 26%,transparent);border-radius:8px;background:transparent;color:inherit;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;white-space:nowrap}
    .hba-btn:hover{background:color-mix(in srgb,var(--header-text) 10%,transparent)}
    .hba-btn svg{width:17px;height:17px;stroke:currentColor;fill:none;stroke-width:1.8}
    .hba-btn.wa{background:#25d366;border-color:#25d366;color:#07301a}
    .hba-btn.wa:hover{background:#1fbe5b;border-color:#1fbe5b}
    .hba-btn.wa svg{fill:currentColor;stroke:none}
    .hba-burger{display:none;width:38px;height:38px;border:0;background:transparent;color:inherit;cursor:pointer;place-items:center}
    .hba-burger svg{width:22px;height:22px;stroke:currentColor;fill:none;stroke-width:2}

    /* 3. Fila de enlaces */
    .hba-nav{border-top:1px solid color-mix(in srgb,var(--header-text) 16%,transparent)}
    .hba-nav{position:relative}
    .hba-nav-in{width:min(1400px,calc(100% - 48px));margin:0 auto;display:flex;align-items:center;justify-content:center;}
.hba-nav-in.has-cats-left{justify-content:flex-start;gap:6px 26px}
.hba-nav-in{gap:6px 30px;flex-wrap:wrap;min-height:42px}
    .hba-link{position:relative;padding:11px 2px;color:inherit;font-size:13.5px;font-weight:600;text-decoration:none;white-space:nowrap;opacity:.88;border:0;background:none;font-family:inherit;line-height:inherit;cursor:pointer}
    .hba-link:hover{opacity:1}
    .hba-link.is-active{opacity:1;font-weight:700}
    .hba-link.is-active::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:3px;border-radius:3px 3px 0 0;background:var(--primary)}

    @media(max-width:1024px){.hba-cinta{display:none}.hba-nav{display:none}.hba-burger{display:grid}.hba-main{gap:14px;min-height:58px}}
    @media(max-width:640px){.hba-search{display:none}.hba-acciones .hba-btn span{display:none}.hba-acciones .hba-btn{padding:0 11px}}
</style>

<div class="hba">
    @if($hbLema !== '' || $hbSellos)
    <div class="hba-cinta"><div class="hba-cinta-in">
        <span>{{ $hbLema }}</span>
        @if($hbSellos)
        <span class="hba-sellos">
            @foreach($hbSellos as $sello)
            <span><svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>{{ $sello }}</span>
            @endforeach
        </span>
        @endif
    </div></div>
    @endif

    <header class="hba-main">
        <button class="hba-burger" type="button" @click="mobileNav=true" aria-label="Abrir menú">
            <svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <a class="hba-brand" href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, new \App\Models\StoreMenuItem(['destination_type' => 'home'])) }}">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $storeName }}">@if(($settings['logo_wordmark'] ?? '0') === '1')<span class="brand-wordmark">{{ $settings['logo_wordmark_text'] ?? $storeName }}</span>@endif
            @else<span>{{ $storeName }}</span>@endif
        </a>

        <div class="hba-search">
            <input type="search" x-model="query" @input.debounce.250ms="fetchSuggest()"
                   @focus="suggest.length && (suggestOpen=true)" @keydown.enter.prevent="goSearch()"
                   placeholder="{{ $txtSearchPlaceholder ?? 'Buscar productos, marcas o códigos…' }}" autocomplete="off" aria-label="Buscar productos">
            <button class="hba-search-btn" type="button" @click="goSearch()" aria-label="Buscar">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            </button>
            <div class="search-suggest" x-show="suggestOpen" x-cloak role="listbox" @click.stop>
                <template x-for="sp in suggest" :key="sp.id">
                    <a class="search-suggest-item" :href="sp.url" role="option">
                        <img :src="sp.image" alt="" loading="lazy"><span x-text="sp.name"></span><strong x-text="sp.price"></strong>
                    </a>
                </template>
                <button type="button" class="search-suggest-all" @click="goSearch()">Ver todos los resultados <span aria-hidden="true">→</span></button>
            </div>
        </div>

        <div class="hba-acciones">
            <button class="hba-btn" type="button" @click="openCartPage()" aria-label="{{ $hbCotiza ? 'Ver mi cotización' : 'Abrir carrito' }}">
                <svg viewBox="0 0 24 24"><path d="M3 3h2l2.1 11.2a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 2-1.6L21 7H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                <span>{{ $hbCotiza ? 'Mi cotización' : 'Carrito' }} (<span x-text="itemCount()">0</span>)</span>
            </button>
            @if($hbWa)
            <a class="hba-btn wa" href="https://wa.me/{{ $hbWa }}" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.5-.6c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.2.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.2-.3-.3-.6-.4zM12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2z"/></svg>
                <span>WhatsApp</span>
            </a>
            @endif
        </div>
    </header>

    <nav class="hba-nav" aria-label="Navegación principal" x-data="{ catsOpen: {{ request('abrir') === 'categorias' ? 'true' : 'false' }} }" @mouseleave="catsOpen=false" @click.outside="catsOpen=false">
        @php
            // Boton de categorias: posicion, estilo y texto desde el Constructor.
            // Sin ajuste explicito se respeta lo que la banda hacia: panel mega
            // si `mega_enabled`, lista simple si no. "Oculto" apaga todo.
            $hbaCatsPos = in_array($settings['hp_cats_pos'] ?? '', ['izquierda', 'derecha', 'oculto'], true) ? $settings['hp_cats_pos'] : 'izquierda';
            $hbaTrig = (string) ($settings['hp_cat_trigger'] ?? '');
            $hbaModo = in_array($hbaTrig, ['mega', 'lista', 'none'], true)
                ? $hbaTrig
                : ((($settings['mega_enabled'] ?? '0') === '1') ? 'mega' : 'lista');
            if ($hbaCatsPos === 'oculto' || ! $navCategories->count()) $hbaModo = 'none';
            $hbaMega = $hbaModo === 'mega';
        @endphp
        <div class="hba-nav-in{{ $hbaModo !== 'none' && $hbaCatsPos === 'izquierda' ? ' has-cats-left' : '' }}">
            {{-- A la IZQUIERDA, integrado con el menu, y no como un enlace de
                 texto perdido al final: era lo que hacia ver muerta esta barra. --}}
            @if($hbaModo !== 'none' && $hbaCatsPos === 'izquierda')
                @include('storefront.partials.nav.cats-button', ['modo' => $hbaModo, 'pos' => $hbaCatsPos, 'var' => 'catsOpen'])
            @endif
            @forelse($menuRoots as $item)
            <a class="hba-link {{ ($item->destination_type ?? null) === $activeDest ? 'is-active' : '' }}"
               href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, $item) }}">{{ $item->label }}</a>
            @empty
            <a class="hba-link {{ $activeDest === 'home' ? 'is-active' : '' }}" href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, new \App\Models\StoreMenuItem(['destination_type' => 'home'])) }}">Inicio</a>
            <a class="hba-link {{ $activeDest === 'shop' ? 'is-active' : '' }}" href="{{ $shopBase }}">Productos</a>
            @endforelse

            @if($hbaModo !== 'none' && $hbaCatsPos === 'derecha')
                @include('storefront.partials.nav.cats-button', ['modo' => $hbaModo, 'pos' => $hbaCatsPos, 'var' => 'catsOpen'])
            @endif
        </div>
        @if($hbaMega)
            @include('storefront.partials.nav.mega-catalogo')
        @endif
    </nav>
</div>
