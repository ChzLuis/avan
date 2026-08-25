@php
    /* La nota de pedido sobre la familia visual de documentos. Hasta ahora
       este papel se fabricaba en el navegador con jsPDF: una captura de
       pantalla partida en páginas, borrosa al imprimir y con un diseño
       propio que no se parecía ni a la cotización ni al comprobante. Ahora
       es el mismo A4 de la familia: se abre y se imprime como los demás.

       Los importes de línea salen de LineMath (centavos enteros, redondeo
       half-up); el total es el que el pedido tiene guardado, que ya incluye
       envío y descuentos. */

    $numero = 'PED-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);
    $pagado = ($order->payment_status ?? 'pending') === 'paid';
    $anulado = $order->status === 'cancelled';

    [$badge, $tono] = match (true) {
        $anulado => ['Cancelado', 'gris'],
        $pagado  => ['Pagado', 'verde'],
        default  => ['Pago pendiente', 'ambar'],
    };

    $canal = match ($order->sales_channel) {
        'ecommerce', 'web' => 'Tienda virtual',
        'pos'              => 'POS / Mostrador',
        'whatsapp'         => 'WhatsApp',
        'cotizacion'       => 'Cotización',
        default            => $order->sales_channel ? ucfirst($order->sales_channel) : null,
    };
    $condiciones = array_filter([
        'Canal'          => $canal,
        'Método de pago' => $order->payment_method,
        'Condición'      => $order->payment_condition,
    ]);

    $envio = (float) ($order->shipping_cost ?? 0);

    // Sin la columna, una linea con descuento muestra 5 × 6.00 = 20.00 y el
    // cliente cree que hay un error de suma. Solo existe si alguna lo tiene.
    $hayDescuento = $order->items->contains(fn ($i) => (float) ($i->discount ?? 0) > 0);
@endphp
<x-doc.hoja :project="$project" :anulado="$anulado"
            :titulo="$numero.($order->client_name ? ' - '.$order->client_name : '')">
  <x-slot:pieEmisor>
    {{ $project->name }}@if($project->phone) &nbsp;·&nbsp; Tel: {{ $project->phone }}@endif
  </x-slot:pieEmisor>

  <x-doc.encabezado :project="$project"
                    :nombre="$project->name"
                    :detalle="trim(($project->address ? $project->address.\PHP_EOL : '').($project->phone ?: ''))">
    <x-doc.tarjeta :ruc="$project->setting('ruc')"
                   :numero="$numero"
                   :fecha="'Fecha: '.$order->created_at->format('d/m/Y H:i')"
                   :badge="$badge" :tono="$tono">
      NOTA DE PEDIDO
    </x-doc.tarjeta>
  </x-doc.encabezado>

  {{-- Cliente --}}
  <div class="secc">
    <div class="secc-etiqueta">Cliente</div>
    <div class="secc-titulo">{{ $order->client_name ?: 'Cliente mostrador' }}</div>
    <div class="secc-detalle">
      @if($order->client_phone)Tel: {{ $order->client_phone }}<br>@endif
      @if($order->delivery_address)Entrega: {{ $order->delivery_address }}@endif
    </div>
  </div>

  @if($condiciones !== [])
  <div class="datos">
    @foreach($condiciones as $etiqueta => $valor)
    <div>
      <div class="dato-etq">{{ $etiqueta }}</div>
      <div class="dato-val">{{ $valor }}</div>
    </div>
    @endforeach
  </div>
  @endif

  {{-- Lo pedido --}}
  <table class="items">
    <thead>
      <tr>
        <th style="width:{{ $hayDescuento ? '50%' : '58%' }}">Descripción</th>
        <th style="width:12%">Cant.</th>
        <th style="width:15%">Precio</th>
        @if($hayDescuento)<th style="width:8%">Dscto.</th>@endif
        <th style="width:15%">Importe</th>
      </tr>
    </thead>
    <tbody>
      @foreach($order->items as $item)
      <tr>
        <td class="desc">{{ $item->name }}</td>
        <td>{{ (int) $item->quantity }}</td>
        <td>{{ \App\Support\LineMath::present(\App\Support\LineMath::canon($item->price)) }}</td>
        @if($hayDescuento)<td>{{ (float) ($item->discount ?? 0) > 0 ? rtrim(rtrim(number_format((float) $item->discount, 2), '0'), '.').'%' : '—' }}</td>@endif
        <td class="total-linea">{{ \App\Support\LineMath::present(\App\Support\LineMath::total($item->price, (int) $item->quantity, $item->discount ?? 0)) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Cierre: notas + totales, como un bloque --}}
  <div class="bloque-cierre">
  <div class="cierre">
    <div class="cierre-izq">
      @if($order->notes)
      <div class="obs" style="margin-bottom:0;"><strong>Notas:</strong> {{ $order->notes }}</div>
      @endif
    </div>
    <div class="totales">
      @if($envio > 0)
      <div class="totales-fila">
        <span>Envío</span>
        <span>S/ {{ number_format($envio, 2) }}</span>
      </div>
      @endif
      <div class="total-final">
        <span class="etq">TOTAL</span>
        <span class="importe">S/ {{ number_format((float) $order->total, 2) }}</span>
      </div>
    </div>
  </div>

  <div class="validacion">
    <div>
      <div class="validacion-titulo">Sobre este documento</div>
      <div class="validacion-texto">
        Esta es una nota de pedido: registra lo acordado con el cliente y no constituye
        comprobante de pago. El comprobante fiscal se emite por separado.
      </div>
    </div>
  </div>
  </div>

</x-doc.hoja>
