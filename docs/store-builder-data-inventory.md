# Inventario de datos del Store Builder

Fecha: 2026-07-28. Todas las verificaciones de este documento se realizaron con consultas `SELECT`. No se ejecutaron `INSERT`, `UPDATE`, `DELETE`, migraciones ni seeders.

## Alcance y base consultada

La base MySQL local estaba detenida. Se inició temporalmente el proceso local de XAMPP únicamente para efectuar el inventario de solo lectura. Las tablas relevantes contenían:

| Tabla | Registros |
|---|---:|
| `projects` | 5 |
| `project_settings` | 331 |
| `store_sections` | 43 |
| `project_templates` | 0 |

## A. Duplicados en `store_sections`

Agrupación examinada: `project_id + page + component`.

Resultado: **0 grupos duplicados y 0 filas afectadas**.

No existen filas que requieran detallar IDs, estado, borrador u orden. La consulta comprobó `COUNT(*) > 1` e incluía, para cualquier coincidencia, `project_id`, `page`, `component`, cantidad, IDs, `is_enabled`, `has_draft`, `sort_order` y `draft_sort_order`.

## B. Proyectos con varias plantillas activas

Resultado: **0 proyectos**.

La tabla `project_templates` está vacía, por lo que no existe ninguna fila activa ni un conflicto de múltiples activas. Esto evita el conflicto actual, pero también confirma que la plantilla efectiva depende de `project_settings.catalog_template` y no de `ProjectTemplate`.

## C. Conflictos de configuración

Se detectaron **14 grupos de conflicto en 4 proyectos**. El conteo representa una combinación proyecto/tipo de conflicto, no la cantidad de claves individuales.

| # | Proyecto | Nombre | Conflicto |
|---:|---:|---|---|
| 1 | 3 | Tecsist Solution | `hero_*` y `store_sections.hero` |
| 2 | 3 | Tecsist Solution | `trust_*` y `store_sections.benefits` |
| 3 | 3 | Tecsist Solution | `countdown_*`/`show_flash_sale` y `store_sections.daily_offer` |
| 4 | 3 | Tecsist Solution | `whatsapp_msg` y `quote_wa_msg` |
| 5 | 3 | Tecsist Solution | `footer_*` y footer propio de `computienda` |
| 6 | 12 | LICORERIA | `hero_*` y `store_sections.hero` |
| 7 | 12 | LICORERIA | `trust_*` y `store_sections.benefits` |
| 8 | 12 | LICORERIA | `footer_*` y footer propio de `computienda` |
| 9 | 14 | Lavandería Burbujas | `hero_*` y `store_sections.hero` |
| 10 | 14 | Lavandería Burbujas | `trust_*` y `store_sections.benefits` |
| 11 | 14 | Lavandería Burbujas | `footer_*` y footer propio de `lavanderia` |
| 12 | 15 | Prueba tu suerte | `hero_*` y `store_sections.hero` |
| 13 | 15 | Prueba tu suerte | `trust_*` y `store_sections.benefits` |
| 14 | 15 | Prueba tu suerte | `footer_*` y footer propio de `ecommerce` |

No se encontraron conflictos para:

- `logo_url` frente a `header_logo_url`: no existen ambos valores distintos en un mismo proyecto.
- `logo_height` frente a `header_logo_height`.
- `featured_categories_*` frente a `store_sections.featured_categories`.
- `catalog_template` frente a una `ProjectTemplate` activa, porque `project_templates` no tiene filas.

El proyecto 13, LA TERRAZA, no presentó conflictos de los tipos solicitados.

## D. Secciones habilitadas que no aparecerían

Para evitar falsos positivos, se consideró “publicada” una sección con `is_enabled = true` y se aplicaron las condiciones públicas observadas en el código: fechas, dispositivo, contenido y referencias existentes.

Se identificaron **2 secciones habilitadas con una causa concreta de no aparición**:

| Proyecto | Sección | Componente | Causa |
|---:|---:|---|---|
| 3 | 8 | `daily_offer` | Finalizó el 2026-07-23 22:16 y tiene `expired_action=hide`; al 2026-07-28 queda oculta. |
| 15 | 42 | `featured_products` | Selección automática sin productos disponibles; el bloque puede quedar sin contenido y omitirse. |

Las otras secciones habilitadas examinadas sí tienen condiciones mínimas para mostrarse. No se encontraron secciones habilitadas excluidas por:

- `publish_from` o `publish_until` vigentes.
- visibilidad por dispositivo.
- IDs manuales inexistentes.
- categorías destacadas inválidas.
- productos sin descuento en un bloque de descuentos habilitado.
- artículos inexistentes en un bloque Blog habilitado.

Una fila con `published_at` pero `is_enabled=false` no se contó como públicamente visible: el runtime exige que esté habilitada.

## Observaciones de integridad

- No hay duplicados actuales, pero la base no impone una restricción única sobre `project_id + page + component`.
- No hay múltiples plantillas activas porque `project_templates` está vacía; tampoco existe evidencia de una restricción que garantice una sola activa por proyecto cuando empiece a usarse.
- La convivencia entre claves heredadas y `store_sections` produce dos fuentes válidas para Hero, Beneficios y Oferta diaria.
- La configuración del footer también compite con el markup propio de varias plantillas.
- Estas observaciones son inventario, no una autorización para limpiar o migrar datos.
