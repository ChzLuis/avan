@props(['settings' => null, 'project' => null])
@php
    /*
        Emisor ÚNICO del movimiento de la tienda (hover y entrada escalonada).

        Por qué vive aquí y no repartido por plantilla: hay 16 plantillas de
        tienda, cada una con su propia clase de tarjeta, y ninguna comparte un
        parcial de producto. Si el hover se escribiera en cada una habría 16
        sitios que tocar y 16 formas de que se desincronicen. Aquí se enumeran
        las clases contenedoras que existen de verdad —salieron de recorrer las
        vistas, no de suponer— y se emite una sola hoja.

        Se excluyen a propósito `.card` (demasiado genérica, la usan modales),
        `.age-card` (la verja de edad de licorería: animar un aviso legal al
        pasar el ratón es ruido) y `.print-card` (impresión).

        Nada de esto se emite si el negocio no lo activó en el Constructor, y
        todo se apaga para quien pide "reducir movimiento" en su sistema: una
        animación no puede ser motivo de mareo para nadie.
    */
    $ajustes = $settings;

    if ($ajustes === null && $project) {
        $ajustes = $project->settings()->pluck('value', 'key')->all();
    }

    $ajustes = is_array($ajustes) ? $ajustes : [];

    $efecto  = (string) ($ajustes['hover_card_effect'] ?? '');
    $zoom    = (string) ($ajustes['hover_image_zoom'] ?? '') === '1';
    $escalon = (int) ($ajustes['anim_stagger_ms'] ?? 0);

    // El catálogo de efectos es el que usan los kits profesionales, medido
    // sobre 29 de ellos: flotar y crecer son los dos dominantes.
    $EFECTOS = [
        'float'  => 'transform: translateY(-6px); box-shadow: 0 12px 28px rgba(0,0,0,.13);',
        'grow'   => 'transform: scale(1.035); box-shadow: 0 12px 28px rgba(0,0,0,.13);',
        'shrink' => 'transform: scale(.972);',
        'bob'    => 'animation: bx-bob 1.1s ease-in-out infinite;',
        'pop'    => 'transform: translateY(-4px) scale(1.02); box-shadow: 0 14px 30px rgba(0,0,0,.16);',
    ];

    $TARJETAS = ['.prod-card', '.porto-card', '.catalog-card', '.ella-card', '.nordic-card',
        '.lic-card', '.testi-card', '.cat-card', '.fresh-card', '.product-card', '.blog-card',
        '.article-card', '.related-card', '.shop-card', '.home-cat-card', '.pdp-rel-card',
        '.flash-card', '.xs-coll-card', '.about-card', '.trust-card', '.bx-card'];

    $sel = implode(',', $TARJETAS);

    // Escalonar más de 12 tarjetas no se percibe y retrasa demasiado la última.
    $PASOS = 12;

    $hayEfecto = isset($EFECTOS[$efecto]);
    $emite     = $hayEfecto || $zoom || $escalon > 0;
@endphp
@if($emite)
<style>
@if($hayEfecto)
{{ $sel }} { transition: transform .28s ease, box-shadow .28s ease; will-change: transform; }
{!! implode(':hover,', $TARJETAS) !!}:hover { {!! $EFECTOS[$efecto] !!} }
@if($efecto === 'bob')
@keyframes bx-bob { 0%,100% { transform: translateY(-4px) } 50% { transform: translateY(2px) } }
@endif
@endif
@if($zoom)
/* La imagen crece DENTRO de su marco: el recorte lo pone el contenedor. */
{!! implode(' .bx-media,', $TARJETAS) !!} .bx-media,
{!! implode(' picture,', $TARJETAS) !!} picture,
{!! implode(' .img-wrap,', $TARJETAS) !!} .img-wrap { overflow: hidden; }
{!! implode(' img,', $TARJETAS) !!} img { transition: transform .45s ease; }
{!! implode(':hover img,', $TARJETAS) !!}:hover img { transform: scale(1.07); }
@endif
@if($escalon > 0)
{{ $sel }} { animation: bx-entrada .5s ease both; }
@for($i = 1; $i <= $PASOS; $i++)
{!! implode(':nth-child('.$i.'),', $TARJETAS) !!}:nth-child({{ $i }}) { animation-delay: {{ ($i - 1) * $escalon }}ms; }
@endfor
@keyframes bx-entrada { from { opacity: 0; transform: translateY(14px) } to { opacity: 1; transform: none } }
@endif
@media (prefers-reduced-motion: reduce) {
  {{ $sel }}, {!! implode(' img,', $TARJETAS) !!} img {
    animation: none !important; transition: none !important; transform: none !important;
  }
}
</style>
@endif
