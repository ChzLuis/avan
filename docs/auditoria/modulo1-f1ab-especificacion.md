# Módulo 1 — Especificación F1a (vocabulario) y F1b (esquema/conversión)

**Fecha:** 2026-08-15 · **Estado:** ESPECIFICACIÓN para aprobar. Sin implementar.
Base: [modulo1-cotizaciones-auditoria.md](modulo1-cotizaciones-auditoria.md) +
decisiones de Codex del mismo día.

---

## F1a — Vocabulario y contratos

### A.1 Clase única: `App\Support\QuoteStatus`

Espejo del patrón ya probado en `OrderStatus` (mismo archivo de referencia),
**solo lectura**, sin migrar datos:

```php
// COMERCIAL — canónicos: draft, sent, accepted, rejected, converted
comercial(?string): string          // 'borrador'→'draft'; ''→'draft'
comercialPresentacion(?string): array
// draft:    «Borrador»    s-pending
// sent:     «Enviada»     s-process
// accepted: «Aceptada»    s-done
// rejected: «Rechazada»   s-cancelled
// converted:«Convertida»  s-done  (tratamiento visual: badge propio + enlace
//                                  al pedido cuando exista FK; hasta F1b, sin enlace)
// default:  crudo + s-legacy (heredado, punteado — igual que Pedidos)

// PAGO — canónicos: pending, partial, paid, refunded (+ alias)
pago(?string): string               // 'pendiente'→'pending'; 'pagado'→'paid';
                                    // ''→'pending'; reutiliza la semántica de OrderStatus
pagoPresentacion / opcionesPago / opcionesComercial
vencida(Quote|array): bool          // valid_until < hoy && comercial ∈ {draft,sent}
                                    // DERIVADA: jamás se almacena
```

No se duplica lógica con `OrderStatus`: los mapas de pago comparten valores
pero los dominios se mantienen separados a propósito (quotes no tiene
`under_review` y orders no tiene `converted`).

### A.2 Escritores y lectores a alinear

| Sitio | Hoy | F1a |
|---|---|---|
| `VentaExtensionController:123` (`crearCotizacion`) | `status='borrador'` | `'draft'` |
| `QuoteController@duplicate:230` | `payment_status='pendiente'` | `'pending'` |
| `QuoteController@update:100-102` | acepta/escribe `'pagado'` | canónicos; input legacy aceptado vía `QuoteStatus::pago()` |
| `PortalController:113` | compara `'pagado'` | compara canónico normalizado |
| `quotes/index.blade.php` (mapa Alpine propio) | draft/sent/accepted/rejected literales | píldoras servidas por `QuoteStatus::*Presentacion` (pre-serializadas por fila, patrón `pill_*` de Pedidos) |
| `QuoteController@store` | hereda default | `'draft'` explícito + `payment_status='pending'` explícito (neutraliza el DEFAULT español del esquema **sin migrarlo** — el default de columna se corrige en F1b) |

### A.3 Contrato JSON y compatibilidad

- Respuestas de `store/update/updateFull/duplicate`: **misma forma**
  (`{quote:{...}}`); los valores de `status/payment_status` que ya emiten los
  consumidores actuales son los canónicos en casi todos los casos (la vista los
  usa); el único emisor legacy es la extensión, cuyo consumidor solo lee `ok`.
- Las **16 filas existentes** (y las 3 `borrador`) no se migran: la lectura las
  normaliza. `pendientes/filtros/badges` funcionan por canónico.
- Extensión: sin cambio de contrato (misma petición, mismo `{ok, ...}`).

### A.4 Tests F1a (`QuotesVocabularyTest`)

1. `crearCotizacion` (API token) crea `draft/pending`.
2. `duplicate` crea copia `draft/pending`.
3. `QuoteStatus::comercial('borrador')==='draft'`; presentación «Borrador».
4. `pago('pendiente')==='pending'`, `pago('pagado')==='paid'`.
5. `vencida()` true solo para draft/sent con `valid_until` pasada.
6. Fila legacy `borrador` aparece en el filtro «Borradores» (lectura).
7. `converted` presenta «Convertida» (sin enlace hasta F1b).
8. Suites base 88/88 intactas.

### A.5 Despliegue F1a

Archivos: `QuoteStatus.php` (nuevo), `VentaExtensionController`,
`QuoteController`, `PortalController`, `quotes/index.blade.php` (solo el mapa
de píldoras), test nuevo. Sin migraciones, sin datos. Ciclo estándar: PHPUnit →
deploy con backup → lint/caché ARIN → smoke lectura → conteo de combinaciones
de quotes idéntico antes/después → logs.

---

## F1b — Esquema, trazabilidad y conversión

### B.1 Migraciones (separadas, reversibles, en este orden)

**M1 — `orders.quote_id`:**
```php
Schema::table('orders', function (Blueprint $t) {
    $t->foreignId('quote_id')->nullable()->after('client_id')
      ->constrained('quotes')->nullOnDelete();
    $t->unique('quote_id');          // 1 Quote → 0..1 Order, reforzado por esquema
});
// down(): dropUnique + dropConstrainedForeignId
```
El `UNIQUE` nullable permite N pedidos sin cotización (NULL no colisiona en
MySQL) y prohíbe dos pedidos de la misma cotización **a nivel de esquema**.

**M2 — `quote_items.discount`:**
```php
$t->decimal('discount', 5, 2)->default(0)->after('price');  // % por línea 0..100
// down(): dropColumn
```
Validación en servidor: `numeric|min:0|max:100`. Redondeo: half-up a 2
decimales **por línea**, y el total = suma de líneas redondeadas (nunca
redondear solo el total: los tests de integridad comparan contra la suma).

**M3 — default de columna** (opcional, misma fase):
`payment_status` default `'pendiente'` → `'pending'` (`->change()`); las filas
existentes no se tocan (la lectura ya normaliza desde F1a).

### B.2 Preflight read-only + reporte de ambiguos (antes de cualquier UPDATE)

```sql
-- candidatos limpios
SELECT order_id, MIN(quote_id) qid FROM order_events
WHERE order_id IS NOT NULL AND quote_id IS NOT NULL
GROUP BY order_id HAVING COUNT(DISTINCT quote_id) = 1;

-- AMBIGUO A: pedido ligado a varias cotizaciones
... HAVING COUNT(DISTINCT quote_id) > 1;

-- AMBIGUO B: cotización ligada a varios pedidos
SELECT quote_id FROM (candidatos) GROUP BY quote_id HAVING COUNT(*) > 1;

-- AMBIGUO C: referencias muertas o de proyecto cruzado
LEFT JOIN quotes q ON q.id = e.quote_id
WHERE q.id IS NULL OR q.project_id <> o.project_id;
```
El backfill **solo** toma candidatos que sobreviven a A, B y C; los ambiguos se
publican en el canal para decisión manual. Ejecutar con `SET quote_id` +
verificación de conteo; rollback: `UPDATE orders SET quote_id = NULL WHERE
quote_id IS NOT NULL` (los eventos permanecen como fuente de re-derivación).

### B.3 `convert()` transaccional (pseudocódigo)

```php
return DB::transaction(function () use ($quoteId, $project) {
    $quote = Quote::whereKey($quoteId)->lockForUpdate()->firstOrFail();
    abort_unless($quote->project_id === $project->id, 403);   // revalidar EN el lock

    if ($quote->status === 'converted') {
        $existente = Order::where('quote_id', $quote->id)->first();
        return $existente
            ? response()->json(['order' => $existente, 'already' => true])   // determinista
            : abort(409, 'Convertida sin pedido rastreable (caso legacy).'); // documentado
    }

    $order = $project->orders()->create([
        ...campos copiados...,
        'quote_id' => $quote->id,
        // líneas: precio efectivo = price * (1 - discount/100), redondeo por línea;
        // order_items NO cambia de esquema: viaja el precio ya descontado y el
        // detalle prístino queda en la cotización (snapshot natural)
    ]);
    $quote->update(['status' => 'converted']);
    OrderEvent::log($project->id, 'quote_converted', [...], $order->id, $quote->id); // auditoría; FK = fuente canónica

    return response()->json(['order' => $order]);
});
```
Carrera del doble clic: el segundo request espera el lock, relee `converted`
y devuelve **el mismo pedido** (o 409 en el caso legacy). El `UNIQUE` es la
red final si algo escapara.

### B.4 Modelos, permisos y superficies

- `Order::quote(): BelongsTo` · `Quote::order(): HasOne`.
- «Convertida a PED-x» y «Originada en COT-y» leen la FK.
- Permisos: sin cambios — `convert` ya exige `quotes.editar|manage-quotes`;
  la ruta del portal para convert **no existe** y no se crea en F1b (F1c
  decidirá si el portal la ofrece).
- Superficies: panel y portal comparten controlador; el fix aplica a ambos.

### B.5 Tests F1b (`QuoteConversionTest`)

1. Convertir guarda `quote_id` y el badge inverso resuelve.
2. **Concurrencia**: dos converts en paralelo → 1 pedido, segundo recibe
   `already:true` (simulado con transacción + segundo intento tras commit).
3. `UNIQUE`: insertar segundo pedido con mismo `quote_id` lanza QueryException.
4. Multiproyecto: convert de quote ajena → 403 (dentro del lock).
5. `nullOnDelete`: borrar quote deja `order.quote_id = NULL`, pedido vivo.
6. **Integridad de totales**: quote con descuentos por línea → total del
   pedido == suma de líneas redondeadas == total de la quote.
7. Backfill: candidato limpio se rellena; ambiguos A/B/C intactos y reportados.
8. Suites base + F1a intactas.

### B.6 Despliegue F1b

Preflight en ARIN (solo lectura, publicar reporte) → aprobar → migrar M1/M2
(M3 opcional) → backfill con conteos antes/después → deploy de código →
smoke + suites → evidencia en canal. Rollback documentado por migración
(`down()`) + `SET NULL` del backfill.

---

# v2 — Ajustes de integridad (respuesta a la devolución de Codex)

## 1. Divergencia fiscal en exportadores — inventario exacto

Verificado en `quotes/index.blade.php`:

| Bloque | Líneas | Cálculo |
|---|---|---|
| Editor Alpine | 288-289 | `igv = subtotal * 0` |
| Exportador PDF | **863-864** | `igv = subtotal * 0.18` |
| Exportador imagen | **994-995** | `subtotal * 0.18` |
| Tercer exportador (nota imprimible) | **1047-1048** | `subtotal * 0.18` |

La misma cotización muestra **dos totales distintos** según el formato.
Regla F1a: **todo documento comercial usa `quotes.total` persistido** y no
pinta línea de IGV mientras no exista cálculo fiscal autorizado. Se eliminan
los tres `* 0.18`. Tests de igualdad: editor == fila == drawer == PDF ==
imagen == ticket == `quotes.total` (mismo fixture con decimales incómodos).
La facturación fiscal existente no se toca.

## 2. Cantidades — decisión respaldada por datos

- Columnas: `quote_items.quantity` **int** y `order_items.quantity` **int**
  (ARIN, SHOW COLUMNS).
- Valores reales en ARIN: `1, 2, 3, 5, 10, 12` — **todos enteros**; no hay
  evidencia de venta fraccionada.
- Validadores desalineados: `QuoteController:134` y `:360`
  (`numeric|min:0.001`) contra columnas int (MySQL truncaría el decimal).

**Decisión F1a: cantidades ENTERAS.** Alinear ambos validadores a
`integer|min:1`. Sin migración decimal — se documenta que si algún rubro
futuro exige fracciones (p. ej. kilos), será una decisión con evidencia y
migración propia. Test de borde: enviar `0.5` → 422, no truncado silencioso.

## 3. Descuentos sin error de redondeo — modelo exacto

El «precio unitario neto» queda **descartado** con contraejemplo:

```
price 33.33 · qty 3 · desc 10%
por línea:  round(33.33*3*0.9, 2)              = 89.99   ✔ exacto
por unitario neto: round(29.997,2)=30.00 ×3    = 90.00   ✘ +0.01
```

**Modelo elegido (verificable):**

- **M2** `quote_items.discount decimal(5,2) 0..100` **y M2b
  `order_items.discount` idéntica** — el descuento viaja con la línea, no se
  funde en el precio: trazabilidad completa («qué precio y qué rebaja»).
- Helper único `App\Support\LineMath::total($price, $qty, $discount)` =
  `round($price * $qty * (1 - $discount/100), 2)` (half-up, **por línea**).
  Lo consumen: editor de quotes, drawer, exportadores, `convert()` y los
  totales de pedido.
- `quotes.total` y `orders.total` = **suma de line totals** (nunca redondear
  el agregado por su cuenta).
- Conversión de líneas: copia `description→name`, `price`, `quantity`,
  **`discount`** tal cual. `order_items` gana una columna (M2b) — corrijo mi
  v1, que prometía no tocarla; la exactitud y trazabilidad pesan más.

Tests numéricos de borde: (33.33, 3, 10) → 89.99 · (0.01, 1, 50) → 0.01 →
round(0.005)=0.01 half-up · (99.99, 7, 33.33) — igualdad quote↔order tras
convertir · suma de líneas == total persistido en ambos documentos.

## 4. Backfill y rollback por manifiesto

El `SET NULL WHERE quote_id IS NOT NULL` de la v1 queda **descartado**:
borraría también los vínculos orgánicos creados por `convert()` después del
backfill. Sustituido por manifiesto:

1. Preflight genera `docs/auditoria/backfill-quote-id-manifest.json`:
   `[{order_id, quote_id, fuente:'order_events'}]` — solo supervivientes de
   A/B/C.
2. El UPDATE aplica **exactamente esas parejas** (`WHERE id IN (…) AND
   quote_id IS NULL`), y se verifica conteo aplicado == manifiesto.
3. **Rollback**: `UPDATE orders SET quote_id = NULL WHERE id IN (ids del
   manifiesto) AND quote_id = (su pareja)` — los vínculos orgánicos quedan
   intactos. El manifiesto se versiona en el repo como evidencia.

## 5. Contrato de conversión preservado

Verificado el código real: la acción de evento ya es **`converted`** (con
etiqueta existente) y el retorno actual es `{ok:true, order_id}`
(`QuoteController` retorno en `convert()`). La v1 proponía `quote_converted`
y `{order:...}` — **ambos descartados**:

- Acción: se mantiene `converted` (meta ampliada con `source`).
- Retorno: `{ok:true, order_id, already:false}` en conversión nueva;
  `{ok:true, order_id, already:true}` en repetición idempotente (campo
  **añadido**, nada sustituido).
- Legacy `converted` sin FK: dentro del lock se intenta resolver por el
  **evento indirecto único** (mismo criterio A/B/C del preflight, en caliente);
  si resuelve → fija FK y devuelve ese `order_id`; solo si es irresoluble →
  409 documentado. **Jamás se crea otro pedido.**
- Test de compatibilidad JSON: aserción de estructura exacta con y sin
  `already`, y de que ningún campo actual desaparece.
