# Paso 5 — shell administrativo responsive

Fecha: 2026-07-28  
Rama: `stabilize/store-builder-baseline`  
Alcance: shell administrativo global y contenedor del Diseñador. No incluye tienda pública, migraciones, runtime público, plantillas funcionales ni despliegue.

## Causa original

El propietario real de la barra lateral, encabezado y área principal es `resources/views/layouts/app.blade.php`. El estado Alpine reducía el sidebar móvil a `w-0`, pero `.sb-bixo` imponía `position: relative` y no existía una traslación que sacara el panel del viewport. El shell conservaba restricciones de ancho/overflow y carecía de un disparador móvil accesible. Por eso, a 375 px el sidebar seguía condicionando el espacio útil y el Diseñador quedaba recortado.

El propietario del contenido del editor es `resources/views/settings/design.blade.php`. Allí faltaban `min-width: 0`, límites de ancho y reglas móviles para grids. Durante la revisión visual se detectó además que los controles directos del grid del pop-up conservaban un ancho intrínseco mayor que su columna.

`resources/css/app.css` aporta estilos generales y reglas de paneles, pero no construye el shell BIXO actual. No existe un partial independiente de sidebar para este layout. Alpine ya estaba cargado por `resources/js/app.js`; se reutilizó sin añadir dependencias ni listeners globales duplicados.

## Comportamiento implementado

- Menos de 768 px: sidebar inicialmente cerrado, `fixed`, fuera del flujo con `translateX(-100%)`, ancho máximo `calc(100vw - 3rem)` y contenido principal al 100 %.
- Apertura mediante botón táctil de 44 × 44 px; overlay, bloqueo de scroll y foco inicial en el botón de cierre.
- Cierre mediante botón, overlay, Escape o enlace de navegación; se retiran overlay/bloqueo y el foco vuelve al disparador.
- Al redimensionar a escritorio se limpia el estado móvil, overlay y clase del `body`.
- Entre 768 y 1023 px se conserva el sidebar colapsado utilizable de 52 px y no aparece overlay.
- Desde 1024 px se conserva el comportamiento de escritorio existente; a 1440 px el sidebar abierto mantiene 240 px.
- El Diseñador usa ancho completo, padding móvil, grids de una columna y controles ajustados a su columna. La navegación secundaria ancha conserva scroll horizontal interno controlado.

## Accesibilidad

- `type="button"`, `aria-label`, `aria-controls="admin-sidebar"` y `aria-expanded` dinámico en el disparador.
- Un único `id="admin-sidebar"`, nombre accesible, `aria-hidden` e `inert` cuando el drawer móvil está cerrado.
- Botón de cierre con nombre accesible y área de 44 × 44 px.
- Foco visible y restauración de foco verificada.
- Escape verificado.
- `prefers-reduced-motion` elimina la transición del shell y reduce animaciones/transiciones del Diseñador.

## Archivos modificados

- `resources/views/layouts/app.blade.php`
- `resources/views/settings/design.blade.php`
- `tests/Feature/AdminResponsiveLayoutTest.php`
- `docs/store-builder-admin-responsive-report.md`
- `docs/store-builder-test-results.md`
- `docs/store-builder-phase-1-readiness.md`

No se modificaron `resources/views/settings/index.blade.php`, `resources/css/app.css` ni `resources/js/app.js`: el arreglo global se resolvió en el layout propietario y el ajuste específico en el Diseñador.

## Pruebas automatizadas

La prueba nueva cubre: disparador, atributos ARIA, drawer único, cierre accesible, Escape, bloqueo de scroll, retorno de foco, dos pestañas, tres plantillas oficiales, rutas de guardado y reutilización del mismo shell en una pantalla administrativa normal.

Resultado previo conservado: **37 tests, 675 aserciones, 0 fallos**.  
Resultado de la prueba nueva: **4 tests, 44 aserciones, 0 fallos**.  
Resultado conjunto final esperado tras la última validación: **41 tests, 719 aserciones, 0 fallos**.

## Pruebas responsive reales

Se renderizaron con SQLite de prueba las vistas Plantillas, Constructor visual y Configuración normal. Se validaron en Chromium mediante DevTools, sin usar producción.

| Viewport | Plantillas `scrollWidth/innerWidth` | Constructor `scrollWidth/innerWidth` | Configuración `scrollWidth/innerWidth` | Sidebar |
|---|---:|---:|---:|---|
| 375 × 812 | 375 / 375 | 375 / 375 | 375 / 375 | drawer cerrado, 320 px al abrir |
| 375 × 900 | 375 / 375 | 375 / 375 | 375 / 375 | drawer cerrado, 320 px al abrir |
| 768 × 1024 | 768 / 768 | 768 / 768 | 768 / 768 | relativo, 52 px |
| 1024 × 768 | 1024 / 1024 | 1024 / 1024 | 1024 / 1024 | relativo, 52 px |
| 1440 × 1100 | 1440 / 1440 | 1440 / 1440 | 1440 / 1440 | relativo, 240 px |

En los 15 casos se cumplió `scrollWidth <= innerWidth + 1`. A 375 px el selector presentó una columna. La navegación secundaria del Constructor desborda únicamente dentro de su contenedor `overflow-x-auto`; no amplía el documento. Los inputs del pop-up quedaron dentro del formulario.

Interacciones verificadas a 375 px:

- drawer inicialmente en `x = -320`, contenido principal de 375 px;
- apertura en `x = 0`, overlay visible, `aria-expanded=true`, `body` bloqueado y foco en cerrar;
- cierre por botón, Escape y overlay con foco restaurado;
- cierre por navegación;
- cinco etiquetas principales visibles dentro del drawer;
- resize abierto de 375 a 1024 limpia overlay y bloqueo, oculta el botón móvil y devuelve el sidebar a `position: relative`.

## Validación técnica

- Sintaxis PHP/Blade de archivos modificados.
- Sintaxis del objeto JavaScript/Alpine responsive mediante Node.
- `php artisan route:list`.
- `php artisan view:clear` y `php artisan view:cache`.
- Inspección visual en 375 y 1440 px.
- No se ejecutó `npm build` porque no se modificó el bundle.
- No se ejecutaron migraciones ni despliegue.

## Commits

1. `fix(admin): make sidebar responsive on mobile`
2. `fix(designer): prevent mobile content clipping`
3. `test(admin): cover responsive shell contract`

## Fuera de alcance

Continúan fuera de este paso la Fase 1 estructural, la unificación de fuentes de configuración, cambios en `store_sections`, adaptación funcional de plantillas, eliminación del runtime público, menú público de CompuTienda, rediseño general, migraciones y despliegue.

## Estado

**VERDE dentro del alcance del Paso 5.** El riesgo responsive de 375 px documentado al cerrar el Paso 4 queda resuelto. Esto no autoriza por sí solo la Fase 1 ni un despliegue.
