{{-- FOOTER CORPORATIVO: 5 columnas — Empresa · Institucional · Categorías · Atención · Horario.
     Todo sale de la configuración existente (sin datos quemados); las columnas sin datos se ocultan. --}}
@php
    $legalBase = ($project->custom_domain && request()->getHost() === $project->custom_domain ? '' : '/'.$project->slug);
    $ftcCats = collect($navCategories ?? [])->take(6);
    $ftcShowCats = ((string) ($settings['footer_show_categories'] ?? '1') !== '0') && $ftcCats->isNotEmpty();
    $ftcShowSocial = ((string) ($settings['footer_show_socials'] ?? '1') !== '0') && !empty($social);
    $ftcPay = $payLogos ?? ['visa' => 'Visa', 'mastercard' => 'Mastercard', 'yape' => 'Yape', 'plin' => 'Plin'];
    $ftcShowPay = (string) ($settings['footer_show_payments'] ?? '1') !== '0';
    // Identificacion legal del comercio (razon social + RUC). Obligatoria en Peru
    // y hasta ahora no se mostraba en ninguna pagina publica aunque el dato ya
    // estaba guardado. Se puede ocultar, pero viene activa por defecto.
    $ftLegalName = trim((string) ($settings['legal_name'] ?? '')) ?: trim((string) $project->name);
    $ftRuc       = preg_replace('/[^0-9]/', '', (string) ($settings['ruc'] ?? ''));
    $ftShowLegal = (string) ($settings['footer_show_legal'] ?? '1') !== '0' && ($ftLegalName !== '' || $ftRuc !== '');

    $ftcShowSecure = (string) ($settings['footer_show_secure'] ?? '1') !== '0';
    $ftcSecureStyle = in_array($settings['footer_secure_style'] ?? 'both', ['gold', 'https', 'both', 'lock'], true)
        ? ($settings['footer_secure_style'] ?? 'both') : 'both';
    $ftcSecureTitle = trim((string) ($settings['footer_secure_title'] ?? '')) ?: 'Compra segura y protegida';
    $ftcCatUrl = fn ($cat) => ($shopBaseFooter ?? '#').'?category='.$cat->id;
@endphp
<style>
    .ftc{margin-top:60px;background:var(--footer-bg);color:var(--footer-text)}
    /* El pie usaba su propio ancho (1360px) y no cuadraba con el encabezado:
       ahora hereda el mismo contenedor para que logo y columnas alineen. */
    .ftc-main{width:var(--header-layout-width,min(1360px,calc(100% - 48px)));max-width:calc(100% - 48px);margin:0 auto;display:grid;grid-template-columns:1.6fr 1fr 1fr 1.15fr 1fr;gap:38px;padding:54px 0 40px}
    {{-- La altura viene del constructor (Marca y diseño → logo del pie): antes la
         variable no se definia nunca y el logo quedaba fijo en 52px. --}}
    .ftc-brand img{height:auto;max-height:{{ max(28, min(120, (int) ($settings['footer_logo_height'] ?? 52))) }}px;max-width:210px;object-fit:contain}
    .ftc-name{font-family:var(--font-title);font-size:21px;font-weight:700}
    .ftc-desc{margin:14px 0 0;max-width:420px;font-size:13.5px;line-height:1.75;opacity:.9}
    .ftc-social{display:flex;gap:10px;margin-top:18px}
    .ftc-social a{display:grid;place-items:center;width:34px;height:34px;color:inherit;border:1px solid color-mix(in srgb,currentColor 25%,transparent);border-radius:50%;opacity:.85;transition:opacity .15s ease,border-color .15s ease}
    .ftc-social a:hover{opacity:1;border-color:var(--accent,var(--primary))}
    .ftc-social svg{width:15px;height:15px}
    .ftc h4{margin:0 0 15px;font-family:var(--font-title);font-size:15px;font-weight:700;letter-spacing:.01em}
    .ftc h4::after{content:"";display:block;width:32px;height:2px;margin-top:8px;background:var(--accent,var(--primary))}
    .ftc ul{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px;font-size:13px}
    .ftc a{color:inherit;opacity:.92;text-decoration:none;transition:color .18s ease,opacity .18s ease}
    .ftc a:hover{color:var(--accent,#fff);opacity:1}
    .ftc a:hover{opacity:1;text-decoration:underline}
    .ftc-contact li{display:flex;gap:8px;align-items:baseline;opacity:.92}
    .ftc-contact li>span:first-child{font-weight:700;opacity:.7;min-width:64px}
    .ftc-hours{font-size:13px;line-height:1.85;opacity:.92;white-space:pre-line;margin:0}
    .ftc-bottom{border-top:1px solid color-mix(in srgb,currentColor 18%,transparent)}
    .ftc-bottom-inner{width:var(--header-layout-width,min(1360px,calc(100% - 48px)));max-width:calc(100% - 48px);margin:0 auto;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px 20px;padding:16px 0;font-size:12px}
    .ft-legal{display:block;opacity:.72;font-size:11.5px}
    .ftc-copy{opacity:.7}
    .ftc-dev{font-weight:700;opacity:1;color:var(--accent,currentColor)}
    .ftc-dev:hover{text-decoration:underline}
    .ftc-secure{display:flex;align-items:center;gap:9px;font-size:12px;opacity:.95}
    .ftc-seals{display:inline-flex;align-items:center;gap:7px;flex:0 0 auto}
    .ftc-secure .ssl-seal{flex:0 0 auto;filter:drop-shadow(0 2px 6px rgba(0,0,0,.3))}
    .ftc-secure b{display:block;font-size:12px}
    .ftc-secure small{opacity:.7;font-size:10.5px}
    .ftc-pays{display:flex;flex-wrap:wrap;gap:7px}
    .ftc-pays .pay-marks{display:contents}
    .ftc-pays span{display:inline-grid;place-items:center;height:26px;min-width:44px;padding:0 8px;background:#fff;border:1px solid rgba(255,255,255,.85);border-radius:6px;color:#0B2038;font-size:10px;font-weight:800;letter-spacing:.04em}
    .ftc-pays span svg{display:block;height:15px;width:auto}
    .ftc-pays span img{display:block;height:17px;width:auto;max-width:60px;object-fit:contain}
    .ftc-pays span img.is-wide{height:17px}
    .ftc-pays span img.is-md{height:18px}
    .ftc-pays span img.is-sq{height:22px}
    @media(max-width:1100px){.ftc-main{grid-template-columns:1.6fr 1fr 1fr;gap:34px}}
    @media(max-width:760px){.ftc-main{grid-template-columns:1fr 1fr;gap:28px;padding:40px 0 28px}.ftc-brand{grid-column:1/-1}}
    @media(max-width:460px){.ftc-main{grid-template-columns:1fr}}
</style>
<footer class="ftc site-footer-corporate">
    <div class="ftc-main">
        <div class="ftc-brand">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $storeName }}" loading="lazy">@else<span class="ftc-name">{{ $storeName }}</span>@endif
            @if($tagline)<p class="ftc-desc">{{ $tagline }}</p>@endif
            @if($ftcShowSocial)
            <div class="ftc-social">
                @foreach($social as $net => $url)
                <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($net) }}">{!! $svgIcons[$net] ?? '' !!}</a>
                @endforeach
            </div>
            @endif
        </div>
        <div>
            <h4>Institucional</h4>
            <ul>
                <li><a href="{{ $aboutUrl }}">Nosotros</a></li>
                <li><a href="{{ $contactUrl }}">Contacto</a></li>
                <li><a href="{{ url($legalBase.'/reclamaciones') }}">Libro de Reclamaciones</a></li>
                <li><a href="{{ url($legalBase.'/privacidad') }}">Privacidad</a></li>
                <li><a href="{{ url($legalBase.'/terminos') }}">Términos y condiciones</a></li>
            </ul>
        </div>
        @if($ftcShowCats)
        <div>
            <h4>Categorías</h4>
            <ul>
                @foreach($ftcCats as $cat)
                <li><a href="{{ $ftcCatUrl($cat) }}">{{ $cat->name }}</a></li>
                @endforeach
            </ul>
        </div>
        @endif
        <div>
            <h4>Atención</h4>
            <ul class="ftc-contact">
                @if($phone)<li><span>Teléfono</span><a href="tel:{{ preg_replace('/[^\d+]/','',$phone) }}">{{ $phone }}</a></li>@endif
                @if($waFooter)<li><span>WhatsApp</span><a href="https://wa.me/{{ $waFooter }}" target="_blank" rel="noopener">Escríbenos</a></li>@endif
                @if($email)<li><span>Correo</span><a href="mailto:{{ $email }}">{{ $email }}</a></li>@endif
                @if($footerAddress)<li><span>Dirección</span><span>{{ $footerAddress }}</span></li>@endif
            </ul>
        </div>
        @if($footerHours)
        <div>
            <h4>Horario</h4>
            <p class="ftc-hours">{{ $footerHours }}</p>
        </div>
        @endif
    </div>
    <div class="ftc-bottom">
        <div class="ftc-bottom-inner">
            @if($ftcShowSecure)
            <div class="ftc-secure">
                <span class="ftc-seals">
                    @if($ftcSecureStyle !== 'https')@include('storefront.partials.badges.ssl-seal', ['sealStyle' => 'gold', 'sealPx' => 44])@endif
                    @if($ftcSecureStyle !== 'gold')@include('storefront.partials.badges.ssl-seal', ['sealStyle' => 'https', 'sealPx' => 44])@endif
                </span>
                <span><b>{{ $ftcSecureTitle }}</b><small>Sitio protegido con certificado SSL</small></span>
            </div>
            @endif
            @if($ftShowLegal)
            <span class="ft-legal">{{ $ftLegalName }}@if($ftRuc) · RUC {{ $ftRuc }}@endif</span>
            @endif
            <span class="ftc-copy">{{ $footerCopyright ?: '© '.date('Y').' '.$storeName.'. Todos los derechos reservados.' }} · Desarrollado por <a class="ftc-dev" href="https://eskalagroup.com/" target="_blank" rel="noopener">Eskala</a></span>
            @if($ftcShowPay && !empty($ftcPay))
            <div class="ftc-pays">@include('storefront.partials.badges.pay-logos', ['pay' => $ftcPay])</div>
            @endif
        </div>
    </div>
</footer>
