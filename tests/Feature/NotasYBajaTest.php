<?php

namespace Tests\Feature;

use App\Jobs\DarDeBajaEnSunat;
use App\Jobs\SendInvoiceToSunat;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Support\ApisPeruService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Corregir un comprobante ya emitido.
 *
 * La única salida que había era borrar la fila: el comprobante desaparecía del
 * sistema y seguía vivo en SUNAT con su IGV declarado. El negocio quedaba
 * debiendo el impuesto de una venta que ya no podía ni consultar.
 */
class NotasYBajaTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        foreach (['invoices.ver', 'invoices.crear', 'invoices.editar', 'invoices.anular'] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ferretería Fiscal', 'slug' => 'ferre-fiscal', 'category' => 'retail', 'is_active' => true,
        ]);
        $m = Module::firstOrCreate(['key' => 'invoices'], ['name' => 'Facturación', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);

        $rol = Role::findOrCreate('fiscal_qa', 'web')
            ->syncPermissions(['invoices.ver', 'invoices.crear', 'invoices.editar', 'invoices.anular']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'Cajero', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $u->syncRoles([$rol->name]);

        $this->actingAs($u)->withSession(['active_project_id' => $this->project->id]);
    }

    /** Una factura aceptada por SUNAT, tal como quedaría tras emitirse. */
    private function facturaAceptada(array $extra = []): Invoice
    {
        $invoice = $this->project->invoices()->create(array_merge([
            'type' => 'factura', 'serie' => 'F001', 'correlativo' => 7,
            'numero' => Invoice::buildNumero('F001', 7),
            'client_name' => 'Constructora Andina SAC',
            'client_doc_type' => 'RUC', 'client_doc_number' => '20600819110',
            'emisor_ruc' => '20512345678', 'emisor_razon_social' => 'Ferretería Fiscal SAC',
            'subtotal' => '100.00', 'igv' => '18.00', 'total' => '118.00',
            'igv_included' => false, 'issue_date' => now()->toDateString(),
            'status' => 'issued', 'sunat_status' => 'accepted',
        ], $extra));

        $invoice->items()->create([
            'description' => 'Cemento Sol', 'unit' => 'BOLSA', 'quantity' => 4,
            'unit_price' => '25.00', 'igv_amount' => '18.00', 'total' => '118.00',
        ]);

        return $invoice->fresh('items');
    }

    /** El número se imprime con ceros: "F001-1" no lo reconoce SUNAT. */
    public function test_el_numero_lleva_ceros_a_la_izquierda(): void
    {
        $this->assertSame('F001-00000007', Invoice::buildNumero('F001', 7));
        $this->assertSame('B001-00000123', Invoice::buildNumero('B001', 123));
    }

    /** Anular una venta es devolver todo lo facturado: la nota copia el documento. */
    public function test_la_nota_de_credito_copia_la_factura_y_dice_a_que_afecta(): void
    {
        $factura = $this->facturaAceptada();

        $this->postJson('/invoices/'.$factura->id.'/nota', [
            'type' => 'nota_credito', 'motivo_codigo' => '01',
        ])->assertSuccessful();

        $nota = Invoice::where('type', 'nota_credito')->latest('id')->first();

        $this->assertNotNull($nota, 'no se creó la nota');
        $this->assertSame($factura->id, $nota->afecta_invoice_id);
        $this->assertSame('01', $nota->afecta_tipo, 'el afectado es una factura: código 01');
        $this->assertSame('F001-00000007', $nota->afecta_numero);
        $this->assertSame('01', $nota->motivo_codigo);
        $this->assertSame('Anulación de la operación', $nota->motivo_descripcion);

        // Mismos importes y mismo cliente que el documento que corrige.
        $this->assertSame('118.00', (string) $nota->total);
        $this->assertSame('20600819110', $nota->client_doc_number);
        $this->assertCount(1, $nota->items);
    }

    /** Y sale hacia SUNAT sola: una nota emitida y no enviada no corrige nada. */
    public function test_la_nota_se_envia_a_sunat(): void
    {
        $factura = $this->facturaAceptada();

        $this->postJson('/invoices/'.$factura->id.'/nota', [
            'type' => 'nota_credito', 'motivo_codigo' => '06',
        ])->assertSuccessful();

        Queue::assertPushed(SendInvoiceToSunat::class);
        $this->assertSame('pending', Invoice::where('type', 'nota_credito')->latest('id')->first()->sunat_status);
    }

    /**
     * La serie de la nota empieza por la misma letra que el documento afectado:
     * una nota "B" sobre una factura "F" la rechaza SUNAT.
     */
    public function test_la_serie_de_la_nota_sigue_a_la_del_documento_afectado(): void
    {
        $boleta = $this->facturaAceptada(['type' => 'boleta', 'serie' => 'B001', 'correlativo' => 3,
            'numero' => Invoice::buildNumero('B001', 3)]);

        $this->postJson('/invoices/'.$boleta->id.'/nota', [
            'type' => 'nota_credito', 'motivo_codigo' => '01',
        ])->assertSuccessful();

        $nota = Invoice::where('type', 'nota_credito')->latest('id')->first();

        $this->assertSame('B001', $nota->serie);
        $this->assertSame('03', $nota->afecta_tipo, 'el afectado es una boleta: código 03');
    }

    /** Sobre lo que SUNAT no ha aceptado no hay nada que corregir. */
    public function test_no_se_emite_nota_sobre_un_comprobante_sin_aceptar(): void
    {
        $borrador = $this->facturaAceptada(['sunat_status' => null]);

        $this->postJson('/invoices/'.$borrador->id.'/nota', [
            'type' => 'nota_credito', 'motivo_codigo' => '01',
        ])->assertStatus(422);

        $this->assertSame(0, Invoice::where('type', 'nota_credito')->count());
    }

    /** Ni una nota se corrige con otra nota. */
    public function test_no_se_emite_una_nota_sobre_otra_nota(): void
    {
        $nota = $this->facturaAceptada(['type' => 'nota_credito', 'serie' => 'F001', 'correlativo' => 9,
            'numero' => Invoice::buildNumero('F001', 9)]);

        $this->postJson('/invoices/'.$nota->id.'/nota', [
            'type' => 'nota_credito', 'motivo_codigo' => '01',
        ])->assertStatus(422);
    }

    /** El motivo tiene que ser uno del catálogo, no texto libre. */
    public function test_el_motivo_sale_del_catalogo_oficial(): void
    {
        $factura = $this->facturaAceptada();

        $this->postJson('/invoices/'.$factura->id.'/nota', [
            'type' => 'nota_credito', 'motivo_codigo' => '99',
        ])->assertStatus(422);

        // El 01 de débito ("intereses por mora") no vale para una de crédito.
        $this->postJson('/invoices/'.$factura->id.'/nota', [
            'type' => 'nota_debito', 'motivo_codigo' => '13',
        ])->assertStatus(422);
    }

    /** La baja no borra: el comprobante se queda con su número, marcado. */
    public function test_la_baja_no_borra_el_comprobante(): void
    {
        $factura = $this->facturaAceptada();

        $this->postJson('/invoices/'.$factura->id.'/baja', [
            'motivo' => 'Error en el RUC del cliente',
        ])->assertSuccessful();

        $factura->refresh();

        $this->assertSame('pending', $factura->baja_estado);
        $this->assertSame('Error en el RUC del cliente', $factura->baja_motivo);
        $this->assertSame('cancelled', $factura->status);
        $this->assertSame('F001-00000007', $factura->numero, 'el número no se toca: no se reutiliza jamás');
        Queue::assertPushed(DarDeBajaEnSunat::class);
    }

    /** Sin motivo no hay baja: SUNAT lo exige y el auditor también. */
    public function test_la_baja_exige_motivo(): void
    {
        $factura = $this->facturaAceptada();

        $this->postJson('/invoices/'.$factura->id.'/baja', [])->assertStatus(422);
        $this->assertNull($factura->fresh()->baja_estado);
    }

    /** Y no se da de baja lo que SUNAT nunca aceptó. */
    public function test_no_se_da_de_baja_lo_que_no_esta_aceptado(): void
    {
        $borrador = $this->facturaAceptada(['sunat_status' => 'error']);

        $this->postJson('/invoices/'.$borrador->id.'/baja', ['motivo' => 'me equivoqué'])
            ->assertStatus(422);
    }

    /** Un comprobante aceptado ya no se borra: se corrige. */
    public function test_un_comprobante_aceptado_ya_no_se_puede_borrar(): void
    {
        $this->assertFalse($this->facturaAceptada()->sePuedeBorrar());

        // Otro correlativo: la tabla no admite dos comprobantes con el mismo
        // número de serie, que es justo lo que impide un duplicado ante SUNAT.
        $sinEnviar = $this->facturaAceptada([
            'sunat_status' => null, 'correlativo' => 8, 'numero' => Invoice::buildNumero('F001', 8),
        ]);

        $this->assertTrue($sinEnviar->sePuedeBorrar());
    }

    /** Dos emisiones seguidas nunca comparten número. */
    public function test_el_correlativo_no_se_repite(): void
    {
        $vistos = [];

        for ($i = 0; $i < 5; $i++) {
            [$correlativo, $numero] = Invoice::emitirNumero($this->project->id, 'factura', 'F001');
            $this->project->invoices()->create([
                'type' => 'factura', 'serie' => 'F001', 'correlativo' => $correlativo, 'numero' => $numero,
                'client_name' => 'X', 'subtotal' => '1.00', 'igv' => '0.18', 'total' => '1.18',
                'issue_date' => now()->toDateString(),
            ]);
            $vistos[] = $correlativo;
        }

        $this->assertSame([1, 2, 3, 4, 5], $vistos);
        $this->assertSame(count($vistos), count(array_unique($vistos)));
    }

    /**
     * El payload que sale hacia el proveedor.
     *
     * Sin `tipDocAfectado`, `numDocfectado` y `codMotivo` la nota viaja como si
     * fuera una factura y SUNAT la rechaza, por muy bien que estén los importes.
     */
    public function test_el_payload_de_la_nota_dice_a_que_afecta_y_por_que(): void
    {
        $factura = $this->facturaAceptada();

        $this->postJson('/invoices/'.$factura->id.'/nota', [
            'type' => 'nota_credito', 'motivo_codigo' => '06',
        ])->assertSuccessful();

        $nota = Invoice::where('type', 'nota_credito')->latest('id')->first()->load('items', 'project');

        $metodo = new \ReflectionMethod(ApisPeruService::class, 'buildPayload');
        $metodo->setAccessible(true);
        $payload = $metodo->invoke(new ApisPeruService(), $nota, '07');

        $this->assertSame('01', $payload['tipDocAfectado'], 'afecta a una factura');
        $this->assertSame('F001-00000007', $payload['numDocfectado']);
        $this->assertSame('06', $payload['codMotivo']);
        $this->assertSame('Devolución total', $payload['desMotivo']);
        $this->assertArrayNotHasKey('formaPago', $payload, 'una nota no lleva forma de pago');

        // Y la unidad viaja con su código oficial, no con el texto del producto.
        $this->assertSame('BG', $payload['details'][0]['unidad'], '"BOLSA" viaja como BG');
    }

    /** El documento del cliente también viaja con su código del catálogo 06. */
    public function test_el_documento_del_cliente_viaja_con_su_codigo(): void
    {
        $factura = $this->facturaAceptada()->load('items', 'project');

        $metodo = new \ReflectionMethod(ApisPeruService::class, 'buildPayload');
        $metodo->setAccessible(true);
        $payload = $metodo->invoke(new ApisPeruService(), $factura, '01');

        $this->assertSame('6', $payload['client']['tipoDoc'], 'RUC es 6');
        $this->assertSame('20600819110', $payload['client']['numDoc']);
    }
}
