<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $project->name ?? 'Facturación' }} — BIXO Facturación</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}
        .fnav-item{
            display:flex; align-items:center; gap:8px;
            padding:7px 10px; border-radius:8px;
            font-size:13px; color:#94a3b8; font-weight:500;
            transition:background .15s, color .15s; white-space:nowrap;
            text-decoration:none;
        }
        .fnav-item:hover{ background:#1e2130; color:#e2e8f0; }
        .fnav-active{ background:#1e3a5f !important; color:#60a5fa !important; }
        .fnav-section{ font-size:10px; font-weight:700; color:#4b5563;
            text-transform:uppercase; letter-spacing:.08em;
            padding:14px 10px 4px; }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50"
      x-data="{ open: true, mob: false }"
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
                              d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                </div>
                <div class="flex flex-col leading-tight">
                    <span class="text-white font-bold text-xs">Facturación</span>
                    <span class="text-gray-500 text-[10px] truncate max-w-[110px]">{{ $project->name ?? '' }}</span>
                </div>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto overflow-x-hidden py-2 px-2 space-y-0.5">

            <p class="fnav-section">Principal</p>

            <a href="{{ route('facturacion.dashboard', $project->slug) }}"
               class="fnav-item {{ request()->routeIs('facturacion.dashboard') ? 'fnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            <a href="{{ route('facturacion.pos', $project->slug) }}"
               class="fnav-item {{ request()->routeIs('facturacion.pos') ? 'fnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1-4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
                POS — Caja
            </a>

            <p class="fnav-section">Ventas</p>

            <a href="{{ route('facturacion.pedidos', $project->slug) }}"
               class="fnav-item {{ request()->routeIs('facturacion.pedidos*') ? 'fnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Pedidos
            </a>

            <a href="{{ route('facturacion.cotizaciones', $project->slug) }}"
               class="fnav-item {{ request()->routeIs('facturacion.cotizaciones*') ? 'fnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Cotizaciones
            </a>

            <p class="fnav-section">Documentos</p>

            <a href="{{ route('facturacion.boletas', $project->slug) }}"
               class="fnav-item {{ request()->routeIs('facturacion.boletas*') ? 'fnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                </svg>
                Boletas
                @php $nb = \App\Models\Invoice::where('project_id', $project->id)->where('type','boleta')->count(); @endphp
                @if($nb > 0)
                <span class="ml-auto text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-emerald-900 text-emerald-400">{{ $nb }}</span>
                @endif
            </a>

            <a href="{{ route('facturacion.facturas', $project->slug) }}"
               class="fnav-item {{ request()->routeIs('facturacion.facturas*') ? 'fnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Facturas
                @php $nf = \App\Models\Invoice::where('project_id', $project->id)->where('type','factura')->count(); @endphp
                @if($nf > 0)
                <span class="ml-auto text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-blue-900 text-blue-400">{{ $nf }}</span>
                @endif
            </a>

            <p class="fnav-section">CRM</p>

            <a href="{{ route('facturacion.clientes', $project->slug) }}"
               class="fnav-item {{ request()->routeIs('facturacion.clientes*') ? 'fnav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Clientes
            </a>

        </nav>

        {{-- User --}}
        <div class="flex-shrink-0 px-3 py-2.5" style="border-top:1px solid #1e2130;">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-gray-300 truncate">{{ auth()->user()->name ?? '' }}</p>
                    <p class="text-[11px] text-gray-600 truncate">{{ auth()->user()->email ?? '' }}</p>
                </div>
                <form method="POST" action="{{ route('facturacion.logout', $project->slug) }}">
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
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">Facturación</span>
                <span class="text-gray-300">/</span>
                <span class="text-gray-500 text-xs truncate">{{ $pageTitle ?? 'Dashboard' }}</span>
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

</body>
</html>
