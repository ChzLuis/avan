# Finanzas

**Qué va aquí:** Payment, ReceivableTerm y el `Ledger` de pagos (CxC), Caja/CajaMovimiento, Invoice/InvoiceItem, GuiaRemision/Item, Certificado, LecturaComprobante y el `Lector/`, todo lo de SUNAT (`Sunat/`, `ApisPeruService`, `NubefactService`, `CatalogoDocumentos`), Registro de Ventas, los jobs y comandos de comprobantes.

**Qué NO va aquí:** Crear pedidos ni cotizaciones (los recibe de Ventas). `LineMath` y `components/doc/*` (hoja, encabezado, tarjeta) son de Core: los comparten pedidos y cotizaciones. Las pantallas de clientes, pedidos y cotizaciones que se ven dentro del portal fiscal (`resources/views/facturacion/{clientes,pedidos,cotizaciones}`) son de Ventas.

**Inventario y decisiones para la mudanza:** ver `docs/architecture/BIXO_HANDOFF.md` (módulo 4/8).

**Orden en la mudanza:** 4.º — Facturacion/ ya está medio agrupado.

**Estado:** vacío. El código sigue en su sitio actual hasta que le toque, según `docs/architecture/BIXO_MODULARIZACION_PLAN.md`.
