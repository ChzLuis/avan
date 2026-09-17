# Personas

**Qué va aquí:** HR (empleados), Attendance (asistencia y comisiones), WorkSchedule (horarios), UserGroup (grupos), RolePermission (perfiles y permisos del proyecto).

**Qué NO va aquí:** `Employee` y `User` son de Core (los usa toda la plataforma); las licencias, de Control.

**Estado:** movido el 2026-09-17 (primer módulo de la mudanza; ensayo del método).

```
Controllers/  HRController, AttendanceController, UserGroupController, RolePermissionController
Models/       Attendance, WorkSchedule, UserGroup
Views/        hr/employees, roles/index   -> view('personas::hr.employees')
```

Las vistas se registran solas por `App\Modules\ModulosServiceProvider` (espacio
de nombres = carpeta en minúsculas). Las clases resuelven por PSR-4 sin registro.

**Quién lo usa desde fuera** (solo por `use`, sin lógica): `Employee`
(`attendances()`, `schedules()`), `Project` (`userGroups()`),
`Admin\AdminTurnosController` (`WorkSchedule`), `routes/web.php`.

**Red:** `tests/Feature/PersonasModuloTest` — pinta las pantallas con sus vistas
bajo `personas::` y vigila que nadie vuelva a crear o importar estas clases
por su ruta vieja.

**Deuda conocida (anterior a la mudanza):** `AttendanceController` devuelve
`personas::hr.attendance` y `personas::hr.comisiones`, y esas dos vistas **no
existen** ni en local ni en ARIN: asistencia y comisiones responden 500 desde
antes de mover nada. Hay que escribirlas o retirar las rutas.

**Para desplegar este módulo a ARIN:** subir los archivos nuevos, **borrar los
viejos** en `app/Http/Controllers/`, `app/Models/` y `resources/views/{hr,roles}`,
y correr `composer dump-autoload -o` (ARIN tiene el classmap optimizado; sin
eso las clases movidas no se encuentran). `deploy.py` regenera la caché de
rutas pero no hace ninguna de las otras dos.
