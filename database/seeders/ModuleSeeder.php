<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Module;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            ['name' => 'Dashboard',           'key' => 'dashboard',   'icon' => 'home',         'route' => 'dashboard',   'sort_order' => 0],
            ['name' => 'Catálogo',             'key' => 'catalog',     'icon' => 'shopping-bag', 'route' => 'catalog',     'sort_order' => 1],
            ['name' => 'Pedidos',              'key' => 'orders',      'icon' => 'clipboard',    'route' => 'orders',      'sort_order' => 2],
            ['name' => 'Cotizaciones',         'key' => 'quotes',      'icon' => 'document',     'route' => 'quotes',      'sort_order' => 3],
            ['name' => 'Agenda y Reservas',    'key' => 'agenda',      'icon' => 'calendar',     'route' => 'agenda',      'sort_order' => 4],
            ['name' => 'Clientes',             'key' => 'clients',     'icon' => 'users',        'route' => 'clients',     'sort_order' => 5],
            ['name' => 'Logística',            'key' => 'logistics',   'icon' => 'truck',        'route' => 'logistics',   'sort_order' => 6],
            ['name' => 'Recursos Humanos',     'key' => 'hr',          'icon' => 'briefcase',    'route' => 'hr',          'sort_order' => 7],
            ['name' => 'Solicitudes Internas', 'key' => 'internal',    'icon' => 'inbox',        'route' => 'requests',    'sort_order' => 8],
            ['name' => 'Solicitudes Externas', 'key' => 'external',    'icon' => 'mail',         'route' => 'external',    'sort_order' => 9],
            ['name' => 'Facturas y Boletas',   'key' => 'invoices',    'icon' => 'receipt',      'route' => 'invoices',    'sort_order' => 10],
            ['name' => 'Configuración',        'key' => 'settings',    'icon' => 'cog',          'route' => 'settings',    'sort_order' => 11],
        ];

        foreach ($modules as $module) {
            Module::updateOrCreate(['key' => $module['key']], array_merge($module, ['is_active' => true]));
        }
    }
}
