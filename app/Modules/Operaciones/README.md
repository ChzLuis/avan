# Operaciones

**Qué va aquí:** lo que pasa "en el local": la agenda y las citas (`Appointment`, `Availability`, `BlockedDate`), mesas, reservas, delivery, el mapa operativo (`OperationalMap`, `OperationalObject`, `OperationalEvent`, `OperationalRequest`) y el flujo de lavandería (`LaundryFlow`, aviso de prendas vencidas `CheckLaundryOverdue`).

**Qué NO va aquí:** el pedido en sí (Ventas: delivery y cocina lo leen), el cobro (Finanzas), `Sede` (es configuración del tenant: Core), el cliente (Crm).

**Estado:** movido el 2026-09-17 (paso posterior a los 8 módulos numerados; el plan lo dejaba "sin número: decidir al llegar". Decisión: sigue siendo un dominio, con estas 5 pantallas del portal comercial y la agenda del panel).

```
Controllers/   Agenda, Delivery, Mesa, OperationalMap, Reserva
Models/        Appointment, Availability, BlockedDate, OperationalEvent, OperationalMap,
               OperationalObject, OperationalRequest
Support/       LaundryFlow
Commands/      CheckLaundryOverdue
Views/         agenda/, mapa/, comercial/{delivery, mesas, reservas}   (operaciones::)
```

**Quién lo usa desde fuera:** `Service` (Catalogo) tiene citas; `Project` (relaciones);
el layout del portal comercial (Core) consulta `LaundryFlow` para el menú de
lavandería; `Order` (Ventas) lleva los campos de entrega (MODULE_OWNERSHIP:
"Fulfillment, temporalmente en orders").

**Deudas conocidas:** `ExternalRequest` e `InternalRequest` (Core) no los usa
nadie fuera de sus modelos: confirmar si están muertos y retirarlos.

**Red:** `tests/Feature/OperacionesModuloTest`: agenda, mapa, delivery, mesas y
reservas con su vista, comando registrado y guardián de frontera.

**Para desplegar a ARIN:** con el resto de módulos (ver `app/Modules/README.md`).
