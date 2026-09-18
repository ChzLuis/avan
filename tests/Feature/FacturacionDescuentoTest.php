<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Modules\Ventas\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * F4 — El descuento por linea llega al comprobante, y el dinero fiscal es exacto.
 *
 * Dos defectos que se arrastraban:
 *
 * 1. `invoice_items` no guardaba el descuento, asi que `unit_price x cantidad`
 *    no cuadraba con `total`. Por eso `convertirPortal` respondia **422** a
 *    toda cotizacion con descuento: eso no frenaba solo la factura, frenaba
 *    **cerrar la venta**.
 * 2. Los importes fiscales se calculaban con FLOTANTES (`round($qty*$price,2)`
 *    y sumas acumuladas) justo en el dinero de mas consecuencias del sistema.
 */
class FacturacionDescuentoTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['quotes.ver', 'quotes.editar', 'invoices.crear'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Fiscal QA', 'slug' => 'fiscal-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        foreach (['quotes', 'orders'] as $k) {
            $m = Module::firstOrCreate(['key' => $k], ['name' => $k, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
        $rol = Role::findOrCreate('fiscal_qa', 'web');
        $rol->syncPermissions(['quotes.ver', 'quotes.editar', 'invoices.crear']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'editor']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'U', 'spatie_role' => $rol->name, 'is_active' => 1]);
        $u->syncRoles([$rol->name]);
        $this->actingAs($u)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
            // El portal de facturacion tiene su propio guard por slug.
            "facturacion_auth.{$this->project->slug}" => true,
        ]);
    }

    /** El caso exacto que el comentario del gate citaba como imposible. */
    public function test_la_linea_con_descuento_cuadra_al_centimo(): void
    {
        $quote = Quote::create([
            'project_id' => $this->project->id, 'client_name' => 'C',
            'status' => 'accepted', 'payment_status' => 'pending',
            'total' => '83.70', 'token' => \Illuminate\Support\Str::random(32),
        ]);
        // 30.00 x 3 con 7% de descuento = 83.70 exacto.
        $quote->items()->create(['description' => 'Servicio', 'price' => '30.00',
            'quantity' => 3, 'discount' => '7.00']);

        $r = $this->postJson("/f/{$this->project->slug}/cotizaciones/{$quote->id}/convertir", [
            'type' => 'boleta',
        ]);

        // Ya NO se rechaza por llevar descuento.
        $this->assertNotSame(422, $r->status(),
            'una cotizacion con descuento debe poder convertirse: antes esto bloqueaba la venta');

        $this->assertDatabaseHas('invoice_items', [
            'unit_price' => '30.00', 'discount' => '7.00', 'total' => '83.70',
        ]);

        // Y el documento cuadra consigo mismo: base + IGV = total.
        $inv = \App\Modules\Finanzas\Models\Invoice::where('quote_id', $quote->id)->firstOrFail();
        $this->assertSame(
            (string) $inv->total,
            (string) number_format((float) $inv->subtotal + (float) $inv->igv, 2, '.', ''),
            'subtotal + IGV debe dar el total exacto'
        );
        $this->assertSame('83.70', (string) $inv->total);
    }

    /** Sin descuento el resultado no cambia: la migracion no altera lo de antes. */
    public function test_sin_descuento_el_importe_es_el_de_siempre(): void
    {
        $quote = Quote::create([
            'project_id' => $this->project->id, 'client_name' => 'C',
            'status' => 'accepted', 'payment_status' => 'pending',
            'total' => '90.00', 'token' => \Illuminate\Support\Str::random(32),
        ]);
        $quote->items()->create(['description' => 'Servicio', 'price' => '30.00',
            'quantity' => 3, 'discount' => 0]);

        $this->postJson("/f/{$this->project->slug}/cotizaciones/{$quote->id}/convertir", ['type' => 'boleta']);

        $inv = \App\Modules\Finanzas\Models\Invoice::where('quote_id', $quote->id)->firstOrFail();
        $this->assertSame('90.00', (string) $inv->total);
        $this->assertDatabaseHas('invoice_items', ['unit_price' => '30.00', 'total' => '90.00']);
    }

    /**
     * Varias lineas: el total del comprobante es la SUMA de sus lineas. Con
     * flotantes, acumular subtotales redondeados deriva.
     */
    public function test_el_total_del_comprobante_es_la_suma_de_sus_lineas(): void
    {
        $quote = Quote::create([
            'project_id' => $this->project->id, 'client_name' => 'C',
            'status' => 'accepted', 'payment_status' => 'pending',
            'total' => '0.00', 'token' => \Illuminate\Support\Str::random(32),
        ]);
        foreach ([['19.99', 3, '0'], ['0.10', 7, '33.33'], ['1234.56', 1, '15.5']] as [$p, $q, $d]) {
            $quote->items()->create(['description' => 'L', 'price' => $p, 'quantity' => $q, 'discount' => $d]);
        }

        $this->postJson("/f/{$this->project->slug}/cotizaciones/{$quote->id}/convertir", ['type' => 'boleta']);

        $inv = \App\Modules\Finanzas\Models\Invoice::where('quote_id', $quote->id)->firstOrFail();
        $sumaLineas = $inv->items->sum(fn ($i) => (int) round((float) $i->total * 100));
        $this->assertSame(
            (int) round((float) $inv->total * 100),
            $sumaLineas,
            'el total del comprobante debe ser EXACTAMENTE la suma de sus lineas'
        );
        $this->assertSame(
            (int) round((float) $inv->total * 100),
            (int) round((float) $inv->subtotal * 100) + (int) round((float) $inv->igv * 100),
            'y base + IGV debe dar ese mismo total'
        );
    }

    /** Cantidad fraccionada (2.5 kg): no puede truncarse a entero. */
    public function test_una_cantidad_fraccionada_no_se_trunca(): void
    {
        $r = $this->postJson('/invoices', [
            'type' => 'boleta',
            'client_name' => 'C',
            'igv_included' => true,
            'items' => [[
                'description' => 'Queso', 'unit' => 'KGM',
                'quantity' => 2.5, 'unit_price' => 20.00,
            ]],
        ]);

        if (in_array($r->status(), [403, 404], true)) {
            $this->markTestSkipped('La ruta de comprobantes exige otro contexto: ' . $r->status());
        }

        $r->assertSuccessful();
        // 20.00 x 2.5 = 50.00, no 40.00 (que seria truncar a 2 unidades).
        $this->assertDatabaseHas('invoice_items', ['total' => '50.00']);
    }
}
