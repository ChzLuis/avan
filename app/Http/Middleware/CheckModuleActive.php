<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleActive
{
    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $project = app('active_project');

        if (!$project || !$project->hasModule($moduleKey)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Módulo no disponible.'], 403);
            }
            return redirect()->route('dashboard', ['project' => $project->id])->with('error', 'Este módulo no está disponible para tu negocio.');
        }

        return $next($request);
    }
}
