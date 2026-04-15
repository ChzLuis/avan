<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;

class WorkspaceController extends Controller
{
    public function index()
    {
        $userId   = auth()->id();
        $projects = Project::where('owner_id', $userId)
            ->orWhereHas('members', fn($q) => $q->where('user_id', $userId))
            ->latest()
            ->get();

        if ($projects->isEmpty()) {
            return view('workspace.no-project');
        }

        // Si ya tiene uno activo en sesión, ir directo
        $activeId = session('active_project_id');
        if ($activeId && $projects->contains('id', $activeId)) {
            return redirect()->route('dashboard');
        }

        // Usar el primer proyecto disponible
        session(['active_project_id' => $projects->first()->id]);
        return redirect()->route('dashboard');
    }

    public function select(Project $project)
    {
        $userId = auth()->id();
        $isMember = $project->owner_id === $userId
            || $project->members()->where('user_id', $userId)->exists();

        abort_unless($isMember, 403);

        session(['active_project_id' => $project->id]);

        return redirect()->route('dashboard');
    }
}
