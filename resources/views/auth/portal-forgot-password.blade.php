<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar contraseña — {{ $portalLabel }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 min-h-screen flex items-center justify-center px-4">

<div class="w-full max-w-md">

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-lg p-8">

        {{-- Icono --}}
        <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center mb-6">
            <svg class="w-6 h-6 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
        </div>

        <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-1">{{ $portalLabel }}</p>
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Cambiar contraseña</h1>
        <p class="text-sm text-gray-500 mb-6">Ingresa tu usuario y elige una nueva contraseña.</p>

        @if(session('status'))
        <div class="mb-5 p-4 bg-green-50 border border-green-200 rounded-xl text-sm text-green-800 flex items-center gap-2">
            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Contraseña actualizada. Ya puedes iniciar sesión.
        </div>
        @endif

        @if($errors->any())
        <div class="mb-5 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 flex items-center gap-2">
            <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('portal.password.send', $portal) }}" x-data="{ show1: false, show2: false }">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nombre de usuario</label>
                <input type="text" name="username" value="{{ old('username') }}" required autofocus
                       placeholder="nombre_usuario"
                       class="w-full px-4 py-3 border border-gray-200 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nueva contraseña</label>
                <div class="relative">
                    <input :type="show1 ? 'text' : 'password'" name="password" required minlength="6"
                           placeholder="Mínimo 6 caracteres"
                           class="w-full pl-4 pr-10 py-3 border border-gray-200 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button type="button" @click="show1=!show1" class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirmar contraseña</label>
                <div class="relative">
                    <input :type="show2 ? 'text' : 'password'" name="password_confirmation" required
                           placeholder="Repite la contraseña"
                           class="w-full pl-4 pr-10 py-3 border border-gray-200 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button type="button" @click="show2=!show2" class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>
            <button type="submit"
                    class="w-full py-3 px-4 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-sm transition-all shadow-sm">
                Cambiar contraseña
            </button>
        </form>

        <div class="mt-5 text-center">
            <a href="{{ route(match($portal) {
                'comercial'      => 'bixosales.login',
                'comunicaciones' => 'bixocrm.login',
                'facturacion'    => 'bixofact.login',
                'admin'          => 'login',
            }) }}" class="text-sm text-gray-400 hover:text-gray-600 transition">
                ← Volver al login
            </a>
        </div>
    </div>
</div>

</body>
</html>
