# Problemas encontrados en el constructor de tienda

## Los 10 problemas más críticos

### 1. El editor de menú y encabezado no está accesible

`store-navigation-builder.blade.php`, su controlador, rutas y tablas existen, pero ninguna vista visible lo incluye. El usuario no puede administrar el menú desde el Diseñador aunque la funcionalidad de backend esté implementada.

**Impacto:** requisitos centrales —texto editable, categorías, submenús, dispositivos y header— parecen inexistentes.

### 2. Nosotros y Contacto tampoco están accesibles en el Diseñador

`institutional-pages.blade.php` existe y `StoreExperienceController::page()` persiste los datos, pero el partial no se incluye en `store-experience.blade.php`.

**Impacto:** no hay forma normal de editar páginas institucionales sin invocar manualmente las rutas.

### 3. Solo CompuTienda separa realmente Inicio, Tienda, Nosotros y Contacto

`PublicController::prepararCatalogo()` pasa `storeView` a todas las plantillas productivas, pero únicamente `computienda.blade.php` consulta esa variable. Ecommerce, Direct y las demás siguen renderizando su página completa.

**Impacto:** `/tienda`, `/nosotros` y `/contacto` pueden mostrar el mismo Inicio/catálogo en lugar de la vista solicitada.

### 4. Existen tres fuentes de verdad para las mismas secciones

- `project_settings` (`hero_*`, banners, contador, trust, tabs).
- `store_sections` (`hero`, anuncios, beneficios, oferta diaria, etc.).
- bloques nativos dentro de cada plantilla.

Luego `public-store-runtime.blade.php` intenta reconciliarlas mediante JavaScript.

**Impacto:** guardar puede producir duplicados, ocultamientos o resultados diferentes al cambiar de plantilla.

### 5. Guardar borrador puede cambiar inmediatamente la tienda publicada

En `saveHomeSection()`, la rama de borrador actualiza `show_desktop`, `show_tablet` y `show_mobile` directamente. Esos campos no tienen equivalente draft.

**Impacto:** aunque el mensaje dice “La tienda publicada no cambió”, la visibilidad por dispositivo sí puede cambiar.

### 6. Fechas de publicación no forman parte completa del borrador

`publish_from` y `publish_until` se validan, pero al guardar borrador no se persisten en campos draft. `publishAll()` tampoco tiene fechas draft que publicar.

**Impacto:** la vista previa no representa una programación pendiente y el usuario puede creer que guardó fechas que se perdieron.

### 7. El runtime depende de manipulación heurística del DOM

`public-store-runtime.blade.php` busca headers, heroes, footers, botones, filtros y textos mediante selectores como clases parciales o contenido visible; después mueve u oculta nodos.

**Impacto:** un cambio visual inocente en una plantilla puede romper configuraciones, provocar saltos, espacios blancos o secciones fuera de orden.

### 8. La ruta Blog depende de V2 aunque las plantillas productivas omiten V2

`StorePageController::blog()` y `blogPost()` retornan 404 si `storefront_structure_v2 != 1`. Sin embargo, `PublicController::catalog()` prioriza cualquier plantilla de `PRODUCTION_TEMPLATE_VIEWS` y no usa la rama V2.

**Impacto:** el menú puede ofrecer Blog y el bloque puede estar publicado, pero la página de listado/post devuelve 404.

### 9. El esquema de categorías destacadas está partido e incompleto

El builder solo guarda título, presentación, cantidad e IDs en `store_sections`. CompuTienda lee más de veinte llaves `featured_categories_*` —forma, estilo, columnas, iconos, uploads, fit, colores, carrusel, conteo, vacías— que no tienen controles visibles ni guardado en `updateDesign()`.

**Impacto:** una parte importante del diseño existe en la plantilla pero no puede administrarse; además no viaja limpiamente a otra plantilla.

### 10. No existe garantía estructural de un componente único ni una plantilla activa única

`store_sections` no tiene único `(project_id,page,component)` y `project_templates` no impide varias filas activas. La inicialización archivó duplicados existentes una sola vez, pero no evita nuevos.

**Impacto:** `groupBy(...)->first()` y `first()` pueden escoger filas ambiguas; la salida depende del ID/orden y no del estado esperado.

## Opciones que se guardan pero no se reflejan de forma confiable

### Confirmadas o estructuralmente no uniformes

- `wholesale_enabled`: se guarda, pero no hay una implementación común de precio mayorista en el renderer auditado.
- `float_cart_pos`: se guarda; CompuTienda usa su carrito de header y no aplica esa posición.
- `footer_pages`, `footer_store_pages`: el runtime los consume, pero CompuTienda usa footer propio y elimina el footer del runtime.
- `footer_newsletter_title`, `footer_newsletter_url`, `footer_show_newsletter`: mismo conflicto con footer propio.
- `login_bg_type`, `login_color1`, `login_color2`, `login_bg_image`, `login_heading`, `login_subtitle`: solo se aplican si el runtime encuentra un panel de login compatible; CompuTienda no contiene uno identificable en el flujo revisado.
- `tab1_label`, `tab2_label`, `tab3_label`: generan pestañas heredadas hacia `#catalogo`, no categorías/destinos reales y pueden quedar ocultas por una sección administrada.
- `show_testimonials` y `show_newsletter`: solo ocultan elementos encontrados por clases heurísticas; no crean una sección si la plantilla no la trae.
- `catalog_quick_view`, `catalog_show_sku`, `catalog_show_stock`, `catalog_show_ratings`: el runtime solo puede ocultar lo que ya exista y encuentre; no agrega la funcionalidad faltante.
- `btn_send_quote_text`, `cart_title`, `cart_empty_msg`, `txt_*`: dependen de coincidencias textuales en el DOM; una plantilla con otro texto queda sin cambios.
- `seo_*`: se inyecta/modifica desde JavaScript en runtime; crawlers que no ejecutan JS pueden recibir el HTML inicial sin esos metadatos.
- `header_hover_color`, `header_active_color`, `header_font`, `header_font_size`, estilos responsive: se guardan por el controlador de navegación, pero el editor está oculto y las plantillas productivas no los consumen uniformemente.
- `header_logo_url`: puede quedar eclipsado por `logo_url`, que CompuTienda prioriza.

### Llaves que CompuTienda consume pero el Diseñador no permite guardar

- `featured_categories_*` (forma, estilo, visual, columnas, iconos/imágenes, colores, conteo, vacías, carrusel y demás).
- `trust_*` avanzadas.
- `promo_*` avanzadas.
- `section_spacing`, `section_background_mode`, `section_card_shadow`, `section_heading_align`, `section_show_dividers`, `section_style_preset`.
- opciones avanzadas de hero como `hero_autoplay`, `hero_duration`, `hero_transition`, `hero_show_arrows`, `hero_show_dots`, `hero_pause_hover`.

Esas opciones solo pueden existir por datos previos/importados; no forman un flujo completo formulario → controlador → persistencia.

## Opciones duplicadas

| Concepto | Fuente 1 | Fuente 2/3 | Riesgo |
|---|---|---|---|
| Hero | `project_settings.hero_*` | `store_sections.hero` + hero nativo | Precedencia distinta por plantilla. |
| Anuncios | `banner*`, `split_*`, `announcement_*` | `store_sections.announcements` + promos nativas | Duplicados/ocultamiento. |
| Oferta diaria | `countdown_*`, `show_flash_sale` | `store_sections.daily_offer` + flash nativo | Contadores distintos. |
| Beneficios | `trust_*` | `store_sections.benefits` + beneficios footer | Contenido repetido en cabecera/Inicio/footer. |
| Categorías | `tab*_label`, `featured_categories_*` | `store_sections.featured_categories` + categorías nativas | Tres esquemas incompatibles. |
| Logo | `logo_url`, `logo_height` | `header_logo_url`, `header_logo_height` | Uno puede eclipsar al otro. |
| Header | pestaña Marca | editor de navegación | Los mismos colores/alto se guardan desde dos lugares. |
| WhatsApp | `whatsapp_msg`, `float_wa_*` | `quote_wa_msg`, `quote_whatsapp`, aliases de plantilla | Número/mensaje/visibilidad divergentes. |
| Productos destacados | bloque `featured_products` | `$featured` nativo aleatorio | Orden/selección manual se puede perder. |
| Footer | partial/runtime configurable | footer propio de cada plantilla | Ajustes no transferibles. |
| Plantilla | `project_settings.catalog_template` | `ProjectTemplate.settings.catalog_template` | Activación y defaults pueden no representar lo visible. |

## Secciones que pueden figurar publicadas y no aparecer

1. **Cualquier sección con `is_enabled=true` fuera de `publish_from/publish_until`.** La consulta pública la excluye.
2. **Beneficios sin items habilitados.** El renderer no produce HTML.
3. **Anuncios sin items habilitados o dentro de fechas válidas.** Se omiten aunque la sección esté publicada.
4. **Categorías destacadas sin categorías activas/seleccionadas válidas.** Se omiten.
5. **Solo por hoy expirado con `expired_action=hide`.** Se omite por diseño.
6. **Productos con descuento sin productos con `compare_price > price`.** Se omite.
7. **Productos destacados sin productos disponibles o IDs manuales vigentes.** Se omite.
8. **Blog sin artículos habilitados.** No aparece en Inicio; su ruta además puede dar 404 sin V2.
9. **Cualquier sección oculta en el dispositivo actual.** Puede estar publicada pero tener clase `display:none`.
10. **`featured_products` en CompuTienda/runtime con `ownFooter=true`.** El renderer canónico la rechaza para evitar duplicidad y depende de que el bloque nativo la represente correctamente.
11. **Secciones canónicas cuyo alias no se normalice en una plantilla nativa.** El runtime puede no hallar el bloque propietario correcto.
12. **Pop-up activo fuera de fechas, oculto para el dispositivo o suprimido por frecuencia en storage.** No aparece aunque el panel diga activo.
13. **Nosotros/Contacto con `is_enabled=false`.** V2 retorna 404; en plantillas productivas `productionPageView()` actualmente ni siquiera aplica el mismo filtro, creando comportamiento contradictorio.

## Caché y datos vencidos

- No se encontró una caché de settings o secciones en los controladores revisados; se consultan desde BD por request.
- Sí puede existir caché de Blade/config/rutas del despliegue (`view:cache`, `config:cache`, OPcache), por lo que cambios de código pueden no verse hasta limpiar/reiniciar.
- Frecuencia de pop-up usa storage del navegador: una prueba puede ocultarlo aunque la configuración sea correcta.
- Contadores y fechas dependen de timezone del servidor/aplicación y del reloj del navegador.
- El hero y varias opciones se aplican tras `DOMContentLoaded`; antes de ejecutar JS puede verse el contenido nativo o producirse salto visual.

## Archivos que sería necesario modificar

### Núcleo obligatorio

- `resources/views/settings/design.blade.php`
- `resources/views/settings/store-experience.blade.php`
- `resources/views/settings/partials/home-builder.blade.php`
- `resources/views/settings/partials/home-section-fields.blade.php`
- `resources/views/settings/partials/store-navigation-builder.blade.php`
- `resources/views/settings/partials/institutional-pages.blade.php`
- `app/Http/Controllers/SettingsController.php`
- `app/Http/Controllers/StoreExperienceController.php`
- `app/Http/Controllers/StoreNavigationController.php`
- `app/Http/Controllers/PublicController.php`
- `app/Http/Controllers/StorePageController.php`
- `app/Support/StorefrontSections.php`
- `app/Support/StorefrontNavigation.php`
- `app/Support/CatalogTemplates.php`
- `resources/views/components/storefront-home-sections.blade.php`
- `resources/views/components/public-store-runtime.blade.php`

### Layout y plantillas

- Crear/adoptar partials compartidos para header/footer.
- `resources/views/public/templates/computienda.blade.php`
- `resources/views/public/templates/ecommerce.blade.php`
- `resources/views/public/templates/direct.blade.php`
- cada vista registrada en `PublicController::PRODUCTION_TEMPLATE_VIEWS`, o un adaptador común que evite editarlas una por una.
- `resources/views/public/storefront/*` para consolidar la rama V2.

### Pruebas

- `tests/Feature/StorefrontHomepageBuilderTest.php`
- `tests/Feature/StorefrontStructureV2Test.php`
- `tests/Feature/GlobalTemplateSettingsTest.php`
- nuevas pruebas de contrato por plantilla, páginas, menú y borrador.

## Cambios que requieren migraciones

### Recomendados

1. Añadir `draft_show_desktop`, `draft_show_tablet`, `draft_show_mobile`, `draft_publish_from`, `draft_publish_until` a `store_sections`.
2. Añadir único `(project_id,page,component)` después de resolver/archivar duplicados existentes.
3. Garantizar una sola `ProjectTemplate` activa por proyecto. MySQL no ofrece índice parcial simple; puede resolverse con columna/tabla de referencia activa o transacción + bloqueo, pero la garantía fuerte requiere cambio de diseño de datos.
4. Si Blog crecerá más allá de tres items JSON, crear `store_posts` con clave única por proyecto, estado, fechas y SEO.
5. Si se desea versionado/publicación para header, menú, páginas y pop-up, hacen falta columnas draft/versiones equivalentes; hoy solo `store_sections` tiene borrador parcial.

### No requieren migración

- Volver visibles los partials de menú/páginas.
- Corregir precedencia y validación.
- Crear un registro canónico de settings en PHP.
- Renderizar SEO/header/footer en servidor.
- Separar Inicio/Tienda en vistas y rutas usando las tablas actuales.
- Hacer que las plantillas consuman un view-model compartido.
- Eliminar gradualmente la reubicación por JavaScript.

## Riesgos de compatibilidad

1. Cambiar precedencia puede alterar tiendas que hoy dependen de `project_settings` heredados.
2. Eliminar runtime de golpe rompería plantillas que no consumen variables directamente.
3. Unificar logo/header puede cambiar cuál de dos valores existentes gana.
4. Separar Inicio/Tienda modificará URLs/anclas y puede afectar enlaces externos/SEO.
5. Un índice único fallará si existen duplicados no archivados; requiere migración de datos previa y reporte.
6. Normalizar teléfonos/URLs puede rechazar valores antiguos que hoy pasan como texto.
7. Convertir Blog JSON a tabla necesita migración reversible y compatibilidad de lectura temporal.
8. Plantillas de rubros de servicios mezclan servicios como productos en `prepararCatalogo()`; un catálogo unificado debe conservar ese comportamiento.
9. El menú en dominios personalizados construye URLs distintas a las basadas en slug; deben probarse ambos modos.
10. Los estilos globales del renderer canónico pueden colisionar con CSS de plantillas productivas.
11. Cambiar el estado por defecto de bloques puede hacer aparecer contenido que antes estaba oculto.
12. La activación `storefront_structure_v2` hoy tiene semántica diferente para plantillas productivas y no productivas; migrarla requiere rollout por tienda y vista previa.

## Evidencia de pruebas en esta auditoría

Se intentó ejecutar:

```powershell
C:\xampp\php\php.exe artisan test --compact \
  tests/Feature/GlobalTemplateSettingsTest.php \
  tests/Feature/StorefrontHomepageBuilderTest.php \
  tests/Feature/StorefrontStructureV2Test.php \
  tests/Unit/CatalogTemplatesManifestTest.php \
  tests/Unit/PublicTemplateRuntimeTest.php
```

El proceso no produjo salida ni terminó dentro de aproximadamente 45 segundos y fue detenido para no dejar una tarea bloqueada. Por tanto, esta auditoría no declara la suite en verde; el bloqueo/tiempo de inicialización del entorno debe investigarse antes de implementar.
