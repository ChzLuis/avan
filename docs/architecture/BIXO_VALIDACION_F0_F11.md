# Validación final F0–F11 (checkpoint 2026-08-27)

> Auditoría independiente contra el CÓDIGO REAL, los commits, la suite y el
> estado de ARIN. No se dio nada por cerrado por aparecer como tal en los
> documentos. Método: 4 auditores en paralelo (aislamiento/jobs, permisos/
> impersonación, Sales/POS/Finance/f-slug, seguridad F11) + verificación
> directa de cada hallazgo crítico por quien firma. Los falsos positivos de
> los auditores se marcan como tales.

## Veredictos permitidos
PASS · PASS WITH DEBT · PARTIAL · FAIL · NOT VERIFIED

## Tabla FASE × VEREDICTO

| Fase | Declarado | Verificado | Evidencia | Tests | Deuda / pendientes | Veredicto |
|---|---|---|---|---|---|---|
| **F0 Auditoría AS-IS** | Medición real del código | Cierto: 7 portales, rutas, matriz de aislamiento medidos | `BIXO_CURRENT_STATE.md`, commit `15ecf80` | n/a | CURRENT_STATE no menciona F11 | **PASS** |
| **F1 Seguridad multiempresa** | Fail-closed, 14 entidades, 4 huecos cerrados | Cierto en lo declarado: `HasProjectScope` fail-closed (L38 `1=0`), 14/14 con trait, 4 huecos con test HTTP real | `HasProjectScope.php:32-46`, `AislamientoTenantTest.php` (9 métodos) | 9 verdes | `transitionStore` sin candado; `DarDeBajaEnSunat:61` sin `allProjects()` (latente, cola=database→consola); **fuga de lectura cross-tenant en reportes de rifa** (`ReporteController:21`, `RifaController:264/328/413/683` vía `BotInstance` sin scope); `WaBotController` mutable por token compartido; test de las 14 entidades es solo `toSql()` | **PASS WITH DEBT** |
| **F2 Ownership + permisos** | Migración aditiva, un propietario, sin huecos | Parcial: bixosales 65/65 con permiso (verificado); migración aditiva coherente por niveles | `BixoSalesAuthorizationTest.php` (65 + barrido) | verdes | **`DELETE/PUT /projects/{project}` solo exige pertenencia** (un `solo_lectura` borra el negocio); rutas GET del panel sin `can:` (exposición de lectura); `/api/pagos/aprobar` salta `payments.aprobar` vía copilot_token | **PARTIAL** |
| **F3 Control vs Workspace + impersonación** | Separados; impersonación auditada | Parcial: `/admin/*` íntegro bajo `superadmin`; impersonación restringida (superadmin) y auditada AL ENTRAR | `routes/admin.php:23`, `web.php:687-703` | sin test propio | Impersonación **sin ruta de salida, sin límite de proyectos, sin rastro de salida**, `meta` doble-codificado, check inline en vez del alias `superadmin` | **PARTIAL** |
| **F4 Menú único por permisos** | Gobernado por permiso, no solo ocultar | Parcial: 6/10 ítems alineados; escrituras de ajustes SÍ cerradas | `_sidebar.blade.php:18-25` vs rutas | verdes | Mi negocio / Constructor / Código QR: la LECTURA solo la protege ocultar el menú (GET sin `can:`); Reparto desalineado menú↔ruta | **PARTIAL** |
| **F5 Sales consolidado + retiro f-slug** | `/f/{slug}` retirado; mismos controladores | **El retiro es cosmético**: solo login+dashboard redirigen; 34 rutas operativas siguen vivas con `facturacion.auth` (solo sesión, **sin RBAC granular**) = bypass de la matriz de bixosales dentro del tenant. Login sí valida pertenencia (no hay fuga entre empresas) | `web.php:906-959`, `FacturacionAuth.php:13` | `FusionPortalesTest` solo prueba el redirect del login | Lógica duplicada real: `updateFullPortal` sin guard-convertida ni auditoría; `convertirPortal` numera fuera de transacción; `/bixofact` sigue abierto | **PARTIAL** |
| **F6 Customer 360** | Agregador read-model sin duplicar | Cierto que es read-only puro sin tablas nuevas | `ClientController::historialDe:67-101` | `Customer360Test` (2) | **3 de 5 fuentes nunca se llenan**: `Invoice.client_id`, `GuiaRemision.client_id`, `SalesInteraction` no tienen escritor → hoy solo muestra cotizaciones y pedidos | **PARTIAL** |
| **F7 POS canónico** | Product/Sales/Payments/Inventory canónicos | Cierto: `PosController` crea Order + `Ledger::registrar` (Payment) + `InventoryLedger` | `PosController:169-237` | verdes | Deuda del sistema (no del POS): 7 de 9 creadores de Order no descuentan stock; `ProductController:251` escribe stock a mano | **PASS WITH DEBT** |
| **F8 Finance desacoplado** | Invoice/Payment/Receivable canónicos; estados desacoplados | Cierto: pago (Order.payment_status por Ledger) y fiscal (sunat_status) separados; baja SUNAT no toca cobro; deuda derivada (total−pagos) | `Ledger.php:136-195`, `NotaController:186` | verdes | **Dos cálculos de deuda divergentes**: `Cobranza` (columna cache) vs `ClientController::resumenDe` (libro) → difieren en pedidos legacy | **PASS WITH DEBT** |
| **F9 Commerce/checkout** | Checkout ya existe, integrado al core | Parcial: `PublicController::storeOrder` crea Order canónico con project_id/canal, precio re-resuelto, stock por ledger | `PublicController:626-773` | sin test de checkout | Checkout de computienda **no envía `shipping_cost` ni `coupon_code`** (total guardado ≠ mostrado) y **no enlaza `client_id`** (venta invisible en 360/CRM); datos de pago de GABDE pendientes | **PARTIAL** |
| **F10 Attribution + Smart QR** | Bloqueada por volumen (ADR-005) | No iniciada, por diseño | — | — | Correctamente diferida | **NOT VERIFIED** (no aplica) |
| **F11 Portal del Cliente** | Seguro, en producción | Sin vía de acceso indebido ni escalada: token CSPRNG 48, índice único, lookup exacto, `findOrFail` colgado del cliente, escrituras del servidor, CSRF activo, default seguro | `PortalClienteController`, `PortalClienteTest` (6) | 6 verdes | Sin revocación a null ni expiración; sin auditoría de generación; **GET sin throttle**; sin idempotencia (doble submit); `abort_unless(is_active)` sin test; endurecimientos menores (etiqueta vs id, `$project->products()`) | **PASS WITH DEBT** |

## A. Hallazgos críticos

1. **`APP_DEBUG=true` con `APP_ENV=production` en ARIN.** Cualquier 500 (incl. los `abort`/`firstOrFail` de la puerta pública `/c/{token}`) sirve stack trace a un anónimo. Verificado leyendo `.env` de ARIN. **No se toca `.env` sin tu orden.**
2. **`DELETE /projects/{project}` solo exige pertenencia.** `ProjectController::destroy` → `authorizeProject()` comprueba membership, no permiso. Un rol `solo_lectura` puede borrar el negocio entero. `routes/web.php:93`, `ProjectController.php:280`.
3. **Retiro de `/f/{slug}` cosmético.** 34 rutas de escritura (POS, pedidos, cotizaciones, comprobantes, baja SUNAT, clientes) siguen vivas con solo `facturacion.auth` (clave de sesión, sin `can:`) → un miembro sin permisos opera todo por esa puerta. Aislamiento entre empresas intacto; el bypass es del RBAC dentro del mismo negocio.

## B. Diferencias entre lo declarado y lo encontrado

- "Portales fusionados / f-slug retirado" → **34 rutas vivas sin RBAC**; el retiro es de login+dashboard.
- "Customer 360 agrega las fuentes canónicas" → **3 de 5 fuentes vacías** (Invoice/GuiaRemision `client_id`, SalesInteraction sin escritor).
- "Menú único gobernado por permisos" → 3 pantallas de ajustes protegidas en lectura **solo por ocultar el menú**.
- "Impersonación auditada" → auditada al entrar, **sin salida, sin límite, sin rastro de fin**.
- "Checkout integrado" → no propaga envío ni cupón ni cliente.
- Falsos positivos de los auditores, descartados tras verificación directa: rifas NO es cross-tenant en escritura (`RifaVenta` tiene `HasProjectScope`, binding 404); `logistics.ver/editar` SÍ existen en producción; `/api/pagos` no es anónimo (requiere copilot_token y filtra por project_id).

## C. Deuda técnica escondida (destapada, no declarada)

- Fuga de LECTURA cross-tenant en reportes de rifa (`BotInstance` sin scope en `getProjectIds`).
- `DarDeBajaEnSunat:61` sin `allProjects()` (latente: hoy corre en consola con cola `database`).
- 7 de 9 creadores de `Order` no descuentan stock (bot WA, extensión, delivery, conversión de cotización, `OrderController::store`, portal cliente).
- Dos cálculos de deuda divergentes entre Cobranza y la ficha 360.
- `convertirPortal` numera comprobantes fuera de transacción (riesgo de colisión de correlativo).
- Claves de pago (`payment_cci_*`, `pickup_*`, `payment_*_on`) solo escribibles desde el Constructor tras feature flag.

## D. Riesgos antes de F10

- F10 (atribución/Smart QR) se apoya en pedidos con origen fiable. Hoy el pedido web no enlaza cliente y el checkout pierde envío/cupón: **la atribución nacería sobre datos incompletos**.
- La fuga de rifa y el `APP_DEBUG` son superficie pública abierta: conviene cerrarlas antes de exponer más puertas (Smart QR es otra puerta anónima).

## E. Tests que faltan

- Aislamiento real (fila/endpoint) de las 13 entidades que hoy solo se prueban con `toSql()`.
- `generateOrderNumber` unicidad global; `transitionStore`; jobs fiscales.
- F11: proyecto inactivo (única palanca de apagado), token null, doble submit, orderId cross-proyecto.
- f-slug: que las 34 rutas exijan permiso (hoy ninguno lo comprueba).
- Checkout: total con envío, cupón, enlace de cliente.

## F. Estado real de producción (ARIN)

- Migraciones 43 (`import_logs`) y 44 (`portal_token`) corridas, sin pendientes.
- Salud: portada 302, panel 200, bixosales 302, `/c/{token}` inválido 404, `/f/{slug}/login` 302 → bixosales. SSL válido hasta 2026-11-04.
- MD5 de los 3 archivos de F11 idénticos local↔ARIN.
- **`APP_DEBUG=true`** (crítico). Cola `database` (no `sync`).

## G. Recomendación GO / NO-GO para F10

**NO-GO todavía.** F0–F1–F7–F8–F11 están sólidas (PASS / PASS WITH DEBT). Pero antes de abrir F10 hay que cerrar, en este orden:

1. `APP_DEBUG=false` en ARIN (1 línea, tu autorización sobre `.env`).
2. `DELETE/PUT /projects/{project}` con permiso real (no solo pertenencia).
3. RBAC en las 34 rutas de `/f/{slug}` o cortarlas de verdad.
4. Enlazar `client_id` + propagar envío/cupón en el checkout (prerequisito de una atribución honesta en F10).

Fugas de rifa, revocación de F11 y los dos cálculos de deuda pueden ir en paralelo, pero deben quedar registrados. Suite: **896 verdes, 0 skipped, 18 warnings de deprecación PHPUnit**, sin suites excluidas.
