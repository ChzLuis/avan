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
    // Colores del mismo modelo. Vacío salvo que la tienda tenga la agrupación
    // encendida y este modelo exista de verdad en más de un color.
    $pcVariantes = \App\Modules\Tienda\Storefront\AgrupadorModelos::activoEn($project)
        ? app(\App\Modules\Tienda\Storefront\AgrupadorModelos::class)->variantesDe($project, $p->id, $project->slug)
        : [];
    $pcNombre = $pcVariantes ? \App\Modules\Tienda\Storefront\AgrupadorModelos::claveModelo($p) : $p->name;
    $pcData = [
        'id' => $p->id, 'name' => $pcNombre, 'price' => (float) $p->price,
        'comparePrice' => $pcOnSale ? (float) $p->compare_price : null,
        'image' => $pcImg, 'category' => $p->category->name ?? '',
        'url' => $pcUrl, 'stock' => $p->stock, 'sizes' => $pcSizes, 'sku' => $p->sku,
        'marca' => $p->marca?->label, 'unit' => $p->unit,
        // Resumen para la vista rapida. Esta tarjeta la usan las secciones de
        // la PORTADA; sin esto el resumen solo salia en el catalogo.
        // `RichText::plain` y no `strip_tags`: la descripcion guarda entidades
        // (&iacute;) y strip_tags las deja crudas, asi que el resumen de la
        // vista rapida mostraba "bater&iacute;a" en vez de "bateria".
        'resumen' => \Illuminate\Support\Str::limit(\App\Support\RichText::plain($p->description), 180),
        'variantes' => $pcVariantes,
        'color' => $pcVariantes ? \App\Modules\Tienda\Storefront\AgrupadorModelos::colorActivo($pcVariantes, $pcImg) : null,
        'srcset' => \App\Support\Imagen\Img::srcsetDe($pcImg, 'producto'),
        'srcsetWebp' => \App\Support\Imagen\Img::srcsetDe($pcImg, 'producto', 'webp'),
    ];
@endphp
{{-- vp arranca con los mismos datos que el HTML: sin JS la tarjeta se ve igual
     y con JS el selector de color puede reemplazarla en el sitio. --}}
<article class="catalog-card" x-data="{ vp: {{ Js::from($pcData) }} }">
    <div class="catalog-card-media{{ $pcImg ? '' : ' is-noimg' }}" @if($marcaAgua ?? null) style="--card-marca:url('{{ $marcaAgua }}');--card-marca-op:{{ $marcaOpacidad ?? .42 }}"@endif>
        <a class="catalog-card-media-link" href="{{ $pcUrl }}" :href="vp.url" @click="qvMobile($event, vp)" aria-label="Ver {{ $pcNombre }}">
            @if($pcImg && $pcVariantes)<picture><source type="image/webp" :srcset="vp.srcsetWebp || ''" sizes="(max-width:640px) 50vw, 320px"><img src="{{ $pcImg }}" :src="vp.image" :srcset="vp.srcset || ''" sizes="(max-width:640px) 50vw, 320px" alt="{{ $pcNombre }}" width="800" height="800" loading="lazy" decoding="async"></picture>@elseif($pcImg){!! \App\Support\Imagen\Img::etiqueta($pcImg, 'producto', ['alt' => $p->name]) !!}@else<svg class="catalog-card-placeholder" width="74" height="74" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linejoin="round" aria-hidden="true"><path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="M4 7v10l8 4 8-4V7"/><path d="M12 11v10"/></svg><span class="ph-note">{{ trim((string) ($settings['card_no_photo_text'] ?? '')) ?: 'Foto en camino' }}</span>@endif
        </a>
        @if($pcOnSale)<span class="catalog-discount">-{{ $pcPct }}%</span>@endif
        @if($pcIsNew && ! $pcOnSale)<span class="catalog-new">Nuevo</span>@endif
        @if($verVistaRapida ?? true)<div class="catalog-quickview">
            <button type="button" title="{{ $settings['catalog_quickview_text'] ?? 'Vista rápida' }}" aria-label="{{ $settings['catalog_quickview_text'] ?? 'Vista rápida' }}" @click.prevent.stop="openQuickView(vp)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg></button>
        </div>@endif
    </div>
    <div class="catalog-card-body">
        <span class="catalog-card-category">{{ $p->category->name ?? 'Producto' }}</span>@if($p->marca?->label)<span class="catalog-card-brand">{{ $p->marca->label }}</span>@endif
        <a class="catalog-card-name" href="{{ $pcUrl }}" :href="vp.url" @click="qvMobile($event, vp)">{{ $pcNombre }}</a>
        @if($hidePrices)
        {{-- El texto es configurable (`price_on_request_text`) y la plantilla ya
             lo respeta en sus otras rejillas; aquí estaba escrito a mano, así
             que al personalizarlo unas tarjetas cambiaban y otras no. --}}
        <div class="catalog-card-prices"><span class="quote-price">{{ $txtPrecioConsul ?? 'Precio a solicitud' }}</span></div>
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
                <button type="button" class="buy-add" @click.prevent.stop="addWholesale(vp.id, vp.name, {{ (float) $p->wholesale_price }}, q, vp.image||'', vp.category||'')">+ Agregar</button>
            </div>
        </div>@endif
        @endif
    </div>
    <div class="catalog-card-actions">
        @if($showCartButton)<button class="catalog-card-action" type="button" @click="@if(count($pcSizes))openQuickView(vp)@else add(vp.id, vp.name, vp.price, vp.image||'', vp.category||'', '', 0, 0, null, {sku:vp.sku, marca:vp.marca, unidad:vp.unit})@endif">{{ $quoteMode ? $quoteBtnText : $cartText }}</button>@endif
        {{-- aria-label con el nombre: en una rejilla de 20 tarjetas, un lector de
             pantalla anunciaba veinte enlaces "Consultar" idénticos y no había
             forma de saber a qué producto correspondía cada uno. --}}
        @if($showInquiryButton)<a class="catalog-card-inquiry" href="https://wa.me/{{ $whatsapp }}?text={{ urlencode($inquiryMsgBase.$pcNombre.' '.$pcUrl) }}" :href="'https://wa.me/{{ $whatsapp }}?text='+encodeURIComponent(@js($inquiryMsgBase)+vp.name+' '+vp.url)" target="_blank" rel="noopener" aria-label="{{ $inquiryText }} por WhatsApp: {{ $pcNombre }}" :aria-label="@js($inquiryText.' por WhatsApp: ')+vp.name"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.4 14.1c-.2.7-1.3 1.3-1.9 1.4-.5.1-1.1.1-1.8-.1-.4-.1-1-.3-1.7-.6-2.9-1.3-4.8-4.2-5-4.4-.1-.2-1.2-1.6-1.2-3s.7-2.1 1-2.4c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.4.2.5.7 1.8.8 1.9.1.1.1.3 0 .5l-.4.6c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1.1 2.2 1.4 2.5 1.5.3.1.5.1.6-.1.2-.2.7-.8.9-1.1.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.2 1.4Z"/></svg>{{ $inquiryText }}</a>@endif
        <a class="catalog-card-more" href="{{ $pcUrl }}" :href="vp.url" aria-label="Ver producto: {{ $pcNombre }}" :aria-label="'Ver producto: '+vp.name">Ver producto <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
    </div>
</article>
