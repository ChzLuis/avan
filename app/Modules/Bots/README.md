# Bots

**Qué va aquí:** el motor de flujos (`FlowRunner`, `Carrito`, plantillas Comercial y Tienda), la IA (`Ia/`: `IA`, `InterpreteComercial`, `RouterComercial`, proveedores de chat y visión), el puntaje de leads, los dos webhooks de entrada (Meta en `/api/whatsapp/webhook` con firma HMAC, conector Baileys en `/api/bot/inbound`), el constructor de bots (pantalla del panel y del portal CRM), el estado de los conectores y el comando que crea el bot comercial en cada empresa.

**Qué NO va aquí:** nada de negocio propio: lee catálogo, pedidos y clientes de sus dueños, y guarda/envía por `Crm/` (`WaConversacion`, `WaMensaje`, `ClienteCloud`). Regla de producto 2026-09-17: **fuera del CRM ningún módulo ejecuta bots, IA ni automatizaciones**.

**Estado:** movido el 2026-09-17 (módulo 5/8, junto con `Crm/`).

```
Controllers/   BotFlowController, BotStatusController, WaBotController, BotBuilderPortalController,
               BotWebhookController (Baileys), WhatsappCloudWebhookController (Meta)
Models/        BotConfig, BotFlow, BotInstance, BotSession, BotState, BotTransition
Support/       FlowEngine/{FlowRunner,Carrito,PlantillaComercial,PlantillaTienda}, LeadScoring
Ia/            IA, IaProvider, InterpreteComercial, RouterComercial, VisionProvider, Providers/*
Commands/      BotComercialParaTodos (bot:comercial-todos)
Views/         bot-builder/, bot-flows/, bots/, comunicaciones/bots/
```

`app/Ia` pasó a `App\Modules\Bots\Ia` con el mismo nombre de clases. Vistas
bajo `bots::` (el constructor dentro del CRM es `bots::comunicaciones.bots.*`).

**Quién lo usa desde fuera:**
- `Crm/`: `IA` y `LeadScoring` (Copilot, clientes, sincronización), `BotSession`
  (seguimiento), `FlowRunner` (`ClienteCloud` traduce sus respuestas).
- `ProjectObserver` crea el `BotFlow` comercial al nacer una empresa.
- `CheckLaundryOverdue` lee `BotInstance` (avisos de lavandería; pasará a Operaciones).
- **Finanzas** usa los proveedores de **visión** (`VisionProvider`, `OpenAiVision`,
  `GeminiVision`, `AnthropicVision`) para leer comprobantes. No es un bot: es
  infraestructura de IA. Deuda: cuando haya un tercer consumidor, mover
  `Ia/Providers` + `VisionProvider`/`IaProvider` a Core (`App\Support\Ia`).

**Red:** `tests/Feature/BotsModuloTest` (5): constructor del CRM y estado de
bots con su vista, webhook de Meta vivo, comando registrado y guardián de
frontera. El motor lo cubren 10 tests propios (`Bot*Test`,
`WhatsappCloudWebhookTest`, `PlantillaTiendaTest`, `RouterComercialTest`).

**Rojos conocidos que NO son de la mudanza** (ya estaban antes, misma cifra):
`BotEditorBloquesTest` (1), `BotPredeterminadoTest` (3) y `BotWebhookTest` "el
panel lee el qr" (1): las fixtures no activan el módulo `clients` que gatea
`bot-flows.*`.

**Para desplegar a ARIN:** junto con `Crm/` y los módulos 1–4. Además de lo
habitual (nuevos + borrar viejos + `composer dump-autoload -o` + caché de
rutas), el conector Baileys (`/home/arindg/bixo-baileys`) llama por URL, no
por clase: no necesita cambios.

## Funciones nativas de Meta (WhatsApp oficial)

- Bloque `opciones` con 3 o menos: botones de respuesta; el id es el numero de la opcion.
- Bloque `intencion`: `botones: [{titulo (<=20), siguiente}]` -> botones con id `btn:<bloque>`
  (salto directo sin IA); `enlace: {url, boton, titulo}` -> boton que abre la URL (`cta_url`);
  si hay ambos, los botones van en un segundo mensaje con `botones_texto`.
- `ClienteCloud::marcarLeido` envia el indicador "escribiendo...".
- Por Baileys (sin `wa_canal_id`) `BotWebhookController` aplana todo al `fallback` de texto.

- Acuses (`statuses`) de Meta: `procesarAcuses` sube el estado del WaMensaje (nunca retrocede);
  `failed` -> `fallido` + `error` legible. `anotarEnvios` guarda el id de Meta en cada saliente.
- `opciones` de 4 a 10 salen como lista nativa (id = numero de la fila).
- Toques: el motor recibe el id (`btn:x` o numero); la bandeja guarda el titulo (`texto_visible`).
