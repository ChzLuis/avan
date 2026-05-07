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
$primaryColor     = $settings['primary_color'] ?? '#16a34a';
$isQuoteOnly      = ($settings['store_mode'] ?? 'direct') === 'quote_only';
$shippingEnabled  = ($settings['shipping_enabled']  ?? '0') === '1';
$shippingCost     = (float)($settings['shipping_cost']      ?? 0);
$shippingFreeFrom = (float)($settings['shipping_free_from'] ?? 0);
$requireAddress   = ($settings['require_address']   ?? '0') === '1';
$quotePriceDisp   = $settings['quote_price_display'] ?? 'show';
$quoteWaRaw       = preg_replace('/\D/', '', $settings['quote_whatsapp'] ?? $project->whatsapp ?? '');
$quoteWaCountry   = $settings['quote_whatsapp_country'] ?? '51';
$quoteWa          = $quoteWaRaw ? (str_starts_with($quoteWaRaw, $quoteWaCountry) ? $quoteWaRaw : $quoteWaCountry.$quoteWaRaw) : '';
$quoteWaMsg       = $settings['quote_wa_msg'] ?? 'Hola, me interesa cotizar los siguientes productos:';
$canonicalUrl     = url('/' . $project->slug);
$seoTitle         = ($settings['seo_title'] ?? null) ?: ($project->name . ' â€” CatÃ¡logo Online');
$seoDesc          = ($settings['seo_description'] ?? null) ?: ($project->description ?? 'Explora nuestros productos frescos y naturales.');
$heroBg           = $settings['hero_bg_color'] ?? '#14532d';
$heroTitle        = $settings['hero_title'] ?? 'Lo mejor de la naturaleza';
$heroSub          = $settings['hero_subtitle'] ?? 'Productos frescos y naturales para tu bienestar';
$heroBadge        = $settings['hero_badge'] ?? '100% Natural';
$b1Title          = $settings['banner1_title'] ?? 'Descubre nuestros productos';
$b1Sub            = $settings['banner1_sub'] ?? 'Calidad natural directamente a tu hogar';
$b2Title          = $settings['banner2_title'] ?? 'Ofertas de Temporada';
$b2Sub            = $settings['banner2_sub'] ?? '';
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

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@if($culqiEnabled && $culqiPublicKey)
<script src="https://checkout.culqi.com/js/v4"></script>
@endif

@php
  $secondaryColor = $settings['secondary_color'] ?? '#4ade80';
  $fontTitle  = $settings['font_title'] ?? $settings['font'] ?? 'Nunito';
  $fontBody   = $settings['font_body']  ?? $settings['font'] ?? 'Inter';
  $borderRadius = ['sharp'=>'0px','rounded'=>'8px','pill'=>'50px'][$settings['border_radius'] ?? 'pill'] ?? '50px';
  $faviconRaw   = $settings['favicon_url'] ?? '';
  $faviconUrl   = $faviconRaw ? (str_starts_with($faviconRaw,'http') ? $faviconRaw : asset('storage/'.$faviconRaw)) : '';
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
* { font-family: var(--font-body); }
[x-cloak] { display: none !important; }
body { background: #f9fffe; }

/* â”€â”€ Utilidades color verde â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.gc    { color: var(--c); }
.bg-gc { background: var(--c); }
.btn-gc { background: var(--c); color: #fff; transition: filter .2s; }
.btn-gc:hover { filter: brightness(.88); }
.btn-outline-gc { border: 2px solid var(--c); color: var(--c); background: transparent; transition: all .2s; }
.btn-outline-gc:hover { background: var(--c); color: #fff; }

/* â”€â”€ Fresh cards â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.fresh-card {
  background: #fff;
  border: 2px solid #dcfce7;
  border-radius: 14px;
  overflow: hidden;
  transition: all .25s;
}
.fresh-card:hover {
  border-color: var(--c);
  box-shadow: 0 8px 28px rgba(22,163,74,.13);
  transform: translateY(-3px);
}
.fresh-card .fc-img {
  aspect-ratio: 1/1;
  overflow: hidden;
  background: #f0fdf4;
}
.fresh-card .fc-img img { width:100%;height:100%;object-fit:cover;transition:transform .35s; }
.fresh-card:hover .fc-img img { transform: scale(1.06); }

/* â”€â”€ Sidebar categorÃ­as â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.scat-btn {
  display: flex; align-items: center; gap: 8px;
  width: 100%; text-align: left;
  padding: 8px 12px; border-radius: 10px;
  font-size: .875rem; border: none; background: transparent;
  color: #166534; cursor: pointer; transition: all .15s;
}
.scat-btn:hover { background: #dcfce7; }
.scat-btn.active { background: var(--c); color: #fff !important; }

/* â”€â”€ Ãcono categorÃ­a scroll â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.cat-circle {
  width: 60px; height: 60px; border-radius: 50%;
  background: #dcfce7;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; font-weight: 800; color: #166534;
  cursor: pointer; transition: all .2s; flex-shrink: 0;
}
.cat-circle:hover, .cat-circle.active { background: var(--c); color: #fff; }

/* â”€â”€ Drawer â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.drawer-overlay {
  position: fixed; inset: 0; z-index: 50;
  background: rgba(0,0,0,.4); backdrop-filter: blur(3px);
}
.drawer-panel {
  position: fixed; right: 0; top: 0; bottom: 0; z-index: 51;
  width: 100%; max-width: 420px; background: #fff;
  display: flex; flex-direction: column;
  box-shadow: -6px 0 32px rgba(0,0,0,.12);
  border-left: 3px solid #dcfce7;
  border-radius: 16px 0 0 16px;
}
@media(max-width:640px){ .drawer-panel{ max-width:100%; border-radius:0; } }

.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

html { scroll-behavior: smooth; }
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

{{-- â”€â”€â”€ DRAWER â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
<div x-show="drawerOpen" class="drawer-overlay" @click="drawerOpen=false" x-cloak></div>

<div x-show="drawerOpen" x-cloak
     class="drawer-panel"
     role="dialog" aria-label="{{ $isQuoteOnly ? 'Mi cotizaciÃ³n' : 'Mi pedido' }}"
     x-transition:enter="transition ease-out duration-300 transform"
     x-transition:enter-start="translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in duration-200 transform"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="translate-x-full">

  {{-- Header drawer --}}
  <div class="flex items-center justify-between px-5 py-4 border-b border-green-100 flex-shrink-0">
    <div class="flex items-center gap-2">
      <button x-show="drawerStep > 1 && !orderSent" @click="drawerStep > 1 ? drawerStep-- : null"
              class="p-1.5 hover:bg-green-50 rounded-lg transition mr-1" aria-label="Volver">
        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
      </button>
      <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        @if($isQuoteOnly)
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        @else
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
        @endif
      </svg>
      <h2 class="font-bold text-green-900 text-base"
          x-text="drawerStep===1 ? '{{ $isQuoteOnly ? 'Mi cotizaciÃ³n' : 'Tu canasta' }}' : (drawerStep===2 ? 'Confirmar datos' : '{{ $isQuoteOnly ? 'Solicitud enviada' : 'Forma de pago' }}')">
      </h2>
      <span x-show="cart.length && drawerStep===1"
            class="bg-gc text-white text-[10px] px-2 py-0.5 rounded-full font-bold" x-text="cart.length"></span>
    </div>
    <button @click="drawerOpen=false" class="p-2 hover:bg-green-50 rounded-xl transition" aria-label="Cerrar">
      <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  </div>

  {{-- â”€â”€ PASO 1: Lista de items â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
  <div x-show="drawerStep===1" class="flex flex-col flex-1 overflow-hidden">
    <div class="flex-1 overflow-y-auto scrollbar-hide px-5 py-4 space-y-3">
      <template x-if="cart.length===0">
        <div class="text-center py-16">
          <div class="text-6xl mb-4">ðŸŒ¿</div>
          <p class="font-semibold text-green-500 mb-1">{{ $isQuoteOnly ? 'Tu cotizaciÃ³n estÃ¡ vacÃ­a' : 'Tu canasta estÃ¡ vacÃ­a' }}</p>
          <p class="text-xs text-green-300">Agrega productos para comenzar</p>
        </div>
      </template>
      <template x-for="(item, idx) in cart" :key="item.id">
        <div class="flex items-center gap-3 bg-green-50 rounded-2xl p-3">
          <div class="w-14 h-14 rounded-xl overflow-hidden flex-shrink-0 bg-green-100">
            <img :src="item.img" x-show="item.img" class="w-full h-full object-cover" :alt="item.name">
            <div x-show="!item.img" class="w-full h-full flex items-center justify-center text-2xl">ðŸŒ¿</div>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-green-800 leading-snug line-clamp-2" x-text="item.name"></p>
            @if(!$isQuoteOnly || $quotePriceDisp==='show')
            <p class="gc font-bold text-sm mt-0.5" x-text="'S/ ' + (item.price * item.qty).toFixed(2)"></p>
            @endif
          </div>
          <div class="flex items-center gap-1 flex-shrink-0">
            <button @click="item.qty>1 ? item.qty-- : cart.splice(idx,1)"
                    class="w-8 h-8 rounded-xl border-2 border-green-200 hover:border-red-300 hover:bg-red-50 text-green-600 hover:text-red-500 font-bold text-sm transition flex items-center justify-center">
              <span x-text="item.qty > 1 ? 'âˆ’' : 'Ã—'"></span>
            </button>
            <span class="w-7 text-center text-sm font-bold text-green-800" x-text="item.qty"></span>
            <button @click="item.qty++"
                    class="w-8 h-8 rounded-xl btn-gc text-white font-bold text-sm transition flex items-center justify-center">+</button>
          </div>
        </div>
      </template>
    </div>

    <div x-show="cart.length > 0" class="border-t border-green-100 px-5 py-4 space-y-3 flex-shrink-0">
      @if(!$isQuoteOnly || $quotePriceDisp==='show')
      <div class="flex justify-between items-center">
        <span class="text-sm text-green-500"><span x-text="cart.reduce((s,i)=>s+i.qty,0)"></span> producto(s)</span>
        <span class="font-bold text-lg gc" x-text="'S/ ' + cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></span>
      </div>
      @else
      <div class="flex justify-between items-center">
        <span class="text-sm text-green-500"><span x-text="cart.reduce((s,i)=>s+i.qty,0)"></span> producto(s)</span>
        <span class="text-xs text-green-300 italic">Precios a cotizar</span>
      </div>
      @endif
      <button @click="drawerStep=2; orderError=''"
              class="w-full btn-gc py-3.5 rounded-full font-bold text-sm flex items-center justify-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
        </svg>
        Continuar
      </button>
    </div>
  </div>

  {{-- â”€â”€ PASO 2: Formulario datos â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
  <div x-show="drawerStep===2" class="flex flex-col flex-1 overflow-hidden">

    <div x-show="!orderSent" class="flex-1 overflow-y-auto scrollbar-hide px-5 py-4 space-y-3">
      <div class="bg-green-50 rounded-2xl px-4 py-3 flex justify-between items-center">
        <span class="text-sm text-green-600 font-medium">
          <span x-text="cart.reduce((s,i)=>s+i.qty,0)"></span> producto(s)
        </span>
        @if(!$isQuoteOnly || $quotePriceDisp==='show')
        <span class="font-bold gc" x-text="'S/ ' + cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></span>
        @else
        <span class="text-xs text-green-300 italic">a cotizar</span>
        @endif
      </div>

      <input x-model="form.name" type="text" placeholder="Tu nombre completo *"
             class="w-full border-2 border-green-100 focus:border-green-400 rounded-xl px-4 py-2.5 text-sm text-green-800 outline-none transition"
             autocomplete="name">
      <input x-model="form.phone" type="tel" placeholder="Tu WhatsApp / telÃ©fono *"
             class="w-full border-2 border-green-100 focus:border-green-400 rounded-xl px-4 py-2.5 text-sm text-green-800 outline-none transition"
             autocomplete="tel">
      <input x-model="form.email" type="email" placeholder="Tu correo electrÃ³nico (opcional)"
             class="w-full border-2 border-green-100 focus:border-green-400 rounded-xl px-4 py-2.5 text-sm text-green-800 outline-none transition"
             autocomplete="email">
      <textarea x-model="form.notes" rows="2" placeholder="DirecciÃ³n / notas adicionales (opcional)"
                class="w-full border-2 border-green-100 focus:border-green-400 rounded-xl px-4 py-2.5 text-sm text-green-800 outline-none resize-none transition"></textarea>
      @if($requireAddress)
      <input x-model="form.address" type="text" placeholder="DirecciÃ³n de entrega *"
             class="w-full border-2 border-green-100 focus:border-green-400 rounded-xl px-4 py-2.5 text-sm text-green-800 outline-none transition"
             autocomplete="street-address">
      @endif
      {{-- CupÃ³n --}}
      <div>
        <div x-show="!couponApplied" class="flex gap-2">
          <input x-model="couponCode" @keydown.enter.prevent="applyCoupon" type="text"
                 placeholder="CÃ³digo de descuento"
                 class="flex-1 border-2 border-green-100 focus:border-green-400 rounded-xl px-4 py-2.5 text-sm outline-none transition uppercase"
                 style="text-transform:uppercase">
          <button @click="applyCoupon" :disabled="couponLoading" type="button"
                  class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-green-100 hover:bg-green-200 transition text-green-700 flex-shrink-0">
            <span x-text="couponLoading ? 'â€¦' : 'Aplicar'"></span>
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
      <div x-show="shippingEnabled || couponApplied" class="bg-green-50 rounded-xl px-4 py-3 space-y-1.5 text-sm text-green-700">
        <div class="flex justify-between opacity-70"><span>Subtotal</span><span x-text="'S/ ' + subtotal.toFixed(2)"></span></div>
        <div x-show="couponApplied && couponDiscount > 0" class="flex justify-between text-green-600 font-medium">
          <span>Descuento</span>
          <span x-text="'- S/ ' + couponDiscount.toFixed(2)"></span>
        </div>
        <div x-show="shippingEnabled" class="flex justify-between" :class="effectiveShipping===0 && shippingFreeFrom>0 ? 'text-green-600 font-medium' : 'opacity-70'">
          <span x-text="effectiveShipping===0 && shippingFreeFrom>0 ? 'ðŸŽ‰ EnvÃ­o gratis' : 'EnvÃ­o'"></span>
          <span x-text="effectiveShipping>0 ? 'S/ '+effectiveShipping.toFixed(2) : 'Gratis'"></span>
        </div>
        @if($shippingFreeFrom > 0)<p x-show="effectiveShipping>0" class="text-xs opacity-60">Agrega S/ <span x-text="Math.max(0,{{ $shippingFreeFrom }}-subtotal).toFixed(2)"></span> mÃ¡s para envÃ­o gratis</p>@endif
        <div class="flex justify-between font-bold text-green-900 border-t border-green-200 pt-1.5"><span>Total</span><span x-text="'S/ '+orderGrandTotal.toFixed(2)"></span></div>
      </div>

      <p x-show="orderError" x-text="orderError"
         class="text-red-500 text-xs text-center font-medium bg-red-50 px-3 py-2 rounded-xl"></p>
    </div>

    @if($isQuoteOnly)
    <div x-show="orderSent" class="flex-1 flex flex-col items-center justify-center px-5 py-8 text-center">
      <div class="w-20 h-20 bg-green-100 rounded-3xl flex items-center justify-center mb-5">
        <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg>
      </div>
      <p class="font-bold text-green-900 text-xl mb-2">Â¡CotizaciÃ³n enviada!</p>
      <p class="text-sm text-green-500 mb-6 leading-relaxed">Recibimos tu solicitud y te responderemos a la brevedad.</p>
      <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
              class="btn-gc px-8 py-3 rounded-full text-sm font-bold">
        Seguir explorando
      </button>
    </div>
    @endif

    <div x-show="!orderSent" class="border-t border-green-100 px-5 py-4 flex-shrink-0">
      @if(!$isQuoteOnly && $hasOnlinePayment)
      <button @click="submitOrder()" :disabled="orderLoading"
              class="w-full btn-gc py-3.5 rounded-full font-bold text-sm disabled:opacity-60 flex items-center justify-center gap-2">
        <template x-if="!orderLoading">
          <span class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            Continuar al pago
          </span>
        </template>
        <svg x-show="orderLoading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <span x-show="orderLoading">Procesando...</span>
      </button>
      @else
      <button @click="submitOrder()" :disabled="orderLoading"
              class="w-full py-3.5 rounded-full font-bold text-sm disabled:opacity-60 flex items-center justify-center gap-2
                     {{ $isQuoteOnly ? 'bg-[#25D366] hover:bg-[#20ba5a] text-white' : 'btn-gc' }}">
        <svg x-show="orderLoading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <span x-show="!orderLoading">{{ $isQuoteOnly ? ($quoteWa ? 'Enviar por WhatsApp' : 'Solicitar cotizaciÃ³n') : 'Confirmar pedido' }}</span>
        <span x-show="orderLoading">Enviando...</span>
      </button>
      @endif
    </div>
  </div>

  @if(!$isQuoteOnly)
  {{-- â”€â”€ PASO 3: Pago â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
  <div x-show="drawerStep===3" class="flex flex-col flex-1 overflow-hidden">

    <div x-show="!orderSent" class="flex-1 overflow-y-auto scrollbar-hide px-4 py-4 space-y-3">

      {{-- Resumen pedido --}}
      <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl px-4 py-3.5 border border-green-100">
        <div class="flex justify-between items-center">
          <div>
            <p class="text-xs text-green-500 font-medium">Pedido #<span x-text="orderId"></span></p>
            <p class="text-xs text-green-400 mt-0.5" x-text="form.name"></p>
          </div>
          <div class="text-right">
            <p class="text-xs text-green-500">Total</p>
            <p class="font-bold text-xl gc" x-text="'S/ ' + orderTotal.toFixed(2)"></p>
          </div>
        </div>
      </div>

      {{-- Error pago --}}
      <div x-show="payError" x-cloak
           class="flex items-start gap-2 bg-red-50 border border-red-200 rounded-xl px-3 py-2.5">
        <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-red-600 text-xs font-medium" x-text="payError"></p>
      </div>

      {{-- MÃ©todos manuales --}}
      @if($payManualEnabled && count($payManualMethods) > 0)
      @php
        $mMeta = [
          'yape'          => ['label'=>'Yape',                 'color'=>'#6d28d9','bg'=>'#f5f3ff','border'=>'#c4b5fd','hint'=>'Escanea el QR o ingresa el nÃºmero'],
          'plin'          => ['label'=>'Plin',                 'color'=>'#0369a1','bg'=>'#f0f9ff','border'=>'#bae6fd','hint'=>'Abre Plin y paga al nÃºmero indicado'],
          'transferencia' => ['label'=>'Transferencia bancaria','color'=>'#0891b2','bg'=>'#ecfeff','border'=>'#a5f3fc','hint'=>'Transfiere y adjunta el nÃºmero de operaciÃ³n'],
          'qr'            => ['label'=>'Pago con QR',          'color'=>'#059669','bg'=>'#ecfdf5','border'=>'#6ee7b7','hint'=>'Escanea con cualquier billetera'],
          'contra_entrega'=> ['label'=>'Contra entrega',       'color'=>'#b45309','bg'=>'#fffbeb','border'=>'#fcd34d','hint'=>'Paga en efectivo al recibir'],
        ];
      @endphp
      <div>
        <p class="text-[11px] font-bold text-green-400 uppercase tracking-widest mb-2">Transferencia / Billetera</p>
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
             class="rounded-2xl overflow-hidden border-2 transition-all duration-200"
             :class="open ? 'border-[{{ $mm['border'] }}] shadow-sm' : 'border-green-100'">
          <button @click="open=!open; if(open) selectedPayMethod='{{ $mKey }}'"
                  class="w-full flex items-center gap-3 px-4 py-3.5 text-left transition"
                  :style="open ? 'background:{{ $mm['bg'] }}' : ''">
            @if($mKey==='yape')
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 font-bold text-white text-xs"
                 style="background:linear-gradient(135deg,#7c3aed,#4f46e5)">Yape</div>
            @elseif($mKey==='plin')
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 font-bold text-white text-xs"
                 style="background:linear-gradient(135deg,#0284c7,#0ea5e9)">Plin</div>
            @elseif($mKey==='transferencia')
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#ecfeff">
              <svg class="w-5 h-5" style="color:#0891b2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
              </svg>
            </div>
            @else
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 bg-green-50 text-xl">
              @if($mKey==='qr') ðŸ“² @else ðŸšš @endif
            </div>
            @endif
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-gray-800">{{ $mm['label'] }}</p>
              <p class="text-xs truncate" style="color:{{ $mm['color'] }}">{{ $mm['hint'] }}</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
              <span x-show="selectedPayMethod==='{{ $mKey }}'"
                    class="text-[10px] font-bold px-2 py-0.5 rounded-full text-white"
                    style="background:{{ $mm['color'] }}">activo</span>
              <svg class="w-4 h-4 text-green-300 transition-transform" :class="open ? 'rotate-180' : ''"
                   fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </div>
          </button>
          <div x-show="open" x-transition class="px-4 pb-5 pt-3 space-y-3" style="background:{{ $mm['bg'] }}">
            @if($mmDetails)
            <div class="bg-white rounded-xl border-2 p-3 space-y-2" style="border-color:{{ $mm['border'] }}">
              <div class="flex items-start justify-between gap-2">
                <div class="flex-1">
                  <p class="text-[10px] font-bold uppercase tracking-wide mb-1" style="color:{{ $mm['color'] }}">
                    @if($mKey==='yape') NÃºmero Yape
                    @elseif($mKey==='plin') NÃºmero Plin
                    @elseif($mKey==='transferencia') Datos bancarios
                    @else Datos de pago
                    @endif
                  </p>
                  <p class="text-sm font-bold text-gray-800 whitespace-pre-line">{{ $mmDetails }}</p>
                </div>
                @if(in_array($mKey, ['yape','plin']))
                <button @click="navigator.clipboard.writeText('{{ addslashes($mmDetails) }}').then(()=>{copied=true;setTimeout(()=>copied=false,2000)})"
                        class="flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                        :style="copied ? 'background:#dcfce7;color:#16a34a' : 'background:{{ $mm['border'] }};color:{{ $mm['color'] }}'">
                  <span x-show="!copied">ðŸ“‹ Copiar</span>
                  <span x-show="copied" x-cloak>âœ“ Copiado</span>
                </button>
                @endif
              </div>
              @if(in_array($mKey, ['yape','plin']) && $mmDetails)
              @php
                $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode($mmDetails);
              @endphp
              <div class="flex justify-center pt-2">
                <div class="bg-white p-2 rounded-xl border-2" style="border-color:{{ $mm['border'] }}">
                  <img src="{{ $qrUrl }}" alt="QR {{ $mm['label'] }}" width="120" height="120" class="rounded-lg" onerror="this.style.display='none'">
                  <p class="text-center text-[9px] mt-1" style="color:{{ $mm['color'] }}">Escanea con tu app</p>
                </div>
              </div>
              @endif
            </div>
            @endif
            @if($payManualInstr)
            <div class="flex items-start gap-2 text-xs text-gray-600 bg-white/70 rounded-xl px-3 py-2">
              <span class="text-base leading-none">ðŸ’¡</span>
              <p>{{ $payManualInstr }}</p>
            </div>
            @endif
            <div>
              <label class="text-xs font-bold block mb-1.5" style="color:{{ $mm['color'] }}">
                @if($mKey==='contra_entrega') Confirma tu direcciÃ³n de entrega
                @else NÃºmero de operaciÃ³n / cÃ³digo *
                @endif
              </label>
              <input x-model="payReference" type="text"
                     placeholder="{{ $mKey==='contra_entrega' ? 'Tu direcciÃ³n de entrega' : 'Ej: 123456789' }}"
                     class="w-full border-2 rounded-xl px-4 py-2.5 text-sm outline-none transition font-mono"
                     style="border-color:{{ $mm['border'] }};background:white">
            </div>
            <button @click="confirmManualPay()" :disabled="payLoading || !payReference.trim()"
                    class="w-full py-3.5 rounded-xl font-bold text-sm text-white transition disabled:opacity-50 flex items-center justify-center gap-2 active:scale-95"
                    style="background:{{ $mm['color'] }}">
              <svg x-show="payLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              <svg x-show="!payLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
              </svg>
              <span x-show="!payLoading">{{ $mKey==='contra_entrega' ? 'Confirmar pedido' : 'Ya paguÃ© Â· confirmar' }}</span>
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
        <p class="text-[11px] font-bold text-green-400 uppercase tracking-widest mb-2">Tarjeta / Culqi</p>
        <button @click="openCulqi()" :disabled="payLoading"
                class="w-full flex items-center gap-3 px-4 py-3.5 border-2 border-green-100 hover:border-green-400 hover:bg-green-50/50 rounded-2xl text-left transition active:scale-95 disabled:opacity-50">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
               style="background:linear-gradient(135deg,#4f46e5,#7c3aed)">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
          </div>
          <div class="flex-1">
            <p class="text-sm font-bold text-gray-800">Tarjeta crÃ©dito / dÃ©bito</p>
            <div class="flex items-center gap-1.5 mt-0.5">
              <span class="text-[10px] bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded font-semibold">Visa</span>
              <span class="text-[10px] bg-orange-100 text-orange-700 px-1.5 py-0.5 rounded font-semibold">Mastercard</span>
              <span class="text-[10px] bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded font-semibold">Yape</span>
            </div>
          </div>
          <svg x-show="!payLoading" class="w-5 h-5 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
          <svg x-show="payLoading" class="w-5 h-5 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
        </button>
      </div>
      @endif

      {{-- Mercado Pago --}}
      @if($mpEnabled)
      <div>
        <p class="text-[11px] font-bold text-green-400 uppercase tracking-widest mb-2">Mercado Pago</p>
        <button @click="openMercadoPago()" :disabled="payLoading"
                class="w-full flex items-center gap-3 px-4 py-3.5 border-2 border-green-100 hover:border-blue-400 hover:bg-blue-50/50 rounded-2xl text-left transition active:scale-95 disabled:opacity-50">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
               style="background:linear-gradient(135deg,#009ee3,#00b1ea)">
            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/>
            </svg>
          </div>
          <div class="flex-1">
            <p class="text-sm font-bold text-gray-800">Mercado Pago</p>
            <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
              <span class="text-[10px] bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded font-semibold">Tarjetas</span>
              <span class="text-[10px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded font-semibold">Yape</span>
              <span class="text-[10px] bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded font-semibold">Cuotas</span>
            </div>
          </div>
          <svg x-show="!payLoading" class="w-5 h-5 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
          </svg>
          <svg x-show="payLoading" class="w-5 h-5 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
        </button>
        <p class="text-center text-[10px] text-green-300 mt-1.5">SerÃ¡s redirigido al checkout de Mercado Pago</p>
      </div>
      @endif

      @if(!$culqiEnabled && !$mpEnabled && !$payManualEnabled)
      <div class="flex flex-col items-center justify-center py-8 text-center space-y-4">
        <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center">
          <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <div>
          <p class="font-bold text-green-900 text-lg">Â¡Pedido recibido!</p>
          <p class="text-sm text-green-500 mt-1 leading-relaxed">Nos contactaremos contigo para coordinar el pago y entrega. ðŸŒ¿</p>
        </div>
        @if($quoteWa)
        <a href="https://wa.me/{{ $quoteWa }}?text={{ urlencode('Hola, acabo de hacer un pedido y quiero coordinar el pago.') }}"
           target="_blank" rel="noopener"
           class="flex items-center gap-2 bg-[#25D366] text-white px-6 py-3 rounded-full text-sm font-bold">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
          </svg>
          Coordinar por WhatsApp
        </a>
        @endif
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
                class="text-sm text-green-400 hover:text-green-600 underline">
          Seguir comprando
        </button>
      </div>
      @endif

      @if($culqiEnabled || $mpEnabled || $payManualEnabled)
      <div class="flex items-center justify-center gap-2 pt-1 pb-2">
        <svg class="w-3.5 h-3.5 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
        <p class="text-[11px] text-green-300">Pago 100% seguro Â· Datos protegidos</p>
      </div>
      @endif
    </div>

    {{-- Ã‰xito pago --}}
    <div x-show="orderSent" class="flex-1 flex flex-col items-center justify-center px-5 py-8 text-center">
      <div class="relative mb-5">
        <div class="w-24 h-24 rounded-full flex items-center justify-center"
             style="background:linear-gradient(135deg,#dcfce7,#bbf7d0)">
          <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <div class="absolute -top-1 -right-1 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
          <span class="text-white text-sm">âœ“</span>
        </div>
      </div>
      <p class="font-bold text-green-900 text-2xl mb-1">Â¡Listo!</p>
      <p class="font-semibold text-green-600 mb-1">Pedido #<span x-text="orderId"></span> registrado</p>
      <p class="text-sm text-green-400 mb-4 leading-relaxed">Tu pago fue registrado. Nos pondremos en contacto contigo pronto. ðŸŒ¿</p>
      <div class="bg-green-50 border-2 border-dashed border-green-200 rounded-2xl px-6 py-3 my-2 w-full">
        <p class="text-xs text-green-400 mb-0.5">Referencia de pago</p>
        <p class="font-mono font-bold text-green-700 text-lg" x-text="'#' + orderId + '-' + (payReference || 'OK')"></p>
      </div>
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\D/', '', $project->whatsapp) }}?text={{ urlencode('Hola, acabo de hacer un pedido #') }}' + orderId + '{{ urlencode(' en ' . $project->name) }}"
         target="_blank"
         class="w-full mt-3 py-3 rounded-full font-bold text-sm text-white flex items-center justify-center gap-2 mb-3"
         style="background:#25D366">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
        </svg>
        Confirmar por WhatsApp
      </a>
      @endif
      <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};payReference='';drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
              class="w-full btn-gc py-3 rounded-full text-sm font-bold">
        Seguir comprando
      </button>
    </div>

  </div>
  @endif

</div>
{{-- â”€â”€â”€ /DRAWER â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}


{{-- â”€â”€â”€ TRUST BAR â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
<div class="bg-[#f0fdf4] border-b border-green-100 py-2 px-4">
  <p class="text-xs text-green-700 font-medium text-center">
    ðŸŒ¿ 100% Natural &nbsp;|&nbsp; ðŸšš Delivery en 24h &nbsp;|&nbsp; âœ… Sin conservantes &nbsp;|&nbsp; ðŸ’š Calidad garantizada
  </p>
</div>


{{-- â”€â”€â”€ HEADER â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
<header class="bg-white border-b border-green-50 sticky top-0 z-40">
  <div class="max-w-6xl mx-auto px-4 py-3 flex items-center gap-4">

    {{-- Logo + hoja SVG --}}
    <a href="{{ $canonicalUrl }}" class="flex items-center gap-2 flex-shrink-0" aria-label="{{ $project->name }}">
      @if($project->logo_url)
        <img src="{{ asset('storage/'.$project->logo_url) }}" alt="Logo {{ $project->name }}"
             style="height:44px; max-height:52px; max-width:180px" class="object-contain w-auto">
      @else
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
          <path d="M17 8C8 10 5.9 16.17 3.82 21h2.14c.62-1.76 1.65-3.38 3.04-4.6C10.37 21.5 12 24 12 24s1.5-2.3 2.91-7.6c1.36 1.22 2.39 2.84 3.04 4.6h2.1C17.9 16.17 17 8 17 8z"/>
        </svg>
        <span class="font-bold text-green-800 text-lg hidden sm:block">{{ $project->name }}</span>
      @endif
    </a>

    {{-- MenÃº categorÃ­as centrado --}}
    <nav class="hidden md:flex items-center gap-5 flex-1 justify-center">
      <button @click="filterCat=''"
              :class="filterCat==='' ? 'text-green-800 font-semibold' : 'text-green-500'"
              class="text-sm hover:text-green-800 transition">Todo</button>
      @foreach($categories as $cat)
      <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
              :class="filterCat==='{{ $cat->id }}' ? 'text-green-800 font-semibold' : 'text-green-500'"
              class="text-sm hover:text-green-800 transition">{{ $cat->name }}</button>
      @endforeach
    </nav>

    {{-- Acciones --}}
    <div class="flex items-center gap-3 flex-shrink-0 ml-auto">
      {{-- Lupa + campo bÃºsqueda --}}
      <div class="relative hidden md:block" @click.outside="searchOpen = false">
        <svg class="w-4 h-4 text-green-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
        </svg>
        <input type="text" x-model="search" placeholder="Buscar..."
               @input="searchOpen = search.trim().length >= 2; searchIdx = -1; if(search.trim().length >= 2) _scrollToCatalog()"
               @keydown.arrow-down.prevent="if(suggestions.length){searchIdx=(searchIdx+1)%suggestions.length}"
               @keydown.arrow-up.prevent="if(suggestions.length){searchIdx=(searchIdx-1+suggestions.length)%suggestions.length}"
               @keydown.enter.prevent="if(searchIdx>=0){selectSuggestion(suggestions[searchIdx])}else{searchOpen=false;_scrollToCatalog()}"
               @keydown.escape="searchOpen=false;searchIdx=-1"
               class="border border-green-200 rounded-full py-1.5 pl-9 pr-4 text-sm text-green-800 focus:outline-none focus:border-green-400 transition w-44">
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
      {{-- Carrito --}}
      <button @click="drawerOpen=true; drawerStep=1" class="relative text-green-600 hover:text-green-800 transition" aria-label="{{ $isQuoteOnly ? 'CotizaciÃ³n' : 'Carrito' }}">
        @if($isQuoteOnly)
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        @else
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        @endif
        <span x-show="cart.length > 0" x-text="cart.reduce((s,i)=>s+i.qty,0)"
              class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-gc flex items-center justify-center text-white text-[10px] font-bold"></span>
      </button>
    </div>
  </div>
</header>


{{-- â”€â”€â”€ HERO â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
<section class="relative flex items-center" style="min-height:65vh; background:{{ $heroBg }};">
  {{-- DecoraciÃ³n ondas --}}
  <svg class="absolute bottom-0 left-0 right-0 w-full" viewBox="0 0 1440 60" preserveAspectRatio="none" fill="#f9fffe" xmlns="http://www.w3.org/2000/svg">
    <path d="M0,40 C360,0 1080,60 1440,20 L1440,60 L0,60 Z"/>
  </svg>
  <div class="max-w-6xl mx-auto px-6 py-20 w-full relative z-10">
    @if($heroBadge)
    <span class="inline-block bg-green-200/90 text-green-800 text-xs font-semibold px-4 py-1.5 rounded-full mb-5">
      ðŸŒ¿ {{ $heroBadge }}
    </span>
    @endif
    <h1 class="text-4xl md:text-5xl font-bold text-white mb-4 leading-tight max-w-xl">{{ $heroTitle }}</h1>
    <p class="text-white/80 text-base md:text-lg mb-8 max-w-md">{{ $heroSub }}</p>
    <div class="flex flex-wrap gap-3">
      <a href="#frescos" class="inline-block bg-green-500 hover:bg-green-400 text-white font-semibold text-sm px-7 py-3 rounded-full transition shadow-lg">
        Ver productos â†’
      </a>
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}" target="_blank" rel="noopener"
         class="inline-flex items-center gap-2 border-2 border-white/40 text-white font-semibold text-sm px-6 py-3 rounded-full hover:bg-white/10 transition">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
        </svg>
        WhatsApp
      </a>
      @endif
    </div>
  </div>
</section>


{{-- â”€â”€â”€ ÃCONOS CATEGORÃAS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
@if($categories->count() > 0)
<section class="max-w-6xl mx-auto px-4 pt-10 pb-6">
  <div class="flex gap-5 overflow-x-auto scrollbar-hide pb-2 justify-start md:justify-center">
    <div class="flex flex-col items-center gap-2 flex-shrink-0">
      <button @click="filterCat=''"
              :class="filterCat==='' ? 'active' : ''"
              class="cat-circle">âœ¦</button>
      <span class="text-xs text-green-700 font-medium">Todo</span>
    </div>
    @foreach($categories as $cat)
    <div class="flex flex-col items-center gap-2 flex-shrink-0">
      <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
              :class="filterCat==='{{ $cat->id }}' ? 'active' : ''"
              class="cat-circle">{{ mb_strtoupper(mb_substr($cat->name, 0, 1)) }}</button>
      <span class="text-xs text-green-700 font-medium max-w-[72px] text-center leading-tight">{{ $cat->name }}</span>
    </div>
    @endforeach
  </div>
</section>
@endif


{{-- â”€â”€â”€ SECCIÃ“N PRODUCTOS FRESCOS (newArrivals) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
@if($newArrivals->count() > 0)
<section id="frescos" class="max-w-6xl mx-auto px-4 pb-14">
  <div class="flex items-center gap-3 mb-7">
    <span class="text-2xl">ðŸŒ¿</span>
    <h2 class="text-xl font-bold text-green-800">Productos Frescos</h2>
    <div class="flex-1 h-px bg-green-100"></div>
  </div>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    @foreach($newArrivals as $p)
    @php
    $qvData = ['id'=>$p->id,'name'=>$p->name,'img'=>$p->mainImage?$p->main_image_url:'','price'=>(float)$p->price,'cp'=>$p->compare_price?(float)$p->compare_price:null,'desc'=>\Str::limit(strip_tags($p->description??''),120),'url'=>route('public.product',[$project->slug,$p->id]),'stock'=>$p->stock];
    @endphp
    <article x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }})"
             class="fresh-card group" itemscope itemtype="https://schema.org/Product"
             data-qv='@json($qvData)'>
      <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="block fc-img relative">
        @if($p->mainImage)
        <img src="{{ $p->main_image_url }}" alt="{{ $p->name }}"
             class="w-full h-full object-cover" itemprop="image" loading="lazy">
        @else
        <div class="w-full h-full flex items-center justify-center text-5xl">ðŸŒ¿</div>
        @endif
        @if($p->compare_price && $p->compare_price > $p->price)
        <span class="absolute top-2 left-2 bg-red-500 text-white text-[9px] font-bold uppercase px-2 py-0.5 rounded-full">Oferta</span>
        @endif
        @php
          $diffDays = \Carbon\Carbon::parse($p->created_at)->diffInDays(now());
        @endphp
        @if($diffDays <= 14)
        <span class="absolute top-2 right-2 bg-green-500 text-white text-[9px] font-bold uppercase px-2 py-0.5 rounded-full">Nuevo</span>
        @endif
        @if($p->stock !== null && $p->stock === 0)
        <span class="absolute bottom-0 left-0 right-0 bg-red-600/90 text-white text-[10px] font-black py-1 text-center tracking-wide">AGOTADO</span>
        @elseif($p->stock !== null && $p->stock > 0 && $p->stock <= 5)
        <span class="absolute bottom-0 left-0 right-0 bg-orange-500/90 text-white text-[10px] font-bold py-1 text-center leading-tight">âš¡ CASI AGOTADO â€” {{ $p->stock }} restantes</span>
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
      <div class="p-4">
        <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="text-green-800 text-sm font-semibold mb-1 line-clamp-2 hover:underline block" itemprop="name">{{ $p->name }}</a>
        @if(!$isQuoteOnly || $quotePriceDisp==='show')
        <div class="flex items-baseline gap-2 mb-3">
          <span class="gc font-bold text-base">S/ {{ number_format($p->price, 2) }}</span>
          @if($p->compare_price && $p->compare_price > $p->price)
          <span class="text-gray-300 text-xs line-through">S/ {{ number_format($p->compare_price, 2) }}</span>
          @endif
          @if($p->compare_price && $p->compare_price > $p->price)
          @php $ah = $p->compare_price - $p->price; @endphp
          <p class="text-[10px] text-green-600 font-semibold leading-none mt-1 w-full">Ahorras {{ $currency }} {{ number_format($ah,2) }}</p>
          @endif
        </div>
        @else
        <p class="text-green-300 text-xs mb-3 italic">Consultar precio</p>
        @endif
        @if(!$isQuoteOnly)
        <button class="w-full py-2 text-xs font-semibold rounded-lg btn-gc"
                @click="addToCart({id:{{ $p->id }}, name:'{{ addslashes($p->name) }}', price:{{ $p->price }}, img:'{{ $p->mainImage ? $p->main_image_url : '' }}'})">
          Agregar al carrito
        </button>
        @else
        <a class="block w-full py-2 text-xs font-semibold rounded-lg btn-outline-gc text-center"
           href="https://wa.me/{{ $quoteWa }}?text={{ urlencode('Hola, me interesa: '.$p->name) }}" target="_blank">
          Cotizar por WhatsApp
        </a>
        @endif
      </div>
    </article>
    @endforeach
  </div>
</section>
@endif


{{-- â”€â”€â”€ TRUST BADGES â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
<section class="bg-[#f0fdf4] py-12 px-4">
  <div class="max-w-4xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
    <div>
      <div class="text-4xl mb-2">ðŸšš</div>
      <p class="text-green-800 font-semibold text-sm">Delivery rÃ¡pido</p>
      <p class="text-green-500 text-xs mt-1">Entregamos en 24h</p>
    </div>
    <div>
      <div class="text-4xl mb-2">ðŸŒ¿</div>
      <p class="text-green-800 font-semibold text-sm">100% Natural</p>
      <p class="text-green-500 text-xs mt-1">Sin quÃ­micos ni conservantes</p>
    </div>
    <div>
      <div class="text-4xl mb-2">âœ…</div>
      <p class="text-green-800 font-semibold text-sm">Calidad garantizada</p>
      <p class="text-green-500 text-xs mt-1">SelecciÃ³n rigurosa</p>
    </div>
    <div>
      <div class="text-4xl mb-2">ðŸ’š</div>
      <p class="text-green-800 font-semibold text-sm">Eco-friendly</p>
      <p class="text-green-500 text-xs mt-1">Empaque sostenible</p>
    </div>
  </div>
</section>


{{-- â”€â”€â”€ BANNER CENTRAL â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
<section class="py-16 px-4" style="background:{{ $heroBg }};">
  <div class="max-w-2xl mx-auto text-center">
    <h2 class="text-white font-bold text-3xl mb-3">{{ $b1Title }}</h2>
    @if($b1Sub)
    <p class="text-white/70 text-base mb-8">{{ $b1Sub }}</p>
    @endif
    <a href="#catalogo"
       class="inline-block border-2 border-white text-white font-semibold text-sm px-8 py-3 rounded-full hover:bg-white hover:text-green-800 transition">
      Ver catÃ¡logo completo
    </a>
  </div>
</section>


{{-- â”€â”€â”€ CATÃLOGO COMPLETO CON SIDEBAR â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
<section id="catalogo" class="max-w-6xl mx-auto px-4 py-16">
  <div class="flex gap-8">

    {{-- Sidebar --}}
    <aside class="hidden md:block w-52 flex-shrink-0">
      <div class="bg-[#f0fdf4] border border-green-100 rounded-2xl p-4 sticky top-24">
        <p class="text-green-700 font-bold text-sm mb-3">CategorÃ­as</p>
        <ul class="space-y-0.5">
          <li>
            <button @click="filterCat=''"
                    :class="filterCat==='' ? 'active' : ''"
                    class="scat-btn">
              <span>âœ¦</span> Todo
            </button>
          </li>
          @foreach($categories as $cat)
          <li>
            <button @click="filterCat='{{ $cat->id }}'"
                    :class="filterCat==='{{ $cat->id }}' ? 'active' : ''"
                    class="scat-btn">
              <span class="w-6 h-6 rounded-full bg-green-200 flex items-center justify-center text-[10px] font-bold text-green-700 flex-shrink-0">
                {{ mb_strtoupper(mb_substr($cat->name, 0, 1)) }}
              </span>
              {{ $cat->name }}
            </button>
          </li>
          @endforeach
        </ul>
        <div class="mt-5 pt-4 border-t border-green-100">
          <p class="text-green-700 font-bold text-sm mb-3">Filtros</p>
          <label class="flex items-center gap-2 text-xs text-green-600 cursor-pointer mb-3">
            <input type="checkbox" x-model="onSaleFilter" class="rounded accent-green-600"> Solo ofertas
          </label>
          <select x-model="priceFilter"
                  class="w-full border border-green-200 rounded-lg py-1.5 px-2 text-xs text-green-700 focus:outline-none focus:border-green-400">
            <option value="">Todos los precios</option>
            <option value="0-50">S/ 0 â€“ 50</option>
            <option value="50-150">S/ 50 â€“ 150</option>
            <option value="150-500">S/ 150 â€“ 500</option>
            <option value="500+">S/ 500+</option>
          </select>
        </div>
        {{-- Campo bÃºsqueda en sidebar mobile hidden --}}
        <div class="mt-4">
          <div class="relative" @click.outside="searchOpen = false">
            <svg class="w-3.5 h-3.5 text-green-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
            </svg>
            <input type="text" x-model="search" placeholder="Buscar..."
                   @input="searchOpen = search.trim().length >= 2; searchIdx = -1; if(search.trim().length >= 2) _scrollToCatalog()"
                   @keydown.arrow-down.prevent="if(suggestions.length){searchIdx=(searchIdx+1)%suggestions.length}"
                   @keydown.arrow-up.prevent="if(suggestions.length){searchIdx=(searchIdx-1+suggestions.length)%suggestions.length}"
                   @keydown.enter.prevent="if(searchIdx>=0){selectSuggestion(suggestions[searchIdx])}else{searchOpen=false;_scrollToCatalog()}"
                   @keydown.escape="searchOpen=false;searchIdx=-1"
                   class="w-full border border-green-200 rounded-lg py-1.5 pl-8 pr-3 text-xs text-green-700 focus:outline-none focus:border-green-400 transition">
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
        </div>
      </div>
    </aside>

    {{-- Grid productos --}}
    <div class="flex-1 min-w-0">

      {{-- Barra bÃºsqueda mobile --}}
      <div class="flex md:hidden items-center gap-3 mb-5">
        <div class="relative flex-1" @click.outside="searchOpen = false">
          <svg class="w-4 h-4 text-green-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
          </svg>
          <input type="text" x-model="search" placeholder="Buscar productos..."
                 @input="searchOpen = search.trim().length >= 2; searchIdx = -1; if(search.trim().length >= 2) _scrollToCatalog()"
                 @keydown.arrow-down.prevent="if(suggestions.length){searchIdx=(searchIdx+1)%suggestions.length}"
                 @keydown.arrow-up.prevent="if(suggestions.length){searchIdx=(searchIdx-1+suggestions.length)%suggestions.length}"
                 @keydown.enter.prevent="if(searchIdx>=0){selectSuggestion(suggestions[searchIdx])}else{searchOpen=false;_scrollToCatalog()}"
                 @keydown.escape="searchOpen=false;searchIdx=-1"
                 class="w-full border border-green-200 rounded-full py-2 pl-9 pr-4 text-sm text-green-800 focus:outline-none focus:border-green-400 transition">
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
        <select x-model="filterCat"
                class="border border-green-200 rounded-full py-2 px-3 text-sm text-green-700 focus:outline-none focus:border-green-400">
          <option value="">Todas</option>
          @foreach($categories as $cat)
          <option value="{{ $cat->id }}">{{ $cat->name }}</option>
          @endforeach
        </select>
        <button @click="filterOpen=true"
                class="xl:hidden flex items-center gap-1.5 text-xs font-semibold border border-green-200 rounded-full px-3 py-2 bg-white hover:bg-green-50 transition relative">
          <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
          </svg>
          <span class="text-green-700">Filtros</span>
          <span x-show="priceFilter!=='' || onSaleFilter"
                class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full text-[10px] font-black flex items-center justify-center text-white"
                style="background:var(--c)"
                x-text="(priceFilter!==''?1:0)+(onSaleFilter?1:0)"></span>
        </button>
        <select x-model="sortBy" class="border border-green-200 rounded-full py-2 px-3 text-sm text-green-700 focus:outline-none focus:border-green-400">
          <option value="default">Ordenar por...</option>
          <option value="price_asc">Precio: menor a mayor</option>
          <option value="price_desc">Precio: mayor a menor</option>
          <option value="newest">MÃ¡s recientes</option>
          <option value="name_az">Nombre Aâ†’Z</option>
        </select>
      </div>
      <p class="text-xs text-gray-400 mb-4" x-show="filterCat!==''||search!==''||priceFilter!==''||onSaleFilter">
        <span x-text="visibleCount"></span> producto<span x-show="visibleCount!==1">s</span> encontrado<span x-show="visibleCount!==1">s</span>
      </p>

      @foreach($categories as $cat)
      @if($cat->products->count() > 0)
      <div x-show="filterCat==='' || filterCat==='{{ $cat->id }}'">
        <h3 class="text-green-700 font-bold text-sm mb-4 mt-2 flex items-center gap-2">
          <span class="text-lg">ðŸŒ¿</span>{{ $cat->name }}
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-10" data-products-grid>
          @foreach($cat->products as $p)
          @php
          $qvData = ['id'=>$p->id,'name'=>$p->name,'img'=>$p->mainImage?$p->main_image_url:'','price'=>(float)$p->price,'cp'=>$p->compare_price?(float)$p->compare_price:null,'desc'=>\Str::limit(strip_tags($p->description??''),120),'url'=>route('public.product',[$project->slug,$p->id]),'stock'=>$p->stock];
          @endphp
          <article x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }}) && ({{ $loop->index }} < 8 || expandedCats['{{ $cat->id }}'])"
                   class="fresh-card group" itemscope itemtype="https://schema.org/Product"
                   data-price="{{ $p->price }}"
                   data-ts="{{ $p->created_at ? $p->created_at->timestamp : 0 }}"
                   data-idx="{{ $loop->index }}"
                   data-qv='@json($qvData)'>
            <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="block fc-img relative">
              @if($p->mainImage)
              <img src="{{ $p->main_image_url }}" alt="{{ $p->name }}"
                   class="w-full h-full object-cover" itemprop="image" loading="lazy">
              @else
              <div class="w-full h-full flex items-center justify-center text-4xl">ðŸŒ¿</div>
              @endif
              @if($p->compare_price && $p->compare_price > $p->price)
              <span class="absolute top-2 left-2 bg-red-500 text-white text-[9px] font-bold uppercase px-2 py-0.5 rounded-full">Oferta</span>
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
            <div class="p-3">
              <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="text-green-800 text-xs font-semibold mb-1 line-clamp-2 hover:underline block" itemprop="name">{{ $p->name }}</a>
              @if(!$isQuoteOnly || $quotePriceDisp==='show')
              @if(isset($productRatings) && isset($productRatings[$p->id]))
              <div class="flex items-center gap-1 mb-1">
                <span class="text-amber-400 text-xs">{{ str_repeat('â˜…', floor($productRatings[$p->id]->avg_rating)) }}{{ str_repeat('â˜†', 5 - floor($productRatings[$p->id]->avg_rating)) }}</span>
                <span class="text-[10px] text-gray-400">({{ $productRatings[$p->id]->rating_count }})</span>
              </div>
              @endif
              <div class="flex items-baseline gap-1.5 mb-2">
                <span class="gc font-bold text-sm">S/ {{ number_format($p->price, 2) }}</span>
                @if($p->compare_price && $p->compare_price > $p->price)
                <span class="text-gray-300 text-xs line-through">S/ {{ number_format($p->compare_price, 2) }}</span>
                @endif
              </div>
              @else
              <p class="text-green-300 text-xs mb-2 italic">Consultar precio</p>
              @endif
              @if(!$isQuoteOnly)
              <button class="w-full py-2 text-[11px] font-semibold rounded-lg btn-gc"
                      @click="addToCart({id:{{ $p->id }}, name:'{{ addslashes($p->name) }}', price:{{ $p->price }}, img:'{{ $p->mainImage ? $p->main_image_url : '' }}'})">
                Agregar
              </button>
              @else
              <a class="block w-full py-2 text-[11px] font-semibold rounded-lg btn-outline-gc text-center"
                 href="https://wa.me/{{ $quoteWa }}?text={{ urlencode('Hola, me interesa: '.$p->name) }}" target="_blank">
                Cotizar
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

      {{-- Sin resultados --}}
      <div x-show="noResults" class="text-center py-20">
        <div class="text-6xl mb-4">ðŸŒ¿</div>
        <p class="font-bold text-green-700 text-lg mb-1">Sin resultados</p>
        <p class="text-green-400 text-sm mb-4">Intenta con otro tÃ©rmino o categorÃ­a</p>
        <button @click="search=''; filterCat=''; priceFilter=''; onSaleFilter=false"
                class="btn-outline-gc px-5 py-2 rounded-full text-sm font-semibold transition">
          Ver todo el catÃ¡logo
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


{{-- â”€â”€â”€ FOOTER â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
<footer style="background:{{ $heroBg }};" class="py-14">
  <div class="max-w-6xl mx-auto px-4 grid grid-cols-1 md:grid-cols-3 gap-10">

    <div>
      @php $footerLogo = ($settings['logo_url'] ?? '') ?: ($project->logo_url ?? ''); $footerLogoSrc = $footerLogo ? (str_starts_with($footerLogo,'http') ? $footerLogo : asset('storage/'.$footerLogo)) : ''; @endphp
      @if($footerLogoSrc)
      <img src="{{ $footerLogoSrc }}" alt="{{ $project->name }}"
           style="max-height:60px; max-width:200px" class="object-contain w-auto mb-4" loading="lazy">
      @else
      <p class="text-white font-bold text-xl mb-4">🌿 {{ $project->name }}</p>
      @endif
      <p class="text-white/60 text-sm leading-relaxed">{{ $seoDesc }}</p>
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}"
         target="_blank" rel="noopener"
         class="inline-flex items-center gap-2 mt-4 bg-[#25D366] hover:bg-[#20ba5a] text-white text-xs font-bold px-4 py-2 rounded-full transition">
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/>
        </svg>
        Escribir por WhatsApp
      </a>
      @endif
    </div>

    <div>
      <p class="text-white/40 text-xs uppercase tracking-widest mb-4">CategorÃ­as</p>
      <ul class="space-y-2">
        @foreach($categories as $cat)
        <li>
          <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                  class="text-white/70 hover:text-white text-sm transition text-left">
            {{ $cat->name }}
          </button>
        </li>
        @endforeach
      </ul>
    </div>

    <div>
      <p class="text-white/40 text-xs uppercase tracking-widest mb-4">Contacto</p>
      <ul class="space-y-2.5 text-sm text-white/60">
        @if($project->phone)
        <li class="flex items-center gap-2">
          <svg class="w-4 h-4 opacity-50 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
          </svg>
          {{ $project->phone }}
        </li>
        @endif
        @if($project->address)
        <li class="flex items-start gap-2">
          <svg class="w-4 h-4 opacity-50 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          {{ $project->address }}
        </li>
        @endif
        @foreach(['facebook_url'=>'Facebook','instagram_url'=>'Instagram','tiktok_url'=>'TikTok','youtube_url'=>'YouTube'] as $sKey=>$sLabel)
        @if($settings[$sKey] ?? null)
        <li><a href="{{ $settings[$sKey] }}" target="_blank" rel="noopener"
               class="hover:text-white transition">{{ $sLabel }}</a></li>
        @endif
        @endforeach
      </ul>
    </div>

  </div>
  <div class="border-t border-white/10 mt-12 pt-6 text-center">
    <p class="text-white/20 text-xs">Â© {{ date('Y') }} <strong class="text-white/30">{{ $project->name }}</strong> â€” CatÃ¡logo online por <strong class="text-white/30">AVAN</strong></p>
  </div>
</footer>


{{-- â”€â”€â”€ BOTÃ“N FLOTANTE WHATSAPP â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
@if($project->whatsapp)
<a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}?text={{ urlencode($settings['whatsapp_msg'] ?? 'Hola, quisiera mÃ¡s informaciÃ³n') }}"
   target="_blank" rel="noopener"
   class="fixed bottom-6 right-6 z-50 w-13 h-13 w-[52px] h-[52px] rounded-full bg-[#25D366] flex items-center justify-center shadow-xl hover:scale-110 transition-transform"
   aria-label="Contactar por WhatsApp">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 24 24">
    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/>
  </svg>
</a>
@endif


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
                  class="w-full btn-gc py-2.5 rounded-xl text-sm font-black flex items-center justify-center gap-2 transition">
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

{{-- â”€â”€â”€ ALPINE STORE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
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
    drawerOpen: false,
    drawerStep: 1,
    toastShow: false, toastMsg: '', toastTimer: null,
    cart: _savedCart,
    form: _savedForm,
    orderLoading: false,
    orderError: '',
    noResults: false,
    orderId: null,
    orderTotal: 0,
    orderSent: false,
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
    payError: '',
    payLoading: false,

    init() {
      try {
          const _rv = JSON.parse(localStorage.getItem('rv_{{ $project->slug }}') || '[]');
          this.recentlyViewed = _rv.filter(x => x && x.id);
      } catch(e) {}
      this.$watch('cart', val => {
        try { localStorage.setItem(this._cartKey, JSON.stringify(val)); } catch(e) {}
        this.$nextTick(() => { this.checkNoResults(); });
      });
      this.$watch('form', val => {
        try { localStorage.setItem(this._formKey, JSON.stringify(val)); } catch(e) {}
      }, { deep: true });
      this.$watch('search', () => this.$nextTick(() => this.checkNoResults()));
      this.$watch('filterCat', () => this.$nextTick(() => this.checkNoResults()));
      this.$watch('priceFilter', () => this.$nextTick(() => this.checkNoResults()));
      this.$watch('onSaleFilter', () => this.$nextTick(() => this.checkNoResults()));
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

      // Detectar retorno desde Mercado Pago
      const urlParams = new URLSearchParams(window.location.search);
      const payStatus  = urlParams.get('payment');
      const payOrderId = urlParams.get('order');
      if (payStatus && payOrderId) {
        this.orderId     = parseInt(payOrderId) || 0;
        this.orderTotal  = 0;
        if (payStatus === 'success' || payStatus === 'approved') {
          this.orderSent   = true;
          this.drawerOpen  = true;
          this.drawerStep  = 3;
          this.payReference = urlParams.get('payment_id') || 'mp-ok';
          try { localStorage.removeItem(this._cartKey); localStorage.removeItem(this._formKey); } catch(e) {}
        } else if (payStatus === 'failure') {
          this.payError   = 'El pago fue rechazado en Mercado Pago. Intenta de nuevo.';
          this.drawerOpen = true;
          this.drawerStep = 3;
        } else if (payStatus === 'pending') {
          this.orderSent   = true;
          this.drawerOpen  = true;
          this.drawerStep  = 3;
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
        this.orderError = 'Por favor ingresa tu nombre.';
        return;
      }
      const businessName = `{{ addslashes($project->name) }}`;
      const customMsg    = `{{ addslashes($quoteWaMsg) }}`;
      const now   = new Date();
      const fecha = now.toLocaleDateString('es-PE', { day:'2-digit', month:'long', year:'numeric' });

      let lines = '';
      lines += `ðŸŒ¿ *SOLICITUD DE COTIZACIÃ“N*\n`;
      lines += `â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”\n`;
      lines += `ðŸª *${businessName}*\n\n`;
      lines += `${customMsg}\n\n`;
      lines += `ðŸ‘¤ *DATOS DE CONTACTO*\n`;
      lines += `â€¢ Nombre: ${this.form.name}\n`;
      if (this.form.phone) lines += `â€¢ TelÃ©fono: ${this.form.phone}\n`;
      if (this.form.email) lines += `â€¢ Correo: ${this.form.email}\n`;
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
      lines += `\n_CotizaciÃ³n desde el catÃ¡logo de ${businessName}_`;

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
      this.orderError   = '';
      const items = this.cart.map(i => ({
        product_id: i.id,
        name: i.name,
        price: i.price,
        quantity: i.qty,
      }));
      try {
        const res = await fetch('/{{ $project->slug }}/order', {
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
          @elseif($isQuoteOnly)
          try { localStorage.removeItem(this._cartKey); localStorage.removeItem(this._formKey); } catch(e) {}
          window.location.href = '/{{ $project->slug }}/thanks/' + data.order_id;
          @else
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
        this.orderError = 'Error de conexiÃ³n. Verifica tu internet.';
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
      } catch(e) {
        this.payError = 'Error de conexiÃ³n.';
      }
      this.payLoading = false;
    },

    @if($culqiEnabled && $culqiPublicKey)
    openCulqi() {
      const self  = this;
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
          desctext:   'Pedido #' + self.orderId,
        },
        paymentMethods: { tarjeta: true, yape: true, billetera: false, cuotealo: false }
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
              self.orderSent    = true;
              self.payReference = data.charge_id || 'culqi-ok';
              try { localStorage.removeItem(self._cartKey); localStorage.removeItem(self._formKey); } catch(e) {}
            } else {
              self.payError = data.message || 'Tarjeta rechazada. Intenta con otra.';
            }
          } catch(e) {
            self.payError = 'Error de conexiÃ³n. IntÃ©ntalo de nuevo.';
          }
          self.payLoading = false;
          Culqi.close();
        } else if (Culqi.error) {
          self.payError   = Culqi.error.user_message || 'Error en el pago.';
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
        if (data.init_point) {
          const url = (data.is_sandbox && data.sandbox_init_point) ? data.sandbox_init_point : data.init_point;
          window.location.href = url;
        } else {
          this.payError   = 'No se pudo iniciar el pago. Intenta de nuevo.';
          this.payLoading = false;
        }
      } catch(e) {
        this.payError   = 'Error de conexiÃ³n.';
        this.payLoading = false;
      }
    },
    @endif
  };
}
</script>

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


