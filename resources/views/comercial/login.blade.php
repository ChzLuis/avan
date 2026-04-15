<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Portal Comercial — Acceso</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="font-sans antialiased">

<div class="min-h-screen flex" x-data="{}">

    {{-- IZQUIERDA — decorativa --}}
    <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 relative overflow-hidden"
         style="background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 100%);">
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
        <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full opacity-20" style="background:rgba(255,255,255,0.15);"></div>
        <div class="absolute -bottom-32 -right-16 w-80 h-80 rounded-full opacity-10" style="background:rgba(255,255,255,0.1);"></div>

        <div class="relative z-10 flex flex-col justify-center px-16 text-white">
            <div class="flex items-center gap-3 mb-12">
                <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1-4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-white/60 font-medium uppercase tracking-wider">Portal</p>
                    <p class="text-xl font-bold">Comercial</p>
                </div>
            </div>

            <h1 class="text-4xl xl:text-5xl font-bold leading-tight mb-4">
                Tu herramienta<br>de ventas diarias
            </h1>
            <p class="text-lg text-white/70 max-w-sm leading-relaxed">
                POS, pedidos, cotizaciones y facturas en un solo lugar. Rápido y sin complicaciones.
            </p>

            <div class="mt-12 space-y-4">
                @foreach([
                    ['icon'=>'M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1-4l-3 3m0 0l-3-3m3 3V4', 'text'=>'Punto de venta rápido'],
                    ['icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'text'=>'Gestión de pedidos y cotizaciones'],
                    ['icon'=>'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z', 'text'=>'Facturas y boletas al instante'],
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

    {{-- DERECHA — formulario --}}
    <div class="w-full lg:w-1/2 xl:w-2/5 flex items-center justify-center bg-white px-6 py-12">
        <div class="w-full max-w-md">

            {{-- Logo mobile --}}
            <div class="flex items-center gap-2 mb-8 lg:hidden">
                <div class="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1-4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                </div>
                <span class="text-lg font-bold text-gray-900">Portal Comercial</span>
            </div>

            <div class="mb-8">
                <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-1">Acceso privado</p>
                <h2 class="text-2xl font-bold text-gray-900">Iniciar sesión</h2>
                <p class="text-sm text-gray-500 mt-1">Ingresa tus credenciales para continuar</p>
            </div>

            @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 flex items-center gap-2">
                <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('bixosales.login.post') }}"
                  x-data="{ loading: false, showPass: false }"
                  @submit="loading = true">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Correo electrónico</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                            </svg>
                        </div>
                        <input type="text" name="email" value="{{ old('email') }}"
                               required autofocus autocomplete="username"
                               placeholder="usuario"
                               class="w-full pl-10 pr-4 py-3 border border-gray-200 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Contraseña</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input :type="showPass ? 'text' : 'password'" name="password"
                               required autocomplete="current-password"
                               placeholder="••••••••"
                               class="w-full pl-10 pr-12 py-3 border border-gray-200 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <button type="button" @click="showPass=!showPass"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPass" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center mb-6">
                    <input id="remember" type="checkbox" name="remember"
                           class="w-4 h-4 rounded border-gray-300 cursor-pointer">
                    <label for="remember" class="ml-2 text-sm text-gray-600 cursor-pointer">Mantener sesión iniciada</label>
                </div>

                <button type="submit" :disabled="loading"
                        class="w-full py-3 px-4 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-sm transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-700 disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-sm">
                    <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span x-text="loading ? 'Ingresando...' : 'Ingresar'">Ingresar</span>
                </button>
            </form>

            <p class="mt-6 text-center text-xs text-gray-400">
                Portal privado — acceso solo para personal autorizado
            </p>
        </div>
    </div>

</div>

</body>
</html>
