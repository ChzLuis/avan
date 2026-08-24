@php
    /* La representación impresa de factura, boleta y notas, montada sobre la
       familia visual de documentos (components/doc). El diseño trabaja
       alrededor de lo fiscal, no en su lugar: denominación oficial, QR
       normado, importe en letras, hash y —en una nota— el documento afectado
       siguen siendo el esqueleto del papel. */

    $denominacion = match ($invoice->type) {
        'factura'      => 'FACTURA ELECTRÓNICA',
        'boleta'       => 'BOLETA DE VENTA ELECTRÓNICA',
        'nota_credito' => 'NOTA DE CRÉDITO ELECTRÓNICA',
        'nota_debito'  => 'NOTA DE DÉBITO ELECTRÓNICA',
        default        => strtoupper($invoice->getTypeLabel()),
    };

    $anulado = $invoice->status === 'cancelled' || $invoice->baja_estado === 'accepted';

    // El estado real del documento, con su color. Verde solo para lo positivo.
    [$badge, $tono] = match (true) {
        $anulado                              => ['Anulada', 'gris'],
        $invoice->sunat_status === 'accepted' => ['Aceptada SUNAT', 'verde'],
        $invoice->sunat_status === 'pending'  => ['En SUNAT', 'ambar'],
        in_array($invoice->sunat_status, ['rejected', 'error'], true) => ['Rechazada', 'rojo'],
        default                               => ['Emitida', 'gris'],
    };

    // El QR normado: RUC|tipo|serie|correlativo|IGV|total|fecha|tipoDocCli|numDocCli|hash
    $qrDatos = implode('|', [
        $invoice->emisor_ruc,
        $invoice->codigoSunat(),
        $invoice->serie,
        $invoice->correlativo,
        number_format((float) $invoice->igv, 2, '.', ''),
        number_format((float) $invoice->total, 2, '.', ''),
        $invoice->issue_date?->format('Y-m-d'),
        \App\Support\Sunat\Catalogos::codigoDocumentoIdentidad($invoice->client_doc_type, $invoice->client_doc_number),
        $invoice->client_doc_number ?: '-',
        $invoice->sunat_hash ?: '',
    ]);

    $enLetras = \App\Support\Sunat\MontoEnLetras::de((float) $invoice->total, $invoice->currency);

    // Solo se muestran los datos complementarios que existen.
    $complementarios = array_filter([
        'Moneda'         => $invoice->currency !== 'PEN' ? $invoice->currency : null,
        'Método de pago' => $invoice->payment_method,
        'Vencimiento'    => $invoice->due_date?->format('d/m/Y'),
    ]);
@endphp
<x-doc.hoja :project="$project" :anulado="$anulado"
            :titulo="$invoice->numero.' — '.$project->name">
  <x-slot:pieEmisor>
    {{ $invoice->emisor_razon_social ?: $project->name }} &nbsp;·&nbsp; RUC {{ $invoice->emisor_ruc }}
  </x-slot:pieEmisor>

  <x-doc.encabezado :project="$project"
                    :nombre="$invoice->emisor_razon_social ?: $project->name"
                    :detalle="(string) $invoice->emisor_direccion">
    <x-doc.tarjeta :ruc="$invoice->emisor_ruc"
                   :numero="$invoice->numero"
                   :fecha="$invoice->issue_date ? 'Emisión: '.$invoice->issue_date->format('d/m/Y') : null"
                   :badge="$badge" :tono="$tono">
      {{ $denominacion }}
    </x-doc.tarjeta>
  </x-doc.encabezado>

  {{-- Receptor --}}
  <div class="secc">
    <div class="secc-etiqueta">{{ $invoice->type === 'boleta' ? 'Cliente' : 'Facturado a' }}</div>
    <div class="secc-titulo">{{ $invoice->client_name }}</div>
    <div class="secc-detalle">
      @if($invoice->client_doc_number){{ $invoice->client_doc_type ?: 'Doc.' }}: {{ $invoice->client_doc_number }}<br>@endif
      @if($invoice->client_address){{ $invoice->client_address }}<br>@endif
      @if($invoice->client_phone)Tel: {{ $invoice->client_phone }} @endif
      @if($invoice->client_email){{ $invoice->client_phone ? ' · ' : '' }}{{ $invoice->client_email }}@endif
    </div>
  </div>

  @if($complementarios !== [])
  <div class="datos">
    @foreach($complementarios as $etiqueta => $valor)
    <div>
      <div class="dato-etq">{{ $etiqueta }}</div>
      <div class="dato-val">{{ $valor }}</div>
    </div>
    @endforeach
  </div>
  @endif

  @if($invoice->esNota())
  {{-- Una nota sin su documento afectado no dice nada: es lo primero que
       mira quien la recibe y lo que exige el formato. --}}
  <div class="nota-ref">
    <div>
      <div class="dato-etq">Documento que modifica</div>
      <div class="dato-val">{{ $invoice->afecta_tipo === '03' ? 'Boleta' : 'Factura' }} {{ $invoice->afecta_numero }}</div>
    </div>
    <div>
      <div class="dato-etq">Motivo ({{ $invoice->motivo_codigo }})</div>
      <div class="dato-val">{{ $invoice->motivo_descripcion }}</div>
    </div>
  </div>
  @endif

  {{-- Bienes y servicios --}}
  <table class="items">
    <thead>
      <tr>
        <th style="width:44%">Descripción</th>
        <th style="width:10%">Unid.</th>
        <th style="width:9%">Cant.</th>
        <th style="width:13%">P. Unit.</th>
        <th style="width:11%">IGV</th>
        <th style="width:13%">Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($invoice->items as $item)
      <tr>
        <td class="desc">{{ $item->description }}</td>
        <td>{{ $item->unit }}</td>
        <td>{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</td>
        <td>{{ number_format($item->unit_price, 2) }}</td>
        <td>{{ number_format($item->igv_amount, 2) }}</td>
        <td class="total-linea">{{ number_format($item->total, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Cierre: condiciones + totales --}}
  <div class="cierre">
    <div class="cierre-izq">
      @if($invoice->notes)
      <div class="obs" style="margin-bottom:0;"><strong>Observaciones:</strong> {{ $invoice->notes }}</div>
      @endif
    </div>
    <div class="totales">
      <div class="totales-fila">
        <span>Subtotal (sin IGV)</span>
        <span>{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</span>
      </div>
      <div class="totales-fila">
        <span>IGV 18%</span>
        <span>{{ $invoice->currency }} {{ number_format($invoice->igv, 2) }}</span>
      </div>
      <div class="total-final">
        <span class="etq">TOTAL</span>
        <span class="importe">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</span>
      </div>
    </div>
  </div>

  <div class="en-letras">{{ $enLetras }}</div>

  {{-- Validación: el QR normado, el hash y el estado ante SUNAT --}}
  <div class="validacion">
    <img class="validacion-qr" alt="QR del comprobante"
         src="https://api.qrserver.com/v1/create-qr-code/?size=192x192&ecc=M&data={{ urlencode($qrDatos) }}">
    <div>
      <div class="validacion-titulo">Validación del comprobante</div>
      <div class="validacion-texto">
        Representación impresa de la {{ $denominacion }}.
        @if($invoice->sunat_hash)
        <div class="validacion-hash">Hash: {{ $invoice->sunat_hash }}</div>
        @endif
        @if($invoice->sunat_status === 'accepted')Aceptada por SUNAT.@else Pendiente de aceptación por SUNAT.@endif
        Consulte el documento en el portal de SUNAT o del emisor.
      </div>
    </div>
  </div>

</x-doc.hoja>
