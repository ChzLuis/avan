@props([
    'categories',
    'catalogProducts',
    'filterScope' => 'desktop',
    'profileLinks' => [],
    'facets' => collect(),
    'brands' => collect(),
    'brandMap' => [],
    // Tienda por cotizacion: no se muestran precios, asi que filtrar por
    // precio (o por "en oferta") no tiene sentido y confunde.
    'sinPrecio' => false,
])

@php
    // Un filtro que no filtra nada es ruido y, si lleva a cero resultados, es
    // una promesa incumplida. Se calculan aqui, sobre el catalogo real, para
    // ocultar los que no aportan en esta tienda.
    $fPrecios = collect($catalogProducts)->pluck('price')->filter(fn ($v) => (float) $v > 0);
    $fRango = $fPrecios->count() ? (float) $fPrecios->max() - (float) $fPrecios->min() : 0.0;
    $fMostrarPrecio = ! $sinPrecio && $fPrecios->count() > 3 && $fRango >= 20;
    $fHayOfertas = ! $sinPrecio && collect($catalogProducts)->contains(fn ($p) => filled($p['comparePrice'] ?? null) && (float) $p['comparePrice'] > (float) ($p['price'] ?? 0));
    $fHayStock = collect($catalogProducts)->contains(fn ($p) => ($p['stock'] ?? null) === null || (int) $p['stock'] > 0);
@endphp

<details class="catalog-filter-group" open>
    <summary class="catalog-filter-summary">
        <span>Categoría</span>
        {{-- ⚠️ Este panel usaba `filterCat`/`filterSubCat` (singular), variables
             que NO existen en el estado Alpine: el estado declara los arrays
             `filterCats`/`filterSubCats` y solo esos están vigilados por
             $watch. Resultado: pulsar una categoría escribía en una variable
             que nadie observaba, nunca se disparaba `catalog:filters-changed`
             y el catálogo no se refiltraba. Precio y disponibilidad sí
             funcionaban porque usan los nombres correctos.
             Se pasa a casillas con los arrays reales, que es la
             multi-selección que el backend (`category[]`) y los chips
             removibles ya soportaban. --}}
        <span class="catalog-filter-badge" x-show="filterCats.length+filterSubCats.length" x-text="filterCats.length+filterSubCats.length" x-cloak></span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
    </summary>
    <div class="catalog-filter-body">
        @if($categories->count() >= 6)
            <label class="catalog-filter-search">
                <span class="sr-only">Buscar categoría</span>
                <input type="search" x-model.debounce.150ms="catSearch" placeholder="Buscar categorías..." autocomplete="off">
            </label>
        @endif

        {{-- "Todos" ya no es una opción más: es limpiar la selección. --}}
        <label class="catalog-filter-option">
            <input type="radio" name="category_{{ $filterScope }}" value=""
                   :checked="!filterCats.length && !filterSubCats.length"
                   @change="filterCats=[];filterSubCats=[]">
            <span>Todos los productos</span>
            <small>{{ $catalogProducts->count() }}</small>
        </label>

        @foreach($categories as $category)
            @php
                $categoryTotal = $category->products->count() + $category->children->sum(fn ($child) => $child->products->count());
                $hasChildren = $category->children->isNotEmpty();
            @endphp
            {{-- Una categoría con 0 productos es un callejón sin salida: el
                 comprador la pulsa y se queda mirando una rejilla vacía. Se
                 omite. Salta sobre todo dentro de un perfil, donde el alcance
                 recorta el catálogo y algunas ramas se quedan sin nada. --}}
            @continue($categoryTotal < 1)
            <div x-show="categoryMatches($el.dataset.categoryName)" data-category-name="{{ $category->name }}">
                @if(isset($profileLinks[(int) $category->id]))
                    {{-- Esta categoria ES un mundo (perfil): se entra en el, con su
                         color, su logo y su pie, en vez de filtrar en el sitio. --}}
                    <a class="catalog-filter-option catalog-filter-option-world" href="{{ $profileLinks[(int) $category->id]['url'] }}">
                        <span>{{ $category->name }}</span>
                        <small>{{ $categoryTotal }}</small>
                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                    </a>
                @else
                <label class="catalog-filter-option">
                    <input type="radio" name="category_{{ $filterScope }}" value="{{ $category->id }}"
                           :checked="filterCats.includes('{{ $category->id }}')"
                           @change="filterCats=['{{ $category->id }}'];filterSubCats=[];catOpen[String({{ (int) $category->id }})]=true">
                    <span>{{ $category->name }}</span>
                    <small>{{ $categoryTotal }}</small>
                </label>
                @endif

                @if($hasChildren)
                    <div class="catalog-subcategories" x-show="filterCats.includes(String({{ (int) $category->id }})) || catOpen[String({{ (int) $category->id }})]" x-cloak>
                        @foreach($category->children as $subcategory)
                            <label class="catalog-filter-option catalog-filter-option-sub">
                                <input type="radio" name="category_{{ $filterScope }}" value="{{ $subcategory->id }}"
                                       :checked="filterSubCats.includes('{{ $subcategory->id }}')"
                                       @change="filterSubCats=['{{ $subcategory->id }}'];filterCats=[]">
                                <span>{{ $subcategory->name }}</span>
                                <small>{{ $subcategory->products->count() }}</small>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</details>

@foreach($facets as $attribute)
<details class="catalog-filter-group" open>
    <summary class="catalog-filter-summary">
        <span>{{ $attribute->name }}</span>
        <span class="catalog-filter-badge" x-show="(filterAttributes['{{ $attribute->id }}']||[]).length" x-text="(filterAttributes['{{ $attribute->id }}']||[]).length" x-cloak></span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
    </summary>
    <div class="catalog-filter-body">
        @foreach($attribute->values as $value)
        <label class="catalog-filter-option catalog-filter-checkbox">
            <input type="checkbox" value="{{ $value->id }}" x-model="filterAttributes['{{ $attribute->id }}']">
            @if($attribute->type === 'color' && $value->color_hex)<span aria-hidden="true" style="width:18px;height:18px;flex:0 0 auto;border:1px solid #cbd5e1;border-radius:50%;background:{{ $value->color_hex }}"></span>@endif
            <span>{{ $value->label }}</span>
        </label>
        @endforeach
    </div>
</details>
@endforeach

@if($fMostrarPrecio)
<details class="catalog-filter-group" open>
    <summary class="catalog-filter-summary">
        <span>Precio</span>
        <span class="catalog-filter-badge" x-show="priceMin>0 || priceMax<maxPrice" x-cloak>1</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
    </summary>
    <div class="catalog-filter-body">
        <div class="catalog-price-inputs">
            <label><span>Mínimo</span><input type="number" x-model.number="priceMin" min="0" :max="Math.max(0,priceMax-1)" @change="clampPrices()"></label>
            <label><span>Máximo</span><input type="number" x-model.number="priceMax" :min="priceMin+1" :max="maxPrice" @change="clampPrices()"></label>
        </div>
        <div class="catalog-range" aria-hidden="true">
            <span :style="`left:${priceMin/maxPrice*100}%;right:${(1-priceMax/maxPrice)*100}%`"></span>
            <input type="range" min="0" :max="maxPrice" x-model.number="priceMin" @input="clampPrices('min')" tabindex="-1">
            <input type="range" min="0" :max="maxPrice" x-model.number="priceMax" @input="clampPrices('max')" tabindex="-1">
        </div>
    </div>
</details>
@endif


{{-- Marca: seleccion multiple (a diferencia de categoria, que es unica).
     Quien compra en ferreteria acepta dos marcas equivalentes para el mismo
     articulo, asi que marcar Indeco Y Bticino a la vez tiene sentido. --}}
@if(!empty($brands) && count($brands))
<details class="catalog-filter-group" open>
    <summary class="catalog-filter-summary">
        <span>Marca</span>
        <span class="catalog-filter-badge" x-show="filterBrands.length" x-text="filterBrands.length" x-cloak></span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
    </summary>
    <div class="catalog-filter-body">
        {{-- `marcaAplica` oculta la marca cuando no tiene nada en el rubro
             elegido: sin esto, 46 de las 56 combinaciones posibles llevaban a
             una pagina vacia (medido en este catalogo). --}}
        @foreach($brands as $m)
        <label class="catalog-filter-option catalog-filter-checkbox"
               x-show="marcaAplica({{ $m->id }})" x-cloak>
            <input type="checkbox" value="{{ $m->id }}" x-model="filterBrands">
            <span>{{ $m->label }}</span>
            <small>{{ $m->total }}</small>
        </label>
        @endforeach
    </div>
</details>
@endif

@if($fHayOfertas || $fHayStock)
<details class="catalog-filter-group" open>
    <summary class="catalog-filter-summary">
        <span>Disponibilidad</span>
        <span class="catalog-filter-badge" x-show="filterInStock || filterOnSale" x-text="Number(filterInStock)+Number(filterOnSale)" x-cloak></span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
    </summary>
    <div class="catalog-filter-body">
        @if($fHayStock)<label class="catalog-filter-option catalog-filter-checkbox"><input type="checkbox" x-model="filterInStock"><span>Solo en stock</span></label>@endif
        @if($fHayOfertas)<label class="catalog-filter-option catalog-filter-checkbox"><input type="checkbox" x-model="filterOnSale"><span>Solo en oferta</span></label>@endif
    </div>
</details>
@endif
