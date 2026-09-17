<?php

namespace App\Modules\Personas\Controllers;

use App\Http\Controllers\Controller;

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
                'description' => (string) $r->description,
                'users_count' => $r->users_count,
                'permissions' => $r->permissions->pluck('name')->values(),
            ]);

        $allPermissions = Permission::all()
            ->groupBy(fn($p) => explode('.', $p->name)[0] ?? 'general');

        return view('personas::roles.index', compact('project', 'roles', 'allPermissions'));
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
        // La descripción se guarda aparte de firstOrCreate: si el rol ya existía,
        // el atributo no se aplicaría y volveríamos al fallo que esto corrige.
        $role->description = $data['description'] ?? null;
        $esNuevo = !$role->exists || $role->wasRecentlyCreated;
        $role->save();
        $role->syncPermissions($data['permissions'] ?? []);

        \App\Models\AccessEvent::registrar(
            $esNuevo ? 'role_created' : 'permissions_changed',
            $project->id,
            $role->name,
            null,
            ['added' => $data['permissions'] ?? []]
        );

        return response()->json([
            'role' => [
                'id'          => $role->id,
                'name'        => $role->name,
                'display'     => $role->name,
                'description' => (string) $role->description,
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
        $nombreAnterior = $role->name;
        $permisosAntes  = $role->permissions->pluck('name')->all();

        if (!empty($data['name'])) {
            $role->name = trim($data['name']);
        }
        // Solo se toca si viene en la petición: con ?? null, una llamada que no
        // mandara el campo borraría la descripción existente.
        if (array_key_exists('description', $data)) {
            $role->description = $data['description'];
        }
        $role->save();
        $role->syncPermissions($data['permissions'] ?? []);

        // Auditoría: qué se amplió y qué se recortó, no la lista completa.
        $permisosDespues = $data['permissions'] ?? [];
        if ($nombreAnterior !== $role->name) {
            \App\Models\AccessEvent::registrar('role_renamed', $project->id, $role->name, null, [
                'from' => $nombreAnterior, 'to' => $role->name,
            ]);
        }
        $anadidos = array_values(array_diff($permisosDespues, $permisosAntes));
        $quitados = array_values(array_diff($permisosAntes, $permisosDespues));
        if ($anadidos || $quitados) {
            \App\Models\AccessEvent::registrar('permissions_changed', $project->id, $role->name, null, [
                'added' => $anadidos, 'removed' => $quitados,
            ]);
        }

        return response()->json([
            'ok'          => true,
            'display'     => $role->name,
            'description' => (string) $role->description,
        ]);
    }

    public function destroy(Role $role)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $this->authorizeProject($project);
        if ($role->users()->count() > 0) {
            return response()->json(['error' => 'El rol tiene usuarios asignados'], 422);
        }
        $nombre = $role->name;
        $role->delete();

        \App\Models\AccessEvent::registrar('role_deleted', $project->id, $nombre);

        return response()->json(['ok' => true]);
    }
}
