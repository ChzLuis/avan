<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Etiqueta {{ $order->tag_code }}</title>
<style>
  @page { size: 80mm auto; margin: 0; }
  * { box-sizing: border-box; }
  body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; color: #111; }
  .tag { width: 80mm; padding: 6mm 5mm; }
  .center { text-align: center; }
  .biz { font-size: 15px; font-weight: 800; letter-spacing: .3px; }
  .sub { font-size: 10px; color: #555; margin-top: 1px; }
  .code { font-size: 30px; font-weight: 900; letter-spacing: 1px; margin: 6px 0 2px; }
  .qr { margin: 4px auto 6px; width: 40mm; height: 40mm; }
  .qr svg { width: 100%; height: 100%; }
  .row { display: flex; justify-content: space-between; font-size: 12px; padding: 2px 0; }
  .row b { font-weight: 700; }
  .pieces { font-size: 13px; font-weight: 800; text-align: center; border: 2px solid #111;
            border-radius: 8px; padding: 5px; margin: 6px 0; }
  hr { border: none; border-top: 1px dashed #999; margin: 6px 0; }
  .items { font-size: 11px; }
  .items div { display: flex; justify-content: space-between; padding: 1px 0; }
  .foot { font-size: 9px; color: #777; text-align: center; margin-top: 6px; }
  @media print { .noprint { display: none; } }
  .noprint { text-align: center; margin: 12px; }
  .btn { background: #06b6d4; color: #fff; border: none; padding: 10px 22px;
         border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; }
</style>
</head>
<body onload="window.print()">
  <div class="tag">
    <div class="center biz">{{ $project->name }}</div>
    <div class="center sub">🧺 Orden de servicio · Lavandería</div>

    <div class="center code">{{ $order->tag_code }}</div>
    <div class="qr"><img src="{{ $qrUrl }}" alt="{{ $order->tag_code }}" style="width:100%;height:100%" onerror="this.style.display='none'"></div>

    <div class="pieces">👕 {{ $order->pieces_count ?? $order->items->sum('quantity') }} prenda(s)</div>

    <div class="row"><span>Cliente</span><b>{{ $order->client_name }}</b></div>
    @if($order->client_phone)
    <div class="row"><span>Teléfono</span><b>{{ $order->client_phone }}</b></div>
    @endif
    <div class="row"><span>Fecha</span><b>{{ $order->created_at->format('d/m/Y H:i') }}</b></div>

    <hr>
    <div class="items">
      @foreach($order->items as $it)
      <div><span>{{ $it->quantity }}× {{ $it->name }}</span></div>
      @endforeach
    </div>
    <hr>
    <div class="row"><span>Total</span><b>{{ $project->setting('currency_symbol','S/') }} {{ number_format($order->total,2) }}</b></div>

    @if($order->notes)
    <div class="items" style="margin-top:4px"><b>Nota:</b> {{ $order->notes }}</div>
    @endif

    <div class="foot">Conserve este ticket para recoger su ropa · {{ now()->format('Y') }}</div>
  </div>

  <div class="noprint">
    <button class="btn" onclick="window.print()">🖨️ Imprimir etiqueta</button>
  </div>
</body>
</html>
