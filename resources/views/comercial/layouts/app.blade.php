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
    <aside class="flex-shrink-0 flex flex-col h-full z-50 fixed lg:relative transition-all duration-200"
           :class="open ? 'w-56' : 'w-0 lg:w-14'"
           style="background:#0f1117; border-right:1px solid #1e2130; overflow:hidden; white-space:nowrap;">

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

            <a href="{{ route('bixosales.dashboard') }}"
               class="cnav-item {{ request()->routeIs('bixosales.dashboard') ? 'cnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            <p class="cnav-section">Operaciones</p>

            <a href="{{ route('bixosales.pos') }}"
               class="cnav-item {{ request()->routeIs('bixosales.pos*') ? 'cnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1-4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
                POS — Caja
            </a>

            <a href="{{ route('bixosales.pedidos') }}"
               class="cnav-item {{ request()->routeIs('bixosales.pedidos*') && !request()->get('canal') ? 'cnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Pedidos
            </a>

            <a href="{{ route('bixosales.cotizaciones') }}"
               class="cnav-item {{ request()->routeIs('bixosales.cotizaciones*') ? 'cnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Cotizaciones
            </a>

            <p class="cnav-section">WhatsApp</p>

            <a href="{{ route('bixosales.pedidos', ['canal' => 'whatsapp']) }}"
               class="cnav-item {{ request()->routeIs('bixosales.pedidos*') && request()->get('canal') === 'whatsapp' ? 'cnav-active' : '' }}"
               style="{{ request()->routeIs('bixosales.pedidos*') && request()->get('canal') === 'whatsapp' ? '' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    <path fill="currentColor" d="M12 0C5.373 0 0 5.373 0 12c0 2.117.554 4.103 1.523 5.824L0 24l6.335-1.502A11.955 11.955 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.885 0-3.652-.49-5.187-1.346l-.372-.214-3.762.892.924-3.67-.234-.388A9.954 9.954 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                </svg>
                Pedidos Bot
            </a>

            <p class="cnav-section">Documentos</p>

            <a href="{{ route('bixosales.facturas') }}"
               class="cnav-item {{ request()->routeIs('bixosales.facturas*') ? 'cnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                </svg>
                Facturas / Boletas
            </a>

            <p class="cnav-section">CRM</p>

            <a href="{{ route('bixosales.clientes') }}"
               class="cnav-item {{ request()->routeIs('bixosales.clientes*') ? 'cnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Clientes
            </a>

            <p class="cnav-section">Sistema</p>

            <a href="{{ route('dashboard') }}"
               class="cnav-item">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Configuración
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
    <div class="flex flex-col flex-1 overflow-hidden min-w-0">

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
</body>
</html>
