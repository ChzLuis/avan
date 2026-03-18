<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Catálogo
            'view-catalog', 'create-products', 'edit-products', 'delete-products',
            // Pedidos
            'view-orders', 'manage-orders',
            // Cotizaciones
            'view-quotes', 'manage-quotes',
            // Agenda
            'view-agenda', 'manage-agenda',
            // Clientes
            'view-clients', 'manage-clients',
            // Logística
            'view-logistics', 'manage-logistics',
            // RRHH
            'view-hr', 'manage-hr',
            // Solicitudes
            'view-requests', 'manage-requests',
            // Configuración
            'manage-settings', 'manage-modules', 'manage-members',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Rol owner - todo
        Role::firstOrCreate(['name' => 'owner'])->givePermissionTo(Permission::all());

        // Rol admin - todo excepto billing
        Role::firstOrCreate(['name' => 'admin'])->givePermissionTo(Permission::all());

        // Rol comercial - catálogo, pedidos, cotizaciones, clientes
        Role::firstOrCreate(['name' => 'comercial'])->givePermissionTo([
            'view-catalog', 'create-products', 'edit-products',
            'view-orders', 'manage-orders',
            'view-quotes', 'manage-quotes',
            'view-clients', 'manage-clients',
            'view-agenda',
        ]);

        // Rol logística
        Role::firstOrCreate(['name' => 'logistica'])->givePermissionTo([
            'view-catalog', 'view-orders',
            'view-logistics', 'manage-logistics',
        ]);

        // Rol rrhh
        Role::firstOrCreate(['name' => 'rrhh'])->givePermissionTo([
            'view-hr', 'manage-hr',
            'view-requests', 'manage-requests',
        ]);

        // Rol operaciones - lectura general
        Role::firstOrCreate(['name' => 'operaciones'])->givePermissionTo([
            'view-catalog', 'view-orders', 'view-quotes',
            'view-agenda', 'view-clients',
            'view-requests',
        ]);
    }
}
