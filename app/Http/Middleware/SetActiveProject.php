<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Employee;
use App\Models\Project;
use Spatie\Permission\Models\Role;

class SetActiveProject
{
    /**
     * Clave de sesion del proyecto activo SEGUN EL PORTAL.
     *
     * Los portales son independientes: estar en Sales sobre un negocio no puede
     * mover el negocio de Admin. Antes los tres escribian `active_project_id`,
     * asi que cambiar de proyecto en cualquiera lo cambiaba en TODOS — y quien
     * tenia las dos pestanas abiertas se encontraba el otro portal movido.
     *
     * Facturacion no entra aqui: lleva el proyecto en la URL (`f/{slug}`), asi
     * que ya era independiente por construccion.
     */
    private function claveSesion(Request $request): string
    {
        if ($request->is('bixosales', 'bixosales/*')) return 'comercial_project_id';
        if ($request->is('bixocrm', 'bixocrm/*'))     return 'comunicaciones_project_id';
        return 'active_project_id';
    }

    public function handle(Request $request, Closure $next): Response
    {
        $routeProject = $request->route('project');
        $clave = $this->claveSesion($request);

        if ($routeProject instanceof Project) {
            $project = $routeProject;
        } else {
            $projectId = $routeProject ?? session($clave);
            $project   = $projectId ? Project::find($projectId) : null;
        }

        if ($project) {
            $userId   = auth()->id();
            $cacheKey = "member_{$project->id}_{$userId}";
            $isMember = session($cacheKey) ?? (
                $project->owner_id === $userId
                || $project->members()->where('user_id', $userId)->exists()
            );
            if ($isMember) {
                session([$cacheKey => true]);
            } else {
                $project = null;
            }
        }

        if ($project) {
            session([$clave => $project->id]);
            view()->share('activeProject', $project);
            app()->instance('active_project', $project);

            // Contexto de equipo de Spatie: el negocio activo. A partir de aquí,
            // roles y permisos del usuario se leen y escriben SOLO para este
            // proyecto. Es lo que convierte el rol en algo por negocio y lo que
            // hace que el syncRoles de abajo ya no sea un borrado global.
            setPermissionsTeamId($project->id);

            // Cargar rol del empleado en el usuario autenticado
            $user = auth()->user();
            if ($user && !$user->is_superadmin) {
                // Los roles cargados antes de fijar el equipo pertenecen a otro
                // contexto. Sin esto, la comparación de abajo usa datos viejos,
                // decide que no hay nada que sincronizar, y la persona se queda
                // sin permisos en este negocio.
                $user->unsetRelation('roles')->unsetRelation('permissions');
                $user->forgetCachedPermissions();
                $employee = Employee::where('project_id', $project->id)
                    ->where('user_id', $user->id)
                    ->first();

                $roleName = $employee?->spatie_role;

                // Superadmin del proyecto (owner) tiene todos los permisos
                if ($project->owner_id === $user->id) {
                    $roleName = null; // sin restricción
                }

                if ($roleName && Role::where('name', $roleName)->exists()) {
                    // Sincronizar rol solo si cambió (evita queries innecesarias)
                    $currentRoles = $user->getRoleNames()->toArray();
                    if ($currentRoles !== [$roleName]) {
                        $user->syncRoles([$roleName]);
                    }
                } elseif (!$project->owner_id || $project->owner_id !== $user->id) {
                    // Sin rol en ESTE proyecto → se le retira aquí y solo aquí.
                    // Antes esto vaciaba model_has_roles entero y una persona
                    // perdía sus roles en todos los negocios de forma permanente.
                    if ($user->roles->isNotEmpty()) {
                        $user->syncRoles([]);
                    }
                }
            }
        } else {
            view()->share('activeProject', null);
            app()->bind('active_project', fn() => null);
        }

        return $next($request);
    }
}
