<?php
namespace App\Http\Controllers;
use App\Models\Project;
use App\Models\UserGroup;
use Illuminate\Http\Request;

class UserGroupController extends Controller
{
    public function index() {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $type   = request('type', 'client'); // 'client' | 'employee'
        $groups = $project->userGroups()->where('type', $type)->orderBy('name')->get();
        return view('company.groups', compact('project', 'groups', 'type'));
    }
    private function rules(): array {
        return [
            'name'        => 'required|string|max:100',
            'type'        => 'required|in:client,employee',
            'description' => 'nullable|string',
            'color'       => 'nullable|string|max:7',
            'is_active'   => 'boolean',
        ];
    }
    public function store(Request $request) {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $data = $request->validate($this->rules());
        $data['project_id'] = $project->id;
        $data['is_active']  = $request->boolean('is_active', true);
        $group = UserGroup::create($data);
        if ($request->expectsJson()) return response()->json(['group' => $this->row($group)]);
        return back()->with('success', 'Grupo creado.');
    }
    public function update(Request $request, UserGroup $userGroup) {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($userGroup->project_id === $project->id, 403);
        $data = $request->validate($this->rules());
        $data['is_active'] = $request->boolean('is_active');
        $userGroup->update($data);
        if ($request->expectsJson()) return response()->json(['group' => $this->row($userGroup->fresh())]);
        return back()->with('success', 'Grupo actualizado.');
    }
    public function destroy(UserGroup $userGroup) {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($userGroup->project_id === $project->id, 403);
        $userGroup->delete();
        if (request()->expectsJson()) return response()->json(['ok' => true]);
        return back()->with('success', 'Grupo eliminado.');
    }
    private function row(UserGroup $g): array {
        return ['id'=>$g->id,'name'=>$g->name,'type'=>$g->type,'description'=>$g->description,'color'=>$g->color,'is_active'=>(bool)$g->is_active];
    }
}
