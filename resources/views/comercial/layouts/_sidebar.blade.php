{{-- ══════════════════════════════════════════════════════════════════
     MENU LATERAL DEL PORTAL COMERCIAL

     Antes eran once enlaces escritos a mano con nombres que no decian a
     donde llevaban ("Centro Operativo", "Actividades", "Indicadores") y,
     sobre todo, FALTABAN modulos que si existen y estan en produccion:
     Cotizaciones, Clientes, Cobranza, Caja y Facturas no tenian ni un
     enlace en todo el portal. Se llegaba a ellos escribiendo la URL.

     Ahora el menu es una estructura de datos: cada entrada declara su ruta
     real y los permisos que la abren, y se pinta solo si el usuario los
     tiene. Los permisos son los MISMOS que exige la ruta en routes/web.php
     (universo canonico A "quotes.ver" o heredado B "view-quotes"), asi que
     el menu no puede ofrecer nada que luego devuelva 403.
══════════════════════════════════════════════════════════════════ --}}
@php
    $_u = auth()->user();
    $_puede = function (array $permisos) use ($_u) {
        if (!$_u) return false;
        if ($_u->is_superadmin) return true;
        foreach ($permisos as $p) {
            if ($_u->can($p)) return true;
        }
        return false;
    };

    // Icono (trazo Heroicons, la misma familia que ya usa el portal).
    $_ico = [
        'inicio'      => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75',
        'pos'         => 'M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z',
        'rayo'        => 'm3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z',
        'cotizacion'  => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
        'pedidos'     => 'm20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z',
        'clientes'    => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        'cobranza'    => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z',
        'factura'     => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z',
        'caja'        => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'reportes'    => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
        'inventario'  => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
        'mesas'       => 'M3 10h18M3 14h18M10 5v14M14 5v14',
        'cocina'      => 'M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z',
        'agenda'      => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
        'delivery'    => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
        'bot'         => 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z',
        'etiqueta'    => 'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z',
    ];

    // Cada grupo: titulo + entradas [etiqueta, ruta, icono, permisos]
    // Los grupos siguen la referencia: Comercial / Catalogos / Finanzas /
    // Analisis / Configuracion. Solo entran rutas que EXISTEN en el portal:
    // "Productos" y "Configuracion" no tienen pantalla propia aqui (viven en
    // el panel principal), asi que no se inventan enlaces muertos.
    $_grupos = [];

    $_grupos[] = ['titulo' => null, 'items' => array_values(array_filter([
        ['Inicio', 'bixosales.dashboard', 'inicio', ['orders.ver', 'view-orders']],
    ], fn ($i) => $_puede($i[3]))) ];

    $_comercial = array_filter([
        [$_nav['pos'] ?: 'Ventas', 'bixosales.pos', 'pos', ['pos.usar']],
        ['Venta express', 'bixosales.ventas.express', 'rayo', ['pos.usar']],
        // El mapa por rubro llamaba "Propuestas" a las cotizaciones en el caso
        // generico, mientras el resto del producto —la ruta, el boton "Nueva
        // cotización", el modulo— dice Cotizaciones. Se respeta el nombre del
        // rubro solo cuando de verdad es otro (clinica y taller: Presupuestos).
        $_nav['cotizaciones']
            ? [$_nav['cotizaciones'] === 'Propuestas' ? 'Cotizaciones' : $_nav['cotizaciones'],
               'bixosales.cotizaciones', 'cotizacion', ['quotes.ver', 'view-quotes']]
            : null,
        // Mismo criterio: el generico decia "Órdenes" y el resto dice Pedidos.
        [($_nav['pedidos'] === 'Órdenes' ? 'Pedidos' : ($_nav['pedidos'] ?: 'Pedidos')),
         'bixosales.pedidos', 'pedidos', ['orders.ver', 'view-orders']],
        $_nav['hasKitchen'] ? ['Mesas', 'bixosales.mesas', 'mesas', ['orders.ver', 'view-orders']] : null,
        $_nav['hasKitchen'] ? ['Cocina', 'bixosales.cocina', 'cocina', ['orders.ver', 'view-orders']] : null,
        $_mod['bot'] ? ['Pedidos del bot', 'bixosales.rifas', 'bot', ['rifas.ver']] : null,
        $_mod['reservas'] ? ['Reservas', 'bixosales.reservas', 'agenda', ['agenda.ver']] : null,
        $_mod['reparto'] ? ['Reparto', 'bixosales.delivery', 'delivery', ['view-logistics', 'manage-logistics']] : null,
    ], fn ($i) => $i && $_puede($i[3]));
    if ($_comercial) $_grupos[] = ['titulo' => 'Comercial', 'items' => array_values($_comercial)];

    $_catalogos = array_filter([
        ['Clientes', 'bixosales.clientes', 'clientes', ['clients.ver', 'view-clients']],
        ['Inventario', 'bixosales.reportes.inventario', 'inventario', ['reports.ver']],
        $_mod['revendedor'] ? ['Mis precios', 'bixosales.reseller.precios', 'etiqueta', ['pos.usar']] : null,
    ], fn ($i) => $i && $_puede($i[3]));
    if ($_catalogos) $_grupos[] = ['titulo' => 'Catálogos', 'items' => array_values($_catalogos)];

    $_finanzas = array_filter([
        ['Cobranza', 'bixosales.cuentas', 'cobranza', ['reports.ver']],
        $_mod['facturas'] ? ['Facturas', 'bixosales.facturas', 'factura', ['invoices.ver']] : null,
        $_mod['caja'] ? ['Caja', 'bixosales.caja', 'caja', ['caja.ver']] : null,
    ], fn ($i) => $i && $_puede($i[3]));
    if ($_finanzas) $_grupos[] = ['titulo' => 'Finanzas', 'items' => array_values($_finanzas)];

    $_analisis = array_filter([
        ['Reportes', 'bixosales.reportes.ventas.general', 'reportes', ['reports.ver']],
    ], fn ($i) => $i && $_puede($i[3]));
    if ($_analisis) $_grupos[] = ['titulo' => 'Análisis', 'items' => array_values($_analisis)];

    // Encargos a medida: dependen del PROYECTO que los pidio, no de quien mira.
    $_medida = array_filter([
        ($_u?->is_superadmin && (int) ($project->setting('modulo_tickets_wp', 0)) === 1)
            ? ['Tickets manuales', 'bixosales.tickets.wp', 'etiqueta', ['tickets.ver']] : null,
        ($_u?->is_superadmin && (int) ($project->setting('modulo_pedidos_web', 0)) === 1)
            ? ['Pedidos web', 'bixosales.woo.orders', 'pedidos', ['tickets.ver']] : null,
        ($_u?->is_superadmin && (int) ($project->setting('modulo_conversaciones', 0)) === 1)
            ? ['Conversaciones', 'bixosales.conversaciones', 'bot', ['tickets.ver']] : null,
    ], fn ($i) => $i !== null);
    if ($_medida) $_grupos[] = ['titulo' => 'A medida', 'items' => array_values($_medida)];
@endphp

<nav id="sidebar" :class="navMovil ? 'nav-movil-abierto' : ''" aria-label="Navegación principal">

    <a href="{{ route('bixosales.dashboard') }}" class="nav-marca" aria-label="Inicio · {{ $project->name ?? '' }}">
        <span class="nav-marca-cuadro">B</span>
        <span class="nav-label nav-marca-bloque">
            <span class="nav-marca-texto">BIXO</span>
            <span class="nav-marca-empresa">{{ \Illuminate\Support\Str::limit($project->name ?? '', 20) }}</span>
        </span>
    </a>

    <div class="nav-scroll">
        @foreach($_grupos as $g)
            @if(count($g['items']))
                @if($g['titulo'])
                <p class="nav-grupo nav-label">{{ $g['titulo'] }}</p>
                <div class="nav-sep" aria-hidden="true"></div>
                @endif
                @foreach($g['items'] as [$etiqueta, $ruta, $icono, $permisos])
                    @if(\Illuminate\Support\Facades\Route::has($ruta))
                    @php $activo = request()->routeIs($ruta) || request()->routeIs($ruta.'.*'); @endphp
                    <a href="{{ route($ruta) }}"
                       class="nav-item {{ $activo ? 'active' : '' }}"
                       @if($activo) aria-current="page" @endif
                       data-tip="{{ $etiqueta }}" aria-label="{{ $etiqueta }}" title="{{ $etiqueta }}">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $_ico[$icono] }}"/>
                        </svg>
                        <span class="nav-label">{{ $etiqueta }}</span>
                    </a>
                    @endif
                @endforeach
            @endif
        @endforeach
    </div>

    <div class="nav-pie">
        <button type="button" class="nav-item nav-toggle" @click="alternarNav()"
                :aria-expanded="navAbierto ? 'true' : 'false'"
                :aria-label="navAbierto ? 'Contraer menú' : 'Expandir menú'"
                :title="navAbierto ? 'Contraer menú' : 'Expandir menú'"
                data-tip="Expandir menú">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
            </svg>
            <span class="nav-label" x-text="navAbierto ? 'Contraer' : 'Expandir'">Expandir</span>
        </button>
    </div>
</nav>

<div id="nav-overlay" x-show="navMovil" x-cloak @click="navMovil = false" aria-hidden="true"></div>
