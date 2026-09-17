# Constructor — Matriz maestra de propiedad de datos

Se construye **incrementalmente** con cada revisión (01→09). Al terminar las 9
será la regla arquitectónica del nuevo Constructor.

**Regla:** cada dato tiene un único propietario y un único lugar donde se
modifica. Las demás áreas solo lo consumen.

Estado: **REORGANIZACIÓN EJECUTADA** (2026-08-31) — verificada por
`php artisan bixo:auditar-constructor`

**Criterios fijados por el usuario (2026-08-30):** ninguna seccion/ajuste se
borra durante la auditoria; lo muerto se marca `DEPRECATED_CANDIDATE` y se
limpia al final en una migracion propia. La etapa 01 esta **implementada,
congelada en local y pendiente de validacion** contra esta matriz.

---

## Datos maestros del negocio (revisión 01)

| Configuración | Fuente canónica | Se configura en | Solo se consume en |
|---|---|---|---|
| Nombre comercial | `projects.name` | 01 Datos | Tienda, PDF, facturación, bot, SEO |
| Teléfono | `projects.phone` | 01 Datos | Header, Footer, Contacto, órdenes, PDF |
| WhatsApp | `projects.whatsapp` | 01 Datos | Header, Home, Producto, Footer, Bot |
| Dirección | `projects.address` | 01 Datos | Contacto, Footer, Bot, SEO |
| Logo | `projects.logo_url` | 01 Datos | Header, Footer, PDF, portal |
| Rubro | `projects.category` | 01 Datos | Presets de diseño |
| Correo | `contact_email` *(ajuste)* | 01 Datos | Contacto, Footer |
| Redes sociales (6) | `*_url` *(ajustes)* | 01 Datos | Header, Contacto, Footer |
| Horarios | `business_hours` *(ajuste)* | 01 Datos | Contacto, Footer, SEO |
| Razón social / RUC | `razon_social`, `ruc` | 01 Datos | Facturación, Libro de Reclamaciones |
| Moneda | `currency_symbol` | ➜ **05 Venta** | Catálogo, carrito, checkout |

**Transición:** `contact_phone`, `quote_whatsapp`, `contact_address` y
`seo_title`-como-nombre quedan como **alias de compatibilidad**, nunca como
segunda fuente maestra. `seo_title` vuelve a ser exclusivamente SEO.

---

## Identidad visual (revisión 02)

| Configuración | Fuente canónica | Se configura en | Solo se consume en |
|---|---|---|---|
| Motor (plantilla) | `catalog_template` | 02 Apariencia | Toda la tienda |
| Preset visual | `theme_preset` | 02 Apariencia | Toda la tienda |
| Colores de marca | `primary_color`, `secondary_color`, `accent_color` | 02 Apariencia | Toda la tienda |
| Tipografías | `font_title`, `font_body` | 02 Apariencia | Toda la tienda |
| Bordes / botones | `border_radius`, `btn_shape`, `btn_show_icon` | 02 Apariencia | Toda la tienda |
| Barra superior | `ticker_*`, `announcement_*` | 02 Apariencia | Header |
| Encabezado y navegación | `header_*`, `hp_*` (45) | 02 Apariencia | Header |
| Footer (28 claves) | `footer_*` | ➜ **07 Footer y legales** | Footer |
| Secciones / hero (13) | `section_*`, `hero_px_*` | ➜ **03 Inicio** | Home |
| Tarjetas (7) | `card_*`, `product_card_style`… | ➜ **04 Catálogo** | Catálogo, Home |
| Login del panel (6) | `login_*` | ➜ **fuera de Mi Tienda** | `layouts/guest` |
| Botones flotantes (5) | `float_*` | ⚠️ **decisión pendiente** | Toda la tienda |

---

## Página de inicio (revisión 03)

| Configuración | Fuente canónica | Se configura en | Solo se consume en |
|---|---|---|---|
| Activación / orden / variante | `store_sections` (+`draft_*`) | 03 Inicio | Home |
| Programación y visibilidad por dispositivo | `publish_from/until`, `show_*` | 03 Inicio | Home |
| Textos de cada sección (título, subtítulo) | `store_sections.content` | 03 Inicio | Home |
| Qué productos/categorías muestra la sección | `store_sections.content` (ids) | 03 Inicio | Home |
| Animación por sección | `anim_{comp}_*`, `intro_{nat}_*` | 03 Inicio | Home |
| Estilo global de sección | `section_*`, `hero_px_*` | ⚠️ **decisión: 02 o 03** | Home |

**Transición:** `featured_categories_title` y demás `*_title`/`*_subtitle` de
ajustes pasan a alias. **Hoy el ajuste GANA sobre el contenido de la sección**
(`computienda.blade.php:503`) — hay que invertirlo tras migrar los datos.

---

## Catálogo (revisión 04)

| Configuración | Fuente canónica | Se configura en | Solo se consume en |
|---|---|---|---|
| Categorías y productos | tablas `categories`/`products` | **administrador existente** (enlazado desde 04) | Inicio, Tienda, menú, bot |
| Perfiles de catálogo | `store_catalog_profiles` | 04 Catálogo | Tienda |
| Rejilla / columnas / vista | `catalog_cols_*`, `catalog_products_view` ⚠️ consolidar con `catalog_layout` | 04 Catálogo | Tienda |
| Filtros | `catalog_filter_*` | 04 Catálogo | Tienda |
| Tarjeta — presentación | `product_card_style` ⚠️ consolidar con `card_style`, `card_image_*` | 04 Catálogo | Tienda, Inicio |
| Badges | `catalog_badge_*` | 04 Catálogo — **rescatar del Designer** | Tienda, Inicio |
| Tarjeta — acción comercial | `card_show_*`, `buy_*_label`, `btn_*_text` | ➜ **05 Venta** | Tienda |
| Modo de compra | `store_mode` (absorber `purchase_mode`) | ➜ **05 Venta** | Tienda, checkout |
| **Ficha de producto** | **no existe** | ➜ **backlog funcional** | — |

### Excepciones y capacidades a rescatar

| Elemento | Clasificación |
|---|---|
| `catalog_badge_new/sale/featured/sold_out` | **rescatar** — 5 tiendas con dato, sin control en el Constructor |
| `card_style` *(nivel proyecto)* | ⚠️ **NO consolidar** — concepto distinto: es la familia visual de la **plantilla**, no la tarjeta. A nivel de proyecto **no lo renderiza nadie** → `DEPRECATED_CANDIDATE` |
| `catalog_layout` | ⚠️ **NO rescatar** — token de la plantilla, **sin consumidor de render**. 6 tiendas con dato inerte → `DEPRECATED_CANDIDATE` |
| `btn_cart_text`, `btn_inquiry_text` | **rescatar** → destino 05 |
| `purchase_mode` | consolidar en `store_mode` (1 consumidor vs 30) |
| `product_show_low_stock/related/share/sku` | `DEPRECATED_CANDIDATE` — 0 consumidores, 0 datos |

---

## Backlog funcional (capacidad nueva, NO deuda)

| Capacidad | Estado |
|---|---|
| **Zonas y costos de envío** | no existen: solo `shipping_cost` plano. Sin tabla ni claves de zona |
| **Comprobantes en el Constructor** | solo `modulo_facturas` y `serie_factura`; la facturación vive en su módulo |
| Newsletter como sección de Inicio | hoy vive en el pie (`footer_newsletter_*`); el manifiesto ya tiene grupo "Newsletter" con variantes inline/popup |
| **Ficha de producto configurable** | no existe ningún control: galería, zoom, variantes, atributos, tabs, favoritos |
| Testimonios | **disponible / sin adopción** — implementado, 0 tiendas lo usan. Mantener |

---

## Venta (revisión 05)

| Configuración | Fuente canónica | Se configura en | Solo se consume en |
|---|---|---|---|
| Modo de venta | `store_mode` (absorber `purchase_mode`) | 05 Venta | Tienda, tarjeta, ficha, checkout |
| Moneda | `currency_symbol` | 05 Venta ✅ *(ya movida desde 01)* | Catálogo, carrito, checkout |
| Carrito | `cart_*`, `btn_cart_text`, `btn_checkout_text` | 05 Venta | Carrito, checkout |
| Mayoristas | `wholesale_enabled` + tarjeta mayorista | 05 Venta | Tarjeta, ficha, carrito |
| Campos del comprador | `checkout_fields` (JSON, escrito desde JS) | 05 Venta | Checkout |
| Envíos | `shipping_*` | 05 Venta | Carrito, checkout |
| Recojo en tienda | `pickup_*` | 05 Venta | Checkout |
| Pagos manuales (Yape/Plin/bancos) | `payment_*` | 05 Venta — **absorber `settings/payments`** | Checkout, footer |
| Pasarelas Culqi / Mercado Pago | `culqi_*`, `mp_*` | 05 Venta — **rescatar** | Checkout |
| **WhatsApp** | `projects.whatsapp` | **01 Datos** — 05 solo consume | Tienda, bot, checkout |

⚠️ **Violación activa de la fuente canónica:** `settings/payments` escribe
`quote_whatsapp` y `quote_whatsapp_country`, datos maestros de 01.

### Capacidades a rescatar (05)

Culqi (4 tiendas) · Mercado Pago (4 tiendas) · 5 cuentas bancarias ·
`payment_manual_instructions` — todas en uso y ausentes del Constructor.

---

## Páginas, footer y legales (revisiones 06 y 07)

| Configuración | Fuente canónica | Se configura en | Solo se consume en |
|---|---|---|---|
| Nosotros (misión, visión, historia, equipo) | `store_pages.nosotros.content` | 06 Páginas | Página, sección `about_preview` |
| Contacto — intro, formulario, mapa | `store_pages.contacto.content` | 06 Páginas | Página Contacto |
| **Contacto — teléfono, correo, horarios, redes, dirección** | **`projects.*` (01)** | **01 Datos** | Contacto *(solo lectura)* |
| Sucursales | tabla `sedes` | 01 Datos | Página, Inicio, Contacto, bot |
| FAQ | `store_sections.faq.content.items` | 03 Inicio | Inicio y página FAQ |
| Blog | `store_sections.blog.content.items` | 03 Inicio | Inicio y `/blog` ⚠️ hoy 404 |
| Composición y estilo del pie | `footer_style`, `footer_layout`, colores, logo | **07** | Pie |
| Textos y enlaces del pie | `footer_tagline`, `footer_copyright`, `footer_pages` | **07** | Pie |
| Newsletter | `footer_newsletter_*` | **07** | Pie |
| Términos y Privacidad | `store_pages.{terminos,privacidad}.content` | **07** | Páginas legales |
| Libro de Reclamaciones | tabla `complaints` + `razon_social`/`ruc` de 01 | **07** / **01** | Página pública |

⚠️ **Fuga activa:** `StoreExperienceController::page` (usado por 06 y 07) escribe
9 datos maestros de 01 dentro de `store_pages.content` — **tercera copia**.

---

## Configuración avanzada y publicación (revisiones 08 y 09)

| Configuración | Fuente canónica | Se configura en | Solo se consume en |
|---|---|---|---|
| SEO (`seo_title`, `seo_description`, `seo_keywords`, `seo_canonical`) | ajustes | **08** | `<head>` |
| Open Graph (`og_*`) | ajustes | **08** | Redes sociales |
| Analítica y píxeles (`ga_id`, `gtm_id`, `fb_pixel_id`, `tiktok_pixel_id`) | ajustes | **08** | ⚠️ **hoy NO se emiten** |
| Verificación de buscadores | ajustes | **08** | `<head>` |
| Dominio | `projects.custom_domain` | **08** o administración | Enrutado, enlaces, PDF |
| Reglas de auditoría | `BuilderRuleRegistry` *(código)* | — | 09 |
| Qué bloquea publicar | `blocks_publish` | **decisión de producto** | 09 |

**09 no posee ningún ajuste**: es verificación y publicación. Lee todo, no es
dueña de nada.

---

## Inventario de secciones de Inicio (clasificación pedida)

Ninguna se elimina. Criterio: *si existe, tiene uso real o contenido de
clientes, se preserva y se incorpora a la nueva taxonomía.*

| Sección | Activas | Clasificación |
|---|---|---|
| `featured_products` | 6 | **mantener** |
| `hero` | 6 | **mantener** |
| `benefits` | 5 | **mantener** |
| `featured_categories` | 4 | **mantener** |
| `about_preview` | 4 | **mantener** |
| `cta_banner` | 4 | **mantener** |
| `brands` | 3 | **mantener** |
| `info_strip` | 3 | **mantener** — incorporar a la taxonomía |
| `wa_advisory` | 3 | **mantener** — incorporar a la taxonomía |
| `faq` | 3 | **mantener** — coordinar con la página FAQ (06) |
| `discounts` | 2 | **mantener** |
| `blog` | 1 | **mantener** — capacidad incompleta (`/blog` da 404) |
| `daily_offer` | 1 | **mantener** |
| `announcements` | 1 | **mantener** |
| `collection_showcase` | 1 | **mantener** — incorporar a la taxonomía |
| `locations` | 1 | **mantener** — coordinar con Sucursales (01/06) |
| `testimonials` | 0 | **disponible / sin adopción** — no eliminar |
| `gallery` | 0 | **disponible / sin adopción** |
| `media_banner` | 0 | **disponible / sin adopción** |
| `category_rows` | 0 | **disponible / sin adopción** |
| `promotions` | 0 | `DEPRECATED_CANDIDATE` — heredada, 1 tienda, 0 activas |

---

## Elementos marcados DEPRECATED_CANDIDATE

No se borra nada durante la auditoría. Limpieza al final, en una migración propia.

| Elemento | Nº | Evidencia |
|---|---|---|
| `hp_*` del encabezado | 18 | 0 consumidores; verificado contra `HeaderPresets.php`, sin claves dinámicas |
| `product_show_low_stock/related/share/sku` | 4 | 0 consumidores, 0 tiendas con dato |
| `promotions` (sección) | 1 | heredada, sin uso |
| `purchase_mode` | 1 | 1 consumidor frente a 30 de `store_mode` |

## Ajustes vivos SIN control (el problema simétrico)

| Elemento | Nº | Nota |
|---|---|---|
| `hp_*` que `HeaderPresets` lee | 7 | solo editables por SQL |
| `catalog_layout` | 1 | 6 tiendas con dato, ningún editor |
| Analítica y píxeles | 4 | se guardan en `settings/seo` y **no se emiten** |

---

## Los OCHO lugares que hoy configuran la tienda

| # | Lugar | Ruta | Acceso | Estado objetivo |
|---|---|---|---|---|
| 1 | **Constructor** | `settings/builder` | Menú | **el único que debe quedar** |
| 2 | Diseño clásico | `settings/design` | Menú "Diseño"; el **Constructor lo enlaza como `classic`**; plantilla `ecommerce` | retirar |
| 3 | Diseñador visual | `settings/designer` | **sin enlace** — 11 editores de sección | decidir |
| 4 | Ajustes | `settings/index` | Menú | dejar solo administración interna |
| 5 | Páginas institucionales | `institutional-pages` | Dentro de Ajustes | absorber en 06 |
| 6 | **Editor incrustado en la plantilla `ecommerce`** | `ecommerce.blade.php:3818,4456` | controles `tw-*` **dentro de la tienda pública** | investigar y retirar |
| 7 | **Pagos** `settings/payments` | `web.php:476` | **elemento de menú en 3 navegaciones** — 446 líneas | absorber en 05 |
| 8 | **SEO** `settings/seo` | `web.php:489` | **elemento de menú en 2 navegaciones** — 14 claves, 12 ausentes del Constructor | absorber en 08 |

**Matiz:** `institutional-pages` **no es una superficie rival** — es un partial
que el propio Constructor incrusta en 06 y 07. El problema no es que duplique
pantalla, sino que escribe datos maestros de 01.

---

## Los CUATRO mecanismos de escritura (trampa de auditoría)

Cualquier barrido que mire solo el primero da cifras falsas:

| # | Mecanismo | Ejemplo |
|---|---|---|
| 1 | `setSetting('clave', …)` | todas las etapas |
| 2 | `<x-bxb-color clave="…">` | 40 usos — **todos los colores** |
| 3 | arrays PHP `['key' => 'clave']` + `@foreach` | `stages/sales.blade.php:203` |
| 4 | `setSetting` desde JavaScript | `builder/script.blade.php` → `business_category`, `header_preset`, `checkout_fields` |

Antes de declarar una clave muerta hay que comprobar además si se **construye
por concatenación** (`anim_{comp}_type`, `hp_nav_chip_{n}`, `payment_{m}_on`).

### Regla de "0 consumidores" — barrido obligatorio

`0 consumidores` **solo vale como evidencia después de barrer TODAS las
superficies de renderizado**. Falló dos veces en esta auditoría por dejarse
rutas fuera:

| Superficie | Ejemplo que se escapó |
|---|---|
| `resources/views/public/` | — |
| `resources/views/components/` | — |
| **`resources/views/storefront/`** | `footers/technology.blade.php` → 3 claves dadas por muertas **sí se consumían** |
| **`resources/views/layouts/`** | `storefront.blade.php` aplica `storefront-family-*` |
| `app/Support/`, `app/Storefront/` | `HeaderPresets`, `StorefrontTheme`, `CatalogTemplates` |
| **JS y build** | `resources/js`, `public/js`, `public/build` |
| Includes dinámicos y variantes por plantilla | partials elegidos por `catalog_template` |

### REGLA: dos claves parecidas NO se consolidan por nomenclatura

Primero se determina si representan el mismo concepto funcional. Para tratarlas
como duplicadas deben coincidir en **las cinco**:

| # | Debe coincidir |
|---|---|
| 1 | Concepto |
| 2 | Propietario |
| 3 | Consumidor |
| 4 | Efecto visible o funcional |
| 5 | Dominio de valores |

**Si alguno difiere, no hay duplicación.** Aplicado dos veces con el mismo
resultado: `card_style` vs `product_card_style` y `catalog_layout` vs
`catalog_products_view` — en ambos casos la primera es un **token de la
definición de plantilla** y la segunda un **ajuste del proyecto que sí
renderiza**. Consolidarlas habría cambiado la apariencia de 5 y 6 tiendas.

### Y la trampa concreta: el mismo nombre, distinto concepto

Antes de consolidar dos claves parecidas hay que comprobar **quién las lee**, no
solo cómo se llaman. `card_style` y `product_card_style` parecían duplicadas y
son cosas distintas: la primera es la familia visual de la **plantilla**
(`StorefrontTheme` la lee de `CatalogTemplates`, no del proyecto), la segunda es
el estilo de la **tarjeta de producto** por tienda. Consolidarlas habría
cambiado la apariencia de 5 tiendas sin motivo. Ver
[B2.1 · Dominio de las tarjetas](BIXO_CONSTRUCTOR_B2_1_TARJETAS.md).

---

## Deuda detectada hasta ahora

| Tipo | Nº | Detalle |
|---|---|---|
| Claves con **dos controles** | **0 vivas** | Las 30 ya estaban dentro de `@if(false)` — código presente, **no se renderiza**. Corregido tras B1 |
| **Claves que perdieron su editor** | **18 → 0** ✅ | B1 cerrado: las 18 tienen editor en su etapa dueña |
| Controles **muertos** | 18 | `hp_*` que nadie consume |
| Ajustes **sin control** | 7 | `hp_*` que se leen pero no se pueden editar |
| Superficies que escriben configuración | **8** | + `settings/designer` (11 editores) y el enlace `classic` del propio Constructor |
| Secciones activas fuera de la estructura objetivo | 5 | `info_strip`, `wa_advisory`, `faq`, `collection_showcase`, `locations` |
| Datos con **dos almacenes** | — | textos de sección: `store_sections.content` vs ajustes `*_title` |

Detalle: [01 Datos del negocio](BIXO_CONSTRUCTOR_01_DATOS_NEGOCIO.md) ·
[02 Apariencia](BIXO_CONSTRUCTOR_02_APARIENCIA.md) ·
[03 Página de inicio](BIXO_CONSTRUCTOR_03_INICIO.md) ·
[04 Catálogo](BIXO_CONSTRUCTOR_04_CATALOGO.md) ·
[05 Venta](BIXO_CONSTRUCTOR_05_VENTA.md) ·
[06 Páginas](BIXO_CONSTRUCTOR_06_PAGINAS.md) ·
[07 Footer y legales](BIXO_CONSTRUCTOR_07_FOOTER_LEGALES.md) ·
[08 Configuración](BIXO_CONSTRUCTOR_08_AVANZADA.md) ·
[09 Revisar y publicar](BIXO_CONSTRUCTOR_09_REVISAR_PUBLICAR.md)

---

## B1 · Estado real de las duplicaciones (medido 2026-08-30)

**Corrección a la revisión 02.** Dije que 30 claves tenían dos controles y que
el comerciante los veía duplicados. **Era falso en su impacto:** las 30 ya
estaban dentro de bloques `@if(false)` en Apariencia — el código existe pero
**no se renderiza**. Ningún comerciante vio nunca un control duplicado.

Medición sobre `stages/appearance.blade.php`:

| | Antes de B1 | Después de B1 |
|---|---|---|
| Controles **vivos** en Apariencia | 36 de 92 | 46 de 92 |
| Controles en código muerto | 56 | 46 |
| Claves duplicadas **vivas** | **0** | **0** |
| Claves con editor vivo en su etapa dueña | 30 / 30 ✅ | 30 / 30 ✅ |

### El problema real era el inverso

Al envolver esos bloques en `@if(false)`, **18 ajustes vivos se quedaron sin
ningún editor en el Constructor**. No es duplicación: es lo contrario.

| Grupo | Nº | Datos reales | Estado tras B1 |
|---|---|---|---|
| `ticker_*` — franja animada | 5 | **2 tiendas** con texto propio | ✅ **devuelto a 02** |
| `section_head_*` — estilo de títulos | 5 | **3 tiendas** con colores propios | ✅ **devuelto a 02** |
| `pastel_band_1/2/3` | 3 | 1 tienda | ✅ **devuelto a 03** |
| `footer_accent_color`, `footer_bg2_color`, `footer_cats_limit`, `footer_dev_text`, `footer_show_benefits` | 5 | **3–4 tiendas** con datos | ✅ **devuelto a 07** (tarjeta "Pie avanzado") |

**18 de 18 resueltas. Criterio de salida de B1 cumplido: `ajustes vivos sin
editor = 0`.**

> **Corrección:** dije que `footer_cats_limit`, `footer_accent_color` y
> `footer_bg2_color` tenían 0 consumidores y propuse marcarlas
> `DEPRECATED_CANDIDATE`. **Era falso:** las tres se consumen en
> `resources/views/storefront/partials/footers/technology.blade.php` — la misma
> ruta que ya se me escapó en la revisión 02. **Ninguna de las 18 estaba muerta.**

### Qué se hizo exactamente

Ninguna clave se borró y ningún dato se tocó. Solo se **extrajeron dos bloques
de controles del código muerto** y se publicaron como tarjetas vivas en
Apariencia, que es su etapa dueña según esta matriz:

- **Franja animada** (`ticker_text`, `ticker_speed`, `ticker_bg_color`,
  `ticker_position`, `ticker_force_motion`) — la estructura objetivo asigna
  "Barra superior" a 02.
- **Títulos de sección** (`section_head_style`, `section_head_bar_color`,
  `section_head_text_color`, `section_head_btn_bg`, `section_head_btn_color`) —
  sistema visual global, luego 02 por la regla que fijaste.

El resto del código muerto (sello de confianza, CTA del pie, controles de
footer/inicio/catálogo) **sigue desactivado**, porque su etapa dueña ya tiene
editor vivo — verificado: **30 / 30**.

---

## Riesgos de regresión al ejecutar la reorganización

| # | Riesgo | Dónde | Mitigación |
|---|---|---|---|
| 1 | **Pérdida de datos maestros** | Electro Jara tiene `projects.address` **vacía** y su dirección solo en el ajuste | Volcar ajuste→columna **antes** de que la columna pase a ser canónica. Nunca dejar vacío un dato que hoy tiene valor en alguna de las 3 copias |
| 2 | **Divergencia ya publicada** | Tecsist muestra **dos direcciones distintas** en la misma página | Preguntar al comerciante cuál es la real antes de unificar |
| 3 | Cambio visible al invertir resolución | Títulos de sección: hoy gana el ajuste sobre `store_sections.content` | Migrar datos primero, invertir después |
| 4 | Cambio visible al consolidar tarjetas | `card_style` (5 tiendas) se edita pero renderiza `product_card_style` (4 tiendas) | Decidir qué valor gana por tienda |
| 5 | Páginas sin borrador | `store_pages` no tiene `draft_*` | Añadir el ciclo antes de que 09 prometa "publicar todo" |
| 6 | ~~Retirar controles duplicados~~ | — | **Resuelto en B1**: no había duplicados vivos |
| 7 | ~~Ajustes sin editor~~ | — | **Resuelto en B1**: 18/18. Verificar SIEMPRE que la etapa dueña tenga control **vivo**, no solo presente en el código |

## Migraciones necesarias

| # | Migración | Motivo |
|---|---|---|
| 1 | Volcar `contact_*` → columnas de `projects` donde la columna esté vacía | Evitar el riesgo 1 |
| 2 | Extraer los 9 datos maestros de `store_pages.content` | Eliminar la tercera copia |
| 3 | Volcar `*_title` de ajustes → `store_sections.content` | Antes de invertir la resolución |
| 4 | ~~Consolidar `card_style`/`product_card_style`~~ | **Descartada en B2.1**: no son el mismo concepto. `catalog_layout`/`catalog_products_view` sigue pendiente de inventario |
| 5 | Añadir columnas `draft_*` a `store_pages` | Coherencia del ciclo de publicación |
| 6 | Absorber `purchase_mode` en `store_mode` | Una clave por concepto |

## Orden de ejecución propuesto

1. ✅ **B0 — validar 01**: hecho. Defecto de `rollback` **corregido** con 8 pruebas.
2. ✅ **B1 — duplicados**: hecho. No había duplicados vivos; se devolvió editor a 18 ajustes huérfanos.
3. **Rescatar capacidades** del Designer y de `settings/payments`/`settings/seo`: badges, `card_style`, `catalog_layout`, Culqi, Mercado Pago, bancos, analítica.
4. **Cerrar las fugas de datos maestros**: partial institucional (06/07) y `quote_whatsapp` en `settings/payments` (05).
5. **Migraciones 1–6.**
6. **Activar `blocks_publish`** en 09.
7. **Retirar superficies** cuando ya no tengan funcionalidad exclusiva: Designer, Diseño clásico, editor incrustado, y el enlace `classic` del propio Constructor.
8. **Backlog funcional**, en orden de valor: analítica que se emita · ficha de producto · zonas de envío · Newsletter · Cookies/envíos/devoluciones · páginas personalizadas · SEO por página · scripts con lista blanca.

## Matriz «Classic» (pendiente de completar)

Tu punto 9 pide una tabla de funciones que el Constructor delega en Diseño
clásico. Lo detectado hasta ahora:

| Función enviada a Classic | Existe en Builder | Falta migrar | Destino |
|---|---|---|---|
| Estilo de tarjeta (`card_style`) | ⚠️ otra clave | sí | 04 |
| Columnas y vista del catálogo | ✅ | no | 04 |
| Título de sección de catálogo | ✅ | no | 04 |
| Contacto, horarios, logo | ✅ (01) | no | 01 |
| `checkout_fields` | ✅ (JS) | no | 05 |
| `custom_domain` | ❌ | sí | 08 |
| `seo_title` | ⚠️ en 3 sitios | sí | 08 |
| `login_*` | ❌ | decidir | fuera de Mi Tienda |

Se completará al ejecutar el paso 7.


---

# EJECUCIÓN DE LA REORGANIZACIÓN — 2026-08-31

## Verificación automática

`php artisan bixo:auditar-constructor` (y `ConstructorAuditoriaTest`, 6 pruebas)

| Métrica | Antes | Ahora |
|---|---|---|
| `DUPLICATE_EDITORS` | 128 | **0** ✅ |
| `LIVE_SETTINGS_WITHOUT_EDITOR` | 39 | **0** ✅ |
| `INERT_CONTROLS` | 24 | **0** ✅ |
| `MASTER_DATA_WRITE_VIOLATIONS` | 2 | **0** ✅ |
| `UNCLASSIFIED` | 84 | **0** ✅ |

El auditor recorre 5 mecanismos de escritura, excluye los bloques `@if(false)`
y barre las 7 superficies de render. **No vuelve a hacer falta descubrir un
duplicado a mano.**

## Superficies legacy retiradas

Orden aplicado: rescatar → validar → probar → redirigir.

| Superficie | Estado | Cómo |
|---|---|---|
| Diseño clásico | **retirada** | redirige al Constructor *(ya lo estaba)* |
| Designer | **retirada** | redirige; sus badges y estilos ya viven en 04 |
| `settings/payments` | **retirada** | redirige; Culqi, MP, bancos, Yape/Plin e instrucciones están en 05 |
| `settings/seo` | **retirada** | redirige; SEO, OG, analítica y verificación están en 08 |
| `settings/index` | **acotada** | dejó de aceptar redes, SEO y envío; conserva solo facturación |
| Editor incrustado en `ecommerce` | pendiente | escribe `card_style`, que es `DEPRECATED_CANDIDATE` |

Todas conservan `?classic=1` para el superadmin como salida de emergencia.

## Capacidades rescatadas al Constructor

| Etapa | Qué recibió |
|---|---|
| 02 | Franja animada (`ticker_*`) · Títulos de sección (`section_head_*`) |
| 03 | Bandas pastel · Categorías destacadas (8) · Barra de confianza (5) · Anuncios (2) |
| 04 | **Badges** (4) · Textos del catálogo (4) · "Qué se muestra en la tarjeta" |
| 05 | **Culqi · Mercado Pago** · instrucciones manuales · botones y precios de la tarjeta *(venidos de 04)* |
| 07 | Pie avanzado (5) · Qué muestra el pie (3) |
| 08 | Analítica (4) · Verificación (2) · Open Graph (3) |

## Correcciones de bugs durante la ejecución

| Bug | Estado |
|---|---|
| `rollback()` no restauraba las columnas maestras | ✅ corregido + 8 pruebas |
| La analítica se guardaba y **nunca se emitía** | ✅ emisor único `<x-analytics-tags>` con validación por proveedor |
| La etapa 09 decía "nada impide publicar" siendo ya falso | ✅ texto corregido + bloque que lista los impedimentos |
| `settings/payments` escribía el WhatsApp maestro | ✅ retirado |
| Título duplicado al insertar los badges | ✅ corregido |
| Copy engañoso del bloque de acceso *(dice "clientes", es el panel)* | ✅ corregido |

## Reglas de publicación activas (09)

**Bloquean siempre:** sin nombre · sin plantilla · sin productos.
**Bloquean según contexto:** sin precio *(solo venta directa)* · sin WhatsApp
*(solo si el modo depende de él)* · sin cobro *(solo compra online)* · sin
dirección *(solo con local físico o recojo)*.
**Nunca bloquean:** logo, correo, rubro, imágenes, redes, SEO, secciones, legales.

`blocks_publish` admite ahora closures, igual que `severity`.

## Migración ejecutada en producción

Rubro específico sobre `retail` genérico: GABDE→ferretería, Tecsist→tecnología,
MegaHogar→muebles, Baby Toncito→bebés, Electro Jara→ferretería. Idempotente,
verificada en ARIN. Las 3 tiendas sin alternativo, intactas.

## Ciclo de borrador en páginas — CERRADO (2026-08-31)

Última pieza de la reorganización. `store_pages` tiene ahora el mismo ciclo que
`store_sections`:

| Pieza | Cómo |
|---|---|
| Columnas | `draft_title`, `draft_content`, `draft_is_enabled`, `has_draft`, `published_at` — migración idempotente; lo ya guardado se marca publicado |
| Editar | `StorePageWriteService::save()` escribe en **borrador**; una página nueva nace publicada porque no hay nada que pisar |
| Vista previa | `StorefrontContextBuilder` sirve el borrador **solo en preview**; la tienda pública siempre lo publicado |
| Publicar | `BuilderDraftService::publish()` promueve las páginas junto con las secciones |
| Descartar | `discardDraft()` las devuelve al último publicado |
| Deshacer | el snapshot guarda su estado previo y `rollback()` lo restaura |
| Avisar | `hasDrafts()` cuenta una página en borrador como cambio pendiente |

7 pruebas en `PaginasCicloBorradorTest`. **Ningún comprador ve un cambio**: la
tienda pública sigue leyendo las columnas publicadas.

## Pendiente — no es reorganización de etapas

- Retirar los 9 datos maestros copiados en `store_pages.content`
- Limpieza de los `DEPRECATED_CANDIDATE`
- Backlog: ficha de producto · zonas de envío · Newsletter · cookies/envíos/
  devoluciones · páginas personalizadas · SEO por página · scripts con lista blanca
- **Despliegue**: todo en local. Bloqueado por deriva con la sesión paralela y
  porque `variant-engine` no existe en ARIN.

## Conflictos que solo puede resolver el comerciante

`MANUAL_DATA_RESOLUTION_REQUIRED` — Tecsist (dirección) y GABDE (teléfono y
WhatsApp). Ninguna migración automática los toca.
