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
}
