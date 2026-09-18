<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Modules\Ventas\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Enlaces profundos en Cotizaciones (paridad con Pedidos).
 *
 * `show()` devolvia SIEMPRE JSON: entrar por /cotizaciones/{id} en el navegador
 * escupia el objeto crudo en pantalla, y por eso los enlaces PED->COT no tenian
 * a donde apuntar. Ahora responde segun lo que pide el cliente.
 */
class QuoteDeepLinkTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['quotes.ver', 'quotes.editar', 'view-quotes', 'manage-quotes'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Deep QA', 'slug' => 'deep-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        foreach (['orders', 'quotes'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
    }

    private function lector(?Project $project = null): User
    {
        $project = $project ?? $this->project;
        Role::findOrCreate('deep_lector', 'web')->syncPermissions(['quotes.ver']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $project->id, 'user_id' => $u->id,
            'name' => 'Lector', 'spatie_role' => 'deep_lector', 'is_active' => 1]);
        $u->syncRoles(['deep_lector']);
        $this->actingAs($u)->withSession([
            'comercial_project_id' => $project->id,
            'active_project_id'    => $project->id,
        ]);

        return $u;
    }

    private function quote(?Project $project = null): Quote
    {
        $q = Quote::create([
            'project_id' => ($project ?? $this->project)->id, 'client_name' => 'Cliente Deep',
            'status' => 'sent', 'total' => '250.00', 'token' => str()->random(24),
        ]);
        $q->items()->create(['description' => 'Servicio', 'price' => '250.00', 'quantity' => 1, 'discount' => 0]);

        return $q;
    }

    public function test_la_navegacion_html_devuelve_la_interfaz_con_la_cotizacion_abierta(): void
    {
        $this->lector();
        $q = $this->quote();

        $r = $this->get("/bixosales/cotizaciones/{$q->id}")->assertSuccessful();

        $r->assertViewIs('ventas::quotes.index');
        $this->assertSame($q->id, $r->viewData('cotizacionInicial'),
            'la vista debe saber que cotizacion abrir');
        $this->assertStringNotContainsString('"client_name":"Cliente Deep","status"', $r->getContent(),
            'no puede devolver el JSON crudo del modelo');
    }

    public function test_la_peticion_ajax_sigue_devolviendo_json(): void
    {
        $this->lector();
        $q = $this->quote();

        $this->getJson("/bixosales/cotizaciones/{$q->id}")
            ->assertSuccessful()
            ->assertJsonStructure(['id', 'client_name', 'status', 'items']);
    }

    public function test_la_lista_no_lleva_cotizacion_inicial(): void
    {
        $this->lector();
        $this->quote();

        $r = $this->get('/bixosales/cotizaciones')->assertSuccessful();

        $this->assertNull($r->viewData('cotizacionInicial'));
    }

    public function test_un_id_inexistente_es_404(): void
    {
        $this->lector();

        $this->get('/bixosales/cotizaciones/999999')->assertStatus(404);
    }

    public function test_una_cotizacion_de_otro_proyecto_no_es_alcanzable(): void
    {
        $otro = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Otro Deep', 'slug' => 'otro-deep', 'category' => 'retail', 'is_active' => true,
        ]);
        $ajena = $this->quote($otro);

        $this->lector();   // sesion en MI proyecto

        // El scope global de proyecto impide siquiera resolver la fila: 404, que
        // ademas no confirma que ese id exista en otro proyecto.
        $this->get("/bixosales/cotizaciones/{$ajena->id}")->assertStatus(404);
    }

    public function test_la_vista_trae_lo_necesario_para_el_historial(): void
    {
        $this->lector();
        $q = $this->quote();

        $html = $this->get("/bixosales/cotizaciones/{$q->id}")->getContent();

        // Sin esto no hay Atras/Adelante ni foco devuelto a la fila de origen.
        $this->assertStringContainsString('montarHistorial()', $html);
        $this->assertStringContainsString('popstate', $html);
        $this->assertStringContainsString('sincronizarUrl', $html);
        $this->assertStringContainsString('cotizacionInicial: ' . $q->id, $html);
    }
}
