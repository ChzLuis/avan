{{-- PIE "BOLETÍN DESTACADO" (footer_layout = boletin).
     Fondo claro; marca, contacto y legales en columnas con un subrayado corto
     de acento bajo cada título; a la derecha una tarjeta en el color del pie
     con el formulario del boletín (o WhatsApp si no hay boletín). Debajo, la
     barra oscura con los derechos y los medios de pago en una placa blanca.
     Referencia del usuario: pie de ferretería con tarjeta de newsletter. --}}
@php
    $fp = \App\Storefront\DatosPie::class;
    $fpBase = $fp::base($project);
    $fpOn = static fn (string $k, string $def = '1') => (string) ($settings[$k] ?? $def) !== '0';
    $fpInfo = $fp::enlaces($settings['footer_pages'] ?? '', $fpBase);
    $fpLegales = $fp::legales($fpBase);
    $fpLegal = $fp::legal($settings, $project);
    $fpShowLegal = $fpOn('footer_show_legal') && ($fpLegal['nombre'] !== '' || $fpLegal['ruc'] !== '');
    $fpSocial = ($fpOn('footer_show_socials') && $fpOn('footer_show_social')) ? ($social ?? []) : [];
    $fpPagos = $fpOn('footer_show_payments') ? $fp::pagos($settings) : [];
    $fpWa = $waFooter ?? '';
    $fpLogoAlto = max(24, min(160, (int) ($settings['footer_logo_height'] ?? 48)));
    $fpCopy = $footerCopyright ?? ('© '.date('Y').' '.$storeName);
    $fpTag = $tagline ?? '';
    $fpNewsUrl = $fpOn('footer_show_newsletter') ? trim((string) ($settings['footer_newsletter_url'] ?? '')) : '';
    $fpNewsTitulo = trim((string) ($settings['footer_newsletter_title'] ?? '')) ?: 'Recibe ofertas y novedades';
    $fpIco = $svgIcons ?? [];
@endphp
<style>
    .fbo{margin-top:64px;background:var(--fp-sup,#fff);color:var(--fp-sup-ink,#1f2937);--fp-fondo:var(--fp-sup,#fff)}
    .fbo-main{width:min(1360px,calc(100% - 48px));margin:0 auto;display:grid;grid-template-columns:1.25fr 1fr 1fr 1.15fr;gap:40px;padding:52px 0 40px;align-items:start}
    .fbo h4{margin:0 0 22px;font-family:var(--font-title,inherit);font-size:20px;font-weight:600;color:var(--fp-sup-ink,#111827);position:relative;padding-bottom:14px}
    .fbo h4::after{content:"";position:absolute;left:0;bottom:0;width:56px;height:3px;background:var(--primary)}
    .fbo-brand img{height:{{ $fpLogoAlto }}px;max-width:240px;width:auto;object-fit:contain}
    .fbo-brand .nombre{font-family:var(--font-title,inherit);font-size:24px;font-weight:700;color:var(--fp-sup-ink,#111827)}
    .fbo-brand p{margin:16px 0 0;font-size:14px;line-height:1.7;color:var(--fp-sup-ink,#4b5563);opacity:.85;max-width:340px}
    .fbo-brand .legal{margin-top:14px;font-size:13px;color:var(--fp-sup-ink,#6b7280);opacity:.7}
    .fbo-social{display:flex;gap:10px;margin-top:22px}
    .fbo-social a{width:42px;height:42px;border:1.5px solid var(--fp-line,#cbd5e1);border-radius:50%;display:grid;place-items:center;color:inherit;transition:all .15s}
    .fbo-social a:hover{border-color:var(--primary);color:var(--primary)}
    .fbo-social svg{width:17px;height:17px;fill:currentColor}
    .fbo ul{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:16px;font-size:15px}
    .fbo li{display:flex;gap:12px;align-items:flex-start;line-height:1.45}
    .fbo li svg{width:18px;height:18px;flex-shrink:0;color:var(--primary);stroke:currentColor;fill:none;stroke-width:1.9;margin-top:2px}
    .fbo a{color:inherit;text-decoration:none}
    .fbo a:hover{color:var(--primary)}
    .fbo-news{background:var(--fp-bg,var(--footer-bg));color:var(--fp-title,#fff);border-radius:22px;padding:30px 28px 28px}
    .fbo-news h4{color:var(--fp-title,#fff)}
    .fbo-news p{margin:0 0 18px;font-size:15px;line-height:1.55;opacity:.92}
    .fbo-news form{display:flex;border-radius:10px;overflow:hidden;background:#fff}
    .fbo-news input{flex:1;min-width:0;border:0;padding:14px 16px;font-size:14px;color:#111827;background:#fff;outline:none}
    .fbo-news button,.fbo-news .wa{border:0;background:var(--primary);color:#fff;padding:0 22px;display:grid;place-items:center;cursor:pointer;font-weight:700;font-size:14px}
    .fbo-news .wa{border-radius:10px;padding:14px 20px;text-decoration:none;display:inline-flex;align-items:center;gap:10px}
    .fbo-news button svg,.fbo-news .wa svg{width:18px;height:18px;fill:currentColor}
    .fbo-bottom{background:var(--fp-fuerte,var(--footer-bg));color:var(--fp-title,#fff)}
    .fbo-bottom-inner{width:min(1360px,calc(100% - 48px));margin:0 auto;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px 24px;padding:18px 0;font-size:14px}
    .fbo-bottom a{color:var(--primary);filter:brightness(1.35)}
    .fbo-pagos{display:flex;gap:10px;padding:8px 14px;background:#fff;border-radius:12px}
    .fp-pago{display:inline-grid;place-items:center;height:26px;padding:0 10px;border-radius:5px;font-size:11px;font-weight:800;letter-spacing:.02em}
    .fbo-top{position:fixed;right:22px;bottom:22px;width:46px;height:46px;border-radius:50%;background:var(--primary);color:#fff;display:grid;place-items:center;box-shadow:0 8px 20px rgba(0,0,0,.2);z-index:40;text-decoration:none}
    .fbo-top svg{width:20px;height:20px;stroke:currentColor;fill:none;stroke-width:2.4}
    @media(max-width:1024px){.fbo-main{grid-template-columns:1fr 1fr;gap:34px}}
    @media(max-width:640px){.fbo-main{grid-template-columns:1fr;padding:40px 0 30px}.fbo-bottom-inner{justify-content:center;text-align:center}.fbo-top{bottom:88px}}
</style>
<footer class="fbo" id="pie">
    <div class="fbo-main">
        <div class="fbo-brand">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $storeName }}" loading="lazy">@else<div class="nombre">{{ $storeName }}</div>@endif
            @if($fpTag)<p>{{ $fpTag }}</p>@endif
            @if($fpShowLegal)<div class="legal">@if($fpLegal['ruc'])RUC {{ $fpLegal['ruc'] }} · @endif{{ $fpLegal['nombre'] }}</div>@endif
            @if($fpSocial)
            <div class="fbo-social">
                @foreach($fpSocial as $red => $url)
                <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $red }}" title="{{ $red }}"><svg viewBox="0 0 24 24">{!! $fp::iconoRed($red) !!}</svg></a>
                @endforeach
            </div>
            @endif
        </div>

        <div>
            <h4>Contacto</h4>
            <ul>
                @if($phone)<li><svg viewBox="0 0 24 24">{!! $fpIco['phone'] ?? '' !!}</svg><a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a></li>@endif
                @if($fpWa && $fpWa !== preg_replace('/\D/', '', (string) $phone))<li><svg viewBox="0 0 24 24">{!! $fpIco['chat'] ?? '' !!}</svg><a href="https://wa.me/{{ $fpWa }}" target="_blank" rel="noopener">WhatsApp {{ $settings['quote_whatsapp'] ?? $fpWa }}</a></li>@endif
                @include('storefront.partials.numeros-extra')
                @if($email)<li><svg viewBox="0 0 24 24">{!! $fpIco['mail'] ?? '' !!}</svg><a href="mailto:{{ $email }}">{{ $email }}</a></li>@endif
                @if($footerAddress)<li><svg viewBox="0 0 24 24">{!! $fpIco['pin'] ?? '' !!}</svg><span>{{ $footerAddress }}</span></li>@endif
                @if($footerHours)<li><svg viewBox="0 0 24 24">{!! $fpIco['clock'] ?? '' !!}</svg><span>{{ $footerHours }}</span></li>@endif
            </ul>
        </div>

        <div>
            <h4>{{ $fpInfo ? 'Información' : 'Páginas legales' }}</h4>
            <ul>
                @foreach(array_merge($fpInfo, $fpLegales) as $l)
                <li><a href="{{ $l['url'] }}">{{ $l['texto'] }}</a></li>
                @endforeach
            </ul>
        </div>

        <div class="fbo-news">
            @if($fpNewsUrl)
            <h4>Boletín</h4>
            <p>{{ $fpNewsTitulo }}</p>
            <form action="{{ $fpNewsUrl }}" method="post" target="_blank">
                <input type="email" name="email" placeholder="Correo electrónico" required aria-label="Correo electrónico">
                <button type="submit" aria-label="Suscribirme"><svg viewBox="0 0 24 24"><path d="M2 21 23 12 2 3v7l15 2-15 2z"/></svg></button>
            </form>
            @elseif($fpWa)
            <h4>¿Hablamos?</h4>
            <p>Respondemos por WhatsApp en horario de atención. Cuéntanos qué necesitas.</p>
            <a class="wa" href="https://wa.me/{{ $fpWa }}" target="_blank" rel="noopener"><svg viewBox="0 0 24 24"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.5-.6c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.2.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.2-.3-.3-.6-.4zM12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2z"/></svg>Escribir por WhatsApp</a>
            @else
            <h4>Visítanos</h4>
            <p>{{ $footerAddress ?: 'Estamos para atenderte.' }}</p>
            <a class="wa" href="{{ $contactUrl }}">Ver cómo llegar</a>
            @endif
        </div>
    </div>
    <div class="fbo-bottom"><div class="fbo-bottom-inner">
        <span>{{ $fpCopy }} @if(!empty($footerDevText)) | {{ $footerDevText }} @else | Desarrollado por <a href="https://eskalagroup.com/" target="_blank" rel="noopener">Eskala</a>@endif</span>
        @if($fpPagos)
        <div class="fbo-pagos" aria-label="Medios de pago">@foreach($fpPagos as $p){!! $fp::fichaPago($p) !!}@endforeach</div>
        @endif
    </div></div>
    <a href="#" class="fbo-top" aria-label="Volver arriba" onclick="window.scrollTo({top:0,behavior:'smooth'});return false;"><svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 15 7-7 7 7"/></svg></a>
</footer>
