@props([
    'name'      => 'BIXO',
    'by'        => 'by Eskala',
    'tagline'   => 'Tu WhatsApp,<br>atendido <em>24/7</em>.',
    'sub'       => 'Gestiona conversaciones, catálogo y ventas desde un solo panel.',
    'caps'      => ['Atención automática', 'Catálogo y pedidos', 'Un solo panel'],
    'accent'    => '#17B890',
    'ink'       => '#04231C',
    'title'     => 'Iniciar sesión',
    'desc'      => 'Ingresa tus credenciales para acceder a tu panel.',
    'pageTitle' => null,
])
@php
    $mark = '<svg class="bixo-mark" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
        .'<rect x="5" y="6" width="34" height="26" rx="9" fill="'.$accent.'"/>'
        .'<path d="M14.5 31.5 L24 31.5 L15 39 Z" fill="'.$accent.'"/>'
        .'<path d="M22 11.5 c1.5 5.4 3.5 7.4 8.9 8.9 c-5.4 1.5 -7.4 3.5 -8.9 8.9 c-1.5 -5.4 -3.5 -7.4 -8.9 -8.9 c5.4 -1.5 7.4 -3.5 8.9 -8.9 Z" fill="#fff"/>'
        .'</svg>';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? ($title.' — '.$name) }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}

        /* ============ BIXO AUTH — sistema autocontenido, tematizable por --brand ============ */
        .bixo-auth{
            --ink:#0A1826; --ink-2:#0C1D2B;
            --text:#0A1826; --muted:#5E6E75; --line:#E4EEEE; --field:#F4F9F9; --white:#fff;
            font-family:'Inter',system-ui,-apple-system,sans-serif;
            color:var(--text);
            min-height:100vh; min-height:100dvh;
            display:grid; grid-template-columns:1.05fr .95fr;
        }
        .bixo-display{font-family:'Plus Jakarta Sans','Inter',system-ui,sans-serif;}

        /* ---------- STAGE (lado marca) ---------- */
        .bixo-stage{
            position:relative; overflow:hidden; color:#EAF3F3;
            padding:3.25rem 3.5rem; display:flex; flex-direction:column;
            background:linear-gradient(160deg,#0A1826 0%,#0C1D2B 55%,#0A1622 100%);
        }
        .bixo-stage::before{
            content:""; position:absolute; inset:0; z-index:0; pointer-events:none;
            background:radial-gradient(760px 520px at 12% 118%, color-mix(in srgb, var(--brand) 16%, transparent), transparent 62%);
        }
        .bixo-stage .bixo-brand,.bixo-stage .bixo-hero,.bixo-stage .bixo-foot{position:relative; z-index:1;}
        .bixo-caps{display:flex; flex-wrap:wrap; gap:.8rem 1.6rem; margin-top:2.4rem;}
        .bixo-caps span{position:relative; font-size:.82rem; letter-spacing:.02em; color:rgba(234,243,243,.56);}
        .bixo-caps span:not(:last-child)::after{content:""; position:absolute; right:-.9rem; top:50%; width:4px; height:4px; border-radius:50%; background:color-mix(in srgb, var(--brand) 55%, transparent); transform:translateY(-50%);}

        /* ---------- Marca ---------- */
        .bixo-brand{display:flex; align-items:center; gap:.7rem;}
        .bixo-mark{width:44px; height:44px; flex-shrink:0; filter:drop-shadow(0 6px 16px color-mix(in srgb, var(--brand) 45%, transparent));}
        .bixo-word{display:flex; flex-direction:column; line-height:1;}
        .bixo-word b{font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:1.5rem; letter-spacing:.02em;}
        .bixo-word small{font-size:.72rem; font-weight:500; letter-spacing:.12em; text-transform:uppercase; opacity:.82; margin-top:.28rem;}
        .bixo-stage .bixo-word b{color:#fff;}
        .bixo-stage .bixo-word small{color:color-mix(in srgb, var(--brand) 60%, #EAF3F3);}

        /* ---------- Hero copy ---------- */
        .bixo-hero{margin:auto 0; max-width:30rem;}
        .bixo-h1{
            font-family:'Plus Jakarta Sans',sans-serif; font-weight:800;
            font-size:clamp(2rem,3.4vw,2.9rem); line-height:1.08; letter-spacing:-.01em;
            color:#fff; text-wrap:balance; margin:0 0 1rem;
        }
        .bixo-h1 em{font-style:normal; color:var(--brand);}
        .bixo-sub{font-size:1.02rem; line-height:1.6; color:rgba(234,243,243,.72); margin:0 0 2rem;}
        .bixo-foot{margin-top:2.5rem; font-size:.76rem; color:rgba(234,243,243,.5);}

        /* ---------- PANEL (lado formulario) ---------- */
        .bixo-panel{background:var(--white); display:flex; align-items:center; justify-content:center; padding:3rem 2rem;}
        .bixo-card{width:100%; max-width:25rem;}
        .bixo-mobilebrand{display:none; align-items:center; gap:.6rem; margin-bottom:2rem;}
        .bixo-head{margin-bottom:1.9rem;}
        .bixo-title{font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:1.55rem; letter-spacing:-.01em; margin:0;}
        .bixo-desc{font-size:.9rem; color:var(--muted); margin:.35rem 0 0;}

        /* ---------- Selector de opción (tarjetas radio) ---------- */
        .bixo-back{display:inline-flex; align-items:center; gap:.35rem; font-size:.85rem; color:var(--muted); background:none; border:0; cursor:pointer; padding:0; margin-bottom:1rem;}
        .bixo-back:hover{color:var(--text);}
        .bixo-choice{display:flex; align-items:center; gap:.75rem; padding:.8rem 1rem; border:1.5px solid var(--line); border-radius:12px; cursor:pointer; transition:border-color .15s, background .15s;}
        .bixo-choice:hover{border-color:#CBDBDB;}
        .bixo-choice.is-sel{border-color:var(--brand); background:color-mix(in srgb, var(--brand) 8%, #fff);}
        .bixo-choice-ic{width:36px; height:36px; border-radius:9px; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:color-mix(in srgb, var(--brand) 14%, #fff); color:var(--brand);}
        .bixo-choice-name{font-size:.9rem; font-weight:600; color:var(--text); margin:0;}
        .bixo-choice-sub{font-size:.78rem; color:var(--muted); margin:0;}
        .bixo-choice-check{margin-left:auto; width:20px; height:20px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:var(--brand); color:#fff;}
        .bixo-sr{position:absolute; width:1px; height:1px; opacity:0; pointer-events:none;}

        /* ---------- Campos ---------- */
        .bixo-field{margin-bottom:1.1rem;}
        .bixo-label{display:block; font-size:.82rem; font-weight:600; color:#33424A; margin-bottom:.45rem;}
        .bixo-inputwrap{position:relative; display:flex; align-items:center;}
        .bixo-inputwrap > .bixo-ic{position:absolute; left:.85rem; width:18px; height:18px; color:#94A6A6; pointer-events:none;}
        .bixo-input, .bixo-select{
            width:100%; box-sizing:border-box; padding:.8rem 1rem .8rem 2.6rem;
            font-size:.92rem; color:var(--text); background:var(--field);
            border:1.5px solid var(--line); border-radius:12px; outline:none;
            transition:border-color .15s, box-shadow .15s, background .15s;
        }
        .bixo-select{padding-left:1rem; appearance:none; cursor:pointer;}
        .bixo-input::placeholder{color:#A9B6B6;}
        .bixo-input:hover, .bixo-select:hover{border-color:#CBDBDB;}
        .bixo-input:focus, .bixo-select:focus{background:#fff; border-color:var(--brand); box-shadow:0 0 0 4px color-mix(in srgb, var(--brand) 16%, transparent);}
        .bixo-input.has-eye{padding-right:3rem;}
        .bixo-input.is-error{border-color:#E5484D; background:#FEF2F2;}
        .bixo-eye{position:absolute; right:.6rem; display:flex; align-items:center; justify-content:center; width:2rem; height:2rem; border:0; background:transparent; color:#94A6A6; cursor:pointer; border-radius:8px;}
        .bixo-eye:hover{color:#33424A; background:#EEF4F4;}

        /* ---------- Row recordarme ---------- */
        .bixo-row{display:flex; align-items:center; justify-content:space-between; gap:1rem; margin:.25rem 0 1.5rem; flex-wrap:wrap;}
        .bixo-check{display:inline-flex; align-items:center; gap:.5rem; font-size:.85rem; color:#4B5A61; cursor:pointer; user-select:none;}
        .bixo-check input{width:1.05rem; height:1.05rem; accent-color:var(--brand); cursor:pointer;}
        .bixo-link{font-size:.85rem; font-weight:600; color:color-mix(in srgb, var(--brand) 75%, #0A1826); text-decoration:none;}
        .bixo-link:hover{color:var(--brand); text-decoration:underline;}

        /* ---------- Botón ---------- */
        .bixo-btn{
            width:100%; border:0; cursor:pointer; color:var(--brand-ink); font-weight:700; font-size:.95rem;
            font-family:'Plus Jakarta Sans',sans-serif; padding:.9rem 1rem; border-radius:12px;
            background:linear-gradient(135deg,var(--brand),var(--brand-2));
            box-shadow:0 14px 30px -12px color-mix(in srgb, var(--brand) 70%, transparent);
            display:flex; align-items:center; justify-content:center; gap:.55rem;
            transition:transform .12s, box-shadow .15s, filter .15s;
        }
        .bixo-btn:hover{filter:brightness(1.04); box-shadow:0 18px 36px -12px color-mix(in srgb, var(--brand) 80%, transparent);}
        .bixo-btn:active{transform:translateY(1px);}
        .bixo-btn:disabled{opacity:.6; cursor:not-allowed;}
        .bixo-spin{width:16px; height:16px; animation:bxspin 1s linear infinite;}
        @keyframes bxspin{to{transform:rotate(360deg)}}

        /* ---------- Alertas ---------- */
        .bixo-alert{display:flex; align-items:center; gap:.55rem; padding:.7rem .85rem; border-radius:12px; font-size:.84rem; margin-bottom:1.1rem; border:1px solid transparent;}
        .bixo-alert svg{width:18px; height:18px; flex-shrink:0;}
        .bixo-alert.warn{background:#FFF7ED; border-color:#FED7AA; color:#9A5B0B;}
        .bixo-alert.ok{background:#ECFDF5; border-color:#A7F3D0; color:#0B7A54;}
        .bixo-alert.err{background:#FEF2F2; border-color:#FECACA; color:#B42318;}

        .bixo-mfoot{display:none; text-align:center; font-size:.74rem; color:#98A6A6; margin-top:2rem;}

        /* ---------- Animación de entrada ---------- */
        .bixo-rise{animation:bxrise .6s cubic-bezier(.2,.7,.2,1) both;}
        .bixo-d1{animation-delay:.05s}.bixo-d2{animation-delay:.13s}.bixo-d3{animation-delay:.22s}.bixo-d4{animation-delay:.32s}.bixo-d5{animation-delay:.42s}
        @keyframes bxrise{from{opacity:0; transform:translateY(14px)}to{opacity:1; transform:none}}

        /* ---------- Responsive ---------- */
        @media (max-width:1023px){
            .bixo-auth{grid-template-columns:1fr;}
            .bixo-stage{display:none;}
            .bixo-panel{padding:2rem 1.4rem;}
            .bixo-mobilebrand{display:flex;}
            .bixo-mfoot{display:block;}
        }
        @media (prefers-reduced-motion:reduce){
            .bixo-spin,.bixo-rise{animation:none!important;}
        }
    </style>
    {{ $head ?? '' }}
</head>
<body class="antialiased">

<div class="bixo-auth" style="--brand:{{ $accent }}; --brand-2:color-mix(in srgb, {{ $accent }} 82%, #06231b); --brand-ink:{{ $ink }};">

    {{-- ===================== STAGE / MARCA ===================== --}}
    <div class="bixo-stage">
        <div class="bixo-brand bixo-rise bixo-d1">
            {!! $mark !!}
            <span class="bixo-word"><b>{{ $name }}</b><small>{{ $by }}</small></span>
        </div>

        <div class="bixo-hero">
            <h1 class="bixo-h1 bixo-rise bixo-d2">{!! $tagline !!}</h1>
            <p class="bixo-sub bixo-rise bixo-d3">{{ $sub }}</p>
            @if(!empty($caps))
            <div class="bixo-caps bixo-rise bixo-d4">
                @foreach($caps as $cap)<span>{{ $cap }}</span>@endforeach
            </div>
            @endif
        </div>

        <div class="bixo-foot bixo-rise bixo-d5">© {{ date('Y') }} Eskala Group · BIXO®</div>
    </div>

    {{-- ===================== FORMULARIO ===================== --}}
    <div class="bixo-panel">
        <div class="bixo-card">

            <div class="bixo-mobilebrand">
                {!! $mark !!}
                <span class="bixo-word"><b style="color:var(--text)">{{ $name }}</b><small style="color:var(--muted)">{{ $by }}</small></span>
            </div>

            @if($title)
            <div class="bixo-head bixo-rise bixo-d1">
                <h2 class="bixo-title bixo-display">{{ $title }}</h2>
                @if($desc)<p class="bixo-desc">{{ $desc }}</p>@endif
            </div>
            @endif

            {{ $slot }}

            <p class="bixo-mfoot">© {{ date('Y') }} Eskala Group · BIXO®</p>
        </div>
    </div>

</div>

</body>
</html>
