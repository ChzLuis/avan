{{-- CATÁLOGO PROFESIONAL — barra lateral de categorías (desplegable).
     Botón fijo bajo el header; panel con raíces + segundo nivel al pasar.
     En móvil no se muestra: el drawer móvil existente ya cubre categorías. --}}
@php
    $hpSbMax = max(4, min(20, (int) ($hp('catalog_max_cats') ?: 10)));
    $hpSbIcons = (string) $hp('catalog_show_icons', '1') !== '0';
    $hpSbCats = ($navCategories ?? collect())->take($hpSbMax);
@endphp
<style>
    .hp-sb-bar{border-bottom:1px solid var(--border);background:var(--surface)}
    .hp-sb-bar .container{display:flex;align-items:center;gap:14px;padding-top:0;padding-bottom:0}
    .hp-sb-toggle{display:inline-flex;align-items:center;gap:9px;min-height:44px;padding:0 16px;color:#fff;background:var(--primary);border:0;border-radius:0;font-size:13px;font-weight:800;cursor:pointer}
    .hp-sb-toggle svg{width:17px;height:17px}
    .hp-sb-hint{color:var(--muted);font-size:12.5px}
    .hp-sb-wrap{position:relative}
    .hp-sb-panel{position:absolute;top:100%;left:0;z-index:60;width:290px;background:var(--surface);border:1px solid var(--border);border-radius:0 0 var(--radius-md) var(--radius-md);box-shadow:var(--shadow-lg)}
    .hp-sb-item{position:relative;border-bottom:1px solid var(--border)}
    .hp-sb-item:last-child{border-bottom:0}
    .hp-sb-link{display:flex;align-items:center;gap:10px;padding:11px 16px;color:var(--text-strong);font-size:13.5px;font-weight:600;text-decoration:none}
    .hp-sb-item:hover{background:var(--surface-soft)}
    .hp-sb-item:hover .hp-sb-link{color:var(--primary)}
    .hp-sb-link svg{width:16px;height:16px;color:var(--accent,var(--primary));flex:0 0 auto}
    .hp-sb-caret{margin-left:auto;color:var(--muted)}
    .hp-sb-sub{position:absolute;left:100%;top:-1px;z-index:61;display:none;width:250px;background:var(--surface);border:1px solid var(--border);border-radius:0 var(--radius-md) var(--radius-md) 0;box-shadow:var(--shadow-lg);padding:6px 0}
    .hp-sb-item:hover .hp-sb-sub{display:block}
    .hp-sb-sub a{display:block;padding:9px 16px;color:var(--text);font-size:13px;text-decoration:none}
    .hp-sb-sub a:hover{color:var(--primary);background:var(--surface-soft)}
    .hp-sb-all{display:block;padding:11px 16px;color:var(--primary);font-size:12.5px;font-weight:800;text-decoration:none;background:var(--surface-soft)}
    @media(max-width:960px){.hp-sb-bar{display:none}}
</style>
@php $hpSbFixed = (string) $hp('catalog_fixed', '1') !== '0' && ($storeView ?? 'home') === 'home'; @endphp
<div class="hp-sb-bar" x-data="{ sb: {{ $hpSbFixed ? 'window.matchMedia(\'(min-width:1100px)\').matches' : 'false' }} }" @keydown.escape.window="sb=false">
    <div class="container">
        <div class="hp-sb-wrap">
            <button type="button" class="hp-sb-toggle" @click="sb=!sb" :aria-expanded="sb">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                Todas las categorías
            </button>
            <div class="hp-sb-panel" x-show="sb" x-cloak x-transition.opacity>
                @foreach($hpSbCats as $cat)
                {{-- Ítem como <div>: un <a> dentro de otro <a> rompe el árbol HTML --}}
                <div class="hp-sb-item">
                    <a class="hp-sb-link" href="{{ $shopBase }}?category={{ $cat->id }}">
                        @if($hpSbIcons)<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="M4 7v10l8 4 8-4V7"/></svg>@endif
                        {{ $cat->name }}
                        @if($cat->children->count())<span class="hp-sb-caret" aria-hidden="true">›</span>@endif
                    </a>
                    @if($cat->children->count())
                    <div class="hp-sb-sub">
                        @foreach($cat->children->take(10) as $sub)
                        <a href="{{ $shopBase }}?category={{ $sub->id }}">{{ $sub->name }}</a>
                        @endforeach
                        <a href="{{ $shopBase }}?category={{ $cat->id }}" style="font-weight:800;color:var(--primary)">Ver todo {{ $cat->name }} →</a>
                    </div>
                    @endif
                </div>
                @endforeach
                <a class="hp-sb-all" href="{{ $shopBase }}">Ver todas las categorías →</a>
            </div>
        </div>
        <span class="hp-sb-hint">Explora el catálogo completo por categoría</span>
    </div>
</div>
