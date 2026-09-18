<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Crm\Models\WaCanal;
use App\Modules\Crm\Models\WaConversacion;
use App\Support\Productos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Ventana de 24 h de Meta y plantillas aprobadas: la bandeja sabe cuando el
 * texto libre ya no llega, lista las plantillas de la cuenta (WABA) y manda
 * una con sus parametros. Al guardar un canal con WABA, la app se suscribe sola.
 */
class CrmPlantillasMetaTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Project $proyecto;
    private WaCanal $canal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuario = User::factory()->create();
        $this->proyecto = Project::create(['owner_id' => $this->usuario->id, 'name' => 'CRM plantillas', 'slug' => 'crm-pl-' . uniqid(), 'is_active' => true]);
        Productos::activar($this->proyecto, 'crm');
        $this->canal = WaCanal::create([
            'project_id' => $this->proyecto->id, 'nombre' => 'Linea', 'tipo' => 'bixo', 'activo' => true,
            'phone_number_id' => '123', 'waba_id' => '999', 'access_token' => 'TOKEN', 'app_secret' => 'S', 'verify_token' => 'v',
        ]);
    }

    private function enElCrm()
    {
        return $this->actingAs($this->usuario)->withSession(['comunicaciones_project_id' => $this->proyecto->id]);
    }

    private function conversacion(): WaConversacion
    {
        return WaConversacion::create([
            'wa_canal_id' => $this->canal->id, 'cliente_nombre' => 'Rosa', 'cliente_telefono' => '51999111222',
            'estado' => 'nuevo', 'no_leidos' => 0, 'ultimo_mensaje_at' => now(), 'bot_activo' => false,
        ]);
    }

    public function test_la_ventana_esta_abierta_hasta_24_h_despues_del_ultimo_mensaje_del_cliente(): void
    {
        $conv = $this->conversacion();
        $conv->mensajes()->create(['direccion' => 'in', 'tipo' => 'texto', 'contenido' => 'hola', 'estado' => 'recibido', 'created_at' => now()->subHours(2)]);

        $r = $this->enElCrm()->getJson("/bixocrm/{$conv->id}/mensajes")->assertOk();
        $this->assertTrue($r->json('ventana.es_meta'));
        $this->assertTrue($r->json('ventana.abierta'));

        $conv->mensajes()->update(['created_at' => now()->subHours(25)]);
        $r = $this->enElCrm()->getJson("/bixocrm/{$conv->id}/mensajes")->assertOk();
        $this->assertFalse($r->json('ventana.abierta'), 'Pasadas 24 h del ultimo entrante, solo plantillas');

        // Un mensaje nuestro no reabre la ventana: la abre el cliente.
        $conv->mensajes()->create(['direccion' => 'saliente', 'tipo' => 'texto', 'contenido' => 'x', 'estado' => 'enviado']);
        $this->assertFalse($this->enElCrm()->getJson("/bixocrm/{$conv->id}/mensajes")->json('ventana.abierta'));
    }

    public function test_sin_mensajes_del_cliente_la_ventana_esta_cerrada_y_un_canal_qr_no_tiene_ventana(): void
    {
        $conv = $this->conversacion();
        $this->assertFalse($this->enElCrm()->getJson("/bixocrm/{$conv->id}/mensajes")->json('ventana.abierta'));

        $qr = WaCanal::create(['project_id' => $this->proyecto->id, 'nombre' => 'Bot', 'tipo' => 'bot', 'activo' => true]);
        $c2 = WaConversacion::create(['wa_canal_id' => $qr->id, 'cliente_nombre' => 'X', 'cliente_telefono' => '51999000000', 'estado' => 'nuevo', 'no_leidos' => 0, 'ultimo_mensaje_at' => now(), 'bot_activo' => false]);
        $r = $this->enElCrm()->getJson("/bixocrm/{$c2->id}/mensajes")->assertOk();
        $this->assertFalse($r->json('ventana.es_meta'));
        $this->assertTrue($r->json('ventana.abierta'));
    }

    public function test_lista_las_plantillas_aprobadas_de_la_cuenta_con_sus_parametros(): void
    {
        Http::fake(['graph.facebook.com/*/999/message_templates*' => Http::response(['data' => [
            ['name' => 'seguimiento_demo', 'language' => 'es', 'category' => 'MARKETING', 'components' => [
                ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Hola {{1}}'],
                ['type' => 'BODY', 'text' => 'Hola {{1}}, ¿pudiste ver la demo de {{2}}?'],
                ['type' => 'BUTTONS', 'buttons' => [['type' => 'QUICK_REPLY', 'text' => 'Sí, hablemos']]],
            ]],
            ['name' => 'gracias', 'language' => 'es', 'category' => 'UTILITY', 'components' => [['type' => 'BODY', 'text' => 'Gracias por escribirnos.']]],
        ]], 200)]);
        $conv = $this->conversacion();

        $r = $this->enElCrm()->getJson("/bixocrm/{$conv->id}/plantillas")->assertOk();

        $this->assertTrue($r->json('ok'));
        $this->assertCount(2, $r->json('plantillas'));
        $this->assertSame(2, $r->json('plantillas.0.parametros'));
        $this->assertSame('Sí, hablemos', $r->json('plantillas.0.botones.0'));
        $this->assertSame(0, $r->json('plantillas.1.parametros'));
        Http::assertSent(fn ($req) => str_contains($req->url(), '/999/message_templates') && $req->hasHeader('Authorization', 'Bearer TOKEN') && str_contains($req->url(), 'status=APPROVED'));
    }

    public function test_sin_waba_id_avisa_que_falta_en_vez_de_fallar_en_silencio(): void
    {
        $this->canal->update(['waba_id' => null]);
        $conv = $this->conversacion();

        $r = $this->enElCrm()->getJson("/bixocrm/{$conv->id}/plantillas")->assertStatus(422);
        $this->assertStringContainsString('WABA', $r->json('error'));
    }

    public function test_envia_la_plantilla_con_sus_parametros_y_la_deja_en_el_historial_ya_rellenada(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.pl.1']]], 200)]);
        $conv = $this->conversacion();

        $r = $this->enElCrm()->postJson("/bixocrm/{$conv->id}/plantilla", [
            'nombre' => 'seguimiento_demo', 'idioma' => 'es',
            'cuerpo' => 'Hola {{1}}, ¿pudiste ver la demo de {{2}}?', 'parametros' => ['Rosa', 'tu ferretería'],
        ])->assertOk();

        $this->assertTrue($r->json('ok'));
        Http::assertSent(function ($req) {
            $d = $req->data();
            return ($d['type'] ?? '') === 'template'
                && $d['template']['name'] === 'seguimiento_demo'
                && $d['template']['language']['code'] === 'es'
                && $d['template']['components'][0]['parameters'][1] === ['type' => 'text', 'text' => 'tu ferretería'];
        });
        $this->assertDatabaseHas('wa_mensajes', ['wa_conversacion_id' => $conv->id, 'direccion' => 'saliente', 'contenido' => '📋 Hola Rosa, ¿pudiste ver la demo de tu ferretería?', 'wa_message_id' => 'wamid.pl.1', 'estado' => 'enviado']);
    }

    public function test_si_meta_rechaza_la_plantilla_lo_dice_y_el_mensaje_queda_pendiente(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => '(#132001) Template name does not exist in the translation', 'code' => 132001]], 400)]);
        $conv = $this->conversacion();

        $r = $this->enElCrm()->postJson("/bixocrm/{$conv->id}/plantilla", ['nombre' => 'no_existe', 'idioma' => 'es', 'cuerpo' => 'x', 'parametros' => []]);

        $this->assertFalse($r->json('ok'));
        $this->assertStringContainsString('132001', $r->json('error'));
        $this->assertDatabaseHas('wa_mensajes', ['wa_conversacion_id' => $conv->id, 'estado' => 'pendiente']);
    }

    public function test_al_guardar_un_canal_con_waba_la_app_se_suscribe_sola(): void
    {
        Http::fake(['graph.facebook.com/*/555/subscribed_apps' => Http::response(['success' => true], 200)]);

        $r = $this->enElCrm()->postJson('/bixocrm/canales', [
            'nombre' => 'Nueva', 'tipo' => 'bixo', 'phone_number_id' => '321', 'waba_id' => '555',
            'access_token' => 'T2', 'app_secret' => 'S2', 'verify_token' => 'v2', 'color' => '#25d366',
        ])->assertOk();

        $this->assertTrue($r->json('suscripcion.ok'));
        Http::assertSent(fn ($req) => $req->method() === 'POST' && str_contains($req->url(), '/555/subscribed_apps') && $req->hasHeader('Authorization', 'Bearer T2'));
        $this->assertDatabaseHas('wa_canales', ['nombre' => 'Nueva', 'waba_id' => '555']);
    }

    public function test_la_conversacion_de_otro_negocio_no_expone_sus_plantillas(): void
    {
        $otro = Project::create(['owner_id' => User::factory()->create()->id, 'name' => 'Otro', 'slug' => 'otro-' . uniqid(), 'is_active' => true]);
        $canalAjeno = WaCanal::create(['project_id' => $otro->id, 'nombre' => 'L', 'tipo' => 'bixo', 'activo' => true, 'phone_number_id' => '1', 'waba_id' => '2', 'access_token' => 'x']);
        $ajena = WaConversacion::create(['wa_canal_id' => $canalAjeno->id, 'cliente_nombre' => 'X', 'cliente_telefono' => '51999000001', 'estado' => 'nuevo', 'no_leidos' => 0, 'ultimo_mensaje_at' => now(), 'bot_activo' => false]);

        $this->enElCrm()->getJson("/bixocrm/{$ajena->id}/plantillas")->assertForbidden();
        $this->enElCrm()->postJson("/bixocrm/{$ajena->id}/plantilla", ['nombre' => 'a', 'idioma' => 'es'])->assertForbidden();
    }
}
