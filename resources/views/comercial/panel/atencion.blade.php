{{-- Centro operativo: lo que hay que hacer, ordenado por gravedad.
     Cada fila nace de una consulta. Si una categoria no tiene problema, no
     aparece: no hay filas de relleno ni luces verdes sin comprobar. La tarjeta
     termina donde termina su contenido — no se estira para igualar la columna
     de al lado, que es lo que dejaba media pantalla en blanco. --}}
@php
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
            'accion' => 'Revisar', 'url' => route('guias.index'),
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

<section class="self-start rounded-xl border border-slate-200 bg-white" aria-labelledby="tit-atencion">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <h2 id="tit-atencion" class="text-sm font-semibold text-slate-900">Requiere tu atención</h2>
        @if(count($avisos))
        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold tabular-nums text-slate-600">{{ count($avisos) }}</span>
        @endif
    </div>

    @forelse($avisos as $a)
    <div class="flex items-center gap-3 border-b border-slate-50 px-4 py-2.5 last:border-0">
        <span class="h-8 w-1 flex-shrink-0 rounded-full {{ $paleta[$a['nivel']][1] }}" aria-hidden="true"></span>
        <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg {{ $paleta[$a['nivel']][0] }}">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $a['icono'] }}"/>
            </svg>
        </span>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-medium leading-snug text-slate-900">{{ $a['titulo'] }}</p>
            <p class="text-xs leading-snug text-slate-500">{{ $a['detalle'] }}</p>
        </div>
        <a href="{{ $a['url'] }}"
           class="inline-flex h-8 flex-shrink-0 items-center gap-1 rounded-lg border border-slate-300 bg-white px-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            {{ $a['accion'] }}
            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
            </svg>
        </a>
    </div>
    @empty
    <div class="flex items-center gap-3 px-4 py-4">
        <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
            </svg>
        </span>
        <div>
            <p class="text-sm font-medium text-slate-900">Todo en orden</p>
            <p class="text-xs text-slate-500">Sin deuda vencida, pedidos en cola ni stock bajo mínimo.</p>
        </div>
    </div>
    @endforelse
</section>
