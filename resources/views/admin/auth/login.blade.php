<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin — BIXO</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}
        body { font-family: 'Inter', sans-serif; }
        .admin-input {
            width: 100%;
            padding: 11px 16px;
            border-radius: 8px;
            font-size: 14px;
            color: #e2e8f0;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .admin-input::placeholder { color: rgba(148,163,184,0.6); }
        .admin-input:focus {
            border-color: rgba(99,102,241,0.7);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
        }
        .admin-input:-webkit-autofill,
        .admin-input:-webkit-autofill:focus {
            -webkit-text-fill-color: #e2e8f0 !important;
            -webkit-box-shadow: 0 0 0px 1000px #1e1b4b inset !important;
        }
    </style>
</head>
<body class="bg-gray-950 min-h-screen flex items-center justify-center p-4">

{{-- Fondo decorativo --}}
<div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full opacity-10"
         style="background:radial-gradient(circle, #6366f1, transparent)"></div>
    <div class="absolute -bottom-40 -left-40 w-96 h-96 rounded-full opacity-10"
         style="background:radial-gradient(circle, #8b5cf6, transparent)"></div>
</div>

<div class="relative w-full max-w-sm" x-data="{ loading: false, showPass: false }">

    {{-- Logo --}}
    <div class="flex flex-col items-center mb-8">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4"
             style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-white">BIXO</h1>
        <p class="text-sm text-gray-500 mt-1">Panel de Administración</p>
    </div>

    {{-- Tarjeta --}}
    <div class="rounded-2xl p-8" style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);">

        @if($errors->any())
        <div class="mb-5 p-3 rounded-xl text-sm text-red-300 flex items-center gap-2"
             style="background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3);">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}" @submit="loading = true">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Correo electrónico</label>
                <input type="text" name="email" value="{{ old('email') }}"
                       required autofocus autocomplete="username"
                       placeholder="usuario"
                       class="admin-input">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Contraseña</label>
                <div class="relative">
                    <input :type="showPass ? 'text' : 'password'"
                           name="password" required autocomplete="current-password"
                           placeholder="••••••••"
                           class="admin-input" style="padding-right:44px;">
                    <button type="button" @click="showPass = !showPass"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-300 transition-colors">
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

            <button type="submit" :disabled="loading"
                    class="w-full py-3 rounded-xl font-semibold text-sm text-white transition-all disabled:opacity-60 flex items-center justify-center gap-2"
                    style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span x-text="loading ? 'Ingresando...' : 'Ingresar al sistema'">Ingresar al sistema</span>
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-gray-600 mt-6">
        Acceso restringido — Solo administradores autorizados
    </p>
</div>

</body>
</html>
