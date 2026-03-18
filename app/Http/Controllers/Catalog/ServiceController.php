<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Project $project)
    {
        $categories = $project->categories()->where('is_active', true)->orderBy('sort_order')->get();
        $services   = $project->services()->with('category')->orderBy('sort_order')->get();

        return view('catalog.services.index', compact('project', 'categories', 'services'));
    }

    private function rules(): array
    {
        return [
            'category_id'  => 'nullable|integer',
            'name'         => 'required|string|max:100',
            'description'  => 'nullable|string',
            'price'        => 'required|numeric|min:0',
            'duration_min' => 'nullable|integer|min:1',
            'modality'     => 'nullable|string|max:30',
            'notes'        => 'nullable|string',
            'is_available' => 'boolean',
        ];
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate($this->rules());
        $data['project_id']   = $project->id;
        $data['is_available'] = $request->boolean('is_available', true);

        $service = Service::create($data);

        if ($request->expectsJson()) {
            return response()->json(['service' => $this->serviceRow($service)]);
        }
        return back()->with('success', 'Servicio creado.');
    }

    public function update(Request $request, Project $project, Service $service)
    {
        abort_unless($service->project_id === $project->id, 403);

        $data = $request->validate($this->rules());
        $data['is_available'] = $request->boolean('is_available');
        $service->update($data);

        if ($request->expectsJson()) {
            return response()->json(['service' => $this->serviceRow($service->fresh())]);
        }
        return back()->with('success', 'Servicio actualizado.');
    }

    public function destroy(Project $project, Service $service)
    {
        abort_unless($service->project_id === $project->id, 403);
        $service->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('success', 'Servicio eliminado.');
    }

    private function serviceRow(Service $s): array
    {
        return [
            'id'           => $s->id,
            'name'         => $s->name,
            'description'  => $s->description,
            'notes'        => $s->notes ?? null,
            'price'        => (float)$s->price,
            'duration_min' => $s->duration_min,
            'modality'     => $s->modality ?? null,
            'is_available' => (bool)$s->is_available,
            'category_id'  => $s->category_id,
            'category_name'=> $s->category?->name,
        ];
    }
}
