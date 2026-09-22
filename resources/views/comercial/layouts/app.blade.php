@php
$_pid  = session('comercial_project_id');
$_mods = $_pid ? \App\Models\Project::find($_pid)?->modules()->wherePivot('is_active', true)->pluck('modules.key')->toArray() : [];
$_has  = fn(string $key) => in_array($key, $_mods);
$_hasBot    = $_pid ? \App\Modules\Crm\Models\WaCanal::where('project_id', $_pid)->exists() : false;
$_isGerente = auth()->user()?->hasRole('gerente');

// ¿Puede el usuario abrir esto? Mismo criterio que el middleware de la ruta:
// superadmin pasa siempre, y basta con UNO de los permisos alternativos
// (universo canonico "quotes.ver" o heredado "view-quotes").
$_uq  = auth()->user();
$_can = fn (string ...$ps) => (bool) ($_uq?->is_superadmin) || collect($ps)->contains(fn ($p) => (bool) $_uq?->can($p));

    // Que modulos se le ofrecen a este negocio. El criterio vive en
    // App\Support\ModulosPortal porque la misma pregunta se hace en el menu,
    // en los atajos del cajon y en los accesos rapidos del panel.
    $_mod = \App\Support\ModulosPortal::liberados($project, $_uq?->id);

// Lo que la campana tiene que decir. Los tres contadores eran literales
// escritos aqui (`3`, `5`, `8`): un aviso que no se corresponde con nada
// enseña al usuario a ignorar la campana.
$_avisos = \App\Support\AvisosPortal::resumen($project);

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
    in_array($_cat, ['comercial']) => [
        'pos'         => 'Nueva Venta',
        'pedidos'     => 'Ventas',
        'cotizaciones'=> false,
        'wa'          => 'Ventas WhatsApp',
        'top'         => 'Top Productos',
        'inventario'  => 'Inventario',
        'secVentas'   => 'Ventas',
        'hasKitchen'  => false,
        'label'       => 'Comercial',
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
    {{-- Instalable como app en el celular --}}
    <link rel="manifest" href="/manifest-sales.json">
    <meta name="theme-color" content="#1e293b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="/img/pwa/sales-192.png">
    <script>if ('serviceWorker' in navigator) { navigator.serviceWorker.register('/sw.js').catch(() => {}); }</script>
    <title>{{ $project->name ?? 'Panel' }} — Operaciones</title>
    {{-- Sin favicon propio, el navegador reutiliza el ultimo que vio para
         arindg.com —el de otra tienda—, asi que la pestaña de TECSIST salia
         con el icono de un cliente distinto. El panel ya lo resolvia asi;
         este portal se habia quedado fuera.
         Orden: favicon configurado → logo del negocio → icono de la raiz. --}}
    @php
        $_icono = $project->settings()->where('key', 'favicon_url')->value('value')
            ?: $project->logo_url;
    @endphp
    <link rel="icon" href="{{ $_icono
        ? (\Illuminate\Support\Str::startsWith($_icono, ['http://', 'https://'])
            ? $_icono
            : asset('storage/'.ltrim($_icono, '/')))
        : asset('favicon.ico') }}">
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

        /* ── PANEL DERECHO (cajón) ──────────────────────────────────────────
           Antes era una columna dentro del flujo: se llevaba 272 px de ancho
           de forma permanente, estuviera o no mirándolo el usuario, y por
           debajo de 1024 px se ocultaba con display:none, o sea que en tablet
           y movil las alertas NO EXISTIAN. Ahora es un cajon superpuesto: el
           area de trabajo recupera esos 272 px SIEMPRE, y las alertas quedan
           disponibles tambien en pantallas pequenas. Ninguna funcion se quita:
           las tres pestanas y su contenido son los mismos. */
        #panel-right {
            position: fixed;
            top: var(--topbar-h);
            right: 0;
            bottom: 0;
            z-index: 60;
            width: var(--panel-w);
            max-width: 100vw;
            background: var(--surface);
            border-left: 1px solid var(--border);
            box-shadow: -8px 0 24px rgba(15,23,42,.10);
            display: flex; flex-direction: column;
            overflow: hidden;
            transition: transform .22s ease;
        }
        #panel-right.hidden-panel {
            transform: translateX(100%);
            box-shadow: none;
            pointer-events: none;
        }
        /* Velo: cierra al tocar fuera. Solo en pantallas donde el cajon tapa
           trabajo; en escritorio ancho se puede dejar abierto y seguir usando
           la pantalla. */
        #panel-overlay {
            position: fixed;
            top: var(--topbar-h); left: 0; right: 0; bottom: 0;
            z-index: 59;
            background: rgba(15,23,42,.28);
            opacity: 0;
            pointer-events: none;
            transition: opacity .22s ease;
        }
        @media (max-width: 1280px) {
            #panel-overlay.is-open { opacity: 1; pointer-events: auto; }
        }
        /* UX1: en movil el buscador colapsaba a 24px de ancho — inutilizable y
           fuera de contrato. Se oculta; cada modulo tiene su propia busqueda. */
        @media (max-width: 767px) { .search-box { display: none !important; } }

        @media (prefers-reduced-motion: reduce) {
            #panel-right, #panel-overlay { transition: none; }
        }

        /* ── Nav íconos sidebar ── */
        .nav-item {
            /* UX1: objetivo tactil 44 (medidos 40x40 en toda pagina). */
            width: 44px; height: 44px;
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
            position: relative;
            /* UX1: objetivo tactil 44 (medidos 34x34). */
            width: 44px; height: 44px;
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
        /* Con algo critico la campana se nota, pero sin bailar: un halo que
           late dos veces por segundo distrae; este tarda 2 s y se apaga si el
           sistema pide menos movimiento. */
        .top-btn-alerta { color: var(--red); }
        .top-btn-alerta::before {
            content: ''; position: absolute; inset: 2px; border-radius: 10px;
            background: var(--red); opacity: .12; animation: latido 2s ease-in-out infinite;
        }
        @keyframes latido { 0%,100% { opacity: .10; } 50% { opacity: .22; } }
        @media (prefers-reduced-motion: reduce) { .top-btn-alerta::before { animation: none; } }
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
        /* min-width:0 es imprescindible, no cosmetico: sin el, un item flex no
           baja de su ancho minimo de contenido. El buscador se negaba a
           encogerse y empujaba los .top-btn (que son flex-shrink:0) fuera del
           viewport en moviles, dejando ALERTAS y PENDIENTES inalcanzables. */
        .search-box {
            flex: 1; max-width: 420px; min-width: 0;
            display: flex; align-items: center; gap: 8px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 6px 12px;
            overflow: hidden;
            transition: border-color .12s, box-shadow .12s;
        }
        .search-box:focus-within {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(37,99,235,.10);
        }
        /* min-width:0 tambien aqui: sin el, el input no baja de su ancho
           intrinseco y su texto se desborda sobre los iconos de la barra. */
        .search-box input {
            flex: 1; min-width: 0; border: none; background: none; outline: none;
            text-overflow: ellipsis;
            font-size: 13px; color: var(--text);
            font-family: inherit;
        }
        .search-box input::placeholder { color: var(--muted-light); }

        /* ── Panel tabs ── */
        .panel-tab {
            /* UX1: 39px de alto medidos -> minimo tactil. */
            flex: 1; padding: 10px 4px; min-height: 44px; text-align: center;
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

        /* ── Responsive: tablet ──
           Antes: `#panel-right { display:none }` — las alertas desaparecían
           por completo por debajo de 1024 px. Como cajón superpuesto ya no
           estorba, así que se mantienen disponibles en todos los tamaños;
           en móvil ocupa el ancho de la pantalla. */
        @media (max-width: 480px) {
            /* NO 100vw: el cajon se ancla a la derecha, asi que con 100vw su
               borde izquierdo queda bajo el sidebar (56 px, z-index 101) y le
               recorta las primeras letras a cada linea. Se descuenta el
               sidebar para que ocupe exactamente el area util. */
            #panel-right { width: calc(100vw - var(--sidebar-w)); }
        }
        @media (max-width: 768px) {
            #topbar { padding-left: calc(var(--sidebar-w) + 8px); padding-right: 8px; }
            .search-box { max-width: none; }
        }
        /* En un celular de 390 px la barra no cerraba: el chip con el nombre
           del negocio (hasta 140 px) empujaba los botones de alertas y
           pendientes fuera de la pantalla —medido: llegaban a 474 px— y con
           ellos el acceso al panel lateral. El nombre del negocio ya está en
           el menú y en la ficha; aquí basta su inicial. */
        @media (max-width: 767px) {
            /* Fuera del todo: el nombre del negocio ya lo dice el menu
               lateral, y aqui solo le robaba sitio a los botones. */
            .empresa-chip { display: none; }
            .vdiv { display: none; }
            .top-btn { width: 38px; height: 38px; }
            #topbar { gap: 4px; padding-right: 4px; }

            /* ZOOM AL ENFOCAR, DE UNA VEZ PARA TODO EL PORTAL.
               Chrome en Android amplia la pagina al tocar cualquier campo por
               debajo de 16px y deja la pantalla descuadrada, sin forma comoda
               de volver. Cada pantalla lo venia arreglando por su cuenta —y
               varias se quedaron sin hacerlo—, asi que la regla vive aqui: la
               heredan todas las de Ventas, incluidas las que se anadan.
               Solo en movil: en escritorio no ocurre y 16px descuadraria
               rejillas pensadas a 13px. */
            input, select, textarea { font-size: 16px !important; }
        }
        /* ══ MENU LATERAL ══════════════════════════════════════════════
           Dos estados reales. Contraido: solo iconos con tooltip y
           aria-label. Expandido: icono + nombre del modulo. Un rail de
           iconos sin texto obliga a adivinar que hay detras de cada dibujo,
           y quien entra por primera vez no tiene forma de saberlo. La
           eleccion se recuerda en localStorage. */
        #sidebar { transition: width .2s ease; overflow: hidden; }
        .nav-scroll {
            flex: 1; width: 100%; overflow-y: auto; overflow-x: hidden;
            display: flex; flex-direction: column; align-items: center; gap: 2px;
        }
        .nav-pie { width: 100%; display: flex; flex-direction: column; align-items: center; padding-top: 6px; }
        .nav-label { display: none; }
        .nav-grupo { display: none; }
        .nav-sep { width: 28px; height: 1px; background: var(--border); margin: 8px 0 6px; }

        .nav-marca {
            display: flex; align-items: center; gap: 10px; text-decoration: none;
            margin-bottom: 10px; flex-shrink: 0; width: 100%; justify-content: center;
        }
        .nav-marca-cuadro {
            width: 36px; height: 36px; flex-shrink: 0; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #1D4ED8, #2563EB);
            box-shadow: 0 2px 8px rgba(37,99,235,.35);
            color: #fff; font-weight: 900; font-size: 15px; letter-spacing: -.5px;
        }
        body.nav-abierto .nav-marca-bloque,
        #sidebar .nav-marca-bloque.nav-label { display: flex; flex-direction: column; line-height: 1.15; min-width: 0; }
        .nav-marca-texto { font-size: 15px; font-weight: 800; letter-spacing: -.02em; color: var(--text); }
        .nav-marca-empresa {
            font-size: 10px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase;
            color: var(--muted-light); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        /* Estado activo: lo marca la fila entera, no solo el icono. */
        .nav-item.active { background: var(--blue-light); color: var(--blue); font-weight: 600; }
        .nav-item.active .nav-label { color: var(--blue); }

        body.nav-abierto { --sidebar-w: 216px; }
        body.nav-abierto #sidebar { align-items: stretch; padding-left: 10px; padding-right: 10px; }
        body.nav-abierto .nav-scroll { align-items: stretch; }
        body.nav-abierto .nav-pie { align-items: stretch; }
        body.nav-abierto .nav-marca { justify-content: flex-start; padding-left: 4px; }
        body.nav-abierto .nav-item { width: 100%; justify-content: flex-start; gap: 12px; padding: 0 10px; }
        body.nav-abierto .nav-item svg { flex-shrink: 0; }
        body.nav-abierto .nav-label {
            display: inline; font-size: 13px; font-weight: 500;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        body.nav-abierto .nav-grupo {
            display: block; font-size: 10px; font-weight: 700; letter-spacing: .06em;
            text-transform: uppercase; color: var(--muted-light);
            margin: 10px 0 2px; padding: 0 10px;
        }
        body.nav-abierto .nav-sep { display: none; }

        /* ── Secciones plegables ───────────────────────────────────────
           <details> nativo: sin JavaScript de por medio. El titulo sigue
           viendose como rotulo, no como un desplegable de formulario. */
        .nav-grupo-caja { width: 100%; }
        .nav-grupo-caja > summary { list-style: none; cursor: pointer; }
        .nav-grupo-caja > summary::-webkit-details-marker { display: none; }
        body.nav-abierto .nav-grupo-caja > summary {
            display: flex; align-items: center; justify-content: space-between;
            gap: 6px; min-height: 32px; border-radius: 8px;
        }
        body.nav-abierto .nav-grupo-caja > summary:hover { color: var(--text); background: var(--bg); }
        .nav-grupo-caja > summary:focus-visible { outline: 2px solid var(--blue); outline-offset: -2px; }
        .nav-grupo-flecha { width: 12px; height: 12px; flex-shrink: 0; transition: transform .18s ease; transform: rotate(-90deg); }
        .nav-grupo-caja[open] > summary .nav-grupo-flecha { transform: rotate(0deg); }
        /* En modo iconos no hay titulos: el plegado no aplica y se ven todos
           los enlaces. Sin esto, un grupo cerrado los esconderia sin que haya
           forma de abrirlo.
           OJO: la clase 'nav-abierto' la lleva el BODY, no el #sidebar. Escrito
           como '#sidebar:not(.nav-abierto)' casaba SIEMPRE, asi que los titulos
           quedaban ocultos tambien con el menu desplegado: sin <summary> no hay
           donde pulsar y los grupos cerrados desaparecian sin rescate. Se veia
           un solo grupo —el de la pantalla actual, el unico que abre el
           servidor— y el resto del menu parecia no existir. */
        body:not(.nav-abierto) .nav-grupo-caja > summary { display: none; }
        body:not(.nav-abierto) .nav-grupo-caja > *:not(summary) { display: flex; }
        @media (prefers-reduced-motion: reduce) { .nav-grupo-flecha { transition: none; } }
        body.nav-abierto .nav-toggle svg { transform: rotate(180deg); }
        /* Con el nombre delante, el tooltip sobra y tapaba contenido. */
        body.nav-abierto .nav-item::after { content: none; }

        /* ── Movil: cajon, no barra permanente ── */
        #nav-overlay {
            position: fixed; inset: 0; background: rgba(15,23,42,.45);
            z-index: 100; display: none;
        }
        @media (max-width: 767px) {
            :root { --sidebar-w: 0px; }
            body.nav-abierto { --sidebar-w: 0px; }
            #sidebar {
                width: 232px; align-items: stretch; padding-left: 10px; padding-right: 10px;
                transform: translateX(-100%); transition: transform .2s ease;
            }
            #sidebar.nav-movil-abierto { transform: translateX(0); box-shadow: var(--shadow-lg); }
            #sidebar .nav-label { display: inline; font-size: 14px; font-weight: 500; }
            #sidebar .nav-grupo { display: block; font-size: 10px; font-weight: 700;
                letter-spacing: .06em; text-transform: uppercase; color: var(--muted-light);
                margin: 10px 0 2px; padding: 0 10px; }
            #sidebar .nav-sep { display: none; }
            #sidebar .nav-item { width: 100%; justify-content: flex-start; gap: 12px; padding: 0 10px; }
            #sidebar .nav-item::after { content: none; }
            #sidebar .nav-scroll, #sidebar .nav-pie { align-items: stretch; }
            #sidebar .nav-marca { justify-content: flex-start; padding-left: 4px; }
            #nav-overlay { display: block; }
            #topbar { padding-left: 8px; }
            /* En el cajon ya se ven los nombres: "Expandir" no significa nada. */
            .nav-toggle { display: none; }
        }
        .nav-menu-btn { display: none; }
        @media (max-width: 767px) { .nav-menu-btn { display: flex; } }


        /* ══ MOVIL: la cara de la app (referencia: app de SUNAT) ══════════
           Cabecera roja con marca, globos y hamburguesa a la derecha; el
           cajon del menu sale por la DERECHA, azul, con letra grande y la
           mancha clara al fondo. Solo en movil: en escritorio el panel sigue
           siendo el de siempre. */
        .top-marca { display: none; }
        #sidebar .nav-acciones { display: none; }
        @media (max-width: 767px) {
            :root { --topbar-h: 60px; }
            #topbar { background: linear-gradient(135deg, #1E3A8A 0%, #1D4ED8 55%, #2563EB 100%); border-bottom: 0; box-shadow: none; padding: 0 12px 0 14px; gap: 14px; }
            #topbar > span, #topbar > svg, #topbar .top-conv, #topbar .top-panel, #topbar .vdiv, #topbar .empresa-chip { display: none !important; }
            .top-marca { display: flex; align-items: center; gap: 8px; color: #fff; font-weight: 900; font-size: 22px; letter-spacing: -.02em; text-decoration: none; margin-right: auto; }
            .top-marca svg { width: 26px; height: 26px; }
            #topbar .top-btn { color: #fff; background: transparent; border: 0; width: 40px; height: 40px; }
            #topbar .top-btn svg { width: 27px; height: 27px; }
            #topbar .top-btn.top-btn-alerta { background: transparent; color: #fff; }
            #topbar .badge { top: -4px; right: -6px; min-width: 22px; height: 22px; font-size: 11px; font-weight: 800; color: #fff; border: 0; }
            #topbar .badge-red { background: #EF4444; }
            #topbar .badge-yellow { background: #F59E0B; color: #fff; }
            #topbar .nav-menu-btn { order: 9; }
            #topbar .nav-menu-btn svg { width: 32px; height: 32px; stroke-width: 2.6; }

            #sidebar { left: auto; right: 0; width: 84%; max-width: 380px; transform: translateX(100%);
                background: linear-gradient(180deg, #0F172A 0%, #1E3A8A 100%); border: 0; color: #fff; padding: 22px 20px 20px; overflow: hidden; }
            #sidebar.nav-movil-abierto { transform: translateX(0); box-shadow: -12px 0 40px rgba(0,0,0,.35); }
            #sidebar::after { content: ""; position: absolute; right: -30%; bottom: -25%; width: 120%; height: 85%;
                border-radius: 50%; background: radial-gradient(circle at 30% 30%, rgba(37,99,235,.55), rgba(37,99,235,0) 70%); pointer-events: none; }
            #sidebar .nav-scroll, #sidebar .nav-pie, #sidebar .nav-marca { position: relative; z-index: 1; }
            #sidebar .nav-marca-cuadro { background: #fff; color: #1D4ED8; }
            #sidebar .nav-marca-texto { color: #fff; font-size: 22px; font-weight: 900; }
            #sidebar .nav-marca-empresa { color: #93C5FD; }
            #sidebar .nav-grupo { color: #93C5FD; font-size: 11px; margin: 14px 0 2px; }
            #sidebar .nav-item { color: #fff; height: 50px; gap: 20px; border-radius: 10px; }
            #sidebar .nav-item svg { width: 26px; height: 26px; color: #93C5FD; }
            #sidebar .nav-label { color: #fff; font-size: 17px; font-weight: 600; }
            #sidebar .nav-item:hover, #sidebar .nav-item.active { background: rgba(255,255,255,.14); color: #fff; }
            #sidebar .nav-item.active .nav-label { color: #fff; }
            #nav-overlay { background: rgba(15,23,42,.55); }
            #sidebar .nav-acciones { display: flex; position: absolute; top: 26px; right: 20px; gap: 22px; z-index: 2; }
            #sidebar .nav-acciones a, #sidebar .nav-acciones button { color: #93C5FD; background: none; border: 0; padding: 0; cursor: pointer; display: grid; place-items: center; }
            #sidebar .nav-acciones svg { width: 30px; height: 30px; }
            #sidebar .nav-marca { padding-right: 90px; }
        }
        @media (prefers-reduced-motion: reduce) { #sidebar, .nav-item { transition: none; } }
    </style>
<style>
/* Resultados del buscador global */
.bs-pop { position:absolute; top:calc(100% + 8px); left:0; right:0; z-index:300;
  background:var(--surface, #fff); border:1px solid var(--border, #e5e7eb); border-radius:12px;
  box-shadow:0 16px 40px rgba(15,23,42,.12); padding:6px; max-height:min(66vh, 460px); overflow-y:auto; }
.bs-info { margin:0; padding:14px 12px; font-size:13px; color:var(--muted, #64748b); }
.bs-grupo + .bs-grupo { margin-top:4px; border-top:1px solid var(--border, #eef0f4); padding-top:4px; }
.bs-grupo-titulo { margin:6px 10px 2px; font-size:10.5px; font-weight:700; letter-spacing:.05em;
  text-transform:uppercase; color:var(--muted, #94a3b8); }
.bs-item { display:flex; align-items:center; justify-content:space-between; gap:12px;
  padding:9px 10px; border-radius:8px; text-decoration:none; color:inherit; }
.bs-item:hover, .bs-item.activo { background:var(--primary-soft, #eef2ff); }
.bs-item-txt { display:flex; flex-direction:column; min-width:0; }
.bs-item-titulo { font-size:13.5px; font-weight:600; color:var(--text, #111827);
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.bs-item-detalle { font-size:11.5px; color:var(--muted, #64748b);
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.bs-item-importe { font-size:12.5px; font-weight:700; color:var(--primary, #4f46e5);
  flex-shrink:0; font-variant-numeric:tabular-nums; }
</style>
</head>
<body class="h-full antialiased"
      :class="navAbierto ? 'nav-abierto' : ''"
      x-data="avanLayout()"
      x-init="init()">

@include('comercial.layouts._sidebar')

{{-- ══════════════════════════════════════
     PANEL CAMBIAR VISTA (flotante)
══════════════════════════════════════ --}}
<div x-show="vistaOpen" @click.outside="vistaOpen=false" x-cloak
     style="position:fixed; left:calc(var(--sidebar-w) + 8px); bottom:60px;
            width:200px; background:var(--surface);
            border:1px solid var(--border); border-radius:12px;
            box-shadow:var(--shadow-lg); z-index:200; padding:6px;
            display:flex; flex-direction:column; gap:1px;">
    <p style="font-size:10px; font-weight:700; color:var(--muted);
              text-transform:uppercase; letter-spacing:.06em;
              padding:6px 10px 4px; margin:0;">Vista</p>
    @foreach([
        ['operativa',  'Centro Operativo', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['comercial',  'Comercial',        'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ['logistica',  'Logística',        'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10h10zM13 8h4l3 3v5h-7V8z'],
        ['gerencial',  'Gerencial',        'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
        ['financiera', 'Financiera',       'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['rrhh',       'RRHH',             'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
    ] as [$vk, $vl, $vp])
    <button @click="vista='{{ $vk }}'; vistaOpen=false"
            style="width:100%; display:flex; flex-direction:row; align-items:center; gap:8px;
                   padding:8px 10px; border-radius:8px; cursor:pointer;
                   font-size:13px; font-weight:500; border:none; font-family:inherit;
                   text-align:left; transition:background .1s; white-space:nowrap;"
            :style="vista==='{{ $vk }}'
                    ? 'background:var(--blue-light);color:var(--blue);'
                    : 'background:none;color:var(--text);'"
            onmouseover="if(this.getAttribute('data-active')!=='1') this.style.background='var(--hover)'"
            onmouseout="if(this.getAttribute('data-active')!=='1') this.style.background='none'"
            :data-active="vista==='{{ $vk }}' ? '1' : '0'">
        <svg style="width:15px;height:15px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $vp }}"/>
        </svg>
        <span>{{ $vl }}</span>
    </button>
    @endforeach
</div>

{{-- ══════════════════════════════════════
     TOPBAR
══════════════════════════════════════ --}}
<header id="topbar">

    {{-- En movil el menu es un cajon: esta barra abre el de la IZQUIERDA. El
         boton de la derecha sigue abriendo el panel de alertas. --}}
    <button type="button" class="top-btn nav-menu-btn" @click="navMovil = !navMovil"
            :aria-expanded="navMovil ? 'true' : 'false'" aria-controls="sidebar"
            aria-label="Abrir menú" title="Menú">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
        </svg>
    </button>

    {{-- Breadcrumb rubro. Las DOS CARAS del Workspace (ADR-002) se distinguen
         aqui: en las pantallas de configuracion (/bixoadmin) el chip dice
         "Configuración" con su propio tono; en la operacion, el rubro. --}}
    {{-- Marca de la app en movil (la barra roja de la referencia). En
         escritorio no se pinta: la marca ya esta en el menu lateral. --}}
    <a href="{{ route('bixosales.dashboard') }}" class="top-marca" aria-label="Inicio">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2 2 12l10 10 10-10zM12 6.8 17.2 12 12 17.2 6.8 12z"/></svg>
        <span>BIXO</span>
    </a>

    @php
        // Debe coincidir con la lista del sidebar (_sidebar.blade.php).
        $_caraConfig = request()->routeIs(
            'settings*', 'products.*', 'categories.*', 'roles.*', 'catalogs.*',
            'bots.*', 'bots-flow.*', 'projects.*', 'certificados.*', 'sedes.*',
            'proveedores.*', 'groups.*', 'design-templates.*', 'catalog-integrations.*'
        );
    @endphp
    @if($_caraConfig)
    <span style="font-size:12px; font-weight:600; color:#7c3aed;
                 background:#f3efff; padding:3px 10px;
                 border-radius:99px; flex-shrink:0; white-space:nowrap;">
        Configuración
    </span>
    @else
    <span style="font-size:12px; font-weight:600; color:var(--blue);
                 background:var(--blue-light); padding:3px 10px;
                 border-radius:99px; flex-shrink:0; white-space:nowrap;">
        {{ $_nav['label'] }}
    </span>
    @endif

    @if(isset($pageTitle))
    <svg style="width:14px;height:14px;color:var(--muted);flex-shrink:0;"
         fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span style="font-size:13px; color:var(--muted); white-space:nowrap;">{{ $pageTitle }}</span>
    @endif

    {{-- Búsqueda Global --}}
    <div class="search-box" style="margin:0 auto; position:relative;"
         @click.outside="buscarAbierto = false"
         @keydown.escape.window="buscarAbierto = false">
        <svg style="width:15px;height:15px;color:var(--muted);flex-shrink:0;"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
        </svg>
        <input type="text"
               x-model="searchQ"
               style="min-height:44px"
               {{-- El buscador decia "clientes, órdenes, mesas" en TODOS los
                    negocios: una ferreteria no tiene mesas y leerlo ahi hace
                    dudar de si el panel es el suyo. El rubro ya sabe como se
                    llaman sus cosas ($_nav), asi que se lo preguntamos. --}}
               placeholder="Buscar clientes, {{ mb_strtolower($_nav['pedidos']) }}, cotizaciones, productos..."
               role="combobox" aria-autocomplete="list" aria-controls="resultados-busqueda"
               :aria-expanded="buscarAbierto ? 'true' : 'false'"
               @input.debounce.220ms="buscarGlobal()"
               @focus="if (buscarGrupos.length) buscarAbierto = true"
               @keydown.down.prevent="moverResultado(1)"
               @keydown.up.prevent="moverResultado(-1)"
               @keydown.enter.prevent="abrirResultado()">
        <kbd style="font-size:10px; color:var(--muted); background:var(--bg);
                    border:1px solid var(--border); padding:1px 6px; border-radius:4px;
                    white-space:nowrap;">⌘K</kbd>

        {{-- Resultados predictivos. Cada grupo se pinta solo si el servidor
             lo devolvio, y el servidor solo devuelve lo que este usuario
             puede ver. --}}
        <div class="bs-pop" id="resultados-busqueda" role="listbox" x-show="buscarAbierto" x-cloak>
            <template x-if="buscarCargando">
                <p class="bs-info">Buscando…</p>
            </template>
            <template x-if="!buscarCargando && !buscarGrupos.length && searchQ.length > 1">
                <p class="bs-info">Sin resultados para «<span x-text="searchQ"></span>».</p>
            </template>
            <template x-for="g in buscarGrupos" :key="g.clave">
                <div class="bs-grupo">
                    <p class="bs-grupo-titulo" x-text="g.titulo"></p>
                    <template x-for="it in g.items" :key="g.clave + it.url + it.titulo">
                        <a :href="it.url" class="bs-item"
                           :class="buscarIndice === buscarPlano.findIndex(x => x.url === it.url && x.titulo === it.titulo) ? 'activo' : ''"
                           role="option"
                           :aria-selected="buscarIndice === buscarPlano.findIndex(x => x.url === it.url && x.titulo === it.titulo) ? 'true' : 'false'">
                            <span class="bs-item-txt">
                                <span class="bs-item-titulo" x-text="it.titulo"></span>
                                <span class="bs-item-detalle" x-show="it.detalle" x-text="it.detalle"></span>
                            </span>
                            <span class="bs-item-importe" x-show="it.importe" x-text="it.importe"></span>
                        </a>
                    </template>
                </div>
            </template>
        </div>
    </div>

    {{-- Alertas --}}
    <button class="top-btn {{ $_avisos['criticos'] > 0 ? 'top-btn-alerta' : '' }}"
            @click="abrirPanel('alertas', $event)"
            :aria-expanded="panelOpen && panelTab==='alertas' ? 'true':'false'" aria-controls="panel-right"
            title="{{ $_avisos['total'] ? $_avisos['total'].' aviso(s) que requieren tu atención' : 'Sin avisos' }}"
            aria-label="{{ $_avisos['total'] ? 'Avisos: '.$_avisos['total'] : 'Sin avisos' }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @if($_avisos['total'] > 0)
        <span class="badge {{ $_avisos['criticos'] > 0 ? 'badge-red' : 'badge-yellow' }}">{{ $_avisos['total'] }}</span>
        @endif
    </button>

    {{-- Conversaciones (solo superadmin) --}}
    @if($_hasBot && auth()->user()->is_superadmin)
    <a href="{{ route('bixosales.conversaciones') }}" class="top-btn top-conv" title="Conversaciones">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
    </a>
    @endif

    {{-- Pendientes --}}
    <button class="top-btn" @click="abrirPanel('pendientes', $event)" :aria-expanded="panelOpen && panelTab==='pendientes' ? 'true':'false'" aria-controls="panel-right" title="Pendientes">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        @if($_avisos['pendientes'] > 0)
        <span class="badge badge-yellow">{{ $_avisos['pendientes'] > 99 ? '99+' : $_avisos['pendientes'] }}</span>
        @endif
    </button>

    {{-- Panel toggle --}}
    <button class="top-btn top-panel" @click="panelOpen=!panelOpen" title="Panel lateral"
            :style="panelOpen ? 'background:var(--blue-light);color:var(--blue);' : ''">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M4 6h16M4 12h8m-8 6h16"/>
        </svg>
    </button>

    <div class="vdiv"></div>

    {{-- Retirado: mostraba "87/100 BIXO Score" a los usuarios del cliente.
         Era una puntuacion fija, sin metodologia ni acciones asociadas, y
         ademas exponia una marca interna. Se elimina en vez de renombrarla:
         un indicador que nadie puede accionar no aporta valor operativo. --}}

    {{-- EMPRESA · MENU DE CUENTA.
         Aqui vive "Cerrar sesion" en escritorio. Antes el boton solo estaba
         en el cajon movil (oculto por CSS en escritorio) y dentro de los
         modales de inactividad: quien queria salir a voluntad no tenia por
         donde. El chip ya era `cursor:pointer` pero no abria nada. --}}
    <div class="empresa-chip" x-data="{ abierto: false }" @keydown.escape.window="abierto = false"
         style="position:relative">
        <button type="button" @click="abierto = !abierto"
                :aria-expanded="abierto ? 'true' : 'false'" aria-haspopup="menu"
                style="display:flex; align-items:center; gap:6px; background:none;
                       border:0; padding:0; cursor:pointer; font:inherit; color:inherit;">
            <span style="width:20px; height:20px; border-radius:6px; flex-shrink:0;
                        background:var(--blue); color:#fff;
                        display:flex; align-items:center; justify-content:center;
                        font-size:10px; font-weight:700;">
                {{ strtoupper(substr($project->name ?? 'A', 0, 1)) }}
            </span>
            <span>{{ $project->name ?? '' }}</span>
            <svg style="width:13px;height:13px;opacity:.55" fill="none" stroke="currentColor"
                 stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
            </svg>
        </button>

        <div x-show="abierto" x-cloak @click.outside="abierto = false" role="menu"
             style="position:absolute; top:calc(100% + 8px); right:0; z-index:60;
                    min-width:210px; padding:6px; background:#fff;
                    border:1px solid var(--border); border-radius:12px;
                    box-shadow:0 12px 32px rgba(15,23,42,.16);">
            <div style="padding:8px 10px 9px; border-bottom:1px solid var(--border); margin-bottom:4px;">
                <div style="font-size:12.5px; font-weight:700; color:#0F172A;">{{ $project->name ?? '' }}</div>
                <div style="font-size:11px; color:#64748B; margin-top:1px;">{{ auth()->user()?->email }}</div>
            </div>

            @if(\Illuminate\Support\Facades\Route::has('settings'))
            <a href="{{ route('settings') }}" role="menuitem"
               style="display:flex; align-items:center; gap:9px; padding:8px 10px;
                      border-radius:8px; font-size:13px; color:#334155; text-decoration:none;"
               onmouseover="this.style.background='#F1F5F9'" onmouseout="this.style.background='none'">
                <svg style="width:16px;height:16px" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.782-.93-.397-.164-.854-.142-1.203.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                </svg>
                Configuración
            </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('bixosales.logout'))
            <form method="POST" action="{{ route('bixosales.logout') }}" style="margin:0">@csrf
                <button type="submit" role="menuitem"
                        style="display:flex; align-items:center; gap:9px; width:100%;
                               padding:8px 10px; border:0; border-radius:8px; background:none;
                               font:inherit; font-size:13px; color:#DC2626; cursor:pointer; text-align:left;"
                        onmouseover="this.style.background='#FEF2F2'" onmouseout="this.style.background='none'">
                    <svg style="width:16px;height:16px" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/>
                    </svg>
                    Cerrar sesión
                </button>
            </form>
            @endif
        </div>
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
    {{-- Velo del cajón: cierra al tocar fuera. Solo se ve por debajo de
         1280 px (en escritorio ancho el cajón no tapa el trabajo). --}}
    <div id="panel-overlay" :class="panelOpen ? 'is-open' : ''" @click="panelOpen=false" aria-hidden="true"></div>

    {{-- `inert` (no solo aria-hidden): aria-hidden lo esconde del lector de
         pantalla pero los botones del cajón cerrado SEGUÍAN siendo tabulables
         — el foco se iba a controles invisibles. `inert` los saca del orden de
         tabulación y del árbol de accesibilidad de una vez. Es atributo nativo,
         sin librerías.
         Al abrir, el foco entra al cajón; al cerrar, vuelve al botón que lo
         abrió (se guarda en `panelTrigger`). --}}
    {{-- x-cloak: la clase que lo esconde la pone Alpine DESPUES de iniciar;
         sin esto, cada carga pinta el cajon abierto un instante (y en un
         equipo lento, varios segundos) tapando el contenido. --}}
    <div id="panel-right" x-cloak :class="panelOpen ? '' : 'hidden-panel'"
         x-effect="panelOpen ? ($el.removeAttribute('inert'), $nextTick(()=>$el.querySelector('.panel-tab')?.focus()))
                             : ($el.setAttribute('inert',''), panelTrigger?.focus())"
         @keydown.escape.window="panelOpen && (panelOpen=false)"
         role="complementary" aria-label="Alertas, pendientes y actividad"
         :aria-hidden="panelOpen ? 'false' : 'true'" inert>

        {{-- Tabs --}}
        <div style="display:flex; border-bottom:1px solid var(--border); flex-shrink:0;">
            <button class="panel-tab" :class="panelTab==='alertas' ? 'active' : ''"
                    @click="panelTab='alertas'">Alertas</button>
            <button class="panel-tab" :class="panelTab==='pendientes' ? 'active' : ''"
                    @click="panelTab='pendientes'">Pendientes</button>
            <button class="panel-tab" :class="panelTab==='actividad' ? 'active' : ''"
                    @click="panelTab='actividad'">Actividad</button>
            <button @click="panelOpen=false" aria-label="Cerrar panel lateral"
                    style="min-width:44px; min-height:44px; display:inline-flex; align-items:center; justify-content:center; color:var(--muted); background:none; border:none; cursor:pointer;">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Contenido del panel --}}
        <div style="flex:1; overflow-y:auto; padding:14px;">

            @php
            $_pid_alert = session('comercial_project_id');
                $_alertas = [];
                $_projAlert = $_pid_alert ? \App\Models\Project::find($_pid_alert) : null;
                $_esLavAlert = $_projAlert && \App\Modules\Ventas\Support\OrderFlow::supportsFlow($_projAlert->category);

                if ($_esLavAlert) {
                    // Lavandería: alertas basadas en el SLA por estado (no umbral genérico).
                    $_lavStatesA = \App\Modules\Operaciones\Support\LaundryFlow::activeStates($_projAlert);
                    $__orders = \App\Modules\Ventas\Models\Order::where('project_id', $_pid_alert)
                        ->whereNotIn('laundry_status', ['entregado','anulado'])
                        ->whereNotNull('laundry_status')
                        ->orderBy('laundry_status_at')->limit(20)->get();
                    foreach ($__orders as $__o) {
                        $_sla = \App\Modules\Operaciones\Support\LaundryFlow::slaStatus($_projAlert, $__o);
                        // Solo alertar si el estado tiene SLA y está en warn/over
                        if (!$_sla['sla'] || $_sla['level'] === 'ok') continue;
                        $_lbl = $_lavStatesA[$__o->laundry_status]['label'] ?? ucfirst($__o->laundry_status);
                        $_min = $_sla['minutes'];
                        $_t   = $_min < 60 ? $_min.' min' : intdiv($_min,60).'h '.($_min%60).'m';
                        $_alertas[] = ['nivel' => $_sla['level'] === 'over' ? 'red' : 'yellow',
                            'titulo' => ($__o->tag_code ?: 'Pedido #'.$__o->id) . ' — ' . $_lbl,
                            'desc'   => $_t . ' en este estado' . ($__o->client_name ? ' · ' . $__o->client_name : '')];
                    }
                } elseif ($_pid_alert) {
                    $__orders = \App\Modules\Ventas\Models\Order::where('project_id', $_pid_alert)
                        ->whereIn('status', ['pending','process'])
                        ->where('created_at', '<', now()->subMinutes(30))
                        ->orderBy('created_at')->limit(5)->get();
                    foreach ($__orders as $__o) {
                        $__min = (int) $__o->created_at->diffInMinutes(now());
                        $_alertas[] = ['nivel' => $__min >= 60 ? 'red' : 'yellow',
                            'titulo' => 'Pedido #' . $__o->id . ' — ' . $__min . ' min',
                            'desc'   => 'Sin atender' . ($__o->client_name ? ' · ' . $__o->client_name : '')];
                    }
                }
            @endphp

            {{-- ALERTAS --}}
            <div x-show="panelTab==='alertas'">
                <p class="section-title">Alertas activas</p>
                    {{-- Los avisos salen de App\Support\AvisosPortal: cobro
                         vencido, pedidos parados, stock bajo minimo y pagos
                         por validar. Antes esta rama solo miraba el SLA de
                         lavanderia y, como `supportsFlow()` es cierto para
                         cualquier rubro, un comercio veia "Todo al dia"
                         teniendo once documentos vencidos. --}}
                    @if($_avisos['total'] === 0)
                        <div style="text-align:center; padding:32px 16px; color:var(--muted);">
                            <div style="font-size:28px; margin-bottom:8px;">✅</div>
                            <p style="font-size:12px; font-weight:600;">Sin avisos</p>
                            <p style="font-size:11px; margin-top:4px;">Nada vencido, parado ni bajo mínimo</p>
                        </div>
                    @endif
                    @foreach($_avisos['avisos'] as $_a)
                    <a href="{{ $_a['url'] }}" style="text-decoration:none; display:block;">
                        <div class="alert-item {{ $_a['nivel'] === 'alto' ? 'alert-red' : 'alert-yellow' }}"
                             style="cursor:pointer; transition:box-shadow .1s;"
                             onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,.08)'"
                             onmouseout="this.style.boxShadow='none'">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:6px; margin-bottom:4px;">
                                <div style="display:flex; align-items:center; gap:6px; min-width:0;">
                                    <span class="sema {{ $_a['nivel'] === 'alto' ? 'sema-red' : 'sema-yellow' }}"></span>
                                    <span style="font-weight:600; color:{{ $_a['nivel'] === 'alto' ? 'var(--red)' : '#92400E' }};">{{ $_a['titulo'] }}</span>
                                </div>
                                <span style="font-size:10px; color:{{ $_a['nivel'] === 'alto' ? 'var(--red)' : '#B45309' }};">›</span>
                            </div>
                            <p style="color:{{ $_a['nivel'] === 'alto' ? '#991B1B' : '#92400E' }}; font-size:12px; margin:0;">{{ $_a['detalle'] }}</p>
                        </div>
                    </a>
                    @endforeach
            </div>

            {{-- PENDIENTES --}}
            <div x-show="panelTab==='pendientes'" x-cloak>
                <p class="section-title">Por atender</p>
                    <div style="text-align:center; padding:32px 16px; color:var(--muted);">
                        <div style="font-size:28px; margin-bottom:8px;">🎉</div>
                        <p style="font-size:12px; font-weight:600;">Sin pendientes</p>
                    </div>
            </div>

            {{-- ACTIVIDAD --}}
            <div x-show="panelTab==='actividad'" x-cloak>
                <p class="section-title">Reciente</p>
                    <p style="font-size:12px; color:var(--muted); text-align:center; padding:20px 0;">Sin actividad reciente</p>
            </div>
        </div>

        {{-- Acceso rápido --}}
        <div style="border-top:1px solid var(--border); padding:12px; flex-shrink:0;">
            <p class="section-title">Acceso rápido</p>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:6px;">
                @if($_has('orders'))
                @if($_can('pos.usar'))
                <a href="{{ route('bixosales.pos') }}"
                   style="display:flex; flex-direction:column; align-items:center; gap:4px;
                          padding:8px 4px; border-radius:8px; border:1px solid var(--border);
                          text-decoration:none; transition:background .1s;"
                   onmouseover="this.style.background='var(--hover)'"
                   onmouseout="this.style.background='none'">
                    <svg style="width:16px;height:16px;color:var(--green);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span style="font-size:10px; color:var(--muted); font-weight:500;">Vender</span>
                </a>
                @endif
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
                @if($_can('agenda.ver') && $_mod['reservas'])
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
                @endif
                @if($_can('caja.ver') && $_mod['caja'])
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
                @endif
                @if($_can('reports.ver'))
                <a href="{{ route('bixosales.reportes.ventas.general') }}"
                   style="display:flex; flex-direction:column; align-items:center; gap:4px;
                          padding:8px 4px; border-radius:8px; border:1px solid var(--border);
                          text-decoration:none; transition:background .1s;"
                   onmouseover="this.style.background='var(--hover)'"
                   onmouseout="this.style.background='none'">
                    <svg style="width:16px;height:16px;color:var(--blue);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                              d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    <span style="font-size:10px; color:var(--muted); font-weight:500;">Ver ventas</span>
                </a>
                @endif
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
    <div x-show="phase==='warn'" x-cloak class="flex items-center justify-center"
         style="position:fixed;inset:0;z-index:9998;padding:16px;background:rgba(0,0,0,.35);">
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
    <div x-show="phase==='expired'" class="flex items-center justify-center" x-cloak
         style="position:fixed;inset:0;z-index:9999;padding:16px;background:rgba(0,0,0,.60);">
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
        // El rail solo de iconos obliga a adivinar que hay detras de cada
        // dibujo. Se puede expandir a icono+texto y la eleccion se recuerda.
        // Sin preferencia guardada, en escritorio arranca EXPANDIDO: un rail
        // de iconos sin texto no se entiende la primera vez. Quien lo contrae
        // se lo encuentra contraido la proxima.
        navAbierto: (localStorage.getItem('bixo.navAbierto') ?? (window.innerWidth >= 1280 ? '1' : '0')) === '1',
        navMovil:   false,
        // Como cajón ya no roba ancho, pero arrancar abierto en escritorio
        // tapaba trabajo al entrar. Cerrado por defecto: se abre a demanda.
        panelOpen:  false,
        // Botón que abrió el cajón, para devolverle el foco al cerrar.
        panelTrigger: null,
        abrirPanel(tab, ev){ this.panelTrigger = ev?.currentTarget || null; this.panelTab = tab; this.panelOpen = true; },
        panelTab:   'alertas',
        searchQ:    '',
        /* Buscador global: grupos que devuelve el servidor, el plano para
           moverse con el teclado y el indice resaltado. */
        buscarAbierto: false, buscarCargando: false, buscarGrupos: [], buscarIndice: -1,
        get buscarPlano() { return this.buscarGrupos.flatMap(g => g.items); },
        async buscarGlobal() {
            const q = (this.searchQ || '').trim();
            this.buscarIndice = -1;
            if (q.length < 2) { this.buscarGrupos = []; this.buscarAbierto = false; return; }
            this.buscarAbierto = true; this.buscarCargando = true;
            try {
                const r = await fetch('{{ route('bixosales.buscar') }}?q=' + encodeURIComponent(q), {headers:{'Accept':'application/json'}});
                this.buscarGrupos = r.ok ? ((await r.json()).grupos || []) : [];
            } catch (e) { this.buscarGrupos = []; }
            this.buscarCargando = false;
        },
        moverResultado(paso) {
            const n = this.buscarPlano.length;
            if (!n) return;
            this.buscarAbierto = true;
            this.buscarIndice = (this.buscarIndice + paso + n) % n;
        },
        /* Enter: abre lo resaltado; si no hay nada resaltado, el primero. Sin
           resultados no se va a ningun sitio, que era lo que antes pasaba
           siempre (acababas en Pedidos buscaras lo que buscaras). */
        abrirResultado() {
            const destino = this.buscarPlano[this.buscarIndice >= 0 ? this.buscarIndice : 0];
            if (destino) window.location = destino.url;
        },
        alternarNav() {
            this.navAbierto = !this.navAbierto;
            try { localStorage.setItem('bixo.navAbierto', this.navAbierto ? '1' : '0'); } catch (e) {}
        },
        init() {
            // Antes esta escucha hacia `panelOpen = ancho >= 1024`, asi que
            // cualquier redimension —o girar el movil— abria el cajon derecho
            // aunque el usuario lo hubiera cerrado. Solo se cierra al pasar a
            // pantalla pequeña; abrirlo es siempre decision del usuario.
            window.addEventListener('resize', () => {
                if (window.innerWidth < 1024) { this.panelOpen = false; }
                if (window.innerWidth >= 768) { this.navMovil = false; }
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
{{-- El shell único sirve también las pantallas que llaman `window.__confirm`
     (eliminar producto/imagen/categoría/servicio, descartar borrador): sin
     este modal esos botones fallaban en silencio. Mismo partial que el otro
     shell — un solo modal, sin duplicar Blade. --}}
@include('partials.confirm-global')
@include('partials.avisos')
</body>
</html>
