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
            ->orderBy('name')
            ->get()
            ->map(fn($r) => [
                'id'          => $r->id,
                'name'        => $r->name,
                'display'     => $r->name,
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
        $this->authorizeProject($project);
        $data = $request->validate([
            'name'        => 'required|string|max:60',
            'description' => 'nullable|string|max:200',
            'permissions' => 'array',
        ]);
        // Guardamos el nombre tal cual (puede tener espacios y mayúsculas)
        $role = Role::firstOrCreate(['name' => trim($data['name']), 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);
        return response()->json([
            'role' => [
                'id'          => $role->id,
                'name'        => $role->name,
                'display'     => $role->name,
                'description' => $data['description'] ?? '',
                'users_count' => 0,
                'permissions' => collect($data['permissions'] ?? [])->values(),
            ]
        ]);
    }

    public function update(Request $request, Role $role)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);
        $data = $request->validate([
            'name'        => 'sometimes|string|max:60',
            'description' => 'nullable|string|max:200',
            'permissions' => 'array',
        ]);
        if (!empty($data['name'])) {
            $role->name = trim($data['name']);
            $role->save();
        }
        $role->syncPermissions($data['permissions'] ?? []);
        return response()->json(['ok' => true, 'display' => $role->name]);
    }

    public function destroy(Role $role)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);
        if ($role->users()->count() > 0) {
            return response()->json(['error' => 'El rol tiene usuarios asignados'], 422);
        }
        $role->delete();
        return response()->json(['ok' => true]);
    }
}
