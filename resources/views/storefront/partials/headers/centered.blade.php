{{-- ═══════════════════════════════════════════════════════════════════
     CABECERA LOGO CENTRADO (variante estructural F1)
     Fila 1: buscador (izq) · LOGO CENTRADO · teléfono + carrito (der).
     Fila 2: menú centrado con categorías desplegables. Estilo editorial.
     Reusa el Alpine del padre (query/suggest/cart/mobileNav) y las
     variables compartidas ($menuRoots, $navCategories, $activeDest…).
     ═══════════════════════════════════════════════════════════════════ --}}
@if($announcementShow)
<div class="topbar topbar--{{ $announcementAlign }}" style="background:{{ $announcementBg }};color:{{ $announcementColor }};font-size:{{ $announcementFontSize }}px">
    <div class="{{ $announcementFull ? 'topbar-full' : 'container' }} topbar-inner">
        <p style="color:{{ $announcementColor }}">{{ $announcementText ?: 'Atención especializada para tu compra' }}</p>
    </div>
</div>
@endif

<style>
    .hdr-centered{position:relative;z-index:50;background:var(--header-bg);border-bottom:1px solid var(--border)}
    .hdc-top{width:min(1360px,calc(100% - 40px));margin:0 auto;min-height:86px;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:18px}
    .hdc-left{display:flex;align-items:center;gap:6px;justify-self:start}
    .hdc-right{display:flex;align-items:center;gap:14px;justify-self:end}
    .hdc-brand{justify-self:center;display:flex;align-items:center;color:var(--header-text);text-decoration:none;font-weight:800;font-size:22px;letter-spacing:-.02em}
    .hdc-brand img{height:auto!important;max-height:56px!important;width:auto!important;max-width:230px!important;object-fit:contain}
    .hdc-phone{text-align:right;line-height:1.25}
    .hdc-phone small{display:block;color:color-mix(in srgb,var(--header-text) 55%,transparent);font-size:10.5px;letter-spacing:.08em;text-transform:uppercase}
    .hdc-phone strong{color:var(--header-text);font-size:14px}
    .hdc-icon{width:42px;height:42px;display:grid;place-items:center;color:var(--header-text);background:transparent;border:0;border-radius:999px;cursor:pointer;position:relative}
    .hdc-icon:hover{background:color-mix(in srgb,var(--header-text) 8%,transparent)}
    .hdc-icon svg{width:20px;height:20px}
    .hdc-count{position:absolute;top:2px;right:0;min-width:17px;height:17px;display:grid;place-items:center;padding:0 4px;background:var(--primary);color:#fff;border-radius:999px;font-size:10px;font-weight:800}
    .hdc-nav{display:flex;justify-content:center;align-items:center;gap:6px;padding:0 20px 14px;flex-wrap:wrap}
    .hdc-link{border:0;background:transparent;cursor:pointer;font-family:inherit;padding:7px 14px;color:var(--header-text);font-size:12.5px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;text-decoration:none;border-bottom:2px solid transparent;transition:border-color .15s,color .15s}
    .hdc-link:hover{color:var(--primary)}
    .hdc-link.is-active{color:var(--primary);border-bottom-color:var(--primary)}
    .hdc-cats{position:relative}
    .hdc-cats-panel{position:absolute;top:calc(100% + 10px);left:50%;transform:translateX(-50%);z-index:60;min-width:250px;padding:8px;background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:0 18px 44px rgba(15,23,42,.14)}
    .hdc-cats-panel a{display:block;padding:9px 13px;border-radius:8px;color:#334155;font-size:13.5px;letter-spacing:0;text-transform:none;text-decoration:none}
    .hdc-cats-panel a:hover{background:#f7f7f5;color:var(--primary)}
    .hdc-search{position:absolute;inset:0;z-index:6;display:flex;align-items:center;background:var(--header-bg)}
    .hdc-search-inner{width:min(760px,calc(100% - 40px));margin:0 auto;display:flex;align-items:center;gap:10px;position:relative}
    .hdc-search input{flex:1;min-height:46px;padding:0 18px;border:1px solid var(--border);border-radius:4px;font-size:14px;background:#fff;color:#0f172a}
    .hdc-search .search-suggest{top:calc(100% + 6px)}
    .hdr-centered .profile-switch{display:flex;justify-content:center;gap:8px;padding:0 0 12px}
    .hdc-burger{display:none}
    @media(max-width:900px){
        .hdc-nav{display:none}.hdc-burger{display:grid}
        .hdc-top{min-height:62px;grid-template-columns:auto 1fr auto}
        .hdc-brand img{max-height:40px!important}
        .hdc-phone{display:none}
    }
</style>

<div class="header-zone hdr-centered {{ $headerStickyMode !== 'none' ? 'is-sticky' : '' }}">
    <header>
        <div class="hdc-top">
            <div class="hdc-left">
                <button class="hdc-icon hdc-burger" type="button" @click="mobileNav=true" aria-label="Abrir menú">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <button class="hdc-icon" type="button" @click="searchOpen=!searchOpen" aria-label="Buscar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                </button>
            </div>

            <a class="hdc-brand" href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, new \App\Models\StoreMenuItem(['destination_type' => 'home'])) }}">
                @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $storeName }}">@if(($settings['logo_wordmark'] ?? '0') === '1')<span class="brand-wordmark">{{ $settings['logo_wordmark_text'] ?? $storeName }}</span>@endif@else<span>{{ $storeName }}</span>@endif
            </a>

            <div class="hdc-right">
                @if($phone)
                <div class="hdc-phone"><small>Atención comercial</small><strong>{{ $phone }}</strong></div>
                @endif
                <button class="hdc-icon" type="button" @click="openCartPage()" aria-label="Abrir carrito">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 3h2l2.1 11.2a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 2-1.6L21 7H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                    <span class="hdc-count" x-text="itemCount()" aria-live="polite">0</span>
                </button>
            </div>

            {{-- Buscador expandible centrado: mismo estado del núcleo --}}
            <div class="hdc-search" x-show="searchOpen" x-cloak x-transition.opacity.duration.150ms @keydown.escape.window="searchOpen=false">
                <div class="hdc-search-inner">
                    <input type="search" x-model="query" @input.debounce.250ms="fetchSuggest()"
                           @focus="suggest.length && (suggestOpen=true)" @keydown.enter.prevent="goSearch()"
                           placeholder="{{ $txtSearchPlaceholder }}" autocomplete="off" aria-label="Buscar productos"
                           x-ref="hdcSearch" x-effect="searchOpen && $nextTick(() => $refs.hdcSearch?.focus())">
                    <button class="hdc-icon" type="button" @click="goSearch()" aria-label="Buscar ahora">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    </button>
                    <button class="hdc-icon" type="button" @click="searchOpen=false" aria-label="Cerrar búsqueda">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="m6 6 12 12M18 6 6 18"/></svg>
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
            </div>
        </div>

        <nav class="hdc-nav" aria-label="Navegación principal" x-data="{ catsOpen: false }" @click.outside="catsOpen=false">
            @forelse($menuRoots as $item)
            <a class="hdc-link {{ ($item->destination_type ?? null) === $activeDest ? 'is-active' : '' }}"
               href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, $item) }}">{{ $item->label }}</a>
            @empty
            <a class="hdc-link {{ $activeDest === 'home' ? 'is-active' : '' }}" href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, new \App\Models\StoreMenuItem(['destination_type' => 'home'])) }}">Inicio</a>
            <a class="hdc-link {{ $activeDest === 'shop' ? 'is-active' : '' }}" href="{{ $shopBase }}">Tienda</a>
            @endforelse
            @if($navCategories->count())
            <span class="hdc-cats">
                <button type="button" class="hdc-link" @click="catsOpen=!catsOpen" :aria-expanded="catsOpen.toString()">Categorías ▾</button>
                <div class="hdc-cats-panel" x-show="catsOpen" x-cloak>
                    @foreach($navCategories->take(10) as $cat)
                    <a href="{{ $shopBase }}?category={{ $cat->id }}">{{ $cat->name }}</a>
                    @endforeach
                </div>
            </span>
            @endif
        </nav>
    </header>

    @if(!empty($catalogProfiles) && $catalogProfiles->count())
    <div class="profile-switch" role="group" aria-label="Colecciones de la tienda">
        @foreach($catalogProfiles as $cp)
        <a class="profile-chip {{ (!empty($activeProfile) && $activeProfile->id === $cp->id) ? 'is-active' : '' }}"
           @if($cp->primary_color) style="--chip-color:{{ $cp->primary_color }}" @endif
           href="{{ route('public.shop.profile', [$project->slug, $cp->slug]) }}"><span class="profile-dot" aria-hidden="true"></span>{{ $cp->menu_label ?: $cp->name }}</a>
        @endforeach
    </div>
    @endif
</div>{{-- /header-zone centrada --}}

<style>
  /* Titulo junto al logo. Apagado por defecto: donde va el logo no se repite
     el nombre, salvo que el negocio lo pida. */
  /* El nombre no puede empujar al buscador: se acota y, si no cabe, se corta
     con puntos suspensivos. Por debajo de 1100 px desaparece — ahi el espacio
     es del buscador y del carrito, que son lo que la gente usa. */
  .brand-wordmark{margin-left:10px;font-family:var(--font-title,inherit);font-size:20px;
                  font-weight:700;color:currentColor;line-height:1.1;white-space:nowrap;
                  max-width:min(30vw,260px);overflow:hidden;text-overflow:ellipsis}
  @media(max-width:1100px){.brand-wordmark{display:none}}
</style>
