<?php
namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function index(Project $project)
    {
        $proveedores = $project->proveedores()->orderBy('name')->get();
        return view('company.proveedores', compact('project', 'proveedores'));
    }

    private function rules(): array
    {
        return [
            'name'         => 'required|string|max:150',
            'contact_name' => 'nullable|string|max:100',
            'phone'        => 'nullable|string|max:30',
            'email'        => 'nullable|email|max:100',
            'address'      => 'nullable|string|max:200',
            'category'     => 'nullable|string|max:80',
            'notes'        => 'nullable|string',
            'is_active'    => 'boolean',
        ];
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate($this->rules());
        $data['project_id'] = $project->id;
        $data['is_active']  = $request->boolean('is_active', true);
        $p = Proveedor::create($data);
        return response()->json(['proveedor' => $this->row($p)]);
    }

    public function update(Request $request, Project $project, Proveedor $proveedor)
    {
        abort_unless($proveedor->project_id === $project->id, 403);
        $data = $request->validate($this->rules());
        $data['is_active'] = $request->boolean('is_active');
        $proveedor->update($data);
        return response()->json(['proveedor' => $this->row($proveedor->fresh())]);
    }

    public function destroy(Project $project, Proveedor $proveedor)
    {
        abort_unless($proveedor->project_id === $project->id, 403);
        $proveedor->delete();
        return response()->json(['ok' => true]);
    }

    private function row(Proveedor $p): array
    {
        return [
            'id'           => $p->id,
            'name'         => $p->name,
            'contact_name' => $p->contact_name ?? '',
            'phone'        => $p->phone ?? '',
            'email'        => $p->email ?? '',
            'address'      => $p->address ?? '',
            'category'     => $p->category ?? '',
            'notes'        => $p->notes ?? '',
            'is_active'    => (bool) $p->is_active,
        ];
    }
}
