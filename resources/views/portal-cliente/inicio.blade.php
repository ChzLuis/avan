<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>{{ $cliente->name }} · {{ $project->name }}</title>
@php
    $primario = $project->setting('primary_color', '#2563eb');
    $logo = $project->setting('logo_url');
    $logo = $logo ? (str_starts_with($logo, 'http') ? $logo : asset('storage/'.ltrim($logo, '/'))) : null;
    $wa = preg_replace('/\D/', '', (string) $project->phone);
@endphp
<style>
  * { box-sizing: border-box; margin: 0; }
  body { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #f4f6f8; color: #1a2733; }
  .hoja { max-width: 640px; margin: 0 auto; padding: 20px 16px 60px; }
  .cab { display: flex; align-items: center; gap: 12px; padding: 18px 0; }
  .cab img { height: 44px; width: auto; object-fit: contain; }
  .cab h1 { font-size: 17px; }
  .cab p { font-size: 12.5px; color: #64748b; }
  .saludo { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; margin-bottom: 14px; }
  .saludo h2 { font-size: 19px; margin-bottom: 4px; }
  .saludo p { font-size: 13.5px; color: #475569; line-height: 1.55; }
  .ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; border-radius: 12px; padding: 14px 16px; font-size: 14px; font-weight: 600; margin-bottom: 14px; }
  .pedido { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; margin-bottom: 12px; }
  .pedido-cab { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; margin-bottom: 8px; }
  .pedido-cab b { font-size: 14.5px; }
  .pedido-cab span { font-size: 12px; color: #94a3b8; }
  .linea { display: flex; justify-content: space-between; gap: 10px; font-size: 13px; color: #475569; padding: 3px 0; }
  .linea span:last-child { white-space: nowrap; color: #94a3b8; }
  .repetir { display: block; width: 100%; margin-top: 12px; padding: 13px; border: 0; border-radius: 10px;
             background: {{ $primario }}; color: #fff; font-size: 14.5px; font-weight: 700; cursor: pointer; }
  .repetir:active { filter: brightness(.92); }
  .vacio { text-align: center; color: #94a3b8; font-size: 14px; padding: 40px 0; }
  .wa { display: inline-flex; align-items: center; gap: 8px; margin-top: 16px; padding: 12px 18px; border-radius: 10px;
        background: #16a34a; color: #fff; text-decoration: none; font-size: 14px; font-weight: 700; }
  .pie { margin-top: 30px; text-align: center; font-size: 11.5px; color: #94a3b8; }
</style>
</head>
<body>
<div class="hoja">

  <div class="cab">
    @if($logo)<img src="{{ $logo }}" alt="">@endif
    <div>
      <h1>{{ $project->name }}</h1>
      <p>Tu portal de pedidos</p>
    </div>
  </div>

  @if(session('portal_ok'))
  <div class="ok">✓ {{ session('portal_ok') }}</div>
  @endif

  <div class="saludo">
    <h2>Hola, {{ $cliente->name }}</h2>
    <p>
      Aquí están tus últimos pedidos. Toca <strong>Pedir de nuevo</strong> y
      {{ $modo === 'fijos'
          ? 'tu pedido entra al instante con los precios vigentes; te lo confirmamos en breve.'
          : 'te preparamos la cotización con los precios del día para que la apruebes.' }}
    </p>
  </div>

  @forelse($pedidos as $p)
  <div class="pedido">
    <div class="pedido-cab">
      <b>Pedido #{{ $p->id }}</b>
      <span>{{ $p->created_at->format('d/m/Y') }}</span>
    </div>
    @foreach($p->items->take(6) as $item)
    <div class="linea">
      <span>{{ $item->quantity }} × {{ $item->name }}</span>
      <span>S/ {{ number_format($item->price * $item->quantity, 2) }}</span>
    </div>
    @endforeach
    @if($p->items->count() > 6)
    <div class="linea"><span>… y {{ $p->items->count() - 6 }} línea(s) más</span><span></span></div>
    @endif
    <form method="POST" action="{{ route('portal.cliente.repetir', [$cliente->portal_token, $p->id]) }}">
      @csrf
      <button class="repetir" type="submit">
        {{ $modo === 'fijos' ? 'Pedir de nuevo' : 'Cotizar de nuevo' }}
      </button>
    </form>
  </div>
  @empty
  <p class="vacio">Todavía no tienes pedidos registrados.<br>Escríbenos y hacemos el primero.</p>
  @endforelse

  @if($wa)
  <div style="text-align:center">
    <a class="wa" href="https://wa.me/51{{ $wa }}?text={{ rawurlencode('Hola '.$project->name.', soy '.$cliente->name.'.') }}">
      Escribir por WhatsApp
    </a>
  </div>
  @endif

  <p class="pie">Enlace personal de {{ $cliente->name }} — no lo compartas.<br>{{ $project->name }} · BIXO® by Eskala</p>
</div>
</body>
</html>
