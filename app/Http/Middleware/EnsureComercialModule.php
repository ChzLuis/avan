<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Entitlement a nivel servidor para el App Shell /bixosales (RISK-013).
 *
 * ACCESS = TENANT_ENTITLEMENT (módulo contratado) AND USER_PERMISSION (can:).
 * El `can:` de cada ruta ya cubre el permiso del usuario; este middleware
 * añade la otra mitad: si el negocio NO contrató el módulo de la capacidad,
 * la ruta responde 403 aunque el usuario tenga el permiso — ya no basta con
 * ocultar el menú.
 *
 * Mapea por el NOMBRE de la ruta (una sola línea en el grupo cubre todas las
 * sub-rutas de cada capacidad). Lo no mapeado pasa: sólo se gatea aquello con
 * un módulo/entitlement claro.
 */
class EnsureComercialModule
{
    /**
     * prefijo del nombre de ruta (sin `bixosales.`) → clave de módulo.
     *
     * Sólo se gatean los módulos que TODOS los tenants activos ya tienen en
     * `project_modules` (medido 2026-08-28), para que activar el entitlement no
     * corte a nadie que hoy opera. `logistics` queda FUERA a propósito: tecsist
     * y ferreteria-demo no lo tienen contratado; entra al gate tras el backfill
     * de project_modules (TD-017).
     */
    private const MAPA = [
        'pedidos'      => 'orders',
        'pos'          => 'orders',
        'clientes'     => 'clients',
        'facturas'     => 'invoices',
        'boletas'      => 'invoices',
        'comprobantes' => 'invoices',
        'cotizaciones' => 'quotes',
        'productos'    => 'catalog',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $nombre = (string) optional($request->route())->getName();
        $corto  = str_starts_with($nombre, 'bixosales.')
            ? substr($nombre, strlen('bixosales.'))
            : $nombre;

        $modulo = null;
        foreach (self::MAPA as $prefijo => $key) {
            if ($corto === $prefijo || str_starts_with($corto, $prefijo.'.')) {
                $modulo = $key;
                break;
            }
        }

        if ($modulo !== null) {
            $projectId = session('comercial_project_id') ?? session('active_project_id');
            $project   = $projectId ? Project::find($projectId) : null;
            abort_unless($project && $project->hasModule($modulo), 403,
                'Tu plan no incluye este módulo.');
        }

        return $next($request);
    }
}
