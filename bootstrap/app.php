<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'wa/*',
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\SetActiveProject::class,
        ]);
        $middleware->alias([
            'module'           => \App\Http\Middleware\CheckModuleActive::class,
            'project.member'   => \App\Http\Middleware\CheckProjectMember::class,
            'facturacion.auth' => \App\Http\Middleware\FacturacionAuth::class,
            'comercial.auth'      => \App\Http\Middleware\ComercialAuth::class,
            'comunicaciones.auth' => \App\Http\Middleware\ComunicacionesAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
