@php
    // Medidas reales en milimetros: la hoja se imprime, no se mira en pantalla,
    // asi que todo va en mm y no en pixeles. Si se usaran pixeles, el tamano
    // del codigo cambiaria con la resolucion de la impresora y dejaria de
    // encajar en la etiqueta adhesiva.
    $ancho = $formato['ancho'];
    $alto = $formato['alto'];
    $cols = $formato['cols'];
    $esRollo = $cols === 1;

    // Disposicion. "precio" imita la etiqueta de tienda: codigo grande arriba,
    // precio destacado y descripcion al pie. "lateral" pone el codigo al lado
    // del texto y rinde mas en etiquetas bajas.
    $diseno = $diseno ?? 'lateral';
    $esPrecio = $diseno === 'precio';

    // Que se imprime. Por defecto lo de siempre, para no cambiar lo que ya
    // usan las hojas de ubicaciones, activos y bultos.
    $campos = $campos ?? ['codigo' => true, 'nombre' => true, 'precio' => $mostrarPrecio ?? false];
    $ver = fn ($k) => (bool) ($campos[$k] ?? false);

    $etiquetaAlta = $alto >= 25;
@endphp
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Etiquetas · {{ $project->name }}</title>
<style>
  @page {
      size: {{ $esRollo ? $ancho.'mm '.$alto.'mm' : 'A4' }};
      margin: {{ $esRollo ? '0' : '8mm 5mm' }};
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, "Segoe UI", Arial, sans-serif; color: #000; background: #fff; }

  .barra {
      padding: 10px 14px; background: #111827; color: #fff; font-size: 13px;
      display: flex; gap: 14px; align-items: center; justify-content: space-between;
  }
  .barra button {
      padding: 7px 16px; border: 0; border-radius: 6px; background: #10b981;
      color: #fff; font: inherit; font-weight: 700; cursor: pointer;
  }

  .hoja { display: grid; grid-template-columns: repeat({{ $cols }}, {{ $ancho }}mm); gap: 0; justify-content: center; }
  .et {
      width: {{ $ancho }}mm; height: {{ $alto }}mm; padding: 1.5mm; overflow: hidden;
      /* La guia de corte solo se ve en pantalla, nunca al imprimir. */
      border: 0.1mm dashed #d1d5db;
  }

  /* ── Disposicion lateral ── */
  .et.lat { display: flex; align-items: center; gap: 1.5mm; }
  .et.lat .cod-img { flex: 0 0 auto; line-height: 0; }
  .et.lat .cod-img svg { display: block; width: {{ min($alto - 3, 22) }}mm; height: {{ min($alto - 3, 22) }}mm; }
  .et.lat .txt { flex: 1 1 auto; min-width: 0; }

  /* ── Disposicion precio (etiqueta de tienda) ── */
  .et.pre { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
  .et.pre .sku-grande {
      font-size: {{ $etiquetaAlta ? '13pt' : '10pt' }}; font-weight: 800;
      letter-spacing: .5px; line-height: 1;
  }
  .et.pre .precio-grande {
      font-size: {{ $etiquetaAlta ? '15pt' : '11pt' }}; font-weight: 800;
      line-height: 1.1; margin-top: .6mm;
  }
  .et.pre .cod-img { line-height: 0; margin-top: .8mm; width: 100%; }
  .et.pre .cod-img svg { display: block; margin: 0 auto; max-width: 92%; height: {{ $etiquetaAlta ? '9mm' : '6mm' }}; }
  .et.pre .cod-img.es-qr svg { width: {{ $etiquetaAlta ? '13mm' : '9mm' }}; height: {{ $etiquetaAlta ? '13mm' : '9mm' }}; }
  .et.pre .nom {
      font-size: {{ $etiquetaAlta ? '5.5pt' : '4.5pt' }}; font-weight: 600; line-height: 1.15;
      margin-top: .7mm; text-transform: uppercase;
      display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
  }
  .et.pre .meta { font-size: {{ $etiquetaAlta ? '5pt' : '4pt' }}; color: #444; margin-top: .3mm; }

  /* ── Textos comunes ── */
  .nom-lat {
      font-size: {{ $etiquetaAlta ? '7pt' : '5.5pt' }}; font-weight: 700; line-height: 1.15;
      /* Dos lineas como maximo: un nombre largo empujaria el codigo fuera de
         la etiqueta, y esa es justo la parte que hay que poder leer. */
      display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
  }
  .cod-txt {
      margin-top: .8mm; font-family: "Consolas", monospace;
      font-size: {{ $etiquetaAlta ? '7.5pt' : '6pt' }}; font-weight: 700; letter-spacing: .2px;
  }
  .precio { margin-top: .5mm; font-size: {{ $etiquetaAlta ? '7.5pt' : '6pt' }}; font-weight: 700; }
  .meta-lat { font-size: {{ $etiquetaAlta ? '5.5pt' : '4.5pt' }}; color: #555; margin-top: .3mm; }

  @media print {
      .barra { display: none; }
      .et { border-color: transparent; }
  }
</style>
</head>
<body>

<div class="barra">
    <span>{{ count($etiquetas) }} etiquetas · {{ $formato['nombre'] }} · {{ $project->name }}</span>
    <span>
        Si el corte no encaja, revisa que la impresora esté al 100 % y sin "ajustar a página".
        <button onclick="window.print()">Imprimir</button>
    </span>
</div>

<div class="hoja">
    @foreach($etiquetas as $e)
    @php
        $esQr = ($e['tipo_codigo'] ?? 'qr') === 'qr';
        // `qr` es la clave con la que llaman las hojas de ubicaciones, activos
        // y bultos; `codigo_img` la nueva, que ademas admite codigo de barras.
        $codigoImg = $e['codigo_img'] ?? $e['qr'] ?? null;
        $meta = collect([$e['categoria'] ?? null, $e['marca'] ?? null])->filter()->implode(' · ');
    @endphp
    <div class="et {{ $esPrecio ? 'pre' : 'lat' }}">
        @if($esPrecio)
            @if($ver('codigo'))<div class="sku-grande">{{ $e['codigo'] }}</div>@endif
            @if($ver('precio') && ($e['precio'] ?? null))
            <div class="precio-grande">{{ $e['moneda'] ?? 'S/' }} {{ number_format($e['precio'], 2) }}</div>
            @endif
            @if(filled($codigoImg))
            <div class="cod-img {{ $esQr ? 'es-qr' : '' }}">{!! $codigoImg !!}</div>
            @endif
            @if($ver('nombre'))<div class="nom">{{ $e['nombre'] }}</div>@endif
            @if(filled($meta))<div class="meta">{{ $meta }}</div>@endif
        @else
            @if(filled($codigoImg))
            <div class="cod-img">{!! $codigoImg !!}</div>
            @endif
            <div class="txt">
                @if($ver('nombre'))<div class="nom-lat">{{ $e['nombre'] }}</div>@endif
                @if($ver('codigo'))<div class="cod-txt">{{ $e['codigo'] }}</div>@endif
                @if($ver('precio') && ($e['precio'] ?? null))
                <div class="precio">{{ $e['moneda'] ?? 'S/' }} {{ number_format($e['precio'], 2) }}@if($ver('unidad') && ($e['unidad'] ?? null)) / {{ $e['unidad'] }}@endif</div>
                @endif
                @if(filled($meta))<div class="meta-lat">{{ $meta }}</div>@endif
            </div>
        @endif
    </div>
    @endforeach
</div>

</body>
</html>
