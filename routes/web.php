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
use App\Http\Controllers\PublicController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

// ─── Avan Store — Catálogo público ───────────────────────────────────────────
Route::get('/p/{slug}', [PublicController::class, 'catalog'])->name('public.catalog');
Route::post('/p/{slug}/order', [PublicController::class, 'storeOrder'])->name('public.order');
Route::post('/p/{slug}/quote', [PublicController::class, 'storeQuote'])->name('public.quote');
Route::get('/p/{slug}/book', [PublicController::class, 'book'])->name('public.book');
Route::post('/p/{slug}/book', [PublicController::class, 'storeBook'])->name('public.book.store');

// ─── Avan Store — Pagos del catálogo ─────────────────────────────────────────
Route::post('/p/{slug}/pay/{order}/manual',     [PaymentController::class, 'confirmManual'])->name('public.pay.manual');
Route::post('/p/{slug}/pay/{order}/culqi',      [PaymentController::class, 'chargeCulqi'])->name('public.pay.culqi');
Route::post('/p/{slug}/pay/{order}/mp',         [PaymentController::class, 'createMpPreference'])->name('public.pay.mp');
Route::post('/p/{slug}/mp-webhook',             [PaymentController::class, 'mpWebhook'])->name('public.mp.webhook')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// ─── Avan Docs — Portal comercial del cliente ────────────────────────────────
Route::get('/b/{slug}',          [PortalController::class, 'home'])->name('portal.home');
Route::get('/b/{slug}/c/{token}',[PortalController::class, 'quote'])->name('portal.quote');
Route::post('/b/{slug}/c/{token}/accept', [PortalController::class, 'accept'])->name('portal.quote.accept');

// ─── Ruta admin: generar/enviar token de cotización ──────────────────────────
Route::middleware(['auth','verified'])->prefix('{project}')->middleware(['project.member'])->group(function () {
    Route::post('/quotes/{quote}/send', [QuoteController::class, 'send'])->name('quotes.send');
});

// ─── Redirect raíz ───────────────────────────────────────────────────────────
Route::get('/', fn() => auth()->check() ? redirect()->route('workspace') : redirect()->route('login'));

// ─── Panel autenticado ────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    // Workspace (selector de negocios)
    Route::get('/workspace', [WorkspaceController::class, 'index'])->name('workspace');

    // CRUD de proyectos (negocios)
    Route::resource('projects', ProjectController::class)->except(['index', 'show']);

    // Panel del negocio (todas las rutas llevan /{project}/)
    Route::prefix('{project}')->middleware(['project.member'])->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Catálogo
        Route::prefix('catalog')->middleware('module:catalog')->group(function () {
            Route::get('/',                           [ProductController::class, 'index'])->name('catalog');
            Route::get('/products/export',            [ProductController::class, 'export'])->name('products.export');
            Route::get('/products/template',          [ProductController::class, 'template'])->name('products.template');
            Route::post('/products/import',           [ProductController::class, 'import'])->name('products.import');
            Route::resource('products', ProductController::class)->except(['index'])->names([
                'create' => 'products.create', 'store' => 'products.store',
                'show'   => 'products.show',   'edit'  => 'products.edit',
                'update' => 'products.update', 'destroy' => 'products.destroy',
            ]);
            Route::get('/services',         [ServiceController::class, 'index'])->name('services.index');
            Route::resource('services',     ServiceController::class)->except(['index'])->names([
                'create' => 'services.create', 'store'  => 'services.store',
                'show'   => 'services.show',   'edit'   => 'services.edit',
                'update' => 'services.update', 'destroy'=> 'services.destroy',
            ]);
            Route::resource('categories',   CategoryController::class)->names([
                'index'  => 'categories.index',  'create'  => 'categories.create',
                'store'  => 'categories.store',  'show'    => 'categories.show',
                'edit'   => 'categories.edit',   'update'  => 'categories.update',
                'destroy'=> 'categories.destroy',
            ]);
        });

        // Pedidos
        Route::resource('orders', OrderController::class)
            ->middleware('module:orders')
            ->names(['index'=>'orders','create'=>'orders.create','store'=>'orders.store',
                     'show'=>'orders.show','edit'=>'orders.edit','update'=>'orders.update','destroy'=>'orders.destroy']);

        // POS — Punto de Venta
        Route::get('/pos',          [PosController::class, 'index'])->name('pos.index')->middleware('module:orders');
        Route::post('/pos',         [PosController::class, 'store'])->name('pos.store')->middleware('module:orders');

        // Facturas y Boletas
        Route::get('/invoices',                     [InvoiceController::class, 'index'])->name('invoices.index')->middleware('module:invoices');
        Route::post('/invoices',                    [InvoiceController::class, 'store'])->name('invoices.store')->middleware('module:invoices');
        Route::get('/invoices/{invoice}',           [InvoiceController::class, 'show'])->name('invoices.show')->middleware('module:invoices');
        Route::put('/invoices/{invoice}',           [InvoiceController::class, 'update'])->name('invoices.update')->middleware('module:invoices');
        Route::delete('/invoices/{invoice}',        [InvoiceController::class, 'destroy'])->name('invoices.destroy')->middleware('module:invoices');
        Route::get('/invoices/{invoice}/pdf',       [InvoiceController::class, 'pdf'])->name('invoices.pdf')->middleware('module:invoices');

        // Cotizaciones
        Route::resource('quotes', QuoteController::class)
            ->middleware('module:quotes')
            ->names(['index'=>'quotes','create'=>'quotes.create','store'=>'quotes.store',
                     'show'=>'quotes.show','edit'=>'quotes.edit','update'=>'quotes.update','destroy'=>'quotes.destroy']);

        // Clientes
        Route::resource('clients', ClientController::class)
            ->middleware('module:clients')
            ->names(['index'=>'clients','create'=>'clients.create','store'=>'clients.store',
                     'show'=>'clients.show','edit'=>'clients.edit','update'=>'clients.update','destroy'=>'clients.destroy']);

        // Agenda
        Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda')->middleware('module:agenda');
        Route::resource('appointments', AgendaController::class)->except(['index'])
            ->middleware('module:agenda');

        // Roles y permisos
        Route::get('/roles',           [RolePermissionController::class, 'index'])->name('roles.index');
        Route::post('/roles',          [RolePermissionController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}',    [RolePermissionController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RolePermissionController::class, 'destroy'])->name('roles.destroy');

        // Catálogos (listas de valores maestros)
        Route::get('/catalogs',                                [CatalogListController::class, 'index'])->name('catalogs.index');
        Route::post('/catalogs',                               [CatalogListController::class, 'store'])->name('catalogs.store');
        Route::put('/catalogs/{catalog}',                      [CatalogListController::class, 'update'])->name('catalogs.update');
        Route::delete('/catalogs/{catalog}',                   [CatalogListController::class, 'destroy'])->name('catalogs.destroy');
        Route::get('/catalogs/{catalog}/values',               [CatalogListController::class, 'values'])->name('catalogs.values');
        Route::post('/catalogs/{catalog}/values',              [CatalogListController::class, 'storeValue'])->name('catalogs.values.store');
        Route::put('/catalogs/{catalog}/values/{value}',       [CatalogListController::class, 'updateValue'])->name('catalogs.values.update');
        Route::delete('/catalogs/{catalog}/values/{value}',    [CatalogListController::class, 'destroyValue'])->name('catalogs.values.destroy');

        // Proyectos (panel 3 columnas)
        Route::get('/projects', [ProjectController::class, 'panel'])->name('projects.panel');
        Route::patch('/projects/{target}/toggle', [ProjectController::class, 'toggleStatus'])->name('projects.toggle');
        Route::post('/projects/{target}/modules', [ProjectController::class, 'updateModules'])->name('projects.modules');

        // HR / Empleados
        Route::get('/hr/employees',                    [HRController::class, 'index'])->name('hr.employees.index');
        Route::post('/hr/employees',                   [HRController::class, 'store'])->name('hr.employees.store');
        Route::put('/hr/employees/{employee}',         [HRController::class, 'update'])->name('hr.employees.update');
        Route::delete('/hr/employees/{employee}',      [HRController::class, 'destroy'])->name('hr.employees.destroy');

        // Compañía – Sedes
        Route::get('/company/sedes',           [SedeController::class, 'index'])->name('sedes.index');
        Route::post('/company/sedes',          [SedeController::class, 'store'])->name('sedes.store');
        Route::put('/company/sedes/{sede}',    [SedeController::class, 'update'])->name('sedes.update');
        Route::delete('/company/sedes/{sede}', [SedeController::class, 'destroy'])->name('sedes.destroy');

        // Compañía – Grupos de usuarios
        Route::get('/company/groups',                [UserGroupController::class, 'index'])->name('groups.index');
        Route::post('/company/groups',               [UserGroupController::class, 'store'])->name('groups.store');
        Route::put('/company/groups/{userGroup}',    [UserGroupController::class, 'update'])->name('groups.update');
        Route::delete('/company/groups/{userGroup}', [UserGroupController::class, 'destroy'])->name('groups.destroy');

        // Compañía – Proveedores
        Route::get('/company/proveedores',                   [ProveedorController::class, 'index'])->name('proveedores.index');
        Route::post('/company/proveedores',                  [ProveedorController::class, 'store'])->name('proveedores.store');
        Route::put('/company/proveedores/{proveedor}',       [ProveedorController::class, 'update'])->name('proveedores.update');
        Route::delete('/company/proveedores/{proveedor}',    [ProveedorController::class, 'destroy'])->name('proveedores.destroy');

        // Configuración (incluye diseño con colores corporativos)
        Route::get('/settings',        [SettingsController::class, 'index'])->name('settings');
        Route::post('/settings',       [SettingsController::class, 'update'])->name('settings.update');
        Route::get('/settings/design', [SettingsController::class, 'design'])->name('settings.design');
        Route::post('/settings/design',[SettingsController::class, 'updateDesign'])->name('settings.design.update');
        Route::get('/settings/modules',[SettingsController::class, 'modules'])->name('settings.modules');
        Route::post('/settings/modules',[SettingsController::class, 'updateModules'])->name('settings.modules.update');
    });

    // Perfil de usuario
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
