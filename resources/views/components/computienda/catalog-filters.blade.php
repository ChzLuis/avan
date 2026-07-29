@props([
    'categories',
    'catalogProducts',
    'filterScope' => 'desktop',
])

<details class="catalog-filter-group" open>
    <summary class="catalog-filter-summary">
        <span>Categoría</span>
        <span class="catalog-filter-badge" x-show="filterCat" x-cloak>1</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
    </summary>
    <div class="catalog-filter-body">
        @if($categories->count() >= 6)
            <label class="catalog-filter-search">
                <span class="sr-only">Buscar categoría</span>
                <input type="search" x-model.debounce.150ms="catSearch" placeholder="Buscar categorías..." autocomplete="off">
            </label>
        @endif

        <label class="catalog-filter-option">
            <input type="radio" name="category_{{ $filterScope }}" value="" x-model="filterCat" @change="filterSubCat=''">
            <span>Todos los productos</span>
            <small>{{ $catalogProducts->count() }}</small>
        </label>

        @foreach($categories as $category)
            @php
                $categoryTotal = $category->products->count() + $category->children->sum(fn ($child) => $child->products->count());
                $hasChildren = $category->children->isNotEmpty();
            @endphp
            <div x-show="categoryMatches($el.dataset.categoryName)" data-category-name="{{ $category->name }}">
                <label class="catalog-filter-option">
                    <input type="radio" name="category_{{ $filterScope }}" value="{{ $category->id }}" x-model="filterCat" @change="filterSubCat='';catOpen[String({{ (int) $category->id }})]=true">
                    <span>{{ $category->name }}</span>
                    <small>{{ $categoryTotal }}</small>
                </label>

                @if($hasChildren)
                    <div class="catalog-subcategories" x-show="filterCat===String({{ (int) $category->id }}) || catOpen[String({{ (int) $category->id }})]" x-cloak>
                        @foreach($category->children as $subcategory)
                            <label class="catalog-filter-option catalog-filter-option-sub">
                                <input type="radio" name="subcategory_{{ $filterScope }}" value="{{ $subcategory->id }}" x-model="filterSubCat" @change="filterCat=String({{ (int) $category->id }})">
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

<details class="catalog-filter-group" open>
    <summary class="catalog-filter-summary">
        <span>Disponibilidad</span>
        <span class="catalog-filter-badge" x-show="filterInStock || filterOnSale" x-text="Number(filterInStock)+Number(filterOnSale)" x-cloak></span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
    </summary>
    <div class="catalog-filter-body">
        <label class="catalog-filter-option catalog-filter-checkbox"><input type="checkbox" x-model="filterInStock"><span>Solo en stock</span></label>
        <label class="catalog-filter-option catalog-filter-checkbox"><input type="checkbox" x-model="filterOnSale"><span>Solo en oferta</span></label>
    </div>
</details>
