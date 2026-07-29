# PASO 9 — Foco determinista del modal global

Fecha: 2026-07-29
Rama: `stabilize/store-builder-baseline`
HEAD inicial: `467d4d6355b2f408bee4f5d8f7db5dc5d0b3fc0f`
HEAD funcional probado: `7b25a8e137123669f65b82a41d5debafe5f47019`
Estado: **VERDE**

## Alcance

El cambio se limitó al modal global de `resources/views/layouts/app.blade.php` y a su contrato en `tests/Feature/AdminGlobalConfirmModalTest.php`. No se modificaron plantillas públicas, drawer, Diseñador, migraciones, datos reales, `.env` real ni dependencias. No hubo push, despliegue ni inicio de la Fase 1.

Commits funcionales:

- `61860fb`: `fix(admin): make confirmation modal initial focus deterministic`.
- `7b25a8e`: `test(admin): cover deterministic modal focus contract`.

## Reproducción anterior a la corrección

Se usó Chrome estable `147.0.7727.116` y el harness CDP temporal del PASO 8. Se realizaron 90 aperturas: Configuración, Plantillas y Constructor; cada vista en 375, 768 y 1440 px; diez repeticiones por combinación.

Resultado: **78/90** aperturas enfocaron el botón Cancelar y **12/90** conservaron el foco en el botón iniciador. Las 12 fallas se distribuyeron así:

| Vista | Viewport | Correctas | Fallidas |
|---|---:|---:|---:|
| Configuración | 375×812 | 8 | 2 |
| Configuración | 768×1024 | 10 | 0 |
| Configuración | 1440×1100 | 8 | 2 |
| Plantillas | 375×812 | 5 | 5 |
| Plantillas | 768×1024 | 7 | 3 |
| Plantillas | 1440×1100 | 10 | 0 |
| Constructor | 375×812 | 10 | 0 |
| Constructor | 768×1024 | 10 | 0 |
| Constructor | 1440×1100 | 10 | 0 |

En cada fallo, la cronología fue:

1. Antes de abrir, el foco estaba en el iniciador.
2. `open()` guardaba ese elemento, actualizaba el contenido y asignaba `show = true`.
3. El único `$nextTick` intentaba enfocar Cancelar mientras el contenedor aún tenía `display:none` por `x-show`.
4. Chrome rechazaba silenciosamente el foco sobre el control no visible.
5. En el siguiente frame el modal ya era visible, pero no existía un segundo intento.
6. A 100 ms, 300 ms y 1 segundo el foco continuaba en el iniciador.

No participaron `inert`, `aria-hidden`, el focus trap, Alpine del drawer ni otro código que robara el foco. Tampoco hubo errores JavaScript. La causa exacta fue una carrera entre el `$nextTick` usado por `open()` y la aplicación de `x-show`/transición por Alpine.

## Corrección

`focusInitial()` es ahora el único responsable del foco inicial. El método:

- comprueba que el modal siga abierto y que el usuario no haya interactuado;
- obtiene `dialog` y el único `cancelButton` mediante `$refs`;
- valida `disabled`, `hidden`, ancestros `inert`, dimensiones, `display` y `visibility`;
- usa `focus({ preventScroll: true })` con fallback;
- verifica que `document.activeElement` quedó dentro del diálogo;
- reintenta mediante `requestAnimationFrame` solo cuando el DOM aún no es enfocables;
- limita el proceso a **4 intentos máximos**;
- cancela el frame pendiente al cerrar, destruir, reemplazar la solicitud o detectar interacción;
- no usa `setTimeout`, intervalos ni listeners persistentes.

Secuencia final:

```text
validar opciones
→ guardar iniciador
→ actualizar contenido
→ show = true
→ Alpine $nextTick
→ focusInitial()
→ validar visibilidad/inert/dimensiones
→ focus Cancelar
→ verificar foco dentro del diálogo
→ reintento limitado por frame solo si el DOM sigue transitorio
```

El focus trap conserva Cancelar y Confirmar como únicos controles del ciclo. Tab mueve a Confirmar y Shift+Tab vuelve a Cancelar. La restauración del foco al cerrar permanece separada del foco inicial.

## Prueba dirigida posterior

Se realizaron **180 aperturas**: nueve combinaciones administrativas por veinte repeticiones.

- Foco dentro del modal: **180/180**.
- Foco específicamente en Cancelar: **180/180**.
- Restauración al iniciador: **180/180**.
- Máximo tiempo hasta el foco: **17,4 ms**.
- Máximo número de intentos usado: **2 de 4**.
- Frames pendientes al cerrar: **0**.
- Page errors: **0**.
- Promesas rechazadas: **0**.
- `console.error`: **0**.

En 164 aperturas el foco quedó aplicado en el primer `$nextTick`. En las otras 16, `x-show` aún estaba transitorio y el segundo intento en el frame siguiente completó el foco.

## Regresión y matriz completa

Las nueve combinaciones aprobaron 9/9:

- llamada sin argumentos;
- apertura válida y foco inicial;
- Tab y Shift+Tab;
- Cancelar y Confirmar;
- Escape y clic exterior;
- dos solicitudes consecutivas;
- restauración de scroll y foco;
- cierre durante un intento pendiente;
- reapertura inmediata;
- una sola instancia y ninguna promesa pendiente.

La matriz completa del PASO 8 se repitió sobre 30 escenarios y quedó verde:

- HTTP 200: 30/30.
- Errores de página, promesas rechazadas y `console.error`: 0.
- Recursos esenciales 404 e imágenes visibles rotas: 0.
- Overflow: 0.
- Drawer: 3/3.
- Modal: 9/9.
- Ecommerce, Direct y CompuTienda: 3/3 aplicaciones correctas y 18/18 páginas públicas.
- Aislamiento: la tienda de control conservó `direct` mientras la activa terminó en `computienda`.

## Repetición de estabilidad

Se ejecutaron otras **45 aperturas**: nueve combinaciones por cinco repeticiones.

- Foco inicial en Cancelar: **45/45**.
- Restauración de foco: **45/45**.
- Máximo tiempo: **8 ms**.
- Máximo de intentos: **2**.
- Frames pendientes: **0**.
- Errores JavaScript: **0**.

## Checkout reproducible y pruebas

Worktree:

```text
C:\Users\luich\AppData\Local\Temp\avan-store-builder-step9-clean-20260729-161500
```

SQLite temporal:

```text
C:\Users\luich\AppData\Local\Temp\avan-store-builder-step9-20260729-161500.sqlite
```

Resultados:

- Composer: 119 instalaciones desde `composer.lock`.
- npm: 160 paquetes desde `package-lock.json`.
- Vite: build correcto.
- Laravel: 481 rutas; `view:clear` y `view:cache` correctos.
- Siete suites: **46 pruebas, 780 aserciones, 0 fallos**.
- Worktree final: limpio; `git diff --check` sin salida.
- Servidor: detenido.
- Procesos Chrome temporales: 0.
- Logs: sin errores de aplicación.

Los JSON y logs permanecen fuera del repositorio, en el harness temporal.

## Cierre green

Los veinte criterios del PASO 9 están aprobados. El tag `store-builder-baseline-green-2026-07-29` y la rama `baseline/store-builder-green` se crean después del commit documental para que ambos apunten al mismo HEAD de cierre. El hash exacto se verifica en la salida final, ya que el hash del commit que contiene este propio informe solo existe después de registrarlo.

Dictamen: **VERDE / APTO para autorizar el inicio de la Fase 1**. La Fase 1 no fue iniciada.
