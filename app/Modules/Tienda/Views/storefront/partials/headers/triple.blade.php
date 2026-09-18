{{-- ═══════════════════════════════════════════════════════════════════
     CABECERA TRI-FILA (variante estructural F1) — estilo marketplace
     Fila 1: anuncio + contacto (tel/correo). Fila 2: logo · BUSCADOR
     GRANDE central · teléfono + carrito. Fila 3: barra de categorías
     con "Todas las categorías" + menú. Ideal para catálogos técnicos.
     Reusa el Alpine del padre y las variables compartidas.
     ═══════════════════════════════════════════════════════════════════ --}}
<style>
    .hdr-triple{position:relative;z-index:50;background:var(--header-bg)}
    .ht3-info{background:color-mix(in srgb,var(--header-text) 94%,var(--header-bg));color:#fff;font-size:11.5px}
    .ht3-info-inner{width:min(1360px,calc(100% - 40px));margin:0 auto;min-height:30px;display:flex;align-items:center;justify-content:space-between;gap:14px}
    .ht3-info a{color:inherit;text-decoration:none;opacity:.85}.ht3-info a:hover{opacity:1}
    .ht3-info .ht3-contact{display:flex;gap:16px}
    .ht3-main{width:min(1360px,calc(100% - 40px));margin:0 auto;min-height:74px;display:flex;align-items:center;gap:24px}
    .ht3-brand{display:flex;align-items:center;color:var(--header-text);text-decoration:none;font-weight:800;font-size:20px;flex:0 0 auto}
    .ht3-brand img{height:auto!important;max-height:48px!important;width:auto!important;max-width:190px!important;object-fit:contain}
    .ht3-search{flex:1 1 auto;position:relative;display:flex}
    .ht3-search input{flex:1;min-height:46px;padding:0 52px 0 18px;border:2px solid var(--primary);border-radius:8px;font-size:14px;background:#fff;color:#0f172a}
    .ht3-search-btn{position:absolute;top:3px;right:3px;bottom:3px;width:46px;display:grid;place-items:center;background:var(--primary);color:#fff;border:0;border-radius:6px;cursor:pointer}
    .ht3-search-btn svg{width:19px;height:19px}
    .ht3-search .search-suggest{top:calc(100% + 6px)}
    .ht3-right{display:flex;align-items:center;gap:16px;flex:0 0 auto}
    .ht3-phone{text-align:right;line-height:1.25}
    .ht3-phone small{display:block;color:color-mix(in srgb,var(--header-text) 55%,transparent);font-size:10.5px;letter-spacing:.06em;text-transform:uppercase}
    .ht3-phone strong{color:var(--header-text);font-size:14px}
    .ht3-icon{width:42px;height:42px;display:grid;place-items:center;color:var(--header-text);background:transparent;border:0;border-radius:10px;cursor:pointer;position:relative}
    .ht3-icon:hover{background:color-mix(in srgb,var(--header-text) 8%,transparent)}
    .ht3-icon svg{width:20px;height:20px}
    .ht3-count{position:absolute;top:2px;right:0;min-width:17px;height:17px;display:grid;place-items:center;padding:0 4px;background:var(--primary);color:#fff;border-radius:999px;font-size:10px;font-weight:800}
    .ht3-catbar{background:var(--header-text);color:#fff}
    .ht3-catbar-inner{width:min(1360px,calc(100% - 40px));margin:0 auto;min-height:44px;display:flex;align-items:center;gap:4px}
    .ht3-allcats{position:relative;flex:0 0 auto}
    .ht3-allcats-btn{display:flex;align-items:center;gap:9px;min-height:44px;padding:0 16px;background:var(--primary);color:#fff;border:0;font-size:13px;font-weight:800;cursor:pointer}
    .ht3-allcats-btn svg{width:16px;height:16px}
    .ht3-allcats-panel{position:absolute;top:100%;left:0;z-index:60;min-width:260px;padding:8px;background:#fff;border:1px solid var(--border);border-radius:0 0 12px 12px;box-shadow:0 18px 44px rgba(15,23,42,.2)}
    .ht3-allcats-panel a{display:flex;justify-content:space-between;padding:10px 13px;border-radius:8px;color:#334155;font-size:13.5px;text-decoration:none}
    .ht3-allcats-panel a:hover{background:#f1f5f9;color:var(--primary)}
    .ht3-allcats-panel small{color:#94a3b8}
    .ht3-menu{display:flex;align-items:center;gap:2px;overflow-x:auto;scrollbar-width:none}
    .ht3-menu::-webkit-scrollbar{display:none}
    .ht3-link{padding:12px 14px;color:rgba(255,255,255,.85);font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap;border-bottom:3px solid transparent}
    .ht3-link:hover{color:#fff}
    .ht3-link.is-active{color:#fff;border-bottom-color:var(--primary)}
    .hdr-triple .profile-switch{display:flex;justify-content:center;gap:8px;padding:10px 0}
    .ht3-burger{display:none}
    @media(max-width:900px){
        .ht3-info,.ht3-menu,.ht3-phone{display:none}
        .ht3-burger{display:grid}
        .ht3-main{min-height:60px;gap:12px;flex-wrap:wrap;padding:8px 0}
        .ht3-search{order:3;flex-basis:100%;padding-bottom:8px}
        .ht3-catbar-inner{min-height:0}
        .ht3-allcats{display:none}
    }
</style>

<div class="header-zone hdr-triple {{ $headerStickyMode !== 'none' ? 'is-sticky' : '' }}">
    {{-- Fila 1: informativa --}}
    <div class="ht3-info">
        <div class="ht3-info-inner">
            <span>{{ $announcementShow ? ($announcementText ?: 'Atención especializada para tu compra') : ($settings['footer_tagline'] ?? '') }}</span>
            <span class="ht3-contact">
                @if($phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">📞 {{ $phone }}</a>@endif
                @if($email)<a href="mailto:{{ $email }}">✉ {{ $email }}</a>@endif
            </span>
        </div>
    </div>

    {{-- Fila 2: logo + buscador grande + acciones --}}
    <header class="ht3-main">
        <button class="ht3-icon ht3-burger" type="button" @click="mobileNav=true" aria-label="Abrir menú">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <a class="ht3-brand" href="{{ \App\Modules\Tienda\Support\StorefrontNavigation::resolveUrl($project, new \App\Modules\Tienda\Models\StoreMenuItem(['destination_type' => 'home'])) }}">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $storeName }}">@if(($settings['logo_wordmark'] ?? '0') === '1')<span class="brand-wordmark">{{ $settings['logo_wordmark_text'] ?? $storeName }}</span>@endif@else<span>{{ $storeName }}</span>@endif
        </a>

        <div class="ht3-search">
            <input type="search" x-model="query" @input.debounce.250ms="fetchSuggest()"
                   @focus="suggest.length && (suggestOpen=true)" @keydown.enter.prevent="goSearch()"
                   placeholder="{{ $txtSearchPlaceholder }}" autocomplete="off" aria-label="Buscar productos">
            <button class="ht3-search-btn" type="button" @click="goSearch()" aria-label="Buscar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
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

        <div class="ht3-right">
            @if($phone)
            <div class="ht3-phone"><small>Asesoría técnica</small><strong>{{ $phone }}</strong></div>
            @endif
            <button class="ht3-icon" type="button" @click="openCartPage()" aria-label="Abrir carrito">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 3h2l2.1 11.2a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 2-1.6L21 7H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                <span class="ht3-count" x-text="itemCount()" aria-live="polite">0</span>
            </button>
        </div>
    </header>

    {{-- Fila 3: barra de categorías + menú --}}
    <nav class="ht3-catbar" aria-label="Categorías y navegación" x-data="{ allOpen: false }" @click.outside="allOpen=false">
        <div class="ht3-catbar-inner">
            @if($navCategories->count())
            <div class="ht3-allcats">
                <button type="button" class="ht3-allcats-btn" @click="allOpen=!allOpen" :aria-expanded="allOpen.toString()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    Todas las categorías
                </button>
                <div class="ht3-allcats-panel" x-show="allOpen" x-cloak>
                    @foreach($navCategories->take(12) as $cat)
                    <a href="{{ $shopBase }}?category={{ $cat->id }}">{{ $cat->name }}
                        <small>{{ $cat->products->count() + $cat->children->sum(fn ($c) => $c->products->count()) }}</small></a>
                    @endforeach
                </div>
            </div>
            @endif
            <div class="ht3-menu">
                @forelse($menuRoots as $item)
                <a class="ht3-link {{ ($item->destination_type ?? null) === $activeDest ? 'is-active' : '' }}"
                   href="{{ \App\Modules\Tienda\Support\StorefrontNavigation::resolveUrl($project, $item) }}">{{ $item->label }}</a>
                @empty
                <a class="ht3-link {{ $activeDest === 'home' ? 'is-active' : '' }}" href="{{ \App\Modules\Tienda\Support\StorefrontNavigation::resolveUrl($project, new \App\Modules\Tienda\Models\StoreMenuItem(['destination_type' => 'home'])) }}">Inicio</a>
                <a class="ht3-link {{ $activeDest === 'shop' ? 'is-active' : '' }}" href="{{ $shopBase }}">Tienda</a>
                @endforelse
            </div>
        </div>
    </nav>

    @if(!empty($catalogProfiles) && $catalogProfiles->count())
    <div class="profile-switch" role="group" aria-label="Colecciones de la tienda">
        @foreach($catalogProfiles as $cp)
        <a class="profile-chip {{ (!empty($activeProfile) && $activeProfile->id === $cp->id) ? 'is-active' : '' }}"
           @if($cp->primary_color) style="--chip-color:{{ $cp->primary_color }}" @endif
           href="{{ route('public.shop.profile', [$project->slug, $cp->slug]) }}"><span class="profile-dot" aria-hidden="true"></span>{{ $cp->menu_label ?: $cp->name }}</a>
        @endforeach
    </div>
    @endif
</div>{{-- /header-zone tri-fila --}}

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
