# Checklist maestro de la reestructuracion

Estados: `[ ]` pendiente · `[~]` en progreso · `[x]` completado ·
`[!]` bloqueado · `[?]` requiere decision

Regla: nada se marca `[x]` sin evidencia (commit, test o documento).

---

## VALIDACIÓN F0–F11 (2026-08-27) — el `[x]` no significa "sin deuda"

Auditoría independiente contra el código real (no contra estos checkboxes).
Detalle y evidencia en `BIXO_VALIDACION_F0_F11.md`.

| Fase | Veredicto |
|---|---|
| F0 Auditoría | PASS |
| F1 Seguridad multiempresa | PASS WITH DEBT (fuga de lectura en reportes de rifa; `DarDeBajaEnSunat:61` latente) |
| F2 Ownership + permisos | PARTIAL (`DELETE /projects` solo pertenencia; GET de panel sin `can:`) |
| F3 Control/Workspace + impersonación | PARTIAL (impersonación sin salida/límite) |
| F4 Menú único | PARTIAL (3 ajustes protegidos solo por ocultar el menú en lectura) |
| F5 Sales + retiro f-slug | PARTIAL (retiro cosmético: 34 rutas vivas sin RBAC granular) |
| F6 Customer 360 | PARTIAL (3 de 5 fuentes nunca se llenan) |
| F7 POS | PASS WITH DEBT (otros creadores de Order no descuentan stock) |
| F8 Finance | PASS WITH DEBT (dos cálculos de deuda divergentes) |
| F9 Commerce | PARTIAL (checkout sin envío/cupón/cliente + datos GABDE) |
| F11 Portal Cliente | PASS WITH DEBT (sin revocación/throttle/idempotencia) |

**GO/NO-GO F10: NO-GO** hasta cerrar (1) `APP_DEBUG=true` en ARIN,
(2) `DELETE/PUT /projects` con permiso, (3) RBAC en `/f/{slug}`,
(4) `client_id` + envío/cupón en checkout. Suite: 896 verdes, 0 skipped.

## VALIDACIÓN ARQUITECTÓNICA 2026-08-28 — 5 FALLAS reabren el checkpoint

Auditoría de arquitectura (`BIXO_VALIDACION_ARQUITECTONICA.md`). La forma
general se respetó, pero hay fallas que impiden cerrar F0–F11 y bloquean F10:

- **FALLA cross-tenant WaBotController** (RISK-008/TD-012) — explotable hoy.
- **Fix de rifa incompleto** (RISK-009/TD-013) — el cierre del 2026-08-27 solo
  cubrió los 3 reportes; nuevoManual/eliminar/editar/recordar siguen sin filtrar.
- **Deuda con 3 fuentes divergentes** (RISK-010/TD-014).
- **Stock de variante fuera del Kardex** (RISK-011/TD-015).
- **Numeración fiscal fuera de transacción** (RISK-012/TD-016).
- Entitlement no exigido en /bixosales (TD-017); client_id no enlazado + 360 con
  3/5 fuentes vacías (TD-018); 7/9 creadores de Order sin descontar stock (TD-019).

Los tres arreglos del 2026-08-27 (APP_DEBUG, /projects, RBAC f-slug) SÍ se
verificaron vigentes en ARIN (md5 idéntico). **GO/NO-GO F10: sigue NO-GO.**
Suite: 903 verdes, 0 skipped.

## CIERRE DEL CHECKPOINT F1–F9 (2026-08-28) — progreso

Plan y evidencia: `BIXO_CHECKPOINT_F1_F9_CLOSURE.md`.

| Bloque | Veredicto | Evidencia |
|---|---|---|
| H1 WaBot isolation | **PASS** (código) · residuo: rotar token legacy | WaBotIsolationTest 7/7, en ARIN |
| H2 Rifa isolation | **PASS** | RifaIsolationTest 6/6, en ARIN |
| H3 Entitlements | **PASS** (5 módulos universales) | ComercialEntitlementTest 4/4, en ARIN |
| H4 Finance/Ledger | **PASS** | CoherenciaDeudaTest 3/3, en ARIN |
| H5 Invoice numbering | **PASS** | InvoiceNumberingTest 4/4, en ARIN |
| H6 Stock ownership | **BLOQUEADO** | sin variantes en prod (no es riesgo activo); feature ajena en vuelo |
| H7 Order→Inventory | **PARTIAL/coordinación** | requiere decisión de evento + toca código ajeno |
| H8 Customer linkage | **coordinación** | toca PublicController/BotWebhook (ajeno en vuelo) |
| H9 Customer 360 | **depende de H8** | — |
| H10 Pricing | **coordinación** (no bloquea F10) | toca PublicController (ajeno) |

**CHECKPOINT CERRADO FORMALMENTE 2026-08-28.** 6 bloques críticos en PASS:
H1 WaBot 7/7, H2 Rifa 6/6, H3 Entitlements 5/5, H4 Deuda 3/3, H5 Numeración 4/4,
H10 Pricing PASS WITH DEBT. H8/H9 PARCIAL (comprobantes enlazan; checkout/bot
ajeno). H6/H7 en el bloque agendado "Inventario y checkout" (feature de
variantes ajena + decisión de negocio: descontar al confirmar).

**GO/NO-GO F10: NO-GO** por dependencias externas (no deuda de seguridad).
**Para salir con clientes: LISTO** — lo crítico cerrado y en ARIN. Detalle:
`BIXO_CHECKPOINT_F1_F9_CLOSURE.md`.

## UNIFICACIÓN VISUAL DEL WORKSPACE (2026-08-30)

- [x] ADR-002 precisado: 2 plataformas; /bixoadmin y /bixosales = 2 caras del Workspace
- [x] Sidebar maestro con la configuración completa (SEO, Pagos, Módulos, Roles, Catálogos, Canales WA; Guías en Finanzas) — `_sidebar.blade.php`
- [x] Mi negocio y Código QR dentro del shell comercial unificado (URLs intactas) — `WorkspaceShellUnificadoTest` 4/4
- [x] Header del panel con identidad común (BIXO + empresa activa)
- [x] Deriva QR reconciliada (vista ARIN + 3 partials al repo)
- [ ] Fase 2 (TD-023): Productos y Constructor al shell unificado (tras feature de variantes ajena) → retirar shell del panel

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

- [x] Enlace con token por cliente — construido 2026-08-27: `GET /c/{token}` +
      repetir pedido (modo `portal_precios` fijos → Order pendiente a precio
      vigente; confirmar → Quote draft sin precios), botón en la ficha del
      cliente (panel y bixosales), `PortalClienteTest` 6/6.
      Evidencia: `PortalClienteController`, `portal-cliente/inicio.blade.php`,
      migración `2026_08_27_210000_add_portal_token_to_clients`.

## FASE 12 — Pulse determinista  `[!]` bloqueado por volumen (ADR-005)

## FASE 13 — Automation + IA contextual  `[!]` depende de 12

## REESTRUCTURACION FINAL — Control / Configuracion / Operacion (2026-08-30)

Plan de 17 pasos del usuario. Entrega: `BIXO_REESTRUCTURACION_ENTREGA.md`.

- [x] 1-3. **BIXO Control ordenado primero** — menu agrupado (Empresas /
      Licencias / Usuarios / Soporte y auditoria / Imports / Configuracion)
      SOLO con lo que existe; `/admin/auditoria` nueva (solo lectura de
      AccessEvent); boton "Entrar como" auditado en Empresas; retirada la
      copia muerta `layouts/admin.blade.php`. Sin CRUDs paralelos del tenant.
      `ControlNavegacionTest` 4/4. Commit `8034c9b`.
- [x] 4-6. **Auditoria y clasificacion de /bixoadmin** — 230 rutas revisadas
      contra `route:list`; matriz `BIXO_CAPACIDADES_MATRIZ.md` con
      PUBLIC_TENANT / RESTRICTED_TENANT / ESKALA_ONLY, con especial detalle
      del Constructor (funcion, ruta, permiso, entitlement, riesgo, decision).
- [x] 7. **Menu de Configuracion ordenado** segun el arbol del plan (Mi
      negocio / Catalogo maestro / Canales / Marketing / Pagos e
      integraciones / Fiscal / Equipo / Sistema).
- [x] 8. **Proteccion entitlement + permission + feature flag** —
      `App\Support\Capacidades` + middleware `capacidad:<clave>`; flags
      `cap_plantillas`, `cap_builder_avanzado`, `cap_seo_avanzado` que solo
      enciende Eskala (el dueño del negocio NO se los salta). Gestion de
      plantillas pasa a ESKALA_ONLY — su **export iba SIN middleware**;
      diseño legacy endurecido y fuera de menu.
      `CapacidadesRestringidasTest` 5/5. Commit `55cef3a`.
- [x] 9-10. **/bixosales ordenado** (solo Operacion) y navegacion cruzada
      "→ Ir a Configuracion" / "→ Ir a Ventas" en ambas caras.
- [x] 11. **Login/landing por cara** — "BIXO · Configuracion" y "BIXO ·
      Ventas y operacion"; admin aterriza en Mi negocio, sales en Inicio.
      Login de ventas reconciliado con la version de ARIN. Commits `5211f7e`,
      `c7e1b0b`.
- [x] 12-16. **Validacion** — suite completa; acceso directo por URL en
      produccion; cada plataforma a SU login (bug corregido: la raiz `/admin`
      empujaba al login del Workspace); tiendas con DNS en 200.
- [x] 17. **Documentacion** — ENTREGA, CAPACIDADES_MATRIZ, HANDOFF,
      TECH_DEBT (TD-024, TD-025), RISKS, este checklist.

Pendiente (deuda, no bloquea): TD-024 bloques del Control sin backend;
TD-025 aplicacion fina de `cap_builder_avanzado` y `cap_seo_avanzado`.
