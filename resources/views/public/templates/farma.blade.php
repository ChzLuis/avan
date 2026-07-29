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
  $primaryColor     = $settings['primary_color']   ?? '#2F80ED';
  $secondaryColor   = $settings['secondary_color'] ?? '#56CCF2';
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
  $heroTitle    = $settings['hero_title']  ?? $project->name;
  $heroSub      = $settings['hero_sub']    ?? ($project->description ?? 'Encuentra lo que necesitas para tu salud y bienestar.');
  $heroBadge    = $settings['hero_badge']  ?? '';
  $heroBg       = $settings['hero_bg_color'] ?? '#EEF3F9';
  $footerTagline   = $settings['footer_tagline']   ?? 'Tu salud es nuestra prioridad.';
  $footerCopyright = $settings['footer_copyright'] ?? '© '.date('Y').' '.$project->name;
  $btnCartText     = $settings['btn_cart_text']  ?? 'Agregar al carrito';
  $btnQuoteText    = $settings['btn_quote_text'] ?? 'Cotizar';
  $acceptedPayments = json_decode($settings['accepted_payments'] ?? '[]', true) ?? [];
  $paymentMeta = [
    'efectivo'      => ['label'=>'Efectivo',        'emoji'=>'💵'],
    'yape'          => ['label'=>'Yape',             'emoji'=>'🟣'],
    'plin'          => ['label'=>'Plin',             'emoji'=>'🔵'],
    'transferencia' => ['label'=>'Transferencia',    'emoji'=>'🏦'],
    'tarjeta'       => ['label'=>'Tarjeta',          'emoji'=>'💳'],
    'qr'            => ['label'=>'Pago QR',          'emoji'=>'📲'],
    'contra_entrega'=> ['label'=>'Contra entrega',   'emoji'=>'🚚'],
  ];
  $currency = $project->setting('currency_symbol') ?: ($project->setting('currency') === 'USD' ? '$' : 'S/');
  // Agrupar productos por categoría
  $featured = $categories->flatMap->products->sortBy('sort_order')->take(8);
  $onSale   = $categories->flatMap->products->filter(fn($p) => $p->compare_price && $p->compare_price > $p->price)->take(6);
@endphp

{{-- SEO --}}
<title>{{ $settings['seo_title'] ?? $project->name }}</title>
<meta name="description" content="{{ $settings['seo_description'] ?? $project->description }}">
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta property="og:title"       content="{{ $settings['seo_title'] ?? $project->name }}">
<meta property="og:description" content="{{ $settings['seo_description'] ?? $project->description }}">
<meta property="og:url"         content="{{ $canonicalUrl }}">
@if($project->logo_url)
<meta property="og:image" content="{{ asset('storage/'.$project->logo_url) }}">
@endif

<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<style>
:root {
  --primary: {{ $primaryColor }};
  --secondary: {{ $secondaryColor }};
}
*, body { font-family: 'Inter', sans-serif; }
.poppins { font-family: 'Poppins', sans-serif; }
[x-cloak] { display:none!important; }

/* Botones */
.btn-primary { background: var(--primary); color: #fff; font-weight: 700; transition: opacity .2s; }
.btn-primary:hover { opacity: .88; }
.btn-outline { border: 2px solid var(--primary); color: var(--primary); font-weight: 700; transition: all .2s; }
.btn-outline:hover { background: var(--primary); color: #fff; }
.text-primary { color: var(--primary); }
.bg-primary { background: var(--primary); }
.border-primary { border-color: var(--primary); }

/* Card producto */
.prod-card { transition: box-shadow .25s, transform .25s; }
.prod-card:hover { box-shadow: 0 12px 32px rgba(0,0,0,.12); transform: translateY(-3px); }
.prod-card .overlay { opacity: 0; transition: opacity .25s; }
.prod-card:hover .overlay { opacity: 1; }
.prod-card .prod-img img { transition: transform .4s ease; }
.prod-card:hover .prod-img img { transform: scale(1.05); }

/* Drawer */
.drawer-overlay { position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:40;backdrop-filter:blur(3px); }
.drawer { position:fixed;top:0;right:0;height:100%;width:420px;max-width:96vw;background:#fff;z-index:50;display:flex;flex-direction:column;box-shadow:-8px 0 40px rgba(0,0,0,.15); }
@media(max-width:640px){ .drawer{ width:100%; } }

/* Hero slideshow */
.hero-slide { position:absolute;inset:0;background-size:cover;background-position:center;opacity:0;transition:opacity 1s ease; }
.hero-slide.active { opacity:1; }

/* Trust bar */
.trust-item { transition: transform .2s; }
.trust-item:hover { transform: translateY(-2px); }

/* Scrollbar oculta */
.no-scroll::-webkit-scrollbar { display:none; }
.no-scroll { scrollbar-width:none; }
/* Farma cat arrows */
.frm-cat-outer { display:flex; align-items:center; flex:1; gap:2px; overflow:hidden; }
.frm-cat-track { display:flex; align-items:center; gap:4px; overflow-x:auto; scroll-behavior:smooth; scrollbar-width:none; flex:1; }
.frm-cat-track::-webkit-scrollbar { display:none; }
.frm-arrow { flex-shrink:0; width:26px; height:26px; display:flex; align-items:center; justify-content:center; background:transparent; border:1px solid #e5e7eb; border-radius:50%; cursor:pointer; color:#9ca3af; transition:all .2s; }
.frm-arrow:hover { border-color:var(--primary); color:var(--primary); }
.frm-arrow.g-hidden { opacity:0; pointer-events:none; }
/* Farma sub dropdown */
.frm-cat-wrap { position:static; flex-shrink:0; }
#frm-sub-dropdown { display:none; position:fixed; min-width:150px; background:#fff; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,.1); z-index:9999; overflow:hidden; }
#frm-sub-dropdown button { display:block; width:100%; text-align:left; padding:8px 14px; font-size:12px; color:#374151; background:none; border:none; cursor:pointer; white-space:nowrap; transition:background .15s; }
#frm-sub-dropdown button:hover { background:#f0fdf4; color:var(--primary); }

/* Badge */
.badge-sale { background:#FD3F30; color:#fff; font-size:10px; font-weight:800; padding:2px 8px; border-radius:4px; }
.badge-new  { background:var(--primary); color:#fff; font-size:10px; font-weight:800; padding:2px 8px; border-radius:4px; }
</style>

<script>
function store() {
  return {
    cart: [], drawerOpen: false, drawerStep: 1,
    search: '', searchOpen: false, searchIdx: -1,
    filterCat: '', sortBy: 'default',
    onSaleFilter: false,
    orderSent: false, orderLoading: false, orderError: '', orderId: null,
    toastShow: false, toastMsg: '',
    couponCode: '', couponApplied: null, couponDiscount: 0, couponLoading: false, couponError: '',
    form: { name:'', phone:'', email:'', notes:'', address:'' },
    heroIdx: 0,
    shippingEnabled: {{ $shippingEnabled ? 'true' : 'false' }},
    shippingCost: {{ $shippingCost }},
    shippingFreeFrom: {{ $shippingFreeFrom }},

    get cartCount() { return this.cart.reduce((s,i)=>s+i.qty,0); },
    get subtotal()  { return this.cart.reduce((s,i)=>s+i.price*i.qty,0); },
    get effectiveShipping() {
      if (!this.shippingEnabled) return 0;
      if (this.shippingFreeFrom > 0 && this.subtotal >= this.shippingFreeFrom) return 0;
      return this.shippingCost;
    },
    get orderGrandTotal() { return Math.max(0, this.subtotal - this.couponDiscount) + this.effectiveShipping; },

    get suggestions() {
      const q = this.search.trim().toLowerCase();
      if (q.length < 2) return [];
      const all = window._allProducts || [];
      return all.filter(p => p.name.toLowerCase().includes(q)).slice(0,6);
    },

    init() {
      try { const s=localStorage.getItem('avan_cart_{{ $project->id }}'); if(s) this.cart=JSON.parse(s); } catch(e){}
      this.$watch('cart', v => { try{ localStorage.setItem('avan_cart_{{ $project->id }}', JSON.stringify(v)); }catch(e){} });
      // Hero auto-slide
      const slides = document.querySelectorAll('.hero-slide');
      if (slides.length > 1) {
        setInterval(() => {
          slides[this.heroIdx].classList.remove('active');
          this.heroIdx = (this.heroIdx + 1) % slides.length;
          slides[this.heroIdx].classList.add('active');
        }, 5000);
      }
    },

    addToCart(p) {
      const idx = this.cart.findIndex(i=>i.id===p.id);
      if (idx>=0) this.cart[idx].qty++;
      else this.cart.push({...p, qty:1});
      this.toastMsg = '✓ ' + p.name.substring(0,30) + ' añadido';
      this.toastShow = true;
      setTimeout(() => this.toastShow=false, 2500);
    },

    _highlight(name) {
      const q = this.search.trim();
      if (!q) return name;
      return name.replace(new RegExp('('+q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+')','gi'),'<mark class="bg-yellow-200 rounded">$1</mark>');
    },

    selectSuggestion(p) {
      this.search = p.name;
      this.searchOpen = false;
      const el = document.getElementById('producto-'+p.id);
      if (el) { el.scrollIntoView({behavior:'smooth',block:'center'}); el.classList.add('ring-2','ring-primary'); setTimeout(()=>el.classList.remove('ring-2','ring-primary'),2000); }
    },

    async applyCoupon() {
      if (!this.couponCode.trim()) return;
      this.couponLoading = true; this.couponError = '';
      try {
        const res = await fetch('/bixoadmin/coupons/validate', {
          method:'POST',
          headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},
          body: JSON.stringify({code: this.couponCode, subtotal: this.subtotal})
        });
        const d = await res.json();
        this.couponLoading = false;
        if (d.ok) { this.couponApplied = d.coupon; this.couponDiscount = d.discount; }
        else { this.couponError = d.message || 'Cupón no válido.'; }
      } catch(e) { this.couponLoading = false; this.couponError = 'Error al validar.'; }
    },
    removeCoupon() { this.couponApplied=null; this.couponDiscount=0; this.couponCode=''; this.couponError=''; },

    async submitOrder() {
      if (!this.form.name.trim() || !this.form.phone.trim()) { this.orderError = 'Nombre y teléfono son requeridos.'; return; }
      @if($requireAddress) if (!this.form.address.trim()) { this.orderError = 'La dirección de entrega es requerida.'; return; } @endif
      this.orderLoading = true; this.orderError = '';
      const items = this.cart.map(i=>({product_id:i.id, qty:i.qty, price:i.price, name:i.name}));
      const payload = { ...this.form, items, coupon_code: this.couponApplied?.code, project_slug: '{{ $project->slug }}' };
      try {
        const res = await fetch('/orders', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'}, body:JSON.stringify(payload) });
        const d = await res.json();
        this.orderLoading = false;
        if (d.ok || d.order) { this.orderSent=true; this.orderId=d.order?.id||d.id||null; }
        else { this.orderError = d.message || 'Error al procesar el pedido.'; }
      } catch(e) { this.orderLoading=false; this.orderError='Error de conexión.'; }
    },

    sendWhatsApp() {
      const wa = '{{ $quoteWa }}';
      if (!wa) return;
      let msg = '{{ addslashes($quoteWaMsg) }}\n\n';
      this.cart.forEach(i => { msg += `• ${i.name} x${i.qty}`; @if(!$isQuoteOnly || $quotePriceDisp==='show') msg += ` - {{ $currency }} ${(i.price*i.qty).toFixed(2)}`; @endif msg += '\n'; });
      @if(!$isQuoteOnly || $quotePriceDisp==='show') msg += `\nTotal: {{ $currency }} ${this.orderGrandTotal.toFixed(2)}`; @endif
      if (this.form.name) msg += `\n\nNombre: ${this.form.name}`;
      if (this.form.phone) msg += `\nTeléfono: ${this.form.phone}`;
      if (this.form.notes) msg += `\nNota: ${this.form.notes}`;
      window.open('https://wa.me/'+wa+'?text='+encodeURIComponent(msg),'_blank');
    },
  };
}
</script>
</head>

<body class="bg-white text-gray-800" x-data="store()" x-cloak>

{{-- CSRF --}}
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- Productos para buscador --}}
@php
$_allProducts = [];
foreach($categories as $_c) {
    foreach($_c->products as $_p) {
        $_allProducts[] = [
            'id'    => $_p->id,
            'name'  => $_p->name,
            'cat'   => $_c->name,
            'price' => (float)$_p->price,
            'img'   => $_p->mainImage ? $_p->main_image_url : '',
        ];
    }
}
@endphp
<script>
window._allProducts = @json($_allProducts);
</script>

{{-- TOAST --}}
<div x-show="toastShow" x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0 -translate-y-2"
     class="fixed top-20 left-1/2 -translate-x-1/2 z-[999] text-sm font-bold px-5 py-3 rounded-xl shadow-xl whitespace-nowrap pointer-events-none text-white"
     style="background:var(--primary)"
     x-text="toastMsg">
</div>

{{-- ══════════════════════════════════════ --}}
{{-- TOP BAR --}}
{{-- ══════════════════════════════════════ --}}
<div class="text-xs py-2 border-b border-gray-100 bg-white">
  <div class="max-w-7xl mx-auto px-4 flex items-center justify-between gap-4">
    <div class="flex items-center gap-5 text-gray-500">
      @if($project->phone)
      <span class="flex items-center gap-1.5">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
        {{ $project->phone }}
      </span>
      @endif
      @if($shippingFreeFrom > 0)
      <span class="font-semibold" style="color:var(--primary)">🚚 Envío gratis desde {{ $currency }} {{ number_format($shippingFreeFrom,0) }}</span>
      @endif
    </div>
    <div class="flex items-center gap-3 text-gray-400">
      @foreach(['facebook_url'=>'FB','instagram_url'=>'IG','tiktok_url'=>'TK'] as $key=>$lbl)
      @if($settings[$key] ?? null)
      <a href="{{ $settings[$key] }}" target="_blank" rel="noopener" class="hover:text-primary transition text-xs font-semibold" style="--tw-text-opacity:1">{{ $lbl }}</a>
      @endif
      @endforeach
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════ --}}
{{-- HEADER --}}
{{-- ══════════════════════════════════════ --}}
<header class="sticky top-0 z-30 bg-white border-b border-gray-100 shadow-sm">
  <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-4">

    {{-- Logo --}}
    <a href="{{ $canonicalUrl }}" class="flex items-center gap-3 flex-shrink-0 min-w-[160px]">
      @if($project->logo_url)
        <img src="{{ asset('storage/'.$project->logo_url) }}" alt="{{ $project->name }}"
             class="object-contain" style="height:{{ $settings['logo_height'] ?? 44 }}px">
      @else
        <span class="poppins font-bold text-xl" style="color:var(--primary)">{{ $project->name }}</span>
      @endif
    </a>

    {{-- Categorías nav desktop --}}
    <nav class="flex-1 hidden lg:flex items-center justify-center">
      <div class="frm-cat-outer">
        <button class="frm-arrow g-hidden" id="frm-cat-prev" onclick="frmCatScroll(-1)" aria-label="Anterior">
          <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
        <div class="frm-cat-track" id="frm-cats-track">
          <button @click="filterCat=''; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                  :class="filterCat==='' ? 'font-bold' : 'text-gray-500 hover:text-gray-800'"
                  class="px-3 py-2 text-sm transition rounded-lg flex-shrink-0"
                  :style="filterCat==='' ? 'color:var(--primary)' : ''">
            Todos
          </button>
          @foreach($categories as $cat)
          <div class="frm-cat-wrap" data-cat-id="{{ $cat->id }}"@if($cat->children->count()) data-has-sub="1"@endif>
            <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                    :class="filterCat==='{{ $cat->id }}' ? 'font-bold' : 'text-gray-500 hover:text-gray-800'"
                    class="px-3 py-2 text-sm transition rounded-lg whitespace-nowrap"
                    :style="filterCat==='{{ $cat->id }}' ? 'color:var(--primary)' : ''">
              {{ $cat->name }}@if($cat->children->count()) <span style="font-size:9px;opacity:.4;margin-left:2px;">▾</span>@endif
            </button>
          </div>
          @endforeach
          <div id="frm-sub-dropdown"></div>
        </div>
        <button class="frm-arrow" id="frm-cat-next" onclick="frmCatScroll(1)" aria-label="Siguiente">
          <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
        </button>
      </div>
    </nav>

    {{-- Acciones --}}
    <div class="flex items-center gap-3 flex-shrink-0 ml-auto">

      {{-- Buscador desktop --}}
      <div class="relative hidden md:block" @click.outside="searchOpen=false">
        <div class="flex items-center bg-gray-50 border border-gray-200 rounded-full px-3 py-2 gap-2 w-52 focus-within:border-primary focus-within:bg-white transition" style="--primary:{{ $primaryColor }}">
          <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
          <input x-model="search" type="search" placeholder="Buscar productos..."
                 @input="searchOpen=search.trim().length>=2; searchIdx=-1"
                 @keydown.arrow-down.prevent="if(suggestions.length){searchIdx=(searchIdx+1)%suggestions.length}"
                 @keydown.arrow-up.prevent="if(suggestions.length){searchIdx=(searchIdx-1+suggestions.length)%suggestions.length}"
                 @keydown.enter.prevent="if(searchIdx>=0){selectSuggestion(suggestions[searchIdx])}else{searchOpen=false}"
                 @keydown.escape="searchOpen=false;searchIdx=-1"
                 class="bg-transparent text-sm outline-none flex-1 min-w-0 text-gray-700 placeholder-gray-400">
        </div>
        {{-- Dropdown --}}
        <div x-show="searchOpen && suggestions.length>0" x-cloak
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="absolute left-0 top-full mt-1 bg-white border border-gray-200 rounded-2xl shadow-xl z-[200] overflow-hidden min-w-[300px]">
          <template x-for="(p,i) in suggestions" :key="p.id">
            <button @click="selectSuggestion(p)" :class="searchIdx===i?'bg-gray-50':''"
                    class="flex items-center gap-3 w-full px-4 py-2.5 hover:bg-gray-50 transition text-left border-b border-gray-100 last:border-0">
              <div class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0 bg-gray-100">
                <img x-show="p.img" :src="p.img" class="w-full h-full object-cover">
                <div x-show="!p.img" class="w-full h-full flex items-center justify-center text-gray-400 text-lg">💊</div>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 truncate" x-html="_highlight(p.name)"></p>
                <p class="text-xs text-gray-400" x-text="p.cat"></p>
              </div>
              <p class="text-sm font-bold flex-shrink-0" style="color:var(--primary)" x-text="'{{ $currency }} '+p.price.toFixed(2)"></p>
            </button>
          </template>
        </div>
      </div>

      {{-- WhatsApp --}}
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}" target="_blank" rel="noopener"
         class="hidden sm:flex items-center gap-2 text-white text-xs font-bold px-4 py-2 rounded-full transition btn-primary">
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
        Pedidos
      </a>
      @endif

      {{-- Carrito --}}
      <button @click="drawerOpen=true" class="relative p-2 rounded-full hover:bg-gray-100 transition" aria-label="Ver carrito">
        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <span x-show="cartCount>0" x-text="cartCount"
              class="absolute -top-0.5 -right-0.5 text-[10px] font-black rounded-full w-5 h-5 flex items-center justify-center text-white btn-primary"></span>
      </button>
    </div>
  </div>

  {{-- Nav móvil --}}
  <div class="lg:hidden flex gap-1 px-4 pb-2 no-scroll overflow-x-auto border-t border-gray-100">
    <button @click="filterCat=''; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
            :class="filterCat===''?'text-white':'bg-gray-100 text-gray-500'"
            :style="filterCat===''?'background:var(--primary)':''"
            class="flex-shrink-0 text-xs font-semibold px-3 py-1.5 rounded-full transition mt-2">Todos</button>
    @foreach($categories as $cat)
    <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
            :class="filterCat==='{{ $cat->id }}'?'text-white':'bg-gray-100 text-gray-500'"
            :style="filterCat==='{{ $cat->id }}'?'background:var(--primary)':''"
            class="flex-shrink-0 text-xs font-semibold px-3 py-1.5 rounded-full transition mt-2">{{ $cat->name }}</button>
    @endforeach
  </div>
</header>

{{-- ══════════════════════════════════════ --}}
{{-- HERO --}}
{{-- ══════════════════════════════════════ --}}
<section class="relative overflow-hidden" data-store-native-section="hero" style="min-height:520px;background:{{ $heroBg }};">
  {{-- Fondo decorativo con gradiente --}}
  <div class="absolute inset-0" style="background:linear-gradient(135deg, color-mix(in srgb, var(--primary) 8%, white) 0%, color-mix(in srgb, var(--secondary) 12%, white) 100%);"></div>
  {{-- Círculo decorativo --}}
  <div class="absolute right-0 top-0 w-[700px] h-[700px] rounded-full opacity-[0.07]" style="background:var(--primary);transform:translate(35%,-30%);"></div>
  <div class="absolute left-0 bottom-0 w-[400px] h-[400px] rounded-full opacity-[0.05]" style="background:var(--secondary);transform:translate(-40%,40%);"></div>

  <div class="relative z-10 max-w-7xl mx-auto px-6 py-24 flex flex-col lg:flex-row items-center gap-12">
    {{-- Texto --}}
    <div class="flex-1 text-center lg:text-left">
      @if($heroBadge)
      <span class="inline-block mb-4 text-xs font-bold uppercase tracking-widest px-4 py-1.5 rounded-full text-white" style="background:var(--primary)">{{ $heroBadge }}</span>
      @endif
      <h1 class="poppins font-black text-4xl md:text-5xl lg:text-6xl leading-tight text-gray-900 mb-5">
        {{ $heroTitle }}
      </h1>
      <p class="text-gray-500 text-lg mb-8 leading-relaxed max-w-xl mx-auto lg:mx-0">{{ $heroSub }}</p>
      <div class="flex flex-wrap gap-3 justify-center lg:justify-start">
        <button @click="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                class="btn-primary px-8 py-3.5 rounded-full text-sm font-bold uppercase tracking-wide shadow-lg">
          Ver productos
        </button>
        @if($project->whatsapp)
        <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}" target="_blank" rel="noopener"
           class="btn-outline px-8 py-3.5 rounded-full text-sm font-bold uppercase tracking-wide">
          Contáctanos
        </a>
        @endif
      </div>
    </div>

    {{-- Imagen decorativa / logo grande --}}
    <div class="flex-1 flex items-center justify-center">
      @if($project->logo_url)
      <img src="{{ asset('storage/'.$project->logo_url) }}" alt="{{ $project->name }}" class="max-h-64 object-contain drop-shadow-2xl">
      @else
      <div class="w-64 h-64 rounded-full flex items-center justify-center text-8xl" style="background:color-mix(in srgb, var(--primary) 12%, white)">💊</div>
      @endif
    </div>
  </div>

  {{-- Ola inferior --}}
  <div class="absolute bottom-0 left-0 right-0 overflow-hidden" style="height:50px;">
    <svg viewBox="0 0 1440 50" preserveAspectRatio="none" class="w-full h-full" fill="white">
      <path d="M0,30 C360,60 1080,0 1440,30 L1440,50 L0,50 Z"/>
    </svg>
  </div>
</section>

{{-- ══════════════════════════════════════ --}}
{{-- TRUST BAR --}}
{{-- ══════════════════════════════════════ --}}
<section class="py-10 border-b border-gray-100" data-store-native-section="benefits">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
      @foreach([
        ['💊','Productos de calidad','Garantizados y certificados'],
        ['🚚','Envío rápido','A todo el país'],
        ['🔒','Compra segura','Datos protegidos'],
        ['📞','Atención 24/7','Siempre disponibles'],
      ] as [$icon,$title,$sub])
      <div class="trust-item flex items-center gap-4 p-4 rounded-2xl bg-gray-50 border border-gray-100">
        <span class="text-3xl flex-shrink-0">{{ $icon }}</span>
        <div>
          <p class="font-bold text-sm text-gray-800">{{ $title }}</p>
          <p class="text-xs text-gray-400 mt-0.5">{{ $sub }}</p>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ══════════════════════════════════════ --}}
{{-- PRODUCTOS DESTACADOS (carrusel) --}}
{{-- ══════════════════════════════════════ --}}
@if($featured->count())
<section class="py-14 max-w-7xl mx-auto px-6" data-store-native-section="featured_products">
  <div class="flex items-center justify-between mb-8">
    <div>
      <p class="text-xs font-bold uppercase tracking-widest mb-1" style="color:var(--primary)">Destacados</p>
      <h2 class="poppins font-black text-2xl text-gray-900">Productos populares</h2>
    </div>
    <button @click="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
            class="text-sm font-semibold hover:opacity-80 transition hidden sm:block" style="color:var(--primary)">
      Ver todos →
    </button>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-5">
    @foreach($featured as $p)
    @php $pct = $p->compare_price > 0 ? round((($p->compare_price-$p->price)/$p->compare_price)*100) : 0; @endphp
    <article class="prod-card bg-white rounded-2xl border border-gray-100 overflow-hidden" id="producto-{{ $p->id }}">
      <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="prod-img block relative overflow-hidden bg-gray-50" style="aspect-ratio:1/1;">
        @if($p->mainImage)
        <img src="{{ $p->main_image_url }}" alt="{{ $p->name }}" loading="lazy" class="w-full h-full object-contain p-4">
        @else
        <div class="w-full h-full flex items-center justify-center text-5xl">💊</div>
        @endif
        @if($pct > 0)<div class="absolute top-2 left-2 badge-sale">-{{ $pct }}%</div>@endif
        @if($p->created_at && $p->created_at->diffInDays() <= 30 && !$pct)<div class="absolute top-2 left-2 badge-new">NUEVO</div>@endif
        <div class="overlay absolute bottom-0 left-0 right-0">
          <button @click.prevent="addToCart({id:{{ $p->id }},name:'{{ addslashes($p->name) }}',price:{{ $p->price }},img:'{{ $p->main_image_url ?? '' }}'})"
                  class="w-full btn-primary py-2.5 text-xs font-black uppercase tracking-wide transition">
            {{ $isQuoteOnly ? $btnQuoteText : $btnCartText }}
          </button>
        </div>
      </a>
      <div class="p-3">
        <p class="text-xs font-semibold mb-1 truncate" style="color:var(--primary)">{{ $p->category?->name ?? '' }}</p>
        <a href="{{ route('public.product', [$project->slug, $p->id]) }}"
           class="text-sm font-semibold text-gray-800 hover:text-primary line-clamp-2 leading-snug block mb-2 transition" style="--primary:{{ $primaryColor }}">{{ $p->name }}</a>
        @if(!$isQuoteOnly || $quotePriceDisp==='show')
        <div class="flex items-baseline gap-2">
          <span class="font-black text-base" style="color:var(--primary)">{{ $currency }} {{ number_format($p->price,2) }}</span>
          @if($p->compare_price && $p->compare_price > $p->price)
          <span class="text-xs text-gray-400 line-through">{{ $currency }} {{ number_format($p->compare_price,2) }}</span>
          @endif
        </div>
        @else
        <p class="text-xs text-gray-400 italic">Precio a consultar</p>
        @endif
      </div>
    </article>
    @endforeach
  </div>
</section>
@endif

{{-- ══════════════════════════════════════ --}}
{{-- BANNER PROMO --}}
{{-- ══════════════════════════════════════ --}}
@if($onSale->count())
<section class="py-10 px-6" data-store-native-section="discounts" style="background:linear-gradient(135deg, color-mix(in srgb, var(--primary) 6%, white), color-mix(in srgb, var(--secondary) 8%, white))">
  <div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <div>
        <p class="text-xs font-bold uppercase tracking-widest mb-1" style="color:var(--primary)">Ofertas</p>
        <h2 class="poppins font-black text-2xl text-gray-900">Precios especiales</h2>
      </div>
      <button @click="onSaleFilter=true; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
              class="text-sm font-semibold hidden sm:block" style="color:var(--primary)">Ver todas →</button>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
      @foreach($onSale as $p)
      @php $pct2 = round((($p->compare_price-$p->price)/$p->compare_price)*100); @endphp
      <article class="prod-card bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="prod-img block relative overflow-hidden bg-gray-50" style="aspect-ratio:1/1;">
          @if($p->mainImage)
          <img src="{{ $p->main_image_url }}" alt="{{ $p->name }}" loading="lazy" class="w-full h-full object-contain p-3">
          @else
          <div class="w-full h-full flex items-center justify-center text-4xl">💊</div>
          @endif
          <div class="absolute top-2 left-2 badge-sale">-{{ $pct2 }}%</div>
          <div class="overlay absolute bottom-0 left-0 right-0">
            <button @click.prevent="addToCart({id:{{ $p->id }},name:'{{ addslashes($p->name) }}',price:{{ $p->price }},img:'{{ $p->main_image_url ?? '' }}'})"
                    class="w-full btn-primary py-2 text-[11px] font-black uppercase tracking-wide">
              {{ $isQuoteOnly ? $btnQuoteText : 'Agregar' }}
            </button>
          </div>
        </a>
        <div class="p-2.5">
          <p class="text-[11px] font-semibold text-gray-700 line-clamp-2 mb-1">{{ $p->name }}</p>
          @if(!$isQuoteOnly || $quotePriceDisp==='show')
          <span class="font-black text-sm" style="color:var(--primary)">{{ $currency }} {{ number_format($p->price,2) }}</span>
          @endif
        </div>
      </article>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ══════════════════════════════════════ --}}
{{-- CATÁLOGO COMPLETO --}}
{{-- ══════════════════════════════════════ --}}
<section id="catalogo" class="max-w-7xl mx-auto px-6 py-16">

  {{-- Header --}}
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
    <div>
      <p class="text-xs font-bold uppercase tracking-widest mb-1" style="color:var(--primary)">Productos</p>
      <h2 class="poppins font-black text-2xl text-gray-900">
        <span x-show="filterCat===''">Catálogo completo</span>
        @foreach($categories as $cat)
        <span x-show="filterCat==='{{ $cat->id }}'">{{ $cat->name }}</span>
        @endforeach
      </h2>
    </div>
    <div class="flex items-center gap-2">
      {{-- Buscador móvil --}}
      <div class="md:hidden flex items-center bg-gray-50 border border-gray-200 rounded-full px-3 py-2 gap-2 flex-1" @click.outside="searchOpen=false">
        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
        <input x-model="search" type="search" placeholder="Buscar..."
               @input="searchOpen=search.trim().length>=2"
               class="bg-transparent text-sm outline-none flex-1 text-gray-700 placeholder-gray-400">
      </div>
      <select x-model="sortBy" class="text-xs border border-gray-200 px-3 py-2 rounded-full outline-none text-gray-600 cursor-pointer bg-white">
        <option value="default">Ordenar</option>
        <option value="price_asc">Precio ↑</option>
        <option value="price_desc">Precio ↓</option>
        <option value="newest">Más nuevos</option>
        <option value="name_az">A→Z</option>
      </select>
      <button x-show="filterCat!==''||search!==''"
              @click="filterCat='';search=''"
              class="text-xs text-gray-400 hover:text-red-500 transition underline">Limpiar</button>
    </div>
  </div>

  {{-- Grid por categorías --}}
  <div class="flex gap-8">

    {{-- Sidebar categorías --}}
    <aside class="w-44 flex-shrink-0 hidden xl:block">
      <p class="text-[11px] font-bold uppercase tracking-widest mb-3" style="color:var(--primary)">Categorías</p>
      <div class="space-y-0.5">
        <button @click="filterCat=''"
                :class="filterCat===''?'font-bold':'text-gray-500 hover:text-gray-800'"
                :style="filterCat===''?'color:var(--primary)':''"
                class="block w-full text-left text-sm py-1.5 transition">
          Todo el catálogo
        </button>
        @foreach($categories as $cat)
        <button @click="filterCat='{{ $cat->id }}'"
                :class="filterCat==='{{ $cat->id }}'?'font-bold':'text-gray-500 hover:text-gray-800'"
                :style="filterCat==='{{ $cat->id }}'?'color:var(--primary)':''"
                class="block w-full text-left text-sm py-1.5 transition flex items-center justify-between">
          <span>{{ $cat->name }}</span>
          <span class="text-xs text-gray-300">{{ $cat->products->count() }}</span>
        </button>
        @endforeach
      </div>
    </aside>

    {{-- Productos --}}
    <div class="flex-1">
      @foreach($categories as $cat)
      @php $catAllProducts = $cat->products->merge($cat->children->flatMap->products); @endphp
      @if($catAllProducts->count())
      <div x-show="filterCat==='' || filterCat==='{{ $cat->id }}'" class="mb-12">
        <div class="flex items-center gap-3 mb-5">
          <div class="w-1 h-6 rounded-full" style="background:var(--primary)"></div>
          <h3 class="poppins font-bold text-lg text-gray-900">{{ $cat->name }}</h3>
          <div class="flex-1 border-t border-gray-100"></div>
          <span class="text-xs text-gray-400">{{ $catAllProducts->count() }} productos</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
          @foreach($catAllProducts as $p)
          @php
            $pct3 = ($p->compare_price && $p->compare_price > $p->price) ? round((($p->compare_price-$p->price)/$p->compare_price)*100) : 0;
          @endphp
          <article class="prod-card bg-white rounded-2xl border border-gray-100 overflow-hidden"
                   id="producto-{{ $p->id }}"
                   x-show="(filterCat==='' || filterCat==='{{ $cat->id }}') && (!search || '{{ strtolower(addslashes($p->name)) }}'.includes(search.toLowerCase()))">

            <a href="{{ route('public.product', [$project->slug, $p->id]) }}" class="prod-img block relative overflow-hidden bg-gray-50" style="aspect-ratio:1/1;">
              @if($p->mainImage)
              <img src="{{ $p->main_image_url }}" alt="{{ $p->name }}" loading="lazy" class="w-full h-full object-contain p-4">
              @else
              <div class="w-full h-full flex items-center justify-center text-5xl">💊</div>
              @endif
              @if($pct3 > 0)<div class="absolute top-2 left-2 badge-sale">-{{ $pct3 }}%</div>@endif
              @if($p->stock !== null && $p->stock === 0)
              <div class="absolute inset-0 bg-white/70 flex items-center justify-center">
                <span class="text-xs font-black text-gray-500 uppercase tracking-widest">Agotado</span>
              </div>
              @endif
              <div class="overlay absolute bottom-0 left-0 right-0" x-show="{{ $p->stock === null || $p->stock > 0 ? 'true' : 'false' }}">
                <button @click.prevent="addToCart({id:{{ $p->id }},name:'{{ addslashes($p->name) }}',price:{{ $p->price }},img:'{{ $p->main_image_url ?? '' }}'})"
                        class="w-full btn-primary py-2.5 text-xs font-black uppercase tracking-wide transition">
                  {{ $isQuoteOnly ? $btnQuoteText : $btnCartText }}
                </button>
              </div>
            </a>

            <div class="p-3">
              @if($p->category)
              <p class="text-[10px] font-semibold uppercase tracking-widest mb-1" style="color:var(--primary);opacity:.7">{{ $p->category->name }}</p>
              @endif
              <a href="{{ route('public.product', [$project->slug, $p->id]) }}"
                 class="text-sm font-semibold text-gray-800 line-clamp-2 leading-snug block mb-2 hover:opacity-80 transition">{{ $p->name }}</a>
              @if(!$isQuoteOnly || $quotePriceDisp==='show')
              <div class="flex items-baseline gap-2">
                <span class="font-black" style="color:var(--primary)">{{ $currency }} {{ number_format($p->price,2) }}</span>
                @if($p->compare_price && $p->compare_price > $p->price)
                <span class="text-xs text-gray-400 line-through">{{ $currency }} {{ number_format($p->compare_price,2) }}</span>
                @endif
              </div>
              @else
              <p class="text-xs text-gray-400 italic">Precio a consultar</p>
              @endif
            </div>
          </article>
          @endforeach
        </div>
      </div>
      @endif
      @endforeach

      <div x-show="filterCat!=='' && !{{ $categories->count() }}" class="text-center py-20">
        <p class="text-5xl mb-4">🔍</p>
        <p class="poppins font-bold text-gray-900 text-xl mb-2">Sin resultados</p>
        <p class="text-gray-400 text-sm mb-5">Intenta con otro término o categoría</p>
        <button @click="search='';filterCat=''" class="btn-primary px-6 py-2.5 rounded-full text-sm font-bold">
          Ver todo el catálogo
        </button>
      </div>
    </div>
  </div>
</section>

{{-- ══════════════════════════════════════ --}}
{{-- FOOTER --}}
{{-- ══════════════════════════════════════ --}}
<footer class="border-t border-gray-100" style="background:#f8fafc;">
  <div class="max-w-7xl mx-auto px-6 py-14 grid grid-cols-1 md:grid-cols-3 gap-10">
    <div>
      @if($project->logo_url)
      <img src="{{ asset('storage/'.$project->logo_url) }}" alt="{{ $project->name }}" class="h-12 object-contain mb-4">
      @else
      <p class="poppins font-black text-2xl mb-2" style="color:var(--primary)">{{ $project->name }}</p>
      @endif
      @if($footerTagline)
      <p class="text-sm text-gray-500 leading-relaxed mb-4">{{ $footerTagline }}</p>
      @endif
      @if($project->whatsapp)
      <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}" target="_blank" rel="noopener"
         class="inline-flex items-center gap-2 btn-primary text-xs font-bold px-4 py-2.5 rounded-full">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
        Hacer pedido
      </a>
      @endif
    </div>

    <div>
      <p class="text-[11px] font-bold uppercase tracking-widest mb-4" style="color:var(--primary)">Categorías</p>
      <ul class="space-y-2">
        @foreach($categories->take(7) as $cat)
        <li>
          <button @click="filterCat='{{ $cat->id }}'; document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})"
                  class="text-sm text-gray-500 hover:text-gray-800 transition">{{ $cat->name }}</button>
        </li>
        @endforeach
      </ul>
    </div>

    <div>
      <p class="text-[11px] font-bold uppercase tracking-widest mb-4" style="color:var(--primary)">Contacto</p>
      <div class="space-y-2 text-sm text-gray-500">
        @if($project->phone)<p>📞 {{ $project->phone }}</p>@endif
        @if($project->address)<p>📍 {{ $project->address }}</p>@endif
        @if($project->whatsapp)<p>💬 {{ $project->whatsapp }}</p>@endif
      </div>
      @php $socials=[['facebook_url','#1877F2','FB'],['instagram_url','#E1306C','IG'],['tiktok_url','#000','TK'],['youtube_url','#FF0000','YT']]; @endphp
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
        <p class="text-[11px] font-bold uppercase tracking-widest mb-2" style="color:var(--primary)">Métodos de pago</p>
        <div class="flex flex-wrap gap-1.5">
          @foreach($acceptedPayments as $pk)
          @if(isset($paymentMeta[$pk]))
          <span class="text-xs px-2 py-1 rounded-lg text-gray-500 bg-white border border-gray-200">{{ $paymentMeta[$pk]['emoji'] }} {{ $paymentMeta[$pk]['label'] }}</span>
          @endif
          @endforeach
        </div>
      </div>
      @endif
    </div>
  </div>
  <div class="border-t border-gray-200 py-4 text-center text-xs text-gray-400">
    {{ $footerCopyright }} &mdash; Catálogo online por <span class="font-semibold" style="color:var(--primary)">BIXO</span>
  </div>
</footer>

{{-- ══════════════════════════════════════ --}}
{{-- CART DRAWER --}}
{{-- ══════════════════════════════════════ --}}
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
        <button x-show="drawerStep===2&&!orderSent" @click="drawerStep=1"
                class="p-1.5 hover:bg-gray-100 rounded-lg transition mr-1">
          <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <h2 class="font-black text-gray-900 text-base"
            x-text="drawerStep===1?'{{ $isQuoteOnly?'Mi cotización':'Tu pedido' }}':'Confirmar datos'"></h2>
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
            <span class="text-5xl block mb-4">🛒</span>
            <p class="font-bold text-gray-600 mb-1">{{ $isQuoteOnly?'Tu cotización está vacía':'Tu carrito está vacío' }}</p>
            <p class="text-sm">Agrega productos para comenzar</p>
          </div>
        </template>
        <template x-for="(item,i) in cart" :key="item.id">
          <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-xl">
            <div class="w-14 h-14 rounded-lg overflow-hidden flex-shrink-0 bg-gray-100">
              <img :src="item.img" x-show="item.img" class="w-full h-full object-cover">
              <div x-show="!item.img" class="w-full h-full flex items-center justify-center text-2xl">💊</div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-gray-800 line-clamp-2" x-text="item.name"></p>
              @if(!$isQuoteOnly || $quotePriceDisp==='show')
              <p class="text-sm font-black mt-0.5" style="color:var(--primary)" x-text="'{{ $currency }} '+(item.price*item.qty).toFixed(2)"></p>
              @endif
            </div>
            <div class="flex items-center gap-1 flex-shrink-0">
              <button @click="item.qty>1?item.qty--:cart.splice(i,1)"
                      class="w-8 h-8 border-2 border-gray-200 hover:border-red-300 hover:bg-red-50 text-gray-600 hover:text-red-500 font-bold text-sm transition flex items-center justify-center rounded-lg">
                <span x-text="item.qty>1?'−':'×'"></span>
              </button>
              <span class="w-7 text-center text-sm font-black text-gray-800" x-text="item.qty"></span>
              <button @click="item.qty++"
                      class="w-8 h-8 text-white font-bold text-sm transition flex items-center justify-center rounded-lg btn-primary">+</button>
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
                class="w-full btn-primary py-3.5 font-black text-sm uppercase tracking-widest transition rounded-xl flex items-center justify-center gap-2">
          {{ $isQuoteOnly?'Continuar y cotizar':'Continuar al pedido' }} →
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
               class="w-full border border-gray-200 focus:border-blue-400 rounded-xl px-4 py-2.5 text-sm outline-none transition">
        <input x-model="form.phone" type="tel" placeholder="Tu WhatsApp / teléfono *"
               class="w-full border border-gray-200 focus:border-blue-400 rounded-xl px-4 py-2.5 text-sm outline-none transition">
        <input x-model="form.email" type="email" placeholder="Correo electrónico (opcional)"
               class="w-full border border-gray-200 focus:border-blue-400 rounded-xl px-4 py-2.5 text-sm outline-none transition">
        <textarea x-model="form.notes" rows="2" placeholder="Nota adicional (opcional)"
                  class="w-full border border-gray-200 focus:border-blue-400 rounded-xl px-4 py-2.5 text-sm outline-none resize-none transition"></textarea>
        @if($requireAddress)
        <input x-model="form.address" type="text" placeholder="Dirección de entrega *"
               class="w-full border border-gray-200 focus:border-blue-400 rounded-xl px-4 py-2.5 text-sm outline-none transition">
        @endif
        {{-- Cupón --}}
        <div>
          <div x-show="!couponApplied" class="flex gap-2">
            <input x-model="couponCode" @keydown.enter.prevent="applyCoupon" type="text" placeholder="Código de descuento"
                   class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none transition uppercase">
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
          <div x-show="shippingEnabled" class="flex justify-between"
               :class="effectiveShipping===0&&shippingFreeFrom>0?'text-green-600 font-medium':'text-gray-500'">
            <span x-text="effectiveShipping===0&&shippingFreeFrom>0?'🎉 Envío gratis':'Envío'"></span>
            <span x-text="effectiveShipping>0?'{{ $currency }} '+effectiveShipping.toFixed(2):'Gratis'"></span>
          </div>
          <div class="flex justify-between font-black text-gray-900 border-t border-gray-200 pt-1.5">
            <span>Total</span><span x-text="'{{ $currency }} '+orderGrandTotal.toFixed(2)"></span>
          </div>
        </div>
        <p x-show="orderError" class="text-red-500 text-xs text-center font-medium" x-text="orderError"></p>
      </div>

      {{-- Éxito --}}
      <div x-show="orderSent" class="flex-1 flex flex-col items-center justify-center px-5 py-8 text-center">
        <div class="w-20 h-20 bg-green-100 flex items-center justify-center mb-5 rounded-full">
          <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>
        <p class="font-black text-gray-900 text-xl mb-2">{{ $isQuoteOnly?'¡Cotización enviada!':'¡Pedido confirmado!' }}</p>
        <p x-show="orderId" class="text-xs text-gray-400 mb-2">Pedido N° <span class="font-black text-gray-700" x-text="orderId"></span></p>
        <p class="text-sm text-gray-500 mb-6 leading-relaxed">Recibimos tu solicitud y nos pondremos en contacto pronto.</p>
        <button @click="cart=[];orderSent=false;drawerStep=1;form={name:'',phone:'',email:'',notes:'',address:''};drawerOpen=false;try{localStorage.removeItem('avan_cart_{{ $project->id }}');}catch(e){}"
                class="btn-primary px-8 py-3 rounded-full text-sm font-bold uppercase tracking-widest transition">
          Seguir comprando
        </button>
      </div>

      <div x-show="!orderSent" class="border-t border-gray-100 px-5 py-4 space-y-2 flex-shrink-0">
        @if($quoteWa)
        <button @click="sendWhatsApp()"
                class="w-full flex items-center justify-center gap-2 py-3.5 font-black text-sm uppercase tracking-widest rounded-xl text-white transition"
                style="background:#25D366">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
          {{ $isQuoteOnly?'Enviar cotización por WhatsApp':'Pedir por WhatsApp' }}
        </button>
        @endif
        @if(!$isQuoteOnly)
        <button @click="submitOrder()" :disabled="orderLoading"
                class="w-full btn-primary py-3.5 font-black text-sm uppercase tracking-widest rounded-xl disabled:opacity-60">
          <span x-text="orderLoading?'Procesando...':'Confirmar pedido'"></span>
        </button>
        @endif
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  var track = document.getElementById('frm-cats-track');
  var prev  = document.getElementById('frm-cat-prev');
  var next  = document.getElementById('frm-cat-next');
  var drop  = document.getElementById('frm-sub-dropdown');
  if(!track) return;
  function update(){
    prev.classList.toggle('g-hidden', track.scrollLeft <= 4);
    next.classList.toggle('g-hidden', track.scrollLeft + track.clientWidth >= track.scrollWidth - 4);
  }
  window.frmCatScroll = function(dir){ track.scrollBy({left: dir * 200, behavior:'smooth'}); };
  track.addEventListener('scroll', update, {passive:true});
  setTimeout(update, 150);

  var SUBS = @json($categories->mapWithKeys(fn($c) => [(string)$c->id => $c->children->map(fn($s) => ['id'=>(string)$s->id,'name'=>$s->name])->values()]));
  var hideT = null;
  function showDrop(wrap){
    var subs = SUBS[wrap.dataset.catId]; if(!subs||!subs.length) return;
    clearTimeout(hideT);
    drop.innerHTML = subs.map(function(s){ return '<button onclick="frmPickSub(\''+s.id+'\')">'+s.name+'</button>'; }).join('');
    var r = wrap.getBoundingClientRect();
    drop.style.top = r.bottom+'px'; drop.style.left = r.left+'px'; drop.style.display='block';
  }
  function hideDrop(){ hideT = setTimeout(function(){ drop.style.display='none'; }, 150); }
  window.frmPickSub = function(id){
    var root = document.querySelector('[x-data]');
    if(root && root._x_dataStack && root._x_dataStack[0]) root._x_dataStack[0].filterCat = id;
    drop.style.display='none';
  };
  track.querySelectorAll('.frm-cat-wrap[data-has-sub]').forEach(function(w){
    w.addEventListener('mouseenter', function(){ showDrop(w); });
    w.addEventListener('mouseleave', hideDrop);
  });
  if(drop){
    drop.addEventListener('mouseenter', function(){ clearTimeout(hideT); });
    drop.addEventListener('mouseleave', hideDrop);
  }
})();
</script>
<x-public-store-runtime :project="$project" :settings="$settings" :popup="$popup ?? null" :sections="$sections ?? collect()" :about-page="$aboutPage ?? null" />
</body>
</html>
