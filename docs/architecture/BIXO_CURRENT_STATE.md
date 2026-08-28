# Estado actual de la reestructuracion BIXO

Ultima actualizacion: 2026-08-28 · commit `b8a0195` (+ validación arquitectónica integral)

## Validación arquitectónica 2026-08-28 (resumen)

La forma general de la plataforma quedó como se acordó, pero la auditoría de
arquitectura (5 auditores, `BIXO_VALIDACION_ARQUITECTONICA.md`) encontró 5
FALLAS: WaBotController cross-tenant (RISK-008), fix de rifa incompleto
(RISK-009), deuda con 3 fuentes (RISK-010), stock de variante sin Kardex
(RISK-011), numeración fiscal fuera de transacción (RISK-012). Más entitlement
no exigido en /bixosales y Customer 360 con 3/5 fuentes vacías.
**GO/NO-GO F10: NO-GO** hasta cerrar esas fallas. Suite 903 verde.

---


## En que fase estamos

**CHECKPOINT F0–F11 VALIDADO** (2026-08-27). F0–F8 ejecutadas, F9 verificada,
F11 construida y en ARIN. Auditoría independiente hecha: veredictos y evidencia
en `BIXO_VALIDACION_F0_F11.md`. Recomendación **NO-GO para F10** hasta cerrar
4 puntos (APP_DEBUG en prod, DELETE /projects con permiso, RBAC en /f/{slug},
client_id+envío/cupón en checkout).

## Que se termino

- **F0–F8**: reestructura (seguridad multiempresa fail-closed, sesión única,
  impersonación auditada, menú por permisos, Sales consolidado, Customer 360,
  POS/Finance canónicos). Commits `15ecf80`…`ab3a36a`.
- **F11 Portal del Cliente**: enlace `/c/{token}` para repetir pedidos, en
  producción (commit `7add45a`). Seguro; deuda en revocación/throttle.
- **Validación F0–F11**: 896 tests verdes (0 skipped), ARIN sano, y una lista
  de deuda/hallazgos real (ver documento de validación).

Todas las cifras de abajo son conteos reales, no estimaciones.

### Superficie de la plataforma

| Medida | Valor |
|---|---|
| Rutas registradas | 675 |
| Controladores | 85 |
| Modelos Eloquent | 83 |
| Tests (suite completa) | 874 passed |

### Puertas de entrada (7)

| Portal | Rutas | Sesion que usa |
|---|---:|---|
| `/bixoadmin` — control SaaS de Eskala | 277 | `auth` + `superadmin` |
| `/` — panel del negocio | 210 | `active_project_id` |
| `/bixosales` — portal comercial | 117 | `comercial_project_id` |
| `/f/{slug}` — portal de facturacion | 38 | `facturacion_auth.{slug}` |
| `/bixocrm` — bots y conversaciones | 26 | propia |
| `/b/{slug}` — enlace del cliente | 6 | token, sin login |
| `/r/{slug}` — catalogo de revendedor | 1 | publico |

**Tres sesiones distintas conviven** para el mismo usuario: `active_project_id`,
`comercial_project_id` y `facturacion_auth.{slug}`.

### Duplicidad real

19 controladores sirven a mas de un portal. La logica **no** esta duplicada; lo
que se multiplico son las puertas y los menus.

| Controlador | Portales |
|---|---|
| `InvoiceController` | panel · sales · f-slug |
| `OrderController` | panel · sales · f-slug |
| `QuoteController` | panel · sales · f-slug |
| `PosController` | panel · sales · f-slug |
| `NotaController` | panel · sales · f-slug |
| `ClientController` | admin · sales · f-slug |
| `ResellerController` | panel · sales · revendedor |
| `DashboardController` | sales · f-slug |

### Aislamiento multiempresa

| Medida | Valor |
|---|---:|
| Modelos con `HasProjectScope` (scope global) | 13 de 83 |
| Modelos con `project_id` pero sin scope | 49 |
| Metodos que reciben un modelo por la URL | 174 |
| — protegidos por scope global | 77 |
| — protegidos por validacion manual | 78 |
| — **sin proteccion visible** | **19** |

De esos 19: 13 pertenecen a `/bixoadmin` (superadmin, acceso global legitimo)
y **5 son `RifaVenta`, con riesgo real de acceso cruzado**.

### Permisos: dos generaciones conviviendo

- 64 permisos con el estilo objetivo `dominio.accion`.
- 9 permisos legacy verbo-recurso: `manage-clients`, `manage-logistics`,
  `manage-orders`, `manage-quotes`, `manage-settings`, `view-clients`,
  `view-logistics`, `view-orders`, `view-quotes`.

El middleware `project.can` acepta alternativas `A|B` con semantica ANY, lo que
permite la convivencia mientras dure la migracion.

### Volumen real del negocio (produccion, 2026-08-27)

| Medida | Valor |
|---|---:|
| Negocios activos | 6 |
| Empleados con acceso | 9 |
| — con rol `gerente` | 8 |
| — cuenta de pruebas | 1 |
| Pedidos ultimos 30 dias | 16 |
| Cotizaciones ultimos 30 dias | 9 |
| Comprobantes ultimos 30 dias | 0 |

Canales de venta en 90 dias: cotizacion 7 · web 6 · WhatsApp 3 · POS 3.

**Consecuencia de producto:** las capacidades que dependen de volumen
(atribucion multicanal, deteccion de clientes inactivos, analitica de QR)
no tienen datos suficientes todavia y mostrarian tableros vacios.

## Que esta en progreso

Nada. Fase 0 cerrada.

## Que falta

Fases 1 a 13 del roadmap. La Fase 1 (seguridad multiempresa) tiene un hallazgo
concreto y accionable que no depende de reestructurar nada: RISK-001.

## Que esta bloqueado

Nada por dependencias tecnicas. Hay tres decisiones de producto pendientes
listadas en `BIXO_HANDOFF.md`.

## Bugs conocidos

- ~~RISK-001~~ `RifaVenta` — **corregido 2026-08-27** con scope + 4 tests.
- **TD-001** El scope global es *fail-open*: sin sesion de proyecto no filtra.
- **TD-003** Columna `products.brand_catalog_id` sin tabla `brand_catalogs`.

## Tests en rojo

Ninguno. 874 passed al commit `2a64c15`.

## Riesgos

Ver `BIXO_RISKS.md`.

## Proximo paso exacto

Corregir RISK-001 en `RifaController` (5 metodos) y añadir el primer test de
aislamiento multiempresa por entidad, que servira de plantilla para el resto.
