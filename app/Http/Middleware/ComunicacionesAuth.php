<?php

namespace App\Http\Middleware;

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
        return $next($request);
    }
}
