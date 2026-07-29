# Informe de estabilización mínima del Store Builder

Fecha: 2026-07-28. Alcance autorizado: Paso 3, corrección exclusiva de cuatro fallos reproducibles de la línea base. No se desplegó, no se modificó `.env`, no se ejecutaron migraciones sobre la base real, no se transformaron datos y no se eliminaron archivos del repositorio.

## Rama y punto de partida

- Rama creada: `stabilize/store-builder-baseline`.
- Rama de origen: `main`.
- Commit base: `e788ee3 feat: mejoras UI mesas, mapa operativo, vistas comercial y plantillas`.
- El árbol de trabajo ya contenía numerosos cambios modificados y sin seguimiento. Se preservaron.

## Commits creados

| Commit | Descripción |
|---|---|
| `6c41587` | `fix(storefront): use canonical announcements component` |
| `250b4d3` | `fix(builder): provide home sections contract` |
| `3c9aa86` | `fix(direct): normalize hero alignment safely` |
| `fddc8c3` | `fix(designer): expose templates and visual builder navigation` |
| Documental (este commit) | `docs(store-builder): record stabilization results` |

Antes de cada commit se ejecutaron `git diff --cached --stat` y `git diff --cached`. Solo se prepararon rutas vinculadas con Store Builder mediante `git add -- <archivos>`; no se usó `git add .` ni `git add -A`.

Nota de trazabilidad: CompuTienda, los partials y los archivos de prueba no estaban seguidos por Git antes de esta rama, por lo que sus commits incorporan el archivo Store Builder completo. `SettingsController.php`, `design.blade.php` y `direct.blade.php` ya estaban modificados frente al commit base; Git registra también esas modificaciones previas contenidas en los mismos archivos. No se añadieron imágenes, `storage`, `vendor`, `node_modules`, logs ni archivos ajenos.

## Respaldo previo

Ruta: `C:\Users\luich\AppData\Local\Temp\store-builder-before-stabilization-20260728-01`.

No se copiaron `.env`, credenciales, `vendor`, `node_modules`, logs ni datos. Cada ruta se conservó con su jerarquía relativa.

| Archivo | SHA256 original | SHA256 final | Motivo |
|---|---|---|---|
| `resources/views/public/templates/computienda.blade.php` | `DCB18393AC2BC611EB09C01D9E3A12FF1572DBC36D68210134603F73155D5272` | `0C7C6D2A4403688C95C37C9F34A0EB752F43FBE8E8EFBBCB12E5857BBC0A2235` | Marcadores canónicos. |
| `app/Http/Controllers/SettingsController.php` | `7FF1610DD79C0C6B1BFA6E5F4ECA9BC273C1F54585DEC78CAF43E2A0CBB96F18` | `A10B164AB0229CD0FAA5A8715A32D15630D9E239191B8FFC8000B7525BEFB10A` | Contrato oficial de secciones. |
| `resources/views/settings/design.blade.php` | `60976E56EEEB57BE4344D037D30CCCD715887C13572226CE16F41726AAAF75EF` | `20F0C0158221CBBE12944A8C33CBE52EC281A440D057368E432689F4146BBA28` | Navegación principal y scroll interno. |
| `resources/views/settings/partials/home-builder.blade.php` | `601D15BE0AC180FDA04DB356A9372910DEAE91CA4987ED5C7B69FB5FAD0EB6A0` | `246A841E4ACF7B0BC58E81E13AA1518A707E324464A669318AAA13B299336486` | Estado vacío controlado. |
| `resources/views/public/templates/direct.blade.php` | `E72ABC62153B594B5E5980715DAE898D774DD11E40916EF7652B8F256CE5F784` | `39E71AB0171A21377094F7DD6AF1EAC831DD27D4D4B2D7C0931F9276D040070E` | Normalización de `hero_align`. |
| `tests/Unit/PublicTemplateRuntimeTest.php` | `CB50A9B8F5B9F03B078841E672703C68C34E9FB1E27BED2C681329200BC37D39` | `CB50A9B8F5B9F03B078841E672703C68C34E9FB1E27BED2C681329200BC37D39` | Prueba ya existente que respalda el marcador. |
| `tests/Feature/StorefrontHomepageBuilderTest.php` | `AC2D69BA47658FBD8B70E8D7D37C1B08445EF12FEB7C293638DB2C11D1E6F104` | `EF00690F8C7F749C3691EC9DB5E53654207A1BAEA4B151A1EFFD442DCCACA067` | Contrato y navegación. |
| `tests/Feature/StorefrontStructureV2Test.php` | `20670FDC1FECFE8731BB072B4A3A5BE0A2139951CE44588B78AE55AC13A5200D` | `E6B83FEA9D49127E7DEE5EFF88456D6FC397C7ABAB33C1E494A79A5E370F2146` | Casos de alineación Direct. |
| `docs/store-builder-test-results.md` | `71C2D0968D1C13BB2C61425C621605552F1E7050CF05B3CB50F567C01EAA634D` | `6BD0F7098988024C6B1794B13E676B7360FAE2576036614A13C75F1E05F28171` | Anexo histórico. |
| `docs/store-builder-phase-1-readiness.md` | `C8150C65DA445FF224C5E279D7BBE24A604548D0003FDC66C017C0ABA512D3DE` | `F919B7CF5B47130D2533B3E969C6F57D660393F87D4ACB6B2B9AC63B571E4DEB` | Nuevo dictamen. |

## Correcciones aplicadas

### 1. Marcadores canónicos de CompuTienda

Causa: `data-store-native-section="promotions"` no pertenecía a `StorefrontSections::COMPONENTS`. Al avanzar la prueba aparecieron otros marcadores del mismo contrato que el primer fallo ocultaba.

Corrección:

- `promotions` → `announcements`.
- `categories` → `featured_categories`.
- `flash_sale` → `daily_offer`.
- `discount_products` → `discounts`.
- Se retiró el atributo de `catalog` y `custom_page`, porque no son componentes administrados de Inicio.
- Las claves internas de orden y diseño de CompuTienda no cambiaron.

Prueba: `PublicTemplateRuntimeTest`. Antes: 4 aprobadas y 1 fallida. Después: **5 aprobadas, 201 aserciones**.

### 2. Contrato de `$homeSections`

Causa: el partial dependía accidentalmente de variables preparadas por otra vista; `SettingsController::design()` no las entregaba.

Corrección:

- `SettingsController::design()` llama al mecanismo oficial `StorefrontSections::ensure($project)`.
- Carga una sola vez `store_sections`, extrae `homeSections` y entrega nombres, categorías y productos aislados por proyecto.
- El partial mantiene obligatorio el contrato; una colección válida pero vacía muestra un estado explícito, sin esconder una variable ausente mediante `?? collect()`.

Prueba: `StorefrontHomepageBuilderTest::test_admin_builder_and_public_store_render_without_errors` y prueba de colección vacía. Esos casos pasan y confirman ocho secciones del proyecto activo.

### 3. `hero_align` en Direct

Causa: la condición usaba `??`, pero la rama verdadera volvía a acceder al índice inexistente.

Corrección: la plantilla lee el índice una sola vez, normaliza texto y acepta exclusivamente `left`, `center` y `right`; cualquier ausencia, `null`, vacío o valor desconocido usa `left`.

Prueba: seis escenarios (`null`, vacío, los tres valores válidos e inválido) responden HTTP 200 y renderizan la vista Direct.

### 4. Navegación del Diseñador

Causa: siete pestañas antiguas competían como navegación principal y no existía `?s=constructor`.

Corrección:

- Dos pestañas principales: Plantillas y Constructor visual.
- `?s=plantilla` y el alias `?s=templates` abren Plantillas.
- `?s=constructor` y valores desconocidos abren Constructor.
- `aria-current`, proyecto en query string y estado activo.
- Marca, Portada, Catálogo, Checkout, Sistema e Inicio permanecen como formularios únicos dentro del Constructor.
- La navegación secundaria usa anclas y desplaza solo `data-design-scroll-container`; se eliminó el reinicio forzado al inicio.

Prueba: `StorefrontHomepageBuilderTest` queda en **9 aprobadas, 109 aserciones**.

## Resultados finales de pruebas

| Suite | Resultado individual final |
|---|---|
| `CatalogTemplatesManifestTest.php` | 1 aprobada, 3 aserciones |
| `PublicTemplateRuntimeTest.php` | 5 aprobadas, 201 aserciones |
| `GlobalTemplateSettingsTest.php` | 2 aprobadas, 197 aserciones |
| `StorefrontHomepageBuilderTest.php` | 9 aprobadas, 109 aserciones |
| `StorefrontStructureV2Test.php` | 11 aprobadas y 2 fallidas al ejecutar todos sus métodos |

Ejecución conjunta: **30 tests observados, 28 aprobados y 2 fallidos**. PHPUnit ejecutó todos los métodos; se detuvo únicamente el proceso que quedó generando el diff HTML completo después de imprimir la lista final. No quedaron procesos `phpunit` ni `artisan test` activos.

## Problemas fuera de alcance encontrados

1. `test_template_selector_updates_the_public_v2_theme_immediately`: `applyTemplate()` devuelve `ok`, `template` y `preserved`, pero no `theme.key` ni `public_url`. La plantilla sí se guarda; falla el contrato JSON esperado por la prueba.
2. `test_template_admin_confirms_the_applied_v2_theme_and_offers_fresh_preview`: la pantalla no muestra “Ella — Moda Minimalista aplicada” ni “Ver cambio en la tienda”.
3. El diff HTML de un fallo del Diseñador es tan grande que la salida de PHPUnit puede tardar varios minutos en finalizar aunque el test interno termine en segundos.
4. En 375 px el pop-up configurado ocupa casi toda la altura. Es utilizable y cerrable, pero merece revisión visual posterior; no se rediseñó en este paso.
5. Persisten los 14 conflictos de configuración, 17 plantillas sin `storeView` y tres vistas productivas inexistentes ya documentadas. No se tocaron.

## Validaciones complementarias

- `php -l`: ocho archivos de PHP/Blade/pruebas modificados sin errores.
- `php artisan route:list`: 561 rutas, salida correcta.
- `php artisan view:clear`: correcto.
- `php artisan view:cache`: correcto; todas las vistas compilaron.
- Prueba local headless de CompuTienda a 375×900, 768×1024 y 1440×1100.
- Sin HTTP 500, sin Hero nativo duplicado, con `announcements` y cero marcadores no canónicos.
- Inicio, Plantillas, Constructor, secciones, Direct sin `hero_align`, CompuTienda y rutas de guardado fueron cubiertos por pruebas Feature.
- Capturas temporales: `C:\Users\luich\AppData\Local\Temp\store-builder-responsive-20260728`.
- El servidor local y MySQL iniciados para smoke tests quedaron nuevamente detenidos.

## Estado final y recomendación

Estado: **ROJO**.

Las cuatro correcciones autorizadas están verdes, pero la condición solicitada era que las cinco suites completas terminaran correctamente. Structure V2 conserva dos fallos fuera del alcance aprobado.

Recomendación: **NO APTO para iniciar la siguiente etapa**. El siguiente paso debe ser una autorización pequeña y separada para alinear el contrato JSON de `applyTemplate()` y la confirmación visual posterior a aplicar una plantilla; después deben repetirse las cinco suites.
