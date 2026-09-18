<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ComunicacionesAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('comunicaciones_project_id')) {
            return redirect()->route('bixocrm.login');
        }

        // Entitlement del producto CRM: el portal solo se abre a negocios
        // activos con el modulo `clients`. Ocultar el menu no es seguridad;
        // esta es la puerta (ACCESS = TENANT_ENTITLEMENT AND USER).
        $project = Project::find(session('comunicaciones_project_id'));
        if (! $project || ! $project->is_active || ! $project->hasModule('clients')) {
            session()->forget('comunicaciones_project_id');

            return redirect()->route('bixocrm.login')
                ->withErrors(['email' => 'Este negocio no tiene contratado BIXO CRM.']);
        }

        return $next($request);
    }
}
