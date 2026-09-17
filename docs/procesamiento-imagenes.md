# Procesamiento automático de imágenes

**Regla de fondo: la tienda se adapta a la imagen, no al revés.** El usuario sube
la foto que tiene —la del móvil en vertical, la del proveedor en panorámico, la
captura de WhatsApp— y el sistema genera la versión que necesita cada componente.
Nadie recorta, redimensiona ni "prepara" nada a mano antes de subir.

## Estado antes de este trabajo (auditoría del 2026-08-19)

| Hecho | Dato |
|---|---|
| Imágenes en producción | 1 098 archivos, 362 MB, media de 337 KB |
| Archivos por encima de 500 KB | 204 |
| Puntos de subida | 7 |
| Puntos con algún procesado | 1 (fotos de producto) |
| Generación de WebP | ninguna: `ImageVariants::webp()` servía un `.webp` que nadie creaba |
| `srcset` real | ninguno; los 6 del catálogo eran `<source media>` de escritorio/móvil |

Además había un fallo abierto: `StoreBuilderController` llamaba a
`SquareImage::jpegBytes()`, un método que no existe ni en local ni en
producción, así que **subir la imagen de una categoría desde el constructor
lanzaba un error fatal**.

## Arquitectura

Todo pasa por `App\Support\Imagen`:

| Clase | Papel |
|---|---|
| `PerfilImagen` | Qué necesita cada hueco de la tienda: proporción, estrategia, anchos, calidad y `sizes`. |
| `ProcesadorImagenes` | Lee, endereza, encuadra y escribe. Único sitio con lógica de imagen. |
| `ResultadoImagen` | Lo que quedó en disco: original, variante por defecto y juego de anchos. |
| `Img` | Cómo se pinta: `<picture>` con AVIF/WebP, `srcset`, `sizes`, `width`/`height` y `loading`. |
| `ImagenNoProcesable` | Error con mensaje en cristiano para enseñárselo al usuario. |

### Perfiles

| Perfil | Proporción | Estrategia | Anchos | Por qué |
|---|---|---|---|---|
| `producto` | 1:1 | contain + recorte de fondo, ocupación 92% | 400 / 800 / 1200 | El producto se ve entero, centrado y **al mismo tamaño que el de al lado**. |
| `categoria` | 1:1 | contain + recorte de fondo, ocupación 86% | 200 / 400 / 800 | Es un icono grande: se lee de un vistazo. |
| `logo` | la del origen | libre | 200 / 400 / 800 | La marca no se recorta ni se rellena, y conserva su transparencia (sale PNG). |
| `banner` | 3:1 | cover centrado | 768 / 1280 / 1920 | El hueco es fijo; el recorte sale del centro. |
| `banner_movil` | 4:5 | cover centrado | 480 / 768 | Es una imagen distinta, no un recorte agresivo de la de escritorio. |
| `seccion` | la del origen | libre | 640 / 1024 / 1600 | Galerías y testimonios no tienen una proporción única. |
| `miniatura` | 1:1 | contain | 120 / 240 | Nunca se sirve la original de alta resolución. |
| `generico` | la del origen | libre | 800 / 1600 | Techo de tamaño para cualquier otra subida. |

Cambiar un ancho o una proporción aquí y relanzar `imagenes:regenerar --forzar`
reencuadra una tienda entera sin volver a pedir las fotos.

### Recorte de fondo: por qué el cuadrado no bastaba

Encajar la foto en un cuadrado **no** hace que la rejilla se vea pareja. Muchas
fotos de catálogo traen su propio margen horneado, así que dentro del cuadro el
producto sale a tamaños distintos. Medido en Baby Toncito antes del recorte:

```
2911  80%   2912  60%   2913  92%   2914  78%
2920  76%   3013  91%   3014  82%   3015  73%
→ el producto ocupaba entre el 60% y el 92% del lienzo
```

Los perfiles `producto`, `categoria` y `miniatura` separan primero el motivo del
fondo (`cajaUtil()`): el color de fondo se deduce de las **cuatro esquinas** —si
no coinciden entre sí es que la foto llega al borde y no hay nada que recortar— y
después se escala el motivo a una ocupación fija del lienzo.

```
después del recorte: mínimo 89%, máximo 90%  (dispersión: 32 puntos → 1 punto)
```

**La foto es el producto, no un lienzo.** El perfil `producto` recorta el fondo y
entrega el producto con su proporción; **no** lo mete en un cuadrado. La forma del
hueco la decide la tarjeta, que cada tienda configura en el constructor
(`card_image_ratio`: 1/1, 4/3 o 3/4). Hornear un cuadrado dentro de un marco 4/3
desperdiciaba el 25% del ancho: el mismo producto salía un cuarto más pequeño en
Tecsist que en Baby Toncito, sin más motivo que el lienzo.

Medido tras el cambio, sobre el lado mayor del marco:

| Tienda | Marco | Producto ocupa | Antes |
|---|---|---|---|
| Tecsist | 4/3 | 92% (mediana) | 67% |
| MegaHogar | 4/3 | 70% (mediana) | 67% |
| Baby Toncito | 1/1 | 92% | 90% |
| Market Huacho | 1/1 | 92% | 90% |

En marco cuadrado **todos** llenan el 92%. En 4/3 lo llenan los productos anchos;
los verticales solo pueden llenar el alto, que es el 67% del lado largo. MegaHogar
se queda en el 70% porque casi todos sus productos son más altos que su marco:
cambiar su tarjeta a 1/1 los subiría al 92%, y es un ajuste del constructor.

**El formato de salida lo decide el origen, no el perfil.** Antes "sin color de
fondo declarado" se leía como "conserva el alfa", y al quitarle el lienzo blanco
al perfil de producto, fotos de cámara sin una sola transparencia empezaron a
escribirse en PNG. Ahora se muestrea el alfa real del archivo de entrada.

**La foto de la tarjeta va en `position:absolute` dentro de su enlace.** El
enlace es una rejilla que centra (`place-items:center`): su fila se dimensiona
por el contenido, así que el `height:100%` de la foto era circular y el
navegador caía a la altura natural de la imagen — desbordaba el marco y el
`overflow:hidden` la decapitaba por arriba y por abajo. **El fallo existía desde
siempre**: con las fotos cuadradas el corte se comía el margen blanco horneado y
no se veía; al ajustar la foto al producto, se comía el producto. Mismo arreglo
en `pf-media`, `qv-media` y `pdp-main-img`, que compartían el patrón.
(`home-cat-media` se queda como está: la variante `style-minimal` se dimensiona
por su imagen y el absoluto la colapsaría.)

**Una sola variable controla el aire: `--foto-aire`.** Vale `4%` —porcentaje, no píxeles, para que escale con la tarjeta— y se usa en las
14 reglas que muestran una foto de producto o categoría (tarjeta, destacados,
ficha, miniaturas, carrito, buscador, checkout). Antes cada una traía su propio
relleno en píxeles fijos —once reglas, de 4 a 28 px—, así que la misma foto salía
con un aire distinto según dónde se enseñara y además cambiaba con el ancho del
contenedor. El margen lo pone el procesador y es proporcional.

**`contain`, nunca `cover`.** Quedaban dos reglas sueltas con `cover` sobre la
foto de la tarjeta, tapadas solo por un `!important` posterior: quitar ese
`!important` habría cambiado todo el catálogo a recortar sin que nadie lo notara.
Ya están alineadas en `contain`.

**No sumes aire por CSS.** La tarjeta llevaba además `padding:18px` en el `<img>`,
que se sumaba al 8% del procesador y dejaba el producto en un 74% del marco —con
un aire en píxeles fijos que descuadraba distinto según el ancho de la tarjeta.
Bajado a 4px: el aire lo pone el procesador y es proporcional.

**Lo que la normalización no puede igualar** es la forma del producto. Medido en
Tecsist, con el lado mayor ya uniforme al 89–92%:

| Producto | Ancho | Alto |
|---|---|---|
| Torre HP 260 G4 MINI | 28% | 89% |
| Laptop HP 15 | 89% | 67% |
| Impresora Epson L3250 | 92% | 53% |

Una torre estrecha y una impresora ancha no pueden llenar igual un cuadrado sin
recortarlas. La única alternativa es `cover`, que las llenaría a costa de cortar
parte del producto.

Salvaguardas: si toda la imagen es fondo, si las esquinas no concuerdan o si el
recorte sale por debajo del 2% del área (una firma, una mota, ruido de JPEG), se
deja la foto tal cual.

### Qué hace el procesador

1. **Lee** JPG, PNG, WebP, GIF, BMP y AVIF. SVG y vídeo pasan de largo sin tocarse.
2. **Endereza** por EXIF: sin esto, media tienda sale tumbada, porque el móvil
   graba siempre igual y deja la orientación real en los metadatos.
3. **Rechaza** por encima de 80 MP con un mensaje entendible, en vez de tumbar PHP
   (una foto de 12000 × 9000 son 432 MB de RAM).
4. **Encuadra** según el perfil. Nunca deforma y **nunca agranda**: estirar 200 px
   a 1200 no añade detalle, solo peso y borrosidad.
5. **Escribe** cada ancho en JPG/PNG + WebP (+ AVIF donde el PHP lo trae).
6. **Archiva el original** en el disco privado `storage/app/private/originales/`,
   fuera de la web. Nunca se sobrescribe: es la fuente de toda regeneración.

### Cómo se sirve

```blade
{!! \App\Support\Imagen\Img::etiqueta($url, 'producto', ['alt' => $p->name]) !!}
```

Sale un `<picture>` con `<source type="image/webp">`, `srcset` por anchos,
`sizes` del perfil, `width`/`height` (para que el texto no salte al cargar) y
`loading="lazy"` —o `eager` + `fetchpriority="high"` si se pasa `['eager' => true]`
para la imagen del hero.

Si una foto no tiene variantes en disco, `Img` devuelve un `<img>` normal con la
URL de siempre: **nunca se rompe una imagen**.

### La ficha de producto pide el ancho que enseña

La ficha servía el **archivo original** en los tres sitios: la foto grande, cada
miniatura de 60 px y los productos relacionados. 587 KB por foto para un cuadro
de 480 px, y otro tanto por cada miniatura.

`Img::deAncho($url, 800)` para la foto grande, `deAncho($url, 400)` para
miniaturas y relacionados. Si ese ancho no existe cae en `mejor()`, y si no hay
nada, en el original: nunca se rompe.

```
peso de las fotos de una ficha: 3 380 KB → 361 KB (89% menos)
```

La galería además va topada a `max-width:480px`: sin tope, en un monitor ancho la
foto pasaba de 580 px y la primera pantalla de la ficha era solo imagen.

### El punto por el que sale la foto de producto

Meter `Img::etiqueta` en las plantillas **no basta**: la rejilla de `/tienda` la
pinta Alpine desde un JSON con un `<img>` pelado, y el endpoint `?format=json`
que pagina también. Por eso la URL se resuelve en el accesor
`Product::getMainImageUrlAttribute()`, del que cuelgan la ficha, las tarjetas, el
JSON del catálogo, el carrito y el panel:

```php
return \App\Support\Imagen\Img::mejor($url, 'producto');
```

`Img::mejor()` devuelve la mejor variante que exista en disco y, si no hay
ninguna, la URL de siempre. Es lo que garantiza que la tienda se vea pareja
aunque el marcado de turno sea un `<img>` sin `<picture>`.

> **Trampa de CSS:** el `<picture>` se mete entre el contenedor y el `<img>`, así
> que rompe cualquier `display:grid`/`flex` del padre. La plantilla lleva
> `picture{display:contents}` para que no cuente en el layout.

## Fotos alojadas fuera

Un catálogo importado por conector guarda la URL del proveedor tal cual. Esa foto
no pasa por el procesador —ni recorte, ni encuadre, ni variantes— así que en la
rejilla se ve distinta a todas las demás, y la tienda depende de que un servidor
ajeno siga sirviéndola.

```bash
php artisan imagenes:importar-externas            # todos los proyectos activos
php artisan imagenes:importar-externas 10 --simular
```

Descarga una vez, procesa como cualquier foto propia y apunta la ficha a la
copia. Si la descarga falla deja la URL externa intacta: mejor una foto de fuera
que ninguna.

En Distribuidores GABDE (140 fotos de Unsplash) se trajeron 88; las otras 49
apuntaban a una foto que Unsplash ya borró (404) y siguen rotas.

## Favicons

`ImageVariants::favicon()` genera el icono de pestaña desde el logo. Dos mejoras
(2026-08-19), verificadas con capturas a 16/32/64 px:

- **Logos "icono + texto"** (muy apaisados o verticales): se parten por los
  huecos en blanco y se queda el trozo correcto. El candidato se elige por
  **densidad de tinta**, no solo por forma: un párrafo de texto apilado también
  da una caja cuadrada, pero es casi todo aire (le pasó a Market Huacho, que
  eligió "Market/Huacho/Express" en vez de la M).
- La plantilla `ecommerce` enlazaba el archivo crudo (un 1080×400 en la pestaña);
  ahora pasa por el generador como computienda.

- **Zoom al núcleo**: si la ventana central (62% del lado, centrada en el
  centroide de tinta) concentra ≥45% de la tinta, el favicon se acerca ahí. Un
  sello con anillo de texto enseña su emblema; un logo compacto (la M, un disco
  lleno) no se toca porque su tinta está repartida.

El sufijo del archivo generado es `-favicon128.png`; cambiarlo invalida los
favicons viejos sin borrarlos a mano. Verificación: pestañas simuladas de Chrome
oscuro/claro a 16 px y marcadores a 32 px, con captura headless.

## Comando de mantenimiento

```bash
php artisan imagenes:regenerar                 # todo lo pendiente
php artisan imagenes:regenerar --simular       # enseña qué haría
php artisan imagenes:regenerar --carpeta=logos # solo una carpeta
php artisan imagenes:regenerar --forzar        # rehace lo ya hecho
```

No mueve ni renombra el archivo original —así ninguna URL guardada en base de
datos deja de funcionar—; solo escribe los hermanos `-400`, `-800`… a su lado.
Es reejecutable.

## Resultado del backfill (2026-08-19, producción)

```
Primera pasada:  783 procesadas, 0 fallaron.  338,8 MB → 106,8 MB (68% menos).
Con recorte:     664 productos + 13 categorías reprocesados, 0 fallaron.
                 289,5 MB → 60,0 MB (79% menos).
2 889 variantes generadas.
```

Ocupación comprobada después, en muestra aleatoria de 60 fotos de todas las
tiendas: mínimo 86%, mediana 90%, máximo 92%. Ninguna fuera del rango 80–96%.

Verificado en tienda.tecsist.net, megahogar.org y babytoncito.arindg.com: las
tres sirven `<picture>` + `srcset` + `width`/`height` sin errores nuevos en el log.

El disco total sube (variantes + originales archivados), que es el precio de
poder reencuadrar sin volver a pedir las fotos:

| Carpeta | Peso |
|---|---|
| `storage/app/public` | 469 MB |
| `public/uploads` | 73 MB |
| `storage/app/private/originales` | 342 MB |

## Pendiente

- **Las URLs de producto apuntan a `arindg.com` aunque la tienda tenga dominio
  propio.** Viene de que en base de datos se guardó la URL absoluta del momento
  de la subida. Funciona, pero obliga al navegador a abrir una segunda conexión
  TLS contra otro dominio. Es previo a este trabajo y arreglarlo pide reescribir
  las URLs guardadas.
- **AVIF solo se genera en local**: el PHP de producción no lo trae compilado. El
  `<picture>` cae a WebP sin que se note. Recompilar GD con AVIF daría otro
  20–30% de ahorro.
- La plantilla `ecommerce` todavía no usa `Img::etiqueta`; sigue con su
  `webpUrl()` propio.
