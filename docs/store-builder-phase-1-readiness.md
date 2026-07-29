# Preparación para la Fase 1 del Store Builder

Fecha: 2026-07-28. Dictamen: **NO APTO** para iniciar o desplegar la Fase 1 sobre el árbol actual.

La conclusión no invalida el diseño propuesto. Significa que antes debe existir una base reproducible: árbol controlado, pruebas verdes o fallos aceptados explícitamente, vistas productivas resolubles y una única precedencia de configuración.

## Matriz de plantillas productivas

Fuente: `PublicController::PRODUCTION_TEMPLATE_VIEWS` y exploración estática de las vistas. “StoreView” indica soporte directo para variar Inicio/Tienda/Nosotros/Contacto dentro de la plantilla. Blog se sirve por el flujo V2 separado y no está implementado directamente por ninguna de estas vistas.

| Clave | Vista Blade | Existe | StoreView | StoreMenu | Secciones/runtime | Header propio | Footer propio | Inicio | Tienda | Nosotros | Contacto | Blog |
|---|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| `default` | `public.catalog` | Sí | No | No | Sí | Sí | No | Sí | No | No | No | No |
| `direct` | `public.templates.direct` | Sí | No | No | Sí | No | No | Sí | No | No | No | No |
| `ella` | `public.templates.ella` | Sí | No | No | Sí | Sí | Sí | Sí | No | No | No | No |
| `editorial` | `public.templates.editorial` | **No** | No | No | No | No | No | No | No | No | No | No |
| `nordic` | `public.templates.nordic` | Sí | No | No | Sí | Sí | Sí | Sí | No | No | No | No |
| `luxe` | `public.templates.luxe` | **No** | No | No | No | No | No | No | No | No | No | No |
| `flash` | `public.templates.flash` | Sí | No | No | Sí | Sí | Sí | Sí | No | No | No | No |
| `bistro` | `public.templates.bistro` | **No** | No | No | No | No | No | No | No | No | No | No |
| `urban` | `public.templates.urban` | Sí | No | No | Sí | Sí | Sí | Sí | No | No | No | No |
| `boutique` | `public.templates.boutique` | Sí | No | No | Sí | Sí | Sí | Sí | No | No | No | No |
| `fresh` | `public.templates.fresh` | Sí | No | No | Sí | Sí | Sí | Sí | No | No | No | No |
| `porto` | `public.templates.porto` | Sí | No | No | Sí | Sí | Sí | Sí | No | No | No | No |
| `licoreria` | `public.templates.licoreria` | Sí | No | No | Sí | Sí | Sí | Sí | No | No | No | No |
| `farma` | `public.templates.farma` | Sí | No | No | Sí | Sí | Sí | Sí | No | No | No | No |
| `lavanderia` | `public.templates.lavanderia` | Sí | No | No | Sí | Sí | Sí | Sí | No | No | No | No |
| `ecommerce` | `public.templates.ecommerce` | Sí | No | No | Sí | Sí | Sí | Sí | No | No | No | No |
| `tecnologia` | `public.templates.tecnologia` | Sí | No | No | Sí | Sí | Sí | Sí | No | No | No | No |
| `computienda` | `public.templates.computienda` | Sí | **Sí** | **Sí** | Sí | Sí | Sí | Sí | Sí | Sí | Sí | No |

Resumen:

- Claves productivas: **18**.
- Vistas registradas inexistentes: **3** (`editorial`, `luxe`, `bistro`).
- Plantillas sin soporte directo para `storeView`: **17**.
- Plantillas con soporte directo para `storeView` y `storeMenu`: solo `computienda`.
- Las 15 vistas existentes consumen `store_sections` mediante `public-store-runtime` o el flujo compartido equivalente; las 3 vistas inexistentes no pueden hacerlo.
- Ninguna vista de plantilla implementa directamente Blog. Blog depende de la ruta/vista V2 separada y de su flag de activación.

## Consultas y rendimiento

Hallazgos estáticos; no se optimizó código.

### Llamadas repetidas a `Project::setting()`

- `app/Models/Project.php:69-71`: cada invocación resuelve la relación mediante una consulta individual; no hay caché por instancia.
- `app/Support/StorefrontSections.php:23-42`: siete lecturas separadas durante la construcción de defaults.
- `app/Http/Controllers/StorePageController.php:21,32,41,55,60,64,73`: múltiples lecturas dentro del mismo request.
- `app/Http/Controllers/PublicController.php:63,82,99,545`: lecturas adicionales en distintos flujos públicos.
- `resources/views/settings/design.blade.php:10,21-24,41,51-66,131,463-1750`: gran cantidad de llamadas desde la vista. En la línea 463, `logo_url` se consulta varias veces dentro de una sola expresión; el patrón se repite con favicon en la línea siguiente.
- `resources/views/settings/qr.blade.php:4`: un closure consulta por clave, multiplicando queries al renderizar los campos.

Riesgo: comportamiento N+1 dentro de una sola página, particularmente grave en el diseñador.

### Consultas directas desde Blade

- `resources/views/public/templates/computienda.blade.php:5`: vuelve a consultar `project_settings` aunque el controlador ya entrega `$settings`.
- `resources/views/settings/design.blade.php:203`: vuelve a hacer `pluck` de settings.
- `resources/views/components/public-store-runtime.blade.php:67-71`: consulta productos, servicios, categorías y categorías de footer al renderizar.
- `resources/views/components/storefront-home-sections.blade.php:16`: consulta productos según el bloque; `:99` consulta categorías.
- `resources/views/settings/qr.blade.php:4`: consulta repetida por cada opción solicitada.

### Consultas repetidas a `project_settings`

- `app/Http/Controllers/PublicController.php:156,199,533,588,690,722`: varios métodos vuelven a cargar la colección completa con `pluck`.
- Se suman las consultas descritas en CompuTienda y Diseñador.

### Consultas repetidas a `store_sections`

- `app/Http/Controllers/PublicController.php:160` y `:207`: loaders paralelos en `storefrontBaseData()` y `prepararCatalogo()`.
- `app/Support/StorefrontSections.php:115`: carga adicional desde el soporte.
- `app/Http/Controllers/StorePageController.php:77`: carga específica para Blog.

### Relaciones y N+1

- La principal fuente comprobable es `Project::setting()`, que consulta una fila por llamada.
- El catálogo central en `prepararCatalogo()` sí usa eager loading de imagen principal/categoría y categorías hijas; esa ruta mitiga el N+1 principal.
- Las consultas de productos en `storefront-home-sections.blade.php` pueden duplicar colecciones ya preparadas por el controlador (`newArrivals`, `onSale`, `featured`).
- `StorefrontNavigation::pageUrl()` mantiene una caché estática de claves de página, lo cual reduce lecturas repetidas en ese punto.
- No se debe retirar el runtime antiguo sin adaptar antes todas las plantillas, porque actualmente es su mecanismo de compatibilidad con secciones.

## Bloqueantes de Fase 1

1. Repositorio no limpio: 61 modificados y 7,884 entradas nuevas sin seguimiento impiden atribuir con seguridad un cambio o rollback.
2. Dos de cinco archivos de prueba están verdes; tres presentan fallos reproducibles.
3. La plantilla Direct genera HTTP 500 si falta `hero_align`.
4. El partial del constructor requiere `$homeSections`, pero el contrato del controlador usado en prueba no lo entrega.
5. La navegación esperada del diseñador no coincide con la interfaz renderizada.
6. CompuTienda usa un nombre no canónico (`promotions`) para un marcador de sección.
7. Tres vistas registradas como productivas no existen.
8. Diecisiete plantillas no soportan directamente `storeView`; cambiar de página o plantilla no garantiza una estructura compartida.
9. Hay 14 grupos de conflicto entre configuración heredada, secciones y markup propio.
10. Las consultas desde Blade y `Project::setting()` repetido hacen incierto el rendimiento del editor y frontend.
11. Vistas y eventos estaban cacheados, por lo que una comprobación manual puede no representar el código fuente actual si no se controla el ciclo de caché.
12. La tabla `project_templates` vacía y sin garantía de unicidad no respalda aún el modelo futuro de plantilla activa.

## Condiciones mínimas para pasar a APTO

Estas acciones requieren una fase posterior expresamente autorizada:

1. Preservar el estado actual en una rama/commit o clon de trabajo verificable.
2. Definir cuáles de las 7,945 entradas son parte válida del Store Builder.
3. Resolver los cuatro defectos exactos identificados por pruebas.
4. Decidir si se crean las tres vistas faltantes o se retiran de la lista productiva.
5. Definir una única precedencia entre `project_settings`, `store_sections` y markup nativo.
6. Adaptar progresivamente las 17 plantillas sin `storeView`, manteniendo un fallback compatible.
7. Añadir restricciones de integridad solo después de limpiar y respaldar datos.
8. Repetir los cinco archivos de prueba hasta obtener una línea base verde o documentar excepciones aprobadas.
9. Ejecutar smoke tests por proyecto, dominio y dispositivo.
10. Validar respaldo y restauración antes de cualquier despliegue.

## Dictamen

**NO APTO.** No debe iniciarse una implementación destructiva ni activarse públicamente la Fase 1 sobre este estado. El próximo paso seguro es estabilizar la línea base en una rama controlada y corregir, con aprobación separada, los fallos ya reproducidos.

## Revisión posterior al Paso 3 — 2026-07-28

Rama de estabilización: `stabilize/store-builder-baseline`.

Se resolvieron y probaron los cuatro fallos inicialmente autorizados:

- Marcadores canónicos de CompuTienda.
- Contrato explícito de `$homeSections`.
- Normalización segura de `hero_align` en Direct.
- Navegación principal Plantillas/Constructor visual y scroll interno.

Cuatro suites están completamente verdes. La quinta, `StorefrontStructureV2Test`, ejecuta correctamente los casos corregidos, pero conserva dos fallos nuevos fuera del alcance del Paso 3:

1. La respuesta JSON de `applyTemplate()` no incluye `theme.key` ni `public_url`.
2. La pantalla de Plantillas no muestra el mensaje y CTA de confirmación que espera la prueba.

Resultado conjunto: 30 tests observados, 28 aprobados y 2 fallidos. Por la regla “no declarar verde si alguna prueba falla”, el estado sigue siendo **ROJO** y el dictamen continúa **NO APTO** para la siguiente etapa.

Próxima acción recomendada: aprobar una estabilización adicional limitada a esos dos contratos, sin iniciar todavía unificación de configuraciones, adaptación masiva de plantillas, migraciones, rediseño general ni despliegue.

## Revisión posterior al Paso 4 — 2026-07-28

Los dos contratos pendientes quedaron resueltos y el catálogo administrativo se redujo a `ecommerce`, `direct` y `computienda` desde una única fuente. Las plantillas heredadas conservan compatibilidad pública, no se migraron automáticamente y ya no pueden aplicarse mediante el selector ni el endpoint oficial.

Las cinco suites auditadas están verdes: **37 tests, 675 aserciones y 0 fallos**. El Paso 4 queda **VERDE**.

Esto no habilita automáticamente la Fase 1 completa. Persisten condiciones previamente documentadas y un hallazgo manual nuevo: a 375 px la barra lateral del layout administrativo recorta el contenido del Diseñador. Debe corregirse mediante una autorización responsive separada antes de considerar el sistema listo para una fase amplia o despliegue.
