<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DetectCustomDomain
{
    // Dominios propios de la plataforma — nunca se tratan como custom domain
    private const OWN_HOSTS = [
        'localhost',
        '127.0.0.1',
        'bot.pruebatusuerte.com.pe',
        'admin.mercadosmayoristas.com.pe',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());

        // Si es un dominio propio, no hacer nada
        foreach (self::OWN_HOSTS as $own) {
            if ($host === $own || str_ends_with($host, '.' . $own)) {
                return $next($request);
            }
        }

        // Buscar proyecto por custom_domain (con cache de 5 min)
        $project = Cache::remember("custom_domain:{$host}", 300, function () use ($host) {
            return Project::where('custom_domain', $host)
                ->where('is_active', true)
                ->first();
        });

        if ($project) {
            // Forzar este proyecto como activo para toda la request
            session(['active_project_id' => $project->id]);
            view()->share('activeProject', $project);
            app()->instance('active_project', $project);
            app()->instance('custom_domain_project', $project);
        }

        return $next($request);
    }
}
