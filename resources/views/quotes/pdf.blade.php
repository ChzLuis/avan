<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
{{-- Este titulo es el nombre que el navegador propone al guardar el PDF. --}}
<title>{{ $quote->etiqueta }}{{ $quote->client_name ? ' - '.$quote->client_name : '' }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }
  .page { max-width: 700px; margin: 0 auto; padding: 36px 40px; }

  .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; margin-bottom: 28px; }
  .emisor-name { font-size: 22px; font-weight: 800; color: #111; letter-spacing: -.3px; }
  .emisor-detail { font-size: 10.5px; color: #6b7280; margin-top: 3px; line-height: 1.6; }

  .doc-badge { text-align: right; flex-shrink: 0; }
  .doc-tipo { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; }
  .doc-numero { font-size: 24px; font-weight: 800; color: #111; margin-top: 2px; letter-spacing: -.5px; }
  .doc-fecha { font-size: 10px; color: #9ca3af; margin-top: 3px; }

  .pill { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; margin-top: 6px; }
  .pill-draft     { background:#fef3c7; color:#92400e; }
  .pill-sent      { background:#dbeafe; color:#1d4ed8; }
  .pill-accepted  { background:#d1fae5; color:#065f46; }
  .pill-rejected  { background:#fee2e2; color:#991b1b; }
  .pill-converted { background:#e0e7ff; color:#3730a3; }
  .pill-legacy    { background:#f3f4f6; color:#4b5563; }

  .divider-accent { border: none; border-top: 3px solid #111; margin-bottom: 20px; }

  .receptor-block { background: #f9fafb; border-radius: 8px; padding: 14px 16px; margin-bottom: 24px; }
  .receptor-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #9ca3af; margin-bottom: 6px; }
  .receptor-name { font-size: 14px; font-weight: 700; color: #111; margin-bottom: 4px; }
  .receptor-detail { font-size: 10.5px; color: #4b5563; line-height: 1.7; }

  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  thead tr { border-bottom: 2px solid #111; }
  thead th { padding: 8px 10px; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; text-align: left; }
  thead th:not(:first-child) { text-align: right; }
  tbody tr { border-bottom: 1px solid #f3f4f6; }
  tbody tr:last-child { border-bottom: 1px solid #e5e7eb; }
  tbody td { padding: 9px 10px; font-size: 11px; color: #374151; }
  tbody td:not(:first-child) { text-align: right; }
  tbody td.desc { font-weight: 500; color: #111; }

  .bottom-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; margin-bottom: 20px; }
  .payment-info { font-size: 10.5px; color: #6b7280; line-height: 1.8; flex: 1; }
  .payment-info strong { color: #374151; font-weight: 600; }

  .totals-block { min-width: 210px; }
  .totals-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 11px; color: #4b5563; }
  .totals-row.grand { border-top: 2px solid #111; margin-top: 6px; padding-top: 10px; }
  .totals-row.grand span { font-size: 14px; font-weight: 800; color: #111; }

  .notes { border-left: 3px solid #e5e7eb; padding: 8px 12px; margin-bottom: 16px; font-size: 10.5px; color: #6b7280; line-height: 1.6; }
  .notes strong { color: #374151; }

  .legal { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; font-size: 10px; color: #6b7280; line-height: 1.6; }

  .footer { border-top: 1px solid #f3f4f6; padding-top: 14px; display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; }
  .footer-left { font-size: 10px; color: #9ca3af; line-height: 1.6; word-break: break-all; }
  .footer-brand { font-size: 10px; color: #d1d5db; font-weight: 600; letter-spacing: .3px; flex-shrink: 0; }

  @media print {
    body { font-size: 10.5px; }
    .no-print { display: none !important; }
    @page { margin: 14mm 12mm; size: A4; }
  }
</style>
</head>
<body>
@php
  // El importe de cada linea y el total salen de LineMath, la unica aritmetica
  // de dinero del sistema: centavos enteros y redondeo half-up. Recalcular aqui
  // "a mano" con floats es como se producen los descuadres de un centimo entre
  // la pantalla, el portal del cliente y este documento.
  $numero  = $quote->etiqueta;
  // Mismo texto que ve el cliente en su enlace: es el mismo documento.
  $estado  = \App\Support\QuoteStatus::clientePresentacion($quote->status);
  $vencida = \App\Support\QuoteStatus::vencida($quote->status, $quote->valid_until);
  $pill    = match (\App\Support\QuoteStatus::comercial($quote->status)) {
      'draft' => 'draft', 'sent' => 'sent', 'accepted' => 'accepted',
      'rejected' => 'rejected', 'converted' => 'converted', default => 'legacy',
  };
  $total   = \App\Support\LineMath::sum($quote->items);
@endphp
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
    <div>
      <div class="emisor-name">{{ $project->name }}</div>
      <div class="emisor-detail">
        @if($project->address){{ $project->address }}<br>@endif
        @if($project->phone){{ $project->phone }}@endif
      </div>
    </div>
    <div class="doc-badge">
      <div class="doc-tipo">Cotización</div>
      <div class="doc-numero">{{ $numero }}</div>
      <div class="doc-fecha">{{ $quote->created_at->format('d/m/Y') }}</div>
      <div><span class="pill pill-{{ $pill }}">{{ $estado['label'] }}</span></div>
    </div>
  </div>

  <hr class="divider-accent">

  <div class="receptor-block">
    <div class="receptor-label">Cliente</div>
    <div class="receptor-name">{{ $quote->client_name ?: 'Cliente no registrado' }}</div>
    <div class="receptor-detail">
      @if($quote->client_doc_number){{ $quote->client_doc_type ?: 'Doc' }}: {{ $quote->client_doc_number }}<br>@endif
      @if($quote->client_address){{ $quote->client_address }}<br>@endif
      @if($quote->client_phone)Tel: {{ $quote->client_phone }}<br>@endif
      @if($quote->client_email){{ $quote->client_email }}@endif
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:52%">Descripción</th>
        <th style="width:10%">Cant.</th>
        <th style="width:16%">P. Unit.</th>
        <th style="width:10%">Desc.</th>
        <th style="width:16%">Importe</th>
      </tr>
    </thead>
    <tbody>
      @foreach($quote->items as $item)
      <tr>
        <td class="desc">{{ $item->description }}</td>
        <td>{{ (int) $item->quantity }}</td>
        <td>{{ \App\Support\LineMath::present(\App\Support\LineMath::canon($item->price)) }}</td>
        <td>{{ (float) $item->discount > 0 ? rtrim(rtrim(number_format((float) $item->discount, 2), '0'), '.') . '%' : '—' }}</td>
        <td style="font-weight:700;color:#111;">
          {{ \App\Support\LineMath::present(\App\Support\LineMath::total($item->price, (int) $item->quantity, $item->discount ?? 0)) }}
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="bottom-row">
    <div class="payment-info">
      @if($quote->valid_until)
      <div><strong>Válida hasta:</strong> {{ $quote->valid_until->format('d/m/Y') }}@if($vencida) (vencida)@endif</div>
      @endif
      @if($quote->payment_condition)
      <div><strong>Condición:</strong> {{ $quote->payment_condition }}</div>
      @endif
      @if($quote->payment_method)
      <div><strong>Forma de pago:</strong> {{ $quote->payment_method }}</div>
      @endif
    </div>

    <div class="totals-block">
      <div class="totals-row grand">
        <span>Total</span>
        <span>S/ {{ \App\Support\LineMath::present($total) }}</span>
      </div>
    </div>
  </div>

  @if($quote->notes)
  <div class="notes"><strong>Observaciones:</strong> {{ $quote->notes }}</div>
  @endif

  {{-- Cotizaciones no guarda desglose de impuestos: declararlo evita que el
       cliente lea un IGV que el sistema nunca calculo. --}}
  <div class="legal">
    Este documento es una cotización comercial y no constituye comprobante de pago.
    Los importes mostrados son los pactados; el comprobante fiscal se emite al concretarse la venta.
  </div>

  <div class="footer">
    <div class="footer-left">
      @if($quote->token)Consulta en línea: {{ url('/b/' . $project->slug . '/c/' . $quote->token) }}@endif
    </div>
    <div class="footer-brand">BIXO by Eskala</div>
  </div>
</div>
</body>
</html>
