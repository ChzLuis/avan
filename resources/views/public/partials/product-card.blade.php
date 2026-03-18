{{-- Product Card Partial — con SEO microdata Schema.org --}}
<article class="prod-card bg-white rounded-2xl border border-gray-200 overflow-hidden"
         itemscope itemtype="https://schema.org/Product">

  <meta itemprop="name"        content="{{ $product->name }}">
  <meta itemprop="sku"         content="{{ $product->sku ?? $product->id }}">
  @if($product->description)
  <meta itemprop="description" content="{{ Str::limit($product->description, 160) }}">
  @endif

  {{-- Imagen con SEO: alt descriptivo, lazy load, dimensiones explícitas --}}
  <div class="prod-img aspect-square bg-gray-50 relative overflow-hidden">
    @if($product->mainImage)
    <img src="{{ asset('storage/'.$product->mainImage->url) }}"
         alt="{{ $product->name }}{{ isset($product->category) && $product->category ? ' — '.$product->category->name : '' }}{{ isset($projectName) ? ' en '.$projectName : '' }}"
         title="{{ $product->name }}"
         loading="lazy"
         decoding="async"
         width="400" height="400"
         class="w-full h-full object-cover"
         itemprop="image">
    @else
    <div class="w-full h-full flex flex-col items-center justify-center text-gray-300 gap-1">
      <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
      <span class="text-xs">Sin imagen</span>
    </div>
    @endif

    {{-- Badge descuento --}}
    @if($product->compare_price && $product->compare_price > $product->price)
    <span class="absolute top-2 left-2 bg-red-500 text-white text-[10px] font-black px-1.5 py-0.5 rounded-lg leading-none">
      -{{ round((($product->compare_price - $product->price) / $product->compare_price) * 100) }}%
    </span>
    @endif

    {{-- Badge nuevo (últimos 30 días) --}}
    @if($product->created_at && $product->created_at->diffInDays() <= 30)
    <span class="absolute top-2 right-2 badge-p text-[10px] font-black px-1.5 py-0.5 rounded-lg leading-none">
      NUEVO
    </span>
    @endif
  </div>

  {{-- Info con Schema.org Offer --}}
  <div class="p-3" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
    <meta itemprop="priceCurrency" content="PEN">
    <meta itemprop="price"         content="{{ $product->price }}">
    <meta itemprop="availability"  content="https://schema.org/InStock">

    @if(isset($product->category) && $product->category)
    <p class="text-[11px] text-gray-400 mb-0.5 font-medium">{{ $product->category->name }}</p>
    @endif

    <p class="text-sm font-bold text-gray-900 leading-snug line-clamp-2 mb-2" itemprop="name">{{ $product->name }}</p>

    @if(!($isQuoteOnly ?? false) || ($quotePriceDisp ?? 'show') === 'show')
    <div class="flex items-baseline gap-2 mb-3">
      <span class="price-p font-black text-lg">S/ {{ number_format($product->price,2) }}</span>
      @if($product->compare_price && $product->compare_price > $product->price)
      <span class="text-xs text-gray-400 line-through font-medium">S/ {{ number_format($product->compare_price,2) }}</span>
      @endif
      @if($isQuoteOnly ?? false)
      <span class="text-[10px] text-gray-400">(referencial)</span>
      @endif
    </div>
    @else
    <div class="mb-3"><span class="text-xs text-gray-400 italic">Precio a consultar</span></div>
    @endif

    <button @click="addToCart({
              id:{{ $product->id }},
              name:'{{ addslashes($product->name) }}',
              price:{{ $product->price }},
              img:'{{ $product->mainImage ? asset("storage/".$product->mainImage->url) : "" }}'
            })"
            class="w-full btn-p py-2 rounded-xl text-sm font-bold transition flex items-center justify-center gap-1.5">
      @if($isQuoteOnly ?? false)
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
      Cotizar
      @else
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
      </svg>
      Agregar
      @endif
    </button>
  </div>
</article>
