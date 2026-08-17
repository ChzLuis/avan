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
        $candidatosDueno = \App\Support\ProjectOwnership::candidatos($project);

        return view('admin.projects.show', compact(
            'project', 'allModules', 'activeModuleIds', 'candidatosDueno'
        ));
    }

    /**
     * Traspasa el negocio a su dueño real.
     *
     * Los 7 proyectos se crearon desde la cuenta de superadmin, así que quedaron
     * a nombre de Eskala y ningún cliente era dueño del suyo. Esto es lo que
     * permite cumplir el §3.4 del plan: separar la plataforma de los negocios.
     */
    public function transferOwnership(Request $request, Project $project)
    {
        $data = $request->validate(['owner_id' => 'required|integer|exists:users,id']);

        $nuevo = \App\Models\User::findOrFail($data['owner_id']);
        \App\Support\ProjectOwnership::transferir($project, $nuevo);

        return back()->with('success', "«{$project->name}» ahora pertenece a {$nuevo->name}.");
    }

    public function toggle(Project $project)
    {
        $project->update(['is_active' => ! $project->is_active]);
        $status = $project->is_active ? 'activado' : 'suspendido';
        return back()->with('success', "Proyecto \"{$project->name}\" {$status}.");
    }

    public function updateSubdomain(Request $request, Project $project)
    {
        $request->validate(['custom_domain' => 'nullable|string|max:253|regex:/^[a-z0-9][a-z0-9\-\.]*[a-z0-9]$/i']);
        $project->update(['custom_domain' => $request->input('custom_domain') ?: null]);
        return back()->with('success', 'Dominio/subdominio actualizado.');
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
