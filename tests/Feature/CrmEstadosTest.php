<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Crm\Models\CrmEstado;
use App\Modules\Crm\Models\WaCanal;
use App\Modules\Crm\Models\WaConversacion;
use App\Support\Productos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Estados de conversacion editables por negocio: siembra al entrar, alta/renombrado/color/orden,
 * borrado que no deja chats huerfanos, validacion contra la tabla y aislamiento entre negocios.
 */
class CrmEstadosTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Project $proyecto;
    private WaCanal $canal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuario = User::factory()->create();
        $this->proyecto = Project::create(['owner_id' => $this->usuario->id, 'name' => 'CRM estados', 'slug' => 'crm-est-' . uniqid(), 'is_active' => true]);
        Productos::activar($this->proyecto, 'crm');
        $this->canal = WaCanal::create(['project_id' => $this->proyecto->id, 'nombre' => 'L', 'tipo' => 'bixo', 'activo' => true, 'phone_number_id' => '1', 'access_token' => 'T']);
    }

    private function enElCrm()
    {
        return $this->actingAs($this->usuario)->withSession(['comunicaciones_project_id' => $this->proyecto->id]);
    }

    private function conv(string $estado = 'nuevo'): WaConversacion
    {
        return WaConversacion::create(['wa_canal_id' => $this->canal->id, 'cliente_nombre' => 'Ana', 'cliente_telefono' => '5199' . rand(1000, 9999), 'estado' => $estado, 'no_leidos' => 0, 'ultimo_mensaje_at' => now(), 'bot_activo' => true]);
    }

    public function test_al_entrar_a_la_bandeja_se_siembran_los_estados_del_negocio(): void
    {
        $this->enElCrm()->get('/bixocrm')->assertOk();

        $estados = CrmEstado::where('project_id', $this->proyecto->id)->orderBy('orden')->get();
        $this->assertCount(count(CrmEstado::SEMILLA), $estados);
        $this->assertSame('nuevo', $estados->first()->clave);
        $this->assertTrue($estados->first()->es_inicial);
        // Los que pidio el usuario existen desde el arranque.
        $this->assertNotNull($estados->firstWhere('clave', 'no_responde'));
        $this->assertNotNull($estados->firstWhere('clave', 'proyecto'));
        $this->assertTrue($estados->firstWhere('clave', 'venta')->es_final);

        // Entrar de nuevo no duplica.
        $this->enElCrm()->get('/bixocrm')->assertOk();
        $this->assertCount(count(CrmEstado::SEMILLA), CrmEstado::where('project_id', $this->proyecto->id)->get());
    }

    public function test_crear_renombrar_recolorear_y_reordenar(): void
    {
        $this->enElCrm()->get('/bixocrm');
        $estados = CrmEstado::where('project_id', $this->proyecto->id)->orderBy('orden')->get();

        $lista = $estados->map(fn ($e) => ['id' => $e->id, 'nombre' => $e->nombre, 'color' => $e->color, 'es_inicial' => $e->es_inicial, 'es_final' => $e->es_final])->all();
        $lista[1]['nombre'] = 'Ya lo llamé';                 // renombrar
        $lista[1]['color'] = '#ff0000';                      // recolorear
        $lista[] = ['id' => null, 'nombre' => 'Visita agendada', 'color' => '#111827']; // crear
        $lista = array_merge([array_pop($lista)], $lista);    // el nuevo va primero

        $r = $this->enElCrm()->putJson('/bixocrm/estados', ['estados' => $lista])->assertOk();

        $this->assertTrue($r->json('ok'));
        $devueltos = collect($r->json('estados'));
        $this->assertSame('Visita agendada', $devueltos->first()['nombre'], 'Respeta el orden enviado');
        $this->assertSame('visita_agendada', $devueltos->first()['clave'], 'La clave sale del nombre');
        $this->assertNotNull($devueltos->firstWhere('nombre', 'Ya lo llamé'));
        $this->assertSame('#ff0000', $devueltos->firstWhere('nombre', 'Ya lo llamé')['color']);
    }

    public function test_al_borrar_un_estado_sus_chats_pasan_al_inicial(): void
    {
        $this->enElCrm()->get('/bixocrm');
        $conv = $this->conv('propuesta');
        $estados = CrmEstado::where('project_id', $this->proyecto->id)->orderBy('orden')->get();

        $lista = $estados->reject(fn ($e) => $e->clave === 'propuesta')
            ->map(fn ($e) => ['id' => $e->id, 'nombre' => $e->nombre, 'color' => $e->color, 'es_inicial' => $e->es_inicial, 'es_final' => $e->es_final])->values()->all();

        $this->enElCrm()->putJson('/bixocrm/estados', ['estados' => $lista])->assertOk();

        $this->assertDatabaseMissing('crm_estados', ['project_id' => $this->proyecto->id, 'clave' => 'propuesta']);
        $this->assertSame('nuevo', $conv->fresh()->estado, 'El chat no queda con un estado fantasma');
    }

    public function test_solo_se_puede_poner_un_estado_que_exista_en_el_negocio(): void
    {
        $this->enElCrm()->get('/bixocrm');
        $conv = $this->conv();

        $this->enElCrm()->patchJson("/bixocrm/{$conv->id}", ['estado' => 'proyecto'])->assertOk();
        $this->assertSame('proyecto', $conv->fresh()->estado);

        $this->enElCrm()->patchJson("/bixocrm/{$conv->id}", ['estado' => 'inventado'])->assertStatus(422);
        $this->assertSame('proyecto', $conv->fresh()->estado);
    }

    public function test_los_estados_de_otro_negocio_no_se_mezclan(): void
    {
        $this->enElCrm()->get('/bixocrm');
        $otro = Project::create(['owner_id' => User::factory()->create()->id, 'name' => 'Otro', 'slug' => 'otro-' . uniqid(), 'is_active' => true]);
        CrmEstado::create(['project_id' => $otro->id, 'clave' => 'solo_suyo', 'nombre' => 'Solo suyo', 'color' => '#000000', 'orden' => 0]);

        $claves = collect($this->enElCrm()->getJson('/bixocrm/estados')->assertOk()->json('estados'))->pluck('clave');
        $this->assertNotContains('solo_suyo', $claves);

        $conv = $this->conv();
        $this->enElCrm()->patchJson("/bixocrm/{$conv->id}", ['estado' => 'solo_suyo'])->assertStatus(422);
    }
    /** La semilla trae los estados de venta que pidio el usuario. */
    public function test_la_semilla_incluye_venta_negociacion_seguimiento_y_no_responde(): void
    {
        $this->enElCrm()->get('/bixocrm')->assertOk();
        $claves = CrmEstado::where('project_id', $this->proyecto->id)->orderBy('orden')->pluck('clave')->all();

        foreach (['nuevo', 'contactado', 'seguimiento', 'demo_enviada', 'propuesta', 'negociacion', 'no_responde', 'proyecto', 'venta', 'perdido'] as $c) {
            $this->assertContains($c, $claves, "Falta el estado {$c}");
        }
        $this->assertTrue(CrmEstado::where('project_id', $this->proyecto->id)->where('clave', 'venta')->first()->es_final);
    }

    /** Prospectos pinta los estados del negocio, no una lista escrita a mano. */
    public function test_prospectos_usa_los_estados_del_negocio(): void
    {
        $this->enElCrm()->get('/bixocrm');
        // Un estado propio del negocio tiene que aparecer en la pantalla de Prospectos.
        CrmEstado::create(['project_id' => $this->proyecto->id, 'clave' => 'visita', 'nombre' => 'Visita agendada', 'color' => '#123456', 'orden' => 99]);

        $html = $this->enElCrm()->get('/bixocrm/clientes')->assertOk()->getContent();

        $this->assertStringContainsString('Visita agendada', $html);
        $this->assertStringContainsString('Negociaci', $html, 'Los estados nuevos de la semilla tambien salen');
        $this->assertStringNotContainsString('Academia', $html, 'Ya no se usa la lista vieja');
    }
}
