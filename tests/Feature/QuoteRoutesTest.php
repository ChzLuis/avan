<?php

namespace Tests\Feature;

use App\Modules\Ventas\Controllers\QuoteController;
use App\Models\Employee;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Modules\Ventas\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tabla de rutas de "convertir": dos conceptos con el mismo verbo.
 *
 *   /f/{slug}/cotizaciones/{id}/convertir        -> convertirPortal()  (COMPROBANTE, F4)
 *   /bixosales/cotizaciones/{quote}/convertir-pedido -> convert()      (PEDIDO, F1c)
 *
 * No habia colision tecnica de URI, pero si de concepto. Y hasta F1c la
 * conversion a pedido solo existia en el panel con 'can:quotes.editar' puro:
 * un usuario del universo heredado podia editar y enviar, pero no convertir.
 */
class QuoteRoutesTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['quotes.ver', 'quotes.editar', 'view-quotes', 'manage-quotes', 'orders.ver'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Rutas QA', 'slug' => 'rutas-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        foreach (['orders', 'quotes'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
    }

    private function usuario(array $permisos, ?Project $project = null): User
    {
        $project = $project ?? $this->project;
        $rol = Role::findOrCreate('rutas_' . md5(implode(',', $permisos)), 'web');
        $rol->syncPermissions($permisos);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $project->id, 'user_id' => $u->id,
            'name' => 'U', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $u->syncRoles([$rol->name]);
        $this->actingAs($u)->withSession([
            'comercial_project_id' => $project->id,
            'active_project_id'    => $project->id,
        ]);

        return $u;
    }

    private function quote(?Project $project = null): Quote
    {
        $q = Quote::create([
            'project_id' => ($project ?? $this->project)->id, 'client_name' => 'C',
            'status' => 'accepted', 'total' => '10.00', 'token' => str()->random(24),
        ]);
        $q->items()->create(['description' => 'X', 'price' => '10.00', 'quantity' => 1, 'discount' => 0]);

        return $q;
    }

    public function test_cada_ruta_apunta_a_su_accion(): void
    {
        $rutas = collect(Route::getRoutes())->mapWithKeys(fn ($r) => [$r->getName() => $r->getActionName()]);

        $this->assertStringContainsString('QuoteController@convertirPortal',
            $rutas['facturacion.cotizaciones.convertir'], 'la ruta fiscal emite COMPROBANTE');
        $this->assertStringContainsString('QuoteController@convert',
            $rutas['bixosales.cotizaciones.convertir_pedido'], 'la ruta de BixoSales crea PEDIDO');

        // El nombre debe ser inequivoco para quien lea el codigo dentro de un ano.
        $this->assertStringContainsString('convertir-pedido',
            collect(Route::getRoutes())->first(fn ($r) => $r->getName() === 'bixosales.cotizaciones.convertir_pedido')->uri());
    }

    public function test_el_universo_canonico_B_puede_convertir_en_bixosales(): void
    {
        $this->usuario(['quotes.ver', 'quotes.editar']);
        $q = $this->quote();

        $this->postJson("/bixosales/cotizaciones/{$q->id}/convertir-pedido")->assertSuccessful();
        $this->assertSame('converted', $q->fresh()->status);
    }

    public function test_el_universo_heredado_A_tambien_puede(): void
    {
        // Justo lo que no podia antes: la ruta del panel exigia 'quotes.editar'
        // puro, asi que 'manage-quotes' se quedaba fuera.
        $this->usuario(['view-quotes', 'manage-quotes']);
        $q = $this->quote();

        $this->postJson("/bixosales/cotizaciones/{$q->id}/convertir-pedido")->assertSuccessful();
        $this->assertSame('converted', $q->fresh()->status);
    }

    public function test_sin_permiso_de_edicion_es_403(): void
    {
        $this->usuario(['quotes.ver']);
        $q = $this->quote();

        $this->postJson("/bixosales/cotizaciones/{$q->id}/convertir-pedido")->assertStatus(403);
        $this->assertSame('accepted', $q->fresh()->status);
    }

    /**
     * Aislamiento multiproyecto. Responde **404**, no 403, y es lo correcto:
     * `Quote` lleva un scope global por proyecto activo (HasProjectScope), asi
     * que el route-model binding ni siquiera encuentra la fila ajena. Un 403
     * confirmaria que ese id existe en otro proyecto; el 404 no revela nada.
     */
    public function test_una_cotizacion_de_otro_proyecto_no_es_alcanzable(): void
    {
        $otro = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ajeno', 'slug' => 'ajeno-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        $ajena = $this->quote($otro);

        $this->usuario(['quotes.ver', 'quotes.editar']);   // sesion en MI proyecto

        $this->postJson("/bixosales/cotizaciones/{$ajena->id}/convertir-pedido")->assertStatus(404);
        $this->postJson("/quotes/{$ajena->id}/convert")->assertStatus(404);

        $this->assertSame('accepted', $ajena->fresh()->status, 'la cotizacion ajena no se toca');
        $this->assertSame(0, \App\Modules\Ventas\Models\Order::where('quote_id', $ajena->id)->count());
    }

    /**
     * Paridad de los DOS shells: la misma capacidad debe existir en el panel y
     * en BixoSales. La ruta del panel usaba 'can:quotes.editar' puro, asi que
     * un usuario legacy veia el boton (QuoteAbilities se lo concede) y recibia
     * 403 al pulsarlo.
     */
    public function test_el_universo_heredado_A_tambien_convierte_en_el_panel(): void
    {
        $this->usuario(['view-quotes', 'manage-quotes']);
        $q = $this->quote();

        $this->postJson("/quotes/{$q->id}/convert")->assertSuccessful();
        $this->assertSame('converted', $q->fresh()->status);
    }

    public function test_sin_permiso_tampoco_convierte_en_el_panel(): void
    {
        $this->usuario(['quotes.ver']);
        $q = $this->quote();

        $this->postJson("/quotes/{$q->id}/convert")->assertStatus(403);
        $this->assertSame('accepted', $q->fresh()->status);
    }
}
