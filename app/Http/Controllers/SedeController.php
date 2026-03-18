<?php
namespace App\Http\Controllers;
use App\Models\Project;
use App\Models\Sede;
use Illuminate\Http\Request;

class SedeController extends Controller
{
    public function index(Project $project) {
        $sedes = $project->sedes()->orderBy('name')->get();
        return view('company.sedes', compact('project', 'sedes'));
    }
    private function rules(): array {
        return [
            'name'    => 'required|string|max:100',
            'address' => 'nullable|string|max:200',
            'phone'   => 'nullable|string|max:30',
            'email'   => 'nullable|email|max:100',
            'manager' => 'nullable|string|max:100',
            'notes'   => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
    public function store(Request $request, Project $project) {
        $data = $request->validate($this->rules());
        $data['project_id'] = $project->id;
        $data['is_active'] = $request->boolean('is_active', true);
        $sede = Sede::create($data);
        if ($request->expectsJson()) return response()->json(['sede' => $this->row($sede)]);
        return back()->with('success', 'Sede creada.');
    }
    public function update(Request $request, Project $project, Sede $sede) {
        abort_unless($sede->project_id === $project->id, 403);
        $data = $request->validate($this->rules());
        $data['is_active'] = $request->boolean('is_active');
        $sede->update($data);
        if ($request->expectsJson()) return response()->json(['sede' => $this->row($sede->fresh())]);
        return back()->with('success', 'Sede actualizada.');
    }
    public function destroy(Project $project, Sede $sede) {
        abort_unless($sede->project_id === $project->id, 403);
        $sede->delete();
        if (request()->expectsJson()) return response()->json(['ok' => true]);
        return back()->with('success', 'Sede eliminada.');
    }
    private function row(Sede $s): array {
        return ['id'=>$s->id,'name'=>$s->name,'address'=>$s->address,'phone'=>$s->phone,'email'=>$s->email,'manager'=>$s->manager,'notes'=>$s->notes,'is_active'=>(bool)$s->is_active];
    }
}
