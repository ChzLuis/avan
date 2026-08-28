# Registro de riesgos

Ultima actualizacion: 2026-08-27

| ID | Riesgo | Prob. | Impacto | Estado |
|---|---|---|---|---|
| RISK-001 | Acceso cross-tenant en `RifaVenta` (5 metodos sin comprobacion) | Alta | Alto | **CERRADO 2026-08-27** (scope + 4 tests) |
| RISK-002 | Scope global *fail-open*: sin sesion no filtra | Media | Alto | **CERRADO 2026-08-27** (politica fail-closed para autenticados; jobs con filtro explicito) |
| RISK-003 | Dos generaciones de permisos (64 `dominio.accion` + 9 legacy) | Media | Medio | ABIERTO |
| RISK-004 | Tres sesiones de proyecto distintas; una pantalla puede leer la equivocada | Media | Medio | ABIERTO |
| RISK-005 | 59 archivos sin commitear de sesiones mezcladas; un commit a ciegas arrastra trabajo ajeno | Alta | Medio | ABIERTO |
| RISK-006 | Deriva codigo local vs produccion (deploy quirurgico historico) | Media | Alto | MITIGADO (puerta de deriva en deploy.py) |
| RISK-007 | SSL de arindg.com vencido; renovacion automatica fallo silenciosamente | Ocurrio | Alto | **RESUELTO 2026-08-27** (vhost apuntado al wildcard valido hasta 2026-11-04; backup .bak-ssl-20260827; TD-009 sigue abierta: investigar por que certbot no renovo ese cert) |
| RISK-008 | **Cross-tenant en WaBotController**: rutas publicas `/wa/*` con token compartido; IDOR mutable de pedidos de cualquier tenant | Alta | Alto | **MITIGADO 2026-08-28** (secreto `wa_bot_token` por proyecto + ownership por dueño; token global a config; `WaBotIsolationTest` 7/7; en ARIN. Residuo: rotar `WABOT_TOKEN` + migrar conector para retirar el puente legacy) |
| RISK-009 | **Fix de rifa incompleto**: nuevoManual/eliminar/editar/recordar + rutas `/wa/rifa*` (dos SIN token check) tocaban ventas de tenant ajeno | Media | Alto | **MITIGADO 2026-08-28** (filtro BotInstance por proyecto + trait `AutenticaConectorWa` en las 5 rutas publicas; `RifaIsolationTest` 6/6; en ARIN. Mismo residuo del puente legacy que RISK-008) |
| RISK-010 | **Deuda con 3 fuentes divergentes**: Cobranza (columna advance_amount) vs ClientController::resumenDe (Ledger) vs PaymentController (escribe payment_status sin asiento) → misma venta pagada por pasarela sale saldada en CxC y como deuda integra en Customer 360 | Alta | Alto | **ABIERTO** (PaymentController.php:31-36; Cobranza.php:52; ClientController.php:120) |
| RISK-011 | **Stock de variante fuera del Kardex**: checkout web con variantes usa `decrement('stock')` directo (PublicController.php:851); InventoryLedger nunca ve esas salidas → descuadre de inventario | Media | Medio | **ABIERTO** |
| RISK-012 | **Numeracion de comprobante fuera de transaccion** en QuoteController::convertirPortal:836 e InvoiceController::store:214 → colision de correlativo bajo concurrencia | Baja | Alto | **ABIERTO** |
| RISK-013 | **Entitlement no exigido en /bixosales**: cero middleware `module:` en el App Shell nuevo → acceso por URL a modulo no contratado | Media | Medio | **ABIERTO** |

## RISK-001 — detalle

Ver `docs/security/BIXO_TENANT_ISOLATION.md`. Correccion: `HasProjectScope`
en `RifaVenta` + test. Es la primera tarea de la Fase 1.

## RISK-002 — detalle

El trait solo filtra si hay `active_project_id` o `comercial_project_id` en
sesion. Mitigacion propuesta (Fase 1): en contexto HTTP autenticado sin
proyecto en sesion, **cerrar** (abort o coleccion vacia), no abrir; en consola
y jobs, exigir `forProject($id)` explicito.

## RISK-007 — detalle

Certificado `arindg.com` vencido el 2026-08-27 17:03 UTC. Existe `arindg-wildcard`
valido hasta 2026-11-04 que ya cubre `arindg.com` y `*.arindg.com`. Accion:
apuntar el vhost al wildcard o renovar; investigar por que certbot no lo
renovo pese a correr (timer activo, ultima corrida 7h antes del vencimiento).
