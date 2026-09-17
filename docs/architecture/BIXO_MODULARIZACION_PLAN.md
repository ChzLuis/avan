# Plan de modularización: un producto, una carpeta

Estado: **propuesta aprobada, sin ejecutar**. Fecha: 2026-09-17.

## Por qué

Hoy `app/Http/Controllers/` tiene **57 archivos sueltos** frente a 41 repartidos
en carpetas, y `app/Models/` tiene **88 modelos** en un solo nivel. En ese
montón conviven productos distintos: catálogo, POS, facturación SUNAT, CRM de
WhatsApp, constructor de tiendas, rifas, RR. HH.

No hay ninguna frontera física entre ellos. Las consecuencias ya se están
pagando:

- Un cambio en las rutas de bots (`module:clients`) tumbó un test del QR, sin
  que quien lo hizo pudiera preverlo.
- Se creó una tabla `whatsapp_cloud_configs` duplicando `wa_canales` porque
  buscar "quién es dueño de las credenciales de WhatsApp" no era evidente.
- Un programador nuevo (o una IA) que abre el repo no tiene forma de saber qué
  archivos forman un producto.

`MODULE_OWNERSHIP.md` ya define **quién es dueño de qué**. Este plan solo
traslada esa decisión, que hoy vive en un documento, a la estructura de
carpetas, donde el código la hace cumplir.

## Qué NO es esto

- **No** es separar repositorios. Un solo repo, un solo `deploy.py`, una sola
  base de datos.
- **No** es microservicios. Sigue siendo un monolito, pero modular.
- **No** es renombrar tablas. Las migraciones y los nombres de tabla se quedan
  como están; solo se mueven clases PHP y vistas.
- **No** cambia ninguna URL. Las rutas siguen exactamente igual.

## Estructura objetivo

```
app/Modules/
  Core/          Project, User, Employee, Module, ProjectModule, Settings
  Catalogo/      Product, Category, Service, ProductVariant, CatalogList…
  Ventas/        Client, Quote, Order, Proposal, POS, Caja
  Finanzas/      Payment, Invoice, GuiaRemision, SUNAT, CxC
  Inventario/    InventoryMovement, InventoryLedger, Proveedor
  Tienda/        Store*, DesignTemplate, Coupon, Promotion, constructor
  Crm/           WaCanal, WaConversacion, WaMensaje, bandeja
  Bots/          BotFlow, FlowRunner, webhooks, proveedores de IA
  Operaciones/   OperationalMap, Agenda, Mesa, Reserva, Delivery
  Personas/      HR, Attendance, WorkSchedule, UserGroup
  Control/       AccessEvent, licencias, demos (BIXO Control)
```

Cada módulo contiene sus `Controllers/`, `Models/`, `Support/` y `Views/`.

Los productos comerciales (BIXO Sales, Commerce, Operations, Finance, ARIN)
**agrupan** módulos; no son carpetas por sí mismos. Un producto puede usar
varios módulos y un módulo puede servir a varios productos — por eso la
carpeta es el dominio, no el nombre comercial.

## Precondiciones (no empezar sin esto)

Mover código con el terreno inestable convierte cualquier fallo en una
búsqueda a ciegas: no se sabe si lo rompió la mudanza o ya estaba roto.

1. **Árbol limpio.** Hoy hay trabajo de otras sesiones sin commitear
   (reestructuración de `bots-flow`, ficha técnica de productos,
   `LectorComprobanteController`). Commitear o descartar antes.
2. **Suite verde, o explicada.** Hay 27–35 tests en rojo. No hace falta
   arreglarlos todos, pero sí tener escrito por qué falla cada uno, para
   distinguir un fallo nuevo de uno heredado.
3. **Meta cerrado.** Decidir cuál de los tres webhooks de WhatsApp se queda
   (ver `project_webhooks_whatsapp_sin_firma`).

## Orden de ejecución

De menos a más acoplado. Cada paso es un commit propio y desplegable; si algo
sale mal se revierte solo ese módulo.

| # | Módulo | Riesgo | Por qué en este orden |
|---|---|---|---|
| 1 | `Personas` | bajo | HR/Attendance casi no lo usa nadie más |
| 2 | `Control` | bajo | superadmin, aislado del resto |
| 3 | `Inventario` | medio | escritor único (`InventoryLedger`) ya definido |
| 4 | `Finanzas` | medio | `Facturacion/` ya está medio agrupado |
| 5 | `Crm` + `Bots` | medio | ya tienen carpeta propia parcial |
| 6 | `Tienda` | alto | el constructor toca muchas vistas |
| 7 | `Catalogo` | alto | lo consume casi todo |
| 8 | `Ventas` | alto | el más entrelazado; va al final |

`Core` no se mueve: se queda donde está y el resto depende de él.

## Método por módulo

1. Crear `app/Modules/<X>/` con `Controllers/`, `Models/`, `Views/`.
2. Mover los archivos **sin editar su contenido** (`git mv`, que conserva el
   historial).
3. Ajustar `namespace` y los `use` que apunten a ellos.
4. Registrar las vistas del módulo con un espacio de nombres propio
   (`view('crm::bandeja')`), o dejarlas en `resources/views/` si mover la vista
   obliga a tocar demasiados sitios de golpe.
5. Correr la suite completa. Comparar contra la lista de fallos heredados.
6. Cargar el panel en el navegador y recorrer las pantallas del módulo.
7. Commit del módulo, con su nombre en el mensaje.

**Un módulo por sesión.** No encadenar dos: si el segundo rompe algo, ya no se
sabe cuál fue.

## Regla: bots, IA y automatizaciones viven SOLO en el CRM

Decisión de producto (2026-09-17): todo lo de bots, IA y automatizaciones se
concentra en el CRM. Los demás productos —catálogo, ventas, facturación,
tienda— no contienen nada de eso.

Hoy **116 rutas de bot/IA viven fuera del CRM**, repartidas así:

| Origen | Rutas | Qué es |
|---|---|---|
| `bixoadmin/bots*` | 33 | constructor de flujos, estado, configuración |
| `api/copilot/*` | 26 | Copilot de la extensión de Chrome |
| `bixosales/pedidos-bot*` | 18 | pedidos del bot + acciones de WhatsApp |
| `wa/*`, `whatsapp/*` | 19 | webhooks entrantes |
| `rifas/*` | 10 | sorteo (ver más abajo: se retira) |
| `orders/{order}/wa-*` | 4 | avisos de WhatsApp desde un pedido |

### Dos cosas distintas que no hay que confundir

**El motor** (FlowRunner, webhooks, Copilot, constructor de bots, proveedores
de IA) se va **entero** al módulo `Crm`/`Bots`. Ahí no hay matices.

**Los botones de WhatsApp dentro de otros productos** sí los tienen. Por
ejemplo `orders/{order}/wa-action` avisa al cliente de que su pedido salió:
arrancarlo dejaría al vendedor sin una herramienta que usa a diario.

La solución no es borrarlos sino **invertir la dependencia**:

- Ventas **no** llama a WhatsApp. Ventas anuncia un hecho: «pedido enviado».
- El CRM escucha ese hecho y decide si manda un mensaje, y con qué plantilla.
- Ventas no sabe que WhatsApp existe; si mañana el aviso es por correo o SMS,
  Ventas no cambia.

En Laravel esto son eventos de dominio (`PedidoEnviado`) con un listener en el
CRM. El botón sigue en la pantalla del pedido; lo que cambia es quién hace el
trabajo detrás.

**Regla práctica para revisiones:** si un archivo fuera de `Modules/Crm` o
`Modules/Bots` menciona `Http::post(...graph.facebook...)`, `WaCanal`,
`FlowRunner` o un proveedor de IA, está mal colocado.

## Regla de nombres: el nombre dice qué hace

Cada clase, ruta, tabla y carpeta se llama por **la función que cumple**, no
por el cliente que la encargó, el proyecto que la originó ni la tecnología con
la que se implementó.

El caso que motiva la regla: `RifaController` acabó gestionando los pedidos
que llegan por el bot. Quien lo abre no encuentra lo que busca, y quien busca
"pedidos" no lo encuentra a él. El nombre mentía sobre el contenido.

| En vez de | Usar |
|---|---|
| `RifaController` para pedidos del bot | `PedidoEntranteController` |
| `rifa_ventas` para pedidos | `pedidos_bot` |
| `WaCanal` (tecnología) | correcto si de verdad es específico de WhatsApp |
| `TicketsWp`, `WooSync` | el nombre del proveedor **sí** vale para integraciones |

Matices, porque la regla no es absoluta:

- **Integraciones sí llevan el nombre del proveedor**: `WooSync`, `ApisPeru`,
  `Baileys`. Ahí el proveedor *es* la función.
- **El dominio manda sobre la implementación**: `NotificadorPedido` mejor que
  `WhatsAppSender` si mañana puede ser correo.
- **Español, como el resto del proyecto**, salvo términos técnicos asentados
  (`webhook`, `token`, `ledger`).

Al mover un archivo, si el nombre ya no describe lo que hace, se renombra en
ese mismo commit. Es el momento más barato para hacerlo.

## Lo que hay que resolver por el camino

- **Código de sorteos: fuera.** No es parte de BIXO — fue un encargo puntual
  que se coló en el repo y hoy vive como producto aparte en
  `c:\xampp\htdocs\sorteos`. Se retiran `RifaController`, `Rifa`, `RifaVenta`,
  sus vistas, sus 10 rutas y las 12 de `bixosales/pedidos-bot` (gestión de
  pedidos del bot, que pasa al CRM). La palabra no debe quedar en ninguna
  parte: `grep -ri rifa` sobre `app/`, `routes/` y `resources/` tiene que
  salir vacío.

  **Dos comprobaciones en ARIN antes de ejecutarlo:**
  1. Filas de `rifa_ventas` en producción (en local hay 0, y eso engaña). Si
     hay datos reales, se exportan antes de tocar la tabla.
  2. Si `/whatsapp/webhook` está recibiendo de Meta hoy —
     `WaWebhookController` usa `RifaVenta`, así que retirarlo con el webhook
     vivo tumbaría el bot.

  El código se retira; **los datos de producción no se borran sin exportarlos
  primero**.
- **Los 57 controladores sueltos** incluyen piezas sin dueño claro
  (`TicketsWp`, `WooSync`, `Demo`, `Combo`). Decidir módulo para cada uno
  antes de moverlo; si no encaja en ninguno, probablemente sea código muerto y
  toca comprobar si está vivo en ARIN.
- **Deriva con ARIN.** Producción diverge del repo local en varios archivos.
  Antes de mover un archivo, comparar md5 contra ARIN: mover uno que allá esté
  más nuevo perdería ese trabajo.

## Cuánto cuesta y qué NO da

Son **varias sesiones** y no añade ni una función que el cliente vea. No hace
el sistema más rápido ni arregla ningún bug.

Lo que da es capacidad de cambio: tocar un producto sin miedo a romper otro,
que alguien nuevo se ubique solo, y que un test en rojo señale un sitio
concreto. Es inversión en poder seguir avanzando dentro de uno o dos años.

Por eso conviene hacerlo cuando no haya nada urgente encima, nunca a medias de
una entrega.
