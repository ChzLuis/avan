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

<div class="min-h-screen flex">

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
        <div class="w-full max-w-md"
             x-data="{
                step: 1,
                loading: false,
                showPass: false,
                error: '',
                projects: [],
                isSuperadmin: false,
                email: '{{ old('email') }}',
                password: '',
                projectId: '',

                async checkCredentials() {
                    this.loading = true;
                    this.error = '';
                    try {
                        const res = await fetch('{{ route('bixosales.get.projects') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ email: this.email, password: this.password }),
                        });
                        const data = await res.json();
                        if (!data.ok) {
                            this.error = data.message || 'Credenciales incorrectas.';
                        } else {
                            this.projects     = data.projects;
                            this.isSuperadmin = data.is_superadmin;
                            this.projectId    = data.is_superadmin ? 'admin' : (data.projects[0]?.id ?? '');
                            this.step = 2;
                        }
                    } catch(e) {
                        this.error = 'Error de conexión. Intenta de nuevo.';
                    }
                    this.loading = false;
                }
             }">

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

            {{-- Error blade (por si hay redirect con error) --}}
            @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 flex items-center gap-2">
                <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ $errors->first() }}
            </div>
            @endif

            {{-- ── PASO 1: Credenciales ── --}}
            <div x-show="step === 1">
                <div class="mb-8">
                    <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-1">Acceso privado</p>
                    <h2 class="text-2xl font-bold text-gray-900">Iniciar sesión</h2>
                    <p class="text-sm text-gray-500 mt-1">Ingresa tus credenciales para continuar</p>
                </div>

                {{-- Error Alpine --}}
                <div x-show="error" x-cloak class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-text="error"></span>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Usuario o correo</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                            </svg>
                        </div>
                        <input type="text" x-model="email"
                               required autofocus autocomplete="username"
                               placeholder="usuario o correo"
                               @keydown.enter="checkCredentials()"
                               class="w-full pl-10 pr-4 py-3 border border-gray-200 bg-gray-50 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Contraseña</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input :type="showPass ? 'text' : 'password'" x-model="password"
                               required autocomplete="current-password"
                               placeholder="••••••••"
                               @keydown.enter="checkCredentials()"
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

                <button type="button" @click="checkCredentials()" :disabled="loading"
                        class="w-full py-3 px-4 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-sm transition-all duration-150 disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-sm">
                    <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span x-text="loading ? 'Verificando...' : 'Continuar'">Continuar</span>
                </button>
            </div>

            {{-- ── PASO 2: Selección de proyecto ── --}}
            <div x-show="step === 2" x-cloak>
                <div class="mb-8">
                    <button @click="step=1; error=''" class="flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-600 mb-4 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Cambiar usuario
                    </button>
                    <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-1">Selecciona tu negocio</p>
                    <h2 class="text-2xl font-bold text-gray-900">¿Dónde vas a trabajar?</h2>
                    <p class="text-sm text-gray-500 mt-1">Elige el proyecto al que deseas acceder</p>
                </div>

                <form method="POST" action="{{ route('bixosales.login.post') }}"
                      x-data="{ formLoading: false }" @submit="formLoading = true">
                    @csrf
                    <input type="hidden" name="email"    :value="email">
                    <input type="hidden" name="password" :value="password">
                    <input type="hidden" name="remember" value="1">

                    <div class="mb-5 space-y-2">

                        {{-- Opción Admin General (solo superadmin) --}}
                        <template x-if="isSuperadmin">
                            <label class="flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition"
                                   :class="projectId === 'admin' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" name="project_id" value="admin"
                                       x-model="projectId" class="sr-only">
                                <div class="w-9 h-9 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-900">Admin General</p>
                                    <p class="text-xs text-gray-400">Panel de administración del sistema</p>
                                </div>
                                <div x-show="projectId === 'admin'" class="w-5 h-5 rounded-full bg-indigo-500 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </label>
                        </template>

                        {{-- Proyectos del usuario --}}
                        <template x-for="p in projects" :key="p.id">
                            <label class="flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition"
                                   :class="projectId == p.id ? 'border-slate-800 bg-slate-50' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" name="project_id" :value="p.id"
                                       x-model="projectId" class="sr-only">
                                <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-900" x-text="p.name"></p>
                                    <p class="text-xs text-gray-400" x-text="p.slug"></p>
                                </div>
                                <div x-show="projectId == p.id" class="w-5 h-5 rounded-full bg-slate-800 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </label>
                        </template>
                    </div>

                    <button type="submit" :disabled="formLoading || !projectId"
                            class="w-full py-3 px-4 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-sm transition-all duration-150 disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-sm">
                        <svg x-show="formLoading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <span x-text="formLoading ? 'Ingresando...' : 'Ingresar'">Ingresar</span>
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-gray-400">
                Portal privado — acceso solo para personal autorizado
            </p>
        </div>
    </div>

</div>

</body>
</html>
