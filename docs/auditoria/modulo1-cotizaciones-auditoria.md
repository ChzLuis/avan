# Módulo 1 — Cotizaciones · Auditoría y diseño

**Fecha:** 2026-08-15 · **Estado:** AUDITORÍA (sin código, sin esquema, sin datos)
Convención: evidencia de **ARIN** (producción) marcada; el resto coincide con el
working tree.

---

## 1. Inventario técnico

### 1.1 Modelos y tablas

**`Quote`** (`app/Models/Quote.php`, trait `HasProjectScope`) → `quotes`.
Columnas reales (ARIN): `token` (UNI), `project_id`, `client_*` (6 campos),
`status` (default **`draft`**), **`payment_status` (default `'pendiente'` a
nivel de ESQUEMA — vocabulario español en la definición de la tabla)**,
`paid_amount`, `paid_at`, `payment_proof_url/_at`, `notes`, `payment_method`,
`payment_condition`, `total`, `valid_until`, `sent_at`, `rejected_at`,
`reject_reason`, `seen_at`. Casts correctos a date/datetime/decimal.

**`QuoteItem`** → `quote_items`: descripción, precio, cantidad.
**Sin columna `discount`** (deuda confirmada: la UI captura descuento por línea
y se pierde al guardar — `QuoteController` mapea `'discount' => 0`).

Relaciones: `project()`, `client()`, `items()`. **No existe relación con
`Order`** (diseño en §4).

### 1.2 Controladores y rutas

| Superficie | Rutas | Permisos |
|---|---|---|
| Panel | `/quotes` (GET/POST), `/quotes/{q}` (GET/PUT/DELETE), `/full`, `/send`, `/duplicate`, `/seen`, `/convert` | `quotes.ver/crear/editar/eliminar` (Paso 0) |
| Portal | `/bixosales/cotizaciones*` (9 rutas espejo, sin convert) | `project.can:quotes.*\|view/manage-quotes` |
| **Público** | `/b/{slug}/c/{token}` (ver) + `/accept`, `/reject`, `/proof` (POST, throttle 10/min) | token de 64 chars, sin sesión |
| API extensión | `POST /api/copilot/venta/cotizacion` | `X-Copilot-Token` |

`PortalController` (público): `quote()` marca visto, `accept()`, `reject()`
con motivo, `proof()` sube comprobante. **El ciclo público completo ya existe.**

### 1.3 Vista y JS

`quotes/index.blade.php` (~1094 líneas): maestro-detalle pre-rediseño (mismo
patrón que Pedidos ANTES del Paso 2). Alpine embebido. Documentos:
`exportQuotePDF()` y `exportQuoteImg()` (html2canvas/jspdf). **No hay ticket
58 mm** (Pedidos sí lo tiene). WhatsApp: enlaces `wa.me` presentes.

---

## 2. Datos reales (ARIN, solo lectura)

```
16 cotizaciones · 14 con token (el enlace público SE USA)
accepted 2 (S/13.559,90) · sent 6 (S/20.569,80) · converted 5 (S/23.005,50)
borrador 3 (S/1.770,00)          ← 🔴 estado en ESPAÑOL
payment_status: 'pendiente' ×16  ← 🔴 todas, por el default del esquema
vencidas con valid_until pasado y status abierto: 2
```

### 🔴 Hallazgos de vocabulario (prioridad alta)

1. **`VentaExtensionController:123` crea cotizaciones con `status='borrador'`.**
   La vista solo mapea `draft/sent/accepted/rejected` (grep: 'converted'
   tampoco aparece citado — verificar su badge en implementación). Esas 3
   cotizaciones caen fuera de los filtros/badges del estado. Mismo patrón que
   el hotfix de pagos acaba de corregir en pedidos: **la extensión quedó fuera
   de aquel alcance para quotes** (aquel hotfix tocó solo `crearPedido`).
2. **`quotes.payment_status` nace `'pendiente'`** por default del esquema, y
   `QuoteController:100-102/230` + `PortalController:113` escriben
   `'pagado'/'pendiente'`. Todo el vocabulario de pago de quotes es español.
   `OrderStatus::pago()` ya normaliza `pagado→paid` en lectura, pero **la vista
   de quotes no usa `OrderStatus`** (mapa propio embebido).
3. **`igv = subtotal * 0`** (`quotes/index.blade.php:288`) — documentado; el
   cálculo NO se toca (límite), pero la UI muestra "IGV S/ 0,00" que es
   **engañosa**: propuesta en §5.

## 3. Flujo funcional real

```
draft ──send()──► sent ──(cliente ve: seen_at)──► accept()/reject() público
  │                                                    │
  duplicate() → nuevo draft                      accepted ──convert()──► Order
  updateFull() (edición total)                   rejected (+reject_reason)
                                                 [vencida: NO existe como estado
                                                  ni como marca — solo derivable]
```

- **Autosave: no existe.** Cerrar el modal pierde el trabajo.
- **Vigencia:** `valid_until` se guarda pero **nada la aplica**: una vencida
  sigue aceptable por el cliente en el enlace público (verificar `accept()` —
  no valida `valid_until`; confirmado por lectura de PortalController@accept).
- **Visto:** `seen_at` vía `markSeen` (panel) y al abrir el enlace público.
- **Conversión:** `convert()` copia campos, marca `converted`, **no guarda
  FK** (solo `order_events.quote_id`); no es transaccional; la idempotencia es
  un `abort_if(status==='converted')` — ver §4.
- **Exportación de listado: no existe** (Pedidos tiene `orders-export` CSV).

## 4. Diseño `orders.quote_id` (NO implementar)

- **Migración:** `orders.quote_id` BIGINT UNSIGNED NULL + índice +
  `foreignId->nullable()->constrained('quotes')->nullOnDelete()`.
  Nullable: la mayoría de pedidos no nacen de cotización.
- **Relaciones:** `Order::quote(): BelongsTo` · `Quote::order(): HasOne`.
  **Cardinalidad:** 1 cotización → 0..1 pedido (hoy `convert()` bloquea
  repetición); el esquema permite 1..N si mañana se re-cotiza — la regla de
  negocio vive en `convert()`, no en el esquema.
- **`convert()` transaccional e idempotente:**
  ```php
  DB::transaction(function () use ($quote) {
      $quote = Quote::whereKey($quote->id)->lockForUpdate()->first();
      abort_if($quote->status === 'converted', 422, 'Ya convertida.');
      $order = ... create([..., 'quote_id' => $quote->id]);
      $quote->update(['status' => 'converted']);
  });
  ```
  El `lockForUpdate` cierra la carrera del doble clic que el `abort_if` actual
  no cubre (dos requests simultáneos pasan ambos el check y crean 2 pedidos).
- **Backfill seguro** (aprobación aparte, reversible):
  ```sql
  UPDATE orders o JOIN (
    SELECT order_id, MIN(quote_id) quote_id FROM order_events
    WHERE quote_id IS NOT NULL AND order_id IS NOT NULL
    GROUP BY order_id HAVING COUNT(DISTINCT quote_id) = 1
  ) e ON e.order_id = o.id
  SET o.quote_id = e.quote_id WHERE o.quote_id IS NULL;
  ```
  **Ambigüedades:** `HAVING COUNT(DISTINCT quote_id)=1` excluye pedidos con
  eventos de 2+ cotizaciones (se listan y deciden a mano). Eventos duplicados
  de la misma quote: inofensivos (MIN = mismo valor).
- **Rollback:** `dropConstrainedForeignId('quote_id')`; el backfill se revierte
  con `SET quote_id = NULL` (los eventos siguen intactos como fuente).
- **Pruebas:** conversión guarda FK; doble submit concurrente crea 1 solo
  pedido; backfill no toca pedidos ambiguos; borrar quote no borra order
  (`nullOnDelete`); trazabilidad inversa `Quote::order`.

## 5. Propuesta UX/UI (diseño, para aprobar)

Reutilizar el patrón CERRADO de Pedidos (tabla ancha 44px + drawer enlazable
`/bixosales/cotizaciones/{id}` + tarjetas móvil + `$puede` con
`QuoteAbilities` espejo de `OrderAbilities`).

**KPIs comerciales** (servidor, único cálculo):
`Abiertas` (draft+sent) · `Por vencer 7d` · `Aceptadas` · `Conversión %`
(converted / enviadas del periodo).

**Vistas rápidas:** Todas · Borradores · Enviadas · Aceptadas · Vencidas
(derivada: `valid_until < hoy` y abierta) · Convertidas.

**Columnas:** Nº · Cliente · Total · Vigencia (con semáforo al acercarse) ·
Estado · Resultado (→ `PED-x` si convertida, vía FK) · ⋯

**Drawer:** cabecera + productos + historial (extender `OrderEvent` con
`quote.sent/seen/accepted/...` usando las columnas ya existentes) + acciones
por estado y permiso: PDF · Imagen · **Ticket (nuevo, reutilizando la plantilla
58mm de Pedidos)** · WhatsApp · Enlace público · Duplicar · **Convertir** (solo
`accepted` + `quotes.editar`).

**IGV engañoso (sin tocar cálculo):** ocultar la línea "IGV" cuando valga 0 y
mostrar "Total" a secas — la UI deja de afirmar un impuesto que no calcula.
El cálculo real llega en la fase fiscal.

**Wireframes** (compactos):
```
┌ Cotizaciones                    [Exportar] [+ Nueva] ┐   ┌─390px────────────┐
│ ABIERTAS 9 · POR VENCER 2 · ACEPTADAS 2 · CONV. 31% │   │ KPIs 2×2         │
│ Todas·Borradores·Enviadas·Aceptadas·Vencidas·Conv.  │   │ 🔍 + [Filtros N] │
│ 🔍  [Estado▾][Vigencia▾][Fecha▾][Canal▾]            │   │ ┌ tarjeta ─────┐ │
│ Nº    CLIENTE   TOTAL   VIGENCIA  ESTADO  RESULTADO │   │ │COT-14 Empresa│ │
│ COT-14 Empresa  890,00  18/08 ⚠  ●Enviada  —     ⋯ │   │ │S/890 ●Enviada│ │
│ COT-13 Cliente  490,00  16/08    ●Convert. PED-31 ⋯ │   │ └──────────────┘ │
└──────────────────────────────────────────────────────┘   └──────────────────┘
```

## 6. Hallazgos priorizados

| # | Hallazgo | Prioridad | Fase |
|---|---|---|---|
| 1 | Extensión crea quotes `status='borrador'` invisible para la vista | 🔴 | hotfix tipo pagos (mín.) |
| 2 | `quotes.payment_status` español por DEFAULT del esquema + 3 escritores | 🔴 | decidir: ¿normalizar en lectura ya (OrderStatus) y default en su momento? |
| 3 | Sin FK `orders.quote_id`; `convert()` con carrera de doble clic | 🔴 | F1 (diseño §4) |
| 4 | Descuento por línea se muestra y se pierde (`quote_items` sin columna) | 🟠 | F1 (autorizada la propuesta) |
| 5 | Vigencia sin efecto: vencida sigue aceptable en enlace público | 🟠 | F1 |
| 6 | UI afirma "IGV S/0,00" | 🟠 | F1 solo presentación; cálculo → fase fiscal |
| 7 | Sin autosave; sin export CSV; sin ticket 58mm | 🟡 | F1 |
| 8 | Vista 1094 líneas maestro-detalle pre-rediseño | 🟡 | F1 (patrón Pedidos) |

## 7. Decisiones necesarias (Codex + usuario)

1. ¿Hotfix de vocabulario de quotes (extensión `borrador→draft` + lectura
   normalizada) **antes** del rediseño, como con pagos, o dentro de F1?
2. ¿`quote_items.discount` entra en F1 (migración pequeña) junto a `quote_id`?
3. Vigencia: ¿una vencida debe bloquear `accept()` público o solo avisar?
4. ¿Ticket 58 mm para cotizaciones (reutilizando plantilla de Pedidos)?
5. Aprobación de fases: **F1a** hotfix vocabulario → **F1b** migraciones
   (`quote_id` + `discount` + backfill) → **F1c** rediseño UX (patrón Pedidos)
   → **F1d** QA/capturas/cierre.
