<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminProjectController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminImportController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\DemoController;
use Illuminate\Support\Facades\Route;

// ── Login admin (sin auth) ────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('/login',  [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');

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
        Route::get('/projects/{project}',              [AdminProjectController::class, 'show'])->name('projects.show');

        // Usuarios
        Route::get('/users',                           [AdminUserController::class, 'index'])->name('users');
        Route::patch('/users/{user}/toggle-admin',     [AdminUserController::class, 'toggleAdmin'])->name('users.toggle-admin');

        // Cargas masivas
        Route::get('/imports',                         [AdminImportController::class, 'index'])->name('imports');
        Route::post('/imports/products',               [AdminImportController::class, 'importProducts'])->name('imports.products');
        Route::post('/imports/clients',                [AdminImportController::class, 'importClients'])->name('imports.clients');
        Route::post('/imports/employees',              [AdminImportController::class, 'importEmployees'])->name('imports.employees');
        Route::get('/imports/template/{type}',         [AdminImportController::class, 'downloadTemplate'])->name('imports.template');

        // Configuración global
        Route::get('/settings',                        [AdminSettingsController::class, 'index'])->name('settings');
        Route::post('/settings',                       [AdminSettingsController::class, 'update'])->name('settings.update');

        // Demos
        Route::get('/demos',                           [DemoController::class, 'adminIndex'])->name('demos.index');
        Route::post('/demos/{demo}/cancel',            [DemoController::class, 'adminCancel'])->name('demos.cancel');
        Route::post('/demos/{demo}/extend',            [DemoController::class, 'adminExtend'])->name('demos.extend');
    });
});
