@php
    /* La representación impresa de la guía: es lo que viaja con la mercadería
       y lo que se muestra en un control de carretera. Sin esto, la guía
       existía en SUNAT pero el chofer no llevaba nada que enseñar. */
    $logoPdf = $project->setting('logo_url') ?: ($project->logo_url ?? null);
    $logoPdf = $logoPdf ? (str_starts_with($logoPdf, 'http') ? $logoPdf : asset('storage/'.ltrim($logoPdf, '/'))) : null;

    // El QR de la GRE apunta a la consulta del documento; sin CDR aún, lleva
    // los datos identificatorios, que es lo que revisa un control.
    $cdr = json_decode((string) $guia->sunat_cdr, true) ?: [];
    $qrDatos = $cdr['reference'] ?? implode('|', [
        $guia->emisor_ruc, '09', $guia->serie, $guia->correlativo,
        $guia->fecha_traslado?->format('Y-m-d'),
        $guia->destinatario_doc_tipo ?: '-', $guia->destinatario_doc_numero ?: '-',
    ]);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $guia->numero }} — Guía de remisión</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }
  .page { max-width: 700px; margin: 0 auto; padding: 36px 40px; }

  .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
  .emisor-wrap { display: flex; gap: 14px; align-items: flex-start; }
  .emisor-logo { max-height: 58px; max-width: 150px; object-fit: contain; }
  .emisor-name { font-size: 20px; font-weight: 800; color: #111; letter-spacing: -.3px; }
  .emisor-detail { font-size: 10.5px; color: #6b7280; margin-top: 3px; line-height: 1.6; }

  .doc-box { border: 2px solid #111; border-radius: 8px; padding: 10px 18px; text-align: center; min-width: 230px; }
  .doc-box-ruc { font-size: 11px; font-weight: 700; color: #374151; }
  .doc-box-tipo { font-size: 11.5px; font-weight: 800; color: #111; margin: 4px 0; letter-spacing: .3px; }
  .doc-box-num { font-size: 18px; font-weight: 800; color: #111; }

  .divider-accent { border: none; border-top: 3px solid #111; margin-bottom: 18px; }

  .grid2 { display: flex; gap: 14px; margin-bottom: 14px; }
  .bloque { flex: 1; background: #f9fafb; border-radius: 8px; padding: 12px 14px; }
  .bloque-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #9ca3af; margin-bottom: 6px; }
  .bloque-body { font-size: 10.5px; color: #374151; line-height: 1.8; }
  .bloque-body strong { color: #111; }

  table { width: 100%; border-collapse: collapse; margin: 16px 0 18px; }
  thead tr { border-bottom: 2px solid #111; }
  thead th { padding: 8px 10px; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; text-align: left; }
  thead th:not(:first-child) { text-align: right; }
  tbody tr { border-bottom: 1px solid #f3f4f6; }
  tbody td { padding: 9px 10px; font-size: 11px; color: #374151; }
  tbody td:not(:first-child) { text-align: right; }

  .legal-row { display: flex; gap: 16px; align-items: flex-start; margin: 18px 0 16px; }
  .legal-qr { width: 92px; height: 92px; flex-shrink: 0; }
  .legal-text { font-size: 9.5px; color: #6b7280; line-height: 1.7; }

  .footer { border-top: 1px solid #f3f4f6; padding-top: 14px; display: flex; justify-content: space-between; align-items: flex-end; }
  .footer-left { font-size: 10px; color: #9ca3af; }
  .footer-brand { font-size: 10px; color: #d1d5db; font-weight: 600; }

  @media print {
    .no-print { display: none !important; }
    @page { margin: 14mm 12mm; size: A4; }
  }
</style>
</head>
<body>
<div class="page">

  <div class="no-print" style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:20px;">
    <button onclick="window.print()"
            style="background:#111;color:#fff;border:none;padding:7px 18px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;">
      Imprimir / Guardar PDF
    </button>
    <button onclick="window.close()"
            style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;padding:7px 16px;border-radius:6px;cursor:pointer;font-size:12px;">
      Cerrar
    </button>
  </div>

  <div class="header">
    <div class="emisor-wrap">
      @if($logoPdf)<img class="emisor-logo" src="{{ $logoPdf }}" alt="">@endif
      <div>
        <div class="emisor-name">{{ $guia->emisor_razon_social ?: $project->name }}</div>
        <div class="emisor-detail">{{ $guia->partida_direccion }}</div>
      </div>
    </div>
    <div class="doc-box">
      <div class="doc-box-ruc">RUC {{ $guia->emisor_ruc }}</div>
      <div class="doc-box-tipo">GUÍA DE REMISIÓN ELECTRÓNICA<br>REMITENTE</div>
      <div class="doc-box-num">{{ $guia->numero }}</div>
    </div>
  </div>

  <hr class="divider-accent">

  <div class="grid2">
    <div class="bloque">
      <div class="bloque-label">Destinatario</div>
      <div class="bloque-body">
        <strong>{{ $guia->destinatario_nombre }}</strong><br>
        @if($guia->destinatario_doc_numero)
          {{ \App\Support\Sunat\Catalogos::DOCUMENTOS_IDENTIDAD[$guia->destinatario_doc_tipo] ?? 'Doc.' }}:
          {{ $guia->destinatario_doc_numero }}
        @endif
      </div>
    </div>
    <div class="bloque">
      <div class="bloque-label">Traslado</div>
      <div class="bloque-body">
        <strong>Motivo:</strong> {{ $guia->motivoLegible() }}<br>
        <strong>Fecha de traslado:</strong> {{ $guia->fecha_traslado?->format('d/m/Y') }}<br>
        <strong>Modalidad:</strong> {{ $guia->esPublico() ? 'Transporte público' : 'Transporte privado' }}<br>
        <strong>Peso:</strong> {{ rtrim(rtrim(number_format((float) $guia->peso_total, 3, '.', ''), '0'), '.') }} {{ $guia->peso_unidad }}
        @if($guia->bultos) &nbsp;·&nbsp; <strong>Bultos:</strong> {{ $guia->bultos }}@endif
      </div>
    </div>
  </div>

  <div class="grid2">
    <div class="bloque">
      <div class="bloque-label">Punto de partida</div>
      <div class="bloque-body">{{ $guia->partida_direccion }}@if($guia->partida_ubigeo)<br>Ubigeo {{ $guia->partida_ubigeo }}@endif</div>
    </div>
    <div class="bloque">
      <div class="bloque-label">Punto de llegada</div>
      <div class="bloque-body">{{ $guia->llegada_direccion }}@if($guia->llegada_ubigeo)<br>Ubigeo {{ $guia->llegada_ubigeo }}@endif</div>
    </div>
  </div>

  <div class="grid2">
    <div class="bloque">
      @if($guia->esPublico())
      <div class="bloque-label">Transportista</div>
      <div class="bloque-body">
        <strong>{{ $guia->transportista_razon_social }}</strong><br>
        RUC: {{ $guia->transportista_ruc }}
        @if($guia->transportista_mtc)<br>Registro MTC: {{ $guia->transportista_mtc }}@endif
      </div>
      @else
      <div class="bloque-label">Vehículo y conductor</div>
      <div class="bloque-body">
        <strong>Placa:</strong> {{ strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $guia->vehiculo_placa)) }}<br>
        <strong>Conductor:</strong> {{ trim($guia->conductor_nombres.' '.$guia->conductor_apellidos) }}<br>
        Doc. {{ $guia->conductor_doc_numero }} &nbsp;·&nbsp; Licencia {{ $guia->conductor_licencia }}
      </div>
      @endif
    </div>
    @if($guia->invoice)
    <div class="bloque">
      <div class="bloque-label">Comprobante relacionado</div>
      <div class="bloque-body">
        <strong>{{ $guia->invoice->getTypeLabel() }} {{ $guia->invoice->numero }}</strong><br>
        {{ $guia->invoice->client_name }}
      </div>
    </div>
    @endif
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:14%">Código</th>
        <th style="width:56%; text-align:left">Descripción</th>
        <th style="width:15%">Unidad</th>
        <th style="width:15%">Cantidad</th>
      </tr>
    </thead>
    <tbody>
      @foreach($guia->items as $item)
      <tr>
        <td style="text-align:left">{{ $item->codigo ?: '—' }}</td>
        <td style="text-align:left; font-weight:500; color:#111;">{{ $item->description }}</td>
        <td>{{ $item->unit }}</td>
        <td style="font-weight:700; color:#111;">{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  @if($guia->observaciones)
  <div style="border-left:3px solid #e5e7eb; padding:8px 12px; margin-bottom:14px; font-size:10.5px; color:#6b7280;">
    <strong style="color:#374151;">Observaciones:</strong> {{ $guia->observaciones }}
  </div>
  @endif

  <div class="legal-row">
    <img class="legal-qr" alt="QR de la guía"
         src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&ecc=M&data={{ urlencode($qrDatos) }}">
    <div class="legal-text">
      Representación impresa de la GUÍA DE REMISIÓN ELECTRÓNICA — REMITENTE.<br>
      @if($guia->sunat_ticket)<strong>Ticket SUNAT:</strong> {{ $guia->sunat_ticket }}<br>@endif
      {{ $guia->estadoSunatLegible() }}. Este documento sustenta el traslado de los bienes;
      no acredita la venta ni otorga crédito fiscal.
    </div>
  </div>

  <div class="footer">
    <div class="footer-left">
      {{ $guia->emisor_razon_social ?: $project->name }} &nbsp;·&nbsp; RUC {{ $guia->emisor_ruc }}
    </div>
    <div style="text-align:right;">
      <div class="footer-brand">BIXO<span style="font-size:7px;vertical-align:super;">®</span> <span style="font-weight:400;color:#9ca3af;">by</span> Eskala</div>
      <div style="font-size:9px;color:#d1d5db;font-style:italic;margin-top:2px;">© {{ date('Y') }} Eskala Group · BIXO®</div>
    </div>
  </div>

</div>
</body>
</html>
