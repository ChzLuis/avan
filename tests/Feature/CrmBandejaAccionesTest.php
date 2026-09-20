<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Crm\Models\WaCanal;
use App\Modules\Crm\Models\WaConversacion;
use App\Modules\Crm\Models\WaMensaje;
use App\Support\Productos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Acciones de chat estilo WhatsApp en la bandeja: fijar, archivar, marcar
 * como no leida, eliminar chat y eliminar mensaje. Todas dentro del negocio
 * de la sesion; nunca sobre conversaciones de otro.
 */
class CrmBandejaAccionesTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Project $proyecto;
    private WaCanal $canal;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->usuario = User::factory()->create();
        $this->proyecto = Project::create(['owner_id' => $this->usuario->id, 'name' => 'CRM acciones', 'slug' => 'crm-acc-' . uniqid(), 'is_active' => true]);
        Productos::activar($this->proyecto, 'crm');
        $this->canal = WaCanal::create(['project_id' => $this->proyecto->id, 'nombre' => 'Linea', 'tipo' => 'bixo', 'activo' => true, 'phone_number_id' => '1', 'access_token' => 'T']);
    }

    private function conv(): WaConversacion
    {
        return WaConversacion::create(['wa_canal_id' => $this->canal->id, 'cliente_nombre' => 'Ana', 'cliente_telefono' => '51999', 'estado' => 'nuevo', 'no_leidos' => 0, 'ultimo_mensaje_at' => now(), 'bot_activo' => false]);
    }

    private function enElCrm()
    {
        return $this->actingAs($this->usuario)->withSession(['comunicaciones_project_id' => $this->proyecto->id]);
    }

    public function test_fijar_archivar_y_marcar_no_leida(): void
    {
        $c = $this->conv();

        $this->enElCrm()->patchJson("/bixocrm/{$c->id}", ['fijada' => true])->assertOk();
        $this->assertTrue($c->fresh()->fijada);

        $this->enElCrm()->patchJson("/bixocrm/{$c->id}", ['no_leidos' => 1])->assertOk();
        $this->assertSame(1, $c->fresh()->no_leidos);

        $this->enElCrm()->patchJson("/bixocrm/{$c->id}", ['archivado' => true])->assertOk();
        $this->assertTrue($c->fresh()->archivado);
        $this->enElCrm()->patchJson("/bixocrm/{$c->id}", ['archivado' => false])->assertOk();
        $this->assertFalse($c->fresh()->archivado);
    }

    public function test_la_lista_incluye_archivadas_y_fijadas_para_que_la_bandeja_las_muestre_en_su_pestana(): void
    {
        $c = $this->conv();
        $c->update(['archivado' => true, 'fijada' => true]);

        $res = $this->enElCrm()->get('/bixocrm')->assertOk();
        $lista = $res->viewData('conversacionesJs');

        $this->assertTrue($lista->firstWhere('id', $c->id)['archivado']);
        $this->assertTrue($lista->firstWhere('id', $c->id)['fijada']);
    }

    public function test_eliminar_chat_borra_mensajes_y_adjuntos_del_negocio(): void
    {
        $c = $this->conv();
        Storage::disk('public')->put('wa/' . $this->proyecto->id . '/foto.jpg', 'x');
        $c->mensajes()->create(['direccion' => 'entrante', 'tipo' => 'imagen', 'contenido' => 'foto', 'media_url' => 'http://localhost/storage/wa/' . $this->proyecto->id . '/foto.jpg', 'estado' => 'recibido']);
        $c->mensajes()->create(['direccion' => 'entrante', 'tipo' => 'texto', 'contenido' => 'hola', 'estado' => 'recibido']);

        $this->enElCrm()->deleteJson("/bixocrm/{$c->id}")->assertOk();

        $this->assertDatabaseMissing('wa_conversaciones', ['id' => $c->id]);
        $this->assertSame(0, WaMensaje::count());
        Storage::disk('public')->assertMissing('wa/' . $this->proyecto->id . '/foto.jpg');
    }

    public function test_eliminar_un_mensaje_solo_quita_ese(): void
    {
        $c = $this->conv();
        $a = $c->mensajes()->create(['direccion' => 'entrante', 'tipo' => 'texto', 'contenido' => 'a', 'estado' => 'recibido']);
        $b = $c->mensajes()->create(['direccion' => 'saliente', 'tipo' => 'texto', 'contenido' => 'b', 'estado' => 'enviado']);

        $this->enElCrm()->deleteJson("/bixocrm/{$c->id}/mensajes/{$a->id}")->assertOk();

        $this->assertDatabaseMissing('wa_mensajes', ['id' => $a->id]);
        $this->assertDatabaseHas('wa_mensajes', ['id' => $b->id]);
    }

    public function test_nada_de_esto_sobre_conversaciones_de_otro_negocio(): void
    {
        $otro = Project::create(['owner_id' => User::factory()->create()->id, 'name' => 'Otro', 'slug' => 'otro-' . uniqid(), 'is_active' => true]);
        $canalOtro = WaCanal::create(['project_id' => $otro->id, 'nombre' => 'L', 'tipo' => 'bixo', 'activo' => true, 'phone_number_id' => '9', 'access_token' => 'T']);
        $ajena = WaConversacion::create(['wa_canal_id' => $canalOtro->id, 'cliente_nombre' => 'X', 'cliente_telefono' => '51900', 'estado' => 'nuevo', 'no_leidos' => 0, 'ultimo_mensaje_at' => now(), 'bot_activo' => false]);
        $m = $ajena->mensajes()->create(['direccion' => 'entrante', 'tipo' => 'texto', 'contenido' => 'x', 'estado' => 'recibido']);

        $this->enElCrm()->patchJson("/bixocrm/{$ajena->id}", ['fijada' => true])->assertForbidden();
        $this->enElCrm()->deleteJson("/bixocrm/{$ajena->id}")->assertForbidden();
        $this->enElCrm()->deleteJson("/bixocrm/{$ajena->id}/mensajes/{$m->id}")->assertForbidden();
        $this->assertDatabaseHas('wa_conversaciones', ['id' => $ajena->id]);
    }
    /** Regresion: la relacion mensajes() ordena ASC por su cuenta; el limit(100)+reverse() salia de cabeza. */
    public function test_los_mensajes_salen_en_orden_cronologico_aunque_haya_mas_de_cien(): void
    {
        $conv = $this->conv();
        for ($i = 1; $i <= 105; $i++) {
            $conv->mensajes()->create(['direccion' => $i % 2 ? 'in' : 'out', 'tipo' => 'texto', 'contenido' => "m{$i}", 'estado' => 'enviado', 'created_at' => now()->subMinutes(200 - $i)]);
        }
        // Tres en el mismo segundo: el id desempata.
        $t = now();
        foreach (['a', 'b', 'c'] as $x) {
            $conv->mensajes()->create(['direccion' => 'out', 'tipo' => 'texto', 'contenido' => "mismo-{$x}", 'estado' => 'enviado', 'created_at' => $t]);
        }

        $ids = collect($this->enElCrm()->getJson("/bixocrm/{$conv->id}/mensajes")->assertOk()->json('mensajes'));

        $this->assertCount(100, $ids, 'Se pintan los 100 mas recientes');
        $this->assertSame('mismo-c', $ids->last()['contenido'], 'El ultimo de la pantalla es el mas nuevo');
        $this->assertSame($ids->pluck('id')->sort()->values()->all(), $ids->pluck('id')->all(), 'Cronologico de arriba a abajo');
        $this->assertSame('m9', $ids->first()['contenido'], 'Los mas viejos (m1..m8) quedan fuera, no los nuevos');
    }

    public function test_probar_conexion_usa_lo_guardado_y_limpia_el_ultimo_error_si_meta_responde(): void
    {
        \Illuminate\Support\Facades\Http::fake(['graph.facebook.com/*' => \Illuminate\Support\Facades\Http::response(['display_phone_number' => '+1 555', 'verified_name' => 'Eskala'], 200)]);
        $this->canal->forceFill(['ultimo_error' => 'Authentication Error'])->save();

        $r = $this->enElCrm()->postJson("/bixocrm/canales/{$this->canal->id}/probar", [])->assertOk();

        $this->assertTrue($r->json('ok'));
        $this->assertSame('+1 555', $r->json('numero'));
        $this->assertNull($this->canal->fresh()->ultimo_error);
        \Illuminate\Support\Facades\Http::assertSent(fn ($req) => $req->hasHeader('Authorization', 'Bearer T'));

    }

    public function test_probar_con_un_token_escrito_prueba_ese_token_y_no_toca_lo_guardado(): void
    {
        \Illuminate\Support\Facades\Http::fake(['graph.facebook.com/*' => \Illuminate\Support\Facades\Http::response(['error' => ['message' => 'Invalid OAuth access token']], 401)]);
        $r = $this->enElCrm()->postJson("/bixocrm/canales/{$this->canal->id}/probar", ['access_token' => 'NUEVO'])->assertStatus(422);
        $this->assertStringContainsString('OAuth', $r->json('error'));
        $this->assertNull($this->canal->fresh()->ultimo_error, 'Probar un token sin guardar no ensucia el estado del canal');
        \Illuminate\Support\Facades\Http::assertSent(fn ($req) => $req->hasHeader('Authorization', 'Bearer NUEVO'));
    }
    /**
     * La API oficial de Meta NO permite borrar un mensaje ya enviado (el campo `status`
     * solo acepta 'read'). Pedirlo devuelve un aviso claro y no borra nada.
     */
    public function test_whatsapp_no_permite_borrar_en_el_telefono_del_cliente(): void
    {
        $this->canal->update(['phone_number_id' => '777', 'access_token' => 'TOKEN']);
        $c = $this->conv();
        $m = $c->mensajes()->create(['direccion' => 'saliente', 'tipo' => 'texto', 'contenido' => 'Mensaje con error', 'estado' => 'enviado', 'wa_message_id' => 'wamid.abc']);
        \Illuminate\Support\Facades\Http::fake();

        $r = $this->enElCrm()->deleteJson("/bixocrm/{$c->id}/mensajes/{$m->id}?para_todos=1")->assertStatus(422);

        $this->assertStringContainsString('app de WhatsApp', $r->json('error'));
        \Illuminate\Support\Facades\Http::assertNothingSent();
        $this->assertDatabaseHas('wa_mensajes', ['id' => $m->id]);
    }

    public function test_quitar_solo_de_la_bandeja_no_llama_a_meta(): void
    {
        $c = $this->conv();
        $m = $c->mensajes()->create(['direccion' => 'saliente', 'tipo' => 'texto', 'contenido' => 'X', 'estado' => 'enviado', 'wa_message_id' => 'wamid.x']);
        \Illuminate\Support\Facades\Http::fake();

        $this->enElCrm()->deleteJson("/bixocrm/{$c->id}/mensajes/{$m->id}")->assertOk()->assertJson(['para_todos' => false]);

        \Illuminate\Support\Facades\Http::assertNothingSent();
        $this->assertDatabaseMissing('wa_mensajes', ['id' => $m->id]);
    }

    /**
     * Abrir el chat pone los dos checks AZULES en el telefono del cliente.
     * Antes solo lo hacia el bot al contestar: en un chat atendido a mano el
     * cliente se quedaba en visto gris creyendo que lo ignoran.
     */
    public function test_abrir_el_chat_avisa_a_meta_que_se_leyo(): void
    {
        $this->canal->update(['phone_number_id' => '777', 'access_token' => 'TOKEN']);
        $c = $this->conv();
        $viejo = $c->mensajes()->create(['direccion' => 'entrante', 'tipo' => 'texto', 'contenido' => 'Hola', 'estado' => 'entregado', 'wa_message_id' => 'wamid.1']);
        $ultimo = $c->mensajes()->create(['direccion' => 'entrante', 'tipo' => 'texto', 'contenido' => '¿Precio?', 'estado' => 'entregado', 'wa_message_id' => 'wamid.2']);
        \Illuminate\Support\Facades\Http::fake(['graph.facebook.com/*' => \Illuminate\Support\Facades\Http::response(['success' => true], 200)]);

        $this->enElCrm()->getJson("/bixocrm/{$c->id}/mensajes")->assertOk();

        // Se avisa del ULTIMO sin leer: Meta marca ese y todos los anteriores.
        \Illuminate\Support\Facades\Http::assertSent(fn ($req) => ($req->data()['status'] ?? '') === 'read' && ($req->data()['message_id'] ?? '') === 'wamid.2');
        $this->assertSame('leido', $ultimo->fresh()->estado);
        $this->assertSame('leido', $viejo->fresh()->estado, 'Los anteriores tambien quedan leidos');
    }

    /** Sin linea de Meta (bot por QR) no hay a quien avisar: no se llama a la Graph API. */
    public function test_sin_linea_de_meta_no_se_avisa_nada(): void
    {
        $this->canal->update(['phone_number_id' => null, 'access_token' => null]);
        $c = $this->conv();
        $c->mensajes()->create(['direccion' => 'entrante', 'tipo' => 'texto', 'contenido' => 'Hola', 'estado' => 'entregado', 'wa_message_id' => 'wamid.9']);
        \Illuminate\Support\Facades\Http::fake();

        $this->enElCrm()->getJson("/bixocrm/{$c->id}/mensajes")->assertOk();

        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    /** Abrir un chat ya leido no molesta a Meta otra vez. */
    public function test_abrir_un_chat_ya_leido_no_reenvia_el_aviso(): void
    {
        $this->canal->update(['phone_number_id' => '777', 'access_token' => 'TOKEN']);
        $c = $this->conv();
        $c->mensajes()->create(['direccion' => 'entrante', 'tipo' => 'texto', 'contenido' => 'Hola', 'estado' => 'leido', 'wa_message_id' => 'wamid.5']);
        \Illuminate\Support\Facades\Http::fake();

        $this->enElCrm()->getJson("/bixocrm/{$c->id}/mensajes")->assertOk();

        \Illuminate\Support\Facades\Http::assertNothingSent();
    }
}
