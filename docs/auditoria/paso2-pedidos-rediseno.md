# Paso 2 — Rediseño de /bixosales/pedidos

**Fecha:** 2026-08-15
**Estado:** auditoría + propuesta. **CÓDIGO NO MODIFICADO.**
**Baseline visual del Paso 1:** `docs/auditoria/capturas/paso1-drawer-baseline/` (8 PNG + MANIFEST.txt)

Alcance: solo la vista de Pedidos y los datos/acciones que ya recibe. No se
abren auditorías de seguridad, fiscalidad ni arquitectura.

---

## 1. Qué hay hoy

`resources/views/orders/index.blade.php` — **984 líneas**, una sola vista
compartida por el panel (`/orders`) y el portal (`/bixosales/pedidos`),
conmutada por `$portalLayout`.

### 1.1 Estructura actual: maestro-detalle, no ancho completo

```
┌──────────────────┬────────────────────────────────┐
│ Lista (≈375 px)  │ Detalle del pedido             │
│ · buscador       │ · cliente, importe, acciones   │
│ · chips estado   │ · estado, productos, pagos     │
│ · tarjetas       │ · o FORMULARIO de alta         │
└──────────────────┴────────────────────────────────┘
```

Tres problemas de fondo:

1. **No es ancho completo.** La lista fija ~375 px y el detalle ocupa el resto:
   nunca se ven muchos pedidos a la vez ni se comparan columnas.
2. **El formulario de alta vive incrustado** en el mismo componente
   (estado `creating`), mezclando consulta y captura.
3. **El estado es uno solo.** `filterStatus` filtra por `status` *o* por
   `laundry_status`, nunca por pago o logística por separado.

### 1.2 Datos que ya recibe del controlador

`OrderController@index` entrega:

| Variable | Contenido |
|---|---|
| `$orders` | últimos **500**, con `client` e `items` |
| `$kpis` | `por_cobrar`, `activos`, `listos`, `atrasados` |
| `$paymentMethods` | de `catalogLists` tipo `payment_method` |
| `$paymentConditions` | ídem `payment_condition` |
| `$salesChannels` | ídem `sales_channel` |
| `$project`, `$portalLayout` | contexto y layout |

Por pedido, el JSON de Alpine ya lleva: `id`, `client_name`, `client_phone`,
`status`, `payment_status`, `payment_method`, `payment_condition`,
`sales_channel`, `total`, `discount`, `items[]`, `notes`, `created_at`,
`laundry_status`, `tag_code`, `pieces_count`, `sla_level`, `delivery_*`,
`document_*`.

**Casi todo lo que necesita el rediseño ya viaja.** Solo `$kpis` se calcula en
servidor y se duplica en cliente (`tabCounts`).

### 1.3 Funcionalidades existentes (a conservar TODAS)

| Grupo | Acciones |
|---|---|
| Consulta | buscador libre, 5 pestañas, chips de estado, selección |
| Estado comercial | Pedido recibido · Preparando · Listo · Enviado · Entregado |
| Pago | Registrar pago, Pago total, Adelanto, método y condición |
| Documentos | Nota PDF, Imagen, Ticket 58 mm |
| WhatsApp | menú de plantillas + registro del envío |
| Logística | estado de entrega, repartidor |
| Lavandería | flujo alterno por `OrderFlow`, etiqueta, nº de prendas, SLA |
| Edición | guardar cambios, eliminar, cancelar |

### 1.4 Dos flujos, no uno

`OrderFlow::supportsFlow($project->category)` conmuta entre el flujo normal y
el de **lavandería** (estados propios, SLA, `tag_code`, `pieces_count`).
El rediseño debe mantener ambos: hoy TECSIST usa el normal, pero el
componente sirve a otros rubros.

---

## 2. Hallazgos

### 2.1 Cuatro endpoints ignoran `$ordersApiBase` 🔴

La vista calcula la base correcta según el portal:

```php
$ordersApiBase = $portalLayout === 'comercial'
    ? route('bixosales.pedidos') : route('orders');
```

La usan `update`, `destroy` y `wa-action`. Pero **cuatro llamadas apuntan al
panel a pelo**, incluso cuando la vista se renderiza dentro del portal:

| Línea | Llamada | Destino real |
|---|---|---|
| 101 | registrar pago | `{{ url('/orders') }}/{id}/pay` |
| 112 | historial de eventos | `{{ url('/orders') }}/{id}/events` |
| 132 | registrar envío WhatsApp | `{{ url('/orders') }}/{id}/wa-sent` |
| 178 | estado de lavandería | `/orders/{id}/laundry-status` |

Hoy funcionan por casualidad: el usuario del portal suele ser también miembro
del proyecto, y `SetActiveProject` deja `active_project_id` en sesión, así que
las rutas del panel resuelven. Pero:

- Aplican los permisos **del panel**, no los del portal.
- `laundry-status` **no existe** como ruta del portal: siempre sale al panel.
- Si algún día el portal se sirve en otro dominio o guard, se rompen las cuatro.

Corregirlo es sustituir 4 literales por `$ordersApiBase`. Entra en el Paso 2
porque es la misma vista que se reescribe.

### 2.2 KPIs duplicados

`$kpis` llega calculado del servidor (`por_cobrar`, `activos`, `listos`,
`atrasados`) y Alpine recalcula otros cuatro distintos en cliente
(`nuevos`, `proceso`, `pagos`, `completados`). Se pintan en sitios distintos y
**no coinciden entre sí**. Hay que unificar a un solo juego.

### 2.3 Ninguna acción está condicionada por permisos

Cero `@can` en la vista. Un `contador` o un `solo_lectura` ve Guardar, Eliminar
y Registrar pago; al pulsarlos recibe un 403 crudo. Ya está documentado en
`rbac-produccion-arin.md` §4 y **se resuelve en este paso** (solo la UI; la
autorización del servidor no se toca).

### 2.4 "BIXO Score" visible al cliente 🔴

| Ubicación | Qué muestra |
|---|---|
| `comercial/layouts/app.blade.php:787-793` | widget "87/100 — BIXO Score" en la barra superior |
| `comercial/layouts/app.blade.php:122` | `<title>{{ $project->name ?? 'BIXO' }} — Operations"` |
| `comercial/dashboard.blade.php` | referencias al score |
| `comercial/mesas.blade.php` | ídem |

BIXO es nombre interno y no debe aparecer ante un cliente; TECSIST lo está
viendo. Propuesta: renombrar la etiqueta visible a **"Índice operativo"**
(mismo cálculo, mismo widget) y el `<title>` a `{{ $project->name }} — Operaciones`.
Es cambio de textos, sin lógica. Las otras dos vistas quedan anotadas para
cuando les toque su paso.

---

## 3. Propuesta de vista nueva

### 3.1 Estructura

```
┌───────────────────────────────────────────────────────────────┐
│ Pedidos                              [Exportar] [+ Nuevo]     │ cabecera
├───────────────────────────────────────────────────────────────┤
│ ⬤ Por cobrar  ⬤ Activos  ⬤ En preparación  ⬤ Atrasados        │ KPIs
├───────────────────────────────────────────────────────────────┤
│ [🔍 buscar]  [Estado ▾][Pago ▾][Logística ▾][Canal ▾][Fecha ▾]│ barra
│ Vistas: Todos · Por cobrar · Atrasados · Hoy · Listos         │ vistas
├───────────────────────────────────────────────────────────────┤
│ TABLA A ANCHO COMPLETO                                        │
│ #  Cliente  Fecha  Items  Total  Comercial  Pago  Logíst.  ⋯  │
├───────────────────────────────────────────────────────────────┤
│ 10 de 500 · [paginación]                                      │
└───────────────────────────────────────────────────────────────┘
```

El detalle deja de ser una columna fija y pasa a **panel deslizante** que se
abre al pulsar una fila, con el mismo patrón del cajón del Paso 1 (superpuesto,
`inert`, foco gestionado, Escape). Así la tabla conserva el ancho completo y el
detalle no compite por espacio.

**El formulario de alta desaparece de esta vista.** El botón "+ Nuevo pedido"
queda visible pero apunta al flujo del Paso 4.

### 3.2 Columnas

| Columna | Origen | Notas |
|---|---|---|
| # | `id` / `tag_code` | en lavandería manda `tag_code` |
| Cliente | `client_name` + `client_phone` | teléfono en segunda línea |
| Fecha | `created_at` | relativa (`hace 2 d`), absoluta en tooltip |
| Items | `items.length` / `pieces_count` | prendas si es lavandería |
| Total | `total` | alineado a la derecha |
| **Comercial** | `status` | píldora: Recibido/Preparando/Listo/Enviado/Entregado |
| **Pago** | `payment_status` + `payment_method` | Pagado/Debe/Adelanto |
| **Logística** | `delivery_status` o `laundry_status` | + SLA cuando aplica |
| Canal | `sales_channel` | etiqueta |
| Acciones | — | menú `⋯` |

Ocultables en pantallas medias por este orden: Canal → Items → Fecha.

### 3.3 Filtros — los tres estados separados

| Filtro | Valores | Fuente |
|---|---|---|
| Estado comercial | Recibido, Preparando, Listo, Enviado, Entregado, Cancelado | `status` |
| Estado de pago | Pagado, Debe, Adelanto, Reembolsado | `payment_status` |
| Estado logístico | según flujo: entrega o lavandería | `delivery_status` / `laundry_status` |
| Canal | valores reales del proyecto | `$salesChannels` |
| Método de pago | valores reales | `$paymentMethods` |
| Fecha | Hoy, 7 días, 30 días, rango | `created_at` |

Combinables (AND) y con contador de filtros activos + "Limpiar".

### 3.4 Vistas rápidas

Sustituyen a las 5 pestañas actuales, como combinaciones de filtros:

| Vista | Equivale a |
|---|---|
| Todos | sin filtro |
| Por cobrar | pago ≠ pagado, estado ≠ cancelado |
| Atrasados | activos sin actualizar +48 h |
| Hoy | creados hoy |
| Listos para envío | estado = listo |
| En preparación | estado = preparando |

### 3.5 KPIs

Cuatro, alineados con las vistas rápidas y **calculados en un solo sitio**
(servidor, reutilizando `$kpis`), clicables para aplicar su filtro:

`Por cobrar (S/)` · `Activos` · `En preparación` · `Atrasados +48 h`

### 3.6 Acciones condicionadas por permiso

| Acción | Permiso | Middleware que ya la protege |
|---|---|---|
| Ver detalle, Nota PDF, Imagen, Ticket | `orders.ver` | `project.can:orders.ver\|view-orders` |
| Editar, cambiar estado, WhatsApp | `orders.editar` | `project.can:orders.editar\|manage-orders` |
| Registrar pago | `orders.editar` | `pedidos.update` |
| Estado logístico | `orders.editar` **o** `manage-logistics` | `pedidos.kitchen`, `pedidos.wa.delivery` |
| Eliminar | `orders.eliminar` | `project.can:orders.eliminar\|manage-orders` |
| Nuevo pedido | `orders.crear` | `project.can:orders.crear\|manage-orders` |

Se envuelven en `@can` para que quien no pueda **no vea el botón**, en lugar de
recibir un 403 al pulsarlo. Es cambio de UI: la autorización del servidor queda
intacta.

### 3.7 Responsive

| Ancho | Comportamiento |
|---|---|
| ≥1280 | tabla completa, todas las columnas |
| 1024-1279 | oculta Canal |
| 768-1023 | oculta Canal + Items; KPIs en 2×2 |
| <768 | **tarjetas** en vez de tabla: cliente + total + 3 píldoras de estado; filtros en hoja inferior |

### 3.8 Qué se reutiliza tal cual

- `OrderFlow` completo (estados, SLA, lavandería)
- Endpoints de documentos: Nota PDF, Imagen, Ticket
- Menú de plantillas de WhatsApp y su registro de eventos
- `$paymentMethods`, `$paymentConditions`, `$salesChannels` desde `catalogLists`
- Historial de eventos (`OrderEvent`) en el panel de detalle
- Píldoras de estado y clases `.status-pill`, `.ch-tag`
- El patrón de cajón del Paso 1 para el detalle

### 3.9 Qué se corrige de camino

1. Los 4 endpoints con `url('/orders')` pasan a `$ordersApiBase` (§2.1)
2. KPIs unificados en un solo cálculo (§2.2)
3. `@can` en las acciones (§2.3)
4. "BIXO Score" → "Índice operativo" y `<title>` sin BIXO (§2.4)

---

## 4. Fuera de este paso

- **Nuevo pedido** → Paso 4
- Lógica fiscal, IGV, SUNAT
- Arquitectura de permisos y roles
- `comercial/dashboard.blade.php` y `comercial/mesas.blade.php` (también citan
  BIXO, pero son otras vistas)
- Paginación en servidor: hoy son 500 en cliente y aguanta; si crece, es otro paso

---

## 5. Decisiones que necesito de ti

1. **Detalle en panel deslizante** o **página propia** `/bixosales/pedidos/{id}`.
   El panel mantiene el contexto de la lista; la página da más sitio y es
   enlazable. Recomiendo panel, coherente con el Paso 1.
2. **"Índice operativo"** como sustituto de "BIXO Score", ¿o prefieres otro
   nombre, o quitar el widget?
3. **Densidad de la tabla**: cómoda (48 px por fila) o compacta (36 px).
   Recomiendo compacta: es una vista operativa de uso diario.
