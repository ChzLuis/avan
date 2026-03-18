<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Project;

class CheckProjectMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $project = app('active_project');

        if (!$project) {
            return redirect()->route('workspace')->with('error', 'Selecciona un negocio primero.');
        }

        $userId = auth()->id();
        $isMember = $project->owner_id === $userId
            || $project->members()->where('user_id', $userId)->exists();

        if (!$isMember) {
            abort(403, 'No tienes acceso a este negocio.');
        }

        return $next($request);
    }
}
