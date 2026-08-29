# Registro de decisiones arquitectonicas (ADR)

Formato: cada decision queda aqui con contexto, alternativas y consecuencias.
Una decision aceptada no se revierte en silencio: se marca SUPERSEDED y se
enlaza la nueva.

---

### ADR-001 — Administrador es un rol, no una aplicacion

Estado: ACEPTADA · 2026-08-27

Contexto: la propuesta comercial hablaba de "BIXO Admin" como espacio propio
dentro del tenant. La auditoria midio que los 9 empleados reales son 8
"gerente" + 1 QA: no existen los perfiles que justificarian una app separada.

Decision: dentro del Workspace, "administrar" es un conjunto de permisos, no
una puerta. El menu muestra mas o menos segun rol.

Alternativas: portal Admin separado (descartada: recrea el problema de las 7
puertas con otro nombre).

Consecuencias: la fusion de menus (Fase 4-5) se diseña por permisos.

---

### ADR-002 — BIXO Control separado del Workspace

Estado: ACEPTADA · 2026-08-27 · **PRECISADA 2026-08-28**

Son DOS plataformas, no tres:

```
BIXO PLATFORM
├── 1. BIXO CONTROL   → /admin        (solo Eskala/superadmin)
│         licencias, tenants, usuarios globales, imports, demos, health
└── 2. BIXO WORKSPACE → el tenant (App Shell del negocio); Administrador = ROL
          dos CARAS de la MISMA plataforma durante la transición:
          ├── /bixoadmin  → Configuración: Mi negocio, Constructor, Catálogo
          │                 maestro (Productos), QR, SEO, APIs, usuarios/permisos,
          │                 configuración fiscal
          └── /bixosales  → Operación: Inicio, Sales, POS, Pedidos, Clientes,
                            Facturas, Cobranza, Reports
```

Corrección respecto a la redacción original: **el plano de control de Eskala es
`/admin`, NO `/bixoadmin`**. `/bixoadmin` y `/bixosales` son las dos caras del
mismo BIXO Workspace del tenant (Configuración y Operación), no dos productos ni
dos plataformas que compiten.

Decision:
- `/admin` (Control) se mantiene separado; no implementa CRUDs paralelos de
  entidades del tenant; el soporte entra por impersonacion auditada (ADR-003).
- `/bixoadmin` y `/bixosales` pueden coexistir como rutas/layouts durante la
  transición, PERO **ambos consumen los mismos dominios canónicos** — nunca
  `BixoAdminProductService ≠ BixoSalesProductService`. Una capacidad, un
  propietario; varias interfaces (ADR-003). Ejemplo verificado: la ficha maestra
  de producto (`/bixoadmin`, `Catalog\ProductController`) y la vista comercial
  (`/bixosales`, `PosController`) usan el MISMO `App\Models\Product`.
- Objetivo visual: que el usuario sienta UN solo Workspace — al entrar a
  "Configuración" se cargan rutas que hoy viven bajo `/bixoadmin`, sin sensación
  de "salir de Sales y entrar a Admin". Unificación = limpieza diferida.

Consecuencias: dos PLATAFORMAS (Control + Workspace), no siete portales ni tres
capas. `AdminImportController` y `AdminTurnosController` (operación de tenant
dentro del control plane) deben migrar al Workspace (ADR-008).

---

### ADR-003 — Una capacidad, un propietario

Estado: ACEPTADA · 2026-08-27

Contexto: la auditoria confirmo que ya es asi en el codigo: 19 controladores
sirven a varios portales sin duplicar logica, y `client_id` es el unico
identificador de cliente en los 8 modelos que lo usan.

Decision: `MODULE_OWNERSHIP.md` es la fuente oficial. Antes de crear un
modelo/servicio/tabla se consulta ese documento; si la entidad tiene dueño,
se reutiliza.

Consecuencias: prohibido crear `WorkspaceXxxController` por el hecho de que
una vista viva en el Workspace.

---

### ADR-004 — El proceso comercial es un grafo, no una cadena

Estado: ACEPTADA · 2026-08-27

Contexto: el codigo real ya lo demuestra: POS crea venta sin cotizacion,
una guia puede nacer de una factura o al reves, una factura admite varios
pagos (CxC), y el checkout web crea pedidos directos.

Decision: no forzar Quote→Order→Invoice→Payment como secuencia obligatoria.
Los vinculos son opcionales y multiples. Los estados viven separados
(`Order.status`, `Invoice.sunat_status`, `payment_status`, `baja_estado`...)
y esta prohibido inferir uno desde otro.

Consecuencias: el semaforo de documentacion de guias (ya implementado) es el
patron a seguir: estados derivados se calculan, no se almacenan duplicados.

---

### ADR-005 — Pulse y atribucion se posponen hasta tener volumen

Estado: ACEPTADA · 2026-08-27

Contexto: produccion registra 16 pedidos y 0 comprobantes en 30 dias. Un motor
de señales sin datos muestra tableros vacios y destruye confianza.

Decision: las fases 10 y 12 del roadmap (atribucion, Pulse) quedan al final y
condicionadas a volumen real. Cuando llegue, Pulse arranca determinista
(reglas: quote_stale, receivable_overdue, low_stock, sunat_rejected...) — el
Centro de Avisos actual ya es su embrion.

---

### ADR-006 — Entitlement del tenant Y permiso del usuario

Estado: ACEPTADA · 2026-08-27

Contexto: ya existe la mitad: `project_modules` gatea modulos por negocio
(middleware `module:catalog` etc.) y Spatie con teams gatea permisos por
usuario. Falta formalizar que **ambos** se evaluen siempre.

Decision: acceso efectivo = modulo habilitado AND permiso del usuario. Un
permiso nunca habilita una funcion no contratada.

Consecuencias: la navegacion unificada (Fase 4) se construye leyendo ambas
fuentes; la matriz vive en `docs/security/BIXO_PERMISSION_MATRIX.md`.

---

### ADR-007 — No big-bang: consolidar por fases con redirects

Estado: ACEPTADA · 2026-08-27

Contexto: 6 negocios operan en produccion; la plataforma tiene 874 tests en
verde que protegen el comportamiento actual.

Decision: cada fase deja la anterior funcionando; las URLs retiradas
redirigen; nada se borra hasta verificar consumidores. `/f/{slug}` sera el
primer retiro (38 rutas, 13 vistas) porque su logica ya vive en controladores
compartidos.

---

### ADR-008 — AdminImport y AdminTurnos quedan DEPRECATE en el control plane

Estado: ACEPTADA · 2026-08-27

Contexto: la auditoria previa detecto que el plano de control (`/bixoadmin`)
opera datos de tenant en dos sitios: importaciones (AdminImportController,
181 lineas) y turnos (AdminTurnosController, 111 lineas). El Workspace ya
tiene importacion propia (`products.import`, permiso `catalog.importar`).

Decision: ambos quedan DEPRECATE, no se borran hoy. Son herramientas de
soporte superadmin-only, de bajo riesgo y en uso potencial para onboarding.
Se retiran en Fase 3 cuando exista la impersonacion auditada que los
sustituye (ADR-002).

Consecuencias: prohibido añadirles funciones nuevas; toda importacion nueva
va al Workspace.

---

### ADR-009 — /f/{slug} se retira por las puertas, no por demolicion

Estado: ACEPTADA · 2026-08-27

Contexto: el portal de facturacion duplica pantallas de /bixosales con los
MISMOS controladores (Fase 0). El log de nginx no permite medir su uso real.

Decision: sus dos entradas (login y tablero) redirigen a /bixosales; las
rutas internas siguen registradas (renombradas *.legado) para no romper
referencias `route()` durante el ciclo de compatibilidad. Las 13 vistas se
borran en un ciclo posterior, tras verificar que nadie llega a ellas.

Consecuencias: el portal queda inaccesible para personas pero integro para
el codigo. Rollback = revertir dos rutas.

---

### ADR-010 — Fases 9 y 11 son producto, no reestructura; 10/12/13 esperan volumen

Estado: ACEPTADA · 2026-08-27

Contexto: al ejecutar el roadmap de corrido, Commerce-checkout (F9) y el
Portal del Cliente (F11) resultaron ser construcciones de producto nuevas,
no consolidacion de lo existente; y ADR-005 ya bloquea 10/12/13 por volumen.

Decision: la reestructura arquitectonica se declara ejecutada hasta F8.
F9 y F11 pasan al backlog de producto con su diseño ya conversado
(checkout tipo carrito; enlace con token por cliente). El unico trabajo
arquitectonico restante es la limpieza diferida: borrar vistas de f-slug,
retirar nombres legacy de permisos, y apagar el panel raiz cuando el menu
unico cubra todo.
