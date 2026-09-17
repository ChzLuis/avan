# Constructor — Revisión 02 · Apariencia

Auditoría verificada el **2026-08-30** contra el código y la BD de producción.
Segundo paso de la reorganización de "Mi Tienda" en 9 etapas.

**Alcance:** absorbe la etapa `header` actual, porque en la arquitectura
objetivo **Encabezado y navegación pertenecen a Apariencia**.

**Principio obligatorio:** cada dato o configuración tiene un único propietario
y un único lugar donde se modifica. Las demás áreas solo lo consumen.

---

## 1. Qué controles existen hoy

Dos vistas separadas que la estructura objetivo funde en una:

| Etapa actual | Archivo | Líneas | Claves que escribe |
|---|---|---|---|
| Apariencia | `stages/appearance.blade.php` | 576 | **92** |
| Encabezado | `stages/header.blade.php` | 429 | **45** |
| | | **1 005** | **137** |

Dos mecanismos de escritura conviven: `setSetting('clave', …)` y el componente
`<x-bxb-color clave="…">` (40 usos). *Cualquier auditoría que solo mire
`setSetting` se pierde todos los colores* — es el error que cometí en el primer
barrido de esta revisión.

### Composición real de las 92 claves de Apariencia

| Familia | Nº | ¿A qué etapa pertenece según la estructura objetivo? |
|---|---|---|
| `footer_*` | **28** | ➜ **07 Footer y legales** |
| `section_*` | **11** | ➜ **03 Página de inicio** |
| `login_*` | **6** | ➜ **ninguna** — estiliza el login del **panel**, no la tienda |
| `ticker_*` | 5 | ✅ 02 (Barra superior) |
| `float_*` | 5 | ⚠️ sin dueño asignado en la estructura objetivo |
| `announcement_*` | 3 | ✅ 02 (Barra superior) |
| `hero_*` | 2 | ➜ **03 Página de inicio** |
| `card_*` + tarjeta | 6 | ➜ **04 Catálogo** (Tarjetas) |
| Colores de marca, tipografías, tema, bordes, botones | ~26 | ✅ **02 — su sitio correcto** |

**Solo ~26 de las 92 claves de Apariencia le pertenecen de verdad.**

---

## 2. La duplicación: 30 claves con dos controles

**30 claves se escriben desde dos etapas distintas — y Apariencia está en las 30.**
No es que dos etapas guarden datos diferentes: guardan **la misma clave** desde
dos pantallas, así que el comerciante encuentra el mismo control en dos sitios y
no sabe cuál manda.

| Con qué etapa choca | Nº | Claves |
|---|---|---|
| **07 legal** (footer) | **15** | `footer_bg_color`, `footer_text_color`, `footer_copyright`, `footer_logo_height`, `footer_logo_plate`, `footer_newsletter_title`, `footer_newsletter_url`, `footer_pages`, `footer_show_categories`, `footer_show_payments`, `footer_show_secure`, `footer_show_socials`, `footer_store_pages`, `footer_style`, `footer_tagline` |
| **03 home** (secciones) | **8** | `section_background_mode`, `section_card_shadow`, `section_heading_align`, `section_show_dividers`, `section_spacing`, `section_style_preset`, `hero_px_desktop`, `hero_px_mobile` |
| **04 catalog** (tarjetas) | **7** | `card_image_bg`, `card_image_ratio`, `catalog_group_models`, `cats_mobile_limit`, `new_badge_days`, `product_card_style`, `wholesale_card_collapse` |

**Apariencia y Encabezado no chocan entre sí:** sus 137 claves son disjuntas.
Fundirlas en una sola etapa es limpio y no genera conflictos.

### La feature partida en dos

La **barra superior** está repartida: `announcement_text` lo escribe *Encabezado*
y `announcement_align`, `announcement_font_size`, `announcement_full_width` los
escribe *Apariencia*. Al fundir ambas etapas esto se resuelve solo.

---

## 3. Controles muertos y ajustes inalcanzables

### 3.1 · 18 controles escriben al vacío

Estas claves se guardan en la BD desde el Constructor y **ningún consumidor las
lee** (barrido completo de `app/`, `resources/views/`, `config/`). Todas son de
la etapa Encabezado, familia de presets `hp_*`:

```
hp_boutique_promo_desc      hp_mega_brands           hp_minimal_cta_url
hp_boutique_promo_title     hp_mega_layout           hp_multiverse_use_categories
hp_boutique_promo_url       hp_mega_promo_badge      hp_topbar_social_gap
hp_commercial_cta_title     hp_mega_promo_desc       hp_topbar_social_size
hp_commercial_cta_url       hp_mega_promo_title      hp_topbar_social_style
hp_commercial_wholesale_text hp_mega_promo_url       hp_header_phone_label
```

Verificado contra `app/Support/HeaderPresets.php`, que **no construye claves
dinámicamente** (sin concatenación de cadenas): las 18 están efectivamente
huérfanas. El comerciante las configura y no pasa nada.

### 3.2 · 7 ajustes vivos sin control en el Constructor

El problema simétrico: `HeaderPresets.php` **sí lee** estas claves, pero el
Constructor **no ofrece dónde editarlas**. Solo se pueden cambiar por SQL:

```
hp_boutique_universe_style   hp_mega_open           hp_multiverse_style
hp_catalog_fixed             hp_mega_show_images    hp_search_max
hp_minimal_transparent
```

---

## 4. Quién consume los colores

Los colores de marca **sí están en su sitio**: Apariencia escribe
`primary_color`, `secondary_color`, `accent_color` y la paleta de texto y
superficie. Consumo medido en la tienda:

| Clave | Usos | Clave | Usos |
|---|---|---|---|
| `primary_color` | 27 | `header_text_color` | 7 |
| `secondary_color` | 18 | `header_bg_color` | 7 |
| `hero_bg_color` | 14 | `accent_color` | 2 |
| `footer_text_color` | 9 | `section_head_*_color` | 6 |
| `footer_bg_color` | 9 | `ticker_bg_color` | 1 |

Sin ambigüedad de fuente: **una sola clave por color, un solo lugar de edición**
(salvo `footer_bg_color` y `footer_text_color`, que están en la lista de
colisiones con la etapa 07).

---

## 5. Propiedad recomendada

**02 Apariencia debe quedarse solo con la identidad visual global**, tal como
define la estructura objetivo:

| Se queda en 02 | Se va a |
|---|---|
| Motor (`catalog_template`) | — |
| Preset visual (`theme_preset`) | — |
| Colores de marca y paleta | — |
| Tipografías (`font_title`, `font_body`) | — |
| Bordes, sombras, botones | — |
| Barra superior (`ticker_*`, `announcement_*`) | — |
| **Encabezado y navegación** (las 45 claves de `header`) | — |
| 28 claves `footer_*` | ➜ **07 Footer y legales** |
| 11 `section_*` + 2 `hero_*` | ➜ **03 Página de inicio** |
| 7 claves de tarjeta | ➜ **04 Catálogo** |
| 6 claves `login_*` | ➜ **fuera de "Mi Tienda"** (es el panel) |
| 5 claves `float_*` | ➜ **decisión pendiente** (ver §7) |

**Regla de resolución de la duplicación:** la clave no se renombra ni se migra —
**se elimina el control de la etapa que no es dueña**. Como ambas escriben la
misma clave, quitar el control duplicado **no cambia ningún dato ya guardado**:
es una operación sin riesgo de regresión en las tiendas vivas.

---

## 6. Trabajo que sale de esta revisión

| # | Tarea | Riesgo | Prioridad |
|---|---|---|---|
| 1 | Fundir `header` dentro de `appearance` (claves disjuntas, sin conflicto) | Bajo | Alta |
| 2 | Quitar de Apariencia los 28 controles `footer_*` (los conserva 07) | **Nulo** — misma clave | Alta |
| 3 | Quitar de Apariencia los 13 controles `section_*`/`hero_*` (los conserva 03) | **Nulo** | Alta |
| 4 | Quitar de Apariencia los 7 controles de tarjeta (los conserva 04) | **Nulo** | Alta |
| 5 | Sacar los 6 controles `login_*` de "Mi Tienda" | Bajo | Media |
| 6 | Decidir: eliminar los 18 controles `hp_*` muertos **o** implementar su consumo | Bajo | Media |
| 7 | Exponer los 7 ajustes `hp_*` hoy inalcanzables, o retirarlos de `HeaderPresets` | Bajo | Baja |
| 8 | Unificar la barra superior (se resuelve con la tarea 1) | Nulo | — |

Las tareas 2, 3 y 4 son **retirar interfaz duplicada, no tocar datos**: son el
avance más grande hacia "un dato, un lugar" con riesgo cero.

---

## 7. Decisiones que necesito del usuario

1. **`float_*` (botón flotante de WhatsApp y del carrito)** — la estructura
   objetivo no los menciona. Caben en **02 Apariencia** (son elementos visuales
   persistentes) o en **05 Venta** (el de carrito es comercial). Mi recomendación:
   **02**, porque son decoración persistente de toda la tienda.
2. **Los 18 controles `hp_*` muertos** — ¿se eliminan de la interfaz, o estaban
   pensados para una funcionalidad que aún se quiere? Escribirlos sin consumo
   engaña al comerciante.
3. **`section_spacing`** — "Espaciado" figura en 02 en tu estructura, pero la
   clave controla las secciones de Inicio. ¿Queda en 02 como espaciado global o
   se va a 03 con el resto de `section_*`?

---

## 8. Siguiente paso

Revisión **03 Página de inicio** con el mismo método: las 23 claves de la etapa
`home`, sus 13 colisiones con Apariencia, qué secciones existen frente a las 13
que define la estructura objetivo, y qué consume cada plantilla.
