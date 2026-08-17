<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica permiso granular. Superadmin y owner del proyecto siempre pasan.
 *
 *   ->middleware('project.can:orders.crear')
 *   ->middleware('project.can:orders.crear|manage-orders')
 *
 * Con varias alternativas separadas por "|" la semántica es ANY: basta con
 * poseer una. Sirve de puente temporal entre el universo de permisos objetivo
 * (dominio.accion: orders.crear) y el heredado (verbo-recurso: manage-orders),
 * mientras conviven. NO es el modelo definitivo: la migración a un único
 * sistema está documentada en docs/auditoria/bixosales-matriz-autorizacion.md.
 *
 * Se usa $user->can() y no hasPermissionTo() a propósito: ante un permiso que
 * no existe en la base, can() devuelve false, mientras que hasPermissionTo()
 * lanza PermissionDoesNotExist y convierte un 403 en un 500. Con alternativas
 * legacy que pueden no estar sembradas, esa diferencia es la que evita tumbar
 * el portal.
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user    = auth()->user();
        $project = app()->bound('active_project') ? app('active_project') : null;

        if (!$user) {
            abort(403);
        }

        // Superadmin y owner del proyecto siempre tienen acceso
        if ($user->is_superadmin || ($project && $project->owner_id === $user->id)) {
            return $next($request);
        }

        $alternativas = array_filter(array_map('trim', explode('|', $permission)));

        foreach ($alternativas as $alternativa) {
            if ($user->can($alternativa)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Sin permiso para esta acción.'], 403);
        }

        abort(403, 'Sin permiso para esta acción.');
    }
}
