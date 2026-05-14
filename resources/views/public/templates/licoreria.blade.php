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
$primaryColor     = $settings['primary_color'] ?? '#b8973a';
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
$seoTitle         = ($settings['seo_title'] ?? null) ?: ($project->name . ' — Licorería Online');
$seoDesc          = ($settings['seo_description'] ?? null) ?: ($project->description ?? 'Explora nuestra selección de licores y bebidas al mejor precio.');
$ogImage          = $project->logo_url ? asset('storage/'.$project->logo_url) : asset('img/og-default.png');
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
$heroBg    = $settings['hero_bg_color'] ?? '#0d1117';
$heroTitle = $settings['hero_title'] ?? $project->name;
$heroSub   = $settings['hero_subtitle'] ?? 'La mejor selección de licores y bebidas';
$heroBadge = $settings['hero_badge'] ?? '🥃 Bienvenido';
$currency  = $settings['currency_symbol'] ?? 'S/';
@endphp

<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDesc }}">
<meta name="robots" content="index, follow">
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta property="og:type"        content="website">
<meta property="og:url"         content="{{ $canonicalUrl }}">
<meta property="og:title"       content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDesc }}">
<meta property="og:image"       content="{{ $ogImage }}">
<meta property="og:locale"      content="es_PE">
<meta property="og:site_name"   content="{{ $project->name }}">

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

@php
$secondaryColor = $settings['secondary_color'] ?? '#8c6a1e';
$faviconUrl     = $settings['favicon_url'] ?? '';
$btnCartText    = $settings['btn_cart_text']  ?? 'Agregar al carrito';
$btnQuoteText   = $settings['btn_quote_text'] ?? 'Cotizar';
$footerTagline   = $settings['footer_tagline']  ?? 'Bebidas premium con entrega a domicilio';
$footerCopyright = $settings['footer_copyright'] ?? ('© ' . date('Y') . ' ' . $project->name);
@endphp
@if($faviconUrl)<link rel="icon" href="{{ $faviconUrl }}">@endif

<style>
:root {
  --gold: {{ $primaryColor }};
  --gold2: {{ $secondaryColor }};
  --navy: #ffffff;
  --navy2: #f8f9fa;
  --navy3: #f1f3f5;
  --font-serif: 'Playfair Display', Georgia, serif;
  --font-sans: 'Inter', sans-serif;
}
*, body { font-family: var(--font-sans); }
.serif { font-family: var(--font-serif); }
.btn-gold { background: var(--gold); color: #fff; font-weight: 700; }
.btn-gold:hover { background: var(--gold2); }
.btn-outline-gold { border: 1.5px solid var(--gold); color: var(--gold); }
.btn-outline-gold:hover { background: var(--gold); color: #fff; }
.text-gold { color: var(--gold); }
.border-gold { border-color: var(--gold); }
.bg-gold { background: var(--gold); }
[x-cloak] { display:none!important; }

/* Drawer */
.drawer-overlay { position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:40;backdrop-filter:blur(4px); }
.drawer { position:fixed;top:0;right:0;height:100%;width:420px;max-width:96vw;background:#fff;z-index:50;display:flex;flex-direction:column;box-shadow:-8px 0 40px rgba(0,0,0,.15); }
@media(max-width:640px){ .drawer{ width:100%; } }

/* Cards */
.lic-card { transition: transform .3s ease, box-shadow .3s ease; }
.lic-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,.12); }
.lic-card .lic-overlay { opacity: 0; transition: opacity .3s; }
.lic-card:hover .lic-overlay { opacity: 1; }
.lic-card .lic-img img { transition: transform .5s ease; }
.lic-card:hover .lic-img img { transform: scale(1.06); }

/* Scrollbar oculta */
.no-scroll::-webkit-scrollbar { display: none; }
.no-scroll { scrollbar-width: none; }

/* Gradiente dorado */
.gold-gradient { background: linear-gradient(135deg, var(--gold) 0%, var(--gold2) 100%); }

/* Badge oferta rojo */
.badge-oferta { background: #dc2626; color: #fff; font-size: 10px; font-weight: 800; padding: 2px 7px; letter-spacing: .05em; }

/* Input search */
.search-dark { background: #f1f3f5; border: 1px solid #e2e8f0; color: #1a202c; }
.search-dark::placeholder { color: #94a3b8; }
.search-dark:focus { background: #fff; border-color: var(--gold); outline: none; box-shadow: 0 0 0 3px color-mix(in srgb, var(--gold) 15%, transparent); }
</style>

@php
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
<body class="text-gray-800" style="background:var(--navy);" x-data="store()" x-cloak>

{{-- TOAST --}}
<div x-show="toastShow" x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-2"
     class="fixed top-20 left-1/2 -translate-x-1/2 z-50 text-sm font-bold px-5 py-3 rounded-xl shadow-2xl whitespace-nowrap pointer-events-none gold-gradient text-gray-900"
     x-text="toastMsg">
</div>

{{-- ═══ TOP BAR ═══ --}}
<div style="background:#1a1a1a;" class="text-xs py-2 border-b border-black/10">
  <div class="max-w-[1400px] mx-auto px-4 flex items-center justify-between gap-4">
    <div class="flex items-center gap-5">
      @if($project->phone)
      <span class="flex items-center gap-1.5 text-gray-300">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
        {{ $project->phone }}
      </span>
      @endif
      @if($shippingFreeFrom > 0)
      <span class="text-gold font-semibold hidden sm:inline">🚚 Envío gratis desde {{ $currency }} {{ number_format($shippingFreeFrom,0) }}</span>
      @endif
    </div>
    <div class="flex items-center gap-3 text-gray-400">
      @foreach(['facebook_url'=>'FB','instagram_url'=>'IG','tiktok_url'=>'TK'] as $key=>$lbl)
      @if($settings[$key] ?? null)
      <a href="{{ $settings[$key] }}" target="_blank" rel="noopener" class="hover:text-gold transition text-xs font-semibold">{{ $lbl }}</a>
      @endif
      @endforeach
    </div>
  </div>
</div>

{{-- ═══ HEADER ═══ --}}
<header style="background:#fff;border-bottom:1px solid #e9ecef;" class="sticky top-0 z-30">
  <div class="max-w-[1400px] mx-auto px-4 py-3 flex items-center gap-4">

    {{-- Logo --}}
    <a href="{{ $canonicalUrl }}" class="flex items-center gap-3 flex-shrink-0 min-w-[160px]">
      @if($project->logo_url)
        <img src="{{ asset('storage/'.$project->logo_url) }}" alt="{{ $project->name }}" class="h-12 object-contain">
      @else
        <div class="flex flex-col">
          <span class="serif font-bold text-xl leading-none" style="color:var(--gold)">{{ $project->name }}</span>
          <span class="text-[9px] tracking-[.25em] uppercase text-gray-500 mt-0.5">Licorería</span>
        </div>
      @endif
    </a>

    {{-- Categorías nav --}}
    <nav class="flex-1 hidden lg:flex items-center justify-center gap-1 no-scroll overflow-x-auto">
      <button @click="filterCat=''; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
              :class="filterCat==='' ? 'text-gold border-b border-gold' : 'text-gray-500 hover:text-gray-800 border-b border-transparent'"
              class="px-3 py-1.5 text-sm font-medium whitespace-nowrap transition">
        Todo
      </button>
      @foreach($categories as $cat)
      <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
              :class="filterCat==='{{ $cat->id }}' ? 'text-gold border-b border-gold' : 'text-gray-500 hover:text-gray-800 border-b border-transparent'"
              class="px-3 py-1.5 text-sm font-medium whitespace-nowrap transition">
        {{ $cat->name }}
      </button>
      @endforeach
    </nav>

    {{-- Acciones --}}
    <div class="flex items-center gap-3 flex-shrink-0 ml-auto">
      {{-- Buscador --}}
      <div class="relative hidden md:block" @click.outside="searchOpen=false">
        <input x-model="search" type="search" placeholder="Buscar licores, marcas..."
               @input="searchOpen=search.trim().length>=2; searchIdx=-1; if(search.trim().length>=2) _scrollToCatalog()"
               @keydown.arrow-down.prevent="if(suggestions.length){searchIdx=(searchIdx+1)%suggestions.length}"
               @keydown.arrow-up.prevent="if(suggestions.length){searchIdx=(searchIdx-1+suggestions.length)%suggestions.length}"
               @keydown.enter.prevent="if(searchIdx>=0){selectSuggestion(suggestions[searchIdx])}else{searchOpen=false;_scrollToCatalog()}"
               @keydown.escape="searchOpen=false;searchIdx=-1"
               class="search-dark rounded-full pl-9 pr-4 py-2 text-sm w-48 focus:w-64 transition-all">
        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
        {{-- Dropdown búsqueda --}}
        <div x-show="searchOpen && suggestions.length>0" x-cloak
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             style="background:#fff;border:1px solid #e9ecef;"
             class="absolute left-0 top-full mt-1 rounded-xl shadow-xl z-[200] overflow-hidden min-w-[300px]">
          <template x-for="(p,i) in suggestions" :key="p.id">
            <button @click="selectSuggestion(p)" :class="searchIdx===i?'bg-gray-50':''"
                    class="flex items-center gap-3 w-full px-4 py-2.5 hover:bg-gray-50 transition text-left border-b border-gray-100 last:border-0">
              <div class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0 bg-gray-100">
                <img x-show="p.img" :src="p.img" class="w-full h-full object-cover">
                <div x-show="!p.img" class="w-full h-full flex items-center justify-center text-gray-400 text-xs">🍾</div>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 truncate" x-html="_highlight(p.name)"></p>
                <p class="text-xs text-gray-500" x-text="p.cat"></p>
              </div>
              <p class="text-sm font-bold text-gold flex-shrink-0" x-text="'{{ $currency }} '+p.price.toFixed(2)"></p>
            </button>
          </template>
          <div class="px-4 py-2 text-center border-t border-gray-100">
            <button @click="searchOpen=false;_scrollToCatalog()" class="text-xs font-semibold text-gold">Ver todos los resultados →</button>
          </div>
        </div>
      </div>

      {{-- WhatsApp --}}
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}" target="_blank" rel="noopener"
         class="hidden sm:flex items-center gap-2 btn-gold text-xs font-bold px-3 py-2 rounded-full transition">
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
        Pedidos
      </a>
      @endif

      {{-- Carrito --}}
      <button @click="drawerOpen=true" class="relative p-2 rounded-full hover:bg-white/10 transition" aria-label="Ver carrito">
        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <span x-show="cartCount>0" x-text="cartCount"
              class="absolute -top-0.5 -right-0.5 text-[10px] font-black rounded-full w-5 h-5 flex items-center justify-center text-gray-900 bg-gold"></span>
      </button>
    </div>
  </div>

  {{-- Nav móvil categorías --}}
  <div class="lg:hidden flex gap-1 px-4 pb-2 no-scroll overflow-x-auto border-t border-gray-100">
    <button @click="filterCat=''; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
            :class="filterCat===''?'bg-gold text-white':'bg-gray-100 text-gray-500'"
            class="flex-shrink-0 text-xs font-semibold px-3 py-1.5 rounded-full transition mt-2">Todo</button>
    @foreach($categories as $cat)
    <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
            :class="filterCat==='{{ $cat->id }}'?'bg-gold text-white':'bg-gray-100 text-gray-500'"
            class="flex-shrink-0 text-xs font-semibold px-3 py-1.5 rounded-full transition mt-2">{{ $cat->name }}</button>
    @endforeach
  </div>
</header>

{{-- ═══ HERO ═══ --}}
<section class="relative overflow-hidden flex items-end" style="min-height:480px;background:{{ $heroBg && $heroBg !== '#0d1117' ? $heroBg : '#fafafa' }};">
  {{-- Fondo decorativo sutil --}}
  <div class="absolute inset-0 opacity-[0.04]" style="background-image:repeating-linear-gradient(45deg,#b8973a 0,#b8973a 1px,transparent 0,transparent 50%);background-size:40px 40px;"></div>
  {{-- Círculo decorativo dorado --}}
  <div class="absolute right-0 top-0 w-[600px] h-[600px] rounded-full opacity-[0.08]" style="background:radial-gradient(circle,var(--gold) 0%,transparent 70%);transform:translate(30%,-30%);"></div>
  {{-- Franja dorada izquierda --}}
  <div class="absolute left-0 top-0 bottom-0 w-1.5" style="background:linear-gradient(to bottom, var(--gold), var(--gold2));"></div>
  {{-- Texto hero --}}
  <div class="relative z-10 max-w-[1400px] mx-auto px-6 pb-16 pt-20 w-full">
    <div class="max-w-2xl">
      @if($heroBadge)
      <div class="inline-flex items-center gap-2 mb-5 border px-4 py-1.5 rounded-full" style="border-color:var(--gold);background:color-mix(in srgb,var(--gold) 10%,transparent)">
        <span class="text-[11px] font-bold uppercase tracking-[.2em] text-gold">{{ $heroBadge }}</span>
      </div>
      @endif
      <h1 class="serif font-black text-5xl md:text-7xl leading-none text-gray-900 mb-4">
        {{ $heroTitle }}
      </h1>
      <p class="text-gray-500 text-lg mb-8 leading-relaxed max-w-lg">{{ $heroSub }}</p>
      <div class="flex flex-wrap gap-3">
        <button @click="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                class="btn-gold px-7 py-3 rounded-full text-sm font-bold uppercase tracking-wide transition shadow-md">
          Ver catálogo completo
        </button>
        @if($onSale->count())
        <button @click="onSaleFilter=true; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                class="btn-outline-gold px-7 py-3 rounded-full text-sm font-bold uppercase tracking-wide transition">
          Ofertas del día
        </button>
        @endif
      </div>
    </div>
  </div>
  {{-- Divisor ondulado --}}
  <div class="absolute bottom-0 left-0 right-0" style="height:40px;background:#ffffff;clip-path:ellipse(55% 100% at 50% 100%);"></div>
</section>

{{-- ═══ CATEGORÍAS CON ICONOS ═══ --}}
@if($categories->count() > 1)
<section class="py-10 border-b border-gray-100">
  <div class="max-w-[1400px] mx-auto px-6">
    <div class="flex gap-4 no-scroll overflow-x-auto pb-1">
      @php
        $catIcons = ['🥃','🍾','🍷','🍺','🍸','🥂','🍻','🫗','🧉','🍹'];
      @endphp
      <button @click="filterCat=''"
              :class="filterCat===''?'border-gold bg-gold/10':'border-gray-200 hover:border-gold/40'"
              class="flex-shrink-0 flex flex-col items-center gap-2 px-5 py-4 rounded-2xl border transition min-w-[90px]">
        <span class="text-2xl">🍾</span>
        <span :class="filterCat===''?'text-gold':'text-gray-500'" class="text-xs font-semibold">Todo</span>
      </button>
      @foreach($categories as $i => $cat)
      <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
              :class="filterCat==='{{ $cat->id }}'?'border-gold bg-gold/10':'border-gray-200 hover:border-gold/40'"
              class="flex-shrink-0 flex flex-col items-center gap-2 px-5 py-4 rounded-2xl border transition min-w-[90px]">
        <span class="text-2xl">{{ $catIcons[$i % count($catIcons)] }}</span>
        <span :class="filterCat==='{{ $cat->id }}'?'text-gold':'text-gray-500'" class="text-xs font-semibold text-center leading-tight">{{ $cat->name }}</span>
      </button>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ═══ OFERTAS DESTACADAS ═══ --}}
@if($onSale->count())
<section class="py-12 max-w-[1400px] mx-auto px-6">
  <div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
      <span class="badge-oferta rounded-sm font-black text-xs px-2 py-1">OFERTA</span>
      <h2 class="serif font-bold text-2xl text-gray-900">Precios especiales de hoy</h2>
    </div>
    <button @click="onSaleFilter=true; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
            class="text-gold text-sm font-semibold hover:text-gold/80 transition hidden sm:block">Ver todos →</button>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
    @foreach($onSale->take(5) as $p)
    @php $pct = $p->compare_price > 0 ? round((($p->compare_price - $p->price) / $p->compare_price) * 100) : 0; @endphp
    <article class="lic-card group cursor-pointer rounded-2xl overflow-hidden" style="background:#fff;border:1px solid #e9ecef;"
             id="oferta-{{ $p->id }}">
      <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="lic-img block relative overflow-hidden" style="aspect-ratio:1/1;">
        @if($p->mainImage)
        <img src="{{ $p->main_image_url }}" alt="{{ $p->name }}" loading="lazy" class="w-full h-full object-contain p-4 bg-gray-50">
        @else
        <div class="w-full h-full flex items-center justify-center bg-gray-50">
          <span class="text-5xl opacity-30">🍾</span>
        </div>
        @endif
        {{-- Badge % --}}
        @if($pct > 0)
        <div class="absolute top-2 left-2 badge-oferta rounded-sm">-{{ $pct }}%</div>
        @endif
        {{-- Overlay agregar --}}
        <div class="lic-overlay absolute bottom-0 left-0 right-0">
          <button @click.prevent="addToCart({id:{{ $p->id }},name:'{{ addslashes($p->name) }}',price:{{ $p->price }},img:'{{ $p->main_image_url ?? '' }}'})"
                  class="w-full btn-gold py-2.5 text-xs font-black uppercase tracking-wide transition">
            {{ $isQuoteOnly ? 'Cotizar' : 'Agregar' }}
          </button>
        </div>
      </a>
      <div class="p-3">
        <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="text-gray-800 text-sm font-semibold line-clamp-2 leading-snug hover:text-gold transition block mb-2">{{ $p->name }}</a>
        @if(!$isQuoteOnly || $quotePriceDisp==='show')
        <div class="flex items-baseline gap-2">
          <span class="text-gold font-black text-lg">{{ $currency }} {{ number_format($p->price,2) }}</span>
          <span class="text-gray-400 text-xs line-through">{{ $currency }} {{ number_format($p->compare_price,2) }}</span>
        </div>
        @endif
      </div>
    </article>
    @endforeach
  </div>
</section>
@endif

{{-- ═══ BUSCADOR MÓVIL ═══ --}}
<div class="md:hidden px-4 py-2 sticky top-[105px] z-10 border-b border-gray-100 bg-white">
  <div class="relative" @click.outside="searchOpen=false">
    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    <input x-model="search" type="search" placeholder="Buscar licores, marcas..."
           @input="searchOpen=search.trim().length>=2; searchIdx=-1"
           @keydown.escape="searchOpen=false"
           class="search-dark w-full pl-9 pr-4 py-2.5 text-sm rounded-xl">
  </div>
</div>

{{-- ═══ CATÁLOGO COMPLETO ═══ --}}
<section id="catalogo" class="max-w-[1400px] mx-auto px-6 pb-24 pt-8">

  {{-- Header sección --}}
  <div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
      <div class="w-1 h-7 rounded-full bg-gold"></div>
      <h2 class="serif font-bold text-2xl text-gray-900">
        <span x-show="filterCat===''">Catálogo completo</span>
        @foreach($categories as $cat)
        <span x-show="filterCat==='{{ $cat->id }}'">{{ $cat->name }}</span>
        @endforeach
      </h2>
      <span x-show="filterCat!==''||search!==''||priceFilter!==''||onSaleFilter"
            class="text-gold text-sm font-semibold"
            x-text="'('+visibleCount+' productos)'"></span>
    </div>
    <div class="flex items-center gap-2">
      <button @click="filterOpen=true"
              class="flex items-center gap-1.5 text-xs font-semibold border border-gray-200 rounded-lg px-3 py-1.5 hover:border-gold/60 transition relative text-gray-600">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
        Filtros
        <span x-show="priceFilter!==''||onSaleFilter"
              class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full text-[10px] font-black flex items-center justify-center text-gray-900 bg-gold"
              x-text="(priceFilter!==''?1:0)+(onSaleFilter?1:0)"></span>
      </button>
      <select x-model="sortBy" class="text-xs border border-gray-200 px-2 py-1.5 rounded-lg outline-none text-gray-600 cursor-pointer hover:border-gold/60 transition bg-white">
        <option value="default">Ordenar</option>
        <option value="price_asc">Precio ↑</option>
        <option value="price_desc">Precio ↓</option>
        <option value="newest">Más nuevos</option>
        <option value="name_az">Nombre A→Z</option>
      </select>
      <button x-show="filterCat!==''||search!==''||priceFilter!==''||onSaleFilter"
              @click="filterCat='';search='';priceFilter='';onSaleFilter=false;priceMin=0;priceMax=0"
              class="text-xs text-gray-500 hover:text-red-400 transition underline underline-offset-2">
        Limpiar
      </button>
    </div>
  </div>

  {{-- Grid productos --}}
  <div class="flex gap-8">

    {{-- Sidebar filtros desktop --}}
    <aside class="w-[180px] flex-shrink-0 hidden xl:block space-y-6">
      <div>
        <p class="text-[11px] font-bold uppercase tracking-[.2em] text-gold mb-3">Categorías</p>
        <div class="space-y-0.5">
          <button @click="filterCat=''"
                  :class="filterCat===''?'text-gold font-bold':'text-gray-500 hover:text-gray-800'"
                  class="block w-full text-left text-sm py-1.5 transition">
            Todo el catálogo
          </button>
          @foreach($categories as $cat)
          <button @click="filterCat='{{ $cat->id }}'"
                  :class="filterCat==='{{ $cat->id }}'?'text-gold font-bold':'text-gray-500 hover:text-gray-800'"
                  class="block w-full text-left text-sm py-1.5 transition flex items-center justify-between">
            <span>{{ $cat->name }}</span>
            <span class="text-xs text-gray-300">{{ $cat->products->count() }}</span>
          </button>
          @endforeach
        </div>
      </div>

      <div>
        <p class="text-[11px] font-bold uppercase tracking-[.2em] text-gold mb-3">Precio</p>
        <div class="space-y-2">
          @foreach(['' => 'Todos', '0-50' => 'Hasta '.$currency.' 50', '50-150' => $currency.' 50–150', '150-500' => $currency.' 150–500', '500+' => 'Más de '.$currency.' 500'] as $val => $label)
          <label class="flex items-center gap-2 text-sm text-gray-500 cursor-pointer hover:text-gray-800 transition">
            <input type="radio" x-model="priceFilter" value="{{ $val }}" class="w-3.5 h-3.5" style="accent-color:var(--gold)">
            {{ $label }}
          </label>
          @endforeach
        </div>
      </div>

      <div>
        <label class="flex items-center gap-2 text-sm text-gray-500 cursor-pointer hover:text-gray-800 transition">
          <input type="checkbox" x-model="onSaleFilter" class="w-3.5 h-3.5 rounded" style="accent-color:var(--gold)">
          Solo en oferta
        </label>
      </div>
    </aside>

    {{-- Productos --}}
    <div class="flex-1">
      @foreach($categories as $cat)
      @php $catAllProducts = $cat->products->merge($cat->children->flatMap->products); @endphp
      @if($catAllProducts->count())
      <div x-show="filterCat==='' || filterCat==='{{ $cat->id }}'" class="mb-12">
        <div class="flex items-center gap-3 mb-5">
          <h3 class="serif font-bold text-gray-900 text-lg">{{ $cat->name }}</h3>
          <div class="flex-1 border-t border-gray-100"></div>
          <span class="text-xs text-gray-400">{{ $catAllProducts->count() }} productos</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-4 gap-4" data-products-grid>
          @foreach($catAllProducts as $p)
          @php
            $qvData = ['id'=>$p->id,'name'=>$p->name,'img'=>$p->mainImage?$p->main_image_url:'','price'=>(float)$p->price,'cp'=>$p->compare_price?(float)$p->compare_price:null,'desc'=>\Str::limit(strip_tags($p->description??''),120),'url'=>route('public.product',[$project->slug,$p->id]),'stock'=>$p->stock];
            $pct2 = ($p->compare_price && $p->compare_price > $p->price) ? round((($p->compare_price - $p->price)/$p->compare_price)*100) : 0;
          @endphp
          <article class="lic-card group cursor-pointer rounded-2xl overflow-hidden"
                   style="background:#fff;border:1px solid #e9ecef;"
                   id="producto-{{ $p->id }}"
                   x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }}) && ({{ $loop->index }} < 8 || expandedCats['{{ $cat->id }}'])"
                   data-price="{{ $p->price }}"
                   data-ts="{{ $p->created_at ? $p->created_at->timestamp : 0 }}"
                   data-idx="{{ $loop->index }}"
                   data-qv='@json($qvData)'>

            <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="lic-img block relative overflow-hidden" style="aspect-ratio:1/1;">
              @if($p->mainImage)
              <img src="{{ $p->main_image_url }}" alt="{{ $p->name }}" loading="lazy" class="w-full h-full object-contain p-4 bg-gray-50">
              @else
              <div class="w-full h-full flex items-center justify-center bg-gray-50">
                <span class="text-5xl opacity-30">🍾</span>
              </div>
              @endif

              {{-- Badge oferta --}}
              @if($pct2 > 0)
              <div class="absolute top-2 left-2 badge-oferta rounded-sm">-{{ $pct2 }}%</div>
              @elseif($p->created_at && $p->created_at->diffInDays() <= 30)
              <div class="absolute top-2 left-2 text-[10px] font-black px-2 py-0.5 rounded-sm bg-gold text-gray-900">NUEVO</div>
              @endif

              @if($p->stock !== null && $p->stock === 0)
              <div class="absolute inset-0 bg-black/60 flex items-center justify-center">
                <span class="text-xs font-black text-white/60 uppercase tracking-widest">Agotado</span>
              </div>
              @endif

              {{-- Overlay agregar --}}
              <div class="lic-overlay absolute bottom-0 left-0 right-0" x-show="{{ $p->stock === null || $p->stock > 0 ? 'true' : 'false' }}">
                <button @click.prevent="addToCart({id:{{ $p->id }},name:'{{ addslashes($p->name) }}',price:{{ $p->price }},img:'{{ $p->main_image_url ?? '' }}'})"
                        class="w-full btn-gold py-2.5 text-xs font-black uppercase tracking-wide transition">
                  {{ $isQuoteOnly ? $btnQuoteText : $btnCartText }}
                </button>
              </div>
            </a>

            <div class="p-3">
              @if($p->category)
              <p class="text-[10px] text-gold/60 uppercase tracking-widest mb-1 font-semibold">{{ $p->category->name }}</p>
              @endif
              <a href="{{ route('public.product', [$project->slug, $p->id]) }}"
                 class="text-gray-800 text-sm font-semibold line-clamp-2 leading-snug hover:text-gold transition block mb-2">{{ $p->name }}</a>
              @if(isset($productRatings[$p->id]))
              <div class="flex items-center gap-1 mb-1.5">
                <span class="text-amber-400 text-[11px]">{{ str_repeat('★', floor($productRatings[$p->id]->avg_rating)) }}{{ str_repeat('☆', 5-floor($productRatings[$p->id]->avg_rating)) }}</span>
                <span class="text-[10px] text-gray-600">({{ $productRatings[$p->id]->rating_count }})</span>
              </div>
              @endif
              @if(!$isQuoteOnly || $quotePriceDisp==='show')
              <div class="flex items-baseline gap-2">
                <span class="text-gold font-black">{{ $currency }} {{ number_format($p->price,2) }}</span>
                @if($p->compare_price && $p->compare_price > $p->price)
                <span class="text-gray-400 text-xs line-through">{{ $currency }} {{ number_format($p->compare_price,2) }}</span>
                @endif
              </div>
              @else
              <p class="text-xs text-gray-400 italic">Precio a consultar</p>
              @endif
            </div>
          </article>
          @endforeach

          @if($catAllProducts->count() > 8)
          <div class="col-span-full mt-2 text-center" x-show="!expandedCats['{{ $cat->id }}']">
            <button @click="expandedCats={...expandedCats,'{{ $cat->id }}':true}"
                    class="btn-outline-gold text-sm font-bold px-6 py-2.5 rounded-full transition">
              Ver todos ({{ $catAllProducts->count() }})
            </button>
          </div>
          @endif
        </div>
      </div>
      @endif
      @endforeach

      <div x-show="noResults" class="text-center py-20">
        <p class="text-5xl mb-4">🔍</p>
        <p class="serif font-bold text-gray-900 text-xl mb-2">Sin resultados</p>
        <p class="text-gray-500 text-sm mb-5">Intenta con otro término o categoría</p>
        <button @click="search='';filterCat='';priceFilter='';onSaleFilter=false"
                class="btn-gold px-6 py-2.5 rounded-full text-sm font-bold transition">
          Ver todo el catálogo
        </button>
      </div>
    </div>
  </div>
</section>

{{-- ═══ FOOTER ═══ --}}
<footer style="background:#1a1a1a;border-top:1px solid rgba(184,151,58,.15);">
  <div class="max-w-[1400px] mx-auto px-6 py-14 grid grid-cols-1 md:grid-cols-3 gap-10">
    <div class="md:col-span-1">
      @if($project->logo_url)
      <img src="{{ asset('storage/'.$project->logo_url) }}" alt="{{ $project->name }}" class="h-12 object-contain mb-4">
      @else
      <p class="serif font-black text-2xl text-gold mb-2">{{ $project->name }}</p>
      @endif
      @if($footerTagline)
      <p class="text-sm text-gray-400 leading-relaxed mb-4">{{ $footerTagline }}</p>
      @endif
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}" target="_blank" rel="noopener"
         class="inline-flex items-center gap-2 btn-gold text-xs font-bold px-4 py-2.5 rounded-full hover:opacity-90 transition">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
        Hacer pedido por WhatsApp
      </a>
      @endif
    </div>

    <div>
      <p class="text-[11px] font-bold uppercase tracking-[.2em] text-gold mb-4">Categorías</p>
      <ul class="space-y-2">
        @foreach($categories->take(7) as $cat)
        <li>
          <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                  class="text-sm text-gray-600 hover:text-gold transition">{{ $cat->name }}</button>
        </li>
        @endforeach
      </ul>
    </div>

    <div>
      <p class="text-[11px] font-bold uppercase tracking-[.2em] text-gold mb-4">Contacto</p>
      <div class="space-y-2 text-sm text-gray-600">
        @if($project->phone)
        <p>📞 {{ $project->phone }}</p>
        @endif
        @if($project->address)
        <p>📍 {{ $project->address }}</p>
        @endif
        @if($project->whatsapp)
        <p>💬 WhatsApp: {{ $project->whatsapp }}</p>
        @endif
      </div>
      @php
        $socials = [['facebook_url','#1877F2','FB'],['instagram_url','#E1306C','IG'],['tiktok_url','#222','TK'],['youtube_url','#FF0000','YT']];
      @endphp
      <div class="flex gap-2 mt-4">
        @foreach($socials as [$key,$color,$lbl])
        @if($settings[$key] ?? null)
        <a href="{{ $settings[$key] }}" target="_blank" rel="noopener"
           class="w-8 h-8 flex items-center justify-center text-white text-xs font-black rounded-lg hover:opacity-80 transition"
           style="background:{{ $color }}">{{ $lbl }}</a>
        @endif
        @endforeach
      </div>
      @if(count($acceptedPayments) > 0)
      <div class="mt-5">
        <p class="text-[11px] font-bold uppercase tracking-[.2em] text-gold mb-2">Métodos de pago</p>
        <div class="flex flex-wrap gap-1.5">
          @foreach($acceptedPayments as $pk)
          @if(isset($paymentMeta[$pk]))
          <span class="text-xs px-2 py-1 rounded-lg text-gray-400 border border-white/10">{{ $paymentMeta[$pk]['emoji'] }} {{ $paymentMeta[$pk]['label'] }}</span>
          @endif
          @endforeach
        </div>
      </div>
      @endif
    </div>
  </div>
  <div style="border-top:1px solid rgba(255,255,255,.08);" class="py-4 text-center text-xs text-gray-500">
    {{ $footerCopyright }} &mdash; Catálogo online por <span class="text-gold font-semibold">AVAN</span>
  </div>
</footer>

{{-- ═══ FILTROS MÓVIL BOTTOM SHEET ═══ --}}
<div x-show="filterOpen" x-cloak class="xl:hidden fixed inset-0 z-50 flex flex-col justify-end">
  <div @click="filterOpen=false" class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
  <div class="relative rounded-t-3xl shadow-2xl max-h-[80vh] flex flex-col"
       style="background:#fff;border-top:3px solid var(--gold);"
       x-transition:enter="transition ease-out duration-300 transform"
       x-transition:enter-start="translate-y-full"
       x-transition:enter-end="translate-y-0"
       x-transition:leave="transition ease-in duration-200 transform"
       x-transition:leave-start="translate-y-0"
       x-transition:leave-end="translate-y-full">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
      <h3 class="font-black text-gray-900">Filtros</h3>
      <button @click="filterOpen=false" class="p-1.5 rounded-lg hover:bg-gray-100 transition">
        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="overflow-y-auto flex-1 px-5 py-4 space-y-6">
      <div>
        <p class="font-bold text-gold text-sm mb-3">Precio</p>
        <div class="grid grid-cols-2 gap-2">
          @foreach(['' => 'Todos', '0-50' => 'Hasta '.$currency.' 50', '50-150' => $currency.' 50–150', '150-500' => $currency.' 150–500', '500+' => 'Más de '.$currency.' 500'] as $val => $label)
          <button @click="priceFilter='{{ $val }}'"
                  :class="priceFilter==='{{ $val }}' ? 'border-gold text-gold font-bold bg-gold/5' : 'border-gray-200 text-gray-600'"
                  class="border rounded-xl px-3 py-2 text-sm text-left transition">{{ $label }}</button>
          @endforeach
        </div>
      </div>
      <div>
        <label class="flex items-center gap-3 p-3 rounded-xl cursor-pointer border transition"
               :class="onSaleFilter?'border-gold bg-gold/5':'border-gray-200'">
          <input type="checkbox" x-model="onSaleFilter" class="w-4 h-4 rounded" style="accent-color:var(--gold)">
          <span class="text-sm font-medium text-gray-700">Solo productos en oferta</span>
        </label>
      </div>
      <div>
        <p class="font-bold text-gold text-sm mb-3">Ordenar por</p>
        <div class="space-y-2">
          @foreach([['default','Relevancia'],['price_asc','Precio: menor a mayor'],['price_desc','Precio: mayor a menor'],['newest','Más nuevos primero'],['name_az','Nombre A→Z']] as [$val,$lbl])
          <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer transition"
                 :class="sortBy==='{{ $val }}'?'border-gold bg-gold/5':'border-gray-200'">
            <input type="radio" x-model="sortBy" value="{{ $val }}" class="w-4 h-4" style="accent-color:var(--gold)">
            <span class="text-sm font-medium text-gray-700">{{ $lbl }}</span>
          </label>
          @endforeach
        </div>
      </div>
    </div>
    <div class="flex gap-3 px-5 py-4 border-t border-gray-100 flex-shrink-0">
      <button @click="priceFilter='';priceMin=0;priceMax=0;onSaleFilter=false;sortBy='default'"
              class="flex-1 py-3 rounded-xl text-sm font-bold border border-gray-200 text-gray-500 hover:bg-gray-50 transition">
        Limpiar
      </button>
      <button @click="filterOpen=false"
              class="flex-1 py-3 rounded-xl text-sm font-black btn-gold transition">
        Ver resultados
      </button>
    </div>
  </div>
</div>

{{-- ═══ CART DRAWER ═══ --}}
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

    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
      <div class="flex items-center gap-2">
        <button x-show="(drawerStep===2||drawerStep===3)&&!orderSent" @click="drawerStep>1?drawerStep--:null"
                class="p-1.5 hover:bg-gray-100 rounded-lg transition mr-1">
          <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <svg class="w-5 h-5 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <h2 class="font-black text-gray-900 text-base"
            x-text="drawerStep===1?'{{ $isQuoteOnly?'Mi cotización':'Tu pedido' }}':(drawerStep===2?'Confirmar datos':'Pagar')"></h2>
        <span x-show="cart.length&&drawerStep===1" class="bg-gray-900 text-white text-xs px-2 py-0.5 font-black rounded-full" x-text="cart.length+' items'"></span>
      </div>
      <button @click="drawerOpen=false" class="p-2 hover:bg-gray-100 rounded-lg transition">
        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    {{-- PASO 1 --}}
    <div x-show="drawerStep===1" class="flex flex-col flex-1 overflow-hidden">
      <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <template x-if="cart.length===0">
          <div class="text-center py-16 text-gray-400">
            <span class="text-5xl block mb-4">🍾</span>
            <p class="font-bold text-gray-600 mb-1">{{ $isQuoteOnly?'Tu cotización está vacía':'Tu carrito está vacío' }}</p>
            <p class="text-sm">Agrega productos para comenzar</p>
          </div>
        </template>
        <template x-for="(item,i) in cart" :key="item.id">
          <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-xl">
            <div class="w-14 h-14 rounded-lg overflow-hidden flex-shrink-0 bg-gray-100">
              <img :src="item.img" x-show="item.img" class="w-full h-full object-cover">
              <div x-show="!item.img" class="w-full h-full flex items-center justify-center text-xl">🍾</div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-gray-800 line-clamp-2" x-text="item.name"></p>
              @if(!$isQuoteOnly || $quotePriceDisp==='show')
              <p class="text-sm font-black text-gray-900 mt-0.5" x-text="'{{ $currency }} '+(item.price*item.qty).toFixed(2)"></p>
              @endif
            </div>
            <div class="flex items-center gap-1 flex-shrink-0">
              <button @click="item.qty>1?item.qty--:cart.splice(i,1)"
                      class="w-8 h-8 border-2 border-gray-200 hover:border-red-300 hover:bg-red-50 text-gray-600 hover:text-red-500 font-bold text-sm transition flex items-center justify-center rounded-lg">
                <span x-text="item.qty>1?'−':'×'"></span>
              </button>
              <span class="w-7 text-center text-sm font-black text-gray-800" x-text="item.qty"></span>
              <button @click="item.qty++"
                      class="w-8 h-8 text-white font-bold text-sm transition flex items-center justify-center rounded-lg"
                      style="background:var(--gold);color:#111">+</button>
            </div>
          </div>
        </template>
      </div>

      <div x-show="cart.length>0" class="border-t border-gray-100 px-5 py-4 space-y-3 flex-shrink-0">
        <div class="flex justify-between items-center">
          <span class="text-sm text-gray-500"><span x-text="cart.reduce((s,i)=>s+i.qty,0)"></span> productos</span>
          @if(!$isQuoteOnly || $quotePriceDisp==='show')
          <span class="font-black text-lg text-gray-900" x-text="'{{ $currency }} '+cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></span>
          @endif
        </div>
        <button @click="drawerStep=2;orderError=''"
                class="w-full btn-gold py-3.5 font-black text-sm uppercase tracking-widest transition rounded-xl flex items-center justify-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
          {{ $isQuoteOnly?'Continuar y cotizar':'Continuar y pedir' }}
        </button>
      </div>
    </div>

    {{-- PASO 2 --}}
    <div x-show="drawerStep===2" class="flex flex-col flex-1 overflow-hidden">
      <div x-show="!orderSent" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <div class="bg-gray-50 px-4 py-3 rounded-xl flex justify-between items-center">
          <span class="text-sm text-gray-600"><span x-text="cart.reduce((s,i)=>s+i.qty,0)"></span> productos</span>
          @if(!$isQuoteOnly || $quotePriceDisp==='show')
          <span class="font-black text-gray-900" x-text="'{{ $currency }} '+cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></span>
          @endif
        </div>
        <input x-model="form.name" type="text" placeholder="Tu nombre completo *"
               class="w-full border border-gray-200 focus:border-gray-400 rounded-xl px-4 py-2.5 text-sm outline-none transition" autocomplete="name">
        <input x-model="form.phone" type="tel" placeholder="Tu WhatsApp / teléfono *"
               class="w-full border border-gray-200 focus:border-gray-400 rounded-xl px-4 py-2.5 text-sm outline-none transition" autocomplete="tel">
        <input x-model="form.email" type="email" placeholder="Correo electrónico (opcional)"
               class="w-full border border-gray-200 focus:border-gray-400 rounded-xl px-4 py-2.5 text-sm outline-none transition" autocomplete="email">
        <textarea x-model="form.notes" rows="2" placeholder="Nota adicional (opcional)"
                  class="w-full border border-gray-200 focus:border-gray-400 rounded-xl px-4 py-2.5 text-sm outline-none resize-none transition"></textarea>
        @if($requireAddress)
        <input x-model="form.address" type="text" placeholder="Dirección de entrega *"
               class="w-full border border-gray-200 focus:border-gray-400 rounded-xl px-4 py-2.5 text-sm outline-none transition">
        @endif
        {{-- Cupón --}}
        <div>
          <div x-show="!couponApplied" class="flex gap-2">
            <input x-model="couponCode" @keydown.enter.prevent="applyCoupon" type="text" placeholder="Código de descuento"
                   class="flex-1 border border-gray-200 focus:border-gray-400 rounded-xl px-4 py-2.5 text-sm outline-none transition uppercase">
            <button @click="applyCoupon" :disabled="couponLoading" type="button"
                    class="px-4 text-sm font-semibold text-gray-700 hover:text-gray-900 transition flex-shrink-0" x-text="couponLoading?'…':'Aplicar'"></button>
          </div>
          <div x-show="couponApplied" class="flex items-center justify-between bg-green-50 border border-green-200 px-4 py-2.5 text-sm rounded-xl">
            <span class="font-mono font-bold text-green-700" x-text="couponApplied?couponApplied.code:''"></span>
            <button @click="removeCoupon" class="text-gray-400 hover:text-red-500 ml-3 text-lg">&times;</button>
          </div>
          <p x-show="couponError" class="text-red-500 text-xs mt-1" x-text="couponError"></p>
        </div>
        <div x-show="shippingEnabled||couponApplied" class="bg-gray-50 px-4 py-3 rounded-xl space-y-1.5 text-sm">
          <div class="flex justify-between text-gray-500"><span>Subtotal</span><span x-text="'{{ $currency }} '+subtotal.toFixed(2)"></span></div>
          <div x-show="couponApplied&&couponDiscount>0" class="flex justify-between text-green-600 font-medium">
            <span>Descuento</span><span x-text="'- {{ $currency }} '+couponDiscount.toFixed(2)"></span>
          </div>
          <div x-show="shippingEnabled" class="flex justify-between" :class="effectiveShipping===0&&shippingFreeFrom>0?'text-green-600 font-medium':'text-gray-500'">
            <span x-text="effectiveShipping===0&&shippingFreeFrom>0?'🎉 Envío gratis':'Envío'"></span>
            <span x-text="effectiveShipping>0?'{{ $currency }} '+effectiveShipping.toFixed(2):'Gratis'"></span>
          </div>
          <div class="flex justify-between font-black text-gray-900 border-t border-gray-200 pt-1.5"><span>Total</span><span x-text="'{{ $currency }} '+orderGrandTotal.toFixed(2)"></span></div>
        </div>
        <p x-show="orderError" class="text-red-500 text-xs text-center font-medium" x-text="orderError"></p>
      </div>

      <div x-show="orderSent" class="flex-1 flex flex-col items-center justify-center px-5 py-8 text-center">
        <div class="w-20 h-20 bg-green-100 flex items-center justify-center mb-5 rounded-full">
          <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>
        <p class="font-black text-gray-900 text-xl mb-2">{{ $isQuoteOnly?'¡Cotización enviada!':'¡Pedido confirmado!' }}</p>
        <p x-show="orderId" class="text-xs text-gray-400 mb-2">Pedido N° <span class="font-black text-gray-700" x-text="orderId"></span></p>
        <p class="text-sm text-gray-500 mb-6 leading-relaxed">{{ $isQuoteOnly?'Recibimos tu solicitud y te contactaremos pronto.':'Recibimos tu pedido y nos pondremos en contacto.' }}</p>
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
                class="btn-gold px-8 py-3 rounded-full text-sm font-bold uppercase tracking-widest transition">
          {{ $isQuoteOnly?'Seguir explorando':'Seguir comprando' }}
        </button>
      </div>

      <div x-show="!orderSent" class="border-t border-gray-100 px-5 py-4 flex-shrink-0">
        @if(!$isQuoteOnly && $hasOnlinePayment)
        <button @click="submitOrder()" :disabled="orderLoading"
                class="w-full btn-gold py-4 font-black text-sm uppercase tracking-widest transition disabled:opacity-60 rounded-xl flex items-center justify-center gap-2">
          <template x-if="!orderLoading"><span class="flex items-center gap-2">💳 Continuar al pago</span></template>
          <svg x-show="orderLoading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          <span x-show="orderLoading">Procesando...</span>
        </button>
        @else
        <button @click="submitOrder()" :disabled="orderLoading"
                class="w-full py-4 font-black text-sm uppercase tracking-widest transition disabled:opacity-60 rounded-xl flex items-center justify-center gap-2 {{ $isQuoteOnly?'bg-[#25D366] hover:bg-[#20ba5a] text-white':'btn-gold' }}">
          <svg x-show="orderLoading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          <span x-show="!orderLoading">{{ $isQuoteOnly?($quoteWa?'Enviar por WhatsApp':'Solicitar cotización'):'Confirmar pedido' }}</span>
          <span x-show="orderLoading">Enviando...</span>
        </button>
        @endif
      </div>
    </div>

    @if(!$isQuoteOnly && $hasOnlinePayment)
    {{-- PASO 3 --}}
    <div x-show="drawerStep===3" class="flex flex-col flex-1 overflow-hidden">
      <div x-show="!orderSent" class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
        <div class="bg-gray-50 px-4 py-3 rounded-xl flex justify-between items-center">
          <span class="text-sm text-gray-600 font-medium">Total a pagar</span>
          <span class="font-black text-lg text-gray-900" x-text="'{{ $currency }} '+orderTotal.toFixed(2)"></span>
        </div>
        <p x-show="payError" class="text-red-500 text-xs text-center font-medium px-2" x-text="payError"></p>
        @if($payManualEnabled && count($payManualMethods)>0)
        <div class="space-y-2">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pago manual</p>
          @foreach($payManualMethods as $mKey)
          @php $mMeta=['yape'=>['label'=>'Yape','emoji'=>'🟣'],'plin'=>['label'=>'Plin','emoji'=>'🔵'],'transferencia'=>['label'=>'Transferencia bancaria','emoji'=>'🏦'],'qr'=>['label'=>'Pago con QR','emoji'=>'📲'],'contra_entrega'=>['label'=>'Contra entrega','emoji'=>'🚚']];$mm=$mMeta[$mKey]??null;$mmDetails=match($mKey){'yape'=>$payYapeNumber,'plin'=>$payPlinNumber,'transferencia'=>$payBankDetails,default=>''}; @endphp
          @if($mm)
          <div x-data="{open:false}" class="border border-gray-200 rounded-xl overflow-hidden">
            <button @click="open=!open" class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-gray-50 transition">
              <span class="text-2xl">{{ $mm['emoji'] }}</span>
              <div class="flex-1"><p class="text-sm font-bold text-gray-800">{{ $mm['label'] }}</p>@if($mmDetails)<p class="text-xs text-gray-500 truncate">{{ Str::limit($mmDetails,40) }}</p>@endif</div>
              <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open?'rotate-180':''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" class="px-4 pb-4 pt-3 space-y-3 bg-gray-50">
              @if($mmDetails)<div class="bg-white border border-gray-200 p-3 rounded-xl"><p class="text-xs font-semibold text-gray-600 mb-1">Datos para pagar:</p><p class="text-sm text-gray-800 whitespace-pre-line">{{ $mmDetails }}</p></div>@endif
              @if($payManualInstr)<p class="text-xs text-gray-500 italic">{{ $payManualInstr }}</p>@endif
              <div>
                <label class="text-xs font-semibold text-gray-600 block mb-1">Número de operación *</label>
                <input x-model="payReference" type="text" placeholder="Ej: 123456789"
                       class="w-full border border-gray-200 focus:border-gray-400 rounded-xl px-4 py-2.5 text-sm outline-none transition">
              </div>
              <button @click="confirmManualPay()" :disabled="payLoading||!payReference.trim()"
                      class="w-full btn-gold py-3 font-black text-sm uppercase tracking-widest transition disabled:opacity-50 rounded-xl flex items-center justify-center gap-2">
                <svg x-show="payLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
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
                  class="w-full flex items-center gap-3 px-4 py-3.5 border border-gray-200 rounded-xl text-left hover:border-gray-400 transition disabled:opacity-50">
            <span class="text-2xl">💳</span>
            <div class="flex-1"><p class="text-sm font-bold text-gray-800">Tarjeta crédito / débito</p><p class="text-xs text-gray-500">Visa, Mastercard — pago seguro Culqi</p></div>
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </button>
        </div>
        <script src="https://checkout.culqi.com/js/v4"></script>
        @endif
        @if($mpEnabled)
        <div class="space-y-2">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Mercado Pago</p>
          <button @click="openMercadoPago()" :disabled="payLoading"
                  class="w-full flex items-center gap-3 px-4 py-3.5 border border-gray-200 rounded-xl text-left hover:border-blue-400 transition disabled:opacity-50">
            <span class="text-2xl">🛒</span>
            <div class="flex-1"><p class="text-sm font-bold text-gray-800">Mercado Pago</p><p class="text-xs text-gray-500">Tarjetas, wallets, cuotas</p></div>
            <svg x-show="!payLoading" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <svg x-show="payLoading" class="w-4 h-4 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          </button>
        </div>
        @endif
      </div>
      <div x-show="orderSent" class="flex-1 flex flex-col items-center justify-center px-5 py-8 text-center">
        <div class="w-20 h-20 bg-green-100 flex items-center justify-center mb-5 rounded-full">
          <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>
        <p class="font-black text-gray-900 text-xl mb-2">¡Pago registrado!</p>
        <p class="text-sm text-gray-500 mb-6">Tu pedido está confirmado. Te contactaremos pronto.</p>
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
                class="btn-gold px-8 py-3 rounded-full text-sm font-bold uppercase tracking-widest transition">Seguir comprando</button>
      </div>
    </div>
    @endif
  </div>
</div>

{{-- ═══ FLOATING BAR ═══ --}}
<div x-show="cart.length>0" x-cloak
     class="fixed bottom-0 left-0 right-0 z-40 px-4 py-3 flex items-center gap-4"
     style="background:var(--navy2);border-top:1px solid rgba(184,151,58,.2);">
  <div class="flex items-center gap-2.5 flex-1 min-w-0">
    <span class="text-gray-900 text-xs font-black w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 bg-gold"
          x-text="cart.reduce((s,i)=>s+i.qty,0)"></span>
    <div class="min-w-0">
      <p class="text-[10px] text-gray-500 leading-none mb-0.5">Total del pedido</p>
      <p class="font-black text-base leading-none text-gold" x-text="'{{ $currency }} '+cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></p>
    </div>
  </div>
  <button @click="drawerOpen=true;drawerStep=1"
          class="btn-gold px-5 py-3 rounded-full font-black text-sm flex items-center gap-2 flex-shrink-0">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
    {{ $isQuoteOnly?'Ver cotización':'Ver pedido' }}
  </button>
</div>

{{-- ═══ ALPINE STORE ═══ --}}
<script>
function store() {
  const _cartKey = 'avan_cart_{{ $project->id }}';
  const _formKey = 'avan_form_{{ $project->id }}';
  let _savedCart = [];
  let _savedForm = { name:'', phone:'', email:'', notes:'', address:'' };
  try {
    const c = localStorage.getItem(_cartKey); if(c) _savedCart = JSON.parse(c);
    const f = localStorage.getItem(_formKey); if(f) _savedForm = {...{name:'',phone:'',email:'',notes:'',address:''},...JSON.parse(f)};
  } catch(e) {}

  return {
    _cartKey, _formKey,
    search:'', filterCat:'', filterParent:'', priceFilter:'', onSaleFilter:false, sortBy:'default',
    qv:null, qvOpen:false, expandedCats:{}, recentlyViewed:[], filterOpen:false, priceMin:0, priceMax:0,
    searchIndex: @json($searchIndex),
    searchOpen:false, searchIdx:-1, searchFocus:false,
    get visibleCount() {
      const s = this.search?this.search.toLowerCase():'';
      return this.searchIndex.filter(p=>{
        const nm = s===''||p.name.toLowerCase().includes(s);
        const cm = !this.filterCat||p.catId===this.filterCat;
        let pm = true;
        if(this.priceFilter==='0-50')    pm=p.price<=50;
        if(this.priceFilter==='50-150')  pm=p.price>50&&p.price<=150;
        if(this.priceFilter==='150-500') pm=p.price>150&&p.price<=500;
        if(this.priceFilter==='500+')    pm=p.price>500;
        if(this.priceFilter==='custom'){ const lo=this.priceMin>0?this.priceMin:0;const hi=this.priceMax>0?this.priceMax:Infinity;pm=p.price>=lo&&p.price<=hi; }
        const sm = !this.onSaleFilter||(p.cp&&p.cp>p.price);
        return nm&&cm&&pm&&sm;
      }).length;
    },
    get suggestions() {
      if(!this.search||this.search.trim().length<2) return [];
      const q=this.search.toLowerCase().trim();
      return this.searchIndex.filter(p=>p.name.toLowerCase().includes(q)||(p.cat&&p.cat.toLowerCase().includes(q))).slice(0,6);
    },
    selectSuggestion(p) { window.location.href=p.url; },
    _highlight(text) {
      if(!this.search||this.search.trim().length<2) return text;
      const q=this.search.trim().replace(/[.*+?^${}()|[\]\\]/g,'\\$&');
      return text.replace(new RegExp('('+q+')','gi'),'<strong style="color:var(--gold)">$1</strong>');
    },
    _scrollToCatalog() { const el=document.getElementById('catalogo');if(!el)return;if(el.getBoundingClientRect().top>100)el.scrollIntoView({behavior:'smooth',block:'start'}); },
    drawerOpen:false, drawerStep:1,
    toastShow:false, toastMsg:'', toastTimer:null,
    cart:_savedCart, form:_savedForm,
    orderLoading:false, orderSent:false, orderError:'', noResults:false,
    orderId:null, orderTotal:0,
    shippingEnabled:  {{ $shippingEnabled  ? 'true':'false' }},
    shippingCost:     {{ $shippingCost }},
    shippingFreeFrom: {{ $shippingFreeFrom }},
    requireAddress:   {{ $requireAddress   ? 'true':'false' }},
    get subtotal() { return this.cart.reduce((s,i)=>s+i.price*i.qty,0); },
    get effectiveShipping() {
      if(!this.shippingEnabled) return 0;
      if(this.shippingFreeFrom>0&&this.subtotal>=this.shippingFreeFrom) return 0;
      return this.shippingCost;
    },
    couponCode:'', couponApplied:null, couponError:'', couponLoading:false,
    get couponDiscount() {
      if(!this.couponApplied) return 0;
      const sub=this.subtotal;
      if(sub<(this.couponApplied.min_order||0)) return 0;
      if(this.couponApplied.type==='percent') return Math.min(sub*this.couponApplied.value/100,sub);
      return Math.min(this.couponApplied.value,sub);
    },
    async applyCoupon() {
      if(!this.couponCode.trim()) return;
      this.couponLoading=true; this.couponError='';
      const res=await fetch('/{{ $project->slug }}/coupon',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({code:this.couponCode,subtotal:this.subtotal})});
      const d=await res.json();
      this.couponLoading=false;
      if(d.ok){this.couponApplied=d;this.couponError='';}
      else{this.couponError=d.message;this.couponApplied=null;}
    },
    removeCoupon() { this.couponApplied=null;this.couponCode='';this.couponError=''; },
    get orderGrandTotal() { return Math.max(0,this.subtotal-this.couponDiscount+this.effectiveShipping); },
    selectedPayMethod:'', payReference:'', payLoading:false, payError:'',

    init() {
      try { const _rv=JSON.parse(localStorage.getItem('rv_{{ $project->slug }}')||'[]');this.recentlyViewed=_rv.filter(x=>x&&x.id); } catch(e){}
      this.$watch('cart',val=>{try{localStorage.setItem(this._cartKey,JSON.stringify(val));}catch(e){}});
      this.$watch('form',val=>{try{localStorage.setItem(this._formKey,JSON.stringify(val));}catch(e){}},{deep:true});
      this.$watch('search',()=>this.$nextTick(()=>this.checkNoResults()));
      this.$watch('filterCat',()=>this.$nextTick(()=>this.checkNoResults()));
      this.$watch('priceFilter',()=>this.$nextTick(()=>this.checkNoResults()));
      this.$watch('onSaleFilter',()=>this.$nextTick(()=>this.checkNoResults()));
      this.$watch('sortBy',()=>{this.applySort();this._syncUrl();});
      const _p=new URLSearchParams(window.location.search);
      if(_p.get('q'))    this.search=_p.get('q');
      if(_p.get('cat'))  this.filterCat=_p.get('cat');
      if(_p.get('price'))this.priceFilter=_p.get('price');
      if(_p.get('sale')) this.onSaleFilter=_p.get('sale')==='1';
      if(_p.get('sort')) {this.sortBy=_p.get('sort');this.$nextTick(()=>this.applySort());}
      this.$watch('search',()=>this._syncUrl());
      this.$watch('filterCat',()=>this._syncUrl());
      this.$watch('priceFilter',()=>this._syncUrl());
      this.$watch('onSaleFilter',()=>this._syncUrl());
    },
    applySort() {
      const grids=this.$el.querySelectorAll('[data-products-grid]');
      grids.forEach(grid=>{
        const cards=Array.from(grid.querySelectorAll('[data-price]'));
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
    _syncUrl() {
      const p=new URLSearchParams();
      if(this.search)       p.set('q',this.search);
      if(this.filterCat)    p.set('cat',this.filterCat);
      if(this.priceFilter)  p.set('price',this.priceFilter);
      if(this.onSaleFilter) p.set('sale','1');
      if(this.sortBy&&this.sortBy!=='default') p.set('sort',this.sortBy);
      history.replaceState(null,'',p.toString()?'?'+p.toString():window.location.pathname);
    },
    get cartCount() { return this.cart.reduce((s,i)=>s+i.qty,0); },
    get cartTotal()  { return this.cart.reduce((s,i)=>s+i.price*i.qty,0); },
    checkNoResults() {
      const hasFilter=this.search!==''||this.filterCat!==''||this.priceFilter!==''||this.onSaleFilter;
      if(!hasFilter){this.noResults=false;return;}
      const articles=document.querySelectorAll('#catalogo article');
      const visible=Array.from(articles).filter(el=>el.style.display!=='none');
      this.noResults=visible.length===0;
    },
    matchProduct(name,price,comparePrice) {
      if(this.search!==''&&!name.includes(this.search.toLowerCase())) return false;
      if(this.priceFilter==='0-50'    &&price>50) return false;
      if(this.priceFilter==='50-150'  &&(price<=50||price>150)) return false;
      if(this.priceFilter==='150-500' &&(price<=150||price>500)) return false;
      if(this.priceFilter==='500+'    &&price<=500) return false;
      if(this.onSaleFilter&&!(comparePrice&&comparePrice>price)) return false;
      return true;
    },
    addToCart(product) {
      const existing=this.cart.find(i=>i.id===product.id);
      if(existing){existing.qty++;}
      else{this.cart.push({...product,qty:1});}
      this.toastMsg='🥃 '+product.name+' agregado';
      this.toastShow=true;
      clearTimeout(this.toastTimer);
      this.toastTimer=setTimeout(()=>{this.toastShow=false;},2000);
    },
    sendQuoteWhatsapp() {
      if(!this.form.name.trim()){this.orderError='Por favor ingresa tu nombre.';return;}
      const businessName=`{{ addslashes($project->name) }}`;
      const customMsg=`{{ addslashes($quoteWaMsg) }}`;
      const fecha=new Date().toLocaleDateString('es-PE',{day:'2-digit',month:'long',year:'numeric'});
      let lines=`🛒 *SOLICITUD DE COTIZACIÓN*\n━━━━━━━━━━━━━━━━━━━━━━\n🏪 *${businessName}*\n\n${customMsg}\n\n👤 *DATOS*\n• Nombre: ${this.form.name}\n`;
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
      lines+=`\n📅 Fecha: ${fecha}\n_Cotización de ${businessName}_`;
      window.open(`https://wa.me/{{ $quoteWa }}?text=${encodeURIComponent(lines)}`,'_blank');
      this.cart=[];this.orderSent=true;
      try{localStorage.removeItem(this._cartKey);localStorage.removeItem(this._formKey);}catch(e){}
    },
    async submitOrder() {
      if(!this.form.name.trim()||!this.form.phone.trim()){this.orderError='Por favor ingresa tu nombre y teléfono.';return;}
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
        } else{this.orderError='No se pudo enviar. Inténtalo de nuevo.';}
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
      Culqi.options({style:{logo:'{{ $project->logo_url?asset("storage/".$project->logo_url):""  }}',maincolor:'{{ $primaryColor }}'}});
      Culqi.open();
      window.culqi=async function(){
        if(Culqi.token){
          self.payLoading=true;self.payError='';
          try {
            const res=await fetch(`{{ url('/'.$project->slug.'/pay') }}/${self.orderId}/culqi`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({token:Culqi.token.id,email:self.form.email})});
            const data=await res.json();
            if(data.ok){self.orderSent=true;try{localStorage.removeItem(self._cartKey);localStorage.removeItem(self._formKey);}catch(e){}}
            else{self.payError=data.message||'Error al procesar el pago.';}
          } catch(e){self.payError='Error de conexión.';}
          self.payLoading=false;Culqi.close();
        }
      };
    },
    @endif
    @if($mpEnabled)
    async openMercadoPago() {
      this.payLoading=true;this.payError='';
      try {
        const res=await fetch(`{{ url('/'.$project->slug.'/pay') }}/${this.orderId}/mp`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({})});
        const data=await res.json();
        if(data.ok){const url=(data.is_sandbox&&data.sandbox_init_point)?data.sandbox_init_point:data.init_point;window.location.href=url;}
        else{this.payError=data.message||'Error al iniciar Mercado Pago.';}
      } catch(e){this.payError='Error de conexión.';}
      this.payLoading=false;
    },
    @endif
  };
}
</script>
</body>
</html>
