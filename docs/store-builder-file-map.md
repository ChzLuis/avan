# Mapa técnico del constructor de tienda

Fecha de auditoría: 2026-07-28. Alcance: lectura del código actual; no se modificó lógica de la aplicación.

## Flujo real actual

```text
Diseñador
  resources/views/settings/design.blade.php
    ├─ formularios globales por pestaña → POST settings.design.update
    │    └─ SettingsController::updateDesign()
    │         └─ project_settings (project_id + key + value)
    └─ pestaña "Inicio y páginas"
         └─ settings/store-experience.blade.php
              ├─ partials/home-builder.blade.php
              │    └─ POST settings.experience.home.save
              │         └─ StoreExperienceController::saveHomeSection()
              │              └─ store_sections (publicado + borrador)
              └─ formulario pop-up
                   └─ StoreExperienceController::popup()
                        └─ store_popups

Selección de plantilla
  POST settings.design.applyTemplate
    └─ SettingsController::applyTemplate()
         ├─ project_settings.catalog_template
         ├─ completa solamente settings vacíos con defaults del catálogo
         └─ desactiva ProjectTemplate activo

Tienda pública /{slug}
  PublicController::catalog()
    ├─ si catalog_template existe en PRODUCTION_TEMPLATE_VIEWS
    │    └─ prepararCatalogo() → resources/views/public/templates/{plantilla}.blade.php
    └─ si no es plantilla de producción y storefront_structure_v2=1
         └─ storefrontBaseData() → resources/views/public/storefront/templates/*.blade.php

CompuTienda
  resources/views/public/templates/computienda.blade.php
    ├─ normaliza componentes store_sections a claves nativas
    ├─ renderiza Inicio/Tienda/Nosotros/Contacto en la misma vista
    └─ <x-public-store-runtime>
         ├─ <x-storefront-home-sections>
         ├─ aplica parte de project_settings mediante JS sobre HTML existente
         └─ renderiza pop-up y compatibilidad de checkout/footer
```

## Archivos de administración

| Archivo | Responsabilidad actual | Observación de auditoría |
|---|---|---|
| `resources/views/settings/design.blade.php` | Pantalla principal, selector de plantilla y formularios Marca/Portada/Catálogo/Checkout/Sistema/Inicio | Tiene siete pestañas. El manifiesto se calcula, pero la mayoría de controles no se filtran realmente por compatibilidad de plantilla. |
| `resources/views/settings/store-experience.blade.php` | Contenedor de Inicio y pop-up | Solo incluye constructor de Inicio y pop-up; no incluye menú ni páginas institucionales. |
| `resources/views/settings/partials/home-builder.blade.php` | Tarjetas por sección, borrador/publicación, orden, fechas y dispositivos | El orden visual se cambia con JS y se persiste con un formulario separado. |
| `resources/views/settings/partials/home-section-fields.blade.php` | Campos específicos de las ocho secciones de Inicio | Es la fuente real de nombres `content[...]` y opciones disponibles. |
| `resources/views/settings/partials/home-products-picker.blade.php` | Selección manual de productos | Lista todos los productos del proyecto en checkboxes. |
| `resources/views/settings/partials/store-navigation-builder.blade.php` | Editor de encabezado y menú | Implementado, pero huérfano: no está incluido por `design.blade.php` ni por `store-experience.blade.php`. |
| `resources/views/settings/partials/store-menu-item.blade.php` | Edición de cada opción/submenú | Solo es alcanzable si se incluye el builder anterior. |
| `resources/views/settings/partials/institutional-pages.blade.php` | Formularios Nosotros y Contacto | Implementado, pero huérfano en la interfaz actual. |

## Controladores y soporte

| Archivo | Métodos relevantes | Persistencia/efecto |
|---|---|---|
| `app/Http/Controllers/SettingsController.php` | `design`, `updateDesign`, `applyTemplate`, `applyProjectTemplate` | `project_settings`, `project_templates`; las plantillas solo rellenan valores vacíos. |
| `app/Http/Controllers/StoreExperienceController.php` | `saveHomeSection`, `reorderHome`, `publishAll`, `preview`, `page`, `popup` | `store_sections`, `store_pages`, `store_popups`. |
| `app/Http/Controllers/StoreNavigationController.php` | `updateHeader`, `publishStructure`, CRUD/reorder de menú | `project_settings`, `store_menus`, `store_menu_items`. |
| `app/Http/Controllers/PublicController.php` | `catalog`, `shop`, `renderStorefrontHome`, `storefrontBaseData`, `prepararCatalogo` | Decide vista, mezcla ajustes y carga contenido público. |
| `app/Http/Controllers/StorePageController.php` | `about`, `contact`, `page`, `blog`, `blogPost`, `sendContact` | Render de páginas y recepción de contacto. |
| `app/Support/StorefrontSections.php` | Catálogo y defaults de ocho componentes; `ensure()` | Inicializa secciones faltantes en `store_sections`. |
| `app/Support/StorefrontNavigation.php` | Menú por defecto, ajustes de encabezado, resolución de URL | Inicializa menú/páginas y resuelve destinos con aislamiento por proyecto. |
| `app/Support/CatalogTemplates.php` | Catálogo, manifiesto y defaults de plantillas | El manifiesto no garantiza que la vista Blade consuma cada ajuste declarado. |
| `app/Support/StorefrontTheme.php` | Familia visual V2 | Solo interviene en la rama V2 genérica, no reemplaza las vistas de producción. |

## Modelos y relaciones

| Modelo | Tabla | Relaciones/campos relevantes |
|---|---|---|
| `Project` | `projects` | `settings`, `storeSections`, `storePages`, `storePopups`, `storeMenus`; `setting()` realiza una consulta por llamada. |
| `ProjectSetting` | `project_settings` | Par único `project_id,key`; valores de texto sin tipado. |
| `StoreSection` | `store_sections` | Publicado y borrador en la misma fila; helpers `contentForPreview`, `variantForPreview`, `enabledForPreview`. |
| `ProjectTemplate` | `project_templates` | Snapshot JSON de settings y bandera activa; la BD no impide varias activas. |
| `StorePage` | `store_pages` | Clave única por proyecto, JSON `content`, `is_enabled`. |
| `StorePopup` | `store_popups` | Configuración, fechas, frecuencia y visibilidad PC/celular. |
| `StoreMenu` | `store_menus` | Menú único por proyecto y ubicación. |
| `StoreMenuItem` | `store_menu_items` | Destino, jerarquía de un nivel, orden y visibilidad por dispositivo. |
| `Category` | `categories` | Árbol padre/hijos aislado por proyecto. |
| `Product` | `products` | Categoría, precios, stock, disponibilidad, imágenes y orden. |

## Rutas relevantes

Todas están en `routes/web.php`:

- Diseñador: `settings.design`, `settings.design.update`, `settings.design.applyTemplate`, `settings.design.applyProjectTemplate`.
- Constructor: `settings.experience.home.save`, `settings.experience.home.reorder`, `settings.experience.home.publishAll`, `settings.experience.preview`.
- Contenido auxiliar: `settings.experience.page`, `settings.experience.popup`.
- Navegación: `settings.storefront.header`, `settings.storefront.publish`, CRUD y reorder de `settings.storefront.menu.*`.
- Público: `public.catalog`, `public.shop`, `public.about`, `public.contact`, `public.blog`, `public.blog.show`, `public.page`.

## Migraciones relevantes

| Migración | Aporte | Carencia detectada |
|---|---|---|
| `2026_03_10_205434_create_project_settings_table.php` | `project_settings` y único `(project_id,key)` | No hay esquema/tipo/registro central de llaves. |
| `2026_07_22_000000_create_project_templates_table.php` | Plantillas de proyecto | No existe único parcial que garantice una sola plantilla activa por proyecto. |
| `2026_07_23_000000_create_store_experience_tables.php` | Secciones, páginas y pop-ups | No hay único `(project_id,page,component)`; pop-up no tiene tablet ni borrador. |
| `2026_07_23_000002_add_draft_fields_to_store_sections.php` | Borrador de contenido/variante/orden/activo | Fechas y visibilidad por dispositivo no tienen columnas draft. |
| `2026_07_23_000003_initialize_home_builder_sections.php` | Inicializa y archiva duplicados existentes | Compensa duplicados una sola vez, no los impide después. |
| `2026_07_23_000004_disable_untouched_builder_defaults.php` | Desactiva defaults no editados | Puede dejar Inicio vacío hasta publicar bloques. |
| `2026_07_24_000000_create_store_menus_tables.php` | Menús y submenús | `destination_id` no tiene FK polimórfica; integridad depende del controlador. |

## Vistas públicas

| Archivo | Papel | Compatibilidad observada |
|---|---|---|
| `resources/views/public/templates/computienda.blade.php` | Plantilla productiva especializada | Única plantilla productiva que implementa explícitamente `storeView=home/tienda/nosotros/contacto`. |
| `resources/views/public/templates/ecommerce.blade.php` | Plantilla productiva | Recibe runtime global, pero no interpreta `storeView`; Inicio/Tienda no están realmente separados. |
| `resources/views/public/templates/direct.blade.php` | Plantilla productiva | Igual que Ecommerce; no interpreta `storeView`. |
| `resources/views/public/templates/*.blade.php` | Otras plantillas productivas | Solo CompuTienda referencia `storeMenu` y `storeView` directamente. |
| `resources/views/components/storefront-home-sections.blade.php` | Renderer canónico de ocho bloques | Consulta productos/categorías durante el render y omite secciones sin datos. |
| `resources/views/components/public-store-runtime.blade.php` | Capa de compatibilidad | Modifica/mueve/oculta DOM con selectores heurísticos; el resultado depende del HTML de cada plantilla. |
| `resources/views/public/storefront/*` | Rama V2 genérica | Tiene layout compartido, catálogo paginado y páginas, pero las plantillas productivas la evitan. |
| `resources/views/components/public-popup.blade.php` | Pop-up público | Aplica delay/frecuencia y dispositivo; depende de que la plantilla incluya runtime. |

## Sistemas superpuestos

1. **Portada global** en `project_settings` (`hero_*`, banners, contador, trust, tabs).
2. **Portada publicable** en `store_sections` (`hero`, `announcements`, `daily_offer`, etc.).
3. **Secciones nativas de cada plantilla** en sus Blade.
4. **Runtime JavaScript** que intenta ocultar/reubicar secciones nativas cuando detecta una sección administrada.
5. **Rama V2** que renderiza un layout distinto, pero queda omitida para toda clave incluida en `PRODUCTION_TEMPLATE_VIEWS`.

La ausencia de una única fuente de verdad explica la mayor parte de los comportamientos inconsistentes.
