<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->is_superadmin) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Acceso no autorizado.'], 403);
            }
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
