{{-- FOOTER SIMPLE: una sola línea con marca, legales y copyright. --}}
@php $legalBase = ($project->custom_domain && request()->getHost() === $project->custom_domain ? '' : '/'.$project->slug); @endphp
<style>
    .fts{margin-top:60px;background:var(--footer-bg);color:var(--footer-text)}
    .fts-inner{width:min(1360px,calc(100% - 48px));margin:0 auto;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px 26px;padding:22px 0;font-size:13px}
    .fts-brand{display:flex;align-items:center;gap:10px;font-weight:700}
    .fts-brand img{height:auto;max-height:32px;max-width:130px;object-fit:contain}
    .fts nav{display:flex;flex-wrap:wrap;gap:4px 20px}
    .fts a{color:inherit;opacity:.8;text-decoration:none}
    .fts a:hover{opacity:1;text-decoration:underline}
    .fts-copy{opacity:.6;font-size:12px}
    @media(max-width:760px){.fts-inner{flex-direction:column;text-align:center}}
</style>
<footer class="fts site-footer-simple">
    <div class="fts-inner">
        <span class="fts-brand">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $storeName }}" loading="lazy">@else{{ $storeName }}@endif
        </span>
        <nav aria-label="Enlaces legales">
            <a href="{{ $contactUrl }}">Contacto</a>
            <a href="{{ url($legalBase.'/reclamaciones') }}">Libro de Reclamaciones</a>
            <a href="{{ url($legalBase.'/privacidad') }}">Privacidad</a>
            <a href="{{ url($legalBase.'/terminos') }}">Términos</a>
        </nav>
        <span class="fts-copy">{{ $footerCopyright ?: '© '.date('Y').' '.$storeName }}</span>
    </div>
</footer>
