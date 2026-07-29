# Resultados de pruebas del Store Builder

Fecha de ejecución: 2026-07-28. Cada archivo se ejecutó por separado con `--stop-on-failure`, usando PHP 8.2.12 de XAMPP. Las pruebas usan SQLite en memoria conforme a `phpunit.xml`; no escribieron en la base MySQL inventariada.

## Resumen

| Archivo | Inició | Terminó | Duración PHPUnit | Duración de pared | Resultado |
|---|---|---|---:|---:|---|
| `CatalogTemplatesManifestTest.php` | Sí | Sí | 4.59 s | 9.10 s | 1 aprobada, 3 aserciones |
| `PublicTemplateRuntimeTest.php` | Sí | Sí | 1.07 s | 2.08 s | 4 aprobadas, 1 fallida, 144 aserciones |
| `GlobalTemplateSettingsTest.php` | Sí | Sí | 12.89 s | 13.64 s | 2 aprobadas, 197 aserciones |
| `StorefrontHomepageBuilderTest.php` | Sí | Sí | 18.52 s | 66.52 s | 4 aprobadas, 3 fallidas, 1 no ejecutada, 27 aserciones |
| `StorefrontStructureV2Test.php` | Sí | Sí | 23.58 s | 24.52 s | 6 aprobadas, 1 fallida, 5 no ejecutadas, 66 aserciones |

“No ejecutada” significa que el archivo tenía más pruebas, pero `--stop-on-failure` detuvo la continuación después del fallo. Ningún comando quedó bloqueado permanentemente.

## 1. Manifest de plantillas

Comando exacto:

```powershell
C:\xampp\php\php.exe artisan test tests/Unit/CatalogTemplatesManifestTest.php --stop-on-failure
```

- Inició y terminó: sí.
- Tests: 1.
- Resultado: aprobado; 3 aserciones.
- Último test: `technology template exposes a component manifest`.
- Error: ninguno.
- Código de salida: 0.

## 2. Runtime de plantillas públicas

Comando exacto:

```powershell
C:\xampp\php\php.exe artisan test tests/Unit/PublicTemplateRuntimeTest.php --stop-on-failure
```

- Inició y terminó: sí.
- Tests: 5; 4 aprobados y 1 fallido.
- Último test: `native section markers only use canonical builder components`.
- Código de salida: 1.

Error completo principal:

```text
computienda.blade.php: promotions
Failed asserting that an array contains 'promotions'.
at tests/Unit/PublicTemplateRuntimeTest.php:94
```

Causa exacta: `computienda.blade.php` declara `data-store-native-section="promotions"`, pero `promotions` no pertenece a la lista canónica admitida por el constructor. El componente canónico equivalente es `announcements`.

## 3. Ajustes globales de plantilla

Comando exacto:

```powershell
C:\xampp\php\php.exe artisan test tests/Feature/GlobalTemplateSettingsTest.php --stop-on-failure
```

- Inició y terminó: sí.
- Tests: 2.
- Resultado: aprobado; 197 aserciones.
- Último test: `saving one tab does not reset boolean settings from another tab`.
- Error: ninguno.
- Código de salida: 0.

## 4. Constructor de página de inicio

Comando exacto:

```powershell
C:\xampp\php\php.exe artisan test tests/Feature/StorefrontHomepageBuilderTest.php --stop-on-failure
```

- Inició y terminó: sí.
- Tests descubiertos: 8; 4 aprobados, 3 fallidos y 1 no ejecutado después de los fallos.
- Código de salida: 2.
- Duración: 18.52 s de PHPUnit y 66.52 s de pared.
- Último test ejecutado: `design navigation has only templates and visual builder tabs`.

Errores completos principales:

```text
ViewException: Undefined variable $homeSections
View: resources/views/settings/partials/home-builder.blade.php
Compiled view: storage/framework/views/e1a0...php:3
Test: admin builder and public store render without errors
```

Causa exacta: el test renderiza el partial con los datos que entrega `SettingsController::design()`, pero ese conjunto no incluye `$homeSections`; el partial depende de variables que normalmente prepara su vista contenedora.

```text
ViewException: Undefined array key "hero_align"
View: resources/views/public/templates/direct.blade.php
Compiled view: line 48
Test: every system template renders with the builder hero without orphaned markup
```

Causa exacta: la condición usa un valor por defecto con `$settings['hero_align'] ?? 'left'`, pero una rama posterior accede directamente a `$settings['hero_align']`. Si la clave no existe, el acceso vuelve a ser inseguro.

```text
Failed asserting that the rendered HTML contains "?s=constructor".
at tests/Feature/StorefrontHomepageBuilderTest.php:187
Test: design navigation has only templates and visual builder tabs
```

Causa exacta: la navegación renderizada todavía contiene la estructura anterior de pestañas y no expone el enlace esperado del constructor visual.

El tiempo de pared alto fue producido por migraciones/renderizado de HTML y la salida extensa de excepciones. El proceso terminó por sí mismo; no se detuvo ni se ejecutó `--filter` porque no hubo bloqueo permanente.

## 5. Estructura Storefront V2

Comando exacto:

```powershell
C:\xampp\php\php.exe artisan test tests/Feature/StorefrontStructureV2Test.php --stop-on-failure
```

- Inició y terminó: sí.
- Tests descubiertos: 12; 6 aprobados, 1 fallido y 5 no ejecutados por `--stop-on-failure`.
- Último test ejecutado: `each catalog template resolves to a distinct supported v2 theme`.
- Código de salida: 1.

Error completo principal:

```text
Expected response status code [200] but received 500.
ViewException: Undefined array key "hero_align"
View: resources/views/public/templates/direct.blade.php
Compiled view: line 48
at tests/Feature/StorefrontStructureV2Test.php:157
```

Causa exacta: es el mismo acceso inseguro a `$settings['hero_align']` de la plantilla Direct. El error de vista convierte la respuesta esperada 200 en 500.

## Procesos y revisión de bloqueo

- Todos los comandos terminaron.
- Al finalizar se verificó que no quedaran procesos `phpunit` ni `artisan test` activos: resultado vacío.
- Como ningún comando quedó bloqueado, no se aplicó el protocolo de terminar proceso y ejecutar un método con `--filter`.
- Se inspeccionaron los cinco archivos: emplean `RefreshDatabase`, factories y SQLite en memoria. No se detectaron seeders, llamadas HTTP externas, `sleep()` ni procesos externos.
- Cola, correo, cache y sesiones están configurados para ejecución local/in-memory durante las pruebas.

## Validación de sintaxis solicitada

```text
No syntax errors detected in app/Http/Controllers/SettingsController.php
No syntax errors detected in app/Http/Controllers/StoreExperienceController.php
No syntax errors detected in app/Http/Controllers/StoreNavigationController.php
No syntax errors detected in app/Http/Controllers/PublicController.php
No syntax errors detected in app/Http/Controllers/StorePageController.php
```

Conclusión: la sintaxis de los controladores es válida, pero la línea base funcional está en rojo por fallos reproducibles de vistas, contrato de datos y navegación.

## Estabilización mínima — 2026-07-28

Se corrigieron exclusivamente los cuatro contratos autorizados: marcadores nativos, `$homeSections`, `hero_align` y navegación principal del Diseñador. Los resultados históricos anteriores se conservan sin modificación.

### Resultado individual posterior

| Suite | Resultado |
|---|---|
| `CatalogTemplatesManifestTest.php` | PASS — 1 test, 3 aserciones |
| `PublicTemplateRuntimeTest.php` | PASS — 5 tests, 201 aserciones |
| `GlobalTemplateSettingsTest.php` | PASS — 2 tests, 197 aserciones |
| `StorefrontHomepageBuilderTest.php` | PASS — 9 tests, 109 aserciones |
| `StorefrontStructureV2Test.php` | FAIL — 11 tests pasan y 2 fallan al ejecutar todos los métodos |

La ejecución individual requerida con `--stop-on-failure` de Structure V2 llegó a 8 aprobadas, 1 fallida y 4 pendientes. Los cuatro métodos pendientes se ejecutaron después con `--filter`: tres aprobaron y uno reveló el segundo fallo descrito abajo.

### Fallos nuevos fuera del alcance autorizado

```text
test_template_selector_updates_the_public_v2_theme_immediately
Failed asserting that null is identical to 'ecommerce'.
La respuesta no contiene theme.key ni public_url.
at tests/Feature/StorefrontStructureV2Test.php:204
```

```text
test_template_admin_confirms_the_applied_v2_theme_and_offers_fresh_preview
Expected rendered HTML to contain: Ella — Moda Minimalista aplicada
at tests/Feature/StorefrontStructureV2Test.php:290
```

### Ejecución conjunta

El comando conjunto ejecutó los 30 métodos: **28 aprobaron y 2 fallaron**. Tras imprimir todos los resultados, PHPUnit quedó formateando el diff HTML extenso del segundo fallo; se detuvo solamente ese proceso conforme al protocolo. El log se conservó temporalmente en:

```text
C:\Users\luich\AppData\Local\Temp\store-builder-five-suites-20260728.log
```

No quedaron procesos de prueba activos. Estado posterior: **ROJO**.

Validaciones adicionales correctas: 561 rutas, `view:clear`, `view:cache` y `php -l` de todos los archivos modificados. El detalle completo está en `docs/store-builder-stabilization-report.md`.
