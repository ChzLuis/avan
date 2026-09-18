<?php

namespace Tests\Feature;

use App\Modules\Finanzas\Models\GuiaRemision;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Historico propio de guias de remision (2026-09-11).
 *
 * La pantalla de Guias mezcla la lista corta con el formulario de emitir:
 * sirve para sacar la siguiente, no para encontrar la de hace dos meses. El
 * historico busca y consulta, con los mismos filtros que "Comprobantes
 * emitidos".
 */
class HistoricoGuiasTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $user;
    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_superadmin' => true]);
        $this->project = Project::create([
            'owner_id' => $this->user->id, 'name' => 'Ferretería QA', 'slug' => 'hg-'.uniqid(), 'is_active' => true,
        ]);
        $modulo = \App\Models\Module::firstOrCreate(['key' => 'invoices'], ['name' => 'invoices', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$modulo->id => ['is_active' => true]]);
    }

    private function comoDueno()
    {
        return $this->actingAs($this->user)->withSession(['active_project_id' => $this->project->id]);
    }

    private function guia(Project $p, array $extra = []): GuiaRemision
    {
        // Contador por test: uno estatico se arrastraba entre tests y
        // "T001-1" dejaba de existir en el segundo.
        $n = ++$this->n;
        return GuiaRemision::create($extra + [
            'project_id' => $p->id, 'serie' => 'T001', 'correlativo' => $n,
            'numero' => 'T001-'.str_pad((string) $n, 8, '0', STR_PAD_LEFT),
            'emisor_razon_social' => 'FERRETERIA QA', 'emisor_ruc' => '20600819110',
            'destinatario_nombre' => 'INVERSIONES FERRE IMPORT SAC', 'destinatario_doc_tipo' => '6', 'destinatario_doc_numero' => '20568337221',
            'motivo_codigo' => '01', 'fecha_traslado' => now()->toDateString(), 'modalidad' => '02', 'vehiculo_m1l' => true,
            'peso_total' => 50, 'peso_unidad' => 'KGM',
            'partida_direccion' => 'JR. A 1', 'partida_ubigeo' => '150101', 'llegada_direccion' => 'AV. B 2', 'llegada_ubigeo' => '120101',
            'status' => 'issued', 'sunat_status' => 'accepted',
        ]);
    }

    public function test_el_historico_lista_las_guias_del_negocio(): void
    {
        $this->guia($this->project);

        $r = $this->comoDueno()->get(route('guias.consulta'))->assertOk();

        $r->assertSee('T001-00000001');
        $r->assertSee('INVERSIONES FERRE IMPORT SAC');
        $r->assertSee('Aceptada');
    }

    /** "T001-1" encuentra T001-00000001: nadie teclea ocho digitos. */
    public function test_busca_por_numero_corto_destinatario_y_placa(): void
    {
        $this->guia($this->project);
        $this->guia($this->project, ['destinatario_nombre' => 'OTRO CLIENTE EIRL', 'vehiculo_placa' => 'ABC123', 'vehiculo_m1l' => false]);

        $this->comoDueno()->get(route('guias.consulta', ['q' => 'T001-1']))->assertOk()
            ->assertSee('INVERSIONES FERRE IMPORT SAC')->assertDontSee('OTRO CLIENTE EIRL');
        $this->comoDueno()->get(route('guias.consulta', ['q' => 'ABC123']))->assertOk()
            ->assertSee('OTRO CLIENTE EIRL')->assertDontSee('INVERSIONES FERRE IMPORT SAC');
        $this->comoDueno()->get(route('guias.consulta', ['q' => '20568337221']))->assertOk()
            ->assertSee('INVERSIONES FERRE IMPORT SAC');
    }

    public function test_filtra_por_estado_y_por_fechas(): void
    {
        $this->guia($this->project, ['sunat_status' => 'accepted', 'fecha_traslado' => '2026-09-01']);
        $this->guia($this->project, ['destinatario_nombre' => 'PENDIENTE SAC', 'sunat_status' => 'error', 'fecha_traslado' => '2026-09-10']);

        $this->comoDueno()->get(route('guias.consulta', ['estado' => 'error']))->assertOk()
            ->assertSee('PENDIENTE SAC')->assertDontSee('INVERSIONES FERRE IMPORT SAC');
        $this->comoDueno()->get(route('guias.consulta', ['desde' => '2026-09-05']))->assertOk()
            ->assertSee('PENDIENTE SAC')->assertDontSee('INVERSIONES FERRE IMPORT SAC');
        $this->comoDueno()->get(route('guias.consulta', ['hasta' => '2026-09-05']))->assertOk()
            ->assertSee('INVERSIONES FERRE IMPORT SAC')->assertDontSee('PENDIENTE SAC');
    }

    /** Un negocio no ve las guias de otro. */
    public function test_no_se_cruzan_los_negocios(): void
    {
        $ajeno = Project::create(['owner_id' => User::factory()->create()->id, 'name' => 'Ajeno', 'slug' => 'aj-'.uniqid(), 'is_active' => true]);
        $this->guia($ajeno, ['destinatario_nombre' => 'CLIENTE AJENO SAC']);
        $this->guia($this->project);

        $this->comoDueno()->get(route('guias.consulta'))->assertOk()
            ->assertSee('INVERSIONES FERRE IMPORT SAC')->assertDontSee('CLIENTE AJENO SAC');
    }

    /** Sin guias, la pantalla explica que esperar en vez de quedarse muda. */
    public function test_sin_guias_explica(): void
    {
        $this->comoDueno()->get(route('guias.consulta'))->assertOk()->assertSee('Todavía no hay guías emitidas');
    }

    /** La pantalla de Guias enlaza al historico y el menu de Ventas tambien. */
    public function test_se_llega_desde_guias(): void
    {
        $this->comoDueno()->get(route('guias.index'))->assertOk()->assertSee(route('guias.consulta'));
    }
}
