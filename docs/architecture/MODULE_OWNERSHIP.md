# BIXO Platform — propiedad de módulos y entidades

Estado: arquitectura objetivo incremental. Los nombres físicos actuales se conservan hasta que exista una migración probada.

> La traslación de este mapa a carpetas físicas (`app/Modules/`) está
> planificada en [BIXO_MODULARIZACION_PLAN.md](BIXO_MODULARIZACION_PLAN.md).

## Regla de diseño

Cada entidad tiene un único propietario. Otros módulos pueden consultar o ejecutar casos de uso públicos del propietario, pero no crear un segundo modelo, tabla, CRUD o vocabulario de estados.

| Entidad actual | Propietario | Consumidores principales | Fuente canónica actual |
|---|---|---|---|
| `Project` | Tenant/Core | Todos | `projects` |
| `User`, `ProjectMember`, roles | Identity | Todos | `users`, `project_members`, tablas Spatie con team por proyecto |
| `Module`, `ProjectModule` | Tenant Entitlements | App Shell, middleware | `modules`, `project_modules` |
| `Client` | CRM | Sales, Commerce, Automation, Finance | `clients` |
| `Product`, `Category`, `Service`, `ProductImage` | Catalog | Sales, Commerce, Inventory, Automation | tablas homónimas |
| `Quote`, `QuoteItem` | Sales | Commerce, Finance, Automation | `quotes`, `quote_items` |
| `Order`, `OrderItem`, `OrderEvent` | Sales | Commerce, Finance, Inventory, Fulfillment | `orders`, `order_items`, `order_events` |
| `Payment`, `ReceivableTerm` | Finance | Sales, POS, Automation | `payments`, `receivable_terms` |
| `Invoice`, `InvoiceItem`, `GuiaRemision` | Finance/Fiscal | Sales, Fulfillment | tablas homónimas |
| `InventoryMovement`, `Product.stock` | Inventory | Catalog, Sales, Purchasing | `inventory_movements`; `InventoryLedger` es el escritor canónico |
| `Proveedor` | Purchasing | Inventory, Finance | `proveedores` |
| Campos de entrega en `Order` | Fulfillment | Sales, Commerce | temporalmente `orders`; extraer sólo cuando haya casos de uso suficientes |
| `Store*`, `DesignTemplate`, `Coupon`, `Promotion`, `Review` | Commerce | Catalog, Sales | tablas `store_*` y relacionadas |
| `Bot*`, `Wa*`, proveedores IA | Automation/ARIN | CRM, Sales, Catalog, Commerce | modelos actuales y `ProjectContext` |
| `ProjectSetting`, `Sede`, usuarios/roles del proyecto | Settings | Todos | configuración del tenant |
| `AccessEvent`, logs globales, licencias, demos | BIXO Control | Eskala | modelos/configuración globales |

## Límites obligatorios

- BIXO Control no crea productos, clientes, empleados ni turnos. El soporte operativo debe entrar mediante una futura impersonación auditada del tenant.
- Un permiso de usuario no activa un producto. El acceso efectivo siempre es: pertenencia al tenant + entitlement activo + permiso del usuario.
- `Order.status`, `payment_status`, estado fiscal y entrega no se derivan unos de otros automáticamente.
- Commerce genera o convierte al `Order` canónico; no tendrá una tabla paralela de pedidos definitivos.
- El inventario cambia mediante `InventoryLedger`; no mediante escrituras directas a `products.stock`.

## Mapa de productos comerciales propuesto

| Producto | Capacidades actuales relacionadas |
|---|---|
| BIXO Sales | `clients`, `quotes`, `orders`, `pos` |
| BIXO Commerce | `store`, `pages`, catálogo público, builder, cupones/promociones |
| BIXO Operations | `inventory`, `proveedores`, `logistics`, compras futuras |
| BIXO Finance | pagos/CxC, `invoices`, SUNAT, caja |
| ARIN | bots, comunicaciones, Copilot e IA |

Los registros actuales de `modules` son capacidades, no todavía productos comerciales. No se deben renombrar como productos sin una capa de agrupación y compatibilidad.
