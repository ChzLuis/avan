# Plan de trabajo — lo que queda después de la reestructura

Última actualización: 2026-08-27 · continúa desde `BIXO_MASTER_CHECKLIST.md`
(fases 0–8 ejecutadas). Orden de ejecución tal cual, de arriba hacia abajo.

## 1. F9 — Commerce/checkout: VERIFICAR, no construir

Hallazgo 2026-08-27: el checkout estilo WooCommerce **ya existe** en la
plantilla computienda (2 columnas, boleta/factura con RUC, Yape QR + vcard,
recojo/envío, bancos expandibles, modo solo-cotización). La memoria que lo
daba por pendiente estaba desactualizada.

- [ ] Configurar pagos de GABDE (número/QR Yape, cuentas) cuando el negocio los entregue
- [ ] Prueba de compra end-to-end en una tienda con pagos configurados
- [ ] Decidir si GABDE opera en modo cotización (B2B) o carrito

## 2. F11 — Portal del Cliente ✔ COMPLETADA (2026-08-27, commit 7add45a, en ARIN)

Diseño acordado: enlace con token por cliente (patrón de las cotizaciones),
sin contraseñas. El cliente ve sus últimos pedidos y **repite pedido** en un
toque. Interruptor por negocio:

- `portal_precios = fijos` → repetir crea PEDIDO pendiente a precio de catálogo actual
- `portal_precios = confirmar` → repetir crea COTIZACIÓN (el negocio pone el
  precio del día y el cliente acepta desde su enlace — circuito ya existente)

Entregables:
- [x] Migración `clients.portal_token` (nullable, único) — corrida local 2026-08-27
- [x] Ruta pública `GET /c/{token}` — ANTES del comodín /{slug} (verificado en route:list)
- [x] Acción "repetir pedido" (POST, throttle 15/min) según el interruptor
- [x] Botón "Enlace del portal" en la ficha del cliente (generar/copiar/regenerar; panel y bixosales)
- [x] Tests: `PortalClienteTest` 6/6 — acceso, ambos modos, token inválido, pedido ajeno, enlace por proyecto propio

## 3. Limpieza diferida (un ciclo después, ~sept 2026)

- [ ] Borrar las 13 vistas de `/f/{slug}` si nadie llegó a las rutas *.legado
- [ ] Retirar los 18 lectores de `comercial_project_id` (usar solo `active_project_id`)
- [ ] Retirar los 9 nombres de permiso legacy de rutas y roles
- [ ] `BIXO_NAVIGATION_MAP.md` y apagado del panel raíz con redirects

## 4. Bloqueado por volumen (ADR-005) — revisar cuando haya >100 comprobantes/mes

- F10 Atribución + Smart QR · F12 Pulse · F13 IA contextual

## Puertas de calidad (cada punto)

Suite completa en verde → deploy sin deriva → checklist/handoff actualizados → commit.
