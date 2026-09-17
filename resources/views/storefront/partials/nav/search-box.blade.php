{{-- Buscador compartido del shell (usa el Alpine del body: query/fetchSuggest/goSearch). --}}
<label class="search hpx-search" :class="{'search-open': searchOpen}" @click.outside="suggestOpen=false" @keydown.escape="suggestOpen=false">
    <span class="sr-only">Buscar en el catálogo</span>
    <input type="search" x-model="query" @input.debounce.250ms="fetchSuggest()" @focus="suggest.length && (suggestOpen=true)" @keydown.enter.prevent="goSearch()"
           placeholder="{{ ($sh['search'] ?? '') === 'takeover' ? 'Busca por producto, código, marca o modelo…' : $txtSearchPlaceholder }}" autocomplete="off" role="combobox" aria-label="Buscar productos" :aria-expanded="suggestOpen">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
    <div class="search-suggest {{ !empty($sh['search_panel']) ? 'hpx-search-panel' : '' }}" x-show="suggestOpen" x-cloak role="listbox" @click.stop>
        <div class="hpx-sp-products">
            @if(!empty($sh['search_panel']))<p class="hpx-sp-title">Productos</p>@endif
            <template x-for="sp in suggest" :key="'sg-'+sp.id">
                <a class="search-suggest-item" :href="sp.url" role="option">
                    <span class="search-suggest-thumb"><template x-if="sp.image"><img :src="sp.image" :alt="sp.name" loading="lazy"></template></span>
                    <span class="search-suggest-info"><strong x-text="sp.name"></strong><small x-text="sp.category||''"></small></span>
                    @if($hidePrices ?? false)
                    <span class="search-suggest-price">{{ $txtPrecioConsul ?? 'A consultar' }}</span>
                    @else
                    {{-- Un producto sin precio tampoco se pinta en cero: se
                         deja el hueco y el cliente pregunta. --}}
                    <span class="search-suggest-price" x-show="sp.price > 0" x-text="money(sp.price)"></span>
                    @endif
                </a>
            </template>
            <button type="button" class="search-suggest-all" @click="goSearch()">Ver todos los resultados <span aria-hidden="true">→</span></button>
        </div>
        @if(!empty($sh['search_panel']) && ($navCategories ?? collect())->count())
        <div class="hpx-sp-cats">
            <p class="hpx-sp-title">Categorías</p>
            @foreach($navCategories->take(8) as $spc)
            <a href="{{ $shopBase }}?category={{ $spc->id }}">{{ $spc->name }}</a>
            @endforeach
        </div>
        @endif
    </div>
</label>
