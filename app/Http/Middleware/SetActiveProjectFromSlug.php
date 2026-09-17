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
                // NO se escribe en sesion: el proyecto viene en la URL y todas
                // las rutas de este grupo lo llevan. Guardarlo movia ademas el
                // proyecto activo del Admin, que es una sesion aparte.

                // Equipo de Spatie al proyecto de la URL. `CheckPermission` usa
                // `$user->can()`, que lee el equipo activo: sin esto los
                // permisos de /f/{slug} se evaluaban contra el proyecto que el
                // usuario tuviera abierto en Admin, no contra este.
                setPermissionsTeamId($project->id);
                $user = auth()->user();
                if ($user && ! $user->is_superadmin && $project->owner_id !== $user->id) {
                    // Los roles cargados antes de fijar el equipo son de otro
                    // contexto: se descartan para que se relean con este.
                    $user->unsetRelation('roles')->unsetRelation('permissions');
                    $user->forgetCachedPermissions();
                }
            }
        }

        return $next($request);
    }
}
