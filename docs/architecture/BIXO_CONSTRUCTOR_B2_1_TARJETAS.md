# Fase B · B2.1.1–B2.1.3 — Dominio de las tarjetas

Inventario verificado el **2026-08-30** contra código y BD de producción.
**Ninguna clave fue modificada.**

---

## Conclusión, por delante

**`card_style` y `product_card_style` NO son la misma configuración.**

No hay que consolidarlas, no hay tabla de equivalencias que construir y no hay
migración semántica que ejecutar. Son **dos conceptos distintos con nombres
parecidos**:

| | `card_style` | `product_card_style` |
|---|---|---|
| Qué es | **Familia visual de la plantilla** | **Estilo de la tarjeta de producto** |
| Quién lo define | `CatalogTemplates.php`, por plantilla | El comerciante, por tienda |
| Vocabulario | 14 valores: `minimal`, `editorial`, `bold`, `food`, `tech`, `detailed`, `service`, `luxury`, `dark`, `fresh`, `flash`, `nordic`… | 5 valores: `classic`, `tech`, `soft`, `elegant`, `contrast` |
| Renderiza | `StorefrontTheme::family()` → clase `storefront-family-*` en `<body>` | `computienda:76`, `ecommerce:37` → variante de la tarjeta |

La coincidencia de `tech` y `minimal` en ambos vocabularios es **casual**, no
una equivalencia.

---

## B2.1.1 · Inventario de dominio

### Por tienda

| Tienda | `card_style` | `product_card_style` | Editor que pudo escribirlo | Consumidor real | Apariencia actual |
|---|---|---|---|---|---|
| 7 Market Huacho | `minimal` | — | Designer / Diseño clásico / editor incrustado | **ninguno** | `classic` *(fallback)* |
| 10 GABDE | `minimal` | — | ídem | **ninguno** | `classic` *(fallback)* |
| 16 Eskala | — | — | — | — | `classic` |
| 18 Tecsist | `tech` | `tech` | Designer / Constructor | `product_card_style` | **`tech`** |
| 19 MegaHogar | `minimal` | `elegant` | ⚠️ ambos | `product_card_style` | **`elegant`** |
| 20 Baby Toncito | `tech` | `soft` | ⚠️ ambos | `product_card_style` | **`soft`** |
| 21 Electro Jara | — | `contrast` | Constructor | `product_card_style` | **`contrast`** |
| 22 Import Musuhuay | — | — | — | — | `classic` |

### Valores históricos en BD (todos los proyectos, no solo activos)

| Clave | Valor | Proyectos | IDs |
|---|---|---|---|
| `card_style` | `minimal` | 3 | 7, 10, 19 |
| `card_style` | `tech` | 2 | 18, 20 |
| `product_card_style` | `contrast` | 1 | 21 |
| `product_card_style` | `elegant` | 1 | 19 |
| `product_card_style` | `soft` | 1 | 20 |
| `product_card_style` | `tech` | 1 | 18 |
| `catalog_layout` | `grid` | 6 | 7, 10, 18, 19, 20, 22 |
| `catalog_products_view` | `cards` | 2 | 18, 20 |

**No existe ningún valor huérfano ni desconocido.** El vocabulario del editor
incrustado (`classic`, `card`, `bold`, `ghost`) y el del Designer (`modern`,
`bordered`) **no tienen ni un solo dato guardado**: son opciones que nadie usó.

### Consumidores, superficie por superficie

| Clave | Superficie | Qué hace |
|---|---|---|
| `product_card_style` | `computienda.blade.php:76-77` | `in_array(...)` con 5 valores; desconocido → **`classic`** |
| `product_card_style` | `ecommerce.blade.php:37-38` | idéntica lógica y mismo fallback |
| `product_card_style` | `StagePresets.php` (96,122,147,168) | valor por defecto según rubro |
| **`card_style`** | `StorefrontTheme::resolve():22` | ⚠️ **lee `$definition['settings']`, es decir el valor de la PLANTILLA, no el del proyecto** |
| `card_style` | `public-store-runtime.blade.php:142` | entra en `window.BixoStoreSettings` — **y nadie lee ese objeto** |
| `card_style` | `ecommerce.blade.php:3823` | `<select>` del editor incrustado (interfaz, no render) |
| `card_style` | `CatalogTemplates.php` (30+ líneas) | define la familia visual de cada plantilla |

### Qué ocurre ante un valor desconocido

`product_card_style` está protegido por `in_array()` en las dos plantillas: un
valor fuera del vocabulario cae a **`classic`** sin romper nada.
`StorefrontTheme` sanea con `preg_replace` y cae a `minimal`.

---

## B2.1.2 · Vocabulario canónico, elegido por el renderer

**Canónico: `product_card_style`** — es el que ambas plantillas leen para pintar
la tarjeta, y su vocabulario (`classic|tech|soft|elegant|contrast`) es el único
con datos reales y con fallback definido.

**`card_style` a nivel de proyecto no renderiza nada.** Verificado en las tres
superficies posibles:

1. `StorefrontTheme` toma el valor **de la plantilla**, no del proyecto → el
   ajuste guardado por la tienda es ignorado.
2. `window.BixoStoreSettings` se define y **ningún JS lo lee** (barrido en
   `resources/js`, `public/js`, `public/build`).
3. El `<select>` del editor incrustado solo pinta la interfaz.

Esto confirma —y ahora explica— el hallazgo de la revisión 04: **quien cambie
"Estilo de tarjeta" en Diseño clásico o en el Designer no ve ningún cambio**. No
es un bug de resolución: es que ese control escribe una clave que a nivel de
proyecto no consume nadie.

---

## B2.1.3 · Tabla de equivalencias

**No procede.** Al no ser el mismo concepto, no hay mapping que construir.
Documentado en la forma pedida para dejar constancia:

| Origen | Valor antiguo | Valor canónico | Equivalencia | Acción |
|---|---|---|---|---|
| `card_style` (proyecto) | `minimal` | — | `NO_EQUIVALENT` | **no migrar** — concepto distinto, sin render |
| `card_style` (proyecto) | `tech` | — | `NO_EQUIVALENT` | **no migrar** — coincidencia de nombre |
| `card_style` Designer | `modern`, `bordered` | — | `NO_EQUIVALENT` | sin datos guardados |
| Editor incrustado | `classic`, `card`, `bold`, `ghost` | — | `NO_EQUIVALENT` | sin datos guardados |
| `product_card_style` | `tech` | `tech` | `EXACT` | **conservar** |
| `product_card_style` | `soft`, `elegant`, `contrast`, `classic` | idem | `EXACT` | **conservar** |

**Ninguna migración de datos es necesaria en B2.1.**

---

## B2.1.4 · Preservación visual

**Se cumple sin hacer nada.** Las dos tiendas con valores distintos en ambas
claves (MegaHogar `minimal`/`elegant`, Baby Toncito `tech`/`soft`) renderizan hoy
por `product_card_style`. Como esa clave no se toca, **su apariencia no cambia**.

Y como `card_style` a nivel de proyecto no renderiza, retirarlo tampoco cambiará
nada — pero eso se hará en la fase de deuda, no ahora.

---

## Trabajo real que queda en B2.1

Al caerse la consolidación, B2.1 se reduce a **rescatar controles**, sin
migración de datos:

| # | Tarea | Riesgo |
|---|---|---|
| 1 | Llevar a 04 los 4 `catalog_badge_*` *(5 tiendas con datos)* | Bajo |
| 2 | Llevar a 04 `catalog_layout` *(6 tiendas, ningún editor hoy)* | Bajo |
| 3 | Dar a `product_card_style` un editor en 04 *(hoy solo en Apariencia)* | Bajo |
| 4 | `card_style` a nivel de proyecto → `DEPRECATED_CANDIDATE` *(no renderiza)* | — |
| 5 | `catalog_layout` ↔ `catalog_products_view`: inventariar antes de consolidar | — |
| 6 | `btn_cart_text`, `btn_inquiry_text` → **05 Venta**, no 04 | Bajo |

### Nota sobre `catalog_layout`

6 tiendas tienen `grid` y **1 solo consumidor**. Antes de consolidarlo con
`catalog_products_view` (2 tiendas, `cards`) hay que repetir este mismo
inventario: puede ser otro caso de dos conceptos distintos con nombres
parecidos, no una duplicación.


---

# ANEXO · `catalog_layout` — el mismo patrón, otra vez

Aplicado el criterio que fijaste ("documenta antes de diseñar el control"), el
resultado es idéntico al de `card_style`: **`catalog_layout` no es la versión
antigua de `catalog_products_view`, y a nivel de proyecto no lo renderiza nadie.**

| | `catalog_layout` | `catalog_products_view` |
|---|---|---|
| Qué es | **Disposición que declara la plantilla** | **Vista del catálogo por tienda** |
| Lo define | `CatalogTemplates.php` — 30 plantillas | El comerciante |
| Vocabulario | `grid` \| `list` \| `cards` | `cards` \| `compact` |
| Consumidor de render | **NINGUNO** | `computienda.blade.php:102-103` |
| Fallback | — | `cards` |
| Datos | 6 tiendas, todas `grid` | 2 tiendas, `cards` |

### Barrido completo — sin consumidor de render

Buscado `catalog_layout` en `resources/views/public/`, `components/`,
`storefront/`, `layouts/`, `resources/js`, `public/js` y `public/build`:
**cero coincidencias**.

Sus únicas apariciones fuera de las definiciones de plantilla:

- `CatalogTemplates.php:1307` — decide si el grupo "Catálogo" figura en el
  manifiesto. Es **metadato, no render**.
- `CatalogTemplates.php:1388` — lista de campos del manifiesto.

### Decisión

**No se crea editor para `catalog_layout`.** Darle un control sería repetir el
error de la analítica: un ajuste que el comerciante configura y no hace nada.

`catalog_layout` a nivel de proyecto → **`DEPRECATED_CANDIDATE`**, igual que
`card_style`. Los valores de las 6 tiendas se conservan intactos; no se migra
nada hacia `catalog_products_view`, que ya tiene su editor vivo en 04.

**La disposición de la plantilla sigue siendo un concepto válido** — solo que su
propietario es la definición de la plantilla, no el proyecto.

---

# Cierre de B2.1

| Criterio de salida | Estado |
|---|---|
| `product_card_style` con editor oficial en 04 | ✅ **ya lo tenía** — verificado vivo, fuera de `@if(false)` |
| Badges con editor | ✅ **añadidos** — tarjeta "Etiquetas de la tarjeta" en 04 |
| `catalog_layout` con editor | ❌ **no procede** — es inerte; se marca `DEPRECATED_CANDIDATE` |
| Ningún dato perdido | ✅ ninguna clave tocada, ninguna migración de tarjetas |
| Ninguna tienda cambió visualmente | ✅ `product_card_style` intacto; lo demás no renderizaba |
| `card_style` documentado como `DEPRECATED_CANDIDATE` | ✅ |
| Catálogo sin capacidades exclusivas del Designer | ✅ badges rescatados; el resto ya estaba en 04 |

**Trabajo real de B2.1: una tarjeta de 4 controles.** Todo lo demás resultó ser
inventario que evitó tres migraciones innecesarias:

1. `card_style` → `product_card_style` *(habría cambiado 5 tiendas)*
2. `catalog_layout` → `catalog_products_view` *(habría cambiado 6)*
3. Un editor para `catalog_layout` *(un control que no hace nada)*
