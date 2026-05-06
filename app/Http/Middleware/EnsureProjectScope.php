<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica que el recurso solicitado por ID pertenece al proyecto activo en sesión.
 * Uso en ruta: ->middleware('project.scope:App\Models\Order')
 */
class EnsureProjectScope
{
    public function handle(Request $request, Closure $next, string $modelClass = ''): Response
    {
        $projectId = session('active_project_id') ?? session('comercial_project_id');

        if (!$projectId) {
            abort(403, 'Sin proyecto activo.');
        }

        // Si se especificó un modelo y hay un ID en la ruta, validamos
        if ($modelClass && class_exists($modelClass)) {
            // Busca el primer parámetro de ruta que sea un entero
            $routeParams = $request->route()->parameters();
            $resourceId  = collect($routeParams)->first(fn($v) => is_numeric($v) || (is_object($v) && isset($v->id)));

            if ($resourceId) {
                $id = is_object($resourceId) ? $resourceId->id : (int) $resourceId;

                $exists = $modelClass::allProjects()
                    ->where('id', $id)
                    ->where('project_id', $projectId)
                    ->exists();

                if (!$exists) {
                    abort(403, 'No tienes acceso a este recurso.');
                }
            }
        }

        return $next($request);
    }
}
