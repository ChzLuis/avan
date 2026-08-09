# DOCUMENTACIÓN TÉCNICA — SISTEMA AVAN (BIXO)

> **Última actualización:** Mayo 2026  
> **Propósito:** Referencia técnica completa para desarrollo, mantenimiento e integración con IA

---

## ÍNDICE

1. [Resumen del Sistema](#1-resumen-del-sistema)
2. [Stack Tecnológico](#2-stack-tecnológico)
3. [Arquitectura General](#3-arquitectura-general)
4. [Base de Datos — Tablas y Modelos](#4-base-de-datos--tablas-y-modelos)
5. [Backend — Controllers](#5-backend--controllers)
6. [Rutas (API + Web)](#6-rutas-api--web)
7. [Middleware y Seguridad](#7-middleware-y-seguridad)
8. [Frontend — Vistas y Componentes](#8-frontend--vistas-y-componentes)
9. [Bot WhatsApp (engine.js)](#9-bot-whatsapp-enginejs)
10. [Módulos del Sistema](#10-módulos-del-sistema)
11. [Portales de Acceso](#11-portales-de-acceso)
12. [Integraciones Externas](#12-integraciones-externas)
13. [Infraestructura (VPS)](#13-infraestructura-vps)
14. [Flujos de Conversación del Bot](#14-flujos-de-conversación-del-bot)
15. [Variables de Entorno (.env)](#15-variables-de-entorno-env)
16. [Directorios Críticos](#16-directorios-críticos)

---

## 1. RESUMEN DEL SISTEMA

AVAN (también llamado BIXO internamente) es una plataforma SaaS multi-tenant desarrollada en Laravel 12. Permite a negocios gestionar ventas, atención al cliente, facturación electrónica y automatización de WhatsApp desde un solo panel.

### Qué hace el sistema:
- **Tienda online pública** con múltiples plantillas de diseño
- **CRM** para gestión de clientes, cotizaciones y pedidos
- **Facturación electrónica peruana** (boletas/facturas con SUNAT)
- **Bot de WhatsApp** con flujos conversacionales configurables
- **Rifas/membresías** con validación de pagos y envío de tickets
- **HR básico** con empleados, asistencia y comisiones
- **Agenda y reservas** de servicios
- **Certificados digitales** con QR de verificación
- **Reportes** de ventas, seguimiento y productos

### Multi-Tenant:
Cada negocio es un **Proyecto** (`projects` table). Todos los datos llevan `project_id`. El usuario puede pertenecer a múltiples proyectos. El proyecto activo se guarda en sesión y se establece via middleware `SetActiveProject`.

---

## 2. STACK TECNOLÓGICO

| Capa | Tecnología | Versión |
|------|-----------|---------|
| Backend | Laravel | 12 (PHP 8.2+) |
| Frontend CSS | Tailwind CSS | 3.1 |
| Frontend JS | Alpine.js | 3.4 |
| Build tool | Vite | 7+ |
| Base de datos | MySQL | 8+ |
| Cache / Queue | Redis (Predis) | 3.4 |
| Auth & Permisos | Spatie Laravel Permission | 6.24 |
| Excel | PhpSpreadsheet | 5.7 |
| Bot WhatsApp | whatsapp-web.js (Node.js) | — |
| Process Manager | PM2 | — |
| Browser headless | Google Chrome (/opt/google/chrome) | — |
| Servidor web | Apache (XAMPP local) / Nginx (VPS) | — |

---

## 3. ARQUITECTURA GENERAL

```
┌─────────────────────────────────────────────────────────┐
│                    USUARIO FINAL                        │
│         Tienda /{slug}    Portal /b/{slug}              │
└────────────────────┬────────────────────────────────────┘
                     │ HTTP
┌────────────────────▼────────────────────────────────────┐
│                 LARAVEL 12 (PHP)                        │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐  │
│  │ Admin    │ │ Factura- │ │ Comercial│ │ Comunic. │  │
│  │/bixoadmin│ │ción /f/  │ │/bixosales│ │/bixocrm  │  │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘  │
│                                                         │
│  Controllers → Models → MySQL                          │
│                                                         │
│  /wa/* API ──────────────────────────────────────────► │
│                                                         │
└─────────┬──────────────────────────────────────────────┘
          │ HTTP interno (127.0.0.1)
┌─────────▼──────────────────────────────────────────────┐
│           NODE.JS — whatsbot/engine.js                  │
│                                                         │
│  PM2 bot-rifa → puerto 3003                            │
│  whatsapp-web.js → Google Chrome headless              │
│                                                         │
│  ◄── WhatsApp Web (sesión autenticada) ──► Usuario WA  │
└─────────────────────────────────────────────────────────┘
```

### Comunicación Laravel ↔ Bot:
- **Laravel → Bot**: `POST http://127.0.0.1:{port}/action` (para enviar mensajes, confirmar pagos)
- **Bot → Laravel**: `GET/POST https://bot.pruebatusuerte.com.pe/wa/*` (para leer/guardar datos)
- **Token compartido**: `wa-bot-secret-2024` (en todas las peticiones)

---

## 4. BASE DE DATOS — TABLAS Y MODELOS

### 4.1 Tablas de Sistema Base

#### `users`
```
id, name, username, email, password, is_superadmin,
email_verified_at, remember_token, timestamps
```
- Modelo: `app/Models/User.php`
- Traits: HasRoles (Spatie)
- Un usuario puede pertenecer a múltiples proyectos vía `project_members`

#### `projects`
```
id, owner_id (FK users), name, slug (único), description,
category, phone, whatsapp, address, city, latitude, longitude,
is_active, wa_phone, custom_domain, timestamps
```
- Modelo: `app/Models/Project.php`
- Cada negocio = 1 proyecto
- `slug` se usa en URL de la tienda pública: `/{slug}`
- `custom_domain` permite dominio propio
- Métodos: `setting($key)` lee `project_settings`, `hasModule($key)` verifica módulo activo

#### `project_members`
```
id, project_id, user_id, timestamps
```
- Asocia usuarios a proyectos (sin rol propio, el rol va en `employees`)

#### `modules`
```
id, name, key, description, is_active, sort_order, timestamps
```
- Módulos disponibles en el sistema (creados por seeder)
- Keys: catalogo, agenda, invoices, quotes, hr, bots, rifas, pos, comunicaciones, etc.

#### `project_modules` (pivot)
```
project_id, module_id, is_active
```

#### `project_settings`
```
id, project_id, key, value, timestamps
```
- Configuración flexible: colores, SEO, pagos, diseño, etc.
- Acceso: `$project->setting('clave')`

---

### 4.2 Tablas de Catálogo y Ventas

#### `categories`
```
id, project_id, name, description, color, is_active, sort_order, timestamps
```

#### `products`
```
id, project_id, category_id, name, sku, description,
price, compare_price, cost_price, wholesale_price, wholesale_min,
stock, stock_min, stock_max, track_stock,
is_available, is_featured, weight, timestamps
```
- Modelo: `app/Models/Product.php`
- Relaciones: category, images, reviews

#### `product_images`
```
id, product_id, image_url, is_main, sort_order, timestamps
```

#### `services`
```
id, project_id, name, description, price, duration,
modality (presencial/virtual/domicilio), notes, is_available, timestamps
```

#### `clients`
```
id, project_id, name, phone, email, notes, timestamps
```
- Modelo: `app/Models/Client.php`
- Trait: HasProjectScope

#### `orders`
```
id, project_id, client_id, client_name, client_phone, client_email,
status, payment_status, payment_method,
subtotal, discount, shipping_cost, total,
delivery_address, delivery_lat, delivery_lng,
sales_channel (web/whatsapp/pos/manual), channel_detail,
wa_number, wa_status, wa_flow_state,
notes, created_by, coupon_code, timestamps
```
- Modelo: `app/Models/Order.php`
- Casts: total, shipping_cost, discount como decimal:2

#### `order_items`
```
id, order_id, product_id, product_name, quantity, price, total, timestamps
```

#### `quotes`
```
id, project_id, client_id, client_name, client_phone, client_email,
status (draft/sent/accepted/rejected/expired), token,
subtotal, discount, total,
notes, valid_until, doc_type, doc_number, timestamps
```

#### `quote_items`
```
id, quote_id, description, quantity, price, total, timestamps
```

#### `coupons`
```
id, project_id, code, type (percent/fixed), value,
min_order, max_uses, used_count, expires_at, is_active, timestamps
```

#### `reviews`
```
id, project_id, product_id, author_name, rating (1-5),
comment, is_approved, timestamps
```

#### `abandoned_carts`
```
id, project_id, client_phone, items (JSON), subtotal, recovered_at, timestamps
```

---

### 4.3 Tablas de Facturación

#### `invoices`
```
id, project_id, order_id, quote_id, client_id,
type (boleta/factura), serie, numero, correlativo,
client_name, client_doc_type, client_doc_number,
client_email, client_address,
subtotal, igv, total,
status (draft/issued/cancelled),
sunat_status (pending/sent/accepted/rejected),
sunat_ticket, sunat_response (JSON),
issue_date, due_date, timestamps
```

#### `invoice_items`
```
id, invoice_id, description, quantity, unit_price,
igv_amount, total, timestamps
```

---

### 4.4 Tablas de WhatsApp

#### `wa_canales`
```
id, project_id, nombre, tipo (meta/local), telefono,
phone_number_id, access_token (oculto en JSON),
verify_token, color, activo, bot_type,
mensaje_bienvenida, mensaje_ausencia, timestamps
```
- Modelo: `app/Models/WaCanal.php`
- `tipo=meta` → Meta Cloud API (oficial)
- `tipo=local` → whatsapp-web.js (no oficial)

#### `wa_conversaciones`
```
id, project_id, canal_id, phone, name,
last_message, last_message_at, status (open/closed), timestamps
```

#### `wa_mensajes`
```
id, conversacion_id, type (text/image/audio/document),
body, media_url, from_me, wa_message_id, timestamp, timestamps
```

#### `wa_respuestas_rapidas`
```
id, project_id, titulo, mensaje, timestamps
```

---

### 4.5 Tablas del Bot Conversacional

#### `bot_flows`
```
id, project_id, name, bot_type (main/rifa/etc),
is_active, timestamps
```
- Un flujo por tipo de bot por proyecto

#### `bot_states`
```
id, flow_id, key (ej: "inicio", "pedir_nombre"),
label, message, input_type (text/number/image/option/location/none),
images (JSON array de URLs), validation_pattern,
error_message, sort_order, is_active, timestamps
```
- `input_type=none` = estado terminal (no espera input)
- `images` = se envían antes del mensaje texto

#### `bot_transitions`
```
id, from_state_id, to_state_id,
trigger (null=cualquier input, "1"=exacto, "PEDIDO+TOTAL"=patrón),
action (save_name/select_rifa/etc), action_param,
sort_order, timestamps
```

#### `bot_sessions`
```
id, flow_id, wa_number, current_state,
data (JSON — datos del usuario en conversación),
last_activity_at, timestamps
UNIQUE(flow_id, wa_number)
```

#### `bot_instances`
```
id, project_id, name, bot_type (único), description,
icon_color, port, is_active, timestamps
```
- Cada fila = un proceso PM2 corriendo `engine.js --bot={bot_type} --port={port}`

#### `bot_configs`
```
id, project_id, bot_type, key, value,
UNIQUE(project_id, bot_type, key)
```

---

### 4.6 Tablas de Rifas / Membresías

#### `rifas`
```
id, project_id, nombre, descripcion, precio_ticket,
min_tickets, max_tickets, imagen_url, premio,
is_active, timestamps
```

#### `rifa_ventas`
```
id, project_id, rifa_id, order_number (ej: RA7K2X9F),
wa_number, plan, plan_nombre, tickets, monto,
nombre, dni, email, telefono, ciudad, direccion,
payment_proof (ruta archivo),
ticket_code (8 chars random),
ticket_numbers (JSON array, ej: [12345, 54321]),
membership_number (número de membresía asignado),
status (pendiente/pagado/enviado/cancelado), timestamps
```
- Modelo: `app/Models/RifaVenta.php`
- Método `generateOrderNumber()`: genera "R" + 6 chars random → "RA7K2X9F"
- Método `assignTicketNumbers()`: asigna números únicos 1-99999 sin repetir en misma rifa

---

### 4.7 Tablas de RRHH

#### `employees`
```
id, project_id, user_id, name, role, email,
hire_date, is_active, commission_rate, trabajo_horario,
spatie_role, timestamps
```
- Método `canAccessNow()`: verifica si el empleado puede acceder según su horario de trabajo

#### `attendances`
```
id, employee_id, date, check_in, check_out, notes, timestamps
```

#### `work_schedules`
```
id, employee_id, day_of_week (0-6), start_time, end_time, timestamps
```

---

### 4.8 Tablas de Agenda

#### `appointments`
```
id, project_id, client_id, service_id, employee_id,
date, time, duration, status, notes, timestamps
```

#### `availabilities`
```
id, service_id, day_of_week, start_time, end_time, timestamps
```

#### `blocked_dates`
```
id, project_id, date, reason, timestamps
```

---

### 4.9 Otras Tablas

#### `sedes`
```
id, project_id, name, address, phone, timestamps
```

#### `user_groups`
```
id, project_id, name, color, timestamps
```

#### `proveedores`
```
id, project_id, name, contact, phone, email, timestamps
```

#### `proposals`
```
id, project_id, name, slug, description, data (JSON), timestamps
```

#### `certificados`
```
id, project_id, codigo (único), holder_name, credential,
title, description, issue_date, is_active, timestamps
```

#### `catalog_lists` / `catalog_values`
```
-- catalog_lists
id, project_id, type, name, is_system, timestamps

-- catalog_values
id, catalog_list_id, label, value, color, is_active, sort_order, timestamps
```
- Listas maestras configurables: tipos de documento, estados personalizados, etc.

#### `inventory_movements`
```
id, product_id, type (in/out/adjustment), quantity,
reference, note, timestamps
```

---

## 5. BACKEND — CONTROLLERS

### 5.1 Controllers Principales (`app/Http/Controllers/`)

#### `DashboardController`
- `index()` — Panel principal con métricas del proyecto activo

#### `ProjectController`
- `store()` — Crear nuevo proyecto (negocio), genera slug único
- `update()` — Actualizar datos del proyecto
- `destroy()` — Eliminar proyecto
- `updateModules()` — Activar/desactivar módulos
- `toggleStatus()` — Activar/desactivar proyecto

#### `SettingsController`
- `index()` — Panel de configuración general (3 columnas: negocio, social, avanzado)
- `update()` — Guardar configuración general
- `seo()` — Vista SEO (meta tags, schema.org, verificaciones Google/FB)
- `updateSeo()` — Guardar SEO
- `modules()` — Panel de módulos
- `design()` — Configurador de diseño (colores, fuentes, layout, banner)
- `updateDesign()` — Guardar diseño
- `payments()` — Configurar métodos de pago
- `updatePayments()` — Guardar config de pagos (Culqi keys, MP keys, cuentas bancarias)
- `applyTemplate()` — Aplicar plantilla de tienda prediseñada (borra products/categories actuales)
- `storeCoupon()` — Crear cupón de descuento
- `destroyCoupon()` — Eliminar cupón
- `toggleCoupon()` — Activar/desactivar cupón
- `storeCanal()` — Crear/actualizar canal WhatsApp (Meta o local)
- `destroyCanal()` — Eliminar canal WhatsApp

#### `ClientController` (`/bixoadmin/clients`)
- `index()` — Lista de clientes con búsqueda
- `store()` — Crear cliente
- `update()` — Actualizar cliente
- `destroy()` — Eliminar cliente
- `import()` — Importar desde Excel

#### `OrderController` (`/bixoadmin/orders` o `/orders`)
- `index()` — Lista de pedidos con filtros (estado, canal, fecha)
- `store()` — Crear pedido manual
- `show()` — Detalle de pedido
- `update()` — Actualizar estado/datos
- `destroy()` — Eliminar pedido
- `waAction()` — Enviar acción WhatsApp sobre pedido

#### `QuoteController` (`/quotes`)
- CRUD completo de cotizaciones
- `sendByEmail()` — Enviar cotización por email
- `convertToInvoice()` — Convertir a factura/boleta
- `convertToOrder()` — Convertir a pedido

#### `InvoiceController` (`/invoices`)
- `index()` / `show()` / `store()` / `update()` / `destroy()` — CRUD básico
- `sendSunat()` — Enviar a SUNAT via Job asíncrono
- `pdf()` — Generar PDF con Blade → HTML
- `lookupRuc()` — Consulta RUC a APIPERU.PE (nombre y dirección automáticos)
- Métodos de portal: `indexBoletasPortal()`, `indexFacturasPortal()`, `createBoletaPortal()`, etc.

#### `PaymentController`
- `confirmManual()` — Confirmar pago manual (Yape, Plin, transferencia)
- `chargeCulqi()` — Cobrar con token Culqi
- `createMpPreference()` — Crear preferencia Mercado Pago y retornar URL de pago
- `mpWebhook()` — Recibir notificación IPN de Mercado Pago, actualizar pedido

#### `PublicController`
- `catalog()` — Tienda pública (detecta plantilla del proyecto, renderiza vista correspondiente)
- `product()` — Detalle de producto con imágenes y reseñas
- `validateCoupon()` — Validar código cupón (retorna descuento o error)
- `storeOrder()` — Crear pedido desde web (con lock de stock, aplicar cupón)
- `saveCart()` — Guardar carrito abandonado para recuperación posterior
- `storeQuote()` — Solicitar cotización desde catálogo
- `storeReview()` — Enviar reseña (rate limiting: 1 por IP por producto)
- `thankyou()` — Página de agradecimiento post-compra
- `book()` — Vista para agendar cita de servicio
- `storeBook()` — Guardar reserva/cita

#### `PortalController`
- `home()` — Home del portal comercial (`/b/{slug}`)
- `quote()` — Ver cotización individual con token público
- `accept()` — Cliente acepta cotización (cambia status a 'accepted')

#### `HRController`
- `index()` — Lista empleados con horarios y asistencia
- `store()` — Crear empleado (también crea User si no existe, asigna rol Spatie)
- `update()` — Actualizar datos de empleado
- `destroy()` — Desactivar empleado

#### `BotStatusController` (`/bixoadmin/bots`)
- `index()` — Panel de bots con estado en vivo (lee JSON de status)
- `botControl()` — Start/stop/restart bot via PM2 shell_exec
- `resetSession()` — Borrar sesión WhatsApp (POST al bot, este borra Chrome + sesión)
- `botLogs()` — Leer últimas N líneas del log del bot
- `botStore()` — Crear nueva instancia de bot (crea BotInstance + BotFlow vacío)
- `botDestroy()` — Eliminar bot (detiene PM2 + elimina registro)
- `status()` — JSON con estado actual del bot (lee archivo `{bot_type}-status.json`)
- `flowIndex()` — Editor visual de flujo + exportar JSON de flujo
- `stateStore/Update/Destroy()` — CRUD de estados del flujo
- `stateMove()` — Cambiar orden de estados (drag & drop)
- `transitionStore/Destroy()` — CRUD de transiciones entre estados
- `uploadImage()` — Subir imagen para estado del bot
- `configSave()` — Guardar configuración del bot
- `exportFlow()` — Exportar flujo a JSON + notificar bot para recargarlo
- `esperaAsesor()` — Ver conversaciones en espera de asesor humano

#### `WaBotController` (`/wa/*`)
- `getConfig()` — Config del negocio por número de teléfono
- `getFlowConfig()` — Flujo completo del bot (cached 5 min en Redis)
- `getSession()` — Estado de sesión de un usuario de WhatsApp
- `updateSession()` — Guardar/actualizar sesión
- `receiveOrder()` — Recibir pedido creado por el bot
- `paymentReceived()` — Confirmar que se recibió pago
- `clientConfirmed()` — Cliente confirmó recepción
- `portalAction()` — Admin ejecuta acción sobre pedido (confirmar pago, en camino, entregado)
- `findOrder()` — Buscar pedido por número de WhatsApp
- `receivePaymentProof()` — Guardar comprobante de pago (imagen en base64)
- `updateDelivery()` — Actualizar dirección y costo de delivery
- `exportFlowJson()` — Helper estático para exportar flujo

#### `WaWebhookController`
- `verify()` — Verificar webhook Meta (GET con challenge)
- `receive()` — Recibir mensajes de Meta Cloud API (POST)
- Procesa: mensajes de texto, imágenes, documentos, audio

#### `RifaController`
- `index()` — Panel con lista de ventas + catálogo de rifas
- `rifaStore/Update/Destroy()` — CRUD de planes de rifa
- `confirmarPago()` — Validar pago de venta (asigna tickets, notifica bot)
- `cancelarVenta()` — Cancelar venta
- `enviarConMembresia()` — Enviar WhatsApp con número de membresía y tickets
- `enviarTicket()` — Enviar ticket final al cliente (via bot o Meta)
- `ventasJson()` — JSON para panel de ventas en tiempo real
- `botList()` — Lista de rifas para el bot (`GET /wa/rifas`)
- `botCreateOrder()` — Crear venta desde bot (`POST /wa/rifa-order`)
- `botPaymentProof()` — Guardar comprobante (`POST /wa/rifa/{venta}/payment-proof`)
- `botUpdateData()` — Actualizar datos del cliente (`POST /wa/rifa/{venta}/data`)

---

### 5.2 Controllers de Portales

#### `Facturacion/DashboardController` (`/f/{slug}`)
- `login()` / `doLogin()` — Autenticación con credenciales del negocio
- `dashboard()` — Panel con resumen de documentos
- `pos()` — Punto de venta
- `pedidos()` / `cotizaciones()` / `boletas()` / `facturas()` — Listas filtradas
- `clientes()` — Gestión de clientes

#### `Comercial/DashboardController` (`/bixosales`)
- `home()` — Dashboard comercial
- `pos()` — Punto de venta comercial
- `pedidosBot()` — Gestión de pedidos del bot (rifas)
- `pedidos()` / `cotizaciones()` / `facturas()` / `clientes()` — Listas
- `reporteVentasBot()` — Ventas del bot con filtros
- `reporteSeguimiento()` — Seguimiento de cotizaciones
- `reporteVentas()` — Ventas generales
- `reporteTopProductos()` — Productos más vendidos

#### `Comunicaciones/*` (`/bixocrm`)
- `ComunicacionesController.bandeja()` — Bandeja de conversaciones WhatsApp
- `getMensajes()` — Cargar mensajes de una conversación
- `enviarMensaje()` — Enviar mensaje desde panel
- `Clientes.index()` — Clientes del CRM
- `Canales.index()` / `store()` — Configurar canales WhatsApp
- `chatbot()` — Configurador visual de chatbot

---

## 6. RUTAS (API + WEB)

### 6.1 Rutas Públicas

```
GET  /                          → Redirect (login o dashboard)
GET  /demo                      → Página presentación del sistema
POST /demo                      → Enviar solicitud de demo
GET  /{slug}                    → Tienda online del negocio
GET  /{slug}/p/{id}             → Detalle de producto
POST /{slug}/order              → Crear pedido
POST /{slug}/cart               → Guardar carrito
POST /{slug}/coupon             → Validar cupón
GET  /{slug}/thanks/{order}     → Página agradecimiento
POST /{slug}/review             → Enviar reseña
POST /{slug}/quote              → Solicitar cotización
GET  /{slug}/book               → Vista reservas
POST /{slug}/book               → Crear reserva
POST /{slug}/pay/{order}/manual → Pago manual
POST /{slug}/pay/{order}/culqi  → Pago Culqi
POST /{slug}/pay/{order}/mp     → Pago Mercado Pago
POST /{slug}/mp-webhook         → Webhook Mercado Pago
GET  /b/{slug}                  → Portal comercial (cotizaciones)
GET  /b/{slug}/c/{token}        → Ver cotización cliente
POST /b/{slug}/c/{token}/accept → Aceptar cotización
GET  /certificados/{codigo}     → Verificar certificado
```

### 6.2 Rutas del Bot (sin CSRF, con token)

```
GET  /wa/config                          → Config negocio por teléfono
GET  /wa/flow-config                     → Flujo completo del bot
GET  /wa/session                         → Sesión de usuario WhatsApp
POST /wa/session                         → Guardar sesión
POST /wa/order                           → Recibir pedido del bot
POST /wa/order/{id}/payment              → Confirmar pago
POST /wa/order/{id}/delivery             → Actualizar delivery
POST /wa/find-order                      → Buscar pedido por WA
GET  /wa/rifas                           → Lista de rifas
POST /wa/rifa-order                      → Crear venta de rifa
POST /wa/rifa/{venta}/payment-proof      → Guardar comprobante
POST /wa/rifa/{venta}/data               → Actualizar datos cliente
GET  /wa/sessions                        → Sesiones activas (admin)
GET  /bot-qr/{bot?}                      → Página pública QR
GET  /bot-status/{bot?}                  → JSON estado del bot
GET  /wa/webhook/{slug}                  → Verificar webhook Meta
POST /wa/webhook/{slug}                  → Recibir mensajes Meta
```

### 6.3 Rutas Autenticadas (`/bixoadmin`)

```
-- Proyectos y workspace
GET  /workspace                          → Selector de proyectos
GET  /workspace/select/{project}         → Cambiar proyecto activo
POST /projects                           → Crear proyecto
PUT  /projects/{id}                      → Actualizar proyecto
DELETE /projects/{id}                    → Eliminar proyecto

-- Panel principal
GET  /bixoadmin                          → Dashboard

-- Catálogo
GET  /bixoadmin/catalog                  → Productos, Servicios, Categorías
POST /bixoadmin/catalog/products         → Crear producto
PUT  /bixoadmin/catalog/products/{id}    → Editar producto
DELETE /bixoadmin/catalog/products/{id} → Eliminar producto
POST /bixoadmin/catalog/products/import → Importar Excel
[similar para services y categories]

-- Clientes, Pedidos, Cotizaciones, Facturas
GET/POST/PUT/DELETE /bixoadmin/clients
GET/POST/PUT/DELETE /bixoadmin/orders
GET/POST/PUT/DELETE /quotes
GET/POST/PUT/DELETE /invoices
POST /invoices/{id}/sunat                → Enviar SUNAT
GET  /invoices/{id}/pdf                  → Generar PDF

-- Bots WhatsApp
GET  /bixoadmin/bots                     → Panel de bots
GET  /bixoadmin/bots/status              → JSON estado (polling)
POST /bixoadmin/bots/control             → start/stop/restart PM2
POST /bixoadmin/bots/reset-session       → Cambiar número WhatsApp
GET  /bixoadmin/bots/logs                → Logs del bot
GET  /bixoadmin/bots/flow                → Editor visual de flujo
POST /bixoadmin/bots/flow                → Crear/guardar flujo
POST /bixoadmin/bots/states              → Crear estado
PUT  /bixoadmin/bots/states/{state}      → Editar estado
DELETE /bixoadmin/bots/states/{state}    → Eliminar estado
POST /bixoadmin/bots/states/{state}/move → Mover estado (orden)
POST /bixoadmin/bots/transitions         → Crear transición
DELETE /bixoadmin/bots/transitions/{t}   → Eliminar transición
POST /bixoadmin/bots/upload-image        → Subir imagen
POST /bixoadmin/bots/instances           → Crear instancia de bot
DELETE /bixoadmin/bots/instances/{bot}   → Eliminar instancia
POST /bixoadmin/bots/reset-session       → Reset sesión WhatsApp

-- Rifas
GET  /rifas                              → Panel de rifas
POST /rifas/catalog                      → Crear rifa
PUT  /rifas/catalog/{id}                 → Editar rifa
DELETE /rifas/catalog/{id}               → Eliminar rifa
POST /rifas/{venta}/confirmar            → Validar pago
POST /rifas/{venta}/cancelar             → Cancelar venta
POST /rifas/{venta}/enviar-membresia     → Enviar membresía
POST /rifas/{venta}/enviar-ticket        → Enviar ticket

-- Configuración
GET  /bixoadmin/settings                 → Configuración general
POST /bixoadmin/settings                 → Guardar configuración
GET  /bixoadmin/settings/design          → Diseño
POST /bixoadmin/settings/design          → Guardar diseño
GET  /bixoadmin/settings/modules         → Módulos
POST /bixoadmin/settings/modules         → Actualizar módulos
GET  /bixoadmin/settings/payments        → Pagos
POST /bixoadmin/settings/payments        → Guardar pagos
POST /bixoadmin/settings/template        → Aplicar plantilla
POST /bixoadmin/settings/canales         → Crear canal WA
DELETE /bixoadmin/settings/canales/{id}  → Eliminar canal WA

-- RRHH
GET  /bixoadmin/hr                       → Panel empleados
POST /bixoadmin/hr                       → Crear empleado
PUT  /bixoadmin/hr/{emp}                 → Editar empleado
DELETE /bixoadmin/hr/{emp}               → Eliminar empleado

-- Otros
GET  /bixoadmin/agenda                   → Agenda y citas
GET  /bixoadmin/pos                      → Punto de venta
GET  /bixoadmin/roles                    → Roles y permisos
GET  /bixoadmin/catalog-lists            → Listas maestras
GET  /bixoadmin/company/sedes            → Sedes
GET  /bixoadmin/company/groups           → Grupos de usuarios
GET  /bixoadmin/proveedores              → Proveedores
GET  /bixoadmin/certificados             → Certificados digitales
GET  /bixoadmin/proposals                → Propuestas BIXO
```

### 6.4 Portales con Auth Propio

```
-- Portal Facturación (/f/{slug})
GET  /f/{slug}/login                     → Login
POST /f/{slug}/login                     → Autenticar
GET  /f/{slug}/                          → Dashboard
GET  /f/{slug}/pos                       → POS
GET  /f/{slug}/pedidos                   → Pedidos
GET  /f/{slug}/cotizaciones              → Cotizaciones
GET  /f/{slug}/boletas                   → Boletas
GET  /f/{slug}/facturas                  → Facturas
GET  /f/{slug}/clientes                  → Clientes

-- Portal Comercial (/bixosales)
GET  /bixosales/login                    → Login
GET  /bixosales/                         → Dashboard
GET  /bixosales/pos                      → POS
GET  /bixosales/pedidos-bot              → Pedidos bot (rifas)
GET  /bixosales/pedidos                  → Pedidos normales
GET  /bixosales/reportes/ventas-bot      → Reporte ventas bot
GET  /bixosales/reportes/seguimiento     → Seguimiento
GET  /bixosales/reportes/ventas          → Ventas generales
GET  /bixosales/reportes/top-productos   → Top productos
POST /bixosales/pedidos-bot/{venta}/enviar-membresia → Enviar membresía
POST /bixosales/pedidos-bot/{venta}/enviar-ticket    → Enviar ticket

-- Portal Comunicaciones (/bixocrm)
GET  /bixocrm/login                      → Login
GET  /bixocrm/                           → Bandeja conversaciones
GET  /bixocrm/{conv}/mensajes            → Mensajes de conversación
POST /bixocrm/{conv}/enviar              → Enviar mensaje
GET  /bixocrm/clientes                   → Clientes CRM
GET  /bixocrm/configuracion              → Configurar canales
POST /bixocrm/canales                    → Guardar canal
GET  /bixocrm/chatbot                    → Configurar chatbot
```

---

## 7. MIDDLEWARE Y SEGURIDAD

### 7.1 Lista de Middlewares

| Middleware | Archivo | Qué hace |
|-----------|---------|----------|
| `SetActiveProject` | Middleware/SetActiveProject.php | Lee sesión `active_project_id`, carga Project y lo inyecta como `app('active_project')`. Si no hay proyecto activo y no es ruta de workspace, redirige a `/workspace`. |
| `CheckProjectMember` | Middleware/CheckProjectMember.php | Verifica que el usuario autenticado sea miembro del proyecto activo. |
| `EnsureProjectScope` | Middleware/EnsureProjectScope.php | En operaciones de escritura, verifica que el registro pertenezca al proyecto activo. |
| `CheckPermission` | Middleware/CheckPermission.php | Valida permisos Spatie con `can:{permiso}`. |
| `CheckModuleActive` | Middleware/CheckModuleActive.php | Verifica que el módulo solicitado esté habilitado en el proyecto. |
| `CheckWorkSchedule` | Middleware/CheckWorkSchedule.php | Para empleados con horario activo, verifica que la hora actual esté dentro del rango permitido. |
| `FacturacionAuth` | Middleware/FacturacionAuth.php | Autenticación propia del portal de facturación (credenciales en session). |
| `ComercialAuth` | Middleware/ComercialAuth.php | Autenticación del portal comercial. |
| `ComunicacionesAuth` | Middleware/ComunicacionesAuth.php | Autenticación del portal de comunicaciones. |
| `IsSuperAdmin` | Middleware/IsSuperAdmin.php | Verifica `is_superadmin=true` en users. |
| `DetectCustomDomain` | Middleware/DetectCustomDomain.php | Si el dominio del request coincide con `custom_domain` de un proyecto, establece ese proyecto como activo automáticamente. |

### 7.2 Excepciones CSRF

En `bootstrap/app.php` las siguientes rutas están excluidas del CSRF:
- `/wa/*` — APIs del bot
- `/whatsapp/webhook` — Webhook Meta
- `/{slug}/mp-webhook` — Webhook Mercado Pago

### 7.3 Token de Autenticación Bot

Todas las peticiones entre `engine.js` y Laravel usan el header o parámetro:
```
token=wa-bot-secret-2024
```
Definido en `engine.js` línea 19 como constante `BOT_TOKEN`.

### 7.4 Roles y Permisos (Spatie)

El sistema usa `spatie/laravel-permission`. Los roles se asignan a empleados. El Gate global en `AppServiceProvider` da acceso total a superadmin y owner del proyecto.

---

## 8. FRONTEND — VISTAS Y COMPONENTES

### 8.1 Layout Principal

Las vistas del panel admin usan `<x-app-layout>` con un sidebar de navegación. Los portales tienen su propio layout:
- `comercial/layouts/app.blade.php`
- `facturacion/layouts/app.blade.php`
- `comunicaciones/layouts/app.blade.php`

### 8.2 Plantillas de Tienda Pública

Hay 7 plantillas de tienda pública en `resources/views/public/templates/`:

| Plantilla | Descripción |
|-----------|-------------|
| `boutique` | Estilo moda/elegante, fondo oscuro |
| `ella` | Diseño femenino, rosa/pastel |
| `nordic` | Minimalista, blanco/gris |
| `flash` | Colores vibrantes, velocidad |
| `porto` | Clásico ecommerce |
| `urban` | Estilo urbano/street |
| `fresh` | Verde/fresco, orgánico |

La plantilla activa se lee de `project_settings` con key `store_template`. La vista `public/catalog.blade.php` hace include del template correspondiente.

### 8.3 Componentes Alpine.js

El frontend usa Alpine.js para interactividad sin recargar página:
- Modales de confirmación
- CRUD inline (sin recargar)
- Drag & drop en editor de flujos
- Polling de estado del bot (cada 5s)
- Notificaciones toast

### 8.4 Panel de Bots (`bots/index.blade.php`)

Polling JavaScript cada 5 segundos a `GET /bixoadmin/bots/status?bot={type}`:
```javascript
setInterval(() => botTypes.forEach(refreshStatus), 5000);
```

Muestra automáticamente QR cuando el bot lo genera. Actualiza badge de estado en tiempo real.

### 8.5 Editor de Flujo (`bots/flow.blade.php`)

Editor visual con 3 paneles:
1. **Lista de estados** — drag & drop para ordenar
2. **Edición de estado** — formulario con input_type, mensaje, imágenes, validaciones
3. **Transiciones** — visual de conexiones entre estados

Guarda automáticamente al hacer blur en campos.

---

## 9. BOT WHATSAPP (engine.js)

### 9.1 Archivo y Ubicación

- **Local**: `c:/xampp/htdocs/avan/whatsbot/engine.js`
- **VPS**: `/home/pruebatusuerte-bot/htdocs/bot.pruebatusuerte.com.pe/whatsbot/engine.js`
- **Líneas**: ~1204

### 9.2 Cómo Ejecutar

```bash
# Modo desarrollo
node engine.js --bot=rifa --port=3003

# Producción con PM2
pm2 start engine.js --name bot-rifa -- --bot=rifa --port=3003
```

### 9.3 Argumentos

| Argumento | Descripción | Default |
|-----------|-------------|---------|
| `--bot` | Tipo de bot (rifa, main, etc.) | main |
| `--port` | Puerto HTTP interno | 3001 (main), 3002 (rifa) |

### 9.4 Archivos que Genera/Lee

| Archivo | Descripción |
|---------|-------------|
| `{bot_type}-status.json` | Estado actual: `{status, qr, updated_at}` |
| `flow-{bot_type}.json` | Flujo de respaldo (si Laravel no responde) |
| `.wwebjs_auth/session-whatsbot-{bot_type}/` | Sesión de WhatsApp (Chrome user data) |
| `logs/{bot_type}.log` | Log del proceso |

### 9.5 Estados Posibles del Bot

| Estado (en status.json) | Descripción |
|------------------------|-------------|
| `offline` | Bot apagado o con error |
| `starting` | Iniciando Chrome |
| `qr` | Esperando escanear QR |
| `connected` | Conectado y funcionando |
| `reconnecting` | Reconectando tras desconexión |

### 9.6 Flujo de Inicialización

1. Lee argumentos `--bot` y `--port`
2. Detecta Chrome en: `/usr/bin/google-chrome`, `/usr/bin/google-chrome-stable`, `/usr/bin/chromium`
3. En VPS Chrome real está en `/opt/google/chrome/chrome`
4. Mata procesos Chrome anteriores: `pkill -9 -f "/opt/google/chrome/chrome"`
5. Borra lock files: `SingletonLock`, `SingletonSocket`, `SingletonCookie`
6. Inicializa `whatsapp-web.js` con `LocalAuth` (sesión persistente)
7. Al evento `ready` → GET `/wa/config` para identificar negocio → guarda status como `connected`
8. Inicia servidor HTTP en `127.0.0.1:{port}`
9. Carga flujo: GET `/wa/flow-config` → guarda en JSON de respaldo

### 9.7 Máquina de Estados del Bot

Cuando llega un mensaje:

```
1. Leer wa_number y body del mensaje
2. Comando "reset" + admin → resetear sesión
3. GET /wa/session → obtener state actual + data del usuario
4. Si currentState === 'comprobante_recibido' → lógica anti-spam especial
5. Cargar stateConfig del FLOW
6. Si input_type === 'none' → estado terminal, responder según keywords
7. Validar input según tipo (text/number/image/option)
8. Buscar transición que coincida con trigger
9. executeAction(accion, param) → llamadas a Laravel API
10. Avanzar a next_state
11. Enviar mensaje del nuevo estado
```

### 9.8 Acciones del Bot (executeAction)

| Acción | Qué hace |
|--------|----------|
| `save_name` | Guarda nombre en session |
| `save_solo_nombre` | Valida nombre (solo letras, min 3 chars) |
| `save_nombre_documento` | Parsea "Nombre, DNI", crea orden |
| `save_email` | Valida email con regex |
| `save_solo_telefono` | Valida 9 dígitos, normaliza a 51XXXXXXXXX |
| `save_solo_ciudad` | Guarda ciudad; si parece código/sospechosa, pide confirmación |
| `save_solo_dni` | Valida 8 dígitos numéricos |
| `select_item` | Selecciona producto de lista por número |
| `select_rifa` | Selecciona plan de rifa + POST `/wa/rifa-order` |
| `save_quantity` | Guarda cantidad |
| `show_lista` | GET `/wa/catalog` y muestra lista |
| `show_rifas` | GET `/wa/rifas` y muestra lista con imágenes |
| `save_payment` | Descarga imagen, convierte a base64, POST `/wa/rifa/{id}/payment-proof` |
| `save_rifa_payment` | Igual que save_payment para rifas |
| `create_order` | Parsea "PEDIDO+TOTAL:X" y POST `/wa/order` |
| `save_delivery` | GPS o texto → calcula costo por distancia |
| `create_rifa_order` | POST `/wa/rifa-order` |

### 9.9 Servidor HTTP del Bot (endpoints internos)

El bot expone un servidor HTTP en `127.0.0.1:{port}` solo accesible localmente:

| Método | Ruta | Función |
|--------|------|---------|
| GET | `/reload-flow` | Recargar flujo desde Laravel |
| POST | `/reload` | Recargar flujo (con token) |
| POST | `/reset-session` | Borrar sesión WhatsApp + mostrar QR |
| POST | `/action` | Laravel envía acción: confirmar pago, enviar mensaje, etc. |

### 9.10 Acciones que Laravel Envía al Bot (`/action`)

| `action` | Descripción |
|----------|-------------|
| `send_message` | Enviar mensaje de texto al usuario |
| `send_ticket` | Enviar imagen de ticket (base64 o URL) |
| `pago_aprobado` | Admin aprobó pago, avanzar en flujo |
| `en_camino` | Pedido en camino, notificar usuario |
| `entregado` | Pedido entregado, pedir confirmación |
| `tickets_enviados` | Membresía enviada, avanzar a confirmacion_final |
| `espera_asesor` | Poner usuario en espera de asesor |

### 9.11 Input Types del Flujo

| input_type | Qué acepta |
|-----------|------------|
| `text` | Cualquier texto |
| `number` | Solo números, valida min/max |
| `image` | Solo imágenes (msg.type === 'image') |
| `option` | Lista de opciones numeradas |
| `location` | Ubicación GPS (latitud/longitud) |
| `none` | Estado terminal, no espera input del flujo pero sí responde keywords |

### 9.12 Anti-Spam y Validaciones

- **Imagen duplicada** (comprobante_recibido): Si envían imagen dentro de 30s de la anterior, responde "Ya recibí tu comprobante"
- **Texto en estado imagen**: Si está esperando imagen pero envía texto, responde que está revisando
- **Dirección sospechosa**: Si es muy corta, solo números, o parece código, pregunta "¿Confirmas tu dirección?"
- **Rate limiting de estado**: No procesa el mismo estado más de N veces en X segundos

---

## 10. MÓDULOS DEL SISTEMA

Los módulos se activan por proyecto en Configuración → Módulos. Cada módulo tiene un `key`:

| Key | Módulo | Qué incluye |
|-----|--------|-------------|
| `catalogo` | Catálogo / Tienda | Productos, servicios, tienda pública |
| `clientes` | CRM | Clientes, historial |
| `pedidos` | Pedidos | Gestión de pedidos |
| `cotizaciones` | Cotizaciones | Envío de propuestas |
| `invoices` | Facturación | Boletas/facturas SUNAT |
| `agenda` | Agenda | Citas y reservas |
| `hr` | RRHH | Empleados, asistencia |
| `bots` | WhatsApp Bot | Bot conversacional |
| `rifas` | Rifas/Membresías | Ventas via WhatsApp |
| `pos` | Punto de Venta | POS pantalla táctil |
| `comunicaciones` | CRM WhatsApp | Bandeja de mensajes |
| `certificados` | Certificados | Emisión de certificados |
| `proveedores` | Proveedores | Gestión de proveedores |

El middleware `CheckModuleActive` bloquea el acceso a rutas de módulos desactivados.

---

## 11. PORTALES DE ACCESO

El sistema tiene 5 formas de acceso, cada una con URL y autenticación diferente:

### 11.1 Panel Admin Principal
- **URL**: `/bixoadmin`
- **Auth**: Laravel Breeze (usuario + contraseña)
- **Acceso**: Dueños y miembros del proyecto
- **Qué hace**: Todo el panel de gestión del negocio

### 11.2 Portal de Facturación
- **URL**: `/f/{slug}`
- **Auth**: Credenciales propias (no son las del panel admin)
- **Acceso**: Empleados de facturación
- **Qué hace**: Crear/ver facturas, boletas, cotizaciones, pedidos, POS

### 11.3 Portal Comercial
- **URL**: `/bixosales`
- **Auth**: Middleware `ComercialAuth` (credenciales en sesión)
- **Acceso**: Vendedores
- **Qué hace**: Ventas, pedidos del bot (rifas), reportes de ventas, POS

### 11.4 Portal de Comunicaciones (CRM WhatsApp)
- **URL**: `/bixocrm`
- **Auth**: Middleware `ComunicacionesAuth`
- **Acceso**: Asesores de atención al cliente
- **Qué hace**: Bandeja de mensajes WhatsApp, responder conversaciones, chatbot config

### 11.5 Tienda Pública
- **URL**: `/{slug}` o dominio personalizado
- **Auth**: Sin autenticación
- **Acceso**: Clientes finales
- **Qué hace**: Catálogo de productos, carrito, checkout, reservas

---

## 12. INTEGRACIONES EXTERNAS

### 12.1 SUNAT (Facturación Electrónica Peruana)
- **Uso**: Envío de boletas y facturas electrónicas
- **Implementación**: Job asíncrono en queue
- **Config**: Credenciales en `project_settings`
- **Controller**: `InvoiceController@sendSunat()`

### 12.2 APIPERU.PE
- **Uso**: Consulta de RUC para autocompletar datos del contribuyente
- **Uso**: `GET https://apiperu.dev/api/ruc/{ruc}`
- **Controller**: `InvoiceController@lookupRuc()`

### 12.3 Culqi (Pagos)
- **Uso**: Cobrar con tarjetas de crédito/débito
- **Flujo**: Frontend genera token Culqi → POST a `PaymentController@chargeCulqi()` → API Culqi
- **Config**: `CULQI_PUBLIC_KEY`, `CULQI_PRIVATE_KEY` en project_settings o .env

### 12.4 Mercado Pago
- **Uso**: Pagos online en Perú/Latam
- **Flujo**: `createMpPreference()` → redirect a MP → IPN webhook → `mpWebhook()`
- **Config**: `MP_ACCESS_TOKEN` en project_settings

### 12.5 Meta Cloud API (WhatsApp Oficial)
- **Uso**: Enviar/recibir mensajes WhatsApp con número oficial de Meta
- **Config**: `phone_number_id` y `access_token` en `wa_canales`
- **Webhook**: `GET/POST /wa/webhook/{slug}` o `/whatsapp/webhook`
- **Controller**: `WaWebhookController`

### 12.6 whatsapp-web.js (WhatsApp No Oficial)
- **Uso**: Bot conversacional con número personal/de empresa
- **No requiere aprobación de Meta**
- **Limitación**: Requiere Chrome headless en servidor
- **Sesión**: Persiste en `.wwebjs_auth/`

---

## 13. INFRAESTRUCTURA (VPS)

### 13.1 Datos del Servidor

| Dato | Valor |
|------|-------|
| IP | 2.24.200.91 |
| Usuario | root |
| SSH Key | `C:/Users/luich/.ssh/vps_key` |
| OS | Linux (Ubuntu) |
| Panel | Hostinger |

### 13.2 Rutas en el VPS

| Propósito | Ruta |
|-----------|------|
| App Laravel | `/home/pruebatusuerte-bot/htdocs/bot.pruebatusuerte.com.pe/` |
| Bot Node.js | `/home/pruebatusuerte-bot/htdocs/bot.pruebatusuerte.com.pe/whatsbot/` |
| Sesión WhatsApp | `/home/pruebatusuerte-bot/htdocs/bot.pruebatusuerte.com.pe/whatsbot/.wwebjs_auth/session-whatsbot-rifa/` |
| Chrome binary | `/opt/google/chrome/chrome` |
| PM2 logs | `/root/.pm2/logs/` |

### 13.3 PM2 — Procesos en Producción

```bash
# Ver estado
pm2 status

# Bot activo
pm2 list  → bot-rifa (puerto 3003)

# Comandos útiles
pm2 restart bot-rifa     # Reiniciar
pm2 logs bot-rifa        # Ver logs en vivo
pm2 logs bot-rifa --lines 50 --nostream  # Últimas 50 líneas

# Iniciar manualmente
pm2 start engine.js --name bot-rifa -- --bot=rifa --port=3003
```

### 13.4 Variables de Configuración del Bot (VPS)

| Variable | Valor |
|----------|-------|
| `BOT_TOKEN` | `wa-bot-secret-2024` |
| `LARAVEL_URL` | `https://bot.pruebatusuerte.com.pe` |
| `BOT_PORT` | 3003 (rifa) |
| `NODE_TLS_REJECT_UNAUTHORIZED` | 0 (SSL sin verificar) |

### 13.5 Base de Datos (VPS)

| Dato | Valor |
|------|-------|
| Base de datos | `botsuerte` |
| Usuario | `suerteuser` |
| Contraseña | `22my0pGwYeOksNM2HNCj` |

### 13.6 Dominio

| Propósito | Dominio |
|-----------|---------|
| App principal | `https://bot.pruebatusuerte.com.pe` |
| API del bot | `https://bot.pruebatusuerte.com.pe/wa/*` |
| QR público | `https://bot.pruebatusuerte.com.pe/bot-qr/rifa` |

---

## 14. FLUJOS DE CONVERSACIÓN DEL BOT

### 14.1 Flujo de Compra de Membresía (Rifa)

```
Usuario: "hola"
  ↓ Bot: Bienvenida + imagen + menú (estado 'inicio')
  
Usuario: "1" (ver planes)
  ↓ Bot: Carga /wa/rifas y muestra cada plan con imagen y precio
  
Usuario: "2" (selecciona Plan Platino)
  ↓ executeAction('select_rifa', '2')
  ↓ POST /wa/rifa-order → crea RifaVenta status='pendiente'
  ↓ Bot: "Plan seleccionado: S/ 100. ¿Cuál es tu nombre?"
  
Usuario: "Juan Pérez"
  ↓ executeAction('save_solo_nombre')
  ↓ Valida: solo letras, min 3 chars
  ↓ POST /wa/rifa/{id}/data {nombre}
  ↓ Bot: "¿Cuál es tu DNI?"
  
Usuario: "12345678"
  ↓ executeAction('save_solo_dni')
  ↓ Valida: exactamente 8 dígitos
  ↓ POST /wa/rifa/{id}/data {dni}
  ↓ Bot: "¿Tu número de celular?"
  
Usuario: "924503455"
  ↓ executeAction('save_solo_telefono')
  ↓ Normaliza: "51924503455"
  ↓ Bot: "¿Tu email?" (si el flujo lo pide)
  
Usuario: [envía imagen del comprobante]
  ↓ executeAction('save_rifa_payment')
  ↓ Descarga imagen → base64 → POST /wa/rifa/{id}/payment-proof
  ↓ Bot: "Comprobante recibido. Estamos validando..."
  
-- ADMIN en panel valida el pago --
  ↓ POST /rifas/{id}/confirmar
  ↓ Asigna números de tickets
  ↓ POST http://127.0.0.1:3003/action {action:'pago_aprobado'}
  ↓ Bot avanza al siguiente estado
  
-- Admin envía membresía --
  ↓ POST /bixosales/pedidos-bot/{id}/enviar-membresia
  ↓ Envía WhatsApp con número de membresía + tickets
  ↓ POST http://127.0.0.1:3003/action {action:'tickets_enviados'}
  
  ↓ Bot guarda estado 'confirmacion_final'
  ↓ 4 segundos después envía:
    "¿Deseas hacer algo más?
    1️⃣ Ver planes disponibles
    2️⃣ Hablar con un asesor
    3️⃣ Tengo un problema"
    
Usuario: "1"
  ↓ Bot carga lista de planes directamente (sin bienvenida completa)
```

### 14.2 Estado Terminal `confirmacion_final`

Cuando el usuario está en este estado, el bot responde aunque sea `input_type=none`:

| Escribe | Respuesta |
|---------|-----------|
| 1, menu, más, ver planes | Carga y muestra lista de planes |
| 2, asesor, ayuda | "Te comunicaré con un asesor..." |
| 3, reclamo, problema | "Lamentamos el inconveniente. Escríbenos a..." |
| Cualquier otra cosa | Muestra el menú de opciones |

### 14.3 Comando Admin `reset`

Solo funciona para el número `51955354646`:
```
Usuario (admin): reset
  ↓ Limpia sesión completamente
  ↓ Bot: "🔄 Sesión reseteada. Iniciando desde cero..."
  ↓ Envía bienvenida
```

---

## 15. VARIABLES DE ENTORNO (.env)

Variables importantes del `.env` de Laravel:

```env
APP_NAME="Avan"
APP_URL=http://localhost           # URL base de Laravel
APP_KEY=base64:...                  # Key de cifrado

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=botsuerte               # Nombre de la BD
DB_USERNAME=suerteuser
DB_PASSWORD=22my0pGwYeOksNM2HNCj

CACHE_DRIVER=redis                  # o file si no hay Redis
QUEUE_CONNECTION=database           # o redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_FROM_ADDRESS=noreply@...

# Opcionales para integraciones
CULQI_PUBLIC_KEY=...
CULQI_PRIVATE_KEY=...
MP_ACCESS_TOKEN=...
CHROMIUM_PATH=/opt/google/chrome/chrome

# El bot usa esto directamente
NODE_TLS_REJECT_UNAUTHORIZED=0
```

---

## 16. DIRECTORIOS CRÍTICOS

```
c:/xampp/htdocs/avan/              (LOCAL)
/home/pruebatusuerte-bot/htdocs/   (VPS)
│
├── app/
│   ├── Http/Controllers/          → Toda la lógica de negocio
│   ├── Models/                    → Modelos Eloquent
│   ├── Http/Middleware/           → Seguridad y contexto
│   └── Providers/AppServiceProvider.php → Gates de permisos
│
├── routes/web.php                 → Todas las rutas (200+)
│
├── database/
│   ├── migrations/                → 121 migraciones
│   └── seeders/                   → 10 seeders
│
├── resources/views/               → 100+ vistas Blade
│   ├── public/templates/          → 7 plantillas de tienda
│   └── bots/                      → Panel y editor de bots
│
├── whatsbot/                      → Bot Node.js
│   ├── engine.js                  → Motor principal del bot (1204 líneas)
│   ├── ticket-generator.js        → Genera PNG de tickets con Puppeteer
│   ├── flow-rifa.json             → Flujo de respaldo (auto-generado)
│   ├── rifa-status.json           → Estado actual del bot (auto-generado)
│   ├── .wwebjs_auth/              → Sesión WhatsApp (NO subir a git)
│   └── logs/                      → Logs del bot
│
├── storage/app/public/
│   ├── tickets/                   → PNGs de boletos generados
│   ├── bot-images/                → Imágenes de estados del bot
│   └── payment-proofs/            → Comprobantes de pago
│
└── public/uploads/
    └── rifas/                     → Comprobantes de rifas (base64 decodificados)
```

---

## APÉNDICE — CONVENCIONES DE CÓDIGO

### Agregar un nuevo módulo:
1. Crear migración con la tabla
2. Crear Model en `app/Models/`
3. Crear Controller en `app/Http/Controllers/`
4. Agregar rutas en `routes/web.php` dentro del grupo `/bixoadmin`
5. Crear vistas en `resources/views/{modulo}/`
6. Agregar entrada en `modules` table via seeder o migration
7. Registrar módulo en `CheckModuleActive` middleware

### Agregar una nueva acción al bot:
1. Abrir `whatsbot/engine.js`
2. Ir a la función `executeAction()` (~línea 644)
3. Agregar nuevo `case` en el switch
4. Si necesita llamar a Laravel, usar `laravelPost()` o `laravelGet()`
5. Subir al VPS: `scp` el archivo
6. Reiniciar: `pm2 restart bot-rifa`

### Agregar una nueva ruta de API del bot:
1. Agregar ruta en `routes/web.php` en la sección `// API sin auth`
2. Agregar método en `WaBotController.php`
3. Asegurarse que la ruta esté excluida del CSRF en `bootstrap/app.php`

### Cambiar el número de WhatsApp del bot:
1. Ir al panel admin → Canales WhatsApp
2. Hacer clic en "Cambiar número"
3. Escanear el QR que aparece con el nuevo número
4. El bot borra la sesión anterior automáticamente

---

*Documentación generada: Mayo 2026 — Sistema AVAN (BIXO)*
