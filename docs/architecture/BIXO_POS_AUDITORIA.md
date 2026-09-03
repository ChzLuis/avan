# POS — Auditoría previa al rediseño (Fase 1)

Fecha: 2026-09-03 · Medido contra el código y la base de **producción**.
Entregable pedido en el punto 36 del encargo: **nada de código todavía**.

---

## A. Inventario actual

### Pantallas y rutas reales

| Pantalla | Ruta | Controlador | Vista | Líneas |
|---|---|---|---|---|
| POS | `GET /bixosales/pos` | `PosController@indexComercial` | `pos/index.blade.php` | 1 589 |
| Cobrar (POS) | `POST /bixosales/pos` | `PosController@store` | — | |
| Cotizar desde POS | `POST /bixosales/pos/cotizar` | `PosController@quote` | — | |
| Venta express | `GET /bixosales/ventas/express` | `PosController@express` | `ventas/express.blade.php` | 441 |
| Caja | `/bixosales/caja` | `CajaController` | `comercial/caja.blade.php` | |

`PosController` tiene 498 líneas y 7 métodos: `index`, `store`,
`indexComercial`, `indexPortal`, `storePortal`, `quote`, `express`. Los tres
`index*` renderizan **la misma vista** cambiando el layout — no hay una vista
móvil y otra de escritorio, hay **una sola**.

### Funcionalidades que existen hoy (no se pierde ninguna)

- Búsqueda de productos y servicios, filtro por categoría.
- Carrito con cantidad, **precio editable**, descuento por ítem y global.
- **Modo Revendedor** ("Vender fácil"): precios propios del vendedor.
- Control de **precio mínimo**: bloquea cobrar por debajo del mínimo.
- **Margen/ganancia** del carrito calculada en vivo (para quien ve costos).
- Cliente: selección y alta rápida.
- Métodos de pago desde catálogo del negocio (`payment_method`).
- Emitir comprobante o **convertir a cotización** desde el mismo carrito.
- Ventas del día (últimas 50) como historial lateral.
- Venta express con precarga de cliente desde CRM, pedido, cotización o
  conversación de WhatsApp.

---

## B. Problemas detectados

### B1. Responsive: no existe

`pos/index.blade.php` tiene **cero reglas `@media`**. La pantalla de facturas
sí las tiene (se trabajaron aparte). El POS en móvil es literalmente el
escritorio encogido — exactamente lo que el encargo quiere eliminar.

### B2. Rendimiento: el catálogo entero viaja en cada carga

`indexComercial` hace `$project->products()->...->get()` **sin límite** y lo
serializa dentro del HTML. Medido en producción:

| Negocio | Productos |
|---|---|
| Corporación MegaHogar | **1 275** |
| Tecsist Solution | 140 |
| Market Huacho Express | 112 |

MegaHogar carga ~1 275 productos en cada apertura del POS. A ~200 bytes de
JSON por producto son **~250 KB solo de catálogo**, embebidos en la página,
antes de vender nada. Con 5 000 productos (escenario del encargo) esto no se
sostiene: hace falta búsqueda en servidor con paginación y `debounce`.

### B3. DNI/RUC: duplicada, incoherente y **rota en producción**

Hay **dos integraciones distintas, con dos proveedores distintos**:

| Qué | Dónde | Proveedor | Cliente HTTP | Token |
|---|---|---|---|---|
| RUC | `InvoiceController::consultarRuc` | `dniruc.apisperu.com` | `curl` crudo | `apiperu_token` |
| DNI | `RifaController::consultarDni` | `api.apis.net.pe` | `Http` facade | ninguno |

Problemas concretos:

1. **La consulta de RUC no funciona hoy.** Exige el ajuste `apiperu_token` y
   **ningún proyecto lo tiene configurado** (verificado en la BD de
   producción: solo existe `apisperu_token`, que es el de *facturación*, otro
   servicio). Toda consulta responde *"Configura el token de APIPERU"*.
2. **El DNI vive en `RifaController`** — un controlador de sorteos. Su ruta
   exige `can:rifas.ver`, así que **un cajero sin permiso de rifas no puede
   consultar un DNI**. Es un accidente de historia, no una decisión.
3. El RUC devuelve solo `razon_social` y `direccion`. El encargo pide también
   departamento, provincia, distrito, estado y condición.
4. `curl` crudo: sin reintentos, sin registro, sin timeout configurable.
5. **Sin caché**: el mismo RUC se consulta tantas veces como se escriba.
6. **El POS no consulta documentos en absoluto** — no hay una sola referencia
   a DNI/RUC en sus 1 589 líneas. Hoy solo existe en la pantalla de facturas.

### B4. Una sola vista para tres contextos

`pos/index.blade.php` sirve al panel, al portal comercial y al portal de
facturación. Cualquier cambio afecta a los tres a la vez: es la razón por la
que "mejorar el móvil" hoy es arriesgado.

---

## C. Arquitectura propuesta

**Una lógica, dos presentaciones.** Nada de funcionalidades distintas por
dispositivo (regla del encargo).

```
PosController  (sin cambios de reglas de negocio)
   ├── datos: catálogo paginado + búsqueda en servidor
   └── vista pos/index.blade.php
          ├── @include pos/_movil     (bottom sheet, FAB, pasos)
          └── @include pos/_escritorio (3 columnas, atajos)
```

Ambas ramas consumen **el mismo componente Alpine** (`posApp()`), el mismo
carrito, los mismos permisos y los mismos endpoints. Lo que cambia es la
distribución, no la lógica — así no puede aparecer una función en un
dispositivo y faltar en el otro.

**Servicio único de documentos** — `App\Support\ConsultaDocumento`:

- Un solo punto para DNI, RUC 10 y RUC 20.
- Validación local **antes** de llamar (8 u 11 dígitos, solo números, dígito
  verificador del RUC) para no gastar cuota en documentos imposibles.
- Caché por documento (evita repetir la misma consulta).
- Errores con lenguaje de persona, nunca "HTTP 500".
- Reemplaza las dos implementaciones actuales sin romper a quien ya las usa.

---

## D. Flujo móvil propuesto

Objetivo del encargo: **buscar → seleccionar → cliente → cobrar → emitir**.

```
┌──────────────────────────┐
│ 🔍 Buscar o escanear     │  ← siempre arriba, enfocado al abrir
├──────────────────────────┤
│  [Producto]  [Producto]  │  ← rejilla de 2, toque = añade directo
│  [Producto]  [Producto]  │     (variantes: solo entonces se pregunta)
│         · · ·            │
├──────────────────────────┤
│ 🛒 4 productos  S/ 248.00│  ← barra fija; toque = se abre el detalle
│         COBRAR           │  ← acción principal, siempre visible
└──────────────────────────┘
```

- El carrito es un **bottom sheet**: se revisa sin perder la lista.
- Cliente y comprobante se piden **en el cobro**, no antes.
- "No encontramos ese producto" ofrece **+ Crear producto** ahí mismo, y al
  guardar lo añade al carrito sin salir de la venta.
- DNI/RUC se completa solo al escribir los 8 u 11 dígitos.

## E. Flujo escritorio propuesto

Mismo esqueleto, aprovechando el espacio:

```
┌─────────┬───────────────────────┬──────────────┐
│Categorías│ 🔍 Buscar            │ Carrito      │
│ Todos    │ [Prod] [Prod] [Prod] │ 2 × Cemento  │
│ Bebidas  │ [Prod] [Prod] [Prod] │ 1 × Pintura  │
│ Ferretería│        · · ·        │ ─────────────│
│          │                      │ Total 248.00 │
│          │                      │ [ COBRAR ]   │
└─────────┴───────────────────────┴──────────────┘
```

El carrito deja de ser un panel que aparece: está siempre visible.

---

## F. Componentes que se reutilizan

- `posApp()` (Alpine) — el motor del carrito, tal cual.
- `PosController@store` y `@quote` — cobrar y cotizar no se tocan.
- Catálogo de métodos de pago del negocio.
- Modal de confirmación global (`partials/confirm-global`).
- Avisos (`partials/avisos`: `bxAviso`, `bxConfirmar`).
- Permisos existentes: `pos.usar`, precios mínimos, Modo Revendedor.

## G. Componentes a refactorizar

| Componente | Por qué | Riesgo |
|---|---|---|
| `pos/index.blade.php` (1 589 líneas) | Sirve 3 contextos y no tiene responsive | **Alto** — se parte en móvil/escritorio conservando el mismo Alpine |
| `PosController@indexComercial` | Carga el catálogo entero | Medio — pasa a búsqueda paginada |
| `InvoiceController::consultarRuc` | curl crudo, token inexistente | Medio — delega en el servicio único |
| `RifaController::consultarDni` | DNI atrapado tras `rifas.ver` | Bajo — se mantiene la ruta como alias y se apunta al servicio |

## H. Integración DNI/RUC — estado y plan

**Estado: no operativa.** El RUC pide un token que nadie tiene; el DNI usa
otro proveedor y exige permiso de rifas.

Plan:
1. `ConsultaDocumento` como único punto (DNI, RUC 10, RUC 20).
2. Validación local + `debounce` antes de llamar.
3. Caché por documento.
4. Estados visibles: *Consultando… / Datos encontrados / No se encontraron /
   No se pudo conectar*, **sin bloquear** el formulario: siempre se puede
   escribir a mano.
5. Mapear todo lo que devuelve la API (razón social, nombre comercial,
   dirección, ubigeo, estado, condición).
6. Decidir **qué token** es el bueno y dejarlo documentado — hoy conviven
   `apiperu_token` (vacío) y `apisperu_token` (facturación).
7. Mocks solo en pruebas; nunca datos ficticios en producción.

---

## Riesgos y orden propuesto

El POS es **la caja del negocio**: si falla, no se vende. Por eso el orden no
empieza por lo vistoso:

| Fase | Qué | Por qué primero |
|---|---|---|
| 1 | Servicio único DNI/RUC + tests | Hoy está roto y es lo que más pediste |
| 2 | Búsqueda en servidor paginada | Sin esto, MegaHogar (1 275) y 5 000 productos no se sostienen |
| 3 | Vista móvil (bottom sheet, FAB, alta rápida) | El grueso del encargo |
| 4 | Vista escritorio de 3 columnas | Mismo motor, otra distribución |
| 5 | Hub de postventa y accesos rápidos | Reorganización, sin lógica nueva |
| 6 | Regresión completa + responsive medido | 8 resoluciones del encargo |

**Compromiso:** cada fase se despliega con su regresión en verde y ninguna
funcionalidad del inventario (A) desaparece; si algo no encaja en el diseño
nuevo, se reubica, no se borra.
