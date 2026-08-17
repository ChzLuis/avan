# AUDITORÍA — ANTES DE LA MIGRACIÓN

> FASE 0 del plan de Perfiles y Accesos. Fotografía del sistema **antes** de
> tocar ninguna autorización. Regenerable con `generar-auditoria.py` para
> comparar el antes y el después de cada fase.

## Resumen

| Métrica | Valor |
|---|---:|
| Permisos definidos | 90 |
| Permisos aplicados en ruta o controlador | 67 |
| Permisos que no restringen nada | 23 |
| Permisos de nomenclatura legacy (inglesa) | 21 |
| Roles definidos | 13 |
| Roles con usuarios asignados | 3 |
| Roles sin ningún usuario | 10 |
| Acciones del panel que modifican datos | 235 |
| ...con autorización | 127 |
| ...**sin autorización** | 108 |
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
| `manage-settings` | LEGACY |  |  | 2 | Configuración / Administrar |
| `reports.exportar` | PENDIENTE DE CONECTAR |  |  | 3 | **SIN DESTINO — decidir** |
| `settings.catalogos` | PENDIENTE DE CONECTAR |  |  | 2 | **SIN DESTINO — decidir** |
| `settings.diseno` | PENDIENTE DE CONECTAR |  |  | 2 | **SIN DESTINO — decidir** |
| `settings.editar` | PENDIENTE DE CONECTAR |  |  | 1 | Configuración / Trabajar |
| `settings.negocio` | PENDIENTE DE CONECTAR |  |  | 2 | **SIN DESTINO — decidir** |
| `settings.pagos` | PENDIENTE DE CONECTAR |  |  | 2 | **SIN DESTINO — decidir** |
| `settings.ver` | PENDIENTE DE CONECTAR |  |  | 2 | Configuración / Ver |
| `view-agenda` | LEGACY |  |  | 4 | Agenda / Ver |
| `view-catalog` | LEGACY |  |  | 5 | Catálogo / Ver |
| `view-hr` | LEGACY |  |  | 2 | Personal / Ver |
| `view-requests` | LEGACY |  |  | 3 | **SIN DESTINO — decidir** |

## 2. Mapa de roles

| Rol | Permisos | Usuarios | Observación |
|---|--:|--:|---|
| `revendedor` | 11 | 10 | ok |
| `gerente` | 67 | 9 | 2 legacy; 9 sin efecto |
| `qa_lectura` | 4 | 1 | ok |
| `admin` | 67 | 0 | sin usuarios; 21 legacy; 21 sin efecto |
| `owner` | 21 | 0 | sin usuarios; **construido solo con permisos legacy: no funciona fuera de pedidos/cotizaciones/clientes**; 13 sin efecto |
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

Total: **108**. Basta con ser miembro del proyecto.

| Área | Acciones abiertas |
|---|--:|
| settings | 57 |
| bots | 14 |
| catalogs | 6 |
| company | 6 |
| combos | 4 |
| promotions | 4 |
| bots-flow | 3 |
| certificados | 3 |
| coupons | 3 |
| proposals | 3 |
| {portal} | 2 |
| copilot | 1 |
| dashboard-comercial | 1 |
| get-projects | 1 |

<details><summary>Listado completo</summary>

| Verbo | Ruta | Controlador |
|---|---|---|
| POST | `bixoadmin/bots/config` | BotStatusController@configSave |
| POST | `bixoadmin/bots/control` | BotStatusController@botControl |
| POST | `bixoadmin/bots/flow` | BotStatusController@flowStore |
| POST | `bixoadmin/bots/flow/import-json` | BotStatusController@flowImportFromJson |
| POST | `bixoadmin/bots/instances` | BotStatusController@botStore |
| DELETE | `bixoadmin/bots/instances/{bot}` | BotStatusController@botDestroy |
| POST | `bixoadmin/bots/reset-session` | BotStatusController@resetSession |
| POST | `bixoadmin/bots/states` | BotStatusController@stateStore |
| PUT | `bixoadmin/bots/states/{state}` | BotStatusController@stateUpdate |
| DELETE | `bixoadmin/bots/states/{state}` | BotStatusController@stateDestroy |
| POST | `bixoadmin/bots/states/{state}/move` | BotStatusController@stateMove |
| POST | `bixoadmin/bots/transitions` | BotStatusController@transitionStore |
| DELETE | `bixoadmin/bots/transitions/{transition}` | BotStatusController@transitionDestroy |
| POST | `bixoadmin/bots/upload-image` | BotStatusController@uploadImage |
| POST | `bixoadmin/bots-flow/{flow}` | BotFlowController@save |
| DELETE | `bixoadmin/bots-flow/{flow}` | BotFlowController@destroy |
| POST | `bixoadmin/bots-flow/{flow}/test` | BotFlowController@test |
| POST | `bixoadmin/catalogs` | CatalogListController@store |
| PUT | `bixoadmin/catalogs/{catalog}` | CatalogListController@update |
| DELETE | `bixoadmin/catalogs/{catalog}` | CatalogListController@destroy |
| POST | `bixoadmin/catalogs/{catalog}/values` | CatalogListController@storeValue |
| PUT | `bixoadmin/catalogs/{catalog}/values/{value}` | CatalogListController@updateValue |
| DELETE | `bixoadmin/catalogs/{catalog}/values/{value}` | CatalogListController@destroyValue |
| POST | `bixoadmin/certificados` | CertificadoController@store |
| PUT | `bixoadmin/certificados/{certificado}` | CertificadoController@update |
| DELETE | `bixoadmin/certificados/{certificado}` | CertificadoController@destroy |
| POST | `bixoadmin/combos` | ComboController@store |
| PUT | `bixoadmin/combos/{combo}` | ComboController@update |
| DELETE | `bixoadmin/combos/{combo}` | ComboController@destroy |
| PATCH | `bixoadmin/combos/{combo}/toggle` | ComboController@toggleAvailable |
| POST | `bixoadmin/company/groups` | UserGroupController@store |
| PUT | `bixoadmin/company/groups/{userGroup}` | UserGroupController@update |
| DELETE | `bixoadmin/company/groups/{userGroup}` | UserGroupController@destroy |
| POST | `bixoadmin/company/sedes` | SedeController@store |
| PUT | `bixoadmin/company/sedes/{sede}` | SedeController@update |
| DELETE | `bixoadmin/company/sedes/{sede}` | SedeController@destroy |
| POST | `bixoadmin/copilot` | CopilotEmpresarialController@preguntar |
| POST | `bixoadmin/coupons` | SettingsController@storeCoupon |
| DELETE | `bixoadmin/coupons/{id}` | SettingsController@destroyCoupon |
| PATCH | `bixoadmin/coupons/{id}/toggle` | SettingsController@toggleCoupon |
| POST | `bixoadmin/dashboard-comercial/meta` | DashboardComercialController@saveMeta |
| POST | `bixosales/get-projects` | AuthController@getProjects |
| POST | `bixoadmin/promotions` | PromotionController@store |
| PUT | `bixoadmin/promotions/{promotion}` | PromotionController@update |
| DELETE | `bixoadmin/promotions/{promotion}` | PromotionController@destroy |
| PATCH | `bixoadmin/promotions/{promotion}/toggle` | PromotionController@toggle |
| POST | `bixoadmin/proposals` | ProposalController@store |
| PUT | `bixoadmin/proposals/{proposal}` | ProposalController@update |
| DELETE | `bixoadmin/proposals/{proposal}` | ProposalController@destroy |
| POST | `bixoadmin/settings` | SettingsController@update |
| POST | `bixoadmin/settings/builder/catalog/bulk` | StoreBuilderController@catalogBulk |
| POST | `bixoadmin/settings/builder/category-photo` | StoreBuilderController@categoryPhoto |
| POST | `bixoadmin/settings/builder/copy` | StoreBuilderController@copyStore |
| POST | `bixoadmin/settings/builder/descartar-borrador` | StoreBuilderController@discardDraft |
| POST | `bixoadmin/settings/builder/design-preset` | StoreBuilderController@applyDesignPreset |
| POST | `bixoadmin/settings/builder/draft/settings` | StoreBuilderController@saveDraftSettings |
| POST | `bixoadmin/settings/builder/icons/assign` | StoreBuilderController@iconAssign |
| POST | `bixoadmin/settings/builder/publish` | StoreBuilderController@publish |
| POST | `bixoadmin/settings/canales` | SettingsController@storeCanal |
| DELETE | `bixoadmin/settings/canales/{canal}` | SettingsController@destroyCanal |
| POST | `bixoadmin/settings/catalog-profiles` | CatalogProfileController@store |
| POST | `bixoadmin/settings/catalog-profiles/feature` | CatalogProfileController@toggleFeature |
| POST | `bixoadmin/settings/catalog-profiles/quick` | CatalogProfileController@quickCreate |
| POST | `bixoadmin/settings/catalog-profiles/reorder` | CatalogProfileController@reorder |
| PUT | `bixoadmin/settings/catalog-profiles/{id}` | CatalogProfileController@update |
| DELETE | `bixoadmin/settings/catalog-profiles/{id}` | CatalogProfileController@destroy |
| POST | `bixoadmin/settings/design` | SettingsController@updateDesign |
| POST | `bixoadmin/settings/design-templates` | DesignTemplateController@store |
| POST | `bixoadmin/settings/design-templates/import` | DesignTemplateController@import |
| PUT | `bixoadmin/settings/design-templates/{id}` | DesignTemplateController@update |
| POST | `bixoadmin/settings/design-templates/{id}/apply` | DesignTemplateController@apply |
| POST | `bixoadmin/settings/design-templates/{id}/duplicate` | DesignTemplateController@duplicate |
| POST | `bixoadmin/settings/design-templates/{id}/restore/{versionNumber}` | DesignTemplateController@restore |
| POST | `bixoadmin/settings/design-templates/{id}/toggle` | DesignTemplateController@toggle |
| POST | `bixoadmin/settings/design-templates/{id}/version` | DesignTemplateController@newVersion |
| POST | `bixoadmin/settings/design/apply-project-template` | SettingsController@applyProjectTemplate |
| POST | `bixoadmin/settings/design/apply-template` | SettingsController@applyTemplate |
| POST | `bixoadmin/settings/design/project-templates` | SettingsController@storeProjectTemplate |
| PUT | `bixoadmin/settings/design/project-templates/{id}` | SettingsController@updateProjectTemplate |
| DELETE | `bixoadmin/settings/design/project-templates/{id}` | SettingsController@destroyProjectTemplate |
| PATCH | `bixoadmin/settings/experience/complaints/{id}` | StoreExperienceController@complaintStatus |
| POST | `bixoadmin/settings/experience/home/publish-all` | StoreExperienceController@publishAll |
| POST | `bixoadmin/settings/experience/home/reorder` | StoreExperienceController@reorderHome |
| POST | `bixoadmin/settings/experience/home/{component}` | StoreExperienceController@saveHomeSection |
| DELETE | `bixoadmin/settings/experience/home/{component}/draft` | StoreExperienceController@discardDraft |
| POST | `bixoadmin/settings/experience/home/{component}/publish` | StoreExperienceController@publishOne |
| POST | `bixoadmin/settings/experience/home/{component}/state` | StoreExperienceController@sectionState |
| POST | `bixoadmin/settings/experience/page` | StoreExperienceController@page |
| POST | `bixoadmin/settings/experience/popup` | StoreExperienceController@popup |
| POST | `bixoadmin/settings/experience/section` | StoreExperienceController@section |
| DELETE | `bixoadmin/settings/experience/section/{id}` | StoreExperienceController@deleteSection |
| POST | `bixoadmin/settings/flow` | SettingsController@updateFlow |
| POST | `bixoadmin/settings/flow/diagram` | SettingsController@updateDiagram |
| POST | `bixoadmin/settings/modules` | SettingsController@updateModules |
| POST | `bixoadmin/settings/payments` | SettingsController@updatePayments |
| POST | `bixoadmin/settings/qr` | SettingsController@updateQr |
| POST | `bixoadmin/settings/seo` | SettingsController@updateSeo |
| POST | `bixoadmin/settings/storefront/header` | StoreNavigationController@updateHeader |
| POST | `bixoadmin/settings/storefront/menu/items` | StoreNavigationController@storeItem |
| PUT | `bixoadmin/settings/storefront/menu/items/{item}` | StoreNavigationController@updateItem |
| DELETE | `bixoadmin/settings/storefront/menu/items/{item}` | StoreNavigationController@destroyItem |
| POST | `bixoadmin/settings/storefront/menu/reorder` | StoreNavigationController@reorder |
| POST | `bixoadmin/settings/storefront/publish` | StoreNavigationController@publishStructure |
| POST | `bixoadmin/settings/upload-logo` | SettingsController@uploadLogo |
| POST | `bixoadmin/settings/{target}/modules` | ProjectController@updateModules |
| PATCH | `bixoadmin/settings/{target}/toggle` | ProjectController@toggleStatus |
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
| POST | `bixoadmin/catalog-integrations` | `catalog-integrations.view, catalog-integrations.manage` |
| PUT | `bixoadmin/catalog-integrations/{integration}` | `catalog-integrations.view, catalog-integrations.manage` |
| DELETE | `bixoadmin/catalog-integrations/{integration}` | `catalog-integrations.view, catalog-integrations.manage` |
| POST | `bixoadmin/catalog-integrations/{integration}/sync` | `catalog-integrations.view, catalog-integrations.sync` |
| POST | `bixoadmin/catalog-integrations/{integration}/test` | `catalog-integrations.view, catalog-integrations.manage` |
| POST | `bixoadmin/categories` | `catalog.crear` |
| POST | `bixoadmin/categories/import` | `catalog.importar` |
| POST | `bixoadmin/categories/reorder` | `catalog.editar` |
| PATCH/PUT | `bixoadmin/categories/{category}` | `catalog.editar` |
| DELETE | `bixoadmin/categories/{category}` | `catalog.eliminar` |
| POST | `bixoadmin/clients` | `clients.crear` |
| PATCH/PUT | `bixoadmin/clients/{client}` | `clients.editar` |
| DELETE | `bixoadmin/clients/{client}` | `clients.eliminar` |
| PATCH | `bixoadmin/clients/{client}/stage` | `clients.editar` |
| POST | `bixoadmin/company/proveedores` | `proveedores.editar` |
| POST | `bixoadmin/company/proveedores/import` | `proveedores.editar` |
| PUT | `bixoadmin/company/proveedores/{proveedor}` | `proveedores.editar` |
| DELETE | `bixoadmin/company/proveedores/{proveedor}` | `proveedores.editar` |
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
| POST | `bixoadmin/roles` | `roles.gestionar` |
| PUT | `bixoadmin/roles/{role}` | `roles.gestionar` |
| DELETE | `bixoadmin/roles/{role}` | `roles.gestionar` |
| POST | `bixoadmin/services` | `catalog.crear` |
| POST | `bixoadmin/services/import` | `catalog.importar` |
| POST | `bixoadmin/services/reorder` | `catalog.editar` |
| PATCH/PUT | `bixoadmin/services/{service}` | `catalog.editar` |
| DELETE | `bixoadmin/services/{service}` | `catalog.eliminar` |
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
| `settings.catalogos` | **SIN DESTINO — decidir** | PENDIENTE DE CONECTAR |
| `settings.diseno` | **SIN DESTINO — decidir** | PENDIENTE DE CONECTAR |
| `settings.negocio` | **SIN DESTINO — decidir** | PENDIENTE DE CONECTAR |
| `settings.pagos` | **SIN DESTINO — decidir** | PENDIENTE DE CONECTAR |
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
| `manage-settings` | Configuración / Administrar | LEGACY |
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

