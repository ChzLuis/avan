<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Crm\Models\CrmEtapa;
use App\Modules\Crm\Models\CrmTrato;
use App\Modules\Crm\Models\WaCanal;
use App\Modules\Crm\Models\WaConversacion;
use App\Support\Productos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tratos: embudo de ventas del CRM. Etapas por negocio sembradas al entrar,
 * alta (manual o desde un chat de WhatsApp), mover entre etapas con marca de
 * ganado/perdido y motivo, edicion de etapas sin perder tratos, aislamiento.
 */
class CrmTratosTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuario = User::factory()->create();
        $this->proyecto = Project::create(['owner_id' => $this->usuario->id, 'name' => 'CRM tratos', 'slug' => 'crm-tratos-' . uniqid(), 'is_active' => true]);
        Productos::activar($this->proyecto, 'crm');
    }

    private function enElCrm()
    {
        return $this->actingAs($this->usuario)->withSession(['comunicaciones_project_id' => $this->proyecto->id]);
    }

    public function test_al_entrar_se_siembra_el_embudo_por_defecto_y_se_pinta_el_tablero(): void
    {
        $this->enElCrm()->get('/bixocrm/tratos')->assertOk()->assertViewIs('crm::tratos.index');

        $etapas = CrmEtapa::where('project_id', $this->proyecto->id)->orderBy('orden')->get();
        $this->assertCount(6, $etapas);
        $this->assertSame('Nuevo', $etapas->first()->nombre);
        $this->assertTrue($etapas->firstWhere('nombre', 'Ganado')->es_ganado);
        $this->assertTrue($etapas->firstWhere('nombre', 'Perdido')->es_perdido);
    }

    public function test_crear_trato_desde_una_conversacion_lo_deja_enlazado_al_chat(): void
    {
        $canal = WaCanal::create(['project_id' => $this->proyecto->id, 'nombre' => 'L', 'tipo' => 'bixo', 'activo' => true]);
        $conv = WaConversacion::create(['wa_canal_id' => $canal->id, 'cliente_nombre' => 'Rosa', 'cliente_telefono' => '51999111222', 'estado' => 'nuevo', 'no_leidos' => 0, 'ultimo_mensaje_at' => now(), 'bot_activo' => false]);

        $this->enElCrm()->get('/bixocrm/tratos?nuevo=1&conversacion=' . $conv->id)->assertOk()->assertSee('51999111222');

        $this->enElCrm()->postJson('/bixocrm/tratos', ['titulo' => 'Tienda para Rosa', 'valor' => 490, 'wa_conversacion_id' => $conv->id, 'contacto_nombre' => 'Rosa', 'contacto_telefono' => '51999111222'])
            ->assertCreated()->assertJsonPath('trato.origen', 'whatsapp');

        $t = CrmTrato::firstOrFail();
        $this->assertSame($conv->id, $t->wa_conversacion_id);
        $this->assertSame('Nuevo', $t->etapa->nombre, 'Sin etapa indicada cae en la primera.');
        $this->assertSame($this->proyecto->id, $t->project_id);
    }

    public function test_mover_a_perdido_exige_motivo_y_mover_a_ganado_marca_la_fecha(): void
    {
        $this->enElCrm()->get('/bixocrm/tratos');
        $t = CrmTrato::create(['project_id' => $this->proyecto->id, 'etapa_id' => CrmEtapa::where('nombre', 'Nuevo')->value('id'), 'titulo' => 'X', 'valor' => 100, 'etapa_desde' => now()->subDays(9)]);
        $ganado = CrmEtapa::where('project_id', $this->proyecto->id)->where('es_ganado', true)->firstOrFail();
        $perdido = CrmEtapa::where('project_id', $this->proyecto->id)->where('es_perdido', true)->firstOrFail();

        $this->assertSame(9, $t->diasEnEtapa());

        $this->enElCrm()->patchJson("/bixocrm/tratos/{$t->id}/mover", ['etapa_id' => $ganado->id])->assertOk();
        $this->assertNotNull($t->fresh()->ganado_at);
        $this->assertSame(0, $t->fresh()->diasEnEtapa(), 'Al moverse, el contador de dias arranca de nuevo.');

        $this->enElCrm()->patchJson("/bixocrm/tratos/{$t->id}/mover", ['etapa_id' => $perdido->id, 'motivo_perdida' => 'Precio'])->assertOk();
        $t->refresh();
        $this->assertNull($t->ganado_at);
        $this->assertNotNull($t->perdido_at);
        $this->assertSame('Precio', $t->motivo_perdida);
    }

    public function test_editar_etapas_reordena_y_una_etapa_borrada_no_pierde_sus_tratos(): void
    {
        $this->enElCrm()->get('/bixocrm/tratos');
        $etapas = CrmEtapa::where('project_id', $this->proyecto->id)->orderBy('orden')->get();
        $cotizado = $etapas->firstWhere('nombre', 'Cotizado');
        $t = CrmTrato::create(['project_id' => $this->proyecto->id, 'etapa_id' => $cotizado->id, 'titulo' => 'En cotizado', 'etapa_desde' => now()]);

        $nuevas = $etapas->reject(fn ($e) => $e->id === $cotizado->id)->values()->map(fn ($e) => ['id' => $e->id, 'nombre' => $e->nombre . '!', 'color' => $e->color, 'probabilidad' => $e->probabilidad, 'es_ganado' => $e->es_ganado, 'es_perdido' => $e->es_perdido])->all();
        $nuevas[] = ['id' => null, 'nombre' => 'Visita', 'color' => '#000000', 'probabilidad' => 60];

        $this->enElCrm()->putJson('/bixocrm/tratos/etapas', ['etapas' => $nuevas])->assertOk();

        $this->assertDatabaseMissing('crm_etapas', ['id' => $cotizado->id]);
        $this->assertDatabaseHas('crm_etapas', ['project_id' => $this->proyecto->id, 'nombre' => 'Visita', 'orden' => 5]);
        $this->assertSame('Nuevo!', $t->fresh()->etapa->nombre, 'El trato de la etapa borrada pasa a la primera.');
    }

    public function test_los_tratos_y_etapas_de_otro_negocio_no_se_tocan(): void
    {
        $otro = Project::create(['owner_id' => User::factory()->create()->id, 'name' => 'Otro', 'slug' => 'otro-' . uniqid(), 'is_active' => true]);
        CrmEtapa::asegurar($otro);
        $ajeno = CrmTrato::create(['project_id' => $otro->id, 'etapa_id' => CrmEtapa::where('project_id', $otro->id)->orderBy('orden')->value('id'), 'titulo' => 'Ajeno', 'etapa_desde' => now()]);
        $this->enElCrm()->get('/bixocrm/tratos');

        $this->enElCrm()->patchJson("/bixocrm/tratos/{$ajeno->id}", ['titulo' => 'Hackeado'])->assertForbidden();
        $this->enElCrm()->deleteJson("/bixocrm/tratos/{$ajeno->id}")->assertForbidden();
        // Una etapa ajena no sirve para mover un trato propio.
        $mio = CrmTrato::create(['project_id' => $this->proyecto->id, 'etapa_id' => CrmEtapa::where('project_id', $this->proyecto->id)->orderBy('orden')->value('id'), 'titulo' => 'Mio', 'etapa_desde' => now()]);
        $this->enElCrm()->patchJson("/bixocrm/tratos/{$mio->id}/mover", ['etapa_id' => CrmEtapa::where('project_id', $otro->id)->orderBy('orden')->value('id')])->assertStatus(422);
        $this->assertSame('Ajeno', $ajeno->fresh()->titulo);
    }
}
