@php
    /* Guía de remisión remitente, con la distribución del talonario impreso del
       negocio: destinatario a la izquierda, transportista a la derecha, y las
       direcciones de partida y llegada bajo el destinatario.

       Diferencia importante con el papel: la guía ELECTRÓNICA exige datos que
       el talonario antiguo no traía —peso bruto, bultos, ubigeo de partida y
       llegada, modalidad y documento del conductor—. Todos son obligatorios en
       `GuiaRemisionController`, así que se imprimen: sin ellos SUNAT rechaza.
       Y al revés: la columna "IMPORTE" del papel no va, porque una guía declara
       qué se traslada, no cuánto vale. */

    $anulado = ($guia->baja_estado ?? null) === 'accepted';

    $qrDatos = implode('|', [
        $guia->emisor_ruc, '09', $guia->serie, $guia->correlativo,
        $guia->created_at?->format('Y-m-d'),
        \App\Support\Sunat\Catalogos::codigoDocumentoIdentidad($guia->destinatario_doc_tipo, $guia->destinatario_doc_numero),
        $guia->destinatario_doc_numero ?: '-',
    ]);

    $logoEmisor = $project->setting('logo_url') ?: ($project->logo_url ?? null);
    $logoEmisor = $logoEmisor
        ? (str_starts_with($logoEmisor, 'http') ? $logoEmisor : asset('storage/'.ltrim($logoEmisor, '/')))
        : null;

    $modalidad = match ((string) $guia->modalidad) {
        '01' => 'Transporte público', '02' => 'Transporte privado',
        default => $guia->modalidad ?: '—',
    };

    // Catálogo 20 de SUNAT, tomado de `Catalogos::MOTIVOS_TRASLADO`: son los
    // 14 que el sistema acepta, no una lista escrita a mano en la plantilla
    // —que se quedaba corta y omitía Consignación, Devolución y otros cuatro—.
    $motivos = \App\Support\Sunat\Catalogos::MOTIVOS_TRASLADO;

    $peso = rtrim(rtrim(number_format((float) $guia->peso_total, 3), '0'), '.');

    /* Qué bloque toca según la modalidad. En transporte PÚBLICO lo obligatorio
       es quién lleva la carga (RUC del transportista y su registro MTC); el
       vehículo y el conductor los declara el transportista en SU propia guía.
       En transporte PRIVADO es al revés. Imprimir el que no aplica solo llena
       la hoja de guiones. Mismo criterio que valida `GuiaRemisionController`
       con sus `required_if:modalidad,01|02`. */
    $esPublico = (string) $guia->modalidad === '01';

    // Si no hay NINGUN dato de traslado, el bloque no se imprime: media hoja de
    // guiones no informa. Ojo: al emitir de verdad SUNAT los exige segun la
    // modalidad, asi que esto solo pasa en borradores.
    $hayTraslado = filled($guia->modalidad) || filled($guia->vehiculo_placa)
        || filled($guia->transportista_ruc) || filled($guia->conductor_doc_numero);
    $hayRuta = filled($guia->partida_direccion) || filled($guia->llegada_direccion);
    $hayCarga = (float) $guia->peso_total > 0 || filled($guia->bultos);
    $motivoTexto = $motivos[(string) $guia->motivo_codigo] ?? ($guia->motivo_descripcion ?: '—');

    /* Estados que SUNAT reconoce para un documento electronico. No existe
       "conciliacion": eso es un concepto contable, no una respuesta del CDR.
       La baja manda sobre todo lo demas — una guia anulada ya no ampara el
       traslado aunque antes fuera aceptada. */
    [$estado, $estadoNota] = match (true) {
        ($guia->baja_estado ?? null) === 'accepted'  => ['ANULADA', 'Dada de baja ante SUNAT: no ampara traslado.'],
        ($guia->baja_estado ?? null) === 'pending'   => ['BAJA EN TRÁMITE', 'Se solicitó la baja y SUNAT aún no responde.'],
        ($guia->sunat_status ?? null) === 'accepted' && ! empty($guia->sunat_observaciones)
            => ['ACEPTADA CON OBSERVACIONES', 'Válida. SUNAT anotó observaciones que conviene corregir.'],
        ($guia->sunat_status ?? null) === 'accepted' => ['ACEPTADA POR SUNAT', 'Documento válido.'],
        ($guia->sunat_status ?? null) === 'rejected' => ['RECHAZADA POR SUNAT', 'No ampara el traslado: corregir y volver a emitir.'],
        ($guia->sunat_status ?? null) === 'error'    => ['ERROR DE ENVÍO', 'No llegó a SUNAT. Reintentar el envío.'],
        default                                      => ['PENDIENTE DE ENVÍO', 'Aún no tiene respuesta de SUNAT.'],
    };
@endphp
<x-doc.hoja :project="$project" :anulado="$anulado"
            :titulo="$guia->numero.' — '.$project->name">
  <x-slot:pieEmisor>
    {{ $guia->emisor_razon_social ?: $project->name }} &nbsp;·&nbsp; RUC {{ $guia->emisor_ruc }}
  </x-slot:pieEmisor>

<style>
  .gr { font-size: 8.2pt; color: #000; font-variant-numeric: tabular-nums; }
  .gr table { width: 100%; border-collapse: collapse; }
  .gr td, .gr th { border: 1px solid #000; padding: 1.2mm 1.6mm; vertical-align: top; }
  .gr th { font-weight: 700; text-align: center; }
  .gr .cen { text-align: center; }

  .gr-cab { display: flex; align-items: flex-start; gap: 4mm; margin-bottom: 2.5mm; }
  .gr-logo { width: 34mm; flex: 0 0 34mm; }
  .gr-logo img { max-width: 100%; max-height: 20mm; }
  .gr-emisor { flex: 1; line-height: 1.4; }
  .gr-emisor .razon { font-weight: 700; font-size: 12pt; line-height: 1.2; }
  .gr-emisor .giro { font-weight: 700; font-size: 7.4pt; letter-spacing: .4px; }
  .gr-emisor .dir { font-size: 7.4pt; margin-top: 1mm; }
  .gr-fiscal { width: 60mm; flex: 0 0 60mm; border: 1.4px solid #000; text-align: center; }
  .gr-fiscal .ruc { font-weight: 700; font-size: 10.5pt; padding: 1.6mm; border-bottom: 1px solid #000; }
  .gr-fiscal .den { font-weight: 700; font-size: 9pt; padding: 1.6mm; border-bottom: 1px solid #000; background: #f2f2f2; }
  .gr-fiscal .nro { font-weight: 700; font-size: 11pt; padding: 1.8mm 1.8mm 0.8mm; }
  .gr-fiscal .est { font-size: 6.8pt; font-weight: 700; letter-spacing: .4px; padding: 0 1.8mm 1.6mm; }

  /* Banda de etiqueta, como los títulos del talonario. */
  .gr-rot { background: #f2f2f2; font-weight: 700; text-align: center; font-size: 7.6pt; letter-spacing: .3px; }
  .gr-cols { display: flex; gap: 2.5mm; margin-bottom: 2.5mm; }
  .gr-cols > div { flex: 1; }
  .gr-campo td:first-child { width: 32mm; font-weight: 700; background: #fafafa; }

  .gr-motivos { font-size: 6.9pt; line-height: 1.75; }
  .gr-pie td { vertical-align: top; }
  .gr-motivos .marcado { font-weight: 700; }
  .gr-firma { height: 22mm; text-align: center; vertical-align: bottom !important; font-size: 7pt; color: #444; }

  .gr-val { text-align: center; margin-top: 4mm; }
  .gr-val img { width: 24mm; height: 24mm; }
  .gr-val .txt { font-size: 7.2pt; line-height: 1.6; margin-top: 1.2mm; }
</style>

<div class="gr">

  <div class="gr-cab">
    <div class="gr-logo">
      @if($logoEmisor)<img src="{{ $logoEmisor }}" alt="{{ $project->name }}">@endif
    </div>
    <div class="gr-emisor">
      <div class="razon">{{ $guia->emisor_razon_social ?: $project->name }}</div>
      @if($giro = $project->setting('business_giro'))<div class="giro">{{ mb_strtoupper($giro) }}</div>@endif
      <div class="dir">
        {{ $project->address }}<br>
        @if($project->phone)Cel.: {{ $project->phone }}@endif
        @if($correo = $project->setting('contact_email')) · {{ $correo }}@endif
      </div>
    </div>
    <div class="gr-fiscal">
      <div class="ruc">R.U.C. {{ $guia->emisor_ruc }}</div>
      <div class="den">GUÍA DE REMISIÓN — REMITENTE</div>
      <div class="nro">{{ $guia->numero }}</div>
      <div class="est">{{ $estado }}</div>
    </div>
  </div>

  {{-- Fechas --}}
  <table class="gr-campo" style="margin-bottom:2.5mm;">
    <tr>
      <td>Fecha de emisión</td><td class="cen">{{ $guia->created_at?->format('d / m / Y') }}</td>
      <td style="width:38mm; font-weight:700; background:#fafafa;">Fecha de inicio del traslado</td>
      <td class="cen">{{ $guia->fecha_traslado?->format('d / m / Y') }}</td>
    </tr>
  </table>

  {{-- Cada fila de dos columnas solo se abre cuando HAY algo que poner a los
       dos lados. Un recuadro solo se pinta a ancho completo: media hoja en
       blanco al costado no informa de nada. --}}
  <div class="gr-cols">
    <div>
      <table class="gr-campo">
        <tr><td colspan="2" class="gr-rot">DESTINATARIO</td></tr>
        <tr><td>Razón social</td><td>{{ $guia->destinatario_nombre }}</td></tr>
        <tr><td>{{ $guia->destinatario_doc_tipo ?: 'R.U.C.' }}</td><td>{{ $guia->destinatario_doc_numero ?: '—' }}</td></tr>
        <tr><td>Comprobante</td><td>{{ $guia->invoice?->numero ?: '—' }}</td></tr>
      </table>
    </div>
    @if($hayTraslado)
    <div>
      <table class="gr-campo">
        <tr><td colspan="2" class="gr-rot">{{ $esPublico ? 'TRANSPORTISTA' : 'TRASLADO' }}</td></tr>
        <tr><td>Modalidad</td><td>{{ $modalidad }}</td></tr>
        @if($esPublico)
        <tr><td>Razón social</td><td>{{ $guia->transportista_razon_social ?: '—' }}</td></tr>
        <tr><td>R.U.C.</td><td>{{ $guia->transportista_ruc ?: '—' }}</td></tr>
        @else
        <tr><td colspan="2" style="font-size:7pt; background:#fff; font-weight:400;">
          Traslado con vehículo propio del remitente.
        </td></tr>
        @endif
      </table>
    </div>
    @endif
  </div>

  @if($hayRuta)
  {{-- Partida y llegada se emparejan entre sí: es la misma clase de dato y la
       fila queda siempre equilibrada, haya o no transportista. --}}
  <div class="gr-cols">
    <div>
      <table class="gr-campo">
        <tr><td colspan="2" class="gr-rot">PUNTO DE PARTIDA</td></tr>
        <tr><td>Dirección</td><td>{{ $guia->partida_direccion ?: '—' }}</td></tr>
        <tr><td>Ubigeo</td><td>{{ $guia->partida_ubigeo ?: '—' }}</td></tr>
      </table>
    </div>
    <div>
      <table class="gr-campo">
        <tr><td colspan="2" class="gr-rot">PUNTO DE LLEGADA</td></tr>
        <tr><td>Dirección</td><td>{{ $guia->llegada_direccion ?: '—' }}</td></tr>
        <tr><td>Ubigeo</td><td>{{ $guia->llegada_ubigeo ?: '—' }}</td></tr>
      </table>
    </div>
  </div>
  @endif

  @if($hayTraslado || $hayCarga)
  <div class="gr-cols">
    @if($hayTraslado)
    <div>
      @if($esPublico)
      <table class="gr-campo">
        <tr><td colspan="2" class="gr-rot">TRANSPORTE PÚBLICO</td></tr>
        <tr><td>Registro MTC</td><td>{{ $guia->transportista_mtc ?: '—' }}</td></tr>
        <tr><td colspan="2" style="font-size:7pt; background:#fff; font-weight:400;">
          El vehículo y el conductor los declara el transportista en su propia
          guía de remisión (GRE Transportista).
        </td></tr>
      </table>
      @else
      <table class="gr-campo">
        <tr><td colspan="2" class="gr-rot">VEHÍCULO Y CONDUCTOR</td></tr>
        <tr><td>Placa N.°</td><td>{{ $guia->vehiculo_placa ?: '—' }}</td></tr>
        <tr><td>Conductor</td><td>{{ trim(($guia->conductor_nombres ?? '').' '.($guia->conductor_apellidos ?? '')) ?: '—' }}</td></tr>
        <tr><td>{{ $guia->conductor_doc_tipo ?: 'DNI' }}</td><td>{{ $guia->conductor_doc_numero ?: '—' }}</td></tr>
        <tr><td>Licencia de conducir</td><td>{{ $guia->conductor_licencia ?: '—' }}</td></tr>
        @if($guia->transportista_mtc)
        <tr><td>Registro MTC</td><td>{{ $guia->transportista_mtc }}</td></tr>
        @endif
      </table>
      @endif
    </div>
    @endif
    @if($hayCarga)
    {{-- Peso y bultos: obligatorios en la guía electrónica y ausentes del
         talonario impreso. Se destacan porque es lo que revisan en carretera. --}}
    <div>
      <table class="gr-campo">
        <tr><td colspan="2" class="gr-rot">CARGA</td></tr>
        <tr><td>Peso bruto total</td><td style="font-weight:700;">{{ $peso }} {{ $guia->peso_unidad ?: 'KGM' }}</td></tr>
        <tr><td>N.° de bultos</td><td style="font-weight:700;">{{ $guia->bultos ?: '—' }}</td></tr>
      </table>
    </div>
    @endif
  </div>
  @endif

  {{-- Bienes trasladados. Sin importes: una guía declara qué y cuánto. --}}
  <table style="margin-bottom:2.5mm;">
    <thead>
      <tr>
        <th style="width:12%">Cant.</th>
        <th style="width:16%">Código</th>
        <th style="width:54%">Descripción</th>
        <th style="width:18%">Unidad de medida</th>
      </tr>
    </thead>
    <tbody>
      @foreach($guia->items as $item)
      <tr>
        <td class="cen">{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</td>
        <td>{{ $item->codigo ?: ($item->product?->sku ?? '') }}</td>
        <td>{{ $item->description }}</td>
        <td class="cen">{{ $item->unit ?: 'NIU' }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Pie. El motivo se imprime resuelto, no como lista de 14 casillas: eso
       tiene sentido en un talonario donde se marca a mano, pero en un documento
       electronico el motivo ya se eligio y 13 casillas vacias solo estiran la
       hoja y dejan en blanco las columnas de al lado. --}}
  <table class="gr-pie" style="margin-bottom:2mm;">
    <tr>
      <td class="gr-rot" style="width:34%">MOTIVO DEL TRASLADO</td>
      <td class="gr-rot" style="width:33%">COMPROBANTE DE PAGO</td>
      <td class="gr-rot" style="width:33%">RECIBÍ CONFORME — DESTINATARIO</td>
    </tr>
    <tr>
      <td style="font-size:9pt; font-weight:700; vertical-align:middle;">
        {{ $motivoTexto }}
        <div style="font-size:6.8pt; font-weight:400; color:#555; margin-top:1mm;">
          Código {{ $guia->motivo_codigo }} · Catálogo 20 SUNAT
        </div>
      </td>
      <td style="font-size:7.6pt; line-height:1.9;">
        @if($guia->invoice)
          Factura {{ $guia->invoice->numero }}<br>
          {{ $guia->invoice->issue_date?->format('d/m/Y') }}
        @else
          <span style="color:#666;">Sin comprobante asociado</span>
        @endif
      </td>
      <td class="gr-firma">Nombre, DNI y firma</td>
    </tr>
  </table>

  <div class="gr-val">
    <img alt="QR de la guía"
         src="https://api.qrserver.com/v1/create-qr-code/?size=192x192&ecc=M&data={{ urlencode($qrDatos) }}">
    <div class="txt">
      Representación impresa de la GUÍA DE REMISIÓN REMITENTE ELECTRÓNICA.<br>
      <strong>{{ $estado }}</strong> — {{ $estadoNota }}
      @if(! empty($guia->sunat_observaciones))
      <br><span style="font-size:6.9pt;">{{ is_array($guia->sunat_observaciones) ? implode(' · ', $guia->sunat_observaciones) : $guia->sunat_observaciones }}</span>
      @endif<br>
      Consulte el documento en el portal de SUNAT o del emisor.
    </div>
  </div>

</div>
</x-doc.hoja>
