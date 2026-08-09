{{-- VISUAL COLLECTIONS — panel "Explorar" con tarjetas fotográficas.
     Usa las imágenes reales de categorías (o su primera foto de producto);
     sin imagen cae a tarjeta de color con inicial (nunca imagen rota). --}}
@php
    $hpVsCols = max(3, min(5, (int) ($hp('visual_columns') ?: 4)));
    $hpVsOverlay = max(0, min(80, (int) ($hp('visual_overlay') ?: 35))) / 100;
    $hpVsCats = ($navCategories ?? collect())->take(10);
    $hpVsImg = static function ($cat) use ($assetUrl) {
        $own = $cat->image_url ?? $cat->image ?? null;
        if ($own) return $assetUrl($own);
        $p = $cat->products?->first(fn ($pp) => filled($pp->main_image_url));
        return $p?->main_image_url;
    };
@endphp
<style>
    .hp-vs-bar{border-bottom:1px solid var(--border);background:var(--surface)}
    .hp-vs-bar .container{display:flex;justify-content:center;padding-top:0;padding-bottom:0}
    .hp-vs-toggle{display:inline-flex;align-items:center;gap:9px;min-height:44px;padding:0 22px;color:var(--text-strong);background:none;border:0;font-size:13px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;cursor:pointer}
    .hp-vs-toggle svg{width:16px;height:16px;color:var(--accent,var(--primary))}
    .hp-vs-panel{position:absolute;left:0;right:0;z-index:60;background:var(--surface);border-bottom:1px solid var(--border);box-shadow:var(--shadow-lg)}
    .hp-vs-grid{display:grid;grid-template-columns:repeat({{ $hpVsCols }},minmax(0,1fr));gap:14px;padding:22px 0}
    .hp-vs-card{position:relative;display:grid;place-items:end start;aspect-ratio:4/3;border-radius:var(--radius-md);overflow:hidden;text-decoration:none;background:linear-gradient(135deg,color-mix(in srgb,var(--primary) 82%,#000),color-mix(in srgb,var(--primary) 45%,#000))}
    .hp-vs-card img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transition:transform .3s ease}
    .hp-vs-card:hover img{transform:scale(1.05)}
    .hp-vs-card:before{content:'';position:absolute;inset:0;z-index:1;background:linear-gradient(180deg,transparent 30%,rgba(8,12,20,{{ $hpVsOverlay + .3 }}))}
    .hp-vs-copy{position:relative;z-index:2;padding:14px 16px;color:#fff}
    .hp-vs-copy strong{display:block;font-family:var(--font-title);font-size:16px}
    .hp-vs-copy small{opacity:.85;font-size:11.5px}
    .hp-vs-initial{position:relative;z-index:0;place-self:center;color:rgba(255,255,255,.35);font-family:var(--font-title);font-size:64px;font-weight:800}
    @media(max-width:960px){.hp-vs-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:480px){.hp-vs-grid{grid-template-columns:1fr 1fr;gap:10px}.hp-vs-copy strong{font-size:13px}}
</style>
<div class="hp-vs-bar" x-data="{ vs:false }" @click.outside="vs=false" @keydown.escape.window="vs=false" style="position:relative">
    <div class="container">
        <button type="button" class="hp-vs-toggle" @click="vs=!vs" :aria-expanded="vs">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/></svg>
            Explorar colecciones
        </button>
    </div>
    <div class="hp-vs-panel" x-show="vs" x-cloak x-transition.opacity>
        <div class="container hp-vs-grid">
            @foreach($hpVsCats as $cat)
            @php $vsImg = $hpVsImg($cat); @endphp
            <a class="hp-vs-card" href="{{ $shopBase }}?category={{ $cat->id }}">
                @if($vsImg)<img src="{{ $vsImg }}" alt="{{ $cat->name }}" loading="lazy">@else<span class="hp-vs-initial" aria-hidden="true">{{ mb_strtoupper(mb_substr($cat->name, 0, 1)) }}</span>@endif
                <span class="hp-vs-copy"><strong>{{ $cat->name }}</strong>@if($cat->children->count())<small>{{ $cat->children->count() }} subcategorías</small>@endif</span>
            </a>
            @endforeach
        </div>
    </div>
</div>
