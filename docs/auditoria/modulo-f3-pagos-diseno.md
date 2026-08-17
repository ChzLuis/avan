# F3 — Pagos: los cobros entran al libro · DISEÑO AUDITADO

**Fecha:** 2026-08-16 · **Estado:** DISEÑO — *sin cambios de flujo todavía*
· **Depende de:** F2b (libro `payments` + `receivable_terms`, en producción)
· **Habilita:** F4 Facturación, conciliación bancaria

---

## 1. Auditoría: quién escribe cobros hoy

Cuatro escritores, ninguno pasa por el libro. Todos mutan columnas:

| Escritor | Qué hace | Riesgo de tocarlo |
|---|---|---|
| `OrderController@pay` | Panel: fija `payment_status` y `advance_amount` | **Bajo** — usuarios internos, un solo drawer |
| `Api\PagoController@aprobar/rechazar` | Bot y extensión aprueban Yape/Plin | **Alto** — lo usan clientes reales por WhatsApp |
| `PosController@store` | Venta directa: `paid` o `pending` + `advance_amount` | Medio — mostrador en vivo |
| `PortalController@proof` | El cliente sube su comprobante | Medio — cara al público |

### 1.1 El defecto activo que hay que cerrar

`OrderController@pay` valida `'amount' => 'nullable|numeric'` y solo guarda el
importe **si el estado es `partial` Y viene `amount`**. Como `amount` es
opcional, se puede marcar un pedido como **pago parcial sin decir cuánto**.

Eso no es teórico: **los pedidos #34 (S/ 1 266,00) y #35 (S/ 490,00) están así
en producción ahora mismo** — `payment_status='partial'` con `advance_amount`
NULL. El sistema afirma que se cobró algo pero no sabe cuánto, y Cuentas por
Cobrar acaba contando el total como deuda.

Segundo detalle: cuando el estado es `paid`, se escribe `advance_amount = null`.
Es coherente con "ya no hay saldo parcial", pero significa que **el importe
cobrado no queda registrado en ninguna parte**. Con el libro deja de importar,
porque el cobro será un asiento.

### 1.2 Lo que el libro ya resuelve (F2b)

`App\Support\Ledger` ya sabe registrar, revertir y **proyectar**: recalcula
`payment_status` y `advance_amount` desde la suma de asientos. Es decir, los
escritores actuales pueden seguir viendo exactamente las mismas columnas; lo
que cambia es quién las escribe.

---

## 2. Diseño

### 2.1 Principio: un solo camino hacia el dinero

Hoy hay cuatro sitios que deciden el estado de pago, cada uno con su
vocabulario. A partir de F3, **todos llaman a `Ledger`** y ninguno escribe
`payment_status` ni `advance_amount` a mano. La proyección los mantiene, así
que el bot, la extensión, el POS y las vistas siguen leyendo lo de siempre.

### 2.2 Fases, de menor a mayor riesgo

**F3a — Panel (`OrderController@pay`).** Usuarios internos, reversible, sin
clientes de por medio. Un cobro pasa a ser `Ledger::registrar`.
- `partial` **exige importe**: se acabó el "pagado a medias sin decir cuánto".
- `paid` sin importe registra el saldo pendiente completo (es lo que significa).
- `pending` sobre un pedido con cobros ya no borra nada: exige revertir el
  asiento concreto, con motivo. Anular un cobro deja rastro.
- Se añade el historial de cobros al drawer: hasta ahora dos abonos parciales
  eran indistinguibles.

**F3b — Bot y extensión (`Api\PagoController`).** El adaptador legacy que ya
decidió Codex: `aprobar` deja de mutar y registra un asiento
`source='bot'|'extension'` con su referencia; `rechazar` revierte si había
asiento. **El contrato JSON de respuesta no cambia** — el bot no se entera.
- Antes de tocarlo: contrato Playwright contra el endpoint real verificando que
  la forma de la respuesta es idéntica.

**F3c — POS y portal.** Venta directa al contado = asiento inmediato. El
comprobante del portal queda como "reportado", no como cobrado: lo confirma
alguien.

### 2.3 Reglas nuevas

1. Ningún controlador escribe `payment_status`/`advance_amount`; solo `Ledger`.
2. Un cobro parcial **siempre** lleva importe.
3. Deshacer un cobro es revertir, no editar ni borrar.
4. Cada asiento guarda su origen (`panel`, `bot`, `extension`, `pos`, `portal`)
   para que la conciliación futura sepa de dónde vino cada sol.

### 2.4 Qué NO entra

Conciliación bancaria, caja/tesorería (`cajas` está a 0 filas y merece su
propia fase) y cualquier cosa fiscal. F3 es "el dinero entra al libro por
todas las puertas", nada más.

---

## 3. Los dos pedidos huérfanos

#34 y #35 seguirán marcados `partial` sin importe. El usuario ya decidió
tratarlos como **pendientes sin cobro**, así que F3a no los inventa: quedan con
su deuda completa hasta que alguien registre el cobro real por el panel, que
entonces sí quedará como asiento. Lo que sí cambia es que **no podrán volver a
crearse casos así**.

---

## 4. Riesgo y verificación

El paso delicado es F3b: el bot atiende clientes reales. Antes de desplegarlo
se fija un contrato que capture la respuesta actual del endpoint y falle si
cambia una sola clave. F3a se puede desplegar de forma independiente y aporta
valor solo, que es el orden correcto: no se toca lo peligroso hasta que lo
seguro esté rodado.
