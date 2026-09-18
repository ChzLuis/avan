{{--
  Tarjeta de producto compartida por los motores de tienda.

  Recibe SIEMPRE el arreglo que produce CatalogQueryService::toCard(): asi
  Directo, Ecommerce y la vista rapida hablan de los mismos campos (id, name,
  price, cp, img, stock, url, realVariants, wholesale*). Antes Directo llevaba
  esta tarjeta copiada dos veces (categoria y subcategoria) con 94 lineas cada
  una, y un arreglo en una no llegaba a la otra.

  La apariencia la dan las clases que pasa cada motor; las reglas NO se
  duplican: la disponibilidad y el precio de una variante los decide el motor
  compartido de JavaScript (public/partials/variant-engine) y el servidor
  revalida todo en el checkout.

  Parametros:
    $item        arreglo de toCard()
    $catId       id de la categoria a la que pertenece en esta grilla
    $parentId    id de la categoria padre o null (para el filtro del cliente)
    $idx         posicion en la grilla (para el orden "recomendado")
    $currency, $isQuoteOnly, $quotePriceDisp, $quoteWa, $wholesaleEnabled
                 vienen del contexto del motor
    $cardClass   clase raiz de la tarjeta (por defecto la de Directo)
--}}
@php
    $cardClass      = $cardClass ?? 'd-card group';
    $tieneVariantes = ! empty($item['realVariants']);
    $enOferta       = ! empty($item['cp']) && $item['cp'] > $item['price'];
    $hasWholesale   = ($wholesaleEnabled ?? false) && ! empty($item['wholesalePrice']) && ($item['wholesaleMinQty'] ?? 0) > 0;
    $muestraPrecio  = ! ($isQuoteOnly ?? false) || ($quotePriceDisp ?? 'show') === 'show';
    // La vista rapida usa el mismo dato de la tarjeta; solo se renombra el
    // resumen para no romper el modal existente.
    $qvData = [
        'id' => $item['id'], 'name' => $item['name'], 'img' => $item['img'] ?? '',
        'price' => (float) $item['price'], 'cp' => $item['cp'],
        'desc' => $item['resumen'] ?? '', 'url' => $item['url'],
        'stock' => $item['stock'], 'hasVariants' => $tieneVariantes,
    ];
    // La linea que entra al carrito: el motor compartido decide la clave.
    $lineaBase = [
        'id' => $item['id'], 'name' => $item['name'], 'price' => (float) $item['price'],
        'img' => $item['img'] ?? '', 'hasVariants' => $tieneVariantes, 'url' => $item['url'],
    ];
@endphp
<article
  x-show="matchProduct(@js(mb_strtolower($item['name'])), {{ (float) $item['price'] }}, {{ $item['cp'] !== null ? (float) $item['cp'] : 'null' }}, @js((string) $catId), @js($parentId !== null ? (string) $parentId : null))"
  class="{{ $cardClass }}"
  data-price="{{ $item['price'] }}"
  data-name="{{ mb_strtolower($item['name']) }}"
  data-ts="{{ $item['ts'] ?? 0 }}"
  data-idx="{{ $idx ?? 0 }}"
  data-qv='@json($qvData)'>
  <a href="{{ $item['url'] }}" class="block card-img relative">
    @if(! empty($item['img']))
    <img src="{{ $item['img'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover" loading="lazy">
    @else
    <div class="w-full h-full flex items-center justify-center text-gray-300">
      <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
    </div>
    @endif
    @if($enOferta)
    <span class="absolute top-2 left-2 bg-red-500 text-white text-[9px] font-bold uppercase px-2 py-0.5 rounded-full">Oferta</span>
    @endif
    @if($item['stock'] === 0)
    <span class="absolute top-2 right-2 bg-black/60 text-white text-[9px] font-bold uppercase px-2 py-0.5 rounded-full">Agotado</span>
    @endif
    <button @click.prevent="const d=$el.closest('[data-qv]');if(d){qv=JSON.parse(d.dataset.qv);qvOpen=true}"
            class="absolute inset-0 flex items-end justify-center pb-3 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none group-hover:pointer-events-auto z-10">
      <span class="bg-black/70 text-white text-xs font-bold px-3 py-1.5 rounded-full backdrop-blur-sm">Vista rápida</span>
    </button>
  </a>
  <div class="p-3">
    <a href="{{ $item['url'] }}" class="text-gray-800 text-xs font-semibold line-clamp-2 hover:underline block mb-1.5">{{ $item['name'] }}</a>

    @if($tieneVariantes && $muestraPrecio)
    {{-- Con variantes el precio puede variar: se dice y se elige en la ficha --}}
    <div class="flex items-baseline gap-1.5 mb-2">
      <span class="text-[10px] text-gray-400">Desde</span>
      <span class="font-bold text-sm" style="color:var(--c)">{{ $currency }} {{ number_format(collect($item['realVariants'])->min('price') ?? $item['price'], 2) }}</span>
    </div>
    <a href="{{ $item['url'] }}" class="block w-full py-2 text-[11px] font-semibold btn-gc text-center">Elegir opciones</a>

    @elseif($hasWholesale)
    {{-- Bloque minorista --}}
    <div x-data="{ qty:1 }" class="rounded-lg border border-gray-200 bg-white overflow-hidden mb-1.5">
      <div class="flex items-center justify-between px-2 py-1 bg-gray-50 border-b border-gray-100">
        <span class="text-[10px] font-bold text-gray-500 uppercase">Minorista</span>
        <span class="font-bold text-sm" style="color:var(--c)">{{ $currency }} {{ number_format($item['price'], 2) }}</span>
      </div>
      <div class="flex items-center gap-1 p-1.5">
        <div class="flex items-center border border-gray-200 rounded overflow-hidden">
          <button @click="qty>1?qty--:null" class="px-2 py-1 text-xs bg-gray-50 hover:bg-gray-100">−</button>
          <span class="px-2 text-xs font-semibold" x-text="qty"></span>
          <button @click="qty++" class="px-2 py-1 text-xs bg-gray-50 hover:bg-gray-100">+</button>
        </div>
        <button class="flex-1 py-1.5 text-[11px] font-semibold btn-gc"
                @click="addToCart({...@js($lineaBase), qty:qty})">+ Agregar</button>
      </div>
    </div>
    {{-- Bloque mayorista --}}
    <div x-data="{ qty:{{ (int) $item['wholesaleMinQty'] }} }" class="rounded-lg border border-amber-400 overflow-hidden">
      <div class="px-2 pt-1.5 pb-1 bg-amber-50 border-b border-amber-100">
        <div class="flex items-center justify-between">
          <span class="text-[10px] font-bold text-amber-700 uppercase">Mayorista</span>
          <span class="font-bold text-sm text-amber-700">{{ $currency }} {{ number_format($item['wholesalePrice'], 2) }}</span>
        </div>
        <p class="text-[9px] text-amber-500 mt-0.5">Mín. {{ (int) $item['wholesaleMinQty'] }} unid.{{ ($item['wholesaleUnit'] ?? '') !== 'unidades' ? ' · '.$item['wholesaleUnit'] : '' }}</p>
      </div>
      <div class="flex items-center gap-1 p-1.5">
        <div class="flex items-center border border-amber-300 rounded overflow-hidden">
          <button @click="qty>({{ (int) $item['wholesaleMinQty'] }})?qty--:null" class="px-2 py-1 text-xs bg-amber-50 hover:bg-amber-100">−</button>
          <span class="px-2 text-xs font-semibold" x-text="qty"></span>
          <button @click="qty++" class="px-2 py-1 text-xs bg-amber-50 hover:bg-amber-100">+</button>
        </div>
        <button class="flex-1 py-1.5 text-[11px] font-semibold bg-amber-500 hover:bg-amber-600 text-white rounded"
                @click="qty=Math.max({{ (int) $item['wholesaleMinQty'] }},qty); addToCart({...@js($lineaBase), name:@js($item['name'].' (mayor)'), price:{{ (float) $item['wholesalePrice'] }}, qty:qty, min_qty:{{ (int) $item['wholesaleMinQty'] }}})">+ Agregar</button>
      </div>
    </div>

    @elseif($muestraPrecio)
    <div class="flex items-baseline gap-1.5 mb-2">
      <span class="font-bold text-sm" style="color:var(--c)">{{ $currency }} {{ number_format($item['price'], 2) }}</span>
      @if($enOferta)
      <span class="text-gray-300 text-xs line-through">{{ $currency }} {{ number_format($item['cp'], 2) }}</span>
      @endif
    </div>
    @if(! ($isQuoteOnly ?? false))
    <button class="w-full py-2 text-[11px] font-semibold btn-gc" @click="addToCart(@js($lineaBase))">+ Agregar</button>
    @else
    <a class="block w-full py-2 text-[11px] font-semibold btn-outline-gc text-center"
       href="https://wa.me/{{ $quoteWa }}?text={{ urlencode('Hola, me interesa: '.$item['name']) }}" target="_blank" rel="noopener">Cotizar</a>
    @endif

    @else
    <p class="text-gray-400 text-xs mb-2 italic">Consultar precio</p>
    @endif
  </div>
</article>
