# Checklist maestro — rediseño de Corporación Mega Hogar

**Corregido el 2026-08-10 con la vara nueva.** `[x]` ya no significa "revisado y
correcto". Significa **investigado → replanteado → rediseñado → implementado →
abierto en navegador → revisado con los ojos → refinado → responsive → funcional**.

Si la estructura sigue siendo esencialmente la misma y solo cambió el CSS, la
fase es `[~]`, por bien que se vea.

Proyecto 19 · https://arindg.com/corporacion-megahogar-ccf4 · dominio propio
`megahogar.org` · plantilla `computienda.blade.php` (compartida con Tecsist y
Baby Toncito).

---

## AUDITORÍA DE LO YA HECHO

Aplicada la condición nueva a las once fases que había marcado `[x]`:

| Fase | Qué hice de verdad | Veredicto |
|---|---|---|
| 0 · Inventario | Descubrimiento, no diseño | **[x]** legítima |
| 1 · Sistema visual | Corregí tokens y derivé la paleta del logo. **No toqué tipografía, escala ni ritmo** | **[~]** |
| 2 · Header | Orden del menú, color de chips, alto del logo. **Misma estructura de 3 filas** | **[~]** |
| 3 · Navbar | Solo verifiqué. **Cero cambios** | **[~]** |
| 4 · Hero | Alto y opacidad del velo. **Misma composición** | **[~]** |
| 5 · Inicio | Solo cambió el bloque de producto | **[~]** |
| 6 · Categorías | **Cero cambios**, conservación pura | **[~]** |
| 7 · Catálogo | Cambié el orden (real), pero la vista es la misma | **[~]** |
| 8 · Buscador y filtros | Solo verifiqué | **[~]** |
| **9 · Tarjeta** | **Eliminé el área de imagen y rehíce la jerarquía** | **[x]** |
| **10 · Ficha** | **De dos columnas a una; fuera el hueco de 652×702** | **[x]** |
| 11 · Carrito | Texto de envío y miniatura | **[~]** |

**Solo 9 y 10 sobreviven.** El resto fue corrección y conservación, no rediseño.

---

## FASE 0 — Inventario  [x]

| | |
|---|---|
| Productos | **1127** · 1108 con precio · **0 con fotografía** |
| Categorías | 8 raíz (con foto de ambiente) + 32 subcategorías |
| Colores reales del logo | azul petróleo `#184860` · dorado `#C0A860` |
| Rutas | `/` `/tienda` `/nosotros` `/contacto` — 200 |

**El problema dominante:** 1127 productos sin foto. Todo el rediseño se apoya en
lo único visual que hay: los 8 ambientes, el nombre, la categoría y el precio.

---

## FASE 1 — Sistema visual  [~]

**Hecho:** paleta derivada del logo (muestreada del PNG, no inventada):
`primary #0F3548` (azul del logo oscurecido a 13:1) + `accent #C0A860` (dorado
del logo tal cual). Textos neutros cálidos. Menú en marca, no en azul rey.

**Replanteado (2026-08-10):**
- [x] **Tipografía decidida, no heredada: Playfair Display + Lato.** Montserrat +
      Inter es la pareja más usada de la web: dice "plantilla". Una serif de
      contraste alto en los titulares es el lenguaje del retail de muebles y se
      entiende con el dorado; los nombres de electrodoméstico viven en Lato, así
      que no hay choque. Cambio visible desde la primera pantalla.
- [x] **Quinto control pisado, corregido:** el preset `commerce` forzaba
      `font-family:var(--font-body)!important` en el titular del hero. La tienda
      elegía letra de títulos y el hero la ignoraba: la primera pantalla
      contradecía al resto de la página. Titular del hero también más grande
      (40 → 52 px de tope).
- [x] **Ritmo:** espaciado de sección `dense` → `comfortable`. Una mueblería
      necesita aire; el ritmo apretado es de tienda de conveniencia.
- [x] **Tres niveles de superficie** (antes dos): página `#FBF8F4`, panel cálido
      `#F2EAE0`, tarjeta blanca. Permite agrupar sin recurrir a bordes.
- [x] Paleta derivada del logo por muestreo
- [ ] Escala tipográfica propia — pendiente, hoy usa la genérica de la plantilla

**Investigación (2026-08-10):** el patrón dominante en retail de muebles es
**navegación por ambiente primero** y tipo de producto después, con mega menú que
muestre las subcategorías de un vistazo. Confirma que los 8 ambientes deben ser
la navegación principal (FASE 6) y no una fila más de la portada.

## FASE 2 — Header  [~]

**Hecho:** orden del menú, chips en paleta, alto del logo.

**Falta:** la estructura sigue siendo tres filas idénticas a las de Tecsist y
Baby Toncito. Hay que decidir qué encabezado necesita una mueblería con 1127
productos y 40 categorías — probablemente uno donde el acceso a ambientes y el
buscador pesen más, y el teléfono deje de competir con el carrito.
- [ ] Investigar encabezados de retail de muebles
- [ ] Replantear composición y jerarquía
- [ ] Implementar, capturar, criticar, refinar

## FASE 3 — Navbar y navegación  [~]

**Hecho:** nada. Solo verifiqué que el mega menú funciona.
- [ ] Replantear: con 8 ambientes y 32 subcategorías, ¿es un panel de texto lo
      correcto, o el menú debe mostrar los ambientes con su fotografía?
- [ ] Decidir la relación entre "Categorías" y el menú principal
- [ ] Implementar, capturar, criticar, refinar

## FASE 4 — Hero  [~]

**Hecho:** alto 500 → 580, velo 50 % → 34 %.
- [ ] Replantear la composición. Hoy es el patrón genérico "foto de fondo +
      texto a la izquierda"
- [ ] Resolver que los ambientes apoyen al hero en vez de competir con él
- [ ] Implementar, capturar, criticar, refinar

## FASE 5 — Inicio  [~]

**Hecho:** el bloque de producto dejó de ser cajas grises.
- [ ] Inventariar las 9 secciones y, por cada una: objetivo comercial, si se
      queda, si se fusiona, si desaparece, y qué composición nueva necesita
- [ ] Reconstruir la narrativa completa, no reordenar la existente
- [ ] Implementar, capturar, criticar, refinar

## FASE 6 — Categorías y ambientes  [~]

**Hecho:** nada.
- [ ] Replantear cómo se descubren los 8 ambientes. Hoy es una rejilla de 4×2 de
      tarjetas iguales; en una tienda sin fotos de producto, esta sección
      debería ser la experiencia principal, no una fila más
- [ ] Resolver el acceso a las 32 subcategorías
- [ ] Implementar, capturar, criticar, refinar

## FASE 7 — Tienda / catálogo  [~]

**Hecho:** orden intercalado por categoría (abría con 8 zapateros seguidos).
- [ ] Replantear la experiencia completa para 1108 productos sin foto:
      densidad, agrupación, comparación, recorrido
- [ ] Implementar, capturar, criticar, refinar

## FASE 8 — Buscador y filtros  [~]

**Hecho:** nada. Solo verifiqué.
- [ ] Replantear: sin imágenes, el buscador y los filtros son la navegación
      principal, no un accesorio lateral
- [ ] Implementar, capturar, criticar, refinar

## FASE 9 — Tarjeta de producto  [x]

**Rediseño real.** Se eliminó el área de imagen y se rehízo la jerarquía.

| | Antes | Después |
|---|---|---|
| Alto | ~380 px | **216 px** |
| Contenido | caja gris + "FOTO EN CAMINO" | filete dorado, categoría, nombre, **precio a 24 px** |

Opción del constructor (`cards_no_photo_style`). Automático: con foto vuelve al
formato normal. Tecsist y Baby Toncito intactas.

## FASE 10 — Ficha de producto  [x]

**Rediseño real.** De dos columnas a una de 760 px centrada; fuera un hueco
blanco de 652×702. Página de 2214 a 1512 px.

## FASE 11 — Carrito  [~]

**Hecho:** "Envío: Gratis" → "Por coordinar" (era una promesa falsa) y miniatura
vacía oculta.
- [ ] Replantear la composición del carrito y del cierre de pedido

## FASE 12 — Nosotros  [~]

**Recompuesto (rediseño real, no retoque).** La rejilla lateral 2fr+3fr dejaba la
columna izquierda a un tercio del alto de la derecha: **medio metro de vacío**
bajo el botón mientras misión y visión desbordaban al lado. Y presentaba lo único
propio de esta empresa como **dos tarjetas con borde**, el formato más genérico
que existe.

Ahora: introducción a ancho completo con medida de lectura (68ch), y misión y
visión debajo en dos columnas **sin caja**, con filete dorado arriba. El botón
perdió el recuadro vacío que lo envolvía.

- [ ] Falta fotografía del local para que deje de ser una página solo de texto
- [ ] Falta llevar los datos duros (14 años, distribuidor oficial Paraíso) a
      jerarquía visual propia en vez de dentro del párrafo de misión
## FASE 13 — Contacto  [~]

**Reconstruido (rediseño real).** Era exactamente lo que el brief prohibía:
"datos flotando en una página vacía" — cinco párrafos con la etiqueta en negrita,
sin jerarquía y sin nada pulsable.

Ahora cada dato es una **acción con su área de pulsación**, en este orden:
WhatsApp destacado en verde (que es como estas tiendas atienden de verdad),
llamar con `tel:`, cómo llegar a Google Maps, horario y correo.

Verificado sirviendo desde el servidor (17 bloques) y renderizado en Tecsist y
Baby Toncito. La captura de MegaHogar salió de caché del navegador.

- [ ] El mapa muestra todo Huancavelica sin marcador del local: no sirve para
      llegar. Necesita coordenadas exactas.
- [ ] Falta revisar en móvil
## FASE 14 — Conversión y WhatsApp  [ ]
## FASE 15 — Footer  [ ]
## FASE 16 — Mobile 390×844  [ ]
## FASE 17 — Tablet 768×1024  [ ]
## FASE 18 — Desktop 1440×900  [ ]
## FASE 19 — Constructor AVAN  [ ]
## FASE 20 — Regresiones  [ ]
## FASE 21 — QA  [ ]
## FASE 22 — Review  [ ]
## FASE 23 — Capturas y cierre  [ ]

---

## Cómo se trabaja de aquí en adelante

Por cada fase, sin excepción:

1. Investigar referencias del sector
2. Replantear la composición **antes** de tocar código
3. Implementar la solución nueva
4. Abrir en navegador y capturar
5. Criticar con los ojos
6. Refinar
7. Verificar responsive y que Tecsist y Baby Toncito sigan en 200
8. Solo entonces `[x]`

**Prohibido** cerrar una fase con "está bien, lo conservo".

---

## Controles del constructor pisados por los presets

Patrón detectado en `section-preset-commerce`. Antes de dar por bueno cualquier
ajuste, verificar el valor computado en el navegador.

| Control | Qué lo anulaba | Estado |
|---|---|---|
| Alto del logo | `height:auto` + tope 96 px | corregido |
| Columnas del catálogo | `auto-fill minmax(220px)` | corregido |
| Alto del hero | `min-height:480px!important` | corregido |
| Borde de tarjeta | ID + `!important` | corregido |

## Pasada 2026-08-14 — colores, botones y escaparate

| Hallazgo | Solución | Estado |
|---|---|---|
| "Lo más vendido" con 8 tarjetas SIN foto (selección manual con IDs de productos que ya no existen; solo 13 de 1106 productos tienen imagen) | Selección manual actualizada a los 8 fotografiados: 3 TVs LG, colchón Paraíso, cabecera queen, caja china, almohada visco, caballete. Además `StorefrontContextBuilder`: los destacados automáticos ahora priorizan con-foto (todas las tiendas) | corregido |
| Botón primario del hero: blanco sobre dorado (2,2:1) | Tinta calculada por luminancia del acento (`--accent-ink`): oscura sobre dorado, 8:1 | corregido |
| Botón fantasma del hero invisible sobre foto clara | Velo oscuro traslúcido rgba(10,15,26,.38) + blur: legible sobre cualquier foto | corregido |
| Hover del primario dorado clavado a mano (#B98A32) | Oscurece el acento configurado con color-mix | corregido |
| Chip "Ofertas" llevaba a rejilla vacía (`hp_nav_force_offers=1` con 0 ofertas) | Forzado apagado; el chip reaparece solo cuando existan descuentos | corregido |
| Banda "Todos los productos" gris (primario petróleo al 8%) | La banda tinta con el ACENTO (`--accent-hex` al 9%): dorada en MH, celeste en Tecsist, aguamarina en BT. Ojo: `color-mix` no acepta `var(a,var(b))` anidado ni funciona dentro de `linear-gradient` en este Chromium — el tinte del gradiente se emite como rgba desde el servidor | corregido |
| Texto "Los muebles que más salen…" con TVs en la lista | "Lo que más sale de nuestro almacén." | corregido |

Pendiente del cliente: fotografiar el catálogo (1093 productos sin imagen).

## Reestructuración 2026-08-14 — "los colores deben combinar con el logo"

Al cliente no le gustaba la combinación (crema + dorado apagado + petróleo,
Playfair Display). Primera propuesta en azul rey se descartó: el logo NO es
azul rey. Colores extraídos del logo real (muestreo de píxeles):
azul acero #184A6A–#2D6585, dorado champán #AC925B–#D5BC7B.

| Rol | Valor | Contraste |
|---|---|---|
| Primario / CTA (Agregar, barra de títulos) | #1F6899 | 6,0:1 con blanco |
| Acento (Categorías, hero, buscador) | #C9A860 | 8,0:1 con tinta oscura |
| Franja | #D5BC7B | tinta oscura automática |
| Tinta fuerte / precios | #12293A | 15,0:1 |
| Texto | #3E5468 | 8,9:1 |
| Oferta | #D7263D | 5,0:1 |
| Pie / aviso | #0E3752 → #1B5480 | tintas automáticas |
| Fondos | blanco + suave azulado #EAF1F6 | — |
| Títulos | Montserrat (antes Playfair Display) | — |
| Radios | rounded (antes small) | — |

Respaldo completo previo en `_backup_mh_settings_20260814` (194 filas).

**Fix sistémico que salió de aquí:** `--accent-ink` (tinta por luminancia del
acento) aplicado a los botones que pintan con acento (`.mega-btn` en
preset-shell, preset-modules y computienda, hero primario). Corrigió de paso
Tecsist (#38BDF8) y Baby Toncito (#2BB3BC), que llevaban blanco sobre acento
claro a ~2:1 sin que nadie lo hubiera reportado.

## Validación integral 2026-08-14 (colores/letras/espacios, medida sección por sección)

- **Causa raíz del descuadre de paleta:** el tema visual `warm-home` re-tokenizaba
  `:root` DESPUÉS de los ajustes del constructor (marrones `#8a7c6c`, superficies
  crema) y pisaba la paleta del logo. Cambiado a `classic` (sin overrides): ahora
  el constructor manda de verdad. 37 fallas de contraste → 0 reales.
- `catalog-card-category` llevaba `#7c899b` clavado → token `var(--muted)`;
  apagado de MH subido a `#54657A` (5,2:1 sobre tarjetas azuladas).
- Sello de hero (`ph-eyebrow`): pastilla azul sin relleno (texto tocando bordes)
  → pastilla dorada con aire y tinta automática.
- "14 años en Huancavelica": azul de enlace suelto → pastilla dorada (sello).
- CTAs del hero: 54px y velo del fantasma a .52 (presencia sin inventar ofertas).
- Sellos de sección (`xs-about-label`): acento sobre blanco (dorado 2,3:1,
  celeste Tecsist 2,2:1) → PRIMARIO en las tres tiendas; la rayita sigue en acento.
- Falsos positivos documentados: textos blancos sobre bandas con degradado (el
  medidor no ve gradientes) y "Arial" que vive dentro de los SVG de los sellos SSL.

## Menú de categorías amigable (2026-08-14)

La rejilla era una pared de enlaces de 20px sin pista de clic. Ahora:
- Título de grupo con su icono (`hp_menu_icons=ambos`, iconos de CategoryIcons
  en chip del primario al 10%).
- Filas de 32px con banda de hover del primario, flecha al pasar y radio.
- "Ver todas (N)" como pastilla con borde; "Ver todo el catálogo" con hover.
- Todo por tokens: en Tecsist y Baby Toncito la misma mejora toma SUS colores.

## Botones del hero + animaciones + menú lateral (2026-08-14, cierre)

- Hero definitivo: primario DORADO del logo con tinta oscura, sombra y
  elevación al pasar; secundario BLANCO nítido con tinta azul. El azul con
  borde dorado venía de TRES reglas peleando (base, commerce y la de
  `buy_button_color` con #storefront-main + !important, que ganaba por ID).
- Aparición de secciones REPARADA para tema classic: el selector interpolaba
  la clase de tema vacía (compilaba roto) y el JS exigía `theme-*`. Las
  tiendas en classic nunca tuvieron animaciones y nadie lo había decidido.
- Menú de categorías en modo LATERAL (el de Tecsist): `hp_mega_layout=lateral`.
- Extensión a TODOS los botones de acción: `buy_button_color` de MH pasa al
  dorado del logo (#C9A860) y la regla del botón de compra ahora calcula su
  tinta por luminancia (blanco fijo daba 2:1 sobre dorado). Sombra y elevación
  al pasar en compra, CTA azules y barra de secciones. Jerarquía: dorado =
  comprar, azul = navegar, verde = WhatsApp.

## Nosotros institucional reestructurado (2026-08-14)

- Hero de marca: barrita dorada de firma bajo el título y los 4 diferenciales
  YA configurados como pastillas de vidrio llenando la mitad derecha (antes
  aire muerto). Solo escritorio y solo sin fotografía; con foto, manda la foto.
- "Conoce nuestra tienda": blanco sobre dorado 1,9:1 → tinta por luminancia en
  la regla de `buy_button_color` (aplica a todos los .button-primary).
- Sellos ("QUIÉNES SOMOS"): descubierto que ni primario ni acento son seguros
  (Tecsist tiene primario cyan CLARO, MegaHogar acento dorado claro) → tinta
  fuerte SIEMPRE, rayita de acento para el color. Aplica a Nosotros y portada
  de las tres tiendas.
- Se respetó la decisión documentada "Misión/Visión sin cajas" (filete dorado).

## Filtros de categoría rotos en las TRES tiendas (2026-08-14)

**Causa:** el panel `catalog-filters.blade.php` usaba `filterCat`/`filterSubCat`
(singular) — variables que NO existen en el estado Alpine. El estado declara
`filterCats`/`filterSubCats` (arrays) y solo esos están vigilados por `$watch`,
que es lo que dispara `catalog:filters-changed`. Al pulsar una categoría se
escribía en una variable huérfana: nunca se refiltraba. Precio, stock y oferta
sí funcionaban porque usan los nombres correctos.

El resto del sistema (backend con `category[]`, chips removibles,
`selectCategory()`, `clearAllFilters()`) ya estaba construido para
multi-selección; solo el panel había quedado en la versión de selección única.

**Arreglo:** radios → casillas con `x-model="filterCats"` / `filterSubCats`.
"Todos los productos" pasa a ser limpiar la selección. Badge muestra el número
de filtros activos.

**Verificado en vivo:** MegaHogar (TVs → Esquinero de Sala), Tecsist (cámaras →
Monitor Samsung), Baby Toncito (Overol → Conjunto de camisa). Subcategorías,
chips y "Limpiar todo" responden. Sin errores de consola.
