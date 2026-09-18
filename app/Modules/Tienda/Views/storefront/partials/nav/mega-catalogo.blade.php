{{-- MEGAMENÚ DE CATÁLOGO (mega_enabled=1). Se pinta dentro del desplegable
     "Categorías" de la cabecera. Espera en el ámbito: $project, $settings,
     $navCategories (con children), $assetUrl, $whatsapp, $categoryIconPaths,
     $autoCategoryIcon y la variable Alpine `catsOpen` del <nav> padre. --}}
@php
    // Las variables de un @include no viajan entre vistas: se resuelven aqui.
    $categoryIconPaths = $categoryIconPaths ?? \App\Modules\Tienda\Support\IconosCategoria::paths();
    $autoCategoryIcon = $autoCategoryIcon ?? (static fn ($n) => \App\Modules\Tienda\Support\IconosCategoria::auto($n));
    $mmCats = $navCategories->take(max(4, min(14, (int) ($settings['mega_max_cats'] ?? 12))))->values();
    $mmBase = \App\Modules\Tienda\Support\StorefrontNavigation::publicUrl($project);
    $mmShop = \App\Modules\Tienda\Support\StorefrontNavigation::shopUrl($project);
    $mmUrl = static fn ($u) => str_starts_with((string) $u, '/') ? rtrim($mmBase, '/').$u : (string) $u;
    $mmBrands = collect();
    if (($settings['mega_brands'] ?? '1') !== '0') {
        try { $mmBrands = app(\App\Modules\Tienda\Storefront\CatalogQueryService::class)->marcas($project)->take(max(2, min(9, (int) ($settings['mega_brands_max'] ?? 6)))); } catch (\Throwable $e) { $mmBrands = collect(); }
    }
    $mmQuick = [];
    $mmQuickDef = [['Promociones', '/promociones', 'etiqueta'], ['Catálogo PDF', '/catalogo', 'documento'], ['Marcas', '/marcas', 'marca']];
    foreach ([1, 2, 3, 4] as $n) {
        $lbl = trim((string) ($settings["mega_quick_{$n}_label"] ?? ($mmQuickDef[$n - 1][0] ?? '')));
        if ($lbl === '' || ($settings["mega_quick_{$n}_label"] ?? null) === '-') continue;
        $mmQuick[] = ['l' => $lbl, 'u' => $mmUrl(trim((string) ($settings["mega_quick_{$n}_url"] ?? '')) ?: ($mmQuickDef[$n - 1][1] ?? $mmShop)), 'i' => $settings["mega_quick_{$n}_icon"] ?? ($mmQuickDef[$n - 1][2] ?? 'estrella')];
    }
    $mmQuickIcons = [
        'etiqueta'  => '<path d="M20 13 11 22l-8-8V5h9l8 8z"/><circle cx="7.5" cy="8.5" r="1.4"/>',
        'estrella'  => '<path d="M12 3l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 15.4 7.2 17.9l.9-5.4L4.2 8.7l5.4-.8z"/>',
        'documento' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
        'marca'     => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'camion'    => '<path d="M3 7h11v8H3zM14 10h4l3 3v2h-7z"/><circle cx="7" cy="17" r="1.6"/><circle cx="17" cy="17" r="1.6"/>',
    ];
    $mmHelpTitle = trim((string) ($settings['mega_help_title'] ?? '¿Necesitas ayuda?'));
    $mmHelpUrl = trim((string) ($settings['mega_help_url'] ?? ''));
    if ($mmHelpUrl === '' && !empty($whatsapp)) $mmHelpUrl = 'https://wa.me/'.$whatsapp;
    $mmHelpUrl = $mmHelpUrl !== '' ? $mmUrl($mmHelpUrl) : '';
    $mmAllText = trim((string) ($settings['mega_all_text'] ?? '')) ?: 'Ver toda la categoría';
    $mmSide = $mmBrands->isNotEmpty() || count($mmQuick) || $mmHelpTitle !== '';
@endphp
@if($mmCats->isNotEmpty())
<style>
    .mmc{position:absolute;left:0;right:0;top:100%;z-index:70;background:#fff;border-top:1px solid var(--border,#e5e7eb);box-shadow:0 24px 48px rgba(15,23,42,.14)}
    .mmc-grid{display:grid;grid-template-columns:270px minmax(0,1fr) 250px;min-height:340px;width:min(1400px,100%);margin:0 auto}
    .mmc-grid.no-side{grid-template-columns:270px minmax(0,1fr)}
    .mmc-cats{display:flex;flex-direction:column;padding:12px 0;background:var(--surface-soft,#f8fafc);border-right:1px solid var(--border,#e5e7eb)}
    .mmc-cat{display:flex;align-items:center;gap:12px;padding:10px 16px 10px 18px;color:var(--text,#334155);font-size:13.5px;font-weight:600;text-decoration:none;border-left:3px solid transparent;border-radius:0 8px 8px 0;margin-right:10px}
    .mmc-cat-ico{width:24px;height:24px;flex:0 0 24px;display:grid;place-items:center;color:var(--muted,#64748b)}
    .mmc-cat-ico svg{width:20px;height:20px}
    .mmc-cat span:nth-child(2){flex:1;min-width:0;line-height:1.2}
    .mmc-cat > svg{width:14px;height:14px;color:var(--muted,#94a3b8)}
    .mmc-cat.is-on{color:var(--primary);background:color-mix(in srgb,var(--primary) 8%,#fff);border-left-color:var(--primary)}
    .mmc-cat.is-on .mmc-cat-ico{color:var(--primary)}
    .mmc-main{padding:22px 26px 20px;min-width:0}
    .mmc-eyebrow{display:block;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--primary)}
    .mmc-pane h4{margin:6px 0 4px;font-family:var(--font-title);font-size:22px;font-weight:800;line-height:1.2;color:var(--secondary,#0f172a)}
    .mmc-pane > p{margin:0 0 16px;max-width:560px;font-size:13.5px;line-height:1.5;color:var(--muted,#64748b)}
    .mmc-subs{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
    .mmc-subs.cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}
    .mmc-sub{display:flex;align-items:center;gap:12px;padding:10px 12px;border:1px solid var(--border,#e5e7eb);border-radius:10px;background:#fff;text-decoration:none;color:inherit;transition:border-color .15s ease,box-shadow .15s ease}
    .mmc-sub:hover{border-color:var(--primary);box-shadow:0 6px 16px rgba(15,23,42,.06)}
    .mmc-sub-img{flex:0 0 56px;width:56px;height:56px;display:grid;place-items:center;border-radius:8px;background:var(--surface-soft,#f8fafc);overflow:hidden;color:var(--muted,#94a3b8)}
    .mmc-sub-img img{width:100%;height:100%;object-fit:contain;mix-blend-mode:multiply}
    .mmc-sub-img svg{width:24px;height:24px}
    .mmc-sub > span:last-child{min-width:0}
    .mmc-sub strong{display:block;font-size:13.5px;font-weight:700;line-height:1.25;color:var(--text-strong,#111827)}
    .mmc-sub small{display:block;margin-top:2px;font-size:11.5px;line-height:1.35;color:var(--muted,#64748b)}
    .mmc-all{display:inline-flex;align-items:center;gap:8px;margin-top:16px;padding:10px 16px;border-radius:8px;background:var(--primary);color:#fff;font-size:13px;font-weight:700;text-decoration:none}
    .mmc-all svg{width:15px;height:15px}
    .mmc-side{display:flex;flex-direction:column;gap:16px;padding:20px 18px;border-left:1px solid var(--border,#e5e7eb);background:var(--surface-soft,#f8fafc)}
    .mmc-block-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px}
    .mmc-block h5{margin:0;font-size:14px;font-weight:800;color:var(--secondary,#0f172a)}
    .mmc-block-head a{font-size:12px;font-weight:700;color:var(--primary);text-decoration:none}
    .mmc-brands{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}
    .mmc-brand{display:grid;place-items:center;height:48px;padding:6px;border:1px solid var(--border,#e5e7eb);border-radius:8px;background:#fff;text-decoration:none;color:var(--secondary,#0f172a);font-family:var(--font-title);font-size:10.5px;font-weight:900;text-transform:uppercase;letter-spacing:0;text-align:center;overflow:hidden;line-height:1.1;white-space:nowrap}
    .mmc-brand img{max-width:100%;max-height:34px;object-fit:contain}
    .mmc-brand:hover{border-color:var(--primary)}
    .mmc-quick{list-style:none;margin:0;padding:0;display:grid;gap:6px}
    .mmc-quick a{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;color:var(--text,#334155);font-size:13px;font-weight:600;text-decoration:none}
    .mmc-quick a:hover{background:#fff;color:var(--primary)}
    .mmc-quick svg{width:18px;height:18px;color:var(--muted,#64748b)}
    .mmc-quick a:hover svg{color:var(--primary)}
    .mmc-quick a > svg:last-child{margin-left:auto;width:14px;height:14px}
    .mmc-help{margin-top:auto;padding:14px;border-radius:10px;background:color-mix(in srgb,var(--primary) 8%,#fff);border:1px solid color-mix(in srgb,var(--primary) 24%,#fff)}
    .mmc-help-head{display:flex;gap:10px;align-items:flex-start}
    .mmc-help-ico{flex:0 0 30px;width:30px;height:30px;display:grid;place-items:center;border-radius:50%;background:var(--primary);color:#fff}
    .mmc-help-ico svg{width:16px;height:16px}
    .mmc-help strong{display:block;font-size:13.5px;font-weight:800;color:var(--primary)}
    .mmc-help small{display:block;margin-top:2px;font-size:12px;line-height:1.4;color:var(--muted,#64748b)}
    .mmc-help-btn{display:flex;align-items:center;justify-content:center;gap:8px;margin-top:12px;padding:10px 12px;border-radius:8px;background:var(--primary);color:#fff;font-size:13px;font-weight:700;text-decoration:none}
    .mmc-help-btn svg{width:14px;height:14px}
    @media(max-width:1200px){.mmc-grid{grid-template-columns:220px minmax(0,1fr) 220px}.mmc-brands{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:1024px){.mmc{display:none!important}}
</style>
<div class="mmc" x-show="catsOpen" x-cloak x-transition.opacity.duration.150ms x-data="{ mmCat: {{ (int) $mmCats->first()->id }} }" @mouseleave="catsOpen=false" role="region" aria-label="Categorías del catálogo">
    <div class="mmc-grid{{ $mmSide ? '' : ' no-side' }}">
        <nav class="mmc-cats" aria-label="Rubros">
            @foreach($mmCats as $cat)
            @php $mmIco = filled($settings['caticon_'.$cat->id] ?? null) ? $settings['caticon_'.$cat->id] : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($categoryIconPaths[$autoCategoryIcon($cat->name)] ?? $categoryIconPaths['default']).'</svg>'; @endphp
            <a class="mmc-cat" :class="mmCat==={{ $cat->id }} && 'is-on'" href="{{ \App\Modules\Tienda\Support\StorefrontNavigation::categoryUrl($project, $cat->id) }}" @mouseenter="mmCat={{ $cat->id }}" @focus="mmCat={{ $cat->id }}">
                <span class="mmc-cat-ico">{!! $mmIco !!}</span>
                <span>{{ $cat->name }}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
            </a>
            @endforeach
        </nav>
        <div class="mmc-main">
            @foreach($mmCats as $cat)
            @php
                $mmSubs = $cat->relationLoaded('children') ? $cat->children->where('is_active', true)->values() : collect();
                $mmTitle = trim((string) ($settings['megacat_'.$cat->id.'_title'] ?? '')) ?: $cat->name;
                $mmDesc = trim((string) ($settings['megacat_'.$cat->id.'_desc'] ?? ''));
            @endphp
            <div class="mmc-pane" x-show="mmCat==={{ $cat->id }}" @if(!$loop->first) x-cloak @endif>
                <span class="mmc-eyebrow">{{ $cat->name }}</span>
                <h4>{{ $mmTitle }}</h4>
                @if($mmDesc !== '')<p>{{ $mmDesc }}</p>@endif
                @if($mmSubs->isNotEmpty())
                <div class="mmc-subs{{ $mmSubs->count() > 8 ? ' cols-3' : '' }}">
                    @foreach($mmSubs->take(12) as $sub)
                    @php $mmSubFoto = $sub->image_url ?: ($sub->relationLoaded('products') ? $sub->products->first(fn ($pr) => filled($pr->main_image_url))?->main_image_url : null); $mmSubImg = $mmSubFoto ? \App\Support\Imagen\Img::deAncho($assetUrl($mmSubFoto), 200) : null; $mmSubDesc = trim((string) ($settings['megacat_'.$sub->id.'_desc'] ?? '')); @endphp
                    <a class="mmc-sub" href="{{ \App\Modules\Tienda\Support\StorefrontNavigation::categoryUrl($project, $sub->id) }}">
                        <span class="mmc-sub-img">@if($mmSubImg)<img src="{{ $mmSubImg }}" alt="" loading="lazy">@else<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $categoryIconPaths[$autoCategoryIcon($sub->name)] ?? $categoryIconPaths['default'] !!}</svg>@endif</span>
                        <span><strong>{{ $sub->name }}</strong>@if($mmSubDesc !== '')<small>{{ $mmSubDesc }}</small>@endif</span>
                    </a>
                    @endforeach
                </div>
                @endif
                <a class="mmc-all" href="{{ \App\Modules\Tienda\Support\StorefrontNavigation::categoryUrl($project, $cat->id) }}">{{ $mmAllText }} <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
            </div>
            @endforeach
        </div>
        @if($mmSide)
        <aside class="mmc-side">
            @if($mmBrands->isNotEmpty())
            <div class="mmc-block">
                <div class="mmc-block-head"><h5>{{ trim((string) ($settings['mega_brands_title'] ?? '')) ?: 'Marcas destacadas' }}</h5><a href="{{ rtrim($mmBase, '/') }}/marcas">Ver todas →</a></div>
                <div class="mmc-brands">
                    @foreach($mmBrands as $b)
                    @php $bImg = data_get($b, 'image_url') ? $assetUrl(data_get($b, 'image_url')) : null; @endphp
                    <a class="mmc-brand" href="{{ $mmShop }}?brand[]={{ data_get($b, 'id') }}" title="{{ data_get($b, 'label') }}" @if(!$bImg && mb_strlen((string) data_get($b, 'label')) >= 8) style="font-size:{{ mb_strlen((string) data_get($b, 'label')) >= 11 ? '8px' : '9px' }}" @endif>@if($bImg)<img src="{{ $bImg }}" alt="{{ data_get($b, 'label') }}" loading="lazy">@else{{ data_get($b, 'label') }}@endif</a>
                    @endforeach
                </div>
            </div>
            @endif
            @if(count($mmQuick))
            <div class="mmc-block">
                <div class="mmc-block-head"><h5>{{ trim((string) ($settings['mega_quick_title'] ?? '')) ?: 'Accesos rápidos' }}</h5></div>
                <ul class="mmc-quick">
                    @foreach($mmQuick as $q)
                    <li><a href="{{ $q['u'] }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $mmQuickIcons[$q['i']] ?? $mmQuickIcons['estrella'] !!}</svg>{{ $q['l'] }}<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></a></li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if($mmHelpTitle !== '')
            <div class="mmc-help">
                <div class="mmc-help-head">
                    <span class="mmc-help-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/></svg></span>
                    <span><strong>{{ $mmHelpTitle }}</strong>@if(filled($settings['mega_help_text'] ?? null))<small>{{ $settings['mega_help_text'] }}</small>@endif</span>
                </div>
                @if($mmHelpUrl !== '')
                <a class="mmc-help-btn" href="{{ $mmHelpUrl }}" @if(str_starts_with($mmHelpUrl, 'http')) target="_blank" rel="noopener" @endif>{{ trim((string) ($settings['mega_help_button'] ?? '')) ?: 'Solicita una cotización' }} <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                @endif
            </div>
            @endif
        </aside>
        @endif
    </div>
</div>
@endif
