# Línea base técnica del Store Builder

Fecha: 2026-07-28. Alcance: comandos y consultas de solo lectura. No se modificaron controladores, vistas, modelos, rutas, migraciones ni datos.

## Estado del repositorio

Comandos ejecutados:

```powershell
git status
git branch --show-current
git log -1 --oneline
git status --porcelain=v1 --untracked-files=all
```

- Rama actual: `main`.
- Commit base: `e788ee3 feat: mejoras UI mesas, mapa operativo, vistas comercial y plantillas`.
- La rama está un commit por delante de `origin/main`.
- Archivos modificados sin preparar: **61**.
- Archivos preparados: **0**.
- Entradas nuevas sin seguimiento, expandiendo directorios: **7,884**.
- Total de entradas no limpias: **7,945**.
- Estado: **repositorio no limpio**.

### Archivos modificados

```text
app/Http/Controllers/Catalog/ProductController.php
app/Http/Controllers/ClientController.php
app/Http/Controllers/Comercial/DashboardController.php
app/Http/Controllers/Comunicaciones/AuthController.php
app/Http/Controllers/Comunicaciones/ClientesCrmController.php
app/Http/Controllers/DemoController.php
app/Http/Controllers/OrderController.php
app/Http/Controllers/PosController.php
app/Http/Controllers/ProjectController.php
app/Http/Controllers/ProposalController.php
app/Http/Controllers/PublicController.php
app/Http/Controllers/RifaController.php
app/Http/Controllers/SettingsController.php
app/Http/Controllers/WaBotController.php
app/Http/Middleware/DetectCustomDomain.php
app/Jobs/SendInvoiceToSunat.php
app/Models/BotFlow.php
app/Models/BotSession.php
app/Models/Client.php
app/Models/Combo.php
app/Models/ComboItem.php
app/Models/Order.php
app/Models/Product.php
app/Models/Project.php
app/Models/Proposal.php
app/Support/CatalogTemplates.php
bootstrap/app.php
config/app.php
database/seeders/PermissionsSeeder.php
resources/views/catalog/products/index.blade.php
resources/views/clients/index.blade.php
resources/views/comercial/dashboard.blade.php
resources/views/comercial/layouts/app.blade.php
resources/views/comercial/mesas.blade.php
resources/views/comercial/rifas.blade.php
resources/views/comunicaciones/bandeja.blade.php
resources/views/comunicaciones/layouts/app.blade.php
resources/views/hr/employees.blade.php
resources/views/layouts/app.blade.php
resources/views/orders/index.blade.php
resources/views/pos/index.blade.php
resources/views/proposals/index.blade.php
resources/views/public/catalog.blade.php
resources/views/public/partials/footer.blade.php
resources/views/public/templates/boutique.blade.php
resources/views/public/templates/direct.blade.php
resources/views/public/templates/ecommerce.blade.php
resources/views/public/templates/ella.blade.php
resources/views/public/templates/farma.blade.php
resources/views/public/templates/flash.blade.php
resources/views/public/templates/fresh.blade.php
resources/views/public/templates/licoreria.blade.php
resources/views/public/templates/menu.blade.php
resources/views/public/templates/nordic.blade.php
resources/views/public/templates/porto.blade.php
resources/views/public/templates/urban.blade.php
resources/views/settings/design.blade.php
resources/views/settings/index.blade.php
resources/views/settings/qr.blade.php
routes/web.php
whatsbot/index.js
```

### Archivos y grupos nuevos relevantes al Store Builder

```text
app/Http/Controllers/StoreExperienceController.php
app/Http/Controllers/StoreNavigationController.php
app/Http/Controllers/StorePageController.php
app/Models/ProjectTemplate.php
app/Models/StoreMenu.php
app/Models/StoreMenuItem.php
app/Models/StorePage.php
app/Models/StorePopup.php
app/Models/StoreSection.php
app/Support/StorefrontNavigation.php
app/Support/StorefrontSections.php
app/Support/StorefrontTheme.php
database/migrations/2026_07_22_000000_create_project_templates_table.php
database/migrations/2026_07_23_000000_create_store_experience_tables.php
database/migrations/2026_07_23_000002_add_draft_fields_to_store_sections.php
database/migrations/2026_07_23_000003_initialize_home_builder_sections.php
database/migrations/2026_07_23_000004_disable_untouched_builder_defaults.php
database/migrations/2026_07_24_000000_create_store_menus_tables.php
resources/views/components/public-popup.blade.php
resources/views/components/public-store-runtime.blade.php
resources/views/components/storefront-home-sections.blade.php
resources/views/components/storefront/
resources/views/layouts/storefront.blade.php
resources/views/public/storefront/
resources/views/public/templates/computienda.blade.php
resources/views/public/templates/lavanderia.blade.php
resources/views/public/templates/tecnologia.blade.php
resources/views/settings/partials/
resources/views/settings/store-experience.blade.php
tests/Feature/GlobalTemplateSettingsTest.php
tests/Feature/StorefrontHomepageBuilderTest.php
tests/Feature/StorefrontStructureV2Test.php
tests/Unit/CatalogTemplatesManifestTest.php
tests/Unit/PublicTemplateRuntimeTest.php
```

También existen numerosos archivos nuevos ajenos o tangenciales: controladores API/comercial, scripts, seeders, imágenes, capturas en `storage/`, plantillas base, HTML independientes y código WhatsApp. El listado exacto completo se obtiene con `git status --short --untracked-files=all`.

## Entorno

Comandos ejecutados:

```powershell
C:\xampp\php\php.exe -v
C:\xampp\php\php.exe composer.phar --version
C:\xampp\php\php.exe artisan --version
C:\xampp\php\php.exe artisan about
C:\xampp\php\php.exe artisan route:list
node -v
npm -v
```

`php` y `composer` no están disponibles en `PATH`; se usaron rutas explícitas. `node` y `npm` sí están disponibles.

| Componente | Valor |
|---|---|
| PHP | 8.2.12, CLI ZTS, OPcache 8.2.12 |
| Laravel | 12.54.1 |
| Composer | 2.10-dev, build 2026-04-14; advertencia de build de desarrollo con más de 60 días |
| Node | v22.20.0 |
| npm | 10.9.3 |
| Aplicación | BIXO |
| Entorno normal | `local`, debug activado |
| Zona horaria | America/Lima |
| Rutas | 561 registradas |
| Storage público | enlace existente |

Estado de cachés observado: config no cacheada, eventos cacheados, rutas no cacheadas y vistas cacheadas. Esto es relevante porque los errores Blade apuntan a `storage/framework/views/*.php`.

## Entorno de pruebas

Fuente: `phpunit.xml`. No se imprimieron secretos.

| Ajuste | Valor de testing |
|---|---|
| `APP_ENV` | `testing` |
| `DB_CONNECTION` | `sqlite` |
| `DB_DATABASE` | `:memory:` |
| `CACHE_STORE` | `array` |
| `SESSION_DRIVER` | `array` |
| `QUEUE_CONNECTION` | `sync` |
| `MAIL_MAILER` | `array` |
| `BROADCAST_CONNECTION` | `null` |
| Filesystem | No se sobrescribe; hereda la configuración de la aplicación |
| Bcrypt | 4 rondas |
| Pulse/Telescope/Nightwatch | desactivados |

Suites: `tests/Unit` y `tests/Feature`; bootstrap `vendor/autoload.php`; fuente de cobertura `app/`.

Extensiones relevantes presentes: `PDO`, `pdo_mysql`, `pdo_sqlite`, `curl`, `fileinfo`, `mbstring`, `openssl`. No apareció `sqlite3`, pero `pdo_sqlite` cubre la conexión usada. En Windows no se observó `pcntl`.

### Factores que podían bloquear

- MySQL local estaba detenido y rechazó `127.0.0.1:3306`; se inició solo el proceso local XAMPP para consultas `SELECT`. Las pruebas usan SQLite en memoria.
- Las pruebas Feature usan `RefreshDatabase` y ejecutan migraciones sobre SQLite en memoria por proceso.
- No se detectaron llamadas HTTP, seeders, `sleep()` ni procesos externos en los cinco archivos de prueba.
- Cola síncrona y correo en memoria evitan workers y SMTP externos.
- Vistas y eventos estaban cacheados en el entorno normal.
- `StorefrontHomepageBuilderTest` renderiza HTML muy grande: tiempo de pared 66.5 s frente a 18.5 s internos de PHPUnit. Terminó; no fue un bloqueo permanente.

## Validación PHP

Los cinco comandos `php -l` solicitados terminaron correctamente: `SettingsController`, `StoreExperienceController`, `StoreNavigationController`, `PublicController` y `StorePageController` no tienen errores de sintaxis.

Después de las pruebas se verificaron procesos `php.exe` con `phpunit` o `artisan test`: **no quedó ninguno activo**.
