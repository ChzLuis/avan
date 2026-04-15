<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $roles = Role::withCount('users')
            ->with('permissions')
            ->get()
            ->map(fn($r) => [
                'id'          => $r->id,
                'name'        => $r->name,
                'display'     => ucwords(str_replace('_', ' ', $r->name)),
                'description' => '',
                'users_count' => $r->users_count,
                'permissions' => $r->permissions->pluck('name')->values(),
            ]);

        $allPermissions = Permission::all()
            ->groupBy(fn($p) => explode('.', $p->name)[0] ?? 'general');

        return view('roles.index', compact('project', 'roles', 'allPermissions'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless(auth()->id() === $project->owner_id, 403);
        $data = $request->validate([
            'name'        => 'required|string|max:60',
            'description' => 'nullable|string|max:200',
            'permissions' => 'array',
        ]);
        $role = Role::firstOrCreate(['name' => \Illuminate\Support\Str::slug($data['name']), 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);
        return response()->json([
            'role' => [
                'id' => $role->id, 'name' => $role->name,
                'display' => $data['name'], 'users_count' => 0,
                'permissions' => collect($data['permissions'] ?? []),
            ]
        ]);
    }

    public function update(Request $request, Role $role)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless(auth()->id() === $project->owner_id, 403);
        $data = $request->validate(['permissions' => 'array']);
        $role->syncPermissions($data['permissions'] ?? []);
        return response()->json(['ok' => true]);
    }

    public function destroy(Role $role)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless(auth()->id() === $project->owner_id, 403);
        if ($role->users()->count() > 0) {
            return response()->json(['error' => 'El rol tiene usuarios asignados'], 422);
        }
        $role->delete();
        return response()->json(['ok' => true]);
    }
}
