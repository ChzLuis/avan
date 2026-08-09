<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@php
$primaryColor  = $settings['primary_color']  ?? '#3340ff';
$currency      = $settings['currency_symbol'] ?? 'S/';
$seoTitle      = ($settings['seo_title'] ?? null) ?: $project->name;
$seoDesc       = ($settings['seo_description'] ?? null) ?: ($project->description ?? 'Explora nuestros productos y haz tu pedido en línea.');
$seoKeywords   = $settings['seo_keywords'] ?? '';
$canonicalUrl  = ($settings['seo_canonical'] ?? null)
    ?: ($project->custom_domain ? 'https://'.$project->custom_domain : url('/'.$project->slug));
$logoUrl       = $settings['logo_url'] ?? $project->logo_url ?? '';
$ogImage       = $logoUrl ? asset('storage/'.$logoUrl) : asset('img/og-default.png');
$seoRobots     = ($settings['seo_robots'] ?? 'index, follow');
$faviconUrl    = !empty($settings['favicon_url']) ? asset('storage/'.$settings['favicon_url']) : '';
$announcementText = $settings['announcement_text'] ?? '';
$footerTagline    = $settings['footer_tagline']  ?? '';
$footerCopyright  = $settings['footer_copyright'] ?? ('© ' . date('Y') . ' ' . $project->name);
$heroTitle     = $settings['hero_title']    ?? $project->name;
$heroSub       = $settings['hero_subtitle'] ?? '';
// Tipografías del panel de Diseño (si no se configuran, usa el par por defecto de la plantilla)
$ecFontTitle   = trim($settings['font_title'] ?? $settings['font'] ?? '') ?: 'Space Grotesk';
$ecFontBody    = trim($settings['font_body']  ?? $settings['font'] ?? '') ?: 'DM Sans';
$ecGoogleFonts = collect([$ecFontTitle, $ecFontBody])->unique()->filter()
                   ->map(fn($f)=>str_replace(' ','+',$f).':wght@300;400;500;600;700')->implode('&family=');
$isQuoteOnly   = ($settings['store_mode'] ?? 'direct') === 'quote_only';
$culqiEnabled  = ($settings['culqi_enabled'] ?? '0') === '1';
$culqiPublicKey= $settings['culqi_public_key'] ?? '';
$mpEnabled     = ($settings['mp_enabled'] ?? '0') === '1';
$payManualEnabled = ($settings['payment_manual_enabled'] ?? '0') === '1';
$payManualMethods = json_decode($settings['payment_manual_methods'] ?? '["yape"]', true) ?? [];
$payYapeNumber  = preg_replace('/\D/', '', $settings['payment_yape_number'] ?? '');
$payYapeName    = $settings['payment_yape_name'] ?? ($project->name ?? '');
$payYapeQr      = $settings['payment_yape_qr'] ?? '';
$payBankDetails = $settings['payment_bank_details'] ?? '';
$payBanks       = [
    'bcp'        => ['label'=>'BCP',               'color'=>'#003DA5', 'logo'=>'https://upload.wikimedia.org/wikipedia/commons/thumb/8/84/BCP_logo.svg/200px-BCP_logo.svg.png'],
    'interbank'  => ['label'=>'Interbank',          'color'=>'#00873D', 'logo'=>'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6b/Interbank_Logo_2021.svg/200px-Interbank_Logo_2021.svg.png'],
    'bbva'       => ['label'=>'BBVA',               'color'=>'#004481', 'logo'=>'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6a/BBVA_2019.svg/200px-BBVA_2019.svg.png'],
    'nacion'     => ['label'=>'Banco de la Nación', 'color'=>'#D22630', 'logo'=>'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e1/Banco-naci%C3%B3n-logo.svg/200px-Banco-naci%C3%B3n-logo.svg.png'],
    'scotiabank' => ['label'=>'Scotiabank',         'color'=>'#EC111A', 'logo'=>'https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/Scotiabank_logo_2021.svg/200px-Scotiabank_logo_2021.svg.png'],
];
$payBankActive = [];
foreach($payBanks as $bk => $bv) {
    $details = trim($settings['payment_bank_'.$bk] ?? '');
    if ($details) $payBankActive[$bk] = array_merge($bv, ['details' => $details]);
}
$shippingEnabled  = ($settings['shipping_enabled'] ?? '0') === '1';
$shippingCost     = (float)($settings['shipping_cost'] ?? 0);
$shippingFreeFrom = (float)($settings['shipping_free_from'] ?? 0);
$requireAddress   = ($settings['require_address'] ?? '0') === '1';
$quoteWaRaw    = preg_replace('/\D/', '', $settings['quote_whatsapp'] ?? '');
if (!$quoteWaRaw) $quoteWaRaw = preg_replace('/\D/', '', $project->whatsapp ?? '');
$quoteWaCountry = $settings['quote_whatsapp_country'] ?? '51';
$quoteWa = $quoteWaRaw ? (str_starts_with($quoteWaRaw, $quoteWaCountry) ? $quoteWaRaw : $quoteWaCountry.$quoteWaRaw) : '';
$ckFields = json_decode($settings['checkout_fields'] ?? 'null', true) ?? [
    'fixed'  => [
        'lname'   => ['label'=>'Apellido',  'enabled'=>true],
        'email'   => ['label'=>'Email',     'enabled'=>true],
        'dni'     => ['label'=>'DNI / RUC', 'enabled'=>true],
        'address' => ['label'=>'Dirección', 'enabled'=>false],
        'notes'   => ['label'=>'Notas',     'enabled'=>true],
    ],
    'custom' => [],
];
$trustIcon1 = $settings['trust_icon_1'] ?? '🚚'; $trustText1 = $settings['trust_text_1'] ?? 'Envío rápido';
$trustIcon2 = $settings['trust_icon_2'] ?? '🔒'; $trustText2 = $settings['trust_text_2'] ?? 'Pago seguro';
$trustIcon3 = $settings['trust_icon_3'] ?? '✅'; $trustText3 = $settings['trust_text_3'] ?? 'Garantía';
$trustIcon4 = $settings['trust_icon_4'] ?? '💬'; $trustText4 = $settings['trust_text_4'] ?? 'Soporte 24/7';

// Flat product search index
$searchIndex = [];
foreach($categories as $_cat) {
    foreach($_cat->products as $_p) {
        $searchIndex[] = ['id'=>$_p->id,'name'=>$_p->name,'price'=>(float)$_p->price,'cp'=>$_p->compare_price?(float)$_p->compare_price:null,'img'=>$_p->mainImage?$_p->main_image_url:null,'cat'=>$_cat->name,'catId'=>(string)$_cat->id,'parentId'=>null,'stock'=>$_p->stock];
    }
    foreach($_cat->children as $_sub) {
        foreach($_sub->products as $_p) {
            $searchIndex[] = ['id'=>$_p->id,'name'=>$_p->name,'price'=>(float)$_p->price,'cp'=>$_p->compare_price?(float)$_p->compare_price:null,'img'=>$_p->mainImage?$_p->main_image_url:null,'cat'=>$_sub->name,'catId'=>(string)$_sub->id,'parentId'=>(string)$_cat->id,'stock'=>$_p->stock];
        }
    }
}
$paymentMeta = ['yape'=>['label'=>'Yape','emoji'=>'🟣'],'plin'=>['label'=>'Plin','emoji'=>'🔵'],'transferencia'=>['label'=>'Transferencia','emoji'=>'🏦'],'efectivo'=>['label'=>'Efectivo','emoji'=>'💵'],'tarjeta'=>['label'=>'Tarjeta','emoji'=>'💳'],'contra_entrega'=>['label'=>'Contra entrega','emoji'=>'🚚']];
@endphp
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDesc }}">
@if($seoKeywords)<meta name="keywords" content="{{ $seoKeywords }}">@endif
<meta name="robots" content="{{ $seoRobots }}">
<meta name="google-site-verification" content="df91b11ed079341d">
<link rel="canonical" href="{{ $canonicalUrl }}">
<link rel="sitemap" type="application/xml" href="{{ $canonicalUrl }}/sitemap.xml">
@if($faviconUrl)<link rel="icon" href="{{ $faviconUrl }}">@endif

{{-- Open Graph --}}
<meta property="og:type"        content="website">
<meta property="og:url"         content="{{ $canonicalUrl }}">
<meta property="og:title"       content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDesc }}">
<meta property="og:image"       content="{{ $ogImage }}">
<meta property="og:image:width"  content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale"      content="es_PE">
<meta property="og:site_name"   content="{{ $project->name }}">

{{-- Twitter Card --}}
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDesc }}">
<meta name="twitter:image"       content="{{ $ogImage }}">

{{-- Schema.org LocalBusiness --}}
@php
$schema = [
    '@context'    => 'https://schema.org',
    '@type'       => ['Store', 'WebSite'],
    'name'        => $project->name,
    'alternateName' => $project->name,
    'description' => $seoDesc,
    'url'         => $canonicalUrl,
    'image'       => $ogImage,
];
if (!empty($project->phone))    $schema['telephone'] = $project->phone;
if (!empty($project->address))  $schema['address'] = ['@type'=>'PostalAddress','streetAddress'=>$project->address,'addressCountry'=>'PE'];
if (!empty($project->whatsapp)) $schema['contactPoint'] = ['@type'=>'ContactPoint','telephone'=>$project->whatsapp,'contactType'=>'customer service'];
@endphp
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family={{ $ecGoogleFonts }}&display=swap" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family={{ $ecGoogleFonts }}&display=swap"></noscript>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
:root {
  --primary: {{ $primaryColor }};
  --primary-ink: #ffffff;
  --primary-hover: color-mix(in srgb, {{ $primaryColor }} 80%, #000);
  --accent: #ff5b3d;
  --success: #16a34a;
  --warn: #f59e0b;
  --danger: #ef4444;
  --bg-body: #f7f7f5;
  --bg-surface: #ffffff;
  --bg-elev: #fafaf8;
  --bg-inset: #f1f1ee;
  --bg-overlay: rgba(15,15,17,.55);
  --text-primary: #0e0e10;
  --text-secondary: #5a5a63;
  --text-muted: #8a8a92;
  --text-on-dark: #f5f5f7;
  --border: rgba(14,14,16,.08);
  --border-strong: rgba(14,14,16,.16);
  --shadow-sm: 0 1px 2px rgba(14,14,16,.04);
  --shadow-md: 0 4px 12px rgba(14,14,16,.06), 0 2px 4px rgba(14,14,16,.04);
  --shadow-lg: 0 20px 40px rgba(14,14,16,.10), 0 6px 14px rgba(14,14,16,.06);
  --font-display: '{{ $ecFontTitle }}', system-ui, -apple-system, sans-serif;
  --font-body: '{{ $ecFontBody }}', system-ui, -apple-system, sans-serif;
  --topbar-h: 36px;
  --header-h: {{ $settings['header_height'] ?? 64 }}px;
  --logo-h: {{ $settings['logo_height'] ?? 40 }}px;
  --nav-h: 48px;
  --page-pad: 32px;
  --page-max: 1440px;
  --grid-gap: 24px;
  --cols: 4;
  --topbar-bg: #0e0e10;
  --topbar-text: #f5f5f7;
  --ease: cubic-bezier(.22,.61,.36,1);
  --t-fast: 140ms;
  --t-base: 220ms;
  --t-slow: 360ms;
  --radius-xs:4px; --radius-sm:8px; --radius-md:12px; --radius-lg:16px; --radius-xl:24px; --radius-full:9999px;
}
/* DARK MODE */
[data-theme="dark"] {
  --bg-body: #0e0e10;
  --bg-surface: #18181b;
  --bg-elev: #1c1c1f;
  --bg-inset: #27272a;
  --bg-overlay: rgba(0,0,0,.7);
  --text-primary: #f4f4f5;
  --text-secondary: #a1a1aa;
  --text-muted: #71717a;
  --text-on-dark: #f5f5f7;
  --border: rgba(255,255,255,.08);
  --border-strong: rgba(255,255,255,.15);
  --shadow-sm: 0 1px 2px rgba(0,0,0,.3);
  --shadow-md: 0 4px 12px rgba(0,0,0,.4), 0 2px 4px rgba(0,0,0,.3);
  --shadow-lg: 0 20px 40px rgba(0,0,0,.5), 0 6px 14px rgba(0,0,0,.4);
  --topbar-bg: #000000;
}
*,*::before,*::after{box-sizing:border-box;}
[x-cloak]{display:none!important;}
html,body{margin:0;padding:0;overflow-x:hidden;}
body{transition:margin-left .3s cubic-bezier(.4,0,.2,1);}
body.tw-open{margin-left:320px;}
body{font-family:var(--font-body);font-size:15px;line-height:1.5;color:var(--text-primary);background:var(--bg-body);-webkit-font-smoothing:antialiased;}
button{font:inherit;color:inherit;}
img,svg{display:block;max-width:100%;}
a{color:inherit;text-decoration:none;}
input,select,textarea{font:inherit;color:inherit;}

/* TOPBAR */
.topbar{height:var(--topbar-h);background:var(--topbar-bg);color:var(--topbar-text);display:flex;align-items:center;font-size:12px;position:relative;z-index:30;}
.topbar-inner{width:100%;max-width:var(--page-max);margin:0 auto;padding:0 var(--page-pad);display:flex;align-items:center;justify-content:space-between;gap:16px;}
.topbar-promo{flex:1;min-width:0;}
.topbar-links{display:flex;gap:18px;opacity:.85;}
.topbar-links a{font-size:11.5px;cursor:pointer;}
.topbar-links a:hover{opacity:1;}

/* HEADER */
.header{background:rgba(255,255,255,.96);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:40;transition:transform .3s ease,box-shadow var(--t-fast);}
.header--hidden{transform:translateY(-100%);}
.header--hidden~*>.nav,.header--hidden+.nav{top:0;}
.header-inner{height:var(--header-h);width:100%;max-width:var(--page-max);margin:0 auto;padding:0 var(--page-pad);display:flex;align-items:center;gap:20px;}
.logo{font-family:var(--font-display);font-weight:700;font-size:22px;letter-spacing:-.01em;display:inline-flex;align-items:center;gap:8px;cursor:pointer;flex-shrink:0;transition:opacity var(--t-fast);}
.logo:hover{opacity:.8;}
.logo-mark{width:32px;height:32px;border-radius:10px;background:var(--primary);display:grid;place-items:center;color:var(--primary-ink);font-family:var(--font-display);font-weight:700;font-size:15px;flex-shrink:0;}
.logo img{height:var(--logo-h,42px);max-height:var(--logo-max,var(--logo-h,42px));width:auto;object-fit:contain;display:block;}
.search{flex:1;position:relative;max-width:580px;margin:0 auto;}
.search-input{width:100%;height:42px;background:var(--bg-inset);border:1.5px solid var(--border-strong);border-radius:var(--radius-full);padding:0 48px 0 18px;font-size:14px;transition:all var(--t-fast);}
.search-input:focus{outline:0;border-color:var(--primary);background:var(--bg-surface);box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 14%,transparent);}
.search-input::placeholder{color:var(--text-muted);}
.search-icon{position:absolute;right:14px;top:50%;transform:translateY(-50%);width:18px;height:18px;color:var(--text-secondary);pointer-events:none;}
.search-dropdown{position:absolute;left:0;right:0;top:calc(100% + 8px);background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);padding:8px;max-height:420px;overflow-y:auto;z-index:50;}
.sd-section{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);padding:8px 12px 4px;}
.sd-item{display:flex;align-items:center;gap:12px;padding:8px 12px;border-radius:var(--radius-md);cursor:pointer;transition:background var(--t-fast);}
.sd-item:hover{background:var(--bg-inset);}
.sd-thumb{width:40px;height:40px;border-radius:var(--radius-md);flex-shrink:0;overflow:hidden;background:var(--bg-inset);}
.sd-thumb img{width:100%;height:100%;object-fit:cover;}
.sd-name{font-size:13.5px;font-weight:500;}
.sd-meta{font-size:11.5px;color:var(--text-muted);}
.sd-price{margin-left:auto;font-weight:700;font-size:13px;color:var(--primary);}
.header-icons{display:flex;align-items:center;gap:4px;flex-shrink:0;}
.icon-btn{position:relative;width:40px;height:40px;display:grid;place-items:center;border-radius:var(--radius-full);background:transparent;border:0;cursor:pointer;color:var(--text-primary);transition:background var(--t-fast);}
.icon-btn:hover{background:var(--bg-inset);}
.icon-btn svg{width:20px;height:20px;}
.icon-badge{position:absolute;top:2px;right:2px;min-width:18px;height:18px;padding:0 4px;background:var(--accent);color:white;font-size:10px;font-weight:700;border-radius:999px;display:grid;place-items:center;border:2px solid white;}
.hamburger{display:none;}

/* NAV */
.nav{background:var(--bg-surface);border-bottom:1px solid var(--border);height:var(--nav-h);position:sticky;top:var(--header-h);z-index:35;}
.nav-inner{height:100%;width:100%;max-width:var(--page-max);margin:0 auto;padding:0 var(--page-pad);display:flex;align-items:center;gap:2px;overflow-x:auto;scrollbar-width:none;}
.nav-inner::-webkit-scrollbar{display:none;}
.nav-item{height:100%;padding:0 14px;display:inline-flex;align-items:center;gap:5px;font-size:13.5px;font-weight:500;cursor:pointer;border-bottom:2px solid transparent;transition:color var(--t-fast),border-color var(--t-fast);white-space:nowrap;position:relative;color:var(--text-secondary);}
.nav-item:hover{color:var(--text-primary);}
.nav-item.active{border-bottom-color:var(--primary);color:var(--primary);font-weight:600;}
.nav-flyout{position:absolute;top:calc(100% + 1px);left:0;background:var(--bg-surface);border:1px solid var(--border);border-radius:0 var(--radius-lg) var(--radius-lg) var(--radius-lg);box-shadow:var(--shadow-lg);padding:16px;display:grid;grid-template-columns:repeat(3,minmax(160px,1fr));gap:2px 20px;min-width:520px;z-index:50;}
.nav-flyout a{font-size:13px;padding:7px 10px;border-radius:var(--radius-sm);display:block;color:var(--text-secondary);cursor:pointer;transition:all var(--t-fast);}
.nav-flyout a:hover{background:var(--bg-inset);color:var(--primary);padding-left:14px;}
/* "Todas las categorías" dropdown del nav */
.nav-all-btn{display:inline-flex;align-items:center;gap:5px;height:100%;padding:0 14px;font-size:13.5px;font-weight:600;cursor:pointer;border-bottom:2px solid transparent;white-space:nowrap;position:relative;color:var(--primary);background:none;border-top:0;border-left:0;border-right:0;transition:border-color var(--t-fast);}
.nav-all-btn:hover,.nav-all-btn.active{border-bottom-color:var(--primary);}
.nav-all-btn svg{width:14px;height:14px;transition:transform var(--t-fast);}
.nav-all-btn.open svg{transform:rotate(180deg);}
.nav-all-flyout{position:absolute;top:calc(100% + 1px);right:0;width:720px;background:var(--bg-surface);border:1px solid var(--border);border-radius:0 0 var(--radius-lg) var(--radius-lg);box-shadow:var(--shadow-lg);padding:20px;z-index:50;display:grid;grid-template-columns:repeat(4,1fr);gap:4px 16px;}
.nav-all-flyout .naf-item{padding:8px 10px;border-radius:var(--radius-sm);cursor:pointer;transition:background var(--t-fast);}
.nav-all-flyout .naf-item:hover{background:var(--bg-inset);}
.nav-all-flyout .naf-name{font-size:13px;font-weight:600;color:var(--text-primary);}
.nav-all-flyout .naf-count{font-size:11px;color:var(--text-muted);margin-top:1px;}
.nav-all-flyout .naf-divider{grid-column:1/-1;border-top:1px solid var(--border);margin:8px 0;padding:0;}
.nav-all-flyout .naf-footer{grid-column:1/-1;padding-top:4px;display:flex;justify-content:center;}

/* CATEGORIES PAGE */
.cat-page{padding-top:24px;}
.cat-page-search{position:relative;max-width:480px;margin:0 auto 32px;}
.cat-page-search input{width:100%;height:48px;border:1.5px solid var(--border-strong);border-radius:var(--radius-full);padding:0 48px 0 20px;font-size:15px;background:var(--bg-surface);}
.cat-page-search input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 4px color-mix(in srgb,var(--primary) 14%,transparent);}
.cat-page-search svg{position:absolute;right:16px;top:50%;transform:translateY(-50%);color:var(--text-muted);}
.cat-page-tools{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;}
.cat-page-count{font-size:13px;color:var(--text-muted);}
.cat-sort{display:flex;gap:6px;}
.cat-sort-btn{height:32px;padding:0 14px;border-radius:999px;border:1px solid var(--border-strong);background:var(--bg-surface);font-size:12.5px;font-weight:500;cursor:pointer;color:var(--text-secondary);transition:all var(--t-fast);}
.cat-sort-btn.active{background:var(--primary);color:var(--primary-ink);border-color:var(--primary);}
/* Alphabet bar (shown when >20 cats) */
.alpha-bar{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:20px;}
.alpha-btn{width:32px;height:32px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--bg-surface);font-size:12.5px;font-weight:600;cursor:pointer;display:grid;place-items:center;color:var(--text-secondary);transition:all var(--t-fast);}
.alpha-btn:hover,.alpha-btn.active{background:var(--primary);color:var(--primary-ink);border-color:var(--primary);}
.alpha-btn.disabled{opacity:.3;cursor:default;pointer-events:none;}
/* alphabet group heading */
.alpha-group-head{font-family:var(--font-display);font-size:18px;font-weight:700;color:var(--text-muted);padding:12px 0 8px;border-bottom:1px solid var(--border);margin:16px 0 12px;grid-column:1/-1;}
/* Cat card grid – flexible density */
.cat-page-grid{display:grid;gap:12px;}
.cat-page-grid.density-2{grid-template-columns:repeat(2,1fr);}
.cat-page-grid.density-3{grid-template-columns:repeat(3,1fr);}
.cat-page-grid.density-4{grid-template-columns:repeat(4,1fr);}
.cat-page-grid.density-5{grid-template-columns:repeat(5,1fr);}
/* Cat card */
.cat-card{background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;cursor:pointer;transition:all var(--t-base) var(--ease);display:flex;flex-direction:column;gap:12px;position:relative;overflow:hidden;}
.cat-card::before{content:'';position:absolute;inset:0;background:var(--primary);opacity:0;transition:opacity var(--t-fast);}
.cat-card:hover{border-color:var(--primary);transform:translateY(-2px);box-shadow:0 8px 24px color-mix(in srgb,var(--primary) 18%,transparent);}
.cat-card:hover::before{opacity:.04;}
.cat-card-icon{width:48px;height:48px;border-radius:var(--radius-md);background:color-mix(in srgb,var(--primary) 10%,transparent);display:grid;place-items:center;font-size:24px;flex-shrink:0;transition:transform var(--t-fast);}
.cat-card:hover .cat-card-icon{transform:scale(1.1);}
.cat-card-info{min-width:0;}
.cat-card-name{font-family:var(--font-display);font-size:14.5px;font-weight:700;line-height:1.2;color:var(--text-primary);transition:color var(--t-fast);}
.cat-card:hover .cat-card-name{color:var(--primary);}
.cat-card-meta{font-size:12px;color:var(--text-muted);margin-top:3px;}
.cat-card-subs{margin-top:8px;display:flex;flex-wrap:wrap;gap:5px;}
.cat-card-sub{font-size:11px;padding:3px 8px;border-radius:999px;background:var(--bg-inset);color:var(--text-secondary);border:1px solid var(--border);}
/* Accordion for large-cat mode */
.cat-alpha-section{margin-bottom:8px;}
.cat-list-row{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-radius:var(--radius-md);cursor:pointer;transition:background var(--t-fast);}
.cat-list-row:hover{background:var(--bg-inset);}
.cat-list-name{font-size:14px;font-weight:600;}
.cat-list-meta{font-size:12px;color:var(--text-muted);margin-top:1px;}
.cat-list-count{font-size:12.5px;color:var(--text-secondary);background:var(--bg-inset);padding:2px 10px;border-radius:999px;}
/* empty state */
.cat-empty{text-align:center;padding:56px 16px;color:var(--text-muted);}
.cat-empty-icon{font-size:48px;margin-bottom:12px;}
.cat-empty-title{font-size:17px;font-weight:600;color:var(--text-primary);margin-bottom:6px;}

/* PAGE */
.page{width:100%;max-width:var(--page-max);margin:0 auto;padding:0 var(--page-pad);}
.page-pad{padding-top:32px;padding-bottom:64px;}
.breadcrumbs{font-size:12.5px;color:var(--text-muted);display:flex;align-items:center;gap:6px;margin:16px 0 8px;}

/* TYPOGRAPHY */
.h1{font-family:var(--font-display);font-size:40px;font-weight:700;letter-spacing:-.02em;line-height:1.1;margin:0;}
.h2{font-family:var(--font-display);font-size:28px;font-weight:700;letter-spacing:-.015em;line-height:1.15;margin:0;}
.h3{font-family:var(--font-display);font-size:20px;font-weight:600;letter-spacing:-.01em;margin:0;}
.eyebrow{font-size:11.5px;letter-spacing:.08em;text-transform:uppercase;color:var(--text-muted);}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:44px;padding:0 20px;font-size:14px;font-weight:600;font-family:var(--font-body);border-radius:var(--radius-md);border:1px solid transparent;cursor:pointer;transition:all var(--t-fast);background:transparent;color:var(--text-primary);white-space:nowrap;}
.btn-primary{background:var(--primary);color:var(--primary-ink);}
.btn-primary:hover{background:var(--primary-hover);transform:translateY(-1px);box-shadow:0 8px 20px color-mix(in srgb,var(--primary) 28%,transparent);}
.btn-ghost{background:var(--bg-inset);}
.btn-ghost:hover{background:var(--bg-elev);}
.btn-outline{border-color:var(--border-strong);}
.btn-outline:hover{background:var(--bg-inset);}
.btn-dark{background:var(--text-primary);color:var(--bg-surface);}
.btn-sm{height:36px;padding:0 14px;font-size:13px;}
.btn-lg{height:52px;padding:0 28px;font-size:15px;}
.btn-block{width:100%;}
.btn[disabled]{opacity:.45;cursor:not-allowed;}

/* BADGES */
.badge{display:inline-flex;align-items:center;font-size:10.5px;font-weight:700;letter-spacing:.04em;padding:4px 8px;border-radius:6px;text-transform:uppercase;}
.badge-sale{background:var(--accent);color:white;}
.badge-new{background:var(--text-primary);color:var(--bg-surface);}
.badge-hot{background:var(--primary);color:var(--primary-ink);}

/* PRODUCT CARD */
.grid-products{display:grid;gap:var(--grid-gap);grid-template-columns:repeat(var(--cols,4),minmax(0,1fr));}
.card{background:var(--bg-surface);border-radius:var(--radius-lg);overflow:hidden;display:flex;flex-direction:column;border:1px solid var(--border);transition:all var(--t-base) var(--ease);position:relative;cursor:pointer;group:true;}
.card:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(14,14,16,.10),0 4px 8px rgba(14,14,16,.06);border-color:transparent;}
.card-media{position:relative;aspect-ratio:1/1;overflow:hidden;background:var(--bg-inset);}
.card-media img{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;object-position:center;transition:transform 400ms var(--ease);padding:6px;background:var(--bg-inset);}
.card:hover .card-media img{transform:scale(1.07);}
.card-media-placeholder{width:100%;height:100%;display:grid;place-items:center;background:linear-gradient(145deg,var(--bg-inset) 0%,var(--bg-elev) 100%);color:var(--text-muted);font-size:44px;}
.card-badges{position:absolute;top:10px;left:10px;display:flex;flex-direction:column;gap:4px;align-items:flex-start;z-index:2;}
.card-wishlist{position:absolute;top:10px;right:10px;width:34px;height:34px;border-radius:999px;background:rgba(255,255,255,.95);backdrop-filter:blur(4px);display:grid;place-items:center;border:0;cursor:pointer;transition:all var(--t-fast);z-index:2;box-shadow:0 2px 8px rgba(0,0,0,.1);}
.card-wishlist:hover{transform:scale(1.12);}
.card-wishlist svg{width:15px;height:15px;color:#0e0e10;}
/* Vista rápida — flota sobre imagen al hover */
.card-quick-view{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity var(--t-fast);z-index:2;background:rgba(0,0,0,.18);pointer-events:none;}
.card:hover .card-quick-view{opacity:1;pointer-events:auto;}
.card-quick-view .btn{height:36px;padding:0 18px;font-size:13px;font-weight:600;background:white;color:var(--text-primary);border-radius:var(--radius-full);box-shadow:0 2px 12px rgba(0,0,0,.18);border:0;}
.card-quick-view .btn:hover{background:var(--primary);color:var(--primary-ink);}
.card-add{padding:0 10px 10px;}
.card-add .btn{width:100%;height:38px;font-size:13px;font-weight:600;background:var(--primary);color:var(--primary-ink);border-radius:var(--radius-md);border:0;transition:opacity var(--t-fast);}
.card-add .btn:hover{opacity:.85;}
/* Quick View modal */
#qv-modal{position:fixed;inset:0;z-index:9998;display:none;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.55);}
#qv-modal.open{display:flex;}
#qv-box{background:var(--bg-surface);border-radius:16px;box-shadow:0 24px 64px rgba(0,0,0,.22);width:100%;max-width:400px;overflow:hidden;animation:qv-in .18s ease;}
@keyframes qv-in{from{opacity:0;transform:scale(.94) translateY(8px)}to{opacity:1;transform:none}}
#qv-img{width:120px;height:120px;object-fit:cover;border-radius:10px;flex-shrink:0;background:var(--bg-inset);}
#qv-img-placeholder{width:120px;height:120px;border-radius:10px;background:var(--bg-inset);display:grid;place-items:center;font-size:48px;flex-shrink:0;}
.card-body{padding:12px 14px 14px;display:flex;flex-direction:column;gap:4px;flex:1;}
.card-cat{font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);font-weight:600;}
.card-name{font-size:14px;font-weight:600;color:var(--text-primary);line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.7em;}
.card-price-row{display:flex;align-items:center;gap:8px;margin-top:6px;flex-wrap:wrap;}
.price-now{font-family:var(--font-display);font-size:17px;font-weight:700;letter-spacing:-.01em;}
.price-was{font-size:12.5px;color:var(--text-muted);text-decoration:line-through;}
.price-pct{font-size:11px;font-weight:700;color:white;background:var(--accent);padding:2px 6px;border-radius:4px;}
.card-cta{margin-top:8px;display:flex;gap:6px;}
.card-cta .btn{flex:1;height:38px;font-size:13px;}

/* SKELETON */
.skeleton{background:linear-gradient(90deg,var(--bg-inset) 0%,var(--bg-elev) 50%,var(--bg-inset) 100%);background-size:200% 100%;animation:shimmer 1.4s linear infinite;border-radius:var(--radius-sm);}
@keyframes shimmer{from{background-position:200% 0;}to{background-position:-200% 0;}}
.sk-card{background:var(--bg-surface);border-radius:var(--radius-lg);overflow:hidden;border:1px solid var(--border);}
.sk-card .sk-media{aspect-ratio:1/1;}
.sk-card .sk-body{padding:14px;display:flex;flex-direction:column;gap:8px;}
.sk-card .sk-line{height:12px;border-radius:4px;}
.sk-card .sk-line.short{width:40%;}
.sk-card .sk-line.med{width:70%;}

/* HERO */
.hero{margin:20px 0 36px;border-radius:var(--radius-xl);overflow:hidden;position:relative;height:480px;display:flex;background:var(--bg-inset);}
.hero-slide{position:absolute;inset:0;display:grid;grid-template-columns:1fr 1fr;align-items:stretch;opacity:0;transition:opacity 500ms var(--ease);pointer-events:none;}
.hero-slide.active{opacity:1;pointer-events:auto;}
.hero-text{padding:60px;display:flex;flex-direction:column;justify-content:center;gap:18px;background:var(--bg-surface);}
.hero-eyebrow{color:var(--primary);font-weight:600;font-size:12px;letter-spacing:.12em;text-transform:uppercase;}
.hero-title{font-family:var(--font-display);font-size:52px;font-weight:700;letter-spacing:-.03em;line-height:.96;margin:0;}
.hero-sub{font-size:16px;color:var(--text-secondary);max-width:34ch;margin:0;line-height:1.55;}
.hero-art{position:relative;display:grid;place-items:center;overflow:hidden;font-size:80px;background:linear-gradient(135deg,var(--primary) 0%,color-mix(in srgb,var(--primary) 70%,#000) 100%);}
.hero-art::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 70% 50%,rgba(255,255,255,.12) 0%,transparent 70%);}
.hero-art::after{content:'';position:absolute;inset:0;background:repeating-linear-gradient(135deg,rgba(255,255,255,0) 0px,rgba(255,255,255,0) 30px,rgba(255,255,255,.05) 30px,rgba(255,255,255,.05) 60px);}
.hero-art span{z-index:2;filter:drop-shadow(0 8px 24px rgba(0,0,0,.25));font-size:120px;}
.hero-dots{position:absolute;bottom:22px;left:60px;display:flex;gap:8px;z-index:3;}
.hero-dot{width:24px;height:3px;border-radius:2px;background:rgba(14,14,16,.15);border:0;cursor:pointer;padding:0;transition:all var(--t-fast);}
.hero-dot.active{background:var(--primary);width:36px;}
.hero-arrows{position:absolute;bottom:16px;right:20px;display:flex;gap:6px;z-index:3;}
.hero-arrow{width:38px;height:38px;border-radius:999px;background:rgba(255,255,255,.9);backdrop-filter:blur(6px);border:1px solid var(--border);display:grid;place-items:center;cursor:pointer;transition:all var(--t-fast);}
.hero-arrow:hover{background:var(--primary);color:white;border-color:var(--primary);}

/* SECTION HEADER */
.sect-head{display:flex;align-items:flex-end;justify-content:space-between;margin:48px 0 20px;gap:16px;}
.sect-title{font-family:var(--font-display);font-size:24px;font-weight:700;letter-spacing:-.015em;margin:0;}
.sect-sub{font-size:13px;color:var(--text-muted);margin-top:4px;}
.more{font-size:13px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:4px;}
.more::after{content:'→';transition:transform var(--t-fast);}
.more:hover::after{transform:translateX(3px);}

/* CATEGORY GRID */
.cat-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:10px;}
.cat-tile{background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px 16px 16px;display:flex;flex-direction:column;align-items:flex-start;gap:20px;cursor:pointer;transition:all var(--t-base) var(--ease);min-height:116px;overflow:hidden;position:relative;}
.cat-tile::before{content:'';position:absolute;inset:0;background:var(--primary);opacity:0;transition:opacity var(--t-fast);}
.cat-tile:hover{border-color:var(--primary);transform:translateY(-3px);box-shadow:0 8px 24px color-mix(in srgb,var(--primary) 20%,transparent);}
.cat-tile:hover::before{opacity:.04;}
.cat-tile:hover .cat-tile-name{color:var(--primary);}
.cat-tile-icon{width:40px;height:40px;background:color-mix(in srgb,var(--primary) 10%,transparent);border-radius:var(--radius-md);display:grid;place-items:center;font-size:20px;transition:transform var(--t-fast);}
.cat-tile:hover .cat-tile-icon{transform:scale(1.1);}
.cat-tile-name{font-family:var(--font-display);font-size:13.5px;font-weight:700;line-height:1.2;transition:color var(--t-fast);}
.cat-tile-count{font-size:10px;color:var(--text-muted);margin-top:1px;}

/* PRODUCT RAIL */
.rail{display:flex;gap:var(--grid-gap);overflow-x:auto;scroll-snap-type:x mandatory;padding-bottom:8px;scrollbar-width:none;}
.rail::-webkit-scrollbar{display:none;}
.rail-item{flex:0 0 240px;scroll-snap-align:start;}

/* FLASH SALE */
.flash-block{background:var(--text-primary);color:var(--text-on-dark);border-radius:var(--radius-xl);padding:32px;margin-top:48px;}
.flash-timer{display:flex;gap:8px;font-family:'DM Sans',monospace;align-items:center;}
.flash-unit{background:rgba(255,255,255,.08);border-radius:10px;padding:8px 14px;text-align:center;min-width:64px;}
.flash-unit-val{font-size:22px;font-weight:700;font-variant-numeric:tabular-nums;}
.flash-unit-label{font-size:10px;opacity:.65;letter-spacing:.1em;text-transform:uppercase;}

/* FILTER SIDEBAR */
.plp-layout{display:grid;grid-template-columns:268px 1fr;gap:28px;align-items:start;}
.filters{background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);position:sticky;top:calc(var(--header-h) + var(--nav-h) + 16px);max-height:calc(100vh - var(--header-h) - var(--nav-h) - 32px);overflow-y:auto;scrollbar-width:thin;scrollbar-color:var(--border) transparent;}
.filters::-webkit-scrollbar{width:4px;}
.filters::-webkit-scrollbar-thumb{background:var(--border-strong);border-radius:4px;}
.filters-header{display:flex;align-items:center;justify-content:space-between;padding:14px 16px 10px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg-surface);z-index:2;}
.filters-header h4{font-family:var(--font-display);font-size:14px;font-weight:700;margin:0;letter-spacing:-.01em;}
.filters-clear{font-size:12px;color:var(--primary);cursor:pointer;font-weight:600;background:none;border:none;padding:0;opacity:0;pointer-events:none;transition:opacity var(--t-fast);}
.filters-clear.show{opacity:1;pointer-events:auto;}

/* filter group — collapsible */
.fg{border-top:1px solid var(--border);}
.fg-summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;font-weight:600;font-size:13px;padding:12px 16px;user-select:none;gap:8px;}
.fg-summary::-webkit-details-marker{display:none;}
.fg-arrow{width:18px;height:18px;flex-shrink:0;transition:transform var(--t-fast);color:var(--text-muted);}
details.fg[open] .fg-arrow{transform:rotate(180deg);}
.fg-body{padding:0 16px 12px;display:flex;flex-direction:column;gap:2px;}
.fg-badge{margin-left:auto;font-size:10.5px;font-weight:700;min-width:18px;height:18px;padding:0 5px;border-radius:999px;background:var(--primary);color:var(--primary-ink);display:grid;place-items:center;}

/* cat search inside sidebar */
.fg-search{width:100%;height:32px;border:1px solid var(--border-strong);border-radius:var(--radius-md);padding:0 10px;font-size:12.5px;margin-bottom:8px;background:var(--bg-inset);}
.fg-search:focus{outline:none;border-color:var(--primary);}

/* filter row */
.filter-check{display:flex;align-items:center;gap:9px;font-size:13px;cursor:pointer;padding:5px 6px;border-radius:var(--radius-sm);transition:background var(--t-fast);line-height:1.3;}
.filter-check:hover{background:var(--bg-inset);}
.filter-check.sub{padding-left:18px;font-size:12.5px;color:var(--text-secondary);}
.filter-check.sub.active-sub{color:var(--text-primary);}
.fck{appearance:none;width:16px;height:16px;border-radius:4px;border:1.5px solid var(--border-strong);cursor:pointer;position:relative;flex-shrink:0;transition:all var(--t-fast);}
.fck[type=radio]{border-radius:50%;}
.fck:checked{background:var(--primary);border-color:var(--primary);}
.fck:checked::after{content:'';position:absolute;inset:0;display:grid;place-items:center;}
.fck[type=checkbox]:checked::after{content:'✓';color:white;font-size:10px;font-weight:800;}
.fck[type=radio]:checked::after{content:'';width:6px;height:6px;background:white;border-radius:50%;margin:auto;}
.filter-label{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.filter-count{margin-left:auto;flex-shrink:0;font-size:11px;font-weight:500;color:var(--text-muted);background:var(--bg-inset);padding:1px 6px;border-radius:999px;}

/* see-more toggle */
.fg-more{font-size:12px;color:var(--primary);cursor:pointer;padding:4px 6px;font-weight:600;background:none;border:none;display:inline-flex;align-items:center;gap:4px;margin-top:2px;}

/* price range */
.price-range-wrap{padding:4px 2px 8px;}
.price-inputs{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;}
.price-input-box{display:flex;flex-direction:column;gap:3px;}
.price-input-box label{font-size:10.5px;color:var(--text-muted);font-weight:500;}
.price-input-box input{height:34px;border:1.5px solid var(--border-strong);border-radius:var(--radius-md);padding:0 10px;font-size:13px;font-weight:600;width:100%;background:var(--bg-inset);}
.price-input-box input:focus{outline:none;border-color:var(--primary);background:var(--bg-surface);}
.range-track{position:relative;height:4px;background:var(--bg-inset);border-radius:2px;margin:12px 4px;}
.range-fill{position:absolute;height:4px;background:var(--primary);border-radius:2px;}
.range-input{position:absolute;width:100%;appearance:none;background:transparent;height:4px;top:0;pointer-events:none;}
.range-input::-webkit-slider-thumb{appearance:none;width:18px;height:18px;border-radius:50%;background:white;border:2px solid var(--primary);cursor:pointer;pointer-events:auto;box-shadow:0 1px 4px rgba(0,0,0,.15);transition:transform var(--t-fast);}
.range-input::-webkit-slider-thumb:hover{transform:scale(1.15);}
.range-input::-moz-range-thumb{width:18px;height:18px;border-radius:50%;background:white;border:2px solid var(--primary);cursor:pointer;pointer-events:auto;}

/* active chips bar */
.chips-bar{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px;}
.chip{display:inline-flex;align-items:center;gap:5px;height:28px;padding:0 10px 0 12px;border-radius:999px;background:color-mix(in srgb,var(--primary) 10%,transparent);border:1px solid color-mix(in srgb,var(--primary) 25%,transparent);font-size:12px;font-weight:600;color:var(--primary);cursor:pointer;transition:all var(--t-fast);}
.chip:hover{background:color-mix(in srgb,var(--primary) 18%,transparent);}
.chip-x{width:14px;height:14px;border-radius:50%;background:color-mix(in srgb,var(--primary) 20%,transparent);display:grid;place-items:center;font-size:9px;font-weight:800;transition:background var(--t-fast);}
.chip:hover .chip-x{background:color-mix(in srgb,var(--primary) 35%,transparent);}
.chip-clear{background:var(--bg-inset);border-color:var(--border-strong);color:var(--text-secondary);}
.chip-clear:hover{background:var(--bg-elev);}

/* plp head */
.plp-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px;flex-wrap:wrap;}
.plp-count{font-size:13px;color:var(--text-secondary);}
.plp-tools{display:flex;gap:8px;align-items:center;}
.sort-select{height:36px;padding:0 32px 0 12px;border-radius:var(--radius-md);background:var(--bg-surface);border:1px solid var(--border-strong);cursor:pointer;font-size:13px;font-weight:500;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238a8a92' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;}
.sort-select:focus{outline:none;border-color:var(--primary);}

/* mobile filter fab */
.filter-fab{display:none;position:fixed;bottom:80px;right:16px;z-index:80;height:46px;padding:0 18px;border-radius:999px;background:var(--text-primary);color:white;border:none;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,.2);align-items:center;gap:8px;}
.filter-fab-badge{background:var(--accent);color:white;font-size:10px;font-weight:700;padding:2px 6px;border-radius:999px;}

/* mobile filter drawer */
.filter-drawer{position:fixed;bottom:0;left:0;right:0;background:var(--bg-surface);z-index:100;border-radius:var(--radius-xl) var(--radius-xl) 0 0;transform:translateY(100%);transition:transform var(--t-base) var(--ease);max-height:85vh;overflow-y:auto;}
.filter-drawer.open{transform:translateY(0);}
.filter-drawer-handle{width:36px;height:4px;background:var(--border-strong);border-radius:2px;margin:10px auto 0;}
.filter-drawer-head{display:flex;align-items:center;justify-content:space-between;padding:12px 16px 8px;border-bottom:1px solid var(--border);}
.filter-drawer-foot{padding:16px;background:var(--bg-surface);position:sticky;bottom:0;border-top:1px solid var(--border);display:grid;grid-template-columns:1fr 2fr;gap:8px;}
.sort-select{height:38px;padding:0 36px 0 14px;border-radius:var(--radius-md);background:var(--bg-surface);border:1px solid var(--border);cursor:pointer;font-size:13px;font-weight:500;appearance:none;}
.chip{display:inline-flex;align-items:center;gap:6px;height:30px;padding:0 12px;border-radius:999px;background:var(--bg-inset);font-size:12.5px;font-weight:500;cursor:pointer;transition:all var(--t-fast);border:1px solid transparent;}
.chip:hover{background:var(--bg-elev);}
.active-filters{display:flex;flex-wrap:wrap;gap:6px;margin:8px 0 16px;}

/* CART */
.cart-page{display:grid;grid-template-columns:1fr 380px;gap:32px;}
.cart-lines{display:flex;flex-direction:column;gap:10px;}
.cart-line{display:grid;grid-template-columns:96px 1fr auto;gap:16px;align-items:center;padding:16px;background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);transition:box-shadow var(--t-fast);}
.cart-line:hover{box-shadow:var(--shadow-sm);}
.cart-line-thumb{aspect-ratio:1/1;width:96px;border-radius:var(--radius-md);overflow:hidden;background:var(--bg-inset);}
.cart-line-thumb img{width:100%;height:100%;object-fit:cover;}
.cart-line-name{font-weight:600;font-size:15px;margin:2px 0 4px;line-height:1.3;}
.cart-line-cat{font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);font-weight:600;}
.cart-line-actions{display:flex;align-items:center;gap:12px;margin-top:10px;}
.cart-line-price{font-weight:700;font-size:16px;text-align:right;font-family:var(--font-display);}
.qty-stepper{display:inline-flex;align-items:center;border:1px solid var(--border-strong);border-radius:var(--radius-md);overflow:hidden;}
.qty-stepper button{width:36px;height:36px;border:0;background:transparent;cursor:pointer;font-size:16px;}
.qty-stepper button:hover{background:var(--bg-inset);}
.qty-stepper span{width:40px;text-align:center;font-size:14px;font-weight:600;}
.summary{position:sticky;top:80px;background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;}
.summary h4{font-family:var(--font-display);font-size:17px;font-weight:700;margin:0 0 12px;}
.summary-row{display:flex;justify-content:space-between;padding:6px 0;font-size:13.5px;}
.summary-row.total{font-weight:700;font-size:16px;border-top:1px solid var(--border);margin-top:8px;padding-top:12px;font-family:var(--font-display);}

/* CHECKOUT */
.checkout{display:grid;grid-template-columns:1.6fr 1fr;gap:32px;}
.co-section{background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;margin-bottom:16px;}
.co-section h3{font-family:var(--font-display);font-size:18px;font-weight:700;margin:0 0 16px;}
.field{display:flex;flex-direction:column;gap:6px;margin-bottom:12px;}
.field label{font-size:12px;font-weight:500;color:var(--text-secondary);}
.field input,.field select,.field textarea{height:44px;padding:0 14px;border:1px solid var(--border-strong);border-radius:var(--radius-md);background:var(--bg-surface);font-size:14px;}
.field textarea{height:80px;padding:12px 14px;resize:vertical;}
.field input:focus,.field select:focus,.field textarea:focus{outline:0;border-color:var(--primary);box-shadow:0 0 0 4px color-mix(in srgb,var(--primary) 16%,transparent);}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.pay-method{display:flex;gap:12px;align-items:center;padding:14px;border:1.5px solid var(--border);border-radius:var(--radius-md);cursor:pointer;margin-bottom:8px;transition:all var(--t-fast);}
.pay-method:hover{border-color:var(--border-strong);}
.pay-method.selected{border-color:var(--primary);background:color-mix(in srgb,var(--primary) 6%,transparent);}
.pay-logo{font-weight:700;font-size:11px;padding:4px 8px;background:var(--bg-inset);border-radius:4px;}
.co-order-item{display:flex;gap:12px;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);}
.co-order-item:last-of-type{border-bottom:0;}
.co-order-thumb{width:48px;height:48px;border-radius:var(--radius-sm);overflow:hidden;background:var(--bg-inset);flex-shrink:0;}
.co-order-thumb img{width:100%;height:100%;object-fit:cover;}

/* TOAST */
.toast-host{position:fixed;bottom:28px;left:50%;transform:translate(-50%,0);z-index:300;display:flex;flex-direction:column;gap:8px;align-items:center;pointer-events:none;}
.toast{background:var(--text-primary);color:var(--bg-surface);padding:11px 18px;border-radius:var(--radius-full);display:inline-flex;align-items:center;gap:10px;font-size:13.5px;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,.2);pointer-events:auto;animation:toast-in 280ms var(--ease);max-width:90vw;}
@keyframes toast-in{from{opacity:0;transform:translateY(16px) scale(.95);}to{opacity:1;transform:translateY(0) scale(1);}}
.fly-cart{position:fixed;z-index:250;width:56px;height:56px;border-radius:50%;overflow:hidden;pointer-events:none;box-shadow:var(--shadow-lg);transition:all 700ms cubic-bezier(.55,-.04,.4,1);}

/* PDP */
.pdp-title{font-family:var(--font-display);font-size:32px;font-weight:700;letter-spacing:-.02em;line-height:1.15;margin:8px 0 0;}
.pdp-price{font-family:var(--font-display);font-size:34px;font-weight:700;}
.pdp-actions-desktop{display:flex;flex-direction:column;gap:10px;margin-top:16px;}
.pdp-actions-mobile{display:none;}
.pdp-img-wrap{aspect-ratio:1/1;border-radius:var(--radius-xl);overflow:hidden;background:var(--bg-inset);position:relative;cursor:zoom-in;}
.pdp-img-main{width:100%;height:100%;object-fit:cover;transition:transform .3s;}
.pdp-img-wrap:hover .pdp-img-main{transform:scale(1.04);}
.pdp-zoom-btn{position:absolute;bottom:10px;right:10px;background:rgba(0,0,0,.5);color:#fff;border-radius:50%;width:36px;height:36px;display:grid;place-items:center;pointer-events:none;transition:opacity .2s;opacity:.8;}
.pdp-img-wrap:hover .pdp-zoom-btn{opacity:1;}
.pdp-rel-card{background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;cursor:pointer;transition:box-shadow .2s,transform .2s;}
.pdp-rel-card:hover{box-shadow:var(--shadow-md);transform:translateY(-2px);}
@media(max-width:768px){.pdp-related-grid{grid-template-columns:repeat(2,1fr)!important;}}

/* TESTIMONIALS */
.testimonials-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
.testi-card{background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;display:flex;flex-direction:column;gap:12px;transition:box-shadow var(--t-fast);}
.testi-card:hover{box-shadow:var(--shadow-md);}
.testi-stars{display:flex;gap:2px;}
.star-on{color:#f59e0b;font-size:18px;}
.star-off{color:var(--border-strong);font-size:18px;}
.testi-text{font-size:14px;color:var(--text-secondary);line-height:1.6;flex:1;font-style:italic;margin:0;}
.testi-author{display:flex;align-items:center;gap:10px;}
.testi-avatar{width:36px;height:36px;border-radius:50%;background:var(--primary);color:var(--primary-ink);display:grid;place-items:center;font-family:var(--font-display);font-weight:700;font-size:15px;flex-shrink:0;}
.testi-name{font-size:13.5px;font-weight:600;}
.testi-date{font-size:11.5px;color:var(--text-muted);}

/* FOOTER */
.footer{background:var(--bg-surface);border-top:1px solid var(--border);margin-top:64px;padding:0;}
.footer-main{display:grid;grid-template-columns:1.4fr 1fr 1fr 1fr 1fr;gap:40px;padding:48px 0 40px;}
.footer-col{}
.footer-col-brand .fc-logo{margin-bottom:12px;}
.fc-logo img{height:{{ $settings['footer_logo_height'] ?? 60 }}px;width:auto;object-fit:contain;}
.footer-col-brand .fc-tagline{color:var(--text-secondary);font-size:13px;line-height:1.6;max-width:26ch;margin:0;}
.footer-col h6{font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text-muted);margin:0 0 16px;}
.footer-col ul{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;}
.footer-col li a{color:var(--text-secondary);font-size:13.5px;text-decoration:none;display:flex;align-items:center;gap:7px;min-height:24px;transition:color var(--t-fast);}
.footer-col li a:hover{color:var(--text-primary);}
.fc-social{display:flex;gap:10px;margin-top:4px;}
.fc-social a{display:grid;place-items:center;width:36px;height:36px;border-radius:50%;border:1px solid var(--border-strong);color:var(--text-secondary);transition:background var(--t-fast),color var(--t-fast),border-color var(--t-fast);}
.fc-social a:hover{background:var(--primary);color:var(--primary-ink);border-color:var(--primary);}
.fc-social svg{width:17px;height:17px;}
.fc-pay{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;}
.pay-logo{display:inline-flex;align-items:center;justify-content:center;height:36px;min-width:52px;padding:0 8px;border-radius:8px;border:1px solid var(--border-strong);background:#fff;box-shadow:var(--shadow-sm);}
.pay-logo img{height:22px;width:auto;object-fit:contain;display:block;}
.footer-base{padding:16px 0;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--text-muted);flex-wrap:wrap;gap:10px;}

/* trust strip above footer */
.trust-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:24px;padding:28px 0;border-top:1px solid var(--border);border-bottom:1px solid var(--border);}
.trust-item{display:flex;gap:12px;align-items:flex-start;}
.trust-item .ti-icon{font-size:22px;flex-shrink:0;}
.trust-item .ti-title{font-size:13.5px;font-weight:600;line-height:1.2;}
.trust-item .ti-sub{font-size:12px;color:var(--text-muted);margin-top:2px;}

/* newsletter (above footer) */
.footer-newsletter{background:var(--text-primary);color:var(--text-on-dark);border-radius:var(--radius-xl);padding:40px;display:grid;grid-template-columns:1.2fr 1fr;gap:32px;align-items:center;margin-bottom:0;}
.footer-newsletter h3{font-family:var(--font-display);font-size:26px;font-weight:700;letter-spacing:-.02em;margin:0 0 8px;line-height:1.1;}
.footer-newsletter p{color:rgba(245,245,247,.75);margin:0;font-size:14px;line-height:1.6;}
.newsletter-form{display:flex;gap:8px;flex-wrap:wrap;}
.newsletter-form input{flex:1;min-width:180px;height:48px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:var(--radius-md);padding:0 16px;color:var(--text-on-dark);font-size:14px;}
.newsletter-form input::placeholder{color:rgba(245,245,247,.45);}
.newsletter-form input:focus{outline:none;border-color:rgba(255,255,255,.4);}

/* MOBILE MENU ARROW */
details[open] .mob-arrow{transform:rotate(180deg);}
.mob-cat-summary::-webkit-details-marker{display:none;}

/* MOBILE DRAWERS */
.drawer-backdrop{position:fixed;inset:0;background:var(--bg-overlay);z-index:90;opacity:0;pointer-events:none;transition:opacity var(--t-base);}
.drawer-backdrop.show{opacity:1;pointer-events:auto;}
.drawer{position:fixed;background:var(--bg-surface);z-index:91;transition:transform var(--t-base) var(--ease);overflow-y:auto;}
.drawer-left{top:0;left:0;bottom:0;width:84%;max-width:360px;transform:translateX(-100%);}
.drawer-left.show{transform:translateX(0);}
.drawer-bottom{bottom:0;left:0;right:0;transform:translateY(100%);}
.drawer-bottom.show{transform:translateY(0);}

/* BACK TO TOP */
.back-top{position:fixed;bottom:24px;right:24px;width:44px;height:44px;border-radius:50%;background:var(--text-primary);color:var(--bg-surface);border:0;cursor:pointer;display:grid;place-items:center;box-shadow:var(--shadow-lg);opacity:0;pointer-events:none;transition:opacity var(--t-base);z-index:100;}
.back-top.show{opacity:1;pointer-events:auto;}

/* CART PREVIEW */
.cart-preview{position:absolute;right:0;top:calc(100% + 8px);width:360px;background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);padding:14px;z-index:60;}
.cart-preview h5{margin:0 0 10px;font-family:var(--font-display);font-size:15px;}
.cp-row{display:flex;gap:10px;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);}
.cp-row:last-of-type{border-bottom:0;}
.cp-thumb{width:44px;height:44px;border-radius:var(--radius-sm);overflow:hidden;flex-shrink:0;background:var(--bg-inset);}
.cp-thumb img{width:100%;height:100%;object-fit:cover;}
.cp-name{font-size:13px;font-weight:500;}
.cp-price{margin-left:auto;font-weight:600;font-size:13px;}
.cp-foot{margin-top:10px;display:grid;gap:6px;}
.cp-total{display:flex;justify-content:space-between;padding:8px 0;font-weight:600;}

/* FULLSCREEN CHECKOUT MODAL */
.ck-modal{position:fixed;inset:0;z-index:200;background:var(--bg-body);overflow-y:auto;overscroll-behavior:contain;}
body.ck-open{overflow:hidden;}
.ck-modal-header{background:var(--bg-surface);border-bottom:1px solid var(--border);padding:16px var(--page-pad);display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:10;}
.ck-modal-header h2{font-family:var(--font-display);font-size:20px;font-weight:700;margin:0;flex:1;}
.ck-grid{display:grid;grid-template-columns:1fr 420px;gap:32px;align-items:start;max-width:1100px;margin:32px auto;padding:0 var(--page-pad) 64px;}
.ck-submit-mobile{display:none;}
.ck-submit-desktop{display:block;}
.ck-submit-mobile{display:none;}

/* ANIMATIONS */
.fade-in{animation:fadeIn 320ms var(--ease);}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}
html{scroll-behavior:smooth;}

/* RESPONSIVE */
@media(max-width:1100px){
  :root{--cols:3;}
  .cart-page{grid-template-columns:1fr;}
  .checkout{grid-template-columns:1fr;}
  .ck-grid{grid-template-columns:1fr;}
  .footer-main{grid-template-columns:1fr 1fr 1fr;gap:28px;}
  .footer-newsletter{grid-template-columns:1fr;padding:28px;}
  .nav-flyout{display:none!important;}
  .nav-all-flyout{display:none!important;}
  .cat-page-grid.density-4,.cat-page-grid.density-5{grid-template-columns:repeat(3,1fr);}
  .testimonials-grid{grid-template-columns:1fr 1fr;}
  .hero-title{font-size:40px;}
  .rail-item{flex:0 0 200px;}
}
@media(max-width:768px){
  :root{--cols:2;--page-pad:16px;--grid-gap:12px;--header-h:56px;--logo-max:36px;}
  /* TOPBAR: solo texto centrado */
  .topbar{height:auto;padding:6px 16px;}
  .topbar-inner{justify-content:center;padding:0;}
  .topbar-promo{text-align:center;font-size:11.5px;white-space:normal;line-height:1.4;width:100%;}
  .topbar-links{display:none;}
  /* HEADER: hamburger | logo centrado | icons */
  .hamburger{display:grid;flex-shrink:0;}
  .search{display:none;}
  .header-inner{height:var(--header-h);gap:0;padding:0 8px;justify-content:space-between;}
  .logo{flex:1;justify-content:center;max-width:none;gap:6px;}
  .logo img{max-height:36px!important;height:36px!important;width:auto!important;max-width:140px!important;object-fit:contain!important;}
  .logo-mark{width:30px;height:30px;font-size:14px;flex-shrink:0;}
  .header-icons{gap:0;flex-shrink:0;}
  .icon-btn{width:44px;height:44px;}
  .icon-btn svg{width:22px;height:22px;}
  /* cart preview oculto en móvil */
  .cart-preview{display:none!important;}
  /* nav */
  .nav{display:none;}
  /* hero */
  .hero{height:auto;min-height:200px;border-radius:var(--radius-lg);}
  .hero-slide{grid-template-columns:1fr;min-height:200px;}
  .hero-art{display:none;}
  .hero-text{padding:24px 20px;gap:12px;}
  .hero-title{font-size:26px;letter-spacing:-.02em;}
  .hero-sub{font-size:14px;max-width:100%;}
  .hero-dots{left:20px;bottom:14px;}
  .hero-arrows{bottom:10px;right:12px;}
  /* sections */
  .sect-head{margin:32px 0 14px;}
  .sect-title{font-size:20px;}
  /* category grid */
  .cat-grid{grid-template-columns:repeat(3,1fr);gap:8px;}
  .cat-tile{padding:14px 10px 12px;min-height:90px;gap:10px;}
  .cat-tile-icon{width:32px;height:32px;font-size:16px;}
  .cat-tile-name{font-size:12px;}
  /* product rail */
  .rail-item{flex:0 0 170px;}
  /* plp */
  .plp-layout{grid-template-columns:1fr;}
  .filters{display:none;}
  .filter-fab{display:inline-flex;}
  /* cart */
  .cart-page{grid-template-columns:1fr;}
  .cart-line{grid-template-columns:72px 1fr auto;gap:10px;padding:12px;}
  .cart-line-thumb{width:72px;}
  /* checkout */
  .checkout{grid-template-columns:1fr;}
  .field-row{grid-template-columns:1fr;}
  /* pdp */
  .pdp-wrap{grid-template-columns:1fr!important;gap:16px!important;}
  .pdp-gallery-col{position:static!important;}
  .pdp-title{font-size:22px;}
  .pdp-price{font-size:26px;}
  .pdp-actions-desktop{display:none!important;}
  .pdp-actions-mobile{display:flex;position:fixed;bottom:0;left:0;right:0;padding:10px 14px;gap:10px;background:var(--bg-surface);border-top:1px solid var(--border);z-index:60;box-shadow:0 -4px 16px rgba(0,0,0,.08);}
  /* flash */
  .flash-block{padding:20px;}
  .flash-timer{gap:6px;}
  .flash-unit{padding:6px 10px;min-width:48px;}
  .flash-unit-val{font-size:18px;}
  /* trust */
  .trust-strip{grid-template-columns:1fr 1fr;gap:12px;padding:18px 0;}
  /* testimonials */
  .testimonials-grid{grid-template-columns:1fr;}
  /* newsletter */
  .footer-newsletter{grid-template-columns:1fr;padding:24px;border-radius:var(--radius-lg);margin-top:40px;}
  .footer-newsletter h3{font-size:20px;}
  .newsletter-form{flex-direction:column;}
  .newsletter-form input,.newsletter-form .btn{width:100%;min-height:48px;box-sizing:border-box;}
  /* footer */
  .footer-main{grid-template-columns:1fr 1fr;gap:24px;padding:32px 0 24px;}
  .fc-social{flex-wrap:wrap;}
  .fc-pay{flex-wrap:wrap;}
  /* categories page */
  .cat-page-grid.density-3,.cat-page-grid.density-4,.cat-page-grid.density-5{grid-template-columns:repeat(2,1fr);}
  .cat-page-search{max-width:100%;}
  .cat-page-tools{flex-direction:column;align-items:flex-start;}
  .alpha-bar{gap:3px;}
  .alpha-btn{width:28px;height:28px;font-size:11.5px;}
  /* nav megamenu — oculto en tablet/móvil ya manejado por hamburguesa */
  .nav-all-btn{display:none;}
}
@media(max-width:480px){
  :root{--cols:2;--page-pad:12px;}
  /* hero */
  .hero-title{font-size:22px;}
  .hero-text{padding:20px 16px;}
  /* cats */
  .cat-grid{grid-template-columns:repeat(2,1fr);gap:6px;}
  .cat-tile{padding:12px 8px;min-height:80px;}
  /* rail */
  .rail-item{flex:0 0 150px;}
  /* flash timer compacto */
  .flash-unit{padding:4px 8px;min-width:42px;}
  .flash-unit-val{font-size:16px;}
  /* trust: columna única */
  .trust-strip{grid-template-columns:1fr;}
  /* footer colapsa a 1 col */
  .footer-main{grid-template-columns:1fr;}
  /* categories page */
  .cat-page-grid.density-2{grid-template-columns:1fr 1fr;}
  .cat-page-grid.density-3,.cat-page-grid.density-4,.cat-page-grid.density-5{grid-template-columns:repeat(2,1fr);}
  /* cart line simplificado */
  .cart-line{grid-template-columns:60px 1fr;gap:8px;}
  .cart-line-price{grid-column:2;}
  /* chips */
  .chips-bar{gap:4px;}
  .chip{font-size:11.5px;height:26px;padding:0 8px 0 10px;}
  /* HEADER 480px */
  :root{--header-h:52px;--logo-max:32px;}
  .header-inner{height:var(--header-h);padding:0 6px;}
  .logo img{max-height:32px!important;height:32px!important;width:auto!important;max-width:120px!important;object-fit:contain!important;}
  .logo-mark{width:26px;height:26px;font-size:12px;}
  .icon-btn{width:40px;height:40px;}
  .icon-btn svg{width:20px;height:20px;}
  .icon-badge{min-width:16px;height:16px;font-size:9px;top:1px;right:1px;}
  /* TOPBAR 480px */
  .topbar{padding:5px 12px;}
  .topbar-promo{font-size:11px;}
  /* checkout móvil */
  .ck-modal-header{padding:10px 14px;gap:8px;}
  .ck-modal-header h2{font-size:15px;}
  .ck-grid{display:flex;flex-direction:column;margin:0 auto;padding:12px 12px 100px;max-width:100%;width:100%;}
  .ck-grid>div{width:100%;}
  /* resumen arriba en móvil */
  .ck-grid>div:last-child{order:-1;margin-bottom:8px;}
  .summary{position:static!important;padding:14px;width:100%;box-sizing:border-box;}
  .summary h4{font-size:15px;margin-bottom:8px;}
  .summary-row{font-size:13px;}
  /* secciones del form */
  .co-section{padding:14px;margin-bottom:8px;width:100%;box-sizing:border-box;}
  .co-section h3{font-size:15px;margin-bottom:10px;}
  .field label{font-size:12px;}
  .field input,.field textarea,.field select{font-size:16px;height:46px;width:100%;box-sizing:border-box;} /* evita zoom en iOS */
  .field-row{grid-template-columns:1fr;gap:0;}
  .field{margin-bottom:10px;}
  /* botón fijo bottom solo en móvil */
  .ck-submit-desktop{display:none;}
  .ck-submit-mobile{display:none;position:fixed;bottom:0;left:0;right:0;padding:10px 14px;background:var(--bg-surface);border-top:1px solid var(--border);z-index:210;box-shadow:0 -4px 16px rgba(0,0,0,.08);}
  body.ck-open .ck-submit-mobile{display:flex;}
  .ck-modal{padding-bottom:80px;}
  /* product card */
  .card-name{font-size:13px;}
  .price-now{font-size:15px;}
  .card-body{padding:10px 10px 12px;}
  /* section titles */
  .sect-title{font-size:18px;}
  .h1{font-size:24px;}
  /* footer bottom */
  .footer-bottom{flex-direction:column;gap:8px;text-align:center;}
  .fc-pay{justify-content:center;}
}
</style>
</head>
<body x-data="ecStore()" x-init="init()" @scroll.window="onScroll">

{{-- TOPBAR --}}
@if($announcementText)
<div class="topbar">
  <div class="topbar-inner">
    <span class="topbar-promo">{{ $announcementText }}</span>
    <nav class="topbar-links">
      <a>Preguntas frecuentes</a>
      <a>Atención al cliente</a>
    </nav>
  </div>
</div>
@endif

{{-- HEADER --}}
<header class="header" :class="{'search-open': searchOpen}">
  <div class="header-inner">
    <button class="icon-btn hamburger" @click="mobileMenuOpen=true" aria-label="Menú">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <a href="#" @click.prevent="page='home';filterCat=null;filterQ=''" class="logo" style="cursor:pointer" x-data>
      @if($logoUrl)
        <img src="{{ asset('storage/'.$logoUrl) }}" alt="{{ $project->name }}">
      @else
        <span class="logo-mark">{{ strtoupper(substr($project->name,0,1)) }}</span>
        <span>{{ $project->name }}</span>
      @endif
    </a>
    <div class="search">
      <input class="search-input" placeholder="Buscar productos…" x-model="searchQ"
        @focus="searchFocused=true" @blur="setTimeout(()=>searchFocused=false,200)"
        @keydown.enter="doSearch()" aria-label="Buscar">
      <span class="search-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </span>
      <div class="search-dropdown" x-show="searchFocused" x-cloak>
        <template x-if="!searchQ">
          <div class="sd-section">Categorías</div>
        </template>
        <template x-if="!searchQ">
          <template x-for="cat in allCats.slice(0,5)" :key="cat.id">
            <div class="sd-item" @mousedown="filterCat=cat.id;page='catalog';searchFocused=false">
              <span class="sd-name" x-text="cat.name"></span>
            </div>
          </template>
        </template>
        <template x-if="searchQ">
          <div class="sd-section">Productos</div>
        </template>
        <template x-if="searchQ">
          <template x-for="p in searchResults.slice(0,6)" :key="p.id">
            <div class="sd-item" @mousedown="openProduct(p.id);searchFocused=false;searchQ=''">
              <div class="sd-thumb">
                <template x-if="p.img"><img :src="p.img" :alt="p.name"></template>
                <template x-if="!p.img"><div style="width:100%;height:100%;display:grid;place-items:center;font-size:18px;background:var(--bg-inset)">📦</div></template>
              </div>
              <div>
                <div class="sd-name" x-text="p.name"></div>
                <div class="sd-meta" x-text="p.cat"></div>
              </div>
              <span class="sd-price" x-text="fmt(p.price)"></span>
            </div>
          </template>
        </template>
        <template x-if="searchQ && searchResults.length===0">
          <div style="padding:16px 10px;color:var(--text-muted);font-size:13px">Sin resultados para "<span x-text="searchQ"></span>"</div>
        </template>
      </div>
    </div>
    <div class="header-icons">
      @if($quoteWa)
      <a href="https://wa.me/{{ $quoteWa }}" target="_blank" rel="noopener" class="icon-btn" aria-label="WhatsApp">
        <svg viewBox="0 0 24 24" fill="currentColor" style="color:#25d366"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2zm5.6 14.2c-.2.6-1.2 1.1-1.7 1.2-.4 0-1 .1-1.6-.1-.4-.1-.9-.3-1.6-.6-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.4-1.1-2.6 0-1.3.6-1.9.9-2.1.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4l.8 1.8c.1.2.1.4 0 .6l-.3.4-.3.4c-.1.1-.2.3-.1.5.1.2.6 1 1.3 1.6.9.8 1.7 1 1.9 1.1.2.1.3.1.5-.1l.7-.8c.2-.2.4-.2.6-.1l1.6.8c.2.1.3.2.4.3 0 .1 0 .8-.2 1.4z"/></svg>
      </a>
      @endif
      <div style="position:relative" @mouseenter="cartHover=true" @mouseleave="cartHover=false">
        <button id="cart-icon-btn" class="icon-btn" @click="window.innerWidth<=768 ? (cartDrawerOpen=true) : (page='cart')" aria-label="Carrito">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
          <span class="icon-badge" x-show="cartCount>0" x-text="cartCount"></span>
        </button>
        {{-- Cart preview hover (solo desktop) --}}
        <div class="cart-preview" x-show="cartHover && cart.length>0" x-cloak style="right:0">
          <h5>Tu carrito (<span x-text="cart.length"></span>)</h5>
          <div style="max-height:200px;overflow-y:auto">
            <template x-for="(item,i) in cart.slice(0,3)" :key="item.id">
              <div class="cp-row">
                <div class="cp-thumb">
                  <template x-if="item.img"><img :src="item.img" :alt="item.name"></template>
                  <template x-if="!item.img"><div style="width:100%;height:100%;display:grid;place-items:center;font-size:20px;background:var(--bg-inset)">📦</div></template>
                </div>
                <div style="min-width:0">
                  <div class="cp-name" x-text="item.name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></div>
                  <div style="font-size:11.5px;color:var(--text-muted)">Cant. <span x-text="item.qty"></span></div>
                </div>
                <span class="cp-price" x-text="fmt(item.price*item.qty)"></span>
              </div>
            </template>
          </div>
          <div class="cp-foot">
            <div class="cp-total"><span>Subtotal</span><span x-text="fmt(subtotal)"></span></div>
            <button class="btn btn-outline btn-sm" @click="page='cart';cartHover=false">Ver carrito</button>
            <button class="btn btn-primary btn-sm" @click="checkoutOpen=true;cartHover=false">{{ $isQuoteOnly ? ($settings['btn_quote_text'] ?? 'Cotizar') : 'Finalizar compra' }}</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>

{{-- NAV --}}
@php
  $navMax = 6;
  $navCats = $categories->take($navMax);
  $overflowCats = $categories->skip($navMax);
  $hasOverflow = $overflowCats->count() > 0;
@endphp
<nav class="nav" aria-label="Categorías">
  <div class="nav-inner">
    <div class="nav-item" :class="{active:page==='home'}" @click="page='home';filterCat=null">Inicio</div>

    {{-- Primeras 6 categorías --}}
    @foreach($navCats as $cat)
    <div class="nav-item" :class="{active:page==='catalog'&&filterCat==='{{ $cat->id }}'}"
      @click="page='catalog';filterCat='{{ $cat->id }}';filterSubCat=null"
      @mouseenter="navOpen='{{ $cat->id }}'"
      @mouseleave="navOpen=null"
      style="position:relative">
      {{ $cat->name }}
      @if($cat->children->count())
      <div class="nav-flyout" x-show="navOpen==='{{ $cat->id }}'" x-cloak @mouseenter="navOpen='{{ $cat->id }}'" @mouseleave="navOpen=null">
        <a href="#" @click.prevent.stop="filterCat='{{ $cat->id }}';filterSubCat=null;page='catalog';navOpen=null" style="font-weight:700;color:var(--primary)">
          Ver todo en {{ $cat->name }}
        </a>
        @foreach($cat->children as $sub)
        <a href="#" @click.prevent.stop="filterCat='{{ $cat->id }}';filterSubCat='{{ $sub->id }}';page='catalog';navOpen=null">
          {{ $sub->name }}
          <span style="float:right;font-size:11px;color:var(--text-muted)">{{ $sub->products->count() }}</span>
        </a>
        @endforeach
      </div>
      @endif
    </div>
    @endforeach

    {{-- Botón "Todas las categorías" — siempre visible --}}
    <div style="position:relative;height:100%;margin-left:auto" @mouseenter="navAllOpen=true" @mouseleave="navAllOpen=false">
      <button class="nav-all-btn" :class="{active:page==='categories',open:navAllOpen}"
        @click="page='categories';navAllOpen=false" aria-expanded="navAllOpen" aria-haspopup="true">
        Todas las categorías
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
      </button>
      {{-- Megamenú con TODAS las categorías --}}
      <div class="nav-all-flyout" x-show="navAllOpen" x-cloak
        @mouseenter="navAllOpen=true" @mouseleave="navAllOpen=false" style="right:0">
        @foreach($categories as $cat)
        @php $catTotal = $cat->products->count() + $cat->children->sum(fn($s)=>$s->products->count()); @endphp
        <div class="naf-item"
          @click="filterCat='{{ $cat->id }}';filterSubCat=null;page='catalog';navAllOpen=false">
          <div class="naf-name">{{ $cat->name }}</div>
          <div class="naf-count">{{ $catTotal }} productos</div>
        </div>
        @endforeach
        <div class="naf-divider"></div>
        <div class="naf-footer">
          <button class="btn btn-primary btn-sm" @click="page='categories';navAllOpen=false">
            Ver página de categorías →
          </button>
        </div>
      </div>
    </div>

  </div>
</nav>

{{-- ═══════════════════════════════════════════════
     HOME PAGE
═══════════════════════════════════════════════ --}}
<main id="content" x-show="page==='home'" class="page page-pad fade-in">

  {{-- HERO --}}
  <section class="hero" data-store-native-section="hero" @mouseenter="heroPaused=true" @mouseleave="heroPaused=false">
    <div class="hero-slide" :class="{active:heroIdx===0}">
      <div class="hero-text">
        <span class="eyebrow hero-eyebrow">{{ $settings['hero_badge'] ?? 'Bienvenido' }}</span>
        <h1 class="hero-title">{{ $heroTitle }}</h1>
        <p class="hero-sub">{{ $heroSub }}</p>
        <div class="hero-cta" style="margin-top:12px">
          <button class="btn btn-primary btn-lg" @click="page='catalog'">{{ $settings['hero_cta1_text'] ?? 'Ver catálogo' }}</button>
        </div>
      </div>
      <div class="hero-art" style="background:var(--primary)">
        <span style="z-index:2;font-size:100px">🛍️</span>
      </div>
    </div>
    @if(isset($settings['banner1_title']))
    <div class="hero-slide" :class="{active:heroIdx===1}">
      <div class="hero-text">
        <span class="eyebrow hero-eyebrow">Destacado</span>
        <h1 class="hero-title">{{ $settings['banner1_title'] }}</h1>
        <p class="hero-sub">{{ $settings['banner1_sub'] ?? '' }}</p>
        <div class="hero-cta" style="margin-top:12px">
          <button class="btn btn-primary btn-lg" @click="page='catalog'">Explorar</button>
        </div>
      </div>
      <div class="hero-art" style="background:#1a1a2e">
        <span style="z-index:2;font-size:100px">✨</span>
      </div>
    </div>
    @endif
    <div class="hero-dots">
      <button class="hero-dot" :class="{active:heroIdx===0}" @click="heroIdx=0"></button>
      @if(isset($settings['banner1_title']))
      <button class="hero-dot" :class="{active:heroIdx===1}" @click="heroIdx=1"></button>
      @endif
    </div>
    <div class="hero-arrows">
      <button class="hero-arrow" @click="heroIdx=(heroIdx-1+2)%2">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <button class="hero-arrow" @click="heroIdx=(heroIdx+1)%2">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>
  </section>

  {{-- TRUST STRIP --}}
  <div class="trust-strip" data-store-native-section="benefits" style="margin-top:0;margin-bottom:0">
    <div class="trust-item"><span class="ti-icon">{{ $trustIcon1 }}</span><div><div class="ti-title">{{ $trustText1 }}</div></div></div>
    <div class="trust-item"><span class="ti-icon">{{ $trustIcon2 }}</span><div><div class="ti-title">{{ $trustText2 }}</div></div></div>
    <div class="trust-item"><span class="ti-icon">{{ $trustIcon3 }}</span><div><div class="ti-title">{{ $trustText3 }}</div></div></div>
    <div class="trust-item"><span class="ti-icon">{{ $trustIcon4 }}</span><div><div class="ti-title">{{ $trustText4 }}</div></div></div>
  </div>

  {{-- CATEGORY GRID --}}
  <section data-store-native-section="featured_categories">
    <div class="sect-head">
      <div>
        <h2 class="sect-title">Explorar categorías</h2>
        <p class="sect-sub">Encuentra lo que buscas rápidamente.</p>
      </div>
      <a class="more" @click="page='catalog';filterCat=null">Ver todo</a>
    </div>
    <div class="cat-grid">
      @foreach($categories as $cat)
      <div class="cat-tile" @click="filterCat='{{ $cat->id }}';page='catalog'">
        <div class="cat-tile-icon">🏷️</div>
        <div>
          <div class="cat-tile-name">{{ $cat->name }}</div>
          <div class="cat-tile-count">{{ $cat->products->count() + $cat->children->sum(fn($s)=>$s->products->count()) }} productos</div>
        </div>
      </div>
      @endforeach
    </div>
  </section>

  {{-- FEATURED PRODUCTS RAIL --}}
  <section data-store-native-section="featured_products">
    <div class="sect-head">
      <div>
        <h2 class="sect-title">{{ $settings['catalog_section_title'] ?? 'Productos destacados' }}</h2>
        <p class="sect-sub">Lo más vendido de la semana.</p>
      </div>
      <div style="display:flex;gap:8px;align-items:center">
        <button class="icon-btn" @click="railScroll('rail-featured',-1)" style="background:var(--bg-inset)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button class="icon-btn" @click="railScroll('rail-featured',1)" style="background:var(--bg-inset)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>
    </div>
    @php
      $featuredProducts = collect();
      foreach($categories as $_fc) {
        $featuredProducts = $featuredProducts->merge($_fc->products);
        foreach($_fc->children as $_fs) {
          $featuredProducts = $featuredProducts->merge($_fs->products->map(function($p) use($_fc,$_fs){ $p->_catLabel=$_fs->name; return $p; }));
        }
      }
      $featuredProducts = $featuredProducts->take(12);
    @endphp
    <div class="rail" id="rail-featured">
      @foreach($featuredProducts as $product)
      @php
        $pImg  = $product->mainImage ? $product->main_image_url : null;
        $pPct  = $product->compare_price ? round((1-$product->price/$product->compare_price)*100) : 0;
        $pCat  = $product->_catLabel ?? $product->category->name ?? '';
      @endphp
      <div class="rail-item">
        <article class="card" @click="openProduct({{ $product->id }})">
          <div class="card-media">
            @if($pImg)<img src="{{ $pImg }}" alt="{{ $product->name }}" loading="lazy">
            @else<div class="card-media-placeholder">📦</div>@endif
            <div class="card-badges">
              @if($pPct > 0)<span class="badge badge-sale">-{{ $pPct }}%</span>@endif
              @if($product->stock == 0)<span class="badge" style="background:rgba(0,0,0,.5);color:white">Agotado</span>@endif
            </div>
            @if(!$isQuoteOnly && $product->stock != 0)
            <div class="card-quick-view">
              <button class="btn" @click.stop="openQuickView({id:{{ $product->id }},name:'{{ addslashes($product->name) }}',price:{{ $product->price }},img:'{{ $product->mainImage?->url ? asset('storage/'.$product->mainImage->url) : '' }}'})">👁 Vista rápida</button>
            </div>
            @endif
          </div>
          <div class="card-body">
            <div class="card-cat">{{ $pCat }}</div>
            <div class="card-name">{{ $product->name }}</div>
            <div class="card-price-row">
              <span class="price-now">{{ $currency }} {{ number_format($product->price,2) }}</span>
              @if($product->compare_price)<span class="price-was">{{ $currency }} {{ number_format($product->compare_price,2) }}</span>@endif
              @if($pPct > 0)<span class="price-pct">-{{ $pPct }}%</span>@endif
            </div>
            <div class="card-cta">
              @if($product->stock == 0)
              <button class="btn btn-sm btn-block" disabled style="background:var(--bg-inset);color:var(--text-muted);cursor:not-allowed">Agotado</button>
              @else
              <button class="btn btn-primary btn-sm btn-block" @click.stop="{{ $isQuoteOnly ? 'openQuotePopup('.$product->id.')' : 'addToCart('.$product->id.')' }}">
                @if(!$isQuoteOnly)
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                {{ $settings['btn_cart_text'] ?? 'Agregar al carrito' }}
                @else
                {{ $settings['btn_quote_text'] ?? 'Cotizar' }}
                @endif
              </button>
              @endif
            </div>
          </div>
        </article>
      </div>
      @endforeach
    </div>
  </section>

  {{-- TESTIMONIALS --}}
  <section style="margin-top:56px">
    <div class="sect-head" style="margin-bottom:24px">
      <div>
        <h2 class="sect-title">Lo que dicen nuestros clientes</h2>
        <p class="sect-sub">Opiniones reales de compradores verificados.</p>
      </div>
    </div>
    <div class="testimonials-grid">
      @php $reviews = collect($testimonials ?? []); @endphp
      @if($reviews->count() > 0)
        @foreach($reviews as $rv)
        <div class="testi-card">
          <div class="testi-stars">
            @for($s=1;$s<=5;$s++)<span class="{{ $s<=$rv->rating ? 'star-on' : 'star-off' }}">★</span>@endfor
          </div>
          <p class="testi-text">"{{ $rv->comment }}"</p>
          <div class="testi-author">
            <div class="testi-avatar">{{ strtoupper(substr($rv->reviewer_name??'C',0,1)) }}</div>
            <div>
              <div class="testi-name">{{ $rv->reviewer_name ?? 'Cliente verificado' }}</div>
              <div class="testi-date">Compra verificada</div>
            </div>
          </div>
        </div>
        @endforeach
      @else
      {{-- Testimonios placeholder cuando no hay reviews --}}
      @php $staticReviews = [
        ['name'=>'Carlos M.','rating'=>5,'text'=>'Excelente atención y muy rápido el despacho. Llegó al día siguiente en perfectas condiciones.'],
        ['name'=>'Ana R.','rating'=>5,'text'=>'Productos de muy buena calidad, justo como en las fotos. Volvería a comprar sin dudarlo.'],
        ['name'=>'Luis P.','rating'=>4,'text'=>'Buena experiencia de compra. El precio es muy competitivo y el envío llegó en el tiempo prometido.'],
      ]; @endphp
      @foreach($staticReviews as $rv)
      <div class="testi-card">
        <div class="testi-stars">
          @for($s=1;$s<=5;$s++)<span class="{{ $s<=$rv['rating'] ? 'star-on' : 'star-off' }}">★</span>@endfor
        </div>
        <p class="testi-text">"{{ $rv['text'] }}"</p>
        <div class="testi-author">
          <div class="testi-avatar">{{ strtoupper(substr($rv['name'],0,1)) }}</div>
          <div>
            <div class="testi-name">{{ $rv['name'] }}</div>
            <div class="testi-date">Compra verificada</div>
          </div>
        </div>
      </div>
      @endforeach
      @endif
    </div>
  </section>

  {{-- ALL PRODUCTS GRID --}}
  <section style="margin-top:48px">
    <div class="sect-head">
      <div>
        <h2 class="sect-title">Todo el catálogo</h2>
      </div>
      <a class="more" @click="page='catalog'">Ver catálogo completo</a>
    </div>
    @php
      $homeGridProducts = collect();
      foreach($categories as $_hc) {
        $homeGridProducts = $homeGridProducts->merge($_hc->products->map(function($p) use($_hc){ $p->_catLabel = $_hc->name; return $p; }));
        foreach($_hc->children as $_hs) {
          $homeGridProducts = $homeGridProducts->merge($_hs->products->map(function($p) use($_hs){ $p->_catLabel = $_hs->name; return $p; }));
        }
      }
      $homeGridProducts = $homeGridProducts->take(8);
    @endphp
    <div class="grid-products">
      @foreach($homeGridProducts as $product)
      @php $pImg = $product->mainImage ? $product->main_image_url : null; $pPct = $product->compare_price ? round((1-$product->price/$product->compare_price)*100) : 0; $pCatLabel = $product->_catLabel ?? ''; @endphp
      <article class="card" @click="openProduct({{ $product->id }})">
        <div class="card-media">
          @if($pImg)<img src="{{ $pImg }}" alt="{{ $product->name }}" loading="lazy">
          @else<div class="card-media-placeholder">📦</div>@endif
          <div class="card-badges">
            @if($pPct > 0)<span class="badge badge-sale">-{{ $pPct }}%</span>@endif
            @if($product->stock == 0)<span class="badge" style="background:rgba(0,0,0,.5);color:white">Agotado</span>@endif
          </div>
          @if(!$isQuoteOnly && $product->stock != 0)
          <div class="card-quick-view">
            <button class="btn" @click.stop="openQuickView({id:{{ $product->id }},name:'{{ addslashes($product->name) }}',price:{{ $product->price }},img:'{{ $pImg }}'})">👁 Vista rápida</button>
          </div>
          @endif
        </div>
        <div class="card-body">
          @if($pCatLabel)<div class="card-cat">{{ $pCatLabel }}</div>@endif
          <div class="card-name">{{ $product->name }}</div>
          <div class="card-price-row">
            <span class="price-now">{{ $currency }} {{ number_format($product->price,2) }}</span>
            @if($product->compare_price)<span class="price-was">{{ $currency }} {{ number_format($product->compare_price,2) }}</span>@endif
            @if($pPct > 0)<span class="price-pct">-{{ $pPct }}%</span>@endif
          </div>
          @if($product->stock != 0)
          <div class="card-add">
            <button class="btn" @click.stop="{{ $isQuoteOnly ? 'openQuotePopup('.$product->id.')' : 'addToCart('.$product->id.')' }}">
              @if(!$isQuoteOnly)🛒 {{ $settings['btn_cart_text'] ?? 'Agregar al carrito' }}@else{{ $settings['btn_quote_text'] ?? 'Cotizar' }}@endif
            </button>
          </div>
          @endif
        </div>
      </article>
      @endforeach
    </div>
  </section>

</main>

{{-- ═══════════════════════════════════════════════
     CATALOG PAGE
═══════════════════════════════════════════════ --}}
<main x-show="page==='catalog'" class="page page-pad fade-in">
  <nav class="breadcrumbs">
    <a href="#" @click.prevent="page='home'" style="cursor:pointer">Inicio</a>
    <span>/</span>
    <span style="color:var(--text-primary)" x-text="activeFilterLabel"></span>
  </nav>
  <h1 class="h1" style="margin-top:8px" x-text="activeFilterLabel"></h1>

  <div class="plp-layout" style="margin-top:24px">

    {{-- ── SIDEBAR FILTERS ── --}}
    <aside class="filters">
      <div class="filters-header">
        <h4>Filtros</h4>
        <button class="filters-clear" :class="{show: hasActiveFilters}" @click="clearAllFilters()">Limpiar todo</button>
      </div>

      {{-- CATEGORÍAS --}}
      <details class="fg" open>
        <summary class="fg-summary">
          Categoría
          <template x-if="filterCat">
            <span class="fg-badge">1</span>
          </template>
          <svg class="fg-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="fg-body">
          {{-- Buscador de categoría (aparece si hay ≥6 categorías) --}}
          @if($categories->count() >= 6)
          <input class="fg-search" type="text" placeholder="Buscar categoría…" x-model="catSearch" @input="catSearchOpen={}">
          @endif

          {{-- Todos --}}
          <label class="filter-check" :class="{active: !filterCat}">
            <input class="fck" type="radio" name="fc" value="" x-model="filterCat" @change="filterSubCat=null">
            <span class="filter-label">Todos los productos</span>
            <span class="filter-count">{{ $categories->sum(fn($c) => $c->products->count() + $c->children->sum(fn($s)=>$s->products->count())) }}</span>
          </label>

          @foreach($categories as $cat)
          @php
            $catTotal = $cat->products->count() + $cat->children->sum(fn($s)=>$s->products->count());
            $hasSubs  = $cat->children->count() > 0;
            $subLimit = 4;
          @endphp
          {{-- hide if doesn't match search --}}
          <div x-show="!catSearch || '{{ strtolower($cat->name) }}'.includes(catSearch.toLowerCase())">

            {{-- Categoría padre --}}
            <label class="filter-check" :class="{'active': filterCat==='{{ $cat->id }}'}">
              <input class="fck" type="radio" name="fc" value="{{ $cat->id }}" x-model="filterCat"
                @change="filterCat==='{{ $cat->id }}' ? (catOpen['{{ $cat->id }}']=true) : null; filterSubCat=null">
              <span class="filter-label">{{ $cat->name }}</span>
              <span class="filter-count">{{ $catTotal }}</span>
            </label>

            @if($hasSubs)
            {{-- Subcategorías: visibles si el padre está seleccionado o el grupo está abierto --}}
            <div x-show="filterCat==='{{ $cat->id }}' || catOpen['{{ $cat->id }}']" style="margin-left:2px;border-left:2px solid var(--border);padding-left:6px;margin-bottom:4px">
              @foreach($cat->children->take($subLimit) as $sub)
              <label class="filter-check sub" :class="{'active-sub': filterSubCat==='{{ $sub->id }}'}">
                <input class="fck" type="radio" name="fcs" value="{{ $sub->id }}" x-model="filterSubCat"
                  @change="filterCat='{{ $cat->id }}'">
                <span class="filter-label">{{ $sub->name }}</span>
                <span class="filter-count">{{ $sub->products->count() }}</span>
              </label>
              @endforeach
              @if($cat->children->count() > $subLimit)
              {{-- Ver más / ver menos --}}
              <div x-show="!subExpanded['{{ $cat->id }}']">
                <button class="fg-more" @click.prevent="subExpanded['{{ $cat->id }}']=true">
                  + {{ $cat->children->count() - $subLimit }} más
                </button>
              </div>
              <div x-show="subExpanded['{{ $cat->id }}']">
                @foreach($cat->children->skip($subLimit) as $sub)
                <label class="filter-check sub" :class="{'active-sub': filterSubCat==='{{ $sub->id }}'}">
                  <input class="fck" type="radio" name="fcs" value="{{ $sub->id }}" x-model="filterSubCat"
                    @change="filterCat='{{ $cat->id }}'">
                  <span class="filter-label">{{ $sub->name }}</span>
                  <span class="filter-count">{{ $sub->products->count() }}</span>
                </label>
                @endforeach
                <button class="fg-more" @click.prevent="subExpanded['{{ $cat->id }}']=false">
                  − Ver menos
                </button>
              </div>
              @endif
            </div>
            @endif
          </div>
          @endforeach
        </div>
      </details>

      {{-- PRECIO --}}
      <details class="fg" open>
        <summary class="fg-summary">
          Precio
          <template x-if="priceMin>0 || priceMax<maxPrice">
            <span class="fg-badge">1</span>
          </template>
          <svg class="fg-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="fg-body">
          <div class="price-range-wrap">
            <div class="price-inputs">
              <div class="price-input-box">
                <label>Mínimo</label>
                <input type="number" x-model.number="priceMin" :min="0" :max="priceMax-1" @change="priceMin=Math.min(priceMin,priceMax-1)" placeholder="0">
              </div>
              <div class="price-input-box">
                <label>Máximo</label>
                <input type="number" x-model.number="priceMax" :min="priceMin+1" :max="maxPrice" @change="priceMax=Math.max(priceMax,priceMin+1)" :placeholder="maxPrice">
              </div>
            </div>
            <div class="range-track">
              <div class="range-fill" :style="`left:${priceMin/maxPrice*100}%;right:${(1-priceMax/maxPrice)*100}%`"></div>
              <input class="range-input" type="range" :min="0" :max="maxPrice" x-model.number="priceMin"
                @input="priceMin=Math.min(priceMin,priceMax-1)">
              <input class="range-input" type="range" :min="0" :max="maxPrice" x-model.number="priceMax"
                @input="priceMax=Math.max(priceMax,priceMin+1)">
            </div>
          </div>
        </div>
      </details>

      {{-- DISPONIBILIDAD --}}
      <details class="fg" open>
        <summary class="fg-summary">
          Disponibilidad
          <template x-if="filterInStock">
            <span class="fg-badge">1</span>
          </template>
          <svg class="fg-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="fg-body">
          <label class="filter-check">
            <input class="fck" type="checkbox" x-model="filterInStock">
            <span class="filter-label">Solo en stock</span>
          </label>
          <label class="filter-check">
            <input class="fck" type="checkbox" x-model="filterOnSale">
            <span class="filter-label">Solo en oferta</span>
          </label>
        </div>
      </details>

    </aside>

    {{-- ── PRODUCT GRID AREA ── --}}
    <div>

      {{-- CHIPS BAR --}}
      <div class="chips-bar" x-show="hasActiveFilters">
        <template x-if="filterCat">
          <span class="chip" @click="filterCat=null;filterSubCat=null">
            <span x-text="allCats.find(c=>c.id==filterCat)?.name||'Categoría'"></span>
            <span class="chip-x">✕</span>
          </span>
        </template>
        <template x-if="filterSubCat">
          <span class="chip" @click="filterSubCat=null">
            <span x-text="allCats.find(c=>c.id==filterSubCat)?.name||'Subcategoría'"></span>
            <span class="chip-x">✕</span>
          </span>
        </template>
        <template x-if="filterInStock">
          <span class="chip" @click="filterInStock=false">
            En stock <span class="chip-x">✕</span>
          </span>
        </template>
        <template x-if="filterOnSale">
          <span class="chip" @click="filterOnSale=false">
            En oferta <span class="chip-x">✕</span>
          </span>
        </template>
        <template x-if="priceMin>0 || priceMax<maxPrice">
          <span class="chip" @click="priceMin=0;priceMax=maxPrice">
            <span x-text="fmt(priceMin)+' – '+fmt(priceMax)"></span>
            <span class="chip-x">✕</span>
          </span>
        </template>
        <span class="chip chip-clear" @click="clearAllFilters()">Limpiar todo</span>
      </div>

      {{-- HEAD: count + sort --}}
      <div class="plp-head">
        <div class="plp-count">
          <strong x-text="catalogProducts.length"></strong>
          <span x-text="' de '+EC_PRODUCTS.length+' productos'"></span>
        </div>
        <div class="plp-tools">
          <select class="sort-select" x-model="sortBy" aria-label="Ordenar">
            <option value="default">Relevancia</option>
            <option value="price-asc">Precio: menor a mayor</option>
            <option value="price-desc">Precio: mayor a menor</option>
            <option value="name">Nombre A-Z</option>
          </select>
        </div>
      </div>

      {{-- GRID --}}
      <div class="grid-products">
        <template x-for="p in catalogProducts" :key="p.id">
          <article class="card" @click="openProduct(p.id)">
            <div class="card-media">
              <template x-if="p.img"><img :src="p.img" :alt="p.name" loading="lazy"></template>
              <template x-if="!p.img"><div class="card-media-placeholder">📦</div></template>
              <div class="card-badges">
                <template x-if="p.cp && p.cp>p.price">
                  <span class="badge badge-sale" x-text="'-'+Math.round((1-p.price/p.cp)*100)+'%'"></span>
                </template>
                <template x-if="p.stock===0">
                  <span class="badge" style="background:rgba(0,0,0,.5);color:white">Agotado</span>
                </template>
              </div>
              @if(!$isQuoteOnly)
              <div class="card-quick-view" x-show="p.stock!==0">
                <button class="btn" @click.stop="openQuickView(p)">👁 Vista rápida</button>
              </div>
              @endif
            </div>
            <div class="card-body">
              <div class="card-cat" x-text="p.cat"></div>
              <div class="card-name" x-text="p.name"></div>
              <div class="card-price-row">
                <span class="price-now" x-text="fmt(p.price)"></span>
                <template x-if="p.cp && p.cp>p.price">
                  <span class="price-was" x-text="fmt(p.cp)"></span>
                </template>
                <template x-if="p.cp && p.cp>p.price">
                  <span class="price-pct" x-text="'-'+Math.round((1-p.price/p.cp)*100)+'%'"></span>
                </template>
              </div>
            </div>
            <div class="card-add" x-show="p.stock!==0">
              <button class="btn" @click.stop="{{ $isQuoteOnly ? 'openQuotePopup(p.id)' : 'addToCart(p.id)' }}">
                @if(!$isQuoteOnly)🛒 {{ $settings['btn_cart_text'] ?? 'Agregar al carrito' }}@else{{ $settings['btn_quote_text'] ?? 'Cotizar' }}@endif
              </button>
            </div>
          </article>
        </template>
      </div>

      {{-- EMPTY STATE --}}
      <div x-show="catalogProducts.length===0" style="text-align:center;padding:72px 24px;background:var(--bg-surface);border-radius:var(--radius-xl);border:1px solid var(--border);margin-top:8px">
        <div style="font-size:52px;margin-bottom:16px">🔍</div>
        <h3 class="h3" style="margin-bottom:8px">Sin resultados</h3>
        <p style="color:var(--text-secondary);font-size:14px;margin-bottom:8px">No encontramos productos con estos filtros.</p>
        <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-bottom:20px">
          <template x-if="filterCat">
            <button class="btn btn-outline btn-sm" @click="filterCat=null;filterSubCat=null">Quitar categoría</button>
          </template>
          <template x-if="filterInStock">
            <button class="btn btn-outline btn-sm" @click="filterInStock=false">Quitar "En stock"</button>
          </template>
          <template x-if="priceMin>0||priceMax<maxPrice">
            <button class="btn btn-outline btn-sm" @click="priceMin=0;priceMax=maxPrice">Quitar rango precio</button>
          </template>
        </div>
        <button class="btn btn-primary" @click="clearAllFilters()">Ver todos los productos</button>
      </div>
    </div>
  </div>

  {{-- MOBILE FILTER FAB --}}
  <button class="filter-fab" @click="filterDrawerOpen=true">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="14" y2="12"/><line x1="4" y1="18" x2="10" y2="18"/></svg>
    Filtros
    <span class="filter-fab-badge" x-show="activeFilterCount>0" x-text="activeFilterCount"></span>
  </button>

  {{-- MOBILE FILTER DRAWER --}}
  <div class="drawer-backdrop" :class="{show:filterDrawerOpen}" @click="filterDrawerOpen=false"></div>
  <div class="filter-drawer" :class="{open:filterDrawerOpen}">
    <div class="filter-drawer-handle"></div>
    <div class="filter-drawer-head">
      <span style="font-family:var(--font-display);font-weight:700;font-size:16px">Filtros</span>
      <button class="icon-btn" @click="filterDrawerOpen=false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    {{-- mismo contenido del sidebar, inline --}}
    <div style="padding:0 0 8px">

      <details class="fg" open>
        <summary class="fg-summary">Categoría
          <svg class="fg-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="fg-body">
          <label class="filter-check">
            <input class="fck" type="radio" name="fcm" value="" x-model="filterCat" @change="filterSubCat=null">
            <span class="filter-label">Todos</span>
            <span class="filter-count">{{ $categories->sum(fn($c)=>$c->products->count()+$c->children->sum(fn($s)=>$s->products->count())) }}</span>
          </label>
          @foreach($categories as $cat)
          @php $hasSubs = $cat->children->count() > 0; @endphp
          <label class="filter-check">
            <input class="fck" type="radio" name="fcm" value="{{ $cat->id }}" x-model="filterCat" @change="filterSubCat=null">
            <span class="filter-label">{{ $cat->name }}</span>
            <span class="filter-count">{{ $cat->products->count() + $cat->children->sum(fn($s)=>$s->products->count()) }}</span>
          </label>
          @if($hasSubs)
          <div x-show="filterCat==='{{ $cat->id }}'" style="margin-left:2px;border-left:2px solid var(--border);padding-left:6px">
            @foreach($cat->children as $sub)
            <label class="filter-check sub">
              <input class="fck" type="radio" name="fcsm" value="{{ $sub->id }}" x-model="filterSubCat" @change="filterCat='{{ $cat->id }}'">
              <span class="filter-label">{{ $sub->name }}</span>
              <span class="filter-count">{{ $sub->products->count() }}</span>
            </label>
            @endforeach
          </div>
          @endif
          @endforeach
        </div>
      </details>

      <details class="fg" open>
        <summary class="fg-summary">Precio
          <svg class="fg-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="fg-body">
          <div class="price-range-wrap">
            <div class="price-inputs">
              <div class="price-input-box"><label>Mínimo</label><input type="number" x-model.number="priceMin" :min="0" :max="priceMax-1"></div>
              <div class="price-input-box"><label>Máximo</label><input type="number" x-model.number="priceMax" :min="priceMin+1" :max="maxPrice"></div>
            </div>
            <div class="range-track">
              <div class="range-fill" :style="`left:${priceMin/maxPrice*100}%;right:${(1-priceMax/maxPrice)*100}%`"></div>
              <input class="range-input" type="range" :min="0" :max="maxPrice" x-model.number="priceMin" @input="priceMin=Math.min(priceMin,priceMax-1)">
              <input class="range-input" type="range" :min="0" :max="maxPrice" x-model.number="priceMax" @input="priceMax=Math.max(priceMax,priceMin+1)">
            </div>
          </div>
        </div>
      </details>

      <details class="fg">
        <summary class="fg-summary">Disponibilidad
          <svg class="fg-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="fg-body">
          <label class="filter-check"><input class="fck" type="checkbox" x-model="filterInStock"><span class="filter-label">Solo en stock</span></label>
          <label class="filter-check"><input class="fck" type="checkbox" x-model="filterOnSale"><span class="filter-label">Solo en oferta</span></label>
        </div>
      </details>
    </div>

    <div class="filter-drawer-foot">
      <button class="btn btn-ghost btn-block" @click="clearAllFilters()">Limpiar</button>
      <button class="btn btn-primary btn-block" @click="filterDrawerOpen=false">
        Ver <span x-text="catalogProducts.length"></span> productos
      </button>
    </div>
  </div>

</main>

{{-- ═══════════════════════════════════════════════
     CATEGORIES PAGE  (page==='categories')
     Adaptativa: 1-6 → grid simple | 7-20 → grid+search | 21+ → alfa+search
═══════════════════════════════════════════════ --}}
@php
  $totalCats = $categories->count();
  /* densidad del grid según cantidad */
  if ($totalCats <= 3)       $catDensity = 'density-2';
  elseif ($totalCats <= 6)   $catDensity = 'density-3';
  elseif ($totalCats <= 12)  $catDensity = 'density-4';
  else                       $catDensity = 'density-5';

  $showSearch   = $totalCats >= 7;
  $showAlpha    = $totalCats >= 21;
  $showSort     = $totalCats >= 7;

  /* Para la barra alfabética: letras con categorías */
  $usedLetters = $categories->map(fn($c) => strtoupper(substr($c->name,0,1)))->unique()->sort()->values();
@endphp
<main x-show="page==='categories'" class="page page-pad fade-in" x-data="{
  catQ: '',
  catSort: 'default',
  catAlpha: '',
  get filtered() {
    let cats = window.EC_CATS || [];
    if (this.catQ) cats = cats.filter(c => c.name.toLowerCase().includes(this.catQ.toLowerCase()));
    if (this.catAlpha) cats = cats.filter(c => c.name.charAt(0).toUpperCase() === this.catAlpha);
    if (this.catSort === 'az') cats = [...cats].sort((a,b) => a.name.localeCompare(b.name));
    if (this.catSort === 'count') cats = [...cats].sort((a,b) => b.count - a.count);
    return cats;
  }
}">

  {{-- Breadcrumbs --}}
  <nav class="breadcrumbs" aria-label="Navegación">
    <a href="#" @click.prevent="page='home'" style="cursor:pointer">Inicio</a>
    <span>/</span>
    <span style="color:var(--text-primary)">Categorías</span>
  </nav>
  <h1 class="h1" style="margin-top:8px;margin-bottom:28px">
    Todas las categorías
    <span style="font-size:16px;font-weight:400;color:var(--text-muted);margin-left:8px">({{ $totalCats }})</span>
  </h1>

  {{-- Buscador — visible si ≥7 categorías --}}
  @if($showSearch)
  <div class="cat-page-search">
    <input type="search" placeholder="Buscar entre las {{ $totalCats }} categorías…"
      x-model="catQ" aria-label="Buscar categoría" autocomplete="off">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true">
      <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
    </svg>
  </div>
  @endif

  {{-- Barra de herramientas: conteo + orden --}}
  @if($showSort)
  <div class="cat-page-tools">
    <span class="cat-page-count" x-text="filtered.length + ' categorías'"></span>
    <div class="cat-sort" role="group" aria-label="Ordenar categorías">
      <button class="cat-sort-btn" :class="{active:catSort==='default'}" @click="catSort='default'">Relevancia</button>
      <button class="cat-sort-btn" :class="{active:catSort==='az'}" @click="catSort='az'">A → Z</button>
      <button class="cat-sort-btn" :class="{active:catSort==='count'}" @click="catSort='count'">Más productos</button>
    </div>
  </div>
  @endif

  {{-- Barra alfabética — visible si ≥21 categorías --}}
  @if($showAlpha)
  <div class="alpha-bar" role="group" aria-label="Filtrar por letra">
    <button class="alpha-btn" :class="{active:catAlpha===''}" @click="catAlpha=''" aria-label="Todas">Todas</button>
    @foreach($usedLetters as $letter)
    <button class="alpha-btn" :class="{active:catAlpha==='{{ $letter }}'}"
      @click="catAlpha=catAlpha==='{{ $letter }}' ? '' : '{{ $letter }}'"
      aria-label="Letra {{ $letter }}">{{ $letter }}</button>
    @endforeach
  </div>
  @endif

  {{-- Grid reactivo (Alpine): funciona para TODOS los casos --}}
  {{-- x-show oculta el grupo alfa estático cuando hay búsqueda/orden activo --}}

  @if($showAlpha)
  {{-- Grupos alfabéticos estáticos (PHP render, visibles solo en modo default) --}}
  <div x-show="!catQ && !catAlpha && catSort==='default'">
    @foreach($usedLetters as $letter)
    @php $letterCats = $categories->filter(fn($c) => strtoupper(substr($c->name,0,1)) === $letter); @endphp
    @if($letterCats->count())
    <div class="cat-alpha-section">
      <div style="font-family:var(--font-display);font-size:22px;font-weight:700;color:var(--text-muted);padding:12px 0 8px;border-bottom:2px solid var(--border);margin-bottom:10px">{{ $letter }}</div>
      <div class="cat-page-grid {{ $catDensity }}">
        @foreach($letterCats as $cat)
        @php
          $catTotal  = $cat->products->count() + $cat->children->sum(fn($s) => $s->products->count());
          $hasSubs   = $cat->children->count() > 0;
          $subNames  = $cat->children->take(3)->pluck('name');
          $extraSubs = max(0, $cat->children->count() - 3);
        @endphp
        <div class="cat-card" role="button" tabindex="0" aria-label="{{ $cat->name }}"
          @click="$dispatch('ec-filter-cat', {catId: '{{ $cat->id }}'})"
          @keydown.enter="$dispatch('ec-filter-cat', {catId: '{{ $cat->id }}'})">
          <div class="cat-card-icon">{{ $cat->icon ?? '📦' }}</div>
          <div class="cat-card-info">
            <div class="cat-card-name">{{ $cat->name }}</div>
            <div class="cat-card-meta">{{ $catTotal }} productos{{ $hasSubs ? ' · '.$cat->children->count().' subcategorías' : '' }}</div>
            @if($hasSubs)
            <div class="cat-card-subs">
              @foreach($subNames as $sn)<span class="cat-card-sub">{{ $sn }}</span>@endforeach
              @if($extraSubs > 0)<span class="cat-card-sub">+{{ $extraSubs }} más</span>@endif
            </div>
            @endif
          </div>
        </div>
        @endforeach
      </div>
    </div>
    @endif
    @endforeach
  </div>
  @endif

  {{-- Grid reactivo Alpine: activo cuando hay búsqueda, filtro alfa o sort --}}
  <div x-show="{{ $showAlpha ? '(catQ || catAlpha || catSort!==\'default\')' : 'true' }}">
    <div class="cat-page-grid {{ $catDensity }}">
      <template x-for="cat in filtered" :key="cat.id">
        <div class="cat-card" role="button" tabindex="0" :aria-label="cat.name"
          @click="$dispatch('ec-filter-cat', {catId: cat.id})"
          @keydown.enter="$dispatch('ec-filter-cat', {catId: cat.id})">
          <div class="cat-card-icon" x-text="cat.icon"></div>
          <div class="cat-card-info">
            <div class="cat-card-name" x-text="cat.name"></div>
            <div class="cat-card-meta" x-text="cat.count + ' productos' + (cat.subs > 0 ? ' · ' + cat.subs + ' subcategorías' : '')"></div>
            <div class="cat-card-subs" x-show="cat.subNames && cat.subNames.length > 0">
              <template x-for="s in (cat.subNames || []).slice(0,3)" :key="s">
                <span class="cat-card-sub" x-text="s"></span>
              </template>
              <span class="cat-card-sub" x-show="cat.subs > 3" x-text="'+' + (cat.subs - 3) + ' más'"></span>
            </div>
          </div>
        </div>
      </template>
    </div>
    <div class="cat-empty" x-show="filtered.length === 0" style="grid-column:1/-1">
      <div class="cat-empty-icon">🔍</div>
      <div class="cat-empty-title">Sin resultados para "<span x-text="catQ || catAlpha"></span>"</div>
      <p style="margin-top:4px;color:var(--text-muted)">Prueba con otra letra o palabra clave.</p>
      <button class="btn btn-outline" style="margin-top:16px" @click="catQ='';catAlpha=''">Ver todas las categorías</button>
    </div>
  </div>

</main>

{{-- ═══════════════════════════════════════════════
     PRODUCT DETAIL PAGE
═══════════════════════════════════════════════ --}}
<main x-show="page==='product'" class="page fade-in" style="padding-bottom:90px">
  {{-- Breadcrumb / Volver --}}
  <div style="padding:12px var(--page-pad) 0;display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--text-muted)">
    <a href="#" @click.prevent="page='home'" style="cursor:pointer">Inicio</a>
    <span>/</span>
    <a href="#" @click.prevent="page=prevPage||'catalog';window.scrollTo({top:0,behavior:'smooth'})" style="cursor:pointer">← Volver</a>
    <span>/</span>
    <span style="color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px" x-text="pdp?.name"></span>
  </div>

  <template x-if="pdp">
    <div x-data="{pdpQty:1}">

      {{-- ── GRID PRINCIPAL ── --}}
      <div class="pdp-wrap" style="display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:20px;padding:0 var(--page-pad)">

        {{-- IMAGEN --}}
        <div class="pdp-gallery-col">
          <div class="pdp-img-wrap" @click="pdp.img && window.openLightbox([pdp.img], 0)">
            <template x-if="pdp.img">
              <img :src="pdp.img" :alt="pdp.name" class="pdp-img-main">
            </template>
            <template x-if="!pdp.img">
              <div style="width:100%;height:100%;display:grid;place-items:center;font-size:96px;background:linear-gradient(145deg,var(--bg-inset),var(--bg-elev))">📦</div>
            </template>
            <template x-if="pdp.cp && pdp.cp>pdp.price">
              <span class="badge badge-sale" style="position:absolute;top:12px;left:12px;font-size:12px;padding:5px 10px" x-text="'-'+Math.round((1-pdp.price/pdp.cp)*100)+'%'"></span>
            </template>
            <span class="pdp-zoom-btn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
            </span>
          </div>
          {{-- Compartir --}}
          <div style="display:flex;gap:8px;margin-top:12px;justify-content:center">
            <span style="font-size:12px;color:var(--text-muted);align-self:center">Compartir:</span>
            <a :href="'https://wa.me/?text='+encodeURIComponent(pdp.name+' - '+window.location.href)" target="_blank" rel="noopener" style="width:34px;height:34px;border-radius:50%;background:#25d366;display:grid;place-items:center" title="WhatsApp">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2z"/></svg>
            </a>
            <a :href="'https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(window.location.href)" target="_blank" rel="noopener" style="width:34px;height:34px;border-radius:50%;background:#1877f2;display:grid;place-items:center" title="Facebook">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
            </a>
            <button @click="navigator.clipboard&&navigator.clipboard.writeText(window.location.href).then(()=>showToast('🔗 Link copiado'))" style="width:34px;height:34px;border-radius:50%;background:var(--bg-inset);border:1px solid var(--border);display:grid;place-items:center;cursor:pointer" title="Copiar link">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
            </button>
          </div>
        </div>

        {{-- INFO --}}
        <div class="pdp-info-col" style="padding-top:4px">
          <span class="eyebrow" x-text="pdp.cat" style="cursor:pointer;color:var(--primary)" @click="page='catalog';filterCat=pdp.catId"></span>
          <h1 class="pdp-title" x-text="pdp.name"></h1>

          {{-- Precio --}}
          <div style="display:flex;align-items:baseline;gap:12px;margin:12px 0 4px;flex-wrap:wrap">
            <span class="pdp-price" x-text="fmt(pdp.price)"></span>
            <template x-if="pdp.cp && pdp.cp>pdp.price">
              <span style="font-size:16px;color:var(--text-muted);text-decoration:line-through" x-text="fmt(pdp.cp)"></span>
            </template>
          </div>
          <template x-if="pdp.cp && pdp.cp>pdp.price">
            <p style="font-size:13px;color:var(--accent);font-weight:600;margin:0 0 12px">Ahorras <span x-text="fmt(pdp.cp-pdp.price)"></span></p>
          </template>

          {{-- Stock badge --}}
          <template x-if="pdp.stock===0">
            <div style="padding:10px 14px;background:color-mix(in srgb,var(--danger) 8%,transparent);border:1px solid color-mix(in srgb,var(--danger) 20%,transparent);border-radius:var(--radius-md);color:var(--danger);font-weight:600;margin-bottom:14px;font-size:13px">⚠️ Producto agotado temporalmente</div>
          </template>
          <template x-if="pdp.stock!==null && pdp.stock!==undefined && pdp.stock>0 && pdp.stock<=10">
            <div style="padding:8px 14px;background:color-mix(in srgb,var(--warn) 10%,transparent);border:1px solid color-mix(in srgb,var(--warn) 25%,transparent);border-radius:var(--radius-md);color:#92400e;font-size:12.5px;margin-bottom:12px">⚡ ¡Solo quedan <strong x-text="pdp.stock"></strong> unidades!</div>
          </template>

          {{-- Selector de cantidad --}}
          <div x-show="pdp.stock!==0" style="margin-bottom:14px">
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:6px;font-weight:500">Cantidad</div>
            <div style="display:inline-flex;align-items:center;border:1.5px solid var(--border);border-radius:var(--radius-md);overflow:hidden">
              <button @click="pdpQty=Math.max(1,pdpQty-1)" style="width:40px;height:40px;border:none;background:var(--bg-inset);cursor:pointer;font-size:18px;display:grid;place-items:center" :disabled="pdpQty<=1">−</button>
              <span x-text="pdpQty" style="min-width:44px;text-align:center;font-weight:600;font-size:15px"></span>
              <button @click="pdpQty=Math.min(pdp.stock||99,pdpQty+1)" style="width:40px;height:40px;border:none;background:var(--bg-inset);cursor:pointer;font-size:18px;display:grid;place-items:center">+</button>
            </div>
            <template x-if="pdp.stock!==null && pdp.stock!==undefined && pdp.stock>0">
              <span style="font-size:12px;color:var(--text-muted);margin-left:10px" x-text="'Stock: '+pdp.stock+' disponibles'"></span>
            </template>
          </div>

          {{-- Botones desktop --}}
          <div class="pdp-actions-desktop" x-show="pdp.stock!==0">
            <button class="btn btn-primary btn-lg btn-block" style="margin-bottom:10px" @click="addToCartQty(pdp.id, pdpQty)">
              @if(!$isQuoteOnly)
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
              {{ $settings['btn_cart_text'] ?? 'Agregar al carrito' }}
              @else
              {{ $settings['btn_quote_text'] ?? 'Cotizar' }}
              @endif
            </button>
            @if(!$isQuoteOnly)
            <button class="btn btn-ghost btn-lg btn-block" style="margin-bottom:10px" @click="addToCartQty(pdp.id, pdpQty); checkoutOpen=true">
              Comprar ahora →
            </button>
            @endif
            @if($quoteWa)
            <a class="btn btn-outline btn-lg btn-block" :href="'https://wa.me/{{ $quoteWa }}?text='+encodeURIComponent('Hola, quiero '+pdpQty+'x '+pdp.name+' ('+fmt(pdp.price*pdpQty)+')')" target="_blank" rel="noopener">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="color:#25d366"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2zm5.6 14.2c-.2.6-1.2 1.1-1.7 1.2-.4 0-1 .1-1.6-.1-.4-.1-.9-.3-1.6-.6-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.4-1.1-2.6 0-1.3.6-1.9.9-2.1.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4l.8 1.8c.1.2.1.4 0 .6l-.3.4-.3.4c-.1.1-.2.3-.1.5.1.2.6 1 1.3 1.6.9.8 1.7 1 1.9 1.1.2.1.3.1.5-.1l.7-.8c.2-.2.4-.2.6-.1l1.6.8c.2.1.3.2.4.3 0 .1 0 .8-.2 1.4z"/></svg>
              Consultar por WhatsApp
            </a>
            @endif
          </div>

          {{-- Trust badges --}}
          <div style="border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-top:20px">
            <div style="display:grid;grid-template-columns:1fr 1fr;font-size:13px">
              <div style="padding:10px 14px;display:flex;align-items:center;gap:8px;border-right:1px solid var(--border);border-bottom:1px solid var(--border)"><span style="font-size:16px">{{ $trustIcon1 }}</span><span>{{ $trustText1 }}</span></div>
              <div style="padding:10px 14px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border)"><span style="font-size:16px">{{ $trustIcon2 }}</span><span>{{ $trustText2 }}</span></div>
              <div style="padding:10px 14px;display:flex;align-items:center;gap:8px;border-right:1px solid var(--border)"><span style="font-size:16px">{{ $trustIcon3 }}</span><span>{{ $trustText3 }}</span></div>
              <div style="padding:10px 14px;display:flex;align-items:center;gap:8px"><span style="font-size:16px">{{ $trustIcon4 }}</span><span>{{ $trustText4 }}</span></div>
            </div>
          </div>
        </div>
      </div>{{-- /pdp-wrap --}}

    </div>{{-- /x-data pdpQty --}}
  </template>

  {{-- ── PRODUCTOS RELACIONADOS (fuera del x-if pdp para evitar anidamiento) ── --}}
  <div x-show="page==='product' && relatedProducts.length > 0" style="padding:40px var(--page-pad) 20px;display:none" :style="page==='product' && relatedProducts.length ? 'display:block' : 'display:none'">
    <h2 style="font-family:var(--font-display);font-size:20px;font-weight:700;margin:0 0 20px">También te puede interesar</h2>
    <div class="pdp-related-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px">
      <template x-for="rp in relatedProducts" :key="rp.id">
        <article class="pdp-rel-card" @click="openProduct(rp.id);window.scrollTo({top:0,behavior:'smooth'})">
          <div style="aspect-ratio:1/1;overflow:hidden;background:var(--bg-inset);position:relative">
            <img x-show="rp.img" :src="rp.img" :alt="rp.name" loading="lazy" style="width:100%;height:100%;object-fit:cover">
            <div x-show="!rp.img" style="width:100%;height:100%;display:grid;place-items:center;font-size:48px">📦</div>
            <span x-show="rp.cp && rp.cp>rp.price" class="badge badge-sale" style="position:absolute;top:8px;left:8px;font-size:11px;padding:3px 7px" x-text="rp.cp ? '-'+Math.round((1-rp.price/rp.cp)*100)+'%' : ''"></span>
          </div>
          <div style="padding:10px 12px">
            <div style="font-size:11px;color:var(--text-muted);margin-bottom:2px" x-text="rp.cat"></div>
            <div style="font-size:13.5px;font-weight:600;line-height:1.3;margin-bottom:6px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical" x-text="rp.name"></div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
              <span style="font-size:15px;font-weight:700;color:var(--primary)" x-text="fmt(rp.price)"></span>
              <span x-show="rp.cp && rp.cp>rp.price" style="font-size:12px;color:var(--text-muted);text-decoration:line-through" x-text="rp.cp ? fmt(rp.cp) : ''"></span>
            </div>
            @if(!$isQuoteOnly)
            <button class="btn btn-primary btn-sm btn-block" style="margin-top:8px" @click.stop="addToCart(rp.id);showToast('✓ '+rp.name+' agregado')">+ Agregar</button>
            @endif
          </div>
        </article>
      </template>
    </div>
  </div>

  {{-- Botones de acción FIJOS en bottom para MÓVIL --}}
  <template x-if="pdp && pdp.stock!==0">
    <div class="pdp-actions-mobile">
      @if($quoteWa)
      <a class="btn btn-outline" style="flex:1;gap:6px" :href="'https://wa.me/{{ $quoteWa }}?text='+encodeURIComponent('Hola, me interesa: '+pdp.name+' ('+fmt(pdp.price)+')')" target="_blank" rel="noopener">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="color:#25d366;flex-shrink:0"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2zm5.6 14.2c-.2.6-1.2 1.1-1.7 1.2-.4 0-1 .1-1.6-.1-.4-.1-.9-.3-1.6-.6-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.4-1.1-2.6 0-1.3.6-1.9.9-2.1.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4l.8 1.8c.1.2.1.4 0 .6l-.3.4-.3.4c-.1.1-.2.3-.1.5.1.2.6 1 1.3 1.6.9.8 1.7 1 1.9 1.1.2.1.3.1.5-.1l.7-.8c.2-.2.4-.2.6-.1l1.6.8c.2.1.3.2.4.3 0 .1 0 .8-.2 1.4z"/></svg>
        WhatsApp
      </a>
      @endif
      <button class="btn btn-primary" style="flex:2;gap:6px" @click="addToCart(pdp.id);showToast('✓ '+pdp.name+' agregado')">
        @if(!$isQuoteOnly)
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
        {{ $settings['btn_cart_text'] ?? 'Agregar al carrito' }}
        @else
        {{ $settings['btn_quote_text'] ?? 'Cotizar' }}
        @endif
      </button>
    </div>
  </template>
</main>

{{-- ═══ QUICK VIEW MODAL ═══ --}}
<div id="qv-modal" @click.self="closeQuickView()">
  <div id="qv-box">
    <div style="display:flex;gap:14px;padding:18px 18px 14px;align-items:flex-start">
      <img id="qv-img" src="" alt="" x-show="false">
      <div id="qv-img-placeholder">📦</div>
      <div style="flex:1;min-width:0">
        <div id="qv-cat" style="font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--primary);margin-bottom:4px"></div>
        <div id="qv-name" style="font-family:var(--font-display);font-size:17px;font-weight:700;line-height:1.3;margin-bottom:8px"></div>
        <div style="display:flex;align-items:baseline;gap:10px">
          <span id="qv-price" style="font-size:20px;font-weight:700;color:var(--primary)"></span>
          <span id="qv-compare" style="font-size:14px;color:var(--text-muted);text-decoration:line-through;display:none"></span>
        </div>
        <div id="qv-stock-low" style="display:none;font-size:12px;color:#92400e;margin-top:4px"></div>
      </div>
      <button onclick="closeQuickView()" style="background:var(--bg-inset);border:none;width:30px;height:30px;border-radius:50%;cursor:pointer;display:grid;place-items:center;font-size:16px;flex-shrink:0;color:var(--text-secondary)">✕</button>
    </div>
    <div style="padding:0 18px 18px;display:flex;flex-direction:column;gap:8px">
      <div id="qv-stock-out" style="display:none;padding:8px 12px;background:color-mix(in srgb,var(--danger) 8%,transparent);border:1px solid color-mix(in srgb,var(--danger) 20%,transparent);border-radius:8px;color:var(--danger);font-size:13px;font-weight:600;text-align:center">⚠️ Producto agotado temporalmente</div>
      <button id="qv-btn-add" onclick="{{ $isQuoteOnly ? 'qvCotizar()' : 'qvAddToCart()' }}"
              style="width:100%;padding:13px;background:var(--primary);color:var(--primary-ink);border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px">
        @if(!$isQuoteOnly)
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
        + {{ $settings['btn_cart_text'] ?? 'Agregar al carrito' }}
        @else
        {{ $settings['btn_quote_text'] ?? 'Cotizar' }}
        @endif
      </button>
      <button id="qv-btn-view" style="width:100%;padding:12px;background:transparent;border:1.5px solid var(--border-strong);border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;color:var(--text-primary)">Ver producto completo</button>
    </div>
  </div>
</div>

{{-- ═══ LIGHTBOX ═══ --}}
<div id="lb" aria-modal="true" role="dialog" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,.92);touch-action:none">
  {{-- controles --}}
  <button id="lb-close" aria-label="Cerrar" style="position:absolute;top:14px;right:14px;z-index:2;background:rgba(255,255,255,.15);border:none;color:#fff;width:44px;height:44px;border-radius:50%;font-size:22px;cursor:pointer;display:grid;place-items:center;backdrop-filter:blur(4px)">✕</button>
  <button id="lb-prev" aria-label="Anterior" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);z-index:2;background:rgba(255,255,255,.15);border:none;color:#fff;width:44px;height:44px;border-radius:50%;font-size:22px;cursor:pointer;display:none;place-items:center;backdrop-filter:blur(4px)">‹</button>
  <button id="lb-next" aria-label="Siguiente" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);z-index:2;background:rgba(255,255,255,.15);border:none;color:#fff;width:44px;height:44px;border-radius:50%;font-size:22px;cursor:pointer;display:none;place-items:center;backdrop-filter:blur(4px)">›</button>
  {{-- contador --}}
  <div id="lb-counter" style="position:absolute;bottom:16px;left:50%;transform:translateX(-50%);color:rgba(255,255,255,.6);font-size:13px;z-index:2;display:none"></div>
  {{-- zoom controls --}}
  <div style="position:absolute;bottom:16px;right:16px;z-index:2;display:flex;gap:8px">
    <button id="lb-zoom-in"  aria-label="Acercar"  style="background:rgba(255,255,255,.15);border:none;color:#fff;width:40px;height:40px;border-radius:50%;font-size:20px;cursor:pointer;display:grid;place-items:center;backdrop-filter:blur(4px)">+</button>
    <button id="lb-zoom-out" aria-label="Alejar"   style="background:rgba(255,255,255,.15);border:none;color:#fff;width:40px;height:40px;border-radius:50%;font-size:20px;cursor:pointer;display:grid;place-items:center;backdrop-filter:blur(4px)">−</button>
    <button id="lb-zoom-rst" aria-label="Restablecer" style="background:rgba(255,255,255,.15);border:none;color:#fff;width:40px;height:40px;border-radius:50%;font-size:13px;cursor:pointer;display:grid;place-items:center;backdrop-filter:blur(4px)">1:1</button>
  </div>
  {{-- imagen --}}
  <div id="lb-stage" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;overflow:hidden">
    <img id="lb-img" src="" alt="" draggable="false" style="max-width:90vw;max-height:88vh;object-fit:contain;border-radius:6px;transform-origin:center center;transition:transform .15s;user-select:none;cursor:grab">
  </div>
</div>

<script>
(function(){
  var lb=document.getElementById('lb'),
      img=document.getElementById('lb-img'),
      prev=document.getElementById('lb-prev'),
      next=document.getElementById('lb-next'),
      counter=document.getElementById('lb-counter');

  var imgs=[], idx=0, scale=1, panX=0, panY=0;

  // ── Abrir ──────────────────────────────────────────
  function open(srcs, start){
    imgs = Array.isArray(srcs) ? srcs : [srcs];
    idx  = start || 0;
    resetZoom();
    show();
    document.body.style.overflow='hidden';
    img.focus && img.focus();
  }

  function show(){
    img.src = imgs[idx];
    var multi = imgs.length > 1;
    prev.style.display = multi ? 'grid' : 'none';
    next.style.display = multi ? 'grid' : 'none';
    counter.style.display = multi ? 'block' : 'none';
    if(multi) counter.textContent = (idx+1)+' / '+imgs.length;
    lb.style.display='block';
  }

  function close(){
    lb.style.display='none';
    img.src='';
    document.body.style.overflow='';
    imgs=[]; resetZoom();
  }

  // ── Zoom ──────────────────────────────────────────
  function resetZoom(){ scale=1; panX=0; panY=0; applyTransform(); }
  function applyTransform(){ img.style.transform='scale('+scale+') translate('+panX/scale+'px,'+panY/scale+'px)'; }
  function clampPan(){
    var maxX = Math.max(0,(img.naturalWidth*scale - window.innerWidth)/2);
    var maxY = Math.max(0,(img.naturalHeight*scale - window.innerHeight)/2);
    panX = Math.max(-maxX, Math.min(maxX, panX));
    panY = Math.max(-maxY, Math.min(maxY, panY));
  }
  function zoomBy(delta, cx, cy){
    var ns = Math.max(1, Math.min(5, scale * delta));
    var ratio = ns/scale;
    panX = (panX - (cx||0)) * ratio + (cx||0);
    panY = (panY - (cy||0)) * ratio + (cy||0);
    scale = ns;
    clampPan();
    applyTransform();
    img.style.cursor = scale>1 ? 'move' : 'grab';
  }

  document.getElementById('lb-zoom-in') .addEventListener('click',function(){ zoomBy(1.4,0,0); });
  document.getElementById('lb-zoom-out').addEventListener('click',function(){ zoomBy(0.7,0,0); });
  document.getElementById('lb-zoom-rst').addEventListener('click',resetZoom);
  document.getElementById('lb-close').addEventListener('click',close);
  lb.addEventListener('click',function(e){ if(e.target===lb||e.target===document.getElementById('lb-stage')) close(); });

  // ── Navegación ────────────────────────────────────
  prev.addEventListener('click',function(){ idx=(idx-1+imgs.length)%imgs.length; resetZoom(); show(); });
  next.addEventListener('click',function(){ idx=(idx+1)%imgs.length;             resetZoom(); show(); });

  // ── Teclado ───────────────────────────────────────
  document.addEventListener('keydown',function(e){
    if(lb.style.display==='none') return;
    if(e.key==='Escape')     close();
    if(e.key==='ArrowLeft')  { idx=(idx-1+imgs.length)%imgs.length; resetZoom(); show(); }
    if(e.key==='ArrowRight') { idx=(idx+1)%imgs.length;             resetZoom(); show(); }
    if(e.key==='+')          zoomBy(1.3,0,0);
    if(e.key==='-')          zoomBy(0.7,0,0);
  });

  // ── Rueda del ratón ───────────────────────────────
  lb.addEventListener('wheel',function(e){
    e.preventDefault();
    var r=img.getBoundingClientRect();
    zoomBy(e.deltaY<0?1.15:0.87, e.clientX-r.left-r.width/2, e.clientY-r.top-r.height/2);
  },{passive:false});

  // ── Arrastre (pan) ────────────────────────────────
  var dragging=false, dragStartX, dragStartY, panStartX, panStartY;
  img.addEventListener('mousedown',function(e){
    if(scale<=1) return;
    dragging=true; dragStartX=e.clientX; dragStartY=e.clientY;
    panStartX=panX; panStartY=panY; img.style.cursor='grabbing'; e.preventDefault();
  });
  document.addEventListener('mousemove',function(e){
    if(!dragging) return;
    panX=panStartX+(e.clientX-dragStartX);
    panY=panStartY+(e.clientY-dragStartY);
    clampPan(); applyTransform();
  });
  document.addEventListener('mouseup',function(){ dragging=false; if(scale>1) img.style.cursor='move'; });

  // ── Touch: pinch-to-zoom + swipe ─────────────────
  var t0={}, lastDist=0, swipeStartX=0, swipeStartY=0, isPinch=false;
  lb.addEventListener('touchstart',function(e){
    if(e.touches.length===2){
      isPinch=true;
      lastDist=Math.hypot(e.touches[0].clientX-e.touches[1].clientX, e.touches[0].clientY-e.touches[1].clientY);
    } else if(e.touches.length===1){
      isPinch=false;
      swipeStartX=e.touches[0].clientX; swipeStartY=e.touches[0].clientY;
      if(scale>1){ t0={x:e.touches[0].clientX,y:e.touches[0].clientY}; panStartX=panX; panStartY=panY; }
    }
  },{passive:true});

  lb.addEventListener('touchmove',function(e){
    if(e.touches.length===2 && isPinch){
      e.preventDefault();
      var d=Math.hypot(e.touches[0].clientX-e.touches[1].clientX, e.touches[0].clientY-e.touches[1].clientY);
      var cx=(e.touches[0].clientX+e.touches[1].clientX)/2 - window.innerWidth/2;
      var cy=(e.touches[0].clientY+e.touches[1].clientY)/2 - window.innerHeight/2;
      zoomBy(d/lastDist, cx, cy); lastDist=d;
    } else if(e.touches.length===1 && scale>1){
      e.preventDefault();
      panX=panStartX+(e.touches[0].clientX-t0.x);
      panY=panStartY+(e.touches[0].clientY-t0.y);
      clampPan(); applyTransform();
    }
  },{passive:false});

  lb.addEventListener('touchend',function(e){
    if(isPinch){ isPinch=false; return; }
    if(scale>1) return;
    var dx=e.changedTouches[0].clientX-swipeStartX;
    var dy=e.changedTouches[0].clientY-swipeStartY;
    if(Math.abs(dx)>60 && Math.abs(dy)<40 && imgs.length>1){
      if(dx<0){ idx=(idx+1)%imgs.length; } else { idx=(idx-1+imgs.length)%imgs.length; }
      resetZoom(); show();
    } else if(Math.abs(dx)<10 && Math.abs(dy)<10){
      // tap en fondo cierra
    }
  },{passive:true});

  // ── API pública ───────────────────────────────────
  window.openLightbox = open;   // openLightbox([url1,url2,...], index)
})();
</script>

<script>
// ═══ QUICK VIEW ═══
var _qvProduct = null;

function openQuickView(p) {
  _qvProduct = p;
  var modal = document.getElementById('qv-modal');
  var img   = document.getElementById('qv-img');
  var ph    = document.getElementById('qv-img-placeholder');

  document.getElementById('qv-cat').textContent   = p.cat || '';
  document.getElementById('qv-name').textContent  = p.name || '';

  var cur = window.EC_CURRENCY || 'S/';
  document.getElementById('qv-price').textContent = cur + ' ' + parseFloat(p.price).toFixed(2);

  var cmp = document.getElementById('qv-compare');
  if (p.cp && p.cp > p.price) {
    cmp.textContent = cur + ' ' + parseFloat(p.cp).toFixed(2);
    cmp.style.display = 'inline';
  } else {
    cmp.style.display = 'none';
  }

  if (p.img) {
    img.src = p.img; img.style.display = 'block'; ph.style.display = 'none';
  } else {
    img.style.display = 'none'; ph.style.display = 'grid';
  }

  var stockOut = document.getElementById('qv-stock-out');
  var btnAdd   = document.getElementById('qv-btn-add');
  var stockLow = document.getElementById('qv-stock-low');

  if (p.stock === 0) {
    stockOut.style.display = 'block';
    if (btnAdd) btnAdd.style.display = 'none';
  } else {
    stockOut.style.display = 'none';
    if (btnAdd) btnAdd.style.display = 'flex';
  }

  if (p.stock !== null && p.stock !== undefined && p.stock > 0 && p.stock <= 10) {
    stockLow.textContent = '⚡ ¡Solo quedan ' + p.stock + ' unidades!';
    stockLow.style.display = 'block';
  } else {
    stockLow.style.display = 'none';
  }

  document.getElementById('qv-btn-view').onclick = function() {
    closeQuickView();
    // Llama al store Alpine
    var store = document.querySelector('[x-data]').__x && document.querySelector('[x-data]').__x.$data;
    if (store && store.openProduct) store.openProduct(p.id);
    else if (window.Alpine) {
      Alpine.store && Alpine.store('ec') ? Alpine.store('ec').openProduct(p.id) : null;
      // fallback: dispatch custom event
      document.dispatchEvent(new CustomEvent('qv-open-product', {detail: {id: p.id}}));
    }
  };

  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
  document.addEventListener('keydown', _qvEsc);
}

function closeQuickView() {
  document.getElementById('qv-modal').classList.remove('open');
  document.body.style.overflow = '';
  document.removeEventListener('keydown', _qvEsc);
  _qvProduct = null;
}

function _qvEsc(e) { if (e.key === 'Escape') closeQuickView(); }

function qvAddToCart() {
  if (!_qvProduct) return;
  var el = document.querySelector('[x-data]');
  if (el && el._x_dataStack && el._x_dataStack[0] && el._x_dataStack[0].addToCart) {
    el._x_dataStack[0].addToCart(_qvProduct.id);
    el._x_dataStack[0].showToast && el._x_dataStack[0].showToast('✓ ' + _qvProduct.name + ' agregado');
  }
  closeQuickView();
}

function qvCotizar() {
  if (!_qvProduct) return;
  var el = document.querySelector('[x-data]');
  if (el && el._x_dataStack && el._x_dataStack[0] && el._x_dataStack[0].openQuotePopup) {
    closeQuickView();
    el._x_dataStack[0].openQuotePopup(_qvProduct.id);
  }
}

// Escuchar evento para abrir producto desde el botón "Ver producto completo"
document.addEventListener('qv-open-product', function(e) {
  var el = document.querySelector('[x-data]');
  if (el && el._x_dataStack && el._x_dataStack[0]) {
    el._x_dataStack[0].openProduct(e.detail.id);
  }
});
</script>

{{-- ═══════════════════════════════════════════════
     CART PAGE
═══════════════════════════════════════════════ --}}
<main x-show="page==='cart'" class="page page-pad fade-in">
  <h1 class="h1" style="margin-bottom:24px">Tu carrito</h1>
  <template x-if="cart.length===0">
    <div style="text-align:center;padding:64px 16px;background:var(--bg-surface);border-radius:var(--radius-lg);border:1px solid var(--border)">
      <div style="font-size:64px;margin-bottom:16px">🛒</div>
      <h3 class="h3" style="margin-bottom:8px">Tu carrito está vacío</h3>
      <p style="color:var(--text-secondary);margin-bottom:24px">Agrega productos para comenzar a comprar</p>
      <button class="btn btn-primary" @click="page='catalog'">Explorar catálogo</button>
    </div>
  </template>
  <template x-if="cart.length>0">
    <div class="cart-page">
      <div class="cart-lines">
        <template x-for="item in cart" :key="item.id">
          <div class="cart-line">
            <div class="cart-line-thumb">
              <template x-if="item.img"><img :src="item.img" :alt="item.name"></template>
              <template x-if="!item.img"><div style="width:100%;height:100%;display:grid;place-items:center;font-size:32px;background:var(--bg-inset)">📦</div></template>
            </div>
            <div>
              <div class="cart-line-cat" x-text="item.cat"></div>
              <div class="cart-line-name" x-text="item.name"></div>
              <div class="cart-line-actions">
                <div class="qty-stepper">
                  <button @click="updateQty(item.id,-1)">−</button>
                  <span x-text="item.qty"></span>
                  <button @click="updateQty(item.id,1)">+</button>
                </div>
                <a href="#" @click.prevent="removeFromCart(item.id)" style="font-size:12px;color:var(--text-secondary);cursor:pointer">Eliminar</a>
              </div>
            </div>
            <div class="cart-line-price" x-text="fmt(item.price*item.qty)"></div>
          </div>
        </template>
      </div>
      <div class="summary">
        <h4>Resumen del pedido</h4>
        <div class="summary-row"><span>Subtotal</span><span x-text="fmt(subtotal)"></span></div>
        @if($shippingEnabled)
        <div class="summary-row">
          <span>Envío</span>
          <span x-text="subtotal>={{ $shippingFreeFrom }} && {{ $shippingFreeFrom }}>0 ? 'Gratis' : fmt({{ $shippingCost }})"></span>
        </div>
        @endif
        <div class="summary-row total"><span>Total</span><span x-text="fmt(orderTotal)"></span></div>
        @if($shippingEnabled && $shippingFreeFrom > 0)
        <p style="font-size:12px;color:var(--text-secondary);margin:8px 0 16px" x-show="subtotal < {{ $shippingFreeFrom }}">
          Agrega <strong x-text="fmt({{ $shippingFreeFrom }}-subtotal)"></strong> más para envío gratis.
        </p>
        @endif
        <button class="btn btn-primary btn-block" style="margin-top:8px" @click="checkoutOpen=true">{{ $isQuoteOnly ? ($settings['btn_quote_text'] ?? 'Cotizar') : 'Finalizar compra' }}</button>
      </div>
    </div>
  </template>
</main>

@if($isQuoteOnly)
{{-- ═══ POPUP COTIZAR CANTIDAD ═══ --}}
<div x-show="quotePopup" x-cloak @click.self="quotePopup=false"
     style="position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border-radius:20px;padding:28px 24px;width:100%;max-width:360px;box-shadow:0 20px 60px rgba(0,0,0,.25)">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
      <p style="font-weight:700;font-size:16px;color:#111;margin:0" x-text="quotePopupProduct?.name"></p>
      <button @click="quotePopup=false" style="background:none;border:none;font-size:20px;cursor:pointer;color:#888;line-height:1">✕</button>
    </div>
    <p style="font-size:13px;color:#666;margin:0 0 16px">¿Cuántas unidades quieres cotizar?</p>
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
      <button @click="quotePopupQty=Math.max(1,quotePopupQty-1)"
              style="width:44px;height:44px;border-radius:12px;border:1.5px solid #e5e7eb;background:#f9fafb;font-size:22px;cursor:pointer;display:grid;place-items:center;font-weight:600">−</button>
      <input type="number" x-model.number="quotePopupQty" min="1"
             @input="quotePopupQty=Math.max(1,parseInt($event.target.value)||1)"
             style="flex:1;text-align:center;font-size:22px;font-weight:700;border:1.5px solid #e5e7eb;border-radius:12px;padding:10px;outline:none;color:#111">
      <button @click="quotePopupQty++"
              style="width:44px;height:44px;border-radius:12px;border:1.5px solid #e5e7eb;background:#f9fafb;font-size:22px;cursor:pointer;display:grid;place-items:center;font-weight:600">+</button>
    </div>
    <button @click="confirmQuotePopup()"
            style="width:100%;padding:14px;background:var(--primary);color:var(--primary-ink);border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer">
      {{ $settings['btn_quote_text'] ?? 'Cotizar' }}
    </button>
  </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════
     CHECKOUT FULLSCREEN MODAL
═══════════════════════════════════════════════ --}}
<div x-show="checkoutOpen" x-cloak class="ck-modal fade-in">

  {{-- Popup carrito vacío --}}
  <div x-show="checkoutOpen && cart.length===0"
       style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.45);padding:16px">
    <div style="background:white;border-radius:16px;padding:32px 24px;max-width:340px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.2)">
      <div style="font-size:52px;margin-bottom:12px">🛒</div>
      <h3 style="font-size:18px;font-weight:700;margin-bottom:8px;color:#111">Tu carrito está vacío</h3>
      <p style="font-size:13px;color:#6b7280;margin-bottom:20px">Agrega productos antes de continuar con tu pedido.</p>
      <button @click="checkoutOpen=false;page='home'"
              style="display:inline-flex;align-items:center;gap:6px;background:var(--primary);color:var(--primary-ink);border:none;border-radius:10px;padding:10px 20px;font-size:14px;font-weight:600;cursor:pointer;width:100%;justify-content:center">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        Ir al catálogo
      </button>
    </div>
  </div>

  <div x-show="cart.length>0">
  <div class="ck-modal-header">
    <button class="btn btn-ghost btn-sm" @click="checkoutOpen=false" aria-label="Volver al catálogo" style="display:flex;align-items:center;gap:6px;padding:6px 12px;font-size:13px;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
      Volver
    </button>
    <h2>{{ $isQuoteOnly ? 'Tu cotización' : 'Finalizar compra' }}</h2>
    <span style="font-size:13px;color:var(--text-secondary)" x-text="cartCount+' '+( cartCount===1?'producto':'productos')"></span>
  </div>

  <div class="ck-grid">
    {{-- LEFT: FORM --}}
    <div>
      <div x-show="orderSuccess" style="background:color-mix(in srgb,var(--success) 10%,transparent);border:1px solid var(--success);border-radius:var(--radius-lg);padding:24px;margin-bottom:16px;text-align:center">
        <div style="font-size:48px;margin-bottom:8px">✅</div>
        <h3 class="h3" style="color:var(--success);margin-bottom:8px">¡Pedido confirmado!</h3>
        <p style="color:var(--text-secondary)" x-text="orderSuccessMsg"></p>
        <button class="btn btn-primary" style="margin-top:16px" @click="checkoutOpen=false;orderSuccess=false;orderCart=[];page='home'">Volver al inicio</button>
      </div>

      <div x-show="!orderSuccess">
        <div class="co-section">
          <h3>Datos de contacto</h3>
          <div class="field-row">
            <div class="field"><label>Nombre *</label><input x-model="form.fname" placeholder="Tu nombre" type="text"></div>
            @if(!$isQuoteOnly && ($ckFields['fixed']['lname']['enabled'] ?? true))
            <div class="field"><label>Apellido</label><input x-model="form.lname" placeholder="Tu apellido" type="text"></div>
            @endif
          </div>
          <div class="field-row">
            <div class="field">
              <label>Celular *</label>
              <input x-model="form.phone" placeholder="999 999 999" type="tel" inputmode="numeric" maxlength="15"
                @input="form.phone = $event.target.value.replace(/[^\d\s\+\-\(\)]/g,'')"
                :style="form.phone && form.phone.replace(/\D/g,'').length > 0 && (form.phone.replace(/\D/g,'').length < 7 || (form.phone.replace(/\D/g,'').length===9 && !/^9/.test(form.phone.replace(/\D/g,'')))) ? 'border-color:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.15)' : ''">
              <span x-show="form.phone && form.phone.replace(/\D/g,'').length===9 && !/^9/.test(form.phone.replace(/\D/g,''))" style="font-size:11px;color:#ef4444;margin-top:4px;display:block">Los celulares peruanos empiezan con 9</span>
            </div>
            @if(!$isQuoteOnly && ($ckFields['fixed']['email']['enabled'] ?? true))
            <div class="field"><label>Email</label><input x-model="form.email" placeholder="tu@correo.com" type="email"></div>
            @endif
          </div>
          @if($isQuoteOnly || ($ckFields['fixed']['dni']['enabled'] ?? true))
          <div class="field"><label>DNI / RUC <span style="font-weight:400;color:var(--text-muted)">(Opcional)</span></label><input x-model="form.dni" placeholder="12345678" type="text"></div>
          @endif
        </div>

        @if(!$isQuoteOnly && (($ckFields['fixed']['address']['enabled'] ?? false) || $requireAddress))

        <div class="co-section">
          <h3>Dirección de entrega</h3>
          <div class="field-row">
            <div class="field"><label>Departamento</label><input x-model="form.department" placeholder="Lima" type="text"></div>
            <div class="field"><label>Distrito</label><input x-model="form.district" placeholder="Miraflores" type="text"></div>
          </div>
          <div class="field"><label>Dirección *</label><input x-model="form.address" placeholder="Av. Principal 123" type="text"></div>
          <div class="field"><label>Referencia</label><input x-model="form.address2" placeholder="Piso 2, puerta roja..." type="text"></div>
        </div>
        @endif

        @if(!$isQuoteOnly && ($ckFields['fixed']['notes']['enabled'] ?? true))
        <div class="co-section">
          <h3>Notas del pedido</h3>
          <div class="field"><textarea x-model="form.notes" placeholder="Instrucciones especiales, horario de entrega, etc."></textarea></div>
        </div>
        @endif

        @if(!$isQuoteOnly && !empty($ckFields['custom']))
        @php $enabledCustom = array_filter($ckFields['custom'], fn($f) => $f['enabled'] ?? true); @endphp
        @if(!empty($enabledCustom))
        <div class="co-section">
          <h3>Información adicional</h3>
          @foreach($enabledCustom as $cf)
          <div class="field">
            <label>{{ $cf['label'] }}@if($cf['required'] ?? false) *@endif</label>
            @if(($cf['type'] ?? 'text') === 'textarea')
            <textarea x-model="form.custom_{{ $cf['key'] }}" placeholder="{{ $cf['label'] }}"></textarea>
            @else
            <input x-model="form.custom_{{ $cf['key'] }}" type="{{ $cf['type'] ?? 'text' }}" placeholder="{{ $cf['label'] }}">
            @endif
          </div>
          @endforeach
        </div>
        @endif
        @endif

        <div x-show="orderError" style="padding:12px 16px;background:color-mix(in srgb,var(--danger) 10%,transparent);border:1px solid var(--danger);border-radius:var(--radius-md);color:var(--danger);margin-bottom:16px;font-size:14px" x-text="orderError"></div>

        {{-- Botón desktop --}}
        <div class="ck-submit-desktop">
          @if($isQuoteOnly)
          <button class="btn btn-block btn-lg" style="background:#25d366;color:white;border:0;display:flex;align-items:center;justify-content:center;gap:10px;font-weight:700;font-size:15px;border-radius:var(--radius-md);height:52px;cursor:pointer"
             @click="quoteByWhatsapp()">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2zm5.6 14.2c-.2.6-1.2 1.1-1.7 1.2-.4 0-1 .1-1.6-.1-.4-.1-.9-.3-1.6-.6-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.4-1.1-2.6 0-1.3.6-1.9.9-2.1.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4l.8 1.8c.1.2.1.4 0 .6l-.3.4-.3.4c-.1.1-.2.3-.1.5.1.2.6 1 1.3 1.6.9.8 1.7 1 1.9 1.1.2.1.3.1.5-.1l.7-.8c.2-.2.4-.2.6-.1l1.6.8c.2.1.3.2.4.3 0 .1 0 .8-.2 1.4z"/></svg>
            {{ $settings['btn_quote_text'] ?? 'Cotizar' }} por WhatsApp · <span x-text="fmt(subtotal)"></span>
          </button>
          @elseif($quoteWa)
          <a class="btn btn-block btn-lg" style="background:#25d366;color:white;border:0;display:flex;align-items:center;justify-content:center;gap:10px;font-weight:700;font-size:15px;border-radius:var(--radius-md);height:52px;text-decoration:none;cursor:pointer"
             @click.prevent="submitAndOpenWhatsApp()">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2zm5.6 14.2c-.2.6-1.2 1.1-1.7 1.2-.4 0-1 .1-1.6-.1-.4-.1-.9-.3-1.6-.6-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.4-1.1-2.6 0-1.3.6-1.9.9-2.1.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4l.8 1.8c.1.2.1.4 0 .6l-.3.4-.3.4c-.1.1-.2.3-.1.5.1.2.6 1 1.3 1.6.9.8 1.7 1 1.9 1.1.2.1.3.1.5-.1l.7-.8c.2-.2.4-.2.6-.1l1.6.8c.2.1.3.2.4.3 0 .1 0 .8-.2 1.4z"/></svg>
            Realizar pedido por WhatsApp · <span x-text="fmt(orderTotal)"></span>
          </a>
          @else
          <button class="btn btn-primary btn-block btn-lg" @click="submitCheckout()" :disabled="orderLoading">
            <span x-show="!orderLoading">Realizar el pedido · <span x-text="fmt(orderTotal)"></span></span>
            <span x-show="orderLoading">Procesando…</span>
          </button>
          @endif
        </div>
      </div>
    </div>

    {{-- RIGHT: ORDER SUMMARY + MÉTODOS DE PAGO --}}
    <div>
      {{-- Resumen del pedido --}}
      <div class="summary">
        <h4>Tu pedido</h4>
        <template x-for="item in (orderSuccess ? orderCart : cart)" :key="item.id">
          <div class="co-order-item">
            <div class="co-order-thumb">
              <template x-if="item.img"><img :src="item.img" :alt="item.name"></template>
              <template x-if="!item.img"><div style="width:100%;height:100%;display:grid;place-items:center;font-size:24px;background:var(--bg-inset)">📦</div></template>
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-size:13px;font-weight:500" x-text="item.name"></div>
              <div x-show="!orderSuccess" style="display:flex;align-items:center;gap:8px;margin-top:4px">
                <button style="width:24px;height:24px;border:1px solid var(--border);border-radius:4px;background:transparent;cursor:pointer;font-size:14px" @click="updateQty(item.id,-1)">−</button>
                <span style="font-size:13px;min-width:16px;text-align:center" x-text="item.qty"></span>
                <button style="width:24px;height:24px;border:1px solid var(--border);border-radius:4px;background:transparent;cursor:pointer;font-size:14px" @click="updateQty(item.id,1)">+</button>
              </div>
              <div x-show="orderSuccess" style="font-size:12px;color:var(--text-muted);margin-top:2px" x-text="'x'+item.qty"></div>
            </div>
            <span style="font-size:14px;font-weight:600;white-space:nowrap" x-text="fmt(item.price*item.qty)"></span>
          </div>
        </template>
        <div class="summary-row" style="margin-top:8px">
          <span>Subtotal</span>
          <span x-text="fmt(orderSuccess ? orderTotal_snapshot : subtotal)"></span>
        </div>
        @if($shippingEnabled)
        <div class="summary-row" x-show="!orderSuccess">
          <span>Envío</span>
          <span x-text="subtotal>={{ $shippingFreeFrom }} && {{ $shippingFreeFrom }}>0 ? 'Gratis' : fmt({{ $shippingCost }})"></span>
        </div>
        @endif
        <div class="summary-row total">
          <span>Total</span>
          <span x-text="fmt(orderSuccess ? orderTotal_snapshot : orderTotal)"></span>
        </div>
      </div>

      {{-- Métodos de pago (columna derecha, debajo del resumen) --}}
      @if(!$isQuoteOnly && ($payManualEnabled || $culqiEnabled || $mpEnabled))
      <div class="co-section" style="margin-top:16px">
        <h3 style="font-size:16px;margin-bottom:12px">Método de pago</h3>

        {{-- ── Yape ── --}}
        @if(in_array('yape', $payManualMethods) && $payYapeNumber)
        <div class="pay-method" :class="{selected:payMethod==='yape'}" @click="payMethod='yape'" style="align-items:flex-start;padding:12px 14px">
          <div style="margin-top:2px;width:18px;height:18px;border-radius:50%;border:2px solid var(--border-strong);display:grid;place-items:center;flex-shrink:0" :class="payMethod==='yape'?'border-primary':''">
            <div x-show="payMethod==='yape'" style="width:9px;height:9px;border-radius:50%;background:var(--primary)"></div>
          </div>
          <div style="flex:1">
            <div style="font-weight:700;font-size:13px;margin-bottom:1px">Paga con Yape</div>
            <div style="font-size:11px;color:var(--text-muted)">Escanea el QR o agrega el número</div>
          </div>
          <div style="width:44px;height:24px;background:#6c1eb0;border-radius:5px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg width="32" height="14" viewBox="0 0 80 32" fill="white"><text x="4" y="24" font-family="Arial" font-weight="900" font-size="26">yape</text></svg>
          </div>
        </div>
        <div x-show="payMethod==='yape'" style="margin:0 0 8px;border-radius:var(--radius-md);overflow:hidden">
          <div style="background:linear-gradient(135deg,#6c1eb0 0%,#8b2fc9 100%);padding:16px;display:flex;gap:16px;align-items:center;flex-wrap:wrap">
            {{-- QR --}}
            <div style="background:white;padding:6px;border-radius:10px;flex-shrink:0">
              @if($payYapeQr)
              <img src="{{ $payYapeQr }}" alt="QR Yape" style="width:130px;height:130px;display:block;border-radius:4px;object-fit:contain">
              @else
              <div style="width:130px;height:130px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;color:#6c1eb0;font-size:11px;text-align:center">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3m0 4h4v-4m-4 0h-3"/></svg>
                <span>Sube tu QR desde Ajustes</span>
              </div>
              @endif
            </div>
            {{-- Info --}}
            <div style="color:white;flex:1;min-width:110px">
              <div style="font-size:10px;font-weight:600;opacity:.8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">Escanea con Yape</div>
              @if($payYapeName)<div style="font-size:13px;font-weight:700;margin-bottom:3px">{{ $payYapeName }}</div>@endif
              <div x-data="{copied:false}" style="display:flex;align-items:center;gap:6px;margin-bottom:10px">
                <span style="font-size:22px;font-weight:900;letter-spacing:.02em;white-space:nowrap">{{ chunk_split($payYapeNumber, 3, ' ') }}</span>
                <button @click="navigator.clipboard.writeText('{{ $payYapeNumber }}').then(()=>{copied=true;setTimeout(()=>copied=false,2000)})"
                        style="flex-shrink:0;width:30px;height:30px;background:white;border:none;border-radius:8px;cursor:pointer;color:#111;display:grid;place-items:center;transition:opacity .15s;box-shadow:0 1px 4px rgba(0,0,0,.2)"
                        :style="copied?'opacity:.75':''"
                        title="Copiar número">
                  <svg x-show="!copied" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                  <svg x-show="copied" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
              </div>
              <a href="https://api.whatsapp.com/send?phone={{ $payYapeNumber }}" target="_blank" rel="noopener"
                 style="display:inline-flex;align-items:center;gap:5px;background:white;color:#6c1eb0;border:0;border-radius:20px;padding:7px 14px;font-size:12px;font-weight:700;text-decoration:none">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                Añadir a contactos
              </a>
            </div>
          </div>
          <div style="background:color-mix(in srgb,#6c1eb0 6%,transparent);border:1px solid color-mix(in srgb,#6c1eb0 18%,transparent);border-top:0;padding:8px 14px;font-size:11px;color:var(--text-secondary)">
            Realiza el pago y envía el comprobante por WhatsApp al confirmar tu pedido.
          </div>
        </div>
        @endif

        {{-- ── Plin ── --}}
        @php $payPlinNumber = preg_replace('/\D/', '', $settings['payment_plin_number'] ?? ''); @endphp
        @if(in_array('plin', $payManualMethods) && $payPlinNumber)
        <div class="pay-method" :class="{selected:payMethod==='plin'}" @click="payMethod='plin'">
          <div style="width:18px;height:18px;border-radius:50%;border:2px solid var(--border-strong);display:grid;place-items:center;flex-shrink:0" :class="payMethod==='plin'?'border-primary':''">
            <div x-show="payMethod==='plin'" style="width:9px;height:9px;border-radius:50%;background:var(--primary)"></div>
          </div>
          <div style="flex:1"><div style="font-weight:600;font-size:13px">Paga con Plin</div><div style="font-size:11px;color:var(--text-muted)">N° {{ $payPlinNumber }}</div></div>
          <div style="background:#00b4d8;border-radius:5px;padding:2px 9px;font-size:11px;font-weight:800;color:white">plin</div>
        </div>
        <div x-show="payMethod==='plin'" style="margin:0 0 8px;padding:12px 14px;background:color-mix(in srgb,#00b4d8 8%,transparent);border:1px solid color-mix(in srgb,#00b4d8 25%,transparent);border-radius:var(--radius-md);font-size:12px;color:var(--text-secondary)">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
            <strong style="font-size:18px;font-weight:900;color:var(--text-primary)">{{ $payPlinNumber }}</strong>
            <button x-data="{copied:false}" @click="navigator.clipboard.writeText('{{ $payPlinNumber }}').then(()=>{copied=true;setTimeout(()=>copied=false,2000)})"
                    style="background:color-mix(in srgb,#00b4d8 15%,transparent);border:1px solid color-mix(in srgb,#00b4d8 35%,transparent);border-radius:6px;padding:3px 8px;cursor:pointer;color:#0077a8;display:flex;align-items:center;gap:4px;font-size:11px;font-weight:600"
                    title="Copiar número">
              <svg x-show="!copied" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
              <svg x-show="copied" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              <span x-text="copied?'¡Copiado!':'Copiar'"></span>
            </button>
          </div>
          Realiza el pago y envía el comprobante por WhatsApp al confirmar.
        </div>
        @endif

        {{-- ── Bancos ── --}}
        @foreach($payBankActive as $bk => $bank)
        <div class="pay-method" :class="{selected:payMethod==='bank_{{ $bk }}'}" @click="payMethod='bank_{{ $bk }}'">
          <div style="width:18px;height:18px;border-radius:50%;border:2px solid var(--border-strong);display:grid;place-items:center;flex-shrink:0" :class="payMethod==='bank_{{ $bk }}'?'border-primary':''">
            <div x-show="payMethod==='bank_{{ $bk }}'" style="width:9px;height:9px;border-radius:50%;background:var(--primary)"></div>
          </div>
          <div style="flex:1">
            <div style="font-weight:600;font-size:13px">Paga con {{ $bank['label'] }}</div>
            <div style="font-size:11px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px">{{ $bank['details'] }}</div>
          </div>
          <div style="background:{{ $bank['color'] }};border-radius:5px;padding:3px 8px;min-width:52px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <img src="{{ $bank['logo'] }}" alt="{{ $bank['label'] }}" style="height:18px;object-fit:contain;filter:brightness(0) invert(1)" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
            <span style="display:none;font-size:9px;font-weight:800;color:white">{{ strtoupper($bk) }}</span>
          </div>
        </div>
        <div x-show="payMethod==='bank_{{ $bk }}'" style="margin:0 0 8px;padding:12px 14px;background:var(--bg-inset);border:1px solid var(--border);border-radius:var(--radius-md)">
          <div style="font-size:12px;font-weight:700;color:{{ $bank['color'] }};margin-bottom:8px">{{ $bank['label'] }}</div>
          @foreach(explode("\n", trim($bank['details'])) as $bankLine)
          @if(trim($bankLine))
          <div x-data="{copied:false}" style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:5px 0;border-bottom:1px solid var(--border)">
            <span style="font-size:12px;color:var(--text-secondary)">{{ trim($bankLine) }}</span>
            @php $bankVal = preg_replace('/^[^:：]+[:：]\s*/', '', trim($bankLine)); @endphp
            @if(strlen(preg_replace('/\D/','',$bankVal)) >= 6 || str_contains($bankLine, ':') || str_contains($bankLine, '：'))
            <button @click="navigator.clipboard.writeText('{{ addslashes(trim($bankVal)) }}').then(()=>{copied=true;setTimeout(()=>copied=false,2000)})"
                    style="flex-shrink:0;background:var(--bg-card);border:1px solid var(--border-strong);border-radius:6px;padding:3px 8px;cursor:pointer;color:var(--text-secondary);display:flex;align-items:center;gap:3px;font-size:10px;font-weight:600;white-space:nowrap"
                    title="Copiar">
              <svg x-show="!copied" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
              <svg x-show="copied" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              <span x-text="copied?'OK':'Copiar'"></span>
            </button>
            @endif
          </div>
          @endif
          @endforeach
        </div>
        @endforeach

        {{-- ── Otros (efectivo, contra entrega, etc.) ── --}}
        @foreach($payManualMethods as $method)
        @if(!in_array($method, ['yape','plin']))
        @php $meta = $paymentMeta[$method] ?? ['label'=>$method,'emoji'=>'💳']; @endphp
        <div class="pay-method" :class="{selected:payMethod==='{{ $method }}'}" @click="payMethod='{{ $method }}'">
          <div style="width:18px;height:18px;border-radius:50%;border:2px solid var(--border-strong);display:grid;place-items:center;flex-shrink:0" :class="payMethod==='{{ $method }}'?'border-primary':''">
            <div x-show="payMethod==='{{ $method }}'" style="width:9px;height:9px;border-radius:50%;background:var(--primary)"></div>
          </div>
          <span style="font-size:17px">{{ $meta['emoji'] }}</span>
          <div style="flex:1"><div style="font-weight:600;font-size:13px">{{ $meta['label'] }}</div></div>
        </div>
        @endif
        @endforeach

        {{-- ── Culqi / MP ── --}}
        @if($culqiEnabled)
        <div class="pay-method" :class="{selected:payMethod==='culqi'}" @click="payMethod='culqi'">
          <div style="width:18px;height:18px;border-radius:50%;border:2px solid var(--border-strong);display:grid;place-items:center;flex-shrink:0" :class="payMethod==='culqi'?'border-primary':''">
            <div x-show="payMethod==='culqi'" style="width:9px;height:9px;border-radius:50%;background:var(--primary)"></div>
          </div>
          <span style="font-size:17px">💳</span>
          <div style="flex:1"><div style="font-weight:600;font-size:13px">Tarjeta de crédito/débito</div><div style="font-size:11px;color:var(--text-muted)">Visa, Mastercard, Amex</div></div>
        </div>
        @endif
        @if($mpEnabled)
        <div class="pay-method" :class="{selected:payMethod==='mp'}" @click="payMethod='mp'">
          <div style="width:18px;height:18px;border-radius:50%;border:2px solid var(--border-strong);display:grid;place-items:center;flex-shrink:0" :class="payMethod==='mp'?'border-primary':''">
            <div x-show="payMethod==='mp'" style="width:9px;height:9px;border-radius:50%;background:var(--primary)"></div>
          </div>
          <span style="font-size:17px">💙</span>
          <div style="flex:1"><div style="font-weight:600;font-size:13px">Mercado Pago</div></div>
        </div>
        @endif

        {{-- ── Nro. operación ── --}}
        <div x-show="payMethod && payMethod!=='culqi' && payMethod!=='mp'" style="margin-top:12px">
          <div class="field"><label>Nro. de operación (opcional)</label><input x-model="form.payReference" placeholder="Ej: 12345678" type="text" inputmode="numeric"></div>
        </div>
      </div>
      @endif
    </div>
  </div>
  </div>{{-- /cart.length>0 --}}

</div>

{{-- Botón fijo bottom móvil — FUERA del ck-modal para que position:fixed funcione en iOS/Android --}}
<div class="ck-submit-mobile">
  @if($isQuoteOnly)
  <button class="btn btn-block btn-lg" style="background:#25d366;color:white;border:0;display:flex;align-items:center;justify-content:center;gap:10px;font-weight:700;font-size:15px;height:48px;border-radius:var(--radius-md);cursor:pointer"
     @click="quoteByWhatsapp()">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2zm5.6 14.2c-.2.6-1.2 1.1-1.7 1.2-.4 0-1 .1-1.6-.1-.4-.1-.9-.3-1.6-.6-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.4-1.1-2.6 0-1.3.6-1.9.9-2.1.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4l.8 1.8c.1.2.1.4 0 .6l-.3.4-.3.4c-.1.1-.2.3-.1.5.1.2.6 1 1.3 1.6.9.8 1.7 1 1.9 1.1.2.1.3.1.5-.1l.7-.8c.2-.2.4-.2.6-.1l1.6.8c.2.1.3.2.4.3 0 .1 0 .8-.2 1.4z"/></svg>
    {{ $settings['btn_quote_text'] ?? 'Cotizar' }} por WhatsApp · <span x-text="fmt(subtotal)"></span>
  </button>
  @elseif($quoteWa)
  <a class="btn btn-block btn-lg" style="background:#25d366;color:white;border:0;display:flex;align-items:center;justify-content:center;gap:10px;font-weight:700;font-size:15px;height:48px;border-radius:var(--radius-md);text-decoration:none"
     @click.prevent="submitAndOpenWhatsApp()">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2zm5.6 14.2c-.2.6-1.2 1.1-1.7 1.2-.4 0-1 .1-1.6-.1-.4-.1-.9-.3-1.6-.6-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.4-1.1-2.6 0-1.3.6-1.9.9-2.1.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4l.8 1.8c.1.2.1.4 0 .6l-.3.4-.3.4c-.1.1-.2.3-.1.5.1.2.6 1 1.3 1.6.9.8 1.7 1 1.9 1.1.2.1.3.1.5-.1l.7-.8c.2-.2.4-.2.6-.1l1.6.8c.2.1.3.2.4.3 0 .1 0 .8-.2 1.4z"/></svg>
    Enviar por WhatsApp · <span x-text="fmt(orderTotal)"></span>
  </a>
  @else
  <button class="btn btn-primary btn-block btn-lg" style="height:48px" @click="submitCheckout()" :disabled="orderLoading">
    <span x-show="!orderLoading">Realizar pedido · <span x-text="fmt(orderTotal)"></span></span>
    <span x-show="orderLoading">Procesando…</span>
  </button>
  @endif
</div>

{{-- FOOTER --}}
<footer class="footer">
  <div class="page">

    {{-- NEWSLETTER (encima del footer, no dentro de los bloques) --}}
    <div class="footer-newsletter" style="margin-top:64px;margin-bottom:0;border-radius:var(--radius-xl) var(--radius-xl) 0 0;">
      <div>
        <h3>5% de descuento en tu primera compra 🎁</h3>
        <p>Suscríbete y recibe ofertas exclusivas antes que nadie.<br><span style="opacity:.7;font-size:13px">🔒 Sin spam. Puedes darte de baja cuando quieras.</span></p>
      </div>
      <form class="newsletter-form" @submit.prevent="newsletterSubmit($event)">
        <input type="email" placeholder="tu@correo.com" aria-label="Tu correo electrónico" required>
        <button class="btn btn-lg" style="background:var(--primary);color:var(--primary-ink);white-space:nowrap;min-height:48px" type="submit">
          Suscribirme y obtener descuento
        </button>
      </form>
    </div>

    {{-- 5 BLOQUES PRINCIPALES --}}
    <div class="footer-main">

      {{-- Bloque 1: Marca --}}
      <div class="footer-col footer-col-brand">
        <div class="fc-logo">
          @if($logoUrl)
          <img src="{{ asset('storage/'.$logoUrl) }}" alt="{{ $project->name }}" style="height:40px;width:auto;object-fit:contain">
          @else
          <div style="font-family:var(--font-display);font-weight:800;font-size:22px;color:var(--text-primary)">{{ $project->name }}</div>
          @endif
        </div>
        <p class="fc-tagline">{{ $project->description ?? $footerTagline }}</p>
      </div>

      {{-- Bloque 2: Atención al cliente --}}
      <div class="footer-col">
        <h6>Atención al cliente</h6>
        <ul>
          @if($quoteWa)
          <li>
            <a href="https://wa.me/{{ preg_replace('/\D/','',$quoteWa) }}" target="_blank" rel="noopener">
              <svg viewBox="0 0 24 24" fill="currentColor" style="width:15px;height:15px;color:#25d366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
              WhatsApp (respuesta en 24h)
            </a>
          </li>
          @endif
          @if(!empty($settings['contact_email']))
          <li>
            <a href="mailto:{{ $settings['contact_email'] }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              {{ $settings['contact_email'] }}
            </a>
          </li>
          @endif
          @if(!empty($settings['business_hours']))
          <li style="color:var(--text-secondary);font-size:13px;display:flex;align-items:center;gap:7px;padding:2px 0">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;flex-shrink:0"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            {{ $settings['business_hours'] }}
          </li>
          @else
          <li style="color:var(--text-secondary);font-size:13px;display:flex;align-items:center;gap:7px;padding:2px 0">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;flex-shrink:0"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Lunes a sábado, 9am – 6pm
          </li>
          @endif
        </ul>
      </div>

      {{-- Bloque 3: Información legal --}}
      <div class="footer-col">
        <h6>Información</h6>
        <ul>
          <li><a href="#" @click.prevent="">Política de privacidad</a></li>
          <li><a href="#" @click.prevent="">Términos y condiciones</a></li>
          <li><a href="#" @click.prevent="" target="_blank" rel="noopener">Libro de reclamaciones</a></li>
          <li><a href="#" @click.prevent="">Política de envíos</a></li>
          <li><a href="#" @click.prevent="">Política de devoluciones</a></li>
        </ul>
      </div>

      {{-- Bloque 4: Redes sociales --}}
      <div class="footer-col">
        <h6>Síguenos</h6>
        <p style="color:var(--text-secondary);font-size:12.5px;margin:0 0 12px">Conoce promociones y novedades</p>
        <div class="fc-social">
          @if(!empty($settings['facebook_url']))
          <a href="{{ $settings['facebook_url'] }}" target="_blank" rel="noopener" aria-label="Facebook de {{ $project->name }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
          </a>
          @endif
          @if(!empty($settings['instagram_url']))
          <a href="{{ $settings['instagram_url'] }}" target="_blank" rel="noopener" aria-label="Instagram de {{ $project->name }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
          </a>
          @endif
          @if(!empty($settings['tiktok_url']))
          <a href="{{ $settings['tiktok_url'] }}" target="_blank" rel="noopener" aria-label="TikTok de {{ $project->name }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.32 6.32 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.18 8.18 0 004.77 1.52V6.75a4.85 4.85 0 01-1-.06z"/></svg>
          </a>
          @endif
          @if(!empty($settings['youtube_url']))
          <a href="{{ $settings['youtube_url'] }}" target="_blank" rel="noopener" aria-label="YouTube de {{ $project->name }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 00-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 00-1.95 1.96A29 29 0 001 12a29 29 0 00.46 5.58A2.78 2.78 0 003.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 001.95-1.95A29 29 0 0023 12a29 29 0 00-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="white"/></svg>
          </a>
          @endif
          @if(!empty($settings['twitter_url']))
          <a href="{{ $settings['twitter_url'] }}" target="_blank" rel="noopener" aria-label="X/Twitter de {{ $project->name }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.748l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          </a>
          @endif
          {{-- WhatsApp como red social si no hay otras redes configuradas --}}
          @if(empty($settings['facebook_url']) && empty($settings['instagram_url']) && empty($settings['tiktok_url']) && $quoteWa)
          <a href="https://wa.me/{{ preg_replace('/\D/','',$quoteWa) }}" target="_blank" rel="noopener" aria-label="WhatsApp de {{ $project->name }}" style="background:#25d366;color:white;border-color:#25d366">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          </a>
          @endif
        </div>
      </div>

      {{-- Bloque 5: Métodos de pago --}}
      @php $acceptedPay = (array)(json_decode($settings['accepted_payments'] ?? '[]', true) ?: []); @endphp
      <div class="footer-col">
        <h6>Métodos de pago</h6>
        <div class="fc-pay">

          @if(in_array('yape', $acceptedPay))
          <span class="pay-logo" title="Yape">
            <svg height="22" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg"><rect width="80" height="80" rx="16" fill="#6c28d9"/><text x="50%" y="56%" dominant-baseline="middle" text-anchor="middle" font-size="28" font-family="Arial Black,sans-serif" font-weight="900" fill="white">Y</text></svg>
          </span>
          @endif

          @if(in_array('plin', $acceptedPay))
          <span class="pay-logo" title="Plin">
            <svg height="22" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg"><rect width="80" height="80" rx="16" fill="#00c2f3"/><text x="50%" y="56%" dominant-baseline="middle" text-anchor="middle" font-size="18" font-family="Arial,sans-serif" font-weight="700" fill="white">plin</text></svg>
          </span>
          @endif

          @if(in_array('tarjeta', $acceptedPay) || $culqiEnabled)
          {{-- Visa --}}
          <span class="pay-logo" title="Visa">
            <svg height="16" viewBox="0 0 750 240" xmlns="http://www.w3.org/2000/svg"><path d="M278 0L186 240h-62L22 38C18 26 14 22 4 17L4 0h100c13 0 24 9 27 24l58 158L278 0zm60 0l-48 240h-59L279 0h59zm198 160c0-77-104-81-103-116 0-11 10-22 31-25 10-1 39-2 71 13l13-59C524 5 501 0 472 0 395 0 340 42 340 102c-1 44 39 69 68 83 31 15 41 25 41 38 0 21-25 30-47 30-40 0-63-11-82-20l-14 62c19 8 53 16 89 16 83 0 141-41 141-111zm209-160h-56c-12 0-22 7-26 18L556 240h62l12-35h76l7 35h55L745 0zm-88 157l31-87 18 87h-49z" fill="#1a1f71"/></svg>
          </span>
          {{-- Mastercard --}}
          <span class="pay-logo" title="Mastercard">
            <svg height="22" viewBox="0 0 152 95" xmlns="http://www.w3.org/2000/svg"><circle cx="52" cy="47.5" r="47.5" fill="#EB001B"/><circle cx="100" cy="47.5" r="47.5" fill="#F79E1B"/><path d="M76 20.7A47.5 47.5 0 0 1 100 47.5 47.5 47.5 0 0 1 76 74.3 47.5 47.5 0 0 1 52 47.5 47.5 47.5 0 0 1 76 20.7z" fill="#FF5F00"/></svg>
          </span>
          @endif

          @if($mpEnabled || in_array('mp', $acceptedPay))
          <span class="pay-logo" title="Mercado Pago">
            <svg height="22" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg"><rect width="80" height="80" rx="16" fill="#009ee3"/><text x="50%" y="56%" dominant-baseline="middle" text-anchor="middle" font-size="16" font-family="Arial,sans-serif" font-weight="700" fill="white">MP</text></svg>
          </span>
          @endif

          @if(in_array('transferencia', $acceptedPay))
          <span class="pay-logo" title="Transferencia bancaria" style="padding:0 10px;font-size:11px;font-weight:700;color:#374151;gap:4px;min-width:unset;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
            Banco
          </span>
          @endif

          @if(in_array('efectivo', $acceptedPay))
          <span class="pay-logo" title="Efectivo" style="padding:0 10px;font-size:11px;font-weight:700;color:#374151;gap:4px;min-width:unset;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/></svg>
            Efectivo
          </span>
          @endif

          @if(in_array('contra_entrega', $acceptedPay))
          <span class="pay-logo" title="Contra entrega" style="padding:0 10px;font-size:11px;font-weight:700;color:#374151;gap:4px;min-width:unset;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            C/Entrega
          </span>
          @endif

          @if(in_array('qr', $acceptedPay))
          <span class="pay-logo" title="Pago QR" style="padding:0 10px;font-size:11px;font-weight:700;color:#374151;gap:4px;min-width:unset;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="3" height="3"/><rect x="19" y="14" width="2" height="2"/><rect x="14" y="19" width="2" height="2"/><rect x="18" y="19" width="3" height="2"/></svg>
            QR
          </span>
          @endif

        </div>
      </div>

    </div>{{-- /footer-main --}}

    {{-- Barra de copyright --}}
    <div class="footer-base">
      <span>{{ $footerCopyright }}</span>
      <span style="display:flex;align-items:center;gap:5px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        Sitio seguro — SSL
      </span>
    </div>

  </div>
</footer>

{{-- MOBILE MENU --}}
<div class="drawer-backdrop" :class="{show:mobileMenuOpen}" @click="mobileMenuOpen=false"></div>
<aside class="drawer drawer-left" :class="{show:mobileMenuOpen}">
  {{-- Header --}}
  <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-bottom:1px solid var(--border);background:var(--bg-surface);position:sticky;top:0;z-index:2">
    @if($logoUrl)
    <img src="{{ asset('storage/'.$logoUrl) }}" alt="{{ $project->name }}" style="height:32px;width:auto;object-fit:contain">
    @else
    <div style="font-family:var(--font-display);font-weight:700;font-size:17px">{{ $project->name }}</div>
    @endif
    <button class="icon-btn" @click="mobileMenuOpen=false" aria-label="Cerrar menú">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>

  {{-- Buscador dentro del menú móvil --}}
  <div style="padding:12px 16px;border-bottom:1px solid var(--border)">
    <div style="position:relative">
      <input type="text" placeholder="Buscar productos…" x-model="searchQ"
        @keydown.enter="page='catalog';searchFocused=false;mobileMenuOpen=false"
        style="width:100%;height:44px;border:1.5px solid var(--border-strong);border-radius:var(--radius-full);padding:0 44px 0 16px;font-size:14px;background:var(--bg-inset)">
      <button @click="if(searchQ){page='catalog';mobileMenuOpen=false;}" style="position:absolute;right:6px;top:50%;transform:translateY(-50%);width:34px;height:34px;background:var(--primary);border:none;border-radius:999px;display:grid;place-items:center;cursor:pointer">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </button>
    </div>
  </div>

  {{-- Navegación --}}
  <nav style="padding:8px">
    {{-- Inicio --}}
    <button @click="page='home';mobileMenuOpen=false"
      style="display:flex;align-items:center;width:100%;min-height:48px;padding:0 14px;border-radius:10px;border:0;background:transparent;font-size:15px;font-weight:600;cursor:pointer;color:var(--text-primary);gap:10px;text-align:left">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Inicio
    </button>

    {{-- Categorías con subcategorías colapsables --}}
    @foreach($categories as $cat)
    @php $hasSubs = $cat->children->count() > 0; @endphp
    @if($hasSubs)
    <details style="border-radius:10px;overflow:hidden">
      <summary style="display:flex;align-items:center;width:100%;min-height:48px;padding:0 14px;border-radius:10px;border:0;background:transparent;font-size:14px;font-weight:500;cursor:pointer;color:var(--text-primary);gap:10px;list-style:none;justify-content:space-between"
        class="mob-cat-summary">
        <span style="display:flex;align-items:center;gap:10px">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          {{ $cat->name }}
          <span style="font-size:11px;color:var(--text-muted);background:var(--bg-inset);padding:1px 7px;border-radius:999px">{{ $cat->products->count() + $cat->children->sum(fn($s)=>$s->products->count()) }}</span>
        </span>
        <svg class="mob-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;transition:transform var(--t-fast)"><polyline points="6 9 12 15 18 9"/></svg>
      </summary>
      <div style="padding:4px 0 8px 14px;border-left:2px solid var(--border);margin:0 14px 4px 28px">
        <button @click="page='catalog';filterCat='{{ $cat->id }}';filterSubCat=null;mobileMenuOpen=false"
          style="display:flex;align-items:center;width:100%;min-height:40px;padding:0 10px;border-radius:8px;border:0;background:transparent;font-size:13.5px;font-weight:600;cursor:pointer;color:var(--primary);gap:6px;text-align:left">
          Ver todos en {{ $cat->name }}
        </button>
        @foreach($cat->children as $sub)
        <button @click="filterCat='{{ $cat->id }}';filterSubCat='{{ $sub->id }}';page='catalog';mobileMenuOpen=false"
          style="display:flex;align-items:center;width:100%;min-height:40px;padding:0 10px;border-radius:8px;border:0;background:transparent;font-size:13.5px;cursor:pointer;color:var(--text-primary);gap:6px;text-align:left">
          {{ $sub->name }}
          <span style="font-size:11px;color:var(--text-muted);margin-left:auto">{{ $sub->products->count() }}</span>
        </button>
        @endforeach
      </div>
    </details>
    @else
    <button @click="page='catalog';filterCat='{{ $cat->id }}';mobileMenuOpen=false"
      style="display:flex;align-items:center;width:100%;min-height:48px;padding:0 14px;border-radius:10px;border:0;background:transparent;font-size:14px;font-weight:500;cursor:pointer;color:var(--text-primary);gap:10px;text-align:left">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      {{ $cat->name }}
      <span style="font-size:11px;color:var(--text-muted);background:var(--bg-inset);padding:1px 7px;border-radius:999px;margin-left:auto">{{ $cat->products->count() }}</span>
    </button>
    @endif
    @endforeach

    {{-- Separador --}}
    <div style="height:1px;background:var(--border);margin:8px 14px"></div>

    {{-- Todas las categorías → página dedicada --}}
    <button @click="page='categories';mobileMenuOpen=false"
      style="display:flex;align-items:center;width:100%;min-height:48px;padding:0 14px;border-radius:10px;border:0;background:color-mix(in srgb,var(--primary) 8%,transparent);font-size:14px;font-weight:600;cursor:pointer;color:var(--primary);gap:10px;text-align:left;margin-top:4px">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Todas las categorías
      <span style="font-size:11px;font-weight:500;margin-left:auto;background:var(--primary);color:var(--primary-ink);padding:2px 8px;border-radius:999px">{{ $categories->count() }}</span>
    </button>

    {{-- Ver todo el catálogo --}}
    <button @click="page='catalog';filterCat=null;mobileMenuOpen=false"
      style="display:flex;align-items:center;width:100%;min-height:48px;padding:0 14px;border-radius:10px;border:0;background:transparent;font-size:14px;font-weight:500;cursor:pointer;color:var(--text-secondary);gap:10px;text-align:left;margin-top:4px">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
      Ver todo el catálogo →
    </button>
  </nav>

  {{-- Footer del menú: WhatsApp --}}
  @if($quoteWa)
  <div style="padding:12px 16px;border-top:1px solid var(--border);margin-top:8px">
    <a href="https://wa.me/{{ $quoteWa }}" target="_blank" rel="noopener"
      style="display:flex;align-items:center;gap:10px;padding:12px 16px;background:#25d366;color:white;border-radius:12px;font-weight:600;font-size:14px;text-decoration:none">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
      Consultar por WhatsApp
    </a>
  </div>
  @endif
</aside>

{{-- CART DRAWER (móvil) --}}
<div class="drawer-backdrop" :class="{show:cartDrawerOpen}" @click="cartDrawerOpen=false"></div>
<aside class="drawer drawer-bottom" :class="{show:cartDrawerOpen}" style="max-height:85vh;border-radius:20px 20px 0 0;padding:0">
  <div style="padding:8px 0 0;text-align:center">
    <div style="width:40px;height:4px;background:var(--border-strong);border-radius:2px;margin:0 auto 4px"></div>
  </div>
  <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px 10px;border-bottom:1px solid var(--border)">
    <span style="font-family:var(--font-display);font-weight:700;font-size:17px">Tu carrito (<span x-text="cartCount"></span>)</span>
    <button class="icon-btn" @click="cartDrawerOpen=false" aria-label="Cerrar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  {{-- items --}}
  <div style="overflow-y:auto;max-height:45vh;padding:8px 16px">
    <template x-if="cart.length===0">
      <div style="text-align:center;padding:32px 16px;color:var(--text-muted)">
        <div style="font-size:36px;margin-bottom:8px">🛒</div>
        <div style="font-size:14px">Tu carrito está vacío</div>
      </div>
    </template>
    <template x-for="item in cart" :key="item.id">
      <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)">
        <div style="width:54px;height:54px;border-radius:10px;overflow:hidden;flex-shrink:0;background:var(--bg-inset)">
          <template x-if="item.img"><img :src="item.img" :alt="item.name" style="width:100%;height:100%;object-fit:cover"></template>
          <template x-if="!item.img"><div style="width:100%;height:100%;display:grid;place-items:center;font-size:22px">📦</div></template>
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-size:13.5px;font-weight:600;line-height:1.3;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical" x-text="item.name"></div>
          <div style="display:flex;align-items:center;gap:8px;margin-top:6px">
            <div class="qty-stepper">
              <button @click="updateQty(item.id,-1)">−</button>
              <span style="min-width:28px;text-align:center;font-size:13px;font-weight:600" x-text="item.qty"></span>
              <button @click="updateQty(item.id,1)">+</button>
            </div>
            <span style="font-family:var(--font-display);font-weight:700;font-size:14px;color:var(--primary)" x-text="fmt(item.price*item.qty)"></span>
          </div>
        </div>
        <button @click="removeFromCart(item.id)" style="color:var(--text-muted);background:none;border:none;cursor:pointer;padding:4px;flex-shrink:0" aria-label="Eliminar">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
        </button>
      </div>
    </template>
  </div>
  {{-- footer --}}
  <div x-show="cart.length>0" style="padding:12px 16px 24px;border-top:1px solid var(--border)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <span style="font-size:14px;color:var(--text-secondary)">Subtotal</span>
      <span style="font-family:var(--font-display);font-weight:700;font-size:18px" x-text="fmt(subtotal)"></span>
    </div>
    <div style="display:grid;gap:8px">
      <button class="btn btn-primary btn-block btn-lg" @click="cartDrawerOpen=false;checkoutOpen=true">
        {{ $isQuoteOnly ? ($settings['btn_quote_text'] ?? 'Cotizar') : 'Finalizar compra' }}
      </button>
      <button class="btn btn-ghost btn-block" @click="cartDrawerOpen=false;page='cart'">
        Ver carrito completo
      </button>
    </div>
  </div>
</aside>

{{-- TOAST --}}
<div class="toast-host">
  <template x-for="t in toasts" :key="t.id">
    <div class="toast" x-text="t.text"></div>
  </template>
</div>

{{-- BACK TO TOP --}}
<button class="back-top" :class="{show:showBackTop}" @click="window.scrollTo({top:0,behavior:'smooth'})" aria-label="Volver arriba">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
</button>

{{-- ═══════════════════════════════════════════════
     TWEAKS PANEL v3 — solo visible al dueño del proyecto
═══════════════════════════════════════════════ --}}
@if(\Illuminate\Support\Facades\Auth::check() && (\Illuminate\Support\Facades\Auth::id() === $project->owner_id || \Illuminate\Support\Facades\Auth::user()->is_superadmin))
<style>
/* TWEAKS v3 */
#tweaks-fab{position:fixed;bottom:24px;left:0;z-index:202;width:32px;height:48px;border-radius:0 8px 8px 0;background:#18181b;color:white;border:none;cursor:pointer;display:grid;place-items:center;box-shadow:2px 0 12px rgba(0,0,0,.3);transition:left .3s cubic-bezier(.4,0,.2,1);}
#tweaks-fab:hover{background:#27272a;}
body.tw-open #tweaks-fab{left:320px;}
#tweaks-panel{position:fixed;top:0;left:0;bottom:0;width:320px;background:#18181b;color:#f4f4f5;z-index:201;transform:translateX(-100%);transition:transform .3s cubic-bezier(.4,0,.2,1);overflow:hidden;display:flex;flex-direction:column;}
#tweaks-panel.open{transform:translateX(0);}
.tw-head{display:flex;flex-direction:column;padding:12px 14px 0;border-bottom:1px solid #27272a;background:#18181b;z-index:2;}
.tw-head-top{display:flex;align-items:center;justify-content:space-between;padding-bottom:10px;}
.tw-head h3{margin:0;font-size:14px;font-weight:700;letter-spacing:-.01em;}
#tw-autosave-dot{width:7px;height:7px;border-radius:50%;background:#3f3f46;transition:background .3s;flex-shrink:0;}
#tw-autosave-dot.saving{background:#f59e0b;}
#tw-autosave-dot.saved{background:#22c55e;}
#tw-autosave-dot.error{background:#ef4444;}
#tw-autosave-label{font-size:10px;color:#71717a;transition:color .3s;}
#tw-autosave-label.saving{color:#f59e0b;}
#tw-autosave-label.saved{color:#22c55e;}
#tw-autosave-label.error{color:#ef4444;}
.tw-close{width:30px;height:30px;border-radius:8px;border:none;background:#27272a;color:#a1a1aa;cursor:pointer;display:grid;place-items:center;}
.tw-close:hover{background:#3f3f46;color:white;}
.tw-tabs-wrap{position:relative;display:flex;align-items:center;width:100%;}
.tw-tabs-scroll{display:flex;flex:1;gap:1px;overflow-x:auto;padding-bottom:0;scrollbar-width:none;}
.tw-tabs-scroll::-webkit-scrollbar{display:none;}
.tw-tab{flex-shrink:0;padding:6px 10px;font-size:10.5px;font-weight:600;border:none;background:transparent;color:#71717a;cursor:pointer;border-bottom:2px solid transparent;transition:all .15s;white-space:nowrap;}
.tw-tab.active{color:#f4f4f5;border-bottom-color:#6c63ff;}
.tw-tab:hover{color:#d4d4d8;}
.tw-tabs-arrow{width:22px;height:28px;border:none;background:#27272a;color:#a1a1aa;cursor:pointer;display:grid;place-items:center;flex-shrink:0;transition:all .15s;}
.tw-tabs-arrow:hover{background:#3f3f46;color:white;}
.tw-tabs-arrow.left{border-radius:6px 0 0 6px;}
.tw-tabs-arrow.right{border-radius:0 6px 6px 0;}
.tw-panels{flex:1;overflow-y:auto;scrollbar-width:thin;scrollbar-color:#3f3f46 transparent;}
.tw-panel{display:none;padding:14px;flex-direction:column;gap:16px;}
.tw-panel.active{display:flex;}
.tw-section{font-size:9.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#71717a;margin-bottom:4px;}
.tw-row{display:flex;align-items:center;justify-content:space-between;gap:8px;}
.tw-label{font-size:12.5px;color:#d4d4d8;}
.tw-sublabel{font-size:11px;color:#71717a;margin-bottom:4px;}
.tw-input{width:100%;height:34px;background:#27272a;border:1px solid #3f3f46;border-radius:8px;color:#f4f4f5;padding:0 10px;font-size:12.5px;}
.tw-input:focus{outline:none;border-color:#6c63ff;}
.tw-textarea{width:100%;background:#27272a;border:1px solid #3f3f46;border-radius:8px;color:#f4f4f5;padding:8px 10px;font-size:12.5px;resize:vertical;min-height:60px;}
.tw-textarea:focus{outline:none;border-color:#6c63ff;}
.tw-select{width:100%;height:34px;background:#27272a;border:1px solid #3f3f46;border-radius:8px;color:#f4f4f5;padding:0 10px;font-size:12.5px;cursor:pointer;appearance:none;}
.tw-select:focus{outline:none;border-color:#6c63ff;}
.tw-toggle{position:relative;width:38px;height:21px;flex-shrink:0;}
.tw-toggle input{opacity:0;width:0;height:0;position:absolute;}
.tw-toggle-track{position:absolute;inset:0;background:#3f3f46;border-radius:99px;cursor:pointer;transition:background .2s;}
.tw-toggle input:checked+.tw-toggle-track{background:#6c63ff;}
.tw-toggle-thumb{position:absolute;top:2.5px;left:2.5px;width:16px;height:16px;background:white;border-radius:50%;transition:transform .2s;pointer-events:none;}
.tw-toggle input:checked~.tw-toggle-thumb{transform:translateX(17px);}
.tw-swatches{display:flex;flex-wrap:wrap;gap:6px;}
.tw-swatch{width:26px;height:26px;border-radius:50%;border:2px solid transparent;cursor:pointer;position:relative;transition:transform .15s;}
.tw-swatch:hover{transform:scale(1.15);}
.tw-swatch.active{border-color:white;}
.tw-swatch.active::after{content:'✓';position:absolute;inset:0;display:grid;place-items:center;color:white;font-size:11px;font-weight:700;text-shadow:0 1px 2px rgba(0,0,0,.6);}
.tw-color-row{display:flex;align-items:center;gap:8px;margin-top:6px;}
.tw-color-input{width:34px;height:34px;border-radius:8px;border:1px solid #3f3f46;padding:2px;background:#27272a;cursor:pointer;}
.tw-color-hex{flex:1;height:34px;background:#27272a;border:1px solid #3f3f46;border-radius:8px;color:#f4f4f5;padding:0 8px;font-size:12px;font-family:monospace;}
.tw-color-hex:focus{outline:none;border-color:#6c63ff;}
.tw-slider{width:100%;accent-color:#6c63ff;}
.tw-slider-val{font-size:11px;color:#a1a1aa;min-width:32px;text-align:right;}
.tw-pills{display:flex;gap:3px;}
.tw-pill{flex:1;height:28px;border-radius:7px;border:1px solid #3f3f46;background:transparent;color:#a1a1aa;font-size:11px;font-weight:500;cursor:pointer;transition:all .15s;}
.tw-pill.active{background:#6c63ff;border-color:#6c63ff;color:white;}
.tw-nav-btn{display:flex;align-items:center;width:100%;height:36px;padding:0 12px;border-radius:8px;border:none;background:#27272a;color:#f4f4f5;font-size:12.5px;font-weight:500;cursor:pointer;gap:8px;margin-bottom:4px;transition:background .15s;}
.tw-nav-btn:hover{background:#3f3f46;}
.tw-foot{padding:10px 14px;border-top:1px solid #27272a;display:flex;flex-direction:column;gap:5px;background:#18181b;}
.tw-save{width:100%;height:38px;border-radius:9px;border:none;background:#6c63ff;color:white;font-size:12.5px;font-weight:700;cursor:pointer;transition:background .15s;}
.tw-save:hover{background:#5b52e0;}
.tw-save:disabled{opacity:.6;cursor:default;}
.tw-save-msg{font-size:10.5px;color:#71717a;text-align:center;}
.tw-reset{width:100%;height:28px;border-radius:7px;border:1px solid #3f3f46;background:transparent;color:#71717a;font-size:11px;cursor:pointer;}
.tw-reset:hover{color:#f4f4f5;border-color:#52525b;}
.tw-divider{height:1px;background:#27272a;margin:2px 0;}
.tw-logo-preview{width:100%;height:64px;background:#27272a;border:1px dashed #3f3f46;border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;margin-bottom:6px;}
.tw-logo-preview img{max-width:100%;max-height:100%;object-fit:contain;padding:4px;}
.tw-upload-btn{display:flex;align-items:center;gap:6px;height:32px;padding:0 12px;border-radius:7px;border:1px solid #3f3f46;background:#27272a;color:#a1a1aa;font-size:11.5px;cursor:pointer;transition:all .15s;width:100%;}
.tw-upload-btn:hover{border-color:#6c63ff;color:#c4b5fd;}
.tw-badge-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px;}
.tw-payment-checks{display:flex;flex-direction:column;gap:6px;}
.tw-payment-check{display:flex;align-items:center;gap:8px;padding:6px 8px;background:#27272a;border-radius:7px;cursor:pointer;}
.tw-payment-check input{accent-color:#6c63ff;width:14px;height:14px;}
.tw-payment-check span{font-size:12px;color:#d4d4d8;}
</style>

{{-- FAB toggle --}}
<button id="tweaks-fab" title="Tweaks" onclick="twTogglePanel()">
  <svg id="tw-fab-icon-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:none"><polyline points="15 18 9 12 15 6"/></svg>
  <svg id="tw-fab-icon-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
</button>

{{-- Panel --}}
<div id="tweaks-panel">
  <div class="tw-head">
    <div class="tw-head-top">
      <div style="display:flex;align-items:center;gap:8px">
        <h3>✏️ Tweaks</h3>
        <div style="display:flex;align-items:center;gap:4px">
          <div id="tw-autosave-dot"></div>
          <span id="tw-autosave-label">auto</span>
        </div>
      </div>
      <button class="tw-close" onclick="twTogglePanel()" title="Ocultar panel">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
    </div>
    <div class="tw-tabs-wrap">
      <button class="tw-tabs-arrow left" onclick="document.getElementById('tw-tabs-scroll').scrollBy({left:-80,behavior:'smooth'})">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <div class="tw-tabs-scroll" id="tw-tabs-scroll">
        <button class="tw-tab active" onclick="twTab('marca',this)">🏷 Marca</button>
        <button class="tw-tab" onclick="twTab('colores',this)">🎨 Colores</button>
        <button class="tw-tab" onclick="twTab('portada',this)">🖼 Portada</button>
        <button class="tw-tab" onclick="twTab('catalogo',this)">📦 Catálogo</button>
        <button class="tw-tab" onclick="twTab('layout',this)">📐 Layout</button>
        <button class="tw-tab" onclick="twTab('sistema',this)">⚙️ Sistema</button>
        <button class="tw-tab" onclick="twTab('footer',this)">🔻 Footer</button>
        <button class="tw-tab" onclick="twTab('nav',this)">🧭 Nav</button>
      </div>
      <button class="tw-tabs-arrow right" onclick="document.getElementById('tw-tabs-scroll').scrollBy({left:80,behavior:'smooth'})">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>
  </div>

  <div class="tw-panels">

    {{-- ══ TAB: MARCA ══ --}}
    <div class="tw-panel active" id="tw-panel-marca">

      <div>
        <div class="tw-section">Identidad</div>
        <div style="display:flex;flex-direction:column;gap:7px;margin-top:6px">
          <div>
            <div class="tw-sublabel">Nombre del negocio</div>
            <input class="tw-input" id="tw-store-name" value="{{ $project->name }}" placeholder="Mi tienda">
          </div>
          <div>
            <div class="tw-sublabel">Tagline / eslogan corto</div>
            <input class="tw-input" id="tw-tagline" value="{{ $settings['footer_tagline'] ?? '' }}" placeholder="Tu tienda de confianza">
          </div>
          <div>
            <div class="tw-sublabel">Copyright footer</div>
            <input class="tw-input" id="tw-copyright" value="{{ $footerCopyright }}" placeholder="© 2026 Mi Tienda">
          </div>
          <div>
            <div class="tw-sublabel">Símbolo de moneda</div>
            <select class="tw-select" id="tw-currency">
              @foreach(['S/'=>'S/ — Sol','$'=>'$ — Dólar','€'=>'€ — Euro','COP$'=>'COP$','CLP$'=>'CLP$','ARS$'=>'ARS$','MXN$'=>'MXN$','Bs.'=>'Bs.'] as $cs_v=>$cs_l)
              <option value="{{ $cs_v }}" {{ ($settings['currency_symbol'] ?? 'S/') === $cs_v ? 'selected' : '' }}>{{ $cs_l }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <div class="tw-sublabel">Mensaje WhatsApp flotante</div>
            <input class="tw-input" id="tw-wa-msg" value="{{ $settings['whatsapp_msg'] ?? '' }}" placeholder="Hola, vi tu catálogo y me interesa...">
          </div>
          <div>
            <div class="tw-sublabel">Texto topbar / anuncio</div>
            <input class="tw-input" id="tw-announcement" value="{{ $announcementText }}" placeholder="🚚 Envío gratis en pedidos mayores a S/ 99">
          </div>
        </div>
      </div>

      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Logo</div>
        <div id="tw-logo-preview-wrap" class="tw-logo-preview">
          @php $logoVal = $settings['logo_url'] ?? $project->logo_url ?? ''; @endphp
          @if($logoVal)
            <img id="tw-logo-img" src="{{ str_starts_with($logoVal,'http') ? $logoVal : asset('storage/'.$logoVal) }}" alt="Logo">
          @else
            <span id="tw-logo-placeholder" style="color:#52525b;font-size:12px">Sin logo</span>
          @endif
        </div>
        <div style="display:flex;gap:6px">
          <label class="tw-upload-btn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Subir logo
            <input type="file" accept="image/*" style="display:none" onchange="twUploadLogo(this,'logo')">
          </label>
          <button class="tw-upload-btn" style="width:auto;padding:0 10px;flex-shrink:0" onclick="twRemoveLogo()" title="Quitar logo">🗑</button>
        </div>
        <div style="margin-top:6px">
          <div class="tw-sublabel">Altura logo (px)</div>
          <input class="tw-input" type="number" id="tw-logo-height" min="20" max="160" value="{{ $settings['logo_height'] ?? 40 }}">
        </div>
        <input type="hidden" id="tw-logo-path" value="{{ $logoVal }}">
      </div>

      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Favicon</div>
        @php $faviVal = $settings['favicon_url'] ?? ''; @endphp
        <div style="display:flex;align-items:center;gap:10px;margin-top:6px">
          <div style="width:40px;height:40px;background:#27272a;border:1px dashed #3f3f46;border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
            @if($faviVal)
              <img id="tw-favi-img" src="{{ str_starts_with($faviVal,'http') ? $faviVal : asset('storage/'.$faviVal) }}" style="max-width:32px;max-height:32px;object-fit:contain">
            @else
              <span id="tw-favi-placeholder" style="font-size:18px">🌐</span>
            @endif
          </div>
          <label class="tw-upload-btn" style="flex:1">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Subir favicon
            <input type="file" accept="image/*" style="display:none" onchange="twUploadLogo(this,'favicon')">
          </label>
        </div>
        <input type="hidden" id="tw-favi-path" value="{{ $faviVal }}">
      </div>

      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Tipografía</div>
        <div style="display:flex;flex-direction:column;gap:7px;margin-top:6px">
          <div>
            <div class="tw-sublabel">Fuente de títulos</div>
            <select class="tw-select" id="tw-font-display" onchange="twApplyFont()">
              @foreach(['Space Grotesk','Inter','Poppins','Jost','Lato','Raleway','Playfair Display','Cormorant Garamond','Montserrat','Nunito','Oswald','DM Sans'] as $fn)
              <option value="'{{$fn}}',sans-serif" {{ ($settings['font_title'] ?? 'Space Grotesk') === $fn ? 'selected' : '' }}>{{ $fn }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <div class="tw-sublabel">Fuente de texto</div>
            <select class="tw-select" id="tw-font-body" onchange="twApplyFont()">
              @foreach(['DM Sans','Inter','Poppins','Lato','Nunito','Jost','Space Grotesk','Montserrat'] as $fn)
              <option value="'{{$fn}}',sans-serif" {{ ($settings['font_body'] ?? 'DM Sans') === $fn ? 'selected' : '' }}>{{ $fn }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </div>

      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Redes sociales</div>
        <div style="display:flex;flex-direction:column;gap:6px;margin-top:6px">
          @foreach([['facebook_url','Facebook','tw-fb'],['instagram_url','Instagram','tw-ig'],['tiktok_url','TikTok','tw-tt'],['youtube_url','YouTube','tw-yt'],['twitter_url','X / Twitter','tw-tw'],['linkedin_url','LinkedIn','tw-li']] as [$key,$label,$id])
          <div>
            <div class="tw-sublabel">{{ $label }}</div>
            <input class="tw-input" id="{{ $id }}" value="{{ $settings[$key] ?? '' }}" placeholder="https://...">
          </div>
          @endforeach
        </div>
      </div>

    </div>{{-- /marca --}}

    {{-- ══ TAB: COLORES ══ --}}
    <div class="tw-panel" id="tw-panel-colores">

      <div>
        <div class="tw-row" style="margin-bottom:4px">
          <span class="tw-label">Modo oscuro</span>
          <label class="tw-toggle">
            <input type="checkbox" id="tw-dark" onchange="twApplyDark()">
            <div class="tw-toggle-track"></div>
            <div class="tw-toggle-thumb"></div>
          </label>
        </div>
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Color primario</div>
        <div class="tw-swatches" style="margin-top:6px" id="tw-primary-swatches">
          @foreach([['#4f46e5','Índigo'],['#2563eb','Azul'],['#0284c7','Celeste'],['#0d9488','Teal'],['#16a34a','Verde'],['#ca8a04','Amarillo'],['#ea580c','Naranja'],['#dc2626','Rojo'],['#db2777','Rosa'],['#7c3aed','Violeta'],['#92400e','Café'],['#18181b','Negro'],['#475569','Slate'],['#6c63ff','Lavanda']] as [$hex,$name])
          <div class="tw-swatch" style="background:{{$hex}}" title="{{$name}}" data-color="{{$hex}}" onclick="twSetPrimary('{{$hex}}',this)"></div>
          @endforeach
        </div>
        <div class="tw-color-row">
          <input type="color" class="tw-color-input" id="tw-primary-custom" value="{{ $primaryColor }}" oninput="twSetPrimary(this.value,null);document.getElementById('tw-primary-hex').value=this.value">
          <input type="text" class="tw-color-hex" id="tw-primary-hex" value="{{ $primaryColor }}" maxlength="7" oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){twSetPrimary(this.value,null);document.getElementById('tw-primary-custom').value=this.value}">
        </div>
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Color secundario / acento</div>
        <div class="tw-swatches" style="margin-top:6px" id="tw-accent-swatches">
          @foreach([['#f97316','Naranja'],['#eab308','Amarillo'],['#84cc16','Lima'],['#06b6d4','Cian'],['#ec4899','Rosa'],['#a855f7','Morado'],['#14b8a6','Teal'],['#f43f5e','Rosado'],['#ff5b3d','Coral'],['#fbbf24','Dorado']] as [$hex,$name])
          <div class="tw-swatch" style="background:{{$hex}}" title="{{$name}}" data-color="{{$hex}}" onclick="twSetAccent('{{$hex}}',this)"></div>
          @endforeach
        </div>
        <div class="tw-color-row">
          <input type="color" class="tw-color-input" id="tw-accent-custom" value="{{ $settings['secondary_color'] ?? '#ff5b3d' }}" oninput="twSetAccent(this.value,null)">
          <input type="text" class="tw-color-hex" id="tw-accent-hex" value="{{ $settings['secondary_color'] ?? '#ff5b3d' }}" maxlength="7" oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){twSetAccent(this.value,null);document.getElementById('tw-accent-custom').value=this.value}">
        </div>
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Header</div>
        <div style="display:flex;flex-direction:column;gap:7px;margin-top:6px">
          <div>
            <div class="tw-sublabel">Fondo del header</div>
            <div class="tw-color-row" style="margin-top:0">
              <input type="color" class="tw-color-input" id="tw-header-bg" value="{{ $settings['header_bg_color'] ?? '#ffffff' }}" oninput="twApplyHeaderBg(this.value)">
              <input type="text" class="tw-color-hex" id="tw-header-bg-hex" value="{{ $settings['header_bg_color'] ?? '#ffffff' }}" maxlength="7" oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){twApplyHeaderBg(this.value);document.getElementById('tw-header-bg').value=this.value}">
            </div>
          </div>
          <div>
            <div class="tw-sublabel">Texto del header</div>
            <div class="tw-color-row" style="margin-top:0">
              <input type="color" class="tw-color-input" id="tw-header-text" value="{{ $settings['header_text_color'] ?? '#111827' }}" oninput="twApplyHeaderText(this.value)">
              <input type="text" class="tw-color-hex" id="tw-header-text-hex" value="{{ $settings['header_text_color'] ?? '#111827' }}" maxlength="7" oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){twApplyHeaderText(this.value);document.getElementById('tw-header-text').value=this.value}">
            </div>
          </div>
        </div>
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Footer</div>
        <div style="display:flex;flex-direction:column;gap:7px;margin-top:6px">
          <div>
            <div class="tw-sublabel">Fondo del footer</div>
            <div class="tw-color-row" style="margin-top:0">
              <input type="color" class="tw-color-input" id="tw-footer-bg" value="{{ $settings['footer_bg_color'] ?? '#111827' }}" oninput="const f=document.querySelector('.footer');if(f)f.style.background=this.value">
              <input type="text" class="tw-color-hex" id="tw-footer-bg-hex" value="{{ $settings['footer_bg_color'] ?? '#111827' }}" maxlength="7" oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){const f=document.querySelector('.footer');if(f)f.style.background=this.value;document.getElementById('tw-footer-bg').value=this.value}">
            </div>
          </div>
          <div>
            <div class="tw-sublabel">Texto del footer</div>
            <div class="tw-color-row" style="margin-top:0">
              <input type="color" class="tw-color-input" id="tw-footer-text" value="{{ $settings['footer_text_color'] ?? '#9ca3af' }}" oninput="const f=document.querySelector('.footer');if(f)f.style.color=this.value">
              <input type="text" class="tw-color-hex" id="tw-footer-text-hex" value="{{ $settings['footer_text_color'] ?? '#9ca3af' }}" maxlength="7" oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){const f=document.querySelector('.footer');if(f)f.style.color=this.value;document.getElementById('tw-footer-text').value=this.value}">
            </div>
          </div>
        </div>
      </div>

    </div>{{-- /colores --}}

    {{-- ══ TAB: PORTADA ══ --}}
    <div class="tw-panel" id="tw-panel-portada">

      <div>
        <div class="tw-section">Hero principal</div>
        <div style="display:flex;flex-direction:column;gap:7px;margin-top:6px">
          <div>
            <div class="tw-sublabel">Título</div>
            <input class="tw-input" id="tw-hero-title" value="{{ $heroTitle }}" placeholder="Tu tienda online">
          </div>
          <div>
            <div class="tw-sublabel">Subtítulo</div>
            <textarea class="tw-textarea" id="tw-hero-sub" placeholder="Descripción breve…">{{ $settings['hero_subtitle'] ?? '' }}</textarea>
          </div>
          <div>
            <div class="tw-sublabel">Badge / etiqueta</div>
            <input class="tw-input" id="tw-hero-badge" value="{{ $settings['hero_badge'] ?? '' }}" placeholder="🚀 Novedades">
          </div>
          <div>
            <div class="tw-sublabel">Botón principal CTA</div>
            <input class="tw-input" id="tw-hero-cta1" value="{{ $settings['hero_cta1_text'] ?? 'Ver catálogo' }}" placeholder="Ver catálogo">
          </div>
          <div>
            <div class="tw-sublabel">Color de fondo hero</div>
            <div class="tw-color-row" style="margin-top:0">
              <input type="color" class="tw-color-input" id="tw-hero-bg" value="{{ $settings['hero_bg_color'] ?? '#0e0e10' }}" oninput="document.querySelector('.ec-hero')&&(document.querySelector('.ec-hero').style.background=this.value)">
              <input type="text" class="tw-color-hex" id="tw-hero-bg-hex" value="{{ $settings['hero_bg_color'] ?? '#0e0e10' }}" maxlength="7" oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){if(document.querySelector('.ec-hero'))document.querySelector('.ec-hero').style.background=this.value;document.getElementById('tw-hero-bg').value=this.value}">
            </div>
          </div>
          <div>
            <div class="tw-sublabel">Imagen de fondo (URL)</div>
            <input class="tw-input" id="tw-hero-image" value="{{ $settings['hero_image'] ?? '' }}" placeholder="https://...imagen.jpg">
          </div>
          <div>
            <div class="tw-sublabel">Alineación contenido</div>
            <div class="tw-pills">
              @foreach(['left'=>'Izq','center'=>'Centro','right'=>'Der'] as $av=>$al)
              <button class="tw-pill {{ ($settings['hero_align'] ?? 'left') === $av ? 'active' : '' }}" onclick="twPill(this);document.getElementById('tw-hero-align').value='{{$av}}'">{{ $al }}</button>
              @endforeach
            </div>
            <input type="hidden" id="tw-hero-align" value="{{ $settings['hero_align'] ?? 'left' }}">
          </div>
          <div>
            <div class="tw-sublabel">Altura del hero</div>
            <div class="tw-pills">
              @foreach(['small'=>'Chico','medium'=>'Medio','large'=>'Grande','full'=>'Full'] as $hv=>$hl)
              <button class="tw-pill {{ ($settings['hero_height'] ?? 'medium') === $hv ? 'active' : '' }}" onclick="twPill(this);document.getElementById('tw-hero-height').value='{{$hv}}'">{{ $hl }}</button>
              @endforeach
            </div>
            <input type="hidden" id="tw-hero-height" value="{{ $settings['hero_height'] ?? 'medium' }}">
          </div>
        </div>
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Trust strip</div>
        <div style="display:flex;flex-direction:column;gap:6px;margin-top:6px">
          @foreach([[1,'🚚','Envío rápido'],[2,'🔒','Pago seguro'],[3,'✅','Garantía'],[4,'💬','Soporte 24/7']] as [$n,$ico,$txt])
          <div style="display:flex;gap:6px">
            <input class="tw-input" id="tw-trust-icon-{{$n}}" value="{{ $settings['trust_icon_'.$n] ?? $ico }}" style="width:48px;text-align:center;flex-shrink:0" placeholder="🚚">
            <input class="tw-input" id="tw-trust-text-{{$n}}" value="{{ $settings['trust_text_'.$n] ?? $txt }}" placeholder="Texto beneficio">
          </div>
          @endforeach
        </div>
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Banners de sección</div>
        <div style="display:flex;flex-direction:column;gap:6px;margin-top:6px">
          <div>
            <div class="tw-sublabel">Banner 1 — Título</div>
            <input class="tw-input" id="tw-banner1-title" value="{{ $settings['banner1_title'] ?? '' }}" placeholder="Nuevos productos">
          </div>
          <div>
            <div class="tw-sublabel">Banner 1 — Descripción</div>
            <input class="tw-input" id="tw-banner1-sub" value="{{ $settings['banner1_sub'] ?? '' }}" placeholder="Descubre lo último">
          </div>
          <div>
            <div class="tw-sublabel">Banner 2 — Título</div>
            <input class="tw-input" id="tw-banner2-title" value="{{ $settings['banner2_title'] ?? '' }}" placeholder="Ofertas especiales">
          </div>
          <div>
            <div class="tw-sublabel">Banner 2 — Descripción</div>
            <input class="tw-input" id="tw-banner2-sub" value="{{ $settings['banner2_sub'] ?? '' }}" placeholder="Precios imperdibles">
          </div>
        </div>
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Secciones visibles</div>
        <div style="display:flex;flex-direction:column;gap:9px;margin-top:6px">
          @foreach([
            ['show_flash_sale','Flash sale / Oferta del día'],
            ['show_testimonials','Testimonios'],
            ['show_newsletter','Newsletter'],
            ['show_trust_strip','Iconos de confianza'],
          ] as [$key,$label])
          <div class="tw-row">
            <span class="tw-label" style="font-size:12px">{{ $label }}</span>
            <label class="tw-toggle">
              <input type="checkbox" id="tw-{{$key}}" {{ ($settings[$key] ?? '1') !== '0' ? 'checked' : '' }}>
              <div class="tw-toggle-track"></div>
              <div class="tw-toggle-thumb"></div>
            </label>
          </div>
          @endforeach
        </div>
      </div>

    </div>{{-- /portada --}}

    {{-- ══ TAB: CATÁLOGO ══ --}}
    <div class="tw-panel" id="tw-panel-catalogo">

      <div>
        <div class="tw-section">Grid de productos</div>
        <div style="display:flex;flex-direction:column;gap:7px;margin-top:6px">
          <div>
            <div class="tw-sublabel">Título de la sección</div>
            <input class="tw-input" id="tw-catalog-title" value="{{ $settings['catalog_section_title'] ?? 'Nuestros productos' }}" placeholder="Nuestros productos">
          </div>
          <div>
            <div class="tw-sublabel">Estilo de tarjeta</div>
            <select class="tw-select" id="tw-card-style">
              @foreach(['minimal'=>'Minimal','classic'=>'Classic','card'=>'Card','bold'=>'Bold','ghost'=>'Ghost'] as $cs_k=>$cs_l)
              <option value="{{ $cs_k }}" {{ ($settings['card_style'] ?? 'minimal') === $cs_k ? 'selected' : '' }}>{{ $cs_l }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <div class="tw-sublabel">Columnas escritorio</div>
            <div class="tw-pills">
              @foreach(['2','3','4'] as $cv)
              <button class="tw-pill {{ ($settings['catalog_cols_desktop'] ?? '3') === $cv ? 'active' : '' }}" onclick="twPill(this);document.getElementById('tw-cols-desktop').value='{{$cv}}'">{{ $cv }}</button>
              @endforeach
            </div>
            <input type="hidden" id="tw-cols-desktop" value="{{ $settings['catalog_cols_desktop'] ?? '3' }}">
          </div>
          <div>
            <div class="tw-sublabel">Columnas móvil</div>
            <div class="tw-pills">
              @foreach(['1'=>'1','2'=>'2'] as $cv=>$cl)
              <button class="tw-pill {{ ($settings['catalog_cols_mobile'] ?? '2') === $cv ? 'active' : '' }}" onclick="twPill(this);document.getElementById('tw-cols-mobile').value='{{$cv}}'">{{ $cl }}</button>
              @endforeach
            </div>
            <input type="hidden" id="tw-cols-mobile" value="{{ $settings['catalog_cols_mobile'] ?? '2' }}">
          </div>
        </div>
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Filtros del catálogo</div>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:6px">
          @foreach([
            ['catalog_filter_price','Filtro por precio'],
            ['catalog_filter_cats','Filtro por categorías'],
            ['catalog_filter_sale','Filtro "En oferta"'],
            ['catalog_filter_search','Campo de búsqueda'],
          ] as [$key,$label])
          <div class="tw-row">
            <span class="tw-label" style="font-size:12px">{{ $label }}</span>
            <label class="tw-toggle">
              <input type="checkbox" id="tw-{{ $key }}" {{ ($settings[$key] ?? '1') !== '0' ? 'checked' : '' }}>
              <div class="tw-toggle-track"></div>
              <div class="tw-toggle-thumb"></div>
            </label>
          </div>
          @endforeach
        </div>
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Opciones de producto</div>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:6px">
          @foreach([
            ['catalog_show_ratings','Mostrar estrellas'],
            ['catalog_quick_view','Quick View'],
            ['catalog_show_sku','Mostrar SKU'],
            ['catalog_show_stock','Mostrar stock'],
            ['btn_show_icon','Ícono en botón carrito'],
          ] as [$key,$label])
          <div class="tw-row">
            <span class="tw-label" style="font-size:12px">{{ $label }}</span>
            <label class="tw-toggle">
              <input type="checkbox" id="tw-{{ $key }}" {{ ($settings[$key] ?? ($key==='catalog_quick_view'||$key==='btn_show_icon'?'1':'0')) !== '0' ? 'checked' : '' }}>
              <div class="tw-toggle-track"></div>
              <div class="tw-toggle-thumb"></div>
            </label>
          </div>
          @endforeach
        </div>
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Badges</div>
        <div class="tw-badge-grid" style="margin-top:6px">
          @foreach([
            ['catalog_badge_sale','Oferta','OFERTA'],
            ['catalog_badge_new','Nuevo','NUEVO'],
            ['catalog_badge_featured','Destacado','DESTACADO'],
            ['catalog_badge_sold_out','Agotado','AGOTADO'],
          ] as [$key,$label,$def])
          <div>
            <div class="tw-sublabel">{{ $label }}</div>
            <input class="tw-input" id="tw-{{ $key }}" value="{{ $settings[$key] ?? $def }}" placeholder="{{ $def }}">
          </div>
          @endforeach
        </div>
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Textos de botones</div>
        <div style="display:flex;flex-direction:column;gap:6px;margin-top:6px">
          <div>
            <div class="tw-sublabel">Botón "Agregar al carrito"</div>
            <input class="tw-input" id="tw-btn-cart" value="{{ $settings['btn_cart_text'] ?? 'Agregar al carrito' }}" placeholder="Agregar al carrito">
          </div>
          <div>
            <div class="tw-sublabel">Botón "Cotizar"</div>
            <input class="tw-input" id="tw-btn-quote" value="{{ $settings['btn_quote_text'] ?? 'Cotizar' }}" placeholder="Cotizar">
          </div>
        </div>
      </div>

    </div>{{-- /catalogo --}}

    {{-- ══ TAB: LAYOUT ══ --}}
    <div class="tw-panel" id="tw-panel-layout">

      <div>
        <div class="tw-section">Radio de bordes</div>
        <div style="display:flex;align-items:center;gap:10px;margin-top:8px">
          <input type="range" class="tw-slider" id="tw-radius" min="0" max="24" value="{{ $settings['border_radius'] ?? 12 }}" step="2" oninput="twApplyRadius(this.value)">
          <span class="tw-slider-val" id="tw-radius-val">{{ $settings['border_radius'] ?? 12 }}px</span>
        </div>
        <div class="tw-pills" style="margin-top:8px">
          <button class="tw-pill" onclick="document.getElementById('tw-radius').value=0;twApplyRadius(0)">Cuadrado</button>
          <button class="tw-pill" onclick="document.getElementById('tw-radius').value=10;twApplyRadius(10)">Redondeado</button>
          <button class="tw-pill" onclick="document.getElementById('tw-radius').value=24;twApplyRadius(24)">Píldora</button>
        </div>
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Altura del header (px)</div>
        <input class="tw-input" type="number" id="tw-header-height" min="48" max="200" value="{{ $settings['header_height'] ?? 64 }}" style="margin-top:6px">
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Densidad del grid</div>
        <div class="tw-pills" style="margin-top:8px">
          <button class="tw-pill" onclick="twSetDensity('compact',this)">Compact</button>
          <button class="tw-pill active" onclick="twSetDensity('regular',this)">Regular</button>
          <button class="tw-pill" onclick="twSetDensity('comfy',this)">Comfy</button>
        </div>
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Elementos flotantes</div>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:6px">
          @foreach([
            ['float_cart_show','Carrito flotante'],
            ['float_wa_show','Botón WhatsApp'],
          ] as [$key,$label])
          <div class="tw-row">
            <span class="tw-label" style="font-size:12px">{{ $label }}</span>
            <label class="tw-toggle">
              <input type="checkbox" id="tw-{{ $key }}" {{ ($settings[$key] ?? '1') !== '0' ? 'checked' : '' }}>
              <div class="tw-toggle-track"></div>
              <div class="tw-toggle-thumb"></div>
            </label>
          </div>
          @endforeach
          <div>
            <div class="tw-sublabel">Tooltip botón WA</div>
            <input class="tw-input" id="tw-wa-tooltip" value="{{ $settings['float_wa_tooltip'] ?? '¿Necesitas ayuda?' }}" placeholder="¿Necesitas ayuda?">
          </div>
        </div>
      </div>

    </div>{{-- /layout --}}

    {{-- ══ TAB: SISTEMA ══ --}}
    <div class="tw-panel" id="tw-panel-sistema">

      <div>
        <div class="tw-section">Modo de venta</div>
        <div style="display:flex;flex-direction:column;gap:6px;margin-top:6px">
          @foreach(['direct'=>'🛒 Compra directa','quote_only'=>'📋 Solo cotizaciones'] as $sv=>$sl)
          <label style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:#27272a;border-radius:8px;cursor:pointer;border:1px solid {{ ($settings['store_mode'] ?? 'direct') === $sv ? '#6c63ff' : '#3f3f46' }}">
            <input type="radio" name="tw-store-mode" value="{{ $sv }}" {{ ($settings['store_mode'] ?? 'direct') === $sv ? 'checked' : '' }} style="accent-color:#6c63ff">
            <span style="font-size:12.5px;color:#d4d4d8">{{ $sl }}</span>
          </label>
          @endforeach
        </div>
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Envío</div>
        <div style="display:flex;flex-direction:column;gap:7px;margin-top:6px">
          <div class="tw-row">
            <span class="tw-label" style="font-size:12px">Activar envío</span>
            <label class="tw-toggle">
              <input type="checkbox" id="tw-shipping-enabled" {{ ($settings['shipping_enabled'] ?? '0') !== '0' ? 'checked' : '' }}>
              <div class="tw-toggle-track"></div>
              <div class="tw-toggle-thumb"></div>
            </label>
          </div>
          <div>
            <div class="tw-sublabel">Costo de envío ({{ $currency }})</div>
            <input class="tw-input" type="number" id="tw-shipping-cost" min="0" step="0.5" value="{{ $settings['shipping_cost'] ?? 0 }}" placeholder="0">
          </div>
          <div>
            <div class="tw-sublabel">Gratis desde ({{ $currency }}, 0 = siempre)</div>
            <input class="tw-input" type="number" id="tw-shipping-free" min="0" step="1" value="{{ $settings['shipping_free_from'] ?? 0 }}" placeholder="0">
          </div>
          <div class="tw-row">
            <span class="tw-label" style="font-size:12px">Requerir dirección</span>
            <label class="tw-toggle">
              <input type="checkbox" id="tw-require-address" {{ ($settings['require_address'] ?? '0') !== '0' ? 'checked' : '' }}>
              <div class="tw-toggle-track"></div>
              <div class="tw-toggle-thumb"></div>
            </label>
          </div>
        </div>
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Métodos de pago aceptados</div>
        <div class="tw-payment-checks" style="margin-top:6px">
          @php $savedPay = (array)(json_decode($settings['accepted_payments'] ?? '[]', true) ?: []); @endphp
          @foreach([['yape','🟣 Yape'],['plin','🔵 Plin'],['efectivo','💵 Efectivo'],['transferencia','🏦 Transferencia'],['tarjeta','💳 Tarjeta'],['qr','📲 Pago QR'],['contra_entrega','🚚 Contra entrega']] as [$pk,$pl])
          <label class="tw-payment-check">
            <input type="checkbox" name="tw-pay[]" value="{{ $pk }}" {{ in_array($pk,$savedPay) ? 'checked' : '' }}>
            <span>{{ $pl }}</span>
          </label>
          @endforeach
        </div>
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">SEO</div>
        <div style="display:flex;flex-direction:column;gap:7px;margin-top:6px">
          <div>
            <div class="tw-sublabel">Título SEO</div>
            <input class="tw-input" id="tw-seo-title" value="{{ $settings['seo_title'] ?? $project->name }}" placeholder="Mi tienda — Lima">
          </div>
          <div>
            <div class="tw-sublabel">Descripción SEO</div>
            <textarea class="tw-textarea" id="tw-seo-desc" placeholder="Descripción para Google…">{{ $settings['seo_description'] ?? '' }}</textarea>
          </div>
        </div>
      </div>

    </div>{{-- /sistema --}}

    {{-- ══ TAB: FOOTER ══ --}}
    <div class="tw-panel" id="tw-panel-footer">

      <div>
        <div class="tw-section">Contenido footer</div>
        <div style="display:flex;flex-direction:column;gap:7px;margin-top:6px">
          <div>
            <div class="tw-sublabel">Eslogan bajo el logo</div>
            <input class="tw-input" id="tw-footer-tagline2" value="{{ $settings['footer_tagline'] ?? '' }}" placeholder="Tu tienda de confianza">
          </div>
          <div>
            <div class="tw-sublabel">Texto copyright</div>
            <input class="tw-input" id="tw-copyright2" value="{{ $footerCopyright }}" placeholder="© 2026 Mi Tienda">
          </div>
          <div>
            <div class="tw-sublabel">Alto del logo en footer (px)</div>
            <input class="tw-input" type="number" id="tw-footer-logo-height" min="24" max="200" value="{{ $settings['footer_logo_height'] ?? 60 }}" placeholder="60">
          </div>
        </div>
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Secciones del footer</div>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:6px">
          @foreach([
            ['show_footer_social','Mostrar redes sociales'],
            ['show_footer_cats','Mostrar categorías'],
            ['show_footer_newsletter','Mostrar newsletter'],
            ['show_footer_benefits','Mostrar barra beneficios'],
            ['show_footer_address','Mostrar dirección'],
          ] as [$key,$label])
          <div class="tw-row">
            <span class="tw-label" style="font-size:12px">{{ $label }}</span>
            <label class="tw-toggle">
              <input type="checkbox" id="tw-{{ $key }}" {{ ($settings[$key] ?? '1') !== '0' ? 'checked' : '' }}>
              <div class="tw-toggle-track"></div>
              <div class="tw-toggle-thumb"></div>
            </label>
          </div>
          @endforeach
        </div>
      </div>
      <div class="tw-divider"></div>

      <div>
        <div class="tw-section">Textos del sistema</div>
        <div style="display:flex;flex-direction:column;gap:6px;margin-top:6px">
          @foreach([
            ['tw-txt-cart','cart_title','Tu carrito'],
            ['tw-txt-empty','cart_empty_msg','Tu carrito está vacío'],
            ['tw-txt-search','search_placeholder','Buscar productos...'],
            ['tw-txt-nocat','no_results_msg','No se encontraron productos'],
          ] as [$id,$key,$def])
          <div>
            <div class="tw-sublabel">{{ ucfirst(str_replace('_',' ',str_replace(['cart_','search_','no_results_'],['','',''],$key))) ?: $def }}</div>
            <input class="tw-input" id="{{ $id }}" value="{{ $settings[$key] ?? $def }}" placeholder="{{ $def }}">
          </div>
          @endforeach
        </div>
      </div>

    </div>{{-- /footer --}}

    {{-- ══ TAB: NAV ══ --}}
    <div class="tw-panel" id="tw-panel-nav">
      <div>
        <div class="tw-section">Ir a página</div>
        <div style="display:flex;flex-direction:column;gap:4px;margin-top:8px">
          <button class="tw-nav-btn" onclick="twGo('home')">🏠 Home</button>
          <button class="tw-nav-btn" onclick="twGo('catalog')">📋 Listado (PLP)</button>
          <button class="tw-nav-btn" onclick="twGo('categories')">📂 Categorías</button>
          <button class="tw-nav-btn" onclick="twGoProduct()">🏷 Detalle producto (PDP)</button>
          <button class="tw-nav-btn" onclick="twGo('cart')">🛒 Carrito</button>
          <button class="tw-nav-btn" onclick="twGo('checkout')">💳 Checkout</button>
        </div>
      </div>
      <div class="tw-divider"></div>
      <div>
        <div class="tw-section">Admin</div>
        <a href="{{ route('settings.design') }}" target="_blank" class="tw-nav-btn" style="text-decoration:none">
          ⚙️ Abrir panel de diseño completo
        </a>
        <a href="{{ url('/'.$project->slug) }}" target="_blank" class="tw-nav-btn" style="text-decoration:none">
          🔗 Ver tienda en nueva pestaña
        </a>
      </div>
    </div>{{-- /nav --}}

  </div>{{-- /tw-panels --}}

  <div class="tw-foot">
    <button class="tw-save" id="tw-save-btn" onclick="twSave()">💾 Guardar cambios</button>
    <div class="tw-save-msg" id="tw-save-msg"></div>
    <button class="tw-reset" onclick="twReset()">↺ Restablecer valores</button>
  </div>
</div>

<script>
// ── TWEAKS ENGINE v3 ─────────────────────────────────────────
const TW_DESIGN_URL  = '{{ route("settings.design.update") }}';
const TW_UPLOAD_URL  = '{{ route("settings.upload-logo") }}';
const TW_CSRF        = '{{ csrf_token() }}';

function twTogglePanel() {
  const panel = document.getElementById('tweaks-panel');
  const isOpen = panel.classList.toggle('open');
  document.body.classList.toggle('tw-open', isOpen);
  document.getElementById('tw-fab-icon-open').style.display   = isOpen ? '' : 'none';
  document.getElementById('tw-fab-icon-closed').style.display = isOpen ? 'none' : '';
}

// ── AUTO-SAVE con debounce ────────────────────────────────────
let twSaveTimer = null;

function twSetAutoStatus(state) {
  const dot   = document.getElementById('tw-autosave-dot');
  const label = document.getElementById('tw-autosave-label');
  if (!dot || !label) return;
  dot.className   = state;
  label.className = state;
  const texts = { saving:'guardando…', saved:'guardado ✓', error:'error ✗', '':'auto' };
  label.textContent = texts[state] ?? 'auto';
  if (state === 'saved') setTimeout(() => twSetAutoStatus(''), 2500);
}

function twScheduleSave() {
  clearTimeout(twSaveTimer);
  twSetAutoStatus('saving');
  twSaveTimer = setTimeout(() => twSave(true), 650);
}

// Observar todos los inputs/selects/textareas/checkboxes del panel
document.addEventListener('DOMContentLoaded', function() {
  // Abrir panel
  const panel = document.getElementById('tweaks-panel');
  panel.classList.add('open');
  document.body.classList.add('tw-open');
  document.getElementById('tw-fab-icon-open').style.display   = '';
  document.getElementById('tw-fab-icon-closed').style.display = 'none';

  // Inicializar variables CSS con valores guardados
  document.documentElement.style.setProperty('--logo-h',   ({{ $settings['logo_height']   ?? 40 }})+'px');
  document.documentElement.style.setProperty('--header-h', ({{ $settings['header_height'] ?? 64 }})+'px');
  // Footer logo height inicial
  const _flh = {{ $settings['footer_logo_height'] ?? 60 }};
  document.querySelectorAll('.fc-logo img').forEach(el => el.style.height = _flh + 'px');

  // Live-preview: aplicar cambios al DOM inmediatamente al editar
  const twLiveMap = {
    'tw-logo-height':      v => document.documentElement.style.setProperty('--logo-h', v+'px'),
    'tw-footer-logo-height': v => { document.querySelectorAll('.fc-logo img').forEach(el => el.style.height = v+'px'); },
    'tw-header-height':    v => document.documentElement.style.setProperty('--header-h', v+'px'),
    'tw-radius':           v => twApplyRadius(v),
    'tw-font-display':     () => twApplyFont(),
    'tw-font-body':        () => twApplyFont(),
    'tw-primary-custom':   v => twSetPrimary(v, null),
    'tw-accent-custom':    v => twSetAccent(v, null),
    'tw-header-bg':        v => { twApplyHeaderBg(v); document.getElementById('tw-header-bg-hex').value=v; },
    'tw-header-text':      v => { twApplyHeaderText(v); document.getElementById('tw-header-text-hex').value=v; },
    'tw-footer-bg':        v => { const f=document.querySelector('.footer'); if(f) f.style.background=v; document.getElementById('tw-footer-bg-hex').value=v; },
    'tw-footer-text':      v => { const f=document.querySelector('.footer'); if(f) f.style.color=v; document.getElementById('tw-footer-text-hex').value=v; },
    'tw-dark':             () => twApplyDark(),
    'tw-announcement':     v => { document.querySelectorAll('.topbar-text,.announcement-text').forEach(el=>el.textContent=v); },
    'tw-store-name':       v => { document.querySelectorAll('.logo-name,.store-name').forEach(el=>el.textContent=v); },
    'tw-hero-title':       v => { document.querySelectorAll('.hero-title,.ec-hero h1').forEach(el=>el.textContent=v); },
    'tw-hero-sub':         v => { document.querySelectorAll('.hero-sub,.ec-hero p').forEach(el=>el.textContent=v); },
    'tw-hero-cta1':        v => { document.querySelectorAll('.hero-cta1,.ec-hero .btn-primary').forEach(el=>el.textContent=v); },
    'tw-hero-bg':          v => { document.querySelectorAll('.ec-hero,.hero').forEach(el=>el.style.background=v); },
  };

  const container = document.getElementById('tweaks-panel');
  container.addEventListener('input', function(e) {
    const fn = twLiveMap[e.target.id];
    if (fn) fn(e.target.value);
    twOnFieldChange(e);
  });
  container.addEventListener('change', function(e) {
    const fn = twLiveMap[e.target.id];
    if (fn) fn(e.target.value);
    twOnFieldChange(e);
  });
});

function twOnFieldChange(e) {
  // Ignorar el botón de guardar y el file input (upload maneja su propio flujo)
  const tag = e.target.tagName;
  const type = e.target.type;
  if (type === 'file') return;
  if (e.target.id === 'tw-save-btn') return;
  twScheduleSave();
}

function twTab(name, el) {
  document.querySelectorAll('.tw-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tw-panel').forEach(p => p.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('tw-panel-'+name).classList.add('active');
}

function twApplyDark() {
  const dark = document.getElementById('tw-dark').checked;
  if (dark) document.documentElement.setAttribute('data-theme','dark');
  else document.documentElement.removeAttribute('data-theme');
}

function twSetPrimary(hex, el) {
  document.documentElement.style.setProperty('--primary', hex);
  const lum = twLum(hex);
  document.documentElement.style.setProperty('--primary-ink', lum < 0.35 ? '#ffffff' : '#111827');
  document.querySelectorAll('#tw-primary-swatches .tw-swatch').forEach(s => s.classList.remove('active'));
  if (el) el.classList.add('active');
  const pc = document.getElementById('tw-primary-custom');
  const ph = document.getElementById('tw-primary-hex');
  if(pc) pc.value = hex;
  if(ph) ph.value = hex;
}

function twSetAccent(hex, el) {
  document.documentElement.style.setProperty('--accent', hex);
  document.querySelectorAll('#tw-accent-swatches .tw-swatch').forEach(s => s.classList.remove('active'));
  if (el) el.classList.add('active');
  const ac = document.getElementById('tw-accent-custom');
  const ah = document.getElementById('tw-accent-hex');
  if(ac) ac.value = hex;
  if(ah) ah.value = hex;
}

function twApplyHeaderBg(hex) {
  const el = document.querySelector('.header');
  if(el) el.style.background = hex;
  const h = document.getElementById('tw-header-bg-hex');
  if(h) h.value = hex;
}
function twApplyHeaderText(hex) {
  const el = document.querySelector('.header');
  if(el) el.style.color = hex;
  const h = document.getElementById('tw-header-text-hex');
  if(h) h.value = hex;
}

function twApplyRadius(val) {
  const v = parseInt(val);
  document.documentElement.style.setProperty('--radius-sm',  Math.max(0,v-4)+'px');
  document.documentElement.style.setProperty('--radius-md',  v+'px');
  document.documentElement.style.setProperty('--radius-lg',  (v+4)+'px');
  document.documentElement.style.setProperty('--radius-xl',  (v+8)+'px');
  document.getElementById('tw-radius-val').textContent = v+'px';
}

function twApplyFont() {
  const fd = document.getElementById('tw-font-display').value;
  const fb = document.getElementById('tw-font-body').value;
  document.documentElement.style.setProperty('--font-display', fd);
  document.documentElement.style.setProperty('--font-body', fb);
}

function twSetDensity(val, el) {
  const gap = val==='compact'?'10px':val==='comfy'?'22px':'14px';
  document.documentElement.style.setProperty('--grid-gap', gap);
  el.closest('.tw-pills').querySelectorAll('.tw-pill').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
}

function twPill(el) {
  el.closest('.tw-pills').querySelectorAll('.tw-pill').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
}

function twGo(page) {
  const store = document.querySelector('[x-data]')?._x_dataStack?.[0];
  if (store) { store.page = page; window.scrollTo({top:0,behavior:'smooth'}); }
}
function twGoProduct() {
  const store = document.querySelector('[x-data]')?._x_dataStack?.[0];
  if (!store) return;
  const first = window.EC_PRODUCTS?.[0];
  if (first) store.openProduct(first.id);
}

async function twUploadLogo(input, type) {
  const file = input.files[0];
  if (!file) return;
  const fd = new FormData();
  fd.append('file', file);
  fd.append('type', type);
  fd.append('_token', TW_CSRF);
  const res = await fetch(TW_UPLOAD_URL, { method:'POST', body: fd });
  const data = await res.json();
  if (!data.url) return;
  if (type === 'logo') {
    const wrap = document.getElementById('tw-logo-preview-wrap');
    wrap.innerHTML = '<img src="'+data.url+'" style="max-width:100%;max-height:60px;object-fit:contain;padding:4px">';
    document.getElementById('tw-logo-path').value = data.path;
  } else {
    const wrap = document.querySelector('#tw-panel-marca .tw-logo-preview + div > div:first-child');
    document.getElementById('tw-favi-path').value = data.path;
    const img = document.getElementById('tw-favi-img') || document.createElement('img');
    img.id = 'tw-favi-img';
    img.src = data.url;
    img.style = 'max-width:32px;max-height:32px;object-fit:contain';
    const ph = document.getElementById('tw-favi-placeholder');
    if(ph) ph.replaceWith(img);
  }
}

function twRemoveLogo() {
  document.getElementById('tw-logo-preview-wrap').innerHTML = '<span style="color:#52525b;font-size:12px">Sin logo</span>';
  document.getElementById('tw-logo-path').value = '';
}

function twVal(id, def='') {
  const el = document.getElementById(id);
  return el ? el.value : def;
}
function twChk(id) {
  const el = document.getElementById(id);
  return el ? (el.checked ? '1' : '0') : '0';
}
function twRadio(name) {
  const el = document.querySelector('input[name="'+name+'"]:checked');
  return el ? el.value : '';
}

async function twSave(auto = false) {
  const btn = document.getElementById('tw-save-btn');
  const msg = document.getElementById('tw-save-msg');
  if (!auto) { btn.disabled = true; btn.textContent = 'Guardando…'; }

  const primary = document.getElementById('tw-primary-custom').value
    || getComputedStyle(document.documentElement).getPropertyValue('--primary').trim();
  const accent  = document.getElementById('tw-accent-custom').value;

  const pays = Array.from(document.querySelectorAll('input[name="tw-pay[]"]:checked')).map(e=>e.value);
  const fontTitle = twVal('tw-font-display').replace(/['"]/g,'').split(',')[0].trim();
  const fontBody  = twVal('tw-font-body').replace(/['"]/g,'').split(',')[0].trim();

  const body = new URLSearchParams({
    _token: TW_CSRF,
    // Marca — claves exactas del SettingsController
    footer_tagline:           twVal('tw-tagline'),
    footer_copyright:         twVal('tw-copyright'),
    currency_symbol:          twVal('tw-currency'),
    whatsapp_msg:             twVal('tw-wa-msg'),
    announcement_text:        twVal('tw-announcement'),
    logo_url:                 twVal('tw-logo-path'),
    favicon_url:              twVal('tw-favi-path'),
    logo_height:              twVal('tw-logo-height','40'),
    font_title:               fontTitle,
    font_body:                fontBody,
    facebook_url:             twVal('tw-fb'),
    instagram_url:            twVal('tw-ig'),
    tiktok_url:               twVal('tw-tt'),
    youtube_url:              twVal('tw-yt'),
    twitter_url:              twVal('tw-tw'),
    linkedin_url:             twVal('tw-li'),
    // Colores
    primary_color:            primary,
    secondary_color:          accent,
    header_bg_color:          twVal('tw-header-bg','#ffffff'),
    header_text_color:        twVal('tw-header-text','#111827'),
    footer_bg_color:          twVal('tw-footer-bg','#111827'),
    footer_text_color:        twVal('tw-footer-text','#9ca3af'),
    // Portada
    hero_title:               twVal('tw-hero-title'),
    hero_subtitle:            twVal('tw-hero-sub'),
    hero_badge:               twVal('tw-hero-badge'),
    hero_cta1_text:           twVal('tw-hero-cta1'),
    hero_bg_color:            twVal('tw-hero-bg','#0e0e10'),
    hero_image:               twVal('tw-hero-image'),
    hero_align:               twVal('tw-hero-align','left'),
    hero_height:              twVal('tw-hero-height','medium'),
    trust_icon_1:             twVal('tw-trust-icon-1'),
    trust_text_1:             twVal('tw-trust-text-1'),
    trust_icon_2:             twVal('tw-trust-icon-2'),
    trust_text_2:             twVal('tw-trust-text-2'),
    trust_icon_3:             twVal('tw-trust-icon-3'),
    trust_text_3:             twVal('tw-trust-text-3'),
    trust_icon_4:             twVal('tw-trust-icon-4'),
    trust_text_4:             twVal('tw-trust-text-4'),
    banner1_title:            twVal('tw-banner1-title'),
    banner1_sub:              twVal('tw-banner1-sub'),
    banner2_title:            twVal('tw-banner2-title'),
    banner2_sub:              twVal('tw-banner2-sub'),
    show_flash_sale:          twChk('tw-show_flash_sale'),
    show_testimonials:        twChk('tw-show_testimonials'),
    show_newsletter:          twChk('tw-show_newsletter'),
    show_trust_strip:         twChk('tw-show_trust_strip'),
    // Catálogo
    catalog_section_title:    twVal('tw-catalog-title'),
    card_style:               twVal('tw-card-style'),
    catalog_cols_desktop:     twVal('tw-cols-desktop','3'),
    catalog_cols_mobile:      twVal('tw-cols-mobile','2'),
    catalog_filter_price:     twChk('tw-catalog_filter_price'),
    catalog_filter_cats:      twChk('tw-catalog_filter_cats'),
    catalog_filter_sale:      twChk('tw-catalog_filter_sale'),
    catalog_filter_search:    twChk('tw-catalog_filter_search'),
    catalog_show_ratings:     twChk('tw-catalog_show_ratings'),
    catalog_quick_view:       twChk('tw-catalog_quick_view'),
    catalog_show_sku:         twChk('tw-catalog_show_sku'),
    catalog_show_stock:       twChk('tw-catalog_show_stock'),
    btn_show_icon:            twChk('tw-btn_show_icon'),
    catalog_badge_sale:       twVal('tw-catalog_badge_sale','OFERTA'),
    catalog_badge_new:        twVal('tw-catalog_badge_new','NUEVO'),
    catalog_badge_featured:   twVal('tw-catalog_badge_featured','DESTACADO'),
    catalog_badge_sold_out:   twVal('tw-catalog_badge_sold_out','AGOTADO'),
    btn_cart_text:            twVal('tw-btn-cart','Agregar al carrito'),
    btn_quote_text:           twVal('tw-btn-quote','Cotizar'),
    // Layout
    border_radius:            twVal('tw-radius','12'),
    header_height:            twVal('tw-header-height','64'),
    float_cart_show:          twChk('tw-float_cart_show'),
    float_wa_show:            twChk('tw-float_wa_show'),
    float_wa_tooltip:         twVal('tw-wa-tooltip','¿Necesitas ayuda?'),
    // Sistema
    store_mode:               twRadio('tw-store-mode') || 'direct',
    shipping_enabled:         twChk('tw-shipping-enabled'),
    shipping_cost:            twVal('tw-shipping-cost','0'),
    shipping_free_from:       twVal('tw-shipping-free','0'),
    require_address:          twChk('tw-require-address'),
    seo_title:                twVal('tw-seo-title'),
    seo_description:          twVal('tw-seo-desc'),
    // Footer
    footer_logo_height:       twVal('tw-footer-logo-height','60'),
    footer_show_social:       twChk('tw-show_footer_social'),
    footer_show_categories:   twChk('tw-show_footer_cats'),
    footer_show_newsletter:   twChk('tw-show_footer_newsletter'),
    footer_show_benefits:     twChk('tw-show_footer_benefits'),
    footer_show_address:      twChk('tw-show_footer_address'),
    cart_title:               twVal('tw-txt-cart','Tu carrito'),
    cart_empty_msg:           twVal('tw-txt-empty','Tu carrito está vacío'),
    txt_search_placeholder:   twVal('tw-txt-search','Buscar productos...'),
    txt_no_results:           twVal('tw-txt-nocat','No se encontraron productos'),
  });
  // accepted_payments como array real (no JSON string)
  pays.forEach(p => body.append('accepted_payments[]', p));

  try {
    const res = await fetch(TW_DESIGN_URL, { method:'POST', body, headers:{'X-CSRF-TOKEN':TW_CSRF,'Accept':'application/json'} });
    if (res.ok) {
      if (auto) {
        twSetAutoStatus('saved');
      } else {
        btn.textContent = '✅ Guardado';
        msg.textContent = 'Cambios aplicados.';
        setTimeout(() => { btn.textContent = '💾 Guardar cambios'; btn.disabled=false; msg.textContent=''; }, 2500);
        twSetAutoStatus('saved');
      }
    } else {
      throw new Error('Error '+res.status);
    }
  } catch(e) {
    twSetAutoStatus('error');
    if (!auto) {
      btn.textContent = '💾 Guardar cambios';
      btn.disabled = false;
      msg.textContent = '❌ Error al guardar.';
    }
  }
}

function twReset() {
  if (!confirm('¿Restablecer estilos visuales?')) return;
  document.documentElement.removeAttribute('style');
  document.documentElement.removeAttribute('data-theme');
  const d = document.getElementById('tw-dark');
  if(d) d.checked = false;
  const r = document.getElementById('tw-radius');
  if(r) { r.value = 12; twApplyRadius(12); }
}

function twLum(hex) {
  const r=parseInt(hex.slice(1,3),16)/255, g=parseInt(hex.slice(3,5),16)/255, b=parseInt(hex.slice(5,7),16)/255;
  return 0.2126*r+0.7152*g+0.0722*b;
}
</script>
@endif{{-- /tweaks: solo visible si eres el dueño del proyecto --}}

<script>
const EC_PRODUCTS = @json($searchIndex);
const EC_CURRENCY = @json($currency);
const EC_SLUG     = @json($project->slug);
const EC_ORDER_ROUTE = @json(route('public.order', $project->slug));
const EC_CSRF    = @json(csrf_token());
const EC_CKFIELDS = @json($ckFields);
window.EC_CATS = {!! json_encode($categories->map(function($c) {
  return [
    'id'       => $c->id,
    'name'     => $c->name,
    'icon'     => $c->icon ?? '📦',
    'count'    => $c->products->count() + $c->children->sum(fn($s) => $s->products->count()),
    'subs'     => $c->children->count(),
    'subNames' => $c->children->pluck('name')->toArray(),
  ];
})->values()) !!};
@if($culqiEnabled)
const CULQI_KEY  = @json($culqiPublicKey);
@endif

function ecStore() {
  return {
    page: 'home',
    // Nav
    navOpen: null,
    navAllOpen: false,
    mobileMenuOpen: false,
    searchOpen: false,
    searchQ: '',
    searchFocused: false,
    // Hero
    heroIdx: 0,
    heroPaused: false,
    heroTimer: null,
    // Cart
    cart: [],
    cartHover: false,
    cartDrawerOpen: false,
    lightboxOpen: false,
    lightboxSrc: '',
    relatedProducts: [],
    // Filters
    filterCat: null,
    filterSubCat: null,
    filterInStock: false,
    filterOnSale: false,
    sortBy: 'default',
    priceMin: 0,
    priceMax: 0,
    maxPrice: 0,
    catSearch: '',
    catOpen: {},
    subExpanded: {},
    filterDrawerOpen: false,
    // Quote qty popup
    quotePopup: false,
    quotePopupProduct: null,
    quotePopupQty: 1,
    openQuotePopup(productId) {
      const p = EC_PRODUCTS.find(x => x.id == productId);
      if (!p) return;
      this.quotePopupProduct = p;
      this.quotePopupQty = 1;
      this.quotePopup = true;
    },
    confirmQuotePopup() {
      if (!this.quotePopupProduct) return;
      this.addToCartQty(this.quotePopupProduct.id, this.quotePopupQty);
      this.quotePopup = false;
    },
    // Checkout
    checkoutOpen: false,
    payMethod: '',
    form: (() => {
      const base = { fname:'',lname:'',phone:'',email:'',dni:'',department:'',district:'',address:'',address2:'',notes:'',payReference:'' };
      (EC_CKFIELDS.custom||[]).forEach(f => { if (f.enabled !== false) base['custom_'+f.key] = ''; });
      return base;
    })(),
    orderLoading: false,
    orderError: '',
    showPayConfirm: false,
    orderSuccess: false,
    orderSuccessMsg: '',
    orderCart: [],
    orderTotal_snapshot: 0,
    // PDP
    pdp: null,
    prevPage: 'catalog',
    // UI
    toasts: [],
    showBackTop: false,

    get allCats() {
      const cats = [];
      @foreach($categories as $cat)
      cats.push({ id: '{{ $cat->id }}', name: '{{ addslashes($cat->name) }}', parentId: null });
      @foreach($cat->children as $sub)
      cats.push({ id: '{{ $sub->id }}', name: '{{ addslashes($sub->name) }}', parentId: '{{ $cat->id }}' });
      @endforeach
      @endforeach
      return cats;
    },

    get activeFilterLabel() {
      if (this.filterSubCat) {
        const s = this.allCats.find(c => c.id == this.filterSubCat);
        return s ? s.name : 'Productos';
      }
      if (this.filterCat) {
        const c = this.allCats.find(c => c.id == this.filterCat);
        return c ? c.name : 'Productos';
      }
      return 'Todos los productos';
    },

    get hasActiveFilters() {
      return !!(this.filterCat || this.filterSubCat || this.filterInStock || this.filterOnSale || this.priceMin > 0 || this.priceMax < this.maxPrice);
    },

    get activeFilterCount() {
      let n = 0;
      if (this.filterCat) n++;
      if (this.filterSubCat) n++;
      if (this.filterInStock) n++;
      if (this.filterOnSale) n++;
      if (this.priceMin > 0 || this.priceMax < this.maxPrice) n++;
      return n;
    },

    get searchResults() {
      if (!this.searchQ) return [];
      const q = this.searchQ.toLowerCase();
      return EC_PRODUCTS.filter(p => p.name.toLowerCase().includes(q) || p.cat.toLowerCase().includes(q)).slice(0, 6);
    },

    get catalogProducts() {
      let out = EC_PRODUCTS.slice();
      // categoria / subcategoria
      if (this.filterSubCat) {
        out = out.filter(p => p.catId == this.filterSubCat);
      } else if (this.filterCat) {
        out = out.filter(p => p.catId == this.filterCat || p.parentId == this.filterCat);
      }
      // precio
      if (this.priceMin > 0)             out = out.filter(p => p.price >= this.priceMin);
      if (this.priceMax < this.maxPrice) out = out.filter(p => p.price <= this.priceMax);
      // disponibilidad
      if (this.filterInStock) out = out.filter(p => p.stock === null || p.stock > 0);
      if (this.filterOnSale)  out = out.filter(p => p.cp && p.cp > p.price);
      // búsqueda
      if (this.searchQ && this.page === 'catalog') {
        const q = this.searchQ.toLowerCase();
        out = out.filter(p => p.name.toLowerCase().includes(q));
      }
      switch(this.sortBy) {
        case 'price-asc':  out.sort((a,b) => a.price-b.price); break;
        case 'price-desc': out.sort((a,b) => b.price-a.price); break;
        case 'name':       out.sort((a,b) => a.name.localeCompare(b.name)); break;
      }
      return out;
    },

    get cartCount() { return this.cart.reduce((s,i) => s+i.qty, 0); },
    get subtotal()  { return this.cart.reduce((s,i) => s+i.price*i.qty, 0); },
    get orderTotal() {
      @if($shippingEnabled && $shippingCost > 0)
      const ship = this.subtotal >= {{ $shippingFreeFrom }} && {{ $shippingFreeFrom }} > 0 ? 0 : {{ $shippingCost }};
      return this.subtotal + ship;
      @else
      return this.subtotal;
      @endif
    },

    init() {
      this.loadCart();
      this.startHero();
      this.loadSavedForm();
      this.maxPrice = EC_PRODUCTS.reduce((m, p) => Math.max(m, p.price), 0);
      this.maxPrice = Math.ceil(this.maxPrice / 10) * 10 || 1000;
      this.priceMax = this.maxPrice;

      // Restaurar página al recargar (producto, carrito, etc.)
      try {
        const saved = JSON.parse(localStorage.getItem('ec_state_'+EC_SLUG) || 'null');
        if (saved && saved.page === 'product' && saved.productId) {
          const p = EC_PRODUCTS.find(x => x.id == saved.productId);
          if (p) {
            this.pdp = p;
            this.page = 'product';
            this.relatedProducts = EC_PRODUCTS.filter(x => x.catId === p.catId && x.id != p.id).slice(0, 4);
          }
        } else if (saved && saved.page) {
          this.page = saved.page;
        }
      } catch(e) {}

      // Bloquear scroll body cuando checkout está abierto
      this.$watch('checkoutOpen', (val) => {
        document.body.classList.toggle('ck-open', val);
      });

      // Guardar estado al cambiar de página
      this.$watch('page', (val) => {
        try {
          if (val === 'product' && this.pdp) {
            localStorage.setItem('ec_state_'+EC_SLUG, JSON.stringify({page:'product', productId: this.pdp.id}));
          } else {
            localStorage.setItem('ec_state_'+EC_SLUG, JSON.stringify({page: val}));
          }
        } catch(e) {}
      });

      // Listener para que la página de categorías navegue al catálogo filtrado
      window.addEventListener('ec-filter-cat', (e) => {
        this.filterCat = e.detail.catId;
        this.filterSubCat = null;
        this.page = 'catalog';
        window.scrollTo({top:0,behavior:'smooth'});
      });

      // Botón "Atrás" del browser — volver a la página anterior dentro de la SPA
      window.addEventListener('popstate', (e) => {
        if (e.state && e.state.page === 'product') {
          // Estaban viendo un producto, popstate significa que venían de antes
          const p = EC_PRODUCTS.find(x => x.id == e.state.productId);
          if (p) { this.pdp = p; this.page = 'product'; }
        } else {
          // Volver al catálogo/home anterior
          this.page = (e.state && e.state.page) ? e.state.page : (this.prevPage || 'catalog');
          this.pdp = null;
          window.scrollTo({top:0,behavior:'smooth'});
        }
      });
    },

    clearAllFilters() {
      this.filterCat     = null;
      this.filterSubCat  = null;
      this.filterInStock = false;
      this.filterOnSale  = false;
      this.priceMin      = 0;
      this.priceMax      = this.maxPrice;
      this.sortBy        = 'default';
      this.catSearch     = '';
    },

    startHero() {
      this.heroTimer = setInterval(() => {
        if (!this.heroPaused) this.heroIdx = (this.heroIdx + 1) % 2;
      }, 6000);
    },

    onScroll() {
      const y = window.scrollY;
      this.showBackTop = y > 320;
      const header = document.querySelector('.header');
      if (header) {
        if (y > 80 && y > (this._lastScrollY || 0)) {
          header.classList.add('header--hidden');
        } else {
          header.classList.remove('header--hidden');
        }
      }
      this._lastScrollY = y;
    },

    fmt(v) { return EC_CURRENCY + ' ' + Number(v).toFixed(2); },

    doSearch() {
      if (this.searchQ) { this.page = 'catalog'; this.searchFocused = false; }
    },

    addToCart(productId) {
      const p = EC_PRODUCTS.find(x => x.id == productId);
      if (!p) return;
      const existing = this.cart.find(i => i.id == productId);
      if (existing) { existing.qty++; }
      else { this.cart.push({ id: p.id, name: p.name, price: p.price, img: p.img, cat: p.cat, qty: 1 }); }
      this.saveCart();
      this.flyToCart(productId);
      this.showToast('✓ Agregado al carrito');
    },

    addToCartQty(productId, qty) {
      const q = parseInt(qty) || 1;
      const p = EC_PRODUCTS.find(x => x.id == productId);
      if (!p) return;
      const existing = this.cart.find(i => i.id == productId);
      if (existing) { existing.qty += q; }
      else { this.cart.push({ id: p.id, name: p.name, price: p.price, img: p.img, cat: p.cat, qty: q }); }
      this.saveCart();
      this.flyToCart(productId);
      this.showToast('✓ ' + q + 'x ' + p.name + ' agregado');
    },

    removeFromCart(id) { this.cart = this.cart.filter(i => i.id != id); this.saveCart(); },
    updateQty(id, delta) {
      const item = this.cart.find(i => i.id == id);
      if (!item) return;
      item.qty = item.qty + delta;
      if (item.qty <= 0) { this.removeFromCart(id); return; }
      this.saveCart();
    },

    saveCart() { try { localStorage.setItem('ec_cart_'+EC_SLUG, JSON.stringify(this.cart)); } catch(e){} },
    loadCart() { try { const d = localStorage.getItem('ec_cart_'+EC_SLUG); if(d) this.cart = JSON.parse(d); } catch(e){} },
    loadSavedForm() { try { const d = localStorage.getItem('ec_form_'+EC_SLUG); if(d) this.form = {...this.form, ...JSON.parse(d)}; } catch(e){} },
    saveForm() { try { localStorage.setItem('ec_form_'+EC_SLUG, JSON.stringify(this.form)); } catch(e){} },

    submitAndOpenWhatsApp() {
      if (!this.validateCheckoutForm()) return;
      this.showPayConfirm = true;
    },

    confirmPayAndSend() {
      this.showPayConfirm = false;
      // Abrir WhatsApp PRIMERO (acción directa del usuario, sin await)
      window.open('https://wa.me/{{ $quoteWa }}?text='+encodeURIComponent(this.buildWaMessage()), '_blank');
      // Guardar orden en BD en segundo plano
      const fullName = ((this.form.fname||'').trim()+' '+(this.form.lname||'').trim()).trim();
      const addr = [this.form.address, this.form.address2, this.form.district, this.form.department].filter(Boolean).join(', ');
      const customParts = (EC_CKFIELDS.custom||[])
        .filter(f => f.enabled !== false && this.form['custom_'+f.key])
        .map(f => f.label+': '+this.form['custom_'+f.key]);
      const notes = [this.form.notes, this.form.dni ? 'DNI/RUC: '+this.form.dni : '', ...customParts].filter(Boolean).join(' | ');
      const items = this.cart.map(i => ({ product_id: i.id, name: i.name, price: i.price, quantity: i.qty }));
      fetch(EC_ORDER_ROUTE, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': EC_CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({
          client_name: fullName, client_phone: this.form.phone, client_email: this.form.email,
          delivery_address: addr, notes, items,
          payment_method: this.payMethod || 'whatsapp',
          payment_reference: this.form.payReference || '',
          payment_proof: '',
        }),
      }).catch(() => {});
    },

    validateCheckoutForm() {
      const fullName = ((this.form.fname||'').trim()+' '+(this.form.lname||'').trim()).trim();
      if (!fullName) { this.orderError = 'Ingresa tu nombre completo.'; return false; }
      const rawPhone = (this.form.phone||'').replace(/\D/g,'');
      if (!rawPhone) { this.orderError = 'Ingresa tu número de celular.'; return false; }
      // Perú: celular = 9 dígitos comenzando en 9, o con prefijo país 51
      let digits = rawPhone;
      if (digits.startsWith('51') && digits.length === 11) digits = digits.slice(2);
      if (digits.startsWith('0051') && digits.length === 13) digits = digits.slice(4);
      if (digits.length !== 9) { this.orderError = 'El número debe tener 9 dígitos (ej: 987 654 321).'; return false; }
      if (!/^9/.test(digits)) { this.orderError = 'Los celulares peruanos empiezan con 9 (ej: 9XX XXX XXX).'; return false; }
      for (const f of (EC_CKFIELDS.custom||[])) {
        if (f.enabled !== false && f.required && !(this.form['custom_'+f.key]||'').trim()) {
          this.orderError = 'Completa el campo: '+f.label; return false;
        }
      }
      this.orderError = '';
      return true;
    },

    buildWaMessage() {
      const items = (this.orderSuccess ? this.orderCart : this.cart);
      const total = this.orderSuccess ? this.orderTotal_snapshot : this.orderTotal;
      const nombre = ((this.form.fname||'').trim()+' '+(this.form.lname||'').trim()).trim();
      const sep = '--------------------';
      const lineas = items.map(i => '  - '+i.name+' x'+i.qty+' ({{ $currency }} '+i.price.toFixed(2)+')').join('\n');
      let msg = '*NUEVO PEDIDO - {{ $project->name }}*\n';
      msg += sep+'\n';
      msg += '*PRODUCTOS:*\n'+lineas+'\n';
      msg += sep+'\n';
      msg += '*TOTAL: {{ $currency }} '+total.toFixed(2)+'*\n';
      msg += sep+'\n';
      if (this.payMethod) msg += '*PAGO:* '+this.payMethod.replace('bank_','Transferencia ').toUpperCase()+'\n'+sep+'\n';
      msg += '*DATOS DEL CLIENTE:*\n';
      if (nombre) msg += 'Nombre: '+nombre+'\n';
      if (this.form.phone) msg += 'Celular: '+this.form.phone+'\n';
      if (this.form.email) msg += 'Email: '+this.form.email+'\n';
      if (this.form.dni) msg += 'DNI/RUC: '+this.form.dni+'\n';
      const addr = [this.form.address, this.form.district, this.form.department].filter(Boolean).join(', ');
      if (addr) msg += 'Direccion: '+addr+'\n';
      if (this.form.address2) msg += 'Referencia: '+this.form.address2+'\n';
      if (this.form.notes) msg += 'Notas: '+this.form.notes+'\n';
      if (this.form.payReference) msg += 'Nro. operacion: '+this.form.payReference+'\n';
      (EC_CKFIELDS.custom||[]).forEach(f => {
        if (f.enabled === false) return;
        const val = this.form['custom_'+f.key];
        if (val) msg += f.label+': '+val+'\n';
      });
      msg += sep+'\n';
      msg += 'Adjunto mi comprobante de pago.';
      return msg;
    },

    openProduct(id) {
      const p = EC_PRODUCTS.find(x => x.id == id);
      if (!p) return;
      this.prevPage = this.page;
      this.pdp = p;
      this.page = 'product';
      // Productos relacionados: misma categoría, máx 4, excluyendo el actual
      this.relatedProducts = EC_PRODUCTS.filter(x => x.catId === p.catId && x.id != p.id).slice(0, 4);
      window.scrollTo({top:0,behavior:'smooth'});
      history.pushState({page: 'product', productId: id, prevPage: this.prevPage}, '', window.location.pathname);
      try { localStorage.setItem('ec_state_'+EC_SLUG, JSON.stringify({page:'product', productId: id})); } catch(e){}
    },

    flyToCart(productId) {
      const card = document.getElementById('cart-icon-btn');
      if (!card) return;
      const t = card.getBoundingClientRect();
      const fly = document.createElement('div');
      fly.className = 'fly-cart';
      fly.style.cssText = `left:${t.left+t.width/2-28}px;top:${t.top+t.height/2-28}px;width:56px;height:56px;background:var(--primary);`;
      document.body.appendChild(fly);
      requestAnimationFrame(() => {
        fly.style.left = (t.left+t.width/2-10)+'px';
        fly.style.top  = (t.top+t.height/2-10)+'px';
        fly.style.width = '20px'; fly.style.height = '20px'; fly.style.opacity = '0.2';
      });
      setTimeout(() => fly.remove(), 720);
    },

    showToast(msg, ms=2200) {
      const id = Date.now();
      this.toasts.push({ id, text: msg });
      setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, ms);
    },

    async quoteByWhatsapp() {
      if (!this.validateCheckoutForm()) return;
      const fullName = ((this.form.fname||'').trim()+' '+(this.form.lname||'').trim()).trim();
      const lines = this.cart.map(i => `• ${i.name} x${i.qty} = ${this.fmt(i.price*i.qty)}`).join('\n');
      const clientInfo = [
        fullName ? `*Cliente:* ${fullName}` : '',
        this.form.phone ? `*Celular:* ${this.form.phone}` : '',
        this.form.dni   ? `*DNI/RUC:* ${this.form.dni}`  : '',
      ].filter(Boolean).join('\n');
      const msg = `{{ $settings['quote_wa_msg'] ?? 'Hola, quiero cotizar:' }}\n${lines}\n*Total: ${this.fmt(this.subtotal)}*\n\n${clientInfo}`;
      try {
        await fetch('/{{ $project->slug }}/quote', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': EC_CSRF },
          body: JSON.stringify({
            client_name:       fullName || null,
            client_phone:      this.form.phone || null,
            client_doc_type:   this.form.dni ? 'DNI' : null,
            client_doc_number: this.form.dni || null,
            items: this.cart.map(i => ({ description: i.name, price: i.price, quantity: i.qty })),
          }),
        });
      } catch(e) {}
      window.open('https://wa.me/{{ $quoteWa }}?text='+encodeURIComponent(msg), '_blank');
    },

    railScroll(id, dir) {
      const el = document.getElementById(id);
      if (el) el.scrollBy({ left: dir * 280, behavior: 'smooth' });
    },

    newsletterSubmit(e) {
      const input = e.target.querySelector('input[type=email]');
      if (!input || !input.value) return;
      this.showToast('🎁 ¡Gracias! Revisa tu correo para obtener tu 5% de descuento.');
      input.value = '';
    },

    async submitCheckout() {
      const fullName = ((this.form.fname||'').trim()+' '+(this.form.lname||'').trim()).trim();
      if (!fullName) { this.orderError = 'Ingresa tu nombre completo.'; return; }
      const rawPhone = this.form.phone.replace(/\D/g,'');
      if (!rawPhone) { this.orderError = 'Ingresa tu número de celular.'; return; }
      if (rawPhone.length < 7) { this.orderError = 'El número de celular es muy corto.'; return; }
      if (rawPhone.length > 15) { this.orderError = 'El número de celular no es válido.'; return; }
      if (rawPhone.length === 9 && !/^9/.test(rawPhone)) { this.orderError = 'Los celulares peruanos empiezan con 9 (ej: 987 654 321).'; return; }
      @if($payManualEnabled || $culqiEnabled || $mpEnabled)
      if (!this.payMethod) { this.orderError = 'Selecciona un método de pago.'; return; }
      @endif

      if (this.payMethod === 'culqi') {
        @if($culqiEnabled)
        this.openCulqi(fullName); return;
        @endif
      }
      if (this.payMethod === 'mp') {
        @if($mpEnabled)
        this.openMercadoPago(fullName); return;
        @endif
      }

      this.orderLoading = true;
      this.orderError = '';
      this.saveForm();

      const addr = [this.form.address, this.form.address2, this.form.district, this.form.department].filter(Boolean).join(', ');
      const notes = [this.form.notes, this.form.dni ? 'DNI/RUC: '+this.form.dni : ''].filter(Boolean).join(' | ');
      const items = this.cart.map(i => ({ product_id: i.id, name: i.name, price: i.price, quantity: i.qty }));

      try {
        const res = await fetch(EC_ORDER_ROUTE, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': EC_CSRF, 'Accept': 'application/json' },
          body: JSON.stringify({
            client_name: fullName, client_phone: this.form.phone, client_email: this.form.email,
            delivery_address: addr, notes,
            items,
            payment_method: this.payMethod || '',
            payment_reference: this.form.payReference || '',
            payment_proof: '',
          }),
        });
        const data = await res.json();
        if (!res.ok) { this.orderError = data.message || 'Error al procesar el pedido.'; }
        else {
          this.orderCart = [...this.cart];
          this.orderTotal_snapshot = this.orderTotal;
          this.orderSuccess = true;
          this.orderSuccessMsg = data.message || '¡Gracias! Tu pedido fue recibido correctamente.';
          this.cart = []; this.saveCart();
        }
      } catch(e) {
        this.orderError = 'Error de conexión. Intenta nuevamente.';
      } finally {
        this.orderLoading = false;
      }
    },

    @if($culqiEnabled)
    openCulqi(fullName) {
      if (typeof Culqi === 'undefined') { this.orderError = 'Culqi no disponible.'; return; }
      Culqi.publicKey = CULQI_KEY;
      Culqi.settings({ title: '{{ $project->name }}', currency: 'PEN', description: 'Pedido', amount: Math.round(this.orderTotal*100) });
      Culqi.open();
    },
    @endif

    @if($mpEnabled)
    openMercadoPago(fullName) {
      this.orderError = 'Redirigiendo a Mercado Pago...';
    },
    @endif
  };
}
</script>

@if($culqiEnabled)
<script src="https://checkout.culqi.com/js/v4"></script>
<script>
function culqi() {
  if (Culqi.token) {
    const store = document.querySelector('[x-data]')?._x_dataStack?.[0];
    if (store) { store.form._culqiToken = Culqi.token.id; store.submitCheckout(); }
  } else { console.error('Culqi error', Culqi.error); }
}
</script>
@endif

{{-- ══ Popup confirmación de pago ══ --}}
<div x-show="showPayConfirm" x-cloak
     style="position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px)">
  <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:20px;width:calc(100% - 32px);max-width:380px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.2)">
    {{-- Header --}}
    <div style="padding:24px 24px 0;text-align:center">
      <div style="width:56px;height:56px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
        <svg style="width:28px;height:28px;color:#16a34a" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <h3 style="font-size:18px;font-weight:700;color:#111;margin:0 0 8px">¿Ya realizaste el pago?</h3>
      <p style="font-size:14px;color:#6b7280;margin:0 0 8px">Al confirmar se enviará tu pedido por WhatsApp y deberás adjuntar tu comprobante de pago.</p>
    </div>
    {{-- Botones --}}
    <div style="padding:20px 24px 24px;display:flex;flex-direction:column;gap:10px">
      <button @click="confirmPayAndSend()"
              style="width:100%;padding:14px;background:#16a34a;color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer">
        ✅ Sí, ya pagué — Enviar pedido
      </button>
      <button @click="showPayConfirm=false"
              style="width:100%;padding:14px;background:#f3f4f6;color:#374151;border:none;border-radius:12px;font-size:15px;font-weight:600;cursor:pointer">
        Aún no, volver al pago
      </button>
    </div>
  </div>
</div>
<x-public-store-runtime :project="$project" :settings="$settings" :popup="$popup ?? null" :sections="$sections ?? collect()" :about-page="$aboutPage ?? null" />
</body>
</html>
