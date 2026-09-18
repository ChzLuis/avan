{{-- HISTORICO de guias de remision.

     Separado de la emision (2026-09-11), igual que "Comprobantes emitidos":
     en Guias conviven el formulario de emitir y una lista corta; aqui solo se
     busca y se consulta lo ya emitido, con los mismos filtros y el mismo
     aspecto para que las dos pantallas se sientan una sola. --}}
<x-portal-layout :layout="$portalLayout ?? 'panel'" :project="$project" pageTitle="Histórico de guías">
<x-slot name="slot">

@php
    $rp = ($portalLayout ?? 'panel') === 'comercial' ? 'bixosales.' : '';
    $ce_estados = [
        '' => 'Cualquier estado',
        'accepted' => 'Aceptada por SUNAT',
        'pending' => 'Pendiente de envío',
        'rejected' => 'Rechazada por SUNAT',
        'error' => 'Con error',
        'sin_enviar' => 'Sin enviar',
    ];
    $ce_badge = [
        'accepted' => ['Aceptada', '#065f46', '#d1fae5'],
        'pending'  => ['Pendiente', '#92400e', '#fef3c7'],
        'rejected' => ['Rechazada', '#991b1b', '#fee2e2'],
        'error'    => ['Error', '#991b1b', '#fee2e2'],
    ];
    $modalidades = ['01' => 'Transporte público', '02' => 'Transporte privado'];
@endphp

@include('partials.consulta-estilos')

<div class="ce-wrap" x-data="{ verUrl: '', verNum: '' }">

    <div class="ce-head">
        <h1>Histórico de guías de remisión</h1>
        <p>Busca cualquier guía ya emitida. Para emitir una nueva, entra a <a href="{{ route($rp.'guias.index') }}" style="color:#4f46e5;font-weight:600">Guías de remisión</a>.</p>
    </div>

    @if(session('ok'))
    <div class="ce-aviso">{{ session('ok') }}</div>
    @endif

    <form method="GET" action="{{ route($rp.'guias.consulta') }}">
        <div class="ce-buscar">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
            </svg>
            <input type="search" name="q" value="{{ $filtros['q'] }}"
                   placeholder="Número, destinatario, RUC/DNI o placa…" aria-label="Buscar guía">
        </div>

        <div class="ce-filtros">
            <select name="estado" aria-label="Estado ante SUNAT">
                @foreach($ce_estados as $valor => $texto)
                <option value="{{ $valor }}" @selected($filtros['estado'] === $valor)>{{ $texto }}</option>
                @endforeach
            </select>

            <input type="date" name="desde" value="{{ $filtros['desde'] }}" aria-label="Desde">
            <span class="ce-sep">a</span>
            <input type="date" name="hasta" value="{{ $filtros['hasta'] }}" aria-label="Hasta">

            <button type="submit" class="ce-btn">Buscar</button>
            @if(array_filter($filtros))
            <a href="{{ route($rp.'guias.consulta') }}" class="ce-btn-ghost">Limpiar</a>
            @endif
        </div>
    </form>

    <div class="ce-tabla">
        <div class="ce-scroll">
        <table>
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Traslado</th>
                    <th>Destinatario</th>
                    <th>Motivo</th>
                    <th>Cómo viaja</th>
                    <th style="text-align:right">Peso</th>
                    <th>Comprobante</th>
                    <th>SUNAT</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($guias as $g)
                @php
                    [$eTexto, $eColor, $eFondo] = $ce_badge[$g->sunat_status] ?? ['Sin enviar', '#4b5563', '#f3f4f6'];
                    $fecha = $g->fecha_traslado ?? $g->created_at;
                    $viaja = ($modalidades[$g->modalidad] ?? '—')
                        .($g->vehiculo_placa ? ' · '.$g->vehiculo_placa : ($g->vehiculo_m1l ? ' · M1/L' : ''));
                @endphp
                <tr>
                    <td class="ce-num" data-col="Número">{{ $g->numero }}</td>
                    <td data-col="Traslado">{{ $fecha?->format('d/m/Y') ?? '—' }}</td>
                    <td data-col="Destinatario">
                        {{ $g->destinatario_nombre }}
                        @if($g->destinatario_doc_numero)<div style="font-size:11px;color:#9ca3af">{{ $g->destinatario_doc_numero }}</div>@endif
                    </td>
                    <td data-col="Motivo">{{ $motivos[$g->motivo_codigo] ?? $g->motivo_codigo }}</td>
                    <td data-col="Cómo viaja">{{ $viaja }}</td>
                    <td class="ce-monto" data-col="Peso">{{ rtrim(rtrim(number_format((float) $g->peso_total, 2, '.', ''), '0'), '.') }} {{ $g->peso_unidad ?: 'KGM' }}</td>
                    <td data-col="Comprobante">
                        @if($g->invoice)
                            <span class="ce-num">{{ $g->invoice->numero }}</span>
                        @else
                            <span style="color:#9ca3af">Sin comprobante</span>
                        @endif
                    </td>
                    <td data-col="SUNAT"><span class="ce-chip" style="color:{{ $eColor }};background:{{ $eFondo }}">{{ $eTexto }}</span></td>
                    <td class="ce-acciones" data-col="">
                        <button type="button" class="ce-accion ce-accion-ghost"
                                @click="verUrl = '{{ route($rp.'guias.pdf', $g->id) }}'; verNum = '{{ $g->numero }}'"
                                title="Ver {{ $g->numero }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <span>Ver</span>
                        </button>
                        @can('invoices.crear')
                        @if($g->sunat_status !== 'accepted')
                        <form method="POST" action="{{ route($rp.'guias.enviar', $g->id) }}"
                              class="ce-envio" x-data="{ enviando: false }" @submit="enviando = true">
                            @csrf
                            <button type="submit" class="ce-accion ce-accion-sunat" :disabled="enviando"
                                    title="Enviar {{ $g->numero }} a SUNAT">
                                <span x-text="enviando ? 'Enviando...' : '{{ $g->sunat_status ? 'Reintentar' : 'Enviar' }}'">{{ $g->sunat_status ? 'Reintentar' : 'Enviar' }}</span>
                            </button>
                        </form>
                        @endif
                        @endcan
                        {{-- Descarga DIRECTA: para adjuntar la guia a un correo
                             sin pasar por "imprimir a PDF" del navegador. --}}
                        <a class="ce-accion ce-accion-ghost" href="{{ route($rp.'guias.pdf', $g->id) }}?descargar=1"
                           download title="Descargar {{ $g->numero }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                            </svg>
                            <span>Descargar</span>
                        </a>
                        <a class="ce-accion" href="{{ route($rp.'guias.pdf', $g->id) }}" target="_blank" rel="noopener"
                           title="Imprimir {{ $g->numero }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6z"/>
                            </svg>
                            <span>Imprimir</span>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="ce-vacio">
                    @if(array_filter($filtros))
                        Ninguna guía coincide con esa búsqueda.
                    @else
                        Todavía no hay guías emitidas.
                    @endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="ce-pag">{{ $guias->links() }}</div>

    {{-- Visor: se mira la guía sin perder la búsqueda de detrás. --}}
    <div x-show="verUrl" x-cloak class="ce-visor" @keydown.escape.window="verUrl = ''">
        <div class="ce-visor-fondo" @click="verUrl = ''"></div>
        <div class="ce-visor-caja">
            <div class="ce-visor-cab">
                <strong x-text="verNum"></strong>
                <span class="ce-visor-acc">
                    <a :href="verUrl" target="_blank" rel="noopener" class="ce-accion"><span>Imprimir</span></a>
                    <button type="button" class="ce-visor-x" @click="verUrl = ''" aria-label="Cerrar">&times;</button>
                </span>
            </div>
            <iframe :src="verUrl || 'about:blank'" title="Vista de la guía"></iframe>
        </div>
    </div>
</div>

</x-slot>
</x-portal-layout>
