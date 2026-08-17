{{-- Tarjeta de producto UNIFICADA: mismo diseño y formato que las del catálogo.
     Recibe $p (Product); hereda del padre: $project, $currency, $settings,
     $hidePrices, $quoteMode, $showCartButton, $showInquiryButton, $inquiryText,
     $whatsapp, $inquiryMsgBase, $cartText. --}}
@php
    // Enlace sin el slug interno cuando la tienda tiene dominio propio.
    $pcUrl = \App\Support\ImageVariants::productUrl($project, $p->id, $p->name);
    // Sirve el .webp de la foto cuando existe: las tarjetas son lo que mas se
    // repite en la pagina y las originales pesaban mas de 1 MB cada una.
    $pcImg = \App\Support\ImageVariants::webp($p->main_image_url);
    $pcOnSale = filled($p->compare_price) && $p->compare_price > $p->price;
    $pcPct = $pcOnSale ? (int) round((1 - ((float) $p->price / max(0.01, (float) $p->compare_price))) * 100) : 0;
    // Novedad: se marca sola por antigüedad, sin tener que etiquetar a mano.
    // El plazo se ajusta en el constructor (Catálogo → días para "Nuevo").
    $pcNewDays = (int) ($settings['new_badge_days'] ?? 30);
    $pcIsNew = $pcNewDays > 0 && $p->created_at && $p->created_at->gt(now()->subDays($pcNewDays));
    $pcSizes = $p->sizes;
    $pcData = [
        'id' => $p->id, 'name' => $p->name, 'price' => (float) $p->price,
        'comparePrice' => $pcOnSale ? (float) $p->compare_price : null,
        'image' => $pcImg, 'category' => $p->category->name ?? '',
        'url' => $pcUrl, 'stock' => $p->stock, 'sizes' => $pcSizes, 'sku' => $p->sku,
        // Resumen para la vista rapida. Esta tarjeta la usan las secciones de
        // la PORTADA; sin esto el resumen solo salia en el catalogo.
        'resumen' => \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $p->description))), 180),
    ];
@endphp
<article class="catalog-card">
    <div class="catalog-card-media{{ $pcImg ? '' : ' is-noimg' }}">
        <a class="catalog-card-media-link" href="{{ $pcUrl }}" @click="qvMobile($event, {{ Js::from($pcData) }})" aria-label="Ver {{ $p->name }}">
            @if($pcImg)<img src="{{ $pcImg }}" alt="{{ $p->name }}" loading="lazy">@else<svg class="catalog-card-placeholder" width="74" height="74" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linejoin="round" aria-hidden="true"><path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="M4 7v10l8 4 8-4V7"/><path d="M12 11v10"/></svg><span class="ph-note">{{ trim((string) ($settings['card_no_photo_text'] ?? '')) ?: 'Foto en camino' }}</span>@endif
        </a>
        @if($pcOnSale)<span class="catalog-discount">-{{ $pcPct }}%</span>@endif
        @if($pcIsNew && ! $pcOnSale)<span class="catalog-new">Nuevo</span>@endif
        <div class="catalog-quickview">
            <button type="button" @click.prevent.stop="openQuickView({{ Js::from($pcData) }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>{{ $settings['catalog_quickview_text'] ?? 'Vista rápida' }}</button>
        </div>
    </div>
    <div class="catalog-card-body">
        <span class="catalog-card-category">{{ $p->category->name ?? 'Producto' }}</span>
        <a class="catalog-card-name" href="{{ $pcUrl }}" @click="qvMobile($event, {{ Js::from($pcData) }})">{{ $p->name }}</a>
        @if($hidePrices)
        <div class="catalog-card-prices"><span class="quote-price">Precio a solicitud</span></div>
        @else
        <div class="catalog-card-prices"><span class="catalog-card-price">{{ $currency }} {{ number_format((float) $p->price, 2) }}</span>@if($pcOnSale)<span class="catalog-card-compare">{{ $currency }} {{ number_format((float) $p->compare_price, 2) }}</span>@endif @if($p->has_tax)<span class="catalog-card-tax-note">Incluye IGV</span>@endif</div>
        @if(($wholesale ?? false) && filled($p->wholesale_price))
@php $whFold = (bool) ($settings['wholesale_card_collapse'] ?? false); @endphp
        <div class="buy-block buy-block--wholesale{{ $whFold ? ' is-foldable' : '' }}" x-data="{ q: {{ max(1,(int) ($p->wholesale_min_qty ?? 1)) }}, open: {{ $whFold ? 'false' : 'true' }} }">
            <div class="buy-head" @if($whFold) role="button" tabindex="0" :aria-expanded="open" @click.prevent.stop="open=!open" @keydown.enter.prevent.stop="open=!open" @endif><span class="buy-tag">Mayorista</span><span class="buy-price">{{ $currency }} {{ number_format((float) $p->wholesale_price, 2) }}</span>@if($whFold)<svg class="buy-fold" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true" :style="open&&'transform:rotate(180deg)'"><path d="m6 9 6 6 6-6"/></svg>@endif</div>
            <small class="buy-min" @if($whFold) x-show="open" x-cloak @endif>Por {{ (filled($p->wholesale_unit) && !is_numeric($p->wholesale_unit)) ? $p->wholesale_unit : 'unidades' }} · desde {{ max(1,(int) ($p->wholesale_min_qty ?? 1)) }}</small>
            <div class="buy-row" @if($whFold) x-show="open" x-cloak @endif>
                <span class="buy-qty">
                    <button type="button" @click.prevent.stop="q=Math.max({{ max(1,(int) ($p->wholesale_min_qty ?? 1)) }},q-1)" aria-label="Quitar">−</button>
                    <input type="number" x-model.number="q" min="{{ max(1,(int) ($p->wholesale_min_qty ?? 1)) }}" @click.stop>
                    <button type="button" @click.prevent.stop="q=q+1" aria-label="Agregar">+</button>
                </span>
                <button type="button" class="buy-add" @click.prevent.stop="addWholesale({{ $p->id }},{{ Js::from($p->name) }},{{ (float) $p->wholesale_price }},q,{{ Js::from($p->main_image_url ?? '') }},{{ Js::from($p->category?->name ?? '') }})">+ Agregar</button>
            </div>
        </div>@endif
        @endif
    </div>
    <div class="catalog-card-actions">
        @if($showCartButton)<button class="catalog-card-action" type="button" @click="@if(count($pcSizes))openQuickView({{ Js::from($pcData) }})@else add({{ $p->id }},{{ Js::from($p->name) }},{{ (float) $p->price }},{{ Js::from($pcImg ?? '') }},{{ Js::from($p->category->name ?? '') }})@endif">{{ $quoteMode ? $quoteBtnText : $cartText }}</button>@endif
        @if($showInquiryButton)<a class="catalog-card-inquiry" href="https://wa.me/{{ $whatsapp }}?text={{ urlencode($inquiryMsgBase.$p->name.' '.$pcUrl) }}" target="_blank" rel="noopener"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.4 14.1c-.2.7-1.3 1.3-1.9 1.4-.5.1-1.1.1-1.8-.1-.4-.1-1-.3-1.7-.6-2.9-1.3-4.8-4.2-5-4.4-.1-.2-1.2-1.6-1.2-3s.7-2.1 1-2.4c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.4.2.5.7 1.8.8 1.9.1.1.1.3 0 .5l-.4.6c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1.1 2.2 1.4 2.5 1.5.3.1.5.1.6-.1.2-.2.7-.8.9-1.1.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.2 1.4Z"/></svg>{{ $inquiryText }}</a>@endif
    </div>
</article>
