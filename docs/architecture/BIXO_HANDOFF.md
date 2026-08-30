# BIXO HANDOFF

> Este archivo es el punto de entrada de cada sesion. Leelo antes de tocar codigo.

Ultima actualizacion: 2026-08-30 (noche)
Branch: `refactor/store-builder-canonical-context`
Ultimo commit revisado: `5211f7e`

## Sesion 2026-08-30 (noche) — REESTRUCTURACION FINAL (plan de 17 pasos)

Entrega completa: `docs/architecture/BIXO_REESTRUCTURACION_ENTREGA.md`
(arboles reales de /admin, /bixoadmin y /bixosales; matrices; clasificacion).
Matriz de capacidades: `docs/architecture/BIXO_CAPACIDADES_MATRIZ.md`.

1. `8034c9b` **Control ordenado**: menu de /admin agrupado (solo lo que
   existe), /admin/auditoria NUEVA (solo lectura AccessEvent), boton
   "Entrar como" auditado en Empresas, retirada copia muerta del shell.
2. `55cef3a` **Capacidades + menu por cara**: middleware `capacidad` =
   entitlement + permiso + flag `cap_*` (solo Eskala lo enciende; el dueño
   NO se salta el flag). Plantillas de diseño = ESKALA_ONLY (su export iba
   SIN middleware); diseño legacy endurecido y fuera de menu. Sidebar:
   /bixoadmin muestra SOLO el arbol de Configuracion (plan §3), /bixosales
   SOLO Operacion (§9), acceso cruzado "→ Ir a..." en ambas caras.
3. `5211f7e` **Logins**: "BIXO · Configuración" y "BIXO · Ventas y
   operación"; aterrizajes admin→Mi negocio, sales→Inicio.

Suite: 1020 pass / 2 failed AJENOS (variantes, plantillas 3→2).
Tests nuevos: ControlNavegacionTest (3), CapacidadesRestringidasTest (5);
contratos de FusionPortales/WorkspaceShellUnificado/SettingsAuthorization
actualizados al modelo de dos caras.
Deuda nueva: TD-024 (bloques del Control sin backend), TD-025 (aplicacion
fina de cap_builder_avanzado / cap_seo_avanzado).

## Sesion 2026-08-30 (tarde) — SSL de raiz + 2 caras del Workspace + lote de cierre

Todo commiteado en `a476929` y DESPLEGADO a ARIN (backup 20260830_181447):

1. **SSL resuelto de raiz (TD-009 CERRADA).** `arindg.com` tiene cert propio
   HTTP-01 renovable (vence 2026-11-28). La causa de que certbot nunca
   renovara: el vhost de `n8n.arindg.com` proxyaba TODO (incluido
   `/.well-known/acme-challenge`) al puerto 5678. Se agrego bloque 80 con la
   excepcion ACME (backup `/root/arindg.com.conf.bak-n8n-acme`); dry-run:
   `all simulated renewals succeeded`. Siguen en wildcard (vence 2026-11-04):
   babytoncito.com e importmusuhuaysac.arindg.com (este espera DNS del usuario).
2. **2 caras del Workspace visibles (decision del usuario 2026-08-30):** el
   usuario vio "Mi negocio" en el shell unificado y sintio que "lo mandaba al
   panel de sales". Eligio: shell y login unicos, pero cara distinguible.
   Implementado: chip morado "Configuración" en el breadcrumb para
   `/bixoadmin/*` (`comercial/layouts/app.blade.php`), grupo Configuración
   acentuado en el sidebar, y el login aterriza en Inicio (`/bixosales`) en
   vez de la lista de negocios (routes/web.php, ruta `dashboard`).
3. **Salida de impersonacion auditada:** `POST /bixoadmin/salir-de-impersonacion`
   (registra `impersonate_end`, limpia sesion); fix del meta doble-encodeado
   en la entrada. `FusionPortalesTest` 5/5.
4. **Fix latente SUNAT:** `DarDeBajaEnSunat::failed()` con `allProjects()` —
   en cola sync el fallo no marcaba el comprobante (quedaba "pendiente" mudo).
5. **ModulosPortal unificado con entitlements:** el menu ya no ofrece
   facturas/reservas/reparto sin modulo contratado (misma regla que el gate
   `comercial.module`). Fixtures de MenuLateralComercialTest contratan modulos.
6. **Rutas legado /f/ medidas: 0 hits** en todos los logs de nginx → retirarlas
   es seguro cuando se decida.

Suite: 1011 pass; los 2 rojos son de la feature de variantes en vuelo
(plantillas 3→2 motores en `supported-template-selector`), ajenos.

**PENDIENTE QUE DECIDE EL USUARIO — token del bot (RISK-008 residuo):** rotar
`WABOT_TOKEN` exige tocar `.env` de ARIN y el conector Baileys (engine.js,
constante BOT_TOKEN) a la vez, con reinicio coordinado del bot (sin afectar al
otro bot). Procedimiento: (1) generar secreto nuevo, (2) actualizar .env +
`config:cache`, (3) actualizar conector y reiniciar SOLO ese PM2, (4) probar
un mensaje. Los defaults hardcodeados `wa-bot-secret-2024` en
CheckLaundryOverdue/SendAbandonedCartReminder/PublicController deben pasar a
config en ese mismo cambio.

## Incidente 2026-08-29 — tiendas caídas por deploy parcial de variantes (RESUELTO)

La feature de variantes se desplegó a ARIN de madrugada SIN sus modelos ni
tablas: `PublicController` (versión ARIN, más nueva que la del árbol local)
hacía eager-load de `activeVariants` y TODAS las tiendas públicas devolvían
500 (megahogar.org incluida). Rescate ejecutado:
1. Subidos los modelos que faltaban: ProductVariant, ProductAttribute,
   ProductAttributeValue, Product/Project (relaciones), MatrixService,
   CatalogQueryService (faltaba `facets()`), ProductVariantController
   (las rutas de ARIN lo referenciaban sin existir).
2. Migración `2026_08_27_000000_create_product_attributes_and_variants`
   corregida: DOS identificadores autogenerados superaban el límite de 64
   chars de MySQL (índice 73c → `pav_project_attr_active_idx`; FK 66c →
   `papv_pivot_value_fk`, distinto del de product_variant_values porque los
   nombres de FK son únicos por BD, error 1826). Registrada en ARIN [46].
3. Verificado: las 7 tiendas + megahogar.org + /tienda en 200; 0 errores
   nuevos en el log.
REGLA para la sesión de variantes: el deploy de esa feature debe ir COMPLETO
(controller+modelos+migraciones+servicios juntos). El `PublicController` y las
vistas de ARIN son MÁS NUEVOS que los del árbol local — comparar md5/mtime
antes de volver a desplegar cualquiera de esas piezas.

## Fase actual

**UNIFICACIÓN VISUAL DEL WORKSPACE — FASE 1 EN ARIN (2026-08-30)**, según la
spec del usuario y el ADR-002 precisado (2 plataformas; /bixoadmin y /bixosales
= dos caras del mismo Workspace):
- Sidebar maestro (`comercial/layouts/_sidebar.blade.php`): la configuración
  COMPLETA vive en el menú unificado (SEO, Pagos, Módulos, Roles, Catálogos
  maestros, Canales WhatsApp; Guías de remisión en Finanzas).
- "Mi negocio" y "Código QR" renderizan DENTRO del shell comercial unificado
  (`x-portal-layout comercial`); URLs /bixoadmin/* sin cambios.
- Header del panel con identidad común (BIXO + empresa activa).
- Deriva reconciliada: qr.blade.php de ARIN adoptada + 3 partials qr-*
  (1.344 líneas) que solo existían en ARIN entran al repo.
- `WorkspaceShellUnificadoTest` 4/4; suite 1006 verde (2 rojos ajenos designer).

**Deuda de la fase (TD-023):** Productos y Constructor siguen en el shell del
panel (feature de variantes ajena en vuelo — se re-parentan cuando aterrice);
después, retirar el shell del panel y paridad de breadcrumbs/notificaciones.
Antes:
**CHECKPOINT F1–F9 CERRADO FORMALMENTE (2026-08-28)** — informe:
`BIXO_CHECKPOINT_F1_F9_CLOSURE.md` (sección "CIERRE FORMAL"). Los 6 bloques de
seguridad/dinero/fiscal/entitlements en **PASS** y en ARIN:
- H1 WaBot (secreto por tenant + ownership), H2 Rifa (toda la capacidad),
  H3 Entitlements (`comercial.module`, 6 módulos incl. logistics), H4 deuda
  (todo pago por Ledger), H5 numeración (`Invoice::emitir` atómico).
- H10 Pricing = PASS WITH DEBT (documentado). H8/H9 = PARCIAL (comprobantes
  enlazan por teléfono; falta checkout/bot ajeno).
Suite: cierres 100% verdes (24 tests nuevos). RISK-008/009/010/012/013 y
TD-017 cerrados/mitigados.

**Bloque "Inventario y checkout" AGENDADO** (H6 stock variantes + H7
order→inventory + H8-checkout/bot + H10 resolver): ejecutar como UNA unidad
cuando la feature de **variantes de producto de otra sesión** aterrice
(ProductVariant/ProductVariantMatrixService sin commitear; tabla
`product_variants` NI EXISTE en prod → RISK-011 no es riesgo activo). Decisión
de negocio pendiente para H7: **descontar stock al confirmar** (recomendado).
4 tests AJENOS rojos (CatalogTemplatesManifest/Admin*Layout) por esa feature de
plantillas en el árbol — no son del checkpoint.

**Para salir con clientes: LISTO.** Lo crítico (seguridad + dinero) cerrado.
**GO/NO-GO F10: NO-GO** por dependencias externas, no por deuda de seguridad.
Antes:
**VALIDACIÓN ARQUITECTÓNICA INTEGRAL (2026-08-28)** — auditoría de arquitectura
contra código real (5 auditores). Informe: `BIXO_VALIDACION_ARQUITECTONICA.md`.
Veredicto: la FORMA general quedó como se acordó (Control≠Workspace,
Administrador=rol, no ERP paralelo, experiencias reutilizan canónicos, Growth
no existe, facturación única, estados desacoplados). Pero hay **5 FALLAS que
obligan NO-GO para F10**:
1. WaBotController mutable cross-tenant (RISK-008, explotable hoy).
2. Fix de rifa incompleto: 4 métodos sin filtrar (RISK-009) — mi cierre del
   2026-08-27 solo cubrió los reportes.
3. Deuda con 3 fuentes divergentes (RISK-010): PaymentController escribe sin Ledger.
4. Stock de variante fuera del Kardex (RISK-011).
5. Numeración de comprobante fuera de transacción (RISK-012).
Más: /bixosales sin `module:` (entitlement no exigido), client_id no enlazado
en 2 canales, Customer 360 con 3/5 fuentes vacías. Suite 903 verde. NADA se
modificó en esta auditoría (regla del usuario). NO iniciar F10.
Antes: checkpoint F0–F11 validado y **críticos cerrados** (2026-08-27), sistema
listo para salir con clientes. Cerrado y desplegado a ARIN: APP_DEBUG=false,
DELETE/PUT /projects con permiso, RBAC en las 34 rutas de /f/{slug} (middleware
`proyecto.slug`), fuga de lectura de rifa, y el envío entra al total del
checkout. Suite 903 verde. Pendiente NO bloqueante: enlace de cliente en el
checkout (client_id) — su fix vive en PublicController, hoy con una feature de
variantes de otra sesion sin terminar; se retoma cuando aterrice. Detalle:
`BIXO_VALIDACION_F0_F11.md` (sección "CIERRE DE HALLAZGOS").

## Ultima tarea terminada

**VALIDACIÓN FINAL F0–F11 (2026-08-27)** — auditoría independiente contra el
código real, commits, suite y ARIN. Resultado en
`BIXO_VALIDACION_F0_F11.md`. Resumen: F0/F1/F7/F8/F11 = PASS o PASS WITH DEBT;
F2/F3/F4/F5/F6/F9 = PARTIAL. **Recomendación NO-GO para F10** hasta cerrar 4
puntos: (1) `APP_DEBUG=true` en producción [CRÍTICO], (2) `DELETE /projects`
solo exige pertenencia, (3) `/f/{slug}` conserva 34 rutas sin RBAC granular
(retiro cosmético), (4) checkout no enlaza cliente ni propaga envío/cupón.
Suite verificada: 896 verdes, 0 skipped. Antes:
**F11 Portal del Cliente construida (2026-08-27)**: enlace personal
`GET /c/{token}` (registrado ANTES del comodin /{slug}), repetir pedido con
interruptor `portal_precios` (fijos → Order pendiente canal portal a precio
de catalogo vigente; confirmar → Quote draft con lineas a 0), boton
generar/copiar/regenerar en la ficha del cliente (panel `clients.portal` y
bixosales `bixosales.clientes.portal`, permiso clients.editar),
migracion `clients.portal_token` corrida en local, `PortalClienteTest` 6/6.
Plan vivo en `BIXO_PLAN_TRABAJO.md`. Antes: Fase 1 casi completa: contrato de scope para las 14 entidades nucleares,
2 huecos mas cerrados (CatalogValue con fachada, BotTransition ajena),
auditoria de allProjects() en jobs limpia, y TD-010 descubierta y registrada
(dos generaciones de flujos de bot). Antes: **RISK-001 cerrado**: `HasProjectScope` en `RifaVenta`, lecturas
multi-proyecto con `allProjects()` explicito, unicidad global de
`order_number`, y `tests/Feature/AislamientoTenantTest.php` como plantilla
de TD-002. Antes de eso: auditoria medida del repositorio: rutas por portal, aislamiento multiempresa
por metodo, generaciones de permisos, entidades competidoras y volumen real
de negocio. Resultados en `BIXO_CURRENT_STATE.md` y `BIXO_RISKS.md`.

## Tarea en progreso

Ninguna. Fase 0 cerrada a la espera de decision sobre el orden de la Fase 1.

## Siguiente tarea

Reestructura ejecutada hasta F8 (ADR-010). Queda la limpieza diferida:
borrar vistas f-slug tras un ciclo, retirar lectores de
`comercial_project_id`, mapa de navegacion y apagado del panel raiz.
F9 (checkout) y F11 (portal cliente) pasan al backlog de producto;
10/12/13 esperan volumen (ADR-005). Antes: Fase 2 COMPLETA (queda el retiro final de
nombres legacy tras un ciclo de uso). Sigue Fase 3: sesion unica de
proyecto (TD-005) e impersonacion auditada. Antes: Fase 1 COMPLETA. Sigue Fase 2: TD-003 (brand_catalog_id huerfano),
retiro final de nombres legacy de TD-004 tras un ciclo de uso, TD-010
(dos generaciones de flujos de bot), y sacar del control plane la
operacion de tenant (AdminImport/AdminTurnos). Antes era: decidir TD-001 — unica tarea de la
Fase 1 que queda, y es decision de diseño, no mecanica. Con eso la Fase 1
cierra y sigue la Fase 2 (ownership: TD-003 brand_catalog_id, TD-004 permisos
legacy, TD-010 dos generaciones de flujos de bot). Antes era: **RISK-001** — RifaVenta sin comprobacion de tenant (5 metodos). Es el unico
hallazgo de acceso cruzado confirmado y no depende de ninguna reestructuracion:
se corrige solo, con test de aislamiento. Ver `docs/security/BIXO_TENANT_ISOLATION.md`.

## Archivos actualmente involucrados

Ninguno en edicion. La auditoria no modifico codigo de aplicacion.

## Tests

- Suite completa: **874 passed** (3.375 assertions) al commit `2a64c15`.
- Tests de aislamiento multiempresa por entidad: **no existen todavia** (deuda TD-002).

## Problemas conocidos

- Certificado SSL de `arindg.com` **vencido el 2026-08-27**; existe `arindg-wildcard`
  valido 68 dias que ya cubre el dominio. No es un tema de arquitectura pero
  afecta a produccion.
- 59 archivos modificados sin commitear en el arbol de trabajo, varios de otra
  sesion (procesamiento de imagenes). No commitear a ciegas.

## Decisiones pendientes

1. Orden de la Fase 1: corregir RISK-001 antes o despues de unificar sesiones.
2. Si `/r/{slug}` (revendedores) sigue vivo comercialmente.
3. Nombre en castellano para la experiencia "Growth".

## NO TOCAR

- `whatsbot/`, `.env`, `storage/`, `vendor/`, `node_modules/`.
- Pedidos 20, 21 y 22.
- `resources/views/public/templates/computienda.blade.php` sin comparar antes
  contra produccion: el archivo tiene deriva y trabajo de otra sesion.

## Contexto necesario para continuar

Leer en este orden: `BIXO_CURRENT_STATE.md`, `BIXO_MASTER_CHECKLIST.md`,
`BIXO_DECISIONS.md`, `MODULE_OWNERSHIP.md`, `BIXO_RESTRUCTURE_ROADMAP.md`.
Los cuatro documentos de arquitectura preexistentes (`BIXO_PLATFORM_AUDIT.md`,
`BIXO_TARGET_ARCHITECTURE.md`, `BIXO_RESTRUCTURE_ROADMAP.md`,
`MODULE_OWNERSHIP.md`) son de una sesion anterior y siguen vigentes: esta
auditoria los complementa con evidencia medida, no los sustituye.
