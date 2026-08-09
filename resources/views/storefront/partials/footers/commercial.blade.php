{{-- FOOTER COMERCIAL: categorías + enlaces + contacto directo + pagos. --}}
@php $legalBase = ($project->custom_domain && request()->getHost() === $project->custom_domain ? '' : '/'.$project->slug); @endphp
<style>
    .ftc{margin-top:60px;background:var(--footer-bg);color:var(--footer-text)}
    .ftc-cta{background:var(--primary);color:#fff}
    .ftc-cta-inner{width:min(1360px,calc(100% - 48px));margin:0 auto;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px 24px;padding:18px 0}
    .ftc-cta strong{font-size:16px}
    .ftc-cta a{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:#fff;color:var(--primary)!important;opacity:1!important;border-radius:8px;font-size:13px;font-weight:800;text-decoration:none}
    .ftc-main{width:min(1360px,calc(100% - 48px));margin:0 auto;display:grid;grid-template-columns:1.2fr 1fr 1fr 1.1fr;gap:44px;padding:46px 0 36px}
    .ftc-brand img{height:auto;max-height:46px;max-width:170px;object-fit:contain}
    .ftc h4{margin:0 0 14px;font-size:13px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
    .ftc ul{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px;font-size:13.5px}
    .ftc a{color:inherit;opacity:.85;text-decoration:none}
    .ftc a:hover{opacity:1;color:var(--primary)}
    .ftc-desc{margin:14px 0 0;font-size:13px;line-height:1.7;opacity:.8}
    .ftc-pay{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
    .ftc-pay span{padding:6px 11px;border:1px solid color-mix(in srgb,currentColor 25%,transparent);border-radius:6px;font-size:11.5px;font-weight:700;opacity:.9}
    .ftc-bottom{border-top:1px solid color-mix(in srgb,currentColor 18%,transparent)}
    .ftc-bottom-inner{width:min(1360px,calc(100% - 48px));margin:0 auto;display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px 20px;padding:16px 0;font-size:12px;opacity:.65}
    .ftc-bottom a{color:inherit;text-decoration:none;margin-left:14px}
    @media(max-width:1000px){.ftc-main{grid-template-columns:1fr 1fr;gap:30px}}
    @media(max-width:640px){.ftc-main{grid-template-columns:1fr}}
</style>
<footer class="ftc site-footer-commercial">
    @if($waFooter)
    <div class="ftc-cta"><div class="ftc-cta-inner">
        <strong>¿Necesitas ayuda para elegir? Te asesoramos sin costo.</strong>
        <a href="https://wa.me/{{ $waFooter }}" target="_blank" rel="noopener">Hablar por WhatsApp →</a>
    </div></div>
    @endif
    <div class="ftc-main">
        <div class="ftc-brand">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $storeName }}" loading="lazy">@else<strong style="font-size:19px">{{ $storeName }}</strong>@endif
            <p class="ftc-desc">{{ $tagline ?: 'Productos con garantía y stock real, listos para entrega.' }}</p>
            <div class="ftc-pay">
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
                <li><a href="{{ $shopBaseFooter }}"><strong>Ver todo →</strong></a></li>
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
            <h4>Contacto directo</h4>
            <ul>
                @if($phone)<li><a href="tel:{{ preg_replace('/[^\d+]/','',$phone) }}">📞 {{ $phone }}</a></li>@endif
                @if($email)<li><a href="mailto:{{ $email }}">✉ {{ $email }}</a></li>@endif
                @if($footerAddress)<li>📍 {{ $footerAddress }}</li>@endif
                @if($footerHours)<li>🕐 {{ $footerHours }}</li>@endif
            </ul>
        </div>
    </div>
    <div class="ftc-bottom"><div class="ftc-bottom-inner">
        <span>{{ $footerCopyright ?: '© '.date('Y').' '.$storeName.'. Todos los derechos reservados.' }}</span>
        <span>Compra segura · Productos garantizados</span>
    </div></div>
</footer>
