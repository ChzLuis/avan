<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Project;

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
        } else {
            view()->share('activeProject', null);
            app()->bind('active_project', fn() => null);
        }

        return $next($request);
    }
}
