<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Module;
use Database\Seeders\DefaultCatalogsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function panel(Project $project)
    {
        $allProjects = \App\Models\Project::where('owner_id', auth()->id())
            ->orWhereHas('members', fn($q) => $q->where('user_id', auth()->id()))
            ->orderByDesc('updated_at')->get();
        $allModules = Module::orderBy('sort_order')->get();
        return view('projects.panel', compact('project', 'allProjects', 'allModules'));
    }

    public function updateModules(Request $request, Project $project, Project $target)
    {
        abort_unless(auth()->id() === $target->owner_id, 403);
        $enabledIds = $request->input('modules', []);
        $allModules = Module::all();
        $sync = [];
        foreach ($allModules as $module) {
            $sync[$module->id] = ['is_active' => in_array($module->id, $enabledIds)];
        }
        $target->modules()->sync($sync);
        return response()->json(['ok' => true]);
    }

    public function toggleStatus(Request $request, Project $project, Project $target)
    {
        abort_unless(auth()->id() === $target->owner_id, 403);
        $target->update(['is_active' => !$target->is_active]);
        return response()->json(['is_active' => $target->is_active]);
    }

    public function create()
    {
        return view('projects.create');
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
        $data['slug']      = Str::slug($data['name']) . '-' . Str::random(4);
        $data['is_active'] = true;

        $project = Project::create($data);

        $defaults = Module::whereIn('key', ['dashboard', 'catalog', 'orders', 'clients', 'settings'])->get();
        foreach ($defaults as $module) {
            $project->modules()->attach($module->id, ['is_active' => true]);
        }

        DefaultCatalogsSeeder::seedForProject($project);

        if ($request->wantsJson()) {
            return response()->json(['project' => $project->only(['id','name','category','is_active','slug'])]);
        }

        return redirect()->route('dashboard', ['project' => $project->id])
            ->with('success', 'Negocio creado exitosamente.');
    }

    public function edit(Project $project)
    {
        abort_unless(auth()->id() === $project->owner_id, 403);
        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        abort_unless(auth()->id() === $project->owner_id, 403);

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
        abort_unless(auth()->id() === $project->owner_id, 403);
        $project->delete();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('workspace')->with('success', 'Negocio eliminado.');
    }
}
