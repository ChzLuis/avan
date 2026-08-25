@php
    /* La cotización sobre la misma familia visual que el comprobante
       (components/doc): quien recibe primero la cotización y después la
       factura ve una sola identidad. Cambia lo que manda aquí —vigencia,
       condición de pago y el aviso de que esto NO es un comprobante— pero
       la hoja, el encabezado y la tarjeta son los mismos.

       El importe de cada línea y el total salen de LineMath, la única
       aritmética de dinero del sistema: centavos enteros y redondeo
       half-up. Recalcular aquí "a mano" con floats es como se producen los
       descuadres de un céntimo entre pantalla, portal y papel. */

    $numero  = $quote->etiqueta;
    $estado  = \App\Support\QuoteStatus::clientePresentacion($quote->status);
    $vencida = \App\Support\QuoteStatus::vencida($quote->status, $quote->valid_until);
    $total   = \App\Support\LineMath::sum($quote->items);

    [$badge, $tono] = $vencida ? ['Vencida', 'rojo'] : [$estado['label'], match ($estado['label']) {
        'Aceptada', 'Convertida' => 'verde',
        'Pendiente'              => 'ambar',
        'Rechazada'              => 'rojo',
        default                  => 'gris',
    }];

    // Solo se muestran las condiciones que existen, como en el comprobante.
    $condiciones = array_filter([
        'Válida hasta'  => $quote->valid_until?->format('d/m/Y'),
        'Condición'     => $quote->payment_condition,
        'Forma de pago' => $quote->payment_method,
    ]);

    $hayDescuento = $quote->items->contains(fn ($i) => (float) ($i->discount ?? 0) > 0);
    $enlace = $quote->token ? url('/b/'.$project->slug.'/c/'.$quote->token) : null;
@endphp
<x-doc.hoja :project="$project"
            :titulo="$numero.($quote->client_name ? ' - '.$quote->client_name : '')">
  <x-slot:pieEmisor>
    {{ $project->name }}@if($project->phone) &nbsp;·&nbsp; Tel: {{ $project->phone }}@endif
  </x-slot:pieEmisor>

  <x-doc.encabezado :project="$project"
                    :nombre="$project->name"
                    :detalle="trim(($project->address ? $project->address.\PHP_EOL : '').($project->phone ?: ''))">
    <x-doc.tarjeta :ruc="$project->setting('ruc')"
                   :numero="$numero"
                   :fecha="'Emisión: '.$quote->created_at->format('d/m/Y')"
                   :badge="$badge" :tono="$tono">
      COTIZACIÓN
    </x-doc.tarjeta>
  </x-doc.encabezado>

  {{-- Cliente --}}
  <div class="secc">
    <div class="secc-etiqueta">Cliente</div>
    <div class="secc-titulo">{{ $quote->client_name ?: 'Cliente no registrado' }}</div>
    <div class="secc-detalle">
      @if($quote->client_doc_number){{ $quote->client_doc_type ?: 'Doc.' }}: {{ $quote->client_doc_number }}<br>@endif
      @if($quote->client_address){{ $quote->client_address }}<br>@endif
      @if($quote->client_phone)Tel: {{ $quote->client_phone }} @endif
      @if($quote->client_email){{ $quote->client_phone ? ' · ' : '' }}{{ $quote->client_email }}@endif
    </div>
  </div>

  @if($condiciones !== [])
  <div class="datos">
    @foreach($condiciones as $etiqueta => $valor)
    <div>
      <div class="dato-etq">{{ $etiqueta }}</div>
      <div class="dato-val">{{ $valor }}{{ $etiqueta === 'Válida hasta' && $vencida ? ' (vencida)' : '' }}</div>
    </div>
    @endforeach
  </div>
  @endif

  {{-- Bienes y servicios cotizados --}}
  <table class="items">
    <thead>
      <tr>
        <th style="width:{{ $hayDescuento ? '52%' : '60%' }}">Descripción</th>
        <th style="width:10%">Cant.</th>
        <th style="width:15%">P. Unit.</th>
        @if($hayDescuento)<th style="width:8%">Dscto.</th>@endif
        <th style="width:15%">Importe</th>
      </tr>
    </thead>
    <tbody>
      @foreach($quote->items as $item)
      <tr>
        <td class="desc">{{ $item->description }}</td>
        <td>{{ (int) $item->quantity }}</td>
        <td>{{ \App\Support\LineMath::present(\App\Support\LineMath::canon($item->price)) }}</td>
        @if($hayDescuento)<td>{{ (float) ($item->discount ?? 0) > 0 ? rtrim(rtrim(number_format((float) $item->discount, 2), '0'), '.').'%' : '—' }}</td>@endif
        <td class="total-linea">{{ \App\Support\LineMath::present(\App\Support\LineMath::total($item->price, (int) $item->quantity, $item->discount ?? 0)) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Cierre: observaciones + total, como un bloque --}}
  <div class="bloque-cierre">
  <div class="cierre">
    <div class="cierre-izq">
      @if($quote->notes)
      <div class="obs" style="margin-bottom:0;"><strong>Observaciones:</strong> {{ $quote->notes }}</div>
      @endif
    </div>
    <div class="totales">
      {{-- Cotizaciones no guarda desglose de impuestos: mostrar solo el total
           pactado evita que el cliente lea un IGV que nunca se calculó. --}}
      <div class="total-final">
        <span class="etq">TOTAL</span>
        <span class="importe">S/ {{ \App\Support\LineMath::present($total) }}</span>
      </div>
    </div>
  </div>

  {{-- Validación: el QR lleva al enlace vivo de la cotización --}}
  <div class="validacion">
    @if($enlace)
    <img class="validacion-qr" alt="QR de la cotización"
         src="https://api.qrserver.com/v1/create-qr-code/?size=192x192&ecc=M&data={{ urlencode($enlace) }}">
    @endif
    <div>
      <div class="validacion-titulo">Sobre este documento</div>
      <div class="validacion-texto">
        Esta es una cotización comercial y no constituye comprobante de pago.
        Los importes mostrados son los pactados; el comprobante fiscal se emite al concretarse la venta.
        @if($enlace)
        <div class="validacion-hash">Consulta en línea: {{ $enlace }}</div>
        @endif
      </div>
    </div>
  </div>
  </div>

</x-doc.hoja>
