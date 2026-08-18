<?php

namespace Tests\Feature;

use App\Jobs\SendInvoiceToSunat;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cada negocio elige su proveedor fiscal.
 *
 * Hay dos integraciones vivas —Nubefact y APIsPERU— y la decisión es del
 * negocio, no del sistema: se configura por proyecto. Lo que este contrato
 * protege es que esa elección se respete y que nadie herede el proveedor del
 * vecino, porque las credenciales de uno no sirven para el otro y un
 * comprobante emitido no se puede deshacer.
 */
class ProveedorFiscalPorProyectoTest extends TestCase
{
    use RefreshDatabase;

    private function proyecto(string $slug, array $ajustes = []): Project
    {
        $p = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => strtoupper($slug), 'slug' => $slug,
            'category' => 'retail', 'is_active' => true,
        ]);
        foreach ($ajustes as $k => $v) {
            $p->settings()->updateOrCreate(['key' => $k], ['value' => $v]);
        }

        return $p->fresh();
    }

    private function factura(Project $project): Invoice
    {
        return Invoice::create([
            'project_id' => $project->id, 'type' => 'factura',
            'serie' => 'F001', 'correlativo' => 1, 'numero' => 'F001-1',
            'client_name' => 'Cliente', 'client_doc' => '20600819110',
            'subtotal' => '100.00', 'igv' => '18.00', 'total' => '118.00',
            'sunat_status' => 'pending',
        ]);
    }

    /**
     * Sin proveedor elegido no se asume ninguno. Antes el sistema caía en
     * Nubefact por defecto y pedía sus credenciales a quien iba a usar
     * APIsPERU: un mensaje que manda a configurar lo que no toca.
     */
    public function test_sin_proveedor_elegido_se_pide_elegirlo(): void
    {
        $project = $this->proyecto('sin-proveedor');
        $invoice = $this->factura($project);

        (new SendInvoiceToSunat($invoice->id))->handle();

        $invoice->refresh();
        $this->assertSame('error', $invoice->sunat_status);
        $this->assertStringContainsString('Elige el proveedor', $invoice->sunat_error);
        // Y no menciona uno solo: la elección sigue abierta.
        $this->assertStringContainsString('Nubefact', $invoice->sunat_error);
        $this->assertStringContainsString('APIsPERU', $invoice->sunat_error);
    }

    /** Elegido Nubefact, se piden SUS credenciales. */
    public function test_con_nubefact_se_piden_las_credenciales_de_nubefact(): void
    {
        $project = $this->proyecto('con-nubefact', ['billing_provider' => 'nubefact']);
        $invoice = $this->factura($project);

        (new SendInvoiceToSunat($invoice->id))->handle();

        $invoice->refresh();
        $this->assertSame('error', $invoice->sunat_status);
        $this->assertStringContainsString('Nubefact', $invoice->sunat_error);
        $this->assertStringNotContainsString('APIsPERU', $invoice->sunat_error);
    }

    /** Elegido APIsPERU, se piden las suyas. */
    public function test_con_apisperu_se_piden_las_credenciales_de_apisperu(): void
    {
        $project = $this->proyecto('con-apisperu', ['billing_provider' => 'apisperu']);
        $invoice = $this->factura($project);

        (new SendInvoiceToSunat($invoice->id))->handle();

        $invoice->refresh();
        $this->assertSame('error', $invoice->sunat_status);
        $this->assertStringContainsString('APIsPERU', $invoice->sunat_error);
        $this->assertStringNotContainsString('Nubefact', $invoice->sunat_error);
    }

    /**
     * Dos negocios con proveedores distintos conviven. Es el caso real: una
     * empresa con Nubefact y otra con APIsPERU en el mismo panel.
     */
    public function test_dos_negocios_pueden_usar_proveedores_distintos(): void
    {
        $a = $this->proyecto('negocio-a', ['billing_provider' => 'nubefact']);
        $b = $this->proyecto('negocio-b', ['billing_provider' => 'apisperu']);

        $fa = $this->factura($a);
        $fb = $this->factura($b);

        (new SendInvoiceToSunat($fa->id))->handle();
        (new SendInvoiceToSunat($fb->id))->handle();

        $this->assertStringContainsString('Nubefact', $fa->refresh()->sunat_error);
        $this->assertStringContainsString('APIsPERU', $fb->refresh()->sunat_error);
    }

    /**
     * El fallo nunca es silencioso: una factura que no salió tiene que
     * decirlo. Antes podía quedarse en "pending" para siempre y el negocio
     * creía que estaba emitida.
     */
    public function test_una_factura_que_no_sale_lo_dice(): void
    {
        $project = $this->proyecto('sin-nada');
        $invoice = $this->factura($project);

        (new SendInvoiceToSunat($invoice->id))->handle();

        $this->assertNotSame('pending', $invoice->refresh()->sunat_status);
        $this->assertNotEmpty($invoice->sunat_error);
    }

    /** Una factura ya aceptada por SUNAT no se reenvía: es irreversible. */
    public function test_una_factura_aceptada_no_se_reenvia(): void
    {
        $project = $this->proyecto('ya-emitida', ['billing_provider' => 'apisperu']);
        $invoice = $this->factura($project);
        $invoice->update(['sunat_status' => 'accepted', 'sunat_error' => null]);

        (new SendInvoiceToSunat($invoice->id))->handle();

        $invoice->refresh();
        $this->assertSame('accepted', $invoice->sunat_status);
        $this->assertNull($invoice->sunat_error);
    }
}
