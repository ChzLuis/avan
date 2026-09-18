# Control

**Qué va aquí:** BIXO Control, el superadmin de Eskala: tablero, proyectos (alta, módulos contratados, subdominio, transferencia de dueño, impersonación), usuarios globales, licencias y sesiones, importaciones masivas, turnos por proyecto, ajustes globales, auditoría de accesos (`AccessEvent`) y solicitudes de demo (`DemoRequest`, con su página pública `/demo`).

**Qué NO va aquí:** productos, clientes, empleados ni pedidos de un tenant (Control no los crea: el soporte entra por impersonación auditada). `Access` (modelo canónico de permisos), `AppSetting`, `Module`/`ProjectModule` y `User` son de Core. `ProjectTemplate` es de Tienda.

**Estado:** movido el 2026-09-17 (módulo 2/8).

```
Controllers/  AdminAuth, AdminDashboard, AdminProject, AdminUser, AdminLicense,
              AdminImport, AdminSettings, AdminTurnos, Demo
Models/       AccessEvent, DemoRequest
Support/      LicenseManager
Views/        admin/{auth,dashboard,projects,users,licenses,imports,settings,turnos,demos,audit}
              demo/index
              components/admin-layout   -> <x-admin-layout> sigue igual (ver abajo)
```

Rutas en `routes/admin.php` (prefijo `/admin`, middleware `auth` + `superadmin`)
y las públicas de demo en `routes/web.php`. Vistas bajo `control::`. El layout
del superadmin es un componente anónimo que el `ModulosServiceProvider`
registra desde `Views/components/` **sin prefijo**: `<x-admin-layout>` no
cambia de nombre.

**Quién lo usa desde fuera:** `AccessEvent` es la auditoría de toda la
plataforma — lo escriben `Personas` (roles, empleados), `ProjectOwnership`
(transferencia de dueño) y la impersonación en `routes/web.php`. `LicenseManager`
lo consulta `LoginRequest` (licencias concurrentes al entrar). `DemoRequest` lo
usan `Jobs/ExpireDemos` y `Mail/DemoCreada`, que se quedan donde están: los
jobs viajan serializados por nombre de clase y moverlos rompería la cola.

**Red:** `tests/Feature/ControlModuloTest` (6): tablero, proyectos, licencias,
auditoría y `/demo` con su vista bajo `control::`; un usuario común sigue fuera;
y guardián de frontera (ruta vieja prohibida).

**Deuda conocida:** `DemoController::success` devuelve `control::demo.success`,
que no existe — pero tampoco tiene ruta: código muerto, no bug vivo.
`AdminTurnosController` edita turnos de empleados de un tenant desde Control,
contra la regla de `MODULE_OWNERSHIP`; se movió tal cual, decidir su destino
(probablemente Personas con impersonación) en otra sesión.

**Para desplegar a ARIN:** archivos nuevos + borrar los viejos
(`app/Http/Controllers/Admin/`, `DemoController.php`, `AccessEvent.php`,
`DemoRequest.php`, `LicenseManager.php`, `resources/views/{admin,demo}`,
`components/admin-layout.blade.php`) + `composer dump-autoload -o` + caché de
rutas. Desplegar junto con Personas, nunca uno sin el otro: Personas ya apunta
a `Control\Models\AccessEvent`.

**Productos (2026-09-17):** la ficha del negocio muestra los productos
(`App\Support\Productos`: CRM, Sales, Commerce, Operations) y los activa con
un clic; "Nuevo negocio" crea el negocio con su producto inicial y su dueno.
Los productos son agrupaciones de modulos: activar uno nunca apaga otro.
