# PASO 8 — Validación real en navegador

Fecha: 2026-07-29  
Rama: `stabilize/store-builder-baseline`  
HEAD probado: `5ec1dbad30271b96d92177738e5888fcdb842683`  
Estado: **ROJO**

## Alcance y aislamiento

Este paso fue exclusivamente de validación. No se modificó código funcional, pruebas, dependencias, datos reales ni `.env` real. No hubo push, despliegue ni inicio de la Fase 1.

Se creó el worktree limpio:

```text
C:\Users\luich\AppData\Local\Temp\avan-store-builder-step8-clean-20260729-153748
```

Estado inicial: HEAD exacto, `status --porcelain` vacío. La base usada fue únicamente:

```text
C:\Users\luich\AppData\Local\Temp\avan-store-builder-step8-20260729-153748.sqlite
```

La base ficticia contiene un usuario QA, una tienda activa, tres categorías, doce productos y una segunda tienda con otro propietario para controlar aislamiento. Después de aplicar las tres plantillas, la tienda activa quedó en `computienda` y la tienda de control conservó `direct`.

## Navegador y automatización

Navegadores encontrados:

- Chrome estable `147.0.7727.116`: `C:\Program Files\Google\Chrome\Application\chrome.exe`.
- Edge estable `150.0.4078.105`: `C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe`.

Se seleccionó Chrome. El proyecto no contiene Playwright, Playwright Core, Puppeteer ni Puppeteer Core. Se reutilizó el enfoque del PASO 6 mediante Chrome DevTools Protocol desde un harness PowerShell temporal, sin instalar paquetes ni modificar manifests. El harness, los JSON y los logs permanecen fuera de Git:

```text
C:\Users\luich\AppData\Local\Temp\store-builder-browser-harness-step8-20260729-153748
```

Comando definitivo:

```powershell
& <HARNESS>\audit.ps1 -BaseUrl http://127.0.0.1:8766 -OutputPath <HARNESS>\browser-results-green-candidate.json -ChromePath 'C:\Program Files\Google\Chrome\Application\chrome.exe'
```

Servidor: `http://127.0.0.1:8766`, PID listener `1536`, log `laravel-server.log`. El proceso fue detenido y no quedan procesos Chrome asociados al perfil temporal.

## Preparación reproducible

- Composer: 119 instalaciones desde `composer.lock`, correcto.
- npm: 160 paquetes desde `package-lock.json`, correcto.
- Vite: build correcto.
- Laravel: 481 rutas.
- `view:clear`: correcto.
- `view:cache`: correcto.
- Migraciones: solo sobre SQLite temporal.

Las advertencias ya conocidas del PHAR de Composer y del orden de `@import` de PostCSS permanecen fuera del alcance.

## Matriz definitiva

Se ejecutaron **30 escenarios**:

| Grupo | 375×812 | 768×1024 | 1440×1100 | Total |
|---|---:|---:|---:|---:|
| Login | 1 | 1 | 1 | 3 |
| Configuración | 1 | 1 | 1 | 3 |
| Diseñador — Plantillas | 1 | 1 | 1 | 3 |
| Diseñador — Constructor | 1 | 1 | 1 | 3 |
| Ecommerce Inicio/Tienda | 2 | 2 | 2 | 6 |
| Direct Inicio/Tienda | 2 | 2 | 2 | 6 |
| CompuTienda Inicio/Tienda | 2 | 2 | 2 | 6 |

Resultado general:

- HTTP distintos de 200: 0.
- `document.readyState` distinto de `complete`: 0.
- Errores de página: 0.
- Promesas rechazadas: 0.
- `console.error` atribuible al sistema: 0.
- Recursos esenciales fallidos/404: 0.
- Imágenes visibles rotas: 0.
- Overflow horizontal: 0.
- Modales accidentales: 0.
- Overlays bloqueantes al cargar: 0.
- Contenido principal ausente: 0.
- Tema público distinto al seleccionado: 0.

Dos imágenes con `src` vacío y ocultas mediante `x-show` aparecieron en una pasada preparatoria como eventos de recurso, pero no eran visibles ni recursos esenciales. El resultado definitivo registra solamente recursos visibles o scripts/hojas de estilo.

## Modal global

Se ejecutaron nueve comprobaciones completas, una por pantalla administrativa autenticada y breakpoint.

Resultados que aprobaron **9/9**:

- `window.__confirm()` sin argumentos devuelve `false`.
- El modal permanece cerrado y el `body` desbloqueado.
- Título, mensaje, botones, `role="dialog"` y `aria-modal="true"`.
- Cancelar devuelve `false`, cierra y restaura scroll/foco.
- Confirmar devuelve `true`, cierra y restaura scroll/foco.
- Escape devuelve `false`.
- Clic exterior devuelve `false`.
- Concurrencia: la primera promesa devuelve `false`, queda una sola segunda instancia activa y luego se resuelve sin pendientes.
- Cero errores JavaScript durante todos los flujos.

Resultado de foco inicial: **7/9**. En la ejecución definitiva falló de manera intermitente en:

1. Constructor visual, `375 × 812`.
2. Diseñador — Plantillas, `1440 × 1100`.

En ambos casos el modal estaba visible y completamente funcional, pero después de esperar dos segundos `document.activeElement` seguía siendo el botón iniciador en lugar del botón Cancelar. Pasadas anteriores mostraron el mismo fallo en combinaciones diferentes, lo que confirma comportamiento intermitente y no un selector fijo del harness.

Por el requisito obligatorio de foco dentro del modal, el bloque queda **ROJO**.

## Drawer administrativo

Las tres pantallas autenticadas a `375 × 812` aprobaron:

- inicio fuera de pantalla y sin reservar ancho;
- botón visible y `aria-expanded=false`;
- apertura, overlay, bloqueo de `body` y foco dentro del drawer;
- cierre por Escape y overlay, con foco restaurado;
- cierre al navegar según la política existente `closeSidebar(false)`;
- resize a 1024 limpia overlay/bloqueo y presenta sidebar de escritorio;
- cero overflow.

Resultado: **3/3 aprobado**.

## Plantillas

Las aplicaciones se realizaron desde la interfaz del Diseñador, incluyendo el diálogo “Cambiar plantilla”.

| Plantilla | Respuesta | `theme.key` | Mensaje/CTA | Inicio/Tienda | JS/overflow/recursos |
|---|---:|---|---|---|---|
| Ecommerce | 200 | `ecommerce` | Correcto, una sola vez | 6/6 | 0 fallos |
| Direct | 200 | `direct` | Correcto, una sola vez | 6/6 | 0 fallos |
| CompuTienda | 200 | `computienda` | Correcto, una sola vez | 6/6 | 0 fallos |

Solo aparecieron las tres tarjetas oficiales; no hubo advertencia ni tarjeta heredada. La segunda tienda ficticia no cambió.

## Pruebas automatizadas

Las siete suites terminaron con **45 pruebas, 759 aserciones y 0 fallos**:

- `CatalogTemplatesManifestTest`.
- `PublicTemplateRuntimeTest`.
- `GlobalTemplateSettingsTest`.
- `StorefrontHomepageBuilderTest`.
- `StorefrontStructureV2Test`.
- `AdminResponsiveLayoutTest`.
- `AdminGlobalConfirmModalTest`.

## Cierre

- `git status --short` del worktree: vacío.
- `git diff --check`: sin salida.
- Servidor: detenido.
- Procesos del navegador temporal: 0.
- Tag green: no creado.
- Rama `baseline/store-builder-green`: no creada.

Dictamen: **ROJO / NO APTO para iniciar la Fase 1**. Se requiere autorización separada para corregir el foco inicial intermitente del modal y repetir el PASO 8.

## PASO 9 — corrección y repetición — 2026-07-29

La carrera de foco quedó reproducida en 90 aperturas: 78 correctas y 12 fallidas. El intento único ocurría en ocasiones mientras `x-show` conservaba `display:none`; el modal se hacía visible en el frame siguiente sin volver a intentar el foco.

La corrección sincroniza `focusInitial()` con el DOM visible, permite hasta cuatro intentos por `requestAnimationFrame` y cancela cualquier frame pendiente al cerrar o interactuar. Resultado posterior: 180/180 aperturas dirigidas, máximo 17,4 ms y dos intentos usados; estabilidad adicional 45/45.

Se repitieron los 30 escenarios completos: cero errores JavaScript, recursos esenciales fallidos, imágenes visibles rotas y overflow; drawer 3/3, modal 9/9 y plantillas 3/3. Las siete suites aprobaron 46 pruebas y 780 aserciones. Estado actualizado: **VERDE**. Detalle: `docs/store-builder-modal-focus-report.md`.
