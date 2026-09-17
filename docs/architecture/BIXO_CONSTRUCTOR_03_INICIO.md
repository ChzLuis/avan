# Constructor — Revisión 03 · Página de inicio

Auditoría verificada el **2026-08-30** contra el código y la BD de producción.
Tercer paso de la reorganización de "Mi Tienda" en 9 etapas.

**Principio obligatorio:** cada dato o configuración tiene un único propietario
y un único lugar donde se modifica. Las demás áreas solo lo consumen.

---

## 1. Lo que ya funciona bien

**Las 7 capacidades que pide la estructura objetivo ya existen en el modelo de
datos.** La tabla `store_sections` tiene el ciclo borrador→publicado completo:

| Capacidad pedida | Columna que ya la soporta |
|---|---|
| Activar o desactivar | `is_enabled` / `draft_is_enabled` |
| Editar contenido | `content` / `draft_content` (JSON) |
| Cambiar variante visual | `variant` / `draft_variant` |
| Reordenar | `sort_order` / `draft_sort_order` |
| Programar | `publish_from` / `publish_until` |
| Guardar borrador | `has_draft` + columnas `draft_*` |
| Publicar | `published_at` |

Además hay visibilidad por dispositivo (`show_desktop` / `show_tablet` /
`show_mobile`, con sus `draft_*`), que la estructura objetivo no pide pero la
vista previa de 09 aprovechará.

**No hay que construir nada de esto: hay que exponerlo bien.**

---

## 2. Las secciones: existen 20, la estructura objetivo lista 13

### 2.1 · Uso real en producción

| Sección | Tiendas | **Activas** | ¿En tu lista de 13? |
|---|---|---|---|
| `featured_products` Productos destacados | 8 | **6** | ✅ |
| `hero` Banner principal | 8 | **6** | ✅ |
| `benefits` Beneficios | 8 | **5** | ✅ |
| `featured_categories` Categorías principales | 8 | **4** | ✅ |
| `about_preview` Información de la empresa | 7 | **4** | ✅ |
| `cta_banner` Llamada a la acción | 7 | **4** | ✅ |
| `brands` Marcas | 7 | **3** | ✅ |
| `info_strip` Banda informativa | 7 | **3** | ❌ **no está** |
| `wa_advisory` Asesoría por WhatsApp | 7 | **3** | ❌ **no está** |
| `faq` Preguntas frecuentes | 7 | **3** | ❌ *(la pones en 06 Páginas)* |
| `discounts` Productos con descuento | 8 | 2 | ✅ |
| `blog` Blog | 8 | 1 | ✅ |
| `daily_offer` Solo por hoy | 8 | 1 | ✅ |
| `announcements` Anuncios | 8 | 1 | ✅ |
| `collection_showcase` Colecciones / Ambientes | 7 | 1 | ❌ **no está** |
| `locations` Sucursales | 7 | 1 | ❌ *(la pones en 01/06)* |
| `testimonials` Testimonios | 7 | **0** | ✅ *(nadie la usa)* |
| `gallery` Galería | 7 | 0 | ❌ |
| `media_banner` Banner multimedia | 7 | 0 | ❌ |
| `category_rows` Filas por categoría | 7 | 0 | ❌ |
| `promotions` *(heredada)* | 1 | 0 | ❌ |

### 2.2 · Los tres desajustes

1. **5 secciones fuera de tu lista están ACTIVAS en tiendas reales**:
   `info_strip` (3), `wa_advisory` (3), `faq` (3), `collection_showcase` (1),
   `locations` (1). **Implementar las 13 al pie de la letra borraría contenido
   vivo de clientes.** Hay que decidir explícitamente qué pasa con ellas.
2. **Newsletter (tu nº 13) no existe como sección de Inicio.** Hoy vive en el
   pie (`footer_newsletter_title`, `footer_newsletter_url`). O se crea la
   sección, o se acepta que Newsletter pertenece a 07.
3. **Testimonios (tu nº 9) existe pero ninguna tienda lo usa** (0 activas de 7).
   Está construido y desaprovechado.

`faq` y `locations` son un caso aparte: existen **a la vez** como sección de
Inicio y como página en 06. No es un error —son cosas distintas— pero la matriz
debe decir cuál es la fuente del contenido para que no se escriba dos veces.

---

## 3. La duplicación: dos almacenes para la misma sección

Aquí está el problema de fondo de esta etapa. El contenido de una sección vive
en `store_sections`, pero **su estilo y varios de sus textos viven en
`project_settings`**:

| Dato de la sección | Dónde se guarda |
|---|---|
| Contenido, variante, orden, activación, programación | `store_sections` ✅ |
| Estilo, títulos, colores, animación | `project_settings` ⚠️ |

### El título está en los dos sitios — y gana el equivocado

`computienda.blade.php:503`:

```php
$featuredCatsTitle = trim($settings['featured_categories_title']
    ?? $categoriesSectionContent['title']
    ?? 'Explora por categoría');
```

**El ajuste gana sobre el contenido de la sección.** Datos reales de producción:

```json
proyecto 10 → {"title": "Nuestras líneas", "subtitle": "Cable, protección…", …}
proyecto  7 → {"title": "Encuentra lo que buscas", …}
```

Si además existe un `featured_categories_title` viejo, **lo que el comerciante
escriba en el editor de la sección no se verá nunca**. Es un control que parece
funcionar y no funciona.

### Claves construidas dinámicamente

`home` declara `anim_` e `intro_`, que **no son claves sino prefijos**:
`anim_{componente}_type`, `anim_{componente}_duration`, `anim_{componente}_delay`
e `intro_{nativo}_style`. Con 20 componentes, el recuento real de claves de esta
etapa es muy superior a las 23 que aparecen a simple vista.

> Esto es la contrapartida de los "controles muertos" de la revisión 02: antes de
> declarar muerta una clave hay que comprobar si se construye por concatenación.

### Colisiones con Apariencia

Las 8 ya documentadas en la revisión 02: `section_background_mode`,
`section_card_shadow`, `section_heading_align`, `section_show_dividers`,
`section_spacing`, `section_style_preset`, `hero_px_desktop`, `hero_px_mobile`.

---

## 4. Hallazgo transversal: hay CINCO lugares que configuran la tienda

La revisión 01 encontró tres puertas. Esta encuentra **dos más**:

| # | Lugar | Ruta | Cómo se llega | Qué escribe |
|---|---|---|---|---|
| 1 | **Constructor** | `settings/builder` | Menú principal | *(el objetivo)* |
| 2 | Diseño clásico | `settings/design` (`web.php:402`) | Menú "Diseño" de `bots/app.blade.php:199`; **el propio Constructor lo enlaza como `'classic'`** (`builder/index.blade.php:19`); y un enlace dentro de la plantilla `ecommerce` (`:4143`) | colores, footer, contacto, logo, títulos de sección |
| 3 | **Diseñador visual** | `settings/designer` (`web.php:417`) | **Sin enlace de navegación** — solo escribiendo la URL | **11 editores de sección** (hero, categorías, beneficios, footer, header, producto, checkout…) |
| 4 | Ajustes | `settings/index` | Menú | phone, whatsapp, address, razon_social |
| 5 | Páginas institucionales | `settings/partials/institutional-pages` | Dentro de Ajustes | phone, whatsapp, address |

El nº 3 es un **constructor paralelo completo** con 11 editores de sección,
ruteado pero huérfano. El nº 2 es especialmente delicado: **el Constructor
enlaza al editor antiguo desde dentro de sí mismo**.

---

## 5. Propiedad recomendada

| Configuración | Fuente canónica | Se configura en |
|---|---|---|
| Activación, orden, variante, programación, visibilidad | `store_sections` (+ `draft_*`) | 03 Inicio |
| Contenido y textos de cada sección (título, subtítulo, ítems) | `store_sections.content` | 03 Inicio |
| Estilo global de secciones (`section_*`, `hero_px_*`) | ajustes | **decisión: 02 o 03** *(ver §7)* |
| Animación por sección (`anim_*`, `intro_*`) | ajustes | 03 Inicio |
| Qué productos/categorías muestra cada sección | `store_sections.content` (ids) | 03 Inicio, alimentado por 04 |

**Regla:** `store_sections.content` debe ser la **única** fuente de los textos de
sección. Las claves `*_title` / `*_subtitle` de ajustes pasan a alias de
transición, y **hay que invertir la cadena de resolución** para que el contenido
gane sobre el ajuste — hoy es al revés.

⚠️ Invertirla cambia lo que se ve en tiendas donde ambas existen. Requiere
migración previa: volcar el ajuste al `content` cuando el `content` esté vacío,
igual que hizo la siembra de la revisión 01.

---

## 6. Trabajo que sale de esta revisión

| # | Tarea | Riesgo | Prioridad |
|---|---|---|---|
| 1 | Decidir el destino de las 5 secciones activas fuera de la lista de 13 | — | **Alta** (bloquea) |
| 2 | Migrar `*_title`/`*_subtitle` de ajustes a `store_sections.content` e invertir la resolución | **Medio** — cambia lo visible | Alta |
| 3 | Quitar el enlace `'classic'` que el Constructor hace a Diseño clásico | Bajo | Alta |
| 4 | Decidir sobre `settings/designer`: retirar la ruta o absorber sus 11 editores | Bajo | Alta |
| 5 | Newsletter: crear la sección en 03 o asumir que pertenece a 07 | Bajo | Media |
| 6 | Exponer la programación (`publish_from/until`) y la visibilidad por dispositivo | Bajo | Media |
| 7 | Testimonios: promocionarla o retirarla (0 uso en 7 tiendas) | Bajo | Baja |
| 8 | Retirar `promotions` (heredada, 1 tienda, 0 activas) | Bajo | Baja |

---

## 7. Decisiones que necesito del usuario

1. **Las 5 secciones activas que no están en tu lista de 13** — `info_strip`,
   `wa_advisory`, `faq`, `collection_showcase`, `locations`. ¿Se incorporan a la
   lista, se marcan como "avanzadas", o se retiran avisando a esos clientes?
   **Recomiendo incorporarlas**: están en uso y funcionan.
2. **Newsletter** — ¿sección propia de Inicio, o se queda en el pie (07)?
3. **`section_*` y `hero_px_*`** — misma pregunta que dejé en la revisión 02:
   ¿estilo global en 02, o estilo de Inicio en 03? Elegir uno cierra 8 colisiones.
4. **`settings/designer`** — ¿alguien lo usa, o se retira la ruta? No tiene
   enlace de navegación; si nadie lo abre, es la limpieza más barata del proyecto.

---

## 8. Siguiente paso

Revisión **04 Catálogo**: las 37 claves de la etapa, sus 7 colisiones con
Apariencia, la frontera entre gestión (Categorías/Productos, que abren el
administrador existente) y presentación (Tarjetas, Filtros, Página de producto),
y qué consume cada plantilla.
