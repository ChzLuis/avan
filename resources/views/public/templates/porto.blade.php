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
$mpEnabled        = ($settings['mp_enabled'] ?? '0') === '1';
$hasOnlinePayment = $culqiEnabled || $mpEnabled || $payManualEnabled;
$primaryColor     = $settings['primary_color'] ?? '#e63946';
$isQuoteOnly      = ($settings['store_mode'] ?? 'direct') === 'quote_only';
$shippingEnabled  = ($settings['shipping_enabled']  ?? '0') === '1';
$shippingCost     = (float)($settings['shipping_cost']      ?? 0);
$shippingFreeFrom = (float)($settings['shipping_free_from'] ?? 0);
$requireAddress   = ($settings['require_address']   ?? '0') === '1';
$quotePriceDisp   = $settings['quote_price_display'] ?? 'show';
$quoteWaRaw     = preg_replace('/\D/', '', $settings['quote_whatsapp'] ?? $project->whatsapp ?? '');
$quoteWaCountry = $settings['quote_whatsapp_country'] ?? '51';
$quoteWa        = $quoteWaRaw ? (str_starts_with($quoteWaRaw, $quoteWaCountry) ? $quoteWaRaw : $quoteWaCountry.$quoteWaRaw) : '';
$canonicalUrl   = url('/' . $project->slug);
$seoTitle       = ($settings['seo_title'] ?? null) ?: ($project->name . ' â€” CatÃ¡logo Online');
$seoDesc        = ($settings['seo_description'] ?? null) ?: ($project->description ?? 'Explora nuestros productos y haz tu pedido en lÃ­nea.');
$heroBg         = $settings['hero_bg_color'] ?? '#1a1a2e';
$heroTitle      = $settings['hero_title'] ?? 'Bienvenido a nuestra tienda';
$heroSub        = $settings['hero_subtitle'] ?? 'Descubre los mejores productos al mejor precio';
$heroBadge      = $settings['hero_badge'] ?? 'Novedad';
$b1Title        = $settings['banner1_title'] ?? 'Nuevos Productos';
$b1Sub          = $settings['banner1_sub'] ?? 'Llegan nuevas novedades esta semana';
$b2Title        = $settings['banner2_title'] ?? 'Ofertas Especiales';
$b2Sub          = $settings['banner2_sub'] ?? 'Hasta 50% de descuento seleccionados';
@endphp

<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDesc }}">
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDesc }}">
<meta property="og:locale" content="es_PE">
<meta property="og:site_name" content="{{ $project->name }}">

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

@php
  $secondaryColor = $settings['secondary_color'] ?? '#e55f00';
  $fontTitle  = $settings['font_title'] ?? $settings['font'] ?? 'Poppins';
  $fontBody   = $settings['font_body']  ?? $settings['font'] ?? 'Inter';
  $borderRadius = ['sharp'=>'0px','rounded'=>'8px','pill'=>'50px'][$settings['border_radius'] ?? 'rounded'] ?? '8px';
  $faviconUrl   = $settings['favicon_url'] ?? '';
  $catalogBadgeSale = $settings['catalog_badge_sale'] ?? 'OFERTA';
  $catalogBadgeNew  = $settings['catalog_badge_new']  ?? 'NUEVO';
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
  * { font-family: var(--font-body); box-sizing: border-box; }
  [x-cloak] { display: none !important; }
  body { background: #f8f9fa; }

  /* Colores utilitarios */
  .accent        { color: var(--c); }
  .bg-accent     { background: var(--c); }
  .border-accent { border-color: var(--c); }
  .btn-accent    { background: var(--c); color: #fff; transition: opacity .2s; }
  .btn-accent:hover { opacity: .88; }
  .btn-outline-white { border: 2px solid #fff; color: #fff; transition: all .2s; }
  .btn-outline-white:hover { background: #fff; color: #1a1a2e; }

  /* Porto card */
  .porto-card { background: #fff; border: 1px solid #e5e7eb; transition: box-shadow .2s; overflow: hidden; }
  .porto-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.12); }
  .porto-card .card-actions {
    position: absolute; bottom: 0; left: 0; right: 0;
    transform: translateY(100%); transition: transform .28s cubic-bezier(.4,0,.2,1);
    background: rgba(0,0,0,.78); padding: 8px 10px;
  }
  .porto-card:hover .card-actions { transform: translateY(0); }
  .porto-card .wishlist-btn {
    position: absolute; top: 8px; right: 8px; opacity: 0; transition: opacity .2s;
    background: #fff; border-radius: 50%; width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 2px 8px rgba(0,0,0,.15);
  }
  .porto-card:hover .wishlist-btn { opacity: 1; }

  /* Drawer */
  .drawer-overlay { position: fixed; inset: 0; z-index: 50; background: rgba(0,0,0,.45); backdrop-filter: blur(2px); }
  .drawer-panel   { position: fixed; right: 0; top: 0; bottom: 0; z-index: 51; width: 100%; max-width: 420px; background: #fff; display: flex; flex-direction: column; box-shadow: -4px 0 32px rgba(0,0,0,.18); }

  /* Mega nav dropdown */
  .mega-dropdown { position: absolute; top: 100%; left: 0; width: 240px; background: #fff; border: 1px solid #e5e7eb; box-shadow: 0 10px 30px rgba(0,0,0,.14); z-index: 100; }

  /* Scrollbar hide */
  .scrollbar-hide::-webkit-scrollbar { display: none; }
  .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

  /* Hero */
  .hero-slide { min-height: 460px; }

  html { scroll-behavior: smooth; }
</style>

@php
$currency = $settings['currency'] ?? 'S/';
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

{{-- â•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—
     â•‘  DRAWER â€” 3 pasos                               â•‘
     â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<div x-show="drawerOpen" class="drawer-overlay" @click="drawerOpen=false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

<div x-show="drawerOpen" class="drawer-panel"
  x-transition:enter="transition ease-out duration-300"
  x-transition:enter-start="translate-x-full"
  x-transition:enter-end="translate-x-0"
  x-transition:leave="transition ease-in duration-200"
  x-transition:leave-start="translate-x-0"
  x-transition:leave-end="translate-x-full">

  <!-- Cabecera drawer -->
  <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
    <span class="font-semibold text-gray-800 text-base flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
      </svg>
      <span x-show="drawerStep===1">Mi Carrito (<span x-text="cartCount"></span>)</span>
      <span x-show="drawerStep===2">Datos del pedido</span>
      <span x-show="drawerStep===3">ConfirmaciÃ³n</span>
    </span>
    <!-- Indicador pasos -->
    <div class="flex items-center gap-1 mr-3">
      <span class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold" :class="drawerStep>=1?'bg-accent text-white':'bg-gray-100 text-gray-400'">1</span>
      <span class="w-4 h-px" :class="drawerStep>=2?'bg-accent':'bg-gray-200'"></span>
      <span class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold" :class="drawerStep>=2?'bg-accent text-white':'bg-gray-100 text-gray-400'">2</span>
      <span class="w-4 h-px" :class="drawerStep>=3?'bg-accent':'bg-gray-200'"></span>
      <span class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold" :class="drawerStep>=3?'bg-accent text-white':'bg-gray-100 text-gray-400'">3</span>
    </div>
    <button @click="drawerOpen=false" class="text-gray-400 hover:text-gray-700 transition">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  </div>

  {{-- â”€â”€ PASO 1: Carrito â”€â”€ --}}
  <div x-show="drawerStep===1" class="flex flex-col flex-1 overflow-hidden">
    <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4 scrollbar-hide">
      <template x-if="cart.length===0">
        <div class="text-center py-16">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-3 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
          <p class="text-sm text-gray-400 mb-1">Tu carrito estÃ¡ vacÃ­o</p>
          <p class="text-xs text-gray-300">Agrega productos para comenzar</p>
          <button @click="drawerOpen=false" class="mt-4 text-xs accent font-semibold hover:opacity-70 transition">Continuar comprando â†’</button>
        </div>
      </template>
      <template x-for="(item, idx) in cart" :key="item.id">
        <div class="flex gap-3 items-start pb-4 border-b border-gray-50 last:border-0">
          <img :src="item.img||''" class="w-16 h-16 object-cover bg-gray-100 flex-shrink-0 border border-gray-100 rounded" :alt="item.name" onerror="this.style.display='none'">
          <div class="flex-1 min-w-0">
            <p class="text-gray-700 text-xs font-semibold truncate" x-text="item.name"></p>
            <p class="accent text-sm font-bold mt-0.5">S/ <span x-text="(item.price*item.qty).toFixed(2)"></span></p>
            <p class="text-gray-400 text-[10px]">S/ <span x-text="item.price.toFixed(2)"></span> c/u</p>
            <div class="flex items-center gap-2 mt-2">
              <button @click="item.qty>1?item.qty--:removeFromCart(idx)"
                class="w-7 h-7 border border-gray-200 text-gray-500 text-sm flex items-center justify-center hover:border-gray-400 hover:bg-gray-50 transition rounded">âˆ’</button>
              <span class="text-gray-700 text-sm font-semibold w-5 text-center" x-text="item.qty"></span>
              <button @click="item.qty++"
                class="w-7 h-7 border border-gray-200 text-gray-500 text-sm flex items-center justify-center hover:border-gray-400 hover:bg-gray-50 transition rounded">+</button>
              <button @click="removeFromCart(idx)" class="ml-auto text-gray-200 hover:text-red-400 transition p-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>
    <div class="border-t border-gray-100 px-5 py-4 bg-gray-50 flex-shrink-0">
      <div class="flex justify-between text-sm mb-1">
        <span class="text-gray-400"><span x-text="cartCount"></span> producto(s)</span>
        <span class="font-bold text-gray-800">S/ <span x-text="cartTotal.toFixed(2)"></span></span>
      </div>
      <p class="text-[10px] text-gray-300 mb-3">EnvÃ­o coordinado al confirmar pedido</p>
      <button @click="cart.length>0&&(drawerStep=2)" :disabled="cart.length===0"
        class="w-full py-3 text-sm font-semibold btn-accent rounded disabled:opacity-30 transition">
        Proceder al pago â†’
      </button>
      <button @click="drawerOpen=false" class="w-full py-2 text-xs text-gray-400 hover:text-gray-600 transition mt-1">Continuar comprando</button>
    </div>
  </div>

  {{-- â”€â”€ PASO 2: Datos del pedido â”€â”€ --}}
  <div x-show="drawerStep===2" class="flex flex-col flex-1 overflow-hidden">
    <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4 scrollbar-hide">
      <p class="text-gray-400 text-xs">Completa tu informaciÃ³n para confirmar el pedido</p>
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre completo <span class="text-red-400">*</span></label>
        <input type="text" x-model="form.name"
          class="w-full border border-gray-200 rounded-md py-2.5 px-3 text-sm text-gray-700 focus:outline-none focus:border-gray-400 transition"
          placeholder="Tu nombre completo">
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">TelÃ©fono / WhatsApp <span class="text-red-400">*</span></label>
        <input type="tel" x-model="form.phone"
          class="w-full border border-gray-200 rounded-md py-2.5 px-3 text-sm text-gray-700 focus:outline-none focus:border-gray-400 transition"
          placeholder="Ej: 987 654 321">
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Correo electrÃ³nico</label>
        <input type="email" x-model="form.email"
          class="w-full border border-gray-200 rounded-md py-2.5 px-3 text-sm text-gray-700 focus:outline-none focus:border-gray-400 transition"
          placeholder="tu@correo.com">
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Notas del pedido</label>
        <textarea x-model="form.notes" rows="3"
          class="w-full border border-gray-200 rounded-md py-2.5 px-3 text-sm text-gray-700 focus:outline-none focus:border-gray-400 transition resize-none"
          placeholder="Indicaciones especiales, talla, color..."></textarea>
      </div>
      @if($requireAddress)
      <div class="px-5 pb-2">
        <input x-model="form.address" type="text" placeholder="DirecciÃ³n de entrega *"
               class="w-full border border-gray-200 rounded-md py-2.5 px-3 text-sm text-gray-700 focus:outline-none focus:border-gray-400 transition"
               autocomplete="street-address">
      </div>
      @endif
      <!-- CupÃ³n -->
      <div class="px-5 mb-3">
        <div x-show="!couponApplied" class="flex gap-2">
          <input x-model="couponCode" @keydown.enter.prevent="applyCoupon" type="text"
                 placeholder="CÃ³digo de descuento"
                 class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-xs outline-none focus:border-[var(--accent)] uppercase transition"
                 style="text-transform:uppercase">
          <button @click="applyCoupon" :disabled="couponLoading" type="button"
                  class="px-3 py-2 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 transition text-gray-700 flex-shrink-0">
            <span x-text="couponLoading ? 'â€¦' : 'Aplicar'"></span>
          </button>
        </div>
        <div x-show="couponApplied" class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2 text-xs">
          <div>
            <span class="font-mono font-bold text-green-700" x-text="couponApplied ? couponApplied.code : ''"></span>
            <span class="text-green-600 ml-1">&mdash; <span x-text="couponApplied && couponApplied.type==='percent' ? couponApplied.value+'% desc.' : 'S/ '+(couponApplied?couponApplied.value:0).toFixed(2)+' desc.'"></span></span>
          </div>
          <button @click="removeCoupon" type="button" class="text-gray-400 hover:text-red-500 ml-2 text-base leading-none">&times;</button>
        </div>
        <p x-show="couponError" class="text-red-500 text-xs mt-1" x-text="couponError"></p>
      </div>
      <!-- Resumen -->
      <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 mx-5 mb-3">
        <p class="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Resumen</p>
        <template x-for="item in cart" :key="item.id">
          <div class="flex justify-between text-xs text-gray-500 py-0.5">
            <span x-text="item.name+' Ã— '+item.qty" class="truncate mr-2 max-w-[180px]"></span>
            <span class="font-semibold whitespace-nowrap">S/ <span x-text="(item.price*item.qty).toFixed(2)"></span></span>
          </div>
        </template>
        <div x-show="couponApplied && couponDiscount > 0" class="flex justify-between text-xs text-green-600 font-medium border-t border-gray-200 pt-1.5 mt-1.5">
          <span>Descuento</span>
          <span x-text="'- S/ ' + couponDiscount.toFixed(2)"></span>
        </div>
        @if($shippingEnabled)
        <div class="flex justify-between text-xs text-gray-500 border-t border-gray-200 pt-1.5 mt-1.5" :class="effectiveShipping===0 && shippingFreeFrom>0 ? 'text-green-600' : ''">
          <span x-text="effectiveShipping===0 && shippingFreeFrom>0 ? 'ðŸŽ‰ EnvÃ­o gratis' : 'EnvÃ­o'"></span>
          <span x-text="effectiveShipping>0 ? 'S/ '+effectiveShipping.toFixed(2) : 'Gratis'"></span>
        </div>
        @endif
        <div class="flex justify-between text-sm font-bold text-gray-800 border-t border-gray-200 pt-2 mt-2">
          <span>Total</span>
          <span class="accent">S/ <span x-text="orderGrandTotal.toFixed(2)"></span></span>
        </div>
      </div>
      <div x-show="orderError" x-text="orderError" class="text-red-500 text-xs py-2 px-3 bg-red-50 rounded-lg border border-red-100"></div>
    </div>
    <div class="border-t border-gray-100 px-5 py-4 space-y-2 flex-shrink-0">
      <button @click="submitOrder()" :disabled="orderLoading"
        class="w-full py-3 text-sm font-semibold btn-accent rounded-lg disabled:opacity-50 transition flex items-center justify-center gap-2">
        <svg x-show="orderLoading" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <span x-text="orderLoading?'Enviando pedido...':'Confirmar pedido'"></span>
      </button>
      <button @click="drawerStep=1" class="w-full py-2 text-xs text-gray-400 hover:text-gray-600 transition">â† Volver al carrito</button>
    </div>
  </div>

  {{-- â”€â”€ PASO 3: ConfirmaciÃ³n / Pago â”€â”€ --}}
  <div x-show="drawerStep===3" class="flex flex-col flex-1 overflow-y-auto px-5 py-6 scrollbar-hide gap-4">

    <!-- Pago confirmado (regresa de MP/Culqi con Ã©xito) -->
    <template x-if="orderSent">
      <div class="text-center py-6">
        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <p class="font-bold text-gray-800 text-xl mb-1">Â¡Pago confirmado!</p>
        <p class="text-gray-500 text-sm">Pedido #<span x-text="orderId"></span></p>
        <p x-show="payReference" class="text-gray-400 text-xs mt-1">Referencia: <span x-text="payReference"></span></p>
        <p class="text-gray-400 text-xs mt-3">RecibirÃ¡s confirmaciÃ³n pronto. Â¡Gracias por tu compra!</p>
        <button @click="cart=[];drawerOpen=false;drawerStep=1;orderSent=false"
          class="mt-6 btn-accent px-8 py-2.5 rounded-lg text-sm font-semibold">Seguir comprando</button>
      </div>
    </template>

    <!-- Pago fallido -->
    <template x-if="!orderSent && payError && orderId===0">
      <div class="text-center py-6">
        <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </div>
        <p class="font-bold text-gray-800 text-lg mb-2">Pago rechazado</p>
        <p x-text="payError" class="text-red-500 text-sm bg-red-50 rounded p-3 mb-4"></p>
        <button @click="drawerStep=2" class="btn-accent px-6 py-2.5 rounded-lg text-sm font-semibold">Intentar de nuevo</button>
      </div>
    </template>

    <!-- Pedido creado â€” seleccionar mÃ©todo de pago -->
    <template x-if="!orderSent && orderId > 0">
      <div>
        <div class="text-center mb-5 bg-green-50 rounded-xl p-5 border border-green-100">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-green-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          <p class="font-bold text-gray-800 text-lg">Pedido #<span x-text="orderId"></span> creado</p>
          <p class="accent font-bold text-xl mt-1">S/ <span x-text="orderTotal.toFixed(2)"></span></p>
          <p class="text-gray-400 text-xs mt-1">Ahora elige cÃ³mo pagar</p>
        </div>

        @if($hasOnlinePayment)
        <p class="text-xs font-semibold text-gray-400 mb-3 uppercase tracking-widest">MÃ©todo de pago</p>
        <div class="space-y-2">

          @if($payManualEnabled && in_array('yape', $payManualMethods) && $payYapeNumber)
          <div class="border border-gray-100 rounded-xl p-4 flex items-center gap-4 hover:border-purple-200 transition cursor-default">
            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0 text-xl">ðŸŸ£</div>
            <div>
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Yape al nÃºmero</p>
              <p class="text-base font-bold text-gray-800">{{ $payYapeNumber }}</p>
            </div>
          </div>
          @endif

          @if($payManualEnabled && in_array('plin', $payManualMethods) && $payPlinNumber)
          <div class="border border-gray-100 rounded-xl p-4 flex items-center gap-4 hover:border-blue-200 transition cursor-default">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 text-xl">ðŸ”µ</div>
            <div>
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Plin al nÃºmero</p>
              <p class="text-base font-bold text-gray-800">{{ $payPlinNumber }}</p>
            </div>
          </div>
          @endif

          @if($payManualEnabled && $payBankDetails)
          <div class="border border-gray-100 rounded-xl p-4 hover:border-green-200 transition">
            <p class="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">ðŸ¦ Transferencia bancaria</p>
            <p class="text-xs text-gray-600 whitespace-pre-line leading-relaxed">{{ $payBankDetails }}</p>
          </div>
          @endif

          @if($culqiEnabled)
          <button id="btn-culqi-porto"
            class="w-full py-3 text-sm font-semibold btn-accent rounded-xl flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            Pagar con tarjeta (Culqi)
          </button>
          @endif

          @if($mpEnabled)
          <a :href="'/{{ $project->slug }}/mp-checkout?order='+orderId"
            class="flex items-center justify-center gap-2 w-full py-3 text-sm font-semibold border-2 border-gray-200 text-gray-700 text-center rounded-xl hover:border-[#009ee3] hover:text-[#009ee3] transition">
            <span>ðŸ’³</span> Pagar con Mercado Pago
          </a>
          @endif

        </div>

        @if($payManualInstr)
        <div class="mt-4 p-4 bg-amber-50 border border-amber-100 rounded-xl text-xs text-amber-700 leading-relaxed">
          <p class="font-semibold mb-1">Instrucciones de pago</p>
          {{ $payManualInstr }}
        </div>
        @endif

        @else
        <div class="text-center bg-blue-50 p-5 rounded-xl border border-blue-100">
          <p class="text-blue-700 text-sm font-semibold mb-1">Â¡Pedido recibido!</p>
          <p class="text-blue-500 text-xs">Nos pondremos en contacto contigo para coordinar el pago y la entrega.</p>
        </div>
        @endif

        <div x-show="payError" x-text="payError" class="text-red-500 text-xs mt-3 text-center bg-red-50 p-3 rounded-xl border border-red-100"></div>
        <button @click="cart=[];drawerOpen=false;drawerStep=1"
          class="mt-4 w-full py-2.5 text-sm border border-gray-100 text-gray-400 rounded-xl hover:bg-gray-50 transition">
          Cerrar y seguir comprando
        </button>
      </div>
    </template>

    <!-- Estado cargando (submitOrder en proceso) -->
    <template x-if="!orderSent && orderId===0 && !payError">
      <div class="text-center py-12">
        <div class="w-10 h-10 border-2 border-gray-200 border-t-[var(--c)] rounded-full animate-spin mx-auto mb-4"></div>
        <p class="text-gray-400 text-sm">Procesando tu pedido...</p>
      </div>
    </template>

  </div>
</div>
{{-- FIN DRAWER --}}


{{-- â•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—
     â•‘  TOP BAR                                        â•‘
     â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<div class="bg-[#2d2d2d] py-2">
  <div class="max-w-7xl mx-auto px-4 flex items-center justify-between text-xs text-gray-400">
    <div class="flex items-center gap-5">
      @if($project->phone)
      <span class="flex items-center gap-1.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
        </svg>
        {{ $project->phone }}
      </span>
      @endif
      @if($project->address)
      <span class="hidden md:flex items-center gap-1.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        {{ $project->address }}
      </span>
      @endif
    </div>
    <div class="flex items-center gap-4">
      @if($settings['facebook_url'] ?? '')
      <a href="{{ $settings['facebook_url'] }}" target="_blank" rel="noopener" class="hover:text-white transition flex items-center gap-1">
        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        <span class="hidden sm:inline">Facebook</span>
      </a>
      @endif
      @if($settings['instagram_url'] ?? '')
      <a href="{{ $settings['instagram_url'] }}" target="_blank" rel="noopener" class="hover:text-white transition flex items-center gap-1">
        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
        <span class="hidden sm:inline">Instagram</span>
      </a>
      @endif
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}" target="_blank" rel="noopener" class="hover:text-white transition flex items-center gap-1">
        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        <span class="hidden sm:inline">WhatsApp</span>
      </a>
      @endif
    </div>
  </div>
</div>


{{-- â•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—
     â•‘  HEADER sticky                                  â•‘
     â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<header class="bg-white shadow-sm sticky top-0 z-40">
  <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-4">
    <!-- Logo -->
    <a href="#" class="flex-shrink-0 mr-2">
      @if($project->logo_url)
        <img src="{{ asset('storage/'.$project->logo_url) }}" alt="{{ $project->name }}" class="h-12 object-contain max-w-[160px]">
      @else
        <span class="font-bold text-gray-800 text-xl tracking-tight">{{ $project->name }}</span>
      @endif
    </a>

    <!-- Buscador ancho -->
    <div class="flex-1 relative" @click.outside="searchOpen = false">
      <div class="flex border border-gray-200 rounded-lg overflow-hidden hover:border-gray-300 transition focus-within:border-gray-400">
        <select x-model="filterCat"
          class="border-r border-gray-200 bg-gray-50 text-xs text-gray-500 px-3 py-2.5 focus:outline-none hidden md:block min-w-[140px]">
          <option value="">Todas las categorÃ­as</option>
          @foreach($categories as $cat)
          <option value="{{ $cat->id }}">{{ $cat->name }}</option>
          @endforeach
        </select>
        <input type="text" x-model="search" placeholder="Buscar productos..."
               @input="searchOpen = search.trim().length >= 2; searchIdx = -1; if(search.trim().length >= 2) _scrollToCatalog()"
               @keydown.arrow-down.prevent="if(suggestions.length){searchIdx=(searchIdx+1)%suggestions.length}"
               @keydown.arrow-up.prevent="if(suggestions.length){searchIdx=(searchIdx-1+suggestions.length)%suggestions.length}"
               @keydown.enter.prevent="if(searchIdx>=0){selectSuggestion(suggestions[searchIdx])}else{searchOpen=false;_scrollToCatalog()}"
               @keydown.escape="searchOpen=false;searchIdx=-1"
               class="w-full py-2.5 px-4 text-sm text-gray-700 focus:outline-none bg-white">
        <button @click="searchOpen=false; _scrollToCatalog()" class="bg-accent text-white px-5 text-sm font-semibold hover:opacity-90 transition flex-shrink-0 flex items-center gap-1.5">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <span class="hidden sm:inline">Buscar</span>
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

    <!-- Carrito -->
    <button @click="drawerOpen=true;drawerStep=1"
      class="flex items-center gap-2 text-gray-700 hover:text-gray-900 transition flex-shrink-0 ml-1 group">
      <div class="relative">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 group-hover:scale-105 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <span x-show="cartCount>0" x-text="cartCount"
          class="absolute -top-2 -right-2 min-w-[20px] h-5 rounded-full bg-accent text-white text-[10px] font-bold flex items-center justify-center px-1"></span>
      </div>
      <div class="hidden md:block text-left">
        <p class="text-[10px] text-gray-400 leading-none">Carrito</p>
        <p class="text-sm font-bold leading-none accent">S/ <span x-text="cartTotal.toFixed(2)"></span></p>
      </div>
    </button>
  </div>
</header>


{{-- â•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—
     â•‘  MEGA NAV                                       â•‘
     â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<nav class="bg-accent sticky top-[72px] z-30" x-data="{catOpen:false}">
  <div class="max-w-7xl mx-auto px-4 flex items-center">
    <!-- Dropdown categorÃ­as -->
    <div class="relative flex-shrink-0">
      <button @click="catOpen=!catOpen"
        class="flex items-center gap-2 text-white font-semibold text-sm py-3 px-4 hover:bg-black/10 transition border-r border-white/10">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
        <span class="hidden sm:inline">â˜° Todas las categorÃ­as</span>
        <span class="sm:hidden">CategorÃ­as</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="catOpen?'rotate-180':''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>
      <div x-show="catOpen" @click.outside="catOpen=false" class="mega-dropdown" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
        <button @click="filterCat='';catOpen=false;document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
          class="block w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 font-medium border-b border-gray-50 transition">
          ðŸª Todos los productos
        </button>
        @foreach($categories as $cat)
        <button @click="filterCat='{{ $cat->id }}';catOpen=false;document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
          class="block w-full text-left px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-b border-gray-50 transition">
          {{ $cat->name }}
        </button>
        @endforeach
      </div>
    </div>

    <!-- Links rÃ¡pidos de categorÃ­as -->
    <div class="flex items-center overflow-x-auto scrollbar-hide flex-1">
      @foreach($categories as $cat)
      <button @click="filterCat='{{ $cat->id }}';document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
        class="text-white/85 hover:text-white text-sm py-3 px-3 whitespace-nowrap hover:bg-black/10 transition flex-shrink-0">
        {{ $cat->name }}
      </button>
      @endforeach
    </div>
  </div>
</nav>


{{-- â•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—
     â•‘  HERO â€” 2 slides Alpine auto-rotando            â•‘
     â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<section x-data="{
  slide: 0,
  slides: [
    {title:'{{ addslashes($heroTitle) }}', sub:'{{ addslashes($heroSub) }}', badge:'{{ addslashes($heroBadge) }}', bg:'{{ $heroBg }}'},
    {title:'{{ addslashes($b1Title) }}',   sub:'{{ addslashes($b1Sub) }}',   badge:'Nuevo',  bg:'#1e3a5f'},
  ]
}" x-init="setInterval(()=>{ slide=(slide+1)%slides.length }, 4000)">
  <div class="relative overflow-hidden hero-slide">
    <template x-for="(s, i) in slides" :key="i">
      <div x-show="slide===i"
        x-transition:enter="transition ease-in-out duration-700"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="absolute inset-0 flex items-center"
        :style="'background:'+s.bg">
        <!-- PatrÃ³n decorativo fondo -->
        <div class="absolute inset-0 opacity-5" style="background-image:radial-gradient(circle at 70% 50%, #fff 1px, transparent 1px);background-size:40px 40px;"></div>
        <div class="relative max-w-7xl mx-auto px-8 md:px-20 w-full">
          <div class="max-w-xl">
            <span x-show="s.badge" x-text="s.badge"
              class="inline-block bg-accent text-white text-xs font-bold uppercase tracking-widest px-4 py-1.5 rounded-full mb-5"></span>
            <h2 x-text="s.title" class="text-3xl md:text-5xl font-extrabold text-white leading-tight mb-4 drop-shadow-sm"></h2>
            <p x-text="s.sub" class="text-white/75 text-base md:text-lg mb-8 leading-relaxed"></p>
            <div class="flex flex-wrap gap-3">
              <button @click="document.getElementById('novedades').scrollIntoView({behavior:'smooth'})"
                class="btn-accent px-7 py-3 rounded-lg text-sm font-semibold shadow-lg">
                Ver productos
              </button>
              <button @click="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                class="btn-outline-white px-7 py-3 rounded-lg text-sm font-semibold">
                Ver catÃ¡logo completo
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>
    <!-- Indicadores dots -->
    <div class="absolute bottom-5 left-1/2 -translate-x-1/2 flex gap-2.5 z-10">
      <template x-for="(s, i) in slides" :key="i">
        <button @click="slide=i"
          class="rounded-full transition-all duration-300"
          :class="slide===i ? 'bg-white w-6 h-2.5' : 'bg-white/40 w-2.5 h-2.5'"></button>
      </template>
    </div>
  </div>
</section>


{{-- â•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—
     â•‘  3 BANNERS FILA                                 â•‘
     â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<section class="max-w-7xl mx-auto px-4 py-7">
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <!-- Banner 1 â€” color primario -->
    <div class="bg-accent rounded-xl p-7 text-white relative overflow-hidden flex flex-col justify-between min-h-[130px]">
      <div class="absolute right-0 top-0 w-32 h-32 rounded-full bg-white/10 -translate-y-8 translate-x-8"></div>
      <div>
        <p class="font-bold text-lg leading-tight">{{ $b1Title }}</p>
        @if($b1Sub)<p class="text-white/75 text-sm mt-1">{{ $b1Sub }}</p>@endif
      </div>
      <a href="#novedades" class="mt-4 inline-block border border-white/40 text-white text-xs font-semibold px-4 py-1.5 rounded-lg hover:bg-white/15 transition self-start">Ver mÃ¡s â†’</a>
    </div>
    <!-- Banner 2 â€” gris oscuro -->
    <div class="bg-gray-700 rounded-xl p-7 text-white relative overflow-hidden flex flex-col justify-between min-h-[130px]">
      <div class="absolute right-0 top-0 w-32 h-32 rounded-full bg-white/5 -translate-y-8 translate-x-8"></div>
      <div>
        <p class="font-bold text-lg leading-tight">{{ $b2Title }}</p>
        @if($b2Sub)<p class="text-white/75 text-sm mt-1">{{ $b2Sub }}</p>@endif
      </div>
      <a href="#catalogo" class="mt-4 inline-block border border-white/30 text-white text-xs font-semibold px-4 py-1.5 rounded-lg hover:bg-white/15 transition self-start">Ver mÃ¡s â†’</a>
    </div>
    <!-- Banner 3 â€” #1a1a2e -->
    <div class="bg-[#1a1a2e] rounded-xl p-7 text-white relative overflow-hidden flex flex-col justify-between min-h-[130px]">
      <div class="absolute right-0 top-0 w-32 h-32 rounded-full bg-white/5 -translate-y-8 translate-x-8"></div>
      <div>
        <p class="font-bold text-lg leading-tight">CatÃ¡logo completo</p>
        <p class="text-white/60 text-sm mt-1">{{ $categories->count() }} categorÃ­as disponibles</p>
      </div>
      <a href="#catalogo" class="mt-4 inline-block border border-white/20 text-white text-xs font-semibold px-4 py-1.5 rounded-lg hover:bg-white/10 transition self-start">Ver mÃ¡s â†’</a>
    </div>
  </div>
</section>


{{-- â•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—
     â•‘  NEW ARRIVALS con TABS                          â•‘
     â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<section id="novedades" class="max-w-7xl mx-auto px-4 py-8" x-data="{tab:'new'}">
  <div class="flex items-center gap-1 border-b border-gray-200 mb-6 overflow-x-auto scrollbar-hide">
    <h2 class="font-bold text-gray-800 text-xl mr-5 whitespace-nowrap">Productos</h2>
    <button @click="tab='new'"
      :class="tab==='new'?'border-b-2 border-accent accent font-semibold':'text-gray-500 hover:text-gray-700'"
      class="pb-3 pt-1 px-3 text-sm transition whitespace-nowrap">Novedades</button>
    <button @click="tab='sale'"
      :class="tab==='sale'?'border-b-2 border-accent accent font-semibold':'text-gray-500 hover:text-gray-700'"
      class="pb-3 pt-1 px-3 text-sm transition whitespace-nowrap">Ofertas</button>
    <button @click="tab='featured'"
      :class="tab==='featured'?'border-b-2 border-accent accent font-semibold':'text-gray-500 hover:text-gray-700'"
      class="pb-3 pt-1 px-3 text-sm transition whitespace-nowrap">Destacados</button>
  </div>

  {{-- Tab Novedades --}}
  <div x-show="tab==='new'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
    @forelse($newArrivals as $p)
    <article x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }})"
      class="porto-card group relative cursor-pointer">
      <div class="relative aspect-square overflow-hidden bg-gray-50">
        @if($p->mainImage)
        <img src="{{ $p->main_image_url }}" alt="{{ $p->name }}"
          class="w-full h-full object-cover transition duration-500 group-hover:scale-108"
          style="transition:transform .5s ease" loading="lazy">
        @else
        <div class="w-full h-full flex items-center justify-center bg-gray-100">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </div>
        @endif
        @if($p->compare_price && $p->compare_price > $p->price)
        <span class="absolute top-2 left-2 bg-red-500 text-white text-[9px] font-extrabold uppercase px-2 py-0.5 rounded">Sale</span>
        @endif
        <div class="wishlist-btn text-gray-300 hover:text-red-400 transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
          </svg>
        </div>
        @if(!$isQuoteOnly)
        <div class="card-actions">
          <button @click.stop="addToCart({id:{{ $p->id }}, name:'{{ addslashes($p->name) }}', price:{{ $p->price }}, img:'{{ $p->mainImage ? $p->main_image_url : '' }}'})"
            class="w-full text-[11px] font-semibold text-white text-center py-1.5 hover:opacity-80 transition">
            + Agregar al carrito
          </button>
        </div>
        @else
        <div class="card-actions">
          <a href="https://wa.me/{{ $quoteWa }}?text={{ urlencode('Hola, me interesa: '.$p->name) }}" target="_blank"
            class="block w-full text-[11px] font-semibold text-white text-center py-1.5 hover:opacity-80 transition">
            Cotizar por WhatsApp
          </a>
        </div>
        @endif
        @if($p->stock !== null && $p->stock === 0)
        <span class="absolute bottom-0 left-0 right-0 bg-red-600/90 text-white text-[10px] font-black py-1 text-center tracking-wide">AGOTADO</span>
        @elseif($p->stock !== null && $p->stock > 0 && $p->stock <= 5)
        <span class="absolute bottom-0 left-0 right-0 bg-orange-500/90 text-white text-[10px] font-bold py-1 text-center leading-tight">âš¡ CASI AGOTADO â€” {{ $p->stock }} restantes</span>
        @endif
      </div>
      <div class="p-3">
        <p class="text-gray-400 text-[10px] mb-0.5 truncate">{{ $p->category->name ?? '' }}</p>
        <p class="text-gray-700 text-xs font-semibold truncate"><a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="hover:underline">{{ $p->name }}</a></p>
        <div class="text-yellow-400 text-[11px] my-1">â­â­â­â­â­</div>
        @if(!$isQuoteOnly || $quotePriceDisp === 'show')
        <div class="flex items-baseline gap-2">
          <span class="accent font-bold text-sm">S/ {{ number_format($p->price, 2) }}</span>
          @if($p->compare_price && $p->compare_price > $p->price)
          <span class="text-gray-300 text-xs line-through">S/ {{ number_format($p->compare_price, 2) }}</span>
          @endif
          @if($p->compare_price && $p->compare_price > $p->price)
          @php $ah = $p->compare_price - $p->price; @endphp
          <p class="text-[10px] text-green-600 font-semibold leading-none mt-1 w-full">Ahorras {{ $currency }} {{ number_format($ah,2) }}</p>
          @endif
        </div>
        @else
        <span class="text-gray-400 text-xs">Consultar precio</span>
        @endif
      </div>
    </article>
    @empty
    <p class="col-span-5 text-center text-gray-400 text-sm py-8">No hay novedades disponibles.</p>
    @endforelse
  </div>

  {{-- Tab Ofertas --}}
  <div x-show="tab==='sale'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
    @forelse($onSale as $p)
    <article x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }})"
      class="porto-card group relative cursor-pointer">
      <div class="relative aspect-square overflow-hidden bg-gray-50">
        @if($p->mainImage)
        <img src="{{ $p->main_image_url }}" alt="{{ $p->name }}"
          class="w-full h-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
        @else
        <div class="w-full h-full flex items-center justify-center bg-gray-100">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </div>
        @endif
        <span class="absolute top-2 left-2 bg-red-500 text-white text-[9px] font-extrabold uppercase px-2 py-0.5 rounded">Sale</span>
        <div class="wishlist-btn text-gray-300 hover:text-red-400 transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
          </svg>
        </div>
        @if(!$isQuoteOnly)
        <div class="card-actions">
          <button @click.stop="addToCart({id:{{ $p->id }}, name:'{{ addslashes($p->name) }}', price:{{ $p->price }}, img:'{{ $p->mainImage ? $p->main_image_url : '' }}'})"
            class="w-full text-[11px] font-semibold text-white text-center py-1.5 hover:opacity-80 transition">
            + Agregar al carrito
          </button>
        </div>
        @else
        <div class="card-actions">
          <a href="https://wa.me/{{ $quoteWa }}?text={{ urlencode('Hola, me interesa: '.$p->name) }}" target="_blank"
            class="block w-full text-[11px] font-semibold text-white text-center py-1.5">Cotizar</a>
        </div>
        @endif
        @if($p->stock !== null && $p->stock === 0)
        <span class="absolute bottom-0 left-0 right-0 bg-red-600/90 text-white text-[10px] font-black py-1 text-center tracking-wide">AGOTADO</span>
        @elseif($p->stock !== null && $p->stock > 0 && $p->stock <= 5)
        <span class="absolute bottom-0 left-0 right-0 bg-orange-500/90 text-white text-[10px] font-bold py-1 text-center leading-tight">âš¡ CASI AGOTADO â€” {{ $p->stock }} restantes</span>
        @endif
      </div>
      <div class="p-3">
        <p class="text-gray-400 text-[10px] mb-0.5">{{ $p->category->name ?? '' }}</p>
        <p class="text-gray-700 text-xs font-semibold truncate"><a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="hover:underline">{{ $p->name }}</a></p>
        <div class="text-yellow-400 text-[11px] my-1">â­â­â­â­â­</div>
        @if(!$isQuoteOnly || $quotePriceDisp === 'show')
        <div class="flex items-baseline gap-2">
          <span class="accent font-bold text-sm">S/ {{ number_format($p->price, 2) }}</span>
          @if($p->compare_price && $p->compare_price > $p->price)
          <span class="text-gray-300 text-xs line-through">S/ {{ number_format($p->compare_price, 2) }}</span>
          @endif
          @if($p->compare_price && $p->compare_price > $p->price)
          @php $ah = $p->compare_price - $p->price; @endphp
          <p class="text-[10px] text-green-600 font-semibold leading-none mt-1 w-full">Ahorras {{ $currency }} {{ number_format($ah,2) }}</p>
          @endif
        </div>
        @else
        <span class="text-gray-400 text-xs">Consultar precio</span>
        @endif
      </div>
    </article>
    @empty
    <p class="col-span-5 text-center text-gray-400 text-sm py-8">No hay ofertas disponibles ahora.</p>
    @endforelse
  </div>

  {{-- Tab Destacados --}}
  <div x-show="tab==='featured'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
    @forelse($featured as $p)
    <article x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }})"
      class="porto-card group relative cursor-pointer">
      <div class="relative aspect-square overflow-hidden bg-gray-50">
        @if($p->mainImage)
        <img src="{{ $p->main_image_url }}" alt="{{ $p->name }}"
          class="w-full h-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
        @else
        <div class="w-full h-full flex items-center justify-center bg-gray-100">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </div>
        @endif
        <div class="wishlist-btn text-gray-300 hover:text-red-400 transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
          </svg>
        </div>
        @if(!$isQuoteOnly)
        <div class="card-actions">
          <button @click.stop="addToCart({id:{{ $p->id }}, name:'{{ addslashes($p->name) }}', price:{{ $p->price }}, img:'{{ $p->mainImage ? $p->main_image_url : '' }}'})"
            class="w-full text-[11px] font-semibold text-white text-center py-1.5 hover:opacity-80 transition">
            + Agregar al carrito
          </button>
        </div>
        @else
        <div class="card-actions">
          <a href="https://wa.me/{{ $quoteWa }}?text={{ urlencode('Hola, me interesa: '.$p->name) }}" target="_blank"
            class="block w-full text-[11px] font-semibold text-white text-center py-1.5">Cotizar</a>
        </div>
        @endif
        @if($p->stock !== null && $p->stock === 0)
        <span class="absolute bottom-0 left-0 right-0 bg-red-600/90 text-white text-[10px] font-black py-1 text-center tracking-wide">AGOTADO</span>
        @elseif($p->stock !== null && $p->stock > 0 && $p->stock <= 5)
        <span class="absolute bottom-0 left-0 right-0 bg-orange-500/90 text-white text-[10px] font-bold py-1 text-center leading-tight">âš¡ CASI AGOTADO â€” {{ $p->stock }} restantes</span>
        @endif
      </div>
      <div class="p-3">
        <p class="text-gray-400 text-[10px] mb-0.5">{{ $p->category->name ?? '' }}</p>
        <p class="text-gray-700 text-xs font-semibold truncate"><a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="hover:underline">{{ $p->name }}</a></p>
        <div class="text-yellow-400 text-[11px] my-1">â­â­â­â­â­</div>
        @if(!$isQuoteOnly || $quotePriceDisp === 'show')
        <span class="accent font-bold text-sm">S/ {{ number_format($p->price, 2) }}</span>
        @else
        <span class="text-gray-400 text-xs">Consultar precio</span>
        @endif
      </div>
    </article>
    @empty
    <p class="col-span-5 text-center text-gray-400 text-sm py-8">No hay destacados disponibles.</p>
    @endforelse
  </div>
</section>


{{-- â•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—
     â•‘  BANNER FULL WIDTH                              â•‘
     â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<section class="py-20 px-4 bg-accent relative overflow-hidden my-4">
  <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle at 80% 50%, #fff 2px, transparent 2px);background-size:50px 50px;"></div>
  <div class="relative text-center max-w-2xl mx-auto">
    <p class="text-white font-extrabold text-3xl md:text-4xl mb-3">Ofertas Especiales</p>
    <p class="text-white/75 text-base md:text-lg mb-8">Aprovecha nuestros precios exclusivos por tiempo limitado. No te lo pierdas.</p>
    <a href="#catalogo" class="inline-block btn-outline-white font-semibold text-sm px-10 py-3.5 rounded-lg">
      Ver todas las ofertas
    </a>
  </div>
</section>


{{-- â•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—
     â•‘  BEST SELLERS                                   â•‘
     â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
@if($featured->count() > 0)
<section class="max-w-7xl mx-auto px-4 py-8">
  <div class="flex items-center justify-between mb-6">
    <h2 class="font-bold text-gray-800 text-xl">MÃ¡s vendidos</h2>
    <a href="#catalogo" class="text-xs accent font-semibold hover:opacity-70 transition">Ver todo â†’</a>
  </div>
  <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
    @foreach($featured->take(5) as $p)
    <article class="porto-card group relative cursor-pointer">
      <div class="relative aspect-square overflow-hidden bg-gray-50">
        @if($p->mainImage)
        <img src="{{ $p->main_image_url }}" alt="{{ $p->name }}"
          class="w-full h-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
        @else
        <div class="w-full h-full flex items-center justify-center bg-gray-100">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </div>
        @endif
        @if($p->compare_price && $p->compare_price > $p->price)
        <span class="absolute top-2 left-2 bg-red-500 text-white text-[9px] font-extrabold uppercase px-2 py-0.5 rounded">Sale</span>
        @endif
        <div class="wishlist-btn text-gray-300 hover:text-red-400 transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
          </svg>
        </div>
        @if(!$isQuoteOnly)
        <div class="card-actions">
          <button @click.stop="addToCart({id:{{ $p->id }}, name:'{{ addslashes($p->name) }}', price:{{ $p->price }}, img:'{{ $p->mainImage ? $p->main_image_url : '' }}'})"
            class="w-full text-[11px] font-semibold text-white text-center py-1.5 hover:opacity-80 transition">
            + Agregar al carrito
          </button>
        </div>
        @else
        <div class="card-actions">
          <a href="https://wa.me/{{ $quoteWa }}?text={{ urlencode('Hola, me interesa: '.$p->name) }}" target="_blank"
            class="block w-full text-[11px] font-semibold text-white text-center py-1.5">Cotizar</a>
        </div>
        @endif
        @if($p->stock !== null && $p->stock === 0)
        <span class="absolute bottom-0 left-0 right-0 bg-red-600/90 text-white text-[10px] font-black py-1 text-center tracking-wide">AGOTADO</span>
        @elseif($p->stock !== null && $p->stock > 0 && $p->stock <= 5)
        <span class="absolute bottom-0 left-0 right-0 bg-orange-500/90 text-white text-[10px] font-bold py-1 text-center leading-tight">âš¡ CASI AGOTADO â€” {{ $p->stock }} restantes</span>
        @endif
      </div>
      <div class="p-3">
        <p class="text-gray-400 text-[10px] mb-0.5">{{ $p->category->name ?? '' }}</p>
        <p class="text-gray-700 text-xs font-semibold truncate"><a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="hover:underline">{{ $p->name }}</a></p>
        <div class="text-yellow-400 text-[11px] my-1">â­â­â­â­â­</div>
        @if(!$isQuoteOnly || $quotePriceDisp === 'show')
        <div class="flex items-baseline gap-2">
          <span class="accent font-bold text-sm">S/ {{ number_format($p->price, 2) }}</span>
          @if($p->compare_price && $p->compare_price > $p->price)
          <span class="text-gray-300 text-xs line-through">S/ {{ number_format($p->compare_price, 2) }}</span>
          @endif
          @if($p->compare_price && $p->compare_price > $p->price)
          @php $ah = $p->compare_price - $p->price; @endphp
          <p class="text-[10px] text-green-600 font-semibold leading-none mt-1 w-full">Ahorras {{ $currency }} {{ number_format($ah,2) }}</p>
          @endif
        </div>
        @else
        <span class="text-gray-400 text-xs">Consultar precio</span>
        @endif
      </div>
    </article>
    @endforeach
  </div>
</section>
@endif


{{-- â•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—
     â•‘  TRUST ROW                                      â•‘
     â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<section class="bg-white border-y border-gray-100 py-10 my-4">
  <div class="max-w-5xl mx-auto px-4 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
    <div class="flex flex-col items-center gap-2.5">
      <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center text-2xl">ðŸšš</div>
      <p class="text-sm font-semibold text-gray-700">EnvÃ­o rÃ¡pido</p>
      <p class="text-xs text-gray-400">Entrega a domicilio</p>
    </div>
    <div class="flex flex-col items-center gap-2.5">
      <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center text-2xl">ðŸ”’</div>
      <p class="text-sm font-semibold text-gray-700">Pago seguro</p>
      <p class="text-xs text-gray-400">Datos protegidos SSL</p>
    </div>
    <div class="flex flex-col items-center gap-2.5">
      <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center text-2xl">ðŸ”„</div>
      <p class="text-sm font-semibold text-gray-700">Devoluciones</p>
      <p class="text-xs text-gray-400">Sin complicaciones</p>
    </div>
    <div class="flex flex-col items-center gap-2.5">
      <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center text-2xl">ðŸŽ§</div>
      <p class="text-sm font-semibold text-gray-700">Soporte 24/7</p>
      <p class="text-xs text-gray-400">Siempre disponibles</p>
    </div>
  </div>
</section>


{{-- â•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—
     â•‘  CATÃLOGO COMPLETO con SIDEBAR                  â•‘
     â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<section id="catalogo" class="max-w-7xl mx-auto px-4 py-12">
  <div class="flex gap-6">

    {{-- Sidebar --}}
    <aside class="hidden md:block w-60 flex-shrink-0 space-y-4">
      <!-- CategorÃ­as -->
      <div class="bg-white border border-gray-100 rounded-xl overflow-hidden shadow-sm">
        <div class="bg-accent text-white px-4 py-3">
          <p class="font-semibold text-sm">CategorÃ­as</p>
        </div>
        <ul>
          <li>
            <button @click="filterCat=''"
              :class="filterCat===''?'bg-gray-50 font-semibold accent border-l-2 border-accent':'text-gray-600 hover:bg-gray-50'"
              class="block w-full text-left px-4 py-2.5 text-sm border-b border-gray-50 transition">
              Todos los productos
            </button>
          </li>
          @foreach($categories as $cat)
          <li>
            <button @click="filterCat='{{ $cat->id }}'"
              :class="filterCat==='{{ $cat->id }}'?'bg-gray-50 font-semibold accent border-l-2 border-accent':'text-gray-600 hover:bg-gray-50'"
              class="flex w-full text-left px-4 py-2.5 text-sm border-b border-gray-50 transition items-center justify-between">
              <span>{{ $cat->name }}</span>
              <span class="text-gray-300 text-[10px]">{{ $cat->products->count() }}</span>
            </button>
          </li>
          @endforeach
        </ul>
      </div>

      <!-- Filtro precio -->
      <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
        <p class="text-xs font-semibold text-gray-500 mb-3 uppercase tracking-widest">Precio</p>
        <div class="space-y-2">
          <label class="flex items-center gap-2.5 text-xs text-gray-500 cursor-pointer hover:text-gray-700 transition">
            <input type="radio" x-model="priceFilter" value="" class="accent-[var(--c)]"> Todos los precios
          </label>
          <label class="flex items-center gap-2.5 text-xs text-gray-500 cursor-pointer hover:text-gray-700 transition">
            <input type="radio" x-model="priceFilter" value="0-50" class="accent-[var(--c)]"> S/ 0 â€“ S/ 50
          </label>
          <label class="flex items-center gap-2.5 text-xs text-gray-500 cursor-pointer hover:text-gray-700 transition">
            <input type="radio" x-model="priceFilter" value="50-100" class="accent-[var(--c)]"> S/ 50 â€“ S/ 100
          </label>
          <label class="flex items-center gap-2.5 text-xs text-gray-500 cursor-pointer hover:text-gray-700 transition">
            <input type="radio" x-model="priceFilter" value="100-250" class="accent-[var(--c)]"> S/ 100 â€“ S/ 250
          </label>
          <label class="flex items-center gap-2.5 text-xs text-gray-500 cursor-pointer hover:text-gray-700 transition">
            <input type="radio" x-model="priceFilter" value="250+" class="accent-[var(--c)]"> S/ 250+
          </label>
        </div>
        <!-- Rango personalizado -->
        <div class="pt-2 border-t border-gray-100 mt-2">
          <p class="text-xs text-gray-400 mb-1.5">Rango personalizado</p>
          <div class="flex items-center gap-1.5">
            <input type="number" x-model.number="priceMin" placeholder="Min" min="0"
                   class="w-full border border-gray-200 rounded-lg px-2 py-1 text-xs outline-none focus:border-gray-400 bg-gray-50 transition">
            <span class="text-gray-300 text-xs">â€”</span>
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

      <!-- Filtro oferta -->
      <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
        <p class="text-xs font-semibold text-gray-500 mb-3 uppercase tracking-widest">Filtros</p>
        <label class="flex items-center gap-2.5 text-xs text-gray-500 cursor-pointer hover:text-gray-700 transition">
          <input type="checkbox" x-model="onSaleFilter" class="rounded accent-[var(--c)]">
          Solo productos en oferta
        </label>
      </div>
    </aside>

    {{-- Grid principal --}}
    <div class="flex-1 min-w-0">
      <!-- Barra top -->
      <div class="flex flex-wrap items-center justify-between gap-3 mb-5 bg-white border border-gray-100 rounded-xl px-4 py-3 shadow-sm sticky top-16 z-20 bg-white/95 backdrop-blur-sm">
        <div class="flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
          </svg>
          <p class="text-xs text-gray-500 font-medium">CatÃ¡logo completo</p>
        </div>
        <div class="flex items-center gap-2">
          <div class="relative" @click.outside="searchOpen = false">
          <input type="text" x-model="search" placeholder="Buscar en catÃ¡logo..."
                 @input="searchOpen = search.trim().length >= 2; searchIdx = -1; if(search.trim().length >= 2) _scrollToCatalog()"
                 @keydown.arrow-down.prevent="if(suggestions.length){searchIdx=(searchIdx+1)%suggestions.length}"
                 @keydown.arrow-up.prevent="if(suggestions.length){searchIdx=(searchIdx-1+suggestions.length)%suggestions.length}"
                 @keydown.enter.prevent="if(searchIdx>=0){selectSuggestion(suggestions[searchIdx])}else{searchOpen=false;_scrollToCatalog()}"
                 @keydown.escape="searchOpen=false;searchIdx=-1"
                 class="border border-gray-200 rounded-lg py-1.5 px-3 text-xs focus:outline-none focus:border-gray-400 w-40 md:w-52 transition">
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
                      Ver todos los resultados â†’
                  </button>
              </div>
          </div>
          </div>
          <select x-model="sortBy" class="border border-gray-200 rounded-lg py-1.5 px-3 text-xs text-gray-500 focus:outline-none focus:border-gray-400 transition bg-white">
            <option value="default">Ordenar por...</option>
            <option value="price_asc">Precio: menor a mayor</option>
            <option value="price_desc">Precio: mayor a menor</option>
            <option value="newest">MÃ¡s recientes</option>
            <option value="name_az">Nombre Aâ†’Z</option>
          </select>
          <!-- Filtros mÃ³vil toggle -->
          <button @click="filterOpen=true" class="xl:hidden border border-gray-200 rounded-lg p-1.5 text-gray-500 hover:bg-gray-50 transition relative" title="Filtros">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
            <span x-show="priceFilter!=='' || onSaleFilter"
                  class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full text-[10px] font-black flex items-center justify-center text-white"
                  style="background:var(--c)"
                  x-text="(priceFilter!==''?1:0)+(onSaleFilter?1:0)"></span>
          </button>
        </div>
      </div>

      <!-- Contador de resultados -->
      <p class="text-xs text-gray-400 mb-4" x-show="filterCat!==''||search!==''||priceFilter!==''||onSaleFilter">
        <span x-text="visibleCount"></span> producto<span x-show="visibleCount!==1">s</span> encontrado<span x-show="visibleCount!==1">s</span>
      </p>

      <!-- Productos por categorÃ­a -->
      @foreach($categories as $cat)
      @if($cat->products->count() > 0)
      <div x-show="filterCat==='' || filterCat==='{{ $cat->id }}'">
        <div class="flex items-center gap-3 mb-4 mt-8">
          <div class="w-1 h-5 bg-accent rounded-full flex-shrink-0"></div>
          <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">{{ $cat->name }}</h3>
          <div class="flex-1 h-px bg-gray-100"></div>
          <span class="text-xs text-gray-300">{{ $cat->products->count() }} productos</span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-4" data-products-grid>
          @foreach($cat->products as $p)
          @php
          $qvData = ['id'=>$p->id,'name'=>$p->name,'img'=>$p->mainImage?$p->main_image_url:'','price'=>(float)$p->price,'cp'=>$p->compare_price?(float)$p->compare_price:null,'desc'=>\Str::limit(strip_tags($p->description??''),120),'url'=>route('public.product',[$project->slug,$p->id]),'stock'=>$p->stock];
          @endphp
          <article x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }}) && ({{ $loop->index }} < 8 || expandedCats['{{ $cat->id }}'])"
            class="porto-card group relative cursor-pointer"
            data-price="{{ $p->price }}"
            data-ts="{{ $p->created_at ? $p->created_at->timestamp : 0 }}"
            data-idx="{{ $loop->index }}"
            data-qv='@json($qvData)'>
            <div class="relative aspect-square overflow-hidden bg-gray-50">
              @if($p->mainImage)
              <img src="{{ $p->main_image_url }}" alt="{{ $p->name }}"
                class="w-full h-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
              @else
              <div class="w-full h-full flex items-center justify-center bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
              </div>
              @endif
              @if($p->compare_price && $p->compare_price > $p->price)
              <span class="absolute top-2 left-2 bg-red-500 text-white text-[9px] font-extrabold uppercase px-2 py-0.5 rounded">Sale</span>
              @endif
              <div class="wishlist-btn text-gray-300 hover:text-red-400 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
              </div>
              @if(!$isQuoteOnly)
              <div class="card-actions">
                <button @click.stop="addToCart({id:{{ $p->id }}, name:'{{ addslashes($p->name) }}', price:{{ $p->price }}, img:'{{ $p->mainImage ? $p->main_image_url : '' }}'})"
                  class="w-full text-[11px] font-semibold text-white text-center py-1.5 hover:opacity-80 transition">
                  + Agregar al carrito
                </button>
              </div>
              @else
              <div class="card-actions">
                <a href="https://wa.me/{{ $quoteWa }}?text={{ urlencode('Hola, me interesa: '.$p->name) }}" target="_blank"
                  class="block w-full text-[11px] font-semibold text-white text-center py-1.5">Cotizar</a>
              </div>
              @endif
              @if($p->stock !== null && $p->stock === 0)
              <span class="absolute bottom-2 left-2 bg-red-600 text-white text-[9px] font-extrabold uppercase px-2 py-0.5 rounded">Agotado</span>
              @elseif($p->stock !== null && $p->stock > 0 && $p->stock <= 5)
              <span class="absolute bottom-2 left-2 bg-amber-500 text-white text-[9px] font-extrabold uppercase px-2 py-0.5 rounded">Ãšltimas {{ $p->stock }}</span>
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
            </div>
            <div class="p-3">
              <p class="text-gray-400 text-[10px] mb-0.5 truncate">{{ $p->category->name ?? '' }}</p>
              <p class="text-gray-700 text-xs font-semibold truncate"><a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="hover:underline">{{ $p->name }}</a></p>
              @if(isset($productRatings) && isset($productRatings[$p->id]))
              <div class="flex items-center gap-1 mb-1">
                <span class="text-amber-400 text-xs">{{ str_repeat('â˜…', floor($productRatings[$p->id]->avg_rating)) }}{{ str_repeat('â˜†', 5 - floor($productRatings[$p->id]->avg_rating)) }}</span>
                <span class="text-[10px] text-gray-400">({{ $productRatings[$p->id]->rating_count }})</span>
              </div>
              @endif
              @if(!$isQuoteOnly || $quotePriceDisp === 'show')
              <div class="flex items-baseline gap-2">
                <span class="accent font-bold text-sm">S/ {{ number_format($p->price, 2) }}</span>
                @if($p->compare_price && $p->compare_price > $p->price)
                <span class="text-gray-300 text-xs line-through">S/ {{ number_format($p->compare_price, 2) }}</span>
                @endif
                @if($p->compare_price && $p->compare_price > $p->price)
                @php $ah = $p->compare_price - $p->price; @endphp
                <p class="text-[10px] text-green-600 font-semibold leading-none mt-1 w-full">Ahorras {{ $currency }} {{ number_format($ah,2) }}</p>
                @endif
              </div>
              @else
              <span class="text-gray-400 text-xs">Consultar precio</span>
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

      {{-- Sin resultados --}}
      <div x-show="noResults" x-cloak class="py-16 text-center">
        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <p class="text-gray-500 font-medium">Sin resultados</p>
        <p class="text-sm text-gray-400 mt-1">Intenta con otro tÃ©rmino de bÃºsqueda</p>
        <button @click="search=''; filterCat=''; priceFilter=''; onSaleFilter=false"
                class="mt-4 text-sm text-indigo-600 hover:text-indigo-800 underline">
          Limpiar filtros
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
  </div>
</section>


{{-- â•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—
     â•‘  FOOTER                                         â•‘
     â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<footer class="bg-[#1a1a2e] text-white pt-16 pb-8 mt-8">
  <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-10 mb-12">
    <!-- Col 1: Logo + desc -->
    <div>
      @if($project->logo_url)
      <img src="{{ asset('storage/'.$project->logo_url) }}" alt="{{ $project->name }}"
        class="h-10 object-contain mb-5 brightness-0 invert opacity-90">
      @else
      <p class="font-extrabold text-xl mb-5">{{ $project->name }}</p>
      @endif
      <p class="text-white/45 text-sm leading-relaxed">{{ $seoDesc }}</p>
      <!-- MÃ©todos de pago -->
      <div class="flex flex-wrap gap-2 mt-5">
        <span class="bg-white/8 text-white/60 text-[10px] px-2.5 py-1 rounded border border-white/10">Visa</span>
        <span class="bg-white/8 text-white/60 text-[10px] px-2.5 py-1 rounded border border-white/10">Mastercard</span>
        <span class="bg-white/8 text-white/60 text-[10px] px-2.5 py-1 rounded border border-white/10">Yape</span>
        <span class="bg-white/8 text-white/60 text-[10px] px-2.5 py-1 rounded border border-white/10">Plin</span>
        <span class="bg-white/8 text-white/60 text-[10px] px-2.5 py-1 rounded border border-white/10">PagoEfectivo</span>
      </div>
    </div>

    <!-- Col 2: Links -->
    <div>
      <p class="text-white/30 text-xs uppercase tracking-widest mb-5 font-semibold">NavegaciÃ³n</p>
      <ul class="space-y-2.5">
        <li>
          <button @click="filterCat='';document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
            class="text-white/55 hover:text-white text-sm transition">Todos los productos</button>
        </li>
        <li>
          <button @click="document.getElementById('novedades').scrollIntoView({behavior:'smooth'})"
            class="text-white/55 hover:text-white text-sm transition">Novedades</button>
        </li>
        <li>
          <button @click="drawerOpen=true;drawerStep=1"
            class="text-white/55 hover:text-white text-sm transition">Mi carrito</button>
        </li>
        @if($project->whatsapp)
        <li>
          <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}" target="_blank"
            class="text-white/55 hover:text-white text-sm transition">Contactar por WhatsApp</a>
        </li>
        @endif
      </ul>
    </div>

    <!-- Col 3: CategorÃ­as -->
    <div>
      <p class="text-white/30 text-xs uppercase tracking-widest mb-5 font-semibold">CategorÃ­as</p>
      <ul class="space-y-2.5">
        @foreach($categories as $cat)
        <li>
          <button @click="filterCat='{{ $cat->id }}';document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
            class="text-white/55 hover:text-white text-sm transition">{{ $cat->name }}</button>
        </li>
        @endforeach
      </ul>
    </div>

    <!-- Col 4: Contacto -->
    <div>
      <p class="text-white/30 text-xs uppercase tracking-widest mb-5 font-semibold">Contacto</p>
      <ul class="space-y-3 text-sm text-white/50">
        @if($project->phone)
        <li class="flex items-start gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 flex-shrink-0 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
          </svg>
          {{ $project->phone }}
        </li>
        @endif
        @if($project->address)
        <li class="flex items-start gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 flex-shrink-0 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          {{ $project->address }}
        </li>
        @endif
        @if($settings['facebook_url'] ?? '')
        <li>
          <a href="{{ $settings['facebook_url'] }}" target="_blank" rel="noopener" class="hover:text-white transition flex items-center gap-2">
            <svg class="h-4 w-4 text-white/30" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            Facebook
          </a>
        </li>
        @endif
        @if($settings['instagram_url'] ?? '')
        <li>
          <a href="{{ $settings['instagram_url'] }}" target="_blank" rel="noopener" class="hover:text-white transition flex items-center gap-2">
            <svg class="h-4 w-4 text-white/30" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
            Instagram
          </a>
        </li>
        @endif
      </ul>
    </div>
  </div>

  <!-- Footer bottom -->
  <div class="border-t border-white/8 pt-6">
    <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-3">
      <p class="text-white/20 text-xs">Â© {{ date('Y') }} {{ $project->name }} â€” Todos los derechos reservados</p>
      <div class="flex items-center gap-2 text-white/20 text-xs">
        <span>Visa</span><span>|</span><span>Mastercard</span><span>|</span>
        <span>Yape</span><span>|</span><span>PagoEfectivo</span>
      </div>
    </div>
  </div>
</footer>


{{-- BotÃ³n flotante WhatsApp --}}
@if($project->whatsapp)
<a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}?text={{ urlencode($settings['whatsapp_msg'] ?? 'Hola, quisiera mÃ¡s informaciÃ³n sobre sus productos') }}"
  target="_blank" rel="noopener"
  class="fixed bottom-6 right-6 z-50 w-14 h-14 rounded-full bg-[#25D366] flex items-center justify-center shadow-2xl hover:scale-110 transition-transform">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-white" fill="currentColor" viewBox="0 0 24 24">
    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
  </svg>
</a>
@endif


<script>
function store() {
  return {
    cart: [], search: '', filterCat: '',
    filterParent: '', priceFilter: '', onSaleFilter: false, sortBy: 'default', noResults: false,
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
    drawerOpen: false, drawerStep: 1,
    toastShow: false, toastMsg: '', toastTimer: null,
    form: { name:'', phone:'', email:'', notes:'', address:'' },
    orderLoading: false, orderError: '', orderId: 0, orderTotal: 0,
    orderSent: false, payReference: '', payError: '', payLoading: false,
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
    _cartKey: 'bixo_cart_{{ $project->id }}',
    _formKey: 'bixo_form_{{ $project->id }}',
    get cartTotal() { return this.cart.reduce((s,i)=>s+(i.price*i.qty),0); },
    get cartCount()  { return this.cart.reduce((s,i)=>s+i.qty,0); },
    init() {
      try {
          const _rv = JSON.parse(localStorage.getItem('rv_{{ $project->slug }}') || '[]');
          this.recentlyViewed = _rv.filter(x => x && x.id);
      } catch(e) {}
      try {
        const c = localStorage.getItem(this._cartKey);
        const f = localStorage.getItem(this._formKey);
        if(c) this.cart = JSON.parse(c);
        if(f) Object.assign(this.form, JSON.parse(f));
      } catch(e) {}
      this.$watch('cart', v => { try { localStorage.setItem(this._cartKey, JSON.stringify(v)); } catch(e) {} });
      this.$watch('form', v => { try { localStorage.setItem(this._formKey, JSON.stringify(v)); } catch(e) {} });
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
      // Manejar retorno de pasarela de pago
      const urlParams   = new URLSearchParams(window.location.search);
      const payStatus   = urlParams.get('payment');
      const payOrderId  = urlParams.get('order');
      if (payStatus && payOrderId) {
        this.orderId = parseInt(payOrderId) || 0;
        if (payStatus === 'success' || payStatus === 'approved') {
          this.orderSent  = true;
          this.drawerOpen = true;
          this.drawerStep = 3;
          this.payReference = urlParams.get('payment_id') || 'mp-ok';
        } else if (payStatus === 'failure') {
          this.payError   = 'El pago fue rechazado. Intenta con otro mÃ©todo.';
          this.drawerOpen = true;
          this.drawerStep = 3;
        }
        window.history.replaceState({}, document.title, window.location.pathname);
      }
    },
    addToCart(item) {
      const existing = this.cart.find(i => i.id === item.id);
      if (existing) { existing.qty++; }
      else { this.cart.push({...item, qty:1}); }
      this.toastMsg = 'âœ“ ' + item.name + ' agregado';
      this.toastShow = true;
      clearTimeout(this.toastTimer);
      this.toastTimer = setTimeout(() => { this.toastShow = false; }, 2000);
    },
    removeFromCart(idx) { this.cart.splice(idx, 1); },
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
      if (this.search && !name.includes(this.search.toLowerCase())) return false;
      if (this.onSaleFilter && !(comparePrice && comparePrice > price)) return false;
      if (this.priceFilter) {
        const parts = this.priceFilter.split('-');
        const min   = parseFloat(parts[0]);
        const max   = this.priceFilter.includes('+') ? 999999 : parseFloat(parts[1]);
        if (price < min || price > max) return false;
      }
      return true;
    },
    async submitOrder() {
      if (!this.form.name || !this.form.phone) { this.orderError = 'Completa nombre y telÃ©fono.'; return; }
      this.orderLoading = true;
      this.orderError   = '';
      this.drawerStep   = 3; // mostrar spinner en paso 3 mientras procesa
      const items = this.cart.map(i => ({product_id:i.id, name:i.name, price:i.price, quantity:i.qty}));
      try {
        const res  = await fetch('/{{ $project->slug }}/order', {
          method:  'POST',
          headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}'},
          body:    JSON.stringify({
            client_name:  this.form.name,
            client_phone: this.form.phone,
            client_email: this.form.email,
            notes:        this.form.notes,
            coupon_code:  this.couponApplied?this.couponApplied.code:null,
            delivery_address: this.form.address||null,
            shipping_cost: this.effectiveShipping>0?this.effectiveShipping:null,
            items
          })
        });
        const data = await res.json();
        if (data.ok) {
          this.orderId      = data.order_id;
          this.orderTotal   = data.total;
          this.orderSent    = false;
          this.payReference = '';
          this.payError     = '';
          this.drawerStep   = 3;
        } else {
          this.orderError  = data.message || 'No se pudo enviar. IntÃ©ntalo de nuevo.';
          this.drawerStep  = 2;
        }
      } catch(e) {
        this.orderError = 'Error de conexiÃ³n. Verifica tu internet e intenta de nuevo.';
        this.drawerStep = 2;
      }
      this.orderLoading = false;
    },
  };
}
</script>

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
       class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
    <button @click="qvOpen=false" class="absolute top-3 right-3 z-10 p-1.5 rounded-full bg-white/80 hover:bg-gray-100">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    <div class="flex flex-col sm:flex-row">
      <div class="sm:w-1/2 bg-gray-50 flex items-center justify-center p-6">
        <img :src="qv?.img" :alt="qv?.name" class="w-full max-h-64 object-contain rounded-lg">
      </div>
      <div class="sm:w-1/2 p-6 flex flex-col gap-3">
        <h3 class="font-semibold text-lg leading-tight" x-text="qv?.name"></h3>
        <p class="text-sm text-gray-500 leading-relaxed" x-text="qv?.desc"></p>
        <div class="flex items-baseline gap-2">
          <span class="text-xl font-bold" style="color:var(--c)" x-text="'$'+Number(qv?.price).toFixed(2)"></span>
          <span x-show="qv?.cp && qv?.cp > qv?.price" class="text-sm text-gray-400 line-through" x-text="'$'+Number(qv?.cp).toFixed(2)"></span>
        </div>
        <p x-show="qv?.stock !== null && qv?.stock <= 5 && qv?.stock > 0" class="text-xs text-amber-600 font-medium">Â¡Solo quedan <span x-text="qv?.stock"></span> en stock!</p>
        <p x-show="qv?.stock === 0" class="text-xs text-red-500 font-medium">Agotado</p>
        <button @click="addToCart({id:qv.id,name:qv.name,price:qv.price,img:qv.img}); qvOpen=false"
                :disabled="qv?.stock === 0"
                class="w-full btn-p py-2.5 rounded-xl font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed">
          Agregar al carrito
        </button>
        <a :href="qv?.url" class="text-center text-sm underline text-gray-500 hover:text-gray-700">Ver producto completo</a>
      </div>
    </div>
  </div>
</div>

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


