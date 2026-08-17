# Análisis: qué reutilizar de AVAN para un sistema propio de sorteos

Fecha: 2026-08-16
Objetivo: sistema propio de generación de sorteos, multi-organizador (varios vendedores),
con publicación de listas de ganadores.

---

## 0. Resumen en una línea

AVAN **ya tiene un módulo de rifas funcionando en producción** (venta de tickets por
WhatsApp, validación de pagos, ticket en imagen). Lo que **NO tiene es el sorteo en sí**:
no existe entidad de "sorteo ejecutado" ni "ganador" ni página pública de resultados.
La estrategia correcta es **copiar el esqueleto + el módulo de rifas** y construir encima
la capa de *draw* (ejecución del sorteo) y *ganadores*.

---

## 1. Lo que se copia TAL CUAL (ya resuelto)

### 1.1 Módulo Rifas — el corazón, ya escrito

| Pieza | Ruta | Qué aporta |
|---|---|---|
| Modelo sorteo | `app/Models/Rifa.php` | nombre, descripción, precio_ticket, min/max tickets, imagen, premio, orden, activo, `project_id` → **ya es multi-sorteo por organizador** |
| Modelo venta | `app/Models/RifaVenta.php` | comprador (nombre/DNI/ciudad/email/tel), tickets, monto, voucher, `ticket_numbers` (JSON), estado, `order_number` |
| Controlador | `app/Http/Controllers/RifaController.php` (1068 líneas) | CRUD de sorteos, confirmar pago, enviar ticket, cancelar, monitoreo, exportar, alta manual, consulta DNI, API del bot |
| Migraciones | `database/migrations/*rifa*` (5) | esquema completo listo |
| Vistas | `resources/views/rifas/index.blade.php`, `ticket.blade.php` | panel + diseño del ticket |
| Rutas | `routes/web.php:731-752` y `:922-933` | panel admin + endpoints del bot |

**Estados ya modelados:** `pendiente → pagado → enviado / cancelado`.

### 1.2 Generador de ticket como imagen PNG
- `resources/views/rifas/ticket.blade.php` (lienzo 1200×480, panel morado + banner)
- `whatsbot/ticket-generator.js` (Puppeteer → PNG)
- `RifaController::generateTicketImage()` + `ticketDesign()` (preview con datos ficticios)

Reutilizable casi sin tocar para: **ticket del participante** y, con otra plantilla, para la
**imagen del ganador** que se publica en redes/WhatsApp.

### 1.3 Multi-organizador (lo que permite "varios vendedores")
- `app/Models/Project.php` + `ProjectMember` + `Module` / `ProjectModule`
- Middlewares: `SetActiveProject`, `EnsureProjectScope`, `CheckProjectMember`, `CheckModuleActive`
- Todo el sistema ya está aislado por `project_id`. **Esto es meses de trabajo ya hechos.**

### 1.4 Roles y permisos
- Spatie + `CheckPermission` + `RolePermissionController` + `UserGroup`
- Ya existen los permisos `rifas.ver`, `rifas.validar`, `rifas.cancelar`.

### 1.5 Cobro Yape/Plin con voucher
- `app/Http/Controllers/Api/PagoController.php` → `pendientes()`, `aprobar()`, `rechazar()`
- Captura del comprobante desde el bot (`botPaymentProof`) y validación manual en panel.
- QR de pago: `whatsbot/qr-pago.png`.

### 1.6 Bot de WhatsApp que vende
- `whatsbot/` (engine, `flow-rifa.json`, `rifa-bot.js`, `voucher-sender.js`)
- `app/Support/FlowEngine` + modelos `BotFlow`, `BotState`, `BotTransition`, `BotSession`
- Constructor visual de flujos: `resources/views/bot-builder/`
- Conector Baileys para listas nativas (ver memoria `project_bot_baileys_listas`).

### 1.7 Constructor de páginas públicas ← **clave para la lista de ganadores**
- `app/Models/StoreSection` / `StorePage` / `StoreMenu` + `app/Storefront/*`
- Trae **borrador/publicado** (`draft_content`, `has_draft`) y **ventana de publicación**
  (`publish_from`, `publish_until`) → la página de ganadores puede publicarse sola a la hora del sorteo.
- Visibilidad por dispositivo (desktop/tablet/móvil) ya resuelta.

### 1.8 Infraestructura de apoyo
- Dominio propio + SSL automático: `DetectCustomDomain` (+ `project_alta_dominios_ssl`)
- Imágenes: `ImageVariants`, `SquareImage` (AVIF/WebP)
- Texto con formato: `RichText`
- Comprobantes SUNAT si se factura el ticket: `ApisPeruService`, `NubefactService`
- Venta del sistema como SaaS: `LicenseManager` + panel de licencias

---

## 2. Lo que NO existe y hay que construir

| Falta | Detalle |
|---|---|
| **Entidad Sorteo ejecutado (`draw`)** | No hay tabla de ejecución: fecha, método, semilla, hash, ejecutor |
| **Ganadores** | No existe `Ganador`/`Winner` ni relación premio↔ticket ganador |
| **Página pública de ganadores** | No hay vista pública de resultados (se construye como sección del builder) |
| **Selección aleatoria auditable** | No hay lógica de sorteo. Hace falta semilla verificable (ej. hash del resultado de la lotería nacional) para que sea transparente |
| **Verificador público "consulta tu número"** | El comprador no puede validar su ticket sin WhatsApp |
| **Cierre del sorteo** | No hay `fecha_cierre`, meta de tickets, barra de progreso, ni cierre automático |
| **Premios múltiples** | Hoy `premio` es un `string`. Para 1er/2do/3er puesto hace falta tabla `premios` |
| **Reembolso si no se completa** | Sin flujo de devolución |

---

## 3. Deuda técnica a corregir AL COPIAR (no arrastrarla)

1. **Numeración de tickets rota a escala** — `RifaVenta::assignTicketNumbers()` hace
   `rand(1,99999)` en bucle de máx. 1000 intentos, cargando en memoria todos los números
   vendidos. Sin índice único en BD → **dos compras simultáneas pueden recibir el mismo número**
   y con el sorteo casi lleno se agotan los intentos y entrega menos tickets de los pagados.
   → Rehacer con tabla `tickets` (una fila por número, índice único `(sorteo_id, numero)`) y
   asignación transaccional con `lockForUpdate`.
2. **Token hardcodeado** `'wa-bot-secret-2024'` repetido en rutas y controlador → a `.env`.
3. **`shell_exec` + `which node`** para generar el PNG → cola/Job en vez de ejecución síncrona.
4. **Planes hardcodeados** (`RifaVenta::planes()` y `plan` tinyint) → tabla `paquetes` por sorteo.
5. **`'ciudad'` duplicado** en `$fillable` de `RifaVenta`.
6. Estados como `enum` en migración → complica agregar `reembolsado`.

---

## 4. Ruta recomendada

**Fase 0 — base (1 día):** copiar el esqueleto AVAN (auth, `Project`, `Module`, permisos,
middlewares, layouts admin, builder de páginas) sin catálogo/POS/facturación.

**Fase 1 — sorteos (reutilización pura):** portar `Rifa`, `RifaVenta`, `RifaController`,
migraciones y vistas. Renombrar a dominio propio (`Sorteo`, `Participacion`).

**Fase 2 — lo nuevo:**
- `sorteos.fecha_cierre`, `meta_tickets`, estado `abierto|cerrado|sorteado`
- tabla `tickets` (numeración única real)
- tabla `premios` (puesto, descripción, imagen)
- tabla `sorteo_draws` (semilla, hash, ejecutado_por, ejecutado_en) + `ganadores`
- comando `sorteo:ejecutar {id}` con semilla verificable
- sección del builder **"Ganadores"** (pública, autopublicable por fecha)
- verificador público `/verificar/{codigo}`

**Fase 3 — canales:** bot de WhatsApp (`flow-rifa.json` como base), imagen del ganador con
el generador Puppeteer, `PagoController` para Yape/Plin.

---

## 5. Estimación de ahorro

Aproximadamente **70-75% del sistema ya existe** en AVAN: todo el multi-tenant, permisos,
panel, venta, cobro, bot, generación de imágenes y publicación de páginas.
Lo genuinamente nuevo es la capa de **ejecución del sorteo + ganadores + transparencia**,
más el rehacer la numeración de tickets.
