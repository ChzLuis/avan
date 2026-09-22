# Pendientes en curso

Cada cosa pedida se anota aquí, se hace y se valida. Nada se da por terminado
sin una prueba que lo demuestre. Se cierra una tarea solo cuando la columna
**Validado** dice cómo se comprobó.

Convención: `[ ]` pendiente · `[~]` en curso · `[x]` hecho y validado.

---

## Tanda 2026-09-22 (c) · La vista previa de la guía no se veía

| # | Qué pidió | Estado | Validado |
|---|---|---|---|
| 8 | "Aún no veo la vista previa de la guía" | `[x]` | Renderizado por las dos caras en ARIN: cada una usa su ruta; 8 tests en `UnidadYPreviaGuiaTest` |

**Tres causas, no una:**

1. **La URL estaba rota justo en Ventas.** Dentro del `<script>` repetí la
   condición con `$portalLayout` en vez de usar `$rutaGuias`, la variable que
   ya resuelve la cara (y que usan Guardar, Enviar e Imprimir). Salía siempre
   `/guias/previsualizar`, así que pulsando el botón desde Ventas se llamaba a
   la ruta del panel. Medido después del arreglo:
   - panel → `/guias/previsualizar`
   - Ventas → `/bixosales/guias/previsualizar`
2. **El botón no se distinguía.** Estaba al pie del formulario (59 % del
   documento) y con el mismo peso visual que "Limpiar". Ahora lleva icono de
   ojo, fondo y borde más marcados.
3. **La guía pisaba la unidad elegida**, el mismo bug que se arregló en
   facturas: `item.unit = p.unit || 'NIU'` sin comprobar si el operador ya
   había elegido. En una guía el error viaja en el camión.

**Nota:** la franja "Última guía" que se quitó el 2026-09-21 volvió a aparecer
en esta vista. No la pisó otra sesión: el cambio se hizo sobre la ruta
anterior a la modularización y se perdió en la mudanza a `app/Modules/`.
Queda pendiente volver a quitarla.

---

## Tanda 2026-09-22 (b) · Menú de Ventas y cerrar sesión

| # | Qué pidió | Estado | Validado |
|---|---|---|---|
| 6 | En el menú de Ventas no aparecen Facturas ni Boletas | `[x]` | No es un bug: es la regla de `ModulosPortal`. El usuario decide dejarlo como está |
| 7 | No puede cerrar sesión desde el portal Comercial | `[x]` | Test `CerrarSesionComercialTest` (5): fallaba antes del arreglo y pasa después; desplegado |

### 6. Facturas y Boletas no salen en el menú

**No es un fallo.** Las entradas existen (`_sidebar.blade.php`, grupo
"Facturación": Facturas, Boletas, Notas, Guías, Comprobantes emitidos), pero
`App\Support\ModulosPortal` solo las ofrece si el negocio **ya emitió al
menos una factura** (`Invoice::exists()`) o si se enciende el ajuste
`modulo_facturas`. Es la regla de "no ofrecer lo que aún no se usa".

Medido en el proyecto 16 (Eskala): **0 facturas emitidas**, ajuste sin
activar, módulo `invoices` **sí contratado**. Aparecerá en cuanto se emita la
primera factura. El usuario decide dejar la regla como está.

### 7. Cerrar sesión no cerraba nada

`Comercial\AuthController::logout()` solo hacía
`session()->forget('comercial_project_id')`: olvidaba qué negocio estaba
abierto pero **dejaba al usuario autenticado** — sin `Auth::logout()`, sin
invalidar la sesión ni regenerar el token. Volvía a entrar solo y el botón
parecía no hacer nada.

Es además un asunto de seguridad: en una computadora compartida, el siguiente
que se sienta entraba con la cuenta del anterior.

**El CRM (`CrmAuthController`) tenía el mismo agujero** y se arregló a la vez,
por decisión del usuario. Ambos usan ahora el cierre del panel de
administración: `Auth::logout()` + `invalidate()` + `regenerateToken()`.

Trampa: el aviso de cierre por inactividad se lee **antes** de invalidar,
porque invalidar borra la sesión donde se guardaría el flash.

---

## Tanda 2026-09-22 · Precios al filtrar en MegaHogar

| # | Qué pidió | Estado | Validado |
|---|---|---|---|
| 5 | Al filtrar en la tienda a veces salen precios y a veces S/ 0.00 | `[x]` | 4 tiendas medidas en ARIN: 0 apariciones de "S/ 0.00"; test `PrecioCeroAlFiltrarTest` (4) + 11 de precios ya existentes |

**Síntoma:** filtrando por Sala todas las tarjetas mostraban S/ 0.00.

**Causa:** `$hidePrices` en `computienda.blade.php` exigía que el ajuste
`quote_price_display` valiera `'hide'`, y caía al defecto `'show'` cuando la
clave **no existía** — justo el caso de MegaHogar y ELECTRO JARA. Pero el
motor sí omite el precio en modo cotización, así que `number_format(null)`
pintaba `"0.00"` en todo el catálogo.

**Arreglo:** el defecto de `quote_price_display` pasa a ser **ocultar**.
Publicar precios en una tienda a cotización es una decisión que se toma a
propósito; mostrar un precio que no se tiene es peor que no mostrarlo.

**Medido en producción tras el arreglo:**

| Tienda | Modo | Precios ausentes | "S/ 0.00" |
|---|---|---|---|
| MegaHogar | quote | 12/12 | 0 |
| ELECTRO JARA | quote | 12/12 | 0 (tenía el mismo bug) |
| Sabor Criollo | direct | 0/12 | 0 (sus precios siguen saliendo) |
| MURUHUAY | quote (`hide`) | 12/12 | 0 |

**Nota aparte:** MegaHogar está **suspendida** (`suspended_at` 2026-09-22
12:48) por decisión del usuario, así que su tienda responde 404. El
diagnóstico se hizo llamando al controlador directo, sin tocar la suspensión.

---

## Tanda 2026-09-21 · Unidades y guías

| # | Qué pidió | Estado | Validado |
|---|---|---|---|
| 1 | La unidad elegida (Caja) se perdía a partir del 2.º producto y salía "Unidad" | `[x]` | Test `el catalogo no pisa la unidad elegida`; desplegado y verificado en ARIN |
| 2 | En la guía debe salir "Caja", no el código `BX` | `[x]` | `etiquetaUnidad('BX') === 'Caja'`; las 3 vistas impresas traducen (test + grep en ARIN) |
| 3 | La guía debe tener vista previa, como las facturas | `[x]` | Tests `la guia tiene vista previa` y `no graba la guia`; 2 rutas vivas en ARIN |
| 4 | Corregir visualmente las 2 facturas ya emitidas para el cliente | `[x]` | F001-24 correcta (base y XML dicen `PK`); F001-25 la resuelve el usuario con NC motivo 03 |

### 1. La unidad elegida se perdía

**Síntoma reportado:** "estoy creando como caja y luego guarda como unidad a
partir del segundo producto". Caso real: F001-00000025 (GABDE, 19/09/2026),
línea 1 con `BX` y línea 2 con `NIU`, habiendo elegido Caja en ambas.

**Causa:** al traer el producto del catálogo, `selectCatalogProduct()` y
`applyPickerProducts()` hacían `item.unit = product.unit || 'NIU'`, pisando la
unidad que el cajero ya había elegido con la de la ficha del producto (que
está en unidades). La primera línea se salvaba solo porque se completaba antes
de tocar el catálogo.

**Arreglo:** el catálogo ahora PROPONE la unidad y solo cuando el cajero no ha
tocado ese campo (`unitTocada`). Además, una línea nueva hereda la unidad de
la última que el cajero eligió: quien factura cajas, factura varias seguidas.

**Archivos:** `app/Modules/Finanzas/Views/invoices/index.blade.php`,
`app/Modules/Finanzas/Views/invoices/_formulario.blade.php`.

### 2. "Caja" en vez de `BX` en la guía

**Síntoma:** la columna "Unidad de medida" de la guía imprime el código SUNAT
crudo (`BX`). La factura sí lo traduce, con
`Catalogos::etiquetaUnidad($item->unit)`; la guía no.

### 3. Vista previa de la guía

Las facturas muestran la representación impresa antes de emitir; la guía se
emite a ciegas. La guía es el documento que viaja con la mercadería: si sale
mal, el problema está en la carretera.

### 4. Las 2 facturas ya emitidas

Se comparó lo que dice la base con el XML firmado que ya tiene SUNAT
(`storage/app/private/comprobantes/10/`):

| Comprobante | Base de datos | XML declarado | Veredicto |
|---|---|---|---|
| F001-00000024 | `PK` Paquete | `PK` | Coinciden: nada que corregir |
| F001-00000025 línea 1 | `BX` Caja | `BX` | Coinciden |
| F001-00000025 línea 2 | `BX` Caja | `NIU` Unidad | **Discrepan** |

La línea 2 se había corregido en la base DESPUÉS de declarar, así que el PDF
que se reimprima hoy no coincide con el XML que tiene SUNAT. Es la peor de
las situaciones: en una fiscalización se cruzan justamente esos dos.

**No se edita el comprobante declarado.** El usuario decide emitir una **nota
de crédito** sobre la F001-00000025 y reemitir con las dos líneas en Caja. El
motivo que corresponde es el **03 — Corrección por error en la descripción**
(no el 01, que anula la operación entera). El módulo ya lo soporta:
`invoices.nota.opciones` / `invoices.nota`, permiso `invoices.anular`.

Entretanto la base se deja como está (Caja): la factura va a quedar sin
efecto de todos modos y no se toca dos veces un comprobante declarado.
