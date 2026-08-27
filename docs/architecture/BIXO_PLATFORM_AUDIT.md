# Auditoría AS-IS de BIXO Platform

Fecha: 2026-08-27  
Alcance: repositorio Laravel en `C:\xampp\htdocs\avan`. Esta auditoría no presupone microservicios y preserva el monolito modular.

## Resumen ejecutivo

BIXO ya tiene una base compartida: las entidades comerciales importantes no están duplicadas en tablas separadas para Admin y Sales. El problema real está en cuatro zonas:

1. El control plane `/admin` todavía contiene operación de tenants (`AdminImportController` y `AdminTurnosController`).
2. Hay varios shells y vocabularios de acceso (`/bixoadmin`, `/bixosales`, facturación, comunicaciones y prototipos BIXO).
3. `ProjectModule` mezcla capacidades técnicas con la futura noción de producto contratado.
4. El aislamiento por tenant y las comprobaciones entitlement/permiso no se aplicaban uniformemente en todos los modelos y vistas.

La recomendación es consolidar, no reescribir: conservar modelos y rutas canónicas, introducir fronteras de dominio y retirar gradualmente accesos paralelos con redirects y pruebas.

## Arquitectura observada

- Framework: Laravel, Blade, Spatie Permission con teams por `project_id`.
- Tenant actual: `Project`; contexto en sesión mediante `active_project_id` y, por compatibilidad, `comercial_project_id`.
- Control plane: `routes/admin.php`, middleware `auth + superadmin`.
- Application plane: principalmente `routes/web.php` bajo `/bixoadmin`, más zonas `/bixosales`, facturación y comunicaciones.
- Entitlements actuales: `modules` + pivot `project_modules.is_active`, comprobados por `CheckModuleActive`.
- Permisos: canónicos en español (`catalog.ver`, `orders.crear`) con alias heredados todavía aceptados.
- Aislamiento: trait `HasProjectScope`, relaciones desde `Project`, middleware de membresía y algunos checks explícitos.

## Matriz AS-IS priorizada

| Función | Ubicación actual | Ruta | Controller | Model/tablas | Permisos/módulos | Vistas | Duplicación o conflicto | Dominio propuesto | Acción |
|---|---|---|---|---|---|---|---|---|---|
| Tenants | Control | `/admin/projects*` | `AdminProjectController` | `Project`, `projects` | superadmin | `admin/projects` | Settings permite operar el proyecto activo | Tenant/Control | KEEP |
| Usuarios globales/licencias | Control | `/admin/users*`, `/admin/licencias*` | `AdminUserController`, `AdminLicenseController` | `User`, app settings/sesiones | superadmin | `admin/*` | Usuarios del tenant también viven en Settings | Identity/Control | KEEP, aclarar alcance |
| Importación de productos/clientes/empleados | Control | `/admin/imports*` | `AdminImportController` | Product, Client, Employee | sólo superadmin | `admin/imports` | CRUD/importaciones ya existen en application plane | Catalog/CRM/Settings | MOVE y luego DEPRECATE |
| Turnos de empleados | Control | `/admin/projects/{project}/turnos*` | `AdminTurnosController` | Employee, WorkSchedule | sólo superadmin | `admin/turnos` | Operación de un tenant dentro de Control | Settings/HR | MOVE y luego DEPRECATE |
| Configuración global | Control | `/admin/settings` | `AdminSettingsController` | AppSetting | superadmin | `admin/settings` | Nombre similar a Settings tenant | Control | KEEP y renombrar visualmente “Plataforma” |
| Configuración tenant | Application | `/bixoadmin/settings*` | `SettingsController`, `ProjectController` | ProjectSetting, Project, módulos | `settings.*` | `settings/*` | Algunas opciones también aparecen en shells alternos | Settings | KEEP como canónica |
| Catálogo | Application | `/bixoadmin/products`, categories, services, integrations | `Catalog\*Controller` | Product, Category, Service | módulo `catalog` + `catalog.*` | `catalog/*` | POS, tienda, bot y Sales lo consumen; no hay tablas duplicadas | Catalog | KEEP/REUSE |
| Clientes/CRM | Application + comunicaciones | `/bixoadmin/clients*`, endpoints Copilot | `ClientController`, `ClientesCrmController`, `CopilotController` | Client | `clients.*` | `clients/*`, comunicaciones | Múltiples superficies sobre el mismo modelo; reglas pueden divergir | CRM | MERGE casos de uso, KEEP vista canónica |
| Cotizaciones | Application/Sales/portal/POS/bot | rutas `quotes*` y APIs | `QuoteController`, extensiones | Quote, QuoteItem | `quotes.*` | `quotes/*`, portal | Varios escritores; modelo ya centraliza numeración | Sales | REUSE servicio canónico |
| Pedidos | Application/Sales/Commerce/POS/bot | `orders*`, APIs | `OrderController`, `PosController`, API/bot | Order, OrderItem, OrderEvent | `orders.*` | `orders/*`, POS | Varios escritores y estados especializados en una tabla | Sales | KEEP; extraer casos de uso |
| Pagos y CxC | Sales/API/Application | rutas pagos/CxC | `PaymentController`, `CxcController`, `Api\PagoController` | Payment, ReceivableTerm | `payments.*` | pagos/CxC | Campos legacy de pago también existen en Order/Quote | Finance | MERGE progresivo al ledger |
| Facturación | zona propia + Application | facturación/invoices | `InvoiceController`, `Facturacion\*` | Invoice, InvoiceItem, GuiaRemision | `invoices.*` | facturación | Shell propio y permisos coexistentes | Finance | KEEP lógica, unificar shell |
| Inventario | Application, ventas, imports | inventario/kardex | `InventoryController`, `OrderController`, Product import | InventoryMovement, Product.stock | `inventory` | inventario | Escritura distribuida; `InventoryLedger` es buen punto canónico | Inventory | REUSE/encapsular |
| Proveedores | Application | company/proveedores | `ProveedorController` | Proveedor | módulo/permiso proveedores | proveedores | Sin órdenes de compra todavía | Purchasing | KEEP |
| Tienda/builder | Application + público | settings builder y storefront | StoreBuilder/Public/Experience/Navigation | Store*, Product, Order | `store`, settings | builder + templates públicas | Muchos componentes, pero catálogo compartido | Commerce | KEEP/encapsular |
| Bots/WhatsApp/IA | comunicaciones, API y procesos Node | múltiples | Bot/Wa/Copilot controllers | Bot*, Wa*, ProjectContext | permisos variados | bots/comunicaciones | Dos runtimes y varias superficies | Automation/ARIN | KEEP, definir contratos |
| Navegación | varios layouts | global | Blade | módulos/permisos | checks distintos | `layouts/app`, `comercial/layouts/app`, `bots/app`, workspace | Duplicación visual y reglas no idénticas | App Shell | MERGE |

## Hallazgos de seguridad y consistencia

### Críticos

- `Payment`, `InventoryMovement` y `Proveedor` tenían `project_id` pero no aplicaban `HasProjectScope`. La primera corrección incremental añade el mismo scope ya usado por entidades principales y una prueba de regresión.
- `HasProjectScope` no filtra si falta sesión. Esto es útil para jobs/CLI, pero significa que ningún endpoint puede depender sólo del scope: debe existir membresía/contexto antes de consultar.
- `EnsureProjectScope` supone que el modelo ofrece `allProjects()`. No debe aplicarse a modelos tenant-owned sin el trait.
- Las APIs de bot/pagos requieren una auditoría separada de autenticación: estar fuera de CSRF es correcto para webhooks, pero no sustituye firma/token, replay protection ni rate limit.

### Altos

- `SetActiveProject` mantiene dos claves de sesión y sincroniza roles durante requests. Es compatibilidad útil, pero aumenta el riesgo de contexto obsoleto y acopla identidad a navegación.
- El owner y el superadmin reciben bypasses en lugares distintos. Debe existir una política única; el superadmin debería usar impersonación explícita y auditada para el application plane.
- `CheckModuleActive` y `can:*` suelen encadenarse en rutas, mientras varias vistas reconstruyen la misma regla manualmente. Esto puede mostrar enlaces que luego terminan en 403 o, peor, omitir una de las condiciones en un endpoint nuevo.

### Medios

- `modules` contiene capacidades como `catalog`, `quotes`, `inventory`; no representa planes/productos Sales, Commerce, Operations, Finance y ARIN.
- `Order` concentra campos comerciales, cobro, cocina, delivery y lavandería. No se recomienda partir la tabla ahora; sí impedir que esos estados se sincronicen implícitamente.
- Existen rutas y vistas prototipo `/bixo/screen-*` duplicadas entre `routes/web.php` y `routes/bixo.php`; `bixo.php` no aparece cargado en `bootstrap/app.php`.

## Actual → propuesto

| Actual | Propuesto |
|---|---|
| Project | Tenant (mantener nombre físico inicialmente) |
| Admin superglobal | BIXO Control |
| Bixoadmin tenant | BIXO Platform / Settings + módulos |
| Bixosales | BIXO Sales dentro del App Shell común |
| modules/project_modules | Capability entitlements; agregar agrupación de productos después |
| Facturación separada | BIXO Finance dentro del App Shell común |
| Comunicaciones/bots | ARIN / Automation |

## Decisiones que no deben tomarse todavía

- No crear carpetas `Domains/*` vacías ni mover todos los modelos.
- No crear microservicios ni bases por producto.
- No borrar rutas legacy antes de medir uso y agregar redirects.
- No crear nuevas tablas Customer/Product/CommerceOrder.
- No partir `Order` hasta estabilizar vocabularios y servicios de transición.
