<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\RifaVenta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Aislamiento multiempresa (RISK-001 y plantilla de TD-002).
 *
 * La regla: un registro de otro negocio NI SE ENCUENTRA. No es un 403 de
 * cortesía — para el intruso ese ID no existe (404). Estos contratos fijan
 * el primer caso corregido (RifaVenta, cuyos métodos recibían la venta por
 * la URL sin comprobar el proyecto) y sirven de plantilla para cubrir el
 * resto de entidades, una por una, en la Fase 1.
 */
class AislamientoTenantTest extends TestCase
{
    use RefreshDatabase;

    private Project $mio;
    private Project $ajeno;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['orders.ver', 'rifas.cancelar', 'rifas.validar'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->mio = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Negocio Propio', 'slug' => 'propio-qa', 'category' => 'rifa', 'is_active' => true,
        ]);
        $this->ajeno = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Negocio Ajeno', 'slug' => 'ajeno-qa', 'category' => 'rifa', 'is_active' => true,
        ]);
    }

    private function ventaDe(Project $proyecto): RifaVenta
    {
        return RifaVenta::allProjects()->create([
            'project_id' => $proyecto->id,
            'order_number' => 'RF-'.$proyecto->id.'-001',
            'wa_number' => '51999888777', 'plan' => 'basico', 'plan_nombre' => 'Básico',
            'tickets' => 3, 'monto' => 30, 'nombre' => 'Cliente '.$proyecto->name,
            'status' => 'pendiente',
        ]);
    }

    private function entrarComo(Project $proyecto): void
    {
        Role::findOrCreate('tenant_qa', 'web')->syncPermissions(['orders.ver', 'rifas.cancelar', 'rifas.validar']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $proyecto->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $proyecto->id, 'user_id' => $u->id,
            'name' => 'Empleado QA', 'spatie_role' => 'tenant_qa', 'is_active' => 1]);
        $u->syncRoles(['tenant_qa']);
        $this->actingAs($u)->withSession([
            'comercial_project_id' => $proyecto->id,
            'active_project_id'    => $proyecto->id,
        ]);
    }

    /** El hueco original: cancelar la venta de OTRO negocio cambiando el ID. */
    public function test_cancelar_una_venta_ajena_devuelve_404(): void
    {
        $ventaAjena = $this->ventaDe($this->ajeno);
        $this->entrarComo($this->mio);

        $this->post('/bixosales/pedidos-bot/'.$ventaAjena->id.'/cancelar')
            ->assertNotFound();

        $this->assertSame('pendiente', RifaVenta::allProjects()->find($ventaAjena->id)->status,
            'La venta ajena no debe cambiar de estado');
    }

    public function test_cancelar_una_venta_propia_sigue_funcionando(): void
    {
        $ventaMia = $this->ventaDe($this->mio);
        $this->entrarComo($this->mio);

        $this->post('/bixosales/pedidos-bot/'.$ventaMia->id.'/cancelar')
            ->assertSuccessful();

        $this->assertSame('cancelado', RifaVenta::allProjects()->find($ventaMia->id)->status);
    }

    /** El scope filtra las consultas: la venta ajena ni aparece en listados. */
    public function test_el_scope_esconde_las_ventas_de_otros_negocios(): void
    {
        $this->ventaDe($this->mio);
        $this->ventaDe($this->ajeno);
        $this->entrarComo($this->mio);

        // Con sesion del proyecto propio, la consulta normal solo ve lo propio.
        $this->get('/bixosales'); // fija la sesion en el request
        $visibles = RifaVenta::count();
        $todas    = RifaVenta::allProjects()->count();

        $this->assertSame(1, $visibles, 'Solo la venta del proyecto en sesión');
        $this->assertSame(2, $todas, 'El escape explícito sí ve todo');
    }

    /** Los endpoints del bot no llevan sesión: el scope no les aplica y el
     *  bot de rifas en producción sigue encontrando sus ventas. */
    public function test_sin_sesion_el_bot_sigue_encontrando_la_venta(): void
    {
        $venta = $this->ventaDe($this->ajeno);

        // Sin actingAs ni sesión: así llegan los webhooks del bot.
        $encontrada = RifaVenta::find($venta->id);

        $this->assertNotNull($encontrada, 'Sin sesión de proyecto el scope es neutro (comportamiento actual del bot)');
    }
}
