<!DOCTYPE html>
<html lang="es" prefix="og: https://ogp.me/ns#">
<head>
@php
  use Illuminate\Support\Str;
  $primaryColor   = $settings['primary_color'] ?? '#4f46e5';
  $currency       = $settings['currency_symbol'] ?? $settings['currency'] ?? 'S/';
  $headerBg       = $settings['header_bg_color'] ?? '#ffffff';
  $headerText     = $settings['header_text_color'] ?? '#111827';
  $headerHeight   = max(48, (int)($settings['header_height'] ?? 72));
  $footerBg       = $settings['footer_bg_color']   ?? '#111827';
  $footerText     = $settings['footer_text_color'] ?? '#9ca3af';
  $footerLogoH    = max(24, (int)($settings['footer_logo_height'] ?? 60));
  $logoRaw        = $settings['logo_url'] ?? $project->logo_url ?? '';
  $logoSrc        = $logoRaw ? (str_starts_with($logoRaw,'http') ? $logoRaw : asset('storage/'.$logoRaw)) : '';
  $logoHeight     = (int)($settings['logo_height'] ?? 40);
  $faviconRaw     = $settings['favicon_url'] ?? '';
  $faviconUrl     = $faviconRaw ? (str_starts_with($faviconRaw,'http') ? $faviconRaw : asset('storage/'.$faviconRaw)) : '';
  $isQuoteOnly    = ($settings['store_mode'] ?? 'direct') === 'quote_only';
  $quotePriceDisp = $settings['quote_price_display'] ?? 'show';
  $quoteWaRaw     = preg_replace('/\D/', '', $settings['quote_whatsapp'] ?? '');
  if (!$quoteWaRaw) $quoteWaRaw = preg_replace('/\D/', '', $project->whatsapp ?? '');
  $quoteWaCountry = $settings['quote_whatsapp_country'] ?? '51';
  $quoteWa = '';
  if ($quoteWaRaw) {
      $quoteWa = str_starts_with($quoteWaRaw, $quoteWaCountry) ? $quoteWaRaw : $quoteWaCountry.$quoteWaRaw;
  }
  $canonicalUrl = url('/' . $project->slug . '/p/' . $product->id);
  $catalogUrl   = url('/' . $project->slug);
  $seoTitle     = $product->name . ' — ' . $project->name;
  $seoDescRaw   = strip_tags($product->description ?? $project->description ?? '');
  $seoDesc      = Str::limit($seoDescRaw ?: 'Ver detalles del producto.', 160);
  $mainImg      = $product->mainImage ?? $product->images->first();
  $imgUrl       = fn($img) => $img ? (str_starts_with($img->url, 'http') ? $img->url : asset('storage/'.$img->url)) : null;
  $ogImage      = $mainImg ? $imgUrl($mainImg)
                           : ($project->logo_url ? asset('storage/'.$project->logo_url) : asset('img/og-default.png'));
  $discount     = ($product->compare_price && $product->compare_price > $product->price)
      ? round((($product->compare_price - $product->price) / $product->compare_price) * 100)
      : 0;
  $schemaImages = $product->images->map(fn($img) => $imgUrl($img))->toJson();
  $searchIndex  = $categories->flatMap(fn($cat) => $cat->products->map(fn($p) => [
      'id'    => $p->id,
      'name'  => $p->name,
      'price' => (float)$p->price,
      'cp'    => $p->compare_price ? (float)$p->compare_price : null,
      'img'   => $p->mainImage ? $p->main_image_url : null,
      'cat'   => $cat->name,
      'catId' => (string)$cat->id,
      'url'   => url('/'.$project->slug.'/p/'.$p->id),
  ]))->values();
@endphp
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $seoTitle }}</title>
@if($faviconUrl)<link rel="icon" href="{{ $faviconUrl }}">@endif
<meta name="description" content="{{ $seoDesc }}">
<meta name="robots" content="index, follow">
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta property="og:type" content="product">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDesc }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:locale" content="es_PE">
<meta property="og:site_name" content="{{ $project->name }}">
@if(!$isQuoteOnly || $quotePriceDisp === 'show')
<meta property="product:price:amount" content="{{ $product->price }}">
<meta property="product:price:currency" content="PEN">
@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDesc }}">
<meta name="twitter:image" content="{{ $ogImage }}">

<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@type": "Product",
  "name": "{{ addslashes($product->name) }}",
  "description": "{{ addslashes(Str::limit(strip_tags($product->description ?? ''), 500)) }}",
  "image": {!! $schemaImages !!},
  "url": "{{ $canonicalUrl }}",
  "sku": "{{ $product->sku ?? '' }}",
  "brand": { "@type": "Organization", "name": "{{ addslashes($project->name) }}" },
  "offers": {
    "@type": "Offer",
    "priceCurrency": "PEN",
    "price": "{{ $product->price }}",
    "availability": "https://schema.org/InStock",
    "url": "{{ $canonicalUrl }}"
  }
}
</script>

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root { --c: {{ $primaryColor }}; }
*, body { font-family: 'Inter', sans-serif; }
[x-cloak]{ display:none!important; }
.btn-p      { background: var(--c); color: #fff; }
.btn-p:hover{ filter: brightness(.88); }
.text-p     { color: var(--c); }
.ring-p     { --tw-ring-color: var(--c); }
.badge-p    { background: var(--c); color: #fff; }
.scrollbar-hide::-webkit-scrollbar { display:none; }
.scrollbar-hide { -ms-overflow-style:none; scrollbar-width:none; }
</style>
</head>
<body class="bg-gray-50 min-h-screen" x-data="productStore()">

@php $canonicalUrl = $catalogUrl; $logoUrl = $logoSrc; $headerCartBtn = false; @endphp
@include('public.partials.header')

{{-- ─── MAIN ─── --}}
<main class="max-w-5xl mx-auto px-4 py-5 sm:py-8">

  {{-- Breadcrumb --}}
  <nav class="text-xs text-gray-400 mb-5 flex items-center gap-1.5 flex-wrap">
    <a href="{{ $catalogUrl }}" class="hover:text-gray-700 transition">{{ $project->name }}</a>
    @if($product->category)
    <span>/</span>
    <span class="hover:text-gray-700 transition">{{ $product->category->name }}</span>
    @endif
    <span>/</span>
    <span class="text-gray-600 font-medium truncate max-w-[200px]">{{ $product->name }}</span>
  </nav>

  {{-- Product layout --}}
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-10 mb-12">

    {{-- Gallery --}}
    <div>
      <div class="relative bg-white rounded-2xl overflow-hidden border border-gray-200 mb-3" style="aspect-ratio:1/1;">
        @if($product->images->count())
          @foreach($product->images as $i => $img)
          <img src="{{ $imgUrl($img) }}"
               alt="{{ $product->name }}"
               x-show="activeImg === {{ $i }}"
               class="w-full h-full object-contain p-4"
               loading="{{ $i === 0 ? 'eager' : 'lazy' }}">
          @endforeach
        @else
          <div class="w-full h-full flex items-center justify-center bg-gray-50">
            <svg class="w-20 h-20 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
        @endif
        @if($discount)
        <div class="absolute top-3 left-3">
          <span class="bg-red-500 text-white text-xs font-black px-2 py-1 rounded-lg">-{{ $discount }}%</span>
        </div>
        @endif
      </div>
      {{-- Thumbnails --}}
      @if($product->images->count() > 1)
      <div class="flex gap-2 flex-wrap">
        @foreach($product->images as $i => $img)
        <button @click="activeImg = {{ $i }}"
                :class="activeImg === {{ $i }} ? 'ring-2 ring-offset-1 ring-p' : 'ring-1 ring-gray-200 hover:ring-gray-300'"
                class="w-14 h-14 rounded-lg overflow-hidden bg-white flex-shrink-0 transition">
          <img src="{{ $imgUrl($img) }}" alt="{{ $product->name }}" class="w-full h-full object-cover" loading="lazy">
        </button>
        @endforeach
      </div>
      @endif
    </div>

    {{-- Product info --}}
    <div class="flex flex-col">
      @if($product->category)
      <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1.5">{{ $product->category->name }}</p>
      @endif
      <h1 class="text-2xl sm:text-3xl font-black text-gray-900 leading-tight mb-4">{{ $product->name }}</h1>

      @if(!$isQuoteOnly || $quotePriceDisp === 'show')
      <div class="flex items-baseline gap-3 mb-5 flex-wrap">
        <span class="text-3xl font-black text-p">{{ $currency }} {{ number_format($product->price, 2) }}</span>
        @if($product->compare_price && $product->compare_price > $product->price)
        <span class="text-lg text-gray-400 line-through">{{ $currency }} {{ number_format($product->compare_price, 2) }}</span>
        <span class="text-sm font-bold text-red-500 bg-red-50 px-2 py-0.5 rounded-lg">Ahorras {{ $currency }} {{ number_format($product->compare_price - $product->price, 2) }}</span>
        @endif
      </div>
      @else
      <p class="text-gray-500 italic mb-5">Precio a consultar</p>
      @endif

      @if($product->stock !== null && $product->stock === 0)
      <p class="text-sm font-bold text-red-600 mb-3 flex items-center gap-1.5">
        <span class="inline-block w-2 h-2 rounded-full bg-red-600"></span>Agotado — no disponible actualmente
      </p>
      @elseif($product->stock !== null && $product->stock > 0 && $product->stock <= 5)
      <p class="text-sm font-bold text-amber-600 mb-3 flex items-center gap-1.5">
        <span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span>Últimas {{ $product->stock }} unidades disponibles
      </p>
      @endif

      @if($product->sku)
      <p class="text-xs text-gray-400 mb-4">SKU: <span class="font-mono text-gray-600">{{ $product->sku }}</span></p>
      @endif

      @if($product->description)
      <div class="text-sm text-gray-600 mb-6 leading-relaxed whitespace-pre-line">{{ $product->description }}</div>
      @endif

      {{-- CTA --}}
      <div class="flex flex-col gap-3 mt-auto">
        @if(!$isQuoteOnly)
        <button @click="addToCart()"
                {{ $product->stock !== null && $product->stock === 0 ? 'disabled' : '' }}
                class="btn-p w-full py-3.5 rounded-xl font-black text-base flex items-center justify-center gap-2 transition disabled:opacity-50 disabled:cursor-not-allowed">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
          Agregar al carrito
        </button>
        @endif
        @if($quoteWa)
        <a href="https://wa.me/{{ $quoteWa }}?text={{ urlencode('Hola, me interesa este producto: ' . $product->name . ' — ' . $canonicalUrl) }}"
           target="_blank" rel="noopener"
           class="w-full py-3.5 rounded-xl font-black text-base flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#20ba5a] text-white transition">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
          </svg>
          Consultar por WhatsApp
        </a>
        @endif
      </div>

      {{-- Toast --}}
      <div x-show="added" x-cloak x-transition
           class="mt-4 bg-green-50 border border-green-200 text-green-700 text-sm font-semibold px-4 py-2.5 rounded-xl flex items-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg>
        Producto agregado al carrito
        <button @click="drawerOpen=true" class="ml-auto underline text-green-600 font-bold text-xs">Ver carrito</button>
      </div>

      {{-- Compartir --}}
      @php
        $shareUrl  = $canonicalUrl;
        $shareText = urlencode($product->name . ' — ' . $project->name . ' ' . $canonicalUrl);
        $shareFb   = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($canonicalUrl);
        $shareWa   = 'https://wa.me/?text=' . $shareText;
        $shareTg   = 'https://t.me/share/url?url=' . urlencode($canonicalUrl) . '&text=' . urlencode($product->name);
        $shareX    = 'https://twitter.com/intent/tweet?text=' . $shareText;
      @endphp
      <div class="flex items-center gap-2.5 pt-4 border-t border-gray-100 mt-3">
        <span class="text-xs text-gray-400 font-medium flex-shrink-0">Compartir:</span>
        <div class="flex items-center gap-1.5">
          {{-- Copiar enlace --}}
          <button @click="copyLink()" title="Copiar enlace"
                  class="w-8 h-8 rounded-lg flex items-center justify-center transition"
                  :class="copied ? 'bg-green-100 text-green-600' : 'bg-gray-100 hover:bg-gray-200 text-gray-600'">
            <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <svg x-show="copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
          </button>
          {{-- WhatsApp --}}
          <a href="{{ $shareWa }}" target="_blank" rel="noopener" title="Compartir por WhatsApp"
             class="w-8 h-8 rounded-lg flex items-center justify-center bg-[#e7faea] hover:bg-[#d2f5d5] text-[#25D366] transition">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.121.55 4.112 1.514 5.845L.057 23.25l5.538-1.453A11.943 11.943 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.854 0-3.6-.5-5.107-1.375l-.366-.217-3.787.994 1.01-3.692-.238-.38A9.943 9.943 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
            </svg>
          </a>
          {{-- Facebook --}}
          <a href="{{ $shareFb }}" target="_blank" rel="noopener" title="Compartir en Facebook"
             class="w-8 h-8 rounded-lg flex items-center justify-center bg-[#e7eeff] hover:bg-[#d4deff] text-[#1877F2] transition">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
              <path d="M24 12.073C24 5.404 18.627 0 12 0S0 5.404 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.886v2.268h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/>
            </svg>
          </a>
          {{-- Telegram --}}
          <a href="{{ $shareTg }}" target="_blank" rel="noopener" title="Compartir en Telegram"
             class="w-8 h-8 rounded-lg flex items-center justify-center bg-[#e6f4fb] hover:bg-[#d0ebf7] text-[#0088cc] transition">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.069l-2.03 9.568c-.144.658-.534.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.294.294-.603.294l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L6.77 14.155l-2.95-.924c-.641-.2-.654-.641.136-.949l11.533-4.448c.534-.194 1.002.13.073.235z"/>
            </svg>
          </a>
          {{-- X / Twitter --}}
          <a href="{{ $shareX }}" target="_blank" rel="noopener" title="Compartir en X"
             class="w-8 h-8 rounded-lg flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-800 transition">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.737-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
            </svg>
          </a>
        </div>
        <span x-show="copied" x-cloak x-transition class="text-xs text-green-600 font-semibold">¡Copiado!</span>
      </div>
    </div>
  </div>

  {{-- Reviews --}}
  <section class="pt-8 border-t border-gray-200 mb-12" x-data="reviewStore()">
    <div class="flex items-center gap-4 mb-6">
      <h2 class="text-lg font-black text-gray-900">Reseñas</h2>
      @if($avgRating)
      <div class="flex items-center gap-1.5">
        <span class="text-amber-400 text-base leading-none tracking-tighter">{{ str_repeat('★', floor($avgRating)) }}{{ $avgRating - floor($avgRating) >= 0.5 ? '★' : '' }}{{ str_repeat('☆', max(0, 5 - ceil($avgRating))) }}</span>
        <span class="text-sm font-bold text-gray-700">{{ $avgRating }}</span>
        <span class="text-sm text-gray-400">({{ $reviews->count() }} {{ $reviews->count() === 1 ? 'reseña' : 'reseñas' }})</span>
      </div>
      @else
      <span class="text-sm text-gray-400">Sin reseñas aún</span>
      @endif
    </div>

    {{-- Existing reviews --}}
    @if($reviews->count())
    <div class="space-y-4 mb-8">
      @foreach($reviews as $rev)
      <div class="bg-white border border-gray-200 rounded-xl p-4 flex gap-3">
        <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
          <span class="text-indigo-600 font-bold text-sm">{{ mb_strtoupper(mb_substr($rev->author_name, 0, 1)) }}</span>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex flex-wrap items-center gap-2 mb-1">
            <span class="font-semibold text-sm text-gray-800">{{ $rev->author_name }}</span>
            <span class="text-amber-400 text-sm leading-none tracking-tighter">{{ str_repeat('★', $rev->rating) }}{{ str_repeat('☆', 5 - $rev->rating) }}</span>
            <span class="text-xs text-gray-400">{{ $rev->created_at->diffForHumans() }}</span>
          </div>
          @if($rev->comment)
          <p class="text-sm text-gray-600 leading-relaxed">{{ $rev->comment }}</p>
          @endif
        </div>
      </div>
      @endforeach
    </div>
    @endif

    {{-- Review form --}}
    <div class="bg-gray-50 rounded-2xl p-5">
      <h3 class="font-bold text-gray-800 mb-4 text-sm">Deja tu reseña</h3>

      <div x-show="sent" x-cloak x-transition class="text-center py-4">
        <svg class="w-10 h-10 text-green-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm font-semibold text-gray-700">¡Gracias! Tu reseña será revisada antes de publicarse.</p>
      </div>

      <form @submit.prevent="submit()" x-show="!sent">
        {{-- Star picker --}}
        <div class="flex items-center gap-1 mb-4">
          <template x-for="n in [1,2,3,4,5]" :key="n">
            <button type="button"
                    @click="form.rating = n; hoverRating = 0"
                    @mouseenter="hoverRating = n"
                    @mouseleave="hoverRating = 0"
                    class="text-2xl leading-none transition"
                    :class="(hoverRating || form.rating) >= n ? 'text-amber-400' : 'text-gray-300'">★</button>
          </template>
          <span class="text-xs text-gray-400 ml-2" x-text="['','Muy malo','Malo','Regular','Bueno','Excelente'][hoverRating || form.rating] || 'Selecciona una puntuación'"></span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
          <input x-model="form.author_name" type="text" placeholder="Tu nombre *"
                 class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-gray-400 bg-white transition">
          <input x-model="form.author_email" type="email" placeholder="Tu email (opcional)"
                 class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-gray-400 bg-white transition">
        </div>
        <textarea x-model="form.comment" rows="3" placeholder="Cuéntanos tu experiencia (opcional)"
                  class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-gray-400 bg-white transition resize-none mb-3"></textarea>

        <div x-show="error" x-cloak class="text-xs text-red-600 font-medium mb-3" x-text="error"></div>

        <button type="submit" :disabled="loading"
                class="btn-p px-6 py-2.5 rounded-xl font-bold text-sm transition disabled:opacity-50 flex items-center gap-2">
          <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
          </svg>
          <span x-text="loading ? 'Enviando…' : 'Publicar reseña'"></span>
        </button>
      </form>
    </div>
  </section>

  {{-- Related products --}}
  @if($related->count())
  <section class="pt-6 border-t border-gray-200">
    <h2 class="text-lg font-black text-gray-900 mb-4">Productos relacionados</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
      @foreach($related as $r)
      <a href="{{ route('public.product', [$project->slug, $r->id]) }}"
         class="group bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
        <div class="bg-gray-50 overflow-hidden" style="aspect-ratio:1/1;">
          @if($r->mainImage)
          <img src="{{ $r->main_image_url }}" alt="{{ $r->name }}"
               loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
          @else
          <div class="w-full h-full flex items-center justify-center">
            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
          @endif
        </div>
        <div class="p-2.5">
          <p class="text-xs font-bold text-gray-800 line-clamp-2 leading-snug mb-1">{{ $r->name }}</p>
          @if(!$isQuoteOnly || $quotePriceDisp === 'show')
          <p class="text-sm font-black text-p">{{ $currency }} {{ number_format($r->price, 2) }}</p>
          @endif
        </div>
      </a>
      @endforeach
    </div>
  </section>
  @endif

</main>

{{-- ─── FOOTER ─── --}}
@include('public.partials.footer', ['footerBg'=>$footerBg,'footerText'=>$footerText,'footerLogoH'=>$footerLogoH,'logoSrc'=>$logoSrc])

{{-- ─── CART DRAWER ─── --}}
<div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50 flex justify-end">
  <div @click="drawerOpen=false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
  <div class="relative w-full max-w-sm bg-white flex flex-col shadow-2xl h-full">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
      <h3 class="font-black text-gray-900 text-lg">Tu carrito</h3>
      <button @click="drawerOpen=false" class="text-gray-400 hover:text-gray-700 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <div x-show="cart.length === 0" class="flex-1 flex flex-col items-center justify-center py-16 px-6 text-center">
      <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
      </svg>
      <p class="font-bold text-gray-400 text-sm">Tu carrito está vacío</p>
    </div>

    <div x-show="cart.length > 0" class="flex-1 overflow-y-auto px-5 py-3 space-y-3">
      <template x-for="(item, idx) in cart" :key="item.id">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden">
            <img x-show="item.img" :src="item.img" class="w-full h-full object-cover">
            <div x-show="!item.img" class="w-full h-full flex items-center justify-center">
              <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold text-gray-800 leading-snug line-clamp-2" x-text="item.name"></p>
            <p class="text-xs font-black text-p mt-0.5" x-text="'{{ $currency }} ' + (item.price * item.qty).toFixed(2)"></p>
          </div>
          <div class="flex items-center gap-1 flex-shrink-0">
            <button @click="updateQty(idx,-1)" class="w-6 h-6 rounded-md bg-gray-100 hover:bg-gray-200 text-sm font-bold text-gray-600 flex items-center justify-center transition">−</button>
            <span class="text-xs font-bold w-5 text-center" x-text="item.qty"></span>
            <button @click="updateQty(idx,1)" class="w-6 h-6 rounded-md bg-gray-100 hover:bg-gray-200 text-sm font-bold text-gray-600 flex items-center justify-center transition">+</button>
          </div>
        </div>
      </template>
    </div>

    <div x-show="cart.length > 0" class="px-5 py-4 border-t border-gray-100 space-y-3">
      <div class="flex items-center justify-between font-black text-gray-900 text-sm">
        <span>Total (<span x-text="cartCount"></span> productos)</span>
        <span x-text="'{{ $currency }} ' + cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></span>
      </div>
      <a href="{{ $catalogUrl }}"
         class="btn-p w-full py-3 rounded-xl font-black text-sm flex items-center justify-center gap-2 transition">
        Ir al catálogo para pagar
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
        </svg>
      </a>
      <button @click="cart=[];try{localStorage.removeItem(_cartKey);}catch(e){}"
              class="w-full py-2 text-xs text-gray-400 hover:text-red-500 transition font-medium">
        Vaciar carrito
      </button>
    </div>
  </div>
</div>

<script>
function productStore() {
  const _cartKey = 'avan_cart_{{ $project->id }}';
  let _savedCart = [];
  try {
    const c = localStorage.getItem(_cartKey);
    if (c) _savedCart = JSON.parse(c);
  } catch(e) {}

  return {
    _cartKey,
    activeImg: 0,
    added: false,
    copied: false,
    drawerOpen: false,
    cart: _savedCart,
    // Búsqueda predictiva
    search: '',
    searchCat: '',
    searchOpen: false,
    searchIdx: -1,
    searchIndex: @json($searchIndex),
    get suggestions() {
      if (!this.search || this.search.trim().length < 2) return [];
      const q = this.search.toLowerCase().trim();
      return this.searchIndex
        .filter(p => {
          const matchText = p.name.toLowerCase().includes(q) || (p.cat && p.cat.toLowerCase().includes(q));
          const matchCat  = !this.searchCat || String(p.catId ?? '') === String(this.searchCat);
          return matchText && matchCat;
        })
        .slice(0, 6);
    },
    selectSuggestion(p) { window.location.href = p.url; },
    goSearch() {
      const q = this.search.trim();
      if (!q) return;
      let url = '{{ $catalogUrl }}?q=' + encodeURIComponent(q);
      if (this.searchCat) url += '&cat=' + encodeURIComponent(this.searchCat);
      window.location.href = url;
    },
    _highlight(text) {
      if (!this.search || this.search.trim().length < 2) return text;
      const q = this.search.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      return text.replace(new RegExp('(' + q + ')', 'gi'), '<strong style="color:var(--c,#4f46e5)">$1</strong>');
    },

    get cartCount() {
      return this.cart.reduce((s, i) => s + i.qty, 0);
    },

    init() {
      this.$watch('cart', v => {
        try { localStorage.setItem(this._cartKey, JSON.stringify(v)); } catch(e) {}
      });
      // Guardar en vistos recientemente
      try {
          const _rvKey = 'rv_{{ $project->slug }}';
          const _rvItem = {
              id: {{ $product->id }},
              name: '{{ addslashes($product->name) }}',
              img: '{{ $mainImg ? asset("storage/".$mainImg->url) : "" }}',
              price: {{ $product->price }},
              cp: {{ $product->compare_price ?? 'null' }},
              url: '{{ $canonicalUrl }}'
          };
          let _rv = [];
          try { _rv = JSON.parse(localStorage.getItem(_rvKey) || '[]'); } catch(e) { _rv = []; }
          _rv = _rv.filter(x => x.id !== _rvItem.id);
          _rv.unshift(_rvItem);
          _rv = _rv.slice(0, 10);
          localStorage.setItem(_rvKey, JSON.stringify(_rv));
      } catch(e) {}
    },

    addToCart() {
      const product = {
        id:    {{ $product->id }},
        name:  '{{ addslashes($product->name) }}',
        price: {{ $product->price }},
        img:   '{{ $mainImg ? asset("storage/".$mainImg->url) : "" }}',
        qty:   1,
      };
      const idx = this.cart.findIndex(i => i.id === product.id);
      if (idx >= 0) {
        this.cart = this.cart.map((it, i) => i === idx ? { ...it, qty: it.qty + 1 } : it);
      } else {
        this.cart = [...this.cart, product];
      }
      this.added = true;
      setTimeout(() => this.added = false, 3000);
    },

    updateQty(idx, delta) {
      const newQty = (this.cart[idx]?.qty ?? 0) + delta;
      if (newQty <= 0) {
        this.cart = this.cart.filter((_, i) => i !== idx);
      } else {
        this.cart = this.cart.map((it, i) => i === idx ? { ...it, qty: newQty } : it);
      }
    },

    async share() {
      const url  = '{{ $canonicalUrl }}';
      const text = '{{ addslashes($product->name) }} — {{ addslashes($project->name) }}';
      if (navigator.share) {
        try { await navigator.share({ title: text, url }); return; } catch(e) {}
      }
      this.copyLink();
    },

    copyLink() {
      const url = '{{ $canonicalUrl }}';
      if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
          this.copied = true;
          setTimeout(() => this.copied = false, 2500);
        });
      } else {
        const el = document.createElement('textarea');
        el.value = url;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        this.copied = true;
        setTimeout(() => this.copied = false, 2500);
      }
    },
  };
}

function reviewStore() {
  return {
    form: { author_name: '', author_email: '', rating: 0, comment: '' },
    hoverRating: 0,
    loading: false,
    sent: false,
    error: '',

    async submit() {
      if (!this.form.rating) { this.error = 'Por favor selecciona una puntuación.'; return; }
      if (!this.form.author_name.trim()) { this.error = 'Tu nombre es obligatorio.'; return; }
      this.error   = '';
      this.loading = true;
      try {
        const res  = await fetch('{{ url("/{$project->slug}/p/{$product->id}/review") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
          },
          body: JSON.stringify(this.form),
        });
        const data = await res.json();
        if (data.ok) {
          this.sent = true;
        } else {
          this.error = data.message || 'Error al enviar la reseña.';
        }
      } catch(e) {
        this.error = 'Error de conexión. Inténtalo de nuevo.';
      } finally {
        this.loading = false;
      }
    },
  };
}
</script>
</body>
</html>
