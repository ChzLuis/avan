@php
    /* Representación impresa CLÁSICA: la de toda la vida en el comercio
       peruano — todo encuadrado, cabecera a tres bloques y el recuadro de
       totales con las seis casillas de la operación.

       Convive con la moderna: el negocio elige con el ajuste
       `invoice_template` (clasico | moderno). Lo fiscal es idéntico en las
       dos —denominación, QR normado, importe en letras, hash y, en una nota,
       el documento afectado—; lo que cambia es la forma del papel. */

    $denominacion = match ($invoice->type) {
        'factura'      => 'FACTURA ELECTRÓNICA',
        'boleta'       => 'BOLETA DE VENTA ELECTRÓNICA',
        'nota_credito' => 'NOTA DE CRÉDITO ELECTRÓNICA',
        'nota_debito'  => 'NOTA DE DÉBITO ELECTRÓNICA',
        default        => strtoupper($invoice->getTypeLabel()),
    };

    $anulado = $invoice->status === 'cancelled' || $invoice->baja_estado === 'accepted';

    // QR normado: RUC|tipo|serie|correlativo|IGV|total|fecha|tipoDocCli|numDocCli|hash
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
    $moneda = $invoice->currency === 'USD' ? 'Dólar Americano' : ($invoice->currency === 'EUR' ? 'Euro' : 'Soles');
    $simbolo = $invoice->currency === 'USD' ? '$' : ($invoice->currency === 'EUR' ? '€' : 'S/');

    // El total de descuento sale de las líneas: el formato clásico lo declara
    // aparte del importe bruto, no mezclado en el precio.
    $descuentoDe = fn ($i) => ((float) $i->quantity * (float) $i->unit_price) * ((float) ($i->discount ?? 0) / 100);
    $totalDescuento = $invoice->items->sum($descuentoDe);
    $totalImporte = $invoice->items->sum(fn ($i) => (float) $i->quantity * (float) $i->unit_price);

    $cuentas = trim((string) $project->setting('cuentas_bancarias'));
    $notaPie = trim((string) $project->setting('invoice_nota_pie'));

    $num = fn ($v, $d = 2) => number_format((float) $v, $d, '.', ',');

    // Las dos columnas de descuento solo se imprimen si alguna linea lo lleva:
    // en una factura sin descuentos son dos columnas vacias que estrechan la
    // descripcion. Cuando SI hay descuento no se pueden ocultar, porque
    // entonces "Importe" y "Valor Venta" no cuadrarian a la vista.
    $hayDescuento = $invoice->items->contains(fn ($i) => (float) ($i->discount ?? 0) > 0);

    // Misma resolucion que `doc/encabezado`, con nombre propio: ese componente
    // tambien define `$logo` y el ambito se pisaba.
    /* Pie comercial: acreditación y marcas que distribuye el negocio.
       Configurable, NUNCA incrustado: cada negocio pone su texto y sus logos
       desde los ajustes (`invoice_pie_texto`, `invoice_marca_1..6`) sin que
       nadie toque la plantilla. Si no hay nada configurado, el bloque no
       existe y la factura sale igual que ahora. */
    $urlImagen = static function (?string $ruta) {
        if (blank($ruta)) return null;
        return str_starts_with($ruta, 'http') ? $ruta : asset('storage/'.ltrim($ruta, '/'));
    };
    $pieTexto = trim((string) $project->setting('invoice_pie_texto', ''));
    $pieMarcas = [];
    foreach (range(1, 6) as $n) {
        if ($u = $urlImagen($project->setting("invoice_marca_{$n}"))) {
            $pieMarcas[] = $u;
        }
    }
    $logoEmisor = $project->setting('logo_url') ?: ($project->logo_url ?? null);
    $logoEmisor = $logoEmisor
        ? (str_starts_with($logoEmisor, 'http') ? $logoEmisor : asset('storage/'.ltrim($logoEmisor, '/')))
        : null;
@endphp
<x-doc.hoja :project="$project" :anulado="$anulado"
            :titulo="$invoice->numero.' — '.$project->name">
  <x-slot:pieEmisor>
    {{ $invoice->emisor_razon_social ?: $project->name }} &nbsp;·&nbsp; RUC {{ $invoice->emisor_ruc }}
  </x-slot:pieEmisor>

<style>
  /* Hoja clásica: rejilla de 0.5pt en todo. Se usa `!important` solo donde la
     familia visual moderna ya fijaba un estilo para el mismo elemento. */
  .cl { font-size: 8.2pt; color: #000; font-variant-numeric: tabular-nums; }
  .cl .marco { border: 1px solid #000; }
  .cl table { width: 100%; border-collapse: collapse; }
  .cl th, .cl td { border: 1px solid #000; padding: 1.2mm 1.6mm; vertical-align: top; }
  .cl th { font-weight: 700; text-align: center; background: #fff; }
  .cl .der { text-align: right; }
  .cl .cen { text-align: center; }

  /* Cabecera a tres bloques: logo · emisor · recuadro fiscal. */
  .cl-cab { display: flex; align-items: flex-start; gap: 4mm; margin-bottom: 2mm; }
  .cl-logo { width: 42mm; flex: 0 0 42mm; }
  .cl-logo img { max-width: 100%; max-height: 22mm; }
  .cl-emisor { flex: 1; text-align: center; line-height: 1.45; padding-top: 1mm; }
  .cl-emisor .razon { font-weight: 700; font-size: 9.5pt; }
  .cl-fiscal { width: 58mm; flex: 0 0 58mm; border: 1.4px solid #000; padding: 3mm 2mm; text-align: center; line-height: 1.6; }
  .cl-fiscal .ruc { font-weight: 700; font-size: 11pt; }
  .cl-fiscal .den { font-weight: 700; font-size: 10pt; margin: 1.5mm 0; }
  .cl-fiscal .nro { font-weight: 700; font-size: 10pt; }

  /* Datos del receptor: etiqueta a la izquierda, valor a la derecha. */
  .cl-receptor { margin-bottom: 0; }
  .cl-receptor td { border: none; padding: 0.35mm 0; vertical-align: top; }
  .cl-receptor .etq { width: 18mm; font-weight: 700; white-space: nowrap; }
  .cl-caja { border: 1px solid #000; padding: 1.4mm 2.5mm; margin-bottom: 1.5mm; }

  .cl-items thead th { font-size: 7.2pt; line-height: 1.12; padding: 1mm 0.8mm; }
  .cl-items td { font-size: 7.9pt; padding: 0.9mm 0.8mm; }
  .cl-items .desc { text-align: left; }

  .cl-resumen { border: 1px solid #000; border-top: none; padding: 1.2mm 1.6mm; font-size: 7.6pt; }
  .cl-avisos { margin: 1.5mm 0; font-size: 7.8pt; line-height: 1.45; }
  .cl-letras { font-weight: 700; margin: 1.5mm 0 1.5mm; font-size: 8.4pt; }
  .cl-totales th, .cl-totales td { text-align: center; font-size: 7.7pt; }
  .cl-totales td { font-weight: 700; }

  .cl-val { text-align: center; margin-top: 3mm; }
  .cl-val img { width: 26mm; height: 26mm; }
  .cl-val .txt { font-size: 7.4pt; line-height: 1.6; margin-top: 1.5mm; }
  /* Franja de marcas: al pie y discreta. Se descartó la marca de agua de
     fondo porque un comprobante tiene que leerse limpio —también fotocopiado—
     y SUNAT exige legibilidad de los datos. */
  .cl-marcas { margin-top: 4mm; padding-top: 2.5mm; border-top: 1px solid #000; text-align: center; }
  .cl-marcas .acred { font-size: 8pt; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; margin-bottom: 2mm; }
  .cl-marcas .logos { display: flex; align-items: center; justify-content: center; gap: 7mm; flex-wrap: wrap; }
  .cl-marcas .logos img { max-height: 11mm; max-width: 32mm; }
</style>

<div class="cl">

  {{-- Cabecera --}}
  <div class="cl-cab">
    <div class="cl-logo">
      @if($logoEmisor)
        <img src="{{ $logoEmisor }}" alt="{{ $project->name }}">
      @else
        <div style="font-weight:700; font-size:13pt;">{{ $project->name }}</div>
      @endif
    </div>
    <div class="cl-emisor">
      <div class="razon">{{ $invoice->emisor_razon_social ?: $project->name }}</div>
      @if($invoice->emisor_direccion)<div>{{ strtoupper($invoice->emisor_direccion) }}</div>@endif
      @if($project->phone)<div>Teléfono: {{ $project->phone }}</div>@endif
      @if($correo = $project->setting('contact_email'))<div>Email: {{ strtoupper($correo) }}</div>@endif
    </div>
    <div class="cl-fiscal">
      <div class="ruc">RUC: {{ $invoice->emisor_ruc }}</div>
      <div class="den">{{ $denominacion }}</div>
      <div class="nro">Nro. {{ $invoice->numero }}</div>
    </div>
  </div>

  {{-- Receptor --}}
  <div class="cl-caja">
    <table class="cl-receptor">
      <tr><td class="etq">Emisión:</td><td>{{ $invoice->issue_date?->format('d-M-Y') }}</td></tr>
      @if($invoice->due_date)
      <tr><td class="etq">Vencimiento:</td><td>{{ $invoice->due_date->format('d-M-Y') }}</td></tr>
      @endif
      <tr><td class="etq">Sr(es):</td><td>{{ $invoice->client_name }}</td></tr>
      @if($invoice->client_address)
      <tr><td class="etq">Dirección:</td><td>{{ $invoice->client_address }}</td></tr>
      @endif
      @if($invoice->client_doc_number)
      <tr><td class="etq">{{ $invoice->client_doc_type ?: 'RUC' }}:</td><td>{{ $invoice->client_doc_number }}</td></tr>
      @endif
    </table>
  </div>

  {{-- Franja de condiciones --}}
  <table style="margin-bottom:2.5mm;">
    {{-- Solo las casillas que el sistema puede rellenar de verdad: guia,
         pedido y vendedor no existen en el comprobante y saldrian vacias. --}}
    <tr>
      <th>Documento</th><th>Fecha de emisión</th>
      <th>Condición de Pago</th><th>Tipo de Moneda</th>
    </tr>
    <tr class="cen">
      <td>{{ $invoice->numero }}</td>
      <td>{{ $invoice->issue_date?->format('d-m-Y') }}</td>
      <td>{{ mb_strtoupper($invoice->payment_method ?: 'CONTADO') }}</td>
      <td>{{ $moneda }}</td>
    </tr>
  </table>

  {{-- Documento que modifica: una nota sin él no dice nada. --}}
  @if($invoice->esNota())
  <table style="margin-bottom:2.5mm;">
    <tr><th>Documento que modifica</th><th>Motivo ({{ $invoice->motivo_codigo }})</th></tr>
    <tr class="cen">
      <td>{{ $invoice->afecta_tipo === '03' ? 'Boleta' : 'Factura' }} {{ $invoice->afecta_numero }}</td>
      <td>{{ $invoice->motivo_descripcion }}</td>
    </tr>
  </table>
  @endif

  @php
    // La columna "Código" solo ocupa sitio si algún ítem lo trae: reservarle
    // ancho en blanco estiraba la tabla y dejaba un vacío en toda la hoja.
    $hayCodigo = $invoice->items->contains(fn ($i) => filled($i->product?->sku ?? null));
    // Con precio ya con IGV, "P. Venta Unit. Inc. IGV" repite el unitario:
    // dos columnas con el mismo numero solo estorban la lectura.
    $mostrarUnitConIgv = ! $invoice->igv_included;
  @endphp
  {{-- Detalle --}}
  <table class="cl-items">
    <thead>
      <tr>
        @if($hayCodigo)<th style="width:11%">Código</th>@endif
        <th style="width:{{ $hayDescuento ? ($hayCodigo ? '32%' : '43%') : ($hayCodigo ? '47%' : '58%') }}">Descripción</th>
        <th style="width:5%">UM</th>
        <th style="width:7%">Cantidad</th>
        <th style="width:9%">Precio<br>Unitario</th>
        <th style="width:9%">Importe</th>
        @if($hayDescuento)
        <th style="width:6%">% Desc.</th>
        <th style="width:8%">Descuento</th>
        @endif
        @if($mostrarUnitConIgv)<th style="width:10%">P. Venta Unit.<br>Inc. IGV</th>@endif
      </tr>
    </thead>
    <tbody>
      @foreach($invoice->items as $item)
      @php
        $importe = (float) $item->quantity * (float) $item->unit_price;
        $pct = (float) ($item->discount ?? 0);
        $descuento = $importe * ($pct / 100);
        $unitarioIgv = (float) $item->quantity > 0 ? ((float) $item->total / (float) $item->quantity) : 0;
      @endphp
      <tr>
        @if($hayCodigo)<td>{{ $item->product?->sku ?? '' }}</td>@endif
        <td class="desc">{{ $item->description }}</td>
        <td class="cen">{{ $item->unit ?: 'NIU' }}</td>
        <td class="cen">{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</td>
        <td class="der">{{ $num($item->unit_price) }}</td>
        <td class="der">{{ $num($importe) }}</td>
        @if($hayDescuento)
        <td class="cen">{{ $descuento > 0 ? $num($pct).'%' : '' }}</td>
        <td class="der">{{ $descuento > 0 ? $num($descuento) : '' }}</td>
        @endif
        @if($mostrarUnitConIgv)<td class="der">{{ $num($unitarioIgv) }}</td>@endif
      </tr>
      @endforeach
    </tbody>
  </table>

  @if($invoice->sunat_hash)
  <div class="cl-resumen">Resumen: {{ $invoice->sunat_hash }}</div>
  @endif

  {{-- Avisos del emisor --}}
  <div class="cl-avisos">
    @if($notaPie){!! nl2br(e($notaPie)) !!}<br>@endif
    @if($cuentas !== '' && ! $invoice->esNota())
      - Sírvase cancelar a la orden de {{ $invoice->emisor_razon_social ?: $project->name }} o depositar a las cuentas<br>
      {!! nl2br(e($cuentas)) !!}<br>
    @endif
    @if($invoice->notes)- {{ $invoice->notes }}@endif
  </div>

  <div class="cl-letras">{{ mb_strtoupper($enLetras) }}</div>

  {{-- Recuadro de totales --}}
  <table class="cl-totales">
    <tr>
      <th>Total importe</th>@if($hayDescuento)<th>Total descuento</th>@endif<th>Op. gravada</th>
      <th>Op. inafecta</th><th>Op. exonerada</th><th>Op. gratuitas</th>
      <th>I.G.V. 18%</th><th>Precio venta</th>
    </tr>
    <tr>
      <td>{{ $simbolo }} {{ $num($totalImporte) }}</td>
      @if($hayDescuento)<td>{{ $simbolo }} {{ $num($totalDescuento) }}</td>@endif
      <td>{{ $simbolo }} {{ $num($invoice->subtotal) }}</td>
      <td>{{ $simbolo }} 0.00</td>
      <td>{{ $simbolo }} 0.00</td>
      <td>{{ $simbolo }} 0.00</td>
      <td>{{ $simbolo }} {{ $num($invoice->igv) }}</td>
      <td>{{ $simbolo }} {{ $num($invoice->total) }}</td>
    </tr>
  </table>

  {{-- Validación --}}
  <div class="cl-val">
    <img alt="QR del comprobante"
         src="https://api.qrserver.com/v1/create-qr-code/?size=192x192&ecc=M&data={{ urlencode($qrDatos) }}">
    <div class="txt">
      Representación impresa de la {{ $denominacion }}.
      @if($invoice->sunat_status === 'accepted') Aceptada por SUNAT.@else Pendiente de aceptación por SUNAT.@endif<br>
      Consulte el documento en el portal de SUNAT o del emisor.
    </div>
  </div>

  @if($pieTexto !== '' || $pieMarcas)
  <div class="cl-marcas">
    @if($pieTexto !== '')<div class="acred">{{ $pieTexto }}</div>@endif
    @if($pieMarcas)
    <div class="logos">
      @foreach($pieMarcas as $marca)
        <img src="{{ $marca }}" alt="">
      @endforeach
    </div>
    @endif
  </div>
  @endif

</div>
</x-doc.hoja>
