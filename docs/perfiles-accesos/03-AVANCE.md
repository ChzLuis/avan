# Avance — Perfiles y Accesos

Una entrada por fase, en el formato acordado (§10 del plan).

---

## FASE 0 — Congelar y auditar · **TERMINADA** · 2026-08-16

**Qué se hizo.** Fotografía del sistema de permisos contra producción, sin tocar
ninguna autorización. Durante toda la fase estuvo activo un bloqueo de escritura
fuera de `docs/`, de modo que la regla «no modificar todavía» quedó garantizada
por el propio entorno.

**Archivos creados.** `00-PLAN.md`, `01-AUDITORIA-ANTES.md`,
`02-HALLAZGOS-Y-PLAN-F1-F3.md`, `generar-auditoria.py`. Ningún archivo de código.

**Base de datos.** Ninguna migración. Solo lecturas contra ARIN.

**Pruebas.** No aplica: no hubo cambios de comportamiento.

**Antes / Después.** Sin cambios. Se establece la línea base:
90 permisos · 67 aplicados · 23 sin efecto · 21 legacy · 13 roles · 3 con usuarios ·
235 acciones mutables · 108 sin autorización · 7 proyectos.

**Pendiente.** Dos decisiones de producto bloquean fases posteriores: el destino
de los 18 permisos sin área (`mapa.*`, `rifas.*`, `tickets.*`, `reports.*`,
`manage-requests`, y el reparto de `settings.*`), y la alternativa de la Fase 7.

**Riesgos encontrados.**

* Los 10 usuarios con rol `revendedor` no tienen ficha de empleado, y
  `SetActiveProject` borra los roles de quien no la tiene. Degradación silenciosa
  en curso, no hipotética.
* No existe ninguna Policy: una ruta nueva nace abierta.
* `settings.editar` solo lo tiene el rol `admin`, que **ningún usuario tiene**.
  Usarlo en la Fase 3 dejaría fuera a los 9 gerentes.

---

## FASE 1 — Descripción de los perfiles · **TERMINADA** · 2026-08-16

**Qué se hizo.** La pantalla de Roles tenía un campo «descripción», el navegador
lo enviaba y el controlador lo validaba, pero la tabla `roles` no tenía la
columna: se descartaba en cada guardado. Ahora se guarda, se devuelve y se pinta.
No se modificó ningún permiso.

**Archivos modificados.**

| Archivo | Cambio |
|---|---|
| `database/migrations/2026_08_16_170000_add_description_to_roles_table.php` | Nuevo. `roles.description` (`varchar(200)`, nullable) con guarda `hasColumn` y `down()` reversible |
| `app/Http/Controllers/RolePermissionController.php` | `index()` devuelve la descripción real en vez de `''`; `store()` la guarda tras `firstOrCreate`; `update()` solo la toca si viene en la petición |
| `tests/Feature/RoleDescriptionTest.php` | Nuevo. 5 casos |

**Base de datos.** Una migración, aplicada en local y en ARIN. Añade una columna
nullable: no altera datos existentes y es reversible.

**Pruebas.** `RoleDescriptionTest` 5/5. Suite completa de autorización
**87 pruebas, todas en verde** (`PanelAuthorizationTest`,
`CatalogAuthorizationTest`, `OrdersQuotesAuthorizationTest`,
`InventoryLedgerTest`, `RoleDescriptionTest`). Verificación adicional en
producción: escritura y lectura de la columna sobre el rol `operaciones`
(sin usuarios), revertida a `NULL` al terminar.

**Antes / Después.**

| | Antes | Después |
|---|---|---|
| Guardar un perfil con descripción | Se aceptaba y se descartaba | Se guarda |
| Recargar la pantalla | Descripción siempre vacía | Muestra lo guardado |
| Editar sin enviar el campo | — | Conserva la descripción anterior |
| Guardar sobre un nombre existente | La descripción se ignoraba | Se actualiza |

**Pendiente.** Redactar las descripciones definitivas de las seis plantillas: eso
es Fase 9, no esta.

**Riesgos encontrados.** Ninguno nuevo. Se verificó antes de desplegar que ARIN no
tuviera ya una columna equivalente creada a mano, por la deriva de esquema
documentada en el proyecto.

**Detalle técnico que evitó un fallo.** `Role::firstOrCreate(['name' => ...])` no
aplica atributos cuando el rol ya existe. Guardar la descripción dentro de esa
llamada habría reproducido el mismo bug —silenciosamente— al reeditar un perfil
existente. Por eso se asigna y se guarda aparte, y hay un test que lo fija.

---

## FASE 2 — Clasificar los 90 permisos · **TERMINADA** · 2026-08-16

**Qué se hizo.** Los 90 permisos quedan clasificados con destino y justificación.
Las 18 filas que no encajaban por prefijo se decidieron **con datos de producción**,
no por intuición, y las decisiones viven en el diccionario `DECISIONES` dentro de
`generar-auditoria.py`, de modo que son reproducibles y revisables de un vistazo.

| Estado | Permisos | Significado |
|---|--:|---|
| ACTIVO | 57 | Se aplica hoy y tiene destino directo |
| LEGACY | 12 | Nomenclatura inglesa; se retira en la Fase 13 |
| SUSTITUIR | 10 | Se aplica, pero cambia de nombre o nivel |
| PENDIENTE DE CONECTAR | 8 | Hay roles que lo tienen, ninguna ruta lo comprueba |
| HUÉRFANO | 2 | No protege nada que exista; se elimina |
| ELIMINAR AL FINAL | 1 | Se conserva mientras haga falta |

**El dato que desatascó las 18.** Consulta a producción: las tablas `rifas`,
`rifa_ventas`, `operational_maps`, `internal_requests`, `external_requests` y
`operational_requests` están **todas vacías**, y **`tickets` ni siquiera existe
como tabla**, pese a haber 41 rutas vivas que mencionan esos módulos.

Decisiones tomadas en consecuencia:

| Permisos | Decisión | Por qué |
|---|---|---|
| `tickets.ver` · `tickets.eliminar` | **HUÉRFANO — eliminar** | No hay tabla de tickets. Protegen algo que no existe. |
| `rifas.*` (3) | **No se les da área todavía** | Módulo sin una sola fila. Meterlos a la fuerza en las 12 áreas ensucia el modelo canónico por un módulo que quizá se retire. |
| `mapa.ver` · `mapa.editar` | Pedidos / Ver y Trabajar | El reparto es parte del pedido. `operational_maps` vacía: no urge. |
| `view-requests` · `manage-requests` | **LEGACY** | Doble motivo: nomenclatura inglesa y módulo sin uso. |
| `reports.ver` · `reports.exportar` | **Transversal, no área propia** | Un reporte no es un área: se ve lo que ya puedes ver. Mantener las 12 áreas exige resolverlo por el área del dato, no con un permiso aparte. |
| `settings.pagos` | Configuración / **Administrar** | Toca dinero: cambiar una cuenta de cobro desvía los pagos del negocio. |
| `settings.negocio` · `.diseno` · `.catalogos` · `.qr` | Configuración / **Trabajar** | Se retocan a diario y equivocarse no destruye datos. |
| `settings.editar` | ELIMINAR AL FINAL | Ningún usuario lo tiene y ninguna ruta lo usa. |
| `catalog.resenas` | Catálogo / Trabajar | Moderar es tarea diaria; borrar reseñas irá a Administrar. |
| `catalog-integrations.view-history` | Catálogo / Ver | Solo lectura. |

**Archivos.** `docs/perfiles-accesos/05-CLASIFICACION-PERMISOS.md` (generado) y
las decisiones en `generar-auditoria.py`. **Cero cambios de código de aplicación.**

**Base de datos.** Ninguna. No se creó ni se borró ningún permiso.

**Pruebas.** No aplica: esta fase no cambia comportamiento.

**Antes / Después.** Antes había 18 permisos sin destino documentado, lo que
bloqueaba la entrada a la Fase 5. Ahora **los 90 tienen decisión escrita**.

**Pendiente.** Dos decisiones son de negocio y conviene confirmarlas, aunque no
bloquean: si el **módulo de rifas** se mantiene o se retira, y si **Reportes**
se acepta como transversal (lo que evita una decimotercera área y respeta el
criterio de aceptación de 12 áreas y 36 permisos).

**Riesgos encontrados.** Ninguno: la fase no toca código. La única trampa
evitada fue forzar módulos sin uso dentro de las 12 áreas solo por cerrar la
tabla, lo que habría contaminado el modelo canónico desde el primer día.

---

## FASE 4 — Modelo canónico · **TERMINADA** · 2026-08-16

**Qué se hizo.** Existen los **36 permisos canónicos**: 12 áreas × 3 niveles, con
una única convención en español (`area.nivel`). La fase es **aditiva**: ningún rol
los usa todavía, así que no cambió lo que nadie puede hacer.

**Fuente de verdad única.** `app/Support/Access.php` define áreas, niveles,
nombres, etiquetas, descripciones y jerarquía. La migración, el Gate, las pruebas
y las futuras pantallas leen de ahí. Es la respuesta directa al origen del
problema: 90 permisos inventados área por área, sin criterio común.

**La herencia no se guarda, se resuelve.** `Administrar` incluye `Trabajar` y
`Trabajar` incluye `Ver`, pero eso **no** se escribe por triplicado en la base: un
perfil guarda UNA fila por área y la equivalencia se resuelve en `Gate::before`.
Dos consecuencias buscadas:

* es **imposible** que exista «editar sin ver», la combinación que hoy sí se
  puede crear a mano en la pantalla de Roles;
* la futura pantalla de perfiles guarda un selector por área, no 36 casillas.

El Gate añadido **solo puede conceder**: devuelve `null` cuando no aplica, nunca
`false`, así que no puede denegar nada que antes funcionara. Hay un test que lo
comprueba contra un permiso legacy.

**Archivos creados/modificados.**

| Archivo | Cambio |
|---|---|
| `app/Support/Access.php` | Nuevo. Fuente de verdad: 12 áreas, 3 niveles, 36 permisos, jerarquía y textos de ayuda |
| `database/migrations/2026_08_16_200000_create_canonical_permissions.php` | Crea los 36 de forma idempotente; el `down()` solo retira los que ningún rol use |
| `app/Providers/AppServiceProvider.php` | Regla de herencia en `Gate::before` |
| `tests/Feature/CanonicalAccessTest.php` | Nuevo. 11 casos, 189 aserciones |

**Base de datos.** Una migración. En producción los permisos pasan de 90 a
**124**: se crean 34 y se **reutilizan** `agenda.ver` y `caja.ver`, que ya
existían y son exactamente el mismo concepto. Ningún permiso borrado, ningún rol
modificado.

**Pruebas.** 11/11. Las once suites juntas: **201 pruebas, 445 aserciones, todas
en verde**.

**Antes / Después.**

| | Antes | Después |
|---|---|---|
| Convención de nombres | Dos generaciones mezcladas, español e inglés | Una sola: `area.nivel` en español |
| Definición de permisos | Repartida por rutas, seeder y vistas | Un archivo: `Access.php` |
| Herencia entre niveles | No existía: `catalog.editar` sin `catalog.ver` era posible | Garantizada por el Gate y probada en las 12 áreas |
| Ayuda contextual | No había | Cada permiso tiene etiqueta y descripción en español |
| Acceso efectivo de los usuarios | — | **Sin cambios**: 0 roles usan los nuevos permisos |

**Pendiente.** Las rutas siguen exigiendo los permisos antiguos. Cambiarlas es la
Fase 6, que necesita antes el mapeo de la Fase 5 y **no debe hacerse sin resolver
la Fase 7**: migrar los roles mientras `SetActiveProject` siga reescribiéndolos en
cada petición sería construir sobre arena.

**Riesgos encontrados.** Uno, evitado a tiempo: `hasPermissionTo()` lanza
`PermissionDoesNotExist` si el permiso no está sembrado, y el Gate de herencia lo
llama en cada comprobación. Sin protección, un entorno sin sembrar habría
reventado con 500 en toda la aplicación. Va dentro de un `try/catch` que no
concede nada y deja seguir.

---

## FASE 7 — Aislamiento por proyecto · **TERMINADA** · 2026-08-16

**Decisión: opción A, teams de Spatie.** No por preferencia. Verificado en el
paquete (`create_permission_tables.php.stub:37`): con `teams` activo, `team_id`
se añade **también a la tabla `roles`**, con único `(team_id, name, guard_name)`.
La opción B —resolver en memoria sin persistir— arreglaba el churn de
asignaciones pero dejaba las DEFINICIONES de rol globales, así que editar
«Vendedor» en MegaHogar habría seguido cambiando el de Tecsist: justo lo que la
Fase 7 prohíbe. Solo A cumple el requisito.

**Qué se hizo.** El proyecto pasa a ser el «equipo» de Spatie. Cada asignación de
perfil vive en su negocio. Desaparece el borrado global de roles.

**Archivos creados/modificados.**

| Archivo | Cambio |
|---|---|
| `database/migrations/2026_08_16_210000_enable_permission_teams.php` | Nueva. `team_id` en `roles`, `model_has_roles` y `model_has_permissions`, con relleno propio |
| `config/permission.php` | `teams => true` |
| `app/Providers/AppServiceProvider.php` | Equipo por defecto 0; los perfiles se siguen creando globales durante la transición |
| `app/Http/Middleware/SetActiveProject.php` | Fija el equipo del negocio y descarta relaciones cargadas de otro contexto |
| `app/Http/Controllers/HRController.php` | Materializa el perfil en el equipo del proyecto al guardarlo |
| `tests/Feature/PerProjectRolesTest.php` | Nueva. 5 casos |

**Por qué una migración propia y no la del paquete.** La de Spatie rellena
`team_id` con el valor fijo **1**, y aquí los proyectos son 7, 10, 16, 18, 19, 20
y 21: las 21 asignaciones habrían quedado colgando de un equipo inexistente y
todo el mundo habría perdido su perfil. La propia lo deduce de la ficha de
empleado de cada persona.

**Base de datos.** Una migración. Copia previa de `roles`, `model_has_roles`,
`model_has_permissions` y `permissions` en
`_deploy_backups/sql/permisos_antes_teams.sql`. Resultado del relleno:

| team_id | Asignaciones | Qué son |
|---|--:|---|
| 7, 10, 18, 19, 21 | 9 | Empleados reales, cada uno en su negocio |
| 0 | 12 | 10 revendedores y 2 usuarios de prueba que no pertenecen a ningún proyecto |

Los 13 perfiles siguen **globales** (`team_id` nulo). Spatie los encuentra en
cualquier negocio (`Role.php:175`), así que nadie perdió acceso.

**Despliegue en dos pasos, a propósito.** Primero la migración con `teams`
todavía en `false`: la columna existe pero Spatie la ignora y la aplicación se
comporta igual. Solo después se activó la configuración. Así no hubo ni un
instante con el código esperando algo que la base aún no tuviera.

**Pruebas.** 5 casos nuevos. Se ejecutaron **todas** las suites afectadas, no
solo las propias: 47 + 67 + 100 = **214 pruebas en verde**.

**Antes / Después.**

| | Antes | Después |
|---|---|---|
| Rol de una persona | Uno solo en todo el sistema, lo fijaba la última petición | Uno por negocio |
| Entrar a un negocio sin ficha | `syncRoles([])` borraba sus roles en TODA la plataforma | Solo se retira en ese negocio |
| Dos pestañas, dos negocios | Condición de carrera: una petición podía ejecutarse con el rol de la otra | Cada una en su equipo |
| Cambiar el perfil en RRHH | No surtía efecto hasta la siguiente petición de esa persona | Se aplica al guardar |
| Escrituras en cada `GET` | Sí | Solo cuando el perfil realmente cambia |

**Dos problemas que aparecieron al probar, y no en producción.**

* La relación `roles` cargada **antes** de fijar el equipo dejaba la comprobación
  obsoleta: el middleware creía que no había nada que sincronizar y la persona se
  quedaba sin permisos. Se descarta la relación tras fijar el equipo.
* `team_id` forma parte de la clave primaria y no admite nulos, así que cualquier
  asignación fuera de un negocio —comandos, colas, seeders— reventaba. De ahí el
  equipo por defecto 0.

**Corrección de datos.** Se materializaron los perfiles de los 9 empleados
activos en su negocio. Uno (Tony Acosta, Baby Toncito) tenía ficha de gerente sin
asignación: el sistema se la creaba en su siguiente acceso. Ahora los 9 están
coherentes de entrada.

**Pendiente.** Los perfiles siguen siendo **globales**: la infraestructura para
tenerlos por negocio ya está, pero las copias por proyecto son la Fase 9. Hasta
entonces, editar «gerente» sigue afectando a los seis negocios. Lo que ya está
resuelto es la asignación —quién tiene qué perfil en qué negocio— y el borrado
silencioso.

**Riesgos encontrados.** Un fallo en `QuoteConversionTest` («convertir a factura
con descuento da 422 y cero mutaciones»). Se comprobó apartando los cambios de
esta fase: **falla igual sin ellos**. Es anterior y ajeno a este trabajo.

---

## FASES 5 y 6 — Equivalencia legacy → canónico y migración de perfiles · **TERMINADAS** · 2026-08-16

**Qué se hizo.** Los 90 permisos antiguos tienen equivalente canónico documentado
(81 mapeados, 9 excluidos con motivo), y los 13 perfiles ya tienen **añadidos** sus
permisos canónicos equivalentes. No se retiró ninguno de los antiguos.

**Por qué aditivo y no sustitutivo.** Las rutas siguen exigiendo los permisos
viejos. Si se hubieran retirado, todo el mundo se quedaría fuera en el mismo
instante del despliegue. Con las dos generaciones conviviendo, hoy nada cambia y
mañana, cuando una ruta pase al canónico, quien podía seguirá pudiendo. Retirar
los antiguos es la Fase 13, y solo cuando nada los use.

**Archivos creados/modificados.**

| Archivo | Cambio |
|---|---|
| `app/Support/Access.php` | `MAPEO_LEGACY` (81 entradas), `traducir()` y `sinEquivalente()` |
| `database/migrations/2026_08_16_220000_migrate_roles_to_canonical_permissions.php` | Añade a cada rol sus canónicos; el `down()` los retira sin tocar los antiguos |
| `tests/Feature/LegacyMappingTest.php` | Nueva. 8 casos, 100 aserciones |

**La traducción se queda con el nivel MÁS ALTO de cada área.** Quien tenía
`catalog.ver`, `catalog.editar` y `catalog.eliminar` acaba con
`catalogo.administrar` a secas: la herencia le devuelve los otros dos y el perfil
guarda **una fila por área** en vez de tres.

**Dos decisiones de criterio.**

* `orders.descuento` no va a Pedidos sino a **Punto de venta / Administrar**: el
  descuento especial es potestad del mostrador, y así lo describe el §5.6 del plan.
* `proveedores.*` entra en **Inventario**: quien registra la entrada de mercadería
  es quien trata con el proveedor.

**Base de datos.** Una migración. Copia previa de `role_has_permissions` en
`_deploy_backups/sql/role_permissions_antes_f6.sql`. Resultado en producción:

| Perfil | Canónicos añadidos | Perfil | Canónicos añadidos |
|---|--:|---|--:|
| gerente | 14 | contador | 6 |
| vendedor | 11 | comercial · operaciones · revendedor | 5 |
| admin · solo_lectura | 10 | almacen · qa_lectura | 3 |
| owner | 7 | logistica | 2 |
| | | rrhh | 1 |

**Pruebas.** 8 casos nuevos. Entre todas las suites: **266 pruebas en verde**. La
más importante reproduce el reparto real de `vendedor`, `almacen` y `contador` de
producción y comprueba que, tras traducir, conservan exactamente sus capacidades
—ni una de más ni una de menos—.

**Antes / Después.**

| | Antes | Después |
|---|---|---|
| Equivalencia legacy → canónico | No existía | 81 mapeadas, 9 excluidas con motivo |
| Perfiles con permisos canónicos | 0 | Los 13 |
| Acceso efectivo de los usuarios | — | **Sin cambios**: las rutas siguen usando los antiguos |

**Una redundancia esperada.** `gerente` queda con `agenda.ver` **y**
`agenda.administrar`. Es porque `agenda.ver` y `caja.ver` son el mismo nombre en
las dos generaciones y las rutas todavía exigen el antiguo: retirarlo rompería la
ruta. La herencia lo hace redundante, no incorrecto, y se resuelve solo en la
Fase 13.

**Pendiente.** Cambiar las rutas para que exijan los permisos canónicos. No se
hizo aquí a propósito: es un cambio de comportamiento en 232 rutas y merece su
propio paso, módulo por módulo, con el mismo método de medir impacto antes de
aplicar.

**Riesgos encontrados.** Ninguno nuevo.

---

## FASE 3 — Cerrar las acciones sin autorización · **TERMINADA** · 2026-08-16

**Qué se hizo.** Las 108 acciones del panel que modificaban datos sin exigir
ningún permiso ahora lo exigen. Bastaba con ser miembro del proyecto para cambiar
el diseño de la tienda, los medios de pago, los bots, las sedes, los cupones o
para **desactivar el negocio entero**.

Se usaron permisos **que ya existían y que los roles reales ya tienen**, para no
dejar a nadie fuera. En particular **no** se usó `settings.editar`: solo lo tiene
el rol `admin`, que en producción no tiene ningún usuario, y habría bloqueado a
los 9 gerentes.

| Módulo | Acciones | Permiso aplicado |
|---|--:|---|
| Configuración · diseño, constructor, experiencia, menú, plantillas, logo | 39 | `settings.diseno` |
| Configuración · perfiles de catálogo y canales | 8 | `settings.catalogos` |
| Configuración · datos, SEO, flujo, módulos, libro de reclamaciones, activar/desactivar negocio | 8 | `settings.negocio` |
| Configuración · medios de pago | 1 | `settings.pagos` |
| Configuración · código QR | 1 | `settings.qr` |
| Bots de WhatsApp y sus flujos | 17 | `settings.negocio` |
| Sedes, grupos y meta comercial | 7 | `settings.negocio` |
| Listas maestras | 6 | `settings.catalogos` |
| Certificados digitales | 3 | `invoices.editar` |
| Combos, promociones y cupones | 11 | `catalog.editar` · `catalog.eliminar` |
| Propuestas | 3 | `quotes.crear` · `.editar` · `.eliminar` |
| Copiloto (no muta, pero expone cifras) | 1 | `reports.ver` |

**Archivos modificados.** `routes/web.php` (105 rutas con middleware añadido),
`tests/Feature/SettingsAuthorizationTest.php` y
`tests/Feature/ModulesAuthorizationTest.php` (nuevos).

**Base de datos.** Ninguna migración. Ningún permiso creado ni borrado.

**Pruebas.** 63 casos nuevos. Las siete suites de autorización y dominio:
**150 pruebas, todas en verde**. Cada acción se prueba dos veces: que un miembro
sin permiso recibe 403, y que quien tiene el permiso **no** recibe 403 — este
segundo es el que impide repetir el error de gatear con un permiso que nadie tiene.

**Antes / Después.**

| | Antes | Después |
|---|---|---|
| Acciones mutables sin autorización | 108 | **3** |
| Acciones mutables con autorización | 127 | 232 |
| Permisos que no restringen nada | 23 | 18 |

Las 3 restantes son de autenticación y deben seguir siendo públicas:
`bixosales/get-projects` (parte del login, con throttle) y las dos de recuperación
de contraseña del portal. Hay un test que fija que sigan abiertas.

**Pendiente.** Los permisos elegidos son **provisionales**: en la Fase 4 pasan al
modelo canónico. Tres reparaciones quedan explícitamente para entonces:

* Bots, módulos del negocio y activar/desactivar el negocio están hoy en
  `settings.negocio`, que es nivel *Trabajar*. Por su carácter destructivo
  corresponden a Configuración / **Administrar**.
* El menú solo muestra Bots al dueño y al superadmin, pero la ruta ahora admite
  también al gerente: sigue siendo mucho más estricto que antes, pero no coincide
  con la interfaz.
* `settings.builder.copy` copia una tienda desde otro proyecto: es un punto a
  revisar en la Fase 8 de aislamiento.

**Riesgos encontrados.** Ninguno nuevo. Antes de aplicar se comprobó, contra
producción, qué roles tienen cada permiso y qué usuarios reales tienen cada rol.

---

## FASE 8 — Pruebas de aislamiento entre negocios · **TERMINADA** · 2026-08-16

**Qué se hizo.** Se escribieron las pruebas que exige el plan y **el aislamiento
resiste**: no hubo que corregir nada. Es el resultado que se buscaba, no una
ausencia de trabajo.

El usuario de las pruebas tiene **todos** los permisos en su proyecto. Lo que se
comprueba no es si puede, sino si el sistema le deja alcanzar datos ajenos.

**Archivos creados.** `tests/Feature/ProjectIsolationTest.php` (23 casos).
Ningún cambio de código de aplicación.

**Base de datos.** Ninguna migración.

**Pruebas.** 23/23. Cubren los tres casos del plan y algunos más: editar y borrar
productos, categorías, servicios, clientes, pedidos, cotizaciones, proveedores y
citas de otro negocio; leer pedido, cotización y Kardex ajenos; que el listado no
mezcle datos; desactivar el negocio ajeno; cambiarle los módulos; mover su stock;
y copiar su tienda.

**Antes / Después.** Sin cambios de comportamiento. Lo que antes era una
suposición ahora está fijado por pruebas.

**Cómo resiste hoy.** Por dos mecanismos distintos:

* `HasProjectScope` — un *global scope* por `session('active_project_id')`. Solo
  lo usan **9 modelos**: Caja, CatalogIntegration, Client, Combo, Invoice, Order,
  Product, Promotion, Quote. En ellos el binding de ruta ni siquiera encuentra el
  registro ajeno (404).
* `abort_unless($x->project_id === $project->id, 403)` escrito a mano en cada
  controlador. Es lo que protege a Categorías, Servicios, Proveedores, Citas y
  todo lo demás. Se verificó uno por uno que la comprobación existe: los tests no
  pasan por casualidad.

**Pendiente.** El segundo mecanismo depende de que cada controlador se acuerde de
comprobarlo — la misma debilidad de «denegar por defecto» del hallazgo A2. La
Fase 7 debería sustituirlo por algo estructural.

**Riesgos encontrados.** Ninguno. Se revisó expresamente
`settings/builder/copy`, el único endpoint que recibe por diseño un id de
proyecto distinto del activo: exige ser dueño del proyecto origen. Queda fijado
por un test.

---

## FASE 12 — Auditoría de cambios de acceso · **TERMINADA** · 2026-08-16

**Qué se hizo.** Hasta ahora ningún cambio de acceso dejaba rastro: nadie podía
responder «¿quién le dio acceso a Caja a Pedro?». Ahora cada cambio deliberado se
registra con proyecto, autor, persona afectada, perfil, qué se amplió, qué se
recortó, fecha e IP.

**Archivos creados/modificados.**

| Archivo | Cambio |
|---|---|
| `database/migrations/2026_08_16_190000_create_access_events_table.php` | Nueva tabla `access_events` |
| `app/Models/AccessEvent.php` | Nuevo. `registrar()` y `label` legible en español |
| `app/Http/Controllers/RolePermissionController.php` | Registra crear, renombrar, cambiar permisos y eliminar perfil |
| `app/Http/Controllers/HRController.php` | Registra asignar y retirar el perfil de una persona |
| `tests/Feature/AccessAuditTest.php` | Nuevo. 8 casos |

**Base de datos.** Una migración. Tabla nueva, no altera nada existente.

**Pruebas.** 8/8. Las nueve suites juntas: **181 pruebas en verde**.

**Antes / Después.**

| | Antes | Después |
|---|---|---|
| Cambiar los permisos de un perfil | Sin rastro | `permissions_changed` con `added` y `removed` |
| Asignar un perfil a alguien | Sin rastro | `profile_assigned` con autor y afectado |
| Retirar el perfil de alguien | Sin rastro | `profile_removed` |
| Crear, renombrar o borrar perfil | Sin rastro | `role_created` · `role_renamed` · `role_deleted` |

Ejemplo de lo que produce: *«Luis asignó el perfil «Vendedor» a Pedro»* y
*«Luis cambió los permisos del perfil «Vendedor» (+1, −1)»*.

**Dos decisiones tomadas.**

* **No se audita la sincronización automática** de `SetActiveProject`. Ocurre en
  cada petición y no es decisión de nadie: registrarla inundaría la tabla y
  enterraría los cambios reales.
* **La auditoría no puede tumbar la operación.** Si el registro falla, se anota
  una advertencia en el log y la operación del usuario sigue adelante. Perder el
  asiento es malo; perder el cambio que el usuario ya creyó guardado, peor. Hay
  un test que borra la tabla y comprueba que crear un perfil sigue funcionando.

**Pendiente.** La información ya es estructurada y consultable por SQL, pero **no
hay pantalla para verla**. Esa pantalla pertenece a las fases de interfaz (14-17),
que el plan bloquea hasta cerrar seguridad, modelo canónico y aislamiento.

---

## FASE 11 — Protección del Dueño · **TERMINADA** · 2026-08-16

**Qué se hizo.** Al entrar en esta fase apareció que la premisa no se cumplía:
**los 7 negocios de producción pertenecían a `Administrator`, la cuenta de
superadmin de Eskala**. Ningún cliente era dueño del suyo, y `projects.owner_id`
solo se escribía al crear el proyecto —no había ninguna forma de cambiarlo—.
La separación del §3.4 entre plataforma BIXO y Dueño del negocio no existía en la
práctica.

Proteger «al último Dueño» sin poder nombrar a ninguno no significaba nada, así
que la fase incluye el mecanismo que le da sentido: **traspasar el negocio a su
dueño real**, con sus tres reglas protegidas.

**Archivos creados/modificados.**

| Archivo | Cambio |
|---|---|
| `app/Support/ProjectOwnership.php` | Nuevo. `transferir()`, `puedeSerRetirado()`, `exigirQueNoSeaElDueno()`, `candidatos()` |
| `app/Http/Controllers/Admin/AdminProjectController.php` | `transferOwnership()` y candidatos en `show()` |
| `app/Http/Controllers/HRController.php` | `destroy()` impide borrar la ficha del dueño |
| `routes/admin.php` | `POST /admin/projects/{project}/owner` |
| `resources/views/admin/projects/show.blade.php` | Bloque «Dueño del negocio» con distintivo ESKALA y traspaso |
| `tests/Feature/ProjectOwnershipTest.php` | Nuevo. 9 casos |

**Base de datos.** Ninguna migración: `projects.owner_id` y `project_members` ya
existían. Se reutiliza `access_events` de la Fase 12.

**Pruebas.** 9/9. Las diez suites: **190 pruebas en verde**.

**Antes / Después.**

| | Antes | Después |
|---|---|---|
| Traspasar un negocio a su dueño | Imposible: solo se fijaba al crearlo | Desde Super Admin → Proyectos |
| Quedarse sin dueño | No podía pasar porque nada lo tocaba | Bloqueado explícitamente, con mensaje |
| Borrar la ficha del dueño | Se borraba sin avisar | 422 con instrucción de traspasar primero |
| Traspaso de propiedad | — | Auditado (`owner_transferred`) |

**Tres reglas fijadas por pruebas.**

1. Un negocio siempre tiene dueño: no se puede retirar a quien lo es.
2. El nuevo dueño recibe la membresía si no la tenía — si no, no podría entrar a
   su propio negocio.
3. Ser dueño de un negocio no da absolutamente nada sobre otro.

**Pendiente / acción de negocio.** Los 7 proyectos **siguen a nombre de Eskala**.
El mecanismo existe pero el traspaso es una decisión comercial: hay que hacerlo
negocio por negocio cuando se entregue cada uno al cliente. No se tocó ningún
dato de producción.

**Riesgos encontrados.** El traspaso da control total del negocio a la persona
elegida, así que se dejó como operación **exclusiva del superadmin**, con
confirmación en la interfaz y un test que comprueba que un usuario normal no
puede ejecutarlo.
