<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nueva contraseña — {{ $portalLabel }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 min-h-screen flex items-center justify-center px-4">

<div class="w-full max-w-md">

    <div class="bg-white rounded-2xl shadow-lg p-8">

        <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center mb-6">
            <svg class="w-6 h-6 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>

        <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-1">{{ $portalLabel }}</p>
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Nueva contraseña</h1>
        <p class="text-sm text-gray-500 mb-6">Elige una contraseña segura de al menos 8 caracteres.</p>

        @if($errors->any())
        <div class="mb-5 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 flex items-center gap-2">
            <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('portal.password.update', $portal) }}"
              x-data="{ showPass: false, showPass2: false }">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Correo electrónico</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required
                       class="w-full px-4 py-3 border border-gray-200 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="mb-4" x-data="{ showPass: false }">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nueva contraseña</label>
                <div class="relative">
                    <input :type="showPass ? 'text' : 'password'" name="password" required autofocus
                           placeholder="Mínimo 8 caracteres"
                           class="w-full pl-4 pr-12 py-3 border border-gray-200 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button type="button" @click="showPass=!showPass"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                        <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="!showPass" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="mb-6" x-data="{ showPass2: false }">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirmar contraseña</label>
                <div class="relative">
                    <input :type="showPass2 ? 'text' : 'password'" name="password_confirmation" required
                           placeholder="Repite la contraseña"
                           class="w-full pl-4 pr-12 py-3 border border-gray-200 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button type="button" @click="showPass2=!showPass2"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                        <svg x-show="!showPass2" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit"
                    class="w-full py-3 px-4 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-sm transition-all shadow-sm">
                Guardar nueva contraseña
            </button>
        </form>
    </div>
</div>

</body>
</html>
