<?php

namespace Tests\Feature;

use App\Modules\Finanzas\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Confirmacion de emision y vista del comprobante emitido (2026-09-12).
 *
 * Tres fallos reales:
 *  - La vista previa moria con "Descuento invalido: ''": dejar Desc. % en
 *    blanco es lo normal, y `?? 0` no sustituye la cadena vacia.
 *  - El aviso de confirmacion no decia NI la fecha en que se emite NI la
 *    direccion, los dos datos que ya no se pueden cambiar despues.
 *  - En el detalle el numero salia tres veces y Descargar/Imprimir eran
 *    iconos grises de 16px que nadie encontraba.
 */
class ComprobanteResumenYAccionesTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_superadmin' => true]);
        $this->project = Project::create([
            'owner_id' => $this->user->id, 'name' => 'Ferretería QA',
            'slug' => 'cr-'.uniqid(), 'is_active' => true,
        ]);
        $m = \App\Models\Module::firstOrCreate(['key' => 'invoices'], ['name' => 'invoices', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
    }

    private function comoDueno()
    {
        return $this->actingAs($this->user)->withSession(['active_project_id' => $this->project->id]);
    }

    /**
     * Un Desc. % en blanco no puede tumbar la vista previa.
     *
     * Es el caso NORMAL: el cajero no escribe descuento. Antes llegaba '' a
     * LineMath::toBasisPoints(), que solo acepta digitos, y la previa se caia
     * entera con 500.
     */
    public function test_la_vista_previa_aguanta_el_descuento_vacio(): void
    {
        $this->comoDueno()->post(route('invoices.previsualizar'), [
            'type'              => 'factura',
            'client_name'       => 'NOEL & CIA S.A.C.',
            'client_doc_type'   => 'RUC',
            'client_doc_number' => '20602814379',
            'client_address'    => 'AV. ARGENTINA 339',
            'issue_date'        => now()->toDateString(),
            'items'             => [
                ['description' => 'CABLE THW', 'quantity' => 2, 'unit_price' => '35.50', 'discount' => ''],
                ['description' => 'INTERRUPTOR', 'quantity' => 10, 'unit_price' => '8.90', 'discount' => '5'],
                ['description' => 'TOMACORRIENTE', 'quantity' => 4, 'unit_price' => '12.00'],
            ],
        ])->assertOk()
          ->assertSee('CABLE THW')
          ->assertSee('AV. ARGENTINA 339');
    }

    /** El aviso de confirmacion muestra la fecha de emision y la direccion. */
    public function test_el_resumen_de_confirmacion_dice_fecha_y_direccion(): void
    {
        $html = $this->comoDueno()->get(route('invoices.index'))->assertOk()->getContent();

        foreach (['confirmar-fecha', 'confirmar-direccion', 'fechaLarga(form.issue_date)',
                  'form.client_address'] as $pieza) {
            $this->assertStringContainsString($pieza, $html, "falta $pieza en el resumen");
        }
    }

    /** fechaLarga() no depende de Date(): en UTC-5 saldria el dia anterior. */
    public function test_la_fecha_se_arma_sin_new_date(): void
    {
        $html = $this->comoDueno()->get(route('invoices.index'))->assertOk()->getContent();

        $this->assertStringContainsString("'setiembre'", $html, 'los meses van en espanol');
        $this->assertStringContainsString('fechaLarga(iso)', $html);
    }

    /** Descargar e Imprimir llevan texto, no solo un icono. */
    public function test_descargar_e_imprimir_se_notan(): void
    {
        $html = $this->comoDueno()->get(route('invoices.index'))->assertOk()->getContent();

        $this->assertStringContainsString('<span>Descargar</span>', $html);
        $this->assertStringContainsString('<span>Imprimir</span>', $html);
        $this->assertStringContainsString('.inv-accion-fuerte', $html, 'falta el estilo del boton principal');
    }

    /** El numero no se repite en la ficha: ya esta en el titulo y en el PDF. */
    public function test_el_numero_no_se_repite_en_la_ficha(): void
    {
        Invoice::create([
            'project_id' => $this->project->id, 'type' => 'factura', 'serie' => 'F001',
            'correlativo' => 17, 'numero' => 'F001-00000017', 'client_name' => 'NOEL & CIA S.A.C.',
            'client_doc_type' => 'RUC', 'client_doc_number' => '20602814379',
            'total' => 203.55, 'status' => 'issued', 'issue_date' => now()->toDateString(),
        ]);

        $html = $this->comoDueno()->get(route('invoices.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('x-text="selected.numero"></p>', $html,
            'la ficha ya no debe repetir el numero');
        // En el titulo si sigue, con su respaldo por si aun no tiene numero.
        $this->assertStringContainsString(
            "x-text=\"selected.numero || 'Comprobante #'+selected.id\"", $html,
            'pero si debe seguir en el titulo'
        );
    }
}
