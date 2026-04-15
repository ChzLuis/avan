<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comunicaciones — Acceso</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="font-sans antialiased">

<div class="min-h-screen flex" x-data="{}">

    {{-- IZQUIERDA --}}
    <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 relative overflow-hidden"
         style="background:linear-gradient(135deg,#0d1117 0%,#1a2744 60%,#0d2a1a 100%);">
        <div class="absolute inset-0 opacity-10">
            <svg width="100%" height="100%"><defs><pattern id="g" width="60" height="60" patternUnits="userSpaceOnUse">
                <path d="M 60 0 L 0 0 0 60" fill="none" stroke="white" stroke-width="1"/>
            </pattern></defs><rect width="100%" height="100%" fill="url(#g)"/></svg>
        </div>

        <div class="relative z-10 flex flex-col justify-center px-16 text-white">
            <div class="flex items-center gap-3 mb-12">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center" style="background:#25d366;">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.558 4.123 1.528 5.855L0 24l6.335-1.508A11.933 11.933 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.848 0-3.596-.484-5.112-1.334l-.368-.214-3.761.896.957-3.656-.234-.382A9.958 9.958 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-white/50 font-medium uppercase tracking-wider">Portal</p>
                    <p class="text-xl font-bold">Comunicaciones</p>
                </div>
            </div>

            <h1 class="text-4xl xl:text-5xl font-bold leading-tight mb-4">
                Tu bandeja de<br>WhatsApp unificada
            </h1>
            <p class="text-lg text-white/60 max-w-sm leading-relaxed">
                Gestiona todos tus canales de WhatsApp, clientes y leads desde un solo lugar.
            </p>

            <div class="mt-12 space-y-4">
                @foreach([
                    ['icon'=>'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', 'text'=>'2 canales WhatsApp en 1 bandeja'],
                    ['icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'text'=>'CRM de clientes y leads'],
                    ['icon'=>'M13 10V3L4 14h7v7l9-11h-7z', 'text'=>'Respuestas rápidas precargadas'],
                ] as $f)
                <div class="flex items-center gap-3 text-white/70">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(37,211,102,.2)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#25d366">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $f['icon'] }}"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium">{{ $f['text'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- DERECHA — form --}}
    <div class="w-full lg:w-1/2 xl:w-2/5 flex items-center justify-center bg-white px-6 py-12">
        <div class="w-full max-w-md">

            <div class="flex items-center gap-2 mb-8 lg:hidden">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center" style="background:#25d366">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <span class="text-lg font-bold text-gray-900">Comunicaciones</span>
            </div>

            <div class="mb-8">
                <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:#25d366">Acceso privado</p>
                <h2 class="text-2xl font-bold text-gray-900">Iniciar sesión</h2>
                <p class="text-sm text-gray-500 mt-1">Solo para personal autorizado</p>
            </div>

            @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('bixocrm.login.post') }}"
                  x-data="{ loading:false, show:false }" @submit="loading=true">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Correo electrónico</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                            </svg>
                        </div>
                        <input type="text" name="email" value="{{ old('email') }}"
                               required autofocus placeholder="usuario"
                               class="w-full pl-10 pr-4 py-3 border border-gray-200 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                               style="--tw-ring-color:#25d366">
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Contraseña</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input :type="show?'text':'password'" name="password" required placeholder="••••••••"
                               class="w-full pl-10 pr-12 py-3 border border-gray-200 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                               style="--tw-ring-color:#25d366">
                        <button type="button" @click="show=!show"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center mb-6">
                    <input id="remember" type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300">
                    <label for="remember" class="ml-2 text-sm text-gray-600 cursor-pointer">Mantener sesión</label>
                </div>

                <button type="submit" :disabled="loading"
                        class="w-full py-3 text-white font-semibold rounded-xl text-sm transition-all disabled:opacity-60 flex items-center justify-center gap-2 shadow-sm"
                        style="background:#25d366;">
                    <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span x-text="loading?'Ingresando...':'Ingresar'">Ingresar</span>
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
