{{-- ═══════════════════════════════════════════════════════════════════
     CABECERA COMPACTA (variante estructural F1)
     Una sola fila delgada: logo · menú inline · buscador expandible · carrito.
     Sin barra de categorías: las categorías van en un desplegable del menú.
     Reusa el MISMO estado Alpine del padre (query/suggest/cart/mobileNav) y
     las variables compartidas ($menuRoots, $navCategories, $activeDest…).
     ═══════════════════════════════════════════════════════════════════ --}}
@if($announcementShow)
<div class="topbar topbar--{{ $announcementAlign }}" style="background:{{ $announcementBg }};color:{{ $announcementColor }};font-size:{{ $announcementFontSize }}px">
    <div class="{{ $announcementFull ? 'topbar-full' : 'container' }} topbar-inner">
        <p style="color:{{ $announcementColor }}">{{ $announcementText ?: 'Atención especializada para tu compra' }}</p>
    </div>
</div>
@endif

<style>
    .hdr-compact{position:relative;z-index:50;background:var(--header-bg);border-bottom:1px solid var(--border)}
    .hdr-compact .hcp-row{width:min(1360px,calc(100% - 40px));margin:0 auto;min-height:60px;display:flex;align-items:center;gap:22px}
    .hcp-brand{display:flex;align-items:center;gap:10px;color:var(--header-text);text-decoration:none;font-weight:800;letter-spacing:-.02em;flex:0 0 auto}
    .hcp-brand img{height:auto!important;max-height:42px!important;width:auto!important;max-width:150px!important;object-fit:contain}
    .hcp-nav{display:flex;align-items:center;gap:4px;flex:1 1 auto;min-width:0;overflow-x:auto;scrollbar-width:none}
    .hcp-nav::-webkit-scrollbar{display:none}
    .hcp-link{padding:8px 12px;border-radius:8px;color:var(--header-text);font-size:13.5px;font-weight:600;text-decoration:none;white-space:nowrap;transition:background .15s}
    .hcp-link:hover{background:color-mix(in srgb,var(--header-text) 8%,transparent)}
    .hcp-link.is-active{color:var(--primary);background:rgba(255,255,255,.92);box-shadow:0 2px 8px rgba(15,23,42,.12)}
    .hcp-cats{position:relative}
    .hcp-cats-panel{position:absolute;top:calc(100% + 8px);left:0;z-index:60;min-width:230px;padding:8px;background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:0 18px 44px rgba(15,23,42,.14)}
    .hcp-cats-panel a{display:block;padding:9px 12px;border-radius:8px;color:#334155;font-size:13.5px;text-decoration:none}
    .hcp-cats-panel a:hover{background:#f1f5f9;color:var(--primary)}
    .hcp-actions{display:flex;align-items:center;gap:6px;flex:0 0 auto}
    .hcp-icon{width:40px;height:40px;display:grid;place-items:center;color:var(--header-text);background:transparent;border:0;border-radius:10px;cursor:pointer;position:relative}
    .hcp-icon:hover{background:color-mix(in srgb,var(--header-text) 8%,transparent)}
    .hcp-icon svg{width:19px;height:19px}
    .hcp-search{position:absolute;inset:0;display:flex;align-items:center;background:var(--header-bg);z-index:5}
    .hcp-search-inner{width:min(1360px,calc(100% - 40px));margin:0 auto;display:flex;align-items:center;gap:10px;position:relative}
    .hcp-search input{flex:1;min-height:44px;padding:0 16px;border:1px solid var(--border);border-radius:999px;font-size:14px;background:#fff;color:#0f172a}
    .hcp-search .search-suggest{top:calc(100% + 6px)}
    .hcp-count{position:absolute;top:2px;right:0;min-width:17px;height:17px;display:grid;place-items:center;padding:0 4px;background:var(--primary);color:#fff;border-radius:999px;font-size:10px;font-weight:800}
    .hdr-compact .profile-switch{display:flex;justify-content:center;gap:8px;padding:0 0 10px}
    .hcp-burger{display:none}
    @media(max-width:900px){.hcp-nav{display:none}.hcp-burger{display:grid}.hdr-compact .hcp-row{min-height:56px}}
</style>

<div class="header-zone hdr-compact {{ $headerStickyMode !== 'none' ? 'is-sticky' : '' }}">
    <header class="hcp-row" x-data="{ catsOpen: false }" @click.outside="catsOpen=false">
        <button class="hcp-icon hcp-burger" type="button" @click="mobileNav=true" aria-label="Abrir menú">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <a class="hcp-brand" href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, new \App\Models\StoreMenuItem(['destination_type' => 'home'])) }}">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $storeName }}">@else<span>{{ $storeName }}</span>@endif
        </a>

        <nav class="hcp-nav" aria-label="Navegación principal">
            @if($navCategories->count())
            <span class="hcp-cats">
                <button type="button" class="hcp-link" @click="catsOpen=!catsOpen" :aria-expanded="catsOpen.toString()">Categorías ▾</button>
                <div class="hcp-cats-panel" x-show="catsOpen" x-cloak>
                    @foreach($navCategories->take(10) as $cat)
                    <a href="{{ $shopBase }}?category={{ $cat->id }}">{{ $cat->name }}</a>
                    @endforeach
                </div>
            </span>
            @endif
            @forelse($menuRoots as $item)
            <a class="hcp-link {{ ($item->destination_type ?? null) === $activeDest ? 'is-active' : '' }}"
               href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, $item) }}">{{ $item->label }}</a>
            @empty
            <a class="hcp-link {{ $activeDest === 'home' ? 'is-active' : '' }}" href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, new \App\Models\StoreMenuItem(['destination_type' => 'home'])) }}">Inicio</a>
            <a class="hcp-link {{ $activeDest === 'shop' ? 'is-active' : '' }}" href="{{ $shopBase }}">Tienda</a>
            @endforelse
        </nav>

        <div class="hcp-actions">
            <button class="hcp-icon" type="button" @click="searchOpen=!searchOpen" aria-label="Buscar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            </button>
            <button class="hcp-icon" type="button" @click="openCartPage()" aria-label="Abrir carrito">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 3h2l2.1 11.2a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 2-1.6L21 7H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                <span class="hcp-count" x-text="itemCount()" aria-live="polite">0</span>
            </button>
        </div>

        {{-- Buscador expandible: mismo estado/sugerencias que el classic --}}
        <div class="hcp-search" x-show="searchOpen" x-cloak x-transition.opacity.duration.150ms @keydown.escape.window="searchOpen=false">
            <div class="hcp-search-inner">
                <input type="search" x-model.debounce.200ms="query" @input.debounce.250ms="fetchSuggest()"
                       @focus="suggest.length && (suggestOpen=true)" @keydown.enter.prevent="goSearch()"
                       placeholder="{{ $txtSearchPlaceholder }}" autocomplete="off" aria-label="Buscar productos"
                       x-ref="hcpSearch" x-effect="searchOpen && $nextTick(() => $refs.hcpSearch?.focus())">
                <button class="hcp-icon" type="button" @click="goSearch()" aria-label="Buscar ahora">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                </button>
                <button class="hcp-icon" type="button" @click="searchOpen=false" aria-label="Cerrar búsqueda">
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
</div>{{-- /header-zone compacta --}}
