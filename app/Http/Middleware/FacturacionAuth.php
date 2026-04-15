<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FacturacionAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug');

        if (! session("facturacion_auth.{$slug}")) {
            return redirect()->route('facturacion.login', ['slug' => $slug]);
        }

        return $next($request);
    }
}
