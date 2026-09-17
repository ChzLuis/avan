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
 * Filtros del reporte "Comprobantes emitidos" (2026-09-05).
 *
 * El usuario avisó de que "no funcionaban bien". Tres causas reales:
 * la tabla mostraba la fecha interna pero el filtro miraba la fiscal;
 * "F001-2" no encontraba F001-00000002; y un comprobante dado de baja
 * seguía saliendo como "Aceptado" sin forma de filtrarlo.
 */
class ConsultaComprobantesFiltrosTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::findOrCreate('invoices.ver', 'web');
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Filtros QA', 'slug' => 'filtros-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        $m = Module::firstOrCreate(['key' => 'invoices'], ['name' => 'invoices', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);

        Role::findOrCreate('filtros_qa', 'web')->syncPermissions(['invoices.ver']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'Cajero', 'spatie_role' => 'filtros_qa', 'is_active' => 1]);
        $u->syncRoles(['filtros_qa']);
        $this->actingAs($u)->withSession([
            'active_project_id'    => $this->project->id,
            'comercial_project_id' => $this->project->id,
        ]);

        $base = ['project_id' => $this->project->id, 'type' => 'factura', 'serie' => 'F001',
                 'status' => 'sent', 'sunat_status' => 'accepted', 'subtotal' => 100, 'igv' => 18, 'total' => 118];

        // Fiscal 04/09 pero interna 03/09: en la tabla se ve 03/09.
        Invoice::create($base + ['correlativo' => 2, 'numero' => 'F001-00000002', 'client_name' => 'Cliente Interna',
            'issue_date' => '2026-09-04', 'internal_issue_date' => '2026-09-03']);
        Invoice::create($base + ['correlativo' => 3, 'numero' => 'F001-00000003', 'client_name' => 'Cliente Fiscal',
            'issue_date' => '2026-09-04']);
        Invoice::create($base + ['correlativo' => 4, 'numero' => 'F001-00000004', 'client_name' => 'Cliente Baja',
            'issue_date' => '2026-09-04', 'baja_estado' => 'accepted']);
    }

    private function consulta(array $filtros): string
    {
        return $this->get(route('bixosales.facturas.consulta', $filtros))->assertOk()->getContent();
    }

    public function test_el_filtro_de_fecha_usa_la_misma_fecha_que_muestra_la_tabla(): void
    {
        $html = $this->consulta(['desde' => '2026-09-03', 'hasta' => '2026-09-03']);

        $this->assertStringContainsString('Cliente Interna', $html);
        $this->assertStringNotContainsString('Cliente Fiscal', $html);
    }

    public function test_el_numero_corto_encuentra_el_comprobante(): void
    {
        foreach (['F001-2', 'f001 2', 'F001-02'] as $q) {
            $html = $this->consulta(['q' => $q]);
            $this->assertStringContainsString('Cliente Interna', $html, "buscando '$q'");
            $this->assertStringNotContainsString('Cliente Fiscal', $html, "buscando '$q'");
        }
    }

    public function test_un_comprobante_dado_de_baja_se_ve_y_se_filtra_como_anulado(): void
    {
        $html = $this->consulta(['estado' => 'anulado']);
        $this->assertStringContainsString('Cliente Baja', $html);
        $this->assertStringNotContainsString('Cliente Fiscal', $html);

        // Y al filtrar "aceptados" el anulado ya no cuenta como tal.
        $html = $this->consulta(['estado' => 'accepted']);
        $this->assertStringContainsString('Cliente Fiscal', $html);
        $this->assertStringNotContainsString('Cliente Baja', $html);
    }
}
