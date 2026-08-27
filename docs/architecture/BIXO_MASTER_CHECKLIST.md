# Checklist maestro de la reestructuracion

Estados: `[ ]` pendiente · `[~]` en progreso · `[x]` completado ·
`[!]` bloqueado · `[?]` requiere decision

Regla: nada se marca `[x]` sin evidencia (commit, test o documento).

---

## FASE 0 — Auditoria AS-IS

- [x] Inventariar rutas por portal — 675 rutas, 7 puertas (2026-08-27, `BIXO_CURRENT_STATE.md`)
- [x] Inventariar controladores compartidos — 19 de 85 sirven a varios portales
- [x] Inventariar modelos y scope de tenant — 13/83 con scope, 174 metodos auditados
- [x] Auditar aislamiento por metodo — 19 sin proteccion, 1 riesgo real (`BIXO_TENANT_ISOLATION.md`)
- [x] Auditar generaciones de permisos — 64 nuevas + 9 legacy
- [x] Buscar entidades competidoras de Customer — no hay: `client_id` consistente en 8 modelos
- [x] Medir volumen real de negocio — 6 tenants, 9 empleados (8 gerente), 16 pedidos/30d
- [x] Auditar documentos de arquitectura preexistentes — 4 encontrados, vigentes, complementados
- [x] Generar memoria persistente — HANDOFF, CURRENT_STATE, DECISIONS, RISKS, TECH_DEBT, TENANT_ISOLATION, CLAUDE.md
- [ ] Matriz AS-IS → TARGET por funcion (`BIXO_AS_IS_TO_TARGET.md`) — pendiente, se construye por fase para no producir un documento muerto de 675 filas
- [ ] Matriz de permisos completa (`docs/security/BIXO_PERMISSION_MATRIX.md`) — base: `docs/auditoria/bixosales-matriz-autorizacion.md` ya existente

## FASE 1 — Seguridad multiempresa

- [x] RISK-001: `HasProjectScope` en `RifaVenta` + test de aislamiento — 2026-08-27, `tests/Feature/AislamientoTenantTest.php` (4 tests: 404 ajeno, propio funciona, scope en listados, bot sin sesion intacto). Lecturas multi-proyecto legitimas (panel de rifas cruza al proyecto del bot) pasadas a `allProjects()` explicito; `generateOrderNumber` verifica unicidad global.
- [x] Plantilla de test de aislamiento por entidad (TD-002, parcial) — `AislamientoTenantTest` es la plantilla; faltan las demas entidades
- [ ] Decidir y aplicar politica fail-closed del scope (TD-001) — [?] requiere decision de diseño
- [x] Tests de aislamiento para las 14 entidades con scope — contrato "toda entidad nuclear filtra por proyecto" en `AislamientoTenantTest` (delata si alguien quita el trait) + contrato que documenta el fail-open de TD-001
- [x] Revisar los 6 metodos restantes sin proteccion no-admin — 2 huecos reales cerrados con test: `CatalogValue` (el catalogo propio servia de fachada para editar/borrar valores ajenos) y `BotTransition::transitionDestroy` (borraba transiciones de bots ajenos). El resto eran admin legitimo o ya protegidos por helpers.
- [x] Auditar jobs y comandos que usan `allProjects()` — 3 usos, todos legitimos: `ReintentarComprobantes` (sistema), `ApisPeruService::correlativoDeBaja` (filtra por proyecto explicito), `ProjectContext` (trabajo de otra sesion, no tocado)

## FASE 2 — Ownership y casos de uso canonicos

- [ ] Confirmar `MODULE_OWNERSHIP.md` contra el codigo (ya existe, revisar)
- [ ] Resolver TD-003 (brand_catalog_id huerfano): migrar tabla o retirar columna
- [ ] Migrar los 9 permisos legacy a `dominio.accion` (TD-004)
- [ ] Retirar del control plane la operacion de tenant (AdminImport, AdminTurnos)

## FASE 3 — Separar Control y Workspace

- [ ] Sesion unica de proyecto (TD-005): retirar `comercial_project_id` y `facturacion_auth.{slug}`
- [ ] Impersonacion auditada para soporte desde BIXO Control
- [ ] Login unico del Workspace

## FASE 4 — App Shell y navegacion unica

- [ ] Menu unico armado por entitlement AND permiso
- [ ] El shell de sales absorbe constructor y ajustes del panel
- [ ] Mapa de navegacion (`BIXO_NAVIGATION_MAP.md`)

## FASE 5 — Sales consolidado

- [ ] Redirigir `/f/{slug}` a sus equivalentes de sales (TD-007)
- [ ] Borrar las 13 vistas de `/f/{slug}` tras verificar consumidores
- [ ] Apagar el panel raiz con redirects

## FASE 6 — Customer 360

- [ ] Vista agregada leyendo fuentes existentes (sin tabla nueva)
- [ ] Linea de tiempo: cotizaciones, pedidos, facturas, pagos, guias, conversaciones

## FASE 7 — POS sobre casos de uso canonicos

- [ ] Verificar que POS consume Sales/Catalog/Payments sin logica propia duplicada

## FASE 8 — Finance y Operations estabilizados

- [ ] Auditar Purchasing/Suppliers reales (`Proveedor` existe; PurchaseOrder no)
- [ ] Kardex y politicas de stock documentadas

## FASE 9 — Commerce

- [ ] Checkout estilo carrito completo (pendiente historico)
- [ ] Ownership de storefront/checkout/cupones confirmado

## FASE 10 — Attribution + Smart QR  `[!]` bloqueado por volumen (ADR-005)

## FASE 11 — Portal del cliente

- [ ] Enlace con token por cliente (diseño ya conversado: repetir pedido, precios fijos vs a confirmar)

## FASE 12 — Pulse determinista  `[!]` bloqueado por volumen (ADR-005)

## FASE 13 — Automation + IA contextual  `[!]` depende de 12
