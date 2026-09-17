    @if($announcementShow)
    <div class="topbar topbar--{{ $announcementAlign }}" style="background:{{ $announcementBg }};color:{{ $announcementColor }};font-size:{{ $announcementFontSize }}px">
        <div class="{{ $announcementFull ? 'topbar-full' : 'container' }} topbar-inner">
            <p style="color:{{ $announcementColor }}">{{ $announcementText ?: 'Atención especializada para tu compra' }}</p>
            @if($announcementAlign !== 'center')
            <div class="contact-links">@if($phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" style="color:{{ $announcementColor }}">{{ $phone }}</a>@endif @if($email)<a href="mailto:{{ $email }}" style="color:{{ $announcementColor }}">{{ $email }}</a>@endif</div>
            @endif
        </div>
    </div>
    @endif

    <div class="header-zone hz-style-{{ $headerStyle }} {{ $headerStickyMode === 'all' ? 'is-sticky' : '' }} {{ $headerStickyMode === 'menu' ? 'is-sticky-menu' : '' }}">
    <header class="store-header">
        <div class="container header-main">
            <button class="header-hamburger" type="button" @click="mobileNav=true" aria-label="Abrir menú">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <a class="brand {{ $logoUrl ? 'brand--logo-only' : 'brand--text' }}"
               href="{{ \App\Support\StorefrontNavigation::homeUrl($project) }}"
               aria-label="Inicio de {{ $storeName }}">
                @if($logoUrl)
                    <img class="brand-logo" src="{{ $logoUrl }}" alt="Logo de {{ $storeName }}">
                    @if(($settings['logo_wordmark'] ?? '0') === '1')<span class="brand-wordmark">{{ $settings['logo_wordmark_text'] ?? $storeName }}</span>@endif
                @else
                    <span class="brand-mark">{{ mb_strtoupper(mb_substr($storeName, 0, 1)) }}</span>
                    <span class="brand-copy">
                        <span class="brand-name">{{ $storeName }}</span>
                        @if(trim($tagline) !== '')
                            <span class="brand-tagline">{{ Str::limit($tagline, 62) }}</span>
                        @endif
                    </span>
                @endif
            </a>
            <label class="search" :class="{'search-open': searchOpen}" @click.outside="suggestOpen=false" @keydown.escape="suggestOpen=false">
                <span class="sr-only">Buscar en el catálogo</span>
                <input type="search" x-model="query" @input.debounce.250ms="fetchSuggest()" @focus="suggest.length && (suggestOpen=true)" @keydown.enter.prevent="goSearch()" placeholder="{{ $txtSearchPlaceholder }}" autocomplete="off" role="combobox" aria-label="Buscar productos" :aria-expanded="suggestOpen">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
                {{-- Sugerencias predictivas --}}
                <div class="search-suggest" x-show="suggestOpen" x-cloak role="listbox" @click.stop>
                    <template x-for="sp in suggest" :key="'sg-'+sp.id">
                        <a class="search-suggest-item" :href="sp.url" role="option">
                            <span class="search-suggest-thumb"><template x-if="sp.image"><img :src="sp.image" :alt="sp.name" loading="lazy"></template></span>
                            <span class="search-suggest-info"><strong x-text="sp.name"></strong><small x-text="sp.category||''"></small></span>
                            @if($hidePrices ?? false)
                            <span class="search-suggest-price">{{ $txtPrecioConsul ?? 'A consultar' }}</span>
                            @else
                            <span class="search-suggest-price" x-show="sp.price > 0" x-text="money(sp.price)"></span>
                            @endif
                        </a>
                    </template>
                    <button type="button" class="search-suggest-all" @click="goSearch()">Ver todos los resultados <span aria-hidden="true">→</span></button>
                </div>
            </label>
            <div class="header-actions">
                <button class="header-search-toggle" type="button" @click="searchOpen=!searchOpen" aria-label="Buscar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg></button>
                @if($phone)<span class="phone-copy"><small>Atención comercial</small><strong>{{ $phone }}</strong></span>@endif
                <button class="cart-trigger" type="button" @click="openCartPage()" aria-label="Abrir carrito"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 3h2l2.1 11.2a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 2-1.6L21 7H6"></path><circle cx="10" cy="20" r="1"></circle><circle cx="18" cy="20" r="1"></circle></svg><span class="cart-count" x-text="itemCount()" aria-live="polite">0</span></button>
            </div>
        </div>
    </header>

    <nav class="category-nav" aria-label="Navegación" x-data="{ mega: false, megaCat: {{ $navCategories->first()->id ?? 'null' }} }" @mouseleave="mega=false">
        <div class="container category-bar menu-{{ $menuAlign }}">
            {{-- Selector de perfiles de catálogo (sólo si la tienda los usa) --}}
            @if(!empty($catalogProfiles) && $catalogProfiles->count())
            <div class="profile-switch" role="group" aria-label="Colecciones de la tienda">
                <span class="profile-switch-label">Colecciones</span>
                <a class="profile-chip {{ empty($activeProfile) ? 'is-active' : '' }}" href="{{ \App\Support\StorefrontNavigation::shopUrl($project) }}">Todo</a>
                @foreach($catalogProfiles as $cp)
                    <a class="profile-chip {{ (!empty($activeProfile) && $activeProfile->id === $cp->id) ? 'is-active' : '' }}"
                       @if($cp->primary_color) style="--chip-color:{{ $cp->primary_color }}" @endif
                       href="{{ \App\Support\StorefrontNavigation::profileUrl($project, $cp->slug) }}"><span class="profile-dot" aria-hidden="true"></span>{{ $cp->menu_label ?: $cp->name }}</a>
                @endforeach
            </div>
            @endif
            {{-- Boton de categorias comun: posicion, estilo, texto y modo de
                 despliegue desde el Constructor. Antes era un boton fijo
                 "Todas las categorias" que solo sabia abrir el panel mega. --}}
            @php
                $clCatsPos = in_array($settings['hp_cats_pos'] ?? '', ['izquierda', 'derecha', 'oculto'], true) ? $settings['hp_cats_pos'] : 'izquierda';
                $clModo = in_array($settings['hp_cat_trigger'] ?? '', ['mega', 'lista', 'none'], true) ? $settings['hp_cat_trigger'] : 'mega';
                if ($clCatsPos === 'oculto' || ! $navCategories->count()) $clModo = 'none';
            @endphp
            @if($clModo !== 'none')
                @include('storefront.partials.nav.cats-button', ['modo' => $clModo, 'pos' => $clCatsPos, 'var' => 'mega'])
            @endif

            {{-- Menú del Constructor (Inicio, Productos, Nosotros, Contacto) --}}
            <div class="category-list">
            @php $currentCategory = \App\Support\StorefrontNavigation::currentCategoryId(); @endphp
            @if($menuRoots->count())
                @foreach($menuRoots as $mItem)
                    @php
                        // Un item de tienda/productos sólo está activo si estamos en la
                        // tienda SIN categoría filtrada. Con categoría activa, sólo se
                        // marca el item de ESA categoría — nunca varios a la vez.
                        $isShopItem = in_array($mItem->destination_type, ['shop','products'], true);
                        $isActive = $isShopItem
                            ? ($activeDest === 'shop' && ! $currentCategory)
                            : in_array($mItem->destination_type, [$activeDest], true);
                    @endphp
                    @if(in_array($mItem->destination_type, ['category','subcategory']) && $mItem->destination_id)
                        {{-- Las categorías del menú abren la vista Tienda con el filtro aplicado. --}}
                        <a href="{{ $shopBase }}?category={{ (int) $mItem->destination_id }}"
                           class="{{ (string) $currentCategory === (string) $mItem->destination_id ? 'is-active' : '' }}">
                            {{ $mItem->label }}
                        </a>
                    @else
                        <a href="{{ \App\Support\StorefrontNavigation::resolveUrl($project, $mItem) }}"
                           class="{{ $isActive ? 'is-active' : '' }}"
                           @if($mItem->target === '_blank') target="_blank" rel="noopener" @endif>{{ $mItem->label }}</a>
                    @endif
                @endforeach
            @else
                <a href="{{ $shopBase }}">Todos los productos</a>
                @foreach($navCategories as $category)<a href="{{ $shopBase }}?category={{ $category->id }}">{{ $category->name }}</a>@endforeach
            @endif
            </div>
        </div>

        {{-- Panel desplegable del mega-menú --}}
        @if($navCategories->count())
        <div class="mega-panel" x-show="mega" x-cloak x-transition.opacity @mouseenter="mega=true">
            <div class="container mega-grid">
                {{-- Columna izquierda: categorías raíz --}}
                <div class="mega-cats">
                    @foreach($navCategories as $cat)
                    <a href="{{ $shopBase }}?category={{ $cat->id }}"
                       class="mega-cat-item" :class="megaCat==={{ $cat->id }} && 'is-active'"
                       @mouseenter="megaCat={{ $cat->id }}">
                        <span>{{ $cat->name }}</span>
                        @if($cat->children->count())<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"></path></svg>@endif
                    </a>
                    @endforeach
                </div>
                {{-- Columna derecha: subcategorías de la categoría activa --}}
                <div class="mega-subs">
                    @foreach($navCategories as $cat)
                    <div x-show="megaCat==={{ $cat->id }}" x-cloak class="mega-sub-panel">
                        <a href="{{ $shopBase }}?category={{ $cat->id }}" class="mega-sub-title">{{ $cat->name }} <small>Ver todo →</small></a>
                        @if($cat->children->count())
                        <div class="mega-sub-grid">
                            @foreach($cat->children as $sub)
                            <a href="{{ $shopBase }}?category={{ $sub->id }}" class="mega-sub-link">{{ $sub->name }}</a>
                            @endforeach
                        </div>
                        @else
                        <p class="mega-sub-empty">Explora todos los productos de {{ $cat->name }}.</p>
                        @endif
                    </div>
                    @endforeach
                </div>
                {{-- Columnas extra del preset Mega Menú Pro (marcas + promoción); sin preset no renderiza nada --}}
                @includeIf('storefront.partials.nav.mega-extras')
            </div>
        </div>
        @endif
    </nav>
    </div>{{-- /header-zone --}}

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
