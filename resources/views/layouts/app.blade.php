<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($activeProject) ? $activeProject->name . ' — ' : '' }}AVAN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}
        .sb{transition:width .25s cubic-bezier(.4,0,.2,1);overflow:hidden;white-space:nowrap;}
    </style>
</head>

<body class="font-sans antialiased bg-gray-50"
      x-data="{
          open: true,
          mob: false,
          isMobile(){ return window.innerWidth < 1024 },
          toggleSidebar(){
              if(this.isMobile()){ this.mob = !this.mob }
              else { this.open = !this.open }
          }
      }"
      @resize.window="if(!isMobile()){ mob=false }">

{{-- overlay mobile --}}
<div x-show="mob" x-cloak @click="mob=false"
     class="fixed inset-0 bg-black/40 z-40 lg:hidden"></div>

<div class="flex h-screen overflow-hidden">

    {{-- ══════════════════ SIDEBAR ══════════════════ --}}
    <aside class="sb flex-shrink-0 flex flex-col h-full z-50
                  fixed lg:relative
                  lg:translate-x-0"
           :class="{
               'w-56': (open && !isMobile()) || mob,
               'w-0':  (!open && !isMobile()) || (!mob && isMobile())
           }"
           style="background:#0f1117; border-right:1px solid #1e2130;">

        {{-- LOGO --}}
        <div class="flex items-center justify-between px-3.5 h-12 flex-shrink-0"
             style="border-bottom:1px solid #1e2130;">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-md bg-indigo-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <span class="text-white font-semibold text-sm tracking-wide">AVAN</span>
            </div>
            <button @click="open=false; mob=false"
                    class="text-gray-600 hover:text-gray-300 p-1 rounded transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
        </div>

        {{-- NAV --}}
        @php
            $pid = $activeProject->id ?? null;

            // Detectar si estamos en zona de configuración o zona de trabajo
            $isConfigZone = request()->routeIs('dashboard*')
                || request()->routeIs('settings*')
                || request()->routeIs('catalogs*')
                || request()->routeIs('projects.panel*')
                || request()->routeIs('roles.*')
                || request()->routeIs('sedes.*')
                || request()->routeIs('groups.*')
                || request()->routeIs('proveedores.*')
                || request()->routeIs('hr.*');

            $isWorkZone = request()->routeIs('pos*')
                || request()->routeIs('orders*')
                || request()->routeIs('quotes*')
                || request()->routeIs('invoices*')
                || request()->routeIs('clients*')
                || request()->routeIs('agenda*')
                || request()->routeIs('catalog*')
                || request()->routeIs('products.*')
                || request()->routeIs('services.*');

            $navMode = $isWorkZone ? 'work' : 'admin';
        @endphp

        {{-- ── SELECTOR ADMIN / WORK ── --}}
        <div class="flex mx-2 mt-2 mb-1 rounded-lg overflow-hidden border" style="border-color:#1e2130;">
            <a href="{{ $pid ? route('settings',['project'=>$pid]) : route('workspace') }}"
               class="flex-1 text-center py-1.5 text-xs font-semibold transition-colors {{ $navMode==='admin' ? 'bg-indigo-600 text-white' : 'text-gray-500 hover:text-gray-300' }}">
                Admin
            </a>
            <a href="{{ $pid ? route('pos.index',['project'=>$pid]) : '#' }}"
               class="flex-1 text-center py-1.5 text-xs font-semibold transition-colors {{ $navMode==='work' ? 'bg-indigo-600 text-white' : 'text-gray-500 hover:text-gray-300' }}">
                Work
            </a>
        </div>

        <nav class="flex-1 overflow-y-auto overflow-x-hidden py-2 px-2 space-y-0.5"
             x-data="{ sub: '{{ request()->routeIs('sedes.*')||request()->routeIs('proveedores.*') ? 'comp' : (request()->routeIs('groups.*') ? 'grupos' : '') }}',
                        togSub(s){ this.sub = (this.sub===s)?null:s; } }">

            {{-- ══════════════════════════════════════════ --}}
            {{-- ZONA ADMIN — solo configuración            --}}
            {{-- ══════════════════════════════════════════ --}}
            @if($navMode === 'admin')

            {{-- Dashboard --}}
            @if($pid)
            <a href="{{ route('dashboard', ['project'=>$pid]) }}"
               class="nav-item {{ request()->routeIs('dashboard*') ? 'nav-active' : '' }}">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>
            @endif

            {{-- ── Negocio ── --}}
            <p class="nav-section-label">Negocio</p>
            @php $adminItems = [
                ['l'=>'Ajustes',   'h'=> $pid ? route('settings',['project'=>$pid]) : '#',        'r'=>'settings_only', 'i'=>'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'],
                ['l'=>'Diseño',    'h'=> $pid ? route('settings.design',['project'=>$pid]) : '#', 'r'=>'settings.design','i'=>'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'],
                ['l'=>'Módulos',   'h'=> $pid ? route('settings.modules',['project'=>$pid]) : '#','r'=>'settings.modules','i'=>'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                ['l'=>'Roles',     'h'=> $pid ? route('roles.index',['project'=>$pid]) : '#',     'r'=>'roles.index',   'i'=>'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'],
                ['l'=>'Catálogos', 'h'=> $pid ? route('catalogs.index',['project'=>$pid]) : '#',  'r'=>'catalogs',      'i'=>'M4 6h16M4 10h16M4 14h16M4 18h16'],
                ['l'=>'Proyectos', 'h'=> $pid ? route('projects.panel',['project'=>$pid]) : '#',  'r'=>'projects.panel','i'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ]; @endphp
            @foreach($adminItems as $ai)
            @php
                $aiActive = match($ai['r']) {
                    'settings_only'   => request()->routeIs('settings') && !request()->routeIs('settings.*'),
                    'settings.design' => request()->routeIs('settings.design*'),
                    'settings.modules'=> request()->routeIs('settings.modules*'),
                    'catalogs'        => request()->routeIs('catalogs*'),
                    default           => request()->routeIs($ai['r'].'*'),
                };
            @endphp
            <a href="{{ $ai['h'] }}"
               class="nav-child {{ $aiActive ? 'nav-child-active' : '' }} {{ $ai['h']==='#' ? 'opacity-40 pointer-events-none' : '' }}">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $ai['i'] }}"/>
                </svg>
                {{ $ai['l'] }}
            </a>
            @endforeach

            {{-- ── Empresa ── --}}
            <p class="nav-section-label mt-2">Empresa</p>

            {{-- Compañías --}}
            <button @click="togSub('comp')" class="nav-child w-full">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                </svg>
                <span class="flex-1 text-left">Compañías</span>
                <svg class="w-3 h-3 flex-shrink-0 transition-transform" :class="sub==='comp'?'rotate-180':''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="sub==='comp'" x-cloak class="nav-leaf-group">
                <a href="{{ $pid ? route('sedes.index',['project'=>$pid]) : '#' }}"
                   class="nav-leaf {{ request()->routeIs('sedes.*') ? 'nav-child-active' : '' }}">Sedes</a>
                <a href="{{ $pid ? route('proveedores.index',['project'=>$pid]) : '#' }}"
                   class="nav-leaf {{ request()->routeIs('proveedores.*') ? 'nav-child-active' : '' }}">Proveedores</a>
            </div>

            {{-- Empleados --}}
            <a href="{{ $pid ? route('hr.employees.index',['project'=>$pid]) : '#' }}"
               class="nav-child {{ request()->routeIs('hr.*') ? 'nav-child-active' : '' }}">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Empleados
            </a>

            {{-- Grupos --}}
            <button @click="togSub('grupos')" class="nav-child w-full">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z"/>
                </svg>
                <span class="flex-1 text-left">Grupos</span>
                <svg class="w-3 h-3 flex-shrink-0 transition-transform" :class="sub==='grupos'?'rotate-180':''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="sub==='grupos'" x-cloak class="nav-leaf-group">
                @php
                    $groupClientActive   = request()->routeIs('groups.*') && request()->get('type','client') === 'client';
                    $groupEmployeeActive = request()->routeIs('groups.*') && request()->get('type') === 'employee';
                @endphp
                <a href="{{ $pid ? route('groups.index',['project'=>$pid,'type'=>'client']) : '#' }}"
                   class="nav-leaf {{ $groupClientActive ? 'nav-child-active' : '' }}">Grupo de clientes</a>
                <a href="{{ $pid ? route('groups.index',['project'=>$pid,'type'=>'employee']) : '#' }}"
                   class="nav-leaf {{ $groupEmployeeActive ? 'nav-child-active' : '' }}">Grupo de trabajadores</a>
            </div>

            {{-- ── Próximamente ── --}}
            <p class="nav-section-label mt-2">Más</p>
            @foreach([
                ['l'=>'Licencias',   'i'=>'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
                ['l'=>'Config. IA',  'i'=>'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2'],
                ['l'=>'Encuestas',   'i'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
            ] as $ci)
            <a href="#" class="nav-child opacity-40 pointer-events-none">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $ci['i'] }}"/>
                </svg>
                {{ $ci['l'] }}
                <span class="ml-auto text-[9px] bg-white/5 text-gray-600 px-1 rounded">pronto</span>
            </a>
            @endforeach

            {{-- ══════════════════════════════════════════ --}}
            {{-- ZONA WORK — operaciones diarias            --}}
            {{-- ══════════════════════════════════════════ --}}
            @else

            <p class="nav-section-label">Ventas</p>
            @php $workItems = [
                ['l'=>'POS',             'r'=>'pos.index',    'm'=>'orders',  'i'=>'M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1-4l-3 3m0 0l-3-3m3 3V4'],
                ['l'=>'Pedidos',         'r'=>'orders',       'm'=>'orders',  'i'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                ['l'=>'Cotizaciones',    'r'=>'quotes',       'm'=>'quotes',  'i'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['l'=>'Facturas/Boletas','r'=>'invoices.index','m'=>'invoices','i'=>'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z'],
            ]; @endphp
            @foreach($workItems as $w)
            @if($activeProject && $activeProject->hasModule($w['m']))
            @php
                $wActive = match($w['r']) {
                    'orders'        => request()->routeIs('orders*'),
                    'quotes'        => request()->routeIs('quotes*'),
                    'pos.index'     => request()->routeIs('pos*'),
                    'invoices.index'=> request()->routeIs('invoices*'),
                    default         => request()->routeIs($w['r'].'*'),
                };
            @endphp
            <a href="{{ $pid ? route($w['r'],['project'=>$pid]) : '#' }}"
               class="nav-child {{ $wActive ? 'nav-child-active' : '' }}">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $w['i'] }}"/>
                </svg>
                {{ $w['l'] }}
            </a>
            @endif
            @endforeach

            <p class="nav-section-label mt-2">Catálogo</p>
            @php $catItems = [
                ['l'=>'Productos',  'r'=>'catalog',       'm'=>'catalog', 'i'=>'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
                ['l'=>'Servicios',  'r'=>'services.index','m'=>'catalog', 'i'=>'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
            ]; @endphp
            @foreach($catItems as $c)
            @if($activeProject && $activeProject->hasModule($c['m']))
            @php
                $cActive = match($c['r']) {
                    'catalog'       => request()->routeIs('catalog') || request()->routeIs('products.*'),
                    'services.index'=> request()->routeIs('services.*'),
                    default         => false,
                };
            @endphp
            <a href="{{ $pid ? route($c['r'],['project'=>$pid]) : '#' }}"
               class="nav-child {{ $cActive ? 'nav-child-active' : '' }}">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $c['i'] }}"/>
                </svg>
                {{ $c['l'] }}
            </a>
            @endif
            @endforeach

            <p class="nav-section-label mt-2">Clientes</p>
            @if($activeProject && $activeProject->hasModule('clients'))
            <a href="{{ $pid ? route('clients',['project'=>$pid]) : '#' }}"
               class="nav-child {{ request()->routeIs('clients*') ? 'nav-child-active' : '' }}">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Clientes
            </a>
            @endif
            @if($activeProject && $activeProject->hasModule('agenda'))
            <a href="{{ $pid ? route('agenda',['project'=>$pid]) : '#' }}"
               class="nav-child {{ request()->routeIs('agenda*') ? 'nav-child-active' : '' }}">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Agenda
            </a>
            @endif

            @endif {{-- end navMode --}}

        </nav>

        {{-- USER --}}
        <div class="flex-shrink-0 px-3 py-2.5" style="border-top:1px solid #1e2130;">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-gray-300 truncate">{{ auth()->user()->name ?? '' }}</p>
                    <p class="text-[11px] text-gray-600 truncate">{{ auth()->user()->email ?? '' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-600 hover:text-red-400 p-1 rounded transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ══════════════════ MAIN ══════════════════ --}}
    <div class="flex flex-col flex-1 overflow-hidden min-w-0">

        {{-- HEADER --}}
        <header class="flex-shrink-0 h-12 bg-white border-b border-gray-200 flex items-center px-4 gap-3 z-30">

            {{-- Hamburger --}}
            <button @click="toggleSidebar()"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- Breadcrumb --}}
            <div class="flex items-center gap-1.5 text-sm flex-1 min-w-0">
                @isset($activeProject)
                <span class="font-medium text-gray-700 truncate hidden sm:block">{{ $activeProject->name }}</span>
                <span class="text-gray-300 hidden sm:block">/</span>
                @endisset
                <span class="text-gray-400 truncate text-xs">{{ $pageTitle ?? 'Panel' }}</span>
            </div>

            {{-- Switcher + avatar --}}
            <div class="flex items-center gap-2 flex-shrink-0">

                {{-- Project switcher --}}
                <div x-data="{ pd: false }" class="relative">
                    <button @click="pd=!pd"
                            class="flex items-center gap-1.5 h-8 px-2.5 rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition-colors text-sm">
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span class="text-xs font-medium text-gray-600 max-w-[90px] truncate hidden sm:block">{{ $activeProject->name ?? 'Negocio' }}</span>
                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="pd" @click.outside="pd=false" x-cloak
                         class="absolute right-0 top-full mt-1 w-60 bg-white border border-gray-200 rounded-xl shadow-xl z-50 py-1">
                        <p class="px-3 pt-2 pb-1.5 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Cambiar negocio</p>
                        @php
                            $userProjects = \App\Models\Project::where('owner_id', auth()->id())
                                ->orWhereHas('members', fn($q) => $q->where('user_id', auth()->id()))
                                ->get();
                        @endphp
                        @foreach($userProjects as $p)
                        <a href="{{ route('dashboard',['project'=>$p->id]) }}" @click="pd=false"
                           class="flex items-center gap-2.5 px-3 py-2 hover:bg-gray-50 transition-colors {{ isset($activeProject) && $activeProject->id===$p->id ? 'bg-indigo-50/60' : '' }}">
                            <div class="w-6 h-6 rounded-md bg-indigo-600 flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0">
                                {{ strtoupper(substr($p->name,0,2)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-800 truncate">{{ $p->name }}</p>
                                <p class="text-[10px] text-gray-400">{{ $p->category ?? 'Negocio' }}</p>
                            </div>
                            @if(isset($activeProject) && $activeProject->id===$p->id)
                            <svg class="w-3.5 h-3.5 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            @endif
                        </a>
                        @endforeach
                        <div class="border-t border-gray-100 mt-1 pt-1">
                            <a href="{{ route('projects.create') }}" @click="pd=false"
                               class="flex items-center gap-2.5 px-3 py-2 hover:bg-gray-50 transition-colors">
                                <div class="w-6 h-6 rounded-md bg-gray-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </div>
                                <span class="text-xs font-medium text-indigo-600">Nuevo negocio</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Avatar --}}
                <div class="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-semibold">
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
