<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use App\Models\Project;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name'              => 'Administrator',
                'email'             => null,
                'password'          => Hash::make('ABC123'),
                'email_verified_at' => now(),
                'is_superadmin'     => true,
            ]
        );

        // Agregar superadmin como miembro del equipo en todos los proyectos activos
        Project::where('is_active', true)->each(function ($project) use ($admin) {
            Employee::firstOrCreate(
                ['project_id' => $project->id, 'user_id' => $admin->id],
                [
                    'name'      => $admin->name,
                    'email'     => $admin->email,
                    'role'      => 'Administrador',
                    'is_active' => true,
                    'hire_date' => now(),
                ]
            );
        });
    }
}
