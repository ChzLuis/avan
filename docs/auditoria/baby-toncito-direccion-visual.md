# Baby Toncito — dirección visual

**Reiniciada el 2026-08-09.** Memoria de diseño: se completa fase por fase y no
se cambia de rumbo sin actualizarla.

Ruta: `c:\xampp\htdocs\avan` · Proyecto 20 · https://babytoncito.arindg.com

## El negocio (dato, no diseño)

Ropa de algodón pima para bebé, venta minorista y mayorista por docena, atención
y cierre por WhatsApp, tres mundos de navegación: General / Niño / Niña.

---

## FASE 0 — Línea base (2026-08-09)

### Inventario real

| | |
|---|---|
| Productos | 61 · 52 con foto · **solo 3 con stock > 0** |
| Precios | S/ 0,00 a S/ 55,00 · casi todo a S/ 20,00 |
| Categorías | Niño (32 prod.) y Niña (29) · 15 subcategorías, **5 vacías** |
| Perfiles | Solo Niño y Niña en BD; "General" es el estado sin perfil |
| Hero por perfil | La tabla admite `hero_desktop_path` y `hero_mobile_path`: **ambos vacíos** |
| Secciones activas | hero · benefits · featured_categories · featured_products · about_preview · info_strip · faq · cta_banner |
| Rutas | `/` `/tienda` `/tienda/nino` `/tienda/nina` `/nosotros` `/contacto` — todas 200 |

Subcategorías vacías: Bodies (384), Pijamas (385), Pantalones (387) en Niño;
Polos y Blusas (392), Pantalones y Leggings (393) en Niña.

### Diagnóstico de diseño

**1. La tienda nunca enseña un bebé.** Todo es prenda sobre fondo blanco: el
hero, los mundos, las tarjetas, el catálogo. Quien vende ropa de bebé vende la
imagen del hijo con esa ropa puesta; aquí se vende tela. Es el problema de
fondo, y ninguna corrección de CSS lo arregla.

**2. El hero no es un hero.** Tres recortes de prenda flotando sobre un degradado
menta, cortados por abajo. 526 px de alto sobre una ventana de 900: ni llena ni
inspira. En móvil las tres tarjetas quedan diminutas y arrinconadas.

**3. Las fotos de producto no forman colección.** Conviven fondos blancos, crema
con nubes y rosa. La escala de la prenda cambia de una foto a otra. En una fila
de cuatro se nota como un error.

**4. Contradicción de cifras en la tienda.** El contador dice "26 productos", el
filtro dice "Todos 57", la base tiene 61 y Niña aparece como 25 cuando son 29.
Cuatro números distintos para lo mismo.

**5. Filtros incompletos.** La barra lateral solo ofrece Niño y Niña, pero la
portada enlaza a las 15 subcategorías. El filtro de precio (0–50) no aporta nada
en un catálogo donde casi todo cuesta S/ 20.

**6. Contacto tiene fallos visibles.** Una etiqueta "Email" huérfana bajo el
área de mensaje; el mapa muestra el distrito de Carabayllo entero, sin local; y
se enseñan dos números distintos (929286873 arriba, 51946122581 en el cuerpo).

**7. Incoherencia de titulares.** Unos llevan subrayado tipo rotulador ("Elige
su mundo", "Venta por mayor", "Tallas y cuidado") y otros antetítulo en
versalitas ("COLECCIÓN", "NUESTRO MATERIAL"). Dos sistemas conviviendo.

**8. Paleta desbordada.** Teal de marca, naranja del buscador, verde de
WhatsApp, rosa y celeste de los perfiles, y un degradado teal→oliva en el CTA
final que ensucia. Nada de eso está decidido, se ha ido acumulando.

**9. Nosotros existe pero no está en la navegación.** Solo se llega desde el pie.

**10. Stock:** 58 de 61 productos con stock 0 y aun así con botón de compra.
Dato de negocio, no de diseño — anotado para consultar al cliente.

---

## Identidad general

**Boutique suave.** Debe transmitir: algodón, cuidado, calidad de materia prima,
cercanía peruana. No debe transmitir: guardería, plástico, plantilla, saturación.

**Principio rector:** *la ropa pone el color, el sitio pone el aire.* Los
packshots ya son coloridos (rosa, celeste, camel, gris). Si el sitio también lo
es, compiten y gana el ruido. Por eso el sitio va en neutros cálidos.

**Lo que se retiró de la identidad anterior** (era el tema `soft-kids`):
lunares de fondo, subrayado de crayón sobre los títulos, tarjetas que rotaban
−0,6° al pasar el ratón y contornos discontinuos en las colecciones. Todo eso
leía guardería. Sustituido por: fondo crema liso, un único sistema de titular y
elevación vertical discreta en hover.

## Perfil General

Mundo neutro: el teal de marca `#2C7A7F` y la prenda sobre blanco. El catálogo
manda; no lleva banda propia porque no la necesita.

## Perfil Niño

Azul apagado `#3E7CA6`, botón `#2F6285` (6,6:1). **No es el celeste chillón de
antes**: un azul saturado compite con las prendas y arrastra la tienda al
territorio "guardería".

Título propio ("Mundo Niño") y texto propio. Fotografía pendiente:
`BT-HERO-NINO-01` (escritorio y móvil, ambos configurables desde
`Catálogo > Perfiles de catálogo`).

**Corregido:** dentro del perfil, la miga decía "Todos los productos" y el filtro
contaba 26 cuando la rejilla mostraba 11.

## Perfil Niña

Rosa apagado `#C4718A`, botón `#A8556F` (5,0:1). Mismo criterio que Niño: el
rosa fucsia anterior competía con la ropa rosa del catálogo.

Fotografía pendiente: `BT-HERO-NINA-01`.

## Tipografía

**Dos familias, no cuatro.** Había Poppins, Nunito, Baloo 2 e Inter conviviendo.

| | |
|---|---|
| Títulos | **Jost** 600 — geométrica limpia, sin la burbuja de Baloo 2 |
| Texto | **Nunito** 400/600 — redondeada, aporta la calidez |

Se cargan los pesos 400–800: los titulares se pedían a 800 y, sin ese peso, el
navegador sintetizaba una negrita falsa. Afectaba a las tres tiendas.

**Escala** (razón 1,25 sobre base 16):

| Rol | Tamaño |
|---|---|
| H1 | clamp(32, 3.2vw, 44) |
| H2 | clamp(26, 2.4vw, 33) |
| H3 | 24 |
| Bloque menor | 19 |
| Cuerpo | 16 |
| Secundario | 14 |
| Micro (categoría de tarjeta, notas) | 12 |

Antetítulo: 12 px, 700, versalitas, `letter-spacing .14em`, color de marca.
Es el **único** adorno permitido sobre un titular.

## Color

Neutro cálido + una acción + una oferta. Nada más.

| Rol | Valor | Contraste sobre blanco |
|---|---|---|
| Tinta | `#2A2520` | 15,2:1 |
| Texto | `#5A5148` | 7,8:1 |
| Apagado | `#7A7168` | 4,8:1 |
| Fondo | `#FFFFFF` | — |
| Fondo suave | `#FAF7F2` | — |
| Borde | `#EAE3D9` | — |
| **Marca / acción** | `#2C7A7F` | 5,0:1 |
| **Oferta** | `#C2543F` | 4,5:1 |
| Niño | `#3E7CA6` · botón `#2F6285` | 6,6:1 |
| Niña | `#C4718A` · botón `#A8556F` | 5,0:1 |

**Eliminados:** el naranja `#F2A15C` del buscador y de las ofertas (que además
era el mismo color para dos cosas distintas), el celeste `#38bdf8` que se colaba
en la banda de confianza, y los azules `#1d4ed8` / `#2563eb` del menú, que venían
de un valor por defecto y no de la marca.

Todo está en `project_settings`, editable desde el constructor. Los colores de
perfil viven en `store_catalog_profiles`: nada hardcodeado.

## Fotografía

**Dos tipos, y no se mezclan en la misma retícula.**

1. **Packshot** — prenda sobre blanco `#FFFFFF`, formato 3:4, misma escala en
   todas, sin props ni fondos ilustrados. Es el catálogo.
2. **Lifestyle** — bebé con la ropa puesta, luz natural, interior cálido y
   desenfocado. Es el hero, los mundos, las categorías y Nosotros.

Hoy la tienda solo tiene packshots, y ni siquiera homogéneos. De ahí salen las
peticiones de recurso visual.

## Encabezado

Tres filas con una función cada una: **mundo** (dónde estoy) → **buscar y
comprar** (qué hago) → **navegar** (a dónde voy).

Sin fotografía salvo el logotipo. Pendiente `BT-LOGO-01`: el sello circular es
ilegible a 64 px y hoy se acompaña del nombre en tipografía, que es un apaño.

**Se eliminó** el teléfono repetido en la barra superior: aparecía dos veces en
el mismo encabezado.

## Navegación

Una tienda de 15 subcategorías no puede tener un menú de tres enlaces. Se activa
el **panel de categorías** (Niño y Niña con sus subcategorías) y entra
**Nosotros**, que existía y solo se alcanzaba desde el pie.

Las subcategorías **sin producto no se muestran**: cinco llevaban a un listado
vacío. Se corrigió en el filtro general, así que afecta también al filtro
lateral y al menú móvil.

## Hero

**Con fotografía** (`BT-HERO-01` cargada): una sola imagen a sangre, el mensaje
sobre la mitad izquierda y un degradado que garantice contraste. Es el patrón de
cualquier tienda de moda infantil y no necesita trucos.

**Sin fotografía** — el estado de hoy: **hero tipográfico centrado**. Se probaron
tres composiciones con recortes de prenda (pegatinas, pantalla partida, mosaico)
y las tres eran parches para esconder el fondo blanco del packshot. Un packshot
no sostiene un hero. Mientras no haya foto de campaña, el texto y el aire son
más dignos que un collage forzado.

Es una opción del constructor (`Inicio > Hero > Si todavía no has subido ninguna
foto`), no una decisión escondida en el código.

## Inicio

**Objetivo:** llevar de "qué es esto" a "quiero esta prenda" en el menor
recorrido, y dejar claro que hay venta por mayor.

**Protagonista:** el producto. Una tienda de 61 prendas no necesita explicarse,
necesita enseñar.

**Orden:** hero · confianza · elige su mundo · **nuestros favoritos** · algodón
pima · venta por mayor · tallas · CTA. El bloque de producto va antes que
cualquier bloque de texto.

**Correcciones de composición:**
- El acordeón de tallas se centraba mientras su titular quedaba a la izquierda:
  dos ejes en la misma sección. Ahora comparten eje y va a **dos columnas**
  (586 → 351 px).
- El botón del CTA final era del color del propio fondo: 3,3:1, casi invisible.
  Sobre banda de marca, botón blanco con texto de marca (5:1).
- El texto de "algodón pima" pasaba de 100 caracteres por línea. Limitado a 68.
- Los botones llevaban una sombra de color a 12 px y 26 %: en un botón pequeño
  se leía como un segundo botón pegado detrás.

## Categorías

Las que no tienen producto no se muestran — ni en el menú, ni en el panel de
categorías, ni en el filtro lateral. Cinco subcategorías llevaban a un listado
vacío.

Las tarjetas de "Elige su mundo" van a imagen a sangre con el nombre sobre un
degradado. Pendiente `BT-MUNDO-NINO-01` y `BT-MUNDO-NINA-01`: hoy muestran un
packshot apaisado con la prenda cortada.

## Tienda

Un solo título, un solo contador y **una sola regla**: se muestra lo que se
puede comprar (disponible y con precio). Antes convivían cuatro cifras
distintas para lo mismo.

Los filtros que no filtran nada no se muestran: el de ofertas desaparece si no
hay ninguna, y el de precio si todas las prendas cuestan casi lo mismo. Un
filtro que lleva a cero resultados es una promesa incumplida.

## Tarjetas

Foto 3:4 protagonista sobre blanco, categoría en versalitas de 12 px, nombre a
dos líneas fijas, precio a 20 px, mayorista en una línea y botón ancho con
WhatsApp compacto al lado. "Vista rápida" solo al pasar el ratón.

La coherencia depende del material: ver `BT-PACK-FONDO`.

## Producto

Un CTA que manda, mayorista como bloque secundario, stock discreto y garantías
bajo el botón. Relacionados en **una fila cerrada de cuatro** (antes eran seis
en una rejilla de cinco: uno quedaba solo en la fila siguiente).

El selector de tallas existe y bloquea la compra si no se elige — pero **ningún
producto tiene tallas cargadas**. Es un vacío de datos, no de diseño.

## Minorista

Recorrido: llegada → mundo → producto → carrito → WhatsApp. Sin fricciones
detectadas salvo la ausencia de tallas.

## Mayorista

Integrado como boutique, no como panel administrativo: una línea en la tarjeta,
un bloque secundario en la ficha y un aviso en la barra superior. La tienda no
debe parecer un sistema de pedidos mayorista.

## Carrito

Dos columnas, resumen a la derecha, estado vacío propio con su CTA.

**"Envío: Gratis" es una promesa comercial.** En una tienda que coordina el
envío por WhatsApp no es cierta, así que el texto de coste cero es configurable
(`Ventas > Textos`). Baby Toncito muestra "Por coordinar".

## Nosotros
_pendiente — FASE 16_

## Contacto

Datos a la izquierda, formulario a la derecha, mapa de la dirección configurada.

**Pendiente del cliente:** en la web conviven dos teléfonos distintos
(929286873 en la cabecera y 51946122581 en el cuerpo). Hay que decidir cuál es
el bueno.

## Pie de página

Cuatro columnas sobre fondo oscuro, sellos de compra segura y medios de pago.

**Corregido:** el botón flotante de WhatsApp tapaba el último medio de pago en
todas las páginas; ahora la fila reserva su carril.

## Mobile

No es el escritorio encogido.

- Cabecera en dos filas, buscador debajo.
- Barra de confianza **en dos líneas**: iba en una sola sin envolver y el primer
  y el último argumento salían cortados por los bordes.
- "Elige su mundo" a **una columna**: a dos, cada tarjeta medía 170×106 y la
  prenda era irreconocible.
- Catálogo a dos columnas, botón de compra a todo el ancho.
- Cero desbordamiento horizontal en las cinco vistas.

---

## FASE 17 — Giro "infantil con oficio" (2026-08-14)

**Decisión del cliente:** quiere la tienda más infantil y llamativa. Se acordó
el punto medio (opción "infantil con oficio"): calidez y color SIN volver a los
adornos de guardería que se retiraron (lunares, crayón, tarjetas giratorias).
Esto MATIZA el principio "la ropa pone el color, el sitio pone el aire": el
sitio ahora pone color, pero pastel y por bandas, nunca compitiendo en
saturación con las prendas.

**Implementado (todo por el constructor):**
- Nuevo modo de fondos **"Bandas pastel"** (`section_background_mode=pastel`):
  tres colores que rotan sección a sección con blanco entre medio, configurables
  (`pastel_band_1/2/3`). Baby Toncito: crema `#FDF6EF`, rosa `#FDF0F2`,
  celeste `#EEF6F9`. La banda de confianza pasó del gris frío `#f8fafc` al rosa.
- **Franja animada con color propio** (`ticker_bg_color`): de acento oscuro a
  rosa pálido `#FBE3E8` con tinta oscura automática por luminancia.
- Tipografía y radios ya estaban (Baloo 2 títulos, radio `rounded`).
- **Red doble para el revelado de secciones**: repaso por scroll en JS +
  animación CSS de respaldo a los 8s. Una sección quedaba en opacity 0 si el
  IntersectionObserver se la saltaba.

**Segunda pasada del mismo día — "se ve muerto":** el cliente pidió botones más
fuertes. Cabeceras de perfil en pastel (Niño `#DCEEF9`/tinta `#10405C`, Niña
`#FBE7F0`/tinta `#6B1F41`) y botones/acciones saturados encima: Niño
`#1266E3` (5,2:1), Niña `#D6336C` (4,6:1), acción general `#0A7A80` (5,1:1).
Regla: **el pastel viste, el saturado acciona.** Chips del menú actualizados a
los mismos tonos.

**Hallazgos del recorrido completo** (producto, contacto, nosotros, ofertas,
búsqueda):
- El chip "Ofertas" llevaba a una rejilla vacía (0 ofertas reales). Ahora los
  chips de ranura respetan el mismo guard que los accesos históricos y se
  ocultan solos sin ofertas/novedades (fix en `preset-shell`, aplica a todas).
- Los perfiles no tenían asignadas las categorías creadas después (415/416/417)
  y el filtro lateral no las listaba. Asignadas.
- Franja animada: nuevo control de posición (`ticker_position`), BT en modo
  "pie" para que no tape la última fila al navegar.
- **Tercera pasada — "que los colores combinen en todas las secciones":**
  alineados los ajustes que quedaban fuera del sistema (aviso naranja `#e75d13`,
  azul de fábrica `#2563eb` en categorías destacadas, gris frío, hero negro,
  popup marrón, pie apagado → ahora `#075E63→#0A7A80`). Y se encontró un bloque
  CSS "estilo más serio y corporativo" que clavaba la barra superior en azul
  marino con `!important` para TODAS las tiendas: ahora el degradado sale del
  color configurado (`announcement_bg`). Tecsist/MegaHogar sin cambio porque ya
  lo tenían configurado.
- **Pendiente del cliente (sigue):** dos teléfonos distintos en Contacto
  (WhatsApp 51946122581 vs Llámanos 929286873).
- Pendiente menor: texto "Email" suelto bajo el formulario de contacto.

**Recategorización mirando las fotos** (respaldo en
`_backup_bt_categorias_20260814`): las familias "casaca + pantalón" estaban
repartidas entre Conjuntos y Casacas según el capricho del nombre, con
productos visualmente idénticos en categorías distintas. Regla nueva: la pieza
que define el producto manda — casacas/abrigos con pantalón → Casacas y
Abrigos; conjuntos de vestir → Conjuntos. Resultado: Niño 14/4/16, Niña 4/3/25
(Conjuntos/Enterizos/Casacas). Overoles siguen en Enterizos.

---

## Referencias investigadas

| Fase | Referencia | Principio útil | Cómo se adapta | Qué NO se copia |
|---|---|---|---|---|
| — | — | — | — | — |
