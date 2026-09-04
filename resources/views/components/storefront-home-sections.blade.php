@props(['project', 'settings' => [], 'sections' => collect(), 'products' => collect(), 'categories' => collect()])

@php
    $assetUrl = static function ($path) {
        if (!$path) return null;
        return str_starts_with((string) $path, 'http://') || str_starts_with((string) $path, 'https://')
            ? $path : asset('storage/'.ltrim((string) $path, '/'));
    };
    $sectionMap = $sections->keyBy('component');
    $currency = $settings['currency_symbol'] ?? 'S/';
    $safeColor = static fn ($value, $fallback = '#0f172a') => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $fallback;
    $loadProducts = static function ($section, bool $discounts = false) use ($products) {
        if (!$section) return collect();
        $content = $section->content ?? [];
        $limit = max(1, min(24, (int) ($content['limit'] ?? 8)));
        $available = collect($products)->filter(fn ($product) => $product->is_available && !str_starts_with((string) $product->id, 'svc-'));
        if (($content['selection'] ?? 'automatic') === 'manual' && !empty($content['product_ids'])) {
            $ids = array_values(array_map('intval', $content['product_ids']));
            return $available->whereIn('id', $ids)->sortBy(fn ($product) => array_search($product->id, $ids, true))->take($limit)->values();
        }
        if ($discounts) $available = $available->filter(fn ($product) => $product->compare_price !== null && (float) $product->compare_price > (float) $product->price);
        return $available->sortBy([['sort_order', 'asc'], ['id', 'desc']])->take($limit)->values();
    };
    $discountProducts = $loadProducts($sectionMap->get('discounts'), true);
    $featuredProducts = $loadProducts($sectionMap->get('featured_products'));
@endphp

<style>
    .sf-home-section{box-sizing:border-box;width:100%;padding:clamp(42px,6vw,76px) 20px;background:#fff;color:#172033;font-family:var(--store-font-body,Inter,sans-serif)}
    .sf-home-section *{box-sizing:border-box}.sf-home-container{width:min(1180px,100%);margin:0 auto}.sf-home-heading{display:flex;align-items:end;justify-content:space-between;gap:20px;margin-bottom:26px}.sf-home-heading h2{margin:0;color:#111827;font-size:clamp(25px,3vw,38px);line-height:1.12;letter-spacing:-.025em}.sf-home-heading p{max-width:640px;margin:8px 0 0;color:#64748b;line-height:1.65}.sf-home-link{color:var(--store-primary);font-weight:750;text-decoration:none}.sf-home-link:hover{text-decoration:underline}
    .sf-home-section a:focus-visible,.sf-home-section button:focus-visible{outline:3px solid color-mix(in srgb,var(--store-primary) 35%,transparent);outline-offset:3px}.sf-hide-mobile,.sf-hide-tablet,.sf-hide-desktop{}.sf-card{border:1px solid #e5e7eb;border-radius:var(--store-radius);background:#fff;box-shadow:0 10px 30px rgba(15,23,42,.055)}
    .sf-hero{position:relative;min-height:clamp(440px,62vw,650px);padding:0;overflow:hidden;background:#111827;color:#fff}.sf-hero-slide{position:absolute;inset:0;display:none;align-items:center;padding:70px 20px;background:linear-gradient(90deg,rgba(2,6,23,.82),rgba(2,6,23,.22)),var(--sf-image,none) center/cover no-repeat}.sf-hero-slide.is-active{display:flex}.sf-hero-content{position:relative;width:min(1180px,100%);margin:auto}.sf-hero-copy{max-width:700px}.sf-hero h1{margin:0;font-size:clamp(38px,6vw,68px);line-height:1.02;letter-spacing:-.045em}.sf-hero p{max-width:620px;margin:20px 0 0;color:rgba(255,255,255,.86);font-size:clamp(16px,2vw,20px);line-height:1.65}.sf-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:28px}.sf-button{display:inline-flex;min-height:46px;align-items:center;justify-content:center;padding:11px 20px;border:1px solid transparent;border-radius:var(--store-button-radius);background:var(--store-primary);color:#fff;font-weight:750;text-decoration:none;transition:transform .18s ease,filter .18s ease}.sf-button:hover{filter:brightness(.94);transform:translateY(-1px)}.sf-button-secondary{border-color:rgba(255,255,255,.55);background:rgba(255,255,255,.96);color:#111827}.sf-hero-nav{position:absolute;z-index:2;right:max(20px,calc((100vw - 1180px)/2));bottom:26px;display:flex;gap:8px}.sf-hero-nav button,.sf-carousel-control{width:44px;height:44px;border:1px solid #dbe2ea;border-radius:999px;background:#fff;color:#172033;font-size:21px;cursor:pointer;box-shadow:0 8px 24px rgba(15,23,42,.12)}
    .sf-benefits{background:#f8fafc}.sf-benefit-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.sf-benefit{display:flex;gap:14px;align-items:flex-start;padding:22px}.sf-benefit-icon{display:grid;flex:0 0 43px;width:43px;height:43px;place-items:center;border-radius:12px;background:color-mix(in srgb,var(--store-primary) 10%,white);color:var(--store-primary)}.sf-benefit-icon svg{width:22px;height:22px}.sf-benefit h3{margin:1px 0 5px;font-size:16px}.sf-benefit p{margin:0;color:#64748b;font-size:14px;line-height:1.55}
    .sf-announcement-grid{display:grid;grid-template-columns:repeat(var(--sf-count,2),minmax(0,1fr));gap:18px}.sf-announcement{position:relative;min-height:260px;overflow:hidden;padding:30px;display:flex;align-items:flex-end;background:#eef2ff center/cover no-repeat}.sf-announcement::before{content:"";position:absolute;inset:0;background:linear-gradient(180deg,transparent 18%,rgba(2,6,23,.82))}.sf-announcement-copy{position:relative;color:#fff}.sf-announcement h3{margin:0 0 8px;font-size:clamp(21px,2.5vw,30px)}.sf-announcement p{margin:0 0 16px;color:rgba(255,255,255,.82)}
    .sf-category-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:16px}.sf-category{display:block;overflow:hidden;color:#172033;text-align:center;text-decoration:none}.sf-category-media{aspect-ratio:1/.8;display:grid;place-items:center;background:#f1f5f9;overflow:hidden}.sf-category-media img{width:100%;height:100%;object-fit:cover}.sf-category-media span{font-size:32px;font-weight:800;color:var(--store-primary)}.sf-category strong{display:block;padding:15px 10px}
    .sf-offer{padding:0;background:#f8fafc}.sf-offer-inner{display:grid;grid-template-columns:1.2fr .8fr;min-height:350px;overflow:hidden}.sf-offer-copy{padding:clamp(34px,6vw,68px);display:flex;flex-direction:column;justify-content:center;color:#fff;background:var(--sf-offer-bg,#0f172a)}.sf-offer-copy h2{margin:0;font-size:clamp(30px,4vw,50px);letter-spacing:-.035em}.sf-offer-copy p{max-width:600px;color:rgba(255,255,255,.8);line-height:1.7}.sf-countdown{display:flex;flex-wrap:wrap;gap:8px;margin:18px 0}.sf-countdown span{min-width:66px;padding:10px;border:1px solid rgba(255,255,255,.22);border-radius:10px;text-align:center;font-weight:800}.sf-offer-media{min-height:280px;background:#e2e8f0 center/cover no-repeat}
    .sf-products{background:#f8fafc}.sf-product-grid{display:grid;grid-template-columns:repeat(var(--sf-cols-desktop,3),minmax(0,1fr));gap:18px}.sf-product-card{position:relative;overflow:hidden;color:#172033;text-decoration:none}.sf-product-image{aspect-ratio:1/.82;display:grid;place-items:center;background:#fff;overflow:hidden}.sf-product-image img{width:100%;height:100%;object-fit:contain;padding:12px}.sf-product-info{padding:17px}.sf-product-category{color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.06em}.sf-product-card h3{margin:7px 0 12px;font-size:16px;line-height:1.35}.sf-price{display:flex;align-items:center;gap:9px;font-weight:800;color:var(--store-primary)}.sf-price del{font-size:13px;color:#94a3b8;font-weight:500}.sf-sale{position:absolute;z-index:2;top:12px;left:12px;padding:5px 8px;border-radius:999px;background:#dc2626;color:#fff;font-size:11px;font-weight:800}.sf-carousel-wrap{position:relative}.sf-carousel{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(230px,27%);gap:18px;overflow-x:auto;scroll-snap-type:x mandatory;scrollbar-width:none}.sf-carousel::-webkit-scrollbar{display:none}.sf-carousel>*{scroll-snap-align:start}.sf-carousel-controls{display:flex;gap:8px}.sf-empty{padding:28px;color:#64748b;text-align:center}
    .sf-blog-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}.sf-article{overflow:hidden;color:#172033;text-decoration:none}.sf-article img{width:100%;aspect-ratio:1/.62;object-fit:cover;background:#e2e8f0}.sf-article-copy{padding:20px}.sf-article-tag{color:var(--store-primary);font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.06em}.sf-article h3{margin:8px 0;font-size:20px;line-height:1.25}.sf-article p{margin:0 0 14px;color:#64748b;line-height:1.6}.sf-article time{color:#94a3b8;font-size:12px}
    @media(max-width:1023px){.sf-benefit-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.sf-category-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.sf-product-grid{grid-template-columns:repeat(var(--sf-cols-tablet,2),minmax(0,1fr))}.sf-carousel{grid-auto-columns:minmax(230px,45%)}.sf-hide-tablet{display:none!important}}
    @media(max-width:767px){.sf-home-section{padding:40px 16px}.sf-home-heading{align-items:start;flex-direction:column}.sf-hero{min-height:510px}.sf-hero-slide{padding:64px 18px;background-image:linear-gradient(180deg,rgba(2,6,23,.48),rgba(2,6,23,.92)),var(--sf-mobile-image,var(--sf-image,none))}.sf-hero h1{font-size:clamp(35px,12vw,50px)}.sf-actions{display:grid}.sf-button{width:100%}.sf-benefit-grid,.sf-announcement-grid,.sf-blog-grid{grid-template-columns:1fr}.sf-category-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.sf-offer-inner{grid-template-columns:1fr}.sf-offer-copy{padding:38px 22px}.sf-product-grid{grid-template-columns:repeat(var(--sf-cols-mobile,1),minmax(0,1fr))}.sf-carousel{grid-auto-columns:minmax(230px,82%)}.sf-hide-mobile{display:none!important}}
    @media(min-width:1024px){.sf-hide-desktop{display:none!important}}
    @media(prefers-reduced-motion:reduce){.sf-button{transition:none}.sf-carousel{scroll-behavior:auto!important}}
</style>
<style>.sf-offer-copy:only-child{grid-column:1/-1}</style>
<style>
    /* Bloques que hasta ahora solo pintaba `computienda`. Se apoyan en las
       mismas piezas que el resto del Inicio (.sf-card, .sf-button) para que
       hereden el tema del negocio en vez de traer su propia paleta. */
    .sf-eyebrow{display:inline-block;font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.7;margin-bottom:8px}
    .sf-about-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:clamp(24px,4vw,52px);align-items:center}
    .sf-about-grid.is-solo{grid-template-columns:1fr}
    .sf-about-copy h2{margin:0 0 10px}.sf-about-copy p{margin:0 0 16px;line-height:1.65;opacity:.85}
    .sf-about-cifras{display:flex;flex-wrap:wrap;gap:26px;margin:18px 0 20px}
    .sf-about-cifras strong{display:block;font-size:26px;line-height:1.1}
    .sf-about-cifras span{font-size:12.5px;opacity:.7}
    .sf-about-foto img{width:100%;height:100%;max-height:420px;object-fit:cover;border-radius:14px;display:block}

    .sf-brand-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:18px;align-items:center}
    .sf-brand{display:flex;align-items:center;justify-content:center;padding:10px}
    .sf-brand img{max-width:100%;max-height:58px;object-fit:contain;display:block}
    .sf-brand-grid.is-gris img{filter:grayscale(1);opacity:.75;transition:filter .2s ease,opacity .2s ease}
    .sf-brand-grid.is-gris a:hover img{filter:none;opacity:1}

    .sf-coll-grid{display:grid;grid-template-columns:repeat(var(--sf-coll-cols,3),minmax(0,1fr));gap:18px}
    .sf-coll{position:relative;overflow:hidden;min-height:230px;display:block;text-decoration:none;color:#fff}
    .sf-coll img{width:100%;height:100%;position:absolute;inset:0;object-fit:cover}
    .sf-coll-copy{position:relative;z-index:1;display:block;padding:20px;background:linear-gradient(to top,rgba(0,0,0,.72),transparent 62%);min-height:230px;display:flex;flex-direction:column;justify-content:flex-end}
    .sf-coll-copy strong{font-size:18px}.sf-coll-copy small{opacity:.85;font-size:12.5px}

    .sf-cta{color:#fff}
    .sf-cta-inner{display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap}
    .sf-cta h2{margin:0 0 6px}.sf-cta p{margin:0;opacity:.9}
    .sf-cta-btn{background:#fff;color:#111}

    .sf-faq-list{display:grid;gap:10px}
    .sf-faq-list.is-2col{grid-template-columns:repeat(2,minmax(0,1fr))}
    .sf-faq-item{padding:0}
    .sf-faq-item summary{cursor:pointer;padding:15px 18px;font-weight:700;list-style:none}
    .sf-faq-item summary::-webkit-details-marker{display:none}
    .sf-faq-item summary::after{content:'+';float:right;font-weight:400;opacity:.6}
    .sf-faq-item[open] summary::after{content:'−'}
    .sf-faq-item>div{padding:0 18px 16px;line-height:1.65;opacity:.85}

    .sf-gal-grid{display:grid;grid-template-columns:repeat(var(--sf-gal-cols,3),minmax(0,1fr));gap:14px}
    .sf-gal{margin:0;overflow:hidden}
    .sf-gal img,.sf-gal iframe{width:100%;aspect-ratio:4/3;object-fit:cover;display:block;border:0}
    .sf-gal figcaption{padding:10px 14px;font-size:12.5px;opacity:.75}

    .sf-strip-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px}
    .sf-strip-item{display:flex;gap:12px;align-items:center;text-decoration:none;color:inherit}
    .sf-strip-item img{width:40px;height:40px;object-fit:contain;flex:0 0 40px}
    .sf-strip-item strong{display:block;font-size:14.5px}
    .sf-strip-item small{opacity:.72;font-size:12.5px}

    .sf-media-wrap{position:relative;min-height:var(--sf-media-h,380px);display:flex;align-items:center;overflow:hidden}
    .sf-media-bg{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border:0}
    .sf-media-velo{position:absolute;inset:0;pointer-events:none}
    .sf-media-copy{position:relative;z-index:1;width:min(1180px,100%);margin:0 auto;padding:32px 20px;color:#fff}
    .sf-media-copy h2{margin:0 0 8px}.sf-media-copy p{margin:0 0 16px;opacity:.92}

    .sf-testi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px}
    .sf-testi-card{margin:0;padding:20px}
    .sf-testi-estrellas{color:#f59e0b;letter-spacing:2px;margin-bottom:8px}
    .sf-testi-card blockquote{margin:0 0 14px;line-height:1.65;font-style:italic;opacity:.9}
    .sf-testi-card figcaption{display:flex;gap:10px;align-items:center}
    .sf-testi-card figcaption img{width:40px;height:40px;border-radius:50%;object-fit:cover}
    .sf-testi-card figcaption strong{display:block;font-size:14px}
    .sf-testi-card figcaption small{opacity:.7;font-size:12px}

    .sf-wa-inner{display:flex;align-items:center;justify-content:space-between;gap:22px;flex-wrap:wrap}
    .sf-wa h2{margin:0 0 6px}.sf-wa p{margin:0;opacity:.85}
    .sf-wa-btn{background:#25d366;color:#08301b;border-color:#25d366}
    .sf-wa.is-card .sf-wa-inner{border-radius:16px;padding:26px;background:#f8fafc}

    @media(max-width:900px){
        .sf-about-grid{grid-template-columns:1fr}
        .sf-faq-list.is-2col{grid-template-columns:1fr}
        .sf-coll-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
        .sf-gal-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    }
    @media(max-width:560px){
        .sf-coll-grid,.sf-gal-grid{grid-template-columns:1fr}
        .sf-cta-inner,.sf-wa-inner{flex-direction:column;align-items:flex-start}
    }
</style>


@foreach($sections as $section)
    @php
        $content = $section->content ?? [];
        $deviceClass = collect([!$section->show_mobile ? 'sf-hide-mobile' : null,!$section->show_tablet ? 'sf-hide-tablet' : null,!$section->show_desktop ? 'sf-hide-desktop' : null])->filter()->implode(' ');
    @endphp

    @if($section->component === 'hero')
        @php
            $slides = ($content['mode'] ?? 'single') === 'slider'
                ? collect($content['slides'] ?? [])->filter(fn($slide) => ($slide['enabled'] ?? false) && filled($slide['title'] ?? null))->sortBy('sort_order')->values()
                : collect([$content['single'] ?? []]);
        @endphp
        @if($slides->isNotEmpty())
            <section class="sf-home-section sf-hero {{ $deviceClass }}" data-store-home-section="hero" data-store-placement="hero" data-autoplay="{{ !empty($content['autoplay']) ? '1':'0' }}" data-interval="{{ max(3,(int)($content['interval'] ?? 6)) }}">
                @foreach($slides as $slide)
                    @php $desktop = $assetUrl($slide['desktop_image'] ?? null); $mobile = $assetUrl($slide['mobile_image'] ?? null); @endphp
                    <article class="sf-hero-slide {{ $loop->first ? 'is-active':'' }}" style="--sf-image:url('{{ $desktop }}');--sf-mobile-image:url('{{ $mobile ?: $desktop }}')">
                        <div class="sf-hero-content"><div class="sf-hero-copy">
                            <h1>{{ $slide['title'] ?? $project->name }}</h1>
                            @if(filled($slide['body'] ?? null))<p>{{ $slide['body'] }}</p>@endif
                            <div class="sf-actions">
                                @if(filled($slide['primary_text'] ?? null))<a class="sf-button" style="background:{{ $safeColor($slide['primary_color'] ?? null, '#2563eb') }}" href="{{ ($slide['primary_url'] ?? '') === '#catalogo' || blank($slide['primary_url'] ?? null) ? route('public.shop',$project->slug) : $slide['primary_url'] }}">{{ $slide['primary_text'] }}</a>@endif
                                @if(filled($slide['secondary_text'] ?? null))<a class="sf-button sf-button-secondary" style="color:{{ $safeColor($slide['secondary_color'] ?? null) }}" href="{{ ($slide['secondary_url'] ?? '') === '#catalogo' || blank($slide['secondary_url'] ?? null) ? route('public.shop',$project->slug) : $slide['secondary_url'] }}">{{ $slide['secondary_text'] }}</a>@endif
                            </div>
                        </div></div>
                    </article>
                @endforeach
                @if($slides->count() > 1)<div class="sf-hero-nav"><button type="button" data-sf-prev aria-label="Anterior">‹</button><button type="button" data-sf-next aria-label="Siguiente">›</button></div>@endif
            </section>
        @endif
    @elseif($section->component === 'benefits')
        @php $items = collect($content['items'] ?? [])->filter(fn($item) => $item['enabled'] ?? false)->sortBy('sort_order')->take(4); @endphp
        @if($items->isNotEmpty())<section class="sf-home-section sf-benefits {{ $deviceClass }}" data-store-home-section="benefits" data-store-placement="before-catalog"><div class="sf-home-container">
            <div class="sf-home-heading"><div><h2>{{ $content['title'] ?? 'Compra con confianza' }}</h2>@if(filled($content['body'] ?? null))<p>{{ $content['body'] }}</p>@endif</div></div>
            <div class="sf-benefit-grid">@foreach($items as $item)<article class="sf-card sf-benefit"><span class="sf-benefit-icon" aria-hidden="true">
                @if(($item['icon'] ?? '') === 'truck')<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 6h11v10H3zM14 9h4l3 3v4h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg>
                @elseif(($item['icon'] ?? '') === 'clock')<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9" stroke-width="1.8"/><path d="M12 7v5l3 2" stroke-width="1.8" stroke-linecap="round"/></svg>
                @elseif(($item['icon'] ?? '') === 'sparkles')<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="1.8" stroke-linejoin="round" d="m12 3 1.4 4.1L17.5 8.5l-4.1 1.4L12 14l-1.4-4.1-4.1-1.4 4.1-1.4L12 3Zm6 10 .8 2.2L21 16l-2.2.8L18 19l-.8-2.2L15 16l2.2-.8L18 13Z"/></svg>
                @else<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="1.8" stroke-linejoin="round" d="M4 10h16v10H4zM3 10l2-6h14l2 6M9 20v-6h6v6"/></svg>@endif
            </span><div><h3>{{ $item['title'] ?? '' }}</h3><p>{{ $item['description'] ?? '' }}</p></div></article>@endforeach</div>
        </div></section>@endif
    @elseif($section->component === 'announcements')
        @php
            $now = now();
            $items = collect($content['items'] ?? [])->filter(fn($item) => ($item['enabled'] ?? false) && (empty($item['starts_at']) || $now->gte($item['starts_at'])) && (empty($item['ends_at']) || $now->lte($item['ends_at'])))->sortBy('sort_order')->take(max(1,min(4,(int)($content['quantity'] ?? 2))));
        @endphp
        @if($items->isNotEmpty())<section class="sf-home-section {{ $deviceClass }}" data-store-home-section="announcements" data-store-placement="before-catalog"><div class="sf-home-container">
            @if(filled($content['title'] ?? null))<div class="sf-home-heading"><h2>{{ $content['title'] }}</h2></div>@endif
            <div class="sf-announcement-grid" style="--sf-count:{{ $items->count() }}">@foreach($items as $item)<article class="sf-card sf-announcement" style="background-image:url('{{ $assetUrl($item['image'] ?? null) }}')"><div class="sf-announcement-copy"><h3>{{ $item['title'] ?? '' }}</h3>@if(filled($item['description'] ?? null))<p>{{ $item['description'] }}</p>@endif @if(filled($item['button_text'] ?? null))<a class="sf-button" href="{{ ($item['url'] ?? '') === '#catalogo' || blank($item['url'] ?? null) ? route('public.shop',$project->slug) : $item['url'] }}">{{ $item['button_text'] }}</a>@endif</div></article>@endforeach</div>
        </div></section>@endif
    @elseif($section->component === 'featured_categories')
        @php
            $ids = array_values(array_map('intval',$content['category_ids'] ?? [])); $limit=max(1,min(12,(int)($content['limit'] ?? 5)));
            $availableCategories=collect($categories)->filter(fn($cat)=>$cat->is_active && $cat->parent_id===null);
            $cats=$ids ? $availableCategories->whereIn('id',$ids)->sortBy(fn($cat)=>array_search($cat->id,$ids,true))->take($limit)->values() : $availableCategories->sortBy('sort_order')->take($limit)->values();
        @endphp
        @if($cats->isNotEmpty())<section class="sf-home-section {{ $deviceClass }}" data-store-home-section="featured_categories" data-store-placement="before-catalog"><div class="sf-home-container"><div class="sf-home-heading"><h2>{{ $content['title'] ?? 'Encuentra lo que buscas' }}</h2></div><div class="sf-category-grid">
            @foreach($cats as $cat)<a class="sf-card sf-category" href="{{ route('public.shop',[$project->slug,'category'=>$cat->id]) }}"><span class="sf-category-media">@if(($content['display'] ?? 'images')==='images' && $cat->image_url)<img loading="lazy" src="{{ $assetUrl($cat->image_url) }}" alt="{{ $cat->name }}">@else<span>{{ mb_strtoupper(mb_substr($cat->name,0,1)) }}</span>@endif</span><strong>{{ $cat->name }}</strong></a>@endforeach
        </div></div></section>@endif
    @elseif($section->component === 'daily_offer')
        @php $endsAt=$content['ends_at'] ?? null; $expired=$endsAt && now()->gte($endsAt); @endphp
        @if(!$expired || ($content['expired_action'] ?? 'hide') === 'message')<section class="sf-home-section sf-offer {{ $deviceClass }}" data-store-home-section="daily_offer" data-store-placement="before-catalog" @if($endsAt)data-sf-countdown="{{ \Illuminate\Support\Carbon::parse($endsAt)->toIso8601String() }}" data-expired-action="{{ $content['expired_action'] ?? 'hide' }}"@endif><div class="sf-home-container sf-card sf-offer-inner">
            <div class="sf-offer-copy" style="--sf-offer-bg:{{ $safeColor($content['background_color'] ?? null) }}"><h2>{{ $expired ? ($content['expired_message'] ?? 'Esta oferta ha finalizado.') : ($content['title'] ?? 'Solo por hoy') }}</h2>@if(!$expired && filled($content['body'] ?? null))<p>{{ $content['body'] }}</p>@endif @if(!$expired && $endsAt)<div class="sf-countdown" aria-live="polite"><span data-days>00 d</span><span data-hours>00 h</span><span data-minutes>00 m</span><span data-seconds>00 s</span></div>@endif @if(!$expired && filled($content['button_text'] ?? null))<div><a class="sf-button" href="{{ ($content['button_url'] ?? '') === '#catalogo' || blank($content['button_url'] ?? null) ? route('public.shop',$project->slug) : $content['button_url'] }}">{{ $content['button_text'] }}</a></div>@endif</div>
            @if($assetUrl($content['image'] ?? null))<div class="sf-offer-media" role="img" aria-label="{{ $content['title'] ?? 'Oferta' }}" style="background-image:url('{{ $assetUrl($content['image']) }}')"></div>@endif
        </div></section>@endif
    @elseif($section->component === 'discounts')
        @php $showOld=$content['show_old_price']??true; $showCurrent=$content['show_current_price']??true; $showPercent=$content['show_percentage']??true; $layout=$content['layout']??'grid'; @endphp
        @if($discountProducts->isNotEmpty())<section class="sf-home-section sf-products {{ $deviceClass }}" data-store-home-section="discounts" data-store-placement="before-catalog"><div class="sf-home-container"><div class="sf-home-heading"><h2>{{ $content['title'] ?? 'Productos con descuento' }}</h2>@if($layout==='carousel')<div class="sf-carousel-controls"><button class="sf-carousel-control" type="button" data-carousel-prev aria-label="Anterior">‹</button><button class="sf-carousel-control" type="button" data-carousel-next aria-label="Siguiente">›</button></div>@endif</div>
            <div class="{{ $layout==='carousel'?'sf-carousel':'sf-product-grid' }}" @if($layout==='grid')style="--sf-cols-desktop:{{ (int)($content['columns_desktop']??3) }};--sf-cols-tablet:{{ (int)($content['columns_tablet']??2) }};--sf-cols-mobile:{{ (int)($content['columns_mobile']??1) }}"@endif>
            @foreach($discountProducts as $product)@php $percent=$product->compare_price>0?round((1-($product->price/$product->compare_price))*100):0; @endphp<a class="sf-card sf-product-card" href="{{ route('public.product',[$project->slug,$product->id]) }}">@if($showPercent)<span class="sf-sale">-{{ $percent }}%</span>@endif<div class="sf-product-image">@if($product->main_image_url)<img loading="lazy" src="{{ $product->main_image_url }}" alt="{{ $product->name }}">@endif</div><div class="sf-product-info">@if($product->category)<span class="sf-product-category">{{ $product->category->name }}</span>@endif<h3>{{ $product->name }}</h3><div class="sf-price">@if($showCurrent)<span>{{ $currency }} {{ number_format((float)$product->price,2) }}</span>@endif @if($showOld)<del>{{ $currency }} {{ number_format((float)$product->compare_price,2) }}</del>@endif</div></div></a>@endforeach
            </div></div></section>@endif
    @elseif($section->component === 'featured_products')
        @if($featuredProducts->isNotEmpty())<section class="sf-home-section {{ $deviceClass }}" data-store-home-section="featured_products" data-store-placement="before-catalog" data-autoplay="{{ !empty($content['autoplay'])?'1':'0' }}" data-interval="{{ max(3,(int)($content['autoplay_seconds']??6)) }}"><div class="sf-home-container"><div class="sf-home-heading"><h2>{{ $content['title'] ?? 'Productos destacados' }}</h2>@if($content['show_arrows']??true)<div class="sf-carousel-controls"><button class="sf-carousel-control" type="button" data-carousel-prev aria-label="Anterior">‹</button><button class="sf-carousel-control" type="button" data-carousel-next aria-label="Siguiente">›</button></div>@endif</div><div class="sf-carousel">
            @foreach($featuredProducts as $product)<a class="sf-card sf-product-card" href="{{ route('public.product',[$project->slug,$product->id]) }}"><div class="sf-product-image">@if($product->main_image_url)<img loading="lazy" src="{{ $product->main_image_url }}" alt="{{ $product->name }}">@endif</div><div class="sf-product-info">@if($product->category)<span class="sf-product-category">{{ $product->category->name }}</span>@endif<h3>{{ $product->name }}</h3><div class="sf-price">{{ $currency }} {{ number_format((float)$product->price,2) }}</div></div></a>@endforeach
        </div></div></section>@endif
    @elseif($section->component === 'blog')
        @php $articles=collect($content['items']??[])->filter(fn($item)=>($item['enabled']??false)&&filled($item['title']??null))->sortBy('sort_order')->take(max(2,min(3,(int)($content['limit']??3)))); @endphp
        @if($articles->isNotEmpty())<section class="sf-home-section {{ $deviceClass }}" data-store-home-section="blog" data-store-placement="after-catalog"><div class="sf-home-container"><div class="sf-home-heading"><h2>{{ $content['title'] ?? 'Consejos y novedades' }}</h2>@if(filled($content['all_text']??null))<a class="sf-home-link" href="{{ blank($content['all_url']??null)||$content['all_url']==='#' ? route('public.blog',$project->slug) : $content['all_url'] }}">{{ $content['all_text'] }}</a>@endif</div><div class="sf-blog-grid">
            @foreach($articles as $article)<a class="sf-card sf-article" href="{{ filled($article['url']??null) ? $article['url'] : route('public.blog.show',[$project->slug,$article['key']]) }}">@if($assetUrl($article['image']??null))<img loading="lazy" src="{{ $assetUrl($article['image']) }}" alt="{{ $article['title'] }}">@endif<div class="sf-article-copy">@if(filled($article['tag']??null))<span class="sf-article-tag">{{ $article['tag'] }}</span>@endif<h3>{{ $article['title'] }}</h3>@if(filled($article['summary']??null))<p>{{ $article['summary'] }}</p>@endif @if(filled($article['date']??null))<time>{{ \Illuminate\Support\Carbon::parse($article['date'])->translatedFormat('d M Y') }}</time>@endif</div></a>@endforeach
        </div></div></section>@endif
    @elseif($section->component === 'locations')
        {{-- Sedes con mapa. El editor ya existía en el Constructor y solo lo
             pintaba `computienda`: en las demás plantillas el negocio cargaba
             su dirección y no salía nada. El mapa va por el incrustado público
             de Google, que no pide clave de API ni cuenta. --}}
        @php
            $items = collect($content['items'] ?? [])
                ->filter(fn ($i) => ($i['enabled'] ?? true) && (filled($i['name'] ?? null) || filled($i['address'] ?? null)))
                ->sortBy('sort_order')->take(8);
            $ladoMapa = ($content['variant'] ?? 'cards') === 'map-side';
        @endphp
        @if($items->isNotEmpty())
        <section class="sf-home-section sf-locations {{ $deviceClass }}" data-store-home-section="locations" data-store-placement="after-catalog"><div class="sf-home-container">
            <div class="sf-home-heading"><div><h2>{{ $content['title'] ?? 'Visítanos' }}</h2>@if(filled($content['subtitle'] ?? null))<p>{{ $content['subtitle'] }}</p>@endif</div></div>
            <div class="sf-loc-grid {{ $ladoMapa ? 'is-side' : '' }}">
                @foreach($items as $loc)
                <article class="sf-card sf-loc">
                    @if(($loc['show_map'] ?? true) && filled($loc['address'] ?? null))
                    <div class="sf-loc-map">
                        <iframe src="https://www.google.com/maps?q={{ urlencode($loc['address']) }}&output=embed&hl=es"
                                title="Mapa de {{ $loc['name'] ?? $loc['address'] }}" loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                    @endif
                    <div class="sf-loc-body">
                        @if(filled($loc['name'] ?? null))<h3>{{ $loc['name'] }}</h3>@endif
                        @if(filled($loc['address'] ?? null))<p>{{ $loc['address'] }}</p>@endif
                        @if(filled($loc['phone'] ?? null))<p class="sf-loc-dato">Tel. {{ $loc['phone'] }}</p>@endif
                        @if(filled($loc['hours'] ?? null))<p class="sf-loc-dato">{{ $loc['hours'] }}</p>@endif
                        @if(filled($loc['address'] ?? null))
                        <a class="sf-loc-link" target="_blank" rel="noopener"
                           href="https://www.google.com/maps/search/?api=1&query={{ urlencode($loc['address']) }}">Cómo llegar</a>
                        @endif
                    </div>
                </article>
                @endforeach
            </div>
        </div></section>
        @endif
    @elseif($section->component === 'about_preview')
        {{-- Presentación del negocio: texto, foto y cifras. --}}
        @php
            $cifras = collect($content['items'] ?? [])->filter(fn ($i) => ($i['enabled'] ?? true) && filled($i['value'] ?? null))->sortBy('sort_order')->take(4);
            $foto = $assetUrl($content['image'] ?? null);
        @endphp
        @if(filled($content['title'] ?? null) || filled($content['body'] ?? null))
        <section class="sf-home-section sf-about {{ $deviceClass }}" data-store-home-section="about_preview" data-store-placement="before-catalog"><div class="sf-home-container">
            <div class="sf-about-grid {{ $foto ? '' : 'is-solo' }}">
                <div class="sf-about-copy">
                    @if(filled($content['label'] ?? null))<span class="sf-eyebrow">{{ $content['label'] }}</span>@endif
                    @if(filled($content['title'] ?? null))<h2>{{ $content['title'] }}</h2>@endif
                    @if(filled($content['body'] ?? null))<p>{{ $content['body'] }}</p>@endif
                    @if($cifras->isNotEmpty())
                    <div class="sf-about-cifras">@foreach($cifras as $c)<div><strong>{{ $c['value'] }}</strong><span>{{ $c['title'] ?? '' }}</span></div>@endforeach</div>
                    @endif
                    @if(filled($content['button_text'] ?? null))<a class="sf-button" href="{{ $content['button_url'] ?? '#' }}">{{ $content['button_text'] }}</a>@endif
                </div>
                @if($foto)<div class="sf-about-foto"><img loading="lazy" src="{{ $foto }}" alt="{{ $content['title'] ?? $project->name }}"></div>@endif
            </div>
        </div></section>
        @endif

    @elseif($section->component === 'brands')
        {{-- Marcas con las que trabaja el negocio. --}}
        @php $items = collect($content['items'] ?? [])->filter(fn ($i) => ($i['enabled'] ?? true) && filled($i['image'] ?? null))->sortBy('sort_order')->take(24); @endphp
        @if($items->isNotEmpty())
        <section class="sf-home-section sf-brands {{ $deviceClass }}" data-store-home-section="brands" data-store-placement="before-catalog"><div class="sf-home-container">
            @if(filled($content['title'] ?? null))<div class="sf-home-heading"><div><h2>{{ $content['title'] }}</h2>@if(filled($content['subtitle'] ?? null))<p>{{ $content['subtitle'] }}</p>@endif</div></div>@endif
            <div class="sf-brand-grid {{ ($content['grayscale'] ?? false) ? 'is-gris' : '' }}">
                @foreach($items as $m)
                @if(filled($m['url'] ?? null))<a href="{{ $m['url'] }}" target="_blank" rel="noopener" class="sf-brand">@else<span class="sf-brand">@endif
                    <img loading="lazy" src="{{ $assetUrl($m['image']) }}" alt="{{ $m['name'] ?? '' }}">
                @if(filled($m['url'] ?? null))</a>@else</span>@endif
                @endforeach
            </div>
        </div></section>
        @endif

    @elseif($section->component === 'collection_showcase')
        {{-- Colecciones destacadas: imagen grande que lleva a una categoría. --}}
        @php
            $items = collect($content['items'] ?? [])->filter(fn ($i) => ($i['enabled'] ?? true) && (filled($i['title'] ?? null) || filled($i['image'] ?? null)))->sortBy('sort_order')->take(8);
            $cols = max(2, min(4, (int) ($content['columns'] ?? 3)));
        @endphp
        @if($items->isNotEmpty())
        <section class="sf-home-section sf-collections {{ $deviceClass }}" data-store-home-section="collection_showcase" data-store-placement="before-catalog"><div class="sf-home-container">
            @if(filled($content['title'] ?? null))<div class="sf-home-heading"><div><h2>{{ $content['title'] }}</h2>@if(filled($content['subtitle'] ?? null))<p>{{ $content['subtitle'] }}</p>@endif</div></div>@endif
            <div class="sf-coll-grid" style="--sf-coll-cols:{{ $cols }}">
                @foreach($items as $c)
                @php $destino = filled($c['url'] ?? null) ? $c['url'] : (filled($c['category_id'] ?? null) ? route('public.shop', [$project->slug, 'category' => $c['category_id']]) : route('public.shop', $project->slug)); @endphp
                <a class="sf-card sf-coll" href="{{ $destino }}">
                    @if($img = $assetUrl($c['image'] ?? null))<img loading="lazy" src="{{ $img }}" alt="{{ $c['title'] ?? '' }}">@endif
                    <span class="sf-coll-copy"><strong>{{ $c['title'] ?? '' }}</strong>@if(filled($c['subtitle'] ?? null))<small>{{ $c['subtitle'] }}</small>@endif</span>
                </a>
                @endforeach
            </div>
        </div></section>
        @endif

    @elseif($section->component === 'cta_banner')
        {{-- Llamada a la acción a todo el ancho. --}}
        @php $fondo = $safeColor($content['background_color'] ?? null, '#0f172a'); $img = $assetUrl($content['image'] ?? null); @endphp
        @if(filled($content['title'] ?? null))
        <section class="sf-home-section sf-cta {{ $deviceClass }}" data-store-home-section="cta_banner" data-store-placement="after-catalog"
                 style="background:{{ $fondo }};@if($img)background-image:linear-gradient(to right,{{ $fondo }}ee,{{ $fondo }}99),url('{{ $img }}');background-size:cover;background-position:center;@endif">
            <div class="sf-home-container sf-cta-inner">
                <div><h2>{{ $content['title'] }}</h2>@if(filled($content['subtitle'] ?? null))<p>{{ $content['subtitle'] }}</p>@endif</div>
                @if(filled($content['button_text'] ?? null))<a class="sf-button sf-cta-btn" href="{{ $content['button_url'] ?? '#' }}">{{ $content['button_text'] }}</a>@endif
            </div>
        </section>
        @endif

    @elseif($section->component === 'faq')
        {{-- Preguntas frecuentes. Se usa `details`, que abre y cierra sin
             JavaScript y es accesible con teclado por defecto. --}}
        @php $items = collect($content['items'] ?? [])->filter(fn ($i) => ($i['enabled'] ?? true) && filled($i['question'] ?? null))->sortBy('sort_order')->take(12); @endphp
        @if($items->isNotEmpty())
        <section class="sf-home-section sf-faq {{ $deviceClass }}" data-store-home-section="faq" data-store-placement="after-catalog"><div class="sf-home-container">
            <div class="sf-home-heading"><div><h2>{{ $content['title'] ?? 'Preguntas frecuentes' }}</h2>@if(filled($content['subtitle'] ?? null))<p>{{ $content['subtitle'] }}</p>@endif</div></div>
            <div class="sf-faq-list {{ ($content['variant'] ?? 'accordion') === 'two-columns' ? 'is-2col' : '' }}">
                @foreach($items as $f)
                <details class="sf-card sf-faq-item"><summary>{{ $f['question'] }}</summary><div>{{ $f['answer'] ?? '' }}</div></details>
                @endforeach
            </div>
        </div></section>
        @endif

    @elseif($section->component === 'gallery')
        {{-- Galería de fotos y vídeos. --}}
        @php
            $items = collect($content['items'] ?? [])->filter(fn ($i) => ($i['enabled'] ?? true) && (filled($i['image'] ?? null) || filled($i['video_url'] ?? null)))->sortBy('sort_order')->take(12);
            $cols = max(2, min(4, (int) ($content['columns'] ?? 3)));
        @endphp
        @if($items->isNotEmpty())
        <section class="sf-home-section sf-gallery {{ $deviceClass }}" data-store-home-section="gallery" data-store-placement="after-catalog"><div class="sf-home-container">
            @if(filled($content['title'] ?? null))<div class="sf-home-heading"><div><h2>{{ $content['title'] }}</h2>@if(filled($content['subtitle'] ?? null))<p>{{ $content['subtitle'] }}</p>@endif</div></div>@endif
            <div class="sf-gal-grid" style="--sf-gal-cols:{{ $cols }}">
                @foreach($items as $g)
                <figure class="sf-card sf-gal">
                    @if(($g['type'] ?? 'image') === 'video' && filled($g['video_url'] ?? null))
                    <iframe src="{{ $g['video_url'] }}" title="{{ $g['caption'] ?? 'Vídeo' }}" loading="lazy" allowfullscreen></iframe>
                    @elseif($img = $assetUrl($g['image'] ?? null))
                    <img loading="lazy" src="{{ $img }}" alt="{{ $g['caption'] ?? '' }}">
                    @endif
                    @if(filled($g['caption'] ?? null))<figcaption>{{ $g['caption'] }}</figcaption>@endif
                </figure>
                @endforeach
            </div>
        </div></section>
        @endif

    @elseif($section->component === 'info_strip')
        {{-- Banda de datos sueltos: envíos, garantía, pago… --}}
        @php $items = collect($content['items'] ?? [])->filter(fn ($i) => ($i['enabled'] ?? true) && filled($i['title'] ?? null))->sortBy('sort_order')->take(6); @endphp
        @if($items->isNotEmpty())
        <section class="sf-home-section sf-strip {{ $deviceClass }}" data-store-home-section="info_strip" data-store-placement="before-catalog"><div class="sf-home-container">
            @if(filled($content['title'] ?? null))<div class="sf-home-heading"><div><h2>{{ $content['title'] }}</h2>@if(filled($content['subtitle'] ?? null))<p>{{ $content['subtitle'] }}</p>@endif</div></div>@endif
            <div class="sf-strip-grid">
                @foreach($items as $i)
                @if(filled($i['url'] ?? null))<a href="{{ $i['url'] }}" class="sf-strip-item">@else<div class="sf-strip-item">@endif
                    @if($img = $assetUrl($i['image'] ?? null))<img loading="lazy" src="{{ $img }}" alt="">@endif
                    <span><strong>{{ $i['title'] }}</strong>@if(filled($i['description'] ?? null))<small>{{ $i['description'] }}</small>@endif</span>
                @if(filled($i['url'] ?? null))</a>@else</div>@endif
                @endforeach
            </div>
        </div></section>
        @endif

    @elseif($section->component === 'media_banner')
        {{-- Banda de imagen o vídeo a todo el ancho, con velo para que el
             texto encima se lea. --}}
        @php
            $img = $assetUrl($content['desktop_image'] ?? null) ?: $assetUrl($content['fallback_image'] ?? null);
            $movil = $assetUrl($content['mobile_image'] ?? null);
            $velo = $safeColor($content['overlay_color'] ?? null, '#0f172a');
            $op = max(0, min(90, (int) ($content['overlay_opacity'] ?? 40))) / 100;
            $alto = max(180, min(720, (int) ($content['height'] ?? 380)));
            $alineado = $content['align'] ?? 'center';
            $alineado = in_array($alineado, ['left', 'center', 'right'], true) ? $alineado : 'center';
        @endphp
        @if($img || filled($content['video_url'] ?? null) || filled($content['title'] ?? null))
        <section class="sf-home-section sf-media {{ $deviceClass }}" data-store-home-section="media_banner" data-store-placement="before-catalog"
                 style="--sf-media-h:{{ $alto }}px;padding:0">
            <div class="sf-media-wrap">
                @if(($content['media_type'] ?? 'image') === 'video' && filled($content['video_url'] ?? null))
                <iframe class="sf-media-bg" src="{{ $content['video_url'] }}" title="{{ $content['title'] ?? 'Vídeo' }}" loading="lazy" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                @elseif($img)
                <img class="sf-media-bg" loading="lazy" src="{{ $movil ?: $img }}" srcset="{{ $movil ? $movil.' 800w, '.$img.' 1600w' : $img }}" alt="{{ $content['title'] ?? '' }}">
                @endif
                <span class="sf-media-velo" style="background:{{ $velo }};opacity:{{ $op }}"></span>
                <div class="sf-media-copy" style="text-align:{{ $alineado }}">
                    @if(filled($content['title'] ?? null))<h2>{{ $content['title'] }}</h2>@endif
                    @if(filled($content['subtitle'] ?? null))<p>{{ $content['subtitle'] }}</p>@endif
                    @if(filled($content['button_text'] ?? null))<a class="sf-button" href="{{ $content['button_url'] ?? '#' }}">{{ $content['button_text'] }}</a>@endif
                </div>
            </div>
        </section>
        @endif

    @elseif($section->component === 'testimonials')
        {{-- Lo que dicen los clientes. --}}
        @php $items = collect($content['items'] ?? [])->filter(fn ($i) => ($i['enabled'] ?? true) && filled($i['text'] ?? null))->sortBy('sort_order')->take(max(1, min(12, (int) ($content['limit'] ?? 6)))); @endphp
        @if($items->isNotEmpty())
        <section class="sf-home-section sf-testi {{ $deviceClass }}" data-store-home-section="testimonials" data-store-placement="after-catalog"><div class="sf-home-container">
            <div class="sf-home-heading"><div><h2>{{ $content['title'] ?? 'Lo que dicen nuestros clientes' }}</h2>@if(filled($content['subtitle'] ?? null))<p>{{ $content['subtitle'] }}</p>@endif</div></div>
            <div class="sf-testi-grid">
                @foreach($items as $t)
                <figure class="sf-card sf-testi-card">
                    @if(($estrellas = (int) ($t['rating'] ?? 0)) > 0)<div class="sf-testi-estrellas" aria-label="{{ $estrellas }} de 5">{{ str_repeat('★', min(5, $estrellas)) }}</div>@endif
                    <blockquote>{{ $t['text'] }}</blockquote>
                    <figcaption>
                        @if($img = $assetUrl($t['image'] ?? null))<img loading="lazy" src="{{ $img }}" alt="{{ $t['name'] ?? '' }}">@endif
                        <span><strong>{{ $t['name'] ?? '' }}</strong>@if(filled($t['role'] ?? null))<small>{{ $t['role'] }}</small>@endif</span>
                    </figcaption>
                </figure>
                @endforeach
            </div>
        </div></section>
        @endif

    @elseif($section->component === 'wa_advisory')
        {{-- Asesoría por WhatsApp. Si el bloque no trae teléfono se usa el del
             negocio: la fuente del WhatsApp es la etapa 01, no este bloque. --}}
        @php
            $tel = preg_replace('/\D+/', '', (string) ($content['phone'] ?? '')) ?: preg_replace('/\D+/', '', (string) ($settings['quote_whatsapp'] ?? $project->phone ?? ''));
            $texto = $content['message'] ?? 'Hola, quisiera una asesoría.';
        @endphp
        @if(filled($tel))
        <section class="sf-home-section sf-wa {{ $deviceClass }} {{ ($content['variant'] ?? 'band') === 'card' ? 'is-card' : '' }}" data-store-home-section="wa_advisory" data-store-placement="after-catalog"><div class="sf-home-container sf-wa-inner">
            <div>
                <h2>{{ $content['title'] ?? '¿Necesitas asesoría?' }}</h2>
                @if(filled($content['subtitle'] ?? null))<p>{{ $content['subtitle'] }}</p>@endif
            </div>
            <a class="sf-button sf-wa-btn" target="_blank" rel="noopener"
               href="https://wa.me/{{ str_starts_with($tel, '51') ? $tel : '51'.$tel }}?text={{ urlencode($texto) }}">{{ $content['button_text'] ?? 'Escríbenos por WhatsApp' }}</a>
        </div></section>
        @endif

    @endif
@endforeach

<script>
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('.sf-hero').forEach(hero=>{const slides=[...hero.querySelectorAll('.sf-hero-slide')];let i=0;const show=n=>{i=(n+slides.length)%slides.length;slides.forEach((s,x)=>s.classList.toggle('is-active',x===i))};hero.querySelector('[data-sf-prev]')?.addEventListener('click',()=>show(i-1));hero.querySelector('[data-sf-next]')?.addEventListener('click',()=>show(i+1));if(hero.dataset.autoplay==='1'&&slides.length>1&&!matchMedia('(prefers-reduced-motion: reduce)').matches)setInterval(()=>show(i+1),Number(hero.dataset.interval||6)*1000)});
  document.querySelectorAll('.sf-carousel').forEach(track=>{const section=track.closest('[data-store-home-section]');const move=d=>track.scrollBy({left:d*track.clientWidth*.8,behavior:'smooth'});section?.querySelector('[data-carousel-prev]')?.addEventListener('click',()=>move(-1));section?.querySelector('[data-carousel-next]')?.addEventListener('click',()=>move(1));if(section?.dataset.autoplay==='1'&&!matchMedia('(prefers-reduced-motion: reduce)').matches)setInterval(()=>move(1),Number(section.dataset.interval||6)*1000)});
  document.querySelectorAll('[data-sf-countdown]').forEach(section=>{const end=new Date(section.dataset.sfCountdown).getTime();const tick=()=>{const diff=end-Date.now();if(diff<=0){if(section.dataset.expiredAction==='hide')section.remove();else location.reload();return}const values=[Math.floor(diff/86400000),Math.floor(diff/3600000)%24,Math.floor(diff/60000)%60,Math.floor(diff/1000)%60];['days','hours','minutes','seconds'].forEach((key,x)=>{const el=section.querySelector(`[data-${key}]`);if(el)el.textContent=String(values[x]).padStart(2,'0')+' '+['d','h','m','s'][x]})};tick();setInterval(tick,1000)});
});
</script>
