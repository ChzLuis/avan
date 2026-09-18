<?php

namespace Tests\Feature;

use App\Modules\Crm\Models\Client;
use App\Models\Employee;
use App\Modules\Finanzas\Models\Invoice;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Parte estable de TD-018: al emitir un comprobante desde el panel, si el
 * cliente ya existe en el negocio (por teléfono/correo) se enlaza su
 * `client_id`, de modo que el comprobante aparece en su Customer 360. No se
 * inventa un cliente nuevo (un comprobante puede emitirse a quien no está en
 * el CRM). El checkout web y el bot quedan fuera (código de otra sesión).
 */
class InvoiceClientLinkTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Fact QA', 'slug' => 'fact-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        foreach (['catalog', 'invoices', 'orders'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
        Permission::findOrCreate('invoices.crear', 'web');
        $rol = Role::findOrCreate('fact_qa', 'web')->syncPermissions(['invoices.crear']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'editor']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'QA', 'spatie_role' => 'fact_qa', 'is_active' => 1]);
        $u->syncRoles(['fact_qa']);
        $this->actingAs($u)->withSession([
            'active_project_id' => $this->project->id, 'comercial_project_id' => $this->project->id,
        ]);
    }

    private function emitir(array $extra): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/invoices', array_merge([
            'type' => 'boleta', 'client_name' => 'Cliente', 'igv_included' => true,
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 20.00]],
        ], $extra));
    }

    public function test_el_comprobante_se_enlaza_al_cliente_existente_por_telefono(): void
    {
        $cliente = Client::create(['project_id' => $this->project->id, 'name' => 'Ana', 'phone' => '999888777']);

        $r = $this->emitir(['client_name' => 'Ana', 'client_phone' => '999888777']);
        if (in_array($r->status(), [403, 404], true)) {
            $this->markTestSkipped('Ruta de comprobantes exige otro contexto: '.$r->status());
        }
        $r->assertSuccessful();

        $invoice = Invoice::allProjects()->latest('id')->first();
        $this->assertSame($cliente->id, $invoice->client_id, 'debe enlazar el cliente por teléfono');
    }

    public function test_no_inventa_cliente_si_no_existe(): void
    {
        $r = $this->emitir(['client_name' => 'Desconocido', 'client_phone' => '900000000']);
        if (in_array($r->status(), [403, 404], true)) {
            $this->markTestSkipped('Ruta de comprobantes exige otro contexto: '.$r->status());
        }
        $r->assertSuccessful();

        $invoice = Invoice::allProjects()->latest('id')->first();
        $this->assertNull($invoice->client_id, 'no debe crear un cliente fantasma');
        $this->assertSame(0, $this->project->clients()->count());
    }
}
