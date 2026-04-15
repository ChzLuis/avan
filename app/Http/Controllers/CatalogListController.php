<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\CatalogList;
use App\Models\CatalogValue;
use Illuminate\Http\Request;

class CatalogListController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $catalogs = $project->catalogLists()->orderBy('sort_order')->get();
        return view('catalogs.index', compact('project', 'catalogs'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $data = $request->validate($this->catalogRules());
        $data['project_id'] = $project->id;
        $data['is_active']  = $request->boolean('is_active', true);
        return response()->json(['catalog' => CatalogList::create($data)]);
    }

    public function update(Request $request, CatalogList $catalog)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeForProject($catalog, $project);
        $data = $request->validate($this->catalogRules());
        $data['is_active'] = $request->boolean('is_active');
        $catalog->update($data);
        return response()->json(['catalog' => $catalog]);
    }

    public function destroy(CatalogList $catalog)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeForProject($catalog, $project);
        abort_if($catalog->is_system, 403, 'Este catálogo es del sistema y no puede eliminarse.');
        $catalog->delete();
        return response()->json(['ok' => true]);
    }

    public function values(CatalogList $catalog)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeForProject($catalog, $project);
        return response()->json($catalog->values()->orderBy('sort_order')->get());
    }

    public function storeValue(Request $request, CatalogList $catalog)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeForProject($catalog, $project);
        $data = $request->validate($this->valueRules());
        $data['is_active'] = $request->boolean('is_active', true);
        return response()->json(['value' => $catalog->values()->create($data)]);
    }

    public function updateValue(Request $request, CatalogList $catalog, CatalogValue $value)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeForProject($catalog, $project);
        $data = $request->validate($this->valueRules());
        $data['is_active'] = $request->boolean('is_active');
        $value->update($data);
        return response()->json(['value' => $value]);
    }

    public function destroyValue(CatalogList $catalog, CatalogValue $value)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeForProject($catalog, $project);
        $value->delete();
        return response()->json(['ok' => true]);
    }

    private function catalogRules(): array
    {
        return [
            'name'        => 'required|string|max:100',
            'type'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'color'       => 'nullable|string|max:10',
            'is_active'   => 'boolean',
        ];
    }

    private function valueRules(): array
    {
        return [
            'label'     => 'required|string|max:100',
            'code'      => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ];
    }

    private function authorizeForProject(CatalogList $catalog, Project $project): void
    {
        abort_unless($catalog->project_id === $project->id, 403);
    }
}
