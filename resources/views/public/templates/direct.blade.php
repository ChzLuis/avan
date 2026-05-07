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
$primaryColor     = $settings['primary_color'] ?? '#4f46e5';
$isQuoteOnly      = ($settings['store_mode'] ?? 'direct') === 'quote_only';
$shippingEnabled  = ($settings['shipping_enabled']  ?? '0') === '1';
$shippingCost     = (float)($settings['shipping_cost']      ?? 0);
$shippingFreeFrom = (float)($settings['shipping_free_from'] ?? 0);
$requireAddress   = ($settings['require_address']   ?? '0') === '1';
$quotePriceDisp   = $settings['quote_price_display'] ?? 'show';
$wholesaleEnabled = ($settings['wholesale_enabled'] ?? '0') === '1';
$quoteWaRaw       = preg_replace('/\D/', '', $settings['quote_whatsapp'] ?? $project->whatsapp ?? '');
$quoteWaCountry   = $settings['quote_whatsapp_country'] ?? '51';
$quoteWa          = $quoteWaRaw ? (str_starts_with($quoteWaRaw, $quoteWaCountry) ? $quoteWaRaw : $quoteWaCountry.$quoteWaRaw) : '';
$quoteWaMsg       = $settings['quote_wa_msg'] ?? 'Hola, me interesa cotizar los siguientes productos:';
$canonicalUrl     = url('/' . $project->slug);
$logoRaw          = $settings['logo_url'] ?? '';
$logoUrl          = $logoRaw ? (str_starts_with($logoRaw, 'http') ? $logoRaw : asset('storage/'.$logoRaw)) : null;
$logoHeight       = (int)($settings['logo_height'] ?? 40);
$seoTitle         = ($settings['seo_title'] ?? null) ?: ($project->name . ' — Catálogo Online');
$seoDesc          = ($settings['seo_description'] ?? null) ?: ($project->description ?? '');
$currency         = $settings['currency'] ?? 'S/';
$secondaryColor   = $settings['secondary_color'] ?? '#818cf8';
$fontTitle        = $settings['font_title'] ?? $settings['font'] ?? 'Inter';
$fontBody         = $settings['font_body']  ?? $settings['font'] ?? 'Inter';
$borderRadius     = ['sharp'=>'4px','rounded'=>'8px','pill'=>'20px'][$settings['border_radius'] ?? 'rounded'] ?? '8px';
$faviconUrl       = $settings['favicon_url'] ?? '';
$footerCopyright  = $settings['footer_copyright'] ?? ('© ' . date('Y') . ' ' . $project->name);
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
@if($faviconUrl)<link rel="icon" href="{{ $faviconUrl }}">@endif

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@if($culqiEnabled && $culqiPublicKey)
<script src="https://checkout.culqi.com/js/v4"></script>
@endif

<style>
:root {
  --c: {{ $primaryColor }};
  --c2: {{ $secondaryColor }};
  --radius: {{ $borderRadius }};
  --font-body: '{{ $fontBody }}', sans-serif;
}
* { font-family: var(--font-body); }
[x-cloak] { display: none !important; }
body { background: #f8fafc; }

.btn-gc { background: var(--c); color: #fff; transition: filter .2s; border-radius: var(--radius); }
.btn-gc:hover { filter: brightness(.88); }
.btn-outline-gc { border: 2px solid var(--c); color: var(--c); background: transparent; transition: all .2s; border-radius: var(--radius); }
.btn-outline-gc:hover { background: var(--c); color: #fff; }
.gc { color: var(--c); }
.bg-gc { background: var(--c); }

/* Cards */
.d-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
  transition: all .2s;
}
.d-card:hover {
  border-color: var(--c);
  box-shadow: 0 4px 20px rgba(0,0,0,.08);
  transform: translateY(-2px);
}
.d-card .card-img {
  aspect-ratio: 1/1;
  overflow: hidden;
  background: #f1f5f9;
}
.d-card .card-img img { width:100%;height:100%;object-fit:cover;transition:transform .3s; }
.d-card:hover .card-img img { transform: scale(1.05); }

/* Sidebar */
.scat-btn {
  display: flex; align-items: center; gap: 8px;
  width: 100%; text-align: left;
  padding: 7px 10px; border-radius: 8px;
  font-size: .8rem; border: none; background: transparent;
  color: #374151; cursor: pointer; transition: all .15s;
}
.scat-btn:hover { background: #f3f4f6; }
.scat-btn.active { background: var(--c); color: #fff !important; }

/* Drawer */
.drawer-overlay { position:fixed;inset:0;z-index:50;background:rgba(0,0,0,.4);backdrop-filter:blur(3px); }
.drawer-panel {
  position:fixed;right:0;top:0;bottom:0;z-index:51;
  width:100%;max-width:420px;background:#fff;
  display:flex;flex-direction:column;
  box-shadow:-6px 0 32px rgba(0,0,0,.12);
  border-radius:16px 0 0 16px;
}
@media(max-width:640px){ .drawer-panel{ max-width:100%;border-radius:0; } }
.scrollbar-hide::-webkit-scrollbar { display:none; }
.scrollbar-hide { -ms-overflow-style:none;scrollbar-width:none; }
html { scroll-behavior:smooth; }
</style>

@php
$searchIndex = $categories->flatMap(function($cat) use ($project) {
    $rows = $cat->products->map(fn($p) => [
        'id'       => $p->id,
        'name'     => $p->name,
        'price'    => (float)$p->price,
        'cp'       => $p->compare_price ? (float)$p->compare_price : null,
        'img'      => $p->main_image_url,
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
        'img'      => $p->main_image_url,
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
     class="fixed top-20 left-1/2 -translate-x-1/2 z-[60] bg-gray-900 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-lg whitespace-nowrap pointer-events-none"
     x-text="toastMsg">
</div>

{{-- ─── DRAWER ─────────────────────────────────────────────────────────────── --}}
<div x-show="drawerOpen" class="drawer-overlay" @click="drawerOpen=false" x-cloak></div>
<div x-show="drawerOpen" x-cloak class="drawer-panel"
     x-transition:enter="transition ease-out duration-300 transform"
     x-transition:enter-start="translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in duration-200 transform"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="translate-x-full">

  <div class="flex items-center justify-between px-5 py-4 border-b flex-shrink-0">
    <div x-show="drawerStep===1">
      <h2 class="font-bold text-gray-900">{{ $isQuoteOnly ? 'Mi cotización' : 'Mi pedido' }}</h2>
      <p class="text-xs text-gray-400" x-text="cart.reduce((s,i)=>s+i.qty,0) + ' producto(s)'"></p>
    </div>
    <div x-show="drawerStep===2"><h2 class="font-bold text-gray-900">Tus datos</h2></div>
    <div x-show="drawerStep===3"><h2 class="font-bold text-gray-900">Pago</h2></div>
    <button @click="drawerOpen=false" class="p-2 rounded-lg hover:bg-gray-100 transition">
      <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  </div>

  {{-- Step 1: carrito --}}
  <div x-show="drawerStep===1" class="flex-1 overflow-y-auto px-4 py-3 space-y-3 scrollbar-hide">
    <template x-if="cart.length===0">
      <div class="text-center py-16">
        <svg class="w-14 h-14 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <p class="text-gray-400 text-sm">{{ $isQuoteOnly ? 'Tu cotización está vacía' : 'Tu carrito está vacío' }}</p>
      </div>
    </template>
    <template x-for="item in cart" :key="item.id">
      <div class="flex items-center gap-3 bg-gray-50 rounded-xl p-3">
        <div class="w-14 h-14 rounded-lg bg-gray-200 overflow-hidden flex-shrink-0">
          <img x-show="item.img" :src="item.img" :alt="item.name" class="w-full h-full object-cover">
          <div x-show="!item.img" class="w-full h-full flex items-center justify-center">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-gray-800 leading-snug line-clamp-2" x-text="item.name"></p>
          <p class="text-sm font-bold mt-0.5" style="color:var(--c)" x-text="'{{ $currency }} ' + (item.price * item.qty).toFixed(2)"></p>
        </div>
        <div class="flex items-center gap-1.5 flex-shrink-0">
          <button @click="item.qty>(item.min_qty||1)?item.qty--:cart.splice(cart.indexOf(item),1)"
                  class="w-7 h-7 rounded-full border border-gray-200 flex items-center justify-center text-gray-500 hover:border-red-300 hover:text-red-500 transition text-sm font-bold">−</button>
          <span class="w-7 text-center text-sm font-bold text-gray-800" x-text="item.qty"></span>
          <button @click="item.qty++"
                  class="w-7 h-7 rounded-full flex items-center justify-center text-white text-sm font-bold transition" style="background:var(--c)">+</button>
        </div>
      </div>
    </template>
  </div>

  <div x-show="drawerStep===1 && cart.length>0" class="px-4 py-4 border-t flex-shrink-0 space-y-2">
    <div class="flex justify-between text-sm text-gray-600">
      <span>Subtotal</span>
      <span class="font-semibold" x-text="'{{ $currency }} ' + subtotal.toFixed(2)"></span>
    </div>
    @if($shippingEnabled)
    <div class="flex justify-between text-sm text-gray-600">
      <span>Envío</span>
      <span x-text="effectiveShipping===0 ? 'Gratis' : '{{ $currency }} ' + effectiveShipping.toFixed(2)" class="font-semibold"></span>
    </div>
    @endif
    <div class="flex justify-between font-bold text-base border-t pt-2">
      <span>Total</span>
      <span style="color:var(--c)" x-text="'{{ $currency }} ' + orderGrandTotal.toFixed(2)"></span>
    </div>
    <button @click="drawerStep=2" class="w-full btn-gc py-3 text-sm font-bold">
      {{ $isQuoteOnly ? 'Continuar →' : 'Continuar →' }}
    </button>
  </div>

  {{-- Step 2: datos --}}
  <div x-show="drawerStep===2" class="flex-1 overflow-y-auto px-4 py-4 scrollbar-hide">
    <div class="space-y-3">
      <div>
        <label class="text-xs font-semibold text-gray-600 mb-1 block">Nombre *</label>
        <input type="text" x-model="form.name" placeholder="Tu nombre completo"
               class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-[var(--c)] transition">
      </div>
      <div>
        <label class="text-xs font-semibold text-gray-600 mb-1 block">Teléfono *</label>
        <input type="tel" x-model="form.phone" placeholder="Ej: 987654321"
               class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-[var(--c)] transition">
      </div>
      <div>
        <label class="text-xs font-semibold text-gray-600 mb-1 block">Correo (opcional)</label>
        <input type="email" x-model="form.email" placeholder="tucorreo@gmail.com"
               class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-[var(--c)] transition">
      </div>
      @if($requireAddress)
      <div>
        <label class="text-xs font-semibold text-gray-600 mb-1 block">Dirección de entrega</label>
        <input type="text" x-model="form.address" placeholder="Av. Principal 123, distrito"
               class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-[var(--c)] transition">
      </div>
      @endif
      <div>
        <label class="text-xs font-semibold text-gray-600 mb-1 block">Notas (opcional)</label>
        <textarea x-model="form.notes" rows="2" placeholder="Indicaciones especiales..."
                  class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-[var(--c)] transition resize-none"></textarea>
      </div>
      <p x-show="orderError" x-text="orderError" class="text-red-500 text-xs font-semibold"></p>
    </div>
  </div>
  <div x-show="drawerStep===2" class="px-4 py-4 border-t flex-shrink-0 space-y-2">
    <button @click="submitOrder()" :disabled="orderLoading"
            class="w-full btn-gc py-3 text-sm font-bold flex items-center justify-center gap-2 disabled:opacity-60">
      <svg x-show="orderLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
      <span x-text="orderLoading ? 'Enviando...' : '{{ $isQuoteOnly ? 'Enviar cotización' : 'Confirmar pedido' }}'"></span>
    </button>
    <button @click="drawerStep=1" class="w-full py-2 text-sm text-gray-500 hover:text-gray-700 transition">← Volver al carrito</button>
  </div>

  {{-- Step 3: pago --}}
  <div x-show="drawerStep===3 && !orderSent" class="flex-1 overflow-y-auto px-4 py-4 scrollbar-hide space-y-3">
    <div class="bg-gray-50 rounded-xl p-3 flex justify-between items-center">
      <span class="text-sm text-gray-600">Total a pagar</span>
      <span class="font-bold text-lg" style="color:var(--c)" x-text="'{{ $currency }} ' + orderGrandTotal.toFixed(2)"></span>
    </div>
    @if($payManualEnabled)
    @foreach($payManualMethods as $method)
    @if($method==='yape' && $payYapeNumber)
    <button @click="selectedPayMethod='yape'"
            :class="selectedPayMethod==='yape'?'border-[var(--c)] bg-[#5c18aa11]':'border-gray-200'"
            class="w-full border-2 rounded-xl p-3 flex items-center gap-3 transition text-left">
      <div class="w-9 h-9 rounded-lg bg-[#5c18aa] flex items-center justify-center flex-shrink-0">
        <span class="text-white font-black text-xs">Y</span>
      </div>
      <div>
        <p class="font-semibold text-sm text-gray-800">Yape</p>
        <p class="text-xs text-gray-500">{{ $payYapeNumber }}</p>
      </div>
    </button>
    @endif
    @if($method==='plin' && $payPlinNumber)
    <button @click="selectedPayMethod='plin'"
            :class="selectedPayMethod==='plin'?'border-[var(--c)]':'border-gray-200'"
            class="w-full border-2 rounded-xl p-3 flex items-center gap-3 transition text-left">
      <div class="w-9 h-9 rounded-lg bg-[#00a859] flex items-center justify-center flex-shrink-0">
        <span class="text-white font-black text-xs">P</span>
      </div>
      <div>
        <p class="font-semibold text-sm text-gray-800">Plin</p>
        <p class="text-xs text-gray-500">{{ $payPlinNumber }}</p>
      </div>
    </button>
    @endif
    @if($method==='bank' && $payBankDetails)
    <button @click="selectedPayMethod='bank'"
            :class="selectedPayMethod==='bank'?'border-[var(--c)]':'border-gray-200'"
            class="w-full border-2 rounded-xl p-3 flex items-center gap-3 transition text-left">
      <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center flex-shrink-0">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
        </svg>
      </div>
      <div>
        <p class="font-semibold text-sm text-gray-800">Transferencia bancaria</p>
        <p class="text-xs text-gray-500 line-clamp-1">{{ $payBankDetails }}</p>
      </div>
    </button>
    @endif
    @endforeach

    <div x-show="selectedPayMethod" class="space-y-2">
      <label class="text-xs font-semibold text-gray-600 block">Número de operación / referencia</label>
      <input type="text" x-model="payReference" placeholder="Ej: 123456789"
             class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-[var(--c)] transition">
      <button @click="confirmManualPay()" :disabled="!payReference.trim() || payLoading"
              class="w-full btn-gc py-3 text-sm font-bold disabled:opacity-50">
        Confirmar pago
      </button>
    </div>
    @endif

    @if($culqiEnabled && $culqiPublicKey)
    <button @click="openCulqi()" class="w-full btn-gc py-3 text-sm font-bold">
      Pagar con tarjeta / Yape
    </button>
    @endif

    @if($mpEnabled)
    <button @click="openMercadoPago()" :disabled="payLoading"
            class="w-full py-3 rounded-xl text-sm font-bold text-white transition flex items-center justify-center gap-2 disabled:opacity-60"
            style="background:#009ee3">
      Pagar con Mercado Pago
    </button>
    @endif

    <p x-show="payError" x-text="payError" class="text-red-500 text-xs font-semibold text-center"></p>
  </div>

  {{-- Confirmación --}}
  <div x-show="orderSent" class="flex-1 flex flex-col items-center justify-center px-6 py-10 text-center">
    <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4" style="background:color-mix(in srgb,var(--c) 15%,white)">
      <svg class="w-8 h-8" style="color:var(--c)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
      </svg>
    </div>
    <h3 class="font-black text-gray-900 text-lg mb-2">{{ $isQuoteOnly ? '¡Cotización enviada!' : '¡Pedido confirmado!' }}</h3>
    <p class="text-sm text-gray-500 mb-6">Te contactaremos pronto para coordinar la entrega.</p>
    <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};payReference='';drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');localStorage.removeItem('avan_form_{{ $project->id }}');}catch(e){}"
            class="w-full btn-gc py-3 text-sm font-bold">
      Seguir comprando
    </button>
  </div>

</div>
{{-- /DRAWER --}}


{{-- ─── HEADER ─────────────────────────────────────────────────────────────── --}}
<header class="bg-white border-b border-gray-200 sticky top-0 z-40 shadow-sm">
  <div class="max-w-7xl mx-auto px-4 h-14 flex items-center gap-4">

    {{-- Logo --}}
    <a href="{{ $canonicalUrl }}" class="flex items-center gap-2.5 flex-shrink-0">
      @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $project->name }}"
             style="height:{{ $logoHeight }}px; max-height:60px" class="object-contain">
      @else
        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:var(--c)">
          <span class="text-white font-black text-sm">{{ mb_strtoupper(mb_substr($project->name,0,1)) }}</span>
        </div>
      @endif
      <span class="font-bold text-gray-800 text-sm hidden sm:block truncate max-w-[140px]">{{ $project->name }}</span>
    </a>

    {{-- Buscador central --}}
    <div class="flex-1 max-w-lg mx-auto relative" @click.outside="searchOpen=false">
      <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
      </svg>
      <input type="text" x-model="search" placeholder="Buscar producto..."
             @input="searchOpen = search.trim().length >= 2; searchIdx = -1"
             @keydown.arrow-down.prevent="if(suggestions.length){searchIdx=(searchIdx+1)%suggestions.length}"
             @keydown.arrow-up.prevent="if(suggestions.length){searchIdx=(searchIdx-1+suggestions.length)%suggestions.length}"
             @keydown.enter.prevent="if(searchIdx>=0){selectSuggestion(suggestions[searchIdx])}else{searchOpen=false}"
             @keydown.escape="searchOpen=false;searchIdx=-1"
             class="w-full border border-gray-200 rounded-full py-2 pl-9 pr-4 text-sm focus:outline-none focus:border-[var(--c)] transition bg-gray-50 focus:bg-white">
      {{-- Predictive dropdown --}}
      <div x-show="searchOpen && suggestions.length > 0" x-cloak
           x-transition:enter="transition ease-out duration-100"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           class="absolute left-0 right-0 top-full mt-1 bg-white rounded-xl shadow-2xl border border-gray-200 z-[200] overflow-hidden">
        <template x-for="(p, i) in suggestions" :key="p.id">
          <button @click="selectSuggestion(p)"
                  :class="searchIdx===i?'bg-gray-50':''"
                  class="flex items-center gap-3 w-full px-4 py-2.5 hover:bg-gray-50 transition text-left border-b border-gray-100 last:border-0">
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
              <p class="text-xs text-gray-400" x-text="p.cat"></p>
            </div>
            <p class="text-sm font-bold flex-shrink-0" style="color:var(--c)" x-text="'{{ $currency }} ' + p.price.toFixed(2)"></p>
          </button>
        </template>
      </div>
    </div>

    {{-- Carrito --}}
    <button @click="drawerOpen=true; drawerStep=1"
            class="relative flex-shrink-0 p-2 rounded-lg hover:bg-gray-100 transition" style="color:var(--c)">
      @if($isQuoteOnly)
      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
      @else
      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
      </svg>
      @endif
      <span x-show="cart.length>0" x-text="cart.reduce((s,i)=>s+i.qty,0)"
            class="absolute -top-1 -right-1 w-5 h-5 rounded-full text-white text-[10px] font-bold flex items-center justify-center bg-gc"></span>
    </button>

  </div>
</header>


{{-- ─── CUERPO: SIDEBAR + CATÁLOGO ─────────────────────────────────────────── --}}
<div id="catalogo" class="max-w-7xl mx-auto px-4 py-6 flex gap-6">

  {{-- ── Sidebar desktop ─────────────────────── --}}
  <aside class="hidden lg:block w-56 flex-shrink-0">
    <div class="bg-white border border-gray-200 rounded-xl p-4 sticky top-20 space-y-5">

      {{-- Categorías --}}
      <div>
        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1 px-1">Categorías</p>
        <ul class="divide-y divide-gray-100">
          {{-- Todos --}}
          <li>
            <button @click="filterCat=''; filterParent=''"
                    class="w-full flex items-center justify-between px-1 py-2.5 text-left transition-colors hover:text-[var(--c)]"
                    :class="filterCat==='' ? 'text-[var(--c)] font-bold' : 'text-gray-700 font-medium'">
              <span class="text-sm">Todos</span>
            </button>
          </li>
          @foreach($categories as $cat)
          @php
            $catTotal = $cat->products->count() + $cat->children->sum(fn($s) => $s->products->count());
          @endphp
          <li x-data="{ open: filterParent==='{{ $cat->id }}' }">
            {{-- Fila padre --}}
            <button @click="filterCat='{{ $cat->id }}'; filterParent='{{ $cat->id }}'; @if($cat->children->isNotEmpty()) open=true; @endif"
                    class="w-full flex items-center justify-between px-1 py-2.5 text-left transition-colors hover:text-[var(--c)] group"
                    :class="filterParent==='{{ $cat->id }}' ? 'text-[var(--c)] font-bold' : 'text-gray-700 font-medium'">
              <span class="text-sm uppercase tracking-wide font-semibold">{{ $cat->name }}</span>
              <div class="flex items-center gap-1.5 flex-shrink-0">
                @if($catTotal > 0)
                <span class="text-[10px] opacity-40">{{ $catTotal }}</span>
                @endif
                @if($cat->children->isNotEmpty())
                <svg @click.stop="open=!open" class="w-3.5 h-3.5 transition-transform duration-150 opacity-40 group-hover:opacity-70 cursor-pointer"
                     :class="open ? 'rotate-90' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
                @endif
              </div>
            </button>
            {{-- Hijos --}}
            @if($cat->children->isNotEmpty())
            <div x-show="open" x-collapse>
              <ul class="divide-y divide-gray-50 pl-3 border-l-2 border-gray-100 ml-1 mb-1">
                @foreach($cat->children as $sub)
                <li>
                  <button @click="filterCat='{{ $sub->id }}'; filterParent='{{ $cat->id }}'"
                          class="w-full flex items-center justify-between px-2 py-2 text-left transition-colors hover:text-[var(--c)]"
                          :class="filterCat==='{{ $sub->id }}' ? 'text-[var(--c)] font-semibold' : 'text-gray-500'">
                    <span class="text-xs">{{ $sub->name }}</span>
                    @if($sub->products->count() > 0)
                    <span class="text-[10px] opacity-40 flex-shrink-0">{{ $sub->products->count() }}</span>
                    @endif
                  </button>
                </li>
                @endforeach
              </ul>
            </div>
            @endif
          </li>
          @endforeach
        </ul>
      </div>

      {{-- Precio --}}
      <div class="border-t border-gray-100 pt-4">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Precio</p>
        <ul class="space-y-1">
          @foreach([
            ['' ,         'Todos'],
            ['0-20',      'Hasta '.$currency.' 20'],
            ['20-50',     $currency.' 20 – 50'],
            ['50-200',    $currency.' 50 – 200'],
            ['200+',      'Más de '.$currency.' 200'],
          ] as [$val,$lbl])
          <li>
            <button @click="priceFilter='{{ $val }}'"
                    :class="priceFilter==='{{ $val }}'?'font-bold':'text-gray-500'"
                    class="text-sm w-full text-left px-2 py-1 rounded-lg hover:bg-gray-50 transition"
                    :style="priceFilter==='{{ $val }}'?'color:var(--c)':''">
              {{ $lbl }}
            </button>
          </li>
          @endforeach
        </ul>
      </div>

      {{-- Solo ofertas --}}
      <div class="border-t border-gray-100 pt-4">
        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-600">
          <input type="checkbox" x-model="onSaleFilter" class="rounded w-4 h-4" style="accent-color:var(--c)">
          Solo ofertas
        </label>
      </div>

    </div>
  </aside>

  {{-- ── Área principal ──────────────────────── --}}
  <div class="flex-1 min-w-0">

    {{-- Barra superior: contador + ordenar + filtros mobile --}}
    <div class="flex items-center justify-between mb-4">
      <p class="text-sm text-gray-500">
        <span class="font-semibold text-gray-800" x-text="visibleCount"></span> productos
        @foreach($categories as $cat)
        <span x-show="filterCat==='{{ $cat->id }}'" class="text-xs ml-1">en <strong>{{ $cat->name }}</strong></span>
        @endforeach
      </p>
      <div class="flex items-center gap-2">
        {{-- Ordenar --}}
        <select x-model="sortBy" @change="applySort()"
                class="border border-gray-200 rounded-lg py-1.5 px-2.5 text-xs text-gray-600 focus:outline-none focus:border-[var(--c)] bg-white">
          <option value="default">Relevancia</option>
          <option value="price_asc">Precio: menor a mayor</option>
          <option value="price_desc">Precio: mayor a menor</option>
          <option value="newest">Más nuevos</option>
          <option value="name_az">Nombre A–Z</option>
        </select>
        {{-- Filtros mobile --}}
        <button @click="filterOpen=true" class="lg:hidden flex items-center gap-1.5 border border-gray-200 rounded-lg py-1.5 px-2.5 text-xs text-gray-600 hover:border-[var(--c)] transition">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
          </svg>
          Filtros
          <span x-show="priceFilter!==''||onSaleFilter" class="w-4 h-4 rounded-full text-white text-[9px] font-bold flex items-center justify-center bg-gc"></span>
        </button>
      </div>
    </div>

    {{-- Grid de productos por categoría --}}
    @foreach($categories as $cat)
    @php $catAllProducts = $cat->products->concat($cat->children->flatMap(fn($s) => $s->products->all())); @endphp
    @if($catAllProducts->count() > 0)
    <div x-show="filterCat==='' || filterCat==='{{ $cat->id }}' || filterParent==='{{ $cat->id }}' || [{{ $cat->children->pluck('id')->map(fn($id)=>"'".$id."'")->join(',') }}].includes(filterCat)">
      <h2 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
        <span class="w-6 h-6 rounded-md flex items-center justify-center text-[10px] font-black text-white flex-shrink-0" style="background:var(--c)">
          {{ mb_strtoupper(mb_substr($cat->name,0,1)) }}
        </span>
        {{ $cat->name }}
        <span class="text-xs text-gray-400 font-normal">({{ $catAllProducts->count() }})</span>
      </h2>
      <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 mb-8" data-products-grid>
        @foreach($cat->products as $idx => $p)
        @php
        $qvData = ['id'=>$p->id,'name'=>$p->name,'img'=>$p->main_image_url??'','price'=>(float)$p->price,'cp'=>$p->compare_price?(float)$p->compare_price:null,'desc'=>\Str::limit(strip_tags($p->description??''),120),'url'=>route('public.product',[$project->slug,$p->id]),'stock'=>$p->stock];
        @endphp
        <article
          x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }}, '{{ $cat->id }}', null)"
          class="d-card group"
          data-price="{{ $p->price }}"
          data-name="{{ strtolower($p->name) }}"
          data-ts="{{ $p->created_at?->timestamp ?? 0 }}"
          data-idx="{{ $idx }}"
          data-qv='@json($qvData)'>
          <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="block card-img relative">
            @if($p->mainImage)
            <img src="{{ $p->main_image_url }}" alt="{{ $p->name }}"
                 class="w-full h-full object-cover" loading="lazy">
            @else
            <div class="w-full h-full flex items-center justify-center text-gray-300">
              <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
            @endif
            @if($p->compare_price && $p->compare_price > $p->price)
            <span class="absolute top-2 left-2 bg-red-500 text-white text-[9px] font-bold uppercase px-2 py-0.5 rounded-full">Oferta</span>
            @endif
            <button @click.prevent="const d=$el.closest('[data-qv]');if(d){qv=JSON.parse(d.dataset.qv);qvOpen=true}"
                    class="absolute inset-0 flex items-end justify-center pb-3 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none group-hover:pointer-events-auto z-10">
              <span class="bg-black/70 text-white text-xs font-bold px-3 py-1.5 rounded-full backdrop-blur-sm">Vista rápida</span>
            </button>
          </a>
          <div class="p-3">
            <a href="{{ route('public.product', [$project->slug, $p->id]) }}"
               class="text-gray-800 text-xs font-semibold line-clamp-2 hover:underline block mb-1.5">{{ $p->name }}</a>
            @php $hasWholesale = $wholesaleEnabled && $p->wholesale_price && $p->wholesale_min_qty; @endphp
            @if($hasWholesale)
            {{-- Bloque minorista --}}
            <div x-data="{ qty:1 }" class="rounded-lg border border-gray-200 bg-white overflow-hidden mb-1.5">
              <div class="flex items-center justify-between px-2 py-1 bg-gray-50 border-b border-gray-100">
                <span class="text-[10px] font-bold text-gray-500 uppercase">Minorista</span>
                <span class="font-bold text-sm" style="color:var(--c)">{{ $currency }} {{ number_format($p->price,2) }}</span>
              </div>
              <div class="flex items-center gap-1 p-1.5">
                <div class="flex items-center border border-gray-200 rounded overflow-hidden">
                  <button @click="qty>1?qty--:null" class="px-2 py-1 text-xs bg-gray-50 hover:bg-gray-100">−</button>
                  <span class="px-2 text-xs font-semibold" x-text="qty"></span>
                  <button @click="qty++" class="px-2 py-1 text-xs bg-gray-50 hover:bg-gray-100">+</button>
                </div>
                <button class="flex-1 py-1.5 text-[11px] font-semibold btn-gc"
                        @click="addToCart({id:{{ $p->id }},name:'{{ addslashes($p->name) }}',price:{{ $p->price }},qty:qty,img:'{{ $p->mainImage ? $p->main_image_url : '' }}'})">
                  + Agregar
                </button>
              </div>
            </div>
            {{-- Bloque mayorista --}}
            <div x-data="{ qty:{{ (int)$p->wholesale_min_qty }} }" class="rounded-lg border border-amber-400 overflow-hidden">
              <div class="flex items-center justify-between px-2 py-1 bg-amber-50 border-b border-amber-100">
                <span class="text-[10px] font-bold text-amber-700 uppercase">Mayor · mín {{ (int)$p->wholesale_min_qty }} {{ $p->wholesale_unit }}</span>
                <span class="font-bold text-sm text-amber-700">{{ $currency }} {{ number_format($p->wholesale_price,2) }}</span>
              </div>
              <div class="flex items-center gap-1 p-1.5">
                <div class="flex items-center border border-amber-300 rounded overflow-hidden">
                  <button @click="qty>({{ (int)$p->wholesale_min_qty }})?qty--:null" class="px-2 py-1 text-xs bg-amber-50 hover:bg-amber-100">−</button>
                  <span class="px-2 text-xs font-semibold" x-text="qty"></span>
                  <button @click="qty++" class="px-2 py-1 text-xs bg-amber-50 hover:bg-amber-100">+</button>
                </div>
                <button class="flex-1 py-1.5 text-[11px] font-semibold bg-amber-500 hover:bg-amber-600 text-white rounded"
                        @click="qty=Math.max({{ (int)$p->wholesale_min_qty }},qty); addToCart({id:{{ $p->id }},name:'{{ addslashes($p->name) }} (mayor)',price:{{ $p->wholesale_price }},qty:qty,min_qty:{{ (int)$p->wholesale_min_qty }},img:'{{ $p->mainImage ? $p->main_image_url : '' }}'})">
                  + Agregar
                </button>
              </div>
            </div>
            @elseif(!$isQuoteOnly || $quotePriceDisp==='show')
            <div class="flex items-baseline gap-1.5 mb-2">
              <span class="font-bold text-sm" style="color:var(--c)">{{ $currency }} {{ number_format($p->price,2) }}</span>
              @if($p->compare_price && $p->compare_price > $p->price)
              <span class="text-gray-300 text-xs line-through">{{ $currency }} {{ number_format($p->compare_price,2) }}</span>
              @endif
            </div>
            @if(!$isQuoteOnly)
            <button class="w-full py-2 text-[11px] font-semibold btn-gc"
                    @click="addToCart({id:{{ $p->id }},name:'{{ addslashes($p->name) }}',price:{{ $p->price }},img:'{{ $p->mainImage ? $p->main_image_url : '' }}'})">
              + Agregar
            </button>
            @else
            <a class="block w-full py-2 text-[11px] font-semibold btn-outline-gc text-center"
               href="https://wa.me/{{ $quoteWa }}?text={{ urlencode('Hola, me interesa: '.$p->name) }}" target="_blank">
              Cotizar
            </a>
            @endif
            @else
            <p class="text-gray-400 text-xs mb-2 italic">Consultar precio</p>
            @endif
          </div>
        </article>
        @endforeach

        {{-- Productos de subcategorías --}}
        @foreach($cat->children as $sub)
        @foreach($sub->products as $idx => $p)
        @php
        $qvData = ['id'=>$p->id,'name'=>$p->name,'img'=>$p->main_image_url??'','price'=>(float)$p->price,'cp'=>$p->compare_price?(float)$p->compare_price:null,'desc'=>\Str::limit(strip_tags($p->description??''),120),'url'=>route('public.product',[$project->slug,$p->id]),'stock'=>$p->stock];
        @endphp
        <article
          x-show="matchProduct('{{ strtolower(addslashes($p->name)) }}', {{ $p->price }}, {{ $p->compare_price ?? 'null' }}, '{{ $sub->id }}', '{{ $cat->id }}')"
          class="d-card group"
          data-price="{{ $p->price }}"
          data-name="{{ strtolower($p->name) }}"
          data-ts="{{ $p->created_at?->timestamp ?? 0 }}"
          data-idx="{{ $idx }}"
          data-qv='@json($qvData)'>
          <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="block card-img relative">
            @if($p->mainImage)
            <img src="{{ $p->main_image_url }}" alt="{{ $p->name }}"
                 class="w-full h-full object-cover" loading="lazy">
            @else
            <div class="w-full h-full flex items-center justify-center text-gray-300">
              <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
            @endif
            @if($p->compare_price && $p->compare_price > $p->price)
            <span class="absolute top-2 left-2 bg-red-500 text-white text-[9px] font-bold uppercase px-2 py-0.5 rounded-full">Oferta</span>
            @endif
            <button @click.prevent="const d=$el.closest('[data-qv]');if(d){qv=JSON.parse(d.dataset.qv);qvOpen=true}"
                    class="absolute inset-0 flex items-end justify-center pb-3 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none group-hover:pointer-events-auto z-10">
              <span class="bg-black/70 text-white text-xs font-bold px-3 py-1.5 rounded-full backdrop-blur-sm">Vista rápida</span>
            </button>
          </a>
          <div class="p-3">
            <a href="{{ route('public.product', [$project->slug, $p->id]) }}"
               class="text-gray-800 text-xs font-semibold line-clamp-2 hover:underline block mb-1.5">{{ $p->name }}</a>
            @php $hasWholesale = $wholesaleEnabled && $p->wholesale_price && $p->wholesale_min_qty; @endphp
            @if($hasWholesale)
            <div x-data="{ qty:1 }" class="rounded-lg border border-gray-200 bg-white overflow-hidden mb-1.5">
              <div class="flex items-center justify-between px-2 py-1 bg-gray-50 border-b border-gray-100">
                <span class="text-[10px] font-bold text-gray-500 uppercase">Minorista</span>
                <span class="font-bold text-sm" style="color:var(--c)">{{ $currency }} {{ number_format($p->price,2) }}</span>
              </div>
              <div class="flex items-center gap-1 p-1.5">
                <div class="flex items-center border border-gray-200 rounded overflow-hidden">
                  <button @click="qty>1?qty--:null" class="px-2 py-1 text-xs bg-gray-50 hover:bg-gray-100">−</button>
                  <span class="px-2 text-xs font-semibold" x-text="qty"></span>
                  <button @click="qty++" class="px-2 py-1 text-xs bg-gray-50 hover:bg-gray-100">+</button>
                </div>
                <button class="flex-1 py-1.5 text-[11px] font-semibold btn-gc"
                        @click="addToCart({id:{{ $p->id }},name:'{{ addslashes($p->name) }}',price:{{ $p->price }},qty:qty,img:'{{ $p->mainImage ? $p->main_image_url : '' }}'})">
                  + Agregar
                </button>
              </div>
            </div>
            <div x-data="{ qty:{{ (int)$p->wholesale_min_qty }} }" class="rounded-lg border border-amber-400 overflow-hidden">
              <div class="flex items-center justify-between px-2 py-1 bg-amber-50 border-b border-amber-100">
                <span class="text-[10px] font-bold text-amber-700 uppercase">Mayor · mín {{ (int)$p->wholesale_min_qty }} {{ $p->wholesale_unit }}</span>
                <span class="font-bold text-sm text-amber-700">{{ $currency }} {{ number_format($p->wholesale_price,2) }}</span>
              </div>
              <div class="flex items-center gap-1 p-1.5">
                <div class="flex items-center border border-amber-300 rounded overflow-hidden">
                  <button @click="qty>({{ (int)$p->wholesale_min_qty }})?qty--:null" class="px-2 py-1 text-xs bg-amber-50 hover:bg-amber-100">−</button>
                  <span class="px-2 text-xs font-semibold" x-text="qty"></span>
                  <button @click="qty++" class="px-2 py-1 text-xs bg-amber-50 hover:bg-amber-100">+</button>
                </div>
                <button class="flex-1 py-1.5 text-[11px] font-semibold bg-amber-500 hover:bg-amber-600 text-white rounded"
                        @click="qty=Math.max({{ (int)$p->wholesale_min_qty }},qty); addToCart({id:{{ $p->id }},name:'{{ addslashes($p->name) }} (mayor)',price:{{ $p->wholesale_price }},qty:qty,min_qty:{{ (int)$p->wholesale_min_qty }},img:'{{ $p->mainImage ? $p->main_image_url : '' }}'})">
                  + Agregar
                </button>
              </div>
            </div>
            @elseif(!$isQuoteOnly || $quotePriceDisp==='show')
            <div class="flex items-baseline gap-1.5 mb-2">
              <span class="font-bold text-sm" style="color:var(--c)">{{ $currency }} {{ number_format($p->price,2) }}</span>
              @if($p->compare_price && $p->compare_price > $p->price)
              <span class="text-gray-300 text-xs line-through">{{ $currency }} {{ number_format($p->compare_price,2) }}</span>
              @endif
            </div>
            @if(!$isQuoteOnly)
            <button class="w-full py-2 text-[11px] font-semibold btn-gc"
                    @click="addToCart({id:{{ $p->id }},name:'{{ addslashes($p->name) }}',price:{{ $p->price }},img:'{{ $p->mainImage ? $p->main_image_url : '' }}'})">
              + Agregar
            </button>
            @else
            <a class="block w-full py-2 text-[11px] font-semibold btn-outline-gc text-center"
               href="https://wa.me/{{ $quoteWa }}?text={{ urlencode('Hola, me interesa: '.$p->name) }}" target="_blank">
              Cotizar
            </a>
            @endif
            @else
            <p class="text-gray-400 text-xs mb-2 italic">Consultar precio</p>
            @endif
          </div>
        </article>
        @endforeach
        @endforeach
      </div>
    </div>
    @endif
    @endforeach

    {{-- Sin resultados --}}
    <div x-show="noResults" class="text-center py-20">
      <svg class="w-14 h-14 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
      </svg>
      <p class="font-bold text-gray-700 mb-1">Sin resultados</p>
      <p class="text-gray-400 text-sm mb-4">Intenta con otro término o categoría</p>
      <button @click="search='';filterCat='';priceFilter='';onSaleFilter=false"
              class="btn-outline-gc px-5 py-2 text-sm font-semibold transition">
        Ver todo el catálogo
      </button>
    </div>

  </div>
</div>


{{-- ─── FOOTER ─────────────────────────────────────────────────────────────── --}}
<footer class="bg-gray-900 mt-10 py-10">
  <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-3 gap-8">
    <div>
      @if($logoUrl)
      <img src="{{ $logoUrl }}" alt="{{ $project->name }}"
           style="height:{{ $logoHeight }}px; max-height:50px" class="object-contain mb-3 brightness-0 invert" loading="lazy">
      @else
      <p class="text-white font-bold text-lg mb-3">{{ $project->name }}</p>
      @endif
      @if($seoDesc)<p class="text-gray-400 text-sm leading-relaxed">{{ $seoDesc }}</p>@endif
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}" target="_blank" rel="noopener"
         class="inline-flex items-center gap-2 mt-4 bg-[#25D366] text-white text-xs font-bold px-4 py-2 rounded-full hover:bg-[#20ba5a] transition">
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/>
        </svg>
        WhatsApp
      </a>
      @endif
    </div>
    <div>
      <p class="text-gray-500 text-xs uppercase tracking-widest mb-4">Categorías</p>
      <ul class="space-y-2">
        @foreach($categories as $cat)
        <li>
          <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                  class="text-gray-400 hover:text-white text-sm transition text-left">
            {{ $cat->name }}
          </button>
        </li>
        @endforeach
      </ul>
    </div>
    <div>
      <p class="text-gray-500 text-xs uppercase tracking-widest mb-4">Contacto</p>
      <ul class="space-y-2 text-sm text-gray-400">
        @if($project->phone)
        <li class="flex items-center gap-2">
          <svg class="w-4 h-4 opacity-40 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
          </svg>
          {{ $project->phone }}
        </li>
        @endif
        @if($project->address)
        <li class="flex items-start gap-2">
          <svg class="w-4 h-4 opacity-40 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
          </svg>
          {{ $project->address }}
        </li>
        @endif
        @foreach(['facebook_url'=>'Facebook','instagram_url'=>'Instagram','tiktok_url'=>'TikTok'] as $sKey=>$sLabel)
        @if($settings[$sKey] ?? null)
        <li><a href="{{ $settings[$sKey] }}" target="_blank" rel="noopener" class="hover:text-white transition">{{ $sLabel }}</a></li>
        @endif
        @endforeach
      </ul>
    </div>
  </div>
  <div class="border-t border-gray-800 mt-8 pt-6 text-center">
    <p class="text-gray-600 text-xs">{{ $footerCopyright }}</p>
  </div>
</footer>


{{-- ─── BOTÓN FLOTANTE WHATSAPP ────────────────────────────────────────────── --}}
@if($project->whatsapp)
<a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}?text={{ urlencode($settings['whatsapp_msg'] ?? 'Hola, quisiera más información') }}"
   target="_blank" rel="noopener"
   class="fixed bottom-6 right-6 z-50 w-[52px] h-[52px] rounded-full bg-[#25D366] flex items-center justify-center shadow-xl hover:scale-110 transition-transform">
  <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 24 24">
    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/>
  </svg>
</a>
@endif


{{-- ─── QUICK VIEW MODAL ───────────────────────────────────────────────────── --}}
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
    <button @click="qvOpen=false" class="absolute top-3 right-3 z-20 w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center">
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
                  class="w-full btn-gc py-2.5 text-sm font-black flex items-center justify-center gap-2">
            + Agregar al carrito
          </button>
          <a :href="qv&&qv.url" class="w-full text-center py-2 text-sm font-semibold border-2 rounded-xl transition hover:bg-gray-50"
             style="border-color:var(--c);color:var(--c)">
            Ver producto completo
          </a>
        </div>
      </div>
    </div>
  </div>
</div>


{{-- ─── MOBILE FILTER BOTTOM-SHEET ────────────────────────────────────────── --}}
<div x-show="filterOpen" x-cloak class="lg:hidden fixed inset-0 z-50 flex flex-col justify-end">
  <div @click="filterOpen=false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
  <div class="relative bg-white rounded-t-3xl shadow-2xl max-h-[80vh] flex flex-col"
       x-transition:enter="transition ease-out duration-300 transform"
       x-transition:enter-start="translate-y-full"
       x-transition:enter-end="translate-y-0"
       x-transition:leave="transition ease-in duration-200 transform"
       x-transition:leave-start="translate-y-0"
       x-transition:leave-end="translate-y-full">
    <div class="flex items-center justify-between px-5 py-4 border-b flex-shrink-0">
      <h3 class="font-black text-gray-900">Filtros</h3>
      <button @click="filterOpen=false" class="p-1.5 rounded-lg hover:bg-gray-100">
        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>
    <div class="overflow-y-auto flex-1 px-5 py-4 space-y-5">
      <div>
        <p class="font-bold text-gray-800 text-sm mb-3">Categorías</p>
        <div class="grid grid-cols-2 gap-2">
          <button @click="filterCat=''"
                  :class="filterCat===''?'border-[var(--c)] text-[var(--c)] font-bold':'border-gray-200 text-gray-600'"
                  class="border rounded-xl px-3 py-2 text-sm transition">Todos</button>
          @foreach($categories as $cat)
          <button @click="filterCat='{{ $cat->id }}'"
                  :class="filterCat==='{{ $cat->id }}'?'border-[var(--c)] text-[var(--c)] font-bold':'border-gray-200 text-gray-600'"
                  class="border rounded-xl px-3 py-2 text-sm text-left transition truncate">{{ $cat->name }}</button>
          @endforeach
        </div>
      </div>
      <div>
        <p class="font-bold text-gray-800 text-sm mb-3">Precio</p>
        <div class="grid grid-cols-2 gap-2">
          @foreach([
            ['' ,      'Todos'],
            ['0-20',   'Hasta '.$currency.' 20'],
            ['20-50',  $currency.' 20–50'],
            ['50-200', $currency.' 50–200'],
            ['200+',   'Más de '.$currency.' 200'],
          ] as [$val,$lbl])
          <button @click="priceFilter='{{ $val }}'"
                  :class="priceFilter==='{{ $val }}'?'border-[var(--c)] text-[var(--c)] font-bold':'border-gray-200 text-gray-600'"
                  class="border rounded-xl px-3 py-2 text-sm text-left transition {{ $val==='' ? 'col-span-2':'' }}">
            {{ $lbl }}
          </button>
          @endforeach
        </div>
      </div>
      <div>
        <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer"
               :class="onSaleFilter?'border-[var(--c)]':''">
          <input type="checkbox" x-model="onSaleFilter" class="w-4 h-4 rounded" style="accent-color:var(--c)">
          <span class="text-sm font-medium text-gray-700">Solo ofertas</span>
        </label>
      </div>
    </div>
    <div class="flex gap-3 px-5 py-4 border-t flex-shrink-0">
      <button @click="filterCat='';priceFilter='';onSaleFilter=false"
              class="flex-1 py-3 rounded-xl text-sm font-bold border border-gray-300 text-gray-700">
        Limpiar
      </button>
      <button @click="filterOpen=false"
              class="flex-1 py-3 rounded-xl text-sm font-bold text-white btn-gc">
        Ver resultados
      </button>
    </div>
  </div>
</div>


{{-- ─── FLOATING BOTTOM BAR (mobile) ──────────────────────────────────────── --}}
<div x-show="cart.length>0" x-cloak
     class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 shadow-[0_-4px_20px_rgba(0,0,0,.08)] px-4 py-3 flex items-center gap-4">
  <div class="flex items-center gap-2.5 flex-1 min-w-0">
    <span class="text-white text-xs font-black w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 bg-gc"
          x-text="cart.reduce((s,i)=>s+i.qty,0)"></span>
    <div class="min-w-0">
      <p class="text-[10px] text-gray-400 leading-none mb-0.5">Total</p>
      <p class="font-black text-base leading-none" style="color:var(--c)"
         x-text="'{{ $currency }} '+subtotal.toFixed(2)"></p>
    </div>
  </div>
  <button @click="drawerOpen=true; drawerStep=1"
          class="px-5 py-3 rounded-xl font-black text-sm text-white btn-gc flex-shrink-0">
    {{ $isQuoteOnly ? 'Ver cotización' : 'Ver pedido' }}
  </button>
</div>


{{-- ─── ALPINE STORE ───────────────────────────────────────────────────────── --}}
<script>
function store() {
  const _cartKey = 'avan_cart_{{ $project->id }}';
  const _formKey = 'avan_form_{{ $project->id }}';
  let _savedCart = [];
  let _savedForm = { name:'', phone:'', email:'', notes:'', address:'' };
  try {
    const c = localStorage.getItem(_cartKey); if(c) _savedCart = JSON.parse(c);
    const f = localStorage.getItem(_formKey); if(f) _savedForm = {..._savedForm, ...JSON.parse(f)};
  } catch(e) {}

  return {
    _cartKey, _formKey,
    search: '', filterCat: '', filterParent: '',
    filterParent: '', priceFilter: '', onSaleFilter: false, sortBy: 'default',
    filterOpen: false,
    searchIndex: @json($searchIndex),
    searchOpen: false, searchIdx: -1,
    qv: null, qvOpen: false,
    drawerOpen: false, drawerStep: 1,
    toastShow: false, toastMsg: '', toastTimer: null,
    cart: _savedCart, form: _savedForm,
    orderLoading: false, orderError: '', noResults: false,
    orderId: null, orderTotal: 0, orderSent: false,
    shippingEnabled:  {{ $shippingEnabled  ? 'true' : 'false' }},
    shippingCost:     {{ $shippingCost }},
    shippingFreeFrom: {{ $shippingFreeFrom }},
    requireAddress:   {{ $requireAddress   ? 'true' : 'false' }},
    couponCode: '', couponApplied: null, couponError: '', couponLoading: false,
    selectedPayMethod: '', payReference: '', payError: '', payLoading: false,

    get subtotal() { return this.cart.reduce((s,i)=>s+i.price*i.qty, 0); },
    get effectiveShipping() {
      if(!this.shippingEnabled) return 0;
      if(this.shippingFreeFrom>0 && this.subtotal>=this.shippingFreeFrom) return 0;
      return this.shippingCost;
    },
    get couponDiscount() {
      if(!this.couponApplied) return 0;
      const sub = this.subtotal;
      if(sub < (this.couponApplied.min_order||0)) return 0;
      if(this.couponApplied.type==='percent') return Math.min(sub*this.couponApplied.value/100, sub);
      return Math.min(this.couponApplied.value, sub);
    },
    get orderGrandTotal() { return Math.max(0, this.subtotal - this.couponDiscount + this.effectiveShipping); },
    get visibleCount() {
      return this.searchIndex.filter(p => this.matchProduct(p.name.toLowerCase(), p.price, p.cp, p.catId, p.parentId)).length;
    },
    get suggestions() {
      if(!this.search || this.search.trim().length<2) return [];
      const q = this.search.toLowerCase().trim();
      return this.searchIndex.filter(p=>p.name.toLowerCase().includes(q)||(p.cat&&p.cat.toLowerCase().includes(q))).slice(0,6);
    },
    selectSuggestion(p) { window.location.href = p.url; },
    _highlight(text) {
      if(!this.search||this.search.trim().length<2) return text;
      const q = this.search.trim().replace(/[.*+?^${}()|[\]\\]/g,'\\$&');
      return text.replace(new RegExp('('+q+')','gi'),'<strong style="color:var(--c)">$1</strong>');
    },

    init() {
      this.$watch('cart', val => {
        try { localStorage.setItem(this._cartKey, JSON.stringify(val)); } catch(e) {}
        this.$nextTick(() => this.checkNoResults());
      });
      this.$watch('form', val => { try { localStorage.setItem(this._formKey, JSON.stringify(val)); } catch(e) {} }, { deep:true });
      ['search','filterCat','priceFilter','onSaleFilter'].forEach(k => {
        this.$watch(k, () => this.$nextTick(() => this.checkNoResults()));
      });
      this.$watch('sortBy', () => { this.applySort(); this._syncUrl(); });
      const _p = new URLSearchParams(window.location.search);
      if(_p.get('q'))     this.search      = _p.get('q');
      if(_p.get('cat'))   this.filterCat   = _p.get('cat');
      if(_p.get('price')) this.priceFilter = _p.get('price');
      if(_p.get('sale'))  this.onSaleFilter= _p.get('sale')==='1';
      ['search','filterCat','priceFilter','onSaleFilter'].forEach(k => this.$watch(k, ()=>this._syncUrl()));

      const urlParams = new URLSearchParams(window.location.search);
      const payStatus  = urlParams.get('payment');
      const payOrderId = urlParams.get('order');
      if(payStatus && payOrderId) {
        this.orderId = parseInt(payOrderId)||0;
        this.orderTotal = 0;
        if(payStatus==='success'||payStatus==='approved') {
          this.orderSent=true; this.drawerOpen=true; this.drawerStep=3;
          this.payReference = urlParams.get('payment_id')||'mp-ok';
          try{localStorage.removeItem(this._cartKey);localStorage.removeItem(this._formKey);}catch(e){}
        } else if(payStatus==='failure') {
          this.payError='El pago fue rechazado. Intenta de nuevo.'; this.drawerOpen=true; this.drawerStep=3;
        } else if(payStatus==='pending') {
          this.orderSent=true; this.drawerOpen=true; this.drawerStep=3; this.payReference='pendiente-mp';
        }
        window.history.replaceState({}, document.title, window.location.pathname);
      }
    },

    applySort() {
      const grids = this.$el.querySelectorAll('[data-products-grid]');
      grids.forEach(grid => {
        const cards = Array.from(grid.querySelectorAll('[data-price]'));
        cards.sort((a,b) => {
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
      const p = new URLSearchParams();
      if(this.search)       p.set('q',    this.search);
      if(this.filterCat)    p.set('cat',  this.filterCat);
      if(this.priceFilter)  p.set('price',this.priceFilter);
      if(this.onSaleFilter) p.set('sale', '1');
      if(this.sortBy&&this.sortBy!=='default') p.set('sort',this.sortBy);
      history.replaceState(null,'',p.toString()?'?'+p.toString():window.location.pathname);
    },

    checkNoResults() {
      const hasFilter = this.search!==''||this.filterCat!==''||this.priceFilter!==''||this.onSaleFilter;
      if(!hasFilter){ this.noResults=false; return; }
      const articles = document.querySelectorAll('#catalogo article');
      const visible  = Array.from(articles).filter(el=>el.style.display!=='none');
      this.noResults = visible.length===0;
    },

    matchProduct(name, price, comparePrice, catId, parentId) {
      const matchSearch = this.search==='' || name.includes(this.search.toLowerCase());
      let matchCat = true;
      if(this.filterCat!=='') {
        const isParentFilter = this.filterCat===this.filterParent; // clickó categoría padre
        if(isParentFilter) {
          // mostrar todos: los del padre y los de sus hijos
          matchCat = catId===this.filterCat || parentId===this.filterCat;
        } else {
          // clickó subcategoría específica: solo productos de esa sub
          matchCat = catId===this.filterCat;
        }
      }
      let matchPrice = true;
      if(this.priceFilter==='0-20')   matchPrice = price<=20;
      if(this.priceFilter==='20-50')  matchPrice = price>20 && price<=50;
      if(this.priceFilter==='50-200') matchPrice = price>50 && price<=200;
      if(this.priceFilter==='200+')   matchPrice = price>200;
      const matchSale = !this.onSaleFilter || (comparePrice && comparePrice>price);
      return matchSearch && matchCat && matchPrice && matchSale;
    },

    addToCart(product) {
      const qty    = product.qty || 1;
      const minQty = product.min_qty || 1;
      const existing = this.cart.find(i=>i.id===product.id && i.name===product.name);
      if(existing){ existing.qty += qty; }
      else{ this.cart.push({...product, qty: Math.max(qty, minQty), min_qty: minQty}); }
      this.toastMsg = '✓ '+product.name+' agregado';
      this.toastShow = true;
      clearTimeout(this.toastTimer);
      this.toastTimer = setTimeout(()=>{ this.toastShow=false; }, 2000);
    },

    sendQuoteWhatsapp() {
      if(!this.form.name.trim()){ this.orderError='Por favor ingresa tu nombre.'; return; }
      const businessName = `{{ addslashes($project->name) }}`;
      const customMsg    = `{{ addslashes($quoteWaMsg) }}`;
      const now=new Date(), fecha=now.toLocaleDateString('es-PE',{day:'2-digit',month:'long',year:'numeric'});
      let lines='';
      lines+=`📋 *SOLICITUD DE COTIZACIÓN*\n`;
      lines+=`━━━━━━━━━━━━━━━━━━━━━━\n`;
      lines+=`🏪 *${businessName}*\n\n${customMsg}\n\n`;
      lines+=`👤 *DATOS DE CONTACTO*\n• Nombre: ${this.form.name}\n`;
      if(this.form.phone) lines+=`• Teléfono: ${this.form.phone}\n`;
      if(this.form.email) lines+=`• Correo: ${this.form.email}\n`;
      lines+=`\n📦 *PRODUCTOS SOLICITADOS*\n━━━━━━━━━━━━━━━━━━━━━━\n`;
      let total=0;
      this.cart.forEach((item,idx)=>{
        @if(!$isQuoteOnly || $quotePriceDisp==='show')
        const subtotal=(item.price*item.qty).toFixed(2);
        lines+=`${idx+1}. *${item.name}*\n   Cant: ${item.qty}  •  {{ $currency }} ${subtotal}\n`;
        total+=item.price*item.qty;
        @else
        lines+=`${idx+1}. *${item.name}* — cant: ${item.qty}\n`;
        @endif
      });
      @if(!$isQuoteOnly || $quotePriceDisp==='show')
      lines+=`━━━━━━━━━━━━━━━━━━━━━━\n💰 *Total referencial: {{ $currency }} ${total.toFixed(2)}*\n`;
      @endif
      if(this.form.notes) lines+=`\n📝 *Nota:* ${this.form.notes}\n`;
      lines+=`\n📅 Fecha: ${fecha}\n_Desde el catálogo de ${businessName}_`;
      window.open(`https://wa.me/{{ $quoteWa }}?text=${encodeURIComponent(lines)}`,'_blank');
      this.cart=[]; this.orderSent=true;
      try{localStorage.removeItem(this._cartKey);localStorage.removeItem(this._formKey);}catch(e){}
    },

    async submitOrder() {
      if(!this.form.name.trim()||!this.form.phone.trim()){
        this.orderError='Por favor ingresa tu nombre y teléfono.'; return;
      }
      this.orderLoading=true; this.orderError='';
      const items = this.cart.map(i=>({product_id:i.id,name:i.name,price:i.price,quantity:i.qty}));
      try {
        const res = await fetch('/{{ $project->slug }}/order',{
          method:'POST',
          headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
          body:JSON.stringify({
            client_name:this.form.name, client_phone:this.form.phone,
            client_email:this.form.email, notes:this.form.notes,
            coupon_code:this.couponApplied?this.couponApplied.code:null,
            delivery_address:this.form.address||null,
            shipping_cost:this.effectiveShipping>0?this.effectiveShipping:null,
            items
          })
        });
        const data = await res.json();
        if(data.ok){
          @if($isQuoteOnly && $quoteWa)
          this.sendQuoteWhatsapp();
          @elseif($isQuoteOnly)
          try{localStorage.removeItem(this._cartKey);localStorage.removeItem(this._formKey);}catch(e){}
          window.location.href='/{{ $project->slug }}/thanks/'+data.order_id;
          @elseif($quoteWa)
          const nPedido='#'+String(data.order_id).padStart(4,'0');
          let waMsg=`📋 *N° Pedido: ${nPedido}*\n👤 *Cliente: ${this.form.name}*\n`;
          if(this.form.phone) waMsg+=`📱 ${this.form.phone}\n`;
          waMsg+=`\n📦 *PRODUCTOS*\n━━━━━━━━━━━━━━━━━━━━━━\n`;
          this.cart.forEach((item,i)=>{
            waMsg+=`${i+1}. *${item.name}*\n   • Cantidad: ${item.qty}\n   • Precio unit.: S/ ${Number(item.price).toFixed(2)}\n   • Subtotal: S/ ${(item.price*item.qty).toFixed(2)}\n\n`;
          });
          waMsg+=`━━━━━━━━━━━━━━━━━━━━━━\n💰 *TOTAL: S/ ${Number(data.total).toFixed(2)}*\n`;
          if(this.form.notes) waMsg+=`\n📝 ${this.form.notes}\n`;
          this.cart=[];this.couponApplied=null;this.couponCode='';
          try{localStorage.removeItem(this._cartKey);localStorage.removeItem(this._formKey);}catch(e){}
          window.open(`https://wa.me/{{ $quoteWa }}?text=${encodeURIComponent(waMsg)}`,'_blank');
          this.orderSent=true;
          @else
          this.orderId=data.order_id; this.orderTotal=data.total;
          this.orderSent=false; this.payReference=''; this.payError=''; this.drawerStep=3;
          @endif
        } else { this.orderError='No se pudo enviar. Inténtalo de nuevo.'; }
      } catch(e) { this.orderError='Error de conexión.'; }
      this.orderLoading=false;
    },

    async confirmManualPay() {
      if(!this.payReference.trim()) return;
      this.payLoading=true; this.payError='';
      try {
        const res = await fetch(`{{ url('/'.$project->slug.'/pay') }}/${this.orderId}/manual`,{
          method:'POST',
          headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
          body:JSON.stringify({reference:this.payReference})
        });
        const data = await res.json();
        if(data.ok){
          try{localStorage.removeItem(this._cartKey);localStorage.removeItem(this._formKey);}catch(e){}
          window.location.href='/{{ $project->slug }}/thanks/'+this.orderId;
        } else { this.payError='No se pudo confirmar el pago.'; }
      } catch(e) { this.payError='Error de conexión.'; }
      this.payLoading=false;
    },

    @if($culqiEnabled && $culqiPublicKey)
    openCulqi() {
      const self=this, email=self.form.email||'cliente@ejemplo.com';
      Culqi.publicKey='{{ $culqiPublicKey }}';
      Culqi.settings({title:'{{ addslashes($project->name) }}',currency:'PEN',description:'Pedido #'+self.orderId,amount:Math.round(self.orderTotal*100),email});
      Culqi.options({style:{logo:'{{ $project->logo_url?asset("storage/".$project->logo_url):"" }}',maincolor:'{{ $primaryColor }}',buttontext:'Pagar {{ $currency }} '+self.orderTotal.toFixed(2),maintext:'{{ addslashes($project->name) }}',desctext:'Pedido #'+self.orderId},paymentMethods:{tarjeta:true,yape:true,billetera:false,cuotealo:false}});
      Culqi.open();
      window.culqi = async function(){
        if(Culqi.token){
          self.payLoading=true; self.payError='';
          try{
            const res=await fetch(`{{ url('/'.$project->slug.'/pay') }}/${self.orderId}/culqi`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({token:Culqi.token.id,email})});
            const data=await res.json();
            if(data.ok){self.orderSent=true;self.payReference=data.charge_id||'culqi-ok';try{localStorage.removeItem(self._cartKey);localStorage.removeItem(self._formKey);}catch(e){}}
            else{self.payError=data.message||'Tarjeta rechazada.';}
          }catch(e){self.payError='Error de conexión.';}
          self.payLoading=false; Culqi.close();
        } else if(Culqi.error){self.payError=Culqi.error.user_message||'Error en el pago.';self.payLoading=false;}
      };
    },
    @endif

    @if($mpEnabled)
    async openMercadoPago(){
      this.payLoading=true; this.payError='';
      try{
        const res=await fetch(`{{ url('/'.$project->slug.'/pay') }}/${this.orderId}/mp`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({email:this.form.email||null})});
        const data=await res.json();
        if(data.init_point){const url=(data.is_sandbox&&data.sandbox_init_point)?data.sandbox_init_point:data.init_point;window.location.href=url;}
        else{this.payError='No se pudo iniciar el pago.';this.payLoading=false;}
      }catch(e){this.payError='Error de conexión.';this.payLoading=false;}
    },
    @endif
  };
}
</script>

</body>
</html>
