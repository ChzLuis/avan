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
}
