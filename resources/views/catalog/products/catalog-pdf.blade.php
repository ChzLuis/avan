{{-- Catálogo imprimible: se abre en pestaña nueva y se guarda como PDF con el
     diálogo del navegador (mismo patrón que las facturas). CSS inline a
     propósito: no depende del build de Tailwind ni de assets compilados. --}}
@php
    $currency = $settings['currency_symbol'] ?? $settings['currency'] ?? 'S/';
    $fmt      = fn ($n) => $currency . ' ' . number_format((float) $n, 2);

    $assetUrl = function ($path) {
        if (blank($path)) return null;
        return str_starts_with($path, 'http') ? $path : asset('storage/' . ltrim($path, '/'));
    };

    // Identidad: si se exporta por perfil y el perfil tiene logo/color propios, mandan.
    $accent  = $profile?->primary_color ?: ($settings['primary_color'] ?? '#4f46e5');
    $logoUrl = $assetUrl($profile?->logo_path ?: ($settings['logo_url'] ?? $project->logo_url));
    $heroUrl = $assetUrl($profile?->hero_desktop_path ?: ($settings['hero_image'] ?? null));

    $scopeLabel = $profile?->name ?? $category?->name ?? null;
    $total      = $groups->flatten(1)->count();
    $wa         = preg_replace('/\D/', '', (string) ($settings['whatsapp_number'] ?? $project->whatsapp ?? ''));
    $storeDomain = filled($project->custom_domain) ? preg_replace('#^https?://#', '', $project->custom_domain) : null;

    // Densidad de la grilla; el modo "detallado" (2 col) es el único con descripción.
    $cols     = ['grid2' => 2, 'grid3' => 3, 'grid4' => 4][$layout] ?? 3;
    $showDesc = $layout === 'grid2';

    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?margin=0&size=190x190&data=' . urlencode($storeUrl);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Catálogo — {{ $project->name }}{{ $scopeLabel ? ' · ' . $scopeLabel : '' }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', system-ui, Arial, Helvetica, sans-serif; color: #111827; background: #eef2f6; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .sheet { max-width: 880px; margin: 0 auto; background: #fff; padding: 34px 38px; }

    .toolbar { position: sticky; top: 0; z-index: 10; background: #0f172a; color: #fff; padding: 11px 18px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
    .toolbar p { font-size: 12px; opacity: .75; }
    .toolbar button { background: {{ $accent }}; color: #fff; border: none; border-radius: 9px; padding: 10px 20px; font-size: 13px; font-weight: 700; cursor: pointer; }
    .toolbar button:hover { filter: brightness(1.08); }

    /* ── Portada ── */
    .cover { min-height: 900px; display: flex; flex-direction: column; page-break-after: always; break-after: page; }
    .cover-band { height: 9px; background: {{ $accent }}; border-radius: 6px; }
    .cover-hero { margin-top: 22px; height: 250px; border-radius: 14px; overflow: hidden; background: #f1f5f9; }
    .cover-hero img { width: 100%; height: 100%; object-fit: cover; }
    .cover-main { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 34px 0 22px; }
    .cover-logo { max-height: 76px; max-width: 250px; object-fit: contain; margin-bottom: 20px; }
    .cover-biz { font-size: 33px; font-weight: 800; letter-spacing: -.6px; line-height: 1.15; }
    .cover-word { font-size: 15px; font-weight: 800; letter-spacing: 6px; color: {{ $accent }}; margin-top: 10px; }
    .cover-scope { display: inline-block; margin-top: 16px; padding: 7px 20px; border-radius: 30px; background: {{ $accent }}; color: #fff; font-size: 14px; font-weight: 700; }
    .cover-meta { font-size: 12.5px; color: #6b7280; margin-top: 14px; }

    .index { margin-top: 26px; padding-top: 18px; border-top: 1px solid #e5e7eb; }
    .index h3 { font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px; color: #9ca3af; margin-bottom: 10px; }
    .index ul { list-style: none; column-count: 2; column-gap: 34px; }
    .index li { display: flex; justify-content: space-between; gap: 8px; font-size: 12.5px; padding: 4px 0; border-bottom: 1px dotted #e5e7eb; break-inside: avoid; }
    .index li b { font-weight: 600; }
    .index li span { color: #9ca3af; }

    .cover-foot { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; padding-top: 20px; border-top: 2px solid {{ $accent }}; }
    .cover-contact { font-size: 12.5px; color: #4b5563; line-height: 1.75; }
    .cover-contact strong { color: #111827; }
    .qr { text-align: center; }
    .qr img { width: 96px; height: 96px; border: 1px solid #e5e7eb; border-radius: 8px; padding: 4px; background: #fff; }
    .qr span { display: block; font-size: 9.5px; color: #9ca3af; margin-top: 4px; }

    /* ── Encabezado de páginas interiores ── */
    .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding-bottom: 15px; margin-bottom: 20px; border-bottom: 3px solid {{ $accent }}; }
    .head img.logo { max-height: 50px; max-width: 185px; object-fit: contain; }
    .head .biz { font-size: 19px; font-weight: 800; }
    .head .meta { font-size: 11px; color: #6b7280; margin-top: 2px; }
    .head .right { text-align: right; }
    .head .title { font-size: 20px; font-weight: 800; color: {{ $accent }}; letter-spacing: .5px; }
    .head .scope { display: inline-block; margin-top: 5px; padding: 3px 12px; border-radius: 20px; background: {{ $accent }}18; color: {{ $accent }}; font-size: 12px; font-weight: 700; }

    .section-title { display: flex; justify-content: space-between; align-items: center; gap: 10px; background: {{ $accent }}; color: #fff; font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: .6px; padding: 9px 15px; border-radius: 9px; margin: 22px 0 13px; break-after: avoid; page-break-after: avoid; }
    .section-title em { font-style: normal; font-size: 11px; font-weight: 600; opacity: .85; }

    /* ── Tarjetas ── */
    .grid { display: grid; grid-template-columns: repeat({{ $cols }}, 1fr); gap: {{ $cols >= 4 ? 10 : 13 }}px; }
    .card { position: relative; border: 1px solid #e5e7eb; border-radius: 11px; overflow: hidden; break-inside: avoid; page-break-inside: avoid; background: #fff; }
    .card .ph { position: relative; width: 100%; aspect-ratio: 1/1; background: #f8fafc; display: flex; align-items: center; justify-content: center; border-bottom: 1px solid #f3f4f6; }
    .card .ph img { width: 100%; height: 100%; object-fit: contain; }
    .card .ph .noimg { font-size: 10px; color: #cbd5e1; }
    .off { position: absolute; top: 7px; left: 7px; background: #dc2626; color: #fff; font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 20px; }
    .card .body { padding: {{ $cols >= 4 ? '7px 9px 9px' : '9px 11px 11px' }}; }
    .card .name { font-size: {{ $cols >= 4 ? '11px' : '12.5px' }}; font-weight: 700; line-height: 1.3; min-height: 2.6em; }
    .card .desc { font-size: 10.5px; color: #6b7280; line-height: 1.45; margin-top: 4px; }
    .card .sku { font-size: 9.5px; color: #9ca3af; margin-top: 3px; font-family: 'Courier New', monospace; }
    .card .price { font-size: {{ $cols >= 4 ? '13px' : '15px' }}; font-weight: 800; color: {{ $accent }}; margin-top: 6px; }
    .card .compare { font-size: 10.5px; color: #9ca3af; text-decoration: line-through; margin-left: 5px; font-weight: 500; }
    .card .whnote { font-size: 9.5px; color: #6b7280; margin-top: 1px; }

    /* ── Cierre ── */
    .cta { margin-top: 30px; border: 2px solid {{ $accent }}; border-radius: 14px; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; gap: 22px; break-inside: avoid; page-break-inside: avoid; }
    .cta h4 { font-size: 17px; font-weight: 800; }
    .cta p { font-size: 12.5px; color: #4b5563; margin-top: 5px; line-height: 1.7; }
    .cta .wa { display: inline-block; margin-top: 10px; background: #25d366; color: #fff; font-size: 13px; font-weight: 700; padding: 9px 18px; border-radius: 9px; text-decoration: none; }

    .foot { margin-top: 22px; padding-top: 11px; border-top: 1px dashed #d1d5db; display: flex; justify-content: space-between; gap: 10px; font-size: 10.5px; color: #6b7280; }
    .credit { text-align: center; font-size: 9.5px; color: #b6bec9; margin-top: 13px; }
    .credit a { color: #9ca3af; text-decoration: none; }

    .empty { text-align: center; color: #9ca3af; padding: 60px 0; font-size: 14px; }

    @media print {
        body { background: #fff; }
        .toolbar { display: none !important; }
        /* Margen de página 0: el navegador imprime su URL/fecha EN el margen,
           así que sin margen desaparecen. El respiro lo da el padding propio. */
        .sheet { max-width: none; padding: 12mm; }
        @page { size: A4; margin: 0; }
        .cover { min-height: 247mm; }
    }

    @media (max-width: 640px) {
        .sheet { padding: 18px 14px; }
        .grid { grid-template-columns: repeat(2, 1fr); }
        .cover { min-height: 0; }
        .cover-biz { font-size: 26px; }
        .index ul { column-count: 1; }
        .cover-foot, .cta, .head, .foot { flex-direction: column; align-items: flex-start; }
        .head .right { text-align: left; }
    }
</style>
</head>
<body>

<div class="toolbar">
    <p>Vista previa — pulsa el botón y elige <strong>"Guardar como PDF"</strong> para descargarlo, o imprímelo directo.</p>
    <button onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
</div>

<div class="sheet">

    @if($cover)
    {{-- ══ PORTADA ══ --}}
    <div class="cover">
        <div class="cover-band"></div>
        @if($heroUrl)<div class="cover-hero"><img src="{{ $heroUrl }}" alt=""></div>@endif

        <div class="cover-main">
            @if($logoUrl)<img class="cover-logo" src="{{ $logoUrl }}" alt="{{ $project->name }}">@endif
            <div class="cover-biz">{{ $project->name }}</div>
            <div class="cover-word">CATÁLOGO DE PRODUCTOS</div>
            @if($scopeLabel)<div><span class="cover-scope">{{ $scopeLabel }}</span></div>@endif
            <div class="cover-meta">
                {{ now()->translatedFormat('F Y') }} · {{ $total }} producto{{ $total === 1 ? '' : 's' }}
                @if($groups->count() > 1) · {{ $groups->count() }} categorías @endif
            </div>

            @if($groups->count() > 1)
            <div class="index">
                <h3>Contenido</h3>
                <ul>
                    @foreach($groups as $gName => $gItems)
                    <li><b>{{ $gName }}</b> <span>{{ $gItems->count() }}</span></li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        <div class="cover-foot">
            <div class="cover-contact">
                @if($storeDomain)<div>🌐 <strong>{{ $storeDomain }}</strong></div>@endif
                @if($wa)<div>💬 WhatsApp +{{ $wa }}</div>@endif
                @if($project->phone)<div>📞 {{ $project->phone }}</div>@endif
                @if($project->address)<div>📍 {{ $project->address }}</div>@endif
            </div>
            <div class="qr">
                <img src="{{ $qrUrl }}" alt="QR de la tienda">
                <span>Escanea y compra</span>
            </div>
        </div>
    </div>
    @endif

    {{-- ══ ENCABEZADO DE CONTENIDO ══ --}}
    <div class="head">
        <div>
            @if($logoUrl && !$cover)<img class="logo" src="{{ $logoUrl }}" alt="{{ $project->name }}">@endif
            <div class="biz">{{ $project->name }}</div>
            <div class="meta">
                @if($project->phone) Tel: {{ $project->phone }} @endif
                @if($wa) · WhatsApp: +{{ $wa }} @endif
            </div>
            @if($storeDomain)<div class="meta">{{ $storeDomain }}</div>@endif
        </div>
        <div class="right">
            <div class="title">CATÁLOGO</div>
            @if($scopeLabel)<div class="scope">{{ $scopeLabel }}</div>@endif
            <div class="meta" style="margin-top:6px">{{ now()->translatedFormat('d \d\e F, Y') }} · {{ $total }} producto{{ $total === 1 ? '' : 's' }}</div>
        </div>
    </div>

    @forelse($groups as $groupName => $items)
        <div class="section-title">
            <span>{{ $groupName }}</span>
            <em>{{ $items->count() }} producto{{ $items->count() === 1 ? '' : 's' }}</em>
        </div>
        <div class="grid">
            @foreach($items as $p)
            @php $onSale = $prices === 'retail' && $p->compare_price && $p->compare_price > $p->price; @endphp
            <div class="card">
                <div class="ph">
                    @if($onSale)
                    <span class="off">-{{ (int) round((1 - $p->price / $p->compare_price) * 100) }}%</span>
                    @endif
                    @if($p->main_image_url)
                        <img src="{{ $p->main_image_url }}" alt="{{ $p->name }}">
                    @else
                        <span class="noimg">Sin foto</span>
                    @endif
                </div>
                <div class="body">
                    <div class="name">{{ $p->name }}</div>

                    @if($showDesc && filled($p->description))
                    <div class="desc">{{ \Illuminate\Support\Str::limit(strip_tags($p->description), 110) }}</div>
                    @endif

                    @if($p->sku)<div class="sku">{{ $p->sku }}</div>@endif

                    @if($prices === 'retail')
                        <div class="price">{{ $fmt($p->price) }}@if($onSale)<span class="compare">{{ $fmt($p->compare_price) }}</span>@endif</div>
                    @elseif($prices === 'wholesale')
                        @if(filled($p->wholesale_price))
                            <div class="price">{{ $fmt($p->wholesale_price) }}</div>
                            <div class="whnote">Por mayor · desde {{ max(1, (int) ($p->wholesale_min_qty ?? 1)) }} {{ (filled($p->wholesale_unit) && !is_numeric($p->wholesale_unit)) ? $p->wholesale_unit : 'unid.' }}</div>
                        @else
                            <div class="price">{{ $fmt($p->price) }}</div>
                        @endif
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    @empty
        <div class="empty">No hay productos disponibles para este filtro.</div>
    @endforelse

    {{-- ══ CIERRE: cómo comprar ══ --}}
    @if($total > 0)
    <div class="cta">
        <div>
            <h4>¿Te interesa algo de este catálogo?</h4>
            <p>
                Escríbenos y coordinamos tu pedido.
                @if($storeDomain) También puedes comprar en línea en <strong>{{ $storeDomain }}</strong>.@endif
            </p>
            @if($wa)
            <a class="wa" href="https://wa.me/{{ $wa }}?text={{ urlencode('Hola, vi su catálogo y quiero hacer un pedido.') }}">💬 Pedir por WhatsApp</a>
            @endif
        </div>
        <div class="qr">
            <img src="{{ $qrUrl }}" alt="QR de la tienda">
            <span>Escanea y compra</span>
        </div>
    </div>
    @endif

    <div class="foot">
        <span>
            {{ $project->name }}
            @if($prices !== 'none') — precios sujetos a cambio sin previo aviso. @endif
            @if($storeDomain) · {{ $storeDomain }}@endif
        </span>
        <span>Generado el {{ now()->format('d/m/Y H:i') }}</span>
    </div>

    {{-- Crédito discreto: cada catálogo compartido es publicidad orgánica de Eskala. --}}
    <div class="credit">Catálogo creado con <a href="https://eskalagroup.com">Eskala</a> · eskalagroup.com</div>
</div>

</body>
</html>
