# AUDITORÍA — ANTES DE LA MIGRACIÓN

> FASE 0 del plan de Perfiles y Accesos. Fotografía del sistema **antes** de
> tocar ninguna autorización. Regenerable con `generar-auditoria.py` para
> comparar el antes y el después de cada fase.

## Resumen

| Métrica | Valor |
|---|---:|
| Permisos definidos | 90 |
| Permisos aplicados en ruta o controlador | 72 |
| Permisos que no restringen nada | 18 |
| Permisos de nomenclatura legacy (inglesa) | 21 |
| Roles definidos | 13 |
| Roles con usuarios asignados | 3 |
| Roles sin ningún usuario | 10 |
| Acciones del panel que modifican datos | 235 |
| ...con autorización | 232 |
| ...**sin autorización** | 3 |
| Proyectos activos | 7 |

## 1. Mapa de permisos

`Ruta`/`Código` indican dónde se comprueba de verdad. Un permiso sin ninguna
de las dos marcas aparece en la pantalla de Roles pero no restringe nada.

| Permiso | Estado | Ruta | Código | Roles | Destino propuesto |
|---|---|:--:|:--:|--:|---|
| `agenda.crear` | ACTIVO | x |  | 3 | Agenda / Trabajar |
| `agenda.editar` | ACTIVO | x |  | 2 | Agenda / Trabajar |
| `agenda.eliminar` | ACTIVO | x |  | 2 | Agenda / Administrar |
| `agenda.ver` | ACTIVO | x |  | 4 | Agenda / Ver |
| `attendance.fichar` | ACTIVO | x |  | 4 | Personal / Trabajar |
| `attendance.ver` | ACTIVO | x |  | 4 | Personal / Ver |
| `caja.abrir` | ACTIVO | x |  | 1 | Caja / Trabajar |
| `caja.cerrar` | ACTIVO | x |  | 1 | Caja / Trabajar |
| `caja.movimiento` | ACTIVO | x |  | 1 | Caja / Trabajar |
| `caja.ver` | ACTIVO | x |  | 4 | Caja / Ver |
| `catalog-integrations.manage` | ACTIVO | x |  | 1 | Catálogo / Administrar |
| `catalog-integrations.sync` | ACTIVO | x |  | 1 | Catálogo / Trabajar |
| `catalog-integrations.view` | ACTIVO | x |  | 1 | Catálogo / Ver |
| `catalog-integrations.view-history` | ACTIVO | x |  | 1 | **SIN DESTINO — decidir** |
| `catalog.crear` | ACTIVO | x |  | 3 | Catálogo / Trabajar |
| `catalog.editar` | ACTIVO | x |  | 3 | Catálogo / Trabajar |
| `catalog.eliminar` | ACTIVO | x | x | 3 | Catálogo / Administrar |
| `catalog.importar` | ACTIVO | x |  | 3 | Catálogo / Trabajar |
| `catalog.resenas` | ACTIVO | x |  | 1 | **SIN DESTINO — decidir** |
| `catalog.ver` | ACTIVO | x |  | 6 | Catálogo / Ver |
| `clients.crear` | ACTIVO | x |  | 4 | Clientes / Trabajar |
| `clients.editar` | ACTIVO | x |  | 4 | Clientes / Trabajar |
| `clients.eliminar` | ACTIVO | x |  | 2 | Clientes / Administrar |
| `clients.ver` | ACTIVO | x |  | 7 | Clientes / Ver |
| `hr.crear` | ACTIVO | x |  | 3 | Personal / Trabajar |
| `hr.editar` | ACTIVO | x |  | 3 | Personal / Trabajar |
| `hr.eliminar` | ACTIVO | x |  | 2 | Personal / Administrar |
| `hr.ver` | ACTIVO | x |  | 4 | Personal / Ver |
| `invoices.anular` | ACTIVO | x |  | 3 | Facturación / Administrar |
| `invoices.crear` | ACTIVO | x |  | 4 | Facturación / Trabajar |
| `invoices.editar` | ACTIVO | x |  | 3 | Facturación / Trabajar |
| `invoices.ver` | ACTIVO | x |  | 5 | Facturación / Ver |
| `manage-clients` | ACTIVO | x |  | 3 | Clientes / Administrar |
| `manage-logistics` | ACTIVO | x |  | 5 | Pedidos / Administrar |
| `manage-orders` | ACTIVO | x | x | 3 | Pedidos / Administrar |
| `manage-quotes` | ACTIVO | x |  | 3 | Cotizaciones / Administrar |
| `manage-settings` | ACTIVO | x | x | 2 | Configuración / Administrar |
| `mapa.editar` | ACTIVO | x |  | 1 | **SIN DESTINO — decidir** |
| `mapa.ver` | ACTIVO | x |  | 2 | **SIN DESTINO — decidir** |
| `orders.cancelar` | ACTIVO |  | x | 3 | Pedidos / Administrar |
| `orders.crear` | ACTIVO | x | x | 4 | Pedidos / Trabajar |
| `orders.descuento` | ACTIVO |  | x | 2 | Pedidos / Administrar |
| `orders.editar` | ACTIVO | x |  | 3 | Pedidos / Trabajar |
| `orders.eliminar` | ACTIVO | x |  | 2 | Pedidos / Administrar |
| `orders.ver` | ACTIVO | x |  | 8 | Pedidos / Ver |
| `payments.aprobar` | ACTIVO | x |  | 2 | Cobros / Administrar |
| `payments.rechazar` | ACTIVO | x |  | 2 | Cobros / Administrar |
| `payments.ver` | ACTIVO | x |  | 4 | Cobros / Ver |
| `pos.usar` | ACTIVO | x |  | 4 | Punto de venta / Trabajar |
| `proveedores.editar` | ACTIVO | x |  | 2 | Inventario / Trabajar |
| `proveedores.ver` | ACTIVO | x |  | 3 | Inventario / Ver |
| `quotes.crear` | ACTIVO | x |  | 4 | Cotizaciones / Trabajar |
| `quotes.editar` | ACTIVO | x |  | 4 | Cotizaciones / Trabajar |
| `quotes.eliminar` | ACTIVO | x |  | 2 | Cotizaciones / Administrar |
| `quotes.ver` | ACTIVO | x |  | 7 | Cotizaciones / Ver |
| `reports.ver` | ACTIVO | x |  | 9 | **SIN DESTINO — decidir** |
| `rifas.cancelar` | ACTIVO | x |  | 1 | **SIN DESTINO — decidir** |
| `rifas.validar` | ACTIVO | x |  | 1 | **SIN DESTINO — decidir** |
| `rifas.ver` | ACTIVO | x |  | 1 | **SIN DESTINO — decidir** |
| `roles.gestionar` | ACTIVO | x |  | 1 | Configuración / Administrar |
| `roles.ver` | ACTIVO | x |  | 2 | Configuración / Ver |
| `settings.catalogos` | ACTIVO | x |  | 2 | **SIN DESTINO — decidir** |
| `settings.diseno` | ACTIVO | x |  | 2 | **SIN DESTINO — decidir** |
| `settings.negocio` | ACTIVO | x |  | 2 | **SIN DESTINO — decidir** |
| `settings.pagos` | ACTIVO | x | x | 2 | **SIN DESTINO — decidir** |
| `settings.qr` | ACTIVO | x |  | 2 | **SIN DESTINO — decidir** |
| `tickets.eliminar` | ACTIVO | x |  | 1 | **SIN DESTINO — decidir** |
| `tickets.ver` | ACTIVO | x |  | 2 | **SIN DESTINO — decidir** |
| `view-clients` | ACTIVO | x |  | 4 | Clientes / Ver |
| `view-logistics` | ACTIVO | x |  | 5 | Pedidos / Ver |
| `view-orders` | ACTIVO | x |  | 5 | Pedidos / Ver |
| `view-quotes` | ACTIVO | x |  | 4 | Cotizaciones / Ver |
| `attendance.editar` | PENDIENTE DE CONECTAR |  |  | 3 | Personal / Trabajar |
| `create-products` | LEGACY |  |  | 3 | Catálogo / Trabajar |
| `delete-products` | LEGACY |  |  | 2 | Catálogo / Administrar |
| `edit-products` | LEGACY |  |  | 3 | Catálogo / Trabajar |
| `inventory.editar` | PENDIENTE DE CONECTAR |  |  | 2 | Inventario / Trabajar |
| `inventory.ver` | PENDIENTE DE CONECTAR |  |  | 3 | Inventario / Ver |
| `manage-agenda` | LEGACY |  |  | 2 | Agenda / Administrar |
| `manage-hr` | LEGACY |  |  | 2 | Personal / Administrar |
| `manage-members` | LEGACY |  |  | 2 | Configuración / Administrar |
| `manage-modules` | LEGACY |  |  | 2 | Configuración / Administrar |
| `manage-requests` | LEGACY |  |  | 2 | **SIN DESTINO — decidir** |
| `reports.exportar` | PENDIENTE DE CONECTAR |  |  | 3 | **SIN DESTINO — decidir** |
| `settings.editar` | PENDIENTE DE CONECTAR |  |  | 1 | Configuración / Trabajar |
| `settings.ver` | PENDIENTE DE CONECTAR |  |  | 2 | Configuración / Ver |
| `view-agenda` | LEGACY |  |  | 4 | Agenda / Ver |
| `view-catalog` | LEGACY |  |  | 5 | Catálogo / Ver |
| `view-hr` | LEGACY |  |  | 2 | Personal / Ver |
| `view-requests` | LEGACY |  |  | 3 | **SIN DESTINO — decidir** |

## 2. Mapa de roles

| Rol | Permisos | Usuarios | Observación |
|---|--:|--:|---|
| `revendedor` | 11 | 10 | ok |
| `gerente` | 67 | 9 | 2 legacy; 5 sin efecto |
| `qa_lectura` | 4 | 1 | ok |
| `admin` | 67 | 0 | sin usuarios; 21 legacy; 16 sin efecto |
| `owner` | 21 | 0 | sin usuarios; **construido solo con permisos legacy: no funciona fuera de pedidos/cotizaciones/clientes**; 12 sin efecto |
| `vendedor` | 21 | 0 | sin usuarios |
| `solo_lectura` | 15 | 0 | sin usuarios; 1 sin efecto |
| `almacen` | 13 | 0 | sin usuarios; 2 legacy; 2 sin efecto |
| `contador` | 13 | 0 | sin usuarios; 1 sin efecto |
| `comercial` | 10 | 0 | sin usuarios; **construido solo con permisos legacy: no funciona fuera de pedidos/cotizaciones/clientes**; 4 sin efecto |
| `rrhh` | 8 | 0 | sin usuarios; 1 sin efecto |
| `operaciones` | 6 | 0 | sin usuarios; **construido solo con permisos legacy: no funciona fuera de pedidos/cotizaciones/clientes**; 3 sin efecto |
| `logistica` | 4 | 0 | sin usuarios; **construido solo con permisos legacy: no funciona fuera de pedidos/cotizaciones/clientes**; 1 sin efecto |

## 3. Alcance por proyecto

Los roles **no pertenecen a ningún proyecto**: la tabla `roles` no tiene
`project_id`. El alcance por negocio se simula en `SetActiveProject`, que en
cada petición hace `syncRoles()` con el rol de la ficha de empleado del
proyecto activo — y `syncRoles([])` si no hay ficha, borrando los roles
globales del usuario. Ver la sección de riesgos.

| Proyecto | Empleados activos | Miembros | Roles en uso |
|---|--:|--:|---|
| Baby Toncito | 1 | 1 | gerente |
| CORPORACION MEGAHOGAR | 2 | 2 | gerente |
| Distribuidores GABDE | 1 | 1 | gerente |
| ELECTRO JARA | 1 | 1 | gerente |
| Eskala | 0 | 0 | - |
| MARKET HUACHO EXPRESS | 2 | 2 | gerente |
| TECSIST SOLUTION S.A.C. | 2 | 2 | gerente,qa_lectura |

## 4. Acciones que modifican datos SIN autorización

Total: **3**. Basta con ser miembro del proyecto.

| Área | Acciones abiertas |
|---|--:|
| {portal} | 2 |
| get-projects | 1 |

<details><summary>Listado completo</summary>

| Verbo | Ruta | Controlador |
|---|---|---|
| POST | `bixosales/get-projects` | AuthController@getProjects |
| POST | `portal/{portal}/forgot-password` | PasswordResetPortalController@sendReset |
| POST | `portal/{portal}/reset-password` | PasswordResetPortalController@updatePassword |

</details>

## 5. Acciones que modifican datos CON autorización

| Verbo | Ruta | Permiso exigido |
|---|---|---|
| POST | `admin/demos/{demo}/cancel` | solo superadmin |
| POST | `admin/demos/{demo}/extend` | solo superadmin |
| POST | `admin/imports/clients` | solo superadmin |
| POST | `admin/imports/employees` | solo superadmin |
| POST | `admin/imports/products` | solo superadmin |
| POST | `admin/licencias/configuracion` | solo superadmin |
| POST | `admin/licencias/inactivas/cerrar` | solo superadmin |
| POST | `admin/licencias/sesion/cerrar` | solo superadmin |
| POST | `admin/licencias/usuario/{user}/cerrar` | solo superadmin |
| PATCH | `admin/licencias/usuario/{user}/tipo` | solo superadmin |
| PATCH | `admin/projects/{project}/modules` | solo superadmin |
| PATCH | `admin/projects/{project}/subdomain` | solo superadmin |
| PATCH | `admin/projects/{project}/toggle` | solo superadmin |
| POST | `admin/projects/{project}/turnos/bulk` | solo superadmin |
| POST | `admin/projects/{project}/turnos/{employee}/save` | solo superadmin |
| POST | `admin/settings` | solo superadmin |
| POST | `admin/users/{user}/reset-password` | solo superadmin |
| PATCH | `admin/users/{user}/toggle-admin` | solo superadmin |
| POST | `bixoadmin/appointments` | `agenda.crear` |
| PATCH/PUT | `bixoadmin/appointments/{appointment}` | `agenda.editar` |
| DELETE | `bixoadmin/appointments/{appointment}` | `agenda.editar` |
| POST | `bixoadmin/bots-flow/{flow}` | `settings.negocio` |
| DELETE | `bixoadmin/bots-flow/{flow}` | `settings.negocio` |
| POST | `bixoadmin/bots-flow/{flow}/test` | `settings.negocio` |
| POST | `bixoadmin/bots/config` | `settings.negocio` |
| POST | `bixoadmin/bots/control` | `settings.negocio` |
| POST | `bixoadmin/bots/flow` | `settings.negocio` |
| POST | `bixoadmin/bots/flow/import-json` | `settings.negocio` |
| POST | `bixoadmin/bots/instances` | `settings.negocio` |
| DELETE | `bixoadmin/bots/instances/{bot}` | `settings.negocio` |
| POST | `bixoadmin/bots/reset-session` | `settings.negocio` |
| POST | `bixoadmin/bots/states` | `settings.negocio` |
| PUT | `bixoadmin/bots/states/{state}` | `settings.negocio` |
| DELETE | `bixoadmin/bots/states/{state}` | `settings.negocio` |
| POST | `bixoadmin/bots/states/{state}/move` | `settings.negocio` |
| POST | `bixoadmin/bots/transitions` | `settings.negocio` |
| DELETE | `bixoadmin/bots/transitions/{transition}` | `settings.negocio` |
| POST | `bixoadmin/bots/upload-image` | `settings.negocio` |
| POST | `bixoadmin/catalog-integrations` | `catalog-integrations.view, catalog-integrations.manage` |
| PUT | `bixoadmin/catalog-integrations/{integration}` | `catalog-integrations.view, catalog-integrations.manage` |
| DELETE | `bixoadmin/catalog-integrations/{integration}` | `catalog-integrations.view, catalog-integrations.manage` |
| POST | `bixoadmin/catalog-integrations/{integration}/sync` | `catalog-integrations.view, catalog-integrations.sync` |
| POST | `bixoadmin/catalog-integrations/{integration}/test` | `catalog-integrations.view, catalog-integrations.manage` |
| POST | `bixoadmin/catalogs` | `settings.catalogos` |
| PUT | `bixoadmin/catalogs/{catalog}` | `settings.catalogos` |
| DELETE | `bixoadmin/catalogs/{catalog}` | `settings.catalogos` |
| POST | `bixoadmin/catalogs/{catalog}/values` | `settings.catalogos` |
| PUT | `bixoadmin/catalogs/{catalog}/values/{value}` | `settings.catalogos` |
| DELETE | `bixoadmin/catalogs/{catalog}/values/{value}` | `settings.catalogos` |
| POST | `bixoadmin/categories` | `catalog.crear` |
| POST | `bixoadmin/categories/import` | `catalog.importar` |
| POST | `bixoadmin/categories/reorder` | `catalog.editar` |
| PATCH/PUT | `bixoadmin/categories/{category}` | `catalog.editar` |
| DELETE | `bixoadmin/categories/{category}` | `catalog.eliminar` |
| POST | `bixoadmin/certificados` | `invoices.editar` |
| PUT | `bixoadmin/certificados/{certificado}` | `invoices.editar` |
| DELETE | `bixoadmin/certificados/{certificado}` | `invoices.editar` |
| POST | `bixoadmin/clients` | `clients.crear` |
| PATCH/PUT | `bixoadmin/clients/{client}` | `clients.editar` |
| DELETE | `bixoadmin/clients/{client}` | `clients.eliminar` |
| PATCH | `bixoadmin/clients/{client}/stage` | `clients.editar` |
| POST | `bixoadmin/combos` | `catalog.editar` |
| PUT | `bixoadmin/combos/{combo}` | `catalog.editar` |
| DELETE | `bixoadmin/combos/{combo}` | `catalog.eliminar` |
| PATCH | `bixoadmin/combos/{combo}/toggle` | `catalog.editar` |
| POST | `bixoadmin/company/groups` | `settings.negocio` |
| PUT | `bixoadmin/company/groups/{userGroup}` | `settings.negocio` |
| DELETE | `bixoadmin/company/groups/{userGroup}` | `settings.negocio` |
| POST | `bixoadmin/company/proveedores` | `proveedores.editar` |
| POST | `bixoadmin/company/proveedores/import` | `proveedores.editar` |
| PUT | `bixoadmin/company/proveedores/{proveedor}` | `proveedores.editar` |
| DELETE | `bixoadmin/company/proveedores/{proveedor}` | `proveedores.editar` |
| POST | `bixoadmin/company/sedes` | `settings.negocio` |
| PUT | `bixoadmin/company/sedes/{sede}` | `settings.negocio` |
| DELETE | `bixoadmin/company/sedes/{sede}` | `settings.negocio` |
| POST | `bixoadmin/copilot` | `reports.ver` |
| POST | `bixoadmin/coupons` | `catalog.editar` |
| DELETE | `bixoadmin/coupons/{id}` | `catalog.eliminar` |
| PATCH | `bixoadmin/coupons/{id}/toggle` | `catalog.editar` |
| POST | `bixoadmin/dashboard-comercial/meta` | `settings.negocio` |
| POST | `bixoadmin/hr/asistencia` | `attendance.ver` |
| POST | `bixoadmin/hr/asistencia/check-in` | `attendance.fichar` |
| POST | `bixoadmin/hr/asistencia/check-out` | `attendance.fichar` |
| POST | `bixoadmin/hr/employees` | `hr.crear` |
| PUT | `bixoadmin/hr/employees/{employee}` | `hr.editar` |
| DELETE | `bixoadmin/hr/employees/{employee}` | `hr.eliminar` |
| POST | `bixoadmin/inventario/movimiento` | `catalog.editar` |
| POST | `bixoadmin/products` | `catalog.crear` |
| POST | `bixoadmin/products/bulk-action` | `catalog.editar` |
| POST | `bixoadmin/products/import` | `catalog.importar` |
| DELETE | `bixoadmin/products/purge-all` | `catalog.eliminar` |
| POST | `bixoadmin/products/reorder` | `catalog.editar` |
| DELETE | `bixoadmin/products/reviews/{id}` | `catalog.resenas` |
| PATCH | `bixoadmin/products/reviews/{id}/approve` | `catalog.resenas` |
| PATCH/PUT | `bixoadmin/products/{product}` | `catalog.editar` |
| DELETE | `bixoadmin/products/{product}` | `catalog.eliminar` |
| POST | `bixoadmin/products/{product}/duplicate` | `catalog.crear` |
| POST | `bixoadmin/products/{product}/images` | `catalog.editar` |
| DELETE | `bixoadmin/products/{product}/images/{image}` | `catalog.editar` |
| PATCH | `bixoadmin/products/{product}/images/{image}/main` | `catalog.editar` |
| POST | `bixoadmin/promotions` | `catalog.editar` |
| PUT | `bixoadmin/promotions/{promotion}` | `catalog.editar` |
| DELETE | `bixoadmin/promotions/{promotion}` | `catalog.eliminar` |
| PATCH | `bixoadmin/promotions/{promotion}/toggle` | `catalog.editar` |
| POST | `bixoadmin/proposals` | `quotes.crear` |
| PUT | `bixoadmin/proposals/{proposal}` | `quotes.editar` |
| DELETE | `bixoadmin/proposals/{proposal}` | `quotes.eliminar` |
| POST | `bixoadmin/roles` | `roles.gestionar` |
| PUT | `bixoadmin/roles/{role}` | `roles.gestionar` |
| DELETE | `bixoadmin/roles/{role}` | `roles.gestionar` |
| POST | `bixoadmin/services` | `catalog.crear` |
| POST | `bixoadmin/services/import` | `catalog.importar` |
| POST | `bixoadmin/services/reorder` | `catalog.editar` |
| PATCH/PUT | `bixoadmin/services/{service}` | `catalog.editar` |
| DELETE | `bixoadmin/services/{service}` | `catalog.eliminar` |
| POST | `bixoadmin/settings` | `settings.negocio` |
| POST | `bixoadmin/settings/builder/catalog/bulk` | `settings.diseno` |
| POST | `bixoadmin/settings/builder/category-photo` | `settings.diseno` |
| POST | `bixoadmin/settings/builder/copy` | `settings.diseno` |
| POST | `bixoadmin/settings/builder/descartar-borrador` | `settings.diseno` |
| POST | `bixoadmin/settings/builder/design-preset` | `settings.diseno` |
| POST | `bixoadmin/settings/builder/draft/settings` | `settings.diseno` |
| POST | `bixoadmin/settings/builder/icons/assign` | `settings.diseno` |
| POST | `bixoadmin/settings/builder/publish` | `settings.diseno` |
| POST | `bixoadmin/settings/canales` | `settings.catalogos` |
| DELETE | `bixoadmin/settings/canales/{canal}` | `settings.catalogos` |
| POST | `bixoadmin/settings/catalog-profiles` | `settings.catalogos` |
| POST | `bixoadmin/settings/catalog-profiles/feature` | `settings.catalogos` |
| POST | `bixoadmin/settings/catalog-profiles/quick` | `settings.catalogos` |
| POST | `bixoadmin/settings/catalog-profiles/reorder` | `settings.catalogos` |
| PUT | `bixoadmin/settings/catalog-profiles/{id}` | `settings.catalogos` |
| DELETE | `bixoadmin/settings/catalog-profiles/{id}` | `settings.catalogos` |
| POST | `bixoadmin/settings/design` | `settings.diseno` |
| POST | `bixoadmin/settings/design-templates` | `settings.diseno` |
| POST | `bixoadmin/settings/design-templates/import` | `settings.diseno` |
| PUT | `bixoadmin/settings/design-templates/{id}` | `settings.diseno` |
| POST | `bixoadmin/settings/design-templates/{id}/apply` | `settings.diseno` |
| POST | `bixoadmin/settings/design-templates/{id}/duplicate` | `settings.diseno` |
| POST | `bixoadmin/settings/design-templates/{id}/restore/{versionNumber}` | `settings.diseno` |
| POST | `bixoadmin/settings/design-templates/{id}/toggle` | `settings.diseno` |
| POST | `bixoadmin/settings/design-templates/{id}/version` | `settings.diseno` |
| POST | `bixoadmin/settings/design/apply-project-template` | `settings.diseno` |
| POST | `bixoadmin/settings/design/apply-template` | `settings.diseno` |
| POST | `bixoadmin/settings/design/project-templates` | `settings.diseno` |
| PUT | `bixoadmin/settings/design/project-templates/{id}` | `settings.diseno` |
| DELETE | `bixoadmin/settings/design/project-templates/{id}` | `settings.diseno` |
| PATCH | `bixoadmin/settings/experience/complaints/{id}` | `settings.negocio` |
| POST | `bixoadmin/settings/experience/home/publish-all` | `settings.diseno` |
| POST | `bixoadmin/settings/experience/home/reorder` | `settings.diseno` |
| POST | `bixoadmin/settings/experience/home/{component}` | `settings.diseno` |
| DELETE | `bixoadmin/settings/experience/home/{component}/draft` | `settings.diseno` |
| POST | `bixoadmin/settings/experience/home/{component}/publish` | `settings.diseno` |
| POST | `bixoadmin/settings/experience/home/{component}/state` | `settings.diseno` |
| POST | `bixoadmin/settings/experience/page` | `settings.diseno` |
| POST | `bixoadmin/settings/experience/popup` | `settings.diseno` |
| POST | `bixoadmin/settings/experience/section` | `settings.diseno` |
| DELETE | `bixoadmin/settings/experience/section/{id}` | `settings.diseno` |
| POST | `bixoadmin/settings/flow` | `settings.negocio` |
| POST | `bixoadmin/settings/flow/diagram` | `settings.negocio` |
| POST | `bixoadmin/settings/modules` | `settings.negocio` |
| POST | `bixoadmin/settings/payments` | `settings.pagos` |
| POST | `bixoadmin/settings/qr` | `settings.qr` |
| POST | `bixoadmin/settings/seo` | `settings.negocio` |
| POST | `bixoadmin/settings/storefront/header` | `settings.diseno` |
| POST | `bixoadmin/settings/storefront/menu/items` | `settings.diseno` |
| PUT | `bixoadmin/settings/storefront/menu/items/{item}` | `settings.diseno` |
| DELETE | `bixoadmin/settings/storefront/menu/items/{item}` | `settings.diseno` |
| POST | `bixoadmin/settings/storefront/menu/reorder` | `settings.diseno` |
| POST | `bixoadmin/settings/storefront/publish` | `settings.diseno` |
| POST | `bixoadmin/settings/upload-logo` | `settings.diseno` |
| POST | `bixoadmin/settings/{target}/modules` | `settings.negocio` |
| PATCH | `bixoadmin/settings/{target}/toggle` | `settings.negocio` |
| POST | `bixosales/caja/abrir` | `caja.abrir` |
| POST | `bixosales/caja/{caja}/cerrar` | `caja.cerrar` |
| POST | `bixosales/caja/{caja}/movimiento` | `caja.movimiento` |
| POST | `bixosales/clientes` | `clients.crear|manage-clients` |
| PUT | `bixosales/clientes/{client}` | `clients.editar|manage-clients` |
| DELETE | `bixosales/clientes/{client}` | `clients.eliminar|manage-clients` |
| POST | `bixosales/cotizaciones` | `quotes.crear|manage-quotes` |
| PUT | `bixosales/cotizaciones/{quote}` | `quotes.editar|manage-quotes` |
| DELETE | `bixosales/cotizaciones/{quote}` | `quotes.eliminar|manage-quotes` |
| POST | `bixosales/cotizaciones/{quote}/convertir-pedido` | `quotes.editar|manage-quotes` |
| POST | `bixosales/cotizaciones/{quote}/duplicate` | `quotes.crear|manage-quotes` |
| PUT | `bixosales/cotizaciones/{quote}/full` | `quotes.editar|manage-quotes` |
| POST | `bixosales/cotizaciones/{quote}/seen` | `quotes.ver|view-quotes` |
| POST | `bixosales/cotizaciones/{quote}/send` | `quotes.editar|manage-quotes` |
| POST | `bixosales/delivery` | `manage-logistics` |
| PUT | `bixosales/delivery/{order}/status` | `manage-logistics` |
| POST | `bixosales/facturas` | `invoices.crear` |
| PUT | `bixosales/facturas/{invoice}` | `invoices.editar` |
| DELETE | `bixosales/facturas/{invoice}` | `invoices.anular` |
| POST | `bixosales/facturas/{invoice}/sunat` | `invoices.crear` |
| POST | `bixosales/mapa/maps` | `mapa.editar` |
| POST | `bixosales/mapa/maps/{map}/objects` | `mapa.editar` |
| PUT | `bixosales/mapa/objects/{object}` | `mapa.editar` |
| DELETE | `bixosales/mapa/objects/{object}` | `mapa.editar` |
| POST | `bixosales/mapa/objects/{object}/alerts` | `mapa.editar` |
| DELETE | `bixosales/mapa/objects/{object}/alerts` | `mapa.editar` |
| PATCH | `bixosales/mapa/objects/{object}/amount` | `mapa.editar` |
| PATCH | `bixosales/mapa/objects/{object}/move` | `mapa.editar` |
| POST | `bixosales/mapa/objects/{object}/requests` | `mapa.editar` |
| PATCH | `bixosales/mapa/objects/{object}/responsible` | `mapa.editar` |
| PATCH | `bixosales/mapa/objects/{object}/status` | `mapa.editar` |
| POST | `bixosales/pagos/aprobar` | `payments.aprobar` |
| POST | `bixosales/pagos/pendientes` | `payments.ver` |
| POST | `bixosales/pagos/rechazar` | `payments.rechazar` |
| POST | `bixosales/pedidos` | `orders.crear|manage-orders` |
| POST | `bixosales/pedidos-bot/nuevo-manual` | `rifas.validar` |
| POST | `bixosales/pedidos-bot/{venta}/cancelar` | `rifas.cancelar` |
| POST | `bixosales/pedidos-bot/{venta}/editar` | `rifas.validar` |
| POST | `bixosales/pedidos-bot/{venta}/eliminar` | `rifas.cancelar` |
| POST | `bixosales/pedidos-bot/{venta}/enviar` | `rifas.validar` |
| POST | `bixosales/pedidos-bot/{venta}/enviar-membresia` | `rifas.validar` |
| POST | `bixosales/pedidos-bot/{venta}/recordar` | `rifas.validar` |
| POST | `bixosales/pedidos-bot/{venta}/validar` | `rifas.validar` |
| PUT | `bixosales/pedidos/{order}` | `orders.editar|manage-orders` |
| DELETE | `bixosales/pedidos/{order}` | `orders.eliminar|manage-orders` |
| PATCH | `bixosales/pedidos/{order}/kitchen` | `orders.editar|manage-logistics` |
| POST | `bixosales/pedidos/{order}/laundry-status` | `orders.editar|manage-logistics` |
| POST | `bixosales/pedidos/{order}/pay` | `orders.editar|manage-orders` |
| POST | `bixosales/pedidos/{order}/wa-action` | `orders.editar|manage-orders` |
| POST | `bixosales/pedidos/{order}/wa-delivery` | `orders.editar|manage-logistics` |
| POST | `bixosales/pedidos/{order}/wa-sent` | `orders.editar|manage-orders` |
| POST | `bixosales/pos` | `pos.usar` |
| POST | `bixosales/pos/cotizar` | `pos.usar` |
| POST | `bixosales/reservas` | `agenda.crear` |
| PUT | `bixosales/reservas/{appointment}` | `agenda.editar` |
| DELETE | `bixosales/reservas/{appointment}` | `agenda.eliminar` |
| POST | `bixosales/revendedor/catalogo` | `pos.usar` |
| POST | `bixosales/revendedor/precio` | `pos.usar` |
| POST | `bixosales/tickets-manuales/eliminar` | `tickets.eliminar` |
| POST | `bixosales/woo/sync` | `catalog.importar` |

## 6. Tabla de migración preliminar

Propuesta automática por prefijo y acción. **Las filas SIN DESTINO exigen
decisión manual** antes de la fase 5.

| Permiso antiguo | Destino propuesto | Estado |
|---|---|---|
| `catalog-integrations.view-history` | **SIN DESTINO — decidir** | ACTIVO |
| `catalog.resenas` | **SIN DESTINO — decidir** | ACTIVO |
| `manage-requests` | **SIN DESTINO — decidir** | LEGACY |
| `mapa.editar` | **SIN DESTINO — decidir** | ACTIVO |
| `mapa.ver` | **SIN DESTINO — decidir** | ACTIVO |
| `reports.exportar` | **SIN DESTINO — decidir** | PENDIENTE DE CONECTAR |
| `reports.ver` | **SIN DESTINO — decidir** | ACTIVO |
| `rifas.cancelar` | **SIN DESTINO — decidir** | ACTIVO |
| `rifas.validar` | **SIN DESTINO — decidir** | ACTIVO |
| `rifas.ver` | **SIN DESTINO — decidir** | ACTIVO |
| `settings.catalogos` | **SIN DESTINO — decidir** | ACTIVO |
| `settings.diseno` | **SIN DESTINO — decidir** | ACTIVO |
| `settings.negocio` | **SIN DESTINO — decidir** | ACTIVO |
| `settings.pagos` | **SIN DESTINO — decidir** | ACTIVO |
| `settings.qr` | **SIN DESTINO — decidir** | ACTIVO |
| `tickets.eliminar` | **SIN DESTINO — decidir** | ACTIVO |
| `tickets.ver` | **SIN DESTINO — decidir** | ACTIVO |
| `view-requests` | **SIN DESTINO — decidir** | LEGACY |
| `agenda.crear` | Agenda / Trabajar | ACTIVO |
| `agenda.editar` | Agenda / Trabajar | ACTIVO |
| `agenda.eliminar` | Agenda / Administrar | ACTIVO |
| `agenda.ver` | Agenda / Ver | ACTIVO |
| `attendance.editar` | Personal / Trabajar | PENDIENTE DE CONECTAR |
| `attendance.fichar` | Personal / Trabajar | ACTIVO |
| `attendance.ver` | Personal / Ver | ACTIVO |
| `caja.abrir` | Caja / Trabajar | ACTIVO |
| `caja.cerrar` | Caja / Trabajar | ACTIVO |
| `caja.movimiento` | Caja / Trabajar | ACTIVO |
| `caja.ver` | Caja / Ver | ACTIVO |
| `catalog-integrations.manage` | Catálogo / Administrar | ACTIVO |
| `catalog-integrations.sync` | Catálogo / Trabajar | ACTIVO |
| `catalog-integrations.view` | Catálogo / Ver | ACTIVO |
| `catalog.crear` | Catálogo / Trabajar | ACTIVO |
| `catalog.editar` | Catálogo / Trabajar | ACTIVO |
| `catalog.eliminar` | Catálogo / Administrar | ACTIVO |
| `catalog.importar` | Catálogo / Trabajar | ACTIVO |
| `catalog.ver` | Catálogo / Ver | ACTIVO |
| `clients.crear` | Clientes / Trabajar | ACTIVO |
| `clients.editar` | Clientes / Trabajar | ACTIVO |
| `clients.eliminar` | Clientes / Administrar | ACTIVO |
| `clients.ver` | Clientes / Ver | ACTIVO |
| `create-products` | Catálogo / Trabajar | LEGACY |
| `delete-products` | Catálogo / Administrar | LEGACY |
| `edit-products` | Catálogo / Trabajar | LEGACY |
| `hr.crear` | Personal / Trabajar | ACTIVO |
| `hr.editar` | Personal / Trabajar | ACTIVO |
| `hr.eliminar` | Personal / Administrar | ACTIVO |
| `hr.ver` | Personal / Ver | ACTIVO |
| `inventory.editar` | Inventario / Trabajar | PENDIENTE DE CONECTAR |
| `inventory.ver` | Inventario / Ver | PENDIENTE DE CONECTAR |
| `invoices.anular` | Facturación / Administrar | ACTIVO |
| `invoices.crear` | Facturación / Trabajar | ACTIVO |
| `invoices.editar` | Facturación / Trabajar | ACTIVO |
| `invoices.ver` | Facturación / Ver | ACTIVO |
| `manage-agenda` | Agenda / Administrar | LEGACY |
| `manage-clients` | Clientes / Administrar | ACTIVO |
| `manage-hr` | Personal / Administrar | LEGACY |
| `manage-logistics` | Pedidos / Administrar | ACTIVO |
| `manage-members` | Configuración / Administrar | LEGACY |
| `manage-modules` | Configuración / Administrar | LEGACY |
| `manage-orders` | Pedidos / Administrar | ACTIVO |
| `manage-quotes` | Cotizaciones / Administrar | ACTIVO |
| `manage-settings` | Configuración / Administrar | ACTIVO |
| `orders.cancelar` | Pedidos / Administrar | ACTIVO |
| `orders.crear` | Pedidos / Trabajar | ACTIVO |
| `orders.descuento` | Pedidos / Administrar | ACTIVO |
| `orders.editar` | Pedidos / Trabajar | ACTIVO |
| `orders.eliminar` | Pedidos / Administrar | ACTIVO |
| `orders.ver` | Pedidos / Ver | ACTIVO |
| `payments.aprobar` | Cobros / Administrar | ACTIVO |
| `payments.rechazar` | Cobros / Administrar | ACTIVO |
| `payments.ver` | Cobros / Ver | ACTIVO |
| `pos.usar` | Punto de venta / Trabajar | ACTIVO |
| `proveedores.editar` | Inventario / Trabajar | ACTIVO |
| `proveedores.ver` | Inventario / Ver | ACTIVO |
| `quotes.crear` | Cotizaciones / Trabajar | ACTIVO |
| `quotes.editar` | Cotizaciones / Trabajar | ACTIVO |
| `quotes.eliminar` | Cotizaciones / Administrar | ACTIVO |
| `quotes.ver` | Cotizaciones / Ver | ACTIVO |
| `roles.gestionar` | Configuración / Administrar | ACTIVO |
| `roles.ver` | Configuración / Ver | ACTIVO |
| `settings.editar` | Configuración / Trabajar | PENDIENTE DE CONECTAR |
| `settings.ver` | Configuración / Ver | PENDIENTE DE CONECTAR |
| `view-agenda` | Agenda / Ver | LEGACY |
| `view-catalog` | Catálogo / Ver | LEGACY |
| `view-clients` | Clientes / Ver | ACTIVO |
| `view-hr` | Personal / Ver | LEGACY |
| `view-logistics` | Pedidos / Ver | ACTIVO |
| `view-orders` | Pedidos / Ver | ACTIVO |
| `view-quotes` | Cotizaciones / Ver | ACTIVO |

Permisos sin destino automático: **18**

