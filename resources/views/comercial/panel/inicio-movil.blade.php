{{-- PORTADA MOVIL de Sales (2026-09-05), con la estructura de la app de SUNAT
     que el usuario pidio replicar: ola roja con la carita y "¡Atencion!" si
     hay pendientes (o azul y "¡Todo en orden!"), y una fila por area con
     tarjetas altas de una sola accion. En escritorio no se pinta: ahi sigue
     el resumen del negocio con sus graficas. La cabecera roja y el cajon
     azul los pone el layout de Sales (solo en movil). --}}
@php
    $u = auth()->user();
    $puede = fn (array $perms) => $u?->is_superadmin
        || $project->owner_id === $u?->id
        || collect($perms)->contains(fn ($p) => $u?->can($p));
    $hay = fn (string $ruta) => \Illuminate\Support\Facades\Route::has($ruta);

    $facturacion  = $project->hasModule('invoices') && $hay('bixosales.facturas') && $puede(['invoices.ver']);
    $emite        = $facturacion && $puede(['invoices.crear']);
    $vende        = $hay('bixosales.pos') && $puede(['pos.usar']);
    $verPedidos   = $puede(['orders.ver', 'view-orders']);
    $verCotiz     = $hay('bixosales.cotizaciones') && $puede(['quotes.ver', 'view-quotes']);
    $verClientes  = $puede(['clients.ver', 'view-clients']);
    $verReportes  = $puede(['reports.ver']);
    $verProductos = $hay('products.index') && $puede(['products.ver', 'manage-products']);
    $lector       = $facturacion && $emite && \App\Support\Lector\LectorComprobantes::disponible($project);

    $avisos   = $avisos ?? [];
    $hayPend  = count($avisos) > 0;
    $nombre   = $u?->name ?? '';
    $nombreCorto = $nombre ? \Illuminate\Support\Str::before($nombre, ' ') : '';
    $moneda   = ($project->setting('currency') ?: 'PEN') === 'USD' ? 'US$' : 'S/';
    $etqPedidos = $kpi['p'] ?? 'Pedidos';

    $sn = [
        'factura'  => 'M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.6a1 1 0 0 1 .7.3l4.4 4.4a1 1 0 0 1 .3.7V19a2 2 0 0 1-2 2z',
        'boleta'   => 'M9 8h6M9 12h6M9 16h4M5 3h14v18l-2.3-1.5L14.4 21l-2.4-1.5L9.6 21l-2.3-1.5L5 21z',
        'nota'     => 'M8 7h12m0 0-4-4m4 4-4 4M16 17H4m0 0 4 4m-4-4 4-4',
        'lupa'     => 'm21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z',
        'camion'   => 'M3 7h11v9H3zM14 10h4l3 3v3h-7zM6 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm11 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4z',
        'billetes' => 'M3 7h18v10H3zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM6 12h.01M18 12h.01',
        'usuarios' => 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM4 21a8 8 0 0 1 16 0',
        'reporte'  => 'M4 20h16M7 16V9m5 7V5m5 11v-4',
        'camara'   => 'M4 8h3l2-3h6l2 3h3v11H4zM12 17a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z',
        'reloj'    => 'M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',
        'alerta'   => 'M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',
        'lista'    => 'M4 6h16M4 12h16M4 18h16',
        'bolsa'    => 'M6 8h12l1 13H5zM9 8V6a3 3 0 0 1 6 0v2',
        'rayo'     => 'm13 2-9 12h7l-1 8 9-12h-7z',
        'cotiz'    => 'M8 3h8l4 4v14H4V3zM8 12h8M8 16h5',
        'pedido'   => 'M4 7h16l-1 12H5zM4 7l2-3h12l2 3M10 11h4',
        'caja'     => 'M3 8h18v12H3zM3 8l2-4h14l2 4M12 12v4',
        'almacen'  => 'M3 21h18M5 21V9l7-5 7 5v12M9 21v-6h6v6',
        'casa'     => 'M3 11 12 3l9 8v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z',
        'mesa'     => 'M3 10h18M3 14h18M10 5v14M14 5v14',
    ];
    $tonoDe = fn ($a) => ($a['nivel'] ?? '') === 'alto' ? '' : (($a['nivel'] ?? '') === 'medio' ? 'amb' : 'gris');
@endphp

@include('comercial.panel._sn-estilos')
<style>
    /* La portada movil solo existe en movil; el resumen de escritorio, solo
       en escritorio. Sin build de Tailwind: se decide aqui. */
    @media (min-width:768px){ .sn-movil{display:none} }
    @media (max-width:767px){ #centroOp{display:none} }
</style>

<div class="sn sn-movil" x-data="snInicio()">

    {{-- ── Saludo sobre la ola de la marca + tarjeta de estado ────── --}}
    <section class="sn-hero" id="sn-pend">
        <div class="saludo">
            <small>{{ $project->name }}</small>
            <h1>Hola{{ $nombreCorto ? ', '.$nombreCorto : '' }}</h1>
            <p>{{ ucfirst(now()->locale('es')->translatedFormat('l j \\d\\e F')) }}</p>
        </div>
        <svg class="ola" viewBox="0 0 400 46" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0 30C80 46 160 46 240 34 300 24 360 18 400 26V46H0Z" fill="#F8F9FB"/>
        </svg>
    </section>
    <div class="sn-estado {{ $hayPend ? 'mal' : 'ok' }}" role="status">
        <div class="punto" aria-hidden="true">
            <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $hayPend ? 'M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z' : 'm5 13 4 4L19 7' }}"/></svg>
        </div>
        <div>
            <h2>{{ $hayPend ? (count($avisos) === 1 ? '1 asunto pendiente' : count($avisos).' asuntos pendientes') : 'Todo en orden' }}</h2>
            <p>{{ $hayPend ? 'Conviene resolverlos hoy. Desliza para verlos.' : 'Nada urgente en el negocio ahora mismo.' }}</p>
        </div>
    </div>

    @if($hayPend)
    <section class="sn-fila" style="padding-top:8px">
        <div class="sn-carril">
            @foreach($avisos as $a)
            <article class="sn-card pend {{ $tonoDe($a) }}">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['alerta'] }}"/></svg></div>
                <h3>{{ $a['titulo'] }}</h3>
                <p class="det">{{ $a['detalle'] ?? '' }}</p>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ $a['url'] }}">{{ $a['accion'] ?? 'Ver' }}</a>
            </article>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ── Ventas ────────────────────────────────────────────────── --}}
    @if($vende || $verPedidos || $verCotiz)
    <section class="sn-fila" x-data="snFila()">
        <div class="sn-fila-cab">
            <h2>Ventas</h2>
            <div class="sn-flechas"><button type="button" @click="mover(-1)" aria-label="Anterior">‹</button><button type="button" @click="mover(1)" aria-label="Siguiente">›</button></div>
        </div>
        <div class="sn-carril" x-ref="carril">
            @if($vende)
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['bolsa'] }}"/></svg></div>
                <h3>{{ $kpi['acc'] ?? 'Nueva venta' }}</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route($hay($kpi['acc_route'] ?? '') ? $kpi['acc_route'] : 'bixosales.pos') }}">Vender</a>
            </article>
            @if($hay('bixosales.ventas.express'))
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['rayo'] }}"/></svg></div>
                <h3>Venta express</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.ventas.express') }}">Vender</a>
            </article>
            @endif
            @endif
            @if($verCotiz)
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['cotiz'] }}"/></svg></div>
                <h3>Cotizaciones</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.cotizaciones') }}">Empezar</a>
            </article>
            @endif
            @if($verPedidos)
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['pedido'] }}"/></svg></div>
                <h3>{{ $etqPedidos }}</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.pedidos') }}">Consultar</a>
            </article>
            @if(!empty($esRest) && $hay('bixosales.mesas'))
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['mesa'] }}"/></svg></div>
                <h3>Mesas</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.mesas') }}">Ver</a>
            </article>
            @endif
            @endif
        </div>
    </section>
    @endif

    @if($facturacion)
    {{-- ── Guias de remision ─────────────────────────────────────── --}}
    <section class="sn-fila" x-data="snFila()">
        <div class="sn-fila-cab">
            <h2>Guía de Remisión Electrónica (GRE)</h2>
            <div class="sn-flechas"><button type="button" @click="mover(-1)" aria-label="Anterior">‹</button><button type="button" @click="mover(1)" aria-label="Siguiente">›</button></div>
        </div>
        <div class="sn-carril" x-ref="carril">
            @if($emite)
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['camion'] }}"/></svg></div>
                <h3>Emitir GRE</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.guias.index', ['nueva' => 1]) }}">Generar</a>
            </article>
            @endif
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['lupa'] }}"/></svg></div>
                <h3>Guías emitidas</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.guias.index') }}">Consultar</a>
            </article>
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['camion'] }}"/></svg></div>
                <h3>Baja de GRE</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.guias.index') }}">Generar</a>
            </article>
        </div>
    </section>

    {{-- ── Comprobantes de pago ──────────────────────────────────── --}}
    <section class="sn-fila" x-data="snFila()">
        <div class="sn-fila-cab">
            <h2>Comprobantes de pago</h2>
            <div class="sn-flechas"><button type="button" @click="mover(-1)" aria-label="Anterior">‹</button><button type="button" @click="mover(1)" aria-label="Siguiente">›</button></div>
        </div>
        <div class="sn-carril" x-ref="carril">
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="{{ $sn['lista'] }}"/></svg></div>
                <h3>Catálogo de Productos</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ $verProductos ? route('products.index') : ($vende ? route('bixosales.pos') : route('bixosales.facturas.consulta')) }}">Consultar</a>
            </article>
            @if($emite)
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['boleta'] }}"/></svg></div>
                <h3>Facturas</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.facturas', ['tipo' => 'factura']) }}">Emitir</a>
            </article>
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['boleta'] }}"/></svg></div>
                <h3>Boletas</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.facturas', ['tipo' => 'boleta']) }}">Emitir</a>
            </article>
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['nota'] }}"/></svg></div>
                <h3>Notas de crédito y débito</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.facturas', ['tipo' => 'nota']) }}">Emitir</a>
            </article>
            @endif
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['lupa'] }}"/></svg></div>
                <h3>Comprobantes emitidos</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.facturas.consulta') }}">Consultar</a>
            </article>
            @if($lector)
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['camara'] }}"/></svg></div>
                <h3>Lector de comprobantes</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.facturas', ['tipo' => 'factura', 'lector' => 1]) }}">Leer</a>
            </article>
            @endif
        </div>
    </section>
    @endif

    {{-- ── Cobranza y pagos ──────────────────────────────────────── --}}
    @if($verReportes || $vende)
    <section class="sn-fila" x-data="snFila()">
        <div class="sn-fila-cab">
            <h2>Cobranza y pagos</h2>
            <div class="sn-flechas"><button type="button" @click="mover(-1)" aria-label="Anterior">‹</button><button type="button" @click="mover(1)" aria-label="Siguiente">›</button></div>
        </div>
        <div class="sn-carril" x-ref="carril">
            @if($verReportes)
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['billetes'] }}"/></svg></div>
                <h3>Cuentas por cobrar</h3>
                @if(($porCobrar ?? 0) > 0)<p class="sub">{{ $moneda }} {{ number_format($porCobrar, 2) }} por cobrar</p>@endif
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.cuentas') }}">Empezar</a>
            </article>
            @endif
            @if($hay('bixosales.pagos.pendientes'))
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['reloj'] }}"/></svg></div>
                <h3>Pagos por aprobar</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.dashboard').'#pagos-por-aprobar' }}">Consultar</a>
            </article>
            @endif
            @if($hay('bixosales.caja') && $puede(['caja.ver']) && $project->hasModule('caja'))
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['caja'] }}"/></svg></div>
                <h3>Caja</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.caja') }}">Abrir</a>
            </article>
            @endif
        </div>
    </section>
    @endif

    {{-- ── Consultas ─────────────────────────────────────────────── --}}
    <section class="sn-fila" x-data="snFila()">
        <div class="sn-fila-cab">
            <h2>Consultas</h2>
            <div class="sn-flechas"><button type="button" @click="mover(-1)" aria-label="Anterior">‹</button><button type="button" @click="mover(1)" aria-label="Siguiente">›</button></div>
        </div>
        <div class="sn-carril" x-ref="carril">
            @if($facturacion)
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['lupa'] }}"/></svg></div>
                <h3>RUC / DNI</h3>
                <form class="sn-mini" @submit.prevent="consultar()">
                    <input type="text" inputmode="numeric" maxlength="11" placeholder="Número" x-model="doc" aria-label="RUC o DNI">
                    <button type="submit" class="sn-btn" :disabled="consultando" x-text="consultando ? '...' : 'Empezar'">Empezar</button>
                </form>
                <div class="sn-res" x-show="res" x-cloak :style="resOk ? 'color:#065f46' : 'color:#b45309'" x-text="res"></div>
            </article>
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['lupa'] }}"/></svg></div>
                <h3>Comprobantes de Pago</h3>
                <form class="sn-mini" method="GET" action="{{ route('bixosales.facturas.consulta') }}">
                    <input type="search" name="q" placeholder="F001-12, cliente, RUC" aria-label="Buscar comprobante">
                    <button type="submit" class="sn-btn">Ir</button>
                </form>
            </article>
            @endif
            @if($verClientes)
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['usuarios'] }}"/></svg></div>
                <h3>Tus clientes</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.clientes') }}">Empezar</a>
            </article>
            @endif
        </div>
    </section>

    {{-- ── Inventario ────────────────────────────────────────────── --}}
    @if($verReportes && $hay('bixosales.reportes.inventario'))
    <section class="sn-fila" x-data="snFila()">
        <div class="sn-fila-cab">
            <h2>Inventario</h2>
            <div class="sn-flechas"><button type="button" @click="mover(-1)" aria-label="Anterior">‹</button><button type="button" @click="mover(1)" aria-label="Siguiente">›</button></div>
        </div>
        <div class="sn-carril" x-ref="carril">
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['almacen'] }}"/></svg></div>
                <h3>Inventario</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.reportes.inventario') }}">Consultar</a>
            </article>
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['alerta'] }}"/></svg></div>
                <h3>Stock crítico</h3>
                <div class="cifra">{{ (int) ($stockCritico ?? 0) }}</div>
                <p class="sub">{{ ($stockCritico ?? 0) === 1 ? 'producto en el mínimo' : 'productos en el mínimo' }}</p>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.reportes.inventario') }}">Revisar</a>
            </article>
        </div>
    </section>
    @endif

    {{-- ── Reportes ──────────────────────────────────────────────── --}}
    @if($verReportes || $facturacion)
    <section class="sn-fila" x-data="snFila()">
        <div class="sn-fila-cab">
            <h2>Reportes</h2>
            <div class="sn-flechas"><button type="button" @click="mover(-1)" aria-label="Anterior">‹</button><button type="button" @click="mover(1)" aria-label="Siguiente">›</button></div>
        </div>
        <div class="sn-carril" x-ref="carril">
            @if($verReportes && $hay('bixosales.reportes.ventas.general'))
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['reporte'] }}"/></svg></div>
                <h3>Ventas del mes</h3>
                <div class="cifra">{{ $moneda }} {{ number_format((float) ($ventasMesTotal ?? 0), 2) }}</div>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.reportes.ventas.general') }}">Generar</a>
            </article>
            @endif
            @if($facturacion)
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['reporte'] }}"/></svg></div>
                <h3>Registro de ventas</h3>
                <form class="sn-mini" method="GET" action="{{ route('bixosales.facturas.registro') }}">
                    <input type="month" name="mes" value="{{ now()->format('Y-m') }}" aria-label="Mes">
                    <button type="submit" class="sn-btn">Generar</button>
                </form>
            </article>
            @endif
        </div>
    </section>
    @endif

    {{-- ── Barra inferior ────────────────────────────────────────── --}}
    <nav class="sn-tabs" aria-label="Accesos">
        <a class="sn-tab act" href="{{ route('bixosales.dashboard') }}">
            <svg fill="currentColor" viewBox="0 0 24 24"><path d="{{ $sn['casa'] }}"/></svg> Inicio
        </a>
        @if($vende)
        <a class="sn-tab" href="{{ route($hay($kpi['acc_route'] ?? '') ? $kpi['acc_route'] : 'bixosales.pos') }}">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['bolsa'] }}"/></svg> Vender
        </a>
        @elseif($emite)
        <a class="sn-tab" href="{{ route('bixosales.facturas', ['tipo' => 'factura']) }}">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sn['factura'] }}"/></svg> Emitir
        </a>
        @endif
    </nav>
</div>

<script>
function snFila() {
    return {
        mover(dir) {
            const c = this.$refs.carril;
            if (!c) return;
            const card = c.querySelector('.sn-card');
            c.scrollBy({ left: dir * ((card ? card.offsetWidth : 250) + 12), behavior: 'smooth' });
        },
    };
}
function snInicio() {
    return {
        doc: '', res: '', resOk: false, consultando: false,
        async consultar() {
            const n = this.doc.replace(/\D/g, '');
            if (n.length !== 8 && n.length !== 11) { this.res = 'Un RUC tiene 11 dígitos y un DNI 8.'; this.resOk = false; return; }
            this.consultando = true; this.res = 'Consultando...'; this.resOk = false;
            try {
                const r = await fetch(@json(\Illuminate\Support\Facades\Route::has('bixosales.facturas.ruc') ? route('bixosales.facturas.ruc') : '') + '?doc=' + n, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                const d = await r.json().catch(() => ({}));
                if (!d.ok) { this.res = d.message || 'No encontramos ese documento.'; return; }
                const extra = [d.estado, d.condicion].filter(Boolean).join(' · ');
                this.res = (d.razon_social || '') + (extra ? ' · ' + extra : '') + (d.direccion ? '. ' + d.direccion : '');
                this.resOk = !d.estado || String(d.estado).toUpperCase() === 'ACTIVO';
            } catch (e) {
                console.error('Consulta RUC/DNI en portada:', e);
                this.res = 'Sin conexión con el servidor. Inténtalo de nuevo.';
            } finally { this.consultando = false; }
        },
    };
}
</script>
