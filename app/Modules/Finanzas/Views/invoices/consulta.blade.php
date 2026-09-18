{{-- CONSULTA de comprobantes emitidos.

     Separada de la emisión (2026-09-02): en la pantalla de Facturas convivían
     el formulario de emitir, el buscador del histórico y la descarga del
     Registro de Ventas. Son tres trabajos de personas distintas y se
     estorbaban entre sí. Aquí solo se busca y se consulta. --}}
<x-portal-layout :layout="$portalLayout ?? 'panel'" :project="$project" pageTitle="Comprobantes emitidos">
<x-slot name="slot">

@php
    /* "HAY FILTROS PUESTOS" no puede mirar `porFecha`: esa clave SIEMPRE trae
       valor ('emision' por defecto), asi que `array_filter($filtros)` daba
       true incluso con la pantalla recien abierta. Consecuencia: el boton
       Limpiar salia siempre —se pulsaba y no pasaba nada— y un negocio sin
       comprobantes leia "Ninguno coincide con esa busqueda" en vez de
       "Todavia no hay comprobantes". */
    $ce_hayFiltros = (bool) array_filter(\Illuminate\Support\Arr::except($filtros, ['porFecha']));

    $ce_tipos = [
        '' => 'Todos los tipos',
        'factura' => 'Facturas',
        'boleta' => 'Boletas',
        'nota_credito' => 'Notas de crédito',
        'nota_debito' => 'Notas de débito',
    ];
    $ce_estados = [
        '' => 'Cualquier estado',
        'accepted' => 'Aceptado por SUNAT',
        'pending' => 'Pendiente de envío',
        'rejected' => 'Rechazado por SUNAT',
        'error' => 'Con error',
        'sin_enviar' => 'Sin enviar',
        // El que cuenta el aviso del plazo: todo lo que sigue sin aceptar.
        'sin_aceptar' => 'Pendientes ante SUNAT',
        'anulado' => 'Anulado (baja)',
        'draft' => 'Borrador',
    ];
    $ce_badge = [
        'accepted' => ['Aceptado', '#065f46', '#d1fae5'],
        'pending'  => ['Pendiente', '#92400e', '#fef3c7'],
        'rejected' => ['Rechazado', '#991b1b', '#fee2e2'],
        'error'    => ['Error', '#991b1b', '#fee2e2'],
        'anulado'  => ['Anulado', '#374151', '#e5e7eb'],
        'draft'    => ['Borrador', '#1e40af', '#dbeafe'],
    ];
    /* El estado que ve el usuario no es solo sunat_status: un comprobante
       dado de baja o un borrador tienen prioridad sobre lo que dijo SUNAT. */
    $ce_estadoDe = function ($c) {
        if ($c->status === 'draft') return 'draft';
        if ($c->status === 'cancelled' || $c->baja_estado === 'accepted') return 'anulado';
        return $c->sunat_status;
    };
@endphp

@include('partials.consulta-estilos')

<div class="ce-wrap" x-data="{ verUrl: '', verNum: '' }">

    <div class="ce-head">
        <h1>Comprobantes emitidos</h1>
        <p>Busca cualquier comprobante ya emitido. Para emitir uno nuevo, entra a Facturas, Boletas o Notas.</p>
    </div>

    {{-- Sin esto el envio no daba senal ninguna: la pagina volvia igual y
         parecia que el boton no habia hecho nada. --}}
    @if(session('ok'))
    <div class="ce-aviso">{{ session('ok') }}</div>
    @endif

    <form method="GET" action="{{ route('bixosales.facturas.consulta') }}">
        <div class="ce-buscar">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
            </svg>
            <input type="search" name="q" value="{{ $filtros['q'] }}"
                   placeholder="Número, cliente o documento (RUC/DNI)…" aria-label="Buscar comprobante">
        </div>

        <div class="ce-filtros">
            <select name="tipo" aria-label="Tipo de comprobante">
                @foreach($ce_tipos as $valor => $texto)
                <option value="{{ $valor }}" @selected($filtros['tipo'] === $valor)>{{ $texto }}</option>
                @endforeach
            </select>

            <select name="estado" aria-label="Estado ante SUNAT">
                @foreach($ce_estados as $valor => $texto)
                <option value="{{ $valor }}" @selected($filtros['estado'] === $valor)>{{ $texto }}</option>
                @endforeach
            </select>

            {{-- Por que fecha se busca. La de emision es la fiscal (la que ve
                 SUNAT); la de creacion es el dia real en que se tecleo. Con
                 hasta 3 dias de atraso permitidos, no son la misma. --}}
            <select name="por_fecha" aria-label="Buscar por fecha de">
                <option value="emision" @selected(($filtros['porFecha'] ?? 'emision') === 'emision')>Fecha de emisión</option>
                <option value="creacion" @selected(($filtros['porFecha'] ?? '') === 'creacion')>Fecha de creación</option>
            </select>

            <input type="date" name="desde" value="{{ $filtros['desde'] }}" aria-label="Desde">
            <span class="ce-sep">a</span>
            <input type="date" name="hasta" value="{{ $filtros['hasta'] }}" aria-label="Hasta">

            <button type="submit" class="ce-btn">Buscar</button>
            @if($ce_hayFiltros)
            <a href="{{ route('bixosales.facturas.consulta') }}" class="ce-btn-ghost">Limpiar</a>
            @endif
        </div>

        {{-- Atajos de periodo: teclear dos fechas para ver "lo de hoy" es
             trabajo de mas, y el mes cerrado es lo que pide el contador. Se
             conservan el tipo, el estado y la fecha elegida. --}}
        @php
            $ce_hoy = now();
            $ce_periodos = [
                'Hoy'        => [$ce_hoy->copy()->toDateString(), $ce_hoy->copy()->toDateString()],
                'Ayer'       => [$ce_hoy->copy()->subDay()->toDateString(), $ce_hoy->copy()->subDay()->toDateString()],
                'Este mes'   => [$ce_hoy->copy()->startOfMonth()->toDateString(), $ce_hoy->copy()->endOfMonth()->toDateString()],
                'Mes pasado' => [$ce_hoy->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), $ce_hoy->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            ];
            $ce_base = ['tipo' => $filtros['tipo'], 'estado' => $filtros['estado'], 'q' => $filtros['q'], 'por_fecha' => $filtros['porFecha'] ?? 'emision'];
        @endphp
        <div class="ce-periodos">
            @foreach($ce_periodos as $ce_nombre => [$ce_d, $ce_h])
            <a class="ce-chip-periodo @if($filtros['desde'] === $ce_d && $filtros['hasta'] === $ce_h) es-activo @endif"
               href="{{ route('bixosales.facturas.consulta', $ce_base + ['desde' => $ce_d, 'hasta' => $ce_h]) }}">{{ $ce_nombre }}</a>
            @endforeach
        </div>
    </form>

    <div class="ce-tabla">
        <div class="ce-scroll">
        <table>
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Tipo</th>
                    <th>Emisión</th>
                    <th class="ce-col-creacion">Creación</th>
                    <th>Cliente</th>
                    <th>SUNAT</th>
                    <th style="text-align:right">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($comprobantes as $c)
                @php [$eTexto, $eColor, $eFondo] = $ce_badge[$ce_estadoDe($c)] ?? ['Sin enviar', '#4b5563', '#f3f4f6']; @endphp
                <tr>
                    <td class="ce-num" data-col="Número">{{ $c->numero }}</td>
                    <td data-col="Tipo">{{ $c->getTypeLabel() }}</td>
                    {{-- EMISION: la fecha fiscal, la que viaja en el XML. --}}
                    <td data-col="Emisión">{{ $c->issue_date?->format('d/m/Y') ?? '—' }}</td>
                    {{-- CREACION: cuando se tecleo de verdad. Si no coinciden se
                         marca, porque esa diferencia es la que explica por que
                         una factura "de ayer" aparece en el registro de hoy. --}}
                    <td data-col="Creación" class="ce-col-creacion">
                        @php $creada = $c->created_at; $desfase = $creada && $c->issue_date && $creada->toDateString() !== $c->issue_date->toDateString(); @endphp
                        <span @class(['ce-creada', 'ce-desfase' => $desfase])
                              @if($desfase) title="Se emitió con fecha {{ $c->issue_date->format('d/m/Y') }} pero se registró el {{ $creada->format('d/m/Y H:i') }}" @endif>
                            {{ $creada?->format('d/m/Y') ?? '—' }}
                        </span>
                    </td>
                    <td data-col="Cliente">{{ $c->client_name }}</td>
                    <td data-col="SUNAT"><span class="ce-chip" style="color:{{ $eColor }};background:{{ $eFondo }}">{{ $eTexto }}</span></td>
                    <td class="ce-monto" data-col="Total">{{ $c->currency ?? 'S/' }} {{ number_format((float) $c->total, 2) }}</td>
                    <td class="ce-acciones" data-col="">
                        {{-- Ver sin salir de la búsqueda: abrir cada comprobante
                             en otra pestaña obliga a volver atrás y perder el
                             filtro que costó escribir. --}}
                        <button type="button" class="ce-accion ce-accion-ghost"
                                @click="verUrl = '{{ route('bixosales.facturas.pdf', $c->id) }}'; verNum = '{{ $c->numero }}'"
                                title="Vista previa de {{ $c->numero }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <span>Ver</span>
                        </button>
                        @if($c->sunat_status === 'accepted')
                        <a class="ce-accion ce-accion-ghost" href="{{ route('bixosales.facturas.xml', $c->id) }}" title="XML firmado">XML</a>
                        @endif
                        {{-- Enviar a SUNAT sin salir de la consulta. Ahora
                             emitir ya declara solo, pero cuando el envio falla
                             (proveedor caido, corte de red) el comprobante se
                             ve aqui y aqui mismo se reintenta, en vez de
                             buscarlo en la pantalla de emision. Un aceptado o
                             un anulado no se reenvian; un borrador tampoco,
                             porque todavia no es un comprobante. --}}
                        @can('invoices.crear')
                        @if($c->sunat_status !== 'accepted' && $c->status !== 'draft'
                            && $c->status !== 'cancelled' && $c->baja_estado !== 'accepted')
                        <form method="POST" action="{{ route('bixosales.facturas.sunat', $c->id) }}"
                              class="ce-envio" x-data="{ enviando: false }"
                              @submit="enviando = true">
                            @csrf
                            <button type="submit" class="ce-accion ce-accion-sunat" :disabled="enviando"
                                    title="Enviar {{ $c->numero }} a SUNAT">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h12m0 0-4-4m4 4-4 4M18 4v16"/>
                                </svg>
                                <span x-text="enviando ? 'Enviando...' : '{{ $c->sunat_status ? 'Reintentar' : 'Enviar' }}'">{{ $c->sunat_status ? 'Reintentar' : 'Enviar' }}</span>
                            </button>
                        </form>
                        @endif
                        @endcan
                        {{-- Descarga DIRECTA: `descargar=1` devuelve el PDF como archivo,
                             sin abrir una pestaña que el usuario tenga que cerrar. Quien
                             baja doce comprobantes seguidos no quiere doce pestañas. --}}
                        <a class="ce-accion" href="{{ route('bixosales.facturas.pdf', $c->id) }}?descargar=1"
                           download title="Descargar el PDF de {{ $c->numero }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                            </svg>
                            <span>PDF</span>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="ce-vacio">
                    @if($ce_hayFiltros)
                        Ningún comprobante coincide con esa búsqueda.
                    @else
                        Todavía no hay comprobantes emitidos.
                    @endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="ce-pag">{{ $comprobantes->links() }}</div>

    {{-- Visor --}}
    <div x-show="verUrl" x-cloak class="ce-visor" @keydown.escape.window="verUrl = ''">
        <div class="ce-visor-fondo" @click="verUrl = ''"></div>
        <div class="ce-visor-caja">
            <div class="ce-visor-cab">
                <strong x-text="verNum"></strong>
                <span class="ce-visor-acc">
                    <a :href="verUrl + '?descargar=1'" download class="ce-accion">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                        </svg>
                        <span>PDF</span>
                    </a>
                    <button type="button" class="ce-visor-x" @click="verUrl = ''" aria-label="Cerrar vista previa">&times;</button>
                </span>
            </div>
            <iframe :src="verUrl + '?vista=incrustada'" title="Vista previa del comprobante"></iframe>
        </div>
    </div>
</div>

</x-slot>
</x-portal-layout>
