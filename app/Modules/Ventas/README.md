# Ventas

**Qué va aquí:** pedidos (`Order`, `OrderItem`, `OrderEvent`, su flujo `OrderFlow`/`OrderStatus`/`OrderAbilities`, cocina, etiquetas, PDF), cotizaciones (`Quote`, `QuoteItem`, `QuoteStatus`, `QuoteAbilities`), propuestas (`Proposal` y su página pública), el POS y la venta express, el portal del cliente (`/c/{token}`, `b/{slug}`), revendedores (`ResellerPrice`, catálogo y precios propios), interacciones comerciales (`SalesInteraction`), carritos abandonados (`AbandonedCart`: una venta no cerrada; la escribe el checkout de Tienda y la recuerda el CRM), los reportes comerciales, el tablero comercial del panel y dos integraciones que traen ventas de fuera: WooSync (pedidos de WooCommerce) y TicketsWp (tickets de un WordPress).

**Qué NO va aquí:** comprobantes, caja ni cuentas por cobrar (Finanzas; `Cobranza` se movió allí en este paso), stock (Inventario), el cliente (`Client`: CRM), cómo se pinta la tienda (Tienda), ni la **cara** del portal comercial: `Comercial\{Auth,Dashboard}Controller`, `comercial/{layouts,login,dashboard,panel,monitoreo}`, `AvisosPortal` y `DashboardController` son Core, porque el portal `/bixosales` lo comparten Ventas, Finanzas, Crm y Operaciones. Delivery, mesas y reservas (`comercial/{delivery,mesas,reservas}`) son de Operaciones.

**Estado:** movido el 2026-09-17 (módulo 8/8).

```
Controllers/   Order, Quote, Pos, Proposal, Portal, PortalCliente, Reseller, WooSync, Reporte,
               DashboardComercial, TicketsWp
Models/        Order, OrderItem, OrderEvent, Quote, QuoteItem, Proposal, ResellerPrice,
               SalesInteraction, AbandonedCart
Support/       OrderFlow, OrderStatus, OrderAbilities, QuoteAbilities, QuoteStatus
Views/         orders/, quotes/, pos/, proposals/, reseller/, portal-cliente/, ventas/,
               dashboard/comercial, comercial/{reportes/, woo-orders, tickets-wp},
               facturacion/{pedidos, cotizaciones}   (pantallas de ventas DENTRO del portal fiscal)
```

Vistas bajo `ventas::`. Los nombres de ruta `orders.*`, `quotes.*`, `pos.*`,
`proposals.*` y `facturacion.*` **no** son vistas: el generador solo renombró
literales que coinciden con una vista real (323 descartados).

**Quién lo usa desde fuera:** `Client` y `Project` (relaciones), Finanzas
(`Invoice` nace de un `Order`/`Quote`; `Ledger` cobra pedidos), Tienda (el
checkout crea `Order`; el portal de cotización), Crm (recordatorios de
carrito, ventas por extensión), Bots (`Carrito`, pedidos del bot), Inventario
(kardex por pedido), Operaciones (delivery lee pedidos), `Comercial\Dashboard`
(`OrderFlow`, `QuoteStatus`).

**Deudas conocidas:**
- Los botones de WhatsApp dentro de un pedido siguen llamando al CRM
  directamente; la inversión por eventos (Ventas anuncia, Crm decide) sigue
  pendiente (ver `BIXO_MODULARIZACION_PLAN.md`).
- `TicketsWpController` lleva la URL y la clave de UN cliente en constantes.

**Red:** `tests/Feature/VentasModuloTest`: pedidos, cotizaciones, POS y
reportes con su vista, tablero comercial y propuestas del panel, y guardián de
frontera. La lógica la cubren ~50 tests propios (`Order*`, `Quote*`, `Pos*`,
`PortalCliente*`, `Reseller*`, `VentaExpress*`...).

**Para desplegar a ARIN:** con los módulos 1–7 (ver `app/Modules/README.md`).
