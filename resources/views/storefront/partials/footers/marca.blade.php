{{-- PIE "MARCA GIGANTE" (footer_layout = marca).
     Editorial: el nombre de la tienda en letras enormes, recortado por el
     borde inferior, es lo único que grita; el resto va en una sola línea
     tranquila de enlaces y contacto. Para moda, diseño, estudios y marcas que
     viven de su nombre. Sin columnas, sin tarjetas. --}}
@php
    $fp = \App\Storefront\DatosPie::class;
    $fpBase = $fp::base($project);
    $fpOn = static fn (string $k, string $def = '1') => (string) ($settings[$k] ?? $def) !== '0';
    $fpInfo = $fp::enlaces($settings['footer_pages'] ?? '', $fpBase);
    $fpTienda = $fp::enlaces($settings['footer_store_pages'] ?? '', $fpBase);
    $fpLegales = $fp::legales($fpBase);
    $fpLegal = $fp::legal($settings, $project);
    $fpShowLegal = $fpOn('footer_show_legal') && ($fpLegal['nombre'] !== '' || $fpLegal['ruc'] !== '');
    $fpSocial = ($fpOn('footer_show_socials') && $fpOn('footer_show_social')) ? ($social ?? []) : [];
    $fpCats = $fpOn('footer_show_categories') ? collect($navCategories ?? [])->take(5) : collect();
    $fpWa = $waFooter ?? '';
    $fpCopy = $footerCopyright ?? ('© '.date('Y').' '.$storeName);
    $fpTag = $tagline ?? '';
    $fpNombre = trim((string) $storeName);
    $fpLargo = mb_strlen($fpNombre);
    // El nombre debe caber en una linea: cuanto mas largo, mas pequeno.
    $fpVw = $fpLargo <= 8 ? 19 : ($fpLargo <= 14 ? 12.5 : ($fpLargo <= 22 ? 8 : 5.5));
@endphp
<style>
    .fma{margin-top:72px;background:var(--fp-bg,var(--footer-bg));color:var(--fp-text,var(--footer-text));overflow:hidden;--fp-fondo:var(--fp-bg,var(--footer-bg))}
    .fma-in{width:min(1360px,calc(100% - 48px));margin:0 auto}
    .fma-top{display:grid;grid-template-columns:1.4fr 1fr;gap:40px;padding:60px 0 34px;align-items:end}
    .fma-tag{margin:0;font-family:var(--font-title,inherit);font-size:clamp(22px,2.6vw,34px);font-weight:500;line-height:1.25;max-width:22ch;color:var(--fp-title,#fff)}
    .fma-contacto{display:flex;flex-direction:column;gap:8px;font-size:15px;justify-self:end;text-align:right}
    .fma-contacto a{color:inherit;text-decoration:none;border-bottom:1px solid color-mix(in srgb,currentColor 35%,transparent)}
    .fma-contacto a:hover{border-color:currentColor}
    .fma-nav{display:flex;flex-wrap:wrap;gap:10px 30px;padding:22px 0;border-top:1px solid var(--fp-line,color-mix(in srgb,currentColor 18%,transparent));border-bottom:1px solid var(--fp-line,color-mix(in srgb,currentColor 18%,transparent));font-size:14px}
    .fma-nav a{color:inherit;text-decoration:none;opacity:.85}
    .fma-nav a:hover{opacity:1;text-decoration:underline;text-underline-offset:4px}
    .fma-nav .sep{opacity:.35}
    .fma-meta{display:flex;flex-wrap:wrap;justify-content:space-between;gap:10px 24px;padding:18px 0 0;font-size:12.5px;opacity:.7}
    .fma-social{display:flex;gap:16px}
    .fma-social a{color:inherit;display:inline-flex}
    .fma-social svg{width:18px;height:18px;fill:currentColor}
    .fma-wordmark{display:block;margin:10px 0 -0.22em;font-family:var(--font-title,inherit);font-size:{{ $fpVw }}vw;font-weight:800;line-height:1;letter-spacing:-.045em;white-space:nowrap;color:var(--fp-title,#fff);opacity:.92;user-select:none}
    .fma-wordmark span{color:var(--primary)}
    @media(max-width:860px){.fma-top{grid-template-columns:1fr;gap:22px;padding:44px 0 26px}.fma-contacto{justify-self:start;text-align:left}.fma-wordmark{font-size:{{ min(19, $fpVw * 1.5) }}vw}}
</style>
<footer class="fma" id="pie">
    <div class="fma-in">
        <div class="fma-top">
            <p class="fma-tag">{{ $fpTag ?: 'Gracias por comprar en '.$storeName.'.' }}</p>
            <div class="fma-contacto">
                @if($fpWa)<a href="https://wa.me/{{ $fpWa }}" target="_blank" rel="noopener">WhatsApp {{ $settings['quote_whatsapp'] ?? $phone }}</a>@elseif($phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a>@endif
                @include('storefront.partials.numeros-extra')
                @if($email)<a href="mailto:{{ $email }}">{{ $email }}</a>@endif
                @if($footerAddress)<span>{{ $footerAddress }}</span>@endif
                @if($footerHours)<span>{{ $footerHours }}</span>@endif
            </div>
        </div>
        <nav class="fma-nav" aria-label="Enlaces del pie">
            <a href="{{ $shopBaseFooter }}">Tienda</a>
            @foreach($fpCats as $cat)<a href="{{ $shopBaseFooter }}?category={{ $cat->id }}">{{ $cat->name }}</a>@endforeach
            <span class="sep">/</span>
            <a href="{{ $aboutUrl }}">Nosotros</a><a href="{{ $contactUrl }}">Contacto</a>
            @foreach(array_merge($fpTienda, $fpInfo) as $l)<a href="{{ $l['url'] }}">{{ $l['texto'] }}</a>@endforeach
            <span class="sep">/</span>
            @foreach($fpLegales as $l)<a href="{{ $l['url'] }}">{{ $l['texto'] }}</a>@endforeach
        </nav>
        <div class="fma-meta">
            <span>{{ $fpCopy }}@if($fpShowLegal) · {{ $fpLegal['nombre'] }}@if($fpLegal['ruc']) · RUC {{ $fpLegal['ruc'] }}@endif @endif @if(!empty($footerDevText)) · {{ $footerDevText }} @endif</span>
            @if($fpSocial)
            <div class="fma-social">@foreach($fpSocial as $red => $url)<a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $red }}"><svg viewBox="0 0 24 24">{!! $fp::iconoRed($red) !!}</svg></a>@endforeach</div>
            @endif
        </div>
        <div class="fma-wordmark" aria-hidden="true">{{ $fpNombre }}<span>.</span></div>
    </div>
</footer>
