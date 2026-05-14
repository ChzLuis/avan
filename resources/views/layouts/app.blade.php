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

        /* ── Sidebar ── */
        .sb { transition: width .22s cubic-bezier(.4,0,.2,1); overflow:hidden; }

        /* ── Nav item ── */
        .ni {
            position:relative;
            display:flex; align-items:center; gap:9px;
            padding:7px 10px 7px 14px;
            border-radius:7px;
            font-size:13px; font-weight:500;
            color:#94a3b8; cursor:pointer;
            transition:background .12s, color .12s;
            white-space:nowrap; text-decoration:none;
            width:100%; border:none; background:none;
        }
        .ni:hover { background:rgba(255,255,255,.07); color:#e2e8f0; }
        .ni.active {
            background:rgba(99,102,241,.18);
            color:#fff;
            font-weight:600;
        }
        .ni.active::before {
            content:'';
            position:absolute; left:0; top:18%; bottom:18%;
            width:3px; border-radius:0 3px 3px 0;
            background:#818cf8;
        }
        .ni.active .ni-icon { color:#a5b4fc !important; }

        /* ── Section label ── */
        .slabel {
            display:flex; align-items:center; justify-content:space-between;
            font-size:10px; font-weight:700; letter-spacing:.1em;
            text-transform:uppercase; color:#475569;
            padding:3px 10px; margin-top:20px; margin-bottom:3px;
            white-space:nowrap; cursor:pointer;
            border:none; background:none; width:100%;
            transition:color .12s;
        }
        .slabel:hover { color:#64748b; }
        .slabel:first-child { margin-top:8px; }

        /* ── Rail tooltip ── */
        .rail-tip {
            position:absolute; left:calc(100% + 10px); top:50%;
            transform:translateY(-50%);
            background:#0f172a; color:#e2e8f0;
            font-size:12px; font-weight:500;
            padding:5px 11px; border-radius:7px;
            white-space:nowrap; pointer-events:none;
            border:1px solid #1e293b;
            box-shadow:0 6px 20px rgba(0,0,0,.6);
            opacity:0; transition:opacity .1s; z-index:200;
        }
        .ni:hover .rail-tip { opacity:1; }

        /* ── Scrollbar ── */
        nav::-webkit-scrollbar { width:3px; }
        nav::-webkit-scrollbar-track { background:transparent; }
        nav::-webkit-scrollbar-thumb { background:#1e293b; border-radius:2px; }

        /* ── Mobile bottom nav ── */
        @media (max-width:1023px) {
            .mob-bottom-nav {
                display:flex; position:fixed; bottom:0; left:0; right:0;
                background:#0f172a; border-top:1px solid #1e293b;
                z-index:50; padding:4px 0 env(safe-area-inset-bottom,4px);
            }
            .mob-bottom-nav a {
                flex:1; display:flex; flex-direction:column; align-items:center;
                gap:2px; padding:6px 4px;
                font-size:10px; font-weight:500; color:#475569;
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

<body class="font-sans antialiased overflow-hidden" style="background:#f1f5f9"
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
     class="fixed inset-0 bg-black/60 z-40 lg:hidden backdrop-blur-sm"></div>

<div class="flex h-screen overflow-hidden">

{{-- ════════════════════════════════════════════════
     SIDEBAR
════════════════════════════════════════════════ --}}
<aside class="sb flex-shrink-0 flex flex-col h-full z-50 fixed lg:relative lg:translate-x-0"
       :class="{
           'w-[240px]': (open && !isMob()) || mob,
           'w-[52px]':  !open && !isMob(),
           'w-0':       !mob && isMob()
       }"
       style="background:#0f172a; border-right:1px solid #1e293b;">

    {{-- ── Logo / Header sidebar ── --}}
    <div class="flex items-center flex-shrink-0 px-3 gap-3"
         style="height:56px; min-height:56px; border-bottom:1px solid #1e293b;">

        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
             style="background:linear-gradient(135deg,#4f46e5,#7c3aed)">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>

        <div class="flex-1 min-w-0" x-show="open || mob" x-cloak>
            <p class="text-white font-bold text-sm tracking-widest leading-tight">BIXO</p>
            <p class="text-slate-600 text-[10px] leading-tight">Panel de administración</p>
        </div>

        <button @click="open=!open"
                class="hidden lg:flex items-center justify-center w-7 h-7 rounded-md text-slate-600 hover:text-slate-300 hover:bg-white/5 transition-colors flex-shrink-0"
                x-show="open || mob" x-cloak title="Colapsar">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
        </button>
    </div>

    @php
        $pid            = $activeProject->id ?? null;
        $authUser       = auth()->user();
        $isOwnerOrSuper = $authUser?->is_superadmin || ($activeProject && $activeProject->owner_id === $authUser?->id);
        $sComActivo     = request()->routeIs('pos*') || request()->routeIs('orders*') || request()->routeIs('quotes*') || request()->routeIs('invoices*') || request()->routeIs('rifas.index');
        $sCatActivo     = request()->routeIs('catalog') || request()->routeIs('products.*') || request()->routeIs('services.*') || request()->routeIs('categories.*') || request()->routeIs('reviews.*');
        $sEmpActivo     = request()->routeIs('clients*') || request()->routeIs('agenda*') || request()->routeIs('hr.*') || request()->routeIs('sedes.*') || request()->routeIs('proveedores.*') || request()->routeIs('groups.*');
        $sCfgActivo     = request()->routeIs('settings*') || request()->routeIs('roles.*') || request()->routeIs('catalogs*') || request()->routeIs('projects.panel*') || request()->routeIs('settings.seo*');
    @endphp

    {{-- ── Nav ── --}}
    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-2 px-2"
         x-data="{
             sec: {
                 cfg: {{ $sCfgActivo ? 'true' : 'false' }},
                 emp: {{ $sEmpActivo ? 'true' : 'false' }},
                 cat: {{ $sCatActivo ? 'true' : 'false' }},
                 com: {{ ($sComActivo || request()->routeIs('bixocrm*') || request()->routeIs('bot-builder*')) ? 'true' : 'false' }},
                 log: false,
             },
             toggle(key) {
                 const wasOpen = this.sec[key];
                 Object.keys(this.sec).forEach(k => this.sec[k] = false);
                 this.sec[key] = !wasOpen;
             }
         }">

        {{-- ▸ CONFIGURACIÓN --}}
        @php
        $cfgItems = [
            ['l'=>'Negocio',    'h'=>$pid?route('settings'):'#',             'r'=>'settings_only',
             'i'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
             'perm' => 'settings.negocio'],
            ['l'=>'SEO',        'h'=>$pid?route('settings.seo'):'#',         'r'=>'settings.seo',
             'i'=>'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z', 'perm' => null],
            ['l'=>'Diseño',     'h'=>$pid?route('settings.design'):'#',      'r'=>'settings.design',
             'i'=>'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
             'perm' => 'settings.diseno'],
            ['l'=>'Pagos',      'h'=>$pid?route('settings.payments'):'#',    'r'=>'settings.payments',
             'i'=>'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
             'perm' => 'settings.pagos'],
            ['l'=>'Módulos',    'h'=>$pid?route('settings.modules'):'#',     'r'=>'settings.modules',
             'i'=>'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
             'perm' => null],
            ['l'=>'QR',         'h'=>$pid?route('settings.qr'):'#',          'r'=>'settings.qr',
             'i'=>'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z',
             'perm' => null],
            ['l'=>'Roles',      'h'=>$pid?route('roles.index'):'#',          'r'=>'roles.index',
             'i'=>'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
             'perm' => null],
            ['l'=>'Catálogos',  'h'=>$pid?route('catalogs.index'):'#',       'r'=>'catalogs.index',
             'i'=>'M4 6h16M4 10h16M4 14h16M4 18h16', 'perm' => 'settings.catalogos'],
            ['l'=>'Canales WA', 'h'=>$pid?route('bots.index'):'#',           'r'=>'bots.index',
             'i'=>'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
             'perm' => null],
        ];
        $cfgVisible = array_filter($cfgItems, function($item) use ($isOwnerOrSuper, $authUser) {
            if ($isOwnerOrSuper) return true;
            return $item['perm'] && $authUser?->can($item['perm']);
        });
        @endphp

        @if(count($cfgVisible) > 0)
        {{-- Label sección colapsable --}}
        <button @click="toggle('cfg')" class="slabel w-full flex items-center justify-between pr-2 hover:text-slate-400 transition-colors" x-show="open || mob" x-cloak>
            <span>Configuración</span>
            <svg class="w-3 h-3 transition-transform duration-150" :class="sec.cfg?'rotate-90':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        <div class="my-1 mx-2 h-px" style="background:#1e293b" x-show="!open && !mob" x-cloak></div>

        <div class="space-y-0.5" x-show="(open || mob) ? sec.cfg : true" x-collapse>
        @foreach($cfgVisible as $item)
        @php
            $ia = match($item['r']) {
                'settings_only'     => request()->routeIs('settings') && !request()->routeIs('settings.*'),
                'settings.seo'      => request()->routeIs('settings.seo*'),
                'settings.design'   => request()->routeIs('settings.design*'),
                'settings.payments' => request()->routeIs('settings.payments*'),
                'settings.modules'  => request()->routeIs('settings.modules*'),
                'settings.qr'       => request()->routeIs('settings.qr*'),
                'catalogs.index'    => request()->routeIs('catalogs*'),
                default             => request()->routeIs($item['r'].'*'),
            };
        @endphp
        <a href="{{ $item['h'] }}" class="ni relative {{ $ia ? 'active' : '' }} {{ $item['h']==='#' ? 'opacity-40 pointer-events-none' : '' }}">
            <svg class="ni-icon w-4 h-4 flex-shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                 style="color:{{ $ia ? '#fff' : '#475569' }}">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $item['i'] }}"/>
            </svg>
            <span x-show="open || mob" x-cloak>{{ $item['l'] }}</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>{{ $item['l'] }}</span>
        </a>
        @endforeach
        </div>
        @endif

        {{-- ▸ MI EMPRESA --}}
        <button @click="toggle('emp')" class="slabel w-full flex items-center justify-between pr-2 hover:text-slate-400 transition-colors" x-show="open || mob" x-cloak>
            <span>Mi empresa</span>
            <svg class="w-3 h-3 transition-transform duration-150" :class="sec.emp?'rotate-90':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        <div class="my-1 mx-2 h-px" style="background:#1e293b" x-show="!open && !mob" x-cloak></div>
        <div class="space-y-0.5" x-show="(open || mob) ? sec.emp : true" x-collapse>

        @if($activeProject && $activeProject->hasModule('clients') && (auth()->user()?->is_superadmin || $activeProject->owner_id===auth()->id() || auth()->user()?->can('clients.ver')))
        @php $cliA = request()->routeIs('clients*'); @endphp
        <a href="{{ $pid?route('clients'):'#' }}" class="ni relative {{ $cliA?'active':'' }}">
            <svg class="ni-icon w-4 h-4 flex-shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $cliA?'#fff':'#475569' }}">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span x-show="open || mob" x-cloak>Contactos</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>Contactos</span>
        </a>
        @php $gcA = request()->routeIs('groups.*') && request()->get('type','client')==='client'; @endphp
        <a href="{{ $pid?route('groups.index',['type'=>'client']):'#' }}" class="ni relative {{ $gcA?'active':'' }}">
            <svg class="ni-icon w-4 h-4 flex-shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $gcA?'#fff':'#475569' }}">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z"/>
            </svg>
            <span x-show="open || mob" x-cloak>Segmentos</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>Segmentos</span>
        </a>
        @endif

        @if($activeProject && $activeProject->hasModule('agenda') && (auth()->user()?->is_superadmin || $activeProject->owner_id===auth()->id() || auth()->user()?->can('agenda.ver')))
        @php $agA = request()->routeIs('agenda*'); @endphp
        <a href="{{ $pid?route('agenda'):'#' }}" class="ni relative {{ $agA?'active':'' }}">
            <svg class="ni-icon w-4 h-4 flex-shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $agA?'#fff':'#475569' }}">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span x-show="open || mob" x-cloak>Agenda</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>Agenda</span>
        </a>
        @endif

        @if($activeProject && $activeProject->hasModule('hr') && (auth()->user()?->is_superadmin || $activeProject->owner_id===auth()->id() || auth()->user()?->can('hr.ver')))
        @php $hrA = request()->routeIs('hr.*'); @endphp
        <a href="{{ $pid?route('hr.employees.index'):'#' }}" class="ni relative {{ $hrA?'active':'' }}">
            <svg class="ni-icon w-4 h-4 flex-shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $hrA?'#fff':'#475569' }}">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span x-show="open || mob" x-cloak>Equipo</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>Equipo</span>
        </a>
        @php $geA = request()->routeIs('groups.*') && request()->get('type')==='employee'; @endphp
        <a href="{{ $pid?route('groups.index',['type'=>'employee']):'#' }}" class="ni relative {{ $geA?'active':'' }}">
            <svg class="ni-icon w-4 h-4 flex-shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $geA?'#fff':'#475569' }}">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span x-show="open || mob" x-cloak>Áreas</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>Áreas</span>
        </a>
        @endif

        @if($isOwnerOrSuper)
        @php $sedA = request()->routeIs('sedes.*'); @endphp
        <a href="{{ $pid?route('sedes.index'):'#' }}" class="ni relative {{ $sedA?'active':'' }}">
            <svg class="ni-icon w-4 h-4 flex-shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $sedA?'#fff':'#475569' }}">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
            </svg>
            <span x-show="open || mob" x-cloak>Sucursales</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>Sucursales</span>
        </a>
        @php $provA = request()->routeIs('proveedores.*'); @endphp
        <a href="{{ $pid?route('proveedores.index'):'#' }}" class="ni relative {{ $provA?'active':'' }}">
            <svg class="ni-icon w-4 h-4 flex-shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $provA?'#fff':'#475569' }}">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            <span x-show="open || mob" x-cloak>Aliados</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>Aliados</span>
        </a>
        @endif
        </div>

        {{-- ▸ CATÁLOGO --}}
        <button @click="toggle('cat')" class="slabel w-full flex items-center justify-between pr-2 hover:text-slate-400 transition-colors" x-show="open || mob" x-cloak>
            <span>Catálogo</span>
            <svg class="w-3 h-3 transition-transform duration-150" :class="sec.cat?'rotate-90':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        <div class="my-1 mx-2 h-px" style="background:#1e293b" x-show="!open && !mob" x-cloak></div>
        <div class="space-y-0.5" x-show="(open || mob) ? sec.cat : true" x-collapse>
        @php $catItems = [
            ['l'=>'Productos',  'r'=>'products.index',   'm'=>'catalog',
             'i'=>'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
            ['l'=>'Servicios',  'r'=>'services.index',   'm'=>'catalog',
             'i'=>'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
            ['l'=>'Categorias', 'r'=>'categories.index', 'm'=>'catalog',
             'i'=>'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
            ['l'=>'Resenas',    'r'=>'reviews.index',    'm'=>'catalog',
             'i'=>'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
        ]; @endphp
        @foreach($catItems as $c)
        @if($activeProject && $activeProject->hasModule($c['m']) && (auth()->user()?->is_superadmin || $activeProject->owner_id===auth()->id() || auth()->user()?->can('catalog.ver')))
        @php $cA = match($c['r']) {
            'products.index'   => request()->routeIs('products.*'),
            'services.index'   => request()->routeIs('services.*'),
            'categories.index' => request()->routeIs('categories.*'),
            'reviews.index'    => request()->routeIs('reviews.*'),
            default            => false
        }; @endphp
        <a href="{{ $pid?route($c['r']):'#' }}" class="ni relative {{ $cA?'active':'' }}">
            <svg class="ni-icon w-4 h-4 flex-shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $cA?'#fff':'#475569' }}">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $c['i'] }}"/>
            </svg>
            <span x-show="open || mob" x-cloak>{{ $c['l'] }}</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>{{ $c['l'] }}</span>
        </a>
        @endif
        @endforeach
        </div>

        {{-- ▸ COMUNICACIONES --}}
        <button @click="toggle('com')" class="slabel w-full flex items-center justify-between pr-2 hover:text-slate-400 transition-colors" x-show="open || mob" x-cloak>
            <span>Comunicaciones</span>
            <svg class="w-3 h-3 transition-transform duration-150" :class="sec.com?'rotate-90':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        <div class="my-1 mx-2 h-px" style="background:#1e293b" x-show="!open && !mob" x-cloak></div>
        <div class="space-y-0.5" x-show="(open || mob) ? sec.com : true" x-collapse>
        @if($pid && $isOwnerOrSuper)
        @php $bandA = request()->routeIs('bixocrm.bandeja') || request()->routeIs('bixocrm.poll') || request()->routeIs('bixocrm.mensajes') || request()->routeIs('bixocrm.enviar'); @endphp
        <a href="{{ route('bixocrm.bandeja') }}" class="ni relative {{ $bandA?'active':'' }}">
            <svg class="ni-icon w-4 h-4 flex-shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $bandA?'#fff':'#475569' }}">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <span x-show="open || mob" x-cloak>WhatsApp Bandeja</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>WA Bandeja</span>
        </a>
        @php $botA = request()->routeIs('bot-builder*'); @endphp
        <a href="{{ route('bot-builder.index') }}" class="ni relative {{ $botA?'active':'' }}">
            <svg class="ni-icon w-4 h-4 flex-shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:{{ $botA?'#fff':'#475569' }}">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
            <span x-show="open || mob" x-cloak>Constructor Bot</span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>Constructor Bot</span>
        </a>
        @endif
        </div>

        {{-- ▸ LOGÍSTICA --}}
        <button @click="toggle('log')" class="slabel w-full flex items-center justify-between pr-2 hover:text-slate-400 transition-colors" x-show="open || mob" x-cloak>
            <span>Logística</span>
            <svg class="w-3 h-3 transition-transform duration-150" :class="sec.log?'rotate-90':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        <div class="my-1 mx-2 h-px" style="background:#1e293b" x-show="!open && !mob" x-cloak></div>
        <div class="space-y-0.5" x-show="(open || mob) ? sec.log : true" x-collapse>
        @foreach([
            ['l'=>'Inventario', 'i'=>'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
            ['l'=>'Despacho',   'i'=>'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1'],
        ] as $li)
        <a href="#" class="ni opacity-40 pointer-events-none">
            <svg class="ni-icon w-4 h-4 flex-shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#475569">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $li['i'] }}"/>
            </svg>
            <span class="flex items-center gap-2" x-show="open || mob" x-cloak>
                {{ $li['l'] }}
                <span class="text-[9px] bg-white/5 text-slate-600 px-1.5 py-0.5 rounded-full">pronto</span>
            </span>
            <span class="rail-tip" x-show="!open && !mob" x-cloak>{{ $li['l'] }}</span>
        </a>
        @endforeach
        </div>

    </nav>

    {{-- ── Mobile bottom nav ── --}}
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
        <a class="text-slate-600" @click.prevent="mob=!mob" href="#">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            Más
        </a>
    </div>

    {{-- ── User footer ── --}}
    <div class="flex-shrink-0 px-3 py-3" style="border-top:1px solid #1e293b;">
        @php
            $uName    = auth()->user()->name ?? 'Usuario';
            $uInitial = strtoupper(substr($uName,0,1));
            $uColors  = ['#1d4ed8','#6d28d9','#047857','#b45309','#be123c'];
            $uColor   = $uColors[ord($uInitial) % count($uColors)];
        @endphp
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                 style="background:{{ $uColor }}">
                {{ $uInitial }}
            </div>
            <div class="flex-1 min-w-0" x-show="open || mob" x-cloak>
                <p class="text-xs font-semibold text-slate-200 truncate">{{ $uName }}</p>
                <p class="text-[10px] text-slate-500 truncate">{{ auth()->user()->email ?? '' }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" x-show="open || mob" x-cloak>
                @csrf
                <button type="submit" title="Cerrar sesión"
                        class="text-slate-600 hover:text-red-400 p-1 rounded transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
            <form method="POST" action="{{ route('logout') }}" x-show="!open && !mob" x-cloak>
                @csrf
                <button type="submit" title="Cerrar sesión"
                        class="text-slate-600 hover:text-red-400 p-1 rounded transition-colors">
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
        $apName       = $activeProject->name ?? 'Negocio';
        $apInits      = strtoupper(substr($apName,0,2));
        $hPalette     = ['#1d4ed8','#6d28d9','#0e7490','#047857','#b45309','#be123c','#be185d'];
        $apBg         = $hPalette[abs(crc32($apName)) % count($hPalette)];
        $uName2       = auth()->user()->name ?? 'Usuario';
        $uEmail2      = auth()->user()->email ?? '';
        $uInit2       = strtoupper(substr($uName2,0,1));
        $uBg2         = $hPalette[abs(crc32($uName2)) % count($hPalette)];
        $userProjects = \App\Models\Project::where('owner_id', auth()->id())
            ->orWhereHas('members', fn($q) => $q->where('user_id', auth()->id()))
            ->get();
    @endphp

    <header class="flex-shrink-0 flex items-center px-4 gap-3 z-30"
            style="height:56px; min-height:56px; background:#e2e8f0; border-bottom:1px solid #cbd5e1;">

        {{-- Toggle sidebar --}}
        <button @click="toggleSidebar()"
                class="w-8 h-8 rounded flex items-center justify-center text-slate-500 hover:text-slate-800 hover:bg-slate-300/60 transition-colors flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Búsqueda global --}}
        <div class="flex-1 flex items-center justify-center px-4"
             x-data="{
                open: false,
                q: '',
                results: [],
                loading: false,
                selected: -1,
                pages: [
                    { label:'Productos',      url:'{{ route('products.index') }}',    icon:'#' },
                    { label:'Servicios',      url:'{{ route('services.index') }}',    icon:'#' },
                    { label:'Categorias',     url:'{{ route('categories.index') }}',  icon:'#' },
                    { label:'Clientes',       url:'{{ url('/bixoadmin/clients') }}',  icon:'#' },
                    { label:'Propuestas',     url:'{{ route('proposals.index') }}',   icon:'#' },
                    { label:'Configuracion',  url:'{{ route('settings') }}',          icon:'#' },
                    { label:'Diseno',         url:'{{ route('settings.design') }}',   icon:'#' },
                    { label:'Catalogo web',   url:'{{ route('catalogs.index') }}',    icon:'#' },
                    { label:'Roles',          url:'{{ route('roles.index') }}',       icon:'#' },
                    { label:'Proveedores',    url:'{{ route('proveedores.index') }}', icon:'#' },
                ],
                get filtered() {
                    if (!this.q.trim()) return this.pages;
                    const s = this.q.toLowerCase();
                    return this.pages.filter(p => p.label.toLowerCase().includes(s));
                },
                show() { this.open=true; this.q=''; this.selected=-1; this.$nextTick(()=>this.$refs.ginput?.focus()); },
                hide() { this.open=false; this.q=''; },
                go(url) { this.hide(); window.location.href=url; },
                onKey(e) {
                    if (e.key==='ArrowDown') { e.preventDefault(); this.selected=Math.min(this.selected+1, this.filtered.length-1); }
                    else if (e.key==='ArrowUp') { e.preventDefault(); this.selected=Math.max(this.selected-1, -1); }
                    else if (e.key==='Enter' && this.selected>=0) { this.go(this.filtered[this.selected].url); }
                    else if (e.key==='Escape') { this.hide(); }
                }
             }"
             @keydown.window="if(event.key==='/' && !['INPUT','TEXTAREA'].includes(event.target.tagName)){ event.preventDefault(); show(); }">
            {{-- Trigger visible --}}
            <button @click="show()"
                    class="hidden sm:flex items-center gap-2 h-8 px-3 bg-slate-700/50 hover:bg-slate-700/70 border border-slate-600/50 rounded-lg text-slate-400 text-xs transition w-48">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span class="flex-1 text-left">Buscar...</span>
                <kbd class="text-[10px] bg-slate-600/60 px-1.5 py-0.5 rounded font-mono">/</kbd>
            </button>
            {{-- Modal búsqueda --}}
            <div x-show="open" x-cloak class="fixed inset-0 z-[9997] flex items-start justify-center pt-20 px-4">
                <div class="absolute inset-0 bg-black/50" @click="hide()"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">
                    <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input x-ref="ginput" x-model="q" @keydown="onKey($event)"
                               placeholder="Buscar sección..."
                               class="flex-1 text-sm text-gray-800 outline-none placeholder-gray-400">
                        <kbd class="text-[10px] bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded font-mono">ESC</kbd>
                    </div>
                    <div class="py-1 max-h-72 overflow-y-auto">
                        <template x-for="(item, idx) in filtered" :key="item.url">
                            <button @click="go(item.url)"
                                    @mouseenter="selected=idx"
                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-left transition"
                                    :class="selected===idx ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'">
                                <span class="text-base w-5 text-center" x-text="item.icon"></span>
                                <span x-text="item.label" class="font-medium"></span>
                            </button>
                        </template>
                        <div x-show="filtered.length===0" class="px-4 py-6 text-center text-sm text-gray-400">
                            Sin resultados para "<span x-text="q"></span>"
                        </div>
                    </div>
                    <div class="px-4 py-2 border-t border-gray-100 flex items-center gap-4 text-[10px] text-gray-400">
                        <span><kbd class="bg-gray-100 px-1 py-0.5 rounded font-mono">↑↓</kbd> navegar</span>
                        <span><kbd class="bg-gray-100 px-1 py-0.5 rounded font-mono">↵</kbd> ir</span>
                        <span><kbd class="bg-gray-100 px-1 py-0.5 rounded font-mono">ESC</kbd> cerrar</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Right zone ── --}}
        <div class="flex items-center gap-1 flex-shrink-0">

            {{-- Notificaciones --}}
            <div x-data="{
                    nd: false,
                    logs: [],
                    loading: false,
                    selected: null,
                    loaded: false,
                    open() {
                        this.nd = !this.nd;
                        if (this.nd && !this.loaded) this.load();
                    },
                    load() {
                        this.loading = true;
                        fetch('{{ route('notifications.imports') }}')
                            .then(r => r.json())
                            .then(d => { this.logs = d.logs; this.loaded = true; })
                            .finally(() => this.loading = false);
                    }
                 }" class="relative">
                <button @click="open()"
                        class="w-8 h-8 rounded flex items-center justify-center text-slate-500 hover:text-slate-800 hover:bg-slate-300/60 transition-colors relative">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </button>
                <div x-show="nd" @click.outside="nd=false; selected=null" x-cloak
                     class="absolute right-0 top-full mt-1.5 w-80 bg-white border border-gray-100 rounded-xl shadow-2xl z-50 overflow-hidden">
                    {{-- Header --}}
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                        <span class="text-xs font-semibold text-gray-800">Historial de importaciones</span>
                        <button x-show="selected" @click="selected=null"
                                class="text-[10px] text-blue-600 font-medium hover:underline">← Volver</button>
                    </div>

                    {{-- Lista de imports --}}
                    <div x-show="!selected" class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                        {{-- Loading --}}
                        <div x-show="loading" class="py-6 text-center">
                            <svg class="w-5 h-5 text-gray-300 mx-auto animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </div>
                        {{-- Sin logs --}}
                        <div x-show="!loading && logs.length === 0" class="py-6 text-center">
                            <svg class="w-8 h-8 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-xs text-gray-400">Sin importaciones registradas</p>
                        </div>
                        {{-- Filas --}}
                        <template x-for="log in logs" :key="log.id">
                            <button @click="selected=log"
                                    class="w-full flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition-colors text-left">
                                {{-- Ícono estado --}}
                                <div class="flex-shrink-0 mt-0.5">
                                    <span x-show="!log.has_errors"
                                          class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100 text-green-600 text-xs">✓</span>
                                    <span x-show="log.has_errors"
                                          class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-600 text-xs">!</span>
                                </div>
                                {{-- Info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded"
                                              :class="log.type==='products' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'"
                                              x-text="log.type==='products' ? 'Productos' : 'Servicios'"></span>
                                        <span class="text-[10px] text-gray-400" x-text="log.date_diff"></span>
                                    </div>
                                    <p class="text-xs text-gray-700 truncate mt-0.5" x-text="log.filename"></p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-[10px] text-green-600 font-medium" x-text="'+'+log.created+' creados'"></span>
                                        <span class="text-[10px] text-blue-600 font-medium" x-text="log.updated+' actualizados'"></span>
                                        <span x-show="log.has_errors" class="text-[10px] text-amber-600 font-medium" x-text="log.errors.length+' advertencias'"></span>
                                    </div>
                                </div>
                            </button>
                        </template>
                    </div>

                    {{-- Detalle de un import --}}
                    <div x-show="selected" class="max-h-80 overflow-y-auto">
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                            <p class="text-xs font-semibold text-gray-800" x-text="selected?.filename"></p>
                            <p class="text-[10px] text-gray-400 mt-0.5" x-text="selected?.date"></p>
                            <div class="flex items-center gap-3 mt-2">
                                <span class="text-[11px] text-green-600 font-semibold" x-text="'+'+selected?.created+' creados'"></span>
                                <span class="text-[11px] text-blue-600 font-semibold" x-text="selected?.updated+' actualizados'"></span>
                                <span class="text-[11px] text-gray-400 font-semibold" x-text="selected?.skipped+' omitidos'"></span>
                            </div>
                        </div>
                        <div x-show="!selected?.has_errors" class="py-5 text-center">
                            <p class="text-xs text-green-600 font-medium">✓ Importación sin errores</p>
                        </div>
                        <div x-show="selected?.has_errors" class="p-3 space-y-1.5">
                            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wide mb-2">Advertencias</p>
                            <template x-for="(err, idx) in (selected?.errors ?? [])" :key="idx">
                                <div class="text-[11px] text-amber-700 bg-amber-50 border border-amber-100 rounded px-2.5 py-1.5 leading-relaxed" x-text="err"></div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Separador --}}
            <div class="w-px h-5 bg-slate-300 mx-1"></div>

            {{-- Switcher de negocio --}}
            <div x-data="{ pd: false }" class="relative">
                <button @click="pd=!pd"
                        class="flex items-center gap-2 h-8 px-2 rounded hover:bg-slate-300/60 transition-colors">
                    <div class="w-6 h-6 rounded flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0"
                         style="background:{{ $apBg }}">
                        {{ $apInits }}
                    </div>
                    <span class="text-xs font-semibold text-slate-700 max-w-[100px] truncate hidden sm:block">{{ $apName }}</span>
                    <svg class="w-3 h-3 text-slate-500 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="pd" @click.outside="pd=false" x-cloak
                     class="absolute right-0 top-full mt-1.5 w-64 bg-white border border-gray-100 rounded-xl shadow-2xl z-50 overflow-hidden">
                    <div class="px-3 py-2 border-b border-gray-100 flex items-center justify-between">
                        <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Mis negocios</span>
                        <a href="{{ route('projects.create') }}" @click="pd=false"
                           class="flex items-center gap-1 text-[11px] text-blue-600 font-semibold hover:text-blue-800">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            Nuevo
                        </a>
                    </div>
                    <div class="py-1 max-h-64 overflow-y-auto">
                        @foreach($userProjects as $p)
                        @php
                            $pBg  = $hPalette[abs(crc32($p->name)) % count($hPalette)];
                            $pIns = strtoupper(substr($p->name,0,2));
                            $isCur = isset($activeProject) && $activeProject->id===$p->id;
                        @endphp
                        <a href="{{ route('workspace.select', $p) }}" @click="pd=false"
                           class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50 transition-colors {{ $isCur ? 'bg-blue-50' : '' }}">
                            <div class="w-7 h-7 rounded flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0"
                                 style="background:{{ $pBg }}">{{ $pIns }}</div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-800 truncate">{{ $p->name }}</p>
                                <p class="text-[10px] text-gray-400">{{ $p->category ?? 'Negocio' }}</p>
                            </div>
                            @if($isCur)
                            <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                            @endif
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Separador --}}
            <div class="w-px h-5 bg-slate-300 mx-1"></div>

            {{-- Usuario --}}
            <div x-data="{ ud: false }" class="relative">
                <button @click="ud=!ud"
                        class="flex items-center gap-2 h-8 px-2 rounded hover:bg-slate-300/60 transition-colors">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0"
                         style="background:{{ $uBg2 }}">
                        {{ $uInit2 }}
                    </div>
                    <div class="hidden sm:flex flex-col items-start leading-tight">
                        <span class="text-xs font-semibold text-slate-700">{{ explode(' ', $uName2)[0] }}</span>
                        <span class="text-[9px] text-slate-500">{{ $authUser?->is_superadmin ? 'Superadmin' : 'Usuario' }}</span>
                    </div>
                    <svg class="w-3 h-3 text-slate-500 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="ud" @click.outside="ud=false" x-cloak
                     class="absolute right-0 top-full mt-1.5 w-52 bg-white border border-gray-100 rounded-xl shadow-2xl z-50 overflow-hidden">
                    <div class="px-3 py-3 border-b border-gray-100 flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                             style="background:{{ $uBg2 }}">{{ $uInit2 }}</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-800 truncate">{{ $uName2 }}</p>
                            <p class="text-[10px] text-gray-400 truncate">{{ $uEmail2 }}</p>
                        </div>
                    </div>
                    <div class="py-1">
                        <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs text-gray-600 hover:bg-gray-50">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Mi perfil
                        </a>
                    </div>
                    <div class="border-t border-gray-100 py-1">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-xs text-red-500 hover:bg-red-50">
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

    {{-- ── Toast global ── --}}
    <div x-data="{
            toasts: [],
            add(msg, type='success') {
                const id = Date.now();
                this.toasts.push({ id, msg, type });
                setTimeout(() => this.remove(id), 3500);
            },
            remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); }
         }"
         x-init="window.addEventListener('app-toast', e => add(e.detail.msg, e.detail.type || 'success'))"
         class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none">
        <template x-for="t in toasts" :key="t.id">
            <div x-show="true"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-x-4"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0 translate-x-4"
                 class="pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-xl text-sm font-medium border max-w-xs"
                 :class="{
                    'bg-green-50 border-green-200 text-green-800': t.type==='success',
                    'bg-red-50 border-red-200 text-red-800': t.type==='error',
                    'bg-amber-50 border-amber-200 text-amber-800': t.type==='warning',
                    'bg-blue-50 border-blue-200 text-blue-800': t.type==='info',
                 }">
                {{-- ícono --}}
                <svg x-show="t.type==='success'" class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <svg x-show="t.type==='error'" class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <svg x-show="t.type==='warning'" class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <svg x-show="t.type==='info'" class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span x-text="t.msg" class="flex-1"></span>
                <button @click="remove(t.id)" class="opacity-50 hover:opacity-100 transition ml-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>

    {{-- ── Modal de confirmación global ── --}}
    <div x-data="{
            show: false,
            title: '',
            msg: '',
            confirmLabel: 'Eliminar',
            confirmClass: 'bg-red-600 hover:bg-red-700 text-white',
            _resolve: null,
            open(opts) {
                this.title        = opts.title || '¿Confirmar acción?';
                this.msg          = opts.msg || '';
                this.confirmLabel = opts.confirmLabel || 'Confirmar';
                this.confirmClass = opts.confirmClass || 'bg-red-600 hover:bg-red-700 text-white';
                this.show = true;
                return new Promise(r => this._resolve = r);
            },
            confirm() { this.show=false; this._resolve && this._resolve(true);  },
            cancel()  { this.show=false; this._resolve && this._resolve(false); }
         }"
         x-init="window.__confirm = (opts) => open(opts)"
         x-show="show" x-cloak
         class="fixed inset-0 z-[9998] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="cancel()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-start gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900" x-text="title"></h3>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed" x-text="msg"></p>
                </div>
            </div>
            <div class="flex gap-2 justify-end">
                <button @click="cancel()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                    Cancelar
                </button>
                <button @click="confirm()"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition"
                        :class="confirmClass"
                        x-text="confirmLabel">
                </button>
            </div>
        </div>
    </div>

    {{-- Flash messages (sesión) --}}
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

{{-- ── Timeout de sesión ── --}}
<div x-data="sessionTimeout()" x-init="init()" x-cloak>
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
                        style="width:100%;background:#1d4ed8;color:#fff;border:none;border-radius:8px;padding:7px 0;font-size:12px;font-weight:600;cursor:pointer;">
                    Mantener sesión activa
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function sessionTimeout() {
    const WARN_AT  = 25 * 60;
    const CLOSE_AT = 30 * 60;
    const LOGOUT   = '{{ route("logout") }}';
    const TOKEN    = document.querySelector('meta[name="csrf-token"]')?.content;
    return {
        warning: false, countdown: 300, _elapsed: 0, _timer: null, _cd: null,
        init() {
            this.reset();
            ['mousemove','keydown','click','scroll','touchstart'].forEach(e => {
                document.addEventListener(e, () => this.reset(), { passive: true });
            });
        },
        reset() {
            this._elapsed = 0; this.warning = false; this.countdown = 300;
            clearInterval(this._timer); clearInterval(this._cd);
            this._timer = setInterval(() => {
                this._elapsed++;
                if (this._elapsed >= CLOSE_AT) { this.logout(); }
                else if (this._elapsed >= WARN_AT && !this.warning) { this.showWarning(); }
            }, 1000);
        },
        showWarning() {
            this.warning = true; this.countdown = CLOSE_AT - this._elapsed;
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
            clearInterval(this._timer); clearInterval(this._cd);
            const f = document.createElement('form');
            f.method = 'POST'; f.action = LOGOUT;
            const t = document.createElement('input'); t.type='hidden'; t.name='_token'; t.value=TOKEN;
            f.appendChild(t);
            const m = document.createElement('input'); m.type='hidden'; m.name='_inactivity'; m.value='1';
            f.appendChild(m);
            document.body.appendChild(f); f.submit();
        }
    }
}
</script>

</body>
</html>
