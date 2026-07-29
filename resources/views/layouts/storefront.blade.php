@php
    $storefrontTheme = $storefrontTheme ?? \App\Support\StorefrontTheme::resolve($settings ?? []);
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $project->name)</title>
    <meta name="description" content="@yield('description', $settings['seo_description'] ?? $project->description ?? '')">
    @if(!empty($settings['favicon_url']))<link rel="icon" href="{{ str_starts_with($settings['favicon_url'],'http') ? $settings['favicon_url'] : asset('storage/'.$settings['favicon_url']) }}">@endif
    <style>
        :root{--store-primary:{{ $settings['primary_color'] ?? '#2563eb' }};--store-secondary:{{ $settings['secondary_color'] ?? '#0f172a' }};--store-font-body:'{{ $settings['font_body'] ?? 'Inter' }}',system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;--store-font-title:'{{ $settings['font_title'] ?? $settings['font_body'] ?? 'Inter' }}',system-ui,sans-serif;--store-radius:{{ (int)($settings['border_radius'] ?? 10) }}px;color-scheme:light}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;color:#172033;background:#fff;font-family:var(--store-font-body);font-size:16px;line-height:1.5}body.store-menu-open{overflow:hidden}a{color:inherit}button,input,select,textarea{font:inherit}img{max-width:100%}.store-main{min-height:55vh}.store-container{width:min(1180px,calc(100% - 40px));margin-inline:auto}.store-skip{position:fixed;z-index:9999;top:8px;left:8px;transform:translateY(-150%);padding:10px 14px;border-radius:8px;background:#0f172a;color:#fff}.store-skip:focus{transform:none}.store-preview-bar{position:relative;z-index:120;padding:9px 18px;background:#fef3c7;color:#92400e;text-align:center;font-size:13px;font-weight:700}.store-alert{margin:18px auto;padding:13px 16px;border-radius:9px;background:#ecfdf5;color:#047857}.store-page-shell{padding:clamp(42px,7vw,84px) 0;background:#f8fafc}.store-page-card{padding:clamp(24px,5vw,58px);border:1px solid #e2e8f0;border-radius:14px;background:#fff}.store-page-card h1{margin:0;color:#0f172a;font:800 clamp(32px,5vw,54px)/1.08 var(--store-font-title);letter-spacing:-.035em}.store-page-card h2{margin:34px 0 10px;color:#0f172a;font:750 clamp(21px,3vw,28px)/1.2 var(--store-font-title)}.store-page-card p{color:#526176;line-height:1.75}.store-button{display:inline-flex;min-height:44px;align-items:center;justify-content:center;padding:11px 17px;border:0;border-radius:8px;background:var(--store-primary);color:#fff;font-weight:750;text-decoration:none;cursor:pointer}.store-button:hover{filter:brightness(.92)}:focus-visible{outline:3px solid color-mix(in srgb,var(--store-primary) 35%,white);outline-offset:3px}[x-cloak]{display:none!important}@media(max-width:767px){.store-container{width:min(100% - 28px,1180px)}.store-page-shell{padding:34px 0}.store-page-card{padding:22px 18px;border-radius:10px}}@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}*,*:before,*:after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}}
    </style>
    <style>html,body{max-width:100%;overflow-x:hidden}.store-header-inner>*{min-width:0}.sf-hero h1,.sf-hero p,.shop-card h2{overflow-wrap:anywhere}</style>
    @stack('styles')
    @include('components.storefront.theme-styles', ['theme' => $storefrontTheme])
</head>
<body class="storefront-theme storefront-theme-{{ $storefrontTheme['key'] }} storefront-family-{{ $storefrontTheme['family'] }} storefront-card-{{ $storefrontTheme['card_style'] }}" data-store-template="{{ $storefrontTheme['key'] }}" data-store-theme-family="{{ $storefrontTheme['family'] }}">
    <a class="store-skip" href="#contenido-principal">Saltar al contenido</a>
    @if(!empty($previewMode))<div class="store-preview-bar">Vista previa privada: estos cambios todavía no están activos públicamente.</div>@endif
    @include('components.storefront.header')
    <main id="contenido-principal" class="store-main">@yield('content')</main>
    @include('components.storefront.footer')
    @if(isset($popup) && $popup)@include('components.public-popup', ['popup'=>$popup])@endif
    @stack('scripts')
</body>
</html>
