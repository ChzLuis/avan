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
    /* Color del recuadro fiscal y de los iconos del emisor. Configurable
       (`invoice_color`), con el azul de comprobante por defecto; los iconos
       usan el color de marca del negocio. Nada fijo en la plantilla. */
    $colorCaja  = trim((string) ($project->setting('invoice_color') ?: ($project->setting('secondary_color') ?: '#111827')));
    // La franja puede usar el color de marca sin teñir también el borde, RUC
    // y número. Si no se configura, conserva exactamente el aspecto anterior.
    $colorFranja = trim((string) ($project->setting('invoice_header_color') ?: $colorCaja));
    $colorMarca = trim((string) ($project->setting('primary_color') ?: $colorCaja));
    $telEmisor  = $project->phone ?: $project->setting('quote_whatsapp');
    $mailEmisor = $project->setting('contact_email');
    $pieMarcas = [];
    foreach (range(1, 6) as $n) {
        if ($u = $urlImagen($project->setting("invoice_marca_{$n}"))) {
            $pieMarcas[] = $u;
        }
    }
    $hayPie = (string) $project->setting('invoice_pie', '1') !== '0'
        && ($pieTexto !== '' || $pieMarcas !== []);
    $logoEmisor = $project->setting('logo_url') ?: ($project->logo_url ?? null);
    $logoEmisor = $logoEmisor
        ? (str_starts_with($logoEmisor, 'http') ? $logoEmisor : asset('storage/'.ltrim($logoEmisor, '/')))
        : null;

@endphp
<x-doc.hoja :incrustada="request()->query('vista') === 'incrustada'" :project="$project" :anulado="$anulado" :previa="!empty($vistaPrevia)" :titulo="$invoice->nombreArchivo()"
            :descargar="request()->fullUrlWithQuery(['descargar' => 1])">
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
  .cl-emisor .tel { font-weight: 700; }
  .cl-fiscal { width: 58mm; flex: 0 0 58mm; border: 1.4px solid #000; padding: 3mm 2mm; text-align: center; line-height: 1.6; }
  .cl-fiscal .ruc { font-weight: 700; font-size: 11pt; }
  .cl-fiscal .den { font-weight: 700; font-size: 10pt; margin: 1.5mm 0; }
  .cl-fiscal .nro { font-weight: 700; font-size: 10pt; }

  /* Recuadro fiscal: cabecera con color y, dentro, RUC y numero. El color se
     imprime solo con print-color-adjust; sin el, el navegador lo descarta. */
  .cl-fiscal { padding: 0; overflow: hidden; border-radius: 1.5mm;
               -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .cl-fiscal .den { margin: 0; padding: 2mm 2mm; color: #fff; font-size: 9.5pt; letter-spacing: .03em; }
  .cl-fiscal .cuerpo { padding: 2.5mm 2mm 2mm; }
  .cl-fiscal .ruc { font-size: 12pt; margin-bottom: 1.5mm; }
  .cl-fiscal .nro { font-size: 10.5pt; }

  /* Datos del receptor: etiqueta a la izquierda, valor a la derecha. */
  .cl-receptor { margin-bottom: 0; }
  .cl-receptor td { border: none; padding: 0.35mm 0; vertical-align: top; }
  .cl-receptor .etq { width: 18mm; font-weight: 700; white-space: nowrap; }
  /* Observaciones: caja propia y etiquetada, como el resto de bloques del
     comprobante. Solo existe si el emisor escribio algo. */
  .cl-obs { border: 1px solid #000; padding: 1.4mm 2.5mm; margin: 1.5mm 0; font-size: 7.9pt; line-height: 1.45; }
  .cl-obs .etq { font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }

  /* Las dos columnas del bloque de datos: sin bordes propios (los pone la
     caja) y con una linea que las separa. */
  .cl-datos .cl-parte { table-layout: fixed; }
  .cl-datos .cl-parte > tbody > tr > td { border: none; padding: 0; vertical-align: top; }
  .cl-datos .cl-parte .izq  { width: 58%; padding-right: 3mm; }
  .cl-datos .cl-parte .der2 { width: 42%; padding-left: 3mm; }

  .cl-datos .cl-cond .etq { width: 24mm; text-align: right; }

  /* Cargo de recepcion: quien recibe la mercaderia firma aqui. En papel es
     la prueba de la entrega. */
  .cl-recibo { border: 1px solid #000; border-radius: 3mm; padding: 2.5mm 5mm;
               width: 74mm; font-size: 7.6pt; line-height: 2.4; margin-top: 3mm; }

  .cl-caja { border: 1px solid #000; padding: 1.4mm 2.5mm; margin-bottom: 1.5mm; }

  .cl-items thead th { font-size: 7.2pt; line-height: 1.12; padding: 1mm 0.8mm; }
  .cl-items td { font-size: 7.9pt; padding: 0.9mm 0.8mm; }
  .cl-items .desc { text-align: left; }
  .cl-items .relleno td { vertical-align: top; }
  /* Sin rayas horizontales entre lineas: el detalle se lee como un bloque y
     las columnas se separan solo en vertical. El cuadro se cierra abajo con
     la ultima fila, haya relleno o no. */
  .cl-items tbody td { border-top: none; border-bottom: none; }
  .cl-items tbody tr:last-child td { border-bottom: 1px solid #000; }

  .cl-resumen { border: 1px solid #000; border-top: none; padding: 1.2mm 1.6mm; font-size: 7.6pt; }
  .cl-avisos { margin: 1.5mm 0; font-size: 7.8pt; line-height: 1.45; }
  .cl-letras { font-weight: 700; margin: 1.5mm 0 1.5mm; font-size: 8.4pt; }
  .cl-totales th, .cl-totales td { text-align: center; font-size: 7.7pt; }
  .cl-totales td { font-weight: 700; }

  /* Cierre a dos bloques: el cargo que se firma a la izquierda y la
     validacion con su QR a la derecha. */
  .cl-cierre { display: flex; align-items: flex-start; justify-content: space-between; gap: 8mm; margin-top: 3mm; }
  .cl-cierre .cl-recibo { margin-top: 0; flex: 0 0 74mm; }
  .cl-val { text-align: center; margin-top: 0; flex: 1; }
  .cl-val img, .cl-val .cl-qr { width: 26mm; height: 26mm; display: inline-block; }
  .cl-val .txt { font-size: 7.4pt; line-height: 1.6; margin-top: 1.5mm; }
  /* Franja de marcas: al pie y discreta. Se descartó la marca de agua de
     fondo porque un comprobante tiene que leerse limpio —también fotocopiado—
     y SUNAT exige legibilidad de los datos. */
  .cl-conpie { padding-bottom: 22mm; }
  .cl-marcas { position: absolute; left: 15mm; right: 15mm; bottom: 24mm;
               padding-top: 2.5mm; border-top: 1px solid #000; text-align: center; }
  .cl-marcas .acred { font-size: 8pt; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; margin-bottom: 2mm; }
  .cl-marcas .logos { display: flex; align-items: center; justify-content: center; gap: 6mm; flex-wrap: wrap; }
  .cl-marcas .logos img { width: 34mm; height: 12mm; object-fit: contain; object-position: center; }
</style>

<div class="cl @if($hayPie)cl-conpie @endif">

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
      @if($telEmisor)<div>Teléfono: <span class="tel">{{ $telEmisor }}</span></div>@endif
      @if($mailEmisor)<div>Email: {{ strtoupper($mailEmisor) }}</div>@endif
    </div>
    <div class="cl-fiscal" style="border-color:{{ $colorCaja }}; color:{{ $colorCaja }}">
      <div class="den" style="background:{{ $colorFranja }}">{{ $denominacion }}</div>
      <div class="cuerpo">
        <div class="ruc">RUC: {{ $invoice->emisor_ruc }}</div>
        <div class="nro">Nro. {{ $invoice->numero }}</div>
      </div>
    </div>
  </div>

  {{-- Receptor y condiciones, en un solo cuadro a dos columnas. Guia y O/C
       se imprimen vacias a proposito: son las casillas que el negocio rellena
       a mano cuando el cliente las pide. --}}
  <div class="cl-caja cl-datos">
    <table class="cl-parte">
      <tr>
        <td class="izq">
          <table class="cl-receptor">
            <tr><td class="etq">Emisión</td><td>: {{ $invoice->issue_date?->format('d/m/Y') }}</td></tr>
            <tr><td class="etq">Cliente</td><td>: {{ $invoice->client_name }}</td></tr>
            @if($invoice->client_doc_number)
            <tr><td class="etq">{{ $invoice->client_doc_type ?: 'RUC' }}</td><td>: {{ $invoice->client_doc_number }}</td></tr>
            @endif
            @if($invoice->client_address)
            <tr><td class="etq">Dirección</td><td>: {{ $invoice->client_address }}</td></tr>
            @endif
          </table>
        </td>
        <td class="der2">
          <table class="cl-receptor cl-cond">
            <tr><td class="etq">Guía Nro.</td><td>:</td></tr>
            <tr><td class="etq">O/C</td><td>:</td></tr>
            <tr><td class="etq">Vencimiento</td><td>:@if($invoice->due_date) {{ $invoice->due_date->format('d/m/Y') }}@endif</td></tr>
            <tr><td class="etq">Cond. Pago</td><td>: {{ mb_strtoupper($invoice->payment_method ?: 'CONTADO') }}</td></tr>
          </table>
        </td>
      </tr>
    </table>
  </div>

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
        <th style="width:4%">Ítem</th>
        @if($hayCodigo)<th style="width:11%">Código</th>@endif
        <th style="width:{{ $hayDescuento ? ($hayCodigo ? '28%' : '39%') : ($hayCodigo ? '43%' : '54%') }}">Descripción</th>
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
        <td class="cen">{{ $loop->iteration }}</td>
        @if($hayCodigo)<td>{{ $item->product?->sku ?? '' }}</td>@endif
        <td class="desc">{{ $item->description }}</td>
        <td class="cen">{{ \App\Support\Sunat\Catalogos::etiquetaUnidad($item->unit) }}</td>
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

      {{-- El cuadro se estira hasta un minimo de lineas, como el talonario
           preimpreso: una sola fila alta mantiene los separadores verticales
           sin dibujar rayas horizontales de mas. Con muchos items no aparece. --}}
      @php
        /* Alto de partida por si el JS no llega a correr: el cuadro nunca sale
           pegado al ultimo item. El ajuste fino lo hace el script del pie. */
        $vacias = max(0, 12 - $invoice->items->count());
        $columnas = 6 + ($hayCodigo ? 1 : 0) + ($hayDescuento ? 2 : 0) + ($mostrarUnitConIgv ? 1 : 0);
      @endphp
      @if(true)
      {{-- Una celda por columna: las separaciones verticales siguen bajando
           hasta cerrar el cuadro, como en el talonario preimpreso. --}}
      <tr class="relleno" style="height:{{ $vacias * 4.6 }}mm">
        @for($c = 0; $c < $columnas; $c++)<td>&nbsp;</td>@endfor
      </tr>
      @endif
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
  </div>

  @if(filled($invoice->notes))
  <div class="cl-obs"><span class="etq">Observaciones:</span> {!! nl2br(e($invoice->notes)) !!}</div>
  @endif

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

  <div class="cl-cierre">
  <div class="cl-recibo">
    <div>Recibido por:</div>
    <div>DNI:</div>
    <div>Firma:</div>
    <div>Fecha: &nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;/</div>
  </div>

  {{-- Validación, al lado del cargo --}}
  <div class="cl-val">
    <div class="cl-qr" role="img" aria-label="QR del comprobante" data-qr="{{ $qrDatos }}"></div>
    <div class="txt">
      Representación impresa de la {{ $denominacion }}.
      @if($invoice->sunat_status === 'accepted') Aceptada por SUNAT.@else Pendiente de aceptación por SUNAT.@endif<br>
      Consulte el documento en el portal de SUNAT o del emisor.
    </div>
  </div>
  </div>

  @if($hayPie)
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

{{-- El cuadro del detalle se estira hasta el pie de la hoja, ni un milimetro
     mas: se mide lo que ocupa todo lo demas y se reparte lo que sobra de la
     ultima pagina. Corre ANTES que el anclaje del pie (esta declarado antes),
     asi que aquel mide la hoja ya con su alto definitivo. --}}
<script>
(function () {
  var fila = document.querySelector('.cl-items .relleno');
  var hoja = document.getElementById('hoja');
  if (!fila || !hoja) return;

  var MM = 96 / 25.4, PAGINA = 275 * MM, PIE = 22 * MM;

  function estirar() {
    fila.style.height = '0px';
    hoja.style.minHeight = '';

    // Se mide el documento como queda IMPRESO, sin los botones de pantalla.
    var ocultos = [].slice.call(document.querySelectorAll('.no-print'));
    ocultos.forEach(function (el) { el.dataset.d = el.style.display; el.style.display = 'none'; });
    var alto = hoja.scrollHeight;
    ocultos.forEach(function (el) { el.style.display = el.dataset.d || ''; });

    var paginas = Math.max(1, Math.ceil((alto + PIE) / PAGINA));
    var sobra   = paginas * PAGINA - PIE - alto;

    // Un margen de 2 mm evita que el redondeo empuje una pagina de mas.
    fila.style.height = Math.max(0, sobra - 2 * MM) + 'px';
  }

  window.addEventListener('load', estirar);
  window.addEventListener('beforeprint', estirar);
  estirar();
})();
</script>
{{-- QR generado AQUI, sin servicio externo: antes se pedia a api.qrserver.com,
     que recibia RUC, importes y hash de cada comprobante y podia caerse. --}}
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
