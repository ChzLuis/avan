{{-- FOOTER TECNOLÓGICO PRO — variante reutilizable (footer_layout = technology).
     4 columnas: Empresa · Categorías · Información · Contacto + barra inferior.
     Todo sale de la configuración existente; cada bloque se oculta si no hay datos.
     Los colores derivan de la paleta de la tienda salvo que se personalicen. --}}
@php
    $legalBase = ($project->custom_domain && request()->getHost() === $project->custom_domain ? '' : '/'.$project->slug);
    $ftCats  = collect($navCategories ?? [])->take(max(3, min(8, (int) ($settings['footer_cats_limit'] ?? 5))));
    $ftOn    = static fn (string $k, string $def = '1') => (string) ($settings[$k] ?? $def) !== '0';
    $ftShowCats     = $ftOn('footer_show_categories') && $ftCats->isNotEmpty();
    $ftShowBenefits = $ftOn('footer_show_benefits');
    $ftShowCta      = $ftOn('footer_show_help_cta');
    $ftShowSecure   = $ftOn('footer_show_secure');
    $ftShowPay      = $ftOn('footer_show_payments');
    // Identificacion legal del comercio (razon social + RUC). Obligatoria en Peru
    // y hasta ahora no se mostraba en ninguna pagina publica aunque el dato ya
    // estaba guardado. Se puede ocultar, pero viene activa por defecto.
    $ftLegalName = trim((string) ($settings['legal_name'] ?? '')) ?: trim((string) $project->name);
    $ftRuc       = preg_replace('/[^0-9]/', '', (string) ($settings['ruc'] ?? ''));
    $ftShowLegal = (string) ($settings['footer_show_legal'] ?? '1') !== '0' && ($ftLegalName !== '' || $ftRuc !== '');

    // Sello de confianza: medalla dorada SSL, escudo HTTPS o el candado simple.
    $ftSecureStyle = in_array($settings['footer_secure_style'] ?? 'both', ['gold', 'https', 'both', 'lock'], true)
        ? ($settings['footer_secure_style'] ?? 'both') : 'both';
    $ftSecureTitle = trim((string) ($settings['footer_secure_title'] ?? '')) ?: 'Compra segura y protegida';
    $ftSecureText  = trim((string) ($settings['footer_secure_text'] ?? 'Sitio protegido con certificado SSL'));
    $ftShowSocial   = $ftOn('footer_show_socials') && !empty($social);
    $ftPay   = $payLogos ?? ['visa' => 'Visa', 'mastercard' => 'Mastercard', 'yape' => 'Yape', 'plin' => 'Plin'];
    $ftCatUrl = fn ($cat) => ($shopBaseFooter ?? '#').'?category='.$cat->id;

    // Paleta: deriva de la tienda; se puede fijar desde el constructor.
    $ftBg     = $settings['footer_bg_color']     ?? '#061B36';
    $ftBg2    = $settings['footer_bg2_color']    ?? '#07284A';
    $ftAccent = $settings['footer_accent_color'] ?? ($settings['accent_color'] ?? '#16BDF2');

    // Beneficios: reutiliza los mismos textos de la banda de confianza.
    $ftBenefits = collect(range(1, 4))->map(fn ($i) => [
        'title' => trim((string) ($settings['trust_text_'.$i] ?? '')),
        'desc'  => trim((string) ($settings['trust_description_'.$i] ?? '')),
        'icon'  => trim((string) ($settings['trust_icon_'.$i] ?? 'check')),
    ])->filter(fn ($b) => $b['title'] !== '')->take(4);

    $ftIcons = [
        'truck'   => '<path d="M3 6h11v11H3z"/><path d="M14 10h4l3 3v4h-7z"/><circle cx="7" cy="19" r="2"/><circle cx="18" cy="19" r="2"/>',
        'shield'  => '<path d="M12 3l8 3v6c0 5-3.4 8.4-8 9-4.6-.6-8-4-8-9V6l8-3Z"/><path d="m9 12 2 2 4-4"/>',
        'card'    => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
        'support' => '<path d="M4 13a8 8 0 0 1 16 0"/><path d="M4 13v3a2 2 0 0 0 2 2h1v-5H6a2 2 0 0 0-2 2Z"/><path d="M20 13v3a2 2 0 0 1-2 2h-1v-5h1a2 2 0 0 1 2 2Z"/>',
        'store'   => '<path d="M3 9l2-5h14l2 5"/><path d="M5 9v11h14V9"/><path d="M9 20v-6h6v6"/>',
        'check'   => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
    ];
    // Logotipos de las pasarelas con sus colores oficiales; si llega una que no
    // conocemos se muestra su nombre en la misma placa blanca.

    $ftSocialPaths = [
        'facebook'  => '<path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H8v3h2v7h3v-7h3l1-3h-4v-2c0-.6.4-1 1-1Z"/>',
        'instagram' => '<path d="M12 8.6A3.4 3.4 0 1 0 12 15.4 3.4 3.4 0 0 0 12 8.6Zm0 5.6a2.2 2.2 0 1 1 0-4.4 2.2 2.2 0 0 1 0 4.4Z"/><path d="M17 2H7a5 5 0 0 0-5 5v10a5 5 0 0 0 5 5h10a5 5 0 0 0 5-5V7a5 5 0 0 0-5-5Zm3.6 15a3.6 3.6 0 0 1-3.6 3.6H7A3.6 3.6 0 0 1 3.4 17V7A3.6 3.6 0 0 1 7 3.4h10A3.6 3.6 0 0 1 20.6 7v10Z"/><circle cx="17.2" cy="6.8" r="1"/>',
        'tiktok'    => '<path d="M16.5 2h-2.7v13.1a2.4 2.4 0 1 1-2-2.4V10a5.4 5.4 0 1 0 4.7 5.3V8.7c.9.7 2.1 1.1 3.3 1.1V7.1c-1.9 0-3.3-1.6-3.3-3.4V2Z"/>',
        'youtube'   => '<path d="M22 12s0-3.2-.4-4.7a2.5 2.5 0 0 0-1.7-1.7C18.3 5.2 12 5.2 12 5.2s-6.3 0-7.9.4A2.5 2.5 0 0 0 2.4 7.3C2 8.8 2 12 2 12s0 3.2.4 4.7c.2.9.9 1.5 1.7 1.7 1.6.4 7.9.4 7.9.4s6.3 0 7.9-.4a2.5 2.5 0 0 0 1.7-1.7C22 15.2 22 12 22 12ZM10 15.1V8.9l5.2 3.1-5.2 3.1Z"/>',
        'linkedin'  => '<path d="M6.9 8.5H4V20h2.9V8.5ZM5.4 4a1.7 1.7 0 1 0 0 3.4 1.7 1.7 0 0 0 0-3.4ZM20 13.2c0-3-1.6-4.4-3.7-4.4-1.7 0-2.5.9-2.9 1.6V8.5H10.5V20h2.9v-6.3c0-1.3.7-2.1 1.8-2.1s1.9.8 1.9 2.1V20H20v-6.8Z"/>',
    ];
    $ftSocial = static fn (string $k) => '<svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true">'.($ftSocialPaths[$k] ?? $ftSocialPaths['facebook']).'</svg>';
    $categoryIconPaths = $categoryIconPaths ?? null;
    $autoCategoryIcon  = $autoCategoryIcon ?? null;
    $ftSvg = static fn (string $k) => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($ftIcons[$k] ?? $ftIcons['check']).'</svg>';
    // Reutiliza el mapa de iconos del template (informatica, hogar, moda...).
    // Si el footer se usa fuera de ese contexto, cae al icono generico de tienda.
    $ftCatIcon = static function ($name) use ($ftSvg, $categoryIconPaths, $autoCategoryIcon) {
        $paths = $categoryIconPaths;
        $auto  = $autoCategoryIcon;
        if (is_array($paths) && is_callable($auto)) {
            $key = $auto($name);
            if (isset($paths[$key])) {
                return '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths[$key].'</svg>';
            }
        }
        return $ftSvg('store');
    };
@endphp
<style>
    .ftt{--ftt-bg:{{ $ftBg }};--ftt-bg2:{{ $ftBg2 }};--ftt-ac:{{ $ftAccent }};
        --ftt-txt:#fff;--ftt-txt2:#B8C9DB;--ftt-muted:#849BB3;
        --ftt-bd:rgba(255,255,255,.10);--ftt-card:rgba(255,255,255,.025);--ftt-cardbd:rgba(255,255,255,.16);
        position:relative;overflow:hidden;color:var(--ftt-txt);
        background:linear-gradient(135deg,var(--ftt-bg) 0%,var(--ftt-bg2) 100%)}
    /* Trama tecnológica muy tenue en los extremos (no estorba la lectura) */
    .ftt::before{content:'';position:absolute;inset:0;pointer-events:none;opacity:.05;
        background-image:linear-gradient(var(--ftt-ac) 1px,transparent 1px),linear-gradient(90deg,var(--ftt-ac) 1px,transparent 1px);
        background-size:44px 44px;
        -webkit-mask-image:linear-gradient(90deg,#000 0,transparent 22%,transparent 78%,#000 100%);
                mask-image:linear-gradient(90deg,#000 0,transparent 22%,transparent 78%,#000 100%)}
    .ftt-inner{position:relative;width:var(--header-layout-width,min(1440px,calc(100% - 64px)));max-width:calc(100% - 40px);margin:0 auto;
        display:grid;grid-template-columns:1.5fr 1fr 1fr 1.2fr;gap:40px;padding:56px 0 44px}
    .ftt details>summary{list-style:none;display:block}
    .ftt details>summary::-webkit-details-marker{display:none}
    .ftt details>summary::marker{content:''}
    .ftt .ftt-chev{display:none}
    .ftt h4{margin:0 0 16px;color:var(--ftt-txt);font-family:var(--font-title);font-size:17px;font-weight:700}
    .ftt h4::after{content:'';display:block;width:36px;height:3px;margin-top:9px;border-radius:2px;background:var(--ftt-ac)}
    /* El logo va sobre placa blanca: los logotipos oscuros o de un solo color
       se apagaban contra el navy y perdian contraste. */
    .ftt-logo{display:inline-flex;align-items:center;padding:10px 16px;background:#fff;border-radius:12px;
        box-shadow:0 6px 18px rgba(0,0,0,.22)}
    .ftt-logo .ftt-name{color:#0B2038}
    .ftt-logo img{max-height:{{ max(28, min(120, (int) ($settings['footer_logo_height'] ?? 46))) }}px;max-width:210px;width:auto;object-fit:contain}
    .ftt-name{font-family:var(--font-title);font-size:20px;font-weight:700}
    .ftt-desc{margin:16px 0 0;max-width:420px;color:var(--ftt-txt2);font-size:14.5px;line-height:1.6}
    .ftt-social{display:flex;gap:10px;margin-top:18px}
    .ftt-social a{display:grid;place-items:center;width:36px;height:36px;color:var(--ftt-txt2);border:1px solid var(--ftt-cardbd);border-radius:10px;transition:color .18s ease,border-color .18s ease,background .18s ease,transform .18s ease}
    .ftt-social a:hover{transform:translateY(-2px);filter:brightness(1.12)}
    /* Colores oficiales de cada marca, siempre visibles (igual que el encabezado). */
    .ftt-social a[data-net="facebook"]{background:#1877F2;border-color:transparent;color:#fff}
    .ftt-social a[data-net="instagram"]{background:radial-gradient(circle at 30% 107%,#FDF497 0,#FDF497 5%,#FD5949 45%,#D6249F 60%,#285AEB 90%);border-color:transparent;color:#fff}
    .ftt-social a[data-net="tiktok"]{background:#010101;border-color:rgba(255,255,255,.28);color:#fff}
    .ftt-social a[data-net="youtube"]{background:#FF0000;border-color:transparent;color:#fff}
    .ftt-social a[data-net="linkedin"]{background:#0A66C2;border-color:transparent;color:#fff}
    .ftt-social a[data-net="whatsapp"]{background:#25D366;border-color:transparent;color:#fff}
    .ftt-social svg{width:15px;height:15px}
    /* Beneficios 2x2 */
    .ftt-bens{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:22px}
    .ftt-ben{display:flex;align-items:flex-start;gap:10px;padding:11px 12px;background:var(--ftt-card);border:1px solid var(--ftt-cardbd);border-radius:9px}
    .ftt-ben>span:first-child{flex:0 0 auto;color:var(--ftt-ac);margin-top:1px}
    .ftt-ben b{display:block;color:var(--ftt-txt);font-size:12.5px;font-weight:700;line-height:1.3}
    .ftt-ben small{display:block;margin-top:2px;color:var(--ftt-muted);font-size:11px;line-height:1.4}
    /* Listas con separador e icono */
    .ftt ul{list-style:none;margin:0;padding:0}
    .ftt ul li{border-bottom:1px solid rgba(255,255,255,.08)}
    .ftt ul li:last-child{border-bottom:0}
    .ftt ul a{display:flex;align-items:center;gap:9px;padding:10px 0;color:#E5EDF6;font-size:14px;text-decoration:none;transition:color .18s ease}
    .ftt ul a .ftt-ar{margin-left:auto;opacity:0;transform:translateX(-4px);transition:.18s ease;color:var(--ftt-ac)}
    .ftt ul a:hover{color:var(--ftt-ac)}
    .ftt ul a:hover .ftt-ar{opacity:1;transform:translateX(0)}
    .ftt ul a>span:first-child{color:var(--ftt-muted);display:inline-flex}
    .ftt ul a:hover>span:first-child{color:var(--ftt-ac)}
    /* Contacto */
    .ftt-contact{display:flex;flex-direction:column;gap:12px;color:var(--ftt-txt2);font-size:14px}
    .ftt-contact li{display:flex;align-items:flex-start;gap:10px;border:0}
    .ftt-contact li>span:first-child{color:var(--ftt-ac);flex:0 0 auto;margin-top:1px}
    .ftt-contact a{color:var(--ftt-txt2);text-decoration:none;padding:0}
    .ftt-contact a:hover{color:var(--ftt-ac)}
    /* CTA de ayuda */
    .ftt-cta{margin-top:18px;padding:16px;background:var(--ftt-card);border:1px solid var(--ftt-cardbd);border-radius:10px}
    .ftt-cta b{display:block;color:var(--ftt-txt);font-size:14.5px}
    .ftt-cta p{margin:5px 0 12px;color:var(--ftt-muted);font-size:12.5px;line-height:1.5}
    .ftt-cta a{display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:8px;font-size:12.5px;font-weight:800;text-decoration:none;transition:filter .18s ease,transform .18s ease}
    .ftt-cta a:hover{filter:brightness(1.06);transform:translateY(-1px)}
    .ftt-cta-wa{background:#25D366;color:#fff}
    .ftt-cta-plain{background:#fff;color:var(--ftt-bg)}
    /* Barra inferior */
    .ftt-bottom{position:relative;border-top:1px solid var(--ftt-bd)}
    .ftt-bottom-inner{width:var(--header-layout-width,min(1440px,calc(100% - 64px)));max-width:calc(100% - 40px);margin:0 auto;
        display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px 24px;padding:18px 0}
    .ftt-secure{display:flex;align-items:center;gap:10px;color:var(--ftt-txt2);font-size:12.5px}
    .ftt-secure-lock{display:grid;place-items:center;width:34px;height:34px;color:var(--ftt-ac);border:1px solid var(--ftt-cardbd);border-radius:50%}
    .ftt-seals{display:inline-flex;align-items:center;gap:8px;flex:0 0 auto}
    .ftt-secure .ssl-seal{flex:0 0 auto;filter:drop-shadow(0 2px 6px rgba(0,0,0,.35))}
    .ftt-copywrap{flex:1 1 auto;display:grid;gap:3px;justify-items:center;text-align:center}
    .ft-legal{color:var(--ftt-muted);font-size:11.5px;letter-spacing:.01em}
    .ftt-secure b{display:block;color:var(--ftt-txt);font-size:12.5px}
    .ftt-secure small{color:var(--ftt-muted);font-size:11px}
    .ftt-copy{color:var(--ftt-muted);font-size:12.5px;text-align:center}
    .ftt-copy a{color:var(--ftt-ac);font-weight:700;text-decoration:none}
    .ftt-copy a:hover{text-decoration:underline}
    .ftt-pays{display:flex;flex-wrap:wrap;align-items:center;gap:7px}
    /* Placa blanca para que cada logotipo se lea con su color de marca. */
    .ftt-pays .pay-marks{display:contents}
    .ftt-pays span{display:inline-grid;place-items:center;height:28px;min-width:46px;padding:0 9px;
        background:#fff;border:1px solid rgba(255,255,255,.9);border-radius:6px;
        color:#0B2038;font-size:10.5px;font-weight:800;letter-spacing:.04em}
    .ftt-pays span svg{display:block;height:16px;width:auto}
    .ftt-pays span img{display:block;height:18px;width:auto;max-width:64px;object-fit:contain}
    .ftt-pays span img.is-wide{height:18px}
    .ftt-pays span img.is-md{height:19px}
    .ftt-pays span img.is-sq{height:24px}
    /* Tablet: 2 columnas */
    @media(max-width:1100px){.ftt-inner{grid-template-columns:1fr 1fr;gap:34px;padding:44px 0 34px}}
    /* Móvil: acordeones para no generar un pie kilométrico */
    @media(max-width:760px){
        .ftt-inner{grid-template-columns:1fr;gap:6px;padding:34px 0 26px}
        .ftt-col-acc h4{margin:0;padding:14px 0;cursor:pointer;display:flex;align-items:center;justify-content:space-between;min-height:44px}
        .ftt-col-acc h4::after{display:none}
        .ftt .ftt-chev{display:inline-flex;transition:transform .2s ease;color:var(--ftt-ac)}
        .ftt-col-acc[open] h4 .ftt-chev{transform:rotate(180deg)}
        .ftt-col-acc{border-bottom:1px solid var(--ftt-bd)}
        .ftt-col-acc>summary{list-style:none}
        .ftt-col-acc>summary::-webkit-details-marker{display:none}
        .ftt-bens{grid-template-columns:1fr 1fr}
        .ftt-bottom-inner{flex-direction:column;align-items:flex-start;gap:14px;padding:18px 0 22px}.ftt-copywrap{justify-items:flex-start;text-align:left}
        .ftt-copy{text-align:left}
    }
</style>
<footer class="ftt site-footer-technology">
    <div class="ftt-inner">
        {{-- EMPRESA --}}
        <div>
            <div class="ftt-logo">
                @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $storeName }}" loading="lazy">@else<span class="ftt-name">{{ $storeName }}</span>@endif
            </div>
            @if($tagline)<p class="ftt-desc">{{ $tagline }}</p>@endif
            @if($ftShowSocial)
            <div class="ftt-social">
                @foreach($social as $net => $url)
                <a href="{{ $url }}" data-net="{{ strtolower($net) }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($net) }}">{!! $ftSocial(strtolower($net)) !!}</a>
                @endforeach
            </div>
            @endif
            @if($ftShowBenefits && $ftBenefits->isNotEmpty())
            <div class="ftt-bens">
                @foreach($ftBenefits as $b)
                <div class="ftt-ben">
                    <span>{!! $ftSvg($b['icon']) !!}</span>
                    <span><b>{{ $b['title'] }}</b>@if($b['desc'])<small>{{ $b['desc'] }}</small>@endif</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- CATEGORÍAS --}}
        @if($ftShowCats)
        <details class="ftt-col-acc" open>
            <summary><h4>Categorías <span class="ftt-chev"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg></span></h4></summary>
            <ul>
                @foreach($ftCats as $cat)
                <li><a href="{{ $ftCatUrl($cat) }}"><span>{!! $ftCatIcon($cat->name) !!}</span>{{ $cat->name }}<span class="ftt-ar"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg></span></a></li>
                @endforeach
            </ul>
        </details>
        @endif

        {{-- INFORMACIÓN --}}
        <details class="ftt-col-acc" open>
            <summary><h4>Información <span class="ftt-chev"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg></span></h4></summary>
            <ul>
                <li><a href="{{ $aboutUrl }}">Nosotros<span class="ftt-ar"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg></span></a></li>
                <li><a href="{{ $contactUrl }}">Contacto<span class="ftt-ar"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg></span></a></li>
                <li><a href="{{ url($legalBase.'/reclamaciones') }}">Libro de Reclamaciones<span class="ftt-ar"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg></span></a></li>
                <li><a href="{{ url($legalBase.'/privacidad') }}">Políticas de privacidad<span class="ftt-ar"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg></span></a></li>
                <li><a href="{{ url($legalBase.'/terminos') }}">Términos y condiciones<span class="ftt-ar"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg></span></a></li>
            </ul>
        </details>

        {{-- CONTACTO --}}
        <details class="ftt-col-acc" open>
            <summary><h4>Contacto <span class="ftt-chev"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg></span></h4></summary>
            <ul class="ftt-contact">
                @if($phone)<li><span><svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/></svg></span><a href="tel:{{ preg_replace('/[^\d+]/','',$phone) }}">{{ $phone }}</a></li>@endif
                @if($waFooter)<li><span><svg viewBox="0 0 24 24" width="17" height="17" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Z"/></svg></span><a href="https://wa.me/{{ $waFooter }}" target="_blank" rel="noopener">Escríbenos por WhatsApp</a></li>@endif
                @if($email)<li><span><svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/></svg></span><a href="mailto:{{ $email }}">{{ $email }}</a></li>@endif
                @if($footerAddress)<li><span><svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg></span><span>{{ $footerAddress }}</span></li>@endif
                @if($footerHours)<li><span><svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span><span style="white-space:pre-line">{{ $footerHours }}</span></li>@endif
            </ul>
            @if($ftShowCta)
            @php
                $ftCtaAction = in_array($settings['footer_cta_action'] ?? 'whatsapp', ['whatsapp','contact','url'], true) ? ($settings['footer_cta_action'] ?? 'whatsapp') : 'whatsapp';
                $ftCtaUrl = $ftCtaAction === 'whatsapp' && $waFooter
                    ? 'https://wa.me/'.$waFooter
                    : ($ftCtaAction === 'url' ? trim((string) ($settings['footer_cta_url'] ?? '')) : $contactUrl);
            @endphp
            @if($ftCtaUrl)
            <div class="ftt-cta">
                <b>{{ $settings['footer_cta_title'] ?? '¿Necesitas ayuda?' }}</b>
                <p>{{ $settings['footer_cta_text'] ?? 'Nuestro equipo está listo para asesorarte.' }}</p>
                <a class="{{ $ftCtaAction === 'whatsapp' ? 'ftt-cta-wa' : 'ftt-cta-plain' }}" href="{{ $ftCtaUrl }}" @if($ftCtaAction === 'whatsapp') target="_blank" rel="noopener" @endif>
                    @if($ftCtaAction === 'whatsapp')<svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Z"/></svg>@endif
                    {{ $settings['footer_cta_btn'] ?? 'Contáctanos' }}
                </a>
            </div>
            @endif
            @endif
        </details>
    </div>

    <div class="ftt-bottom">
        <div class="ftt-bottom-inner">
            @if($ftShowSecure)
            <div class="ftt-secure">
                @if($ftSecureStyle === 'lock')
                <span class="ftt-secure-lock"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
                @else
                <span class="ftt-seals">
                    @if($ftSecureStyle !== 'https')@include('storefront.partials.badges.ssl-seal', ['sealStyle' => 'gold', 'sealPx' => 50])@endif
                    @if($ftSecureStyle !== 'gold')@include('storefront.partials.badges.ssl-seal', ['sealStyle' => 'https', 'sealPx' => 50])@endif
                </span>
                @endif
                <span><b>{{ $ftSecureTitle }}</b>@if($ftSecureText)<small>{{ $ftSecureText }}</small>@endif</span>
            </div>
            @endif
            <div class="ftt-copywrap">
                @if($ftShowLegal)
                <span class="ft-legal">{{ $ftLegalName }}@if($ftRuc) · RUC {{ $ftRuc }}@endif</span>
                @endif
                <span class="ftt-copy">{{ $footerCopyright ?: '© '.date('Y').' '.$storeName.'. Todos los derechos reservados.' }} · Desarrollado por <a href="https://eskalagroup.com/" target="_blank" rel="noopener">Eskala</a></span>
            </div>
            @if($ftShowPay && !empty($ftPay))
            <div class="ftt-pays">@include('storefront.partials.badges.pay-logos', ['pay' => $ftPay])</div>
            @endif
        </div>
    </div>
</footer>
<script>
/* Las columnas del pie se pliegan solo en movil (ahorra ~45% de scroll).
   Nacen abiertas en el HTML: si el JS no corre, el escritorio se ve completo. */
(function () {
    var mq = window.matchMedia('(max-width:760px)');
    var apply = function () {
        document.querySelectorAll('.ftt details.ftt-col-acc').forEach(function (d) { d.open = !mq.matches; });
    };
    apply();
    mq.addEventListener ? mq.addEventListener('change', apply) : mq.addListener(apply);
})();
</script>
