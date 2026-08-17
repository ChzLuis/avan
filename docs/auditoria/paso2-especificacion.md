# Paso 2 — Especificación de /bixosales/pedidos

**Fecha:** 2026-08-15 · **Estado:** especificación para aprobar. **CÓDIGO NO MODIFICADO.**
Auditoría previa: [paso2-pedidos-rediseno.md](paso2-pedidos-rediseno.md)

---

## 3. Estados reales (medidos en ARIN, no supuestos)

### 3.1 `status` — comercial

| Valor | Pedidos | Nota |
|---|---:|---|
| `pending` | 15 | |
| `done` | 3 | |
| **`pagado`** | **3** | 🔴 valor de PAGO en el campo comercial |

La vista y el controlador manejan además `process`/`processing`,
`completed` y `cancelled`, hoy sin filas.

### 3.2 `payment_status` — pago

| Valor | Pedidos | Nota |
|---|---:|---|
| `(null)` | 10 | se trata como `pending` |
| `pending` | 6 | |
| `partial` | 2 | |
| **`pagado`** | **3** | 🔴 el código solo reconoce `paid` |

`OrderController@update` valida `in:paid,partial,pending,rejected,refunded`
(inglés). Los tres `pagado` no salieron de ahí: son datos heredados.

### 3.3 `delivery_status` — entrega

**21 de 21 en null.** Nunca se ha usado.

### 3.4 `laundry_status` / `kitchen_status` — operativo por rubro

`laundry_status`: 20 null, 1 `entregado`. `kitchen_status`: 19 `pending`, 2 `done`
(valor por defecto, no uso real).

`OrderFlow` define flujos por rubro, con estados propios:

- **lavanderia**: recibido · cotizado · lavando · secando · planchando ·
  control_calidad · listo · en_reparto · entregado · anulado
- **restaurante**: recibido · en_cocina · emplatando · listo · en_reparto ·
  entregado · anulado

TECSIST no encaja en ninguno → **flujo normal**, sin columna operativa con datos.

### 3.5 `sales_channel`

`web` (9) · `cotizacion` (5) · `pos` (3) · `whatsapp` (3) · null (1)

### 🔴 Defecto cuantificado: el KPI "Por cobrar" está inflado

```
por cobrar que se muestra hoy      S/ 36 205,12
de eso, ya cobrado (payment_status='pagado')  S/  1 670,12
```

`OrderController:24` y `isPendingPay()` en la vista comparan contra
`['paid','refunded']`. Los tres pedidos `pagado` (ids 20, 21, 22) se cuentan
como deuda. Además su `status='pagado'` no corresponde a ningún estado
comercial, así que tampoco encajan en ninguna píldora.

**No es lógica fiscal** (no toca IGV, cálculo ni SUNAT): es un mapeo de estado.
Propuesta: normalizar en lectura mediante un mapa de sinónimos
`pagado→paid`, sin migrar datos todavía. La migración de esas 3 filas se
propone aparte, con tu autorización.

---

## 4. Columnas definitivas

Densidad **44 px** por fila.

| # | Columna | Contenido | Oculta a |
|---|---|---|---|
| 1 | **Pedido** | `#id` o `tag_code`; debajo: nº de items y canal (icono) | nunca |
| 2 | **Cliente** | nombre; debajo teléfono | nunca |
| 3 | **Fecha** | relativa + absoluta en tooltip | <768 (tarjeta) |
| 4 | **Total** | `S/`, alineado derecha | nunca |
| 5 | **Comercial** | píldora de `status` | nunca |
| 6 | **Pago** | píldora de `payment_status` + método | nunca |
| 7 | **Preparación/Entrega** | flujo del rubro o `delivery_status` | <1024 |
| 8 | **Responsable** | `delivery_person_name`, si no `created_by` | <1280 |
| 9 | **Acciones** | menú `⋯` | nunca |

Items y Canal **no** son columnas: viajan bajo Pedido como metadato secundario.
El canal se oculta el primero al reducir ancho.

**Responsable**: los datos lo soportan solo parcialmente —
`delivery_person_name` existe y `created_by` está en 3 de 21 pedidos. La columna
se pinta con lo que haya y un guion cuando no. No se inventa dato.

---

## 5. Filtros definitivos

Independientes, combinables por AND, con contador de activos y "Limpiar".

| Filtro | Valores (solo los reales) |
|---|---|
| Estado comercial | Pendiente · En proceso · Completado · Cancelado |
| Pago | Pendiente · Parcial · Pagado · Reembolsado |
| Preparación/Entrega | los de `OrderFlow` del rubro; si no hay flujo, el filtro no se pinta |
| Canal | web · cotizacion · pos · whatsapp |
| Fecha | Hoy · 7 días · 30 días · rango |
| Responsable | valores presentes en los datos |

### Vistas rápidas

Escriben sobre **el mismo estado de filtros**, no un sistema paralelo:

| Vista | Filtros que aplica |
|---|---|
| Todos | ninguno |
| Por cobrar | pago ∈ {pendiente, parcial} · comercial ≠ cancelado |
| Atrasados | activos con `updated_at` +48 h |
| Hoy | fecha = hoy |
| En preparación | comercial = en proceso |
| Listos | comercial = listo/completado |

### KPIs — definición única

Se calculan **solo en el servidor** y se pasan a la vista; Alpine deja de
recalcular. Cada uno es clicable y aplica **la vista rápida equivalente**:

| KPI | Vista rápida |
|---|---|
| Por cobrar (S/) | Por cobrar |
| Activos | Todos menos completados/cancelados |
| En preparación | En preparación |
| Atrasados | Atrasados |

---

## 6. Wireframe desktop (≥1280)

```
┌────────────────────────────────────────────────────────────────────────────┐
│ Pedidos                                        [⬇ Exportar]  [+ Nuevo]     │
│ Todos tus pedidos y su estado de pago                                      │
├────────────────────────────────────────────────────────────────────────────┤
│ ┌──────────┐ ┌──────────┐ ┌──────────────┐ ┌──────────┐                    │
│ │POR COBRAR│ │ ACTIVOS  │ │EN PREPARACIÓN│ │ATRASADOS │   ← clicables      │
│ │S/34535,00│ │    8     │ │      0       │ │    6     │                    │
│ └──────────┘ └──────────┘ └──────────────┘ └──────────┘                    │
├────────────────────────────────────────────────────────────────────────────┤
│ Todos │ Por cobrar │ Atrasados │ Hoy │ En preparación │ Listos             │
│ ┌────────────────────┐ [Comercial▾][Pago▾][Entrega▾][Canal▾][Fecha▾] (2) ✕ │
│ │🔍 Buscar…          │                                                     │
├────────────────────────────────────────────────────────────────────────────┤
│ PEDIDO      CLIENTE        FECHA    TOTAL  COMERCIAL  PAGO    ENTREGA  RESP│
│ ─────────────────────────────────────────────────────────────────────── ⋯ │
│ #34         POOL ESPINOZA  hace 1d  1266  ●Pendiente ●Debe   —      —   ⋯ │
│ 3 items 🌐  961035483                                                      │
│ ─────────────────────────────────────────────────────────────────────── ⋯ │
│ #33         pool espinoza  hace 1d   478  ●Pendiente ●Debe   —      —   ⋯ │
│ 2 items 📄  961035483                                                      │
├────────────────────────────────────────────────────────────────────────────┤
│ Mostrando 10 de 21                                                         │
└────────────────────────────────────────────────────────────────────────────┘
```

Con el drawer abierto la tabla **no se reflowa**: el drawer se superpone
(560–680 px) con velo.

## 7. Wireframe móvil (<768)

```
┌──────────────────────────┐
│ Pedidos        [+ Nuevo] │
├──────────────────────────┤
│ ┌────────┐ ┌────────┐    │
│ │POR COBR│ │ACTIVOS │    │ 2×2
│ │S/34535 │ │   8    │    │
│ └────────┘ └────────┘    │
│ ┌────────┐ ┌────────┐    │
│ │EN PREP │ │ATRASAD │    │
│ └────────┘ └────────┘    │
├──────────────────────────┤
│ 🔍 Buscar…    [Filtros 2]│ ← hoja inferior
│ Todos│Por cobrar│Atrasad…│ ← chips deslizables
├──────────────────────────┤
│ ┌──────────────────────┐ │
│ │ #34    POOL ESPINOZA │ │  TARJETA (no tabla)
│ │ hace 1d  ·  3 items  │ │
│ │            S/ 1266,00│ │
│ │ ●Pendiente  ●Debe    │ │
│ │                    ⋯ │ │
│ └──────────────────────┘ │
└──────────────────────────┘
```

Drawer a ancho útil completo: `calc(100vw - var(--sidebar-w))`, la lección del Paso 1.

### Cortes responsive

| Ancho | Cambio |
|---|---|
| ≥1280 | todo |
| 1024-1279 | oculta Responsable |
| 768-1023 | oculta además Entrega y Fecha |
| <768 | tarjetas |

---

## 8. Drawer enlazable — comportamiento exacto

| Acción | URL | Historial | Listado |
|---|---|---|---|
| Cargar `/bixosales/pedidos` | igual | — | render normal |
| Clic en fila | `/bixosales/pedidos/{id}` | `pushState` | intacto detrás |
| Cerrar (✕, Escape, velo) | `/bixosales/pedidos` | `back()` si vino de la lista, si no `replaceState` | intacto |
| Atrás del navegador | según pila | `popstate` cierra o cambia de pedido | intacto |
| Entrar directo a `/{id}` | igual | — | carga lista **y** abre ese pedido |
| Adelante tras cerrar | reabre | `popstate` | intacto |

**Filtros, búsqueda y scroll se conservan siempre**: viven en el estado de
Alpine, no en el DOM que se reemplaza. Al abrir por URL directa, el listado se
renderiza primero y el drawer se abre en `$nextTick`.

**Accesibilidad**, reutilizando el patrón del Paso 1: `inert` cuando cierra,
foco al primer control al abrir, foco de vuelta a la fila que lo abrió al
cerrar, `role="dialog"` + `aria-modal` + `aria-labelledby`, Escape, velo con
`z-index` bajo el del drawer.

Ancho: `clamp(560px, 42vw, 680px)` en escritorio.

**Ruta nueva necesaria**: `GET /bixosales/pedidos/{order}` ya existe
(`pedidos.show`, `project.can:orders.ver|view-orders`). Se reutiliza: si la
petición no es AJAX, devuelve el listado con el pedido preseleccionado.

---

## 9. Los cuatro endpoints — verificados uno a uno

| # | Llamada actual | Portal correcto | Controlador | Autorización portal |
|---|---|---|---|---|
| 1 | `url('/orders')/{id}/pay` | **no existe** | `OrderController@pay` | — |
| 2 | `url('/orders')/{id}/events` | **no existe** | `OrderController@events` | — |
| 3 | `url('/orders')/{id}/wa-sent` | **no existe** | `OrderController@waSent` | — |
| 4 | `/orders/{id}/laundry-status` | **no existe** | `WaBotController@changeLaundryStatus` | — |

**Ninguno de los cuatro tiene equivalente en el portal.** No es un fallo de
sustitución: faltan las rutas.

`changeLaundryStatus` **ya está preparado** para el portal:

```php
$projectId = request()->routeIs('bixosales.*')
    ? session('comercial_project_id')
    : app('active_project')?->id;
```

Es decir, el handler contempla una ruta `bixosales.*` que nunca se creó.

### Plan

Añadir **4 rutas al portal**, reutilizando los handlers existentes sin duplicar
una línea de lógica, con los permisos ya acordados en el Paso 0:

| Ruta nueva | Handler | Permiso |
|---|---|---|
| `POST /bixosales/pedidos/{order}/pay` | `OrderController@pay` | `project.can:orders.editar\|manage-orders` |
| `GET /bixosales/pedidos/{order}/events` | `OrderController@events` | `project.can:orders.ver\|view-orders` |
| `POST /bixosales/pedidos/{order}/wa-sent` | `OrderController@waSent` | `project.can:orders.editar\|manage-orders` |
| `POST /bixosales/pedidos/{order}/laundry-status` | `WaBotController@changeLaundryStatus` | `project.can:orders.editar\|manage-logistics` |

Luego la vista usa `$ordersApiBase` en los cuatro.

⚠️ **Esto altera el recuento del Paso 0**: el portal pasaría de 97 a **101
rutas** y de 56 a **59 con verbo mutador** (3 nuevas mutadoras + 1 GET). Las
tres nuevas mutadoras nacen protegidas, así que la propiedad "0 mutadoras solo
con comercial.auth" se mantiene. Actualizaré la matriz y la prueba estática
(`assertSame(56, …)` → 59). **Necesito tu visto bueno explícito** porque toca
rutas, aunque sea para cerrar bugs de esta vista.

`pay` y `events` requieren además que `OrderController` resuelva el proyecto
como hace `index`/`show` (`session('comercial_project_id')` en rutas
`bixosales.*`); hoy usan solo `app('active_project')`. Es el mismo patrón ya
presente en el archivo, no lógica nueva.

---

## 10. Botón "Nuevo pedido"

El Paso 4 aún no existe, así que **no** se publica un CTA hacia una ruta
inexistente. Comportamiento temporal:

- Visible solo con `orders.crear|manage-orders`
- Apunta a `/bixosales/pos` (**Venta Express**), que ya existe, está protegida
  con `pos.usar` y es hoy la vía real de alta rápida
- Si el usuario no tiene `pos.usar`, el botón se muestra deshabilitado con
  tooltip "Disponible próximamente"

Al llegar el Paso 4 se cambia el destino y nada más.

---

## 11. Reparto de las 984 líneas

| Bloque | Líneas aprox. | Destino |
|---|---:|---|
| `@php` cabecera + `$ordersApiBase` | 1-8 | **se reutiliza**, corrigiendo los 4 endpoints |
| `<style>` de lista maestro-detalle | 9-40 | **se elimina** (`.ord-list-item`, columna fija) |
| Estado Alpine + serialización de pedidos | 41-92 | **se extrae** a `resources/js` o `@push('scripts')` |
| `tabCounts` duplicando KPIs | 93-99 | **se elimina** (KPI único de servidor) |
| `pagar()`, `loadEvents()`, `waSent()` | 100-135 | **se reutiliza** con base corregida |
| Filtros y `filtered` | 136-210 | **se reescribe**: de 1 filtro a 6 combinables |
| CRUD (`guardar`, `eliminar`, `waAction`) | 240-340 | **se reutiliza** tal cual |
| Pestañas + chips | 385-435 | **se reescribe** como vistas rápidas + barra |
| Lista de tarjetas | 436-478 | **se reescribe** como tabla; la tarjeta sobrevive en móvil |
| Detalle | 480-545 | **se extrae** a `<x-pedidos.drawer>` |
| Formulario de alta (`creating`) | 486-540 | **se elimina** de esta vista (Paso 4) |
| Modal de pago | 546-620 | **se reutiliza** dentro del drawer |
| Estado, productos, pagos, WhatsApp | 620-984 | **se extrae** a parciales del drawer |

**Resultado estimado:** `index.blade.php` baja a ~250 líneas (KPIs, barra,
tabla, tarjetas) y aparecen `pedidos/_drawer.blade.php`,
`pedidos/_fila.blade.php`, `pedidos/_filtros.blade.php` y un módulo JS con el
estado. Nada de lógica de negocio se reescribe.

---

## 12. Permisos en la UI — sin duplicar la matriz

Nada de `@can` sueltos y dispares. Un único helper que refleje el `canAny` del
backend:

```php
// app/Support/OrderAbilities.php  (lectura, sin efectos)
public static function for(?User $u): array {
    return [
        'ver'       => self::any($u, ['orders.ver',      'view-orders']),
        'crear'     => self::any($u, ['orders.crear',    'manage-orders']),
        'editar'    => self::any($u, ['orders.editar',   'manage-orders']),
        'eliminar'  => self::any($u, ['orders.eliminar', 'manage-orders']),
        'logistica' => self::any($u, ['orders.editar',   'manage-logistics']),
    ];
}
```

Se pasa una vez a la vista como `$puede` y de ahí a Alpine, de modo que
servidor y cliente comparten **una sola** definición, alineada con el
`project.can:a|b` de las rutas. Si mañana cambia la equivalencia A/B, se cambia
en un sitio.

---

## 13. Validación

Misma disciplina del Paso 1: suite Playwright propio
(`pedidos.spec.js`), capturas reales en 1440/1024/768/390 con drawer abierto y
cerrado, y revisión visual de las imágenes. Los tests no sustituyen a las
imágenes: en el Paso 1, 26 tests en verde convivían con un cajón recortado por
el sidebar que solo se vio mirando el PNG.

---

## 14. Plan de extracción con líneas exactas

Medido sobre `resources/views/orders/index.blade.php` (984 líneas, intacto).
La estrategia es **extraer y luego reescribir**, no reescribir todo: el bloque
de detalle se traslada verbatim para no tocar lógica de negocio ya probada.

| Líneas | Bloque | Destino |
|---:|---|---|
| 1-8 | `@php` + `$ordersApiBase` | **index** — corregir los 4 endpoints a `$ordersApiBase` |
| 9-39 | `<style>` maestro-detalle | **eliminar** (`.ord-list-item`, columna fija) |
| 41-92 | `x-data` + serialización | **módulo JS** — añadir `$puede`, `flujoOperativo`, `pedidoInicial` |
| 93-99 | `tabCounts` | **eliminar** (KPI único de servidor) |
| 100-135 | `pagar()`, `loadEvents()`, `waSent()` | **módulo JS** con base corregida |
| 136-210 | `filterStatus` + `filtered` | **reescribir**: 6 filtros combinables AND |
| 211-345 | helpers, CRUD, `waAction` | **módulo JS** tal cual |
| 346-368 | Header | **reescribir** — "Nuevo" → Venta Express |
| 369-388 | KPIs | **reescribir** compactos y clicables |
| 389-400 | Pestañas | **reescribir** como vistas rápidas |
| 401-403 | Wrapper BODY | **reescribir** a ancho completo |
| 404-435 | Búsqueda + chips | **`_filtros.blade.php`** |
| 436-474 | Tarjetas de lista | **`_fila.blade.php`** (tabla desktop + tarjeta móvil) |
| 475-484 | Wrapper detalle + vacío | **`_drawer.blade.php`** |
| **485-544** | **Formulario de alta (`creating`)** | **🗑 ELIMINAR** — es el Paso 4 |
| 545-567 | Modal de pago | **`_drawer.blade.php`** verbatim |
| 568-984 | Detalle del pedido | **`_drawer.blade.php`** verbatim |

**Orden sugerido de trabajo**

1. `_drawer.blade.php` ← mover 475-484 + 545-984 sin editar contenido, solo el
   contenedor (posición fija, `inert`, foco, Escape, ARIA, velo).
2. Borrar 485-544 del bloque movido (formulario de alta).
3. Módulo JS con el estado, ya con los 6 filtros y el historial de URL.
4. `_filtros.blade.php` y `_fila.blade.php`.
5. `index.blade.php` nuevo: header, KPIs, vistas rápidas, tabla, tarjetas.
6. Corregir los 4 endpoints a `$ordersApiBase`.

**Comprobación de no regresión**: las tres llamadas AJAX existentes
(líneas 242, 289, 301) envían `Accept: application/json`; deben seguir
haciéndolo para que `show()` no les devuelva el listado.
