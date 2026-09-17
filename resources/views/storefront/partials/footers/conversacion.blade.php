{{-- PIE "CONVERSACIÓN" (footer_layout = conversacion).
     Para tiendas que venden conversando (cotización, WhatsApp): en el centro
     una burbuja de chat grande en color de marca con la invitación, el número
     y el horario; alrededor, solo lo imprescindible: un menú corto, redes y
     legales. Nada de columnas. --}}
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
    $fpWa = $waFooter ?? '';
    $fpTel = $settings['quote_whatsapp'] ?? ($phone ?: $fpWa);
    $fpCopy = $footerCopyright ?? ('© '.date('Y').' '.$storeName);
    $fpLogoAlto = max(24, min(160, (int) ($settings['footer_logo_height'] ?? 40)));
    $fpTitulo = trim((string) ($settings['footer_cta_title'] ?? '')) ?: '¿Tienes una duda? Escríbenos';
    $fpTexto  = trim((string) ($settings['footer_cta_text'] ?? '')) ?: 'Te respondemos por WhatsApp y te ayudamos a elegir.';
    $fpBoton  = trim((string) ($settings['footer_cta_btn'] ?? '')) ?: 'Abrir WhatsApp';
    $fpHoras = $fp::horario($footerHours ?? '');
@endphp
<style>
    .fco{margin-top:72px;background:var(--fp-bg,var(--footer-bg));color:var(--fp-text,var(--footer-text));text-align:center;--fp-fondo:var(--fp-bg,var(--footer-bg))}
    .fco-in{width:min(880px,calc(100% - 48px));margin:0 auto;padding:60px 0 28px}
    .fco-logo img{height:{{ $fpLogoAlto }}px;max-width:200px;width:auto;object-fit:contain;margin:0 auto}
    .fco-logo .nombre{font-family:var(--font-title,inherit);font-size:22px;font-weight:700;color:var(--fp-title,#fff)}
    .fco-burbuja{position:relative;margin:34px auto 0;max-width:640px;background:var(--primary);color:#fff;border-radius:28px 28px 28px 6px;padding:36px 34px 32px;text-align:left;box-shadow:0 24px 50px -20px rgba(0,0,0,.5)}
    .fco-burbuja::before{content:"";position:absolute;left:-10px;bottom:0;width:0;height:0;border-style:solid;border-width:0 0 22px 22px;border-color:transparent transparent var(--primary) transparent}
    .fco-burbuja h3{margin:0;font-family:var(--font-title,inherit);font-size:30px;font-weight:800;line-height:1.1;letter-spacing:-.01em}
    .fco-burbuja p{margin:12px 0 0;font-size:16px;line-height:1.55;opacity:.95;max-width:44ch}
    .fco-fila{display:flex;flex-wrap:wrap;align-items:center;gap:14px 26px;margin-top:24px}
    .fco-btn{display:inline-flex;align-items:center;gap:10px;background:#fff;color:var(--primary);text-decoration:none;font-weight:800;padding:14px 22px;border-radius:999px;font-size:15px}
    .fco-btn svg{width:20px;height:20px;fill:currentColor}
    .fco-dato{font-size:14px;opacity:.95;display:flex;flex-direction:column;gap:2px}
    .fco-dato b{font-size:16px}
    .fco-nav{display:flex;flex-wrap:wrap;justify-content:center;gap:8px 24px;margin:38px 0 0;font-size:14px}
    .fco-nav a{color:inherit;text-decoration:none;opacity:.85}
    .fco-nav a:hover{opacity:1;text-decoration:underline;text-underline-offset:4px}
    .fco-social{display:flex;justify-content:center;gap:12px;margin-top:22px}
    .fco-social a{width:40px;height:40px;border-radius:50%;border:1px solid var(--fp-line,color-mix(in srgb,currentColor 30%,transparent));display:grid;place-items:center;color:inherit}
    .fco-social svg{width:16px;height:16px;fill:currentColor}
    .fco-copy{margin-top:26px;font-size:12.5px;opacity:.65}
    .fco-copy a{color:inherit}
    @media(max-width:600px){.fco-in{padding:44px 0 22px}.fco-burbuja{padding:28px 22px 26px;border-radius:22px 22px 22px 6px}.fco-burbuja h3{font-size:24px}}
</style>
<footer class="fco" id="pie">
    <div class="fco-in">
        <div class="fco-logo">@if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $storeName }}" loading="lazy">@else<div class="nombre">{{ $storeName }}</div>@endif</div>

        <div class="fco-burbuja">
            <h3>{{ $fpTitulo }}</h3>
            <p>{{ $fpTexto }}</p>
            <div class="fco-fila">
                @if($fpWa)
                <a class="fco-btn" href="https://wa.me/{{ $fpWa }}" target="_blank" rel="noopener"><svg viewBox="0 0 24 24"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.5-.6c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.2.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.2-.3-.3-.6-.4zM12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2z"/></svg>{{ $fpBoton }}</a>
                @else
                <a class="fco-btn" href="{{ $contactUrl }}">Contáctanos</a>
                @endif
                @if($fpTel)<div class="fco-dato"><span>Llámanos o escríbenos</span><b>{{ $fpTel }}</b></div>@endif
                @if($fpHoras)<div class="fco-dato"><span>Atendemos</span><b>{{ implode(' · ', $fpHoras) }}</b></div>@endif
            </div>
        </div>

        <nav class="fco-nav" aria-label="Enlaces del pie">
            <a href="{{ $shopBaseFooter }}">Catálogo</a><a href="{{ $aboutUrl }}">Nosotros</a><a href="{{ $contactUrl }}">Contacto</a>
            @foreach(array_merge($fpTienda, $fpInfo, $fpLegales) as $l)<a href="{{ $l['url'] }}">{{ $l['texto'] }}</a>@endforeach
        </nav>
        @if($fpSocial)
        <div class="fco-social">@foreach($fpSocial as $red => $url)<a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $red }}"><svg viewBox="0 0 24 24">{!! $fp::iconoRed($red) !!}</svg></a>@endforeach</div>
        @endif
        <div class="fco-copy">
            {{ $fpCopy }}@if($fpShowLegal) · {{ $fpLegal['nombre'] }}@if($fpLegal['ruc']) · RUC {{ $fpLegal['ruc'] }}@endif @endif
            @if($footerAddress) · {{ $footerAddress }}@endif
            @if(!empty($footerDevText)) · {{ $footerDevText }} @else · Desarrollado por <a href="https://eskalagroup.com/" target="_blank" rel="noopener">Eskala</a>@endif
        </div>
    </div>
</footer>
