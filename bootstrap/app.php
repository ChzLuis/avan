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
        // Sesion expirada: cada PLATAFORMA vuelve a SU login, no al generico
        // /login. 'admin/*' por si solo NO casa con la raiz '/admin', asi que
        // la portada del Control mandaba al login del Workspace — justo la
        // mezcla de plataformas que el modelo prohibe (hallazgo 2026-08-30).
        $middleware->redirectGuestsTo(fn ($request) => $request->is('admin', 'admin/*')
            ? route('admin.login')
            : route('bixoadmin.login'));
        $middleware->alias([
            // routes/admin.php usa 'superadmin' desde siempre, pero el alias no
            // estaba registrado: toda ruta /admin/* protegida lanzaba
            // "Target class [superadmin] does not exist" y el panel de
            // superadmin era inalcanzable.
            'superadmin'       => \App\Http\Middleware\IsSuperAdmin::class,
            // Capacidades restringidas: entitlement + permiso + feature flag
            // (BIXO_CAPACIDADES_MATRIZ.md). Un permiso solo NO abre esto.
            'capacidad'        => \App\Http\Middleware\VerificaCapacidad::class,
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
