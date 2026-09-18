<?php

namespace Tests\Feature;

use App\Modules\Finanzas\Models\GuiaRemision;
use App\Modules\Finanzas\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guías: ubigeo de destino y descarga (2026-09-11).
 *
 * El ubigeo de llegada NO vive ni en la factura ni en la ficha del cliente, y
 * SUNAT lo exige: se tecleaba a mano en cada guía. Pero sí está en las guías
 * ya emitidas a ese mismo destinatario. Y el histórico solo tenía Ver e
 * Imprimir: para adjuntar la guía a un correo había que imprimirla a PDF.
 */
class GuiaUbigeoYDescargaTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_superadmin' => true]);
        $this->project = Project::create([
            'owner_id' => $this->user->id, 'name' => 'Ferretería QA', 'slug' => 'gu-'.uniqid(), 'is_active' => true,
        ]);
        $m = \App\Models\Module::firstOrCreate(['key' => 'invoices'], ['name' => 'invoices', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
    }

    private function comoDueno()
    {
        return $this->actingAs($this->user)->withSession(['active_project_id' => $this->project->id]);
    }

    private function guia(array $extra = []): GuiaRemision
    {
        static $n = 0; $n++;
        return GuiaRemision::create($extra + [
            'project_id' => $this->project->id, 'serie' => 'T001', 'correlativo' => $n,
            'numero' => 'T001-'.str_pad((string) $n, 8, '0', STR_PAD_LEFT),
            'emisor_razon_social' => 'QA', 'emisor_ruc' => '20600819110',
            'destinatario_nombre' => 'NOEL & CIA S.A.C.', 'destinatario_doc_tipo' => '6', 'destinatario_doc_numero' => '20602814379',
            'motivo_codigo' => '01', 'fecha_traslado' => now()->toDateString(), 'modalidad' => '02', 'vehiculo_m1l' => true,
            'peso_total' => 60, 'peso_unidad' => 'KGM',
            'partida_direccion' => 'JR. A 1', 'partida_ubigeo' => '150101',
            'llegada_direccion' => 'AV. ARGENTINA 339', 'llegada_ubigeo' => '150101',
            'status' => 'issued', 'sunat_status' => 'accepted',
        ]);
    }

    /** Al elegir el comprobante se propone el destino de la última guía a ese cliente. */
    public function test_propone_el_ubigeo_de_la_ultima_guia_al_mismo_cliente(): void
    {
        $this->guia();
        $factura = Invoice::create([
            'project_id' => $this->project->id, 'type' => 'factura', 'serie' => 'F001', 'correlativo' => 16,
            'numero' => 'F001-00000016', 'client_name' => 'NOEL & CIA S.A.C.', 'client_doc_type' => 'RUC',
            'client_doc_number' => '20602814379', 'total' => 100, 'status' => 'issued', 'sunat_status' => 'accepted',
            'issue_date' => now()->toDateString(),
        ]);

        $r = $this->comoDueno()->getJson(route('guias.opciones', ['invoice_id' => $factura->id]))->assertOk();

        $this->assertSame('150101', $r->json('desde_venta.ultimo_destino.ubigeo'));
        $this->assertSame('AV. ARGENTINA 339', $r->json('desde_venta.ultimo_destino.direccion'));
    }

    /** Un cliente nuevo no hereda el destino de otro. */
    public function test_un_cliente_sin_guias_no_hereda_destino(): void
    {
        $this->guia();
        $otra = Invoice::create([
            'project_id' => $this->project->id, 'type' => 'factura', 'serie' => 'F001', 'correlativo' => 17,
            'numero' => 'F001-00000017', 'client_name' => 'CLIENTE NUEVO SAC', 'client_doc_type' => 'RUC',
            'client_doc_number' => '20999999999', 'total' => 50, 'status' => 'issued', 'sunat_status' => 'accepted',
            'issue_date' => now()->toDateString(),
        ]);

        $r = $this->comoDueno()->getJson(route('guias.opciones', ['invoice_id' => $otra->id]))->assertOk();

        $this->assertNull($r->json('desde_venta.ultimo_destino'));
    }

    /**
     * El destino de un negocio no se filtra a otro.
     *
     * `GuiaRemision` lleva `HasProjectScope`: toda consulta se filtra sola por
     * el proyecto activo. Para crear la guia AJENA hay que salirse de ese
     * ambito a proposito (`withoutGlobalScope`), o el propio modelo la crearia
     * con el project_id equivocado y el test no probaria nada.
     */
    public function test_no_se_cruzan_los_negocios(): void
    {
        $ajeno = Project::create(['owner_id' => User::factory()->create()->id, 'name' => 'Ajeno', 'slug' => 'aj-'.uniqid(), 'is_active' => true]);
        GuiaRemision::withoutGlobalScope('project')->create([
            'project_id' => $ajeno->id, 'serie' => 'T001', 'correlativo' => 99, 'numero' => 'T001-00000099',
            'emisor_razon_social' => 'Ajeno', 'emisor_ruc' => '20111111111',
            'destinatario_nombre' => 'NOEL & CIA S.A.C.', 'destinatario_doc_numero' => '20602814379',
            'motivo_codigo' => '01', 'fecha_traslado' => now()->toDateString(), 'modalidad' => '02',
            'peso_total' => 10, 'partida_direccion' => 'ORIGEN AJENO', 'partida_ubigeo' => '040101',
            'llegada_ubigeo' => '999999', 'llegada_direccion' => 'DIRECCION AJENA',
            'status' => 'issued',
        ]);
        $factura = Invoice::create([
            'project_id' => $this->project->id, 'type' => 'factura', 'serie' => 'F001', 'correlativo' => 18,
            'numero' => 'F001-00000018', 'client_name' => 'NOEL & CIA S.A.C.', 'client_doc_number' => '20602814379',
            'total' => 10, 'status' => 'issued', 'issue_date' => now()->toDateString(),
        ]);

        $r = $this->comoDueno()->getJson(route('guias.opciones', ['invoice_id' => $factura->id]))->assertOk();

        $this->assertNull($r->json('desde_venta.ultimo_destino'), 'no debe ver el destino del otro negocio');
    }

    /** El histórico ofrece Descargar además de Ver e Imprimir. */
    public function test_el_historico_ofrece_descargar(): void
    {
        $g = $this->guia();

        $html = $this->comoDueno()->get(route('guias.consulta'))->assertOk()->getContent();

        $this->assertStringContainsString('Descargar', $html);
        $this->assertStringContainsString(route('guias.pdf', $g->id).'?descargar=1', $html);
    }
}
