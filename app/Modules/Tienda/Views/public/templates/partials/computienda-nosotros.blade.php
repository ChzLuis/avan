{{-- Mejoras de la página Nosotros.

     Va en su propio parcial porque `computienda.blade.php` lo tocan varias
     sesiones a la vez: aislar esto evita el conflicto y permite desplegarlo
     solo. Nada de lo de aquí inventa contenido — todo sale de lo que el
     negocio ya guardó en la página (`store_pages.content`).

     Lo que corrige, en orden de lo que más se notaba:

     1. LA MITAD DERECHA VACÍA. `.about-grid` estaba fijada a UNA columna, así
        que el texto ocupaba la izquierda y sobraba media pantalla en blanco.
        Que exista una regla móvil bajándola a 1 columna delata que la de
        escritorio debía ser 2: se repone.

     2. NINGUNA FOTO bajo la portada. La foto que el negocio sube (`image`) se
        usaba solo como fondo del hero; aquí acompaña a la presentación.

     3. VALORES PLANOS. Eran cajas de una línea; pasan a tarjetas con jerarquía.

     4. HISTORIA Y EQUIPO a media pantalla, con la otra media en blanco.

     Todo el color sale de `--primary`/`--accent` del negocio. Ni un valor de
     marca escrito a mano. --}}
<style>
  /* ── 0. Portada CON foto: que el texto se lea sobre cualquier imagen ──────
     Con foto, el subtítulo salía en el color de acento (lila, naranja, el que
     sea) directamente sobre la fotografía, y en cuanto la foto tenía detalle
     detrás —estanterías, luces, cables— se perdía. El título ya iba en blanco
     con sombra; se aplica el mismo criterio a subtítulo y descripción, y se
     añade un velo suave SOLO detrás del bloque de texto, que se desvanece
     hacia la derecha para no tapar la foto. El acento sigue presente en la
     barra bajo el título, que es donde no compite con nada.
     Solo aplica a la variante con foto: el degradado de marca no lo necesita. */
  #storefront-main .about-hero:not(.about-hero--brand) .about-hero-content{
    background:linear-gradient(90deg, rgba(8,10,20,.58) 0%, rgba(8,10,20,.42) 55%, rgba(8,10,20,0) 100%);
  }
  #storefront-main .about-hero:not(.about-hero--brand) .about-hero-subtitle{
    color:#fff; text-shadow:0 1px 14px rgba(0,0,0,.55), 0 0 2px rgba(0,0,0,.35);
  }
  #storefront-main .about-hero:not(.about-hero--brand) .about-hero-desc{
    color:rgba(255,255,255,.94); text-shadow:0 1px 12px rgba(0,0,0,.55);
  }
  /* En móvil el bloque ocupa todo el ancho: el velo cubre completo. */
  @media(max-width:640px){
    #storefront-main .about-hero:not(.about-hero--brand) .about-hero-content{
      background:linear-gradient(180deg, rgba(8,10,20,.35) 0%, rgba(8,10,20,.62) 100%);
    }
  }

  /* ── 1. La presentación recupera su segunda columna ───────────────────────
     Con `1fr` la sección quedaba a media pantalla. Con dos, el texto respira a
     la izquierda y a la derecha entran misión/visión o la foto. */
  #storefront-main .about-grid{
    grid-template-columns:minmax(0,1.05fr) minmax(0,.95fr);
    align-items:start;
  }
  /* Cuando NO hay misión ni visión que poner al lado, la columna la ocupa la
     foto del negocio; y si tampoco hay foto, se centra el texto en vez de
     dejarlo pegado a la izquierda con un hueco al lado. */
  #storefront-main .about-grid--single{
    grid-template-columns:minmax(0,1fr); max-width:820px;
  }

  /* ── 2. La foto del negocio, al lado de la presentación ─────────────────── */
  .about-foto{
    position:relative; overflow:hidden; border-radius:14px;
    background:color-mix(in srgb,var(--primary) 5%,#fff);
  }
  .about-foto img{
    display:block; width:100%; height:100%; min-height:260px; max-height:420px;
    object-fit:cover; transition:transform .5s ease;
  }
  @media (hover:hover){ .about-foto:hover img{transform:scale(1.02)} }
  /* Rótulo opcional sobre la foto: solo si el negocio escribió uno. */
  .about-foto-nota{
    position:absolute; left:0; right:0; bottom:0; padding:26px 20px 16px;
    background:linear-gradient(to top,rgba(8,10,20,.72),transparent);
    color:#fff; font-size:13px; font-weight:600;
  }

  /* ── 3. Indicadores: la prueba de trayectoria ───────────────────────────
     Sin tarjetas ni cajas: cifra grande en el color del negocio y una línea de
     apoyo debajo, separadas por un filete finísimo. */
  /* Columnas FIJAS por número de indicadores y no `auto-fit`: con `auto-fit` y
     el ancho de la columna izquierda, cuatro cifras se partían 3 + 1 y la
     última quedaba sola en una segunda fila, que es justo lo que se veía
     descuidado. Con `repeat(4,...)` entran las cuatro en una línea. */
  .about-cifras{
    display:grid; grid-template-columns:repeat(4,minmax(0,1fr));
    gap:clamp(12px,2vw,26px); margin:34px 0 6px;
    padding:24px 0; border-top:1px solid var(--border,#e9e6f0);
    border-bottom:1px solid var(--border,#e9e6f0);
  }
  /* Con menos de cuatro no se dejan huecos: se reparten el ancho. */
  .about-cifras:has(> :nth-child(3):last-child){grid-template-columns:repeat(3,minmax(0,1fr))}
  .about-cifras:has(> :nth-child(2):last-child){grid-template-columns:repeat(2,minmax(0,1fr))}
  .about-cifras:has(> :only-child){grid-template-columns:minmax(0,1fr)}
  /* Cifras algo más contenidas: comparten fila con su etiqueta y a 4 columnas
     dentro de media pantalla no caben al tamaño de un titular. */
  .about-cifras .about-cifra strong{font-size:clamp(22px,2.4vw,30px)}
  .about-cifras .about-cifra span{font-size:11.5px}
  .about-cifra strong{
    display:block; color:var(--primary);
    font-family:var(--font-title,inherit);
    font-size:clamp(26px,3.2vw,38px); font-weight:800; line-height:1.05;
    letter-spacing:-.02em;
  }
  .about-cifra span{
    display:block; margin-top:5px; color:var(--muted,#65708a);
    font-size:12.5px; line-height:1.45;
  }

  /* ── 4. Historia: texto y foto, no texto y vacío ───────────────────────── */
  .about-historia{
    display:grid; grid-template-columns:minmax(0,1fr) minmax(0,.85fr);
    gap:clamp(26px,4vw,50px); align-items:center; margin-top:8px;
  }
  .about-historia--solo{grid-template-columns:minmax(0,1fr); max-width:800px}
  .about-historia .store-page-block{margin-top:0}
  .about-historia-foto{overflow:hidden; border-radius:14px}
  .about-historia-foto img{
    display:block; width:100%; height:100%; min-height:240px; max-height:380px;
    object-fit:cover;
  }

  /* ── 5. Valores con jerarquía ──────────────────────────────────────────
     Eran renglones sueltos dentro de una caja; ahora cada uno es una tarjeta
     con su marca de color, que es lo que los hace legibles de un vistazo. */
  #storefront-main .sp-values{
    display:grid; grid-template-columns:repeat(auto-fit,minmax(215px,1fr)); gap:14px;
  }
  #storefront-main .sp-values li{
    display:flex; align-items:flex-start; gap:11px;
    padding:18px 18px 18px 16px;
    background:#fff; border:1px solid var(--border,#e9e6f0);
    border-left:3px solid var(--primary);
    border-radius:12px; color:var(--text-strong,#11132f);
    font-size:14.5px; font-weight:600; line-height:1.45;
    transition:transform .2s ease, box-shadow .2s ease;
  }
  @media (hover:hover){
    #storefront-main .sp-values li:hover{
      transform:translateY(-2px); box-shadow:0 8px 22px rgba(15,23,42,.07);
    }
  }
  #storefront-main .sp-values svg{
    width:18px; height:18px; flex:0 0 18px; margin-top:1px; color:var(--primary);
  }

  /* La banda de beneficios traia margen ARRIBA pero ninguno abajo, asi que lo
     que viniera despues se le pegaba. Se le da su propio respiro en vez de
     compensarlo desde cada bloque siguiente. */
  #storefront-main .about-diffs{margin-bottom:8px}

  /* Galería: rejilla que se adapta al número de fotos, sin huecos. */
  .about-galeria{
    display:grid; grid-template-columns:repeat(auto-fit,minmax(230px,1fr)); gap:14px;
  }
  .about-galeria img{
    display:block; width:100%; aspect-ratio:4/3; object-fit:cover;
    border-radius:12px; transition:transform .4s ease;
  }
  @media (hover:hover){ .about-galeria img:hover{transform:scale(1.02)} }
  @media(max-width:560px){ .about-galeria{grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px} }
  @media (prefers-reduced-motion:reduce){ .about-galeria img{transition:none} }

  /* ── 6. Los bloques finales, en dos columnas ───────────────────────────
     El párrafo mantiene su tope de lectura (70ch) porque una línea de 1400px
     no se lee: el problema NO era el ancho del texto sino la media pantalla
     vacía a su derecha. Se resuelve poniendo Historia y Equipo en dos
     columnas, no estirando la tipografía.

     `Valores` queda fuera a propósito: son tarjetas y ya ocupan todo el ancho. */
  #storefront-main .about-cierre{
    display:grid; grid-template-columns:repeat(2,minmax(0,1fr));
    gap:clamp(24px,4vw,52px); align-items:start;
    /* El aire lo pone el CONTENEDOR. Anular el `margin-top` de los bloques
       hijos es lo que los alinea entre si —arrancan los dos a la misma
       altura—, pero de paso borraba la separacion con la banda de beneficios
       de arriba y los titulos quedaban pegados a ella. */
    margin-top:44px;
  }
  #storefront-main .about-cierre > .store-page-block{margin-top:0}
  /* Y el bloque que venga DESPUES (Valores) recupera su respiro: si no, el
     salto de la columna corta a Valores se leia como un hueco raro. */
  #storefront-main .about-cierre + .store-page-block{margin-top:46px}
  @media(max-width:900px){
    #storefront-main .about-cierre{grid-template-columns:1fr; gap:0; margin-top:34px}
    #storefront-main .about-cierre > .store-page-block + .store-page-block{margin-top:34px}
  }

  /* ── MÓVIL ────────────────────────────────────────────────────────────── */
  @media(max-width:980px){
    #storefront-main .about-grid,
    .about-historia{grid-template-columns:1fr}
    /* La foto va DESPUÉS del texto: en móvil manda lo que se lee. */
    .about-foto,.about-historia-foto{order:2}
    .about-foto img,.about-historia-foto img{min-height:200px;max-height:260px}
    .about-cifras{gap:16px;margin:26px 0 4px;padding:20px 0}
  }
  @media(max-width:560px){
    .about-cifras{grid-template-columns:repeat(2,minmax(0,1fr))}
    #storefront-main .sp-values{grid-template-columns:1fr}
  }
  @media (prefers-reduced-motion:reduce){
    .about-foto img,#storefront-main .sp-values li{transition:none}
    .about-foto:hover img{transform:none}
  }
</style>
