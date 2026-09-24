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
    /**
     * Regresion: el sondeo NO devolvia bot_activo y al mezclar en el navegador borraba el
     * apagado; el boton volvia a ON y el bot seguia respondiendo (paso con un cliente real).
     */
    public function test_el_sondeo_devuelve_el_bot_apagado_y_tambien_refresca_los_archivados(): void
    {
        $usuario = User::factory()->create();
        $proyecto = Project::create(['owner_id' => $usuario->id, 'name' => 'CRM bot off', 'slug' => 'crm-botoff-' . uniqid(), 'is_active' => true]);
        Productos::activar($proyecto, 'crm');
        $canal = WaCanal::create(['project_id' => $proyecto->id, 'nombre' => 'L', 'tipo' => 'bixo', 'activo' => true, 'phone_number_id' => '1', 'access_token' => 'T']);
        $conv = WaConversacion::create(['wa_canal_id' => $canal->id, 'cliente_nombre' => 'Ana', 'cliente_telefono' => '51999', 'estado' => 'nuevo', 'no_leidos' => 0, 'ultimo_mensaje_at' => now(), 'bot_activo' => true]);
        $archivada = WaConversacion::create(['wa_canal_id' => $canal->id, 'cliente_nombre' => 'Beto', 'cliente_telefono' => '51888', 'estado' => 'nuevo', 'no_leidos' => 0, 'ultimo_mensaje_at' => now(), 'bot_activo' => true, 'archivado' => true]);
        $yo = fn () => $this->actingAs($usuario)->withSession(['comunicaciones_project_id' => $proyecto->id]);

        // El asesor apaga el bot desde la bandeja.
        $yo()->patchJson("/bixocrm/chatbot/toggle/{$conv->id}")->assertOk()->assertJson(['ok' => true, 'bot_activo' => false]);
        $this->assertFalse((bool) $conv->fresh()->bot_activo);

        $since = now()->subMinute()->timestamp;
        $filas = collect($yo()->getJson('/bixocrm/poll?since=' . $since)->assertOk()->json('conversaciones_actualizadas'));

        $fila = $filas->firstWhere('id', $conv->id);
        $this->assertArrayHasKey('bot_activo', $fila, 'El sondeo debe traer bot_activo o el navegador lo borra');
        $this->assertFalse($fila['bot_activo'], 'El bot sigue apagado tras el sondeo');
        $this->assertNotNull($filas->firstWhere('id', $archivada->id), 'Los archivados tambien se refrescan');
    }

    /**
     * Las notas del cliente deben viajar en el sondeo.
     *
     * No lo hacian, pero el sondeo SI reemplaza la conversacion en la lista: al
     * reabrir el chat las notas y el sector volvian vacios y parecia que el boton
     * "Guardar notas" no funcionaba, aunque en la base si estaban guardadas.
     */
    public function test_el_sondeo_devuelve_las_notas_del_cliente(): void
    {
        $usuario = User::factory()->create();
        $proyecto = Project::create(['owner_id' => $usuario->id, 'name' => 'CRM notas', 'slug' => 'crm-notas-' . uniqid(), 'is_active' => true]);
        Productos::activar($proyecto, 'crm');
        $canal = WaCanal::create(['project_id' => $proyecto->id, 'nombre' => 'L', 'tipo' => 'bixo', 'activo' => true, 'phone_number_id' => '1', 'access_token' => 'T']);
        $conv = WaConversacion::create(['wa_canal_id' => $canal->id, 'cliente_nombre' => 'Alicia', 'cliente_telefono' => '51970', 'estado' => 'nuevo', 'no_leidos' => 0, 'ultimo_mensaje_at' => now(), 'bot_activo' => true]);
        $yo = fn () => $this->actingAs($usuario)->withSession(['comunicaciones_project_id' => $proyecto->id]);

        $yo()->patchJson("/bixocrm/{$conv->id}", [
            'notas'            => 'Busca celulares',
            'cliente_sector'   => 'Tecnologia',
            'cliente_distrito' => 'Los Olivos',
        ])->assertOk();

        $fila = collect($yo()->getJson('/bixocrm/poll?since=' . now()->subMinute()->timestamp)
            ->assertOk()->json('conversaciones_actualizadas'))->firstWhere('id', $conv->id);

        $this->assertSame('Busca celulares', $fila['notas'] ?? null, 'Sin esto el navegador borra las notas al sondear');
        $this->assertSame('Tecnologia', $fila['cliente_sector'] ?? null);
        $this->assertSame('Los Olivos', $fila['cliente_distrito'] ?? null);
    }
}
