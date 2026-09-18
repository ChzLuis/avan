<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Modules\Ventas\Models\Order;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La nota de pedido es un documento del servidor, no una captura del navegador.
 *
 * Hasta ahora "Nota PDF" fabricaba el papel con jsPDF: una imagen partida en
 * páginas, borrosa al imprimir y con un diseño que no se parecía al de la
 * cotización ni al del comprobante. Estos contratos fijan la nueva vía: el
 * servidor la sirve sobre la familia visual de documentos, en el panel y en
 * el portal comercial, y solo para pedidos del propio proyecto.
 */
class NotaPedidoTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['orders.ver', 'view-orders'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Nota QA', 'slug' => 'nota-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        $m = Module::firstOrCreate(['key' => 'orders'], ['name' => 'Pedidos', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
    }

    private function pedido(?Project $proyecto = null): Order
    {
        $proyecto ??= $this->project;
        $order = Order::create([
            'project_id' => $proyecto->id, 'client_name' => 'Cliente QA',
            'client_phone' => '999111222', 'status' => 'pending',
            'payment_status' => 'paid', 'payment_method' => 'Yape',
            'shipping_cost' => '10.00', 'total' => '128.00', 'sales_channel' => 'pos',
        ]);
        $order->items()->create(['name' => 'Cable HDMI 2 m', 'price' => '59.00', 'quantity' => 2]);
        return $order;
    }

    private function lector(): User
    {
        Role::findOrCreate('nota_lector', 'web')->syncPermissions(['orders.ver']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'Lector', 'spatie_role' => 'nota_lector', 'is_active' => 1]);
        $u->syncRoles(['nota_lector']);
        $this->actingAs($u)->withSession([
            'active_project_id'    => $this->project->id,
            'comercial_project_id' => $this->project->id,
        ]);
        return $u;
    }

    public function test_la_nota_sale_del_servidor_con_numero_lineas_y_total(): void
    {
        $order = $this->pedido();
        $this->lector();

        $this->get("/orders/{$order->id}/pdf")
            ->assertOk()
            ->assertSee('NOTA DE PEDIDO')
            ->assertSee('PED-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT))
            ->assertSee('Cliente QA')
            ->assertSee('Cable HDMI 2 m')
            ->assertSee('118.00')   // 59 × 2, por LineMath
            ->assertSee('128.00')   // total guardado (con envío)
            ->assertSee('Pagado')
            ->assertSee('no constituye')
            ->assertSee('comprobante de pago');
    }

    public function test_el_portal_comercial_sirve_el_mismo_documento(): void
    {
        $order = $this->pedido();
        $this->lector();

        $this->get("/bixosales/pedidos/{$order->id}/pdf")
            ->assertOk()
            ->assertSee('NOTA DE PEDIDO')
            ->assertSee('Cable HDMI 2 m');
    }

    public function test_el_pedido_de_otro_proyecto_no_se_abre(): void
    {
        $ajeno = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Otro', 'slug' => 'otro-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        $order = $this->pedido($ajeno);
        $this->lector();

        // El scope de proyecto lo filtra antes del binding: para el intruso
        // ese pedido ni existe (404), que es aun mas hermetico que un 403.
        $this->get("/orders/{$order->id}/pdf")->assertNotFound();
    }
}
