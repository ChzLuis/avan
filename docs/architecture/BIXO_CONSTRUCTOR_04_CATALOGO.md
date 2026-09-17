# Constructor — Revisión 04 · Catálogo

Auditoría verificada el **2026-08-30** contra el código y la BD de producción.
Cuarto paso de la reorganización de "Mi Tienda" en 9 etapas.

**Principio obligatorio:** cada dato o configuración tiene un único propietario
y un único lugar donde se modifica. Las demás áreas solo lo consumen.

---

## 1. Lo que ya está bien

**La etapa NO crea un CRUD paralelo de productos.** Delega en el administrador
existente (`stages/catalog.blade.php:18-21`): crear producto, abrir catálogo
completo, plantilla Excel e importación abren `route('products.index')`. Cumple
la regla que fijaste.

**Los perfiles de catálogo son propiedad clara de 04** y tienen datos reales:

| Proyecto | Perfiles activos |
|---|---|
| Baby Toncito (20) | `nino`, `nina` |
| Tecsist (18) | `reacondicionados` |

### Matiz: la etapa sí muta productos

`stages/catalog.blade.php:87-95` expone **acciones masivas**: publicar,
despublicar, **fijar precio**, **ajustar precio %**, fijar categoría, precio
mayorista. No es un CRUD, pero **cambia precios y disponibilidad** desde el
Constructor. Queda a tu criterio si eso respeta "no otro CRUD" — mi lectura es
que sí (es gestión en lote, no edición individual), pero conviene decidirlo
explícitamente porque toca datos comerciales.

---

## 2. Tres declaraciones distintas de qué es "Catálogo"

Éste es el hallazgo central de la revisión. **Tres fuentes definen el catálogo y
no coinciden:**

| Fuente | Claves | Naturaleza |
|---|---|---|
| `CatalogTemplates.php` — grupo "Catálogo" | 18 | **manifiesto** del sistema |
| `stages/catalog.blade.php` — Constructor | 37 | la etapa objetivo |
| `designer/sections/catalog.blade.php` — Designer paralelo | 20 | LEGACY_PARALLEL_BUILDER |

### 2.1 · Claves del manifiesto que el Constructor NO puede editar

| Clave | Consumidores | **Tiendas con dato** | Dónde se edita hoy |
|---|---|---|---|
| `catalog_badge_new` | 16 | **5** — "NUEVO" | solo Designer |
| `catalog_badge_sale` | 16 | **5** — "OFERTA" | solo Designer |
| `card_style` | 12 | **5** — minimal \| tech | Designer, Diseño clásico, editor incrustado |
| `catalog_layout` | 1 | **6** — grid | **ningún editor del Constructor** |
| `catalog_badge_sold_out` | 7 | 1 — "AGOTADO" | solo Designer |
| `catalog_badge_featured` | 6 | 1 — "DESTACADO" | solo Designer |
| `btn_cart_text` | 22 | **5** — "Agregar al carrito" | solo Designer |

**Los badges —que tu estructura objetivo pide explícitamente en 04— no existen
en el Constructor.** Cinco tiendas de producción los tienen configurados y su
dueño solo puede cambiarlos en el editor que queremos retirar.

Esto es exactamente lo que pediste detectar en tu punto 8: **capacidades del
`LEGACY_PARALLEL_BUILDER` que el Constructor todavía no posee**. Son
**candidatas a rescate**, no a borrado.

### 2.2 · Dos claves para el estilo de tarjeta — y gana la que no se edita

| Clave | Tiendas | ¿Renderiza? | Dónde se edita |
|---|---|---|---|
| `product_card_style` | 4 (contrast, elegant, soft, tech) | **SÍ** — `computienda:76`, `ecommerce:37` | Constructor + Apariencia |
| `card_style` | **5** (minimal, tech) | **NO** — solo selector y runtime JS | Designer, Diseño clásico, editor incrustado |

`card_style` se lee en `public-store-runtime.blade.php:142`
(`'cardStyle' => $settings['card_style'] ?? 'minimal'`) y en un `<select>`, pero
**el estilo que realmente pinta la tarjeta sale de `product_card_style`**.

⚠️ **Consecuencia:** quien cambie "Estilo de tarjeta" en Diseño clásico o en el
Designer está escribiendo `card_style` y **no verá ningún cambio en su tienda**.
Cinco tiendas tienen ese valor guardado.

> No lo he arreglado: no muestra información incorrecta al comprador, así que
> queda fuera del permiso de hotfix. Es deuda a resolver al unificar 04.

### 2.3 · `catalog_layout` vs `catalog_products_view`

Dos claves para "cómo se ve la rejilla": `catalog_layout` (6 tiendas, valor
`grid`, sin editor en el Constructor) y `catalog_products_view` (2 tiendas,
valor `cards`, sí editable). Duplicación semántica a consolidar.

---

## 3. Hallazgo nuevo: una SEXTA superficie de edición

Las revisiones anteriores encontraron cinco. Ésta añade la más inesperada:

**Un panel de edición incrustado dentro de la propia plantilla `ecommerce`.**

`resources/views/public/templates/ecommerce.blade.php:3818-3826` y `:4456`
contienen un editor con controles `tw-*` (`<select id="tw-card-style">`,
`card_style: twVal('tw-card-style')`) que escribe ajustes **desde la tienda
pública**. Y en `:4143` enlaza a `route('settings.design')`.

| # | Superficie | Estado objetivo |
|---|---|---|
| 1 | **Constructor** `settings/builder` | el único que debe quedar |
| 2 | Diseño clásico `settings/design` | retirar |
| 3 | Designer `settings/designer` | LEGACY_PARALLEL_BUILDER |
| 4 | Ajustes `settings/index` | solo administración interna |
| 5 | Páginas institucionales | absorber en 06 |
| 6 | **Editor incrustado en `ecommerce`** | **investigar y retirar** |

---

## 4. Frontera 04 Catálogo ↔ 05 Venta

Aplico tu regla: **cómo se muestra → 04; qué acción comercial permite → 05.**

### Se queda en 04 (presentación)

`card_image_bg`, `card_image_ratio`, `card_no_photo_text`, `card_cart_style`,
`card_qty_style`, `card_whatsapp_style`, `catalog_cols_desktop`,
`catalog_cols_mobile`, `catalog_filter_cats`, `catalog_filter_price`,
`catalog_filter_sale`, `catalog_filter_search`, `catalog_group_models`,
`catalog_products_view`, `catalog_quick_view`, `catalog_section_title`,
`catalog_show_ratings`, `catalog_show_sku`, `catalog_show_stock`,
`cats_mobile_limit`, `new_badge_days`, `product_card_style`, `related_title`,
`sold_out_text`, `price_on_request_text`
— **más las rescatadas:** los 4 `catalog_badge_*`, `catalog_layout`, `card_style`.

### Se va a 05 Venta (acción comercial)

| Clave | Por qué |
|---|---|
| `purchase_mode` | modo de compra minorista/mayorista |
| `card_show_cart` | habilita la acción de comprar |
| `card_show_whatsapp` | habilita la acción de consultar |
| `card_show_quantity`, `card_show_subtotal`, `card_show_savings` | cálculo de compra |
| `card_show_wholesale_price`, `card_show_wholesale_condition`, `wholesale_card_collapse` | política mayorista |
| `buy_retail_label`, `buy_wholesale_label`, `buy_unit_label` | etiquetas de la acción de compra |
| `btn_cart_text`, `btn_inquiry_text` *(hoy solo en Designer)* | textos de la acción |

**Distinción fina:** `card_show_cart` (¿existe el botón?) es 05;
`card_cart_style` (¿cómo se ve?) es 04. Misma tarjeta, dueños distintos.

### `purchase_mode` es casi deuda muerta

`purchase_mode` tiene **1 solo consumidor** (`computienda:181`) y **1 tienda**
con dato, frente a `store_mode` con **30 consumidores**. Ambos expresan
"directa / cotización". Candidato a consolidar en `store_mode` (propiedad de 05).

---

## 5. Petición B — La ficha de producto: el mayor hueco del Constructor

**La ficha/detalle de producto no es configurable.** Barrido completo de claves
`product_*`, `pdp_*`, `gallery_*`, `zoom_*`, `variant_*` consumidas en todo el
sistema: solo aparecen **dos**, y ninguna es de la ficha:

- `product_button_mode` (5 usos) → es comercial, pertenece a **05**
- `product_card_style` (4 usos) → es la **tarjeta**, no la ficha

De todo lo que pide tu estructura objetivo para la ficha:

| Control pedido | ¿Existe? |
|---|---|
| Galería, imagen principal, zoom | ❌ |
| Variantes | ❌ *(la feature está en vuelo por otra sesión)* |
| Precio, stock, SKU, marca, atributos | ❌ *(hay `catalog_show_sku`/`stock`, pero son del listado)* |
| Descripción, tabs/acordeones, información adicional | ❌ |
| Relacionados / recomendados | ⚠️ solo `related_title` (el texto, no el comportamiento) |
| WhatsApp / cotización / carrito | ⚠️ vía `product_button_mode` (comercial → 05) |
| Favoritos | ❌ |

Lo único que existió apunta al Designer, y **está muerto**:
`product_show_low_stock`, `product_show_related`, `product_show_share`,
`product_show_sku` — **0 consumidores** y **0 tiendas con dato**.
→ `DEPRECATED_CANDIDATE`.

**Conclusión:** 04 no es solo una reorganización; la ficha de producto es
**capacidad nueva a construir**, igual que Newsletter. Debe entrar al backlog
funcional, no a la auditoría de deuda.

---

## 6. Petición C — Configuración de catálogo dispersa

| Dónde | Claves de catálogo que escribe |
|---|---|
| **02 Apariencia** | 7 (colisiones ya documentadas): `card_image_bg`, `card_image_ratio`, `catalog_group_models`, `cats_mobile_limit`, `new_badge_days`, `product_card_style`, `wholesale_card_collapse` |
| **Diseño clásico** | `card_style`, `catalog_cols_desktop`, `catalog_cols_mobile`, `catalog_products_view`, `catalog_section_title` |
| **Designer** | 20 claves, 7 de ellas exclusivas |
| **Editor incrustado en `ecommerce`** | `card_style` y otros `tw-*` |
| **Manifiesto `CatalogTemplates`** | 18 claves declaradas, no todas editables |
| `projects` (columnas) | ninguna de catálogo — ✅ limpio |
| Perfiles de catálogo | tabla propia `store_catalog_profiles` — ✅ limpio |

**Cinco superficies escriben configuración de catálogo.**

---

## 7. Propiedad recomendada

| Configuración | Fuente canónica | Se configura en | Se consume en |
|---|---|---|---|
| Categorías y productos | tablas `categories` / `products` | **administrador existente** (enlazado desde 04) | Inicio, Tienda, menú, bot |
| Perfiles de catálogo | `store_catalog_profiles` | 04 Catálogo | Tienda |
| Rejilla, columnas, vista | `catalog_cols_*`, `catalog_products_view` *(consolidar con `catalog_layout`)* | 04 Catálogo | Tienda |
| Filtros | `catalog_filter_*` | 04 Catálogo | Tienda |
| Tarjeta — presentación | `product_card_style` *(consolidar con `card_style`)*, `card_image_*`, `card_*_style` | 04 Catálogo | Tienda, Inicio |
| Badges | `catalog_badge_*` | 04 Catálogo **(rescatar del Designer)** | Tienda, Inicio |
| Tarjeta — acción comercial | `card_show_*`, `buy_*_label`, `btn_*_text` | ➜ **05 Venta** | Tienda |
| Modo de compra | `store_mode` *(absorber `purchase_mode`)* | ➜ **05 Venta** | Tienda, checkout |
| Ficha de producto | **no existe** | ➜ backlog funcional | — |

---

## 8. Trabajo que sale de esta revisión

| # | Tarea | Riesgo | Prioridad |
|---|---|---|---|
| 1 | Rescatar del Designer al Constructor: 4 `catalog_badge_*`, `card_style`, `btn_cart_text`, `btn_inquiry_text` | Bajo | **Alta** |
| 2 | Consolidar `card_style` ↔ `product_card_style` (hoy se edita una y renderiza la otra) | **Medio** — cambia lo visible en 5 tiendas | Alta |
| 3 | Consolidar `catalog_layout` ↔ `catalog_products_view` | Medio | Alta |
| 4 | Mover a 05 las 13 claves de acción comercial | Nulo *(misma clave)* | Alta |
| 5 | Quitar de Apariencia los 7 controles de catálogo *(tarea 4 de la revisión 02)* | Nulo | Alta |
| 6 | Investigar y retirar el editor incrustado en `ecommerce` | Medio | Alta |
| 7 | Absorber `purchase_mode` en `store_mode` | Bajo | Media |
| 8 | Ficha de producto: especificar como capacidad nueva | — | Backlog |
| 9 | `product_show_*` (4) → `DEPRECATED_CANDIDATE` | — | Deuda |

**No se implementa nada todavía**, conforme a tu instrucción. Tampoco hubo
hotfix en esta revisión: el fallo de `card_style` no muestra información
incorrecta al comprador, solo desatiende un control del comerciante.

---

## 9. Decisiones que necesito del usuario

1. **Acciones masivas de precio** en la etapa 04 (`price_set`, `price_adjust`,
   `wholesale_set`): ¿se quedan en Catálogo, se mueven a 05 Venta, o solo al
   administrador de productos?
2. **`card_style` ↔ `product_card_style`**: al consolidar hay que elegir qué ve
   cada una de las 5 tiendas afectadas. ¿Gana el valor que hoy se renderiza
   (`product_card_style`) aunque el comerciante hubiera elegido otro?
3. **Editor incrustado en `ecommerce`**: ¿lo conocías? Saber si alguien lo usa
   decide si se retira directamente o requiere migración.

---

## 10. Siguiente paso

Revisión **05 Venta**: las 27 claves de la etapa, las 13 que le llegan desde 04,
la consolidación `store_mode`/`purchase_mode`, pagos, envíos, recojo en tienda y
checkout — y verificar que no haya configuración comercial escondida en Catálogo,
Inicio o el Footer.
