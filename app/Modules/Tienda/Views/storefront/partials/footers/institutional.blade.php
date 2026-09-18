@php
    // Razon social y RUC: obligatorios en la web de un comercio peruano.
    $fpLegal = \App\Modules\Tienda\Storefront\DatosPie::legal($settings ?? [], $project);
    $fpVerLegal = (string) (($settings ?? [])['footer_show_legal'] ?? '1') !== '0'
        && ($fpLegal['nombre'] !== '' || $fpLegal['ruc'] !== '');
    $fpTextoLegal = trim($fpLegal['nombre'].($fpLegal['ruc'] ? ' · RUC '.$fpLegal['ruc'] : ''), ' ·');
@endphp
{{-- FOOTER INSTITUCIONAL: descripción amplia de la marca + políticas + contacto. --}}
@php $legalBase = ($project->custom_domain && request()->getHost() === $project->custom_domain ? '' : '/'.$project->slug); @endphp
<style>
    .fti{margin-top:60px;background:var(--footer-bg);color:var(--footer-text)}
    .fti-main{width:min(1360px,calc(100% - 48px));margin:0 auto;display:grid;grid-template-columns:1.5fr 1fr 1fr;gap:52px;padding:54px 0 40px}
    .fti-brand img{height:auto;max-height:52px;max-width:190px;object-fit:contain}
    .fti-name{font-family:var(--font-title);font-size:21px;font-weight:700}
    .fti-desc{margin:16px 0 0;max-width:460px;font-size:14px;line-height:1.8;opacity:.85}
    .fti h4{margin:0 0 16px;font-family:var(--font-title);font-size:16px;font-weight:600}
    .fti h4::after{content:"";display:block;width:34px;height:1px;margin-top:9px;background:var(--primary)}
    .fti ul{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:11px;font-size:13.5px}
    .fti a{color:inherit;opacity:.85;text-decoration:none}
    .fti a:hover{opacity:1;text-decoration:underline}
    .fti-contact li{display:flex;gap:9px;align-items:baseline;opacity:.85}
    .fti-bottom{border-top:1px solid color-mix(in srgb,currentColor 18%,transparent)}
    .fti-bottom-inner{width:min(1360px,calc(100% - 48px));margin:0 auto;display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px 20px;padding:18px 0;font-size:12px;opacity:.65}
    @media(max-width:900px){.fti-main{grid-template-columns:1fr;gap:32px;padding:40px 0 28px}}
</style>
<footer class="fti site-footer-institutional">
    <div class="fti-main">
        <div class="fti-brand">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $storeName }}" loading="lazy">@else<span class="fti-name">{{ $storeName }}</span>@endif
            <p class="fti-desc">{{ $tagline ?: 'Atendemos con productos seleccionados, entrega coordinada y un equipo que te acompaña antes y después de tu compra.' }}</p>
        </div>
        <div>
            <h4>Institucional</h4>
            <ul>
                <li><a href="{{ $aboutUrl }}">Nosotros</a></li>
                <li><a href="{{ $contactUrl }}">Contacto</a></li>
                <li><a href="{{ url($legalBase.'/reclamaciones') }}">Libro de Reclamaciones</a></li>
                <li><a href="{{ url($legalBase.'/privacidad') }}">Políticas de privacidad</a></li>
                <li><a href="{{ url($legalBase.'/terminos') }}">Términos y condiciones</a></li>
            </ul>
        </div>
        <div>
            <h4>Atención</h4>
            <ul class="fti-contact">
                @if($phone)<li><span>Teléfono</span><a href="tel:{{ preg_replace('/[^\d+]/','',$phone) }}">{{ $phone }}</a></li>@endif
                @include('tienda::storefront.partials.numeros-extra')
                @if($email)<li><span>Correo</span><a href="mailto:{{ $email }}">{{ $email }}</a></li>@endif
                @if($waFooter)<li><span>WhatsApp</span><a href="https://wa.me/{{ $waFooter }}" target="_blank" rel="noopener">Escríbenos</a></li>@endif
                @if($footerAddress)<li><span>Dirección</span><span>{{ $footerAddress }}</span></li>@endif
                @if($footerHours)<li><span>Horario</span><span>{{ $footerHours }}</span></li>@endif
            </ul>
        </div>
    </div>
    <div class="fti-bottom">
        <div class="fti-bottom-inner">
            <span>{{ $footerCopyright ?: '© '.date('Y').' '.$storeName.'. Todos los derechos reservados.' }}@if($fpVerLegal) · {{ $fpTextoLegal }}@endif</span>
            <span>Compra protegida · Atención personalizada</span>
        </div>
    </div>
</footer>
