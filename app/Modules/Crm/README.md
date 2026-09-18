# Crm

**Qué va aquí:** el portal de Comunicaciones (`/bixocrm`: login, bandeja, canales de WhatsApp, clientes con puntaje), las conversaciones que se ven desde el portal comercial, el Copilot (panel y API), los pagos que llegan por la extensión de venta, la sincronización de WhatsApp y los recordatorios de carrito abandonado. `WaCanal` es el **propietario único** de las credenciales de WhatsApp (token, app secret, verify token, cifrados) y `ClienteCloud` el único que habla con la Graph API de Meta para enviar.

**Qué NO va aquí:** el motor que decide qué contesta un bot (eso es `Bots/`). `ComunicacionesAuth` (middleware) se queda en Core. `OrderFlow` es de Ventas y `LaundryFlow` de Operaciones.

**Estado:** movido el 2026-09-17 (módulo 5/8, junto con `Bots/`). El mismo día, tras los 8 módulos, llegó **`Client`** (MODULE_OWNERSHIP: el cliente es del CRM): `ClientController` (panel `bixoadmin/clients`, pipeline y la pantalla de clientes dentro del portal fiscal), el modelo y las vistas `clients/` y `facturacion/clientes` (`crm::`). Lo consumen Ventas, Finanzas, Bots y Operaciones por `App\Modules\Crm\Models\Client`.

```
Controllers/   CrmAuthController, BandejaController, CanalesController, ClientesCrmController, ClientController,
               ConversacionesController, ComunicacionesController, CopilotEmpresarialController,
               CopilotController, VentaExtensionController, WhatsappSyncController, PagoController
Models/        Client, WaCanal, WaChatbotFlow, WaConversacion, WaMensaje, WaRespuestaRapida
Support/       WhatsappCloud/ClienteCloud
Jobs/          SendAbandonedCartReminder
Commands/      SeguimientoConversaciones (bot:seguimiento), SendAbandonedCartReminders (carts:remind)
Views/         clients/, facturacion/clientes/, comunicaciones/{auth,bandeja,chatbot,clientes,configuracion,index,layouts},
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

**El CRM como producto (2026-09-17):** `Productos::activar($p, 'crm')` enciende
`clients` + `bots`. `/bixocrm/registro` da de alta un negocio "solo CRM" y lo
manda al asistente `/bixocrm/conectar` (prueba las credenciales contra Meta
con `ClienteCloud::probarCredenciales` antes de guardar). El middleware
`ComunicacionesAuth` (Core) solo abre el portal a negocios activos con el
modulo `clients`. Guia para el cliente: `docs/guias/CONECTAR_WHATSAPP_META.md`.

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
