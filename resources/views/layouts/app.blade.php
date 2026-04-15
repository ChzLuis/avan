<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($activeProject) ? $activeProject->name . ' — ' : '' }}BIXO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}

        /* Sidebar */
        .sb { transition: width .22s cubic-bezier(.4,0,.2,1); overflow:hidden; }

        /* Nav item base */
        .ni {
            display:flex; align-items:center; gap:9px;
            padding:8px 10px; border-radius:8px;
            font-size:13px; font-weight:500;
            color:#9ca3af; cursor:pointer;
            transition:background .15s, color .15s;
            white-space:nowrap; text-decoration:none;
            width:100%; border:none; background:none;
        }
        .ni:hover { background:rgba(255,255,255,.05); color:#e5e7eb; }
        .ni.active { background:rgba(99,102,241,.15); color:#fff; }
        .ni.active .ni-label { color:#fff; }

        /* Icon wrap */
        .ic-wrap {
            width:30px; height:30px; border-radius:8px;
            display:flex; align-items:center; justify-content:center;
            flex-shrink:0; transition:background .15s;
        }

        /* Section header (colapsable) */
        .sh {
            display:flex; align-items:center; gap:7px;
            padding:5px 8px; border-radius:6px;
            font-size:10px; font-weight:700; letter-spacing:.08em;
            text-transform:uppercase; color:#4b5563;
            cursor:pointer; transition:color .15s, background .15s;
            white-space:nowrap; width:100%; border:none; background:none;
            margin-top:12px; margin-bottom:2px;
        }
        .sh:hover { color:#6b7280; background:rgba(255,255,255,.03); }
        .sh-dot {
            width:5px; height:5px; border-radius:50%;
            flex-shrink:0;
        }

        /* Rail tooltip */
        .rail-tip {
            position:absolute; left:calc(100% + 10px); top:50%;
            transform:translateY(-50%);
            background:#1e2235; color:#e5e7eb;
            font-size:12px; font-weight:500;
            padding:5px 11px; border-radius:7px;
            white-space:nowrap; pointer-events:none;
            border:1px solid #2d3348;
            box-shadow:0 6px 20px rgba(0,0,0,.5);
            opacity:0; transition:opacity .1s;
            z-index:200;
        }
        .ni:hover .rail-tip { opacity:1; }

        /* Leaf items */
        .leaf {
            display:flex; align-items:center;
            padding:6px 8px 6px 44px;
            font-size:12.5px; font-weight:500; color:#6b7280;
            border-radius:7px; white-space:nowrap;
            transition:background .12s, color .12s;
            text-decoration:none; position:relative;
        }
        .leaf:hover { background:rgba(255,255,255,.04); color:#d1d5db; }
        .leaf.active { color:#a5b4fc; background:rgba(99,102,241,.08); }
        .leaf::before {
            content:''; position:absolute; left:28px; top:20%; bottom:20%;
            width:1.5px; background:#2a2f45; border-radius:2px;
        }

        /* Scrollbar */
        nav::-webkit-scrollbar { width:3px; }
        nav::-webkit-scrollbar-track { background:transparent; }
        nav::-webkit-scrollbar-thumb { background:#2d3348; border-radius:2px; }

        /* Mobile bottom nav */
        @media (max-width:1023px) {
            .mob-bottom-nav {
                display:flex; position:fixed; bottom:0; left:0; right:0;
                background:#0b0e17; border-top:1px solid #161927;
                z-index:50; padding:4px 0 env(safe-area-inset-bottom,4px);
            }
            .mob-bottom-nav a {
                flex:1; display:flex; flex-direction:column; align-items:center;
                gap:2px; padding:6px 4px;
                font-size:10px; font-weight:500; color:#4b5563;
                text-decoration:none; transition:color .15s;
            }
            .mob-bottom-nav a.active, .mob-bottom-nav a:hover { color:#818cf8; }
            .mob-main { padding-bottom:60px; }
        }
        @media (min-width:1024px) {
            .mob-bottom-nav { display:none !important; }
        }
    </style>
</head>

<body class="font-sans antialiased bg-gray-50"
      x-data="{
          open: localStorage.getItem('sb_open') !== null
                    ? localStorage.getItem('sb_open') !== 'false'
                    : window.innerWidth >= 1280,
          mob: false,
          isMob(){ return window.innerWidth < 1024 },
          toggleSidebar(){
              if(this.isMob()){ this.mob = !this.mob }
              else { this.open = !this.open; localStorage.setItem('sb_open', this.open) }
          },
          handleResize(){
              if(!this.isMob()){
                  this.mob = false;
                  if(localStorage.getItem('sb_open') === null){
                      this.open = window.innerWidth >= 1280;
                  }
              }
          },
          init(){ this.$watch('open', () => {}); }
      }"
      @resize.window.debounce.150ms="handleResize()">

{{-- Overlay mobile --}}
<div x-show="mob" x-cloak @click="mob=false"
     class="fixed inset-0 bg-black/50 z-40 lg:hidden backdrop-blur-sm"></div>

<div class="flex h-screen overflow-hidden">

{{-- ════════════════════════════════════════════════
     SIDEBAR
════════════════════════════════════════════════ --}}
<aside class="sb flex-shrink-0 flex flex-col h-full z-50 fixed lg:relative lg:translate-x-0"
       :class="{
           'w-[220px]': (open && !isMob()) || mob,
           'w-[52px]':  !open && !isMob(),
           'w-0':       !mob && isMob()
       }"
       style="background:#0b0e17; border-right:1px solid #1a1f2e;">

    {{-- ── Logo ── --}}
    <div class="flex items-center flex-shrink-0 px-4 gap-3"
         style="height:56px; min-height:56px; border-bottom:1px solid #1a1f2e;">
        <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>
        <span class="text-white font-bold text-sm tracking-widest" x-show="open || mob" x-cloak>BIXO</span>
        {{-- collapse btn (solo desktop) --}}
        <button @click="open=!open"
                class="ml-auto text-gray-600 hover:text-gray-300 p-1 rounded transition-colors hidden lg:block"
                x-show="open || mob" x-cloak>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
    </div>

    @php
        $pid = $activeProject->id ?? null;

        $isWorkZone = request()->routeIs('pos*')
            || request()->routeIs('orders*')
            || request()->routeIs('quotes*')
            || request()->routeIs('invoices*')
            || request()->routeIs('clients*')
            || request()->routeIs('agenda*')
            || request()->routeIs('catalog')
            || request()->routeIs('products.*')
            || request()->routeIs('services.*')
            || request()->routeIs('categories.*')
            || request()->routeIs('hr.*')
            || request()->routeIs('sedes.*')
            || request()->routeIs('proveedores.*')
            || request()->routeIs('groups.*');

        $navMode = $isWorkZone ? 'work' : 'admin';
    @endphp

    {{-- ── Nav ── --}}
    @php
        $sComActivo  = request()->routeIs('pos*') || request()->routeIs('orders*') || request()->routeIs('quotes*') || request()->routeIs('invoices*');
        $sCatActivo  = request()->routeIs('catalog') || request()->routeIs('products.*') || request()->routeIs('services.*') || request()->routeIs('categories.*') || request()->routeIs('reviews.*');
        $sEmpActivo  = request()->routeIs('clients*') || request()->routeIs('agenda*') || request()->routeIs('hr.*') || request()->routeIs('sedes.*') || request()->routeIs('proveedores.*') || request()->routeIs('groups.*');
        $sCfgActivo  = request()->routeIs('settings*') || request()->routeIs('roles.*') || request()->routeIs('catalogs*') || request()->routeIs('projects.panel*') || request()->routeIs('settings.seo*');
    @endphp
    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-3 px-2"
         x-data="{
             sec: {
                 cfg: {{ $sCfgActivo ? 'true' : 'true' }},
                 emp: {{ $sEmpActivo ? 'true' : 'false' }},
                 cat: {{ $sCatActivo ? 'true' : 'false' }},
                 com: {{ $sComActivo ? 'true' : 'false' }},
                 wa:  {{ request()->routeIs('bixocrm*') ? 'true' : 'false' }},
                 log: false,
             }
         }">

        {{-- ══════════════════════════════════════
             SIDEBAR UNIFICADO
        ══════════════════════════════════════ --}}

        {{-- ▸ CONFIGURACIÓN --}}
        <button @click="sec.cfg=!sec.cfg" class="sh w-full" style="margin-top:0" x-show="open || mob" x-cloak>
            <span class="sh-dot" style="background:#818cf8"></span>
            <span class="flex-1 text-left">Configuración</span>
            <svg class="w-3 h-3 transition-transform flex-shrink-0" :class="sec.cfg?'rotate-90':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        <div x-show="!open && !mob" x-cloak class="my-1.5 mx-3 h-px" style="background:#1e2235;"></div>
        <div x-show="(open || mob) ? sec.cfg : true" class="space-y-0.5 mb-1">
        @php
        $cfgItems = [
            ['l'=>'Negocio',    'h'=>$pid?route('settings'):'#',             'r'=>'settings_only',   'c'=>'#818cf8','cb'=>'rgba(99,102,241,.15)',
             'i'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['l'=>'SEO',        'h'=>$pid?route('settings.seo'):'#',         'r'=>'settings.seo',    'c'=>'#fb923c','cb'=>'rgba(251,146,60,.12)',
             'i'=>'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
            ['l'=>'Diseño',     'h'=>$pid?route('settings.design'):'#',      'r'=>'settings.design', 'c'=>'#a78bfa','cb'=>'rgba(167,139,250,.15)',
             'i'=>'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'],
            ['l'=>'Pagos',      'h'=>$pid?route('settings.payments'):'#',    'r'=>'settings.payments','c'=>'#34d399','cb'=>'rgba(52,211,153,.12)',
             'i'=>'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
            ['l'=>'Módulos',    'h'=>$pid?route('settings.modules'):'#',     'r'=>'settings.modules', 'c'=>'#22d3ee','cb'=>'rgba(6,182,212,.12)',
             'i'=>'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
            ['l'=>'Roles',      'h'=>$pid?route('roles.index'):'#',          'r'=>'roles.index',     'c'=>'#fbbf24','cb'=>'rgba(245,158,11,.12)',
             'i'=>'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'],
            ['l'=>'Canales WA', 'h'=>$pid?route('bots.index'):'#','r'=>'bots.index','c'=>'#25d366','cb'=>'rgba(37,211,102,.12)',
             'i'=>'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
        ];
        @endphp
        @foreach($cfgItems as $item)
        @php
            $ia = match($item['r']) {
                'settings_only'         => request()->routeIs('settings') && !request()->routeIs('settings.*'),
                'settings.seo'          => request()->routeIs('settings.seo*'),
                'settings.design'       => request()->routeIs('settings.design*'),
                'settings.payments'     => request()->routeIs('settings.payments*'),
                'settings.modules'      => request()->routeIs('settings.modules*'),
                'settings.whatsapp'    => request()->routeIs('settings') && request()->get('s') === 'whatsapp',
                default                 => request()->routeIs($item['r'].'*'),
            };
        @endphp
        <a href="{{ $item['h'] }}" class="ni relative {{ $ia?'active':'' }} {{ $item['h']==='#'?'opacity-40 pointer-events-none':'' }}">
            <span class="ic-wrap" style="background:{{ $ia ? $item['cb'] : 'transparent' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $ia ? $item['c'] : '#4b5563' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $item['i'] }}"/>
                </svg>
            </span>
            <span class="ni-label" x-show="open || mob" x-cloak>{{ $item['l'] }}</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>{{ $item['l'] }}</span>
        </a>
        @endforeach
        </div>

        {{-- ▸ MI EMPRESA --}}
        <div x-show="open || mob" x-cloak class="my-1.5 mx-3 h-px" style="background:#1e2235;"></div>
        <button @click="sec.emp=!sec.emp" class="sh w-full" x-show="open || mob" x-cloak>
            <span class="sh-dot" style="background:#38bdf8"></span>
            <span class="flex-1 text-left">Mi empresa</span>
            <svg class="w-3 h-3 transition-transform flex-shrink-0" :class="sec.emp?'rotate-90':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        <div x-show="!open && !mob" x-cloak class="my-1.5 mx-3 h-px" style="background:#1e2235;"></div>
        <div x-show="(open || mob) ? sec.emp : true" class="space-y-0.5 mb-1">

        {{-- Clientes --}}
        @if($activeProject && $activeProject->hasModule('clients'))
        @php $cliA = request()->routeIs('clients*'); @endphp
        <a href="{{ $pid?route('clients'):'#' }}" class="ni relative {{ $cliA?'active':'' }}">
            <span class="ic-wrap" style="background:{{ $cliA ? 'rgba(251,113,133,.12)' : 'transparent' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $cliA ? '#fb7185' : '#4b5563' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </span>
            <span class="ni-label" x-show="open || mob" x-cloak>Clientes</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>Clientes</span>
        </a>
        @php $gcA = request()->routeIs('groups.*') && request()->get('type','client')==='client'; @endphp
        <a href="{{ $pid?route('groups.index',['type'=>'client']):'#' }}" class="ni relative {{ $gcA?'active':'' }}">
            <span class="ic-wrap" style="background:{{ $gcA ? 'rgba(251,113,133,.08)' : 'transparent' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $gcA ? '#fb7185' : '#4b5563' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z"/>
                </svg>
            </span>
            <span class="ni-label" x-show="open || mob" x-cloak>Grupos clientes</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>Grupos</span>
        </a>
        @endif

        {{-- Agenda --}}
        @if($activeProject && $activeProject->hasModule('agenda'))
        @php $agA = request()->routeIs('agenda*'); @endphp
        <a href="{{ $pid?route('agenda'):'#' }}" class="ni relative {{ $agA?'active':'' }}">
            <span class="ic-wrap" style="background:{{ $agA ? 'rgba(167,139,250,.15)' : 'transparent' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $agA ? '#a78bfa' : '#4b5563' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </span>
            <span class="ni-label" x-show="open || mob" x-cloak>Agenda</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>Agenda</span>
        </a>
        @endif

        {{-- Empleados --}}
        @if($activeProject && $activeProject->hasModule('hr'))
        @php $hrA = request()->routeIs('hr.*'); @endphp
        <a href="{{ $pid?route('hr.employees.index'):'#' }}" class="ni relative {{ $hrA?'active':'' }}">
            <span class="ic-wrap" style="background:{{ $hrA ? 'rgba(56,189,248,.12)' : 'transparent' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $hrA ? '#38bdf8' : '#4b5563' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </span>
            <span class="ni-label" x-show="open || mob" x-cloak>Empleados</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>Empleados</span>
        </a>
        @php $geA = request()->routeIs('groups.*') && request()->get('type')==='employee'; @endphp
        <a href="{{ $pid?route('groups.index',['type'=>'employee']):'#' }}" class="ni relative {{ $geA?'active':'' }}">
            <span class="ic-wrap" style="background:{{ $geA ? 'rgba(56,189,248,.08)' : 'transparent' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $geA ? '#38bdf8' : '#4b5563' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </span>
            <span class="ni-label" x-show="open || mob" x-cloak>Grupos equipo</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>Grupos</span>
        </a>
        @endif

        {{-- Sedes y Proveedores --}}
        @php $sedA = request()->routeIs('sedes.*'); @endphp
        <a href="{{ $pid?route('sedes.index'):'#' }}" class="ni relative {{ $sedA?'active':'' }}">
            <span class="ic-wrap" style="background:{{ $sedA ? 'rgba(56,189,248,.08)' : 'transparent' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $sedA ? '#38bdf8' : '#4b5563' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                </svg>
            </span>
            <span class="ni-label" x-show="open || mob" x-cloak>Sedes</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>Sedes</span>
        </a>
        @php $provA = request()->routeIs('proveedores.*'); @endphp
        <a href="{{ $pid?route('proveedores.index'):'#' }}" class="ni relative {{ $provA?'active':'' }}">
            <span class="ic-wrap" style="background:{{ $provA ? 'rgba(56,189,248,.08)' : 'transparent' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $provA ? '#38bdf8' : '#4b5563' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </span>
            <span class="ni-label" x-show="open || mob" x-cloak>Proveedores</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>Proveedores</span>
        </a>
        </div>

        {{-- ▸ CATÁLOGO --}}
        <div x-show="open || mob" x-cloak class="my-1.5 mx-3 h-px" style="background:#1e2235;"></div>
        <button @click="sec.cat=!sec.cat" class="sh w-full" x-show="open || mob" x-cloak>
            <span class="sh-dot" style="background:#f97316"></span>
            <span class="flex-1 text-left">Catálogo</span>
            <svg class="w-3 h-3 transition-transform flex-shrink-0" :class="sec.cat?'rotate-90':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        <div x-show="!open && !mob" x-cloak class="my-1.5 mx-3 h-px" style="background:#1e2235;"></div>
        <div x-show="(open || mob) ? sec.cat : true" class="space-y-0.5 mb-1">
        @php $catItems = [
            ['l'=>'Productos',  'r'=>'catalog',          'm'=>'catalog','c'=>'#f97316','cb'=>'rgba(249,115,22,.15)',
             'i'=>'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
            ['l'=>'Servicios',  'r'=>'services.index',   'm'=>'catalog','c'=>'#22d3ee','cb'=>'rgba(6,182,212,.12)',
             'i'=>'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
            ['l'=>'Categorías', 'r'=>'categories.index', 'm'=>'catalog','c'=>'#fb923c','cb'=>'rgba(251,146,60,.15)',
             'i'=>'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
            ['l'=>'Reseñas',    'r'=>'reviews.index',    'm'=>'catalog','c'=>'#a78bfa','cb'=>'rgba(167,139,250,.15)',
             'i'=>'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
        ]; @endphp
        @foreach($catItems as $c)
        @if($activeProject && $activeProject->hasModule($c['m']))
        @php $cA = match($c['r']) {
            'catalog'          => request()->routeIs('catalog') || request()->routeIs('products.*'),
            'services.index'   => request()->routeIs('services.*'),
            'categories.index' => request()->routeIs('categories.*'),
            'reviews.index'    => request()->routeIs('reviews.*'),
            default            => false
        }; @endphp
        <a href="{{ $pid?route($c['r']):'#' }}" class="ni relative {{ $cA?'active':'' }}">
            <span class="ic-wrap" style="background:{{ $cA ? $c['cb'] : 'transparent' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $cA ? $c['c'] : '#4b5563' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $c['i'] }}"/>
                </svg>
            </span>
            <span class="ni-label" x-show="open || mob" x-cloak>{{ $c['l'] }}</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>{{ $c['l'] }}</span>
        </a>
        @endif
        @endforeach
        </div>

        {{-- ▸ COMERCIAL --}}
        <div x-show="open || mob" x-cloak class="my-1.5 mx-3 h-px" style="background:#1e2235;"></div>
        <button @click="sec.com=!sec.com" class="sh w-full" x-show="open || mob" x-cloak>
            <span class="sh-dot" style="background:#34d399"></span>
            <span class="flex-1 text-left">Comercial</span>
            <svg class="w-3 h-3 transition-transform flex-shrink-0" :class="sec.com?'rotate-90':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        <div x-show="!open && !mob" x-cloak class="my-1.5 mx-3 h-px" style="background:#1e2235;"></div>
        <div x-show="(open || mob) ? sec.com : true" class="space-y-0.5 mb-1">
        @php $workComItems = [
            ['l'=>'POS',          'r'=>'pos.index','m'=>'orders','c'=>'#34d399','cb'=>'rgba(52,211,153,.12)',
             'i'=>'M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1-4l-3 3m0 0l-3-3m3 3V4'],
            ['l'=>'Pedidos',      'r'=>'orders',   'm'=>'orders','c'=>'#6ee7b7','cb'=>'rgba(110,231,183,.1)',
             'i'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['l'=>'Cotizaciones', 'r'=>'quotes',   'm'=>'quotes','c'=>'#fbbf24','cb'=>'rgba(245,158,11,.12)',
             'i'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['l'=>'Facturas',     'r'=>'invoices.index','m'=>'invoices','c'=>'#818cf8','cb'=>'rgba(99,102,241,.15)',
             'i'=>'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z'],
        ]; @endphp
        @foreach($workComItems as $w)
        @if($activeProject && $activeProject->hasModule($w['m']))
        @php $wA = match($w['r']) {
            'pos.index'       => request()->routeIs('pos*'),
            'orders'          => request()->routeIs('orders*'),
            'quotes'          => request()->routeIs('quotes*'),
            'invoices.index'  => request()->routeIs('invoices*'),
            default           => false
        }; @endphp
        <a href="{{ $pid?route($w['r']):'#' }}" class="ni relative {{ $wA?'active':'' }}">
            <span class="ic-wrap" style="background:{{ $wA ? $w['cb'] : 'transparent' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $wA ? $w['c'] : '#4b5563' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $w['i'] }}"/>
                </svg>
            </span>
            <span class="ni-label" x-show="open || mob" x-cloak>{{ $w['l'] }}</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>{{ $w['l'] }}</span>
        </a>
        @endif
        @endforeach

        {{-- Propuestas BIXO --}}
        @if($pid)
        @php $propActivo = request()->routeIs('proposals*'); @endphp
        <a href="{{ route('proposals.index') }}" class="ni relative {{ $propActivo?'active':'' }}">
            <span class="ic-wrap" style="background:{{ $propActivo ? 'rgba(29,158,117,.15)' : 'transparent' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $propActivo ? '#1D9E75' : '#4b5563' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </span>
            <span class="ni-label flex items-center gap-2" x-show="open || mob" x-cloak>
                Propuestas BIXO
                <span class="text-xs px-1.5 py-0.5 rounded-full font-bold" style="background:rgba(29,158,117,.2);color:#1D9E75;font-size:9px;">BIXO</span>
            </span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>Propuestas BIXO</span>
        </a>

        {{-- Certificados digitales --}}
        @php $certActivo = request()->routeIs('certificados*'); @endphp
        <a href="{{ route('certificados.index') }}" class="ni relative {{ $certActivo?'active':'' }}">
            <span class="ic-wrap" style="background:{{ $certActivo ? 'rgba(83,74,183,.15)' : 'transparent' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $certActivo ? '#534AB7' : '#4b5563' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
            </span>
            <span class="ni-label" x-show="open || mob" x-cloak>Certificados</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>Certificados</span>
        </a>
        @endif
        </div>

        {{-- ▸ LOGÍSTICA --}}
        <div x-show="open || mob" x-cloak class="my-1.5 mx-3 h-px" style="background:#1e2235;"></div>
        {{-- ▸ COMUNICACIONES --}}
        <button @click="sec.wa=!sec.wa" class="sh w-full" x-show="open || mob" x-cloak>
            <span class="sh-dot" style="background:#25d366"></span>
            <span class="flex-1 text-left">Comunicaciones</span>
            <svg class="w-3 h-3 transition-transform flex-shrink-0" :class="sec.wa?'rotate-90':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        <div x-show="!open && !mob" x-cloak class="my-1.5 mx-3 h-px" style="background:#1e2235;"></div>
        <div x-show="(open || mob) ? sec.wa : true" class="space-y-0.5 mb-1">
            @if($pid)
            <a href="{{ route('bixocrm.bandeja') }}"
               class="ni {{ request()->routeIs('bixocrm.bandeja') || request()->routeIs('bixocrm.poll') || request()->routeIs('bixocrm.mensajes') || request()->routeIs('bixocrm.enviar') ? 'ni-active' : '' }}">
                <span class="ic-wrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#25d366">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </span>
                <span class="ni-label" x-show="open || mob" x-cloak>WhatsApp Bandeja</span>
            </a>
            @endif
        </div>

        <button @click="sec.log=!sec.log" class="sh w-full" x-show="open || mob" x-cloak>
            <span class="sh-dot" style="background:#a78bfa"></span>
            <span class="flex-1 text-left">Logística</span>
            <svg class="w-3 h-3 transition-transform flex-shrink-0" :class="sec.log?'rotate-90':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        <div x-show="!open && !mob" x-cloak class="my-1.5 mx-3 h-px" style="background:#1e2235;"></div>
        <div x-show="(open || mob) ? sec.log : true" class="space-y-0.5 mb-1">
        @foreach([
            ['l'=>'Inventario', 'i'=>'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
            ['l'=>'Despacho',   'i'=>'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1'],
        ] as $li)
        <a href="#" class="ni opacity-40 pointer-events-none">
            <span class="ic-wrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#4b5563">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $li['i'] }}"/>
                </svg>
            </span>
            <span class="ni-label flex items-center gap-2" x-show="open || mob" x-cloak>
                {{ $li['l'] }}
                <span class="text-[9px] bg-white/5 text-gray-600 px-1.5 py-0.5 rounded-full">pronto</span>
            </span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>{{ $li['l'] }}</span>
        </a>
        @endforeach
        </div>

    </nav>

    {{-- ── Mobile bottom nav (solo en cell) ── --}}
    <div class="mob-bottom-nav lg:hidden">
        <a href="{{ $pid?route('pos.index'):'#' }}" class="{{ request()->routeIs('pos*')?'active':'' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1-4l-3 3m0 0l-3-3m3 3V4"/></svg>
            POS
        </a>
        <a href="{{ $pid?route('orders'):'#' }}" class="{{ request()->routeIs('orders*')?'active':'' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Pedidos
        </a>
        <a href="{{ $pid?route('clients'):'#' }}" class="{{ request()->routeIs('clients*')?'active':'' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Clientes
        </a>
        <a href="{{ $pid?route('settings'):'#' }}" class="{{ request()->routeIs('settings*')?'active':'' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Config
        </a>
        <a class="text-gray-600" @click.prevent="mob=!mob" href="#">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            Más
        </a>
    </div>

    {{-- ── User footer ── --}}
    <div class="flex-shrink-0 px-2 py-2.5" style="border-top:1px solid #161927;">
        <div class="flex items-center gap-2">
            @php
                $uName = auth()->user()->name ?? 'U';
                $uInitial = strtoupper(substr($uName,0,1));
                $uColors = ['bg-indigo-600','bg-violet-600','bg-emerald-600','bg-amber-600','bg-rose-600'];
                $uColor  = $uColors[ord($uInitial) % count($uColors)];
            @endphp
            <div class="w-7 h-7 rounded-full {{ $uColor }} flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                {{ $uInitial }}
            </div>
            <div class="flex-1 min-w-0" x-show="open || mob" x-cloak>
                <p class="text-xs font-medium text-gray-300 truncate">{{ $uName }}</p>
                <p class="text-[10px] text-gray-600 truncate">{{ auth()->user()->email ?? '' }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" x-show="open || mob" x-cloak>
                @csrf
                <button type="submit" title="Cerrar sesión"
                        class="text-gray-600 hover:text-red-400 p-1 rounded transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
            {{-- Rail: logout icon only --}}
            <form method="POST" action="{{ route('logout') }}" x-show="!open && !mob" x-cloak>
                @csrf
                <button type="submit" title="Cerrar sesión"
                        class="text-gray-600 hover:text-red-400 p-1 rounded transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- ════════════════════════════════════════════════
     MAIN
════════════════════════════════════════════════ --}}
<div class="flex flex-col flex-1 overflow-hidden min-w-0">

    {{-- ── Header ── --}}
    @php
        $apName    = $activeProject->name ?? 'Negocio';
        $apInits   = strtoupper(substr($apName,0,2));
        $hPalette  = ['#4f46e5','#7c3aed','#0891b2','#059669','#d97706','#dc2626','#db2777'];
        $apBg      = $hPalette[abs(crc32($apName)) % count($hPalette)];
        $uName2    = auth()->user()->name ?? 'Usuario';
        $uEmail2   = auth()->user()->email ?? '';
        $uInit2    = strtoupper(substr($uName2,0,1));
        $uColors2  = ['#4f46e5','#7c3aed','#059669','#d97706','#dc2626'];
        $uBg2      = $uColors2[abs(crc32($uName2)) % count($uColors2)];
        $userProjects = \App\Models\Project::where('owner_id', auth()->id())
            ->orWhereHas('members', fn($q) => $q->where('user_id', auth()->id()))
            ->get();
    @endphp
    <header class="flex-shrink-0 bg-white flex items-center px-5 gap-3 z-30"
            style="height:56px; min-height:56px; border-bottom:1px solid #e5e7eb;">

        {{-- Toggle sidebar --}}
        <button @click="toggleSidebar()"
                class="w-9 h-9 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
            <svg style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Divisor vertical alineado con sidebar --}}
        <div class="w-px h-6 bg-gray-200 flex-shrink-0"></div>

        {{-- Breadcrumb: negocio / página --}}
        <nav class="flex items-center gap-1.5 flex-1 min-w-0">
            @isset($activeProject)
            <a href="{{ route('dashboard') }}"
               class="items-center gap-2 text-gray-500 hover:text-gray-800 transition-colors font-medium truncate hidden sm:flex">
                <div class="w-5 h-5 rounded-md flex items-center justify-center text-white text-[9px] font-bold flex-shrink-0"
                     style="background:{{ $apBg }}">
                    {{ strtoupper(substr($apName,0,1)) }}
                </div>
                <span class="truncate max-w-[140px] text-sm">{{ $apName }}</span>
            </a>
            <svg class="w-3.5 h-3.5 text-gray-300 flex-shrink-0 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            @endisset
            <span class="text-gray-900 font-semibold text-[15px] truncate">{{ $pageTitle ?? 'Panel' }}</span>
        </nav>

        {{-- ── Right zone ── --}}
        <div class="flex items-center gap-1.5 flex-shrink-0">

            {{-- Buscador global (placeholder) --}}
            <button class="hidden lg:flex items-center gap-2.5 h-9 px-3.5 rounded-xl border border-gray-200 text-gray-400
                           hover:border-indigo-300 hover:bg-indigo-50/40 transition-all text-sm"
                    style="min-width:160px; max-width:200px;">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span>Buscar...</span>
                <span class="ml-auto text-[11px] bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded font-mono leading-none">⌘K</span>
            </button>

            {{-- Separador --}}
            <div class="w-px h-6 bg-gray-200 mx-1 hidden lg:block"></div>

            {{-- Notificaciones --}}
            <div x-data="{ nd: false }" class="relative">
                <button @click="nd=!nd"
                        class="w-9 h-9 rounded-xl flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors relative">
                    <svg style="width:19px;height:19px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    {{-- badge --}}
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500 border-2 border-white"></span>
                </button>
                <div x-show="nd" @click.outside="nd=false" x-cloak
                     class="absolute right-0 top-full mt-1.5 w-72 max-w-[calc(100vw-1rem)] bg-white border border-gray-100 rounded-xl shadow-2xl z-50 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                        <span class="text-xs font-semibold text-gray-800">Notificaciones</span>
                        <span class="text-[10px] text-indigo-600 font-medium cursor-pointer hover:underline">Marcar todo leído</span>
                    </div>
                    <div class="py-6 text-center">
                        <svg class="w-8 h-8 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <p class="text-xs text-gray-400">Sin notificaciones nuevas</p>
                    </div>
                </div>
            </div>

            {{-- Switcher de negocio --}}
            <div x-data="{ pd: false }" class="relative">
                <button @click="pd=!pd"
                        class="flex items-center gap-2.5 h-9 pl-2 pr-3 rounded-xl hover:bg-gray-100 transition-colors border border-transparent hover:border-gray-200">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0 shadow-sm"
                         style="background:{{ $apBg }}">
                        {{ $apInits }}
                    </div>
                    <span class="text-sm font-semibold text-gray-700 max-w-[110px] truncate hidden sm:block">{{ $apName }}</span>
                    <svg class="w-3.5 h-3.5 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="pd" @click.outside="pd=false" x-cloak
                     class="absolute right-0 top-full mt-1.5 w-68 bg-white border border-gray-100 rounded-xl shadow-2xl z-50 overflow-hidden"
                     style="width:272px;">
                    {{-- Header del dropdown --}}
                    <div class="px-3 py-2.5 border-b border-gray-100 flex items-center justify-between">
                        <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Mis negocios</span>
                        <a href="{{ route('projects.create') }}" @click="pd=false"
                           class="flex items-center gap-1 text-[11px] text-indigo-600 font-semibold hover:text-indigo-800 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            Nuevo
                        </a>
                    </div>
                    {{-- Lista --}}
                    <div class="py-1 max-h-64 overflow-y-auto">
                        @foreach($userProjects as $p)
                        @php
                            $pBg  = $hPalette[abs(crc32($p->name)) % count($hPalette)];
                            $pIns = strtoupper(substr($p->name,0,2));
                            $isCur = isset($activeProject) && $activeProject->id===$p->id;
                        @endphp
                        <a href="{{ route('workspace.select', $p) }}" @click="pd=false"
                           class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50 transition-colors {{ $isCur ? 'bg-indigo-50/50' : '' }}">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0"
                                 style="background:{{ $pBg }}">
                                {{ $pIns }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-800 truncate">{{ $p->name }}</p>
                                <p class="text-[10px] text-gray-400 mt-px">{{ $p->category ?? 'Negocio' }}</p>
                            </div>
                            @if($isCur)
                            <svg class="w-3.5 h-3.5 text-indigo-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                            @endif
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Separador --}}
            <div class="w-px h-6 bg-gray-200 mx-0.5"></div>

            {{-- Perfil de usuario --}}
            <div x-data="{ ud: false }" class="relative">
                <button @click="ud=!ud"
                        class="flex items-center gap-2.5 h-9 pl-1.5 pr-3 rounded-xl hover:bg-gray-100 transition-colors border border-transparent hover:border-gray-200">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                         style="background:{{ $uBg2 }}">
                        {{ $uInit2 }}
                    </div>
                    <span class="text-sm font-medium text-gray-700 hidden sm:block truncate max-w-[80px]">{{ explode(' ', $uName2)[0] }}</span>
                    <svg class="w-3.5 h-3.5 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="ud" @click.outside="ud=false" x-cloak
                     class="absolute right-0 top-full mt-1.5 w-56 bg-white border border-gray-100 rounded-xl shadow-2xl z-50 overflow-hidden">
                    {{-- Info usuario --}}
                    <div class="px-3 py-3 border-b border-gray-100 flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                             style="background:{{ $uBg2 }}">
                            {{ $uInit2 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-800 truncate">{{ $uName2 }}</p>
                            <p class="text-[10px] text-gray-400 truncate">{{ $uEmail2 }}</p>
                        </div>
                    </div>
                    {{-- Acciones --}}
                    <div class="py-1">
                        <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs text-gray-600 hover:bg-gray-50 transition-colors">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Mi perfil
                        </a>
                        <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs text-gray-600 hover:bg-gray-50 transition-colors">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Preferencias
                        </a>
                    </div>
                    <div class="border-t border-gray-100 py-1">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-xs text-red-500 hover:bg-red-50 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </header>

    {{-- Flash messages --}}
    @if(session('error'))
    <div x-data="{s:true}" x-show="s" x-init="setTimeout(()=>s=false,5000)" x-cloak
         class="fixed top-4 right-4 z-50 max-w-sm bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm shadow-xl flex items-start gap-2">
        <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif
    @if(session('success'))
    <div x-data="{s:true}" x-show="s" x-init="setTimeout(()=>s=false,4000)" x-cloak
         class="fixed top-4 right-4 z-50 max-w-sm bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm shadow-xl flex items-start gap-2">
        <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    {{-- Content --}}
    <div class="flex flex-1 min-h-0 mob-main" style="overflow:hidden;align-items:stretch">
        {{ $slot }}
    </div>
</div>

</div>
</body>
</html>
