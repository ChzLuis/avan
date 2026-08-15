<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\Catalog\ServiceController;
use App\Http\Controllers\Catalog\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\CatalogListController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\HRController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\UserGroupController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ComunicacionesController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\WaWebhookController;
use App\Http\Controllers\ComboController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\WaBotController;
use App\Http\Controllers\RifaController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\BotStatusController;
use App\Http\Controllers\OperationalMapController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\Comunicaciones\AuthController as ComWaAuthController;
use App\Http\Controllers\Comunicaciones\BandejaController;
use App\Http\Controllers\Comunicaciones\ClientesCrmController;
use App\Http\Controllers\Comunicaciones\CanalesController;
use Illuminate\Support\Facades\Route;

// ─── Redirect raíz ───────────────────────────────────────────────────────────
Route::get('/', fn() => redirect('/login'));

// ─── BIXO Design System Prototype ─────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/bixo/screen-1', fn() => view('bixo.screen-1-business-center'))->name('bixo.screen1');
    Route::get('/bixo/screen-1-v2', fn() => view('bixo.screen-1-negocio-centro-v2'))->name('bixo.screen1v2');
    Route::get('/bixo/screen-2', fn() => view('bixo.screen-2-capability-operar'))->name('bixo.screen2');
    Route::get('/bixo/screen-3', fn() => view('bixo.screen-3-tool-inventario'))->name('bixo.screen3');
    Route::get('/bixo/screen-4', fn() => view('bixo.screen-4-object-producto'))->name('bixo.screen4');
    Route::get('/bixo/screen-5', fn() => view('bixo.screen-5-business-switch'))->name('bixo.screen5');
    Route::get('/bixo/demo', fn() => view('bixo.demo-prototype'))->name('bixo.demo');
    Route::get('/bixo/sidebar', fn() => view('bixo.sidebar-demo'))->name('bixo.sidebar');
    Route::get('/bixo/app', fn() => view('bixo.aplicacion-bixo'))->name('bixo.app');
    Route::get('/bixo/app-v3', fn() => view('bixo.aplicacion-bixo-v3'))->name('bixo.appv3');
    Route::get('/bixo/app-v4', fn() => view('bixo.aplicacion-bixo-v4'))->name('bixo.appv4');
});

// ─── Demo onboarding (público) ───────────────────────────────────────────────
Route::get('/demo',                 [DemoController::class, 'index'])->name('demo.index');
Route::get('/demo/features',        [DemoController::class, 'features'])->name('demo.features');
Route::post('/demo',                [DemoController::class, 'store'])->name('demo.store');

// ─── Ping de sesión (keep-alive) ─────────────────────────────────────────────
Route::post('/ping-session', function () {
    return response()->json(['ok' => true]);
})->middleware('web')->name('ping.session');

// Devuelve un token CSRF fresco para evitar el error 419 en páginas de larga vida
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->middleware('web')->name('csrf.token');

// ─── Panel autenticado ────────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    // Workspace (selector de negocios)
    Route::get('/workspace', [WorkspaceController::class, 'index'])->name('workspace');

    // Seleccionar proyecto activo
    Route::get('/workspace/select/{project}', [WorkspaceController::class, 'select'])->name('workspace.select');

    // CRUD de proyectos (negocios)
    Route::resource('projects', ProjectController::class)->except(['index', 'show']);

    // Panel de módulos del negocio activo (reemplaza /bixoadmin/negocios)
    Route::patch('/bixoadmin/settings/{target}/toggle', [ProjectController::class, 'toggleStatus'])->name('projects.toggle');
    Route::post('/bixoadmin/settings/{target}/modules', [ProjectController::class, 'updateModules'])->name('projects.modules');

    // Panel del negocio — todas las rutas bajo /panel
    Route::prefix('bixoadmin')->middleware(['project.member'])->group(function () {

        // Inicio → redirige a configuración del negocio
        Route::get('/', fn() => redirect()->route('settings'))->name('dashboard');
        Route::get('/dashboard', fn() => redirect()->route('settings'))->name('dashboard.alt');

        // Productos — /bixoadmin/products
        Route::prefix('products')->middleware(['module:catalog', 'can:catalog.ver'])->group(function () {
            Route::get('/',               [ProductController::class, 'index'])->name('products.index');
            Route::get('/export',         [ProductController::class, 'export'])->name('products.export');
            Route::get('/catalog-pdf',    [ProductController::class, 'catalogPdf'])->name('products.catalog.pdf');
            Route::get('/template',       [ProductController::class, 'template'])->name('products.template');
            Route::post('/import',        [ProductController::class, 'import'])->name('products.import');
            Route::post('/reorder',       [ProductController::class, 'reorder'])->name('products.reorder');
            Route::post('/bulk-action',   [ProductController::class, 'bulkAction'])->name('products.bulk-action');
            Route::delete('/purge-all',   [ProductController::class, 'purgeAll'])->name('products.purge-all');
            Route::get('/export/static',  [ProductController::class, 'exportStatic'])->name('products.export.static');
            Route::get('/export/meli',    [ProductController::class, 'exportMeli'])->name('products.export.meli');
            Route::get('/export/rappi',   [ProductController::class, 'exportRappi'])->name('products.export.rappi');
            Route::get('/export/shopee',  [ProductController::class, 'exportShopee'])->name('products.export.shopee');
            Route::resource('/', ProductController::class)->except(['index'])->parameters(['' => 'product'])->names([
                'create' => 'products.create', 'store'   => 'products.store',
                'show'   => 'products.show',   'edit'    => 'products.edit',
                'update' => 'products.update', 'destroy' => 'products.destroy',
            ]);
            Route::post('/{product}/duplicate',             [ProductController::class, 'duplicate'])->name('products.duplicate');
            Route::post('/{product}/images',               [ProductController::class, 'uploadImage'])->name('products.images.upload');
            Route::delete('/{product}/images/{image}',     [ProductController::class, 'deleteImage'])->name('products.images.delete');
            Route::patch('/{product}/images/{image}/main', [ProductController::class, 'setMainImage'])->name('products.images.main');
            // Reseñas
            Route::get('/reviews',                [ProductController::class, 'reviews'])->name('reviews.index');
            Route::patch('/reviews/{id}/approve', [ProductController::class, 'approveReview'])->name('reviews.approve');
            Route::delete('/reviews/{id}',        [ProductController::class, 'destroyReview'])->name('reviews.destroy');
        });
        // Alias legacy para no romper links internos viejos
        Route::get('/catalog',          fn() => redirect()->route('products.index'))->name('catalog');
        Route::get('/catalog/products', fn() => redirect()->route('products.index'));

        // Integraciones de catálogo — conectores externos (SISKOTE y futuros ERP)
        Route::prefix('catalog-integrations')->middleware(['module:catalog', 'can:catalog-integrations.view'])->group(function () {
            Route::get('/',                     [\App\Http\Controllers\Catalog\CatalogIntegrationController::class, 'index'])->name('catalog-integrations.index');
            Route::get('/schema/{provider}',    [\App\Http\Controllers\Catalog\CatalogIntegrationController::class, 'schema'])->name('catalog-integrations.schema');
            Route::post('/',                    [\App\Http\Controllers\Catalog\CatalogIntegrationController::class, 'store'])->name('catalog-integrations.store')->middleware('can:catalog-integrations.manage');
            Route::put('/{integration}',        [\App\Http\Controllers\Catalog\CatalogIntegrationController::class, 'update'])->whereNumber('integration')->name('catalog-integrations.update')->middleware('can:catalog-integrations.manage');
            Route::delete('/{integration}',     [\App\Http\Controllers\Catalog\CatalogIntegrationController::class, 'destroy'])->whereNumber('integration')->name('catalog-integrations.destroy')->middleware('can:catalog-integrations.manage');
            Route::post('/{integration}/test',  [\App\Http\Controllers\Catalog\CatalogIntegrationController::class, 'testConnection'])->whereNumber('integration')->name('catalog-integrations.test')->middleware('can:catalog-integrations.manage');
            Route::post('/{integration}/sync',  [\App\Http\Controllers\Catalog\CatalogIntegrationController::class, 'sync'])->whereNumber('integration')->name('catalog-integrations.sync')->middleware('can:catalog-integrations.sync');
            Route::get('/{integration}/history', [\App\Http\Controllers\Catalog\CatalogIntegrationController::class, 'history'])->whereNumber('integration')->name('catalog-integrations.history')->middleware('can:catalog-integrations.view-history');
            Route::get('/{integration}/preview', [\App\Http\Controllers\Catalog\CatalogIntegrationController::class, 'preview'])->whereNumber('integration')->name('catalog-integrations.preview')->middleware('can:catalog-integrations.manage');
        });

        // Servicios — /bixoadmin/services
        Route::prefix('services')->middleware(['module:catalog', 'can:catalog.ver'])->group(function () {
            Route::get('/',          [ServiceController::class, 'index'])->name('services.index');
            Route::get('/export',    [ServiceController::class, 'export'])->name('services.export');
            Route::get('/template',  [ServiceController::class, 'template'])->name('services.template');
            Route::post('/import',   [ServiceController::class, 'import'])->name('services.import');
            Route::post('/reorder',  [ServiceController::class, 'reorder'])->name('services.reorder');
            Route::resource('/', ServiceController::class)->except(['index'])->parameters(['' => 'service'])->names([
                'create' => 'services.create', 'store'   => 'services.store',
                'show'   => 'services.show',   'edit'    => 'services.edit',
                'update' => 'services.update', 'destroy' => 'services.destroy',
            ]);
        });
        // Alias legacy
        Route::get('/catalog/services', fn() => redirect()->route('services.index'));

        // Categorías — /bixoadmin/categories
        Route::prefix('categories')->middleware(['module:catalog', 'can:catalog.ver'])->group(function () {
            Route::get('/export',    [CategoryController::class, 'export'])->name('categories.export');
            Route::get('/template',  [CategoryController::class, 'template'])->name('categories.template');
            Route::post('/import',   [CategoryController::class, 'import'])->name('categories.import');
            Route::post('/reorder',  [CategoryController::class, 'reorder'])->name('categories.reorder');
            Route::resource('/', CategoryController::class)->parameters(['' => 'category'])->names([
                'index'   => 'categories.index',   'create'  => 'categories.create',
                'store'   => 'categories.store',   'show'    => 'categories.show',
                'edit'    => 'categories.edit',    'update'  => 'categories.update',
                'destroy' => 'categories.destroy',
            ]);
        });
        // Alias legacy
        Route::get('/catalog/categories', fn() => redirect()->route('categories.index'));

        // Clientes
        Route::resource('clients', ClientController::class)
            ->middleware(['module:clients', 'can:clients.ver'])
            ->names(['index'=>'clients','create'=>'clients.create','store'=>'clients.store',
                     'show'=>'clients.show','edit'=>'clients.edit','update'=>'clients.update','destroy'=>'clients.destroy']);

        // CRM: pipeline de ventas (leads del Copilot)
        Route::get('/clients-pipeline', [ClientController::class, 'pipeline'])
            ->middleware(['module:clients', 'can:clients.ver'])->name('clients.pipeline');
        Route::patch('/clients/{client}/stage', [ClientController::class, 'moveStage'])
            ->middleware(['module:clients', 'can:clients.ver'])->name('clients.stage');

        // Dashboard Comercial (indicadores de negocio en tiempo real)
        Route::get('/dashboard-comercial', [\App\Http\Controllers\DashboardComercialController::class, 'index'])->name('dashboard.comercial');
        Route::post('/dashboard-comercial/meta', [\App\Http\Controllers\DashboardComercialController::class, 'saveMeta'])->name('dashboard.comercial.meta');

        // Copilot Empresarial (pregúntale a tu negocio en español)
        Route::get('/copilot',  [\App\Http\Controllers\CopilotEmpresarialController::class, 'index'])->name('copilot.index');
        Route::post('/copilot', [\App\Http\Controllers\CopilotEmpresarialController::class, 'preguntar'])->name('copilot.preguntar');

        // Constructor visual de bots
        Route::get('/bots-flow',            [\App\Http\Controllers\BotFlowController::class, 'index'])->name('bot-flows.index');
        Route::get('/bots-flow/nuevo',      [\App\Http\Controllers\BotFlowController::class, 'editor'])->name('bot-flows.editor.new');
        Route::get('/bots-flow/{flow}',     [\App\Http\Controllers\BotFlowController::class, 'editor'])->name('bot-flows.editor');
        Route::post('/bots-flow/{flow}',    [\App\Http\Controllers\BotFlowController::class, 'save'])->name('bot-flows.save');
        Route::post('/bots-flow/{flow}/test',[\App\Http\Controllers\BotFlowController::class, 'test'])->name('bot-flows.test');
        Route::delete('/bots-flow/{flow}',  [\App\Http\Controllers\BotFlowController::class, 'destroy'])->name('bot-flows.destroy');

        // Agenda
        Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda')->middleware(['module:agenda', 'can:agenda.ver']);
        Route::resource('appointments', AgendaController::class)->except(['index'])->middleware(['module:agenda', 'can:agenda.ver']);

        // Roles y permisos
        Route::get('/roles',           [RolePermissionController::class, 'index'])->name('roles.index');
        Route::post('/roles',          [RolePermissionController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}',    [RolePermissionController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RolePermissionController::class, 'destroy'])->name('roles.destroy');

        // Catálogos (listas de valores maestros)
        Route::get('/catalogs',                             [CatalogListController::class, 'index'])->name('catalogs.index');
        Route::post('/catalogs',                            [CatalogListController::class, 'store'])->name('catalogs.store');
        Route::put('/catalogs/{catalog}',                   [CatalogListController::class, 'update'])->name('catalogs.update');
        Route::delete('/catalogs/{catalog}',                [CatalogListController::class, 'destroy'])->name('catalogs.destroy');
        Route::get('/catalogs/{catalog}/values',            [CatalogListController::class, 'values'])->name('catalogs.values');
        Route::post('/catalogs/{catalog}/values',           [CatalogListController::class, 'storeValue'])->name('catalogs.values.store');
        Route::put('/catalogs/{catalog}/values/{value}',    [CatalogListController::class, 'updateValue'])->name('catalogs.values.update');
        Route::delete('/catalogs/{catalog}/values/{value}', [CatalogListController::class, 'destroyValue'])->name('catalogs.values.destroy');

        // Proyectos (panel 3 columnas) — rutas movidas fuera del grupo project.member

        // Constructor Bot
        Route::get('/bot-builder',                      fn() => view('bot-builder.index'))->name('bot-builder.index');

        // Bots WhatsApp
        Route::get('/bots',                             [BotStatusController::class, 'index'])->name('bots.index');
        Route::get('/bots/status',                      [BotStatusController::class, 'status'])->name('bots.status');
        Route::get('/bots/flow',                        [BotStatusController::class, 'flowIndex'])->name('bots.flow');
        Route::post('/bots/flow',                       [BotStatusController::class, 'flowStore'])->name('bots.flow.store');
        Route::post('/bots/states',                     [BotStatusController::class, 'stateStore'])->name('bots.states.store');
        Route::put('/bots/states/{state}',              [BotStatusController::class, 'stateUpdate'])->name('bots.states.update');
        Route::delete('/bots/states/{state}',           [BotStatusController::class, 'stateDestroy'])->name('bots.states.destroy');
        Route::post('/bots/states/{state}/move',        [BotStatusController::class, 'stateMove'])->name('bots.states.move');
        Route::post('/bots/transitions',                [BotStatusController::class, 'transitionStore'])->name('bots.transitions.store');
        Route::delete('/bots/transitions/{transition}', [BotStatusController::class, 'transitionDestroy'])->name('bots.transitions.destroy');
        Route::post('/bots/config',                     [BotStatusController::class, 'configSave'])->name('bots.config.save');
        Route::post('/bots/control',                    [BotStatusController::class, 'botControl'])->name('bots.control');
        Route::post('/bots/reset-session',              [BotStatusController::class, 'resetSession'])->name('bots.reset-session');
        Route::get('/bots/logs',                        [BotStatusController::class, 'botLogs'])->name('bots.logs');
        Route::post('/bots/upload-image',               [BotStatusController::class, 'uploadImage'])->name('bots.upload.image');
        Route::post('/bots/instances',                  [BotStatusController::class, 'botStore'])->name('bots.instances.store');
        Route::delete('/bots/instances/{bot}',          [BotStatusController::class, 'botDestroy'])->name('bots.instances.destroy');
        Route::get('/bots/espera-asesor',               [BotStatusController::class, 'esperaAsesor'])->name('bots.espera-asesor');
        Route::post('/bots/flow/import-json',           [BotStatusController::class, 'flowImportFromJson'])->name('bots.flow.import');

        // HR / Empleados
        Route::get('/hr/employees',                [HRController::class, 'index'])->name('hr.employees.index')->middleware('can:hr.ver');
        Route::post('/hr/employees',               [HRController::class, 'store'])->name('hr.employees.store')->middleware('can:hr.crear');
        Route::put('/hr/employees/{employee}',     [HRController::class, 'update'])->name('hr.employees.update')->middleware('can:hr.editar');
        Route::delete('/hr/employees/{employee}',  [HRController::class, 'destroy'])->name('hr.employees.destroy')->middleware('can:hr.eliminar');

        // Asistencia y Comisiones
        Route::get('/hr/asistencia',               [\App\Http\Controllers\AttendanceController::class, 'index'])->name('hr.attendance.index')->middleware('can:attendance.ver');
        Route::post('/hr/asistencia',              [\App\Http\Controllers\AttendanceController::class, 'store'])->name('hr.attendance.store')->middleware('can:attendance.ver');
        Route::post('/hr/asistencia/check-in',     [\App\Http\Controllers\AttendanceController::class, 'checkIn'])->name('hr.attendance.checkin')->middleware('can:attendance.fichar');
        Route::post('/hr/asistencia/check-out',    [\App\Http\Controllers\AttendanceController::class, 'checkOut'])->name('hr.attendance.checkout')->middleware('can:attendance.fichar');
        Route::get('/hr/comisiones',               [\App\Http\Controllers\AttendanceController::class, 'comisiones'])->name('hr.comisiones.index')->middleware('can:attendance.ver');

        // Sedes
        Route::get('/company/sedes',           [SedeController::class, 'index'])->name('sedes.index');
        Route::post('/company/sedes',          [SedeController::class, 'store'])->name('sedes.store');
        Route::put('/company/sedes/{sede}',    [SedeController::class, 'update'])->name('sedes.update');
        Route::delete('/company/sedes/{sede}', [SedeController::class, 'destroy'])->name('sedes.destroy');

        // Grupos de usuarios
        Route::get('/company/groups',                [UserGroupController::class, 'index'])->name('groups.index');
        Route::post('/company/groups',               [UserGroupController::class, 'store'])->name('groups.store');
        Route::put('/company/groups/{userGroup}',    [UserGroupController::class, 'update'])->name('groups.update');
        Route::delete('/company/groups/{userGroup}', [UserGroupController::class, 'destroy'])->name('groups.destroy');

        // Proveedores
        Route::get('/company/proveedores',                    [ProveedorController::class, 'index'])->name('proveedores.index');
        Route::post('/company/proveedores',                   [ProveedorController::class, 'store'])->name('proveedores.store');
        Route::put('/company/proveedores/{proveedor}',        [ProveedorController::class, 'update'])->name('proveedores.update');
        Route::delete('/company/proveedores/{proveedor}',     [ProveedorController::class, 'destroy'])->name('proveedores.destroy');
        Route::get('/company/proveedores/template',           [ProveedorController::class, 'template'])->name('proveedores.template');
        Route::get('/company/proveedores/export',             [ProveedorController::class, 'export'])->name('proveedores.export');
        Route::post('/company/proveedores/import',            [ProveedorController::class, 'import'])->name('proveedores.import');

        // Combos
        Route::get('/combos',                        [ComboController::class, 'index'])->name('combos.index');
        Route::post('/combos',                       [ComboController::class, 'store'])->name('combos.store');
        Route::put('/combos/{combo}',                [ComboController::class, 'update'])->name('combos.update');
        Route::delete('/combos/{combo}',             [ComboController::class, 'destroy'])->name('combos.destroy');
        Route::patch('/combos/{combo}/toggle',       [ComboController::class, 'toggleAvailable'])->name('combos.toggle');

        // Promociones
        Route::get('/promotions',                    [PromotionController::class, 'index'])->name('promotions.index');
        Route::post('/promotions',                   [PromotionController::class, 'store'])->name('promotions.store');
        Route::put('/promotions/{promotion}',        [PromotionController::class, 'update'])->name('promotions.update');
        Route::delete('/promotions/{promotion}',     [PromotionController::class, 'destroy'])->name('promotions.destroy');
        Route::patch('/promotions/{promotion}/toggle',[PromotionController::class, 'toggle'])->name('promotions.toggle');

        // Comunicaciones / WhatsApp — redirige al portal bixocrm o a settings
        Route::prefix('bixocrm')->group(function () {
            Route::get('/configuracion', fn() => redirect()->route('settings', ['s' => 'whatsapp']));
            Route::get('/{any}',         fn() => redirect()->route('bixocrm.bandeja'))->where('any', '.*');
            Route::get('/',              fn() => redirect()->route('bixocrm.bandeja'));
        });

        // Configuración
        Route::get('/settings',          [SettingsController::class, 'index'])->name('settings');
        Route::post('/settings',         [SettingsController::class, 'update'])->name('settings.update');
        Route::get('/settings/design',   [SettingsController::class, 'design'])->name('settings.design');
        Route::get('/settings/designer', [SettingsController::class, 'designer'])->name('settings.designer'); // nuevo Diseñador visual (Fase A)

        // Constructor guiado (B0): entrada + lectura + contrato borrador/publicación.
        Route::get('/settings/builder', [\App\Http\Controllers\StoreBuilderController::class, 'index'])->name('settings.builder');
        Route::get('/settings/builder/progress', [\App\Http\Controllers\StoreBuilderController::class, 'progress'])->name('settings.builder.progress');
        Route::get('/settings/builder/checklist', [\App\Http\Controllers\StoreBuilderController::class, 'checklist'])->name('settings.builder.checklist');
        Route::get('/settings/builder/catalog/metrics', [\App\Http\Controllers\StoreBuilderController::class, 'catalogMetrics'])->name('settings.builder.metrics');
        Route::post('/settings/builder/draft/settings', [\App\Http\Controllers\StoreBuilderController::class, 'saveDraftSettings'])->name('settings.builder.draft.settings');
        Route::post('/settings/builder/design-preset', [\App\Http\Controllers\StoreBuilderController::class, 'applyDesignPreset'])->name('settings.builder.design-preset');
        // Diseños guardados ("Mis plantillas")
        Route::get('/settings/design-templates', [\App\Http\Controllers\DesignTemplateController::class, 'index'])->name('design-templates.index');
        Route::post('/settings/design-templates', [\App\Http\Controllers\DesignTemplateController::class, 'store'])->name('design-templates.store');
        Route::post('/settings/design-templates/import', [\App\Http\Controllers\DesignTemplateController::class, 'import'])->name('design-templates.import');
        Route::post('/settings/design-templates/{id}/apply', [\App\Http\Controllers\DesignTemplateController::class, 'apply'])->name('design-templates.apply');
        Route::post('/settings/design-templates/{id}/version', [\App\Http\Controllers\DesignTemplateController::class, 'newVersion'])->name('design-templates.version');
        Route::post('/settings/design-templates/{id}/restore/{versionNumber}', [\App\Http\Controllers\DesignTemplateController::class, 'restore'])->name('design-templates.restore');
        Route::post('/settings/design-templates/{id}/duplicate', [\App\Http\Controllers\DesignTemplateController::class, 'duplicate'])->name('design-templates.duplicate');
        Route::post('/settings/design-templates/{id}/toggle', [\App\Http\Controllers\DesignTemplateController::class, 'toggle'])->name('design-templates.toggle');
        Route::put('/settings/design-templates/{id}', [\App\Http\Controllers\DesignTemplateController::class, 'update'])->name('design-templates.update');
        Route::get('/settings/design-templates/{id}/export', [\App\Http\Controllers\DesignTemplateController::class, 'export'])->name('design-templates.export');
        Route::post('/settings/builder/publish', [\App\Http\Controllers\StoreBuilderController::class, 'publish'])->name('settings.builder.publish');
        // Descartar el borrador y volver a lo publicado. Hasta ahora la única
        // salida de un borrador con cambios no deseados era publicarlos.
        Route::post('/settings/builder/descartar-borrador', [\App\Http\Controllers\StoreBuilderController::class, 'discardDraft'])->name('settings.builder.discard');
        Route::get('/settings/builder/preview', [\App\Http\Controllers\StoreBuilderController::class, 'preview'])->name('settings.builder.preview');
        Route::get('/settings/builder/catalog/products', [\App\Http\Controllers\StoreBuilderController::class, 'catalogList'])->name('settings.builder.catalog.list');
        Route::post('/settings/builder/catalog/bulk', [\App\Http\Controllers\StoreBuilderController::class, 'catalogBulk'])->name('settings.builder.catalog.bulk');
        Route::get('/settings/builder/copy/sources', [\App\Http\Controllers\StoreBuilderController::class, 'copySources'])->name('settings.builder.copy.sources');
        Route::post('/settings/builder/copy', [\App\Http\Controllers\StoreBuilderController::class, 'copyStore'])->name('settings.builder.copy');
        Route::get('/settings/builder/icons/search', [\App\Http\Controllers\StoreBuilderController::class, 'iconSearch'])->name('settings.builder.icons.search');
        Route::post('/settings/builder/category-photo', [\App\Http\Controllers\StoreBuilderController::class, 'categoryPhoto'])->name('settings.builder.category-photo');
        Route::post('/settings/builder/icons/assign', [\App\Http\Controllers\StoreBuilderController::class, 'iconAssign'])->name('settings.builder.icons.assign');
        Route::post('/settings/design',  [SettingsController::class, 'updateDesign'])->name('settings.design.update');
        Route::post('/settings/design/apply-template', [SettingsController::class, 'applyTemplate'])->name('settings.design.applyTemplate');
        Route::post('/settings/design/apply-project-template', [SettingsController::class, 'applyProjectTemplate'])->name('settings.design.applyProjectTemplate');
        Route::post('/settings/design/project-templates', [SettingsController::class, 'storeProjectTemplate'])->name('settings.design.projectTemplates.store');
        Route::put('/settings/design/project-templates/{id}', [SettingsController::class, 'updateProjectTemplate'])->name('settings.design.projectTemplates.update');
        Route::delete('/settings/design/project-templates/{id}', [SettingsController::class, 'destroyProjectTemplate'])->name('settings.design.projectTemplates.destroy');
        Route::get('/settings/experience', [\App\Http\Controllers\StoreExperienceController::class, 'index'])->name('settings.experience');
        Route::post('/settings/experience/home/reorder', [\App\Http\Controllers\StoreExperienceController::class, 'reorderHome'])->name('settings.experience.home.reorder');
        Route::post('/settings/experience/home/publish-all', [\App\Http\Controllers\StoreExperienceController::class, 'publishAll'])->name('settings.experience.home.publishAll');
        Route::post('/settings/experience/home/{component}/publish', [\App\Http\Controllers\StoreExperienceController::class, 'publishOne'])->name('settings.experience.home.publishOne');
        Route::delete('/settings/experience/home/{component}/draft', [\App\Http\Controllers\StoreExperienceController::class, 'discardDraft'])->name('settings.experience.home.discardDraft');
        Route::post('/settings/experience/home/{component}', [\App\Http\Controllers\StoreExperienceController::class, 'saveHomeSection'])->name('settings.experience.home.save');
        Route::post('/settings/experience/home/{component}/state', [\App\Http\Controllers\StoreExperienceController::class, 'sectionState'])->name('settings.experience.home.state'); // Diseñador: sólo estado
        Route::get('/settings/experience/preview', [\App\Http\Controllers\StoreExperienceController::class, 'preview'])->name('settings.experience.preview');
        Route::post('/settings/experience/section', [\App\Http\Controllers\StoreExperienceController::class, 'section'])->name('settings.experience.section');
        Route::delete('/settings/experience/section/{id}', [\App\Http\Controllers\StoreExperienceController::class, 'deleteSection'])->name('settings.experience.section.delete');
        Route::post('/settings/experience/page', [\App\Http\Controllers\StoreExperienceController::class, 'page'])->name('settings.experience.page');
        Route::post('/settings/experience/popup', [\App\Http\Controllers\StoreExperienceController::class, 'popup'])->name('settings.experience.popup');
        Route::post('/settings/storefront/header', [\App\Http\Controllers\StoreNavigationController::class, 'updateHeader'])->name('settings.storefront.header');
        Route::post('/settings/storefront/publish', [\App\Http\Controllers\StoreNavigationController::class, 'publishStructure'])->name('settings.storefront.publish');
        Route::post('/settings/storefront/menu/items', [\App\Http\Controllers\StoreNavigationController::class, 'storeItem'])->name('settings.storefront.menu.items.store');
        Route::put('/settings/storefront/menu/items/{item}', [\App\Http\Controllers\StoreNavigationController::class, 'updateItem'])->name('settings.storefront.menu.items.update');
        Route::delete('/settings/storefront/menu/items/{item}', [\App\Http\Controllers\StoreNavigationController::class, 'destroyItem'])->name('settings.storefront.menu.items.destroy');
        Route::post('/settings/storefront/menu/reorder', [\App\Http\Controllers\StoreNavigationController::class, 'reorder'])->name('settings.storefront.menu.reorder');

        // Perfiles de catálogo (opcional, desactivado por defecto)
        Route::post('/settings/catalog-profiles/feature', [\App\Http\Controllers\CatalogProfileController::class, 'toggleFeature'])->name('settings.catalog-profiles.feature');
        Route::post('/settings/catalog-profiles', [\App\Http\Controllers\CatalogProfileController::class, 'store'])->name('settings.catalog-profiles.store');
        Route::post('/settings/catalog-profiles/quick', [\App\Http\Controllers\CatalogProfileController::class, 'quickCreate'])->name('settings.catalog-profiles.quick');
        Route::put('/settings/catalog-profiles/{id}', [\App\Http\Controllers\CatalogProfileController::class, 'update'])->name('settings.catalog-profiles.update')->where('id', '[0-9]+');
        Route::delete('/settings/catalog-profiles/{id}', [\App\Http\Controllers\CatalogProfileController::class, 'destroy'])->name('settings.catalog-profiles.destroy')->where('id', '[0-9]+');
        Route::post('/settings/catalog-profiles/reorder', [\App\Http\Controllers\CatalogProfileController::class, 'reorder'])->name('settings.catalog-profiles.reorder');
        Route::patch('/settings/experience/complaints/{id}', [\App\Http\Controllers\StoreExperienceController::class, 'complaintStatus'])->name('settings.experience.complaint.status');
        Route::post('/settings/flow', [SettingsController::class, 'updateFlow'])->name('settings.flow.update');
        Route::post('/settings/flow/diagram', [SettingsController::class, 'updateDiagram'])->name('settings.flow.diagram');
        Route::post('/settings/upload-logo', [SettingsController::class, 'uploadLogo'])->name('settings.upload-logo');
        Route::get('/notifications/imports',  [SettingsController::class, 'importLogs'])->name('notifications.imports');
        Route::get('/settings/payments', [SettingsController::class, 'payments'])->name('settings.payments');
        Route::post('/settings/payments',[SettingsController::class, 'updatePayments'])->name('settings.payments.update');
        Route::get('/settings/modules',  [SettingsController::class, 'modules'])->name('settings.modules');
        Route::get('/settings/qr',        [SettingsController::class, 'qr'])->name('settings.qr');
        Route::post('/settings/qr',       [SettingsController::class, 'updateQr'])->name('settings.qr.save');
        Route::get('/settings/seo',      [SettingsController::class, 'seo'])->name('settings.seo');
        Route::post('/settings/seo',     [SettingsController::class, 'updateSeo'])->name('settings.seo.update');
        Route::post('/settings/modules', [SettingsController::class, 'updateModules'])->name('settings.modules.update');
        // Cupones
        Route::post('/coupons',          [SettingsController::class, 'storeCoupon'])->name('coupons.store');
        Route::delete('/coupons/{id}',   [SettingsController::class, 'destroyCoupon'])->name('coupons.destroy');
        Route::patch('/coupons/{id}/toggle', [SettingsController::class, 'toggleCoupon'])->name('coupons.toggle');

        // WhatsApp canales desde el portal administrador
        Route::post('/settings/canales',            [SettingsController::class, 'storeCanal'])->name('settings.canales.store');
        Route::delete('/settings/canales/{canal}',  [SettingsController::class, 'destroyCanal'])->name('settings.canales.destroy');

        // Propuestas BIXO
        Route::get('/proposals',              [ProposalController::class, 'index'])->name('proposals.index');
        Route::post('/proposals',             [ProposalController::class, 'store'])->name('proposals.store');
        Route::put('/proposals/{proposal}',   [ProposalController::class, 'update'])->name('proposals.update');
        Route::delete('/proposals/{proposal}',[ProposalController::class, 'destroy'])->name('proposals.destroy');

        // Certificados digitales
        Route::get('/certificados',                    [CertificadoController::class, 'index'])->name('certificados.index');
        Route::post('/certificados',                   [CertificadoController::class, 'store'])->name('certificados.store');
        Route::put('/certificados/{certificado}',      [CertificadoController::class, 'update'])->name('certificados.update');
        Route::delete('/certificados/{certificado}',   [CertificadoController::class, 'destroy'])->name('certificados.destroy');
    });

    // ─── Herramientas operativas (URL corta, fuera de /panel) ────────────────
    Route::middleware(['project.member'])->group(function () {

        // POS
        Route::get('/pos',  [PosController::class, 'index'])->name('pos.index')->middleware(['module:orders', 'can:pos.usar']);
        Route::get('/venta-express', [PosController::class, 'express'])->name('ventas.express')->middleware(['module:orders', 'can:pos.usar']);
        Route::post('/pos', [PosController::class, 'store'])->name('pos.store')->middleware(['module:orders', 'can:pos.usar']);
        Route::post('/pos/cotizar', [PosController::class, 'quote'])->name('pos.quote')->middleware(['module:orders', 'can:pos.usar']);

        // Revendedor: sus precios propios + su catálogo compartible
        Route::get('/revendedor/precios',  [\App\Http\Controllers\ResellerController::class, 'misPrecios'])->name('reseller.precios')->middleware('can:pos.usar');
        Route::post('/revendedor/precio',  [\App\Http\Controllers\ResellerController::class, 'guardarPrecio'])->name('reseller.precio.guardar')->middleware('can:pos.usar');
        Route::post('/revendedor/catalogo',[\App\Http\Controllers\ResellerController::class, 'toggleCatalogo'])->name('reseller.catalogo.toggle')->middleware('can:pos.usar');

        // Facturas
        Route::get('/invoices',               [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('/invoices',              [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('/invoices/{invoice}',     [InvoiceController::class, 'show'])->name('invoices.show');
        Route::put('/invoices/{invoice}',     [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('/invoices/{invoice}',  [InvoiceController::class, 'destroy'])->name('invoices.destroy');
        Route::get('/invoices/{invoice}/pdf',    [InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::post('/invoices/{invoice}/sunat', [InvoiceController::class, 'sendSunat'])->name('invoices.sunat');

        // Cotizaciones
        // Un permiso por verbo: *.ver solo autoriza lectura. Antes iba un unico
        // can:quotes.ver sobre todo el resource y quien podia leer podia borrar.
        Route::get('/quotes',              [QuoteController::class, 'index'])->name('quotes')->middleware(['module:quotes', 'can:quotes.ver']);
        Route::get('/quotes/{quote}',      [QuoteController::class, 'show'])->name('quotes.show')->middleware(['module:quotes', 'can:quotes.ver']);
        Route::post('/quotes',             [QuoteController::class, 'store'])->name('quotes.store')->middleware(['module:quotes', 'can:quotes.crear']);
        Route::match(['put', 'patch'], '/quotes/{quote}', [QuoteController::class, 'update'])->name('quotes.update')->middleware(['module:quotes', 'can:quotes.editar']);
        Route::delete('/quotes/{quote}',   [QuoteController::class, 'destroy'])->name('quotes.destroy')->middleware(['module:quotes', 'can:quotes.eliminar']);
        Route::put('/quotes/{quote}/full',   [QuoteController::class, 'updateFull'])->name('quotes.update_full')->middleware('can:quotes.editar');
        Route::post('/quotes/{quote}/send', [QuoteController::class, 'send'])->name('quotes.send')->middleware('can:quotes.editar');
        Route::post('/quotes/{quote}/duplicate', [QuoteController::class, 'duplicate'])->name('quotes.duplicate')->middleware('can:quotes.crear');
        // Acuse de lectura que dispara la propia vista: basta con poder leerla.
        Route::post('/quotes/{quote}/seen',      [QuoteController::class, 'markSeen'])->name('quotes.seen')->middleware(['module:quotes', 'can:quotes.ver']);

        // Pedidos
        Route::get('/orders',              [OrderController::class, 'index'])->name('orders')->middleware(['module:orders', 'can:orders.ver']);
        Route::get('/orders/{order}',      [OrderController::class, 'show'])->name('orders.show')->middleware(['module:orders', 'can:orders.ver']);
        Route::post('/orders',             [OrderController::class, 'store'])->name('orders.store')->middleware(['module:orders', 'can:orders.crear']);
        Route::match(['put', 'patch'], '/orders/{order}', [OrderController::class, 'update'])->name('orders.update')->middleware(['module:orders', 'can:orders.editar']);
        Route::delete('/orders/{order}',   [OrderController::class, 'destroy'])->name('orders.destroy')->middleware(['module:orders', 'can:orders.eliminar']);

        // Pedidos WhatsApp — acción del portal sobre pedido WA
        Route::post('/orders/{order}/wa-action',   [WaBotController::class, 'portalAction'])->name('orders.wa.action')->middleware(['module:orders', 'can:orders.editar']);
        Route::post('/orders/{order}/wa-delivery', [WaBotController::class, 'updateDelivery'])->name('orders.wa.delivery')->middleware(['module:orders', 'can:orders.editar']);
        Route::post('/orders/{order}/laundry-status', [WaBotController::class, 'changeLaundryStatus'])->name('orders.laundry-status')->middleware(['module:orders', 'can:orders.editar']);
        Route::get('/orders/{order}/tag',          [OrderController::class, 'tag'])->name('orders.tag')->middleware(['module:orders', 'can:orders.ver']);
        Route::post('/orders/{order}/pay',         [OrderController::class, 'pay'])->name('orders.pay')->middleware(['module:orders', 'can:orders.editar']);
        Route::post('/orders/{order}/issue-document', [OrderController::class, 'issueDocument'])->name('orders.issue-document')->middleware(['module:orders', 'can:orders.editar']);
        Route::get('/orders/{order}/events',       [OrderController::class, 'events'])->name('orders.events')->middleware(['module:orders', 'can:orders.ver']);
        Route::post('/orders/{order}/wa-sent',     [OrderController::class, 'waSent'])->name('orders.wa-sent')->middleware(['module:orders', 'can:orders.editar']);
        Route::get('/orders-export',               [OrderController::class, 'exportCsv'])->name('orders.export')->middleware(['module:orders', 'can:orders.ver']);
        Route::post('/quotes/{quote}/convert',     [QuoteController::class, 'convert'])->name('quotes.convert')->middleware(['module:quotes', 'can:quotes.editar']);
    });

    // Perfil de usuario
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ─── Verificación pública de certificados ────────────────────────────────────
Route::get('/cert/{codigo}', [CertificadoController::class, 'verificar'])->name('cert.verificar');

// ─── Sitemap/robots para custom domains (sin slug en URL) ────────────────────
Route::get('/sitemap.xml', function () {
    // app('...') lanza excepción si el binding no existe (visitas sin dominio custom):
    // verificar bound() evita el error que inundaba el log.
    $project = app()->bound('custom_domain_project') ? app('custom_domain_project') : null;
    if (!$project) abort(404);
    return app(\App\Http\Controllers\PublicController::class)->sitemap($project->slug);
});
Route::get('/robots.txt', function () {
    $project = app()->bound('custom_domain_project') ? app('custom_domain_project') : null;
    if (!$project) abort(404);
    return app(\App\Http\Controllers\PublicController::class)->robots($project->slug);
});

// ─── Ficha de producto en dominio propio (sin slug en la URL) ────────────────
// El middleware de dominio propio corre dentro del grupo 'web', así que Laravel
// resolvía el 404 antes de llegar a él: la ruta debe existir explícitamente.
// URL legible: /producto/teclado-mecanico-rgb-460. El id cierra la clave, asi
// que un cambio de nombre no rompe el enlace; si el nombre no coincide con el
// actual redirigimos 301 al canonico para no repartir dos URLs por producto.
Route::get('/producto/{clave}', function (string $clave) {
    $project = app()->bound('custom_domain_project') ? app('custom_domain_project') : null;
    if (! $project) {
        abort(404);
    }
    $id = \App\Support\ImageVariants::idDeClave($clave);
    if ($id <= 0) {
        abort(404);
    }
    $producto = $project->products()->where('is_available', true)->find($id);
    if ($producto && \App\Support\ImageVariants::claveProducto($id, $producto->name) !== $clave) {
        return redirect(\App\Support\ImageVariants::productUrl($project, $id, $producto->name), 301);
    }

    return app(\App\Http\Controllers\PublicController::class)->product($project->slug, $id);
})->where('clave', '[A-Za-z0-9-]*[0-9]+')->name('public.product.domain');

// Enlaces antiguos (/p/460): 301 al nombre para no perder lo ya compartido.
Route::get('/p/{id}', function (int $id) {
    $project = app()->bound('custom_domain_project') ? app('custom_domain_project') : null;
    if (! $project) {
        abort(404);
    }
    $producto = $project->products()->where('is_available', true)->findOrFail($id);

    return redirect(\App\Support\ImageVariants::productUrl($project, $id, $producto->name), 301);
})->where('id', '[0-9]+')->name('public.product.domain.legacy');

// El carrito vive dentro de la tienda; un enlace directo no debe dar error.
Route::get('/carrito', function () {
    $project = app()->bound('custom_domain_project') ? app('custom_domain_project') : null;
    return $project ? redirect('/') : abort(404);
});

// Categoría legible en dominio propio: tienda.tecsist.net/tienda/c/computadoras
Route::get('/tienda/c/{categoria}', function (string $categoria, \Illuminate\Http\Request $request) {
    $project = app()->bound('custom_domain_project') ? app('custom_domain_project') : null;
    if (! $project) {
        abort(404);
    }
    return app(\App\Http\Controllers\PublicController::class)->shopPorCategoria($request, $project->slug, $categoria);
})->where('categoria', '[a-z0-9-]+');

// Colección del catálogo en dominio propio (/tienda/nino, /tienda/nina...).
Route::get('/tienda/{profile}', function (string $profile, \Illuminate\Http\Request $request) {
    $project = app()->bound('custom_domain_project') ? app('custom_domain_project') : null;
    if (! $project) {
        abort(404);
    }
    return app(\App\Http\Controllers\PublicController::class)->shop($request, $project->slug, $profile);
})->where('profile', '[a-z0-9-]+');

// ─── Catálogo público ─────────────────────────────────────────────────────────
$reserved = 'login|register|logout|workspace|bixoadmin|profile|projects|dashboard|b|f|up|pos|invoices|quotes|orders|bixosales|bixocrm|bixofact|wa|cert';
Route::get('/storefront-preview/{project}', function (\App\Models\Project $project) {
    abort_unless($project->is_active, 404);
    if (request('page') === 'shop') {
        request()->attributes->set('storefront_preview', true);
        return app(\App\Http\Controllers\PublicController::class)->shop(request(), $project->slug);
    }
    return app(\App\Http\Controllers\PublicController::class)->previewStorefront($project);
})->middleware('signed')->name('public.storefront.preview');
Route::get('/{slug}/sitemap.xml', [PublicController::class, 'sitemap'])->name('public.sitemap')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::get('/{slug}/robots.txt',  [PublicController::class, 'robots'])->name('public.robots')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::get('/{slug}/contacto', [\App\Http\Controllers\StorePageController::class, 'contact'])->name('public.contact')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::get('/{slug}/nosotros', [\App\Http\Controllers\StorePageController::class, 'about'])->name('public.about')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::get('/{slug}/blog', [\App\Http\Controllers\StorePageController::class, 'blog'])->name('public.blog')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::get('/{slug}/blog/{key}', [\App\Http\Controllers\StorePageController::class, 'blogPost'])->name('public.blog.show')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+')->where('key', '[a-zA-Z0-9_-]+');
Route::post('/{slug}/contacto', [\App\Http\Controllers\StorePageController::class, 'sendContact'])->name('public.contact.send')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::get('/{slug}/libro-reclamaciones', [\App\Http\Controllers\StorePageController::class, 'complaints'])->name('public.complaints')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::post('/{slug}/libro-reclamaciones', [\App\Http\Controllers\StorePageController::class, 'storeComplaint'])->name('public.complaints.store')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
// Alias corto usado por los footers de las plantillas + páginas legales.
Route::get('/{slug}/reclamaciones', [\App\Http\Controllers\StorePageController::class, 'complaints'])->name('public.complaints.short')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::post('/{slug}/reclamaciones', [\App\Http\Controllers\StorePageController::class, 'storeComplaint'])->name('public.complaints.short.store')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::get('/{slug}/privacidad', [\App\Http\Controllers\StorePageController::class, 'legal'])->defaults('key', 'privacidad')->name('public.privacy')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::get('/{slug}/terminos', [\App\Http\Controllers\StorePageController::class, 'legal'])->defaults('key', 'terminos')->name('public.terms')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::get('/{slug}/pagina/{key}', [\App\Http\Controllers\StorePageController::class, 'page'])->name('public.page')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+')->where('key', '[a-z0-9-]+');
// ═══ Categoría con URL legible: /{slug}/tienda/c/computadoras ═══
// El prefijo `c/` es deliberado: sin él chocaría con /tienda/{profile}, que ya
// existe para los perfiles (nino, nina...). La categoría se resuelve por slug y
// se inyecta como si viniera en la query, así el catálogo no cambia en nada.
Route::get('/{slug}/tienda/c/{categoria}', function (string $slug, string $categoria, \Illuminate\Http\Request $request) {
    return app(PublicController::class)->shopPorCategoria($request, $slug, $categoria);
})->name('public.shop.category')
  ->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+')
  ->where('categoria', '[a-z0-9-]+');

Route::get('/{slug}/tienda', [PublicController::class, 'shop'])->name('public.shop')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::get('/{slug}/tienda/{profile}', [PublicController::class, 'shop'])->name('public.shop.profile')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+')->where('profile', '[a-z0-9-]+');
Route::get('/{slug}',          [PublicController::class, 'catalog'])->name('public.catalog')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::get('/{slug}/producto/{clave}', function (string $slug, string $clave) {
    $id = \App\Support\ImageVariants::idDeClave($clave);
    if ($id <= 0) {
        abort(404);
    }
    $project = \App\Models\Project::where('slug', $slug)->firstOrFail();
    $producto = $project->products()->where('is_available', true)->find($id);
    if ($producto && \App\Support\ImageVariants::claveProducto($id, $producto->name) !== $clave) {
        return redirect(\App\Support\ImageVariants::productUrl($project, $id, $producto->name), 301);
    }

    return app(PublicController::class)->product($slug, $id);
})->name('public.product')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+')->where('clave', '[A-Za-z0-9-]*[0-9]+');
// Enlaces antiguos (/tienda-x/p/460): 301 al nombre.
Route::get('/{slug}/p/{id}', function (string $slug, int $id) {
    $project = \App\Models\Project::where('slug', $slug)->firstOrFail();
    $producto = $project->products()->where('is_available', true)->findOrFail($id);

    return redirect(\App\Support\ImageVariants::productUrl($project, $id, $producto->name), 301);
})->name('public.product.legacy')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+')->where('id', '[0-9]+');
Route::post('/{slug}/order-proof',    [PublicController::class, 'uploadOrderProof'])->name('public.order.proof')->middleware('throttle:10,1')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::post('/{slug}/order',          [PublicController::class, 'storeOrder'])->name('public.order')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::post('/{slug}/upload-voucher', [PublicController::class, 'uploadVoucher'])->name('public.upload.voucher')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::post('/{slug}/cart',    [PublicController::class, 'saveCart'])->name('public.cart.save')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::post('/{slug}/coupon',  [PublicController::class, 'validateCoupon'])->name('public.coupon')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::get('/{slug}/thanks/{order}', [PublicController::class, 'thankyou'])->name('public.thanks')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+')->where('order', '[0-9]+');
Route::post('/{slug}/p/{product}/review', [PublicController::class, 'storeReview'])->name('public.review')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+')->where('product', '[0-9]+');
Route::post('/{slug}/quote',   [PublicController::class, 'storeQuote'])->name('public.quote')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::get('/{slug}/book',     [PublicController::class, 'book'])->name('public.book')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');
Route::post('/{slug}/book',    [PublicController::class, 'storeBook'])->name('public.book.store')->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+');

// ─── WhatsApp Bot API (sin auth, validada por token interno) ─────────────────
Route::get('/wa/config',                        [WaBotController::class, 'getConfig'])->name('wa.config');
Route::get('/wa/flow-config',                   [WaBotController::class, 'getFlowConfig'])->name('wa.flow_config');
Route::get('/wa/session',                       [WaBotController::class, 'getSession'])->name('wa.session.get')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/wa/session',                      [WaBotController::class, 'updateSession'])->name('wa.session.update')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/wa/order',                        [WaBotController::class, 'receiveOrder'])->name('wa.order')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/wa/order/{order}/payment',        [WaBotController::class, 'paymentReceived'])->name('wa.order.payment')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/wa/order/{order}/confirmed',      [WaBotController::class, 'clientConfirmed'])->name('wa.order.confirmed')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/wa/order/{order}/payment-proof',  [WaBotController::class, 'receivePaymentProof'])->name('wa.order.payment_proof')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/wa/find-order',                   [WaBotController::class, 'findOrder'])->name('wa.find_order')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/wa/order/{order}/delivery',       [WaBotController::class, 'updateDelivery'])->name('wa.order.delivery')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// ─── QR público para conectar bot ────────────────────────────────────────────
Route::get('/bot-qr/{bot?}', function ($bot = 'rifa') {
    $file = base_path("whatsbot/{$bot}-status.json");
    $css  = '<style>*{box-sizing:border-box;margin:0;padding:0;}body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f8fafc;padding:12px;}.wrap{text-align:center;width:100%;}p.hint{color:#6b7280;font-size:12px;margin-bottom:10px;line-height:1.4;}img.qr{width:100%;max-width:260px;border-radius:10px;border:2px solid #e5e7eb;}.connected{color:#16a34a;font-weight:600;font-size:15px;}.wait{color:#9ca3af;font-size:13px;}</style>';
    // Recarga cada 3 seg para mostrar QR nuevo cuando el bot lo genere
    $head = '<head><meta charset="utf-8"><meta http-equiv="refresh" content="3"><title>Bot QR</title>'.$css.'</head>';

    $data   = file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
    $status = $data['status'] ?? '';
    $qr     = $data['qr'] ?? null;

    if ($status === 'connected') {
        $html = '<!DOCTYPE html><html>'.$head.'<body><div class="wrap"><p class="connected">&#10003; Bot Conectado</p></div></body></html>';
        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    if ($qr) {
        $qrSafe = htmlspecialchars($qr, ENT_QUOTES);
        $html   = '<!DOCTYPE html><html>'.$head.'<body><div class="wrap"><p class="hint">Abre WhatsApp &rarr; Dispositivos vinculados &rarr; Vincular dispositivo</p><img class="qr" src="'.$qrSafe.'"></div></body></html>';
        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    $html = '<!DOCTYPE html><html>'.$head.'<body><div class="wrap"><p class="wait">Bot iniciando... espera unos segundos</p></div></body></html>';
    return response($html)->header('Content-Type', 'text/html; charset=utf-8');
})->name('bot.qr');

// ─── Bot status JSON público (para polling desde cualquier panel) ─────────────
Route::get('/bot-status/meta', function () {
    $canal = \App\Models\WaCanal::where('bot_type', 'rifa')
        ->whereNotNull('phone_number_id')
        ->where('phone_number_id', '!=', '')
        ->first();
    if (!$canal) return response()->json(['connected' => false]);
    $row = \Illuminate\Support\Facades\DB::table('wa_canales')->where('id', $canal->id)->first();
    if (!$row->access_token || !$row->phone_number_id) return response()->json(['connected' => false]);
    try {
        $r = \Illuminate\Support\Facades\Http::withToken($row->access_token)
            ->timeout(5)
            ->get("https://graph.facebook.com/v19.0/{$row->phone_number_id}");
        return response()->json(['connected' => $r->successful()]);
    } catch (\Throwable $e) {
        return response()->json(['connected' => false]);
    }
})->name('bot.status.meta');

Route::get('/bot-status/{bot?}', function ($bot = 'rifa') {
    $file = base_path("whatsbot/{$bot}-status.json");
    $data = file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
    return response()->json([
        'status' => $data['status'] ?? 'disconnected',
        'qr'     => $data['qr']     ?? null,
    ]);
})->name('bot.status.json');

// ─── WooCommerce Webhook (sin CSRF) ──────────────────────────────────────────
Route::post('/api/woo-webhook', [\App\Http\Controllers\WooSyncController::class, 'webhook'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
    ->name('woo.webhook');

// ─── Rifa Bot API ─────────────────────────────────────────────────────────────
$nocsrf = [\App\Http\Middleware\VerifyCsrfToken::class];
Route::get('/rifas/ticket-design',             [RifaController::class, 'ticketDesign'])->name('rifas.ticket.design');
Route::get('/rifas/{venta}/ticket-preview',    [RifaController::class, 'ticketPreview'])->name('rifas.ticket.preview');
Route::get('/wa/rifas',                        [RifaController::class, 'botList'])->withoutMiddleware($nocsrf);
Route::post('/wa/rifa-order',                  [RifaController::class, 'botCreateOrder'])->withoutMiddleware($nocsrf);
Route::post('/wa/rifa/save',                   [RifaController::class, 'botSave'])->withoutMiddleware($nocsrf);
Route::post('/wa/rifa/{venta}/payment-proof',  [RifaController::class, 'botPaymentProof'])->withoutMiddleware($nocsrf);
Route::post('/wa/rifa/{venta}/data',           [RifaController::class, 'botUpdateData'])->withoutMiddleware($nocsrf);

// ─── Rifa Panel Admin ─────────────────────────────────────────────────────────
Route::middleware(['auth', \App\Http\Middleware\SetActiveProject::class])->group(function () {
    Route::get('/rifas/ventas-json',             [RifaController::class, 'ventasJson'])->name('rifas.ventas-json');
    Route::get('/rifas',                         [RifaController::class, 'index'])->name('rifas.index');
    Route::post('/rifas/catalog',                [RifaController::class, 'rifaStore'])->name('rifas.store');
    Route::put('/rifas/catalog/{rifa}',          [RifaController::class, 'rifaUpdate'])->name('rifas.update');
    Route::delete('/rifas/catalog/{rifa}',       [RifaController::class, 'rifaDestroy'])->name('rifas.destroy');
    Route::post('/rifas/{venta}/confirmar',      [RifaController::class, 'confirmarPago'])->name('rifas.confirmar');
    Route::post('/rifas/{venta}/enviar',         [RifaController::class, 'enviarTicket'])->name('rifas.enviar');
    Route::post('/rifas/{venta}/cancelar',       [RifaController::class, 'cancelar'])->name('rifas.cancelar');
    // Compatibilidad
    Route::post('/rifas/{venta}/validar',        [RifaController::class, 'validar'])->name('rifas.validar');
});

// ─── Pagos del catálogo ───────────────────────────────────────────────────────
Route::post('/{slug}/pay/{order}/manual', [PaymentController::class, 'confirmManual'])->name('public.pay.manual')->where('slug', '(?!' . $reserved . '$)[a-z0-9-]+');
Route::post('/{slug}/pay/{order}/culqi',  [PaymentController::class, 'chargeCulqi'])->name('public.pay.culqi')->where('slug', '(?!' . $reserved . '$)[a-z0-9-]+');
Route::post('/{slug}/pay/{order}/mp',     [PaymentController::class, 'createMpPreference'])->name('public.pay.mp')->where('slug', '(?!' . $reserved . '$)[a-z0-9-]+');
Route::post('/{slug}/mp-webhook',         [PaymentController::class, 'mpWebhook'])->name('public.mp.webhook')->where('slug', '(?!' . $reserved . '$)[a-z0-9-]+')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// ─── Portal comercial del cliente ─────────────────────────────────────────────
Route::get('/b/{slug}',           [PortalController::class, 'home'])->name('portal.home');
Route::get('/b/{slug}/c/{token}', [PortalController::class, 'quote'])->name('portal.quote');
Route::post('/b/{slug}/c/{token}/accept', [PortalController::class, 'accept'])->name('portal.quote.accept')->middleware('throttle:10,1');
Route::post('/b/{slug}/c/{token}/reject', [PortalController::class, 'reject'])->name('portal.quote.reject')->middleware('throttle:10,1');
Route::post('/b/{slug}/c/{token}/proof',  [PortalController::class, 'proof'])->name('portal.quote.proof')->middleware('throttle:10,1');

// ─── Catálogo público del revendedor ─────────────────────────────────────────
Route::get('/r/{slug}', [\App\Http\Controllers\ResellerController::class, 'catalogoPublico'])->name('reseller.catalogo.publico');

// ─── Propuesta comercial pública (la abre el cliente y la guarda como PDF) ────
Route::get('/propuesta/{token}', [\App\Http\Controllers\ProposalController::class, 'publica'])->name('proposal.publica');

// ─── Portal Facturación ───────────────────────────────────────────────────────
use App\Http\Controllers\Facturacion\AuthController as FacAuthController;
use App\Http\Controllers\Facturacion\DashboardController as FacDashController;
use App\Http\Controllers\Comercial\AuthController as ComAuthController;
use App\Http\Controllers\Comercial\DashboardController as ComDashController;
use App\Http\Controllers\PasswordResetPortalController;

// ─── Reset de contraseña — portales ──────────────────────────────────────────
Route::get('/portal/{portal}/forgot-password',         [PasswordResetPortalController::class, 'showForgot'])->name('portal.password.request')->where('portal', 'comercial|comunicaciones|facturacion|admin');
Route::post('/portal/{portal}/forgot-password',        [PasswordResetPortalController::class, 'sendReset'])->name('portal.password.send')->middleware('throttle:5,1')->where('portal', 'comercial|comunicaciones|facturacion|admin');
Route::get('/portal/{portal}/reset-password/{token}', [PasswordResetPortalController::class, 'showReset'])->name('portal.password.reset')->where('portal', 'comercial|comunicaciones|facturacion|admin');
Route::post('/portal/{portal}/reset-password',         [PasswordResetPortalController::class, 'updatePassword'])->name('portal.password.update')->where('portal', 'comercial|comunicaciones|facturacion|admin');


// ─── BixoFact — login genérico con desplegable de negocios ──────────────────
Route::get('/bixofact',        [FacAuthController::class, 'showLoginGeneral'])->name('bixofact.login');
Route::post('/bixofact/login', [FacAuthController::class, 'loginGeneral'])->middleware('throttle:10,1')->name('bixofact.login.post');

Route::prefix('f/{slug}')->name('facturacion.')->group(function () {
    Route::get('/login',  [FacAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [FacAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.post');
    Route::post('/logout',[FacAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'facturacion.auth'])->group(function () {
        Route::get('/', [FacDashController::class, 'index'])->name('dashboard');

        Route::get('/pos',  [PosController::class, 'indexPortal'])->name('pos');
        Route::post('/pos', [PosController::class, 'storePortal'])->name('pos.store');

        Route::get('/pedidos',            [OrderController::class, 'indexPortal'])->name('pedidos');
        Route::post('/pedidos',           [OrderController::class, 'storePortal'])->name('pedidos.store');
        Route::get('/pedidos/{order}',    [OrderController::class, 'showPortal'])->name('pedidos.show');
        Route::put('/pedidos/{order}',    [OrderController::class, 'updatePortal'])->name('pedidos.update');
        Route::delete('/pedidos/{order}', [OrderController::class, 'destroy'])->name('pedidos.destroy');

        Route::get('/cotizaciones',                       [QuoteController::class, 'indexPortal'])->name('cotizaciones');
        Route::get('/cotizaciones/create',                [QuoteController::class, 'createPortal'])->name('cotizaciones.create');
        Route::post('/cotizaciones',                      [QuoteController::class, 'storePortal'])->name('cotizaciones.store');
        Route::get('/cotizaciones/{id}',                  [QuoteController::class, 'showPortal'])->name('cotizaciones.show');
        Route::get('/cotizaciones/{id}/edit',             [QuoteController::class, 'editPortal'])->name('cotizaciones.edit');
        Route::put('/cotizaciones/{id}',                  [QuoteController::class, 'updatePortal'])->name('cotizaciones.update');
        Route::put('/cotizaciones/{id}/full',             [QuoteController::class, 'updateFullPortal'])->name('cotizaciones.update_full');
        Route::delete('/cotizaciones/{id}',               [QuoteController::class, 'destroyPortal'])->name('cotizaciones.destroy');
        Route::post('/cotizaciones/{id}/convertir',       [QuoteController::class, 'convertirPortal'])->name('cotizaciones.convertir');

        Route::get('/ruc',                      [InvoiceController::class, 'lookupRuc'])->name('ruc.lookup');
        Route::get('/boletas',                  [InvoiceController::class, 'indexBoletasPortal'])->name('boletas');
        Route::get('/boletas/create',           [InvoiceController::class, 'createBoletaPortal'])->name('boletas.create');
        Route::get('/facturas',                 [InvoiceController::class, 'indexFacturasPortal'])->name('facturas');
        Route::get('/facturas/create',          [InvoiceController::class, 'createFacturaPortal'])->name('facturas.create');
        Route::post('/comprobantes',            [InvoiceController::class, 'storePortal'])->name('facturas.store');
        Route::get('/facturas/{invoice}',     [InvoiceController::class, 'showPortal'])->name('facturas.show');
        Route::put('/facturas/{invoice}',     [InvoiceController::class, 'updatePortal'])->name('facturas.update');
        Route::delete('/facturas/{invoice}',  [InvoiceController::class, 'destroyPortal'])->name('facturas.destroy');
        Route::get('/facturas/{invoice}/pdf',   [InvoiceController::class, 'pdfPortal'])->name('facturas.pdf');
        Route::post('/facturas/{invoice}/sunat',[InvoiceController::class, 'sendSunatPortal'])->name('facturas.sunat');

        Route::get('/clientes',            [ClientController::class, 'indexPortal'])->name('clientes');
        Route::post('/clientes',           [ClientController::class, 'storePortal'])->name('clientes.store');
        Route::put('/clientes/{client}',   [ClientController::class, 'updatePortal'])->name('clientes.update');
        Route::delete('/clientes/{client}',[ClientController::class, 'destroyPortal'])->name('clientes.destroy');
    });
});

// ─── WhatsApp Webhooks (públicos, sin auth) ───────────────────────────────────
Route::get('/wa/webhook/{slug}',  [WaWebhookController::class, 'verify'])->name('wa.webhook.verify');
Route::post('/wa/webhook/{slug}', [WaWebhookController::class, 'receive'])->name('wa.webhook.receive')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// ─── Meta WhatsApp webhook directo ───────────────────────────────────────────
Route::get('/whatsapp/webhook',  [WaWebhookController::class, 'verifyMeta'])->name('wa.meta.verify');
Route::post('/whatsapp/webhook', [WaWebhookController::class, 'receiveMeta'])->name('wa.meta.receive')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// ─── Portal Comunicaciones ────────────────────────────────────────────────────
Route::prefix('bixocrm')->name('bixocrm.')->group(function () {
    Route::get('/login',  [ComWaAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [ComWaAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.post');
    Route::post('/logout',[ComWaAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'comunicaciones.auth'])->group(function () {
        Route::get('/',                              [BandejaController::class, 'index'])->name('bandeja');
        Route::get('/poll',                          [BandejaController::class, 'poll'])->name('poll');
        // Cambiar de negocio activo dentro del CRM (selector)
        Route::post('/cambiar-negocio',              [ComWaAuthController::class, 'cambiarProyecto'])->name('cambiar.negocio');
        // Estado + QR del bot WhatsApp (Baileys) — el frontend hace polling
        Route::get('/bots/wa-status',                [\App\Http\Controllers\Comunicaciones\BotBuilderPortalController::class, 'waStatus'])->name('bots.wa.status');
        // Ficha CRM del lead (columna derecha de la bandeja) — antes de {conversacion}
        Route::post('/lead',                         [ClientesCrmController::class, 'lead'])->name('lead');
        Route::post('/lead/{id}/etapa',              [ClientesCrmController::class, 'leadEtapa'])->name('lead.etapa');
        Route::get('/{conversacion}/mensajes',       [BandejaController::class, 'mensajes'])->name('mensajes');
        Route::post('/{conversacion}/enviar',        [BandejaController::class, 'enviar'])->name('enviar');
        Route::patch('/{conversacion}',              [BandejaController::class, 'actualizar'])->name('actualizar');

        Route::get('/clientes',                      [ClientesCrmController::class, 'index'])->name('clientes');

        Route::get('/configuracion',                 [CanalesController::class, 'index'])->name('configuracion');
        Route::post('/canales',                      [CanalesController::class, 'guardar'])->name('canales.guardar');
        Route::delete('/canales/{canal}',            [CanalesController::class, 'eliminar'])->name('canales.eliminar');

        // Chatbot
        Route::get('/chatbot',                       [CanalesController::class, 'chatbot'])->name('chatbot');

        // Constructor visual de bots (nuevo) dentro del portal CRM
        Route::get('/bots',            [\App\Http\Controllers\Comunicaciones\BotBuilderPortalController::class, 'index'])->name('bots.index');
        Route::get('/bots/nuevo',      [\App\Http\Controllers\Comunicaciones\BotBuilderPortalController::class, 'editor'])->name('bots.editor.new');
        Route::get('/bots/{id}',       [\App\Http\Controllers\Comunicaciones\BotBuilderPortalController::class, 'editor'])->name('bots.editor');
        Route::post('/bots/{id}',      [\App\Http\Controllers\Comunicaciones\BotBuilderPortalController::class, 'save'])->name('bots.save');
        Route::post('/bots/{id}/test', [\App\Http\Controllers\Comunicaciones\BotBuilderPortalController::class, 'test'])->name('bots.test');
        Route::delete('/bots/{id}',    [\App\Http\Controllers\Comunicaciones\BotBuilderPortalController::class, 'destroy'])->name('bots.destroy');
        Route::post('/chatbot/flows',                [CanalesController::class, 'guardarFlow'])->name('chatbot.guardar');
        Route::delete('/chatbot/flows/{flow}',       [CanalesController::class, 'eliminarFlow'])->name('chatbot.eliminar');
        Route::patch('/chatbot/toggle/{id}',         [CanalesController::class, 'toggleBot'])->name('chatbot.toggle');
    });
});

// ─── Portal Comercial ─────────────────────────────────────────────────────────
Route::prefix('bixosales')->name('bixosales.')->group(function () {
    Route::get('/login',         [ComAuthController::class, 'showLogin'])->name('login');
    Route::post('/login',        [ComAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.post');
    Route::post('/get-projects', [ComAuthController::class, 'getProjects'])->middleware('throttle:10,1')->name('get.projects');
    Route::post('/logout',       [ComAuthController::class, 'logout'])->name('logout');

    Route::middleware(['comercial.auth'])->group(function () {
        Route::get('/',             [ComDashController::class, 'index'])->name('dashboard');

        Route::get('/pos',  [PosController::class, 'indexComercial'])->name('pos');
        Route::get('/venta-express', [PosController::class, 'express'])->name('ventas.express');
        Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
        Route::post('/pos/cotizar', [PosController::class, 'quote'])->name('pos.quote');

        // Aprobación de pagos Yape/Plin (pedidos del bot en revisión)
        Route::post('/pagos/pendientes', [\App\Http\Controllers\Api\PagoController::class, 'pendientes'])->name('pagos.pendientes');
        Route::post('/pagos/aprobar',    [\App\Http\Controllers\Api\PagoController::class, 'aprobar'])->name('pagos.aprobar');
        Route::post('/pagos/rechazar',   [\App\Http\Controllers\Api\PagoController::class, 'rechazar'])->name('pagos.rechazar');

        // Revendedor: sus precios propios + su catálogo compartible
        Route::get('/revendedor/precios',   [\App\Http\Controllers\ResellerController::class, 'misPrecios'])->name('reseller.precios');
        Route::post('/revendedor/precio',   [\App\Http\Controllers\ResellerController::class, 'guardarPrecio'])->name('reseller.precio.guardar');
        Route::post('/revendedor/catalogo', [\App\Http\Controllers\ResellerController::class, 'toggleCatalogo'])->name('reseller.catalogo.toggle');

        Route::get('/pedidos-bot',                  [RifaController::class, 'indexComercial'])->name('rifas');
        Route::get('/pedidos-bot/monitoreo',         [RifaController::class, 'monitoreo'])->name('rifas.monitoreo');
        Route::get('/pedidos-bot/exportar',          [RifaController::class, 'exportarComercial'])->name('rifas.exportar');
        Route::post('/pedidos-bot/{venta}/validar', [RifaController::class, 'confirmarPago'])->name('rifas.validar');
        Route::post('/pedidos-bot/{venta}/enviar',  [RifaController::class, 'enviarTicket'])->name('rifas.enviar');
        Route::post('/pedidos-bot/{venta}/cancelar',[RifaController::class, 'cancelar'])->name('rifas.cancelar');
        Route::post('/pedidos-bot/{venta}/editar',   [RifaController::class, 'editarComercial'])->name('rifas.editar');
        Route::post('/pedidos-bot/{venta}/eliminar',  [RifaController::class, 'eliminarComercial'])->name('rifas.eliminar');
        Route::post('/pedidos-bot/{venta}/recordar',  [RifaController::class, 'recordar'])->name('rifas.recordar');
        Route::post('/pedidos-bot/{venta}/enviar-membresia', [RifaController::class, 'enviarConMembresia'])->name('rifas.enviar.membresia');
        Route::post('/pedidos-bot/nuevo-manual', [RifaController::class, 'nuevoManual'])->name('rifas.nuevo-manual');
        Route::get('/consultar-dni/{dni}', [RifaController::class, 'consultarDni'])->name('rifas.consultar-dni');

        // WooCommerce
        Route::get('/woo/orders',  [\App\Http\Controllers\WooSyncController::class, 'index'])->name('woo.orders');
        Route::post('/woo/sync',   [\App\Http\Controllers\WooSyncController::class, 'sync'])->name('woo.sync');
        Route::get('/woo/stats',   [\App\Http\Controllers\WooSyncController::class, 'stats'])->name('woo.stats');

        // Tickets manuales WordPress
        Route::get('/conversaciones', [\App\Http\Controllers\Comercial\ConversacionesController::class, 'index'])->name('conversaciones');
        Route::get('/conversaciones/{id}/mensajes', [\App\Http\Controllers\Comercial\ConversacionesController::class, 'mensajes'])->name('conversaciones.mensajes');
        Route::get('/tickets-manuales', [\App\Http\Controllers\TicketsWpController::class, 'index'])->name('tickets.wp');
        Route::get('/tickets-manuales/buscar', [\App\Http\Controllers\TicketsWpController::class, 'buscar'])->name('tickets.wp.buscar');
        Route::post('/tickets-manuales/eliminar', [\App\Http\Controllers\TicketsWpController::class, 'eliminar'])->name('tickets.wp.eliminar');

        Route::get('/pedidos',                [OrderController::class, 'index'])->name('pedidos');
        Route::post('/pedidos',               [OrderController::class, 'store'])->name('pedidos.store');
        Route::get('/pedidos/{order}',        [OrderController::class, 'show'])->name('pedidos.show');
        Route::put('/pedidos/{order}',        [OrderController::class, 'update'])->name('pedidos.update');
        Route::delete('/pedidos/{order}',     [OrderController::class, 'destroy'])->name('pedidos.destroy');
        Route::post('/pedidos/{order}/wa-action',   [WaBotController::class, 'portalAction'])->name('pedidos.wa.action');
        Route::post('/pedidos/{order}/wa-delivery', [WaBotController::class, 'updateDelivery'])->name('pedidos.wa.delivery');
        Route::patch('/pedidos/{order}/kitchen',    [OrderController::class, 'updateKitchen'])->name('pedidos.kitchen');
        Route::get('/cocina',                       [OrderController::class, 'kitchen'])->name('cocina');
        Route::get('/mesas',                        [MesaController::class, 'index'])->name('mesas');
        Route::get('/mesas/data',                   [MesaController::class, 'data'])->name('mesas.data');

        // ── MAPA OPERATIVO ────────────────────────────────────────────────────
        Route::prefix('mapa')->name('mapa.')->group(function () {
            Route::get('/',                                   [OperationalMapController::class, 'index'])->name('index');
            Route::get('/maps/{map}/objects',                 [OperationalMapController::class, 'objects'])->name('objects');
            Route::post('/maps',                              [OperationalMapController::class, 'storemap'])->name('maps.store');
            Route::post('/maps/{map}/objects',               [OperationalMapController::class, 'storeObject'])->name('objects.store');
            Route::patch('/objects/{object}/move',            [OperationalMapController::class, 'move'])->name('objects.move');
            Route::patch('/objects/{object}/status',          [OperationalMapController::class, 'changeStatus'])->name('objects.status');
            Route::patch('/objects/{object}/amount',          [OperationalMapController::class, 'updateAmount'])->name('objects.amount');
            Route::patch('/objects/{object}/responsible',     [OperationalMapController::class, 'assignResponsible'])->name('objects.responsible');
            Route::post('/objects/{object}/alerts',           [OperationalMapController::class, 'addAlert'])->name('objects.alerts.add');
            Route::delete('/objects/{object}/alerts',         [OperationalMapController::class, 'clearAlerts'])->name('objects.alerts.clear');
            Route::post('/objects/{object}/requests',         [OperationalMapController::class, 'createRequest'])->name('objects.requests.store');
            Route::get('/objects/{object}/history',           [OperationalMapController::class, 'history'])->name('objects.history');
            Route::put('/objects/{object}',                   [OperationalMapController::class, 'updateObject'])->name('objects.update');
            Route::delete('/objects/{object}',                [OperationalMapController::class, 'destroyObject'])->name('objects.destroy');
        });

        // Reservas
        Route::get('/reservas',                     [ReservaController::class, 'index'])->name('reservas');
        Route::post('/reservas',                    [ReservaController::class, 'store'])->name('reservas.store');
        Route::put('/reservas/{appointment}',       [ReservaController::class, 'update'])->name('reservas.update');
        Route::delete('/reservas/{appointment}',    [ReservaController::class, 'destroy'])->name('reservas.destroy');
        Route::get('/reservas/calendar',            [ReservaController::class, 'calendar'])->name('reservas.calendar');

        // Delivery
        Route::get('/delivery',                     [DeliveryController::class, 'index'])->name('delivery');
        Route::get('/delivery/data',                [DeliveryController::class, 'data'])->name('delivery.data');
        Route::post('/delivery',                    [DeliveryController::class, 'store'])->name('delivery.store');
        Route::put('/delivery/{order}/status',      [DeliveryController::class, 'updateStatus'])->name('delivery.status');

        // Caja / Tesorería
        Route::get('/caja',                         [CajaController::class, 'index'])->name('caja');
        Route::post('/caja/abrir',                  [CajaController::class, 'abrir'])->name('caja.abrir');
        Route::post('/caja/{caja}/cerrar',          [CajaController::class, 'cerrar'])->name('caja.cerrar');
        Route::post('/caja/{caja}/movimiento',      [CajaController::class, 'movimiento'])->name('caja.movimiento');
        Route::get('/caja/{caja}/data',             [CajaController::class, 'data'])->name('caja.data');

        Route::get('/cotizaciones',           [QuoteController::class, 'index'])->name('cotizaciones');
        Route::post('/cotizaciones',          [QuoteController::class, 'store'])->name('cotizaciones.store');
        Route::get('/cotizaciones/{quote}',   [QuoteController::class, 'show'])->name('cotizaciones.show');
        Route::put('/cotizaciones/{quote}',   [QuoteController::class, 'update'])->name('cotizaciones.update');
        Route::put('/cotizaciones/{quote}/full', [QuoteController::class, 'updateFull'])->name('cotizaciones.update_full');
        Route::delete('/cotizaciones/{quote}',[QuoteController::class, 'destroy'])->name('cotizaciones.destroy');
        Route::post('/cotizaciones/{quote}/send', [QuoteController::class, 'send'])->name('cotizaciones.send');
        Route::post('/cotizaciones/{quote}/duplicate', [QuoteController::class, 'duplicate'])->name('cotizaciones.duplicate');
        Route::post('/cotizaciones/{quote}/seen',      [QuoteController::class, 'markSeen'])->name('cotizaciones.seen');

        Route::get('/facturas',               [InvoiceController::class, 'index'])->name('facturas');
        Route::post('/facturas',              [InvoiceController::class, 'store'])->name('facturas.store');
        Route::get('/facturas/{invoice}',     [InvoiceController::class, 'show'])->name('facturas.show');
        Route::put('/facturas/{invoice}',     [InvoiceController::class, 'update'])->name('facturas.update');
        Route::delete('/facturas/{invoice}',  [InvoiceController::class, 'destroy'])->name('facturas.destroy');
        Route::get('/facturas/{invoice}/pdf',    [InvoiceController::class, 'pdf'])->name('facturas.pdf');
        Route::post('/facturas/{invoice}/sunat', [InvoiceController::class, 'sendSunat'])->name('facturas.sunat');

        Route::get('/clientes',               [ClientController::class, 'index'])->name('clientes');
        Route::post('/clientes',              [ClientController::class, 'store'])->name('clientes.store');
        Route::put('/clientes/{client}',      [ClientController::class, 'update'])->name('clientes.update');
        Route::delete('/clientes/{client}',   [ClientController::class, 'destroy'])->name('clientes.destroy');

        Route::get('/reportes/ventas-bot',    [ReporteController::class, 'ventasBot'])->name('reportes.ventas');
        Route::get('/reportes/seguimiento',   [ReporteController::class, 'seguimientoBot'])->name('reportes.seguimiento');
        Route::get('/reportes/ventas',        [ReporteController::class, 'ventas'])->name('reportes.ventas.general');
        Route::get('/reportes/top-productos', [ReporteController::class, 'topProductos'])->name('reportes.top.productos');
        Route::get('/reportes/rentabilidad',  [ReporteController::class, 'rentabilidad'])->name('reportes.rentabilidad');
        Route::get('/reportes/dashboard-data',[ReporteController::class, 'dashboardData'])->name('reportes.dashboard.data');
        Route::get('/reportes/inventario',    [ReporteController::class, 'inventario'])->name('reportes.inventario');
    });
});

require __DIR__.'/auth.php';
Route::get('/test-membresia', function(){ return 'OK'; });
