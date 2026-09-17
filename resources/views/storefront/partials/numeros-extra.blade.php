{{-- Números de contacto adicionales (Constructor → Negocio → Más números).
     Se pinta DESPUÉS del teléfono y el WhatsApp principales, así que aquí solo
     salen los extra: los principales ya los pinta cada pie a su manera.
     Recibe $settings y $project del pie que lo incluye; `$fpIco` es opcional
     porque no todos los pies tienen juego de iconos. --}}
@php
    $nxExtra = \App\Storefront\ContactosTienda::extra($settings ?? []);
@endphp
@foreach($nxExtra as $nx)
    @php
        $nxWa = $nx['t'] === 'whatsapp';
        $nxHref = $nxWa
            ? 'https://wa.me/'.\App\Storefront\ContactosTienda::waInternacional($nx['n'])
            : 'tel:'.preg_replace('/[^\d+]/', '', $nx['n']);
        $nxIcono = ($fpIco ?? [])[$nxWa ? 'chat' : 'phone'] ?? '';
    @endphp
    <li>
        @if($nxIcono)<svg viewBox="0 0 24 24">{!! $nxIcono !!}</svg>@endif
        <a href="{{ $nxHref }}" @if($nxWa) target="_blank" rel="noopener" @endif>{{ $nx['n'] }}@if($nx['l']) · {{ $nx['l'] }}@endif</a>
    </li>
@endforeach
