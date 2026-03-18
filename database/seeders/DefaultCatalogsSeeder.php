<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\CatalogList;
use Illuminate\Database\Seeder;

class DefaultCatalogsSeeder extends Seeder
{
    /**
     * Inserta catálogos por defecto en todos los proyectos que aún no los tengan.
     * También se puede llamar con un project_id específico.
     */
    public function run(): void
    {
        $projects = Project::all();

        foreach ($projects as $project) {
            self::seedForProject($project);
        }

        $this->command->info('Catálogos por defecto creados correctamente.');
    }

    /**
     * Crea los catálogos predeterminados para un proyecto dado.
     * Evita duplicados comparando por el campo `type` (key único por proyecto).
     */
    public static function seedForProject(Project $project): void
    {
        $defaults = self::defaultCatalogs();

        $existingTypes = $project->catalogLists()->pluck('type')->filter()->toArray();

        foreach ($defaults as $order => $catalog) {
            if (in_array($catalog['type'], $existingTypes)) {
                // Marcar como sistema si ya existe pero no tiene la flag
                $project->catalogLists()
                    ->where('type', $catalog['type'])
                    ->where('is_system', false)
                    ->update(['is_system' => true]);
                continue;
            }

            $list = $project->catalogLists()->create([
                'name'        => $catalog['name'],
                'type'        => $catalog['type'],
                'description' => $catalog['description'],
                'color'       => $catalog['color'],
                'is_active'   => true,
                'is_system'   => true,
                'sort_order'  => $order + 1,
            ]);

            foreach ($catalog['values'] as $vOrder => $val) {
                $list->values()->create([
                    'label'      => $val['label'],
                    'code'       => $val['code'] ?? null,
                    'is_active'  => true,
                    'sort_order' => $vOrder + 1,
                ]);
            }
        }
    }

    public static function defaultCatalogs(): array
    {
        return [
            // ── Comercial ─────────────────────────────────────────────────────
            [
                'name'        => 'Categorías de productos',
                'type'        => 'product_category',
                'description' => 'Clasificación de productos y servicios del catálogo.',
                'color'       => '#6366f1',
                'values'      => [
                    ['label' => 'General',      'code' => 'general'],
                    ['label' => 'Electrónica',  'code' => 'electronica'],
                    ['label' => 'Ropa',         'code' => 'ropa'],
                    ['label' => 'Alimentos',    'code' => 'alimentos'],
                    ['label' => 'Servicios',    'code' => 'servicios'],
                ],
            ],
            [
                'name'        => 'Marcas',
                'type'        => 'brand',
                'description' => 'Marcas o fabricantes de los productos.',
                'color'       => '#8b5cf6',
                'values'      => [
                    ['label' => 'Sin marca',   'code' => 'sin_marca'],
                    ['label' => 'Genérico',    'code' => 'generico'],
                ],
            ],
            [
                'name'        => 'Unidades de medida',
                'type'        => 'unit',
                'description' => 'Unidades para cuantificar productos (kg, und, m², etc.).',
                'color'       => '#0ea5e9',
                'values'      => [
                    ['label' => 'Unidad',      'code' => 'und'],
                    ['label' => 'Kilogramo',   'code' => 'kg'],
                    ['label' => 'Gramo',       'code' => 'g'],
                    ['label' => 'Litro',       'code' => 'lt'],
                    ['label' => 'Metro',       'code' => 'm'],
                    ['label' => 'Metro cuadrado', 'code' => 'm2'],
                    ['label' => 'Caja',        'code' => 'caja'],
                    ['label' => 'Pack',        'code' => 'pack'],
                    ['label' => 'Docena',      'code' => 'doc'],
                    ['label' => 'Par',         'code' => 'par'],
                ],
            ],
            [
                'name'        => 'Condiciones de pago',
                'type'        => 'payment_condition',
                'description' => 'Términos de pago para pedidos y cotizaciones.',
                'color'       => '#10b981',
                'values'      => [
                    ['label' => 'Contado',          'code' => 'contado'],
                    ['label' => 'Crédito 15 días',  'code' => 'credito_15'],
                    ['label' => 'Crédito 30 días',  'code' => 'credito_30'],
                    ['label' => 'Crédito 45 días',  'code' => 'credito_45'],
                    ['label' => 'Crédito 60 días',  'code' => 'credito_60'],
                    ['label' => 'Contra entrega',   'code' => 'contra_entrega'],
                ],
            ],
            [
                'name'        => 'Métodos de pago',
                'type'        => 'payment_method',
                'description' => 'Formas de pago aceptadas.',
                'color'       => '#f59e0b',
                'values'      => [
                    ['label' => 'Efectivo',            'code' => 'efectivo'],
                    ['label' => 'Transferencia',       'code' => 'transferencia'],
                    ['label' => 'Tarjeta de crédito',  'code' => 'tarjeta_credito'],
                    ['label' => 'Tarjeta de débito',   'code' => 'tarjeta_debito'],
                    ['label' => 'Yape',                'code' => 'yape'],
                    ['label' => 'Plin',                'code' => 'plin'],
                    ['label' => 'Cheque',              'code' => 'cheque'],
                ],
            ],
            [
                'name'        => 'Canales de venta',
                'type'        => 'sales_channel',
                'description' => 'De dónde provienen los pedidos.',
                'color'       => '#f97316',
                'values'      => [
                    ['label' => 'Tienda física',  'code' => 'tienda'],
                    ['label' => 'WhatsApp',       'code' => 'whatsapp'],
                    ['label' => 'Web',            'code' => 'web'],
                    ['label' => 'Instagram',      'code' => 'instagram'],
                    ['label' => 'Facebook',       'code' => 'facebook'],
                    ['label' => 'Teléfono',       'code' => 'telefono'],
                    ['label' => 'Email',          'code' => 'email'],
                    ['label' => 'Referido',       'code' => 'referido'],
                ],
            ],
            // ── Clientes / CRM ────────────────────────────────────────────────
            [
                'name'        => 'Tipos de cliente',
                'type'        => 'client_type',
                'description' => 'Clasificación de clientes (persona natural, empresa, etc.).',
                'color'       => '#06b6d4',
                'values'      => [
                    ['label' => 'Persona natural', 'code' => 'persona'],
                    ['label' => 'Empresa',         'code' => 'empresa'],
                    ['label' => 'Mayorista',       'code' => 'mayorista'],
                    ['label' => 'VIP',             'code' => 'vip'],
                ],
            ],
            [
                'name'        => 'Orígenes de lead',
                'type'        => 'lead_source',
                'description' => 'Cómo conocieron el negocio los clientes.',
                'color'       => '#a855f7',
                'values'      => [
                    ['label' => 'Referido',         'code' => 'referido'],
                    ['label' => 'Redes sociales',   'code' => 'redes'],
                    ['label' => 'Google',           'code' => 'google'],
                    ['label' => 'Página web',       'code' => 'web'],
                    ['label' => 'Evento',           'code' => 'evento'],
                    ['label' => 'Publicidad',       'code' => 'publicidad'],
                ],
            ],
            // ── RRHH ──────────────────────────────────────────────────────────
            [
                'name'        => 'Áreas / Departamentos',
                'type'        => 'department',
                'description' => 'Áreas organizacionales de la empresa.',
                'color'       => '#ec4899',
                'values'      => [
                    ['label' => 'Administración',  'code' => 'admin'],
                    ['label' => 'Ventas',          'code' => 'ventas'],
                    ['label' => 'Operaciones',     'code' => 'operaciones'],
                    ['label' => 'Logística',       'code' => 'logistica'],
                    ['label' => 'Marketing',       'code' => 'marketing'],
                    ['label' => 'Tecnología',      'code' => 'tecnologia'],
                    ['label' => 'Finanzas',        'code' => 'finanzas'],
                ],
            ],
            [
                'name'        => 'Cargos / Puestos',
                'type'        => 'job_title',
                'description' => 'Roles o puestos de trabajo del personal.',
                'color'       => '#f43f5e',
                'values'      => [
                    ['label' => 'Gerente General',    'code' => 'gerente_general'],
                    ['label' => 'Supervisor',         'code' => 'supervisor'],
                    ['label' => 'Vendedor',           'code' => 'vendedor'],
                    ['label' => 'Almacenero',         'code' => 'almacenero'],
                    ['label' => 'Repartidor',         'code' => 'repartidor'],
                    ['label' => 'Contador',           'code' => 'contador'],
                    ['label' => 'Asistente',          'code' => 'asistente'],
                ],
            ],
            [
                'name'        => 'Tipos de contrato',
                'type'        => 'contract_type',
                'description' => 'Modalidades de contratación del personal.',
                'color'       => '#64748b',
                'values'      => [
                    ['label' => 'Planilla',           'code' => 'planilla'],
                    ['label' => 'Locación de servicios', 'code' => 'locacion'],
                    ['label' => 'Practicante',        'code' => 'practicante'],
                    ['label' => 'Por obra',           'code' => 'por_obra'],
                    ['label' => 'Tiempo completo',    'code' => 'full_time'],
                    ['label' => 'Tiempo parcial',     'code' => 'part_time'],
                ],
            ],
            // ── Agenda / Citas ────────────────────────────────────────────────
            [
                'name'        => 'Tipos de cita',
                'type'        => 'appointment_type',
                'description' => 'Categorías de citas o servicios en la agenda.',
                'color'       => '#14b8a6',
                'values'      => [
                    ['label' => 'Consulta',        'code' => 'consulta'],
                    ['label' => 'Seguimiento',     'code' => 'seguimiento'],
                    ['label' => 'Instalación',     'code' => 'instalacion'],
                    ['label' => 'Mantenimiento',   'code' => 'mantenimiento'],
                    ['label' => 'Visita comercial','code' => 'visita'],
                ],
            ],
            // ── General ───────────────────────────────────────────────────────
            [
                'name'        => 'Prioridades',
                'type'        => 'priority',
                'description' => 'Niveles de urgencia o importancia.',
                'color'       => '#ef4444',
                'values'      => [
                    ['label' => 'Baja',    'code' => 'baja'],
                    ['label' => 'Media',   'code' => 'media'],
                    ['label' => 'Alta',    'code' => 'alta'],
                    ['label' => 'Urgente', 'code' => 'urgente'],
                ],
            ],
            [
                'name'        => 'Estados generales',
                'type'        => 'general_status',
                'description' => 'Estados reutilizables para cualquier entidad.',
                'color'       => '#84cc16',
                'values'      => [
                    ['label' => 'Pendiente',   'code' => 'pendiente'],
                    ['label' => 'En proceso',  'code' => 'en_proceso'],
                    ['label' => 'Completado',  'code' => 'completado'],
                    ['label' => 'Cancelado',   'code' => 'cancelado'],
                    ['label' => 'En pausa',    'code' => 'en_pausa'],
                ],
            ],
        ];
    }
}
