<?php

namespace App\Http\Middleware;

use App\Support\Capacidades;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ->middleware('capacidad:plantillas')
 *
 * Puerta de capacidades restringidas (BIXO_CAPACIDADES_MATRIZ.md): combina
 * entitlement + permiso + feature flag del proyecto. Fail-closed: capacidad
 * desconocida o sin proyecto activo = 403, nunca abierto por accidente.
 */
class VerificaCapacidad
{
    public function handle(Request $request, Closure $next, string $capacidad): Response
    {
        $project = app()->bound('active_project') ? app('active_project') : null;

        if (! Capacidades::permite($project, auth()->user(), $capacidad)) {
            abort(403, 'Esta herramienta no está habilitada para tu negocio.');
        }

        return $next($request);
    }
}
