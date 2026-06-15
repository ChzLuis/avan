<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $invoice->numero ?? 'Comprobante' }} — {{ $project->name }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }
  .page { max-width: 700px; margin: 0 auto; padding: 36px 40px; }

  /* ── HEADER ── */
  .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
  .emisor-name { font-size: 22px; font-weight: 800; color: #111; letter-spacing: -.3px; }
  .emisor-detail { font-size: 10.5px; color: #6b7280; margin-top: 3px; line-height: 1.6; }

  .doc-badge { text-align: right; }
  .doc-tipo { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; }
  .doc-numero { font-size: 24px; font-weight: 800; color: #111; margin-top: 2px; letter-spacing: -.5px; }
  .doc-fecha { font-size: 10px; color: #9ca3af; margin-top: 3px; }

  /* status pill */
  .pill { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; margin-top: 6px; }
  .pill-draft      { background:#fef3c7; color:#92400e; }
  .pill-issued     { background:#dbeafe; color:#1d4ed8; }
  .pill-sent       { background:#d1fae5; color:#065f46; }
  .pill-cancelled  { background:#fee2e2; color:#991b1b; }

  /* ── DIVIDER ── */
  .divider { border: none; border-top: 1px solid #e5e7eb; margin-bottom: 20px; }
  .divider-accent { border: none; border-top: 3px solid #111; margin-bottom: 20px; }

  /* ── RECEPTOR ── */
  .receptor-block { background: #f9fafb; border-radius: 8px; padding: 14px 16px; margin-bottom: 24px; }
  .receptor-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #9ca3af; margin-bottom: 6px; }
  .receptor-name { font-size: 14px; font-weight: 700; color: #111; margin-bottom: 4px; }
  .receptor-detail { font-size: 10.5px; color: #4b5563; line-height: 1.7; }

  /* ── TABLE ── */
  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  thead tr { border-bottom: 2px solid #111; }
  thead th { padding: 8px 10px; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; text-align: left; background: transparent; }
  thead th:not(:first-child) { text-align: right; }
  tbody tr { border-bottom: 1px solid #f3f4f6; }
  tbody tr:last-child { border-bottom: 1px solid #e5e7eb; }
  tbody td { padding: 9px 10px; font-size: 11px; color: #374151; }
  tbody td:not(:first-child) { text-align: right; }
  tbody td.desc { font-weight: 500; color: #111; }

  /* ── BOTTOM ROW ── */
  .bottom-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; margin-bottom: 20px; }
  .payment-info { font-size: 10.5px; color: #6b7280; line-height: 1.8; flex: 1; }
  .payment-info strong { color: #374151; font-weight: 600; }

  /* ── TOTALS ── */
  .totals-block { min-width: 210px; }
  .totals-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 11px; color: #4b5563; }
  .totals-row:last-child { border-top: 2px solid #111; margin-top: 6px; padding-top: 10px; }
  .totals-row.grand span { font-size: 14px; font-weight: 800; color: #111; }

  /* ── NOTES ── */
  .notes { border-left: 3px solid #e5e7eb; padding: 8px 12px; margin-bottom: 16px; font-size: 10.5px; color: #6b7280; line-height: 1.6; }
  .notes strong { color: #374151; }

  /* ── SUNAT ── */
  .sunat-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; font-size: 10px; color: #065f46; line-height: 1.6; word-break: break-all; }

  /* ── FOOTER ── */
  .footer { border-top: 1px solid #f3f4f6; padding-top: 14px; display: flex; justify-content: space-between; align-items: flex-end; }
  .footer-left { font-size: 10px; color: #9ca3af; line-height: 1.6; }
  .footer-brand { font-size: 10px; color: #d1d5db; font-weight: 600; letter-spacing: .3px; }
  .footer-sub { font-size: 9px; color: #d1d5db; font-style: italic; margin-top: 2px; }

  /* ── PRINT ── */
  @media print {
    body { font-size: 10.5px; }
    .no-print { display: none !important; }
    @page { margin: 14mm 12mm; size: A4; }
  }
</style>
</head>
<body>
<div class="page">

  {{-- Botones --}}
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

  {{-- Header --}}
  <div class="header">
    <div>
      <div class="emisor-name">{{ $invoice->emisor_razon_social ?: $project->name }}</div>
      <div class="emisor-detail">
        @if($invoice->emisor_ruc)RUC: {{ $invoice->emisor_ruc }}<br>@endif
        @if($invoice->emisor_direccion){{ $invoice->emisor_direccion }}@endif
      </div>
    </div>
    <div class="doc-badge">
      <div class="doc-tipo">{{ $invoice->getTypeLabel() }}</div>
      <div class="doc-numero">{{ $invoice->numero }}</div>
      @if($invoice->issue_date)
      <div class="doc-fecha">{{ $invoice->issue_date->format('d/m/Y') }}</div>
      @endif
      <div>
        <span class="pill pill-{{ $invoice->status }}">{{ $invoice->getStatusLabel() }}</span>
      </div>
    </div>
  </div>

  <hr class="divider-accent">

  {{-- Receptor --}}
  <div class="receptor-block">
    <div class="receptor-label">Receptor / Cliente</div>
    <div class="receptor-name">{{ $invoice->client_name }}</div>
    <div class="receptor-detail">
      @if($invoice->client_doc_type){{ $invoice->client_doc_type }}: {{ $invoice->client_doc_number }}<br>@endif
      @if($invoice->client_address){{ $invoice->client_address }}<br>@endif
      @if($invoice->client_phone)Tel: {{ $invoice->client_phone }}<br>@endif
      @if($invoice->client_email){{ $invoice->client_email }}@endif
    </div>
  </div>

  {{-- Items --}}
  <table>
    <thead>
      <tr>
        <th style="width:42%">Descripción</th>
        <th style="width:9%">Unid.</th>
        <th style="width:9%">Cant.</th>
        <th style="width:14%">P. Unit.</th>
        <th style="width:12%">IGV</th>
        <th style="width:14%">Total</th>
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
        <td style="font-weight:700;color:#111;">{{ number_format($item->total, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Bottom: pago + totales --}}
  <div class="bottom-row">
    <div class="payment-info">
      @if($invoice->payment_method)
      <div><strong>Método de pago:</strong> {{ $invoice->payment_method }}</div>
      @endif
      @if($invoice->due_date)
      <div><strong>Vencimiento:</strong> {{ $invoice->due_date->format('d/m/Y') }}</div>
      @endif
      @if($invoice->currency !== 'PEN')
      <div><strong>Moneda:</strong> {{ $invoice->currency }}</div>
      @endif
    </div>

    <div class="totals-block">
      <div class="totals-row">
        <span>Subtotal (sin IGV)</span>
        <span>{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</span>
      </div>
      <div class="totals-row">
        <span>IGV 18%</span>
        <span>{{ $invoice->currency }} {{ number_format($invoice->igv, 2) }}</span>
      </div>
      <div class="totals-row grand">
        <span>TOTAL</span>
        <span>{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</span>
      </div>
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

  {{-- Footer --}}
  <div class="footer">
    <div class="footer-left">
      {{ $invoice->emisor_razon_social ?: $project->name }}
      @if($invoice->emisor_ruc) &nbsp;·&nbsp; RUC {{ $invoice->emisor_ruc }}@endif
    </div>
    <div style="text-align:right;">
      <div class="footer-brand">BIXO</div>
      <div class="footer-sub">Sistema de Gestión</div>
    </div>
  </div>

</div>
</body>
</html>
