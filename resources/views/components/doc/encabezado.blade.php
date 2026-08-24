{{--
    El encabezado de un documento: el emisor a la izquierda con su logo en
    protagonista, y a la derecha la tarjeta del comprobante (el slot).
--}}
@props(['project', 'nombre', 'detalle' => ''])

@php
    $logo = $project->setting('logo_url') ?: ($project->logo_url ?? null);
    $logo = $logo ? (str_starts_with($logo, 'http') ? $logo : asset('storage/'.ltrim($logo, '/'))) : null;
@endphp
<div class="enc">
  <div class="enc-emisor">
    @if($logo)<img class="enc-logo" src="{{ $logo }}" alt="">@endif
    <div>
      <div class="enc-nombre">{{ $nombre }}</div>
      @if(trim($detalle) !== '')
      <div class="enc-detalle">{!! nl2br(e($detalle)) !!}</div>
      @endif
    </div>
  </div>
  {{ $slot }}
</div>
