<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Module;
use App\Models\Project;
use App\Models\User;
use App\Support\ApisPeruService;
use App\Support\Sunat\ArchivoComprobantes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Brechas de cumplimiento cerradas el 2026-09-05 (ver
 * docs/architecture/BIXO_SUNAT_CUMPLIMIENTO.md):
 *  - el XML firmado y el CDR se archivan como ficheros y se descargan;
 *  - una boleta se anula por resumen diario, no por comunicacion de baja;
 *  - el QR del comprobante se genera en local.
 */
class CumplimientoSunatTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(ArchivoComprobantes::DISCO);
        $this->user = User::factory()->create();
        $this->project = Project::create([
            'owner_id' => $this->user->id, 'name' => 'Cumplimiento QA', 'slug' => 'cumpl-qa', 'is_active' => true,
        ]);
        $m = Module::firstOrCreate(['key' => 'invoices'], ['name' => 'invoices', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        $this->project->settings()->createMany([
            ['key' => 'apisperu_token', 'value' => 'token-qa'],
            ['key' => 'billing_provider', 'value' => 'apisperu'],
        ]);
    }

    private function comoDueno()
    {
        return $this->actingAs($this->user)->withSession([
            'active_project_id' => $this->project->id, 'comercial_project_id' => $this->project->id,
        ]);
    }

    private function aceptada(string $tipo = 'factura', string $serie = 'F001', int $n = 9): Invoice
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?><Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2">firmado</Invoice>';
        $respuesta = [
            'xml'  => $xml,
            'hash' => 'Ks/nyGh6Lk2VESTt60LtshZO/ws=',
            'sunatResponse' => [
                'success' => true,
                'cdrZip'  => base64_encode('PK-zip-de-prueba'),
                'cdrResponse' => ['id' => "20601234567-01-$serie-$n", 'code' => '0', 'description' => 'aceptada', 'notes' => []],
            ],
        ];

        return Invoice::create([
            'project_id' => $this->project->id, 'type' => $tipo, 'serie' => $serie, 'correlativo' => $n,
            'numero' => sprintf('%s-%08d', $serie, $n), 'client_name' => 'CLIENTE',
            'client_doc_type' => $tipo === 'factura' ? 'RUC' : 'DNI',
            'client_doc_number' => $tipo === 'factura' ? '20123456789' : '12345678',
            'emisor_ruc' => '20601234567', 'emisor_razon_social' => 'EMISOR SAC',
            'status' => 'sent', 'sunat_status' => 'accepted', 'sunat_hash' => 'Ks/nyGh6Lk2VESTt60LtshZO/ws=',
            'sunat_cdr' => json_encode($respuesta), 'issue_date' => now()->toDateString(),
            'subtotal' => 100, 'igv' => 18, 'total' => 118, 'currency' => 'PEN',
        ]);
    }

    public function test_el_xml_y_el_cdr_se_archivan_con_el_nombre_normado(): void
    {
        $inv = $this->aceptada();

        $hecho = ArchivoComprobantes::guardar($inv);

        $this->assertSame(['xml' => true, 'cdr' => true], $hecho);
        Storage::disk(ArchivoComprobantes::DISCO)->assertExists("comprobantes/{$this->project->id}/20601234567-01-F001-00000009.xml");
        Storage::disk(ArchivoComprobantes::DISCO)->assertExists("comprobantes/{$this->project->id}/R-20601234567-01-F001-00000009.zip");
        $this->assertStringContainsString('firmado', Storage::disk(ArchivoComprobantes::DISCO)->get(ArchivoComprobantes::rutaXml($inv)));
    }

    public function test_el_xml_y_el_cdr_se_descargan_desde_sales_y_desde_el_panel(): void
    {
        $inv = $this->aceptada();

        $r = $this->comoDueno()->get(route('bixosales.facturas.xml', $inv->id));
        $r->assertOk();
        $this->assertStringContainsString('20601234567-01-F001-00000009.xml', $r->headers->get('content-disposition'));

        $r = $this->comoDueno()->get(route('invoices.cdr', $inv->id));
        $r->assertOk();
        $this->assertStringContainsString('R-20601234567-01-F001-00000009.zip', $r->headers->get('content-disposition'));
    }

    public function test_sin_aceptacion_de_sunat_no_hay_xml_que_descargar(): void
    {
        $inv = $this->aceptada();
        $inv->update(['sunat_status' => 'pending', 'sunat_cdr' => null]);

        $this->comoDueno()->get(route('bixosales.facturas.xml', $inv->id))->assertStatus(404);
    }

    public function test_una_boleta_se_anula_por_resumen_diario_con_la_boleta_en_estado_3(): void
    {
        $boleta = $this->aceptada('boleta', 'B001', 12);
        $boleta->setRelation('project', $this->project);

        $this->assertTrue($boleta->sePuedeDarDeBaja(), 'la boleta ya admite anulacion');

        $payload = (new ApisPeruService)->payloadResumenBaja($boleta);

        $this->assertSame('1', $payload['correlativo']);
        $this->assertStringStartsWith(now()->toDateString(), $payload['fecResumen']);
        $d = $payload['details'][0];
        $this->assertSame('03', $d['tipoDoc']);
        $this->assertSame('B001-12', $d['serieNro']);
        $this->assertSame('3', $d['estado']);
        $this->assertSame('1', $d['clienteTipo']);
        $this->assertSame('12345678', $d['clienteNro']);
        $this->assertEquals(118, $d['total']);
        $this->assertEquals(18, $d['mtoIGV']);
        $this->assertEquals(100, $d['mtoOperGravadas']);
    }

    public function test_la_pantalla_deja_anular_boletas_y_el_servidor_responde_con_el_camino_del_resumen(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $boleta = $this->aceptada('boleta', 'B001', 12);

        $r = $this->comoDueno()->postJson(route('bixosales.facturas.baja', $boleta->id), ['motivo' => 'Error en el importe']);

        $r->assertOk();
        $this->assertStringContainsString('resumen diario', $r->json('message'));
        $this->assertSame('pending', $boleta->fresh()->baja_estado);
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\DarDeBajaEnSunat::class);
    }

    public function test_el_pdf_genera_el_qr_en_local_sin_servicios_externos(): void
    {
        $inv = $this->aceptada();

        foreach (['', 'clasico'] as $plantilla) {
            $this->project->settings()->updateOrCreate(['key' => 'invoice_template'], ['value' => $plantilla]);
            $html = $this->comoDueno()->get(route('bixosales.facturas.pdf', $inv->id))->assertOk()->getContent();

            $this->assertStringNotContainsString('qrserver', $html, "plantilla '$plantilla' sigue usando el servicio externo");
            $this->assertStringContainsString('data-qr="20601234567|01|F001|9|18.00|118.00|', $html);
            $this->assertStringContainsString('var qrcode=function', $html, 'la libreria QR va embebida');
        }
    }
}
