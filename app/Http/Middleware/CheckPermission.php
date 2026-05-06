<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica permiso granular. Uso: ->middleware('can:orders.crear')
 * Superadmin siempre pasa. El owner del proyecto también.
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user    = auth()->user();
        $project = app('active_project');

        if (!$user) {
            abort(403);
        }

        // Superadmin y owner del proyecto siempre tienen acceso
        if ($user->is_superadmin || ($project && $project->owner_id === $user->id)) {
            return $next($request);
        }

        if (!$user->hasPermissionTo($permission)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Sin permiso para esta acción.'], 403);
            }
            abort(403, 'Sin permiso para esta acción.');
        }

        return $next($request);
    }
}
