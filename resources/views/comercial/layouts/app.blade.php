@php
$_pid = session('comercial_project_id');
$_mods = $_pid ? \App\Models\Project::find($_pid)?->modules()->wherePivot('is_active', true)->pluck('modules.key')->toArray() : [];
$_has = fn(string $key) => in_array($key, $_mods);
$_hasBot = $_pid ? \App\Models\WaCanal::where('project_id', $_pid)->exists() : false;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $project->name ?? 'Comercial' }} — Portal Comercial</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}
        .cnav-item{
            display:flex; align-items:center; gap:8px;
            padding:7px 10px; border-radius:8px;
            font-size:13px; color:#94a3b8; font-weight:500;
            transition:background .15s, color .15s; white-space:nowrap;
            text-decoration:none;
        }
        .cnav-item:hover{ background:#1e2130; color:#e2e8f0; }
        .cnav-active{ background:#1e3a5f !important; color:#60a5fa !important; }
        .cnav-section{ font-size:10px; font-weight:700; color:#4b5563;
            text-transform:uppercase; letter-spacing:.08em;
            padding:14px 10px 4px; }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50"
      x-data="{ open: true }"
      @resize.window="if(window.innerWidth<1024){ open=true }">

<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    <aside class="flex-shrink-0 flex flex-col h-full z-50 fixed transition-all duration-200"
           :class="open ? 'w-56' : 'w-0'"
           style="background:#0f1117; border-right:1px solid #1e2130; overflow:hidden; white-space:nowrap; top:0; left:0; bottom:0;">

        {{-- Logo --}}
        <div class="flex items-center justify-between px-3.5 h-12 flex-shrink-0"
             style="border-bottom:1px solid #1e2130;">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0" style="background:#1d4ed8;">
                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1-4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                </div>
                <div class="flex flex-col leading-tight">
                    <span class="text-white font-bold text-xs">Comercial</span>
                    <span class="text-gray-500 text-[10px] truncate max-w-[110px]">{{ $project->name ?? '' }}</span>
                </div>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto overflow-x-hidden py-2 px-2 space-y-0.5">

            @if($_has('orders') || $_has('quotes'))
            <p class="cnav-section">Ventas</p>
            @endif

            @if($_has('orders'))
            <a href="{{ route('bixosales.pos') }}"
               class="cnav-item {{ request()->routeIs('bixosales.pos*') ? 'cnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1-4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
                Punto de Venta
            </a>
            @endif

            @if($_has('orders'))
            <a href="{{ route('bixosales.pedidos') }}"
               class="cnav-item {{ request()->routeIs('bixosales.pedidos*') && !request()->get('canal') ? 'cnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Órdenes
            </a>
            @endif

            @if($_has('quotes'))
            <a href="{{ route('bixosales.cotizaciones') }}"
               class="cnav-item {{ request()->routeIs('bixosales.cotizaciones*') ? 'cnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Propuestas
            </a>
            @endif

            @if($_hasBot)
            <p class="cnav-section">Canal WhatsApp</p>
            <a href="{{ route('bixosales.rifas') }}"
               class="cnav-item {{ request()->routeIs('bixosales.rifas*') ? 'cnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                </svg>
                Pedidos WhatsApp
            </a>
            @endif

            @if($_has('invoices'))
            <p class="cnav-section">Facturación</p>
            <a href="{{ route('bixosales.facturas') }}"
               class="cnav-item {{ request()->routeIs('bixosales.facturas*') ? 'cnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                </svg>
                Comprobantes
            </a>
            @endif

            @if($_has('clients'))
            <p class="cnav-section">Clientes</p>
            <a href="{{ route('bixosales.clientes') }}"
               class="cnav-item {{ request()->routeIs('bixosales.clientes*') ? 'cnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Directorio
            </a>
            @endif

            <p class="cnav-section">Reportes</p>

            <a href="{{ route('bixosales.reportes.ventas') }}"
               class="cnav-item {{ request()->routeIs('bixosales.reportes.ventas') ? 'cnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Resumen de Ventas
            </a>

            <a href="{{ route('bixosales.reportes.seguimiento') }}"
               class="cnav-item {{ request()->routeIs('bixosales.reportes.seguimiento') ? 'cnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Seguimiento de Leads
            </a>

            <a href="{{ route('bixosales.reportes.ventas.general') }}"
               class="cnav-item {{ request()->routeIs('bixosales.reportes.ventas.general') ? 'cnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Ventas General
            </a>

            <a href="{{ route('bixosales.reportes.top.productos') }}"
               class="cnav-item {{ request()->routeIs('bixosales.reportes.top.productos') ? 'cnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                Top Productos
            </a>


        </nav>

        {{-- User + logout --}}
        <div class="flex-shrink-0 px-3 py-2.5" style="border-top:1px solid #1e2130;">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-gray-300 truncate">{{ auth()->user()->name ?? '' }}</p>
                    <p class="text-[11px] text-gray-600 truncate">{{ auth()->user()->email ?? '' }}</p>
                </div>
                <form method="POST" action="{{ route('bixosales.logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-600 hover:text-red-400 p-1 rounded transition-colors" title="Cerrar sesión">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- MAIN --}}
    <div class="flex flex-col flex-1 overflow-hidden min-w-0 transition-all duration-200"
         :style="open ? 'margin-left:224px' : 'margin-left:0'"
         style="margin-left:224px">

        {{-- Header --}}
        <header class="flex-shrink-0 h-12 bg-white border-b border-gray-200 flex items-center px-4 gap-3 z-30">
            <button @click="open=!open"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="flex items-center gap-1.5 flex-1 min-w-0">
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">Comercial</span>
                <span class="text-gray-300">/</span>
                <span class="text-gray-500 text-xs truncate">{{ $pageTitle ?? '' }}</span>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                <span class="text-xs text-gray-400 hidden sm:block">{{ $project->name ?? '' }}</span>
                <div class="w-7 h-7 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-semibold">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
            </div>
        </header>

        {{-- Flash --}}
        @if(session('error'))
        <div x-data="{s:true}" x-show="s" x-init="setTimeout(()=>s=false,4000)" x-cloak
             class="fixed top-4 right-4 z-50 bg-red-50 border border-red-200 text-red-800 px-4 py-2.5 rounded-xl text-sm shadow-lg">
            {{ session('error') }}
        </div>
        @endif
        @if(session('success'))
        <div x-data="{s:true}" x-show="s" x-init="setTimeout(()=>s=false,4000)" x-cloak
             class="fixed top-4 right-4 z-50 bg-green-50 border border-green-200 text-green-800 px-4 py-2.5 rounded-xl text-sm shadow-lg">
            {{ session('success') }}
        </div>
        @endif

        {{-- Content --}}
        <div class="flex flex-1 overflow-hidden">
            {{ $slot }}
        </div>
    </div>
</div>

@stack('scripts')

{{-- ── Timeout de sesión ───────────────────────────────────── --}}
<div x-data="sessionTimeoutComercial()" x-init="init()" x-cloak>
    <div x-show="warning"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         style="position:fixed;bottom:24px;right:24px;z-index:9999;width:320px;background:#1e293b;border:1px solid #334155;border-radius:14px;padding:18px 20px;box-shadow:0 8px 32px rgba(0,0,0,.35);">
        <div style="display:flex;align-items:flex-start;gap:12px;">
            <div style="width:36px;height:36px;border-radius:50%;background:#f59e0b22;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg style="width:18px;height:18px;color:#f59e0b" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div style="flex:1">
                <p style="color:#f1f5f9;font-size:13px;font-weight:600;margin:0 0 4px">Sesión por expirar</p>
                <p style="color:#94a3b8;font-size:12px;margin:0 0 12px">Tu sesión cerrará en <strong x-text="countdown" style="color:#f59e0b"></strong> segundos por inactividad.</p>
                <button @click="keepAlive()"
                        style="width:100%;background:#3b82f6;color:#fff;border:none;border-radius:8px;padding:7px 0;font-size:12px;font-weight:600;cursor:pointer;">
                    Mantener sesión activa
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function sessionTimeoutComercial() {
    const WARN_AT  = 25 * 60;
    const CLOSE_AT = 30 * 60;
    const LOGOUT   = '{{ route("bixosales.logout") }}';
    const TOKEN    = document.querySelector('meta[name="csrf-token"]')?.content;

    return {
        warning: false,
        countdown: 300,
        _elapsed: 0,
        _timer: null,
        _cd: null,

        init() {
            this.reset();
            ['mousemove','keydown','click','scroll','touchstart'].forEach(e => {
                document.addEventListener(e, () => this.reset(), { passive: true });
            });
        },

        reset() {
            this._elapsed = 0;
            this.warning  = false;
            this.countdown = 300;
            clearInterval(this._timer);
            clearInterval(this._cd);
            this._timer = setInterval(() => {
                this._elapsed++;
                if (this._elapsed >= CLOSE_AT) { this.logout(); }
                else if (this._elapsed >= WARN_AT && !this.warning) { this.showWarning(); }
            }, 1000);
        },

        showWarning() {
            this.warning   = true;
            this.countdown = CLOSE_AT - this._elapsed;
            clearInterval(this._cd);
            this._cd = setInterval(() => {
                this.countdown = CLOSE_AT - this._elapsed;
                if (this.countdown <= 0) clearInterval(this._cd);
            }, 1000);
        },

        keepAlive() {
            fetch('/ping-session', { method:'POST', headers:{ 'X-CSRF-TOKEN': TOKEN, 'Content-Type':'application/json' } });
            this.reset();
        },

        logout() {
            clearInterval(this._timer);
            clearInterval(this._cd);
            const f = document.createElement('form');
            f.method = 'POST'; f.action = LOGOUT;
            const t = document.createElement('input');
            t.type = 'hidden'; t.name = '_token'; t.value = TOKEN;
            f.appendChild(t);
            const m = document.createElement('input');
            m.type = 'hidden'; m.name = '_inactivity'; m.value = '1';
            f.appendChild(m);
            document.body.appendChild(f);
            f.submit();
        }
    }
}
</script>
</body>
</html>
