# Crm

**Qué va aquí:** el portal de Comunicaciones (`/bixocrm`: login, bandeja, canales de WhatsApp, clientes con puntaje), las conversaciones que se ven desde el portal comercial, el Copilot (panel y API), los pagos que llegan por la extensión de venta, la sincronización de WhatsApp y los recordatorios de carrito abandonado. `WaCanal` es el **propietario único** de las credenciales de WhatsApp (token, app secret, verify token, cifrados) y `ClienteCloud` el único que habla con la Graph API de Meta para enviar.

**Qué NO va aquí:** el motor que decide qué contesta un bot (eso es `Bots/`). `ComunicacionesAuth` (middleware) se queda en Core. `OrderFlow`/`LaundryFlow` (flujos de pedido y lavandería) se quedan donde están hasta que les toque a Ventas/Operaciones.

**Estado:** movido el 2026-09-17 (módulo 5/8, junto con `Bots/`).

```
Controllers/   CrmAuthController, BandejaController, CanalesController, ClientesCrmController,
               ConversacionesController, ComunicacionesController, CopilotEmpresarialController,
               CopilotController, VentaExtensionController, WhatsappSyncController, PagoController
Models/        WaCanal, WaChatbotFlow, WaConversacion, WaMensaje, WaRespuestaRapida
Support/       WhatsappCloud/ClienteCloud
Jobs/          SendAbandonedCartReminder
Commands/      SeguimientoConversaciones (bot:seguimiento), SendAbandonedCartReminders (carts:remind)
Views/         comunicaciones/{auth,bandeja,chatbot,clientes,configuracion,index,layouts},
               copilot/, comercial/conversaciones
```

`Comunicaciones\AuthController` se renombró a `CrmAuthController`: al perder la
subcarpeta, el nombre tenía que decir de qué portal es y no chocar con los
`AuthController` de la raíz. Vistas bajo `crm::`.

**Quién lo usa desde fuera:** `WaCanal` (Ajustes del panel, `ModulosPortal`, el
layout del portal comercial, y `Bots/` para resolver el canal de un webhook);
`WaConversacion`/`WaMensaje` (`Bots/` registra lo que entra y sale);
`ClienteCloud` (`Bots/` responde por Meta). Es la relación esperada: Bots
decide, Crm guarda y envía.

**Deudas conocidas:**
- `ComunicacionesController` no tiene rutas: solo `web.php` lo importa para
  pintar `comunicaciones.index`. Confirmar si está muerto y retirarlo.
- `WaChatbotFlow` y la pantalla `chatbot` no tienen motor que las ejecute;
  decidir si se consolidan en el constructor de `Bots/` o se retiran.

**Red:** `tests/Feature/CrmModuloTest` (5): login, bandeja, canales, clientes con
su vista, comandos registrados y guardián de frontera. La lógica la cubren
`CanalesCredencialesMetaTest`, `WaCanalCredencialesTest`, `BotFase2CierreTest`,
`PagoBotContratoTest`, `PaymentsStabilizationTest`.

**Para desplegar a ARIN:** junto con `Bots/` y los módulos 1–4: archivos nuevos
+ borrar los viejos + `composer dump-autoload -o` + caché de rutas. Antes:
cola `jobs` vacía (el job de carritos se encola por nombre de clase).
