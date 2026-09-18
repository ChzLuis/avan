<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BIXO CRM — Crea tu cuenta</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="font-sans antialiased">

<div class="min-h-screen flex">

    {{-- IZQUIERDA: que es el producto --}}
    <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 relative overflow-hidden"
         style="background:linear-gradient(135deg,#0d1117 0%,#1a2744 60%,#0d2a1a 100%);">
        <div class="relative z-10 flex flex-col justify-center px-16 text-white">
            <div class="flex items-center gap-3 mb-12">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center" style="background:#25d366;">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-white/50 font-medium uppercase tracking-wider">BIXO by Eskala</p>
                    <p class="text-xl font-bold">BIXO CRM</p>
                </div>
            </div>
            <h1 class="text-4xl xl:text-5xl font-bold leading-tight mb-4">Tu WhatsApp,<br>ordenado y vendiendo</h1>
            <p class="text-lg text-white/60 max-w-md leading-relaxed">
                Bandeja unificada, clientes con puntaje, respuestas rápidas y bots con IA que atienden cuando tú no puedes.
            </p>
            <div class="mt-12 space-y-3 text-white/70 text-sm">
                <p>1. Creas tu cuenta en un minuto.</p>
                <p>2. Conectas tu número con el asistente de Meta.</p>
                <p>3. Empiezas a atender desde la bandeja.</p>
            </div>
        </div>
    </div>

    {{-- DERECHA: formulario --}}
    <div class="w-full lg:w-1/2 xl:w-2/5 flex items-center justify-center bg-white px-6 py-12">
        <div class="w-full max-w-md">
            <div class="mb-8">
                <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:#25d366">Empieza gratis</p>
                <h2 class="text-2xl font-bold text-gray-900">Crea tu BIXO CRM</h2>
                <p class="text-sm text-gray-500 mt-1">Solo clientes y WhatsApp. Sin tienda ni facturación, salvo que las quieras después.</p>
            </div>

            @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('bixocrm.registro.post') }}" x-data="{ loading:false }" @submit="loading=true" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Nombre del negocio</label>
                    <input type="text" name="negocio" value="{{ old('negocio') }}" required maxlength="100" autofocus placeholder="Ferretería Lima"
                           class="w-full px-4 py-3 border border-gray-200 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color:#25d366">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tu nombre</label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}" required maxlength="100" placeholder="Rosa"
                               class="w-full px-4 py-3 border border-gray-200 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color:#25d366">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">WhatsApp (opcional)</label>
                        <input type="text" name="whatsapp" value="{{ old('whatsapp') }}" maxlength="30" placeholder="999 111 222"
                               class="w-full px-4 py-3 border border-gray-200 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color:#25d366">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Correo electrónico</label>
                    <input type="email" name="email" value="{{ old('email') }}" required maxlength="150" placeholder="rosa@negocio.pe"
                           class="w-full px-4 py-3 border border-gray-200 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color:#25d366">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Contraseña</label>
                        <input type="password" name="password" required minlength="8" placeholder="mínimo 8 caracteres"
                               class="w-full px-4 py-3 border border-gray-200 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color:#25d366">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Repítela</label>
                        <input type="password" name="password_confirmation" required minlength="8" placeholder="••••••••"
                               class="w-full px-4 py-3 border border-gray-200 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent" style="--tw-ring-color:#25d366">
                    </div>
                </div>

                <button type="submit" :disabled="loading"
                        class="w-full py-3 text-white font-semibold rounded-xl text-sm transition-all disabled:opacity-60 shadow-sm" style="background:#25d366;">
                    <span x-text="loading?'Creando tu CRM...':'Crear mi CRM'">Crear mi CRM</span>
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-600">
                ¿Ya tienes cuenta?
                <a href="{{ route('bixocrm.login') }}" class="font-semibold underline" style="color:#128c4b">Inicia sesión</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
