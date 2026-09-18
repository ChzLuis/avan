{{-- ENCABEZADO Y NAVEGACIÓN — módulos del preset activo.
     Se incluye UNA vez después del header. Cada módulo se enciende según las
     capabilities del preset (HeaderPresets) y sus claves hp_{preset}_*.
     Fallbacks estrictos: sin datos → el módulo no se renderiza. --}}
@php
    $hpCaps = $hpPreset['capabilities'] ?? [];
    // Con shell componible activo, el shell ya arma universos/B2B/CTA/panel visual
    // dentro de su estructura: aquí solo quedan los módulos que van BAJO el header.
    $hpShellOn = !empty($hpPreset['shell']);
    $hpSearchSize = $hpPreset['search'] ?? 'normal';
    $hpMegaCols = max(2, min(5, (int) ($hp('mega_columns') ?: 4)));
    $hpWa = preg_replace('/\D/', '', (string) ($settings['quote_whatsapp'] ?? ''));
    if ($hpWa && !str_starts_with($hpWa, '51')) $hpWa = '51' . $hpWa;
    $hpWholesaleOn = ($hpCaps['b2b'] ?? false)
        && (string) $hp('commercial_show_wholesale', '1') !== '0'
        && (string) ($settings['wholesale_enabled'] ?? '1') !== '0';
    $hpCtaText = trim((string) $hp('minimal_cta_text', ''));
    $hpCtaUrl = trim((string) $hp('minimal_cta_url', '')) ?: ($shopBase ?? '#');
    // Universos: perfiles del proyecto; sin perfiles caen a las categorías raíz
    // (la especificación permite alimentarlos de perfiles, categorías o colecciones).
    $hpHasProfiles = !empty($catalogProfiles) && $catalogProfiles->count();
    $hpUniCats = $hpHasProfiles ? collect() : ($navCategories ?? collect())->take(6);
    $hpUniverses = ($hpCaps['universes'] ?? false) && ($hpHasProfiles || $hpUniCats->isNotEmpty());
    $hpUniStyle = in_array($hp('multiverse_style', $hp('boutique_universe_style', 'pill')), ['text','icon','both','pill'], true)
        ? $hp('multiverse_style', $hp('boutique_universe_style', 'pill')) : 'pill';
@endphp

<style>
    /* ═══ Identidad visible EN REPOSO de cada modelo ═══ */
    /* Mega Menú Pro: botón de categorías protagonista + columnas configuradas */
    {{-- Tinta por luminancia: sobre acento claro (dorado) el blanco fijo era ilegible. --}}
    body.hp-mega-menu .mega-btn{background:var(--accent,var(--primary));color:var(--accent-ink,#fff);border:0;padding:10px 18px;border-radius:8px;font-weight:800}
    body.hp-mega-menu .mega-sub-grid{grid-template-columns:repeat({{ min(3, max(1, $hpMegaCols - 2)) }},minmax(0,1fr))}
    body.hp-mega-menu .mega-sub-grid .mega-sub-link:nth-child(n+{{ max(3, (int) ($hp('mega_max_subs') ?: 6)) + 1 }}){display:none}
    body.hp-mega-menu .mega-grid{grid-template-columns:250px minmax(0,1fr) {{ '280px' }}}
    /* Boutique Editorial: navegación tipográfica, aire y separadores sutiles */
    body.hp-boutique .category-list a{font-family:var(--font-title);font-size:12.5px;letter-spacing:.12em;text-transform:uppercase}
    body.hp-boutique .category-list{gap:26px;justify-content:center}
    body.hp-boutique .mega-trigger{display:none}
    body.hp-boutique .hp-universes{border-top:1px solid var(--border)}
    /* Catálogo Profesional / Visual: la barra propia reemplaza el botón mega */
    body.hp-catalog-pro .mega-trigger,body.hp-visual-collections .mega-trigger{display:none}
    body.hp-visual-collections .hp-vs-toggle{color:var(--accent,var(--primary));border-bottom:2px solid var(--accent,var(--primary))}
    /* Minimal: sin teléfono, todo compacto */
    body.hp-minimal .phone-copy{display:none}
    /* Comercial: la barra B2B es la identidad; carrito destacado */
    body.hp-commercial .cart-trigger{background:var(--primary);color:#fff;border-color:var(--primary)}
    /* Search First: el buscador manda, lo demás se aquieta */
    body.hp-search-first .phone-copy{display:none}
    body.hp-search-first .search input{border-width:2px;border-color:var(--primary);box-shadow:0 6px 22px color-mix(in srgb,var(--primary) 18%,transparent)}
    body.hp-search-first .category-list a{font-size:12px}
    body.hp-search-hero .search{max-width:760px}
    body.hp-search-hero .search input{height:52px;font-size:15px}
    body.hp-search-compact .search input{height:38px;font-size:13px}
    @if((string) $hp('search_show_price', '1') === '0') .search-suggest-price{display:none} @endif
    @if((string) $hp('search_show_thumb', '1') === '0') .search-suggest-thumb{display:none} @endif
    body.hp-search-icon .search{max-width:46px;overflow:hidden;transition:max-width .25s ease}
    body.hp-search-icon .search:focus-within,body.hp-search-icon .search.search-open{max-width:420px}
    @media(max-width:760px){body.hp-search-icon .search{max-width:100%}}
    /* Franja de universos (perfiles) */
    .hp-universes{border-bottom:1px solid var(--border);background:var(--surface)}
    .hp-universes-inner{display:flex;flex-wrap:wrap;justify-content:center;gap:8px;padding:9px 16px}
    .hp-universe{display:inline-flex;align-items:center;gap:7px;padding:7px 15px;color:var(--text);background:var(--surface-soft);border:1px solid var(--border);border-radius:999px;font-size:13px;font-weight:700;text-decoration:none;transition:.15s}
    .hp-universe:hover{border-color:var(--primary)}
    .hp-universe.is-active{color:#fff;background:var(--chip-color,var(--primary));border-color:var(--chip-color,var(--primary))}
    .hp-universes.style-text .hp-universe{background:none;border:0;border-radius:0}
    .hp-universes.style-text .hp-universe.is-active{color:var(--primary);background:none;border-bottom:2px solid var(--primary)}
    .hp-universe-dot{width:8px;height:8px;border-radius:50%;background:var(--chip-color,var(--primary))}
    /* Barra comercial B2B */
    .hp-b2b{background:var(--secondary);color:#fff}
    .hp-b2b-inner{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:10px 22px;padding:9px 16px;font-size:13px}
    .hp-b2b a{display:inline-flex;align-items:center;gap:8px;color:#fff;font-weight:700;text-decoration:none;opacity:.92}
    .hp-b2b a:hover{opacity:1;text-decoration:underline}
    .hp-b2b a.hp-b2b-wa{padding:6px 14px;background:#22c55e;border-radius:999px;opacity:1}
    .hp-b2b a.hp-b2b-wa:hover{text-decoration:none;filter:brightness(1.06)}
    .hp-b2b svg{width:15px;height:15px}
    /* CTA minimal */
    .hp-cta-strip{display:flex;justify-content:center;padding:0}
    /* Botonera flotante del CTA dentro del header compacto */
    body.hp-minimal .header-actions .hp-cta{display:inline-flex;align-items:center;padding:9px 18px;color:#fff;background:var(--primary);border-radius:var(--btn-radius,8px);font-size:13px;font-weight:800;text-decoration:none}
    @media(max-width:760px){body.hp-minimal .header-actions .hp-cta{display:none}}
</style>

{{-- Universos / selector de público (perfiles existentes; no duplica datos) --}}
@if(!$hpShellOn && $hpUniverses)
<div class="hp-universes style-{{ $hpUniStyle }}">
    <div class="container hp-universes-inner">
        <a class="hp-universe {{ empty($activeProfile) && ! \App\Modules\Tienda\Support\StorefrontNavigation::currentCategoryId() ? 'is-active' : '' }}" href="{{ \App\Modules\Tienda\Support\StorefrontNavigation::shopUrl($project) }}">
            @if($hpUniStyle !== 'text')<span class="hp-universe-dot" aria-hidden="true"></span>@endif Todo
        </a>
        @if($hpHasProfiles)
            @foreach($catalogProfiles as $cp)
            <a class="hp-universe {{ (!empty($activeProfile) && $activeProfile->id === $cp->id) ? 'is-active' : '' }}"
               @if($cp->primary_color) style="--chip-color:{{ $cp->primary_color }}" @endif
               href="{{ \App\Modules\Tienda\Support\StorefrontNavigation::profileUrl($project, $cp->slug) }}">
                @if($hpUniStyle !== 'text')<span class="hp-universe-dot" aria-hidden="true"></span>@endif
                {{ $cp->menu_label ?: $cp->name }}
            </a>
            @endforeach
        @else
            @foreach($hpUniCats as $uc)
            <a class="hp-universe {{ (string) \App\Modules\Tienda\Support\StorefrontNavigation::currentCategoryId() === (string) $uc->id ? 'is-active' : '' }}" href="{{ $shopBase }}?category={{ $uc->id }}">
                @if($hpUniStyle !== 'text')<span class="hp-universe-dot" aria-hidden="true"></span>@endif
                {{ $uc->name }}
            </a>
            @endforeach
        @endif
    </div>
</div>
@endif

{{-- Barra comercial B2B (Cotizar / WhatsApp / Mayorista) --}}
@if(!$hpShellOn && ($hpCaps['b2b'] ?? false) && ($hpWa || $hpWholesaleOn))
<div class="hp-b2b">
    <div class="container hp-b2b-inner">
        @if($hpWholesaleOn)
        <a href="{{ $shopBase ?? '#' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7l-8-4-8 4v10l8 4 8-4V7Z"/><path d="M4 7l8 4 8-4M12 11v10"/></svg>{{ $hp('commercial_wholesale_text', 'Venta mayorista — precios especiales por volumen') }}</a>
        @endif
        @if(filled($hp('commercial_cta_title')))
        <a href="{{ $hp('commercial_cta_url') ?: ($hpWa ? 'https://wa.me/'.$hpWa : '#') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16M9 8h1m4 0h1M9 12h1m4 0h1"/></svg>{{ $hp('commercial_cta_title') }}</a>
        @endif
        @if($hpWa)
        <a class="hp-b2b-wa" href="https://wa.me/{{ $hpWa }}?text={{ urlencode($hp('commercial_wa_msg', $settings['quote_wa_msg'] ?? 'Hola, quiero una cotización')) }}" target="_blank" rel="noopener">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Z"/></svg>{{ $hp('commercial_wa_text', 'Cotiza por WhatsApp') }}
        </a>
        @endif
    </div>
</div>
@endif

{{-- CTA opcional (Minimal Conversión) --}}
@if(($hpCaps['cta'] ?? false) && $hpCtaText !== '' && (($hpCaps['b2b'] ?? false) === false))
@php /* con shell, el CTA vive como franja fina bajo el header minimal: se mantiene */ @endphp
<div style="display:flex;justify-content:center;padding:10px 16px;border-bottom:1px solid var(--border);background:var(--surface)">
    <a href="{{ $hpCtaUrl }}" style="display:inline-flex;align-items:center;gap:8px;padding:10px 26px;color:#fff;background:var(--primary);border-radius:var(--btn-radius,8px);font-size:13px;font-weight:800;text-decoration:none">{{ $hpCtaText }} <span aria-hidden="true">→</span></a>
</div>
@endif

{{-- Sidebar de categorías (Catálogo Profesional) --}}
@if(($hpCaps['sidebar'] ?? false) && ($navCategories ?? collect())->count())
    @include('tienda::storefront.partials.nav.sidebar')
@endif

{{-- Navegación visual por tarjetas (Visual Collections) — solo sin shell (el shell trae su panel "Comprar") --}}
@if(!$hpShellOn && ($hpCaps['visual'] ?? false) && ($navCategories ?? collect())->count())
    @include('tienda::storefront.partials.nav.visual')
@endif
