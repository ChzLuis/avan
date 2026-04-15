<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
        return view('hr.employees', compact('project', 'employees', 'departments', 'jobTitles', 'contractTypes'));
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
            'area'                  => 'nullable|string|max:80',
            'phone'                 => 'nullable|string|max:30',
            'email'                 => 'nullable|email|max:100',
            'hire_date'             => 'nullable|date',
            'is_active'             => 'boolean',
            'username'              => 'nullable|string|max:50|alpha_dash',
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
            // Buscar usuario existente por email o username
            $existingUser = null;
            if (!empty($data['email'])) {
                $existingUser = User::where('email', $data['email'])->first();
            }
            if (!$existingUser) {
                $existingUser = User::where('username', $username)->first();
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
            $existingUser = null;
            if (!empty($data['email'])) $existingUser = User::where('email', $data['email'])->first();
            if (!$existingUser) $existingUser = User::where('username', $username)->first();

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
        $employee->update($data);

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
            'id'        => $e->id,
            'name'      => $e->name,
            'role'      => $e->role ?? '',
            'area'      => $e->area ?? '',
            'phone'     => $e->phone ?? '',
            'email'     => $e->email ?? '',
            'hire_date' => $e->hire_date?->format('Y-m-d') ?? '',
            'is_active' => (bool)$e->is_active,
            'has_user'  => (bool)$e->user_id,
            'username'  => $e->user?->username ?? '',
        ];
    }
}
