{{-- Product Card Partial — con SEO microdata Schema.org --}}
<article class="prod-card bg-white rounded-2xl border border-gray-200 overflow-hidden"
         data-price="{{ $product->price }}"
         data-ts="{{ $product->created_at ? $product->created_at->timestamp : 0 }}"
         data-idx="{{ isset($loop) ? $loop->index : 0 }}"
         itemscope itemtype="https://schema.org/Product">

  <meta itemprop="name"        content="{{ $product->name }}">
  <meta itemprop="sku"         content="{{ $product->sku ?? $product->id }}">
  @if($product->description)
  <meta itemprop="description" content="{{ Str::limit($product->description, 160) }}">
  @endif

  {{-- Imagen con SEO: alt descriptivo, lazy load, dimensiones explícitas --}}
  <a href="{{ route('public.product', [$project->slug, $product->id]) }}" class="block prod-img aspect-square bg-gray-50 relative overflow-hidden">
    @if($product->mainImage)
    <img src="{{ $product->mainImage->url }}"
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

    {{-- Badge stock --}}
    @if($product->stock !== null && $product->stock === 0)
    <span class="absolute bottom-2 left-2 bg-red-600 text-white text-[10px] font-black px-1.5 py-0.5 rounded-lg leading-none">Agotado</span>
    @elseif($product->stock !== null && $product->stock > 0 && $product->stock <= 5)
    <span class="absolute bottom-2 left-2 bg-amber-500 text-white text-[10px] font-black px-1.5 py-0.5 rounded-lg leading-none">Últimas {{ $product->stock }} unid.</span>
    @endif
  </a>

  {{-- Info con Schema.org Offer --}}
  <div class="p-3" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
    <meta itemprop="priceCurrency" content="PEN">
    <meta itemprop="price"         content="{{ $product->price }}">
    <meta itemprop="availability"  content="https://schema.org/InStock">

    @if(isset($product->category) && $product->category)
    <p class="text-[11px] text-gray-400 mb-0.5 font-medium">{{ $product->category->name }}</p>
    @endif

    <a href="{{ route('public.product', [$project->slug, $product->id]) }}" class="text-sm font-bold text-gray-900 leading-snug line-clamp-2 mb-2 hover:underline block" itemprop="name">{{ $product->name }}</a>

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
              img:'{{ $product->mainImage ? $product->mainImage->url : "" }}'
            })"
            {{ $product->stock !== null && $product->stock === 0 ? 'disabled' : '' }}
            class="w-full btn-p py-2 rounded-xl text-sm font-bold transition flex items-center justify-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed">
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
    @if(isset($quoteWa) && $quoteWa && !($isQuoteOnly ?? false))
    <a href="https://wa.me/{{ $quoteWa }}?text={{ urlencode('Hola, me interesa este producto: ' . $product->name) }}"
       target="_blank" rel="noopener"
       class="mt-1.5 w-full py-1.5 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 text-white transition hover:opacity-90"
       style="background:#25D366;">
      <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
      </svg>
      Consultar por WA
    </a>
    @endif
  </div>
</article>
