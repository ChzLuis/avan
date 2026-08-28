<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Portal del Cliente (Fase 11): el enlace personal con token para repetir
 * pedidos. Cubre las dos posturas del interruptor portal_precios y que la
 * puerta pública no filtre nada entre proyectos ni con tokens malos.
 */
class PortalClienteTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Client $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Portal QA', 'slug' => 'portal-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        $this->cliente = Client::create([
            'project_id' => $this->project->id,
            'name' => 'María Quispe',
            'phone' => '999888777',
            'portal_token' => Str::random(48),
        ]);
    }

    /** Un pedido histórico con dos líneas para repetir. */
    private function pedidoOriginal(): \App\Models\Order
    {
        $pedido = $this->cliente->orders()->create([
            'project_id' => $this->project->id, 'client_name' => $this->cliente->name,
            'status' => 'done', 'payment_status' => 'paid', 'total' => 70,
        ]);
        $producto = $this->project->products()->create([
            'name' => 'Detergente 5kg', 'price' => 42.50, 'is_active' => true,
        ]);
        $pedido->items()->create(['product_id' => $producto->id, 'name' => $producto->name, 'price' => 40, 'quantity' => 1]);
        $pedido->items()->create(['product_id' => null, 'name' => 'Lejía artesanal', 'price' => 15, 'quantity' => 2]);

        return $pedido;
    }

    public function test_el_token_valido_muestra_los_pedidos_del_cliente(): void
    {
        $this->pedidoOriginal();

        $res = $this->get('/c/'.$this->cliente->portal_token);

        $res->assertOk()
            ->assertSee('María Quispe')
            ->assertSee('Detergente 5kg')
            ->assertSee('Lejía artesanal');
    }

    public function test_token_invalido_o_corto_es_404(): void
    {
        $this->get('/c/abc')->assertNotFound();
        $this->get('/c/'.Str::random(48))->assertNotFound();
    }

    public function test_repetir_en_modo_fijos_crea_pedido_pendiente_a_precio_vigente(): void
    {
        $this->project->settings()->create(['key' => 'portal_precios', 'value' => 'fijos']);
        $original = $this->pedidoOriginal();

        $res = $this->post('/c/'.$this->cliente->portal_token.'/repetir/'.$original->id);

        $res->assertRedirect(route('portal.cliente', $this->cliente->portal_token));
        $nuevo = $this->cliente->orders()->where('id', '!=', $original->id)->with('items')->first();
        $this->assertNotNull($nuevo, 'Debe nacer un pedido nuevo');
        $this->assertSame('pending', $nuevo->status);
        $this->assertSame('portal', $nuevo->sales_channel);
        // El producto de catálogo viaja al precio VIGENTE (42.50, no 40);
        // la línea sin producto conserva su precio histórico (15).
        $this->assertEqualsWithDelta(42.50, (float) $nuevo->items->firstWhere('name', 'Detergente 5kg')->price, 0.001);
        $this->assertEqualsWithDelta(15.00, (float) $nuevo->items->firstWhere('name', 'Lejía artesanal')->price, 0.001);
        $this->assertEqualsWithDelta(72.50, (float) $nuevo->total, 0.001);
    }

    public function test_repetir_en_modo_confirmar_crea_cotizacion_sin_precios(): void
    {
        // Sin setting: 'confirmar' es el modo por defecto.
        $original = $this->pedidoOriginal();

        $this->post('/c/'.$this->cliente->portal_token.'/repetir/'.$original->id)
            ->assertRedirect(route('portal.cliente', $this->cliente->portal_token));

        $this->assertSame(0, $this->cliente->orders()->count() - 1, 'No debe nacer pedido en modo confirmar');
        $quote = $this->cliente->quotes()->with('items')->first();
        $this->assertNotNull($quote, 'Debe nacer una cotización');
        $this->assertSame('draft', $quote->status);
        $this->assertCount(2, $quote->items);
        $this->assertEqualsWithDelta(0, (float) $quote->items->sum('price'), 0.001, 'Precios por confirmar: todo a 0');
    }

    public function test_no_se_puede_repetir_un_pedido_de_otro_cliente(): void
    {
        $otro = Client::create(['project_id' => $this->project->id, 'name' => 'Otro', 'portal_token' => Str::random(48)]);
        $ajeno = $this->pedidoOriginal(); // pertenece a $this->cliente

        $this->post('/c/'.$otro->portal_token.'/repetir/'.$ajeno->id)->assertNotFound();
    }

    public function test_generar_enlace_exige_proyecto_propio(): void
    {
        foreach (['clients.editar', 'manage-clients'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        Role::findOrCreate('portal_qa', 'web')->syncPermissions(['clients.editar']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'editor']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'QA', 'spatie_role' => 'portal_qa', 'is_active' => 1]);
        $u->syncRoles(['portal_qa']);
        $this->actingAs($u)->withSession([
            'active_project_id'    => $this->project->id,
            'comercial_project_id' => $this->project->id,
        ]);

        $sinToken = Client::create(['project_id' => $this->project->id, 'name' => 'Nuevo Cliente']);
        $res = $this->postJson('/bixosales/clientes/'.$sinToken->id.'/portal');
        $res->assertOk();
        $this->assertStringContainsString('/c/', $res->json('url'));
        $this->assertNotNull($sinToken->fresh()->portal_token);

        // Cliente de un proyecto ajeno: el scope de sesión lo vuelve invisible.
        $ajeno = Project::create(['owner_id' => User::factory()->create()->id,
            'name' => 'Ajeno', 'slug' => 'ajeno-portal', 'category' => 'retail', 'is_active' => true]);
        $clienteAjeno = Client::allProjects()->create(['project_id' => $ajeno->id, 'name' => 'Intruso']);
        $this->postJson('/bixosales/clientes/'.$clienteAjeno->id.'/portal')->assertNotFound();
    }
}
