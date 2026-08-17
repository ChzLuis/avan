<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Models\Order;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Frontera del modulo Pedidos (paquete integrado del Paso 2).
 *
 * La capacidad operativa es EXPLICITA: el modulo 'logistics' del proyecto.
 * OrderFlow::supportsFlow() devuelve true para cualquier categoria no vacia
 * ("tiene rubro"), asi que no sirve como frontera. Y la cobranza pertenece a
 * Cuentas por Cobrar: el KPI principal de Pedidos es comercial ('nuevos'),
 * no 'por_cobrar'.
 */
class OrdersBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['orders.ver', 'orders.crear', 'orders.editar', 'quotes.ver'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
    }

    private function proyecto(bool $conLogistics): Project
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name'     => 'Frontera ' . ($conLogistics ? 'con' : 'sin'),
            'slug'     => 'frontera-' . ($conLogistics ? 'con' : 'sin'),
            'category' => 'retail',      // supportsFlow('retail') === true
            'is_active' => true,
        ]);

        $claves = $conLogistics ? ['orders', 'quotes', 'logistics'] : ['orders', 'quotes'];
        foreach ($claves as $key) {
            $module = Module::firstOrCreate(['key' => $key], ['name' => ucfirst($key), 'is_active' => true]);
            $project->modules()->syncWithoutDetaching([$module->id => ['is_active' => true]]);
        }

        Order::create(['project_id' => $project->id, 'client_name' => 'C', 'status' => 'pending', 'total' => 10]);
        Order::create(['project_id' => $project->id, 'client_name' => 'C', 'status' => 'done', 'total' => 20]);

        return $project;
    }

    private function entrar(Project $project): User
    {
        Role::findOrCreate('lector_frontera', 'web')->syncPermissions(['orders.ver', 'quotes.ver']);
        $user = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $user->id, 'role' => 'viewer']);
        Employee::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'name' => 'Lector', 'spatie_role' => 'lector_frontera', 'is_active' => 1,
        ]);
        $user->syncRoles(['lector_frontera']);

        $this->actingAs($user)->withSession([
            'comercial_project_id' => $project->id,
            'active_project_id'    => $project->id,
        ]);

        return $user;
    }

    public function test_sin_modulo_logistics_no_hay_flujo_operativo_aunque_la_categoria_lo_soporte(): void
    {
        $project = $this->proyecto(conLogistics: false);
        $this->entrar($project);

        $respuesta = $this->get('/bixosales/pedidos')->assertSuccessful();

        $this->assertSame([], $respuesta->viewData('flujoOperativo'),
            'Sin capacidad logistics el flujo operativo debe quedar vacio');
    }

    public function test_con_modulo_logistics_el_flujo_del_rubro_se_conserva(): void
    {
        $project = $this->proyecto(conLogistics: true);
        $this->entrar($project);

        $respuesta = $this->get('/bixosales/pedidos')->assertSuccessful();

        $flujo = $respuesta->viewData('flujoOperativo');
        $this->assertNotEmpty($flujo, 'Con capacidad activa, retail conserva su flujo');
        $this->assertContains('recibido', array_column($flujo, 'key'));
    }

    public function test_los_kpis_son_comerciales_y_ya_no_incluyen_cobranza(): void
    {
        $project = $this->proyecto(conLogistics: false);
        $this->entrar($project);

        $kpis = $this->get('/bixosales/pedidos')->viewData('kpis');

        $this->assertArrayHasKey('nuevos', $kpis);
        $this->assertSame(1, $kpis['nuevos']);          // 1 pending de los 2 sembrados
        $this->assertArrayHasKey('completados', $kpis); // sustituto sin flujo
        $this->assertArrayNotHasKey('por_cobrar', $kpis,
            'La cobranza pertenece a Cuentas por Cobrar, no a los KPIs de Pedidos');
    }

    public function test_orders_total_fuera_del_rango_decimal_da_422(): void
    {
        // orders.total es decimal(10,2) — verificado en create_orders_table
        // (el (12,2) anterior venia del cast del modelo, correccion Codex).
        $project = $this->proyecto(conLogistics: false);
        $u = $this->entrar($project);
        \Spatie\Permission\Models\Role::findOrFail(
            \Spatie\Permission\Models\Role::where('name','lector_frontera')->value('id')
        )->givePermissionTo('orders.crear');
        $u->syncRoles(['lector_frontera']);

        $this->postJson('/bixosales/pedidos', ['client_name' => 'B', 'items' => [
            ['name' => 'A', 'price' => '99999999.99', 'quantity' => 1],
            ['name' => 'B', 'price' => '0.02', 'quantity' => 1],
        ]])->assertStatus(422);

        // dentro del rango: pasa
        $this->postJson('/bixosales/pedidos', ['client_name' => 'B', 'items' => [
            ['name' => 'A', 'price' => '10.00', 'quantity' => 1],
        ]])->assertSuccessful();
    }

    public function test_el_lector_recibe_capacidades_sin_logistica(): void
    {
        $project = $this->proyecto(conLogistics: true);
        $this->entrar($project);

        $puede = $this->get('/bixosales/pedidos')->viewData('puede');

        $this->assertFalse($puede['logistica'], 'orders.ver no concede mutaciones operativas');
        $this->assertFalse($puede['editar']);
        $this->assertTrue($puede['ver']);
    }
}
