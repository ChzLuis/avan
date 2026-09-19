<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('pageTitle', 'BIXO') &mdash; BIXO</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}
        /* Armazon estilo Pipedrive: barra lateral azul marino, texto claro, activo resaltado. */
        .nav-item { display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:10px;font-size:13.5px;color:#c7c9d9;font-weight:500;transition:all .15s;text-decoration:none;white-space:nowrap; }
        .nav-item:hover { background:rgba(255,255,255,.08);color:#fff; }
        .nav-active { background:#5b4ef5 !important;color:#fff !important; }
        .nav-section { font-size:10px;font-weight:700;color:#8b8da8;text-transform:uppercase;letter-spacing:.08em;padding:14px 12px 4px; }
        .nav-pronto { opacity:.45;cursor:default; }
        .nav-pronto:hover { background:transparent;color:#c7c9d9; }
        .nav-tag { margin-left:auto;font-size:9px;font-weight:700;padding:1px 6px;border-radius:999px;background:rgba(255,255,255,.12);color:#e0e0f0; }
    </style>
</head>
{{-- En movil la barra lateral arranca cerrada (es fija y taparia el chat); en PC, abierta. --}}
<body class="font-sans antialiased bg-gray-50" x-data="{ open: window.innerWidth >= 1024 }">

<div class="flex h-screen overflow-hidden">

    {{-- Fondo oscuro en movil: tocar fuera cierra el menu (la barra tapaba el boton de la cabecera). --}}
    <div x-show="open && window.innerWidth < 1024" x-cloak @click="open=false" class="fixed inset-0 z-40" style="background:rgba(0,0,0,.4)"></div>

    {{-- SIDEBAR --}}
    <aside class="flex-shrink-0 flex flex-col h-full z-50 fixed lg:relative transition-all duration-200"
           :class="open ? 'w-56' : 'w-0 lg:w-14'"
           style="background:#26233b;border-right:1px solid #1f1c33;overflow:hidden;white-space:nowrap;">

        {{-- Logo --}}
        <div class="flex items-center px-3.5 h-12 flex-shrink-0 gap-2" style="border-bottom:1px solid #1f1c33;">
            <div class="w-6 h-6 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#25d366;">
                <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <button x-show="window.innerWidth < 1024" @click="open=false" type="button" class="ml-auto order-last p-1.5 rounded-lg text-gray-400 hover:text-white flex-shrink-0" title="Cerrar menú">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="flex flex-col leading-tight min-w-0">
                <span class="text-white font-bold text-xs">BIXO CRM</span>
                @php $_negocios = \App\Modules\Crm\Controllers\CrmAuthController::proyectosDelUsuario(); @endphp
                @if($_negocios->count() > 1)
                    {{-- Selector de negocio (el usuario tiene varios) --}}
                    <div x-data="{open:false}" class="relative">
                        <button @click="open=!open" type="button"
                                class="flex items-center gap-1 text-gray-400 hover:text-white text-[10px] max-w-[120px] transition">
                            <span class="truncate">{{ $project->name ?? 'Elegir negocio' }}</span>
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.outside="open=false" x-cloak
                             class="absolute left-0 top-6 z-50 w-52 rounded-xl shadow-xl py-1" style="background:#1f1c33;border:1px solid #3a3660;">
                            <p class="text-[9px] text-gray-500 uppercase tracking-wide px-3 py-1">Cambiar de negocio</p>
                            @foreach($_negocios as $neg)
                                <form method="POST" action="{{ route('bixocrm.cambiar.negocio') }}">
                                    @csrf
                                    <input type="hidden" name="project_id" value="{{ $neg->id }}">
                                    <button type="submit"
                                            class="w-full text-left px-3 py-1.5 text-xs transition flex items-center gap-2 {{ ($project->id ?? null)==$neg->id ? 'text-green-400 font-bold' : 'text-gray-300 hover:bg-[#2a2f3a]' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ ($project->id ?? null)==$neg->id ? 'bg-green-400' : 'bg-gray-600' }}"></span>
                                        <span class="truncate">{{ $neg->name }}</span>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @else
                    <span class="text-gray-500 text-[10px] truncate max-w-[110px]">{{ $project->name ?? '' }}</span>
                @endif
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto overflow-x-hidden py-2 px-2 space-y-0.5">
            @php $noLeidos = \App\Modules\Crm\Models\WaConversacion::whereIn('wa_canal_id', \App\Modules\Crm\Models\WaCanal::where('project_id', session('comunicaciones_project_id'))->pluck('id'))->sum('no_leidos'); @endphp

            <a href="{{ route('bixocrm.bandeja') }}" class="nav-item {{ request()->routeIs('bixocrm.bandeja') ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                Bandeja
                @if($noLeidos > 0)<span class="nav-tag" style="background:#25d366;color:#fff">{{ $noLeidos }}</span>@endif
            </a>
            <a href="{{ route('bixocrm.clientes') }}" class="nav-item {{ request()->routeIs('bixocrm.clientes') ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Prospectos
            </a>
            {{-- Hoja de ruta del producto (fases 2 y 3): se muestran apagados a proposito, sin enlace. --}}
            <a href="{{ route('bixocrm.tratos') }}" class="nav-item {{ request()->routeIs('bixocrm.tratos*') ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Tratos
            </a>
            <span class="nav-item nav-pronto" title="Fase 3: tareas y recordatorios">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Acciones <span class="nav-tag">pronto</span>
            </span>
            <span class="nav-item nav-pronto" title="Fase 3: metricas del equipo">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Avances <span class="nav-tag">pronto</span>
            </span>

            <p class="nav-section">Automatización</p>
            <a href="{{ route('bixocrm.bots.index') }}" class="nav-item {{ request()->routeIs('bixocrm.bots.*') ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v3"/></svg>
                Chatbot
            </a>
            <a href="{{ route('bixocrm.chatbot') }}" class="nav-item {{ request()->routeIs('bixocrm.chatbot') ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Respuestas rápidas
            </a>

            <p class="nav-section">Configuración</p>
            <a href="{{ route('bixocrm.configuracion') }}" class="nav-item {{ request()->routeIs('bixocrm.configuracion') ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Canales de WhatsApp
            </a>
            <a href="{{ route('bixocrm.conectar') }}" class="nav-item {{ request()->routeIs('bixocrm.conectar') ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                Conectar WhatsApp
            </a>
        </nav>

        {{-- User --}}
        <div class="flex-shrink-0 px-3 py-2.5" style="border-top:1px solid #1f1c33;">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:#25d366;">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-gray-300 truncate">{{ auth()->user()->name ?? '' }}</p>
                </div>
                <form method="POST" action="{{ route('bixocrm.logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-600 hover:text-red-400 p-1 rounded transition-colors" title="Salir">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- MAIN --}}
    <div class="flex flex-col flex-1 overflow-hidden min-w-0">
        <header class="flex-shrink-0 h-12 bg-white border-b border-gray-200 flex items-center px-4 gap-3 z-30">
            <button @click="open=!open" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <span class="text-gray-700 text-sm font-semibold truncate">@yield('pageTitle', '')</span>
            <form method="GET" action="{{ route('bixocrm.bandeja') }}" class="hidden md:flex flex-1 max-w-md mx-auto relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar en BIXO CRM…"
                       class="w-full pl-9 pr-3 py-1.5 text-sm border border-gray-200 rounded-full bg-gray-50 focus:outline-none focus:ring-1 focus:ring-indigo-400">
            </form>
            <div class="ml-auto text-xs text-gray-500 truncate max-w-[160px]">{{ $project->name ?? '' }}</div>
        </header>

        @if(session('success'))
        <div x-data="{s:true}" x-show="s" x-init="setTimeout(()=>s=false,4000)" x-cloak
             class="fixed top-4 right-4 z-50 bg-green-50 border border-green-200 text-green-800 px-4 py-2.5 rounded-xl text-sm shadow-lg">
            {{ session('success') }}
        </div>
        @endif

        <div class="flex flex-1 overflow-hidden">
            @yield('content')
        </div>
    </div>
</div>

<script>
window.addEventListener('pageshow', function(e) {
    if (e.persisted) {
        fetch('/bixocrm', { method: 'HEAD', credentials: 'same-origin' })
            .then(r => { if (r.redirected || r.url.includes('login')) window.location.replace('{{ route("bixocrm.login") }}'); })
            .catch(() => window.location.replace('{{ route("bixocrm.login") }}'));
    }
});
</script>

{{-- CHG-92608: Control de cierre de sesión por inactividad --}}
<div x-data="sessionWatcherCrm()" x-init="init()" x-cloak>

    {{-- Modal preventivo (3 min antes) --}}
    <div x-show="phase==='warn'"
         style="position:fixed;inset:0;z-index:9998;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.45);backdrop-filter:blur(2px);">
        <div style="background:#fff;border-radius:16px;padding:28px 32px;width:380px;max-width:92vw;box-shadow:0 20px 60px rgba(0,0,0,.25);">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                <div style="width:40px;height:40px;min-width:40px;border-radius:50%;background:#FEF3C7;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:20px;line-height:1;">
                    ⏰
                </div>
                <div>
                    <p style="font-size:14px;font-weight:700;color:#111827;margin:0;">Sesión por expirar</p>
                    <p style="font-size:12px;color:#9CA3AF;margin:2px 0 0;">Tu sesión cerrará en <strong x-text="fmtCd()" style="color:#D97706;"></strong></p>
                </div>
            </div>
            <div style="background:#F3F4F6;border-radius:99px;height:4px;margin-bottom:20px;overflow:hidden;">
                <div :style="'width:'+cdPct+'%;background:#F59E0B;height:100%;border-radius:99px;transition:width 1s linear;'"></div>
            </div>
            <div style="display:flex;gap:8px;">
                <button @click="logout()"
                        style="flex:1;padding:9px;border-radius:9px;border:1px solid #E5E8EF;background:#fff;color:#6B7280;font-size:13px;font-weight:600;cursor:pointer;">
                    Cerrar sesión
                </button>
                <button @click="keep()"
                        style="flex:2;padding:9px;border-radius:9px;border:none;background:#25d366;color:#fff;font-size:13px;font-weight:600;cursor:pointer;">
                    Continuar trabajando
                </button>
            </div>
        </div>
    </div>

    {{-- Modal definitivo (sesión cerrada) --}}
    <div x-show="phase==='expired'"
         style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);">
        <div style="background:#fff;border-radius:16px;padding:32px;width:360px;max-width:92vw;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.4);">
            <div style="width:52px;height:52px;min-width:52px;border-radius:50%;background:#FEE2E2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px;line-height:1;">
                🔒
            </div>
            <p style="font-size:16px;font-weight:700;color:#111827;margin:0 0 8px;">Sesión cerrada</p>
            <p style="font-size:13px;color:#6B7280;margin:0 0 24px;">Tu sesión expiró por inactividad. Inicia sesión nuevamente para continuar.</p>
            <button @click="logout()"
                    style="width:100%;padding:10px;border-radius:10px;border:none;background:#25d366;color:#fff;font-size:14px;font-weight:600;cursor:pointer;">
                Iniciar sesión
            </button>
        </div>
    </div>
</div>

<script>
function sessionWatcherCrm() {
    const LIMIT_S = 30 * 60;
    const WARN_S  = 3 * 60;
    const WARN_AT = LIMIT_S - WARN_S;
    const LOGOUT  = {!! json_encode(route('bixocrm.logout')) !!};
    const TOKEN   = document.querySelector('meta[name="csrf-token"]')?.content;
    return {
        phase: 'idle', cd: WARN_S, cdPct: 100, _tick: null, _elapsed: 0,
        init() {
            this._startTick();
            ['mousemove','keydown','click','scroll','touchstart'].forEach(ev =>
                document.addEventListener(ev, () => this._onActivity(), { passive: true })
            );
        },
        _startTick() {
            clearInterval(this._tick);
            this._tick = setInterval(() => this._onTick(), 1000);
        },
        _onTick() {
            this._elapsed++;
            if (this._elapsed >= LIMIT_S) {
                this.phase = 'expired';
                clearInterval(this._tick);
                setTimeout(() => this.logout(), 8000);
            } else if (this._elapsed >= WARN_AT) {
                this.phase = 'warn';
                this.cd = LIMIT_S - this._elapsed;
                this.cdPct = Math.round((this.cd / WARN_S) * 100);
            }
        },
        _onActivity() {
            if (this.phase === 'expired' || this.phase === 'warn') return;
            this._elapsed = 0;
        },
        keep() {
            fetch('/ping-session', { method:'POST', headers:{ 'X-CSRF-TOKEN': TOKEN, 'Content-Type':'application/json' } }).catch(()=>{});
            this._elapsed = 0; this.phase = 'idle'; this.cd = WARN_S; this.cdPct = 100;
            this._startTick();
        },
        fmtCd() {
            const m = Math.floor(this.cd / 60), s = this.cd % 60;
            return m > 0 ? m + ' min ' + s + ' s' : s + ' s';
        },
        logout() {
            clearInterval(this._tick);
            const f = document.createElement('form');
            f.method = 'POST'; f.action = LOGOUT;
            const t = document.createElement('input'); t.type='hidden'; t.name='_token'; t.value=TOKEN;
            f.appendChild(t);
            document.body.appendChild(f); f.submit();
        }
    };
}
</script>
@include('partials.avisos')
</body>
</html>
