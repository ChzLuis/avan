# Registro de deuda tecnica

Ultima actualizacion: 2026-08-27

| ID | Sev. | Dominio | Descripcion | Fase objetivo | Estado |
|---|---|---|---|---|---|
| TD-001 | HIGH | Tenancy | `HasProjectScope` es fail-open (sin sesion no filtra) | 1 | **CERRADA 2026-08-27** |
| TD-002 | HIGH | Tenancy | Cero tests de aislamiento por entidad | 1 | ABIERTA |
| TD-003 | MEDIUM | Catalog | `products.brand_catalog_id` — el nombre engañaba: es FK a `catalog_values` (lista tipo brand), con migracion y consumidores. Dueño canonico: Catalog | 2 | **CERRADA 2026-08-27 (falsa alarma documentada)** |
| TD-004 | MEDIUM | Identity | 9 permisos legacy verbo-recurso conviven con `dominio.accion`; `project.can` acepta `A\|B` como puente | 2 | ABIERTA |
| TD-005 | MEDIUM | Plataforma | 3 sesiones de proyecto (`active_project_id`, `comercial_project_id`, `facturacion_auth.{slug}`) | 3-4 | ABIERTA |
| TD-006 | MEDIUM | Fiscal | Casos no cubiertos: credito con cuotas, lineas exoneradas, detracciones, resumen diario de bajas de boletas | segun demanda | ABIERTA |
| TD-007 | LOW | Vistas | `/f/{slug}`: 13 vistas / 4.038 lineas duplicando pantallas de sales | 5 | ABIERTA |
| TD-008 | LOW | Repo | 30+ archivos de trabajo ajeno sin commitear (procesamiento de imagenes); ya desplegado quirurgicamente | otra sesion | ABIERTA |
| TD-010 | LOW | Automation | Dos generaciones de flujos de bot — MEDIDO 2026-08-27: la vieja (`bot_flows`/`bot_states`/`bot_transitions`) esta VACIA en produccion; los 12 flujos vivos son `bot_builder_flows`. La generacion vieja y su editor en BotStatusController quedan DEPRECATE: retirar en Fase 5 tras verificar consumidores | 5 | DEGRADADA (deprecar, no refactorizar) |
| TD-011 | MEDIUM | Catalog | `ImportLog::create` corria contra una tabla inexistente y un `catch {}` vacio se lo tragaba: la auditoria de importaciones se perdia en silencio desde siempre | 2 | **CERRADA 2026-08-27** (migracion `2026_08_27_200000` en local y produccion) |
| TD-009 | LOW | Infra | Renovacion de certbot no cubrio el cert `arindg.com` (multi-cert, orden no determinista) | inmediata | ABIERTA |

Regla: la deuda se registra aqui, no en comentarios sueltos. Al cerrarla,
anotar commit y test que la cubre.
