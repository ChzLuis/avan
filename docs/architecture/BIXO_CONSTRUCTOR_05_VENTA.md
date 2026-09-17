# Constructor — Revisión 05 · Venta

Auditoría verificada el **2026-08-30** contra el código y la BD de producción.
Quinto paso de la reorganización de "Mi Tienda" en 9 etapas.

**Principio obligatorio:** cada dato o configuración tiene un único propietario
y un único lugar donde se modifica. Las demás áreas solo lo consumen.

---

## 0. Corrección de método (afecta a las revisiones anteriores)

Encontré un **tercer y un cuarto mecanismo de escritura** que mis barridos
previos no capturaban:

| # | Mecanismo | Dónde |
|---|---|---|
| 1 | `setSetting('clave', …)` | etapas |
| 2 | `<x-bxb-color clave="…">` | etapas *(detectado en la revisión 02)* |
| 3 | **arrays PHP `['key' => 'clave', …]` recorridos con `@foreach`** | `stages/sales.blade.php:203-204` |
| 4 | **`setSetting` desde JavaScript** | `builder/script.blade.php` |

**Recuento corregido:** solo `sales` estaba mal contada — **33 claves, no 27**
(`btn_checkout_text`, `btn_send_quote_text`, `cart_empty_msg`, `cart_title`,
`cart_shipping_zero_label`, `currency_symbol`). Las cifras de 02, 03 y 04 se
mantienen; **las 30 colisiones tampoco cambian**.

El mecanismo 4 añade 3 claves que el Constructor escribe sin pertenecer a
ninguna etapa: `business_category`, `header_preset` y **`checkout_fields`**.

> Corrijo aquí una afirmación de mi análisis inicial: **"Campos del comprador"
> SÍ existe** en el Constructor (`script.blade.php:806,820`), solo que se escribe
> desde JS. No es un hueco.

---

## 1. Hallazgo principal: una SÉPTIMA superficie, y es un elemento de menú

`settings/payments` (446 líneas) es un editor de pagos **de primera clase**:
está en el menú principal en **tres** archivos de navegación
(`bots/app.blade.php:202`, `comercial/layouts/_sidebar.blade.php:115`,
`layouts/app.blade.php:988`), con ruta y permiso propios
(`web.php:476-477`, `can:settings.pagos`).

A diferencia del Designer —huérfano y sin enlace— **éste lo usa la gente**.

### 1.1 · Ocho claves colisionan con la etapa Venta del Constructor

`payment_manual_enabled`, `payment_plin_number`, `payment_yape_name`,
`payment_yape_number`, `payment_yape_qr`, `store_mode`, `quote_price_display`,
`quote_wa_msg`

### 1.2 · Y reescribe un dato maestro de la etapa 01

**`settings/payments` escribe `quote_whatsapp` y `quote_whatsapp_country`.**

La revisión 01 fijó que el WhatsApp es dato maestro con fuente canónica
`projects.whatsapp`. Este editor lo vuelve a escribir desde una pantalla de
pagos. **Es una violación directa de la regla "un dato, un lugar"** y hay que
resolverla antes de dar por buena la implementación congelada de 01.

---

## 2. Capacidades que el Constructor no conoce

`settings/payments` administra **integraciones reales y en uso** que la etapa
Venta ni menciona:

| Capacidad | Consumidores | **Tiendas con dato** |
|---|---|---|
| **Culqi** (`culqi_enabled`, `culqi_mode`, `culqi_public_key`) | 19 | **4** |
| **Mercado Pago** (`mp_enabled`) | 19 | **4** |
| Instrucciones de pago manual (`payment_manual_instructions`) | 19 | 1 |
| Cuentas bancarias (`payment_bank_bcp/bbva/interbank/nacion/scotiabank`, `payment_bank_details`) | 4 | 1–2 por banco |

**Cuatro tiendas cobran con Culqi y cuatro con Mercado Pago**, y desde el
Constructor —que queremos que sea el único lugar de configuración— eso es
invisible. Son **capacidades a rescatar**, como los badges de la revisión 04.

---

## 3. Cobertura de la estructura objetivo

| Lo que pide 05 | Estado |
|---|---|
| Venta directa, cotización o ambas | ✅ `store_mode` (7 tiendas) — ⚠️ absorber `purchase_mode` |
| Carrito | ✅ `cart_*` (7 claves) |
| Mayoristas | ✅ `wholesale_enabled` (2 tiendas) + claves que llegan de 04 |
| Checkout | ✅ `checkout_chrome` |
| **Campos del comprador** | ✅ `checkout_fields` (2 tiendas) — vía JS |
| Entregas | ⚠️ parcial |
| **Zonas y costos de envío** | ❌ **no existen zonas.** Solo `shipping_cost` plano, `shipping_enabled` (4 tiendas), `shipping_free_from`. Sin tabla ni claves de zona |
| Recojo en tienda | ✅ `pickup_enabled` (2), `pickup_instructions` |
| Métodos de pago | ⚠️ **repartido** entre la etapa Venta y `settings/payments` |
| **Comprobantes** | ❌ prácticamente inexistente: solo `modulo_facturas` y `serie_factura` (1 tienda). La facturación vive en su propio módulo, fuera del Constructor |
| Mensajes de confirmación | ✅ `quote_wa_msg`, `wa_product_msg`, `cart_footer_note` |

**Dos huecos de capacidad:** zonas de envío y comprobantes. Ninguno es deuda —
son **backlog funcional**, como la ficha de producto y Newsletter.

---

## 4. Lo que llega desde 04 Catálogo

Las 13 claves de acción comercial identificadas en la revisión 04 pasan a ser
propiedad de 05: `card_show_cart`, `card_show_whatsapp`, `card_show_quantity`,
`card_show_subtotal`, `card_show_savings`, `card_show_wholesale_price`,
`card_show_wholesale_condition`, `wholesale_card_collapse`, `purchase_mode`,
`buy_retail_label`, `buy_wholesale_label`, `buy_unit_label`, y las rescatadas
`btn_cart_text` / `btn_inquiry_text`.

**Ninguna colisiona** con las 33 actuales de la etapa: el traslado es limpio.

---

## 5. Propiedad recomendada

| Configuración | Fuente canónica | Se configura en | Se consume en |
|---|---|---|---|
| Modo de venta | `store_mode` *(absorber `purchase_mode`)* | 05 Venta | Tienda, tarjeta, ficha, checkout |
| Moneda | `currency_symbol` | 05 Venta ✅ *(ya movida desde 01)* | Catálogo, carrito, checkout |
| Carrito | `cart_*`, `btn_cart_text`, `btn_checkout_text` | 05 Venta | Carrito, checkout |
| Mayoristas | `wholesale_enabled` + claves de tarjeta mayorista | 05 Venta | Tarjeta, ficha, carrito |
| Campos del comprador | `checkout_fields` (JSON) | 05 Venta | Checkout |
| Envíos | `shipping_*` | 05 Venta | Carrito, checkout |
| Recojo en tienda | `pickup_*` | 05 Venta | Checkout |
| Pagos manuales (Yape/Plin/bancos) | `payment_*` | 05 Venta **(absorber `settings/payments`)** | Checkout, footer |
| Pasarelas (Culqi, Mercado Pago) | `culqi_*`, `mp_*` | 05 Venta **(rescatar)** | Checkout |
| **WhatsApp** | `projects.whatsapp` | **01 Datos** — 05 solo lo consume | Tienda, bot, checkout |
| Zonas de envío | **no existe** | ➜ backlog funcional | — |
| Comprobantes | módulo de facturación | fuera del Constructor | — |

---

## 6. Trabajo que sale de esta revisión

| # | Tarea | Riesgo | Prioridad |
|---|---|---|---|
| 1 | **Quitar `quote_whatsapp` de `settings/payments`** — viola la fuente canónica de 01 | Bajo | **Alta** |
| 2 | Rescatar Culqi, Mercado Pago, bancos e instrucciones manuales al Constructor | Bajo | **Alta** |
| 3 | Resolver las 8 colisiones etapa Venta ↔ `settings/payments` | Nulo *(misma clave)* | Alta |
| 4 | Decidir el destino de `settings/payments`: absorber en 05 o dejar como administración interna | — | Alta |
| 5 | Recibir las 13 claves comerciales de 04 | Nulo | Alta |
| 6 | Absorber `purchase_mode` en `store_mode` | Bajo | Media |
| 7 | Zonas de envío | — | Backlog |
| 8 | Comprobantes dentro del Constructor | — | Backlog |

**No se implementa nada todavía.** Tampoco hubo hotfix: ninguna de las
duplicaciones muestra información incorrecta al comprador — el riesgo es que el
comerciante edite en un sitio y el otro lo pise, que es deuda, no fallo activo.

---

## 7. Decisiones que necesito del usuario

1. **`settings/payments`** — está en el menú y la gente lo usa. ¿Se absorbe
   dentro de 05 (y el menú lleva al Constructor), o se conserva como pantalla de
   administración interna con las claves comerciales en solo lectura?
   Mi recomendación: **absorber**, porque hoy además pisa el WhatsApp maestro.
2. **Culqi y Mercado Pago** — ¿entran en 05 como "Métodos de pago → Pasarelas",
   o pertenecen a **08 Integraciones**? Son cobro, pero también integración
   externa con claves de API. Mi recomendación: **05**, y en 08 solo la analítica
   y los píxeles.
3. **Zonas de envío** — ¿backlog para después de la reorganización, o requisito
   de la primera versión? Cambia el alcance de 05.

---

## 8. Siguiente paso

Revisión **06 Páginas**: las páginas institucionales (Nosotros, Contacto,
Sucursales, FAQ, Blog, personalizadas), su relación con `store_pages`, la
reutilización de los datos maestros de 01 en la página Contacto, el solapamiento
con las secciones `faq` y `locations` de Inicio, y la quinta superficie
(`institutional-pages`).
