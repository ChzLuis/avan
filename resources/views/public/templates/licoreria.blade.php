<!DOCTYPE html>
<html lang="es" prefix="og: https://ogp.me/ns#">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

@php
$payManualEnabled = ($settings['payment_manual_enabled'] ?? '0') === '1';
$payManualMethods = json_decode($settings['payment_manual_methods'] ?? '["yape","plin"]', true) ?? [];
$payYapeNumber    = $settings['payment_yape_number'] ?? '';
$payPlinNumber    = $settings['payment_plin_number'] ?? '';
$payBankDetails   = $settings['payment_bank_details'] ?? '';
$payManualInstr   = $settings['payment_manual_instructions'] ?? '';
$culqiEnabled     = ($settings['culqi_enabled'] ?? '0') === '1';
$culqiPublicKey   = $settings['culqi_public_key'] ?? '';
$culqiMode        = $settings['culqi_mode'] ?? 'test';
$mpEnabled        = ($settings['mp_enabled'] ?? '0') === '1';
$hasOnlinePayment = $culqiEnabled || $mpEnabled || $payManualEnabled;
$primaryColor     = $settings['primary_color'] ?? '#D4AF37';
$secondaryColor   = $settings['secondary_color'] ?? '#B68B40';
$isQuoteOnly      = ($settings['store_mode'] ?? 'direct') === 'quote_only';
$shippingEnabled  = ($settings['shipping_enabled']  ?? '0') === '1';
$shippingCost     = (float)($settings['shipping_cost']      ?? 0);
$shippingFreeFrom = (float)($settings['shipping_free_from'] ?? 0);
$requireAddress   = ($settings['require_address']   ?? '0') === '1';
$quotePriceDisp   = $settings['quote_price_display'] ?? 'show';
$quoteWaRaw       = preg_replace('/\D/', '', $settings['quote_whatsapp'] ?? '');
if (!$quoteWaRaw) $quoteWaRaw = preg_replace('/\D/', '', $project->whatsapp ?? '');
$quoteWaCountry   = $settings['quote_whatsapp_country'] ?? '51';
$quoteWa          = '';
if ($quoteWaRaw) {
    $quoteWa = str_starts_with($quoteWaRaw, $quoteWaCountry) ? $quoteWaRaw : $quoteWaCountry.$quoteWaRaw;
}
$quoteWaMsg       = $settings['quote_wa_msg'] ?? 'Hola, me interesa cotizar los siguientes productos:';
$canonicalUrl     = url('/' . $project->slug);
$seoTitle         = ($settings['seo_title'] ?? null) ?: ($project->name . ' — Licorería Premium');
$seoDesc          = ($settings['seo_description'] ?? null) ?: ($project->description ?? 'Selección premium de licores, destilados y bebidas especiales.');
$logoUrl          = $settings['logo_url'] ?? $project->logo_url ?? '';
$logoHeight       = $settings['logo_height'] ?? 44;
$ogImage          = $logoUrl ? asset('storage/'.$logoUrl) : asset('img/og-default.png');
$acceptedPayments = json_decode($settings['accepted_payments'] ?? '[]', true) ?? [];
$heroTitle        = $settings['hero_title'] ?? $project->name;
$heroSub          = $settings['hero_subtitle'] ?? 'La mejor selección de destilados y bebidas premium';
$heroBadge        = $settings['hero_badge'] ?? '🥃 Bienvenido';
$currency         = $settings['currency_symbol'] ?? 'S/';
$faviconUrl       = !empty($settings['favicon_url'] ?? '') ? asset('storage/'.$settings['favicon_url']) : '';
$btnCartText      = $settings['btn_cart_text']  ?? 'Agregar al carrito';
$btnQuoteText     = $settings['btn_quote_text'] ?? 'Cotizar';
$footerTagline    = $settings['footer_tagline']  ?? 'Destilados con excelencia';
$footerCopyright  = $settings['footer_copyright'] ?? ('© ' . date('Y') . ' ' . $project->name);
$trustIcon1 = $settings['trust_icon_1'] ?? '🚚'; $trustText1 = $settings['trust_text_1'] ?? 'Envío rápido';
$trustIcon2 = $settings['trust_icon_2'] ?? '🔒'; $trustText2 = $settings['trust_text_2'] ?? 'Pago seguro';
$trustIcon3 = $settings['trust_icon_3'] ?? '✅'; $trustText3 = $settings['trust_text_3'] ?? 'Producto auténtico';
$trustIcon4 = $settings['trust_icon_4'] ?? '🔞'; $trustText4 = $settings['trust_text_4'] ?? 'Mayor de 18';
$announcementText = $settings['announcement_text'] ?? '';
$paymentMeta = [
    'efectivo'=>['label'=>'Efectivo','emoji'=>'💵'],
    'yape'=>['label'=>'Yape','emoji'=>'🟣'],
    'plin'=>['label'=>'Plin','emoji'=>'🔵'],
    'transferencia'=>['label'=>'Transferencia','emoji'=>'🏦'],
    'tarjeta'=>['label'=>'Tarjeta','emoji'=>'💳'],
    'qr'=>['label'=>'Pago QR','emoji'=>'📲'],
    'contra_entrega'=>['label'=>'Contra entrega','emoji'=>'🚚'],
];
// Construir searchIndex sin arrow functions dentro de @json
$searchIndex = [];
foreach($categories as $_cat) {
    foreach($_cat->products as $_p) {
        $searchIndex[] = [
            'id'       => $_p->id,
            'name'     => $_p->name,
            'price'    => (float)$_p->price,
            'cp'       => $_p->compare_price ? (float)$_p->compare_price : null,
            'img'      => $_p->mainImage ? $_p->main_image_url : null,
            'cat'      => $_cat->name,
            'catId'    => (string)$_cat->id,
            'parentId' => null,
            'url'      => route('public.product', [$project->slug, $_p->id]),
            'stock'    => $_p->stock,
        ];
    }
    foreach($_cat->children as $_sub) {
        foreach($_sub->products as $_p) {
            $searchIndex[] = [
                'id'       => $_p->id,
                'name'     => $_p->name,
                'price'    => (float)$_p->price,
                'cp'       => $_p->compare_price ? (float)$_p->compare_price : null,
                'img'      => $_p->mainImage ? $_p->main_image_url : null,
                'cat'      => $_sub->name,
                'catId'    => (string)$_sub->id,
                'parentId' => (string)$_cat->id,
                'url'      => route('public.product', [$project->slug, $_p->id]),
                'stock'    => $_p->stock,
            ];
        }
    }
}
@endphp

<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDesc }}">
<meta name="robots" content="index, follow">
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDesc }}">
<meta property="og:image" content="{{ $ogImage }}">
@if($faviconUrl)<link rel="icon" href="{{ $faviconUrl }}">@endif

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<style>
:root {
  --gold:    {{ $primaryColor }};
  --gold-2:  {{ $secondaryColor }};
  --gold-soft: color-mix(in srgb, {{ $primaryColor }} 60%, #fff);
  --wine:    #8B0000;
  --green:   #2f8f5a;
  /* dark defaults */
  --bg-0:    #0F0F0F;
  --bg-1:    #161616;
  --bg-2:    #1E1E1E;
  --bg-3:    #262626;
  --line:    #2a2a2a;
  --text:    #F5F5F5;
  --text-dim:#B8B0A4;
  --text-mute:#7a7368;
  --header-bg: rgba(15,15,15,.92);
  --nav-bg:  rgba(20,20,20,.95);
  --utility-bg: #1a1a1a;
  --utility-text: #E8C97A;
  --addcart-bg: #111;
  --addcart-text: #F5F5F5;
  --shadow-gold: 0 6px 22px rgba(212,175,55,.18);
  --font-serif: 'Playfair Display', Georgia, serif;
  --font-sans:  'Inter', system-ui, sans-serif;
  --container: 1320px;
  --r-sm: 4px; --r-md: 8px; --r-lg: 14px;
}
/* LIGHT MODE — default */
body {
  --bg-0:    #FAF7F2;
  --bg-1:    #F2EDE4;
  --bg-2:    #EAE3D6;
  --bg-3:    #DDD3C0;
  --line:    #E2D9C8;
  --text:    #1A1612;
  --text-dim:#5E5448;
  --text-mute:#8A7E6C;
  --gold:    {{ $primaryColor }};
  --gold-2:  {{ $secondaryColor }};
  --header-bg: rgba(250,247,242,.95);
  --nav-bg:  rgba(242,237,228,.97);
  --utility-bg: #1A1612;
  --utility-text: #E8C97A;
  --addcart-bg: #1A1612;
  --addcart-text: #FAF7F2;
  --shadow-gold: 0 6px 22px rgba(168,132,31,.16);
}
/* DARK MODE when class added */
body.dark {
  --bg-0:    #0F0F0F;
  --bg-1:    #161616;
  --bg-2:    #1E1E1E;
  --bg-3:    #262626;
  --line:    #2a2a2a;
  --text:    #F5F5F5;
  --text-dim:#B8B0A4;
  --text-mute:#7a7368;
  --header-bg: rgba(15,15,15,.92);
  --nav-bg:  rgba(20,20,20,.95);
  --utility-bg: #1a1a1a;
  --addcart-bg: #111;
  --addcart-text: #F5F5F5;
}

*, body { box-sizing: border-box; font-family: var(--font-sans); }
html, body { margin:0; padding:0; background:var(--bg-0); color:var(--text); font-size:15px; line-height:1.6; -webkit-font-smoothing:antialiased; transition:background .3s,color .3s; }
img { max-width:100%; display:block; }
a { color:inherit; text-decoration:none; }
button { font:inherit; cursor:pointer; border:0; background:none; color:inherit; }
[x-cloak] { display:none!important; }

.container { max-width:var(--container); margin:0 auto; padding:0 24px; }
.serif { font-family:var(--font-serif); }
.eyebrow { text-transform:uppercase; letter-spacing:.22em; font-size:11px; color:var(--gold); font-weight:600; }

/* UTILITY BAR */
.utility-bar { background:var(--utility-bg); border-bottom:1px solid #232323; color:var(--utility-text); font-size:12px; max-height:44px; overflow:hidden; transition:max-height .35s,opacity .25s; }
.utility-bar.is-collapsed { max-height:0; opacity:0; }
.utility-bar .container { display:flex; align-items:center; justify-content:space-between; height:44px; }
.utility-left, .utility-right { display:flex; align-items:center; gap:14px; }
.utility-sep { color:#3a3a3a; }
.utility-right a:hover { color:var(--gold); }

/* HEADER */
.site-header { position:sticky; top:0; z-index:50; background:var(--header-bg); backdrop-filter:blur(14px); border-bottom:1px solid var(--line); }
.header-main { display:grid; grid-template-columns:auto 1fr auto; gap:24px; align-items:center; padding:16px 0; }
.brand { display:flex; align-items:center; gap:12px; }
.brand-mark { width:36px; height:36px; border:1.5px solid var(--gold); border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--gold); flex-shrink:0; }
.brand-name { font-family:var(--font-serif); font-size:18px; font-weight:700; letter-spacing:.04em; color:var(--text); }
.brand-tag { font-size:10px; letter-spacing:.15em; color:var(--text-mute); text-transform:uppercase; margin-top:1px; }
.search-wrap { position:relative; }
.search-input { width:100%; background:var(--bg-2); border:1px solid var(--line); color:var(--text); border-radius:8px; padding:10px 16px 10px 42px; font-size:14px; outline:none; transition:border .2s,box-shadow .2s; }
.search-input:focus { border-color:var(--gold); box-shadow:0 0 0 3px color-mix(in srgb,var(--gold) 12%,transparent); }
.search-input::placeholder { color:var(--text-mute); }
.search-ico { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-mute); pointer-events:none; }
.search-dropdown { position:absolute; left:0; right:0; top:calc(100% + 6px); background:var(--bg-1); border:1px solid var(--line); border-radius:10px; overflow:hidden; box-shadow:0 12px 40px rgba(0,0,0,.3); z-index:200; }
.search-item { display:flex; align-items:center; gap:12px; padding:10px 16px; border-bottom:1px solid var(--line); cursor:pointer; transition:background .15s; }
.search-item:last-child { border-bottom:0; }
.search-item:hover { background:var(--bg-2); }
.header-actions { display:flex; align-items:center; gap:8px; }
.icon-btn { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:var(--bg-2); color:var(--text-dim); transition:background .2s,color .2s; position:relative; }
.icon-btn:hover { background:var(--bg-3); color:var(--gold); }
.icon-btn .badge { position:absolute; top:-2px; right:-2px; background:var(--gold); color:var(--bg-0); font-size:10px; font-weight:800; min-width:18px; height:18px; border-radius:9px; display:flex; align-items:center; justify-content:center; padding:0 4px; }
.btn-gold { background:var(--gold); color:var(--bg-0); font-weight:700; border-radius:8px; padding:10px 20px; display:inline-flex; align-items:center; gap:8px; transition:background .2s,transform .15s; }
.btn-gold:hover { background:var(--gold-2); }
.btn-ghost { border:1.5px solid var(--gold); color:var(--gold); background:transparent; border-radius:8px; padding:9px 20px; font-weight:600; display:inline-flex; align-items:center; gap:8px; transition:background .2s,color .2s; }
.btn-ghost:hover { background:var(--gold); color:var(--bg-0); }

/* THEME TOGGLE */
.theme-toggle { width:50px; height:26px; background:var(--bg-3); border-radius:13px; position:relative; border:1px solid var(--line); transition:background .3s; cursor:pointer; display:flex; align-items:center; justify-content:space-between; padding:0 6px; }
.tt-thumb { position:absolute; width:20px; height:20px; background:var(--gold); border-radius:50%; top:2px; left:2px; transition:transform .3s; display:flex; align-items:center; justify-content:center; }
body.dark .tt-thumb { transform:translateX(24px); }
.tt-sun, .tt-moon { font-size:10px; color:var(--text-mute); z-index:1; pointer-events:none; }

/* NAV CATS */
.nav-cats { background:var(--nav-bg); border-top:1px solid var(--line); position:relative; }
.nav-cats-inner { display:flex; align-items:center; overflow:hidden; }
.cats-track { display:flex; align-items:center; gap:0; overflow-x:auto; scrollbar-width:none; scroll-behavior:smooth; flex:1; }
.cats-track::-webkit-scrollbar { display:none; }
.cat-btn { padding:12px 16px; font-size:13px; font-weight:500; color:var(--text-dim); white-space:nowrap; border-bottom:2px solid transparent; transition:color .2s,border-color .2s; background:none; border-left:none; border-right:none; border-top:none; cursor:pointer; }
.cat-btn:hover, .cat-btn.active { color:var(--gold); border-bottom-color:var(--gold); }
.cat-arrow { flex-shrink:0; width:32px; height:44px; display:flex; align-items:center; justify-content:center; background:var(--nav-bg); border:none; cursor:pointer; color:var(--text-dim); transition:color .2s,opacity .2s; z-index:2; }
.cat-arrow:hover { color:var(--gold); }
.cat-arrow.hidden { opacity:0; pointer-events:none; }
/* Subcategory dropdown — posicionado con JS (fixed) para evitar overflow:hidden del track */
.cat-wrap { position:static; }
#liq-sub-dropdown { display:none; position:fixed; min-width:160px; background:var(--nav-bg); border:1px solid var(--line); border-top:2px solid var(--gold); box-shadow:0 8px 24px rgba(0,0,0,.4); z-index:9999; }
#liq-sub-dropdown button { display:block; width:100%; text-align:left; padding:10px 16px; font-size:13px; color:var(--text-dim); white-space:nowrap; border:none; background:none; cursor:pointer; transition:background .15s,color .15s; }
#liq-sub-dropdown button:hover, #liq-sub-dropdown button.active-sub { background:rgba(255,255,255,.06); color:var(--gold); }

/* CHECKOUT FULLSCREEN */
.ck-grid { display:grid; grid-template-columns:1fr 420px; gap:24px; align-items:start; }
@media(max-width:900px){ .ck-grid { grid-template-columns:1fr; } }

/* HERO */
.hero { background:linear-gradient(135deg,var(--bg-1) 0%,var(--bg-0) 70%); overflow:hidden; position:relative; }
.hero-track { display:flex; transition:transform .6s cubic-bezier(.77,0,.18,1); }
.hero-slide { min-width:100%; display:grid; grid-template-columns:1fr 420px; gap:48px; align-items:center; padding:72px 0; }
.hero-slide .container { display:contents; }
.hero-wrap { max-width:var(--container); margin:0 auto; padding:0 24px; display:grid; grid-template-columns:1fr 380px; gap:48px; align-items:center; min-height:420px; }
.hero-copy { padding:0; }
.hero-title { font-family:var(--font-serif); font-size:clamp(32px,4vw,52px); line-height:1.12; font-weight:700; margin:12px 0 16px; color:var(--text); }
.hero-title span { color:var(--gold); }
.hero-sub { color:var(--text-dim); font-size:15px; line-height:1.7; max-width:480px; margin-bottom:24px; }
.hero-meta { display:flex; gap:24px; margin-bottom:28px; flex-wrap:wrap; }
.hero-meta span { font-size:13px; color:var(--text-dim); }
.hero-meta strong { color:var(--gold); }
.hero-cta { display:flex; gap:12px; flex-wrap:wrap; }
.hero-visual { display:flex; align-items:center; justify-content:center; }
.bottle-placeholder { width:200px; height:320px; margin:0 auto; opacity:.9; }
.hero-nav { display:flex; align-items:center; justify-content:center; gap:12px; padding:16px 0 24px; }
.hero-dot { width:8px; height:8px; border-radius:4px; background:var(--line); border:0; transition:width .3s,background .3s; cursor:pointer; }
.hero-dot.active { width:24px; background:var(--gold); }
.hero-arrow { width:36px; height:36px; border-radius:50%; border:1px solid var(--line); color:var(--text-dim); display:flex; align-items:center; justify-content:center; font-size:18px; transition:border-color .2s,color .2s; }
.hero-arrow:hover { border-color:var(--gold); color:var(--gold); }

/* TRUST STRIP */
.trust-strip { background:var(--bg-1); border-top:1px solid var(--line); border-bottom:1px solid var(--line); }
.trust-strip .container { display:grid; grid-template-columns:repeat(4,1fr); }
.trust-item { display:flex; align-items:center; gap:14px; padding:20px 24px; border-right:1px solid var(--line); }
.trust-item:last-child { border-right:0; }
.ti-ico { width:40px; height:40px; border-radius:10px; background:color-mix(in srgb,var(--gold) 10%,transparent); display:flex; align-items:center; justify-content:center; color:var(--gold); flex-shrink:0; font-size:18px; }
.trust-item h4 { font-size:13px; font-weight:700; margin:0 0 2px; color:var(--text); }
.trust-item p { font-size:12px; color:var(--text-mute); margin:0; }

/* SECTION */
.section { padding:64px 0; }
.section-head { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:32px; gap:16px; flex-wrap:wrap; }
.section-head h2 { font-family:var(--font-serif); font-size:clamp(24px,3vw,36px); font-weight:700; margin:6px 0 0; color:var(--text); }

/* SHOP LAYOUT */
.shop { display:grid; grid-template-columns:240px 1fr; gap:32px; align-items:start; }
.filters { background:var(--bg-1); border:1px solid var(--line); border-radius:12px; padding:20px; position:sticky; top:90px; }
.filter-block { margin-bottom:20px; padding-bottom:20px; border-bottom:1px solid var(--line); }
.filter-block:last-child { margin-bottom:0; padding-bottom:0; border-bottom:0; }
.filter-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
.filter-head h4 { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:var(--text-dim); margin:0; }
.check-list label { display:flex; align-items:center; gap:8px; font-size:13px; color:var(--text-dim); padding:4px 0; cursor:pointer; transition:color .15s; }
.check-list label:hover { color:var(--gold); }
.check-list label.checked { color:var(--text); }
.check-box { width:16px; height:16px; border:1.5px solid var(--line); border-radius:3px; display:flex; align-items:center; justify-content:center; font-size:9px; color:transparent; flex-shrink:0; transition:border-color .15s,background .15s,color .15s; }
label.checked .check-box { border-color:var(--gold); background:var(--gold); color:var(--bg-0); }
.tag-row { display:flex; flex-wrap:wrap; gap:6px; }
.tag { font-size:11px; padding:4px 10px; border:1px solid var(--line); border-radius:20px; color:var(--text-mute); cursor:pointer; transition:border-color .15s,color .15s,background .15s; }
.tag.on { border-color:var(--gold); color:var(--gold); background:color-mix(in srgb,var(--gold) 8%,transparent); }

/* TOOLBAR */
.shop-toolbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; gap:12px; flex-wrap:wrap; }
.sort-select { background:var(--bg-2); border:1px solid var(--line); color:var(--text); border-radius:8px; padding:8px 12px; font-size:13px; outline:none; cursor:pointer; }

/* PRODUCTS GRID */
.products { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:20px; }
.product { background:var(--bg-1); border:1px solid var(--line); border-radius:12px; overflow:hidden; transition:transform .3s,box-shadow .3s,border-color .3s; position:relative; }
.product:hover { transform:translateY(-4px); border-color:var(--gold-2); box-shadow:var(--shadow-gold); }
.product-img { aspect-ratio:3/4; background:var(--bg-2); display:flex; align-items:center; justify-content:center; overflow:hidden; position:relative; }
.product-img img { width:100%; height:100%; object-fit:cover; transition:transform .5s; }
.product:hover .product-img img { transform:scale(1.06); }
.product-img .bottle-svg { width:100px; height:160px; opacity:.7; }
.product-body { padding:14px; }
.product-cat { font-size:10px; text-transform:uppercase; letter-spacing:.15em; color:var(--gold); font-weight:600; margin-bottom:4px; }
.product-name { font-size:14px; font-weight:600; line-height:1.3; color:var(--text); margin-bottom:8px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.product-price { display:flex; align-items:baseline; gap:8px; }
.price-main { font-size:16px; font-weight:800; color:var(--gold); }
.price-old { font-size:12px; color:var(--text-mute); text-decoration:line-through; }
.product-rating { display:flex; align-items:center; gap:4px; font-size:11px; color:var(--text-mute); margin-bottom:8px; }
.star-filled { color:var(--gold); }
.add-cart-btn { width:100%; margin-top:10px; background:var(--addcart-bg); color:var(--addcart-text); border-radius:8px; padding:8px; font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; gap:6px; transition:background .2s,color .2s; }
.add-cart-btn:hover { background:var(--gold); color:var(--bg-0); }
.badge-sale { position:absolute; top:10px; left:10px; background:var(--wine); color:#fff; font-size:10px; font-weight:800; padding:2px 8px; border-radius:4px; letter-spacing:.05em; }
.badge-new { position:absolute; top:10px; left:10px; background:var(--green); color:#fff; font-size:10px; font-weight:800; padding:2px 8px; border-radius:4px; }
.wish-btn { position:absolute; top:10px; right:10px; width:30px; height:30px; border-radius:50%; background:var(--bg-0); border:1px solid var(--line); color:var(--text-mute); display:flex; align-items:center; justify-content:center; font-size:13px; transition:color .2s,background .2s; }
.wish-btn.on { color:var(--wine); }

/* TOAST */
.toast { position:fixed; top:80px; left:50%; transform:translateX(-50%) translateY(-10px); background:var(--gold); color:var(--bg-0); font-size:13px; font-weight:700; padding:10px 20px; border-radius:50px; white-space:nowrap; pointer-events:none; opacity:0; transition:opacity .2s,transform .2s; z-index:1000; }
.toast.show { opacity:1; transform:translateX(-50%) translateY(0); }

/* DRAWER */
.drawer-overlay { position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:60; backdrop-filter:blur(4px); }
.drawer { position:fixed; top:0; right:0; height:100%; width:420px; max-width:96vw; background:var(--bg-1); border-left:1px solid var(--line); z-index:70; display:flex; flex-direction:column; box-shadow:-8px 0 40px rgba(0,0,0,.3); }
@media(max-width:640px){ .drawer{ width:100%; } }

/* FOOTER */
.site-footer { background:var(--bg-1); border-top:1px solid var(--line); padding:56px 0 0; }
.footer-grid { display:grid; grid-template-columns:1.5fr repeat(3,1fr); gap:40px; margin-bottom:40px; }
.footer-brand { }
.footer-brand .brand-name { font-size:20px; margin-bottom:8px; }
.footer-tagline { font-size:13px; color:var(--text-mute); line-height:1.6; margin-bottom:16px; }
.social-row { display:flex; gap:10px; }
.soc-btn { width:36px; height:36px; border-radius:50%; background:var(--bg-2); border:1px solid var(--line); display:flex; align-items:center; justify-content:center; font-size:14px; color:var(--text-mute); transition:border-color .2s,color .2s; }
.soc-btn:hover { border-color:var(--gold); color:var(--gold); }
.footer-col h4 { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.15em; color:var(--text-dim); margin-bottom:14px; }
.footer-col a { display:block; font-size:13px; color:var(--text-mute); margin-bottom:8px; transition:color .2s; }
.footer-col a:hover { color:var(--gold); }
.footer-bottom { border-top:1px solid var(--line); padding:16px 0; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; }
.footer-bottom p { font-size:12px; color:var(--text-mute); margin:0; }
.pay-icons { display:flex; gap:8px; flex-wrap:wrap; }
.pay-icon { font-size:18px; }

/* AGE MODAL */
.age-modal { position:fixed; inset:0; background:rgba(0,0,0,.85); z-index:200; display:flex; align-items:center; justify-content:center; padding:24px; opacity:0; pointer-events:none; transition:opacity .3s; }
.age-modal.show { opacity:1; pointer-events:all; }
.age-card { background:var(--bg-1); border:1px solid var(--line); border-radius:16px; max-width:440px; width:100%; padding:40px 32px; text-align:center; }
.lock-ico { width:56px; height:56px; border:1.5px solid var(--gold); border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--gold); margin:0 auto 20px; }
.age-card h2 { font-family:var(--font-serif); font-size:24px; margin:0 0 12px; color:var(--text); }
.age-card p { font-size:14px; color:var(--text-dim); line-height:1.7; margin-bottom:24px; }
.age-actions { display:flex; gap:12px; justify-content:center; }
.age-note { font-size:11px; color:var(--text-mute); margin-top:16px; }

/* FLOATING BAR */
.floating-bar { position:fixed; bottom:0; left:0; right:0; z-index:40; background:var(--bg-1); border-top:1px solid var(--line); padding:12px 24px; display:flex; align-items:center; gap:16px; }

/* RESPONSIVE */
@media(max-width:1100px){
  .shop { grid-template-columns:200px 1fr; }
  .footer-grid { grid-template-columns:1fr 1fr; }
  .hero-wrap { grid-template-columns:1fr 280px; }
  .trust-strip .container { grid-template-columns:repeat(2,1fr); }
}
@media(max-width:900px){
  .shop { grid-template-columns:1fr; }
  .filters { position:static; display:none; }
  .header-main { grid-template-columns:auto auto; }
  .search-wrap { display:none; }
}
@media(max-width:640px){
  .hero-wrap { grid-template-columns:1fr; }
  .hero-visual { display:none; }
  .trust-strip .container { grid-template-columns:1fr 1fr; }
  .footer-grid { grid-template-columns:1fr; }
}
</style>
    <x-analytics-tags :settings="$settings" />
    <x-storefront-motion :settings="$settings" />
</head>
<body x-data="licStore()" x-init="init()" :class="darkMode?'dark':''">

{{-- AGE MODAL --}}
@if(($settings['age_gate'] ?? '0') === '1')
<div class="age-modal" id="ageModal" x-ref="ageModal">
  <div class="age-card">
    <div class="lock-ico">
      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
    </div>
    <h2 class="serif">Verificación de Edad</h2>
    <p>Para ingresar confirma que tienes la mayoría de edad legal en tu país de residencia.</p>
    <div class="age-actions">
      <button class="btn-ghost" onclick="document.getElementById('ageModal').style.display='none';sessionStorage.setItem('vs_age_no','1');window.location='https://www.google.com'">Soy menor</button>
      <button class="btn-gold" onclick="document.getElementById('ageModal').style.display='none';sessionStorage.setItem('vs_age_ok','1')">Soy mayor de 18</button>
    </div>
    <p class="age-note">La venta de alcohol a menores está prohibida.</p>
  </div>
</div>
<script>
(function(){
  if(!sessionStorage.getItem('vs_age_ok')){
    setTimeout(()=>{const m=document.getElementById('ageModal');if(m)m.classList.add('show');},400);
  }
})();
</script>
@endif

{{-- TOAST --}}
<div class="toast" id="vs-toast"></div>

{{-- UTILITY BAR --}}
<div class="utility-bar" id="utilityBar">
  <div class="container">
    <div class="utility-left">
      @if($announcementText)
        <span>{{ $announcementText }}</span>
      @elseif($shippingFreeFrom > 0)
        <span>🚚 <b style="color:var(--gold)">ENVÍOS GRATIS</b> desde {{ $currency }} {{ number_format($shippingFreeFrom,0) }}</span>
      @else
        <span>🥃 Bienvenido a {{ $project->name }}</span>
      @endif
      @if($project->phone)
      <span class="utility-sep">|</span>
      <span>📞 {{ $project->phone }}</span>
      @endif
    </div>
    <div class="utility-right">
      @foreach(['facebook_url'=>'Facebook','instagram_url'=>'Instagram','tiktok_url'=>'TikTok'] as $sk=>$sl)
      @if($settings[$sk] ?? null)
      <a href="{{ $settings[$sk] }}" target="_blank" rel="noopener">{{ $sl }}</a>
      <span class="utility-sep">|</span>
      @endif
      @endforeach
      <a href="#catalogo">Catálogo</a>
    </div>
  </div>
</div>

{{-- HEADER --}}
<header class="site-header">
  <div class="container">
    <div class="header-main">
      {{-- Brand --}}
      <a href="{{ $canonicalUrl }}" class="brand">
        @if($logoUrl)
          <img src="{{ asset('storage/'.$logoUrl) }}" alt="{{ $project->name }}" style="height:{{ $logoHeight }}px;object-fit:contain;">
        @else
          <div class="brand-mark">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path d="M8 3h8l-1 6a4 4 0 0 1-3 3 4 4 0 0 1-3-3z"/><path d="M12 12v7"/><path d="M9 20h6"/></svg>
          </div>
          <div>
            <div class="brand-name">{{ $project->name }}</div>
            <div class="brand-tag">{{ $footerTagline }}</div>
          </div>
        @endif
      </a>

      {{-- Search --}}
      <div class="search-wrap" @click.outside="searchOpen=false">
        <span class="search-ico">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        </span>
        <input type="search" class="search-input" placeholder="Busca por nombre, categoría…"
               x-model="search"
               @input="searchOpen=search.trim().length>=2;searchIdx=-1"
               @keydown.arrow-down.prevent="searchIdx=(searchIdx+1)%Math.max(suggestions.length,1)"
               @keydown.arrow-up.prevent="searchIdx=(searchIdx-1+suggestions.length)%Math.max(suggestions.length,1)"
               @keydown.enter.prevent="if(searchIdx>=0&&suggestions[searchIdx]){window.location.href=suggestions[searchIdx].url}else{searchOpen=false;scrollToCatalog()}"
               @keydown.escape="searchOpen=false">
        <div class="search-dropdown" x-show="searchOpen && suggestions.length>0" x-cloak>
          <template x-for="(p,i) in suggestions" :key="p.id">
            <div class="search-item" :class="searchIdx===i?'bg-[var(--bg-2)]:'''" @click="window.location.href=p.url">
              <div style="width:40px;height:40px;border-radius:8px;overflow:hidden;background:var(--bg-2);flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                <img x-show="p.img" :src="p.img" style="width:100%;height:100%;object-fit:cover;">
                <span x-show="!p.img" style="font-size:18px;">🍾</span>
              </div>
              <div style="flex:1;min-width:0;">
                <p style="font-size:13px;font-weight:600;color:var(--text);margin:0 0 2px;" x-text="p.name"></p>
                <p style="font-size:11px;color:var(--text-mute);margin:0;" x-text="p.cat"></p>
              </div>
              <p style="font-size:13px;font-weight:700;color:var(--gold);flex-shrink:0;" x-text="'{{ $currency }} '+p.price.toFixed(2)"></p>
            </div>
          </template>
        </div>
      </div>

      {{-- Actions --}}
      <div class="header-actions">
        {{-- Theme toggle --}}
        <button class="theme-toggle" @click="darkMode=!darkMode;localStorage.setItem('vs_theme',darkMode?'dark':'light')" :title="darkMode?'Modo claro':'Modo oscuro'" style="cursor:pointer;">
          <span class="tt-sun">☀️</span>
          <span class="tt-thumb"></span>
          <span class="tt-moon">🌙</span>
        </button>

        @if($project->whatsapp)
        <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}" target="_blank" rel="noopener" class="btn-gold" style="font-size:12px;padding:8px 14px;border-radius:8px;">
          <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
          Pedidos
        </a>
        @endif

        <button class="icon-btn" @click="drawerOpen=true" aria-label="Carrito">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path d="M3 3h2l3 13h11l2-8H7"/><circle cx="10" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/></svg>
          <span class="badge" x-show="cartCount>0" x-text="cartCount" x-cloak></span>
        </button>
      </div>
    </div>
  </div>

  {{-- Nav categorías --}}
  <nav class="nav-cats">
    <div class="container" style="padding:0;">
      <div class="nav-cats-inner">
        <button class="cat-arrow" id="liq-cat-prev" onclick="liqCatScroll(-1)" aria-label="Anterior">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
        <div class="cats-track" id="liq-cats-track">
          <button class="cat-btn" :class="filterCat===''?'active':''" @click="filterCat='';scrollToCatalog()">Todo</button>
          @foreach($categories as $cat)
          <div class="cat-wrap" data-cat-id="{{ $cat->id }}"@if($cat->children->count()) data-has-sub="1"@endif>
            <button class="cat-btn"
              :class="filterCat==='{{ $cat->id }}'?'active':''"
              @click="filterCat='{{ $cat->id }}';scrollToCatalog()">
              {{ $cat->name }}@if($cat->children->count()) <span style="font-size:9px;opacity:.5;margin-left:3px;">▾</span>@endif
            </button>
          </div>
          @endforeach
        </div>
        {{-- Dropdown global (fuera del track para evitar overflow:hidden) --}}
        <div id="liq-sub-dropdown"></div>
        <button class="cat-arrow" id="liq-cat-next" onclick="liqCatScroll(1)" aria-label="Siguiente">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
        </button>
      </div>
    </div>
  </nav>
  <script>
  (function(){
    var track = document.getElementById('liq-cats-track');
    var prev  = document.getElementById('liq-cat-prev');
    var next  = document.getElementById('liq-cat-next');
    var drop  = document.getElementById('liq-sub-dropdown');
    if(!track) return;

    // Flechas
    function update(){
      prev.classList.toggle('hidden', track.scrollLeft <= 4);
      next.classList.toggle('hidden', track.scrollLeft + track.clientWidth >= track.scrollWidth - 4);
    }
    window.liqCatScroll = function(dir){ track.scrollBy({left: dir * 200, behavior:'smooth'}); };
    track.addEventListener('scroll', update, {passive:true});
    setTimeout(update, 100);

    // Subcategorías: mapa catId → [{id, name}]
    var SUBS = @json($categories->mapWithKeys(fn($cat) => [
      (string)$cat->id => $cat->children->map(fn($s) => ['id'=>(string)$s->id,'name'=>$s->name])->values()
    ]));

    var hideTimer = null;

    function showDrop(wrap) {
      var catId = wrap.dataset.catId;
      var subs  = SUBS[catId];
      if (!subs || !subs.length) return;
      clearTimeout(hideTimer);

      drop.innerHTML = subs.map(function(s){
        return '<button onclick="liqSelectSub(\''+s.id+'\')">'+s.name+'</button>';
      }).join('');

      var rect = wrap.getBoundingClientRect();
      drop.style.top  = rect.bottom + 'px';
      drop.style.left = rect.left + 'px';
      drop.style.display = 'block';
    }

    function hideDrop(){
      hideTimer = setTimeout(function(){ drop.style.display='none'; }, 150);
    }

    window.liqSelectSub = function(subId){
      // Cambiar filtro en Alpine
      var root = document.querySelector('[x-data]');
      if (root && root._x_dataStack && root._x_dataStack[0]) {
        root._x_dataStack[0].filterCat = subId;
        if (typeof root._x_dataStack[0].scrollToCatalog === 'function') root._x_dataStack[0].scrollToCatalog();
      }
      drop.style.display = 'none';
    };

    track.querySelectorAll('.cat-wrap[data-has-sub]').forEach(function(wrap){
      wrap.addEventListener('mouseenter', function(){ showDrop(wrap); });
      wrap.addEventListener('mouseleave', hideDrop);
    });
    drop.addEventListener('mouseenter', function(){ clearTimeout(hideTimer); });
    drop.addEventListener('mouseleave', hideDrop);
  })();
  </script>
    <x-store-menu :menu="$storeMenu ?? null" :project="$project" :store-view="$storeView ?? 'home'" />
</header>

{{-- HERO --}}
<section class="hero" data-store-native-section="hero">
  <div id="heroTrack" class="hero-track">

    {{-- Slide 1 --}}
    <div style="min-width:100%;">
      <div class="hero-wrap container">
        <div class="hero-copy">
          <span class="eyebrow">{{ $heroBadge }}</span>
          <h1 class="hero-title serif">{{ $heroTitle }} <span>— selección premium</span></h1>
          <p class="hero-sub">{{ $heroSub }}</p>
          <div class="hero-meta">
            @if($shippingFreeFrom > 0)
            <span>🚚 <strong>Envío gratis</strong> desde {{ $currency }} {{ number_format($shippingFreeFrom,0) }}</span>
            @endif
            <span>✅ <strong>Producto</strong> auténtico</span>
          </div>
          <div class="hero-cta">
            <button class="btn-gold" @click="scrollToCatalog()">Ver catálogo →</button>
            @if($project->whatsapp)
            <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}" target="_blank" rel="noopener" class="btn-ghost">WhatsApp</a>
            @endif
          </div>
        </div>
        <div class="hero-visual">
          <div class="bottle-placeholder">
            <svg viewBox="0 0 200 320" fill="none" xmlns="http://www.w3.org/2000/svg">
              <defs>
                <linearGradient id="bot1" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0" stop-color="color-mix(in srgb,var(--gold) 40%,var(--bg-2))"/>
                  <stop offset=".55" stop-color="color-mix(in srgb,var(--gold) 60%,var(--bg-3))"/>
                  <stop offset="1" stop-color="var(--bg-2)"/>
                </linearGradient>
              </defs>
              <path d="M85 20h30v40c0 8 18 18 18 40v200a10 10 0 0 1-10 10H77a10 10 0 0 1-10-10V100c0-22 18-32 18-40z" fill="url(#bot1)" stroke="var(--gold)" stroke-width="1"/>
              <rect x="72" y="160" width="56" height="80" fill="var(--bg-0)" stroke="var(--gold)" stroke-width=".8" opacity=".8"/>
              <text x="100" y="190" text-anchor="middle" fill="var(--gold)" font-family="Playfair Display,serif" font-size="11">{{ Str::upper(Str::limit($project->name,8)) }}</text>
              <text x="100" y="210" text-anchor="middle" fill="var(--gold)" font-family="Playfair Display,serif" font-style="italic" font-size="9">premium</text>
              <line x1="80" y1="220" x2="120" y2="220" stroke="var(--gold)" stroke-width=".5"/>
              <text x="100" y="232" text-anchor="middle" fill="var(--text-mute)" font-family="Inter,sans-serif" font-size="6" letter-spacing="2">SELECCIÓN</text>
            </svg>
          </div>
        </div>
      </div>
    </div>

    {{-- Slide 2 --}}
    @if($onSale->count() > 0)
    <div style="min-width:100%;">
      <div class="hero-wrap container">
        <div class="hero-copy">
          <span class="eyebrow">🔥 Ofertas especiales</span>
          <h1 class="hero-title serif">Los mejores <span>precios</span> de la temporada.</h1>
          <p class="hero-sub">Productos seleccionados con descuentos exclusivos. No te quedes sin los tuyos.</p>
          <div class="hero-cta">
            <button class="btn-gold" @click="onSaleFilter=true;scrollToCatalog()">Ver ofertas →</button>
          </div>
        </div>
        <div class="hero-visual">
          <div class="bottle-placeholder">
            <svg viewBox="0 0 200 320" fill="none">
              <path d="M90 20h20v50c0 6 22 14 22 38v200a8 8 0 0 1-8 8H76a8 8 0 0 1-8-8V108c0-24 22-32 22-38z" fill="var(--bg-2)" stroke="var(--gold)" stroke-width="1"/>
              <rect x="76" y="170" width="48" height="70" fill="var(--bg-0)" stroke="var(--gold)" stroke-width=".6" opacity=".8"/>
              <text x="100" y="200" text-anchor="middle" fill="var(--gold)" font-family="Playfair Display,serif" font-size="11">OFERTA</text>
              <text x="100" y="220" text-anchor="middle" fill="var(--gold)" font-family="Playfair Display,serif" font-style="italic" font-size="9">especial</text>
            </svg>
          </div>
        </div>
      </div>
    </div>
    @endif

    {{-- Slide 3 --}}
    @if($newArrivals->count() > 0)
    <div style="min-width:100%;">
      <div class="hero-wrap container">
        <div class="hero-copy">
          <span class="eyebrow">✨ Nuevos ingresos</span>
          <h1 class="hero-title serif">Recién llegados a <span>nuestra cava</span>.</h1>
          <p class="hero-sub">Los últimos productos que acaban de llegar. Sé el primero en probarlos.</p>
          <div class="hero-cta">
            <button class="btn-gold" @click="sortBy='newest';scrollToCatalog()">Ver nuevos →</button>
          </div>
        </div>
        <div class="hero-visual">
          <div class="bottle-placeholder">
            <svg viewBox="0 0 200 320" fill="none">
              <path d="M50 40h100l-10 90a40 40 0 0 1-80 0z" fill="none" stroke="var(--gold)" stroke-width="1.4"/>
              <path d="M100 130v110" stroke="var(--gold)" stroke-width="1.4"/>
              <path d="M70 250h60" stroke="var(--gold)" stroke-width="1.4"/>
              <text x="100" y="290" text-anchor="middle" fill="var(--text-mute)" font-family="Inter" font-size="8" letter-spacing="3">NUEVO · {{ date('Y') }}</text>
            </svg>
          </div>
        </div>
      </div>
    </div>
    @endif

  </div>
  <div class="hero-nav container" style="max-width:var(--container);margin:0 auto;padding:0 24px;">
    <button class="hero-arrow" id="heroPrev">‹</button>
    <button class="hero-dot active" id="dot0"></button>
    @if($onSale->count() > 0)<button class="hero-dot" id="dot1"></button>@endif
    @if($newArrivals->count() > 0)<button class="hero-dot" id="dot2"></button>@endif
    <button class="hero-arrow" id="heroNext">›</button>
  </div>
</section>

{{-- TRUST STRIP --}}
<section class="trust-strip" data-store-native-section="benefits">
  <div class="container">
    <div class="trust-item">
      <div class="ti-ico">{{ $trustIcon1 }}</div>
      <div><h4>{{ $trustText1 }}</h4></div>
    </div>
    <div class="trust-item">
      <div class="ti-ico">{{ $trustIcon2 }}</div>
      <div><h4>{{ $trustText2 }}</h4></div>
    </div>
    <div class="trust-item">
      <div class="ti-ico">{{ $trustIcon3 }}</div>
      <div><h4>{{ $trustText3 }}</h4></div>
    </div>
    <div class="trust-item">
      <div class="ti-ico">{{ $trustIcon4 }}</div>
      <div><h4>{{ $trustText4 }}</h4></div>
    </div>
  </div>
</section>

{{-- SHOP --}}
<section class="section" id="catalogo">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Catálogo completo</span>
        <h2 class="serif">Explora la cava</h2>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-dim);cursor:pointer;">
          <input type="checkbox" x-model="onSaleFilter" style="accent-color:var(--gold);"> Solo ofertas
        </label>
        <select class="sort-select" x-model="sortBy" @change="applySort()">
          <option value="default">Orden por defecto</option>
          <option value="price_asc">Precio: menor a mayor</option>
          <option value="price_desc">Precio: mayor a menor</option>
          <option value="newest">Más recientes</option>
          <option value="name_az">Nombre A–Z</option>
        </select>
      </div>
    </div>

    <div class="shop">
      {{-- SIDEBAR FILTERS --}}
      <aside class="filters">
        <div class="filter-block">
          <div class="filter-head"><h4>Categoría</h4></div>
          <div class="check-list">
            <label :class="filterCat===''?'checked':''" @click="filterCat=''">
              <span class="check-box">✓</span> Todos
            </label>
            @foreach($categories as $cat)
            <label :class="filterCat==='{{ $cat->id }}'?'checked':''" @click="filterCat='{{ $cat->id }}'">
              <span class="check-box">✓</span> {{ $cat->name }}
              <span style="margin-left:auto;font-size:11px;color:var(--text-mute);">{{ $cat->products->count() }}</span>
            </label>
            @foreach($cat->children as $sub)
            <label style="padding-left:16px;" :class="filterCat==='{{ $sub->id }}'?'checked':''" @click="filterCat='{{ $sub->id }}'">
              <span class="check-box">✓</span> {{ $sub->name }}
              <span style="margin-left:auto;font-size:11px;color:var(--text-mute);">{{ $sub->products->count() }}</span>
            </label>
            @endforeach
            @endforeach
          </div>
        </div>
        <div class="filter-block">
          <div class="filter-head"><h4>Precio</h4></div>
          <div class="check-list">
            <label :class="priceFilter===''?'checked':''" @click="priceFilter=''"><span class="check-box">✓</span> Todos</label>
            <label :class="priceFilter==='0-50'?'checked':''" @click="priceFilter='0-50'"><span class="check-box">✓</span> Hasta {{ $currency }} 50</label>
            <label :class="priceFilter==='50-150'?'checked':''" @click="priceFilter='50-150'"><span class="check-box">✓</span> {{ $currency }} 50–150</label>
            <label :class="priceFilter==='150-500'?'checked':''" @click="priceFilter='150-500'"><span class="check-box">✓</span> {{ $currency }} 150–500</label>
            <label :class="priceFilter==='500+'?'checked':''" @click="priceFilter='500+'"><span class="check-box">✓</span> Más de {{ $currency }} 500</label>
          </div>
        </div>
        <div class="filter-block">
          <div class="filter-head"><h4>Disponibilidad</h4></div>
          <div class="tag-row">
            <span class="tag" :class="onSaleFilter?'on':''" @click="onSaleFilter=!onSaleFilter">⚡ En oferta</span>
          </div>
        </div>
        <button @click="filterCat='';priceFilter='';onSaleFilter=false;sortBy='default'" style="width:100%;padding:8px;border:1px solid var(--line);border-radius:8px;font-size:12px;color:var(--text-mute);margin-top:4px;transition:border-color .2s,color .2s;" onmouseover="this.style.borderColor='var(--gold)';this.style.color='var(--gold)'" onmouseout="this.style.borderColor='var(--line)';this.style.color='var(--text-mute)'">Limpiar filtros</button>
      </aside>

      {{-- PRODUCTS --}}
      <div>
        <div x-show="noResults" x-cloak style="text-align:center;padding:48px 0;color:var(--text-mute);">
          <div style="font-size:48px;margin-bottom:12px;">🍾</div>
          <p style="font-weight:600;color:var(--text);margin-bottom:4px;">Sin resultados</p>
          <p style="font-size:13px;">Prueba con otra búsqueda o categoría.</p>
        </div>

        {{-- Categorías con productos --}}
        @foreach($categories as $cat)
        @php $allCatProducts = $cat->products; @endphp
        @foreach($cat->children as $sub)
        @php $allCatProducts = $allCatProducts->concat($sub->products); @endphp
        @endforeach

        @if($allCatProducts->count() > 0)
        @php
          $_subChecks = [];
          foreach($cat->children as $_sc) { $_subChecks[] = "filterCat==='".$_sc->id."'"; }
          $_xshow = "filterCat===''||filterCat==='".$cat->id."'".($_subChecks ? '||'.implode('||',$_subChecks) : '');
        @endphp
        <div class="cat-section" data-cat-id="{{ $cat->id }}"
             x-show="{{ $_xshow }}"
             style="margin-bottom:40px;">
          <h3 class="serif" style="font-size:20px;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--line);color:var(--text);">
            {{ $cat->name }}
          </h3>
          <div class="products" data-products-grid>
            @foreach($allCatProducts as $idx => $product)
            @php
              $rating = $productRatings[$product->id] ?? null;
              $hasOffer = $product->compare_price && $product->compare_price > $product->price;
              $discount = $hasOffer ? round((1 - $product->price / $product->compare_price) * 100) : 0;
              $catName = $product->category?->name ?? $cat->name;
              $catId = $product->category_id ? (string)$product->category_id : (string)$cat->id;
            @endphp
            <article class="product"
                     data-price="{{ $product->price }}"
                     data-name="{{ strtolower($product->name) }}"
                     data-cat="{{ $catId }}"
                     data-ts="{{ $product->created_at?->timestamp ?? 0 }}"
                     data-idx="{{ $idx }}"
                     data-sale="{{ $hasOffer?'1':'0' }}"
                     x-show="matchProduct('{{ addslashes(strtolower($product->name)) }}',{{ $product->price }},{{ $product->compare_price ?? 'null' }},{{ $catId }})">

              <div class="product-img">
                @if($product->mainImage)
                  <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}" loading="lazy">
                @else
                  <svg class="bottle-svg" viewBox="0 0 100 160" fill="none">
                    <path d="M40 8h20v22c0 4 10 8 10 20v100a5 5 0 0 1-5 5H35a5 5 0 0 1-5-5V50c0-12 10-16 10-20z" fill="var(--bg-2)" stroke="var(--gold)" stroke-width=".8"/>
                    <rect x="34" y="80" width="32" height="45" fill="var(--bg-0)" stroke="var(--gold)" stroke-width=".5" opacity=".7"/>
                    <text x="50" y="100" text-anchor="middle" fill="var(--gold)" font-family="serif" font-size="6">{{ Str::upper(Str::limit($product->name,6)) }}</text>
                  </svg>
                @endif
                @if($hasOffer)<span class="badge-sale">-{{ $discount }}%</span>@endif
              </div>

              <div class="product-body">
                <div class="product-cat">{{ $catName }}</div>
                <div class="product-name">{{ $product->name }}</div>

                @if($rating)
                <div class="product-rating">
                  @for($s=1;$s<=5;$s++)
                  <span class="{{ $s <= round($rating->avg_rating) ? 'star-filled' : '' }}">★</span>
                  @endfor
                  <span>({{ $rating->rating_count }})</span>
                </div>
                @endif

                @if(!$isQuoteOnly || $quotePriceDisp==='show')
                <div class="product-price">
                  <span class="price-main">{{ $currency }} {{ number_format($product->price,2) }}</span>
                  @if($hasOffer)<span class="price-old">{{ $currency }} {{ number_format($product->compare_price,2) }}</span>@endif
                </div>
                @endif

                @if($product->stock !== null && $product->stock <= 5 && $product->stock > 0)
                <p style="font-size:11px;color:var(--wine);margin-top:4px;">Solo {{ $product->stock }} disponibles</p>
                @endif

                <button class="add-cart-btn"
                        @click="addToCart({id:{{ $product->id }},name:'{{ addslashes($product->name) }}',price:{{ $product->price }},img:'{{ $product->mainImage ? $product->main_image_url : '' }}'})">
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3h2l3 13h11l2-8H7"/><circle cx="10" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/></svg>
                  {{ $isQuoteOnly ? $btnQuoteText : $btnCartText }}
                </button>
              </div>
            </article>
            @endforeach
          </div>
        </div>
        @endif
        @endforeach
      </div>
    </div>
  </div>
</section>

{{-- OFFERS SECTION --}}
@if($onSale->count() > 0)
<section class="section" data-store-native-section="discounts" style="background:var(--bg-1);padding:48px 0;">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">🔥 Descuentos activos</span>
        <h2 class="serif">En oferta ahora</h2>
      </div>
    </div>
    <div class="products" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr));">
      @foreach($onSale->take(6) as $product)
      @php
        $cpd = $product->compare_price && $product->compare_price > $product->price ? round((1-$product->price/$product->compare_price)*100) : 0;
      @endphp
      <article class="product">
        <div class="product-img">
          @if($product->mainImage)
            <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}" loading="lazy">
          @else
            <svg class="bottle-svg" viewBox="0 0 100 160" fill="none"><path d="M40 8h20v22c0 4 10 8 10 20v100a5 5 0 0 1-5 5H35a5 5 0 0 1-5-5V50c0-12 10-16 10-20z" fill="var(--bg-2)" stroke="var(--gold)" stroke-width=".8"/></svg>
          @endif
          <span class="badge-sale">-{{ $cpd }}%</span>
        </div>
        <div class="product-body">
          <div class="product-cat">{{ $product->category?->name }}</div>
          <div class="product-name">{{ $product->name }}</div>
          @if(!$isQuoteOnly || $quotePriceDisp==='show')
          <div class="product-price">
            <span class="price-main">{{ $currency }} {{ number_format($product->price,2) }}</span>
            <span class="price-old">{{ $currency }} {{ number_format($product->compare_price,2) }}</span>
          </div>
          @endif
          <button class="add-cart-btn"
                  @click="addToCart({id:{{ $product->id }},name:'{{ addslashes($product->name) }}',price:{{ $product->price }},img:'{{ $product->mainImage ? $product->main_image_url : '' }}'})">
            {{ $isQuoteOnly ? $btnQuoteText : $btnCartText }}
          </button>
        </div>
      </article>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- FOOTER --}}
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        @if($logoUrl)
          <img src="{{ asset('storage/'.$logoUrl) }}" alt="{{ $project->name }}" style="height:{{ $logoHeight }}px;object-fit:contain;margin-bottom:12px;">
        @else
          <div class="brand-name serif" style="font-size:22px;margin-bottom:8px;color:var(--text);">{{ $project->name }}</div>
        @endif
        <p class="footer-tagline">{{ $footerTagline }}</p>
        <div class="social-row">
          @if($settings['facebook_url'] ?? null)<a href="{{ $settings['facebook_url'] }}" target="_blank" class="soc-btn" title="Facebook">f</a>@endif
          @if($settings['instagram_url'] ?? null)<a href="{{ $settings['instagram_url'] }}" target="_blank" class="soc-btn" title="Instagram">ig</a>@endif
          @if($settings['tiktok_url'] ?? null)<a href="{{ $settings['tiktok_url'] }}" target="_blank" class="soc-btn" title="TikTok">tt</a>@endif
          @if($project->whatsapp)<a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}" target="_blank" class="soc-btn" title="WhatsApp">wa</a>@endif
        </div>
      </div>

      <div class="footer-col">
        <h4>Catálogo</h4>
        @foreach($categories->take(6) as $cat)
        <a href="#catalogo" @click.prevent="filterCat='{{ $cat->id }}';scrollToCatalog()">{{ $cat->name }}</a>
        @endforeach
      </div>

      <div class="footer-col">
        <h4>Información</h4>
        @if($project->phone)<a href="tel:{{ $project->phone }}">📞 {{ $project->phone }}</a>@endif
        @if($project->whatsapp)<a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}" target="_blank">💬 WhatsApp</a>@endif
        @if($project->email ?? null)<a href="mailto:{{ $project->email }}">✉️ {{ $project->email }}</a>@endif
        @if($project->address ?? null)<a href="#">📍 {{ $project->address }}</a>@endif
      </div>

      <div class="footer-col">
        <h4>Pagos aceptados</h4>
        @if(count($acceptedPayments))
          @foreach($acceptedPayments as $pm)
          @if(isset($paymentMeta[$pm]))
          <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-mute);margin-bottom:8px;">
            <span>{{ $paymentMeta[$pm]['emoji'] }}</span> {{ $paymentMeta[$pm]['label'] }}
          </div>
          @endif
          @endforeach
        @else
          <p style="font-size:13px;color:var(--text-mute);">Efectivo, transferencia y más.</p>
        @endif
      </div>
    </div>

    <div class="footer-bottom">
      <p>{{ $footerCopyright }}</p>
      <p style="display:flex;align-items:center;gap:6px;">
        Venta responsable — Solo mayores de 18 años 🔞
      </p>
    </div>
  </div>
</footer>

{{-- CART DRAWER --}}
<div x-show="drawerOpen" x-cloak>
  <div class="drawer-overlay" @click="drawerOpen=false"></div>
  <div class="drawer" role="dialog"
       x-show="drawerOpen"
       x-transition:enter="transition ease-out duration-300 transform"
       x-transition:enter-start="translate-x-full"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-200 transform"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="translate-x-full">

    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--line);flex-shrink:0;">
      <div style="display:flex;align-items:center;gap:10px;">
        <span style="font-weight:800;font-size:16px;color:var(--text);">{{ $isQuoteOnly?'Cotización':'Tu pedido' }}</span>
        <span x-show="cart.length&&drawerStep===1" style="background:var(--gold);color:var(--bg-0);font-size:11px;font-weight:800;padding:2px 8px;border-radius:20px;" x-text="cart.length+' items'" x-cloak></span>
      </div>
      <button @click="drawerOpen=false" style="width:36px;height:36px;border-radius:50%;background:var(--bg-2);display:flex;align-items:center;justify-content:center;color:var(--text-dim);font-size:18px;">×</button>
    </div>

    {{-- STEP 1: Cart --}}
    <div x-show="drawerStep===1" style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
      <div style="flex:1;overflow-y:auto;padding:16px 20px;">
        <template x-if="cart.length===0">
          <div style="text-align:center;padding:48px 0;color:var(--text-mute);">
            <div style="font-size:48px;margin-bottom:12px;">🍾</div>
            <p style="font-weight:700;color:var(--text);margin:0 0 4px;">{{ $isQuoteOnly?'Tu cotización está vacía':'Tu carrito está vacío' }}</p>
            <p style="font-size:13px;margin:0;">Agrega productos para comenzar</p>
          </div>
        </template>
        <template x-for="(item,i) in cart" :key="item.id">
          <div style="display:flex;align-items:center;gap:12px;background:var(--bg-2);padding:12px;border-radius:10px;margin-bottom:10px;">
            <div style="width:52px;height:52px;border-radius:8px;overflow:hidden;background:var(--bg-3);flex-shrink:0;display:flex;align-items:center;justify-content:center;">
              <img :src="item.img" x-show="item.img" style="width:100%;height:100%;object-fit:cover;">
              <span x-show="!item.img" style="font-size:20px;">🍾</span>
            </div>
            <div style="flex:1;min-width:0;">
              <p style="font-size:13px;font-weight:700;color:var(--text);margin:0 0 4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="item.name"></p>
              @if(!$isQuoteOnly || $quotePriceDisp==='show')
              <p style="font-size:13px;font-weight:800;color:var(--gold);margin:0;" x-text="'{{ $currency }} '+(item.price*item.qty).toFixed(2)"></p>
              @endif
            </div>
            <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
              <button @click="item.qty>1?item.qty--:cart.splice(i,1)" style="width:28px;height:28px;border:1.5px solid var(--line);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:14px;color:var(--text-dim);transition:border-color .15s;" onmouseover="this.style.borderColor='var(--wine)';this.style.color='var(--wine)'" onmouseout="this.style.borderColor='var(--line)';this.style.color='var(--text-dim)'">
                <span x-text="item.qty>1?'−':'×'"></span>
              </button>
              <span style="width:24px;text-align:center;font-weight:800;color:var(--text);" x-text="item.qty"></span>
              <button @click="item.qty++" style="width:28px;height:28px;background:var(--gold);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:14px;color:var(--bg-0);font-weight:800;">+</button>
            </div>
          </div>
        </template>
      </div>
      <div x-show="cart.length>0" style="border-top:1px solid var(--line);padding:16px 20px;flex-shrink:0;" x-cloak>
        @if(!$isQuoteOnly || $quotePriceDisp==='show')
        <div style="display:flex;justify-content:space-between;margin-bottom:12px;">
          <span style="color:var(--text-dim);font-size:14px;"><span x-text="cartCount"></span> productos</span>
          <span style="font-weight:800;font-size:18px;color:var(--gold);" x-text="'{{ $currency }} '+cartTotal.toFixed(2)"></span>
        </div>
        @endif
        <button @click="drawerOpen=false;checkoutOpen=true;orderError=''" style="width:100%;padding:14px;background:var(--gold);color:var(--bg-0);border-radius:10px;font-weight:800;font-size:14px;letter-spacing:.05em;transition:background .2s;" onmouseover="this.style.background='var(--gold-2)'" onmouseout="this.style.background='var(--gold)'">
          {{ $isQuoteOnly?'Continuar y cotizar':'Continuar y pedir' }} →
        </button>
      </div>
    </div>

  </div>
</div>

{{-- CHECKOUT FULLSCREEN --}}
<div x-show="checkoutOpen" x-cloak style="position:fixed;inset:0;z-index:200;background:#f4f4f4;overflow-y:auto;">
  <div style="max-width:1100px;margin:0 auto;padding:24px 16px 60px;">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
      <div style="display:flex;align-items:center;gap:12px;">
        <button @click="checkoutOpen=false;drawerOpen=true" style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-dim);background:none;border:none;cursor:pointer;padding:0;">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg>
          Volver al carrito
        </button>
      </div>
      <span style="font-weight:800;font-size:18px;color:var(--text);">{{ $isQuoteOnly?'Cotización':'Checkout' }}</span>
      <button @click="checkoutOpen=false" style="width:36px;height:36px;border-radius:50%;background:#fff;border:1px solid #e5e7eb;display:flex;align-items:center;justify-content:center;font-size:18px;color:#6b7280;cursor:pointer;">×</button>
    </div>

    {{-- Cupón --}}
    <div style="background:#fff;border-radius:12px;padding:14px 18px;margin-bottom:18px;border:1px solid #e5e7eb;">
      <div x-show="!couponApplied" style="display:flex;align-items:center;gap:8px;">
        <span style="font-size:13px;color:#6b7280;">¿Tienes un cupón?</span>
        <button @click="couponFieldOpen=!couponFieldOpen" style="font-size:13px;color:var(--gold);font-weight:600;text-decoration:underline;background:none;border:none;cursor:pointer;">Haz clic aquí para introducir tu código</button>
      </div>
      <div x-show="couponFieldOpen&&!couponApplied" style="display:flex;gap:8px;margin-top:10px;">
        <input x-model="couponCode" @keydown.enter.prevent="applyCoupon" type="text" placeholder="Código de descuento" style="flex:1;border:1px solid #e5e7eb;border-radius:8px;padding:9px 12px;font-size:13px;outline:none;text-transform:uppercase;">
        <button @click="applyCoupon" :disabled="couponLoading" style="padding:9px 16px;background:var(--gold);color:var(--bg-0);border-radius:8px;font-size:13px;font-weight:700;" x-text="couponLoading?'…':'Aplicar'"></button>
      </div>
      <div x-show="couponApplied" style="display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:13px;color:#059669;font-weight:700;" x-text="'Cupón aplicado: '+(couponApplied?couponApplied.code:'')"></span>
        <button @click="removeCoupon" style="color:#6b7280;font-size:18px;background:none;border:none;cursor:pointer;">&times;</button>
      </div>
      <p x-show="couponError" style="color:#dc2626;font-size:12px;margin:6px 0 0;" x-text="couponError"></p>
    </div>

    {{-- 2 columnas --}}
    <div class="ck-grid">

      {{-- Col izquierda: formulario --}}
      <div>
        @php $inp = "width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:11px 14px;font-size:14px;outline:none;background:#fff;color:#111;box-sizing:border-box;"; @endphp
        <div style="background:#fff;border-radius:12px;padding:24px;border:1px solid #e5e7eb;margin-bottom:18px;">
          <h2 style="font-size:15px;font-weight:800;color:#111;margin:0 0 18px;text-transform:uppercase;letter-spacing:.05em;">Datos de contacto</h2>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
            <div>
              <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Nombres <span style="color:#dc2626;">*</span></label>
              <input x-model="form.fname" type="text" placeholder="" style="{{ $inp }}" autocomplete="given-name">
            </div>
            <div>
              <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Apellidos <span style="color:#dc2626;">*</span></label>
              <input x-model="form.lname" type="text" placeholder="" style="{{ $inp }}" autocomplete="family-name">
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
            <div>
              <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Celular <span style="color:#dc2626;">*</span></label>
              <input x-model="form.phone" type="tel" placeholder="" style="{{ $inp }}" autocomplete="tel">
            </div>
            <div>
              <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Correo electrónico <span style="color:#dc2626;">*</span></label>
              <input x-model="form.email" type="email" placeholder="" style="{{ $inp }}" autocomplete="email">
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
              <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">RUC ó DNI</label>
              <input x-model="form.dni" type="text" placeholder="" style="{{ $inp }}">
            </div>
            <div>
              <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">País</label>
              <input type="text" value="Perú" readonly style="{{ $inp }}background:#f9fafb;color:#6b7280;">
            </div>
          </div>
        </div>

        @if($requireAddress)
        <div style="background:#fff;border-radius:12px;padding:24px;border:1px solid #e5e7eb;margin-bottom:18px;">
          <h2 style="font-size:15px;font-weight:800;color:#111;margin:0 0 18px;text-transform:uppercase;letter-spacing:.05em;">Dirección de entrega</h2>
          <div style="margin-bottom:12px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Departamento <span style="color:#dc2626;">*</span></label>
            <input x-model="form.department" type="text" placeholder="" style="{{ $inp }}">
          </div>
          <div style="margin-bottom:12px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Distrito <span style="color:#dc2626;">*</span></label>
            <input x-model="form.district" type="text" placeholder="" style="{{ $inp }}">
          </div>
          <div style="margin-bottom:12px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Dirección de entrega <span style="color:#dc2626;">*</span></label>
            <input x-model="form.address" type="text" placeholder="Nombre de la calle y número de la casa" style="{{ $inp }}">
          </div>
          <div>
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Referencia (opcional)</label>
            <input x-model="form.address2" type="text" placeholder="Apartamento, referencia, etc." style="{{ $inp }}">
          </div>
        </div>
        @endif

        <div style="background:#fff;border-radius:12px;padding:24px;border:1px solid #e5e7eb;">
          <h2 style="font-size:15px;font-weight:800;color:#111;margin:0 0 14px;text-transform:uppercase;letter-spacing:.05em;">Información adicional</h2>
          <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Notas del pedido <span style="font-weight:400;color:#9ca3af;">(opcional)</span></label>
          <textarea x-model="form.notes" rows="3" placeholder="Notas sobre tu pedido, por ejemplo, notas especiales para la entrega." style="{{ $inp }}resize:none;"></textarea>
        </div>
      </div>

      {{-- Col derecha: resumen + pago --}}
      <div>
        <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;margin-bottom:18px;">
          <div style="padding:18px 20px;border-bottom:1px solid #f3f4f6;">
            <h2 style="font-size:15px;font-weight:800;color:#111;margin:0;text-transform:uppercase;letter-spacing:.05em;">Tu pedido</h2>
          </div>
          <div style="padding:0 20px;">
            <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f3f4f6;">
              <span style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;">Producto</span>
              <span style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;">Subtotal</span>
            </div>
            <template x-for="(item,i) in cart" :key="item.id">
              <div style="display:flex;align-items:center;gap:10px;padding:12px 0;border-bottom:1px solid #f3f4f6;">
                <div style="width:40px;height:40px;border-radius:8px;overflow:hidden;background:#f3f4f6;flex-shrink:0;">
                  <img :src="item.img" x-show="item.img" style="width:100%;height:100%;object-fit:cover;">
                </div>
                <div style="flex:1;min-width:0;">
                  <p style="font-size:13px;font-weight:600;color:#111;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="item.name"></p>
                  <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
                    <button @click="item.qty>1?item.qty--:cart.splice(i,1)" style="width:22px;height:22px;border:1px solid #e5e7eb;border-radius:4px;font-size:13px;display:flex;align-items:center;justify-content:center;color:#374151;cursor:pointer;background:#fff;">
                      <span x-text="item.qty>1?'−':'×'"></span>
                    </button>
                    <span style="font-size:13px;font-weight:700;color:#111;" x-text="item.qty"></span>
                    <button @click="item.qty++" style="width:22px;height:22px;background:var(--gold);border-radius:4px;font-size:13px;display:flex;align-items:center;justify-content:center;color:var(--bg-0);font-weight:800;cursor:pointer;">+</button>
                  </div>
                </div>
                @if(!$isQuoteOnly || $quotePriceDisp==='show')
                <span style="font-size:14px;font-weight:700;color:var(--gold);flex-shrink:0;" x-text="'{{ $currency }} '+(item.price*item.qty).toFixed(2)"></span>
                @endif
              </div>
            </template>
          </div>
          @if(!$isQuoteOnly || $quotePriceDisp==='show')
          <div style="padding:14px 20px;background:#fafafa;border-top:1px solid #f3f4f6;">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
              <span style="font-size:13px;color:#6b7280;">Subtotal</span>
              <span style="font-size:13px;color:var(--gold);font-weight:700;" x-text="'{{ $currency }} '+subtotal.toFixed(2)"></span>
            </div>
            <div x-show="couponApplied&&couponDiscount>0" style="display:flex;justify-content:space-between;margin-bottom:8px;">
              <span style="font-size:13px;color:#059669;">Descuento</span>
              <span style="font-size:13px;color:#059669;font-weight:700;" x-text="'- {{ $currency }} '+couponDiscount.toFixed(2)"></span>
            </div>
            <div x-show="shippingEnabled" style="display:flex;justify-content:space-between;margin-bottom:8px;">
              <span style="font-size:13px;color:#6b7280;" x-text="effectiveShipping===0&&shippingFreeFrom>0?'Envío gratis':'Envío'"></span>
              <span style="font-size:13px;font-weight:700;" :style="effectiveShipping===0?'color:#059669':'color:#111'" x-text="effectiveShipping>0?'{{ $currency }} '+effectiveShipping.toFixed(2):'Gratis'"></span>
            </div>
            <div style="display:flex;justify-content:space-between;border-top:1px solid #e5e7eb;padding-top:10px;">
              <span style="font-size:15px;font-weight:800;color:#111;">Total</span>
              <span style="font-size:15px;font-weight:800;color:var(--gold);" x-text="'{{ $currency }} '+orderGrandTotal.toFixed(2)"></span>
            </div>
          </div>
          @endif
        </div>

        @if($shippingEnabled && $quoteWa)
        <a href="https://wa.me/{{ $quoteWa }}" target="_blank"
           style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:13px;background:#25D366;color:#fff;border-radius:10px;font-weight:700;font-size:14px;text-decoration:none;margin-bottom:14px;box-sizing:border-box;">
          <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          Cotiza tu envío con nosotros
        </a>
        @endif

        {{-- Métodos de pago --}}
        @if($hasOnlinePayment || $payManualEnabled)
        <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;margin-bottom:14px;">
          @if($payManualEnabled && count($payManualMethods)>0)
          @foreach($payManualMethods as $mKey)
          @php
            $mMeta=['yape'=>['label'=>'Yape 👆','color'=>'#6d1ed4','qr'=>true],'plin'=>['label'=>'Plin','color'=>'#00b5e2','qr'=>false],'transferencia'=>['label'=>'Transferencia bancaria','color'=>'#374151','qr'=>false],'qr'=>['label'=>'Pago con QR','color'=>'#374151','qr'=>false],'contra_entrega'=>['label'=>'Contra entrega','color'=>'#374151','qr'=>false]];
            $mm=$mMeta[$mKey]??['label'=>$mKey,'color'=>'#374151','qr'=>false];
            $mmDetails=match($mKey){'yape'=>$payYapeNumber,'plin'=>$payPlinNumber,'transferencia'=>$payBankDetails,default=>''};
          @endphp
          <div x-data="{open:false}" style="border-bottom:1px solid #f3f4f6;">
            <label style="display:flex;align-items:center;gap:12px;padding:14px 18px;cursor:pointer;">
              <input type="radio" name="pay_method_lic" value="{{ $mKey }}" x-model="payMethod" style="accent-color:var(--gold);width:16px;height:16px;">
              <span style="font-size:14px;font-weight:600;color:#111;flex:1;">Paga con {{ $mm['label'] }}</span>
              @if($mKey==='yape')<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/7/7b/Yape_logo.svg/200px-Yape_logo.svg.png" style="height:24px;object-fit:contain;" alt="Yape">@endif
            </label>
            <div x-show="payMethod==='{{ $mKey }}'" style="padding:16px 18px 18px;background:#f9fafb;border-top:1px solid #f3f4f6;">
              @if($mKey==='yape' && $payYapeNumber)
              <div style="display:flex;gap:16px;background:#6d1ed4;border-radius:12px;padding:16px;align-items:center;">
                <div style="flex:1;">
                  {{-- QR Yape generado con API pública --}}
                  <div style="width:150px;height:150px;background:#fff;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($payYapeNumber) }}" style="width:150px;height:150px;" alt="QR Yape">
                  </div>
                </div>
                <div style="color:#fff;flex:1;">
                  <div style="width:40px;height:40px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:8px;">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/7/7b/Yape_logo.svg/200px-Yape_logo.svg.png" style="width:28px;height:28px;object-fit:contain;" alt="">
                  </div>
                  <p style="font-size:12px;margin:0 0 4px;opacity:.8;">Celular Yape:</p>
                  <p style="font-size:22px;font-weight:900;margin:0 0 10px;letter-spacing:.05em;">{{ $payYapeNumber }}</p>
                  <a href="https://wa.me/51{{ preg_replace('/\D/','',$payYapeNumber) }}" target="_blank" style="display:inline-block;background:#25D366;color:#fff;padding:8px 16px;border-radius:20px;font-size:12px;font-weight:700;text-decoration:none;">Añadir a contacto</a>
                </div>
              </div>
              @endif
              @if($mKey==='plin' && $payPlinNumber)
              <p style="font-size:14px;color:#374151;margin:0 0 6px;">Número Plin: <strong>{{ $payPlinNumber }}</strong></p>
              @endif
              @if($mKey==='transferencia' && $payBankDetails)
              <p style="font-size:13px;color:#374151;white-space:pre-line;margin:0 0 10px;">{{ $payBankDetails }}</p>
              @endif
              @if($payManualInstr)<p style="font-size:12px;color:#6b7280;margin-bottom:10px;">{{ $payManualInstr }}</p>@endif
              <div style="margin-top:12px;">
                <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Número de operación *</label>
                <input x-model="payReference" type="text" placeholder="Ej: 123456789" style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:9px 12px;font-size:13px;outline:none;box-sizing:border-box;">
              </div>
            </div>
          </div>
          @endforeach
          @endif

          @if($culqiEnabled && $culqiPublicKey)
          <div style="border-bottom:1px solid #f3f4f6;">
            <label style="display:flex;align-items:center;gap:12px;padding:14px 18px;cursor:pointer;">
              <input type="radio" name="pay_method_lic" value="culqi" x-model="payMethod" style="accent-color:var(--gold);width:16px;height:16px;">
              <span style="font-size:14px;font-weight:600;color:#111;flex:1;">💳 Tarjeta crédito / débito</span>
            </label>
            <div x-show="payMethod==='culqi'" style="padding:14px 18px;background:#f9fafb;border-top:1px solid #f3f4f6;">
              <p style="font-size:12px;color:#6b7280;margin:0;">Pago seguro con Culqi — Visa, Mastercard</p>
            </div>
          </div>
          <script src="https://checkout.culqi.com/js/v4"></script>
          @endif

          @if($mpEnabled)
          <div>
            <label style="display:flex;align-items:center;gap:12px;padding:14px 18px;cursor:pointer;">
              <input type="radio" name="pay_method_lic" value="mp" x-model="payMethod" style="accent-color:var(--gold);width:16px;height:16px;">
              <span style="font-size:14px;font-weight:600;color:#111;flex:1;">🛒 Mercado Pago</span>
            </label>
          </div>
          @endif
        </div>
        @endif

        <p x-show="orderError" style="color:#dc2626;font-size:13px;text-align:center;margin-bottom:10px;" x-text="orderError"></p>

        <button @click="submitCheckout()" :disabled="orderLoading"
                style="width:100%;padding:16px;background:var(--gold);color:var(--bg-0);border-radius:10px;font-weight:800;font-size:15px;display:flex;align-items:center;justify-content:center;gap:8px;box-sizing:border-box;">
          <span x-show="!orderLoading">{{ $isQuoteOnly?($quoteWa?'📲 Enviar por WhatsApp':'Solicitar cotización'):'✅ Realizar el pedido' }}</span>
          <span x-show="orderLoading">Procesando...</span>
        </button>
      </div>
    </div>
  </div>
</div>

{{-- FLOATING BAR --}}
<div x-show="cartCount>0" x-cloak class="floating-bar" style="background:var(--bg-1);border-top:1px solid var(--line);">
  <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:0;">
    <span style="background:var(--gold);color:var(--bg-0);font-size:11px;font-weight:800;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;" x-text="cartCount"></span>
    <div>
      <p style="font-size:10px;color:var(--text-mute);margin:0 0 2px;">Total del pedido</p>
      @if(!$isQuoteOnly || $quotePriceDisp==='show')
      <p style="font-weight:800;font-size:16px;color:var(--gold);margin:0;" x-text="'{{ $currency }} '+cartTotal.toFixed(2)"></p>
      @endif
    </div>
  </div>
  <button @click="drawerOpen=true;drawerStep=1" style="background:var(--gold);color:var(--bg-0);padding:12px 20px;border-radius:30px;font-weight:800;font-size:13px;display:flex;align-items:center;gap:8px;flex-shrink:0;">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3h2l3 13h11l2-8H7"/><circle cx="10" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/></svg>
    {{ $isQuoteOnly?'Ver cotización':'Ver pedido' }}
  </button>
</div>

{{-- ALPINE STORE --}}
<script>
function licStore() {
  const _cartKey = 'avan_cart_{{ $project->id }}';
  const _formKey = 'avan_form_{{ $project->id }}';
  let _savedCart = [], _savedForm = {name:'',fname:'',lname:'',phone:'',email:'',dni:'',department:'',district:'',address:'',address2:'',notes:''};
  try {
    const c = localStorage.getItem(_cartKey); if(c) _savedCart = JSON.parse(c);
    const f = localStorage.getItem(_formKey); if(f) _savedForm = {..._savedForm,...JSON.parse(f)};
  } catch(e){}

  return {
    _cartKey, _formKey,
    darkMode: false,
    search:'', filterCat:'', priceFilter:'', onSaleFilter:false, sortBy:'default',
    searchOpen:false, searchIdx:-1,
    drawerOpen:false, drawerStep:1,
    checkoutOpen:false, couponFieldOpen:false, payMethod:'',
    cart:_savedCart, form:_savedForm,
    orderLoading:false, orderSent:false, orderError:'', orderId:null, orderTotal:0,
    noResults:false,
    shippingEnabled:  {{ $shippingEnabled  ? 'true':'false' }},
    shippingCost:     {{ $shippingCost }},
    shippingFreeFrom: {{ $shippingFreeFrom }},
    couponCode:'', couponApplied:null, couponError:'', couponLoading:false,
    payReference:'', payLoading:false, payError:'',
    searchIndex: @json($searchIndex),

    get suggestions() {
      if(!this.search||this.search.trim().length<2) return [];
      const q=this.search.toLowerCase().trim();
      return this.searchIndex.filter(p=>p.name.toLowerCase().includes(q)||(p.cat&&p.cat.toLowerCase().includes(q))).slice(0,6);
    },
    get cartCount() { return this.cart.reduce((s,i)=>s+i.qty,0); },
    get cartTotal()  { return this.cart.reduce((s,i)=>s+i.price*i.qty,0); },
    get subtotal()   { return this.cartTotal; },
    get effectiveShipping() {
      if(!this.shippingEnabled) return 0;
      if(this.shippingFreeFrom>0&&this.subtotal>=this.shippingFreeFrom) return 0;
      return this.shippingCost;
    },
    get couponDiscount() {
      if(!this.couponApplied) return 0;
      const sub=this.subtotal;
      if(sub<(this.couponApplied.min_order||0)) return 0;
      if(this.couponApplied.type==='percent') return Math.min(sub*this.couponApplied.value/100,sub);
      return Math.min(this.couponApplied.value,sub);
    },
    get orderGrandTotal() { return Math.max(0,this.subtotal-this.couponDiscount+this.effectiveShipping); },

    init() {
      // Theme: light by default
      const saved = localStorage.getItem('vs_theme');
      this.darkMode = saved === 'dark';

      this.$watch('cart', val=>{ try{localStorage.setItem(this._cartKey,JSON.stringify(val));}catch(e){} });
      this.$watch('form', val=>{ try{localStorage.setItem(this._formKey,JSON.stringify(val));}catch(e){} },{deep:true});

      // Utility bar collapse on scroll
      let lastY = 0;
      window.addEventListener('scroll', ()=>{
        const y = window.scrollY;
        const bar = document.getElementById('utilityBar');
        if(bar){ if(y>80&&y>lastY) bar.classList.add('is-collapsed'); else if(y<30) bar.classList.remove('is-collapsed'); }
        lastY = y;
      }, {passive:true});

      // Hero carousel
      const track = document.getElementById('heroTrack');
      if(track) {
        const slides = track.children.length;
        let idx = 0;
        const dots = document.querySelectorAll('.hero-dot');
        const goTo = n => {
          idx = (n+slides)%slides;
          track.style.transform = 'translateX(-'+idx*100+'%)';
          dots.forEach((d,i)=>d.classList.toggle('active',i===idx));
        };
        dots.forEach((d,i)=>d.addEventListener('click',()=>goTo(i)));
        document.getElementById('heroPrev')?.addEventListener('click',()=>goTo(idx-1));
        document.getElementById('heroNext')?.addEventListener('click',()=>goTo(idx+1));
        let timer = setInterval(()=>goTo(idx+1),6000);
        track.addEventListener('mouseenter',()=>clearInterval(timer));
        track.addEventListener('mouseleave',()=>{ timer=setInterval(()=>goTo(idx+1),6000); });
      }
    },

    scrollToCatalog() {
      const el = document.getElementById('catalogo');
      if(el) el.scrollIntoView({behavior:'smooth',block:'start'});
    },

    matchProduct(name, price, comparePrice, catId) {
      const s = this.search.toLowerCase().trim();
      if(s && !name.includes(s)) return false;
      if(this.filterCat && String(catId) !== this.filterCat) return false;
      if(this.priceFilter==='0-50'     && price>50) return false;
      if(this.priceFilter==='50-150'   && (price<=50||price>150)) return false;
      if(this.priceFilter==='150-500'  && (price<=150||price>500)) return false;
      if(this.priceFilter==='500+'     && price<=500) return false;
      if(this.onSaleFilter && !(comparePrice && comparePrice>price)) return false;
      return true;
    },

    applySort() {
      const grids = document.querySelectorAll('[data-products-grid]');
      grids.forEach(grid=>{
        const cards = Array.from(grid.querySelectorAll('[data-price]'));
        cards.sort((a,b)=>{
          if(this.sortBy==='price_asc')  return (parseFloat(a.dataset.price)||0)-(parseFloat(b.dataset.price)||0);
          if(this.sortBy==='price_desc') return (parseFloat(b.dataset.price)||0)-(parseFloat(a.dataset.price)||0);
          if(this.sortBy==='newest')     return (parseInt(b.dataset.ts)||0)-(parseInt(a.dataset.ts)||0);
          if(this.sortBy==='name_az')    return (a.dataset.name||'').localeCompare(b.dataset.name||'','es');
          return (parseInt(a.dataset.idx)||0)-(parseInt(b.dataset.idx)||0);
        });
        cards.forEach(c=>grid.appendChild(c));
      });
    },

    addToCart(product) {
      const ex = this.cart.find(i=>i.id===product.id);
      if(ex){ ex.qty++; } else { this.cart.push({...product,qty:1}); }
      const t = document.getElementById('vs-toast');
      if(t){ t.textContent='🥃 '+product.name+' agregado'; t.classList.add('show'); clearTimeout(this._tt); this._tt=setTimeout(()=>t.classList.remove('show'),2500); }
    },

    async applyCoupon() {
      if(!this.couponCode.trim()) return;
      this.couponLoading=true; this.couponError='';
      const res=await fetch('/{{ $project->slug }}/coupon',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({code:this.couponCode,subtotal:this.subtotal,shipping:this.effectiveShipping})});
      const d=await res.json();
      this.couponLoading=false;
      if(d.ok){this.couponApplied=d;this.couponError='';}
      else{this.couponError=d.message;this.couponApplied=null;}
    },
    removeCoupon() { this.couponApplied=null;this.couponCode='';this.couponError=''; },

    sendQuoteWhatsapp() {
      if(!this.form.name.trim()){this.orderError='Por favor ingresa tu nombre.';return;}
      const fecha=new Date().toLocaleDateString('es-PE',{day:'2-digit',month:'long',year:'numeric'});
      let lines=`🛒 *SOLICITUD DE COTIZACIÓN*\n━━━━━━━━━━━━━━━━━━━━━━\n🏪 *{{ addslashes($project->name) }}*\n\n{{ addslashes($quoteWaMsg) }}\n\n👤 *DATOS*\n• Nombre: ${this.form.name}\n`;
      if(this.form.phone) lines+=`• Teléfono: ${this.form.phone}\n`;
      lines+=`\n📦 *PRODUCTOS*\n━━━━━━━━━━━━━━━━━━━━━━\n`;
      let total=0;
      this.cart.forEach((item,idx)=>{
        @if(!$isQuoteOnly || $quotePriceDisp==='show')
        const sub=(item.price*item.qty).toFixed(2);
        lines+=`${idx+1}. *${item.name}*\n   Cant: ${item.qty}  •  {{ $currency }} ${sub}\n`;
        total+=item.price*item.qty;
        @else
        lines+=`${idx+1}. *${item.name}* — cant: ${item.qty}\n`;
        @endif
      });
      @if(!$isQuoteOnly || $quotePriceDisp==='show')
      lines+=`━━━━━━━━━━━━━━━━━━━━━━\n💰 *Total referencial: {{ $currency }} ${total.toFixed(2)}*\n`;
      @endif
      if(this.form.notes) lines+=`\n📝 Nota: ${this.form.notes}\n`;
      lines+=`\n📅 Fecha: ${fecha}`;
      window.open(`https://wa.me/{{ $quoteWa }}?text=${encodeURIComponent(lines)}`,'_blank');
      this.cart=[];this.orderSent=true;
      try{localStorage.removeItem(this._cartKey);localStorage.removeItem(this._formKey);}catch(e){}
    },

    async submitOrder() {
      if(!this.form.name.trim()||!this.form.phone.trim()){this.orderError='Ingresa tu nombre y teléfono.';return;}
      this.orderLoading=true;this.orderError='';
      const items=this.cart.map(i=>({product_id:i.id,name:i.name,price:i.price,quantity:i.qty}));
      try {
        const res=await fetch('{{ route("public.order",$project->slug) }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({client_name:this.form.name,client_phone:this.form.phone,client_email:this.form.email,notes:this.form.notes,coupon_code:this.couponApplied?this.couponApplied.code:null,delivery_address:this.form.address||null,shipping_cost:this.effectiveShipping>0?this.effectiveShipping:null,items})});
        const data=await res.json();
        if(data.ok){
          @if($isQuoteOnly && $quoteWa)
          this.sendQuoteWhatsapp();
          @elseif(!$isQuoteOnly && $hasOnlinePayment)
          this.orderId=data.order_id;this.orderTotal=data.total;this.orderSent=false;this.payReference='';this.payError='';this.drawerStep=3;
          @else
          try{localStorage.removeItem(this._cartKey);localStorage.removeItem(this._formKey);}catch(e){}
          window.location.href='/{{ $project->slug }}/thanks/'+data.order_id;
          @endif
        } else{this.orderError=data.message||'No se pudo enviar. Inténtalo de nuevo.';}
      } catch(e){this.orderError='Error de conexión.';}
      this.orderLoading=false;
    },

    async submitCheckout() {
      const fullName = ((this.form.fname||'').trim()+' '+(this.form.lname||'').trim()).trim() || this.form.name.trim();
      if(!fullName){this.orderError='Ingresa tu nombre.';return;}
      if(!this.form.phone.trim()){this.orderError='Ingresa tu celular.';return;}
      this.form.name = fullName;
      this.orderLoading=true;this.orderError='';
      const addr = [this.form.address,this.form.address2,this.form.district,this.form.department].filter(Boolean).join(', ');
      const items=this.cart.map(i=>({product_id:i.id,name:i.name,price:i.price,quantity:i.qty}));
      const notes = [this.form.notes, this.form.dni?'DNI/RUC: '+this.form.dni:''].filter(Boolean).join(' | ');
      try {
        const res=await fetch('{{ route("public.order",$project->slug) }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({client_name:fullName,client_phone:this.form.phone,client_email:this.form.email,notes:notes||null,coupon_code:this.couponApplied?this.couponApplied.code:null,delivery_address:addr||null,shipping_cost:this.effectiveShipping>0?this.effectiveShipping:null,items})});
        const data=await res.json();
        if(data.ok){
          @if($isQuoteOnly && $quoteWa)
          this.checkoutOpen=false;
          this.sendQuoteWhatsapp();
          @elseif(!$isQuoteOnly && $hasOnlinePayment)
          this.orderId=data.order_id;this.orderTotal=data.total;this.payReference='';this.payError='';
          if(this.payMethod==='culqi'){ this.checkoutOpen=false; this.openCulqi(); }
          else if(this.payMethod==='mp'){ this.openMercadoPago(); }
          else if(this.payMethod&&this.payReference.trim()){ this.confirmManualPay(); }
          else { this.orderError='Selecciona un método de pago y completa el número de operación.'; this.orderLoading=false; return; }
          @else
          try{localStorage.removeItem(this._cartKey);localStorage.removeItem(this._formKey);}catch(e){}
          window.location.href='/{{ $project->slug }}/thanks/'+data.order_id;
          @endif
        } else{this.orderError=data.message||'No se pudo enviar. Inténtalo de nuevo.';}
      } catch(e){this.orderError='Error de conexión.';}
      this.orderLoading=false;
    },

    async confirmManualPay() {
      if(!this.payReference.trim()) return;
      this.payLoading=true;this.payError='';
      try {
        const res=await fetch(`{{ url('/'.$project->slug.'/pay') }}/${this.orderId}/manual`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({reference:this.payReference})});
        const data=await res.json();
        if(data.ok){try{localStorage.removeItem(this._cartKey);localStorage.removeItem(this._formKey);}catch(e){}window.location.href='/{{ $project->slug }}/thanks/'+this.orderId;}
        else{this.payError='No se pudo confirmar el pago.';}
      } catch(e){this.payError='Error de conexión.';}
      this.payLoading=false;
    },

    @if($culqiEnabled && $culqiPublicKey)
    openCulqi() {
      const self=this;
      Culqi.publicKey='{{ $culqiPublicKey }}';
      Culqi.settings({title:'{{ addslashes($project->name) }}',currency:'PEN',description:'Pedido #'+this.orderId,amount:Math.round(this.orderTotal*100)});
      Culqi.options({style:{logo:'{{ $logoUrl?asset("storage/".$logoUrl):""  }}',maincolor:'{{ $primaryColor }}'}});
      Culqi.open();
      window.culqi=async function(){
        if(Culqi.token){
          self.payLoading=true;self.payError='';
          try{
            const r=await fetch(`{{ url('/'.$project->slug.'/pay') }}/${self.orderId}/culqi`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({token:Culqi.token.id,email:Culqi.token.email,amount:Math.round(self.orderTotal*100)})});
            const d=await r.json();
            if(d.ok){try{localStorage.removeItem(self._cartKey);localStorage.removeItem(self._formKey);}catch(e){}window.location.href='/{{ $project->slug }}/thanks/'+self.orderId;}
            else{self.payError=d.message||'Error al procesar el pago.';}
          }catch(e){self.payError='Error de conexión.';}
          self.payLoading=false;
          Culqi.close();
        } else if(Culqi.error){self.payError=Culqi.error.user_message;}
      };
    },
    @endif

    @if($mpEnabled)
    async openMercadoPago() {
      this.payLoading=true;this.payError='';
      try {
        const res=await fetch(`{{ url('/'.$project->slug.'/pay') }}/${this.orderId}/mp`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({amount:this.orderTotal})});
        const data=await res.json();
        if(data.ok&&data.init_point) window.location.href=data.init_point;
        else this.payError=data.message||'Error al conectar con Mercado Pago.';
      } catch(e){this.payError='Error de conexión.';}
      this.payLoading=false;
    },
    @endif
  };
}
</script>

<x-public-store-runtime :project="$project" :settings="$settings" :popup="$popup ?? null" :sections="$sections ?? collect()" :about-page="$aboutPage ?? null" />
</body>
</html>
