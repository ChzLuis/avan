# Registro de riesgos

Ultima actualizacion: 2026-08-27

| ID | Riesgo | Prob. | Impacto | Estado |
|---|---|---|---|---|
| RISK-001 | Acceso cross-tenant en `RifaVenta` (5 metodos sin comprobacion) | Alta | Alto | **CERRADO 2026-08-27** (scope + 4 tests) |
| RISK-002 | Scope global *fail-open*: sin sesion no filtra (jobs, consola, `/f/{slug}`) | Media | Alto | ABIERTO |
| RISK-003 | Dos generaciones de permisos (64 `dominio.accion` + 9 legacy) | Media | Medio | ABIERTO |
| RISK-004 | Tres sesiones de proyecto distintas; una pantalla puede leer la equivocada | Media | Medio | ABIERTO |
| RISK-005 | 59 archivos sin commitear de sesiones mezcladas; un commit a ciegas arrastra trabajo ajeno | Alta | Medio | ABIERTO |
| RISK-006 | Deriva codigo local vs produccion (deploy quirurgico historico) | Media | Alto | MITIGADO (puerta de deriva en deploy.py) |
| RISK-007 | SSL de arindg.com vencido; renovacion automatica fallo silenciosamente | Ocurrio | Alto | **ACTIVO 2026-08-27** |

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
