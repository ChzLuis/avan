<?php

namespace App\Support;

use App\Modules\Ventas\Support\OrderFlow;

use App\Models\Project;

/**
 * @deprecated Usar App\Modules\Ventas\Support\OrderFlow (flujo genérico por rubro).
 *
 * Se mantiene como fachada para no romper el código existente. Todos los
 * métodos delegan en OrderFlow, que ahora maneja los estados por rubro.
 * El catálogo sin proyecto asume lavandería (compat con llamadas antiguas).
 */
class LaundryFlow
{
    /** Catálogo de lavandería (compat: método sin proyecto). */
    public static function catalog(): array
    {
        // Reconstruye el catálogo de lavandería vía un proyecto ficticio.
        $p = new Project(['category' => 'lavanderia']);
        return OrderFlow::catalog($p);
    }

    public static function defaultActive(): array
    {
        return OrderFlow::defaultActive(new Project(['category' => 'lavanderia']));
    }

    public static function coreKeys(): array
    {
        return OrderFlow::coreKeys(new Project(['category' => 'lavanderia']));
    }

    public static function activeKeys(Project $project): array
    {
        return OrderFlow::activeKeys($project);
    }

    public static function config(Project $project): array
    {
        return OrderFlow::config($project);
    }

    public static function times(Project $project, string $key): array
    {
        return OrderFlow::times($project, $key);
    }

    public static function activeStates(Project $project): array
    {
        return OrderFlow::activeStates($project);
    }

    public static function slaMinutes(Project $project, string $key): ?int
    {
        return OrderFlow::slaMinutes($project, $key);
    }

    public static function alertsOn(Project $project, string $key): bool
    {
        return OrderFlow::alertsOn($project, $key);
    }

    public static function shouldNotify(string $key, ?Project $project = null): bool
    {
        if ($project) return OrderFlow::shouldNotify($project, $key);
        // Sin proyecto: usar catálogo de lavandería
        $p = new Project(['category' => 'lavanderia']);
        return OrderFlow::catalog($p)[$key]['notify'] ?? false;
    }

    public static function state(string $key): ?array
    {
        $p = new Project(['category' => 'lavanderia']);
        return OrderFlow::state($p, $key);
    }

    public static function nextKey(Project $project, string $current): ?string
    {
        return OrderFlow::nextKey($project, $current);
    }

    public static function slaStatus(Project $project, $order): array
    {
        return OrderFlow::slaStatus($project, $order);
    }

    public static function overdueCount(Project $project): int
    {
        return OrderFlow::overdueCount($project);
    }

    public static function toGenericStatus(string $laundryStatus): string
    {
        $p = new Project(['category' => 'lavanderia']);
        return OrderFlow::toGenericStatus($p, $laundryStatus);
    }

    public static function notifyMessage(string $key, $order, string $negocio, ?Project $project = null): string
    {
        $p = $project ?? new Project(['category' => 'lavanderia']);
        return OrderFlow::notifyMessage($p, $key, $order, $negocio);
    }
}
