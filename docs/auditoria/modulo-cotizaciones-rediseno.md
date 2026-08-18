# Cotizaciones — Auditoría y propuesta de rediseño

**Fecha:** 2026-08-16 · **Estado:** PROPUESTA — *ningún código modificado*
· **Alcance:** dominio Cotizaciones (no toca Pedidos salvo dependencia probada)

> Todo lo marcado **EXISTE / PARCIAL / NO EXISTE** está contrastado contra el
> código real y contra la base de ARIN. Lo que no pude verificar se dice.

---

## 1. Auditoría técnica

### 1.1 El esquema, que es lo que manda

`quotes` (verificado en ARIN, 27 columnas):

| Campo | Tipo | Nota |
|---|---|---|
| `token` | varchar(64) UNI | enlace público del portal |
| `client_id` + 6 campos `client_*` | — | **snapshot de cliente ya existe** en la cabecera |
| `status` | varchar(255) def `draft` | |
| `payment_status`, `paid_amount`, `paid_at`, `payment_proof_*` | — | **dominio ajeno** viviendo en Cotizaciones |
| `total` | decimal(10,2) | **único importe almacenado** |
| `valid_until` | date | vigencia |
| `sent_at`, `seen_at`, `rejected_at`, `reject_reason` | — | trazabilidad mínima |
| `notes` | text | un solo campo para todo |

`quote_items` (8 columnas):

| Campo | Tipo |
|---|---|
| `quote_id`, `description` varchar(255), `price` decimal(10,2), `discount` decimal(5,2), `quantity` **int** |

### 1.2 Los seis huecos del esquema que condicionan el rediseño

| # | Hueco | Estado | Impacto en lo que pides |
|---|---|---|---|
| 1 | **`quote_items` no tiene `product_id`** | NO EXISTE | Sin enlace al catálogo no hay búsqueda por SKU, ni stock, ni "snapshot de producto": la línea es texto libre. **Bloquea la grilla tipo Excel** tal como la describes |
| 2 | **`quantity` es `int`** | — | Imposible cotizar 2,5 kg o 0,5 h. `invoice_items` sí usa decimal(10,3) |
| 3 | **No hay unidad de medida (UM)** | NO EXISTE | La columna "UM" de tu grilla no tiene dónde guardarse |
| 4 | **No hay `currency` ni `vendedor` ni `origin`** | NO EXISTE | Moneda, vendedor y origen POS/módulo del brief |
| 5 | **No se guardan `subtotal` ni `igv`** | NO EXISTE | Solo `total`. La vista documento y el PDF tendrían que recalcular, que es como se producen descuadres |
| 6 | **Un solo campo `notes`** | PARCIAL | Pides observaciones + condiciones comerciales + notas internas: tres audiencias distintas en un solo `text` |

### 1.3 Dominios mezclados

`quotes` arrastra **6 columnas de cobro** (`payment_status`, `paid_amount`,
`paid_at`, `payment_proof_url`, `payment_proof_at`, más `payment_method` y
`payment_condition`). Coincide con tu regla de no mezclar estados: hoy
Cotizaciones sí asume estado financiero.

Contexto que importa: desde hoy existe el **libro de cobros** (`payments` +
`receivable_terms`, módulo F2b/F3), que es el sitio natural de esa información.
La cotización ya no necesita llevar el cobro encima.

### 1.4 Estados reales en producción

```
status:          accepted 2 · sent 6 · borrador 3 · converted 5
payment_status:  pendiente 16   ← el 100% en español heredado
```

**`borrador` (3) y `pendiente` (16) son valores heredados en español.**
`App\Support\QuoteStatus` los normaliza **solo al leer** (decisión previa
documentada: no se migran datos). Cualquier máquina de estados nueva tiene que
seguir aceptándolos de entrada.

Vigencia: **derivada**, nunca almacenada (`QuoteStatus::vencida()` cruza
`valid_until` con el estado). Correcto y hay que conservarlo.

### 1.5 Volumen del código actual

- `resources/views/quotes/index.blade.php`: **1 583 líneas**. Listado, detalle,
  editor, exportadores y modales, todo en un archivo.
- **37 rutas** tocan cotizaciones, repartidas en cuatro universos: panel
  `/quotes`, portal comercial `/bixosales/cotizaciones`, portal fiscal
  `/f/{slug}/cotizaciones` y portal público del cliente `/b/{slug}/c/{token}`.
- `app/Support/QuoteStatus.php`, `QuoteAbilities.php`, `LineMath.php` — piezas
  sanas y reutilizables.

### 1.6 Lo que SÍ está bien y hay que conservar

- **`LineMath`**: dinero en centavos enteros, `lineCents(price, qty, discount)`
  con redondeo half-up. Es el motor de cálculo correcto y ya está probado.
- **`QuoteStatus`**: vocabulario canónico con sinónimos heredados, y la vigencia
  derivada.
- **`QuoteAbilities`**: capacidades resueltas en un solo sitio, con universo
  dual de permisos (`quotes.editar | manage-quotes`).
- **Ciclo de vida ya blindado**: una cotización convertida no se edita, no se
  reenvía ni vuelve a borrador (`bloquearSiConvertida`).
- **`orders.quote_id`** con UNIQUE: la relación COT→PED ya es sólida.
- **Portal del cliente** con token, aceptación/rechazo atómicos y comprobante.

*(Secciones 2 a 9 se completan al integrar las tres auditorías en curso.)*
