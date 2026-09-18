@php
    $theme = $theme ?? \App\Modules\Tienda\Support\StorefrontTheme::resolve($settings ?? []);
@endphp
<style id="storefront-active-theme">
/* Shared storefront visual themes. Content and navigation remain project-global. */
body.storefront-theme{
    --theme-page:#ffffff;
    --theme-surface:#ffffff;
    --theme-soft:#f6f8fb;
    --theme-ink:#111827;
    --theme-muted:#64748b;
    --theme-border:#e2e8f0;
    --theme-radius:14px;
    --theme-card-shadow:0 12px 34px rgba(15,23,42,.07);
    --theme-section-space:clamp(46px,6vw,78px);
    --theme-container:1180px;
    background:var(--theme-page);
    color:var(--theme-ink);
}
body.storefront-theme .store-container,
body.storefront-theme .sf-home-container,
body.storefront-theme .sf-hero-content{width:min(var(--theme-container),calc(100% - 40px))}
body.storefront-theme .sf-home-section{padding-block:var(--theme-section-space);background:var(--theme-surface);color:var(--theme-ink)}
body.storefront-theme .sf-benefits,
body.storefront-theme .sf-products,
body.storefront-theme .institutional-sections,
body.storefront-theme .shop-hero{background:var(--theme-soft)}
body.storefront-theme .sf-card,
body.storefront-theme .shop-card,
body.storefront-theme .institutional-block,
body.storefront-theme .store-page-card,
body.storefront-theme .shop-filters{border-color:var(--theme-border);border-radius:var(--theme-radius);background:var(--theme-surface);box-shadow:var(--theme-card-shadow)}
body.storefront-theme .sf-home-heading h2,
body.storefront-theme .shop-hero h1,
body.storefront-theme .shop-card h2,
body.storefront-theme .institutional-block h2,
body.storefront-theme .store-page-card h1,
body.storefront-theme .store-page-card h2{color:var(--theme-ink)}
body.storefront-theme .sf-home-heading p,
body.storefront-theme .sf-benefit p,
body.storefront-theme .shop-hero p,
body.storefront-theme .shop-toolbar p,
body.storefront-theme .institutional-block p,
body.storefront-theme .store-page-card p{color:var(--theme-muted)}
body.storefront-theme .sf-hero{padding:0;background:var(--store-secondary);color:#fff}
body.storefront-theme .sf-hero-slide{padding-inline:max(20px,calc((100vw - var(--theme-container))/2))}
body.storefront-theme .sf-button,
body.storefront-theme .store-button,
body.storefront-theme .store-header-action,
body.storefront-theme .store-nav-link{border-radius:calc(var(--theme-radius) * .62)}
body.storefront-theme .sf-product-card,
body.storefront-theme .shop-card{transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}
body.storefront-theme .sf-product-card:hover,
body.storefront-theme .shop-card:hover{transform:translateY(-3px);box-shadow:0 18px 42px rgba(15,23,42,.12)}

/* Ecommerce — Tienda Completa: faithful V2 adaptation of the existing design. */
body.storefront-theme-ecommerce{--theme-page:#f7f7f5;--theme-surface:#fff;--theme-soft:#f3f4f6;--theme-ink:#0e0e10;--theme-muted:#626975;--theme-border:#e4e6ea;--theme-radius:16px;--theme-card-shadow:0 12px 34px rgba(17,24,39,.065);--theme-container:1240px}
body.storefront-theme-ecommerce .store-header{box-shadow:0 8px 26px rgba(15,23,42,.055)}
body.storefront-theme-ecommerce .store-nav-link.is-active{box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--store-primary) 18%,transparent)}
body.storefront-theme-ecommerce .sf-hero{width:min(var(--theme-container),calc(100% - 40px));min-height:480px;margin:22px auto 38px;border:1px solid var(--theme-border);border-radius:22px;background:var(--theme-surface);color:var(--theme-ink);box-shadow:0 18px 54px rgba(15,23,42,.08)}
body.storefront-theme-ecommerce .sf-hero-slide{padding:0;background-color:var(--theme-surface);background-position:right center;background-size:50% 100%;background-repeat:no-repeat}
body.storefront-theme-ecommerce .sf-hero-slide::after{position:absolute;z-index:0;inset:0 0 0 50%;content:"";background:linear-gradient(135deg,color-mix(in srgb,var(--store-primary) 88%,#111827),var(--store-primary));opacity:.78}
body.storefront-theme-ecommerce .sf-hero-content{z-index:1;width:100%;margin:0;padding:0}
body.storefront-theme-ecommerce .sf-hero-copy{width:50%;max-width:none;padding:58px 54px;border:0;border-radius:0;background:var(--theme-surface);box-shadow:none;backdrop-filter:none}
body.storefront-theme-ecommerce .sf-hero h1{color:var(--theme-ink);font-size:clamp(42px,5vw,62px);line-height:.98}
body.storefront-theme-ecommerce .sf-hero p{max-width:34ch;color:var(--theme-muted);font-size:17px;line-height:1.6}
body.storefront-theme-ecommerce .sf-button-secondary{border-color:var(--theme-border);background:#fff;color:var(--theme-ink)}
body.storefront-theme-ecommerce .sf-hero-nav{right:22px;bottom:20px}
body.storefront-theme-ecommerce .sf-hero-nav button{border-color:rgba(255,255,255,.55);background:rgba(255,255,255,.94)}
body.storefront-theme-ecommerce .sf-benefits{padding-block:26px;border-block:1px solid var(--theme-border);background:var(--theme-surface)}
.sf-loc-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px}
.sf-loc{display:flex;flex-direction:column;overflow:hidden}
.sf-loc-map iframe{width:100%;height:190px;border:0;display:block;background:#e2e8f0}
.sf-loc-body{padding:16px 18px}
.sf-loc-body h3{margin:0;font-size:16px}
.sf-loc-body p{margin:6px 0 0;font-size:13.5px;line-height:1.55}
.sf-loc-dato{opacity:.75}
.sf-loc-link{display:inline-block;margin-top:10px;font-size:12.5px;font-weight:700;text-decoration:none}
.sf-loc-grid.is-side{grid-template-columns:1fr}
.sf-loc-grid.is-side .sf-loc{flex-direction:row}
.sf-loc-grid.is-side .sf-loc-map{flex:0 0 46%}
.sf-loc-grid.is-side .sf-loc-map iframe{height:100%;min-height:220px}
@media(max-width:760px){.sf-loc-grid.is-side .sf-loc{flex-direction:column}.sf-loc-grid.is-side .sf-loc-map iframe{height:180px}}
body.storefront-theme-ecommerce .sf-benefit-grid{gap:0}
body.storefront-theme-ecommerce .sf-benefit{padding:14px 24px;border:0;border-right:1px solid var(--theme-border);border-radius:0;box-shadow:none}
body.storefront-theme-ecommerce .sf-benefit:last-child{border-right:0}
body.storefront-theme-ecommerce .sf-category-grid{grid-template-columns:repeat(5,minmax(0,1fr));gap:14px}
body.storefront-theme-ecommerce .sf-category{box-shadow:none;transition:border-color .2s ease,transform .2s ease}
body.storefront-theme-ecommerce .sf-category:hover{transform:translateY(-2px);border-color:var(--store-primary)}
body.storefront-theme-ecommerce .sf-category-media{aspect-ratio:1;background:#f1f3f5}
body.storefront-theme-ecommerce .sf-product-image,
body.storefront-theme-ecommerce .shop-card-media{background:#f6f7f8}
body.storefront-theme-ecommerce .sf-product-card,
body.storefront-theme-ecommerce .shop-card{box-shadow:none}
body.storefront-theme-ecommerce .sf-product-card:hover,
body.storefront-theme-ecommerce .shop-card:hover{border-color:var(--store-primary);box-shadow:0 16px 38px rgba(15,23,42,.10)}
body.storefront-theme-ecommerce .sf-sale,
body.storefront-theme-ecommerce .shop-sale{border-radius:6px;background:var(--store-primary)}
body.storefront-theme-ecommerce .shop-hero{padding:38px 0 28px;background:var(--theme-page)}
body.storefront-theme-ecommerce .shop-layout{grid-template-columns:248px minmax(0,1fr);gap:28px}
body.storefront-theme-ecommerce .shop-filters{position:sticky;top:calc(var(--store-header-height,72px) + 18px);box-shadow:none}

/* Classic/default: balanced neutral storefront. */
body.storefront-theme-default{--theme-radius:12px;--theme-card-shadow:0 8px 24px rgba(15,23,42,.055)}

/* Direct: compact visual language while preserving every enabled section. */
body.storefront-family-direct{--theme-radius:8px;--theme-section-space:clamp(34px,4vw,54px);--theme-card-shadow:none;--theme-container:1120px}
body.storefront-family-direct .sf-hero{min-height:390px;border-bottom:1px solid var(--theme-border);background:#f8fafc;color:var(--theme-ink)}
body.storefront-family-direct .sf-hero-slide{align-items:center;padding-block:42px;background-image:linear-gradient(90deg,rgba(248,250,252,.99) 0%,rgba(248,250,252,.96) 43%,rgba(248,250,252,.28) 72%,rgba(248,250,252,.08) 100%),var(--sf-image,none);background-position:center,right center;background-size:cover,55% auto;background-repeat:no-repeat}
body.storefront-family-direct .sf-hero-copy{max-width:610px;padding:36px 38px;border-left:4px solid var(--store-primary);background:#fff;box-shadow:0 16px 46px rgba(15,23,42,.08)}
body.storefront-family-direct .sf-hero h1{color:var(--theme-ink);font-size:clamp(34px,5vw,54px);letter-spacing:-.035em}
body.storefront-family-direct .sf-hero p{color:var(--theme-muted);font-size:17px}
body.storefront-family-direct .sf-button-secondary{border-color:var(--theme-border);background:#fff;color:var(--theme-ink)}
body.storefront-family-direct .sf-hero-nav button{border-radius:6px;box-shadow:none}
body.storefront-family-direct .sf-card,
body.storefront-family-direct .shop-card{box-shadow:none}
body.storefront-family-direct .sf-benefit-grid{gap:0;border-block:1px solid var(--theme-border)}
body.storefront-family-direct .sf-benefit{border-width:0 1px 0 0;border-radius:0;background:transparent}
body.storefront-family-direct .sf-benefit:last-child{border-right:0}
body.storefront-family-direct .sf-category-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
body.storefront-family-direct .sf-category{display:grid;grid-template-columns:72px 1fr;align-items:center;text-align:left}
body.storefront-family-direct .sf-category-media{aspect-ratio:1;border-right:1px solid var(--theme-border)}
body.storefront-family-direct .sf-category strong{padding:14px}
body.storefront-family-direct .shop-hero{padding:30px 0 24px;background:#fff}
body.storefront-family-direct .shop-hero h1{font-size:clamp(30px,4vw,42px)}
body.storefront-family-direct .shop-layout{grid-template-columns:1fr;gap:20px;padding-top:26px}
body.storefront-family-direct .shop-filters{display:grid;grid-template-columns:2fr 1.35fr 1.35fr auto auto;align-items:end;gap:12px;padding:16px;border-radius:8px}
body.storefront-family-direct .shop-filters h2{grid-column:1/-1;margin:0 0 2px}
body.storefront-family-direct .shop-field{margin:0}
body.storefront-family-direct .shop-check{align-self:end;margin-bottom:1px;white-space:nowrap}
body.storefront-family-direct .shop-filter-actions{grid-template-columns:auto;align-self:end}
body.storefront-family-direct .shop-filter-reset{display:none}
body.storefront-family-direct .shop-card-media{aspect-ratio:1/.82;background:#f8fafc}

/* Editorial/fashion: sharp silhouettes, restrained borders, generous rhythm. */
body.storefront-family-editorial{--theme-page:#fbfaf8;--theme-surface:#fff;--theme-soft:#f4f0eb;--theme-ink:#171412;--theme-muted:#716a63;--theme-border:#ded8d1;--theme-radius:1px;--theme-card-shadow:none;--theme-section-space:clamp(62px,8vw,106px);--theme-container:1280px}
body.storefront-family-editorial .sf-home-heading h2,
body.storefront-family-editorial .shop-hero h1,
body.storefront-family-editorial .institutional-hero h1{font-weight:600;letter-spacing:-.025em}
body.storefront-family-editorial .sf-category strong,
body.storefront-family-editorial .sf-product-category,
body.storefront-family-editorial .shop-card-category{letter-spacing:.12em;text-transform:uppercase}
body.storefront-family-editorial .sf-product-card:hover,
body.storefront-family-editorial .shop-card:hover{transform:none;border-color:var(--theme-ink)}
body.storefront-family-editorial .sf-product-image,
body.storefront-family-editorial .shop-card-media{background:#f4f0eb}
body.storefront-theme-boutique .sf-hero-copy,
body.storefront-theme-ella .sf-hero-copy{max-width:820px;margin-inline:auto;text-align:center}

/* Dark/luxury: dark surfaces with a brand-colored accent. */
body.storefront-family-dark{--theme-page:#0b0d12;--theme-surface:#12151c;--theme-soft:#0f1218;--theme-ink:#f8fafc;--theme-muted:#a8b0be;--theme-border:#2b303b;--theme-radius:10px;--theme-card-shadow:0 18px 46px rgba(0,0,0,.25);color-scheme:dark}
body.storefront-family-dark .sf-product-image,
body.storefront-family-dark .shop-card-media,
body.storefront-family-dark .sf-category-media{background:#171b24}
body.storefront-family-dark .shop-field input,
body.storefront-family-dark .shop-field select,
body.storefront-family-dark .shop-toolbar select{border-color:var(--theme-border);background:#0e1117;color:var(--theme-ink)}
body.storefront-family-dark .shop-card-price del{color:#9299a7}
body.storefront-family-dark .store-page-shell{background:var(--theme-soft)}

/* Technology: precise grids, tighter corners and crisp depth. */
body.storefront-family-technology{--theme-page:#f3f6fa;--theme-surface:#fff;--theme-soft:#edf2f8;--theme-ink:#0b1324;--theme-muted:#5b6b82;--theme-border:#d5deea;--theme-radius:7px;--theme-card-shadow:0 12px 30px rgba(15,35,65,.075);--theme-container:1220px}
body.storefront-family-technology .sf-hero-slide{background-image:linear-gradient(90deg,rgba(2,8,23,.94),rgba(2,8,23,.42)),var(--sf-image,none)}
body.storefront-family-technology .sf-product-image,
body.storefront-family-technology .shop-card-media{background:linear-gradient(145deg,#f8fafc,#edf2f7)}
body.storefront-family-technology .sf-benefit-icon{border:1px solid color-mix(in srgb,var(--store-primary) 22%,transparent)}
body.storefront-family-technology .sf-card,
body.storefront-family-technology .shop-card{border-width:1px}
body.storefront-theme-computienda{--theme-page:#f4f7fb;--theme-surface:#fff;--theme-soft:#edf3fa;--theme-ink:#0e1a30;--theme-muted:#5b6b82;--theme-border:#d7e1ee;--theme-radius:8px;--theme-card-shadow:0 12px 30px rgba(14,26,48,.075);--theme-container:1220px}
body.storefront-theme-computienda .sf-hero{min-height:510px;background:#0e1a30;color:#fff}
body.storefront-theme-computienda .sf-hero-slide{background-image:linear-gradient(90deg,rgba(14,26,48,.98) 0%,rgba(14,26,48,.91) 52%,rgba(30,80,160,.36) 100%),var(--sf-image,none);background-position:center,right center;background-size:cover,50% 100%;background-repeat:no-repeat}
body.storefront-theme-computienda .sf-hero-slide::after{position:absolute;right:max(7%,calc((100vw - var(--theme-container))/2));width:min(34vw,460px);height:330px;content:"";border:1px solid rgba(147,197,253,.24);border-radius:10px;background:radial-gradient(circle at 72% 28%,rgba(96,165,250,.42),transparent 31%),linear-gradient(135deg,rgba(37,99,235,.96),rgba(30,80,160,.36) 62%,rgba(14,26,48,.22)),repeating-linear-gradient(90deg,rgba(255,255,255,.06) 0 1px,transparent 1px 48px);box-shadow:0 24px 58px rgba(2,8,23,.32)}
body.storefront-theme-computienda .sf-hero-copy{max-width:670px;padding:36px 0;border:0;background:transparent;box-shadow:none}
body.storefront-theme-computienda .sf-hero-content{z-index:1}
body.storefront-theme-computienda .sf-hero h1{color:#fff;font-size:clamp(40px,5.2vw,64px);line-height:1.04}
body.storefront-theme-computienda .sf-hero p{max-width:620px;color:#dbe6f5;font-size:17px}
body.storefront-theme-computienda .sf-button{background:#2563eb}
body.storefront-theme-computienda .sf-button-secondary{border-color:#d7e1ee;background:#fff;color:#0e1a30}
body.storefront-theme-computienda .sf-benefits{padding-block:0;border-bottom:1px solid var(--theme-border);background:#fff}
body.storefront-theme-computienda .sf-benefit-grid{gap:0}
body.storefront-theme-computienda .sf-benefit{min-height:122px;padding:24px;border:0;border-right:1px solid var(--theme-border);border-radius:0;box-shadow:none}
body.storefront-theme-computienda .sf-benefit:last-child{border-right:0}
body.storefront-theme-computienda .sf-product-image,
body.storefront-theme-computienda .shop-card-media{background:linear-gradient(145deg,#fff,#edf3f8)}
body.storefront-theme-computienda .sf-product-card,
body.storefront-theme-computienda .shop-card{border-color:#d7e1ee;box-shadow:none}
body.storefront-theme-computienda .sf-product-card:hover,
body.storefront-theme-computienda .shop-card:hover{border-color:#2563eb;box-shadow:0 14px 34px rgba(30,80,160,.12)}

/* Organic/wellness/food: welcoming surfaces and softer geometry. */
body.storefront-family-organic{--theme-page:#fbfcf8;--theme-surface:#fff;--theme-soft:#f3f7ef;--theme-ink:#1d2b22;--theme-muted:#657268;--theme-border:#dfe8dc;--theme-radius:20px;--theme-card-shadow:0 14px 34px rgba(35,66,42,.075)}
body.storefront-family-organic .sf-hero{border-bottom-left-radius:32px;border-bottom-right-radius:32px}
body.storefront-family-organic .sf-category-media,
body.storefront-family-organic .sf-product-image,
body.storefront-family-organic .shop-card-media{background:#f3f7ef}
body.storefront-family-organic .sf-benefit-icon{border-radius:50%}

/* Professional/services: restrained density and strong information hierarchy. */
body.storefront-family-professional{--theme-page:#fff;--theme-surface:#fff;--theme-soft:#f5f7fa;--theme-ink:#152033;--theme-muted:#5d6b80;--theme-border:#d9e0ea;--theme-radius:9px;--theme-card-shadow:0 9px 26px rgba(15,31,54,.055);--theme-container:1160px}
body.storefront-family-professional .sf-hero{min-height:520px}
body.storefront-family-professional .sf-card,
body.storefront-family-professional .institutional-block{border-left:3px solid var(--store-primary)}

/* Retail variants remain distinct inside the commerce family. */
body.storefront-theme-porto{--theme-radius:5px;--theme-section-space:clamp(40px,5vw,64px);--theme-container:1260px}
body.storefront-theme-porto .sf-card,
body.storefront-theme-porto .shop-card{box-shadow:none}
body.storefront-theme-porto .sf-product-card:hover,
body.storefront-theme-porto .shop-card:hover{border-color:var(--store-primary)}
body.storefront-theme-flash,
body.storefront-theme-market{--theme-soft:#fff8e8;--theme-radius:12px;--theme-card-shadow:0 10px 28px rgba(120,53,15,.09)}
body.storefront-theme-flash .sf-sale,
body.storefront-theme-market .sf-sale{padding:7px 10px;border-radius:6px;font-size:12px}
body.storefront-theme-ferreteria{--theme-radius:4px;--theme-soft:#f2f4f6;--theme-card-shadow:0 8px 20px rgba(15,23,42,.07)}

@media(max-width:767px){
    body.storefront-theme .store-container,
    body.storefront-theme .sf-home-container,
    body.storefront-theme .sf-hero-content{width:min(var(--theme-container),calc(100% - 28px))}
    body.storefront-theme .sf-home-section{padding-block:clamp(36px,10vw,50px)}
    body.storefront-theme-ecommerce .sf-hero{width:calc(100% - 28px);min-height:360px;margin:14px auto 28px;border-radius:14px}
    body.storefront-theme-ecommerce .sf-hero-slide{padding:0;background-image:none!important}
    body.storefront-theme-ecommerce .sf-hero-slide::after{display:none}
    body.storefront-theme-ecommerce .sf-hero-copy{width:100%;padding:34px 24px;background:var(--theme-surface)}
    body.storefront-theme-ecommerce .sf-hero h1{font-size:clamp(34px,11vw,46px)}
    body.storefront-theme-ecommerce .sf-benefits{padding-block:22px}
    body.storefront-theme-ecommerce .sf-benefit-grid{gap:10px}
    body.storefront-theme-ecommerce .sf-benefit{border:1px solid var(--theme-border);border-radius:12px}
    body.storefront-theme-ecommerce .sf-category-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    body.storefront-family-direct .sf-hero{min-height:440px}
    body.storefront-family-direct .sf-hero-slide{padding:32px 14px;background-image:linear-gradient(180deg,rgba(248,250,252,.80),rgba(248,250,252,.98)),var(--sf-mobile-image,var(--sf-image,none));background-size:cover}
    body.storefront-family-direct .sf-hero-copy{padding:28px 22px}
    body.storefront-family-direct .sf-benefit-grid{border:0}
    body.storefront-family-direct .sf-benefit{border:1px solid var(--theme-border);border-radius:8px}
    body.storefront-family-direct .sf-category-grid{grid-template-columns:1fr}
    body.storefront-family-direct .shop-filters{display:none}
    body.storefront-family-direct .shop-filters.is-open{display:grid;grid-template-columns:1fr}
    body.storefront-family-direct .shop-filters h2{grid-column:auto}
    body.storefront-family-direct .shop-filter-reset{display:block}
    body.storefront-family-editorial{--theme-section-space:48px}
    body.storefront-family-organic .sf-hero{border-radius:0 0 20px 20px}
    body.storefront-theme-computienda .sf-hero{min-height:460px}
    body.storefront-theme-computienda .sf-hero-slide{background-image:linear-gradient(180deg,rgba(14,26,48,.80),rgba(14,26,48,.98)),var(--sf-mobile-image,var(--sf-image,none));background-size:cover}
    body.storefront-theme-computienda .sf-hero-slide::after{display:none}
    body.storefront-theme-computienda .sf-hero-copy{padding:32px 0}
    body.storefront-theme-computienda .sf-benefit-grid{gap:10px;padding-block:18px}
    body.storefront-theme-computienda .sf-benefit{min-height:0;border:1px solid var(--theme-border);border-radius:8px}
}
@media(prefers-reduced-motion:reduce){
    body.storefront-theme .sf-product-card,
    body.storefront-theme .shop-card{transition:none}
}
</style>
