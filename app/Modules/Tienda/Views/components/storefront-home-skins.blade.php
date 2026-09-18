@props(['settings' => []])
@php
    // Nunca confiar en el valor guardado: normalizar antes de escribirlo en el
    // HTML. Ambos salen de una lista cerrada, así que no hay forma de inyectar.
    $hpPreset = \App\Modules\Tienda\Storefront\HomePresets::clave($settings['home_template'] ?? null);
    $hpVariante = \App\Modules\Tienda\Storefront\HomePresets::variante($settings['home_hero_variant'] ?? null);
@endphp
<style>
/* ─────────────────────────────────────────────────────────────────────────
   PIELES DE LA PAGINA DE INICIO

   Cinco lenguajes visuales sobre los MISMOS bloques. No hay marcado nuevo:
   cada piel reinterpreta lo que ya pinta `storefront-home-sections`, asi que
   ninguna tienda pierde contenido al cambiar de diseño.

   Todo cuelga de `.hp-1 … .hp-5` en el <body>, y todo color sale de las
   variables del tema del negocio (`--store-primary`, `--store-font-title`).
   Aqui no hay un solo color de marca escrito a mano: el violeta del Señor de
   Muruhuay fue la referencia, no el valor.
   ───────────────────────────────────────────────────────────────────────── */

/* Piezas comunes a las cinco. Se declaran una vez para no repetir. */
.hp-scope .sf-home-section{font-family:var(--store-font-body,inherit)}
.hp-scope .sf-home-heading h2{font-family:var(--store-font-title,inherit)}

/* ── 01 · COMERCIAL CLASICO ────────────────────────────────────────────────
   El punto de partida: no reinventa nada, solo ordena y da un ritmo comodo.
   Es lo que ya conocian las tiendas existentes, por eso es el fallback. */
.hp-1 .sf-home-section{padding:clamp(44px,5.5vw,72px) 20px}
.hp-1 .sf-hero{min-height:clamp(420px,52vw,560px)}
.hp-1 .sf-benefit{padding:20px}
.hp-1 .sf-category-grid{gap:16px}

/* ── 02 · PRODUCTO PRIMERO ────────────────────────────────────────────────
   Todo se comprime para que el catalogo entre antes del primer scroll: la
   portada baja a la mitad de alto, las secciones respiran menos y las
   categorias se vuelven una banda ancha en vez de tarjetas grandes. */
.hp-2 .sf-home-section{padding:clamp(28px,3.4vw,44px) 20px}
.hp-2 .sf-hero{min-height:clamp(240px,30vw,340px)}
.hp-2 .sf-hero h1{font-size:clamp(28px,4vw,46px)}
.hp-2 .sf-hero p{font-size:clamp(14px,1.5vw,17px);margin-top:12px}
.hp-2 .sf-hero-slide{padding:36px 20px}
.hp-2 .sf-category-grid{grid-template-columns:repeat(6,minmax(0,1fr));gap:12px}
.hp-2 .sf-category-media{aspect-ratio:1}
.hp-2 .sf-category strong{padding:11px 8px;font-size:13.5px}
.hp-2 .sf-home-heading{margin-bottom:18px}
.hp-2 .sf-benefits{padding-top:22px;padding-bottom:22px}
.hp-2 .sf-benefit{padding:14px 16px}
.hp-2 .sf-benefit h3{font-size:14.5px}
.hp-2 .sf-benefit p{font-size:13px}

/* ── 03 · MARCA & HISTORIA ────────────────────────────────────────────────
   Registro editorial: titulos mas grandes y con mas aire, indicadores con
   peso tipografico, y la seccion Nosotros tratada como pieza principal. */
.hp-3 .sf-home-section{padding:clamp(52px,6.5vw,92px) 20px}
.hp-3 .sf-home-heading{margin-bottom:34px}
.hp-3 .sf-home-heading h2{font-size:clamp(28px,3.6vw,46px);letter-spacing:-.03em}
.hp-3 .sf-home-heading p{font-size:16.5px}
.hp-3 .sf-hero h1{font-size:clamp(38px,5.4vw,64px);letter-spacing:-.04em}
.hp-3 .sf-about-cifras{gap:38px;margin:24px 0 26px}
.hp-3 .sf-about-cifras strong{font-size:clamp(30px,3.4vw,42px);color:var(--store-primary);font-family:var(--store-font-title,inherit)}
.hp-3 .sf-about-cifras span{font-size:13px;letter-spacing:.02em}
.hp-3 .sf-about-grid{gap:clamp(32px,5vw,68px)}
.hp-3 .sf-about-foto img{max-height:480px}
/* Las tarjetas pierden la sombra y ganan un filete: menos "app", mas papel. */
.hp-3 .sf-card{box-shadow:none;border-color:color-mix(in srgb,var(--store-primary) 16%,#e5e7eb)}
.hp-3 .sf-testi-card{border-left:3px solid var(--store-primary)}

/* ── 04 · MINIMAL PREMIUM ─────────────────────────────────────────────────
   El que tiene que sentirse de otra tienda. Se quitan las cajas: sin bordes,
   sin sombras, sin fondos grises. Manda la foto y el espacio en blanco. */
.hp-4 .sf-home-section{padding:clamp(56px,7.5vw,112px) 20px;background:#fff}
.hp-4 .sf-benefits,.hp-4 .sf-products,.hp-4 .sf-offer{background:#fff}
.hp-4 .sf-card{border:0;border-radius:0;box-shadow:none;background:transparent}
.hp-4 .sf-home-heading{flex-direction:column;align-items:center;text-align:center;gap:8px;margin-bottom:40px}
.hp-4 .sf-home-heading h2{font-size:clamp(26px,3.2vw,40px);font-weight:400;letter-spacing:.01em}
.hp-4 .sf-home-heading p{margin-inline:auto;text-align:center}
.hp-4 .sf-hero{min-height:min(72vh,720px)}
.hp-4 .sf-hero h1{font-size:clamp(36px,5vw,62px);font-weight:400;letter-spacing:-.02em}
.hp-4 .sf-button{border-radius:0;background:transparent;border:1px solid currentColor;color:#fff;font-weight:500;letter-spacing:.06em;text-transform:uppercase;font-size:12.5px}
.hp-4 .sf-hero .sf-button:hover{background:#fff;color:#111}
/* Fuera del hero el boton va sobre fondo claro: se invierte para que se lea. */
.hp-4 .sf-home-container .sf-button{border-color:#111;color:#111}
.hp-4 .sf-home-container .sf-button:hover{background:#111;color:#fff}
.hp-4 .sf-category-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.hp-4 .sf-category-media{aspect-ratio:3/4}
.hp-4 .sf-category-media img{transition:transform .5s ease}
.hp-4 .sf-category:hover .sf-category-media img{transform:scale(1.04)}
.hp-4 .sf-category strong{padding:14px 2px;font-weight:400;letter-spacing:.06em;text-transform:uppercase;font-size:13px}
.hp-4 .sf-coll-grid{gap:10px}
.hp-4 .sf-gal-grid{gap:8px}
.hp-4 .sf-about-grid{gap:clamp(36px,6vw,84px)}
.hp-4 .sf-about-foto img{border-radius:0;max-height:560px}
.hp-4 .sf-brand-grid img{filter:grayscale(1);opacity:.6}

/* ── 05 · MAYORISTA / CORPORATIVO ─────────────────────────────────────────
   Tecnico y denso: esquinas rectas, filetes marcados, titulos en caja alta y
   una franja de color a la izquierda de cada encabezado. Lee a catalogo
   industrial, no a boutique. */
.hp-5 .sf-home-section{padding:clamp(38px,4.6vw,62px) 20px}
.hp-5 .sf-card{border-radius:4px;box-shadow:none;border-color:#d7dee7}
.hp-5 .sf-home-heading h2{font-size:clamp(22px,2.6vw,32px);text-transform:uppercase;letter-spacing:.01em;font-weight:800}
.hp-5 .sf-home-heading>div{border-left:4px solid var(--store-primary);padding-left:14px}
.hp-5 .sf-button{border-radius:4px;text-transform:uppercase;letter-spacing:.03em;font-size:13.5px}
.hp-5 .sf-benefit{padding:18px;border-top:3px solid var(--store-primary)}
.hp-5 .sf-benefit-icon{border-radius:4px}
.hp-5 .sf-category-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
.hp-5 .sf-category strong{text-transform:uppercase;font-size:13px;letter-spacing:.02em}
.hp-5 .sf-brand-grid{gap:10px}
.hp-5 .sf-brand{border:1px solid #e5e9ef;border-radius:4px;padding:14px;min-height:78px}
.hp-5 .sf-strip-item{padding:14px;border:1px solid #e5e9ef;border-radius:4px}

/* ── VARIANTE B DE PORTADA ────────────────────────────────────────────────
   Una sola variante por preset, no cinco diseños mas. El bloque `hero` es
   siempre el mismo marcado: lo que cambia es como se compone.

   La A es la que ya existia (foto de fondo con el texto encima), asi que no
   se toca — las tiendas que hoy funcionan siguen exactamente igual. */

/* 1B y 5B — la foto se lleva toda la portada y el texto se apoya en un panel
   solido, legible sobre cualquier fotografia por oscura que sea. */
.hp-1.hp-b .sf-hero-slide,.hp-5.hp-b .sf-hero-slide{background-image:var(--sf-image,none)}
.hp-1.hp-b .sf-hero-copy,.hp-5.hp-b .sf-hero-copy{
    background:color-mix(in srgb,var(--store-primary) 92%,#000);
    color:#fff;padding:clamp(26px,3.4vw,46px);max-width:560px;
    border-radius:var(--store-radius,10px);
}
.hp-5.hp-b .sf-hero-copy{border-radius:4px;border-left:5px solid #fff}

/* 2B — banda partida: la foto ocupa la mitad derecha en vez de todo el fondo,
   para que el producto se vea limpio y no debajo de un velo. */
@media(min-width:900px){
    .hp-2.hp-b .sf-hero-slide{
        background-position:right center;background-size:52% 100%;
        background-color:color-mix(in srgb,var(--store-primary) 8%,#fff);
        background-repeat:no-repeat;
    }
    .hp-2.hp-b .sf-hero-copy{max-width:46%}
    .hp-2.hp-b .sf-hero h1,.hp-2.hp-b .sf-hero p{color:#111827}
    .hp-2.hp-b .sf-hero p{opacity:.78}
}

/* 3B — panoramica: portada mas baja y ancha, con el texto centrado. El gesto
   editorial de una portada de revista. */
.hp-3.hp-b .sf-hero{min-height:clamp(320px,42vw,480px)}
.hp-3.hp-b .sf-hero-copy{max-width:760px;margin:0 auto;text-align:center}
.hp-3.hp-b .sf-actions{justify-content:center}

/* 4B — split editorial: media portada de foto, media de texto sobre blanco.
   Sin velos ni degradados: la foto se ve tal cual la subio el negocio. */
@media(min-width:900px){
    .hp-4.hp-b .sf-hero{min-height:min(66vh,640px);background:#fff}
    .hp-4.hp-b .sf-hero-slide{
        background-image:var(--sf-image,none);background-position:right center;
        background-size:50% 100%;background-repeat:no-repeat;background-color:#fff;
    }
    .hp-4.hp-b .sf-hero-copy{max-width:44%}
    .hp-4.hp-b .sf-hero h1{color:#111}
    .hp-4.hp-b .sf-hero p{color:#4b5563}
    .hp-4.hp-b .sf-hero .sf-button{border-color:#111;color:#111}
    .hp-4.hp-b .sf-hero .sf-button:hover{background:#111;color:#fff}
}

/* ── MOVIL ───────────────────────────────────────────────────────────────
   Se revisa aqui y no delegando en el `flex-col` de cada bloque: los presets
   que aprietan (02) o que estiran (04) necesitan su propio ajuste o quedan
   con titulos diminutos o con secciones de pantalla y media. */
@media(max-width:767px){
    .hp-1 .sf-home-section{padding:38px 16px}
    .hp-2 .sf-home-section{padding:26px 16px}
    .hp-2 .sf-hero{min-height:230px}
    .hp-2 .sf-category-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .hp-3 .sf-home-section{padding:44px 16px}
    .hp-3 .sf-about-cifras{gap:22px}
    .hp-3 .sf-about-cifras strong{font-size:26px}
    .hp-4 .sf-home-section{padding:48px 18px}
    .hp-4 .sf-hero{min-height:62vh}
    .hp-4 .sf-category-grid{grid-template-columns:1fr;gap:8px}
    .hp-4 .sf-category-media{aspect-ratio:16/10}
    .hp-5 .sf-home-section{padding:32px 16px}
    .hp-5 .sf-category-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    /* En movil no hay dos columnas que partir: la variante B vuelve a ser una
       foto de fondo con el texto encima, que es lo unico que cabe bien. */
    .hp-b .sf-hero-copy{max-width:100%!important}
}
@media(min-width:768px) and (max-width:1023px){
    .hp-2 .sf-category-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
    .hp-5 .sf-category-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
    .hp-4 .sf-category-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
}
@media(prefers-reduced-motion:reduce){
    .hp-4 .sf-category-media img{transition:none}
}
</style>
<script>
    // La piel se marca en el <body> para que las reglas de arriba alcancen a
    // TODOS los bloques, esten donde esten. Se hace inline y sin esperar a
    // DOMContentLoaded para que no haya un parpadeo con el diseño anterior.
    document.body.classList.add('hp-scope', 'hp-{{ $hpPreset }}', 'hp-{{ $hpVariante }}');
</script>
