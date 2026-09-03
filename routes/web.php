<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\Catalog\ProductVariantController;
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
use App\Http\Controllers\Facturacion\NotaController;
use App\Http\Controllers\Facturacion\GuiaRemisionController;
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
    Route::patch('/bixoadmin/settings/{target}/toggle', [ProjectController::class, 'toggleStatus'])->name('projects.toggle')->middleware('can:settings.negocio');
    Route::post('/bixoadmin/settings/{target}/modules', [ProjectController::class, 'updateModules'])->name('projects.modules')->middleware('can:settings.negocio');

    // Panel del negocio — todas las rutas bajo /panel
    Route::prefix('bixoadmin')->middleware(['project.member'])->group(function () {

        // Inicio de la plataforma ADMIN: quien entra por /bixoadmin (o su
        // login propio) se queda en la plataforma admin — configuración del
        // negocio. Son 2 plataformas: entrar por admin NUNCA aterriza en
        // sales (decisión del usuario 2026-08-30; corrige el aterrizaje
        // "Inicio neutro" que duró unas horas y mandaba a /bixosales).
        Route::get('/', fn() => redirect()->route('settings'))->name('dashboard');
        Route::get('/dashboard', fn() => redirect()->route('settings'))->name('dashboard.alt');

        // Productos — /bixoadmin/products
        // Un permiso por verbo. Antes iba un unico can:catalog.ver sobre todo el
        // grupo, asi que un usuario de SOLO LECTURA podia crear, editar, importar,
        // hacer acciones masivas y hasta vaciar el catalogo entero. Mismo hueco
        // que 9b2bcc5 cerro en Pedidos y Cotizaciones; Catalogo se quedo fuera.
        Route::prefix('products')->middleware(['module:catalog'])->group(function () {
            // Lectura
            Route::middleware('can:catalog.ver')->group(function () {
                Route::get('/',               [ProductController::class, 'index'])->name('products.index');
                Route::get('/export',         [ProductController::class, 'export'])->name('products.export');
                Route::get('/catalog-pdf',    [ProductController::class, 'catalogPdf'])->name('products.catalog.pdf');
                Route::get('/template',       [ProductController::class, 'template'])->name('products.template');
                Route::get('/export/static',  [ProductController::class, 'exportStatic'])->name('products.export.static');
                Route::get('/export/meli',    [ProductController::class, 'exportMeli'])->name('products.export.meli');
                Route::get('/export/rappi',   [ProductController::class, 'exportRappi'])->name('products.export.rappi');
                Route::get('/export/shopee',  [ProductController::class, 'exportShopee'])->name('products.export.shopee');
                Route::get('/reviews',        [ProductController::class, 'reviews'])->name('reviews.index');
                Route::get('/{product}/variants', [ProductVariantController::class, 'show'])->name('products.variants.show');
            });
            // Alta
            Route::middleware('can:catalog.crear')->group(function () {
                Route::post('/',                   [ProductController::class, 'store'])->name('products.store');
                Route::post('/{product}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');
            });
            // Importacion masiva
            Route::post('/import', [ProductController::class, 'import'])->name('products.import')->middleware('can:catalog.importar');
            // Edicion
            Route::middleware('can:catalog.editar')->group(function () {
                Route::match(['put', 'patch'], '/{product}',    [ProductController::class, 'update'])->name('products.update');
                Route::put('/{product}/variants',               [ProductVariantController::class, 'update'])->name('products.variants.update');
                Route::post('/reorder',                        [ProductController::class, 'reorder'])->name('products.reorder');
                // bulkAction incluye 'delete': el propio metodo exige catalog.eliminar
                // para esa accion concreta, porque el permiso depende del payload.
                Route::post('/bulk-action',                    [ProductController::class, 'bulkAction'])->name('products.bulk-action');
                Route::post('/{product}/images',               [ProductController::class, 'uploadImage'])->name('products.images.upload');
                Route::delete('/{product}/images/{image}',     [ProductController::class, 'deleteImage'])->name('products.images.delete');
                Route::patch('/{product}/images/{image}/main', [ProductController::class, 'setMainImage'])->name('products.images.main');
            });
            // Borrado
            Route::middleware('can:catalog.eliminar')->group(function () {
                Route::delete('/purge-all',  [ProductController::class, 'purgeAll'])->name('products.purge-all');
                Route::delete('/{product}',  [ProductController::class, 'destroy'])->name('products.destroy');
            });
            // Moderacion de resenas
            Route::middleware('can:catalog.resenas')->group(function () {
                Route::patch('/reviews/{id}/approve', [ProductController::class, 'approveReview'])->name('reviews.approve');
                Route::delete('/reviews/{id}',        [ProductController::class, 'destroyReview'])->name('reviews.destroy');
            });
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
        Route::prefix('services')->middleware(['module:catalog'])->group(function () {
            Route::middleware('can:catalog.ver')->group(function () {
                Route::get('/',          [ServiceController::class, 'index'])->name('services.index');
                Route::get('/export',    [ServiceController::class, 'export'])->name('services.export');
                Route::get('/template',  [ServiceController::class, 'template'])->name('services.template');
            });
            Route::post('/import',  [ServiceController::class, 'import'])->name('services.import')->middleware('can:catalog.importar');
            Route::post('/',        [ServiceController::class, 'store'])->name('services.store')->middleware('can:catalog.crear');
            Route::middleware('can:catalog.editar')->group(function () {
                Route::post('/reorder',                     [ServiceController::class, 'reorder'])->name('services.reorder');
                Route::match(['put', 'patch'], '/{service}', [ServiceController::class, 'update'])->name('services.update');
            });
            Route::delete('/{service}', [ServiceController::class, 'destroy'])->name('services.destroy')->middleware('can:catalog.eliminar');
        });
        // Alias legacy
        Route::get('/catalog/services', fn() => redirect()->route('services.index'));

        // Categorías — /bixoadmin/categories
        Route::prefix('categories')->middleware(['module:catalog'])->group(function () {
            Route::middleware('can:catalog.ver')->group(function () {
                Route::get('/',          [CategoryController::class, 'index'])->name('categories.index');
                Route::get('/export',    [CategoryController::class, 'export'])->name('categories.export');
                Route::get('/template',  [CategoryController::class, 'template'])->name('categories.template');
            });
            Route::post('/import',  [CategoryController::class, 'import'])->name('categories.import')->middleware('can:catalog.importar');
            Route::post('/',        [CategoryController::class, 'store'])->name('categories.store')->middleware('can:catalog.crear');
            Route::middleware('can:catalog.editar')->group(function () {
                Route::post('/reorder',                      [CategoryController::class, 'reorder'])->name('categories.reorder');
                Route::match(['put', 'patch'], '/{category}', [CategoryController::class, 'update'])->name('categories.update');
            });
            Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy')->middleware('can:catalog.eliminar');
        });
        // Alias legacy
        Route::get('/catalog/categories', fn() => redirect()->route('categories.index'));

        // Clientes. Permiso GRANULAR por accion (UX2/seguridad): el resource
        // entero iba bajo can:clients.ver, asi que un lector podia crear,
        // editar y BORRAR clientes — la misma clase de agujero que el hotfix
        // "*.ver ya no autoriza escribir" cerro en Pedidos y Cotizaciones,
        // pero Clientes quedo fuera de aquel barrido.
        Route::get('/clients',                 [ClientController::class, 'index'])->name('clients')->middleware(['module:clients', 'can:clients.ver']);
        Route::get('/clients/create',          [ClientController::class, 'create'])->name('clients.create')->middleware(['module:clients', 'can:clients.crear']);
        Route::post('/clients',                [ClientController::class, 'store'])->name('clients.store')->middleware(['module:clients', 'can:clients.crear']);
        Route::get('/clients/{client}',        [ClientController::class, 'show'])->name('clients.show')->middleware(['module:clients', 'can:clients.ver']);
        Route::get('/clients/{client}/edit',   [ClientController::class, 'edit'])->name('clients.edit')->middleware(['module:clients', 'can:clients.editar']);
        Route::match(['put', 'patch'], '/clients/{client}', [ClientController::class, 'update'])->name('clients.update')->middleware(['module:clients', 'can:clients.editar']);
        Route::delete('/clients/{client}',     [ClientController::class, 'destroy'])->name('clients.destroy')->middleware(['module:clients', 'can:clients.eliminar']);
        // Portal del Cliente (F11): generar/regenerar el enlace es una escritura.
        Route::post('/clients/{client}/portal', [\App\Http\Controllers\PortalClienteController::class, 'generarEnlace'])->name('clients.portal')->middleware(['module:clients', 'can:clients.editar']);

        // CRM: pipeline de ventas (leads del Copilot)
        Route::get('/clients-pipeline', [ClientController::class, 'pipeline'])
            ->middleware(['module:clients', 'can:clients.ver'])->name('clients.pipeline');
        // Mover de etapa MUTA al cliente: exige edicion, no lectura.
        Route::patch('/clients/{client}/stage', [ClientController::class, 'moveStage'])
            ->middleware(['module:clients', 'can:clients.editar'])->name('clients.stage');

        // Dashboard Comercial (indicadores de negocio en tiempo real)
        // Sin `can:` cualquier miembro del proyecto veia facturacion del mes,
        // cuentas por cobrar, meta y **ranking de ventas por vendedor**. Su
        // propia ruta hermana `saveMeta` si exigia permiso, lo que delata el
        // olvido. Los 7 roles tienen `reports.ver`, asi que no deja fuera a
        // nadie legitimo.
        Route::get('/dashboard-comercial', [\App\Http\Controllers\DashboardComercialController::class, 'index'])->name('dashboard.comercial')->middleware('can:reports.ver');
        Route::post('/dashboard-comercial/meta', [\App\Http\Controllers\DashboardComercialController::class, 'saveMeta'])->name('dashboard.comercial.meta')->middleware('can:settings.negocio');

        // Copilot Empresarial (pregúntale a tu negocio en español)
        Route::get('/copilot',  [\App\Http\Controllers\CopilotEmpresarialController::class, 'index'])->name('copilot.index');
        Route::post('/copilot', [\App\Http\Controllers\CopilotEmpresarialController::class, 'preguntar'])->name('copilot.preguntar')->middleware('can:reports.ver');

        // Constructor visual de bots
        Route::get('/bots-flow',            [\App\Http\Controllers\BotFlowController::class, 'index'])->name('bot-flows.index');
        Route::get('/bots-flow/nuevo',      [\App\Http\Controllers\BotFlowController::class, 'editor'])->name('bot-flows.editor.new');
        Route::get('/bots-flow/wa-status',  [\App\Http\Controllers\BotFlowController::class, 'waStatus'])->name('bot-flows.wa-status');
        Route::post('/bots-flow/plantilla-tienda', [\App\Http\Controllers\BotFlowController::class, 'desdePlantilla'])->name('bot-flows.plantilla')->middleware('can:settings.negocio');
        Route::post('/bots-flow/plantilla-comercial', [\App\Http\Controllers\BotFlowController::class, 'desdePlantillaComercial'])->name('bot-flows.plantilla-comercial');
        Route::post('/bots-flow/ia', [\App\Http\Controllers\BotFlowController::class, 'toggleIa'])->name('bot-flows.ia');
        Route::get('/bots-flow/{flow}',     [\App\Http\Controllers\BotFlowController::class, 'editor'])->name('bot-flows.editor');
        Route::post('/bots-flow/{flow}',    [\App\Http\Controllers\BotFlowController::class, 'save'])->name('bot-flows.save')->middleware('can:settings.negocio');
        Route::post('/bots-flow/{flow}/test',[\App\Http\Controllers\BotFlowController::class, 'test'])->name('bot-flows.test')->middleware('can:settings.negocio');
        Route::post('/bots-flow/{flow}/restaurar',[\App\Http\Controllers\BotFlowController::class, 'restaurarPlantilla'])->name('bot-flows.restaurar')->middleware('can:settings.negocio');
        Route::delete('/bots-flow/{flow}',  [\App\Http\Controllers\BotFlowController::class, 'destroy'])->name('bot-flows.destroy')->middleware('can:settings.negocio');

        // Agenda
        // Un permiso por verbo: agenda.ver autorizaba tambien crear, mover y
        // BORRAR citas. No hay permiso agenda.eliminar en el sistema, asi que
        // borrar exige agenda.editar, que es el mismo nivel de autoridad.
        Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda')->middleware(['module:agenda', 'can:agenda.ver']);
        // Sin 'show'/'create'/'edit': esos metodos no existen en el controlador,
        // el resource los generaba igual y solo podian dar 500.
        Route::prefix('appointments')->middleware('module:agenda')->group(function () {
            Route::post('/',              [AgendaController::class, 'store'])->name('appointments.store')->middleware('can:agenda.crear');
            Route::match(['put', 'patch'], '/{appointment}', [AgendaController::class, 'update'])->name('appointments.update')->middleware('can:agenda.editar');
            Route::delete('/{appointment}', [AgendaController::class, 'destroy'])->name('appointments.destroy')->middleware('can:agenda.editar');
        });

        // Roles y permisos
        //
        // ESCALADA DE PRIVILEGIOS: estas rutas no exigian ningun permiso, asi que
        // cualquier MIEMBRO del proyecto (un vendedor, un solo_lectura) podia
        // llamar a update() y hacer syncPermissions() sobre su propio rol,
        // concediendose todos los permisos del sistema. authorizeProject() solo
        // comprueba pertenencia, no autoridad.
        //
        // Ademas los roles de Spatie son GLOBALES, no por proyecto: tocarlos
        // afecta a TODOS los negocios. Por eso mutarlos queda reservado a
        // roles.gestionar (que hoy solo tiene 'admin'), mas el dueño del
        // proyecto y el superadmin, que pasan por Gate::before.
        Route::get('/roles',           [RolePermissionController::class, 'index'])->name('roles.index')->middleware('can:roles.ver');
        Route::post('/roles',          [RolePermissionController::class, 'store'])->name('roles.store')->middleware('can:roles.gestionar');
        Route::put('/roles/{role}',    [RolePermissionController::class, 'update'])->name('roles.update')->middleware('can:roles.gestionar');
        Route::delete('/roles/{role}', [RolePermissionController::class, 'destroy'])->name('roles.destroy')->middleware('can:roles.gestionar');

        // Catálogos (listas de valores maestros)
        Route::get('/catalogs',                             [CatalogListController::class, 'index'])->name('catalogs.index');
        Route::post('/catalogs',                            [CatalogListController::class, 'store'])->name('catalogs.store')->middleware('can:settings.catalogos');
        Route::put('/catalogs/{catalog}',                   [CatalogListController::class, 'update'])->name('catalogs.update')->middleware('can:settings.catalogos');
        Route::delete('/catalogs/{catalog}',                [CatalogListController::class, 'destroy'])->name('catalogs.destroy')->middleware('can:settings.catalogos');
        Route::get('/catalogs/{catalog}/values',            [CatalogListController::class, 'values'])->name('catalogs.values');
        Route::post('/catalogs/{catalog}/values',           [CatalogListController::class, 'storeValue'])->name('catalogs.values.store')->middleware('can:settings.catalogos');
        Route::put('/catalogs/{catalog}/values/{value}',    [CatalogListController::class, 'updateValue'])->name('catalogs.values.update')->middleware('can:settings.catalogos');
        Route::delete('/catalogs/{catalog}/values/{value}', [CatalogListController::class, 'destroyValue'])->name('catalogs.values.destroy')->middleware('can:settings.catalogos');

        // Proyectos (panel 3 columnas) — rutas movidas fuera del grupo project.member

        // Constructor Bot
        Route::get('/bot-builder',                      fn() => view('bot-builder.index'))->name('bot-builder.index');

        // Bots WhatsApp
        Route::get('/bots',                             [BotStatusController::class, 'index'])->name('bots.index');
        Route::get('/bots/status',                      [BotStatusController::class, 'status'])->name('bots.status');
        Route::get('/bots/flow',                        [BotStatusController::class, 'flowIndex'])->name('bots.flow');
        Route::post('/bots/flow',                       [BotStatusController::class, 'flowStore'])->name('bots.flow.store')->middleware('can:settings.negocio');
        Route::post('/bots/states',                     [BotStatusController::class, 'stateStore'])->name('bots.states.store')->middleware('can:settings.negocio');
        Route::put('/bots/states/{state}',              [BotStatusController::class, 'stateUpdate'])->name('bots.states.update')->middleware('can:settings.negocio');
        Route::delete('/bots/states/{state}',           [BotStatusController::class, 'stateDestroy'])->name('bots.states.destroy')->middleware('can:settings.negocio');
        Route::post('/bots/states/{state}/move',        [BotStatusController::class, 'stateMove'])->name('bots.states.move')->middleware('can:settings.negocio');
        Route::post('/bots/transitions',                [BotStatusController::class, 'transitionStore'])->name('bots.transitions.store')->middleware('can:settings.negocio');
        Route::delete('/bots/transitions/{transition}', [BotStatusController::class, 'transitionDestroy'])->name('bots.transitions.destroy')->middleware('can:settings.negocio');
        Route::post('/bots/config',                     [BotStatusController::class, 'configSave'])->name('bots.config.save')->middleware('can:settings.negocio');
        Route::post('/bots/control',                    [BotStatusController::class, 'botControl'])->name('bots.control')->middleware('can:settings.negocio');
        Route::post('/bots/reset-session',              [BotStatusController::class, 'resetSession'])->name('bots.reset-session')->middleware('can:settings.negocio');
        Route::get('/bots/logs',                        [BotStatusController::class, 'botLogs'])->name('bots.logs');
        Route::post('/bots/upload-image',               [BotStatusController::class, 'uploadImage'])->name('bots.upload.image')->middleware('can:settings.negocio');
        Route::post('/bots/instances',                  [BotStatusController::class, 'botStore'])->name('bots.instances.store')->middleware('can:settings.negocio');
        Route::delete('/bots/instances/{bot}',          [BotStatusController::class, 'botDestroy'])->name('bots.instances.destroy')->middleware('can:settings.negocio');
        Route::get('/bots/espera-asesor',               [BotStatusController::class, 'esperaAsesor'])->name('bots.espera-asesor');
        Route::post('/bots/flow/import-json',           [BotStatusController::class, 'flowImportFromJson'])->name('bots.flow.import')->middleware('can:settings.negocio');

        // HR / Empleados
        Route::get('/hr/employees',                [HRController::class, 'index'])->name('hr.employees.index')->middleware('can:hr.ver');
        Route::post('/hr/employees',               [HRController::class, 'store'])->name('hr.employees.store')->middleware('can:hr.crear');
        Route::put('/hr/employees/{employee}',     [HRController::class, 'update'])->name('hr.employees.update')->middleware('can:hr.editar');
        Route::delete('/hr/employees/{employee}',  [HRController::class, 'destroy'])->name('hr.employees.destroy')->middleware('can:hr.eliminar');

        // Asistencia y Comisiones
        Route::get('/hr/asistencia',               [\App\Http\Controllers\AttendanceController::class, 'index'])->name('hr.attendance.index')->middleware('can:attendance.ver');
        // Escritura autorizada por un permiso de LECTURA: `store` hace
        // updateOrCreate de asistencias, y esas horas alimentan el calculo de
        // comisiones (`AttendanceController::159`). Quien solo podia MIRAR la
        // asistencia podia FABRICARLA, y con ella la comision a pagar.
        // `attendance.editar` ya existe y lo tienen admin, rrhh y gerente; el
        // unico que pierde algo es `solo_lectura`, que es justo el objetivo.
        Route::post('/hr/asistencia',              [\App\Http\Controllers\AttendanceController::class, 'store'])->name('hr.attendance.store')->middleware('can:attendance.editar');
        Route::post('/hr/asistencia/check-in',     [\App\Http\Controllers\AttendanceController::class, 'checkIn'])->name('hr.attendance.checkin')->middleware('can:attendance.fichar');
        Route::post('/hr/asistencia/check-out',    [\App\Http\Controllers\AttendanceController::class, 'checkOut'])->name('hr.attendance.checkout')->middleware('can:attendance.fichar');
        Route::get('/hr/comisiones',               [\App\Http\Controllers\AttendanceController::class, 'comisiones'])->name('hr.comisiones.index')->middleware('can:attendance.ver');

        // Sedes
        Route::get('/company/sedes',           [SedeController::class, 'index'])->name('sedes.index');
        Route::post('/company/sedes',          [SedeController::class, 'store'])->name('sedes.store')->middleware('can:settings.negocio');
        Route::put('/company/sedes/{sede}',    [SedeController::class, 'update'])->name('sedes.update')->middleware('can:settings.negocio');
        Route::delete('/company/sedes/{sede}', [SedeController::class, 'destroy'])->name('sedes.destroy')->middleware('can:settings.negocio');

        // Grupos de usuarios
        Route::get('/company/groups',                [UserGroupController::class, 'index'])->name('groups.index');
        Route::post('/company/groups',               [UserGroupController::class, 'store'])->name('groups.store')->middleware('can:settings.negocio');
        Route::put('/company/groups/{userGroup}',    [UserGroupController::class, 'update'])->name('groups.update')->middleware('can:settings.negocio');
        Route::delete('/company/groups/{userGroup}', [UserGroupController::class, 'destroy'])->name('groups.destroy')->middleware('can:settings.negocio');

        // Inventario y Kardex
        // Inventario tiene modulo y permisos propios (`inventory.*`), pero las
        // rutas se gateaban con los del catalogo. Se aceptan ambos pares: los
        // 3 roles que solo tienen `catalog.ver` y el negocio que aun no
        // activo el modulo `inventory` conservan el acceso, y el permiso
        // propio empieza a valer desde ya.
        Route::get('/inventario',                  [\App\Http\Controllers\InventoryController::class, 'index'])->name('inventory.index')->middleware(['module:inventory|catalog', 'project.can:inventory.ver|catalog.ver']);
        Route::post('/inventario/movimiento',      [\App\Http\Controllers\InventoryController::class, 'store'])->name('inventory.store')->middleware(['module:inventory|catalog', 'project.can:inventory.editar|catalog.editar']);
        Route::get('/inventario/{product}/kardex', [\App\Http\Controllers\InventoryController::class, 'kardex'])->name('inventory.kardex')->middleware(['module:inventory|catalog', 'project.can:inventory.ver|catalog.ver']);

        // Proveedores
        // proveedores.ver / proveedores.editar ya existian en la tabla de permisos
        // y aparecian en Roles, pero ninguna ruta los usaba: cualquier miembro
        // del proyecto podia crear, editar y borrar proveedores.
        Route::get('/company/proveedores',                    [ProveedorController::class, 'index'])->name('proveedores.index')->middleware('can:proveedores.ver');
        Route::get('/company/proveedores/template',           [ProveedorController::class, 'template'])->name('proveedores.template')->middleware('can:proveedores.ver');
        Route::get('/company/proveedores/export',             [ProveedorController::class, 'export'])->name('proveedores.export')->middleware('can:proveedores.ver');
        Route::middleware('can:proveedores.editar')->group(function () {
            Route::post('/company/proveedores',               [ProveedorController::class, 'store'])->name('proveedores.store');
            Route::put('/company/proveedores/{proveedor}',    [ProveedorController::class, 'update'])->name('proveedores.update');
            Route::delete('/company/proveedores/{proveedor}', [ProveedorController::class, 'destroy'])->name('proveedores.destroy');
            Route::post('/company/proveedores/import',        [ProveedorController::class, 'import'])->name('proveedores.import');
        });

        // Combos
        Route::get('/combos',                        [ComboController::class, 'index'])->name('combos.index');
        Route::post('/combos',                       [ComboController::class, 'store'])->name('combos.store')->middleware('can:catalog.editar');
        Route::put('/combos/{combo}',                [ComboController::class, 'update'])->name('combos.update')->middleware('can:catalog.editar');
        Route::delete('/combos/{combo}',             [ComboController::class, 'destroy'])->name('combos.destroy')->middleware('can:catalog.eliminar');
        Route::patch('/combos/{combo}/toggle',       [ComboController::class, 'toggleAvailable'])->name('combos.toggle')->middleware('can:catalog.editar');

        // Promociones
        Route::get('/promotions',                    [PromotionController::class, 'index'])->name('promotions.index');
        Route::post('/promotions',                   [PromotionController::class, 'store'])->name('promotions.store')->middleware('can:catalog.editar');
        Route::put('/promotions/{promotion}',        [PromotionController::class, 'update'])->name('promotions.update')->middleware('can:catalog.editar');
        Route::delete('/promotions/{promotion}',     [PromotionController::class, 'destroy'])->name('promotions.destroy')->middleware('can:catalog.eliminar');
        Route::patch('/promotions/{promotion}/toggle',[PromotionController::class, 'toggle'])->name('promotions.toggle')->middleware('can:catalog.editar');

        // Comunicaciones / WhatsApp — redirige al portal bixocrm o a settings
        Route::prefix('bixocrm')->group(function () {
            Route::get('/configuracion', fn() => redirect()->route('settings', ['s' => 'whatsapp']));
            Route::get('/{any}',         fn() => redirect()->route('bixocrm.bandeja'))->where('any', '.*');
            Route::get('/',              fn() => redirect()->route('bixocrm.bandeja'));
        });

        // Configuración
        Route::get('/settings',          [SettingsController::class, 'index'])->name('settings');
        Route::post('/settings',         [SettingsController::class, 'update'])->name('settings.update')->middleware('can:settings.negocio');
        // DEPRECATED_CANDIDATE (matriz de capacidades): fuera del menú; se
        // endurecen con permiso de diseño (project.can respeta dueño/superadmin).
        Route::get('/settings/design',   [SettingsController::class, 'design'])->name('settings.design')->middleware('project.can:settings.diseno');
        Route::get('/settings/designer', [SettingsController::class, 'designer'])->name('settings.designer')->middleware('project.can:settings.diseno'); // nuevo Diseñador visual (Fase A)

        // Constructor guiado (B0): entrada + lectura + contrato borrador/publicación.
        Route::get('/settings/builder', [\App\Http\Controllers\StoreBuilderController::class, 'index'])->name('settings.builder');
        Route::get('/settings/builder/progress', [\App\Http\Controllers\StoreBuilderController::class, 'progress'])->name('settings.builder.progress');
        Route::get('/settings/builder/checklist', [\App\Http\Controllers\StoreBuilderController::class, 'checklist'])->name('settings.builder.checklist');
        Route::get('/settings/builder/catalog/metrics', [\App\Http\Controllers\StoreBuilderController::class, 'catalogMetrics'])->name('settings.builder.metrics');
        Route::post('/settings/builder/draft/settings', [\App\Http\Controllers\StoreBuilderController::class, 'saveDraftSettings'])->name('settings.builder.draft.settings')->middleware('can:settings.diseno');
        Route::post('/settings/builder/design-preset', [\App\Http\Controllers\StoreBuilderController::class, 'applyDesignPreset'])->name('settings.builder.design-preset')->middleware('can:settings.diseno');

        // Plantilla automatica de imagenes de producto (constructor -> Catalogo).
        // Lectura con catalog.ver; escribir y regenerar exige settings.diseno,
        // que es el permiso con el que ya se toca el resto del constructor.
        Route::get('/settings/builder/image-template', [\App\Http\Controllers\ProductImageTemplateController::class, 'show'])->name('builder.image-template.show')->middleware('can:catalog.ver');
        Route::get('/settings/builder/image-template/status', [\App\Http\Controllers\ProductImageTemplateController::class, 'status'])->name('builder.image-template.status')->middleware('can:catalog.ver');
        Route::post('/settings/builder/image-template', [\App\Http\Controllers\ProductImageTemplateController::class, 'save'])->name('builder.image-template.save')->middleware('can:settings.diseno');
        Route::post('/settings/builder/image-template/toggle', [\App\Http\Controllers\ProductImageTemplateController::class, 'toggle'])->name('builder.image-template.toggle')->middleware('can:settings.diseno');
        Route::post('/settings/builder/image-template/reset', [\App\Http\Controllers\ProductImageTemplateController::class, 'reset'])->name('builder.image-template.reset')->middleware('can:settings.diseno');
        Route::post('/settings/builder/image-template/upload', [\App\Http\Controllers\ProductImageTemplateController::class, 'upload'])->name('builder.image-template.upload')->middleware('can:settings.diseno');
        Route::post('/settings/builder/image-template/preview', [\App\Http\Controllers\ProductImageTemplateController::class, 'preview'])->name('builder.image-template.preview')->middleware('can:settings.diseno');
        Route::post('/settings/builder/image-template/apply', [\App\Http\Controllers\ProductImageTemplateController::class, 'apply'])->name('builder.image-template.apply')->middleware('can:settings.diseno');
        Route::post('/settings/builder/image-template/save-as', [\App\Http\Controllers\ProductImageTemplateController::class, 'saveAs'])->name('builder.image-template.save-as')->middleware('can:settings.diseno');
        Route::post('/settings/builder/image-template/activate', [\App\Http\Controllers\ProductImageTemplateController::class, 'activate'])->name('builder.image-template.activate')->middleware('can:settings.diseno');
        // Diseños guardados ("Mis plantillas")
        // Gestión de plantillas de diseño = ESKALA_ONLY por defecto (matriz de
        // capacidades): capacidad 'plantillas' — solo superadmin, o tenant con
        // el flag cap_plantillas que Eskala enciende a mano. El export iba SIN
        // middleware (hallazgo de la auditoría 2026-08-30).
        Route::middleware('capacidad:plantillas')->group(function () {
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
        });
        Route::post('/settings/builder/publish', [\App\Http\Controllers\StoreBuilderController::class, 'publish'])->name('settings.builder.publish')->middleware('can:settings.diseno');
        // Descartar el borrador y volver a lo publicado. Hasta ahora la única
        // salida de un borrador con cambios no deseados era publicarlos.
        Route::post('/settings/builder/descartar-borrador', [\App\Http\Controllers\StoreBuilderController::class, 'discardDraft'])->name('settings.builder.discard')->middleware('can:settings.diseno');
        Route::get('/settings/builder/preview', [\App\Http\Controllers\StoreBuilderController::class, 'preview'])->name('settings.builder.preview');
        Route::get('/settings/builder/catalog/products', [\App\Http\Controllers\StoreBuilderController::class, 'catalogList'])->name('settings.builder.catalog.list');
        Route::post('/settings/builder/catalog/bulk', [\App\Http\Controllers\StoreBuilderController::class, 'catalogBulk'])->name('settings.builder.catalog.bulk')->middleware('can:settings.diseno');
        Route::get('/settings/builder/copy/sources', [\App\Http\Controllers\StoreBuilderController::class, 'copySources'])->name('settings.builder.copy.sources');
        Route::post('/settings/builder/copy', [\App\Http\Controllers\StoreBuilderController::class, 'copyStore'])->name('settings.builder.copy')->middleware('can:settings.diseno');
        Route::get('/settings/builder/icons/search', [\App\Http\Controllers\StoreBuilderController::class, 'iconSearch'])->name('settings.builder.icons.search');
        Route::post('/settings/builder/category-photo', [\App\Http\Controllers\StoreBuilderController::class, 'categoryPhoto'])->name('settings.builder.category-photo')->middleware('can:settings.diseno');
        Route::post('/settings/builder/icons/assign', [\App\Http\Controllers\StoreBuilderController::class, 'iconAssign'])->name('settings.builder.icons.assign')->middleware('can:settings.diseno');
        Route::post('/settings/design',  [SettingsController::class, 'updateDesign'])->name('settings.design.update')->middleware('can:settings.diseno');
        Route::post('/settings/design/apply-template', [SettingsController::class, 'applyTemplate'])->name('settings.design.applyTemplate')->middleware('can:settings.diseno');
        Route::post('/settings/design/apply-project-template', [SettingsController::class, 'applyProjectTemplate'])->name('settings.design.applyProjectTemplate')->middleware('can:settings.diseno');
        Route::post('/settings/design/project-templates', [SettingsController::class, 'storeProjectTemplate'])->name('settings.design.projectTemplates.store')->middleware('can:settings.diseno');
        Route::put('/settings/design/project-templates/{id}', [SettingsController::class, 'updateProjectTemplate'])->name('settings.design.projectTemplates.update')->middleware('can:settings.diseno');
        Route::delete('/settings/design/project-templates/{id}', [SettingsController::class, 'destroyProjectTemplate'])->name('settings.design.projectTemplates.destroy')->middleware('can:settings.diseno');
        Route::get('/settings/experience', [\App\Http\Controllers\StoreExperienceController::class, 'index'])->name('settings.experience');
        Route::post('/settings/experience/home/reorder', [\App\Http\Controllers\StoreExperienceController::class, 'reorderHome'])->name('settings.experience.home.reorder')->middleware('can:settings.diseno');
        Route::post('/settings/experience/home/publish-all', [\App\Http\Controllers\StoreExperienceController::class, 'publishAll'])->name('settings.experience.home.publishAll')->middleware('can:settings.diseno');
        Route::post('/settings/experience/home/{component}/publish', [\App\Http\Controllers\StoreExperienceController::class, 'publishOne'])->name('settings.experience.home.publishOne')->middleware('can:settings.diseno');
        Route::delete('/settings/experience/home/{component}/draft', [\App\Http\Controllers\StoreExperienceController::class, 'discardDraft'])->name('settings.experience.home.discardDraft')->middleware('can:settings.diseno');
        Route::post('/settings/experience/home/{component}', [\App\Http\Controllers\StoreExperienceController::class, 'saveHomeSection'])->name('settings.experience.home.save')->middleware('can:settings.diseno');
        Route::post('/settings/experience/home/{component}/state', [\App\Http\Controllers\StoreExperienceController::class, 'sectionState'])->name('settings.experience.home.state')->middleware('can:settings.diseno'); // Diseñador: sólo estado
        Route::get('/settings/experience/preview', [\App\Http\Controllers\StoreExperienceController::class, 'preview'])->name('settings.experience.preview');
        Route::post('/settings/experience/section', [\App\Http\Controllers\StoreExperienceController::class, 'section'])->name('settings.experience.section')->middleware('can:settings.diseno');
        Route::delete('/settings/experience/section/{id}', [\App\Http\Controllers\StoreExperienceController::class, 'deleteSection'])->name('settings.experience.section.delete')->middleware('can:settings.diseno');
        Route::post('/settings/experience/page', [\App\Http\Controllers\StoreExperienceController::class, 'page'])->name('settings.experience.page')->middleware('can:settings.diseno');
        Route::post('/settings/experience/popup', [\App\Http\Controllers\StoreExperienceController::class, 'popup'])->name('settings.experience.popup')->middleware('can:settings.diseno');
        Route::post('/settings/storefront/header', [\App\Http\Controllers\StoreNavigationController::class, 'updateHeader'])->name('settings.storefront.header')->middleware('can:settings.diseno');
        Route::post('/settings/storefront/publish', [\App\Http\Controllers\StoreNavigationController::class, 'publishStructure'])->name('settings.storefront.publish')->middleware('can:settings.diseno');
        Route::post('/settings/storefront/menu/items', [\App\Http\Controllers\StoreNavigationController::class, 'storeItem'])->name('settings.storefront.menu.items.store')->middleware('can:settings.diseno');
        Route::put('/settings/storefront/menu/items/{item}', [\App\Http\Controllers\StoreNavigationController::class, 'updateItem'])->name('settings.storefront.menu.items.update')->middleware('can:settings.diseno');
        Route::delete('/settings/storefront/menu/items/{item}', [\App\Http\Controllers\StoreNavigationController::class, 'destroyItem'])->name('settings.storefront.menu.items.destroy')->middleware('can:settings.diseno');
        Route::post('/settings/storefront/menu/reorder', [\App\Http\Controllers\StoreNavigationController::class, 'reorder'])->name('settings.storefront.menu.reorder')->middleware('can:settings.diseno');

        // Perfiles de catálogo (opcional, desactivado por defecto)
        Route::post('/settings/catalog-profiles/feature', [\App\Http\Controllers\CatalogProfileController::class, 'toggleFeature'])->name('settings.catalog-profiles.feature')->middleware('can:settings.catalogos');
        Route::post('/settings/catalog-profiles', [\App\Http\Controllers\CatalogProfileController::class, 'store'])->name('settings.catalog-profiles.store')->middleware('can:settings.catalogos');
        Route::post('/settings/catalog-profiles/quick', [\App\Http\Controllers\CatalogProfileController::class, 'quickCreate'])->name('settings.catalog-profiles.quick')->middleware('can:settings.catalogos');
        Route::put('/settings/catalog-profiles/{id}', [\App\Http\Controllers\CatalogProfileController::class, 'update'])->name('settings.catalog-profiles.update')->where('id', '[0-9]+')->middleware('can:settings.catalogos');
        Route::delete('/settings/catalog-profiles/{id}', [\App\Http\Controllers\CatalogProfileController::class, 'destroy'])->name('settings.catalog-profiles.destroy')->where('id', '[0-9]+')->middleware('can:settings.catalogos');
        Route::post('/settings/catalog-profiles/reorder', [\App\Http\Controllers\CatalogProfileController::class, 'reorder'])->name('settings.catalog-profiles.reorder')->middleware('can:settings.catalogos');
        Route::patch('/settings/experience/complaints/{id}', [\App\Http\Controllers\StoreExperienceController::class, 'complaintStatus'])->name('settings.experience.complaint.status')->middleware('can:settings.negocio');
        Route::post('/settings/flow', [SettingsController::class, 'updateFlow'])->name('settings.flow.update')->middleware('can:settings.negocio');
        Route::post('/settings/flow/diagram', [SettingsController::class, 'updateDiagram'])->name('settings.flow.diagram')->middleware('can:settings.negocio');
        Route::post('/settings/upload-logo', [SettingsController::class, 'uploadLogo'])->name('settings.upload-logo')->middleware('can:settings.diseno');
        Route::get('/notifications/imports',  [SettingsController::class, 'importLogs'])->name('notifications.imports');
        Route::get('/settings/payments', [SettingsController::class, 'payments'])->name('settings.payments');
        Route::post('/settings/payments',[SettingsController::class, 'updatePayments'])->name('settings.payments.update')->middleware('can:settings.pagos');
        Route::get('/settings/modules',  [SettingsController::class, 'modules'])->name('settings.modules');
        Route::get('/settings/qr',        [SettingsController::class, 'qr'])->name('settings.qr');
        Route::post('/settings/qr',       [SettingsController::class, 'updateQr'])->name('settings.qr.save')->middleware('can:settings.qr');
        Route::get('/settings/seo',      [SettingsController::class, 'seo'])->name('settings.seo');
        Route::post('/settings/seo',     [SettingsController::class, 'updateSeo'])->name('settings.seo.update')->middleware('can:settings.negocio');
        Route::post('/settings/modules', [SettingsController::class, 'updateModules'])->name('settings.modules.update')->middleware('can:settings.negocio');
        // Cupones
        Route::post('/coupons',          [SettingsController::class, 'storeCoupon'])->name('coupons.store')->middleware('can:catalog.editar');
        Route::delete('/coupons/{id}',   [SettingsController::class, 'destroyCoupon'])->name('coupons.destroy')->middleware('can:catalog.eliminar');
        Route::patch('/coupons/{id}/toggle', [SettingsController::class, 'toggleCoupon'])->name('coupons.toggle')->middleware('can:catalog.editar');

        // WhatsApp canales desde el portal administrador
        Route::post('/settings/canales',            [SettingsController::class, 'storeCanal'])->name('settings.canales.store')->middleware('can:settings.catalogos');
        Route::delete('/settings/canales/{canal}',  [SettingsController::class, 'destroyCanal'])->name('settings.canales.destroy')->middleware('can:settings.catalogos');

        // Propuestas BIXO
        Route::get('/proposals',              [ProposalController::class, 'index'])->name('proposals.index');
        Route::post('/proposals',             [ProposalController::class, 'store'])->name('proposals.store')->middleware('can:quotes.crear');
        Route::put('/proposals/{proposal}',   [ProposalController::class, 'update'])->name('proposals.update')->middleware('can:quotes.editar');
        Route::delete('/proposals/{proposal}',[ProposalController::class, 'destroy'])->name('proposals.destroy')->middleware('can:quotes.eliminar');

        // Certificados digitales
        Route::get('/certificados',                    [CertificadoController::class, 'index'])->name('certificados.index');
        Route::post('/certificados',                   [CertificadoController::class, 'store'])->name('certificados.store')->middleware('can:invoices.editar');
        Route::put('/certificados/{certificado}',      [CertificadoController::class, 'update'])->name('certificados.update')->middleware('can:invoices.editar');
        Route::delete('/certificados/{certificado}',   [CertificadoController::class, 'destroy'])->name('certificados.destroy')->middleware('can:invoices.editar');
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
        // Estas rutas estaban SIN permiso alguno mientras sus gemelas
        // `/facturas` (lineas ~1057) exigen `invoices.crear|editar|anular`.
        // Mismo controlador, mismas acciones: por la URL corta cualquier
        // miembro del proyecto —incluido un rol de solo lectura— emitia,
        // modificaba y borraba comprobantes fiscales. Se igualan a sus gemelas.
        Route::get('/invoices',               [InvoiceController::class, 'index'])->name('invoices.index')->middleware('can:invoices.ver');
        Route::post('/invoices',              [InvoiceController::class, 'store'])->name('invoices.store')->middleware('can:invoices.crear');
        Route::get('/invoices/{invoice}',     [InvoiceController::class, 'show'])->name('invoices.show')->middleware('can:invoices.ver');
        Route::put('/invoices/{invoice}',     [InvoiceController::class, 'update'])->name('invoices.update')->middleware('can:invoices.editar');
        Route::delete('/invoices/{invoice}',  [InvoiceController::class, 'destroy'])->name('invoices.destroy')->middleware('can:invoices.anular');
        Route::get('/invoices/{invoice}/pdf',    [InvoiceController::class, 'pdf'])->name('invoices.pdf')->middleware('can:invoices.ver');
        Route::post('/invoices/{invoice}/sunat', [InvoiceController::class, 'sendSunat'])->name('invoices.sunat')->middleware('can:invoices.crear');

        // Corregir un comprobante que SUNAT ya acepto. Borrar la fila lo dejaba
        // vivo en SUNAT con su IGV declarado; estas son las dos vias legales.
        Route::get('/invoices/{invoice}/nota',  [NotaController::class, 'opciones'])->name('invoices.nota.opciones')->middleware('can:invoices.ver');
        Route::post('/invoices/{invoice}/nota', [NotaController::class, 'store'])->name('invoices.nota')->middleware('can:invoices.anular');
        Route::post('/invoices/{invoice}/baja', [NotaController::class, 'darDeBaja'])->name('invoices.baja')->middleware('can:invoices.anular');

        // Consulta RUC: rellena razon social y direccion desde el padron. El
        // portal ya la tenia; el panel obligaba a teclearlo a mano.
        Route::get('/invoices-ruc', [InvoiceController::class, 'lookupRucPanel'])->name('invoices.ruc')->middleware('can:invoices.ver');
        // El Registro de Ventas del periodo: lo que pide el contador cada mes.
        Route::get('/invoices-registro', [InvoiceController::class, 'registroVentas'])->name('invoices.registro')->middleware('can:invoices.ver');

        // Guias de remision: el documento que viaja con la mercaderia. La
        // factura dice que se vendio; en un control de carretera piden esta.
        Route::get('/guias',                 [GuiaRemisionController::class, 'index'])->name('guias.index')->middleware('can:invoices.ver');
        Route::get('/guias/opciones',        [GuiaRemisionController::class, 'opciones'])->name('guias.opciones')->middleware('can:invoices.ver');
        Route::post('/guias',                [GuiaRemisionController::class, 'store'])->name('guias.store')->middleware('can:invoices.crear');
        Route::get('/guias/{guia}',          [GuiaRemisionController::class, 'show'])->name('guias.show')->middleware('can:invoices.ver');
        Route::get('/guias/{guia}/pdf',      [GuiaRemisionController::class, 'pdf'])->name('guias.pdf')->middleware('can:invoices.ver');
        Route::post('/guias/{guia}/enviar',  [GuiaRemisionController::class, 'enviar'])->name('guias.enviar')->middleware('can:invoices.crear');
        Route::delete('/guias/{guia}',       [GuiaRemisionController::class, 'destroy'])->name('guias.destroy')->middleware('can:invoices.anular');

        // Cotizaciones
        // Un permiso por verbo: *.ver solo autoriza lectura. Antes iba un unico
        // can:quotes.ver sobre todo el resource y quien podia leer podia borrar.
        Route::get('/quotes',              [QuoteController::class, 'index'])->name('quotes')->middleware(['module:quotes', 'can:quotes.ver']);
        Route::get('/quotes/{quote}',      [QuoteController::class, 'show'])->name('quotes.show')->middleware(['module:quotes', 'can:quotes.ver']);
        Route::get('/quotes/{quote}/pdf',  [QuoteController::class, 'pdf'])->name('quotes.pdf')->middleware(['module:quotes', 'can:quotes.ver']);
        Route::get('/quotes/{quote}/events', [QuoteController::class, 'events'])->name('quotes.events')->middleware(['module:quotes', 'can:quotes.ver']);
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
        Route::get('/orders/{order}/pdf',          [OrderController::class, 'pdf'])->name('orders.pdf')->middleware(['module:orders', 'can:orders.ver']);
        Route::post('/orders/{order}/pay',         [OrderController::class, 'pay'])->name('orders.pay')->middleware(['module:orders', 'can:orders.editar']);
        Route::post('/orders/{order}/issue-document', [OrderController::class, 'issueDocument'])->name('orders.issue-document')->middleware(['module:orders', 'can:orders.editar']);
        Route::get('/orders/{order}/events',       [OrderController::class, 'events'])->name('orders.events')->middleware(['module:orders', 'can:orders.ver']);
        Route::post('/orders/{order}/wa-sent',     [OrderController::class, 'waSent'])->name('orders.wa-sent')->middleware(['module:orders', 'can:orders.editar']);
        Route::get('/orders-export',               [OrderController::class, 'exportCsv'])->name('orders.export')->middleware(['module:orders', 'can:orders.ver']);
        // Mismo contrato dual A|B que su gemela de BixoSales (F1c): con
        // 'can:quotes.editar' puro, un usuario del universo heredado veia el
        // boton —QuoteAbilities le concede 'convertir'— y recibia 403 al
        // pulsarlo. La capacidad anunciada y la ruta deben coincidir.
        Route::post('/quotes/{quote}/convert',     [QuoteController::class, 'convert'])->name('quotes.convert')->middleware(['module:quotes', 'project.can:quotes.editar|manage-quotes']);
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

// Las rutas de autenticacion se cargan ANTES del comodin publico: al estar al
// final del archivo, `/{slug}` las capturaba y /forgot-password, /verify-email
// y /confirm-password devolvian 404. Aqui ganan ellas, que es lo correcto:
// ninguna tienda deberia llamarse "reset-password".
require __DIR__.'/auth.php';

// ─── Catálogo público ─────────────────────────────────────────────────────────
// `admin` faltaba en la lista: `routes/admin.php` se carga despues de este
// archivo, asi que el comodin tapaba /admin (el panel del superadmin).
// ── Fase 11: Portal del Cliente — enlace personal con token, sin contraseña.
// Registrado ANTES del comodín /{slug} (que se traga toda ruta posterior).
Route::get('/c/{token}',                    [\App\Http\Controllers\PortalClienteController::class, 'ver'])->name('portal.cliente');
Route::post('/c/{token}/repetir/{orderId}', [\App\Http\Controllers\PortalClienteController::class, 'repetir'])->middleware('throttle:15,1')->name('portal.cliente.repetir');

// ── Fase 3: impersonación auditada — soporte de Eskala entra al Workspace del
// cliente con su usuario superadmin, dejando rastro en access_events. Es el
// sustituto de operar tenants desde el plano de control (ADR-002 / ADR-008).
Route::post('/bixoadmin/entrar-como/{project}', function (\App\Models\Project $project) {
    abort_unless(auth()->user()?->is_superadmin, 403);
    \App\Models\AccessEvent::create([
        'project_id' => $project->id,
        'actor_id'   => auth()->id(),
        'action'     => 'impersonate',
        'role_name'  => 'superadmin',
        // El cast 'meta' => 'array' ya serializa; pasarle json_encode guardaba
        // un JSON de un string JSON (hallazgo de la auditoría).
        'meta'       => ['desde' => 'bixoadmin'],
        'ip'         => request()->ip(),
        'created_at' => now(),
    ]);
    session([
        'active_project_id'    => $project->id,
        'comercial_project_id' => $project->id,
    ]);
    return redirect('/bixosales');
})->middleware('auth')->name('bixoadmin.entrar-como');

// La salida deja el mismo rastro que la entrada: sin esto la auditoría sabía
// cuándo entró soporte pero no cuánto duró ni cuándo terminó.
Route::post('/bixoadmin/salir-de-impersonacion', function () {
    abort_unless(auth()->user()?->is_superadmin, 403);
    $pid = session('active_project_id') ?? session('comercial_project_id');
    if ($pid) {
        \App\Models\AccessEvent::create([
            'project_id' => $pid,
            'actor_id'   => auth()->id(),
            'action'     => 'impersonate_end',
            'role_name'  => 'superadmin',
            'meta'       => ['hacia' => 'workspace'],
            'ip'         => request()->ip(),
            'created_at' => now(),
        ]);
    }
    session()->forget(['active_project_id', 'comercial_project_id']);
    return redirect()->route('workspace');
})->middleware('auth')->name('bixoadmin.salir-impersonacion');

$reserved = 'admin|login|register|logout|workspace|bixoadmin|profile|projects|dashboard|b|f|up|pos|invoices|quotes|orders|bixosales|bixocrm|bixofact|wa|cert';
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
// Catalogo PDF por categoria (enlace FIRMADO que emite el bot).
Route::get('/{slug}/catalogo-pdf/{categoria}', [PublicController::class, 'catalogoPdf'])
    ->name('publico.catalogo.pdf')
    ->where('slug', '(?!(?:' . $reserved . ')$)[a-z0-9-]+')
    ->where('categoria', '[a-z0-9-]+');

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
Route::get('/b/{slug}/c/{token}/pdf', [PortalController::class, 'pdf'])->name('portal.quote.pdf');
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
    // Fase 5: el portal de facturacion se retira — sus pantallas viven en
    // /bixosales con los MISMOS controladores. Las entradas redirigen; las
    // rutas internas siguen registradas para no romper referencias mientras
    // dura el ciclo de compatibilidad (ADR-007).
    Route::get('/login', fn (string $slug) => redirect()->route('bixosales.login'))->name('login');
    Route::get('/__login_legado',  [FacAuthController::class, 'showLogin'])->name('login.legado');
    Route::post('/login', [FacAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.post');
    Route::post('/logout',[FacAuthController::class, 'logout'])->name('logout');

    // F5-cierre: el portal legado exige los MISMOS permisos por verbo que su
    // gemelo en /bixosales. Antes solo pedia estar logueado (`facturacion.auth`),
    // asi que era un bypass del RBAC. `proyecto.slug` fija el proyecto activo
    // desde la URL para que `project.can` (dual: canonico|legacy) funcione.
    Route::middleware(['auth', 'facturacion.auth', 'proyecto.slug'])->group(function () {
        Route::get('/', fn (string $slug) => redirect('/bixosales'))->name('dashboard');
        Route::get('/__dashboard_legado', [FacDashController::class, 'index'])->name('dashboard.legado');

        Route::get('/pos',  [PosController::class, 'indexPortal'])->name('pos')->middleware('can:pos.usar');
        Route::post('/pos', [PosController::class, 'storePortal'])->name('pos.store')->middleware('can:pos.usar');

        Route::get('/pedidos',            [OrderController::class, 'indexPortal'])->name('pedidos')->middleware('project.can:orders.ver|view-orders');
        Route::post('/pedidos',           [OrderController::class, 'storePortal'])->name('pedidos.store')->middleware('project.can:orders.crear|manage-orders');
        Route::get('/pedidos/{order}',    [OrderController::class, 'showPortal'])->name('pedidos.show')->middleware('project.can:orders.ver|view-orders');
        Route::put('/pedidos/{order}',    [OrderController::class, 'updatePortal'])->name('pedidos.update')->middleware('project.can:orders.editar|manage-orders');
        Route::delete('/pedidos/{order}', [OrderController::class, 'destroy'])->name('pedidos.destroy')->middleware('project.can:orders.eliminar|manage-orders');

        Route::get('/cotizaciones',                       [QuoteController::class, 'indexPortal'])->name('cotizaciones')->middleware('project.can:quotes.ver|view-quotes');
        Route::get('/cotizaciones/create',                [QuoteController::class, 'createPortal'])->name('cotizaciones.create')->middleware('project.can:quotes.crear|manage-quotes');
        Route::post('/cotizaciones',                      [QuoteController::class, 'storePortal'])->name('cotizaciones.store')->middleware('project.can:quotes.crear|manage-quotes');
        Route::get('/cotizaciones/{id}',                  [QuoteController::class, 'showPortal'])->name('cotizaciones.show')->middleware('project.can:quotes.ver|view-quotes');
        Route::get('/cotizaciones/{id}/edit',             [QuoteController::class, 'editPortal'])->name('cotizaciones.edit')->middleware('project.can:quotes.editar|manage-quotes');
        Route::put('/cotizaciones/{id}',                  [QuoteController::class, 'updatePortal'])->name('cotizaciones.update')->middleware('project.can:quotes.editar|manage-quotes');
        Route::put('/cotizaciones/{id}/full',             [QuoteController::class, 'updateFullPortal'])->name('cotizaciones.update_full')->middleware('project.can:quotes.editar|manage-quotes');
        Route::delete('/cotizaciones/{id}',               [QuoteController::class, 'destroyPortal'])->name('cotizaciones.destroy')->middleware('project.can:quotes.eliminar|manage-quotes');
        Route::post('/cotizaciones/{id}/convertir',       [QuoteController::class, 'convertirPortal'])->name('cotizaciones.convertir')->middleware('project.can:quotes.editar|manage-quotes');

        Route::get('/ruc',                      [InvoiceController::class, 'lookupRuc'])->name('ruc.lookup')->middleware('can:invoices.ver');
        Route::get('/boletas',                  [InvoiceController::class, 'indexBoletasPortal'])->name('boletas')->middleware('can:invoices.ver');
        Route::get('/boletas/create',           [InvoiceController::class, 'createBoletaPortal'])->name('boletas.create')->middleware('can:invoices.crear');
        Route::get('/facturas',                 [InvoiceController::class, 'indexFacturasPortal'])->name('facturas')->middleware('can:invoices.ver');
        Route::get('/facturas/create',          [InvoiceController::class, 'createFacturaPortal'])->name('facturas.create')->middleware('can:invoices.crear');
        Route::post('/comprobantes',            [InvoiceController::class, 'storePortal'])->name('facturas.store')->middleware('can:invoices.crear');
        Route::get('/facturas/{invoice}',     [InvoiceController::class, 'showPortal'])->name('facturas.show')->middleware('can:invoices.ver');
        Route::put('/facturas/{invoice}',     [InvoiceController::class, 'updatePortal'])->name('facturas.update')->middleware('can:invoices.editar');
        Route::delete('/facturas/{invoice}',  [InvoiceController::class, 'destroyPortal'])->name('facturas.destroy')->middleware('can:invoices.anular');
        Route::get('/facturas/{invoice}/pdf',   [InvoiceController::class, 'pdfPortal'])->name('facturas.pdf')->middleware('can:invoices.ver');
        Route::post('/facturas/{invoice}/sunat',[InvoiceController::class, 'sendSunatPortal'])->name('facturas.sunat')->middleware('can:invoices.crear');

        // Las mismas dos vias legales para corregir, tambien en el portal.
        Route::get('/facturas/{invoice}/nota',  [NotaController::class, 'opcionesPortal'])->name('facturas.nota.opciones')->middleware('can:invoices.ver');
        Route::post('/facturas/{invoice}/nota', [NotaController::class, 'storePortal'])->name('facturas.nota')->middleware('can:invoices.anular');
        Route::post('/facturas/{invoice}/baja', [NotaController::class, 'darDeBajaPortal'])->name('facturas.baja')->middleware('can:invoices.anular');

        Route::get('/clientes',            [ClientController::class, 'indexPortal'])->name('clientes')->middleware('project.can:clients.ver|view-clients');
        Route::post('/clientes',           [ClientController::class, 'storePortal'])->name('clientes.store')->middleware('project.can:clients.crear|manage-clients');
        Route::put('/clientes/{client}',   [ClientController::class, 'updatePortal'])->name('clientes.update')->middleware('project.can:clients.editar|manage-clients');
        Route::delete('/clientes/{client}',[ClientController::class, 'destroyPortal'])->name('clientes.destroy')->middleware('project.can:clients.eliminar|manage-clients');
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

    Route::middleware(['comercial.auth', 'comercial.module'])->group(function () {
        // Autorizacion por ruta. Antes TODO el portal colgaba solo de
        // comercial.auth (sesion + proyecto), sin comprobar un solo permiso:
        // cualquier usuario del proyecto podia crear, editar y borrar.
        // Matriz aprobada: docs/auditoria/bixosales-matriz-autorizacion.md §3
        // project.can admite alternativas "B|legacy" con semantica ANY.
        Route::get('/',             [ComDashController::class, 'index'])->name('dashboard')->middleware('project.can:orders.ver|view-orders');
        // Ocultar la guia de arranque escribe un ajuste del negocio: mismo
        // criterio que /cuentas/condiciones — ver el panel no basta para
        // decidir por todo el equipo que la guia sobra.
        Route::post('/arranque/ocultar', [ComDashController::class, 'ocultarArranque'])->name('arranque.ocultar')->middleware('project.can:settings.negocio|manage-settings');

        Route::get('/pos',  [PosController::class, 'indexComercial'])->name('pos')->middleware('can:pos.usar');
        Route::get('/venta-express', [PosController::class, 'express'])->name('ventas.express')->middleware('can:pos.usar');
        Route::post('/pos', [PosController::class, 'store'])->name('pos.store')->middleware('can:pos.usar');
        Route::post('/pos/cotizar', [PosController::class, 'quote'])->name('pos.quote')->middleware('can:pos.usar');

        // Aprobación de pagos Yape/Plin (pedidos del bot en revisión)
        // "pendientes" usa POST pero es una CONSULTA: lista los pagos en revision
        // y no escribe nada. Es la unica excepcion verbo/semantica del portal.
        Route::post('/pagos/pendientes', [\App\Http\Controllers\Api\PagoController::class, 'pendientes'])->name('pagos.pendientes')->middleware('can:payments.ver');
        Route::post('/pagos/aprobar',    [\App\Http\Controllers\Api\PagoController::class, 'aprobar'])->name('pagos.aprobar')->middleware('can:payments.aprobar');
        Route::post('/pagos/rechazar',   [\App\Http\Controllers\Api\PagoController::class, 'rechazar'])->name('pagos.rechazar')->middleware('can:payments.rechazar');

        // Revendedor: sus precios propios + su catálogo compartible
        Route::get('/revendedor/precios',   [\App\Http\Controllers\ResellerController::class, 'misPrecios'])->name('reseller.precios')->middleware('can:pos.usar');
        Route::post('/revendedor/precio',   [\App\Http\Controllers\ResellerController::class, 'guardarPrecio'])->name('reseller.precio.guardar')->middleware('can:pos.usar');
        Route::post('/revendedor/catalogo', [\App\Http\Controllers\ResellerController::class, 'toggleCatalogo'])->name('reseller.catalogo.toggle')->middleware('can:pos.usar');

        Route::get('/pedidos-bot',                  [RifaController::class, 'indexComercial'])->name('rifas')->middleware('can:rifas.ver');
        Route::get('/pedidos-bot/monitoreo',         [RifaController::class, 'monitoreo'])->name('rifas.monitoreo')->middleware('can:rifas.ver');
        Route::get('/pedidos-bot/exportar',          [RifaController::class, 'exportarComercial'])->name('rifas.exportar')->middleware('can:rifas.ver');
        Route::post('/pedidos-bot/{venta}/validar', [RifaController::class, 'confirmarPago'])->name('rifas.validar')->middleware('can:rifas.validar');
        Route::post('/pedidos-bot/{venta}/enviar',  [RifaController::class, 'enviarTicket'])->name('rifas.enviar')->middleware('can:rifas.validar');
        Route::post('/pedidos-bot/{venta}/cancelar',[RifaController::class, 'cancelar'])->name('rifas.cancelar')->middleware('can:rifas.cancelar');
        Route::post('/pedidos-bot/{venta}/editar',   [RifaController::class, 'editarComercial'])->name('rifas.editar')->middleware('can:rifas.validar');
        Route::post('/pedidos-bot/{venta}/eliminar',  [RifaController::class, 'eliminarComercial'])->name('rifas.eliminar')->middleware('can:rifas.cancelar');
        Route::post('/pedidos-bot/{venta}/recordar',  [RifaController::class, 'recordar'])->name('rifas.recordar')->middleware('can:rifas.validar');
        Route::post('/pedidos-bot/{venta}/enviar-membresia', [RifaController::class, 'enviarConMembresia'])->name('rifas.enviar.membresia')->middleware('can:rifas.validar');
        Route::post('/pedidos-bot/nuevo-manual', [RifaController::class, 'nuevoManual'])->name('rifas.nuevo-manual')->middleware('can:rifas.validar');
        Route::get('/consultar-dni/{dni}', [RifaController::class, 'consultarDni'])->name('rifas.consultar-dni')->middleware('can:rifas.ver');

        // WooCommerce
        Route::get('/woo/orders',  [\App\Http\Controllers\WooSyncController::class, 'index'])->name('woo.orders')->middleware('can:catalog.ver');
        Route::post('/woo/sync',   [\App\Http\Controllers\WooSyncController::class, 'sync'])->name('woo.sync')->middleware('can:catalog.importar');
        Route::get('/woo/stats',   [\App\Http\Controllers\WooSyncController::class, 'stats'])->name('woo.stats')->middleware('can:catalog.ver');

        // Tickets manuales WordPress
        Route::get('/conversaciones', [\App\Http\Controllers\Comercial\ConversacionesController::class, 'index'])->name('conversaciones')->middleware('can:tickets.ver');
        Route::get('/conversaciones/{id}/mensajes', [\App\Http\Controllers\Comercial\ConversacionesController::class, 'mensajes'])->name('conversaciones.mensajes')->middleware('can:tickets.ver');
        Route::get('/tickets-manuales', [\App\Http\Controllers\TicketsWpController::class, 'index'])->name('tickets.wp')->middleware('can:tickets.ver');
        Route::get('/tickets-manuales/buscar', [\App\Http\Controllers\TicketsWpController::class, 'buscar'])->name('tickets.wp.buscar')->middleware('can:tickets.ver');
        Route::post('/tickets-manuales/eliminar', [\App\Http\Controllers\TicketsWpController::class, 'eliminar'])->name('tickets.wp.eliminar')->middleware('can:tickets.eliminar');

        Route::get('/pedidos',                [OrderController::class, 'index'])->name('pedidos')->middleware('project.can:orders.ver|view-orders');
        Route::get('/pedidos/{order}/pdf',    [OrderController::class, 'pdf'])->name('pedidos.pdf')->middleware('project.can:orders.ver|view-orders');
        Route::post('/pedidos',               [OrderController::class, 'store'])->name('pedidos.store')->middleware('project.can:orders.crear|manage-orders');
        Route::get('/pedidos/{order}',        [OrderController::class, 'show'])->name('pedidos.show')->middleware('project.can:orders.ver|view-orders');
        // update toca cliente, productos, precios y condiciones comerciales:
        // exige orders.editar a secas. Almacen NO entra por aqui.
        Route::put('/pedidos/{order}',        [OrderController::class, 'update'])->name('pedidos.update')->middleware('project.can:orders.editar|manage-orders');
        Route::delete('/pedidos/{order}',     [OrderController::class, 'destroy'])->name('pedidos.destroy')->middleware('project.can:orders.eliminar|manage-orders');
        Route::post('/pedidos/{order}/wa-action',   [WaBotController::class, 'portalAction'])->name('pedidos.wa.action')->middleware('project.can:orders.editar|manage-orders');
        // Preparacion y entrega: tambien accesibles a logistica/almacen, que
        // cambian estado sin poder tocar datos comerciales.
        Route::post('/pedidos/{order}/wa-delivery', [WaBotController::class, 'updateDelivery'])->name('pedidos.wa.delivery')->middleware('project.can:orders.editar|manage-logistics');
        Route::patch('/pedidos/{order}/kitchen',    [OrderController::class, 'updateKitchen'])->name('pedidos.kitchen')->middleware('project.can:orders.editar|manage-logistics');
        // Estas cuatro faltaban: la vista de Pedidos las llamaba con la URL del
        // PANEL (url('/orders')/...) aun renderizada dentro del portal, de modo
        // que se evaluaban con los permisos del panel. changeLaundryStatus ya
        // contemplaba routeIs('bixosales.*') para una ruta que nunca se creo.
        // Se reutilizan los handlers existentes, sin duplicar logica.
        Route::post('/pedidos/{order}/pay',            [OrderController::class, 'pay'])->name('pedidos.pay')->middleware('project.can:orders.editar|manage-orders');
        Route::get('/pedidos/{order}/events',          [OrderController::class, 'events'])->name('pedidos.events')->middleware('project.can:orders.ver|view-orders');
        Route::post('/pedidos/{order}/wa-sent',        [OrderController::class, 'waSent'])->name('pedidos.wa-sent')->middleware('project.can:orders.editar|manage-orders');
        Route::post('/pedidos/{order}/laundry-status', [WaBotController::class, 'changeLaundryStatus'])->name('pedidos.laundry-status')->middleware('project.can:orders.editar|manage-logistics');
        Route::get('/cocina',                       [OrderController::class, 'kitchen'])->name('cocina')->middleware('project.can:orders.ver|view-orders');
        Route::get('/mesas',                        [MesaController::class, 'index'])->name('mesas')->middleware('project.can:orders.ver|view-orders');
        Route::get('/mesas/data',                   [MesaController::class, 'data'])->name('mesas.data')->middleware('project.can:orders.ver|view-orders');

        // ── MAPA OPERATIVO ────────────────────────────────────────────────────
        Route::prefix('mapa')->name('mapa.')->group(function () {
            // Lectura con mapa.ver; TODA mutacion con mapa.editar (11 rutas).
            Route::get('/',                                   [OperationalMapController::class, 'index'])->name('index')->middleware('can:mapa.ver');
            Route::get('/maps/{map}/objects',                 [OperationalMapController::class, 'objects'])->name('objects')->middleware('can:mapa.ver');
            Route::post('/maps',                              [OperationalMapController::class, 'storemap'])->name('maps.store')->middleware('can:mapa.editar');
            Route::post('/maps/{map}/objects',               [OperationalMapController::class, 'storeObject'])->name('objects.store')->middleware('can:mapa.editar');
            Route::patch('/objects/{object}/move',            [OperationalMapController::class, 'move'])->name('objects.move')->middleware('can:mapa.editar');
            Route::patch('/objects/{object}/status',          [OperationalMapController::class, 'changeStatus'])->name('objects.status')->middleware('can:mapa.editar');
            Route::patch('/objects/{object}/amount',          [OperationalMapController::class, 'updateAmount'])->name('objects.amount')->middleware('can:mapa.editar');
            Route::patch('/objects/{object}/responsible',     [OperationalMapController::class, 'assignResponsible'])->name('objects.responsible')->middleware('can:mapa.editar');
            Route::post('/objects/{object}/alerts',           [OperationalMapController::class, 'addAlert'])->name('objects.alerts.add')->middleware('can:mapa.editar');
            Route::delete('/objects/{object}/alerts',         [OperationalMapController::class, 'clearAlerts'])->name('objects.alerts.clear')->middleware('can:mapa.editar');
            Route::post('/objects/{object}/requests',         [OperationalMapController::class, 'createRequest'])->name('objects.requests.store')->middleware('can:mapa.editar');
            Route::get('/objects/{object}/history',           [OperationalMapController::class, 'history'])->name('objects.history')->middleware('can:mapa.ver');
            Route::put('/objects/{object}',                   [OperationalMapController::class, 'updateObject'])->name('objects.update')->middleware('can:mapa.editar');
            Route::delete('/objects/{object}',                [OperationalMapController::class, 'destroyObject'])->name('objects.destroy')->middleware('can:mapa.editar');
        });

        // Reservas
        Route::get('/reservas',                     [ReservaController::class, 'index'])->name('reservas')->middleware('can:agenda.ver');
        Route::post('/reservas',                    [ReservaController::class, 'store'])->name('reservas.store')->middleware('can:agenda.crear');
        Route::put('/reservas/{appointment}',       [ReservaController::class, 'update'])->name('reservas.update')->middleware('can:agenda.editar');
        Route::delete('/reservas/{appointment}',    [ReservaController::class, 'destroy'])->name('reservas.destroy')->middleware('can:agenda.eliminar');
        Route::get('/reservas/calendar',            [ReservaController::class, 'calendar'])->name('reservas.calendar')->middleware('can:agenda.ver');

        // Delivery — unica capacidad que solo existe en el universo legacy.
        // Migracion a dominio.accion documentada en rbac-produccion-arin.md
        Route::get('/delivery',                     [DeliveryController::class, 'index'])->name('delivery')->middleware('project.can:logistics.ver|view-logistics');
        Route::get('/delivery/data',                [DeliveryController::class, 'data'])->name('delivery.data')->middleware('project.can:logistics.ver|view-logistics');
        Route::post('/delivery',                    [DeliveryController::class, 'store'])->name('delivery.store')->middleware('project.can:logistics.editar|manage-logistics');
        Route::put('/delivery/{order}/status',      [DeliveryController::class, 'updateStatus'])->name('delivery.status')->middleware('project.can:logistics.editar|manage-logistics');

        // Caja / Tesorería — operaciones monetarias, permiso explicito por accion
        Route::get('/caja',                         [CajaController::class, 'index'])->name('caja')->middleware('can:caja.ver');
        Route::post('/caja/abrir',                  [CajaController::class, 'abrir'])->name('caja.abrir')->middleware('can:caja.abrir');
        Route::post('/caja/{caja}/cerrar',          [CajaController::class, 'cerrar'])->name('caja.cerrar')->middleware('can:caja.cerrar');
        Route::post('/caja/{caja}/movimiento',      [CajaController::class, 'movimiento'])->name('caja.movimiento')->middleware('can:caja.movimiento');
        Route::get('/caja/{caja}/data',             [CajaController::class, 'data'])->name('caja.data')->middleware('can:caja.ver');

        // Busqueda global de la barra superior. Sin middleware de modulo: el
        // propio servicio decide grupo por grupo lo que este usuario puede
        // ver, porque busca en varias secciones a la vez.
        Route::get('/buscar', function (\Illuminate\Http\Request $request) {
            return response()->json([
                'grupos' => \App\Support\BusquedaGlobal::buscar(
                    auth()->user(), app('active_project'), (string) $request->query('q', '')
                ),
            ]);
        })->name('buscar');

        Route::get('/cotizaciones',           [QuoteController::class, 'index'])->name('cotizaciones')->middleware('project.can:quotes.ver|view-quotes');
        Route::post('/cotizaciones',          [QuoteController::class, 'store'])->name('cotizaciones.store')->middleware('project.can:quotes.crear|manage-quotes');
        Route::get('/cotizaciones/{quote}',   [QuoteController::class, 'show'])->name('cotizaciones.show')->middleware('project.can:quotes.ver|view-quotes');
        Route::get('/cotizaciones/{quote}/pdf', [QuoteController::class, 'pdf'])->name('cotizaciones.pdf')->middleware('project.can:quotes.ver|view-quotes');
        Route::get('/cotizaciones/{quote}/events', [QuoteController::class, 'events'])->name('cotizaciones.events')->middleware('project.can:quotes.ver|view-quotes');
        Route::put('/cotizaciones/{quote}',   [QuoteController::class, 'update'])->name('cotizaciones.update')->middleware('project.can:quotes.editar|manage-quotes');
        Route::put('/cotizaciones/{quote}/full', [QuoteController::class, 'updateFull'])->name('cotizaciones.update_full')->middleware('project.can:quotes.editar|manage-quotes');
        Route::delete('/cotizaciones/{quote}',[QuoteController::class, 'destroy'])->name('cotizaciones.destroy')->middleware('project.can:quotes.eliminar|manage-quotes');
        Route::post('/cotizaciones/{quote}/send', [QuoteController::class, 'send'])->name('cotizaciones.send')->middleware('project.can:quotes.editar|manage-quotes');
        Route::post('/cotizaciones/{quote}/duplicate', [QuoteController::class, 'duplicate'])->name('cotizaciones.duplicate')->middleware('project.can:quotes.crear|manage-quotes');
        // Acuse de lectura que dispara la propia vista: basta con poder leerla.
        Route::post('/cotizaciones/{quote}/seen',      [QuoteController::class, 'markSeen'])->name('cotizaciones.seen')->middleware('project.can:quotes.ver|view-quotes');
        // Convertir en PEDIDO (F1c). Nombre inequivoco: en el portal fiscal ya
        // existe /f/{slug}/cotizaciones/{id}/convertir, que emite COMPROBANTE.
        // Mismo permiso dual A|B que el resto de acciones de edicion; hasta
        // ahora solo existia la ruta del panel con 'can:quotes.editar' puro, de
        // modo que un usuario con el permiso heredado podia editar pero no
        // convertir.
        Route::post('/cotizaciones/{quote}/convertir-pedido', [QuoteController::class, 'convert'])->name('cotizaciones.convertir_pedido')->middleware('project.can:quotes.editar|manage-quotes');

        // Solo se añade middleware: NO se toca logica fiscal ni SUNAT.
        Route::get('/facturas',               [InvoiceController::class, 'index'])->name('facturas')->middleware('can:invoices.ver');
        // EMITIR y CONSULTAR son trabajos distintos, de personas distintas:
        // el cajero emite todos los días; buscar un comprobante pasado o sacar
        // el registro del mes es del contador. Estaban en la misma pantalla.
        Route::get('/comprobantes-emitidos', [InvoiceController::class, 'consulta'])->name('facturas.consulta')->middleware('can:invoices.ver');
        Route::post('/facturas',              [InvoiceController::class, 'store'])->name('facturas.store')->middleware('can:invoices.crear');
        Route::get('/facturas/{invoice}',     [InvoiceController::class, 'show'])->name('facturas.show')->middleware('can:invoices.ver');
        Route::put('/facturas/{invoice}',     [InvoiceController::class, 'update'])->name('facturas.update')->middleware('can:invoices.editar');
        Route::delete('/facturas/{invoice}',  [InvoiceController::class, 'destroy'])->name('facturas.destroy')->middleware('can:invoices.anular');
        Route::get('/facturas/{invoice}/pdf',    [InvoiceController::class, 'pdf'])->name('facturas.pdf')->middleware('can:invoices.ver');
        Route::post('/facturas/{invoice}/sunat', [InvoiceController::class, 'sendSunat'])->name('facturas.sunat')->middleware('can:invoices.crear');

        Route::get('/facturas/{invoice}/nota',  [NotaController::class, 'opciones'])->name('facturas.nota.opciones')->middleware('can:invoices.ver');
        Route::post('/facturas/{invoice}/nota', [NotaController::class, 'store'])->name('facturas.nota')->middleware('can:invoices.anular');
        Route::post('/facturas/{invoice}/baja', [NotaController::class, 'darDeBaja'])->name('facturas.baja')->middleware('can:invoices.anular');

        // Guias de remision tambien en Ventas. Antes solo existian en el panel,
        // asi que el enlace del menu de Ventas sacaba al operador a la cara de
        // Configuracion en mitad de su trabajo. Mismo controlador: la guia es
        // una sola capacidad, lo unico que cambia es la puerta por la que entra.
        Route::get('/guias',                [GuiaRemisionController::class, 'index'])->name('guias.index')->middleware('can:invoices.ver');
        Route::get('/guias/opciones',       [GuiaRemisionController::class, 'opciones'])->name('guias.opciones')->middleware('can:invoices.ver');
        Route::post('/guias',               [GuiaRemisionController::class, 'store'])->name('guias.store')->middleware('can:invoices.crear');
        Route::get('/guias/{guia}',         [GuiaRemisionController::class, 'show'])->name('guias.show')->middleware('can:invoices.ver');
        Route::get('/guias/{guia}/pdf',     [GuiaRemisionController::class, 'pdf'])->name('guias.pdf')->middleware('can:invoices.ver');
        Route::post('/guias/{guia}/enviar', [GuiaRemisionController::class, 'enviar'])->name('guias.enviar')->middleware('can:invoices.crear');
        Route::delete('/guias/{guia}',      [GuiaRemisionController::class, 'destroy'])->name('guias.destroy')->middleware('can:invoices.anular');

        Route::get('/clientes',               [ClientController::class, 'index'])->name('clientes')->middleware('project.can:clients.ver|view-clients');
        // La ficha 360 (`show`) estaba escrita —calcula lo vendido y la deuda
        // desde el libro de cobros— pero no tenia ruta: nadie podia llamarla.
        Route::get('/clientes/{client}',      [ClientController::class, 'show'])->name('clientes.show')->middleware('project.can:clients.ver|view-clients');
        // Portal del Cliente (F11): generar/regenerar el enlace es una escritura.
        Route::post('/clientes/{client}/portal', [\App\Http\Controllers\PortalClienteController::class, 'generarEnlace'])->name('clientes.portal')->middleware('project.can:clients.editar|manage-clients');
        Route::post('/clientes',              [ClientController::class, 'store'])->name('clientes.store')->middleware('project.can:clients.crear|manage-clients');
        Route::put('/clientes/{client}',      [ClientController::class, 'update'])->name('clientes.update')->middleware('project.can:clients.editar|manage-clients');
        Route::delete('/clientes/{client}',   [ClientController::class, 'destroy'])->name('clientes.destroy')->middleware('project.can:clients.eliminar|manage-clients');

        // F2 v1 — Cuentas por Cobrar (LECTURA sobre datos existentes; la
        // entidad contable llega en F2b/F3 con diseño auditado). Mismo permiso
        // que los reportes: es una vista agregada, no muta nada.
        Route::get('/cuentas',                [\App\Http\Controllers\CxcController::class, 'index'])->name('cuentas')->middleware('can:reports.ver');
        // Ver la deuda y CAMBIAR las condiciones de cobro son cosas distintas:
        // lo segundo exige settings.pagos, no reports.ver.
        Route::post('/cuentas/condiciones',   [\App\Http\Controllers\CxcController::class, 'guardarCondiciones'])->name('cuentas.condiciones')->middleware('project.can:settings.pagos|manage-settings');

        Route::get('/reportes/ventas-bot',    [ReporteController::class, 'ventasBot'])->name('reportes.ventas')->middleware('can:reports.ver');
        Route::get('/reportes/seguimiento',   [ReporteController::class, 'seguimientoBot'])->name('reportes.seguimiento')->middleware('can:reports.ver');
        Route::get('/reportes/ventas',        [ReporteController::class, 'ventas'])->name('reportes.ventas.general')->middleware('can:reports.ver');
        Route::get('/reportes/top-productos', [ReporteController::class, 'topProductos'])->name('reportes.top.productos')->middleware('can:reports.ver');
        Route::get('/reportes/rentabilidad',  [ReporteController::class, 'rentabilidad'])->name('reportes.rentabilidad')->middleware('can:reports.ver');
        Route::get('/reportes/dashboard-data',[ReporteController::class, 'dashboardData'])->name('reportes.dashboard.data')->middleware('can:reports.ver');
        Route::get('/reportes/inventario',    [ReporteController::class, 'inventario'])->name('reportes.inventario')->middleware('can:reports.ver');
    });
});

Route::get('/test-membresia', function(){ return 'OK'; });
