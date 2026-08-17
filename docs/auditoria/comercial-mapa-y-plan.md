# Módulo comercial — mapa actual, propuesta y orden de implementación

**Fecha:** 2026-08-14 · **Estado:** auditoría cerrada, sin código modificado.
Decisión tomada: pantalla única con panel lateral sticky. **Wizard descartado.**

---

## A. MAPA DE RUTAS: actual → propuesta

Existen **dos mundos paralelos** que comparten controladores:
el **panel principal** (`/orders`, `/quotes`) y el **portal de facturación**
(`/bixofact/...`, métodos `*Portal`). Esta reestructuración toca solo el panel.

| # | Flujo | Ruta hoy | ¿Vista? | Propuesta |
|---|---|---|---|---|
| 1 | Pedidos (listado) | `GET /orders` → `orders` | `orders/index.blade.php` (984 l.) | **Se mantiene** la ruta; la vista se parte: listado a ancho completo |
| 2 | Nuevo pedido | `GET /orders/create` **declarada, sin método** | ❌ no existe | **Implementar** `create()` + `orders/create.blade.php` |
| 3 | Detalle de pedido | `GET /orders/{order}` → **devuelve JSON** | ❌ no hay vista | **Nueva** `orders/show.blade.php`; el JSON pasa a `/orders/{order}/data` |
| 4 | Cotizaciones (listado) | `GET /quotes` → `quotes` | `quotes/index.blade.php` (1094 l.) | **Se mantiene**; añadir estado *Vencida* |
| 5 | Nueva cotización | `GET /quotes/create` **declarada, sin método** | ❌ no existe | **Implementar** `create()` + `quotes/create.blade.php` |
| 6 | Detalle de cotización | `GET /quotes/{quote}` → **devuelve JSON** | ❌ no hay vista | **Nueva** `quotes/show.blade.php` con conversión a pedido |
| 7 | Venta Express | `GET /venta-express` → `PosController@express` | `ventas/express.blade.php` (441 l.) | **No se toca.** Queda como flujo reducido |
| 8 | Panel de alertas | `#panel-right` en `comercial/layouts/app.blade.php` | columna fija | **Convertir a drawer** (el estado `panelOpen` ya existe) |

**Hallazgo que ahorra trabajo:** `Route::resource('orders')` y `Route::resource('quotes')`
**ya declaran** `create`, `show` y `edit`. No hay que crear rutas: hay que
implementar los métodos que faltan y devolver vistas.

---

## B. RASTRO DEL PRECIO Y EL IGV (sin modificar nada)

### Dónde vive cada dato

| Tabla | Campos fiscales | Qué guarda |
|---|---|---|
| `products` | `price`, `has_tax`, `tax_rate` | **1624 productos: `has_tax = 0`, `tax_rate = 18.00`** |
| `order_items` | `name`, `price`, `quantity` | **Sin impuesto y sin descuento** |
| `orders` | `total`, `discount`, `shipping_cost`, `advance_amount` | **Sin subtotal ni IGV** |
| `quote_items` | `description`, `price`, `quantity` | **Sin impuesto y sin descuento** |
| `quotes` | `total` | **Sin subtotal, IGV ni descuento** |
| `invoices` | `subtotal`, `igv`, `total`, **`igv_included`** | Único sitio con desglose fiscal real |
| `invoice_items` | `igv_amount` | Desglose por línea |

### Dónde ocurre el cálculo

**Un solo lugar: `InvoiceController@store` (líneas 126-155).**

```php
$igvIncluded = $request->boolean('igv_included', true);   // ← por defecto TRUE
$igvRate = 0.18;
if ($igvIncluded) {
    $lineSub = round($lineTotal / 1.18, 2);   // precio BRUTO: se le EXTRAE el IGV
} else {
    $lineIgv = round($lineTotal * $igvRate, 2); // precio NETO: se le SUMA
}
```

### Respuesta a la pregunta: ¿brutos o netos?

**Los precios almacenados se tratan hoy como BRUTOS (IGV incluido).**
La facturación asume `igv_included = true` por defecto y divide entre 1,18 para
obtener la base imponible.

**El sistema es coherente hoy, pero por casualidad:**

1. Pedidos y cotizaciones **no calculan impuesto en absoluto** — `total` es la
   suma cruda de líneas.
2. `quotes/index.blade.php:288` hace `get igv() { return this.subtotal * 0 }`,
   que da el mismo resultado que no calcular nada.
3. Los 1624 productos tienen `has_tax = 0`, así que **ningún producto está
   marcado como afecto**. El `tax_rate = 18.00` está guardado pero inactivo.

**El riesgo real:** el día que alguien active `has_tax` en un producto —o que
la tienda pública lo lea, cosa que ya hace (`CatalogQueryService::toCard`
expone `hasTax` y `taxRate`)— aparecerá un IGV en la tienda que **pedidos y
cotizaciones seguirán ignorando**, y facturación volverá a extraerlo del precio
bruto. Habría tres criterios distintos conviviendo.

### Decisión tomada (2026-08-14) — ver Fase 7 al final del documento

El modelo fiscal quedó definido: **modalidad de precios** y **tratamiento
tributario** son ejes independientes, configurables por proyecto, con herencia
al producto y desglose opcional en cotizaciones. **No se implementa todavía.**

---

## C. HALLAZGO ADICIONAL: descuentos fantasma

La UI de cotizaciones tiene columna de descuento por línea y la usa en el
cálculo del subtotal, pero **`quote_items` no tiene columna `discount`**.
En `QuoteController` el descuento se mapea literalmente como `'discount' => 0`.

**Consecuencia:** el descuento por línea se ve en pantalla, altera el total que
el vendedor lee… y **se pierde al guardar**. Al reabrir la cotización el
descuento vuelve a 0 y el total cambia.

Esto corrige mi análisis anterior, donde di el descuento por línea como
funcional. Está en la interfaz, no en los datos.

---

## D. QUÉ SE REUTILIZA (no reconstruir)

**Backend operativo:**
- `QuoteController@convert` — **conversión cotización → pedido ya existe**
- `QuoteController@duplicate`, `@send`, `@markSeen`
- `OrderController@pay` (registrar pago), `@issueDocument` (emitir comprobante),
  `@events` (actividad), `@exportCsv`, `@tag`
- `InvoiceController` completo con integración SUNAT

**Front ya construido:**
- Exportación: `exportOrderPDF()`, `exportOrderImg()`, `printOrderTicket()`
  (html2canvas + jsPDF) — patrón replicable a cotizaciones
- Plantillas de WhatsApp con envío (`waTemplates`, `sendWa`)
- Búsqueda de productos que rellena línea
- Barra de estados con `done/active/idle` (`q-status-step`)
- `kpis` calculados en `OrderController@index`
- `search` + `filterStatus` en el listado de pedidos

**Campos que YA existen para los filtros pedidos** (no hay que migrar nada):
`status`, `payment_status`, `delivery_status`, `delivery_type`, `sales_channel`,
`kitchen_status`, `delivery_person_id`, `document_status`, `order_type`.
**Los filtros por estado/pago/entrega/canal/responsable que pides ya tienen su
dato en la base.**

---

## E. CAMBIOS NECESARIOS

### Sin migración (solo vistas y controladores)
1. `OrderController@create` + `orders/create.blade.php`
2. `OrderController@show` devuelve vista; JSON se mueve a `/orders/{order}/data`
3. `orders/show.blade.php` con tres estados separados (pedido / pago / entrega)
   y timeline alimentado por `@events`
4. `QuoteController@create` + `quotes/create.blade.php`
5. `quotes/show.blade.php` con botón de conversión (usa `@convert`)
6. Listado de pedidos a ancho completo + filtros nuevos sobre campos existentes
7. `#panel-right` → drawer (`position:fixed` + overlay)
8. Estado *Vencida* en cotizaciones: **derivado de `valid_until < hoy`**, no
   necesita columna nueva

### Con migración (fase 2, previa decisión fiscal)
- `quote_items.discount`, `quotes.subtotal`, `quotes.tax`, `quotes.discount`
- `order_items.discount`, `orders.subtotal`, `orders.tax`
- Sin esto, el descuento global y el IGV desglosado no se pueden persistir

### Aplazado explícitamente (fase 3)
- Archivos adjuntos (almacenamiento, validación, límite de peso)
- Envío por email (entregabilidad, plantillas)
- Sistema de plantillas de términos y condiciones reutilizables

---

## F. ORDEN DE IMPLEMENTACIÓN PROPUESTO

Criterio: **primero lo que no arriesga datos**, después lo fiscal.

| Paso | Trabajo | Riesgo | Por qué va aquí |
|---|---|---|---|
| **1** | Panel de alertas → drawer | Nulo | Recupera ancho para todo lo demás. Aislado del resto |
| **2** | Listado de pedidos a ancho completo + filtros | Bajo | Los campos ya existen; solo consulta y UI |
| **3** | Detalle de pedido (`show.blade.php`) | Bajo | Ojo: `show()` deja de devolver JSON. Hay que mover el endpoint y revisar quién lo consume |
| **4** | Nuevo pedido (`create.blade.php`) con panel sticky | Medio | Formulario nuevo; reusa `store()` existente |
| **5** | Listado + detalle de cotizaciones | Bajo | Espeja lo de pedidos; conversión ya existe |
| **6** | Nueva cotización | Medio | Mismo patrón que paso 4 |
| **7** | **Decisión fiscal + migración de descuentos e impuestos** | **Alto** | Toca dinero. Requiere tus 3 respuestas y pruebas de punta a punta |
| **8** | Fase 3: adjuntos, email, plantillas | — | Aplazado |

**Regla para cada paso:** desplegar y verificar antes de empezar el siguiente.
Nada de tocar los pasos 1-6 y el 7 en la misma tanda: si algo sale mal en lo
fiscal, hay que poder revertir sin perder el rediseño.

---

## G. LO QUE NECESITO DE TI PARA SEGUIR

**Bloqueante solo para el paso 7** (los pasos 1-6 pueden empezar ya):

1. ¿Los precios de Productos **incluyen IGV**?
2. ¿Las cotizaciones deben **mostrar IGV desglosado** al cliente?
3. ¿`has_tax` por producto sirve, o toda la operación es igual?

**Aviso operativo:** el paso 3 cambia `GET /orders/{order}` de JSON a vista.
Antes de ejecutarlo hay que rastrear quién consume ese JSON hoy (el propio
listado lo usa vía fetch) para no romper el panel en producción.

---

# FASE 7 — Modelo fiscal configurable (definido, NO implementado)

**Definido por el cliente el 2026-08-14.** Dos ejes independientes, herencia al
producto y desglose opcional. Nada de esto está en código todavía.

## 7.1 Los dos ejes (no confundirlos)

| Eje | Qué responde | Valores | Naturaleza |
|---|---|---|---|
| **Modalidad de precios** | ¿Cómo interpreto el número que escribe el vendedor? | `incluye_igv` (defecto) · `no_incluye_igv` | **Captura** |
| **Tratamiento tributario** | ¿Qué es esta operación para SUNAT? | `gravado` (defecto) · `exonerado` · `inafecto` | **Tributación** |

Son ortogonales: una operación puede ser **gravada** con precio ingresado
**antes** del impuesto. Y una **exonerada** nunca lleva IGV, se ingrese como se
ingrese.

## 7.2 Estado actual de cada eslabón

| Eslabón | Hoy | Con el modelo nuevo |
|---|---|---|
| **Config. proyecto** | No existe nada fiscal | 4 ajustes nuevos en `project_settings` |
| **Producto** | `has_tax` (bool) + `tax_rate` (18.00) | `has_tax` **se conserva con su significado real**; entra `tax_treatment` |
| **Tienda pública** | `hasTax` → leyenda "Incluye IGV" en la tarjeta | Igual, pero la leyenda la decide la modalidad del proyecto |
| **Cotización** | `igv = subtotal * 0`. Sin columnas fiscales | Desglose opcional; columnas nuevas |
| **Pedido** | Sin cálculo fiscal. `total` crudo | Hereda del proyecto; columnas nuevas |
| **Facturación** | `igv_included` por documento, defecto `true` | Se **inicializa** desde la modalidad del proyecto |
| **SUNAT** | `tipAfeIgv` **hardcodeado '10'**, `porcentajeIgv` **18.0** | Salen de la afectación y tasa resueltas |
| **PDF/comprobante** | Muestra lo que traiga el documento | Respeta el desglose configurado |

## 7.3 VEREDICTO SOBRE `has_tax`: se conserva, NO se reemplaza

Investigado en `catalog/products/index.blade.php:455-458`, el propio código lo
documenta:

> *"El precio que se ingresa es siempre el precio final al cliente (así lo exige
> la ley en Perú: el precio mostrado ya incluye IGV). **`has_tax` solo decide si
> la tienda le informa al cliente que ese precio incluye IGV**"*

**`has_tax` NO es tratamiento tributario: es un rótulo de escaparate.** Su único
consumo real es pintar `<span>Incluye IGV</span>` en la tarjeta de producto
(`computienda.blade.php:5037`) y calcular el desglose informativo del formulario.

Por eso:

- ❌ **No se reutiliza** como Gravado/Exonerado/Inafecto — significan cosas
  distintas y confundirlos rompería la tienda (1624 productos perderían o
  ganarían la leyenda sin criterio).
- ❌ **No se reemplaza** — hay 4 consumidores vivos: validación y acciones
  masivas `tax_on`/`tax_off` en `ProductController`, el modelo, `CatalogQueryService`
  y la vista de productos.
- ✅ **Se conserva con su nombre y función**, y se le suma un campo nuevo
  `tax_treatment` para lo tributario. Cada uno con su responsabilidad.

**Los 1624 productos no se migran**: `has_tax = 0` sigue significando "no mostrar
la leyenda", que es correcto hoy. El campo nuevo nace en `heredar` y nadie tiene
que tocar nada.

## 7.4 Ajustes nuevos (en `project_settings`, sin tabla nueva)

| Clave | Valores | Defecto | Alcance |
|---|---|---|---|
| `tax_price_mode` | `incluye_igv` · `no_incluye_igv` | `incluye_igv` | Cómo se interpreta el precio |
| `tax_treatment` | `gravado` · `exonerado` · `inafecto` | `gravado` | Afectación por defecto |
| `tax_rate` | decimal | `18.00` | Tasa cuando es gravado |
| `quote_show_tax_breakdown` | `1` · `0` | `0` | **Solo presentación** del documento |

El defecto `incluye_igv` + `gravado` **reproduce exactamente el comportamiento
actual**: ningún proyecto cambia de conducta al desplegar.

## 7.5 Herencia (el vendedor no elige nada en la venta)

```
Proyecto.tax_treatment  ──┐
                          ├──► Producto.tax_treatment = 'heredar' (defecto)
Producto (excepción) ─────┘         │
                                    ▼
                    Línea de pedido/cotización congela:
                    afectación + tasa + modalidad al momento de vender
```

**Regla de oro:** la línea guarda el valor **resuelto**, no la referencia. Si
mañana el proyecto pasa de gravado a exonerado, los documentos ya emitidos
conservan su tributación original. Un documento fiscal no puede cambiar
retroactivamente.

`Producto.tax_treatment` con 4 valores: `heredar` (defecto) · `gravado` ·
`exonerado` · `inafecto`.

## 7.6 Desglose en cotizaciones (presentación, nunca el total)

**Con desglose:** Valor de venta · Descuento · IGV · Total
**Sin desglose:** Total + leyenda "Precio incluye IGV" cuando corresponda

El total calculado es **idéntico** en ambos casos. Es el mismo número contado
de dos maneras.

## 7.7 Impacto en SUNAT (el eslabón más delicado)

`ApisPeruService.php:129-137` hoy:

```php
'tipAfeIgv'     => '10',   // ← HARDCODEADO: todo va como Gravado
'porcentajeIgv' => 18.0,   // ← HARDCODEADO
```

Con el modelo nuevo, `tipAfeIgv` sale de la afectación congelada en la línea
(catálogo 07 de SUNAT): **Gravado `10` · Exonerado `20` · Inafecto `30`**.

⚠️ **Trabajo adicional no evidente:** SUNAT exige los totales **separados por
tipo de operación** — `mtoOperGravadas`, `mtoOperExoneradas`, `mtoOperInafectas`.
Hoy el payload solo envía `$gravadas = $invoice->subtotal` (todo gravado). Emitir
una exonerada o inafecta **requiere tocar la construcción del payload**, no solo
el campo de la línea. Si no se hace, SUNAT rechaza el comprobante.

## 7.8 Migraciones necesarias (cuando se autorice)

```
products              + tax_treatment ENUM('heredar','gravado','exonerado','inafecto') DEFAULT 'heredar'
quotes                + subtotal, tax_amount, discount_amount, tax_treatment, price_mode
quote_items           + discount, tax_treatment, tax_rate, tax_amount
orders                + subtotal, tax_amount, tax_treatment, price_mode
order_items           + discount, tax_treatment, tax_rate, tax_amount
```

Todas con defecto que reproduce la conducta actual. **`has_tax` no se toca.**

## 7.9 Orden de ejecución de la Fase 7

| # | Trabajo | Riesgo |
|---|---|---|
| 7a | Ajustes en Configuración del proyecto (4 controles) | Nulo — nadie los lee aún |
| 7b | Migraciones con defectos neutros | Bajo — no cambia ningún total |
| 7c | Resolución de herencia (proyecto → producto → línea) | Medio |
| 7d | Cálculo real en cotización y pedido (adiós `* 0`) | **Alto — cambia totales** |
| 7e | Desglose en cotizaciones y PDF | Bajo |
| 7f | `tipAfeIgv` dinámico + totales separados en SUNAT | **Alto — rechazo de comprobantes** |

**7d y 7f exigen pruebas de punta a punta con documentos reales antes de
producción.** Un error aquí no es visual: es tributario.

## 7.10 Pregunta que queda abierta

En el paso 7d, cuando la modalidad es `no_incluye_igv`, **el precio mostrado en
la tienda pública debe seguir siendo el precio final al cliente** (la ley peruana
lo exige). Es decir: la tienda tendría que **sumar** el IGV al precio del producto
antes de mostrarlo. Hay que confirmar que ese es el comportamiento esperado, o
si la modalidad `no_incluye_igv` se limita a documentos B2B y la tienda siempre
muestra precio final.

## 7.10 RESUELTO — Precio en tienda pública (regla del paso 7d)

**La tienda pública SIEMPRE muestra el precio final al consumidor.** Confirmado
por el cliente el 2026-08-14.

| Modalidad | Tratamiento | Precio almacenado | Lo que ve el consumidor |
|---|---|---|---|
| `incluye_igv` | cualquiera | 118 | **S/ 118** (tal cual) |
| `no_incluye_igv` | gravado | 100 | **S/ 118** ← la tienda SUMA el impuesto |
| `no_incluye_igv` | exonerado / inafecto | 100 | **S/ 100** (no se suma nada) |

```
precio_final = precio_base + impuesto     (solo si gravado)
precio_final = precio_base                (exonerado / inafecto)
```

**Prohibido** mostrar S/ 100 en la ficha y que el IGV aparezca recién en el
carrito o el checkout. El precio principal del escaparate es el final.

En documentos B2B (cotización/factura) sí se presenta Base + IGV + Total por
separado, pero **el total final siempre explícito**.

**Impacto técnico:** afecta `CatalogQueryService::toCard()` y todo punto que
publique precio a la tienda (tarjeta, ficha, carrito, checkout, JSON del
catálogo). El cálculo debe hacerse **en el servidor al publicar el precio**, no
en el front, para que no haya dos números en circulación.

## 7.11 Etiquetas de la interfaz de Configuración

Lo que ve el usuario ≠ lo que se guarda:

| Interfaz (usuario) | Valor interno |
|---|---|
| **Precio final** — ya incluye impuestos aplicables | `incluye_igv` |
| **Precio base** — los impuestos se calculan aparte | `no_incluye_igv` |

Tratamiento tributario se muestra tal cual: **Gravado · Exonerado · Inafecto**.

Los dos ajustes se presentan como **independientes**, sin sugerir que uno
condiciona al otro.
