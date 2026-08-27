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

Estado: ACEPTADA · 2026-08-27

Contexto: `/bixoadmin` (277 rutas) es el plano de control de Eskala: licencias,
tenants, soporte. Su audiencia, ritmo y riesgo son distintos de los del cliente.

Decision: se mantiene como producto separado. No implementa CRUDs paralelos de
entidades del tenant; el soporte accede por impersonacion auditada (pendiente
de construir, Fase 3).

Consecuencias: dos productos, no siete. `AdminImportController` y
`AdminTurnosController` (operacion de tenant dentro del control plane,
detectados por la auditoria previa) deberan migrar al Workspace.

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
