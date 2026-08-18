<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleActive
{
    /**
     * Acepta alternativas separadas por `|`, igual que CheckPermission:
     * `module:inventory|catalog` deja pasar a quien tenga cualquiera de los
     * dos. Sirve para mover una seccion a su modulo propio sin dejar fuera a
     * los negocios que aun estan en el anterior.
     */
    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $project = app('active_project');

        $alternativas = array_filter(array_map('trim', explode('|', $moduleKey)));
        $tieneAlguno  = $project && collect($alternativas)->contains(fn ($m) => $project->hasModule($m));

        if (!$tieneAlguno) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Módulo no disponible.'], 403);
            }
            return redirect()->route('dashboard', ['project' => $project->id])->with('error', 'Este módulo no está disponible para tu negocio.');
        }

        return $next($request);
    }
}
