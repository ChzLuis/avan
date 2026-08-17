# F1b — Expediente para auditoría (NO autorizada para implementación)

**Fecha:** 2026-08-15 · Estado: **DISEÑO / EXPEDIENTE**. Cero escrituras en
producción. El código listado existe como **borrador local en el working tree**
(anexo del diff, no desplegado, no autorizado).

## 1. Preflight ARIN (solo lectura, ejecutado)

```
candidatos_limpios            5
ambiguo_A_pedido_multi_quote  0
ambiguo_B_quote_multi_pedido  0
ambiguo_C_muertas_o_cruzadas  0
```

Pares candidatos (con validación de mismo proyecto vía JOIN):
`(26,25) (29,27) (32,28) (33,29) (34,30)` — reporte completo en
`backfill-quote-id-manifest.json` (marcado REPORTE DE PREFLIGHT, no manifiesto
aplicable; el definitivo se genera y aprueba en la ejecución autorizada).

## 2. Inventario y orden exacto del paquete

| # | Pieza | Archivo (borrador en working tree) |
|---|---|---|
| 1 | M1 FK+UNIQUE | `database/migrations/2026_08_16_100001_add_quote_id_to_orders_table.php` |
| 2 | M2 discount quotes | `..._100002_add_discount_to_quote_items_table.php` |
| 3 | M2b discount orders | `..._100003_add_discount_to_order_items_table.php` |
| 4 | M3 default canónico | `..._100004_canonical_default_on_quotes_payment_status.php` |
| 5 | `App\Support\LineMath` | escrito |
| 6 | Modelos | `Order::quote()` + fillable `quote_id`; `Quote::order()`; `discount` en fillables de ambos items |
| 7 | `convert()` transaccional | reescrito en `QuoteController` (borrador) |
| 8 | UI/exportadores + persistencia de `discount` en store/updateFull | **PENDIENTE de diseño fino** — no redactado aún |
| 9 | `QuoteConversionTest` | PENDIENTE (matriz en §7) |

Orden de ejecución en producción: M1→M2→M2b→M3 → backfill → código.

## 3. Migraciones: nombres reales y compatibilidad

- **ARIN: MySQL `8.4.10`** (verificado por `SELECT VERSION()`).
- Nombres por convención Laravel que crearán M1:
  FK `orders_quote_id_foreign` · índice único `orders_quote_id_unique`
  (el `down()` usa `dropUnique(['quote_id'])` + `dropConstrainedForeignId`,
  que resuelven esos mismos nombres).
- **`change()` evitado por completo**: M3 usa DDL directo
  `ALTER TABLE quotes ALTER COLUMN payment_status SET DEFAULT 'pending'`
  — sintaxis soportada por MySQL 8.4; sin dependencia de doctrine/dbal;
  no toca datos ni el tipo de columna. En sqlite (tests) es no-op documentado:
  el default explícito lo pone el código desde F1a.
- UNIQUE nullable sobre `quote_id`: en MySQL 8 los NULL no colisionan —
  N pedidos sin cotización conviven; dos pedidos de la misma quote, imposibles.

## 4. `convert()` (borrador escrito — resumen del contrato)

Transacción + `lockForUpdate` + revalidación de proyecto y estado **dentro**
del lock. Nueva conversión → `{ok:true, order_id, already:false}` + evento
`converted` (auditoría; FK canónica). Repetición → **mismo** `order_id`,
`already:true`. Legacy `converted` sin FK → resolución por evento indirecto
único (mismo criterio del preflight, en caliente, fija la FK) y solo si es
irresoluble → 409. Jamás un segundo pedido. Total del pedido =
`LineMath::sum(items)`.

## 5. LineMath y espejo JS sin discrepancias binarias

- PHP: `round($price*$qty*(1-$d/100), 2)` — half-up nativo de `round()`.
- JS del editor/exportadores: `Math.round((price*qty*(1-d/100) + Number.EPSILON) * 100) / 100`
  — el término `Number.EPSILON` corrige los casos límite binarios
  (p. ej. `1.005*100 = 100.49999…`) para igualar el half-up de PHP.
- Test de paridad propuesto: fixture compartido de casos incómodos
  (33.33×3@10, 0.01×1@50, 99.99×7@33.33, 1.005×1@0) asertado en PHPUnit y,
  en la vista, total mostrado == `quotes.total` persistido (las superficies ya
  se igualaron en F1a).

## 6. Backfill idempotente + rollback por pareja

1. En la ejecución autorizada: regenerar preflight → generar manifiesto
   definitivo (pares + conteo) → publicarlo aquí → aprobación → aplicar.
2. UPDATE por pareja: `SET quote_id=:q WHERE id=:o AND quote_id IS NULL`
   (idempotente: re-ejecutar no toca filas ya fijadas).
3. Verificación: filas afectadas == parejas del manifiesto; conteo de
   `quote_id NOT NULL` == 5.
4. Rollback **por pareja**: `SET quote_id=NULL WHERE id=:o AND quote_id=:q` —
   los vínculos orgánicos creados por `convert()` después no se tocan jamás.

## 7. Matriz de tests (`QuoteConversionTest`, a escribir tras autorización)

| Caso | Verifica |
|---|---|
| Conversión guarda FK y badge inverso | `order.quote_id`, `Quote::order` |
| Doble conversión secuencial | mismo `order_id`, `already:true`, 1 solo pedido |
| Carrera simulada | 2º intento tras commit devuelve el mismo pedido |
| UNIQUE | segundo pedido con misma quote → `QueryException` |
| Multiproyecto | quote ajena → 403 dentro del lock |
| `nullOnDelete` | borrar quote → pedido vivo con `quote_id=NULL` |
| Legacy sin FK | evento único → resuelve y fija FK; ambiguo → 409 |
| Bordes numéricos | 33.33×3@10=89.99 · 0.01×1@50=0.01 · paridad PHP/JS |
| Igualdad quote↔order | totales == suma de líneas en ambos documentos |
| Persistencia discount | store/updateFull guardan 0..100; 150 → 422 |
| JSON | `{ok,order_id,already}` — nada desaparece |
| Regresión | 103 base intactos |

## 8. Despliegue escalonado (cuando se autorice)

| Etapa | Gate |
|---|---|
| 1. Migraciones M1-M3 en ARIN (backup previo + `migrate --pretend` primero) | esquema verificado por SHOW COLUMNS/INDEX |
| 2. **Ventana de compatibilidad**: el código viejo ignora las columnas nuevas (nullable/default 0) → cero downtime | smoke lectura |
| 3. Backfill por manifiesto aprobado | conteos == manifiesto |
| 4. Código (convert + persistencia discount + UI) | lint/caché/smoke |
| 5. Suites + capturas + logs | 54/54 UI + PHPUnit total |
| Rollback | código: backups deploy.py · backfill: por pareja · esquema: `down()` por migración en orden inverso |

## 9. Punto abierto que la auditoría debe decidir

La pieza 8 del inventario (reintroducir el input de descuento + persistirlo en
`store`/`updateFull` + espejo JS) quedó **sin redactar a propósito** al llegar
tu instrucción de expediente: prefiero que audites este plan y el borrador
existente antes de escribir más código.

---

# v2 — Correcciones de la auditoría de Codex

## A. Anexo: SQL exactas del preflight (reproducibles)

Ejecutadas 2026-08-15 19:06:54 (NOW() ARIN) · MySQL 8.4.10-10.

```sql
-- Candidatos limpios ENRIQUECIDOS (proyectos + eventos fuente)
SELECT e.order_id, MIN(e.quote_id) quote_id,
       o.project_id order_project_id, q.project_id quote_project_id,
       GROUP_CONCAT(e.id ORDER BY e.id) event_ids
FROM order_events e
JOIN orders o ON o.id = e.order_id
JOIN quotes q ON q.id = e.quote_id AND q.project_id = o.project_id
WHERE e.quote_id IS NOT NULL
GROUP BY e.order_id, o.project_id, q.project_id
HAVING COUNT(DISTINCT e.quote_id) = 1 ORDER BY e.order_id;

-- Ambiguo A / B / C: identicas a la seccion 1, anexadas integras aqui
-- (A: >1 quote por pedido; B: >1 pedido por quote; C: LEFT JOIN quotes
--  con q.id IS NULL OR q.project_id <> o.project_id)
```

Resultados: 5 limpios (todos proyecto 18/18, eventos fuente [2] [4] [7,9]
[11] [15]) · A=0 · B=0 · C=0. JSON enriquecido actualizado.

## B. LineMath reescrito: aritmética entera exacta (borrador ajustado)

Sin floats en el camino crítico: precio a CENTAVOS (parser string 2dp),
descuento a BASIS POINTS 0..10000, línea half-up =
intdiv(cents*qty*(10000-bp) + 5000, 10000), total = suma de centavos, decimal
solo en el borde. Sin clamps: dato imposible lanza excepción (150%, 1.005 con
3dp, qty<1, overflow). Límite anti-overflow: price_cents*qty <= 10^14.

Bordes ejecutados en el borrador:

```
33.33x3@10    = 89.99   ·   0.01x1@50 = 0.01
0.05x1@50     = 0.03  (half-up de 0.025 — acarreo)
multilínea    = 90.00 (89.99 + 0.01)
99.99x7@33.33 = 466.64
```

ATENCIÓN — corrección al fixture de tu instrucción: 99.99x7@33.33 NO es
466.62. Aritmética exacta: 69993 x 6667 = 466 643 331; +5000; intdiv 10^4 =
46 664 centavos = 466.64. (Float de control: 699.93 x 0.6667 = 466.6433…,
mismo redondeo.) El expediente adopta 466.64 con esta derivación como
evidencia.

Espejo JS (contrato para la fase autorizada): mismos parsers string→entero;
Number es seguro hasta 2^53 y el producto intermedio se acota con el mismo
límite 10^14 (o BigInt). Prohibido float+EPSILON. Fixture de paridad PHP↔JS
compartido con estos bordes + sumas multilínea.

## C. Inventario TOTAL de consumidores precio×cantidad

| Superficie | Hoy | F1b |
|---|---|---|
| QuoteController@store | sum(price*qty) | LineMath::sum + persistir discount |
| @updateFull (panel) | ídem | ídem |
| @updateFullPortal | ídem | ídem |
| @duplicate | copia items sin discount | copiar discount |
| @convertirPortal (quote→factura) | líneas con IGV propio | copiar discount al cálculo de línea; IGV fiscal NO se toca (fuera de F1) |
| convert() (quote→pedido) | corregido en borrador | LineMath::sum + discount por línea |
| Portal público portal-quote | total de línea bruto | descuento visible; total = quotes.total |
| Editor de quotes (JS subtotal) | fórmula float con d=0 | espejo JS entero |
| Serialización quotes | sin discount | incluir discount por item |
| 3 exportadores de quotes | total persistido (F1a) | + columna de descuento por línea |
| OrderController@store | sum(price*qty) | LineMath::sum (discount opcional) |
| Serialización orders (items) | sin discount | incluir discount |
| Drawer de Pedidos (productos) | precio×cant | mostrar descuento cuando exista |
| PDF/imagen/ticket de Pedidos | precio×cant | LineMath por línea |

## D. Estrategia de concurrencia (honesta)

- Limitación documentada: el SQLite de la suite no reproduce dos conexiones
  con lockForUpdate real.
- Cobertura determinista en SQLite: (1) doble conversión secuencial → mismo
  order_id/already:true; (2) desalineado inverso — pedido con FK existente y
  quote sin converted → re-alineación y already:true, SIN intento de segundo
  insert (rama nueva en el borrador de convert()); (3) UNIQUE violado a mano →
  QueryException (la red del esquema existe y se demuestra).
- Integración MySQL (procedimiento manual para la ejecución autorizada): dos
  conexiones sobre el clon; A: BEGIN + SELECT ... FOR UPDATE; B: convert
  bloqueado; commit de A; B relee converted y devuelve already:true.
  Evidencia por transcripción en el canal.
- Garantía estructural: ninguna ruta crea segundo pedido — las tres ramas de
  convert() (converted+FK · converted sin FK→resolución/409 · no-converted con
  FK huérfana→re-alineación) retornan sin insertar; el UNIQUE es la red final
  dentro de la transacción (rollback, no 500 en el reintento del cliente).

## E. Esquema, modelos y despliegue (correcciones)

- Cast discount => decimal:2 añadido en QuoteItem y OrderItem (borrador).
- Migraciones renombradas a secuencia real: 2026_08_15_200001..200004
  (posteriores a 2026_08_15_090000, sin colisión).
- M3 en SQLite = no-op (documentado en la migración); la prueba del default
  real es en MySQL vía information_schema.COLUMNS (COLUMN_DEFAULT de
  quotes.payment_status) antes/después — gate de la etapa 1.
- Rollback en ORDEN INVERSO al despliegue: 1º código dependiente (backups
  deploy.py) → 2º backfill por pareja → 3º migraciones down() M3→M2b→M2→M1.
- Ensayo previo obligatorio en clon de esquema: migrate --pretend + ciclo
  up/down/up completo, con transcripción publicada antes de tocar ARIN.

Sin implementación adicional, sin tests de F1b ejecutados, sin manifiesto
aplicable, sin escrituras en ARIN.

---

# v3 — Correcciones de la segunda auditoría

## A2. Las CUATRO SQL completas + candidatos como supervivientes explícitos

Reejecutadas read-only (2026-08-15, MySQL 8.4.10-10). Los candidatos ya no se
infieren de contadores paralelos: son supervivientes de A -> B -> C por CTE.

```sql
-- (1) AMBIGUO A: pedido con eventos de mas de una cotizacion
SELECT e.order_id FROM order_events e
WHERE e.order_id IS NOT NULL AND e.quote_id IS NOT NULL
GROUP BY e.order_id HAVING COUNT(DISTINCT e.quote_id) > 1;

-- (2) AMBIGUO B: cotizacion apuntada por mas de un pedido (sobre pares 1:1)
WITH pares AS (
  SELECT e.order_id, MIN(e.quote_id) AS quote_id FROM order_events e
  WHERE e.order_id IS NOT NULL AND e.quote_id IS NOT NULL
  GROUP BY e.order_id HAVING COUNT(DISTINCT e.quote_id) = 1)
SELECT quote_id FROM pares GROUP BY quote_id HAVING COUNT(*) > 1;

-- (3) AMBIGUO C: referencia muerta o de proyecto cruzado
SELECT DISTINCT e.order_id FROM order_events e
JOIN orders o ON o.id = e.order_id
LEFT JOIN quotes q ON q.id = e.quote_id
WHERE e.quote_id IS NOT NULL
  AND (q.id IS NULL OR q.project_id <> o.project_id);

-- (4) CANDIDATOS = supervivientes explicitos de A, B y C
WITH pares AS (
  SELECT e.order_id, MIN(e.quote_id) AS quote_id FROM order_events e
  WHERE e.order_id IS NOT NULL AND e.quote_id IS NOT NULL
  GROUP BY e.order_id HAVING COUNT(DISTINCT e.quote_id) = 1),
sin_A AS (
  SELECT p.* FROM pares p WHERE NOT EXISTS (
    SELECT 1 FROM order_events e2
    WHERE e2.order_id = p.order_id AND e2.quote_id IS NOT NULL
      AND e2.quote_id <> p.quote_id)),
sin_B AS (
  SELECT s.* FROM sin_A s WHERE NOT EXISTS (
    SELECT 1 FROM sin_A s2
    WHERE s2.quote_id = s.quote_id AND s2.order_id <> s.order_id)),
sin_C AS (
  SELECT s.* FROM sin_B s
  JOIN orders o ON o.id = s.order_id
  JOIN quotes q ON q.id = s.quote_id AND q.project_id = o.project_id)
SELECT s.order_id, s.quote_id, o.project_id order_project_id,
       q.project_id quote_project_id,
       (SELECT GROUP_CONCAT(e.id ORDER BY e.id) FROM order_events e
        WHERE e.order_id = s.order_id AND e.quote_id = s.quote_id) event_ids
FROM sin_C s
JOIN orders o ON o.id = s.order_id
JOIN quotes q ON q.id = s.quote_id
ORDER BY s.order_id;
```

Resultado de (4): los MISMOS 5 pares del JSON — coincide, por lo que el JSON
no cambia (regla impuesta por la auditoria).

## B2. LineMath: contrato sin floats (borrador reescrito y ejecutado)

- Nucleo publico: lineCents(string,int,string):int · sumCents():int ·
  format(int):string "X.YY". total()/sum() devuelven STRING.
- canon() es el unico punto que acepta numerico JSON legacy: int -> "N.00";
  float solo si 2dp lo reproducen (1.005 -> excepcion). Documentado como
  compatibilidad de frontera; el payload nuevo de Alpine enviara strings.
- Docblock residual corregido: bordes canonicos con 466.64.
- Ejecutado en vivo: total("33.33",3,"10") -> "89.99";
  total("99.99",7,"33.33") -> "466.64"; sum con legacy float -> "90.00";
  canon(33) -> "33.00"; canon(1.005) -> excepcion.
- Tests de parser/limites/overflow: diseñados para la fase autorizada.

## C2. convert(): pseudocodigo exacto del borrador (verificable en el diff)

```
convert(quote):
  try: return convertTx(quote, project)
  catch QueryException con 'orders_quote_id_unique':   # FUERA de la tx
      ganador = Order.where(quote_id).first()
      si ganador -> 200 {ok, order_id, already:true}   # primer request NUNCA 500
      si no      -> 409 controlado

convertTx(quote, project):  DB::transaction:
  quote = lockForUpdate + firstOrFail
  403 si quote.project_id != project.id                # revalidado EN el lock

  si comercial(status) == 'converted':
      existente = Order.where(quote_id).first()
      si !existente:                                   # legacy sin FK
          candidatos = eventos(quote_id=este).distinct(order_id)
          si count==1:
              A: el pedido NO tiene eventos de OTRA quote
              C: Order del MISMO proyecto (B: count==1 lo cubre)
              si pasa A y C:
                  UPDATE ... SET quote_id WHERE id=? AND quote_id IS NULL
                  existente = fresh() solo si quedo apuntando a ESTA quote
      409 si !existente                                # jamas segundo pedido
      200 {already:true}

  desalineado = Order.where(quote_id=este).first()     # FK existe, status no
  si desalineado: status='converted'; 200 {already:true}

  crear pedido: quote_id + items con discount + total = LineMath (string 2dp)
  status='converted'; evento 'converted'
  200 {ok, order_id, already:false}
```

## D2. convertirPortal (quote -> factura): descuento BLOQUEADO en F1b

invoice_items.unit_price tiene 2dp y no hay columna discount: un unitario neto
produciria 90.00 donde corresponde 89.99, y alterar el modelo fiscal excede
F1b. Diseño: 422 ANTES de crear nada si alguna linea tiene discount > 0, con
mensaje "La emision de comprobantes para cotizaciones con descuento se
habilitara con la fase de Facturacion". Cotizaciones sin descuento: flujo
intacto. Test de cero mutaciones (sin invoice, sin items, sin cambio de
status). Deuda registrada en el roadmap, seccion Facturacion (F4).

## E2. Tabla final: archivos y gates

| Archivo (borrador local) | Estado | Gate |
|---|---|---|
| 2026_08_15_200001..4 (migraciones) | escritas | --pretend + up/down/up en clon; information_schema para default M3 |
| app/Support/LineMath.php | reescrito v3, bordes ejecutados | tests parser/limites/formula en fase autorizada |
| Models Order/Quote/QuoteItem/OrderItem | relaciones + fillables + casts | suite completa |
| QuoteController convert()+convertTx() | pseudocodigo implementado | matriz de tests + conflicto UNIQUE determinista |
| convertirPortal (bloqueo 422) | diseñado, NO escrito | test de cero mutaciones |
| UI/serializacion/exportadores (14 superficies) | diseñadas, NO escritas | paridad PHP-JS + capturas |
| Backfill | solo diseño; manifiesto aplicable pendiente | preflight regenerado + aprobacion expresa |

Fuera de este expediente: ejecutar tests F1b, completar UI, escribir en ARIN.


---

## FICHA DE CIERRE — F1b (2026-08-15, producción ARIN)

| Concepto | Valor |
|---|---|
| Backup pre-migración | `/home/arindg/_deploy_backups/20260815_223712/f1b_pre_migracion.sql` · 32 960 B · `0600` · fuera del docroot (404 por HTTP) |
| SHA-256 del backup | `9960e6ef70c65d9756779208c5d2cf294ce2746eb050b6930a4a5f7f71036ce8` |
| Migraciones | **batch 26**, ids 128-131 — M1 `quote_id` (FK nullOnDelete + UNIQUE), M2 `quote_items.discount`, M2b `order_items.discount`, M3 default `pending` |
| Stamp del código (11 archivos) | `20260815_224811` — SHA-256 local/remoto 11/11 |
| Stamp de la corrección 6b (3 archivos) | `20260815_232040` — 3/3 |
| Manifiesto de backfill | `docs/auditoria/backfill-quote-id-manifest-aplicable.json` · estado **APLICADO** |
| SHA-256 de pares (intacto antes y después) | `fa59edeb161e439fc098d8f26464093f441325d4ae9ccd8d5dc30820f557bb12` |
| Backfill | transacción única, PDO puro (sin observers/eventos) · `ROW_COUNT` **1,1,1,1,1** · COMMIT 2026-08-16 04:25:12 (hora ARIN) |
| Pares vinculados | 26→25 · 29→27 · 32→28 · 33→29 · 34→30 (todos proyecto 18, cotización `converted`) |
| Invariantes post-COMMIT | mapeo 5 · faltantes 0 · inesperados 0 · relacional 5 · candidatos NULL 0 |
| Sin variación | orders 21 · quotes 16 · order_items 33 · quote_items 33 · order_events **19** · discount≠0 en 0/0 · totales 5/5 IGUAL |
| Log y disponibilidad | `laravel.log` +0 bytes en todo el despliegue · HTTP 302/200 · Playwright productivo 4/4 |

### Deudas DIFERIDAS explícitamente (no son de F1b)

**F1c (visual/operativo):** solape de 14 px entre la tarjeta de productos y
CLIENTE · tabla/editor en móvil · labels crudos `draft/sent/accepted/rejected`
en el stepper · botón visible de **Convertir en pedido**.

**F4 (fiscal):** `invoice_items` sin columna de descuento — `convertirPortal`
responde **422** si alguna línea trae descuento. No se toca hasta su fase.

**Operativa/seguridad (fuera del módulo):** health-check de `deploy.py`
apuntando a `tecsist.net`, dominio que ya no resuelve (el gate lleva tiempo
ciego, siempre 000) · `storage/logs/laravel.log` de 117 MB sin rotar ·
`/tmp/telemax_dump.sql` (174 KB, `0644`, legible por cualquier usuario del
sistema) — **requiere decisión del usuario**, no se tocó.

**Pendiente del usuario:** rotar la contraseña temporal de `qa@arindg.com`
(`QaDrawer2026tmp`) y decidir el estado comercial de los pedidos 20, 21 y 22.
