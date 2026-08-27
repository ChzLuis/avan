# Registro de deuda tecnica

Ultima actualizacion: 2026-08-27

| ID | Sev. | Dominio | Descripcion | Fase objetivo | Estado |
|---|---|---|---|---|---|
| TD-001 | HIGH | Tenancy | `HasProjectScope` es fail-open (sin sesion no filtra) | 1 | ABIERTA |
| TD-002 | HIGH | Tenancy | Cero tests de aislamiento por entidad | 1 | ABIERTA |
| TD-003 | MEDIUM | Catalog | `products.brand_catalog_id` existe como columna, la tabla `brand_catalogs` no (deriva de esquema sin migracion) | 2 | ABIERTA |
| TD-004 | MEDIUM | Identity | 9 permisos legacy verbo-recurso conviven con `dominio.accion`; `project.can` acepta `A\|B` como puente | 2 | ABIERTA |
| TD-005 | MEDIUM | Plataforma | 3 sesiones de proyecto (`active_project_id`, `comercial_project_id`, `facturacion_auth.{slug}`) | 3-4 | ABIERTA |
| TD-006 | MEDIUM | Fiscal | Casos no cubiertos: credito con cuotas, lineas exoneradas, detracciones, resumen diario de bajas de boletas | segun demanda | ABIERTA |
| TD-007 | LOW | Vistas | `/f/{slug}`: 13 vistas / 4.038 lineas duplicando pantallas de sales | 5 | ABIERTA |
| TD-008 | LOW | Repo | 30+ archivos de trabajo ajeno sin commitear (procesamiento de imagenes); ya desplegado quirurgicamente | otra sesion | ABIERTA |
| TD-010 | MEDIUM | Automation | Dos generaciones de flujos de bot: `bot_flows` (FK real de `bot_states.flow_id`) y `bot_builder_flows` (lo que lee `BotState::flow()`). Ids alineados por casualidad de autoincremento; una desalineacion resolveria el flujo equivocado | 2 | ABIERTA |
| TD-009 | LOW | Infra | Renovacion de certbot no cubrio el cert `arindg.com` (multi-cert, orden no determinista) | inmediata | ABIERTA |

Regla: la deuda se registra aqui, no en comentarios sueltos. Al cerrarla,
anotar commit y test que la cubre.
