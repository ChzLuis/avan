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
  $canonicalUrl   = url('/p/' . $project->slug);
  $ogImage        = $project->logo_url ? asset('storage/'.$project->logo_url) : asset('img/og-default.png');
  $primaryColor   = $settings['primary_color'] ?? '#4f46e5';
  $storeMode      = $settings['store_mode']           ?? 'direct';   // 'direct' | 'quote_only'
  $quotePriceDisp = $settings['quote_price_display']  ?? 'show';     // 'show' | 'hide'
  // Número WA: si hay quote_whatsapp en settings úsalo, si no, whatsapp del proyecto
  $quoteWaRaw = preg_replace('/\D/', '', $settings['quote_whatsapp'] ?? '');
  if (!$quoteWaRaw) {
      $quoteWaRaw = preg_replace('/\D/', '', $project->whatsapp ?? '');
  }
  // Código de país guardado por separado (default 51 = Perú)
  $quoteWaCountry = $settings['quote_whatsapp_country'] ?? '51';
  // Si el número ya empieza con el código de país, úsalo tal cual; si no, anteponlo
  $quoteWa = '';
  if ($quoteWaRaw) {
      $quoteWa = str_starts_with($quoteWaRaw, $quoteWaCountry)
          ? $quoteWaRaw
          : $quoteWaCountry . $quoteWaRaw;
  }
  $quoteWaMsg     = $settings['quote_wa_msg'] ?? 'Hola, me interesa cotizar los siguientes productos:';
  $isQuoteOnly       = $storeMode === 'quote_only';
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

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
:root { --c: {{ $primaryColor }}; }
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
    <div class="flex-1 max-w-2xl mx-auto">
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
               type="search"
               placeholder="Buscar productos, marcas..."
               aria-label="Buscar en el catálogo de {{ $project->name }}"
               autocomplete="off"
               class="flex-1 bg-transparent px-4 py-3 text-sm outline-none min-w-0 placeholder-gray-400">
        <button class="btn-p px-5 py-3 text-sm font-semibold flex-shrink-0 flex items-center gap-1.5 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
          </svg>
          <span class="hidden sm:block">Buscar</span>
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
  <div class="flex items-center gap-6 border-b-2 border-gray-200 mb-6">
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
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4">
      @foreach($newArrivals as $p)
        @include('public.partials.product-card', ['product' => $p, 'projectName' => $project->name, 'isQuoteOnly' => $isQuoteOnly, 'quotePriceDisp' => $quotePriceDisp])
      @endforeach
    </div>
    @else
    <p class="text-gray-400 text-sm py-10 text-center">No hay productos disponibles</p>
    @endif
  </div>

  {{-- En Oferta --}}
  <div x-show="tab==='sale'" x-transition:enter="transition opacity-0 duration-200" x-transition:enter-end="opacity-100">
    @if($onSale->count())
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4">
      @foreach($onSale as $p)
        @include('public.partials.product-card', ['product' => $p, 'projectName' => $project->name, 'isQuoteOnly' => $isQuoteOnly, 'quotePriceDisp' => $quotePriceDisp])
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
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4">
      @foreach($featured as $p)
        @include('public.partials.product-card', ['product' => $p, 'projectName' => $project->name, 'isQuoteOnly' => $isQuoteOnly, 'quotePriceDisp' => $quotePriceDisp])
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
  <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div class="flex items-center gap-3">
      <h2 class="font-black text-gray-900 text-xl">Catálogo completo</h2>
      <span x-show="filterCat!=='' || search!==''"
            class="badge-p text-xs px-2 py-0.5 rounded-full font-semibold">Filtrado</span>
    </div>
    <div class="flex items-center gap-2">
      <span x-show="filterCat!=='' || search!==''" class="text-sm text-gray-500">
        <button @click="filterCat=''; search=''"
                class="text-xs text-red-500 hover:text-red-700 border border-red-200 hover:border-red-400 px-3 py-1.5 rounded-lg transition">
          ✕ Limpiar filtros
        </button>
      </span>
    </div>
  </div>

  {{-- Layout: sidebar + grid --}}
  <div class="flex gap-6">

    {{-- Sidebar filtros (desktop) --}}
    <aside class="w-[200px] flex-shrink-0 hidden xl:block space-y-3">
      <div class="bg-white rounded-2xl border border-gray-200 p-4">
        <p class="font-bold text-gray-800 text-sm mb-3">Precio</p>
        <div class="space-y-2">
          <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="radio" x-model="priceFilter" value="" class="accent-[var(--c)]">
            Todos los precios
          </label>
          <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="radio" x-model="priceFilter" value="0-50" class="accent-[var(--c)]">
            Hasta S/ 50
          </label>
          <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="radio" x-model="priceFilter" value="50-150" class="accent-[var(--c)]">
            S/ 50 — S/ 150
          </label>
          <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="radio" x-model="priceFilter" value="150-500" class="accent-[var(--c)]">
            S/ 150 — S/ 500
          </label>
          <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="radio" x-model="priceFilter" value="500+" class="accent-[var(--c)]">
            Más de S/ 500
          </label>
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

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5 gap-4">
          @foreach($cat->products as $p)
          {{-- SEO: cada producto tiene id anchor para deep-link --}}
          <article id="producto-{{ $p->id }}"
                   class="prod-card bg-white rounded-2xl border border-gray-200 overflow-hidden"
                   x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }})"
                   itemscope itemtype="https://schema.org/Product">

            {{-- Schema.org microdata --}}
            <meta itemprop="name" content="{{ $p->name }}">
            <meta itemprop="sku"  content="{{ $p->sku ?? $p->id }}">
            @if($p->description)<meta itemprop="description" content="{{ Str::limit($p->description, 160) }}">@endif

            {{-- Imagen con SEO --}}
            <div class="prod-img aspect-square bg-gray-50 relative overflow-hidden">
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

              {{-- Badge descuento --}}
              @if($p->compare_price && $p->compare_price > $p->price)
              <span class="absolute top-2 left-2 bg-red-500 text-white text-[10px] font-black px-1.5 py-0.5 rounded-lg leading-none">
                -{{ round((($p->compare_price - $p->price) / $p->compare_price) * 100) }}%
              </span>
              @endif

              {{-- Badge nuevo (últimos 30 días) --}}
              @if($p->created_at && $p->created_at->diffInDays() <= 30)
              <span class="absolute top-2 right-2 badge-p text-[10px] font-black px-1.5 py-0.5 rounded-lg leading-none">
                NUEVO
              </span>
              @endif
            </div>

            {{-- Info --}}
            <div class="p-3" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
              <meta itemprop="priceCurrency" content="PEN">
              <meta itemprop="price" content="{{ $p->price }}">
              <meta itemprop="availability" content="https://schema.org/InStock">

              <p class="text-[11px] text-gray-400 mb-0.5 font-medium">{{ $cat->name }}</p>
              <p class="text-sm font-bold text-gray-900 leading-snug line-clamp-2 mb-2" itemprop="name">{{ $p->name }}</p>

              @if($p->description)
              <p class="text-xs text-gray-500 line-clamp-2 mb-2 leading-relaxed">{{ $p->description }}</p>
              @endif

              @if(!$isQuoteOnly || $quotePriceDisp === 'show')
              <div class="flex items-baseline gap-2 mb-3">
                <span class="price-p font-black text-lg" itemprop="price">S/ {{ number_format($p->price,2) }}</span>
                @if($p->compare_price && $p->compare_price > $p->price)
                <span class="text-xs text-gray-400 line-through font-medium">S/ {{ number_format($p->compare_price,2) }}</span>
                @endif
                @if($isQuoteOnly)
                <span class="text-[10px] text-gray-400 font-medium">(referencial)</span>
                @endif
              </div>
              @else
              <div class="mb-3">
                <span class="text-xs text-gray-400 italic">Precio a consultar</span>
              </div>
              @endif

              <button @click="addToCart({
                        id:{{ $p->id }},
                        name:'{{ addslashes($p->name) }}',
                        price:{{ $p->price }},
                        img:'{{ $p->mainImage ? asset("storage/".$p->mainImage->url) : "" }}'
                      })"
                      class="w-full btn-p py-2 rounded-xl text-sm font-bold transition flex items-center justify-center gap-1.5">
                @if($isQuoteOnly)
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Cotizar
                @else
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Agregar
                @endif
              </button>
            </div>
          </article>
          @endforeach
        </div>
      </div>
      @endif
      @endforeach

      {{-- Sin resultados --}}
      <div x-show="noResults" class="text-center py-20">
        <p class="text-5xl mb-4">🔍</p>
        <p class="font-bold text-gray-700 text-lg mb-1">Sin resultados</p>
        <p class="text-gray-400 text-sm">Intenta con otro término o categoría</p>
        <button @click="search=''; filterCat=''; priceFilter=''; onSaleFilter=false"
                class="mt-4 btn-outline-p px-5 py-2 rounded-xl text-sm font-semibold transition">
          Ver todo el catálogo
        </button>
      </div>

    </div>
  </div>
</section>

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

        <template x-for="(item, i) in cart" :key="item.id">
          <div class="flex items-center gap-3 bg-gray-50 rounded-2xl p-3">
            <div class="w-14 h-14 rounded-xl overflow-hidden flex-shrink-0 bg-gray-200">
              <img :src="item.img" x-show="item.img" class="w-full h-full object-cover" :alt="item.name">
              <div x-show="!item.img" class="w-full h-full flex items-center justify-center text-gray-400 text-xs">IMG</div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-gray-800 leading-snug line-clamp-2" x-text="item.name"></p>
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
        <p class="text-sm text-gray-500 mb-6 leading-relaxed">Recibimos tu solicitud y te enviaremos los precios a la brevedad.</p>
        @else
        <p class="font-black text-gray-900 text-xl mb-2">¡Pedido confirmado!</p>
        <p class="text-sm text-gray-500 mb-6 leading-relaxed">Recibimos tu pedido y nos pondremos en contacto muy pronto.</p>
        @endif
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:''};drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
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
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:''};drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
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
  let _savedForm = { name: '', phone: '', email: '', notes: '' };
  try {
    const c = localStorage.getItem(_cartKey);
    if (c) _savedCart = JSON.parse(c);
    const f = localStorage.getItem(_formKey);
    if (f) _savedForm = { ...{ name:'', phone:'', email:'', notes:'' }, ...JSON.parse(f) };
  } catch(e) {}

  return {
    _cartKey,
    _formKey,
    search: '',
    filterCat: '',
    priceFilter: '',
    onSaleFilter: false,
    drawerOpen: false,
    drawerStep: 1,
    cart: _savedCart,
    form: _savedForm,
    orderLoading: false,
    orderSent: false,
    orderError: '',
    noResults: false,
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
      this.drawerOpen = true;
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
          this.orderSent = true;
          try {
            localStorage.removeItem(this._cartKey);
            localStorage.removeItem(this._formKey);
          } catch(e) {}
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
        const res = await fetch(`/p/{{ $project->slug }}/pay/${this.orderId}/manual`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
          body: JSON.stringify({ reference: this.payReference }),
        });
        const data = await res.json();
        if (data.ok) {
          this.orderSent = true;
          try { localStorage.removeItem(this._cartKey); localStorage.removeItem(this._formKey); } catch(e) {}
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
            const res = await fetch(`/p/{{ $project->slug }}/pay/${self.orderId}/culqi`, {
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
        const res = await fetch(`/p/{{ $project->slug }}/pay/${this.orderId}/mp`, {
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
