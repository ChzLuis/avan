<?php

namespace Tests\Feature;

use App\Modules\Crm\Models\Client;
use App\Modules\Ventas\Models\Order;
use App\Modules\Catalogo\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Usabilidad y campos del POS (2026-09-11).
 *
 * El vuelto salia como "V: S/ 12.00" en letra diminuta, y el cliente se
 * tecleaba a mano en CADA venta aunque ya estuviera registrado: sin historial,
 * sin telefono y sin cuenta por cobrar.
 */
class PosUsabilidadTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_superadmin' => true]);
        $this->project = Project::create([
            'owner_id' => $this->user->id, 'name' => 'Ferretería QA', 'slug' => 'pu-'.uniqid(), 'is_active' => true,
        ]);
        foreach (['orders', 'catalog', 'invoices'] as $key) {
            $m = \App\Models\Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
    }

    private function comoDueno()
    {
        return $this->actingAs($this->user)->withSession(['active_project_id' => $this->project->id]);
    }

    /** El vuelto se ve en grande y dice cuánto falta si el dinero no alcanza. */
    public function test_el_vuelto_se_lee_de_un_vistazo(): void
    {
        $html = $this->comoDueno()->get(route('pos.index'))->assertOk()->getContent();

        $this->assertStringContainsString("'Vuelto' : 'Falta'", $html, 'dice Vuelto o Falta, no siempre 0.00');
        $this->assertStringContainsString('text-2xl font-black', $html, 'el importe va en grande');
        $this->assertStringNotContainsString("'V: S/ '", $html, 'ya no es la etiqueta diminuta');
    }

    /** Se puede elegir un cliente ya registrado en vez de teclearlo cada vez. */
    public function test_se_puede_elegir_un_cliente_registrado(): void
    {
        $html = $this->comoDueno()->get(route('pos.index'))->assertOk()->getContent();

        foreach (['buscarCliente()', 'elegirCliente(', 'RUTA_CLIENTES', 'Nombre o RUC/DNI del cliente'] as $pieza) {
            $this->assertStringContainsString($pieza, $html, "falta $pieza");
        }
    }

    /** La venta queda atada al cliente: entra en su historial, no es texto suelto. */
    public function test_la_venta_se_ata_al_cliente_elegido(): void
    {
        $cliente = Client::create([
            'project_id' => $this->project->id, 'name' => 'FERRE IMPORT SAC', 'doc_number' => '20568337221',
        ]);
        $producto = Product::create([
            'project_id' => $this->project->id, 'name' => 'CABLE', 'price' => 50, 'stock' => 10, 'is_available' => true,
        ]);

        $this->comoDueno()->postJson(route('pos.store', $this->project), [
            'payment_method' => 'efectivo',
            'client_id'      => $cliente->id,
            'client_name'    => $cliente->name,
            'items'          => [['product_id' => $producto->id, 'name' => 'CABLE', 'price' => 50, 'quantity' => 1]],
        ])->assertOk();

        $this->assertSame($cliente->id, Order::where('project_id', $this->project->id)->value('client_id'));
    }

    /** El buscador encuentra por nombre y por documento, y no cruza negocios. */
    public function test_el_buscador_de_clientes_funciona_y_no_cruza_negocios(): void
    {
        Client::create(['project_id' => $this->project->id, 'name' => 'FERRE IMPORT SAC', 'doc_number' => '20568337221']);
        $ajeno = Project::create(['owner_id' => User::factory()->create()->id, 'name' => 'Ajeno', 'slug' => 'aj-'.uniqid(), 'is_active' => true]);
        Client::create(['project_id' => $ajeno->id, 'name' => 'CLIENTE AJENO SAC', 'doc_number' => '20999999999']);

        $porNombre = $this->comoDueno()->getJson(route('invoices.clientes', ['q' => 'FERRE']));
        $this->assertSame('FERRE IMPORT SAC', $porNombre->json('clientes.0.nombre'));

        $porDoc = $this->comoDueno()->getJson(route('invoices.clientes', ['q' => '20568337221']));
        $this->assertSame('FERRE IMPORT SAC', $porDoc->json('clientes.0.nombre'));

        $this->assertSame([], $this->comoDueno()->getJson(route('invoices.clientes', ['q' => 'AJENO']))->json('clientes'));
    }
}
