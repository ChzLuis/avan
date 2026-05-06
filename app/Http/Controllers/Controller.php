<?php

namespace App\Http\Controllers;

use App\Models\Project;

abstract class Controller
{
    /** Aborta con 403 si el usuario no es superadmin ni owner del proyecto. */
    protected function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->is_superadmin || $user->id === $project->owner_id), 403);
    }
}
