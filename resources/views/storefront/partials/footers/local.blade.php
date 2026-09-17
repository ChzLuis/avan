{{-- PIE "VISÍTANOS" (footer_layout = local).
     Para negocios con tienda física: el mapa de la dirección ocupa media
     pantalla y al lado van la dirección, el horario en líneas, el teléfono y
     el botón de WhatsApp. Debajo, una sola fila de enlaces y los derechos.
     Sin dirección, el mapa deja su sitio a una tarjeta de contacto grande. --}}
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
    $fpHoras = $fp::horario($footerHours ?? '');
    $fpDir = trim((string) ($footerAddress ?? ''));
    $fpMapa = $fpDir !== '' && $fpOn('footer_show_map') ? 'https://www.google.com/maps?q='.rawurlencode($fpDir).'&output=embed' : '';
    $fpComoLlegar = $fpDir !== '' ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($fpDir) : '';
    $fpIco = $svgIcons ?? [];
@endphp
<style>
    .flo{margin-top:64px;background:var(--fp-bg,var(--footer-bg));color:var(--fp-text,var(--footer-text));--fp-fondo:var(--fp-bg,var(--footer-bg))}
    .flo-in{width:min(1360px,calc(100% - 48px));margin:0 auto}
    .flo-top{display:grid;grid-template-columns:1.1fr 1fr;gap:44px;padding:54px 0 40px;align-items:stretch}
    .flo-mapa{border-radius:20px;overflow:hidden;min-height:320px;background:color-mix(in srgb,currentColor 8%,transparent)}
    .flo-mapa iframe{width:100%;height:100%;min-height:320px;border:0;display:block;filter:saturate(.9)}
    .flo-sin{height:100%;min-height:320px;display:flex;flex-direction:column;justify-content:center;padding:36px;border-radius:20px;background:linear-gradient(160deg,var(--primary),color-mix(in srgb,var(--primary) 55%,#000));color:#fff}
    .flo-sin strong{font-family:var(--font-title,inherit);font-size:30px;line-height:1.1}
    .flo-sin p{margin:12px 0 0;opacity:.9;max-width:36ch}
    .flo-datos h3{margin:0 0 6px;font-family:var(--font-title,inherit);font-size:34px;font-weight:700;line-height:1.05;color:var(--fp-title,#fff)}
    .flo-datos .nombre{font-size:15px;opacity:.75;margin-bottom:26px}
    .flo-datos dl{margin:0;display:grid;grid-template-columns:auto 1fr;gap:14px 18px;font-size:15px}
    .flo-datos dt{display:flex;align-items:center;gap:8px;font-weight:700;color:var(--fp-title,#fff);white-space:nowrap}
    .flo-datos dt svg{width:18px;height:18px;stroke:var(--primary);fill:none;stroke-width:2}
    .flo-datos dd{margin:0;line-height:1.55}
    .flo-datos dd a{color:inherit;text-decoration:none;border-bottom:1px solid color-mix(in srgb,currentColor 35%,transparent)}
    .flo-horas{display:flex;flex-direction:column;gap:4px}
    .flo-botones{display:flex;flex-wrap:wrap;gap:10px;margin-top:26px}
    .flo-btn{display:inline-flex;align-items:center;gap:9px;padding:12px 18px;border-radius:12px;text-decoration:none;font-weight:700;font-size:14px}
    .flo-btn.wa{background:#25d366;color:#052e16}
    .flo-btn.ir{background:var(--fp-sup,#fff);color:var(--fp-sup-ink,#111827)}
    .flo-btn svg{width:18px;height:18px;fill:currentColor}
    .flo-nav{display:flex;flex-wrap:wrap;align-items:center;gap:8px 26px;padding:20px 0;border-top:1px solid var(--fp-line,color-mix(in srgb,currentColor 18%,transparent));font-size:14px}
    .flo-nav a{color:inherit;text-decoration:none;opacity:.85}
    .flo-nav a:hover{opacity:1;text-decoration:underline;text-underline-offset:4px}
    .flo-nav .social{margin-left:auto;display:flex;gap:12px}
    .flo-nav .social svg{width:17px;height:17px;fill:currentColor}
    .flo-copy{padding:0 0 22px;font-size:12.5px;opacity:.65}
    .flo-copy a{color:inherit}
    @media(max-width:900px){.flo-top{grid-template-columns:1fr;gap:28px;padding:40px 0 28px}.flo-mapa,.flo-mapa iframe,.flo-sin{min-height:240px}.flo-datos h3{font-size:28px}.flo-nav .social{margin-left:0;width:100%}}
</style>
<footer class="flo" id="pie">
    <div class="flo-in">
        <div class="flo-top">
            <div class="flo-mapa">
                @if($fpMapa)
                <iframe src="{{ $fpMapa }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Mapa de {{ $storeName }}" allowfullscreen></iframe>
                @else
                <div class="flo-sin"><strong>{{ $storeName }}</strong><p>{{ $tagline ?: 'Atendemos con gusto. Escríbenos o llámanos y te ayudamos.' }}</p></div>
                @endif
            </div>
            <div class="flo-datos">
                <h3>Visítanos</h3>
                @php
                    // El nombre comercial no sustituye a la razon social: si son
                    // distintos se dicen los dos, con el RUC al final.
                    $floLegal = $fpShowLegal
                        ? trim(($fpLegal['nombre'] !== $storeName ? ' · '.$fpLegal['nombre'] : '')
                            .($fpLegal['ruc'] ? ' · RUC '.$fpLegal['ruc'] : ''))
                        : '';
                @endphp
                <div class="nombre">{{ $storeName }} {{ $floLegal }}</div>
                <dl>
                    @if($fpDir)<dt><svg viewBox="0 0 24 24">{!! $fpIco['pin'] ?? '' !!}</svg>Dirección</dt><dd>{{ $fpDir }}</dd>@endif
                    @if($fpHoras)<dt><svg viewBox="0 0 24 24">{!! $fpIco['clock'] ?? '' !!}</svg>Horario</dt><dd class="flo-horas">@foreach($fpHoras as $h)<span>{{ $h }}</span>@endforeach</dd>@endif
                    @if($phone)<dt><svg viewBox="0 0 24 24">{!! $fpIco['phone'] ?? '' !!}</svg>Teléfono</dt><dd><a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a></dd>@endif
                    @include('storefront.partials.numeros-extra')
                    @if($email)<dt><svg viewBox="0 0 24 24">{!! $fpIco['mail'] ?? '' !!}</svg>Correo</dt><dd><a href="mailto:{{ $email }}">{{ $email }}</a></dd>@endif
                </dl>
                <div class="flo-botones">
                    @if($fpWa)<a class="flo-btn wa" href="https://wa.me/{{ $fpWa }}" target="_blank" rel="noopener"><svg viewBox="0 0 24 24"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.5-.6c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.2.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.2-.3-.3-.6-.4zM12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2z"/></svg>WhatsApp</a>@endif
                    @if($fpComoLlegar)<a class="flo-btn ir" href="{{ $fpComoLlegar }}" target="_blank" rel="noopener">Cómo llegar</a>@endif
                </div>
            </div>
        </div>
        <nav class="flo-nav" aria-label="Enlaces del pie">
            <a href="{{ $shopBaseFooter }}">Tienda</a>
            @foreach($fpCats as $cat)<a href="{{ $shopBaseFooter }}?category={{ $cat->id }}">{{ $cat->name }}</a>@endforeach
            <a href="{{ $aboutUrl }}">Nosotros</a><a href="{{ $contactUrl }}">Contacto</a>
            @foreach(array_merge($fpTienda, $fpInfo, $fpLegales) as $l)<a href="{{ $l['url'] }}">{{ $l['texto'] }}</a>@endforeach
            @if($fpSocial)<div class="social">@foreach($fpSocial as $red => $url)<a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $red }}"><svg viewBox="0 0 24 24">{!! $fp::iconoRed($red) !!}</svg></a>@endforeach</div>@endif
        </nav>
        <div class="flo-copy">{{ $fpCopy }} @if(!empty($footerDevText)) · {{ $footerDevText }} @else · Desarrollado por <a href="https://eskalagroup.com/" target="_blank" rel="noopener">Eskala</a>@endif</div>
    </div>
</footer>
