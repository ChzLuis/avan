<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminProjectController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminImportController;
use App\Http\Controllers\Admin\AdminLicenseController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminTurnosController;
use App\Http\Controllers\DemoController;
use Illuminate\Support\Facades\Route;

// ── Login admin (sin auth) ────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('/login',  [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');

    Route::get('/forgot-password', fn() => redirect()->route('portal.password.request', 'admin'))->name('password.request');

    // ── Rutas protegidas ──────────────────────────────────────────────────────
    Route::middleware(['auth', 'superadmin'])->group(function () {

        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        // Dashboard
        Route::get('/',          [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        // Proyectos
        Route::get('/projects',                        [AdminProjectController::class, 'index'])->name('projects');
        Route::patch('/projects/{project}/toggle',     [AdminProjectController::class, 'toggle'])->name('projects.toggle');
        Route::patch('/projects/{project}/modules',    [AdminProjectController::class, 'updateModules'])->name('projects.modules');
        Route::patch('/projects/{project}/subdomain',  [AdminProjectController::class, 'updateSubdomain'])->name('projects.subdomain');
        Route::get('/projects/{project}',              [AdminProjectController::class, 'show'])->name('projects.show');
        Route::post('/projects/{project}/owner',       [AdminProjectController::class, 'transferOwnership'])->name('projects.owner');

        // Usuarios
        Route::get('/users',                           [AdminUserController::class, 'index'])->name('users');
        Route::patch('/users/{user}/toggle-admin',     [AdminUserController::class, 'toggleAdmin'])->name('users.toggle-admin');
        Route::post('/users/{user}/reset-password',    [AdminUserController::class, 'resetPassword'])->name('users.reset-password');

        // Licencias y sesiones
        Route::get('/licencias',                          [AdminLicenseController::class, 'index'])->name('licenses');
        Route::post('/licencias/sesion/cerrar',           [AdminLicenseController::class, 'revokeSession'])->name('licenses.revoke-session');
        Route::post('/licencias/usuario/{user}/cerrar',   [AdminLicenseController::class, 'revokeUser'])->name('licenses.revoke-user');
        Route::post('/licencias/inactivas/cerrar',        [AdminLicenseController::class, 'revokeIdle'])->name('licenses.revoke-idle');
        Route::patch('/licencias/usuario/{user}/tipo',    [AdminLicenseController::class, 'updateLicense'])->name('licenses.update-user');
        Route::post('/licencias/configuracion',           [AdminLicenseController::class, 'updateSettings'])->name('licenses.settings');

        // Cargas masivas
        Route::get('/imports',                         [AdminImportController::class, 'index'])->name('imports');
        Route::post('/imports/products',               [AdminImportController::class, 'importProducts'])->name('imports.products');
        Route::post('/imports/clients',                [AdminImportController::class, 'importClients'])->name('imports.clients');
        Route::post('/imports/employees',              [AdminImportController::class, 'importEmployees'])->name('imports.employees');
        Route::get('/imports/template/{type}',         [AdminImportController::class, 'downloadTemplate'])->name('imports.template');

        // Gestión de Turnos
        Route::get('/projects/{project}/turnos',                              [AdminTurnosController::class, 'index'])->name('turnos.index');
        Route::post('/projects/{project}/turnos/{employee}/save',             [AdminTurnosController::class, 'saveEmployee'])->name('turnos.save-employee');
        Route::post('/projects/{project}/turnos/bulk',                        [AdminTurnosController::class, 'saveBulk'])->name('turnos.save-bulk');
        Route::get('/projects/{project}/turnos/active-now',                   [AdminTurnosController::class, 'activeNow'])->name('turnos.active-now');

        // Configuración global
        Route::get('/settings',                        [AdminSettingsController::class, 'index'])->name('settings');
        Route::post('/settings',                       [AdminSettingsController::class, 'update'])->name('settings.update');

        // Auditoría (SOLO LECTURA): accesos, impersonaciones y cambios de
        // perfiles. El Control observa, no edita datos del tenant (ADR-002).
        Route::get('/auditoria', function () {
            $eventos = \App\Models\AccessEvent::with(['actor', 'afectado'])
                ->when(request('accion'), fn ($q, $a) => $q->where('action', $a))
                ->orderByDesc('created_at')
                ->paginate(50)
                ->withQueryString();

            return view('admin.audit.index', ['eventos' => $eventos]);
        })->name('audit');

        // Demos
        Route::get('/demos',                           [DemoController::class, 'adminIndex'])->name('demos.index');
        Route::post('/demos/{demo}/cancel',            [DemoController::class, 'adminCancel'])->name('demos.cancel');
        Route::post('/demos/{demo}/extend',            [DemoController::class, 'adminExtend'])->name('demos.extend');
    });
});
