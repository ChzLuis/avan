# BIXO Mini-ERP — Roadmap maestro

**Fecha:** 2026-08-15 · **v2** tras auditoría de Codex · **Tipo:** auditoría
contrastada contra código y esquema reales. **Ningún cambio de código.**
Convención: se distingue **working tree** (árbol local) de **ARIN**
(producción); cuando no se indica, ambos coinciden.
**Mantenimiento:** actualizar tras cerrar cada módulo.

Estados: `NO INICIADO · AUDITORÍA · DISEÑO · IMPLEMENTACIÓN · QA · CERRADO`

---

## 1. Estado global

| Módulo | Clasificación | Estado roadmap |
|---|---|---|
| Base (seguridad, drawer, RBAC portal) | EXISTE | **CERRADO** |
| Pedidos / Ventas | EXISTE | **CERRADO** (2026-08-15) |
| Cotizaciones | EXISTE | **CERRADO (2026-08-16)** — F1a+F1b+F1c desplegadas en producción y F1d verificada (QA read-only 6/6 + baseline) |
| Clientes | PARCIAL | NO INICIADO |
| Cuentas por cobrar | EXISTE | **CERRADO (2026-08-16)** — F2 v1 + F2b: libro `payments` inmutable, `receivable_terms` con vencimiento real, condiciones configurables por negocio y revisión visual. **Alcance:** saber qué se debe y desde cuándo. **Registrar** cobros desde POS/bot es F3 Pagos |
| Pagos | EXISTE | **CERRADO (2026-08-16)** — F3a/b/c: las cuatro puertas (panel, bot, extensión, POS) registran en `Ledger`; reversión con motivo, idempotencia en reintentos del bot e historial por cobro. **Falta la verificación viva** de la primera aprobación real del bot |
| Facturación / SUNAT | PARCIAL | NO INICIADO |
| Operación / Entregas | PARCIAL | NO INICIADO |
| Inventario | **PARCIAL con Kardex/movimientos** | NO INICIADO |
| Clientes 360 | NO EXISTE | NO INICIADO |
| Dashboard | PARCIAL | NO INICIADO |
| Compras / CxP | NO EXISTE (salvo Proveedores básico) | FUTURO |

### 1.1 Estado visual / UX

El roadmap maestro medía arquitectura y funcionalidad; el trabajo visual
quedaba enterrado dentro de las fases. Esta tabla lo hace visible. **Estado
funcional y estado visual son independientes**: Pedidos está funcionalmente
cerrado y visualmente parcial. Nada se marca "cerrado visual" por inferencia.

| Área / pantallas | Estado funcional | Estado visual | Próximo hito | Evidencia |
|---|---|---|---|---|
| Base / shell (sidebar, topbar, drawer global) | CERRADO | **NO MEDIDO** | UX1 | 8 tokens en `app.css`; 102 hex en `layouts/app`, 44 en `sidebar-bixo` |
| Pedidos / Ventas | CERRADO (2026-08-15) | **CONFORME EN PRODUCCIÓN — medido con contratos (2026-08-16)** | UX1 (shell) y estados vacío/carga | Paso 2 rediseñó lista+drawer con 8 capturas baseline; F1b/6b destapó `.toFixed` sobre string y labels crudos en su drawer |
| **Cotizaciones** | **CERRADO (2026-08-16)** | **CONFORME EN PRODUCCIÓN — medido con contratos** | mantenimiento (UX2 sigue con Pedidos/Clientes) | [F1c](modulo1-f1c-expediente.md): 141 tests / 496 aserciones · Playwright **18/18** · 7 breakpoints con solape 0, scroll interno 0, táctiles <44 = 0, separaciones <8 px = 0, claves crudas 0 (los defectos que abrieron F1c quedaron resueltos y convertidos en contratos) |
| Clientes | PARCIAL | **CONFORME — medido con contratos (2026-08-16)** | estados vacío/carga | seguridad granular por acción + bug Alpine de fábrica corregido |
| Cuentas por cobrar | **v1 LECTURA en producción** | **CONFORME EN PRODUCCIÓN — medido con contratos (2026-08-16)** | F2b (entidad contable) | 0 fallos AA, foco visible 24/25, estado vacío diseñado; cifra contrastada con SQL independiente: 45 196,60 en 11 documentos |
| Pagos | PARCIAL funcional | **NO MEDIDO** | UX3 | vocabulario de pagos ya canonizado (F1a) |
| Facturación / SUNAT | PARCIAL | **NO MEDIDO** | UX3 | — |
| Operación / Entregas | PARCIAL | **NO MEDIDO** | UX4 | `orders/kitchen` con 40 hex propios |
| Inventario / Kardex | PARCIAL | **NO MEDIDO** | UX4 | — |
| Dashboard / KPIs | PARCIAL | **NO MEDIDO** | UX5 | — |
| Portal de cotización | CERRADO | **CONFORME EN PRODUCCIÓN — medido** | UX6 (catálogo y documentos) | 0 overflow · 0 importes cortados · fecha en español · `converted` con estado propio y comprobante activo — verificado en LOCAL (Playwright 4/4); producción aún con la versión 6b |
| Catálogo / tienda pública | EXISTE | **NO MEDIDO (fuera del ERP)** | UX6 | constructor de tiendas, frente aparte |
| Documentos (PDF, ticket, boleta) | EXISTE | **NO MEDIDO** | UX6 | 3 exportadores en `quotes/index` |

#### Los tres indicadores, con su fórmula

1. **Avance funcional** = módulos cerrados ÷ módulos del alcance.
   **5 de 12 = 42 %** (Base, Pedidos, Cotizaciones, **Cuentas por cobrar** y **Pagos**,
   cerrados el 2026-08-16 con F2b y F3). Se cuenta cerrada por su alcance —saber
   qué se debe y desde cuándo—; el libro ya existe pero **nada escribe en él
   salvo el backfill**: los flujos de cobro son F3 y no se contabilizan aquí.
2. **Avance visual/UX** = áreas con **DoD cumplido y verificado** ÷ áreas con
   UI. **5 de 12 = 42 %** (Cotizaciones, Pedidos, Clientes, portal de
   cotización y **Cuentas por cobrar**, incorporada el 2026-08-16 con
   contraste AA a 0 fallos y teclado verificados en ARIN).
3. **Avance global ponderado** (60 % funcional + 40 % visual) = **42 %**
   (0,6 × 42 + 0,4 × 42). El funcional NO se movió: la tanda del 2026-08-16
   fue de correccion y deuda —CxC ocultaba S/ 8 298, el KPI de WhatsApp
   marcaba 0 teniendo 3, `/admin/*` estaba caído por un alias ausente—, no
   de módulos nuevos. Sube la fiabilidad, no el alcance.
   El shell aporta 15 controles <44 px a TODA página (deuda UX1 cuantificada);
   Cocina es el área más lejana del DoD (12 táctiles + 16 emojis).

El plan visual completo vive en **[bixo-ux-roadmap.md](bixo-ux-roadmap.md)**.

---

## 2. Matriz de trazabilidad objetivo vs real

`Cliente → Cotización → Pedido → CxC → Pago → Factura → Entrega`

| Enlace | ¿Existe hoy? | Evidencia |
|---|---|---|
| Cliente → Cotización | ✅ | `quotes.client_id` |
| Cliente → Pedido | ✅ | `orders.client_id` |
| **Cotización → Pedido** | ✅ **RESUELTO (F1b, 2026-08-15)** | **`orders.quote_id`** nullable con FK `nullOnDelete` e índice **UNIQUE**: relación 1:0..1 garantizada por esquema. Los 5 históricos quedaron vinculados por backfill CAS transaccional (26→25, 29→27, 32→28, 33→29, 34→30). `order_events.quote_id` se conserva como bitácora, ya no como sustituto de la FK. Evidencia: [manifiesto APLICADO](backfill-quote-id-manifest-aplicable.json) |
| Pedido → CxC | ❌ | no existe entidad CxC |
| CxC → Pago | ❌ | no existen ninguna de las dos |
| Cotización → Factura | ✅ | `invoices.quote_id` (bigint, indexado); lo escribe el portal de facturación (`QuoteController:424`) |
| Pedido → Factura | ✅ | `invoices.order_id` (bigint, indexado) |
| Factura → SUNAT | ✅ embebido | `invoices.sunat_status/hash/cdr/ticket/error/sent_at` — estado en la fila, sin expediente |
| Pedido → Entrega | ⚠️ embebido | `orders.delivery_status/person/dispatched_at/...` — campos en la fila, sin entidad de despacho |
| Pago → cualquier doc | ❌ | campos (`payment_status`, `paid_amount`) + evento con `meta` JSON estructurado, pero **sin entidad de pago conciliable ni FK canónica de transacción** |

**Hallazgo crítico #1 — RESUELTO en F1b (2026-08-15).** El eslabón central
Cotización→Pedido era el único del tramo comercial sin columna. Hoy existe
`orders.quote_id` (nullable, FK `nullOnDelete`, UNIQUE) y los cinco históricos
están vinculados. Queda como referencia de cómo se cerró, no como pendiente.

---

## 3. Módulos — evidencia por módulo

### 3.1 Pedidos / Ventas — EXISTE · **CERRADO**

Cerrado por dictamen de Codex (ver canal). `Order`/`OrderItem`/`OrderEvent`,
tablas `orders`/`order_items`/`order_events`, `OrderController` +
`WaBotController` (operativo), rutas panel + portal protegidas
(`project.can:` con OR A/B), vista `orders/index` + `_drawer`/`_fila`/
`_filtros`/`_filtros-campos`, soportes `OrderStatus`/`OrderAbilities`/`OrderFlow`,
capacidad operativa por módulo `logistics` (TECSIST apagado). Pruebas: 79
PHPUnit + 54 Playwright. Deuda registrada: pedidos 20-22 con `status='pagado'`
(pendiente decisión de usuario), iconografía documental (emojis) unificable.

### 3.2 Cotizaciones — CERRADO (2026-08-16): F1a+F1b+F1c en producción, F1d verificada

- **Modelos/tablas:** `Quote`, `QuoteItem` → `quotes`, `quote_items`.
  `quotes` tiene `valid_until`, `sent_at`, `seen_at`, `paid_amount`,
  `payment_proof_url`, `rejected_at`, `reject_reason`, `token` (enlace público).
- **Controlador:** `QuoteController` — CRUD, `updateFull`, `send`, `duplicate`,
  `markSeen`, `convert` (panel) + portal completo (`*Portal`) y emisión de
  factura desde cotización.
- **Rutas:** panel `/quotes*` y portal `/bixosales/cotizaciones*`, todas con
  permiso desde el Paso 0.
- **Vista:** `quotes/index.blade.php` (maestro-detalle **pendiente de
  rediseño**: es justo el objeto de F1c).
- **Columnas y trazabilidad (F1b, ya en producción desde 2026-08-15):**
  `quote_items.discount` y `order_items.discount` `decimal(5,2) NOT NULL
  DEFAULT 0.00`; `orders.quote_id` nullable con FK `nullOnDelete` + índice
  **UNIQUE** (relación 1:0..1 garantizada por esquema, no por convención);
  `quotes.payment_status` con DEFAULT canónico `pending` (las 16 filas
  históricas siguen en `pendiente` y las canoniza `QuoteStatus` en lectura).
- **Dinero exacto:** `App\Support\LineMath` — centavos + basis points, half-up
  entero, salida string `X.YY`, y `present()` para separadores **sin float**.
  Espejo JS con **BigInt** en editor, exportadores y documentos de Pedidos.
- **Conversión:** `convert()` transaccional e idempotente (`lockForUpdate`,
  contrato `{ok, order_id, already}`), con resolución de vínculos legacy por
  evento único y captura de colisión de UNIQUE fuera de la transacción.
- **Deuda ya documentada** ([propuesta-cotizaciones-pedidos.md](propuesta-cotizaciones-pedidos.md)):
  🔴 `get igv() { return this.subtotal * 0; }` (IGV en cero en la UI);
  ✅ `quote_items.discount` **RESUELTO por F1b**: la columna existe
  (`decimal(5,2) NOT NULL DEFAULT 0.00`), el descuento persiste y viaja
  canónico en el payload. ✅ Vigencia: `QuoteStatus::vencida()` la deriva de
  `valid_until` (F1a) y el portal la muestra. 🔴 Sigue pendiente: autosave.
- **Estados hoy:** `draft / sent / accepted / rejected / converted` (+
  `seen_at` como marca, no estado). "Vencida" es derivable de `valid_until`;
  "en negociación" NO existe.
- **Documentos:** PDF/imagen/ticket existen para PEDIDOS; para cotizaciones
  hay portal público por `token` — el detalle de formatos queda para la
  auditoría del módulo (no auditado a fondo aún).

### 3.3 Clientes — PARCIAL

`Client` → `clients`, `ClientController` (CRUD portal), permisos `clients.*`
(+ legacy `view/manage-clients`), módulo `clients`. Sin ficha 360, sin
consolidación comercial/financiera.

### 3.4 Cuentas por cobrar — NO EXISTE

No hay tablas `receivables` ni `installments`/cuotas (SHOW TABLES). Lo más
cercano: `orders.payment_status/advance_amount`, `quotes.paid_amount`, y el
seguimiento de pago de `proposals`. Precisión (corregida): `Proposal` **sí
tiene `project_id`**; es una **vertical comercial separada** — proformas de
ESKALA para vender su solución — que no forma parte de la cadena transaccional
Cotización→Pedido→CxC del mini-ERP de cada negocio (decisión de Codex: fuera
de la cadena; no reutilizar como CxC). Módulo a diseñar desde cero
(modelo, cuotas, vencimientos) según los puntos 8-9 y 12 de la dirección.

### 3.5 Pagos — PARCIAL funcional, SIN entidad contable

Dos dimensiones que no deben confundirse:

**Capacidad operativa (existe):** `Api\PagoController` opera sobre **`Order`**
(corrección: NO sobre `rifa_ventas`): lista pedidos con
`payment_status='en_revision'` (Yape/Plin reportados por bot/extensión) y los
aprueba o rechaza. Rutas `/bixosales/pagos/pendientes|aprobar|rechazar`,
permisos `payments.ver/aprobar/rechazar` (Paso 0). Además: registro de pago en
el drawer de Pedidos (`OrderController@pay`) y `cajas`+`caja_movimientos`
(tesorería de sesión).

**Entidad contable (no existe):** sin tabla `payments`. Un pago es mutación de
campos + `OrderEvent::log('payment_registered', ...)` en JSON: no enlazable a
cuotas, no conciliable, no reversible.

🔴 **Hallazgo (verificado 2026-08-15, CERRADO 2026-08-16 — ver abajo):** `PagoController@aprobar` (líneas 66-67)
escribe `payment_status='pagado'` **y `status='pagado'`** — es el **origen
activo** de la deuda legacy de los pedidos 20-22: no es dato histórico, se
sigue generando con cada aprobación. `rechazar` escribe
`payment_status='pendiente'` (también fuera del contrato
`in:paid,partial,pending,...` que valida `OrderController@pay`). Y
`'en_revision'` es un estado de pago más que `OrderStatus` aún no reconoce
como sinónimo. Dirección de Codex: en la fase Pagos este controlador se
integra como **adaptador legacy de entrada** que cree una transacción
conciliable — sin copiar su mutación actual.

**Estabilización DESPLEGADA Y VERIFICADA (2026-08-16).** El documento decía
"pendiente de despliegue" y estaba obsoleto: los cuatro archivos del hotfix
tienen el mismo SHA-256 en local y en ARIN, y el archivo vivo escribe
`payment_status = 'paid'` sin tocar el `status` comercial. Los pedidos con
estado comercial legacy siguen siendo **exactamente 3** (20, 21 y 22): la fuga
está cerrada, no se generan nuevos. Consumidores verificados uno a uno —el bot
y bixo-baileys no comparan literales de estado (solo texto para el cliente), y
la extensión envía un booleano `pagado` y pinta id/método/fecha/total, nunca el
estado—, y `pendientes` acepta `under_review` + alias `en_revision`, así que
ningún pedido en vuelo se perdió. Contenido del hotfix:
el canónico de revisión es **`under_review`** (`en_revision` queda como alias
de solo lectura en `OrderStatus`); aprobar/rechazar ya no tocan el `status`
comercial y registran `OrderEvent('payment_status', {from,to,source[,motivo]})`;
bot y extensión emiten `pending` comercial y `under_review|paid|pending` de pago.

**Deuda adicional de la fase Pagos:** el `PaymentController` del catálogo
público maneja estados de pasarela propios (`pending_payment` y estados
externos). No genera `status='pagado'`; se unificará en la fase Pagos.

### 3.6 Facturación / SUNAT — PARCIAL

`Invoice` → `invoices` con `serie`, `correlativo`, `numero`, `type`,
`subtotal/igv/total`, `igv_included`, y **enlaces `order_id` + `quote_id`**.
Campos SUNAT completos en la tabla. `InvoiceController` + `sendSunat` (rutas
panel y portal, protegidas).

**F4a RESUELTA (2026-08-16).** `invoice_items` ya tiene `discount`; el cálculo
fiscal pasó de flotantes a **centavos enteros** en `InvoiceController` y en
`convertirPortal`, y el total del comprobante es la suma de sus líneas. Se
retiró el gate 422 de F1b: bloqueaba **convertir**, o sea cerrar la venta, no
solo emitir. Además `convertirPortal` ignoraba el descuento al armar las líneas
—habría facturado precio de lista— y eso también quedó corregido.
Contratos: `FacturacionDescuentoTest` (4) + `QuoteConversionTest` replanteado.

**Hallazgo que reencuadra la fase:** en ARIN hay **0 facturas emitidas** y
**ningún proyecto tiene proveedor fiscal configurado** (solo `ruc`; ningún
`apisperu_token`), pese a que la pantalla de configuración existe. El módulo no
está roto: está **sin adoptar**. Avanzar en NC/ND o `FiscalProvider` sería
construir sobre algo que nadie usa todavía.

**Sigue pendiente:** NC/ND, expediente del documento, centro fiscal,
`FiscalProvider` desacoplado, y la **verificación real contra SUNAT** — que
exige un token configurado y enviar un documento irreversible.

### 3.7 Operación / Entregas — PARCIAL

`OrderFlow` (flujos por rubro: lavandería 10 estados, restaurante 7, retail 5)
+ editor por proyecto (`project_settings.order_flow`,
`settings/partials/flow-editor.blade.php`) + capacidad por módulo `logistics`
(frontera nueva del Paso 2). `DeliveryController`, `kitchen`, campos
`delivery_*` en `orders`. Mapa operativo aparte (`operational_maps/objects/
events/requests`, permisos `mapa.*`). Sin entidad de despacho/traslado (GRE
futuro la necesitará).

### 3.8 Inventario — PARCIAL con Kardex/movimientos (corregido)

Mi v1 decía "stock plano sin movimientos": **falso**. Verificado:

- `app/Models/InventoryMovement.php`, `app/Support/InventoryLedger.php`
  (punto transaccional del Kardex), `app/Http/Controllers/InventoryController.php`.
- Vistas `inventory/index` y `inventory/kardex`.
- Rutas: `/inventario`, `/inventario/movimiento`,
  `/inventario/{product}/kardex` ([routes/web.php:281-283](../../routes/web.php)).
- **En ARIN**: tabla `inventory_movements` desplegada con esquema auditable
  completo (`type`, `reason`, `quantity`, `unit_cost`, `balance_after`,
  `reference_type/id`, `user_id`).

⚠️ Discrepancia de frontera: las rutas usan `module:catalog` +
`can:catalog.ver/editar`, **no** el módulo `inventory` ni los permisos
`inventory.ver/editar` (que existen en el catálogo pero nadie consume).
Alinear módulo/permiso queda para la fase de Inventario. Faltan: almacenes,
reservas, stock mínimo.

### 3.9 Dashboard — PARCIAL

`Comercial/DashboardController` + `comercial/dashboard.blade.php`. Deuda:
todavía referencia "BIXO Score" (2 menciones; el layout ya se limpió en el
Paso 2). Rediseño accionable: Fase 8.

### 3.10 Compras / CxP — NO EXISTE (Proveedores básico sí)

`proveedores` (tabla + módulo + permisos `proveedores.ver/editar`). Sin
solicitudes, OC, recepciones ni cuentas por pagar. FUTURO por dirección.

### 3.11 Capas transversales

- **Eventos:** `order_events` (patrón `OrderEvent::log`) — solo pedidos.
  `operational_events`, `sales_interactions` aislados. No hay capa unificada
  `quote.sent` / `payment.confirmed`… (punto 5 de la dirección): a introducir
  gradualmente desde Cotizaciones.
- **Notificaciones:** drawer Alertas/Pendientes/Actividad (Paso 1, CERRADO)
  listo para recibir eventos de módulos.
- **Módulos activables:** `modules` + `project_modules` + `hasModule()` + UI en
  Ajustes → **la arquitectura modular por proyecto ya existe** y quedó probada
  con `logistics`.
- **Permisos:** universos A (legacy `view-*/manage-*`) y B (`dominio.accion`)
  conviven con OR en `project.can:` — unificación pendiente, documentada en
  [rbac-produccion-arin.md](rbac-produccion-arin.md).
- **Multiproyecto:** `HasProjectScope` + `abort_unless(project_id)` en Order y
  Quote; toda entidad nueva (receivable, installment, payment, shipment) nace
  con `project_id` y el mismo doble cierre.

---

## 4. Dependencias y orden

```
F1 Cotizaciones ──► F2 CxC ──► F3 Pagos ──► F4 Facturación ──► F5 Operación
      │                └── el plan de cuotas lo consumirá Facturación (no recapturar)
      └── orders.quote_id: RESUELTO en F1b (migrado, backfilled y cerrado)
F6 Clientes 360 (consume F1-F5) · F7 Inventario · F8 Dashboard · F9 SIRE · F10 Compras · F11 GRE
```

## 5. Decisiones tomadas (Codex, 2026-08-15)

1. **`orders.quote_id`**: **CERRADO en F1b (2026-08-15)** — FK nullable con
   `nullOnDelete` + UNIQUE migrada en ARIN (batch 26), `convert()`
   transaccional idempotente desplegado y backfill de los 5 históricos
   aplicado con manifiesto CAS. Ya no es una decisión pendiente.
2. **`proposals`**: fuera de la cadena mini-ERP; vertical comercial separada.
3. **`Api\PagoController`**: en la fase Pagos se integra como **adaptador
   legacy de entrada** (crea transacción conciliable + proyección del pedido);
   no se copia su mutación actual.
4. **IGV**: el cálculo fiscal NO se toca en Fase 1; la auditoría documenta el
   `subtotal*0` y propone cómo evitar una UI engañosa. El **descuento** que la
   UI captura y la base no persiste sí es deuda de integridad de Cotizaciones
   y puede proponerse en Fase 1, sujeta a diseño/aprobación.
