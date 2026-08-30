<x-bixo-auth
    name="BIXO" by="Ventas y operación"
    tagline="Tu herramienta<br>de <em>ventas</em> diaria."
    sub="POS, pedidos, cotizaciones y cobros en un solo lugar. Rápido y sin complicaciones."
    :caps="['Punto de venta', 'Pedidos y cotizaciones', 'Cuentas por cobrar']"
    accent="#F0A63C" ink="#3A2606"
    title="Iniciar sesión">

    <div x-data="{
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
                        this.isSuperadmin = false;
                        this.projectId    = data.projects[0]?.id ?? '';
                        this.step = 2;
                    }
                } catch(e) {
                    this.error = 'Error de conexión. Intenta de nuevo.';
                }
                this.loading = false;
            }
         }">

        @if(session('inactivity'))
        <div class="bixo-alert warn">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Sesión cerrada por inactividad.
        </div>
        @endif

        @if($errors->any())
        <div x-data x-init="$data.step = 1" class="bixo-alert err">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ $errors->first() }}
        </div>
        @endif

        {{-- ── PASO 1: Credenciales ── --}}
        <div x-show="step === 1">
            <div class="bixo-head">
                <h2 class="bixo-title bixo-display">Iniciar sesión</h2>
                <p class="bixo-desc">Ingresa tus credenciales para continuar.</p>
            </div>

            <div x-show="error" x-cloak class="bixo-alert err">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-text="error"></span>
            </div>

            <div class="bixo-field">
                <label class="bixo-label">Usuario o correo</label>
                <div class="bixo-inputwrap">
                    <svg class="bixo-ic" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <input type="text" x-model="email" required autofocus autocomplete="username" placeholder="usuario o correo" @keydown.enter="checkCredentials()" class="bixo-input">
                </div>
            </div>

            <div class="bixo-field">
                <label class="bixo-label">Contraseña</label>
                <div class="bixo-inputwrap">
                    <svg class="bixo-ic" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <input :type="showPass ? 'text' : 'password'" x-model="password" required autocomplete="current-password" placeholder="••••••••" @keydown.enter="checkCredentials()" class="bixo-input has-eye">
                    <button type="button" @click="showPass=!showPass" class="bixo-eye" aria-label="Mostrar u ocultar contraseña">
                        <svg x-show="!showPass" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="showPass" x-cloak width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
            </div>

            <button type="button" @click="email.trim() && password ? checkCredentials() : (error = 'Completa usuario y contraseña.')" :disabled="loading" class="bixo-btn">
                <svg x-show="loading" x-cloak class="bixo-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                <span x-text="loading ? 'Verificando...' : 'Continuar'">Continuar</span>
            </button>
        </div>

        {{-- ── PASO 2: Selección de proyecto ── --}}
        <div x-show="step === 2" x-cloak>
            <button type="button" class="bixo-back" @click="step=1; error=''">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Cambiar usuario
            </button>
            <div class="bixo-head">
                <h2 class="bixo-title bixo-display">¿Dónde vas a trabajar?</h2>
                <p class="bixo-desc">Elige el proyecto al que deseas acceder.</p>
            </div>

            <form method="POST" action="{{ route('bixosales.login.post') }}" x-data="{ formLoading: false }" @submit="formLoading = true">
                @csrf
                <input type="hidden" name="email"    :value="email">
                <input type="hidden" name="password" :value="password">
                <input type="hidden" name="remember" value="1">

                <div style="display:flex; flex-direction:column; gap:.55rem; margin-bottom:1.3rem;">

                    <template x-if="isSuperadmin">
                        <label class="bixo-choice" :class="projectId === 'admin' ? 'is-sel' : ''">
                            <input type="radio" name="project_id" value="admin" x-model="projectId" class="bixo-sr">
                            <span class="bixo-choice-ic"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></span>
                            <span style="flex:1"><p class="bixo-choice-name">Admin General</p><p class="bixo-choice-sub">Administración del sistema</p></span>
                            <span x-show="projectId === 'admin'" class="bixo-choice-check"><svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></span>
                        </label>
                    </template>

                    <template x-for="p in projects" :key="p.id">
                        <label class="bixo-choice" :class="projectId == p.id ? 'is-sel' : ''">
                            <input type="radio" name="project_id" :value="p.id" x-model="projectId" class="bixo-sr">
                            <span class="bixo-choice-ic"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></span>
                            <span style="flex:1"><p class="bixo-choice-name" x-text="p.name"></p><p class="bixo-choice-sub" x-text="p.slug"></p></span>
                            <span x-show="projectId == p.id" class="bixo-choice-check"><svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></span>
                        </label>
                    </template>
                </div>

                <button type="submit" :disabled="formLoading || !projectId" class="bixo-btn">
                    <svg x-show="formLoading" x-cloak class="bixo-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span x-text="formLoading ? 'Ingresando...' : 'Ingresar'">Ingresar</span>
                </button>
            </form>
        </div>

        <p style="margin-top:1.3rem; text-align:center;">
            <a href="{{ route('portal.password.request', 'comercial') }}" class="bixo-link" style="color:var(--muted); font-weight:500;">¿Olvidaste tu contraseña?</a>
        </p>
    </div>
</x-bixo-auth>
