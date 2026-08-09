{{-- LOGOS DE METODOS DE PAGO — vectoriales, con los colores de cada marca.
     Se dibujan sobre placa blanca para que se lean en pies claros y oscuros.
     Si llega un metodo desconocido se muestra su nombre en la misma placa. --}}
@php
    $payMarks = [
        'visa' => '<svg viewBox="0 0 54 16" height="16" aria-hidden="true"><rect width="54" height="16" rx="3" fill="#1A1F71"/><text x="27" y="11.8" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="10.5" font-weight="bold" font-style="italic" letter-spacing="1" fill="#fff">VISA</text></svg>',
        'mastercard' => '<svg viewBox="0 0 40 24" height="16" aria-hidden="true"><circle cx="15" cy="12" r="11" fill="#EB001B"/><circle cx="25" cy="12" r="11" fill="#F79E1B"/><path fill="#FF5F00" d="M20 3.4a11 11 0 0 0 0 17.2 11 11 0 0 0 0-17.2Z"/></svg>',
        'yape' => '<svg viewBox="0 0 54 16" height="16" aria-hidden="true"><rect width="54" height="16" rx="3" fill="#742284"/><text x="27" y="11.6" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="10" font-weight="bold" fill="#fff">Yape</text></svg>',
        'plin' => '<svg viewBox="0 0 54 16" height="16" aria-hidden="true"><rect width="54" height="16" rx="3" fill="#0DBEC7"/><text x="27" y="11.6" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="10" font-weight="bold" fill="#fff">plin</text></svg>',
    ];
    $payList = $pay ?? [];
    // Logotipos oficiales subidos a public/img/pagos. Si alguno falta, se usa el
    // vectorial de respaldo definido arriba, para que la fila nunca quede coja.
    $payFile = static function (string $k) {
        $ruta = public_path('img/pagos/'.$k.'.png');
        if (! is_file($ruta)) {
            return null;
        }
        // Los logotipos oficiales no comparten proporcion (Visa es alargado, Yape
        // y Plin son cuadrados): los cuadrados se muestran algo mas altos para
        // que todos pesen visualmente lo mismo en la fila.
        [$w, $h] = @getimagesize($ruta) ?: [1, 1];
        // Tres familias segun la proporcion: rotulo ancho (Visa), simbolo
        // compacto (Mastercard) y cuadrado (Yape, Plin). Cada una necesita un
        // alto distinto para que todas pesen igual en la fila.
        $ratio = $h > 0 ? $w / $h : 1;
        return [
            'url'  => asset('img/pagos/'.$k.'.png').'?v='.substr((string) filemtime($ruta), -6),
            'tipo' => $ratio < 1.2 ? 'sq' : ($ratio < 2.6 ? 'md' : 'wide'),
        ];
    };
@endphp
@if(!empty($payList))
<div class="pay-marks">
    @foreach($payList as $pk => $pl)
    @php $pkl = strtolower((string) $pk); $pimg = $payFile($pkl); @endphp
    <span title="{{ is_string($pl) ? $pl : ucfirst($pk) }}">
        @if($pimg)<img class="is-{{ $pimg['tipo'] }}" src="{{ $pimg['url'] }}" alt="{{ is_string($pl) ? $pl : ucfirst($pk) }}" loading="lazy" decoding="async">
        @else{!! $payMarks[$pkl] ?? e(is_string($pl) ? $pl : strtoupper($pk)) !!}@endif
    </span>
    @endforeach
</div>
@endif
