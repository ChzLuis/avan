<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Project;
use Illuminate\Http\Request;

class AdminProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with(['owner', 'members'])
            ->withCount(['orders', 'clients', 'invoices'])
            ->latest()
            ->get();

        return view('admin.projects.index', compact('projects'));
    }

    public function show(Project $project)
    {
        $project->load(['owner', 'members.user', 'modules']);
        $allModules = Module::orderBy('sort_order')->get();
        $activeModuleIds = $project->modules()->wherePivot('is_active', true)->pluck('modules.id');

        return view('admin.projects.show', compact('project', 'allModules', 'activeModuleIds'));
    }

    public function toggle(Project $project)
    {
        $project->update(['is_active' => ! $project->is_active]);
        $status = $project->is_active ? 'activado' : 'suspendido';
        return back()->with('success', "Proyecto \"{$project->name}\" {$status}.");
    }

    public function updateModules(Request $request, Project $project)
    {
        $moduleIds = $request->input('module_ids', []);

        // Sync: activa los seleccionados, desactiva el resto
        $all = Module::pluck('id');
        foreach ($all as $id) {
            $project->modules()->syncWithoutDetaching([$id => ['is_active' => in_array($id, $moduleIds)]]);
        }

        return back()->with('success', 'Módulos actualizados.');
    }
}
