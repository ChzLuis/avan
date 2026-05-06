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
  $primaryColor     = $settings['primary_color'] ?? '#c9a96e';
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
  // Paleta nórdica
  $bg    = '#f7f3ee';
  $text  = '#3d2b1f';
  $accent = $primaryColor !== '#c9a96e' ? $primaryColor : '#c9a96e';
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
<link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

@php
  $secondaryColor = $settings['secondary_color'] ?? '#b8971e';
  $fontTitle  = $settings['font_title'] ?? $settings['font'] ?? 'Raleway';
  $fontBody   = $settings['font_body']  ?? $settings['font'] ?? 'Jost';
  $borderRadius = ['sharp'=>'0px','rounded'=>'8px','pill'=>'50px'][$settings['border_radius'] ?? 'rounded'] ?? '8px';
  $faviconUrl   = $settings['favicon_url'] ?? '';
  $catalogBadgeSale = $settings['catalog_badge_sale'] ?? 'OFERTA';
  $catalogBadgeNew  = $settings['catalog_badge_new']  ?? 'NUEVO';
  $btnCartText  = $settings['btn_cart_text']  ?? 'Agregar al carrito';
  $btnQuoteText = $settings['btn_quote_text'] ?? 'Cotizar';
  $footerTagline   = $settings['footer_tagline']  ?? '';
  $footerCopyright = $settings['footer_copyright'] ?? ('© ' . date('Y') . ' ' . $project->name);
@endphp
@if($faviconUrl)<link rel="icon" href="{{ $faviconUrl }}">@endif
<style>
:root {
  --c:      {{ $accent }};
  --c2:     {{ $secondaryColor }};
  --radius: {{ $borderRadius }};
  --bg:     {{ $bg }};
  --text:   {{ $text }};
  --card:   #ffffff;
  --border: #e8e0d6;
  --font-title: '{{ $fontTitle }}', sans-serif;
  --font-body:  '{{ $fontBody }}', sans-serif;
}
*, body { font-family: var(--font-body); color: var(--text); }
body { background: var(--bg); }

.btn-accent      { background: var(--c); color: #fff; }
.btn-accent:hover{ filter: brightness(.92); }
.btn-outline     { border: 1.5px solid var(--text); color: var(--text); background: transparent; }
.btn-outline:hover{ background: var(--text); color: #fff; }
.price-p { color: var(--c); }
.badge-p { background: var(--c); color: #fff; }

[x-cloak]{ display:none!important; }

/* Drawer */
.drawer-overlay { position:fixed;inset:0;background:rgba(61,43,31,.45);z-index:40;backdrop-filter:blur(3px); }
.drawer { position:fixed;top:0;right:0;height:100%;width:420px;max-width:96vw;background:#fff;z-index:50;display:flex;flex-direction:column;box-shadow:-6px 0 32px rgba(61,43,31,.12); }
@media(max-width:640px){ .drawer{ width:100%; } }

/* Nordic card */
.nordic-card { background: var(--card); border: 1px solid var(--border); transition: box-shadow .25s, transform .25s; }
.nordic-card:hover { box-shadow: 0 8px 30px rgba(61,43,31,.1); transform: translateY(-2px); }
.nordic-card .nordic-img img { transition: transform .4s ease; }
.nordic-card:hover .nordic-img img { transform: scale(1.04); }
.nordic-btn { opacity: 0; transform: translateY(6px); transition: opacity .25s, transform .25s; }
.nordic-card:hover .nordic-btn { opacity: 1; transform: translateY(0); }

/* Cat filter pill */
.cat-pill { border: 1.5px solid var(--border); color: var(--text); background: transparent; transition: all .2s; }
.cat-pill:hover { border-color: var(--c); color: var(--c); }
.cat-pill.active { background: var(--c); color: #fff; border-color: var(--c); }

/* Scroll sin scrollbar */
.no-scrollbar { overflow-x:auto; scrollbar-width:none; }
.no-scrollbar::-webkit-scrollbar { display:none; }
.scrollbar-hide { -ms-overflow-style:none; scrollbar-width:none; }
.scrollbar-hide::-webkit-scrollbar { display:none; }
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

{{-- ═══ FREE SHIPPING BAR ═══ --}}
<div style="background:var(--border);color:var(--text);" class="text-xs text-center py-2.5 tracking-wide font-medium">
  Envío gratis en pedidos mayores a S/ 99
  @if($project->phone)
  &nbsp;&nbsp;·&nbsp;&nbsp; {{ $project->phone }}
  @endif
</div>

{{-- ═══ HEADER CENTRADO ═══ --}}
<header class="bg-white sticky top-0 z-30" style="border-bottom:1px solid var(--border);">
  <div class="max-w-[1200px] mx-auto px-6">

    {{-- Fila logo + acciones --}}
    <div class="flex items-center justify-between py-5">
      {{-- Buscador izq --}}
      <div class="flex items-center gap-2 w-52 hidden md:flex">
        <div class="relative flex-1" @click.outside="searchOpen = false">
          <input x-model="search" type="search" placeholder="Buscar productos..."
                 @input="searchOpen = search.trim().length >= 2; searchIdx = -1; if(search.trim().length >= 2) _scrollToCatalog()"
                 @keydown.arrow-down.prevent="if(suggestions.length){searchIdx=(searchIdx+1)%suggestions.length}"
                 @keydown.arrow-up.prevent="if(suggestions.length){searchIdx=(searchIdx-1+suggestions.length)%suggestions.length}"
                 @keydown.enter.prevent="if(searchIdx>=0){selectSuggestion(suggestions[searchIdx])}else{searchOpen=false;_scrollToCatalog()}"
                 @keydown.escape="searchOpen=false;searchIdx=-1"
                 class="w-full text-sm border outline-none pl-8 pr-3 py-2 transition"
                 style="border-color:var(--border);background:var(--bg);">
          <svg class="w-4 h-4 absolute left-2 top-1/2 -translate-y-1/2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
      </div>

      {{-- Logo centrado --}}
      <a href="{{ $canonicalUrl }}" class="flex flex-col items-center gap-1" aria-label="{{ $project->name }}">
        @if($project->logo_url)
          <img src="{{ asset('storage/'.$project->logo_url) }}" alt="Logo {{ $project->name }}"
               class="h-14 object-contain" width="140" height="56">
        @else
          <span class="font-bold tracking-[.3em] uppercase text-2xl" style="color:var(--text);">{{ $project->name }}</span>
          <div class="w-8 h-[1.5px]" style="background:var(--c);"></div>
        @endif
      </a>

      {{-- Carrito + WA derecha --}}
      <div class="flex items-center gap-3 w-52 justify-end">
        @if($project->whatsapp)
        <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}"
           target="_blank" rel="noopener" title="WhatsApp"
           class="text-[#25D366] hover:opacity-80 transition hidden sm:block">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
          </svg>
        </a>
        @endif
        <button @click="drawerOpen=true" class="relative p-2 hover:opacity-70 transition" aria-label="Ver carrito">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--text);">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
          <span x-show="cartCount > 0" x-text="cartCount"
                class="absolute -top-0.5 -right-0.5 badge-p text-white text-[10px] font-black rounded-full w-5 h-5 flex items-center justify-center"></span>
        </button>
      </div>
    </div>

    {{-- Nav categorías centrado --}}
    <nav class="flex items-center justify-center gap-1 pb-4 no-scrollbar hidden md:flex">
      <div class="w-full flex-1 border-t" style="border-color:var(--border);"></div>
      <div class="flex items-center gap-6 px-8">
        <button @click="filterCat=''; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                :class="filterCat==='' ? 'font-semibold' : 'opacity-60 hover:opacity-100'"
                class="text-sm whitespace-nowrap transition tracking-wide"
                style="color:var(--text);">Todo</button>
        @foreach($categories as $cat)
        <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                :class="filterCat==='{{ $cat->id }}' ? 'font-semibold' : 'opacity-60 hover:opacity-100'"
                class="text-sm whitespace-nowrap transition tracking-wide"
                style="color:var(--text);">{{ $cat->name }}</button>
        @endforeach
      </div>
      <div class="w-full flex-1 border-t" style="border-color:var(--border);"></div>
    </nav>
  </div>
</header>

{{-- ═══ HERO ═══ --}}
<section class="relative flex items-center justify-center overflow-hidden text-center"
         style="height:60vh;min-height:400px;background:{{ $settings['hero_bg_color'] ?? '#e8e0d6' }};">
  <div class="absolute inset-0" style="background:rgba(247,243,238,.65);"></div>
  <div class="relative z-10 max-w-2xl mx-auto px-6">
    @if($settings['hero_badge'] ?? null)
    <span class="inline-flex items-center gap-2 text-xs font-semibold tracking-[.25em] uppercase mb-5"
          style="color:var(--c);">
      <span class="w-8 h-[1px]" style="background:var(--c);display:inline-block;"></span>
      {{ $settings['hero_badge'] }}
      <span class="w-8 h-[1px]" style="background:var(--c);display:inline-block;"></span>
    </span>
    @endif
    <h1 class="font-bold text-5xl md:text-7xl leading-none mb-4 tracking-tight" style="color:var(--text);">
      {{ $settings['hero_title'] ?? $project->name }}
    </h1>
    <p class="text-base font-light leading-relaxed mb-8 opacity-70 max-w-md mx-auto" style="color:var(--text);">
      {{ $settings['hero_subtitle'] ?? 'Diseño y calidad para tu hogar' }}
    </p>
    <button @click="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
            class="btn-outline inline-flex items-center gap-3 px-8 py-3 text-sm font-semibold tracking-[.15em] uppercase transition">
      Explorar colección
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
      </svg>
    </button>
  </div>
  {{-- Líneas decorativas --}}
  <div class="absolute left-8 top-8 w-[1px] h-16 opacity-20" style="background:var(--text);"></div>
  <div class="absolute right-8 bottom-8 w-[1px] h-16 opacity-20" style="background:var(--text);"></div>
</section>

{{-- ═══ CATEGORÍAS GRID 3 COL ═══ --}}
@if($categories->count() > 1)
<section class="max-w-[1200px] mx-auto px-6 py-16">
  <div class="text-center mb-10">
    <span class="text-xs font-semibold uppercase tracking-[.25em] opacity-50">Navegar</span>
    <h2 class="font-bold text-3xl mt-1">Categorías</h2>
    <div class="w-12 h-[1.5px] mx-auto mt-3" style="background:var(--c);"></div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($categories as $cat)
    @php
      $catBgs = ['#d5cfc6','#c9c0b6','#bfb5a8','#cdc4b8','#c5bbb0','#d0c8be'];
      $catBg  = $catBgs[$loop->index % count($catBgs)];
    @endphp
    <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
            class="group relative overflow-hidden text-left"
            style="aspect-ratio:4/3;background:{{ $catBg }};"
            :style="filterCat==='{{ $cat->id }}' ? 'outline:2px solid var(--c);outline-offset:3px' : ''">
      <div class="absolute inset-0 flex items-end">
        <div class="w-full px-6 py-5 transition-all group-hover:pb-6"
             style="background:rgba(247,243,238,.88);">
          <p class="font-bold text-lg tracking-wide" style="color:var(--text);">{{ $cat->name }}</p>
          <p class="text-sm opacity-50 mt-0.5 font-light">{{ $cat->products->count() }} productos</p>
          <div class="w-6 h-[1.5px] mt-2 transition-all group-hover:w-10" style="background:var(--c);"></div>
        </div>
      </div>
    </button>
    @endforeach
  </div>
</section>
@endif

{{-- ═══ DESTACADOS 3 COLUMNAS ═══ --}}
@if($featured->count())
<section class="py-16" style="background:var(--card);">
  <div class="max-w-[1200px] mx-auto px-6">
    <div class="text-center mb-10">
      <span class="text-xs font-semibold uppercase tracking-[.25em] opacity-50">Selección especial</span>
      <h2 class="font-bold text-3xl mt-1">Productos Destacados</h2>
      <div class="w-12 h-[1.5px] mx-auto mt-3" style="background:var(--c);"></div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
      @foreach($featured->take(6) as $p)
      @php
      $qvData = ['id'=>$p->id,'name'=>$p->name,'img'=>$p->mainImage?asset('storage/'.$p->mainImage->url):'','price'=>(float)$p->price,'cp'=>$p->compare_price?(float)$p->compare_price:null,'desc'=>\Str::limit(strip_tags($p->description??''),120),'url'=>route('public.product',[$project->slug,$p->id]),'stock'=>$p->stock];
      @endphp
      <article class="nordic-card p-4 cursor-pointer group" id="prod-feat-{{ $p->id }}" data-qv='@json($qvData)'>
        <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="block nordic-img relative overflow-hidden mb-4" style="aspect-ratio:1/1;">
          @if($p->mainImage)
          <img src="{{ asset('storage/'.$p->mainImage->url) }}" alt="{{ $p->name }}"
               loading="lazy" class="w-full h-full object-cover">
          @else
          <div class="w-full h-full flex items-center justify-center" style="background:var(--bg);">
            <svg class="w-10 h-10 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
          @endif
          @if($p->compare_price && $p->compare_price > $p->price)
          <span class="absolute top-3 left-3 badge-p text-[10px] font-bold px-2 py-1 rounded-sm">
            -{{ round((($p->compare_price - $p->price) / $p->compare_price) * 100) }}%
          </span>
          @endif
          @if($p->stock !== null && $p->stock === 0)
          <span class="absolute bottom-0 left-0 right-0 bg-red-600/90 text-white text-[10px] font-black py-1 text-center tracking-wide">AGOTADO</span>
          @elseif($p->stock !== null && $p->stock > 0 && $p->stock <= 5)
          <span class="absolute bottom-0 left-0 right-0 bg-orange-500/90 text-white text-[10px] font-bold py-1 text-center leading-tight">⚡ CASI AGOTADO — {{ $p->stock }} restantes</span>
          @endif
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
        <div class="px-2">
          @if($p->category)
          <p class="text-[11px] uppercase tracking-[.18em] opacity-40 mb-1 font-medium">{{ $p->category->name }}</p>
          @endif
          <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="font-semibold text-base leading-snug line-clamp-2 mb-2 hover:underline block">{{ $p->name }}</a>
          @if($p->description)
          <p class="text-sm opacity-50 line-clamp-2 mb-3 font-light leading-relaxed">{{ $p->description }}</p>
          @endif
          @if(!$isQuoteOnly || $quotePriceDisp === 'show')
          <div class="flex items-center gap-2 mb-4">
            <span class="font-bold text-lg price-p">S/ {{ number_format($p->price,2) }}</span>
            @if($p->compare_price && $p->compare_price > $p->price)
            <span class="text-sm opacity-40 line-through">S/ {{ number_format($p->compare_price,2) }}</span>
            @endif
            @if($p->compare_price && $p->compare_price > $p->price)
            @php $ah = $p->compare_price - $p->price; @endphp
            <p class="text-[10px] text-green-600 font-semibold leading-none mt-1">Ahorras {{ $currency }} {{ number_format($ah,2) }}</p>
            @endif
          </div>
          @else
          <p class="text-sm opacity-40 italic mb-4">Precio a consultar</p>
          @endif
          <button class="nordic-btn w-full btn-outline py-2.5 text-sm font-semibold tracking-wider uppercase transition"
                  @click="addToCart({
                    id:{{ $p->id }},
                    name:'{{ addslashes($p->name) }}',
                    price:{{ $p->price }},
                    img:'{{ $p->mainImage ? asset("storage/".$p->mainImage->url) : "" }}'
                  })">
            {{ $isQuoteOnly ? 'Cotizar' : 'Agregar al carrito' }}
          </button>
        </div>
      </article>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ═══ BANNER BEIGE CENTRADO ═══ --}}
<section class="py-20 text-center" style="background:var(--border);">
  <div class="max-w-[700px] mx-auto px-6">
    {{-- Decoración de líneas --}}
    <div class="flex items-center gap-6 justify-center mb-6 opacity-30">
      <div class="flex-1 border-t" style="border-color:var(--text);max-width:80px;"></div>
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--text);">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3l14 9-14 9V3z"/>
      </svg>
      <div class="flex-1 border-t" style="border-color:var(--text);max-width:80px;"></div>
    </div>
    <h2 class="font-bold text-4xl md:text-5xl leading-tight mb-4" style="color:var(--text);">
      {{ $settings['banner1_title'] ?? 'Nueva Temporada' }}
    </h2>
    <p class="text-base font-light leading-relaxed opacity-60 mb-8 max-w-sm mx-auto" style="color:var(--text);">
      {{ $settings['banner1_sub'] ?? 'Descubre los mejores productos de la temporada con diseño escandinavo' }}
    </p>
    <button @click="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
            class="btn-outline px-10 py-3.5 text-sm font-semibold tracking-[.15em] uppercase transition inline-flex items-center gap-3">
      Ver toda la colección
    </button>
    {{-- Decoración inferior --}}
    <div class="flex items-center gap-6 justify-center mt-6 opacity-30">
      <div class="flex-1 border-t" style="border-color:var(--text);max-width:80px;"></div>
      <div class="w-1.5 h-1.5 rounded-full" style="background:var(--text);"></div>
      <div class="flex-1 border-t" style="border-color:var(--text);max-width:80px;"></div>
    </div>
  </div>
</section>

{{-- ═══ NOVEDADES ═══ --}}
@if($newArrivals->count())
<section class="max-w-[1200px] mx-auto px-6 py-16">
  <div class="text-center mb-10">
    <span class="text-xs font-semibold uppercase tracking-[.25em] opacity-50">Recién llegados</span>
    <h2 class="font-bold text-3xl mt-1">Novedades</h2>
    <div class="w-12 h-[1.5px] mx-auto mt-3" style="background:var(--c);"></div>
  </div>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
    @foreach($newArrivals->take(8) as $p)
    @php
    $qvData = ['id'=>$p->id,'name'=>$p->name,'img'=>$p->mainImage?asset('storage/'.$p->mainImage->url):'','price'=>(float)$p->price,'cp'=>$p->compare_price?(float)$p->compare_price:null,'desc'=>\Str::limit(strip_tags($p->description??''),120),'url'=>route('public.product',[$project->slug,$p->id]),'stock'=>$p->stock];
    @endphp
    <article class="nordic-card p-4 cursor-pointer group" id="prod-new-{{ $p->id }}" data-qv='@json($qvData)'>
      <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="block nordic-img relative overflow-hidden mb-3" style="aspect-ratio:1/1;">
        @if($p->mainImage)
        <img src="{{ asset('storage/'.$p->mainImage->url) }}" alt="{{ $p->name }}"
             loading="lazy" class="w-full h-full object-cover">
        @else
        <div class="w-full h-full flex items-center justify-center" style="background:var(--bg);">
          <svg class="w-8 h-8 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </div>
        @endif
        @if($p->compare_price && $p->compare_price > $p->price)
        <span class="absolute top-2 left-2 badge-p text-[10px] font-bold px-1.5 py-1 rounded-sm">
          -{{ round((($p->compare_price - $p->price) / $p->compare_price) * 100) }}%
        </span>
        @endif
        @if($p->created_at && $p->created_at->diffInDays() <= 30)
        <span class="absolute top-2 right-2 text-[10px] font-bold px-1.5 py-1 rounded-sm"
              style="background:var(--text);color:#fff;">Nuevo</span>
        @endif
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
      <div class="px-1">
        <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="text-sm font-semibold leading-snug line-clamp-2 mb-1 hover:underline block">{{ $p->name }}</a>
        @if(!$isQuoteOnly || $quotePriceDisp === 'show')
        <div class="flex items-center gap-2 mb-3">
          <span class="font-bold price-p">S/ {{ number_format($p->price,2) }}</span>
          @if($p->compare_price && $p->compare_price > $p->price)
          <span class="text-xs opacity-40 line-through">S/ {{ number_format($p->compare_price,2) }}</span>
          @endif
          @if($p->compare_price && $p->compare_price > $p->price)
          @php $ah = $p->compare_price - $p->price; @endphp
          <p class="text-[10px] text-green-600 font-semibold leading-none mt-1">Ahorras {{ $currency }} {{ number_format($ah,2) }}</p>
          @endif
        </div>
        @else
        <p class="text-xs opacity-40 italic mb-3">Precio a consultar</p>
        @endif
        <button class="nordic-btn w-full btn-outline py-2 text-xs font-semibold tracking-wider uppercase transition"
                @click="addToCart({
                  id:{{ $p->id }},
                  name:'{{ addslashes($p->name) }}',
                  price:{{ $p->price }},
                  img:'{{ $p->mainImage ? asset("storage/".$p->mainImage->url) : "" }}'
                })">
          {{ $isQuoteOnly ? 'Cotizar' : 'Agregar' }}
        </button>
      </div>
    </article>
    @endforeach
  </div>
</section>
@endif

{{-- ═══ BUSCADOR MÓVIL ═══ --}}
<div class="md:hidden px-4 py-2 bg-white border-b sticky top-[56px] z-10" style="border-color:var(--border);">
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
           class="w-full pl-9 pr-4 py-2 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-1"
           style="border-color:var(--border);background:var(--bg);">
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
</div>

{{-- ═══ CATÁLOGO COMPLETO (sin sidebar, filtros horizontales) ═══ --}}
<section id="catalogo" class="max-w-[1200px] mx-auto px-6 pb-20">
  <div class="py-10 border-t" style="border-color:var(--border);">
    <div class="text-center mb-8">
      <span class="text-xs font-semibold uppercase tracking-[.25em] opacity-50">Explorar todo</span>
      <h2 class="font-bold text-3xl mt-1">Catálogo Completo</h2>
      <div class="w-12 h-[1.5px] mx-auto mt-3" style="background:var(--c);"></div>
    </div>

    {{-- Filtros barra horizontal --}}
    <div class="flex flex-wrap items-center gap-3 justify-center mb-10 sticky top-16 z-20 bg-white/95 backdrop-blur-sm shadow-sm py-3 px-2 -mx-2 rounded-xl">
      {{-- Categorías pills --}}
      <button @click="filterCat=''"
              :class="filterCat==='' ? 'active' : ''"
              class="cat-pill px-4 py-2 text-sm font-medium rounded-full transition">
        Todo
      </button>
      @foreach($categories as $cat)
      <button @click="filterCat='{{ $cat->id }}'"
              :class="filterCat==='{{ $cat->id }}' ? 'active' : ''"
              class="cat-pill px-4 py-2 text-sm font-medium rounded-full transition">
        {{ $cat->name }}
      </button>
      @endforeach

      {{-- Separador --}}
      <div class="w-[1px] h-6 opacity-20 hidden md:block" style="background:var(--text);"></div>

      {{-- Precio dropdown --}}
      <div x-data="{ open: false }" class="relative">
        <button @click="open=!open"
                class="cat-pill px-4 py-2 text-sm font-medium rounded-full transition flex items-center gap-2">
          Precio
          <svg class="w-3.5 h-3.5" :class="open?'rotate-180':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div x-show="open" @click.away="open=false"
             class="absolute top-full left-0 mt-2 bg-white border rounded-xl p-3 shadow-lg z-20 min-w-[180px]"
             style="border-color:var(--border);">
          @foreach(['' => 'Todos los precios', '0-50' => 'Hasta S/ 50', '50-150' => 'S/ 50 — S/ 150', '150-500' => 'S/ 150 — S/ 500', '500+' => 'Más de S/ 500'] as $val => $label)
          <label class="flex items-center gap-2.5 text-sm cursor-pointer py-1.5 hover:opacity-100 opacity-70 transition">
            <input type="radio" x-model="priceFilter" value="{{ $val }}" @change="open=false"
                   class="w-3.5 h-3.5" style="accent-color:var(--c);">
            {{ $label }}
          </label>
          @endforeach
        </div>
      </div>

      {{-- Oferta checkbox --}}
      <label class="cat-pill px-4 py-2 text-sm font-medium rounded-full transition flex items-center gap-2 cursor-pointer"
             :class="onSaleFilter ? 'active' : ''">
        <input type="checkbox" x-model="onSaleFilter" class="hidden">
        Solo en oferta
      </label>

      {{-- Ordenar --}}
      <button @click="filterOpen=true"
              class="xl:hidden flex items-center gap-1.5 text-xs font-semibold border rounded-full px-3 py-2 bg-white/10 hover:bg-white/20 transition relative"
              style="border-color:var(--border,rgba(255,255,255,.15));color:inherit">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
        </svg>
        <span>Filtros</span>
        <span x-show="priceFilter!=='' || onSaleFilter"
              class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full text-[10px] font-black flex items-center justify-center text-white"
              style="background:var(--c)"
              x-text="(priceFilter!==''?1:0)+(onSaleFilter?1:0)"></span>
      </button>
      <select x-model="sortBy" class="cat-pill px-4 py-2 text-sm font-medium rounded-full transition bg-transparent cursor-pointer focus:outline-none">
        <option value="default">Ordenar...</option>
        <option value="price_asc">Precio ↑</option>
        <option value="price_desc">Precio ↓</option>
        <option value="newest">Más nuevos</option>
        <option value="name_az">Nombre A→Z</option>
      </select>

      {{-- Limpiar --}}
      <button x-show="filterCat!=='' || search!=='' || priceFilter!=='' || onSaleFilter"
              @click="filterCat=''; search=''; priceFilter=''; onSaleFilter=false"
              class="text-xs opacity-50 hover:opacity-100 underline underline-offset-4 transition">
        Limpiar
      </button>
    </div>

    {{-- Grid productos --}}
    <p class="text-xs text-gray-400 mb-4" x-show="filterCat!==''||search!==''||priceFilter!==''||onSaleFilter">
      <span x-text="visibleCount"></span> producto<span x-show="visibleCount!==1">s</span> encontrado<span x-show="visibleCount!==1">s</span>
    </p>
    <div class="space-y-14">
      @foreach($categories as $cat)
      @php $catAllProducts = $cat->products->merge($cat->children->flatMap->products); @endphp
      @if($catAllProducts->count())
      <div x-show="filterCat==='' || filterCat==='{{ $cat->id }}'">
        <div class="flex items-center gap-5 mb-7">
          <h3 class="font-bold text-xl">{{ $cat->name }}</h3>
          <div class="flex-1 border-t" style="border-color:var(--border);"></div>
          <span class="text-sm opacity-40">{{ $cat->products->count() }}</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-5" data-products-grid>
          @foreach($cat->products as $p)
          @php
          $qvData = ['id'=>$p->id,'name'=>$p->name,'img'=>$p->mainImage?asset('storage/'.$p->mainImage->url):'','price'=>(float)$p->price,'cp'=>$p->compare_price?(float)$p->compare_price:null,'desc'=>\Str::limit(strip_tags($p->description??''),120),'url'=>route('public.product',[$project->slug,$p->id]),'stock'=>$p->stock];
          @endphp
          <article class="nordic-card p-4 cursor-pointer group" id="producto-{{ $p->id }}"
                   x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }}) && ({{ $loop->index }} < 8 || expandedCats['{{ $cat->id }}'])"
                   data-price="{{ $p->price }}"
                   data-ts="{{ $p->created_at ? $p->created_at->timestamp : 0 }}"
                   data-idx="{{ $loop->index }}"
                   data-qv='@json($qvData)'>
            <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="block nordic-img relative overflow-hidden mb-3" style="aspect-ratio:1/1;">
              @if($p->mainImage)
              <img src="{{ asset('storage/'.$p->mainImage->url) }}" alt="{{ $p->name }} — {{ $project->name }}"
                   loading="lazy" class="w-full h-full object-cover">
              @else
              <div class="w-full h-full flex items-center justify-center" style="background:var(--bg);">
                <svg class="w-8 h-8 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
              </div>
              @endif
              @if($p->compare_price && $p->compare_price > $p->price)
              <span class="absolute top-2 left-2 badge-p text-[10px] font-bold px-1.5 py-1 rounded-sm">
                -{{ round((($p->compare_price - $p->price) / $p->compare_price) * 100) }}%
              </span>
              @endif
              @if($p->created_at && $p->created_at->diffInDays() <= 30)
              <span class="absolute top-2 right-2 text-[10px] font-bold px-1.5 py-1 rounded-sm"
                    style="background:var(--text);color:#fff;">Nuevo</span>
              @endif
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
            <div class="px-1">
              <p class="text-[11px] uppercase tracking-[.15em] opacity-30 mb-0.5 font-medium">{{ $cat->name }}</p>
              <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="text-sm font-semibold leading-snug line-clamp-2 mb-1 hover:underline block">{{ $p->name }}</a>
              @if(!$isQuoteOnly || $quotePriceDisp === 'show')
              @if(isset($productRatings) && isset($productRatings[$p->id]))
              <div class="flex items-center gap-1 mb-1">
                <span class="text-amber-400 text-xs">{{ str_repeat('★', floor($productRatings[$p->id]->avg_rating)) }}{{ str_repeat('☆', 5 - floor($productRatings[$p->id]->avg_rating)) }}</span>
                <span class="text-[10px] text-gray-400">({{ $productRatings[$p->id]->rating_count }})</span>
              </div>
              @endif
              <div class="flex items-center gap-2 mb-3">
                <span class="font-bold price-p">S/ {{ number_format($p->price,2) }}</span>
                @if($p->compare_price && $p->compare_price > $p->price)
                <span class="text-xs opacity-40 line-through">S/ {{ number_format($p->compare_price,2) }}</span>
                @endif
              </div>
              @else
              <p class="text-xs opacity-40 italic mb-3">Precio a consultar</p>
              @endif
              <button class="nordic-btn w-full btn-outline py-2 text-xs font-semibold tracking-wider uppercase transition"
                      @click="addToCart({
                        id:{{ $p->id }},
                        name:'{{ addslashes($p->name) }}',
                        price:{{ $p->price }},
                        img:'{{ $p->mainImage ? asset("storage/".$p->mainImage->url) : "" }}'
                      })">
                {{ $isQuoteOnly ? 'Cotizar' : 'Agregar' }}
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

      <div x-show="noResults" class="text-center py-16">
        <p class="font-bold text-xl mb-2">Sin resultados</p>
        <p class="opacity-50 text-sm mb-6">Intenta con otro término o categoría</p>
        <button @click="search=''; filterCat=''; priceFilter=''; onSaleFilter=false"
                class="btn-outline px-6 py-2.5 text-sm font-semibold tracking-wider uppercase transition">
          Ver todo el catálogo
        </button>
      </div>

      {{-- Vistos recientemente --}}
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
  </div>
</section>

{{-- ═══ FOOTER MINIMALISTA ═══ --}}
<footer style="background:var(--text);color:rgba(247,243,238,.7);">
  <div class="max-w-[1200px] mx-auto px-6 py-14 grid grid-cols-1 md:grid-cols-3 gap-10">
    {{-- Logo + desc --}}
    <div>
      @if($project->logo_url)
      <img src="{{ asset('storage/'.$project->logo_url) }}" alt="{{ $project->name }}"
           class="h-10 object-contain mb-4 brightness-0 invert opacity-80" loading="lazy">
      @else
      <p class="font-bold text-xl text-white mb-4 tracking-wide">{{ $project->name }}</p>
      @endif
      <p class="text-sm leading-relaxed opacity-60 max-w-xs">
        {{ $project->description ?? 'Diseño escandinavo para tu vida diaria.' }}
      </p>
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}"
         target="_blank" rel="noopener"
         class="inline-flex items-center gap-2 mt-5 bg-[#25D366] text-white text-xs font-semibold px-4 py-2.5 hover:bg-[#20ba5a] transition">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
        WhatsApp
      </a>
      @endif
    </div>

    {{-- Categorías --}}
    <div>
      <p class="font-bold text-white text-xs uppercase tracking-[.2em] mb-5">Colecciones</p>
      <ul class="space-y-3">
        @foreach($categories->take(5) as $cat)
        <li>
          <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                  class="text-sm opacity-60 hover:opacity-100 transition text-left">{{ $cat->name }}</button>
        </li>
        @endforeach
      </ul>
    </div>

    {{-- Contacto --}}
    <div>
      <p class="font-bold text-white text-xs uppercase tracking-[.2em] mb-5">Información</p>
      <div class="space-y-3 text-sm">
        @if($project->phone)
        <p class="opacity-60 flex items-center gap-2">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
          </svg>
          {{ $project->phone }}
        </p>
        @endif
        @if($project->address)
        <p class="opacity-60 flex items-start gap-2">
          <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          {{ $project->address }}
        </p>
        @endif
      </div>
      @php
        $footerSocials = [['key'=>'facebook_url','label'=>'Facebook'],['key'=>'instagram_url','label'=>'Instagram'],['key'=>'tiktok_url','label'=>'TikTok']];
      @endphp
      <div class="flex flex-wrap gap-2 mt-5">
        @foreach($footerSocials as $soc)
        @if($settings[$soc['key']] ?? null)
        <a href="{{ $settings[$soc['key']] }}" target="_blank" rel="noopener"
           class="text-xs px-3 py-1.5 border text-white hover:opacity-100 opacity-50 transition"
           style="border-color:rgba(255,255,255,.2);">{{ $soc['label'] }}</a>
        @endif
        @endforeach
      </div>
    </div>
  </div>
  <div class="border-t py-5 text-center text-xs opacity-30" style="border-color:rgba(255,255,255,.1);">
    &copy; {{ date('Y') }} {{ $project->name }} &mdash; Catálogo online por AVAN
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

    {{-- Header --}}
    <div class="flex items-center justify-between px-5 py-4 flex-shrink-0" style="border-bottom:1px solid var(--border);">
      <div class="flex items-center gap-2">
        <button x-show="(drawerStep===2 || drawerStep===3) && !orderSent"
                @click="drawerStep > 1 ? drawerStep-- : null"
                class="p-1.5 rounded-lg transition mr-1 hover:opacity-60" aria-label="Volver">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--text);">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <h2 class="font-bold text-base" style="color:var(--text);"
            x-text="drawerStep===1 ? '{{ $isQuoteOnly ? 'Mi cotización' : 'Tu pedido' }}' : (drawerStep===2 ? 'Confirmar datos' : 'Pagar')"></h2>
        <span x-show="cart.length && drawerStep===1"
              class="badge-p text-xs px-2 py-0.5 font-bold rounded-sm" x-text="cart.length + ' items'"></span>
      </div>
      <button @click="drawerOpen=false" class="p-2 rounded-lg transition hover:opacity-60" aria-label="Cerrar">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--text);">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    {{-- PASO 1 --}}
    <div x-show="drawerStep===1" class="flex flex-col flex-1 overflow-hidden">
      <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <template x-if="cart.length===0">
          <div class="text-center py-16 opacity-40">
            <svg class="w-14 h-14 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--text);opacity:.3;">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="font-semibold mb-1" style="color:var(--text);">{{ $isQuoteOnly ? 'Tu cotización está vacía' : 'Tu carrito está vacío' }}</p>
            <p class="text-sm opacity-60">Agrega productos para comenzar</p>
          </div>
        </template>
        <template x-for="(item, i) in cart" :key="item.id">
          <div class="flex items-center gap-3 p-3" style="border:1px solid var(--border);">
            <div class="w-14 h-14 overflow-hidden flex-shrink-0" style="background:var(--bg);">
              <img :src="item.img" x-show="item.img" class="w-full h-full object-cover" :alt="item.name">
              <div x-show="!item.img" class="w-full h-full flex items-center justify-center text-xs opacity-30">IMG</div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold line-clamp-2" x-text="item.name" style="color:var(--text);"></p>
              @if(!$isQuoteOnly || $quotePriceDisp === 'show')
              <p class="text-sm font-bold price-p mt-0.5" x-text="'S/ ' + (item.price * item.qty).toFixed(2)"></p>
              @endif
            </div>
            <div class="flex items-center gap-1 flex-shrink-0">
              <button @click="item.qty > 1 ? item.qty-- : cart.splice(i,1)"
                      class="w-8 h-8 border text-sm transition flex items-center justify-center hover:opacity-60"
                      style="border-color:var(--border);color:var(--text);">
                <span x-text="item.qty > 1 ? '−' : '×'"></span>
              </button>
              <span class="w-7 text-center text-sm font-bold" x-text="item.qty" style="color:var(--text);"></span>
              <button @click="item.qty++"
                      class="w-8 h-8 btn-accent text-white font-bold text-sm transition flex items-center justify-center">+</button>
            </div>
          </div>
        </template>
      </div>

      <div x-show="cart.length > 0" class="px-5 py-4 space-y-3 flex-shrink-0" style="border-top:1px solid var(--border);">
        <div class="flex justify-between items-center">
          <span class="text-sm opacity-50"><span x-text="cart.reduce((s,i)=>s+i.qty,0)"></span> productos</span>
          @if(!$isQuoteOnly || $quotePriceDisp === 'show')
          <span class="font-bold text-lg price-p" x-text="'S/ ' + cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></span>
          @else
          <span class="text-xs opacity-40 italic">Precios a cotizar</span>
          @endif
        </div>
        @if(!$isQuoteOnly && count($acceptedPayments) > 0)
        <div class="flex flex-wrap gap-1.5">
          @foreach($acceptedPayments as $pmKey)
          @if(isset($paymentMeta[$pmKey]))
          <span class="inline-flex items-center gap-1 text-xs px-2 py-1 font-medium rounded-sm"
                style="background:var(--bg);color:var(--text);">
            {{ $paymentMeta[$pmKey]['emoji'] }} {{ $paymentMeta[$pmKey]['label'] }}
          </span>
          @endif
          @endforeach
        </div>
        @endif
        <button @click="drawerStep=2; orderError=''"
                class="w-full btn-accent py-3.5 font-bold text-sm tracking-wider uppercase transition flex items-center justify-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
          </svg>
          {{ $isQuoteOnly ? 'Continuar y cotizar' : 'Continuar y pedir' }}
        </button>
      </div>
    </div>

    {{-- PASO 2 --}}
    <div x-show="drawerStep===2" class="flex flex-col flex-1 overflow-hidden">
      <div x-show="!orderSent" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <div class="px-4 py-3 flex justify-between items-center" style="background:var(--bg);">
          <span class="text-sm opacity-60"><span x-text="cart.reduce((s,i)=>s+i.qty,0)"></span> productos</span>
          @if(!$isQuoteOnly || $quotePriceDisp === 'show')
          <span class="font-bold price-p" x-text="'S/ ' + cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></span>
          @else
          <span class="text-xs opacity-40 italic">a cotizar</span>
          @endif
        </div>
        <input x-model="form.name" type="text" placeholder="Tu nombre completo *"
               class="w-full border px-4 py-2.5 text-sm outline-none transition" style="border-color:var(--border);" autocomplete="name">
        <input x-model="form.phone" type="tel" placeholder="Tu WhatsApp / teléfono *"
               class="w-full border px-4 py-2.5 text-sm outline-none transition" style="border-color:var(--border);" autocomplete="tel">
        <input x-model="form.email" type="email" placeholder="Tu correo electrónico (opcional)"
               class="w-full border px-4 py-2.5 text-sm outline-none transition" style="border-color:var(--border);" autocomplete="email">
        <textarea x-model="form.notes" rows="2" placeholder="Nota adicional (opcional)"
                  class="w-full border px-4 py-2.5 text-sm outline-none resize-none transition" style="border-color:var(--border);"></textarea>
        @if($requireAddress)
        <input x-model="form.address" type="text" placeholder="Dirección de entrega *"
               class="w-full border px-4 py-2.5 text-sm outline-none transition" style="border-color:var(--border);"
               autocomplete="street-address">
        @endif
        {{-- Cupón --}}
        <div>
          <div x-show="!couponApplied" class="flex gap-2">
            <input x-model="couponCode" @keydown.enter.prevent="applyCoupon" type="text"
                   placeholder="Código de descuento"
                   class="flex-1 rounded-xl px-4 py-2.5 text-sm outline-none transition uppercase"
                   style="background:var(--bg);border:1px solid var(--border);text-transform:uppercase">
            <button @click="applyCoupon" :disabled="couponLoading" type="button"
                    class="px-4 py-2.5 rounded-xl text-sm font-semibold flex-shrink-0 transition"
                    style="background:var(--bg);border:1px solid var(--border);">
              <span x-text="couponLoading ? '…' : 'Aplicar'"></span>
            </button>
          </div>
          <div x-show="couponApplied" class="flex items-center justify-between bg-green-50 border border-green-200 rounded-xl px-4 py-2.5 text-sm">
            <div>
              <span class="font-mono font-bold text-green-700" x-text="couponApplied ? couponApplied.code : ''"></span>
              <span class="text-green-600 ml-1">&mdash; <span x-text="couponApplied && couponApplied.type==='percent' ? couponApplied.value+'% desc.' : 'S/ '+(couponApplied?couponApplied.value:0).toFixed(2)+' desc.'"></span></span>
            </div>
            <button @click="removeCoupon" type="button" class="text-gray-400 hover:text-red-500 ml-3 text-lg leading-none">&times;</button>
          </div>
          <p x-show="couponError" class="text-red-500 text-xs mt-1" x-text="couponError"></p>
        </div>
        <div x-show="shippingEnabled || couponApplied" class="rounded-xl px-4 py-3 space-y-1.5 text-sm" style="background:var(--bg);">
          <div class="flex justify-between opacity-60"><span>Subtotal</span><span x-text="'S/ ' + subtotal.toFixed(2)"></span></div>
          <div x-show="couponApplied && couponDiscount > 0" class="flex justify-between text-green-600 font-medium">
            <span>Descuento</span>
            <span x-text="'- S/ ' + couponDiscount.toFixed(2)"></span>
          </div>
          <div x-show="shippingEnabled" class="flex justify-between" :class="effectiveShipping===0 && shippingFreeFrom>0 ? 'text-green-600 font-medium' : 'opacity-60'">
            <span x-text="effectiveShipping===0 && shippingFreeFrom>0 ? '🎉 Envío gratis' : 'Envío'"></span>
            <span x-text="effectiveShipping>0 ? 'S/ '+effectiveShipping.toFixed(2) : 'Gratis'"></span>
          </div>
          @if($shippingFreeFrom > 0)<p x-show="effectiveShipping>0" class="text-xs opacity-40">Agrega S/ <span x-text="Math.max(0,{{ $shippingFreeFrom }}-subtotal).toFixed(2)"></span> más para envío gratis</p>@endif
          <div class="flex justify-between font-black" style="border-top:1px solid var(--border);padding-top:6px;"><span>Total</span><span x-text="'S/ '+orderGrandTotal.toFixed(2)"></span></div>
        </div>
        <p x-show="orderError" class="text-red-500 text-xs text-center font-medium" x-text="orderError"></p>
      </div>

      <div x-show="orderSent" class="flex-1 flex flex-col items-center justify-center px-5 py-8 text-center">
        <div class="w-20 h-20 bg-green-100 flex items-center justify-center mb-5">
          <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <p class="font-bold text-xl mb-2" style="color:var(--text);">{{ $isQuoteOnly ? '¡Cotización enviada!' : '¡Pedido confirmado!' }}</p>
        <p x-show="orderId" class="text-xs opacity-50 mb-1">Pedido N° <span class="font-black" style="color:var(--text);" x-text="orderId"></span></p>
        <p class="text-sm opacity-50 mb-6 leading-relaxed">{{ $isQuoteOnly ? 'Recibimos tu solicitud.' : 'Nos pondremos en contacto pronto.' }}</p>
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
                class="btn-accent px-8 py-3 text-sm font-semibold tracking-wider uppercase">
          {{ $isQuoteOnly ? 'Seguir explorando' : 'Seguir comprando' }}
        </button>
      </div>

      <div x-show="!orderSent" class="px-5 py-4 flex-shrink-0" style="border-top:1px solid var(--border);">
        @if(!$isQuoteOnly && $hasOnlinePayment)
        <button @click="submitOrder()" :disabled="orderLoading"
                class="w-full btn-accent py-4 font-bold text-sm tracking-wider uppercase transition disabled:opacity-60 flex items-center justify-center gap-2">
          <template x-if="!orderLoading"><span>Continuar al pago</span></template>
          <svg x-show="orderLoading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span x-show="orderLoading">Procesando...</span>
        </button>
        @else
        <button @click="submitOrder()" :disabled="orderLoading"
                class="w-full py-4 font-bold text-sm tracking-wider uppercase transition disabled:opacity-60 flex items-center justify-center gap-2
                       {{ $isQuoteOnly ? 'bg-[#25D366] hover:bg-[#20ba5a] text-white' : 'btn-accent' }}">
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
    {{-- PASO 3 --}}
    <div x-show="drawerStep===3" class="flex flex-col flex-1 overflow-hidden">
      <div x-show="!orderSent" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <div class="px-4 py-3 flex justify-between items-center" style="background:var(--bg);">
          <span class="text-sm opacity-60">Total a pagar</span>
          <span class="font-bold text-lg price-p" x-text="'S/ ' + orderTotal.toFixed(2)"></span>
        </div>
        <p x-show="payError" class="text-red-500 text-xs text-center font-medium px-2" x-text="payError"></p>

        @if($payManualEnabled && count($payManualMethods) > 0)
        <div class="space-y-2">
          <p class="text-xs font-semibold opacity-40 uppercase tracking-wide">Pago manual</p>
          @foreach($payManualMethods as $mKey)
          @php
            $mMeta = ['yape'=>['label'=>'Yape','emoji'=>'🟣'],'plin'=>['label'=>'Plin','emoji'=>'🔵'],'transferencia'=>['label'=>'Transferencia bancaria','emoji'=>'🏦'],'qr'=>['label'=>'Pago con QR','emoji'=>'📲'],'contra_entrega'=>['label'=>'Contra entrega','emoji'=>'🚚']];
            $mm = $mMeta[$mKey] ?? null;
            $mmDetails = match($mKey) { 'yape' => $payYapeNumber, 'plin' => $payPlinNumber, 'transferencia' => $payBankDetails, default => '' };
          @endphp
          @if($mm)
          <div x-data="{ open: false }" class="border overflow-hidden" style="border-color:var(--border);">
            <button @click="open = !open"
                    class="w-full flex items-center gap-3 px-4 py-3 text-left transition hover:opacity-80">
              <span class="text-2xl leading-none">{{ $mm['emoji'] }}</span>
              <div class="flex-1">
                <p class="text-sm font-semibold" style="color:var(--text);">{{ $mm['label'] }}</p>
                @if($mmDetails)<p class="text-xs opacity-40 truncate">{{ Str::limit($mmDetails, 40) }}</p>@endif
              </div>
              <svg class="w-4 h-4 opacity-40 transition-transform" :class="open ? 'rotate-180' : ''"
                   fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
            <div x-show="open" class="px-4 pb-4 pt-3 space-y-3" style="background:var(--bg);">
              @if($mmDetails)
              <div class="bg-white border p-3" style="border-color:var(--border);">
                <p class="text-xs font-semibold opacity-50 mb-1">Datos para pagar:</p>
                <p class="text-sm whitespace-pre-line" style="color:var(--text);">{{ $mmDetails }}</p>
              </div>
              @endif
              @if($payManualInstr)<p class="text-xs opacity-40 italic">{{ $payManualInstr }}</p>@endif
              <div>
                <label class="text-xs font-semibold opacity-50 block mb-1">Número de operación / referencia *</label>
                <input x-model="payReference" type="text" placeholder="Ej: 123456789"
                       class="w-full border px-4 py-2.5 text-sm outline-none transition" style="border-color:var(--border);">
              </div>
              <button @click="confirmManualPay()" :disabled="payLoading || !payReference.trim()"
                      class="w-full btn-accent py-3 font-bold text-sm tracking-wider uppercase transition disabled:opacity-50 flex items-center justify-center gap-2">
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
          <p class="text-xs font-semibold opacity-40 uppercase tracking-wide">Pago con tarjeta</p>
          <button @click="openCulqi()" :disabled="payLoading"
                  class="w-full flex items-center gap-3 px-4 py-3.5 border text-left hover:opacity-80 transition disabled:opacity-50"
                  style="border-color:var(--border);">
            <span class="text-2xl leading-none">💳</span>
            <div class="flex-1">
              <p class="text-sm font-semibold" style="color:var(--text);">Tarjeta crédito / débito</p>
              <p class="text-xs opacity-40">Visa, Mastercard — pago seguro vía Culqi</p>
            </div>
            <svg class="w-4 h-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
        <script src="https://checkout.culqi.com/js/v4"></script>
        @endif

        @if($mpEnabled)
        <div class="space-y-2">
          <p class="text-xs font-semibold opacity-40 uppercase tracking-wide">Mercado Pago</p>
          <button @click="openMercadoPago()" :disabled="payLoading"
                  class="w-full flex items-center gap-3 px-4 py-3.5 border text-left hover:opacity-80 transition disabled:opacity-50"
                  style="border-color:var(--border);">
            <span class="text-2xl leading-none">🛒</span>
            <div class="flex-1">
              <p class="text-sm font-semibold" style="color:var(--text);">Mercado Pago</p>
              <p class="text-xs opacity-40">Tarjetas, wallets, cuotas</p>
            </div>
            <svg x-show="!payLoading" class="w-4 h-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <svg x-show="payLoading" class="w-4 h-4 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
          </button>
        </div>
        @endif
        <p class="text-center text-xs opacity-30 pt-2">Tus datos están protegidos</p>
      </div>

      <div x-show="orderSent" class="flex-1 flex flex-col items-center justify-center px-5 py-8 text-center">
        <div class="w-20 h-20 bg-green-100 flex items-center justify-center mb-5">
          <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <p class="font-bold text-xl mb-2" style="color:var(--text);">¡Pago registrado!</p>
        <p x-show="orderId" class="text-xs opacity-50 mb-1">Pedido N° <span class="font-black" style="color:var(--text);" x-text="orderId"></span></p>
        <p class="text-sm opacity-50 mb-6">Tu pedido está confirmado.</p>
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
                class="btn-accent px-8 py-3 text-sm font-semibold tracking-wider uppercase">Seguir comprando</button>
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

{{-- ═══ ALPINE STORE ═══ --}}
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
    filterOpen: false,
    priceMin: 0,
    priceMax: 0,
    // Predictive search
    searchIndex: @json($searchIndex),
    searchOpen: false,
    searchIdx: -1,
    searchFocus: false,
    qv: null,
    qvOpen: false,
    expandedCats: {},
    recentlyViewed: [],
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
