# Informe de cierre — Fase 1A: contexto canónico del Store Builder

Fecha: 2026-07-29.

## Identificación

- Rama: `refactor/store-builder-canonical-context`.
- Baseline: `baseline/store-builder-green` en `9a296968fa8a9a6e6ab85662e300387b651ee5e3`.
- Tag anotado: `store-builder-baseline-green-2026-07-29`, que resuelve al mismo commit al desreferenciarlo.
- HEAD funcional: `9e51730f3a53dd8b69cf44c3be0d6f0d8e9c804a`.
- HEAD documental: commit `docs(storefront): report phase 1a canonical context`; su hash se registra en la entrega final para evitar una referencia circular dentro del propio commit.

## Resultado funcional

Se crearon `App\Storefront\StorefrontContext` y `App\Storefront\StorefrontContextBuilder`. La primera representa un resultado de solo lectura; la segunda centraliza la carga y resolución para Diseñador, Ecommerce, Direct, CompuTienda, navegación, páginas, pop-up, URLs y catálogo.

Controladores integrados:

- `SettingsController::design()` y `SettingsController::qr()`.
- `PublicController` para las tres plantillas oficiales; las plantillas heredadas conservan su fallback anterior.
- `StorePageController`, con inyección explícita del builder y variables heredadas derivadas del contexto.
- `StoreExperienceController` y `StoreNavigationController` fueron revisados; sus escrituras quedaron deliberadamente intactas.

Vistas adaptadas sin consultas de base de datos:

- `settings/design.blade.php` y `settings/qr.blade.php`.
- `public/templates/ecommerce.blade.php`, `direct.blade.php` y `computienda.blade.php`.
- `components/public-store-runtime.blade.php`.
- `components/storefront-home-sections.blade.php`.
- `components/storefront/header.blade.php`.

`PRODUCTION_TEMPLATE_VIEWS`, los Blade heredados, aliases y el runtime público permanecen disponibles. No se cambiaron rutas, apariencia, textos, orden, carrito, checkout, WhatsApp, pop-up ni comportamiento responsive.

## Archivos funcionales

- Controladores: `PublicController.php`, `SettingsController.php`, `StorePageController.php`.
- Contexto: `StorefrontContext.php`, `StorefrontContextBuilder.php`.
- Soporte: `StorefrontNavigation.php`, `StorefrontSections.php`.
- Vistas: los ocho Blade enumerados en la sección anterior.
- Pruebas nuevas: `StorefrontContextTest.php`, `StorefrontContextIntegrationTest.php`, `StorefrontQueryBudgetTest.php`.

El detalle de propiedad, precedencia y aliases está en `docs/store-builder-canonical-sources.md`. La medición completa está en `docs/store-builder-query-comparison.md`.

## Verificación

- Siete suites green anteriores: 46 pruebas, 780 aserciones, 0 fallos.
- Pruebas nuevas: 10 pruebas, 109 aserciones, 0 fallos.
- Total requerido: 56 pruebas, 889 aserciones, 0 fallos.
- Navegador: 30/30 escenarios, plantillas 3/3, drawer 3/3 y modal 9/9.
- Incidencias: 0 fallos HTTP, overflow, errores JavaScript, promesas rechazadas, `console.error`, recursos esenciales o imágenes visibles.
- Instalación/build limpio: Composer, `npm ci`, Vite, rutas y caché de vistas correctos.

La prueba visual no encontró diferencias de contenido o layout respecto de la baseline. No se repitieron las 180 aperturas dirigidas del foco porque el modal no fue modificado.

## Fuera de alcance y riesgos conservados

- No se crearon ni ejecutaron migraciones del proyecto.
- No se tocaron datos reales ni se transformaron valores heredados.
- No se unificaron escrituras, borradores ni publicación.
- No se eliminó el runtime JavaScript ni plantillas heredadas.
- No se implementó Blog ni la separación definitiva Inicio/Tienda.
- No hubo push, despliegue ni activación pública.
- `npm audit` informa 9 vulnerabilidades de dependencias (1 baja, 1 moderada, 5 altas y 2 críticas), pendientes de una tarea separada.
- Vite mantiene un warning existente de orden de `@import` en CSS; el build termina correctamente.
- La suite completa `tests/Unit` contiene un fallo ajeno en el archivo local no rastreado `RouterComercialTest`, que espera textos antiguos del asesor comercial. No forma parte de las suites autorizadas ni fue modificado.

## Dictamen

La Fase 1A queda **VERDE**. El contrato único, la precedencia central y la compatibilidad están probados sin cambiar persistencia. Recomendación: **APTO para autorizar la Fase 1B**, pero no iniciarla sin aprobación expresa. Esa fase debe tratar las escrituras duplicadas, integridad de datos y versionado/borradores mediante un plan y migraciones reversibles separados.
