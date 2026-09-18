# Finanzas

**Qué va aquí:** comprobantes electrónicos (`Invoice`/`InvoiceItem`, notas, baja), guías de remisión, todo lo de SUNAT (`Support/Sunat/`, `ApisPeruService`, `NubefactService`, `CatalogoDocumentos`), el lector de comprobantes (`Support/Lector/`, `LecturaComprobante`), pagos y cuentas por cobrar (`Payment`, `ReceivableTerm`, el `Ledger` de pagos, CxC), caja (`Caja`, `CajaMovimiento`), certificados digitales, los jobs y comandos de comprobantes.

**Qué NO va aquí:** crear pedidos ni cotizaciones (los recibe de Ventas). `LineMath` y `components/doc/*` (hoja, encabezado, tarjeta impresa) son de Core: los comparten pedidos y cotizaciones. Las pantallas de clientes, pedidos y cotizaciones que se ven **dentro del portal fiscal** (`resources/views/facturacion/{clientes,pedidos,cotizaciones}`) son de Ventas y se quedan ahí. `FacturacionAuth` (middleware) y `App\View\Components\FacturacionLayout` (componente de clase, solo repunta su vista) se quedan en Core.

**Estado:** movido el 2026-09-17 (módulo 4/8, el primero grande).

```
Controllers/   Invoice, Payment, Caja, Cxc, Certificado, LectorComprobante,
               GuiaRemision, Nota, FacturacionAuth, FacturacionDashboard
Models/        Invoice, InvoiceItem, Payment, ReceivableTerm, Caja, CajaMovimiento,
               Certificado, LecturaComprobante, GuiaRemision, GuiaRemisionItem
Support/       ApisPeruService, NubefactService, CatalogoDocumentos, Ledger, Cobranza (desde el paso 8),
               Sunat/*, Lector/*
Jobs/          SendInvoiceToSunat, DarDeBajaEnSunat, EnviarGuiaASunat  (pasan IDs, no modelos)
Commands/      ArchivarComprobantes, ReintentarComprobantes  (los registra el ModulosServiceProvider)
Views/         invoices/, cxc/, certificados/, facturacion/{portada,dashboard,login,login-general,facturas,guias,layouts}
```

`Facturacion\AuthController` y `Facturacion\DashboardController` se renombraron a
`FacturacionAuthController` y `FacturacionDashboardController`: al perder la
subcarpeta, el nombre tenía que decir de qué portal son y no chocar con los de
la raíz.

Vistas bajo `finanzas::`. Ojo con dos sitios donde el nombre de la vista se
elige por **ternario** (plantilla de PDF clásica o simple, en facturas y guías):
no van dentro de `view(...)` y hay que actualizarlos a mano si se añade otro.

**Quién lo usa desde fuera:** el `Ledger` de pagos (POS, pedidos, clientes, API
del bot), `Invoice` (clientes, pedidos, cotizaciones al convertir, tablero del
superadmin, `ModulosPortal`), `Payment`/`ReceivableTerm` (cobranza en ventas).
Uso legítimo de entidades públicas del propietario; ninguno escribe stock ni
estados fiscales por su cuenta.

**Red:** `tests/Feature/FinanzasModuloTest` (6): portada fiscal, CxC, facturas,
certificados y caja con su vista, y guardián de frontera. La lógica fiscal la
cubren 40 tests propios (emisión, notas y baja, guías, lector, SUNAT, ledger).

**Para desplegar a ARIN:** archivos nuevos + borrar los viejos (10 controladores,
10 modelos, 4 soportes + 2 carpetas, 3 jobs, 2 comandos, 4 carpetas de vistas y
4 archivos sueltos de `facturacion/`) + `composer dump-autoload -o` + caché de
rutas. **Antes**: comprobar que la cola (`jobs`) esté vacía — los jobs pasan
IDs, pero un job encolado con el nombre de clase viejo no se encontraría. Junto
con Personas, Control e Inventario.
