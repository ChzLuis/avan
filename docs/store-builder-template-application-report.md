# Informe del Paso 4 — Aplicación de plantillas

Fecha: 2026-07-28. Rama: `stabilize/store-builder-baseline`.

## Alcance y decisión de producto

El catálogo oficialmente seleccionable queda limitado a tres plantillas:

| Clave | Nombre | Descripción | Vista |
|---|---|---|---|
| `ecommerce` | Ecommerce | Tienda online completa | `public.templates.ecommerce` |
| `direct` | Catálogo Directo | Catálogo simple para ventas y cotizaciones | `public.templates.direct` |
| `computienda` | CompuTienda | Tienda especializada en tecnología | `public.templates.computienda` |

`CatalogTemplates::SUPPORTED_KEYS`, `supportedKeys()`, `supported()` y `supportedTheme()` son la única fuente administrativa de este catálogo. La definición incluye clave, nombre, descripción corta, vista, estado soportado, representación previa y capacidades.

## Compatibilidad heredada

- Las demás entradas permanecen en `CatalogTemplates::all()` y en `PublicController::PRODUCTION_TEMPLATE_VIEWS`.
- No se eliminaron archivos Blade, settings ni datos históricos.
- Una tienda heredada no cambia automáticamente y muestra la advertencia: “Esta tienda utiliza una plantilla heredada que ya no recibe nuevas funciones.”
- El selector solo ofrece las tres plantillas oficiales.
- Si una vista heredada registrada no existe, el frontend usa el fallback actual y registra una advertencia estructurada con proyecto, clave y vista; no expone una excepción interna.

## Validación del servidor

`SettingsController::applyTemplate()` normaliza la entrada y la compara estrictamente con el catálogo oficial antes de escribir. `ella`, `editorial`, `luxe`, `bistro`, claves inexistentes y cadenas vacías devuelven HTTP 422 con `ok=false`.

Ante un rechazo no se modifica:

- `catalog_template`;
- el estado de `ProjectTemplate`;
- los defaults del proyecto;
- otro proyecto.

La aplicación válida se ejecuta dentro de una transacción, confirma la clave efectivamente persistida y conserva los ajustes globales existentes.

## Contrato JSON

La respuesta conserva `ok`, `template` y `preserved`, y agrega:

```json
{
  "ok": true,
  "template": "ecommerce",
  "preserved": 12,
  "theme": {
    "key": "ecommerce",
    "name": "Ecommerce",
    "description": "Tienda online completa",
    "view": "public.templates.ecommerce",
    "preview": null,
    "supported": true,
    "capabilities": ["catalog", "filters", "cart", "checkout", "responsive"]
  },
  "public_url": "https://dominio-o-ruta-publica"
}
```

`theme` proviene del catálogo oficial. `theme.key` usa la clave persistida. `public_url` usa `StorefrontNavigation::publicUrl()`: dominio personalizado HTTPS cuando existe o la ruta pública con slug.

## Confirmación visual

El selector muestra exactamente tres tarjetas y utiliza los datos del servidor para construir la confirmación:

`{theme.name} — {theme.description} aplicada`

La acción “Ver cambio en la tienda” utiliza `public_url` y abre con `rel="noopener noreferrer"`. El flujo conserva CSRF, usa `fetch`, evita doble envío, muestra carga, sustituye la confirmación anterior, expone éxito con `role="status"`/`aria-live="polite"` y error con `role="alert"`.

La revisión UI se limitó a claridad de estados, tamaño táctil mínimo de 44 px y accesibilidad; no se rediseñó el Constructor.

## Archivos modificados

- `app/Support/CatalogTemplates.php`
- `app/Support/StorefrontNavigation.php`
- `app/Http/Controllers/SettingsController.php`
- `app/Http/Controllers/PublicController.php`
- `resources/views/settings/design.blade.php`
- `resources/views/settings/partials/supported-template-selector.blade.php`
- `tests/Unit/CatalogTemplatesManifestTest.php`
- `tests/Feature/GlobalTemplateSettingsTest.php`
- `tests/Feature/StorefrontStructureV2Test.php`
- documentación de Store Builder.

`CatalogTemplates.php` y `PublicController.php` ya contenían cambios amplios sin commit al comenzar el Paso 4. Se preservaron y quedaron incluidos al preparar esos archivos completos. No se usó `git add .` ni `git add -A`.

## Commits

| Commit | Descripción |
|---|---|
| `0655912` | `refactor(templates): define three supported storefront templates` |
| `7a7e7c9` | `fix(templates): return applied theme and public url` |
| `8d86811` | `fix(designer): confirm supported template application` |
| `4d5591c` | `test(templates): enforce supported template catalog` |

## Pruebas

- Test pendiente de respuesta: 1 aprobado, 48 aserciones.
- Test pendiente de confirmación: 1 aprobado, 13 aserciones.
- `StorefrontStructureV2Test`: 17 aprobados, 304 aserciones.
- `CatalogTemplatesManifestTest`: 4 aprobados, 26 aserciones.
- `PublicTemplateRuntimeTest`: 5 aprobados, 201 aserciones.
- `GlobalTemplateSettingsTest`: 2 aprobados, 35 aserciones.
- `StorefrontHomepageBuilderTest`: 9 aprobados, 109 aserciones.
- Ejecución conjunta: **37 aprobados, 675 aserciones, 0 fallos**.

Validaciones adicionales:

- `php -l` correcto en nueve archivos.
- `node --check` correcto sobre una copia temporal de la lógica inline.
- `php artisan route:list`: 561 rutas.
- `php artisan view:clear` y `view:cache`: correctos.
- No se ejecutó `npm build`.

## Revisión manual local

- Frontend público de solo lectura a 375×900, 768×1024 y 1440×1100: sin HTTP 500.
- Selector real generado con SQLite temporal: exactamente tres tarjetas y advertencia heredada.
- A 768 y 1440 px el selector es legible y se adapta a una y tres columnas.
- A 375 px el shell administrativo global mantiene abierta la barra lateral y recorta el contenido principal. El selector usa una columna, pero queda limitado por ese defecto preexistente del layout general.
- Aplicación secuencial, persistencia, rechazo heredado, URL pública e aislamiento se comprobaron mediante pruebas Feature para evitar mutar proyectos reales.
- Capturas: `C:\Users\luich\AppData\Local\Temp\store-builder-step4-selector-responsive-20260728` y `C:\Users\luich\AppData\Local\Temp\store-builder-step4-responsive-20260728`.
- Los servicios locales temporales quedaron detenidos.

## Fuera de alcance

- Corregir la barra lateral global a 375 px.
- Corregir el desbordamiento del menú público de CompuTienda a 375 px.
- Retirar físicamente plantillas heredadas.
- Restringir o rediseñar la administración histórica de `ProjectTemplate` fuera del selector oficial.
- Unificar configuraciones, retirar el runtime JavaScript o iniciar la Fase 1.

## Estado final

**VERDE para el contrato del Paso 4:** las cinco suites requeridas terminan sin fallos y los dos bloqueantes originales están resueltos.

Recomendación: antes de la Fase 1, autorizar por separado una corrección responsive del shell administrativo a 375 px. No desplegar todavía.
