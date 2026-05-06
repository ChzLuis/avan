<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            // ── Plataformas ──────────────────────────────
            ['key' => 'store',       'name' => 'Tienda online',           'category' => 'plataformas', 'sort_order' => 1],
            ['key' => 'orders',      'name' => 'Panel de operaciones',    'category' => 'plataformas', 'sort_order' => 2],
            ['key' => 'docs',        'name' => 'Portal de clientes',      'category' => 'plataformas', 'sort_order' => 3],
            ['key' => 'pages',       'name' => 'Página web',              'category' => 'plataformas', 'sort_order' => 4],

            // ── Ventas ───────────────────────────────────
            ['key' => 'catalog',     'name' => 'Productos y categorías',  'category' => 'ventas',      'sort_order' => 10],
            ['key' => 'quotes',      'name' => 'Cotizaciones',            'category' => 'ventas',      'sort_order' => 11],
            ['key' => 'invoices',    'name' => 'Facturas y boletas',      'category' => 'ventas',      'sort_order' => 12],

            // ── Clientes ─────────────────────────────────
            ['key' => 'clients',     'name' => 'Base de clientes',        'category' => 'clientes',    'sort_order' => 20],
            ['key' => 'agenda',      'name' => 'Agenda y citas',          'category' => 'clientes',    'sort_order' => 21],
            ['key' => 'groups',      'name' => 'Grupos de clientes',      'category' => 'clientes',    'sort_order' => 22],
            ['key' => 'loyalty',     'name' => 'Fidelización y puntos',   'category' => 'clientes',    'sort_order' => 23],

            // ── Empresa ──────────────────────────────────
            ['key' => 'hr',          'name' => 'Empleados',               'category' => 'empresa',     'sort_order' => 30],
            ['key' => 'sedes',       'name' => 'Sedes y sucursales',      'category' => 'empresa',     'sort_order' => 31],
            ['key' => 'payroll',     'name' => 'Planilla y nómina',       'category' => 'empresa',     'sort_order' => 32],
            ['key' => 'attendance',  'name' => 'Asistencia y turnos',     'category' => 'empresa',     'sort_order' => 33],

            // ── Logística ────────────────────────────────
            ['key' => 'inventory',   'name' => 'Inventario',              'category' => 'logistica',   'sort_order' => 40],
            ['key' => 'proveedores', 'name' => 'Proveedores',             'category' => 'logistica',   'sort_order' => 41],
            ['key' => 'logistics',   'name' => 'Despacho y envíos',       'category' => 'logistica',   'sort_order' => 42],
        ];

        foreach ($modules as $data) {
            Module::updateOrCreate(
                ['key' => $data['key']],
                array_merge($data, ['is_active' => true, 'icon' => '', 'route' => $data['key']])
            );
        }
    }
}
