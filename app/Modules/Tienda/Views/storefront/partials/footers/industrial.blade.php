{{-- PIE "INDUSTRIAL" (footer_layout = industrial).
     Negro, denso, cinco columnas: Pagos y garantía (cuentas bancarias y
     sellos), Datos de la empresa (con iconos en acento), Categorías, Enlaces
     útiles y Marcas (o Tienda si no hay marcas). Flechas de acento en cada
     enlace y derechos centrados abajo. Pensado para ferreterías, distribuidoras
     y proveedores industriales. Referencia del usuario: pie de distribuidora
     eléctrica con transferencia bancaria y sellos. --}}
@php
    $fp = \App\Modules\Tienda\Storefront\DatosPie::class;
    $fpBase = $fp::base($project);
    $fpOn = static fn (string $k, string $def = '1') => (string) ($settings[$k] ?? $def) !== '0';
    $fpInfo = $fp::enlaces($settings['footer_pages'] ?? '', $fpBase);
    $fpTienda = $fp::enlaces($settings['footer_store_pages'] ?? '', $fpBase);
    $fpLegales = $fp::legales($fpBase);
    $fpLegal = $fp::legal($settings, $project);
    $fpShowLegal = $fpOn('footer_show_legal') && ($fpLegal['nombre'] !== '' || $fpLegal['ruc'] !== '');
    $fpCats = $fpOn('footer_show_categories') ? collect($navCategories ?? [])->take(7) : collect();
    $fpMarcas = $fp::marcas($project, 9);
    $fpCuentas = $fp::cuentas($settings);
    $fpPagos = $fpOn('footer_show_payments') ? $fp::pagos($settings) : [];
    $fpSecure = $fpOn('footer_show_secure') && $fpOn('footer_show_ssl');
    $fpWa = $waFooter ?? '';
    $fpCopy = $footerCopyright ?? ('© '.date('Y').' '.$storeName);
    $fpIco = $svgIcons ?? [];
    $fpBeneficios = $benefits ?? [];
    $fpSocial = ($fpOn('footer_show_socials') && $fpOn('footer_show_social')) ? ($social ?? []) : [];
@endphp
<style>
    .fin{margin-top:64px;background:var(--fp-fuerte,color-mix(in srgb,var(--footer-bg) 30%,#000));color:var(--fp-text,#e5e7eb);--fp-fondo:var(--fp-fuerte,#000)}
    .fin-main{width:min(1400px,calc(100% - 48px));margin:0 auto;display:grid;grid-template-columns:1.1fr 1.25fr 1fr 1fr .9fr;gap:36px;padding:54px 0 44px}
    .fin h4{margin:0 0 22px;font-size:17px;font-weight:800;letter-spacing:.02em;text-transform:uppercase;color:var(--fp-title,#fff)}
    .fin ul{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:15px;font-size:15px}
    .fin a{color:inherit;text-decoration:none}
    .fin a:hover{color:var(--fp-title,#fff);text-decoration:underline}
    .fin-flecha li{display:flex;gap:10px;align-items:flex-start}
    .fin-flecha li::before{content:"";width:7px;height:7px;border-top:2px solid var(--primary);border-right:2px solid var(--primary);transform:rotate(45deg);margin-top:7px;flex-shrink:0}
    .fin-datos li{display:flex;gap:10px;align-items:flex-start;line-height:1.5}
    .fin-datos li svg{width:18px;height:18px;flex-shrink:0;stroke:var(--primary);fill:none;stroke-width:1.9;margin-top:3px}
    .fin-cuentas{margin:0 0 22px;padding:0 0 18px;border-bottom:1px solid var(--fp-line,rgba(255,255,255,.18));list-style:none;display:flex;flex-direction:column;gap:10px;font-size:14px;font-family:ui-monospace,Menlo,Consolas,monospace}
    .fin-cuentas li{padding:8px 12px;background:color-mix(in srgb,currentColor 8%,transparent);border-radius:6px}
    .fin-sellos{display:flex;flex-wrap:wrap;gap:12px;align-items:center}
    .fin-sello{display:inline-flex;align-items:center;gap:7px;padding:7px 11px;border:1px solid var(--fp-line,rgba(255,255,255,.22));border-radius:8px;font-size:12px;font-weight:700;color:var(--fp-title,#fff)}
    .fin-sello svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2}
    .fin-sello.ok{border-color:#22c55e;color:#86efac}
    .fin-garantia{margin-top:18px;width:104px;height:104px;border-radius:50%;background:radial-gradient(circle at 35% 30%,#fde68a,#d97706 62%,#92400e);color:#3b1d00;display:grid;place-items:center;text-align:center;font-weight:900;line-height:1.05;box-shadow:0 0 0 5px rgba(217,119,6,.28),0 10px 24px rgba(0,0,0,.45);font-size:11px;letter-spacing:.06em}
    .fin-garantia b{display:block;font-size:20px;letter-spacing:0}
    .fp-pago{display:inline-grid;place-items:center;height:24px;padding:0 9px;border-radius:5px;font-size:11px;font-weight:800}
    .fin-pagos{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
    .fin-bottom{border-top:1px solid var(--fp-line,rgba(255,255,255,.14));padding:16px 24px;font-size:14px;color:inherit;opacity:.9;display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:10px 22px}
    .fin-bottom a{color:var(--primary);filter:brightness(1.4)}
    .fin-redes{display:flex;gap:14px}
    .fin-redes a{color:inherit;filter:none;display:inline-flex}
    .fin-redes a:hover{color:var(--primary);filter:brightness(1.4)}
    .fin-redes svg{width:18px;height:18px;fill:currentColor}
    @media(max-width:1200px){.fin-main{grid-template-columns:1fr 1fr 1fr}}
    @media(max-width:760px){.fin-main{grid-template-columns:1fr 1fr;gap:28px;padding:40px 0 30px}}
    @media(max-width:480px){.fin-main{grid-template-columns:1fr}}
</style>
<footer class="fin" id="pie">
    <div class="fin-main">
        <div>
            <h4>{{ $fpCuentas ? 'Transferencia bancaria' : 'Pagos y garantía' }}</h4>
            @if($fpCuentas)
            <ul class="fin-cuentas">@foreach($fpCuentas as $c)<li>{{ $c }}</li>@endforeach</ul>
            @endif
            @if($fpPagos)<div class="fin-pagos" aria-label="Medios de pago">@foreach($fpPagos as $p){!! $fp::fichaPago($p) !!}@endforeach</div>@endif
            @if($fpSecure)
            <div class="fin-sellos" style="margin-top:16px">
                <span class="fin-sello ok"><svg viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>https · SSL</span>
                <span class="fin-sello"><svg viewBox="0 0 24 24">{!! $fpIco['shield'] ?? '' !!}</svg>Compra protegida</span>
            </div>
            <div class="fin-garantia" aria-label="Garantía de calidad">GARANTÍA<b>100%</b>CALIDAD</div>
            @endif
        </div>

        <div>
            <h4>Datos de la empresa</h4>
            <ul class="fin-datos">
                @include('tienda::storefront.partials.numeros-extra')
                @if($email)<li><svg viewBox="0 0 24 24">{!! $fpIco['mail'] ?? '' !!}</svg><span>Correo: <a href="mailto:{{ $email }}">{{ $email }}</a></span></li>@endif
                @if($phone)<li><svg viewBox="0 0 24 24">{!! $fpIco['phone'] ?? '' !!}</svg><span>Teléfono: <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a></span></li>@endif
                @if($fpWa)<li><svg viewBox="0 0 24 24">{!! $fpIco['chat'] ?? '' !!}</svg><span>WhatsApp: <a href="https://wa.me/{{ $fpWa }}" target="_blank" rel="noopener">{{ $settings['quote_whatsapp'] ?? $fpWa }}</a></span></li>@endif
                @if($footerAddress)<li><svg viewBox="0 0 24 24">{!! $fpIco['pin'] ?? '' !!}</svg><span>Dirección: {{ $footerAddress }}</span></li>@endif
                @if($fpShowLegal && $fpLegal['ruc'])<li><svg viewBox="0 0 24 24">{!! $fpIco['warranty'] ?? '' !!}</svg><span>RUC: {{ $fpLegal['ruc'] }} · {{ $fpLegal['nombre'] }}</span></li>@endif
                @if($footerHours)<li><svg viewBox="0 0 24 24">{!! $fpIco['clock'] ?? '' !!}</svg><span>Horario: {{ $footerHours }}</span></li>@endif
                @foreach($fpBeneficios as $b)<li><svg viewBox="0 0 24 24">{!! $fpIco['truck'] ?? '' !!}</svg><span>{{ $b['t'] }}</span></li>@endforeach
            </ul>
        </div>

        @if($fpCats->isNotEmpty())
        <div>
            <h4>Categorías</h4>
            <ul class="fin-flecha">
                @foreach($fpCats as $cat)<li><a href="{{ $shopBaseFooter }}?category={{ $cat->id }}">{{ $cat->name }}</a></li>@endforeach
            </ul>
        </div>
        @endif

        <div>
            <h4>Enlaces de importancia</h4>
            <ul class="fin-flecha">
                <li><a href="{{ $aboutUrl }}">Nosotros</a></li>
                <li><a href="{{ $contactUrl }}">Contacto</a></li>
                @foreach($fpInfo as $l)<li><a href="{{ $l['url'] }}">{{ $l['texto'] }}</a></li>@endforeach
                @foreach($fpLegales as $l)<li><a href="{{ $l['url'] }}">{{ $l['texto'] }}</a></li>@endforeach
            </ul>
        </div>

        @if($fpMarcas->isNotEmpty())
        <div>
            <h4>Marcas</h4>
            <ul class="fin-flecha">@foreach($fpMarcas as $m)<li><a href="{{ $shopBaseFooter }}?q={{ urlencode($m) }}">{{ $m }}</a></li>@endforeach</ul>
        </div>
        @elseif($fpTienda)
        <div>
            <h4>Tienda</h4>
            <ul class="fin-flecha">@foreach($fpTienda as $l)<li><a href="{{ $l['url'] }}">{{ $l['texto'] }}</a></li>@endforeach</ul>
        </div>
        @endif
    </div>
    <div class="fin-bottom">
        <span>{{ $fpCopy }} @if(!empty($footerDevText)) | {{ $footerDevText }} @else | Diseñado por <a href="https://eskalagroup.com/" target="_blank" rel="noopener">Eskala</a>@endif</span>
        @if($fpSocial)
        <div class="fin-redes">@foreach($fpSocial as $red => $url)<a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $red }}" title="{{ $red }}"><svg viewBox="0 0 24 24">{!! $fp::iconoRed($red) !!}</svg></a>@endforeach</div>
        @endif
    </div>
</footer>
