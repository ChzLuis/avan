<?php

namespace App\Http\Middleware;

use App\Http\Controllers\PublicController;
use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DetectCustomDomain
{
    // Hosts exactos de la plataforma — nunca se tratan como custom domain
    private const OWN_HOSTS = [
        'localhost',
        '127.0.0.1',
        'arindg.com',
        'www.arindg.com',
        'bot.pruebatusuerte.com.pe',
        'admin.mercadosmayoristas.com.pe',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());

        // Si es un host propio (comparación exacta), no hacer nada
        if (in_array($host, self::OWN_HOSTS)) {
            return $next($request);
        }

        // Buscar proyecto por custom_domain (con cache de 5 min)
        $project = Cache::remember("custom_domain:{$host}", 300, function () use ($host) {
            return Project::where('custom_domain', $host)
                ->where('is_active', true)
                ->first();
        });

        if ($project) {
            app()->instance('custom_domain_project', $project);

            $path = rtrim($request->getPathInfo(), '/');

            // Raíz o slug → servir catálogo (inicio) directamente sin cambiar URL
            if ($path === '' || $path === '/' . $project->slug) {
                $view = app(PublicController::class)->catalog($project->slug);
                return response()->make($view instanceof Response ? $view->getContent() : $view);
            }

            // /tienda → catálogo completo con filtros (modo tienda)
            if ($path === '/tienda') {
                $view = app(PublicController::class)->shop($request, $project->slug);
                return $view instanceof Response ? $view : response()->make($view);
            }
            // /nosotros y /contacto → páginas institucionales
            if ($path === '/nosotros') {
                $view = app(\App\Http\Controllers\StorePageController::class)->about($project->slug);
                return $view instanceof Response ? $view : response()->make($view);
            }
            if ($path === '/contacto' && $request->isMethod('get')) {
                $view = app(\App\Http\Controllers\StorePageController::class)->contact($project->slug);
                return $view instanceof Response ? $view : response()->make($view);
            }

            // Sitemap y robots en raíz del custom domain
            if ($path === '/sitemap.xml') {
                return app(PublicController::class)->sitemap($project->slug);
            }
            if ($path === '/robots.txt') {
                return app(PublicController::class)->robots($project->slug);
            }

            // Rutas de admin → bloquear
            if (str_starts_with($path, '/bixoadmin') || str_starts_with($path, '/login') || str_starts_with($path, '/dashboard')) {
                return redirect("https://{$host}/");
            }

            // Rutas internas del catálogo con slug → quitar el slug del path
            if (str_starts_with($path, '/' . $project->slug . '/')) {
                $newPath = substr($path, strlen('/' . $project->slug));
                $request->server->set('REQUEST_URI', $newPath . ($request->getQueryString() ? '?' . $request->getQueryString() : ''));
                return $next($request);
            }

            // Rutas sin slug (custom domain directo): /tienda, /nosotros, /p/{id}, etc.
            // Anteponemos el slug para que el router de Laravel las encuentre
            $slugRoutes = ['/tienda', '/nosotros', '/contacto', '/blog', '/p/', '/thanks/', '/book', '/order', '/cart', '/coupon', '/quote', '/upload-voucher'];
            $needsSlug = collect($slugRoutes)->contains(fn($r) => str_starts_with($path, $r) || $path === $r);
            if ($needsSlug || $path === '') {
                $newPath = '/' . $project->slug . ($path ?: '/');
                $request->server->set('REQUEST_URI', $newPath . ($request->getQueryString() ? '?' . $request->getQueryString() : ''));
            }
        }

        return $next($request);
    }
}
