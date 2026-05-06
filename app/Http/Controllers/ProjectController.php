<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Module;
use Database\Seeders\DefaultCatalogsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function updateModules(Request $request, Project $target)
    {
        $this->authorizeProject($target);
        $enabledIds = $request->input('modules', []);
        $allModules = Module::all();
        $sync = [];
        foreach ($allModules as $module) {
            $sync[$module->id] = ['is_active' => in_array($module->id, $enabledIds)];
        }
        $target->modules()->sync($sync);
        return response()->json(['ok' => true]);
    }

    public function toggleStatus(Request $request, Project $target)
    {
        $this->authorizeProject($target);
        $target->update(['is_active' => !$target->is_active]);
        return response()->json(['is_active' => $target->is_active]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'category'    => 'nullable|string|max:80',
            'phone'       => 'nullable|string|max:30',
            'whatsapp'    => 'nullable|string|max:30',
            'address'     => 'nullable|string|max:200',
        ]);

        $data['owner_id']  = auth()->id();
        $data['slug']      = Str::slug($data['name']) . '-' . strtolower(Str::random(4));
        $data['is_active'] = true;

        $project = Project::create($data);

        foreach (Module::all() as $module) {
            $project->modules()->attach($module->id, ['is_active' => true]);
        }

        DefaultCatalogsSeeder::seedForProject($project);

        if ($request->wantsJson()) {
            return response()->json(['project' => $project->only(['id','name','category','is_active','slug'])]);
        }

        return redirect()->route('dashboard', ['project' => $project->id])
            ->with('success', 'Negocio creado exitosamente.');
    }

    public function update(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'category'    => 'nullable|string|max:80',
            'phone'       => 'nullable|string|max:30',
            'whatsapp'    => 'nullable|string|max:30',
            'address'     => 'nullable|string|max:200',
            'is_active'   => 'nullable|boolean',
            'ruc'         => 'nullable|string|max:30',
            'email'       => 'nullable|email|max:100',
            'country'     => 'nullable|string|max:5',
            'currency'    => 'nullable|string|max:5',
        ]);

        $project->update($data);

        foreach (['ruc', 'email', 'country', 'currency'] as $key) {
            if ($request->has($key)) {
                $project->settings()->updateOrCreate(['key' => $key], ['value' => $request->input($key)]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['project' => $project->only(['id','name','category','is_active','slug','phone','address'])]);
        }

        return redirect()->route('workspace')->with('success', 'Negocio actualizado.');
    }

    public function destroy(Request $request, Project $project)
    {
        $this->authorizeProject($project);
        $project->delete();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('workspace')->with('success', 'Negocio eliminado.');
    }
}
