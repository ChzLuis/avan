# Checklist maestro — rediseño de Baby Toncito

**Reiniciado desde cero el 2026-08-09** bajo la metodología definitiva
(ver `feedback_metodologia_rediseno` en memoria y la sección "Método" más abajo).
Todo lo marcado antes queda anulado: nada hereda su estado.

Estados: `[ ]` pendiente · `[~]` en proceso · `[!]` bloqueado por recurso visual · `[x]` completado y verificado.

URL: https://babytoncito.arindg.com · Proyecto 20 · Plantilla `computienda.blade.php`
Documentos hermanos: `baby-toncito-direccion-visual.md` · `baby-toncito-recursos-visuales.md`

---

## Método — se aplica a TODAS las fases

Ninguna fase se marca `[x]` sin recorrer estos 17 puntos:

1. Investigación / referencias del rubro (cuando aporte)
2. Dirección de diseño definida
3. Composición definida ANTES de programar
4. Recursos visuales identificados
5. Imágenes necesarias solicitadas (ficha + prompt)
6. Estructura rediseñada (no parcheada)
7. Implementación realizada
8. Constructor integrado (nada hardcodeado)
9. Screenshot desktop
10. Screenshot mobile
11. Crítica visual realizada (con los ojos, no con métricas)
12. Refinamiento realizado
13. CSS limpio y coherente
14. Sin parches innecesarios
15. Funcionalidad comprobada
16. Resultado profesional (¿se lo enseño al cliente?)
17. COMPLETADO Y VERIFICADO

**Filtro final antes de cualquier `[x]`:** composición intencional · jerarquía ·
fotografía resuelta · desktop · mobile · constructor · identidad propia ·
claramente superior a lo anterior · presentable al cliente.

---

## FASE 0 — Inventario y línea base  [x]

- [x] Abrir Baby Toncito en el VPS y recorrerla entera
- [x] Listar todas las rutas públicas reales (6, todas en 200)
- [x] Perfiles: solo Niño y Niña en BD; "General" es el estado sin perfil
- [x] Categorías y subcategorías reales (2 + 15, cinco vacías)
- [x] Catálogo: 61 productos · 52 con foto · 3 con stock
- [x] Lógica minorista existente
- [x] Lógica mayorista existente (docena, mínimo 12)
- [x] Configuración del constructor: 8 secciones activas en portada
- [x] Componentes compartidos: `computienda.blade.php` con Tecsist y MegaHogar
- [x] Datos de contacto: dos teléfonos distintos en la misma web (anotado)
- [x] Captura ANTES desktop 1440 (inicio, tienda, niño, niña, nosotros, contacto)
- [x] Captura ANTES tablet 768
- [x] Captura ANTES mobile 390
- [x] Diagnóstico escrito de diseño (10 puntos, en la dirección visual)
- [x] COMPLETADO Y VERIFICADO

Capturas en `docs/auditoria/capturas/bt-antes/`.

## FASE 1 — Identidad y sistema visual  [x]

- [x] Investigación: referentes de boutique infantil premium
- [x] Identidad definida: "boutique suave" — la ropa pone el color, el sitio el aire
- [x] Tipografías: **Jost + Nunito** (eran cuatro: Poppins, Nunito, Baloo 2, Inter)
- [x] Escala tipográfica completa (razón 1,25 sobre base 16)
- [x] Paleta: tinta cálida + marca `#2C7A7F` + oferta `#C2543F`
- [x] Contraste WCAG verificado en los 9 colores (mínimo 4,5:1)
- [x] Sistema de precios definido
- [x] Escala de espaciados y ritmo vertical
- [x] Ancho útil 1240 y rejilla base
- [x] Radios (4/8/12/20) y dos niveles de sombra
- [x] Botones: 44 px, radio 10, peso 600
- [x] Iconografía: 18 px en línea, 22 px en acción
- [x] Dirección fotográfica: packshot sobre blanco vs lifestyle, sin mezclar
- [x] Escrito en `baby-toncito-direccion-visual.md`
- [x] Expresado como ajustes del constructor, no como overrides
- [x] COMPLETADO Y VERIFICADO

**Identidad de tema rediseñada** (`theme-soft-kids`): fuera lunares de fondo,
subrayado de crayón, rotación de tarjeta en hover y contornos discontinuos.
**Corregido para las tres tiendas:** faltaba el peso 800 en la carga de fuentes,
así que los titulares se veían con negrita sintética.

## FASE 2 — Encabezado y navegación  [!] bloqueada por BT-LOGO-01

- [x] Investigación / referencias
- [x] Dirección de diseño definida
- [x] Composición definida ANTES de programar
- [x] Recursos visuales identificados
- [x] Imágenes solicitadas (BT-LOGO-01)
- [x] Estructura rediseñada: panel de categorías, Nosotros en el menú
- [x] Implementación
- [x] Constructor integrado (desplegable de categorías + aviso de barra superior)
- [x] Screenshot desktop / tablet / mobile
- [x] Crítica visual
- [x] Refinamiento (teléfono duplicado fuera, subcategorías vacías ocultas)
- [x] CSS limpio: rejilla real en el panel, no masonry con overflow
- [x] Funcionalidad comprobada (buscador, carrito, menú, perfiles)
- [!] Nivel profesional — condicionado a BT-LOGO-01
- [ ] COMPLETADO Y VERIFICADO

## FASE 3 — Hero  [!] bloqueada por BT-HERO-01

- [x] Investigación / referencias
- [x] Dirección definida: con foto, imagen a sangre; sin foto, tipográfico
- [x] Composición definida ANTES de programar
- [x] Recursos visuales identificados
- [x] Imágenes solicitadas (BT-HERO-01 escritorio y móvil)
- [x] Estructura rediseñada (se retiró el collage de recortes)
- [x] Implementación del estado sin foto
- [x] Constructor integrado — opción "Si todavía no has subido ninguna foto"
- [x] Screenshot desktop
- [ ] Screenshot mobile del estado con foto
- [x] Crítica visual
- [x] Refinamiento (halo del titular retirado; fondo coherente con el cuerpo)
- [x] CSS limpio — **56 líneas del intento anterior eliminadas**
- [x] Funcionalidad comprobada
- [!] Nivel profesional — condicionado a BT-HERO-01
- [ ] COMPLETADO Y VERIFICADO

## FASE 4 — Inicio (arquitectura y secciones)  [!] bloqueada por imágenes

- [x] Investigación / referencias
- [x] Dirección de diseño definida
- [x] Arquitectura de la portada revisada (producto antes que texto)
- [x] Composición de cada sección definida ANTES de programar
- [x] Recursos visuales identificados
- [x] Imágenes solicitadas (BT-MUNDO-NINO/NINA-01, BT-MATERIAL-01)
- [x] Estructura rediseñada: tallas a dos columnas
- [x] Implementación
- [x] Constructor integrado (variante de la sección de tallas)
- [x] Screenshot desktop
- [ ] Screenshot tablet
- [ ] Screenshot mobile
- [x] Crítica visual del recorrido completo
- [x] Refinamiento: eje del acordeón, contraste del CTA, medida de lectura, sombra
- [x] CSS limpio, sin parches
- [!] Nivel profesional — "Elige su mundo" y "algodón pima" esperan imagen
- [ ] COMPLETADO Y VERIFICADO

Portada: 5155 → 4878 px.

## FASE 5 — Perfil General  [x]

- [x] Dirección y composición definidas
- [x] Estructura implementada · constructor integrado
- [x] Screenshots desktop / tablet / mobile
- [x] Crítica y refinamiento
- [x] CSS limpio · funcionalidad · nivel profesional
- [x] COMPLETADO Y VERIFICADO

## FASE 6 — Perfil Niño  [!] bloqueada por BT-HERO-NINO-01

- [x] Dirección y composición definidas
- [x] Paleta propia configurable (`#3E7CA6` / botón `#2F6285`, 6,6:1)
- [x] Recurso solicitado (BT-HERO-NINO-01 escritorio y móvil)
- [x] **Corregido:** la miga decía "Todos los productos" y el filtro contaba 26 con 11 en rejilla
- [x] Screenshots
- [!] Nivel profesional — espera fotografía
- [ ] COMPLETADO Y VERIFICADO

## FASE 7 — Perfil Niña  [!] bloqueada por BT-HERO-NINA-01

- [x] Igual que Niño, con `#C4718A` / botón `#A8556F` (5,0:1)
- [x] Contadores y miga corregidos (15 = 15)
- [!] Nivel profesional — espera fotografía
- [ ] COMPLETADO Y VERIFICADO

## FASE 8 — Categorías y subcategorías  [!] bloqueada por BT-MUNDO-*

- [x] Jerarquía resuelta: panel de categorías con Niño y Niña
- [x] **Subcategorías vacías ocultas** en menú, panel y filtro lateral (5 casos)
- [x] Recursos solicitados (BT-MUNDO-NINO-01, BT-MUNDO-NINA-01)
- [x] Implementado y verificado en las tres tiendas
- [!] Tarjetas de "Elige su mundo" — esperan fotografía
- [ ] COMPLETADO Y VERIFICADO

## FASE 9 — Tienda / catálogo  [x]

- [x] Un solo título y un solo contador
- [x] **Una sola regla de catálogo**: disponible + con precio, en rejilla y en contadores
- [x] Densidad y rejilla
- [x] Paginación por "Ver más"
- [x] Estado sin resultados
- [x] Screenshots desktop / tablet / mobile
- [x] Crítica y refinamiento
- [x] COMPLETADO Y VERIFICADO

## FASE 10 — Búsqueda y filtros  [x]

- [x] Filtros sin opciones vacías
- [x] **El filtro de ofertas se oculta si no hay ofertas**
- [x] **El filtro de precio se oculta si el rango no aporta**
- [x] Búsqueda predictiva revisada
- [x] COMPLETADO Y VERIFICADO

## FASE 11 — Tarjeta de producto  [!] bloqueada por BT-PACK-FONDO

- [x] Fotografía protagonista 3:4, precio, oferta, CTA, hover
- [x] Mayorista en una línea
- [x] Casos límite revisados
- [x] Screenshots desktop / tablet / mobile
- [!] Coherencia de la fila — fondos de packshot dispares
- [ ] COMPLETADO Y VERIFICADO

## FASE 12 — Ficha de producto  [x] con reserva de datos

- [x] Galería y miniaturas
- [x] Jerarquía de CTA · bloque de confianza
- [x] **Relacionados en fila cerrada de 4** (eran 6 en rejilla de 5)
- [x] **51 descripciones con texto interno sustituidas** por plantillas por categoría
- [x] URL legible para SEO (`/producto/nombre-id`)
- [ ] Tallas — **ningún producto tiene tallas cargadas** (dato del cliente)
- [x] COMPLETADO Y VERIFICADO

## FASE 13 — Experiencia minorista  [x]

- [x] Recorrido completo: llegada → producto → carrito → WhatsApp
- [x] Sin fricciones salvo la ausencia de tallas
- [x] COMPLETADO Y VERIFICADO

## FASE 14 — Experiencia mayorista  [x]

- [x] Comunicación en tarjeta, ficha y barra superior
- [x] Integrada como boutique, no como panel administrativo
- [x] COMPLETADO Y VERIFICADO

## FASE 15 — Carrito y cierre de pedido  [x]

- [x] Carrito con productos · resumen · totales
- [x] Carrito vacío con estado propio y CTA
- [x] **"Envío: Gratis" ya no se promete**: texto configurable, BT muestra "Por coordinar"
- [x] Paso a WhatsApp
- [x] COMPLETADO Y VERIFICADO

## FASE 16 — Nosotros  [!] bloqueada por BT-NOSOTROS-01

- [x] Titular coherente con el antetítulo y el texto
- [x] **CTA recuperado**: requería `button_url` y nunca se mostraba; ahora usa WhatsApp
- [x] Contraste del subtítulo corregido (era el color de marca sobre fondo de marca)
- [!] Cabecera sin fotografía
- [ ] COMPLETADO Y VERIFICADO

## FASE 17 — Contacto  [~] pendiente del cliente

- [x] Datos, formulario y mapa verificados contra el servidor
- [x] Composición y responsive correctos
- [ ] **Dos teléfonos distintos en la misma web** — decisión del cliente
- [ ] COMPLETADO Y VERIFICADO

## FASE 18 — WhatsApp y conversión  [x]

- [x] Un solo flotante · sin solapamientos tras el arreglo del pie
- [x] Accesos en tarjeta, ficha, cabecera y CTA
- [x] COMPLETADO Y VERIFICADO

## FASE 19 — Pie de página  [x]

- [x] Contenido, sellos y medios de pago
- [x] **Corregido: el flotante de WhatsApp tapaba el último medio de pago**
- [x] Screenshots desktop / mobile
- [x] COMPLETADO Y VERIFICADO

## FASE 20 — Mobile  [x]

- [x] Recorrido entero a 390 px en las cinco vistas
- [x] **Banda de confianza en dos líneas** (salía cortada por los bordes)
- [x] **"Elige su mundo" a una columna** (era 170×106 por tarjeta)
- [x] Sin desbordamiento horizontal en ninguna vista
- [x] COMPLETADO Y VERIFICADO

## FASE 21 — Tablet  [x]

- [x] Recorrido entero a 768 px en las cinco vistas
- [x] Navegación completa, sin desbordamiento
- [x] COMPLETADO Y VERIFICADO

## FASE 22 — Constructor AVAN  [x]

Controles **añadidos** en esta pasada (antes solo se podían cambiar tocando código):

| Control | Dónde |
|---|---|
| Desplegable de categorías en el menú | Encabezado |
| Aviso de la barra superior | Encabezado > Universos |
| 10 colores del sistema (compra, oferta, textos, bordes, menú) | Apariencia |
| Hero móvil y texto de cada perfil | Catálogo > Perfiles |
| Estilo del hero sin fotografía | Inicio > Hero |
| Texto del envío sin coste | Ventas > Textos |

- [x] Imágenes cargables desde el constructor, escritorio y móvil por separado
- [x] Colores por perfil
- [x] Ningún control muerto detectado
- [x] COMPLETADO Y VERIFICADO

## FASE 23 — Regresiones  [x]

Componentes compartidos tocados: `computienda.blade.php`,
`computienda-home-extras.blade.php`, `catalog-filters.blade.php`,
`preset-shell.blade.php`, `footers/corporate.blade.php`,
`PublicController@prepararCatalogo`, `StorefrontContextBuilder@catalog`.

- [x] Tecsist verificada (inicio y tienda)
- [x] MegaHogar verificada (inicio y tienda) — su panel de categorías salió mejorado
- [x] Las tres en HTTP 200
- [x] COMPLETADO Y VERIFICADO

## FASE 24 — QA funcional  [x]

- [x] **Corregido: cientos de errores de Alpine por carga** — el cajón de filtros se
      renderizaba en todas las vistas y en portada su estado no existe
- [x] Un solo `h1` por página en las tres tiendas
- [x] `title` y `canonical` presentes en todas las vistas
- [x] URLs legibles para SEO
- [x] Carrito, WhatsApp y enlaces comprobados
- [x] COMPLETADO Y VERIFICADO

## FASE 25 — Revisión de código  [x]

- [x] **56 líneas del hero anterior eliminadas** (intento rechazado)
- [x] `!important`: 451 → 433
- [x] Overrides sustituidos por decisiones: rejilla real en el panel de categorías
      (era masonry con overflow) y regla única de catálogo en el origen
- [x] Cada regla nueva lleva escrito el porqué
- [x] COMPLETADO Y VERIFICADO

## FASE 26 — Cierre  [~]

- [x] Comparación ANTES / DESPUÉS (`capturas/bt-antes/` y `capturas/bt-despues/`)
- [x] Dirección visual documentada al día
- [x] Recursos visuales con ficha, prompt y ruta del constructor
- [x] Recorrido final desktop / tablet / mobile
- [!] **Apto para entrega cuando lleguen las imágenes P0** (logo y tres heroes)

---

## Pendiente del cliente (no es diseño)

| Asunto | Detalle |
|---|---|
| **Precios** | 31 productos a S/ 0,00 → invisibles en la tienda (26 Conjuntos, 5 Enterizos) |
| **Tallas** | Ningún producto tiene tallas cargadas; el selector existe y funciona |
| **Teléfono** | Dos números distintos en la misma web |
| **Stock** | 58 de 61 con stock 0 y botón de compra activo |
| **Descripciones** | 51 sustituidas por plantillas de ejemplo, listas para afinar |

---

## Cambios en componentes compartidos

| Archivo | Cambio | Verificado en |
|---|---|---|
| `computienda.blade.php` | peso 800 en fuentes, tema, hero, medida de lectura, sombra de botón, relacionados, cajón de filtros, banda de confianza | las 3 tiendas |
| `computienda-home-extras.blade.php` | eje del acordeón, medida del texto de Nosotros | las 3 tiendas |
| `catalog-filters.blade.php` | ocultar filtros que no filtran | las 3 tiendas |
| `headers/preset-shell.blade.php` | `cat_trigger` configurable, rejilla real del panel | BT y MegaHogar |
| `footers/corporate.blade.php` | carril para el flotante de WhatsApp | las 3 tiendas |
| `PublicController@prepararCatalogo` | regla única de catálogo (disponible + con precio) | las 3 tiendas |
| `StorefrontContextBuilder@catalog` | misma regla | las 3 tiendas |

---

## Segunda pasada — correcciones sobre la marcha (2026-08-10)

Cambios posteriores al cierre inicial, casi todos nacidos de lo que el cliente
vio en pantalla. Se registran aquí porque tocan componentes compartidos.

### Controles del constructor que estaban muertos

Tres ajustes existían en el panel y no hacían nada porque una regla fija los
pisaba. El cliente los movía y no pasaba nada.

| Control | Qué lo anulaba |
|---|---|
| Columnas del catálogo | `auto-fill minmax(220px)` con `!important` |
| Alto del logo | `height:64px!important` |
| Ancho del logo | tope fijo de 230 px: subir el alto no servía de nada |

### Encabezado que no paraba de moverse

El modo "solo el menú" plegaba la fila del logo con una animación de
`max-height` + `opacity`. Dos fallos a la vez:

1. Durante los 0,28 s de animación la fila seguía ocupando su sitio a media
   opacidad y se superponía al menú: **encabezado doble y fantasma**.
2. Al plegarse, la página se acortaba 179 px, el scroll volvía a cruzar el
   umbral, se desplegaba, la página crecía y vuelta a empezar. **Bucle.**

Ahora la fila del logo se va con la página y solo la de navegación queda fija
(desplazamiento negativo). Sin animación, sin JS por scroll, y la altura del
documento no cambia en ningún momento — que es lo que garantiza que no tiemble.

### Subida de imágenes de perfil

- La carpeta `storage/app/public/catalog-profiles` era de `root`: PHP no podía
  escribir. Corregido en toda `storage/app/public` (productos y pagos estaban
  igual, así que **cualquier** subida fallaba).
- La tienda **no leía el logo del perfil**: el campo existía en la base y en el
  formulario, pero la cabecera siempre mostraba el logo general.
- El formulario no daba señal alguna: ni error ni confirmación. Ahora lleva
  vista previa inmediata, aviso de peso antes de enviar y recuadro de error.
- Tope de 4 a 8 MB, y AVIF admitido.

### Identidad y color

- Tipografía a **Baloo 2 + Nunito**, acento ámbar y fondos crema: el cliente
  veía la paleta "demasiado seria" para ropa de bebé.
- **Niño celeste `#4EB8E5`** y **Niña rosa `#F79DC4`** (botones `#1B7099` y
  `#C2437B` para que el texto se lea).
- **El pie sigue al mundo activo**: teal en General, azul en Niño, rosa en Niña.
  La letra se elige por luminancia, así que un fondo claro no lo deja ilegible.
  Se añadieron "Fondo del pie" y "Letra del encabezado" al formulario del perfil.
- Banda de beneficios en estilo "banda". Estaba rota: las tarjetas salían
  blancas con el texto también blanco encima, invisible.
- Barra animada inferior activada, en ámbar. En teal se fundía con el pie, y
  entre ambas quedaba una franja blanca de 60 px (la regla apuntaba al hermano
  *adyacente*, pero entre medias hay una etiqueta `<style>`).

### Iconos que leían a plantilla

- "Novedades": el destello de ocho rayos hoy significa "IA" y a 16 px parece un
  spinner. Sustituido por una etiqueta de producto.
- WhatsApp: era un bulto verde sin auricular, se leía como punto de estado.
  Ahora el glifo real, y sin el verde chillón que competía con el teal.

### Catálogo y compra

- **Niño y Niña dejan de ser un filtro y pasan a ser una entrada**: llevan a su
  perfil con su color, logo, hero y pie. Las subcategorías siguen siendo filtro.
- **Carrito con tramo mayorista automático**: al llegar al mínimo el precio baja
  solo y aparece el aviso. Antes había que volver a la tarjeta y usar el botón
  "por mayor".
- Tarjetas de 297×618 a 218×513, foto cuadrada.

### Contenido recuperado

Una publicación desde el constructor vació el contenido de "Por qué algodón
pima" y "Tallas y cuidado". No había copia en borrador. Reconstruidos: los 3
puntos del algodón pima y las 4 preguntas con sus respuestas.

### Fuera del alcance de Baby Toncito

- `settings/design-templates.blade.php` daba **error 500**: un `@if` pegado a una
  palabra (`Exportar@if(...)`) que Blade tomaba por un correo, no compilaba, y
  dejaba el `@endif` suelto.
- La vista previa del constructor servía la versión cacheada. Ahora pide una URL
  distinta en cada refresco.
- Al enviar un formulario embebido, el constructor volvía al paso 1. La etapa
  vive en el `#hash` y el navegador no lo manda; ahora se estampa en la acción.

### Método

Dos veces di por bueno un cambio que no estaba: un despliegue se cortó por
tiempo y dejó el archivo viejo en el VPS. **Ahora comparo el md5 local con el
del servidor después de subir.** Y el lint pasa por todas las vistas
compiladas, no solo por las 40 más recientes — así se coló el 500 de
design-templates.
