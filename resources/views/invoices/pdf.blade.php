<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $invoice->numero ?? 'Comprobante' }} — {{ $project->name }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }
  .page { max-width: 680px; margin: 0 auto; padding: 32px; }
  .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1e1e2e; padding-bottom: 16px; margin-bottom: 16px; }
  .brand { font-size: 20px; font-weight: 700; color: #1e1e2e; }
  .brand-sub { font-size: 11px; color: #555; margin-top: 2px; }
  .doc-box { border: 2px solid #1e1e2e; border-radius: 6px; padding: 10px 20px; text-align: center; min-width: 160px; }
  .doc-box .tipo { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
  .doc-box .numero { font-size: 14px; font-weight: 700; color: #4338ca; margin-top: 2px; }
  .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
  .box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px; }
  .box-title { font-size: 9px; text-transform: uppercase; letter-spacing: .6px; color: #6b7280; font-weight: 700; margin-bottom: 6px; }
  .box p { font-size: 11px; color: #374151; line-height: 1.5; }
  .box .name { font-size: 13px; font-weight: 600; color: #111; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
  thead th { background: #1e1e2e; color: #fff; padding: 6px 8px; font-size: 10px; text-align: left; }
  thead th:not(:first-child) { text-align: right; }
  tbody tr:nth-child(even) { background: #f9fafb; }
  tbody td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
  tbody td:not(:first-child) { text-align: right; }
  .totals { margin-left: auto; width: 220px; }
  .totals table { margin-bottom: 0; }
  .totals td { border: none; padding: 3px 6px; }
  .totals .grand { font-size: 13px; font-weight: 700; border-top: 2px solid #1e1e2e; }
  .notes { border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px; margin-top: 16px; font-size: 11px; color: #555; }
  .footer { margin-top: 24px; text-align: center; font-size: 10px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 12px; }
  .sunat-box { margin-top: 12px; border: 1px dashed #d1fae5; background: #f0fdf4; border-radius: 4px; padding: 8px; font-size: 10px; color: #065f46; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; }
  .badge-draft { background:#fef3c7; color:#92400e; }
  .badge-issued { background:#dbeafe; color:#1d4ed8; }
  .badge-sent { background:#d1fae5; color:#065f46; }
  .badge-cancelled { background:#fee2e2; color:#991b1b; }
  @media print {
    body { font-size: 10px; }
    .no-print { display: none !important; }
    @page { margin: 12mm; size: A4; }
  }
</style>
</head>
<body>
<div class="page">

  {{-- Botones imprimir --}}
  <div class="no-print" style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:16px;">
    <button onclick="window.print()"
            style="background:#4338ca;color:#fff;border:none;padding:6px 16px;border-radius:6px;cursor:pointer;font-size:12px;">
      🖨 Imprimir
    </button>
    <button onclick="window.close()"
            style="background:#f3f4f6;color:#374151;border:1px solid #d1d5db;padding:6px 16px;border-radius:6px;cursor:pointer;font-size:12px;">
      Cerrar
    </button>
  </div>

  {{-- Cabecera --}}
  <div class="header">
    <div>
      <div class="brand">{{ $project->name }}</div>
      @if($invoice->emisor_ruc)
      <div class="brand-sub">RUC: {{ $invoice->emisor_ruc }}</div>
      @endif
      @if($invoice->emisor_direccion)
      <div class="brand-sub">{{ $invoice->emisor_direccion }}</div>
      @endif
    </div>
    <div class="doc-box">
      <div class="tipo">{{ $invoice->getTypeLabel() }}</div>
      <div class="numero">{{ $invoice->numero }}</div>
      <div style="margin-top:6px;">
        <span class="badge badge-{{ $invoice->status }}">{{ $invoice->getStatusLabel() }}</span>
      </div>
      @if($invoice->issue_date)
      <div style="font-size:10px;color:#6b7280;margin-top:4px;">{{ $invoice->issue_date->format('d/m/Y') }}</div>
      @endif
    </div>
  </div>

  {{-- Emisor / Receptor --}}
  <div class="two-col">
    <div class="box">
      <div class="box-title">Emisor</div>
      <p class="name">{{ $invoice->emisor_razon_social ?: $project->name }}</p>
      @if($invoice->emisor_ruc)<p>RUC: {{ $invoice->emisor_ruc }}</p>@endif
      @if($invoice->emisor_direccion)<p>{{ $invoice->emisor_direccion }}</p>@endif
    </div>
    <div class="box">
      <div class="box-title">Receptor</div>
      <p class="name">{{ $invoice->client_name }}</p>
      @if($invoice->client_doc_type)
      <p>{{ $invoice->client_doc_type }}: {{ $invoice->client_doc_number }}</p>
      @endif
      @if($invoice->client_address)<p>{{ $invoice->client_address }}</p>@endif
      @if($invoice->client_phone)<p>Tel: {{ $invoice->client_phone }}</p>@endif
      @if($invoice->client_email)<p>{{ $invoice->client_email }}</p>@endif
    </div>
  </div>

  {{-- Items --}}
  <table>
    <thead>
      <tr>
        <th style="width:40%">Descripción</th>
        <th style="width:10%">Unidad</th>
        <th style="width:10%">Cant.</th>
        <th style="width:15%">P. Unit.</th>
        <th style="width:12%">IGV</th>
        <th style="width:13%">Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($invoice->items as $item)
      <tr>
        <td>{{ $item->description }}</td>
        <td>{{ $item->unit }}</td>
        <td>{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</td>
        <td>{{ number_format($item->unit_price, 2) }}</td>
        <td>{{ number_format($item->igv_amount, 2) }}</td>
        <td style="font-weight:600;">{{ number_format($item->total, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Totales --}}
  <div style="display:flex;justify-content:space-between;align-items:flex-start;">
    {{-- Pago --}}
    <div style="font-size:11px;color:#555;">
      @if($invoice->payment_method)
      <p><span style="font-weight:600;">Método de pago:</span> {{ $invoice->payment_method }}</p>
      @endif
      @if($invoice->due_date)
      <p><span style="font-weight:600;">Vencimiento:</span> {{ $invoice->due_date->format('d/m/Y') }}</p>
      @endif
      @if($invoice->currency !== 'PEN')
      <p><span style="font-weight:600;">Moneda:</span> {{ $invoice->currency }}</p>
      @endif
    </div>
    <div class="totals">
      <table>
        <tr>
          <td>Subtotal (sin IGV)</td>
          <td style="text-align:right;">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        <tr>
          <td>IGV (18%)</td>
          <td style="text-align:right;">{{ $invoice->currency }} {{ number_format($invoice->igv, 2) }}</td>
        </tr>
        <tr class="grand">
          <td style="padding-top:6px;font-weight:700;">TOTAL</td>
          <td style="text-align:right;padding-top:6px;font-weight:700;">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</td>
        </tr>
      </table>
    </div>
  </div>

  @if($invoice->notes)
  <div class="notes"><strong>Observaciones:</strong> {{ $invoice->notes }}</div>
  @endif

  @if($invoice->sunat_hash)
  <div class="sunat-box">
    <strong>Hash CDR SUNAT:</strong> {{ $invoice->sunat_hash }}
  </div>
  @endif

  <div class="footer">
    <p>{{ $project->name }} &nbsp;|&nbsp; Comprobante generado por AVAN</p>
    @if(!$invoice->sunat_status)
    <p style="margin-top:2px;font-style:italic;">Este documento es una representación impresa — pendiente de envío a SUNAT</p>
    @endif
  </div>

</div>
</body>
</html>
