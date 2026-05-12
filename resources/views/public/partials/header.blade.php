<header class="border-b border-gray-200 sticky top-0 z-40 shadow-sm" style="background:{{ $headerBg }}">
  <div class="max-w-7xl mx-auto px-4 flex items-center gap-4" style="color:{{ $headerText }}; min-height:{{ $headerHeight }}px">

    {{-- Logo --}}
    <a href="{{ $canonicalUrl }}" class="flex items-center gap-2.5 flex-shrink-0">
      @if($logoUrl ?? $logoSrc ?? null)
        <img src="{{ $logoUrl ?? $logoSrc }}" alt="{{ $project->name }}"
             style="height:{{ $logoHeight }}px; max-width:300px" class="object-contain w-auto">
      @else
        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:var(--c)">
          <span class="text-white font-black text-sm">{{ mb_strtoupper(mb_substr($project->name,0,1)) }}</span>
        </div>
        <span class="font-bold text-gray-800 text-sm hidden sm:block truncate max-w-[140px]">{{ $project->name }}</span>
      @endif
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

    {{-- Carrito / acción derecha --}}
    @if($headerCartBtn ?? true)
    <button @click="drawerOpen=true; drawerStep=1"
            class="relative flex-shrink-0 p-2 rounded-lg hover:bg-gray-100 transition" style="color:var(--c)">
      @if($isQuoteOnly ?? false)
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
    @endif

  </div>
</header>
