<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesión — AVAN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}
    </style>
</head>
<body class="font-sans antialiased">

@php
    // Buscar proyecto activo para leer la config del login
    // (por ahora tomamos el primer proyecto del sistema como portada)
    $loginProject = \App\Models\Project::first();
    $loginBg      = $loginProject?->setting('login_bg_type', 'gradient');
    $loginColor1  = $loginProject?->setting('login_color1', '#4f46e5');
    $loginColor2  = $loginProject?->setting('login_color2', '#7c3aed');
    $loginBgImage = $loginProject?->setting('login_bg_image', '');
    $loginHeading = $loginProject?->setting('login_heading', 'Bienvenido de vuelta');
    $loginSubtitle = $loginProject?->setting('login_subtitle', 'Ingresa a tu panel de gestión');
    $appName      = $loginProject?->name ?? 'AVAN';

    if ($loginBg === 'solid') {
        $bgStyle = "background:{$loginColor1};";
    } elseif ($loginBg === 'image' && $loginBgImage) {
        $bgStyle = "background:url('{$loginBgImage}') center/cover no-repeat;";
    } else {
        $bgStyle = "background:linear-gradient(135deg, {$loginColor1} 0%, {$loginColor2} 100%);";
    }
@endphp

<div class="min-h-screen flex" x-data="{}">

    {{-- LADO IZQUIERDO — decorativo --}}
    <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 relative overflow-hidden" style="{{ $bgStyle }}">
        {{-- Overlay oscuro suave --}}
        <div class="absolute inset-0" style="background:rgba(0,0,0,0.35);"></div>

        {{-- Patrón geométrico decorativo --}}
        <div class="absolute inset-0 opacity-10">
            <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid" width="60" height="60" patternUnits="userSpaceOnUse">
                        <path d="M 60 0 L 0 0 0 60" fill="none" stroke="white" stroke-width="1"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" />
            </svg>
        </div>

        {{-- Círculos decorativos --}}
        <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full opacity-20" style="background:rgba(255,255,255,0.3);"></div>
        <div class="absolute -bottom-32 -right-16 w-80 h-80 rounded-full opacity-15" style="background:rgba(255,255,255,0.2);"></div>

        {{-- Contenido centrado --}}
        <div class="relative z-10 flex flex-col justify-center px-16 text-white">
            {{-- Logo --}}
            <div class="flex items-center gap-3 mb-12">
                <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold tracking-wide">{{ strtoupper($appName) }}</span>
            </div>

            <h1 class="text-4xl xl:text-5xl font-bold leading-tight mb-4">
                {{ $loginHeading }}
            </h1>
            <p class="text-lg text-white/70 max-w-sm leading-relaxed">
                {{ $loginSubtitle }}
            </p>

            {{-- Features --}}
            <div class="mt-12 space-y-4">
                @foreach([
                    ['icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'text'=>'Control total de tu negocio'],
                    ['icon'=>'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',                  'text'=>'Reportes y estadísticas en tiempo real'],
                    ['icon'=>'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'text'=>'Acceso seguro y protegido'],
                ] as $feat)
                <div class="flex items-center gap-3 text-white/80">
                    <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $feat['icon'] }}"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium">{{ $feat['text'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- LADO DERECHO — formulario --}}
    <div class="w-full lg:w-1/2 xl:w-2/5 flex items-center justify-center bg-white px-6 py-12">
        <div class="w-full max-w-md">

            {{-- Logo mobile (solo en pequeño) --}}
            <div class="flex items-center gap-2 mb-8 lg:hidden">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <span class="text-lg font-bold text-gray-900">{{ strtoupper($appName) }}</span>
            </div>

            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900">Iniciar sesión</h2>
                <p class="text-sm text-gray-500 mt-1">Ingresa tus credenciales para continuar</p>
            </div>

            {{ $slot }}

        </div>
    </div>

</div>

</body>
</html>
