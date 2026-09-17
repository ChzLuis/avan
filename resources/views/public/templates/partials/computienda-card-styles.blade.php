{{-- Estilos de la tarjeta de producto. Van en su propio parcial porque el
     catalogo arma sus tarjetas en linea y no incluye `computienda-card`:
     dejarlos alli los dejaba fuera de /tienda. --}}
<style>
  /* ─────────────────────────────────────────────────────────────────────────
     TARJETA DE PRODUCTO — rediseño 2026

     Tres decisiones sostienen todo lo demas:

     1. ALTURA POR PARTES FIJAS. La foto mide igual en toda la fila, el nombre
        reserva SIEMPRE dos lineas y el pie se empuja con `margin-top:auto`.
        Asi las cuatro tarjetas terminan alineadas —foto, categoria, titulo,
        precio, boton y borde inferior— aunque un producto se llame "TW-80" y
        el de al lado ocupe dos renglones. No se logra metiendo huecos.

     2. LA FOTO MANDA. El producto ocupa el marco completo menos un aire
        minimo. El error anterior era sumar relleno aqui MAS el que la foto ya
        trae horneada: entre los dos lo dejaban en ~30% del marco.

     3. TODO COLOR SALE DEL TEMA. `var(--primary)` es el color que configuro el
        negocio. Aqui no hay un solo color de marca escrito a mano; el unico
        fijo es el verde de WhatsApp, que es el color del canal, no de la
        tienda.
     ───────────────────────────────────────────────────────────────────────── */

  .catalog-card{
    display:flex; flex-direction:column; height:100%; position:relative;
    background:#fff; border:1px solid #E7E5EA; border-radius:14px; overflow:hidden;
    box-shadow:0 1px 2px rgba(15,23,42,.04);
    transition:transform .22s ease, box-shadow .22s ease, border-color .22s ease;
  }
  /* El movimiento solo donde hay puntero de verdad: en tactil el :hover se
     queda "pegado" tras tocar y la tarjeta se queda elevada sin motivo. */
  @media (hover:hover){
    .catalog-card:hover{
      transform:translateY(-3px);
      border-color:color-mix(in srgb, var(--primary,#7c3aed) 32%, #E7E5EA);
      box-shadow:0 10px 26px rgba(15,23,42,.10);
    }
  }
  /* Foco visible para quien navega con teclado. Sigue el color de la tienda,
     nunca se elimina. */
  .catalog-card a:focus-visible,
  .catalog-card button:focus-visible{
    outline:2px solid var(--primary,#7c3aed); outline-offset:2px; border-radius:6px;
  }

  /* ── AREA DE IMAGEN ────────────────────────────────────────────────────── */
  /* Blanco puro y con !important: otra regla lo dejaba en #FAFAF9 y ese crudo
     contra el blanco del card dibujaba un rectangulo interno que parecia una
     segunda tarjeta dentro de la tarjeta. */
  .catalog-card-media{
    position:relative; height:252px; flex:0 0 252px;
    background:#fff!important; display:flex; align-items:center; justify-content:center;
    padding:10px 12px 12px; box-sizing:border-box; overflow:hidden;
  }

  /* ── MARCA DE AGUA ─────────────────────────────────────────────────────── */
  /* Va como capa CSS y NO quemada en el JPG: asi se coloca arriba, se atenua y
     usa el logo de cada tienda. Antes venia incrustada en el centro de la foto
     del proveedor, atravesando el aparato, y un pixel quemado no se puede
     mover ni suavizar despues.
     Se pinta sobre un pseudo-elemento en z-index 0 y el enlace de la foto va
     en z-index 1: el producto SIEMPRE queda por delante del logo. */
  .catalog-card-media::before{
    content:""; position:absolute; z-index:2; left:22%; right:22%; bottom:2%; height:14%;
    background-image:var(--card-marca); background-repeat:no-repeat;
    background-position:center bottom; background-size:contain;
    /* Al PIE, no arriba: el producto (luminarias apaisadas) ocupa el centro-alto
       del marco, asi que arriba la marca se le montaba encima. Abajo cae sobre
       blanco limpio, se lee entera y queda a la misma altura en todas las
       tarjetas porque la caja de la foto mide igual en toda la fila.
       `contain` acotado a los lados: un logo apaisado nunca cruza de borde a
       borde y todos los logos escalan al mismo alto.
       .42 porque sobre blanco es donde se lee: ya no compite con el producto. */
    opacity:var(--card-marca-op,.42); pointer-events:none;
  }
  /* Sin logo configurado no se dibuja nada: una tienda sin marca no debe
     enseñar un hueco ni una imagen rota. */
  .catalog-card-media:not([style*="--card-marca"])::before,
  .catalog-card-media.is-noimg::before{content:none}

  /* El producto ocupa el marco completo menos el aire del contenedor, pero
     dejando libre la banda INFERIOR donde vive la marca de agua: asi el logo
     se lee como branding del catalogo y no como una imagen pegada encima del
     aparato. Es el 78-86% del area que pide el encargo.
     La banda va abajo y no arriba porque el producto (luminarias apaisadas)
     se apoya en el centro-alto del marco: arriba la marca se le montaba
     encima y abajo cae siempre sobre blanco limpio. */
  .catalog-card-media-link{
    position:relative; z-index:1;
    display:flex; align-items:center; justify-content:center;
    width:100%; height:100%; box-sizing:border-box;
  }
  /* El hueco de la marca se reserva aqui y no en el enlace: la plantilla pone
     el enlace en `position:absolute;inset:0` (para que la foto no salga
     decapitada) y eso anula cualquier padding suyo. Recortando el fondo de la
     caja de la imagen al 80%, el producto se centra en la parte de arriba y la
     banda inferior queda siempre libre para el logo. */
  .catalog-card-media-link img{bottom:24%!important;height:auto!important}
  /* El relleno lo pone SOLO el contenedor. La imagen traia otro 8% heredado
     del procesador y entre los dos encogian el producto sin que se notara de
     donde salia. */
  /* `scale(1.06)` compensa el aire que muchas fotos de proveedor traen dentro
     del propio archivo: con `contain` ese margen blanco cuenta como imagen y el
     producto acaba ocupando bastante menos marco del que le corresponde. Ampliar
     un 6% recupera presencia y NO recorta nada, porque `contain` sigue
     encajando la imagen entera dentro de la caja. */
  .catalog-card-media img{
    width:100%; height:100%; max-width:100%; max-height:100%;
    object-fit:contain; display:block; padding:0!important;
    transform:scale(1.06); transform-origin:center;
    transition:transform .3s ease;
  }
  /* Zoom sutil + 1px de subida: da vida sin que parezca un efecto barato. */
  @media (hover:hover){
    .catalog-card:hover .catalog-card-media img{transform:scale(1.09) translateY(-1px)}
  }
  .catalog-card-media.is-noimg{flex-direction:column;gap:8px;color:#94a3b8;background:#fff}
  .catalog-card-media .ph-note{font-size:11.5px;color:#94a3b8}

  /* Distintivos: solo los que ya existian (descuento real y novedad por fecha).
     No se añade ninguno inventado. */
  .catalog-card .catalog-discount,
  .catalog-card .catalog-new{
    position:absolute; top:10px; left:10px; z-index:2;
    padding:3px 9px; border-radius:999px;
    font-size:10.5px; font-weight:800; letter-spacing:.02em; line-height:1.6;
  }
  .catalog-card .catalog-discount{background:#dc2626;color:#fff}
  .catalog-card .catalog-new{background:color-mix(in srgb,var(--primary,#7c3aed) 12%,#fff);color:var(--primary,#7c3aed)}

  /* ── ETIQUETAS DE PRODUCTO ─────────────────────────────────────────────── */
  /* Viven aqui y no solo en el componente Blade porque el catalogo arma sus
     tarjetas con Alpine y nunca incluye ese parcial: dejando el CSS alli, las
     etiquetas salian sin estilo en /tienda. Los colores NO se fijan aqui —
     cada etiqueta trae los suyos en linea, resueltos por `EtiquetasProducto`. */
  .catalog-card .pe-etqs{
    position:absolute; z-index:3; display:flex; flex-direction:column; gap:5px;
    pointer-events:none; max-width:calc(100% - 20px);
  }
  .catalog-card .pe-etqs--izquierda{top:10px; left:10px; align-items:flex-start}
  .catalog-card .pe-etqs--derecha{top:10px; right:10px; align-items:flex-end}
  .catalog-card .pe-etq{
    display:inline-flex; align-items:center; gap:4px;
    padding:4px 9px; border-radius:999px;
    font-size:11px; font-weight:600; line-height:1.45; letter-spacing:.01em;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%;
    box-shadow:0 1px 2px rgba(15,23,42,.06);
  }
  /* Con etiqueta a la izquierda, el % de descuento se aparta para no solaparse. */
  .catalog-card .pe-etqs--izquierda ~ .catalog-discount,
  .catalog-card .pe-etqs--izquierda ~ .catalog-new{left:auto; right:10px}
  @media (max-width:640px){
    .catalog-card .pe-etqs{gap:4px; max-width:calc(100% - 14px)}
    .catalog-card .pe-etqs--izquierda{top:7px; left:7px}
    .catalog-card .pe-etqs--derecha{top:7px; right:7px}
    .catalog-card .pe-etq{font-size:10px; padding:3px 7px}
  }

  /* El ojo de vista rapida se retira: la foto Y el nombre ya llevan al detalle,
     asi que era un tercer camino al mismo sitio ocupando la esquina.
     Se oculta en CSS y no se borra del marcado a proposito — el mismo parcial
     lo usan otras tiendas y `openQuickView` sigue en uso desde el catalogo. */
  /* RESTITUIDA: ocultarla dejaba muerto el interruptor "Vista rapida" del
     constructor — el cliente lo encendia y no pasaba nada. Quien decide si
     aparece es la tienda, no esta hoja. En movil sigue oculta por las reglas
     de la plantilla, que es donde estorbaba. */
  .catalog-card .catalog-quickview{display:flex}

  /* ── ZONA DE INFORMACION ───────────────────────────────────────────────── */
  /* Separador finisimo entre foto e informacion: ordena sin pesar. */
  .catalog-card-body{
    display:flex; flex-direction:column; padding:14px 16px 0; gap:0;
    border-top:1px solid #F1F1F4;
  }
  .catalog-card-category{
    font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.04em;
    color:#6B7280; margin-bottom:6px;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
  }
  /* Dos lineas reservadas SIEMPRE: si el alto dependiera del texto, un nombre
     corto subiria su precio y su boton y la fila quedaria escalonada. */
  .catalog-card-name{
    /* Solo la forma prefijada. Declarar ademas `line-clamp` estandar hacia que
       el navegador resolviera `display:flow-root` y el recorte no se aplicara. */
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
    /* Dos lineas EXACTAS reservadas (2 × 1.3), no `em` sobre la caja: asi el
       precio y el boton caen a la misma altura en toda la fila. */
    min-height:calc(2 * 1.3 * 15.5px); font-size:15.5px; font-weight:700; line-height:1.3;
    color:#111827; text-decoration:none; margin-bottom:14px;
    transition:color .18s ease;
  }
  .catalog-card-name:hover{color:var(--primary,#7c3aed)}

  /* `Precio a solicitud` en UNA linea: al partirse en dos empujaba el boton y
     rompia la alineacion de la fila. Si no cabe, se recorta con puntos.
     Sin caja, sin fondo y sin borde: resalta por tipografia y color. */
  .catalog-card-prices{display:flex;align-items:baseline;flex-wrap:wrap;gap:7px;margin-bottom:14px}
  .catalog-card-prices .quote-price{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
  .catalog-card-prices .catalog-card-price,
  .catalog-card .catalog-card-prices .quote-price{
    font-size:17px; font-weight:800; letter-spacing:-.02em; color:var(--primary,#7c3aed);
    padding:0; background:none; border:0; border-radius:0;
  }
  .catalog-card .catalog-card-prices .quote-price{display:inline-flex;align-items:center;gap:7px}
  /* Icono de etiqueta delante del texto. Se dibuja con `mask` sobre
     `currentColor` y no con un SVG en el marcado porque este texto se pinta
     desde cuatro sitios distintos (tarjeta, catalogo y las dos rejillas de la
     portada): asi toma solo el color de cada tienda sin tocar el marcado. */
  .catalog-card .catalog-card-prices .quote-price::before{
    content:""; display:block; flex:0 0 16px; width:16px; height:16px;
    background:currentColor;
    -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z'/%3E%3Ccircle cx='7' cy='7' r='1.4'/%3E%3C/svg%3E") center/contain no-repeat;
            mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z'/%3E%3Ccircle cx='7' cy='7' r='1.4'/%3E%3C/svg%3E") center/contain no-repeat;
  }
  .catalog-card-prices .catalog-card-compare{font-size:12.5px;color:#94a3b8;text-decoration:line-through}
  .catalog-card-prices .catalog-card-tax-note{font-size:11px;color:#94a3b8}

  /* ── PIE: BOTON Y ENLACE ───────────────────────────────────────────────── */
  /* `margin-top:auto` empuja el pie al fondo: es lo que alinea los botones de
     toda la fila aunque un nombre ocupe una linea y el de al lado dos. */
  .catalog-card-actions{
    display:flex; flex-direction:column; gap:9px;
    margin-top:auto; padding:0 16px 14px;
  }
  .catalog-card .catalog-card-inquiry{
    display:flex; align-items:center; justify-content:center; gap:8px;
    width:100%; min-height:44px; padding:0 12px; box-sizing:border-box;
    background:#25D366; color:#fff; border:0; border-radius:9px;
    font-size:14px; font-weight:600; text-decoration:none; line-height:1;
    white-space:nowrap; overflow:hidden;
    transition:background-color .2s ease, transform .2s ease, box-shadow .2s ease;
  }
  @media (hover:hover){
    .catalog-card .catalog-card-inquiry:hover{
      background:#1FB855; transform:translateY(-1px);
      box-shadow:0 4px 12px rgba(37,211,102,.32);
    }
  }
  .catalog-card .catalog-card-inquiry:active{transform:scale(.99)}
  /* El icono no se encoge; el que cede es el texto. */
  .catalog-card .catalog-card-inquiry svg{width:17px;height:17px;flex:0 0 17px}

  /* Boton de carrito (tiendas en venta directa): mismo cuerpo que el de
     consulta, con el color de la tienda. */
  .catalog-card .catalog-card-action{
    display:flex; align-items:center; justify-content:center;
    width:100%; min-height:44px; padding:0 12px; box-sizing:border-box;
    border-radius:9px; cursor:pointer;
    background:var(--primary,#7c3aed); color:#fff; border:0;
    font-size:14px; font-weight:600; line-height:1;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    transition:filter .2s ease, transform .2s ease, box-shadow .2s ease;
  }
  @media (hover:hover){
    .catalog-card .catalog-card-action:hover{
      filter:brightness(.94); transform:translateY(-1px);
      box-shadow:0 4px 12px color-mix(in srgb,var(--primary,#7c3aed) 30%,transparent);
    }
  }
  .catalog-card .catalog-card-action:active{transform:scale(.99)}

  /* Enlace discreto al detalle: sin caja ni fondo, para no competir con el
     boton. Con un separador finisimo arriba en vez de flotar suelto: asi se lee
     como el pie del card y no como un elemento olvidado debajo del boton. */
  .catalog-card-more{
    display:flex; align-items:center; justify-content:center; gap:5px;
    padding-top:9px; border-top:1px solid #F1F5F9;
    font-size:12.5px; font-weight:500; color:#64748B; text-decoration:none;
    transition:color .18s ease;
  }
  .catalog-card-more:hover{color:var(--primary,#7c3aed)}
  /* La flecha avanza al pasar: refuerza que lleva a otro sitio. */
  .catalog-card-more svg{transition:transform .18s ease}
  @media (hover:hover){.catalog-card-more:hover svg{transform:translateX(3px)}}

  /* Las reglas de la plantilla llevan `#storefront-main` y `!important`; para
     que estas ganen tienen que igualar esa especificidad. Sin esto la flecha
     salia gigante y el precio conservaba su pastilla. */
  #storefront-main .catalog-card .catalog-card-more{
    display:flex!important; align-items:center; justify-content:center; gap:5px;
    font-size:12.5px; font-weight:500; color:#64748B; text-decoration:none;
    min-height:0; background:none; border:0;
    padding:9px 0 0!important; border-top:1px solid #F1F5F9!important;
  }
  #storefront-main .catalog-card .catalog-card-more:hover{color:var(--primary,#7c3aed)}
  #storefront-main .catalog-card .catalog-card-more svg{
    width:14px!important; height:14px!important; flex:0 0 14px;
  }
  #storefront-main .catalog-card .catalog-card-prices .quote-price{
    display:inline-flex!important; align-items:center; gap:7px;
    padding:0!important; background:none!important; border:0!important;
    font-size:17px; font-weight:800; letter-spacing:-.02em; color:var(--primary,#7c3aed);
  }
  /* Sustituye al punto gris de la pildora antigua. */
  #storefront-main .catalog-card .catalog-card-prices .quote-price::before{
    content:""!important; display:block!important; flex:0 0 16px;
    width:16px!important; height:16px!important;
    background:currentColor!important; border-radius:0!important; opacity:1!important;
    -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z'/%3E%3Ccircle cx='7' cy='7' r='1.4'/%3E%3C/svg%3E") center/contain no-repeat;
            mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z'/%3E%3Ccircle cx='7' cy='7' r='1.4'/%3E%3C/svg%3E") center/contain no-repeat;
  }

  /* ── CARRUSEL DE DESTACADOS ────────────────────────────────────────────── */
  /* Las reglas de ancho de la plantilla apuntan a `.pf-card`, una clase que
     ninguna tarjeta lleva: por eso no se aplicaban y entraban ocho apretadas
     en la fila. Aqui se apunta a la tarjeta real. El avance automatico cada
     tres segundos ya lo hace el JS de la plantilla. */
  body.featured-view-carousel .pf-grid > .catalog-card{
    flex:0 0 calc((100% - 66px) / 4); min-width:0; scroll-snap-align:start;
  }
  @media (max-width:1279px){
    body.featured-view-carousel .pf-grid > .catalog-card{flex-basis:calc((100% - 44px) / 3)}
  }
  @media (max-width:1023px){
    body.featured-view-carousel .pf-grid > .catalog-card{flex-basis:calc((100% - 22px) / 2)}
  }
  @media (max-width:640px){
    /* Se deja asomar la siguiente para que se vea que hay mas. */
    body.featured-view-carousel .pf-grid > .catalog-card{flex-basis:82%}
  }

  /* ── REJILLA ───────────────────────────────────────────────────────────── */
  /* 4 / 3 / 2 / 1 segun el ancho, con 22px de aire en escritorio. */
  .catalog-product-grid{
    display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:22px; align-items:stretch;
  }
  @media (max-width:1279px){.catalog-product-grid{grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:18px}}
  @media (max-width:1023px){.catalog-product-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:16px}}
  /* Dos por fila mientras quepan de verdad. A 390px caben (≈180px por tarjeta);
     rendirse antes obligaba a recorrer el catalogo de uno en uno en la mayoria
     de moviles. Solo por debajo de 360px pasa a una. */
  @media (max-width:640px){.catalog-product-grid{gap:12px}}
  @media (max-width:359px){.catalog-product-grid{grid-template-columns:1fr!important}}

  /* Tablet ancha (2 columnas, tarjeta de ~480px): la foto CRECE, no encoge.
     Con la altura de escritorio el marco quedaba apaisado y el producto se
     veia diminuto en medio de una franja blanca. */
  @media (max-width:1279px) and (min-width:1024px){
    .catalog-card-media{height:282px;flex-basis:282px}
  }
  /* Tablet: la foto baja un punto pero la tarjeta no se estrecha todavia. */
  @media (max-width:1023px){
    .catalog-card-media{height:224px;flex-basis:224px}
  }

  /* ── MOVIL ─────────────────────────────────────────────────────────────── */
  /* No es el escritorio encogido: se recorta el aire y baja la tipografia,
     pero el boton NO baja de 42px o deja de ser comodo de pulsar. */
  @media (max-width:640px){
    .catalog-card{border-radius:12px}
    .catalog-card-media{height:192px;flex-basis:192px;padding:8px 10px 10px}
    .catalog-card-body{padding:12px 12px 0}
    .catalog-card-name{font-size:14.5px;min-height:calc(2 * 1.3 * 14.5px);margin-bottom:12px}
    .catalog-card-category{font-size:10.5px}
    .catalog-card-prices{margin-bottom:12px}
    .catalog-card-prices .catalog-card-price,
    .catalog-card-prices .quote-price,
    #storefront-main .catalog-card .catalog-card-prices .quote-price{font-size:15px}
    .catalog-card-actions{padding:0 12px 12px;gap:8px}
    .catalog-card .catalog-card-inquiry,
    .catalog-card .catalog-card-action{min-height:42px;font-size:13px}
    .catalog-card-more,
    #storefront-main .catalog-card .catalog-card-more{font-size:12px}
  }
  /* 390px y 360px: con dos columnas cada tarjeta baja de ~180px. */
  @media (max-width:400px){
    .catalog-card-media{height:174px;flex-basis:174px;padding:6px 8px 8px}
    .catalog-card-body{padding:10px 10px 0}
    .catalog-card-name{font-size:13.5px;min-height:calc(2 * 1.3 * 13.5px);margin-bottom:10px}
    .catalog-card-category{font-size:10px;margin-bottom:5px}
    .catalog-card-prices{margin-bottom:10px}
    .catalog-card-prices .catalog-card-price,
    .catalog-card-prices .quote-price,
    #storefront-main .catalog-card .catalog-card-prices .quote-price{font-size:14px}
    .catalog-card-actions{padding:0 10px 10px;gap:7px}
    .catalog-card .catalog-card-inquiry{gap:6px;font-size:12.5px}
    .catalog-card .catalog-card-inquiry svg{width:15px;height:15px;flex:0 0 15px}
    .catalog-card .catalog-card-action{font-size:12.5px}
  }

  /* Quien pidio menos movimiento en su sistema no lo recibe. */
  @media (prefers-reduced-motion:reduce){
    .catalog-card,
    .catalog-card-media img,
    .catalog-card .catalog-card-inquiry,
    .catalog-card .catalog-card-action,
    .catalog-card-more,
    .catalog-card-more svg{transition:none!important}
    .catalog-card:hover{transform:none}
    .catalog-card:hover .catalog-card-media img{transform:none}
  }
</style>
