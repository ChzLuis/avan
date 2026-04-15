@php
$currency    = $settings['currency'] ?? $settings['currency_symbol'] ?? 'S/';
$primaryColor= $settings['primary_color'] ?? '#4f46e5';
$projectName = $project->name;
$waRaw       = preg_replace('/\D/', '', $settings['quote_whatsapp'] ?? $project->whatsapp ?? '');
$wholesale   = ($settings['wholesale_enabled'] ?? '0') === '1';
$logoUrl     = $project->logo_url ? asset('storage/'.$project->logo_url) : null;

$allProducts = $categories->flatMap(fn($cat) => $cat->products->map(fn($p) => [
    'id'              => $p->id,
    'name'            => $p->name,
    'cat'             => $cat->name,
    'catId'           => (string)$cat->id,
    'price'           => (float)$p->price,
    'cp'              => $p->compare_price ? (float)$p->compare_price : null,
    'wp'              => $p->wholesale_price ? (float)$p->wholesale_price : null,
    'wq'              => $p->wholesale_min_qty ?? null,
    'wu'              => $p->wholesale_unit ?? null,
    'unit'            => $p->unit ?? null,
    'stock'           => $p->stock,
    'desc'            => \Str::limit(strip_tags($p->description ?? ''), 120),
    'img'             => $p->mainImage ? asset('storage/'.$p->mainImage->url) : null,
]))->values();

$cats = $categories->map(fn($c) => ['id'=>(string)$c->id, 'name'=>$c->name])->values();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $projectName }}</title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
  :root { --c: {{ $primaryColor }}; }
  [x-cloak] { display: none !important; }
  * { box-sizing: border-box; }
  body { font-family: system-ui, sans-serif; background: #f8fafc; }
  .btn-p { background: var(--c); color: #fff; }
  .btn-p:hover { filter: brightness(1.1); }
  .price-p { color: var(--c); }
  .ring-p { outline: 2px solid var(--c); outline-offset: 2px; }
  input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; }
  .line-clamp-2 { display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
  .animate-spin { animation: spin 1s linear infinite; }
  @keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body x-data="catalog()" x-cloak>

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

{{-- HEADER --}}
<header class="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-sm">
  <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      @if($logoUrl)
      <img src="{{ $logoUrl }}" alt="{{ $projectName }}" class="h-9 object-contain">
      @else
      <span class="font-black text-xl text-gray-900">{{ $projectName }}</span>
      @endif
    </div>
    {{-- Búsqueda --}}
    <div class="flex-1 max-w-md relative">
      <input type="text" x-model="search" placeholder="Buscar producto..."
             class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-indigo-400 bg-gray-50">
      <svg x-show="!search" class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
      </svg>
      <button x-show="search" @click="search=''" class="absolute right-3 top-2 text-gray-400 hover:text-gray-600 text-lg font-bold">×</button>
    </div>
    {{-- Carrito --}}
    <button @click="cartOpen=true" class="relative flex items-center gap-2 btn-p px-4 py-2 rounded-xl text-sm font-bold">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
      </svg>
      <span>Carrito</span>
      <span x-show="cartCount>0" x-text="cartCount"
            class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center"></span>
    </button>
  </div>
</header>

{{-- CATEGORÍAS --}}
<div class="bg-white border-b border-gray-100 sticky top-[57px] z-30 overflow-x-auto">
  <div class="max-w-7xl mx-auto px-4 flex gap-2 py-2">
    <button @click="filterCat=''"
            :class="filterCat==='' ? 'btn-p' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
            class="flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-bold transition">
      Todos
    </button>
    @foreach($categories as $cat)
    <button @click="filterCat='{{ $cat->id }}'"
            :class="filterCat==='{{ $cat->id }}' ? 'btn-p' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
            class="flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap">
      {{ $cat->name }}
    </button>
    @endforeach
  </div>
</div>

{{-- BODY --}}
<div class="max-w-7xl mx-auto px-4 py-6 pb-24 flex gap-6">

  {{-- SIDEBAR FILTROS --}}
  <aside class="hidden lg:block w-52 flex-shrink-0">
    <div class="bg-white rounded-2xl border border-gray-200 p-4 sticky top-28 space-y-5">
      {{-- Precio --}}
      <div>
        <p class="text-xs font-bold text-gray-700 mb-2 uppercase tracking-wider">Precio</p>
        <div class="space-y-1">
          @foreach([
            ['' ,'Todos'],
            ['0-20','Hasta S/ 20'],
            ['20-50','S/ 20 – 50'],
            ['50-200','S/ 50 – 200'],
            ['200+','Más de S/ 200'],
          ] as [$v,$l])
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" x-model="priceFilter" value="{{ $v }}" class="accent-[var(--c)]">
            <span class="text-sm text-gray-600">{{ $l }}</span>
          </label>
          @endforeach
        </div>
      </div>
      {{-- Oferta --}}
      <div>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" x-model="onSale" class="accent-[var(--c)] w-4 h-4 rounded">
          <span class="text-sm font-medium text-gray-700">Solo ofertas</span>
        </label>
      </div>
      {{-- Limpiar --}}
      <button x-show="filterCat||priceFilter||onSale||search"
              @click="filterCat='';priceFilter='';onSale=false;search=''"
              class="w-full text-xs text-indigo-600 font-semibold hover:underline text-left">
        Limpiar filtros
      </button>
    </div>
  </aside>

  {{-- GRID --}}
  <div class="flex-1 min-w-0">
    <p class="text-sm text-gray-400 mb-4" x-text="filtered.length + ' producto' + (filtered.length!==1?'s':'')"></p>

    <div x-show="filtered.length===0" class="text-center py-16 text-gray-400">
      <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <p class="font-medium">No hay productos con esos filtros</p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
      <template x-for="p in filtered" :key="p.id">
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden flex flex-col">
          {{-- Imagen --}}
          <div class="aspect-square bg-gray-50 relative overflow-hidden">
            <template x-if="p.img">
              <img :src="p.img" :alt="p.name" class="w-full h-full object-cover">
            </template>
            <template x-if="!p.img">
              <div class="w-full h-full flex items-center justify-center text-gray-300">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
              </div>
            </template>
            {{-- Descuento: esquina superior izquierda --}}
            <span x-show="p.cp && p.cp > p.price"
                  class="absolute top-0 left-0 bg-red-500 text-white text-[10px] font-black px-2 py-1 rounded-br-xl leading-none"
                  x-text="'-'+Math.round(((p.cp-p.price)/p.cp)*100)+'%'"></span>
            {{-- AGOTADO: barra inferior completa --}}
            <div x-show="p.stock !== null && p.stock === 0"
                 class="absolute bottom-0 left-0 right-0 bg-red-600/90 text-white text-[10px] font-black text-center py-1.5 tracking-widest uppercase">
              Agotado
            </div>
            {{-- CASI AGOTADO: barra inferior completa --}}
            <div x-show="p.stock !== null && p.stock > 0 && p.stock <= 10"
                 class="absolute bottom-0 left-0 right-0 bg-orange-500/90 text-white text-[10px] font-bold text-center py-1 leading-tight"
                 x-text="'⚡ CASI AGOTADO — '+p.stock+' restantes'"></div>
          </div>
          {{-- Info --}}
          <div class="p-3 flex flex-col flex-1">
            <p class="text-[10px] text-gray-400 font-medium mb-0.5" x-text="p.cat"></p>
            <p class="text-sm font-bold text-gray-900 line-clamp-2 mb-1 leading-snug" x-text="p.name"></p>
            <p x-show="p.desc" class="text-xs text-gray-500 line-clamp-2 mb-2 leading-relaxed" x-text="p.desc"></p>

            <template x-if="!(p.stock !== null && p.stock === 0)">
              <div class="mt-auto space-y-1.5">

                {{-- CON MAYORISTA --}}
                <template x-if="p.wp && p.wq">
                  <div class="space-y-1.5" x-data="{ qr:1, qw: p.wq }">
                    {{-- Minorista --}}
                    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                      <div class="px-2.5 pt-1.5 pb-1 bg-gray-50 border-b border-gray-100">
                        <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Minorista</span>
                        <div class="flex items-baseline gap-1">
                          <span class="price-p font-black text-base leading-none" x-text="'{{ $currency }} '+p.price.toFixed(2)"></span>
                          <span x-show="p.unit" class="text-[10px] text-gray-400 font-medium" x-text="p.unit"></span>
                        </div>
                      </div>
                      <div class="flex items-center gap-1.5 px-2 py-1.5">
                        <div class="flex items-center rounded-lg border border-gray-200 overflow-hidden shrink-0">
                          <button @click="qr=Math.max(1,qr-1)" class="w-6 h-7 flex items-center justify-center text-gray-500 hover:bg-gray-100 text-base font-bold">−</button>
                          <input type="number" x-model.number="qr" min="1" class="w-8 h-7 text-center text-xs font-bold border-x border-gray-200 focus:outline-none bg-white">
                          <button @click="qr++" class="w-6 h-7 flex items-center justify-center text-gray-500 hover:bg-gray-100 text-base font-bold">+</button>
                        </div>
                        <button @click="addToCart({id:p.id,name:p.name+' (minorista)',price:p.price,qty:qr,img:p.img,unit:p.unit})"
                                class="flex-1 btn-p h-7 rounded-lg text-[11px] font-bold flex items-center justify-center gap-1 whitespace-nowrap">
                          <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                          Agregar
                        </button>
                      </div>
                    </div>
                    {{-- Mayorista --}}
                    <div class="rounded-xl border border-amber-400 overflow-hidden">
                      <div class="px-2.5 pt-1.5 pb-1 bg-amber-500">
                        <span class="block text-[9px] font-black text-amber-200 uppercase tracking-widest mb-0.5">Mayorista</span>
                        <div class="flex items-baseline gap-1">
                          <span class="font-black text-base text-white leading-none" x-text="'{{ $currency }} '+p.wp.toFixed(2)"></span>
                          <span x-show="p.wu" class="text-[10px] text-amber-200 font-medium" x-text="p.wu"></span>
                        </div>
                        <p class="text-[9px] text-amber-200 mt-0.5" x-text="'Mín. '+p.wq+(p.wu ? ' '+p.wu : '')"></p>
                      </div>
                      <div class="flex items-center gap-1.5 px-2 py-1.5 bg-amber-50">
                        <div class="flex items-center rounded-lg border border-amber-300 overflow-hidden bg-white shrink-0">
                          <button @click="qw=Math.max(p.wq,qw-1)" class="w-6 h-7 flex items-center justify-center text-amber-600 hover:bg-amber-100 text-base font-bold">−</button>
                          <input type="number" x-model.number="qw" :min="p.wq" class="w-8 h-7 text-center text-xs font-bold border-x border-amber-200 focus:outline-none bg-white text-amber-700">
                          <button @click="qw++" class="w-6 h-7 flex items-center justify-center text-amber-600 hover:bg-amber-100 text-base font-bold">+</button>
                        </div>
                        <button @click="addToCart({id:p.id,name:p.name+' (mayorista)',price:p.wp,qty:qw,img:p.img,unit:p.wu})"
                                class="flex-1 h-7 rounded-lg text-[11px] font-bold flex items-center justify-center gap-1 bg-amber-500 hover:bg-amber-600 text-white whitespace-nowrap">
                          <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                          Agregar
                        </button>
                      </div>
                    </div>
                  </div>
                </template>

                {{-- SIN MAYORISTA --}}
                <template x-if="!(p.wp && p.wq)">
                  <div x-data="{ qty:1 }">
                    <div class="flex items-baseline gap-2 mb-0.5">
                      <span class="price-p font-black text-lg" x-text="'{{ $currency }} '+p.price.toFixed(2)"></span>
                      <span x-show="p.cp && p.cp>p.price" class="text-xs text-gray-400 line-through" x-text="'{{ $currency }} '+p.cp?.toFixed(2)"></span>
                    </div>
                    <p x-show="p.cp && p.cp > p.price"
                       class="text-[11px] text-green-600 font-bold mb-1.5 leading-none"
                       x-text="'Ahorras {{ $currency }} '+(p.cp - p.price).toFixed(2)"></p>
                    <template x-if="p.unit">
                      <div class="flex items-center gap-2 mb-1">
                        <div class="flex items-center rounded-lg border border-gray-200 overflow-hidden">
                          <button @click="qty=Math.max(1,qty-1)" class="w-7 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 font-bold text-lg">−</button>
                          <input type="number" x-model.number="qty" min="1" class="w-9 h-8 text-center text-sm font-bold border-x border-gray-200 focus:outline-none">
                          <button @click="qty++" class="w-7 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 font-bold text-lg">+</button>
                        </div>
                        <span class="text-xs text-gray-400" x-text="p.unit"></span>
                        <button @click="addToCart({id:p.id,name:p.name,price:p.price,qty:qty,img:p.img,unit:p.unit})"
                                class="flex-1 btn-p h-8 rounded-xl text-xs font-bold flex items-center justify-center gap-1">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                          Agregar
                        </button>
                      </div>
                    </template>
                    <template x-if="!p.unit">
                      <button @click="addToCart({id:p.id,name:p.name,price:p.price,qty:1,img:p.img})"
                              class="w-full btn-p py-2 rounded-xl text-sm font-bold flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Agregar
                      </button>
                    </template>
                  </div>
                </template>

              </div>
            </template>
          </div>
        </div>
      </template>
    </div>
  </div>
</div>

{{-- CARRITO PANEL --}}
<div x-show="cartOpen" x-cloak class="fixed inset-0 z-50 flex justify-end">
  <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="cartOpen=false"></div>
  <div class="relative w-full max-w-sm bg-white h-full flex flex-col shadow-2xl">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
      <h2 class="font-black text-lg text-gray-900">Tu pedido</h2>
      <button @click="cartOpen=false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold leading-none">×</button>
    </div>

    <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
      <template x-if="cart.length===0">
        <div class="text-center py-12 text-gray-400">
          <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
          <p class="font-medium text-sm">Tu carrito está vacío</p>
        </div>
      </template>
      <template x-for="(item, i) in cart" :key="i">
        <div class="flex items-start gap-3 bg-gray-50 rounded-xl p-3">
          <img x-show="item.img" :src="item.img" class="w-12 h-12 object-cover rounded-lg flex-shrink-0">
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 leading-tight" x-text="item.name"></p>
            <p x-show="item.unit" class="text-[10px] text-gray-400 mt-0.5" x-text="item.qty+' '+item.unit+' × {{ $currency }} '+item.price.toFixed(2)"></p>
            <p x-show="!item.unit" class="text-[10px] text-gray-400 mt-0.5" x-text="item.qty+' und × {{ $currency }} '+item.price.toFixed(2)"></p>
            <p class="text-sm font-black price-p mt-0.5" x-text="'{{ $currency }} '+(item.price*item.qty).toFixed(2)"></p>
            <div class="flex items-center gap-1.5 mt-1.5">
              <div class="flex items-center rounded-lg border border-gray-200 overflow-hidden bg-white">
                <button @click="changeQty(i,-1)" class="w-6 h-6 flex items-center justify-center text-gray-500 hover:bg-gray-100 font-bold">−</button>
                <span class="w-7 text-center text-xs font-bold" x-text="item.qty"></span>
                <button @click="changeQty(i,1)" class="w-6 h-6 flex items-center justify-center text-gray-500 hover:bg-gray-100 font-bold">+</button>
              </div>
              <button @click="removeItem(i)" class="text-red-400 hover:text-red-600 text-xs">Quitar</button>
            </div>
          </div>
        </div>
      </template>
    </div>

    <div x-show="cart.length>0" class="px-5 py-4 border-t border-gray-200 space-y-3 bg-white">
      <div class="flex items-center justify-between">
        <span class="font-bold text-gray-700">Total</span>
        <span class="font-black text-xl price-p" x-text="'{{ $currency }} '+cartTotal.toFixed(2)"></span>
      </div>
      <div>
        <label class="text-xs text-gray-500 font-medium">Tu nombre (opcional)</label>
        <input type="text" x-model="clientName" placeholder="Ej: Juan Pérez"
               class="w-full mt-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
      </div>
      <div>
        <label class="text-xs text-gray-500 font-medium">Notas del pedido (opcional)</label>
        <textarea x-model="clientNotes" rows="2" placeholder="Dirección, hora de entrega..."
                  class="w-full mt-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-indigo-400 resize-none"></textarea>
      </div>
      @if($waRaw)
      <a :href="waLink()" target="_blank"
         class="w-full py-3 rounded-xl font-black text-white flex items-center justify-center gap-2 text-sm transition hover:opacity-90"
         style="background:#25D366;">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
        </svg>
        Enviar pedido por WhatsApp
      </a>
      @else
      <div class="w-full py-3 rounded-xl bg-gray-100 text-gray-400 text-sm text-center font-medium">
        Configura tu WhatsApp en el admin para recibir pedidos
      </div>
      @endif
      <button @click="cart=[];saveCart()" class="w-full text-xs text-gray-400 hover:text-red-500 transition">
        Vaciar carrito
      </button>
    </div>
  </div>
</div>

{{-- FLOATING BOTTOM BAR --}}
<div x-show="cart.length > 0" x-cloak
     class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 shadow-[0_-4px_20px_rgba(0,0,0,0.1)] px-4 py-3 flex items-center gap-4">
  <div class="flex items-center gap-2.5 flex-1 min-w-0">
    <span class="btn-p text-xs font-black w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0"
          x-text="cart.reduce((s,i)=>s+i.qty,0)"></span>
    <div class="min-w-0">
      <p class="text-[10px] text-gray-400 leading-none mb-0.5">Total del pedido</p>
      <p class="font-black text-base leading-none price-p" x-text="'{{ $currency }} '+cart.reduce((s,i)=>s+i.price*i.qty,0).toFixed(2)"></p>
    </div>
  </div>
  <button @click="cartOpen=true"
          class="btn-p px-5 py-3 rounded-xl font-black text-sm flex items-center gap-2 flex-shrink-0">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-9H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
    </svg>
    Ver pedido
  </button>
</div>

<script>
const PRODUCTS = {!! json_encode($allProducts) !!};
const WA = '{{ $waRaw }}';
const CURRENCY = '{{ $currency }}';
const PROJECT = '{{ $projectName }}';
const WHOLESALE = {{ $wholesale ? 'true' : 'false' }};

function catalog() {
  return {
    products: PRODUCTS,
    search: '',
    filterCat: '',
    priceFilter: '',
    onSale: false,
    cartOpen: false,
    cart: JSON.parse(localStorage.getItem('cart_static') || '[]'),
    clientName: '',
    clientNotes: '',
    toastShow: false,
    toastMsg: '',
    toastTimer: null,

    get filtered() {
      let list = this.products;
      if (this.filterCat) list = list.filter(p => p.catId === this.filterCat);
      if (this.onSale)    list = list.filter(p => p.cp && p.cp > p.price);
      if (this.search) {
        const q = this.search.toLowerCase();
        list = list.filter(p => p.name.toLowerCase().includes(q) || p.cat.toLowerCase().includes(q) || (p.desc||'').toLowerCase().includes(q));
      }
      if (this.priceFilter) {
        const [mn, mx] = this.priceFilter === '200+' ? [200, 99999] : this.priceFilter.split('-').map(Number);
        list = list.filter(p => p.price >= mn && p.price <= mx);
      }
      return list;
    },

    get cartCount() { return this.cart.reduce((s, i) => s + i.qty, 0); },
    get cartTotal() { return this.cart.reduce((s, i) => s + i.price * i.qty, 0); },

    addToCart(item) {
      const idx = this.cart.findIndex(c => c.id === item.id && c.name === item.name);
      if (idx > -1) this.cart[idx].qty += item.qty || 1;
      else this.cart.push({ ...item, qty: item.qty || 1 });
      this.saveCart();
      this.toastMsg = '✓ ' + item.name + ' agregado';
      this.toastShow = true;
      clearTimeout(this.toastTimer);
      this.toastTimer = setTimeout(() => { this.toastShow = false; }, 2000);
    },

    changeQty(i, d) {
      this.cart[i].qty = Math.max(1, this.cart[i].qty + d);
      this.saveCart();
    },

    removeItem(i) {
      this.cart.splice(i, 1);
      this.saveCart();
    },

    saveCart() { localStorage.setItem('cart_static', JSON.stringify(this.cart)); },

    waLink() {
      const now = new Date();
      const fecha = now.toLocaleDateString('es-PE', { day:'2-digit', month:'2-digit', year:'numeric' });
      const hora  = now.toLocaleTimeString('es-PE', { hour:'2-digit', minute:'2-digit' });
      const nPedido = 'PED-' + Date.now().toString().slice(-6);

      let msg = '';
      msg += `🛒 *PEDIDO - ${PROJECT}*\n`;
      msg += `━━━━━━━━━━━━━━━━━━━━\n`;
      msg += `📋 *N° Pedido:* ${nPedido}\n`;
      msg += `📅 *Fecha:* ${fecha} ${hora}\n`;
      if (this.clientName) msg += `👤 *Cliente:* ${this.clientName}\n`;
      msg += `━━━━━━━━━━━━━━━━━━━━\n`;
      msg += `*DETALLE DEL PEDIDO:*\n\n`;

      let subtotal = 0;
      this.cart.forEach((i, idx) => {
        const linea = i.price * i.qty;
        subtotal += linea;
        msg += `${idx+1}. *${i.name}*\n`;
        msg += `   • Cantidad: ${i.qty}${i.unit ? ' '+i.unit : ''}\n`;
        msg += `   • Precio unit.: ${CURRENCY} ${i.price.toFixed(2)}\n`;
        msg += `   • Subtotal: ${CURRENCY} ${linea.toFixed(2)}\n\n`;
      });

      msg += `━━━━━━━━━━━━━━━━━━━━\n`;
      msg += `💰 *TOTAL: ${CURRENCY} ${this.cartTotal.toFixed(2)}*\n`;
      msg += `━━━━━━━━━━━━━━━━━━━━\n`;

      if (this.clientNotes) msg += `\n📝 *Notas:* ${this.clientNotes}\n`;
      msg += `\n_Pedido realizado desde el catálogo online_`;

      return `https://wa.me/${WA}?text=${encodeURIComponent(msg)}`;
    }
  }
}
</script>
</body>
</html>
