<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fija el proyecto activo a partir del {slug} de la URL para el portal legado
 * `/f/{slug}`. La pertenencia ya la garantizó el login de facturación (que
 * validó owner/member antes de crear `facturacion_auth.{slug}`); esto solo
 * deja `active_project` disponible para que el middleware `project.can` pueda
 * exigir permisos por verbo en esas rutas, cerrando el bypass de RBAC.
 */
class SetActiveProjectFromSlug
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug');
        if ($slug) {
            $project = Project::where('slug', $slug)->first();
            if ($project) {
                app()->instance('active_project', $project);
                session(['active_project_id' => $project->id]);
            }
        }

        return $next($request);
    }
}
