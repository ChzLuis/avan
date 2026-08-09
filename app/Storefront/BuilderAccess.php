<?php

namespace App\Storefront;

use App\Models\Project;
use App\Models\User;

/**
 * Feature flag del Constructor por proyecto (B0).
 *
 * Estado guardado como project_setting `builder_mode`:
 *   enabled  → visible para todos los miembros del proyecto (default)
 *   beta     → visible para owner y superadmin del proyecto
 *   disabled → solo modo clásico (opt-out por tienda)
 *
 * Sin dependencia de un flag global en .env; el superadmin puede activar
 * tienda por tienda y regresar a "disabled" sin alterar datos.
 */
class BuilderAccess
{
    public const MODES = ['disabled', 'beta', 'enabled'];

    public static function modeFor(Project $project): string
    {
        $mode = (string) $project->setting('builder_mode', 'enabled');

        return in_array($mode, self::MODES, true) ? $mode : 'enabled';
    }

    public static function allows(Project $project, ?User $user): bool
    {
        if (!$user) return false;

        return match (self::modeFor($project)) {
            'enabled' => true,
            'beta' => (bool) ($user->is_superadmin || $project->owner_id === $user->id),
            default => false,
        };
    }

    public static function setMode(Project $project, string $mode): void
    {
        if (!in_array($mode, self::MODES, true)) return;
        $project->settings()->updateOrCreate(['key' => 'builder_mode'], ['value' => $mode]);
    }
}
