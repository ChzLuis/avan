# Conectar el bot con WhatsApp oficial (Meta)

Guía para dar de alta una línea de la **WhatsApp Cloud API** y enchufarla al
motor de bots de BIXO. Los flujos del constructor funcionan igual en Meta y en
Baileys: no hay que rehacer ningún bot.

## Antes de empezar

| Requisito | Detalle |
|---|---|
| Cuenta de Meta for Developers | developers.facebook.com |
| Business Manager | business.facebook.com, con la empresa creada |
| Página de Facebook | Meta la pide para asociar la cuenta de WhatsApp |
| Número libre | **sin WhatsApp activo**, ni normal ni Business |
| URL pública HTTPS | el servidor donde corre BIXO |

### El número

Es el punto donde más se traba el alta:

- Si el número **ya tiene WhatsApp**, hay que borrar esa cuenta desde la app
  (Ajustes → Cuenta → Eliminar mi cuenta) y esperar unos minutos.
- Debe poder recibir **SMS o llamada** para el código de verificación.
- Sirve un chip físico, uno virtual o un fijo (verificación por llamada).
- **No uses el número que ya usa el bot con QR**: al migrarlo se pierde esa
  sesión y el historial que vive en el teléfono.

Recomendación: un chip prepago nuevo para las pruebas. El número comercial real
se migra después, cuando el flujo ya esté verificado.

## Pasos en Meta

1. **Crear la app** — developers.facebook.com → Mis apps → Crear app → tipo
   **Otro** → **Negocio** → asociarla al Business Manager.
2. **Agregar WhatsApp** — Panel de la app → Agregar producto → WhatsApp →
   Configurar. Meta crea una cuenta de prueba y un **número de prueba propio**.
3. **Anotar los datos** — en WhatsApp → Configuración de la API:
   - `Phone Number ID` (identificador del número)
   - `WhatsApp Business Account ID` (WABA)
   - **Token temporal** (dura 24 h, solo para probar)
4. **Clave secreta de la app** — Configuración → Básica → Clave secreta.
   Es la que firma los mensajes entrantes.

> Empieza con el número de prueba de Meta: permite validar todo el flujo sin
> gastar el chip nuevo. Solo entrega a los destinatarios que registres (5).

## Pasos en BIXO

1. Entrar al portal de Comunicaciones (`/bixocrm`) → **Canales de WhatsApp**
   → nuevo canal, o editar el que ya existe.
2. Pegar `Phone Number ID`, el token de acceso y el **App Secret** (la clave
   secreta de la app: sin ella el webhook no puede comprobar que los mensajes
   vienen de Meta).
3. Copiar de esa pantalla la **URL de devolución de llamada** y el
   **token de verificación**.
4. En Meta: WhatsApp → Configuración → Webhook → Editar → pegar ambos →
   Verificar y guardar.
5. Suscribirse al campo **`messages`** (sin esto Meta no avisa de nada).
6. Guardar el canal y comprobar que en la lista figura como **Activo**.
7. Escribir desde un celular al número conectado: el bot debe responder y la
   conversación aparecer en la **Bandeja**. Si no llega nada, ver «Cuando algo
   falla».

## Token permanente

El token de 24 h no sirve en producción. Para el definitivo:

1. Business Manager → Configuración del negocio → **Usuarios del sistema**.
2. Crear uno (rol Administrador) → **Generar nuevo token**.
3. Elegir la app y marcar `whatsapp_business_messaging` y
   `whatsapp_business_management`.
4. Ese token no vence: pegarlo en BIXO reemplazando el temporal.

## Diferencias frente al bot con QR

Esto cambia cómo se comporta el bot, no solo cómo se conecta:

| | QR (Baileys) | Meta |
|---|---|---|
| Costo | gratis | por conversación |
| Riesgo de bloqueo | sí | no |
| Escribir primero | libre | **solo con plantilla aprobada** |
| Responder | siempre | dentro de las 24 h desde el último mensaje del cliente |
| Listas y botones | limitados | nativos |

**La ventana de 24 horas** es lo más importante: pasado ese plazo sin que el
cliente escriba, solo se le puede contactar con una plantilla que Meta aprobó
previamente. Los recordatorios automáticos que hoy salen libres tendrían que
convertirse en plantillas.

## Cuando algo falla

El último error que devolvió Meta se muestra en la misma pantalla. Los
habituales:

| Mensaje de Meta | Qué pasa |
|---|---|
| `Invalid OAuth access token` | token vencido (el temporal dura 24 h) |
| `Recipient phone number not in allowed list` | app en pruebas: registra ese número como destinatario |
| `Message failed to send because more than 24 hours have passed` | ventana cerrada: hace falta una plantilla |
| `Template name does not exist` | la plantilla no está aprobada o cambió de nombre |

Si el webhook no verifica, revisa que la URL sea **https** y que el token de
verificación sea idéntico en las dos pantallas.

## Cómo está armado por dentro

- `WaCanal` (tabla `wa_canales`) — propietario único de las credenciales de la
  línea, según `MODULE_OWNERSHIP.md`. Token y clave secreta se guardan
  cifrados y nunca se muestran completos. La bandeja de Comunicaciones envía
  con las mismas credenciales, así que bot y asesor comparten número e
  historial.
- `WhatsappCloudWebhookController` — recibe los eventos. Comprueba la firma
  HMAC con la clave secreta **antes** de tocar el motor: la URL es pública, así
  que la firma es lo único que distingue a Meta de un tercero.
- `ClienteCloud` — traduce las respuestas del `FlowRunner` (texto, lista,
  imagen, archivo) al JSON de la Graph API, respetando los topes de Meta
  (10 filas por lista, 24 caracteres por título).
- El motor es el mismo `BotWebhookController` que usa Baileys, así que CRM,
  reglas de silencio y disparos se comportan igual en los dos canales.

## Atajo: el asistente del CRM

Desde el 2026-09-17 el portal tiene un asistente en **CRM → Canales → Asistente**
(`/bixocrm/conectar`): pegas Phone number ID, token y app secret, pulsas
"Probar conexion" (consulta a Meta y te muestra el numero reconocido), copias
la URL del webhook y el token de verificacion que te genera, los pegas en
Meta y guardas. Los pasos de developers.facebook.com de arriba siguen siendo
los mismos; el asistente solo evita equivocarse al copiar.
