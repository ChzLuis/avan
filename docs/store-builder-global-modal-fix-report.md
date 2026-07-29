# PASO 7 - Modal global de confirmación

Fecha de verificación: 2026-07-29  
Rama: `stabilize/store-builder-baseline`  
HEAD inicial: `bec11ddc9d3396ea59e223d8936afad615b0d5e1`  
HEAD funcional y de pruebas: `35cbfca2cab57bcf8fc63de458310b6fb0406076`

## Alcance

Se corrigió exclusivamente el contrato del modal global de confirmación del shell administrativo. No se iniciaron la Fase 1, migraciones estructurales, cambios de plantillas públicas, despliegues ni push.

## Causa exacta

La expresión anterior era equivalente a:

```html
x-init="window.__confirm = (opts) => open(opts)"
```

La asignación evaluaba a la propia función. Alpine podía tratar ese valor como callback de inicialización y ejecutarlo sin argumentos. `open(opts)` accedía después a propiedades como `opts.title`, lo que producía `TypeError` durante la carga de las vistas administrativas.

## Contrato revisado

Las llamadas existentes están en las vistas de categorías, productos y servicios del catálogo. Todas esperan `Promise<boolean>` y usan `title`, `msg` y `confirmLabel`.

El contrato final es:

- `window.__confirm(optsValido)` abre un único modal y devuelve `Promise<boolean>`.
- Confirmar resuelve `true`; cancelar, Escape o clic exterior resuelven `false`.
- `undefined`, `null`, arrays y valores no objeto resuelven `false` sin abrir el modal ni lanzar una excepción.
- Se conservan `title`, `msg`, `confirmLabel` y `confirmClass`.
- Se admiten los alias seguros `message`, `confirmText`, `cancelLabel` y `cancelText`.
- Si llega una segunda solicitud mientras existe una pendiente, la anterior se resuelve como `false` antes de abrir la nueva.
- Título y mensaje se renderizan mediante `x-text`; no se interpreta HTML arbitrario.

La inicialización usa el método automático `init()` del componente Alpine. Registra una sola referencia estable en `window.__confirm`; `destroy()` elimina exclusivamente esa misma referencia y resuelve de forma segura una solicitud pendiente.

## Accesibilidad y comportamiento

El modal conserva su apariencia y agrega o asegura `role="dialog"`, `aria-modal`, `aria-labelledby`, `aria-describedby`, botones `type="button"`, foco inicial en Cancelar, trampa de foco, Escape, restauración del foco y bloqueo/restauración del scroll. No se sustituyó por `window.confirm`.

## Commits controlados

- `d5c3a32` - `fix(admin): initialize global confirmation modal safely`
- `35cbfca` - `test(admin): cover global confirmation modal contract`

El primer commit contiene solo el hunk del modal en `resources/views/layouts/app.blade.php`. El segundo contiene solo `tests/Feature/AdminGlobalConfirmModalTest.php`. Los cambios locales ajenos del directorio principal no entraron en ninguno de los commits.

## Checkout e instalación limpia

Checkout temporal nuevo:

```text
C:\Users\luich\AppData\Local\Temp\avan-store-builder-step7-clean-20260729-013135
```

El checkout inició limpio y en `35cbfca2cab57bcf8fc63de458310b6fb0406076`. Resultados:

- Composer desde `composer.lock`: 119 instalaciones, correcto.
- npm desde `package-lock.json`: 160 paquetes, correcto.
- Vite: build correcto.
- Laravel `12.54.1`; PHP `8.2.12`.
- `route:list`: 481 rutas.
- `view:clear`: correcto.
- `view:cache`: correcto.
- `php -l` de la prueba nueva: correcto.
- `node --check` del objeto Alpine extraído: correcto.

La advertencia existente de PostCSS sobre la posición de `@import` y la advertencia del PHAR de Composer no se corrigieron porque están expresamente fuera del alcance.

## Pruebas automatizadas

Las siete suites autorizadas terminaron con **45 pruebas, 759 aserciones y 0 fallos**. Se usó un `.env` temporal de testing, sin secretos, para evitar advertencias causadas únicamente por la ausencia física del archivo; no se copió el `.env` real.

La suite nueva cubre unicidad, registro seguro, normalización de opciones, contrato booleano, concurrencia, accesibilidad y permanencia del shell responsive, las dos pestañas y las tres plantillas oficiales.

## Navegador

Se preparó una base SQLite temporal nueva y aislada, se ejecutaron migraciones solo en esa base y se crearon datos ficticios. No se tocaron datos reales.

La conexión de navegador disponible para esta ejecución devolvió una lista vacía de navegadores. Por tanto no fue posible repetir válidamente las 21 comprobaciones ni ejecutar el contrato del modal en las nueve vistas administrativas. No se sustituyó esa validación por una afirmación basada solo en pruebas de servidor.

Consecuencias:

- Errores JavaScript administrativos finales: no verificados en navegador.
- Errores JavaScript públicos finales: no verificados en navegador.
- Aplicación visual de las tres plantillas: no repetida en navegador en este PASO.
- Responsive y drawer: cubiertos por pruebas de contrato, pero no repetidos visualmente.

## Estado del worktree limpio

Después de instalación, build y suites:

- `git status --short`: vacío.
- `git diff --check`: sin salida.
- Dependencias, build, caché, sesiones y `.env` temporal: ignorados.
- Archivos fuente copiados manualmente: ninguno.

## Dictamen

El arreglo y las pruebas automatizadas están correctos, pero el criterio obligatorio de navegador no pudo ejecutarse. El estado del PASO 7 permanece **ROJO**. No se creó el tag `store-builder-baseline-green-2026-07-29` ni la rama `baseline/store-builder-green`.

Recomendación: **NO APTO aún para iniciar la Fase 1**. Falta repetir las 21 comprobaciones en una sesión con navegador disponible; solo si todas quedan en cero errores debe crearse el tag y la rama baseline.

## PASO 8 — navegador disponible — 2026-07-29

Chrome estable `147.0.7727.116` permitió ejecutar una matriz ampliada de 30 escenarios. Se confirmaron cero errores JavaScript administrativos/públicos, cero 404 esenciales, cero imágenes visibles rotas, cero overflow, tres drawers correctos y las tres plantillas oficiales operativas.

El modal aprobó sin argumentos, cancelar, confirmar, Escape, clic exterior y concurrencia en las nueve pantallas autenticadas. Sin embargo, el foco inicial solo se trasladó dentro del diálogo en 7 de 9 casos de la ejecución definitiva. El fallo es intermitente y apareció en Constructor móvil y Plantillas escritorio después de una espera de dos segundos.

El estado permanece **ROJO**. No se creó tag ni rama green. Detalle completo: `docs/store-builder-browser-validation-report.md`.

## PASO 9 — foco inicial determinista — 2026-07-29

La intermitencia fue una carrera entre el `$nextTick` de `open()` y `x-show`: 12 de 90 intentos ocurrieron cuando el modal aún tenía `display:none`. No hubo robo posterior de foco ni competencia con el drawer o el focus trap.

El modal ahora valida el estado real del DOM y reintenta por frame de forma limitada y cancelable. Aprobó 180/180 aperturas dirigidas, toda la regresión 9/9, la matriz de 30 escenarios y 45/45 aperturas adicionales. Las siete suites finalizaron con 46 pruebas, 780 aserciones y 0 fallos. Estado: **VERDE**. Detalle: `docs/store-builder-modal-focus-report.md`.
