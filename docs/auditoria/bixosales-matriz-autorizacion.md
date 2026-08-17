# BixoSales — matriz de autorizacion (Paso 0-Security, fase 2)

**Fecha:** 2026-08-14
**Estado:** matriz entregada. **CODIGO NO MODIFICADO.**
**Fase 1 (ya en produccion):** commit `9b2bcc5`, panel `/orders` y `/quotes`.

Proteccion actual de **todo** el portal: `comercial.auth` (sesion + proyecto activo).
Cero comprobaciones de permiso en 97 rutas.

---

## 0. Cifras exactas

| | |
|---|---|
| Rutas dentro de `comercial.auth` | **101** |
| Con verbo mutador (POST/PUT/PATCH/DELETE) | **59** |
| De esas, que **realmente** mutan estado | **58** |
| POST cuya semantica es lectura (excepcion documentada) | **1** |
| Permisos nuevos a crear | **11** |

> **Actualizado en el Paso 2 (2026-08-15).** Se añadieron 4 rutas al portal
> —`pedidos.pay`, `pedidos.events`, `pedidos.wa-sent`, `pedidos.laundry-status`—
> porque la vista de Pedidos las llamaba contra el PANEL (`url('/orders')/...`)
> aun renderizada dentro del portal. Reutilizan los handlers existentes.
> De las 4, tres son mutadoras (56 → 59) y `events` es GET.
> Las tres nacen protegidas, asi que se mantiene:
> **0 mutaciones reales unicamente bajo `comercial.auth`.**

La unica excepcion verbo/semantica es `POST /pagos/pendientes`
(`Api\PagoController@pendientes`): lista pagos pendientes, no escribe nada. Usa
POST por comodidad del cliente JS. Le corresponde `payments.ver`.

---

## 1. Compatibilidad universo A / universo B

Los dos esquemas de permisos estan vivos y son disjuntos:

| Universo | Roles | Ejemplo |
|---|---|---|
| **A** (legacy, deprecado) | `owner`, `admin`, `comercial`, `logistica`, `operaciones` | `view-orders`, `manage-orders` |
| **B** (objetivo) | `superadmin`, `gerente`, `vendedor`, `almacen`, `contador`, `solo_lectura`, `revendedor` | `orders.ver`, `orders.crear` |

Solo `superadmin` pertenece a ambos.

### Equivalencias inequivocas que SI se aplican

| Dominio | Lectura | Escritura |
|---|---|---|
| Pedidos | `orders.ver` OR `view-orders` | `orders.*` OR `manage-orders` |
| Cotizaciones | `quotes.ver` OR `view-quotes` | `quotes.*` OR `manage-quotes` |
| Clientes | `clients.ver` OR `view-clients` | `clients.*` OR `manage-clients` |
| Logistica/Delivery | `view-logistics` | `manage-logistics` |

Delivery **solo** existe en el universo A: no hay `delivery.*` ni `logistics.*` en B.
Se conserva `manage-logistics` como capacidad legitima, no como compatibilidad.
Queda anotado para migrar a B en la fase RBAC.

### Equivalencias que NO se aplican por ambiguas

`view-catalog` no equivale a `catalog.ver` de forma inequivoca (uno cubre el
catalogo publico y el otro el modulo interno). `view-requests` y `manage-requests`
no tienen dominio correspondiente en B. `manage-agenda` frente a `agenda.crear` /
`agenda.editar` / `agenda.eliminar` es 1-a-N, no 1-a-1: un OR aqui concederia de
golpe las tres acciones a quien solo deberia tener una.

### Mecanismo

`can:` (Laravel `Authorize`) acepta **una sola** habilidad; no hay sintaxis OR.
Tampoco estan registrados los alias `permission` / `role` de Spatie.

Se propone extender el middleware ya existente
[`CheckPermission`](../../app/Http/Middleware/CheckPermission.php) —hoy registrado
como `project.can` y sin uso— para aceptar alternativas separadas por `|`:

    ->middleware('project.can:orders.crear|manage-orders')

Ventajas: reutiliza una pieza existente, no añade un tercer esquema de nombres, y
`project.can` ya contempla el atajo de superadmin y de dueño del proyecto. Se
cambiaria `hasPermissionTo()` por `$user->can()` por cada alternativa, que
devuelve `false` ante un permiso inexistente en vez de lanzar
`PermissionDoesNotExist`.

Donde no hay equivalencia (caja, mapa, tickets, pagos, facturas, agenda, rifas,
pos) se mantiene `can:` a secas, sin cambios.

---

## 2. Permisos nuevos (11)

| Permiso | Cubre |
|---|---|
| `caja.ver` | consultar caja y su detalle |
| `caja.abrir` | abrir caja |
| `caja.cerrar` | cerrar caja |
| `caja.movimiento` | registrar ingreso/egreso |
| `mapa.ver` | consultar mapa operativo, objetos, historial |
| `mapa.editar` | **toda** mutacion del mapa (11 rutas) |
| `tickets.ver` | tickets manuales y conversaciones |
| `tickets.eliminar` | borrado manual de ticket |
| `payments.ver` | listar pagos pendientes |
| `payments.aprobar` | aprobar pago |
| `payments.rechazar` | rechazar pago |

### Asignacion definitiva (confirmada)

| Rol | Permisos nuevos |
|---|---|
| `gerente` | los 11 |
| `contador` | `caja.ver`, `payments.ver`, `payments.aprobar`, `payments.rechazar` |
| `vendedor` | `caja.ver`, `payments.ver` |
| `solo_lectura` | `caja.ver`, `mapa.ver`, `tickets.ver`, `payments.ver` |
| `qa_lectura` | **ninguno** — solo `orders.ver` y `quotes.ver` |
| `superadmin` | se asignan por consistencia, pero la autorizacion **no depende** de ello: el atajo de `Gate::before` es suficiente |

Capacidades sensibles —`mapa.editar`, `tickets.eliminar`, `caja.abrir`,
`caja.cerrar`, `caja.movimiento`— quedan solo en `gerente`.

#### Por que el vendedor NO recibe las operaciones de caja

Comprobado en el codigo: [`PosController`](../../app/Http/Controllers/PosController.php)
**no menciona `Caja` ni una sola vez**. Vender por POS no abre caja, no la cierra
y no registra movimientos: los dos modulos estan desacoplados.

[`CajaController`](../../app/Http/Controllers/CajaController.php) sella
`auth()->id()` y `auth()->user()->name` al abrir (`:72`) y en cada movimiento
(`:131`), de modo que la caja queda atribuida a quien la opera. Pero nada en el
flujo de venta lo exige.

**No hay bloqueo tecnico.** Que un vendedor opere su propia caja es una decision
de negocio, no una dependencia del codigo. Si en el futuro se quiere ese modelo,
basta añadir `caja.abrir` / `caja.cerrar` / `caja.movimiento` al rol.

---

## 3. Matriz completa

`R` = lectura · `M` = mutacion · ⚠️ = excepcion documentada

### 3.1 Pedidos (prioridad 1)

| Ruta | Verbo | Controlador@metodo | R/M | Propuesto |
|---|---|---|---|---|
| `/pedidos` | GET | `OrderController@index` | R | `orders.ver\|view-orders` |
| `/pedidos/{o}` | GET | `OrderController@show` | R | `orders.ver\|view-orders` |
| `/pedidos` | POST | `OrderController@store` | M | `orders.crear\|manage-orders` |
| `/pedidos/{o}` | PUT | `OrderController@update` | M | `orders.editar\|manage-orders` |
| `/pedidos/{o}` | DELETE | `OrderController@destroy` | M | `orders.eliminar\|manage-orders` |
| `/pedidos/{o}/wa-action` | POST | `WaBotController@portalAction` | M | `orders.editar\|manage-orders` |
| `/pedidos/{o}/wa-delivery` | POST | `WaBotController@updateDelivery` | M | `orders.editar\|manage-orders` |
| `/pedidos/{o}/kitchen` | PATCH | `OrderController@updateKitchen` | M | `orders.editar\|manage-orders` |
| `/cocina` | GET | `OrderController@kitchen` | R | `orders.ver\|view-orders` |
| `/mesas` | GET | `MesaController@index` | R | `orders.ver\|view-orders` |
| `/mesas/data` | GET | `MesaController@data` | R | `orders.ver\|view-orders` |

### 3.2 Cotizaciones (prioridad 2)

| Ruta | Verbo | Controlador@metodo | R/M | Propuesto |
|---|---|---|---|---|
| `/cotizaciones` | GET | `QuoteController@index` | R | `quotes.ver\|view-quotes` |
| `/cotizaciones/{q}` | GET | `QuoteController@show` | R | `quotes.ver\|view-quotes` |
| `/cotizaciones` | POST | `QuoteController@store` | M | `quotes.crear\|manage-quotes` |
| `/cotizaciones/{q}` | PUT | `QuoteController@update` | M | `quotes.editar\|manage-quotes` |
| `/cotizaciones/{q}/full` | PUT | `QuoteController@updateFull` | M | `quotes.editar\|manage-quotes` |
| `/cotizaciones/{q}` | DELETE | `QuoteController@destroy` | M | `quotes.eliminar\|manage-quotes` |
| `/cotizaciones/{q}/send` | POST | `QuoteController@send` | M | `quotes.editar\|manage-quotes` |
| `/cotizaciones/{q}/duplicate` | POST | `QuoteController@duplicate` | M | `quotes.crear\|manage-quotes` |
| `/cotizaciones/{q}/seen` | POST | `QuoteController@markSeen` | M ⚠️ | `quotes.ver\|view-quotes` |

`seen` es un acuse de lectura que dispara la propia vista; exigir escritura daria
403 a todo lector legitimo. Mismo criterio ya desplegado en el panel.
El portal **no** expone `convert`.

### 3.3 Clientes y Facturas (prioridad 3)

| Ruta | Verbo | Controlador@metodo | R/M | Propuesto |
|---|---|---|---|---|
| `/clientes` | GET | `ClientController@index` | R | `clients.ver\|view-clients` |
| `/clientes` | POST | `ClientController@store` | M | `clients.crear\|manage-clients` |
| `/clientes/{c}` | PUT | `ClientController@update` | M | `clients.editar\|manage-clients` |
| `/clientes/{c}` | DELETE | `ClientController@destroy` | M | `clients.eliminar\|manage-clients` |
| `/facturas` | GET | `InvoiceController@index` | R | `invoices.ver` |
| `/facturas/{i}` | GET | `InvoiceController@show` | R | `invoices.ver` |
| `/facturas/{i}/pdf` | GET | `InvoiceController@pdf` | R | `invoices.ver` |
| `/facturas` | POST | `InvoiceController@store` | M | `invoices.crear` |
| `/facturas/{i}` | PUT | `InvoiceController@update` | M | `invoices.editar` |
| `/facturas/{i}` | DELETE | `InvoiceController@destroy` | M | `invoices.anular` |
| `/facturas/{i}/sunat` | POST | `InvoiceController@sendSunat` | M | `invoices.crear` |

Solo se añade middleware. **No se toca ni una linea de logica fiscal ni de SUNAT.**

### 3.4 Endpoints AJAX de esos flujos (prioridad 4)

| Ruta | Verbo | R/M | Propuesto |
|---|---|---|---|
| `/mesas/data` | GET | R | `orders.ver\|view-orders` |
| `/delivery/data` | GET | R | `view-logistics` |
| `/caja/{c}/data` | GET | R | `caja.ver` |
| `/reservas/calendar` | GET | R | `agenda.ver` |
| `/reportes/dashboard-data` | GET | R | `reports.ver` |
| `/conversaciones/{id}/mensajes` | GET | R | `tickets.ver` |
| `/woo/stats` | GET | R | `catalog.ver` |
| `/consultar-dni/{dni}` | GET | R | `rifas.ver` |
| `/mapa/maps/{map}/objects` | GET | R | `mapa.ver` |
| `/mapa/objects/{o}/history` | GET | R | `mapa.ver` |
| `/pagos/pendientes` | **POST** | **R** ⚠️ | `payments.ver` |

### 3.5 Caja

| Ruta | Verbo | Controlador@metodo | R/M | Propuesto |
|---|---|---|---|---|
| `/caja` | GET | `CajaController@index` | R | `caja.ver` |
| `/caja/{c}/data` | GET | `CajaController@data` | R | `caja.ver` |
| `/caja/abrir` | POST | `CajaController@abrir` | M | `caja.abrir` |
| `/caja/{c}/cerrar` | POST | `CajaController@cerrar` | M | `caja.cerrar` |
| `/caja/{c}/movimiento` | POST | `CajaController@movimiento` | M | `caja.movimiento` |

### 3.6 Mapa operativo — 11 mutaciones, todas bajo `mapa.editar`

| Ruta | Verbo | Metodo | R/M | Propuesto |
|---|---|---|---|---|
| `/mapa` | GET | `@index` | R | `mapa.ver` |
| `/mapa/maps/{map}/objects` | GET | `@objects` | R | `mapa.ver` |
| `/mapa/objects/{o}/history` | GET | `@history` | R | `mapa.ver` |
| `/mapa/maps` | POST | `@storemap` | M | `mapa.editar` |
| `/mapa/maps/{map}/objects` | POST | `@storeObject` | M | `mapa.editar` |
| `/mapa/objects/{o}/move` | PATCH | `@move` | M | `mapa.editar` |
| `/mapa/objects/{o}/status` | PATCH | `@changeStatus` | M | `mapa.editar` |
| `/mapa/objects/{o}/amount` | PATCH | `@updateAmount` | M | `mapa.editar` |
| `/mapa/objects/{o}/responsible` | PATCH | `@assignResponsible` | M | `mapa.editar` |
| `/mapa/objects/{o}/alerts` | POST | `@addAlert` | M | `mapa.editar` |
| `/mapa/objects/{o}/alerts` | DELETE | `@clearAlerts` | M | `mapa.editar` |
| `/mapa/objects/{o}/requests` | POST | `@createRequest` | M | `mapa.editar` |
| `/mapa/objects/{o}` | PUT | `@updateObject` | M | `mapa.editar` |
| `/mapa/objects/{o}` | DELETE | `@destroyObject` | M | `mapa.editar` |

### 3.7 Pedidos-bot (rifas)

| Ruta | Verbo | R/M | Propuesto |
|---|---|---|---|
| `/pedidos-bot`, `/monitoreo`, `/exportar` | GET | R | `rifas.ver` |
| `/pedidos-bot/{v}/validar` | POST | M | `rifas.validar` |
| `/pedidos-bot/{v}/enviar` | POST | M | `rifas.validar` |
| `/pedidos-bot/{v}/editar` | POST | M | `rifas.validar` |
| `/pedidos-bot/{v}/recordar` | POST | M | `rifas.validar` |
| `/pedidos-bot/{v}/enviar-membresia` | POST | M | `rifas.validar` |
| `/pedidos-bot/nuevo-manual` | POST | M | `rifas.validar` |
| `/pedidos-bot/{v}/cancelar` | POST | M | `rifas.cancelar` |
| `/pedidos-bot/{v}/eliminar` | POST | M | `rifas.cancelar` |

### 3.8 Resto

| Ruta | Verbo | R/M | Propuesto |
|---|---|---|---|
| `/` (dashboard) | GET | R | `orders.ver\|view-orders` |
| `/pos`, `/venta-express` | GET | R | `pos.usar` |
| `/pos`, `/pos/cotizar` | POST | M | `pos.usar` |
| `/revendedor/precios` | GET | R | `pos.usar` |
| `/revendedor/precio`, `/revendedor/catalogo` | POST | M | `pos.usar` |
| `/woo/orders` | GET | R | `catalog.ver` |
| `/woo/sync` | POST | M | `catalog.importar` |
| `/conversaciones`, `/tickets-manuales`, `/buscar` | GET | R | `tickets.ver` |
| `/tickets-manuales/eliminar` | POST | M | `tickets.eliminar` |
| `/reservas`, `/reservas/calendar` | GET | R | `agenda.ver` |
| `/reservas` | POST | M | `agenda.crear` |
| `/reservas/{a}` | PUT | M | `agenda.editar` |
| `/reservas/{a}` | DELETE | M | `agenda.eliminar` |
| `/delivery`, `/delivery/data` | GET | R | `view-logistics` |
| `/delivery` | POST | M | `manage-logistics` |
| `/delivery/{o}/status` | PUT | M | `manage-logistics` |
| `/pagos/aprobar` | POST | M | `payments.aprobar` |
| `/pagos/rechazar` | POST | M | `payments.rechazar` |
| `/reportes/*` (7 rutas) | GET | R | `reports.ver` |

Rutas de autenticacion (`/login` GET y POST, `/get-projects`, `/logout`) quedan
fuera: son previas a la sesion. `/logout` muta sesion, no datos de negocio.

---

## 4. Recuento de las 97 rutas

Verificacion aritmetica del grupo `comercial.auth`:

| Bloque | Rutas | De ellas mutadoras |
|---|---:|---:|
| Dashboard | 1 | 0 |
| POS + venta express | 4 | 2 |
| Pagos | 3 | 2 (+1 lectura por POST) |
| Revendedor | 3 | 2 |
| Pedidos-bot (rifas) | 12 | 8 |
| Woo | 3 | 1 |
| Conversaciones + tickets | 5 | 1 |
| **Pedidos** | 11 | 6 |
| Mapa operativo | 14 | 11 |
| Reservas | 5 | 3 |
| Delivery | 4 | 2 |
| Caja | 5 | 3 |
| **Cotizaciones** | 9 | 7 |
| Facturas | 7 | 4 |
| Clientes | 4 | 3 |
| Reportes | 7 | 0 |
| **TOTAL** | **97** | **55 + 1** |

56 rutas con verbo mutador, de las que 55 mutan estado y 1
(`POST /pagos/pendientes`) es consulta. Fuera del grupo quedan 4 rutas de
autenticacion (`/login` GET y POST, `/get-projects`, `/logout`), previas a la
sesion; el total del prefijo `bixosales` es 101.

**Objetivo verificable: 0 rutas mutadoras protegidas unicamente por `comercial.auth`.**

---

## 5. Despliegue por etapas

Una ruta que exige un permiso inexistente responde 403 a todo el mundo salvo
superadmin y dueño. Desplegar rutas antes que permisos deja el portal
inutilizable, y revertir con el portal caido no tiene vuelta atras limpia.

| # | Etapa | Verificacion |
|---|---|---|
| 1 | `CheckPermission` con soporte `\|` | Cambio **neutro**: `project.can` no se usa en ninguna ruta todavia |
| 2 | Smoke test | Portal y panel siguen respondiendo |
| 3 | Sembrar los 11 permisos (idempotente) | `Permission::count()` sube en 11 |
| 4 | Asignar permisos a roles segun §2 | Matriz rol→permiso en produccion |
| 5 | Verificar matriz resultante | Consulta directa a la base |
| 6 | Desplegar rutas BixoSales protegidas | `route:list` |
| 7 | Smoke tests | Sin 500, tiendas en 200 |
| 8 | Pruebas de autorizacion | Ver §6 |

## 6. Comprobaciones exigidas

1. 55/55 mutadoras protegidas
2. 0 mutadoras solo con `comercial.auth`
3. `POST /pagos/pendientes` documentado como consulta con `payments.ver`
4. `qa_lectura` no ejecuta **ninguna** mutacion del portal
5. `gerente` conserva la operacion completa
6. Usuario con permiso **solo del universo B** funciona
7. Usuario con **solo el equivalente legacy A** funciona
8. Usuario **sin A ni B** recibe 403

---

## 5. Fuera de alcance en esta fase

- Migrar el universo A a B (queda documentado aqui y en
  [incidencia-roles-multiproyecto.md](incidencia-roles-multiproyecto.md)).
- Spatie Teams.
- `orders.descuento`, inexistente en base e invocado en
  [`PosController`](../../app/Http/Controllers/PosController.php) con
  `hasPermissionTo()`, que lanza excepcion. Bug de POS aparte.
- Rediseño de vistas, logica comercial, calculos, IGV, SUNAT.
- Paso 2.
