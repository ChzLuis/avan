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
use App\Http\Controllers\ComunicacionesController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\WaWebhookController;
use App\Http\Controllers\WaBotController;
use App\Http\Controllers\RifaController;
use App\Http\Controllers\BotStatusController;
use App\Http\Controllers\Comunicaciones\AuthController as ComWaAuthController;
use App\Http\Controllers\Comunicaciones\BandejaController;
use App\Http\Controllers\Comunicaciones\ClientesCrmController;
use App\Http\Controllers\Comunicaciones\CanalesController;
use Illuminate\Support\Facades\Route;

// ─── Redirect raíz ───────────────────────────────────────────────────────────
Route::get('/', fn() => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

// ─── Panel autenticado ────────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    // Workspace (selector de negocios)
    Route::get('/workspace', [WorkspaceController::class, 'index'])->name('workspace');

    // Seleccionar proyecto activo
    Route::get('/workspace/select/{project}', [WorkspaceController::class, 'select'])->name('workspace.select');

    // CRUD de proyectos (negocios)
    Route::resource('projects', ProjectController::class)->except(['index', 'show']);

    // Panel del negocio — todas las rutas bajo /panel
    Route::prefix('bixoadmin')->middleware(['project.member'])->group(function () {

        // Dashboard
        Route::get('/',          [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.alt');

        // Catálogo
        Route::prefix('catalog')->middleware('module:catalog')->group(function () {
            Route::get('/',                        [ProductController::class, 'index'])->name('catalog');
            Route::get('/products/export',         [ProductController::class, 'export'])->name('products.export');
            Route::get('/products/template',       [ProductController::class, 'template'])->name('products.template');
            Route::post('/products/import',        [ProductController::class, 'import'])->name('products.import');
            Route::get('/products/export/static',  [ProductController::class, 'exportStatic'])->name('products.export.static');
            Route::get('/products/export/meli',    [ProductController::class, 'exportMeli'])->name('products.export.meli');
            Route::get('/products/export/rappi',   [ProductController::class, 'exportRappi'])->name('products.export.rappi');
            Route::get('/products/export/shopee',  [ProductController::class, 'exportShopee'])->name('products.export.shopee');
            Route::resource('products', ProductController::class)->except(['index'])->names([
                'create' => 'products.create', 'store' => 'products.store',
                'show'   => 'products.show',   'edit'  => 'products.edit',
                'update' => 'products.update', 'destroy' => 'products.destroy',
            ]);
            Route::post('/products/{product}/images',            [ProductController::class, 'uploadImage'])->name('products.images.upload');
            Route::delete('/products/{product}/images/{image}',  [ProductController::class, 'deleteImage'])->name('products.images.delete');
            Route::patch('/products/{product}/images/{image}/main', [ProductController::class, 'setMainImage'])->name('products.images.main');
            Route::get('/services',     [ServiceController::class, 'index'])->name('services.index');
            Route::resource('services', ServiceController::class)->except(['index'])->names([
                'create' => 'services.create', 'store'  => 'services.store',
                'show'   => 'services.show',   'edit'   => 'services.edit',
                'update' => 'services.update', 'destroy'=> 'services.destroy',
            ]);
            Route::resource('categories', CategoryController::class)->names([
                'index'  => 'categories.index',  'create'  => 'categories.create',
                'store'  => 'categories.store',  'show'    => 'categories.show',
                'edit'   => 'categories.edit',   'update'  => 'categories.update',
                'destroy'=> 'categories.destroy',
            ]);
            // Reseñas
            Route::get('/reviews',              [ProductController::class, 'reviews'])->name('reviews.index');
            Route::patch('/reviews/{id}/approve', [ProductController::class, 'approveReview'])->name('reviews.approve');
            Route::delete('/reviews/{id}',      [ProductController::class, 'destroyReview'])->name('reviews.destroy');
        });

        // Clientes
        Route::resource('clients', ClientController::class)
            ->middleware('module:clients')
            ->names(['index'=>'clients','create'=>'clients.create','store'=>'clients.store',
                     'show'=>'clients.show','edit'=>'clients.edit','update'=>'clients.update','destroy'=>'clients.destroy']);

        // Agenda
        Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda')->middleware('module:agenda');
        Route::resource('appointments', AgendaController::class)->except(['index'])->middleware('module:agenda');

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

        // Proyectos (panel 3 columnas)
        Route::get('/projects',                  [ProjectController::class, 'panel'])->name('projects.panel');
        Route::patch('/projects/{target}/toggle',[ProjectController::class, 'toggleStatus'])->name('projects.toggle');
        Route::post('/projects/{target}/modules',[ProjectController::class, 'updateModules'])->name('projects.modules');

        // Bots WhatsApp
        Route::get('/bots',                             [BotStatusController::class, 'index'])->name('bots.index');
        Route::get('/bots/status',                      [BotStatusController::class, 'status'])->name('bots.status');
        Route::get('/bots/flow',                        [BotStatusController::class, 'flowIndex'])->name('bots.flow');
        Route::post('/bots/flow',                       [BotStatusController::class, 'flowStore'])->name('bots.flow.store');
        Route::post('/bots/states',                     [BotStatusController::class, 'stateStore'])->name('bots.states.store');
        Route::put('/bots/states/{state}',              [BotStatusController::class, 'stateUpdate'])->name('bots.states.update');
        Route::delete('/bots/states/{state}',           [BotStatusController::class, 'stateDestroy'])->name('bots.states.destroy');
        Route::post('/bots/transitions',                [BotStatusController::class, 'transitionStore'])->name('bots.transitions.store');
        Route::delete('/bots/transitions/{transition}', [BotStatusController::class, 'transitionDestroy'])->name('bots.transitions.destroy');
        Route::post('/bots/config',                     [BotStatusController::class, 'configSave'])->name('bots.config.save');
        Route::post('/bots/control',                    [BotStatusController::class, 'botControl'])->name('bots.control');
        Route::get('/bots/logs',                        [BotStatusController::class, 'botLogs'])->name('bots.logs');
        Route::post('/bots/upload-image',               [BotStatusController::class, 'uploadImage'])->name('bots.upload.image');
        Route::post('/bots/instances',                  [BotStatusController::class, 'botStore'])->name('bots.instances.store');
        Route::delete('/bots/instances/{bot}',          [BotStatusController::class, 'botDestroy'])->name('bots.instances.destroy');

        // HR / Empleados
        Route::get('/hr/employees',                [HRController::class, 'index'])->name('hr.employees.index');
        Route::post('/hr/employees',               [HRController::class, 'store'])->name('hr.employees.store');
        Route::put('/hr/employees/{employee}',     [HRController::class, 'update'])->name('hr.employees.update');
        Route::delete('/hr/employees/{employee}',  [HRController::class, 'destroy'])->name('hr.employees.destroy');

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
        Route::get('/company/proveedores',                [ProveedorController::class, 'index'])->name('proveedores.index');
        Route::post('/company/proveedores',               [ProveedorController::class, 'store'])->name('proveedores.store');
        Route::put('/company/proveedores/{proveedor}',    [ProveedorController::class, 'update'])->name('proveedores.update');
        Route::delete('/company/proveedores/{proveedor}', [ProveedorController::class, 'destroy'])->name('proveedores.destroy');

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
        Route::post('/settings/design',  [SettingsController::class, 'updateDesign'])->name('settings.design.update');
        Route::post('/settings/design/apply-template', [SettingsController::class, 'applyTemplate'])->name('settings.design.applyTemplate');
        Route::get('/settings/payments', [SettingsController::class, 'payments'])->name('settings.payments');
        Route::post('/settings/payments',[SettingsController::class, 'updatePayments'])->name('settings.payments.update');
        Route::get('/settings/modules',  [SettingsController::class, 'modules'])->name('settings.modules');
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
        Route::get('/pos',  [PosController::class, 'index'])->name('pos.index')->middleware('module:orders');
        Route::post('/pos', [PosController::class, 'store'])->name('pos.store')->middleware('module:orders');

        // Facturas
        Route::get('/invoices',               [InvoiceController::class, 'index'])->name('invoices.index')->middleware('module:invoices');
        Route::post('/invoices',              [InvoiceController::class, 'store'])->name('invoices.store')->middleware('module:invoices');
        Route::get('/invoices/{invoice}',     [InvoiceController::class, 'show'])->name('invoices.show')->middleware('module:invoices');
        Route::put('/invoices/{invoice}',     [InvoiceController::class, 'update'])->name('invoices.update')->middleware('module:invoices');
        Route::delete('/invoices/{invoice}',  [InvoiceController::class, 'destroy'])->name('invoices.destroy')->middleware('module:invoices');
        Route::get('/invoices/{invoice}/pdf',    [InvoiceController::class, 'pdf'])->name('invoices.pdf')->middleware('module:invoices');
        Route::post('/invoices/{invoice}/sunat', [InvoiceController::class, 'sendSunat'])->name('invoices.sunat')->middleware('module:invoices');

        // Cotizaciones
        Route::resource('quotes', QuoteController::class)
            ->middleware('module:quotes')
            ->names(['index'=>'quotes','create'=>'quotes.create','store'=>'quotes.store',
                     'show'=>'quotes.show','edit'=>'quotes.edit','update'=>'quotes.update','destroy'=>'quotes.destroy']);
        Route::post('/quotes/{quote}/send', [QuoteController::class, 'send'])->name('quotes.send');

        // Pedidos
        Route::resource('orders', OrderController::class)
            ->middleware('module:orders')
            ->names(['index'=>'orders','create'=>'orders.create','store'=>'orders.store',
                     'show'=>'orders.show','edit'=>'orders.edit','update'=>'orders.update','destroy'=>'orders.destroy']);

        // Pedidos WhatsApp — acción del portal sobre pedido WA
        Route::post('/orders/{order}/wa-action',   [WaBotController::class, 'portalAction'])->name('orders.wa.action')->middleware('module:orders');
        Route::post('/orders/{order}/wa-delivery', [WaBotController::class, 'updateDelivery'])->name('orders.wa.delivery')->middleware('module:orders');
    });

    // Perfil de usuario
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ─── Verificación pública de certificados ────────────────────────────────────
Route::get('/cert/{codigo}', [CertificadoController::class, 'verificar'])->name('cert.verificar');

// ─── Catálogo público ─────────────────────────────────────────────────────────
$reserved = 'login|register|logout|workspace|bixoadmin|profile|projects|dashboard|b|f|up|pos|invoices|quotes|orders|bixosales|bixocrm|wa|cert';
Route::get('/{slug}',          [PublicController::class, 'catalog'])->name('public.catalog')->where('slug', '(?!' . $reserved . '$)[a-z0-9-]+');
Route::get('/{slug}/p/{id}',   [PublicController::class, 'product'])->name('public.product')->where('slug', '(?!' . $reserved . '$)[a-z0-9-]+')->where('id', '[0-9]+');
Route::post('/{slug}/order',   [PublicController::class, 'storeOrder'])->name('public.order')->where('slug', '(?!' . $reserved . '$)[a-z0-9-]+');
Route::post('/{slug}/coupon',  [PublicController::class, 'validateCoupon'])->name('public.coupon')->where('slug', '(?!' . $reserved . '$)[a-z0-9-]+');
Route::get('/{slug}/thanks/{order}', [PublicController::class, 'thankyou'])->name('public.thanks')->where('slug', '(?!' . $reserved . '$)[a-z0-9-]+')->where('order', '[0-9]+');
Route::post('/{slug}/p/{product}/review', [PublicController::class, 'storeReview'])->name('public.review')->where('slug', '(?!' . $reserved . '$)[a-z0-9-]+')->where('product', '[0-9]+');
Route::post('/{slug}/quote',   [PublicController::class, 'storeQuote'])->name('public.quote')->where('slug', '(?!' . $reserved . '$)[a-z0-9-]+');
Route::get('/{slug}/book',     [PublicController::class, 'book'])->name('public.book')->where('slug', '(?!' . $reserved . '$)[a-z0-9-]+');
Route::post('/{slug}/book',    [PublicController::class, 'storeBook'])->name('public.book.store')->where('slug', '(?!' . $reserved . '$)[a-z0-9-]+');

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
Route::post('/b/{slug}/c/{token}/accept', [PortalController::class, 'accept'])->name('portal.quote.accept');

// ─── Portal Facturación ───────────────────────────────────────────────────────
use App\Http\Controllers\Facturacion\AuthController as FacAuthController;
use App\Http\Controllers\Facturacion\DashboardController as FacDashController;
use App\Http\Controllers\Comercial\AuthController as ComAuthController;
use App\Http\Controllers\Comercial\DashboardController as ComDashController;


// ─── BixoFact — login genérico con desplegable de negocios ──────────────────
Route::get('/bixofact',        [FacAuthController::class, 'showLoginGeneral'])->name('bixofact.login');
Route::post('/bixofact/login', [FacAuthController::class, 'loginGeneral'])->name('bixofact.login.post');

Route::prefix('f/{slug}')->name('facturacion.')->group(function () {
    Route::get('/login',  [FacAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [FacAuthController::class, 'login'])->name('login.post');
    Route::post('/logout',[FacAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'facturacion.auth'])->group(function () {
        Route::get('/', [FacDashController::class, 'index'])->name('dashboard');

        Route::get('/pos',  [PosController::class, 'indexPortal'])->name('pos');
        Route::post('/pos', [PosController::class, 'storePortal'])->name('pos.store');

        Route::get('/pedidos',         [OrderController::class, 'indexPortal'])->name('pedidos');
        Route::post('/pedidos',        [OrderController::class, 'storePortal'])->name('pedidos.store');
        Route::get('/pedidos/{order}', [OrderController::class, 'showPortal'])->name('pedidos.show');
        Route::put('/pedidos/{order}', [OrderController::class, 'updatePortal'])->name('pedidos.update');

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

// ─── Portal Comunicaciones ────────────────────────────────────────────────────
Route::prefix('bixocrm')->name('bixocrm.')->group(function () {
    Route::get('/login',  [ComWaAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [ComWaAuthController::class, 'login'])->name('login.post');
    Route::post('/logout',[ComWaAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'comunicaciones.auth'])->group(function () {
        Route::get('/',                              [BandejaController::class, 'index'])->name('bandeja');
        Route::get('/poll',                          [BandejaController::class, 'poll'])->name('poll');
        Route::get('/{conversacion}/mensajes',       [BandejaController::class, 'mensajes'])->name('mensajes');
        Route::post('/{conversacion}/enviar',        [BandejaController::class, 'enviar'])->name('enviar');
        Route::patch('/{conversacion}',              [BandejaController::class, 'actualizar'])->name('actualizar');

        Route::get('/clientes',                      [ClientesCrmController::class, 'index'])->name('clientes');

        Route::get('/configuracion',                 [CanalesController::class, 'index'])->name('configuracion');
        Route::post('/canales',                      [CanalesController::class, 'guardar'])->name('canales.guardar');
        Route::delete('/canales/{canal}',            [CanalesController::class, 'eliminar'])->name('canales.eliminar');

        // Chatbot
        Route::get('/chatbot',                       [CanalesController::class, 'chatbot'])->name('chatbot');
        Route::post('/chatbot/flows',                [CanalesController::class, 'guardarFlow'])->name('chatbot.guardar');
        Route::delete('/chatbot/flows/{flow}',       [CanalesController::class, 'eliminarFlow'])->name('chatbot.eliminar');
        Route::patch('/chatbot/toggle/{id}',         [CanalesController::class, 'toggleBot'])->name('chatbot.toggle');
    });
});

// ─── Portal Comercial ─────────────────────────────────────────────────────────
Route::prefix('bixosales')->name('bixosales.')->group(function () {
    Route::get('/login',         [ComAuthController::class, 'showLogin'])->name('login');
    Route::post('/login',        [ComAuthController::class, 'login'])->name('login.post');
    Route::post('/get-projects', [ComAuthController::class, 'getProjects'])->name('get.projects');
    Route::post('/logout',       [ComAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'comercial.auth'])->group(function () {
        Route::get('/',             [ComDashController::class, 'index'])->name('dashboard');

        Route::get('/pos',  [PosController::class, 'indexComercial'])->name('pos');
        Route::post('/pos', [PosController::class, 'store'])->name('pos.store');

        Route::get('/pedidos',                [OrderController::class, 'index'])->name('pedidos');
        Route::post('/pedidos',               [OrderController::class, 'store'])->name('pedidos.store');
        Route::get('/pedidos/{order}',        [OrderController::class, 'show'])->name('pedidos.show');
        Route::put('/pedidos/{order}',        [OrderController::class, 'update'])->name('pedidos.update');
        Route::post('/pedidos/{order}/wa-action',   [WaBotController::class, 'portalAction'])->name('pedidos.wa.action');
        Route::post('/pedidos/{order}/wa-delivery', [WaBotController::class, 'updateDelivery'])->name('pedidos.wa.delivery');

        Route::get('/cotizaciones',           [QuoteController::class, 'index'])->name('cotizaciones');
        Route::post('/cotizaciones',          [QuoteController::class, 'store'])->name('cotizaciones.store');
        Route::get('/cotizaciones/{quote}',   [QuoteController::class, 'show'])->name('cotizaciones.show');
        Route::put('/cotizaciones/{quote}',   [QuoteController::class, 'update'])->name('cotizaciones.update');
        Route::delete('/cotizaciones/{quote}',[QuoteController::class, 'destroy'])->name('cotizaciones.destroy');
        Route::post('/cotizaciones/{quote}/send', [QuoteController::class, 'send'])->name('cotizaciones.send');

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
    });
});

require __DIR__.'/auth.php';
