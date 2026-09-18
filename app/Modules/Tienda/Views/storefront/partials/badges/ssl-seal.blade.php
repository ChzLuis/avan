{{-- SELLO DE COMPRA SEGURA (SSL) — vectorial, reutilizable en pie y checkout.
     Se dibuja con SVG para que escale nitido en cualquier tamano y no dependa
     de una imagen subida. Los IDs llevan sufijo unico por si aparece dos veces
     en la misma pagina (pie + checkout). --}}
@php
    $sealId  = 'sslseal-'.substr(md5(uniqid('', true)), 0, 6);
    $sealTop = $sealTop ?? '100% SECURE TRANSACTIONS';
    $sealMid = $sealMid ?? 'SSL';
    $sealPx  = (int) ($sealPx ?? 52);
@endphp
@if(($sealStyle ?? 'gold') === 'https')
<svg class="ssl-seal" viewBox="0 0 200 200" width="{{ $sealPx }}" height="{{ $sealPx }}"
     role="img" aria-label="Conexion segura HTTPS">
    <defs>
        <linearGradient id="{{ $sealId }}-h" x1="0" y1="0" x2="0.4" y2="1">
            <stop offset="0%" stop-color="#4AE68F"/><stop offset="55%" stop-color="#17A85C"/>
            <stop offset="100%" stop-color="#0B6E3C"/>
        </linearGradient>
    </defs>
    <circle cx="100" cy="100" r="97" fill="url(#{{ $sealId }}-h)"/>
    <circle cx="100" cy="100" r="88" fill="#0C2B1C"/>
    <circle cx="100" cy="100" r="82" fill="none" stroke="#4AE68F" stroke-width="3" stroke-dasharray="6 5" opacity=".55"/>
    {{-- Escudo con candado: ocupa la mitad superior para dejar sitio al rotulo. --}}
    <path d="M100 26 154 46v40c0 28-21 47-54 57-33-10-54-29-54-57V46Z" fill="url(#{{ $sealId }}-h)"/>
    <g fill="#0C2B1C">
        <rect x="80" y="72" width="40" height="32" rx="6"/>
        <path d="M87 72V62a13 13 0 0 1 26 0v10h-9V62a4 4 0 0 0-8 0v10Z"/>
    </g>
    <circle cx="100" cy="86" r="5" fill="#4AE68F"/>
    <rect x="93" y="88" width="14" height="9" rx="3" fill="#4AE68F"/>
    <text x="100" y="172" text-anchor="middle" fill="#4AE68F"
          font-family="Arial Black,Arial,Helvetica,sans-serif" font-size="38" font-weight="900"
          letter-spacing="1.5">HTTPS</text>
</svg>
@else
<svg class="ssl-seal" viewBox="0 0 200 200" width="{{ $sealPx }}" height="{{ $sealPx }}"
     role="img" aria-label="{{ $sealTop }}">
    <defs>
        <linearGradient id="{{ $sealId }}-g" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#FBE9A0"/><stop offset="28%" stop-color="#E8B923"/>
            <stop offset="52%" stop-color="#FDF3C0"/><stop offset="74%" stop-color="#D4A017"/>
            <stop offset="100%" stop-color="#B8860B"/>
        </linearGradient>
        <linearGradient id="{{ $sealId }}-c" x1="0" y1="0" x2="0.6" y2="1">
            <stop offset="0%" stop-color="#FDF0B4"/><stop offset="45%" stop-color="#EFC33B"/>
            <stop offset="100%" stop-color="#C99A16"/>
        </linearGradient>
        <path id="{{ $sealId }}-top" d="M100,100 m-70,0 a70,70 0 0 1 140,0" fill="none"/>
        <path id="{{ $sealId }}-bot" d="M100,100 m-70,0 a70,70 0 0 0 140,0" fill="none"/>
    </defs>
    <path d="M100.00,3.00 L105.31,12.16 L111.69,3.71 L115.86,13.44 L123.21,5.82 L126.18,15.98 L134.40,9.30 L136.12,19.75 L145.08,14.11 L145.53,24.69 L155.10,20.17 L154.27,30.73 L164.32,27.39 L162.23,37.77 L172.61,35.68 L169.27,45.73 L179.83,44.90 L175.31,54.47 L185.89,54.92 L180.25,63.88 L190.70,65.60 L184.02,73.82 L194.18,76.79 L186.56,84.14 L196.29,88.31 L187.84,94.69 L197.00,100.00 L187.84,105.31 L196.29,111.69 L186.56,115.86 L194.18,123.21 L184.02,126.18 L190.70,134.40 L180.25,136.12 L185.89,145.08 L175.31,145.53 L179.83,155.10 L169.27,154.27 L172.61,164.32 L162.23,162.23 L164.32,172.61 L154.27,169.27 L155.10,179.83 L145.53,175.31 L145.08,185.89 L136.12,180.25 L134.40,190.70 L126.18,184.02 L123.21,194.18 L115.86,186.56 L111.69,196.29 L105.31,187.84 L100.00,197.00 L94.69,187.84 L88.31,196.29 L84.14,186.56 L76.79,194.18 L73.82,184.02 L65.60,190.70 L63.88,180.25 L54.92,185.89 L54.47,175.31 L44.90,179.83 L45.73,169.27 L35.68,172.61 L37.77,162.23 L27.39,164.32 L30.73,154.27 L20.17,155.10 L24.69,145.53 L14.11,145.08 L19.75,136.12 L9.30,134.40 L15.98,126.18 L5.82,123.21 L13.44,115.86 L3.71,111.69 L12.16,105.31 L3.00,100.00 L12.16,94.69 L3.71,88.31 L13.44,84.14 L5.82,76.79 L15.98,73.82 L9.30,65.60 L19.75,63.88 L14.11,54.92 L24.69,54.47 L20.17,44.90 L30.73,45.73 L27.39,35.68 L37.77,37.77 L35.68,27.39 L45.73,30.73 L44.90,20.17 L54.47,24.69 L54.92,14.11 L63.88,19.75 L65.60,9.30 L73.82,15.98 L76.79,5.82 L84.14,13.44 L88.31,3.71 L94.69,12.16 Z" fill="url(#{{ $sealId }}-g)"/>
    <circle cx="100" cy="100" r="84" fill="#3A3A3A"/>
    <circle cx="100" cy="100" r="82" fill="none" stroke="url(#{{ $sealId }}-g)" stroke-width="2"/>
    <circle cx="100" cy="100" r="59" fill="url(#{{ $sealId }}-c)"/>
    <g fill="#F2D375" font-family="Arial,Helvetica,sans-serif" font-size="13.5" font-weight="bold" letter-spacing="1.6">
        <text><textPath href="#{{ $sealId }}-top" startOffset="50%" text-anchor="middle">{{ $sealTop }}</textPath></text>
        <text><textPath href="#{{ $sealId }}-bot" startOffset="50%" text-anchor="middle">{{ $sealTop }}</textPath></text>
    </g>
    <g fill="#4A4A4A"><polygon points="78.00,45.60 79.69,49.67 84.09,50.02 80.74,52.89 81.76,57.18 78.00,54.88 74.24,57.18 75.26,52.89 71.91,50.02 76.31,49.67"/><polygon points="100.00,45.60 101.69,49.67 106.09,50.02 102.74,52.89 103.76,57.18 100.00,54.88 96.24,57.18 97.26,52.89 93.91,50.02 98.31,49.67"/><polygon points="122.00,45.60 123.69,49.67 128.09,50.02 124.74,52.89 125.76,57.18 122.00,54.88 118.24,57.18 119.26,52.89 115.91,50.02 120.31,49.67"/><polygon points="78.00,143.60 79.69,147.67 84.09,148.02 80.74,150.89 81.76,155.18 78.00,152.88 74.24,155.18 75.26,150.89 71.91,148.02 76.31,147.67"/><polygon points="100.00,143.60 101.69,147.67 106.09,148.02 102.74,150.89 103.76,155.18 100.00,152.88 96.24,155.18 97.26,150.89 93.91,148.02 98.31,147.67"/><polygon points="122.00,143.60 123.69,147.67 128.09,148.02 124.74,150.89 125.76,155.18 122.00,152.88 118.24,155.18 119.26,150.89 115.91,148.02 120.31,147.67"/><polygon points="30.00,94.80 31.38,98.11 34.95,98.39 32.23,100.72 33.06,104.21 30.00,102.34 26.94,104.21 27.77,100.72 25.05,98.39 28.62,98.11"/><polygon points="170.00,94.80 171.38,98.11 174.95,98.39 172.23,100.72 173.06,104.21 170.00,102.34 166.94,104.21 167.77,100.72 165.05,98.39 168.62,98.11"/></g>
    <text x="100" y="118" text-anchor="middle" fill="#4A4A4A"
          font-family="Arial Black,Arial,Helvetica,sans-serif" font-size="46" font-weight="900"
          letter-spacing="1">{{ $sealMid }}</text>
</svg>
@endif
