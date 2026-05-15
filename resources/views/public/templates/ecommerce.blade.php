<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@php
$primaryColor  = $settings['primary_color']  ?? '#3340ff';
$currency      = $settings['currency_symbol'] ?? 'S/';
$seoTitle      = ($settings['seo_title'] ?? null) ?: $project->name;
$seoDesc       = ($settings['seo_description'] ?? null) ?: ($project->description ?? '');
$canonicalUrl  = url('/' . $project->slug);
$logoUrl       = $settings['logo_url'] ?? $project->logo_url ?? '';
$ogImage       = $logoUrl ? asset('storage/'.$logoUrl) : asset('img/og-default.png');
$faviconUrl    = !empty($settings['favicon_url']) ? asset('storage/'.$settings['favicon_url']) : '';
$announcementText = $settings['announcement_text'] ?? '';
$footerTagline    = $settings['footer_tagline']  ?? '';
$footerCopyright  = $settings['footer_copyright'] ?? ('© ' . date('Y') . ' ' . $project->name);
$heroTitle     = $settings['hero_title']    ?? $project->name;
$heroSub       = $settings['hero_subtitle'] ?? '';
$isQuoteOnly   = ($settings['store_mode'] ?? 'direct') === 'quote_only';
$culqiEnabled  = ($settings['culqi_enabled'] ?? '0') === '1';
$culqiPublicKey= $settings['culqi_public_key'] ?? '';
$mpEnabled     = ($settings['mp_enabled'] ?? '0') === '1';
$payManualEnabled = ($settings['payment_manual_enabled'] ?? '0') === '1';
$payManualMethods = json_decode($settings['payment_manual_methods'] ?? '["yape"]', true) ?? [];
$payYapeNumber = $settings['payment_yape_number'] ?? '';
$payBankDetails= $settings['payment_bank_details'] ?? '';
$shippingEnabled  = ($settings['shipping_enabled'] ?? '0') === '1';
$shippingCost     = (float)($settings['shipping_cost'] ?? 0);
$shippingFreeFrom = (float)($settings['shipping_free_from'] ?? 0);
$requireAddress   = ($settings['require_address'] ?? '0') === '1';
$quoteWaRaw    = preg_replace('/\D/', '', $settings['quote_whatsapp'] ?? '');
if (!$quoteWaRaw) $quoteWaRaw = preg_replace('/\D/', '', $project->whatsapp ?? '');
$quoteWaCountry = $settings['quote_whatsapp_country'] ?? '51';
$quoteWa = $quoteWaRaw ? (str_starts_with($quoteWaRaw, $quoteWaCountry) ? $quoteWaRaw : $quoteWaCountry.$quoteWaRaw) : '';
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
<link rel="canonical" href="{{ $canonicalUrl }}">
@if($faviconUrl)<link rel="icon" href="{{ $faviconUrl }}">@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap"></noscript>
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
  --font-display: 'Space Grotesk', system-ui, -apple-system, sans-serif;
  --font-body: 'DM Sans', system-ui, -apple-system, sans-serif;
  --topbar-h: 36px;
  --header-h: 64px;
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
*,*::before,*::after{box-sizing:border-box;}
html,body{margin:0;padding:0;}
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
.header{background:rgba(255,255,255,.96);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:40;transition:box-shadow var(--t-fast);}
.header-inner{height:var(--header-h);width:100%;max-width:var(--page-max);margin:0 auto;padding:0 var(--page-pad);display:flex;align-items:center;gap:20px;}
.logo{font-family:var(--font-display);font-weight:700;font-size:22px;letter-spacing:-.01em;display:inline-flex;align-items:center;gap:8px;cursor:pointer;flex-shrink:0;transition:opacity var(--t-fast);}
.logo:hover{opacity:.8;}
.logo-mark{width:32px;height:32px;border-radius:10px;background:var(--primary);display:grid;place-items:center;color:var(--primary-ink);font-family:var(--font-display);font-weight:700;font-size:15px;flex-shrink:0;}
.logo img{height:42px;width:auto;object-fit:contain;}
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
.card-media{position:relative;aspect-ratio:4/5;overflow:hidden;background:var(--bg-inset);}
.card-media img{width:100%;height:100%;object-fit:cover;transition:transform 400ms var(--ease);}
.card:hover .card-media img{transform:scale(1.07);}
.card-media-placeholder{width:100%;height:100%;display:grid;place-items:center;background:linear-gradient(145deg,var(--bg-inset) 0%,var(--bg-elev) 100%);color:var(--text-muted);font-size:44px;}
.card-badges{position:absolute;top:10px;left:10px;display:flex;flex-direction:column;gap:4px;align-items:flex-start;z-index:2;}
.card-wishlist{position:absolute;top:10px;right:10px;width:34px;height:34px;border-radius:999px;background:rgba(255,255,255,.95);backdrop-filter:blur(4px);display:grid;place-items:center;border:0;cursor:pointer;transition:all var(--t-fast);z-index:2;box-shadow:0 2px 8px rgba(0,0,0,.1);}
.card-wishlist:hover{transform:scale(1.12);}
.card-wishlist svg{width:15px;height:15px;color:#0e0e10;}
.card-quick-add{position:absolute;bottom:0;left:0;right:0;padding:10px;background:linear-gradient(to top,rgba(0,0,0,.6) 0%,transparent 100%);opacity:0;transition:opacity var(--t-fast);z-index:2;display:flex;gap:6px;}
.card:hover .card-quick-add{opacity:1;}
.card-quick-add .btn{flex:1;height:38px;font-size:13px;font-weight:600;background:white;color:var(--text-primary);border-radius:var(--radius-sm);}
.card-quick-add .btn:hover{background:var(--primary);color:var(--primary-ink);}
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
.summary{position:sticky;top:calc(var(--header-h) + var(--nav-h) + 16px);background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;}
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
.footer-col-brand .fc-tagline{color:var(--text-secondary);font-size:13px;line-height:1.6;max-width:26ch;margin:0;}
.footer-col h6{font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text-muted);margin:0 0 16px;}
.footer-col ul{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;}
.footer-col li a{color:var(--text-secondary);font-size:13.5px;text-decoration:none;display:flex;align-items:center;gap:7px;min-height:24px;transition:color var(--t-fast);}
.footer-col li a:hover{color:var(--text-primary);}
.fc-social{display:flex;gap:10px;margin-top:4px;}
.fc-social a{display:grid;place-items:center;width:36px;height:36px;border-radius:50%;border:1px solid var(--border-strong);color:var(--text-secondary);transition:background var(--t-fast),color var(--t-fast),border-color var(--t-fast);}
.fc-social a:hover{background:var(--primary);color:var(--primary-ink);border-color:var(--primary);}
.fc-social svg{width:17px;height:17px;}
.fc-pay{display:flex;flex-wrap:wrap;gap:8px;margin-top:4px;}
.pay-badge{display:inline-flex;align-items:center;gap:5px;height:28px;padding:0 10px;border-radius:6px;border:1px solid var(--border-strong);background:var(--bg-inset);font-size:12px;font-weight:600;color:var(--text-primary);}
.pay-badge-yape{background:#6c28d9;color:white;border-color:#6c28d9;}
.pay-badge-plin{background:#0071f3;color:white;border-color:#0071f3;}
.pay-badge-mp{background:#009ee3;color:white;border-color:#009ee3;}
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
.ck-modal{position:fixed;inset:0;z-index:200;background:var(--bg-body);overflow-y:auto;}
.ck-modal-header{background:var(--bg-surface);border-bottom:1px solid var(--border);padding:16px var(--page-pad);display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:10;}
.ck-modal-header h2{font-family:var(--font-display);font-size:20px;font-weight:700;margin:0;flex:1;}
.ck-grid{display:grid;grid-template-columns:1fr 420px;gap:32px;align-items:start;max-width:1100px;margin:32px auto;padding:0 var(--page-pad) 64px;}

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
  .footer-cols{grid-template-columns:1fr 1fr;gap:32px;}
  .footer-newsletter{grid-template-columns:1fr;padding:28px;}
  .nav-flyout{display:none!important;}
  .nav-all-flyout{display:none!important;}
  .cat-page-grid.density-4,.cat-page-grid.density-5{grid-template-columns:repeat(2,1fr);}
  .cat-page-grid.density-3{grid-template-columns:repeat(2,1fr);}
  .testimonials-grid{grid-template-columns:1fr 1fr;}
}
@media(max-width:768px){
  :root{--cols:2;--page-pad:16px;--grid-gap:14px;}
  .hamburger{display:grid;}
  .search{display:none;}
  .nav{display:none;}
  .topbar-links{display:none;}
  .plp-layout{grid-template-columns:1fr;}
  .filters{display:none;}
  .filter-fab{display:inline-flex;}
  .testimonials-grid{grid-template-columns:1fr;}
  .pay-strip{flex-direction:column;align-items:flex-start;gap:10px;}
  .footer-newsletter{grid-template-columns:1fr;padding:24px;}
  .footer-newsletter h3{font-size:22px;}
  .newsletter-form{flex-direction:column;}
  .newsletter-form input,.newsletter-form .btn{width:100%;min-height:48px;}
  .pdp-wrap{grid-template-columns:1fr!important;}
  .pdp-wrap>div:first-child{position:static!important;}
  .hero{height:320px;}
  .hero-slide{grid-template-columns:1fr;}
  .hero-art{display:none;}
  .hero-text{padding:28px;}
  .hero-title{font-size:32px;}
  .cat-grid{grid-template-columns:repeat(3,1fr);}
  .trust-strip{grid-template-columns:1fr 1fr;gap:16px;}
  .footer-cols{grid-template-columns:1fr 1fr;gap:24px;padding:32px 0;}
}
@media(max-width:480px){
  :root{--cols:2;}
  .cat-grid{grid-template-columns:repeat(2,1fr);gap:8px;}
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
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <a @click="page='home';filterCat=null;filterQ=''" class="logo" style="cursor:pointer">
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
        <button id="cart-icon-btn" class="icon-btn" @click="page='cart'" aria-label="Carrito">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
          <span class="icon-badge" x-show="cartCount>0" x-text="cartCount"></span>
        </button>
        {{-- Cart preview hover --}}
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
            <button class="btn btn-primary btn-sm" @click="checkoutOpen=true;cartHover=false">Finalizar compra</button>
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
        <a @click.stop="filterCat='{{ $cat->id }}';filterSubCat=null;page='catalog';navOpen=null" style="font-weight:700;color:var(--primary)">
          Ver todo en {{ $cat->name }}
        </a>
        @foreach($cat->children as $sub)
        <a @click.stop="filterCat='{{ $cat->id }}';filterSubCat='{{ $sub->id }}';page='catalog';navOpen=null">
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
  <section class="hero" @mouseenter="heroPaused=true" @mouseleave="heroPaused=false">
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
  <div class="trust-strip" style="margin-top:0;margin-bottom:0">
    <div class="trust-item"><span class="ti-icon">{{ $trustIcon1 }}</span><div><div class="ti-title">{{ $trustText1 }}</div></div></div>
    <div class="trust-item"><span class="ti-icon">{{ $trustIcon2 }}</span><div><div class="ti-title">{{ $trustText2 }}</div></div></div>
    <div class="trust-item"><span class="ti-icon">{{ $trustIcon3 }}</span><div><div class="ti-title">{{ $trustText3 }}</div></div></div>
    <div class="trust-item"><span class="ti-icon">{{ $trustIcon4 }}</span><div><div class="ti-title">{{ $trustText4 }}</div></div></div>
  </div>

  {{-- CATEGORY GRID --}}
  <section>
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
  <section>
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
            <div class="card-quick-add">
              <button class="btn" @click.stop="addToCart({{ $product->id }})">🛒 Agregar</button>
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
              @if(!$isQuoteOnly && $product->stock != 0)
              <button class="btn btn-primary btn-sm btn-block" @click.stop="addToCart({{ $product->id }})">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                Agregar al carrito
              </button>
              @elseif($product->stock == 0)
              <button class="btn btn-sm btn-block" disabled style="background:var(--bg-inset);color:var(--text-muted);cursor:not-allowed">Agotado</button>
              @else
              <button class="btn btn-outline btn-sm btn-block" @click.stop="openProduct({{ $product->id }})">Ver producto →</button>
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
      @php
        $reviews = \App\Models\Review::where('project_id', $project->id)
          ->where('is_approved', true)->orderByDesc('rating')->take(3)->get();
      @endphp
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
          <div class="card-quick-add">
            <button class="btn" @click.stop="addToCart({{ $product->id }})">🛒 Agregar</button>
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
    <a @click="page='home'" style="cursor:pointer">Inicio</a>
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
              <div class="card-quick-add" x-show="p.stock!==0">
                <button class="btn" @click.stop="addToCart(p.id)">🛒 Agregar</button>
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
    <a @click="page='home'" style="cursor:pointer">Inicio</a>
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
<main x-show="page==='product'" class="page page-pad fade-in">
  <nav class="breadcrumbs">
    <a @click="page='home'" style="cursor:pointer">Inicio</a>
    <span>/</span>
    <a @click="page='catalog'" style="cursor:pointer">Catálogo</a>
    <span>/</span>
    <span style="color:var(--text-primary)" x-text="pdp?.name"></span>
  </nav>
  <template x-if="pdp">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:56px;margin-top:24px" class="pdp-wrap">
      {{-- GALLERY --}}
      <div style="position:sticky;top:calc(var(--header-h) + var(--nav-h) + 24px)">
        <div style="aspect-ratio:1/1;border-radius:var(--radius-xl);overflow:hidden;background:var(--bg-inset)">
          <template x-if="pdp.img"><img :src="pdp.img" :alt="pdp.name" style="width:100%;height:100%;object-fit:cover;transition:transform 400ms var(--ease)" @mouseenter="$el.style.transform='scale(1.03)'" @mouseleave="$el.style.transform='scale(1)'"></template>
          <template x-if="!pdp.img"><div style="width:100%;height:100%;display:grid;place-items:center;font-size:96px;background:linear-gradient(145deg,var(--bg-inset),var(--bg-elev))">📦</div></template>
        </div>
      </div>
      {{-- INFO --}}
      <div style="padding-top:8px">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
          <span class="eyebrow" x-text="pdp.cat" style="cursor:pointer;color:var(--primary)" @click="page='catalog';filterCat=pdp.catId"></span>
          <template x-if="pdp.cp && pdp.cp>pdp.price">
            <span class="badge badge-sale" x-text="'-'+Math.round((1-pdp.price/pdp.cp)*100)+'%'"></span>
          </template>
        </div>
        <h1 style="font-family:var(--font-display);font-size:34px;font-weight:700;letter-spacing:-.02em;line-height:1.1;margin:0 0 20px" x-text="pdp.name"></h1>
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:8px">
          <span style="font-family:var(--font-display);font-size:36px;font-weight:700;color:var(--text-primary)" x-text="fmt(pdp.price)"></span>
          <template x-if="pdp.cp && pdp.cp>pdp.price">
            <span style="font-size:18px;color:var(--text-muted);text-decoration:line-through" x-text="fmt(pdp.cp)"></span>
          </template>
        </div>
        <template x-if="pdp.cp && pdp.cp>pdp.price">
          <p style="font-size:13px;color:var(--accent);font-weight:600;margin:0 0 20px">Ahorras <span x-text="fmt(pdp.cp-pdp.price)"></span></p>
        </template>

        <template x-if="pdp.stock===0">
          <div style="padding:14px 16px;background:color-mix(in srgb,var(--danger) 8%,transparent);border:1px solid color-mix(in srgb,var(--danger) 20%,transparent);border-radius:var(--radius-md);color:var(--danger);font-weight:600;margin-bottom:20px;font-size:14px">⚠️ Producto agotado temporalmente</div>
        </template>

        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:24px" x-show="pdp.stock!==0">
          @if(!$isQuoteOnly)
          <button class="btn btn-primary btn-lg btn-block" @click="addToCart(pdp.id);showToast('✓ '+pdp.name+' agregado')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
            Agregar al carrito
          </button>
          <button class="btn btn-ghost btn-lg btn-block" @click="addToCart(pdp.id);checkoutOpen=true" x-show="!{{ $isQuoteOnly ? 'true' : 'false' }}">
            Comprar ahora →
          </button>
          @endif
          @if($quoteWa)
          <a class="btn btn-outline btn-lg btn-block" :href="'https://wa.me/{{ $quoteWa }}?text='+encodeURIComponent('Hola, me interesa: '+pdp.name+' ('+fmt(pdp.price)+')')" target="_blank" rel="noopener">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="color:#25d366"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2zm5.6 14.2c-.2.6-1.2 1.1-1.7 1.2-.4 0-1 .1-1.6-.1-.4-.1-.9-.3-1.6-.6-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.4-1.1-2.6 0-1.3.6-1.9.9-2.1.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4l.8 1.8c.1.2.1.4 0 .6l-.3.4-.3.4c-.1.1-.2.3-.1.5.1.2.6 1 1.3 1.6.9.8 1.7 1 1.9 1.1.2.1.3.1.5-.1l.7-.8c.2-.2.4-.2.6-.1l1.6.8c.2.1.3.2.4.3 0 .1 0 .8-.2 1.4z"/></svg>
            Consultar por WhatsApp
          </a>
          @endif
        </div>

        <div style="border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden">
          <div style="display:grid;grid-template-columns:1fr 1fr;font-size:13px">
            <div style="padding:12px 16px;display:flex;align-items:center;gap:8px;border-right:1px solid var(--border);border-bottom:1px solid var(--border)"><span style="font-size:18px">{{ $trustIcon1 }}</span><span>{{ $trustText1 }}</span></div>
            <div style="padding:12px 16px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border)"><span style="font-size:18px">{{ $trustIcon2 }}</span><span>{{ $trustText2 }}</span></div>
            <div style="padding:12px 16px;display:flex;align-items:center;gap:8px;border-right:1px solid var(--border)"><span style="font-size:18px">{{ $trustIcon3 }}</span><span>{{ $trustText3 }}</span></div>
            <div style="padding:12px 16px;display:flex;align-items:center;gap:8px"><span style="font-size:18px">{{ $trustIcon4 }}</span><span>{{ $trustText4 }}</span></div>
          </div>
        </div>
      </div>
    </div>
  </template>
</main>

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
                <a style="font-size:12px;color:var(--text-secondary);cursor:pointer" @click="removeFromCart(item.id)">Eliminar</a>
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
        @if(!$isQuoteOnly)
        <button class="btn btn-primary btn-block" style="margin-top:8px" @click="checkoutOpen=true">Finalizar compra</button>
        @else
        <button class="btn btn-primary btn-block" style="margin-top:8px" @click="quoteByWhatsapp()">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2z"/></svg>
          Cotizar por WhatsApp
        </button>
        @endif
      </div>
    </div>
  </template>
</main>

{{-- ═══════════════════════════════════════════════
     CHECKOUT FULLSCREEN MODAL
═══════════════════════════════════════════════ --}}
<div x-show="checkoutOpen" x-cloak class="ck-modal fade-in">
  <div class="ck-modal-header">
    <button class="icon-btn" @click="checkoutOpen=false" aria-label="Cerrar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <h2>Finalizar compra</h2>
    <span style="font-size:13px;color:var(--text-secondary)" x-text="cartCount+' '+( cartCount===1?'producto':'productos')"></span>
  </div>

  <div class="ck-grid">
    {{-- LEFT: FORM --}}
    <div>
      <div x-show="orderSuccess" style="background:color-mix(in srgb,var(--success) 10%,transparent);border:1px solid var(--success);border-radius:var(--radius-lg);padding:24px;margin-bottom:16px;text-align:center">
        <div style="font-size:48px;margin-bottom:8px">✅</div>
        <h3 class="h3" style="color:var(--success);margin-bottom:8px">¡Pedido confirmado!</h3>
        <p style="color:var(--text-secondary)" x-text="orderSuccessMsg"></p>
        <button class="btn btn-primary" style="margin-top:16px" @click="checkoutOpen=false;cart=[];saveCart();page='home'">Volver al inicio</button>
      </div>

      <div x-show="!orderSuccess">
        <div class="co-section">
          <h3>Datos de contacto</h3>
          <div class="field-row">
            <div class="field"><label>Nombre *</label><input x-model="form.fname" placeholder="Tu nombre" type="text"></div>
            <div class="field"><label>Apellido</label><input x-model="form.lname" placeholder="Tu apellido" type="text"></div>
          </div>
          <div class="field-row">
            <div class="field"><label>Celular *</label><input x-model="form.phone" placeholder="999 999 999" type="tel"></div>
            <div class="field"><label>Email</label><input x-model="form.email" placeholder="tu@correo.com" type="email"></div>
          </div>
          <div class="field"><label>DNI / RUC</label><input x-model="form.dni" placeholder="Opcional" type="text"></div>
        </div>

        @if($requireAddress)
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

        <div class="co-section">
          <h3>Notas del pedido</h3>
          <div class="field"><textarea x-model="form.notes" placeholder="Instrucciones especiales, horario de entrega, etc."></textarea></div>
        </div>

        @if($payManualEnabled || $culqiEnabled || $mpEnabled)
        <div class="co-section">
          <h3>Método de pago</h3>
          @foreach($payManualMethods as $method)
          @php $meta = $paymentMeta[$method] ?? ['label'=>$method,'emoji'=>'💳']; @endphp
          <div class="pay-method" :class="{selected:payMethod==='{{ $method }}'}" @click="payMethod='{{ $method }}'">
            <span style="font-size:20px">{{ $meta['emoji'] }}</span>
            <div style="flex:1">
              <div style="font-weight:600;font-size:14px">{{ $meta['label'] }}</div>
              @if($method==='yape' && $payYapeNumber)<div style="font-size:12px;color:var(--text-muted)">N° {{ $payYapeNumber }}</div>@endif
              @if($method==='transferencia' && $payBankDetails)<div style="font-size:12px;color:var(--text-muted)">{{ $payBankDetails }}</div>@endif
            </div>
            <div style="width:20px;height:20px;border-radius:50%;border:2px solid var(--border-strong);display:grid;place-items:center" :class="payMethod==='{{ $method }}' ? 'border-primary' : ''">
              <div x-show="payMethod==='{{ $method }}'" style="width:10px;height:10px;border-radius:50%;background:var(--primary)"></div>
            </div>
          </div>
          @if($method==='yape' && $payYapeNumber)
          <div x-show="payMethod==='yape'" style="margin-bottom:12px;padding:16px;background:var(--bg-inset);border-radius:var(--radius-md);text-align:center">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode('yape:'. $payYapeNumber) }}" alt="QR Yape" style="width:180px;height:180px;margin:0 auto;border-radius:8px">
            <p style="font-size:13px;color:var(--text-secondary);margin-top:8px">Escanea con Yape · N° {{ $payYapeNumber }}</p>
          </div>
          @endif
          @endforeach
          @if($culqiEnabled)
          <div class="pay-method" :class="{selected:payMethod==='culqi'}" @click="payMethod='culqi'">
            <span style="font-size:20px">💳</span>
            <div style="flex:1"><div style="font-weight:600;font-size:14px">Tarjeta de crédito/débito</div><div style="font-size:12px;color:var(--text-muted)">Visa, Mastercard, Amex</div></div>
            <div style="width:20px;height:20px;border-radius:50%;border:2px solid var(--border-strong);display:grid;place-items:center">
              <div x-show="payMethod==='culqi'" style="width:10px;height:10px;border-radius:50%;background:var(--primary)"></div>
            </div>
          </div>
          @endif
          @if($mpEnabled)
          <div class="pay-method" :class="{selected:payMethod==='mp'}" @click="payMethod='mp'">
            <span style="font-size:20px">💙</span>
            <div style="flex:1"><div style="font-weight:600;font-size:14px">Mercado Pago</div></div>
            <div style="width:20px;height:20px;border-radius:50%;border:2px solid var(--border-strong);display:grid;place-items:center">
              <div x-show="payMethod==='mp'" style="width:10px;height:10px;border-radius:50%;background:var(--primary)"></div>
            </div>
          </div>
          @endif
          <div x-show="payMethod && payMethod!=='culqi' && payMethod!=='mp'" style="margin-top:12px">
            <div class="field"><label>Referencia / Nro. de operación</label><input x-model="form.payReference" placeholder="Número de operación (opcional)" type="text"></div>
          </div>
        </div>
        @endif

        <div x-show="orderError" style="padding:12px 16px;background:color-mix(in srgb,var(--danger) 10%,transparent);border:1px solid var(--danger);border-radius:var(--radius-md);color:var(--danger);margin-bottom:16px;font-size:14px" x-text="orderError"></div>

        <button class="btn btn-primary btn-block btn-lg" @click="submitCheckout()" :disabled="orderLoading">
          <span x-show="!orderLoading">✅ Realizar el pedido · <span x-text="fmt(orderTotal)"></span></span>
          <span x-show="orderLoading">Procesando…</span>
        </button>
      </div>
    </div>

    {{-- RIGHT: ORDER SUMMARY --}}
    <div>
      <div class="summary" style="position:sticky;top:80px">
        <h4>Tu pedido</h4>
        <template x-for="item in cart" :key="item.id">
          <div class="co-order-item">
            <div class="co-order-thumb">
              <template x-if="item.img"><img :src="item.img" :alt="item.name"></template>
              <template x-if="!item.img"><div style="width:100%;height:100%;display:grid;place-items:center;font-size:24px;background:var(--bg-inset)">📦</div></template>
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-size:13px;font-weight:500" x-text="item.name"></div>
              <div style="display:flex;align-items:center;gap:8px;margin-top:4px">
                <button style="width:24px;height:24px;border:1px solid var(--border);border-radius:4px;background:transparent;cursor:pointer;font-size:14px" @click="updateQty(item.id,-1)">−</button>
                <span style="font-size:13px;min-width:16px;text-align:center" x-text="item.qty"></span>
                <button style="width:24px;height:24px;border:1px solid var(--border);border-radius:4px;background:transparent;cursor:pointer;font-size:14px" @click="updateQty(item.id,1)">+</button>
              </div>
            </div>
            <span style="font-size:14px;font-weight:600;white-space:nowrap" x-text="fmt(item.price*item.qty)"></span>
          </div>
        </template>
        <div class="summary-row" style="margin-top:8px"><span>Subtotal</span><span x-text="fmt(subtotal)"></span></div>
        @if($shippingEnabled)
        <div class="summary-row">
          <span>Envío</span>
          <span x-text="subtotal>={{ $shippingFreeFrom }} && {{ $shippingFreeFrom }}>0 ? 'Gratis' : fmt({{ $shippingCost }})"></span>
        </div>
        @endif
        <div class="summary-row total"><span>Total</span><span x-text="fmt(orderTotal)"></span></div>
        @if($quoteWa)
        <a class="btn btn-outline btn-sm btn-block" style="margin-top:12px" href="https://wa.me/{{ $quoteWa }}" target="_blank" rel="noopener">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="color:#25d366"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2z"/></svg>
          Consultar por WhatsApp
        </a>
        @endif
      </div>
    </div>
  </div>
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
          @if(!empty($settings['social_facebook']))
          <a href="{{ $settings['social_facebook'] }}" target="_blank" rel="noopener" aria-label="Facebook de {{ $project->name }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
          </a>
          @endif
          @if(!empty($settings['social_instagram']))
          <a href="{{ $settings['social_instagram'] }}" target="_blank" rel="noopener" aria-label="Instagram de {{ $project->name }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
          </a>
          @endif
          @if(!empty($settings['social_tiktok']))
          <a href="{{ $settings['social_tiktok'] }}" target="_blank" rel="noopener" aria-label="TikTok de {{ $project->name }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.32 6.32 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.18 8.18 0 004.77 1.52V6.75a4.85 4.85 0 01-1-.06z"/></svg>
          </a>
          @endif
          @if(!empty($settings['social_youtube']))
          <a href="{{ $settings['social_youtube'] }}" target="_blank" rel="noopener" aria-label="YouTube de {{ $project->name }}">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 00-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 00-1.95 1.96A29 29 0 001 12a29 29 0 00.46 5.58A2.78 2.78 0 003.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 001.95-1.95A29 29 0 0023 12a29 29 0 00-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="white"/></svg>
          </a>
          @endif
          {{-- WhatsApp como red social si no hay otras redes configuradas --}}
          @if(empty($settings['social_facebook']) && empty($settings['social_instagram']) && empty($settings['social_tiktok']) && $quoteWa)
          <a href="https://wa.me/{{ preg_replace('/\D/','',$quoteWa) }}" target="_blank" rel="noopener" aria-label="WhatsApp de {{ $project->name }}" style="background:#25d366;color:white;border-color:#25d366">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          </a>
          @endif
        </div>
      </div>

      {{-- Bloque 5: Métodos de pago --}}
      <div class="footer-col">
        <h6>Métodos de pago</h6>
        <div class="fc-pay">
          @if(in_array('yape', $payManualMethods))
          <span class="pay-badge pay-badge-yape" title="Yape">🟣 Yape</span>
          @endif
          @if(in_array('plin', $payManualMethods))
          <span class="pay-badge pay-badge-plin" title="Plin">🔵 Plin</span>
          @endif
          @if($culqiEnabled || in_array('tarjeta', $payManualMethods))
          <span class="pay-badge" title="Visa">
            <svg width="24" height="8" viewBox="0 0 750 471" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Visa"><rect width="750" height="471" rx="40" fill="#1A1F71"/><path d="M286 334L316 137H364L334 334H286Z" fill="white"/><path d="M498 141c-19-7-48-15-84-15-93 0-158 47-158 115 0 50 47 78 83 95 37 17 49 28 49 43 0 23-30 34-57 34-38 0-59-5-91-18l-12-6-13 78c22 10 63 19 106 19 98 0 162-46 163-118 0-39-25-69-80-94-33-16-54-26-54-42 0-14 17-29 54-29 31-1 54 6 71 13l9 4 14-79z" fill="white"/><path d="M614 137h-56c-17 0-30 5-37 22l-106 176h75l15-40h91l9 40h66l-57-198zm-88 110l28-73 16 73h-44z" fill="white"/><path d="M229 137l-92 134-10-49c-17-52-70-108-130-136l84 248h76l113-197h-41z" fill="white"/><path d="M121 137H4l-1 6c91 22 151 75 176 139l-25-121c-4-17-17-23-33-24z" fill="#F9A51A"/></svg>
            Visa
          </span>
          <span class="pay-badge" title="Mastercard">
            <svg width="22" height="14" viewBox="0 0 152 95" xmlns="http://www.w3.org/2000/svg" aria-label="Mastercard"><circle cx="52" cy="47.5" r="47.5" fill="#EB001B"/><circle cx="100" cy="47.5" r="47.5" fill="#F79E1B"/><path d="M76 20.7A47.5 47.5 0 0 1 100 47.5 47.5 47.5 0 0 1 76 74.3 47.5 47.5 0 0 1 52 47.5 47.5 47.5 0 0 1 76 20.7z" fill="#FF5F00"/></svg>
            MC
          </span>
          @endif
          @if($mpEnabled)
          <span class="pay-badge pay-badge-mp" title="Mercado Pago">💙 MP</span>
          @endif
          @if(in_array('transferencia', $payManualMethods))
          <span class="pay-badge" title="Transferencia bancaria">🏦 Banco</span>
          @endif
          @if(in_array('efectivo', $payManualMethods))
          <span class="pay-badge" title="Pago en efectivo">💵 Efectivo</span>
          @endif
          @if(in_array('contra_entrega', $payManualMethods))
          <span class="pay-badge" title="Pago contra entrega">🚚 C/E</span>
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
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2zm5.6 14.2c-.2.6-1.2 1.1-1.7 1.2-.4 0-1 .1-1.6-.1-.4-.1-.9-.3-1.6-.6-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.4-1.1-2.6 0-1.3.6-1.9.9-2.1.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4l.8 1.8c.1.2.1.4 0 .6l-.3.4-.3.4c-.1.1-.2.3-.1.5.1.2.6 1 1.3 1.6.9.8 1.7 1 1.9 1.1.2.1.3.1.5-.1l.7-.8c.2-.2.4-.2.6-.1l1.6.8c.2.1.3.2.4.3 0 .1 0 .8-.2 1.4z"/></svg>
      Consultar por WhatsApp
    </a>
  </div>
  @endif
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

<script>
const EC_PRODUCTS = @json($searchIndex);
const EC_CURRENCY = @json($currency);
const EC_SLUG     = @json($project->slug);
const EC_ORDER_ROUTE = @json(route('public.order', $project->slug));
const EC_CSRF    = @json(csrf_token());
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
    // Checkout
    checkoutOpen: false,
    payMethod: '',
    form: { fname:'',lname:'',phone:'',email:'',dni:'',department:'',district:'',address:'',address2:'',notes:'',payReference:'' },
    orderLoading: false,
    orderError: '',
    orderSuccess: false,
    orderSuccessMsg: '',
    // PDP
    pdp: null,
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
      // calcular precio máximo de todos los productos
      this.maxPrice = EC_PRODUCTS.reduce((m, p) => Math.max(m, p.price), 0);
      this.maxPrice = Math.ceil(this.maxPrice / 10) * 10 || 1000;
      this.priceMax = this.maxPrice;

      // Listener para que la página de categorías navegue al catálogo filtrado
      window.addEventListener('ec-filter-cat', (e) => {
        this.filterCat = e.detail.catId;
        this.filterSubCat = null;
        this.page = 'catalog';
        window.scrollTo({top:0,behavior:'smooth'});
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

    onScroll() { this.showBackTop = window.scrollY > 320; },

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

    removeFromCart(id) { this.cart = this.cart.filter(i => i.id != id); this.saveCart(); },
    updateQty(id, delta) {
      const item = this.cart.find(i => i.id == id);
      if (!item) return;
      item.qty = Math.max(1, item.qty + delta);
      if (item.qty === 0) this.removeFromCart(id);
      this.saveCart();
    },

    saveCart() { try { localStorage.setItem('ec_cart_'+EC_SLUG, JSON.stringify(this.cart)); } catch(e){} },
    loadCart() { try { const d = localStorage.getItem('ec_cart_'+EC_SLUG); if(d) this.cart = JSON.parse(d); } catch(e){} },
    loadSavedForm() { try { const d = localStorage.getItem('ec_form_'+EC_SLUG); if(d) this.form = {...this.form, ...JSON.parse(d)}; } catch(e){} },
    saveForm() { try { localStorage.setItem('ec_form_'+EC_SLUG, JSON.stringify(this.form)); } catch(e){} },

    openProduct(id) {
      const p = EC_PRODUCTS.find(x => x.id == id);
      if (p) { this.pdp = p; this.page = 'product'; window.scrollTo({top:0,behavior:'smooth'}); }
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

    quoteByWhatsapp() {
      const lines = this.cart.map(i => `• ${i.name} x${i.qty} = ${this.fmt(i.price*i.qty)}`).join('\n');
      const msg = `{{ $settings['quote_wa_msg'] ?? 'Hola, quiero cotizar:' }}\n${lines}\n*Total: ${this.fmt(this.subtotal)}*`;
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
      if (!fullName) { this.orderError = 'Ingresa tu nombre.'; return; }
      if (!this.form.phone.trim()) { this.orderError = 'Ingresa tu número de celular.'; return; }
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
            name: fullName, phone: this.form.phone, email: this.form.email,
            address: addr, notes, payment_method: this.payMethod,
            pay_reference: this.form.payReference,
            items, total: this.orderTotal,
          }),
        });
        const data = await res.json();
        if (!res.ok) { this.orderError = data.message || 'Error al procesar el pedido.'; }
        else {
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
</body>
</html>
