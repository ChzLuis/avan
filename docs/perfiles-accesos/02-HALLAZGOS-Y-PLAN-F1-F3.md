# FASE 0 — Hallazgos estructurales y plan de las Fases 1 a 3

> Complemento cualitativo de `01-AUDITORIA-ANTES.md`, que contiene los datos.
> Aquí va lo que los números no dicen y la definición exacta de qué tocar después.
> **Durante la Fase 0 no se modificó ninguna autorización.**

### Trabajo previo que ya existía

Antes de esta auditoría el repo ya contenía dos análisis directamente
relacionados, del 14 de agosto de 2026. **No se repiten aquí; se dan por buenos
y se citan:**

* [`docs/auditoria/rbac-produccion-arin.md`](../auditoria/rbac-produccion-arin.md)
  — establece la regla de auditar **contra ARIN** porque local miente (local tenía
  ~80 permisos y ARIN 90; `gerente` 49 vs 65). Esta Fase 0 sigue esa regla: todos
  los datos de `01-AUDITORIA-ANTES.md` salen de producción. También documenta que
  `orders.descuento` **sí** existe en ARIN y que la propiedad del pedido
  (`orders.created_by`) solo la rellena el POS, lo que impide hoy autorizar «solo
  mis pedidos».
* [`docs/auditoria/incidencia-roles-multiproyecto.md`](../auditoria/incidencia-roles-multiproyecto.md)
  — analiza el problema de `SetActiveProject` descrito abajo y plantea las tres
  alternativas de solución. Es la entrada natural a la Fase 7.

---

## A. Hallazgos estructurales

### A1. El aislamiento por proyecto es destructivo — y ahora hay más gente expuesta

El mecanismo ya estaba documentado en
[`incidencia-roles-multiproyecto.md`](../auditoria/incidencia-roles-multiproyecto.md):
`app/Http/Middleware/SetActiveProject.php` reescribe **los roles globales del
usuario en cada petición**, según la ficha de empleado del proyecto activo, y
ejecuta `syncRoles([])` —borrado total— si no hay ficha.

Lo que aporta esta auditoría son los números de hoy:

* Aquel análisis contaba «4 usuarios, 1 no superadmin». Hoy hay **20 usuarios con
  rol asignado**: 9 `gerente`, 10 `revendedor` y 1 `qa_lectura`.
* Los **10 `revendedor` no tienen ficha de empleado en ningún proyecto**. Cualquier
  petición suya con un proyecto activo dispara la rama `syncRoles([])` y les borra
  el rol de forma permanente. Es degradación silenciosa, no hipotética.
* **Sigue sin haber ningún usuario en dos proyectos** (verificado en `employees` y
  en `project_members`), así que la condición de carrera descrita entonces **aún no
  es explotable**. Esa es la única razón por la que no ha estallado.
* Cada `GET` sigue provocando escrituras en `model_has_roles`.

Para la Fase 7, el documento previo ya dejó tres alternativas evaluadas —
**A)** Teams de Spatie (`team_id` en la tabla pivote), **B)** no persistir el rol y
resolverlo en memoria con un `Gate::before` que lea `Employee.spatie_role`, y
**C)** parche de caché, que no resuelve nada de fondo. Su recomendación es **A** si
se van a admitir usuarios multiproyecto, **B** si se prefiere no tocar el esquema.
Esa decisión sigue pendiente y es un requisito de entrada a la Fase 7.

### A2. No existe ninguna Policy

`app/Policies/` no existe y no hay `Gate::define`. Toda la autorización vive en
el middleware de ruta más un puñado de `->can()` sueltos en controladores. El
efecto práctico es el contrario de «denegar por defecto»: **una ruta nueva nace
abierta**, y solo se cierra si alguien se acuerda de añadirle el middleware. Los
108 casos abiertos son exactamente eso.

### A3. `authorizeProject()` se usa como si fuera autorización

`app/Http/Controllers/Controller.php` solo comprueba pertenencia al proyecto:

```php
$isMember = $user->is_superadmin || $user->id === $project->owner_id
            || $project->members()->where('user_id', $user->id)->exists();
abort_unless($isMember, 403);
```

Responde a «¿es de este negocio?», no a «¿puede hacer esto?». Es el punto 3.2 del
plan —permiso y proyecto son condiciones independientes— y hoy están confundidas.

### A4. Una asignación de rol corrupta

`model_has_roles` tiene 1 fila con `model_type = 'AppModelsUser'` (sin barras
invertidas) frente a las 20 correctas con `App\Models\User`. Spatie nunca la lee,
así que es un huérfano inofensivo, pero conviene limpiarlo antes de migrar para
que los recuentos cuadren.

### A5. Cinco áreas del sistema no están en el modelo objetivo

18 permisos no encajan en las 12 áreas del plan. Requieren decisión explícita
antes de la Fase 5:

| Permisos | Decisión pendiente |
|---|---|
| `mapa.ver` · `mapa.editar` | ¿Área propia «Mapa/Reparto» o dentro de Pedidos? |
| `rifas.ver` · `rifas.validar` · `rifas.cancelar` | ¿Área propia o funcionalidad de un solo cliente que se retira? |
| `tickets.ver` · `tickets.eliminar` | ¿Soporte es un área o pertenece a Clientes? |
| `reports.ver` · `reports.exportar` | Reportes atraviesa todas las áreas: ¿nivel transversal o permiso aparte? |
| `manage-requests` · `view-requests` | Solicitudes internas/externas: no hay área equivalente. |
| `settings.negocio` · `settings.diseno` · `settings.pagos` · `settings.catalogos` · `settings.qr` | Reparto entre Configuración/Trabajar y Configuración/Administrar. |
| `catalog.resenas` | ¿Catálogo/Trabajar o Catálogo/Administrar? |
| `catalog-integrations.view-history` | Historial de sincronización: ¿Catálogo/Ver? |

**No se decide en Fase 0 a propósito.** El plan exige justificación documentada
por permiso, y estas son decisiones de producto, no de código.

### A6. El riesgo real de la Fase 3 está medido

`settings.editar` solo lo tiene el rol `admin`, y **ningún usuario tiene ese rol**.
Los 9 usuarios reales del panel son `gerente`. Si la Fase 3 cierra configuración
con `settings.editar`, deja a todos fuera. Hay que usar los permisos granulares
(`settings.negocio`, `settings.diseno`, `settings.pagos`, `settings.catalogos`,
`settings.qr`), que `gerente` sí tiene, aunque hoy no restrinjan nada.

---

## B. Plan preciso — Fase 1

**Objetivo:** que la descripción del rol se guarde. No se toca ningún permiso.

### Migración

`database/migrations/XXXX_add_description_to_roles_table.php`

* añade `roles.description` (`string`, 200, nullable) con guarda `Schema::hasColumn`;
* `down()` reversible (la columna es nueva y no la usa nadie más).

> Atención a la deriva de migraciones ya documentada: comprobar que ARIN no tenga
> ya una columna equivalente creada a mano antes de desplegar.

### Archivos

| Archivo | Cambio |
|---|---|
| `app/Http/Controllers/RolePermissionController.php` | `store()` y `update()` guardan `description`; `index()` la devuelve en vez de `''` |
| `resources/views/roles/index.blade.php` | Ninguno: ya envía y pinta el campo |

### Verificación previa

Spatie define el modelo `Role` con `guarded = []`, pero hay que confirmarlo en la
versión instalada antes de asumir que `description` es asignable en masa.

### Criterio de cierre

Crear un rol con descripción, recargar y comprobar que persiste. Test de
integración que lo afirme.

---

## C. Plan preciso — Fase 2

**Objetivo:** clasificar los 90 permisos. **Cero cambios de código.**

La clasificación automática ya está en `01-AUDITORIA-ANTES.md` §1 y §6:
67 ACTIVO, 21 LEGACY, y el resto PENDIENTE DE CONECTAR / HUÉRFANO.

Trabajo humano pendiente: resolver las 18 filas **SIN DESTINO** del apartado A5.

### Entregable

`03-CLASIFICACION-PERMISOS.md` con la tabla del plan
(`| Permiso actual | Dónde se usa | Rol | Sustitución futura | Estado |`),
partiendo de la generada y añadiendo la decisión de las 18.

---

## D. Plan preciso — Fase 3

**Objetivo:** 0 acciones mutables sensibles sin autorización. Es la fase de
seguridad y la de mayor riesgo de dejar gente fuera.

### Orden y volumen reales

| # | Módulo | Acciones abiertas | Permiso provisional propuesto |
|---|---|--:|---|
| 1 | `settings` | 57 | `settings.negocio` / `.diseno` / `.pagos` / `.catalogos` / `.qr` según la pantalla; **nunca** `settings.editar` |
| 2 | `bots` + `bots-flow` | 17 | Sin permiso equivalente: crear uno o restringir a dueño/superadmin |
| 3 | `company` (sedes, grupos) | 6 | Sin equivalente: decidir |
| 4 | `catalogs` (listas maestras) | 6 | `settings.catalogos` |
| 5 | `combos` + `promotions` | 8 | `catalog.editar` / `catalog.eliminar` |
| 6 | `coupons` | 3 | Sin equivalente: decidir |
| 7 | `certificados` | 3 | Sin equivalente: decidir |
| 8 | `proposals` | 3 | `quotes.*` (son documentos comerciales) |
| 9 | `copilot`, `dashboard-comercial`, `get-projects`, `{portal}` | 5 | Revisar caso a caso |

### Método obligatorio por módulo

1. Tabla `| Acción | Quién puede hoy | Quién debería poder | Permiso provisional |`.
2. Consultar qué roles tienen ese permiso y qué usuarios reales tienen ese rol.
3. Aplicar.
4. Ejecutar los tests de autorización.
5. Regenerar `01-AUDITORIA-ANTES.md` y comparar el contador de acciones abiertas.

### Archivos previsibles

* `routes/web.php` — la mayoría de los cierres.
* Controladores donde el permiso dependa del contenido de la petición, como ya
  ocurre en `ProductController@bulkAction` con `catalog.eliminar`.
* `tests/Feature/` — un archivo por módulo cerrado, siguiendo el patrón de
  `CatalogAuthorizationTest` y `PanelAuthorizationTest`.

---

## E. Cómo repetir esta auditoría

```bash
D=<directorio temporal>
php artisan route:list --json > "$D/rutas.json"
python deploy.py --sql "<consulta permisos>"  > "$D/permisos.tsv"
python deploy.py --sql "<consulta roles>"     > "$D/roles.tsv"
python deploy.py --sql "<consulta proyectos>" > "$D/proyectos.tsv"
python docs/perfiles-accesos/generar-auditoria.py "$D" > docs/perfiles-accesos/01-AUDITORIA-ANTES.md
```

Las consultas exactas están en la cabecera de `generar-auditoria.py`. Tras cada
fase, regenerar sobre una copia con otro nombre y comparar con `diff`: ese es el
ANTES vs DESPUÉS que exige el plan.
