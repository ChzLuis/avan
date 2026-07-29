# Inventario reproducible del Store Builder

Fecha: 2026-07-29. Rama: `stabilize/store-builder-baseline`. Base original: `e788ee3`. Alcance: únicamente consolidación Git y comprobación desde un checkout limpio; no incluye Fase 1, cambios funcionales ni datos reales.

## Estado de partida

| Dato | Valor |
|---|---:|
| HEAD inicial del Paso 6 | `8b22f36f6b7795dd0c7fff8479261d29fcd8f8f4` |
| Archivos modificados | 56 |
| Entradas sin seguimiento expandidas | 7,823 |
| Entradas preparadas | 0 |
| Conflictos | 0 |
| Entradas ignoradas observadas | 23,159 |

Los commits de estabilización anteriores a este paso son `0b67a74`, `fddc8c3`, `3c9aa86`, `250b4d3`, `6c41587`, `0655912`, `7a7e7c9`, `8d86811`, `4d5591c`, `00e8a99`, `74eb3a0`, `1ec726d` y `8b22f36`.

## Archivos indispensables

La columna **Commit** identifica el commit que incorporó el archivo faltante o el último commit del contrato estabilizado. Todos los archivos de esta tabla están rastreados, no están ignorados y están presentes en el checkout limpio. `Sí` en pruebas significa que el archivo es usado directamente por las seis suites de línea base o por una vista que ellas renderizan.

| Ruta | Responsabilidad | Commit | Pruebas | Ámbito |
|---|---|---|---|---|
| `app/Http/Controllers/SettingsController.php` | Diseñador, guardado y aplicación de plantillas | `7a7e7c9` | Sí | Store Builder |
| `app/Http/Controllers/PublicController.php` | Inicio, tienda, plantilla pública y datos compartidos | `7a7e7c9` | Sí | Store Builder |
| `app/Http/Controllers/StoreExperienceController.php` | Constructor, publicación, páginas y pop-up | `732268b` | Sí | Store Builder |
| `app/Http/Controllers/StoreNavigationController.php` | Encabezado, menú, destinos y orden | `732268b` | Sí | Store Builder |
| `app/Http/Controllers/StorePageController.php` | Nosotros, Contacto, Blog y páginas | `732268b` | Sí | Store Builder |
| `app/Http/Controllers/DemoController.php` | Inicialización de proyecto de demostración | `732268b` | Indirecto | Compartido |
| `app/Http/Controllers/ProjectController.php` | Inicialización de proyectos | `732268b` | Indirecto | Compartido |
| `app/Http/Middleware/DetectCustomDomain.php` | Dominio público y aislamiento de tienda | `732268b` | Indirecto | Compartido |
| `app/Models/Project.php` | Relaciones del constructor y settings | `732268b` | Sí | Compartido |
| `app/Models/ProjectSetting.php` | Configuración por proyecto | anterior a `e788ee3` | Sí | Compartido |
| `app/Models/ProjectTemplate.php` | Plantillas guardadas por proyecto | `732268b` | Sí | Store Builder |
| `app/Models/StoreSection.php` | Secciones publicadas y borradores | `732268b` | Sí | Store Builder |
| `app/Models/StorePage.php` | Páginas institucionales | `732268b` | Sí | Store Builder |
| `app/Models/StorePopup.php` | Pop-up promocional | `732268b` | Sí | Store Builder |
| `app/Models/StoreMenu.php` | Menú por ubicación | `732268b` | Sí | Store Builder |
| `app/Models/StoreMenuItem.php` | Destinos, submenús y visibilidad | `732268b` | Sí | Store Builder |
| `app/Models/Complaint.php` | Libro de reclamaciones | `732268b` | Indirecto | Store Builder |
| `app/Models/ContactMessage.php` | Mensajes de Contacto | `732268b` | Indirecto | Store Builder |
| `app/Models/Category.php` | Categorías y subcategorías | anterior a `e788ee3` | Sí | Compartido |
| `app/Models/Product.php` | Catálogo y filtros | anterior a `e788ee3` | Sí | Compartido |
| `app/Support/CatalogTemplates.php` | Catálogo oficial y manifiesto | `0655912` | Sí | Store Builder |
| `app/Support/StorefrontNavigation.php` | Menú por defecto y resolución de URL | `0655912` | Sí | Store Builder |
| `app/Support/StorefrontSections.php` | Ocho secciones canónicas y defaults | `732268b` | Sí | Store Builder |
| `app/Support/StorefrontTheme.php` | Metadatos visuales V2 | `732268b` | Sí | Store Builder |
| `resources/views/layouts/app.blade.php` | Shell administrativo responsive | `74eb3a0` | Sí | Compartido |
| `resources/views/settings/design.blade.php` | Diseñador y sus dos pestañas | `1ec726d` | Sí | Store Builder |
| `resources/views/settings/store-experience.blade.php` | Constructor visual | `732268b` | Sí | Store Builder |
| `resources/views/settings/partials/home-builder.blade.php` | Navegación y formularios de secciones | `fddc8c3` | Sí | Store Builder |
| `resources/views/settings/partials/supported-template-selector.blade.php` | Tres plantillas oficiales | `8d86811` | Sí | Store Builder |
| `resources/views/settings/partials/home-section-fields.blade.php` | Campos por componente | `732268b` | Sí | Store Builder |
| `resources/views/settings/partials/home-products-picker.blade.php` | Selección manual de productos | `732268b` | Sí | Store Builder |
| `resources/views/settings/partials/store-navigation-builder.blade.php` | Administrador de encabezado y menú | `732268b` | Indirecto | Store Builder |
| `resources/views/settings/partials/store-menu-item.blade.php` | Editor de ítems y submenús | `732268b` | Indirecto | Store Builder |
| `resources/views/settings/partials/institutional-pages.blade.php` | Nosotros y Contacto | `732268b` | Indirecto | Store Builder |
| `resources/views/components/public-store-runtime.blade.php` | Compatibilidad pública heredada | `732268b` | Sí | Store Builder |
| `resources/views/components/storefront-home-sections.blade.php` | Render canónico de Inicio | `732268b` | Sí | Store Builder |
| `resources/views/components/public-popup.blade.php` | Render y frecuencia del pop-up | `732268b` | Sí | Store Builder |
| `resources/views/components/computienda/catalog-filters.blade.php` | Filtros avanzados | `732268b` | Sí | Store Builder |
| `resources/views/components/storefront/{header,footer,theme-styles}.blade.php` | Componentes V2 compartidos | `732268b` | Sí | Store Builder |
| `resources/views/layouts/storefront.blade.php` | Layout V2 compartido | `732268b` | Sí | Store Builder |
| `resources/views/public/templates/computienda.blade.php` | Plantilla oficial CompuTienda | `6c41587` | Sí | Store Builder |
| `resources/views/public/templates/ecommerce.blade.php` | Plantilla oficial Ecommerce | `732268b` | Sí | Store Builder |
| `resources/views/public/templates/direct.blade.php` | Plantilla oficial Direct | `3c9aa86` | Sí | Store Builder |
| `resources/views/public/templates/tecnologia.blade.php` | Compatibilidad heredada requerida por pruebas | `37422e9` | Sí | Compatibilidad |
| `resources/views/public/templates/lavanderia.blade.php` | Compatibilidad heredada requerida por pruebas | `c38f991` | Sí | Compatibilidad |
| `resources/views/public/templates/{boutique,ella,farma,flash,fresh,licoreria,menu,nordic,porto,urban}.blade.php` | Vistas heredadas reconocidas | `732268b` | Sí | Compatibilidad |
| `resources/views/public/storefront/{home,shop,product,page,contact,blog,blog-post}.blade.php` | Inicio, Tienda y páginas V2 | `732268b` | Sí | Store Builder |
| `resources/views/public/storefront/templates/{classic,computienda,direct,ecommerce}.blade.php` | Familias visuales V2 | `732268b` | Sí | Store Builder |
| `resources/views/public/{catalog,contact,page,complaints}.blade.php` y `public/partials/footer.blade.php` | Fallbacks públicos | `732268b` | Indirecto | Compartido |
| `routes/web.php` | Rutas administrativas y públicas | `732268b` | Sí | Mixto |
| `database/migrations/2026_07_22_000000_create_project_templates_table.php` | Plantillas por proyecto | `732268b` | Sí | Store Builder |
| `database/migrations/2026_07_23_000000_create_store_experience_tables.php` | Secciones, páginas y pop-ups | `732268b` | Sí | Store Builder |
| `database/migrations/2026_07_23_000001_create_store_communications_tables.php` | Contacto y reclamaciones | `732268b` | Sí | Store Builder |
| `database/migrations/2026_07_23_000002_add_draft_fields_to_store_sections.php` | Campos de borrador | `732268b` | Sí | Store Builder |
| `database/migrations/2026_07_23_000003_initialize_home_builder_sections.php` | Inicialización canónica | `732268b` | Sí | Store Builder |
| `database/migrations/2026_07_23_000004_disable_untouched_builder_defaults.php` | Defaults heredados | `732268b` | Sí | Store Builder |
| `database/migrations/2026_07_24_000000_create_store_menus_tables.php` | Menús y destinos | `732268b` | Sí | Store Builder |
| `tests/Unit/CatalogTemplatesManifestTest.php` | Catálogo oficial | `4d5591c` | Sí | Prueba |
| `tests/Unit/PublicTemplateRuntimeTest.php` | Runtime y marcadores | anterior al Paso 6 | Sí | Prueba |
| `tests/Feature/GlobalTemplateSettingsTest.php` | Persistencia global | anterior al Paso 6 | Sí | Prueba |
| `tests/Feature/StorefrontHomepageBuilderTest.php` | Constructor y publicación | anterior al Paso 6 | Sí | Prueba |
| `tests/Feature/StorefrontStructureV2Test.php` | Navegación, aplicación y páginas | `4d5591c` | Sí | Prueba |
| `tests/Feature/AdminResponsiveLayoutTest.php` | Contrato responsive del shell | `8b22f36` | Sí | Prueba |
| `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `vite.config.js`, `resources/css/app.css`, `resources/js/app.js` | Instalación y build reproducibles | anteriores al Paso 6 | Sí | Compartido |

No queda ningún archivo indispensable del Store Builder sin seguimiento en el HEAD candidato `c38f991`.

## Archivos mixtos

| Archivo | Store Builder versionado | Cambios locales ajenos que permanecen | Separación | Riesgo |
|---|---|---|---|---|
| `SettingsController.php` | Guardado y aplicación | Ninguno pendiente | Ya estabilizado sin reescritura | Medio por amplitud histórica |
| `PublicController.php` | Rutas públicas y tema | Ninguno pendiente | Ya estabilizado sin reescritura | Alto por múltiples flujos |
| `CatalogTemplates.php` | Catálogo oficial | Ninguno pendiente | Ya estabilizado | Medio |
| `design.blade.php` | Selector/constructor | Ninguno pendiente | Ya estabilizado | Medio |
| `layouts/app.blade.php` | Drawer responsive | Hay cambios locales de CRM, CSRF, navegación y confirmación global | No separar sin aprobación; implicaría seleccionar hunks funcionales, no reescribir historial | Alto |
| `direct.blade.php` | Normalización y runtime | Ninguno pendiente | Ya estabilizado | Medio |
| `ecommerce.blade.php` | Runtime y contrato público | Permanece un cambio local de SVG de WhatsApp | Separable, pero ajeno al Paso 6 | Bajo |
| `computienda.blade.php` | Marcadores y catálogo avanzado | Ninguno pendiente | Ya estabilizado | Alto por tamaño |
| `routes/web.php` | Rutas exactas del Store Builder | Permanecen rutas locales de CRM, POS, bots, Woo y revendedores | Se versionaron únicamente los hunks del Store Builder | Alto si se agrega el archivo completo |

No se usó `rebase`, `reset --hard`, `amend`, `filter-branch` ni reescritura de historia.

## Clasificación de entradas sin seguimiento

Estado posterior a los commits de código: 151 entradas expandidas sin seguimiento. Ninguna pertenece al Store Builder necesario.

| Ruta o grupo | Cantidad | Clasificación | ¿Versionar ahora? | Motivo / acción futura |
|---|---:|---|---|---|
| `app/` (API, Copilot, bots, CRM, reseller, flows y soportes) | 32 | B: código ajeno | No | Auditar por módulo en una rama propia |
| `resources/` (CRM, bot builder, reseller, propuestas y flow editor) | 16 | B: código ajeno | No | Versionar junto con sus controladores y pruebas |
| `database/` (migraciones/seeds comerciales) | 13 | B: código ajeno | No | Requiere revisión de datos separada |
| `templates-base/` | 44 | B: material ajeno | No | No participa en runtime ni suites del Store Builder |
| `config/` | 3 | B: configuración ajena | No | Revisar con los módulos que la consumen |
| `routes/api.php`, `routes/bixo.php` | 2 | B: rutas ajenas | No | Integrar solo con sus módulos completos |
| `tests/Feature/QrSettingsTest.php`, `tests/Unit/RouterComercialTest.php` | 2 | B: pruebas ajenas | No | Mantener con QR/Comercial |
| `docs/` preexistentes no rastreados | 8 | B: documentación ajena | No automático | Revisar contenido y vigencia |
| `public/`, `img/`, `logo/` no ignorados | 4 | C: assets/locales | No automático | Definir propietario y política de assets |
| `whatsbot/` y `whatsbot-index.js` | 6 | B/D | No | Puede contener configuración operativa; revisar aparte |
| `deploy.py`, `upload-vps.sh` | 2 | D: potencialmente sensible | No | Revisar credenciales/hosts antes de cualquier decisión |
| Seeders, scripts `fix_*`, `patch_*`, importadores y listados raíz | 13 | D: potencialmente sensible | No | Pueden contener datos, rutas o acciones destructivas |
| HTML/cotizaciones/documentación raíz | 9 | C/D | No | Artefactos locales; clasificar por propietario |
| Otros archivos raíz (`DESIGN_SYSTEM.md`, `NAVIGATION_ORBITAL.md`, etc.) | 5 | B/C | No | Evaluación editorial separada |

No se borró ninguna entrada y no se ejecutó `git clean`.

## Reglas `.gitignore`

Commit `b3904cd` agregó únicamente artefactos inequívocamente locales/generados: `.mcp.json`, `composer.phar`, resultados de navegador, uploads locales y archivos QA/PNG/HTML/TXT/SQL/PHP de primer nivel en `storage`. Se verificó que cero archivos bajo `app/`, `resources/`, `routes/`, `config/`, `database/migrations/` y `tests/` quedaran ocultos por estas reglas.

Tras instalar en el worktree limpio, `vendor/`, `node_modules/`, `public/build/`, cachés Blade/Composer y sesiones temporales quedaron correctamente ignorados; ningún archivo fuente rastreado cambió.

## Riesgos abiertos

1. El árbol original sigue sucio por trabajo ajeno; debe conservarse y revisarse por módulos.
2. `layouts/app.blade.php` y `routes/web.php` mantienen cambios locales mixtos no incluidos.
3. Chrome reproduce un error JavaScript del modal global en el HEAD limpio: `open(opts)` recibe `undefined` desde `x-init` y lee `opts.title`.
4. La compilación Vite advierte que el `@import` de fuentes aparece después de directivas Tailwind.
5. Composer advierte que el PHAR local es una build de desarrollo con más de 60 días.
6. Corregir el error JavaScript es un cambio funcional y requiere autorización posterior.
