# Centro Comercial — Análisis y plan de implementación

Fecha: 2026-08-05 · Spec del usuario: rediseño de "Panel Comercial" + "Centro de Pedidos".

## 1. Arquitectura detectada

- **Laravel 12**, multi-tenant por `project_id` (trait `HasProjectScope`), Blade + Alpine (sin build JS), Tailwind por CDN en panel.
- **Permisos**: Gate `modulo.accion` (PermissionsSeeder: `orders.ver/crear/editar/eliminar/cancelar`, `quotes.*`, `pos.usar`) + middleware `module:orders|quotes` por proyecto. Roles: admin, ventas, cajero, lectura (ver seeder).
- **Pedidos** (`orders`): status, payment_status, payment_method, payment_condition, sales_channel, payment_reference, payment_proof, coupon/discount/shipping, delivery_*, kitchen_*, laundry_*, `created_by`, `client_id`. Items en `order_items`. Vista `orders/index.blade.php` (619 líneas): maestro-detalle Alpine, filtros cliente, edición inline, ya muestra payment_proof. Controller carga TODO sin paginar.
- **Cotizaciones** (`quotes` + `quote_items`): status, valid_until, token público, sent_at, client_doc_type/number. `QuoteController` resource + `send` + portal público. (OJO: `Proposal` es otra cosa — proformas de venta de tiendas Eskala; NO tocar.)
- **Comprobantes**: `InvoiceController` (+pdf) y `SendInvoiceToSunat` (APIsPERU) ya existen.
- **Paneles**: `/dashboard-comercial` (bixoadmin, DashboardComercialController) y portal vendedores `/bixosales` (`Comercial\DashboardController`, layout propio azul marino, 844 líneas de dashboard con ventas del bot/rifas/Woo).
- **POS**: crea pedidos y cotizaciones (`pos.quote`). Aprobación de pagos Yape/Plin del bot: `Api\PagoController` (pendientes/aprobar/rechazar).
- **WhatsApp**: sin API oficial en panel; patrón existente = `wa.me/+51{n}?text=` (checkout, rifas). Bot Baileys aparte.

## 2. Qué ya existe y se REUTILIZA (no se re-crea)

| Requisito de la spec | Ya existe |
|---|---|
| Estado operativo vs pago separados | `orders.status` + `orders.payment_status` ✓ |
| Comprobante de pago con evidencia | `payment_proof` (+ subida pública del checkout) ✓ |
| Canal de venta / responsable | `sales_channel`, `created_by` ✓ |
| Cotizaciones con vencimiento y token | `quotes.valid_until`, `token`, `sent_at` ✓ |
| Boleta/Factura/PDF | InvoiceController + SUNAT job ✓ |
| Permisos por rol | Gates + seeder ✓ |
| Maestro–detalle en pedidos | vista actual ✓ (se amplía, no se reemplaza) |
| Pagos Yape/Plin aprobación | Api\PagoController ✓ |

## 3. Brechas reales a construir

1. `orders/index`: KPIs superiores, pestañas con contadores, cotizaciones unificadas, filtros servidor + paginación real, stepper, acciones WhatsApp con plantillas, registrar pago (parcial→`payment_status=partial`), export CSV, semáforo de urgencia.
2. Conversión cotización→pedido (endpoint nuevo `quotes.convert`) copiando cliente+items sin reescribir.
3. Auditoría: nueva tabla `order_events` (project_id, order_id nullable, quote_id nullable, user_id, action, meta JSON) — no existe equivalente (verificado).
4. Panel Comercial: KPIs reales (día/mes/meta/ticket/por cobrar/por vencer), gráfico ventas vs meta (SVG inline, sin librerías), ranking por `created_by`, alertas accionables. Meta comercial: nuevo setting de proyecto `sales_goal_month` (no existe).
5. Embudo/pipeline: sin CRM de leads en este módulo → se construye con estados equivalentes (quotes draft→sent→seen(sent_at+token visto)→accepted→converted; orders pending→…→completed) como permite la spec.

## 4. Archivos a modificar / crear

- MOD `app/Http/Controllers/OrderController.php` (index con KPIs+filtros+paginación; endpoints pago/estado/export)
- MOD `app/Http/Controllers/QuoteController.php` (convert, KPIs)
- MOD `resources/views/orders/index.blade.php` (pestañas, KPIs, stepper, WhatsApp, acciones)
- MOD `app/Http/Controllers/DashboardComercialController.php` + su vista (panel nuevo)
- NUEVO `database/migrations/*_create_order_events_table.php`
- NUEVO `app/Models/OrderEvent.php` + helper de registro
- MOD `routes/web.php` (orders/{order}/pay, /status, /events, orders/export, quotes/{quote}/convert)
- MOD `database/seeders/PermissionsSeeder.php` solo si falta acción (revisar `orders.cancelar` ya existe)
- Tests nuevos: OrderCenterTest, QuoteConvertTest, ComercialPanelTest

## 5. Migraciones

- `order_events` (auditoría). Nada más: el resto usa columnas existentes. `partial` es valor nuevo del string `payment_status` (sin migración).

## 6. Riesgos y mitigación

- Vista orders la usan panel Y portal bixosales (`portalLayout`) → mantener ambas rutas y probar las dos.
- Lavandería/cocina/delivery usan la misma tabla → no tocar sus estados; el stepper solo aplica a pedidos "retail".
- Cargar todo sin paginar hoy: al paginar, el detalle Alpine debe seguir funcionando → detalle por fila embebida (como hoy) + paginación servidor.
- `status` valores actuales: pending/processing/completed/cancelled → el stepper mapea sobre estos + payment_status; NO se renombran valores existentes.

## 7. Orden de implementación (cada fase = commit + deploy + verificación)

1. **F1 Centro de Pedidos base**: KPIs + pestañas + filtros servidor + paginación + cotizaciones unificadas.
2. **F2 Detalle**: stepper, cliente estructurado, registrar pago (parcial/total), semáforo, WhatsApp con plantillas.
3. **F3 Cotizaciones**: convert→pedido, vencimientos, estados.
4. **F4 Auditoría**: order_events en cada transición + historial en detalle.
5. **F5 Panel Comercial**: KPIs, gráfico meta, ranking, alertas (enlazan al Centro con filtros).
6. **F6 Export CSV + acciones masivas + responsive móvil (cards) + pruebas completas.**

Regla transversal: S/ en todos los montos, colores estado (verde=ok, azul=en curso, amarillo=pendiente, rojo=urgente, gris=cerrado), sin datos simulados.
