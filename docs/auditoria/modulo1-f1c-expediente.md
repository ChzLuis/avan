# Módulo 1 · F1c — Auditoría y diseño de Cotizaciones · **v2**

> Estado: **DESPLEGADO EN PRODUCCIÓN (2026-08-16) y VERIFICADO (F1d 6/6 read-only).**
> Autorización expresa del usuario ("VE CON TODO / AVANCEMOS TODA LA FASE").
> Despliegue: stamps 20260816_101058 · 101148 · 102708 · 103016, SHA-256
> 17/17, 343 vistas compiladas sin error, log +0 bytes, datos byte a byte.
> Antes del despliegue se resolvieron los 3 hallazgos visuales de la última
> auditoría (verde falso de Rechazada, recorte a 320, tres columnas a 768).
> La v2 se ejecutó completa mas las correcciones de las auditorías
> incrementales de Codex (pago canónico, XSS de exportadores, fronteras float
> de serialización, concurrencia atómica del portal, paridad de rutas en ambos
> shells, contratos táctiles medidos). Codex quedó sin créditos durante la
> batería final: el cierre lo completó Claude con los mismos gates.
> Evidencia: 101 tests PHP focales verdes (466 aserciones), Playwright 18/18
> (7 breakpoints con contratos, 1/3/10/50 líneas, modal/foco/Escape, deep
> links, portal en 4 estados), build Vite correcto. **Producción NO tocada.**
>
> **Encuadre**: F1c es el **primer hito formal de UX2 · Núcleo comercial**
> en el [roadmap visual](bixo-ux-roadmap.md). Encabeza ese carril porque no
> es solo estética: cierra el ciclo de vida del documento. Por acuerdo con
> UX0, F1c **extrae sus tokens a `app.css`** en vez de añadir hex
> hardcodeado, y así aporta a los fundamentos en lugar de crear deuda.

---

## 0. Qué cambia respecto de v1

La v1 trataba F1c como un rediseño visual. **Era insuficiente.** Verifiqué una
por una las brechas señaladas por Codex —ninguna aceptada de palabra— y todas
resultaron ciertas. F1c es, además de visual, **el cierre del ciclo de vida
del documento**: no basta con esconder un botón si el servidor deja hacer la
acción por URL.

### 0.1 Brechas verificadas en el código (con su evidencia)

| # | Brecha | Evidencia |
|---|---|---|
| B1 | `convert()` crea pedido **desde cualquier estado** | tras la rama `converted` va directo a `$project->orders()->create(...)`; no hay `abort_unless(... 'accepted')` |
| B2 | Se puede **asignar `converted` a mano** | `update` línea 100 y `updateFull` línea 145: `in:draft,sent,accepted,rejected,converted` |
| B3 | Una convertida **se puede reenviar** | `send()` fija `status = 'sent'` sin mirar el estado previo |
| B4 | Una convertida **se puede reeditar** | `updateFull` reescribe líneas y total sin comprobar si ya generó pedido |
| B5 | **El portal muestra "Borrador"** en una convertida | `$statusMap` solo tiene `draft/sent/accepted/rejected`; `converted` cae en `?? $statusMap['draft']` |
| B6 | El portal **ofrece Aceptar/Rechazar** en una convertida | los botones dependen de ese mismo mapa |
| B7 | Una convertida **no puede subir comprobante** | `PortalController::proof()`: `if ($quote->status !== 'accepted')` — comparación cruda, ni siquiera canónica |
| B8 | **No hay deep link** en Cotizaciones | `QuoteController::show()` devuelve `response()->json($quote)`: entrar por URL da JSON crudo. Pedidos sí tiene `pushState`/`popstate` (`orders/index` 478-504) |
| B9 | `convert` sin gemela en bixosales y sin dual A/B | `routes/web.php:472`, solo panel, `can:quotes.editar` puro |
| B10 | La vista **no recibe `$puede`** | `QuoteController::index` no lo pasa; `OrderController` sí, vía `OrderAbilities` |

### 0.2 Severidad de B5/B6 — está pasando ahora

Tras el backfill hay **5 cotizaciones `converted` con token** en el proyecto
18. Si un cliente abre hoy su enlace, ve **"Borrador"** y botones para
aceptar o rechazar algo que ya se convirtió en pedido; al pulsar, el backend
responde "ya procesada". Es un bug **de cara al cliente, activo en producción**.
Es anterior a F1b (esos estados ya existían), pero F1c debe cerrarlo.

---

## 1. Estado visual, medido

### 1.1 Editor en escritorio (1440×900)

| Medida | Valor |
|---|---|
| Filas de producto | 3 × **49 px**: 312→361, 361→410, 410→**459** |
| Tarjeta CLIENTE | `top` **445**, alto 62 |
| **Solape** | **14 px** |
| Claves crudas visibles | **`draft`, `sent`, `accepted`, `rejected`** |
| Botones de conversión | **0** |
| Relación visible con el pedido | **ninguna** |

El solape se midió dos veces. **No lo causa F1b**: ocultando en vivo los 3
inputs de descuento, filas (49 px) y contenedor (214 px) no cambian.

### 1.2 Editor en móvil (390×844)

| Medida | Valor |
|---|---|
| Overflow horizontal de página | **0 px** |
| Ancho tabla / contenedor | **581 / 292 px** |
| **Scroll interno** | **289 px** |
| Interactivos < 44 px de alto | **57 de 69 (83 %)** |

Y por observación: **la lista se apila sobre el detalle**; hay que recorrer las
7 cotizaciones para llegar a la abierta. Los chips de filtro se cortan.

### 1.3 Portal en móvil

`0 px` de overflow, **0 importes cortados** — mejor que el panel tras 6b. Falla
la fecha: *"11 de **August**, 2026"*.

### 1.4 Formato desalineado panel↔portal (lo causó 6b)

Portal: `S/ 19,345.50`. Panel: **`S/ 19345.50`**. Corregimos el lado del
cliente y dejamos el del vendedor.

---

## 2. Ciclo de vida: la matriz que gobierna el servidor

Principio: **la UI no es una barrera**. Cada regla se aplica en el servidor y
se prueba; el botón solo refleja lo que el servidor ya garantiza.

| Acción | draft | sent | accepted | rejected | **converted** |
|---|---|---|---|---|---|
| `convert()` crear pedido | 422 | 422 | **crea** | 422 | idempotente: mismo `order_id`, `already:true` |
| `update` / `updateFull` → `converted` | 422 | 422 | 422 | 422 | — |
| `updateFull` (cliente/líneas/total) | sí | sí | sí | sí | **422** |
| Cambio de estado comercial | sí | sí | sí | sí | **422** |
| `send()` / reenviar | sí | sí | sí | sí | **422** |
| `destroy()` | sí | sí | sí | sí | **422** |
| `duplicate()` | sí | sí | sí | sí | **sí, como `draft`** |
| Portal: Aceptar / Rechazar | — | sí | — | — | **no (read-only)** |
| Portal: subir comprobante | — | — | sí | — | **sí** (continuidad) |

Reglas adicionales:

- **Precedencia**: la rama idempotente de `converted` y la reparación
  FK↔estado se evalúan **antes** que el guard de `accepted`. Una convertida
  nunca debe recibir 422: debe devolver su pedido.
- **Módulo**: sin módulo `orders` activo, `convert()` responde **422 en el
  servidor**, no solo botón oculto.
- **Qué sigue editable en una convertida** (explícito, para no bloquear de
  más): notas internas y `payment_status` (F2/F3 lo gobernarán). **No**
  editables: cliente, líneas, totales, estado comercial, validez, token.
- **`converted` no se asigna a mano**: se llega solo por `convert()`.

---

## 3. Portal público

1. **`converted` en `$statusMap`** con etiqueta **"Convertida"** propia, no el
   fallback a "Borrador".
2. **Modo procesado**: sin Aceptar/Rechazar; mensaje claro de que la
   cotización ya fue tramitada.
3. **Continuidad de comprobante**: `proof()` acepta `accepted|converted` por
   **estado canónico** (`QuoteStatus::comercial`), no comparación cruda. Sigue
   registrando su evento como hoy; **no toca pedido ni pago contable** (F3), y
   se prueba que no altera la relación quote↔order.
4. **Fecha en español explícita**, formateada en el punto de uso; **sin
   `setlocale` ni efectos globales** que puedan alterar otras vistas o el
   formateo numérico.
5. El portal **no ofrece conversión** (decisión mantenida, §7).

---

## 4. Rutas y permisos

```php
// NUEVA, nombre inequívoco (evita confundirla con la fiscal)
POST /bixosales/cotizaciones/{quote}/convertir-pedido
     -> QuoteController@convert
     middleware: project.can:quotes.editar|manage-quotes  (+ module:quotes)
```

- **No se toca** la ruta fiscal `/f/{slug}/cotizaciones/{id}/convertir` →
  `convertirPortal()` (F4). No hay colisión de URI, pero sí de concepto: por
  eso el sufijo `-pedido`.
- La vista genera la URL **por nombre de ruta según `portalLayout`**, nunca
  concatenando cadenas.
- `QuoteAbilities::para(?User $user, ?Project $project): array` — recibe
  también el proyecto y resuelve **una sola clave** `convertir`
  (permiso OR **y** módulo `orders`). **Sin `$puedeConvertir` aparte**: dos
  fuentes de verdad se desincronizan.

---

## 5. Deep links (paridad con Pedidos)

- `QuoteController::show`: **JSON** si la petición es AJAX/espera JSON;
  navegación HTML devuelve `index()` con **`cotizacionInicial`**, igual que
  Pedidos, conservando el cierre multiproyecto (403 cross-project).
- Abrir/cerrar actualiza **URL e historial** (`pushState`/`replaceState`);
  Atrás/Adelante restauran selección, filtros y scroll; al cerrar, **el foco
  vuelve a la fila de origen**.
- Los enlaces PED↔COT usan las rutas `*.show` exactas, nunca una lista
  genérica.
- Se prueban: entrada directa, refresh, `popstate`, ID inexistente (404) y
  cross-project (403).

---

## 6. Accesibilidad y responsive (criterios endurecidos)

- **44×44 px para todo control táctil independiente**, no solo los CTA, con
  **≥ 8 px de separación**. Única excepción admitida: enlaces en línea dentro
  de un párrafo, **documentada uno por uno**.
- Breakpoints probados: **320, 375, 390, 768, 1024, 1440** y **móvil
  apaisado**. Cero scroll horizontal y **cero scroll anidado**.
- Inputs con tamaño de fuente que **no dispare zoom automático** en iOS.
- Maestro-detalle con **URL, historial y foco**, no un simple `x-show`.
- Altura con **`dvh`** y espacio reservado para cabeceras/acciones fijas.
- **Modal Convertir**: `role="dialog"`, `aria-modal="true"`, nombre y
  descripción asociados, foco inicial en un destino seguro (**no** en el botón
  destructivo), trampa de foco, cierre con Escape y con Cancelar, y
  restauración del foco al disparador.
- Botón: `disabled` **real** durante el envío + `aria-busy`; éxito anunciado
  con `aria-live="polite"`; error con `role="alert"` y camino de recuperación.
- El estado **nunca se comunica solo por color**; foco visible; contraste
  **4.5:1** en texto y **3:1** en componentes; orden de teclado coherente;
  respeto a `prefers-reduced-motion`.

---

## 7. Decisión: el portal **no** convierte

**NO en F1c.** El portal ya permite **aceptar**; convertir en pedido es una
decisión comercial del vendedor. Cambiar de manos esa decisión requiere
evidencia de negocio y una autorización propia.

## 8. Fuera de alcance

`LineMath` y su espejo BigInt (salvo añadir el presentador JS), IGV/SUNAT (F4),
CxC y pagos contables (F2/F3), y **ningún estado de negocio nuevo**.

---

## 9. Dinero y pruebas sin mutar producción

- El panel usará un **presentador JS sobre string/centavos BigInt**
  (`lmPresent`, espejo de `LineMath::present`), **nunca**
  `parseFloat(...).toFixed()` para importes críticos. Casos probados: `89.99`,
  `1,000.00`, `19,345.50`, `99,999,999.99`.
- **"Byte a byte" queda retirado** como criterio para PDF/canvas: sus
  metadatos y marcas de tiempo lo hacen inestable y daría falsos rojos. Se
  sustituye por **comparación semántica normalizada** (textos e importes
  extraídos y normalizados) **más** captura visual del documento.
- **Criterio de datos reformulado** (la v1 se contradecía: "ningún cambio de
  datos" es incompatible con probar la conversión): *F1c no introduce
  migraciones ni backfill; las mutaciones ocurren **solo en BD local aislada y
  revertible**.* El **QA productivo de F1d es read-only: no se crean pedidos
  reales** para probar el botón.

---

## 10. Criterios de aceptación v2

**Ciclo de vida (servidor).** 1) `draft/sent/rejected` → `convert` = **422**.
2) `accepted` → crea **un** pedido. 3) `converted` → mismo `order_id`,
`already:true`. 4) Sin módulo `orders` → 422 aunque se llame por URL.
5) `update`/`updateFull` con destino `converted` → 422. 6) Sobre una
convertida: editar líneas/cliente/total, reenviar, borrar o cambiar estado →
**422 sin variar** quote, order ni items. 7) `duplicate` de una convertida →
nueva en `draft`.

**Portal.** 8) `converted` se presenta como **"Convertida"**. 9) Sin
Aceptar/Rechazar. 10) Comprobante admitido en `accepted|converted`, sin alterar
la relación quote↔order. 11) Fecha "11 de agosto de 2026", sin locale global.

**Rutas y permisos.** 12) Tabla de rutas: fiscal→`convertirPortal`,
pedido→`convert`. 13) Universo B y legacy A pueden convertir; sin permiso
**403**; cross-project **403**.

**Deep links.** 14) Entrada directa a `/bixosales/cotizaciones/{id}` abre la
interfaz con esa cotización. 15) Atrás/Adelante restauran selección y foco.
16) ID inexistente 404; de otro proyecto 403.

**Visual y accesibilidad.** 17) `tarjetaCliente.top ≥ ultimaFila.bottom` con
1/3/10/50 líneas. 18) `scrollInternoTablaPx = 0` en 320-390 px sin perder
ningún dato. 19) Detalle abierto en móvil: la lista no ocupa altura.
20) Claves crudas visibles = **0**. 21) **Todos** los controles táctiles
≥ 44×44 con ≥ 8 px, salvo excepciones documentadas. 22) Modal accesible
completo (dialog, trap, Escape, foco restaurado). 23) Contraste 4.5:1 / 3:1 y
estado no comunicado solo por color. 24) Sin scroll horizontal ni anidado en
los 6 breakpoints y en apaisado.

**Integridad.** 25) Mismo importe idéntico en lista, editor, portal y
exportadores. 26) `33.33×3@10 = 89.99` y `3,869.10×5 = 19,345.50` intactos.
27) Exportadores: comparación semántica normalizada idéntica antes/después.
28) Sin migraciones ni backfill; mutaciones solo en BD local.

---

## 11. Matriz de pruebas

**PHPUnit — ampliados (no "conservados"):**
`QuoteConversionTest` **se modifica y amplía** con la matriz del §2 (guard de
`accepted`, módulo apagado, mutaciones prohibidas sobre convertida, duplicate).
`QuotesVocabularyTest` suma portal `converted` y fecha en español.

**PHPUnit — nuevos:** `QuoteAbilitiesTest` (5+1 acciones × A/B/ninguno,
superadmin, dueño, con y sin módulo) · `QuotesUiContractTest` (la vista recibe
`$puede`; botón solo con permiso+estado+módulo; cero claves crudas) ·
`QuoteRoutesTest` (tabla de rutas, 403s) · `QuoteDeepLinkTest`
(`show` dual, `cotizacionInicial`, 404/403) · `PortalQuoteConvertedTest`
(presentación, sin Aceptar/Rechazar, comprobante en `converted`).

**Playwright F1c:** geometría (1/3/10/50 líneas × 6 breakpoints + apaisado) ·
conversión con doble clic (un solo pedido) · modal accesible y teclado ·
deep links con Atrás/Adelante · permisos con dos usuarios · errores 422/409 ·
formato en las cuatro superficies · portal convertido.

**F1d — cierre:** 8 capturas baseline, contraste medido, revisión visual
crítica (mirar, no "pasa el test") y **QA productivo read-only**.

---

## 12. Manifiesto de archivos v2

| # | Archivo | Acción | Nuevo en v2 |
|---|---|---|---|
| 1 | `app/Support/QuoteAbilities.php` | crear (con `$project` y clave `convertir`) | ajustado |
| 2 | `app/Http/Controllers/QuoteController.php` | `index` pasa `$puede`; **guards de ciclo de vida** en `convert`/`update`/`updateFull`/`send`/`destroy`; `show` dual | **ampliado** |
| 3 | `app/Http/Controllers/PortalController.php` | `proof()` acepta `accepted|converted` por estado canónico | **nuevo** |
| 4 | `routes/web.php` | `POST /bixosales/cotizaciones/{quote}/convertir-pedido` | ajustado |
| 5 | `resources/views/quotes/index.blade.php` | orquestador + historial/URL | — |
| 6 | `resources/views/quotes/_lista.blade.php` | crear | — |
| 7 | `resources/views/quotes/_detalle.blade.php` | crear (fichas móviles, fin del solape) | — |
| 8 | `resources/views/quotes/_acciones.blade.php` | crear (Convertir + modal accesible) | — |
| 9 | `resources/views/public/portal-quote.blade.php` | `converted`, modo procesado, fecha | **ampliado** |
| 10 | `resources/views/orders/_drawer.blade.php` | "Originada en COT-y" enlazado | — |
| 11 | `tests/Feature/QuoteConversionTest.php` | **modificar y ampliar** | **nuevo** |
| 12 | `tests/Feature/QuotesVocabularyTest.php` | ampliar | **nuevo** |
| 13 | `tests/Unit/QuoteAbilitiesTest.php` | crear | — |
| 14 | `tests/Feature/QuotesUiContractTest.php` | crear | — |
| 15 | `tests/Feature/QuoteRoutesTest.php` | crear | **nuevo** |
| 16 | `tests/Feature/QuoteDeepLinkTest.php` | crear | **nuevo** |
| 17 | `tests/Feature/PortalQuoteConvertedTest.php` | crear | **nuevo** |
| 18 | `qa-avan/f1c-*.spec.js` | crear | — |

**Sin migraciones. Sin backfill. Sin tocar `convert()` en su núcleo
transaccional** (solo se le antepone el guard de estado/módulo, respetando la
precedencia de la rama idempotente).

---

## 13. Imprescindible F1c vs futuro

**Imprescindible:** matriz de ciclo de vida en servidor · portal `converted`
correcto y comprobante continuo · ruta inequívoca y permisos duales ·
`QuoteAbilities` + `$puede` · botón Convertir gobernado con modal accesible ·
deep links con historial y foco · solape · móvil sin scroll interno ·
maestro-detalle · vocabulario en español · formato único · 44×44 con 8 px ·
errores visibles.

**Futuro (registrado, no bloquea):** rediseño gráfico completo · filtros
avanzados · previsualización de PDF en panel · plantillas · historial de
cambios · sistema de notificaciones común que sustituya al `alert()`.

---

## 14. Riesgos

1. **Troceo de `quotes/index.blade.php`** (vista + editor + 3 exportadores +
   espejo BigInt). Mitigación: mover los exportadores **sin editar una línea de
   su lógica** y comparar su salida **semánticamente normalizada** antes y
   después, más captura visual.
2. **Guards de ciclo de vida sobre datos vivos**: hay 5 convertidas reales. Un
   guard mal ordenado las dejaría en 422 en vez de devolver su pedido. Por eso
   la precedencia del §2 es explícita y se prueba **primero**.
3. **`proof()` en `converted`**: amplía permiso, no lo restringe; se prueba que
   no toca pedido ni pago contable, que siguen siendo F3.
