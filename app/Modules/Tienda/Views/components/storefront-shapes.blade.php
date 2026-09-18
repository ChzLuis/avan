@props(['settings' => null, 'project' => null])
@php
    /*
        Emisor ÚNICO de las formas divisorias entre secciones.

        Se hace entero con CSS sobre `[data-store-home-section]`, que es el
        atributo que ya lleva cada sección del Inicio. Así no hay que tocar el
        renderizador de bloques ni las plantillas: una sola hoja y cada bloque
        elige su forma en el Constructor.

        El dibujo va en SVG incrustado, no en imagen: escala a cualquier ancho
        sin pixelarse, pesa unos cientos de bytes y hereda el color que el
        negocio elija.
    */
    $ajustes = $settings;

    if ($ajustes === null && $project) {
        $ajustes = $project->settings()->pluck('value', 'key')->all();
    }

    $ajustes = is_array($ajustes) ? $ajustes : [];

    /* Las cinco que usan de verdad los kits profesionales, en su orden real de
       uso: triángulo, inclinada, onda, pincelada y curva. */
    $FORMAS = [
        'triangle'   => '<path d="M1200 0L600 100 0 0v100h1200z"/>',
        'tilt'       => '<path d="M1200 0L0 100v0h1200z"/>',
        'waves'      => '<path d="M0 60c150 40 350-40 600 0s450 40 600 0v40H0z"/>',
        'wave-brush' => '<path d="M0 45c120 55 260-30 400 5s180 60 320 25 200-55 330-20 150 30 150 30v15H0z"/>',
        'curve'      => '<path d="M0 100c300-80 900-80 1200 0z"/>',
    ];

    // Se recogen los bloques que tienen forma configurada. Nada de listas fijas
    // de componentes: si mañana hay un bloque nuevo, esto lo recoge solo.
    $conForma = [];

    foreach ($ajustes as $clave => $valor) {
        if (! preg_match('/^shape_([a-z_]+)_style$/', (string) $clave, $m)) {
            continue;
        }
        $estilo = (string) $valor;
        if (! isset($FORMAS[$estilo])) {
            continue;
        }
        $bloque = $m[1];
        $alto = (int) ($ajustes['shape_'.$bloque.'_height'] ?? 60);
        $conForma[$bloque] = [
            'estilo' => $estilo,
            'alto'   => max(20, min(160, $alto ?: 60)),
            'voltea' => (string) ($ajustes['shape_'.$bloque.'_flip'] ?? '') === '1',
        ];
    }

    /* Velo sobre el fondo de la sección. Es el ajuste más repetido de todos
       los kits profesionales (96%) y resuelve un problema real: cuando el
       bloque lleva una foto de fondo, el texto encima no se lee. Un velo
       oscuro al 40% lo arregla sin tocar la foto. */
    $conVelo = [];

    foreach ($ajustes as $clave => $valor) {
        if (! preg_match('/^overlay_([a-z_]+)_opacity$/', (string) $clave, $m)) {
            continue;
        }
        $opacidad = (int) $valor;
        if ($opacidad <= 0) {
            continue;
        }
        $bloque = $m[1];
        $tono = (string) ($ajustes['overlay_'.$bloque.'_color'] ?? '#0f172a');
        $conVelo[$bloque] = [
            // Por encima de 90 el fondo deja de verse y es una banda de color.
            'opacidad' => min(90, $opacidad) / 100,
            'color'    => preg_match('/^#[0-9a-f]{3,8}$/i', $tono) ? $tono : '#0f172a',
        ];
    }

    /* El color por defecto es el fondo de la página: así la forma "recorta" la
       sección en lugar de pintar una banda de color encima. */
    $color = (string) ($ajustes['surface_color'] ?? '') ?: '#ffffff';
    $color = preg_match('/^#[0-9a-f]{3,8}$/i', $color) ? $color : '#ffffff';
    $svgColor = str_replace('#', '%23', $color);
@endphp
@if($conForma || $conVelo)
<style>
[data-store-home-section] { position: relative; }
@foreach($conVelo as $bloque => $v)
[data-store-home-section="{{ $bloque }}"]::before {
  content: ""; position: absolute; inset: 0; z-index: 0; pointer-events: none;
  background: {{ $v['color'] }}; opacity: {{ $v['opacidad'] }};
}
[data-store-home-section="{{ $bloque }}"] > * { position: relative; z-index: 1; }
@endforeach
@foreach($conForma as $bloque => $f)
[data-store-home-section="{{ $bloque }}"]::after {
  content: ""; position: absolute; left: 0; right: 0; bottom: -1px; z-index: 1;
  height: {{ $f['alto'] }}px; pointer-events: none;
  background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 100' preserveAspectRatio='none' fill='{{ $svgColor }}'%3E{{ str_replace(['<', '>', '"', '#'], ['%3C', '%3E', "'", '%23'], $FORMAS[$f['estilo']]) }}%3C/svg%3E") no-repeat bottom center / 100% 100%;
@if($f['voltea'])
  transform: scaleX(-1);
@endif
}
@endforeach
</style>
@endif
