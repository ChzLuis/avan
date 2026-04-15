<?php

namespace Database\Seeders;

use App\Models\WaRespuestaRapida;
use App\Models\Project;
use Illuminate\Database\Seeder;

class WaRespuestasRapidasSeeder extends Seeder
{
    public static function seedForProject(int $projectId): void
    {
        $respuestas = [
            ['canal'=>'bixo',    'nombre'=>'Bienvenida BIXO',       'orden'=>1,  'texto'=>"¡Hola! 👋 Gracias por escribirnos.\nSomos AVAN — digitalizamos negocios peruanos con catálogo digital, pagos con Yape y facturación electrónica SUNAT.\nPara atenderte mejor, cuéntame:\n1️⃣ ¿Qué tipo de negocio tienes?\n2️⃣ ¿En qué distrito estás?\nEn menos de 5 minutos te muestro cómo quedaría tu negocio digital. 🚀"],
            ['canal'=>'bixo',    'nombre'=>'Envío de demo',          'orden'=>2,  'texto'=>"Aquí te comparto la demo de cómo quedaría tu negocio con BIXO:\n👉 [LINK_DEMO]\nEn este link puedes ver el catálogo, los productos, y cómo funciona el pago con Yape.\n¿Qué te parece? ¿Lo activamos para tu negocio?"],
            ['canal'=>'bixo',    'nombre'=>'Cierre de venta',        'orden'=>3,  'texto'=>"Perfecto [nombre] 🎉\nPara activar BIXO para tu negocio el proceso es simple:\n1️⃣ Haces el pago de activación por Yape/Plin\n2️⃣ Me mandas el voucher\n3️⃣ En 24 horas tienes tu sistema activo\n¿Lo activamos hoy?"],
            ['canal'=>'academy', 'nombre'=>'Bienvenida Academia',    'orden'=>4,  'texto'=>"¡Hola! 👋 Gracias por escribir a AVAN Academy.\nNuestro próximo taller es:\n📅 Sábado [FECHA]\n⏰ 3:00 PM – 6:00 PM\n💻 Virtual por Zoom\n👥 Solo 15 cupos disponibles\n💰 S/ 80 con certificado\n¿Quieres reservar tu lugar?"],
            ['canal'=>'academy', 'nombre'=>'Confirmación de pago',   'orden'=>5,  'texto'=>"¡Perfecto [nombre]! Recibí tu pago ✅\nTu lugar está confirmado para el sábado [FECHA].\n📅 Sábado [FECHA]\n⏰ 3:00 PM — Link de Zoom: [LINK]\nEl día del taller te mando un recordatorio 1 hora antes. ¡Nos vemos!"],
            ['canal'=>'academy', 'nombre'=>'Recordatorio 24h antes', 'orden'=>6,  'texto'=>"Hola [nombre] 👋 Mañana es el taller.\n📅 Mañana sábado [FECHA] · 3:00 PM\n🔗 Zoom: [LINK]\nPrepara: tu celular con internet, laptop si tienes, y la lista de tus productos con precios.\n¡Nos vemos mañana!"],
            ['canal'=>'academy', 'nombre'=>'Recordatorio 1h antes',  'orden'=>7,  'texto'=>"¡Hola [nombre]! En 1 hora empieza el taller 🚀\n📍 Zoom: [LINK]\n⏰ 3:00 PM en punto\n¡Te espero!"],
            ['canal'=>'academy', 'nombre'=>'Seguimiento post-taller','orden'=>8,  'texto'=>"Hola [nombre], ayer fue increíble el taller.\nVi que creaste tu catálogo en vivo — quedó muy bien para tu negocio.\nLas cuentas de prueba están activas hasta el [FECHA].\nSi quieres mantenerlo activo permanentemente, el precio especial de clase lo mantengo hasta este viernes.\n¿Lo activamos?"],
        ];

        foreach ($respuestas as $r) {
            WaRespuestaRapida::firstOrCreate(
                ['project_id' => $projectId, 'nombre' => $r['nombre']],
                array_merge($r, ['project_id' => $projectId])
            );
        }
    }

    public function run(): void
    {
        Project::all()->each(fn($p) => self::seedForProject($p->id));
    }
}
