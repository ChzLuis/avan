<?php

namespace Tests\Feature;

use App\Modules\Finanzas\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Modules\Finanzas\Support\ApisPeruService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cuatro huecos del flujo de emision cerrados el 2026-09-05.
 *
 * - El formulario ofrecia "Credito" pero el XML declaraba SIEMPRE "Contado".
 * - Una boleta de S/ 700 o mas salia sin identificar al comprador y SUNAT
 *   la rechazaba con el correlativo ya gastado.
 * - "Vista previa" era un boton vacio.
 */
class EmisionMejorasSunatTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->project = Project::create([
            'owner_id' => $this->user->id,
            'name' => 'Mejoras QA', 'slug' => 'mejoras-qa', 'is_active' => true,
        ]);
    }

    private function comoDueno()
    {
        return $this->actingAs($this->user)->withSession(['active_project_id' => $this->project->id]);
    }

    public function test_una_boleta_de_700_o_mas_exige_identificar_al_comprador(): void
    {
        $r = $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'boleta', 'client_name' => 'CLIENTE',
            'items' => [['description' => 'Televisor', 'quantity' => 1, 'unit_price' => 700]],
        ]);

        $r->assertStatus(422)->assertJsonValidationErrors('client_doc_number');
        // Y no se gasto correlativo.
        $this->assertSame(0, Invoice::allProjects()->where('project_id', $this->project->id)->count());

        // Con DNI pasa.
        $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'boleta', 'client_name' => 'CLIENTE',
            'client_doc_type' => 'DNI', 'client_doc_number' => '12345678',
            'items' => [['description' => 'Televisor', 'quantity' => 1, 'unit_price' => 700]],
        ])->assertOk();
    }

    public function test_por_debajo_de_700_la_boleta_sigue_saliendo_sin_documento(): void
    {
        $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'boleta', 'client_name' => 'CLIENTE',
            'items' => [['description' => 'Cable', 'quantity' => 1, 'unit_price' => 699.99]],
        ])->assertOk();
    }

    public function test_a_credito_el_vencimiento_es_obligatorio_y_se_guarda_la_condicion(): void
    {
        $base = [
            'type' => 'factura', 'client_name' => 'CLIENTE SAC',
            'client_doc_type' => 'RUC', 'client_doc_number' => '20601234567',
            'payment_condition' => 'credito',
            'items' => [['description' => 'Cable', 'quantity' => 2, 'unit_price' => 100]],
        ];

        $this->comoDueno()->postJson(route('invoices.store'), $base)
            ->assertStatus(422)->assertJsonValidationErrors('due_date');

        $r = $this->comoDueno()->postJson(route('invoices.store'), $base + [
            'issue_date' => now()->toDateString(), 'due_date' => now()->addDays(30)->toDateString(),
        ]);
        $r->assertOk();
        $this->assertSame('credito', $r->json('invoice.payment_condition'));
    }

    public function test_el_xml_declara_credito_con_su_cuota(): void
    {
        $inv = Invoice::allProjects()->make([
            'project_id' => $this->project->id, 'type' => 'factura', 'serie' => 'F001', 'correlativo' => 1,
            'client_name' => 'CLIENTE SAC', 'client_doc_type' => 'RUC', 'client_doc_number' => '20601234567',
            'emisor_ruc' => '20123456789', 'emisor_razon_social' => 'EMISOR',
            'subtotal' => 100, 'igv' => 18, 'total' => 118, 'currency' => 'PEN',
            'issue_date' => '2026-09-05', 'due_date' => '2026-10-05', 'payment_condition' => 'credito',
        ]);
        $inv->setRelation('project', $this->project);
        $inv->setRelation('items', collect([new \App\Modules\Finanzas\Models\InvoiceItem([
            'description' => 'Cable', 'unit' => 'NIU', 'quantity' => 1, 'unit_price' => 118, 'igv_amount' => 18, 'total' => 118,
        ])]));

        $m = new \ReflectionMethod(ApisPeruService::class, 'buildPayload');
        $m->setAccessible(true);
        $payload = $m->invoke(new ApisPeruService, $inv, '01');

        $this->assertSame('Credito', $payload['formaPago']['tipo']);
        $this->assertEquals(118, $payload['formaPago']['monto']);
        $this->assertCount(1, $payload['cuotas']);
        $this->assertStringStartsWith('2026-10-05', $payload['cuotas'][0]['fechaPago']);

        // Al contado no viajan cuotas.
        $inv->payment_condition = 'contado';
        $payload = $m->invoke(new ApisPeruService, $inv, '01');
        $this->assertSame('Contado', $payload['formaPago']['tipo']);
        $this->assertNull($payload['cuotas']);
    }

    public function test_la_vista_previa_ensena_la_hoja_sin_gastar_correlativo(): void
    {
        $r = $this->comoDueno()->post(route('invoices.previsualizar'), [
            'payload' => json_encode([
                'type' => 'factura', 'client_name' => 'CLIENTE SAC',
                'client_doc_type' => 'RUC', 'client_doc_number' => '20601234567',
                'items' => [
                    ['description' => 'Cable UTP', 'quantity' => 2, 'unit_price' => 100],
                    ['description' => '', 'quantity' => 1, 'unit_price' => 0], // fila vacia del formulario
                ],
            ]),
        ]);

        $r->assertOk();
        $html = $r->getContent();
        $this->assertStringContainsString('VISTA PREVIA', $html);
        $this->assertStringContainsString('Cable UTP', $html);
        $this->assertStringContainsString('200.00', $html);
        $this->assertStringNotContainsString('(sin productos)', $html);
        $this->assertSame(0, Invoice::allProjects()->where('project_id', $this->project->id)->count());
    }
}
