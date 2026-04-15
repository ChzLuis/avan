<?php

namespace Database\Seeders;

use App\Models\BotFlow;
use App\Models\BotInstance;
use App\Models\BotState;
use App\Models\BotTransition;
use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Siembra el flujo conversacional del bot de rifas.
 * Estados:
 *   inicio → ver_rifas → seleccionar_rifa → elegir_cantidad
 *   → pedir_nombre → pedir_dni → pedir_ciudad
 *   → confirmar_pedido → esperando_pago → pago_recibido
 */
class RifaFlowSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::first();
        if (!$project) { $this->command->error('No hay proyectos.'); return; }

        $bot = BotInstance::where('bot_type', 'rifa')->first()
            ?? BotInstance::create([
                'project_id'  => $project->id,
                'bot_type'    => 'rifa',
                'name'        => 'Bot Rifa',
                'description' => 'Bot de rifas WhatsApp',
                'icon_color'  => 'purple',
                'port'        => 3002,
                'is_active'   => true,
            ]);

        $flow = BotFlow::firstOrCreate(
            ['project_id' => $project->id, 'bot_type' => 'rifa'],
            ['name' => 'Flujo Rifa', 'is_active' => true]
        );

        // Limpiar estados y transiciones anteriores
        BotTransition::whereIn('from_state_id', $flow->states->pluck('id'))->delete();
        $flow->states()->delete();

        $states = [
            'inicio' => [
                'label'      => 'Inicio / Menú',
                'message'    => "¡Hola! 👋 Bienvenido a *{negocio}*\n\n¿En qué te puedo ayudar?\n\n*1* — 🎟️ Ver rifas disponibles\n*2* — 📋 Consultar mi pedido\n\nEscribe el número de tu opción:",
                'input_type' => 'option',
                'sort_order' => 1,
            ],
            'ver_rifas' => [
                'label'      => 'Ver Rifas',
                'message'    => "🎯 Estas son nuestras rifas disponibles:",
                'input_type' => 'option',
                'sort_order' => 2,
            ],
            'seleccionar_rifa' => [
                'label'      => 'Seleccionar Rifa',
                'message'    => "✅ Escribe el *número* de la rifa que deseas:",
                'input_type' => 'option',
                'sort_order' => 3,
            ],
            'elegir_cantidad' => [
                'label'      => 'Elegir Cantidad',
                'message'    => "🎟️ *{rifa_nombre}*\nPrecio por ticket: S/ {rifa_precio}\n\n¿Cuántos tickets deseas comprar?\n_(Escribe solo el número, ej: 2)_",
                'input_type' => 'number',
                'sort_order' => 4,
            ],
            'pedir_nombre' => [
                'label'      => 'Pedir Nombre',
                'message'    => "👤 Por favor escribe tu *nombre completo*:",
                'input_type' => 'text',
                'sort_order' => 5,
            ],
            'pedir_dni' => [
                'label'      => 'Pedir DNI',
                'message'    => "🪪 Escribe tu *DNI o documento de identidad*:",
                'input_type' => 'text',
                'sort_order' => 6,
            ],
            'pedir_ciudad' => [
                'label'      => 'Pedir Ciudad',
                'message'    => "📍 ¿Desde qué *ciudad* participas?",
                'input_type' => 'text',
                'sort_order' => 7,
            ],
            'confirmar_pedido' => [
                'label'      => 'Confirmar Pedido',
                'message'    => "📋 *Resumen de tu pedido:*\n\n🎟️ Rifa: *{rifa_nombre}*\n🔢 Tickets: *{rifa_tickets}*\n💰 Total: *S/ {rifa_total}*\n📦 N° Pedido: *{order_number}*\n\n👤 Nombre: *{nombre}*\n🪪 DNI: *{dni}*\n\n*Realiza el pago:*\n🏦 Yape: 999-999-999\n🏦 BCP: 123-456789-0-12\n\nEnvía tu comprobante de pago para confirmar tu participación 📸",
                'input_type' => 'image',
                'sort_order' => 8,
            ],
            'esperando_pago' => [
                'label'      => 'Esperando Pago',
                'message'    => "⏳ *Comprobante recibido*\n\nEstamos verificando tu pago.\nTe notificaremos cuando sea confirmado. ✅\n\nN° Pedido: *{order_number}*",
                'input_type' => 'text',
                'sort_order' => 9,
            ],
            'pago_recibido' => [
                'label'      => 'Pago Confirmado',
                'message'    => "🎉 *¡Tu participación está confirmada!*\n\nPronto recibirás tu ticket con los números asignados. 🍀",
                'input_type' => 'text',
                'sort_order' => 10,
            ],
        ];

        $created = [];
        foreach ($states as $key => $data) {
            $created[$key] = BotState::create(array_merge($data, [
                'flow_id'   => $flow->id,
                'key'       => $key,
                'is_active' => true,
            ]));
        }

        // Transiciones
        $transitions = [
            // inicio → ver_rifas
            ['from' => 'inicio',           'to' => 'ver_rifas',        'trigger' => '1',       'action' => null],
            ['from' => 'inicio',           'to' => 'ver_rifas',        'trigger' => 'rifas',   'action' => null],
            ['from' => 'inicio',           'to' => 'ver_rifas',        'trigger' => '',        'action' => null],
            // ver_rifas → seleccionar_rifa (carga y muestra rifas)
            ['from' => 'ver_rifas',        'to' => 'seleccionar_rifa', 'trigger' => '',        'action' => 'show_rifas'],
            // seleccionar_rifa → elegir_cantidad
            ['from' => 'seleccionar_rifa', 'to' => 'elegir_cantidad',  'trigger' => '',        'action' => 'select_rifa'],
            // elegir_cantidad → pedir_nombre (guarda cantidad + crea orden)
            ['from' => 'elegir_cantidad',  'to' => 'pedir_nombre',     'trigger' => '',        'action' => 'save_quantity|create_rifa_order'],
            // recopilar datos
            ['from' => 'pedir_nombre',     'to' => 'pedir_dni',        'trigger' => '',        'action' => 'save_rifa_nombre'],
            ['from' => 'pedir_dni',        'to' => 'pedir_ciudad',     'trigger' => '',        'action' => 'save_rifa_dni'],
            ['from' => 'pedir_ciudad',     'to' => 'confirmar_pedido', 'trigger' => '',        'action' => 'save_rifa_ciudad'],
            // confirmar_pedido → esperando_pago (recibe comprobante imagen)
            ['from' => 'confirmar_pedido', 'to' => 'esperando_pago',   'trigger' => 'image',   'action' => 'save_rifa_payment'],
            ['from' => 'confirmar_pedido', 'to' => 'confirmar_pedido', 'trigger' => '',        'action' => null],
            // esperando_pago → loop
            ['from' => 'esperando_pago',   'to' => 'esperando_pago',   'trigger' => '',        'action' => null],
        ];

        foreach ($transitions as $t) {
            BotTransition::create([
                'from_state_id' => $created[$t['from']]->id,
                'to_state_id'   => $created[$t['to']]->id,
                'trigger'       => $t['trigger'],
                'action'        => $t['action'],
                'action_param'  => null,
            ]);
        }

        $this->command->info("✅ Flujo rifa creado: " . count($states) . " estados, " . count($transitions) . " transiciones.");
    }
}
