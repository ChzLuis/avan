# Validación arquitectónica integral BIXO — checkpoint (2026-08-28)

> Auditoría de arquitectura contra el CÓDIGO REAL, no contra los checklists.
> Método: 5 auditores independientes en paralelo (Control/Workspace/nav,
> ownership/duplicados, facturación/estados/finance/operations,
> multitenancy/sales/360, experiencias) + verificación directa de commits,
> producción y tests. Cada hallazgo con archivo:línea. NO se modificó código
> (regla §29). Los falsos positivos de los auditores se marcan como tales.

## Tabla — ÁREA × VEREDICTO

| Área | Declarado | Verificado | Evidencia | Tests | Deuda / pendientes | Veredicto |
|---|---|---|---|---|---|---|
| **Control vs Workspace** | Planos separados | `/admin/*` íntegro bajo `superadmin`; `/bixoadmin` y `/bixosales` = workspace | routes/admin.php:23, web.php:101/1023 | FusionPortalesTest | Impersonación vive en `/bixoadmin` no `/admin`; tenant puede auto-editar sus módulos (web.php:98); AdminImport escribe datos de tenants desde Control | **PASS WITH DEBT** |
| **Control ≠ ERP paralelo** | Sin CRUD propio de negocio | No hay modelos propios; consume canónicos (withCount) | AdminProjectController.php:15 | — | AdminImportController escribe Product/Client/Employee cross-tenant | **PASS WITH DEBT** |
| **Administrador = rol** | Rol, no app | superadmin=columna (Eskala); admin=rol Spatie (tenant); sin panel paralelo | RolesPermissionsSeeder.php:45, IsSuperAdmin.php:13 | PanelAuthorizationTest | — | **PASS** |
| **Entitlements + Permissions** | Ambos exigidos en ruta | `module:` exigido en `/bixoadmin`; **cero `module:` en `/bixosales`**; DOS sistemas de módulo (project_modules vs ModulosPortal por uso); no existe entidad Plan | web.php:1023-1219, ModulosPortal.php:29, Project.php:81 | — | Acceso por URL a módulo no contratado en el App Shell nuevo | **PARTIAL** |
| **Ownership (16 entidades)** | Un propietario por capacidad | Dinero (LineMath/Ledger/Payment) impecable; sin modelos gemelos | ver tabla ownership | AislamientoTenantTest | **STOCK: dos almacenes** (products.stock + product_variants.stock fuera del Kardex); selección de precio divergente por canal | **PARTIAL** |
| **Product / Stock** | Product canónico único | Un solo App\Models\Product; aritmética única | Product.php:5, LineMath | CheckoutPublicoPrecioTest | **product_variants.stock descontado por decrement() directo sin Kardex** (PublicController.php:851); ProductController escrituras directas | **FAIL** (stock) |
| **Customer** | Fuente canónica única | Un solo Client; sin IDs fragmentados (no hay customer_id/buyer_id...) | Client.php:5 | Customer360Test | Denormalización de nombre en 7 tablas; Client mezcla cliente+lead | **PASS WITH DEBT** |
| **Facturación canónica** | Un dominio fiscal | Invoice cubre boleta/factura/NC/ND; guía separada; SUNAT centralizado | Invoice.php:112, SendInvoiceToSunat.php | NotasYBajaTest, GuiaRemisionTest | **Numeración fuera de transacción en 2 emisores** (QuoteController.php:836, InvoiceController.php:214) → colisión de correlativo | **PARTIAL** |
| **Estados desacoplados** | Sin estado gigante | Columnas separadas; PAGADO≠COMPLETADO, FACTURADO≠ENTREGADO, DESPACHADO≠COBRADO verificado | PaymentController.php:35, NotaController.php:186 | — | — | **PASS** |
| **Finance (deuda)** | Invoice/Payment/Receivable canónicos | Deuda derivada; Payment inmutable | Ledger.php:148 | — | **3 fuentes de deuda divergentes**: Cobranza (columna) vs ClientController (ledger) vs PaymentController (escribe payment_status SIN ledger) → venta pagada por pasarela sale saldada en CxC y deuda íntegra en 360 | **FAIL** |
| **Operations** | Inventory/Suppliers/etc canónicos | Kardex punto único declarado; Sedes/Proveedor OK | InventoryLedger.php:20 | KardexInventarioTest | Sin dominio de Compras (PurchaseOrder no modelado); 7 de 9 creadores de Order no descuentan stock | **PARTIAL** |
| **Multi-tenancy fail-closed** | Aislamiento cerrado | Fail-closed real; 14 entidades; APIs/exports/jobs aislados | HasProjectScope.php:38 | AislamientoTenantTest (9) | **FALLA A** RifaController 4 métodos sin filtro project_id; **FALLA B** WaBotController mutable cross-tenant | **PARTIAL** |
| **Sales consolidado** | Portal viejo retirado + canónico | /f/{slug} redirige y sus 34 rutas exigen permiso; controladores Portal delegan | web.php:923-966, ClientController.php:254 | PortalFacturacionRbacTest | SetActiveProjectFromSlug no re-valida membresía (depende de sesión) | **PASS WITH DEBT** |
| **Customer 360** | Read-model agregador | Solo lecturas, sin tabla nueva, cliente scoped | ClientController.php:67 | Customer360Test | **3 de 5 fuentes nunca se llenan** (Invoice/GuiaRemision.client_id, SalesInteraction sin escritor); el test da falso positivo | **PARTIAL** |
| **POS** | Experiencia de Sales | Reutiliza Client/Product/Ledger/InventoryLedger/Invoice | PosController.php:169-235 | PuntoVentaTest | Precio base propio (ResellerPrice) distinto de la tienda | **PASS WITH DEBT** |
| **Commerce/Storefront** | Consume canónicas | Order canónico, precio de catálogo blindado, stock por ledger; shipping ya viaja | PublicController.php:806, computienda:6911 | CheckoutPublicoPrecioTest | No enlaza client_id; variante descuenta fuera del Kardex | **PARTIAL** |
| **Portal Cliente F11** | Experiencia segura | Reutiliza Order/Quote/Client; sin PortalOrder/Customer/Invoice; token CSPRNG | PortalClienteController.php:67/96 | PortalClienteTest (6) | GET sin throttle; sin expiración; sin auditoría de generación; revocación solo por rotación | **PASS WITH DEBT** |
| **Growth** | No debe apropiarse dominios | No existe; nada mal agrupado | grep sin resultados | — | — | **PASS** (no construido) |
| **ARIN/Automation** | Capacidad separada sin duplicar | Bots emiten acciones; webhook crea Order canónico; IA aislada | BotWebhookController.php:207 | BotComercialTest | Order del bot sin client_id ni InventoryLedger | **PASS WITH DEBT** |
| **Navegación** | App Shell taxonomía objetivo | Sidebar por permisos | _sidebar.blade.php:55 | — | No alinea con target (faltan Commerce/Growth/ARIN; Productos/Clientes/Inventario mal ubicados); "A medida" gateado por is_superadmin | **PARTIAL** |

Suite: **903 verde, 3.480 assertions, 0 skipped, 0 fallos**, 93 suites, 18 warnings (deprecación PHPUnit).

## A. Arquitectura final encontrada (REAL)

```
BIXO PLATFORM
├── BIXO CONTROL  → /admin/*   (middleware superadmin, IsSuperAdmin)
│     tenants, licencias-de-asiento, imports, demos, health, AccessEvent
│     (NO hay entidad Plan/suscripción; "licencia" = asiento por usuario)
│     fuga: AdminImportController escribe Product/Client de tenants
│
└── BIXO WORKSPACE  (dos App Shells coexistiendo)
      ├── /bixoadmin/*  (panel legacy, project.member + module: + can:)
      └── /bixosales/*  (App Shell nuevo, comercial.auth + can:  — SIN module:)
            Sidebar por rubro: Comercial · Catálogos · Finanzas · Análisis ·
            Configuración · A medida (≠ taxonomía objetivo)
      Portales heredados en retiro: /f/{slug} (redirige+RBAC), /bixocrm, /b/{slug}, /c/{token}

Capacidades (dominio técnico, un propietario salvo lo anotado):
  Tenant=Project · User(+Employee) · Client · Product(+ProductVariant⚠) ·
  Category · Quote→Order→Invoice(4 tipos) · Payment(Ledger) · ReceivableTerm ·
  InventoryMovement(Kardex)⚠variantes · Proveedor · GuiaRemision · (sin PurchaseOrder)

Experiencias (no fuentes de verdad): POS · Storefront · Portal · ARIN/bots
Administrador = rol Spatie 'admin' (no app)
Growth = no existe
```

## B. Diferencias contra la arquitectura acordada (sin suavizar)

1. **El App Shell nuevo (`/bixosales`) NO exige entitlement** — cero `module:`. La regla "entitlement + permission" solo se cumple en el panel legacy. Un usuario con permiso entra por URL a un módulo que su empresa no contrató.
2. **Existen DOS App Shells** (`/bixoadmin` legacy + `/bixosales` nuevo), no uno. La migración no terminó.
3. **DOS sistemas de "módulo"**: `project_modules` (entitlement real) vs `ModulosPortal` (visibilidad por uso de datos). El menú usa el segundo.
4. **No existe modelo de Plan/suscripción** — el SaaS objetivo (tenants→planes→licencias) está a medias: solo hay licencias de asiento por usuario.
5. **Navegación no alineada** con Sales/Commerce/Operations/Finance/Growth/ARIN/Reports.
6. **Impersonación bajo `/bixoadmin`** (workspace) en vez de `/admin` (control).
7. **El tenant puede editarse sus propios entitlements** (web.php:98).

## C. Duplicidades todavía existentes

- **Stock: dos almacenes** — `products.stock` (Kardex) y `product_variants.stock` (fuera del Kardex, `decrement()` directo en PublicController.php:851; matriz en ProductVariantMatrixService.php:192). **FAIL de ownership.**
- **Selección de precio divergente por canal** (no la aritmética, que es única en LineMath): POS usa ResellerPrice→sugerido→base (PosController.php:138); web usa variant.price||product.price e ignora wholesale/reseller (PublicController.php:786); bot expone wholesale (ProjectContext.php:284). No hay PriceResolver único.
- **Cálculo de deuda triplicado** — Cobranza (columna advance_amount) / ClientController::resumenDe (Ledger) / PaymentController (escribe payment_status sin asiento). Ver E y G.
- No hay modelos gemelos (no hay dos Client/Order/Product). La duplicación es de almacén de stock y de cálculo, no de clases.

## D. Problemas de ownership

- **STOCK sin propietario único** (FAIL) — el checkout web con variantes evade el Kardex; el Kardex nunca ve esas salidas → el saldo de variante y el histórico divergen.
- **Purchasing no modelado** — la "compra" es solo un motivo `'compra'` en el Kardex, sin cabecera, líneas ni vínculo a Proveedor (que queda de catálogo aislado).
- **Client mezcla cliente + lead CRM** en la misma tabla; nombre denormalizado en 7 tablas (snapshots) que pueden divergir del maestro.

## E. Problemas multiempresa (los graves)

- **FALLA B — WaBotController mutable cross-tenant (ALTA, explotable hoy).** Rutas públicas `/wa/*` (routes/web.php:781-786) autenticadas solo por token compartido `wa-bot-secret-2024` (WaBotController.php:16). `findOrder` hace `Order::allProjects()->where('wa_number',$phone)` sin project_id (:336-340); como la petición es anónima el scope es neutro, así que el route-model-binding liga órdenes de cualquier tenant. Con ese token se puede leer order_id y **mutar total/dirección/estado/comprobante** de pedidos de otros negocios (:214, :347, :372-397).
- **FALLA A — RifaController fix INCOMPLETO (ALTA).** En la sesión anterior filtré `BotInstance` por proyecto en los 3 reportes, pero **quedaron 4 métodos sin arreglar**: `nuevoManual:874` (crea el RifaVenta bajo el primer bot de rifa global → escribe en tenant ajeno si hay ≥2 bots de rifa), `eliminarComercial:936`, `editarComercial:960`, `recordar:1013` (allow-list ensanchada con project_id ajeno). Es mi propio fix declarado cerrado que no lo estaba.
- Menor (BAJA): RifaController confirmar/enviar/cancelar sin `abort_unless` explícito (dependen solo del scope); Customer 360 sub-consultas sin project_id explícito (dependen del scope).

## F. Problemas de permissions / entitlements

- `/bixosales` sin `module:` (acceso a módulo no contratado por URL) — ver B.1.
- Dos definiciones de módulo (project_modules vs ModulosPortal) — ver B.3.
- Tenant auto-edita entitlements (web.php:98) — debería ser exclusivo de Control.
- Núcleo de permisos por verbo (can:/project.can:) sólido y granular; migración legacy aditiva coherente (verificado en auditorías previas).

## G. Deuda técnica (nueva o agravada)

- **Numeración de comprobantes fuera de transacción** en QuoteController::convertirPortal:836 e InvoiceController::store:214 (el canónico Invoice::emitirNumero envuelve en DB::transaction; estos llaman nextCorrelativo crudo). Colisión de correlativo bajo concurrencia.
- **3 escritores de deuda** incl. PaymentController que fija `payment_status='paid'` sin crear asiento en payments ni tocar advance_amount (PaymentController.php:31-36,80-85) → la MISMA venta pagada por Culqi/MP/manual aparece saldada en Cuentas por Cobrar y como deuda íntegra en Customer 360.
- **client_id no se enlaza** en checkout web (PublicController.php:806) ni en pedidos de WhatsApp (BotWebhookController.php:207) → ventas invisibles en CRM/360.
- **Customer 360: 3 de 5 fuentes vacías** (Invoice/GuiaRemision.client_id nunca asignados; SalesInteraction sin ningún escritor) + Customer360Test inserta client_id a mano (falso positivo).
- **7 de 9 creadores de Order no descuentan stock** (solo POS y checkout web).
- **ProductVariant fuera del Kardex** (ver C/D).

## H. Riesgos

- **RIESGO-CT-1 (alto):** WaBotController — IDOR + broken auth por token compartido. Cualquiera con el token (público en el repo) muta pedidos de todos los tenants. Antes de escalar canales o abrir más superficie pública, cerrar.
- **RIESGO-CT-2 (alto→condicional):** RifaController::nuevoManual escribe en tenant ajeno **si hay ≥2 proyectos con bot de rifa**. Hoy hay 0 bots de rifa registrados, así que no está activo, pero el código está y el fix se declaró cerrado.
- **RIESGO-FIN-1 (alto):** divergencia de deuda entre pantallas — decisiones de cobranza sobre cifras contradictorias.
- **RIESGO-INV-1 (medio):** stock de variantes sin Kardex — descuadre de inventario en tiendas que usan variantes.
- **RIESGO-FISC-1 (medio):** colisión de correlativo de comprobante bajo concurrencia.

## I. Tests faltantes

- Cross-tenant real de WaBotController `/wa/*` (hoy sin test; la fuga vive ahí).
- RifaController nuevoManual/eliminar/editar/recordar con ≥2 bots de rifa.
- Customer 360: camino REAL del controlador (que Invoice/GuiaRemision reciban client_id) — el test actual lo inserta a mano y da falso positivo; falta cubrir GuiaRemision y SalesInteraction.
- PaymentController: que un pago por pasarela deje la deuda coherente en CxC y en 360.
- Stock de variante descontado por el Kardex.
- Numeración concurrente de comprobantes.
- Entitlement en /bixosales (acceso a módulo no contratado por URL).

## J. Estado real de producción (ARIN)

- Los 9 archivos críticos idénticos local↔ARIN (md5 verificado): Controller, ProjectController, RifaController, ReporteController, SetActiveProjectFromSlug, bootstrap/app.php, routes/web.php, PortalClienteController, HasProjectScope.
- APP_DEBUG=false; salud portada/panel/bixosales/tienda/f-login/c-portal todos 302/200; SSL hasta 2026-11-04.
- **Importante:** las FALLAS A y B ya están en producción (código desplegado). No son regresiones nuevas: A es un fix incompleto; B es preexistente (token compartido de siempre).

## K. Qué falta para cerrar F0–F11 formalmente

1. Cerrar FALLA B (WaBotController) y FALLA A (RifaController 4 métodos) — cross-tenant.
2. Unificar deuda en una sola fuente (Ledger) y hacer que PaymentController registre asiento.
3. Enrutar stock de variante por el Kardex.
4. Numeración de comprobante siempre transaccional.
5. Exigir entitlement (`module:`) en `/bixosales`.
6. Enlazar client_id en checkout web y bot; poblar las 3 fuentes del 360 o marcarlas explícitamente como no implementadas.
7. F11: throttle en GET, y decidir expiración/auditoría/revocación.

## L. GO / NO-GO para F10

**NO-GO.** F10 (Atribución/Smart QR) nace sobre datos de venta que hoy: (a) no enlazan cliente en 2 de 3 canales, (b) tienen deuda calculada de 3 formas, (c) tienen stock que se descuadra con variantes. Atribuir sobre esa base produciría métricas falsas. Además hay dos fugas cross-tenant abiertas (WaBotController explotable, RifaController condicional). Primero cerrar E y G; luego F10.

Lo estructural SÍ quedó como se acordó: Control≠Workspace (con matices de ubicación), Administrador=rol, no ERP paralelo en Control, POS/Portal/Storefront/ARIN reutilizan canónicos, Growth no se apropió de nada, facturación es un dominio único, estados desacoplados. Los FAIL son de ownership de stock, coherencia de deuda y dos fugas cross-tenant — no de la forma general de la plataforma.
