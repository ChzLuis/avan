# Agrupar los colores de un mismo modelo

Una tienda de ropa carga cada color como un producto aparte. Es lo correcto para
el inventario y para la factura —cada color tiene su stock y su ficha—, pero en
el escaparate llena la rejilla con siete fotos casi iguales: el cliente no ve el
catálogo, ve un color.

## El dato de partida (Baby Toncito, 2026-08-19)

```
66 productos → 12 modelos
65 de los 66 llevan la coletilla " - Color X"
cada modelo: 1 sola categoría y 1 solo precio
```

El color no hay que adivinarlo: ya venía estructurado en `options.colors`.

## Cómo funciona

`App\Storefront\AgrupadorModelos` elige **un representante por modelo** y el
resto viajan con él como opciones. **No cambia nada en la base de datos**: cada
color sigue siendo su producto, con su stock, su precio y su ficha.

- **Clave del modelo** = el nombre sin la coletilla del color. Se recorta usando
  el color declarado en `options.colors`, no un guion cualquiera: partir por el
  primer `-` juntaría `Polo - Talla 4` con `Polo - Talla 6`, que no son el mismo
  artículo. La categoría entra en la clave, porque dos modelos pueden llamarse
  igual en secciones distintas.
- **Representante** = el que tiene foto (una tarjeta sin imagen es una tarjeta
  perdida) y, a igualdad, el de menor `sort_order`.
- **Al buscar no se agrupa.** Quien escribe "rosado" quiere ver el rosado, no el
  modelo representado por el azul.

## Dónde se aplica

| Sitio | Cómo |
|---|---|
| `/tienda` (rejilla reactiva y render de servidor) | `CatalogQueryService::paginate()` restringe a los representantes |
| Portada (destacados, novedades, ofertas, categorías) | `PublicController::prepararCatalogo()` acota `$vendible` |
| Tarjeta | `variantes` en el shape de la tarjeta; vacío si el modelo es de un solo color |

## La tarjeta

Cada tarjeta lleva su propio estado Alpine `vp`, inicializado con **los mismos
datos que el HTML estático**: sin JS se ve exactamente igual que antes (SEO
intacto) y con JS el selector de color puede reemplazarla en el sitio.

Tocar un color cambia a la vez la foto (con su `srcset` y su WebP), el enlace, el
precio y **el id que va al carrito**, así que "Agregar al carrito" añade siempre
el color que se está viendo.

Las muestras son **la foto de cada color**, no un círculo de pantone: en ropa el
color no es un código, es cómo queda la prenda.

## Interruptor

Constructor → Apariencia → **Agrupar los colores de un mismo modelo**
(`catalog_group_models`). Apagado por defecto: las tiendas que no cargan colores
como productos separados no notan nada.

## Resultado

```
/tienda:  60 productos → 11 modelos (4, 4, 6… colores por tarjeta)
portada:   8 tarjetas, 8 modelos distintos (antes salía 3 veces el mismo)
```

Tecsist y MegaHogar, con la agrupación apagada, siguen exactamente igual.

## Fusión de datos (lo que se hizo en Baby Toncito)

Agrupar solo la vista deja los 66 productos vivos en el panel: el cliente sigue
editando seis fichas para cambiar un precio. Por eso existe además:

```bash
php artisan productos:fusionar-modelos 20 --simular   # ver antes
php artisan productos:fusionar-modelos 20             # hacerlo
```

Deja **un producto por modelo**: los colores pasan a `options.colors`, cada foto
de color queda mapeada en `options.color_images`, todas las fotos van a la
galería del modelo, las tallas se juntan sin repetirse y la descripción cambia
"Color: Rojo." por "Colores: Azul, Rojo.".

**Se niega a borrar un producto que ya aparece en un pedido**: su id vive en el
historial y borrarlo dejaría la venta señalando a la nada.

Resultado en producción (2026-08-19), con respaldo previo en
`products_backup_fusion_20` y `product_images_backup_fusion_20`:

```
66 productos → 12 modelos
54 productos borrados, 0 saltados
82 fotos: las 82 conservadas
```

Tras la fusión, los colores ya no salen de productos hermanos sino de
`options.color_images` del propio producto (`AgrupadorModelos::coloresPropios()`).
Como el id ya no cambia al elegir color, **el color viaja al carrito como
atributo, junto a la talla** (`"4 · Rosado"`), y la vista rápida lo pide antes de
dejar añadir.

## Dónde se elige el color

**En la tarjeta, en ningún sitio.** Se probó una fila de miniaturas bajo el
nombre y se retiró: no aportaba nada y ensuciaba la rejilla.

El color se elige en la **vista rápida**, junto a la talla, y viaja al carrito
como atributo (`"4 · Rosado"`). Es obligatorio: tras la fusión un modelo es un
solo producto, así que sin esa elección el pedido no diría qué color se compró.

## La ficha de producto: las miniaturas son el selector

Tras la fusión, la galería de la ficha tiene una foto por color, así que las
miniaturas hacen de selector (2026-08-20): tocar una cambia la foto grande, fija
el color —rotulado debajo: "Color: Beige floral"— y ese color viaja al carrito
junto a la talla. Con varios colores, el botón de compra lo exige antes de
añadir, igual que la vista rápida.

Qué color enseña cada foto se resuelve comparando su nombre sin extensión ni
ancho contra `options.color_images`; una foto de galería que no es de ningún
color (un detalle, una etiqueta) simplemente no fija color.

## Dos trampas del selector de color

Tras la fusión **todos los colores son el mismo producto**, así que el id deja de
servir para distinguirlos. Dos cosas se rompieron por eso:

1. El bucle iba indexado por `:key="v.id"`. Con seis colores compartiendo id,
   Alpine los tomaba por duplicados y **pintaba una sola miniatura**. Ahora la
   clave es el color.
2. La marca de activo comparaba `v.id === vp.id`, que tras la fusión es cierto
   para todos. Ahora se compara por color, y el color activo se deduce de qué
   variante trae la foto que se está viendo (`colorActivo()`).

Esa comparación mira el nombre **sin extensión**: la tarjeta pide la foto en
`.webp` y la variante la trae en `.jpg`; comparando la URL entera no coincidían
nunca y la tarjeta marcaba un color que no era el de la foto.

## El catálogo PDF entiende los modelos (2026-08-20)

El modal de "Catálogo en PDF" gana la sección **Contenido del catálogo**
(`?content=`), separada de la Presentación. Dos opciones:

- **main** — solo la foto principal (el PDF de siempre).
- (`variants` existió como modo intermedio con minis de color y se **eliminó** a
  petición del usuario: un catálogo vende con fotos grandes, no con
  muestrarios. El valor se sigue aceptando como alias de `full` para no romper
  enlaces guardados.)
- **full** (por defecto y recomendado) — **cada color se despliega como un
  producto propio**: su tarjeta, su foto en grande (variante de 800) y el
  nombre "Modelo — Color". La fusión no se toca: es solo presentación del PDF;
  el panel y la tienda siguen viendo un producto por modelo.

Por presentación: Detallado = minis 52 px con nombre debajo; Estándar = minis
42 px + lista de nombres; Compacto = "N colores: …" sin minis ilegibles (y el
modal avisa que Completo en Compacta genera más páginas). Tiendas sin colores:
cero contenedores nuevos (protegido por test). Toda mini lleva `onerror` que la
esconde si su foto no carga: una imagen rota jamás aborta el PDF.

Trampa encontrada: las rutas de galería a veces traen el prefijo `/storage/` y
a veces no; anteponerlo a ciegas daba `storage/storage/...` y la mini moría en
el onerror.

## Límites conocidos

- El contador dice "11 productos" cuando en realidad son 11 **modelos**. Es
  coherente con lo que se ve, pero el texto podría mejorarse.
- La agrupación se calcula en PHP sobre los productos vendibles del proyecto: en
  un catálogo de 1 200 productos son 1 200 filas ligeras por petición. Aceptable
  hoy; si alguna tienda grande la enciende, conviene una columna `model_key`
  indexada.
- El filtro lateral y los contadores por categoría siguen contando colores, no
  modelos.
