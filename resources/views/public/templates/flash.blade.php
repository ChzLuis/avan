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
  $primaryColor     = $settings['primary_color'] ?? '#ef4444';
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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

@php
  $secondaryColor = $settings['secondary_color'] ?? '#f97316';
  $fontTitle  = $settings['font_title'] ?? $settings['font'] ?? 'Inter';
  $fontBody   = $settings['font_body']  ?? $settings['font'] ?? 'Inter';
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
  --c: {{ $primaryColor }};
  --c2: {{ $secondaryColor }};
  --radius: {{ $borderRadius }};
  --font-title: '{{ $fontTitle }}', sans-serif;
  --font-body:  '{{ $fontBody }}', sans-serif;
}
*, body { font-family: var(--font-body); }
body { background: #f5f5f5; }
.btn-red      { background: #ef4444; color: #fff; }
.btn-red:hover{ background: #dc2626; }
.btn-p        { background: var(--c); color: #fff; }
.btn-p:hover  { filter: brightness(.9); }
.price-p      { color: #ef4444; }
.badge-p      { background: var(--c); color: #fff; }
.badge-red    { background: #ef4444; color: #fff; }

[x-cloak]{ display:none!important; }

/* Drawer */
.drawer-overlay { position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:40;backdrop-filter:blur(2px); }
.drawer { position:fixed;top:0;right:0;height:100%;width:420px;max-width:96vw;background:#fff;z-index:50;display:flex;flex-direction:column;box-shadow:-6px 0 32px rgba(0,0,0,.15); }
@media(max-width:640px){ .drawer{ width:100%; } }

/* Flash card */
.flash-card { background: #fff; border: 1.5px solid #fecaca; transition: box-shadow .2s, transform .2s; }
.flash-card:hover { box-shadow: 0 6px 24px rgba(239,68,68,.15); transform: translateY(-2px); }

/* No scrollbar */
.no-scroll { overflow-x:auto; scrollbar-width:none; }
.no-scroll::-webkit-scrollbar { display:none; }

/* Blink animation */
@keyframes blink { 0%,100%{ opacity:1; } 50%{ opacity:.5; } }
.blink { animation: blink 1.2s ease-in-out infinite; }

/* Countdown badge */
.countdown { font-feature-settings: "tnum"; font-variant-numeric: tabular-nums; }
</style>

@php
$currency = $settings['currency'] ?? 'S/';
$searchIndex = $categories->flatMap(fn($cat) => $cat->products->map(fn($p) => [
    'id'   => $p->id,
    'name' => $p->name,
    'price'=> (float)$p->price,
    'cp'   => $p->compare_price ? (float)$p->compare_price : null,
    'img'  => $p->mainImage ? asset('storage/'.$p->mainImage->url) : null,
    'cat'  => $cat->name,
    'url'  => route('public.product', [$project->slug, $p->id]),
]))->values();
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

{{-- ═══ BARRA ROJA SUPERIOR ═══ --}}
<div class="bg-red-600 text-white py-2 px-4 text-center text-sm font-bold flex items-center justify-center gap-6 flex-wrap">
  <span class="blink">OFERTA RELÁMPAGO</span>
  <span class="opacity-50">|</span>
  <span class="flex items-center gap-2">
    Termina en:
    <span class="countdown bg-red-800 px-3 py-0.5 rounded-full text-sm font-black tracking-widest"
          x-data="{
            h:23, m:59, s:59,
            tick() {
              if(this.s > 0) { this.s--; }
              else if(this.m > 0) { this.m--; this.s=59; }
              else if(this.h > 0) { this.h--; this.m=59; this.s=59; }
              else { this.h=23; this.m=59; this.s=59; }
            },
            fmt(n){ return String(n).padStart(2,'0'); },
            get display(){ return this.fmt(this.h)+':'+this.fmt(this.m)+':'+this.fmt(this.s); }
          }"
          x-init="setInterval(()=>tick(), 1000)"
          x-text="display">
    </span>
  </span>
  <span class="opacity-50">|</span>
  <span class="blink text-yellow-200">Envio Gratis</span>
</div>

{{-- ═══ HEADER COMPACTO ═══ --}}
<header class="bg-white sticky top-0 z-30 border-b border-gray-200" style="box-shadow:0 2px 8px rgba(0,0,0,.08);">
  <div class="max-w-[1400px] mx-auto px-4 py-3 flex items-center gap-4">

    {{-- Logo --}}
    <a href="{{ $canonicalUrl }}" class="flex-shrink-0 flex items-center gap-2" aria-label="{{ $project->name }}">
      @if($project->logo_url)
        <img src="{{ asset('storage/'.$project->logo_url) }}" alt="Logo {{ $project->name }}"
             class="h-9 object-contain" width="100" height="36">
      @else
        <span class="font-black text-xl text-gray-900">{{ $project->name }}</span>
      @endif
    </a>

    {{-- Buscador central grande --}}
    <div class="flex-1 flex items-stretch max-w-2xl mx-auto">
      <div class="relative flex-1" @click.outside="searchOpen = false">
      <input x-model="search" type="search" placeholder="Buscar productos, marcas, categorías..."
             @input="searchOpen = search.trim().length >= 2; searchIdx = -1; if(search.trim().length >= 2) _scrollToCatalog()"
             @keydown.arrow-down.prevent="if(suggestions.length){searchIdx=(searchIdx+1)%suggestions.length}"
             @keydown.arrow-up.prevent="if(suggestions.length){searchIdx=(searchIdx-1+suggestions.length)%suggestions.length}"
             @keydown.enter.prevent="if(searchIdx>=0){selectSuggestion(suggestions[searchIdx])}else{searchOpen=false;_scrollToCatalog()}"
             @keydown.escape="searchOpen=false;searchIdx=-1"
             aria-label="Buscar en el catálogo"
             class="w-full border-2 border-gray-200 border-r-0 rounded-l-lg px-4 py-2.5 text-sm outline-none focus:border-red-400 transition">
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
      <button @click="searchOpen=false; _scrollToCatalog()" class="bg-red-500 hover:bg-red-600 text-white px-5 py-2.5 rounded-r-lg font-bold text-sm transition flex items-center gap-1.5 flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
        </svg>
        <span class="hidden sm:block">Buscar</span>
      </button>
    </div>

    {{-- Acciones --}}
    <div class="flex items-center gap-2 flex-shrink-0">
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}"
         target="_blank" rel="noopener" title="WhatsApp"
         class="hidden sm:flex items-center gap-1.5 bg-[#25D366] hover:bg-[#20ba5a] text-white text-xs font-bold px-3 py-2 rounded-lg transition">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
        </svg>
        WA
      </a>
      @endif
      <button @click="drawerOpen=true" class="relative p-2.5 hover:bg-gray-100 rounded-lg transition" aria-label="Ver carrito">
        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <span x-show="cartCount > 0" x-text="cartCount"
              class="absolute -top-0.5 -right-0.5 badge-red text-white text-[10px] font-black rounded-full w-5 h-5 flex items-center justify-center leading-none"></span>
      </button>
    </div>
  </div>
</header>

{{-- ═══ BARRA DE CATEGORÍAS ═══ --}}
@if($categories->count())
<div class="bg-white border-b border-gray-200" style="box-shadow:0 1px 4px rgba(0,0,0,.04);">
  <div class="max-w-[1400px] mx-auto px-4">
    <div class="flex items-center gap-1 no-scroll py-2">
      <button @click="filterCat=''; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
              :class="filterCat==='' ? 'bg-red-500 text-white' : 'text-gray-600 hover:bg-gray-100'"
              class="flex-shrink-0 flex flex-col items-center gap-1 px-4 py-2 rounded-lg text-xs font-semibold transition min-w-[72px]">
        <span class="text-lg">🏪</span>
        <span>Todo</span>
      </button>
      @foreach($categories as $cat)
      @php
        $catIcons = ['👕','👖','👟','👜','💄','🏠','📱','💻','🎮','🧴','🍕','🎁'];
        $icon = $catIcons[$loop->index % count($catIcons)];
      @endphp
      <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
              :class="filterCat==='{{ $cat->id }}' ? 'bg-red-500 text-white' : 'text-gray-600 hover:bg-gray-100'"
              class="flex-shrink-0 flex flex-col items-center gap-1 px-4 py-2 rounded-lg text-xs font-semibold transition min-w-[72px]">
        <span class="text-lg">{{ $icon }}</span>
        <span class="truncate max-w-[64px]">{{ $cat->name }}</span>
      </button>
      @endforeach
    </div>
  </div>
</div>
@endif

{{-- ═══ HERO CAROUSEL ═══ --}}
<section x-data="{ slide: 0, playing: true }"
         x-init="setInterval(() => { if(playing) slide = slide === 0 ? 1 : 0; }, 4000)"
         class="relative overflow-hidden" style="height:50vh;min-height:360px;">

  {{-- Slide 1 --}}
  <div class="absolute inset-0 transition-opacity duration-700"
       :class="slide===0 ? 'opacity-100' : 'opacity-0'"
       style="background:linear-gradient(135deg,#ef4444 0%,#b91c1c 100%);">
    <div class="absolute inset-0 flex items-center">
      <div class="max-w-[1400px] mx-auto px-8 w-full flex items-center justify-between">
        <div class="max-w-lg">
          <span class="inline-block bg-yellow-400 text-yellow-900 text-xs font-black px-3 py-1.5 rounded-full uppercase tracking-wider mb-4">
            Oferta del día
          </span>
          <h1 class="text-white font-black text-4xl md:text-6xl leading-none mb-3 tracking-tight">
            {{ $settings['hero_title'] ?? 'Precios Increíbles' }}
          </h1>
          <p class="text-white/80 text-base md:text-lg mb-6 font-medium">
            {{ $settings['hero_subtitle'] ?? 'Los mejores productos al mejor precio' }}
          </p>
          <button @click="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                  class="bg-white text-red-600 px-8 py-3.5 font-black text-sm uppercase tracking-wider rounded-full hover:bg-yellow-400 hover:text-yellow-900 transition-all">
            Ver todas las ofertas
          </button>
        </div>
        <div class="text-white/10 font-black text-[14rem] leading-none select-none hidden lg:block">%</div>
      </div>
    </div>
  </div>

  {{-- Slide 2 --}}
  <div class="absolute inset-0 transition-opacity duration-700"
       :class="slide===1 ? 'opacity-100' : 'opacity-0'"
       style="background:linear-gradient(135deg,#f97316 0%,#ea580c 100%);">
    <div class="absolute inset-0 flex items-center">
      <div class="max-w-[1400px] mx-auto px-8 w-full flex items-center justify-between">
        <div class="max-w-lg">
          <span class="inline-block bg-white text-orange-600 text-xs font-black px-3 py-1.5 rounded-full uppercase tracking-wider mb-4">
            Nuevo ingreso
          </span>
          <h1 class="text-white font-black text-4xl md:text-6xl leading-none mb-3 tracking-tight">
            {{ $settings['banner1_title'] ?? 'Nuevos Productos' }}
          </h1>
          <p class="text-white/80 text-base md:text-lg mb-6 font-medium">
            {{ $settings['banner1_sub'] ?? 'Descubre las últimas novedades' }}
          </p>
          <button @click="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                  class="bg-white text-orange-600 px-8 py-3.5 font-black text-sm uppercase tracking-wider rounded-full hover:bg-yellow-400 hover:text-yellow-900 transition-all">
            Explorar ahora
          </button>
        </div>
        <div class="text-white/10 font-black text-[14rem] leading-none select-none hidden lg:block">★</div>
      </div>
    </div>
  </div>

  {{-- Dots --}}
  <div class="absolute bottom-5 left-1/2 -translate-x-1/2 flex gap-2">
    <button @click="slide=0; playing=false" :class="slide===0 ? 'bg-white w-6' : 'bg-white/40 w-3'"
            class="h-3 rounded-full transition-all"></button>
    <button @click="slide=1; playing=false" :class="slide===1 ? 'bg-white w-6' : 'bg-white/40 w-3'"
            class="h-3 rounded-full transition-all"></button>
  </div>
</section>

{{-- ═══ 3 BANNERS ═══ --}}
<section class="max-w-[1400px] mx-auto px-4 py-6">
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-red-500 rounded-2xl p-6 text-white relative overflow-hidden cursor-pointer hover:bg-red-600 transition"
         @click="onSaleFilter=true; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})">
      <div class="absolute -right-6 -bottom-6 w-28 h-28 rounded-full bg-white/10"></div>
      <p class="text-xs font-bold uppercase tracking-widest opacity-80 mb-1">Ofertas activas</p>
      <p class="font-black text-2xl leading-tight">{{ $settings['banner1_title'] ?? 'Hasta 50% OFF' }}</p>
      <p class="text-white/70 text-sm mt-1">{{ $settings['banner1_sub'] ?? 'En productos seleccionados' }}</p>
      <span class="inline-block mt-3 bg-white/20 px-3 py-1 rounded-full text-xs font-bold">Ver ofertas →</span>
    </div>
    <div class="bg-orange-500 rounded-2xl p-6 text-white relative overflow-hidden cursor-pointer hover:bg-orange-600 transition"
         @click="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})">
      <div class="absolute -right-6 -top-6 w-28 h-28 rounded-full bg-white/10"></div>
      <p class="text-xs font-bold uppercase tracking-widest opacity-80 mb-1">Nuevos ingresos</p>
      <p class="font-black text-2xl leading-tight">{{ $settings['banner2_title'] ?? 'Lo Más Nuevo' }}</p>
      <p class="text-white/70 text-sm mt-1">{{ $settings['banner2_sub'] ?? 'Recién llegados' }}</p>
      <span class="inline-block mt-3 bg-white/20 px-3 py-1 rounded-full text-xs font-bold">Explorar →</span>
    </div>
    <div class="bg-blue-600 rounded-2xl p-6 text-white relative overflow-hidden cursor-pointer hover:bg-blue-700 transition"
         @click="drawerOpen=true">
      <div class="absolute -right-4 -bottom-4 w-24 h-24 rounded-full bg-white/10"></div>
      <p class="text-xs font-bold uppercase tracking-widest opacity-80 mb-1">Tu carrito</p>
      <p class="font-black text-2xl leading-tight">Pedido Fácil</p>
      <p class="text-white/70 text-sm mt-1">Compra rápida y segura</p>
      <span class="inline-block mt-3 bg-white/20 px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1.5 w-fit">
        <span x-text="'Ver carrito (' + cartCount + ')'" class="text-xs font-bold"></span>
      </span>
    </div>
  </div>
</section>

{{-- ═══ CATÁLOGO COMPLETO (grid denso) ═══ --}}
<section id="catalogo" class="max-w-[1400px] mx-auto px-4 pb-16">

  {{-- Barra filtros sticky --}}
  <div class="sticky top-16 z-20 bg-white/95 backdrop-blur-sm border border-gray-200 rounded-xl px-4 py-3 mb-5 flex items-center gap-3 flex-wrap shadow-sm border-b border-gray-100">
    <div class="flex items-center gap-2 no-scroll flex-1 min-w-0">
      <button @click="filterCat=''"
              :class="filterCat==='' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
              class="px-3 py-1.5 text-xs font-bold rounded-full transition whitespace-nowrap flex-shrink-0">
        Todo
      </button>
      @foreach($categories as $cat)
      <button @click="filterCat='{{ $cat->id }}'"
              :class="filterCat==='{{ $cat->id }}' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
              class="px-3 py-1.5 text-xs font-bold rounded-full transition whitespace-nowrap flex-shrink-0">
        {{ $cat->name }}
      </button>
      @endforeach
    </div>

    {{-- Filtros adicionales --}}
    <div class="flex items-center gap-2 flex-shrink-0">
      <button @click="filterOpen=true"
              class="xl:hidden flex items-center gap-1.5 text-xs font-semibold border border-gray-300 rounded-full px-3 py-1.5 bg-white hover:bg-gray-50 transition relative">
        <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
        </svg>
        <span>Filtros</span>
        <span x-show="priceFilter!=='' || onSaleFilter"
              class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full text-[10px] font-black flex items-center justify-center text-white"
              style="background:var(--c)"
              x-text="(priceFilter!==''?1:0)+(onSaleFilter?1:0)"></span>
      </button>
      <select x-model="sortBy" class="text-xs border border-gray-200 rounded-full px-2 py-1.5 outline-none bg-white text-gray-700 cursor-pointer hover:border-gray-400 transition">
        <option value="default">Ordenar</option>
        <option value="price_asc">Precio ↑</option>
        <option value="price_desc">Precio ↓</option>
        <option value="newest">Más nuevos</option>
        <option value="name_az">Nombre A→Z</option>
      </select>
      {{-- Solo oferta --}}
      <button @click="onSaleFilter=!onSaleFilter"
              :class="onSaleFilter ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
              class="px-3 py-1.5 text-xs font-bold rounded-full transition flex items-center gap-1">
        🏷️ Oferta
      </button>

      {{-- Precio dropdown --}}
      <div x-data="{open:false}" class="relative">
        <button @click="open=!open"
                :class="priceFilter ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="px-3 py-1.5 text-xs font-bold rounded-full transition flex items-center gap-1">
          Precio
          <svg class="w-3 h-3" :class="open?'rotate-180':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div x-show="open" @click.away="open=false"
             class="absolute right-0 top-full mt-2 bg-white border border-gray-200 rounded-xl p-3 shadow-xl z-30 min-w-[175px]">
          @foreach(['' => 'Todos', '0-50' => 'Hasta S/ 50', '50-150' => 'S/ 50 — S/ 150', '150-500' => 'S/ 150 — S/ 500', '500+' => 'Más de S/ 500'] as $val => $label)
          <label class="flex items-center gap-2 text-xs cursor-pointer py-1.5 text-gray-700 hover:text-red-600 transition">
            <input type="radio" x-model="priceFilter" value="{{ $val }}" @change="open=false"
                   class="w-3 h-3 accent-red-500">
            {{ $label }}
          </label>
          @endforeach
        </div>
      </div>

      {{-- Limpiar --}}
      <button x-show="filterCat!=='' || search!=='' || priceFilter!=='' || onSaleFilter"
              @click="filterCat=''; search=''; priceFilter=''; onSaleFilter=false; priceMin=0; priceMax=0"
              class="px-3 py-1.5 text-xs font-bold rounded-full bg-gray-200 hover:bg-gray-300 text-gray-700 transition">
        ✕ Limpiar
      </button>
    </div>
  </div>

  <p class="text-xs text-gray-400 mb-4" x-show="filterCat!==''||search!==''||priceFilter!==''||onSaleFilter">
    <span x-text="visibleCount"></span> producto<span x-show="visibleCount!==1">s</span> encontrado<span x-show="visibleCount!==1">s</span>
  </p>

  {{-- Grid denso --}}
  <div class="space-y-10">
    @foreach($categories as $cat)
    @if($cat->products->count())
    <div x-show="filterCat==='' || filterCat==='{{ $cat->id }}'">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-1.5 h-6 bg-red-500 rounded-full flex-shrink-0"></div>
        <h2 class="font-black text-gray-900 text-lg">{{ $cat->name }}</h2>
        <span class="badge-red text-[10px] px-2 py-0.5 rounded-full font-black">{{ $cat->products->count() }}</span>
        <div class="flex-1 border-t border-gray-200"></div>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3" data-products-grid>
        @foreach($cat->products as $p)
        @php
        $qvData = ['id'=>$p->id,'name'=>$p->name,'img'=>$p->mainImage?asset('storage/'.$p->mainImage->url):'','price'=>(float)$p->price,'cp'=>$p->compare_price?(float)$p->compare_price:null,'desc'=>\Str::limit(strip_tags($p->description??''),120),'url'=>route('public.product',[$project->slug,$p->id]),'stock'=>$p->stock];
        @endphp
        <article class="flash-card group rounded-xl overflow-hidden cursor-pointer" id="producto-{{ $p->id }}"
                 x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }}) && ({{ $loop->index }} < 8 || expandedCats['{{ $cat->id }}'])"
                 data-price="{{ $p->price }}"
                 data-ts="{{ $p->created_at ? $p->created_at->timestamp : 0 }}"
                 data-idx="{{ $loop->index }}"
                 data-qv='@json($qvData)'>

          <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="block relative bg-gray-50 overflow-hidden" style="aspect-ratio:1/1;">
            @if($p->mainImage)
            <img src="{{ asset('storage/'.$p->mainImage->url) }}" alt="{{ $p->name }}"
                 loading="lazy" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
            @else
            <div class="w-full h-full flex items-center justify-center bg-gray-100">
              <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
            @endif

            {{-- Badge descuento SIEMPRE visible --}}
            @if($p->compare_price && $p->compare_price > $p->price)
            <span class="absolute top-2 left-2 bg-red-500 text-white text-[10px] font-black px-1.5 py-1 rounded-lg leading-none">
              -{{ round((($p->compare_price - $p->price) / $p->compare_price) * 100) }}%
            </span>
            @endif

            @if($p->created_at && $p->created_at->diffInDays() <= 30)
            <span class="absolute top-2 right-2 bg-blue-500 text-white text-[10px] font-black px-1.5 py-1 rounded-lg leading-none">
              NUEVO
            </span>
            @endif
            {{-- Badge stock --}}
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

          <div class="p-2.5">
            <p class="text-[10px] text-gray-400 font-medium mb-0.5 truncate">{{ $cat->name }}</p>
            <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="text-xs font-bold text-gray-900 leading-snug line-clamp-2 mb-1.5 min-h-[2.5rem] hover:underline block">{{ $p->name }}</a>

            @if(isset($productRatings) && isset($productRatings[$p->id]))
            <div class="flex items-center gap-1 mb-1">
              <span class="text-amber-400 text-xs">{{ str_repeat('★', floor($productRatings[$p->id]->avg_rating)) }}{{ str_repeat('☆', 5 - floor($productRatings[$p->id]->avg_rating)) }}</span>
              <span class="text-[10px] text-gray-400">({{ $productRatings[$p->id]->rating_count }})</span>
            </div>
            @endif

            @if(!$isQuoteOnly || $quotePriceDisp === 'show')
            <div class="mb-2">
              <span class="font-black text-red-500 text-base">S/ {{ number_format($p->price,2) }}</span>
              @if($p->compare_price && $p->compare_price > $p->price)
              <span class="text-xs text-gray-400 line-through ml-1">S/ {{ number_format($p->compare_price,2) }}</span>
              @endif
              @if($p->compare_price && $p->compare_price > $p->price)
              @php $ah = $p->compare_price - $p->price; @endphp
              <p class="text-[10px] text-green-600 font-semibold leading-none mt-1">Ahorras {{ $currency }} {{ number_format($ah,2) }}</p>
              @endif
            </div>
            @else
            <p class="text-xs text-gray-400 italic mb-2">Precio a consultar</p>
            @endif

            {{-- Botón SIEMPRE visible (no solo en hover) --}}
            <button @click="addToCart({
                      id:{{ $p->id }},
                      name:'{{ addslashes($p->name) }}',
                      price:{{ $p->price }},
                      img:'{{ $p->mainImage ? asset("storage/".$p->mainImage->url) : "" }}'
                    })"
                    {{ $p->stock !== null && $p->stock === 0 ? 'disabled' : '' }}
                    class="w-full bg-red-500 hover:bg-red-600 text-white py-2 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
              </svg>
              {{ $isQuoteOnly ? 'Cotizar' : 'Agregar' }}
            </button>
            @if($quoteWa && !$isQuoteOnly)
            <a href="https://wa.me/{{ $quoteWa }}?text={{ urlencode('Hola, me interesa este producto: ' . $p->name) }}"
               target="_blank" rel="noopener"
               class="mt-1.5 w-full py-1.5 rounded-lg text-[11px] font-bold flex items-center justify-center gap-1 text-white transition hover:opacity-90"
               style="background:#25D366;">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
              </svg>
              Consultar por WA
            </a>
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

    <div x-show="noResults" class="text-center py-16">
      <p class="text-4xl mb-3">🔍</p>
      <p class="font-black text-gray-700 text-lg mb-1">Sin resultados</p>
      <p class="text-gray-400 text-sm mb-5">Intenta con otro término</p>
      <button @click="search=''; filterCat=''; priceFilter=''; onSaleFilter=false; priceMin=0; priceMax=0"
              class="bg-red-500 hover:bg-red-600 text-white px-6 py-2.5 rounded-full text-sm font-bold transition">
        Ver todo
      </button>
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

{{-- ═══ FOOTER COMPACTO ═══ --}}
<footer class="bg-gray-900 text-gray-400">
  {{-- Fila 1 --}}
  <div class="max-w-[1400px] mx-auto px-4 py-8 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
    <div>
      @if($project->logo_url)
      <img src="{{ asset('storage/'.$project->logo_url) }}" alt="{{ $project->name }}" class="h-8 object-contain mb-3" loading="lazy">
      @else
      <p class="font-black text-white text-lg mb-3">{{ $project->name }}</p>
      @endif
      <p class="text-sm text-gray-500">{{ $project->description ?? 'Tu tienda de ofertas.' }}</p>
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}"
         target="_blank" rel="noopener"
         class="inline-flex items-center gap-1.5 mt-3 bg-[#25D366] text-white text-xs font-bold px-3 py-2 rounded-lg hover:bg-[#20ba5a] transition">
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
        WhatsApp
      </a>
      @endif
    </div>
    <div>
      <p class="font-bold text-white text-xs uppercase tracking-wider mb-3">Categorías</p>
      <ul class="space-y-1.5">
        @foreach($categories->take(5) as $cat)
        <li>
          <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                  class="text-sm text-gray-500 hover:text-white transition text-left">{{ $cat->name }}</button>
        </li>
        @endforeach
      </ul>
    </div>
    <div>
      <p class="font-bold text-white text-xs uppercase tracking-wider mb-3">Contacto</p>
      @if($project->phone)
      <p class="text-sm flex items-center gap-1.5 mb-2">
        <svg class="w-4 h-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
        </svg>
        {{ $project->phone }}
      </p>
      @endif
      @if($project->address)
      <p class="text-sm flex items-start gap-1.5">
        <svg class="w-4 h-4 opacity-40 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        {{ $project->address }}
      </p>
      @endif
    </div>
    <div>
      <p class="font-bold text-white text-xs uppercase tracking-wider mb-3">Síguenos</p>
      @php
        $footerSocials = [['key'=>'facebook_url','label'=>'Facebook','color'=>'#1877F2'],['key'=>'instagram_url','label'=>'Instagram','color'=>'#E1306C'],['key'=>'tiktok_url','label'=>'TikTok','color'=>'#222'],['key'=>'youtube_url','label'=>'YouTube','color'=>'#FF0000']];
      @endphp
      <div class="flex flex-wrap gap-2">
        @foreach($footerSocials as $soc)
        @if($settings[$soc['key']] ?? null)
        <a href="{{ $settings[$soc['key']] }}" target="_blank" rel="noopener"
           class="px-3 py-1.5 rounded-lg text-white text-xs font-bold hover:opacity-80 transition"
           style="background:{{ $soc['color'] }}">{{ $soc['label'] }}</a>
        @endif
        @endforeach
      </div>
    </div>
  </div>
  {{-- Fila 2 --}}
  <div class="border-t border-gray-800 py-4 text-center text-xs text-gray-600">
    &copy; {{ date('Y') }} <strong class="text-gray-500">{{ $project->name }}</strong> &mdash; Catálogo online por <strong class="text-gray-500">AVAN</strong>
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
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
      <div class="flex items-center gap-2">
        <button x-show="(drawerStep===2 || drawerStep===3) && !orderSent"
                @click="drawerStep > 1 ? drawerStep-- : null"
                class="p-1.5 hover:bg-gray-100 rounded-lg transition mr-1" aria-label="Volver">
          <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <div class="w-5 h-5 bg-red-500 rounded-md flex items-center justify-center">
          <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
        </div>
        <h2 class="font-black text-gray-900 text-base"
            x-text="drawerStep===1 ? '{{ $isQuoteOnly ? 'Mi cotización' : 'Tu pedido' }}' : (drawerStep===2 ? 'Confirmar datos' : 'Pagar')"></h2>
        <span x-show="cart.length && drawerStep===1"
              class="badge-red text-xs px-2 py-0.5 rounded-full font-black" x-text="cart.length + ' items'"></span>
      </div>
      <button @click="drawerOpen=false" class="p-2 hover:bg-gray-100 rounded-xl transition" aria-label="Cerrar">
        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    {{-- PASO 1 --}}
    <div x-show="drawerStep===1" class="flex flex-col flex-1 overflow-hidden">
      <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <template x-if="cart.length===0">
          <div class="text-center py-16 text-gray-400">
            <svg class="w-14 h-14 mx-auto mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="font-bold text-gray-600 mb-1">{{ $isQuoteOnly ? 'Tu cotización está vacía' : 'Tu carrito está vacío' }}</p>
            <p class="text-sm">Agrega productos para comenzar</p>
          </div>
        </template>
        <template x-for="(item, i) in cart" :key="item.id">
          <div class="flex items-center gap-3 bg-gray-50 rounded-xl p-3 border border-gray-100">
            <div class="w-14 h-14 rounded-lg overflow-hidden flex-shrink-0 bg-gray-200">
              <img :src="item.img" x-show="item.img" class="w-full h-full object-cover" :alt="item.name">
              <div x-show="!item.img" class="w-full h-full flex items-center justify-center text-gray-400 text-xs">IMG</div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-gray-800 line-clamp-2" x-text="item.name"></p>
              @if(!$isQuoteOnly || $quotePriceDisp === 'show')
              <p class="text-sm font-black text-red-500 mt-0.5" x-text="'S/ ' + (item.price * item.qty).toFixed(2)"></p>
              @endif
            </div>
            <div class="flex items-center gap-1 flex-shrink-0">
              <button @click="item.qty > 1 ? item.qty-- : cart.splice(i,1)"
                      class="w-8 h-8 rounded-lg border-2 border-gray-200 hover:border-red-300 hover:bg-red-50 text-gray-600 hover:text-red-500 font-bold text-sm transition flex items-center justify-center">
                <span x-text="item.qty > 1 ? '−' : '×'"></span>
              </button>
              <span class="w-7 text-center text-sm font-black text-gray-800" x-text="item.qty"></span>
              <button @click="item.qty++"
                      class="w-8 h-8 rounded-lg bg-red-500 hover:bg-red-600 text-white font-bold text-sm transition flex items-center justify-center">+</button>
            </div>
          </div>
        </template>
      </div>

      <div x-show="cart.length > 0" class="border-t border-gray-100 px-5 py-4 space-y-3 flex-shrink-0">
        <div class="flex justify-between items-center">
          <span class="text-sm text-gray-500"><span x-text="cart.reduce((s,i)=>s+i.qty,0)"></span> productos</span>
          @if(!$isQuoteOnly || $quotePriceDisp === 'show')
          <span class="font-black text-lg text-red-500" x-text="'S/ ' + cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></span>
          @else
          <span class="text-xs text-gray-400 italic">Precios a cotizar</span>
          @endif
        </div>
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
                class="w-full bg-red-500 hover:bg-red-600 text-white py-3.5 rounded-xl font-black text-sm transition flex items-center justify-center gap-2">
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
        <div class="bg-red-50 rounded-xl px-4 py-3 flex justify-between items-center border border-red-100">
          <span class="text-sm text-gray-600"><span x-text="cart.reduce((s,i)=>s+i.qty,0)"></span> productos</span>
          @if(!$isQuoteOnly || $quotePriceDisp === 'show')
          <span class="font-black text-red-500" x-text="'S/ ' + cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></span>
          @else
          <span class="text-xs text-gray-400 italic">a cotizar</span>
          @endif
        </div>
        <input x-model="form.name" type="text" placeholder="Tu nombre completo *"
               class="w-full border-2 border-gray-200 focus:border-red-400 rounded-xl px-4 py-2.5 text-sm outline-none transition" autocomplete="name">
        <input x-model="form.phone" type="tel" placeholder="Tu WhatsApp / teléfono *"
               class="w-full border-2 border-gray-200 focus:border-red-400 rounded-xl px-4 py-2.5 text-sm outline-none transition" autocomplete="tel">
        <input x-model="form.email" type="email" placeholder="Tu correo electrónico (opcional)"
               class="w-full border-2 border-gray-200 focus:border-red-400 rounded-xl px-4 py-2.5 text-sm outline-none transition" autocomplete="email">
        <textarea x-model="form.notes" rows="2" placeholder="Nota adicional (opcional)"
                  class="w-full border-2 border-gray-200 focus:border-red-400 rounded-xl px-4 py-2.5 text-sm outline-none resize-none transition"></textarea>
        @if($requireAddress)
        <input x-model="form.address" type="text" placeholder="Dirección de entrega *"
               class="w-full border-2 border-gray-200 focus:border-red-400 rounded-xl px-4 py-2.5 text-sm outline-none transition"
               autocomplete="street-address">
        @endif
        {{-- Cupón --}}
        <div>
          <div x-show="!couponApplied" class="flex gap-2">
            <input x-model="couponCode" @keydown.enter.prevent="applyCoupon" type="text"
                   placeholder="Código de descuento"
                   class="flex-1 border-2 border-gray-200 focus:border-[var(--accent)] rounded-xl px-4 py-2.5 text-sm outline-none transition uppercase"
                   style="text-transform:uppercase">
            <button @click="applyCoupon" :disabled="couponLoading" type="button"
                    class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-gray-100 hover:bg-gray-200 transition text-gray-700 flex-shrink-0">
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
        <div x-show="shippingEnabled || couponApplied" class="bg-gray-50 rounded-xl px-4 py-3 space-y-1.5 text-sm">
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
        <div class="w-20 h-20 bg-green-100 rounded-3xl flex items-center justify-center mb-5">
          <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <p class="font-black text-gray-900 text-xl mb-2">{{ $isQuoteOnly ? '¡Cotización enviada!' : '¡Pedido confirmado!' }}</p>
        <p class="text-sm text-gray-500 mb-1 leading-relaxed">{{ $isQuoteOnly ? 'Recibimos tu solicitud.' : 'Nos pondremos en contacto pronto.' }}</p>
        @if(!$isQuoteOnly)
        <p x-show="orderId" class="text-xs text-gray-400 mb-4">Pedido N° <span class="font-black text-gray-700" x-text="orderId"></span></p>
        @endif
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
                class="bg-red-500 hover:bg-red-600 text-white px-8 py-3 rounded-xl text-sm font-bold transition mt-3">
          {{ $isQuoteOnly ? 'Seguir explorando' : 'Seguir comprando' }}
        </button>
      </div>

      <div x-show="!orderSent" class="border-t border-gray-100 px-5 py-4 flex-shrink-0">
        @if(!$isQuoteOnly && $hasOnlinePayment)
        <button @click="submitOrder()" :disabled="orderLoading"
                class="w-full bg-red-500 hover:bg-red-600 text-white py-4 rounded-xl font-black text-sm transition disabled:opacity-60 flex items-center justify-center gap-2">
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
                class="w-full py-4 rounded-xl font-black text-sm transition disabled:opacity-60 flex items-center justify-center gap-2
                       {{ $isQuoteOnly ? 'bg-[#25D366] hover:bg-[#20ba5a] text-white' : 'bg-red-500 hover:bg-red-600 text-white' }}">
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
        <div class="bg-red-50 rounded-xl px-4 py-3 flex justify-between items-center border border-red-100">
          <span class="text-sm text-gray-600 font-medium">Total a pagar</span>
          <span class="font-black text-lg text-red-500" x-text="'S/ ' + orderTotal.toFixed(2)"></span>
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
          <div x-data="{ open: false }" class="border-2 border-gray-200 rounded-2xl overflow-hidden">
            <button @click="open = !open"
                    class="w-full flex items-center gap-3 px-4 py-3 text-left transition hover:bg-gray-50">
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
              @if($payManualInstr)<p class="text-xs text-gray-500 italic">{{ $payManualInstr }}</p>@endif
              <div>
                <label class="text-xs font-semibold text-gray-600 block mb-1">Número de operación / referencia *</label>
                <input x-model="payReference" type="text" placeholder="Ej: 123456789"
                       class="w-full border-2 border-gray-200 focus:border-red-400 rounded-xl px-4 py-2.5 text-sm outline-none transition">
              </div>
              <button @click="confirmManualPay()" :disabled="payLoading || !payReference.trim()"
                      class="w-full bg-red-500 hover:bg-red-600 text-white py-3 rounded-xl font-black text-sm transition disabled:opacity-50 flex items-center justify-center gap-2">
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
                  class="w-full flex items-center gap-3 px-4 py-3.5 border-2 border-gray-200 rounded-2xl text-left hover:border-red-300 transition disabled:opacity-50">
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
                  class="w-full flex items-center gap-3 px-4 py-3.5 border-2 border-gray-200 rounded-2xl text-left hover:border-blue-400 transition disabled:opacity-50">
            <span class="text-2xl leading-none">🛒</span>
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
        </div>
        @endif
        <p class="text-center text-xs text-gray-400 pt-2">Tus datos están protegidos y nunca se comparten</p>
      </div>

      <div x-show="orderSent" class="flex-1 flex flex-col items-center justify-center px-5 py-8 text-center">
        <div class="w-20 h-20 bg-green-100 rounded-3xl flex items-center justify-center mb-5">
          <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <p class="font-black text-gray-900 text-xl mb-2">¡Pago registrado!</p>
        <p class="text-sm text-gray-500 mb-6 leading-relaxed">Tu pedido está confirmado.</p>
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
                class="bg-red-500 hover:bg-red-600 text-white px-8 py-3 rounded-xl text-sm font-bold transition">
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
    search: '', filterCat: '', priceFilter: '', onSaleFilter: false, sortBy: 'default',
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
