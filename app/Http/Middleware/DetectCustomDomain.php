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

        // www.midominio.com debe resolver igual que midominio.com (el cliente
        // guarda un solo custom_domain sin www; el certificado wildcard cubre ambos).
        $lookupHost = str_starts_with($host, 'www.') ? substr($host, 4) : $host;

        // Buscar proyecto por custom_domain (con cache de 5 min)
        $project = Cache::remember("custom_domain:{$lookupHost}", 300, function () use ($lookupHost) {
            return Project::where('custom_domain', $lookupHost)
                ->where('is_active', true)
                ->first();
        });

        if ($project) {
            app()->instance('custom_domain_project', $project);

            $path = rtrim($request->getPathInfo(), '/');

            // El slug interno no pinta nada en un dominio propio: 301 a la raiz
            // para no repartir la misma portada entre dos direcciones.
            if ($path === '/' . $project->slug) {
                return redirect('/', 301);
            }

            // Raíz → servir catálogo (inicio) directamente sin cambiar URL
            if ($path === '') {
                $view = app(PublicController::class)->catalog($project->slug);
                return response()->make($view instanceof Response ? $view->getContent() : $view);
            }

            // /tienda → catálogo completo con filtros (modo tienda)
            if ($path === '/tienda') {
                $view = app(PublicController::class)->shop($request, $project->slug);
                return $view instanceof Response ? $view : response()->make($view);
            }
            // /tienda/{coleccion} → catálogo acotado a una colección (Niño, Niña…).
            // Sin esta ruta las colecciones solo funcionaban entrando por arindg.com.
            if (preg_match('#^/tienda/([a-z0-9-]+)$#', $path, $mp)) {
                $view = app(PublicController::class)->shop($request, $project->slug, $mp[1]);
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
            // Páginas legales y Libro de Reclamaciones (el rewrite de REQUEST_URI no
            // aplica aquí: Symfony ya resolvió pathInfo, así que van explícitas).
            if ($path === '/privacidad' || $path === '/terminos') {
                $view = app(\App\Http\Controllers\StorePageController::class)->legal($project->slug, ltrim($path, '/'));
                return $view instanceof Response ? $view : response()->make($view);
            }
            if ($path === '/reclamaciones' || $path === '/libro-reclamaciones') {
                if ($request->isMethod('post')) {
                    return app(\App\Http\Controllers\StorePageController::class)->storeComplaint($request, $project->slug);
                }
                $view = app(\App\Http\Controllers\StorePageController::class)->complaints($project->slug);
                return $view instanceof Response ? $view : response()->make($view);
            }

            // Ficha de producto con nombre: /producto/pc-de-escritorio-i5-460.
            // El id cierra la clave; si el nombre no coincide con el actual,
            // 301 al canonico para no tener dos URLs vivas del mismo producto.
            if (preg_match('#^/producto/([A-Za-z0-9-]*[0-9]+)$#', $path, $m)) {
                $id = \App\Support\ImageVariants::idDeClave($m[1]);
                $producto = $id > 0 ? $project->products()->where('is_available', true)->find($id) : null;
                if (! $producto) {
                    abort(404);
                }
                if (\App\Support\ImageVariants::claveProducto($id, $producto->name) !== $m[1]) {
                    return redirect('/producto/'.\App\Support\ImageVariants::claveProducto($id, $producto->name), 301);
                }
                $view = app(PublicController::class)->product($project->slug, $id);
                return $view instanceof Response ? $view : response()->make($view);
            }

            // Enlaces antiguos (/p/460): 301 a la URL con nombre.
            if (preg_match('#^/p/(\d+)$#', $path, $m)) {
                $producto = $project->products()->where('is_available', true)->find((int) $m[1]);
                if (! $producto) {
                    abort(404);
                }
                return redirect('/producto/'.\App\Support\ImageVariants::claveProducto((int) $m[1], $producto->name), 301);
            }

            // El carrito es una capa dentro de la tienda, no una página propia:
            // quien llegue a /carrito por un enlace guardado va al inicio.
            if ($path === '/carrito' || $path === '/cart') {
                return redirect("https://{$host}/");
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

            // Rutas internas del catálogo con slug → 301 a la version sin slug.
            // Antes se servian en silencio y quedaban dos URLs por pagina.
            if (str_starts_with($path, '/' . $project->slug . '/')) {
                $newPath = substr($path, strlen('/' . $project->slug));
                if ($request->isMethodSafe()) { // GET y HEAD: POST sigue sin redirigir
                    return redirect($newPath . ($request->getQueryString() ? '?' . $request->getQueryString() : ''), 301);
                }
                $request->server->set('REQUEST_URI', $newPath . ($request->getQueryString() ? '?' . $request->getQueryString() : ''));
                return $next($request);
            }

            // Rutas sin slug (custom domain directo): /tienda, /nosotros, /p/{id}, etc.
            // Anteponemos el slug para que el router de Laravel las encuentre
            $slugRoutes = ['/tienda', '/nosotros', '/contacto', '/blog', '/producto/', '/p/', '/thanks/', '/book', '/order', '/cart', '/coupon', '/quote', '/upload-voucher', '/reclamaciones', '/libro-reclamaciones', '/privacidad', '/terminos'];
            $needsSlug = collect($slugRoutes)->contains(fn($r) => str_starts_with($path, $r) || $path === $r);
            if ($needsSlug || $path === '') {
                $newPath = '/' . $project->slug . ($path ?: '/');
                $request->server->set('REQUEST_URI', $newPath . ($request->getQueryString() ? '?' . $request->getQueryString() : ''));
            }
        }

        return $next($request);
    }
}
