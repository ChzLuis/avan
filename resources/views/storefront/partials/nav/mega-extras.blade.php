{{-- MEGA MENÚ PRO — columnas extra dentro del panel existente del header:
     marcas destacadas (de la sección Marcas del catálogo) y bloque promocional.
     Solo renderiza con el preset mega_menu activo y con datos reales. --}}
@php
    $hpMegaOn = ($hpKey ?? null) === 'mega_menu';
    $hpMegaBrands = collect();
    if ($hpMegaOn && (string) ($hp('mega_brands', '1')) !== '0') {
        $brandsRow = ($homeSectionByNativeKey ?? collect())->get('brands');
        $hpMegaBrands = collect(is_array($brandsRow?->content ?? null) ? ($brandsRow->content['items'] ?? []) : [])
            ->filter(fn ($b) => is_array($b) && ($b['enabled'] ?? true) && filled($b['name'] ?? null))
            ->take(max(2, min(8, (int) ($hp('mega_brands_max') ?: 6))));
    }
    $hpMegaPromoTitle = $hpMegaOn ? trim((string) $hp('mega_promo_title', '')) : '';
    $hpMegaPromoImg = $hpMegaOn ? $assetUrl($hp('mega_promo_image')) : null;
@endphp
@if($hpMegaOn && ($hpMegaBrands->isNotEmpty() || $hpMegaPromoTitle !== ''))
<style>
    .hp-mm-extra{display:flex;flex-direction:column;gap:14px;padding:6px 0 6px 22px;border-left:1px solid var(--border)}
    .hp-mm-extra h5{margin:0;color:var(--muted);font-size:10.5px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
    .hp-mm-brands{display:flex;flex-wrap:wrap;gap:8px}
    .hp-mm-brand{padding:7px 14px;color:var(--text);background:var(--surface-soft);border:1px solid var(--border);border-radius:999px;font-size:12px;font-weight:700;text-decoration:none}
    .hp-mm-brand:hover{color:var(--primary);border-color:var(--primary)}
    .hp-mm-brand img{max-height:22px;max-width:80px;object-fit:contain;display:block}
    .hp-mm-promo{position:relative;display:block;border-radius:var(--radius-md);overflow:hidden;background:var(--secondary);color:#fff;text-decoration:none;min-height:150px}
    .hp-mm-promo img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.55}
    .hp-mm-promo-copy{position:relative;z-index:1;padding:16px}
    .hp-mm-promo-copy em{display:inline-block;padding:3px 9px;background:var(--accent,var(--primary));border-radius:4px;color:#fff;font-size:10px;font-weight:800;font-style:normal;letter-spacing:.08em;text-transform:uppercase}
    .hp-mm-promo-copy strong{display:block;margin-top:8px;font-family:var(--font-title);font-size:16px;line-height:1.3}
    .hp-mm-promo-copy small{display:block;margin-top:5px;opacity:.85;font-size:12px}
    .hp-mm-promo-copy span{display:inline-block;margin-top:10px;font-size:12px;font-weight:800;text-decoration:underline}
    /* Sin fotografia el bloque quedaba como un rectangulo plano con el texto
       pegado arriba: se centra el contenido, se anade acento de marca y el
       enlace pasa a boton. Al subir la imagen vuelve al tratamiento normal. */
    .hp-mm-promo--flat{display:flex;align-items:center;min-height:170px;background:linear-gradient(145deg,var(--secondary) 0%,color-mix(in srgb,var(--secondary) 82%,#000) 100%);border:1px solid color-mix(in srgb,var(--accent,var(--primary)) 34%,transparent)}
    .hp-mm-promo--flat:before{content:'';position:absolute;right:-38px;top:-38px;width:132px;height:132px;border-radius:50%;background:color-mix(in srgb,var(--accent,var(--primary)) 20%,transparent)}
    .hp-mm-promo--flat .hp-mm-promo-copy{padding:20px}
    .hp-mm-promo--flat .hp-mm-promo-copy strong{margin-top:10px;font-size:17px}
    .hp-mm-promo--flat .hp-mm-promo-copy small{margin-top:6px;line-height:1.5;opacity:.78}
    .hp-mm-promo--flat .hp-mm-promo-copy span{margin-top:14px;padding:8px 15px;color:#0f172a;background:var(--accent,#fff);border-radius:7px;font-size:11.5px;text-decoration:none}
    .hp-mm-promo--flat:hover .hp-mm-promo-copy span{filter:brightness(1.07)}
    @media(max-width:960px){.hp-mm-extra{display:none}}
</style>
<div class="hp-mm-extra">
    @if($hpMegaBrands->isNotEmpty())
    <div>
        <h5>Marcas destacadas</h5>
        <div class="hp-mm-brands" style="margin-top:9px">
            @foreach($hpMegaBrands as $b)
            <a class="hp-mm-brand" href="{{ $b['url'] ?? (($shopBase ?? '#').'?q='.urlencode($b['name'])) }}">
                @if(!empty($b['image']))<img src="{{ $assetUrl($b['image']) }}" alt="{{ $b['name'] }}" loading="lazy">@else{{ $b['name'] }}@endif
            </a>
            @endforeach
        </div>
    </div>
    @endif
    @if($hpMegaPromoTitle !== '')
    <a class="hp-mm-promo{{ $hpMegaPromoImg ? '' : ' hp-mm-promo--flat' }}" href="{{ $hp('mega_promo_url') ?: ($shopBase ?? '#') }}">
        @if($hpMegaPromoImg)<img src="{{ $hpMegaPromoImg }}" alt="" loading="lazy">@endif
        <span class="hp-mm-promo-copy">
            @if(filled($hp('mega_promo_badge')))<em>{{ $hp('mega_promo_badge') }}</em>@endif
            <strong>{{ $hpMegaPromoTitle }}</strong>
            @if(filled($hp('mega_promo_desc')))<small>{{ $hp('mega_promo_desc') }}</small>@endif
            <span>{{ $hp('mega_promo_button', 'Ver más') }} →</span>
        </span>
    </a>
    @endif
</div>
@endif
