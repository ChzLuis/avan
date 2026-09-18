<?php

namespace Tests\Feature;

use App\Modules\Crm\Models\Client;
use App\Models\Employee;
use App\Models\Module;
use App\Modules\Ventas\Models\Order;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Modules\Ventas\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El documento se enlaza con la ficha del cliente.
 *
 * `orders.client_id` y `quotes.client_id` existían, el backend los validaba y
 * los guardaba, y ninguna pantalla los enviaba: en ARIN, 0 de 37 documentos
 * estaban enlazados. La consecuencia es que la ficha del cliente —que ya sabe
 * calcular lo vendido y la deuda desde el libro de cobros— mostraba siempre
 * cero. No estaba mal hecha: no tenía de dónde leer.
 */
class ClienteEnlazadoTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['quotes.ver', 'quotes.crear', 'quotes.editar', 'clients.ver',
                  'view-quotes', 'manage-quotes'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Cliente QA', 'slug' => 'cliente-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        foreach (['orders', 'quotes', 'clients'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }

        $rol = Role::findOrCreate('cli_qa', 'web');
        $rol->syncPermissions(['quotes.ver', 'quotes.crear', 'quotes.editar', 'clients.ver']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'U', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $u->syncRoles([$rol->name]);
        $this->actingAs($u)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);
    }

    /** La pantalla recibe la cartera para poder sugerirla. */
    public function test_la_vista_recibe_la_cartera_del_negocio(): void
    {
        Client::create(['project_id' => $this->project->id, 'name' => 'Ferretería Molina', 'phone' => '999111222']);

        $clients = $this->get('/bixosales/cotizaciones')->assertSuccessful()->viewData('clients');

        $this->assertCount(1, $clients);
        $this->assertSame('Ferretería Molina', $clients->first()->name);
    }

    /** Y el formulario envía el enlace, que es lo que faltaba. */
    public function test_el_formulario_envia_el_enlace_con_la_ficha(): void
    {
        Client::create(['project_id' => $this->project->id, 'name' => 'Ferretería Molina']);

        $html = $this->get('/bixosales/cotizaciones')->getContent();

        $this->assertStringContainsString('client_id: this.form.client_id', $html);
        $this->assertStringContainsString('usarCliente(c)', $html);
        $this->assertStringContainsString('buscarCliente(', $html);
    }

    /** Al guardar con enlace, el documento queda en el historial del cliente. */
    public function test_una_cotizacion_enlazada_aparece_en_la_ficha_del_cliente(): void
    {
        $cliente = Client::create(['project_id' => $this->project->id, 'name' => 'Ferretería Molina']);

        $this->postJson('/bixosales/cotizaciones', [
            'client_name' => 'Ferretería Molina',
            'client_id'   => $cliente->id,
            'items'       => [['description' => 'Taladro', 'price' => '200.00', 'quantity' => 1, 'discount' => 0]],
        ])->assertSuccessful();

        $quote = Quote::latest('id')->first();
        $this->assertSame($cliente->id, $quote->client_id);
        $this->assertSame(1, $cliente->quotes()->count());
    }

    /**
     * La deuda de la ficha sale del libro de cobros, no de un campo suelto.
     * Con el documento enlazado, por fin tiene algo que sumar.
     */
    public function test_la_ficha_suma_la_deuda_de_los_pedidos_enlazados(): void
    {
        $cliente = Client::create(['project_id' => $this->project->id, 'name' => 'Ferretería Molina']);
        Order::create(['project_id' => $this->project->id, 'client_id' => $cliente->id,
            'client_name' => 'Ferretería Molina', 'status' => 'pending', 'total' => '300.00']);

        $ficha = $this->getJson('/bixosales/clientes/'.$cliente->id)->assertSuccessful()->json();

        $this->assertSame(1, $ficha['resumen']['pedidos']);
        $this->assertSame(30000, $ficha['resumen']['deuda_cents']);
    }

    /** Un documento sin cliente en la cartera sigue siendo válido. */
    public function test_se_puede_cotizar_a_alguien_que_no_esta_en_la_cartera(): void
    {
        $this->postJson('/bixosales/cotizaciones', [
            'client_name' => 'Cliente de paso',
            'items'       => [['description' => 'Taladro', 'price' => '50.00', 'quantity' => 1, 'discount' => 0]],
        ])->assertSuccessful();

        $quote = Quote::latest('id')->first();
        $this->assertNull($quote->client_id);
        $this->assertSame('Cliente de paso', $quote->client_name);
    }

    /** Y no se puede enlazar con la ficha de otro negocio. */
    public function test_no_se_enlaza_con_un_cliente_de_otro_negocio(): void
    {
        $otro = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ajeno', 'slug' => 'ajeno-cli', 'category' => 'retail', 'is_active' => true,
        ]);
        $ajeno = Client::create(['project_id' => $otro->id, 'name' => 'Cliente Ajeno']);

        $clients = $this->get('/bixosales/cotizaciones')->viewData('clients');

        $this->assertCount(0, $clients, 'la cartera de otro negocio no puede aparecer aquí');
    }
}
