# Arquitectura objetivo de BIXO Platform

Estado: dirección oficial propuesta para la migración incremental. Este documento define conceptos; no ordena una reescritura ni una correspondencia 1:1 con carpetas.

## Principio rector

Una capacidad de negocio tiene un único propietario técnico. Varias experiencias pueden consumirla sin duplicar modelos, tablas, validaciones, estados ni casos de uso.

## Las cinco capas

### 1. Plataforma

| Componente | Responsabilidad |
|---|---|
| BIXO Control | Administración SaaS de Eskala: tenants, planes, suscripciones, licencias, feature flags, consumo, soporte, observabilidad, auditoría global e impersonación segura. |
| BIXO Workspace | App Shell del tenant. Compone las capacidades habilitadas y autorizadas; no posee entidades de negocio. |
| Shared Platform | Identidad, tenancy, permisos, entitlements, auditoría, eventos, archivos, notificaciones, búsqueda, integraciones, observabilidad y feature flags. |

BIXO Control no opera productos, clientes, pedidos, turnos ni comprobantes de una empresa mediante CRUD paralelos. El acceso de soporte al application plane deberá usar impersonación temporal, visible y auditada.

### 2. Productos comerciales

| Producto | Resultado que compra el cliente | Dominios que puede agrupar |
|---|---|---|
| BIXO Sales | Vender y gestionar la relación comercial | CRM, Catalog, Pricing, Sales |
| BIXO Commerce | Vender en canales digitales | Commerce, Catalog, Pricing, Attribution |
| BIXO Operations | Controlar abastecimiento y cumplimiento | Inventory, Purchasing, Fulfillment |
| BIXO Finance | Cobrar y cumplir obligaciones fiscales | Payments, Receivables, Billing/Fiscal |
| ARIN | Automatizar y asistir la operación | Automation, Integrations, Reporting |

Un producto comercial agrupa capacidades. No tiene que corresponder a una carpeta, servicio, base de datos o módulo técnico único.

### 3. Dominios técnicos

```text
Identity · Tenancy
CRM
Catalog · Pricing
Sales
Payments · Receivables · Billing/Fiscal
Inventory · Purchasing · Fulfillment
Commerce
Attribution · Marketing
Automation
Reporting
Integrations
```

Los límites físicos se introducen sólo cuando reduzcan acoplamiento real. BIXO continuará como monolito modular mientras ese modelo sea suficiente.

### 4. Experiencias

| Experiencia | Propósito |
|---|---|
| Workspace | Trabajo diario del tenant según entitlements y permisos |
| POS | Venta presencial rápida; consume Sales, Catalog, Payments y Finance |
| Storefront | Experiencia pública de Commerce |
| Portal del cliente | Autoservicio autenticado sobre documentos, deuda, pedidos y entregas |
| Growth | Composición orientada a crecimiento; lee/orquesta Commerce, CRM, Attribution, Reporting y Automation |
| Pulse | Experiencia de señales deterministas y acciones recomendadas |

Workspace, Growth y Pulse no crean controladores o modelos duplicados por llevar su nombre. Son composiciones sobre capacidades propietarias.

### 5. Roles

Dueño, Administrador, Vendedor, Cajero, Operaciones, Contador y otros roles del tenant. `Administrador` es exclusivamente un rol; no es dominio, producto, aplicación ni modo de acceso.

## Modelo de acceso efectivo

```text
usuario autenticado
AND miembro/owner del tenant activo
AND producto/capacidad habilitada para el tenant
AND permiso concedido al usuario en ese tenant
AND política contextual cuando corresponda
= acceso
```

Los permisos no conceden productos no contratados. Los entitlements no conceden acciones a usuarios sin permiso.

## Relaciones, no tubería obligatoria

El ciclo comercial es un grafo. Quote, Order, Invoice, Payment, Receivable, Shipment y Delivery son entidades relacionadas con ciclos de vida independientes. POS o Commerce pueden omitir pasos que no correspondan.

```text
Customer ── Quote ──┐
    │                ├── Order ── Shipment ── Delivery
    ├── Invoice ─────┘      │
    │      ├── Receivable   └── Payment
    │      └── Payment
    └── Conversation / Activity
```

No se debe inferir que pagar completa el pedido, que facturar entrega la mercadería ni que cancelar comercialmente revierte automáticamente un comprobante fiscal.

## Read models

Customer 360, Growth y Pulse deben comenzar como agregadores/read models. No se crearán tablas gigantes que dupliquen la realidad operacional.

- Customer 360 agrega cliente, cotizaciones, pedidos, comprobantes, pagos, deuda, entregas, conversaciones y actividad.
- Growth compone métricas y acciones de Commerce, CRM, Attribution y Automation.
- Pulse recibe señales deterministas con evidencia y luego puede usar IA para explicarlas o proponer acciones.

## Mapa AS-IS → TARGET

| AS-IS | TARGET | Estrategia |
|---|---|---|
| `/admin` con control SaaS e imports/turnos | BIXO Control puro | Mantener control SaaS; mover operación al Workspace con redirects |
| `/bixoadmin` | BIXO Workspace | Conservar rutas inicialmente; cambiar concepto y shell de forma compatible |
| `/bixosales` y shell comercial | Producto/experiencia Sales dentro del Workspace | Reutilizar casos de uso y consolidar navegación |
| Shell separado de facturación | Finance dentro del Workspace | Mantener lógica fiscal; integrar experiencia gradualmente |
| Shell de comunicaciones/bots | ARIN dentro del Workspace | Mantener runtime y contratos; unificar acceso/navegación |
| `modules`/`project_modules` | Capacidades habilitadas | Preservar; agregar agrupación comercial sin renombrado destructivo |
| Checks de permisos repetidos en Blade | Evaluador único de acceso | Centralizar entitlement + permiso + política |
| `Project` | Tenant conceptual | Mantener nombre físico mientras no exista beneficio real al renombrar |
| `ProjectContext` | Facade/read service de Automation | Reducir su crecimiento y delegar casos de uso a dominios propietarios |

## Decisiones prohibidas durante la migración

- Crear `WorkspaceCustomerController`, `GrowthOrder` o equivalentes sólo por la experiencia que los muestra.
- Crear una segunda tabla de productos, clientes, pedidos o comprobantes por producto comercial.
- Convertir productos comerciales en cinco microservicios por simetría.
- Hacer de Growth o Pulse propietarios de datos operativos.
- Sincronizar estados comercial, financiero, fiscal y logístico mediante equivalencias implícitas.
- Exponer datos de tenants desde BIXO Control sin impersonación y auditoría.
