{{-- PIE "TARJETA FLOTANTE" (footer_layout = tarjeta).
     El pie no es una banda: es un objeto. Una tarjeta grande y redondeada en
     el color del pie flota sobre el fondo de la página; a la izquierda un
     panel en color de marca con el logo y la invitación a escribir, a la
     derecha las columnas de enlaces. Los derechos y los pagos van fuera,
     en una línea discreta. Para tiendas modernas y de estilo de vida. --}}
@php
    $fp = \App\Modules\Tienda\Storefront\DatosPie::class;
    $fpBase = $fp::base($project);
    $fpOn = static fn (string $k, string $def = '1') => (string) ($settings[$k] ?? $def) !== '0';
    $fpInfo = $fp::enlaces($settings['footer_pages'] ?? '', $fpBase);
    $fpTienda = $fp::enlaces($settings['footer_store_pages'] ?? '', $fpBase);
    $fpLegales = $fp::legales($fpBase);
    $fpLegal = $fp::legal($settings, $project);
    $fpShowLegal = $fpOn('footer_show_legal') && ($fpLegal['nombre'] !== '' || $fpLegal['ruc'] !== '');
    $fpSocial = ($fpOn('footer_show_socials') && $fpOn('footer_show_social')) ? ($social ?? []) : [];
    $fpCats = $fpOn('footer_show_categories') ? collect($navCategories ?? [])->take(6) : collect();
    $fpPagos = $fpOn('footer_show_payments') ? $fp::pagos($settings) : [];
    $fpWa = $waFooter ?? '';
    $fpLogoAlto = max(24, min(160, (int) ($settings['footer_logo_height'] ?? 44)));
    $fpCopy = $footerCopyright ?? ('© '.date('Y').' '.$storeName);
    $fpTag = $tagline ?? '';
    $fpIco = $svgIcons ?? [];
@endphp
<style>
    .fta{margin-top:72px;padding:0 24px 28px;color:var(--text,#111827)}
    .fta-card{width:min(1360px,100%);margin:0 auto;background:var(--fp-bg,var(--footer-bg));color:var(--fp-text,var(--footer-text));border-radius:30px;overflow:hidden;display:grid;grid-template-columns:minmax(280px,5fr) 7fr;box-shadow:0 30px 60px -30px rgba(0,0,0,.45);--fp-fondo:var(--fp-bg,var(--footer-bg))}
    .fta-panel{background:linear-gradient(160deg,var(--primary),color-mix(in srgb,var(--primary) 55%,#000));color:#fff;padding:44px 40px;display:flex;flex-direction:column;gap:18px;position:relative;overflow:hidden}
    .fta-panel::after{content:"";position:absolute;right:-70px;bottom:-90px;width:260px;height:260px;border-radius:50%;background:rgba(255,255,255,.12)}
    .fta-panel img{height:{{ $fpLogoAlto }}px;max-width:220px;width:auto;object-fit:contain;background:#fff;padding:8px 12px;border-radius:12px;align-self:flex-start}
    .fta-panel .nombre{font-family:var(--font-title,inherit);font-size:28px;font-weight:800;letter-spacing:-.01em}
    .fta-panel p{margin:0;font-size:15px;line-height:1.6;opacity:.92;max-width:34ch}
    .fta-panel .cta{position:relative;z-index:1;margin-top:auto;align-self:flex-start;background:#fff;color:var(--primary);text-decoration:none;font-weight:800;padding:13px 20px;border-radius:999px;display:inline-flex;gap:10px;align-items:center}
    .fta-panel .cta svg{width:18px;height:18px;fill:currentColor}
    .fta-social{display:flex;gap:10px;position:relative;z-index:1}
    .fta-social a{width:38px;height:38px;border-radius:12px;background:rgba(255,255,255,.16);display:grid;place-items:center;color:#fff}
    .fta-social svg{width:16px;height:16px;fill:currentColor}
    .fta-cols{padding:44px 40px;display:grid;grid-template-columns:repeat(3,1fr);gap:32px}
    .fta-cols h4{margin:0 0 16px;font-size:13px;font-weight:700;letter-spacing:.04em;opacity:.7}
    .fta-cols ul{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:11px;font-size:14.5px}
    .fta-cols a{color:inherit;text-decoration:none}
    .fta-cols a:hover{text-decoration:underline;text-underline-offset:4px}
    .fta-cols li.dato{display:flex;gap:9px;align-items:flex-start;line-height:1.45}
    .fta-cols li.dato svg{width:16px;height:16px;flex-shrink:0;stroke:currentColor;fill:none;stroke-width:1.8;opacity:.75;margin-top:3px}
    .fta-meta{width:min(1360px,100%);margin:16px auto 0;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:10px 20px;font-size:12.5px;color:var(--muted,#6b7280);padding:0 8px}
    .fta-meta a{color:inherit}
    .fta-pagos{display:flex;gap:6px}
    .fp-pago{display:inline-grid;place-items:center;height:22px;padding:0 8px;border-radius:5px;font-size:10.5px;font-weight:800}
    @media(max-width:960px){.fta-card{grid-template-columns:1fr}.fta-cols{grid-template-columns:1fr 1fr;padding:32px 28px}}
    @media(max-width:560px){.fta{padding:0 12px 22px}.fta-card{border-radius:22px}.fta-panel{padding:30px 24px}.fta-cols{grid-template-columns:1fr;padding:26px 24px}}
</style>
<footer class="fta" id="pie">
    <div class="fta-card">
        <div class="fta-panel">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $storeName }}" loading="lazy">@else<div class="nombre">{{ $storeName }}</div>@endif
            @if($fpTag)<p>{{ $fpTag }}</p>@endif
            @if($fpSocial)
            <div class="fta-social">@foreach($fpSocial as $red => $url)<a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $red }}"><svg viewBox="0 0 24 24">{!! $fp::iconoRed($red) !!}</svg></a>@endforeach</div>
            @endif
            @if($fpWa)
            <a class="cta" href="https://wa.me/{{ $fpWa }}" target="_blank" rel="noopener"><svg viewBox="0 0 24 24"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.5-.6c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.2.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.2-.3-.3-.6-.4zM12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2z"/></svg>Escríbenos</a>
            @else
            <a class="cta" href="{{ $contactUrl }}">Contáctanos</a>
            @endif
        </div>
        <div class="fta-cols">
            @if($fpCats->isNotEmpty())
            <div><h4>Comprar</h4><ul>@foreach($fpCats as $cat)<li><a href="{{ $shopBaseFooter }}?category={{ $cat->id }}">{{ $cat->name }}</a></li>@endforeach<li><a href="{{ $shopBaseFooter }}">Ver todo</a></li></ul></div>
            @endif
            <div><h4>Conócenos</h4><ul>
                <li><a href="{{ $aboutUrl }}">Nosotros</a></li><li><a href="{{ $contactUrl }}">Contacto</a></li>
                @foreach(array_merge($fpTienda, $fpInfo) as $l)<li><a href="{{ $l['url'] }}">{{ $l['texto'] }}</a></li>@endforeach
                @foreach($fpLegales as $l)<li><a href="{{ $l['url'] }}">{{ $l['texto'] }}</a></li>@endforeach
            </ul></div>
            <div><h4>Contacto</h4><ul>
                @if($phone)<li class="dato"><svg viewBox="0 0 24 24">{!! $fpIco['phone'] ?? '' !!}</svg><a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a></li>@endif
                @include('tienda::storefront.partials.numeros-extra')
                @if($email)<li class="dato"><svg viewBox="0 0 24 24">{!! $fpIco['mail'] ?? '' !!}</svg><a href="mailto:{{ $email }}">{{ $email }}</a></li>@endif
                @if($footerAddress)<li class="dato"><svg viewBox="0 0 24 24">{!! $fpIco['pin'] ?? '' !!}</svg><span>{{ $footerAddress }}</span></li>@endif
                @if($footerHours)<li class="dato"><svg viewBox="0 0 24 24">{!! $fpIco['clock'] ?? '' !!}</svg><span>{{ $footerHours }}</span></li>@endif
                @if($fpShowLegal)<li class="dato"><svg viewBox="0 0 24 24">{!! $fpIco['warranty'] ?? '' !!}</svg><span>{{ $fpLegal['nombre'] }}@if($fpLegal['ruc']) · RUC {{ $fpLegal['ruc'] }}@endif</span></li>@endif
            </ul></div>
        </div>
    </div>
    <div class="fta-meta">
        <span>{{ $fpCopy }} @if(!empty($footerDevText)) · {{ $footerDevText }} @else · Desarrollado por <a href="https://eskalagroup.com/" target="_blank" rel="noopener">Eskala</a>@endif</span>
        @if($fpPagos)<div class="fta-pagos" aria-label="Medios de pago">@foreach($fpPagos as $p){!! $fp::fichaPago($p) !!}@endforeach</div>@endif
    </div>
</footer>
