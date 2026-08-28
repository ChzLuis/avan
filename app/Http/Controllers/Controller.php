<?php

namespace App\Http\Controllers;

use App\Models\Project;

abstract class Controller
{
    /** Aborta con 403 si el usuario no tiene acceso al proyecto. */
    protected function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        $isMember = $user && (
            $user->is_superadmin ||
            $user->id === $project->owner_id ||
            $project->members()->where('user_id', $user->id)->exists()
        );
        abort_unless($isMember, 403);
    }

    /**
     * Escritura de ajustes del negocio: dueño, superadmin o un permiso de
     * negocio (nuevo o legacy). Ser miembro a secas no autoriza.
     */
    protected function authorizeGestionNegocio(Project $project): void
    {
        $user = auth()->user();
        $puede = $user && (
            $user->is_superadmin ||
            $user->id === $project->owner_id ||
            $user->can('settings.negocio') ||
            $user->can('settings.editar') ||
            $user->can('manage-settings')
        );
        abort_unless($puede, 403);
    }
}
