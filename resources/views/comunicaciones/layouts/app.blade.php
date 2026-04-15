<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('pageTitle', '') — Comunicaciones</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}
        .nav-item { display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:8px;font-size:13px;color:#94a3b8;font-weight:500;transition:all .15s;text-decoration:none;white-space:nowrap; }
        .nav-item:hover { background:#1e2130;color:#e2e8f0; }
        .nav-active { background:#0d2a1a !important;color:#25d366 !important; }
        .nav-section { font-size:10px;font-weight:700;color:#4b5563;text-transform:uppercase;letter-spacing:.08em;padding:14px 10px 4px; }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50" x-data="{ open:true }">

<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    <aside class="flex-shrink-0 flex flex-col h-full z-50 fixed lg:relative transition-all duration-200"
           :class="open ? 'w-56' : 'w-0 lg:w-14'"
           style="background:#0f1117;border-right:1px solid #1e2130;overflow:hidden;white-space:nowrap;">

        {{-- Logo --}}
        <div class="flex items-center px-3.5 h-12 flex-shrink-0 gap-2" style="border-bottom:1px solid #1e2130;">
            <div class="w-6 h-6 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#25d366;">
                <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <div class="flex flex-col leading-tight">
                <span class="text-white font-bold text-xs">Comunicaciones</span>
                <span class="text-gray-500 text-[10px] truncate max-w-[110px]">{{ $project->name ?? '' }}</span>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto overflow-x-hidden py-2 px-2 space-y-0.5">
            <p class="nav-section">WhatsApp</p>

            <a href="{{ route('bixocrm.bandeja') }}"
               class="nav-item {{ request()->routeIs('bixocrm.bandeja') ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                Bandeja
                @php $noLeidos = \App\Models\WaConversacion::whereIn('wa_canal_id', \App\Models\WaCanal::where('project_id', session('comunicaciones_project_id'))->pluck('id'))->sum('no_leidos'); @endphp
                @if($noLeidos > 0)
                <span class="ml-auto bg-green-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full">{{ $noLeidos }}</span>
                @endif
            </a>

            <p class="nav-section">CRM</p>

            <a href="{{ route('bixocrm.clientes') }}"
               class="nav-item {{ request()->routeIs('bixocrm.clientes') ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Clientes
            </a>

            <p class="nav-section">Automatización</p>

            <a href="{{ route('bixocrm.chatbot') }}"
               class="nav-item {{ request()->routeIs('bixocrm.chatbot') ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v3"/>
                </svg>
                Chatbot
            </a>

            <p class="nav-section">Sistema</p>

            <a href="{{ route('bixocrm.configuracion') }}"
               class="nav-item {{ request()->routeIs('bixocrm.configuracion') ? 'nav-active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Canales / Config
            </a>

        </nav>

        {{-- User --}}
        <div class="flex-shrink-0 px-3 py-2.5" style="border-top:1px solid #1e2130;">
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
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full" style="background:#dcfce7;color:#166534">Comunicaciones</span>
            <span class="text-gray-300">/</span>
            <span class="text-gray-500 text-xs truncate">@yield('pageTitle', '')</span>
            <div class="ml-auto text-xs text-gray-400">{{ $project->name ?? '' }}</div>
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

</body>
</html>
