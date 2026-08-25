{{--
    La tarjeta del comprobante: RUC, denominación oficial, el número como
    protagonista, la fecha y el estado real como badge. Es la identidad del
    documento y va igual en toda la familia.
--}}
@props(['ruc' => null, 'numero', 'fecha' => null, 'badge' => null, 'tono' => 'gris'])

<div class="tarjeta">
  <div class="tarjeta-cinta"></div>
  <div class="tarjeta-cuerpo">
    <div class="tarjeta-tipo">{{ $slot }}</div>
    <div class="tarjeta-numero">{{ $numero }}</div>
    @if($ruc)
    <div class="tarjeta-ruc">RUC {{ $ruc }}</div>
    @endif
    @if($fecha)
    <div class="tarjeta-fecha">{{ $fecha }}</div>
    @endif
    @if($badge)
    <div><span class="badge badge-{{ $tono }}">{{ $badge }}</span></div>
    @endif
  </div>
</div>
