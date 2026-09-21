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
        \App\Modules\Finanzas\Support\Sunat\Catalogos::codigoDocumentoIdentidad($guia->destinatario_doc_tipo, $guia->destinatario_doc_numero),
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
    $motivos = \App\Modules\Finanzas\Support\Sunat\Catalogos::MOTIVOS_TRASLADO;

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
<x-doc.hoja :incrustada="request()->query('vista') === 'incrustada'" :project="$project" :anulado="$anulado"
            :titulo="$guia->numero.' — '.$project->name">
  <x-slot:pieEmisor>
    {{ $guia->emisor_razon_social ?: $project->name }} &nbsp;·&nbsp; RUC {{ $guia->emisor_ruc }}
  </x-slot:pieEmisor>

@php
    /* Mismos ajustes que el comprobante: el negocio configura su color una vez
       (`invoice_color` / `invoice_header_color`) y toda su papeleria lo usa. */
    $colorCaja   = trim((string) ($project->setting('invoice_color') ?: ($project->setting('secondary_color') ?: '#111827')));
    $colorFranja = trim((string) ($project->setting('invoice_header_color') ?: $colorCaja));
    $telEmisor   = $project->phone ?: $project->setting('quote_whatsapp');
    $mailEmisor  = $project->setting('contact_email');
@endphp
<style>
  /* MISMO FORMATO QUE EL COMPROBANTE. La guia y la factura las recibe la
     misma persona el mismo dia: si una lleva el QR a 24 mm y la otra a 26, o
     los titulos de tabla con fondo distinto, se ven como papeles de dos
     sistemas. Los valores de aqui son los de `invoices/pdf-clasico`, no
     elecciones propias: cambiar uno alli obliga a cambiarlo aqui. */
  .gr { font-size: 8.2pt; color: #000; font-variant-numeric: tabular-nums; }
  .gr table { width: 100%; border-collapse: collapse; }
  .gr td, .gr th { border: 1px solid #000; padding: 1.2mm 1.6mm; vertical-align: top; }
  .gr th { font-weight: 700; text-align: center; background: #fff; }
  .gr .der { text-align: right; }
  .gr .cen { text-align: center; }

  /* Cabecera IDENTICA a la del comprobante: logo · emisor centrado · recuadro
     fiscal con el color del negocio y esquinas redondeadas. */
  .gr-cab { display: flex; align-items: flex-start; gap: 4mm; margin-bottom: 2mm; }
  .gr-logo { width: 42mm; flex: 0 0 42mm; }
  .gr-logo img { max-width: 100%; max-height: 22mm; }
  .gr-emisor { flex: 1; text-align: center; line-height: 1.45; padding-top: 1mm; }
  .gr-emisor .razon { font-weight: 700; font-size: 9.5pt; }
  .gr-emisor .tel { font-weight: 700; }
  .gr-emisor .dir { font-size: 7.6pt; }
  .gr-fiscal { width: 58mm; flex: 0 0 58mm; border: 1.4px solid #000; text-align: center;
               line-height: 1.6; padding: 0; overflow: hidden; border-radius: 1.5mm;
               -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .gr-fiscal .den { font-weight: 700; margin: 0; padding: 2mm; color: #fff; font-size: 9.5pt; letter-spacing: .03em; }
  .gr-fiscal .cuerpo { padding: 2.5mm 2mm 2mm; }
  .gr-fiscal .ruc { font-weight: 700; font-size: 12pt; margin-bottom: 1.5mm; }
  .gr-fiscal .nro { font-weight: 700; font-size: 10.5pt; }
  .gr-fiscal .est { font-size: 6.8pt; font-weight: 700; letter-spacing: .4px; padding: 0 2mm 1.5mm; }

  /* Cuadro unico a dos columnas: el mismo patron que el bloque de datos del
     comprobante. Una sola caja, etiqueta a la izquierda y valor a la derecha,
     sin un borde por cada apartado. */
  .gr-caja { border: 1px solid #000; padding: 1.4mm 2.5mm; margin-bottom: 1.5mm; }
  .gr-datos .gr-parte { table-layout: fixed; }
  .gr-datos .gr-parte > tbody > tr > td { border: none; padding: 0; vertical-align: top; }
  .gr-datos .gr-parte .izq  { width: 55%; padding-right: 4mm; }
  .gr-datos .gr-parte .der2 { width: 45%; padding: 0; }
  /* Etiqueta completa y valor en gris, igual que el cierre: el documento
     entero se lee con el mismo criterio y no con dos estilos distintos. */
  .gr-datos .gr-lista .etq { width: 43mm; white-space: normal; font-weight: 400; }
  .gr-datos .gr-lista .val { color: #7a6a58; }
  /* La ruta va en bloque, no en dos columnas: una direccion completa con
     ubigeo no entra en media columna y se partiria en cuatro renglones. */
  .gr-ruta td { padding: 0.35mm 0; }
  .gr-ruta .val { line-height: 1.35; }
  .gr-lista td { border: none; padding: 0.35mm 0; vertical-align: top; }
  .gr-lista .etq { width: 26mm; font-weight: 700; white-space: nowrap; }
  /* Titulo de cada apartado dentro de la caja: sin fondo, solo versalita. */
  /* Etiqueta y valor en el MISMO renglon, pegados por los dos puntos: con
     etiquetas de 50 caracteres una columna de valores dejaba un hueco que se
     lee como dato faltante. Juntos, la linea se sigue sin saltar la vista. */
  .gr-cierre .gr-lista .par, .gr-datos .gr-lista .par { font-weight: 400; }
  .gr-datos .gr-lista .par .val { color: #7a6a58; }
  .gr-cierre .gr-lista .par .val { color: #7a6a58; }
  .gr-cierre .gr-lista .etq.gr-sub, .gr-datos .gr-lista .etq.gr-sub,
  .gr-cierre .gr-lista .gr-sub, .gr-datos .gr-lista .gr-sub { font-weight: 700; }
  /* Los rotulos de la ruta son titulos de su bloque, no etiquetas de pareja. */
  .gr-datos .gr-ruta .etq { font-weight: 700; }
  .gr-sub { font-weight: 700; font-size: 7.2pt; letter-spacing: .03em;
            text-transform: uppercase; padding-top: 1.2mm !important; }
  .gr-lista tr:first-child .gr-sub { padding-top: 0 !important; }
  .gr-nota { font-size: 7pt; font-weight: 400; line-height: 1.35; }

  /* Rotulos y campos: sin fondos de color. La factura no tiñe nada, solo usa
     la rejilla; la guia hacia lo contrario y por eso se veian distintas. */
  .gr-rot { font-weight: 700; text-align: center; font-size: 7.2pt;
            letter-spacing: .03em; text-transform: uppercase; }
  .gr-cols { display: flex; gap: 2.5mm; margin-bottom: 2.5mm; }
  .gr-cols > div { flex: 1; }
  .gr-campo td:first-child { width: 32mm; font-weight: 700; }

  /* Lista de bienes DE CORRIDO, como el detalle de la factura: sin rayas
     horizontales entre lineas. Se lee como un bloque y las columnas se
     separan solo en vertical; el cuadro lo cierra la ultima fila. */
  .gr-items thead th { font-size: 7.2pt; line-height: 1.12; padding: 1mm 0.8mm; }
  .gr-items td { font-size: 7.9pt; padding: 0.9mm 0.8mm; }
  .gr-items .desc { text-align: left; }
  .gr-items .relleno td { vertical-align: top; }
  .gr-items tbody td { border-top: none; border-bottom: none; }
  .gr-items tbody tr:last-child td { border-bottom: 1px solid #000; }

  /* Cierre bajo la lista: como viaja y que lleva. Es dato de despacho, no de
     identificacion, y va al final igual que los totales de la factura. */
  /* Sin borde ni margen propios: vive DENTRO del recuadro comun. */
  .gr-cierre { border: none; padding: 1.8mm 2.5mm; margin-bottom: 0; }
  .gr-cierre .gr-lista { width: 100%; }
  /* Etiqueta descriptiva completa a la izquierda y valor a la derecha en un
     gris tenue: la etiqueta dice QUE es el dato y el valor resalta por
     contraste, sin negritas ni dos puntos. Es como lo muestra el portal de
     SUNAT y se lee de un vistazo al fiscalizar. */
  /* Solo los TITULOS de apartado van en negrita. Si tambien la llevan las
     etiquetas, la negrita deja de senalar nada y el bloque se lee como un
     muro: el titulo tiene que destacar sobre lo que agrupa. */
  .gr-cierre .gr-lista .etq { width: 62mm; white-space: normal; font-weight: 400; }
  .gr-cierre .gr-lista .val { color: #7a6a58; }
  .gr-cierre .gr-lista td { padding: 0.55mm 0; }

  .gr-motivos { font-size: 6.9pt; line-height: 1.75; }
  /* Pie a tres celdas: todas del mismo alto y con el contenido centrado.
     Antes cada una se alineaba por su cuenta y el bloque salia descuadrado. */
  .gr-pie td { vertical-align: middle; text-align: center; height: 20mm; }
  .gr-pie .gr-rot { height: auto; }
  .gr-motivos .marcado { font-weight: 700; }
  .gr-firma { font-size: 7pt; color: #444; padding-top: 8mm !important; }
  /* Linea sobre la que se firma: antes era un hueco en blanco y cada quien
     firmaba donde queria, a veces encima del rotulo. Al pasar a media hoja se
     ajusta al ancho de su columna en vez de a los 70 mm de antes. */
  .gr-linea { width: 80%; border-top: 1px solid #000; margin: 0 auto 1.2mm; }

  /* Despacho (izquierda) y conformidad (derecha) en la misma franja.
     Tabla y no flex: el motor de PDF compone por tablas, y un flex aqui se
     apila en dos filas al imprimir. `fixed` obliga a respetar los anchos:
     sin el, el texto largo de "Unidad de medida del peso bruto" estiraba la
     columna izquierda y dejaba la firma sin sitio. */
  /* UN SOLO RECUADRO, PARTIDO POR DENTRO.
     Al principio cada bloque traia su propio borde y quedaban a 4 mm uno de
     otro: impreso, esas dos lineas negras tan juntas se leen como un cuadro
     doble, y con el marco de la hoja al lado parecian tres. El borde lo pone
     ahora la tabla que los contiene y por dentro los separa UNA linea, que es
     como esta el resto del documento. */
  .gr-cuadro { width: 100%; border-collapse: collapse; table-layout: fixed;
               border: 1px solid #000; margin-bottom: 2mm; }
  .gr-cuadro > tr > td { vertical-align: top; padding: 0; border: none; }
  .gr-cuadro-izq { width: 62%; border-right: 1px solid #000 !important; }
  .gr-cuadro-der { width: 38%; }
  /* Los dos cuadros arrancan a la misma altura y el de la firma acompana al
     de datos: una caja a media altura al lado de otra entera se ve caida. */
  .gr-cuadro-der .gr-pie { width: 100%; border: none;
                           border-collapse: collapse; margin-bottom: 0; }
  .gr-cuadro-der .gr-pie td { border: none; }
  .gr-cuadro-der .gr-rot { border-bottom: 1px solid #000; padding: 1.2mm 1mm; }
  /* El hueco de firma se mide en milimetros, no en porcentaje: es sitio para
     escribir a mano al entregar, y el motor de PDF ignora un `height:100%`
     dentro de una celda de tabla. 40 mm es lo que ocupa el cuadro de datos de
     al lado en una guia normal. */
  .gr-cuadro-der .gr-firma { height: 40mm; vertical-align: bottom; padding-bottom: 3mm !important; }
  .gr-datos .cod { font-size: 6.8pt; color: #777; }

  .gr-val { text-align: center; margin-top: 4mm; }
  .gr-val img, .gr-val .gr-qr { width: 26mm; height: 26mm; display: inline-block; }
  .gr-val .txt { font-size: 7.4pt; line-height: 1.6; margin-top: 1.5mm; }
</style>

<div class="gr">

  <div class="gr-cab">
    <div class="gr-logo">
      @if($logoEmisor)
        <img src="{{ $logoEmisor }}" alt="{{ $project->name }}">
      @else
        <div style="font-weight:700; font-size:13pt;">{{ $project->name }}</div>
      @endif
    </div>
    <div class="gr-emisor">
      <div class="razon">{{ $guia->emisor_razon_social ?: $project->name }}</div>
      @if($guia->emisor_direccion ?: $project->address)<div class="dir">{{ mb_strtoupper($guia->emisor_direccion ?: $project->address) }}</div>@endif
      @if($telEmisor)<div>Teléfono: <span class="tel">{{ $telEmisor }}</span></div>@endif
      @if($mailEmisor)<div>Email: {{ mb_strtoupper($mailEmisor) }}</div>@endif
    </div>
    {{-- Mismo recuadro que el comprobante: la franja de color arriba y, dentro,
         RUC y numero. Antes iba en gris y con el RUC encima del titulo. --}}
    <div class="gr-fiscal" style="border-color:{{ $colorCaja }}; color:{{ $colorCaja }}">
      <div class="den" style="background:{{ $colorFranja }}">GUÍA DE REMISIÓN ELECTRÓNICA REMITENTE</div>
      <div class="cuerpo">
        <div class="ruc">RUC: {{ $guia->emisor_ruc }}</div>
        <div class="nro">Nro. {{ $guia->numero }}</div>
      </div>
      {{-- El estado de envio a SUNAT NO va en el recuadro fiscal: es un dato
           interno del emisor. Un "ERROR DE ENVIO" impreso junto al RUC hacia
           parecer invalido un documento que ampara el traslado igual. Solo se
           imprime cuando el documento de verdad NO ampara: anulado o rechazado. --}}
      @if($estado && in_array($estado, ['ANULADA', 'RECHAZADA POR SUNAT'], true))<div class="est">{{ $estado }}</div>@endif
    </div>
  </div>

  {{-- Dos columnas de verdad: a la izquierda CUANDO sale y POR QUE, a la
       derecha POR DONDE va. Las direcciones son largas y necesitan el ancho;
       las fechas y el motivo son cortos y se leen en lista. Antes la columna
       derecha existia vacia y todo caia apretado a la izquierda. --}}
  <div class="gr-caja gr-datos">
    <table class="gr-parte">
      <tr>
        <td class="izq">
          <table class="gr-lista">
            <tr><td class="par" colspan="2">Fecha de emisión: <span class="val">{{ $guia->created_at?->format('d/m/Y') }}</span></td></tr>
            <tr><td class="par" colspan="2">Fecha de inicio de traslado: <span class="val">{{ $guia->fecha_traslado?->format('d/m/Y') }}</span></td></tr>
            <tr><td class="par" colspan="2">Motivo de traslado: <span class="val">{{ $motivoTexto }} <span class="cod">(código {{ $guia->motivo_codigo }} — catálogo 20 SUNAT)</span></span></td></tr>
            <tr><td class="par" colspan="2">Comprobante: <span class="val">{{ $guia->invoice?->numero ?: '—' }}</span></td></tr>
            <tr><td class="etq gr-sub" colspan="2">Datos del destinatario</td></tr>
            <tr><td class="par" colspan="2">Razón social o nombre: <span class="val">{{ $guia->destinatario_nombre }}</span></td></tr>
            <tr><td class="par" colspan="2">{{ \App\Modules\Finanzas\Support\Sunat\Catalogos::DOCUMENTOS_IDENTIDAD[$guia->destinatario_doc_tipo] ?? ($guia->destinatario_doc_tipo ?: 'R.U.C.') }}: <span class="val">{{ $guia->destinatario_doc_numero ?: '—' }}</span></td></tr>
          </table>
        </td>
        <td class="der2">
          @if($hayRuta)
          <table class="gr-lista gr-ruta">
            <tr><td class="etq">Punto de partida</td></tr>
            <tr><td class="val">{{ $guia->partida_direccion ?: '—' }}</td></tr>
            <tr><td class="etq" style="padding-top:1.6mm;">Punto de llegada</td></tr>
            <tr><td class="val">{{ $guia->llegada_direccion ?: '—' }}</td></tr>
          </table>
          @endif
        </td>
      </tr>
    </table>
  </div>

  {{-- Bienes trasladados. Sin importes: una guía declara qué y cuánto. --}}
  @php
    // La columna "Codigo" solo ocupa sitio si algun bien lo trae: reservarle
    // ancho en blanco estiraba la tabla, igual que en la factura.
    $hayCodigo = $guia->items->contains(fn ($i) => filled($i->codigo ?: ($i->product?->sku ?? null)));
  @endphp
  <table class="gr-items" style="margin-bottom:2.5mm;">
    <thead>
      <tr>
        {{-- El numero de orden: quien recibe la mercaderia cuenta los bienes
             contra el papel, y sin numerar no hay forma de decir "falta el 3"
             ni de saber si se perdio una linea. La representacion simple ya
             lo traia; esta no. --}}
        <th style="width:7%">N°</th>
        <th style="width:12%">Cant.</th>
        @if($hayCodigo)<th style="width:16%">Código</th>@endif
        <th>Descripción</th>
        <th style="width:18%">Unidad de medida</th>
      </tr>
    </thead>
    <tbody>
      @foreach($guia->items as $i => $item)
      <tr>
        <td class="cen">{{ $i + 1 }}</td>
        <td class="cen">{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</td>
        @if($hayCodigo)<td class="cen">{{ $item->codigo ?: ($item->product?->sku ?? '') }}</td>@endif
        <td class="desc">{{ $item->description }}</td>
        <td class="cen">{{ \App\Modules\Finanzas\Support\Sunat\Catalogos::etiquetaUnidad($item->unit) }}</td>
      </tr>
      @endforeach
      @php
        /* Alto de partida: el cuadro nunca sale pegado al ultimo bien. Mismo
           criterio que el detalle del comprobante. */
        $vacias = max(0, 12 - $guia->items->count());
        $columnas = 4 + ($hayCodigo ? 1 : 0);
      @endphp
      {{-- Una celda por columna: las separaciones verticales siguen bajando
           hasta cerrar el cuadro, como en el talonario preimpreso. --}}
      <tr class="relleno" style="height:{{ $vacias * 4.6 }}mm">
        @for($c = 0; $c < $columnas; $c++)<td>&nbsp;</td>@endfor
      </tr>
    </tbody>
  </table>

  {{-- DESPACHO Y CONFORMIDAD, LADO A LADO.
       La conformidad ocupaba una franja entera de ancho completo para una
       linea de firma, y los datos de carga dejaban medio cuadro en blanco a su
       derecha. Juntos caben en la misma altura y la hoja gana espacio, que en
       una guia importa: cuanto mas corta, menos riesgo de que el detalle se
       parta en dos paginas. --}}
  <table class="gr-cuadro">
  <tr>
  <td class="gr-cuadro-izq">

  {{-- Cómo viaja y qué lleva: al cierre, después del detalle. Cada dato se
       nombra completo —"Unidad de medida del peso bruto", no "Peso"— porque
       quien fiscaliza compara contra el XML, donde los campos se llaman asi. --}}
  <div class="gr-cierre">
    <table class="gr-lista">
            @if($hayCarga)
            <tr><td class="etq gr-sub" colspan="2">Datos de la carga</td></tr>
            <tr><td class="par" colspan="2">Peso bruto total de la carga: <span class="val">{{ $peso }}</span></td></tr>
            <tr><td class="par" colspan="2">Unidad de medida del peso bruto: <span class="val">{{ $guia->peso_unidad ?: 'KGM' }}</span></td></tr>
            <tr><td class="par" colspan="2">Número total de bultos: <span class="val">{{ $guia->bultos ?: '—' }}</span></td></tr>
            @endif
            @if($hayTraslado)
            <tr><td class="etq gr-sub" colspan="2">Datos del traslado</td></tr>
            <tr><td class="par" colspan="2">Modalidad de traslado: <span class="val">{{ $modalidad }}</span></td></tr>
            <tr><td class="par" colspan="2">Indicador de transbordo programado: <span class="val">{{ $guia->transbordo_programado ? 'Sí' : 'No' }}</span></td></tr>
            @if(! $esPublico)
            <tr><td class="par" colspan="2">Indicador traslado en vehículos de categoría M1 o L: <span class="val">{{ $guia->vehiculo_m1l ? 'Sí' : 'No' }}</span></td></tr>
            @endif
            @if($esPublico)
            <tr><td class="etq gr-sub" colspan="2">Datos del transportista</td></tr>
            <tr><td class="par" colspan="2">Razón social del transportista: <span class="val">{{ $guia->transportista_razon_social ?: '—' }}</span></td></tr>
            <tr><td class="par" colspan="2">R.U.C. del transportista: <span class="val">{{ $guia->transportista_ruc ?: '—' }}</span></td></tr>
            <tr><td class="par" colspan="2">Número de registro MTC: <span class="val">{{ $guia->transportista_mtc ?: '—' }}</span></td></tr>
            <tr><td colspan="2" class="gr-nota">El vehículo y el conductor los declara el transportista en su propia guía (GRE Transportista).</td></tr>
            @elseif($guia->vehiculo_m1l)
            {{-- Vehiculo M1 o L: SUNAT no exige placa ni conductor, asi que no
                 se listan cuatro campos con un guion cada uno. El indicador de
                 mas arriba ya dice que el traslado es en M1 o L. --}}
            @if($guia->transportista_mtc)
            <tr><td class="par" colspan="2">Número de registro MTC: <span class="val">{{ $guia->transportista_mtc }}</span></td></tr>
            @endif
            @else
            <tr><td class="etq gr-sub" colspan="2">Datos del vehículo y del conductor</td></tr>
            <tr><td class="par" colspan="2">Número de placa del vehículo: <span class="val">{{ $guia->placaNormalizada() ?: '—' }}</span></td></tr>
            <tr><td class="par" colspan="2">Nombre del conductor: <span class="val">{{ trim(($guia->conductor_nombres ?? '').' '.($guia->conductor_apellidos ?? '')) ?: '—' }}</span></td></tr>
            <tr><td class="par" colspan="2">{{ \App\Modules\Finanzas\Support\Sunat\Catalogos::DOCUMENTOS_IDENTIDAD[$guia->conductor_doc_tipo] ?? ($guia->conductor_doc_tipo ?: 'DNI') }} del conductor: <span class="val">{{ $guia->conductor_doc_numero ?: '—' }}</span></td></tr>
            <tr><td class="par" colspan="2">Número de licencia de conducir: <span class="val">{{ $guia->conductor_licencia ?: '—' }}</span></td></tr>
            @if($guia->transportista_mtc)
            <tr><td class="par" colspan="2">Número de registro MTC: <span class="val">{{ $guia->transportista_mtc }}</span></td></tr>
            @endif
            @endif
            @endif
    </table>
  </div>


  </td>
  <td class="gr-cuadro-der">

  {{-- Pie: SOLO la conformidad. El motivo y el comprobante ya salen en el
       encabezado, y repetirlos aqui gastaba dos tercios del ancho en decir dos
       veces lo mismo. Lo unico que no puede estar arriba es la firma, porque
       se llena a mano al entregar. --}}
  <table class="gr-pie">
    <tr><td class="gr-rot">RECIBÍ CONFORME — DESTINATARIO</td></tr>
    <tr><td class="gr-firma">
      <div class="gr-linea"></div>
      Nombre, DNI y firma · Fecha de recepción
    </td></tr>
  </table>

  </td>
  </tr>
  </table>

  <div class="gr-val">
    {{-- QR LOCAL, como en el comprobante. Se pedia a api.qrserver.com, que
         recibia el RUC del emisor y los datos de cada traslado —a donde va la
         mercaderia y de quien es— y ademas dejaba la guia SIN QR si el
         servicio se caia o el equipo no tenia internet, justo cuando el
         camion esta por salir. --}}
    <div class="gr-qr" data-qr="{{ $qrDatos }}" role="img" aria-label="QR de la guía"></div>
    <div class="txt">
      Representación impresa de la GUÍA DE REMISIÓN ELECTRÓNICA REMITENTE.<br>
      @if(in_array($estado, ['ANULADA', 'BAJA EN TRÁMITE', 'RECHAZADA POR SUNAT', 'ACEPTADA CON OBSERVACIONES'], true))
      <strong>{{ $estado }}</strong> — {{ $estadoNota }}
      @endif
      @if(! empty($guia->sunat_observaciones))
      <br><span style="font-size:6.9pt;">{{ is_array($guia->sunat_observaciones) ? implode(' · ', $guia->sunat_observaciones) : $guia->sunat_observaciones }}</span>
      @endif<br>
      {{-- Una guia NO es una factura: quien recibe la mercaderia con este
           papel puede creer que ya tiene su comprobante. --}}
      Este documento sustenta el traslado de los bienes; no acredita la venta ni otorga crédito fiscal.<br>
      Consulte el documento en el portal de SUNAT o del emisor.
    </div>
  </div>

</div>
{{-- El mismo dibujante de QR que usa el comprobante: se incrusta el
     script en la pagina para que funcione sin red. --}}
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
