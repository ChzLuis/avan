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
    public function handle(Request $request, Closure $next): Response
    {
        $routeProject = $request->route('project');

        if ($routeProject instanceof Project) {
            $project = $routeProject;
        } else {
            $projectId = $routeProject ?? session('active_project_id');
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
            session(['active_project_id' => $project->id]);
            view()->share('activeProject', $project);
            app()->instance('active_project', $project);

            // Cargar rol del empleado en el usuario autenticado
            $user = auth()->user();
            if ($user && !$user->is_superadmin) {
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
                    // Sin rol asignado → quitar todos los roles de Spatie
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
