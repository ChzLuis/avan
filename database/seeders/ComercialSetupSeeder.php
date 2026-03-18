<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Project;
use App\Models\Module;
use App\Models\CatalogList;

class ComercialSetupSeeder extends Seeder
{
    /**
     * Activa módulos comerciales y crea catálogos por defecto
     * para TODOS los proyectos existentes (idempotente).
     */
    public function run(): void
    {
        // ── 1. Módulos que deben existir en la tabla modules ─────────────────
        $moduleDefs = [
            ['key' => 'catalog',  'name' => 'Catálogo / Productos', 'sort_order' => 1],
            ['key' => 'orders',   'name' => 'Pedidos',              'sort_order' => 2],
            ['key' => 'quotes',   'name' => 'Cotizaciones',         'sort_order' => 3],
            ['key' => 'clients',  'name' => 'Clientes',             'sort_order' => 4],
            ['key' => 'agenda',   'name' => 'Agenda',               'sort_order' => 5],
        ];

        foreach ($moduleDefs as $def) {
            Module::firstOrCreate(
                ['key' => $def['key']],
                ['name' => $def['name'], 'sort_order' => $def['sort_order'], 'is_active' => true]
            );
        }

        // ── 2. Para cada proyecto, activar módulos y crear catálogos ─────────
        $projects = Project::all();

        foreach ($projects as $project) {
            $this->activateModules($project);
            $this->seedCatalogs($project);
        }

        $this->command->info('✓ Módulos comerciales activados y catálogos creados.');
    }

    private function activateModules(Project $project): void
    {
        $commercialModules = ['catalog', 'orders', 'quotes', 'clients'];

        foreach ($commercialModules as $key) {
            $module = Module::where('key', $key)->first();
            if (!$module) continue;

            $exists = DB::table('project_modules')
                ->where('project_id', $project->id)
                ->where('module_id', $module->id)
                ->exists();

            if ($exists) {
                DB::table('project_modules')
                    ->where('project_id', $project->id)
                    ->where('module_id', $module->id)
                    ->update(['is_active' => true, 'updated_at' => now()]);
            } else {
                DB::table('project_modules')->insert([
                    'project_id' => $project->id,
                    'module_id'  => $module->id,
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedCatalogs(Project $project): void
    {
        $catalogs = [
            [
                'type'        => 'payment_method',
                'name'        => 'Método de pago',
                'description' => 'Formas de pago aceptadas',
                'color'       => '#4f46e5',
                'values'      => ['Efectivo', 'Transferencia bancaria', 'Yape', 'Plin', 'Tarjeta de crédito', 'Tarjeta de débito'],
            ],
            [
                'type'        => 'payment_condition',
                'name'        => 'Condición de pago',
                'description' => 'Términos de pago',
                'color'       => '#0891b2',
                'values'      => ['Contado', 'Crédito 15 días', 'Crédito 30 días', 'Crédito 60 días', '50% adelanto'],
            ],
            [
                'type'        => 'sales_channel',
                'name'        => 'Canal de venta',
                'description' => 'Origen del pedido',
                'color'       => '#059669',
                'values'      => ['Tienda online', 'WhatsApp', 'Llamada telefónica', 'Presencial', 'Redes sociales', 'Referido'],
            ],
            [
                'type'        => 'client_type',
                'name'        => 'Tipo de cliente',
                'description' => 'Clasificación del cliente',
                'color'       => '#d97706',
                'values'      => ['Persona natural', 'Empresa', 'Mayorista', 'Minorista', 'Gobierno'],
            ],
            [
                'type'        => 'lead_source',
                'name'        => 'Fuente de captación',
                'description' => 'Cómo llegó el cliente',
                'color'       => '#7c3aed',
                'values'      => ['Google', 'Facebook / Instagram', 'WhatsApp', 'Referido', 'Visita directa', 'Feria / Evento'],
            ],
        ];

        foreach ($catalogs as $catDef) {
            // Crear lista si no existe
            $list = CatalogList::firstOrCreate(
                ['project_id' => $project->id, 'type' => $catDef['type']],
                [
                    'name'        => $catDef['name'],
                    'description' => $catDef['description'],
                    'color'       => $catDef['color'],
                    'is_active'   => true,
                    'is_system'   => true,
                    'sort_order'  => 0,
                ]
            );

            // Crear valores solo si la lista es nueva (no duplicar)
            if ($list->wasRecentlyCreated) {
                foreach ($catDef['values'] as $i => $label) {
                    $list->values()->create([
                        'label'      => $label,
                        'value'      => $label,
                        'sort_order' => $i,
                        'is_active'  => true,
                        'is_system'  => false,
                    ]);
                }
            }
        }
    }
}
