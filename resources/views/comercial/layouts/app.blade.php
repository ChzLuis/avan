@php
$_pid  = session('comercial_project_id');
$_mods = $_pid ? \App\Models\Project::find($_pid)?->modules()->wherePivot('is_active', true)->pluck('modules.key')->toArray() : [];
$_has  = fn(string $key) => in_array($key, $_mods);
$_hasBot    = $_pid ? \App\Models\WaCanal::where('project_id', $_pid)->exists() : false;
$_isGerente = auth()->user()?->hasRole('gerente');

$_cat = $project->category ?? 'default';
$_nav = match(true) {
    in_array($_cat, ['restaurante','cafeteria']) => [
        'pos'         => 'Nueva Orden',
        'pedidos'     => 'Pedidos',
        'cotizaciones'=> false,
        'wa'          => 'Pedidos WhatsApp',
        'top'         => 'Top platos',
        'inventario'  => 'Stock de ingredientes',
        'secVentas'   => 'Operaciones',
        'hasKitchen'  => true,
        'label'       => 'Restaurante',
        'mapaLabel'   => 'Mapa de Mesas',
        'mapaRoute'   => 'bixosales.mesas',
    ],
    in_array($_cat, ['peluqueria','salon_belleza']) => [
        'pos'         => 'Cobrar servicio',
        'pedidos'     => 'Atenciones del día',
        'cotizaciones'=> false,
        'wa'          => 'Citas WhatsApp',
        'top'         => 'Top servicios',
        'inventario'  => 'Stock de productos',
        'secVentas'   => 'Operaciones',
        'hasKitchen'  => false,
        'label'       => 'Salón',
        'mapaLabel'   => false,
        'mapaRoute'   => false,
    ],
    $_cat === 'clinica' => [
        'pos'         => 'Cobrar atención',
        'pedidos'     => 'Atenciones',
        'cotizaciones'=> 'Presupuestos',
        'wa'          => 'Citas WhatsApp',
        'top'         => 'Top tratamientos',
        'inventario'  => 'Stock de insumos',
        'secVentas'   => 'Operaciones',
        'hasKitchen'  => false,
        'label'       => 'Clínica',
        'mapaLabel'   => 'Consultorios',
        'mapaRoute'   => 'bixosales.dashboard',
    ],
    $_cat === 'veterinaria' => [
        'pos'         => 'Cobrar atención',
        'pedidos'     => 'Atenciones',
        'cotizaciones'=> false,
        'wa'          => 'Pedidos WhatsApp',
        'top'         => 'Top productos/servicios',
        'inventario'  => 'Stock de productos',
        'secVentas'   => 'Ventas',
        'hasKitchen'  => false,
        'label'       => 'Veterinaria',
        'mapaLabel'   => false,
        'mapaRoute'   => false,
    ],
    $_cat === 'gimnasio' => [
        'pos'         => 'Cobrar membresía',
        'pedidos'     => 'Cobros del día',
        'cotizaciones'=> false,
        'wa'          => 'Mensajes WhatsApp',
        'top'         => 'Top planes',
        'inventario'  => false,
        'secVentas'   => 'Ventas',
        'hasKitchen'  => false,
        'label'       => 'Gimnasio',
        'mapaLabel'   => false,
        'mapaRoute'   => false,
    ],
    $_cat === 'taller' => [
        'pos'         => 'Nueva orden de trabajo',
        'pedidos'     => 'Órdenes de trabajo',
        'cotizaciones'=> 'Presupuestos',
        'wa'          => 'Pedidos WhatsApp',
        'top'         => 'Top servicios',
        'inventario'  => 'Stock de repuestos',
        'secVentas'   => 'Ventas',
        'hasKitchen'  => false,
        'label'       => 'Taller',
        'mapaLabel'   => false,
        'mapaRoute'   => false,
    ],
    default => [
        'pos'         => 'Punto de Venta',
        'pedidos'     => 'Órdenes',
        'cotizaciones'=> 'Propuestas',
        'wa'          => 'Pedidos WhatsApp',
        'top'         => 'Top Productos',
        'inventario'  => 'Inventario',
        'secVentas'   => 'Ventas',
        'hasKitchen'  => false,
        'label'       => 'Comercial',
        'mapaLabel'   => false,
        'mapaRoute'   => false,
    ],
};
@endphp
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $project->name ?? 'AVAN' }} — Operations</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* ─────────────────────────────────────────
           AVAN OPERATIONS — Design System v2
           Tema: Blanco / Gris claro / Azul corporativo
        ───────────────────────────────────────── */
        [x-cloak] { display: none !important; }

        :root {
            --bg:          #F8F9FB;
            --surface:     #FFFFFF;
            --border:      #E5E8EF;
            --border-dark: #D1D5DE;
            --hover:       #F1F3F8;
            --blue:        #2563EB;
            --blue-light:  #EFF6FF;
            --blue-dark:   #1D4ED8;
            --text:        #111827;
            --muted:       #6B7280;
            --muted-light: #9CA3AF;
            --green:       #10B981;
            --green-bg:    #ECFDF5;
            --yellow:      #F59E0B;
            --yellow-bg:   #FFFBEB;
            --red:         #EF4444;
            --red-bg:      #FEF2F2;
            --purple:      #8B5CF6;
            --purple-bg:   #F5F3FF;
            --shadow-sm:   0 1px 3px rgba(0,0,0,.06);
            --shadow-md:   0 4px 12px rgba(0,0,0,.08);
            --shadow-lg:   0 8px 24px rgba(0,0,0,.10);
            --sidebar-w:   56px;
            --topbar-h:    52px;
            --panel-w:     272px;
        }

        * { box-sizing: border-box; }
        body {
            background: var(--bg);
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--text);
            margin: 0; padding: 0;
        }

        /* ── Scrollbar delgado ── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border-dark); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--muted-light); }

        /* ── TOPBAR ── */
        #topbar {
            position: fixed; top: 0; left: 0; right: 0;
            height: var(--topbar-h);
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 8px;
            padding: 0 16px 0 calc(var(--sidebar-w) + 16px);
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }

        /* ── SIDEBAR ── */
        #sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w);
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column; align-items: center;
            padding: 12px 0;
            z-index: 101;
            box-shadow: var(--shadow-sm);
        }

        /* ── MAIN AREA ── */
        #main-wrap {
            padding-top: var(--topbar-h);
            padding-left: var(--sidebar-w);
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        #main-content {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            min-width: 0;
        }

        /* ── PANEL DERECHO ── */
        #panel-right {
            width: var(--panel-w);
            flex-shrink: 0;
            background: var(--surface);
            border-left: 1px solid var(--border);
            display: flex; flex-direction: column;
            overflow: hidden;
            transition: width .2s ease, opacity .2s ease;
        }
        #panel-right.hidden-panel {
            width: 0;
            opacity: 0;
            pointer-events: none;
        }

        /* ── Nav íconos sidebar ── */
        .nav-item {
            width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 10px;
            color: var(--muted);
            cursor: pointer;
            transition: background .12s, color .12s;
            text-decoration: none;
            position: relative;
        }
        .nav-item:hover {
            background: var(--hover);
            color: var(--text);
        }
        .nav-item.active {
            background: var(--blue-light);
            color: var(--blue);
        }
        .nav-item svg { width: 20px; height: 20px; }

        /* Tooltip sidebar */
        .nav-item::after {
            content: attr(data-tip);
            position: absolute; left: calc(100% + 10px); top: 50%;
            transform: translateY(-50%);
            background: #1F2937; color: #fff;
            font-size: 12px; font-weight: 500;
            padding: 4px 10px; border-radius: 6px;
            white-space: nowrap;
            pointer-events: none; opacity: 0;
            transition: opacity .12s;
        }
        .nav-item:hover::after { opacity: 1; }

        /* ── Topbar botones ── */
        .top-btn {
            width: 34px; height: 34px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 8px; cursor: pointer;
            color: var(--muted);
            transition: background .12s, color .12s;
            position: relative; flex-shrink: 0;
            background: none; border: none;
        }
        .top-btn:hover { background: var(--hover); color: var(--text); }
        .top-btn svg { width: 18px; height: 18px; }

        /* ── Badge ── */
        .badge {
            position: absolute; top: 3px; right: 3px;
            min-width: 16px; height: 16px; border-radius: 8px;
            font-size: 9px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            padding: 0 3px;
            border: 2px solid var(--surface);
            pointer-events: none;
        }
        .badge-red    { background: var(--red);    color: #fff; }
        .badge-yellow { background: var(--yellow);  color: #fff; }
        .badge-blue   { background: var(--blue);    color: #fff; }

        /* ── Semáforo dots ── */
        .sema {
            width: 9px; height: 9px; border-radius: 50%;
            flex-shrink: 0; display: inline-block;
        }
        .sema-green  { background: var(--green);  box-shadow: 0 0 0 3px #ECFDF5; }
        .sema-yellow { background: var(--yellow); box-shadow: 0 0 0 3px #FFFBEB; }
        .sema-red    { background: var(--red);    box-shadow: 0 0 0 3px #FEF2F2; animation: sema-pulse 1.8s infinite; }

        @keyframes sema-pulse {
            0%, 100% { box-shadow: 0 0 0 3px #FEF2F2; }
            50%       { box-shadow: 0 0 0 5px #FECACA; }
        }

        /* ── Cronómetro ── */
        .timer {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 2px 7px; border-radius: 99px;
            font-size: 11px; font-weight: 600;
        }
        .timer-green  { background: var(--green-bg);  color: #059669; }
        .timer-yellow { background: var(--yellow-bg); color: #D97706; }
        .timer-red    { background: var(--red-bg);    color: #DC2626;
                        animation: timer-pulse 2s infinite; }

        @keyframes timer-pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: .7; }
        }

        /* ── AVAN Score ring ── */
        .score-ring {
            width: 36px; height: 36px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; cursor: pointer;
            background: conic-gradient(var(--blue) calc(87 * 3.6deg), #E5E8EF 0);
        }
        .score-inner {
            width: 26px; height: 26px; border-radius: 50%;
            background: var(--surface);
            display: flex; align-items: center; justify-content: center;
            font-size: 8px; font-weight: 800; color: var(--blue);
        }

        /* ── Objeto Operativo (card) ── */
        .obj-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px;
            min-width: 200px;
            transition: box-shadow .15s, border-color .15s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .obj-card::before {
            content: '';
            position: absolute; left: 0; top: 0; bottom: 0;
            width: 3px;
            border-radius: 12px 0 0 12px;
        }
        .obj-card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--border-dark);
        }
        .obj-card.obj-green::before  { background: var(--green); }
        .obj-card.obj-yellow::before { background: var(--yellow); }
        .obj-card.obj-red::before    { background: var(--red); }
        .obj-card.obj-gray::before   { background: var(--border-dark); }

        /* ── Búsqueda global ── */
        .search-box {
            flex: 1; max-width: 420px;
            display: flex; align-items: center; gap: 8px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 6px 12px;
            transition: border-color .12s, box-shadow .12s;
        }
        .search-box:focus-within {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(37,99,235,.10);
        }
        .search-box input {
            flex: 1; border: none; background: none; outline: none;
            font-size: 13px; color: var(--text);
            font-family: inherit;
        }
        .search-box input::placeholder { color: var(--muted-light); }

        /* ── Panel tabs ── */
        .panel-tab {
            flex: 1; padding: 10px 4px; text-align: center;
            font-size: 11px; font-weight: 600;
            color: var(--muted); cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: color .12s, border-color .12s;
        }
        .panel-tab.active {
            color: var(--blue);
            border-bottom-color: var(--blue);
        }

        /* ── Alerta card panel ── */
        .alert-item {
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 12px;
        }
        .alert-red    { background: var(--red-bg);    border-left: 3px solid var(--red); }
        .alert-yellow { background: var(--yellow-bg); border-left: 3px solid var(--yellow); }
        .alert-green  { background: var(--green-bg);  border-left: 3px solid var(--green); }

        /* ── Section titles ── */
        .section-title {
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .06em;
            color: var(--muted); margin-bottom: 12px;
        }

        /* ── Divisor vertical ── */
        .vdiv {
            width: 1px; height: 20px;
            background: var(--border); flex-shrink: 0;
        }

        /* ── Chip de empresa ── */
        .empresa-chip {
            display: flex; align-items: center; gap: 6px;
            padding: 4px 10px; border-radius: 8px;
            background: var(--hover); cursor: pointer;
            border: 1px solid var(--border);
            font-size: 12px; font-weight: 500; color: var(--text);
            max-width: 140px; white-space: nowrap; overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ── Flash messages ── */
        .flash {
            position: fixed; top: calc(var(--topbar-h) + 12px); right: 16px;
            z-index: 9999;
            padding: 10px 16px; border-radius: 10px;
            font-size: 13px; font-weight: 500;
            box-shadow: var(--shadow-lg);
            max-width: 340px;
        }
        .flash-success { background: var(--green-bg); color: #065F46; border: 1px solid #A7F3D0; }
        .flash-error   { background: var(--red-bg);   color: #991B1B; border: 1px solid #FECACA; }

        /* ── Responsive: tablet ── */
        @media (max-width: 1024px) {
            #panel-right { display: none; }
        }
        @media (max-width: 768px) {
            #topbar { padding-left: calc(var(--sidebar-w) + 8px); padding-right: 8px; }
            .search-box { max-width: none; }
        }
    </style>
</head>
<body class="h-full antialiased"
      x-data="avanLayout()"
      x-init="init()">

{{-- ══════════════════════════════════════
     SIDEBAR
══════════════════════════════════════ --}}
<nav id="sidebar">

    {{-- Logo --}}
    <a href="{{ route('bixosales.dashboard') }}"
       class="flex items-center justify-center w-9 h-9 rounded-xl mb-4 flex-shrink-0"
       style="background: linear-gradient(135deg, #1D4ED8, #2563EB); box-shadow: 0 2px 8px rgba(37,99,235,.35);">
        <span style="color:#fff; font-weight:900; font-size:15px; letter-spacing:-.5px;">A</span>
    </a>

    <div style="width:28px; height:1px; background:var(--border); margin-bottom:8px;"></div>

    {{-- Centro Operativo --}}
    <a href="{{ route('bixosales.dashboard') }}"
       class="nav-item {{ request()->routeIs('bixosales.dashboard') ? 'active' : '' }}"
       data-tip="Centro Operativo">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
    </a>

    {{-- Actividades --}}
    <a href="{{ route('bixosales.pedidos') }}"
       class="nav-item mt-1 {{ request()->routeIs('bixosales.pedidos*') ? 'active' : '' }}"
       data-tip="Actividades">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
        </svg>
    </a>

    {{-- Conversaciones --}}
    @if($_hasBot)
    <a href="{{ route('bixosales.rifas') }}"
       class="nav-item mt-1 {{ request()->routeIs('bixosales.rifas*') ? 'active' : '' }}"
       data-tip="Conversaciones">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
    </a>
    @endif

    {{-- Indicadores --}}
    <a href="{{ route('bixosales.reportes.ventas.general') }}"
       class="nav-item mt-1 {{ request()->routeIs('bixosales.reportes*') ? 'active' : '' }}"
       data-tip="Indicadores">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
    </a>

    <div style="flex:1;"></div>

    {{-- Cambiar Vista --}}
    <button @click="vistaOpen = !vistaOpen"
            class="nav-item"
            :class="vistaOpen ? 'active' : ''"
            data-tip="Cambiar Vista">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
        </svg>
    </button>

    {{-- POS / Acción principal --}}
    @if($_has('orders'))
    <a href="{{ route('bixosales.pos') }}"
       class="nav-item mt-1 {{ request()->routeIs('bixosales.pos*') ? 'active' : '' }}"
       data-tip="{{ $_nav['pos'] }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4"/>
        </svg>
    </a>
    @endif

    <div style="width:28px; height:1px; background:var(--border); margin: 8px 0;"></div>

    {{-- Avatar / logout --}}
    <div class="relative" x-data="{open:false}">
        <button @click="open=!open"
                class="nav-item"
                data-tip="{{ auth()->user()->name ?? 'Usuario' }}">
            <div style="width:28px; height:28px; border-radius:50%;
                        background:var(--blue); color:#fff;
                        display:flex; align-items:center; justify-content:center;
                        font-size:11px; font-weight:700;">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
        </button>
        <div x-show="open" @click.outside="open=false" x-cloak
             style="position:absolute; left:calc(100% + 8px); bottom:0; width:200px;
                    background:var(--surface); border:1px solid var(--border);
                    border-radius:12px; box-shadow:var(--shadow-lg); overflow:hidden; z-index:200;">
            <div style="padding:12px 14px; border-bottom:1px solid var(--border);">
                <p style="font-size:13px; font-weight:600; color:var(--text);">{{ auth()->user()->name ?? '' }}</p>
                <p style="font-size:11px; color:var(--muted);">{{ auth()->user()->email ?? '' }}</p>
            </div>
            <form method="POST" action="{{ route('bixosales.logout') }}">
                @csrf
                <button type="submit"
                        style="width:100%; text-align:left; padding:10px 14px;
                               font-size:13px; color:var(--red); background:none;
                               border:none; cursor:pointer; font-family:inherit;"
                        onmouseover="this.style.background='var(--red-bg)'"
                        onmouseout="this.style.background='none'">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</nav>

{{-- ══════════════════════════════════════
     PANEL CAMBIAR VISTA (flotante)
══════════════════════════════════════ --}}
<div x-show="vistaOpen" @click.outside="vistaOpen=false" x-cloak
     style="position:fixed; left:calc(var(--sidebar-w) + 8px); bottom:60px;
            width:196px; background:var(--surface);
            border:1px solid var(--border); border-radius:12px;
            box-shadow:var(--shadow-lg); z-index:200; overflow:hidden; padding:6px;">
    <p style="font-size:10px; font-weight:700; color:var(--muted);
              text-transform:uppercase; letter-spacing:.06em;
              padding:6px 10px 4px;">Vista</p>
    @foreach([
        ['operativa',  'Centro Operativo', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['comercial',  'Comercial',        'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ['logistica',  'Logística',        'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10h10zM13 8h4l3 3v5h-7V8z'],
        ['gerencial',  'Gerencial',        'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
        ['financiera', 'Financiera',       'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['rrhh',       'RRHH',             'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
    ] as [$vk, $vl, $vp])
    <button @click="vista='{{ $vk }}'; vistaOpen=false"
            style="width:100%; display:flex; align-items:center; gap:8px;
                   padding:7px 10px; border-radius:8px; cursor:pointer;
                   font-size:12px; font-weight:500; border:none; font-family:inherit;
                   text-align:left; transition:background .1s;"
            :style="vista==='{{ $vk }}'
                    ? 'background:var(--blue-light);color:var(--blue);'
                    : 'background:none;color:var(--text);'"
            onmouseover="if(this.getAttribute('data-active')!=='1') this.style.background='var(--hover)'"
            onmouseout="if(this.getAttribute('data-active')!=='1') this.style.background='none'"
            :data-active="vista==='{{ $vk }}' ? '1' : '0'">
        <svg style="width:14px;height:14px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $vp }}"/>
        </svg>
        {{ $vl }}
    </button>
    @endforeach
</div>

{{-- ══════════════════════════════════════
     TOPBAR
══════════════════════════════════════ --}}
<header id="topbar">

    {{-- Breadcrumb rubro --}}
    <span style="font-size:12px; font-weight:600; color:var(--blue);
                 background:var(--blue-light); padding:3px 10px;
                 border-radius:99px; flex-shrink:0; white-space:nowrap;">
        {{ $_nav['label'] }}
    </span>

    @if(isset($pageTitle))
    <svg style="width:14px;height:14px;color:var(--muted);flex-shrink:0;"
         fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span style="font-size:13px; color:var(--muted); white-space:nowrap;">{{ $pageTitle }}</span>
    @endif

    {{-- Búsqueda Global --}}
    <div class="search-box" style="margin: 0 auto;">
        <svg style="width:15px;height:15px;color:var(--muted-light);flex-shrink:0;"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
        </svg>
        <input type="text"
               x-model="searchQ"
               placeholder="Buscar clientes, órdenes, mesas..."
               @keydown.enter="if(searchQ.length>1) window.location='{{ route('bixosales.pedidos') }}?q='+searchQ">
        <kbd style="font-size:10px; color:var(--muted-light); background:var(--bg);
                    border:1px solid var(--border); padding:1px 6px; border-radius:4px;
                    white-space:nowrap;">⌘K</kbd>
    </div>

    {{-- Alertas --}}
    <button class="top-btn" @click="panelTab='alertas'; panelOpen=true" title="Alertas">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span class="badge badge-red">3</span>
    </button>

    {{-- Conversaciones --}}
    @if($_hasBot)
    <a href="{{ route('bixosales.rifas') }}" class="top-btn" title="Conversaciones">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
        <span class="badge badge-blue">5</span>
    </a>
    @endif

    {{-- Pendientes --}}
    <button class="top-btn" @click="panelTab='pendientes'; panelOpen=true" title="Pendientes">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="badge badge-yellow">8</span>
    </button>

    {{-- Panel toggle --}}
    <button class="top-btn" @click="panelOpen=!panelOpen" title="Panel lateral"
            :style="panelOpen ? 'background:var(--blue-light);color:var(--blue);' : ''">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M4 6h16M4 12h8m-8 6h16"/>
        </svg>
    </button>

    <div class="vdiv"></div>

    {{-- AVAN Score --}}
    <div style="display:flex; align-items:center; gap:8px; cursor:pointer;"
         title="AVAN Score">
        <div class="score-ring">
            <div class="score-inner">87</div>
        </div>
        <div style="display:flex; flex-direction:column; line-height:1.2;">
            <span style="font-size:13px; font-weight:700; color:var(--blue);">87/100</span>
            <span style="font-size:10px; color:var(--muted);">AVAN Score</span>
        </div>
    </div>

    <div class="vdiv"></div>

    {{-- Empresa --}}
    <div class="empresa-chip">
        <div style="width:20px; height:20px; border-radius:6px; flex-shrink:0;
                    background:var(--blue); color:#fff;
                    display:flex; align-items:center; justify-content:center;
                    font-size:10px; font-weight:700;">
            {{ strtoupper(substr($project->name ?? 'A', 0, 1)) }}
        </div>
        <span>{{ $project->name ?? '' }}</span>
    </div>
</header>

{{-- ══════════════════════════════════════
     FLASH MESSAGES
══════════════════════════════════════ --}}
@if(session('error'))
<div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,4500)" x-cloak
     class="flash flash-error">
    {{ session('error') }}
</div>
@endif
@if(session('success'))
<div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,4500)" x-cloak
     class="flash flash-success">
    {{ session('success') }}
</div>
@endif

{{-- ══════════════════════════════════════
     MAIN WRAPPER
══════════════════════════════════════ --}}
<div id="main-wrap">

    {{-- Contenido principal --}}
    <div id="main-content">
        {{ $slot }}
    </div>

    {{-- ══════════════════════════════════════
         PANEL DERECHO
    ══════════════════════════════════════ --}}
    <div id="panel-right" :class="panelOpen ? '' : 'hidden-panel'">

        {{-- Tabs --}}
        <div style="display:flex; border-bottom:1px solid var(--border); flex-shrink:0;">
            <button class="panel-tab" :class="panelTab==='alertas' ? 'active' : ''"
                    @click="panelTab='alertas'">Alertas</button>
            <button class="panel-tab" :class="panelTab==='pendientes' ? 'active' : ''"
                    @click="panelTab='pendientes'">Pendientes</button>
            <button class="panel-tab" :class="panelTab==='actividad' ? 'active' : ''"
                    @click="panelTab='actividad'">Actividad</button>
            <button @click="panelOpen=false"
                    style="padding:8px; color:var(--muted); background:none; border:none; cursor:pointer;">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Contenido del panel --}}
        <div style="flex:1; overflow-y:auto; padding:14px;">

            {{-- ALERTAS --}}
            <div x-show="panelTab==='alertas'">
                <p class="section-title">Alertas activas</p>
                <div class="alert-item alert-red">
                    <div style="display:flex; align-items:center; gap:6px; margin-bottom:4px;">
                        <span class="sema sema-red"></span>
                        <span style="font-weight:600; color:var(--red);">Mesa 3 — 52 min</span>
                    </div>
                    <p style="color:#991B1B; font-size:12px;">Cliente esperando sin atención</p>
                </div>
                <div class="alert-item alert-yellow">
                    <div style="display:flex; align-items:center; gap:6px; margin-bottom:4px;">
                        <span class="sema sema-yellow"></span>
                        <span style="font-weight:600; color:#92400E;">Delivery #45 — 28 min</span>
                    </div>
                    <p style="color:#92400E; font-size:12px;">En ruta, cerca del límite de tiempo</p>
                </div>
                <div class="alert-item alert-yellow">
                    <div style="display:flex; align-items:center; gap:6px; margin-bottom:4px;">
                        <span class="sema sema-yellow"></span>
                        <span style="font-weight:600; color:#92400E;">Stock bajo</span>
                    </div>
                    <p style="color:#92400E; font-size:12px;">Papas fritas — 2 porciones</p>
                </div>
            </div>

            {{-- PENDIENTES --}}
            <div x-show="panelTab==='pendientes'" x-cloak>
                <p class="section-title">Por hacer</p>
                @php
                $pends = [
                    ['Confirmar reserva García 13:00','red'],
                    ['Asignar repartidor Pedido #047','yellow'],
                    ['Arqueo de caja pendiente','red'],
                    ['Responder WhatsApp Ana Torres','blue'],
                    ['Pedido #041 sin cocina','yellow'],
                ];
                @endphp
                @foreach($pends as [$txt,$col])
                <div style="display:flex; align-items:flex-start; gap:8px;
                            padding:8px 10px; border-radius:8px; margin-bottom:6px;
                            border:1px solid var(--border); cursor:pointer;
                            transition:background .1s;"
                     onmouseover="this.style.background='var(--hover)'"
                     onmouseout="this.style.background='none'">
                    <div style="width:7px; height:7px; border-radius:50%; margin-top:4px; flex-shrink:0;
                                background:{{ $col==='red' ? 'var(--red)' : ($col==='yellow' ? 'var(--yellow)' : 'var(--blue)') }};"></div>
                    <span style="font-size:12px; color:var(--text); line-height:1.4;">{{ $txt }}</span>
                </div>
                @endforeach
            </div>

            {{-- ACTIVIDAD --}}
            <div x-show="panelTab==='actividad'" x-cloak>
                <p class="section-title">Reciente</p>
                @php
                $acts = [
                    ['14:23','Pedido #044 completado','green'],
                    ['14:18','Mesa 2 abierta','blue'],
                    ['14:05','Reserva confirmada — García','blue'],
                    ['13:58','Delivery #043 entregado','green'],
                    ['13:45','Caja abierta — S/ 200','blue'],
                    ['13:30','Pedido #040 enviado a cocina','yellow'],
                    ['13:12','Mesa 5 cerrada — S/ 145','green'],
                ];
                @endphp
                @foreach($acts as [$h,$t,$c])
                <div style="display:flex; align-items:flex-start; gap:8px; padding:6px 0;
                            border-bottom:1px solid var(--border);">
                    <span style="font-size:10px; font-family:monospace; color:var(--muted-light);
                                 flex-shrink:0; margin-top:2px; width:36px;">{{ $h }}</span>
                    <div style="width:6px; height:6px; border-radius:50%; margin-top:4px; flex-shrink:0;
                                background:{{ $c==='green' ? 'var(--green)' : ($c==='yellow' ? 'var(--yellow)' : 'var(--blue)') }};"></div>
                    <span style="font-size:12px; color:var(--muted); line-height:1.4;">{{ $t }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Acceso rápido --}}
        <div style="border-top:1px solid var(--border); padding:12px; flex-shrink:0;">
            <p class="section-title">Acceso rápido</p>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:6px;">
                @if($_has('orders'))
                <a href="{{ route('bixosales.pos') }}"
                   style="display:flex; flex-direction:column; align-items:center; gap:4px;
                          padding:8px 4px; border-radius:8px; border:1px solid var(--border);
                          text-decoration:none; transition:background .1s;"
                   onmouseover="this.style.background='var(--hover)'"
                   onmouseout="this.style.background='none'">
                    <svg style="width:16px;height:16px;color:var(--blue);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span style="font-size:10px; color:var(--muted); font-weight:500;">Nueva orden</span>
                </a>
                @endif
                @if($_nav['hasKitchen'])
                <a href="{{ route('bixosales.mesas') }}"
                   style="display:flex; flex-direction:column; align-items:center; gap:4px;
                          padding:8px 4px; border-radius:8px; border:1px solid var(--border);
                          text-decoration:none; transition:background .1s;"
                   onmouseover="this.style.background='var(--hover)'"
                   onmouseout="this.style.background='none'">
                    <svg style="width:16px;height:16px;color:var(--green);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                              d="M3 10h18M3 14h18M10 5v14M14 5v14"/>
                    </svg>
                    <span style="font-size:10px; color:var(--muted); font-weight:500;">Mesas</span>
                </a>
                <a href="{{ route('bixosales.reservas') }}"
                   style="display:flex; flex-direction:column; align-items:center; gap:4px;
                          padding:8px 4px; border-radius:8px; border:1px solid var(--border);
                          text-decoration:none; transition:background .1s;"
                   onmouseover="this.style.background='var(--hover)'"
                   onmouseout="this.style.background='none'">
                    <svg style="width:16px;height:16px;color:var(--yellow);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span style="font-size:10px; color:var(--muted); font-weight:500;">Reservas</span>
                </a>
                @endif
                <a href="{{ route('bixosales.caja') }}"
                   style="display:flex; flex-direction:column; align-items:center; gap:4px;
                          padding:8px 4px; border-radius:8px; border:1px solid var(--border);
                          text-decoration:none; transition:background .1s;"
                   onmouseover="this.style.background='var(--hover)'"
                   onmouseout="this.style.background='none'">
                    <svg style="width:16px;height:16px;color:var(--purple);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <span style="font-size:10px; color:var(--muted); font-weight:500;">Caja</span>
                </a>
                <a href="{{ route('bixosales.reportes.ventas.general') }}"
                   style="display:flex; flex-direction:column; align-items:center; gap:4px;
                          padding:8px 4px; border-radius:8px; border:1px solid var(--border);
                          text-decoration:none; transition:background .1s;"
                   onmouseover="this.style.background='var(--hover)'"
                   onmouseout="this.style.background='none'">
                    <svg style="width:16px;height:16px;color:var(--green);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                              d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    <span style="font-size:10px; color:var(--muted); font-weight:500;">Reportes</span>
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════
     SESSION TIMEOUT  (CHG-92608-19-302127)
     • Modal preventivo: 3 min antes del vencimiento
     • Modal definitivo: al alcanzar el límite de inactividad
══════════════════════════════════════ --}}
<div x-data="sessionWatcher()" x-init="init()">

    {{-- ── Modal preventivo (falta ≤ 3 min) ── --}}
    <div x-show="phase==='warn'" x-cloak
         style="position:fixed;inset:0;z-index:9998;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.35);">
        <div style="background:#fff;border-radius:18px;box-shadow:0 24px 64px rgba(0,0,0,.18);
                    width:100%;max-width:360px;overflow:hidden;"
             @click.stop>

            {{-- Franja superior amarilla --}}
            <div style="background:#FFFBEB;border-bottom:1px solid #FDE68A;padding:20px 22px 16px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:44px;height:44px;min-width:44px;border-radius:12px;background:#FEF3C7;
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:22px;line-height:1;">
                        ⏰
                    </div>
                    <div>
                        <p style="font-size:15px;font-weight:700;color:#92400E;margin:0;">Sesión por expirar</p>
                        <p style="font-size:12px;color:#B45309;margin:2px 0 0;">Inactividad detectada</p>
                    </div>
                </div>
            </div>

            {{-- Cuerpo --}}
            <div style="padding:18px 22px 20px;">
                <p style="font-size:13px;color:#374151;margin:0 0 16px;line-height:1.5;">
                    Tu sesión se cerrará automáticamente en
                    <strong x-text="fmtCd()" style="color:#EF4444;font-size:15px;"></strong>
                    por inactividad.
                </p>

                {{-- Barra de progreso --}}
                <div style="width:100%;height:5px;background:#E5E8EF;border-radius:99px;overflow:hidden;margin-bottom:18px;">
                    <div :style="'width:' + cdPct + '%;background:#F59E0B;height:100%;border-radius:99px;transition:width 1s linear;'"
                         style="height:100%;border-radius:99px;"></div>
                </div>

                <div style="display:flex;gap:8px;">
                    <button @click="logout()"
                            style="flex:1;padding:9px;border-radius:9px;font-size:12px;font-weight:600;
                                   background:#FEE2E2;color:#EF4444;border:1px solid #FECACA;cursor:pointer;font-family:inherit;">
                        Cerrar sesión
                    </button>
                    <button @click="keep()"
                            style="flex:2;padding:9px;border-radius:9px;font-size:13px;font-weight:700;
                                   background:#2563EB;color:#fff;border:none;cursor:pointer;font-family:inherit;">
                        Continuar trabajando
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Modal definitivo (sesión expirada) ── --}}
    <div x-show="phase==='expired'" x-cloak
         style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.60);">
        <div style="background:#fff;border-radius:18px;box-shadow:0 24px 64px rgba(0,0,0,.25);
                    width:100%;max-width:340px;overflow:hidden;text-align:center;">

            {{-- Ícono rojo --}}
            <div style="padding:28px 22px 16px;">
                <div style="width:60px;height:60px;min-width:60px;border-radius:50%;background:#FEF2F2;
                            display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:28px;line-height:1;">
                    🔒
                </div>
                <p style="font-size:17px;font-weight:800;color:#111827;margin:0 0 8px;">Sesión cerrada</p>
                <p style="font-size:13px;color:#6B7280;margin:0 0 22px;line-height:1.5;">
                    Tu sesión fue cerrada por inactividad.<br>Debes autenticarte nuevamente para continuar.
                </p>
                <button @click="logout()"
                        style="width:100%;padding:11px;border-radius:10px;font-size:14px;font-weight:700;
                               background:#2563EB;color:#fff;border:none;cursor:pointer;font-family:inherit;">
                    Iniciar sesión
                </button>
            </div>
        </div>
    </div>

</div>

@stack('scripts')

<script>
function avanLayout() {
    return {
        vista:      'operativa',
        vistaOpen:  false,
        panelOpen:  window.innerWidth >= 1024,
        panelTab:   'alertas',
        searchQ:    '',
        init() {
            window.addEventListener('resize', () => {
                this.panelOpen = window.innerWidth >= 1024;
            });
        }
    };
}

function sessionWatcher() {
    // Tiempo de sesión configurado (minutos). Ajustar según config del proyecto.
    const SESSION_MINUTES = 30;
    const LIMIT_S  = SESSION_MINUTES * 60;   // segundos hasta expiración
    const WARN_S   = 3 * 60;                 // mostrar alerta faltando 3 min (CHG-92608)
    const WARN_AT  = LIMIT_S - WARN_S;       // elapsed en que aparece el modal

    const LOGOUT_URL = {!! json_encode(route('bixosales.logout')) !!};
    const TOKEN = () => document.querySelector('meta[name="csrf-token"]')?.content;

    return {
        // phase: 'idle' | 'warn' | 'expired'
        phase:    'idle',
        cd:       WARN_S,     // segundos restantes mostrados en el countdown
        cdPct:    100,        // % para la barra de progreso (100 → 0)
        _tick:    null,
        _elapsed: 0,

        init() {
            this._startTick();
            // Cualquier actividad del usuario reinicia el contador,
            // SOLO si la sesión no ha expirado definitivamente
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
                // Límite alcanzado → cierre definitivo
                clearInterval(this._tick);
                this.phase = 'expired';
                // Auto-submit después de 8 s por si el usuario no hace clic
                setTimeout(() => this.logout(), 8000);
                return;
            }

            if (this._elapsed >= WARN_AT) {
                // Dentro de la ventana de 3 minutos
                this.phase  = 'warn';
                this.cd     = LIMIT_S - this._elapsed;
                this.cdPct  = Math.round((this.cd / WARN_S) * 100);
            }
        },

        _onActivity() {
            // No reiniciar si ya expiró definitivamente
            if (this.phase === 'expired') return;
            // Si estaba en warn pero el usuario se mueve (sin confirmar),
            // no reiniciamos — solo el botón "Continuar" lo hace
            if (this.phase === 'warn') return;
            this._elapsed = 0;
        },

        // Confirmar permanencia — reinicia el contador completo (CHG-92608)
        keep() {
            fetch('/ping-session', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': TOKEN(), 'Content-Type': 'application/json' }
            }).catch(() => {});
            this._elapsed = 0;
            this.phase    = 'idle';
            this.cd       = WARN_S;
            this.cdPct    = 100;
            this._startTick();
        },

        // Formatear countdown como mm:ss
        fmtCd() {
            const m = Math.floor(this.cd / 60);
            const s = this.cd % 60;
            return (m > 0 ? m + ' min ' : '') + s + ' s';
        },

        logout() {
            clearInterval(this._tick);
            const f = document.createElement('form');
            f.method = 'POST'; f.action = LOGOUT_URL;
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = '_token'; inp.value = TOKEN();
            f.appendChild(inp); document.body.appendChild(f); f.submit();
        }
    };
}
</script>
</body>
</html>
