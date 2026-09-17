<?php

namespace App\Modules\Personas\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class HRController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $employees     = $project->employees()->orderBy('name')->get();
        $departments   = $this->catValues($project, 'department');
        $jobTitles     = $this->catValues($project, 'job_title');
        $contractTypes = $this->catValues($project, 'contract_type');
        $availableRoles = Role::orderBy('name')->get()->map(fn($r) => [
            'id'      => $r->id,
            'name'    => $r->name,
            'display' => $r->name,
        ]);
        return view('personas::hr.employees', compact('project', 'employees', 'departments', 'jobTitles', 'contractTypes', 'availableRoles'));
    }

    /**
     * Materializa el perfil de un empleado en el equipo de su proyecto.
     *
     * `setPermissionsTeamId` acota la escritura a ese negocio: asignar un perfil
     * en MegaHogar no toca los roles que esa persona tenga en otro proyecto. Se
     * restaura el equipo anterior para no dejar contaminada la petición.
     */
    private function aplicarPerfil(Employee $employee, int $projectId): void
    {
        $equipoPrevio = getPermissionsTeamId();

        try {
            setPermissionsTeamId($projectId);
            $usuario = $employee->user;
            if (!$usuario) {
                return;
            }

            $perfil = $employee->spatie_role;
            if ($perfil && \Spatie\Permission\Models\Role::where('name', $perfil)->exists()) {
                $usuario->syncRoles([$perfil]);
            } else {
                $usuario->syncRoles([]);
            }
        } finally {
            setPermissionsTeamId($equipoPrevio);
        }
    }

    private function catValues(Project $project, string $type): \Illuminate\Support\Collection
    {
        $list = $project->catalogLists()->where('type', $type)->first();
        return $list ? $list->values()->where('is_active', true)->orderBy('sort_order')->pluck('label') : collect();
    }

    private function rules(): array
    {
        return [
            'name'                  => 'required|string|max:100',
            'role'                  => 'nullable|string|max:80',
            'spatie_role'           => 'nullable|string|max:80',
            'area'                  => 'nullable|string|max:80',
            'phone'                 => 'nullable|string|max:30',
            'email'                 => 'nullable|email|max:100',
            'hire_date'             => 'nullable|date',
            'is_active'             => 'boolean',
            'username'              => 'nullable|string|max:50|regex:/^[a-zA-Z0-9_.\-]+$/',
            'password'              => 'nullable|string|min:6|confirmed',
        ];
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $data = $request->validate($this->rules());
        $data['project_id'] = $project->id;
        $data['is_active']  = $request->boolean('is_active', true);

        $plainPassword = null;

        // Crear usuario si se proporcionó username o email
        $hasCredentials = !empty($data['username']) || !empty($data['email']);
        if ($hasCredentials) {
            $plainPassword = !empty($data['password']) ? $data['password'] : Str::random(10);
            $username = !empty($data['username'])
                ? $data['username']
                : $this->generateUsername($data['name']);
            // Asegurar username único
            $base = $username; $i = 1;
            while (User::where('username', $username)->exists()) {
                $username = $base . $i++;
            }
            // Solo reutilizar un user existente si ya es empleado de este mismo proyecto
            $existingUser = null;
            if (!empty($data['email'])) {
                $candidate = User::where('email', $data['email'])->first();
                if ($candidate && Employee::where('user_id', $candidate->id)->where('project_id', $project->id)->exists()) {
                    $existingUser = $candidate;
                }
            }
            if (!$existingUser) {
                $user = User::create([
                    'name'              => $data['name'],
                    'username'          => $username,
                    'email'             => $data['email'] ?? null,
                    'password'          => Hash::make($plainPassword),
                    'email_verified_at' => now(),
                ]);
                $data['user_id'] = $user->id;
            } else {
                $user = $existingUser;
                $data['user_id'] = $existingUser->id;
                $plainPassword = null;
            }

            // Agregar como miembro del proyecto si no lo es ya
            if (!empty($data['user_id'])) {
                ProjectMember::firstOrCreate([
                    'project_id' => $project->id,
                    'user_id'    => $data['user_id'],
                ]);
            }
        }

        unset($data['username'], $data['password'], $data['password_confirmation']);

        $employee = Employee::create($data);

        if ($employee->spatie_role && $employee->user_id) {
            $this->aplicarPerfil($employee, $project->id);
        }

        if ($employee->spatie_role) {
            \App\Modules\Control\Models\AccessEvent::registrar(
                'profile_assigned',
                $project->id,
                $employee->spatie_role,
                $employee->user_id,
                ['from' => null, 'to' => $employee->spatie_role]
            );
        }

        if ($request->expectsJson()) {
            $row = $this->row($employee);
            if ($plainPassword) {
                $row['generated_password'] = $plainPassword;
                $row['generated_username'] = $employee->user->username ?? null;
            }
            return response()->json(['employee' => $row]);
        }
        return back()->with('success', 'Empleado creado.');
    }

    public function update(Request $request, Employee $employee)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($employee->project_id === $project->id, 403);
        $data = $request->validate($this->rules());
        $data['is_active'] = $request->boolean('is_active');

        $plainPassword = null;
        $hasCredentials = !empty($data['username']) || !empty($data['email']);

        if (!$employee->user_id && $hasCredentials) {
            // Sin usuario aún → crear
            $plainPassword = !empty($data['password']) ? $data['password'] : Str::random(10);
            $username = !empty($data['username'])
                ? $data['username']
                : $this->generateUsername($data['name']);
            $base = $username; $i = 1;
            while (User::where('username', $username)->exists()) {
                $username = $base . $i++;
            }
            // Solo reutilizar si ya es empleado de este proyecto
            $existingUser = null;
            if (!empty($data['email'])) {
                $candidate = User::where('email', $data['email'])->first();
                if ($candidate && Employee::where('user_id', $candidate->id)->where('project_id', $project->id)->exists()) {
                    $existingUser = $candidate;
                }
            }

            if (!$existingUser) {
                $user = User::create([
                    'name'              => $data['name'],
                    'username'          => $username,
                    'email'             => $data['email'] ?? null,
                    'password'          => Hash::make($plainPassword),
                    'email_verified_at' => now(),
                ]);
                $data['user_id'] = $user->id;
            } else {
                $data['user_id'] = $existingUser->id;
                $plainPassword = null;
            }

            // Agregar como miembro del proyecto
            ProjectMember::firstOrCreate([
                'project_id' => $project->id,
                'user_id'    => $data['user_id'],
            ]);
        } elseif ($employee->user_id) {
            // Ya tiene usuario → actualizar si cambió algo
            $user = $employee->user;
            if ($user) {
                $userUpdate = [];
                if (!empty($data['username']) && $data['username'] !== $user->username) {
                    $userUpdate['username'] = $data['username'];
                }
                if (!empty($data['email']) && $data['email'] !== $user->email) {
                    $userUpdate['email'] = $data['email'];
                }
                if (!empty($data['password'])) {
                    $userUpdate['password'] = Hash::make($data['password']);
                    $plainPassword = $data['password'];
                }
                if (!empty($userUpdate)) $user->update($userUpdate);
            }
        }

        unset($data['username'], $data['password'], $data['password_confirmation']);

        $perfilAnterior = $employee->spatie_role;
        $employee->update($data);

        // El perfil se materializa aquí mismo, en el equipo de este proyecto.
        // Antes solo lo hacía SetActiveProject en la siguiente petición de esa
        // persona, así que entre guardar y su próximo acceso el cambio no existía.
        if ($perfilAnterior !== $employee->spatie_role && $employee->user_id) {
            $this->aplicarPerfil($employee, $project->id);
        }

        // Auditoría de accesos: cambiar el perfil de una persona es un cambio de
        // acceso, y hasta ahora no dejaba rastro en ninguna parte.
        if ($perfilAnterior !== $employee->spatie_role) {
            \App\Modules\Control\Models\AccessEvent::registrar(
                $employee->spatie_role ? 'profile_assigned' : 'profile_removed',
                $project->id,
                $employee->spatie_role ?: $perfilAnterior,
                $employee->user_id,
                ['from' => $perfilAnterior, 'to' => $employee->spatie_role]
            );
        }

        if ($request->expectsJson()) {
            $row = $this->row($employee->fresh());
            if ($plainPassword) {
                $row['generated_password'] = $plainPassword;
                $row['generated_username'] = $employee->fresh()->user->username ?? null;
            }
            return response()->json(['employee' => $row]);
        }
        return back()->with('success', 'Empleado actualizado.');
    }

    public function destroy(Employee $employee)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($employee->project_id === $project->id, 403);

        // Un negocio no puede quedarse sin dueño: si esta ficha es la del dueño,
        // primero hay que traspasar la propiedad a otra persona.
        if ($employee->user_id) {
            \App\Support\ProjectOwnership::exigirQueNoSeaElDueno($project, $employee->user_id, 'eliminar');
        }

        $employee->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('success', 'Empleado eliminado.');
    }

    private function generateUsername(string $name): string
    {
        $base = Str::slug(Str::ascii(explode(' ', trim($name))[0]), '');
        $base = strtolower(preg_replace('/[^a-z0-9]/', '', $base)) ?: 'user';
        $username = $base;
        $i = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . $i++;
        }
        return $username;
    }

    private function row(Employee $e): array
    {
        $e->loadMissing('user');
        return [
            'id'          => $e->id,
            'name'        => $e->name,
            'role'        => $e->role ?? '',
            'spatie_role' => $e->spatie_role ?? '',
            'area'        => $e->area ?? '',
            'phone'       => $e->phone ?? '',
            'email'       => $e->email ?? '',
            'hire_date'   => $e->hire_date?->format('Y-m-d') ?? '',
            'is_active'   => (bool)$e->is_active,
            'has_user'    => (bool)$e->user_id,
            'username'    => $e->user?->username ?? '',
        ];
    }
}
