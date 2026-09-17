<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La seccion "Notas de credito y debito" del menu.
 *
 * Abria el formulario de venta con el tipo cambiado: serie de facturas,
 * cliente vacio, cinco lineas en blanco y ni documento afectado ni motivo.
 * Ahora manda a elegir el comprobante a corregir y lista las ya emitidas.
 */
class NotasPantallaTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['invoices.ver', 'invoices.crear', 'invoices.anular'] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ferretería Fiscal', 'slug' => 'ferre-notas',
            'category' => 'retail', 'is_active' => true,
        ]);
        $m = Module::firstOrCreate(['key' => 'invoices'], ['name' => 'Facturación', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);

        $rol = Role::findOrCreate('fiscal_pantalla', 'web')
            ->syncPermissions(['invoices.ver', 'invoices.crear', 'invoices.anular']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'Cajero', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $u->syncRoles([$rol->name]);

        $this->actingAs($u)->withSession(['active_project_id' => $this->project->id]);
    }

    private function factura(array $extra = []): Invoice
    {
        return $this->project->invoices()->create(array_merge([
            'type' => 'factura', 'serie' => 'F001', 'correlativo' => 7,
            'numero' => Invoice::buildNumero('F001', 7),
            'client_name' => 'Constructora Andina SAC',
            'client_doc_type' => 'RUC', 'client_doc_number' => '20600819110',
            'emisor_ruc' => '20512345678',
            'subtotal' => '100.00', 'igv' => '18.00', 'total' => '118.00',
            'issue_date' => now()->toDateString(),
            'status' => 'issued', 'sunat_status' => 'accepted',
        ], $extra));
    }

    /** Entrar por el menu ya no abre un formulario de venta en blanco. */
    public function test_la_seccion_notas_no_abre_el_formulario_de_venta(): void
    {
        $this->factura();

        $html = $this->get('/invoices?tipo=nota')->assertOk()->getContent();

        $this->assertStringContainsString('¿Qué comprobante quieres corregir?', $html);
        // El arranque ya no llama a openNew() para esta seccion.
        $this->assertStringNotContainsString("this.openNew(seccion === 'nota' ? 'nota_credito' : seccion)", $html);
    }

    /** Para elegir a cual corregir hacen falta las facturas, no solo las notas. */
    public function test_la_seccion_notas_trae_los_comprobantes_corregibles(): void
    {
        $this->factura();

        $html = $this->get('/invoices?tipo=nota')->assertOk()->getContent();

        $this->assertStringContainsString('F001-00000007', $html,
            'sin los comprobantes aceptados el buscador saldría vacío');
    }

    /** El filtro necesita baja_estado: sin el, ofreceria comprobantes ya anulados. */
    public function test_la_lista_dice_si_el_comprobante_esta_dado_de_baja(): void
    {
        $this->factura(['baja_estado' => 'accepted']);

        $html = $this->get('/invoices?tipo=nota')->assertOk()->getContent();

        $this->assertStringContainsString('baja_estado', $html);
    }
}
