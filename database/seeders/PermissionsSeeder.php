<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeder único y definitivo de permisos y roles.
 * Ejecutar: php artisan db:seed --class=PermissionsSeeder
 * Es idempotente — se puede ejecutar varias veces sin duplicar.
 */
class PermissionsSeeder extends Seeder
{
    // ── Permisos por módulo ──────────────────────────────────────────────────
    // Formato: 'modulo.accion'  →  se usan como ->middleware('can:orders.crear')
    //          o en Blade:  @can('orders.crear') / @canany([...])

    public const PERMISOS = [

        // Catálogo — productos, servicios, categorías
        'catalog.ver', 'catalog.crear', 'catalog.editar', 'catalog.eliminar', 'catalog.importar',

        // Pedidos y POS
        'orders.ver', 'orders.crear', 'orders.editar', 'orders.eliminar', 'orders.cancelar',
        'pos.usar',

        // Cotizaciones
        'quotes.ver', 'quotes.crear', 'quotes.editar', 'quotes.eliminar',

        // Facturas y boletas
        'invoices.ver', 'invoices.crear', 'invoices.editar', 'invoices.anular',

        // Clientes
        'clients.ver', 'clients.crear', 'clients.editar', 'clients.eliminar',

        // Agenda y citas
        'agenda.ver', 'agenda.crear', 'agenda.editar', 'agenda.eliminar',

        // Reportes
        'reports.ver', 'reports.exportar',

        // RRHH — empleados
        'hr.ver', 'hr.crear', 'hr.editar', 'hr.eliminar',

        // Asistencia
        'attendance.fichar', 'attendance.ver', 'attendance.editar',

        // Inventario
        'inventory.ver', 'inventory.editar',

        // Proveedores
        'proveedores.ver', 'proveedores.editar',

        // Rifas / Bot
        'rifas.ver', 'rifas.validar', 'rifas.cancelar',

        // Configuración del proyecto (general — solo owner/superadmin)
        'settings.ver', 'settings.editar',

        // Configuración limitada (asignable a empleados)
        'settings.negocio', 'settings.diseno', 'settings.pagos', 'settings.catalogos',

        // Roles y miembros del equipo
        'roles.ver', 'roles.gestionar',
    ];

    // ── Definición de roles y sus permisos ──────────────────────────────────

    public const ROLES = [

        /*
         * GERENTE
         * Acceso total excepto: eliminar ajustes críticos, gestionar roles
         * Ideal para: encargado general, jefe de tienda
         */
        'gerente' => [
            'catalog.ver', 'catalog.crear', 'catalog.editar', 'catalog.eliminar', 'catalog.importar',
            'orders.ver',  'orders.crear',  'orders.editar',  'orders.eliminar',  'orders.cancelar',
            'pos.usar',
            'quotes.ver',  'quotes.crear',  'quotes.editar',  'quotes.eliminar',
            'invoices.ver','invoices.crear','invoices.editar','invoices.anular',
            'clients.ver', 'clients.crear', 'clients.editar', 'clients.eliminar',
            'agenda.ver',  'agenda.crear',  'agenda.editar',  'agenda.eliminar',
            'reports.ver', 'reports.exportar',
            'hr.ver',      'hr.crear',      'hr.editar',
            'attendance.fichar', 'attendance.ver', 'attendance.editar',
            'inventory.ver', 'inventory.editar',
            'proveedores.ver', 'proveedores.editar',
            'rifas.ver',   'rifas.validar', 'rifas.cancelar',
            'settings.ver', 'settings.negocio', 'settings.diseno', 'settings.pagos', 'settings.catalogos',
            'roles.ver',
        ],

        /*
         * VENDEDOR
         * Enfocado en atención al cliente y ventas
         * Ideal para: cajero, vendedor de tienda, asesor comercial
         */
        'vendedor' => [
            'catalog.ver',
            'orders.ver',  'orders.crear',  'orders.editar',
            'pos.usar',
            'quotes.ver',  'quotes.crear',  'quotes.editar',
            'invoices.ver','invoices.crear',
            'clients.ver', 'clients.crear', 'clients.editar',
            'agenda.ver',  'agenda.crear',
            'reports.ver',
            'attendance.fichar',
        ],

        /*
         * ALMACEN
         * Gestión de productos e inventario
         * Ideal para: encargado de almacén, logística
         */
        'almacen' => [
            'catalog.ver',  'catalog.crear', 'catalog.editar', 'catalog.eliminar', 'catalog.importar',
            'inventory.ver','inventory.editar',
            'proveedores.ver', 'proveedores.editar',
            'orders.ver',
            'reports.ver',
        ],

        /*
         * RRHH
         * Gestión del equipo humano
         * Ideal para: jefe de personal, recursos humanos
         */
        'rrhh' => [
            'hr.ver',  'hr.crear', 'hr.editar', 'hr.eliminar',
            'attendance.fichar', 'attendance.ver', 'attendance.editar',
            'reports.ver',
        ],

        /*
         * CONTADOR
         * Solo facturación y reportes
         * Ideal para: contador externo, auditor
         */
        'contador' => [
            'invoices.ver', 'invoices.crear', 'invoices.editar', 'invoices.anular',
            'orders.ver',
            'quotes.ver',
            'clients.ver',
            'reports.ver',  'reports.exportar',
        ],

        /*
         * SOLO LECTURA
         * Sin acciones, solo visualización
         * Ideal para: supervisor externo, inversor
         */
        'solo_lectura' => [
            'catalog.ver',
            'orders.ver',
            'quotes.ver',
            'invoices.ver',
            'clients.ver',
            'agenda.ver',
            'reports.ver',
            'hr.ver',
            'attendance.ver',
            'inventory.ver',
            'proveedores.ver',
        ],
    ];

    public function run(): void
    {
        // Limpiar caché antes de ejecutar
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Crear todos los permisos
        foreach (self::PERMISOS as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $this->command->info('✓ ' . count(self::PERMISOS) . ' permisos registrados');

        // 2. Crear roles y asignar permisos
        foreach (self::ROLES as $nombreRol => $permisosRol) {
            $role = Role::firstOrCreate(['name' => $nombreRol, 'guard_name' => 'web']);
            $role->syncPermissions($permisosRol);
            $this->command->info("✓ Rol [{$nombreRol}] → " . count($permisosRol) . ' permisos');
        }

        $this->command->info('');
        $this->command->info('Total permisos en BD: ' . Permission::count());
        $this->command->info('Total roles en BD:    ' . Role::count());
    }
}
