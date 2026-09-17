@php
    /* La guía de remisión sobre la misma familia visual que el comprobante:
       misma hoja, mismo encabezado, misma tarjeta. Cambian los campos porque
       cambia el documento —aquí mandan el traslado, el transporte y el peso—
       pero quien reciba ambos papeles ve una sola identidad. */

    $anulado = $guia->status === 'cancelled';

    [$badge, $tono] = match (true) {
        $anulado                           => ['Anulada', 'gris'],
        $guia->sunat_status === 'accepted' => ['Aceptada SUNAT', 'verde'],
        $guia->sunat_status === 'pending'  => ['En SUNAT', 'ambar'],
        in_array($guia->sunat_status, ['rejected', 'error'], true) => ['Rechazada', 'rojo'],
        default                            => ['Emitida', 'gris'],
    };

    // El QR de la GRE apunta a la consulta del documento; sin CDR aún, lleva
    // los datos identificatorios, que es lo que revisa un control.
    $cdr = json_decode((string) $guia->sunat_cdr, true) ?: [];
    $qrDatos = $cdr['reference'] ?? implode('|', [
        $guia->emisor_ruc, '09', $guia->serie, $guia->correlativo,
        $guia->fecha_traslado?->format('Y-m-d'),
        $guia->destinatario_doc_tipo ?: '-', $guia->destinatario_doc_numero ?: '-',
    ]);

    // La misma normalizacion que viaja en el XML, sin repetir la expresion.
    $placa = $guia->placaNormalizada();
@endphp
<x-doc.hoja :incrustada="request()->query('vista') === 'incrustada'" :project="$project" :anulado="$anulado"
            :titulo="$guia->numero.' — Guía de remisión'">
  <x-slot:pieEmisor>
    {{ $guia->emisor_razon_social ?: $project->name }} &nbsp;·&nbsp; RUC {{ $guia->emisor_ruc }}
  </x-slot:pieEmisor>

  <x-doc.encabezado :project="$project"
                    :nombre="$guia->emisor_razon_social ?: $project->name"
                    :detalle="(string) $guia->partida_direccion">
    <x-doc.tarjeta :ruc="$guia->emisor_ruc"
                   :numero="$guia->numero"
                   :fecha="'Traslado: '.$guia->fecha_traslado?->format('d/m/Y')"
                   :badge="$badge" :tono="$tono">
      GUÍA DE REMISIÓN ELECTRÓNICA<br>REMITENTE
    </x-doc.tarjeta>
  </x-doc.encabezado>

  {{-- Destinatario --}}
  <div class="secc">
    <div class="secc-etiqueta">Destinatario</div>
    <div class="secc-titulo">{{ $guia->destinatario_nombre }}</div>
    <div class="secc-detalle">
      @if($guia->destinatario_doc_numero)
        {{ \App\Support\Sunat\Catalogos::DOCUMENTOS_IDENTIDAD[$guia->destinatario_doc_tipo] ?? 'Doc.' }}:
        {{ $guia->destinatario_doc_numero }}
      @endif
    </div>
  </div>

  {{-- El traslado en una sola línea de datos --}}
  <div class="datos">
    <div>
      <div class="dato-etq">Fecha de emisión</div>
      <div class="dato-val">{{ ($guia->created_at ?? now())->format('d/m/Y') }}</div>
    </div>
    <div>
      <div class="dato-etq">Motivo</div>
      <div class="dato-val">{{ $guia->motivoLegible() }}</div>
    </div>
    <div>
      <div class="dato-etq">Modalidad</div>
      <div class="dato-val">{{ $guia->esPublico() ? 'Transporte público' : 'Transporte privado' }}</div>
    </div>
    <div>
      <div class="dato-etq">Peso total</div>
      <div class="dato-val">{{ rtrim(rtrim(number_format((float) $guia->peso_total, 3, '.', ''), '0'), '.') }} {{ $guia->peso_unidad }}</div>
    </div>
    @if($guia->bultos)
    <div>
      <div class="dato-etq">Bultos</div>
      <div class="dato-val">{{ $guia->bultos }}</div>
    </div>
    @endif
    @if($guia->invoice)
    <div>
      <div class="dato-etq">Comprobante relacionado</div>
      <div class="dato-val">{{ $guia->invoice->getTypeLabel() }} {{ $guia->invoice->numero }}</div>
    </div>
    @endif
  </div>

  {{-- Ruta y transporte, en tarjetas gemelas --}}
  <div class="cierre" style="margin-bottom:20px;">
    <div style="flex:1;">
      <div class="secc" style="margin-bottom:14px;">
        <div class="secc-etiqueta">Punto de partida</div>
        <div class="secc-detalle" style="margin-top:0;">{{ $guia->partida_direccion }}@if($guia->partida_ubigeo) · Ubigeo {{ $guia->partida_ubigeo }}@endif</div>
      </div>
      <div class="secc" style="margin-bottom:0;">
        <div class="secc-etiqueta">Punto de llegada</div>
        <div class="secc-detalle" style="margin-top:0;">{{ $guia->llegada_direccion }}@if($guia->llegada_ubigeo) · Ubigeo {{ $guia->llegada_ubigeo }}@endif</div>
      </div>
    </div>
    <div style="flex:1;">
      <div class="secc" style="margin-bottom:0;">
        @if($guia->esPublico())
        <div class="secc-etiqueta">Transportista</div>
        <div class="secc-detalle" style="margin-top:0;">
          <strong style="color:#0F172A;">{{ $guia->transportista_razon_social }}</strong><br>
          RUC: {{ $guia->transportista_ruc }}
          @if($guia->transportista_mtc)<br>Registro MTC: {{ $guia->transportista_mtc }}@endif
        </div>
        @else
        <div class="secc-etiqueta">Vehículo y conductor</div>
        <div class="secc-detalle" style="margin-top:0;">
          <strong style="color:#0F172A;">Placa {{ $placa }}</strong><br>
          {{ trim($guia->conductor_nombres.' '.$guia->conductor_apellidos) }}<br>
          Doc. {{ $guia->conductor_doc_numero }} · Licencia {{ $guia->conductor_licencia }}
        </div>
        @endif
      </div>
    </div>
  </div>

  {{-- Bienes que se trasladan --}}
  <table class="items">
    <thead>
      <tr>
        <th style="width:16%; text-align:left;">Código</th>
        <th style="width:54%; text-align:left;">Descripción</th>
        <th style="width:14%">Unidad</th>
        <th style="width:16%">Cantidad</th>
      </tr>
    </thead>
    <tbody>
      @foreach($guia->items as $item)
      <tr>
        <td style="text-align:left;">{{ $item->codigo ?: '—' }}</td>
        <td class="desc">{{ $item->description }}</td>
        <td>{{ $item->unit }}</td>
        <td class="total-linea">{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="bloque-cierre">
  @if($guia->observaciones)
  <div class="obs"><strong>Observaciones:</strong> {{ $guia->observaciones }}</div>
  @endif

  {{-- Validación --}}
  <div class="validacion">
    {{-- QR local: ni el RUC ni el destino de la carga salen a un tercero,
         y la guia lleva su QR aunque el equipo este sin internet. --}}
    <div class="validacion-qr" data-qr="{{ $qrDatos }}" role="img" aria-label="QR de la guía"></div>
    <div>
      <div class="validacion-titulo">Validación del documento</div>
      <div class="validacion-texto">
        Representación impresa de la GUÍA DE REMISIÓN ELECTRÓNICA — REMITENTE.
        @if($guia->sunat_ticket)
        <div class="validacion-hash">Ticket SUNAT: {{ $guia->sunat_ticket }}</div>
        @endif
        {{ $guia->estadoSunatLegible() }}. Este documento sustenta el traslado de los bienes;
        no acredita la venta ni otorga crédito fiscal.
      </div>
    </div>
  </div>
  </div>

<script>{!! file_get_contents(public_path('js/qrcode.min.js')) !!}</script>
<script>
(function () {
    document.querySelectorAll('[data-qr]').forEach(function (el) {
        try {
            var qr = qrcode(0, 'M');
            qr.addData(el.getAttribute('data-qr'));
            qr.make();
            el.innerHTML = qr.createSvgTag({ cellSize: 3, margin: 0, scalable: true });
            var svg = el.querySelector('svg'); if (svg) { svg.setAttribute('width', '100%'); svg.setAttribute('height', '100%'); }
        } catch (e) { el.textContent = ''; }
    });
})();
</script>
</x-doc.hoja>
