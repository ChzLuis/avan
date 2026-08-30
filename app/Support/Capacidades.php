<?php

namespace App\Support;

use App\Models\Project;
use App\Models\User;

/**
 * Capacidades restringidas del Workspace (matriz: BIXO_CAPACIDADES_MATRIZ.md).
 *
 * Un permiso Spatie NO basta para las herramientas sensibles: la regla de la
 * reestructuración 2026-08-30 es ENTITLEMENT + PERMISSION + FEATURE FLAG.
 * El flag vive como ajuste del proyecto (`cap_<clave>` = 1) y SOLO Eskala lo
 * enciende: así una función puede ser exclusiva de soporte/implementación o
 * de planes concretos sin tocar código.
 *
 * - superadmin: pasa siempre (es quien opera las herramientas internas).
 * - dueño del proyecto: pasa el PERMISO (como CheckPermission), pero NUNCA
 *   se salta el flag — restringido significa restringido también para él.
 */
final class Capacidades
{
    /**
     * ['modulo' => entitlement requerido|null, 'permiso' => spatie ('|'=ANY),
     *  'flag' => bool exige cap_<clave>=1].
     */
    private const DEFINICIONES = [
        // Gestión de plantillas de diseño (crear/importar/versionar/aplicar):
        // administración de presets, nivel interno (ESKALA_ONLY por defecto).
        'plantillas'       => ['modulo' => null,      'permiso' => 'settings.diseno',  'flag' => true],
        // Constructor avanzado: cambio de motor/plantilla y herramientas que
        // pueden romper el storefront.
        'builder_avanzado' => ['modulo' => 'catalog', 'permiso' => 'settings.diseno',  'flag' => true],
        // SEO técnico: analytics/píxeles, schema, verificaciones.
        'seo_avanzado'     => ['modulo' => null,      'permiso' => 'settings.negocio', 'flag' => true],
    ];

    public static function existe(string $clave): bool
    {
        return isset(self::DEFINICIONES[$clave]);
    }

    public static function permite(?Project $project, ?User $user, string $clave): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->is_superadmin) {
            return true;
        }

        $def = self::DEFINICIONES[$clave] ?? null;
        if ($def === null || ! $project) {
            return false; // capacidad desconocida o sin tenant: cerrado.
        }

        if ($def['modulo'] && ! $project->hasModule($def['modulo'])) {
            return false;
        }

        $esDueno = $project->owner_id === $user->id;
        if (! $esDueno) {
            $alguno = false;
            foreach (explode('|', $def['permiso']) as $p) {
                if ($user->can(trim($p))) {
                    $alguno = true;
                    break;
                }
            }
            if (! $alguno) {
                return false;
            }
        }

        if ($def['flag'] && (int) $project->setting('cap_' . $clave, 0) !== 1) {
            return false;
        }

        return true;
    }
}
