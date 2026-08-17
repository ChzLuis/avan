# F2b — Cuentas por Cobrar: entidad contable · DISEÑO AUDITADO

**Fecha:** 2026-08-16 · **Estado:** DISEÑO — *ningún cambio de esquema todavía*
· **Depende de:** F1 Cotizaciones (CERRADO), F2 CxC v1 lectura (en producción)
· **Habilita:** F3 Pagos, F4 Facturación

---

## 1. Auditoría: qué hay hoy, medido en ARIN

| Hecho | Evidencia (ARIN, 2026-08-16) |
|---|---|
| **No existe entidad de pago** | No hay tabla `payments`. Un cobro es mutación de `orders.advance_amount` + `payment_status` |
| **No existe fecha de vencimiento** | `orders`: ninguna columna `due/venc/expir`. `quotes.valid_until` es validez de la oferta, no vencimiento de cobro |
| **Nadie registra condición de crédito** | `payment_condition` es **NULL en los 21 pedidos y las 16 cotizaciones** |
| Tesorería sin uso | `cajas` **0 filas**, `caja_movimientos` **0 filas** |
| Facturación sin uso | `invoices` **0 filas** |
| Sí hay bitácora | `order_events`: 19 filas · `project_id, order_id, quote_id, user_id, action, meta(json), created_at` — append-only, base sólida |
| El dinero vive en decimales | `orders.total decimal(10,2)`, `advance_amount decimal(10,2)`, `quotes.paid_amount decimal(12,2)` |

### 1.1 El hallazgo que justifica la fase

**El "vencido" que hoy muestra Cuentas por Cobrar es ficción.** Como no existe
ninguna fecha de vencimiento, `CxcController` calcula la antigüedad desde
`created_at` y llama vencido a todo lo que pase de 15 días. Una venta a 30 días
de crédito aparece como morosa el día 16, y una venta al contado impagada
figura "al día" durante dos semanas. La cifra es exacta en el importe —eso se
verificó— pero el semáforo miente.

No es un fallo del v1: es que **el dato no existe en la base**. Por eso F2b no
es "más UI", es el dato que falta.

### 1.2 Lo que un cobro no puede hacer hoy

Al ser una mutación de columnas y no un registro:

- **No es enumerable** — dos abonos parciales son indistinguibles de uno solo
  por la suma; se pierde cuándo, quién y con qué medio entró cada uno.
- **No es reversible** — anular un cobro mal cargado obliga a restar a mano
  sobre `advance_amount`, sin rastro de que hubo corrección.
- **No es conciliable** — no hay forma de casar lo cobrado contra un extracto
  de Yape/BCP: no existe la unidad "movimiento".
- **No es atribuible** — `OrderEvent` guarda el cambio de estado, no el importe.

---

## 2. Diseño propuesto

### 2.1 Principio: libro mayor inmutable + proyección

Dos capas, y **la verdad está siempre en la primera**:

1. **`payments`** — asientos de cobro, *append-only*. Nunca se hace `UPDATE` ni
   `DELETE`: una corrección es **otro asiento** que revierte al anterior.
2. **Proyección** — `orders.payment_status` y `advance_amount` se siguen
   manteniendo, pero pasan a ser **caché derivada** del libro. Así el bot, la
   extensión, el POS y toda la UI actual siguen funcionando sin tocarse: nada
   de big-bang.

El saldo nunca se almacena: se deriva. `saldo = total − Σ(cobros vigentes)`.
Todo en **centavos enteros** (`bigint`), coherente con `LineMath`; los decimales
se quedan solo en la proyección legacy.

### 2.2 Tablas

```
payments
  id, project_id
  payable_type ENUM('order','quote')   -- a qué documento abona
  payable_id
  amount_cents  BIGINT   -- SIEMPRE positivo; el signo lo da la reversión
  method        VARCHAR(40)   -- yape, plin, efectivo, transferencia, tarjeta
  reference     VARCHAR(120)  -- nº de operación / voucher
  received_at   DATETIME      -- cuándo entró el dinero (≠ created_at)
  user_id, source VARCHAR(30) -- panel, bot, extension, pos, portal
  reverses_id   -- si no es null, este asiento anula al indicado
  reversal_reason VARCHAR(200)
  meta JSON, created_at
  ÍNDICES: (project_id, payable_type, payable_id), (project_id, received_at)

receivable_terms            -- el vencimiento que hoy no existe
  id, project_id
  payable_type, payable_id
  numero SMALLINT           -- 1..n: soporta cuotas sin forzarlas
  due_date DATE
  amount_cents BIGINT
  created_at
  UNIQUE (payable_type, payable_id, numero)
```

Un documento al contado genera **una** fila con `numero=1` y
`due_date = fecha del documento`. Uno a crédito, una por cuota. La misma
estructura sirve para ambos, sin ramas especiales.

### 2.3 Reglas que el código debe garantizar

1. **Nunca se edita un cobro.** Corregir = insertar reversión con
   `reverses_id`. Un asiento solo puede revertirse una vez (UNIQUE parcial).
2. **No se sobrecobra.** `Σ cobros vigentes ≤ total` del documento, validado
   dentro de la transacción con `lockForUpdate` sobre el documento.
3. **La proyección se recalcula siempre desde el libro**, nunca por
   incremento: `advance_amount = Σ/100`, y el `payment_status` sale de comparar
   con el total (`pending` / `partial` / `paid`).
4. **Vencido es `due_date < hoy`**, no "días desde que se creó".
5. Todo asiento deja su `OrderEvent`, dentro de la misma transacción.

### 2.4 Retrocompatibilidad y backfill

- Los 10 pedidos con saldo y la cotización aceptada reciben su fila en
  `receivable_terms` con `due_date` = fecha del documento + plazo por defecto
  del proyecto (0 = contado).
- Cada `advance_amount > 0` existente se convierte en **un** asiento de
  `payments` con `source='backfill'` y `received_at = updated_at`, para que el
  saldo derivado coincida exactamente con el actual.
- **Invariante de aceptación:** tras el backfill, el total de CxC debe seguir
  siendo **45 196,60 en 11 documentos**. Si cambia un céntimo, se revierte.
- Backfill transaccional con PDO puro y verificación de `ROW_COUNT()` por fila,
  igual que el de `orders.quote_id` en F1b.

### 2.5 Lo que F2b NO incluye

Para no repetir el error de ensanchar una fase: aquí **no** entran el registro
de cobros desde POS/bot (eso es F3, con `PagoController` como adaptador
legacy), ni la conciliación bancaria, ni nada fiscal (F4). F2b entrega la
entidad, el vencimiento real y la proyección; los flujos de escritura vienen
después.

---

## 3. Plan de ejecución

| Paso | Contenido | Riesgo |
|---|---|---|
| F2b-1 | Migraciones `payments` y `receivable_terms` (idempotentes, sin `down()` destructivo) | Bajo: tablas nuevas, nada existente se toca |
| F2b-2 | `Payment` y `ReceivableTerm` + servicio `Ledger` (registrar, revertir, proyectar) con sus contratos | Bajo |
| F2b-3 | `CxcController` pasa a leer del libro; el vencido deja de ser ficción | Medio: cambia la cifra de "vencido" (a mejor) |
| F2b-4 | Backfill con manifiesto y verificación del invariante 45 196,60 / 11 | **Alto: toca datos de producción — requiere autorización explícita** |
| F2b-5 | QA Playwright + capturas + revisión visual | Bajo |

**Autorización requerida antes de F2b-1** (crear tablas en ARIN) y de nuevo,
por separado, antes de F2b-4 (backfill).

---

## 4. Condiciones de cobro: configurables por negocio

**Decisión del usuario (2026-08-16): configurable para cada negocio.** Es lo
coherente con un producto multiempresa — una bodega cobra al contado y una
distribuidora fía a 30 días; el sistema no debe imponer ninguna de las dos.

Se resuelve con `ProjectSetting` (mecanismo ya existente, `$project->setting()`),
sin columnas nuevas en `projects`:

| Ajuste | Valor | Significado |
|---|---|---|
| `cxc_plazo_dias` | entero, por defecto `0` | Días desde el documento hasta el vencimiento. `0` = contado |
| `cxc_cuotas_activas` | `'0'` / `'1'`, por defecto `'0'` | Habilita el plan de cuotas en la UI del documento |
| `cxc_dias_aviso` | entero, por defecto `3` | Días antes del vencimiento para marcar "por vencer" |

Jerarquía: **el documento manda sobre el proyecto**. `receivable_terms` guarda
el vencimiento ya resuelto, así que cambiar el ajuste del proyecto no reescribe
el pasado — solo afecta a lo que se cree después. Esto es deliberado: un
vencimiento pactado es un hecho, no una preferencia.

Para el backfill, los documentos existentes toman `cxc_plazo_dias` del proyecto
(hoy sin definir ⇒ **0, contado**), que es la lectura conservadora: nada nace
marcado como "aún no vence" sin que alguien lo haya pactado.

Las cuotas quedan soportadas por el esquema desde el primer día (`numero` en
`receivable_terms`), pero su UI solo aparece si el negocio las activa. Así no
se paga complejidad que la mayoría no usa.
