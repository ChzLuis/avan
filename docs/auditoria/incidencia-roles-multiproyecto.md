# Incidencia: el rol del usuario es estado global, no por proyecto

**Fecha:** 2026-08-14
**Estado:** analizado, NO corregido. Requiere autorizacion antes de rediseñar.
**Relacionado:** hotfix de autorizacion en `routes/web.php` (Pedidos/Cotizaciones).

---

## 1. Las tres piezas y lo que hace cada una

| Pieza | Alcance | Que decide | Quien la escribe |
|---|---|---|---|
| `project_members.role` | por proyecto | **nada** — solo se comprueba la *existencia* de la fila | `HRController`, seeder |
| `Employee.spatie_role` | por proyecto | **el rol efectivo** | `HRController`, formulario de RRHH |
| `model_has_roles` (Spatie) | **global por usuario** | los permisos que evalua `can:` | `SetActiveProject` en cada peticion |

`ProjectMember.role` es un campo muerto: [CheckProjectMember](../../app/Http/Middleware/CheckProjectMember.php)
solo hace `->where('user_id', $userId)->exists()`. El valor `viewer` que guarda
`HRController` no se lee en ninguna parte.

## 2. El mecanismo

[SetActiveProject.php:51-67](../../app/Http/Middleware/SetActiveProject.php) —
en **cada peticion**:

```php
$roleName = $employee?->spatie_role;                 // valor POR PROYECTO
if ($project->owner_id === $user->id) $roleName = null;

if ($roleName && Role::where('name',$roleName)->exists()) {
    if ($currentRoles !== [$roleName]) $user->syncRoles([$roleName]);   // escritura GLOBAL
} elseif (...) {
    if ($user->roles->isNotEmpty()) $user->syncRoles([]);               // borra todo
}
```

Un valor con dimension de proyecto se proyecta sobre una tabla que no tiene esa
dimension. `model_has_roles` no tiene columna de proyecto: guarda `(model_id, role_id)`.

**Consecuencia estructural:** un usuario solo puede tener un rol a la vez en todo
el sistema, y ese rol lo fija la ultima peticion atendida.

## 3. Analisis de concurrencia

### 3.1 Dos pestañas, dos proyectos, roles distintos

Usuario U es `vendedor` en el proyecto A y `solo_lectura` en el B.

```
t0  Pestaña A  SetActiveProject -> syncRoles(['vendedor'])       model_has_roles = vendedor
t1  Pestaña B  SetActiveProject -> syncRoles(['solo_lectura'])   model_has_roles = solo_lectura
t2  Pestaña A  can:orders.crear  -> ?
```

En t2 la peticion de A ya tiene `$user->roles` cargada en memoria desde su propio
`syncRoles`, asi que **normalmente** evalua `vendedor` y acierta. El riesgo no es
teorico pero si condicionado: aparece cuando algo fuerza una relectura de la
relacion dentro de la misma peticion — `$user->load('roles')`, `$user->fresh()`,
una segunda instancia del modelo obtenida por consulta, o `forgetCachedPermissions()`
disparado por otra peticion concurrente. En esos casos A lee `solo_lectura` y
recibe un 403 espurio (fallo cerrado, no abierto).

**Direccion del fallo:** predominantemente hacia *menos* privilegio, no hacia mas.
Eso lo hace molesto antes que peligroso. Pero el caso inverso existe: si A es
`solo_lectura` y B es `vendedor`, una peticion de A que relea la relacion tras la
escritura de B evalua `vendedor`. **Ahi si es escalada.**

### 3.2 Trabajo asincrono

Cualquier job en cola que resuelva el usuario desde la base hereda el rol que dejo
la ultima peticion web, sin relacion con el proyecto que origino el job.

### 3.3 Coste sistemico (esto si es seguro, no probabilistico)

`syncRoles()` invoca `forgetCachedPermissions()`, que **vacia la cache de permisos
de Spatie para todos los usuarios**, no solo para el afectado. Dos pestañas
alternandose reescriben `model_has_roles` (DELETE + INSERT) y tiran la cache global
en cada peticion. Con un usuario es imperceptible; con decenas degrada a todos.

### 3.4 Perdida silenciosa de privilegios — ya presente en produccion

Consulta ejecutada sobre la base real:

```
model_has_roles:  user 5 (admin2) -> gerente
employees con spatie_role no nulo:  (ninguno)
```

`admin2` tiene `gerente` en Spatie pero **ningun** `Employee` con `spatie_role`.
En su proxima peticion al panel, la rama `elseif` ejecuta `syncRoles([])` y lo deja
sin permisos. No hay aviso: simplemente empieza a recibir 403.

El mismo mecanismo es la trampa del usuario QA: `assignRole()` por si solo no
sobrevive a la primera peticion.

## 4. Riesgo

| Aspecto | Valoracion |
|---|---|
| Explotable hoy | **No** — 4 usuarios, 1 no-superadmin, ningun usuario multiproyecto |
| Explotable al crecer | **Si** — basta un usuario con dos proyectos y roles distintos |
| Direccion del fallo | Mayoritariamente restrictiva; escalada posible si el rol del *otro* proyecto es mas alto |
| Degradacion silenciosa | **Ya ocurre** (caso `admin2`) |
| Coste de rendimiento | Real y creciente con el numero de usuarios |

## 5. Alternativas (ninguna aplicada)

**A — Roles por equipo de Spatie (correcta).** `laravel-permission` soporta
`teams`: se activa en `config/permission.php`, se añade `team_id` a
`model_has_roles` y se llama `setPermissionsTeamId($project->id)`. Los roles pasan
a ser por proyecto de forma nativa; desaparece el `syncRoles` por peticion, la race
y el vaciado de cache. Coste: migracion de la tabla pivote y revision de todo punto
que asigne roles.

**B — No persistir el rol (ligera).** Dejar de escribir en `model_has_roles`.
Resolver la autorizacion en memoria con un `Gate::before` que lea
`Employee.spatie_role` del proyecto activo y consulte los permisos de ese rol.
Spatie queda solo como catalogo de roles y permisos. Sin escrituras, sin race, sin
migracion. Coste: hay que cubrir todos los caminos que hoy dependen de
`$user->hasPermissionTo()` y de `can:`.

**C — Parche minimo.** Mantener el diseño y cachear el rol en sesion por proyecto
para reducir escrituras. **No resuelve** el problema de fondo: `model_has_roles`
sigue siendo global. Solo baja la frecuencia de la colision.

**Recomendacion:** A si se va a admitir usuarios multiproyecto; B si se prefiere
no tocar el esquema. C solo como mitigacion temporal del coste, nunca como solucion.

## 6. Hallazgo colateral

> **ACTUALIZACION 2026-08-14 — no aplica a produccion.**
> Verificado contra ARIN: `orders.descuento` **si existe** en la tabla
> `permissions` y lo tienen los roles `gerente` y `vendedor`. El riesgo de
> `PermissionDoesNotExist` descrito abajo es **exclusivo de local**, donde el
> permiso nunca se sembro pese a estar en `PermissionsSeeder`.
> No se toca. Queda como sintoma de la divergencia local/produccion
> documentada en [rbac-produccion-arin.md](rbac-produccion-arin.md).

[PosController.php:112](../../app/Http/Controllers/PosController.php) y `:369`
llaman `hasPermissionTo('orders.descuento')`. Ese permiso **esta en
`PermissionsSeeder` pero no existe en la base**. Spatie lanza
`PermissionDoesNotExist` ante un permiso desconocido, y la expresion solo se evalua
cuando el usuario **no** es superadmin ni dueño del proyecto — precisamente el
perfil de un vendedor. Habria que confirmarlo con un usuario de ese perfil antes de
darlo por roto, pero el riesgo de error 500 en el POS es concreto.

Correccion previsible: sembrar el permiso, o sustituir por
`hasPermissionTo(...)` tolerante (`->can('orders.descuento')`, que devuelve `false`
en vez de lanzar).
