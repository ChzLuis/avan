@props(['etiquetas' => [], 'donde' => 'card'])
{{-- Etiquetas de producto.

     Un solo componente para las dos superficies: en la tarjeta van flotando
     sobre la foto (maximo dos, apiladas) y en la ficha en linea bajo el
     nombre. El color y el orden ya vienen resueltos por `EtiquetasProducto`;
     aqui solo se pinta.

     Sin etiquetas no se emite NADA: ni el contenedor, para que no quede un
     hueco reservado sobre la foto. --}}
@php
    $etqIzq = collect($etiquetas)->where('posicion', 'izquierda')->values();
    $etqDer = collect($etiquetas)->where('posicion', 'derecha')->values();
@endphp
@if(count($etiquetas))
@once
<style>
  /* La pila se ancla a la esquina de la foto. `pointer-events:none` en el
     contenedor para que la etiqueta no robe el clic que lleva al producto. */
  .pe-etqs{position:absolute;z-index:3;display:flex;flex-direction:column;gap:5px;pointer-events:none;max-width:calc(100% - 20px)}
  .pe-etqs--izquierda{top:10px;left:10px;align-items:flex-start}
  .pe-etqs--derecha{top:10px;right:10px;align-items:flex-end}
  .pe-etq{
    display:inline-flex;align-items:center;gap:4px;
    padding:4px 9px;border-radius:999px;
    font-size:11px;font-weight:600;line-height:1.45;letter-spacing:.01em;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;
    box-shadow:0 1px 2px rgba(15,23,42,.06);
  }
  /* En la ficha hay sitio: van en linea bajo el nombre y sin posicionar. */
  .pe-etqs--ficha{position:static;flex-direction:row;flex-wrap:wrap;gap:7px;margin:10px 0 0;max-width:none}
  .pe-etqs--ficha .pe-etq{font-size:12px;padding:5px 11px}
  /* En movil la foto es pequeña: la etiqueta encoge para no taparla. */
  @media (max-width:640px){
    .pe-etqs{gap:4px;max-width:calc(100% - 14px)}
    .pe-etqs--izquierda{top:7px;left:7px}
    .pe-etqs--derecha{top:7px;right:7px}
    .pe-etq{font-size:10px;padding:3px 7px}
  }
</style>
@endonce

@if($donde === 'ficha')
    <div class="pe-etqs pe-etqs--ficha">
        @foreach($etiquetas as $e)
            <span class="pe-etq" style="background:{{ $e['fondo'] }};color:{{ $e['texto_color'] }}">{{ $e['texto'] }}</span>
        @endforeach
    </div>
@else
    @foreach(['izquierda' => $etqIzq, 'derecha' => $etqDer] as $lado => $grupo)
        @if($grupo->isNotEmpty())
        <div class="pe-etqs pe-etqs--{{ $lado }}">
            @foreach($grupo as $e)
                <span class="pe-etq" style="background:{{ $e['fondo'] }};color:{{ $e['texto_color'] }}">{{ $e['texto'] }}</span>
            @endforeach
        </div>
        @endif
    @endforeach
@endif
@endif
