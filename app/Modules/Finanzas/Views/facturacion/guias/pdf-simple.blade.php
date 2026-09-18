@php
    /* Guía de remisión, presentación moderna.
       Misma información y mismos campos obligatorios que la clásica, pero con
       la identidad del negocio: bloques redondeados, iconos que anclan cada
       rótulo y los colores que el negocio eligió en el constructor. */

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

    /* Los colores salen del constructor: fijarlos aquí obligaría a tocar la
       plantilla cada vez que un negocio cambia su marca. */
    $acento = $project->setting('primary_color') ?: '#ff6001';
    $tinta  = $project->setting('secondary_color') ?: '#1b3a5c';

    $modalidad = match ((string) $guia->modalidad) {
        '01' => 'Público', '02' => 'Privado',
        default => $guia->modalidad ?: '—',
    };

    $motivos = \App\Modules\Finanzas\Support\Sunat\Catalogos::MOTIVOS_TRASLADO;
    $motivoTexto = $motivos[(string) $guia->motivo_codigo] ?? ($guia->motivo_descripcion ?: '—');

    $peso = rtrim(rtrim(number_format((float) $guia->peso_total, 3), '0'), '.');

    $esPublico   = (string) $guia->modalidad === '01';
    $exentoM1L   = ! $esPublico && (bool) ($guia->vehiculo_m1l ?? false);
    $hayRuta     = filled($guia->partida_direccion) || filled($guia->llegada_direccion);

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

    /* Solo una guía aceptada ampara el traslado; cualquier otro estado se
       marca en el color de alerta para que nadie salga con un papel que no
       sirve en un control. */
    $estadoOk = ($guia->sunat_status ?? null) === 'accepted' && ! $anulado;

    /* Los iconos van en SVG dentro del HTML: una imagen externa no siempre
       resuelve al imprimir y dejaría los rótulos cojos. */
    $ico = fn (string $d, float $mm = 4.2, ?string $color = null) =>
        '<svg class="gm-ico" width="'.$mm.'mm" height="'.$mm.'mm" viewBox="0 0 24 24" fill="none" stroke="'
        .($color ?: 'currentColor').'" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">'.$d.'</svg>';

    $icoPin     = '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0116 0z"/><circle cx="12" cy="10" r="3"/>';
    $icoCaja    = '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/>';
    $icoPeso    = '<path d="M12 3v18"/><path d="M5 7h14"/><path d="M8 7l-4 7h8L8 7z"/><path d="M16 7l-4 7h8l-4-7z"/>';
    $icoCandado = '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/>';
    $icoAlerta  = '<circle cx="12" cy="12" r="9"/><path d="M12 7v6"/><path d="M12 16.5v.5"/>';
    $icoOk      = '<circle cx="12" cy="12" r="9"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/>';
    $icoDoc     = '<path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8l-5-5z"/><path d="M14 3v5h5"/>';
@endphp
<x-doc.hoja :project="$project" :anulado="$anulado"
            :titulo="$guia->numero.' — '.$project->name">
  <x-slot:pieEmisor>
    {{ $guia->emisor_razon_social ?: $project->name }} &nbsp;·&nbsp; RUC {{ $guia->emisor_ruc }}
  </x-slot:pieEmisor>

<style>
  .gm {
    --acc: {{ $acento }};
    --tin: {{ $tinta }};
    --bor: #d9e0ea;
    --sua: #f4f7fa;
    --mut: #6b7a8d;
    font-size: 8pt; color: #333; line-height: 1.45;
    font-variant-numeric: tabular-nums;
  }
  .gm .cen { text-align: center; }
  .gm .der { text-align: right; }
  .gm-ico { flex: 0 0 auto; vertical-align: middle; }

  /* Franja de marca: los dos colores del negocio, sin texto encima. */
  .gm-franja { display: flex; height: 1.6mm; margin-bottom: 4mm; }
  .gm-franja i { display: block; height: 100%; }

  .gm-top { display: flex; align-items: stretch; gap: 4mm; margin-bottom: 3mm; }
  .gm-top .izq { flex: 1; min-width: 0; }

  .gm-cab { display: flex; align-items: center; gap: 4mm; margin-bottom: 3mm; }
  .gm-cab .marca { flex: 0 0 38mm; }
  .gm-cab .marca img { max-width: 100%; max-height: 16mm; }
  .gm-cab .emisor { flex: 1; text-align: center; min-width: 0; }
  .gm-cab .razon { font-weight: 700; font-size: 11.5pt; color: var(--tin); line-height: 1.2; }
  .gm-cab .dir { font-size: 7pt; color: var(--mut); margin-top: 0.8mm; }

  .gm-fiscal { flex: 0 0 54mm; border: 0.5px solid var(--bor); border-radius: 1.6mm;
               padding: 2.5mm; text-align: center; }
  .gm-fiscal .den { font-weight: 700; font-size: 8.6pt; color: var(--tin);
                    line-height: 1.3; padding-bottom: 2mm;
                    border-bottom: 0.5px solid var(--bor); }
  .gm-fiscal .ruc { display: flex; justify-content: space-between; align-items: baseline;
                    font-size: 8pt; padding: 2mm 0; border-bottom: 0.5px solid var(--bor); }
  .gm-fiscal .ruc b { color: var(--tin); }
  .gm-fiscal .nro { font-weight: 700; font-size: 13pt; color: var(--tin);
                    padding: 2.5mm 0; border-bottom: 0.5px solid var(--bor); }
  .gm-fiscal .est { display: flex; align-items: center; justify-content: center;
                    gap: 1.5mm; padding-top: 2.5mm; font-size: 7.6pt; font-weight: 700; }

  .gm-caja { border: 0.5px solid var(--bor); border-radius: 1.6mm; overflow: hidden; }
  .gm-datos { background: var(--sua); padding: 2.5mm 3mm; }
  .gm-datos table { width: 100%; border-collapse: collapse; }
  .gm-datos td { padding: 0.7mm 0; vertical-align: top; }
  .gm-datos .et { width: 26mm; font-weight: 700; font-size: 7.2pt; color: var(--tin); }
  .gm-datos .et2 { width: 32mm; font-weight: 700; font-size: 7.2pt; color: var(--tin);
                   white-space: nowrap; padding-left: 3mm; }

  /* Ruta: el icono ancla la lectura y distingue partida de llegada de un vistazo. */
  .gm-ruta { margin: 3mm 0; }
  .gm-ruta .par { display: flex; align-items: stretch; border: 0.5px solid var(--bor);
                  border-radius: 1.6mm; margin-bottom: 2mm; }
  .gm-ruta .rot { flex: 0 0 42mm; display: flex; align-items: center; gap: 2mm;
                  padding: 2.2mm 3mm; font-weight: 700; font-size: 7.4pt;
                  color: var(--tin); border-right: 0.5px solid var(--bor); }
  .gm-ruta .val { flex: 1; padding: 2.2mm 3mm; min-width: 0; }

  .gm-items { width: 100%; border-collapse: collapse; }
  .gm-items th { background: var(--sua); font-weight: 700; font-size: 7.4pt;
                 color: var(--tin); padding: 2mm 2.5mm; text-align: center;
                 border-bottom: 0.5px solid var(--bor); }
  .gm-items td { padding: 2mm 2.5mm; border-bottom: 0.5px solid var(--bor);
                 vertical-align: top; }
  .gm-items tr:last-child td { border-bottom: 0; }
  .gm-aviso { padding: 2.2mm 3mm; font-size: 7.4pt; color: var(--mut); }

  .gm-sec .tit { background: var(--sua); font-weight: 700; font-size: 7.6pt;
                 color: var(--tin); padding: 2mm 3mm; border-bottom: 0.5px solid var(--bor); }
  .gm-sec table { width: 100%; border-collapse: collapse; }
  .gm-sec td { padding: 1.8mm 3mm; border-bottom: 0.5px solid var(--bor); font-size: 7.6pt; }
  .gm-sec tr:last-child td { border-bottom: 0; }
  .gm-sec .et { color: var(--mut); width: 42%; }
  .gm-cols { display: flex; gap: 3mm; margin-top: 3mm; align-items: flex-start; }
  .gm-cols > div { flex: 1; min-width: 0; }
  .gm-nota { padding: 1.8mm 3mm; font-size: 7pt; color: var(--mut); line-height: 1.5;
             border-top: 0.5px solid var(--bor); }

  /* Cifras de carga: es lo que revisan en carretera, va en grande. */
  .gm-cifras { display: flex; gap: 3mm; margin-top: 3mm; }
  .gm-cifras > div { flex: 1; display: flex; align-items: center; gap: 2.5mm;
                     border: 0.5px solid var(--bor); border-radius: 1.6mm; padding: 2.5mm 3mm; }
  .gm-cifras .rot { font-weight: 700; font-size: 7.2pt; color: var(--tin); }
  .gm-cifras .val { font-weight: 700; font-size: 12pt; color: var(--tin); margin-left: auto; }

  .gm-val { display: flex; align-items: center; gap: 4mm; margin-top: 5mm; }
  .gm-val .qr { flex: 0 0 26mm; }
  .gm-val .qr img, .gm-val .qr [data-qr] { width: 26mm; height: 26mm; display: block; }
  .gm-val .txt { font-size: 7.2pt; color: var(--mut); line-height: 1.6; }
</style>

<div class="gm">

  <div class="gm-franja">
    <i style="flex:3; background: {{ $tinta }};"></i>
    <i style="flex:1; background: {{ $acento }};"></i>
  </div>

  <div class="gm-top">
    <div class="izq">

      <div class="gm-cab">
        <div class="marca">
          @if($logoEmisor)<img src="{{ $logoEmisor }}" alt="{{ $project->name }}">@endif
        </div>
        <div class="emisor">
          <div class="razon">{{ $guia->emisor_razon_social ?: $project->name }}</div>
          @if($dir = ($project->setting('contact_address') ?: $project->address))
          <div class="dir">{{ mb_strtoupper((string) $dir) }}</div>
          @endif
          @if($project->phone)<div class="dir">Teléfono: {{ $project->phone }}</div>@endif
          @if($correo = $project->setting('contact_email'))<div class="dir">{{ $correo }}</div>@endif
        </div>
      </div>

      <div class="gm-caja gm-datos">
        <table>
          <tr>
            <td class="et">Fecha:</td>
            <td>{{ $guia->created_at?->format('d-M-Y') }}</td>
            <td class="et2">Fecha Traslado:</td>
            <td>{{ $guia->fecha_traslado?->format('d-M-Y') }}</td>
          </tr>
          <tr>
            <td class="et">Sr(es):</td>
            <td colspan="3">{{ $guia->destinatario_nombre }}</td>
          </tr>
          <tr>
            <td class="et">{{ $guia->destinatario_doc_tipo ?: 'RUC' }}:</td>
            <td>{{ $guia->destinatario_doc_numero ?: '—' }}</td>
            <td class="et2">Modalidad Transporte:</td>
            <td>{{ $modalidad }}</td>
          </tr>
          <tr>
            <td class="et">Doc. Referencia:</td>
            <td colspan="3">{{ $guia->invoice?->numero ?: '—' }}</td>
          </tr>
          <tr>
            <td class="et">Motivo Traslado:</td>
            <td colspan="3">{{ mb_strtoupper($motivoTexto) }}</td>
          </tr>
        </table>
      </div>

    </div>

    <div class="gm-fiscal">
      {{-- La denominacion oficial de SUNAT es "GUIA DE REMISION ELECTRONICA
           REMITENTE", en ese orden: aqui salia "GUIA DE REMISION / REMITENTE
           ELECTRONICA", que ademas partia la frase en dos con el salto. --}}
      <div class="den">GUÍA DE REMISIÓN ELECTRÓNICA<br>REMITENTE</div>
      <div class="ruc"><b>RUC:</b> <span>{{ $guia->emisor_ruc }}</span></div>
      <div class="nro">Nro. {{ $guia->numero }}</div>
      @if(in_array($estado, ['ANULADA', 'RECHAZADA POR SUNAT'], true))
      <div class="est" style="color: {{ $acento }};">
        {!! $ico($icoAlerta, 3.8) !!}
        <span>{{ $estado }}</span>
      </div>
      @endif
    </div>
  </div>

  @if($hayRuta)
  <div class="gm-ruta">
    <div class="par">
      <div class="rot">{!! $ico($icoPin, 4, $acento) !!} PUNTO DE PARTIDA</div>
      <div class="val">{{ $guia->partida_direccion ?: '—' }}</div>
    </div>
    <div class="par">
      <div class="rot">{!! $ico($icoPin, 4, $acento) !!} PUNTO DE LLEGADA</div>
      <div class="val">{{ $guia->llegada_direccion ?: '—' }}</div>
    </div>
  </div>
  @endif

  <div class="gm-caja">
    <table class="gm-items">
      <thead>
        <tr>
          <th style="width:9%">Ítem</th>
          <th style="width:22%">Código</th>
          <th style="text-align:left;">Descripción</th>
          <th style="width:11%">UM</th>
          <th style="width:14%">Cantidad</th>
        </tr>
      </thead>
      <tbody>
        @foreach($guia->items as $i => $item)
        <tr>
          <td class="cen">{{ $i + 1 }}</td>
          <td class="cen">{{ $item->codigo ?: ($item->product?->sku ?? '') }}</td>
          <td>{{ $item->description }}</td>
          <td class="cen">{{ $item->unit ?: 'NIU' }}</td>
          <td class="der">{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="gm-caja" style="margin-top:2mm;">
    <div class="gm-aviso">
      UNA VEZ SALIDA LA MERCADERÍA NO HAY DERECHO A RECLAMO, LA MERCADERÍA VIAJA POR CUENTA DEL CLIENTE
    </div>
    {{-- Una guia NO es una factura: sin decirlo, el cliente que recibe la
         mercaderia con este papel cree tener su comprobante y no reclama la
         factura, y en una fiscalizacion nadie puede acreditar la venta. --}}
    <div class="gm-aviso" style="border-top:0.5px solid var(--bor);">
      Este documento sustenta el traslado de los bienes; no acredita la venta ni otorga crédito fiscal.
    </div>
  </div>

  <div class="gm-caja gm-sec" style="margin-top:3mm;">
    <div class="tit">TRANSPORTISTA</div>
    <table>
      <tr>
        <td class="et">RAZÓN SOCIAL:</td>
        <td colspan="3">{{ $esPublico ? $guia->transportista_razon_social : '' }}</td>
      </tr>
      <tr>
        <td class="et">RUC:</td>
        <td style="width:28%">{{ $esPublico ? $guia->transportista_ruc : '' }}</td>
        <td class="et" style="width:12%">MTC:</td>
        <td>{{ $guia->transportista_mtc }}</td>
      </tr>
    </table>
  </div>

  <div class="gm-cols">
    <div class="gm-caja gm-sec">
      <div class="tit">DATOS DEL TRANSPORTE</div>
      <table>
        <tr><td class="et">APELLIDOS Y NOMBRES:</td>
            <td>{{ $exentoM1L ? '' : trim(($guia->conductor_nombres ?? '').' '.($guia->conductor_apellidos ?? '')) }}</td></tr>
        <tr><td class="et">LICENCIA DE CONDUCIR:</td>
            <td>{{ $exentoM1L ? '' : $guia->conductor_licencia }}</td></tr>
        <tr><td class="et">NÚMERO DE PLACA:</td>
            <td>{{ $exentoM1L ? '' : $guia->placaNormalizada() }}</td></tr>
      </table>
      @if($exentoM1L)
      <div class="gm-nota">
        Traslado en vehículo de categoría M1 o L: exento de declarar placa,
        conductor y licencia.
      </div>
      @endif
    </div>
    <div class="gm-caja gm-sec">
      <div class="tit">CONFORMIDAD DEL CLIENTE</div>
      <table>
        <tr><td class="et">DNI:</td><td></td></tr>
        <tr><td class="et">NOMBRES:</td><td></td></tr>
        <tr><td class="et">FECHA:</td><td></td></tr>
        <tr><td class="et">FIRMA:</td><td></td></tr>
      </table>
    </div>
  </div>

  <div class="gm-cifras">
    <div>
      {!! $ico($icoCaja, 5, $acento) !!}
      <span class="rot">TOTAL BULTOS:</span>
      <span class="val">{{ $guia->bultos ?: '—' }}</span>
    </div>
    <div>
      {!! $ico($icoPeso, 5, $acento) !!}
      <span class="rot">PESO TOTAL ({{ $guia->peso_unidad ?: 'KGM' }}):</span>
      <span class="val">{{ $peso }}</span>
    </div>
    <div>
      {!! $ico($icoCandado, 5, $acento) !!}
      <span class="rot">PRECINTOS:</span>
      <span class="val"></span>
    </div>
  </div>

  <div class="gm-val">
    <div class="qr">
      {{-- QR local: no se manda el RUC ni el destino de la carga a un
           servicio externo, y sale aunque el equipo este sin internet. --}}
      <div data-qr="{{ $qrDatos }}" role="img" aria-label="QR de la guía"></div>
    </div>
    {!! $ico($icoDoc, 9, '#b9c4d2') !!}
    <div class="txt">
      Representación impresa<br>de la guía de remisión remitente.<br>
      @if(in_array($estado, ['ANULADA', 'BAJA EN TRÁMITE', 'RECHAZADA POR SUNAT', 'ACEPTADA CON OBSERVACIONES'], true))
      <b style="color: {{ $acento }};">{{ $estado }}</b> —
      {{ $estadoNota }}
      @endif
      @if(! empty($guia->sunat_observaciones))
      <br>{{ is_array($guia->sunat_observaciones) ? implode(' · ', $guia->sunat_observaciones) : $guia->sunat_observaciones }}
      @endif
    </div>
  </div>

  <div class="gm-franja" style="margin: 5mm 0 0;">
    <i style="flex:1; background: {{ $acento }};"></i>
    <i style="flex:3; background: {{ $tinta }};"></i>
  </div>

</div>
{{-- El mismo dibujante de QR que usa el comprobante: incrustado para
     que la guia salga con su QR aunque no haya red. --}}
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
