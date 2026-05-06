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
  $primaryColor     = $settings['primary_color'] ?? '#111111';
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
  $seoTitle     = ($settings['seo_title'] ?? null) ?: ($project->name . ' — Catálogo Online');
  $seoDesc      = ($settings['seo_description'] ?? null) ?: ($project->description ?? 'Explora nuestros productos.');
  $ogImage      = $project->logo_url ? asset('storage/'.$project->logo_url) : asset('img/og-default.png');
  $acceptedPayments = json_decode($settings['accepted_payments'] ?? '[]', true) ?? [];
  $paymentMeta = [
      'efectivo'      => ['label'=>'Efectivo',              'emoji'=>'💵'],
      'yape'          => ['label'=>'Yape',                  'emoji'=>'🟣'],
      'plin'          => ['label'=>'Plin',                  'emoji'=>'🔵'],
      'transferencia' => ['label'=>'Transferencia',         'emoji'=>'🏦'],
      'tarjeta'       => ['label'=>'Tarjeta crédito/débito','emoji'=>'💳'],
      'qr'            => ['label'=>'Pago QR',               'emoji'=>'📲'],
      'contra_entrega'=> ['label'=>'Contra entrega',        'emoji'=>'🚚'],
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

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

@php
  $secondaryColor = $settings['secondary_color'] ?? '#444444';
  $fontTitle  = $settings['font_title'] ?? $settings['font'] ?? 'Jost';
  $fontBody   = $settings['font_body']  ?? $settings['font'] ?? 'Jost';
  $borderRadius = ['sharp'=>'0px','rounded'=>'8px','pill'=>'50px'][$settings['border_radius'] ?? 'sharp'] ?? '0px';
  $faviconUrl   = $settings['favicon_url'] ?? '';
  $catalogBadgeSale = $settings['catalog_badge_sale'] ?? 'SALE';
  $catalogBadgeNew  = $settings['catalog_badge_new']  ?? 'NEW';
  $btnCartText  = $settings['btn_cart_text']  ?? 'Agregar';
  $btnQuoteText = $settings['btn_quote_text'] ?? 'Cotizar';
  $footerTagline   = $settings['footer_tagline']  ?? '';
  $footerCopyright = $settings['footer_copyright'] ?? ('© ' . date('Y') . ' ' . $project->name);
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
h1,h2,h3,h4,.font-jost { font-family: var(--font-title); }
.btn-p       { background: var(--c); color: #fff; }
.btn-p:hover { filter: brightness(.88); }
.btn-black   { background: #111; color: #fff; }
.btn-black:hover { background: #333; }
.btn-outline-black { border: 2px solid #111; color: #111; }
.btn-outline-black:hover { background: #111; color: #fff; }
.price-p { color: var(--c); }
.badge-p { background: var(--c); color: #fff; }
.ring-p  { outline: 2px solid var(--c); outline-offset: 2px; }
[x-cloak]{ display:none!important; }

/* Drawer */
.drawer-overlay { position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:40;backdrop-filter:blur(3px); }
.drawer { position:fixed;top:0;right:0;height:100%;width:420px;max-width:96vw;background:#fff;z-index:50;display:flex;flex-direction:column;box-shadow:-8px 0 40px rgba(0,0,0,.15); }
@media(max-width:640px){ .drawer{ width:100%; } }

/* Ella card */
.ella-card .ella-img img { transition: transform .4s ease; }
.ella-card:hover .ella-img img { transform: scale(1.08); }
.ella-card .ella-add-btn { transform: translateY(100%); transition: transform .3s ease; opacity: 0; }
.ella-card:hover .ella-add-btn { transform: translateY(0); opacity: 1; }

/* Category scroll */
.cat-scroll { overflow-x: auto; scrollbar-width: none; }
.cat-scroll::-webkit-scrollbar { display:none; }
</style>

@php
$currency = $settings['currency'] ?? 'S/';
$searchIndex = $categories->flatMap(function($cat) use ($project) {
    $rows = $cat->products->map(fn($p) => [
        'id'       => $p->id,
        'name'     => $p->name,
        'price'    => (float)$p->price,
        'cp'       => $p->compare_price ? (float)$p->compare_price : null,
        'img'      => $p->mainImage ? asset('storage/'.$p->mainImage->url) : null,
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
        'img'      => $p->mainImage ? asset('storage/'.$p->mainImage->url) : null,
        'cat'      => $sub->name,
        'catId'    => (string)$sub->id,
        'parentId' => (string)$cat->id,
        'url'      => route('public.product', [$project->slug, $p->id]),
        'desc'     => \Str::limit(strip_tags($p->description ?? ''), 100),
        'stock'    => $p->stock,
    ]));
    return $rows->merge($subRows);
})->values();
@endphp
</head>
<body class="bg-white text-gray-900" x-data="store()" x-cloak>

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

{{-- ═══ ANNOUNCEMENT BAR ═══ --}}
<div class="bg-gray-950 text-gray-300 text-xs py-2 text-center tracking-widest uppercase font-medium">
  <span>Envío gratis en pedidos mayores a S/ 99</span>
  @if($project->phone)
  <span class="mx-4 opacity-40">|</span>
  <span>{{ $project->phone }}</span>
  @endif
</div>

{{-- ═══ HEADER ═══ --}}
<header class="bg-white sticky top-0 z-30 border-b border-gray-100" style="box-shadow:0 2px 12px rgba(0,0,0,.05)">
  <div class="max-w-[1400px] mx-auto px-6 py-4 flex items-center gap-6">

    {{-- Logo --}}
    <a href="{{ $canonicalUrl }}" class="flex items-center gap-3 flex-shrink-0 w-48" aria-label="{{ $project->name }}">
      @if($project->logo_url)
        <img src="{{ asset('storage/'.$project->logo_url) }}" alt="Logo {{ $project->name }}"
             class="h-10 object-contain" width="120" height="40">
      @else
        <span class="font-jost font-black text-gray-900 text-2xl tracking-tight">{{ $project->name }}</span>
      @endif
    </a>

    {{-- Nav categorias (centro) --}}
    <nav class="flex-1 flex items-center justify-center gap-1 hidden md:flex overflow-x-auto cat-scroll">
      <button @click="filterCat=''; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
              :class="filterCat==='' ? 'font-bold text-gray-900 border-b-2 border-gray-900' : 'text-gray-500 hover:text-gray-900'"
              class="px-3 py-1 text-sm whitespace-nowrap transition border-b-2 border-transparent">
        Todo
      </button>
      @foreach($categories as $cat)
      <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
              :class="filterCat==='{{ $cat->id }}' ? 'font-bold text-gray-900 border-b-2 border-gray-900' : 'text-gray-500 hover:text-gray-900'"
              class="px-3 py-1 text-sm whitespace-nowrap transition border-b-2 border-transparent">
        {{ $cat->name }}
      </button>
      @endforeach
    </nav>

    {{-- Acciones --}}
    <div class="flex items-center gap-3 flex-shrink-0 w-48 justify-end">
      {{-- Buscador mini --}}
      <div class="relative hidden lg:block" @click.outside="searchOpen = false">
        <input x-model="search" type="search" placeholder="Buscar..."
               @input="searchOpen = search.trim().length >= 2; searchIdx = -1; if(search.trim().length >= 2) _scrollToCatalog()"
               @keydown.arrow-down.prevent="if(suggestions.length){searchIdx=(searchIdx+1)%suggestions.length}"
               @keydown.arrow-up.prevent="if(suggestions.length){searchIdx=(searchIdx-1+suggestions.length)%suggestions.length}"
               @keydown.enter.prevent="if(searchIdx>=0){selectSuggestion(suggestions[searchIdx])}else{searchOpen=false;_scrollToCatalog()}"
               @keydown.escape="searchOpen=false;searchIdx=-1"
               class="bg-gray-50 border border-gray-200 rounded-full pl-9 pr-4 py-2 text-sm outline-none w-44 focus:w-56 focus:border-gray-400 transition-all">
        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
        </svg>
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
                        :class="searchIdx === i ? 'bg-gray-100' : ''"
                        class="flex items-center gap-3 w-full px-4 py-2.5 hover:bg-gray-50 transition-colors text-left border-b border-gray-100 last:border-0">
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
                    <div class="text-right flex-shrink-0 ml-2">
                        <p class="text-sm font-bold" style="color:var(--c,#4f46e5)" x-text="'{{ $currency }} ' + p.price.toFixed(2)"></p>
                        <p x-show="p.cp && p.cp > p.price" class="text-xs text-gray-400 line-through" x-text="'{{ $currency }} ' + (p.cp||0).toFixed(2)"></p>
                    </div>
                    <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </template>
            <div class="px-4 py-2.5 bg-gray-50 border-t border-gray-100 text-center">
                <button @click="searchOpen=false; _scrollToCatalog()" class="text-xs font-medium" style="color:var(--c,#4f46e5)">
                    Ver todos los resultados →
                </button>
            </div>
        </div>
      </div>

      {{-- WhatsApp --}}
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}"
         target="_blank" rel="noopener" title="WhatsApp"
         class="text-[#25D366] hover:text-[#1da851] transition">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
        </svg>
      </a>
      @endif

      {{-- Carrito --}}
      <button @click="drawerOpen=true" class="relative p-2 hover:bg-gray-100 rounded-full transition" aria-label="Ver carrito">
        <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <span x-show="cartCount > 0" x-text="cartCount"
              class="absolute -top-0.5 -right-0.5 bg-gray-900 text-white text-[10px] font-black rounded-full w-5 h-5 flex items-center justify-center"></span>
      </button>
    </div>
  </div>
</header>

{{-- ═══ HERO FULL WIDTH ═══ --}}
<section class="relative flex items-center overflow-hidden" style="height:75vh;min-height:480px;background:{{ $settings['hero_bg_color'] ?? '#1a1a2e' }};">
  <div class="absolute inset-0 bg-black/40"></div>
  {{-- Decoración geométrica --}}
  <div class="absolute inset-0 opacity-5" style="background-image:repeating-linear-gradient(45deg,#fff 0,#fff 1px,transparent 0,transparent 50%);background-size:30px 30px;"></div>
  <div class="relative z-10 max-w-[1400px] mx-auto px-8 md:px-16 w-full">
    <div class="max-w-xl">
      @if($settings['hero_badge'] ?? null)
      <span class="inline-block text-[10px] font-bold uppercase tracking-[.2em] border border-white/50 text-white/80 px-3 py-1.5 mb-5">
        {{ $settings['hero_badge'] }}
      </span>
      @endif
      <h1 class="font-jost font-black text-white text-5xl md:text-7xl leading-none mb-5 tracking-tight">
        {{ $settings['hero_title'] ?? $project->name }}
      </h1>
      <p class="text-white/70 text-base md:text-lg mb-8 font-light max-w-sm leading-relaxed">
        {{ $settings['hero_subtitle'] ?? 'Descubre nuestra colección exclusiva' }}
      </p>
      <button @click="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
              class="inline-flex items-center gap-3 border-2 border-white text-white px-8 py-3.5 text-sm font-bold uppercase tracking-widest hover:bg-white hover:text-gray-900 transition-all">
        Ver catálogo
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
        </svg>
      </button>
    </div>
  </div>
</section>

{{-- ═══ CATEGORÍAS SCROLL ═══ --}}
@if($categories->count() > 1)
<section class="py-12 border-b border-gray-100">
  <div class="max-w-[1400px] mx-auto px-6">
    <h2 class="font-jost font-black text-2xl text-gray-900 mb-6">Comprar por categoría</h2>
    <div class="flex gap-4 cat-scroll pb-2">
      @foreach($categories as $cat)
      @php
        $catColors = ['#111','#1a1a2e','#2d1b69','#1c3a5c','#1a2c1a','#3d1a1a','#2c1a3d','#1a3d3d'];
        $catColor  = $catColors[$loop->index % count($catColors)];
      @endphp
      <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
              class="flex-shrink-0 group relative overflow-hidden"
              style="width:200px;height:200px;"
              :class="filterCat==='{{ $cat->id }}' ? 'ring-4 ring-offset-2' : ''"
              :style="filterCat==='{{ $cat->id }}' ? 'outline:3px solid var(--c)' : ''">
        <div class="absolute inset-0" style="background:{{ $catColor }};"></div>
        <div class="absolute inset-0 flex flex-col items-center justify-center text-white p-4">
          <span class="font-jost font-black text-5xl opacity-20 select-none">{{ strtoupper(substr($cat->name,0,2)) }}</span>
        </div>
        <div class="absolute bottom-0 left-0 right-0 bg-white/95 py-3 px-4 group-hover:bg-white transition-colors">
          <p class="font-jost font-bold text-gray-900 text-sm truncate">{{ $cat->name }}</p>
          <p class="text-xs text-gray-400">{{ $cat->products->count() }} productos</p>
        </div>
      </button>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ═══ NEW ARRIVALS ═══ --}}
@if($newArrivals->count())
<section class="py-14 max-w-[1400px] mx-auto px-6">
  <div class="flex items-end justify-between mb-8">
    <div>
      <p class="text-xs font-bold uppercase tracking-[.2em] text-gray-400 mb-1">Lo último</p>
      <h2 class="font-jost font-black text-3xl text-gray-900">Novedades</h2>
    </div>
    <button @click="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
            class="text-sm font-semibold text-gray-500 hover:text-gray-900 underline underline-offset-4 transition hidden md:block">
      Ver todos
    </button>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
    @foreach($newArrivals->take(8) as $p)
    <article class="ella-card group cursor-pointer" id="producto-{{ $p->id }}">
      <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="block ella-img relative overflow-hidden bg-gray-50" style="aspect-ratio:3/4;">
        @if($p->mainImage)
        <img src="{{ asset('storage/'.$p->mainImage->url) }}" alt="{{ $p->name }}"
             loading="lazy" class="w-full h-full object-cover">
        @else
        <div class="w-full h-full flex items-center justify-center bg-gray-100">
          <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </div>
        @endif

        {{-- Badge oferta (negro, estilo Ella) --}}
        @if($p->compare_price && $p->compare_price > $p->price)
        <span class="absolute top-3 left-3 bg-gray-900 text-white text-[10px] font-black px-2 py-1 uppercase tracking-wider">
          -{{ round((($p->compare_price - $p->price) / $p->compare_price) * 100) }}%
        </span>
        @endif
        @if($p->stock !== null && $p->stock === 0)
        <span class="absolute bottom-0 left-0 right-0 bg-red-600/90 text-white text-[10px] font-black py-1 text-center tracking-wide z-10">AGOTADO</span>
        @elseif($p->stock !== null && $p->stock > 0 && $p->stock <= 5)
        <span class="absolute bottom-0 left-0 right-0 bg-orange-500/90 text-white text-[10px] font-bold py-1 text-center leading-tight z-10">⚡ CASI AGOTADO — {{ $p->stock }} restantes</span>
        @endif

        {{-- Botón agregar deslizante --}}
        <div class="ella-add-btn absolute bottom-0 left-0 right-0">
          <button @click.stop="addToCart({
                    id:{{ $p->id }},
                    name:'{{ addslashes($p->name) }}',
                    price:{{ $p->price }},
                    img:'{{ $p->mainImage ? asset("storage/".$p->mainImage->url) : "" }}'
                  })"
                  class="w-full bg-gray-900 text-white text-xs font-bold uppercase tracking-widest py-3.5 hover:bg-gray-700 transition">
            {{ $isQuoteOnly ? 'Cotizar' : 'Agregar al carrito' }}
          </button>
        </div>
      </a>

      <div class="pt-3 pb-1">
        @if($p->category)
        <p class="text-[11px] text-gray-400 uppercase tracking-widest mb-0.5 font-medium">{{ $p->category->name }}</p>
        @endif
        <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="font-jost font-semibold text-gray-900 text-sm leading-snug line-clamp-2 hover:underline block">{{ $p->name }}</a>
        @if(!$isQuoteOnly || $quotePriceDisp === 'show')
        <div class="flex items-center gap-2 mt-1.5">
          <span class="font-bold text-gray-900 text-sm">S/ {{ number_format($p->price,2) }}</span>
          @if($p->compare_price && $p->compare_price > $p->price)
          <span class="text-xs text-gray-400 line-through">S/ {{ number_format($p->compare_price,2) }}</span>
          @endif
          @if($p->compare_price && $p->compare_price > $p->price)
          @php $ah = $p->compare_price - $p->price; @endphp
          <p class="text-[10px] text-green-600 font-semibold leading-none mt-1">Ahorras {{ $currency }} {{ number_format($ah,2) }}</p>
          @endif
        </div>
        @else
        <p class="text-xs text-gray-400 mt-1.5 italic">Precio a consultar</p>
        @endif
      </div>
    </article>
    @endforeach
  </div>
</section>
@endif

{{-- ═══ BANNER SPLIT 50/50 ═══ --}}
<section class="max-w-[1400px] mx-auto px-6 py-4 mb-10">
  <div class="grid grid-cols-1 md:grid-cols-2 gap-0 overflow-hidden">
    {{-- Izquierda: color + pattern --}}
    <div class="relative flex items-center justify-center p-12 min-h-[260px] overflow-hidden"
         style="background:var(--c)">
      <div class="absolute inset-0 opacity-10"
           style="background-image:repeating-linear-gradient(-45deg,#fff 0,#fff 1px,transparent 0,transparent 50%);background-size:20px 20px;"></div>
      <div class="absolute inset-0 flex items-center justify-center opacity-5 select-none">
        <span class="font-jost font-black text-white" style="font-size:18rem;line-height:1;">S</span>
      </div>
    </div>
    {{-- Derecha: texto --}}
    <div class="bg-gray-950 flex flex-col items-start justify-center px-12 py-12 min-h-[260px]">
      <p class="text-xs font-bold uppercase tracking-[.25em] text-gray-500 mb-3">Temporada actual</p>
      <h3 class="font-jost font-black text-white text-4xl leading-tight mb-3">
        {{ $settings['banner1_title'] ?? 'Nueva Colección' }}
      </h3>
      <p class="text-gray-400 text-sm leading-relaxed mb-6 max-w-xs">
        {{ $settings['banner1_sub'] ?? 'Descubre los mejores productos de la temporada' }}
      </p>
      <button @click="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
              class="border border-white/30 text-white px-6 py-3 text-xs font-bold uppercase tracking-widest hover:bg-white hover:text-gray-900 transition-all">
        Explorar ahora
      </button>
    </div>
  </div>
</section>

{{-- ═══ EN OFERTA ═══ --}}
@if($onSale->count())
<section class="py-14 max-w-[1400px] mx-auto px-6">
  <div class="flex items-end justify-between mb-8">
    <div>
      <p class="text-xs font-bold uppercase tracking-[.2em] text-gray-400 mb-1">Precios rebajados</p>
      <h2 class="font-jost font-black text-3xl text-gray-900">En Oferta</h2>
    </div>
    <span class="bg-gray-900 text-white text-xs font-bold px-3 py-1.5 uppercase tracking-wider">
      {{ $onSale->count() }} productos
    </span>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
    @foreach($onSale->take(8) as $p)
    <article class="ella-card group cursor-pointer" id="prod-sale-{{ $p->id }}">
      <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="block ella-img relative overflow-hidden bg-gray-50" style="aspect-ratio:3/4;">
        @if($p->mainImage)
        <img src="{{ asset('storage/'.$p->mainImage->url) }}" alt="{{ $p->name }}"
             loading="lazy" class="w-full h-full object-cover">
        @else
        <div class="w-full h-full flex items-center justify-center bg-gray-100">
          <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </div>
        @endif
        @if($p->compare_price && $p->compare_price > $p->price)
        <span class="absolute top-3 left-3 bg-gray-900 text-white text-[10px] font-black px-2 py-1 uppercase tracking-wider">
          -{{ round((($p->compare_price - $p->price) / $p->compare_price) * 100) }}%
        </span>
        @endif
        <div class="ella-add-btn absolute bottom-0 left-0 right-0">
          <button @click.stop="addToCart({
                    id:{{ $p->id }},
                    name:'{{ addslashes($p->name) }}',
                    price:{{ $p->price }},
                    img:'{{ $p->mainImage ? asset("storage/".$p->mainImage->url) : "" }}'
                  })"
                  class="w-full bg-gray-900 text-white text-xs font-bold uppercase tracking-widest py-3.5 hover:bg-gray-700 transition">
            {{ $isQuoteOnly ? 'Cotizar' : 'Agregar al carrito' }}
          </button>
        </div>
      </a>
      <div class="pt-3 pb-1">
        @if($p->category)
        <p class="text-[11px] text-gray-400 uppercase tracking-widest mb-0.5 font-medium">{{ $p->category->name }}</p>
        @endif
        <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="font-jost font-semibold text-gray-900 text-sm leading-snug line-clamp-2 hover:underline block">{{ $p->name }}</a>
        @if(!$isQuoteOnly || $quotePriceDisp === 'show')
        <div class="flex items-center gap-2 mt-1.5">
          <span class="font-bold text-gray-900 text-sm">S/ {{ number_format($p->price,2) }}</span>
          @if($p->compare_price && $p->compare_price > $p->price)
          <span class="text-xs text-gray-400 line-through">S/ {{ number_format($p->compare_price,2) }}</span>
          @endif
          @if($p->compare_price && $p->compare_price > $p->price)
          @php $ah = $p->compare_price - $p->price; @endphp
          <p class="text-[10px] text-green-600 font-semibold leading-none mt-1">Ahorras {{ $currency }} {{ number_format($ah,2) }}</p>
          @endif
        </div>
        @else
        <p class="text-xs text-gray-400 mt-1.5 italic">Precio a consultar</p>
        @endif
      </div>
    </article>
    @endforeach
  </div>
</section>
@endif

{{-- ═══ BANNER 2 ═══ --}}
<section class="max-w-[1400px] mx-auto px-6 py-4 mb-10">
  <div class="relative overflow-hidden flex items-center justify-between px-12 py-14 min-h-[200px]"
       style="background:#111;">
    <div class="absolute inset-0 opacity-5 pointer-events-none"
         style="background-image:radial-gradient(circle,#fff 1px,transparent 1px);background-size:32px 32px;"></div>
    <div class="relative z-10">
      <p class="text-xs font-bold uppercase tracking-[.25em] text-gray-500 mb-2">Tiempo limitado</p>
      <h3 class="font-jost font-black text-white text-3xl md:text-4xl leading-tight">
        {{ $settings['banner2_title'] ?? 'Ofertas Especiales' }}
      </h3>
      <p class="text-gray-400 text-sm mt-2">{{ $settings['banner2_sub'] ?? 'No te pierdas los mejores precios' }}</p>
    </div>
    <button @click="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
            class="relative z-10 flex-shrink-0 border border-gray-600 text-gray-300 px-6 py-3 text-xs font-bold uppercase tracking-widest hover:border-white hover:text-white transition hidden md:block">
      Ver ofertas &rarr;
    </button>
  </div>
</section>

{{-- ═══ BUSCADOR MÓVIL ═══ --}}
<div class="lg:hidden px-4 py-2 bg-white border-b border-gray-100 sticky top-[56px] z-10">
  <div class="relative" @click.outside="searchOpen = false">
    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
    <input x-model="search" type="search" placeholder="Buscar productos..."
           @input="searchOpen = search.trim().length >= 2; searchIdx = -1; if(search.trim().length >= 2) _scrollToCatalog()"
           @keydown.arrow-down.prevent="if(suggestions.length){searchIdx=(searchIdx+1)%suggestions.length}"
           @keydown.arrow-up.prevent="if(suggestions.length){searchIdx=(searchIdx-1+suggestions.length)%suggestions.length}"
           @keydown.enter.prevent="if(searchIdx>=0){selectSuggestion(suggestions[searchIdx])}else{searchOpen=false;_scrollToCatalog()}"
           @keydown.escape="searchOpen=false;searchIdx=-1"
           class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-400">
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
                    class="flex items-center gap-3 w-full px-4 py-2.5 hover:bg-gray-50 transition-colors text-left border-b border-gray-100 last:border-0">
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
                <div class="text-right flex-shrink-0 ml-2">
                    <p class="text-sm font-bold" style="color:var(--c,#4f46e5)" x-text="'{{ $currency }} ' + p.price.toFixed(2)"></p>
                    <p x-show="p.cp && p.cp > p.price" class="text-xs text-gray-400 line-through" x-text="'{{ $currency }} ' + (p.cp||0).toFixed(2)"></p>
                </div>
                <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </template>
        <div class="px-4 py-2.5 bg-gray-50 border-t border-gray-100 text-center">
            <button @click="searchOpen=false; _scrollToCatalog()" class="text-xs font-medium" style="color:var(--c,#4f46e5)">
                Ver todos los resultados →
            </button>
        </div>
    </div>
  </div>
</div>

{{-- ═══ CATÁLOGO COMPLETO ═══ --}}
<section id="catalogo" class="max-w-[1400px] mx-auto px-6 pb-20">
  <div class="border-t border-gray-200 pt-10 mb-4 flex items-end justify-between">
    <div>
      <p class="text-xs font-bold uppercase tracking-[.2em] text-gray-400 mb-1">Tienda completa</p>
      <h2 class="font-jost font-black text-3xl text-gray-900">Catálogo</h2>
    </div>
    <div class="sticky top-16 z-20 bg-white/95 backdrop-blur-sm shadow-sm border-b border-gray-100 flex items-center gap-3">
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
      <select x-model="sortBy" class="text-xs border border-gray-200 px-2 py-1.5 outline-none bg-white text-gray-600 cursor-pointer hover:border-gray-400 transition">
        <option value="default">Ordenar</option>
        <option value="price_asc">Precio ↑</option>
        <option value="price_desc">Precio ↓</option>
        <option value="newest">Más nuevos</option>
        <option value="name_az">Nombre A→Z</option>
      </select>
      <button x-show="filterCat!=='' || search!=='' || priceFilter!=='' || onSaleFilter"
              @click="filterCat=''; search=''; priceFilter=''; onSaleFilter=false; priceMin=0; priceMax=0"
              class="text-xs text-gray-500 hover:text-gray-900 underline underline-offset-4 transition">
        Limpiar filtros
      </button>
    </div>
  </div>
  <p class="text-xs text-gray-400 mb-4" x-show="filterCat!==''||search!==''||priceFilter!==''||onSaleFilter">
    <span x-text="visibleCount"></span> producto<span x-show="visibleCount!==1">s</span> encontrado<span x-show="visibleCount!==1">s</span>
  </p>

  <div class="flex gap-8">

    {{-- Sidebar filtros --}}
    <aside class="w-[200px] flex-shrink-0 hidden xl:block space-y-6">
      {{-- Categorías --}}
      <div>
        <p class="font-jost font-bold text-gray-900 text-sm uppercase tracking-widest mb-3">Categorías</p>
        <div class="space-y-1">
          <button @click="filterCat=''"
                  :class="filterCat==='' ? 'text-gray-900 font-bold' : 'text-gray-500'"
                  class="block w-full text-left text-sm py-1.5 hover:text-gray-900 transition">
            Todo el catálogo
          </button>
          @foreach($categories as $cat)
          <button @click="filterCat='{{ $cat->id }}'"
                  :class="filterCat==='{{ $cat->id }}' ? 'text-gray-900 font-bold' : 'text-gray-500'"
                  class="block w-full text-left text-sm py-1.5 hover:text-gray-900 transition flex items-center justify-between">
            <span>{{ $cat->name }}</span>
            <span class="text-xs text-gray-300">{{ $cat->products->count() }}</span>
          </button>
          @endforeach
        </div>
      </div>

      {{-- Precio --}}
      <div>
        <p class="font-jost font-bold text-gray-900 text-sm uppercase tracking-widest mb-3">Precio</p>
        <div class="space-y-2">
          @foreach(['' => 'Todos los precios', '0-50' => 'Hasta S/ 50', '50-150' => 'S/ 50 — S/ 150', '150-500' => 'S/ 150 — S/ 500', '500+' => 'Más de S/ 500'] as $val => $label)
          <label class="flex items-center gap-2.5 text-sm text-gray-600 cursor-pointer hover:text-gray-900 transition">
            <input type="radio" x-model="priceFilter" value="{{ $val }}"
                   class="w-3.5 h-3.5 accent-gray-900">
            {{ $label }}
          </label>
          @endforeach
        </div>
        {{-- Rango personalizado --}}
        <div class="pt-2 border-t border-gray-100 mt-2">
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

      {{-- Oferta --}}
      <div>
        <p class="font-jost font-bold text-gray-900 text-sm uppercase tracking-widest mb-3">Disponibilidad</p>
        <label class="flex items-center gap-2.5 text-sm text-gray-600 cursor-pointer hover:text-gray-900 transition">
          <input type="checkbox" x-model="onSaleFilter" class="w-3.5 h-3.5 accent-gray-900 rounded">
          Solo en oferta
        </label>
      </div>
    </aside>

    {{-- Grid productos --}}
    <div class="flex-1">
      @foreach($categories as $cat)
      @php $catAllProducts = $cat->products->merge($cat->children->flatMap->products); @endphp
      @if($catAllProducts->count())
      <div x-show="filterCat==='' || filterCat==='{{ $cat->id }}'" class="mb-14">
        <div class="flex items-center gap-4 mb-6">
          <h3 class="font-jost font-black text-gray-900 text-xl">{{ $cat->name }}</h3>
          <div class="flex-1 border-t border-gray-100"></div>
          <span class="text-xs text-gray-400">{{ $cat->products->count() }} productos</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-5" data-products-grid>
          @foreach($cat->products as $p)
          @php
          $qvData = ['id'=>$p->id,'name'=>$p->name,'img'=>$p->mainImage?asset('storage/'.$p->mainImage->url):'','price'=>(float)$p->price,'cp'=>$p->compare_price?(float)$p->compare_price:null,'desc'=>\Str::limit(strip_tags($p->description??''),120),'url'=>route('public.product',[$project->slug,$p->id]),'stock'=>$p->stock];
          @endphp
          <article class="ella-card group cursor-pointer" id="producto-{{ $p->id }}"
                   x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }}) && ({{ $loop->index }} < 8 || expandedCats['{{ $cat->id }}'])"
                   data-price="{{ $p->price }}"
                   data-ts="{{ $p->created_at ? $p->created_at->timestamp : 0 }}"
                   data-idx="{{ $loop->index }}"
                   data-qv='@json($qvData)'>
            <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="block ella-img relative overflow-hidden bg-gray-50" style="aspect-ratio:3/4;">
              @if($p->mainImage)
              <img src="{{ asset('storage/'.$p->mainImage->url) }}" alt="{{ $p->name }} — {{ $project->name }}"
                   loading="lazy" class="w-full h-full object-cover">
              @else
              <div class="w-full h-full flex items-center justify-center bg-gray-100">
                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
              </div>
              @endif
              @if($p->compare_price && $p->compare_price > $p->price)
              <span class="absolute top-3 left-3 bg-gray-900 text-white text-[10px] font-black px-2 py-1 uppercase tracking-wider">
                -{{ round((($p->compare_price - $p->price) / $p->compare_price) * 100) }}%
              </span>
              @endif
              @if($p->created_at && $p->created_at->diffInDays() <= 30)
              <span class="absolute top-3 right-3 badge-p text-[10px] font-black px-2 py-1 uppercase tracking-wider">
                Nuevo
              </span>
              @endif
              <div class="ella-add-btn absolute bottom-0 left-0 right-0">
                <button @click.stop="addToCart({
                          id:{{ $p->id }},
                          name:'{{ addslashes($p->name) }}',
                          price:{{ $p->price }},
                          img:'{{ $p->mainImage ? asset("storage/".$p->mainImage->url) : "" }}'
                        })"
                        class="w-full bg-gray-900 text-white text-xs font-bold uppercase tracking-widest py-3.5 hover:bg-gray-700 transition">
                  {{ $isQuoteOnly ? 'Cotizar' : 'Agregar' }}
                </button>
              </div>
              <button @click.prevent="const d=$el.closest('[data-qv]');if(d){qv=JSON.parse(d.dataset.qv);qvOpen=true}"
                      class="absolute inset-0 flex items-end justify-center pb-3 opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none group-hover:pointer-events-auto z-10">
                <span class="bg-black/70 text-white text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-1.5 backdrop-blur-sm">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                  Vista rápida
                </span>
              </button>
            </a>
            <div class="pt-3 pb-1">
              <p class="text-[11px] text-gray-400 uppercase tracking-widest mb-0.5">{{ $cat->name }}</p>
              <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="font-jost font-semibold text-gray-900 text-sm leading-snug line-clamp-2 hover:underline block">{{ $p->name }}</a>
              @if(isset($productRatings) && isset($productRatings[$p->id]))
              <div class="flex items-center gap-1 mb-1">
                <span class="text-amber-400 text-xs">{{ str_repeat('★', floor($productRatings[$p->id]->avg_rating)) }}{{ str_repeat('☆', 5 - floor($productRatings[$p->id]->avg_rating)) }}</span>
                <span class="text-[10px] text-gray-400">({{ $productRatings[$p->id]->rating_count }})</span>
              </div>
              @endif
              @if(!$isQuoteOnly || $quotePriceDisp === 'show')
              <div class="flex items-center gap-2 mt-1.5">
                <span class="font-bold text-gray-900 text-sm">S/ {{ number_format($p->price,2) }}</span>
                @if($p->compare_price && $p->compare_price > $p->price)
                <span class="text-xs text-gray-400 line-through">S/ {{ number_format($p->compare_price,2) }}</span>
                @endif
                @if($p->compare_price && $p->compare_price > $p->price)
                @php $ah = $p->compare_price - $p->price; @endphp
                <p class="text-[10px] text-green-600 font-semibold leading-none mt-1">Ahorras {{ $currency }} {{ number_format($ah,2) }}</p>
                @endif
              </div>
              @else
              <p class="text-xs text-gray-400 mt-1.5 italic">Precio a consultar</p>
              @endif
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

      <div x-show="noResults" class="text-center py-20">
        <p class="font-jost font-black text-gray-700 text-xl mb-2">Sin resultados</p>
        <p class="text-gray-400 text-sm mb-5">Intenta con otro término o categoría</p>
        <button @click="search=''; filterCat=''; priceFilter=''; onSaleFilter=false"
                class="border-2 border-gray-900 text-gray-900 px-6 py-2.5 text-sm font-bold uppercase tracking-widest hover:bg-gray-900 hover:text-white transition">
          Ver todo el catálogo
        </button>
      </div>
    </div>
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
</section>

{{-- ═══ FOOTER ═══ --}}
<footer class="bg-gray-950 text-gray-500">
  <div class="max-w-[1400px] mx-auto px-6 py-14 grid grid-cols-1 md:grid-cols-4 gap-10">
    {{-- Logo + desc --}}
    <div class="md:col-span-2">
      @if($project->logo_url)
      <img src="{{ asset('storage/'.$project->logo_url) }}" alt="{{ $project->name }}" class="h-10 object-contain mb-4" loading="lazy">
      @else
      <p class="font-jost font-black text-white text-2xl mb-4">{{ $project->name }}</p>
      @endif
      @if($project->description ?? null)
      <p class="text-sm text-gray-600 leading-relaxed max-w-xs">{{ $project->description }}</p>
      @else
      <p class="text-sm text-gray-600 leading-relaxed max-w-xs">Tu tienda de confianza. Encuentra los mejores productos al mejor precio.</p>
      @endif
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}"
         target="_blank" rel="noopener"
         class="inline-flex items-center gap-2 mt-5 bg-[#25D366] text-white text-xs font-bold px-4 py-2.5 rounded-sm hover:bg-[#20ba5a] transition">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
        Escribir por WhatsApp
      </a>
      @endif
    </div>

    {{-- Links rápidos --}}
    <div>
      <p class="font-jost font-bold text-white text-xs uppercase tracking-[.2em] mb-4">Categorías</p>
      <ul class="space-y-2.5">
        @foreach($categories->take(6) as $cat)
        <li>
          <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                  class="text-sm text-gray-500 hover:text-white transition">{{ $cat->name }}</button>
        </li>
        @endforeach
      </ul>
    </div>

    {{-- Contacto + Redes --}}
    <div>
      <p class="font-jost font-bold text-white text-xs uppercase tracking-[.2em] mb-4">Contacto</p>
      <div class="space-y-2.5 text-sm">
        @if($project->phone)
        <p class="flex items-center gap-2">
          <svg class="w-4 h-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
          </svg>
          {{ $project->phone }}
        </p>
        @endif
        @if($project->address)
        <p class="flex items-start gap-2">
          <svg class="w-4 h-4 opacity-40 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          {{ $project->address }}
        </p>
        @endif
      </div>
      @php
        $footerSocials = [
          ['key'=>'facebook_url','label'=>'FB','color'=>'#1877F2'],
          ['key'=>'instagram_url','label'=>'IG','color'=>'#E1306C'],
          ['key'=>'tiktok_url','label'=>'TK','color'=>'#222'],
          ['key'=>'youtube_url','label'=>'YT','color'=>'#FF0000'],
        ];
      @endphp
      <div class="flex flex-wrap gap-2 mt-5">
        @foreach($footerSocials as $soc)
        @if($settings[$soc['key']] ?? null)
        <a href="{{ $settings[$soc['key']] }}" target="_blank" rel="noopener"
           class="w-8 h-8 flex items-center justify-center text-white text-xs font-bold rounded-sm hover:opacity-80 transition"
           style="background:{{ $soc['color'] }}">{{ $soc['label'] }}</a>
        @endif
        @endforeach
      </div>
    </div>
  </div>
  <div class="border-t border-gray-900 py-5 text-center text-xs text-gray-700">
    &copy; {{ date('Y') }} {{ $project->name }} &mdash; Catálogo online por <strong class="text-gray-600">AVAN</strong>
  </div>
</footer>

{{-- ═══════════════════════════════════════════
     MOBILE FILTER BOTTOM-SHEET
═══════════════════════════════════════════ --}}
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
                  class="border rounded-xl px-3 py-2 text-sm text-left transition">{{ $currency }} 50–150</button>
          <button @click="priceFilter='150-500'"
                  :class="priceFilter==='150-500' ? 'border-[var(--c)] text-[var(--c)] font-bold' : 'border-gray-200 text-gray-600'"
                  class="border rounded-xl px-3 py-2 text-sm text-left transition">{{ $currency }} 150–500</button>
          <button @click="priceFilter='500+'"
                  :class="priceFilter==='500+' ? 'border-[var(--c)] text-[var(--c)] font-bold' : 'border-gray-200 text-gray-600'"
                  class="border rounded-xl px-3 py-2 text-sm text-left transition col-span-2">Más de {{ $currency }} 500</button>
        </div>
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

{{-- ═══ CART DRAWER ═══ --}}
<div x-show="drawerOpen" x-cloak>
  <div class="drawer-overlay" @click="drawerOpen=false" aria-hidden="true"></div>
  <div class="drawer" role="dialog" aria-label="{{ $isQuoteOnly ? 'Mi cotización' : 'Mi pedido' }}"
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
        <button x-show="(drawerStep === 2 || drawerStep === 3) && !orderSent"
                @click="drawerStep > 1 ? drawerStep-- : null"
                class="p-1.5 hover:bg-gray-100 rounded-lg transition mr-1" aria-label="Volver">
          <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <svg class="w-5 h-5 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <h2 class="font-jost font-black text-gray-900 text-base"
            x-text="drawerStep===1 ? '{{ $isQuoteOnly ? 'Mi cotización' : 'Tu pedido' }}' : (drawerStep===2 ? 'Confirmar datos' : 'Pagar')"></h2>
        <span x-show="cart.length && drawerStep===1"
              class="bg-gray-900 text-white text-xs px-2 py-0.5 font-black" x-text="cart.length + ' items'"></span>
      </div>
      <button @click="drawerOpen=false" class="p-2 hover:bg-gray-100 rounded-lg transition" aria-label="Cerrar">
        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    {{-- PASO 1: Lista --}}
    <div x-show="drawerStep===1" class="flex flex-col flex-1 overflow-hidden">
      <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <template x-if="cart.length===0">
          <div class="text-center py-16 text-gray-400">
            <svg class="w-14 h-14 mx-auto mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="font-jost font-bold text-gray-600 mb-1">{{ $isQuoteOnly ? 'Tu cotización está vacía' : 'Tu carrito está vacío' }}</p>
            <p class="text-sm">Agrega productos para comenzar</p>
          </div>
        </template>
        <template x-for="(item, i) in cart" :key="item.id">
          <div class="flex items-center gap-3 bg-gray-50 p-3">
            <div class="w-14 h-14 overflow-hidden flex-shrink-0 bg-gray-200">
              <img :src="item.img" x-show="item.img" class="w-full h-full object-cover" :alt="item.name">
              <div x-show="!item.img" class="w-full h-full flex items-center justify-center text-gray-400 text-xs">IMG</div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-gray-800 line-clamp-2" x-text="item.name"></p>
              @if(!$isQuoteOnly || $quotePriceDisp === 'show')
              <p class="text-sm font-black text-gray-900 mt-0.5" x-text="'S/ ' + (item.price * item.qty).toFixed(2)"></p>
              @endif
            </div>
            <div class="flex items-center gap-1 flex-shrink-0">
              <button @click="item.qty > 1 ? item.qty-- : cart.splice(i,1)"
                      class="w-8 h-8 border-2 border-gray-200 hover:border-red-300 hover:bg-red-50 text-gray-600 hover:text-red-500 font-bold text-sm transition flex items-center justify-center">
                <span x-text="item.qty > 1 ? '−' : '×'"></span>
              </button>
              <span class="w-7 text-center text-sm font-black text-gray-800" x-text="item.qty"></span>
              <button @click="item.qty++"
                      class="w-8 h-8 bg-gray-900 text-white font-bold text-sm transition flex items-center justify-center hover:bg-gray-700">+</button>
            </div>
          </div>
        </template>
      </div>

      <div x-show="cart.length > 0" class="border-t border-gray-100 px-5 py-4 space-y-3 flex-shrink-0">
        <div class="flex justify-between items-center">
          <span class="text-sm text-gray-500"><span x-text="cart.reduce((s,i)=>s+i.qty,0)"></span> productos</span>
          @if(!$isQuoteOnly || $quotePriceDisp === 'show')
          <span class="font-black text-lg text-gray-900" x-text="'S/ ' + cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></span>
          @else
          <span class="text-xs text-gray-400 italic">Precios a cotizar</span>
          @endif
        </div>
        @if(!$isQuoteOnly && count($acceptedPayments) > 0)
        <div class="flex flex-wrap gap-1.5">
          @foreach($acceptedPayments as $pmKey)
          @if(isset($paymentMeta[$pmKey]))
          <span class="inline-flex items-center gap-1 text-xs px-2 py-1 bg-gray-100 text-gray-600 font-medium">
            {{ $paymentMeta[$pmKey]['emoji'] }} {{ $paymentMeta[$pmKey]['label'] }}
          </span>
          @endif
          @endforeach
        </div>
        @endif
        <button @click="drawerStep=2; orderError=''"
                class="w-full bg-gray-900 text-white py-3.5 font-black text-sm uppercase tracking-widest transition hover:bg-gray-700 flex items-center justify-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
          </svg>
          {{ $isQuoteOnly ? 'Continuar y cotizar' : 'Continuar y pedir' }}
        </button>
      </div>
    </div>

    {{-- PASO 2: Formulario --}}
    <div x-show="drawerStep===2" class="flex flex-col flex-1 overflow-hidden">
      <div x-show="!orderSent" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <div class="bg-gray-50 px-4 py-3 flex justify-between items-center">
          <span class="text-sm text-gray-600"><span x-text="cart.reduce((s,i)=>s+i.qty,0)"></span> productos</span>
          @if(!$isQuoteOnly || $quotePriceDisp === 'show')
          <span class="font-black text-gray-900" x-text="'S/ ' + cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></span>
          @else
          <span class="text-xs text-gray-400 italic">a cotizar</span>
          @endif
        </div>
        <input x-model="form.name" type="text" placeholder="Tu nombre completo *"
               class="w-full border-2 border-gray-200 focus:border-gray-900 px-4 py-2.5 text-sm outline-none transition" autocomplete="name">
        <input x-model="form.phone" type="tel" placeholder="Tu WhatsApp / teléfono *"
               class="w-full border-2 border-gray-200 focus:border-gray-900 px-4 py-2.5 text-sm outline-none transition" autocomplete="tel">
        <input x-model="form.email" type="email" placeholder="Tu correo electrónico (opcional)"
               class="w-full border-2 border-gray-200 focus:border-gray-900 px-4 py-2.5 text-sm outline-none transition" autocomplete="email">
        <textarea x-model="form.notes" rows="2" placeholder="Nota adicional (opcional)"
                  class="w-full border-2 border-gray-200 focus:border-gray-900 px-4 py-2.5 text-sm outline-none resize-none transition"></textarea>
        @if($requireAddress)
        <input x-model="form.address" type="text" placeholder="Dirección de entrega *"
               class="w-full border-2 border-gray-200 focus:border-gray-900 px-4 py-2.5 text-sm outline-none transition"
               autocomplete="street-address">
        @endif
        {{-- Cupón --}}
        <div>
          <div x-show="!couponApplied" class="flex gap-2">
            <input x-model="couponCode" @keydown.enter.prevent="applyCoupon" type="text"
                   placeholder="Código de descuento"
                   class="flex-1 border-b border-gray-200 focus:border-gray-900 px-0 py-2.5 text-sm outline-none transition uppercase bg-transparent"
                   style="text-transform:uppercase">
            <button @click="applyCoupon" :disabled="couponLoading" type="button"
                    class="px-4 py-2.5 text-sm font-semibold text-gray-700 hover:text-gray-900 transition flex-shrink-0">
              <span x-text="couponLoading ? '…' : 'Aplicar'"></span>
            </button>
          </div>
          <div x-show="couponApplied" class="flex items-center justify-between bg-green-50 border border-green-200 px-4 py-2.5 text-sm">
            <div>
              <span class="font-mono font-bold text-green-700" x-text="couponApplied ? couponApplied.code : ''"></span>
              <span class="text-green-600 ml-1">&mdash; <span x-text="couponApplied && couponApplied.type==='percent' ? couponApplied.value+'% desc.' : 'S/ '+(couponApplied?couponApplied.value:0).toFixed(2)+' desc.'"></span></span>
            </div>
            <button @click="removeCoupon" type="button" class="text-gray-400 hover:text-red-500 ml-3 text-lg leading-none">&times;</button>
          </div>
          <p x-show="couponError" class="text-red-500 text-xs mt-1" x-text="couponError"></p>
        </div>
        <div x-show="shippingEnabled || couponApplied" class="bg-gray-50 px-4 py-3 space-y-1.5 text-sm">
          <div class="flex justify-between text-gray-500"><span>Subtotal</span><span x-text="'S/ ' + subtotal.toFixed(2)"></span></div>
          <div x-show="couponApplied && couponDiscount > 0" class="flex justify-between text-green-600 font-medium">
            <span>Descuento</span>
            <span x-text="'- S/ ' + couponDiscount.toFixed(2)"></span>
          </div>
          <div x-show="shippingEnabled" class="flex justify-between" :class="effectiveShipping===0 && shippingFreeFrom>0 ? 'text-green-600 font-medium' : 'text-gray-500'">
            <span x-text="effectiveShipping===0 && shippingFreeFrom>0 ? '🎉 Envío gratis' : 'Envío'"></span>
            <span x-text="effectiveShipping>0 ? 'S/ '+effectiveShipping.toFixed(2) : 'Gratis'"></span>
          </div>
          @if($shippingFreeFrom > 0)<p x-show="effectiveShipping>0" class="text-xs text-gray-400">Agrega S/ <span x-text="Math.max(0,{{ $shippingFreeFrom }}-subtotal).toFixed(2)"></span> más para envío gratis</p>@endif
          <div class="flex justify-between font-black text-gray-900 border-t border-gray-200 pt-1.5"><span>Total</span><span x-text="'S/ '+orderGrandTotal.toFixed(2)"></span></div>
        </div>
        <p x-show="orderError" class="text-red-500 text-xs text-center font-medium" x-text="orderError"></p>
      </div>

      <div x-show="orderSent" class="flex-1 flex flex-col items-center justify-center px-5 py-8 text-center">
        <div class="w-20 h-20 bg-green-100 flex items-center justify-center mb-5">
          <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <p class="font-jost font-black text-gray-900 text-xl mb-2">{{ $isQuoteOnly ? '¡Cotización enviada!' : '¡Pedido confirmado!' }}</p>
        <p x-show="orderId" class="text-xs text-gray-400 mb-2">Pedido N° <span class="font-black text-gray-700" x-text="orderId"></span></p>
        <p class="text-sm text-gray-500 mb-6 leading-relaxed">{{ $isQuoteOnly ? 'Recibimos tu solicitud y te enviaremos los precios a la brevedad.' : 'Recibimos tu pedido y nos pondremos en contacto muy pronto.' }}</p>
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
                class="bg-gray-900 text-white px-8 py-3 text-sm font-bold uppercase tracking-widest hover:bg-gray-700 transition">
          {{ $isQuoteOnly ? 'Seguir explorando' : 'Seguir comprando' }}
        </button>
      </div>

      <div x-show="!orderSent" class="border-t border-gray-100 px-5 py-4 flex-shrink-0">
        @if(!$isQuoteOnly && $hasOnlinePayment)
        <button @click="submitOrder()" :disabled="orderLoading"
                class="w-full bg-gray-900 text-white py-4 font-black text-sm uppercase tracking-widest transition disabled:opacity-60 hover:bg-gray-700 flex items-center justify-center gap-2">
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
        <button @click="submitOrder()" :disabled="orderLoading"
                class="w-full py-4 font-black text-sm uppercase tracking-widest transition disabled:opacity-60 flex items-center justify-center gap-2
                       {{ $isQuoteOnly ? 'bg-[#25D366] hover:bg-[#20ba5a] text-white' : 'bg-gray-900 hover:bg-gray-700 text-white' }}">
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
    {{-- PASO 3: Pago --}}
    <div x-show="drawerStep===3" class="flex flex-col flex-1 overflow-hidden">
      <div x-show="!orderSent" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <div class="bg-gray-50 px-4 py-3 flex justify-between items-center">
          <span class="text-sm text-gray-600 font-medium">Total a pagar</span>
          <span class="font-black text-lg text-gray-900" x-text="'S/ ' + orderTotal.toFixed(2)"></span>
        </div>
        <p x-show="payError" class="text-red-500 text-xs text-center font-medium px-2" x-text="payError"></p>

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
          <div x-data="{ open: false }" class="border-2 border-gray-200 overflow-hidden">
            <button @click="open = !open"
                    class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-gray-50 transition">
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
              <div class="bg-white border border-gray-200 p-3">
                <p class="text-xs font-semibold text-gray-600 mb-1">Datos para pagar:</p>
                <p class="text-sm text-gray-800 whitespace-pre-line">{{ $mmDetails }}</p>
              </div>
              @endif
              @if($payManualInstr)<p class="text-xs text-gray-500 italic">{{ $payManualInstr }}</p>@endif
              <div>
                <label class="text-xs font-semibold text-gray-600 block mb-1">Número de operación / referencia *</label>
                <input x-model="payReference" type="text" placeholder="Ej: 123456789"
                       class="w-full border-2 border-gray-200 focus:border-gray-900 px-4 py-2.5 text-sm outline-none transition">
              </div>
              <button @click="confirmManualPay()" :disabled="payLoading || !payReference.trim()"
                      class="w-full bg-gray-900 text-white py-3 font-black text-sm uppercase tracking-widest transition disabled:opacity-50 flex items-center justify-center gap-2 hover:bg-gray-700">
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
        <div class="space-y-2">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pago con tarjeta</p>
          <button @click="openCulqi()" :disabled="payLoading"
                  class="w-full flex items-center gap-3 px-4 py-3.5 border-2 border-gray-200 text-left hover:border-gray-900 transition disabled:opacity-50">
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
        <script src="https://checkout.culqi.com/js/v4"></script>
        @endif

        @if($mpEnabled)
        <div class="space-y-2">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Mercado Pago</p>
          <button @click="openMercadoPago()" :disabled="payLoading"
                  class="w-full flex items-center gap-3 px-4 py-3.5 border-2 border-gray-200 text-left hover:border-blue-400 transition disabled:opacity-50">
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
        <p class="text-center text-xs text-gray-400 pt-2">Tus datos están protegidos y nunca se comparten</p>
      </div>

      <div x-show="orderSent" class="flex-1 flex flex-col items-center justify-center px-5 py-8 text-center">
        <div class="w-20 h-20 bg-green-100 flex items-center justify-center mb-5">
          <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <p class="font-jost font-black text-gray-900 text-xl mb-2">¡Pago registrado!</p>
        <p class="text-sm text-gray-500 mb-6 leading-relaxed">Tu pedido está confirmado. Nos pondremos en contacto contigo pronto.</p>
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
                class="bg-gray-900 text-white px-8 py-3 text-sm font-bold uppercase tracking-widest hover:bg-gray-700 transition">
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
                  class="w-full btn-p py-2.5 rounded-xl text-sm font-black flex items-center justify-center gap-2 transition">
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

{{-- ═══ ALPINE STORE SCRIPT ═══ --}}
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
    filterParent: '', priceFilter: '', onSaleFilter: false, sortBy: 'default',
    qv: null,
    qvOpen: false,
    expandedCats: {},
    recentlyViewed: [],
    filterOpen: false,
    priceMin: 0,
    priceMax: 0,
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
    selectedPayMethod: '', payReference: '', payLoading: false, payError: '',

    init() {
      try {
          const _rv = JSON.parse(localStorage.getItem('rv_{{ $project->slug }}') || '[]');
          this.recentlyViewed = _rv.filter(x => x && x.id);
      } catch(e) {}
      this.$watch('cart', val => { try { localStorage.setItem(this._cartKey, JSON.stringify(val)); } catch(e) {} });
      this.$watch('form', val => { try { localStorage.setItem(this._formKey, JSON.stringify(val)); } catch(e) {} }, { deep: true });
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

    get cartCount() { return this.cart.reduce((s,i)=>s+i.qty,0); },
    get cartTotal() { return this.cart.reduce((s,i)=>s+(i.price*i.qty),0); },

    checkNoResults() {
      const hasFilter = this.search !== '' || this.filterCat !== '' || this.priceFilter !== '' || this.onSaleFilter;
      if (!hasFilter) { this.noResults = false; return; }
      const articles = document.querySelectorAll('#catalogo article');
      const visible  = Array.from(articles).filter(el => el.style.display !== 'none');
      this.noResults = visible.length === 0;
    },

    matchProduct(name, price, comparePrice) {
      if (this.search !== '' && !name.includes(this.search.toLowerCase())) return false;
      if (this.priceFilter === '0-50'    && price > 50) return false;
      if (this.priceFilter === '50-150'  && (price <= 50 || price > 150)) return false;
      if (this.priceFilter === '150-500' && (price <= 150 || price > 500)) return false;
      if (this.priceFilter === '500+'    && price <= 500) return false;
      if (this.onSaleFilter && !(comparePrice && comparePrice > price)) return false;
      return true;
    },

    addToCart(product) {
      const existing = this.cart.find(i => i.id === product.id);
      if (existing) { existing.qty++; }
      else { this.cart.push({ ...product, qty: 1 }); }
      this.toastMsg = '✓ ' + product.name + ' agregado';
      this.toastShow = true;
      clearTimeout(this.toastTimer);
      this.toastTimer = setTimeout(() => { this.toastShow = false; }, 2000);
    },

    sendQuoteWhatsapp() {
      if (!this.form.name.trim()) { this.orderError = 'Por favor ingresa tu nombre primero.'; return; }
      const businessName = `{{ addslashes($project->name) }}`;
      const customMsg    = `{{ addslashes($quoteWaMsg) }}`;
      const fecha = new Date().toLocaleDateString('es-PE', { day:'2-digit', month:'long', year:'numeric' });
      let lines = `🛒 *SOLICITUD DE COTIZACIÓN*\n━━━━━━━━━━━━━━━━━━━━━━\n🏪 *${businessName}*\n\n${customMsg}\n\n👤 *DATOS*\n• Nombre: ${this.form.name}\n`;
      if (this.form.phone) lines += `• Teléfono: ${this.form.phone}\n`;
      lines += `\n📦 *PRODUCTOS*\n━━━━━━━━━━━━━━━━━━━━━━\n`;
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
      lines += `━━━━━━━━━━━━━━━━━━━━━━\n💰 *Total referencial: S/ ${total.toFixed(2)}*\n`;
      @endif
      if (this.form.notes) lines += `\n📝 Nota: ${this.form.notes}\n`;
      lines += `\n📅 Fecha: ${fecha}\n_Cotización generada desde el catálogo online de ${businessName}_`;
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
        if (data.ok) {
          try { localStorage.removeItem(this._cartKey); localStorage.removeItem(this._formKey); } catch(e) {}
          window.location.href = '/{{ $project->slug }}/thanks/' + this.orderId;
        } else { this.payError = 'No se pudo confirmar el pago.'; }
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
    {{ $isQuoteOnly ? 'Ver cotización' : 'Ver pedido' }}
  </button>
</div>
</body>
</html>
