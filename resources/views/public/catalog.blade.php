<!DOCTYPE html>
<html lang="es" prefix="og: https://ogp.me/ns#">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

{{-- ═══════════════════════════════════════════
     SEO — META TAGS PRIMARIOS
═══════════════════════════════════════════ --}}
@php
  // ─── Config de pagos ───────────────────────────────────────────────────────
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
  // ──────────────────────────────────────────────────────────────────────────
@endphp
@php
  $seoTitle       = ($settings['seo_title']       ?? null) ?: ($project->name . ' — Catálogo Online');
  $seoDesc        = ($settings['seo_description'] ?? null) ?: ($project->description ?? 'Explora nuestros productos y haz tu pedido en línea.');
  $seoKeywords    = ($settings['seo_keywords']    ?? null) ?: ($project->name . ', catálogo, productos, comprar online');
  $canonicalUrl   = url('/' . $project->slug);
  $ogImage        = $project->logo_url ? asset('storage/'.$project->logo_url) : asset('img/og-default.png');
  $primaryColor   = $settings['primary_color'] ?? '#4f46e5';
  $storeMode        = $settings['store_mode']           ?? 'direct';   // 'direct' | 'quote_only'
  $quotePriceDisp   = $settings['quote_price_display']  ?? 'show';     // 'show' | 'hide'
  $wholesaleEnabled = ($settings['wholesale_enabled'] ?? '0') === '1';
  // Número WA: si hay quote_whatsapp en settings úsalo, si no, whatsapp del proyecto
  $quoteWaRaw = preg_replace('/\D/', '', $settings['quote_whatsapp'] ?? '');
  if (!$quoteWaRaw) {
      $quoteWaRaw = preg_replace('/\D/', '', $project->whatsapp ?? '');
  }
  // Código de país guardado por separado (default 51 = Perú)
  $quoteWaCountry = $settings['quote_whatsapp_country'] ?? '51';
  // Si el número ya empieza con el código de país, úsalo tal cual; si no, anteponlo
  $quoteWa = '';
  if ($quoteWaRaw && ($settings['show_wa_button'] ?? '1') === '1') {
      $quoteWa = str_starts_with($quoteWaRaw, $quoteWaCountry)
          ? $quoteWaRaw
          : $quoteWaCountry . $quoteWaRaw;
  }
  $quoteWaMsg     = $settings['quote_wa_msg'] ?? 'Hola, me interesa cotizar los siguientes productos:';
  $isQuoteOnly       = $storeMode === 'quote_only';
  // Envío
  $shippingEnabled  = ($settings['shipping_enabled']  ?? '0') === '1';
  $shippingCost     = (float)($settings['shipping_cost']      ?? 0);
  $shippingFreeFrom = (float)($settings['shipping_free_from'] ?? 0);
  $requireAddress   = ($settings['require_address']   ?? '0') === '1';
  $acceptedPayments  = json_decode($settings['accepted_payments'] ?? '[]', true) ?? [];
  $paymentMeta = [
      'efectivo'      => ['label'=>'Efectivo',              'emoji'=>'💵', 'color'=>'#16a34a'],
      'yape'          => ['label'=>'Yape',                  'emoji'=>'🟣', 'color'=>'#7c3aed'],
      'plin'          => ['label'=>'Plin',                  'emoji'=>'🔵', 'color'=>'#0284c7'],
      'transferencia' => ['label'=>'Transferencia',         'emoji'=>'🏦', 'color'=>'#0891b2'],
      'tarjeta'       => ['label'=>'Tarjeta crédito/débito','emoji'=>'💳', 'color'=>'#64748b'],
      'qr'            => ['label'=>'Pago QR',               'emoji'=>'📲', 'color'=>'#059669'],
      'contra_entrega'=> ['label'=>'Contra entrega',        'emoji'=>'🚚', 'color'=>'#b45309'],
  ];
@endphp

<title>{{ $seoTitle }}</title>
<meta name="description"  content="{{ $seoDesc }}">
<meta name="keywords"     content="{{ $seoKeywords }}">
<meta name="robots"       content="index, follow">
<link rel="canonical"     href="{{ $canonicalUrl }}">

{{-- Open Graph --}}
<meta property="og:type"        content="website">
<meta property="og:url"         content="{{ $canonicalUrl }}">
<meta property="og:title"       content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDesc }}">
<meta property="og:image"       content="{{ $ogImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale"      content="es_PE">
<meta property="og:site_name"   content="{{ $project->name }}">

{{-- Twitter Card --}}
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDesc }}">
<meta name="twitter:image"       content="{{ $ogImage }}">

{{-- Geo / Local SEO --}}
@if($project->address)
<meta name="geo.placename" content="{{ $project->address }}">
@endif

{{-- JSON-LD — LocalBusiness Schema --}}
@php
  $ldBusiness = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Store',
    'name'        => $project->name,
    'description' => $seoDesc,
    'url'         => $canonicalUrl,
    'logo'        => $ogImage,
    'image'       => $ogImage,
  ];
  if ($project->phone)    $ldBusiness['telephone'] = $project->phone;
  if ($project->address)  $ldBusiness['address'] = ['@type'=>'PostalAddress','streetAddress'=>$project->address];
  if ($project->whatsapp) $ldBusiness['contactPoint'] = ['@type'=>'ContactPoint','telephone'=>$project->whatsapp,'contactType'=>'customer service','availableLanguage'=>'Spanish'];
  $ldSameAs = array_values(array_filter([
    $settings['facebook_url']  ?? null,
    $settings['instagram_url'] ?? null,
    $settings['tiktok_url']    ?? null,
    $settings['youtube_url']   ?? null,
    $settings['twitter_url']   ?? null,
    $settings['linkedin_url']  ?? null,
  ]));
  if ($ldSameAs) $ldBusiness['sameAs'] = $ldSameAs;
@endphp
<script type="application/ld+json">{!! json_encode($ldBusiness, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>

{{-- JSON-LD — ItemList de productos --}}
@if($categories->count())
@php
  $ldItems = [];
  $ldIdx   = 1;
  foreach ($categories as $ldCat) {
    foreach ($ldCat->products as $ldProd) {
      $ldItem = [
        '@type'    => 'ListItem',
        'position' => $ldIdx++,
        'item'     => [
          '@type'       => 'Product',
          'name'        => $ldProd->name,
          'description' => Str::limit($ldProd->description ?? '', 160),
          'sku'         => $ldProd->sku ?? (string)$ldProd->id,
          'offers'      => [
            '@type'        => 'Offer',
            'priceCurrency'=> 'PEN',
            'price'        => (string)$ldProd->price,
            'availability' => 'https://schema.org/InStock',
            'url'          => $canonicalUrl . '#producto-' . $ldProd->id,
          ],
        ],
      ];
      if ($ldProd->mainImage) {
        $ldItem['item']['image'] = asset('storage/'.$ldProd->mainImage->url);
      }
      $ldItems[] = $ldItem;
    }
  }
  $ldList = [
    '@context'       => 'https://schema.org',
    '@type'          => 'ItemList',
    'name'           => 'Catálogo de ' . $project->name,
    'url'            => $canonicalUrl,
    'numberOfItems'  => count($ldItems),
    'itemListElement'=> $ldItems,
  ];
@endphp
<script type="application/ld+json">{!! json_encode($ldList, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endif

@php
  $secondaryColor  = $settings['secondary_color']  ?? '#6b7280';
  $fontTitle       = $settings['font_title']        ?? $settings['font'] ?? 'Inter';
  $fontBody        = $settings['font_body']         ?? $settings['font'] ?? 'Inter';
  $borderRadiusMap = ['sharp'=>'0px','rounded'=>'8px','pill'=>'50px'];
  $borderRadius    = $borderRadiusMap[$settings['border_radius'] ?? 'rounded'] ?? '8px';
  $currencySymbol  = $settings['currency_symbol']   ?? 'S/';
  $heroImage       = $settings['hero_image']        ?? '';
  $heroOverlay     = (int)($settings['hero_overlay'] ?? '50');
  $heroAlign       = $settings['hero_align']        ?? 'center';
  $heroHeight      = $settings['hero_height']       ?? 'medium';
  $heroCta1Show    = ($settings['hero_cta1_show']   ?? '1') === '1';
  $heroCta1Text    = $settings['hero_cta1_text']    ?? 'Ver catálogo';
  $heroCta2Show    = ($settings['hero_cta2_show']   ?? '0') === '1';
  $heroCta2Text    = $settings['hero_cta2_text']    ?? 'Contáctanos';
  $catalogTitle    = $settings['catalog_section_title'] ?? 'Nuestros productos';
  $catalogBadgeSale = $settings['catalog_badge_sale'] ?? 'OFERTA';
  $catalogBadgeNew  = $settings['catalog_badge_new']  ?? 'NUEVO';
  $btnCartText     = $settings['btn_cart_text']     ?? 'Agregar al carrito';
  $btnQuoteText    = $settings['btn_quote_text']    ?? 'Cotizar';
  $floatCartShow   = ($settings['float_cart_show']  ?? '1') === '1';
  $floatWaShow     = ($settings['float_wa_show']    ?? '1') === '1';
  $floatWaTooltip  = $settings['float_wa_tooltip']  ?? '¿Necesitas ayuda?';
  $footerTagline   = $settings['footer_tagline']    ?? '';
  $footerCopyright = $settings['footer_copyright']  ?? ('© ' . date('Y') . ' ' . $project->name);
  $faviconUrl      = $settings['favicon_url']       ?? '';
  $logoUrl         = $settings['logo_url']          ?? '';
  $logoHeight      = (int)($settings['logo_height'] ?? '40');
  $heroHeightMap   = ['small'=>'300px','medium'=>'480px','large'=>'600px','full'=>'100vh'];
  $heroHeightCss   = $heroHeightMap[$heroHeight] ?? '480px';
  $heroAlignClass  = ['left'=>'text-left items-start','center'=>'text-center items-center','right'=>'text-right items-end'][$heroAlign] ?? 'text-center items-center';
  $allFonts = array_unique(array_filter([$fontTitle, $fontBody]));
@endphp
@if($faviconUrl)<link rel="icon" href="{{ $faviconUrl }}">@endif
@foreach($allFonts as $f)
<link href="https://fonts.googleapis.com/css2?family={{ urlencode(str_replace(' ', '+', $f)) }}:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
@endforeach
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
:root {
  --c: {{ $primaryColor }};
  --c2: {{ $secondaryColor }};
  --radius: {{ $borderRadius }};
  --font-title: '{{ $fontTitle }}', sans-serif;
  --font-body: '{{ $fontBody }}', sans-serif;
}
.btn-p         { background:var(--c); color:#fff; }
.btn-p:hover   { filter:brightness(.9); }
.btn-outline-p { border:2px solid var(--c); color:var(--c); }
.btn-outline-p:hover { background:var(--c); color:#fff; }
.badge-p  { background:var(--c); color:#fff; }
.price-p  { color:var(--c); }
.tab-act  { border-bottom:3px solid var(--c); color:var(--c)!important; font-weight:700; }
.cat-act  { background:var(--c); color:#fff!important; }
.ring-p   { outline:2px solid var(--c); outline-offset:2px; }
[x-cloak]{ display:none!important; }

/* Drawer */
.drawer-overlay{ position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:40;backdrop-filter:blur(2px); }
.drawer{ position:fixed;top:0;right:0;height:100%;width:420px;max-width:96vw;background:#fff;z-index:50;display:flex;flex-direction:column;box-shadow:-8px 0 32px rgba(0,0,0,.12); }

/* Product card */
.prod-card{ transition:transform .2s,box-shadow .2s; }
.prod-card:hover{ transform:translateY(-3px); box-shadow:0 12px 32px rgba(0,0,0,.10); }
.prod-img img{ transition:transform .35s; }
.prod-card:hover .prod-img img{ transform:scale(1.06); }

/* Sidebar */
.sidebar-cat{ display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:8px;font-size:.875rem;cursor:pointer;transition:all .15s;border:none;background:transparent;width:100%;text-align:left; }
.sidebar-cat:hover{ background:#f3f4f6; }
.sidebar-cat.active{ background:var(--c);color:#fff!important; }

@media(max-width:640px){ .drawer{ width:100%; } }
</style>

@php
$currency = $settings['currency'] ?? 'S/';
$searchIndex = $categories->flatMap(fn($cat) => $cat->products->map(fn($p) => [
    'id'    => $p->id,
    'name'  => $p->name,
    'price' => (float)$p->price,
    'cp'    => $p->compare_price ? (float)$p->compare_price : null,
    'img'   => $p->mainImage ? asset('storage/'.$p->mainImage->url) : null,
    'cat'   => $cat->name,
    'catId' => (string)$cat->id,
    'url'   => route('public.product', [$project->slug, $p->id]),
    'desc'  => \Str::limit(strip_tags($p->description ?? ''), 100),
    'stock' => $p->stock,
]))->values();
@endphp
</head>
<body class="bg-gray-50 text-gray-800" x-data="store()" x-cloak>

{{-- ═══════════════════════════════════════════
     TOP BAR
═══════════════════════════════════════════ --}}
<div class="bg-gray-900 text-gray-300 text-xs py-2 px-4 hidden md:block">
  <div class="max-w-[1400px] mx-auto flex items-center justify-between gap-4">
    <div class="flex items-center gap-6">
      @if($project->phone)
      <span class="flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
        </svg>
        {{ $project->phone }}
      </span>
      @endif
      @if($project->address)
      <span class="flex items-center gap-1.5 opacity-80">
        <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        {{ $project->address }}
      </span>
      @endif
    </div>
    <div class="flex items-center gap-4">
      @php
        $topSocials = [
          'facebook_url'  => ['label'=>'Facebook',  'icon'=>'M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z'],
          'instagram_url' => ['label'=>'Instagram', 'icon'=>'M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37zm1.5-4.87h.01M6.5 19.5h11a3 3 0 003-3v-11a3 3 0 00-3-3h-11a3 3 0 00-3 3v11a3 3 0 003 3z'],
          'tiktok_url'    => ['label'=>'TikTok',    'icon'=>'M9 12a4 4 0 104 4V4a5 5 0 005 5'],
          'twitter_url'   => ['label'=>'X',         'icon'=>'M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z'],
        ];
      @endphp
      @foreach($topSocials as $key => $soc)
      @if($settings[$key] ?? null)
      <a href="{{ $settings[$key] }}" target="_blank" rel="noopener"
         class="hover:text-white transition flex items-center gap-1" title="{{ $soc['label'] }}">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="{{ $soc['icon'] }}"/>
        </svg>
      </a>
      @endif
      @endforeach
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════
     HEADER PRINCIPAL
═══════════════════════════════════════════ --}}
<header class="bg-white shadow-sm sticky top-0 z-30 border-b border-gray-100">
  <div class="max-w-[1400px] mx-auto px-4 py-3 flex items-center gap-4">

    {{-- Logo --}}
    <a href="{{ $canonicalUrl }}" class="flex items-center gap-2.5 flex-shrink-0" aria-label="{{ $project->name }}">
      @if($project->logo_url)
        <img src="{{ asset('storage/'.$project->logo_url) }}"
             alt="Logo {{ $project->name }}"
             class="h-11 w-11 rounded-xl object-cover"
             width="44" height="44">
      @else
        <div class="h-11 w-11 rounded-xl btn-p flex items-center justify-center text-white font-black text-xl select-none">
          {{ strtoupper(substr($project->name,0,1)) }}
        </div>
      @endif
      <span class="font-black text-gray-900 text-xl tracking-tight hidden sm:block">{{ $project->name }}</span>
    </a>

    {{-- Buscador central --}}
    <div class="flex-1 max-w-2xl mx-auto relative" @click.outside="searchOpen = false">
      <div class="flex items-stretch bg-gray-50 rounded-xl border-2 border-gray-200 overflow-hidden
                  focus-within:border-[var(--c)] focus-within:bg-white transition-all">
        <select x-model="filterCat"
                class="bg-transparent text-sm text-gray-600 pl-4 pr-2 border-r border-gray-200 outline-none cursor-pointer hidden lg:block min-w-[130px] font-medium">
          <option value="">Todas las categorías</option>
          @foreach($categories as $cat)
          <option value="{{ $cat->id }}">{{ $cat->name }}</option>
          @endforeach
        </select>
        <input x-model="search"
               @input.debounce.200ms="filterCat=filterCat"
               @input="searchOpen = search.trim().length >= 2; searchIdx = -1; if(search.trim().length >= 2) _scrollToCatalog()"
               @keydown.escape="searchOpen = false; search = ''; searchIdx = -1"
               @keydown.arrow-down.prevent="if(suggestions.length) searchIdx = Math.min(searchIdx+1, suggestions.length-1)"
               @keydown.arrow-up.prevent="searchIdx = Math.max(searchIdx-1, -1)"
               @keydown.enter.prevent="suggestions[searchIdx] ? selectSuggestion(suggestions[searchIdx]) : (searchOpen=false, _scrollToCatalog())"
               type="search"
               placeholder="Buscar productos, marcas..."
               aria-label="Buscar en el catálogo de {{ $project->name }}"
               autocomplete="off"
               class="flex-1 bg-transparent px-4 py-3 text-sm outline-none min-w-0 placeholder-gray-400">
        <button @click="searchOpen=false; _scrollToCatalog()" class="btn-p px-5 py-3 text-sm font-semibold flex-shrink-0 flex items-center gap-1.5 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
          </svg>
          <span class="hidden sm:block">Buscar</span>
        </button>
      </div>
      {{-- Dropdown predictivo --}}
      <div x-show="searchOpen && suggestions.length > 0"
           x-cloak
           x-transition:enter="transition ease-out duration-100"
           x-transition:enter-start="opacity-0 -translate-y-1"
           x-transition:enter-end="opacity-100 translate-y-0"
           class="absolute left-0 right-0 top-full mt-1 bg-white rounded-xl shadow-2xl border border-gray-200 z-[200] overflow-hidden">
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
      {{-- WhatsApp --}}
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}?text={{ urlencode($settings['whatsapp_msg'] ?? 'Hola, vi tu catálogo y quiero más información.') }}"
         target="_blank" rel="noopener"
         title="Contactar por WhatsApp"
         class="flex items-center gap-2 bg-[#25D366] hover:bg-[#20ba5a] text-white text-sm font-semibold px-3 py-2.5 rounded-xl transition">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
          <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
        </svg>
        <span class="hidden md:block">WhatsApp</span>
      </a>
      @endif

      {{-- Carrito --}}
      <button @click="drawerOpen=true"
              class="relative p-2.5 hover:bg-gray-100 rounded-xl transition"
              aria-label="Ver carrito de compras">
        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <span x-show="cart.length" x-text="cart.length"
              class="absolute -top-0.5 -right-0.5 badge-p text-white text-[10px] font-black rounded-full w-5 h-5 flex items-center justify-center leading-none"></span>
      </button>
    </div>

  </div>
</header>

{{-- ═══════════════════════════════════════════
     HERO + BANNERS
═══════════════════════════════════════════ --}}
<section class="max-w-[1400px] mx-auto px-4 py-5">
  <div class="flex gap-4">

    {{-- Sidebar Categorías --}}
    <aside class="w-[220px] flex-shrink-0 hidden lg:block">
      <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="btn-p px-4 py-3 flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
          <span class="font-bold text-sm">Todas las categorías</span>
        </div>
        <nav class="p-2" aria-label="Categorías del catálogo">
          <button @click="filterCat=''; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                  :class="filterCat==='' ? 'active' : ''"
                  class="sidebar-cat text-gray-700 font-medium">
            <svg class="w-4 h-4 flex-shrink-0 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
            <span>Todo el catálogo</span>
            <span class="ml-auto text-xs opacity-50 font-normal">{{ $categories->sum(fn($c)=>$c->products->count()) }}</span>
          </button>
          @foreach($categories as $cat)
          <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                  :class="filterCat==='{{ $cat->id }}' ? 'active' : ''"
                  class="sidebar-cat text-gray-600">
            <svg class="w-4 h-4 flex-shrink-0 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span class="flex-1 truncate">{{ $cat->name }}</span>
            <span class="text-xs opacity-50 font-normal flex-shrink-0">{{ $cat->products->count() }}</span>
          </button>
          @endforeach
        </nav>
      </div>
    </aside>

    {{-- Hero + Mini banners --}}
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-3 gap-4">

      {{-- Hero --}}
      <div class="lg:col-span-2 rounded-2xl overflow-hidden relative flex items-end p-8 min-h-[280px] md:min-h-[320px]"
           style="background:{{ $settings['hero_bg_color'] ?? '#1e1b4b' }};">
        {{-- Decoración --}}
        <div class="absolute inset-0 opacity-10"
             style="background:radial-gradient(circle at 70% 50%, #fff 0%, transparent 60%)"></div>
        <div class="relative z-10 text-white">
          @if($settings['hero_badge'] ?? null)
          <span class="inline-block badge-p text-xs font-bold px-3 py-1 rounded-full mb-3 uppercase tracking-wider">
            {{ $settings['hero_badge'] }}
          </span>
          @endif
          <h1 class="text-3xl md:text-4xl lg:text-5xl font-black leading-tight mb-3 max-w-md">
            {{ $settings['hero_title'] ?? 'Bienvenido a nuestra tienda' }}
          </h1>
          <p class="text-white/75 text-base md:text-lg mb-5 max-w-sm">
            {{ $settings['hero_subtitle'] ?? 'Encuentra todo lo que necesitas al mejor precio' }}
          </p>
          <button @click="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                  class="btn-p px-7 py-3 rounded-xl font-bold text-sm transition inline-flex items-center gap-2">
            Ver catálogo
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
        {{-- Producto destacado visual --}}
        <div class="absolute right-8 bottom-0 top-0 flex items-center opacity-20 pointer-events-none select-none">
          <svg class="w-48 h-48 text-white" fill="currentColor" viewBox="0 0 24 24">
            <path d="M6 2l1.5 4.5h9L18 2H6zm.75 6A.75.75 0 006 8.75v10.5a2 2 0 002 2h8a2 2 0 002-2V8.75A.75.75 0 0017.25 8H6.75z"/>
          </svg>
        </div>
      </div>

      {{-- Mini banners --}}
      <div class="flex flex-col gap-4">
        <div class="rounded-2xl p-6 flex-1 flex items-center justify-between overflow-hidden relative"
             style="background:var(--c)">
          <div class="text-white relative z-10">
            <p class="text-xs font-bold uppercase tracking-wider opacity-75 mb-1">Destacado</p>
            <p class="font-black text-xl leading-tight">{{ $settings['banner1_title'] ?? 'Nuevos productos' }}</p>
            <p class="text-white/75 text-sm mt-1">{{ $settings['banner1_sub'] ?? 'Descubre lo último' }}</p>
            <button @click="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                    class="mt-3 text-white border border-white/40 rounded-lg px-3 py-1 text-xs font-semibold hover:bg-white/20 transition">
              Ver más →
            </button>
          </div>
          <div class="absolute -right-4 -bottom-4 w-24 h-24 rounded-full bg-white/10"></div>
        </div>
        <div class="rounded-2xl p-6 flex-1 flex items-center justify-between overflow-hidden relative bg-gray-800">
          <div class="text-white relative z-10">
            <p class="text-xs font-bold uppercase tracking-wider opacity-60 mb-1">Ofertas</p>
            <p class="font-black text-xl leading-tight">{{ $settings['banner2_title'] ?? 'Ofertas especiales' }}</p>
            <p class="text-white/60 text-sm mt-1">{{ $settings['banner2_sub'] ?? 'Precios imperdibles' }}</p>
            <button @click="tab='sale'; document.getElementById('tabs-section').scrollIntoView({behavior:'smooth'})"
                    class="mt-3 text-white border border-white/30 rounded-lg px-3 py-1 text-xs font-semibold hover:bg-white/10 transition">
              Ver ofertas →
            </button>
          </div>
          <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full opacity-10" style="background:var(--c)"></div>
        </div>
      </div>

    </div>
  </div>
</section>

{{-- ═══════════════════════════════════════════
     TOP CATEGORÍAS (cards)
═══════════════════════════════════════════ --}}
@if($categories->count() > 1)
<section class="max-w-[1400px] mx-auto px-4 pb-6" aria-label="Explorar categorías">
  <div class="flex items-center justify-between mb-3">
    <h2 class="font-black text-gray-900 text-lg">Explorar categorías</h2>
  </div>
  <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
    @foreach($categories as $cat)
    <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
            :class="filterCat==='{{ $cat->id }}' ? 'ring-2 ring-[var(--c)] ring-offset-2' : ''"
            class="bg-white rounded-2xl border border-gray-200 p-3 text-center hover:shadow-md transition group"
            aria-label="Ver categoría {{ $cat->name }}">
      <div class="w-10 h-10 rounded-xl btn-p flex items-center justify-center mx-auto mb-2 transition group-hover:scale-105">
        <span class="text-white font-black text-xs">{{ strtoupper(substr($cat->name,0,2)) }}</span>
      </div>
      <p class="text-xs font-semibold text-gray-700 leading-tight line-clamp-2">{{ $cat->name }}</p>
      <p class="text-[10px] text-gray-400 mt-0.5">{{ $cat->products->count() }}</p>
    </button>
    @endforeach
  </div>
</section>
@endif

{{-- ═══════════════════════════════════════════
     TABS: NOVEDADES / OFERTAS / TODOS
═══════════════════════════════════════════ --}}
<section id="tabs-section" class="max-w-[1400px] mx-auto px-4 pb-8" x-data="{ tab: 'new' }">
  {{-- Header --}}
  <div class="flex items-center gap-6 border-b-2 border-gray-200 mb-6 overflow-x-auto scrollbar-hide -mx-4 px-4">
    <button @click="tab='new'"
            :class="tab==='new' ? 'tab-act' : 'text-gray-500 hover:text-gray-800'"
            class="pb-3 text-sm font-semibold transition whitespace-nowrap">
      🆕 Novedades
    </button>
    <button @click="tab='sale'"
            :class="tab==='sale' ? 'tab-act' : 'text-gray-500 hover:text-gray-800'"
            class="pb-3 text-sm font-semibold transition flex items-center gap-1.5 whitespace-nowrap">
      🏷️ En Oferta
      @if($onSale->count())
      <span class="badge-p text-[10px] px-1.5 py-0.5 rounded-full font-black leading-none">{{ $onSale->count() }}</span>
      @endif
    </button>
    <button @click="tab='all'"
            :class="tab==='all' ? 'tab-act' : 'text-gray-500 hover:text-gray-800'"
            class="pb-3 text-sm font-semibold transition whitespace-nowrap">
      ⭐ Destacados
    </button>
  </div>

  {{-- Novedades --}}
  <div x-show="tab==='new'" x-transition:enter="transition opacity-0 duration-200" x-transition:enter-end="opacity-100">
    @if($newArrivals->count())
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4" data-products-grid>
      @foreach($newArrivals as $p)
        @include('public.partials.product-card', ['product' => $p, 'projectName' => $project->name, 'isQuoteOnly' => $isQuoteOnly, 'quotePriceDisp' => $quotePriceDisp, 'quoteWa' => $quoteWa, 'loop' => $loop])
      @endforeach
    </div>
    @else
    <p class="text-gray-400 text-sm py-10 text-center">No hay productos disponibles</p>
    @endif
  </div>

  {{-- En Oferta --}}
  <div x-show="tab==='sale'" x-transition:enter="transition opacity-0 duration-200" x-transition:enter-end="opacity-100">
    @if($onSale->count())
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4" data-products-grid>
      @foreach($onSale as $p)
        @include('public.partials.product-card', ['product' => $p, 'projectName' => $project->name, 'isQuoteOnly' => $isQuoteOnly, 'quotePriceDisp' => $quotePriceDisp, 'quoteWa' => $quoteWa, 'loop' => $loop])
      @endforeach
    </div>
    @else
    <div class="text-center py-10">
      <p class="text-4xl mb-2">🏷️</p>
      <p class="text-gray-500 text-sm">No hay productos en oferta por el momento</p>
    </div>
    @endif
  </div>

  {{-- Destacados --}}
  <div x-show="tab==='all'" x-transition:enter="transition opacity-0 duration-200" x-transition:enter-end="opacity-100">
    @if($featured->count())
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4" data-products-grid>
      @foreach($featured as $p)
        @include('public.partials.product-card', ['product' => $p, 'projectName' => $project->name, 'isQuoteOnly' => $isQuoteOnly, 'quotePriceDisp' => $quotePriceDisp, 'quoteWa' => $quoteWa, 'loop' => $loop])
      @endforeach
    </div>
    @else
    <p class="text-gray-400 text-sm py-10 text-center">No hay productos disponibles</p>
    @endif
  </div>
</section>

{{-- ═══════════════════════════════════════════
     CATÁLOGO COMPLETO
═══════════════════════════════════════════ --}}
<section id="catalogo" class="max-w-[1400px] mx-auto px-4 pb-16">

  {{-- Barra de filtros activos + resultados --}}
  <div class="sticky top-16 z-20 bg-white/95 backdrop-blur-sm -mx-4 px-4 py-3 mb-6 border-b border-gray-100 shadow-sm flex items-center justify-between flex-wrap gap-3">
    <div class="flex items-center gap-3">
      <h2 class="font-black text-gray-900 text-xl">Catálogo completo</h2>
      <span x-show="filterCat!=='' || search!=='' || priceFilter!=='' || onSaleFilter"
            class="badge-p text-xs px-2 py-0.5 rounded-full font-semibold">Filtrado</span>
    </div>
    <div class="flex items-center gap-2">
      {{-- Botón Filtros (solo mobile/tablet) --}}
      <button @click="filterOpen=true"
              class="xl:hidden flex items-center gap-1.5 text-xs font-semibold border border-gray-300 rounded-lg px-3 py-1.5 bg-white hover:bg-gray-50 transition relative">
        <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
        </svg>
        <span>Filtros</span>
        <span x-show="priceFilter!=='' || onSaleFilter"
              class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full text-[10px] font-black flex items-center justify-center text-white"
              style="background:var(--c)"
              x-text="(priceFilter!==''?1:0)+(onSaleFilter?1:0)"></span>
      </button>
      <select x-model="sortBy" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 outline-none bg-white text-gray-700 cursor-pointer hover:border-gray-400 transition">
        <option value="default">Ordenar</option>
        <option value="price_asc">Precio ↑</option>
        <option value="price_desc">Precio ↓</option>
        <option value="newest">Más nuevos</option>
        <option value="name_az">Nombre A→Z</option>
      </select>
      <span x-show="filterCat!=='' || search!=='' || priceFilter!=='' || onSaleFilter" class="text-sm text-gray-500">
        <button @click="filterCat=''; search=''; priceFilter=''; onSaleFilter=false"
                class="text-xs text-red-500 hover:text-red-700 border border-red-200 hover:border-red-400 px-3 py-1.5 rounded-lg transition">
          ✕ Limpiar filtros
        </button>
      </span>
    </div>
  </div>

  {{-- Contador --}}
  <p class="text-xs text-gray-400 mb-4" x-show="filterCat!=='' || search!=='' || priceFilter!=='' || onSaleFilter">
    <span x-text="visibleCount"></span> producto<span x-show="visibleCount !== 1">s</span> encontrado<span x-show="visibleCount !== 1">s</span>
  </p>

  {{-- Layout: sidebar + grid --}}
  <div class="flex gap-6">

    {{-- Sidebar filtros (desktop) --}}
    <aside class="w-[210px] flex-shrink-0 hidden xl:block space-y-3">
      <div class="bg-white rounded-2xl border border-gray-200 p-4">
        <p class="font-bold text-gray-800 text-sm mb-3">Precio</p>
        <div class="space-y-2">
          <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="radio" x-model="priceFilter" value="" class="accent-[var(--c)]">
            Todos los precios
          </label>
          <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="radio" x-model="priceFilter" value="0-50" class="accent-[var(--c)]">
            Hasta {{ $currency }} 50
          </label>
          <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="radio" x-model="priceFilter" value="50-150" class="accent-[var(--c)]">
            {{ $currency }} 50 — {{ $currency }} 150
          </label>
          <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="radio" x-model="priceFilter" value="150-500" class="accent-[var(--c)]">
            {{ $currency }} 150 — {{ $currency }} 500
          </label>
          <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="radio" x-model="priceFilter" value="500+" class="accent-[var(--c)]">
            Más de {{ $currency }} 500
          </label>
          {{-- Rango personalizado --}}
          <div class="pt-2 border-t border-gray-100">
            <p class="text-xs text-gray-400 mb-1.5">Rango personalizado</p>
            <div class="flex items-center gap-1.5">
              <input type="number" x-model.number="priceMin" placeholder="Min" min="0"
                     class="w-full border border-gray-200 rounded-lg px-2 py-1 text-xs outline-none focus:border-gray-400 bg-gray-50 transition">
              <span class="text-gray-300 text-xs">—</span>
              <input type="number" x-model.number="priceMax" placeholder="Max" min="0"
                     class="w-full border border-gray-200 rounded-lg px-2 py-1 text-xs outline-none focus:border-gray-400 bg-gray-50 transition">
            </div>
            <button @click="priceFilter='custom'; priceMin=priceMin||0; priceMax=priceMax||99999"
                    :disabled="!priceMin && !priceMax"
                    class="mt-2 w-full text-xs font-semibold py-1.5 rounded-lg transition disabled:opacity-40"
                    style="background:var(--c);color:#fff">Aplicar</button>
            <button x-show="priceFilter==='custom'" @click="priceFilter=''; priceMin=0; priceMax=0"
                    class="mt-1 w-full text-xs text-gray-400 hover:text-red-500 transition">Quitar rango</button>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-gray-200 p-4">
        <p class="font-bold text-gray-800 text-sm mb-3">Disponibilidad</p>
        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
          <input type="checkbox" x-model="onSaleFilter" class="accent-[var(--c)] rounded">
          Solo en oferta
        </label>
      </div>
    </aside>

    {{-- Grid de productos --}}
    <div class="flex-1">
      @foreach($categories as $cat)
      @if($cat->products->count())
      <div x-show="filterCat==='' || filterCat==='{{ $cat->id }}'" class="mb-12">
        <div class="flex items-center gap-3 mb-5">
          <div class="w-1 h-6 rounded-full btn-p flex-shrink-0"></div>
          <h3 class="font-black text-gray-900 text-lg">{{ $cat->name }}</h3>
          <span class="badge-p text-xs px-2 py-0.5 rounded-full font-bold">{{ $cat->products->count() }}</span>
          <div class="flex-1 border-t border-gray-200 ml-1"></div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5 gap-4" data-products-grid>
          @foreach($cat->products as $p)
          @php
          $qvData = json_encode([
              'id'    => $p->id,
              'name'  => $p->name,
              'img'   => $p->mainImage ? asset('storage/'.$p->mainImage->url) : '',
              'price' => (float)$p->price,
              'cp'    => $p->compare_price ? (float)$p->compare_price : null,
              'desc'  => \Str::limit(strip_tags($p->description ?? ''), 120),
              'url'   => route('public.product', [$project->slug, $p->id]),
              'stock' => $p->stock,
          ]);
          @endphp
          {{-- SEO: cada producto tiene id anchor para deep-link --}}
          <article id="producto-{{ $p->id }}"
                   class="prod-card bg-white rounded-2xl border border-gray-200 overflow-hidden group"
                   x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }}) && ({{ $loop->index }} < 8 || expandedCats['{{ $cat->id }}'])"
                   data-price="{{ $p->price }}"
                   data-ts="{{ $p->created_at ? $p->created_at->timestamp : 0 }}"
                   data-idx="{{ $loop->index }}"
                   data-name="{{ strtolower($p->name) }}"
                   data-qv='{{ $qvData }}'
                   itemscope itemtype="https://schema.org/Product">

            {{-- Schema.org microdata --}}
            <meta itemprop="name" content="{{ $p->name }}">
            <meta itemprop="sku"  content="{{ $p->sku ?? $p->id }}">
            @if($p->description)<meta itemprop="description" content="{{ Str::limit($p->description, 160) }}">@endif

            {{-- Imagen con SEO --}}
            <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="block prod-img aspect-square bg-gray-50 relative overflow-hidden">
              @if($p->mainImage)
              <img src="{{ asset('storage/'.$p->mainImage->url) }}"
                   alt="{{ $p->name }} — {{ $cat->name }} en {{ $project->name }}"
                   title="{{ $p->name }}"
                   loading="lazy"
                   decoding="async"
                   width="400" height="400"
                   class="w-full h-full object-cover"
                   itemprop="image">
              @else
              <div class="w-full h-full flex flex-col items-center justify-center text-gray-300 gap-2">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="text-xs">Sin imagen</span>
              </div>
              @endif

              {{-- Badge descuento estilo Temu --}}
              @if($p->compare_price && $p->compare_price > $p->price)
              @php $pct = round((($p->compare_price - $p->price) / $p->compare_price) * 100); @endphp
              <span class="absolute top-0 left-0 bg-red-500 text-white font-black leading-none rounded-br-xl rounded-tl-xl px-2 py-1" style="font-size:11px">
                -{{ $pct }}%
              </span>
              @endif

              {{-- Badge nuevo --}}
              @if($p->created_at && $p->created_at->diffInDays() <= 30)
              <span class="absolute top-0 right-0 badge-p text-[10px] font-black px-2 py-1 rounded-bl-xl rounded-tr-xl leading-none">
                NUEVO
              </span>
              @endif

              {{-- Badge stock --}}
              @if($p->stock !== null && $p->stock === 0)
              <span class="absolute bottom-0 left-0 right-0 bg-red-600/90 text-white text-[10px] font-black py-1 text-center tracking-wide">AGOTADO</span>
              @elseif($p->stock !== null && $p->stock > 0 && $p->stock <= 5)
              <span class="absolute bottom-0 left-0 right-0 bg-orange-500/90 text-white text-[10px] font-black py-1 text-center tracking-wide">⚡ CASI AGOTADO — {{ $p->stock }} restantes</span>
              @endif

              {{-- Botón vista rápida --}}
              <button @click.prevent="$el.closest('article').dataset.qv && (qv=JSON.parse($el.closest('article').dataset.qv), qvOpen=true)"
                      class="absolute inset-0 flex items-end justify-center pb-3 opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-10 pointer-events-none group-hover:pointer-events-auto">
                <span class="bg-black/70 text-white text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-1.5 backdrop-blur-sm">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                  Vista rápida
                </span>
              </button>
            </a>

            {{-- Info --}}
            <div class="p-3" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
              <meta itemprop="priceCurrency" content="PEN">
              <meta itemprop="price" content="{{ $p->price }}">
              <meta itemprop="availability" content="https://schema.org/InStock">

              <p class="text-[11px] text-gray-400 mb-0.5 font-medium">{{ $cat->name }}</p>
              <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="text-sm font-bold text-gray-900 leading-snug line-clamp-2 mb-2 hover:underline block" itemprop="name">{{ $p->name }}</a>

              @if($p->description)
              <p class="text-xs text-gray-500 line-clamp-2 mb-2 leading-relaxed">{{ $p->description }}</p>
              @endif

              {{-- Rating --}}
              @if(isset($productRatings) && isset($productRatings[$p->id]))
              <div class="flex items-center gap-1 mb-1.5">
                <span class="text-amber-400 text-xs leading-none">{{ str_repeat('★', floor($productRatings[$p->id]->avg_rating)) }}{{ str_repeat('☆', 5 - floor($productRatings[$p->id]->avg_rating)) }}</span>
                <span class="text-[10px] text-gray-400">({{ $productRatings[$p->id]->rating_count }})</span>
              </div>
              @endif

              @if(!$isQuoteOnly || $quotePriceDisp === 'show')
              @php $hasWholesale = $wholesaleEnabled && $p->wholesale_price && $p->wholesale_min_qty; @endphp
              @if($hasWholesale)
              {{-- DOS BLOQUES: Minorista + Mayorista --}}
              <div class="space-y-1.5 mb-1">

                {{-- BLOQUE MINORISTA --}}
                <div x-data="{ qty:1 }" class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                  <div class="px-2.5 pt-1.5 pb-1 bg-gray-50 border-b border-gray-100">
                    <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Minorista</span>
                    <div class="flex items-baseline gap-1">
                      <span class="price-p font-black text-base leading-none">{{ $currency }} {{ number_format($p->price,2) }}</span>
                      @if($p->unit)<span class="text-[10px] text-gray-400 font-medium">{{ $p->unit }}</span>@endif
                    </div>
                  </div>
                  <div class="flex items-center gap-1.5 px-2 py-1.5">
                    <div class="flex items-center rounded-lg border border-gray-200 overflow-hidden shrink-0">
                      <button type="button" @click="qty=Math.max(1,qty-1)" class="w-6 h-7 flex items-center justify-center text-gray-500 hover:bg-gray-100 text-base font-bold leading-none">−</button>
                      <input type="number" x-model.number="qty" min="1" class="w-8 h-7 text-center text-xs font-bold border-x border-gray-200 focus:outline-none bg-white">
                      <button type="button" @click="qty++" class="w-6 h-7 flex items-center justify-center text-gray-500 hover:bg-gray-100 text-base font-bold leading-none">+</button>
                    </div>
                    <button @click="addToCart({id:{{ $p->id }},name:'{{ addslashes($p->name) }} (minorista)',price:{{ $p->price }},qty:qty,img:'{{ $p->mainImage ? asset('storage/'.$p->mainImage->url) : '' }}'})"
                            {{ $p->stock !== null && $p->stock === 0 ? 'disabled' : '' }}
                            class="flex-1 btn-p h-7 rounded-lg text-[11px] font-bold flex items-center justify-center gap-1 disabled:opacity-50 whitespace-nowrap">
                      <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                      Agregar
                    </button>
                  </div>
                </div>

                {{-- BLOQUE MAYORISTA --}}
                <div x-data="{ qty: {{ (int)$p->wholesale_min_qty }} }" class="rounded-xl border border-amber-400 overflow-hidden">
                  <div class="px-2.5 pt-1.5 pb-1 bg-amber-500">
                    <span class="block text-[9px] font-black text-amber-200 uppercase tracking-widest mb-0.5">Mayorista</span>
                    <div class="flex items-baseline gap-1">
                      <span class="font-black text-base text-white leading-none">{{ $currency }} {{ number_format($p->wholesale_price,2) }}</span>
                      @if($p->wholesale_unit)<span class="text-[10px] text-amber-200 font-medium">{{ $p->wholesale_unit }}</span>@endif
                    </div>
                    <p class="text-[9px] text-amber-200 mt-0.5">Mín. {{ $p->wholesale_min_qty }}{{ $p->wholesale_unit ? ' '.$p->wholesale_unit : '' }}</p>
                  </div>
                  <div class="flex items-center gap-1.5 px-2 py-1.5 bg-amber-50">
                    <div class="flex items-center rounded-lg border border-amber-300 overflow-hidden bg-white shrink-0">
                      <button type="button" @click="qty=Math.max({{ (int)$p->wholesale_min_qty }},qty-1)" class="w-6 h-7 flex items-center justify-center text-amber-600 hover:bg-amber-100 text-base font-bold leading-none">−</button>
                      <input type="number" x-model.number="qty" min="{{ $p->wholesale_min_qty }}" class="w-8 h-7 text-center text-xs font-bold border-x border-amber-200 focus:outline-none bg-white text-amber-700">
                      <button type="button" @click="qty++" class="w-6 h-7 flex items-center justify-center text-amber-600 hover:bg-amber-100 text-base font-bold leading-none">+</button>
                    </div>
                    <button @click="addToCart({id:{{ $p->id }},name:'{{ addslashes($p->name) }} (mayorista)',price:{{ $p->wholesale_price }},qty:qty,img:'{{ $p->mainImage ? asset('storage/'.$p->mainImage->url) : '' }}'})"
                            {{ $p->stock !== null && $p->stock === 0 ? 'disabled' : '' }}
                            class="flex-1 h-7 rounded-lg text-[11px] font-bold flex items-center justify-center gap-1 bg-amber-500 hover:bg-amber-600 text-white disabled:opacity-50 whitespace-nowrap">
                      <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                      Agregar
                    </button>
                  </div>
                </div>

              </div>
              @else
              {{-- Sin mayoreo: precio estilo Temu --}}
              <div class="mb-2">
                <div class="flex items-baseline gap-1.5 flex-wrap">
                  <span class="price-p font-black text-lg leading-none" itemprop="price">{{ $currency }} {{ number_format($p->price,2) }}</span>
                  @if($p->compare_price && $p->compare_price > $p->price)
                  <span class="text-xs text-gray-400 line-through font-medium">{{ $currency }} {{ number_format($p->compare_price,2) }}</span>
                  @endif
                  @if($isQuoteOnly)
                  <span class="text-[10px] text-gray-400 font-medium">(referencial)</span>
                  @endif
                </div>
                @if($p->compare_price && $p->compare_price > $p->price)
                @php $ahorro = $p->compare_price - $p->price; @endphp
                <p class="text-[10px] text-green-600 font-semibold mt-0.5">Ahorras {{ $currency }} {{ number_format($ahorro,2) }}</p>
                @endif
              </div>
              @if($p->unit)
              <div x-data="{ qty:1 }" class="flex items-center gap-2 mb-2">
                <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                  <button type="button" @click="qty=Math.max(1,qty-1)" class="px-2 py-1 text-gray-600 hover:bg-gray-100 text-sm font-bold">−</button>
                  <input type="number" x-model.number="qty" min="1" class="w-10 text-center text-sm font-semibold border-x border-gray-200 py-1 focus:outline-none">
                  <button type="button" @click="qty++" class="px-2 py-1 text-gray-600 hover:bg-gray-100 text-sm font-bold">+</button>
                </div>
                <span class="text-xs text-gray-400">{{ $p->unit }}</span>
                <button @click="addToCart({id:{{ $p->id }},name:'{{ addslashes($p->name) }}',price:{{ $p->price }},qty:qty,img:'{{ $p->mainImage ? asset('storage/'.$p->mainImage->url) : '' }}'})"
                        {{ $p->stock !== null && $p->stock === 0 ? 'disabled' : '' }}
                        class="flex-1 btn-p py-2 rounded-xl text-sm font-bold transition flex items-center justify-center gap-1 disabled:opacity-50">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                  Agregar
                </button>
              </div>
              @else
              <button @click="addToCart({id:{{ $p->id }},name:'{{ addslashes($p->name) }}',price:{{ $p->price }},img:'{{ $p->mainImage ? asset('storage/'.$p->mainImage->url) : '' }}'})"
                      {{ $p->stock !== null && $p->stock === 0 ? 'disabled' : '' }}
                      class="w-full btn-p py-2 rounded-xl text-sm font-bold transition flex items-center justify-center gap-1.5 disabled:opacity-50 mb-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                @if($isQuoteOnly) Cotizar @else Agregar @endif
              </button>
              @endif
              @endif
              @else
              <div class="mb-3"><span class="text-xs text-gray-400 italic">Precio a consultar</span></div>
              <button @click="addToCart({id:{{ $p->id }},name:'{{ addslashes($p->name) }}',price:{{ $p->price }},img:'{{ $p->mainImage ? asset('storage/'.$p->mainImage->url) : '' }}'})"
                      {{ $p->stock !== null && $p->stock === 0 ? 'disabled' : '' }}
                      class="w-full btn-p py-2 rounded-xl text-sm font-bold transition flex items-center justify-center gap-1.5 disabled:opacity-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Cotizar
              </button>
              @endif
              @if($quoteWa && !$isQuoteOnly)
              <a href="https://wa.me/{{ $quoteWa }}?text={{ urlencode('Hola, me interesa este producto: ' . $p->name) }}"
                 target="_blank" rel="noopener"
                 class="mt-1.5 w-full py-1.5 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 text-white transition hover:opacity-90"
                 style="background:#25D366;">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                </svg>
                Consultar por WA
              </a>
              @endif
            </div>
          </article>
          @endforeach
        </div>
        {{-- Ver más --}}
        @if($cat->products->count() > 8)
        <div class="mt-4 text-center" x-show="!expandedCats['{{ $cat->id }}'] && matchProduct('', 0, null)">
          <button @click="expandedCats = {...expandedCats, '{{ $cat->id }}': true}"
                  class="text-sm font-semibold px-5 py-2 rounded-xl border-2 transition hover:text-white hover:bg-[var(--c)] hover:border-[var(--c)]"
                  style="border-color:var(--c); color:var(--c)">
            Ver todos los {{ $cat->products->count() }} productos
          </button>
        </div>
        @endif
      </div>
      @endif
      @endforeach

      {{-- Sin resultados --}}
      <div x-show="noResults" class="text-center py-20">
        <p class="text-5xl mb-4">🔍</p>
        <p class="font-bold text-gray-700 text-lg mb-1">Sin resultados</p>
        <p class="text-gray-400 text-sm">Intenta con otro término o categoría</p>
        <button @click="search=''; filterCat=''; priceFilter=''; onSaleFilter=false; priceMin=0; priceMax=0"
                class="mt-4 btn-outline-p px-5 py-2 rounded-xl text-sm font-semibold transition">
          Ver todo el catálogo
        </button>
      </div>

    </div>
  </div>

  {{-- Vistos recientemente --}}
  <div x-show="recentlyViewed.length > 0" x-cloak class="mt-12 pt-8 border-t border-gray-100">
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
</section>

{{-- ═══════════════════════════════════════════
     QUICK VIEW MODAL
═══════════════════════════════════════════ --}}
<div x-show="qvOpen" x-cloak
     class="fixed inset-0 z-[60] flex items-center justify-center p-4"
     @keydown.escape.window="qvOpen=false">
  <div @click="qvOpen=false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
  <div x-show="qvOpen && qv"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0 scale-95"
       x-transition:enter-end="opacity-100 scale-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100 scale-100"
       x-transition:leave-end="opacity-0 scale-95"
       class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden z-10">
    {{-- Close --}}
    <button @click="qvOpen=false" class="absolute top-3 right-3 z-20 w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition">
      <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
    <div class="flex flex-col sm:flex-row" x-show="qv">
      {{-- Imagen --}}
      <div class="sm:w-48 aspect-square flex-shrink-0 bg-gray-50 overflow-hidden">
        <img x-show="qv && qv.img" :src="qv && qv.img" :alt="qv && qv.name" class="w-full h-full object-cover">
        <div x-show="qv && !qv.img" class="w-full h-full flex items-center justify-center">
          <svg class="w-12 h-12 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </div>
      </div>
      {{-- Info --}}
      <div class="flex-1 p-5 flex flex-col">
        <p class="text-lg font-black text-gray-900 leading-snug mb-1" x-text="qv && qv.name"></p>
        <div class="flex items-baseline gap-2 mb-2">
          <span class="text-xl font-black" style="color:var(--c)" x-text="qv && ('{{ $currency }} ' + qv.price.toFixed(2))"></span>
          <span x-show="qv && qv.cp && qv.cp > qv.price" class="text-sm text-gray-400 line-through" x-text="qv && qv.cp && ('{{ $currency }} ' + qv.cp.toFixed(2))"></span>
        </div>
        <p x-show="qv && qv.desc" class="text-sm text-gray-500 leading-relaxed mb-4 flex-1" x-text="qv && qv.desc"></p>
        <p x-show="qv && qv.stock === 0" class="text-xs font-bold text-red-500 mb-3">Agotado</p>
        <div class="flex flex-col gap-2 mt-auto">
          <button @click="addToCart({id:qv.id,name:qv.name,price:qv.price,img:qv.img}); qvOpen=false"
                  x-show="qv && qv.stock !== 0"
                  class="w-full btn-p py-2.5 rounded-xl text-sm font-black flex items-center justify-center gap-2 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            {{ $isQuoteOnly ? 'Cotizar' : 'Agregar al carrito' }}
          </button>
          <a :href="qv && qv.url" class="w-full text-center py-2 rounded-xl text-sm font-semibold border-2 transition hover:bg-gray-50"
             style="border-color:var(--c); color:var(--c)">
            Ver producto completo
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════
     MOBILE FILTER BOTTOM-SHEET
═══════════════════════════════════════════ --}}
<div x-show="filterOpen" x-cloak class="xl:hidden fixed inset-0 z-50 flex flex-col justify-end">
  {{-- Overlay --}}
  <div @click="filterOpen=false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
  {{-- Panel --}}
  <div class="relative bg-white rounded-t-3xl shadow-2xl max-h-[80vh] flex flex-col"
       x-transition:enter="transition ease-out duration-300 transform"
       x-transition:enter-start="translate-y-full"
       x-transition:enter-end="translate-y-0"
       x-transition:leave="transition ease-in duration-200 transform"
       x-transition:leave-start="translate-y-0"
       x-transition:leave-end="translate-y-full">

    {{-- Handle + header --}}
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

    {{-- Contenido con scroll --}}
    <div class="overflow-y-auto flex-1 px-5 py-4 space-y-6">

      {{-- Precio --}}
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
                  class="border rounded-xl px-3 py-2 text-sm text-left transition">{{ $currency }} 50–150</button>
          <button @click="priceFilter='150-500'"
                  :class="priceFilter==='150-500' ? 'border-[var(--c)] text-[var(--c)] font-bold' : 'border-gray-200 text-gray-600'"
                  class="border rounded-xl px-3 py-2 text-sm text-left transition">{{ $currency }} 150–500</button>
          <button @click="priceFilter='500+'"
                  :class="priceFilter==='500+' ? 'border-[var(--c)] text-[var(--c)] font-bold' : 'border-gray-200 text-gray-600'"
                  class="border rounded-xl px-3 py-2 text-sm text-left transition col-span-2">Más de {{ $currency }} 500</button>
        </div>
        {{-- Rango personalizado --}}
        <div class="mt-3 pt-3 border-t border-gray-100">
          <p class="text-xs text-gray-400 mb-2">O ingresa un rango personalizado</p>
          <div class="flex items-center gap-2">
            <input type="number" x-model.number="priceMin" placeholder="Mín" min="0"
                   class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-gray-400 transition">
            <span class="text-gray-300">—</span>
            <input type="number" x-model.number="priceMax" placeholder="Máx" min="0"
                   class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-gray-400 transition">
          </div>
          <button @click="if(priceMin||priceMax){priceFilter='custom'}"
                  :disabled="!priceMin && !priceMax"
                  class="mt-2 w-full py-2 rounded-xl text-sm font-bold transition disabled:opacity-40"
                  style="background:var(--c);color:#fff">Aplicar rango</button>
        </div>
      </div>

      {{-- Oferta --}}
      <div>
        <p class="font-bold text-gray-800 text-sm mb-3">Disponibilidad</p>
        <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer transition"
               :class="onSaleFilter ? 'border-[var(--c)] bg-[color-mix(in_srgb,var(--c)_8%,white)]' : ''">
          <input type="checkbox" x-model="onSaleFilter" class="accent-[var(--c)] w-4 h-4 rounded">
          <span class="text-sm font-medium text-gray-700">Solo productos en oferta</span>
        </label>
      </div>

      {{-- Ordenamiento --}}
      <div>
        <p class="font-bold text-gray-800 text-sm mb-3">Ordenar por</p>
        <div class="space-y-2">
          @foreach([['default','Relevancia'],['price_asc','Precio: menor a mayor'],['price_desc','Precio: mayor a menor'],['newest','Más nuevos primero'],['name_az','Nombre A → Z']] as [$val,$lbl])
          <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer transition"
                 :class="sortBy==='{{ $val }}' ? 'border-[var(--c)] bg-[color-mix(in_srgb,var(--c)_8%,white)]' : ''">
            <input type="radio" x-model="sortBy" value="{{ $val }}" class="accent-[var(--c)] w-4 h-4">
            <span class="text-sm font-medium text-gray-700">{{ $lbl }}</span>
          </label>
          @endforeach
        </div>
      </div>
    </div>

    {{-- Footer botones --}}
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

{{-- TOAST notificación producto agregado --}}
<div x-show="toastShow && cart.length > 0" x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed top-4 left-1/2 -translate-x-1/2 z-50 bg-gray-900 text-white text-sm font-semibold px-5 py-2.5 rounded-full shadow-lg whitespace-nowrap"
     x-text="toastMsg">
</div>

{{-- BARRA FLOTANTE INFERIOR estilo Temu --}}
<div x-show="cart.length > 0" x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-full"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-full"
     class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 shadow-2xl px-4 py-3 flex items-center gap-3">
  {{-- Resumen --}}
  <div class="flex-1 min-w-0">
    <div class="flex items-center gap-2">
      <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-white text-[10px] font-black btn-p" x-text="cart.reduce((s,i)=>s+i.qty,0)"></span>
      <span class="text-xs text-gray-500 font-medium">{{ $isQuoteOnly ? 'productos a cotizar' : 'productos en tu pedido' }}</span>
    </div>
    <p class="font-black text-base price-p leading-none mt-0.5" x-text="'{{ $currency }} ' + cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></p>
  </div>
  {{-- Botón ver pedido --}}
  <button @click="drawerOpen=true"
          class="btn-p px-5 py-3 rounded-xl font-black text-sm flex items-center gap-2 whitespace-nowrap shadow-lg">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
    </svg>
    {{ $isQuoteOnly ? 'Ver cotización' : 'Ver pedido' }}
  </button>
</div>

{{-- ═══════════════════════════════════════════
     CART DRAWER — 2 pasos
═══════════════════════════════════════════ --}}
<div x-show="drawerOpen" x-cloak>
  <div class="drawer-overlay" @click="drawerOpen=false" aria-hidden="true"></div>
  <div class="drawer"
       role="dialog" aria-label="{{ $isQuoteOnly ? 'Mi cotización' : 'Mi pedido' }}"
       x-show="drawerOpen"
       x-transition:enter="transition ease-out duration-300 transform"
       x-transition:enter-start="translate-x-full"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-200 transform"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="translate-x-full">

    {{-- ── HEADER ── --}}
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
      <div class="flex items-center gap-2">
        {{-- Botón volver (solo en paso 2) --}}
        <button x-show="(drawerStep === 2 || drawerStep === 3) && !orderSent" @click="drawerStep > 1 ? drawerStep-- : null"
                class="p-1.5 hover:bg-gray-100 rounded-lg transition mr-1" aria-label="Volver">
          <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        @if($isQuoteOnly)
        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <h2 class="font-black text-gray-900 text-base"
            x-text="drawerStep === 1 ? 'Mi cotización' : (drawerStep === 2 ? 'Confirmar datos' : 'Pagar')"></h2>
        @else
        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <h2 class="font-black text-gray-900 text-base"
            x-text="drawerStep === 1 ? 'Tu pedido' : (drawerStep === 2 ? 'Confirmar datos' : 'Pagar')"></h2>
        @endif
        <span x-show="cart.length && drawerStep === 1"
              class="badge-p text-xs px-2 py-0.5 rounded-full font-black" x-text="cart.length + ' items'"></span>
      </div>
      <button @click="drawerOpen=false" class="p-2 hover:bg-gray-100 rounded-xl transition" aria-label="Cerrar">
        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    {{-- ══════════════════════════════════════
         PASO 1 — Lista de productos
    ══════════════════════════════════════ --}}
    <div x-show="drawerStep === 1" class="flex flex-col flex-1 overflow-hidden">

      {{-- Lista scrolleable --}}
      <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <template x-if="cart.length === 0">
          <div class="text-center py-16 text-gray-400">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="font-semibold text-gray-500 mb-1">{{ $isQuoteOnly ? 'Tu cotización está vacía' : 'Tu carrito está vacío' }}</p>
            <p class="text-sm">Agrega productos para comenzar</p>
          </div>
        </template>

        <template x-for="(item, i) in cart" :key="item.id+'_'+item.name">
          <div class="flex items-center gap-3 bg-gray-50 rounded-2xl p-3">
            <div class="w-14 h-14 rounded-xl overflow-hidden flex-shrink-0 bg-gray-200">
              <img :src="item.img" x-show="item.img" class="w-full h-full object-cover" :alt="item.name">
              <div x-show="!item.img" class="w-full h-full flex items-center justify-center text-gray-400 text-xs">IMG</div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-gray-800 leading-snug line-clamp-2" x-text="item.name"></p>
              <p x-show="item.unit" class="text-[10px] text-gray-400 font-medium" x-text="item.qty+' '+item.unit+' × S/ '+item.price.toFixed(2)"></p>
              @if(!$isQuoteOnly || $quotePriceDisp === 'show')
              <p class="price-p font-black text-sm mt-0.5" x-text="'S/ ' + (item.price * item.qty).toFixed(2)"></p>
              @endif
            </div>
            <div class="flex items-center gap-1 flex-shrink-0">
              <button @click="item.qty > 1 ? item.qty-- : cart.splice(i,1)"
                      class="w-8 h-8 rounded-xl border-2 border-gray-200 hover:border-red-300 hover:bg-red-50 text-gray-600 hover:text-red-500 font-bold text-sm transition flex items-center justify-center">
                <span x-text="item.qty > 1 ? '−' : '×'"></span>
              </button>
              <span class="w-7 text-center text-sm font-black text-gray-800" x-text="item.qty"></span>
              <button @click="item.qty++"
                      class="w-8 h-8 rounded-xl btn-p text-white font-bold text-sm transition flex items-center justify-center">+</button>
            </div>
          </div>
        </template>
      </div>

      {{-- Footer fijo paso 1 --}}
      <div x-show="cart.length > 0" class="border-t border-gray-100 px-5 py-4 space-y-3 flex-shrink-0">
        {{-- Totales --}}
        <div class="flex justify-between items-center">
          <span class="text-sm text-gray-500"><span x-text="cart.reduce((s,i)=>s+i.qty,0)"></span> productos</span>
          @if(!$isQuoteOnly || $quotePriceDisp === 'show')
          <span class="font-black text-lg price-p" x-text="'S/ ' + cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></span>
          @else
          <span class="text-xs text-gray-400 italic">Precios a cotizar</span>
          @endif
        </div>

        {{-- Métodos de pago aceptados (solo venta directa) --}}
        @if(!$isQuoteOnly && count($acceptedPayments) > 0)
        <div class="flex flex-wrap gap-1.5">
          @foreach($acceptedPayments as $pmKey)
          @if(isset($paymentMeta[$pmKey]))
          <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-lg bg-gray-100 text-gray-600 font-medium">
            <span>{{ $paymentMeta[$pmKey]['emoji'] }}</span>
            {{ $paymentMeta[$pmKey]['label'] }}
          </span>
          @endif
          @endforeach
        </div>
        @endif

        {{-- CTA paso 2 --}}
        <button @click="drawerStep=2; orderError=''"
                class="w-full btn-p py-3.5 rounded-xl font-black text-sm transition flex items-center justify-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
          </svg>
          {{ $isQuoteOnly ? 'Continuar y cotizar' : 'Continuar y pedir' }}
        </button>
      </div>
    </div>

    {{-- ══════════════════════════════════════
         PASO 2 — Formulario + envío
    ══════════════════════════════════════ --}}
    <div x-show="drawerStep === 2" class="flex flex-col flex-1 overflow-hidden">

      <div x-show="!orderSent" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">

        {{-- Mini-resumen --}}
        <div class="bg-gray-50 rounded-2xl px-4 py-3 flex justify-between items-center">
          <span class="text-sm text-gray-600 font-medium">
            <span x-text="cart.reduce((s,i)=>s+i.qty,0)"></span> producto<span x-show="cart.reduce((s,i)=>s+i.qty,0)!==1">s</span>
          </span>
          @if(!$isQuoteOnly || $quotePriceDisp === 'show')
          <span class="font-black price-p" x-text="'S/ ' + cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></span>
          @else
          <span class="text-xs text-gray-400 italic">a cotizar</span>
          @endif
        </div>

        {{-- Campos --}}
        <input x-model="form.name" type="text" placeholder="Tu nombre completo *"
               class="w-full border-2 border-gray-200 focus:border-[var(--c)] rounded-xl px-4 py-2.5 text-sm outline-none transition"
               autocomplete="name">
        <input x-model="form.phone" type="tel" placeholder="Tu WhatsApp / teléfono *"
               class="w-full border-2 border-gray-200 focus:border-[var(--c)] rounded-xl px-4 py-2.5 text-sm outline-none transition"
               autocomplete="tel">
        <input x-model="form.email" type="email" placeholder="Tu correo electrónico (opcional)"
               class="w-full border-2 border-gray-200 focus:border-[var(--c)] rounded-xl px-4 py-2.5 text-sm outline-none transition"
               autocomplete="email">
        <textarea x-model="form.notes" rows="2" placeholder="¿Alguna nota adicional? (opcional)"
                  class="w-full border-2 border-gray-200 focus:border-[var(--c)] rounded-xl px-4 py-2.5 text-sm outline-none resize-none transition"></textarea>
        @if($requireAddress)
        <input x-model="form.address" type="text" placeholder="Dirección de entrega *"
               class="w-full border-2 border-gray-200 focus:border-[var(--c)] rounded-xl px-4 py-2.5 text-sm outline-none transition"
               autocomplete="street-address">
        @endif

        {{-- Cupón de descuento --}}
        <div>
          <div x-show="!couponApplied" class="flex gap-2">
            <input x-model="couponCode" @keydown.enter.prevent="applyCoupon" type="text"
                   placeholder="Código de descuento"
                   class="flex-1 border-2 border-gray-200 focus:border-[var(--c)] rounded-xl px-4 py-2.5 text-sm outline-none transition uppercase"
                   style="text-transform:uppercase">
            <button @click="applyCoupon" :disabled="couponLoading" type="button"
                    class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-gray-100 hover:bg-gray-200 transition text-gray-700 flex-shrink-0">
              <span x-text="couponLoading ? '…' : 'Aplicar'"></span>
            </button>
          </div>
          <div x-show="couponApplied" class="flex items-center justify-between bg-green-50 border border-green-200 rounded-xl px-4 py-2.5 text-sm">
            <div>
              <span class="font-mono font-bold text-green-700" x-text="couponApplied ? couponApplied.code : ''"></span>
              <span class="text-green-600 ml-1">&mdash;
                <span x-text="couponApplied && couponApplied.type==='percent' ? couponApplied.value+'% desc.' : 'S/ '+(couponApplied?couponApplied.value:0).toFixed(2)+' desc.'"></span>
              </span>
            </div>
            <button @click="removeCoupon" type="button" class="text-gray-400 hover:text-red-500 ml-3 text-lg leading-none">&times;</button>
          </div>
          <p x-show="couponError" class="text-red-500 text-xs mt-1" x-text="couponError"></p>
        </div>

        {{-- Resumen de costos --}}
        <div x-show="shippingEnabled || couponApplied" class="bg-gray-50 rounded-xl px-4 py-3 space-y-1.5 text-sm">
          <div class="flex justify-between text-gray-500">
            <span>Subtotal</span>
            <span x-text="'S/ ' + subtotal.toFixed(2)"></span>
          </div>
          <div x-show="couponApplied && couponDiscount > 0" class="flex justify-between text-green-600 font-medium">
            <span>Descuento</span>
            <span x-text="'- S/ ' + couponDiscount.toFixed(2)"></span>
          </div>
          <div x-show="shippingEnabled" class="flex justify-between" :class="effectiveShipping === 0 && shippingFreeFrom > 0 ? 'text-green-600 font-medium' : 'text-gray-500'">
            <span x-text="effectiveShipping === 0 && shippingFreeFrom > 0 ? '🎉 Envío gratis' : 'Envío'"></span>
            <span x-text="effectiveShipping > 0 ? 'S/ ' + effectiveShipping.toFixed(2) : 'Gratis'"></span>
          </div>
          @if($shippingFreeFrom > 0)
          <p x-show="effectiveShipping > 0" class="text-xs text-gray-400">
            Agrega S/ <span x-text="Math.max(0, {{ $shippingFreeFrom }} - subtotal).toFixed(2)"></span> más para envío gratis
          </p>
          @endif
          <div class="flex justify-between font-black text-gray-900 border-t border-gray-200 pt-1.5">
            <span>Total</span>
            <span x-text="'S/ ' + orderGrandTotal.toFixed(2)"></span>
          </div>
        </div>
        {{-- Si no hay envío ni cupón, mostrar solo total --}}
        <div x-show="!shippingEnabled && !couponApplied" class="flex justify-between font-black text-gray-900 text-sm px-1">
          <span>Total</span>
          <span x-text="'S/ ' + orderGrandTotal.toFixed(2)"></span>
        </div>

        <p x-show="orderError" class="text-red-500 text-xs text-center font-medium" x-text="orderError"></p>
      </div>

      {{-- Éxito --}}
      <div x-show="orderSent" class="flex-1 flex flex-col items-center justify-center px-5 py-8 text-center">
        <div class="w-20 h-20 bg-green-100 rounded-3xl flex items-center justify-center mb-5">
          <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        @if($isQuoteOnly)
        <p class="font-black text-gray-900 text-xl mb-2">¡Cotización enviada!</p>
        <p class="text-sm text-gray-500 mb-4 leading-relaxed">Recibimos tu solicitud y te enviaremos los precios a la brevedad.</p>
        @else
        <p class="font-black text-gray-900 text-xl mb-2">¡Pedido confirmado!</p>
        <p class="text-sm text-gray-500 mb-1 leading-relaxed">Recibimos tu pedido y nos pondremos en contacto muy pronto.</p>
        <p x-show="orderId" class="text-xs text-gray-400 mb-4">Pedido N° <span class="font-black text-gray-700" x-text="orderId"></span></p>
        @endif
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
                class="btn-p px-8 py-3 rounded-xl text-sm font-bold">
          {{ $isQuoteOnly ? 'Seguir explorando' : 'Seguir comprando' }}
        </button>
      </div>

      {{-- Footer fijo paso 2 --}}
      <div x-show="!orderSent" class="border-t border-gray-100 px-5 py-4 flex-shrink-0">
        @if(!$isQuoteOnly && $hasOnlinePayment)
        {{-- Con pagos: primero crear el pedido y luego ir al paso 3 --}}
        <button @click="submitOrder()" :disabled="orderLoading"
                class="w-full py-4 rounded-xl font-black text-sm transition disabled:opacity-60 flex items-center justify-center gap-2 btn-p">
          <template x-if="!orderLoading">
            <span class="flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        {{-- Sin pasarela: comportamiento original --}}
        <button @click="submitOrder()" :disabled="orderLoading"
                class="w-full py-4 rounded-xl font-black text-sm transition disabled:opacity-60 flex items-center justify-center gap-2
                       {{ $isQuoteOnly ? 'bg-[#25D366] hover:bg-[#20ba5a] text-white' : 'btn-p' }}">
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
          <span x-show="!orderLoading">{{ $isQuoteOnly ? ($quoteWa ? 'Enviar cotización por WhatsApp' : 'Solicitar cotización') : 'Confirmar pedido' }}</span>
          <span x-show="orderLoading">Enviando...</span>
        </button>
        @endif
      </div>
    </div>

  @if(!$isQuoteOnly && $hasOnlinePayment)
    {{-- ══════════════════════════════════════
         PASO 3 — Pago en línea
    ══════════════════════════════════════ --}}
    <div x-show="drawerStep === 3" class="flex flex-col flex-1 overflow-hidden">

      {{-- Sin pagar aún --}}
      <div x-show="!orderSent" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">

        {{-- Resumen mini --}}
        <div class="bg-gray-50 rounded-2xl px-4 py-3 flex justify-between items-center">
          <span class="text-sm text-gray-600 font-medium">Total a pagar</span>
          <span class="font-black text-lg price-p" x-text="'S/ ' + orderTotal.toFixed(2)"></span>
        </div>

        <p x-show="payError" class="text-red-500 text-xs text-center font-medium px-2" x-text="payError"></p>

        {{-- ── MÉTODOS MANUALES ── --}}
        @if($payManualEnabled && count($payManualMethods) > 0)
        <div class="space-y-2">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pago manual</p>

          @foreach($payManualMethods as $mKey)
          @php
            $mMeta = ['yape'=>['label'=>'Yape','emoji'=>'🟣','color'=>'#7c3aed'],'plin'=>['label'=>'Plin','emoji'=>'🔵','color'=>'#0284c7'],'transferencia'=>['label'=>'Transferencia bancaria','emoji'=>'🏦','color'=>'#0891b2'],'qr'=>['label'=>'Pago con QR','emoji'=>'📲','color'=>'#059669'],'contra_entrega'=>['label'=>'Contra entrega','emoji'=>'🚚','color'=>'#b45309']];
            $mm = $mMeta[$mKey] ?? null;
            $mmDetails = match($mKey) {
              'yape' => $payYapeNumber,
              'plin' => $payPlinNumber,
              'transferencia' => $payBankDetails,
              default => '',
            };
          @endphp
          @if($mm)
          <div x-data="{ open: false }" class="border-2 border-gray-200 rounded-2xl overflow-hidden">
            <button @click="open = !open; if(open) selectedPayMethod='{{ $mKey }}'"
                    class="w-full flex items-center gap-3 px-4 py-3 text-left transition hover:bg-gray-50"
                    :class="selectedPayMethod==='{{ $mKey }}' ? 'bg-purple-50 border-b border-purple-100' : ''">
              <span class="text-2xl leading-none">{{ $mm['emoji'] }}</span>
              <div class="flex-1">
                <p class="text-sm font-bold text-gray-800">{{ $mm['label'] }}</p>
                @if($mmDetails)<p class="text-xs text-gray-500 truncate">{{ Str::limit($mmDetails, 40) }}</p>@endif
              </div>
              <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''"
                   fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
            <div x-show="open" class="px-4 pb-4 pt-3 space-y-3 bg-gray-50">
              @if($mmDetails)
              <div class="bg-white border border-gray-200 rounded-xl p-3">
                <p class="text-xs font-semibold text-gray-600 mb-1">Datos para pagar:</p>
                <p class="text-sm text-gray-800 whitespace-pre-line">{{ $mmDetails }}</p>
              </div>
              @endif
              @if($payManualInstr)
              <p class="text-xs text-gray-500 italic">{{ $payManualInstr }}</p>
              @endif
              {{-- Campo número de operación --}}
              <div>
                <label class="text-xs font-semibold text-gray-600 block mb-1">Número de operación / referencia *</label>
                <input x-model="payReference" type="text" placeholder="Ej: 123456789"
                       class="w-full border-2 border-gray-200 focus:border-[var(--c)] rounded-xl px-4 py-2.5 text-sm outline-none transition">
              </div>
              <button @click="confirmManualPay()" :disabled="payLoading || !payReference.trim()"
                      class="w-full btn-p py-3 rounded-xl font-black text-sm transition disabled:opacity-50 flex items-center justify-center gap-2">
                <svg x-show="payLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span x-show="!payLoading">Ya pagué — confirmar</span>
                <span x-show="payLoading">Confirmando...</span>
              </button>
            </div>
          </div>
          @endif
          @endforeach
        </div>
        @endif

        {{-- ── CULQI ── --}}
        @if($culqiEnabled && $culqiPublicKey)
        <div class="space-y-2">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pago con tarjeta</p>
          <button @click="openCulqi()" :disabled="payLoading"
                  class="w-full flex items-center gap-3 px-4 py-3.5 border-2 border-gray-200 rounded-2xl text-left hover:border-indigo-400 transition disabled:opacity-50">
            <span class="text-2xl leading-none">💳</span>
            <div class="flex-1">
              <p class="text-sm font-bold text-gray-800">Tarjeta crédito / débito</p>
              <p class="text-xs text-gray-500">Visa, Mastercard — pago seguro vía Culqi</p>
            </div>
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
        {{-- Script Culqi --}}
        <script src="https://checkout.culqi.com/js/v4"></script>
        @endif

        {{-- ── MERCADO PAGO ── --}}
        @if($mpEnabled)
        <div class="space-y-2">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Mercado Pago</p>
          <button @click="openMercadoPago()" :disabled="payLoading"
                  class="w-full flex items-center gap-3 px-4 py-3.5 border-2 border-gray-200 rounded-2xl text-left hover:border-blue-400 transition disabled:opacity-50">
            <span class="text-2xl leading-none">🛒</span>
            <div class="flex-1">
              <p class="text-sm font-bold text-gray-800">Mercado Pago</p>
              <p class="text-xs text-gray-500">Tarjetas, wallets, cuotas — seguro y rápido</p>
            </div>
            <svg x-show="!payLoading" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <svg x-show="payLoading" class="w-4 h-4 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
          </button>
        </div>
        @endif

        <p class="text-center text-xs text-gray-400 pt-2">🔒 Tus datos están protegidos y nunca se comparten</p>
      </div>

      {{-- Éxito pago --}}
      <div x-show="orderSent" class="flex-1 flex flex-col items-center justify-center px-5 py-8 text-center">
        <div class="w-20 h-20 bg-green-100 rounded-3xl flex items-center justify-center mb-5">
          <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <p class="font-black text-gray-900 text-xl mb-2">¡Pago registrado!</p>
        <p class="text-sm text-gray-500 mb-6 leading-relaxed">Tu pedido está confirmado. Nos pondremos en contacto contigo pronto.</p>
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
                class="btn-p px-8 py-3 rounded-xl text-sm font-bold">Seguir comprando</button>
      </div>

    </div>
  @endif

  </div>
</div>

{{-- ═══════════════════════════════════════════
     FOOTER
═══════════════════════════════════════════ --}}
<footer class="bg-gray-900 text-gray-400" itemscope itemtype="https://schema.org/WPFooter">
  <div class="max-w-[1400px] mx-auto px-4 py-12 grid grid-cols-1 md:grid-cols-3 gap-10">

    <div>
      <div class="flex items-center gap-3 mb-4">
        @if($project->logo_url)
        <img src="{{ asset('storage/'.$project->logo_url) }}"
             alt="Logo {{ $project->name }}"
             class="h-10 w-10 rounded-xl object-cover"
             loading="lazy" width="40" height="40">
        @else
        <div class="h-10 w-10 rounded-xl btn-p flex items-center justify-center text-white font-black text-lg">
          {{ strtoupper(substr($project->name,0,1)) }}
        </div>
        @endif
        <span class="text-white font-black text-lg">{{ $project->name }}</span>
      </div>
      @if($project->description)
      <p class="text-sm leading-relaxed text-gray-500 line-clamp-4">{{ $project->description }}</p>
      @endif
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}"
         target="_blank" rel="noopener"
         class="inline-flex items-center gap-2 mt-4 bg-[#25D366] hover:bg-[#20ba5a] text-white text-xs font-bold px-4 py-2 rounded-xl transition">
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
        </svg>
        Escribir por WhatsApp
      </a>
      @endif
    </div>

    <div>
      <h4 class="text-white font-bold mb-4 text-sm uppercase tracking-wider">Contacto</h4>
      <div class="space-y-3 text-sm">
        @if($project->phone)
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 opacity-50 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
          </svg>
          <span>{{ $project->phone }}</span>
        </div>
        @endif
        @if($project->address)
        <div class="flex items-start gap-2">
          <svg class="w-4 h-4 opacity-50 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <span>{{ $project->address }}</span>
        </div>
        @endif
      </div>
    </div>

    <div>
      <h4 class="text-white font-bold mb-4 text-sm uppercase tracking-wider">Síguenos</h4>
      <div class="flex flex-wrap gap-2">
        @php
          $footerSocials = [
            ['key'=>'facebook_url',  'label'=>'Facebook',  'color'=>'#1877F2'],
            ['key'=>'instagram_url', 'label'=>'Instagram', 'color'=>'#E1306C'],
            ['key'=>'tiktok_url',    'label'=>'TikTok',    'color'=>'#010101'],
            ['key'=>'youtube_url',   'label'=>'YouTube',   'color'=>'#FF0000'],
            ['key'=>'twitter_url',   'label'=>'X / Twitter','color'=>'#14171A'],
            ['key'=>'linkedin_url',  'label'=>'LinkedIn',  'color'=>'#0A66C2'],
          ];
        @endphp
        @foreach($footerSocials as $soc)
        @if($settings[$soc['key']] ?? null)
        <a href="{{ $settings[$soc['key']] }}" target="_blank" rel="noopener"
           class="px-3 py-1.5 rounded-xl text-white text-xs font-bold hover:opacity-80 transition"
           style="background:{{ $soc['color'] }}"
           aria-label="{{ $project->name }} en {{ $soc['label'] }}">
          {{ $soc['label'] }}
        </a>
        @endif
        @endforeach
      </div>
    </div>

  </div>
  <div class="border-t border-gray-800 py-5 text-center text-xs text-gray-600">
    <span>&copy; {{ date('Y') }} <strong class="text-gray-500">{{ $project->name }}</strong>.</span>
    <span class="mx-2">·</span>
    <span>Catálogo online por <strong class="text-gray-500">AVAN</strong></span>
  </div>
</footer>

{{-- ═══════════════════════════════════════════
     ALPINE STORE
═══════════════════════════════════════════ --}}
<script>
function store() {
  // Recuperar carrito y form desde localStorage al iniciar
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
    priceFilter: '',
    priceMin: 0,
    priceMax: 0,
    onSaleFilter: false,
    sortBy: 'default',
    filterOpen: false,
    qv: null,
    qvOpen: false,
    expandedCats: {},
    recentlyViewed: [],
    // Predictive search
    searchIndex: @json($searchIndex),
    searchOpen: false,
    searchFocus: false,
    searchIdx: -1,
    get visibleCount() {
        const s = this.search.toLowerCase();
        return this.searchIndex.filter(p => {
            const nm = s === '' || p.name.toLowerCase().includes(s);
            const cm = this.filterCat === '' || p.catId === this.filterCat;
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
    cart: _savedCart,
    form: _savedForm,
    orderLoading: false,
    orderSent: false,
    toastShow: false,
    toastMsg: '',
    toastTimer: null,
    orderError: '',
    noResults: false,
    // Envío
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
    // Cupones
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
    // Pago
    orderId: null,
    orderTotal: 0,
    selectedPayMethod: '',
    payReference: '',
    payLoading: false,
    payError: '',

    init() {
      this.$watch('cart', val => {
        try { localStorage.setItem(this._cartKey, JSON.stringify(val)); } catch(e) {}
      });
      this.$watch('form', val => {
        try { localStorage.setItem(this._formKey, JSON.stringify(val)); } catch(e) {}
      }, { deep: true });
      this.$watch('sortBy', () => { this.applySort(); this._syncUrl(); });
      // Cargar vistos recientemente
      try {
        const _rv = JSON.parse(localStorage.getItem('rv_{{ $project->slug }}') || '[]');
        this.recentlyViewed = _rv.filter(x => x && x.id);
      } catch(e) {}
      const _p = new URLSearchParams(window.location.search);
      if (_p.get('q'))     this.search       = _p.get('q');
      if (_p.get('cat'))   this.filterCat    = _p.get('cat');
      if (_p.get('price')) this.priceFilter  = _p.get('price');
      if (_p.get('pmin'))  this.priceMin     = parseFloat(_p.get('pmin')) || 0;
      if (_p.get('pmax'))  this.priceMax     = parseFloat(_p.get('pmax')) || 0;
      if (_p.get('sale'))  this.onSaleFilter = _p.get('sale') === '1';
      if (_p.get('sort'))  { this.sortBy = _p.get('sort'); this.$nextTick(() => this.applySort()); }
      // Sync URL when filters change
      this.$watch('search',       () => this._syncUrl());
      this.$watch('filterCat',    () => this._syncUrl());
      this.$watch('priceFilter',  () => this._syncUrl());
      this.$watch('onSaleFilter', () => this._syncUrl());
    },

    _syncUrl() {
      const p = new URLSearchParams();
      if (this.search)        p.set('q',     this.search);
      if (this.filterCat)     p.set('cat',   this.filterCat);
      if (this.priceFilter)   p.set('price', this.priceFilter);
      if (this.priceFilter === 'custom' && this.priceMin) p.set('pmin', this.priceMin);
      if (this.priceFilter === 'custom' && this.priceMax) p.set('pmax', this.priceMax);
      if (this.onSaleFilter)  p.set('sale',  '1');
      if (this.sortBy && this.sortBy !== 'default') p.set('sort', this.sortBy);
      history.replaceState(null, '', p.toString() ? '?' + p.toString() : window.location.pathname);
    },

    applySort() {
      document.querySelectorAll('[data-products-grid]').forEach(grid => {
        const cards = [...grid.children];
        cards.sort((a, b) => {
          if (this.sortBy === 'price_asc')  return (parseFloat(a.dataset.price)||0) - (parseFloat(b.dataset.price)||0);
          if (this.sortBy === 'price_desc') return (parseFloat(b.dataset.price)||0) - (parseFloat(a.dataset.price)||0);
          if (this.sortBy === 'newest')     return (parseInt(b.dataset.ts)||0) - (parseInt(a.dataset.ts)||0);
          if (this.sortBy === 'name_az')    return (a.dataset.name||'').localeCompare(b.dataset.name||'', 'es');
          return (parseInt(a.dataset.idx)||0) - (parseInt(b.dataset.idx)||0);
        });
        cards.forEach(c => grid.appendChild(c));
      });
    },

    matchProduct(name, price, comparePrice) {
      const matchSearch = this.search === '' || name.includes(this.search.toLowerCase());
      let matchPrice = true;
      if (this.priceFilter === '0-50')    matchPrice = price <= 50;
      if (this.priceFilter === '50-150')  matchPrice = price > 50 && price <= 150;
      if (this.priceFilter === '150-500') matchPrice = price > 150 && price <= 500;
      if (this.priceFilter === '500+')    matchPrice = price > 500;
      if (this.priceFilter === 'custom') {
        const lo = this.priceMin > 0 ? this.priceMin : 0;
        const hi = this.priceMax > 0 ? this.priceMax : Infinity;
        matchPrice = price >= lo && price <= hi;
      }
      const matchSale = !this.onSaleFilter || (comparePrice && comparePrice > price);
      return matchSearch && matchPrice && matchSale;
    },

    addToCart(product) {
      const key = (product.id + '_' + (product.name || '')).toLowerCase();
      const existing = this.cart.find(i => (i.id + '_' + (i.name || '')).toLowerCase() === key);
      if (existing) {
        existing.qty += (product.qty || 1);
      } else {
        this.cart.push({ ...product, qty: product.qty || 1 });
      }
      // Mostrar toast sin abrir carrito
      this.toastMsg = '✓ ' + product.name + ' agregado';
      this.toastShow = true;
      clearTimeout(this.toastTimer);
      this.toastTimer = setTimeout(() => this.toastShow = false, 2000);
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
      lines += `🛒 *SOLICITUD DE COTIZACIÓN*\n`;
      lines += `━━━━━━━━━━━━━━━━━━━━━━\n`;
      lines += `🏪 *${businessName}*\n\n`;
      lines += `${customMsg}\n\n`;
      lines += `👤 *DATOS DE CONTACTO*\n`;
      lines += `• Nombre: ${this.form.name}\n`;
      if (this.form.phone) lines += `• Teléfono: ${this.form.phone}\n`;
      lines += `\n📦 *PRODUCTOS SOLICITADOS*\n`;
      lines += `━━━━━━━━━━━━━━━━━━━━━━\n`;
      let total = 0;
      this.cart.forEach((item, idx) => {
        @if(!$isQuoteOnly || $quotePriceDisp === 'show')
        const subtotal = (item.price * item.qty).toFixed(2);
        lines += `${idx+1}. *${item.name}*\n   Cant: ${item.qty}  •  S/ ${subtotal}\n`;
        total += item.price * item.qty;
        @else
        lines += `${idx+1}. *${item.name}* — cant: ${item.qty}\n`;
        @endif
      });
      @if(!$isQuoteOnly || $quotePriceDisp === 'show')
      lines += `━━━━━━━━━━━━━━━━━━━━━━\n`;
      lines += `💰 *Total referencial: S/ ${total.toFixed(2)}*\n`;
      @endif
      if (this.form.notes) lines += `\n📝 *Nota:* ${this.form.notes}\n`;
      lines += `\n📅 Fecha: ${fecha}\n`;
      lines += `\n_Cotización generada desde el catálogo online de ${businessName}_`;
      const url = `https://wa.me/{{ $quoteWa }}?text=${encodeURIComponent(lines)}`;
      window.open(url, '_blank');
      // Limpiar carrito tras enviar cotización
      this.cart = [];
      this.orderSent = true;
      try {
        localStorage.removeItem(this._cartKey);
        localStorage.removeItem(this._formKey);
      } catch(e) {}
    },

    async submitOrder() {
      if (!this.form.name.trim() || !this.form.phone.trim()) {
        this.orderError = 'Por favor ingresa tu nombre y teléfono.';
        return;
      }
      this.orderLoading = true;
      this.orderError = '';
      const items = this.cart.map(i => ({
        product_id: i.id,
        name: i.name,
        price: i.price,
        quantity: i.qty,
      }));
      try {
        const res = await fetch('{{ route("public.order", $project->slug) }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({
            client_name: this.form.name,
            client_phone: this.form.phone,
            client_email: this.form.email,
            notes: this.form.notes,
            coupon_code: this.couponApplied ? this.couponApplied.code : null,
            delivery_address: this.form.address || null,
            shipping_cost: this.effectiveShipping > 0 ? this.effectiveShipping : null,
            items: items,
          })
        });
        const data = await res.json();
        if (data.ok) {
          @if($isQuoteOnly && $quoteWa)
          // Cotización con WhatsApp
          this.sendQuoteWhatsapp();
          @elseif(!$isQuoteOnly && $hasOnlinePayment)
          // Pedido con pasarela: guardar order_id y pasar al paso 3
          this.orderId    = data.order_id;
          this.orderTotal = data.total;
          this.drawerStep = 3;
          @else
          // Pedido sin pasarela: éxito directo
          this.couponApplied = null; this.couponCode = '';
          try {
            localStorage.removeItem(this._cartKey);
            localStorage.removeItem(this._formKey);
          } catch(e) {}
          window.location.href = '/{{ $project->slug }}/thanks/' + data.order_id;
          @endif
        } else {
          this.orderError = 'No se pudo enviar. Inténtalo de nuevo.';
        }
      } catch(e) {
        this.orderError = 'Error de conexión. Verifica tu internet e inténtalo de nuevo.';
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
          this.payError = 'No se pudo confirmar el pago. Inténtalo de nuevo.';
        }
      } catch(e) { this.payError = 'Error de conexión.'; }
      this.payLoading = false;
    },

    @if($culqiEnabled && $culqiPublicKey)
    openCulqi() {
      const self = this;
      Culqi.publicKey = '{{ $culqiPublicKey }}';
      Culqi.settings({
        title: '{{ addslashes($project->name) }}',
        currency: 'PEN',
        description: 'Pedido #' + this.orderId,
        amount: Math.round(this.orderTotal * 100),
      });
      Culqi.options({ style: { logo: '' } });
      Culqi.open();
      window.culqi = async function() {
        if (Culqi.token) {
          self.payLoading = true;
          self.payError   = '';
          try {
            const res = await fetch(`{{ url('/' . $project->slug . '/pay') }}/${self.orderId}/culqi`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
              body: JSON.stringify({ token: Culqi.token.id, email: self.form.email }),
            });
            const data = await res.json();
            if (data.ok) {
              self.orderSent = true;
              try { localStorage.removeItem(self._cartKey); localStorage.removeItem(self._formKey); } catch(e) {}
            } else {
              self.payError = data.message || 'Error al procesar el pago.';
            }
          } catch(e) { self.payError = 'Error de conexión.'; }
          self.payLoading = false;
          Culqi.close();
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
          body: JSON.stringify({}),
        });
        const data = await res.json();
        if (data.ok) {
          // Redirigir al checkout de Mercado Pago
          window.location.href = data.init_point;
        } else {
          this.payError = data.message || 'Error al iniciar Mercado Pago.';
        }
      } catch(e) { this.payError = 'Error de conexión.'; }
      this.payLoading = false;
    },
    @endif
  };
}
</script>
</body>
</html>
