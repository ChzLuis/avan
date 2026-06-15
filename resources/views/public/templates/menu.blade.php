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
  $primaryColor     = $settings['primary_color'] ?? '#e4002b';
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
  $seoTitle     = ($settings['seo_title'] ?? null) ?: ($project->name . ' — Menú Online');
  $seoDesc      = ($settings['seo_description'] ?? null) ?: ($project->description ?? 'Explora nuestro menú y haz tu pedido.');
  $ogImage      = $project->logo_url ? asset('storage/'.$project->logo_url) : asset('img/og-default.png');
  $acceptedPayments = json_decode($settings['accepted_payments'] ?? '[]', true) ?? [];
  $paymentMeta = [
      'efectivo'      => ['label'=>'Efectivo',               'emoji'=>'💵'],
      'yape'          => ['label'=>'Yape',                   'emoji'=>'🟣'],
      'plin'          => ['label'=>'Plin',                   'emoji'=>'🔵'],
      'transferencia' => ['label'=>'Transferencia',          'emoji'=>'🏦'],
      'tarjeta'       => ['label'=>'Tarjeta crédito/débito', 'emoji'=>'💳'],
      'qr'            => ['label'=>'Pago QR',                'emoji'=>'📲'],
      'contra_entrega'=> ['label'=>'Contra entrega',         'emoji'=>'🚚'],
  ];
  $footerBg    = $settings['footer_bg_color']    ?? '#111827';
  $footerText  = $settings['footer_text_color']  ?? '#9ca3af';
  $footerLogoH = max(24, (int)($settings['footer_logo_height'] ?? 60));
  $logoRaw     = ($settings['logo_url'] ?? '') ?: ($project->logo_url ?? '');
  $logoSrc     = $logoRaw ? (str_starts_with($logoRaw,'http') ? $logoRaw : asset('storage/'.$logoRaw)) : null;
  $currency    = $settings['currency_symbol'] ?? $settings['currency'] ?? 'S/';
  $heroTitle   = $settings['hero_title']    ?? $project->name;
  $heroSub     = $settings['hero_subtitle'] ?? ($project->description ?? 'Haz tu pedido fácil y rápido');
  $searchIndex = $categories->flatMap(function($cat) use ($project) {
      $rows = $cat->products->map(fn($p) => [
          'id'    => $p->id,
          'name'  => $p->name,
          'price' => (float)$p->price,
          'cp'    => $p->compare_price ? (float)$p->compare_price : null,
          'img'   => $p->mainImage ? $p->main_image_url : null,
          'cat'   => $cat->name,
          'catId' => (string)$cat->id,
          'url'   => route('public.product', [$project->slug, $p->id]),
          'desc'  => \Str::limit(strip_tags($p->description ?? ''), 100),
          'stock' => $p->stock,
      ]);
      $subRows = $cat->children->flatMap(fn($sub) => $sub->products->map(fn($p) => [
          'id'    => $p->id,
          'name'  => $p->name,
          'price' => (float)$p->price,
          'cp'    => $p->compare_price ? (float)$p->compare_price : null,
          'img'   => $p->mainImage ? $p->main_image_url : null,
          'cat'   => $sub->name,
          'catId' => (string)$sub->id,
          'url'   => route('public.product', [$project->slug, $p->id]),
          'desc'  => \Str::limit(strip_tags($p->description ?? ''), 100),
          'stock' => $p->stock,
      ]));
      return $rows->concat($subRows);
  })->values();
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

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@if($culqiEnabled && $culqiPublicKey)
<script src="https://checkout.culqi.com/js/v4"></script>
@endif

<style>
  :root { --c: {{ $primaryColor }}; }
  [x-cloak] { display: none !important; }

  /* Scrollbar oculto */
  .cats-bar, .no-scroll { scrollbar-width: none; -ms-overflow-style: none; }
  .cats-bar::-webkit-scrollbar, .no-scroll::-webkit-scrollbar { display: none; }

  /* Drawer */
  .drawer-overlay { position:fixed;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(3px);z-index:40; }
  .drawer { position:fixed;top:0;right:0;bottom:0;width:min(420px,100vw);background:#fff;z-index:41;display:flex;flex-direction:column;box-shadow:-8px 0 40px rgba(0,0,0,.18); }

  .btn-p { background:var(--c); color:#fff; }
  .btn-p:hover { filter: brightness(.9); }
  .badge { background:var(--c); }

  /* ─── GRID DESKTOP: 4 cols con separador 1px ─── */
  .menu-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1px;
    background: #e5e7eb;
  }
  @media (max-width: 1280px) { .menu-grid { grid-template-columns: repeat(3,1fr); } }
  @media (max-width: 900px)  { .menu-grid { grid-template-columns: repeat(2,1fr); } }

  /* ─── MOBILE: cards horizontales estilo UberEats ─── */
  @media (max-width: 639px) {
    .menu-grid {
      display: flex;
      flex-direction: column;
      background: transparent;
      gap: 0;
    }
    .product-card {
      border-bottom: 1px solid #f3f4f6;
    }
    /* Card horizontal */
    .product-card-inner {
      display: flex !important;
      flex-direction: row !important;
      align-items: stretch;
      min-height: 110px;
    }
    /* Imagen a la DERECHA, cuadrada */
    .product-card-img {
      order: 2;
      width: 130px !important;
      height: 110px !important;
      flex-shrink: 0;
      border-radius: 0 !important;
    }
    .product-card-body {
      order: 1;
      flex: 1;
      padding: 12px 14px !important;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-width: 0;
    }
    /* Ocultar categoría en mobile para ahorrar espacio */
    .product-card .cat-label { display: none; }
    /* Descripción — solo 2 líneas */
    .product-card .prod-desc { -webkit-line-clamp: 2; }
    /* Botón + más grande y accesible en touch */
    .product-card .add-btn {
      width: 36px !important;
      height: 36px !important;
      font-size: 1.25rem !important;
    }
  }

  /* ─── CATEGORIA PILL activa ─── */
  .cat-pill-active {
    border-bottom: 2px solid var(--c);
    color: var(--c);
    font-weight: 700;
  }

  /* ─── Animación add to cart ─── */
  @keyframes bump { 0%,100%{transform:scale(1)} 50%{transform:scale(1.3)} }
  .bump { animation: bump .2s ease; }
</style>
</head>

<body x-data="store()" x-cloak class="bg-gray-50 font-sans antialiased">

{{-- TOAST --}}
<div x-show="toastShow" x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-2"
     class="fixed top-[72px] left-1/2 -translate-x-1/2 z-50 bg-gray-900 text-white text-sm font-semibold px-5 py-2.5 rounded-full shadow-lg whitespace-nowrap pointer-events-none"
     x-text="toastMsg">
</div>

{{-- ═══ HEADER ═══ --}}
<header class="bg-white sticky top-0 z-30 border-b border-gray-100 shadow-sm"
        x-data="{ searchExpanded: false }">
  <div class="max-w-7xl mx-auto px-3 sm:px-4 h-14 flex items-center gap-2 sm:gap-3">

    {{-- Logo / nombre (oculto en mobile cuando búsqueda expandida) --}}
    <a href="{{ $canonicalUrl }}"
       :class="searchExpanded ? 'hidden sm:flex' : 'flex'"
       class="items-center gap-2 flex-shrink-0 min-w-0" aria-label="{{ $project->name }}">
      @if($logoSrc)
        <img src="{{ $logoSrc }}" alt="{{ $project->name }}" class="h-7 sm:h-8 object-contain max-w-[120px] sm:max-w-none" loading="eager">
      @else
        <span class="font-black text-base sm:text-lg truncate max-w-[100px] sm:max-w-none" style="color:var(--c)">{{ $project->name }}</span>
      @endif
    </a>

    {{-- Buscador — expandible en mobile --}}
    <div class="flex-1 relative" @click.outside="searchOpen=false; searchExpanded=false">
      {{-- Mobile: icono que expande --}}
      <button x-show="!searchExpanded"
              @click="searchExpanded=true; $nextTick(()=>$refs.searchInput.focus())"
              class="sm:hidden p-2 rounded-full hover:bg-gray-100 transition text-gray-500">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
        </svg>
      </button>

      {{-- Buscador full (siempre visible en sm+, toggle en mobile) --}}
      <div :class="searchExpanded ? 'flex' : 'hidden sm:flex'"
           class="items-center bg-gray-100 rounded-full px-3 sm:px-4 py-2 gap-2 w-full">
        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
        </svg>
        <input x-ref="searchInput"
               x-model="search" type="search" placeholder="Buscar en el menú..."
               @input="searchOpen = search.trim().length >= 2; if(search.trim().length>=2) _scrollToCatalog()"
               @keydown.escape="searchOpen=false; searchExpanded=false; search=''"
               class="bg-transparent flex-1 text-sm outline-none text-gray-700 placeholder-gray-400 min-w-0 w-full">
        <button x-show="search || searchExpanded"
                @click="search=''; searchOpen=false; searchExpanded=false"
                class="text-gray-400 hover:text-gray-600 flex-shrink-0 p-0.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      {{-- Dropdown búsqueda --}}
      <div x-show="searchOpen && suggestions.length > 0" x-cloak
           x-transition:enter="transition ease-out duration-100"
           x-transition:enter-start="opacity-0 translate-y-1"
           x-transition:enter-end="opacity-100 translate-y-0"
           class="absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl shadow-2xl border border-gray-100 z-[200] overflow-hidden">
        <template x-for="p in suggestions" :key="p.id">
          <a :href="p.url"
             class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 active:bg-gray-100 transition border-b border-gray-50 last:border-0">
            <div class="w-12 h-12 rounded-xl bg-gray-100 flex-shrink-0 overflow-hidden">
              <img x-show="p.img" :src="p.img" class="w-full h-full object-cover">
              <div x-show="!p.img" class="w-full h-full flex items-center justify-center text-gray-300 text-xl">🍽</div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-gray-800 truncate" x-text="p.name"></p>
              <p class="text-xs text-gray-400" x-text="p.cat"></p>
            </div>
            <p class="text-sm font-black flex-shrink-0" style="color:var(--c)" x-text="'{{ $currency }} ' + p.price.toFixed(2)"></p>
          </a>
        </template>
      </div>
    </div>

    {{-- Carrito --}}
    <button @click="drawerOpen=true"
            :class="searchExpanded ? 'hidden sm:flex' : 'flex'"
            class="relative flex-shrink-0 items-center justify-center w-10 h-10 hover:bg-gray-100 rounded-full transition" aria-label="Ver pedido">
      <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
      </svg>
      <span x-show="cartCount > 0" x-text="cartCount"
            class="absolute -top-0.5 -right-0.5 badge text-white text-[10px] font-black rounded-full w-5 h-5 flex items-center justify-center leading-none"></span>
    </button>
  </div>
</header>

{{-- ═══ BARRA DE CATEGORÍAS STICKY ═══ --}}
@if($categories->count())
<nav class="bg-white border-b border-gray-100 sticky top-14 z-20 shadow-sm">
  <div class="max-w-7xl mx-auto">
    <div class="cats-bar flex items-center overflow-x-auto px-2 sm:px-4">
      {{-- "Ver todo" --}}
      <button @click="filterCat=''; _scrollToCatalog()"
              class="flex-shrink-0 flex flex-col items-center gap-0.5 px-3 sm:px-4 py-2.5 text-xs font-semibold transition whitespace-nowrap border-b-2"
              :class="filterCat==='' ? 'border-b-2 font-bold' : 'border-transparent text-gray-500'"
              :style="filterCat==='' ? 'border-color:var(--c);color:var(--c)' : ''">
        <span class="text-base leading-none">🍽️</span>
        <span class="mt-0.5">Todo</span>
      </button>
      @foreach($categories as $cat)
      <button @click="filterCat='{{ $cat->id }}'; _scrollToCatalog()"
              class="flex-shrink-0 flex flex-col items-center gap-0.5 px-3 sm:px-4 py-2.5 text-xs font-semibold transition whitespace-nowrap border-b-2"
              :class="filterCat==='{{ $cat->id }}' ? 'font-bold' : 'border-transparent text-gray-500'"
              :style="filterCat==='{{ $cat->id }}' ? 'border-color:var(--c);color:var(--c)' : ''">
        @php
          $catEmojis = ['🍔','🍕','🍜','🥗','🍣','🍰','🥤','🍗','🌮','🥩','🍱','🥪','🍦','☕'];
          echo '<span class="text-base leading-none">'.$catEmojis[$loop->index % count($catEmojis)].'</span>';
        @endphp
        <span class="mt-0.5">{{ $cat->name }}</span>
      </button>
      @endforeach
    </div>
  </div>
</nav>
@endif

{{-- ═══ CONTENIDO PRINCIPAL ═══ --}}
<main class="max-w-7xl mx-auto" id="catalogo">

  {{-- Banner hero opcional --}}
  @if($settings['hero_image'] ?? null)
  <div class="relative overflow-hidden" style="height:200px">
    <img src="{{ asset('storage/'.$settings['hero_image']) }}" alt="{{ $heroTitle }}"
         class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-black/40 flex flex-col items-start justify-end px-6 pb-6">
      <h1 class="text-white font-black text-2xl leading-tight">{{ $heroTitle }}</h1>
      <p class="text-white/80 text-sm mt-1">{{ $heroSub }}</p>
    </div>
  </div>
  @endif

  {{-- Barra de resultados + ordenar --}}
  <div class="flex items-center justify-between px-3 sm:px-4 py-2.5 border-b border-gray-100 bg-white gap-2">
    <p class="text-xs sm:text-sm text-gray-500 flex-shrink-0">
      <span class="font-bold text-gray-800" x-text="visibleCount"></span>
      <span class="hidden sm:inline"> resultados</span>
    </p>
    <div class="flex items-center gap-2">
      {{-- Filtro en oferta --}}
      <label class="flex items-center gap-1 text-xs sm:text-sm text-gray-600 cursor-pointer select-none bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-1.5 hover:bg-gray-100 transition">
        <input type="checkbox" x-model="onSaleFilter" class="w-3.5 h-3.5 rounded accent-[var(--c)]">
        <span>Ofertas</span>
      </label>
      {{-- Ordenar --}}
      <select x-model="sortBy" @change="applySort()"
              class="text-xs sm:text-sm border border-gray-200 rounded-lg px-2 sm:px-3 py-1.5 outline-none text-gray-700 bg-white cursor-pointer max-w-[110px] sm:max-w-none">
        <option value="default">Ordenar</option>
        <option value="price_asc">Precio ↑</option>
        <option value="price_desc">Precio ↓</option>
        <option value="newest">Nuevos</option>
        <option value="name_az">A → Z</option>
      </select>
    </div>
  </div>

  {{-- Sin resultados --}}
  <div x-show="noResults" x-cloak class="py-20 text-center text-gray-400 px-4">
    <svg class="w-12 h-12 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <p class="font-semibold text-gray-600 mb-1">Sin resultados</p>
    <p class="text-sm">Intenta con otro término o categoría</p>
    <button @click="search='';filterCat='';onSaleFilter=false" class="mt-4 text-sm font-semibold underline" style="color:var(--c)">Limpiar filtros</button>
  </div>

  {{-- Grid de productos --}}
  <div class="menu-grid bg-white" data-products-grid>
    @php $prodIdx = 0; @endphp
    @foreach($categories as $cat)
      @foreach($cat->products as $p)
      @php
        $img = $p->mainImage ? $p->main_image_url : null;
        $imgUrl = $img ?: null;
        $disc = ($p->compare_price && $p->compare_price > $p->price)
          ? round((1 - $p->price / $p->compare_price) * 100)
          : 0;
        $outOfStock = isset($p->stock) && $p->stock !== null && $p->stock <= 0;
      @endphp
      <article class="product-card bg-white"
               data-price="{{ $p->price }}"
               data-name="{{ strtolower($p->name) }}"
               data-ts="{{ $p->created_at?->timestamp ?? 0 }}"
               data-idx="{{ $prodIdx++ }}"
               x-show="filterCat==='' || filterCat==='{{ $cat->id }}' ? matchProduct('{{ addslashes(strtolower($p->name)) }}', {{ (float)$p->price }}, {{ $p->compare_price ? (float)$p->compare_price : 'null' }}) : false"
               x-transition:enter="transition-opacity duration-200"
               x-transition:enter-start="opacity-0"
               x-transition:enter-end="opacity-100">

        {{-- CARD INNER: vertical en desktop, horizontal (img derecha) en mobile --}}
        <div class="product-card-inner flex flex-col sm:flex-col h-full">

          {{-- Imagen — arriba en desktop, derecha en mobile --}}
          <div class="product-card-img relative bg-gray-100 overflow-hidden sm:h-[200px]" style="height:200px">
            @if($imgUrl)
              <img src="{{ $imgUrl }}" alt="{{ $p->name }}"
                   class="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
                   loading="lazy" width="400" height="200">
            @else
              <div class="w-full h-full flex items-center justify-center text-4xl opacity-20">🍽️</div>
            @endif
            @if($disc > 0)
            <span class="absolute top-2 left-2 bg-red-500 text-white text-[11px] font-black px-2 py-0.5 rounded-md leading-tight">
              -{{ $disc }}%
            </span>
            @endif
            @if($outOfStock)
            <div class="absolute inset-0 bg-white/75 flex items-center justify-center">
              <span class="bg-gray-800 text-white text-xs font-bold px-3 py-1 rounded-full">Agotado</span>
            </div>
            @endif
          </div>

          {{-- Info --}}
          <div class="product-card-body flex flex-col flex-1 p-3 sm:p-4">
            <p class="cat-label text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ $cat->name }}</p>
            <h3 class="font-bold text-gray-900 text-sm leading-snug mb-1 line-clamp-2">{{ $p->name }}</h3>
            @if($p->description)
            <p class="prod-desc text-xs text-gray-500 leading-relaxed line-clamp-2 mb-2 flex-1" style="overflow:hidden;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;line-clamp:2">{{ Str::limit(strip_tags($p->description), 100) }}</p>
            @else
            <div class="flex-1 min-h-[4px]"></div>
            @endif
            {{-- Precio + botón + --}}
            <div class="flex items-center justify-between gap-2 mt-auto">
              <div class="min-w-0">
                @if(!$isQuoteOnly || $quotePriceDisp === 'show')
                <div class="flex items-baseline gap-1 flex-wrap">
                  <span class="text-base font-black leading-none" style="color:var(--c)">{{ $currency }} {{ number_format($p->price,2) }}</span>
                  @if($p->compare_price && $p->compare_price > $p->price)
                  <span class="text-[11px] text-gray-400 line-through">{{ $currency }} {{ number_format($p->compare_price,2) }}</span>
                  @endif
                </div>
                @else
                <span class="text-xs text-gray-400 italic">A cotizar</span>
                @endif
              </div>
              @if(!$outOfStock)
              <button @click="addToCart({id:{{ $p->id }},name:'{{ addslashes($p->name) }}',price:{{ (float)$p->price }},img:'{{ $imgUrl ?? '' }}'})"
                      class="add-btn w-9 h-9 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-white font-black text-xl sm:text-lg transition active:scale-90 flex-shrink-0 shadow"
                      style="background:var(--c)"
                      aria-label="Agregar {{ $p->name }}">+</button>
              @else
              <span class="add-btn w-9 h-9 sm:w-8 sm:h-8 rounded-full flex items-center justify-center bg-gray-200 text-gray-400 text-xl sm:text-lg font-black flex-shrink-0">+</span>
              @endif
            </div>
          </div>

        </div>
      </article>
      @endforeach

      {{-- Subcategorías --}}
      @foreach($cat->children as $sub)
        @foreach($sub->products as $p)
        @php
          $img    = $p->mainImage ? $p->main_image_url : null;
          $imgUrl = $img ?: null;
          $disc   = ($p->compare_price && $p->compare_price > $p->price)
            ? round((1 - $p->price / $p->compare_price) * 100) : 0;
          $outOfStock = isset($p->stock) && $p->stock !== null && $p->stock <= 0;
        @endphp
        <article class="product-card bg-white"
                 data-price="{{ $p->price }}"
                 data-name="{{ strtolower($p->name) }}"
                 data-ts="{{ $p->created_at?->timestamp ?? 0 }}"
                 data-idx="{{ $prodIdx++ }}"
                 x-show="filterCat==='' || filterCat==='{{ $cat->id }}' || filterCat==='{{ $sub->id }}' ? matchProduct('{{ addslashes(strtolower($p->name)) }}', {{ (float)$p->price }}, {{ $p->compare_price ? (float)$p->compare_price : 'null' }}) : false">

          <div class="product-card-inner flex flex-col sm:flex-col h-full">
            <div class="product-card-img relative bg-gray-100 overflow-hidden sm:h-[200px]" style="height:200px">
              @if($imgUrl)
                <img src="{{ $imgUrl }}" alt="{{ $p->name }}" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300" loading="lazy">
              @else
                <div class="w-full h-full flex items-center justify-center text-4xl opacity-20">🍽️</div>
              @endif
              @if($disc > 0)
              <span class="absolute top-2 left-2 bg-red-500 text-white text-[11px] font-black px-2 py-0.5 rounded-md">-{{ $disc }}%</span>
              @endif
              @if($outOfStock)
              <div class="absolute inset-0 bg-white/75 flex items-center justify-center">
                <span class="bg-gray-800 text-white text-xs font-bold px-3 py-1 rounded-full">Agotado</span>
              </div>
              @endif
            </div>
            <div class="product-card-body flex flex-col flex-1 p-3 sm:p-4">
              <p class="cat-label text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ $sub->name }}</p>
              <h3 class="font-bold text-gray-900 text-sm leading-snug mb-1 line-clamp-2">{{ $p->name }}</h3>
              @if($p->description)
              <p class="prod-desc text-xs text-gray-500 leading-relaxed mb-2 flex-1" style="overflow:hidden;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;line-clamp:2">{{ Str::limit(strip_tags($p->description), 100) }}</p>
              @else
              <div class="flex-1 min-h-[4px]"></div>
              @endif
              <div class="flex items-center justify-between gap-2 mt-auto">
                <div class="min-w-0">
                  @if(!$isQuoteOnly || $quotePriceDisp === 'show')
                  <div class="flex items-baseline gap-1 flex-wrap">
                    <span class="text-base font-black leading-none" style="color:var(--c)">{{ $currency }} {{ number_format($p->price,2) }}</span>
                    @if($p->compare_price && $p->compare_price > $p->price)
                    <span class="text-[11px] text-gray-400 line-through">{{ $currency }} {{ number_format($p->compare_price,2) }}</span>
                    @endif
                  </div>
                  @else
                  <span class="text-xs text-gray-400 italic">A cotizar</span>
                  @endif
                </div>
                @if(!$outOfStock)
                <button @click="addToCart({id:{{ $p->id }},name:'{{ addslashes($p->name) }}',price:{{ (float)$p->price }},img:'{{ $imgUrl ?? '' }}'})"
                        class="add-btn w-9 h-9 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-white font-black text-xl sm:text-lg transition active:scale-90 flex-shrink-0 shadow"
                        style="background:var(--c)"
                        aria-label="Agregar {{ $p->name }}">+</button>
                @else
                <span class="add-btn w-9 h-9 sm:w-8 sm:h-8 rounded-full flex items-center justify-center bg-gray-200 text-gray-400 text-xl font-black flex-shrink-0">+</span>
                @endif
              </div>
            </div>
          </div>
        </article>
        @endforeach
      @endforeach
    @endforeach
  </div>

</main>

{{-- ═══ FOOTER ═══ --}}
@include('public.partials.footer', ['footerBg'=>$footerBg,'footerText'=>$footerText,'footerLogoH'=>$footerLogoH,'logoSrc'=>$logoSrc])

{{-- ═══ BARRA FLOTANTE PEDIDO ═══ --}}
<div x-show="cartCount > 0" x-cloak
     x-transition:enter="transition ease-out duration-200 transform"
     x-transition:enter-start="translate-y-full"
     x-transition:enter-end="translate-y-0"
     x-transition:leave="transition ease-in duration-150 transform"
     x-transition:leave-start="translate-y-0"
     x-transition:leave-end="translate-y-full"
     class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-100 shadow-[0_-4px_20px_rgba(0,0,0,.1)] px-4 py-3 flex items-center gap-4">
  <div class="flex items-center gap-2.5 flex-1 min-w-0">
    <span class="text-white text-xs font-black w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 badge"
          x-text="cartCount"></span>
    <div>
      <p class="text-[10px] text-gray-400 leading-none mb-0.5">{{ $isQuoteOnly ? 'Productos a cotizar' : 'Total del pedido' }}</p>
      <p class="font-black text-base leading-none" style="color:var(--c)"
         x-text="'{{ $currency }} ' + cartTotal.toFixed(2)"></p>
    </div>
  </div>
  <button @click="drawerOpen=true"
          class="btn-p px-5 py-3 rounded-xl font-black text-sm flex items-center gap-2 flex-shrink-0">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
    </svg>
    {{ $isQuoteOnly ? 'Ver cotización' : 'Ver pedido' }}
  </button>
</div>

{{-- ═══ CART DRAWER ═══ --}}
<div x-show="drawerOpen" x-cloak>
  <div class="drawer-overlay" @click="drawerOpen=false"></div>
  <div class="drawer"
       x-show="drawerOpen"
       x-transition:enter="transition ease-out duration-300 transform"
       x-transition:enter-start="translate-x-full"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-200 transform"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="translate-x-full">

    {{-- Header drawer --}}
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
      <div class="flex items-center gap-2">
        <button x-show="(drawerStep===2||drawerStep===3)&&!orderSent" @click="drawerStep--"
                class="p-1.5 hover:bg-gray-100 rounded-lg transition mr-1">
          <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <h2 class="font-black text-gray-900 text-base"
            x-text="drawerStep===1 ? '{{ $isQuoteOnly ? 'Mi cotización' : 'Tu pedido' }}' : (drawerStep===2 ? 'Confirmar datos' : 'Pagar')"></h2>
        <span x-show="cart.length && drawerStep===1"
              class="badge text-white text-xs px-2 py-0.5 rounded-full font-black" x-text="cart.length + ' items'"></span>
      </div>
      <button @click="drawerOpen=false" class="p-2 hover:bg-gray-100 rounded-xl transition">
        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    {{-- PASO 1: Carrito --}}
    <div x-show="drawerStep===1" class="flex flex-col flex-1 overflow-hidden">
      <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <template x-if="cart.length===0">
          <div class="text-center py-16 text-gray-400">
            <svg class="w-14 h-14 mx-auto mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="font-bold text-gray-600 mb-1">{{ $isQuoteOnly ? 'Tu cotización está vacía' : 'Tu pedido está vacío' }}</p>
            <p class="text-sm">Agrega productos para comenzar</p>
          </div>
        </template>
        <template x-for="(item, i) in cart" :key="item.id">
          <div class="flex items-center gap-3 bg-gray-50 rounded-2xl p-3 border border-gray-100">
            <div class="w-14 h-14 rounded-xl overflow-hidden flex-shrink-0 bg-gray-200">
              <img :src="item.img" x-show="item.img" class="w-full h-full object-cover" :alt="item.name">
              <div x-show="!item.img" class="w-full h-full flex items-center justify-center text-gray-400 text-lg">🍽</div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-gray-800 line-clamp-2" x-text="item.name"></p>
              @if(!$isQuoteOnly || $quotePriceDisp === 'show')
              <p class="text-sm font-black mt-0.5" style="color:var(--c)" x-text="'{{ $currency }} ' + (item.price * item.qty).toFixed(2)"></p>
              @endif
            </div>
            <div class="flex items-center gap-1 flex-shrink-0">
              <button @click="item.qty > 1 ? item.qty-- : cart.splice(i,1)"
                      class="w-8 h-8 rounded-full border-2 border-gray-200 hover:border-red-300 hover:bg-red-50 text-gray-600 hover:text-red-500 font-bold text-sm transition flex items-center justify-center">
                <span x-text="item.qty > 1 ? '−' : '×'"></span>
              </button>
              <span class="w-7 text-center text-sm font-black text-gray-800" x-text="item.qty"></span>
              <button @click="item.qty++"
                      class="w-8 h-8 rounded-full text-white font-bold text-sm transition flex items-center justify-center btn-p">+</button>
            </div>
          </div>
        </template>
      </div>

      <div x-show="cart.length > 0" class="border-t border-gray-100 px-5 py-4 space-y-3 flex-shrink-0">
        @if(!$isQuoteOnly || $quotePriceDisp === 'show')
        <div class="flex justify-between items-center">
          <span class="text-sm text-gray-500"><span x-text="cartCount"></span> productos</span>
          <span class="font-black text-lg" style="color:var(--c)" x-text="'{{ $currency }} ' + cartTotal.toFixed(2)"></span>
        </div>
        @endif
        @if(!$isQuoteOnly && count($acceptedPayments) > 0)
        <div class="flex flex-wrap gap-1.5">
          @foreach($acceptedPayments as $pmKey)
          @if(isset($paymentMeta[$pmKey]))
          <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-lg bg-gray-100 text-gray-600 font-medium">
            {{ $paymentMeta[$pmKey]['emoji'] }} {{ $paymentMeta[$pmKey]['label'] }}
          </span>
          @endif
          @endforeach
        </div>
        @endif
        <button @click="drawerStep=2; orderError=''"
                class="w-full btn-p py-3.5 rounded-2xl font-black text-sm transition flex items-center justify-center gap-2">
          {{ $isQuoteOnly ? 'Continuar y cotizar' : 'Continuar y confirmar' }}
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
      </div>
    </div>

    {{-- PASO 2: Datos --}}
    <div x-show="drawerStep===2" class="flex flex-col flex-1 overflow-hidden">
      <div x-show="!orderSent" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <div class="rounded-2xl px-4 py-3 flex justify-between items-center" style="background:color-mix(in srgb, var(--c) 10%, white); border:1px solid color-mix(in srgb, var(--c) 20%, white)">
          <span class="text-sm text-gray-600"><span x-text="cartCount"></span> productos</span>
          @if(!$isQuoteOnly || $quotePriceDisp === 'show')
          <span class="font-black" style="color:var(--c)" x-text="'{{ $currency }} ' + cartTotal.toFixed(2)"></span>
          @endif
        </div>
        <input x-model="form.name" type="text" placeholder="Tu nombre completo *"
               class="w-full border-2 border-gray-200 focus:border-[var(--c)] rounded-xl px-4 py-2.5 text-sm outline-none transition" autocomplete="name">
        <input x-model="form.phone" type="tel" placeholder="Tu WhatsApp / teléfono *"
               class="w-full border-2 border-gray-200 focus:border-[var(--c)] rounded-xl px-4 py-2.5 text-sm outline-none transition" autocomplete="tel">
        <input x-model="form.email" type="email" placeholder="Correo electrónico (opcional)"
               class="w-full border-2 border-gray-200 focus:border-[var(--c)] rounded-xl px-4 py-2.5 text-sm outline-none transition" autocomplete="email">
        <textarea x-model="form.notes" rows="2" placeholder="Nota para tu pedido (opcional)"
                  class="w-full border-2 border-gray-200 focus:border-[var(--c)] rounded-xl px-4 py-2.5 text-sm outline-none resize-none transition"></textarea>
        @if($requireAddress)
        <input x-model="form.address" type="text" placeholder="Dirección de entrega *"
               class="w-full border-2 border-gray-200 focus:border-[var(--c)] rounded-xl px-4 py-2.5 text-sm outline-none transition" autocomplete="street-address">
        @endif
        {{-- Cupón --}}
        <div>
          <div x-show="!couponApplied" class="flex gap-2">
            <input x-model="couponCode" @keydown.enter.prevent="applyCoupon" type="text"
                   placeholder="Código de descuento"
                   class="flex-1 border-2 border-gray-200 focus:border-[var(--c)] rounded-xl px-4 py-2.5 text-sm outline-none transition uppercase"
                   style="text-transform:uppercase">
            <button @click="applyCoupon" :disabled="couponLoading"
                    class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-gray-100 hover:bg-gray-200 transition text-gray-700 flex-shrink-0">
              <span x-text="couponLoading ? '…' : 'Aplicar'"></span>
            </button>
          </div>
          <div x-show="couponApplied" class="flex items-center justify-between bg-green-50 border border-green-200 rounded-xl px-4 py-2.5 text-sm">
            <div>
              <span class="font-mono font-bold text-green-700" x-text="couponApplied ? couponApplied.code : ''"></span>
              <span class="text-green-600 ml-1">— <span x-text="couponApplied && couponApplied.type==='percent' ? couponApplied.value+'% desc.' : '{{ $currency }} '+(couponApplied?couponApplied.value:0).toFixed(2)+' desc.'"></span></span>
            </div>
            <button @click="removeCoupon" class="text-gray-400 hover:text-red-500 ml-3 text-lg">&times;</button>
          </div>
          <p x-show="couponError" class="text-red-500 text-xs mt-1" x-text="couponError"></p>
        </div>
        <div x-show="shippingEnabled || couponApplied" class="bg-gray-50 rounded-xl px-4 py-3 space-y-1.5 text-sm">
          <div class="flex justify-between text-gray-500"><span>Subtotal</span><span x-text="'{{ $currency }} ' + subtotal.toFixed(2)"></span></div>
          <div x-show="couponApplied && couponDiscount > 0" class="flex justify-between text-green-600 font-medium">
            <span>Descuento</span><span x-text="'- {{ $currency }} ' + couponDiscount.toFixed(2)"></span>
          </div>
          <div x-show="shippingEnabled" class="flex justify-between" :class="effectiveShipping===0 && shippingFreeFrom>0 ? 'text-green-600 font-medium' : 'text-gray-500'">
            <span x-text="effectiveShipping===0 && shippingFreeFrom>0 ? '🎉 Envío gratis' : 'Envío'"></span>
            <span x-text="effectiveShipping>0 ? '{{ $currency }} '+effectiveShipping.toFixed(2) : 'Gratis'"></span>
          </div>
          @if($shippingFreeFrom > 0)<p x-show="effectiveShipping>0" class="text-xs text-gray-400">Agrega {{ $currency }} <span x-text="Math.max(0,{{ $shippingFreeFrom }}-subtotal).toFixed(2)"></span> más para envío gratis</p>@endif
          <div class="flex justify-between font-black text-gray-900 border-t border-gray-200 pt-1.5"><span>Total</span><span x-text="'{{ $currency }} '+orderGrandTotal.toFixed(2)"></span></div>
        </div>
        <p x-show="orderError" class="text-red-500 text-xs text-center font-medium" x-text="orderError"></p>
      </div>

      <div x-show="orderSent" class="flex-1 flex flex-col items-center justify-center px-5 py-8 text-center">
        <div class="w-20 h-20 bg-green-100 rounded-3xl flex items-center justify-center mb-5">
          <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <p class="font-black text-gray-900 text-xl mb-2">{{ $isQuoteOnly ? '¡Cotización enviada!' : '¡Pedido confirmado!' }}</p>
        <p class="text-sm text-gray-500 mb-6">{{ $isQuoteOnly ? 'Nos pondremos en contacto pronto.' : 'Nos comunicaremos contigo enseguida.' }}</p>
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};drawerOpen=false;try{localStorage.removeItem(_cartKey);localStorage.removeItem(_formKey);}catch(e){}"
                class="btn-p px-8 py-3 rounded-2xl text-sm font-bold transition">
          Seguir explorando
        </button>
      </div>

      <div x-show="!orderSent" class="border-t border-gray-100 px-5 py-4 flex-shrink-0">
        @if(!$isQuoteOnly && $hasOnlinePayment)
        <button @click="submitOrder()" :disabled="orderLoading"
                class="w-full btn-p py-4 rounded-2xl font-black text-sm transition disabled:opacity-60 flex items-center justify-center gap-2">
          <svg x-show="orderLoading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <span x-show="!orderLoading">Continuar al pago</span>
          <span x-show="orderLoading">Procesando...</span>
        </button>
        @else
        <button @click="submitOrder()" :disabled="orderLoading"
                class="w-full py-4 rounded-2xl font-black text-sm transition disabled:opacity-60 flex items-center justify-center gap-2
                       {{ $isQuoteOnly ? 'bg-[#25D366] hover:bg-[#20ba5a] text-white' : 'btn-p' }}">
          <svg x-show="orderLoading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <span x-show="!orderLoading">{{ $isQuoteOnly ? ($quoteWa ? 'Enviar por WhatsApp' : 'Solicitar cotización') : 'Confirmar pedido' }}</span>
          <span x-show="orderLoading">Enviando...</span>
        </button>
        @endif
      </div>
    </div>

    @if(!$isQuoteOnly && $hasOnlinePayment)
    {{-- PASO 3: Pago --}}
    <div x-show="drawerStep===3" class="flex flex-col flex-1 overflow-hidden">
      <div x-show="!orderSent" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <div class="rounded-2xl px-4 py-3 flex justify-between items-center" style="background:color-mix(in srgb, var(--c) 10%, white)">
          <span class="text-sm text-gray-600 font-medium">Total a pagar</span>
          <span class="font-black text-lg" style="color:var(--c)" x-text="'{{ $currency }} ' + orderTotal.toFixed(2)"></span>
        </div>
        <p x-show="payError" class="text-red-500 text-xs text-center font-medium" x-text="payError"></p>

        @if($payManualEnabled && count($payManualMethods) > 0)
        <div class="space-y-2">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pago manual</p>
          @foreach($payManualMethods as $mKey)
          @php
            $mMeta = ['yape'=>['label'=>'Yape','emoji'=>'🟣'],'plin'=>['label'=>'Plin','emoji'=>'🔵'],'transferencia'=>['label'=>'Transferencia bancaria','emoji'=>'🏦'],'qr'=>['label'=>'Pago con QR','emoji'=>'📲'],'contra_entrega'=>['label'=>'Contra entrega','emoji'=>'🚚']];
            $mm = $mMeta[$mKey] ?? null;
            $mmDetails = match($mKey) { 'yape' => $payYapeNumber, 'plin' => $payPlinNumber, 'transferencia' => $payBankDetails, default => '' };
          @endphp
          @if($mm)
          <div x-data="{ open: false }" class="border-2 border-gray-200 rounded-2xl overflow-hidden">
            <button @click="open=!open" class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-gray-50 transition">
              <span class="text-2xl leading-none">{{ $mm['emoji'] }}</span>
              <div class="flex-1">
                <p class="text-sm font-bold text-gray-800">{{ $mm['label'] }}</p>
                @if($mmDetails)<p class="text-xs text-gray-500 truncate">{{ Str::limit($mmDetails,40) }}</p>@endif
              </div>
              <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open?'rotate-180':''"
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
              @if($payManualInstr)<p class="text-xs text-gray-500 italic">{{ $payManualInstr }}</p>@endif
              <div>
                <label class="text-xs font-semibold text-gray-600 block mb-1">Número de operación / referencia *</label>
                <input x-model="payReference" type="text" placeholder="Ej: 123456789"
                       class="w-full border-2 border-gray-200 focus:border-[var(--c)] rounded-xl px-4 py-2.5 text-sm outline-none transition">
              </div>
              <button @click="confirmManualPay()" :disabled="payLoading||!payReference.trim()"
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

        @if($culqiEnabled && $culqiPublicKey)
        <button @click="openCulqi()" :disabled="payLoading"
                class="w-full flex items-center gap-3 px-4 py-3.5 border-2 border-gray-200 rounded-2xl text-left hover:border-[var(--c)] transition disabled:opacity-50">
          <span class="text-2xl">💳</span>
          <div class="flex-1">
            <p class="text-sm font-bold text-gray-800">Tarjeta crédito / débito</p>
            <p class="text-xs text-gray-500">Visa, Mastercard — pago seguro vía Culqi</p>
          </div>
          <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
        @endif

        @if($mpEnabled)
        <button @click="openMercadoPago()" :disabled="payLoading"
                class="w-full flex items-center gap-3 px-4 py-3.5 border-2 border-gray-200 rounded-2xl text-left hover:border-blue-400 transition disabled:opacity-50">
          <span class="text-2xl">🛒</span>
          <div class="flex-1">
            <p class="text-sm font-bold text-gray-800">Mercado Pago</p>
            <p class="text-xs text-gray-500">Tarjetas, wallets, cuotas</p>
          </div>
          <svg x-show="!payLoading" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
          <svg x-show="payLoading" class="w-4 h-4 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
        </button>
        @endif
      </div>

      <div x-show="orderSent" class="flex-1 flex flex-col items-center justify-center px-5 py-8 text-center">
        <div class="w-20 h-20 bg-green-100 rounded-3xl flex items-center justify-center mb-5">
          <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <p class="font-black text-gray-900 text-xl mb-2">¡Pago registrado!</p>
        <p class="text-sm text-gray-500 mb-6">Tu pedido está confirmado.</p>
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};drawerOpen=false;try{localStorage.removeItem(_cartKey);localStorage.removeItem(_formKey);}catch(e){}"
                class="btn-p px-8 py-3 rounded-2xl text-sm font-bold transition">
          Seguir comprando
        </button>
      </div>
    </div>
    @endif
  </div>
</div>

{{-- WhatsApp flotante --}}
@if($project->whatsapp)
<a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}?text={{ urlencode($settings['whatsapp_msg'] ?? 'Hola, quisiera más información') }}"
   target="_blank" rel="noopener"
   class="fixed bottom-20 right-4 z-30 w-12 h-12 rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition-transform"
   style="background:#25D366"
   x-show="cartCount===0"
   title="WhatsApp">
  <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
  </svg>
</a>
@endif

<script>
function store() {
  const _cartKey = 'avan_cart_{{ $project->id }}';
  const _formKey = 'avan_form_{{ $project->id }}';
  let _savedCart = [];
  let _savedForm = { name:'', phone:'', email:'', notes:'', address:'' };
  try {
    const c = localStorage.getItem(_cartKey);
    if (c) _savedCart = JSON.parse(c);
    const f = localStorage.getItem(_formKey);
    if (f) _savedForm = { ...{ name:'', phone:'', email:'', notes:'', address:'' }, ...JSON.parse(f) };
  } catch(e) {}

  return {
    _cartKey, _formKey,
    search: '', filterCat: '',
    onSaleFilter: false, sortBy: 'default',
    searchIndex: @json($searchIndex),
    searchOpen: false,
    drawerOpen: false, drawerStep: 1,
    toastShow: false, toastMsg: '', toastTimer: null,
    cart: _savedCart,
    form: _savedForm,
    orderLoading: false, orderSent: false, orderError: '', noResults: false,
    orderId: null, orderTotal: 0,
    shippingEnabled:  {{ $shippingEnabled  ? 'true' : 'false' }},
    shippingCost:     {{ $shippingCost }},
    shippingFreeFrom: {{ $shippingFreeFrom }},
    requireAddress:   {{ $requireAddress   ? 'true' : 'false' }},
    couponCode: '', couponApplied: null, couponError: '', couponLoading: false,
    selectedPayMethod: '', payReference: '', payLoading: false, payError: '',

    get subtotal()       { return this.cart.reduce((s,i) => s + i.price * i.qty, 0); },
    get cartCount()      { return this.cart.reduce((s,i) => s + i.qty, 0); },
    get cartTotal()      { return this.cart.reduce((s,i) => s + i.price * i.qty, 0); },
    get effectiveShipping() {
      if (!this.shippingEnabled) return 0;
      if (this.shippingFreeFrom > 0 && this.subtotal >= this.shippingFreeFrom) return 0;
      return this.shippingCost;
    },
    get couponDiscount() {
      if (!this.couponApplied) return 0;
      const sub = this.subtotal;
      if (sub < (this.couponApplied.min_order || 0)) return 0;
      if (this.couponApplied.type === 'percent') return Math.min(sub * this.couponApplied.value / 100, sub);
      return Math.min(this.couponApplied.value, sub);
    },
    get orderGrandTotal() { return Math.max(0, this.subtotal - this.couponDiscount + this.effectiveShipping); },

    get suggestions() {
      if (!this.search || this.search.trim().length < 2) return [];
      const q = this.search.toLowerCase().trim();
      return this.searchIndex.filter(p => p.name.toLowerCase().includes(q) || (p.cat && p.cat.toLowerCase().includes(q))).slice(0, 7);
    },

    get visibleCount() {
      return this.searchIndex.filter(p => {
        const nm = !this.search || p.name.toLowerCase().includes(this.search.toLowerCase());
        const cm = !this.filterCat || p.catId === this.filterCat;
        const sm = !this.onSaleFilter || (p.cp && p.cp > p.price);
        return nm && cm && sm;
      }).length;
    },

    init() {
      this.$watch('cart', val => { try { localStorage.setItem(this._cartKey, JSON.stringify(val)); } catch(e) {} });
      this.$watch('form', val => { try { localStorage.setItem(this._formKey, JSON.stringify(val)); } catch(e) {} }, { deep: true });
      this.$watch('search',      () => this.$nextTick(() => this.checkNoResults()));
      this.$watch('filterCat',   () => this.$nextTick(() => this.checkNoResults()));
      this.$watch('onSaleFilter',() => this.$nextTick(() => this.checkNoResults()));
      this.$watch('sortBy', () => this.applySort());
      const _p = new URLSearchParams(window.location.search);
      if (_p.get('q'))   this.search       = _p.get('q');
      if (_p.get('cat')) this.filterCat    = _p.get('cat');
      if (_p.get('sale'))this.onSaleFilter = _p.get('sale') === '1';
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

    _scrollToCatalog() {
      const el = document.getElementById('catalogo');
      if (el && el.getBoundingClientRect().top > 120) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    checkNoResults() {
      const hasFilter = this.search !== '' || this.filterCat !== '' || this.onSaleFilter;
      if (!hasFilter) { this.noResults = false; return; }
      const articles = document.querySelectorAll('[data-products-grid] article');
      const visible  = Array.from(articles).filter(el => el.style.display !== 'none');
      this.noResults = visible.length === 0;
    },

    matchProduct(name, price, comparePrice) {
      if (this.search !== '' && !name.includes(this.search.toLowerCase())) return false;
      if (this.onSaleFilter && !(comparePrice && comparePrice > price)) return false;
      return true;
    },

    addToCart(product) {
      const existing = this.cart.find(i => i.id === product.id);
      if (existing) { existing.qty++; }
      else { this.cart.push({ ...product, qty: 1 }); }
      this.toastMsg = '✔ ' + product.name + ' agregado';
      this.toastShow = true;
      clearTimeout(this.toastTimer);
      this.toastTimer = setTimeout(() => { this.toastShow = false; }, 2000);
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

    sendQuoteWhatsapp() {
      if (!this.form.name.trim()) { this.orderError = 'Por favor ingresa tu nombre primero.'; return; }
      const businessName = `{{ addslashes($project->name) }}`;
      const customMsg    = `{{ addslashes($quoteWaMsg) }}`;
      const fecha = new Date().toLocaleDateString('es-PE', { day:'2-digit', month:'long', year:'numeric' });
      let lines = `🛒 *SOLICITUD DE COTIZACIÓN*\n━━━━━━━━━━━━━━━━━━━━━━\n🪐 *${businessName}*\n\n${customMsg}\n\n👤 *DATOS*\n• Nombre: ${this.form.name}\n`;
      if (this.form.phone) lines += `• Teléfono: ${this.form.phone}\n`;
      lines += `\n📦 *PRODUCTOS*\n━━━━━━━━━━━━━━━━━━━━━━\n`;
      let total = 0;
      this.cart.forEach((item, idx) => {
        @if(!$isQuoteOnly || $quotePriceDisp === 'show')
        const subtotal = (item.price * item.qty).toFixed(2);
        lines += `${idx+1}. *${item.name}*\n   Cant: ${item.qty}  •  S/ ${subtotal}\n`;
        total += item.price * item.qty;
        @else
        lines += `${idx+1}. *${item.name}* – cant: ${item.qty}\n`;
        @endif
      });
      @if(!$isQuoteOnly || $quotePriceDisp === 'show')
      lines += `━━━━━━━━━━━━━━━━━━━━━━\n💰 *Total referencial: S/ ${total.toFixed(2)}*\n`;
      @endif
      if (this.form.notes) lines += `\n📝 Nota: ${this.form.notes}\n`;
      lines += `\n📅 Fecha: ${fecha}\n_Pedido generado desde el menú online de ${businessName}_`;
      window.open(`https://wa.me/{{ $quoteWa }}?text=${encodeURIComponent(lines)}`, '_blank');
      this.cart = [];
      this.orderSent = true;
      try { localStorage.removeItem(this._cartKey); localStorage.removeItem(this._formKey); } catch(e) {}
    },

    async submitOrder() {
      if (!this.form.name.trim() || !this.form.phone.trim()) {
        this.orderError = 'Por favor ingresa tu nombre y teléfono.'; return;
      }
      this.orderLoading = true; this.orderError = '';
      const items = this.cart.map(i => ({ product_id: i.id, name: i.name, price: i.price, quantity: i.qty }));
      try {
        const res = await fetch('{{ route("public.order", $project->slug) }}', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
          body: JSON.stringify({ client_name: this.form.name, client_phone: this.form.phone, client_email: this.form.email, notes: this.form.notes, coupon_code: this.couponApplied?this.couponApplied.code:null, delivery_address: this.form.address||null, shipping_cost: this.effectiveShipping>0?this.effectiveShipping:null, items })
        });
        const data = await res.json();
        if (data.ok) {
          @if($isQuoteOnly && $quoteWa)
          this.sendQuoteWhatsapp();
          @elseif(!$isQuoteOnly && $hasOnlinePayment)
          this.orderId = data.order_id; this.orderTotal = data.total; this.orderSent = false; this.payReference = ''; this.payError = ''; this.drawerStep = 3;
          @else
          try { localStorage.removeItem(this._cartKey); localStorage.removeItem(this._formKey); } catch(e) {}
          window.location.href = '/{{ $project->slug }}/thanks/' + data.order_id;
          @endif
        } else { this.orderError = 'No se pudo enviar. Inténtalo de nuevo.'; }
      } catch(e) { this.orderError = 'Error de conexión.'; }
      this.orderLoading = false;
    },

    async confirmManualPay() {
      if (!this.payReference.trim()) return;
      this.payLoading = true; this.payError = '';
      try {
        const res = await fetch(`{{ url('/' . $project->slug . '/pay') }}/${this.orderId}/manual`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
          body: JSON.stringify({ reference: this.payReference }),
        });
        const data = await res.json();
        if (data.ok) { try { localStorage.removeItem(this._cartKey); localStorage.removeItem(this._formKey); } catch(e) {}; window.location.href = '/{{ $project->slug }}/thanks/' + this.orderId; }
        else { this.payError = 'No se pudo confirmar el pago.'; }
      } catch(e) { this.payError = 'Error de conexión.'; }
      this.payLoading = false;
    },

    @if($culqiEnabled && $culqiPublicKey)
    openCulqi() {
      const self = this;
      Culqi.publicKey = '{{ $culqiPublicKey }}';
      Culqi.settings({ title: '{{ addslashes($project->name) }}', currency: 'PEN', description: 'Pedido #' + this.orderId, amount: Math.round(this.orderTotal * 100) });
      Culqi.options({ style: { logo: '' } });
      Culqi.open();
      window.culqi = async function() {
        if (Culqi.token) {
          self.payLoading = true; self.payError = '';
          try {
            const res = await fetch(`{{ url('/' . $project->slug . '/pay') }}/${self.orderId}/culqi`, {
              method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
              body: JSON.stringify({ token: Culqi.token.id, email: self.form.email }),
            });
            const data = await res.json();
            if (data.ok) { self.orderSent = true; try { localStorage.removeItem(self._cartKey); localStorage.removeItem(self._formKey); } catch(e) {} }
            else { self.payError = data.message || 'Error al procesar el pago.'; }
          } catch(e) { self.payError = 'Error de conexión.'; }
          self.payLoading = false; Culqi.close();
        }
      };
    },
    @endif

    @if($mpEnabled)
    async openMercadoPago() {
      this.payLoading = true; this.payError = '';
      try {
        const res = await fetch(`{{ url('/' . $project->slug . '/pay') }}/${this.orderId}/mp`, {
          method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
          body: JSON.stringify({}),
        });
        const data = await res.json();
        if (data.ok) {
          const url = (data.is_sandbox && data.sandbox_init_point) ? data.sandbox_init_point : data.init_point;
          window.location.href = url;
        } else { this.payError = data.message || 'Error al iniciar Mercado Pago.'; }
      } catch(e) { this.payError = 'Error de conexión.'; }
      this.payLoading = false;
    },
    @endif
  };
}
</script>

</body>
</html>
