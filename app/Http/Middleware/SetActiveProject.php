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
            $isMember = $project->owner_id === $userId
                || $project->members()->where('user_id', $userId)->exists();

            if ($isMember) {
                session(['active_project_id' => $project->id]);
                app()->instance('active_project', $project);
                view()->share('activeProject', $project);
            }
        }

        return $next($request);
    }
}
