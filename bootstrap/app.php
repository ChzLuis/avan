<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [__DIR__.'/../routes/web.php', __DIR__.'/../routes/admin.php'],
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*', headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO);
        $middleware->validateCsrfTokens(except: [
            'wa/*',
            'whatsapp/*',
            'api/*',
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\DetectCustomDomain::class,
            \App\Http\Middleware\SetActiveProject::class,
        ]);
        // Sesion expirada: cada zona vuelve a SU login, no al generico /login.
        $middleware->redirectGuestsTo(fn ($request) => $request->is('admin/*')
            ? route('admin.login')
            : route('bixoadmin.login'));
        $middleware->alias([
            // routes/admin.php usa 'superadmin' desde siempre, pero el alias no
            // estaba registrado: toda ruta /admin/* protegida lanzaba
            // "Target class [superadmin] does not exist" y el panel de
            // superadmin era inalcanzable.
            'superadmin'       => \App\Http\Middleware\IsSuperAdmin::class,
            'module'           => \App\Http\Middleware\CheckModuleActive::class,
            'project.member'   => \App\Http\Middleware\CheckProjectMember::class,
            'project.scope'    => \App\Http\Middleware\EnsureProjectScope::class,
            'project.can'      => \App\Http\Middleware\CheckPermission::class,
            'work.schedule'    => \App\Http\Middleware\CheckWorkSchedule::class,
            'facturacion.auth' => \App\Http\Middleware\FacturacionAuth::class,
            'proyecto.slug'    => \App\Http\Middleware\SetActiveProjectFromSlug::class,
            'comercial.auth'      => \App\Http\Middleware\ComercialAuth::class,
            'comercial.module'    => \App\Http\Middleware\EnsureComercialModule::class,
            'comunicaciones.auth' => \App\Http\Middleware\ComunicacionesAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
