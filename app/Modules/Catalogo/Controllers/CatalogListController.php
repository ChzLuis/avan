<?php

namespace App\Modules\Catalogo\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Project;
use App\Modules\Catalogo\Models\CatalogList;
use App\Modules\Catalogo\Models\CatalogValue;
use Illuminate\Http\Request;

class CatalogListController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $catalogs = $project->catalogLists()->orderBy('sort_order')->get();
        return view('catalogo::catalogs.index', compact('project', 'catalogs'));
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
        // El catalogo ya es del proyecto, pero el VALOR llega por su propio ID:
        // sin este candado se podia editar el valor de un catalogo ajeno
        // pasando un catalogo propio como fachada.
        abort_unless($value->catalog_list_id === $catalog->id, 404);
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
        abort_unless($value->catalog_list_id === $catalog->id, 404);
        $value->delete();
        return response()->json(['ok' => true]);
    }

    /**
     * Logo o imagen de un valor (marcas, sobre todo). Se procesa con el perfil
     * `logo` y se guarda en marcas/{proyecto}. Con `remove=1` se quita.
     */
    public function imagenValor(Request $request, CatalogList $catalog, CatalogValue $value)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeForProject($catalog, $project);
        abort_unless($value->catalog_list_id === $catalog->id, 404);

        if ($request->boolean('remove')) {
            $value->update(['image_url' => null]);
            return response()->json(['ok' => true, 'image_url' => null]);
        }
        $request->validate(['image' => 'required|image|max:4096']);
        try {
            $resultado = app(\App\Support\Imagen\ProcesadorImagenes::class)
                ->procesar($request->file('image'), "marcas/{$project->id}", 'logo');
        } catch (\App\Support\Imagen\ImagenNoProcesable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
        $value->update(['image_url' => $resultado->principal]);
        return response()->json(['ok' => true, 'image_url' => $resultado->principal, 'url' => $resultado->urlPrincipal()]);
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
            'label'       => 'required|string|max:100',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string|max:300',
            'is_active'   => 'boolean',
        ];
    }

    private function authorizeForProject(CatalogList $catalog, Project $project): void
    {
        abort_unless($catalog->project_id === $project->id, 403);
    }
}
