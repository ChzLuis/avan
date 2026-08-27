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
- [x] Decidir y aplicar politica fail-closed del scope (TD-001) — 2026-08-27: autenticado sin proyecto = CERRADO; superadmin y anonimos (webhooks) = neutro; consola/colas = neutro con filtro explicito. Destapo que los 3 jobs fiscales dependian de la sesion → ahora `allProjects()` explicito. Contratos en `AislamientoTenantTest`.
- [x] Tests de aislamiento para las 14 entidades con scope — contrato "toda entidad nuclear filtra por proyecto" en `AislamientoTenantTest` (delata si alguien quita el trait) + contrato que documenta el fail-open de TD-001
- [x] Revisar los 6 metodos restantes sin proteccion no-admin — 2 huecos reales cerrados con test: `CatalogValue` (el catalogo propio servia de fachada para editar/borrar valores ajenos) y `BotTransition::transitionDestroy` (borraba transiciones de bots ajenos). El resto eran admin legitimo o ya protegidos por helpers.
- [x] Auditar jobs y comandos que usan `allProjects()` — 3 usos, todos legitimos: `ReintentarComprobantes` (sistema), `ApisPeruService::correlativoDeBaja` (filtra por proyecto explicito), `ProjectContext` (trabajo de otra sesion, no tocado)

## FASE 2 — Ownership y casos de uso canonicos

- [x] Confirmar `MODULE_OWNERSHIP.md` contra el codigo — marcas: `brand_catalog_id` → `catalog_values` (dueño Catalog); flujos de bot vivos → `bot_builder_flows` (dueño Automation)
- [x] Resolver TD-003 — falsa alarma con nombre engañoso; documentado el dueño real. De regalo: TD-011 encontrada y cerrada (tabla `import_logs` que el codigo escribia contra el vacio, migrada en local y produccion)
- [~] Migrar los 9 permisos legacy a `dominio.accion` (TD-004) — migracion ADITIVA hecha en produccion (41 concesiones; `logistics.ver/editar` creados; 4 rutas de delivery con puente A|B). Falta: retirar los nombres legacy de rutas y roles cuando se verifique un ciclo de uso.
- [x] Retirar del control plane la operacion de tenant — decision ADR-008: DEPRECATE hoy (congelados, sin funciones nuevas), retiro fisico en Fase 3 con la impersonacion auditada

## FASE 3 — Separar Control y Workspace

- [x] Sesion unica de proyecto (TD-005, primera mitad) — el login comercial escribe AMBAS claves alineadas y el logout borra ambas; el login de facturacion ya escribia `active_project_id`. Retirar los 18 lectores de `comercial_project_id` queda como limpieza diferida
- [x] Impersonacion auditada — `POST /bixoadmin/entrar-como/{project}`: solo superadmin, registra actor/IP/proyecto en `access_events` y entra al Workspace. Test fija el 403 y el rastro
- [x] Login unico del Workspace — /bixosales es LA entrada; /f/{slug}/login redirige alli (ADR-009)

## FASE 4 — App Shell y navegacion unica

- [x] Menu unico armado por permiso — el grupo Configuración del sidebar de sales aparece/desaparece segun `settings.*`/`catalog.ver` (test lo fija con gerente vs vendedor)
- [x] El shell de sales absorbe constructor y ajustes — Mi negocio, Constructor, Productos y Código QR enlazados a sus pantallas canonicas del panel (misma sesion tras F3)
- [ ] Mapa de navegacion (`BIXO_NAVIGATION_MAP.md`) — diferido: documentarlo antes de apagar el panel raiz

## FASE 5 — Sales consolidado

- [x] Redirigir `/f/{slug}` — login y tablero redirigen a /bixosales; rutas internas registradas como *.legado (ADR-009)
- [ ] Borrar las 13 vistas de `/f/{slug}` — diferido un ciclo, tras verificar que nadie llega
- [ ] Apagar el panel raiz con redirects — diferido: requiere que el menu unico cubra el 100% (hoy cubre configuracion nuclear)

## FASE 6 — Customer 360

- [x] Vista agregada sin tabla nueva — `ClientController::historialDe()` agrega cotizaciones, pedidos, comprobantes, guias e interacciones de las fuentes canonicas
- [x] Linea de tiempo en la ficha — el tab Historial del portal carga la relacion completa con resumen vendido/deuda; reemplaza al "Proximamente". Test fija agregacion y aislamiento

## FASE 7 — POS sobre casos de uso canonicos

- [x] Verificado — POS crea con `$project->orders()->create` (Order canonico), stock via `InventoryLedger::registrar`, cero modelos propios. Ya cumplia

## FASE 8 — Finance y Operations estabilizados

- [x] Auditado — `Proveedor` es un directorio CRUD (7 metodos); ordenes de compra NO existen: brecha de producto, se construye con demanda real
- [x] Kardex — todo movimiento pasa por `InventoryLedger` (escritor canonico, ya documentado en memoria de proyecto)

## FASE 9 — Commerce

- [ ] Checkout estilo carrito completo (pendiente historico)
- [ ] Ownership de storefront/checkout/cupones confirmado

## FASE 10 — Attribution + Smart QR  `[!]` bloqueado por volumen (ADR-005)

## FASE 11 — Portal del cliente

- [ ] Enlace con token por cliente (diseño ya conversado: repetir pedido, precios fijos vs a confirmar)

## FASE 12 — Pulse determinista  `[!]` bloqueado por volumen (ADR-005)

## FASE 13 — Automation + IA contextual  `[!]` depende de 12
