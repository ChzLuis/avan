{{-- PORTADA del modulo de Facturacion (2026-09-05).

     Replica de la app movil de SUNAT, que el usuario pidio copiar tal cual:
     cabecera roja con marca, globos y menu; ola roja con la carita y el
     "¡Atencion!" centrado con los pendientes; filas por area con tarjetas
     altas (icono celeste, titulo azul, boton azul abajo a la izquierda);
     barra inferior de dos pestanas; y el menu lateral azul con la mancha
     clara. En movil esta pagina sustituye la barra del sistema para que la
     cabecera sea la roja; en escritorio la roja es una banda y el menu de
     Sales sigue siendo el de siempre. --}}
<x-portal-layout layout="comercial" :project="$project" pageTitle="Facturación">
<x-slot name="slot">

@php
    $puedeEmitir  = $puedeEmitir ?? false;
    $lectorActivo = $lectorActivo ?? false;
    $hayPend      = count($pendientes) > 0;
    $moneda       = ($project->setting('currency') ?: 'PEN') === 'USD' ? 'US$' : 'S/';
    $nombreCorto  = $nombre ? \Illuminate\Support\Str::before($nombre, ' ') : '';
    $borradores   = collect($pendientes)->first(fn ($p) => $p['icono'] === 'borrador');
    $nBorradores  = $borradores ? (int) filter_var($borradores['titulo'], FILTER_SANITIZE_NUMBER_INT) : 0;

    $icono = [
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
        'ajustes'  => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm7.4-3a7.4 7.4 0 0 0-.1-1l2-1.6-2-3.4-2.4 1a7.5 7.5 0 0 0-1.7-1L14.8 3H9.2l-.4 2.6a7.5 7.5 0 0 0-1.7 1l-2.4-1-2 3.4L4.7 11a7.4 7.4 0 0 0 0 2l-2 1.6 2 3.4 2.4-1a7.5 7.5 0 0 0 1.7 1l.4 2.6h5.6l.4-2.6a7.5 7.5 0 0 0 1.7-1l2.4 1 2-3.4-2-1.6c.1-.3.1-.7.1-1z',
        'borrador' => 'M16.9 3.5a2 2 0 0 1 2.8 2.8L8.5 17.5 4 19l1.5-4.5z',
        'cuenta'   => 'M8 7h8m-8 4h8m-8 4h5M6 3h12a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z',
        'casa'     => 'M3 11 12 3l9 8v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z',
        'salir'    => 'M15 12H4m0 0 4-4m-4 4 4 4M13 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5',
        'sobre'    => 'M3 6h18v12H3zM3 7l9 6 9-6',
        'campana'  => 'M6 9a6 6 0 1 1 12 0v5l2 3H4l2-3zM10 20a2 2 0 0 0 4 0',
    ];
@endphp

@include('comercial.panel._sn-estilos')

<div class="sn" x-data="portadaFacturacion()">

    <div class="sn-in">

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
            <h2>{{ $hayPend ? (count($pendientes) === 1 ? '1 asunto pendiente' : count($pendientes).' asuntos pendientes') : 'Todo en orden' }}</h2>
            <p>{{ $hayPend ? 'Conviene resolverlos hoy. Desliza para verlos.' : 'Nada pendiente ante SUNAT ni cobros vencidos.' }}</p>
        </div>
    </div>

    @if($hayPend)
    <section class="sn-fila" style="padding-top:8px">
        <div class="sn-carril" x-ref="carril">
            @foreach($pendientes as $p)
            <article class="sn-card pend {{ $p['tono'] === 'ambar' ? 'amb' : ($p['tono'] === 'gris' ? 'gris' : '') }}">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono[$p['icono']] ?? $icono['alerta'] }}"/></svg></div>
                <h3>{{ $p['titulo'] }}</h3>
                <p class="det">{{ $p['detalle'] }}</p>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ $p['url'] }}">{{ $p['accion'] }}</a>
            </article>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ── Guias de remision ─────────────────────────────────────── --}}
    <section class="sn-fila" id="sn-guias" x-data="fila()">
        <div class="sn-fila-cab">
            <h2>Guía de Remisión Electrónica (GRE)</h2>
            <div class="sn-flechas"><button type="button" @click="mover(-1)" aria-label="Anterior">‹</button><button type="button" @click="mover(1)" aria-label="Siguiente">›</button></div>
        </div>
        <div class="sn-carril" x-ref="carril">
            @if($puedeEmitir)
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono['camion'] }}"/></svg></div>
                <h3>Emitir GRE</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('guias.index', ['nueva' => 1]) }}">Generar</a>
            </article>
            @endif
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono['lupa'] }}"/></svg></div>
                <h3>Guías emitidas</h3>
                <div class="aire"></div>
                {{-- Consultar lleva al HISTORICO. Apuntaba a la pantalla de
                     emitir, asi que quien venia a buscar una guia pasada
                     aterrizaba en un formulario en blanco. --}}
                <a class="sn-btn" href="{{ route('guias.consulta') }}">Consultar</a>
            </article>
            {{-- BAJA DE GRE: RETIRADA A PROPOSITO.
                 La comunicacion de baja de una guia ante SUNAT NO esta
                 implementada: el job `DarDeBajaEnSunat` solo maneja Invoice, y
                 `GuiaRemisionController::destroy` lo dice en su propio mensaje
                 ("no se borra, se anula ante SUNAT"). La tarjeta llevaba a un
                 formulario de emision y despues al historico, donde tampoco
                 hay ninguna accion de baja: un callejon sin salida con el
                 camion parado. Mejor no ofrecerla que mandar a ningun sitio.
                 Cuando exista la baja real, se repone aqui. --}}
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono['factura'] }}"/></svg></div>
                <h3>Guía desde una factura</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.facturas.consulta', ['estado' => 'accepted', 'tipo' => 'factura']) }}">Elegir</a>
            </article>
        </div>
    </section>

    {{-- ── Comprobantes de pago ──────────────────────────────────── --}}
    <section class="sn-fila" id="sn-comprobantes" x-data="fila()">
        <div class="sn-fila-cab">
            <h2>Comprobantes de pago</h2>
            <div class="sn-flechas"><button type="button" @click="mover(-1)" aria-label="Anterior">‹</button><button type="button" @click="mover(1)" aria-label="Siguiente">›</button></div>
        </div>
        <div class="sn-carril" x-ref="carril">
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg></div>
                <h3>Catálogo de Productos</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ \Illuminate\Support\Facades\Route::has('products.index') && $puedeEmitir ? route('products.index') : route('bixosales.pos') }}">Consultar</a>
            </article>
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono['lupa'] }}"/></svg></div>
                <h3>Comprobantes emitidos</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.facturas.consulta') }}">Consultar</a>
            </article>
            @if($puedeEmitir)
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono['boleta'] }}"/></svg></div>
                <h3>Facturas</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.facturas', ['tipo' => 'factura']) }}">Emitir</a>
            </article>
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono['boleta'] }}"/></svg></div>
                <h3>Boletas</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.facturas', ['tipo' => 'boleta']) }}">Emitir</a>
            </article>
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono['nota'] }}"/></svg></div>
                <h3>Notas de crédito y débito</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.facturas', ['tipo' => 'nota']) }}">Emitir</a>
            </article>
            @if($lectorActivo)
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono['camara'] }}"/></svg></div>
                <h3>Lector de comprobantes</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.facturas', ['tipo' => 'factura', 'lector' => 1]) }}">Leer</a>
            </article>
            @endif
            @endif
        </div>
    </section>

    {{-- ── Cobranzas y pagos ─────────────────────────────────────── --}}
    <section class="sn-fila" id="sn-cobranzas" x-data="fila()">
        <div class="sn-fila-cab">
            <h2>Cobranzas y pagos</h2>
            <div class="sn-flechas"><button type="button" @click="mover(-1)" aria-label="Anterior">‹</button><button type="button" @click="mover(1)" aria-label="Siguiente">›</button></div>
        </div>
        <div class="sn-carril" x-ref="carril">
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono['billetes'] }}"/></svg></div>
                <h3>Cuentas por cobrar</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.cuentas') }}">Empezar</a>
            </article>
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono['reloj'] }}"/></svg></div>
                <h3>Pagos por aprobar</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.dashboard').'#pagos-por-aprobar' }}">Consultar</a>
            </article>
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono['cuenta'] }}"/></svg></div>
                <h3>Condiciones de pago</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.cuentas.condiciones') }}">Configurar</a>
            </article>
        </div>
    </section>

    {{-- ── Consultas ─────────────────────────────────────────────── --}}
    <section class="sn-fila" id="sn-consultas" x-data="fila()">
        <div class="sn-fila-cab">
            <h2>Consultas</h2>
            <div class="sn-flechas"><button type="button" @click="mover(-1)" aria-label="Anterior">‹</button><button type="button" @click="mover(1)" aria-label="Siguiente">›</button></div>
        </div>
        <div class="sn-carril" x-ref="carril">
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono['lupa'] }}"/></svg></div>
                <h3>RUC / DNI</h3>
                <form class="sn-mini" @submit.prevent="consultar()">
                    <input type="text" inputmode="numeric" maxlength="11" placeholder="Número" x-model="doc" aria-label="RUC o DNI">
                    <button type="submit" class="sn-btn" :disabled="consultando" x-text="consultando ? '...' : 'Empezar'">Empezar</button>
                </form>
                <div class="sn-res" x-show="res" x-cloak :style="resOk ? 'color:#065f46' : 'color:#b45309'" x-text="res"></div>
            </article>
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono['lupa'] }}"/></svg></div>
                <h3>Comprobantes de Pago</h3>
                <form class="sn-mini" method="GET" action="{{ route('bixosales.facturas.consulta') }}">
                    <input type="search" name="q" placeholder="F001-12, cliente, RUC" aria-label="Buscar comprobante">
                    <button type="submit" class="sn-btn">Ir</button>
                </form>
            </article>
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono['usuarios'] }}"/></svg></div>
                <h3>Tus clientes</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.clientes') }}">Empezar</a>
            </article>
        </div>
    </section>

    {{-- ── Reportes ──────────────────────────────────────────────── --}}
    <section class="sn-fila" id="sn-reportes" x-data="fila()">
        <div class="sn-fila-cab">
            <h2>Reportes</h2>
            <div class="sn-flechas"><button type="button" @click="mover(-1)" aria-label="Anterior">‹</button><button type="button" @click="mover(1)" aria-label="Siguiente">›</button></div>
        </div>
        <div class="sn-carril" x-ref="carril">
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono['reporte'] }}"/></svg></div>
                <h3>Registro de ventas</h3>
                <form class="sn-mini" method="GET" action="{{ route('bixosales.facturas.registro') }}">
                    <input type="month" name="mes" value="{{ now()->format('Y-m') }}" aria-label="Mes">
                    <button type="submit" class="sn-btn">Generar</button>
                </form>
            </article>
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono['reporte'] }}"/></svg></div>
                <h3>Facturado en {{ ucfirst(now()->locale('es')->translatedFormat('F')) }}</h3>
                <div class="cifra">{{ $moneda }} {{ number_format($mes['total'], 2) }}</div>
                <p class="sub">{{ $mes['n'] }} {{ $mes['n'] === 1 ? 'comprobante' : 'comprobantes' }}, sin anulados</p>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.facturas.consulta', ['desde' => now()->startOfMonth()->toDateString(), 'hasta' => now()->toDateString()]) }}">Ver detalle</a>
            </article>
            <article class="sn-card">
                <div class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono['reporte'] }}"/></svg></div>
                <h3>Resumen de ventas</h3>
                <div class="aire"></div>
                <a class="sn-btn" href="{{ route('bixosales.dashboard') }}">Generar</a>
            </article>
        </div>
    </section>

    </div>{{-- .sn-in --}}

</div>

<script>
/* Cada fila mueve su propio carril; en el movil se desliza con el dedo. */
function fila() {
    return {
        mover(dir) {
            const c = this.$refs.carril;
            if (!c) return;
            const card = c.querySelector('.sn-card');
            c.scrollBy({ left: dir * ((card ? card.offsetWidth : 250) + 12), behavior: 'smooth' });
        },
    };
}

function portadaFacturacion() {
    return {
        doc: '', res: '', resOk: false, consultando: false,
        async consultar() {
            const n = this.doc.replace(/\D/g, '');
            if (n.length !== 8 && n.length !== 11) {
                this.res = 'Un RUC tiene 11 dígitos y un DNI 8.'; this.resOk = false; return;
            }
            this.consultando = true; this.res = 'Consultando...'; this.resOk = false;
            try {
                const r = await fetch(@json(route('bixosales.facturas.ruc')) + '?doc=' + n, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                const d = await r.json().catch(() => ({}));
                if (!d.ok) { this.res = d.message || 'No encontramos ese documento.'; return; }
                const extra = [d.estado, d.condicion].filter(Boolean).join(' · ');
                this.res = (d.razon_social || '') + (extra ? ' · ' + extra : '') + (d.direccion ? '. ' + d.direccion : '');
                this.resOk = !d.estado || String(d.estado).toUpperCase() === 'ACTIVO';
            } catch (e) {
                console.error('Consulta RUC/DNI en portada:', e);
                this.res = 'Sin conexión con el servidor. Inténtalo de nuevo.';
            } finally {
                this.consultando = false;
            }
        },
    };
}
</script>

</x-slot>
</x-portal-layout>
