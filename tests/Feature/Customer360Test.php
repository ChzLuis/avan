<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Customer 360 (Fase 6): la ficha responde con TODA la relación del cliente
 * en una línea de tiempo — cotizó, pidió, se le facturó, se le despachó —
 * agregando fuentes canónicas existentes, sin tablas nuevas (ADR-003).
 */
class Customer360Test extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['clients.ver', 'orders.ver', 'view-clients'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'C360 QA', 'slug' => 'c360-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        Role::findOrCreate('c360_qa', 'web')->syncPermissions(['clients.ver', 'orders.ver']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'QA', 'spatie_role' => 'c360_qa', 'is_active' => 1]);
        $u->syncRoles(['c360_qa']);
        $this->actingAs($u)->withSession([
            'active_project_id'    => $this->project->id,
            'comercial_project_id' => $this->project->id,
        ]);
    }

    public function test_la_ficha_agrega_toda_la_relacion_en_una_linea_de_tiempo(): void
    {
        $cliente = Client::create(['project_id' => $this->project->id, 'name' => 'Constructora Andina SAC']);
        $cliente->orders()->create(['project_id' => $this->project->id, 'client_name' => $cliente->name,
            'status' => 'done', 'total' => 350, 'payment_status' => 'paid']);
        $cliente->quotes()->create(['project_id' => $this->project->id, 'client_name' => $cliente->name,
            'status' => 'sent', 'total' => 890, 'token' => str()->random(48)]);
        Invoice::create(['project_id' => $this->project->id, 'client_id' => $cliente->id,
            'type' => 'factura', 'serie' => 'F001', 'correlativo' => 1, 'numero' => 'F001-00000001',
            'client_name' => $cliente->name, 'subtotal' => '296.61', 'igv' => '53.39', 'total' => '350.00',
            'currency' => 'PEN', 'issue_date' => now()->toDateString(), 'status' => 'issued',
            'sunat_status' => 'accepted']);

        $res = $this->getJson('/bixosales/clientes/'.$cliente->id);

        $res->assertOk()
            ->assertJsonPath('cliente.name', 'Constructora Andina SAC');

        $historial = collect($res->json('historial'));
        $this->assertGreaterThanOrEqual(3, $historial->count(), 'Cotización + pedido + comprobante');
        $tipos = $historial->pluck('tipo')->unique()->values()->all();
        foreach (['cotizacion', 'pedido', 'comprobante'] as $t) {
            $this->assertContains($t, $tipos, "El historial debe incluir {$t}");
        }
        $this->assertSame('Factura F001-00000001',
            $historial->firstWhere('tipo', 'comprobante')['etiqueta']);
    }

    public function test_la_ficha_de_un_cliente_ajeno_no_se_abre(): void
    {
        $ajeno = Project::create(['owner_id' => User::factory()->create()->id,
            'name' => 'Otro', 'slug' => 'otro-c360', 'category' => 'retail', 'is_active' => true]);
        $clienteAjeno = Client::allProjects()->create(['project_id' => $ajeno->id, 'name' => 'Secreto SAC']);

        // Con scope activo, el cliente ajeno ni se encuentra.
        $this->getJson('/bixosales/clientes/'.$clienteAjeno->id)->assertNotFound();
    }
}
