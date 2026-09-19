<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Crm\Models\WaCanal;
use App\Modules\Crm\Models\WaConversacion;
use App\Support\Productos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** El sondeo de la bandeja trae los mensajes nuevos sin recargar la pagina. */
class CrmBandejaPollTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_sondeo_devuelve_el_mensaje_que_llego_despues_del_ultimo_sondeo(): void
    {
        $usuario = User::factory()->create();
        $proyecto = Project::create(['owner_id' => $usuario->id, 'name' => 'CRM poll', 'slug' => 'crm-poll-' . uniqid(), 'is_active' => true]);
        Productos::activar($proyecto, 'crm');
        $canal = WaCanal::create(['project_id' => $proyecto->id, 'nombre' => 'L', 'tipo' => 'bixo', 'activo' => true, 'phone_number_id' => '1', 'access_token' => 'T']);
        $conv = WaConversacion::create(['wa_canal_id' => $canal->id, 'cliente_nombre' => 'Ana', 'cliente_telefono' => '51999', 'estado' => 'nuevo', 'no_leidos' => 0, 'ultimo_mensaje_at' => now(), 'bot_activo' => true]);
        $yo = fn () => $this->actingAs($usuario)->withSession(['comunicaciones_project_id' => $proyecto->id]);

        // Primer sondeo: se queda con la hora del servidor.
        $since = $yo()->getJson('/bixocrm/poll?conversacion_id=' . $conv->id)->assertOk()->json('server_time');

        // Llega un mensaje (como lo guarda el bot: con la hora de la app).
        $m = $conv->mensajes()->create(['direccion' => 'in', 'tipo' => 'texto', 'contenido' => 'hola', 'estado' => 'recibido']);
        $conv->update(['ultimo_mensaje_at' => now()]);

        $r = $yo()->getJson('/bixocrm/poll?conversacion_id=' . $conv->id . '&since=' . $since)->assertOk();
        $this->assertSame([$m->id], collect($r->json('mensajes_nuevos'))->pluck('id')->all(), 'El mensaje nuevo tiene que salir en el sondeo');
        $this->assertSame([$conv->id], collect($r->json('conversaciones_actualizadas'))->pluck('id')->all());
    }
}
