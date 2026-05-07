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
  $primaryColor     = $settings['primary_color'] ?? '#e5ff00';
  $isQuoteOnly      = ($settings['store_mode'] ?? 'direct') === 'quote_only';
  $shippingEnabled  = ($settings['shipping_enabled']  ?? '0') === '1';
  $shippingCost     = (float)($settings['shipping_cost']      ?? 0);
  $shippingFreeFrom = (float)($settings['shipping_free_from'] ?? 0);
  $requireAddress   = ($settings['require_address']   ?? '0') === '1';
  $quotePriceDisp   = $settings['quote_price_display'] ?? 'show';
  $quoteWaRaw = preg_replace('/\D/', '', $settings['quote_whatsapp'] ?? '');
  if (!$quoteWaRaw) $quoteWaRaw = preg_replace('/\D/', '', $project->whatsapp ?? '');
  $quoteWaCountry = $settings['quote_whatsapp_country'] ?? '51';
  $quoteWa = '';
  if ($quoteWaRaw) {
      $quoteWa = str_starts_with($quoteWaRaw, $quoteWaCountry) ? $quoteWaRaw : $quoteWaCountry.$quoteWaRaw;
  }
  $quoteWaMsg   = $settings['quote_wa_msg'] ?? 'Hola, me interesa cotizar los siguientes productos:';
  $canonicalUrl = url('/' . $project->slug);
  $seoTitle     = ($settings['seo_title'] ?? null) ?: ($project->name . ' â€” CatÃ¡logo Online');
  $seoDesc      = ($settings['seo_description'] ?? null) ?: ($project->description ?? 'Explora nuestros productos.');
  $ogImage      = $project->logo_url ? asset('storage/'.$project->logo_url) : asset('img/og-default.png');
  $heroBg       = $settings['hero_bg_color'] ?? '#000000';
  $heroTitle    = $settings['hero_title'] ?? 'NUEVO DROP DISPONIBLE';
  $heroSub      = $settings['hero_subtitle'] ?? 'Streetwear de alto impacto. EdiciÃ³n limitada.';
  $heroBadge    = $settings['hero_badge'] ?? 'NUEVA COLECCIÃ“N';
  $b1Title      = $settings['banner1_title'] ?? 'NUEVOS DROPS';
  $b1Sub        = $settings['banner1_sub'] ?? 'Piezas exclusivas';
  $b2Title      = $settings['banner2_title'] ?? 'HASTA 50% OFF';
  $b2Sub        = $settings['banner2_sub'] ?? 'Ofertas limitadas';
  $acceptedPayments = json_decode($settings['accepted_payments'] ?? '[]', true) ?? [];
  $paymentMeta = [
      'efectivo'      => ['label'=>'Efectivo',              'emoji'=>'ðŸ’µ'],
      'yape'          => ['label'=>'Yape',                  'emoji'=>'ðŸŸ£'],
      'plin'          => ['label'=>'Plin',                  'emoji'=>'ðŸ”µ'],
      'transferencia' => ['label'=>'Transferencia',         'emoji'=>'ðŸ¦'],
      'tarjeta'       => ['label'=>'Tarjeta crÃ©dito/dÃ©bito','emoji'=>'ðŸ’³'],
      'qr'            => ['label'=>'Pago QR',               'emoji'=>'ðŸ“²'],
      'contra_entrega'=> ['label'=>'Contra entrega',        'emoji'=>'ðŸšš'],
  ];
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
<meta property="og:locale" content="es_PE">
<meta property="og:site_name" content="{{ $project->name }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDesc }}">
<meta name="twitter:image" content="{{ $ogImage }}">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,900&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

@php
  $secondaryColor = $settings['secondary_color'] ?? '#ff6b63';
  $fontTitle  = $settings['font_title'] ?? $settings['font'] ?? 'Poppins';
  $fontBody   = $settings['font_body']  ?? $settings['font'] ?? 'Poppins';
  $borderRadius = ['sharp'=>'0px','rounded'=>'8px','pill'=>'50px'][$settings['border_radius'] ?? 'rounded'] ?? '8px';
  $faviconRaw   = $settings['favicon_url'] ?? '';
  $faviconUrl   = $faviconRaw ? (str_starts_with($faviconRaw,'http') ? $faviconRaw : asset('storage/'.$faviconRaw)) : '';
  $catalogBadgeSale = $settings['catalog_badge_sale'] ?? 'SALE';
  $catalogBadgeNew  = $settings['catalog_badge_new']  ?? 'NEW DROP';
  $btnCartText  = $settings['btn_cart_text']  ?? 'Agregar al carrito';
  $btnQuoteText = $settings['btn_quote_text'] ?? 'Cotizar';
  $footerTagline   = $settings['footer_tagline']  ?? '';
  $footerCopyright = $settings['footer_copyright'] ?? ('Â© ' . date('Y') . ' ' . $project->name);
@endphp
@if($faviconUrl)<link rel="icon" href="{{ $faviconUrl }}">@endif
<style>
:root {
  --c: {{ $primaryColor }};
  --c2: {{ $secondaryColor }};
  --radius: {{ $borderRadius }};
  --font-title: '{{ $fontTitle }}', sans-serif;
  --font-body:  '{{ $fontBody }}', sans-serif;
}
*, body { font-family: var(--font-body); }
body { background: #0a0a0a; color: #e5e5e5; }
[x-cloak] { display: none !important; }

/* â”€â”€ Utilidades acento â”€â”€ */
.btn-accent       { background: var(--c); color: #000; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; border-radius: 0; transition: filter .2s; }
.btn-accent:hover { filter: brightness(1.1); }
.btn-outline-accent { border: 2px solid var(--c); color: var(--c); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-radius: 0; background: transparent; transition: all .2s; }
.btn-outline-accent:hover { background: var(--c); color: #000; }
.price-c  { color: var(--c); }
.badge-c  { background: var(--c); color: #000; }

/* â”€â”€ Announcement bar â”€â”€ */
.announce-bar { background: var(--c); color: #000; font-weight: 700; font-size: .75rem; text-transform: uppercase; letter-spacing: .08em; text-align: center; padding: 10px 16px; }

/* â”€â”€ Header â”€â”€ */
.site-header { background: #000; border-bottom: 1px solid #1a1a1a; position: sticky; top: 0; z-index: 40; }
.search-box { background: #1a1a1a; border: 1px solid #333; border-radius: 0; transition: border-color .2s; }
.search-box:focus-within { border-color: var(--c); }

/* â”€â”€ Hero â”€â”€ */
.hero-section { min-height: 80vh; display: flex; align-items: center; position: relative; overflow: hidden; }

/* â”€â”€ Category tiles â”€â”€ */
.cat-tile { width: 180px; flex-shrink: 0; height: 260px; background: #111; position: relative; overflow: hidden; cursor: pointer; border: 2px solid transparent; transition: border-color .2s, transform .2s; }
.cat-tile:hover { border-color: var(--c); transform: translateY(-4px); }
.cat-tile img { width: 100%; height: 100%; object-fit: cover; filter: brightness(.6); transition: filter .3s; }
.cat-tile:hover img { filter: brightness(.8); }
.cat-tile-name { position: absolute; bottom: 0; left: 0; right: 0; padding: 14px; background: linear-gradient(to top, rgba(0,0,0,.9) 0%, transparent 100%); color: #fff; font-weight: 700; font-size: .85rem; text-transform: uppercase; letter-spacing: .06em; text-align: center; }

/* â”€â”€ Dark product card â”€â”€ */
.prod-card { background: #111; border: 1px solid #1e1e1e; border-radius: 0; transition: border-color .2s, box-shadow .2s; }
.prod-card:hover { border-color: var(--c); box-shadow: 0 0 28px rgba(0,0,0,.6); }
.prod-img { aspect-ratio: 1/1; overflow: hidden; background: #0d0d0d; }
.prod-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s, filter .3s; filter: brightness(.9); }
.prod-card:hover .prod-img img { transform: scale(1.06); filter: brightness(1); }
.prod-info { padding: 14px; }
.prod-cat-label { font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: #555; margin-bottom: 4px; }
.prod-name { font-size: .875rem; font-weight: 700; color: #f0f0f0; line-height: 1.3; }
.prod-price { font-size: 1.1rem; font-weight: 800; color: var(--c); margin-top: 6px; }
.prod-price-old { font-size: .75rem; color: #555; text-decoration: line-through; margin-left: 6px; }
.prod-btn { width: 100%; padding: 10px; background: transparent; color: var(--c); font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; border: 1px solid var(--c); cursor: pointer; margin-top: 12px; transition: all .2s; border-radius: 0; }
.prod-btn:hover { background: var(--c); color: #000; }
.badge-sale { position: absolute; top: 0; left: 0; background: var(--c); color: #000; font-size: .6rem; font-weight: 900; padding: 4px 10px; text-transform: uppercase; letter-spacing: .08em; border-radius: 0; }
.badge-new  { position: absolute; top: 0; right: 0; background: #222; color: #fff; font-size: .6rem; font-weight: 900; padding: 4px 10px; text-transform: uppercase; letter-spacing: .08em; border-radius: 0; }

/* â”€â”€ Section titles â”€â”€ */
.section-title { font-size: 1.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: -.02em; color: #fff; }

/* â”€â”€ Filter pills â”€â”€ */
.filter-pill { background: #1a1a1a; border: 1px solid #2a2a2a; color: #aaa; font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; padding: 6px 16px; cursor: pointer; transition: all .15s; border-radius: 0; }
.filter-pill:hover { border-color: #555; color: #fff; }
.filter-pill.active { background: var(--c); border-color: var(--c); color: #000; }

/* â”€â”€ Drawer oscuro â”€â”€ */
.drawer-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.75); z-index: 50; backdrop-filter: blur(4px); }
.drawer { position: fixed; top: 0; right: 0; height: 100%; width: 420px; max-width: 96vw; background: #111; z-index: 60; display: flex; flex-direction: column; box-shadow: -8px 0 48px rgba(0,0,0,.6); border-left: 1px solid #222; }
@media(max-width:640px){ .drawer { width: 100%; } }

/* â”€â”€ Scrollbar dark â”€â”€ */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: #0a0a0a; }
::-webkit-scrollbar-thumb { background: #333; border-radius: 0; }
::-webkit-scrollbar-thumb:hover { background: var(--c); }

/* â”€â”€ Horizontal scroll categories â”€â”€ */
.cats-scroll { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 4px; scrollbar-width: none; }
.cats-scroll::-webkit-scrollbar { display: none; }
</style>

@php
$currency = $settings['currency_symbol'] ?? $settings['currency'] ?? 'S/';
$searchIndex = $categories->flatMap(function($cat) use ($project) {
    $rows = $cat->products->map(fn($p) => [
        'id'       => $p->id,
        'name'     => $p->name,
        'price'    => (float)$p->price,
        'cp'       => $p->compare_price ? (float)$p->compare_price : null,
        'img'      => $p->mainImage ? $p->main_image_url : null,
        'cat'      => $cat->name,
        'catId'    => (string)$cat->id,
        'parentId' => null,
        'url'      => route('public.product', [$project->slug, $p->id]),
        'desc'     => \Str::limit(strip_tags($p->description ?? ''), 100),
        'stock'    => $p->stock,
    ]);
    $subRows = $cat->children->flatMap(fn($sub) => $sub->products->map(fn($p) => [
        'id'       => $p->id,
        'name'     => $p->name,
        'price'    => (float)$p->price,
        'cp'       => $p->compare_price ? (float)$p->compare_price : null,
        'img'      => $p->mainImage ? $p->main_image_url : null,
        'cat'      => $sub->name,
        'catId'    => (string)$sub->id,
        'parentId' => (string)$cat->id,
        'url'      => route('public.product', [$project->slug, $p->id]),
        'desc'     => \Str::limit(strip_tags($p->description ?? ''), 100),
        'stock'    => $p->stock,
    ]));
    return $rows->concat($subRows);
})->values();
@endphp
</head>
<body x-data="store()" x-cloak>

{{-- TOAST --}}
<div x-show="toastShow" x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-2"
     class="fixed top-20 left-1/2 -translate-x-1/2 z-50 bg-gray-900 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-lg whitespace-nowrap pointer-events-none"
     x-text="toastMsg">
</div>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     ANNOUNCEMENT BAR
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<div class="announce-bar">
  ENVÃO GRATIS EN PEDIDOS MAYORES A S/150 &mdash; NUEVO DROP DISPONIBLE
</div>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     HEADER
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<header class="site-header">
  <div class="max-w-[1400px] mx-auto px-4 py-4 flex items-center gap-4">

    {{-- Logo --}}
    <a href="{{ $canonicalUrl }}" class="flex items-center gap-3 flex-shrink-0" aria-label="{{ $project->name }}">
      @if($project->logo_url)
        <img src="{{ asset('storage/'.$project->logo_url) }}"
             alt="Logo {{ $project->name }}"
             style="height:44px; max-height:52px; max-width:180px" class="object-contain w-auto">
      @else
        <div class="h-10 w-10 btn-accent flex items-center justify-center text-lg font-black select-none">
          {{ strtoupper(substr($project->name,0,1)) }}
        </div>
        <span class="font-black text-white text-xl uppercase tracking-tight hidden sm:block">{{ $project->name }}</span>
      @endif
    </a>

    {{-- Buscador --}}
    <div class="flex-1 max-w-xl mx-auto relative" @click.outside="searchOpen = false">
      <div class="search-box flex items-stretch overflow-hidden">
        <input x-model="search"
               type="search"
               @input="searchOpen = search.trim().length >= 2; searchIdx = -1; if(search.trim().length >= 2) _scrollToCatalog()"
               @keydown.arrow-down.prevent="if(suggestions.length){searchIdx=(searchIdx+1)%suggestions.length}"
               @keydown.arrow-up.prevent="if(suggestions.length){searchIdx=(searchIdx-1+suggestions.length)%suggestions.length}"
               @keydown.enter.prevent="if(searchIdx>=0){selectSuggestion(suggestions[searchIdx])}else{searchOpen=false;_scrollToCatalog()}"
               @keydown.escape="searchOpen=false;searchIdx=-1"
               placeholder="Buscar productos..."
               aria-label="Buscar en el catÃ¡logo"
               autocomplete="off"
               class="w-full bg-transparent px-4 py-2.5 text-sm text-white outline-none placeholder-gray-600">
        <button @click="searchOpen=false; _scrollToCatalog()" class="btn-accent px-5 py-2 text-xs flex-shrink-0 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
          </svg>
          <span class="hidden sm:block">Buscar</span>
        </button>
      </div>
      {{-- Predictive search dropdown --}}
      <div x-show="searchOpen && suggestions.length > 0"
           x-cloak
           x-transition:enter="transition ease-out duration-100"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           class="absolute left-0 right-0 top-full mt-1 bg-white rounded-xl shadow-2xl border border-gray-200 z-[200] overflow-hidden"
           style="min-width:280px">
          <template x-for="(p, i) in suggestions" :key="p.id">
              <button @click="selectSuggestion(p)"
                      :class="searchIdx===i ? 'bg-indigo-50' : 'hover:bg-gray-50'"
                      class="flex items-center gap-3 w-full px-4 py-2.5 transition-colors text-left border-b border-gray-100 last:border-0">
                  <div class="w-10 h-10 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden">
                      <img x-show="p.img" :src="p.img" class="w-full h-full object-cover">
                      <div x-show="!p.img" class="w-full h-full flex items-center justify-center">
                          <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                          </svg>
                      </div>
                  </div>
                  <div class="flex-1 min-w-0">
                      <p class="text-sm font-semibold text-gray-800 truncate" x-html="_highlight(p.name)"></p>
                      <p class="text-xs text-gray-400 truncate" x-text="p.cat"></p>
                  </div>
                  <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                      <div class="text-right">
                          <p class="text-sm font-bold" style="color:var(--c,#4f46e5)" x-text="'{{ $currency }} ' + p.price.toFixed(2)"></p>
                          <p x-show="p.cp && p.cp > p.price" class="text-xs text-gray-400 line-through" x-text="'{{ $currency }} ' + (p.cp||0).toFixed(2)"></p>
                      </div>
                      <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                      </svg>
                  </div>
              </button>
          </template>
          <button @click="searchOpen=false; _scrollToCatalog()"
                  class="flex items-center justify-between w-full px-4 py-2.5 bg-gray-50 border-t border-gray-100 hover:bg-gray-100 transition-colors text-left">
              <span class="text-xs font-semibold text-gray-600">Ver todos los resultados
                  <span style="color:var(--c,#4f46e5)" x-text="'(' + suggestions.length + ')'"></span>
              </span>
              <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
              </svg>
          </button>
      </div>
    </div>

    {{-- Acciones --}}
    <div class="flex items-center gap-2 flex-shrink-0">
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}?text={{ urlencode('Hola, vi tu catÃ¡logo y quiero mÃ¡s informaciÃ³n.') }}"
         target="_blank" rel="noopener"
         class="hidden md:flex items-center gap-2 bg-[#25D366] hover:bg-[#20ba5a] text-white text-xs font-bold px-3 py-2 transition">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
        </svg>
        WhatsApp
      </a>
      @endif

      {{-- Carrito --}}
      <button @click="drawerOpen=true"
              class="relative p-2.5 hover:bg-white/5 transition"
              aria-label="Ver carrito">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <span x-show="cart.length" x-text="cart.length"
              class="absolute -top-0.5 -right-0.5 badge-c text-[10px] font-black w-5 h-5 flex items-center justify-center leading-none"></span>
      </button>
    </div>
  </div>
</header>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     HERO
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<section class="hero-section" style="background:{{ $heroBg }};">
  {{-- Textura sutil de fondo --}}
  <div class="absolute inset-0 opacity-5 pointer-events-none" style="background-image:repeating-linear-gradient(45deg,#fff 0,#fff 1px,transparent 0,transparent 50%);background-size:20px 20px;"></div>

  <div class="max-w-[1400px] mx-auto px-6 md:px-10 relative z-10 py-20 w-full">
    @if($heroBadge)
    <div class="inline-block btn-accent text-xs px-4 py-2 mb-6 tracking-widest">{{ strtoupper($heroBadge) }}</div>
    @endif
    <h1 class="text-6xl md:text-8xl font-black italic text-white leading-none mb-6 max-w-3xl uppercase" style="letter-spacing:-.03em;">
      {{ $heroTitle }}
    </h1>
    <p class="text-white/60 text-lg md:text-xl mb-8 max-w-xl font-medium">
      {{ $heroSub }}
    </p>
    <div class="flex flex-wrap items-center gap-4">
      <button @click="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
              class="btn-accent px-8 py-4 text-sm inline-flex items-center gap-2">
        Ver catÃ¡logo
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
        </svg>
      </button>
      @if($onSale->count())
      <button @click="document.getElementById('seccion-ofertas').scrollIntoView({behavior:'smooth'})"
              class="btn-outline-accent px-8 py-4 text-sm">
        Ver ofertas
      </button>
      @endif
    </div>
  </div>

  <div class="absolute bottom-0 right-0 w-64 h-64 opacity-5 pointer-events-none"
       style="background:radial-gradient(circle, var(--c) 0%, transparent 70%);"></div>
</section>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     CATEGORÃAS (scroll horizontal)
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
@if($categories->count() > 1)
<section class="py-12" style="background:#000;">
  <div class="max-w-[1400px] mx-auto px-4">
    <div class="flex items-center justify-between mb-6">
      <h2 class="section-title text-2xl">CategorÃ­as</h2>
    </div>
    <div class="cats-scroll">
      @foreach($categories as $cat)
      @php $catImg = $cat->products->first()?->mainImage?->url ?? null; @endphp
      <div class="cat-tile"
           @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
           :class="filterCat==='{{ $cat->id }}' ? 'border-[var(--c)]' : ''">
        @if($catImg)
          <img src="{{ asset('storage/'.$catImg) }}" alt="{{ $cat->name }}" loading="lazy">
        @else
          <div class="w-full h-full flex items-center justify-center" style="background:#1a1a1a;">
            <span class="text-5xl font-black text-white/10 uppercase">{{ strtoupper(substr($cat->name,0,2)) }}</span>
          </div>
        @endif
        <div class="cat-tile-name">{{ strtoupper($cat->name) }}</div>
        <div class="absolute top-2 right-2 badge-c text-[10px] font-black px-2 py-0.5">{{ $cat->products->count() }}</div>
      </div>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     DROPS â€” Novedades
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
@if($newArrivals->count())
<section class="py-14" style="background:#0a0a0a;">
  <div class="max-w-[1400px] mx-auto px-4">
    <div class="flex items-center gap-4 mb-8">
      <h2 class="section-title">DROPS</h2>
      <div class="flex-1" style="border-top:1px solid #1a1a1a;"></div>
      <span class="badge-c text-xs font-black px-3 py-1 uppercase tracking-widest">Nuevos</span>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
      @foreach($newArrivals as $p)
      <article id="prod-new-{{ $p->id }}" class="prod-card"
               x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }})">
        <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="block prod-img relative">
          @if($p->mainImage)
            <img src="{{ $p->main_image_url }}" alt="{{ $p->name }}" loading="lazy" class="w-full h-full object-cover">
          @else
            <div class="w-full h-full flex items-center justify-center opacity-10">
              <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
          @endif
          @if($p->compare_price && $p->compare_price > $p->price)
            <span class="badge-sale">-{{ round((($p->compare_price - $p->price) / $p->compare_price) * 100) }}%</span>
          @else
            <span class="badge-new">NEW</span>
          @endif
          @if($p->stock !== null && $p->stock === 0)
          <span class="absolute bottom-0 left-0 right-0 bg-red-600/90 text-white text-[10px] font-black py-1 text-center tracking-wide">AGOTADO</span>
          @elseif($p->stock !== null && $p->stock > 0 && $p->stock <= 5)
          <span class="absolute bottom-0 left-0 right-0 bg-orange-500/90 text-white text-[10px] font-bold py-1 text-center leading-tight">âš¡ CASI AGOTADO â€” {{ $p->stock }} restantes</span>
          @endif
        </a>
        <div class="prod-info">
          <p class="prod-cat-label">{{ $p->category->name ?? '' }}</p>
          <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="prod-name hover:underline">{{ $p->name }}</a>
          @if(!$isQuoteOnly || $quotePriceDisp === 'show')
          <div class="flex items-baseline flex-wrap">
            <span class="prod-price">S/ {{ number_format($p->price,2) }}</span>
            @if($p->compare_price && $p->compare_price > $p->price)
            <span class="prod-price-old">S/ {{ number_format($p->compare_price,2) }}</span>
            @endif
            @if($p->compare_price && $p->compare_price > $p->price)
            @php $ah = $p->compare_price - $p->price; @endphp
            <p class="text-[10px] text-green-600 font-semibold leading-none mt-1 w-full">Ahorras {{ $currency }} {{ number_format($ah,2) }}</p>
            @endif
          </div>
          @else
          <p style="font-size:.75rem;color:#555;margin-top:6px;font-style:italic">Precio a consultar</p>
          @endif
          <button class="prod-btn"
                  @click="addToCart({id:{{ $p->id }}, name:'{{ addslashes($p->name) }}', price:{{ $p->price }}, img:'{{ $p->mainImage ? $p->main_image_url : '' }}'})">
            {{ $isQuoteOnly ? 'Cotizar' : 'Agregar' }}
          </button>
        </div>
      </article>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     BANNER FULL-WIDTH OFERTA
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<section id="seccion-ofertas" style="background:var(--c);">
  <div class="max-w-[1400px] mx-auto px-4 py-16 flex flex-col md:flex-row items-center justify-between gap-6">
    <div>
      <p class="text-black/60 text-sm font-bold uppercase tracking-widest mb-2">Ofertas especiales</p>
      <p class="text-black font-black uppercase leading-none" style="font-size:clamp(3rem,8vw,6rem);letter-spacing:-.04em;">{{ $b2Title }}</p>
      <p class="text-black/70 font-semibold mt-2 text-lg">{{ $b2Sub }}</p>
    </div>
    <button @click="onSaleFilter=true; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
            class="flex-shrink-0 bg-black text-white font-black uppercase tracking-widest px-10 py-5 text-sm hover:bg-[#111] transition inline-flex items-center gap-2">
      Ver todas las ofertas
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
      </svg>
    </button>
  </div>
</section>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     BEST SELLERS â€” Destacados
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
@if($featured->count())
<section class="py-14" style="background:#000;">
  <div class="max-w-[1400px] mx-auto px-4">
    <div class="flex items-center gap-4 mb-8">
      <h2 class="section-title">BEST SELLERS</h2>
      <div class="flex-1" style="border-top:1px solid #1a1a1a;"></div>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
      @foreach($featured as $p)
      <article id="prod-feat-{{ $p->id }}" class="prod-card"
               x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }})">
        <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="block prod-img relative">
          @if($p->mainImage)
            <img src="{{ $p->main_image_url }}" alt="{{ $p->name }}" loading="lazy" class="w-full h-full object-cover">
          @else
            <div class="w-full h-full flex items-center justify-center opacity-10">
              <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
          @endif
          @if($p->compare_price && $p->compare_price > $p->price)
            <span class="badge-sale">-{{ round((($p->compare_price - $p->price) / $p->compare_price) * 100) }}%</span>
          @endif
          @if($p->stock !== null && $p->stock === 0)
          <span class="absolute bottom-0 left-0 right-0 bg-red-600/90 text-white text-[10px] font-black py-1 text-center tracking-wide">AGOTADO</span>
          @elseif($p->stock !== null && $p->stock > 0 && $p->stock <= 5)
          <span class="absolute bottom-0 left-0 right-0 bg-orange-500/90 text-white text-[10px] font-bold py-1 text-center leading-tight">âš¡ CASI AGOTADO â€” {{ $p->stock }} restantes</span>
          @endif
        </a>
        <div class="prod-info">
          <p class="prod-cat-label">{{ $p->category->name ?? '' }}</p>
          <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="prod-name hover:underline">{{ $p->name }}</a>
          @if(!$isQuoteOnly || $quotePriceDisp === 'show')
          <div class="flex items-baseline flex-wrap">
            <span class="prod-price">S/ {{ number_format($p->price,2) }}</span>
            @if($p->compare_price && $p->compare_price > $p->price)
            <span class="prod-price-old">S/ {{ number_format($p->compare_price,2) }}</span>
            @endif
            @if($p->compare_price && $p->compare_price > $p->price)
            @php $ah = $p->compare_price - $p->price; @endphp
            <p class="text-[10px] text-green-600 font-semibold leading-none mt-1 w-full">Ahorras {{ $currency }} {{ number_format($ah,2) }}</p>
            @endif
          </div>
          @else
          <p style="font-size:.75rem;color:#555;margin-top:6px;font-style:italic">Precio a consultar</p>
          @endif
          <button class="prod-btn"
                  @click="addToCart({id:{{ $p->id }}, name:'{{ addslashes($p->name) }}', price:{{ $p->price }}, img:'{{ $p->mainImage ? $p->main_image_url : '' }}'})">
            {{ $isQuoteOnly ? 'Cotizar' : 'Agregar' }}
          </button>
        </div>
      </article>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     CATÃLOGO COMPLETO
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<section id="catalogo" class="py-14" style="background:#0a0a0a;">
  <div class="max-w-[1400px] mx-auto px-4">

    {{-- Cabecera + filtros --}}
    <div class="mb-8 sticky top-16 z-20 bg-white/95 backdrop-blur-sm shadow-sm px-4 py-3 -mx-4">
      <div class="flex items-center gap-4 mb-5">
        <h2 class="section-title">CATÃLOGO</h2>
        <span x-show="filterCat!=='' || search!=='' || onSaleFilter"
              class="badge-c text-xs font-black px-3 py-1 uppercase tracking-widest">Filtrado</span>
      </div>
      <p class="text-xs text-gray-400 mb-4" x-show="filterCat!==''||search!==''||priceFilter!==''||onSaleFilter">
        <span x-text="visibleCount"></span> producto<span x-show="visibleCount!==1">s</span> encontrado<span x-show="visibleCount!==1">s</span>
      </p>

      {{-- Filtro precio --}}
      <div class="flex flex-wrap items-center gap-2 mb-3">
        <span class="text-xs text-white/30 font-bold uppercase tracking-widest mr-2">Precio:</span>
        <button @click="priceFilter=''" :class="priceFilter==='' ? 'active' : ''" class="filter-pill">Todos</button>
        <button @click="priceFilter='0-50'" :class="priceFilter==='0-50' ? 'active' : ''" class="filter-pill">Hasta S/50</button>
        <button @click="priceFilter='50-150'" :class="priceFilter==='50-150' ? 'active' : ''" class="filter-pill">S/50â€“150</button>
        <button @click="priceFilter='150-500'" :class="priceFilter==='150-500' ? 'active' : ''" class="filter-pill">S/150â€“500</button>
        <button @click="priceFilter='500+'" :class="priceFilter==='500+' ? 'active' : ''" class="filter-pill">+S/500</button>
      </div>

      {{-- Filtro categorÃ­as + oferta --}}
      <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs text-white/30 font-bold uppercase tracking-widest mr-2">Cat:</span>
        <button @click="filterCat=''" :class="filterCat==='' ? 'active' : ''" class="filter-pill">Todo</button>
        @foreach($categories as $cat)
        <button @click="filterCat='{{ $cat->id }}'" :class="filterCat==='{{ $cat->id }}' ? 'active' : ''" class="filter-pill">
          {{ strtoupper($cat->name) }}
        </button>
        @endforeach
        <button @click="onSaleFilter=!onSaleFilter" :class="onSaleFilter ? 'active' : ''" class="filter-pill">
          Solo ofertas
        </button>
        <button x-show="filterCat!=='' || search!=='' || priceFilter!=='' || onSaleFilter"
                @click="filterCat=''; search=''; priceFilter=''; onSaleFilter=false"
                class="filter-pill" style="color:#f87171;border-color:rgba(127,29,29,.5);">
          x Limpiar
        </button>
        <button @click="filterOpen=true"
                class="xl:hidden filter-pill flex items-center gap-1.5 relative">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
          </svg>
          Filtros
          <span x-show="priceFilter!=='' || onSaleFilter"
                class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full text-[10px] font-black flex items-center justify-center text-white"
                style="background:var(--c)"
                x-text="(priceFilter!==''?1:0)+(onSaleFilter?1:0)"></span>
        </button>
        <select x-model="sortBy" class="filter-pill bg-transparent cursor-pointer focus:outline-none text-xs">
          <option value="default" class="bg-black">Ordenar...</option>
          <option value="price_asc" class="bg-black">Precio â†‘</option>
          <option value="price_desc" class="bg-black">Precio â†“</option>
          <option value="newest" class="bg-black">MÃ¡s nuevos</option>
          <option value="name_az" class="bg-black">Nombre Aâ†’Z</option>
        </select>
      </div>
    </div>

    {{-- Grid productos --}}
    @foreach($categories as $cat)
    @if($cat->products->count())
    <div x-show="filterCat==='' || filterCat==='{{ $cat->id }}'" class="mb-14">
      <div class="flex items-center gap-3 mb-5">
        <h3 class="text-sm font-black text-white/50 uppercase tracking-widest">{{ $cat->name }}</h3>
        <span class="badge-c text-[10px] font-black px-2 py-0.5">{{ $cat->products->count() }}</span>
        <div class="flex-1" style="border-top:1px solid #1a1a1a;"></div>
      </div>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4" data-products-grid>
        @foreach($cat->products as $p)
        @php
        $qvData = ['id'=>$p->id,'name'=>$p->name,'img'=>$p->mainImage?$p->main_image_url:'','price'=>(float)$p->price,'cp'=>$p->compare_price?(float)$p->compare_price:null,'desc'=>\Str::limit(strip_tags($p->description??''),120),'url'=>route('public.product',[$project->slug,$p->id]),'stock'=>$p->stock];
        @endphp
        <article id="producto-{{ $p->id }}" class="prod-card group"
                 x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }}) && ({{ $loop->index }} < 8 || expandedCats['{{ $cat->id }}'])"
                 itemscope itemtype="https://schema.org/Product"
                 data-price="{{ $p->price }}"
                 data-ts="{{ $p->created_at ? $p->created_at->timestamp : 0 }}"
                 data-idx="{{ $loop->index }}"
                 data-qv='@json($qvData)'>
          <meta itemprop="name" content="{{ $p->name }}">
          <meta itemprop="sku"  content="{{ $p->sku ?? $p->id }}">

          <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="block prod-img relative">
            @if($p->mainImage)
            <img src="{{ $p->main_image_url }}"
                 alt="{{ $p->name }} â€” {{ $cat->name }}"
                 loading="lazy" decoding="async" width="400" height="400"
                 class="w-full h-full object-cover" itemprop="image">
            @else
            <div class="w-full h-full flex items-center justify-center opacity-10">
              <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
            @endif
            @if($p->compare_price && $p->compare_price > $p->price)
            <span class="badge-sale">-{{ round((($p->compare_price - $p->price) / $p->compare_price) * 100) }}%</span>
            @elseif($p->created_at && $p->created_at->diffInDays() <= 30)
            <span class="badge-new">NEW</span>
            @endif
            @if($p->stock !== null && $p->stock === 0)
            <span class="badge-sale" style="bottom:8px;left:8px;top:auto;background:#dc2626;">Agotado</span>
            @elseif($p->stock !== null && $p->stock > 0 && $p->stock <= 5)
            <span class="badge-new" style="bottom:8px;left:8px;top:auto;background:#f59e0b;">Ãšltimas {{ $p->stock }}</span>
            @endif
            <button @click.prevent="const d=$el.closest('[data-qv]');if(d){qv=JSON.parse(d.dataset.qv);qvOpen=true}"
                    class="absolute inset-0 flex items-end justify-center pb-3 opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none group-hover:pointer-events-auto z-10">
              <span class="bg-black/70 text-white text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-1.5 backdrop-blur-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Vista rÃ¡pida
              </span>
            </button>
          </a>

          <div class="prod-info" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
            <meta itemprop="priceCurrency" content="PEN">
            <meta itemprop="price" content="{{ $p->price }}">
            <meta itemprop="availability" content="https://schema.org/InStock">
            <p class="prod-cat-label">{{ $cat->name }}</p>
            <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="prod-name hover:underline" itemprop="name">{{ $p->name }}</a>
            @if(isset($productRatings) && isset($productRatings[$p->id]))
            <div class="flex items-center gap-1 mb-1">
              <span class="text-amber-400 text-xs">{{ str_repeat('â˜…', floor($productRatings[$p->id]->avg_rating)) }}{{ str_repeat('â˜†', 5 - floor($productRatings[$p->id]->avg_rating)) }}</span>
              <span class="text-[10px] text-gray-400">({{ $productRatings[$p->id]->rating_count }})</span>
            </div>
            @endif
            @if(!$isQuoteOnly || $quotePriceDisp === 'show')
            <div class="flex items-baseline flex-wrap">
              <span class="prod-price">S/ {{ number_format($p->price,2) }}</span>
              @if($p->compare_price && $p->compare_price > $p->price)
              <span class="prod-price-old">S/ {{ number_format($p->compare_price,2) }}</span>
              @endif
              @if($p->compare_price && $p->compare_price > $p->price)
              @php $ah = $p->compare_price - $p->price; @endphp
              <p class="text-[10px] text-green-600 font-semibold leading-none mt-1 w-full">Ahorras {{ $currency }} {{ number_format($ah,2) }}</p>
              @endif
            </div>
            @else
            <p style="font-size:.75rem;color:#555;margin-top:6px;font-style:italic">Precio a consultar</p>
            @endif
            <button class="prod-btn"
                    @click="addToCart({id:{{ $p->id }}, name:'{{ addslashes($p->name) }}', price:{{ $p->price }}, img:'{{ $p->mainImage ? $p->main_image_url : '' }}'})">
              {{ $isQuoteOnly ? 'Cotizar' : 'Agregar al carrito' }}
            </button>
          </div>
        </article>
        @endforeach
        @if($cat->products->count() > 8)
        <div class="col-span-full mt-2 text-center" x-show="!expandedCats['{{ $cat->id }}']">
          <button @click="expandedCats={...expandedCats,'{{ $cat->id }}':true}"
                  class="text-sm font-semibold px-5 py-2 rounded-xl border-2 transition hover:text-white hover:bg-[var(--c)] hover:border-[var(--c)]"
                  style="border-color:var(--c);color:var(--c)">
            Ver todos los {{ $cat->products->count() }} productos
          </button>
        </div>
        @endif
      </div>
    </div>
    @endif
    @endforeach

    {{-- Sin resultados --}}
    <div x-show="noResults" class="text-center py-24">
      <p class="text-5xl mb-4 opacity-20">â€”</p>
      <p class="font-black text-white text-xl uppercase mb-2">Sin resultados</p>
      <p class="text-white/30 text-sm mb-6">Intenta con otro tÃ©rmino o categorÃ­a</p>
      <button @click="search=''; filterCat=''; priceFilter=''; onSaleFilter=false"
              class="btn-outline-accent px-6 py-3 text-sm">
        Ver todo el catÃ¡logo
      </button>
    </div>

    <div x-show="recentlyViewed.length > 0" x-cloak class="mt-10 pt-6 border-t border-gray-100">
      <h3 class="font-black text-gray-800 text-base mb-4">Vistos recientemente</h3>
      <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
        <template x-for="rv in recentlyViewed" :key="rv.id">
          <a :href="rv.url" class="flex-shrink-0 w-36 bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition group">
            <div class="aspect-square bg-gray-50 overflow-hidden">
              <img x-show="rv.img" :src="rv.img" :alt="rv.name" class="w-full h-full object-cover group-hover:scale-105 transition">
              <div x-show="!rv.img" class="w-full h-full flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
              </div>
            </div>
            <div class="p-2">
              <p class="text-xs font-semibold text-gray-800 line-clamp-2 leading-tight" x-text="rv.name"></p>
              <p class="text-xs font-black mt-1" style="color:var(--c)" x-text="'{{ $currency }} ' + rv.price.toFixed(2)"></p>
            </div>
          </a>
        </template>
      </div>
    </div>

  </div>
</section>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     FOOTER
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<footer style="background:#000;border-top:1px solid #1a1a1a;">
  <div class="max-w-[1400px] mx-auto px-4 py-14 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">

    {{-- Col 1: Marca --}}
    <div>
      <div class="flex items-center gap-3 mb-5">
        @php $footerLogo = ($settings['logo_url'] ?? '') ?: ($project->logo_url ?? ''); $footerLogoSrc = $footerLogo ? (str_starts_with($footerLogo,'http') ? $footerLogo : asset('storage/'.$footerLogo)) : ''; @endphp
        @if($footerLogoSrc)
        <img src="{{ $footerLogoSrc }}" alt="Logo {{ $project->name }}"
             style="max-height:60px; max-width:200px" class="object-contain w-auto" loading="lazy">
        @else
        <div class="h-10 w-10 btn-accent flex items-center justify-center text-lg font-black">
          {{ strtoupper(substr($project->name,0,1)) }}
        </div>
        @endif
        <span class="text-white font-black text-lg uppercase tracking-tight">{{ $project->name }}</span>
      </div>
      <p class="text-white/30 text-sm leading-relaxed">Streetwear urbano. Prendas de alto impacto para quienes no pasan desapercibidos.</p>
    </div>

    {{-- Col 2: CategorÃ­as --}}
    <div>
      <h4 class="text-white font-black text-xs uppercase tracking-widest mb-4">CategorÃ­as</h4>
      <ul class="space-y-2">
        @foreach($categories->take(6) as $cat)
        <li>
          <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                  class="text-white/40 hover:text-white text-sm transition uppercase tracking-wide font-medium">
            {{ $cat->name }}
          </button>
        </li>
        @endforeach
      </ul>
    </div>

    {{-- Col 3: Contacto --}}
    <div>
      <h4 class="text-white font-black text-xs uppercase tracking-widest mb-4">Contacto</h4>
      <div class="space-y-3 text-sm text-white/40">
        @if($project->phone)
        <div class="flex items-center gap-2">
          <svg class="w-3.5 h-3.5 opacity-50 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
          </svg>
          <span>{{ $project->phone }}</span>
        </div>
        @endif
        @if($project->address)
        <div class="flex items-start gap-2">
          <svg class="w-3.5 h-3.5 opacity-50 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <span>{{ $project->address }}</span>
        </div>
        @endif
        @if($project->whatsapp)
        <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}"
           target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 bg-[#25D366] hover:bg-[#20ba5a] text-white text-xs font-bold px-4 py-2 transition mt-2">
          WhatsApp
        </a>
        @endif
      </div>
    </div>

    {{-- Col 4: Redes --}}
    <div>
      <h4 class="text-white font-black text-xs uppercase tracking-widest mb-4">SÃ­guenos</h4>
      <div class="flex flex-wrap gap-2">
        @if($settings['instagram_url'] ?? null)
        <a href="{{ $settings['instagram_url'] }}" target="_blank" rel="noopener"
           class="px-3 py-1.5 text-white text-xs font-bold transition hover:opacity-80"
           style="background:#E1306C">Instagram</a>
        @endif
        @if($settings['facebook_url'] ?? null)
        <a href="{{ $settings['facebook_url'] }}" target="_blank" rel="noopener"
           class="px-3 py-1.5 text-white text-xs font-bold transition hover:opacity-80"
           style="background:#1877F2">Facebook</a>
        @endif
        @if($settings['tiktok_url'] ?? null)
        <a href="{{ $settings['tiktok_url'] }}" target="_blank" rel="noopener"
           class="px-3 py-1.5 text-white text-xs font-bold transition hover:opacity-80"
           style="background:#010101;border:1px solid #333">TikTok</a>
        @endif
        @if($settings['twitter_url'] ?? null)
        <a href="{{ $settings['twitter_url'] }}" target="_blank" rel="noopener"
           class="px-3 py-1.5 text-white text-xs font-bold transition hover:opacity-80"
           style="background:#14171A;border:1px solid #333">X</a>
        @endif
      </div>
    </div>

  </div>
  <div style="border-top:1px solid #111;" class="py-5 text-center text-xs text-white/20">
    <span>&copy; {{ date('Y') }} <strong class="text-white/40">{{ $project->name }}</strong>.</span>
    <span class="mx-2 opacity-30">/</span>
    <span>CatÃ¡logo online por <strong class="text-white/40">AVAN</strong></span>
  </div>
</footer>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     MOBILE FILTER BOTTOM-SHEET
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<div x-show="filterOpen" x-cloak class="xl:hidden fixed inset-0 z-50 flex flex-col justify-end">
  <div @click="filterOpen=false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
  <div class="relative bg-white rounded-t-3xl shadow-2xl max-h-[80vh] flex flex-col"
       x-transition:enter="transition ease-out duration-300 transform"
       x-transition:enter-start="translate-y-full"
       x-transition:enter-end="translate-y-0"
       x-transition:leave="transition ease-in duration-200 transform"
       x-transition:leave-start="translate-y-0"
       x-transition:leave-end="translate-y-full">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
      <div class="flex items-center gap-2">
        <h3 class="font-black text-gray-900">Filtros</h3>
        <span x-show="priceFilter!=='' || onSaleFilter"
              class="text-xs px-2 py-0.5 rounded-full font-bold text-white"
              style="background:var(--c)"
              x-text="(priceFilter!==''?1:0)+(onSaleFilter?1:0) + ' activo' + ((priceFilter!==''?1:0)+(onSaleFilter?1:0)>1?'s':'')"></span>
      </div>
      <button @click="filterOpen=false" class="p-1.5 rounded-lg hover:bg-gray-100 transition">
        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>
    <div class="overflow-y-auto flex-1 px-5 py-4 space-y-6">
      <div>
        <p class="font-bold text-gray-800 text-sm mb-3">Precio</p>
        <div class="grid grid-cols-2 gap-2">
          <button @click="priceFilter=''; priceMin=0; priceMax=0"
                  :class="priceFilter==='' ? 'border-[var(--c)] text-[var(--c)] font-bold' : 'border-gray-200 text-gray-600'"
                  class="border rounded-xl px-3 py-2 text-sm text-left transition">Todos</button>
          <button @click="priceFilter='0-50'"
                  :class="priceFilter==='0-50' ? 'border-[var(--c)] text-[var(--c)] font-bold' : 'border-gray-200 text-gray-600'"
                  class="border rounded-xl px-3 py-2 text-sm text-left transition">Hasta {{ $currency }} 50</button>
          <button @click="priceFilter='50-150'"
                  :class="priceFilter==='50-150' ? 'border-[var(--c)] text-[var(--c)] font-bold' : 'border-gray-200 text-gray-600'"
                  class="border rounded-xl px-3 py-2 text-sm text-left transition">{{ $currency }} 50â€“150</button>
          <button @click="priceFilter='150-500'"
                  :class="priceFilter==='150-500' ? 'border-[var(--c)] text-[var(--c)] font-bold' : 'border-gray-200 text-gray-600'"
                  class="border rounded-xl px-3 py-2 text-sm text-left transition">{{ $currency }} 150â€“500</button>
          <button @click="priceFilter='500+'"
                  :class="priceFilter==='500+' ? 'border-[var(--c)] text-[var(--c)] font-bold' : 'border-gray-200 text-gray-600'"
                  class="border rounded-xl px-3 py-2 text-sm text-left transition col-span-2">MÃ¡s de {{ $currency }} 500</button>
        </div>
        <div class="mt-3 pt-3 border-t border-gray-100">
          <p class="text-xs text-gray-400 mb-2">O ingresa un rango personalizado</p>
          <div class="flex items-center gap-2">
            <input type="number" x-model.number="priceMin" placeholder="MÃ­n" min="0"
                   class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-gray-400 transition">
            <span class="text-gray-300">â€”</span>
            <input type="number" x-model.number="priceMax" placeholder="MÃ¡x" min="0"
                   class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-gray-400 transition">
          </div>
          <button @click="if(priceMin||priceMax){priceFilter='custom'}"
                  :disabled="!priceMin && !priceMax"
                  class="mt-2 w-full py-2 rounded-xl text-sm font-bold transition disabled:opacity-40"
                  style="background:var(--c);color:#fff">Aplicar rango</button>
        </div>
      </div>
      <div>
        <p class="font-bold text-gray-800 text-sm mb-3">Disponibilidad</p>
        <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer transition"
               :class="onSaleFilter ? 'border-[var(--c)] bg-[color-mix(in_srgb,var(--c)_8%,white)]' : ''">
          <input type="checkbox" x-model="onSaleFilter" class="accent-[var(--c)] w-4 h-4 rounded">
          <span class="text-sm font-medium text-gray-700">Solo productos en oferta</span>
        </label>
      </div>
      <div>
        <p class="font-bold text-gray-800 text-sm mb-3">Ordenar por</p>
        <div class="space-y-2">
          @foreach([['default','Relevancia'],['price_asc','Precio: menor a mayor'],['price_desc','Precio: mayor a menor'],['newest','MÃ¡s nuevos primero'],['name_az','Nombre A â†’ Z']] as [$val,$lbl])
          <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer transition"
                 :class="sortBy==='{{ $val }}' ? 'border-[var(--c)] bg-[color-mix(in_srgb,var(--c)_8%,white)]' : ''">
            <input type="radio" x-model="sortBy" value="{{ $val }}" class="accent-[var(--c)] w-4 h-4">
            <span class="text-sm font-medium text-gray-700">{{ $lbl }}</span>
          </label>
          @endforeach
        </div>
      </div>
    </div>
    <div class="flex gap-3 px-5 py-4 border-t border-gray-100 flex-shrink-0">
      <button @click="priceFilter=''; priceMin=0; priceMax=0; onSaleFilter=false; sortBy='default'"
              class="flex-1 py-3 rounded-xl text-sm font-bold border border-gray-300 text-gray-700 hover:bg-gray-50 transition">
        Limpiar todo
      </button>
      <button @click="filterOpen=false"
              class="flex-1 py-3 rounded-xl text-sm font-bold text-white transition"
              style="background:var(--c)">
        Ver resultados
      </button>
    </div>
  </div>
</div>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     DRAWER â€” 3 pasos
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<div x-show="drawerOpen" x-cloak>
  <div class="drawer-overlay" @click="drawerOpen=false" aria-hidden="true"></div>

  <div class="drawer"
       role="dialog"
       aria-label="{{ $isQuoteOnly ? 'Mi cotizaciÃ³n' : 'Mi pedido' }}"
       x-show="drawerOpen"
       x-transition:enter="transition ease-out duration-300 transform"
       x-transition:enter-start="translate-x-full"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-200 transform"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="translate-x-full">

    {{-- Header del drawer --}}
    <div class="flex items-center justify-between px-5 py-4 flex-shrink-0" style="border-bottom:1px solid #222;">
      <div class="flex items-center gap-2">
        <button x-show="(drawerStep === 2 || drawerStep === 3) && !orderSent"
                @click="drawerStep > 1 ? drawerStep-- : null"
                class="p-1.5 hover:bg-white/5 transition mr-1">
          <svg class="w-4 h-4 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <h2 class="font-black text-white uppercase tracking-wide text-sm"
            x-text="drawerStep === 1 ? '{{ $isQuoteOnly ? 'Mi cotizaciÃ³n' : 'Mi pedido' }}' : (drawerStep === 2 ? 'Confirmar datos' : 'Pagar')"></h2>
        <span x-show="cart.length && drawerStep === 1"
              class="badge-c text-[10px] font-black px-2 py-0.5" x-text="cart.length + ' items'"></span>
      </div>
      <button @click="drawerOpen=false" class="p-2 hover:bg-white/5 transition">
        <svg class="w-5 h-5 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    {{-- â•â• PASO 1: Carrito â•â• --}}
    <div x-show="drawerStep === 1" class="flex flex-col flex-1 overflow-hidden">
      <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <template x-if="cart.length === 0">
          <div class="text-center py-16">
            <svg class="w-16 h-16 mx-auto mb-4 text-white/10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="font-bold text-white/30 mb-1">{{ $isQuoteOnly ? 'Tu cotizaciÃ³n estÃ¡ vacÃ­a' : 'Tu carrito estÃ¡ vacÃ­o' }}</p>
            <p class="text-sm text-white/20">Agrega productos para comenzar</p>
          </div>
        </template>

        <template x-for="(item, i) in cart" :key="item.id">
          <div class="flex items-center gap-3 p-3" style="background:#1a1a1a;border:1px solid #2a2a2a;">
            <div class="w-14 h-14 overflow-hidden flex-shrink-0" style="background:#111;">
              <img :src="item.img" x-show="item.img" class="w-full h-full object-cover" :alt="item.name">
              <div x-show="!item.img" class="w-full h-full flex items-center justify-center text-white/20 text-xs">IMG</div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-white leading-snug line-clamp-2" x-text="item.name"></p>
              @if(!$isQuoteOnly || $quotePriceDisp === 'show')
              <p class="price-c font-black text-sm mt-0.5" x-text="'S/ ' + (item.price * item.qty).toFixed(2)"></p>
              @endif
            </div>
            <div class="flex items-center gap-1 flex-shrink-0">
              <button @click="item.qty > 1 ? item.qty-- : cart.splice(i,1)"
                      class="w-8 h-8 text-white/50 hover:text-red-400 font-bold text-sm flex items-center justify-center transition"
                      style="border:1px solid #333;">
                <span x-text="item.qty > 1 ? 'âˆ’' : 'Ã—'"></span>
              </button>
              <span class="w-7 text-center text-sm font-black text-white" x-text="item.qty"></span>
              <button @click="item.qty++"
                      class="w-8 h-8 btn-accent text-xs font-bold flex items-center justify-center">+</button>
            </div>
          </div>
        </template>
      </div>

      <div x-show="cart.length > 0" class="px-5 py-4 flex-shrink-0 space-y-3" style="border-top:1px solid #222;">
        <div class="flex justify-between items-center">
          <span class="text-sm text-white/40"><span x-text="cart.reduce((s,i)=>s+i.qty,0)"></span> productos</span>
          @if(!$isQuoteOnly || $quotePriceDisp === 'show')
          <span class="font-black text-lg price-c" x-text="'S/ ' + cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></span>
          @else
          <span class="text-xs text-white/30 italic">Precios a cotizar</span>
          @endif
        </div>
        <button @click="drawerStep=2; orderError=''"
                class="w-full btn-accent py-4 text-sm flex items-center justify-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
          </svg>
          {{ $isQuoteOnly ? 'Continuar y cotizar' : 'Continuar y pedir' }}
        </button>
      </div>
    </div>

    {{-- â•â• PASO 2: Formulario â•â• --}}
    <div x-show="drawerStep === 2" class="flex flex-col flex-1 overflow-hidden">
      <div x-show="!orderSent" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">

        <div class="px-4 py-3 flex justify-between items-center" style="background:#1a1a1a;border:1px solid #2a2a2a;">
          <span class="text-sm text-white/50 font-medium">
            <span x-text="cart.reduce((s,i)=>s+i.qty,0)"></span> producto(s)
          </span>
          @if(!$isQuoteOnly || $quotePriceDisp === 'show')
          <span class="font-black price-c" x-text="'S/ ' + cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></span>
          @else
          <span class="text-xs text-white/30 italic">a cotizar</span>
          @endif
        </div>

        <input x-model="form.name" type="text" placeholder="Tu nombre completo *"
               style="background:#1a1a1a;border:1px solid #333;"
               class="w-full px-4 py-3 text-sm text-white placeholder-gray-600 outline-none transition"
               autocomplete="name">
        <input x-model="form.phone" type="tel" placeholder="Tu WhatsApp / telÃ©fono *"
               style="background:#1a1a1a;border:1px solid #333;"
               class="w-full px-4 py-3 text-sm text-white placeholder-gray-600 outline-none transition"
               autocomplete="tel">
        <input x-model="form.email" type="email" placeholder="Correo electrÃ³nico (opcional)"
               style="background:#1a1a1a;border:1px solid #333;"
               class="w-full px-4 py-3 text-sm text-white placeholder-gray-600 outline-none transition"
               autocomplete="email">
        <textarea x-model="form.notes" rows="2" placeholder="Notas adicionales (opcional)"
                  style="background:#1a1a1a;border:1px solid #333;"
                  class="w-full px-4 py-3 text-sm text-white placeholder-gray-600 outline-none resize-none transition"></textarea>
        @if($requireAddress)
        <input x-model="form.address" type="text" placeholder="DirecciÃ³n de entrega *"
               style="background:#1a1a1a;border:1px solid #333;" autocomplete="street-address"
               class="w-full px-4 py-3 text-sm text-white placeholder-gray-600 outline-none transition">
        @endif
        {{-- CupÃ³n --}}
        <div>
          <div x-show="!couponApplied" class="flex gap-2">
            <input x-model="couponCode" @keydown.enter.prevent="applyCoupon" type="text"
                   placeholder="CÃ³digo de descuento"
                   class="flex-1 rounded px-4 py-2.5 text-sm outline-none transition uppercase text-white"
                   style="background:#1a1a1a;border:1px solid #333;text-transform:uppercase">
            <button @click="applyCoupon" :disabled="couponLoading" type="button"
                    class="px-4 py-2.5 rounded text-sm font-semibold flex-shrink-0 text-gray-300 transition"
                    style="background:#1a1a1a;border:1px solid #333;">
              <span x-text="couponLoading ? 'â€¦' : 'Aplicar'"></span>
            </button>
          </div>
          <div x-show="couponApplied" class="flex items-center justify-between rounded px-4 py-2.5 text-sm" style="background:#1a3a1a;border:1px solid #2a5a2a;">
            <div>
              <span class="font-mono font-bold text-green-400" x-text="couponApplied ? couponApplied.code : ''"></span>
              <span class="text-green-500 ml-1">&mdash; <span x-text="couponApplied && couponApplied.type==='percent' ? couponApplied.value+'% desc.' : 'S/ '+(couponApplied?couponApplied.value:0).toFixed(2)+' desc.'"></span></span>
            </div>
            <button @click="removeCoupon" type="button" class="text-gray-500 hover:text-red-400 ml-3 text-lg leading-none">&times;</button>
          </div>
          <p x-show="couponError" class="text-red-400 text-xs mt-1" x-text="couponError"></p>
        </div>
        <div x-show="shippingEnabled || couponApplied" style="background:#1a1a1a;border:1px solid #333;" class="rounded px-4 py-3 space-y-1.5 text-sm">
          <div class="flex justify-between text-gray-400"><span>Subtotal</span><span x-text="'S/ ' + subtotal.toFixed(2)"></span></div>
          <div x-show="couponApplied && couponDiscount > 0" class="flex justify-between text-green-400 font-medium">
            <span>Descuento</span>
            <span x-text="'- S/ ' + couponDiscount.toFixed(2)"></span>
          </div>
          <div x-show="shippingEnabled" class="flex justify-between" :class="effectiveShipping===0 && shippingFreeFrom>0 ? 'text-green-400 font-medium' : 'text-gray-400'">
            <span x-text="effectiveShipping===0 && shippingFreeFrom>0 ? 'ðŸŽ‰ EnvÃ­o gratis' : 'EnvÃ­o'"></span>
            <span x-text="effectiveShipping>0 ? 'S/ '+effectiveShipping.toFixed(2) : 'Gratis'"></span>
          </div>
          @if($shippingFreeFrom > 0)<p x-show="effectiveShipping>0" class="text-xs text-gray-600">Agrega S/ <span x-text="Math.max(0,{{ $shippingFreeFrom }}-subtotal).toFixed(2)"></span> mÃ¡s para envÃ­o gratis</p>@endif
          <div class="flex justify-between font-black text-white" style="border-top:1px solid #333;padding-top:6px;"><span>Total</span><span x-text="'S/ '+orderGrandTotal.toFixed(2)"></span></div>
        </div>

        <p x-show="orderError" class="text-red-400 text-xs text-center font-medium" x-text="orderError"></p>
      </div>

      @if($isQuoteOnly)
      <div x-show="orderSent" class="flex-1 flex flex-col items-center justify-center px-5 py-8 text-center">
        <div class="w-20 h-20 btn-accent flex items-center justify-center mb-5">
          <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <p class="font-black text-white text-xl mb-2 uppercase">CotizaciÃ³n enviada</p>
        <p class="text-sm text-white/40 mb-6 leading-relaxed">Recibimos tu solicitud. Te contactaremos pronto.</p>
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
                class="btn-accent px-8 py-3 text-sm">
          Seguir explorando
        </button>
      </div>
      @endif

      <div x-show="!orderSent" class="px-5 py-4 flex-shrink-0" style="border-top:1px solid #222;">
        @if(!$isQuoteOnly && $hasOnlinePayment)
        <button @click="submitOrder()" :disabled="orderLoading"
                class="w-full btn-accent py-4 text-sm flex items-center justify-center gap-2 disabled:opacity-50">
          <template x-if="!orderLoading">
            <span class="flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
              </svg>
              Continuar al pago
            </span>
          </template>
          <svg x-show="orderLoading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span x-show="orderLoading">Procesando...</span>
        </button>
        @else
        <button @click="submitOrder()" :disabled="orderLoading"
                class="w-full py-4 text-sm flex items-center justify-center gap-2 disabled:opacity-50 font-black uppercase tracking-wide {{ $isQuoteOnly ? 'bg-[#25D366] text-white' : 'btn-accent' }}">
          <template x-if="!orderLoading">
            @if($isQuoteOnly && $quoteWa)
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
            </svg>
            @else
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
            @endif
          </template>
          <svg x-show="orderLoading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span x-show="!orderLoading">{{ $isQuoteOnly ? ($quoteWa ? 'Enviar cotizaciÃ³n por WhatsApp' : 'Solicitar cotizaciÃ³n') : 'Confirmar pedido' }}</span>
          <span x-show="orderLoading">Enviando...</span>
        </button>
        @endif
      </div>
    </div>

    @if(!$isQuoteOnly)
    {{-- â•â• PASO 3: Pago â•â• --}}
    <div x-show="drawerStep === 3" class="flex flex-col flex-1 overflow-hidden">

      <div x-show="!orderSent" class="flex-1 overflow-y-auto px-4 py-4 space-y-3">

        <div class="px-4 py-3.5" style="background:#1a1a1a;border:1px solid #2a2a2a;">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-xs text-white/30 font-medium">Pedido #<span x-text="orderId"></span></p>
              <p class="text-xs text-white/20 mt-0.5" x-text="form.name"></p>
            </div>
            <div class="text-right">
              <p class="text-xs text-white/30">Total a pagar</p>
              <p class="font-black text-xl price-c" x-text="'S/ ' + orderTotal.toFixed(2)"></p>
            </div>
          </div>
        </div>

        <div x-show="payError" x-cloak class="px-3 py-2.5" style="background:#2a0a0a;border:1px solid #5a1a1a;">
          <p class="text-red-400 text-xs font-medium" x-text="payError"></p>
        </div>

        {{-- MÃ©todos manuales --}}
        @if($payManualEnabled && count($payManualMethods) > 0)
        @php
          $mMeta = [
            'yape'          => ['label'=>'Yape',                'color'=>'#7c3aed','bg'=>'#1a0f2e','border'=>'#3d2060','emoji'=>'ðŸŸ£','hint'=>'Escanea el QR o ingresa el nÃºmero'],
            'plin'          => ['label'=>'Plin',                'color'=>'#0369a1','bg'=>'#0a1a2e','border'=>'#1a3a5a','emoji'=>'ðŸ”µ','hint'=>'Abre Plin y paga al nÃºmero indicado'],
            'transferencia' => ['label'=>'Transferencia bancaria','color'=>'#0891b2','bg'=>'#0a1e22','border'=>'#1a3d44','emoji'=>'ðŸ¦','hint'=>'Transfiere y adjunta el nÃºmero de operaciÃ³n'],
            'qr'            => ['label'=>'Pago con QR',          'color'=>'#059669','bg'=>'#0a1e17','border'=>'#1a3d2e','emoji'=>'ðŸ“²','hint'=>'Escanea con cualquier billetera'],
            'contra_entrega'=> ['label'=>'Contra entrega',       'color'=>'#b45309','bg'=>'#1e1408','border'=>'#3d2a10','emoji'=>'ðŸšš','hint'=>'Paga en efectivo al recibir'],
          ];
        @endphp
        <div>
          <p class="text-[11px] font-bold text-white/20 uppercase tracking-widest mb-2">Transferencia / Billetera</p>
          <div class="space-y-2">
          @foreach($payManualMethods as $mKey)
          @php
            $mm = $mMeta[$mKey] ?? null;
            $mmDetails = match($mKey) {
              'yape' => $payYapeNumber,
              'plin' => $payPlinNumber,
              'transferencia' => $payBankDetails,
              default => '',
            };
          @endphp
          @if($mm)
          <div x-data="{ open: false, copied: false }"
               class="overflow-hidden transition-all duration-200"
               :style="open ? 'border:1px solid {{ $mm['border'] }}' : 'border:1px solid #2a2a2a'">
            <button @click="open = !open; if(open) selectedPayMethod='{{ $mKey }}'"
                    class="w-full flex items-center gap-3 px-4 py-3.5 text-left transition"
                    :style="open ? 'background:{{ $mm['bg'] }}' : 'background:#1a1a1a'">
              @if($mKey === 'yape')
              <div class="w-10 h-10 flex items-center justify-center flex-shrink-0 font-black text-white text-xs"
                   style="background:linear-gradient(135deg,#7c3aed,#4f46e5)">Yape</div>
              @elseif($mKey === 'plin')
              <div class="w-10 h-10 flex items-center justify-center flex-shrink-0 font-black text-white text-xs"
                   style="background:linear-gradient(135deg,#0284c7,#0ea5e9)">Plin</div>
              @elseif($mKey === 'transferencia')
              <div class="w-10 h-10 flex items-center justify-center flex-shrink-0" style="background:#0a1e22">
                <svg class="w-5 h-5" style="color:#0891b2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                </svg>
              </div>
              @else
              <span class="text-2xl leading-none flex-shrink-0">{{ $mm['emoji'] }}</span>
              @endif
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-white">{{ $mm['label'] }}</p>
                <p class="text-xs truncate" style="color:{{ $mm['color'] }}">{{ $mm['hint'] }}</p>
              </div>
              <svg class="w-4 h-4 text-white/30 transition-transform" :class="open ? 'rotate-180' : ''"
                   fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            <div x-show="open" x-transition class="px-4 pb-5 pt-3 space-y-3" style="background:{{ $mm['bg'] }}">
              @if($mmDetails)
              <div class="p-3 space-y-2" style="background:#0a0a0a;border:1px solid {{ $mm['border'] }};">
                <div class="flex items-start justify-between gap-2">
                  <div class="flex-1">
                    <p class="text-[10px] font-bold uppercase tracking-wide mb-1" style="color:{{ $mm['color'] }}">
                      @if($mKey==='yape') NÃºmero Yape
                      @elseif($mKey==='plin') NÃºmero Plin
                      @elseif($mKey==='transferencia') Datos bancarios
                      @else Datos de pago
                      @endif
                    </p>
                    <p class="text-sm font-bold text-white whitespace-pre-line">{{ $mmDetails }}</p>
                  </div>
                  @if(in_array($mKey, ['yape','plin']))
                  <button @click="navigator.clipboard.writeText('{{ addslashes($mmDetails) }}').then(()=>{copied=true;setTimeout(()=>copied=false,2000)})"
                          class="flex-shrink-0 px-3 py-1.5 text-xs font-semibold transition"
                          :style="copied ? 'background:#052e16;color:#4ade80' : 'background:{{ $mm['border'] }};color:{{ $mm['color'] }}'">
                    <span x-show="!copied">Copiar</span>
                    <span x-show="copied" x-cloak>Copiado</span>
                  </button>
                  @endif
                </div>
                @if(in_array($mKey, ['yape','plin']) && $mmDetails)
                @php $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode($mmDetails); @endphp
                <div class="flex justify-center pt-2">
                  <div class="p-2" style="background:#0a0a0a;border:1px solid {{ $mm['border'] }};">
                    <img src="{{ $qrUrl }}" alt="QR {{ $mm['label'] }}" width="120" height="120"
                         onerror="this.style.display='none'">
                    <p class="text-center text-[9px] mt-1" style="color:{{ $mm['color'] }}">Escanea con tu app</p>
                  </div>
                </div>
                @endif
              </div>
              @endif

              @if($payManualInstr)
              <div class="flex items-start gap-2 text-xs text-white/40 px-3 py-2" style="background:#0a0a0a;">
                <p>{{ $payManualInstr }}</p>
              </div>
              @endif

              <div>
                <label class="text-xs font-bold block mb-1.5" style="color:{{ $mm['color'] }}">
                  @if($mKey==='contra_entrega') Confirma tu direcciÃ³n
                  @else NÃºmero de operaciÃ³n / cÃ³digo *
                  @endif
                </label>
                <input x-model="payReference" type="text"
                       placeholder="{{ $mKey==='contra_entrega' ? 'Ingresa tu direcciÃ³n de entrega' : 'Ej: 123456789' }}"
                       class="w-full px-4 py-2.5 text-sm text-white outline-none font-mono"
                       style="background:#0a0a0a;border:1px solid {{ $mm['border'] }};">
              </div>

              <button @click="confirmManualPay()" :disabled="payLoading || !payReference.trim()"
                      class="w-full py-3.5 font-black text-sm text-white transition disabled:opacity-40 flex items-center justify-center gap-2"
                      style="background:{{ $mm['color'] }}">
                <svg x-show="payLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <svg x-show="!payLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span x-show="!payLoading">
                  {{ $mKey==='contra_entrega' ? 'Confirmar pedido' : 'Ya paguÃ© Â· confirmar' }}
                </span>
                <span x-show="payLoading" x-cloak>Procesando...</span>
              </button>
            </div>
          </div>
          @endif
          @endforeach
          </div>
        </div>
        @endif

        {{-- Culqi --}}
        @if($culqiEnabled && $culqiPublicKey)
        <div>
          <p class="text-[11px] font-bold text-white/20 uppercase tracking-widest mb-2">Tarjeta / Culqi</p>
          <button @click="openCulqi()" :disabled="payLoading"
                  class="w-full flex items-center gap-3 px-4 py-3.5 text-left transition disabled:opacity-50"
                  style="background:#1a1a1a;border:1px solid #2a2a2a;">
            <div class="w-10 h-10 flex items-center justify-center flex-shrink-0"
                 style="background:linear-gradient(135deg,#4f46e5,#7c3aed)">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-white">Tarjeta crÃ©dito / dÃ©bito</p>
              <div class="flex items-center gap-2 mt-0.5">
                <span class="text-[10px] px-1.5 py-0.5 font-semibold text-white" style="background:#1a3a6a;">Visa</span>
                <span class="text-[10px] px-1.5 py-0.5 font-semibold text-white" style="background:#5a2a0a;">Mastercard</span>
                <span class="text-[10px] px-1.5 py-0.5 font-semibold text-white" style="background:#3d1a6a;">Yape</span>
              </div>
            </div>
            <svg x-show="!payLoading" class="w-5 h-5 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <svg x-show="payLoading" class="w-5 h-5 animate-spin text-indigo-400" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
          </button>
          <p class="text-center text-[10px] text-white/20 mt-1.5">Powered by Culqi Â· {{ $culqiMode === 'live' ? 'ProducciÃ³n' : 'Modo pruebas' }}</p>
        </div>
        <script src="https://checkout.culqi.com/js/v4"></script>
        @endif

        {{-- Mercado Pago --}}
        @if($mpEnabled)
        <div>
          <p class="text-[11px] font-bold text-white/20 uppercase tracking-widest mb-2">Mercado Pago</p>
          <button @click="openMercadoPago()" :disabled="payLoading"
                  class="w-full flex items-center gap-3 px-4 py-3.5 text-left transition disabled:opacity-50"
                  style="background:#1a1a1a;border:1px solid #2a2a2a;">
            <div class="w-10 h-10 flex items-center justify-center flex-shrink-0"
                 style="background:linear-gradient(135deg,#009ee3,#00b1ea)">
              <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/>
              </svg>
            </div>
            <div class="flex-1">
              <p class="text-sm font-bold text-white">Mercado Pago</p>
              <div class="flex flex-wrap items-center gap-1 mt-0.5">
                <span class="text-[10px] px-1.5 py-0.5 font-semibold text-white" style="background:#1a3a6a;">Tarjetas</span>
                <span class="text-[10px] px-1.5 py-0.5 font-semibold text-white" style="background:#1a3a1a;">Yape</span>
                <span class="text-[10px] px-1.5 py-0.5 font-semibold text-white" style="background:#3a2a0a;">Cuotas</span>
              </div>
            </div>
            <svg x-show="!payLoading" class="w-5 h-5 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            <svg x-show="payLoading" class="w-5 h-5 animate-spin text-blue-400" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
          </button>
          <p class="text-center text-[10px] text-white/20 mt-1.5">SerÃ¡s redirigido al checkout de Mercado Pago</p>
        </div>
        @endif

        @if(!$culqiEnabled && !$mpEnabled && !$payManualEnabled)
        <div class="flex flex-col items-center justify-center py-8 text-center space-y-4">
          <div class="w-16 h-16 btn-accent flex items-center justify-center">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <div>
            <p class="font-black text-white text-lg uppercase">Pedido recibido</p>
            <p class="text-sm text-white/40 mt-1 leading-relaxed">Nos pondremos en contacto para coordinar el pago.</p>
          </div>
          @if($quoteWa)
          <a href="https://wa.me/{{ $quoteWa }}?text={{ urlencode('Hola, acabo de hacer un pedido y quiero coordinar el pago.') }}"
             target="_blank" rel="noopener"
             class="flex items-center gap-2 bg-[#25D366] text-white px-6 py-3 text-sm font-bold">
            Coordinar por WhatsApp
          </a>
          @endif
          <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
                  class="text-sm text-white/30 hover:text-white underline">
            Seguir comprando
          </button>
        </div>
        @endif

      </div>

      {{-- Ã‰xito paso 3 --}}
      <div x-show="orderSent" class="flex-1 flex flex-col items-center justify-center px-5 py-8 text-center">
        <div class="w-24 h-24 btn-accent flex items-center justify-center mb-5">
          <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <p class="font-black text-white text-2xl mb-1 uppercase">Listo</p>
        <p class="font-semibold text-white/50 mb-1">Pedido #<span x-text="orderId"></span> registrado</p>
        <p class="text-sm text-white/30 mb-4 leading-relaxed">Tu pago fue registrado exitosamente.</p>
        <div class="px-6 py-3 my-3 w-full" style="background:#1a1a1a;border:1px dashed #333;">
          <p class="text-xs text-white/30 mb-0.5">Referencia de pago</p>
          <p class="font-mono font-bold text-white text-lg" x-text="'#' + orderId + '-' + (payReference || 'OK')"></p>
        </div>
        @if($project->whatsapp)
        <a href="https://wa.me/{{ preg_replace('/\D/', '', $project->whatsapp) }}?text={{ urlencode('Hola, acabo de hacer un pedido #') }}' + orderId + '{{ urlencode(' en ' . $project->name) }}"
           target="_blank"
           class="w-full mt-2 py-3 font-bold text-sm text-white flex items-center justify-center gap-2 mb-3"
           style="background:#25D366">
          Confirmar por WhatsApp
        </a>
        @endif
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};payReference='';drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
                class="w-full btn-accent py-3 text-sm font-black">
          Seguir comprando
        </button>
      </div>

    </div>
    @endif

  </div>
</div>

{{-- Quick View Modal --}}
<div x-show="qvOpen" x-cloak @keydown.escape.window="qvOpen=false"
     class="fixed inset-0 z-[60] flex items-center justify-center p-4">
  <div @click="qvOpen=false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
  <div x-show="qvOpen && qv"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0 scale-95"
       x-transition:enter-end="opacity-100 scale-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100 scale-100"
       x-transition:leave-end="opacity-0 scale-95"
       class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden z-10">
    <button @click="qvOpen=false" class="absolute top-3 right-3 z-20 w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition">
      <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
    <div class="flex flex-col sm:flex-row" x-show="qv">
      <div class="sm:w-48 aspect-square flex-shrink-0 bg-gray-50 overflow-hidden">
        <img x-show="qv&&qv.img" :src="qv&&qv.img" :alt="qv&&qv.name" class="w-full h-full object-cover">
        <div x-show="qv&&!qv.img" class="w-full h-full flex items-center justify-center">
          <svg class="w-12 h-12 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </div>
      </div>
      <div class="flex-1 p-5 flex flex-col">
        <p class="text-lg font-black text-gray-900 leading-snug mb-1" x-text="qv&&qv.name"></p>
        <div class="flex items-baseline gap-2 mb-2">
          <span class="text-xl font-black" style="color:var(--c)" x-text="qv&&('{{ $currency }} '+qv.price.toFixed(2))"></span>
          <span x-show="qv&&qv.cp&&qv.cp>qv.price" class="text-sm text-gray-400 line-through" x-text="qv&&qv.cp&&('{{ $currency }} '+qv.cp.toFixed(2))"></span>
        </div>
        <p x-show="qv&&qv.desc" class="text-sm text-gray-500 leading-relaxed mb-4 flex-1" x-text="qv&&qv.desc"></p>
        <p x-show="qv&&qv.stock===0" class="text-xs font-bold text-red-500 mb-3">Agotado</p>
        <div class="flex flex-col gap-2 mt-auto">
          <button @click="addToCart({id:qv.id,name:qv.name,price:qv.price,img:qv.img});qvOpen=false"
                  x-show="qv&&qv.stock!==0"
                  class="w-full btn-accent py-2.5 rounded-xl text-sm font-black flex items-center justify-center gap-2 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Agregar al carrito
          </button>
          <a :href="qv&&qv.url" class="w-full text-center py-2 rounded-xl text-sm font-semibold border-2 transition hover:bg-gray-50"
             style="border-color:var(--c);color:var(--c)">
            Ver producto completo
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     ALPINE STORE
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<script>
function store() {
  const _cartKey = 'avan_cart_{{ $project->id }}';
  const _formKey = 'avan_form_{{ $project->id }}';
  let _savedCart = [];
  let _savedForm = { name: '', phone: '', email: '', notes: '', address: '' };
  try {
    const c = localStorage.getItem(_cartKey);
    if (c) _savedCart = JSON.parse(c);
    const f = localStorage.getItem(_formKey);
    if (f) _savedForm = { ...{ name:'', phone:'', email:'', notes:'', address:'' }, ...JSON.parse(f) };
  } catch(e) {}

  return {
    _cartKey,
    _formKey,
    search: '',
    filterCat: '',
    filterParent: '',
    priceFilter: '',
    onSaleFilter: false, sortBy: 'default',
    filterOpen: false,
    priceMin: 0,
    priceMax: 0,
    qv: null,
    qvOpen: false,
    expandedCats: {},
    recentlyViewed: [],
    // Predictive search
    searchIndex: @json($searchIndex),
    searchOpen: false,
    searchIdx: -1,
    searchFocus: false,
    get visibleCount() {
        const s = this.search ? this.search.toLowerCase() : '';
        return this.searchIndex.filter(p => {
            const nm = s === '' || p.name.toLowerCase().includes(s);
            const cm = !this.filterCat || p.catId === this.filterCat;
            let pm = true;
            if (this.priceFilter === '0-50')    pm = p.price <= 50;
            if (this.priceFilter === '50-150')  pm = p.price > 50 && p.price <= 150;
            if (this.priceFilter === '150-500') pm = p.price > 150 && p.price <= 500;
            if (this.priceFilter === '500+')    pm = p.price > 500;
            if (this.priceFilter === 'custom') {
                const lo = this.priceMin > 0 ? this.priceMin : 0;
                const hi = this.priceMax > 0 ? this.priceMax : Infinity;
                pm = p.price >= lo && p.price <= hi;
            }
            const sm = !this.onSaleFilter || (p.cp && p.cp > p.price);
            return nm && cm && pm && sm;
        }).length;
    },
    get suggestions() {
        if (!this.search || this.search.trim().length < 2) return [];
        const q = this.search.toLowerCase().trim();
        return this.searchIndex
            .filter(p => p.name.toLowerCase().includes(q) || (p.cat && p.cat.toLowerCase().includes(q)))
            .slice(0, 6);
    },
    selectSuggestion(p) {
        window.location.href = p.url;
    },
    _highlight(text) {
        if (!this.search || this.search.trim().length < 2) return text;
        const q = this.search.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return text.replace(new RegExp('(' + q + ')', 'gi'), '<strong style="color:var(--c,#4f46e5)">$1</strong>');
    },
    _scrollToCatalog() {
        const el = document.getElementById('catalogo');
        if (!el) return;
        if (el.getBoundingClientRect().top > 100) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },
    drawerOpen: false,
    drawerStep: 1,
    toastShow: false, toastMsg: '', toastTimer: null,
    cart: _savedCart,
    form: _savedForm,
    orderLoading: false,
    orderSent: false,
    orderError: '',
    noResults: false,
    orderId: null,
    orderTotal: 0,
    shippingEnabled:  {{ $shippingEnabled  ? 'true' : 'false' }},
    shippingCost:     {{ $shippingCost }},
    shippingFreeFrom: {{ $shippingFreeFrom }},
    requireAddress:   {{ $requireAddress   ? 'true' : 'false' }},
    get subtotal() { return this.cart.reduce((s,i) => s + i.price * i.qty, 0); },
    get effectiveShipping() {
      if (!this.shippingEnabled) return 0;
      if (this.shippingFreeFrom > 0 && this.subtotal >= this.shippingFreeFrom) return 0;
      return this.shippingCost;
    },
    couponCode: '',
    couponApplied: null,
    couponError: '',
    couponLoading: false,
    get couponDiscount() {
      if (!this.couponApplied) return 0;
      const sub = this.subtotal;
      if (sub < (this.couponApplied.min_order || 0)) return 0;
      if (this.couponApplied.type === 'percent') return Math.min(sub * this.couponApplied.value / 100, sub);
      return Math.min(this.couponApplied.value, sub);
    },
    async applyCoupon() {
      if (!this.couponCode.trim()) return;
      this.couponLoading = true; this.couponError = '';
      const res = await fetch('/{{ $project->slug }}/coupon', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify({ code: this.couponCode, subtotal: this.subtotal })
      });
      const d = await res.json();
      this.couponLoading = false;
      if (d.ok) { this.couponApplied = d; this.couponError = ''; }
      else { this.couponError = d.message; this.couponApplied = null; }
    },
    removeCoupon() { this.couponApplied = null; this.couponCode = ''; this.couponError = ''; },
    get orderGrandTotal() {
      return Math.max(0, this.subtotal - this.couponDiscount + this.effectiveShipping);
    },
    selectedPayMethod: '',
    payReference: '',
    payLoading: false,
    payError: '',

    init() {
      try {
          const _rv = JSON.parse(localStorage.getItem('rv_{{ $project->slug }}') || '[]');
          this.recentlyViewed = _rv.filter(x => x && x.id);
      } catch(e) {}
      this.$watch('cart', val => {
        try { localStorage.setItem(this._cartKey, JSON.stringify(val)); } catch(e) {}
      });
      this.$watch('form', val => {
        try { localStorage.setItem(this._formKey, JSON.stringify(val)); } catch(e) {}
      }, { deep: true });
      this.$watch('search',      () => this.$nextTick(() => this.checkNoResults()));
      this.$watch('filterCat',   () => this.$nextTick(() => this.checkNoResults()));
      this.$watch('priceFilter', () => this.$nextTick(() => this.checkNoResults()));
      this.$watch('onSaleFilter',() => this.$nextTick(() => this.checkNoResults()));
      this.$watch('sortBy', () => { this.applySort(); this._syncUrl(); });
      // Restore filters from URL
      const _p = new URLSearchParams(window.location.search);
      if (_p.get('q'))     this.search       = _p.get('q');
      if (_p.get('cat'))   this.filterCat    = _p.get('cat');
      if (_p.get('price')) this.priceFilter  = _p.get('price');
      if (_p.get('sale'))  this.onSaleFilter = _p.get('sale') === '1';
      if (_p.get('sort'))  { this.sortBy = _p.get('sort'); this.$nextTick(() => this.applySort()); }
      // Sync URL when filters change
      this.$watch('search',       () => this._syncUrl());
      this.$watch('filterCat',    () => this._syncUrl());
      this.$watch('priceFilter',  () => this._syncUrl());
      this.$watch('onSaleFilter', () => this._syncUrl());

      const urlParams = new URLSearchParams(window.location.search);
      const payStatus  = urlParams.get('payment');
      const payOrderId = urlParams.get('order');
      if (payStatus && payOrderId) {
        this.orderId    = parseInt(payOrderId) || 0;
        this.orderTotal = 0;
        if (payStatus === 'success' || payStatus === 'approved') {
          this.orderSent    = true;
          this.drawerOpen   = true;
          this.drawerStep   = 3;
          this.payReference = urlParams.get('payment_id') || 'mp-ok';
          try { localStorage.removeItem(this._cartKey); localStorage.removeItem(this._formKey); } catch(e) {}
        } else if (payStatus === 'failure') {
          this.payError   = 'El pago fue rechazado en Mercado Pago. Intenta de nuevo.';
          this.drawerOpen = true;
          this.drawerStep = 3;
        } else if (payStatus === 'pending') {
          this.orderSent    = true;
          this.drawerOpen   = true;
          this.drawerStep   = 3;
          this.payReference = 'pendiente-mp';
        }
        window.history.replaceState({}, document.title, window.location.pathname);
      }
    },

    applySort() {
      const grids = this.$el.querySelectorAll('[data-products-grid]');
      grids.forEach(grid => {
        const cards = Array.from(grid.querySelectorAll('[data-price]'));
        cards.sort((a, b) => {
          if (this.sortBy === 'price_asc')  return (parseFloat(a.dataset.price)||0) - (parseFloat(b.dataset.price)||0);
          if (this.sortBy === 'price_desc') return (parseFloat(b.dataset.price)||0) - (parseFloat(a.dataset.price)||0);
          if (this.sortBy === 'newest')     return (parseInt(b.dataset.ts)||0)  - (parseInt(a.dataset.ts)||0);
          if (this.sortBy === 'name_az')    return (a.dataset.name||'').localeCompare(b.dataset.name||'', 'es');
          return (parseInt(a.dataset.idx)||0) - (parseInt(b.dataset.idx)||0);
        });
        cards.forEach(c => grid.appendChild(c));
      });
    },
    _syncUrl() {
      const p = new URLSearchParams();
      if (this.search)        p.set('q',     this.search);
      if (this.filterCat)     p.set('cat',   this.filterCat);
      if (this.priceFilter)   p.set('price', this.priceFilter);
      if (this.onSaleFilter)  p.set('sale',  '1');
      if (this.sortBy && this.sortBy !== 'default') p.set('sort', this.sortBy);
      history.replaceState(null, '', p.toString() ? '?' + p.toString() : window.location.pathname);
    },
    checkNoResults() {
      const hasFilter = this.search !== '' || this.filterCat !== '' || this.priceFilter !== '' || this.onSaleFilter;
      if (!hasFilter) { this.noResults = false; return; }
      const articles = document.querySelectorAll('#catalogo article');
      const visible  = Array.from(articles).filter(el => el.style.display !== 'none');
      this.noResults = visible.length === 0;
    },

    matchProduct(name, price, comparePrice) {
      const matchSearch = this.search === '' || name.includes(this.search.toLowerCase());
      let matchPrice = true;
      if (this.priceFilter === '0-50')    matchPrice = price <= 50;
      if (this.priceFilter === '50-150')  matchPrice = price > 50 && price <= 150;
      if (this.priceFilter === '150-500') matchPrice = price > 150 && price <= 500;
      if (this.priceFilter === '500+')    matchPrice = price > 500;
      const matchSale = !this.onSaleFilter || (comparePrice && comparePrice > price);
      return matchSearch && matchPrice && matchSale;
    },

    addToCart(product) {
      const existing = this.cart.find(i => i.id === product.id);
      if (existing) {
        existing.qty++;
      } else {
        this.cart.push({ ...product, qty: 1 });
      }
      this.toastMsg = 'âœ“ ' + product.name + ' agregado';
      this.toastShow = true;
      clearTimeout(this.toastTimer);
      this.toastTimer = setTimeout(() => { this.toastShow = false; }, 2000);
    },

    sendQuoteWhatsapp() {
      if (!this.form.name.trim()) {
        this.orderError = 'Por favor ingresa tu nombre primero.';
        return;
      }
      const businessName = `{{ addslashes($project->name) }}`;
      const customMsg    = `{{ addslashes($quoteWaMsg) }}`;
      const now = new Date();
      const fecha = now.toLocaleDateString('es-PE', { day:'2-digit', month:'long', year:'numeric' });

      let lines = '';
      lines += `ðŸ›’ *SOLICITUD DE COTIZACIÃ“N*\n`;
      lines += `â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”\n`;
      lines += `ðŸª *${businessName}*\n\n`;
      lines += `${customMsg}\n\n`;
      lines += `ðŸ‘¤ *DATOS DE CONTACTO*\n`;
      lines += `â€¢ Nombre: ${this.form.name}\n`;
      if (this.form.phone) lines += `â€¢ TelÃ©fono: ${this.form.phone}\n`;
      lines += `\nðŸ“¦ *PRODUCTOS SOLICITADOS*\n`;
      lines += `â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”\n`;
      let total = 0;
      this.cart.forEach((item, idx) => {
        @if(!$isQuoteOnly || $quotePriceDisp === 'show')
        const subtotal = (item.price * item.qty).toFixed(2);
        lines += `${idx+1}. *${item.name}*\n   Cant: ${item.qty}  â€¢  S/ ${subtotal}\n`;
        total += item.price * item.qty;
        @else
        lines += `${idx+1}. *${item.name}* â€” cant: ${item.qty}\n`;
        @endif
      });
      @if(!$isQuoteOnly || $quotePriceDisp === 'show')
      lines += `â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”\n`;
      lines += `ðŸ’° *Total referencial: S/ ${total.toFixed(2)}*\n`;
      @endif
      if (this.form.notes) lines += `\nðŸ“ *Nota:* ${this.form.notes}\n`;
      lines += `\nðŸ“… Fecha: ${fecha}\n`;
      lines += `\n_CotizaciÃ³n generada desde el catÃ¡logo online de ${businessName}_`;
      const url = `https://wa.me/{{ $quoteWa }}?text=${encodeURIComponent(lines)}`;
      window.open(url, '_blank');
      this.cart = [];
      this.orderSent = true;
      try {
        localStorage.removeItem(this._cartKey);
        localStorage.removeItem(this._formKey);
      } catch(e) {}
    },

    async submitOrder() {
      if (!this.form.name.trim() || !this.form.phone.trim()) {
        this.orderError = 'Por favor ingresa tu nombre y telÃ©fono.';
        return;
      }
      this.orderLoading = true;
      this.orderError = '';
      const items = this.cart.map(i => ({
        product_id: i.id,
        name:       i.name,
        price:      i.price,
        quantity:   i.qty,
      }));
      try {
        const res = await fetch('{{ route("public.order", $project->slug) }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({
            client_name:  this.form.name,
            client_phone: this.form.phone,
            client_email: this.form.email,
            notes:        this.form.notes,
            coupon_code:  this.couponApplied?this.couponApplied.code:null,
            delivery_address: this.form.address||null,
            shipping_cost: this.effectiveShipping>0?this.effectiveShipping:null,
            items:        items,
          })
        });
        const data = await res.json();
        if (data.ok) {
          @if($isQuoteOnly && $quoteWa)
          this.sendQuoteWhatsapp();
          @elseif(!$isQuoteOnly)
          this.orderId      = data.order_id;
          this.orderTotal   = data.total;
          this.orderSent    = false;
          this.payReference = '';
          this.payError     = '';
          this.drawerStep   = 3;
          @endif
        } else {
          this.orderError = 'No se pudo enviar. IntÃ©ntalo de nuevo.';
        }
      } catch(e) {
        this.orderError = 'Error de conexiÃ³n. Verifica tu internet e intÃ©ntalo de nuevo.';
      }
      this.orderLoading = false;
    },

    async confirmManualPay() {
      if (!this.payReference.trim()) return;
      this.payLoading = true;
      this.payError   = '';
      try {
        const res = await fetch(`{{ url('/' . $project->slug . '/pay') }}/${this.orderId}/manual`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
          body: JSON.stringify({ reference: this.payReference }),
        });
        const data = await res.json();
        if (data.ok) {
          try { localStorage.removeItem(this._cartKey); localStorage.removeItem(this._formKey); } catch(e) {}
          window.location.href = '/{{ $project->slug }}/thanks/' + this.orderId;
        } else {
          this.payError = 'No se pudo confirmar el pago. IntÃ©ntalo de nuevo.';
        }
      } catch(e) { this.payError = 'Error de conexiÃ³n.'; }
      this.payLoading = false;
    },

    @if($culqiEnabled && $culqiPublicKey)
    openCulqi() {
      const self = this;
      const email = self.form.email || 'cliente@ejemplo.com';
      Culqi.publicKey = '{{ $culqiPublicKey }}';
      Culqi.settings({
        title:       '{{ addslashes($project->name) }}',
        currency:    'PEN',
        description: 'Pedido #' + self.orderId,
        amount:      Math.round(self.orderTotal * 100),
        email:       email,
      });
      Culqi.options({
        style: {
          logo:       '{{ $project->logo_url ? asset("storage/".$project->logo_url) : "" }}',
          maincolor:  '{{ $primaryColor }}',
          buttontext: 'Pagar S/ ' + self.orderTotal.toFixed(2),
          maintext:   '{{ addslashes($project->name) }}',
          desctext:   'Pago seguro Â· Pedido #' + self.orderId,
        },
        paymentMethods: {
          tarjeta:   true,
          yape:      true,
          billetera: false,
          cuotealo:  false,
        }
      });
      Culqi.open();
      window.culqi = async function() {
        if (Culqi.token) {
          self.payLoading = true;
          self.payError   = '';
          try {
            const res = await fetch(`{{ url('/' . $project->slug . '/pay') }}/${self.orderId}/culqi`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
              body: JSON.stringify({ token: Culqi.token.id, email }),
            });
            const data = await res.json();
            if (data.ok) {
              self.orderSent = true;
              self.payReference = data.charge_id || 'culqi-ok';
              try { localStorage.removeItem(self._cartKey); localStorage.removeItem(self._formKey); } catch(e) {}
            } else {
              self.payError = data.message || 'Tarjeta rechazada. Intenta con otra.';
            }
          } catch(e) { self.payError = 'Error de conexiÃ³n. IntÃ©ntalo de nuevo.'; }
          self.payLoading = false;
          Culqi.close();
        } else if (Culqi.error) {
          self.payError = Culqi.error.user_message || 'Error en el pago.';
          self.payLoading = false;
        }
      };
    },
    @endif

    @if($mpEnabled)
    async openMercadoPago() {
      this.payLoading = true;
      this.payError   = '';
      try {
        const res = await fetch(`{{ url('/' . $project->slug . '/pay') }}/${this.orderId}/mp`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
          body: JSON.stringify({ email: this.form.email || null }),
        });
        const data = await res.json();
        if (data.ok) {
          const isSandbox = data.sandbox_init_point && !data.init_point.includes('mercadopago.com/checkout/v1');
          const url = (isSandbox && data.sandbox_init_point) ? data.sandbox_init_point : data.init_point;
          window.location.href = url;
        } else {
          this.payError = data.message || 'Error al iniciar Mercado Pago.';
          this.payLoading = false;
        }
      } catch(e) {
        this.payError = 'Error de conexiÃ³n con Mercado Pago.';
        this.payLoading = false;
      }
    },
    @endif
  };
}
</script>

{{-- FLOATING BOTTOM BAR --}}
<div x-show="cart.length > 0" x-cloak
     class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 shadow-[0_-4px_20px_rgba(0,0,0,0.1)] px-4 py-3 flex items-center gap-4">
  <div class="flex items-center gap-2.5 flex-1 min-w-0">
    <span class="text-white text-xs font-black w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0"
          style="background:var(--c)"
          x-text="cart.reduce((s,i)=>s+i.qty,0)"></span>
    <div class="min-w-0">
      <p class="text-[10px] text-gray-400 leading-none mb-0.5">Total del pedido</p>
      <p class="font-black text-base leading-none" style="color:var(--c)"
         x-text="'{{ $currency }} '+cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></p>
    </div>
  </div>
  <button @click="drawerOpen=true; drawerStep=1"
          class="px-5 py-3 rounded-xl font-black text-sm flex items-center gap-2 flex-shrink-0 text-white"
          style="background:var(--c)">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
    </svg>
    {{ $isQuoteOnly ? 'Ver cotizaciÃ³n' : 'Ver pedido' }}
  </button>
</div>
</body>
</html>


