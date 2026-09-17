{{-- PIE "UNA BANDA" (footer_layout = banda).
     Una sola franja horizontal, sin columnas altas: logo con el lema a su
     derecha, contacto con iconos, enlaces repartidos en dos listas cortas,
     redes en cuadros y una frase de marca en cursiva cerrando a la derecha.
     Debajo, una franja más oscura con los derechos a un lado y los legales
     al otro. Ocupa poco alto y se lee de un vistazo: para negocios que
     quieren un pie sobrio y ancho. Referencia del usuario: pie corporativo
     azul de una distribuidora eléctrica. --}}
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
    $fpCats = $fpOn('footer_show_categories') ? collect($navCategories ?? [])->take(4) : collect();
    $fpWa = $waFooter ?? '';
    $fpLogoAlto = max(24, min(160, (int) ($settings['footer_logo_height'] ?? 46)));
    $fpCopy = $footerCopyright ?? ('© '.date('Y').' '.$storeName.'. Todos los derechos reservados.');
    $fpIco = $svgIcons ?? [];

    /* El lema va PEGADO al logo, separado por una línea vertical, así que se
       parte en dos renglones cortos: una frase larga de corrido rompería la
       banda. Y la frase en cursiva de la derecha es el mismo texto de
       "Llamada a la acción" del pie, que hasta ahora solo usaba Tecnológico. */
    $fpLema  = trim((string) ($tagline ?? ''));
    $fpLemaA = $fpLema;
    $fpLemaB = '';
    if ($fpLema !== '' && mb_strlen($fpLema) > 26) {
        $corte = mb_strrpos(mb_substr($fpLema, 0, 30), ' ');
        if ($corte) {
            $fpLemaA = mb_substr($fpLema, 0, $corte);
            $fpLemaB = mb_substr($fpLema, $corte + 1);
        }
    }
    $fpFrase = trim((string) ($settings['footer_cta_title'] ?? ''));

    // Las dos listas de enlaces: lo del negocio a la izquierda, lo demás a la
    // derecha. Se reparten para que ninguna quede vacía si solo hay unos pocos.
    $fpEnlaces = array_merge(
        [['texto' => 'Inicio', 'url' => url($fpBase ?: '/')], ['texto' => 'Tienda', 'url' => $shopBaseFooter]],
        $fpCats->map(fn ($c) => ['texto' => $c->name, 'url' => $shopBaseFooter.'?category='.$c->id])->all(),
        $fpTienda,
        [['texto' => 'Nosotros', 'url' => $aboutUrl], ['texto' => 'Contacto', 'url' => $contactUrl]],
        $fpInfo,
    );
    $fpMitad = (int) ceil(count($fpEnlaces) / 2);
    $fpCol1 = array_slice($fpEnlaces, 0, $fpMitad);
    $fpCol2 = array_slice($fpEnlaces, $fpMitad);
@endphp
<style>
    .fba{margin-top:64px;background:var(--fp-bg,var(--footer-bg));color:var(--fp-text,var(--footer-text));--fp-fondo:var(--fp-bg,var(--footer-bg))}
    .fba-main{width:min(1400px,calc(100% - 56px));margin:0 auto;display:grid;grid-template-columns:minmax(240px,1.15fr) minmax(200px,.95fr) minmax(190px,.95fr) auto minmax(190px,1fr);gap:28px 40px;align-items:start;padding:34px 0 30px}
    .fba h4{margin:0 0 14px;font-size:14px;font-weight:700;color:var(--fp-title,#fff);letter-spacing:.01em}
    .fba a{color:inherit;text-decoration:none}
    .fba a:hover{color:var(--fp-title,#fff);text-decoration:underline;text-underline-offset:3px}

    /* Marca: logo y lema separados por una línea, como en la referencia. */
    .fba-marca{display:flex;align-items:center;gap:18px}
    .fba-marca img{height:{{ $fpLogoAlto }}px;max-width:190px;width:auto;object-fit:contain}
    .fba-marca .nombre{font-family:var(--font-title,inherit);font-size:21px;font-weight:800;color:var(--fp-title,#fff);letter-spacing:-.01em}
    .fba-lema{padding-left:18px;border-left:1px solid var(--fp-line,rgba(255,255,255,.28));font-size:13.5px;line-height:1.45;max-width:210px}

    .fba-lista{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:9px;font-size:13.5px}
    .fba-contacto li{display:flex;gap:10px;align-items:flex-start;line-height:1.4}
    .fba-contacto li svg{width:16px;height:16px;flex-shrink:0;stroke:currentColor;fill:none;stroke-width:1.9;margin-top:2px;opacity:.9}
    .fba-enlaces{display:grid;grid-template-columns:1fr 1fr;gap:9px 26px}

    .fba-social{display:flex;gap:10px}
    .fba-social a{width:34px;height:34px;border-radius:8px;border:1px solid var(--fp-line,rgba(255,255,255,.34));display:grid;place-items:center;color:inherit;transition:background .15s,border-color .15s}
    .fba-social a:hover{background:color-mix(in srgb,currentColor 14%,transparent);border-color:currentColor}
    .fba-social svg{width:16px;height:16px;fill:currentColor}

    .fba-frase{font-style:italic;font-size:15px;line-height:1.5;font-weight:600;color:var(--fp-title,#fff);text-align:right;align-self:center}

    .fba-bottom{background:var(--fp-fuerte,color-mix(in srgb,var(--footer-bg) 74%,#000))}
    .fba-bottom-inner{width:min(1400px,calc(100% - 56px));margin:0 auto;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px 24px;padding:13px 0;font-size:12.5px;opacity:.82}
    .fba-legales{display:flex;flex-wrap:wrap;gap:6px 22px}

    @media(max-width:1180px){.fba-main{grid-template-columns:minmax(240px,1.2fr) 1fr 1fr;gap:26px 34px}.fba-frase{grid-column:1/-1;text-align:left;font-size:14px}}
    @media(max-width:720px){.fba-main{grid-template-columns:1fr;padding:28px 0 24px}.fba-marca{flex-wrap:wrap;gap:12px}.fba-lema{padding-left:0;border-left:0;max-width:none}.fba-bottom-inner{justify-content:center;text-align:center}}
</style>
<footer class="fba" id="pie">
    <div class="fba-main">
        <div class="fba-marca">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $storeName }}" loading="lazy">@else<span class="nombre">{{ $storeName }}</span>@endif
            @if($fpLemaA !== '')<div class="fba-lema">{{ $fpLemaA }}@if($fpLemaB !== '')<br>{{ $fpLemaB }}@endif</div>@endif
        </div>

        <div>
            <h4>Contacto</h4>
            <ul class="fba-lista fba-contacto">
                @if($phone)<li><svg viewBox="0 0 24 24">{!! $fpIco['phone'] ?? '' !!}</svg><a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a></li>@endif
                @if($fpWa && preg_replace('/\D/', '', (string) $phone) !== $fpWa)<li><svg viewBox="0 0 24 24">{!! $fpIco['chat'] ?? '' !!}</svg><a href="https://wa.me/{{ $fpWa }}" target="_blank" rel="noopener">{{ $settings['quote_whatsapp'] ?? $fpWa }}</a></li>@endif
                @include('storefront.partials.numeros-extra')
                @if($email)<li><svg viewBox="0 0 24 24">{!! $fpIco['mail'] ?? '' !!}</svg><a href="mailto:{{ $email }}">{{ $email }}</a></li>@endif
                @if($footerAddress)<li><svg viewBox="0 0 24 24">{!! $fpIco['pin'] ?? '' !!}</svg><span>{{ $footerAddress }}</span></li>@endif
                @if($footerHours)<li><svg viewBox="0 0 24 24">{!! $fpIco['clock'] ?? '' !!}</svg><span>{{ $footerHours }}</span></li>@endif
            </ul>
        </div>

        <div>
            <h4>Enlaces</h4>
            <div class="fba-enlaces">
                <ul class="fba-lista">@foreach($fpCol1 as $l)<li><a href="{{ $l['url'] }}">{{ $l['texto'] }}</a></li>@endforeach</ul>
                <ul class="fba-lista">@foreach($fpCol2 as $l)<li><a href="{{ $l['url'] }}">{{ $l['texto'] }}</a></li>@endforeach</ul>
            </div>
        </div>

        @if($fpSocial)
        <div>
            <h4>Síguenos</h4>
            <div class="fba-social">
                @foreach($fpSocial as $red => $url)
                <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $red }}" title="{{ $red }}"><svg viewBox="0 0 24 24">{!! $fp::iconoRed($red) !!}</svg></a>
                @endforeach
            </div>
        </div>
        @endif

        @if($fpFrase !== '')<p class="fba-frase">“{{ $fpFrase }}”</p>@endif
    </div>

    <div class="fba-bottom"><div class="fba-bottom-inner">
        <span>{{ $fpCopy }}@if($fpShowLegal) · {{ $fpLegal['nombre'] }}@if($fpLegal['ruc']) · RUC {{ $fpLegal['ruc'] }}@endif @endif</span>
        <div class="fba-legales">
            @foreach($fpLegales as $l)<a href="{{ $l['url'] }}">{{ $l['texto'] }}</a>@endforeach
            @if(!empty($footerDevText))<span>{{ $footerDevText }}</span>@else<a href="https://eskalagroup.com/" target="_blank" rel="noopener">Desarrollado por Eskala</a>@endif
        </div>
    </div></div>
</footer>
