<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe el acceso fuera del horario laboral del empleado.
 * Superadmin y owner del proyecto siempre pasan.
 * Si el empleado no tiene horario definido (work_start/work_end), pasa sin restricción.
 */
class CheckWorkSchedule
{
    public function handle(Request $request, Closure $next): Response
    {
        $user    = auth()->user();
        $project = app('active_project');

        if (!$user || !$project) {
            return $next($request);
        }

        // Superadmin y owner: sin restricción
        if ($user->is_superadmin || $project->owner_id === $user->id) {
            return $next($request);
        }

        $employee = Employee::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if ($employee && !$employee->canAccessNow()) {
            $msg = "Acceso restringido fuera del horario laboral ({$employee->work_start} - {$employee->work_end}).";
            if ($request->expectsJson()) {
                return response()->json(['error' => $msg], 403);
            }
            return response()->view('errors.outside-schedule', [
                'work_start' => $employee->work_start,
                'work_end'   => $employee->work_end,
            ], 403);
        }

        return $next($request);
    }
}
