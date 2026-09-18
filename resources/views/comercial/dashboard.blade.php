<x-portal-layout layout="comercial" :project="$project">
@php
$cat = $project->category ?? 'default';
$esRest = in_array($cat, ['restaurante','cafeteria']);
$esLavanderia = \App\Modules\Ventas\Support\OrderFlow::supportsFlow($cat); // "tiene flujo de estados" (cualquier rubro)
$lavStates = $esLavanderia ? \App\Modules\Ventas\Support\OrderFlow::activeStates($project) : [];
$lavOverdue = $esLavanderia ? \App\Modules\Ventas\Support\OrderFlow::overdueCount($project) : 0;

// KPI labels por rubro
$kpi = match(true) {
    in_array($cat, ['restaurante','cafeteria']) => ['v'=>'Ventas hoy','p'=>'Pedidos / Mesas','pend'=>'En cocina','acc'=>'Vender','acc_route'=>'bixosales.pos'],
    in_array($cat, ['peluqueria','salon_belleza']) => ['v'=>'Facturación hoy','p'=>'Atenciones hoy','pend'=>'Por atender','acc'=>'Cobrar servicio','acc_route'=>'bixosales.pos'],
    $cat==='clinica'    => ['v'=>'Facturación hoy','p'=>'Atenciones hoy','pend'=>'Por atender','acc'=>'Cobrar atención','acc_route'=>'bixosales.pos'],
    $cat==='gimnasio'   => ['v'=>'Cobros hoy','p'=>'Membresías hoy','pend'=>'Por cobrar','acc'=>'Cobrar membresía','acc_route'=>'bixosales.pos'],
    $cat==='taller'     => ['v'=>'Facturación hoy','p'=>'Órdenes hoy','pend'=>'En taller','acc'=>'Vender','acc_route'=>'bixosales.pos'],
    in_array($cat, ['comercial']) => ['v'=>'Cobrado hoy','p'=>'Ventas hoy','pend'=>'Por confirmar','acc'=>'Ver ventas','acc_route'=>'bixosales.pedidos'],
    default             => ['v'=>'Ventas hoy','p'=>'Pedidos hoy','pend'=>'Por atender','acc'=>'Nueva venta','acc_route'=>'bixosales.pos'],
};

$hayScore   = isset($semScore) && $semScore !== null;
$scoreDeg   = $hayScore ? round($semScore * 3.6) : 0;
$scoreColor = $hayScore
    ? ($semScore >= 80 ? '#10B981' : ($semScore >= 60 ? '#F59E0B' : '#EF4444'))
    : '#E5E8EF';

// ── Avisos del negocio (los usa el centro operativo y la portada movil) ──
    $avisos = [];

    // El unico aviso con fecha de muerte: SUNAT deja de aceptar el envio a
    // los 3 dias de la emision, asi que va primero y con la cuenta atras.
    if (($sunatRiesgo['n'] ?? 0) > 0) {
        $n = $sunatRiesgo['n'];
        $dias = $sunatRiesgo['dias'];
        $avisos[] = [
            'nivel' => 'alto',
            'titulo' => $n.' comprobante'.($n === 1 ? '' : 's').' sin aceptar por SUNAT',
            'detalle' => $dias === 0 ? 'El plazo de envío vence HOY' : 'Al más urgente le quedan '.$dias.' día'.($dias === 1 ? '' : 's').' de plazo',
            'accion' => 'Enviar', 'url' => route('bixosales.facturas'),
            'icono' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
        ];
    }

    // Mercaderia que salio por VENTA sin factura ni boleta vinculada: cada
    // dia que pasa es mas dificil de explicar ante una fiscalizacion.
    if (($guiasSinComprobante ?? 0) > 0) {
        $n = $guiasSinComprobante;
        $avisos[] = [
            'nivel' => 'alto',
            'titulo' => $n.' entrega'.($n === 1 ? '' : 's').' por venta sin comprobante',
            'detalle' => 'Guía'.($n === 1 ? '' : 's').' de remisión con motivo venta sin factura ni boleta',
            // Este panel vive en Ventas: el aviso tiene que llevar a las guias
            // de Ventas, no sacar al operador a la cara de Configuracion.
            'accion' => 'Revisar', 'url' => route('bixosales.guias.index'),
            'icono' => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
        ];
    }

    if (($docsVencidos ?? 0) > 0) {
        $avisos[] = [
            'nivel' => 'alto',
            'titulo' => $docsVencidos.' documento'.($docsVencidos === 1 ? '' : 's').' vencido'.($docsVencidos === 1 ? '' : 's'),
            'detalle' => 'S/ '.number_format($vencido ?? 0, 2).' pendientes de cobro',
            'accion' => 'Cobrar', 'url' => route('bixosales.cuentas'),
            'icono' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z',
        ];
    }
    if ($esLavanderia && $lavOverdue > 0) {
        $avisos[] = [
            'nivel' => 'alto',
            'titulo' => $lavOverdue.' pedido'.($lavOverdue === 1 ? '' : 's').' fuera de plazo',
            'detalle' => 'Superaron el tiempo configurado para su estado',
            'accion' => 'Atender', 'url' => route('bixosales.pedidos'),
            'icono' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ];
    }
    if (($pendientes + ($enProceso ?? 0)) > 0) {
        $n = $pendientes + ($enProceso ?? 0);
        $avisos[] = [
            'nivel' => 'medio',
            'titulo' => $n.' pedido'.($n === 1 ? '' : 's').' pendiente'.($n === 1 ? '' : 's'),
            'detalle' => $pendientes.' sin empezar · '.($enProceso ?? 0).' en proceso',
            'accion' => 'Atender', 'url' => route('bixosales.pedidos'),
            'icono' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z',
        ];
    }
    // Una cotizacion aceptada no es deuda: es trabajo a medias. El cliente
    // dijo que si y falta convertirla en pedido para que exista la venta.
    // Antes su importe se sumaba a "por cobrar", que es lo que hacia parecer
    // que el cliente ya debia ese dinero.
    if (($porConvertir['n'] ?? 0) > 0) {
        $avisos[] = [
            'nivel' => 'medio',
            'titulo' => $porConvertir['n'].' cotización'.($porConvertir['n'] === 1 ? '' : 'es').' aceptada'.($porConvertir['n'] === 1 ? '' : 's').' sin convertir',
            'detalle' => 'S/ '.number_format($porConvertir['cents'] / 100, 2).' en juego: falta generar el pedido',
            'accion' => 'Convertir', 'url' => route('bixosales.cotizaciones'),
            'icono' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
        ];
    }
    if (($stockCritico ?? 0) > 0) {
        $avisos[] = [
            'nivel' => 'bajo',
            'titulo' => $stockCritico.' producto'.($stockCritico === 1 ? '' : 's').' con stock crítico',
            'detalle' => 'Requieren reposición',
            'accion' => 'Revisar', 'url' => route('bixosales.reportes.inventario'),
            'icono' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z',
        ];
    }
    if (($waPendientes ?? 0) > 0) {
        $avisos[] = [
            'nivel' => 'medio',
            'titulo' => $waPendientes.' pedido'.($waPendientes === 1 ? '' : 's').' de WhatsApp sin cerrar',
            'detalle' => 'Llegaron por el bot y no se han entregado',
            'accion' => 'Ver', 'url' => route('bixosales.pedidos'),
            'icono' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ];
    }

    $paleta = [
        'alto'  => ['bg-red-50 text-red-600', 'bg-red-500'],
        'medio' => ['bg-amber-50 text-amber-600', 'bg-amber-500'],
        'bajo'  => ['bg-yellow-50 text-yellow-700', 'bg-yellow-400'],
    ];
@endphp

{{-- En movil la portada es la de la app (tarjetas por area); el centro
     operativo de abajo queda para escritorio. --}}
@include('comercial.panel.inicio-movil')

{{-- ══════════════════════════════════════════════════════
     RESUMEN DEL NEGOCIO
     Orden de lectura: como voy hoy → que tengo que hacer → por que
     esta pasando → a donde entro. Antes la pantalla abria con un
     marcador y un semaforo cuyas tres ultimas luces (Caja, Logistica,
     Stock) eran constantes escritas a mano: decian "Stock: niveles
     normales" sin consultar una sola fila del catalogo.
══════════════════════════════════════════════════════ --}}
<div class="co-wrap" id="centroOp" x-data="centroOp()" x-init="init()">

    {{-- ── Cabecera ── --}}
    <header class="flex flex-wrap items-start justify-between gap-x-4 gap-y-3">
        <div class="min-w-0">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-semibold tracking-tight text-slate-900">Resumen del negocio</h1>
                @if($hayScore)
                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold"
                      style="border-color:{{ $scoreColor }}33;background:{{ $scoreColor }}14;color:{{ $scoreColor }}">
                    BIXO Score {{ $semScore }}/100
                </span>
                @endif
            </div>
            <p class="mt-0.5 text-sm text-slate-500">
                Hoy es {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                @if($varVentas !== null)
                    · <span class="font-medium {{ $varVentas >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $varVentas >= 0 ? '+' : '' }}{{ $varVentas }}% en ventas vs ayer
                    </span>
                @endif
            </p>
        </div>
        {{-- Las dos acciones de cabecera se ofrecian a todo el mundo. Un
             vendedor sin permiso de cotizar veia el boton y se comia un 403
             al pulsarlo: ofrecer lo que no se puede hacer es peor que no
             ofrecerlo. Se comprueban los mismos permisos que exige la ruta. --}}
        @php
            $_u2 = auth()->user();
            $_puedeVender  = $_u2?->is_superadmin || $_u2?->can('pos.usar');
            $_puedeCotizar = $_u2?->is_superadmin || $_u2?->can('quotes.crear') || $_u2?->can('manage-quotes');
        @endphp
        <div class="flex flex-wrap items-center gap-2">
            @if($_puedeVender)
            <a href="{{ route('bixosales.pos') }}"
               class="inline-flex h-10 items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Nueva venta
            </a>
            @endif
            @if($_puedeCotizar)
            <a href="{{ route('bixosales.cotizaciones') }}"
               class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Nueva cotización
            </a>
            @endif
        </div>
    </header>

    {{-- Orden de lectura: como voy → que hago → por que pasa → de donde
         viene la venta. `items-start` es deliberado: cada tarjeta termina
         donde termina su contenido y no se estira para igualar a su vecina,
         que es lo que dejaba media pantalla en blanco. --}}
    @include('comercial.panel.arranque')

    @include('comercial.panel.kpis')

    <div class="grid grid-cols-1 items-start gap-3 lg:grid-cols-12">
        <div class="lg:col-span-5">@include('comercial.panel.atencion')</div>
        <div class="lg:col-span-7">@include('comercial.panel.ventas')</div>
    </div>

    <div class="grid grid-cols-1 items-start gap-3 lg:grid-cols-12">
        <div class="lg:col-span-5">@include('comercial.panel.estado-pedidos')</div>
        <div class="lg:col-span-7">@include('comercial.panel.canales')</div>
    </div>

    @if($esRest)
    <div class="co-section">
        <div class="co-section-header">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Mesas</h2>
                <p class="text-xs text-slate-500">Ocupación en tiempo real</p>
            </div>
            <div style="display:flex; gap:8px; align-items:center;">
                {{-- Leyenda --}}
                <div class="mapa-leyenda">
                    <span class="ley-dot" style="background:#10B981;"></span><span>Libre</span>
                    <span class="ley-dot" style="background:#F59E0B;"></span><span>Atención</span>
                    <span class="ley-dot" style="background:#EF4444;"></span><span>Urgente</span>
                    <span class="ley-dot" style="background:#E5E8EF;"></span><span>Cerrada</span>
                </div>
                <a href="{{ route('bixosales.mesas') }}"
                   style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px;
                          background:var(--blue); color:#fff; border-radius:8px;
                          font-size:12px; font-weight:600; text-decoration:none;
                          transition:background .1s;"
                   onmouseover="this.style.background='#1D4ED8'"
                   onmouseout="this.style.background='#2563EB'">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Abrir mapa completo
                </a>
            </div>
        </div>

        <div class="mapa-grid" id="mapaMesas" x-ref="mapa">
            <template x-if="mesasLoading">
                <div style="grid-column:1/-1; text-align:center; padding:40px 0; color:var(--muted);">
                    <svg style="width:24px;height:24px;animation:spin 1s linear infinite;margin:0 auto 8px;"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <p style="font-size:13px;">Cargando mesas...</p>
                </div>
            </template>
            <template x-if="!mesasLoading && mesas.length === 0">
                <div style="grid-column:1/-1; text-align:center; padding:40px 0;">
                    <p style="font-size:13px; color:var(--muted);">No hay mesas configuradas.</p>
                    <a href="{{ route('bixosales.mesas') }}" style="font-size:12px; color:var(--blue);">
                        Ir al mapa de mesas →
                    </a>
                </div>
            </template>
            <template x-for="m in mesas" :key="m.number">
                <div class="mesa-cell"
                     :style="mesaStyle(m)"
                     @click="window.location='{{ route('bixosales.mesas') }}'">
                    <div class="mesa-num" x-text="'M' + m.number"></div>
                    <div class="mesa-timer" x-show="m.minutes !== null">
                        <svg style="width:10px;height:10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span x-text="m.minutes + 'm'"></span>
                    </div>
                    <div class="mesa-estado" x-text="mesaLabel(m)"></div>
                    <div class="mesa-monto" x-show="m.total > 0" x-text="'S/ ' + m.total.toLocaleString()"></div>
                </div>
            </template>
        </div>

    </div>
    @endif

    {{-- ── SECCIÓN 3: OBJETOS OPERATIVOS ── --}}
    {{-- ══ PAGOS POR APROBAR (Yape/Plin reportados en el bot) ══ --}}
    {{-- Con ancla: las tarjetas "Pagos por aprobar" de las portadas enlazaban
         al ENDPOINT `bixosales.pagos.pendientes`, que es POST, asi que un
         <a href> daba error 405 y el boton estaba muerto. Ahora traen aqui. --}}
    <div id="pagos-por-aprobar" class="co-section" x-data="pagosAprobar()" x-init="cargar()" x-show="pedidos.length > 0" x-cloak>
        <div class="co-section-header">
            <div>
                <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                    Pagos por aprobar
                    <span x-text="pedidos.length"
                          class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700"></span>
                </h2>
                <p class="text-xs text-slate-500">Yape/Plin reportados por el cliente</p>
            </div>
            <button @click="cargar()" style="font-size:12px;color:var(--blue);background:none;border:none;cursor:pointer;font-weight:500;">↻ Actualizar</button>
        </div>

        <div style="display:flex;flex-direction:column;gap:10px;">
            <template x-for="p in pedidos" :key="p.id">
                <div style="background:#fff;border:1px solid #FDE68A;border-left:4px solid #F59E0B;border-radius:12px;padding:14px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:200px;">
                        <p style="font-weight:700;color:#111827;margin:0;">
                            <span x-text="p.cliente"></span>
                            <span style="font-weight:400;color:#6B7280;font-size:12px;" x-text="' · ' + p.telefono"></span>
                        </p>
                        <p style="margin:3px 0 0;font-size:13px;color:#6B7280;">
                            Pedido #<span x-text="p.id"></span> · <span x-text="p.metodo"></span> · <span x-text="p.fecha"></span>
                        </p>
                    </div>
                    <p style="font-size:20px;font-weight:800;color:#059669;margin:0;" x-text="'S/ ' + p.total.toFixed(2)"></p>
                    <div style="display:flex;gap:8px;">
                        <button @click="aprobar(p)" :disabled="cargando"
                                style="background:#059669;color:#fff;border:none;padding:9px 18px;border-radius:9px;font-weight:700;font-size:13px;cursor:pointer;">
                            ✅ Aprobar
                        </button>
                        <button @click="rechazar(p)" :disabled="cargando"
                                style="background:#fff;color:#DC2626;border:1px solid #FCA5A5;padding:9px 16px;border-radius:9px;font-weight:600;font-size:13px;cursor:pointer;">
                            Rechazar
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <template x-if="mensaje">
            <div style="margin-top:12px;background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;padding:12px;">
                <p style="margin:0 0 6px;font-size:12px;color:#065F46;font-weight:700;">Mensaje para enviar al cliente:</p>
                <p style="margin:0;font-size:13px;color:#065F46;white-space:pre-wrap;" x-text="mensaje"></p>
                <button @click="copiar()" style="margin-top:8px;background:#059669;color:#fff;border:none;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;"
                        x-text="copiado ? '¡Copiado!' : 'Copiar mensaje'"></button>
            </div>
        </template>
    </div>

    <script>
    function pagosAprobar() {
        return {
            pedidos: [], cargando: false, mensaje: '', copiado: false,
            async cargar() {
                try {
                    const r = await fetch('{{ route("bixosales.pagos.pendientes") }}', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content,'Accept':'application/json'},
                        body: '{}'
                    });
                    const d = await r.json();
                    this.pedidos = d.pedidos || [];
                } catch(e) { this.pedidos = []; }
            },
            /* Aprobar mueve dinero: se pregunta, como ya hacia Rechazar. Sin
               confirmacion, un toque accidental daba por bueno un Yape que
               nadie habia comprobado. */
            async aprobar(p) {
                const ok = await bxConfirmar({
                    titulo: 'Aprobar el pago',
                    descripcion: 'Se dará por cobrado el pedido de ' + (p.client_name || 'este cliente')
                        + ' por S/ ' + Number(p.total || 0).toFixed(2) + '. ¿Confirmas que el pago llegó?',
                    boton: 'Aprobar pago',
                });
                if (! ok) return;
                await this.accion('{{ route("bixosales.pagos.aprobar") }}', { order_id: p.id });
            },
            async rechazar(p) {
                const motivo = await bxConfirmar({
                    titulo: 'Rechazar el pago',
                    descripcion: 'Escribe el motivo del rechazo. El cliente lo verá tal cual.',
                    boton: 'Rechazar pago',
                    entrada: { etiqueta: 'Motivo', requerido: true, valor: 'No pudimos validar el comprobante' },
                });
                if (motivo === null) return;
                await this.accion('{{ route("bixosales.pagos.rechazar") }}', { order_id: p.id, motivo });
            },
            async accion(url, body) {
                this.cargando = true;
                try {
                    const r = await fetch(url, {
                        method: 'POST',
                        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content,'Accept':'application/json'},
                        body: JSON.stringify(body)
                    });
                    const d = await r.json();
                    if (d.ok) {
                        this.mensaje = d.mensaje_cliente || '';
                        await this.cargar();
                        bxAviso('Listo. El pedido se actualizó.', 'success');
                    } else {
                        /* Sin `else` y con el catch vacio, un 403 o un 422
                           dejaban la pantalla muda: el operador creia haber
                           aprobado y el pedido seguia en revision. */
                        bxAviso(d.message || d.error || 'No se pudo completar la acción.', 'error');
                    }
                } catch(e) {
                    bxAviso('Sin conexión: la acción no se completó.', 'error');
                }
                this.cargando = false;
            },
            async copiar() {
                try { await navigator.clipboard.writeText(this.mensaje); this.copiado = true; setTimeout(()=>this.copiado=false, 1500); } catch(e) {}
            },
        }
    }
    </script>

    {{-- La cola operativa sustituye a las tarjetas de "Pedidos en curso" y
         al listado de "Ultimos pedidos": eran dos formas distintas de contar
         los mismos pedidos que el KPI y el bloque de atencion ya cuentan.
         "Acceso rapido" tambien sale — esa navegacion vive en el menu — y
         "Productos mas vendidos" se va a Reportes, que es donde se analiza. --}}
    @include('comercial.panel.cola-pedidos')

    <div class="grid grid-cols-1 items-start gap-3 lg:grid-cols-12">
        <div class="lg:col-span-5">@include('comercial.panel.conversion')</div>
        <div class="lg:col-span-7">@include('comercial.panel.actividad')</div>
    </div>

</div>

{{-- ══ ESTILOS DEL CENTRO OPERATIVO ══ --}}
<style>
/* Wrapper */
.co-wrap {
    padding: 20px;
    display: flex; flex-direction: column; gap: 20px;
    overflow-y: auto; height: 100%;
}

/* Header: Score + Semáforo + KPIs */
.co-header {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 16px;
    align-items: stretch;
}
@media (max-width: 1100px) {
    .co-header { grid-template-columns: 1fr 1fr; }
    .kpi-strip { grid-column: 1/-1; }
}
@media (max-width: 700px) {
    .co-header { grid-template-columns: 1fr; }
}

/* Score card */
.score-card {
    display: flex; align-items: center; gap: 16px;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; padding: 16px 20px;
    box-shadow: var(--shadow-sm);
}
.score-ring-lg {
    width: 72px; height: 72px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.score-inner-lg {
    width: 54px; height: 54px; border-radius: 50%;
    background: var(--surface);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.score-num { font-size: 18px; font-weight: 800; color: var(--blue); line-height: 1; }
.score-den { font-size: 9px; color: var(--muted); font-weight: 500; }
.score-info { display: flex; flex-direction: column; gap: 4px; }
.score-title { font-size: 13px; font-weight: 700; color: var(--text); }
.score-sub { font-size: 11px; color: var(--muted); }
.score-bar-wrap {
    width: 110px; height: 4px; background: var(--border); border-radius: 2px; margin-top: 4px;
}
.score-bar-fill { height: 100%; border-radius: 2px; transition: width .5s; }

/* Semáforo card */
.semaforo-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; padding: 16px 20px;
    box-shadow: var(--shadow-sm);
}
.sema-grid {
    display: flex; flex-wrap: wrap; gap: 10px 20px; margin-top: 10px;
}
.sema-item {
    display: flex; align-items: center; gap: 7px; cursor: pointer;
}
.sema-dot-lg {
    width: 11px; height: 11px; border-radius: 50%; flex-shrink: 0;
}
.sema-dot-green  { background: #10B981; box-shadow: 0 0 0 3px #D1FAE5; }
.sema-dot-yellow { background: #F59E0B; box-shadow: 0 0 0 3px #FDE68A; }
.sema-dot-red    { background: #EF4444; box-shadow: 0 0 0 3px #FECACA; animation: sema-pulse 1.8s infinite; }
.sema-dot-gray   { background: #D1D5DB; box-shadow: 0 0 0 3px #F3F4F6; }
.sema-label { font-size: 12px; font-weight: 500; color: var(--text); }

/* KPI strip */
.kpi-strip {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;
}
@media (max-width: 800px) {
    .kpi-strip { grid-template-columns: repeat(2, 1fr); }
}
.kpi-mini {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 12px 14px;
    box-shadow: var(--shadow-sm);
}
.kpi-mini-label { font-size: 11px; color: var(--muted); font-weight: 500; margin-bottom: 4px; }
.kpi-mini-val   { font-size: 22px; font-weight: 800; color: var(--text); line-height: 1.1; }
.kpi-mini-trend { font-size: 11px; font-weight: 500; }

/* Section headers */
.section-label {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: var(--muted); margin-bottom: 4px;
}
.section-title { font-size: 16px; font-weight: 700; color: var(--text); }
.co-section { display: flex; flex-direction: column; gap: 12px; }
.co-section-header {
    display: flex; align-items: flex-end; justify-content: space-between;
}

/* Leyenda mapa */
.mapa-leyenda {
    display: flex; align-items: center; gap: 10px;
    font-size: 11px; color: var(--muted); font-weight: 500;
}
.ley-dot {
    width: 9px; height: 9px; border-radius: 50%; display: inline-block;
}

/* Mapa de mesas */
.mapa-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 10px;
}
.mesa-cell {
    background: var(--surface); border: 2px solid var(--border);
    border-radius: 12px; padding: 12px 10px 10px;
    display: flex; flex-direction: column; align-items: center;
    cursor: pointer; transition: all .15s;
    min-height: 90px; position: relative;
    text-align: center;
}
.mesa-cell:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); }
.mesa-num  { font-size: 15px; font-weight: 800; margin-bottom: 4px; }
.mesa-timer { font-size: 10px; font-weight: 600; display: flex; align-items: center; gap: 3px; }
.mesa-estado { font-size: 10px; font-weight: 500; color: var(--muted); margin-top: 2px; }
.mesa-monto  { font-size: 11px; font-weight: 700; margin-top: 4px; }

/* Objetos operativos */
.objetos-scroll {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 10px;
}
.obj-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 14px;
    display: flex; flex-direction: column; gap: 10px;
    text-decoration: none; color: inherit;
    position: relative; overflow: hidden;
    transition: box-shadow .15s, border-color .15s;
    border-left: 3px solid transparent;
}
.obj-card:hover { box-shadow: var(--shadow-md); }
.obj-card.obj-green  { border-left-color: #10B981; }
.obj-card.obj-yellow { border-left-color: #F59E0B; }
.obj-card.obj-red    { border-left-color: #EF4444; }
.obj-header { display: flex; align-items: flex-start; gap: 8px; }
.obj-icon {
    width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.obj-icon-green  { background: #D1FAE5; color: #059669; }
.obj-icon-yellow { background: #FEF3C7; color: #D97706; }
.obj-icon-red    { background: #FEE2E2; color: #DC2626; }
.obj-meta { flex: 1; min-width: 0; }
.obj-name { font-size: 13px; font-weight: 700; color: var(--text); }
.obj-sub  { font-size: 11px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.obj-body { display: flex; flex-direction: column; gap: 4px; }
.obj-row  { display: flex; justify-content: space-between; align-items: center; font-size: 11px; }
.obj-row-label { color: var(--muted); }
.obj-val  { font-weight: 600; color: var(--text); }
.obj-status { font-size: 10px; font-weight: 600; padding: 1px 7px; border-radius: 99px; }
.obj-status-green  { background: #D1FAE5; color: #065F46; }
.obj-status-yellow { background: #FEF3C7; color: #92400E; }
.obj-status-red    { background: #FEE2E2; color: #991B1B; }

/* Cronómetros */
.timer { display: inline-flex; align-items: center; gap: 3px; padding: 2px 7px; border-radius: 99px; font-size: 11px; font-weight: 600; flex-shrink: 0; }
.timer-green  { background: #D1FAE5; color: #059669; }
.timer-yellow { background: #FEF3C7; color: #D97706; }
.timer-red    { background: #FEE2E2; color: #DC2626; animation: timer-pulse 2s infinite; }

/* Bottom grid */
.co-bottom-grid {
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 14px;
    flex-direction: unset !important;
}
@media (max-width: 900px) {
    .co-bottom-grid { grid-template-columns: 1fr; }
}
.co-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; padding: 16px;
    box-shadow: var(--shadow-sm);
}
.co-card-wide { grid-column: span 1; } /* ajustado por grid padre */
.co-card-header {
    display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;
}
.top-item { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.top-rank {
    width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0;
    background: var(--blue-light); color: var(--blue);
    font-size: 10px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}
.top-info { flex: 1; min-width: 0; }
.top-name { font-size: 12px; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.top-bar-wrap { height: 3px; background: var(--border); border-radius: 2px; margin-top: 4px; overflow: hidden; }
.top-bar-fill { height: 100%; background: var(--blue); border-radius: 2px; }
.top-nums { text-align: right; flex-shrink: 0; }
.top-qty   { font-size: 11px; font-weight: 700; color: var(--text); }
.top-total { font-size: 10px; color: var(--muted); }

/* Ops genéricas */
.ops-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 12px; }
@media (max-width: 800px) { .ops-grid { grid-template-columns: 1fr; } }
.ops-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 16px; box-shadow: var(--shadow-sm);
}
.ops-card-wide { grid-column: 1; }
.ops-card-title { font-size: 12px; font-weight: 600; color: var(--text); margin-bottom: 10px; }

@keyframes timer-pulse { 0%,100%{opacity:1} 50%{opacity:.65} }
@keyframes sema-pulse  { 0%,100%{box-shadow:0 0 0 3px #FECACA} 50%{box-shadow:0 0 0 6px #FEE2E2} }
@keyframes spin        { to{transform:rotate(360deg)} }
</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Ventas: un solo grafico, tres rangos reales ──────────
// Las tres series llegan calculadas del servidor. El selector cambia los
// datos del mismo canvas; no hay tres graficos escondidos ni datos de
// relleno para el rango que no se este mirando.
function panelVentas(series) {
    return {
        series: series || {},
        rango: '7d',
        chart: null,
        get actual() {
            return this.series[this.rango] || { labels: [], data: [], total: 0, varianza: null, vacia: true };
        },
        fmt(n) {
            return (Number(n) || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        init() {
            this.$nextTick(() => this.pintar());
        },
        elegir(r) {
            this.rango = r;
            this.$nextTick(() => this.pintar());
        },
        pintar() {
            const el = document.getElementById('chartVentas');
            if (!el || typeof Chart === 'undefined' || this.actual.vacia) return;
            if (this.chart) { this.chart.destroy(); this.chart = null; }
            this.chart = new Chart(el, {
                type: 'line',
                data: {
                    labels: this.actual.labels,
                    datasets: [{
                        data: this.actual.data,
                        borderColor: '#4F46E5',
                        backgroundColor: 'rgba(79,70,229,0.06)',
                        borderWidth: 2,
                        pointRadius: this.actual.labels.length > 14 ? 0 : 3,
                        pointBackgroundColor: '#4F46E5',
                        tension: 0.35, fill: true,
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, border: { display: false }, grid: { color: '#F1F5F9' },
                             ticks: { font: { size: 10 }, color: '#94A3B8', callback: v => 'S/ ' + v } },
                        x: { border: { display: false }, grid: { display: false },
                             ticks: { font: { size: 10 }, color: '#94A3B8', maxRotation: 0, autoSkipPadding: 12 } }
                    }
                }
            });
        },
    };
}

// ── Alpine: Centro Operativo ─────────────────────────────
function centroOp() {
    return {
        mesas: [], mesasLoading: true,
        async init() {
            @if($esRest)
            try {
                const r = await fetch('{{ route("bixosales.mesas.data") }}');
                const d = await r.json();
                this.mesas = d.mesas || [];
            } catch(e) { this.mesas = []; }
            this.mesasLoading = false;
            // refresh cada 20s
            setInterval(async () => {
                try {
                    const r = await fetch('{{ route("bixosales.mesas.data") }}');
                    const d = await r.json();
                    this.mesas = d.mesas || [];
                } catch(e) {}
            }, 20000);
            @else
            this.mesasLoading = false;
            @endif
        },
        mesaStyle(m) {
            const colors = {
                libre:     { bg: '#F0FDF4', border: '#10B981', text: '#065F46' },
                pedido:    { bg: '#FFFBEB', border: '#F59E0B', text: '#92400E' },
                cooking:   { bg: '#FFF7ED', border: '#F97316', text: '#7C2D12' },
                ready:     { bg: '#EFF6FF', border: '#3B82F6', text: '#1E40AF' },
                cerrada:   { bg: '#F9FAFB', border: '#E5E7EB', text: '#6B7280' },
            };
            const c = colors[m.status] || colors.cerrada;
            return `background:${c.bg}; border-color:${c.border}; color:${c.text};`;
        },
        mesaLabel(m) {
            const labels = { libre:'Libre', pedido:'Con pedido', cooking:'En cocina', ready:'Listo', cerrada:'Cerrada' };
            return labels[m.status] || m.status;
        }
    };
}
</script>
@endpush
</x-portal-layout>
