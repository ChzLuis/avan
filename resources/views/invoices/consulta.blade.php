{{-- CONSULTA de comprobantes emitidos.

     Separada de la emisión (2026-09-02): en la pantalla de Facturas convivían
     el formulario de emitir, el buscador del histórico y la descarga del
     Registro de Ventas. Son tres trabajos de personas distintas y se
     estorbaban entre sí. Aquí solo se busca y se consulta. --}}
<x-portal-layout :layout="$portalLayout ?? 'panel'" :project="$project" pageTitle="Comprobantes emitidos">
<x-slot name="slot">

@php
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
        'pending' => 'Pendiente',
        'error' => 'Con error',
    ];
    $ce_badge = [
        'accepted' => ['Aceptado', '#065f46', '#d1fae5'],
        'pending'  => ['Pendiente', '#92400e', '#fef3c7'],
        'error'    => ['Error', '#991b1b', '#fee2e2'],
    ];
@endphp

<style>
    .ce-wrap{padding:20px;max-width:1200px;margin:0 auto;width:100%}
    .ce-head{margin-bottom:16px}
    .ce-head h1{margin:0;font-size:20px;font-weight:700;color:#111827;letter-spacing:-.01em}
    .ce-head p{margin:4px 0 0;font-size:13px;color:#6b7280}
    /* El buscador manda: es lo único a lo que se viene aquí. */
    .ce-buscar{position:relative;margin-bottom:10px}
    .ce-buscar svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);width:17px;height:17px;color:#9ca3af;pointer-events:none}
    .ce-buscar input{width:100%;padding:11px 14px 11px 40px;font-size:14px;color:#111827;background:#fff;border:1px solid #e5e7eb;border-radius:10px}
    .ce-buscar input:focus{outline:2px solid #6366f1;outline-offset:-1px;border-color:transparent}
    .ce-filtros{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:16px}
    .ce-filtros select,.ce-filtros input[type=date]{padding:8px 11px;font-size:13px;color:#374151;background:#fff;border:1px solid #e5e7eb;border-radius:8px}
    .ce-filtros .ce-sep{font-size:12px;color:#9ca3af}
    .ce-btn{padding:8px 16px;font-size:13px;font-weight:600;color:#fff;background:#4f46e5;border:0;border-radius:8px;cursor:pointer}
    .ce-btn:hover{background:#4338ca}
    .ce-btn-ghost{padding:8px 14px;font-size:13px;font-weight:600;color:#4b5563;background:#fff;border:1px solid #e5e7eb;border-radius:8px;text-decoration:none}
    .ce-tabla{width:100%;background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden}
    .ce-scroll{overflow-x:auto}
    .ce-tabla table{width:100%;border-collapse:collapse;font-size:13px}
    .ce-tabla th{padding:10px 14px;text-align:left;font-weight:600;color:#6b7280;background:#f9fafb;border-bottom:1px solid #e5e7eb;white-space:nowrap}
    .ce-tabla td{padding:11px 14px;color:#374151;border-bottom:1px solid #f3f4f6}
    .ce-tabla tr:last-child td{border-bottom:0}
    .ce-tabla tr:hover td{background:#f9fafb}
    .ce-num{font-weight:600;color:#111827;white-space:nowrap}
    .ce-monto{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
    .ce-chip{display:inline-block;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:600;white-space:nowrap}
    .ce-vacio{padding:44px 20px;text-align:center;color:#9ca3af;font-size:14px}
    .ce-pag{margin-top:14px}

    /* ── MÓVIL ────────────────────────────────────────────────────────────
       Una tabla de 6 columnas en 390 px no se lee ni con scroll lateral: se
       pierde la referencia de qué columna es cada dato. Cada comprobante
       pasa a ser una tarjeta con sus datos etiquetados, que es como se
       consulta de pie en el mostrador. */
    @media (max-width: 720px) {
        .ce-wrap{padding:14px}
        .ce-head h1{font-size:18px}

        /* Filtros: cada uno a su ancho, sin apretarse en una línea. */
        .ce-filtros{gap:8px}
        .ce-filtros select{flex:1 1 100%}
        .ce-filtros input[type=date]{flex:1 1 calc(50% - 16px)}
        .ce-filtros .ce-sep{display:none}
        .ce-filtros .ce-btn,.ce-filtros .ce-btn-ghost{flex:1 1 auto;text-align:center;min-height:44px;line-height:26px}

        /* Campos a 16px: por debajo, iOS hace zoom al tocarlos y descuadra. */
        .ce-buscar input,.ce-filtros select,.ce-filtros input[type=date]{font-size:16px;min-height:46px}

        .ce-tabla{border:0;background:transparent;border-radius:0}
        .ce-scroll{overflow:visible}
        .ce-tabla thead{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
        .ce-tabla tr{display:block;margin-bottom:10px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px}
        .ce-tabla td{display:flex;justify-content:space-between;gap:12px;align-items:baseline;padding:4px 0;border:0}
        .ce-tabla tr:hover td{background:transparent}
        /* La etiqueta la pone el CSS: sin cabecera, el dato solo no dice nada. */
        .ce-tabla td::before{content:attr(data-col);flex:0 0 auto;font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.03em}
        .ce-tabla td.ce-num{padding-bottom:8px;margin-bottom:4px;border-bottom:1px solid #f3f4f6;font-size:15px}
        .ce-tabla td.ce-monto{text-align:right;font-size:15px;font-weight:700;color:#111827}
        .ce-tabla td.ce-vacio{display:block;text-align:center}
        .ce-tabla td.ce-vacio::before{content:''}
    }
</style>

<div class="ce-wrap">

    <div class="ce-head">
        <h1>Comprobantes emitidos</h1>
        <p>Busca cualquier comprobante ya emitido. Para emitir uno nuevo, entra a Facturas, Boletas o Notas.</p>
    </div>

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

            <input type="date" name="desde" value="{{ $filtros['desde'] }}" aria-label="Desde">
            <span class="ce-sep">a</span>
            <input type="date" name="hasta" value="{{ $filtros['hasta'] }}" aria-label="Hasta">

            <button type="submit" class="ce-btn">Buscar</button>
            @if(array_filter($filtros))
            <a href="{{ route('bixosales.facturas.consulta') }}" class="ce-btn-ghost">Limpiar</a>
            @endif
        </div>
    </form>

    <div class="ce-tabla">
        <div class="ce-scroll">
        <table>
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>SUNAT</th>
                    <th style="text-align:right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($comprobantes as $c)
                @php [$eTexto, $eColor, $eFondo] = $ce_badge[$c->sunat_status] ?? ['Sin enviar', '#4b5563', '#f3f4f6']; @endphp
                <tr>
                    <td class="ce-num" data-col="Número">{{ $c->numero }}</td>
                    <td data-col="Tipo">{{ $c->getTypeLabel() }}</td>
                    <td data-col="Fecha">{{ $c->issue_date?->format('d/m/Y') ?? '—' }}</td>
                    <td data-col="Cliente">{{ $c->client_name }}</td>
                    <td data-col="SUNAT"><span class="ce-chip" style="color:{{ $eColor }};background:{{ $eFondo }}">{{ $eTexto }}</span></td>
                    <td class="ce-monto" data-col="Total">{{ $c->currency ?? 'S/' }} {{ number_format((float) $c->total, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="ce-vacio">
                    @if(array_filter($filtros))
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
</div>

</x-slot>
</x-portal-layout>
