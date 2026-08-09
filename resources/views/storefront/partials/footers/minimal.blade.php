{{-- FOOTER MINIMAL centrado: logo · menú corto · redes · legal. --}}
@php $legalBase = ($project->custom_domain && request()->getHost() === $project->custom_domain ? '' : '/'.$project->slug); @endphp
<style>
    .ftm{margin-top:60px;padding:44px 20px 28px;background:var(--footer-bg);color:var(--footer-text);text-align:center}
    .ftm-logo img{height:auto;max-height:46px;max-width:170px;object-fit:contain;margin:0 auto}
    .ftm-name{font-family:var(--font-title);font-size:19px;font-weight:700}
    .ftm-links{display:flex;flex-wrap:wrap;justify-content:center;gap:4px 22px;margin:20px 0 6px}
    .ftm-links a{color:inherit;opacity:.85;font-size:13.5px;text-decoration:none}
    .ftm-links a:hover{opacity:1;text-decoration:underline}
    .ftm-social{display:flex;justify-content:center;gap:12px;margin:16px 0}
    .ftm-social a{display:grid;place-items:center;width:38px;height:38px;border:1px solid color-mix(in srgb,currentColor 30%,transparent);border-radius:999px;color:inherit;opacity:.85}
    .ftm-social a:hover{opacity:1;border-color:currentColor}
    .ftm-social svg{width:16px;height:16px}
    .ftm-legal{display:flex;flex-wrap:wrap;justify-content:center;gap:4px 18px;margin-top:8px;font-size:12px;opacity:.7}
    .ftm-legal a{color:inherit;text-decoration:none}
    .ftm-copy{margin-top:14px;font-size:12px;opacity:.55}
</style>
<footer class="ftm site-footer-minimal">
    <div class="ftm-logo">
        @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $storeName }}" loading="lazy">@else<span class="ftm-name">{{ $storeName }}</span>@endif
    </div>
    <nav class="ftm-links" aria-label="Enlaces del pie">
        <a href="{{ $shopBaseFooter }}">Tienda</a>
        <a href="{{ $aboutUrl }}">Nosotros</a>
        <a href="{{ $contactUrl }}">Contacto</a>
        @if($waFooter)<a href="https://wa.me/{{ $waFooter }}" target="_blank" rel="noopener">WhatsApp</a>@endif
    </nav>
    @if(!empty($social) && count(array_filter($social)))
    <div class="ftm-social">
        @foreach($social as $red => $url)
        @if($url)<a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $red }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">{!! $svgIcons[$red] ?? $svgIcons['link'] ?? '<circle cx="12" cy="12" r="9"/>' !!}</svg></a>@endif
        @endforeach
    </div>
    @endif
    <div class="ftm-legal">
        <a href="{{ url($legalBase.'/reclamaciones') }}">Libro de Reclamaciones</a>
        <a href="{{ url($legalBase.'/privacidad') }}">Privacidad</a>
        <a href="{{ url($legalBase.'/terminos') }}">Términos</a>
    </div>
    <p class="ftm-copy">{{ $footerCopyright ?: '© '.date('Y').' '.$storeName.'. Todos los derechos reservados.' }}</p>
</footer>
