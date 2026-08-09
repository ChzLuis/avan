<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Crea 10 usuarios de VENTAS (rol 'vendedor') en el proyecto ECCOMERCE POC.
 * Cada uno con User (login) + Employee (rol) + ProjectMember (acceso al proyecto).
 *
 * Uso:  php artisan db:seed --class=EcommerceVendedoresSeeder --force
 * Imprime al final el usuario y la contraseña de cada uno.
 *
 * Idempotente: si el email ya existe como empleado del proyecto, lo salta.
 */
class EcommerceVendedoresSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::where('slug', 'eccomerce-poc-mj6a')->first()
            ?? Project::where('slug', 'like', 'eccomerce%')->first()
            ?? Project::where('name', 'like', '%ECCOMERCE%')->first();

        if (!$project) { $this->command->error('No encontré ECCOMERCE POC.'); return; }
        $this->command->info("Proyecto: {$project->name} (id {$project->id})");

        // Nombres inventados
        $vendedores = [
            'Lucía Ramírez Soto', 'Carlos Fuentes Aguirre', 'María Delgado Ríos',
            'Jorge Salazar Ponce', 'Andrea Chávez Molina', 'Diego Vargas Núñez',
            'Paola Herrera Campos', 'Miguel Rojas Ibáñez', 'Valeria Torres Espinoza',
            'Renato Guzmán Cáceres',
        ];

        $credenciales = [];

        foreach ($vendedores as $i => $nombre) {
            // Email tipo nombre.apellido{n}@ecommercepoc.com
            $parts = explode(' ', Str::lower(Str::ascii($nombre)));
            $email = $parts[0] . '.' . ($parts[1] ?? 'v') . ($i + 1) . '@ecommercepoc.com';

            // Idempotente: si ya es empleado de este proyecto, saltar
            $exists = User::where('email', $email)->first();
            if ($exists && Employee::where('user_id', $exists->id)->where('project_id', $project->id)->exists()) {
                $this->command->warn("Ya existe: {$email} — saltado");
                continue;
            }

            // Usuario = primer nombre (si ya existe, se le añade un número)
            $username = $parts[0];
            $base = $username; $n = 2;
            while (User::where('username', $username)->exists()) { $username = $base . $n++; }

            // Contraseña común para pruebas
            $password = 'ABC123';

            $user = User::create([
                'name'              => $nombre,
                'username'          => $username,
                'email'             => $email,
                'password'          => Hash::make($password),
                'email_verified_at' => now(),
            ]);

            ProjectMember::firstOrCreate([
                'project_id' => $project->id,
                'user_id'    => $user->id,
            ]);

            Employee::create([
                'project_id'   => $project->id,
                'user_id'      => $user->id,
                'name'         => $nombre,
                'role'         => 'Vendedor',
                'spatie_role'  => 'vendedor',
                'area'         => 'Ventas',
                'email'        => $email,
                'is_active'    => true,
                'hire_date'    => now()->subDays(rand(10, 300)),
            ]);

            $credenciales[] = compact('nombre', 'username', 'email', 'password');
        }

        $this->command->info("\n=== VENDEDORES CREADOS (" . count($credenciales) . ") ===");
        foreach ($credenciales as $c) {
            $this->command->info("• {$c['nombre']}  |  email: {$c['email']}  |  usuario: {$c['username']}  |  clave: {$c['password']}");
        }
    }
}
