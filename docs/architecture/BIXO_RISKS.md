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
| RISK-007 | SSL de arindg.com vencido; renovacion automatica fallo silenciosamente | Ocurrio | Alto | **RESUELTO DE RAIZ 2026-08-30**: cert propio HTTP-01 renovable hasta 2026-11-28 (ya no depende del wildcard manual); causa hallada y corregida: el vhost de n8n.arindg.com proxyaba el desafio ACME (bloque 80 con excepcion .well-known agregado, backup /root/arindg.com.conf.bak-n8n-acme); dry-run `all simulated renewals succeeded`. TD-009 CERRADA |
| RISK-008 | **Cross-tenant en WaBotController**: rutas publicas `/wa/*` con token compartido; IDOR mutable de pedidos de cualquier tenant | Alta | Alto | **MITIGADO 2026-08-28** (secreto `wa_bot_token` por proyecto + ownership por dueño; token global a config; `WaBotIsolationTest` 7/7; en ARIN. Residuo: rotar `WABOT_TOKEN` + migrar conector para retirar el puente legacy) |
| RISK-009 | **Fix de rifa incompleto**: nuevoManual/eliminar/editar/recordar + rutas `/wa/rifa*` (dos SIN token check) tocaban ventas de tenant ajeno | Media | Alto | **MITIGADO 2026-08-28** (filtro BotInstance por proyecto + trait `AutenticaConectorWa` en las 5 rutas publicas; `RifaIsolationTest` 6/6; en ARIN. Mismo residuo del puente legacy que RISK-008) |
| RISK-010 | **Deuda con 3 fuentes divergentes**: PaymentController escribia payment_status sin asiento → CxC y 360 divergian | Alta | Alto | **MITIGADO 2026-08-28** (todo pago por Ledger::registrar; ambas vistas derivan del libro; `CoherenciaDeudaTest` 3/3; en ARIN. Historicos divergentes: solo pedidos 20/21/22 vetados) |
| RISK-011 | **Stock de variante fuera del Kardex**: checkout web con variantes usa `decrement('stock')` directo; InventoryLedger nunca ve esas salidas → descuadre | Media | Medio | **BLOQUEADO** (el codigo de variantes —PublicController, ProductVariantMatrixService, migracion product_attribute_values— es una feature EN VUELO de otra sesion; la decision de esquema A/B/C debe coordinarse con su dueño, no construirse sobre codigo inacabado) |
| RISK-012 | **Numeracion de comprobante fuera de transaccion** → colision de correlativo bajo concurrencia | Baja | Alto | **MITIGADO 2026-08-28** (`Invoice::emitir` atomico; barrido estatico sin llamadas crudas; `InvoiceNumberingTest` 4/4; en ARIN) |
| RISK-013 | **Entitlement no exigido en /bixosales**: cero middleware `module:` en el App Shell nuevo → acceso por URL a modulo no contratado | Media | Medio | **MITIGADO 2026-08-28** (middleware `comercial.module`; ACCESS = entitlement AND permiso; `ComercialEntitlementTest` 4/4; en ARIN. Gatea 5 modulos universales; logistics pendiente de backfill — TD-017) |

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
