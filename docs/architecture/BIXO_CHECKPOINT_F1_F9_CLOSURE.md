# Cierre del checkpoint F1–F9 — plan de corrección

> Estado PRE-corrección: `BIXO_VALIDACION_ARQUITECTONICA.md` (no se sobrescribe).
> Este documento es el plan de cierre de los hallazgos verificados. La
> arquitectura acordada NO cambia; estas son correcciones de incumplimientos.
> Regla vigente: **NO-GO para F10** hasta que los 9 bloques de seguridad/
> integridad estén en PASS (Pricing admite PASS WITH DEBT documentado).
>
> Principio guía: una venta, un pago, un movimiento de stock, un cliente y un
> comprobante deben producir la MISMA verdad nazcan desde Sales, POS,
> Storefront, WhatsApp, Portal, API o ARIN.

## Matriz de hallazgos

| ID | Hallazgo | P | Bloquea F10 | Solución propuesta | Riesgo | Tests | Estimación |
|---|---|---|---|---|---|---|---|
| H1 | WaBot mutable cross-tenant | P0 | Sí | Tenant derivado de secreto por proyecto (patrón `copilot_token`/BotWebhook) + ownership en toda ruta /wa/* + token fuera del repo | Alto (toca bot externo) | cross-tenant A→B | LARGE |
| H2 | Rifa: fix incompleto | P0 | Sí | Auditar TODA la capacidad Rifa; filtrar project_id en cada lectura/mutación; tests cross-tenant por operación | Medio | cross-tenant por método | MEDIUM |
| H3 | Entitlements no exigidos en /bixosales | P0 | Sí | `module:` a nivel servidor en las rutas con entitlement; unificar con project_modules | Medio (puede cortar acceso real) | sin módulo→403, con módulo+permiso→ok | MEDIUM |
| H4 | Deuda con 3 fuentes | P1 | Sí | PaymentController registra por `Ledger::registrar`; Cobranza y 360 derivan del Ledger; payment_status/advance_amount = cache | Alto (dinero) | S/100 pago/parcial/reversión coherente en 4 vistas | LARGE |
| H5 | Correlativos fuera de transacción | P1 | Sí | Único emisor `Invoice::emitirNumero` (transaccional); reemplazar todas las llamadas crudas | Medio (fiscal) | numeración concurrente sin colisión | MEDIUM |
| H6 | Stock de variante fuera del Kardex | P2 | Sí | Decisión de esquema (A/B/C) + caso de uso único de mutación de existencias; prohibir decrement/increment/update directos | Alto (inventario) | venta de variante mueve Kardex | LARGE |
| H7 | Order→Inventory (7/9 no descuentan) | P2 | Sí | Definir el EVENTO que afecta inventario; centralizar en un caso de uso que todo canal invoque | Alto | cada canal produce el mismo efecto | LARGE |
| H8 | client_id no propagado | P3 | Sí | Reglas de identificación (RUC/DNI/teléfono/email/invitado) sin duplicar Client; propagar FK en cada creador | Medio | pedido/comprobante enlaza Client correcto | MEDIUM |
| H9 | Customer 360 incompleto + test falso | P3 | Sí | Tras H8, poblar Invoice/GuiaRemision.client_id y decidir SalesInteraction; test con casos de uso reales | Bajo | 360 muestra las fuentes reales, sin insertar FK a mano | MEDIUM |
| H10 | Pricing divergente por canal | P4 | No | PriceResolver canónico (AS-IS→TARGET); documentar reglas | Bajo | precio consistente por canal/cliente | MEDIUM |

Gaps registrados (NO se cierran en este checkpoint): PurchaseOrder (gap
funcional de Operations, otro roadmap); snapshots de cliente en documentos
(legítimos: FK canónica vs snapshot histórico, no duplicación).

---

## Progreso de ejecución

| Bloque | Estado | Evidencia |
|---|---|---|
| H1 WaBot isolation | **PASS (código+deploy)** · residuo operativo | `wa_bot_token` por proyecto + ownership por dueño + token global a config; `WaBotIsolationTest` 7/7; en ARIN (migración + smoke). RESIDUO: el puente legacy acepta aún el token global conocido — cierre pleno requiere rotar `WABOT_TOKEN` en .env + migrar el conector a wa_bot_token (paso operativo, no rompe hasta coordinarlo) |
| H2 Rifa isolation | **PASS (local)** — pendiente deploy | Auditada toda la capacidad: 4 métodos de panel (filtro BotInstance por proyecto) + 5 rutas públicas /wa/rifa* (trait `AutenticaConectorWa`: secreto por tenant + ownership; `botPaymentProof`/`botUpdateData` NO tenían token check); `RifaIsolationTest` 6/6 |
| H3 Entitlements | **PASS (local)** — pendiente deploy | Middleware `comercial.module` en el grupo /bixosales (1 línea cubre todas las rutas): ACCESS = entitlement AND permiso. Gatea los 5 módulos que TODOS los tenants tienen (orders/clients/invoices/quotes/catalog); logistics queda para backfill (tecsist/demo no lo tienen). `ComercialEntitlementTest` 4/4 |
| H4 Finance/Ledger | **PASS (local)** — pendiente deploy | PaymentController (manual/Culqi/MP) registra por `Ledger::registrar` (idempotente) en vez de escribir `payment_status` directo; Cobranza y 360 derivan del libro. `CoherenciaDeudaTest` 3/3 (S/100→0/30/70/reversión coherente). Históricos divergentes en prod: SOLO pedidos 20/21/22 (vetados, documentados) |
| H5 Correlativos | **PASS (local)** — pendiente deploy | `Invoice::emitir()` reserva correlativo + crea el comprobante en la MISMA transacción; InvoiceController y QuoteController migrados; ninguna llamada cruda a nextCorrelativo (barrido estático). `InvoiceNumberingTest` 4/4 |
| H6 Stock variantes | pendiente | — |
| H7 Order→Inventory | pendiente | — |
| H8 client_id | pendiente | — |
| H9 Customer 360 | pendiente | — |
| H10 Pricing | pendiente | — |

## H1 — WaBotController mutable cross-tenant (P0, PRIORIDAD ABSOLUTA)

- **Gravedad:** ALTA. Explotable hoy. RISK-008/TD-012.
- **Causa raíz:** las rutas públicas `/wa/*` (routes/web.php:777-786) se autentican con un único token global hardcodeado (`WaBotController.php:16 BOT_TOKEN='wa-bot-secret-2024'`, visible en el repo). El tenant NO se resuelve; el `Order` se liga por route-model-binding y, al ser petición anónima, el `HasProjectScope` es neutro → el binding alcanza órdenes de cualquier tenant. `findOrder` busca por teléfono con `Order::allProjects()` sin project_id.
- **Comportamiento actual:** con ese token (público) se puede: leer order_id de cualquiera (`findOrder:336-340`), y mutar `wa_status`/`status`/`payment_proof`/**`total`/`delivery_address`/`shipping_cost`** de pedidos de cualquier negocio (`paymentReceived:214`, `clientConfirmed:224`, `receivePaymentProof:347`, `updateDelivery` rama /wa/:372-395).
- **Comportamiento esperado:** REQUEST → TENANT (derivado de un secreto NO controlable por el usuario) → PROJECT CONTEXT → validación de ENTITY OWNERSHIP → MUTATION. Una credencial de la Empresa A no puede tocar NADA de la Empresa B.
- **Archivos:** `app/Http/Controllers/WaBotController.php`, `routes/web.php:777-786`, nuevo middleware, `config/services.php`, migración de token por proyecto. Modelo de referencia (ya correcto en el repo): `app/Http/Controllers/Api/BotWebhookController.php` y `Api/CopilotController.php::project()` (resuelven tenant por token + `where project_id`).
- **Caso de uso canónico:** resolución de tenant WhatsApp única. El tenant se deriva así, en orden: (1) `wa_bot_token` por proyecto (secreto generado, no público) si el request lo trae; (2) durante la transición, token global movido a `config('services.wabot.token')` (fuera del repo, en `.env` del VPS) + número del negocio (`biz_phone`=`wa_phone`) para anclar el tenant. Toda operación sobre `Order` pasa por un helper `ordenDelTenant($order, $project)` que aborta 404 si `$order->project_id !== $project->id`.
- **Migraciones:** `projects.wa_bot_token` (string, nullable, unique) para la fase objetivo. No destructiva.
- **Compatibilidad:** el conector del bot vive fuera del repo (VPS `/home/arindg/bixo-baileys`, `whatsbot/`). Estrategia: (a) mover el token a `.env` con el MISMO valor actual → el bot no cambia, pero el secreto sale del repositorio y se rota; (b) aceptar `wa_bot_token` por proyecto cuando el bot se actualice; (c) exigir `biz_phone` en los endpoints de Order — el bot ya envía el número del negocio en `getConfig`/`getFlowConfig`, se extiende a los de Order. Durante la transición se aceptan ambos, con ownership SIEMPRE validado.
- **Riesgos:** cortar el flujo real del bot si se exige de golpe un parámetro que el bot aún no manda. Mitigación: la validación de ownership no depende de cambiar el bot (usa el tenant resuelto por el medio disponible); el corte solo ocurre si no se puede resolver tenant, y ahí es correcto cerrar (fail-closed).
- **Tests actuales:** ninguno cubre `/wa/*` cross-tenant.
- **Tests nuevos:** credencial/token de A contra order_id de B → 404; findOrder de A no ve pedidos de B; updateDelivery de A no altera total de B; sin tenant resoluble → deny.
- **Rollback:** el cambio es aditivo (middleware + validaciones + columna nullable). Revertir = quitar el middleware de las rutas; sin migración destructiva.
- **Orden:** primero. Se ejecuta en esta sesión.

## H2 — Rifa: cerrar el fix incompleto (P0)

- **Gravedad:** ALTA (condicional: hoy 0 bots de rifa). RISK-009/TD-013.
- **Causa raíz:** el fix del 2026-08-27 filtró `BotInstance` por proyecto solo en los 3 reportes. Quedaron `nuevoManual:874`, `eliminarComercial:936`, `editarComercial:960`, `recordar:1013`, y las rutas públicas `/wa/rifa*` (botList, botCreateOrder, botSave, botPaymentProof, botUpdateData) que comparten el patrón token global + BotInstance sin filtrar.
- **Comportamiento actual:** `nuevoManual` crea `RifaVenta` bajo el primer bot de rifa global. Los otros ensanchan la allow-list con project_id ajeno. Las rutas `/wa/rifa*` heredan el problema de H1.
- **Comportamiento esperado:** cada lectura/mutación de RifaVenta/Rifa valida Project/Tenant. Sin excepción.
- **Archivos:** `app/Http/Controllers/RifaController.php` (todos los métodos), `routes/web.php:851-855`.
- **Caso de uso canónico:** resolución de tenant de rifa idéntica a H1 (mismo helper de WhatsApp para las rutas /wa/rifa*); en el panel, `app('active_project')`.
- **Migraciones:** ninguna.
- **Compatibilidad:** igual que H1 para las rutas de bot.
- **Riesgos:** bajo (hoy sin bots de rifa activos).
- **Tests actuales:** AislamientoTenantTest cubre `cancelar`. No cubre nuevoManual/editar/eliminar/recordar ni las rutas /wa/rifa*.
- **Tests nuevos:** cross-tenant para cada operación mutable de rifa.
- **Rollback:** aditivo.
- **Orden:** segundo. Solo cerrar RISK-009/TD-013 con cobertura completa demostrada.

## H3 — Entitlements efectivos en /bixosales (P0)

- **Gravedad:** MEDIA. RISK-013/TD-017.
- **Causa raíz:** el App Shell nuevo `/bixosales` (web.php:1023-1219) no lleva `module:`; el entitlement solo se exige en `/bixoadmin`. Además hay dos sistemas de módulo (`project_modules`/`hasModule()` vs `ModulosPortal` por uso).
- **Comportamiento actual:** una empresa sin el módulo entra por URL directa a /bixosales/... si el usuario tiene el permiso.
- **Comportamiento esperado:** ACCESS = TENANT_ENTITLEMENT AND USER_PERMISSION, a nivel servidor.
- **Archivos:** `routes/web.php` grupo bixosales, `app/Http/Middleware/CheckModuleActive.php`, `app/Support/ModulosPortal.php`.
- **Caso de uso canónico:** `hasModule()` sobre `project_modules` como fuente única de entitlement. `ModulosPortal` pasa a derivar de ahí (no de uso de datos).
- **Migraciones:** posible seed/normalización de `project_modules` para los tenants vivos (aditiva; documentar la realidad de qué módulos tiene cada uno hoy).
- **Compatibilidad:** cuidado de no cortar acceso real — auditar qué módulos usan de facto los tenants activos antes de exigir el entitlement.
- **Riesgos:** cortar acceso a un tenant que usa un módulo sin tenerlo en `project_modules`. Mitigación: backfill de project_modules desde el uso real antes de activar el gate.
- **Tests actuales:** ninguno de entitlement en /bixosales.
- **Tests nuevos:** sin módulo→403; con módulo sin permiso→403; con módulo+permiso→200.
- **Rollback:** quitar `module:` de las rutas.
- **Orden:** tercero.

## H4 — Payment / Ledger / Cobranza: una sola verdad (P1)

- **Gravedad:** ALTA (dinero). RISK-010/TD-014.
- **Causa raíz:** `PaymentController` (pasarelas manual/Culqi/MercadoPago) escribe `payment_status='paid'` directo sin crear asiento en `payments` ni tocar `advance_amount` (PaymentController.php:31-36,80-85,142,185). Cobranza lee la columna; ClientController::resumenDe lee el Ledger → divergen.
- **Comportamiento actual:** una venta pagada por pasarela sale saldada en Cuentas por Cobrar y como deuda íntegra en Customer 360.
- **Comportamiento esperado:** todo pago real genera asiento vía `Ledger::registrar`. `payment_status`/`advance_amount` = proyección del Ledger. Todas las vistas derivan del Ledger.
- **Archivos:** `app/Http/Controllers/PaymentController.php`, `app/Support/Ledger.php`, `app/Support/Cobranza.php`, `app/Http/Controllers/ClientController.php`, `Api/PagoController.php` (ya usa Ledger), `PosController` (referencia correcta).
- **Caso de uso canónico:** `Ledger::registrar($project,$order,$monto,...)` como único punto de entrada de un pago; idempotencia por origen (webhook repetido no duplica).
- **Migraciones:** posible backfill de asientos para pagos históricos que quedaron solo como `payment_status='paid'` sin fila en `payments` (con cuidado; documentar).
- **Compatibilidad:** revisar todos los orígenes (manual, POS, Culqi, MercadoPago, Yape/Plin, API, checkout, integraciones).
- **Riesgos:** doble conteo si se registra asiento sobre pagos ya reflejados en advance_amount. Mitigación: el patrón `adoptarAdelantoHeredado` ya existe en Ledger; usarlo.
- **Tests actuales:** ninguno cruza Cobranza vs 360.
- **Tests nuevos:** pedido S/100: pago 0→deuda 100; pago 30→70; pago 70→0; reversión 30→30; mismo resultado en CxC, 360, detalle de pedido y reportes. Sobrepago, doble pago, reversión, anulación, pago rechazado, webhook repetido (idempotencia).
- **Rollback:** el registro por Ledger es aditivo; si se detecta doble conteo, revertir PaymentController al set directo y recalcular.
- **Orden:** cuarto.

## H5 — Correlativos de comprobante: un único camino transaccional (P1)

- **Gravedad:** MEDIA (fiscal). RISK-012/TD-016.
- **Causa raíz:** `Invoice::nextCorrelativo()` se llama crudo y fuera de transacción en `QuoteController::convertirPortal:836` e `InvoiceController::store:214`. El canónico `Invoice::emitirNumero()` (Invoice.php:97) sí envuelve en `DB::transaction` con `lockForUpdate`.
- **Comportamiento esperado:** reserva de correlativo + creación atómica, único emisor.
- **Archivos:** `app/Models/Invoice.php`, `app/Http/Controllers/QuoteController.php`, `app/Http/Controllers/InvoiceController.php`, y toda otra ocurrencia (grep `nextCorrelativo`).
- **Caso de uso canónico:** `Invoice::emitirNumero()` como única puerta; `nextCorrelativo` pasa a privado o protegido.
- **Migraciones:** ninguna.
- **Compatibilidad:** revisar series y tipos de documento (boleta/factura/NC/ND, F001/B001…).
- **Riesgos:** bajo.
- **Tests nuevos:** emisión concurrente (o la mejor prueba transaccional posible) sin correlativo repetido; por serie y tipo.
- **Rollback:** trivial.
- **Orden:** quinto.

## H6 — Stock de variante: un solo propietario (P2)

- **Gravedad:** ALTA (inventario). RISK-011/TD-015.
- **Causa raíz:** `product_variants.stock` es un segundo almacén fuera del Kardex; el checkout web con variante hace `decrement('stock')` directo (PublicController.php:851); ProductVariantMatrixService.php:192 y ProductController hacen escrituras directas.
- **Decisión arquitectónica requerida (A/B/C) — a tomar leyendo el modelo real:** A) stock solo por Product; B) stock por variante, Product agregado; C) ambos con reglas explícitas. NO inventar: analizar productos sin/ con variante, matrices, POS, checkout, importaciones, sync, inventario inicial, edición, ajustes, devoluciones, anulaciones.
- **Comportamiento esperado:** una mutación de existencias = un caso de uso auditable por Kardex. Ningún controlador hace decrement/increment/update de stock para una operación comercial.
- **Archivos:** `app/Support/InventoryLedger.php`, `PublicController.php`, `PosController.php`, `Catalog/ProductController.php`, `Storefront/ProductVariantMatrixService.php`, `Catalog/Sync/*`, modelos Product/ProductVariant.
- **Migraciones:** según la decisión (posible consolidación de existencias).
- **Compatibilidad:** productos sin variante hoy usan products.stock; no romperlos.
- **Riesgos:** alto; descuadre si la migración de saldos se hace mal.
- **Tests nuevos:** venta de variante mueve el Kardex; saldo de variante y movimiento cuadran.
- **Rollback:** cuidadoso; snapshot de saldos antes de migrar.
- **Orden:** sexto (tras decisión explícita).

## H7 — Order→Inventory: centralizar el evento (P2)

- **Gravedad:** ALTA. TD-019.
- **Causa raíz:** el descuento de stock está incrustado ad-hoc en 2 de 9 creadores de Order; no hay un caso de uso que defina QUÉ evento afecta inventario.
- **Comportamiento esperado:** definir el evento (creación/confirmación/aprobación/pago/preparación/despacho, según el negocio real, posiblemente distinto por tipo) y centralizarlo en un caso de uso que todos los canales invoquen.
- **Archivos:** los 9 creadores de Order (OrderController, PosController, PublicController, QuoteController::convert, DeliveryController, WaBotController/BotWebhookController, PortalClienteController, Api/VentaExtensionController) + nuevo caso de uso + `InventoryLedger`.
- **Caso de uso canónico:** p.ej. `CommitInventory` disparado por el evento de negocio correcto (nombre a adaptar al código real).
- **Migraciones:** ninguna.
- **Riesgos:** alto (cambiar cuándo se descuenta afecta operación real). Requiere leer el comportamiento existente antes de decidir.
- **Tests nuevos:** el mismo caso de uso de negocio produce el mismo efecto de stock en todos los canales.
- **Rollback:** por canal.
- **Orden:** séptimo (depende de H6).

## H8 — Propagación de client_id (P3)

- **Gravedad:** MEDIA. TD-018.
- **Causa raíz:** checkout web (PublicController.php:806) y bot WhatsApp (BotWebhookController.php:207) crean Order sin `client_id`; Invoice/GuiaRemision tampoco lo asignan.
- **Comportamiento esperado:** cuando el cliente está identificado (RUC/DNI/teléfono/email conocido), preservar la FK canónica; invitado/no identificado no inventa Client sin regla explícita; evitar duplicados.
- **Archivos:** creadores de Quote/Order/Invoice/GuiaRemision/SalesInteraction.
- **Caso de uso canónico:** resolución/creación de Client con reglas por identificador; `firstOrCreate` controlado.
- **Migraciones:** ninguna (posible backfill opcional).
- **Riesgos:** duplicar clientes si la regla de match es laxa.
- **Tests nuevos:** cada creador enlaza el Client correcto; invitado sin datos no crea Client fantasma.
- **Orden:** octavo.

## H9 — Customer 360 completo y honesto (P3)

- **Gravedad:** BAJA/MEDIA. TD-018.
- **Causa raíz:** 3 de 5 fuentes nunca se llenan; Customer360Test inserta client_id a mano (falso positivo).
- **Comportamiento esperado:** la ficha agrega las relaciones reales del cliente; el test usa los mismos casos de uso que producción; Customer 360 actúa como detector de integridad del Customer canónico.
- **Archivos:** `ClientController.php`, `tests/Feature/Customer360Test.php`.
- **Orden:** noveno (depende de H8).

## H10 — Price Resolver (P4, no bloquea F10)

- **Gravedad:** BAJA. TD-020.
- **Causa raíz:** cada canal selecciona el precio distinto (POS reseller/sugerido; web ignora wholesale; bot expone wholesale). Aritmética única en LineMath.
- **Comportamiento esperado:** una política/caso de uso canónico que responda qué precio corresponde a producto/variante para cliente/canal/cantidad, contemplando price/price_suggested/price_min/price_max/wholesale_price/reseller/variant. Documentar AS-IS→TARGET antes de cambiar reglas comerciales.
- **Orden:** décimo. Puede quedar PASS WITH DEBT si la divergencia restante está documentada y no produce valores incorrectos.

---

## Protocolo por bloque (obligatorio)

Tras cada bloque: tests específicos → regresión relacionada → revisar aislamiento
tenant → registrar evidencia → actualizar checklist. NO acumular 10 cambios y
probar al final. P0 de seguridad: desplegar en cuanto sus tests estén verdes.
Cambios financieros/inventario: verificar migración/compatibilidad/rollback
antes de desplegar. Flujo: local → tests → deploy → health → smoke → comparar
md5 local/producción.

## Autorización de F10

Requiere PASS en: WaBot isolation, Rifa isolation, Entitlements, Finance/Ledger,
Invoice numbering, Stock ownership, Order→Inventory, Customer linkage,
Customer 360. Pricing admite PASS WITH DEBT si está documentado y no produce
valores incorrectos.
