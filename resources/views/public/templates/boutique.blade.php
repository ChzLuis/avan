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
$primaryColor     = $settings['primary_color'] ?? '#111111';
$isQuoteOnly      = ($settings['store_mode'] ?? 'direct') === 'quote_only';
$shippingEnabled  = ($settings['shipping_enabled']  ?? '0') === '1';
$shippingCost     = (float)($settings['shipping_cost']      ?? 0);
$shippingFreeFrom = (float)($settings['shipping_free_from'] ?? 0);
$requireAddress   = ($settings['require_address']   ?? '0') === '1';
$quotePriceDisp   = $settings['quote_price_display'] ?? 'show';
$quoteWaRaw       = preg_replace('/\D/', '', $settings['quote_whatsapp'] ?? $project->whatsapp ?? '');
$quoteWaCountry   = $settings['quote_whatsapp_country'] ?? '51';
$quoteWa          = $quoteWaRaw ? (str_starts_with($quoteWaRaw, $quoteWaCountry) ? $quoteWaRaw : $quoteWaCountry.$quoteWaRaw) : '';
$canonicalUrl     = url('/' . $project->slug);
$seoTitle         = ($settings['seo_title'] ?? null) ?: ($project->name . ' â€” CatÃ¡logo Online');
$seoDesc          = ($settings['seo_description'] ?? null) ?: ($project->description ?? 'Explora nuestra colecciÃ³n y haz tu pedido en lÃ­nea.');
$heroBg           = $settings['hero_bg_color'] ?? '#1a1a1a';
$heroTitle        = $settings['hero_title'] ?? $project->name;
$heroSub          = $settings['hero_subtitle'] ?? 'Descubre nuestra colecciÃ³n';
$heroBadge        = $settings['hero_badge'] ?? '';
$b1Title          = $settings['banner1_title'] ?? 'Nueva ColecciÃ³n';
$b1Sub            = $settings['banner1_sub'] ?? '';
$b2Title          = $settings['banner2_title'] ?? 'EdiciÃ³n Limitada';
$b2Sub            = $settings['banner2_sub'] ?? '';
@endphp

<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDesc }}">
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta property="og:type"        content="website">
<meta property="og:url"         content="{{ $canonicalUrl }}">
<meta property="og:title"       content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDesc }}">
<meta property="og:locale"      content="es_PE">
<meta property="og:site_name"   content="{{ $project->name }}">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@if($culqiEnabled && $culqiPublicKey)
<script src="https://checkout.culqi.com/js/v4"></script>
@endif

@php
  $secondaryColor = $settings['secondary_color'] ?? '#c4a97d';
  $fontTitle  = $settings['font_title'] ?? $settings['font'] ?? 'Playfair Display';
  $fontBody   = $settings['font_body']  ?? $settings['font'] ?? 'Jost';
  $borderRadius = ['sharp'=>'0px','rounded'=>'8px','pill'=>'50px'][$settings['border_radius'] ?? 'sharp'] ?? '0px';
  $faviconUrl   = $settings['favicon_url'] ?? '';
  $catalogBadgeSale = $settings['catalog_badge_sale'] ?? 'SALE';
  $catalogBadgeNew  = $settings['catalog_badge_new']  ?? 'NOUVEAU';
  $btnCartText  = $settings['btn_cart_text']  ?? 'AÃ±adir al carrito';
  $btnQuoteText = $settings['btn_quote_text'] ?? 'Solicitar cotizaciÃ³n';
  $footerTagline   = $settings['footer_tagline']  ?? '';
  $footerCopyright = $settings['footer_copyright'] ?? ('Â© ' . date('Y') . ' ' . $project->name);
@endphp
@if($faviconUrl)<link rel="icon" href="{{ $faviconUrl }}">@endif
<style>
:root {
  --c: {{ $primaryColor }};
  --c2: {{ $secondaryColor }};
  --radius: {{ $borderRadius }};
  --font-title: '{{ $fontTitle }}', Georgia, serif;
  --font-body:  '{{ $fontBody }}', sans-serif;
}
*, *::before, *::after { box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; background: #fafaf8; color: #1a1a1a; }
.font-serif { font-family: 'Playfair Display', Georgia, serif !important; }
[x-cloak] { display: none !important; }

/* Drawer */
.drawer-overlay { position:fixed;inset:0;z-index:50;background:rgba(0,0,0,.40);backdrop-filter:blur(3px); }
.drawer-panel   { position:fixed;right:0;top:0;bottom:0;z-index:51;width:100%;max-width:420px;background:#fff;display:flex;flex-direction:column;box-shadow:-8px 0 40px rgba(0,0,0,.14); }
@media(max-width:480px){ .drawer-panel{ max-width:100%; } }

/* Scrollbar */
.scrollbar-hide::-webkit-scrollbar { display:none; }
.scrollbar-hide { -ms-overflow-style:none; scrollbar-width:none; }

/* Card hover overlay */
.prod-overlay { position:absolute;inset:0;background:rgba(0,0,0,0);transition:background .35s;display:flex;align-items:center;justify-content:center; }
.group:hover .prod-overlay { background:rgba(0,0,0,.40); }
.prod-btn-hover { opacity:0;transform:translateY(10px);transition:opacity .3s,transform .3s; }
.group:hover .prod-btn-hover { opacity:1;transform:translateY(0); }

/* Category tabs active */
.tab-cat-active { border-bottom:2px solid var(--c);color:var(--c) !important; }

html { scroll-behavior:smooth; }
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

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     DRAWER â€” 3 pasos
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<div x-show="drawerOpen" x-cloak>
  <div class="drawer-overlay" @click="drawerOpen=false" aria-hidden="true"></div>
  <div class="drawer-panel"
       role="dialog" aria-label="{{ $isQuoteOnly ? 'Mi cotizaciÃ³n' : 'Mi pedido' }}"
       x-show="drawerOpen"
       x-transition:enter="transition ease-out duration-300 transform"
       x-transition:enter-start="translate-x-full"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-200 transform"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="translate-x-full">

    {{-- Header drawer --}}
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
      <div class="flex items-center gap-2">
        <button x-show="drawerStep > 1 && !orderSent"
                @click="drawerStep--"
                class="p-1 text-gray-400 hover:text-gray-700 transition mr-1" aria-label="Volver">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <span class="font-serif text-lg tracking-wide text-gray-900">
          <span x-show="drawerStep===1">{{ $isQuoteOnly ? 'Mi CotizaciÃ³n' : 'Mi SelecciÃ³n' }}</span>
          <span x-show="drawerStep===2" x-cloak>Tus Datos</span>
          <span x-show="drawerStep===3" x-cloak>{{ $isQuoteOnly ? 'ConfirmaciÃ³n' : 'Pago' }}</span>
        </span>
        <span x-show="cart.length > 0 && drawerStep === 1"
              x-text="cart.length + ' items'"
              class="text-[10px] uppercase tracking-widest text-gray-400 font-light"></span>
      </div>
      <button @click="drawerOpen=false" class="p-1.5 text-gray-300 hover:text-gray-700 transition" aria-label="Cerrar">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    {{-- â”€â”€ PASO 1: Lista de items â”€â”€ --}}
    <div x-show="drawerStep===1" class="flex flex-col flex-1 overflow-hidden">
      <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5 scrollbar-hide">
        <template x-if="cart.length === 0">
          <div class="text-center py-20 text-gray-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 mx-auto mb-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <p class="text-sm font-light text-gray-400">Tu selecciÃ³n estÃ¡ vacÃ­a</p>
            <p class="text-xs text-gray-300 mt-1">Agrega productos para comenzar</p>
          </div>
        </template>
        <template x-for="(item, i) in cart" :key="item.id">
          <div class="flex gap-4 items-start">
            <div class="w-16 h-20 bg-gray-100 flex-shrink-0 overflow-hidden">
              <img :src="item.img" x-show="item.img" class="w-full h-full object-cover" :alt="item.name">
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs uppercase tracking-widest text-gray-700 leading-snug line-clamp-2" x-text="item.name"></p>
              @if(!$isQuoteOnly || $quotePriceDisp === 'show')
              <p class="text-sm text-[#9a9a9a] mt-1">S/ <span x-text="(item.price * item.qty).toFixed(2)"></span></p>
              @endif
              <div class="flex items-center gap-2 mt-2">
                <button @click="item.qty > 1 ? item.qty-- : cart.splice(i, 1)"
                        class="w-6 h-6 border border-gray-200 text-xs flex items-center justify-center hover:border-gray-700 transition">
                  <span x-text="item.qty > 1 ? 'âˆ’' : 'Ã—'"></span>
                </button>
                <span class="text-xs w-5 text-center text-gray-700" x-text="item.qty"></span>
                <button @click="item.qty++"
                        class="w-6 h-6 border border-gray-200 text-xs flex items-center justify-center hover:border-gray-700 transition">+</button>
              </div>
            </div>
          </div>
        </template>
      </div>
      <div class="border-t border-gray-100 px-6 py-5 flex-shrink-0">
        <div class="flex justify-between items-center mb-4">
          <span class="text-xs uppercase tracking-widest text-[#9a9a9a]">Subtotal</span>
          @if(!$isQuoteOnly || $quotePriceDisp === 'show')
          <span class="text-sm font-medium text-gray-900">S/ <span x-text="cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></span></span>
          @else
          <span class="text-xs text-[#9a9a9a] italic">Precio a consultar</span>
          @endif
        </div>
        <button @click="cart.length > 0 && (drawerStep = 2)"
                :disabled="cart.length === 0"
                class="w-full py-3 text-xs uppercase tracking-widest bg-gray-900 text-white hover:bg-gray-700 disabled:opacity-30 transition">
          Continuar
        </button>
      </div>
    </div>

    {{-- â”€â”€ PASO 2: Formulario de datos â”€â”€ --}}
    <div x-show="drawerStep===2" x-cloak class="flex flex-col flex-1 overflow-hidden">
      <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4 scrollbar-hide">
        <div class="bg-[#fafaf8] px-4 py-3 flex justify-between text-xs">
          <span class="text-[#9a9a9a]"><span x-text="cart.reduce((s,i)=>s+i.qty,0)"></span> producto<span x-show="cart.reduce((s,i)=>s+i.qty,0)!==1">s</span></span>
          @if(!$isQuoteOnly || $quotePriceDisp === 'show')
          <span class="text-gray-700 font-medium">S/ <span x-text="cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></span></span>
          @else
          <span class="text-[#9a9a9a] italic">a cotizar</span>
          @endif
        </div>
        <div>
          <label class="block text-[10px] uppercase tracking-widest text-[#9a9a9a] mb-1.5">Nombre *</label>
          <input type="text" x-model="form.name" autocomplete="name"
                 class="w-full border-b border-gray-200 bg-transparent py-2 text-sm focus:outline-none focus:border-gray-900 transition"
                 placeholder="Tu nombre completo">
        </div>
        <div>
          <label class="block text-[10px] uppercase tracking-widest text-[#9a9a9a] mb-1.5">TelÃ©fono *</label>
          <input type="tel" x-model="form.phone" autocomplete="tel"
                 class="w-full border-b border-gray-200 bg-transparent py-2 text-sm focus:outline-none focus:border-gray-900 transition"
                 placeholder="NÃºmero de contacto">
        </div>
        <div>
          <label class="block text-[10px] uppercase tracking-widest text-[#9a9a9a] mb-1.5">Correo</label>
          <input type="email" x-model="form.email" autocomplete="email"
                 class="w-full border-b border-gray-200 bg-transparent py-2 text-sm focus:outline-none focus:border-gray-900 transition"
                 placeholder="tucorreo@email.com">
        </div>
        <div>
          <label class="block text-[10px] uppercase tracking-widest text-[#9a9a9a] mb-1.5">Notas</label>
          <textarea x-model="form.notes" rows="2"
                    class="w-full border-b border-gray-200 bg-transparent py-2 text-sm focus:outline-none focus:border-gray-900 resize-none transition"
                    placeholder="Talla, color, notas adicionales..."></textarea>
          @if($requireAddress)
          <input x-model="form.address" type="text" placeholder="DirecciÃ³n de entrega *"
                 class="w-full border-b border-gray-200 bg-transparent py-2 text-sm focus:outline-none focus:border-gray-900 transition"
                 autocomplete="street-address">
          @endif
        </div>
        {{-- CupÃ³n --}}
        <div class="mx-6 mb-3">
          <div x-show="!couponApplied" class="flex gap-2">
            <input x-model="couponCode" @keydown.enter.prevent="applyCoupon" type="text"
                   placeholder="CÃ³digo de descuento"
                   class="flex-1 border-b border-gray-200 focus:border-gray-900 px-0 py-2 text-[10px] uppercase tracking-widest outline-none transition bg-transparent"
                   style="text-transform:uppercase">
            <button @click="applyCoupon" :disabled="couponLoading" type="button"
                    class="px-3 py-2 text-[10px] uppercase tracking-widest font-semibold text-gray-700 hover:text-gray-900 transition flex-shrink-0">
              <span x-text="couponLoading ? 'â€¦' : 'Aplicar'"></span>
            </button>
          </div>
          <div x-show="couponApplied" class="flex items-center justify-between bg-green-50 border border-green-200 px-3 py-2 text-[10px] uppercase tracking-widest">
            <div>
              <span class="font-mono font-bold text-green-700" x-text="couponApplied ? couponApplied.code : ''"></span>
              <span class="text-green-600 ml-1">&mdash; <span x-text="couponApplied && couponApplied.type==='percent' ? couponApplied.value+'%' : 'S/ '+(couponApplied?couponApplied.value:0).toFixed(2)"></span></span>
            </div>
            <button @click="removeCoupon" type="button" class="text-gray-400 hover:text-red-500 ml-3 text-lg leading-none">&times;</button>
          </div>
          <p x-show="couponError" class="text-red-500 text-[9px] mt-1" x-text="couponError"></p>
        </div>
        <div x-show="shippingEnabled || couponApplied" class="bg-gray-50 mx-6 mb-3 px-4 py-3 space-y-1.5 text-sm">
          <div class="flex justify-between text-gray-400 text-[10px] uppercase tracking-widest"><span>Subtotal</span><span x-text="'S/ ' + subtotal.toFixed(2)"></span></div>
          <div x-show="couponApplied && couponDiscount > 0" class="flex justify-between text-[10px] uppercase tracking-widest text-green-600">
            <span>Descuento</span>
            <span x-text="'- S/ ' + couponDiscount.toFixed(2)"></span>
          </div>
          <div x-show="shippingEnabled" class="flex justify-between text-[10px] uppercase tracking-widest" :class="effectiveShipping===0 && shippingFreeFrom>0 ? 'text-green-600' : 'text-gray-400'">
            <span x-text="effectiveShipping===0 && shippingFreeFrom>0 ? 'ðŸŽ‰ EnvÃ­o gratis' : 'EnvÃ­o'"></span>
            <span x-text="effectiveShipping>0 ? 'S/ '+effectiveShipping.toFixed(2) : 'Gratis'"></span>
          </div>
          @if($shippingFreeFrom > 0)<p x-show="effectiveShipping>0" class="text-[9px] text-gray-300">Agrega S/ <span x-text="Math.max(0,{{ $shippingFreeFrom }}-subtotal).toFixed(2)"></span> mÃ¡s para envÃ­o gratis</p>@endif
          <div class="flex justify-between text-[10px] uppercase tracking-widest font-black text-gray-900" style="border-top:1px solid #e5e7eb;padding-top:6px;"><span>Total</span><span x-text="'S/ '+orderGrandTotal.toFixed(2)"></span></div>
        </div>
        <p x-show="orderError" x-text="orderError" class="text-red-400 text-xs py-1 px-6"></p>
      </div>
      <div class="border-t border-gray-100 px-6 py-5 flex-shrink-0 space-y-2">
        <button @click="submitOrder()" :disabled="orderLoading"
                class="w-full py-3 text-xs uppercase tracking-widest bg-gray-900 text-white hover:bg-gray-700 disabled:opacity-50 transition flex items-center justify-center gap-2">
          <svg x-show="orderLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <span x-show="!orderLoading">{{ $isQuoteOnly ? ($quoteWa ? 'Enviar por WhatsApp' : 'Solicitar cotizaciÃ³n') : 'Confirmar pedido' }}</span>
          <span x-show="orderLoading">Enviando...</span>
        </button>
      </div>
    </div>

    {{-- â”€â”€ PASO 3: Pago / ConfirmaciÃ³n â”€â”€ --}}
    <div x-show="drawerStep===3" x-cloak class="flex flex-col flex-1 overflow-hidden">

      {{-- Ã‰xito --}}
      <div x-show="orderSent" class="flex-1 flex flex-col items-center justify-center px-6 py-10 text-center">
        <div class="w-14 h-14 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-5">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <p class="font-serif text-2xl text-gray-900 mb-1">{{ $isQuoteOnly ? 'Â¡CotizaciÃ³n enviada!' : 'Â¡Pedido confirmado!' }}</p>
        <p class="text-xs text-[#9a9a9a] mb-1" x-show="orderId">Pedido #<span x-text="orderId"></span></p>
        <p class="text-xs text-[#9a9a9a]" x-show="payReference">Ref: <span x-text="payReference"></span></p>
        @if($project->whatsapp)
        <a href="https://wa.me/{{ preg_replace('/\D/', '', $project->whatsapp) }}?text={{ urlencode('Hola, acabo de hacer un pedido en ' . $project->name) }}"
           target="_blank"
           class="mt-6 inline-flex items-center gap-2 px-5 py-2.5 text-xs uppercase tracking-widest text-white rounded-none transition"
           style="background:#25D366">
          Confirmar por WhatsApp
        </a>
        @endif
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};payReference='';drawerOpen=false;try{localStorage.removeItem('boutique_cart_{{ $project->id }}');localStorage.removeItem('boutique_form_{{ $project->id }}');}catch(e){}"
                class="mt-3 text-xs uppercase tracking-widest text-[#9a9a9a] hover:text-gray-900 transition">
          Seguir explorando
        </button>
      </div>

      {{-- MÃ©todos de pago (sin pagar aÃºn) --}}
      <div x-show="!orderSent" class="flex-1 overflow-y-auto px-6 py-5 space-y-4 scrollbar-hide">

        {{-- Resumen --}}
        <div class="border border-gray-100 px-4 py-3 text-xs flex justify-between">
          <div>
            <p class="text-[#9a9a9a]">Pedido #<span x-text="orderId"></span></p>
            <p class="text-[#9a9a9a] mt-0.5" x-text="form.name"></p>
          </div>
          <div class="text-right">
            <p class="text-[#9a9a9a]">Total</p>
            <p class="font-medium text-gray-900 text-base mt-0.5">S/ <span x-text="orderTotal.toFixed(2)"></span></p>
          </div>
        </div>

        <p x-show="payError" x-text="payError" class="text-red-400 text-xs"></p>

        @if($hasOnlinePayment)
        <p class="text-[10px] uppercase tracking-widest text-[#9a9a9a]">Elige cÃ³mo pagar</p>

        {{-- Yape --}}
        @if($payManualEnabled && in_array('yape', $payManualMethods) && $payYapeNumber)
        <div x-data="{ open: false, copied: false }" class="border border-gray-100">
          <button @click="open=!open" class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-[#fafaf8] transition">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 flex items-center justify-center font-black text-white text-[10px] rounded" style="background:linear-gradient(135deg,#7c3aed,#4f46e5)">Yape</div>
              <span class="text-xs uppercase tracking-widest text-gray-700">Yape</span>
            </div>
            <svg class="w-3.5 h-3.5 text-gray-300 transition-transform" :class="open?'rotate-180':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
          <div x-show="open" x-transition class="px-4 pb-4 space-y-3 bg-[#fafaf8]">
            <div class="flex items-center justify-between">
              <p class="text-sm text-gray-700 font-medium">{{ $payYapeNumber }}</p>
              <button @click="navigator.clipboard.writeText('{{ addslashes($payYapeNumber) }}').then(()=>{copied=true;setTimeout(()=>copied=false,2000)})"
                      class="text-[10px] uppercase tracking-widest px-2 py-1 border border-gray-200 hover:border-gray-700 transition">
                <span x-show="!copied">Copiar</span>
                <span x-show="copied" x-cloak>Copiado</span>
              </button>
            </div>
            <div>
              <label class="block text-[10px] uppercase tracking-widest text-[#9a9a9a] mb-1">NÃºmero de operaciÃ³n *</label>
              <input x-model="payReference" type="text" placeholder="Ej: 123456789"
                     class="w-full border-b border-gray-200 bg-transparent py-2 text-sm focus:outline-none focus:border-gray-900 transition font-mono">
            </div>
            @if($payManualInstr)
            <p class="text-xs text-[#9a9a9a] leading-relaxed">{{ $payManualInstr }}</p>
            @endif
            <button @click="confirmManualPay()" :disabled="payLoading || !payReference.trim()"
                    class="w-full py-2.5 text-xs uppercase tracking-widest text-white disabled:opacity-40 transition flex items-center justify-center gap-2"
                    style="background:#7c3aed">
              <svg x-show="payLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              <span x-show="!payLoading">Confirmar pago</span>
              <span x-show="payLoading">Procesando...</span>
            </button>
          </div>
        </div>
        @endif

        {{-- Plin --}}
        @if($payManualEnabled && in_array('plin', $payManualMethods) && $payPlinNumber)
        <div x-data="{ open: false, copied: false }" class="border border-gray-100">
          <button @click="open=!open" class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-[#fafaf8] transition">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 flex items-center justify-center font-black text-white text-[10px] rounded" style="background:linear-gradient(135deg,#0284c7,#0ea5e9)">Plin</div>
              <span class="text-xs uppercase tracking-widest text-gray-700">Plin</span>
            </div>
            <svg class="w-3.5 h-3.5 text-gray-300 transition-transform" :class="open?'rotate-180':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
          <div x-show="open" x-transition class="px-4 pb-4 space-y-3 bg-[#fafaf8]">
            <div class="flex items-center justify-between">
              <p class="text-sm text-gray-700 font-medium">{{ $payPlinNumber }}</p>
              <button @click="navigator.clipboard.writeText('{{ addslashes($payPlinNumber) }}').then(()=>{copied=true;setTimeout(()=>copied=false,2000)})"
                      class="text-[10px] uppercase tracking-widest px-2 py-1 border border-gray-200 hover:border-gray-700 transition">
                <span x-show="!copied">Copiar</span>
                <span x-show="copied" x-cloak>Copiado</span>
              </button>
            </div>
            <div>
              <label class="block text-[10px] uppercase tracking-widest text-[#9a9a9a] mb-1">NÃºmero de operaciÃ³n *</label>
              <input x-model="payReference" type="text" placeholder="Ej: 123456789"
                     class="w-full border-b border-gray-200 bg-transparent py-2 text-sm focus:outline-none focus:border-gray-900 transition font-mono">
            </div>
            @if($payManualInstr)
            <p class="text-xs text-[#9a9a9a] leading-relaxed">{{ $payManualInstr }}</p>
            @endif
            <button @click="confirmManualPay()" :disabled="payLoading || !payReference.trim()"
                    class="w-full py-2.5 text-xs uppercase tracking-widest text-white disabled:opacity-40 transition flex items-center justify-center gap-2"
                    style="background:#0284c7">
              <svg x-show="payLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              <span x-show="!payLoading">Confirmar pago</span>
              <span x-show="payLoading">Procesando...</span>
            </button>
          </div>
        </div>
        @endif

        {{-- Transferencia bancaria --}}
        @if($payManualEnabled && in_array('transferencia', $payManualMethods) && $payBankDetails)
        <div x-data="{ open: false }" class="border border-gray-100">
          <button @click="open=!open" class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-[#fafaf8] transition">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 flex items-center justify-center bg-[#f0f9ff] rounded">
                <svg class="w-4 h-4 text-[#0891b2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                </svg>
              </div>
              <span class="text-xs uppercase tracking-widest text-gray-700">Transferencia bancaria</span>
            </div>
            <svg class="w-3.5 h-3.5 text-gray-300 transition-transform" :class="open?'rotate-180':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
          <div x-show="open" x-transition class="px-4 pb-4 space-y-3 bg-[#fafaf8]">
            <p class="text-xs text-gray-600 whitespace-pre-line leading-relaxed">{{ $payBankDetails }}</p>
            <div>
              <label class="block text-[10px] uppercase tracking-widest text-[#9a9a9a] mb-1">NÃºmero de operaciÃ³n *</label>
              <input x-model="payReference" type="text" placeholder="Nro. de transferencia"
                     class="w-full border-b border-gray-200 bg-transparent py-2 text-sm focus:outline-none focus:border-gray-900 transition font-mono">
            </div>
            @if($payManualInstr)
            <p class="text-xs text-[#9a9a9a] leading-relaxed">{{ $payManualInstr }}</p>
            @endif
            <button @click="confirmManualPay()" :disabled="payLoading || !payReference.trim()"
                    class="w-full py-2.5 text-xs uppercase tracking-widest bg-gray-900 text-white disabled:opacity-40 transition flex items-center justify-center gap-2">
              <svg x-show="payLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              <span x-show="!payLoading">Confirmar pago</span>
              <span x-show="payLoading">Procesando...</span>
            </button>
          </div>
        </div>
        @endif

        {{-- Culqi --}}
        @if($culqiEnabled && $culqiPublicKey)
        <button @click="openCulqi()" :disabled="payLoading"
                class="w-full flex items-center gap-3 px-4 py-3 border border-gray-100 hover:bg-[#fafaf8] transition disabled:opacity-50">
          <div class="w-8 h-8 flex items-center justify-center bg-[#f0fdf4] rounded">
            <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
          </div>
          <span class="text-xs uppercase tracking-widest text-gray-700 flex-1 text-left">Pagar con tarjeta</span>
          <svg x-show="payLoading" class="w-4 h-4 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
        </button>
        @endif

        {{-- Mercado Pago --}}
        @if($mpEnabled)
        <button @click="openMercadoPago()" :disabled="payLoading"
                class="w-full flex items-center gap-3 px-4 py-3 border border-gray-100 hover:bg-[#fafaf8] transition disabled:opacity-50">
          <div class="w-8 h-8 flex items-center justify-center bg-blue-50 rounded">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <span class="text-xs uppercase tracking-widest text-gray-700 flex-1 text-left">Mercado Pago</span>
          <svg x-show="payLoading" class="w-4 h-4 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
        </button>
        @endif

        @else
        {{-- Sin pagos en lÃ­nea --}}
        <div class="text-center py-6">
          <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <p class="font-serif text-lg text-gray-900">Pedido recibido</p>
          <p class="text-xs text-[#9a9a9a] mt-2">Nos pondremos en contacto para coordinar el pago.</p>
        </div>
        <button @click="cart=[];orderSent=true" class="w-full py-3 text-xs uppercase tracking-widest bg-gray-900 text-white hover:bg-gray-700 transition">Cerrar</button>
        @endif

      </div>
    </div>

  </div>
</div>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     HEADER â€” logo centrado
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<header class="bg-white border-b border-gray-100 sticky top-0 z-40">
  <div class="max-w-6xl mx-auto px-4">
    {{-- Barra principal: lupa | logo | carrito --}}
    <div class="flex items-center py-4 relative">
      {{-- Lupa --}}
      <div class="flex-1 flex justify-start">
        <button @click="showSearch=!showSearch" class="p-1.5 text-[#9a9a9a] hover:text-gray-900 transition" aria-label="Buscar">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
          </svg>
        </button>
      </div>
      {{-- Logo centrado --}}
      <a href="{{ $canonicalUrl }}" class="absolute left-1/2 -translate-x-1/2 flex items-center justify-center">
        @if($project->logo_url)
          <img src="{{ asset('storage/'.$project->logo_url) }}" alt="{{ $project->name }}" class="h-10 object-contain max-w-[160px]">
        @else
          <span class="font-serif text-xl tracking-widest uppercase text-gray-900">{{ $project->name }}</span>
        @endif
      </a>
      {{-- Carrito --}}
      <div class="flex-1 flex justify-end">
        <button @click="drawerOpen=true; drawerStep=1" class="relative p-1.5 text-gray-700 hover:text-gray-900 transition" aria-label="Ver carrito">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
          </svg>
          <span x-show="cart.length > 0" x-text="cart.reduce((s,i)=>s+i.qty,0)"
                class="absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full bg-gray-900 text-white text-[9px] flex items-center justify-center leading-none"></span>
        </button>
      </div>
    </div>
    {{-- Barra de bÃºsqueda desplegable --}}
    <div x-show="showSearch" x-transition class="pb-3">
      <div class="relative" @click.outside="searchOpen = false">
      <input type="search" x-model="search" x-ref="searchInput"
             @keydown.escape="showSearch=false; search=''; searchOpen=false; searchIdx=-1"
             @input="searchOpen = search.trim().length >= 2; searchIdx = -1; if(search.trim().length >= 2) _scrollToCatalog()"
             @keydown.arrow-down.prevent="if(suggestions.length){searchIdx=(searchIdx+1)%suggestions.length}"
             @keydown.arrow-up.prevent="if(suggestions.length){searchIdx=(searchIdx-1+suggestions.length)%suggestions.length}"
             @keydown.enter.prevent="if(searchIdx>=0){selectSuggestion(suggestions[searchIdx])}else{searchOpen=false;_scrollToCatalog()}"
             placeholder="Buscar en la colecciÃ³n..."
             autocomplete="off"
             class="w-full text-center text-sm border-b border-gray-200 bg-transparent py-2 focus:outline-none focus:border-gray-900 transition placeholder-gray-300">
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
    </div>
    {{-- Nav categorÃ­as --}}
    <nav class="flex items-center justify-center gap-5 pb-3 overflow-x-auto scrollbar-hide" aria-label="CategorÃ­as">
      <button @click="filterCat=''"
              :class="filterCat==='' ? 'text-gray-900 border-b border-gray-900' : 'text-[#9a9a9a]'"
              class="text-[10px] uppercase tracking-widest hover:text-gray-700 transition whitespace-nowrap flex-shrink-0 pb-0.5">
        Todo
      </button>
      @foreach($categories as $cat)
      <span class="text-gray-200 text-xs flex-shrink-0">|</span>
      <button @click="filterCat='{{ $cat->id }}'"
              :class="filterCat==='{{ $cat->id }}' ? 'text-gray-900 border-b border-gray-900' : 'text-[#9a9a9a]'"
              class="text-[10px] uppercase tracking-widest hover:text-gray-700 transition whitespace-nowrap flex-shrink-0 pb-0.5">
        {{ $cat->name }}
      </button>
      @endforeach
    </nav>
  </div>
</header>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     HERO â€” 85vh
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<section class="relative flex items-center justify-center overflow-hidden" style="height:85vh;background:{{ $heroBg }};">
  <div class="text-center px-6 max-w-3xl mx-auto relative z-10">
    @if($heroBadge)
    <p class="font-serif italic text-white/50 text-sm mb-5 tracking-wide">{{ $heroBadge }}</p>
    @endif
    <h1 class="font-serif text-5xl md:text-7xl text-white leading-tight tracking-tight mb-5">{{ $heroTitle }}</h1>
    @if($heroSub)
    <p class="text-white/50 text-sm font-light mb-9 max-w-md mx-auto tracking-wide">{{ $heroSub }}</p>
    @endif
    <a href="#catalogo" class="inline-block text-white text-xs uppercase tracking-widest border-b border-dotted border-white/30 pb-0.5 hover:border-white/70 transition">
      Explorar colecciÃ³n â†’
    </a>
  </div>
</section>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     CATEGORÃAS â€” grid 2 cols desktop
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
@if($categories->count() > 0)
<section class="max-w-6xl mx-auto px-4 py-16">
  @php
    $catBgs = ['#1a1a1a','#2d1b69','#0d3b2e','#3b1a1a','#1a2d3b','#2a2a2a','#1a3b2d','#3b2d1a'];
  @endphp
  <div class="grid grid-cols-2 gap-3">
    @foreach($categories as $i => $cat)
    @php $catBg = $catBgs[$i % count($catBgs)]; @endphp
    <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
            class="relative overflow-hidden group cursor-pointer text-left"
            style="aspect-ratio:3/2;background:{{ $catBg }};">
      @if($cat->products->first() && $cat->products->first()->mainImage)
      <img src="{{ $cat->products->first()->main_image_url ?? '' }}"
           alt="{{ $cat->name }}"
           class="absolute inset-0 w-full h-full object-cover opacity-50 group-hover:opacity-60 transition-opacity duration-500">
      @endif
      <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
      <div class="absolute inset-0 flex items-end p-5">
        <span class="text-white text-sm font-light uppercase tracking-widest">{{ $cat->name }}</span>
      </div>
      <div class="absolute inset-0 bg-white/0 group-hover:bg-white/5 transition duration-300"></div>
    </button>
    @endforeach
  </div>
</section>
@endif

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     NUEVOS INGRESOS
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
@if($newArrivals && $newArrivals->count() > 0)
<section class="max-w-6xl mx-auto px-4 py-8">
  <div class="flex items-center gap-4 mb-10">
    <span class="text-[10px] uppercase tracking-widest text-[#9a9a9a]">Nuevos Ingresos</span>
    <div class="flex-1 h-px bg-gray-100"></div>
  </div>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-x-5 gap-y-10">
    @foreach($newArrivals as $p)
    <article x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }})"
             class="group relative cursor-pointer">
      <div class="relative overflow-hidden" style="aspect-ratio:2/3;background:#f0eeeb;">
        @if($p->mainImage)
        <img src="{{ $p->main_image_url }}"
             alt="{{ $p->name }}"
             class="w-full h-full object-cover transition duration-500 group-hover:scale-105"
             loading="lazy">
        @endif
        <div class="prod-overlay">
          @if(!$isQuoteOnly)
          <button class="prod-btn-hover bg-white text-gray-900 text-[10px] uppercase tracking-widest px-5 py-2.5 hover:bg-gray-900 hover:text-white transition"
                  @click.stop="addToCart({id:{{ $p->id }}, name:'{{ addslashes($p->name) }}', price:{{ $p->price }}, img:'{{ $p->mainImage ? $p->main_image_url : '' }}'})">
            Agregar
          </button>
          @else
          <a class="prod-btn-hover bg-white text-gray-900 text-[10px] uppercase tracking-widest px-5 py-2.5 hover:bg-gray-900 hover:text-white transition"
             href="https://wa.me/{{ $quoteWa }}?text={{ urlencode('Hola, me interesa: '.$p->name) }}"
             target="_blank">
            Cotizar
          </a>
          @endif
        </div>
        @if($p->compare_price && $p->compare_price > $p->price)
        <span class="absolute top-0 left-0 bg-black text-white text-[9px] uppercase tracking-widest px-2.5 py-1">Oferta</span>
        @endif
        @if($p->stock !== null && $p->stock === 0)
        <span class="absolute bottom-0 left-0 right-0 bg-red-600/90 text-white text-[10px] font-black py-1 text-center tracking-wide">AGOTADO</span>
        @elseif($p->stock !== null && $p->stock > 0 && $p->stock <= 5)
        <span class="absolute bottom-0 left-0 right-0 bg-orange-500/90 text-white text-[10px] font-bold py-1 text-center leading-tight">âš¡ CASI AGOTADO â€” {{ $p->stock }} restantes</span>
        @endif
      </div>
      <div class="mt-3">
        @if($p->category)
        <p class="text-[9px] uppercase tracking-widest text-[#9a9a9a] mb-0.5">{{ $p->category->name }}</p>
        @endif
        <p class="text-xs text-gray-700 font-normal leading-snug"><a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="hover:underline">{{ $p->name }}</a></p>
        @if(!$isQuoteOnly || $quotePriceDisp === 'show')
        <div class="flex items-center gap-2 mt-1">
          <span class="text-sm text-[#9a9a9a]">S/ {{ number_format($p->price, 2) }}</span>
          @if($p->compare_price && $p->compare_price > $p->price)
          <span class="text-xs text-gray-300 line-through">S/ {{ number_format($p->compare_price, 2) }}</span>
          @endif
          @if($p->compare_price && $p->compare_price > $p->price)
          @php $ah = $p->compare_price - $p->price; @endphp
          <p class="text-[10px] text-green-600 font-semibold leading-none mt-1">Ahorras {{ $currency }} {{ number_format($ah,2) }}</p>
          @endif
        </div>
        @elseif($isQuoteOnly && $quotePriceDisp === 'hide')
        <p class="text-xs text-[#9a9a9a] mt-1 italic">Consultar precio</p>
        @endif
      </div>
    </article>
    @endforeach
  </div>
</section>
@endif

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     BANNER SPLIT 50/50
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<section class="my-4">
  <div class="grid grid-cols-1 md:grid-cols-2">
    {{-- Lado izquierdo: fondo heroBg + SVG lÃ­neas diagonales --}}
    <div class="relative flex items-center justify-center py-24 px-10 overflow-hidden" style="background:{{ $heroBg }};">
      <svg class="absolute inset-0 opacity-10 w-full h-full" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
        <defs>
          <pattern id="boutique-diag" patternUnits="userSpaceOnUse" width="12" height="12" patternTransform="rotate(45)">
            <line x1="0" y1="0" x2="0" y2="12" stroke="white" stroke-width="1"/>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#boutique-diag)"/>
      </svg>
      <div class="relative text-center text-white">
        <p class="font-serif italic text-4xl md:text-5xl mb-3 leading-tight">{{ $b1Title }}</p>
        @if($b1Sub)
        <p class="text-white/50 text-sm font-light tracking-wide">{{ $b1Sub }}</p>
        @endif
      </div>
    </div>
    {{-- Lado derecho: fondo blanco --}}
    <div class="flex items-center justify-center py-24 px-10 bg-white">
      <div class="text-center max-w-xs">
        <p class="font-serif text-4xl md:text-5xl text-gray-900 leading-tight mb-3">{{ $b2Title }}</p>
        @if($b2Sub)
        <p class="text-[#9a9a9a] text-sm font-light mb-7">{{ $b2Sub }}</p>
        @endif
        <a href="#catalogo"
           class="inline-block border border-gray-900 text-gray-900 text-[10px] uppercase tracking-widest px-7 py-3 hover:bg-gray-900 hover:text-white transition">
          Ver colecciÃ³n
        </a>
      </div>
    </div>
  </div>
</section>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     CATÃLOGO COMPLETO â€” tabs + grid
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<section id="catalogo" class="max-w-6xl mx-auto px-4 py-16">

  {{-- Tabs de categorÃ­as --}}
  <div class="flex items-center gap-0 border-b border-gray-100 mb-10 overflow-x-auto scrollbar-hide sticky top-16 z-20 bg-white/95 backdrop-blur-sm shadow-sm -mx-4 px-4">
    <p class="text-xs text-gray-400 mb-4 w-full" x-show="filterCat!==''||search!==''||priceFilter!==''||onSaleFilter">
      <span x-text="visibleCount"></span> producto<span x-show="visibleCount!==1">s</span> encontrado<span x-show="visibleCount!==1">s</span>
    </p>
    <button @click="filterCat=''"
            :class="filterCat==='' ? 'border-b-2 border-gray-900 text-gray-900' : 'text-[#9a9a9a]'"
            class="text-[10px] uppercase tracking-widest px-4 pb-3 -mb-px whitespace-nowrap hover:text-gray-700 transition flex-shrink-0">
      Todo
    </button>
    @foreach($categories as $cat)
    <button @click="filterCat='{{ $cat->id }}'"
            :class="filterCat==='{{ $cat->id }}' ? 'border-b-2 border-[var(--c)] text-[var(--c)]' : 'text-[#9a9a9a]'"
            class="text-[10px] uppercase tracking-widest px-4 pb-3 -mb-px whitespace-nowrap hover:text-gray-700 transition flex-shrink-0">
      {{ $cat->name }}
    </button>
    @endforeach
  </div>

  {{-- Filtros adicionales --}}
  <div class="flex flex-wrap items-center gap-5 mb-10">
    <label class="flex items-center gap-2 text-[10px] uppercase tracking-widest text-[#9a9a9a] cursor-pointer">
      <input type="checkbox" x-model="onSaleFilter" class="rounded-none">
      Solo ofertas
    </label>
    <select x-model="priceFilter" class="text-[10px] uppercase tracking-widest border-b border-gray-100 bg-transparent py-1 focus:outline-none text-[#9a9a9a] cursor-pointer">
      <option value="">Todos los precios</option>
      <option value="0-50">S/ 0 â€“ 50</option>
      <option value="50-150">S/ 50 â€“ 150</option>
      <option value="150-500">S/ 150 â€“ 500</option>
      <option value="500+">S/ 500+</option>
    </select>
    <button @click="filterOpen=true"
            class="xl:hidden flex items-center gap-1.5 text-[10px] uppercase tracking-widest border-b border-gray-100 text-[#9a9a9a] py-1 hover:text-gray-700 transition relative">
      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
      </svg>
      Filtros
      <span x-show="priceFilter!=='' || onSaleFilter"
            class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full text-[10px] font-black flex items-center justify-center text-white"
            style="background:var(--c)"
            x-text="(priceFilter!==''?1:0)+(onSaleFilter?1:0)"></span>
    </button>
    <select x-model="sortBy" class="text-[10px] uppercase tracking-widest border-b border-gray-100 bg-transparent py-1 focus:outline-none text-[#9a9a9a] cursor-pointer">
      <option value="default">Ordenar</option>
      <option value="price_asc">Precio â†‘</option>
      <option value="price_desc">Precio â†“</option>
      <option value="newest">MÃ¡s nuevos</option>
      <option value="name_az">Nombre Aâ†’Z</option>
    </select>
  </div>

  {{-- Grid de productos por categorÃ­a --}}
  @foreach($categories as $cat)
  @if($cat->products->count() > 0)
  <div x-show="filterCat==='' || filterCat==='{{ $cat->id }}'">
    <p class="text-[9px] uppercase tracking-widest text-gray-300 mb-6 mt-8">{{ $cat->name }}</p>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-x-5 gap-y-10" data-products-grid>
      @foreach($cat->products as $p)
      @php
      $qvData = ['id'=>$p->id,'name'=>$p->name,'img'=>$p->mainImage?$p->main_image_url:'','price'=>(float)$p->price,'cp'=>$p->compare_price?(float)$p->compare_price:null,'desc'=>\Str::limit(strip_tags($p->description??''),120),'url'=>route('public.product',[$project->slug,$p->id]),'stock'=>$p->stock];
      @endphp
      <article x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }}) && ({{ $loop->index }} < 8 || expandedCats['{{ $cat->id }}'])"
               class="group relative cursor-pointer"
               data-price="{{ $p->price }}"
               data-ts="{{ $p->created_at ? $p->created_at->timestamp : 0 }}"
               data-idx="{{ $loop->index }}"
               data-qv='@json($qvData)'>
        <div class="relative overflow-hidden" style="aspect-ratio:2/3;background:#f0eeeb;">
          @if($p->mainImage)
          <img src="{{ $p->main_image_url }}"
               alt="{{ $p->name }}"
               class="w-full h-full object-cover transition duration-500 group-hover:scale-105"
               loading="lazy">
          @endif
          <div class="prod-overlay">
            @if(!$isQuoteOnly)
            <button class="prod-btn-hover bg-white text-gray-900 text-[10px] uppercase tracking-widest px-5 py-2.5 hover:bg-gray-900 hover:text-white transition"
                    @click.stop="addToCart({id:{{ $p->id }}, name:'{{ addslashes($p->name) }}', price:{{ $p->price }}, img:'{{ $p->mainImage ? $p->main_image_url : '' }}'})">
              Agregar
            </button>
            @else
            <a class="prod-btn-hover bg-white text-gray-900 text-[10px] uppercase tracking-widest px-5 py-2.5 hover:bg-gray-900 hover:text-white transition"
               href="https://wa.me/{{ $quoteWa }}?text={{ urlencode('Hola, me interesa: '.$p->name) }}"
               target="_blank">
              Cotizar
            </a>
            @endif
          </div>
          @if($p->compare_price && $p->compare_price > $p->price)
          <span class="absolute top-0 left-0 bg-black text-white text-[9px] uppercase tracking-widest px-2.5 py-1">Oferta</span>
          @endif
          @if($p->stock !== null && $p->stock === 0)
          <span class="absolute bottom-2 left-2 bg-red-600 text-white text-[9px] font-bold px-2 py-0.5 uppercase tracking-widest">Agotado</span>
          @elseif($p->stock !== null && $p->stock > 0 && $p->stock <= 5)
          <span class="absolute bottom-2 left-2 bg-amber-500 text-white text-[9px] font-bold px-2 py-0.5 uppercase tracking-widest">Ãšltimas {{ $p->stock }}</span>
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
        <div class="mt-3">
          <p class="text-xs text-gray-700 font-normal leading-snug"><a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="hover:underline">{{ $p->name }}</a></p>
          @if(isset($productRatings) && isset($productRatings[$p->id]))
          <div class="flex items-center gap-1 mb-1">
            <span class="text-amber-400 text-xs">{{ str_repeat('â˜…', floor($productRatings[$p->id]->avg_rating)) }}{{ str_repeat('â˜†', 5 - floor($productRatings[$p->id]->avg_rating)) }}</span>
            <span class="text-[10px] text-gray-400">({{ $productRatings[$p->id]->rating_count }})</span>
          </div>
          @endif
          @if(!$isQuoteOnly || $quotePriceDisp === 'show')
          <div class="flex items-center gap-2 mt-1">
            <span class="text-sm text-[#9a9a9a]">S/ {{ number_format($p->price, 2) }}</span>
            @if($p->compare_price && $p->compare_price > $p->price)
            <span class="text-xs text-gray-300 line-through">S/ {{ number_format($p->compare_price, 2) }}</span>
            @endif
          </div>
          @elseif($isQuoteOnly && $quotePriceDisp === 'hide')
          <p class="text-xs text-[#9a9a9a] mt-1 italic">Consultar precio</p>
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
  <div x-show="noResults" x-cloak class="text-center py-20">
    <p class="font-serif text-2xl text-gray-300 mb-3">Sin resultados</p>
    <p class="text-xs text-[#9a9a9a] mb-6">Intenta con otro tÃ©rmino o categorÃ­a</p>
    <button @click="search=''; filterCat=''; priceFilter=''; onSaleFilter=false"
            class="text-[10px] uppercase tracking-widest border-b border-gray-300 pb-0.5 hover:border-gray-900 text-gray-500 hover:text-gray-900 transition">
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

</section>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     FOOTER
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<footer class="bg-[#fafaf8] border-t border-gray-100 mt-10">
  <div class="max-w-6xl mx-auto px-4 py-16 grid grid-cols-1 md:grid-cols-3 gap-12">
    {{-- Col 1: marca --}}
    <div>
      @if($project->logo_url)
      <img src="{{ asset('storage/'.$project->logo_url) }}" alt="{{ $project->name }}" class="h-8 object-contain mb-5 opacity-60" loading="lazy">
      @else
      <p class="font-serif text-lg tracking-widest uppercase mb-5 text-gray-600">{{ $project->name }}</p>
      @endif
      <p class="text-xs text-[#9a9a9a] font-light leading-relaxed max-w-xs">{{ $seoDesc }}</p>
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}"
         target="_blank" rel="noopener"
         class="inline-flex items-center gap-2 mt-5 text-[10px] uppercase tracking-widest text-white px-4 py-2 transition"
         style="background:#25D366">
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
        </svg>
        WhatsApp
      </a>
      @endif
    </div>
    {{-- Col 2: colecciones --}}
    <div>
      <p class="text-[10px] uppercase tracking-widest text-[#9a9a9a] mb-5">Colecciones</p>
      <ul class="space-y-2.5">
        @foreach($categories as $cat)
        <li>
          <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                  class="text-xs text-[#9a9a9a] font-light hover:text-gray-900 transition">
            {{ $cat->name }}
          </button>
        </li>
        @endforeach
      </ul>
    </div>
    {{-- Col 3: contacto --}}
    <div>
      <p class="text-[10px] uppercase tracking-widest text-[#9a9a9a] mb-5">Contacto</p>
      <ul class="space-y-2.5 text-xs text-[#9a9a9a] font-light">
        @if($project->phone)
        <li>{{ $project->phone }}</li>
        @endif
        @if($project->address)
        <li>{{ $project->address }}</li>
        @endif
        @if($settings['facebook_url'] ?? '')
        <li><a href="{{ $settings['facebook_url'] }}" target="_blank" rel="noopener" class="hover:text-gray-900 transition">Facebook</a></li>
        @endif
        @if($settings['instagram_url'] ?? '')
        <li><a href="{{ $settings['instagram_url'] }}" target="_blank" rel="noopener" class="hover:text-gray-900 transition">Instagram</a></li>
        @endif
      </ul>
    </div>
  </div>
  <div class="border-t border-gray-100 py-6 text-center">
    <p class="text-[9px] uppercase tracking-widest text-gray-300">Â© {{ date('Y') }} {{ $project->name }} Â· CatÃ¡logo online por AVAN</p>
  </div>
</footer>

{{-- BotÃ³n flotante WhatsApp --}}
@if($project->whatsapp)
<a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}?text={{ urlencode($settings['whatsapp_msg'] ?? 'Hola, quisiera mÃ¡s informaciÃ³n sobre sus productos') }}"
   target="_blank" rel="noopener"
   class="fixed bottom-6 right-6 z-50 w-12 h-12 rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition"
   style="background:#25D366"
   aria-label="Contactar por WhatsApp">
  <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 24 24">
    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
  </svg>
</a>
@endif

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     ALPINE STORE
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<script>
function store() {
  const _cartKey = 'boutique_cart_{{ $project->id }}';
  const _formKey = 'boutique_form_{{ $project->id }}';
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
    cart: _savedCart,
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
    showSearch: false,
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
    form: _savedForm,
    orderLoading: false,
    orderError: '',
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
    payReference: '',
    payError: '',
    payLoading: false,
    noResults: false,

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

      // DetecciÃ³n de retorno desde Mercado Pago
      const urlParams = new URLSearchParams(window.location.search);
      const payStatus  = urlParams.get('payment');
      const payOrderId = urlParams.get('order');
      if (payStatus && payOrderId) {
        this.orderId     = parseInt(payOrderId) || 0;
        this.orderTotal  = 0;
        if (payStatus === 'success' || payStatus === 'approved') {
          this.orderSent    = true;
          this.drawerOpen   = true;
          this.drawerStep   = 3;
          this.payReference = urlParams.get('payment_id') || 'mp-ok';
          try { localStorage.removeItem(this._cartKey); localStorage.removeItem(this._formKey); } catch(e) {}
        } else if (payStatus === 'failure') {
          this.payError   = 'El pago fue rechazado. Intenta con otro mÃ©todo.';
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
      const matchSearch = this.search === '' || name.includes(this.search.toLowerCase().trim());
      let matchPrice = true;
      if (this.priceFilter === '0-50')     matchPrice = price <= 50;
      if (this.priceFilter === '50-150')   matchPrice = price > 50  && price <= 150;
      if (this.priceFilter === '150-500')  matchPrice = price > 150 && price <= 500;
      if (this.priceFilter === '500+')     matchPrice = price > 500;
      const matchSale = !this.onSaleFilter || (comparePrice && comparePrice > price);
      const visible = matchSearch && matchPrice && matchSale;
      return visible;
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
        name:       i.name,
        price:      i.price,
        quantity:   i.qty,
      }));
      try {
        const res = await fetch('/{{ $project->slug }}/order', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
          body:    JSON.stringify({
            client_name:  this.form.name,
            client_phone: this.form.phone,
            client_email: this.form.email,
            notes:        this.form.notes,
            coupon_code:  this.couponApplied?this.couponApplied.code:null,
            delivery_address: this.form.address||null,
            shipping_cost: this.effectiveShipping>0?this.effectiveShipping:null,
            items:        items,
          }),
        });
        const data = await res.json();
        if (data.ok) {
          @if($isQuoteOnly && $quoteWa)
          this.sendQuoteWhatsapp();
          @elseif($isQuoteOnly)
          this.orderId    = data.order_id;
          this.orderTotal = data.total;
          this.orderSent  = true;
          this.drawerStep = 3;
          @else
          this.orderId      = data.order_id;
          this.orderTotal   = data.total;
          this.orderSent    = false;
          this.payReference = '';
          this.payError     = '';
          @if($hasOnlinePayment)
          this.drawerStep = 3;
          @else
          try { localStorage.removeItem(this._cartKey); localStorage.removeItem(this._formKey); } catch(e) {}
          window.location.href = '/{{ $project->slug }}/thanks/' + data.order_id;
          @endif
          @endif
        } else {
          this.orderError = 'No se pudo enviar el pedido. IntÃ©ntalo de nuevo.';
        }
      } catch(e) {
        this.orderError = 'Error de conexiÃ³n. Verifica tu internet e intÃ©ntalo de nuevo.';
      }
      this.orderLoading = false;
    },

    sendQuoteWhatsapp() {
      const businessName = '{{ addslashes($project->name) }}';
      const customMsg    = '{{ addslashes($settings['quote_wa_msg'] ?? 'Hola, me interesa cotizar los siguientes productos:') }}';
      const fecha = new Date().toLocaleDateString('es-PE', { day:'2-digit', month:'long', year:'numeric' });
      let lines = '';
      lines += '*SOLICITUD DE COTIZACIÃ“N*\n';
      lines += 'â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”\n';
      lines += `*${businessName}*\n\n`;
      lines += `${customMsg}\n\n`;
      lines += `*Nombre:* ${this.form.name}\n`;
      if (this.form.phone) lines += `*TelÃ©fono:* ${this.form.phone}\n`;
      lines += '\n*PRODUCTOS*\n';
      lines += 'â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”\n';
      let total = 0;
      this.cart.forEach((item, idx) => {
        @if(!$isQuoteOnly || $quotePriceDisp === 'show')
        const sub = (item.price * item.qty).toFixed(2);
        lines += `${idx+1}. *${item.name}* â€” cant: ${item.qty}  â€¢  S/ ${sub}\n`;
        total += item.price * item.qty;
        @else
        lines += `${idx+1}. *${item.name}* â€” cant: ${item.qty}\n`;
        @endif
      });
      @if(!$isQuoteOnly || $quotePriceDisp === 'show')
      lines += `â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”\n`;
      lines += `*Total referencial: S/ ${total.toFixed(2)}*\n`;
      @endif
      if (this.form.notes) lines += `\n*Nota:* ${this.form.notes}\n`;
      lines += `\n_Fecha: ${fecha}_`;
      const url = `https://wa.me/{{ $quoteWa }}?text=${encodeURIComponent(lines)}`;
      window.open(url, '_blank');
      this.cart      = [];
      this.orderSent = true;
      this.drawerStep = 3;
      try { localStorage.removeItem(this._cartKey); localStorage.removeItem(this._formKey); } catch(e) {}
    },

    async confirmManualPay() {
      if (!this.payReference.trim()) return;
      this.payLoading = true;
      this.payError   = '';
      try {
        const res = await fetch(`{{ url('/' . $project->slug . '/pay') }}/${this.orderId}/manual`, {
          method:  'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
          body:    JSON.stringify({ reference: this.payReference }),
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
          desctext:   'Pago seguro Â· Pedido #' + self.orderId,
        },
        paymentMethods: { tarjeta: true, yape: true, billetera: false, cuotealo: false },
      });
      Culqi.open();
      window.culqi = async function() {
        if (Culqi.token) {
          self.payLoading = true;
          self.payError   = '';
          try {
            const res = await fetch(`{{ url('/' . $project->slug . '/pay') }}/${self.orderId}/culqi`, {
              method:  'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
              body:    JSON.stringify({ token: Culqi.token.id, email }),
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
          method:  'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
          body:    JSON.stringify({ email: this.form.email || null }),
        });
        const data = await res.json();
        if (data.ok && data.init_point) {
          const url = (data.is_sandbox && data.sandbox_init_point) ? data.sandbox_init_point : data.init_point;
          window.location.href = url;
        } else {
          this.payError = data.message || 'No se pudo iniciar el pago con Mercado Pago. IntÃ©ntalo de nuevo.';
        }
      } catch(e) {
        this.payError = 'Error de conexiÃ³n.';
      }
      this.payLoading = false;
    },
    @endif
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


