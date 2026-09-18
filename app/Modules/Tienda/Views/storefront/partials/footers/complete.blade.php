@php
    // Razon social y RUC: obligatorios en la web de un comercio peruano.
    $fpLegal = \App\Modules\Tienda\Storefront\DatosPie::legal($settings ?? [], $project);
    $fpVerLegal = (string) (($settings ?? [])['footer_show_legal'] ?? '1') !== '0'
        && ($fpLegal['nombre'] !== '' || $fpLegal['ruc'] !== '');
    $fpTextoLegal = trim($fpLegal['nombre'].($fpLegal['ruc'] ? ' · RUC '.$fpLegal['ruc'] : ''), ' ·');
@endphp
{{-- FOOTER COMPLETO: marca+descripción · categorías · información · contacto
     + fila de confianza (pagos/seguridad) + legal. El más denso. --}}
@php $legalBase = ($project->custom_domain && request()->getHost() === $project->custom_domain ? '' : '/'.$project->slug); @endphp
<style>
    .ftk{margin-top:60px;background:var(--footer-bg);color:var(--footer-text)}
    .ftk-trust{border-bottom:1px solid color-mix(in srgb,currentColor 16%,transparent)}
    .ftk-trust-inner{width:min(1360px,calc(100% - 48px));margin:0 auto;display:grid;grid-template-columns:repeat(3,1fr);gap:18px;padding:22px 0}
    .ftk-trust b{display:block;font-size:13.5px}
    .ftk-trust small{opacity:.7;font-size:12px}
    .ftk-main{width:min(1360px,calc(100% - 48px));margin:0 auto;display:grid;grid-template-columns:1.4fr 1fr 1fr 1.2fr;gap:44px;padding:44px 0 34px}
    .ftk-brand img{height:auto;max-height:46px;max-width:180px;object-fit:contain}
    .ftk-desc{margin:14px 0 0;font-size:13px;line-height:1.75;opacity:.82;max-width:360px}
    .ftk h4{margin:0 0 14px;font-size:13px;font-weight:800;letter-spacing:.07em;text-transform:uppercase}
    .ftk ul{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px;font-size:13.5px}
    .ftk a{color:inherit;opacity:.85;text-decoration:none}
    .ftk a:hover{opacity:1;color:var(--primary)}
    .ftk-pay{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
    .ftk-pay span{padding:6px 11px;border:1px solid color-mix(in srgb,currentColor 25%,transparent);border-radius:6px;font-size:11.5px;font-weight:700;opacity:.9}
    .ftk-bottom{border-top:1px solid color-mix(in srgb,currentColor 16%,transparent)}
    .ftk-bottom-inner{width:min(1360px,calc(100% - 48px));margin:0 auto;display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px 20px;padding:16px 0;font-size:12px;opacity:.65}
    .ftk-bottom nav a{color:inherit;text-decoration:none;margin-left:16px}
    @media(max-width:1000px){.ftk-main{grid-template-columns:1fr 1fr;gap:30px}.ftk-trust-inner{grid-template-columns:1fr}}
    @media(max-width:640px){.ftk-main{grid-template-columns:1fr}}
</style>
<footer class="ftk site-footer-complete">
    <div class="ftk-trust"><div class="ftk-trust-inner">
        <div><b>Compra protegida</b><small>Tus datos viajan cifrados</small></div>
        <div><b>Productos garantizados</b><small>Respaldo real en cada pedido</small></div>
        <div><b>Atención personalizada</b><small>Te acompañamos post venta</small></div>
    </div></div>
    <div class="ftk-main">
        <div class="ftk-brand">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $storeName }}" loading="lazy">@else<strong style="font-size:19px">{{ $storeName }}</strong>@endif
            <p class="ftk-desc">{{ $tagline ?: 'Catálogo seleccionado, precios claros y entrega coordinada contigo.' }}</p>
            <div class="ftk-pay">
                @foreach(($payLogos ?? ['Yape','Plin','Visa','Mastercard','Transferencia']) as $pay)
                <span>{{ is_array($pay) ? ($pay['name'] ?? '') : $pay }}</span>
                @endforeach
            </div>
        </div>
        <div>
            <h4>Categorías</h4>
            <ul>
                @foreach($categories->take(6) as $cat)
                <li><a href="{{ $shopBaseFooter }}?category={{ $cat->id }}">{{ $cat->name }}</a></li>
                @endforeach
            </ul>
        </div>
        <div>
            <h4>Información</h4>
            <ul>
                <li><a href="{{ $aboutUrl }}">Nosotros</a></li>
                <li><a href="{{ $contactUrl }}">Contacto</a></li>
                <li><a href="{{ url($legalBase.'/reclamaciones') }}">Libro de Reclamaciones</a></li>
                <li><a href="{{ url($legalBase.'/privacidad') }}">Privacidad</a></li>
                <li><a href="{{ url($legalBase.'/terminos') }}">Términos</a></li>
            </ul>
        </div>
        <div>
            <h4>Contacto</h4>
            <ul>
                @if($phone)<li><a href="tel:{{ preg_replace('/[^\d+]/','',$phone) }}">Teléfono: {{ $phone }}</a></li>@endif
                @include('tienda::storefront.partials.numeros-extra')
                @if($email)<li><a href="mailto:{{ $email }}">{{ $email }}</a></li>@endif
                @if($waFooter)<li><a href="https://wa.me/{{ $waFooter }}" target="_blank" rel="noopener">WhatsApp directo</a></li>@endif
                @if($footerAddress)<li>{{ $footerAddress }}</li>@endif
                @if($footerHours)<li>{{ $footerHours }}</li>@endif
            </ul>
        </div>
    </div>
    <div class="ftk-bottom"><div class="ftk-bottom-inner">
        <span>{{ $footerCopyright ?: '© '.date('Y').' '.$storeName.'. Todos los derechos reservados.' }}@if($fpVerLegal) · {{ $fpTextoLegal }}@endif</span>
        <nav><a href="{{ url($legalBase.'/privacidad') }}">Privacidad</a><a href="{{ url($legalBase.'/terminos') }}">Términos</a></nav>
    </div></div>
</footer>
